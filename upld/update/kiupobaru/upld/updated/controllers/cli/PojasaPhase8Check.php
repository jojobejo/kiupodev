<?php
defined('BASEPATH') or exit('No direct script access allowed');

class PojasaPhase8Check extends CI_Controller
{
    private $checks = array();
    private $sequence = 1;

    public function index()
    {
        if (!is_cli()) { show_404(); return; }
        $this->load->helper(array('pojasa_authorization', 'pojasa_status'));
        $this->load->model('PO/M_PojasaCore');
        $this->load->model('PO/M_PojasaWorkflow');
        $this->load->model('PO/M_PojasaIntegration');
        $this->load->model('PO/M_PojasaSpkChange');
        $ready = $this->M_PojasaIntegration->schema_ready() && $this->M_PojasaSpkChange->schema_ready();
        $this->assert('phase8_schema_ready', $ready);
        if ($ready) $this->runChecks();
        $failed = array_filter($this->checks, function ($row) { return !$row['passed']; });
        echo json_encode(array('success' => !$failed, 'checks' => $this->checks), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL;
        if ($failed) exit(1);
    }

    private function runChecks()
    {
        $users = $this->users();
        if (!$users) { $this->assert('phase8_users_available', false); return; }
        $before = $this->counts();
        $legacyTransactions = (int) $this->db->count_all('tbpo_transaksi');
        $this->db->trans_begin();

        $code = 'P8TESTFLOW001';
        list($scopeId, $material1, $material2) = $this->createRequest($code, $users['pic'], 'IT');
        $request = $this->M_PojasaCore->get_request($code);
        $this->assert('pic_owner_can_access_secure_workflow_document', $this->M_PojasaWorkflow->can_access_request($users['pic'], $request));
        $otherPic = $users['pic']; $otherPic['kode_user'] = 'OTHER-PIC';
        $this->assert('other_pic_cannot_access_secure_workflow_document', !$this->M_PojasaWorkflow->can_access_request($otherPic, $request));
        $kadep=$users['pic'];$kadep['role']='KADEP';$kadep['departemen']='IT';
        $this->assert('workflow_document_access_all_required_roles', $this->M_PojasaWorkflow->can_access_request($users['purchasing'],$request) && $this->M_PojasaWorkflow->can_access_request($kadep,$request) && $this->M_PojasaWorkflow->can_access_request($users['dirops'],$request) && $this->M_PojasaWorkflow->can_access_request($users['director'],$request));
        $this->assert('image_pdf_preview_office_txt_download_only', $this->M_PojasaWorkflow->document_can_preview((object)array('file_original'=>'image.jpg','mime_type'=>'image/jpeg')) && $this->M_PojasaWorkflow->document_can_preview((object)array('file_original'=>'file.pdf','mime_type'=>'application/pdf')) && !$this->M_PojasaWorkflow->document_can_preview((object)array('file_original'=>'file.docx','mime_type'=>'application/vnd.openxmlformats-officedocument.wordprocessingml.document')) && !$this->M_PojasaWorkflow->document_can_preview((object)array('file_original'=>'note.txt','mime_type'=>'text/plain')));
        $workflowView=file_get_contents(APPPATH.'views/content/po/jasa/workflow/detail.php');
        $this->assert('image_preview_uses_internal_modal', strpos($workflowView,'workflowDocumentModal')!==false && strpos($workflowView,'workflow-document-preview')!==false && strpos($workflowView,'Google Docs Viewer')===false);

        $this->db->insert('tbpo_jasa_stock_allocation', array('kd_po_jasa'=>$code,'id_material'=>$material1,'id_brg_nk'=>$this->stockId(),'qty_allocation'=>2,'qty_received'=>0,'qty_released'=>0,'status_allocation'=>'ACTIVE','allocated_by'=>$users['purchasing']['id_user']));
        $this->draft($code, $material1, 3, 20000, $users['purchasing']);
        $this->draft($code, $material2, 4, 30000, $users['purchasing']);
        $this->db->insert('tbpo_jasa_material', array('kd_po_jasa'=>$code,'revision_no'=>0,'line_no'=>1,'nama_material'=>'Draft revisi lama','deskripsi'=>'Tidak boleh ikut diajukan','qty_kebutuhan'=>99,'satuan'=>'PCS','harga_estimasi'=>1,'total_estimasi'=>99,'sumber_material'=>'PEMBELIAN','created_by'=>$users['pic']['id_user'],'is_active'=>0));
        $staleMaterial = (int) $this->db->insert_id();
        $this->db->insert('tbpo_jasa_draft_pembelian', array('kd_po_jasa'=>$code,'revision_no'=>0,'id_material'=>$staleMaterial,'qty'=>99,'harga_estimasi'=>1,'keterangan'=>'Stale P8','status_draft'=>'DRAFT','version'=>1,'idempotency_token'=>$this->token(),'created_by'=>$users['purchasing']['id_user']));
        $materials = $this->M_PojasaWorkflow->get_materials($code, 1);
        $this->assert('fulfillment_formula_backend_computed', abs($materials[0]['reserved_qty']-2)<0.000001 && abs($materials[0]['active_draft_qty']-3)<0.000001 && abs($materials[0]['remaining_need']-5)<0.000001 && $materials[0]['computed_source']==='CAMPURAN');
        $this->assert('material_with_active_planning_is_locked', !empty($materials[0]['planning_locked']));

        $created = $this->M_PojasaIntegration->create_purchase_submission($users['purchasing'], $code, $this->token());
        $state = $this->M_PojasaIntegration->get_submission_state($code);
        $this->assert('one_comprehensive_purchase_header_created', $created['success'] && $created['code']==='CREATED' && count($state['details'])===2);
        $purchaseHeader = $this->db->get_where('tbpo_po_nk', array('id_po_nk'=>$state['submission']['id_po_nk']))->row();
        $this->assert('purchase_header_starts_proses_pembelian', $state['submission']['status']==='PROSES_PEMBELIAN' && $purchaseHeader->status==='PROSES PEMBELIAN');
        $this->assert('purchase_submission_excludes_stale_material_drafts_and_keeps_pic_owner', $purchaseHeader->kd_user===$users['pic']['kode_user'] && $purchaseHeader->acc_with_kadep==='KADEP-P8-IT' && (int)$this->db->where(array('id_material'=>$staleMaterial,'status_draft'=>'DRAFT'))->count_all_results('tbpo_jasa_draft_pembelian')===1);
        $this->assert('drafts_are_mapped_and_locked_after_submission', (int)$this->db->where(array('kd_po_jasa'=>$code,'status_draft'=>'DIAJUKAN_KE_PO_PEMBELIAN'))->count_all_results('tbpo_jasa_draft_pembelian')===2);
        $this->assert('purchase_creation_does_not_post_stock', (int)$this->db->count_all('tbpo_transaksi')===$legacyTransactions);
        $replay = $this->M_PojasaIntegration->create_purchase_submission($users['purchasing'], $code, $this->token());
        $this->assert('purchase_submission_is_idempotent_by_spk_version', $replay['success'] && $replay['code']==='IDEMPOTENT_REPLAY' && $replay['kd_po_nk']===$created['kd_po_nk']);
        $noDraftCode='P8TESTEMPTY01';$this->createRequest($noDraftCode,$users['pic'],'FINANCE');$poBefore=(int)$this->db->count_all('tbpo_po_nk');$noDraft=$this->M_PojasaIntegration->create_purchase_submission($users['purchasing'],$noDraftCode,$this->token());
        $this->assert('spk_without_draft_does_not_create_empty_purchase', $noDraft['success'] && $noDraft['code']==='NO_ACTIVE_DRAFT' && (int)$this->db->count_all('tbpo_po_nk')===$poBefore);
        $devCode='P8TESTDEV001'; list($devScope,$devMaterial,$devMaterial2)=$this->createRequest($devCode,$users['pic'],'IT');
        $this->db->where('kd_po_jasa',$devCode)->delete('tbpo_jasa_spk');
        $this->draft($devCode,$devMaterial,2,18000,$users['purchasing']);
        $devCreated=$this->M_PojasaIntegration->create_purchase_submission($users['purchasing'],$devCode,$this->token(),true);
        $devHeader=$devCreated['success'] ? $this->db->get_where('tbpo_po_nk',array('id_po_nk'=>$devCreated['id_po_nk']))->row() : null;
        $this->assert('dev_purchase_can_create_legacy_po_without_spk_and_preserves_draft', $devCreated['success'] && $devCreated['code']==='DEV_CREATED' && $devHeader && $devHeader->status==='PROSES PEMBELIAN' && $devHeader->source_module==='PO_JASA_DEV' && (int)$this->db->where(array('kd_po_jasa'=>$devCode,'status_draft'=>'DRAFT'))->count_all_results('tbpo_jasa_draft_pembelian')===1);

        $detail1 = $state['details'][0]; $detail2 = $state['details'][1];
        $receiptToken = $this->token();
        $partial = $this->M_PojasaIntegration->receive_purchase($users['purchasing'], $code, $detail1['id_detail_po_nk'], 1, 150000, '2026-09-11', 'SJ-P8-1', $receiptToken);
        $onHandTransaction = !empty($partial['id_transnk']) ? $this->db->get_where('tbpo_transaksi', array('id_transnk' => (int) $partial['id_transnk']))->row() : null;
        $this->assert('partial_on_hand_creates_automatic_cost', $partial['success'] && $partial['code']==='PARTIAL_ON_HAND' && (int)$this->db->where(array('kd_po_jasa'=>$code,'record_type'=>'AUTOMATIC'))->count_all_results('tbpo_jasa_biaya_aktual')===1);
        $this->assert('on_hand_posts_stock_in_account_11511', $onHandTransaction && $onHandTransaction->kd_akun==='11511' && (float)$onHandTransaction->tr_qty===1.0);
        $this->assert('overbudget_cost_waits_for_revision_approval', $partial['cost_status']==='MENUNGGU_PERSETUJUAN_REVISI');
        $partialReplay = $this->M_PojasaIntegration->receive_purchase($users['purchasing'], $code, $detail1['id_detail_po_nk'], 1, 150000, '2026-09-11', 'SJ-P8-1', $receiptToken);
        $this->assert('receipt_replay_does_not_duplicate_cost', $partialReplay['success'] && $partialReplay['code']==='IDEMPOTENT_REPLAY' && (int)$this->db->where(array('kd_po_jasa'=>$code,'record_type'=>'AUTOMATIC'))->count_all_results('tbpo_jasa_biaya_aktual')===1);
        $this->M_PojasaIntegration->receive_purchase($users['purchasing'], $code, $detail1['id_detail_po_nk'], 2, 20000, '2026-09-11', 'SJ-P8-2', $this->token());
        $this->M_PojasaIntegration->receive_purchase($users['purchasing'], $code, $detail2['id_detail_po_nk'], 4, 30000, '2026-09-11', 'SJ-P8-3', $this->token());
        $state = $this->M_PojasaIntegration->get_submission_state($code);
        $this->assert('all_received_keeps_legacy_purchase_in_process', $state['submission']['status']==='ON_HAND' && $this->db->get_where('tbpo_po_nk', array('id_po_nk'=>$state['submission']['id_po_nk']))->row()->status==='PROSES PEMBELIAN');
        $reversed = $this->M_PojasaIntegration->reverse_receipt($users['purchasing'], $code, $partial['id_receipt'], 'Barang dikembalikan.', $this->token());
        $this->assert('receipt_reversal_is_additive_and_audited', $reversed['success'] && (int)$this->db->where(array('kd_po_jasa'=>$code,'record_type'=>'REVERSAL'))->count_all_results('tbpo_jasa_purchase_receipt')===1 && (int)$this->db->where(array('kd_po_jasa'=>$code,'record_type'=>'REVERSAL'))->count_all_results('tbpo_jasa_biaya_aktual')===1);

        $manual = $this->M_PojasaCore->save_actual_cost($users['pic'], $code, array('tgl_biaya'=>'2026-09-11','cost_source'=>'PEMBELIAN_BARU','id_draft_pembelian'=>null,'id_scope'=>$scopeId,'id_material'=>null,'jenis_biaya'=>'Transport','deskripsi'=>'Biaya manual','nominal'=>60000,'idempotency_token'=>$this->token()), $this->document());
        $this->assert('manual_cost_has_owner_allocation_and_audit', $manual['success'] && (int)$this->db->where(array('id_biaya_aktual'=>$manual['id'],'id_scope'=>$scopeId,'record_type'=>'MANUAL'))->count_all_results('tbpo_jasa_biaya_aktual')===1 && (int)$this->db->where('id_biaya_aktual',$manual['id'])->count_all_results('tbpo_jasa_cost_audit')===1);
        $this->assert('manual_overbudget_also_requires_cost_revision', isset($manual['record_status']) && $manual['record_status']==='MENUNGGU_PERSETUJUAN_REVISI');
        $forbidden = $this->M_PojasaCore->update_actual_cost($users['purchasing'], $code, $manual['id'], array('tgl_biaya'=>'2026-09-11','cost_source'=>'PEMBELIAN_BARU','jenis_biaya'=>'X','deskripsi'=>'X','nominal'=>10,'id_scope'=>$scopeId,'id_material'=>null), 'Tidak berhak');
        $verified = $this->M_PojasaCore->verify_actual_cost($users['purchasing'], $code, $manual['id'], 'DIVERIFIKASI', 'Sesuai bukti');
        $this->assert('purchasing_verifies_but_cannot_edit_manual_cost', !$forbidden['success'] && $forbidden['code']==='FORBIDDEN' && $verified['success']);

        $this->db->where('id_po_nk', (int)$state['submission']['id_po_nk'])->update('tbpo_po_nk', array('status'=>'DONE'));
        $this->db->where('id_submission', (int)$state['submission']['id_submission'])->update('tbpo_jasa_purchase_submission', array('status'=>'ON_HAND'));
        $dataChange = $this->M_PojasaSpkChange->submit($users['purchasing'], $code, 'DATA', array('tgl_mulai_pekerjaan'=>'2026-09-12','tgl_selesai_pekerjaan'=>'2026-10-02','materials'=>array(array('id'=>$material1,'name'=>'Material P8 revised','description'=>'Revised','qty'=>3,'unit'=>'PCS','estimated_price'=>25000))), 'Perubahan kebutuhan.', $this->token());
        $this->assert('it_change_routes_to_dirops_without_pending_action', $dataChange['success'] && $dataChange['status']==='MENUNGGU_DIROPS');
        $directorSkip = $this->M_PojasaSpkChange->decide($users['director'], $code, $dataChange['id_change'], 'ACC', '', $this->token());
        $opsAcc = $this->M_PojasaSpkChange->decide($users['dirops'], $code, $dataChange['id_change'], 'ACC', '', $this->token());
        $directorAcc = $this->M_PojasaSpkChange->decide($users['director'], $code, $dataChange['id_change'], 'ACC', '', $this->token());
        $this->assert('dirops_then_director_approval_enforced', !$directorSkip['success'] && $opsAcc['success'] && $opsAcc['status']==='MENUNGGU_DIREKTUR' && $directorAcc['success']);
        $this->assert('approved_change_archives_and_versions_spk', (int)$this->db->where(array('kd_po_jasa'=>$code,'spk_version_no'=>1))->count_all_results('tbpo_jasa_spk_archive')===1 && (int)$this->db->get_where('tbpo_jasa_spk',array('kd_po_jasa'=>$code))->row()->spk_version_no===2);
        $this->assert('done_purchase_change_records_pending_adjustment', (int)$this->db->where(array('kd_po_jasa'=>$code,'status'=>'MENUNGGU_PENYESUAIAN'))->count_all_results('tbpo_jasa_purchase_adjustment')===1);
        $pendingIds=array_map('intval',array_column($this->db->get_where('tbpo_jasa_biaya_aktual',array('kd_po_jasa'=>$code,'record_status'=>'MENUNGGU_PERSETUJUAN_REVISI'))->result_array(),'id_biaya_aktual'));
        $costChange=$this->M_PojasaSpkChange->submit($users['purchasing'],$code,'COST',array('cost_ids'=>$pendingIds,'approved_total'=>300000),'Persetujuan realisasi overbudget.',$this->token());
        $costOps=$costChange['success']?$this->M_PojasaSpkChange->decide($users['dirops'],$code,$costChange['id_change'],'ACC','',$this->token()):array('success'=>false);
        $costDirector=$costOps['success']?$this->M_PojasaSpkChange->decide($users['director'],$code,$costChange['id_change'],'ACC','',$this->token()):array('success'=>false);
        $this->assert('cost_revision_acc_updates_budget_and_cost_status', $costChange['success'] && $costDirector['success'] && (float)$this->M_PojasaCore->get_request($code)->estimasi_disetujui===300000.0 && (int)$this->db->where_in('id_biaya_aktual',$pendingIds)->where('record_status','DISETUJUI_REVISI')->count_all_results('tbpo_jasa_biaya_aktual')===count($pendingIds));
        $normalCode='P8TESTNORMAL1';$this->createRequest($normalCode,$users['pic'],'FINANCE');$normalChange=$this->M_PojasaSpkChange->submit($users['purchasing'],$normalCode,'DATA',array('tgl_mulai_pekerjaan'=>'2026-09-13','tgl_selesai_pekerjaan'=>'2026-09-30'),'Perubahan non khusus.',$this->token());
        $this->assert('non_special_department_skips_dirops', $normalChange['success'] && $normalChange['status']==='MENUNGGU_DIREKTUR');

        $this->db->trans_rollback();
        $this->assert('phase8_transaction_rolled_back_cleanly', $before===$this->counts() && (int)$this->db->where('kd_po_jasa',$code)->count_all_results('tbpo_jasa_request')===0);
    }

    private function createRequest($code, $pic, $department)
    {
        $spkNo='SPKJ-'.$code;
        $this->db->insert('tbpo_jasa_request', array('kd_po_jasa'=>$code,'kd_user'=>$pic['kode_user'],'nm_user'=>$pic['nama_user'],'departemen'=>$department,'tgl_request'=>'2026-09-11','tgl_target'=>'2026-09-30','tgl_mulai_pekerjaan'=>'2026-09-11','tgl_selesai_pekerjaan'=>'2026-09-30','lokasi_pekerjaan'=>'Test','tujuan_pekerjaan'=>'Phase 8','estimasi_total_jasa'=>10000,'estimasi_total_bahan'=>200000,'estimasi_total'=>210000,'estimasi_disetujui'=>210000,'status'=>'SPK_TERBIT','status_version'=>1,'revision_no'=>1,'no_spk'=>$spkNo,'acc_with_kadep'=>'KADEP-P8-IT'));
        $this->db->insert('tbpo_jasa_scope', array('kd_po_jasa'=>$code,'revision_no'=>1,'line_no'=>1,'jenis_scope'=>'JASA','nama_scope'=>'Scope P8','deskripsi'=>'Scope test','qty'=>1,'satuan'=>'LS','harga_estimasi'=>10000,'total_estimasi'=>10000,'is_active'=>1)); $scope=(int)$this->db->insert_id();
        $stockId=$this->stockId(); $ids=array(); foreach (array(array(1,10,10000),array(2,4,25000)) as $m) { $this->db->insert('tbpo_jasa_material', array('kd_po_jasa'=>$code,'revision_no'=>1,'line_no'=>$m[0],'nama_material'=>'Material P8 '.$m[0],'deskripsi'=>'Test','qty_kebutuhan'=>$m[1],'satuan'=>'PCS','harga_estimasi'=>$m[2],'total_estimasi'=>$m[1]*$m[2],'sumber_material'=>'PEMBELIAN','id_brg_nk'=>$stockId,'created_by'=>$pic['id_user'],'is_active'=>1)); $ids[]=(int)$this->db->insert_id(); }
        $request=$this->M_PojasaCore->get_request($code); $this->db->insert('tbpo_jasa_spk',array('kd_po_jasa'=>$code,'no_spk'=>$spkNo,'revision_no'=>1,'spk_version_no'=>1,'issued_by'=>$pic['id_user'],'issued_at'=>'2026-09-11 08:00:00','snapshot_json'=>json_encode(array('request'=>(array)$request)),'template_version'=>'DRAFT-SPK-COMPAT-V1','document_status'=>'ISSUED'));
        return array($scope,$ids[0],$ids[1]);
    }
    private function draft($code,$material,$qty,$price,$user){$this->db->insert('tbpo_jasa_draft_pembelian',array('kd_po_jasa'=>$code,'revision_no'=>1,'id_material'=>$material,'qty'=>$qty,'harga_estimasi'=>$price,'keterangan'=>'P8','status_draft'=>'DRAFT','version'=>1,'idempotency_token'=>$this->token(),'created_by'=>$user['id_user']));}
    private function document(){return array('jenis_dokumen'=>'BUKTI_BIAYA_AKTUAL','keterangan'=>'P8','file_path'=>'images/pojasa/P8-test.txt','file_original'=>'P8-test.txt','mime_type'=>'text/plain','file_size_bytes'=>2,'sha256'=>hash('sha256','p8'));}
    private function stockId(){ $row=$this->db->query('SELECT id_brg_nk FROM tbpo_barang_nk ORDER BY id_brg_nk LIMIT 1')->row(); return $row?(int)$row->id_brg_nk:1; }
    private function users(){ $p=$this->db->query("SELECT * FROM tbpo_user WHERE aksess_lv=2 AND UPPER(TRIM(departement))='PURCHASING' LIMIT 1")->row_array();$d=$this->db->query("SELECT * FROM tbpo_user WHERE aksess_lv=3 AND UPPER(TRIM(departement))='DIREKTUR' LIMIT 1")->row_array();$o=$this->db->query("SELECT * FROM tbpo_user WHERE aksess_lv=6 LIMIT 1")->row_array();$pic=$this->db->query('SELECT * FROM tbpo_user WHERE aksess_lv=4 LIMIT 1')->row_array();if(!$p||!$d||!$o||!$pic)return false;return array('purchasing'=>$this->context($p),'director'=>$this->context($d),'dirops'=>$this->context($o),'pic'=>$this->context($pic));}
    private function context($u){return array('id_user'=>(int)$u['id_user'],'kode_user'=>$u['kode_user'],'nama_user'=>$u['nama_user'],'departemen'=>pojasa_normalize_department($u['departement']),'role'=>pojasa_role_from_context($u['aksess_lv'],$u['departement']));}
    private function token(){return sprintf('80000000-0000-4000-8000-%012d',$this->sequence++);}
    private function counts(){return array('request'=>(int)$this->db->count_all('tbpo_jasa_request'),'po'=>(int)$this->db->count_all('tbpo_po_nk'),'detail'=>(int)$this->db->count_all('tbpo_detail_po_nk'),'submission'=>(int)$this->db->count_all('tbpo_jasa_purchase_submission'),'receipt'=>(int)$this->db->count_all('tbpo_jasa_purchase_receipt'),'transaction'=>(int)$this->db->count_all('tbpo_transaksi'),'cost'=>(int)$this->db->count_all('tbpo_jasa_biaya_aktual'),'change'=>(int)$this->db->count_all('tbpo_jasa_spk_change'),'archive'=>(int)$this->db->count_all('tbpo_jasa_spk_archive'));}
    private function assert($name,$passed,$detail=null){$row=array('name'=>$name,'passed'=>(bool)$passed);if(!$passed&&$detail!==null)$row['detail']=$detail;$this->checks[]=$row;}
}
