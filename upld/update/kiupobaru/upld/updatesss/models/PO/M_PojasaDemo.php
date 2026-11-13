<?php
defined('BASEPATH') or exit('No direct script access allowed');

/** Creates a clearly labelled, self-contained PO Jasa demo fixture. */
class M_PojasaDemo extends CI_Model
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('PO/M_PojasaCore');
    }

    public function schema_ready()
    {
        foreach (array('tbpo_jasa_request', 'tbpo_jasa_scope', 'tbpo_jasa_material', 'tbpo_jasa_spk',
            'tbpo_jasa_stock_allocation', 'tbpo_jasa_draft_pembelian', 'tbpo_jasa_purchase_submission',
            'tbpo_jasa_purchase_submission_detail', 'tbpo_po_nk', 'tbpo_detail_po_nk', 'tbpo_barang_nk',
            'v_stockbarangnk') as $name) {
            if (!$this->db->table_exists($name)) return false;
        }
        return $this->db->field_exists('source_module', 'tbpo_po_nk')
            && $this->db->field_exists('status_master', 'tbpo_barang_nk');
    }

    public function create($context)
    {
        if ($context['role'] !== 'PURCHASING') return array('success' => false, 'code' => 'FORBIDDEN');
        if (!$this->schema_ready()) return array('success' => false, 'code' => 'SCHEMA_NOT_READY');

        $this->db->trans_begin();
        // Only read and reserve an existing non-commercial stock row; no physical stock is changed.
        $stock = $this->db->query(
            'SELECT s.*,b.kd_br_adm,b.kat_barang,b.descnk,b.satuan,b.kd_lokasi FROM v_stockbarangnk s '
            . 'JOIN tbpo_barang_nk b ON b.id_brg_nk=s.id_brg_nk '
            . 'WHERE s.qty_ready BETWEEN 1 AND 3 ORDER BY s.qty_ready ASC, s.id_brg_nk ASC LIMIT 1'
        )->row();
        if (!$stock) return $this->fail('STOCK_NOT_FOUND');

        $now = date('Y-m-d H:i:s');
        $today = date('Y-m-d');
        $tag = strtoupper(substr(bin2hex(random_bytes(4)), 0, 8));
        $requestCode = $this->M_PojasaCore->generate_request_number($today);
        $spkCode = $this->M_PojasaCore->generate_spk_number($today);
        $poCode = $this->M_PojasaCore->generateAtomicNumber('PURCHASE_NK', date('Ymd'), 'NKPO' . date('dmy'), 4, array(array('table' => 'tbpo_po_nk', 'column' => 'kd_po_nk')));
        if (!$requestCode || !$spkCode || !$poCode) return $this->fail('NUMBER_ERROR');

        $allocatedQty = max(1, (int) floor((float) $stock->qty_ready));
        $existingNeed = $allocatedQty + 1; // Explicitly greater than this item's available physical stock.
        $newNeed = 2;
        $newCode = 'DJS' . $tag;
        $newAdminCode = 'DEMO' . $tag;
        $newName = 'Barang Baru Demo PO Jasa ' . $tag;
        $this->db->insert('tbpo_barang_nk', array(
            'kd_barang' => $newCode, 'kd_br_adm' => $newAdminCode, 'kat_barang' => $stock->kat_barang,
            'nama_barang' => $newName, 'descnk' => 'Master barang baru hasil automasi demo PO Jasa.',
            'status_master' => 'ACTIVE', 'satuan' => (int) $stock->satuan, 'gbr_barang' => 'Karisma.png',
            'qrcode_path' => '', 'qrcode_data' => '', 'inputer' => $context['kode_user'],
            'create_at' => $now, 'last_updated' => $context['kode_user'], 'kd_lokasi' => (int) $stock->kd_lokasi,
        ));
        $newItemId = (int) $this->db->insert_id();

        $materialTotal = (1 * 50000) + ($newNeed * 75000);
        $this->db->insert('tbpo_jasa_request', array(
            'kd_po_jasa' => $requestCode, 'no_spk' => $spkCode, 'kd_user' => $context['kode_user'],
            'nm_user' => $context['nama_user'], 'departemen' => 'PURCHASING', 'tgl_request' => $today,
            'tgl_target' => date('Y-m-d', strtotime('+14 days')), 'tgl_mulai_pekerjaan' => $today,
            'tgl_selesai_pekerjaan' => date('Y-m-d', strtotime('+14 days')), 'lokasi_pekerjaan' => 'Lokasi demo',
            'tujuan_pekerjaan' => 'DEMO — automasi pemenuhan material PO Jasa.',
            'catatan_pic' => 'Data DEMO. Jangan digunakan sebagai transaksi operasional.',
            'estimasi_total' => $materialTotal, 'estimasi_purchasing' => $materialTotal,
            'estimasi_total_bahan' => $materialTotal, 'estimasi_disetujui' => $materialTotal,
            'status' => 'SPK_TERBIT', 'status_version' => 1, 'revision_no' => 1, 'requires_dirut_ops' => 0,
            'reviewed_by_purchasing' => $context['kode_user'], 'reviewed_at_purchasing' => $now,
            'purchasing_note' => 'Fixture demo dibuat otomatis oleh Purchasing.', 'generated_by' => $context['kode_user'],
            'generated_at' => $now, 'acc_with_direktur' => $context['kode_user'], 'acc_at_direktur' => $now,
        ));
        $this->db->insert('tbpo_jasa_scope', array(
            'kd_po_jasa' => $requestCode, 'revision_no' => 1, 'line_no' => 1, 'jenis_scope' => 'JASA',
            'nama_scope' => 'Pekerjaan demo automasi', 'deskripsi' => 'Scope khusus fixture demo.', 'qty' => 1,
            'satuan' => 'LOT', 'harga_estimasi' => 0, 'total_estimasi' => 0, 'is_active' => 1,
        ));
        $scopeId = (int) $this->db->insert_id();
        $this->db->insert('tbpo_jasa_material', array(
            'kd_po_jasa' => $requestCode, 'revision_no' => 1, 'line_no' => 1, 'id_scope' => $scopeId,
            'id_brg_nk' => (int) $stock->id_brg_nk, 'reference_type' => 'CATALOG', 'kd_barang_snapshot' => $stock->kode_barang,
            'nama_material' => $stock->nama_barang, 'deskripsi' => $stock->deskripsi, 'qty_kebutuhan' => $existingNeed,
            'satuan' => $stock->satuan, 'sumber_material' => 'CAMPURAN', 'harga_estimasi' => 50000,
            'total_estimasi' => $existingNeed * 50000, 'is_active' => 1, 'created_by' => $context['id_user'],
        ));
        $existingMaterialId = (int) $this->db->insert_id();
        $this->db->insert('tbpo_jasa_material', array(
            'kd_po_jasa' => $requestCode, 'revision_no' => 1, 'line_no' => 2, 'id_scope' => $scopeId,
            'id_brg_nk' => $newItemId, 'reference_type' => 'CATALOG', 'kd_barang_snapshot' => $newCode,
            'nama_material' => $newName, 'deskripsi' => 'Material master baru, stok awal 0.', 'qty_kebutuhan' => $newNeed,
            'satuan' => $stock->satuan, 'sumber_material' => 'PEMBELIAN', 'harga_estimasi' => 75000,
            'total_estimasi' => $newNeed * 75000, 'is_active' => 1, 'created_by' => $context['id_user'],
        ));
        $newMaterialId = (int) $this->db->insert_id();
        $this->db->insert('tbpo_jasa_stock_allocation', array(
            'kd_po_jasa' => $requestCode, 'id_material' => $existingMaterialId, 'id_brg_nk' => (int) $stock->id_brg_nk,
            'qty_allocation' => $allocatedQty, 'status_allocation' => 'ACTIVE', 'allocation_token' => $this->uuid(),
            'allocated_by' => $context['id_user'], 'allocated_at' => $now,
        ));

        $drafts = array(array($existingMaterialId, 1, 50000, $stock->kd_br_adm, $stock->kode_barang, $stock->nama_barang, $stock->deskripsi, $stock->kat_barang),
                        array($newMaterialId, $newNeed, 75000, $newAdminCode, $newCode, $newName, 'Master barang baru DEMO.', $stock->kat_barang));
        $this->db->insert('tbpo_jasa_spk', array(
            'kd_po_jasa' => $requestCode, 'no_spk' => $spkCode, 'revision_no' => 1, 'spk_version_no' => 1,
            'issued_by' => $context['id_user'], 'issued_at' => $now,
            'snapshot_json' => json_encode(array('demo' => true, 'label' => 'DEMO — bukan SPK operasional', 'request_code' => $requestCode)),
            'template_version' => 'DEMO-AUTOMATION-V1', 'document_status' => 'ISSUED',
        ));
        $spkId = (int) $this->db->insert_id();
        $totalPurchase = 0; $draftRows = array();
        foreach ($drafts as $line) {
            $this->db->insert('tbpo_jasa_draft_pembelian', array('kd_po_jasa' => $requestCode, 'revision_no' => 1,
                'id_material' => $line[0], 'qty' => $line[1], 'harga_estimasi' => $line[2],
                'keterangan' => 'DEMO — kekurangan material.', 'status_draft' => 'DIAJUKAN_KE_PO_PEMBELIAN',
                'created_by' => $context['id_user'], 'idempotency_token' => $this->uuid(), 'submitted_at' => $now));
            $draftRows[] = array('id' => (int) $this->db->insert_id(), 'line' => $line);
            $totalPurchase += $line[1] * $line[2];
        }
        $this->db->insert('tbpo_po_nk', array('jns_po' => 2, 'kd_po_nk' => $poCode, 'kd_po_req' => $requestCode,
            'nopo' => 'DEMO-' . $spkCode, 'kd_user' => $context['kode_user'], 'nm_user' => $context['nama_user'],
            'tgl_transaksi' => $today, 'jml_item' => count($draftRows), 'total_harga' => $totalPurchase,
            'status' => 'PROSES PEMBELIAN', 'departemen' => 'PURCHASING',
            'tj_pembelian' => 'DEMO — otomatis dari ' . $requestCode, 'tax' => 0, 'hrg_pajak' => 0,
            'hrg_nyata' => 0, 'status_hrg_nyata' => 0, 'acc_with' => $context['kode_user'], 'acc_with_kadep' => '',
            'source_module' => 'PO_JASA_DEMO', 'source_reference' => 'DEMO:' . $requestCode . ':REV:1',
            'source_spk_no' => $spkCode, 'source_spk_version' => 1));
        $poId = (int) $this->db->insert_id();
        $this->db->insert('tbpo_jasa_purchase_submission', array('kd_po_jasa' => $requestCode, 'id_spk' => $spkId,
            'no_spk' => $spkCode, 'spk_version_no' => 1, 'source_reference' => 'DEMO:' . $requestCode . ':REV:1',
            'id_po_nk' => $poId, 'kd_po_nk' => $poCode, 'status' => 'PROSES_PEMBELIAN',
            'idempotency_token' => $this->uuid(), 'created_by' => $context['id_user']));
        $submissionId = (int) $this->db->insert_id();
        foreach ($draftRows as $index => $draft) {
            $line = $draft['line'];
            $this->db->insert('tbpo_detail_po_nk', array('kd_po_nk' => $poCode, 'kd_po_req' => $requestCode,
                'kd_user' => $context['kode_user'], 'tgl_transaksi' => $today, 'kd_bsys' => $line[3], 'kd_barang' => $line[4],
                'nama_barang' => $line[5], 'deskripsi' => $line[6], 'keterangan' => 'DEMO — kebutuhan melebihi stok.',
                'qty' => $line[1], 'satuan' => (int) $stock->satuan, 'kat_barang' => $line[7], 'hrg_satuan' => $line[2],
                'hrg_nyata' => 0, 'total_harga' => $line[1] * $line[2], 'total_nyata' => 0, 'gbr_produk' => 'Karisma.png',
                'source_module' => 'PO_JASA_DEMO', 'source_reference' => 'DEMO:' . $requestCode . ':DRAFT:' . $draft['id'],
                'source_material_id' => $line[0], 'source_draft_id' => $draft['id']));
            $detailId = (int) $this->db->insert_id();
            $this->db->insert('tbpo_jasa_purchase_submission_detail', array('id_submission' => $submissionId,
                'kd_po_jasa' => $requestCode, 'id_material' => $line[0], 'id_draft_pembelian' => $draft['id'],
                'id_detail_po_nk' => $detailId, 'qty_submitted' => $line[1], 'estimated_unit_price' => $line[2], 'fulfillment_status' => 'DIAJUKAN'));
        }
        $this->M_PojasaCore->append_activity_log(array('kd_po_jasa' => $requestCode, 'revision_no' => 1,
            'event_type' => 'DEMO_AUTOMATION_CREATED', 'from_status' => null, 'to_status' => 'SPK_TERBIT',
            'actor_id' => $context['id_user'], 'actor_code' => $context['kode_user'], 'actor_name' => $context['nama_user'],
            'actor_role' => 'PURCHASING', 'actor_departemen' => 'PURCHASING',
            'catatan' => 'Fixture demo dibuat otomatis.', 'metadata_json' => json_encode(array('po' => $poCode, 'new_master' => $newCode))));
        if ($this->db->trans_status() === false) return $this->fail('DATABASE_ERROR');
        $this->db->trans_commit();
        return array('success' => true, 'code' => 'CREATED', 'kd_po_jasa' => $requestCode, 'no_spk' => $spkCode,
            'kd_po_nk' => $poCode, 'kd_barang_baru' => $newCode, 'stok_dipakai' => $stock->kode_barang,
            'qty_stok' => $allocatedQty, 'qty_kebutuhan' => $existingNeed);
    }

    private function fail($code) { $this->db->trans_rollback(); return array('success' => false, 'code' => $code); }
    private function uuid() { return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x', mt_rand(0,65535),mt_rand(0,65535),mt_rand(0,65535),mt_rand(0,4095)|0x4000,mt_rand(0,16383)|0x8000,mt_rand(0,65535),mt_rand(0,65535),mt_rand(0,65535)); }
}
