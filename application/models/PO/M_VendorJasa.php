<?php
defined('BASEPATH') or exit('No direct script access allowed');

class M_VendorJasa extends CI_Model
{
    public function listing($term = '')
    {
        $this->db->select('id_vendor_jasa,kd_vendor_jasa,nama_vendor,alamat_vendor,no_telpon,nama_pic,email,no_fax,deskripsi_vendor,version');
        $this->db->where(array('deleted_at' => null, 'status_vendor' => 'AKTIF'));
        if ($term !== '') $this->db->like('nama_vendor', $term);
        return $this->db->order_by('nama_vendor')->get('tbpo_vendor_jasa')->result_array();
    }

    public function save($context, $id, $version, $data, $delete = false)
    {
        $this->load->model('PO/M_PojasaCore');
        $this->db->trans_begin();
        if ($id > 0) {
            $current = $this->db->query('SELECT * FROM tbpo_vendor_jasa WHERE id_vendor_jasa=? AND deleted_at IS NULL FOR UPDATE', array($id))->row();
            if (!$current || (int) $current->version !== $version) {
                $this->db->trans_rollback();
                return array('success' => false, 'code' => 'CONCURRENT_UPDATE');
            }
            if ($delete) $data = array('deleted_at' => date('Y-m-d H:i:s'), 'deleted_by' => (int) $context['id_user'], 'status_vendor' => 'NONAKTIF');
            $data['version'] = $version + 1;
            $data['updated_by'] = $context['kode_user'];
            $this->db->where('id_vendor_jasa', $id)->update('tbpo_vendor_jasa', $data);
        } else {
            $code = $this->M_PojasaCore->generateAtomicNumber('VENDOR', 'ALL', 'VJ', 4, array(array('table' => 'tbpo_vendor_jasa', 'column' => 'kd_vendor_jasa')));
            if (!$code) { $this->db->trans_rollback(); return array('success' => false, 'code' => 'NUMBER_ERROR'); }
            $data += array('kd_vendor_jasa' => $code, 'created_by' => $context['kode_user'], 'created_name' => $context['nama_user'], 'status_vendor' => 'AKTIF');
            $this->db->insert('tbpo_vendor_jasa', $data);
            $id = (int) $this->db->insert_id();
        }
        if (!$this->db->trans_status()) { $this->db->trans_rollback(); return array('success' => false, 'code' => 'DATABASE_ERROR'); }
        $this->db->trans_commit();
        return array('success' => true, 'code' => 'SAVED', 'id' => $id);
    }
}
