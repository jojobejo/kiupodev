<?php
defined('BASEPATH') or exit('No direct script access allowed');

/** BAST and invoice tracking for the canonical PO Jasa workflow. */
class M_PojasaSettlement extends CI_Model
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('PO/M_PojasaCore');
        $this->load->model('PO/M_PojasaWorkflow');
        $this->load->helper(array('pojasa_authorization','pojasa_status'));
    }

    public function schema_ready()
    {
        foreach (array('tbpo_jasa_bast'=>array('idempotency_token','payload_hash','processed_by','revision_no','spk_version_no'),
            'tbpo_jasa_payment'=>array('row_version','updated_by','updated_at'),
            'tbpo_jasa_payment_audit'=>array('idempotency_token','payload_hash','before_json','after_json'),
            'tbpo_jasa_evaluation'=>array('row_version','vendor_name_snapshot'),
            'tbpo_jasa_evaluation_audit'=>array('idempotency_token','payload_hash','before_json','after_json')) as $table=>$columns) {
            if (!$this->db->table_exists($table)) { return false; }
            foreach ($columns as $column) { if (!$this->db->field_exists($column,$table)) { return false; } }
        }
        return $this->M_PojasaCore->tables_ready();
    }

    public function get_state($context,$code)
    {
        $request=$this->M_PojasaCore->get_request($code);
        if (!$request || !$this->M_PojasaWorkflow->can_access_request($context,$request)) { return false; }
        $bast=$this->db->get_where('tbpo_jasa_bast',array('kd_po_jasa'=>$code))->row();
        $payments=$this->db->order_by('id_payment_jasa','DESC')->get_where('tbpo_jasa_payment',array('kd_po_jasa'=>$code))->result();
        $summary=$this->db->query('SELECT COALESCE(SUM(nominal_tagihan),0) total_tagihan,COALESCE(SUM(nominal_bayar),0) total_bayar,COALESCE(SUM(nominal_tagihan-nominal_bayar),0) sisa_bayar FROM tbpo_jasa_payment WHERE kd_po_jasa=?',array($code))->row();
        return array('request'=>$request,'bast'=>$bast,'payments'=>$payments,'summary'=>$summary,'evaluation'=>$this->db->get_where('tbpo_jasa_evaluation',array('kd_po_jasa'=>$code))->row(),
            'can_manage'=>in_array($context['role'],array('ADMIN','PURCHASING'),true),
            'audit'=>$this->db->select('a.*,u.nama_user AS actor_name')->from('tbpo_jasa_payment_audit a')
                ->join('tbpo_user u','u.id_user=a.actor_id','left')->where('a.kd_po_jasa',$code)
                ->order_by('a.id_payment_audit','DESC')->get()->result());
    }

    public function save_evaluation($context,$code,$input)
    {
        if (!$this->manager($context)) return $this->error('FORBIDDEN');
        if (!$this->db->table_exists('tbpo_jasa_evaluation_audit')) return $this->error('SCHEMA_NOT_READY');
        $token=(string)$input['idempotency_token'];$scores=array();
        foreach(array('kualitas_score','ketepatan_waktu_score','biaya_score') as $field) {
            $value=(string)$input[$field];
            if(!preg_match('/^[1-5](?:\.[0-9]{1,2})?$/D',$value) || (float)$value>5) return $this->error('VALIDATION_ERROR');
            $scores[$field]=round((float)$value,2);
        }
        $note=trim((string)$input['catatan_evaluasi']);
        if(!$this->uuid($token) || mb_strlen($note)>10000) return $this->error('VALIDATION_ERROR');
        $hash=hash('sha256',json_encode(array($code,(int)$context['id_user'],$scores,$note,(int)$input['row_version'])));
        $this->db->trans_begin();$r=$this->lockRequest($code);
        if(!$r || !empty($r->deleted_at)) return $this->abort('NOT_FOUND');
        $replay=$this->db->get_where('tbpo_jasa_evaluation_audit',array('idempotency_token'=>$token))->row();
        if($replay) {
            if($replay->kd_po_jasa!==$code || (int)$replay->actor_id!==(int)$context['id_user'] || !hash_equals($replay->payload_hash,$hash)) return $this->abort('IDEMPOTENCY_CONFLICT');
            return $this->commit(array('success'=>true,'code'=>'IDEMPOTENT_REPLAY'));
        }
        if($r->status!=='DITUTUP' || !$this->db->where('kd_po_jasa',$code)->count_all_results('tbpo_jasa_bast')) return $this->abort('BAST_REQUIRED');
        $old=$this->db->get_where('tbpo_jasa_evaluation',array('kd_po_jasa'=>$code))->row_array();
        if((int)$input['row_version']!==($old?(int)$old['row_version']:0)) return $this->abort('CONCURRENT_UPDATE');
        $vendor=$r->kd_vendor_jasa?$this->db->get_where('tbpo_vendor_jasa',array('kd_vendor_jasa'=>$r->kd_vendor_jasa))->row():null;
        $name=$vendor?$vendor->nama_vendor:$r->vendor_usulan;
        if(trim((string)$name)==='') return $this->abort('VENDOR_REQUIRED');
        $data=array_merge($scores,array('kd_po_jasa'=>$code,'kd_vendor_jasa'=>$vendor?$vendor->kd_vendor_jasa:null,
            'vendor_name_snapshot'=>mb_substr($name,0,180),'total_score'=>round(array_sum($scores)/3,2),
            'catatan_evaluasi'=>$note,'evaluated_by'=>$context['kode_user'],'evaluated_name'=>$context['nama_user'],
            'row_version'=>$old?(int)$old['row_version']+1:1));
        if($old) { $id=(int)$old['id_evaluation_jasa'];$this->db->where('id_evaluation_jasa',$id)->update('tbpo_jasa_evaluation',$data); }
        else { $this->db->insert('tbpo_jasa_evaluation',$data);$id=(int)$this->db->insert_id(); }
        $this->db->insert('tbpo_jasa_evaluation_audit',array('kd_po_jasa'=>$code,'id_evaluation_jasa'=>$id,'actor_id'=>(int)$context['id_user'],
            'idempotency_token'=>$token,'payload_hash'=>$hash,'before_json'=>$old?json_encode($old):null,'after_json'=>json_encode($data)));
        $this->M_PojasaCore->append_activity_log(array('kd_po_jasa'=>$code,'revision_no'=>(int)$r->revision_no,'event_type'=>'EVALUASI_VENDOR',
            'from_status'=>$r->status,'to_status'=>$r->status,'actor_id'=>(int)$context['id_user'],'actor_code'=>$context['kode_user'],
            'actor_name'=>$context['nama_user'],'actor_role'=>$context['role'],'actor_departemen'=>$context['departemen'],'metadata_json'=>json_encode($data)));
        return $this->commit(array('success'=>true,'code'=>'EVALUATION_SAVED','id'=>$id,'row_version'=>$data['row_version'],'total_score'=>$data['total_score']));
    }

    public function complete_bast($context,$code,$input)
    {
        if (!$this->schema_ready()) { return $this->error('SCHEMA_NOT_READY'); }
        if (!$this->manager($context)) { return $this->error('FORBIDDEN'); }
        $token=isset($input['idempotency_token'])?(string)$input['idempotency_token']:'';
        $number=trim((string)$input['no_bast']); $date=(string)$input['tgl_bast'];
        $recipient=trim((string)$input['penerima_pekerjaan']); $note=trim((string)$input['catatan_bast']);
        if (!$this->uuid($token) || $number==='' || mb_strlen($number)>80 || !$this->validDate($date)
            || $recipient==='' || mb_strlen($recipient)>120 || mb_strlen($note)>10000) { return $this->error('VALIDATION_ERROR'); }
        $hash=hash('sha256',json_encode(array($code,(int)$context['id_user'],$number,$date,$recipient,$note)));
        $this->db->trans_begin();
        $request=$this->lockRequest($code);
        if (!$request) { return $this->abort('NOT_FOUND'); }
        $replay=$this->db->get_where('tbpo_jasa_bast',array('idempotency_token'=>$token))->row();
        if ($replay) {
            if ($replay->kd_po_jasa!==$code || (int)$replay->processed_by!==(int)$context['id_user'] || !hash_equals((string)$replay->payload_hash,$hash)) { return $this->abort('IDEMPOTENCY_CONFLICT'); }
            return $this->commit(array('success'=>true,'code'=>'IDEMPOTENT_REPLAY','id'=>(int)$replay->id_bast_jasa));
        }
        if ($this->db->where('kd_po_jasa',$code)->count_all_results('tbpo_jasa_bast')) { return $this->abort('BAST_EXISTS'); }
        if ((int)$input['status_version']!==(int)$request->status_version) { return $this->abort('CONCURRENT_UPDATE'); }
        if ($request->status!=='SELESAI' || !$request->no_spk) { return $this->abort('WORK_NOT_COMPLETE'); }
        $progress=$this->db->order_by('tgl_progress','DESC')->order_by('id_progress_jasa','DESC')->get_where('tbpo_jasa_progress',array('kd_po_jasa'=>$code))->row();
        if (!$progress || $progress->status_progress!=='SELESAI' || (float)$progress->progress_persen!==100.0) { return $this->abort('WORK_NOT_COMPLETE'); }
        if ($date<$progress->tgl_progress) { return $this->abort('VALIDATION_ERROR'); }
        $revisions=$this->db->where(array('kd_po_jasa'=>$code,'status'=>'MENUNGGU'))->count_all_results('tbpo_jasa_spk_revision');
        $revisions+=$this->db->where('kd_po_jasa',$code)->where_in('status',array('MENUNGGU_DIROPS','MENUNGGU_DIREKTUR'))->count_all_results('tbpo_jasa_spk_change');
        if ($revisions) return $this->abort('REVISION_PENDING');
        $pending=(int)$this->db->query("SELECT COUNT(*) total FROM tbpo_jasa_purchase_submission_detail d JOIN tbpo_jasa_purchase_submission s ON s.id_submission=d.id_submission JOIN tbpo_po_nk p ON p.id_po_nk=s.id_po_nk WHERE d.kd_po_jasa=? AND p.status NOT IN ('PENGAJUAN DIBATALKAN','REJECT') AND d.qty_received<d.qty_submitted",array($code))->row()->total;
        $pending+=(int)$this->db->query("SELECT COUNT(*) total FROM tbpo_jasa_stock_allocation WHERE kd_po_jasa=? AND status_allocation IN ('ACTIVE','PARTIAL') AND qty_allocation-qty_received-qty_released>0.000001",array($code))->row()->total;
        $pending+=(int)$this->db->query('SELECT COUNT(*) total FROM tbpo_jasa_material m WHERE m.kd_po_jasa=? AND m.is_active=1 AND m.qty_kebutuhan>(SELECT COALESCE(SUM(a.qty_received),0) FROM tbpo_jasa_stock_allocation a WHERE a.id_material=m.id_material)+0.000001',array($code))->row()->total;
        if ($pending) { return $this->abort('MATERIAL_PENDING'); }
        $spk=$this->db->get_where('tbpo_jasa_spk',array('kd_po_jasa'=>$code,'no_spk'=>$request->no_spk))->row();
        if (!$spk) { return $this->abort('SPK_NOT_FOUND'); }
        $this->db->insert('tbpo_jasa_bast',array('kd_po_jasa'=>$code,'no_bast'=>$number,'tgl_bast'=>$date,
            'penerima_pekerjaan'=>$recipient,'catatan_bast'=>$note,'created_by'=>$context['kode_user'],
            'created_name'=>$context['nama_user'],'processed_by'=>(int)$context['id_user'],
            'revision_no'=>(int)$request->revision_no,'spk_version_no'=>max(1,(int)$spk->spk_version_no),
            'idempotency_token'=>$token,'payload_hash'=>$hash));
        $id=(int)$this->db->insert_id();
        $this->db->where(array('id_po_jasa'=>$request->id_po_jasa,'status_version'=>$request->status_version))->update('tbpo_jasa_request',array(
            'status'=>'DITUTUP','status_version'=>(int)$request->status_version+1,'closed_at'=>date('Y-m-d H:i:s'),'closed_by'=>(int)$context['id_user']));
        if ($this->db->affected_rows()!==1) { return $this->abort('CONCURRENT_UPDATE'); }
        $this->log($context,$request,'BAST_DITUTUP',array('id_bast'=>$id,'no_bast'=>$number),'DITUTUP');
        $this->notifyOwner($request,'BAST_DITUTUP','BAST selesai; pekerjaan ditutup oleh Purchasing/Admin.');
        return $this->commit(array('success'=>true,'code'=>'BAST_CLOSED','id'=>$id,'status'=>'DITUTUP'));
    }

    public function save_payment($context,$code,$input)
    {
        if (!$this->schema_ready()) { return $this->error('SCHEMA_NOT_READY'); }
        if (!$this->manager($context)) { return $this->error('FORBIDDEN'); }
        $token=isset($input['idempotency_token'])?(string)$input['idempotency_token']:'';
        $invoice=trim((string)$input['no_invoice']); $date=(string)$input['tgl_invoice']; $due=(string)$input['jatuh_tempo'];
        $bill=$this->money($input['nominal_tagihan']); $paid=$this->money($input['nominal_bayar']);
        $note=trim((string)$input['catatan_payment']); $id=(int)$input['id_payment_jasa']; $version=(int)$input['row_version'];
        if (!$this->uuid($token) || $invoice==='' || mb_strlen($invoice)>100 || !$this->validDate($date) || !$this->validDate($due)
            || $due<$date || !$bill || !$paid || $bill['cents']<=0 || $paid['cents']>$bill['cents'] || mb_strlen($note)>10000 || $id<0) { return $this->error('VALIDATION_ERROR'); }
        $status=$paid['cents']===0?'BELUM BAYAR':($paid['cents']===$bill['cents']?'LUNAS':'PARTIAL');
        $hash=hash('sha256',json_encode(array($code,(int)$context['id_user'],$invoice,$date,$due,$bill['value'],$paid['value'],$note,$id,$version)));
        $this->db->trans_begin();
        $request=$this->lockRequest($code);
        if (!$request) { return $this->abort('NOT_FOUND'); }
        $replay=$this->db->get_where('tbpo_jasa_payment_audit',array('idempotency_token'=>$token))->row();
        if ($replay) {
            if ($replay->kd_po_jasa!==$code || (int)$replay->actor_id!==(int)$context['id_user'] || !hash_equals((string)$replay->payload_hash,$hash)) { return $this->abort('IDEMPOTENCY_CONFLICT'); }
            return $this->commit(array('success'=>true,'code'=>'IDEMPOTENT_REPLAY','id'=>(int)$replay->id_payment_jasa));
        }
        if (!$request->no_spk || !in_array($request->status,array('SPK_TERBIT','ON_PROGRESS','SELESAI','DITUTUP'),true)) { return $this->abort('INVALID_STATUS'); }
        $before=null;
        if ($id) {
            $before=$this->db->get_where('tbpo_jasa_payment',array('id_payment_jasa'=>$id,'kd_po_jasa'=>$code))->row_array();
            if (!$before || $before['no_invoice']!==$invoice) { return $this->abort('INVOICE_INVALID'); }
            if ($version!==(int)$before['row_version']) { return $this->abort('CONCURRENT_UPDATE'); }
        } elseif ($this->db->where(array('kd_po_jasa'=>$code,'no_invoice'=>$invoice))->count_all_results('tbpo_jasa_payment')) {
            return $this->abort('INVOICE_EXISTS');
        }
        $row=array('kd_po_jasa'=>$code,'tgl_invoice'=>$date,'jatuh_tempo'=>$due,'no_invoice'=>$invoice,
            'nominal_tagihan'=>$bill['value'],'nominal_bayar'=>$paid['value'],'status_bayar'=>$status,'catatan_payment'=>$note);
        if ($before) {
            $row['row_version']=$version+1; $row['updated_by']=(int)$context['id_user']; $row['updated_at']=date('Y-m-d H:i:s');
            $this->db->where(array('id_payment_jasa'=>$id,'row_version'=>$version))->update('tbpo_jasa_payment',$row);
            if ($this->db->affected_rows()!==1) { return $this->abort('CONCURRENT_UPDATE'); }
        } else {
            $row['created_by']=$context['kode_user']; $row['created_name']=$context['nama_user'];
            $this->db->insert('tbpo_jasa_payment',$row); $id=(int)$this->db->insert_id();
        }
        $after=$this->db->get_where('tbpo_jasa_payment',array('id_payment_jasa'=>$id))->row_array();
        $this->db->insert('tbpo_jasa_payment_audit',array('id_payment_jasa'=>$id,'kd_po_jasa'=>$code,
            'event_type'=>$before?'UPDATED':'CREATED','before_json'=>$before?json_encode($before):null,
            'after_json'=>json_encode($after),'actor_id'=>(int)$context['id_user'],'idempotency_token'=>$token,'payload_hash'=>$hash));
        $this->log($context,$request,'PAYMENT_'.$status,array('id_payment_jasa'=>$id,'row_version'=>$after['row_version']));
        return $this->commit(array('success'=>true,'code'=>$before?'PAYMENT_UPDATED':'PAYMENT_CREATED','id'=>$id,'row_version'=>(int)$after['row_version'],'status_bayar'=>$status));
    }

    private function lockRequest($code)
    {
        return $this->db->query('SELECT * FROM tbpo_jasa_request WHERE kd_po_jasa=? AND deleted_at IS NULL FOR UPDATE',array($code))->row();
    }
    private function manager($context) { return in_array($context['role'],array('ADMIN','PURCHASING'),true); }
    private function uuid($s) { return (bool)preg_match('/^[a-f0-9]{8}-[a-f0-9]{4}-[1-5][a-f0-9]{3}-[89ab][a-f0-9]{3}-[a-f0-9]{12}$/i',$s); }
    private function validDate($s) { $d=DateTime::createFromFormat('!Y-m-d',$s); return $d && $d->format('Y-m-d')===$s; }
    private function money($raw)
    {
        $s=str_replace(' ','',trim((string)$raw));
        if (strpos($s,',')!==false) { $s=str_replace(',','.',str_replace('.','',$s)); }
        elseif (preg_match('/^\d{1,3}(\.\d{3})+$/',$s)) { $s=str_replace('.','',$s); }
        if (!preg_match('/^(\d{1,16})(?:\.(\d{1,2}))?$/',$s,$m)) { return false; }
        $whole=(int)$m[1]; $fraction=str_pad(isset($m[2])?$m[2]:'',2,'0');
        return array('value'=>$whole.'.'.$fraction,'cents'=>$whole*100+(int)$fraction);
    }
    private function error($code) { return array('success'=>false,'code'=>$code); }
    private function abort($code) { $this->db->trans_rollback(); return $this->error($code); }
    private function commit($result)
    {
        if (!$this->db->trans_status()) { return $this->abort('DATABASE_ERROR'); }
        $this->db->trans_commit(); return $result;
    }
    private function log($context,$request,$event,$metadata,$next=null)
    {
        $this->M_PojasaCore->append_activity_log(array('kd_po_jasa'=>$request->kd_po_jasa,'revision_no'=>$request->revision_no,
            'event_type'=>$event,'from_status'=>$request->status,'to_status'=>$next?:$request->status,
            'actor_id'=>(int)$context['id_user'],'actor_code'=>$context['kode_user'],'actor_name'=>$context['nama_user'],
            'actor_role'=>$context['role'],'actor_departemen'=>$context['departemen'],'metadata_json'=>json_encode($metadata)));
    }
    private function notifyOwner($request,$event,$message)
    {
        $user=$this->db->get_where('tbpo_user',array('kode_user'=>$request->kd_user))->row();
        if ($user) { $this->M_PojasaCore->create_notifications(array(array('kd_po_jasa'=>$request->kd_po_jasa,
            'event_type'=>$event,'recipient_user_id'=>$user->id_user,'title'=>'BAST PO Jasa','message'=>$message,
            'target_url'=>base_url('pojasa/settlement/'.$request->kd_po_jasa)))); }
    }
}
