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
        $this->db->order_by('id_user', 'ASC');
        return $this->db->get('tbpo_user')->result();
    }

    public function getById($iduser)
    {
        $this->db->where('id_user', $iduser);
        return $this->db->get('tbpo_user')->row();
    }

    public function usernameExists($username, $exclude_id = null)
    {
        $this->db->where('username', $username);
        if ($exclude_id !== null) {
            $this->db->where('id_user !=', $exclude_id);
        }

        return $this->db->get('tbpo_user')->num_rows() > 0;
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

    public function deleteUser($iduser)
    {
        $this->db->where('id_user', $iduser);
        return $this->db->delete('tbpo_user');
    }

    public function upsertAdmin($data)
    {
        if ($this->usernameExists($data['username'])) {
            $this->db->where('username', $data['username']);
            return $this->db->update('tbpo_user', $data);
        }

        return $this->db->insert('tbpo_user', $data);
    }
    public function getInfoUser($kduser)
    {
        $this->db->select('*');
        $this->db->from('tbpo_user');
        $this->db->where('kode_user', $kduser);
        $query = $this->db->get();
        return $query;
    }
    public function editPassword($kd_user, $data)
    {
        $this->db->where('kode_user', $kd_user);
        return $this->db->update('tbpo_user', $data);
    }
}
