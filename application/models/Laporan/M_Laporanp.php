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
        $hasRealisasi = $this->db->table_exists('tbpo_realisasi_detail_po_nk');
        $hasLifo = $this->db->table_exists('tbpo_stock_lifo_batch_nk');
        $hargaEdit = $hasRealisasi
            ? 'COALESCE(NULLIF(r.harga_nyata, 0), NULLIF(a.hrg_nyata, 0), a.hrg_satuan)'
            : 'COALESCE(NULLIF(a.hrg_nyata, 0), a.hrg_satuan)';

        $this->db->select("c.nopo, a.tgl_transaksi, b.nama_user, b.departement, a.nama_barang, a.qty,
            a.hrg_satuan, {$hargaEdit} AS harga_edit_purchasing, a.total_harga, a.kd_po_nk, c.status, a.deskripsi", false);
        if ($hasRealisasi) {
            $this->db->join('tbpo_realisasi_detail_po_nk r', 'r.id_det_po_nk = a.id_det_po_nk', 'left');
        }
        if ($hasLifo) {
            // Referensi memakai batch LIFO pembelian terakhir untuk barang yang sama.
            // Data batch adalah snapshot, sehingga laporan lama tidak berubah ketika
            // harga detail PO kemudian diedit.
            $lifoWhere = "(lb.kd_barang = a.kd_barang OR (COALESCE(a.kd_bsys, '') <> '' AND lb.kd_barangsys = a.kd_bsys))";
            $this->db->select("(SELECT lb.harga_satuan FROM tbpo_stock_lifo_batch_nk lb
                WHERE {$lifoWhere} AND lb.status_harga = 'VALID' AND lb.harga_satuan > 0
                ORDER BY lb.tgl_efektif DESC, lb.id_batch DESC LIMIT 1) AS harga_referensi_lifo", false);
            $this->db->select("(SELECT lb.referensi_sumber FROM tbpo_stock_lifo_batch_nk lb
                WHERE {$lifoWhere} AND lb.status_harga = 'VALID' AND lb.harga_satuan > 0
                ORDER BY lb.tgl_efektif DESC, lb.id_batch DESC LIMIT 1) AS referensi_pembelian_lifo", false);
            $this->db->select("(SELECT DATE(lb.tgl_efektif) FROM tbpo_stock_lifo_batch_nk lb
                WHERE {$lifoWhere} AND lb.status_harga = 'VALID' AND lb.harga_satuan > 0
                ORDER BY lb.tgl_efektif DESC, lb.id_batch DESC LIMIT 1) AS tanggal_pembelian_lifo", false);
        } else {
            $this->db->select('NULL AS harga_referensi_lifo, NULL AS referensi_pembelian_lifo, NULL AS tanggal_pembelian_lifo', false);
        }
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

    public function getdaterangelaptr($tgl1, $tgl2, $scope = array(), $pengambilanSaja = false)
    {
        $hasLifo = $this->db->table_exists('tbpo_stock_lifo_batch_nk') && $this->db->table_exists('tbpo_stock_lifo_allocation');
        $hasRealisasi = $this->db->table_exists('tbpo_realisasi_detail_po_nk');
        // Sebagian database lama masih memiliki kolom kode dengan kolasi yang
        // berbeda. Paksa satu kolasi di setiap perbandingan antar-kolom agar
        // laporan tetap dapat dibaca selama migrasi skema belum dijalankan.
        $kodeSama = function ($kiri, $kanan) {
            return "{$kiri} COLLATE utf8mb4_general_ci = {$kanan} COLLATE utf8mb4_general_ci";
        };
        $poSama = $kodeSama('p.kd_po_nk', 'a.kd_po_nk');
        $barangSama = $kodeSama('p.kd_barang', 'a.kd_barang');
        $barangSistemSama = $kodeSama('p.kd_bsys', 'a.kd_barangsys');
        $hargaMasuk = $hasRealisasi
            ? 'COALESCE(NULLIF(r.harga_nyata,0), NULLIF(p.hrg_nyata,0), p.hrg_satuan)'
            : 'COALESCE(NULLIF(p.hrg_nyata,0), p.hrg_satuan)';
        $realisasiJoin = $hasRealisasi ? ' LEFT JOIN tbpo_realisasi_detail_po_nk r ON r.id_det_po_nk=p.id_det_po_nk' : '';
        $nominal = $hasLifo
            ? "CASE WHEN a.kd_akun IN ('11512','11514') THEN COALESCE((SELECT SUM(la.nilai_alokasi) FROM tbpo_stock_lifo_allocation la WHERE la.id_transnk_keluar=a.id_transnk),0) ELSE COALESCE((SELECT {$hargaMasuk} FROM tbpo_detail_po_nk p{$realisasiJoin} WHERE {$poSama} AND ({$barangSama} OR {$barangSistemSama}) ORDER BY p.id_det_po_nk DESC LIMIT 1),0) END"
            : "COALESCE((SELECT {$hargaMasuk} FROM tbpo_detail_po_nk p{$realisasiJoin} WHERE {$poSama} AND ({$barangSama} OR {$barangSistemSama}) ORDER BY p.id_det_po_nk DESC LIMIT 1),0)";
        $hargaPurchasing = $hasLifo
            ? "CASE WHEN a.kd_akun IN ('11512','11514') THEN (SELECT CASE WHEN SUM(la.qty_alokasi) > 0 THEN SUM(la.nilai_alokasi) / SUM(la.qty_alokasi) ELSE NULL END FROM tbpo_stock_lifo_allocation la WHERE la.id_transnk_keluar=a.id_transnk) WHEN a.kd_akun='11511' THEN (SELECT {$hargaMasuk} FROM tbpo_detail_po_nk p{$realisasiJoin} WHERE {$poSama} AND ({$barangSama} OR {$barangSistemSama}) ORDER BY p.id_det_po_nk DESC LIMIT 1) ELSE NULL END"
            : "CASE WHEN a.kd_akun='11511' THEN (SELECT {$hargaMasuk} FROM tbpo_detail_po_nk p{$realisasiJoin} WHERE {$poSama} AND ({$barangSama} OR {$barangSistemSama}) ORDER BY p.id_det_po_nk DESC LIMIT 1) ELSE NULL END";
        $batch = $hasLifo
            ? "(SELECT GROUP_CONCAT(CONCAT(lb.referensi_sumber, ' (qty ', la.qty_alokasi, ')') ORDER BY la.id_alokasi SEPARATOR '; ') FROM tbpo_stock_lifo_allocation la JOIN tbpo_stock_lifo_batch_nk lb ON lb.id_batch=la.id_batch WHERE la.id_transnk_keluar=a.id_transnk)"
            : "NULL";
        $this->db->select("a.kd_po_nk AS kdpo,d.nama_user AS inputer,a.kd_akun AS jn_transaksi, a.tgl_transaksi, c.departement, c.nama_user, b.nama_barang, a.keterangan, a.tr_qty AS qty, {$nominal} AS nominal_satuan, {$hargaPurchasing} AS harga_purchasing, {$batch} AS batch_lifo", false);
        $this->db->from('tbpo_transaksi a');
        // $escape=false wajib agar CI tidak menganggap COLLATE sebagai identifier.
        $this->db->join('tbpo_barang_nk b', $kodeSama('b.kd_barang', 'a.kd_barang'), 'left', false);
        $this->db->join('tbpo_user c', $kodeSama('c.kode_user', 'a.req_by'), 'left', false);
        $this->db->join('tbpo_user d', $kodeSama('d.kode_user', 'a.inputer'), 'left', false);
        $this->db->where('DATE(a.tgl_transaksi) >= ' . $this->db->escape($tgl1), null, false);
        $this->db->where('DATE(a.tgl_transaksi) <= ' . $this->db->escape($tgl2), null, false);
        $this->db->where('a.kd_akun != 11411');
        if ($pengambilanSaja) {
            $this->db->where('a.kd_akun', '11512');
        }
        if (!empty($scope['kode_user'])) {
            $this->db->where('a.req_by', $scope['kode_user']);
        }
        if (!empty($scope['departemen'])) {
            $this->db->where('c.departement', $scope['departemen']);
        }
        $this->db->order_by('a.tgl_transaksi', 'DESC');
        $this->db->order_by('a.id_transnk', 'DESC');
        $query = $this->db->get();
        return $query;
    }


    public function v_stock()
    {
        return $this->db->get('v_stockbarangnk')->result();
    }
}
