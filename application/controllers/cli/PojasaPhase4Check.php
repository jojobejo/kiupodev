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
        $this->assert('initial_purchasing_routes_pic', pojasa_next_status('MENUNGGU_PURCHASING_AWAL', 'UPDATE', 'KEUANGAN') === 'MENUNGGU_KONFIRMASI_PIC');
        $this->assert('confirmed_pic_purchasing_routes_kadep', pojasa_next_status('MENUNGGU_PURCHASING', 'SUBMIT', 'KEUANGAN') === 'MENUNGGU_KADEP');
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
        $this->happyPath($f,'P4TESTGAHAPPY01','GA',$f['kadep_ga']);
    }
    private function testNormalDepartmentHappyPath($f)
    {
        $this->happyPath($f,'P4TESTKEUHAPPY1','KEUANGAN',$f['kadep_keu']);
    }
    private function happyPath($f,$code,$department,$kadep)
    {
        $this->createRequest($code,$department,'MENUNGGU_PURCHASING_AWAL',$f['pic']);
        $updated=$this->approve($f['purchasing'],$code,'UPDATE','Review siap dikonfirmasi PIC.');
        $this->assert($department.'_purchasing_update_requires_pic_confirmation',$updated['success'] && $updated['status']==='MENUNGGU_KONFIRMASI_PIC',$updated);
        $confirmed=$this->approve($f['pic'],$code,'ACC','Setuju hasil review.');
        $submitted=$this->approve($f['purchasing'],$code,'SUBMIT');
        $this->assert($department.'_pic_then_purchasing_routes_kadep',$confirmed['success'] && $submitted['success'] && $submitted['status']==='MENUNGGU_KADEP',array($confirmed,$submitted));
        $wrong=$this->approve($department==='GA'?$f['kadep_keu']:$f['kadep_ga'],$code,'ACC');
        $this->assert($department.'_other_kadep_denied',!$wrong['success'] && $wrong['code']==='FORBIDDEN',$wrong);
        $acc=$this->approve($kadep,$code,'ACC');
        $this->assert($department.'_kadep_routes_ops',$acc['success'] && $acc['status']==='MENUNGGU_DIRUT_OPS',$acc);
        $skip=$this->approve($f['director'],$code,'ACC');
        $pending=$this->approve($f['director_ops'],$code,'PENDING');
        $this->assert($department.'_cannot_skip_ops_or_pending',!$skip['success'] && $skip['code']==='FORBIDDEN' && !$pending['success'] && $pending['code']==='INVALID_ACTION',array($skip,$pending));
        $ops=$this->approve($f['director_ops'],$code,'ACC');
        $this->assert($department.'_ops_routes_director',$ops['success'] && $ops['status']==='MENUNGGU_DIREKTUR',$ops);
        $director=$this->approve($f['director'],$code,'ACC');
        $this->assert($department.'_director_does_not_issue_early',$director['success'] && $director['status']==='MENUNGGU_PENERBITAN_PURCHASING' && !$director['no_spk'],$director);
        $issued=$this->approve($f['purchasing'],$code,'TERBITKAN');
        $request=$this->M_PojasaCore->get_request($code);
        $this->assert($department.'_purchasing_issues_single_spk',$issued['success'] && $request->status==='SPK_TERBIT' && $request->no_spk && (int)$this->db->where('kd_po_jasa',$code)->count_all_results('tbpo_jasa_spk')===1,$issued);
        $approval=$this->db->order_by('id_approval','DESC')->get_where('tbpo_jasa_approval',array('kd_po_jasa'=>$code))->row();
        $other=$this->M_PojasaCore->process_approval($f['director'],$code,'TERBITKAN','',$approval->action_token,'MENUNGGU_PENERBITAN_PURCHASING',(int)$request->status_version);
        $this->assert($department.'_replay_other_actor_denied',!$other['success'] && $other['code']==='FORBIDDEN',$other);
        foreach(array('purchasing','director','pic') as $role) $this->assert($department.'_notification_'.$role,(int)$this->db->where(array('kd_po_jasa'=>$code,'recipient_user_id'=>$f[$role]['id_user']))->count_all_results('tbpo_jasa_notifikasi')>0);
    }
    private function testKadepRevisionAndPending($f)
    {
        $code='P4TESTKADREV001';$this->createRequest($code,'KEUANGAN','MENUNGGU_KADEP',$f['pic'],true);
        $missing=$this->approve($f['kadep_keu'],$code,'REVISI','');
        $this->assert('kadep_revision_note_required_backend',!$missing['success'] && $missing['code']==='NOTE_REQUIRED',$missing);
        $rev=$this->approve($f['kadep_keu'],$code,'REVISI','Perbaiki review Purchasing.');
        $row=$this->db->get_where('tbpo_jasa_revisi',array('kd_po_jasa'=>$code,'revision_no'=>2))->row();
        $this->assert('kadep_revision_returns_purchasing_next_version',$rev['success'] && $rev['status']==='REVISI_PURCHASING_KADEP' && $row,$rev);
        $request=$this->M_PojasaCore->get_request($code);
        $this->assert('kadep_revision_does_not_allow_pic_edit',!$this->M_PojasaPic->can_edit($request,$f['pic']));
        $before=$this->approve($f['purchasing'],$code,'SUBMIT');
        $this->assert('kadep_revision_requires_saved_review',!$before['success'] && $before['code']==='REVISION_NOT_SAVED',$before);
        $save=$this->saveReview($f['purchasing'],$code,'Review diperbaiki.',140000);
        $resubmit=$this->approve($f['purchasing'],$code,'SUBMIT');
        $request=$this->M_PojasaCore->get_request($code);
        $this->assert('kadep_revision_resubmits_to_kadep',$save['success'] && $resubmit['success'] && $resubmit['status']==='MENUNGGU_KADEP' && (int)$request->revision_no===2,array($save,$resubmit));
        $pendingCode='P4TESTPENDING01';$this->createRequest($pendingCode,'KEUANGAN','MENUNGGU_KADEP',$f['pic']);
        $pending=$this->approve($f['kadep_keu'],$pendingCode,'PENDING');
        $request=$this->M_PojasaCore->get_request($pendingCode);
        $submit=$this->M_PojasaPic->submit_request($f['pic'],$pendingCode);
        $this->assert('kadep_pending_locks_pic',$pending['success'] && $request->status==='PENDING_KADEP' && !$this->M_PojasaPic->can_edit($request,$f['pic']) && !$submit['success'],array($pending,$submit));
        $continued=$this->approve($f['kadep_keu'],$pendingCode,'ACC');
        $this->assert('kadep_can_continue_pending_to_ops',$continued['success'] && $continued['status']==='MENUNGGU_DIRUT_OPS',$continued);
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
        $this->assert('purchasing_revision_preserves_versioned_rows', $targetScope && (float) $targetScope->harga_pembanding_purchasing === 175000.0, $targetScope);

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

    private function saveReview($context,$code,$note,$price)
    {
        $r=$this->M_PojasaCore->get_request($code);
        $saved=$this->M_PojasaWorkflow->save_purchasing_review($context,$code,array('segment'=>'purchasing','kd_vendor_jasa'=>'',
            'vendor_usulan'=>'Vendor Fase 4','note'=>$note,'tgl_mulai_pekerjaan'=>$r->tgl_mulai_pekerjaan,'tgl_selesai_pekerjaan'=>$r->tgl_selesai_pekerjaan),$r->status,(int)$r->status_version);
        if(!$saved['success']) return $saved;
        $r=$this->M_PojasaCore->get_request($code);$revision=$r->edit_revision_no?(int)$r->edit_revision_no:(int)$r->revision_no;
        $scopes=array();foreach($this->M_PojasaWorkflow->get_scopes($code,$revision) as $row) $scopes[]=array('id'=>(int)$row['id_scope'],'is_new'=>false,
            'nama_scope'=>$row['nama_scope'],'deskripsi'=>$row['deskripsi'],'qty'=>(float)$row['qty'],'satuan'=>$row['satuan'],'harga_pembanding'=>$price,'keterangan_purchasing'=>$note);
        return $this->M_PojasaWorkflow->save_purchasing_review($context,$code,array('segment'=>'scope','deleted_scopes'=>array(),'scopes'=>$scopes,'note'=>$note),$r->status,(int)$r->status_version);
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
