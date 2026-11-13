<?php
defined('BASEPATH') or exit('No direct script access allowed');

class PojasaPhase5Check extends CI_Controller
{
    private $checks = array();
    private $sequence = 1;

    public function index()
    {
        if (!is_cli()) { show_404(); return; }
        $this->load->helper(array('pojasa_authorization', 'pojasa_status'));
        $this->load->model('PO/M_PojasaCore');
        $this->load->model('PO/M_PojasaPurchasing');
        $this->assert('phase5_schema_ready', $this->M_PojasaPurchasing->schema_ready());
        if ($this->M_PojasaPurchasing->schema_ready()) { $this->runTransactionalChecks(); }
        $failed = array_filter($this->checks, function ($row) { return !$row['passed']; });
        echo json_encode(array('success' => !$failed, 'checks' => $this->checks), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL;
        if ($failed) { exit(1); }
    }

    private function runTransactionalChecks()
    {
        $users = $this->users();
        $stock = $this->db->query("SELECT * FROM v_stockbarangnk WHERE qty_ready >= 2 ORDER BY qty_ready DESC LIMIT 1")->row();
        if (!$users || !$stock) { $this->assert('phase5_fixtures_available', false); return; }
        $before = $this->counts();
        $this->db->trans_begin();

        $masterResults = $this->M_PojasaPurchasing->search_vendors('SAPRO', 20);
        $hasMaster = false;
        foreach ($masterResults as $item) { if ($item['source_type'] === 'MASTER') { $hasMaster = true; break; } }
        $this->assert('master_supplier_ajax_source_available', $hasMaster, $masterResults);

        $requestCode = 'P5TESTPLAN0001';
        $materialId = $this->createRequest($requestCode, 'KEUANGAN', 'MENUNGGU_PURCHASING', $users['pic'], (float) $stock->qty_ready + 3, (int) $stock->id_brg_nk);
        $master = null;
        foreach ($masterResults as $item) { if ($item['source_type'] === 'MASTER') { $master = $item; break; } }
        $comparison1 = $this->M_PojasaPurchasing->save_comparison($users['purchasing'], $requestCode, 'MASTER', $master['source_code'], array(
            'quotation_no' => 'QT-P5-001', 'quotation_date' => '2026-09-10', 'amount' => 1500000,
            'lead_time_days' => 5, 'offer_detail' => 'Penawaran master supplier',
        ), array(
            'jenis_dokumen' => 'PENAWARAN_VENDOR', 'keterangan' => 'Dokumen test rollback',
            'file_path' => 'images/pojasa/PHASE5-TEST/quotation.txt', 'file_original' => 'quotation.txt',
            'mime_type' => 'text/plain', 'file_size_bytes' => 10, 'sha256' => str_repeat('5', 64),
        ));
        $this->assert('master_supplier_synced_as_service_vendor', $comparison1['success'] && (int) $this->db->where('source_supplier_code', $master['source_code'])->count_all_results('tbpo_jasa_vendor') === 1, $comparison1);
        $comparisonDocument = $this->db->get_where('tbpo_jasa_vendor_comparison', array('id_vendor_comparison' => $comparison1['id']))->row();
        $this->assert('comparison_supporting_document_is_linked', $comparisonDocument && (int) $comparisonDocument->id_dokumen > 0, $comparisonDocument);

        $manual = $this->M_PojasaPurchasing->create_manual_vendor($users['purchasing'], array(
            'vendor_type' => 'PERORANGAN', 'nama_vendor' => 'P5 TEST TEKNISI MANDIRI', 'kategori_jasa' => 'TEST',
            'nama_pic' => 'Teknisi', 'no_telpon' => '081200000000', 'email' => 'p5@example.test',
            'alamat_vendor' => 'Alamat uji', 'npwp' => '',
        ));
        $comparison2 = $this->M_PojasaPurchasing->save_comparison($users['purchasing'], $requestCode, 'JASA', $manual['vendor']->kd_vendor_jasa, array(
            'quotation_no' => 'QT-P5-002', 'quotation_date' => '2026-09-10', 'amount' => 1400000,
            'lead_time_days' => 7, 'offer_detail' => 'Penawaran perorangan',
        ));
        $this->assert('manual_vendor_and_multiple_comparisons_saved', $manual['success'] && $comparison2['success'] && (int) $this->db->where('kd_po_jasa', $requestCode)->count_all_results('tbpo_jasa_vendor_comparison') === 2);
        $selected = $this->M_PojasaPurchasing->select_comparison($users['purchasing'], $requestCode, $comparison2['id'], 'Harga terbaik dan detail teknis sesuai.');
        $request = $this->M_PojasaCore->get_request($requestCode);
        $this->assert('selected_vendor_reason_and_request_reference_saved', $selected['success'] && $request->kd_vendor_jasa === $manual['vendor']->kd_vendor_jasa && $request->vendor_selection_reason !== '', $selected);
        $lockedDelete = $this->M_PojasaPurchasing->delete_comparison($users['purchasing'], $requestCode, $comparison2['id']);
        $this->assert('selected_comparison_cannot_be_deleted', !$lockedDelete['success'] && $lockedDelete['code'] === 'SELECTED_VENDOR_LOCKED', $lockedDelete);
        $comparisonCountBeforeArchive = (int) $this->db->where('kd_po_jasa', $requestCode)->count_all_results('tbpo_jasa_vendor_comparison');
        $archived = $this->M_PojasaPurchasing->delete_comparison($users['purchasing'], $requestCode, $comparison1['id']);
        $archivedRow = $this->db->get_where('tbpo_jasa_vendor_comparison', array('id_vendor_comparison' => $comparison1['id']))->row();
        $archivedDocument = $this->db->get_where('tbpo_jasa_dokumen', array('id_dokumen' => $archivedRow->id_dokumen))->row();
        $this->assert('comparison_delete_is_soft_archive', $archived['success'] && (int) $archivedRow->is_active === 0 && (int) $archivedDocument->is_active === 0 && (int) $this->db->where('kd_po_jasa', $requestCode)->count_all_results('tbpo_jasa_vendor_comparison') === $comparisonCountBeforeArchive, array($archived, $archivedRow));

        $transactionBefore = (int) $this->db->count_all('tbpo_transaksi');
        $allocationToken = $this->token();
        $allocated = $this->M_PojasaPurchasing->allocate_stock($users['purchasing'], $requestCode, $materialId, (int) $stock->id_brg_nk, (float) $stock->qty_ready, $allocationToken);
        $transactionAfter = (int) $this->db->count_all('tbpo_transaksi');
        $this->assert('reservation_does_not_reduce_physical_stock', $allocated['success'] && $transactionBefore === $transactionAfter, $allocated);
        $allocationReplay = $this->M_PojasaPurchasing->allocate_stock($users['purchasing'], $requestCode, $materialId, (int) $stock->id_brg_nk, (float) $stock->qty_ready, $allocationToken);
        $allocationConflictToken = $this->M_PojasaPurchasing->allocate_stock($users['purchasing'], $requestCode, $materialId, (int) $stock->id_brg_nk, 1, $allocationToken);
        $this->assert('reservation_idempotency_is_scoped_to_payload_and_actor', $allocationReplay['success'] && $allocationReplay['code'] === 'IDEMPOTENT_REPLAY' && !$allocationConflictToken['success'] && $allocationConflictToken['code'] === 'IDEMPOTENCY_CONFLICT', array($allocationReplay, $allocationConflictToken));
        $conflict = $this->M_PojasaPurchasing->allocate_stock($users['purchasing'], $requestCode, $materialId, (int) $stock->id_brg_nk, 1, $this->token());
        $this->assert('stock_conflict_blocks_over_allocation', !$conflict['success'] && $conflict['code'] === 'STOCK_CONFLICT', $conflict);
        $searchAfter = $this->M_PojasaPurchasing->search_stock($stock->kode_barangs, 20);
        $foundAfter = $this->findStock($searchAfter, (int) $stock->id_brg_nk);
        $this->assert('stock_search_subtracts_active_reservations', $foundAfter && (float) $foundAfter['available_stock'] < 0.000001, $foundAfter);

        $draft = $this->M_PojasaPurchasing->save_purchase_draft($users['purchasing'], $requestCode, $materialId, array(
            'qty' => 3, 'harga_estimasi' => 50000, 'id_vendor_jasa' => (int) $manual['vendor']->id_vendor_jasa, 'keterangan' => 'Kekurangan stok uji',
        ), $this->token());
        $draftRow = $this->db->get_where('tbpo_jasa_draft_pembelian', array('id_draft_pembelian' => $draft['id']))->row();
        $this->assert('purchase_draft_references_request_and_source_material', $draft['success'] && $draftRow->kd_po_jasa === $requestCode && (int) $draftRow->id_material === $materialId && (float) $draftRow->qty === 3.0, $draft);

        $released = $this->M_PojasaPurchasing->release_stock($users['purchasing'], $requestCode, $allocated['id'], 'Perubahan rencana uji.', $this->token());
        $searchReleased = $this->M_PojasaPurchasing->search_stock($stock->kode_barangs, 20);
        $foundReleased = $this->findStock($searchReleased, (int) $stock->id_brg_nk);
        $this->assert('release_restores_available_stock_without_stock_transaction', $released['success'] && abs((float) $foundReleased['available_stock'] - (float) $stock->qty_ready) < 0.000001 && (int) $this->db->count_all('tbpo_transaksi') === $transactionBefore, array($released, $foundReleased));

        $rejectCode = 'P5TESTRELEASE01';
        $rejectMaterial = $this->createRequest($rejectCode, 'GA', 'MENUNGGU_PURCHASING', $users['pic'], 2, (int) $stock->id_brg_nk);
        $rejectAllocation = $this->M_PojasaPurchasing->allocate_stock($users['purchasing'], $rejectCode, $rejectMaterial, (int) $stock->id_brg_nk, 1, $this->token());
        $purchaseOnlyMaterial = $this->insertMaterial($rejectCode, 2, 1, null, 'Material tanpa stok');
        $rejectDraft = $this->M_PojasaPurchasing->save_purchase_draft($users['purchasing'], $rejectCode, $purchaseOnlyMaterial, array('qty' => 1, 'harga_estimasi' => 10000, 'id_vendor_jasa' => null, 'keterangan' => 'Draft reject'), $this->token());
        $this->db->where('kd_po_jasa', $rejectCode)->update('tbpo_jasa_request', array('status' => 'MENUNGGU_DIREKTUR', 'status_version' => 2));
        $rejected = $this->M_PojasaCore->process_approval($users['director'], $rejectCode, 'REJECT', 'Ditolak pada pengujian.', $this->token(), 'MENUNGGU_DIREKTUR', 2);
        $allocationRow = $this->db->get_where('tbpo_jasa_stock_allocation', array('id_allocation' => $rejectAllocation['id']))->row();
        $rejectedDraft = $this->db->get_where('tbpo_jasa_draft_pembelian', array('id_draft_pembelian' => $rejectDraft['id']))->row();
        $this->assert('reject_auto_releases_reservation_and_cancels_draft', $rejected['success'] && $allocationRow->status_allocation === 'RELEASED' && $rejectedDraft->status_draft === 'CANCELLED', array($rejected, $allocationRow, $rejectedDraft));

        $spkCode = 'P5TESTSPK00001';
        $this->createRequest($spkCode, 'KEUANGAN', 'MENUNGGU_PURCHASING', $users['pic'], 1, null);
        $spkComparison = $this->M_PojasaPurchasing->save_comparison($users['purchasing'], $spkCode, 'JASA', $manual['vendor']->kd_vendor_jasa, array('quotation_no' => 'SPK-QT', 'quotation_date' => '2026-09-10', 'amount' => 2000000, 'lead_time_days' => 3, 'offer_detail' => 'Snapshot SPK'));
        $this->M_PojasaPurchasing->select_comparison($users['purchasing'], $spkCode, $spkComparison['id'], 'Vendor terpilih untuk SPK.');
        $spkRequest = $this->M_PojasaCore->get_request($spkCode);
        $this->db->where('kd_po_jasa', $spkCode)->update('tbpo_jasa_request', array('status' => 'MENUNGGU_DIREKTUR', 'status_version' => (int) $spkRequest->status_version + 1));
        $spkRequest = $this->M_PojasaCore->get_request($spkCode);
        $issued = $this->M_PojasaCore->process_approval($users['director'], $spkCode, 'ACC', '', $this->token(), 'MENUNGGU_DIREKTUR', (int) $spkRequest->status_version);
        $spkRow = $this->db->get_where('tbpo_jasa_spk', array('kd_po_jasa' => $spkCode))->row();
        $snapshot = json_decode($spkRow->snapshot_json, true);
        $issuedRequest = $this->M_PojasaCore->get_request($spkCode);
        $this->assert('director_acc_issues_unique_spk_with_vendor_snapshot', $issued['success'] && $issued['status'] === 'SPK_TERBIT' && $spkRow->template_version === 'DRAFT-SPK-COMPAT-V1' && count($snapshot['vendor_comparison']) === 1 && (float) $issuedRequest->estimasi_disetujui === (float) $issuedRequest->estimasi_total, array($issued, $spkRow));
        $spkData = $this->M_PojasaPurchasing->get_spk($spkCode, $users['pic']);
        $this->assert('pic_owner_can_view_issued_spk', $spkData && $spkData['spk']->no_spk === $issued['no_spk']);
        $spkHtml = $this->load->view('content/po/jasa/spk/document', array('request' => $spkData['request'], 'spk' => $spkData['spk'], 'snapshot' => $spkData['snapshot']), true);
        $this->assert('spk_document_renders_company_print_compatible_draft', strpos($spkHtml, $issued['no_spk']) !== false && strpos($spkHtml, 'DRAFT KOMPATIBILITAS') !== false && strpos($spkHtml, 'PT. Karisma Indoagro Universal') !== false);

        $ga = $this->M_PojasaCore->get_request($rejectCode);
        $keu = $this->M_PojasaCore->get_request($requestCode);
        $this->assert('cross_department_purchasing_access_and_kadep_restriction', $this->M_PojasaPurchasing->can_access($users['purchasing'], $ga) && $this->M_PojasaPurchasing->can_access($users['purchasing'], $keu) && !$this->M_PojasaPurchasing->can_access($users['kadep_keu'], $ga) && $this->M_PojasaPurchasing->can_access($users['kadep_keu'], $keu));

        $this->db->trans_rollback();
        $after = $this->counts();
        $markers = (int) $this->db->like('kd_po_jasa', 'P5TEST', 'after')->count_all_results('tbpo_jasa_request');
        $this->assert('phase5_transaction_rolled_back_cleanly', $before === $after && $markers === 0, array('before' => $before, 'after' => $after, 'markers' => $markers));
    }

    private function createRequest($code, $department, $status, $pic, $quantity, $stockId)
    {
        $this->db->insert('tbpo_jasa_request', array(
            'kd_po_jasa' => $code, 'kd_vendor_jasa' => null, 'vendor_usulan' => 'Vendor uji', 'kd_user' => $pic['kode_user'],
            'nm_user' => $pic['nama_user'], 'departemen' => $department, 'tgl_request' => '2026-09-10', 'tgl_target' => '2026-09-30',
            'tgl_mulai_pekerjaan' => '2026-09-11', 'tgl_selesai_pekerjaan' => '2026-09-30', 'lokasi_pekerjaan' => 'Lokasi uji',
            'tujuan_pekerjaan' => 'Pengujian Fase 5', 'catatan_pic' => 'Fixture rollback', 'status' => $status, 'status_version' => 1,
            'revision_no' => 1, 'requires_dirut_ops' => pojasa_requires_dirut_ops($department) ? 1 : 0,
            'estimasi_total' => 100000, 'estimasi_total_jasa' => 50000, 'estimasi_total_bahan' => 50000,
        ));
        $this->db->insert('tbpo_jasa_scope', array('kd_po_jasa' => $code, 'revision_no' => 1, 'line_no' => 1, 'nama_scope' => 'Scope uji', 'qty' => 1, 'satuan' => 'LOT', 'harga_estimasi' => 50000, 'total_estimasi' => 50000));
        return $this->insertMaterial($code, 1, $quantity, $stockId, 'Material uji');
    }

    private function insertMaterial($code, $line, $quantity, $stockId, $name)
    {
        $this->db->insert('tbpo_jasa_material', array('kd_po_jasa' => $code, 'revision_no' => 1, 'line_no' => $line, 'id_brg_nk' => $stockId, 'nama_material' => $name, 'qty_kebutuhan' => $quantity, 'satuan' => 'UNIT', 'harga_estimasi' => 10000, 'total_estimasi' => $quantity * 10000, 'sumber_material' => $stockId ? 'STOK' : 'PEMBELIAN'));
        return (int) $this->db->insert_id();
    }

    private function users()
    {
        $purchasing = $this->db->query("SELECT * FROM tbpo_user WHERE aksess_lv=2 AND UPPER(TRIM(departement))='PURCHASING' LIMIT 1")->row_array();
        $director = $this->db->query("SELECT * FROM tbpo_user WHERE aksess_lv=3 AND UPPER(TRIM(departement))='DIREKTUR' LIMIT 1")->row_array();
        $pic = $this->db->query("SELECT * FROM tbpo_user WHERE aksess_lv=4 AND UPPER(TRIM(departement))='KEUANGAN' LIMIT 1")->row_array();
        $kadep = $this->db->query("SELECT * FROM tbpo_user WHERE aksess_lv=5 AND UPPER(TRIM(departement))='KEUANGAN' LIMIT 1")->row_array();
        if (!$purchasing || !$director || !$pic || !$kadep) { return false; }
        return array('purchasing' => $this->context($purchasing), 'director' => $this->context($director), 'pic' => $this->context($pic), 'kadep_keu' => $this->context($kadep));
    }

    private function context($user)
    {
        return array('id_user' => (int) $user['id_user'], 'kode_user' => $user['kode_user'], 'nama_user' => $user['nama_user'], 'level' => (string) $user['aksess_lv'], 'departemen' => pojasa_normalize_department($user['departement']), 'role' => pojasa_role_from_context($user['aksess_lv'], $user['departement']));
    }

    private function findStock($rows, $id) { foreach ($rows as $row) { if ((int) $row['id_brg_nk'] === $id) { return $row; } } return null; }
    private function counts() { return array('request' => (int) $this->db->count_all('tbpo_jasa_request'), 'vendor' => (int) $this->db->count_all('tbpo_jasa_vendor'), 'comparison' => (int) $this->db->count_all('tbpo_jasa_vendor_comparison'), 'allocation' => (int) $this->db->count_all('tbpo_jasa_stock_allocation'), 'draft' => (int) $this->db->count_all('tbpo_jasa_draft_pembelian'), 'spk' => (int) $this->db->count_all('tbpo_jasa_spk'), 'transaction' => (int) $this->db->count_all('tbpo_transaksi')); }
    private function token() { return sprintf('50000000-0000-4000-8000-%012d', $this->sequence++); }
    private function assert($name, $passed, $detail = null) { $row = array('name' => $name, 'passed' => (bool) $passed); if (!$passed && $detail !== null) { $row['detail'] = $detail; } $this->checks[] = $row; }
}
