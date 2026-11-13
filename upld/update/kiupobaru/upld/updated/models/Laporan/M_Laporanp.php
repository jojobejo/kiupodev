<?php
defined('BASEPATH') or exit('No direct script access allowed');
/**
 *
 */
class M_Laporanp extends CI_Model
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
    public function getdaterangelap($d1, $d2)
    {
        $this->db->select('c.nopo , a.tgl_transaksi , b.nama_user , b.departement , a.nama_barang , a.qty , a.hrg_satuan , a.total_harga ,a.kd_po_nk,c.status,a.deskripsi');
        $this->db->from('tbpo_detail_po_nk a');
        $this->db->join('tbpo_user b', 'b.kode_user = a.kd_user');
        $this->db->join('tbpo_po_nk c', 'c.kd_po_nk = a.kd_po_nk');
        $this->db->where('a.tgl_transaksi >=', $d1);
        $this->db->where('a.tgl_transaksi <=', $d2);
        $this->db->where('c.status =', 'DONE');
        $this->db->where('c.status =', 'DONE');
        $query = $this->db->get();
        return $query;
    }

    public function getdaterangelaptr($tgl1, $tgl2)
    {
        $this->db->select('a.kd_po_nk AS kdpo,d.nama_user AS inputer,a.kd_akun AS jn_transaksi, a.tgl_transaksi, c.departement, c.nama_user, b.nama_barang, a.keterangan, a.tr_qty AS qty');
        $this->db->from('tbpo_transaksi a');
        $this->db->join('tbpo_barang_nk b', 'b.kd_barang = a.kd_barang', 'left');
        $this->db->join('tbpo_user c', 'c.kode_user = a.req_by', 'left');
        $this->db->join('tbpo_user d', 'd.kode_user = a.inputer', 'left');
        $this->db->where('DATE(a.tgl_transaksi) >= ' . $this->db->escape($tgl1), null, false);
        $this->db->where('DATE(a.tgl_transaksi) <= ' . $this->db->escape($tgl2), null, false);
        $this->db->where('a.kd_akun != 11411');
        $query = $this->db->get();
        return $query;
    }


    public function v_stock()
    {
        return $this->db->get('v_stockbarangnk')->result();
    }
}
