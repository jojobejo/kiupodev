<?php
defined('BASEPATH') or exit('No direct script access allowed');

class PojasaPhase4Check extends CI_Controller
{
    private $checks = array();
    private $tokenSequence = 1;

    public function index()
    {
        if (!is_cli()) {
            show_404();
            return;
        }
        $this->load->helper(array('pojasa_authorization', 'pojasa_status'));
        $this->load->model('PO/M_PojasaCore');
        $this->load->model('PO/M_PojasaWorkflow');
        $this->load->model('PO/M_PojasaPic');
        $this->assert('phase4_schema_ready', $this->M_PojasaWorkflow->schema_ready());
        $this->testStateMachineDefinitions();
        if ($this->M_PojasaWorkflow->schema_ready()) {
            $this->testWorkflowWithRollback();
        }
        $directorOpsCount = (int) $this->db->query("SELECT COUNT(*) total FROM tbpo_user WHERE aksess_lv=6 AND UPPER(TRIM(departement)) IN ('DIREKTUR OPERASIONAL','DIREKTUR OPRASIONAL')")->row()->total;
        $failed = array_filter($this->checks, function ($check) { return !$check['passed']; });
        echo json_encode(array(
            'success' => !$failed,
            'checks' => $this->checks,
            'prerequisites' => array(
                'director_ops_level_6_user_present' => $directorOpsCount > 0,
                'note' => $directorOpsCount > 0 ? 'User Direktur Operasional tersedia.' : 'UAT seluruh alur memerlukan user level 6; pengujian transaksi menggunakan context policy sintetis tanpa membuat user.',
            ),
        ), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL;
        if ($failed) { exit(1); }
    }

    private function testStateMachineDefinitions()
    {
        $this->assert('kadep_waiting_actions', pojasa_workflow_actions('MENUNGGU_KADEP') === array('ACC', 'REVISI', 'PENDING', 'REJECT'));
        $this->assert('kadep_pending_has_no_second_pending', pojasa_workflow_actions('PENDING_KADEP') === array('ACC', 'REVISI', 'REJECT'));
        $this->assert('director_ops_no_pending', !in_array('PENDING', pojasa_workflow_actions('MENUNGGU_DIRUT_OPS'), true));
        $this->assert('director_no_pending', !in_array('PENDING', pojasa_workflow_actions('MENUNGGU_DIREKTUR'), true));
        $this->assert('initial_purchasing_routes_kadep', pojasa_next_status('MENUNGGU_PURCHASING_AWAL', 'SUBMIT', 'KEUANGAN') === 'MENUNGGU_KADEP');
        $this->assert('post_kadep_purchasing_routes_ops_for_all_departments', pojasa_next_status('MENUNGGU_PURCHASING', 'SUBMIT', 'KEUANGAN') === 'MENUNGGU_DIRUT_OPS');
        $this->assert('post_ops_purchasing_routes_director', pojasa_next_status('MENUNGGU_PURCHASING_DIROPS', 'SUBMIT', 'KEUANGAN') === 'MENUNGGU_DIREKTUR');
        $this->assert('purchasing_revision_returns_ops', pojasa_next_status('REVISI_PURCHASING_DIROPS', 'SUBMIT', 'GA') === 'MENUNGGU_DIRUT_OPS');
        $this->assert('purchasing_revision_returns_director', pojasa_next_status('REVISI_PURCHASING_DIRUT', 'SUBMIT', 'SALES') === 'MENUNGGU_DIREKTUR');
    }

    private function testWorkflowWithRollback()
    {
        $fixtures = $this->users();
        if (!$fixtures) {
            $this->assert('workflow_user_fixtures_available', false, 'KADEP GA/KEUANGAN, Purchasing, Direktur, dan PIC wajib tersedia.');
            return;
        }
        $beforeRequests = (int) $this->db->count_all('tbpo_jasa_request');
        $beforeLevel6 = (int) $this->db->where('aksess_lv', 6)->count_all_results('tbpo_user');
        $this->db->trans_begin();

        $this->testSpecialDepartmentHappyPath($fixtures);
        $this->testNormalDepartmentHappyPath($fixtures);
        $this->testKadepRevisionAndPending($fixtures);
        $this->testPurchasingRevisionPaths($fixtures);
        $this->testRejectAndAccessPolicies($fixtures);

        $this->db->trans_rollback();
        $afterRequests = (int) $this->db->count_all('tbpo_jasa_request');
        $markers = (int) $this->db->like('kd_po_jasa', 'P4TEST', 'after')->count_all_results('tbpo_jasa_request');
        $afterLevel6 = (int) $this->db->where('aksess_lv', 6)->count_all_results('tbpo_user');
        $this->assert('phase4_test_transaction_rolled_back', $beforeRequests === $afterRequests && $markers === 0, array('before' => $beforeRequests, 'after' => $afterRequests, 'markers' => $markers));
        $this->assert('no_director_ops_user_created', $beforeLevel6 === $afterLevel6, array('before' => $beforeLevel6, 'after' => $afterLevel6));
    }

    private function testSpecialDepartmentHappyPath($f)
    {
        $code = 'P4TESTGAHAPPY01';
        $this->createRequest($code, 'GA', 'MENUNGGU_PURCHASING_AWAL', $f['pic']);
        $initialPurchasing = $this->approve($f['purchasing'], $code, 'SUBMIT');
        $this->assert('ga_initial_purchasing_routes_to_kadep', $initialPurchasing['success'] && $initialPurchasing['status'] === 'MENUNGGU_KADEP', $initialPurchasing);
        $wrong = $this->approve($f['kadep_keu'], $code, 'ACC');
        $this->assert('kadep_other_department_denied', !$wrong['success'] && $wrong['code'] === 'FORBIDDEN', $wrong);
        $kadep = $this->approve($f['kadep_ga'], $code, 'ACC');
        $this->assert('ga_kadep_acc_to_purchasing', $kadep['success'] && $kadep['status'] === 'MENUNGGU_PURCHASING', $kadep);
        $purchasing = $this->approve($f['purchasing'], $code, 'SUBMIT');
        $this->assert('ga_purchasing_routes_to_ops', $purchasing['success'] && $purchasing['status'] === 'MENUNGGU_DIRUT_OPS', $purchasing);
        $wrongDirector = $this->approve($f['director'], $code, 'ACC');
        $this->assert('director_cannot_skip_ops', !$wrongDirector['success'] && $wrongDirector['code'] === 'FORBIDDEN', $wrongDirector);
        $pendingOps = $this->approve($f['director_ops'], $code, 'PENDING');
        $this->assert('ops_pending_rejected', !$pendingOps['success'] && $pendingOps['code'] === 'INVALID_ACTION', $pendingOps);
        $ops = $this->approve($f['director_ops'], $code, 'ACC');
        $request = $this->M_PojasaCore->get_request($code);
        $this->assert('ops_acc_returns_to_purchasing', $ops['success'] && $ops['status'] === 'MENUNGGU_PURCHASING_DIROPS' && (int) $request->dirut_ops_approved_revision === 1, $ops);
        $toDirector = $this->approve($f['purchasing'], $code, 'SUBMIT');
        $this->assert('post_ops_purchasing_routes_director', $toDirector['success'] && $toDirector['status'] === 'MENUNGGU_DIREKTUR', $toDirector);
        $director = $this->approve($f['director'], $code, 'ACC');
        $request = $this->M_PojasaCore->get_request($code);
        $spkCount = (int) $this->db->where('kd_po_jasa', $code)->count_all_results('tbpo_jasa_spk');
        $this->assert('director_acc_atomically_issues_spk_special', $director['success'] && $request->status === 'SPK_TERBIT' && $request->no_spk && $spkCount === 1, $director);
        $approval = $this->db->order_by('id_approval', 'DESC')->get_where('tbpo_jasa_approval', array('kd_po_jasa' => $code))->row();
        $replayOtherActor = $this->M_PojasaCore->process_approval(
            $f['purchasing'], $code, 'ACC', '', $approval->action_token, 'MENUNGGU_DIREKTUR', (int) $request->status_version
        );
        $this->assert('idempotency_replay_is_bound_to_original_actor', !$replayOtherActor['success'] && $replayOtherActor['code'] === 'FORBIDDEN', $replayOtherActor);
        $purchasingNotif = (int) $this->db->where(array('kd_po_jasa' => $code, 'recipient_user_id' => $f['purchasing']['id_user']))->count_all_results('tbpo_jasa_notifikasi');
        $directorNotif = (int) $this->db->where(array('kd_po_jasa' => $code, 'recipient_user_id' => $f['director']['id_user']))->count_all_results('tbpo_jasa_notifikasi');
        $picNotif = (int) $this->db->where(array('kd_po_jasa' => $code, 'recipient_user_id' => $f['pic']['id_user']))->count_all_results('tbpo_jasa_notifikasi');
        $this->assert('workflow_notifications_reach_purchasing_director_pic', $purchasingNotif > 0 && $directorNotif > 0 && $picNotif > 0, array($purchasingNotif, $directorNotif, $picNotif));
    }

    private function testNormalDepartmentHappyPath($f)
    {
        $code = 'P4TESTKEUHAPPY1';
        $this->createRequest($code, 'KEUANGAN', 'MENUNGGU_PURCHASING_AWAL', $f['pic']);
        $initialPurchasing = $this->approve($f['purchasing'], $code, 'SUBMIT');
        $this->assert('normal_initial_purchasing_routes_to_kadep', $initialPurchasing['success'] && $initialPurchasing['status'] === 'MENUNGGU_KADEP', $initialPurchasing);
        $this->approve($f['kadep_keu'], $code, 'ACC');
        $toOps = $this->approve($f['purchasing'], $code, 'SUBMIT');
        $this->assert('all_departments_route_to_ops', $toOps['success'] && $toOps['status'] === 'MENUNGGU_DIRUT_OPS', $toOps);
        $ops = $this->approve($f['director_ops'], $code, 'ACC');
        $this->assert('normal_ops_returns_to_purchasing', $ops['success'] && $ops['status'] === 'MENUNGGU_PURCHASING_DIROPS', $ops);
        $toDirector = $this->approve($f['purchasing'], $code, 'SUBMIT');
        $this->assert('normal_post_ops_purchasing_routes_director', $toDirector['success'] && $toDirector['status'] === 'MENUNGGU_DIREKTUR', $toDirector);
        $director = $this->approve($f['director'], $code, 'ACC');
        $this->assert('director_acc_atomically_issues_spk_normal', $director['success'] && $director['status'] === 'SPK_TERBIT' && !empty($director['no_spk']), $director);
    }

    private function testKadepRevisionAndPending($f)
    {
        $revisionCode = 'P4TESTKADREV001';
        $this->createRequest($revisionCode, 'KEUANGAN', 'MENUNGGU_KADEP', $f['pic'], true);
        $missingNote = $this->approve($f['kadep_keu'], $revisionCode, 'REVISI', '');
        $this->assert('kadep_revision_note_required_backend', !$missingNote['success'] && $missingNote['code'] === 'NOTE_REQUIRED', $missingNote);
        $revision = $this->approve($f['kadep_keu'], $revisionCode, 'REVISI', 'Lengkapi scope pekerjaan.');
        $revisionRow = $this->db->get_where('tbpo_jasa_revisi', array('kd_po_jasa' => $revisionCode, 'revision_no' => 2))->row();
        $this->assert('kadep_revision_targets_next_version', $revision['success'] && $revision['status'] === 'REVISI_PIC' && $revisionRow !== null, $revision);
        $header = array(
            'kd_vendor_jasa' => null, 'vendor_usulan' => 'Vendor revisi', 'tgl_request' => '2026-09-10',
            'tgl_target' => '2026-09-30', 'tgl_mulai_pekerjaan' => '2026-09-11', 'tgl_selesai_pekerjaan' => '2026-09-30',
            'lokasi_pekerjaan' => 'Kantor', 'tujuan_pekerjaan' => 'Request revisi KADEP', 'catatan_pic' => 'Diperbaiki PIC',
        );
        $save = $this->M_PojasaPic->save_draft($f['pic'], $revisionCode, $header, array(array('nama_scope' => 'Scope revisi', 'deskripsi' => 'Revisi PIC', 'qty' => 1, 'satuan' => 'LOT', 'harga_estimasi' => 150000)), array());
        $submit = $this->M_PojasaPic->submit_request($f['pic'], $revisionCode);
        $revisionRow = $this->db->get_where('tbpo_jasa_revisi', array('kd_po_jasa' => $revisionCode, 'revision_no' => 2))->row();
        $this->assert('pic_revision_resubmits_to_initial_purchasing', $save['success'] && $submit['success'] && $submit['status'] === 'MENUNGGU_PURCHASING_AWAL' && !empty($revisionRow->submitted_at), array($save, $submit));
        $purchasingNotification = (int) $this->db->where(array('kd_po_jasa' => $revisionCode, 'recipient_user_id' => $f['purchasing']['id_user'], 'event_type' => 'MENUNGGU_PURCHASING_AWAL'))->count_all_results('tbpo_jasa_notifikasi');
        $this->assert('revision_resubmit_notifies_purchasing', $purchasingNotification > 0, $purchasingNotification);

        $pendingCode = 'P4TESTPENDING01';
        $this->createRequest($pendingCode, 'KEUANGAN', 'MENUNGGU_KADEP', $f['pic']);
        $pending = $this->approve($f['kadep_keu'], $pendingCode, 'PENDING', '');
        $pendingRequest = $this->M_PojasaCore->get_request($pendingCode);
        $picSubmit = $this->M_PojasaPic->submit_request($f['pic'], $pendingCode);
        $this->assert('kadep_pending_locks_pic', $pending['success'] && $pendingRequest->status === 'PENDING_KADEP' && !$this->M_PojasaPic->can_edit($pendingRequest, $f['pic']) && !$picSubmit['success'], array($pending, $picSubmit));
        $pendingFollowUp = $this->approve($f['kadep_keu'], $pendingCode, 'ACC');
        $this->assert('kadep_can_continue_pending', $pendingFollowUp['success'] && $pendingFollowUp['status'] === 'MENUNGGU_PURCHASING', $pendingFollowUp);
    }

    private function testPurchasingRevisionPaths($f)
    {
        $opsCode = 'P4TESTOPSREV001';
        $this->createRequest($opsCode, 'GA', 'MENUNGGU_DIRUT_OPS', $f['pic']);
        $revision = $this->approve($f['director_ops'], $opsCode, 'REVISI', 'Sesuaikan estimasi scope.');
        $this->assert('ops_revision_returns_purchasing', $revision['success'] && $revision['status'] === 'REVISI_PURCHASING_DIROPS', $revision);
        $beforeSave = $this->approve($f['purchasing'], $opsCode, 'SUBMIT');
        $this->assert('ops_revision_cannot_submit_before_save', !$beforeSave['success'] && $beforeSave['code'] === 'REVISION_NOT_SAVED', $beforeSave);
        $saved = $this->saveReview($f['purchasing'], $opsCode, 'Perbaikan untuk DirOps', 175000);
        $submitted = $this->approve($f['purchasing'], $opsCode, 'SUBMIT');
        $request = $this->M_PojasaCore->get_request($opsCode);
        $this->assert('ops_revision_resubmits_directly_ops', $saved['success'] && $submitted['success'] && $request->status === 'MENUNGGU_DIRUT_OPS' && (int) $request->revision_no === 2, array($saved, $submitted));
        $targetScope = $this->db->get_where('tbpo_jasa_scope', array('kd_po_jasa' => $opsCode, 'revision_no' => 2, 'line_no' => 1))->row();
        $this->assert('purchasing_revision_preserves_versioned_rows', $targetScope && (float) $targetScope->harga_estimasi === 175000.0, $targetScope);

        $directorCode = 'P4TESTDIRREV001';
        $this->createRequest($directorCode, 'KEUANGAN', 'MENUNGGU_DIREKTUR', $f['pic']);
        $revision = $this->approve($f['director'], $directorCode, 'REVISI', 'Koreksi estimasi final.');
        $this->assert('director_revision_returns_purchasing', $revision['success'] && $revision['status'] === 'REVISI_PURCHASING_DIRUT', $revision);
        $saved = $this->saveReview($f['purchasing'], $directorCode, 'Perbaikan untuk Direktur', 225000);
        $submitted = $this->approve($f['purchasing'], $directorCode, 'SUBMIT');
        $request = $this->M_PojasaCore->get_request($directorCode);
        $this->assert('director_revision_resubmits_directly_director', $saved['success'] && $submitted['success'] && $request->status === 'MENUNGGU_DIREKTUR' && (int) $request->revision_no === 2, array($saved, $submitted));
        $stale = $this->M_PojasaCore->process_approval($f['director'], $directorCode, 'ACC', '', $this->token(), 'MENUNGGU_DIREKTUR', 1);
        $this->assert('stale_status_version_blocked', !$stale['success'] && $stale['code'] === 'CONCURRENT_UPDATE', $stale);
    }

    private function testRejectAndAccessPolicies($f)
    {
        $cases = array(
            array('P4TESTREJKAD01', 'KEUANGAN', 'MENUNGGU_KADEP', $f['kadep_keu'], 'DITOLAK_KADEP'),
            array('P4TESTREJOPS01', 'GA', 'MENUNGGU_DIRUT_OPS', $f['director_ops'], 'DITOLAK_DIRUT_OPS'),
            array('P4TESTREJDIR01', 'KEUANGAN', 'MENUNGGU_DIREKTUR', $f['director'], 'DITOLAK_DIREKTUR'),
        );
        foreach ($cases as $case) {
            $this->createRequest($case[0], $case[1], $case[2], $f['pic']);
            $result = $this->approve($case[3], $case[0], 'REJECT', 'Alasan reject pengujian.');
            $this->assert('reject_' . strtolower($case[4]), $result['success'] && $result['status'] === $case[4], $result);
        }
        $gaRequest = $this->M_PojasaCore->get_request('P4TESTREJOPS01');
        $keuRequest = $this->M_PojasaCore->get_request('P4TESTREJDIR01');
        $this->assert('director_ops_accesses_all_departments', $this->M_PojasaWorkflow->can_access_request($f['director_ops'], $gaRequest) && $this->M_PojasaWorkflow->can_access_request($f['director_ops'], $keuRequest));
        $this->assert('kadep_access_only_own_department', $this->M_PojasaWorkflow->can_access_request($f['kadep_ga'], $gaRequest) && !$this->M_PojasaWorkflow->can_access_request($f['kadep_ga'], $keuRequest));
        $approval = $this->db->get_where('tbpo_jasa_approval', array('kd_po_jasa' => 'P4TESTREJDIR01'))->row();
        $logCount = (int) $this->db->where('kd_po_jasa', 'P4TESTREJDIR01')->count_all_results('tbpo_jasa_log_aktivitas');
        $this->assert('approval_history_records_actor_time_status_note', $approval && $approval->actor_id && $approval->created_at && $approval->from_status && $approval->to_status && $approval->catatan !== '', $approval);
        $this->assert('approval_action_logged', $logCount > 0, $logCount);
    }

    private function saveReview($context, $code, $note, $price)
    {
        $request = $this->M_PojasaCore->get_request($code);
        $revision = !empty($request->edit_revision_no) ? (int) $request->edit_revision_no : (int) $request->revision_no;
        $scopes = $this->M_PojasaWorkflow->get_scopes($code, $revision);
        $materials = $this->M_PojasaWorkflow->get_materials($code, $revision);
        $scopePayload = array();
        foreach ($scopes as $row) {
            $scopePayload[] = array('line_no' => (int) $row['line_no'], 'qty' => (float) $row['qty'], 'harga_estimasi' => $price, 'keterangan_purchasing' => $note);
        }
        $materialPayload = array();
        foreach ($materials as $row) {
            $materialPayload[] = array('line_no' => (int) $row['line_no'], 'qty' => (float) $row['qty_kebutuhan'], 'harga_estimasi' => (float) $row['harga_estimasi']);
        }
        return $this->M_PojasaWorkflow->save_purchasing_review($context, $code, array(
            'kd_vendor_jasa' => '', 'note' => $note, 'scopes' => $scopePayload, 'materials' => $materialPayload,
        ), $request->status, (int) $request->status_version);
    }

    private function createRequest($code, $department, $status, $pic, $withDocument = false)
    {
        $this->db->insert('tbpo_jasa_request', array(
            'kd_po_jasa' => $code, 'kd_vendor_jasa' => null, 'vendor_usulan' => 'Vendor Fase 4',
            'kd_user' => $pic['kode_user'], 'nm_user' => $pic['nama_user'], 'departemen' => $department,
            'tgl_request' => '2026-09-10', 'tgl_target' => '2026-09-30', 'tgl_mulai_pekerjaan' => '2026-09-11',
            'tgl_selesai_pekerjaan' => '2026-09-30', 'lokasi_pekerjaan' => 'Lokasi test',
            'tujuan_pekerjaan' => 'PHASE4_TRANSACTION_TEST', 'estimasi_total' => 120000,
            'estimasi_total_jasa' => 100000, 'estimasi_total_bahan' => 20000,
            'status' => $status, 'status_version' => 1, 'revision_no' => 1,
            'requires_dirut_ops' => pojasa_requires_dirut_ops($department) ? 1 : 0,
        ));
        $this->db->insert('tbpo_jasa_scope', array(
            'kd_po_jasa' => $code, 'revision_no' => 1, 'line_no' => 1, 'jenis_scope' => 'JASA',
            'nama_scope' => 'Scope test', 'deskripsi' => 'Scope workflow', 'qty' => 1, 'satuan' => 'LOT',
            'harga_estimasi' => 100000, 'total_estimasi' => 100000, 'is_active' => 1,
        ));
        $this->db->insert('tbpo_jasa_material', array(
            'kd_po_jasa' => $code, 'revision_no' => 1, 'line_no' => 1, 'nama_material' => 'Material test',
            'qty_kebutuhan' => 2, 'satuan' => 'PCS', 'harga_estimasi' => 10000, 'total_estimasi' => 20000,
            'sumber_material' => 'PEMBELIAN', 'created_by' => $pic['id_user'], 'is_active' => 1,
        ));
        if ($withDocument) {
            $this->db->insert('tbpo_jasa_dokumen', array(
                'kd_po_jasa' => $code, 'revision_no' => 1, 'jenis_dokumen' => 'DOKUMEN_REQUEST',
                'file_path' => 'images/pojasa/PHASE4-TEST/document.txt', 'file_original' => 'document.txt',
                'mime_type' => 'text/plain', 'file_size_bytes' => 1, 'sha256' => str_repeat('4', 64),
                'uploaded_by' => $pic['id_user'], 'is_active' => 1,
            ));
        }
    }

    private function approve($context, $code, $action, $note = '')
    {
        $request = $this->M_PojasaCore->get_request($code);
        return $this->M_PojasaCore->process_approval(
            $context, $code, $action, $note, $this->token(),
            $request ? $request->status : null, $request ? (int) $request->status_version : null
        );
    }

    private function users()
    {
        $queries = array(
            'kadep_ga' => "SELECT * FROM tbpo_user WHERE aksess_lv=5 AND UPPER(TRIM(departement))='GA' ORDER BY id_user LIMIT 1",
            'kadep_keu' => "SELECT * FROM tbpo_user WHERE aksess_lv=5 AND UPPER(TRIM(departement))='KEUANGAN' ORDER BY id_user LIMIT 1",
            'purchasing' => "SELECT * FROM tbpo_user WHERE aksess_lv=2 AND UPPER(TRIM(departement))='PURCHASING' ORDER BY id_user LIMIT 1",
            'director' => "SELECT * FROM tbpo_user WHERE aksess_lv=3 AND UPPER(TRIM(departement))='DIREKTUR' ORDER BY id_user LIMIT 1",
            'pic' => "SELECT * FROM tbpo_user WHERE aksess_lv=4 AND UPPER(TRIM(departement))='KEUANGAN' ORDER BY id_user LIMIT 1",
        );
        $users = array();
        foreach ($queries as $key => $sql) {
            $row = $this->db->query($sql)->row();
            if (!$row) { return false; }
            $users[$key] = $this->context($row, null);
        }
        $users['director_ops'] = $users['director'];
        $users['director_ops']['role'] = 'DIREKTUR_OPERASIONAL';
        $users['director_ops']['departemen'] = 'DIREKTUR OPERASIONAL';
        return $users;
    }

    private function context($user, $role)
    {
        return array(
            'id_user' => (int) $user->id_user, 'kode_user' => (string) $user->kode_user,
            'nama_user' => (string) $user->nama_user, 'departemen' => pojasa_normalize_department($user->departement),
            'role' => $role ?: pojasa_role_from_context($user->aksess_lv, $user->departement),
            'ip_address' => '127.0.0.1', 'user_agent' => 'PojasaPhase4Check',
        );
    }

    private function token()
    {
        return sprintf('00000000-0000-4000-8000-%012d', $this->tokenSequence++);
    }

    private function assert($name, $passed, $details = null)
    {
        $row = array('name' => $name, 'passed' => (bool) $passed);
        if (!$passed && $details !== null) { $row['details'] = $details; }
        $this->checks[] = $row;
    }
}
