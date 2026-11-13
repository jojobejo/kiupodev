<?php
defined('BASEPATH') or exit('No direct script access allowed');
/**
 *
 */
class M_NoteSeting extends CI_Model
{
    function __construct()
    {
        parent::__construct();
    }

    public function getAll()
    {
        $this->db->select('*');
        $this->db->from('tbpo_set_note');
        $query = $this->db->get();
        return $query;
    }

    public function get_all_nt_setting()
    {
        return $this->db->get('tbpo_notetemplate')->result();
    }
    public function addPajak($data)
    {
        return $this->db->insert('tbpo_set_note', $data);
    }
    public function editPajak($idpajak, $data)
    {
        $this->db->where('id_set_note', $idpajak);
        return $this->db->update('tbpo_set_note', $data);
    }
    public function hapusPajak($id)
    {
        $this->db->where('id_set_note', $id);
        return $this->db->delete('tbpo_set_note');
    }
    public function generate_kd()
    {
        $cd1 = $this->db->query("SELECT MAX(RIGHT(kd_nt_template,4)) AS kd_max FROM tbpo_notetemplate WHERE DATE(create_at)=CURDATE()");
        $kd1 = "";
        if ($cd1->num_rows() > 0) {
            foreach ($cd1->result() as $k) {
                $tmp = ((int)$k->kd_max) + 1;
                $kd1 = sprintf("%04s", $tmp);
            }
        } else {
            $kd1 = "0001";
        }

        date_default_timezone_set('Asia/Jakarta');
        $kdnk1 = 'KDNT' . date('dmy') . $kd1;
        return $kdnk1;
    }
    public function insert_nm_template($data)
    {
        return $this->db->insert('tbpo_notetemplate', $data);
    }
    public function edit_nm_template($idpajak, $data)
    {
        $this->db->where('id_nt_template ', $idpajak);
        return $this->db->update('tbpo_notetemplate', $data);
    }
    public function hapus_nm_template($id)
    {
        $this->db->where('id_nt_tmplate', $id);
        return $this->db->delete('tbpo_notetemplate');
    }
    public function get_nt_setting($kd)
    {
        $this->db->select('*');
        $this->db->from('tbpo_notetemplate');
        $this->db->where('kd_nt_template', $kd);
        $query = $this->db->get()->result();
        return $query;
    }
}
