<?php
defined('BASEPATH') or exit('No direct script access allowed');

class M_PojasaPickup extends CI_Model
{
    public function ready()
    {
        return $this->db->table_exists('tbpo_jasa_pickup_request') && $this->db->field_exists('received_at','tbpo_jasa_pickup_request');
    }

    public function state($requestCode)
    {
        if (!$this->ready()) return array('ready'=>false,'eligible'=>false,'pickup'=>null,'po'=>null);
        $request=$this->db->get_where('tbpo_jasa_request',array('kd_po_jasa'=>$requestCode))->row();
        $po=$this->db->order_by('id_submission','DESC')->get_where('tbpo_jasa_purchase_submission',array('kd_po_jasa'=>$requestCode))->row_array();
        if($po) { $native=$this->db->get_where('tbpo_po_nk',array('id_po_nk'=>$po['id_po_nk']))->row(); $po['native_status']=$native?$native->status:$po['status']; }
        $pickup=$this->db->order_by('id_pickup_request','DESC')->get_where('tbpo_jasa_pickup_request',array('kd_po_jasa'=>$requestCode))->row_array();
        $materials=$this->db->get_where('tbpo_jasa_material',array('kd_po_jasa'=>$requestCode,'is_active'=>1))->result();
        $unmapped=0;$unplanned=0;$remaining=0;
        foreach($materials as $m) {
            if(!$m->id_brg_nk) $unmapped++;
            $a=$this->db->query("SELECT COALESCE(SUM(qty_allocation-qty_released),0) planned, COALESCE(SUM(qty_received),0) received FROM tbpo_jasa_stock_allocation WHERE id_material=? AND status_allocation!='RELEASED'",array($m->id_material))->row();
            $b=$this->db->query("SELECT COALESCE(SUM(d.qty_received),0) qty FROM tbpo_jasa_purchase_submission_detail d JOIN tbpo_jasa_purchase_submission s ON s.id_submission=d.id_submission WHERE s.kd_po_jasa=? AND d.id_material=?",array($requestCode,$m->id_material))->row();
            if((float)$a->planned+(float)$b->qty+0.000001<(float)$m->qty_kebutuhan) $unplanned++;
            $remaining+=max(0,(float)$m->qty_kebutuhan-(float)$a->received);
        }
        $pending=(int)$this->db->query("SELECT COUNT(*) total FROM tbpo_jasa_purchase_submission_detail d JOIN tbpo_jasa_purchase_submission s ON s.id_submission=d.id_submission WHERE s.kd_po_jasa=? AND d.fulfillment_status!='ON_HAND'",array($requestCode))->row()->total;
        $eligible=$request && empty($request->deleted_at) && in_array($request->status,array('SPK_TERBIT','ON_PROGRESS','SELESAI'),true) && $remaining>0 && !$unmapped && !$unplanned && !$pending && (!$pickup || $pickup['status']==='DITOLAK_KADEP');
        return array('ready'=>true,'po'=>$po,'pickup'=>$pickup,'unmapped_count'=>$unmapped,'unplanned_count'=>$unplanned,'pending_purchase_count'=>$pending,'eligible'=>$eligible);
    }

    private function lockRequest($code)
    {
        return $this->db->query('SELECT * FROM tbpo_jasa_request WHERE kd_po_jasa=? FOR UPDATE',array($code))->row();
    }
    private function abort($code)
    {
        $this->db->trans_rollback();return array('success'=>false,'code'=>$code);
    }
    private function finish($code,$id)
    {
        if(!$this->db->trans_status()) return $this->abort('DATABASE_ERROR');
        $this->db->trans_commit();return array('success'=>true,'code'=>$code,'id'=>$id);
    }
    public function request($context,$requestCode,$token)
    {
        if(!$this->ready()) return array('success'=>false,'code'=>'SCHEMA_NOT_READY');
        $this->db->trans_begin();$request=$this->lockRequest($requestCode);
        if(!$request || $context['role']!=='PIC' || $request->kd_user!==$context['kode_user']) return $this->abort('FORBIDDEN');
        $old=$this->db->get_where('tbpo_jasa_pickup_request',array('idempotency_token'=>$token))->row();
        if($old) {
            if($old->kd_po_jasa!==$requestCode || (int)$old->requested_by!==(int)$context['id_user']) return $this->abort('IDEMPOTENCY_CONFLICT');
            return $this->finish('IDEMPOTENT_REPLAY',(int)$old->id_pickup_request);
        }
        $pending=$this->db->where(array('kd_po_jasa'=>$requestCode,'status'=>'MENUNGGU'))->count_all_results('tbpo_jasa_spk_revision');
        $pending+=$this->db->where('kd_po_jasa',$requestCode)->where_in('status',array('MENUNGGU_DIROPS','MENUNGGU_DIREKTUR'))->count_all_results('tbpo_jasa_spk_change');
        if($pending) return $this->abort('REVISION_PENDING');
        $state=$this->state($requestCode);
        if(!$state['eligible']) return $this->abort('MATERIAL_NOT_READY');
        $this->db->insert('tbpo_jasa_pickup_request',array('kd_po_jasa'=>$requestCode,'requested_by'=>(int)$context['id_user'],'idempotency_token'=>$token));
        $id=(int)$this->db->insert_id();
        foreach($this->db->get_where('tbpo_jasa_material',array('kd_po_jasa'=>$requestCode,'is_active'=>1))->result() as $m) {
            $qty=$this->db->query('SELECT COALESCE(SUM(qty_received),0) qty FROM tbpo_jasa_stock_allocation WHERE id_material=?',array($m->id_material))->row()->qty;
            if((float)$m->qty_kebutuhan>(float)$qty) $this->db->insert('tbpo_jasa_pickup_detail',array('id_pickup_request'=>$id,'id_material'=>$m->id_material,'qty'=>(float)$m->qty_kebutuhan-(float)$qty));
        }
        $this->notifyDepartmentKadep($request,'PENGAMBILAN_MENUNGGU_KADEP','Approval pengambilan barang','PIC mengajukan pengambilan material untuk '.$requestCode.'.');
        return $this->finish('REQUESTED',$id);
    }
    public function decide($context,$requestCode,$action,$note)
    {
        $this->db->trans_begin();$request=$this->lockRequest($requestCode);
        if(!$request || $context['role']!=='KADEP' || strtoupper(trim($context['departemen']))!==strtoupper(trim($request->departemen))) return $this->abort('FORBIDDEN');
        if(mb_strlen($note)>5000 || !in_array($action,array('ACC','REJECT'),true) || ($action==='REJECT' && trim($note)==='')) return $this->abort('NOTE_REQUIRED');
        if(!empty($request->deleted_at) || !in_array($request->status,array('SPK_TERBIT','ON_PROGRESS','SELESAI'),true)) return $this->abort('INVALID_STATUS');
        $p=$this->db->order_by('id_pickup_request','DESC')->get_where('tbpo_jasa_pickup_request',array('kd_po_jasa'=>$requestCode))->row();
        if(!$p || $p->status!=='MENUNGGU_ACC_KADEP') return $this->abort('PICKUP_NOT_PENDING');
        $status=$action==='ACC'?'MENUNGGU_PERSIAPAN_PURCHASING':'DITOLAK_KADEP';
        $this->db->where('id_pickup_request',$p->id_pickup_request)->update('tbpo_jasa_pickup_request',array('status'=>$status,'approved_by'=>(int)$context['id_user'],'approved_at'=>date('Y-m-d H:i:s'),'approval_note'=>$note));
        if($action==='ACC') $this->notifyPurchasing($requestCode,'PENGAMBILAN_DISETUJUI','Siapkan barang untuk PIC','Kadep telah menyetujui pengambilan material untuk '.$requestCode.'.');
        return $this->finish($status,(int)$p->id_pickup_request);
    }
    public function prepare($context,$requestCode,$note)
    {
        if(mb_strlen($note)>5000) return array('success'=>false,'code'=>'VALIDATION_ERROR');
        $this->db->trans_begin();$request=$this->lockRequest($requestCode);
        if(!$request || !in_array($context['role'],array('PURCHASING','ADMIN'),true)) return $this->abort('FORBIDDEN');
        if(!empty($request->deleted_at) || !in_array($request->status,array('SPK_TERBIT','ON_PROGRESS','SELESAI'),true)) return $this->abort('INVALID_STATUS');
        $p=$this->db->order_by('id_pickup_request','DESC')->get_where('tbpo_jasa_pickup_request',array('kd_po_jasa'=>$requestCode))->row();
        if(!$p || $p->status!=='MENUNGGU_PERSIAPAN_PURCHASING') return $this->abort('PICKUP_NOT_PENDING');
        $state=$this->state($requestCode);
        if($state['unmapped_count'] || $state['unplanned_count'] || $state['pending_purchase_count']) return $this->abort('MATERIAL_NOT_READY');
        // Purchased materials become reservations too; physical stock is posted only when PIC confirms.
        foreach($this->db->order_by('id_brg_nk','ASC')->get_where('tbpo_jasa_material',array('kd_po_jasa'=>$requestCode,'is_active'=>1))->result() as $m) {
            $b=$this->db->query('SELECT * FROM tbpo_barang_nk WHERE id_brg_nk=? FOR UPDATE',array($m->id_brg_nk))->row();
            if(!$b) return $this->abort('MATERIAL_NOT_READY');
            $a=$this->db->query("SELECT COALESCE(SUM(qty_allocation-qty_released),0) qty FROM tbpo_jasa_stock_allocation WHERE id_material=? AND status_allocation!='RELEASED'",array($m->id_material))->row();
            $need=max(0,(float)$m->qty_kebutuhan-(float)$a->qty);
            $stock=$this->db->query("SELECT COALESCE(SUM(CASE WHEN kd_akun IN ('11511','11513') THEN tr_qty ELSE -tr_qty END),0) qty FROM tbpo_transaksi WHERE kd_barang=? AND kd_akun IN ('11511','11512','11513','11514') FOR UPDATE",array($b->kd_barang))->row()->qty;
            $reserved=$this->db->query("SELECT COALESCE(SUM(qty_allocation-qty_received-qty_released),0) qty FROM tbpo_jasa_stock_allocation WHERE id_brg_nk=? AND status_allocation IN ('ACTIVE','PARTIAL') FOR UPDATE",array($m->id_brg_nk))->row()->qty;
            if((float)$stock-(float)$reserved+0.000001<$need) return $this->abort('STOCK_INSUFFICIENT');
            if($need>0) $this->db->insert('tbpo_jasa_stock_allocation',array('kd_po_jasa'=>$requestCode,'id_material'=>$m->id_material,'id_brg_nk'=>$m->id_brg_nk,'qty_allocation'=>$need,'allocated_by'=>(int)$context['id_user'],'allocation_token'=>substr(hash('sha256','pickup|'.$p->id_pickup_request.'|'.$m->id_material),0,36)));
        }
        $this->db->where('id_pickup_request',$p->id_pickup_request)->update('tbpo_jasa_pickup_request',array('status'=>'SIAP_DIAMBIL','prepared_by'=>(int)$context['id_user'],'prepared_at'=>date('Y-m-d H:i:s'),'preparation_note'=>$note));
        $owner=$this->db->get_where('tbpo_user',array('kode_user'=>$request->kd_user))->row();
        if($owner) $this->notice((int)$owner->id_user,$requestCode,'BARANG_SIAP_DIAMBIL','Barang siap diambil','Purchasing telah menyiapkan seluruh material untuk '.$requestCode.'.');
        return $this->finish('SIAP_DIAMBIL',(int)$p->id_pickup_request);
    }

    private function notifyDepartmentKadep($request, $event, $title, $message)
    {
        foreach ($this->db->query("SELECT id_user FROM tbpo_user WHERE aksess_lv=5 AND UPPER(TRIM(departement))=UPPER(?)", array($request->departemen))->result() as $user) $this->notice((int)$user->id_user, $request->kd_po_jasa, $event, $title, $message);
    }
    private function notifyPurchasing($requestCode, $event, $title, $message)
    {
        foreach ($this->db->query("SELECT id_user FROM tbpo_user WHERE aksess_lv=2 AND UPPER(TRIM(departement))='PURCHASING'")->result() as $user) $this->notice((int)$user->id_user, $requestCode, $event, $title, $message);
    }
    private function notice($userId, $requestCode, $event, $title, $message)
    {
        if ($this->db->table_exists('tbpo_jasa_notifikasi')) $this->db->insert('tbpo_jasa_notifikasi', array('kd_po_jasa'=>$requestCode,'event_type'=>$event,'recipient_user_id'=>$userId,'title'=>$title,'message'=>$message,'target_url'=>base_url('pojasa/pic/detail/' . $requestCode)));
    }
}
