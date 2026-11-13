<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Buku bantu batch persediaan non-komersil. Semua nilai di sini adalah
 * snapshot audit; harga PO tidak dibaca ulang saat menghitung nilai stok.
 */
class M_StockLifo extends CI_Model
{
    private $batchTable = 'tbpo_stock_lifo_batch';

    public function schema_ready()
    {
        return $this->db->table_exists($this->batchTable)
            && $this->db->table_exists('tbpo_stock_lifo_allocation')
            && $this->db->table_exists('tbpo_stock_lifo_rebuild_issue');
    }

    public function rebuild_history($actor = 'SYSTEM')
    {
        if (!$this->schema_ready()) {
            return array('success' => false, 'message' => 'Schema LIFO belum tersedia. Jalankan migration terlebih dahulu.');
        }

        $this->db->trans_begin();
        $this->db->query('DELETE FROM tbpo_stock_lifo_allocation');
        $this->db->query('DELETE FROM tbpo_stock_lifo_harga_log');
        $this->db->query('DELETE FROM tbpo_stock_lifo_rebuild_issue');
        $this->db->query('DELETE FROM ' . $this->batchTable);

        $transactions = $this->transactions_for_rebuild();
        $summary = array('transaction_count' => count($transactions), 'batches' => 0, 'allocations' => 0, 'issues' => 0);
        foreach ($transactions as $transaction) {
            if (in_array($transaction->kd_akun, array('11511', '11513'), true)) {
                $this->create_batch_from_transaction($transaction, $actor, true);
                $summary['batches']++;
            } else {
                $summary['allocations'] += $this->allocate_historical_outgoing($transaction);
            }
        }
        $summary['issues'] = (int) $this->db->count_all('tbpo_stock_lifo_rebuild_issue');

        if ($this->db->trans_status() === false) {
            $this->db->trans_rollback();
            return array('success' => false, 'message' => 'Rekonstruksi LIFO gagal dan seluruh perubahan dibatalkan.');
        }
        $this->db->trans_commit();
        return array('success' => true, 'summary' => $summary);
    }

    /** Digunakan tahap integrasi sebelum transaksi keluar disimpan. */
    public function can_allocate_outgoing($kodeBarang, $qty)
    {
        $qty = (float) $qty;
        $valid = (float) $this->db->select('COALESCE(SUM(qty_sisa), 0) AS qty', false)
            ->where(array('kd_barang' => $kodeBarang, 'status_batch' => 'AKTIF', 'status_harga' => 'VALID'))
            ->get($this->batchTable)->row()->qty;
        $unpriced = (float) $this->db->select('COALESCE(SUM(qty_sisa), 0) AS qty', false)
            ->where(array('kd_barang' => $kodeBarang, 'status_batch' => 'AKTIF', 'status_harga' => 'PERLU_HARGA'))
            ->get($this->batchTable)->row()->qty;

        return array(
            'allowed' => $qty > 0 && $valid + 0.0001 >= $qty,
            'qty_requested' => $qty,
            'qty_priced_available' => $valid,
            'qty_unpriced_available' => $unpriced,
            'message' => $valid + 0.0001 >= $qty
                ? ''
                : sprintf('Transaksi tidak dapat dilanjutkan. Permintaan %s, stok berharga tersedia %s, dan stok tanpa harga %s.', $qty, $valid, $unpriced)
        );
    }

    public function get_summary($kodeBarang)
    {
        if (!$this->schema_ready()) { return null; }
        return $this->db->query("SELECT
            COUNT(CASE WHEN status_batch='AKTIF' AND qty_sisa > 0 THEN 1 END) AS batch_aktif,
            COALESCE(SUM(CASE WHEN status_batch='AKTIF' THEN qty_sisa ELSE 0 END), 0) AS qty_batch,
            COALESCE(SUM(CASE WHEN status_batch='AKTIF' AND status_harga='VALID' THEN qty_sisa * harga_satuan ELSE 0 END), 0) AS nilai_lifo,
            COALESCE(SUM(CASE WHEN status_batch='AKTIF' AND status_harga='PERLU_HARGA' THEN qty_sisa ELSE 0 END), 0) AS qty_perlu_harga
            FROM {$this->batchTable} WHERE kd_barang=?", array($kodeBarang))->row();
    }

    public function get_active_batches($kodeBarang)
    {
        if (!$this->schema_ready()) { return array(); }
        return $this->db->where(array('kd_barang' => $kodeBarang, 'status_batch' => 'AKTIF'))
            ->where('qty_sisa >', 0)->order_by('tgl_efektif', 'DESC')->order_by('id_batch', 'DESC')
            ->get($this->batchTable)->result();
    }

    public function register_incoming_transaction($transactionId, $actor)
    {
        if (!$this->schema_ready()) { return array('success' => false, 'message' => 'Schema LIFO belum tersedia.'); }
        $transaction = $this->db->where('id_transnk', $transactionId)->get('tbpo_transaksi')->row();
        if (!$transaction || !in_array($transaction->kd_akun, array('11511', '11513'), true)) {
            return array('success' => false, 'message' => 'Transaksi masuk tidak ditemukan.');
        }
        if ($this->db->where('id_transnk_masuk', $transactionId)->count_all_results($this->batchTable) > 0) {
            return array('success' => true, 'message' => 'Batch sudah tersedia.');
        }
        $transaction->tgl_efektif = $this->normalize_transaction_date($transaction);
        $this->create_batch_from_transaction($transaction, $actor, true);
        return array('success' => true);
    }

    public function allocate_new_outgoing($transactionId)
    {
        $transaction = $this->db->where('id_transnk', $transactionId)->get('tbpo_transaksi')->row();
        if (!$transaction || !in_array($transaction->kd_akun, array('11512', '11514'), true)) {
            return array('success' => false, 'message' => 'Transaksi keluar tidak ditemukan.');
        }
        $check = $this->can_allocate_outgoing($transaction->kd_barang, $transaction->tr_qty);
        if (!$check['allowed']) { return array('success' => false, 'message' => $check['message']); }
        $remaining = (float) $transaction->tr_qty;
        $batches = $this->db->where(array('kd_barang' => $transaction->kd_barang, 'status_batch' => 'AKTIF', 'status_harga' => 'VALID'))
            ->where('qty_sisa >', 0)->order_by('tgl_efektif', 'DESC')->order_by('id_batch', 'DESC')->get($this->batchTable)->result();
        foreach ($batches as $batch) {
            if ($remaining <= 0) { break; }
            $used = min($remaining, (float) $batch->qty_sisa);
            $this->write_allocation($transaction->id_transnk, $batch, $used);
            $remaining -= $used;
        }
        return array('success' => $remaining <= 0.0001, 'message' => $remaining <= 0.0001 ? '' : 'Alokasi batch LIFO tidak lengkap.');
    }

    public function set_manual_price($batchId, $price, $reason, $actor)
    {
        $batch = $this->db->where('id_batch', $batchId)->get($this->batchTable)->row();
        $price = (float) $price;
        if (!$batch || $price <= 0 || trim($reason) === '') {
            return array('success' => false, 'message' => 'Batch, harga positif, dan alasan wajib diisi.');
        }
        $this->db->trans_begin();
        $this->db->insert('tbpo_stock_lifo_harga_log', array(
            'id_batch' => $batchId, 'harga_lama' => $batch->harga_satuan, 'harga_baru' => $price,
            'dasar_harga_lama' => $batch->dasar_harga, 'dasar_harga_baru' => 'MANUAL',
            'alasan' => trim($reason), 'kd_user' => $actor,
        ));
        $this->db->where('id_batch', $batchId)->update($this->batchTable, array(
            'harga_satuan' => $price, 'dasar_harga' => 'MANUAL', 'status_harga' => 'VALID',
            'catatan_harga' => trim($reason), 'dibuat_oleh' => $actor,
        ));
        $this->db->where(array('id_transnk' => $batch->id_transnk_masuk, 'jenis_issue' => 'PERLU_HARGA', 'resolved_at' => null))
            ->update('tbpo_stock_lifo_rebuild_issue', array('resolved_at' => date('Y-m-d H:i:s'), 'resolved_by' => $actor));
        if ($this->db->trans_status() === false) { $this->db->trans_rollback(); return array('success' => false, 'message' => 'Harga batch gagal disimpan.'); }
        $this->db->trans_commit();
        return array('success' => true, 'message' => 'Harga batch LIFO berhasil disimpan.');
    }

    private function transactions_for_rebuild()
    {
        $effective = "CASE
            WHEN t.tgl_transaksi REGEXP '^[0-9]{4}-[0-9]{2}-[0-9]{2} [0-9]{2}:[0-9]{2}:[0-9]{2}$' THEN STR_TO_DATE(t.tgl_transaksi, '%Y-%m-%d %H:%i:%s')
            WHEN t.tgl_transaksi REGEXP '^[0-9]{4}-[0-9]{2}-[0-9]{2}$' THEN STR_TO_DATE(t.tgl_transaksi, '%Y-%m-%d')
            WHEN t.tgl_transaksi REGEXP '^[0-9]{2}/[0-9]{2}/[0-9]{4}$' THEN STR_TO_DATE(t.tgl_transaksi, '%d/%m/%Y')
            ELSE CONCAT(t.create_at, ' 00:00:00') END";
        return $this->db->query("SELECT t.*, {$effective} AS tgl_efektif FROM tbpo_transaksi t
            WHERE t.kd_akun IN ('11511','11512','11513','11514')
            ORDER BY tgl_efektif ASC, t.id_transnk ASC")->result();
    }

    private function normalize_transaction_date($transaction)
    {
        $date = trim((string) $transaction->tgl_transaksi);
        foreach (array('Y-m-d H:i:s', 'Y-m-d', 'd/m/Y') as $format) {
            $parsed = DateTime::createFromFormat($format, $date);
            if ($parsed && $parsed->format($format) === $date) { return $parsed->format('Y-m-d H:i:s'); }
        }
        return trim((string) $transaction->create_at) . ' 00:00:00';
    }

    private function create_batch_from_transaction($transaction, $actor, $recordIssue)
    {
        $price = $transaction->kd_akun === '11511' ? $this->po_price_for_transaction($transaction) : null;
        $source = $transaction->kd_akun === '11511' ? 'PO' : 'ADJUSTMENT';
        $data = array(
            'id_transnk_masuk' => $transaction->id_transnk,
            'id_detail_po_nk' => $price ? $price['id_detail_po_nk'] : null,
            'kd_barang' => $transaction->kd_barang,
            'kd_barangsys' => $transaction->kd_barangsys,
            'jenis_sumber' => $source,
            'referensi_sumber' => $transaction->kd_po_nk,
            'tgl_efektif' => $transaction->tgl_efektif,
            'qty_awal' => $transaction->tr_qty,
            'qty_sisa' => $transaction->tr_qty,
            'harga_satuan' => $price ? $price['harga'] : null,
            'dasar_harga' => $price ? $price['dasar'] : 'BELUM_ADA',
            'status_harga' => $price ? 'VALID' : 'PERLU_HARGA',
            'status_batch' => ((float) $transaction->tr_qty > 0) ? 'AKTIF' : 'HABIS',
            'catatan_harga' => $price ? '' : 'Harga manual wajib diisi.',
            'dibuat_oleh' => $actor,
        );
        $this->db->insert($this->batchTable, $data);
        $batchId = (int) $this->db->insert_id();
        if (!$price && $recordIssue) {
            $this->add_issue($transaction->id_transnk, $transaction->kd_barang, 'PERLU_HARGA', $transaction->tr_qty,
                'Batch masuk tidak memiliki harga PO final; harga manual wajib diisi.');
        }
        return $batchId;
    }

    private function po_price_for_transaction($transaction)
    {
        $hasRealisasi = $this->db->table_exists('tbpo_realisasi_detail_po_nk');
        $realisasiSelect = $hasRealisasi
            ? 'r.harga_nyata AS harga_realisasi,r.status_approval_harga'
            : '0 AS harga_realisasi,\'\' AS status_approval_harga';
        $realisasiJoin = $hasRealisasi
            ? 'LEFT JOIN tbpo_realisasi_detail_po_nk r ON r.id_det_po_nk=d.id_det_po_nk'
            : '';
        $detail = $this->db->query("SELECT d.id_det_po_nk,d.hrg_satuan,d.hrg_nyata,p.status,{$realisasiSelect}
            FROM tbpo_detail_po_nk d JOIN tbpo_po_nk p ON p.kd_po_nk=d.kd_po_nk
            {$realisasiJoin}
            WHERE d.kd_po_nk=? AND (d.kd_bsys=? OR d.kd_barang=?)
            ORDER BY d.id_det_po_nk DESC LIMIT 1", array($transaction->kd_po_nk, $transaction->kd_barangsys, $transaction->kd_barang))->row();
        if (!$detail) { return null; }
        $approved = in_array((string) $detail->status_approval_harga, array('DISETUJUI_OTOMATIS', 'DISETUJUI_DIREKTUR'), true);
        if ($approved && (float) $detail->harga_realisasi > 0) {
            return array('id_detail_po_nk' => $detail->id_det_po_nk, 'harga' => $detail->harga_realisasi, 'dasar' => 'REALISASI');
        }
        // Sistem lama tidak memiliki detail approval per item. Harga nyata PO DONE diperlakukan final.
        if ((string) $detail->status === 'DONE' && (float) $detail->hrg_nyata > 0) {
            return array('id_detail_po_nk' => $detail->id_det_po_nk, 'harga' => $detail->hrg_nyata, 'dasar' => 'REALISASI');
        }
        if ((float) $detail->hrg_satuan > 0) {
            return array('id_detail_po_nk' => $detail->id_det_po_nk, 'harga' => $detail->hrg_satuan, 'dasar' => 'PENGAJUAN');
        }
        return null;
    }

    private function allocate_historical_outgoing($transaction)
    {
        $remaining = (float) $transaction->tr_qty;
        $count = 0;
        $batches = $this->db->where(array('kd_barang' => $transaction->kd_barang, 'status_batch' => 'AKTIF'))
            ->where('qty_sisa >', 0)->order_by('tgl_efektif', 'DESC')->order_by('id_batch', 'DESC')->get($this->batchTable)->result();
        foreach ($batches as $batch) {
            if ($remaining <= 0) { break; }
            $used = min($remaining, (float) $batch->qty_sisa);
            $this->write_allocation($transaction->id_transnk, $batch, $used);
            $remaining -= $used;
            $count++;
        }
        if ($remaining > 0.0001) {
            $this->add_issue($transaction->id_transnk, $transaction->kd_barang, 'STOK_MINUS_HISTORIS', $remaining,
                'Transaksi keluar historis melebihi batch masuk yang tersedia.');
        }
        return $count;
    }

    private function write_allocation($outId, $batch, $qty)
    {
        $harga = $batch->status_harga === 'VALID' ? $batch->harga_satuan : null;
        $this->db->insert('tbpo_stock_lifo_allocation', array(
            'id_transnk_keluar' => $outId, 'id_batch' => $batch->id_batch, 'qty_alokasi' => $qty,
            'harga_satuan_snapshot' => $harga, 'nilai_alokasi' => $harga === null ? null : $qty * $harga,
        ));
        $sisa = (float) $batch->qty_sisa - $qty;
        $this->db->where('id_batch', $batch->id_batch)->update($this->batchTable, array(
            'qty_sisa' => $sisa, 'status_batch' => $sisa <= 0.0001 ? 'HABIS' : 'AKTIF'
        ));
    }

    private function add_issue($transactionId, $kodeBarang, $type, $qty, $message)
    {
        $this->db->insert('tbpo_stock_lifo_rebuild_issue', array(
            'id_transnk' => $transactionId, 'kd_barang' => $kodeBarang, 'jenis_issue' => $type,
            'qty_terdampak' => $qty, 'pesan' => $message,
        ));
    }
}
