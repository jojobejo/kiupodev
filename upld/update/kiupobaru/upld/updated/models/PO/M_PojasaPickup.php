<?php
defined('BASEPATH') or exit('No direct script access allowed');

class M_PojasaPickup extends CI_Model
{
    public function ready()
    {
        return $this->db->table_exists('tbpo_jasa_pickup_request') && $this->db->table_exists('tbpo_jasa_pickup_detail');
    }

    public function state($requestCode)
    {
        if (!$this->ready()) return array('ready' => false, 'reason' => 'SCHEMA_NOT_READY', 'pickup' => null, 'po' => null);
        $po = $this->db->order_by('id_submission', 'DESC')->get_where('tbpo_jasa_purchase_submission', array('kd_po_jasa' => $requestCode))->row_array();
        if ($po) {
            $native = $this->db->select('status')->get_where('tbpo_po_nk', array('id_po_nk' => $po['id_po_nk']))->row();
            $po['native_status'] = $native ? $native->status : $po['status'];
        }
        $pickup = $this->db->order_by('id_pickup_request', 'DESC')->get_where('tbpo_jasa_pickup_request', array('kd_po_jasa' => $requestCode))->row_array();
        $unmapped = (int) $this->db->where(array('kd_po_jasa' => $requestCode, 'is_active' => 1))->where('(id_brg_nk IS NULL OR id_brg_nk=0)', null, false)->count_all_results('tbpo_jasa_material');
        $pendingPurchase = (int) $this->db->query("SELECT COUNT(*) total FROM tbpo_jasa_purchase_submission_detail d JOIN tbpo_jasa_purchase_submission s ON s.id_submission=d.id_submission WHERE s.kd_po_jasa=? AND d.fulfillment_status!='ON_HAND'", array($requestCode))->row()->total;
        $unplanned = (int) $this->db->query("SELECT COUNT(*) total FROM tbpo_jasa_material m WHERE m.kd_po_jasa=? AND m.is_active=1 AND NOT EXISTS (SELECT 1 FROM tbpo_jasa_stock_allocation a WHERE a.id_material=m.id_material AND a.status_allocation IN ('ACTIVE','PARTIAL','RECEIVED')) AND NOT EXISTS (SELECT 1 FROM tbpo_jasa_purchase_submission_detail d WHERE d.id_material=m.id_material AND d.fulfillment_status='ON_HAND')", array($requestCode))->row()->total;
        $eligible = $unmapped === 0 && $pendingPurchase === 0 && $unplanned === 0 && !$pickup;
        return array('ready' => true, 'po' => $po, 'pickup' => $pickup, 'unmapped_count' => $unmapped, 'pending_purchase_count' => $pendingPurchase, 'unplanned_count' => $unplanned, 'eligible' => $eligible);
    }

    public function request($context, $requestCode, $token)
    {
        $state = $this->state($requestCode);
        if (!$state['ready'] || !$state['eligible']) return array('success' => false, 'code' => 'MATERIAL_NOT_READY', 'state' => $state);
        $request = $this->db->get_where('tbpo_jasa_request', array('kd_po_jasa' => $requestCode))->row();
        if (!$request || $request->kd_user !== $context['kode_user']) return array('success' => false, 'code' => 'FORBIDDEN');
        $this->db->insert('tbpo_jasa_pickup_request', array('kd_po_jasa' => $requestCode, 'requested_by' => (int)$context['id_user'], 'idempotency_token' => $token));
        $id = (int)$this->db->insert_id();
        foreach ($this->db->get_where('tbpo_jasa_material', array('kd_po_jasa' => $requestCode, 'is_active' => 1))->result() as $material) {
            $this->db->insert('tbpo_jasa_pickup_detail', array('id_pickup_request' => $id, 'id_material' => $material->id_material, 'qty' => $material->qty_kebutuhan));
        }
        $this->notifyDepartmentKadep($request, 'PENGAMBILAN_MENUNGGU_KADEP', 'Approval pengambilan barang', 'PIC mengajukan pengambilan material untuk ' . $requestCode . '.');
        return array('success' => true, 'code' => 'REQUESTED', 'id' => $id);
    }

    public function decide($context, $requestCode, $action, $note)
    {
        $request = $this->db->get_where('tbpo_jasa_request', array('kd_po_jasa' => $requestCode))->row();
        $pickup = $this->db->order_by('id_pickup_request', 'DESC')->get_where('tbpo_jasa_pickup_request', array('kd_po_jasa' => $requestCode, 'status' => 'MENUNGGU_ACC_KADEP'))->row();
        if (!$request || !$pickup || $context['role'] !== 'KADEP' || strtoupper($context['departemen']) !== strtoupper($request->departemen)) return array('success' => false, 'code' => 'FORBIDDEN');
        $status = $action === 'ACC' ? 'MENUNGGU_PERSIAPAN_PURCHASING' : 'DITOLAK_KADEP';
        $this->db->where('id_pickup_request', $pickup->id_pickup_request)->update('tbpo_jasa_pickup_request', array('status' => $status, 'approved_by' => (int)$context['id_user'], 'approved_at' => date('Y-m-d H:i:s'), 'approval_note' => $note));
        if ($action === 'ACC') $this->notifyPurchasing($requestCode, 'PENGAMBILAN_DISETUJUI', 'Siapkan barang untuk PIC', 'Kadep telah menyetujui pengambilan material untuk ' . $requestCode . '.');
        return array('success' => true, 'code' => $status);
    }

    public function prepare($context, $requestCode, $note)
    {
        $pickup = $this->db->order_by('id_pickup_request', 'DESC')->get_where('tbpo_jasa_pickup_request', array('kd_po_jasa' => $requestCode, 'status' => 'MENUNGGU_PERSIAPAN_PURCHASING'))->row();
        if (!$pickup || !in_array($context['role'], array('PURCHASING', 'ADMIN'), true)) return array('success' => false, 'code' => 'FORBIDDEN');
        $this->db->where('id_pickup_request', $pickup->id_pickup_request)->update('tbpo_jasa_pickup_request', array('status' => 'SIAP_DIAMBIL', 'prepared_by' => (int)$context['id_user'], 'prepared_at' => date('Y-m-d H:i:s'), 'preparation_note' => $note));
        $request = $this->db->get_where('tbpo_jasa_request', array('kd_po_jasa' => $requestCode))->row();
        if ($request) $this->notice((int)$request->id_user, $requestCode, 'BARANG_SIAP_DIAMBIL', 'Barang siap diambil', 'Purchasing telah menyiapkan seluruh material untuk ' . $requestCode . '.');
        return array('success' => true, 'code' => 'SIAP_DIAMBIL');
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
