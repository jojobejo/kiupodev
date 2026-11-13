<?php
defined('BASEPATH') or exit('No direct script access allowed');
/**
 *
 */
class M_Suplier extends CI_Model
{
    function __construct()
    {
        parent::__construct();
    }

    public function getAll()
    {
        return $this->db->get('tbpo_suplier')->result();
    }

    public function addSuplier($data)
    {
        return $this->db->insert('tbpo_suplier', $data);
    }

    public function editSuplier($iduser, $data)
    {
        $this->db->where('id_suplier', $iduser);
        return $this->db->update('tbpo_suplier', $data);
    }
}
