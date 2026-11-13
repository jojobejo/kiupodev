<?php
defined('BASEPATH') or exit('No direct script access allowed');
/**
 *
 */
class M_TaxSetting extends CI_Model
{
    function __construct()
    {
        parent::__construct();
    }

    public function getAll()
    {
        $this->db->select('*');
        $this->db->from('tbpo_set_tax');
        $this->db->order_by('nm_tax', 'ASC');
        $query = $this->db->get();
        return $query;
    }

    public function addPajak($data)
    {
        return $this->db->insert('tbpo_set_tax', $data);
    }

    public function editPajak($idpajak, $data)
    {
        $this->db->where('id_tax', $idpajak);
        return $this->db->update('tbpo_set_tax', $data);
    }

    public function hapusPajak($id)
    {
        $this->db->where('id_tax', $id);
        return $this->db->delete('tbpo_set_tax');
    }
}
