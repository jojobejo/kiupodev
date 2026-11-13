<?php
defined('BASEPATH') or exit('No direct script access allowed');
/**
 *
 */
class M_Stockcontroller  extends CI_Model
{
    function __construct()
    {
        parent::__construct();
    }
    public function getAllTransaksi()
    {
        return $this->db->get('tbpo_transaksi')->result();
    }

    // FUNGSI MODEL UNTUK MENAMPILKAN STOK CONTROLLER BERDASARKAN TRANSAKSI
    
    // SELECT a.id_transnk, a.kd_po_nk , d.kd_br_adm , d.nama_barang , a.tr_qty , e.nm_satuan , a.create_at
    // FROM tbpo_transaksi a
    // JOIN tbpo_po_nk b ON b.kd_po_nk = a.kd_po_nk
    // JOIN tbpo_detail_po_nk c ON c.kd_po_nk = a.kd_po_nk
    // JOIN tbpo_barang_nk d ON d.kd_br_adm = a.kd_barang
    // JOIN tbpo_satuan e ON e.id_satuan = a.satuan 
    // GROUP BY a.kd_barangsys
}
