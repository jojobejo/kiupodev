<?php
defined('BASEPATH') or exit('No direct script access allowed');

class PojasaPhase7Check extends CI_Controller
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
        $ready = $this->M_PojasaPurchasing->spk_revision_ready()
            && $this->db->field_exists('manual_vendor_name', 'tbpo_jasa_vendor_comparison')
            && $this->db->field_exists('spk_version_no', 'tbpo_jasa_progress');
        $this->assert('phase7_schema_ready', $ready);
        if ($ready) $this->runChecks();
        $failed = array_filter($this->checks, function ($row) { return !$row['passed']; });
        echo json_encode(array('success' => !$failed, 'checks' => $this->checks), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL;
        if ($failed) exit(1);
    }

    private function runChecks()
    {
        $users = $this->users();
        $stock = $this->db->query('SELECT * FROM v_stockbarangnk WHERE qty_ready >= 3 ORDER BY qty_ready DESC LIMIT 1')->row();
        if (!$users || !$stock) { $this->assert('phase7_fixtures_available', false); return; }
        $before = $this->counts();
        $masterBefore = (int) $this->db->count_all('tbpo_jasa_vendor');
        $transactionBefore = (int) $this->db->count_all('tbpo_transaksi');
        $this->db->trans_begin();

        $code = 'P7TESTLIMIT001';
        $materialId = $this->createRequest($code, $users['pic'], (int) $stock->id_brg_nk, 'MENUNGGU_PURCHASING');
        $manual = $this->M_PojasaPurchasing->save_comparison($users['purchasing'], $code, 'MANUAL', '', array(
            'manual_vendor_name' => 'Teknisi Manual Phase 7', 'quotation_no' => 'P7-QT-01',
            'quotation_date' => '2026-09-10', 'amount' => 750000, 'lead_time_days' => 2, 'offer_detail' => 'Vendor request-only',
        ));
        $manualRow = $this->db->get_where('tbpo_jasa_vendor_comparison', array('id_vendor_comparison' => $manual['id']))->row();
        $this->assert('manual_offer_does_not_create_active_master_vendor', $manual['success'] && !$manualRow->id_vendor_jasa
            && $manualRow->manual_vendor_name === 'Teknisi Manual Phase 7' && (int) $this->db->count_all('tbpo_jasa_vendor') === $masterBefore, $manualRow);
        $selected = $this->M_PojasaPurchasing->select_comparison($users['purchasing'], $code, $manual['id'], 'Vendor manual sesuai kebutuhan.');
        $request = $this->M_PojasaCore->get_request($code);
        $this->assert('manual_offer_is_renderable_as_selected_request_vendor', $selected['success'] && !$request->kd_vendor_jasa && $request->vendor_usulan === 'Teknisi Manual Phase 7');

        $allocation = $this->M_PojasaPurchasing->allocate_stock($users['purchasing'], $code, $materialId, (int) $stock->id_brg_nk, 3, $this->token());
        $draft = $this->M_PojasaPurchasing->save_purchase_draft($users['purchasing'], $code, $materialId, array(
            'qty' => 7, 'harga_estimasi' => 10000, 'id_vendor_jasa' => null, 'keterangan' => 'Kekurangan material',
        ), $this->token());
        $tooLargeEdit = $this->M_PojasaPurchasing->save_purchase_draft($users['purchasing'], $code, $materialId, array(
            'qty' => 8, 'harga_estimasi' => 10000, 'id_vendor_jasa' => null, 'keterangan' => 'Melebihi kekurangan',
        ), $this->token());
        $state = $this->M_PojasaPurchasing->state($this->M_PojasaCore->get_request($code));
        $this->assert('draft_formula_is_need_minus_active_reservation_minus_active_draft', $allocation['success'] && $draft['success']
            && !$tooLargeEdit['success'] && $tooLargeEdit['code'] === 'DRAFT_QUANTITY_INVALID'
            && abs((float) $state['materials'][0]['remaining_need']) < 0.000001, array($allocation, $draft, $tooLargeEdit));
        $cancelled = $this->M_PojasaPurchasing->cancel_purchase_draft($users['purchasing'], $code, $draft['id'], 'Tidak jadi dibeli.');
        $released = $this->M_PojasaPurchasing->release_stock($users['purchasing'], $code, $allocation['id'], 'Reservasi dihapus.', $this->token());
        $state = $this->M_PojasaPurchasing->state($this->M_PojasaCore->get_request($code));
        $this->assert('delete_draft_and_reservation_restore_remaining_need_without_stock_posting', $cancelled['success'] && $released['success']
            && abs((float) $state['materials'][0]['remaining_need'] - 10.0) < 0.000001
            && (int) $this->db->count_all('tbpo_transaksi') === $transactionBefore);

        $spkCode = 'P7TESTSPK0001';
        $this->createRequest($spkCode, $users['pic'], (int) $stock->id_brg_nk, 'SPK_TERBIT');
        $this->db->where('kd_po_jasa', $spkCode)->update('tbpo_jasa_request', array('no_spk' => 'SPKJ-P7-ROLLBACK'));
        $request = $this->M_PojasaCore->get_request($spkCode);
        $snapshot = json_encode(array('request' => (array) $request, 'scope' => array(), 'materials' => array(), 'vendor' => array()));
        $this->db->insert('tbpo_jasa_spk', array('kd_po_jasa' => $spkCode, 'no_spk' => 'SPKJ-P7-ROLLBACK', 'revision_no' => 1,
            'spk_version_no' => 1, 'issued_by' => $users['director']['id_user'], 'issued_at' => '2026-09-10 08:00:00',
            'snapshot_json' => $snapshot, 'template_version' => 'DRAFT-SPK-COMPAT-V1', 'document_status' => 'ISSUED'));
        $progress1 = $this->M_PojasaExecution->save_progress($users['pic'], $spkCode, $this->progress(10));
        $invalidSchedule = $this->M_PojasaPurchasing->submit_spk_revision($users['purchasing'], $spkCode, '2026-10-20', '2026-10-01', 'Tanggal terbalik.', $this->token());
        $submitted = $this->M_PojasaPurchasing->submit_spk_revision($users['purchasing'], $spkCode, '2026-10-01', '2026-10-20', 'Perubahan kesiapan lokasi.', $this->token());
        $unchanged = $this->M_PojasaCore->get_request($spkCode);
        $revision = $this->db->order_by('id_spk_revision', 'DESC')->get_where('tbpo_jasa_spk_revision', array('kd_po_jasa' => $spkCode))->row();
        $forbiddenDecision = $this->M_PojasaPurchasing->decide_spk_revision($users['purchasing'], $spkCode, $revision->id_spk_revision, 'ACC', '', $this->token());
        $askRevision = $this->M_PojasaPurchasing->decide_spk_revision($users['director'], $spkCode, $revision->id_spk_revision, 'REVISI', 'Perjelas tanggal selesai.', $this->token());
        $resubmitted = $this->M_PojasaPurchasing->submit_spk_revision($users['purchasing'], $spkCode, '2026-10-02', '2026-10-22', 'Tanggal disesuaikan kesiapan PIC.', $this->token());
        $revision = $this->db->order_by('id_spk_revision', 'DESC')->get_where('tbpo_jasa_spk_revision', array('kd_po_jasa' => $spkCode))->row();
        $approved = $this->M_PojasaPurchasing->decide_spk_revision($users['director'], $spkCode, $revision->id_spk_revision, 'ACC', '', $this->token());
        $active = $this->db->get_where('tbpo_jasa_spk', array('kd_po_jasa' => $spkCode))->row();
        $archive = $this->db->get_where('tbpo_jasa_spk_archive', array('kd_po_jasa' => $spkCode, 'spk_version_no' => 1))->row();
        $updated = $this->M_PojasaCore->get_request($spkCode);
        $progress2 = $this->M_PojasaExecution->save_progress($users['pic'], $spkCode, $this->progress(20));
        $p1 = $this->db->get_where('tbpo_jasa_progress', array('id_progress_jasa' => $progress1['id']))->row();
        $p2 = $this->db->get_where('tbpo_jasa_progress', array('id_progress_jasa' => $progress2['id']))->row();
        $this->assert('spk_revision_validates_dates_requires_director_and_keeps_schedule_until_acc', !$invalidSchedule['success'] && $invalidSchedule['code'] === 'SCHEDULE_INVALID'
            && $submitted['success'] && !$forbiddenDecision['success'] && $forbiddenDecision['code'] === 'FORBIDDEN'
            && $unchanged->tgl_mulai_pekerjaan === '2026-09-11' && $askRevision['success'] && $resubmitted['success']);
        $this->assert('director_acc_atomically_archives_snapshot_and_activates_next_version', $approved['success'] && $archive
            && (int) $active->spk_version_no === 2 && $active->no_spk === $archive->no_spk
            && $updated->tgl_mulai_pekerjaan === '2026-10-02' && $updated->tgl_selesai_pekerjaan === '2026-10-22');
        $this->assert('progress_keeps_active_spk_version_reference', $progress1['success'] && $progress2['success'] && (int) $p1->spk_version_no === 1 && (int) $p2->spk_version_no === 2);
        $archiveData = $this->M_PojasaPurchasing->get_spk($spkCode, $users['pic'], 1);
        $this->assert('authorized_user_can_open_archived_spk_version', $archiveData && $archiveData['spk']->document_status === 'ARCHIVED');

        $rejectSubmit = $this->M_PojasaPurchasing->submit_spk_revision($users['purchasing'], $spkCode, '2026-11-01', '2026-11-15', 'Alternatif ditolak.', $this->token());
        $rejectRevision = $this->db->order_by('id_spk_revision', 'DESC')->get_where('tbpo_jasa_spk_revision', array('kd_po_jasa' => $spkCode))->row();
        $rejected = $this->M_PojasaPurchasing->decide_spk_revision($users['director'], $spkCode, $rejectRevision->id_spk_revision, 'REJECT', 'Jadwal aktif dipertahankan.', $this->token());
        $afterReject = $this->M_PojasaCore->get_request($spkCode);
        $this->assert('director_reject_does_not_change_active_schedule_or_version', $rejectSubmit['success'] && $rejected['success']
            && $afterReject->tgl_mulai_pekerjaan === '2026-10-02' && (int) $this->db->get_where('tbpo_jasa_spk', array('kd_po_jasa' => $spkCode))->row()->spk_version_no === 2);
        $this->assert('spk_revision_actions_are_audited_and_notified', (int) $this->db->like('event_type', 'REVISI_SPK_', 'after')->where('kd_po_jasa', $spkCode)->count_all_results('tbpo_jasa_log_aktivitas') >= 6
            && (int) $this->db->where('kd_po_jasa', $spkCode)->like('event_type', 'REVISI_SPK_', 'after')->count_all_results('tbpo_jasa_notifikasi') >= 4);

        $this->db->trans_rollback();
        $this->assert('phase7_transaction_rolled_back_cleanly', $before === $this->counts()
            && (int) $this->db->like('kd_po_jasa', 'P7TEST', 'after')->count_all_results('tbpo_jasa_request') === 0);
    }

    private function createRequest($code, $pic, $stockId, $status)
    {
        $this->db->insert('tbpo_jasa_request', array('kd_po_jasa' => $code, 'kd_user' => $pic['kode_user'], 'nm_user' => $pic['nama_user'],
            'departemen' => $pic['departemen'], 'tgl_request' => '2026-09-10', 'tgl_target' => '2026-09-30',
            'tgl_mulai_pekerjaan' => '2026-09-11', 'tgl_selesai_pekerjaan' => '2026-09-30', 'lokasi_pekerjaan' => '',
            'tujuan_pekerjaan' => 'Phase 7 transactional check', 'estimasi_total' => 100000, 'estimasi_total_bahan' => 100000,
            'status' => $status, 'status_version' => 1, 'revision_no' => 1));
        $this->db->insert('tbpo_jasa_material', array('kd_po_jasa' => $code, 'revision_no' => 1, 'line_no' => 1,
            'id_brg_nk' => $stockId, 'nama_material' => 'Material Phase 7', 'qty_kebutuhan' => 10, 'satuan' => 'PCS',
            'harga_estimasi' => 10000, 'total_estimasi' => 100000, 'sumber_material' => 'STOK', 'created_by' => $pic['id_user'], 'is_active' => 1));
        return (int) $this->db->insert_id();
    }

    private function users()
    {
        $p = $this->db->query("SELECT * FROM tbpo_user WHERE aksess_lv=2 AND UPPER(TRIM(departement))='PURCHASING' LIMIT 1")->row_array();
        $d = $this->db->query("SELECT * FROM tbpo_user WHERE aksess_lv=3 AND UPPER(TRIM(departement))='DIREKTUR' LIMIT 1")->row_array();
        $pic = $this->db->query('SELECT * FROM tbpo_user WHERE aksess_lv=4 LIMIT 1')->row_array();
        if (!$p || !$d || !$pic) return false;
        return array('purchasing' => $this->context($p), 'director' => $this->context($d), 'pic' => $this->context($pic));
    }

    private function context($u) { return array('id_user'=>(int)$u['id_user'],'kode_user'=>$u['kode_user'],'nama_user'=>$u['nama_user'],'departemen'=>pojasa_normalize_department($u['departement']),'role'=>pojasa_role_from_context($u['aksess_lv'],$u['departement'])); }
    private function token() { return sprintf('70000000-0000-4000-8000-%012d', $this->sequence++); }
    private function progress($percent) { return array('tgl_progress'=>'2026-09-10','progress_persen'=>$percent,'status_progress'=>'ON_PROGRESS','aktivitas'=>'Progress Phase 7','kendala'=>'','tindak_lanjut'=>'','catatan'=>'','idempotency_token'=>$this->token()); }
    private function counts() { return array('request'=>(int)$this->db->count_all('tbpo_jasa_request'),'comparison'=>(int)$this->db->count_all('tbpo_jasa_vendor_comparison'),'allocation'=>(int)$this->db->count_all('tbpo_jasa_stock_allocation'),'draft'=>(int)$this->db->count_all('tbpo_jasa_draft_pembelian'),'spk'=>(int)$this->db->count_all('tbpo_jasa_spk'),'archive'=>(int)$this->db->count_all('tbpo_jasa_spk_archive'),'revision'=>(int)$this->db->count_all('tbpo_jasa_spk_revision'),'progress'=>(int)$this->db->count_all('tbpo_jasa_progress'),'log'=>(int)$this->db->count_all('tbpo_jasa_log_aktivitas'),'notification'=>(int)$this->db->count_all('tbpo_jasa_notifikasi')); }
    private function assert($name, $passed, $detail = null) { $row=array('name'=>$name,'passed'=>(bool)$passed); if(!$passed&&$detail!==null)$row['detail']=$detail; $this->checks[]=$row; }
}
