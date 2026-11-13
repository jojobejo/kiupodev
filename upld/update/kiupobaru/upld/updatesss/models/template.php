<?php
defined('BASEPATH') or exit('No direct script access allowed');
/**
 *
 */
class M_User extends CI_Model
{
    function __construct()
    {
        parent::__construct();
    }

    public function getAll()
    {
        return $this->db->get('tbpo_user')->result();
    }

    public function addUser($data)
    {
        return $this->db->insert('tbpo_user', $data);
    }

    public function editUser($iduser, $data)
    {
        $this->db->where('id_user', $iduser);
        return $this->db->update('tbpo_user', $data);
    }
}
