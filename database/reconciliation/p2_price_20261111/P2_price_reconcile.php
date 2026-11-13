<?php
defined('BASEPATH') or exit('No direct script access allowed');

/** One-off reconciliation runner; install only in the isolated CLI test app. */
class P2_price_reconcile extends CI_Controller
{
    private function check($condition, $message)
    {
        if (!$condition) { throw new RuntimeException($message); }
    }

    private function scalar($sql, array $bindings = array())
    {
        $row = $this->db->query($sql, $bindings)->row_array();
        return reset($row);
    }

    private function prepare()
    {
        $this->check(is_cli() && ENVIRONMENT === 'testing', 'CLI testing wajib.');
        $this->check($this->db->database === 'kiucoid_kiupo_merge_p0_20261110', 'Hanya database uji lokal yang diizinkan.');
        $this->db->db_debug = false;
        $path = getenv('P2_PRICE_MANIFEST');
        $this->check($path && is_file($path), 'Manifest bukti wajib tersedia.');
        $raw = file_get_contents($path);
        $plan = json_decode($raw, true);
        $this->check(is_array($plan) && count($plan) === 101, 'Cakupan manifest harus 101 batch.');
        return array($plan, hash('sha256', $raw));
    }

    private function sameRow($table, $key, array $expected, $lock = false)
    {
        $row = $this->db->query("SELECT * FROM $table WHERE $key=?" . ($lock ? ' FOR UPDATE' : ''),
            array($expected[$key]))->row_array();
        $this->check($row !== null, "Bukti hilang: $table/$key=" . $expected[$key]);
        foreach ($expected as $column => $value) {
            $actual = $row[$column];
            $equal = $value === null ? $actual === null : ($actual !== null && (string)$actual === (string)$value);
            if (!$equal && $value !== null && $actual !== null && is_numeric($value) && is_numeric($actual)) {
                $equal = abs((float)$value - (float)$actual) < 0.0000001;
            }
            $this->check($equal, "Bukti berubah: $table.$column/$key=" . $expected[$key]);
        }
        return $row;
    }

    private function stockChecks()
    {
        $row = $this->db->query("SELECT COUNT(*) items,
            SUM(ABS(v.qty_ready-COALESCE(b.qty,0))>0.0001) gaps,SUM(v.qty_ready<0) negative
            FROM v_stockbarangnk v LEFT JOIN
            (SELECT kd_barang,SUM(qty_sisa) qty FROM tbpo_stock_lifo_batch_nk GROUP BY kd_barang) b
            ON b.kd_barang=v.kode_barangs")->row_array();
        $this->check((int)$row['items'] === 943 && (int)$row['gaps'] === 0 && (int)$row['negative'] === 0,
            'Saldo kuantitas berubah.');
        $this->check((int)$this->scalar("SELECT COUNT(*) FROM tbpo_stock_lifo_batch_nk b LEFT JOIN
            (SELECT id_batch,SUM(qty_alokasi) qty FROM tbpo_stock_lifo_allocation GROUP BY id_batch) a
            ON a.id_batch=b.id_batch WHERE ABS(b.qty_awal-b.qty_sisa-COALESCE(a.qty,0))>0.0001 OR b.qty_sisa<0") === 0,
            'Konservasi batch tidak cocok.');
        $this->check((int)$this->scalar("SELECT COUNT(*) FROM tbpo_transaksi t LEFT JOIN
            (SELECT id_transnk_keluar,SUM(qty_alokasi) qty FROM tbpo_stock_lifo_allocation GROUP BY id_transnk_keluar) a
            ON a.id_transnk_keluar=t.id_transnk WHERE t.kd_akun IN ('11512','11514')
            AND ABS(t.tr_qty-COALESCE(a.qty,0))>0.0001") === 0, 'Alokasi keluar tidak cocok.');
        return $row;
    }

    public function index()
    {
        list($plan, $manifestHash) = $this->prepare();
        if ($this->db->table_exists('p2_price_reconcile_run') &&
            (int)$this->scalar("SELECT COUNT(*) FROM p2_price_reconcile_run WHERE state='COMPLETE'") === 1) {
            $this->check($this->scalar('SELECT manifest_sha256 FROM p2_price_reconcile_run WHERE id=1') === $manifestHash,
                'Manifest berbeda dari pekerjaan yang sudah selesai.');
            $this->verify();
            echo "Tidak ada perubahan ulang.\n";
            return;
        }
        $chosen = array_filter($plan, function ($item) { return $item['chosen'] !== null; });
        $this->check(count($chosen) === 12, 'Harus ada 12 batch berharga terbukti.');
        $this->stockChecks();

        foreach (array('tbpo_stock_lifo_batch_nk','tbpo_stock_lifo_allocation',
            'tbpo_stock_lifo_harga_log','tbpo_stock_lifo_rebuild_issue') as $table) {
            $backup = 'p2_price_backup_' . $table;
            $this->check(!$this->db->table_exists($backup), 'Backup sudah ada; periksa pekerjaan sebelumnya.');
            $this->check($this->db->query("CREATE TABLE $backup LIKE $table"), 'Backup gagal dibuat.');
            $this->check($this->db->query("INSERT INTO $backup SELECT * FROM $table"), 'Backup gagal disalin.');
        }
        $this->check($this->db->query("CREATE TABLE p2_price_closure_review (
            id_batch BIGINT UNSIGNED PRIMARY KEY,kd_barang VARCHAR(25) NOT NULL,id_transnk_masuk INT NOT NULL,
            qty_sisa DECIMAL(18,3),keputusan VARCHAR(40),alasan TEXT,harga_diterapkan DECIMAL(18,2) NULL,
            dasar_harga VARCHAR(20),bukti_json LONGTEXT,bukti_sha256 CHAR(64),checked_at DATETIME
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"), 'Tabel review gagal dibuat.');
        $this->check($this->db->query("CREATE TABLE p2_price_reconcile_run (
            id INT PRIMARY KEY,state VARCHAR(20),manifest_sha256 CHAR(64),reviewed_batches INT,
            priced_batches INT,priced_qty DECIMAL(18,3),updated_allocations INT,completed_at DATETIME
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"), 'Tabel audit gagal dibuat.');

        $this->check($this->db->trans_begin(), 'Transaksi tidak dapat dimulai.');
        try {
            $count = 0; $qty = 0; $allocations = 0;
            foreach ($plan as $item) {
                $batch = $item['batch']; $price = $item['chosen'];
                $this->sameRow('tbpo_stock_lifo_batch_nk', 'id_batch', $batch, true);
                $this->sameRow('tbpo_transaksi', 'id_transnk', $item['transaction']);
                if ($price !== null) {
                    $sources = $item['explicit_sources'];
                    $this->check(count($sources) === 2, 'Dua bukti sumber wajib.');
                    foreach ($sources as $source) {
                        $this->check(in_array($source['db'], array('kiucoid_kiupo_baru','kiucoid_po'), true), 'Sumber tidak diizinkan.');
                        $prefix = $source['db'] === 'kiucoid_po' ? 'tb_' : 'tbpo_';
                        $this->sameRow($source['db'].'.'.$prefix.'detail_po_nk', 'id_det_po_nk', $source['detail']);
                        $this->sameRow($source['db'].'.'.$prefix.'po_nk', 'id_po_nk', $source['po']);
                        $detail = $source['detail'];
                        $this->check($detail['kd_barang'] === $batch['kd_barang'] && $detail['kd_bsys'] === $batch['kd_barangsys'] &&
                            (int)$detail['satuan'] === (int)$item['transaction']['satuan'] && $source['price'] === $price['price'] &&
                            $source['base'] === $price['base'] && !empty($source['matches']), 'Identitas/harga bukti tidak cocok.');
                        $this->check($source['real'] === null && $price['base'] === 'PENGAJUAN' &&
                            (float)$detail['hrg_satuan'] === (float)$price['price'], 'Dasar harga di luar manifest ini.');
                    }
                    $detail = $sources[0]['detail'];
                    $this->sameRow('tbpo_detail_po_nk', 'id_det_po_nk', array_intersect_key($detail,
                        array_flip(array('id_det_po_nk','kd_po_nk','kd_barang','kd_bsys','satuan','hrg_satuan'))));
                    if ((int)$batch['id_batch'] === 4488) {
                        $this->check(isset($price['user_confirmation']) && $price['user_confirmation'] === 'Rp4.500 per satu gantungan',
                            'Konfirmasi satuan gantungan wajib.');
                    }
                    $note = 'Rekonsiliasi dua DB lokal; PO '.$detail['kd_po_nk'].' detail '.$detail['id_det_po_nk'].'; mutasi '.$batch['id_transnk_masuk'];
                    if (isset($price['user_confirmation'])) { $note .= '; pengguna: 4500 per gantungan'; }
                    $this->db->insert('tbpo_stock_lifo_harga_log', array(
                        'id_batch'=>$batch['id_batch'],'harga_lama'=>$batch['harga_satuan'],'harga_baru'=>$price['price'],
                        'dasar_harga_lama'=>$batch['dasar_harga'],'dasar_harga_baru'=>$price['base'],
                        'alasan'=>$note,'kd_user'=>'P2_PRICE_RECONCILE'
                    ));
                    $this->db->where('id_batch', $batch['id_batch'])->update('tbpo_stock_lifo_batch_nk', array(
                        'id_detail_po_nk'=>$detail['id_det_po_nk'],'harga_satuan'=>$price['price'],
                        'dasar_harga'=>$price['base'],'status_harga'=>'VALID','catatan_harga'=>$note,
                        'dibuat_oleh'=>'P2_PRICE_RECONCILE'
                    ));
                    $this->db->query('UPDATE tbpo_stock_lifo_allocation SET harga_satuan_snapshot=?,nilai_alokasi=qty_alokasi*? WHERE id_batch=?',
                        array($price['price'],$price['price'],$batch['id_batch']));
                    $allocations += $this->db->affected_rows();
                    $this->db->where(array('id_transnk'=>$batch['id_transnk_masuk'],'jenis_issue'=>'PERLU_HARGA','resolved_at'=>null))
                        ->update('tbpo_stock_lifo_rebuild_issue',array('resolved_at'=>date('Y-m-d H:i:s'),'resolved_by'=>'P2_PRICE_RECONCILE'));
                    $count++; $qty += $batch['qty_sisa'];
                }
                $evidence = json_encode($item);
                $this->db->insert('p2_price_closure_review', array(
                    'id_batch'=>$batch['id_batch'],'kd_barang'=>$batch['kd_barang'],'id_transnk_masuk'=>$batch['id_transnk_masuk'],
                    'qty_sisa'=>$batch['qty_sisa'],'keputusan'=>$item['decision'],'alasan'=>$item['reason'],
                    'harga_diterapkan'=>$price === null ? null : $price['price'],
                    'dasar_harga'=>$price === null ? 'BELUM_ADA' : $price['base'],
                    'bukti_json'=>$evidence,'bukti_sha256'=>hash('sha256',$evidence),'checked_at'=>date('Y-m-d H:i:s')
                ));
            }
            $this->check($count === 12 && abs($qty-16)<0.0001, 'Jumlah penerapan harga tidak cocok.');
            $this->stockChecks();
            $this->db->insert('p2_price_reconcile_run',array('id'=>1,'state'=>'COMPLETE','manifest_sha256'=>$manifestHash,
                'reviewed_batches'=>count($plan),'priced_batches'=>$count,'priced_qty'=>$qty,
                'updated_allocations'=>$allocations,'completed_at'=>date('Y-m-d H:i:s')));
            $this->verify();
            $this->check($this->db->trans_status(), 'Penerapan harga gagal; rollback.');
            $this->check($this->db->trans_commit(), 'Commit gagal.');
            echo "Harga 12 batch tersimpan; kuantitas tetap.\n";
        } catch (Throwable $error) {
            $this->db->trans_rollback();
            throw $error;
        }
    }

    public function verify()
    {
        $this->prepare();
        $stock = $this->stockChecks();
        $this->check((int)$this->scalar("SELECT COUNT(*) FROM p2_price_closure_review r JOIN tbpo_stock_lifo_batch_nk b ON b.id_batch=r.id_batch
            WHERE r.keputusan='TERBUKTI' AND (b.status_harga<>'VALID' OR NOT (b.harga_satuan <=> r.harga_diterapkan)
            OR b.dasar_harga<>r.dasar_harga)") === 0, 'Harga terapan tidak cocok.');
        $this->check((int)$this->scalar("SELECT COUNT(*) FROM p2_price_closure_review r JOIN tbpo_stock_lifo_batch_nk b ON b.id_batch=r.id_batch
            WHERE r.keputusan<>'TERBUKTI' AND (b.status_harga<>'PERLU_HARGA' OR b.harga_satuan IS NOT NULL)") === 0,
            'Batch tanpa bukti berubah harga.');
        $this->check((int)$this->scalar("SELECT COUNT(*) FROM tbpo_stock_lifo_allocation a JOIN p2_price_closure_review r ON r.id_batch=a.id_batch
            WHERE r.keputusan='TERBUKTI' AND (NOT(a.harga_satuan_snapshot <=> r.harga_diterapkan)
            OR NOT(a.nilai_alokasi <=> a.qty_alokasi*r.harga_diterapkan))") === 0, 'Snapshot nilai alokasi tidak cocok.');
        $remaining = $this->db->query("SELECT COUNT(*) batches,COUNT(DISTINCT kd_barang) items,SUM(qty_sisa) qty
            FROM tbpo_stock_lifo_batch_nk WHERE qty_sisa>0 AND status_harga='PERLU_HARGA'")->row_array();
        $this->check((int)$remaining['batches'] === 89 && (int)$remaining['items'] === 77 && abs($remaining['qty']-2404.75)<0.0001,
            'Cakupan sisa tidak cocok.');
        echo json_encode(array('stock'=>$stock,'remaining_unpriced'=>$remaining))."\n";
    }

    /** Exercise real allocation and unknown-price rejection, then roll back. */
    public function exercise()
    {
        $this->prepare();
        $this->load->model('stock/M_StockLifo');
        $known = $this->M_StockLifo->can_allocate_outgoing('QHEA001', 1);
        $unknown = $this->M_StockLifo->can_allocate_outgoing('QCDR001', 1);
        $this->check($known['allowed'] && !$unknown['allowed'], 'Gerbang harga tidak sesuai.');
        $this->check($this->db->trans_begin(), 'Transaksi uji gagal dimulai.');
        try {
            foreach (array('QHEA001','QCDR001') as $code) {
                $transaction = $this->db->where('kd_barang', $code)->get('tbpo_transaksi', 1)->row_array();
                unset($transaction['id_transnk'], $transaction['update_at']);
                $transaction['kd_akun'] = '11512';
                $transaction['kd_po_nk'] = 'P2_PRICE_ROLLBACK_TEST';
                $transaction['tr_qty'] = 1;
                $transaction['tgl_transaksi'] = date('Y-m-d H:i:s');
                $transaction['create_at'] = date('Y-m-d');
                $this->check($this->db->insert('tbpo_transaksi', $transaction), 'Mutasi uji gagal.');
                $id = $this->db->insert_id();
                $result = $this->M_StockLifo->allocate_new_outgoing($id);
                if ($code === 'QHEA001') {
                    $this->check($result['success'], 'Batch berharga gagal digunakan.');
                    $row = $this->db->where('id_transnk_keluar', $id)->get('tbpo_stock_lifo_allocation')->row_array();
                    $this->check((int)$row['id_batch'] === 4231 && (float)$row['harga_satuan_snapshot'] === 96834.0 &&
                        (float)$row['nilai_alokasi'] === 96834.0, 'Harga snapshot pengeluaran tidak cocok.');
                } else {
                    $this->check(!$result['success'] && (int)$this->scalar(
                        'SELECT COUNT(*) FROM tbpo_stock_lifo_allocation WHERE id_transnk_keluar=?',array($id)) === 0,
                        'Batch tanpa harga seharusnya tidak dialokasikan.');
                }
            }
            echo "PASS pengeluaran QHEA001 memakai harga 96834; QCDR001 tanpa harga ditolak. Semua mutasi uji di-rollback.\n";
        } finally {
            $this->db->trans_rollback();
        }
        $this->verify();
    }
}
