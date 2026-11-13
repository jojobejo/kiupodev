<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Buku bantu batch persediaan non-komersil. Semua nilai di sini adalah
 * snapshot audit; harga PO tidak dibaca ulang saat menghitung nilai stok.
 */
class M_StockLifo extends CI_Model
{
    private $batchTable = 'tbpo_stock_lifo_batch_nk';

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
        return $this->batch_list_query($kodeBarang)
            ->where(array('lb.status_batch' => 'AKTIF'))
            ->where('lb.qty_sisa >', 0)->order_by('lb.tgl_efektif', 'DESC')->order_by('lb.id_batch', 'DESC')
            ->get()->result();
    }

    /**
     * Seluruh batch untuk panel histori: batch aktif diprioritaskan, lalu
     * batch habis dari yang terbaru hingga batch habis paling lama.
     */
    public function count_batch_history_lifo($kodeBarang)
    {
        if (!$this->schema_ready()) { return 0; }
        return (int) $this->db->where('kd_barang', $kodeBarang)->count_all_results($this->batchTable);
    }

    public function get_batch_history_lifo($kodeBarang, $limit = null, $offset = 0)
    {
        if (!$this->schema_ready()) { return array(); }
        $query = $this->batch_list_query($kodeBarang)
            ->order_by("CASE WHEN lb.status_batch = 'AKTIF' THEN 0 ELSE 1 END", 'ASC', false)
            ->order_by('lb.tgl_efektif', 'DESC')->order_by('lb.id_batch', 'DESC');
        if ($limit !== null) {
            $query->limit(max(1, (int) $limit), max(0, (int) $offset));
        }
        return $query->get()->result();
    }

    /**
     * Harga LIFO terakhir adalah harga batch masuk terbaru, termasuk batch
     * yang sudah habis.  Ini membuat halaman detail tetap dapat menunjukkan
     * harga terakhir ketika stok item sedang nol.
     */
    public function get_latest_price($kodeBarang)
    {
        if (!$this->schema_ready()) { return null; }
        return $this->db->where('kd_barang', $kodeBarang)
            ->where('status_harga', 'VALID')
            ->where('harga_satuan >', 0)
            ->order_by('tgl_efektif', 'DESC')->order_by('id_batch', 'DESC')
            ->get($this->batchTable, 1)->row();
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

    /**
     * Keputusan harga pada modal Purchasing berlaku untuk seluruh batch PO
     * terkait, termasuk alokasi pengambilan yang sudah tercatat.
     */
    public function sync_purchasing_price($detailId, $price, $priceChoice, $actor)
    {
        if (!$this->schema_ready()) {
            return array('success' => false, 'message' => 'Schema LIFO belum tersedia.');
        }

        $detail = $this->db->where('id_det_po_nk', (int) $detailId)->get('tbpo_detail_po_nk')->row();
        $priceChoice = strtoupper(trim((string) $priceChoice));
        $price = $priceChoice === 'PENGAJUAN'
            ? (float) ($detail ? $detail->hrg_satuan : 0)
            : (float) $price;
        $base = $priceChoice === 'PENGAJUAN' ? 'PENGAJUAN' : 'REALISASI';
        $note = $base === 'REALISASI' ? 'Harga Purchasing (realisasi)' : 'Harga Purchasing (pengajuan)';

        if (!$detail || $price <= 0 || !in_array($priceChoice, array('REALISASI', 'PENGAJUAN'), true)) {
            return array('success' => false, 'message' => 'Pilihan harga Purchasing tidak valid.');
        }

        $batches = $this->db->where('id_detail_po_nk', (int) $detailId)
            ->where('jenis_sumber', 'PO')->get($this->batchTable)->result();
        if (empty($batches)) {
            return array('success' => true, 'updated' => 0);
        }

        $this->db->trans_begin();
        foreach ($batches as $batch) {
            if ($this->db->table_exists('tbpo_stock_lifo_harga_log')) {
                $this->db->insert('tbpo_stock_lifo_harga_log', array(
                    'id_batch' => $batch->id_batch,
                    'harga_lama' => $batch->harga_satuan,
                    'harga_baru' => $price,
                    'dasar_harga_lama' => $batch->dasar_harga,
                    'dasar_harga_baru' => $base,
                    'alasan' => $note,
                    'kd_user' => $actor,
                ));
            }
            $this->db->where('id_batch', $batch->id_batch)->update($this->batchTable, array(
                'harga_satuan' => $price,
                'dasar_harga' => $base,
                'status_harga' => 'VALID',
                'catatan_harga' => $note,
                'dibuat_oleh' => $actor,
            ));
            $this->db->where('id_batch', $batch->id_batch)->update('tbpo_stock_lifo_allocation', array(
                'harga_satuan_snapshot' => $price,
                'nilai_alokasi' => 'qty_alokasi * ' . $this->db->escape($price),
            ), false);
            $this->db->where(array('id_transnk' => $batch->id_transnk_masuk, 'jenis_issue' => 'PERLU_HARGA', 'resolved_at' => null))
                ->update('tbpo_stock_lifo_rebuild_issue', array('resolved_at' => date('Y-m-d H:i:s'), 'resolved_by' => $actor));
        }

        if ($this->db->trans_status() === false) {
            $this->db->trans_rollback();
            return array('success' => false, 'message' => 'Sinkronisasi harga batch LIFO gagal.');
        }
        $this->db->trans_commit();
        return array('success' => true, 'updated' => count($batches));
    }

    private function batch_list_query($kodeBarang)
    {
        $this->db->select('lb.*');
        if ($this->db->table_exists('tbpo_realisasi_detail_po_nk')) {
            $this->db->select('COALESCE(NULLIF(r.harga_nyata, 0), NULLIF(d.hrg_nyata, 0)) AS harga_purchasing', false);
            $this->db->join('tbpo_detail_po_nk d', 'd.id_det_po_nk=lb.id_detail_po_nk', 'left');
            $this->db->join('tbpo_realisasi_detail_po_nk r', 'r.id_det_po_nk=lb.id_detail_po_nk', 'left');
        } else {
            $this->db->select('NULL AS harga_purchasing', false);
        }
        $this->db->select("CASE lb.dasar_harga WHEN 'REALISASI' THEN 'Realisasi' WHEN 'PENGAJUAN' THEN 'Pengajuan' WHEN 'MANUAL' THEN 'Manual' ELSE 'Belum Harga' END AS label_dasar_harga", false);
        return $this->db->from($this->batchTable . ' lb')->where('lb.kd_barang', $kodeBarang);
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
        $hasPilihan = $hasRealisasi && $this->db->field_exists('harga_dipakai_lifo', 'tbpo_realisasi_detail_po_nk');
        $realisasiSelect = $hasRealisasi
            ? 'r.harga_nyata AS harga_realisasi' . ($hasPilihan ? ', r.harga_dipakai_lifo' : ', "REALISASI" AS harga_dipakai_lifo')
            : '0 AS harga_realisasi, "REALISASI" AS harga_dipakai_lifo';
        $realisasiJoin = $hasRealisasi
            ? 'LEFT JOIN tbpo_realisasi_detail_po_nk r ON r.id_det_po_nk=d.id_det_po_nk'
            : '';
        $detail = $this->db->query("SELECT d.id_det_po_nk,d.hrg_satuan,d.hrg_nyata,{$realisasiSelect}
            FROM tbpo_detail_po_nk d JOIN tbpo_po_nk p ON p.kd_po_nk=d.kd_po_nk
            {$realisasiJoin}
            WHERE d.kd_po_nk=? AND (d.kd_bsys=? OR d.kd_barang=?)
            ORDER BY d.id_det_po_nk DESC LIMIT 1", array($transaction->kd_po_nk, $transaction->kd_barangsys, $transaction->kd_barang))->row();
        if (!$detail) { return null; }

        // Harga nyata yang sudah direkam Purchasing adalah harga pembelian
        // terakhir untuk LIFO, tanpa menunggu perubahan status PO/approval.
        // Bila belum ada, gunakan harga satuan pada detail PO: harga yang
        // sebelumnya diinput Purchasing dari pengajuan PIC.
        if ($detail->harga_dipakai_lifo === 'PENGAJUAN' && (float) $detail->hrg_satuan > 0) {
            return array('id_detail_po_nk' => $detail->id_det_po_nk, 'harga' => $detail->hrg_satuan, 'dasar' => 'PENGAJUAN');
        }
        if ((float) $detail->harga_realisasi > 0) {
            return array('id_detail_po_nk' => $detail->id_det_po_nk, 'harga' => $detail->harga_realisasi, 'dasar' => 'REALISASI');
        }
        if ((float) $detail->hrg_nyata > 0) {
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
