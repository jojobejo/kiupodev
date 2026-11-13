<?php
defined('BASEPATH') or exit('No direct script access allowed');

class PojasaPhase6Check extends CI_Controller
{
    private $checks = array();
    private $sequence = 1;

    public function index()
    {
        if (!is_cli()) { show_404(); return; }
        $this->load->helper(array('pojasa_authorization', 'pojasa_status'));
        $this->load->model('PO/M_PojasaCore');
        $this->load->model('PO/M_PojasaPurchasing');
        $this->load->model('PO/M_PojasaExecution');
        $this->assert('phase6_schema_ready', $this->M_PojasaExecution->schema_ready());
        if ($this->M_PojasaExecution->schema_ready()) { $this->runTransactionalChecks(); }
        $failed = array_filter($this->checks, function ($row) { return !$row['passed']; });
        echo json_encode(array('success' => !$failed, 'checks' => $this->checks), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL;
        if ($failed) { exit(1); }
    }

    private function runTransactionalChecks()
    {
        $users = $this->users();
        $stock = $this->db->query('SELECT * FROM v_stockbarangnk WHERE qty_ready >= 1 ORDER BY qty_ready DESC LIMIT 1')->row();
        if (!$users || !$stock) { $this->assert('phase6_fixtures_available', false); return; }
        $before = $this->counts();
        $level6Before = (int) $this->db->where('aksess_lv', 6)->count_all_results('tbpo_user');
        $this->db->trans_begin();

        $code = 'P6TESTEXEC0001';
        $materialId = $this->createRequest($code, $users['pic'], (int) $stock->id_brg_nk);
        $allocation = $this->M_PojasaPurchasing->allocate_stock($users['purchasing'], $code, $materialId, (int) $stock->id_brg_nk, 1, $this->token());
        $this->assert('stock_is_reserved_before_spk', $allocation['success']);
        $this->db->where('kd_po_jasa', $code)->update('tbpo_jasa_request', array(
            'status' => 'SPK_TERBIT', 'status_version' => 2, 'no_spk' => 'SPKJ-P6-ROLLBACK',
            'estimasi_disetujui' => 900000, 'generated_at' => date('Y-m-d H:i:s'),
        ));

        $state = $this->M_PojasaExecution->get_state($users['pic'], $code);
        $this->assert('pic_owner_execution_state_contains_reserved_material', $state && count($state['allocations']) === 1 && (float) $state['cost_summary']['approved'] === 900000.0, $state);
        $this->assert('other_pic_cannot_view_execution_state', $this->M_PojasaExecution->get_state($users['other_pic'], $code) === false);

        $invalidProgress = $this->M_PojasaExecution->save_progress($users['pic'], $code, $this->progressInput('BELUM_DIMULAI', 20, $this->token()));
        $this->assert('invalid_work_status_percent_is_rejected', !$invalidProgress['success'] && $invalidProgress['code'] === 'PROGRESS_INVALID', $invalidProgress);
        $progressToken = $this->token();
        $progressInput = $this->progressInput('ON_PROGRESS', 25, $progressToken);
        $progress = $this->M_PojasaExecution->save_progress($users['pic'], $code, $progressInput, $this->document('EVIDENCE_PROGRESS'));
        $request = $this->M_PojasaCore->get_request($code);
        $progressRow = $this->db->get_where('tbpo_jasa_progress', array('id_progress_jasa' => $progress['id']))->row();
        $this->assert('progress_evidence_and_structured_fields_are_atomic', $progress['success'] && $request->status === 'ON_PROGRESS' && $progressRow->aktivitas === 'Aktivitas uji' && (int) $progressRow->id_dokumen_evidence > 0, array($progress, $progressRow));
        $replay = $this->M_PojasaExecution->save_progress($users['pic'], $code, $progressInput, $this->document('EVIDENCE_PROGRESS'));
        $conflictInput = $progressInput; $conflictInput['catatan'] = 'Payload berbeda';
        $conflict = $this->M_PojasaExecution->save_progress($users['pic'], $code, $conflictInput, $this->document('EVIDENCE_PROGRESS'));
        $this->assert('progress_idempotency_is_bound_to_actor_and_payload', $replay['success'] && $replay['code'] === 'IDEMPOTENT_REPLAY' && !$conflict['success'] && $conflict['code'] === 'IDEMPOTENCY_CONFLICT', array($replay, $conflict));
        $regression = $this->M_PojasaExecution->save_progress($users['pic'], $code, $this->progressInput('ON_PROGRESS', 10, $this->token()));
        $this->assert('progress_percentage_cannot_regress', !$regression['success'] && $regression['code'] === 'PROGRESS_REGRESSION', $regression);

        $badCost = $this->M_PojasaCore->save_actual_cost($users['pic'], $code, $this->costInput('STOK_NONKOMERSIAL', $this->token()), $this->document('BUKTI_BIAYA_AKTUAL'));
        $this->assert('noncommercial_stock_cannot_be_actual_cost', !$badCost['success'] && $badCost['code'] === 'COST_SOURCE_INVALID', $badCost);
        $this->assert('purchasing_cannot_input_actual_cost', !$this->M_PojasaCore->save_actual_cost($users['purchasing'], $code, $this->costInput('PEMBELIAN_BARU', $this->token()), $this->document('BUKTI_BIAYA_AKTUAL'))['success']);
        $costToken = $this->token();
        $costInput = $this->costInput('NOTA_INVOICE_BARU', $costToken);
        $cost = $this->M_PojasaCore->save_actual_cost($users['pic'], $code, $costInput, $this->document('BUKTI_BIAYA_AKTUAL'));
        $costRow = $this->db->get_where('tbpo_jasa_biaya_aktual', array('id_biaya_aktual' => $cost['id']))->row();
        $this->assert('pic_actual_cost_requires_document_and_non_stock_source', $cost['success'] && $costRow->cost_source === 'NOTA_INVOICE_BARU' && (int) $costRow->id_dokumen_bukti > 0, array($cost, $costRow));
        $costReplay = $this->M_PojasaCore->save_actual_cost($users['pic'], $code, $costInput, $this->document('BUKTI_BIAYA_AKTUAL'));
        $changedCost = $costInput; $changedCost['nominal'] = 200000;
        $costConflict = $this->M_PojasaCore->save_actual_cost($users['pic'], $code, $changedCost, $this->document('BUKTI_BIAYA_AKTUAL'));
        $this->assert('actual_cost_idempotency_is_bound_to_actor_and_payload', $costReplay['success'] && $costReplay['code'] === 'IDEMPOTENT_REPLAY' && !$costConflict['success'] && $costConflict['code'] === 'IDEMPOTENCY_CONFLICT', array($costReplay, $costConflict));

        $transactionBefore = (int) $this->db->count_all('tbpo_transaksi');
        $receiptToken = $this->token();
        $receiptItems = array(array('id_allocation' => $allocation['id'], 'qty_received' => 1));
        $receipt = $this->M_PojasaCore->confirm_stock_receipt($users['pic'], $code, '2026-09-10 10:00:00', 'Diterima utuh', $receiptItems, $receiptToken);
        $transaction = $this->db->query('SELECT * FROM tbpo_transaksi WHERE id_transnk = (SELECT id_transnk FROM tbpo_jasa_stock_receipt_detail WHERE id_receipt = ? LIMIT 1)', array($receipt['id']))->row();
        $this->assert('receipt_atomically_posts_account_11512', $receipt['success'] && $transaction && $transaction->kd_akun === '11512' && (int) $this->db->count_all('tbpo_transaksi') === $transactionBefore + 1, array($receipt, $transaction));
        $receiptReplay = $this->M_PojasaCore->confirm_stock_receipt($users['pic'], $code, '2026-09-10 11:59:59', 'Diterima utuh', $receiptItems, $receiptToken);
        $this->assert('receipt_replay_does_not_post_twice', $receiptReplay['success'] && $receiptReplay['code'] === 'IDEMPOTENT_REPLAY' && (int) $this->db->count_all('tbpo_transaksi') === $transactionBefore + 1, $receiptReplay);
        $receiptConflict = $this->M_PojasaCore->confirm_stock_receipt($users['pic'], $code, '2026-09-10 11:59:59', 'Catatan berubah', $receiptItems, $receiptToken);
        $this->assert('receipt_idempotency_is_bound_to_payload', !$receiptConflict['success'] && $receiptConflict['code'] === 'IDEMPOTENCY_CONFLICT', $receiptConflict);

        $complete = $this->M_PojasaExecution->save_progress($users['pic'], $code, $this->progressInput('SELESAI', 100, $this->token()));
        $closed = $this->M_PojasaExecution->save_progress($users['pic'], $code, $this->progressInput('DITUTUP', 100, $this->token()));
        $closedRequest = $this->M_PojasaCore->get_request($code);
        $this->assert('pic_can_complete_then_close_with_audit_timestamps', $complete['success'] && $closed['success'] && $closedRequest->status === 'DITUTUP' && $closedRequest->completed_at && $closedRequest->closed_at && (int) $closedRequest->closed_by === (int) $users['pic']['id_user'], array($complete, $closed, $closedRequest));
        $afterClosed = $this->M_PojasaExecution->save_progress($users['pic'], $code, $this->progressInput('DITUTUP', 100, $this->token()));
        $this->assert('closed_request_is_terminal', !$afterClosed['success'] && $afterClosed['code'] === 'INVALID_STATUS', $afterClosed);

        $state = $this->M_PojasaExecution->get_state($users['pic'], $code);
        $this->assert('execution_state_calculates_actual_and_variance', (float) $state['cost_summary']['actual'] === 125000.0 && (float) $state['cost_summary']['variance'] === 775000.0 && count($state['receipts']) === 1, $state['cost_summary']);
        $this->assert('execution_changes_create_logs_and_notifications', (int) $this->db->where('kd_po_jasa', $code)->count_all_results('tbpo_jasa_log_aktivitas') >= 4 && (int) $this->db->where('kd_po_jasa', $code)->count_all_results('tbpo_jasa_notifikasi') >= 3);
        $this->assert('role_dashboards_are_scoped', $this->M_PojasaExecution->dashboard_summary($users['pic']) !== null && $this->M_PojasaExecution->dashboard_summary($users['purchasing']) !== null && $this->M_PojasaExecution->dashboard_summary($users['director']) !== null);
        $this->assert('migration_did_not_create_director_ops_user', (int) $this->db->where('aksess_lv', 6)->count_all_results('tbpo_user') === $level6Before);

        $this->db->trans_rollback();
        $after = $this->counts();
        $markers = (int) $this->db->like('kd_po_jasa', 'P6TEST', 'after')->count_all_results('tbpo_jasa_request');
        $this->assert('phase6_transaction_rolled_back_cleanly', $before === $after && $markers === 0, array('before' => $before, 'after' => $after, 'markers' => $markers));
    }

    private function createRequest($code, $pic, $stockId)
    {
        $this->db->insert('tbpo_jasa_request', array(
            'kd_po_jasa' => $code, 'vendor_usulan' => 'Vendor Fase 6', 'kd_user' => $pic['kode_user'], 'nm_user' => $pic['nama_user'],
            'departemen' => $pic['departemen'], 'tgl_request' => '2026-09-10', 'tgl_target' => '2026-09-30',
            'tgl_mulai_pekerjaan' => '2026-09-10', 'tgl_selesai_pekerjaan' => '2026-09-30', 'lokasi_pekerjaan' => 'Lokasi uji',
            'tujuan_pekerjaan' => 'Pengujian eksekusi Fase 6', 'estimasi_total' => 1000000, 'estimasi_total_jasa' => 900000,
            'estimasi_total_bahan' => 100000, 'status' => 'MENUNGGU_PURCHASING', 'status_version' => 1, 'revision_no' => 1,
        ));
        $this->db->insert('tbpo_jasa_scope', array('kd_po_jasa' => $code, 'revision_no' => 1, 'line_no' => 1, 'nama_scope' => 'Scope uji', 'qty' => 1, 'satuan' => 'LOT', 'harga_estimasi' => 900000, 'total_estimasi' => 900000));
        $this->db->insert('tbpo_jasa_material', array('kd_po_jasa' => $code, 'revision_no' => 1, 'line_no' => 1, 'id_brg_nk' => $stockId, 'nama_material' => 'Material uji', 'qty_kebutuhan' => 1, 'satuan' => 'UNIT', 'harga_estimasi' => 100000, 'total_estimasi' => 100000, 'sumber_material' => 'STOK'));
        return (int) $this->db->insert_id();
    }

    private function progressInput($status, $percent, $token)
    {
        return array('tgl_progress' => '2026-09-10', 'progress_persen' => $percent, 'status_progress' => $status, 'aktivitas' => 'Aktivitas uji', 'kendala' => '', 'tindak_lanjut' => 'Tindak lanjut uji', 'catatan' => 'Catatan uji', 'idempotency_token' => $token);
    }

    private function costInput($source, $token)
    {
        return array('tgl_biaya' => '2026-09-10', 'cost_source' => $source, 'id_draft_pembelian' => null, 'jenis_biaya' => 'Jasa tambahan', 'deskripsi' => 'Nota baru pengujian', 'nominal' => 125000, 'idempotency_token' => $token);
    }

    private function document($type)
    {
        return array('jenis_dokumen' => $type, 'keterangan' => 'Dokumen fixture rollback', 'file_path' => 'images/pojasa/PHASE6-TEST/evidence.txt', 'file_original' => 'evidence.txt', 'mime_type' => 'text/plain', 'file_size_bytes' => 12, 'sha256' => str_repeat('6', 64));
    }

    private function users()
    {
        $purchasing = $this->db->query("SELECT * FROM tbpo_user WHERE aksess_lv=2 AND UPPER(TRIM(departement))='PURCHASING' LIMIT 1")->row_array();
        $director = $this->db->query("SELECT * FROM tbpo_user WHERE aksess_lv=3 AND UPPER(TRIM(departement))='DIREKTUR' LIMIT 1")->row_array();
        $pics = $this->db->query('SELECT * FROM tbpo_user WHERE aksess_lv=4 ORDER BY id_user LIMIT 2')->result_array();
        if (!$purchasing || !$director || count($pics) < 2) { return false; }
        return array('purchasing' => $this->context($purchasing), 'director' => $this->context($director), 'pic' => $this->context($pics[0]), 'other_pic' => $this->context($pics[1]));
    }

    private function context($user) { return array('id_user' => (int) $user['id_user'], 'kode_user' => $user['kode_user'], 'nama_user' => $user['nama_user'], 'level' => (string) $user['aksess_lv'], 'departemen' => pojasa_normalize_department($user['departement']), 'role' => pojasa_role_from_context($user['aksess_lv'], $user['departement'])); }
    private function counts() { return array('request' => (int) $this->db->count_all('tbpo_jasa_request'), 'progress' => (int) $this->db->count_all('tbpo_jasa_progress'), 'cost' => (int) $this->db->count_all('tbpo_jasa_biaya_aktual'), 'document' => (int) $this->db->count_all('tbpo_jasa_dokumen'), 'allocation' => (int) $this->db->count_all('tbpo_jasa_stock_allocation'), 'receipt' => (int) $this->db->count_all('tbpo_jasa_stock_receipt'), 'transaction' => (int) $this->db->count_all('tbpo_transaksi'), 'log' => (int) $this->db->count_all('tbpo_jasa_log_aktivitas'), 'notification' => (int) $this->db->count_all('tbpo_jasa_notifikasi')); }
    private function token() { return sprintf('60000000-0000-4000-8000-%012d', $this->sequence++); }
    private function assert($name, $passed, $detail = null) { $row = array('name' => $name, 'passed' => (bool) $passed); if (!$passed && $detail !== null) { $row['detail'] = $detail; } $this->checks[] = $row; }
}
