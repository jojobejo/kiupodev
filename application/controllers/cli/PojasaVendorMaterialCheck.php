<?php
defined('BASEPATH') or exit('No direct script access allowed');

// Reversible integration checks; every fixture is rolled back.
class PojasaVendorMaterialCheck extends CI_Controller
{
    public function index()
    {
        if (!is_cli()) { show_404(); return; }
        $this->load->model('PO/M_VendorJasa');
        $this->load->model('PO/M_PojasaWorkflow');
        $this->load->model('PO/M_PojasaPurchasing');
        $user = $this->db->get('tbpo_user', 1)->row();
        $request = $this->db->get('tbpo_jasa_request', 1)->row();
        if (!$user || !$request) { echo "Missing integration fixtures\n"; exit(1); }
        $context = array('id_user' => (int) $user->id_user, 'kode_user' => $user->kode_user, 'nama_user' => $user->nama_user);
        $this->db->trans_begin();
        try {
            $name = 'CHECK VENDOR ' . bin2hex(random_bytes(5));
            $vendor = array('nama_vendor'=>$name,'nama_vendor_normalized'=>$name,'alamat_vendor'=>'Alamat uji','no_telpon'=>'021123','nama_pic'=>'Kontak uji','email'=>'vendor@example.com','no_fax'=>'021456','deskripsi_vendor'=>'Jasa instalasi dan perawatan <contoh>');
            $created = $this->M_VendorJasa->save($context, 0, 0, $vendor);
            $this->check($created['success'], 'vendor create');
            $items = $this->M_VendorJasa->listing($name);
            $this->check(count($items)===1 && $items[0]['no_fax']==='021456' && $items[0]['deskripsi_vendor']===$vendor['deskripsi_vendor'], 'vendor list and fax');
            $updated = $this->M_VendorJasa->save($context, $created['id'], 1, array('nama_pic'=>'Kontak baru','deskripsi_vendor'=>'Deskripsi diperbarui'));
            $this->check($updated['success'] && $this->M_VendorJasa->listing($name)[0]['deskripsi_vendor']==='Deskripsi diperbarui', 'vendor update and description');
            $conflict = $this->M_VendorJasa->save($context, $created['id'], 1, array('nama_pic'=>'stale'));
            $this->check(!$conflict['success'], 'vendor stale version rejected');
            $deleted = $this->M_VendorJasa->save($context, $created['id'], 2, array(), true);
            $this->check($deleted['success'] && !$this->M_VendorJasa->listing($name), 'vendor delete');
            $stocks = $this->M_PojasaPurchasing->search_stock('', 1);
            $this->check(!empty($stocks), 'stock fixture');
            $stock = $stocks[0];
            $method = new ReflectionMethod('M_PojasaWorkflow', 'saveReviewMaterial');
            $method->setAccessible(true);
            foreach (array(array('stock'=>(int)$stock['id_brg_nk'],'qty'=>(float)$stock['available_stock']+2,'expected'=>2), array('stock'=>null,'qty'=>3,'expected'=>3)) as $case) {
                $row = array('is_new'=>true,'nama_material'=>'CHECK MATERIAL','deskripsi'=>'Test rollback','qty'=>$case['qty'],'satuan'=>$stock['satuan'],'harga_pembanding'=>12500,'keterangan_purchasing'=>null,'id_brg_nk'=>$case['stock'],'reference_type'=>$case['stock']?'MASTER':'MANUAL');
                $method->invoke($this->M_PojasaWorkflow, $request->kd_po_jasa, (int)$request->revision_no, $row, $context);
                $material = $this->db->order_by('id_material','DESC')->get('tbpo_jasa_material',1)->row();
                $draft = $this->db->get_where('tbpo_jasa_draft_pembelian',array('id_material'=>$material->id_material))->row();
                $this->check($draft && abs((float)$draft->qty-$case['expected'])<0.001 && (float)$draft->harga_estimasi===12500.0, 'automatic shortage and manual draft price');
                $this->check((float)$draft->qty*(float)$draft->harga_estimasi===$case['expected']*12500.0, 'draft total');
            }
            $availableStock = $this->M_PojasaPurchasing->search_stock('', 30);
            foreach ($availableStock as $enough) {
                if ((float)$enough['available_stock'] < 1) continue;
                $row['id_brg_nk'] = (int)$enough['id_brg_nk']; $row['reference_type'] = 'MASTER'; $row['qty'] = 1;
                $method->invoke($this->M_PojasaWorkflow, $request->kd_po_jasa, (int)$request->revision_no, $row, $context);
                $material = $this->db->order_by('id_material','DESC')->get('tbpo_jasa_material',1)->row();
                $this->check(!$this->db->get_where('tbpo_jasa_draft_pembelian', array('id_material'=>$material->id_material))->row(), 'sufficient stock does not create draft');
                break;
            }
            $this->check($this->db->trans_status(), 'database transaction');
            $this->db->trans_rollback();
            echo "PASS vendor CRUD/version guard, stock shortage, manual material, unit price and totals (rolled back)\n";
        } catch (Throwable $e) {
            $this->db->trans_rollback();
            echo 'FAIL: '.$e->getMessage()."\n"; exit(1);
        }
    }
    private function check($condition, $label) { if (!$condition) throw new RuntimeException($label); }
}
