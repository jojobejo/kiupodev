<?php
defined('BASEPATH') or exit('No direct script access allowed');
/**
 *
 */
class M_PoStatus extends CI_Model
{
    function __construct()
    {
        parent::__construct();
        $this->load->database();
        $this->db->reconnect();
    }

    public function getAll()
    {
        $this->db->select('*');
        $this->db->from('tbpo_po a');
        $this->db->join('tbpo_suplier b', 'b.kd_suplier = a.kd_suplier');
        $this->db->order_by('a.id_po', 'DESC');
        return $this->db->get()->result();
    }
    public function ntformat()
    {
        return $this->db->get('tbpo_notetemplate')->result();
    }
    public function getpotoday()
    {
        return $this->db->query("SELECT *,
        a.status
        FROM tbpo_po a
        JOIN tbpo_suplier b ON b.kd_suplier = a.kd_suplier
        WHERE SUBSTR(a.tgl_transaksi,1,10)=DATE(NOW())
        ORDER BY a.id_po DESC
            ");
    }

    public function getOnProgress()
    {
        $this->db->select('*');
        $this->db->from('tbpo_po a');
        $this->db->join('tbpo_suplier b', 'b.kd_suplier = a.kd_suplier');
        $this->db->where('a.status', 'ON PROGRESS');
        $this->db->or_where('a.status', 'NOTE KEUANGAN');
        $this->db->or_where('a.status', 'NOTE DIREKTUR');
        $this->db->or_where('a.status', 'PO REVISI');
        $this->db->order_by('a.id_po', 'DESC');
        return $this->db->get()->result();
    }
    public function getDoneToday()
    {
        return $this->db->query("SELECT *,
        a.status
        FROM tbpo_po a
        JOIN tbpo_suplier b ON b.kd_suplier = a.kd_suplier
        WHERE SUBSTR(a.tgl_transaksi,1,10)=DATE(NOW()) AND a.status = 'DONE'
        ORDER BY a.id_po DESC
            ");
    }
    public function getOnProgressToday()
    {
        return $this->db->query("SELECT *,
        a.status
        FROM tbpo_po a
        JOIN tbpo_suplier b ON b.kd_suplier = a.kd_suplier
        WHERE SUBSTR(a.tgl_transaksi,1,10)=DATE(NOW()) AND a.status = 'ON PROGRESS' OR a.status = 'PO REVISI'
        ORDER BY a.id_po DESC
            ");
    }
    public function getRejectToday()
    {
        return $this->db->query("SELECT *,
        a.status
        FROM tbpo_po a
        JOIN tbpo_suplier b ON b.kd_suplier = a.kd_suplier
        WHERE SUBSTR(a.tgl_transaksi,1,10)=DATE(NOW()) AND a.status = 'REJECT'
        ORDER BY a.id_po DESC
            ");
    }
    public function getdone()
    {
        $this->db->select('*');
        $this->db->from('tbpo_po a');
        $this->db->join('tbpo_suplier b', 'b.kd_suplier = a.kd_suplier');
        $this->db->where('a.status', 'DONE');
        $this->db->order_by('a.id_po', 'DESC');
        return $this->db->get()->result();
    }

    public function getReject()
    {
        $this->db->select('*');
        $this->db->from('tbpo_po a');
        $this->db->join('tbpo_suplier b', 'b.kd_suplier = a.kd_suplier');
        $this->db->where('a.status', 'REJECT');
        $this->db->order_by('a.id_po', 'DESC');
        return $this->db->get()->result();
    }

    public function getDetail($kdpo)
    {
        $this->db->select('a.*, b.*');
        if ($this->db->field_exists('merk_barang', 'tbpo_barang')) {
            $this->db->select('c.merk_barang');
        }
        $this->db->from('tbpo_detail_po a');
        $this->db->join('tbpo_suplier b', 'b.kd_suplier = a.kd_suplier');
        if ($this->db->field_exists('merk_barang', 'tbpo_barang')) {
            $this->db->join('tbpo_barang c', 'c.kode_barang = a.kd_barang AND c.kd_suplier = a.kd_suplier', 'left');
        }
        $this->db->where('kd_po', $kdpo);
        if ($this->db->field_exists('is_bonus', 'tbpo_detail_po')) {
            $this->db->order_by('COALESCE(a.is_bonus, 0)', 'ASC', false);
        }
        $this->db->order_by('a.id_det_po', 'ASC');
        return $this->db->get()->result();
    }

    public function getMerkBarangPo($kdpo)
    {
        if (!$this->db->field_exists('merk_barang', 'tbpo_barang')) {
            return array();
        }

        $this->db->select('c.merk_barang');
        $this->db->from('tbpo_detail_po a');
        $this->db->join('tbpo_barang c', 'c.kode_barang = a.kd_barang AND c.kd_suplier = a.kd_suplier', 'left');
        $this->db->where('a.kd_po', $kdpo);
        $this->db->where('c.merk_barang IS NOT NULL', null, false);
        $this->db->where("TRIM(c.merk_barang) <> ''", null, false);
        $this->db->group_by('c.merk_barang');
        $this->db->order_by('c.merk_barang', 'ASC');
        return $this->db->get()->result();
    }

    function sumTransaksiPenjualan($kdpo)
    {
        $this->db->select("SUM(hrg_total) as total_harga");
        $this->db->select("COUNT(id_det_po) as total_item");
        $this->db->from('tbpo_detail_po');
        $this->db->where('kd_po', $kdpo);
        return $this->db->get()->result();
    }

    function sumTransaksiPenjualanAll($kdpo)
    {
        $this->db->select("SUM(hrg_total) as total_harga");
        $this->db->from('tbpo_detail_po');
        $this->db->where('kd_po', $kdpo);
        return $this->db->get()->result();
    }
    function totalDiskon($kdpo)
    {
        $this->db->select("SUM(nominal) as total_diskon");
        $this->db->select("count(id_diskon) as total_item");
        $this->db->from('tbpo_diskon');
        $this->db->where('kd_po', $kdpo);
        return $this->db->get()->result();
    }
    function counhrgnyata($kdpo)
    {
        $this->db->select("SUM(hrg_nyata) as total_hrg");
        $this->db->from('tbpo_detail_po_nk');
        $this->db->where('kd_po_nk', $kdpo);
        return $this->db->get()->result();
    }
    function totalnote($kdpo)
    {
        $this->db->select("SUM(id_nt_barang) as total_note");
        $this->db->select("count(id_nt_barang) as total_nt_item");
        $this->db->from('tbpo_note_barang');
        $this->db->where('kd_po', $kdpo);
        return $this->db->get()->result();
    }

    function getdataStatus($kdpo)
    {
        $this->db->select('*');
        $this->db->from('tbpo_po a');
        $this->db->join('tbpo_suplier b', 'b.kd_suplier = a.kd_suplier');
        $this->db->where('kd_po', $kdpo);
        return $this->db->get()->result();
    }
    function getDataStatuss($kdpo)
    {
        $this->db->select('*');
        $this->db->from('tbpo_po a');
        $this->db->join('tbpo_suplier b', 'b.kd_suplier = a.kd_suplier');
        $this->db->join('tbpo_user c', 'c.kode_user = a.acc_with');
        $this->db->join('tbpo_notetemplate d', 'd.kd_nt_template = a.kd_printout_note');
        $this->db->where('kd_po', $kdpo);
        return $this->db->get()->result();
    }
    function getDataStatusPrint($kdpo)
    {
        $this->db->select('*');
        $this->db->from('tbpo_po a');
        $this->db->join('tbpo_suplier b', 'b.kd_suplier = a.kd_suplier');
        $this->db->join('tbpo_user c', 'c.kode_user = a.acc_with', 'left');
        $this->db->join('tbpo_notetemplate d', 'd.kd_nt_template = a.kd_printout_note', 'left');
        $this->db->where('kd_po', $kdpo);
        return $this->db->get()->result();
    }
    function getDataStatussnk($kdpo)
    {
        return $this->db->query("SELECT a.* , b.nama_user AS nm_karyawan , c.nama_user AS nm_kadep , d.nama_user AS nm_direktur
        FROM tbpo_po_nk a 
        JOIN tbpo_user b ON b.kode_user = a.kd_user
        JOIN tbpo_user c ON c.kode_user = a.acc_with_kadep
        JOIN tbpo_user d ON d.kode_user = a.acc_with
        WHERE a.kd_po_nk = '$kdpo' 
        ");
    }
    function CountItem($kdpo)
    {
        return $this->db->query("SELECT 
        COUNT(a.id_det_po) AS total_item
        FROM tbpo_detail_po a
        WHERE kd_po = '$kdpo'
        ");
    }
    function CountItemnk($kdpo)
    {
        return $this->db->query("SELECT 
        COUNT(a.id_det_po_nk) AS total_item
        FROM tbpo_detail_po_nk a
        WHERE kd_po_nk = '$kdpo'
        ");
    }

    function konfirmPo($kdpo, $data)
    {
        $this->db->where('kd_po', $kdpo);
        return $this->db->update('tbpo_po', $data);
    }

    function tolakPo($kdpo, $data)
    {
        $this->db->where('kd_po', $kdpo);
        return $this->db->update('tbpo_po', $data);
    }
    function konfirmPonk($kdpo, $data)
    {
        $this->db->where('kd_po_nk', $kdpo);
        return $this->db->update('tbpo_po_nk', $data);
    }

    function tolakPonk($kdpo, $data)
    {
        $this->db->where('kd_po_nk', $kdpo);
        return $this->db->update('tbpo_po_nk', $data);
    }
    function pendingordernk($kdpo, $data)
    {
        $this->db->where('kd_po_nk', $kdpo);
        return $this->db->update('tbpo_po_nk', $data);
    }

    function addNote($data)
    {
        $this->db->insert('tbpo_note_direktur', $data);
    }
    function addnotesuplier($data)
    {
        $this->db->insert('tbpo_note_barang', $data);
    }
    function insertDiskon($data)
    {
        $this->db->insert('tbpo_diskon', $data);
        return $this->db->insert_id();
    }

    function insert_diskon_merk($data)
    {
        $this->db->insert('tbpo_diskon_merk', $data);
        return $this->db->insert_id();
    }

    function update_diskon_merk($id_diskon, $data)
    {
        if (!$this->db->table_exists('tbpo_diskon_merk')) {
            return false;
        }

        $this->db->where('id_diskon', $id_diskon);
        return $this->db->update('tbpo_diskon_merk', $data);
    }

    function get_items_po_by_merk($kdpo, $merkBarang)
    {
        if (!$this->db->field_exists('merk_barang', 'tbpo_barang')) {
            return array();
        }

        $this->db->select('a.*, c.merk_barang');
        $this->db->from('tbpo_detail_po a');
        $this->db->join('tbpo_barang c', 'c.kode_barang = a.kd_barang AND c.kd_suplier = a.kd_suplier', 'left');
        $this->db->where('a.kd_po', $kdpo);
        $this->db->where('c.merk_barang', $merkBarang);
        if ($this->db->field_exists('is_bonus', 'tbpo_detail_po')) {
            $this->db->where('COALESCE(a.is_bonus, 0) = 0', null, false);
        }
        $this->db->order_by('a.id_det_po', 'ASC');
        return $this->db->get()->result();
    }

    function update_diskon_item($id_detail, $data)
    {
        $filteredData = array();
        foreach ($data as $field => $value) {
            if ($this->db->field_exists($field, 'tbpo_detail_po')) {
                $filteredData[$field] = $value;
            }
        }

        if (empty($filteredData)) {
            return false;
        }

        $this->db->where('id_det_po', $id_detail);
        return $this->db->update('tbpo_detail_po', $filteredData);
    }
    function editDiskon($id_diskon, $data)
    {
        $this->db->where('id_diskon', $id_diskon);
        return $this->db->update('tbpo_diskon', $data);
    }
    function updateStatus($kdpo, $status)
    {
        $this->db->where('kd_po', $kdpo);
        return $this->db->update('tbpo_po', $status);
    }
    function updateStatusnk($kdpo, $status)
    {
        $this->db->where('kd_po_nk', $kdpo);
        return $this->db->update('tbpo_po_nk', $status);
    }
    function updateTax($kdpo, $updateTax)
    {
        $filteredData = array();
        foreach ($updateTax as $field => $value) {
            if ($this->db->field_exists($field, 'tbpo_po')) {
                $filteredData[$field] = $value;
            }
        }

        $this->db->where('kd_po', $kdpo);
        return $this->db->update('tbpo_po', $filteredData);
    }

    function updateDetailPO($id, $data)
    {
        $this->db->where('id_det_po', $id);
        return $this->db->update('tbpo_detail_po', $data);
    }

    function getDiskon($kdpo)
    {
        $this->db->select('*');
        $this->db->from('tbpo_diskon');
        $this->db->where('kd_po', $kdpo);
        $this->db->order_by('id_diskon', 'ASC');
        return $this->db->get()->result();
    }

    function getNextNomorDiskon($kdpo)
    {
        $this->db->select('keterangan');
        $this->db->from('tbpo_diskon');
        $this->db->where('kd_po', $kdpo);
        $rows = $this->db->get()->result();

        $maxNomor = 0;
        foreach ($rows as $row) {
            if (preg_match('/^Diskon\s+(\d+)\s*-/i', (string) $row->keterangan, $match)) {
                $maxNomor = max($maxNomor, (int) $match[1]);
            }
        }

        return max($maxNomor, count($rows)) + 1;
    }

    function getHistoriDiskonPo($kdpo)
    {
        $this->db->select('
            a.id_diskon,
            a.kd_po,
            b.no_po,
            b.tgl_transaksi,
            c.nama_suplier,
            a.keterangan,
            a.nominal
        ');
        $this->db->from('tbpo_diskon a');
        $this->db->join('tbpo_po b', 'b.kd_po = a.kd_po', 'left');
        $this->db->join('tbpo_suplier c', 'c.kd_suplier = b.kd_suplier', 'left');
        $this->db->where('a.kd_po', $kdpo);
        $this->db->order_by('a.id_diskon', 'ASC');
        return $this->db->get()->result();
    }
    function getDiskonnk($kdpo)
    {
        $this->db->select('*');
        $this->db->from('tbpo_diskon');
        $this->db->where('kd_po', $kdpo);
        return $this->db->get()->result();
    }

    function hapusDiskon($id)
    {
        $this->db->where('id_diskon', $id);
        return $this->db->delete('tbpo_diskon');
    }

    function hapusDiskonNK($id)
    {
        $this->db->where('id_diskon', $id);
        return $this->db->delete('tbpo_diskon');
    }

    function getNoted($kdpo)
    {
        $this->db->select('*');
        $this->db->from('tbpo_note_direktur');
        $this->db->where('kd_po', $kdpo);
        return $this->db->get()->result();
    }

    public function getTax()
    {
        return $this->db->get('tbpo_set_tax')->result();
    }
    public function getSatuan()
    {
        return $this->db->get('tbpo_satuan')->result();
    }
    public function addRevisiChart($data)
    {
        $filteredData = array();
        foreach ($data as $field => $value) {
            if ($this->db->field_exists($field, 'tbpo_detail_po')) {
                $filteredData[$field] = $value;
            }
        }

        $this->db->insert('tbpo_detail_po', $filteredData);
    }

    function revisiPO($id, $data)
    {
        $filteredData = array();
        foreach ($data as $field => $value) {
            if ($this->db->field_exists($field, 'tbpo_detail_po')) {
                $filteredData[$field] = $value;
            }
        }

        $this->db->where('id_det_po', $id);
        return $this->db->update('tbpo_detail_po', $filteredData);
    }
    function updateLog($data)
    {
        $filteredData = array();
        foreach ($data as $field => $value) {
            if ($this->db->field_exists($field, 'tbpo_tracking_po')) {
                $filteredData[$field] = $value;
            }
        }

        if (empty($filteredData)) {
            return false;
        }

        return $this->db->insert('tbpo_tracking_po', $filteredData);
    }
    function getDetailItemById($id)
    {
        $this->db->select('a.*');
        if ($this->db->field_exists('merk_barang', 'tbpo_barang')) {
            $this->db->select('c.merk_barang');
        }
        $this->db->from('tbpo_detail_po a');
        if ($this->db->field_exists('merk_barang', 'tbpo_barang')) {
            $this->db->join('tbpo_barang c', 'c.kode_barang = a.kd_barang AND c.kd_suplier = a.kd_suplier', 'left');
        }
        $this->db->where('a.id_det_po', $id);
        return $this->db->get()->row();
    }
    function getLog($kdpo)
    {
        $this->db->select('*');
        $this->db->from('tbpo_tracking_po');
        $this->db->where('kd_po', $kdpo);
        $this->db->order_by('createat', 'DESC');
        return $this->db->get()->result();
    }
    function hapusBarang($id)
    {
        $this->db->where('id_det_po', $id);
        return $this->db->delete('tbpo_detail_po');
    }
    function deletepodet($kdpo)
    {
        $this->db->where('kd_po', $kdpo);
        return $this->db->delete('tbpo_detail_po');
    }
    function deletepo($kdpo)
    {
        $this->db->where('kd_po', $kdpo);
        return $this->db->delete('tbpo_po');
    }

    public function getAllNk()
    {
        return $this->db->query("SELECT *,
        a.status
        FROM tbpo_po_nk a
        JOIN tbpo_user b ON b.kode_user = a.kd_user");
    }

    public function getAllNK_keu()
    {
        return $this->db->query("SELECT *,
        a.status
        FROM tbpo_po_nk a
        JOIN tbpo_user b ON b.kode_user = a.kd_user
        WHERE a.status != 'DONE' AND a.status != 'ACC-KADEP' AND a.departemen = 'KEUANGAN'
            ");
    }
    public function getAllNK_keu_purchasing()
    {
        return $this->db->query("SELECT *,
        a.status
        FROM tbpo_po_nk a
        JOIN tbpo_user b ON b.kode_user = a.kd_user
        WHERE a.status != 'DONE' AND a.status != 'REJECT' AND a.departemen = 'PURCHASING'
            ");
    }

    public function ponkgetAllNK_keu_purchasing($kd)
    {
        return $this->db->query("SELECT *,
            a.status
            FROM tbpo_po_nk a
            JOIN tbpo_user b ON b.kode_user = a.kd_user
            WHERE a.status != 'DONE'
            AND a.status != 'REJECT'
                ");
    }

    public function getAllNK_kar($kduser)
    {
        return $this->db->query("SELECT *,
        a.status
        FROM tbpo_po_nk a
        JOIN tbpo_user b ON b.kode_user = a.kd_user
        WHERE a.status != 'DONE'
        AND a.status != 'REJECT'
            ");
    }

    public function getuserdone($kd, $sts)
    {
        if ($sts == '1') {
            return $this->db->query("SELECT *,
            a.status
            FROM tbpo_po_nk a
            JOIN tbpo_user b ON b.kode_user = a.kd_user
            WHERE a.kd_user = '$kd'
            AND a.status = 'DONE'
                ");
        } else {
            return $this->db->query("SELECT *,
            a.status
            FROM tbpo_po_nk a
            JOIN tbpo_user b ON b.kode_user = a.kd_user
            WHERE a.kd_user = '$kd'
            AND a.status = 'REJECT'
                ");
        }
    }

    public function getAllNK_kadep($kddep)
    {
        return $this->db->query("SELECT 
        a.kd_po_nk, a.kd_po_req, a.nopo, a.status, a.tgl_transaksi, b.nama_user, a.departemen, a.tj_pembelian
        FROM tbpo_po_nk a
        JOIN tbpo_user b ON b.kode_user = a.kd_user
        WHERE a.departemen = '$kddep'
        AND a.status NOT IN ('DONE','ON PROGRESS')
            ");
    }

    public function getall_nk_kadep_keu_sales()
    {
        return $this->db->query("SELECT 
            a.kd_po_nk,
            a.kd_po_req,
            a.nopo,
            a.status,
            a.tgl_transaksi,
            b.nama_user,
            a.departemen,
            a.tj_pembelian
        FROM tbpo_po_nk a
        JOIN tbpo_user b 
            ON b.kode_user = a.kd_user
        WHERE a.status NOT IN ('DONE','ON PROGRESS')
        AND a.departemen IN ('KEUANGAN','SALES');");
    }

    public function getall_nk_promosi_seed()
    {
        return $this->db->query("SELECT 
            a.kd_po_nk,
            a.kd_po_req,
            a.nopo,
            a.status,
            a.tgl_transaksi,
            b.nama_user,
            a.departemen,
            a.tj_pembelian
        FROM tbpo_po_nk a
        JOIN tbpo_user b 
            ON b.kode_user = a.kd_user
        WHERE a.status NOT IN ('DONE','ON PROGRESS')
        AND a.departemen = 'PROMOSI SEED'
        ");
    }
    public function getall_nk_promosi_cp()
    {
        return $this->db->query("SELECT 
            a.kd_po_nk,
            a.kd_po_req,
            a.nopo,
            a.status,
            a.tgl_transaksi,
            b.nama_user,
            a.departemen,
            a.tj_pembelian
        FROM tbpo_po_nk a
        JOIN tbpo_user b 
            ON b.kode_user = a.kd_user
        WHERE a.status NOT IN ('DONE','ON PROGRESS')
        AND a.departemen = 'PROMOSI CP'
        ");
    }

    public function getAllNK_direktur()
    {
        return $this->db->query("SELECT *,
        a.status
        FROM tbpo_po_nk a
        JOIN tbpo_user b ON b.kode_user = a.kd_user
        WHERE a.status != 'ACC-KADEP' AND a.status != 'DONE' AND a.status != 'ON PROGRESS'
        AND a.status != 'ON PROGRESS - KADEP' AND a.status != 'PENDING' AND a.status != 'ACC DIREKTUR' 
        AND a.status != 'PROSES PEMBELIAN'AND a.status != 'REJECT' AND a.status != 'PO REVISI'");
    }

    public function getNKpch($sts)
    {
        $this->db->select('*');
        $this->db->from('tbpo_po_nk a');
        $this->db->join('tbpo_user b', 'b.kode_user = a.kd_user');
        $this->db->where('a.status', $sts);
        return $this->db->get()->result();
    }
    public function getstatusRevisi($kdpo)
    {
        $this->db->select('*');
        $this->db->from('tbpo_po_nk a');
        $this->db->where('a.kd_po_nk', $kdpo);
        return $this->db->get()->result();
    }
    public function getNKdep($dep)
    {
        $this->db->select('*');
        $this->db->from('tbpo_po_nk a');
        $this->db->join('tbpo_user b', 'b.kode_user = a.kd_user');
        $this->db->where('a.departemen', $dep);
        return $this->db->get()->result();
    }
    public function getNKdirektur($sts)
    {
        $this->db->select('*');
        $this->db->from('tbpo_po_nk a');
        $this->db->join('tbpo_user b', 'b.kode_user = a.kd_user');
        $this->db->where('a.departemen', $sts);
        return $this->db->get()->result();
    }
    public function getDetailnk($kd)
    {
        $this->db->select('a.*');
        // PO Jasa dapat membawa material manual yang belum mempunyai master
        // barang.  Tetap tampilkan baris PO tersebut agar Purchasing dapat
        // melengkapi master-nya dari halaman detail PO.
        $this->db->select("COALESCE(c.gbr_barang, 'Karisma.png') AS gbr_barang", false);
        if ($this->db->table_exists('tbpo_realisasi_detail_po_nk')) {
            $this->db->select('COALESCE(r.qty_nyata, a.qty) AS qty_nyata', false);
            $this->db->select('COALESCE(r.harga_nyata, a.hrg_nyata) AS hrg_nyata', false);
            $this->db->select('COALESCE(r.total_nyata, a.total_nyata) AS total_nyata', false);
            $this->db->select('COALESCE(r.status_approval_harga, "") AS status_approval_harga_nyata', false);
            $this->db->select('COALESCE(r.alasan_realisasi, "") AS alasan_realisasi', false);
            $this->db->select('COALESCE(r.selisih_harga, 0) AS selisih_harga_nyata', false);
            $this->db->select('COALESCE(r.updated_at, r.created_at) AS realisasi_updated_at', false);
        } else {
            $this->db->select('a.qty AS qty_nyata', false);
            $this->db->select('a.hrg_nyata AS hrg_nyata', false);
            $this->db->select('a.total_nyata AS total_nyata', false);
            $this->db->select('"" AS status_approval_harga_nyata', false);
            $this->db->select('"" AS alasan_realisasi', false);
            $this->db->select('(a.hrg_nyata - a.hrg_satuan) AS selisih_harga_nyata', false);
            $this->db->select('NULL AS realisasi_updated_at', false);
        }
        $this->db->from('tbpo_detail_po_nk a');
        $this->db->join('tbpo_user b', 'b.kode_user = a.kd_user', 'left');
        $this->db->join('tbpo_barang_nk c', 'c.kd_barang = a.kd_barang', 'left');
        if ($this->db->table_exists('tbpo_realisasi_detail_po_nk')) {
            $this->db->join('tbpo_realisasi_detail_po_nk r', 'r.id_det_po_nk = a.id_det_po_nk', 'left');
        }
        $this->db->where('a.kd_po_nk', $kd);
        return $this->db->get()->result();
    }

    public function get_manual_master_required_for_ponk($kdpo)
    {
        if (!$this->db->table_exists('tbpo_jasa_material')
            || !$this->db->field_exists('source_material_id', 'tbpo_detail_po_nk')) {
            return array();
        }

        return $this->db->query(
            "SELECT d.id_det_po_nk, d.source_material_id, d.nama_barang, d.deskripsi,
                    d.qty, d.hrg_satuan, p.kd_po_req AS kd_po_jasa,
                    m.nama_material, m.deskripsi AS deskripsi_material, m.satuan AS satuan_material
             FROM tbpo_detail_po_nk d
             JOIN tbpo_po_nk p ON p.kd_po_nk = d.kd_po_nk
             JOIN tbpo_jasa_material m ON m.id_material = d.source_material_id
             LEFT JOIN tbpo_barang_nk b ON b.id_brg_nk = m.id_brg_nk
             WHERE d.kd_po_nk = ?
               AND (d.source_module IN ('PO_JASA', 'PO_JASA_DEV') OR p.source_module IN ('PO_JASA', 'PO_JASA_DEV'))
               AND (m.id_brg_nk IS NULL OR m.id_brg_nk = 0 OR b.id_brg_nk IS NULL)
             ORDER BY d.id_det_po_nk ASC",
            array($kdpo)
        )->result();
    }

    public function get_kategori_barang_nk()
    {
        return $this->db->order_by('nama_kategori', 'ASC')->get('tbpo_kat_br')->result();
    }

    public function get_satuan_barang_nk()
    {
        return $this->db->order_by('nm_satuan', 'ASC')->get('tbpo_satuan')->result();
    }

    /**
     * Generates a PONK code that is absent from both code columns on the
     * non-commercial master table. The caller may invoke this inside a
     * transaction; the final existence check then runs under that transaction.
     */
    public function generate_unique_manual_master_code()
    {
        $prefix = 'PONK' . date('dmy');
        $like = $prefix . '%';
        $sequence = $this->db->query(
            "SELECT GREATEST(\n"
            . "  COALESCE(MAX(CAST(RIGHT(kd_barang, 4) AS UNSIGNED)), 0),\n"
            . "  COALESCE(MAX(CAST(RIGHT(kd_br_adm, 4) AS UNSIGNED)), 0)\n"
            . ") AS kd_max\n"
            . "FROM tbpo_barang_nk\n"
            . "WHERE kd_barang LIKE ? OR kd_br_adm LIKE ?",
            array($like, $like)
        )->row();
        $next = (int) ($sequence ? $sequence->kd_max : 0) + 1;

        for ($candidateNumber = $next; $candidateNumber <= 9999; $candidateNumber++) {
            $code = $prefix . sprintf('%04d', $candidateNumber);
            $exists = $this->db->group_start()
                ->where('kd_barang', $code)
                ->or_where('kd_br_adm', $code)
                ->group_end()
                ->count_all_results('tbpo_barang_nk') > 0;
            if (!$exists) {
                return $code;
            }
        }

        return false;
    }

    public function save_manual_master_barang_pojasa($kdpo, $materialId, $payload, $inputer)
    {
        if (!$this->db->table_exists('tbpo_jasa_material')
            || !$this->db->field_exists('source_material_id', 'tbpo_detail_po_nk')) {
            return array('status' => false, 'message' => 'Struktur integrasi PO Jasa belum tersedia.');
        }

        $this->db->trans_begin();
        $detail = $this->db->query(
            "SELECT d.id_det_po_nk, d.source_material_id, p.kd_po_req
             FROM tbpo_detail_po_nk d
             JOIN tbpo_po_nk p ON p.kd_po_nk = d.kd_po_nk
             JOIN tbpo_jasa_material m ON m.id_material = d.source_material_id
             WHERE d.kd_po_nk = ? AND d.source_material_id = ?
               AND (d.source_module IN ('PO_JASA', 'PO_JASA_DEV') OR p.source_module IN ('PO_JASA', 'PO_JASA_DEV'))
               AND (m.id_brg_nk IS NULL OR m.id_brg_nk = 0)
             FOR UPDATE",
            array($kdpo, (int) $materialId)
        )->row();

        if (!$detail) {
            $this->db->trans_rollback();
            return array('status' => false, 'message' => 'Item manual tidak ditemukan atau master barangnya sudah direkam.');
        }

        if (trim((string) $payload['kat_barang']) === '' || (int) $payload['satuan'] <= 0 || trim((string) $payload['nama_barang']) === '') {
            $this->db->trans_rollback();
            return array('status' => false, 'message' => 'Kategori, nama barang, dan satuan wajib diisi.');
        }
        if (!$this->db->where('kd_kat', $payload['kat_barang'])->count_all_results('tbpo_kat_br')
            || !$this->db->where('id_satuan', (int) $payload['satuan'])->count_all_results('tbpo_satuan')) {
            $this->db->trans_rollback();
            return array('status' => false, 'message' => 'Kategori atau satuan tidak valid.');
        }

        $kodeBarang = $this->generate_unique_manual_master_code();
        if ($kodeBarang === false) {
            $this->db->trans_rollback();
            return array('status' => false, 'message' => 'Kode barang otomatis tidak dapat dibuat.');
        }
        $kodeAdmin = $kodeBarang;
        $now = date('Y-m-d H:i:s');
        $master = array(
            'kd_barang' => $kodeBarang,
            'kd_br_adm' => $kodeAdmin,
            'kat_barang' => $payload['kat_barang'],
            'nama_barang' => trim((string) $payload['nama_barang']),
            'descnk' => trim((string) $payload['descnk']),
            'satuan' => (int) $payload['satuan'],
            'minimum_stock' => 0,
            'gbr_barang' => 'Karisma.png',
            'qrcode_path' => '',
            'qrcode_data' => '',
            'inputer' => $inputer,
            'create_at' => $now,
            'last_updated' => $inputer,
        );
        $this->db->insert('tbpo_barang_nk', $master);
        $masterId = (int) $this->db->insert_id();
        $this->db->insert('tbpo_generate_kd', array('kd_barang' => $kodeBarang));

        $materialUpdate = array('id_brg_nk' => $masterId);
        if ($this->db->field_exists('kd_barang_snapshot', 'tbpo_jasa_material')) {
            $materialUpdate['kd_barang_snapshot'] = $kodeBarang;
        }
        $this->db->where('id_material', (int) $materialId)
            ->where('(id_brg_nk IS NULL OR id_brg_nk = 0)', null, false)
            ->update('tbpo_jasa_material', $materialUpdate);
        if ($this->db->affected_rows() !== 1) {
            $this->db->trans_rollback();
            return array('status' => false, 'message' => 'Master barang telah direkam oleh pengguna lain.');
        }

        $this->db->where(array('kd_po_nk' => $kdpo, 'source_material_id' => (int) $materialId))->update('tbpo_detail_po_nk', array(
            'kd_bsys' => $kodeAdmin,
            'kd_barang' => $kodeBarang,
            'kat_barang' => $payload['kat_barang'],
            'satuan' => (int) $payload['satuan'],
            'gbr_produk' => 'Karisma.png',
        ));

        if ($this->db->trans_status() === false) {
            $this->db->trans_rollback();
            return array('status' => false, 'message' => 'Master barang gagal disimpan.');
        }
        $this->db->trans_commit();
        return array('status' => true, 'message' => 'Master barang berhasil direkam dan item PO pembelian telah dilengkapi.');
    }

    public function get_last_harga_barang_nk($kodeBarang, $kodeBarangSys = '')
    {
        $this->db->select('a.hrg_satuan, a.hrg_nyata, a.total_harga, a.total_nyata, a.kd_po_nk, p.tgl_transaksi');
        $this->db->from('tbpo_detail_po_nk a');
        $this->db->join('tbpo_po_nk p', 'p.kd_po_nk = a.kd_po_nk', 'left');
        $this->db->where('a.hrg_satuan >', 0);

        if ($kodeBarangSys !== '') {
            $this->db->where('a.kd_bsys', $kodeBarangSys);
        } else {
            $this->db->where('a.kd_barang', $kodeBarang);
        }

        $this->db->order_by('p.tgl_transaksi', 'DESC');
        $this->db->order_by('a.id_det_po_nk', 'DESC');
        $this->db->limit(1);

        return $this->db->get()->row();
    }
    public function getDetailnktgl($kd)
    {
        $this->db->select('*');
        $this->db->from('tbpo_po_nk a');
        $this->db->join('tbpo_user b', 'b.kode_user = a.kd_user');
        $this->db->where('kd_po_nk', $kd);
        return $this->db->get()->result();
    }
    function getdataStatusnk($kdpo)
    {
        $this->db->select('a.*');
        $this->db->from('tbpo_po_nk a');
        $this->db->join('tbpo_user b', 'b.kode_user = a.kd_user');
        $this->db->where('kd_po_nk', $kdpo);
        return $this->db->get()->result();
    }

    function get_ponk_by_req($kd_po_req)
    {
        $this->db->select('*');
        $this->db->from('tbpo_po_nk');
        $this->db->where('kd_po_req', $kd_po_req);
        return $this->db->get()->row();
    }

    function cancel_pengajuan_ponk($kd_po_req)
    {
        $this->db->where('kd_po_req', $kd_po_req);
        return $this->db->update('tbpo_po_nk', array(
            'status' => 'PENGAJUAN DIBATALKAN'
        ));
    }

    function update_tujuan_pembelian_ponk($kd_po_req, $tujuan_pembelian)
    {
        $this->db->where('kd_po_req', $kd_po_req);
        return $this->db->update('tbpo_po_nk', array(
            'tj_pembelian' => $tujuan_pembelian
        ));
    }

    function flupload($kdpo)
    {
        return $this->db->query("SELECT 
        a.*,
        CASE WHEN a.file_name LIKE 'assets/request-pendukung/%' THEN a.file_name ELSE CONCAT('images/filepndukung/', a.file_uploaded) END AS display_path,
        LOWER(SUBSTRING_INDEX(a.file_uploaded,'.',-1)) AS kdfile
        FROM tbpo_file_nk a
        JOIN tbpo_user b ON b.kode_user = a.user_upload
        WHERE a.kd_po_nk = '$kdpo'");
    }

    function get_supporting_file($id)
    {
        return $this->db->query("SELECT a.*, CASE WHEN a.file_name LIKE 'assets/request-pendukung/%' THEN a.file_name ELSE CONCAT('images/filepndukung/', a.file_uploaded) END AS display_path
            FROM tbpo_file_nk a
            WHERE a.id_file_nk = ?", array((int) $id))->row();
    }

    /** Dokumen yang diunggah PIC pada request asal dari PO non-komersil ini. */
    function get_pic_supporting_documents($kdpo)
    {
        if (!$this->db->table_exists('tbpo_req_nk_supporting_file')) {
            return array();
        }

        return $this->db->query("SELECT d.*
            FROM tbpo_req_nk_supporting_file d
            JOIN tbpo_po_nk p ON p.kd_po_req = d.kd_po_nk
            WHERE p.kd_po_nk = ?
            ORDER BY d.id_supporting_file ASC", array($kdpo))->result();
    }

    /**
     * Folder dokumen pendukung PIC untuk PO pembelian ini. Bukti pembelian
     * harus disimpan bersama dokumen request asal, bukan di folder baru milik
     * tanggal upload Purchasing.
     */
    function get_pic_supporting_directory($kdpo)
    {
        if (!$this->db->table_exists('tbpo_req_nk_supporting_file')) {
            return null;
        }

        $document = $this->db->query("SELECT d.file_path
            FROM tbpo_req_nk_supporting_file d
            JOIN tbpo_po_nk p ON p.kd_po_req = d.kd_po_nk
            WHERE p.kd_po_nk = ?
              AND d.file_path LIKE 'assets/request-pendukung/%'
            ORDER BY d.id_supporting_file ASC
            LIMIT 1", array($kdpo))->row();

        if (!$document || empty($document->file_path)) {
            return null;
        }

        $directory = trim(str_replace('\\', '/', dirname($document->file_path)), '/');
        return strpos($directory, 'assets/request-pendukung/') === 0 ? $directory . '/' : null;
    }
    function fluploadbukti($kdpo)
    {
        return $this->db->query("SELECT 
        a.*,
        CASE WHEN a.file_name LIKE 'assets/request-pendukung/%' THEN a.file_name ELSE CONCAT('images/upbukti/', a.file_uploaded) END AS display_path,
        LOWER(SUBSTRING_INDEX(a.file_uploaded,'.',-1)) AS kdfile
        FROM tbpo_file_bukti_beli a
        JOIN tbpo_user b ON b.kode_user = a.user_upload
        WHERE a.kd_po_nk = '$kdpo'");
    }

    function get_purchase_proof_file($id)
    {
        return $this->db->query("SELECT a.*, CASE WHEN a.file_name LIKE 'assets/request-pendukung/%' THEN a.file_name ELSE CONCAT('images/upbukti/', a.file_uploaded) END AS display_path
            FROM tbpo_file_bukti_beli a
            WHERE a.id_fk_bukti = ?", array((int) $id))->row();
    }

    // function fluploads($kdpo)
    // {
    //     $this->db->select('*');
    //     $this->db->select('RIGHT');
    //     $this->db->from('tbpo_file_nk a');
    //     $this->db->join('tbpo_user b', 'b.kode_user = a.user_upload');
    //     $this->db->where('kd_po_nk', $kdpo);
    //     return $this->db->get()->result();
    // }
    // function fluploadbuktis($kdpo)
    // {
    //     $this->db->select('*');
    //     $this->db->from('tbpo_file_bukti_beli a');
    //     $this->db->join('tbpo_user b', 'b.kode_user = a.user_upload');
    //     $this->db->where('kd_po_nk', $kdpo);
    //     return $this->db->get()->result();
    // }

    function upbuktibeli($data)
    {
        $this->db->insert('tbpo_file_bukti_beli', $data);
        return $this->db->insert_id();
    }

    function editflupload($id, $data)
    {
        $this->db->where('id_file_nk', $id);
        return $this->db->update('tbpo_file_nk', $data);
    }

    function updateshipment($kd, $data)
    {
        $this->db->where('kd_po', $kd);
        return $this->db->update('tbpo_po', $data);
    }

    function deletegbrfilependukung($id)
    {
        $this->db->where('id_file_nk', $id);
        return $this->db->delete('tbpo_file_nk');
    }
    function gtflnmfilependukung($id)
    {
        $this->db->select("*");
        $this->db->from('tbpo_file_nk');
        $this->db->where('id_file_nk', $id);
        return $this->db->get()->result();
    }
    function sumTransaksiPenjualannk($kdpo)
    {
        $this->db->select("SUM(total_harga) as total_harga");
        $this->db->select("COUNT(id_det_po_nk) as total_item");
        $this->db->from('tbpo_detail_po_nk');
        $this->db->where('kd_po_nk', $kdpo);
        return $this->db->get()->result();
    }
    function sumharganyata($kdpo)
    {
        if ($this->db->table_exists('tbpo_realisasi_detail_po_nk')) {
            $this->db->select("SUM(COALESCE(r.total_nyata, a.total_nyata)) as total_nyata", false);
            $this->db->select("COUNT(a.id_det_po_nk) as total_item", false);
            $this->db->from('tbpo_detail_po_nk a');
            $this->db->join('tbpo_realisasi_detail_po_nk r', 'r.id_det_po_nk = a.id_det_po_nk', 'left');
            $this->db->where('a.kd_po_nk', $kdpo);
        } else {
            $this->db->select("SUM(total_nyata) as total_nyata");
            $this->db->select("COUNT(id_det_po_nk) as total_item");
            $this->db->from('tbpo_detail_po_nk');
            $this->db->where('kd_po_nk', $kdpo);
        }
        return $this->db->get()->result();
    }
    function edit_faktur_item_nk($id, $kdpo)
    {
        $this->db->where('id_det_po_nk', $id);
        return $this->db->update('tbpo_detail_po_nk', $kdpo);
    }
    function hapus_faktur_item_nk($id)
    {
        $this->db->where('id_det_po_nk', $id);
        return $this->db->delete('tbpo_detail_po_nk');
    }
    public function get_note_barang($kdpo)
    {
        $this->db->select('*');
        $this->db->from('tbpo_note_barang');
        $this->db->where('kd_po', $kdpo);
        return $this->db->get()->result();
    }

    function editnotesuplier($id, $data)
    {
        $this->db->where('id_nt_barang', $id);
        return $this->db->update('tbpo_note_barang', $data);
    }
    function hapusnotesuplier($id)
    {
        return $this->db->delete('tbpo_note_barang', array('id_nt_barang' => $id));
    }
    function srcdatepo($dt1, $dt2)
    {
        $this->db->select("*");
        $this->db->where("a.tgl_transaksi >='$dt1'");
        $this->db->where("a.tgl_transaksi <='$dt2'");
        $this->db->from('tbpo_po a');
        $this->db->join('tbpo_suplier b', 'b.kd_suplier = a.kd_suplier');
        $query = $this->db->get();
        return $query->result();
    }

    function kdpo($kdpo)
    {
        $cd = $this->db->query("SELECT MAX(RIGHT(kd_po,4)) AS kd_max FROM tbpo_po WHERE DATE(create_at)=CURDATE() ");
        $kd = "";
        if ($cd->num_rows() > 0) {
            foreach ($cd->result() as $k) {
                $tmp = ((int)$k->kd_max) + 1;
                $kd = sprintf("%04s", $tmp);
            }
        } else {
            $kd = "0001";
        }
        date_default_timezone_set('Asia/Jakarta');
        return 'KPO' . 'REV' . date('dmy') . $kd;
    }

    public function get_ori_po($id_tmp)
    {
        $this->db->from('tbpo_detail_po');
        $this->db->where('kd_po', $id_tmp);
        return $this->db->get()->result();
    }
    public function get_ori_nk($id_tmp)
    {
        $this->db->from('tbpo_detail_po_nk');
        $this->db->where('kd_po_nk', $id_tmp);
        return $this->db->get()->result();
    }
    public function get_note_pembelian($kd)
    {
        $this->db->from('tbpo_note_pembelian');
        $this->db->where('kd_po', $kd);
        return $this->db->get()->result();
    }
    public function inputRevisi($data)
    {
        $filteredData = array();
        foreach ($data as $field => $value) {
            if ($this->db->field_exists($field, 'tbpo_po')) {
                $filteredData[$field] = $value;
            }
        }

        $this->db->insert('tbpo_po', $filteredData);
    }
    public function inputDetailPO($data)
    {
        $filteredData = array();
        foreach ($data as $field => $value) {
            if ($this->db->field_exists($field, 'tbpo_detail_po')) {
                $filteredData[$field] = $value;
            }
        }

        $this->db->insert('tbpo_detail_po', $filteredData);
    }
    public function input_diskon($data)
    {
        $this->db->insert('tbpo_diskon', $data);
    }
    public function input_note($data)
    {
        $this->db->insert('tbpo_note_barang', $data);
    }
    function editnopo($id, $kdpo)
    {
        $this->db->where('id_po', $id);
        return $this->db->update('tbpo_po', $kdpo);
    }
    function editnopodet($id, $kdpo)
    {
        $this->db->where('kd_po', $id);
        return $this->db->update('tbpo_detail_po', $kdpo);
    }
    function editharganyatadetail($id, $data)
    {
        $this->db->where('id_det_po_nk', $id);
        return $this->db->update('tbpo_detail_po_nk', $data);
    }
    public function get_detail_po_nk_row($id)
    {
        $this->db->select('*');
        $this->db->from('tbpo_detail_po_nk');
        $this->db->where('id_det_po_nk', $id);
        return $this->db->get()->row();
    }
    public function simpan_realisasi_harga_nyata($id, $data, $logData = array())
    {
        $detail = $this->get_detail_po_nk_row($id);
        if (!$detail) {
            return false;
        }

        $this->db->trans_start();

        if ($this->db->table_exists('tbpo_realisasi_po_nk')) {
            $header = array(
                'kd_po_nk' => $detail->kd_po_nk,
                'status_realisasi' => 'DRAFT',
                'updated_by' => isset($logData['kd_user']) ? $logData['kd_user'] : '',
                'updated_at' => date('Y-m-d H:i:s')
            );

            $exists = $this->db
                ->where('kd_po_nk', $detail->kd_po_nk)
                ->get('tbpo_realisasi_po_nk')
                ->row();

            if ($exists) {
                $this->db->where('kd_po_nk', $detail->kd_po_nk);
                $this->db->update('tbpo_realisasi_po_nk', $header);
            } else {
                $header['created_by'] = isset($logData['kd_user']) ? $logData['kd_user'] : '';
                $header['created_at'] = date('Y-m-d H:i:s');
                $this->db->insert('tbpo_realisasi_po_nk', $header);
            }
        }

        if ($this->db->table_exists('tbpo_realisasi_detail_po_nk')) {
            $row = array(
                'kd_po_nk' => $detail->kd_po_nk,
                'id_det_po_nk' => $id,
                'qty_pengajuan' => $detail->qty,
                'harga_pengajuan' => $detail->hrg_satuan,
                'total_pengajuan' => $detail->total_harga,
                'qty_nyata' => $data['qty_nyata'],
                'harga_nyata' => $data['hrg_nyata'],
                'total_nyata' => $data['total_nyata'],
                'selisih_harga' => $data['hrg_nyata'] - $detail->hrg_satuan,
                'status_approval_harga' => $data['status_approval_harga_nyata'],
                'alasan_realisasi' => isset($data['alasan_realisasi']) ? $data['alasan_realisasi'] : '',
                'updated_by' => isset($logData['kd_user']) ? $logData['kd_user'] : '',
                'updated_at' => date('Y-m-d H:i:s')
            );

            $exists = $this->db
                ->where('id_det_po_nk', $id)
                ->get('tbpo_realisasi_detail_po_nk')
                ->row();

            if ($exists) {
                $this->db->where('id_det_po_nk', $id);
                $this->db->update('tbpo_realisasi_detail_po_nk', $row);
            } else {
                $row['created_by'] = isset($logData['kd_user']) ? $logData['kd_user'] : '';
                $row['created_at'] = date('Y-m-d H:i:s');
                $this->db->insert('tbpo_realisasi_detail_po_nk', $row);
            }
        }

        $legacy = array(
            'hrg_nyata' => $data['hrg_nyata'],
            'total_nyata' => $data['total_nyata']
        );
        $this->db->where('id_det_po_nk', $id);
        $this->db->update('tbpo_detail_po_nk', $legacy);

        if ($this->db->table_exists('tbpo_realisasi_harganyata_log')) {
            $log = array(
                'kd_po_nk' => $detail->kd_po_nk,
                'id_det_po_nk' => $id,
                'aksi' => isset($logData['aksi']) ? $logData['aksi'] : 'SIMPAN_REALISASI',
                'keterangan' => isset($logData['keterangan']) ? $logData['keterangan'] : '',
                'kd_user' => isset($logData['kd_user']) ? $logData['kd_user'] : '',
                'nama_user' => isset($logData['nama_user']) ? $logData['nama_user'] : '',
                'created_at' => date('Y-m-d H:i:s')
            );
            $this->db->insert('tbpo_realisasi_harganyata_log', $log);
        }

        $this->db->trans_complete();
        return $this->db->trans_status();
    }
    public function get_harga_nyata_summary($kdpo)
    {
        if ($this->db->table_exists('tbpo_realisasi_detail_po_nk')) {
            return $this->db->query("
                SELECT
                    COUNT(a.id_det_po_nk) AS total_item,
                    SUM(CASE WHEN r.id_realisasi_detail IS NULL OR COALESCE(r.harga_nyata,0) <= 0 THEN 1 ELSE 0 END) AS belum_input,
                    SUM(CASE WHEN COALESCE(r.harga_nyata,0) > a.hrg_satuan THEN 1 ELSE 0 END) AS harga_lebih_tinggi,
                    SUM(CASE WHEN r.status_approval_harga = 'PENDING_DIREKTUR' THEN 1 ELSE 0 END) AS pending_direktur,
                    SUM(CASE WHEN r.status_approval_harga = 'DITOLAK_DIREKTUR' THEN 1 ELSE 0 END) AS ditolak_direktur
                FROM tbpo_detail_po_nk a
                LEFT JOIN tbpo_realisasi_detail_po_nk r ON r.id_det_po_nk = a.id_det_po_nk
                WHERE a.kd_po_nk = ?
            ", array($kdpo))->row();
        }

        return $this->db->query("
            SELECT
                COUNT(id_det_po_nk) AS total_item,
                SUM(CASE WHEN COALESCE(hrg_nyata,0) <= 0 THEN 1 ELSE 0 END) AS belum_input,
                SUM(CASE WHEN COALESCE(hrg_nyata,0) > hrg_satuan THEN 1 ELSE 0 END) AS harga_lebih_tinggi,
                0 AS pending_direktur,
                0 AS ditolak_direktur
            FROM tbpo_detail_po_nk
            WHERE kd_po_nk = ?
        ", array($kdpo))->row();
    }
    public function approve_harga_nyata_detail($id, $status, $userData = array())
    {
        $detail = $this->get_detail_po_nk_row($id);
        if (!$detail || !$this->db->table_exists('tbpo_realisasi_detail_po_nk')) {
            return false;
        }

        $data = array(
            'status_approval_harga' => $status,
            'approved_by' => isset($userData['kd_user']) ? $userData['kd_user'] : '',
            'approved_at' => date('Y-m-d H:i:s'),
            'updated_by' => isset($userData['kd_user']) ? $userData['kd_user'] : '',
            'updated_at' => date('Y-m-d H:i:s')
        );

        $this->db->trans_start();
        $this->db->where('id_det_po_nk', $id);
        $this->db->update('tbpo_realisasi_detail_po_nk', $data);

        if ($this->db->table_exists('tbpo_realisasi_harganyata_log')) {
            $this->db->insert('tbpo_realisasi_harganyata_log', array(
                'kd_po_nk' => $detail->kd_po_nk,
                'id_det_po_nk' => $id,
                'aksi' => $status,
                'keterangan' => $status === 'DISETUJUI_DIREKTUR' ? 'Harga nyata lebih tinggi disetujui Direktur.' : 'Harga nyata ditolak Direktur.',
                'kd_user' => isset($userData['kd_user']) ? $userData['kd_user'] : '',
                'nama_user' => isset($userData['nama_user']) ? $userData['nama_user'] : '',
                'created_at' => date('Y-m-d H:i:s')
            ));
        }

        $this->db->trans_complete();
        return $this->db->trans_status();
    }
    public function can_proses_pembelian_harga_nyata($kdpo)
    {
        $summary = $this->get_harga_nyata_summary($kdpo);
        if (!$summary) {
            return array('status' => true, 'message' => '');
        }

        if (!$this->db->table_exists('tbpo_realisasi_detail_po_nk') && (int) $summary->harga_lebih_tinggi > 0) {
            return array('status' => false, 'message' => 'Harga nyata lebih tinggi membutuhkan tabel realisasi dan approval Direktur. Jalankan migrasi database realisasi harga nyata terlebih dahulu.');
        }

        if ((int) $summary->pending_direktur > 0) {
            return array('status' => false, 'message' => 'Harga nyata lebih tinggi dari harga pengajuan masih menunggu approval Direktur.');
        }

        if ((int) $summary->ditolak_direktur > 0) {
            return array('status' => false, 'message' => 'Ada harga nyata yang ditolak Direktur. Revisi harga nyata sebelum proses pembelian.');
        }

        return array('status' => true, 'message' => '');
    }
    function editharganyata($id, $data)
    {
        $this->db->where('kd_po_nk', $id);
        return $this->db->update('tbpo_po_nk', $data);
    }
    function generatekd()
    {
        $cd1 = $this->db->query("SELECT MAX(RIGHT(kd_barang,4)) AS kd_max FROM tbpo_generate_kd WHERE DATE(create_at)=CURDATE()");
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
        $kdnk1 = 'PONK' . date('dmy') . $kd1;
        return $kdnk1;
    }

    function insertkd($data)
    {
        $this->db->insert('tbpo_generate_kd', $data);
    }
    function add_faktur_nk($data)
    {
        $this->db->insert('tbpo_detail_po_nk', $data);
    }
    function add_note_pembelian_nk($data)
    {
        $this->db->insert('tbpo_note_pembelian', $data);
    }
    function add_file_po_nk($data)
    {
        $this->db->insert('tbpo_file_nk', $data);
        return $this->db->insert_id();
    }

    function insert_arsip_evident_ponk($data)
    {
        if (!$this->db->table_exists('tbpo_arsip_evident_ponk')) {
            return false;
        }

        $this->db->insert('tbpo_arsip_evident_ponk', $data);
        return $this->db->insert_id();
    }

    function get_arsip_evident_ponk_done($filters = array())
    {
        $where = "p.status = 'DONE'";
        $binds = array();

        if (!empty($filters['tgl_start'])) {
            $where .= " AND p.tgl_transaksi >= ?";
            $binds[] = $filters['tgl_start'];
        }

        if (!empty($filters['tgl_end'])) {
            $where .= " AND p.tgl_transaksi <= ?";
            $binds[] = $filters['tgl_end'];
        }

        if (!empty($filters['kd_po_nk'])) {
            $where .= " AND p.kd_po_nk LIKE ?";
            $binds[] = '%' . $filters['kd_po_nk'] . '%';
        }

        if ($this->db->table_exists('tbpo_arsip_evident_ponk')) {
            return $this->db->query("
                SELECT
                    a.id_arsip_evident AS id_file,
                    a.kd_po_nk,
                    a.jenis_evident,
                    a.keterangan,
                    a.file_path,
                    a.file_uploaded,
                    a.user_upload,
                    a.create_at,
                    p.nopo,
                    p.tgl_transaksi,
                    p.departemen,
                    p.tj_pembelian
                FROM tbpo_arsip_evident_ponk a
                JOIN tbpo_po_nk p ON p.kd_po_nk = a.kd_po_nk
                WHERE {$where}
                ORDER BY p.tgl_transaksi DESC, a.create_at DESC
            ", $binds)->result();
        }

        return $this->db->query("
            SELECT
                f.id_file_nk AS id_file,
                f.kd_po_nk,
                'FILE PENDUKUNG PENGAJUAN' AS jenis_evident,
                f.keterangan,
                CONCAT('images/filepndukung/', f.file_uploaded) AS file_path,
                f.file_uploaded,
                f.user_upload,
                f.create_at,
                p.nopo,
                p.tgl_transaksi,
                p.departemen,
                p.tj_pembelian
            FROM tbpo_file_nk f
            JOIN tbpo_po_nk p ON p.kd_po_nk = f.kd_po_nk
            WHERE {$where}
            UNION ALL
            SELECT
                b.id_fk_bukti AS id_file,
                b.kd_po_nk,
                'BUKTI PEMBELIAN BARANG' AS jenis_evident,
                b.keterangan,
                CONCAT('images/upbukti/', b.file_uploaded) AS file_path,
                b.file_uploaded,
                b.user_upload,
                b.create_at,
                p.nopo,
                p.tgl_transaksi,
                p.departemen,
                p.tj_pembelian
            FROM tbpo_file_bukti_beli b
            JOIN tbpo_po_nk p ON p.kd_po_nk = b.kd_po_nk
            WHERE {$where}
            ORDER BY tgl_transaksi DESC, create_at DESC
        ", array_merge($binds, $binds))->result();
    }
    function add_tax_nk($id, $data)
    {
        $this->db->where('kd_po_nk', $id);
        return $this->db->update('tbpo_po_nk', $data);
    }
    function edit_note_pembelian_nk($id, $data)
    {
        $this->db->where('id_nt_pembelian', $id);
        return $this->db->update('tbpo_note_pembelian', $data);
    }
    function editedponk($id, $data)
    {
        $this->db->where('kd_po_nk', $id);
        return $this->db->update('tbpo_po_nk', $data);
    }
    function addnopo($id, $data)
    {
        $this->db->where('kd_po_nk', $id);
        return $this->db->update('tbpo_po_nk', $data);
    }
    function hapus_note_pembelian_nk($kdpo)
    {
        $this->db->where('id_nt_pembelian', $kdpo);
        return $this->db->delete('tbpo_note_pembelian');
    }
    function deletepodetnk($kdpo)
    {
        $this->db->where('kd_po_nk', $kdpo);
        return $this->db->delete('tbpo_detail_po_nk');
    }
    function deleteponk($kdpo)
    {
        $this->db->where('kd_po_nk', $kdpo);
        return $this->db->delete('tbpo_po_nk');
    }
    function deletediskonk($kdpo)
    {
        $this->db->where('kd_po', $kdpo);
        return $this->db->delete('tbpo_diskon');
    }
    function deletenotenk($kdpo)
    {
        $this->db->where('kd_po', $kdpo);
        return $this->db->delete('tbpo_note_direktur');
    }
    function insert_setting_note($id, $data)
    {
        $this->db->where('kd_po', $id);
        return $this->db->update('tbpo_po', $data);
    }
    function update_pr_po($id, $data)
    {
        $this->db->where('kd_po', $id);
        return $this->db->update('tbpo_po', $data);
    }
    function get_total($kdpo)
    {
        return $this->db->query("SELECT *,
        a.status
        FROM tbpo_po_nk a
        JOIN tbpo_user b ON b.kode_user = a.kd_user
        WHERE a.status != 'ACC-KADEP' AND a.status != 'DONE' AND a.status != 'ON PROGRESS' AND a.status != 'ON PROGRESS - KADEP' AND a.status != 'PENDING' AND a.status != 'ACC DIREKTUR' AND a.status != 'PROSES PEMBELIAN'
            ");
    }

    function uploadgbr_edited($kdpo, $data)
    {
        $this->db->where('id_det_po_nk', $kdpo);
        return $this->db->update('tbpo_detail_po_nk', $data);
    }
    function changestatusnyata($kdponk, $data)
    {
        $this->db->where('kd_po_nk', $kdponk);
        return $this->db->update('tbpo_po_nk', $data);
    }
    function getdaterangelap($tgl1, $tgl2)
    {
        $this->db->select('*');
        $this->db->from('tbpo_po_nk a');
        $this->db->join('tbpo_user b', 'b.kode_user = a.kd_user');
        $this->db->where('a.tgl_transaksi >=', $tgl1);
        $this->db->where('a.tgl_transaksi <=', $tgl2);
        $query = $this->db->get();
        return $query;
    }

    function getdatapodone($lv, $kd)
    {
        if ($lv == '3') {
            return $this->db->query("SELECT *
        FROM tbpo_po_nk a
        JOIN tbpo_user b ON b.kode_user = a.kd_user
        WHERE a.status = 'DONE'
        ORDER BY a.tgl_transaksi ASC
            ");
        } elseif ($lv == '5') {
            if ($kd == 'KADEP05') {
                return $this->db->query("SELECT *
                FROM tbpo_po_nk a
                JOIN tbpo_user b ON b.kode_user = a.kd_user
                WHERE a.status = 'DONE' 
                AND a.departemen = 'GA'
                ORDER BY a.tgl_transaksi ASC
                    ");
            } elseif ($kd == 'KADEP01') {
                return $this->db->query("SELECT *
                FROM tbpo_po_nk a
                JOIN tbpo_user b ON b.kode_user = a.kd_user
                WHERE a.status = 'DONE'
                AND a.departemen = 'KEAUANGAN'
                OR a.departemen = 'HRD'
                ORDER BY a.tgl_transaksi ASC
                    ");
            } elseif ($kd == 'KADEP02') {
                return $this->db->query("SELECT *
                FROM tbpo_po_nk a
                JOIN tbpo_user b ON b.kode_user = a.kd_user
                WHERE a.status = 'DONE'
                AND a.departemen = 'SALES'
                ORDER BY a.tgl_transaksi ASC
                    ");
            } elseif ($kd == 'KADEP03') {
                return $this->db->query("SELECT *
                FROM tbpo_po_nk a
                JOIN tbpo_user b ON b.kode_user = a.kd_user
                WHERE a.status = 'DONE'
                AND a.departemen = 'LOGISTIK'
                ORDER BY a.tgl_transaksi ASC
                    ");
            }
        } elseif ($lv == '2') {
            return $this->db->query("SELECT *
                FROM tbpo_po_nk a
                JOIN tbpo_user b ON b.kode_user = a.kd_user
                WHERE a.status = 'DONE'
                ORDER BY a.tgl_transaksi ASC
            ");
        }
    }
    function srcgetdateponk($dep, $vartgl1, $vartgl2)
    {
        $this->db->select('a.tgl_transaksi AS tgl , a.departemen AS dep , a.tj_pembelian as ket , a.status AS sts , a.kd_po_nk as kdponk');
        $this->db->from('tbpo_po_nk a');
        $this->db->join('tbpo_user b', 'b.kode_user = a.kd_user');
        $this->db->where('a.tgl_transaksi >=', $vartgl1);
        $this->db->where('a.tgl_transaksi <=', $vartgl2);
        $this->db->where('a.departemen', $dep);
        $this->db->where('a.status', 'DONE');
        $query = $this->db->get();
        return $query->result();
    }

    function srchistoriadmpurchasing($vartgl1, $vartgl2)
    {
        $this->db->select('a.tgl_transaksi AS tgl , a.departemen AS dep , a.tj_pembelian as ket , a.status AS sts , a.kd_po_nk as kdponk');
        $this->db->from('tbpo_po_nk a');
        $this->db->join('tbpo_user b', 'b.kode_user = a.kd_user');
        $this->db->where('a.tgl_transaksi >=', $vartgl1);
        $this->db->where('a.tgl_transaksi <=', $vartgl2);
        $this->db->where('a.status', 'DONE');
        $query = $this->db->get();
        return $query->result();
    }

    function get_br_nk_det($kd)
    {
        $this->db->select('a.* , b.kat_barang , b.satuan , c.nm_satuan');
        $this->db->from('tbpo_detail_po_nk a');
        $this->db->join('tbpo_barang_nk b', 'b.kd_br_adm = a.kd_bsys');
        $this->db->join('tbpo_satuan c', 'c.id_satuan = b.satuan');
        $this->db->where('kd_po_nk', $kd);
        $query = $this->db->get()->result();
        return $query;
    }

    /**
     * Posts the physical movement for an automatically-created PO Jasa
     * purchase.  The stock is received under the purchase PO (11511) and is
     * immediately issued to the originating PO Jasa (11512).  This must stay
     * separate from the normal non-commercial PO flow.  The header is marked
     * DONE only after both legs of every stock movement are persisted.
     */
    public function post_pojasa_on_hand_stock_pair($kdpo, $transactionDate, $actorCode)
    {
        if (!$this->db->table_exists('tbpo_jasa_purchase_submission')
            || !$this->db->field_exists('source_module', 'tbpo_po_nk')) {
            return array('success' => false, 'code' => 'NOT_PO_JASA');
        }

        $this->db->trans_begin();
        $header = $this->db->query(
            "SELECT * FROM tbpo_po_nk WHERE kd_po_nk=? AND source_module='PO_JASA' FOR UPDATE",
            array($kdpo)
        )->row();
        if (!$header) {
            $this->db->trans_rollback();
            return array('success' => false, 'code' => 'PURCHASE_NOT_READY');
        }
        if ($header->status === 'DONE') {
            $this->db->trans_rollback();
            return array('success' => false, 'code' => 'ALREADY_POSTED');
        }
        if ($header->status !== 'PROSES PEMBELIAN') {
            $this->db->trans_rollback();
            return array('success' => false, 'code' => 'PURCHASE_NOT_READY');
        }

        // The detail-receipt screen may have already recorded all or part of
        // this purchase.  Never let the legacy all-item button post a second
        // movement on top of that more granular flow.
        $hasGranularReceipt = $this->db->table_exists('tbpo_jasa_purchase_submission_detail')
            && (int) $this->db->query(
                'SELECT COUNT(*) total FROM tbpo_jasa_purchase_submission_detail sd '
                . 'JOIN tbpo_jasa_purchase_submission s ON s.id_submission=sd.id_submission '
                . 'WHERE s.kd_po_nk=? AND sd.qty_received > 0',
                array($kdpo)
            )->row()->total > 0;
        if ($hasGranularReceipt) {
            $this->db->trans_rollback();
            return array('success' => false, 'code' => 'USE_DETAIL_RECEIPT');
        }

        // This guard makes repeated clicks harmless and prevents duplicate stock.
        $alreadyPosted = (int) $this->db->where(array(
            'kd_po_nk' => $kdpo,
            'kd_akun' => '11511',
        ))->like('keterangan', 'ON_HAND PO Jasa ' . $kdpo . ' - stok masuk', 'after')
            ->count_all_results('tbpo_transaksi');
        if ($alreadyPosted > 0) {
            $this->db->trans_rollback();
            return array('success' => false, 'code' => 'ALREADY_POSTED');
        }

        $items = $this->db->query(
            "SELECT d.*, b.id_brg_nk, b.kd_barang, b.kd_br_adm, b.kat_barang, b.satuan AS stock_satuan,
                    m.qty_kebutuhan
             FROM tbpo_detail_po_nk d
             JOIN tbpo_barang_nk b ON b.kd_barang=d.kd_barang
             JOIN tbpo_jasa_material m ON m.id_material=d.source_material_id
             WHERE d.kd_po_nk=? ORDER BY d.id_det_po_nk ASC FOR UPDATE",
            array($kdpo)
        )->result();
        if (!$items) {
            $this->db->trans_rollback();
            return array('success' => false, 'code' => 'ITEM_NOT_FOUND');
        }

        foreach ($items as $item) {
            if (empty($item->kd_barang) || empty($item->kd_br_adm) || empty($item->kat_barang)
                || (int) $item->stock_satuan < 1 || (float) $item->qty <= 0
                || (float) $item->qty_kebutuhan <= 0) {
                $this->db->trans_rollback();
                return array('success' => false, 'code' => 'MASTER_BARANG_INVALID');
            }

            // Barang dari PO Pembelian otomatis melengkapi kebutuhan material
            // jasa.  Keluarkan stok yang tersedia setelah penerimaan, paling
            // banyak sebesar kebutuhan material jasa.  Contoh: ready 2,
            // kebutuhan 4, PO 2 => IN 2 dan OUT 4 (stok akhir 0).
            $stockRow = $this->db->query(
                'SELECT qty_ready FROM v_stockbarangnk WHERE id_brg_nk=? LIMIT 1',
                array((int) $item->id_brg_nk)
            )->row();
            $qtyReady = max(0, (float) ($stockRow ? $stockRow->qty_ready : 0));
            $qtyIn = (float) $item->qty;
            $qtyOut = min((float) $item->qty_kebutuhan, $qtyReady + $qtyIn);
            $base = array(
                'kd_barang' => $item->kd_barang,
                'kd_barangsys' => $item->kd_br_adm,
                'kat_barang' => $item->kat_barang,
                'tr_qty' => $qtyIn,
                'satuan' => (int) $item->stock_satuan,
                'inputer' => $actorCode,
                'req_by' => $header->kd_user,
                'tgl_transaksi' => $transactionDate,
                'create_at' => date('Y-m-d'),
                'last_updated_by' => $actorCode,
            );
            $this->db->insert('tbpo_transaksi', array_merge($base, array(
                'kd_akun' => '11511', 'kd_po_nk' => $kdpo,
                'keterangan' => 'ON_HAND PO Jasa ' . $kdpo . ' - stok masuk PO Pembelian',
            )));
            $this->db->insert('tbpo_transaksi', array_merge($base, array(
                'kd_akun' => '11512', 'kd_po_nk' => $header->kd_po_req,
                'tr_qty' => $qtyOut,
                'keterangan' => 'ON_HAND PO Jasa ' . $kdpo . ' - stok keluar ke PO Jasa ' . $header->kd_po_req
                    . ' (ready ' . $qtyReady . ', kebutuhan ' . (float) $item->qty_kebutuhan . ')',
            )));
        }

        $this->db->where('id_po_nk', (int) $header->id_po_nk)
            ->where('status', 'PROSES PEMBELIAN')
            ->update('tbpo_po_nk', array('status' => 'DONE'));
        if ($this->db->affected_rows() !== 1) {
            $this->db->trans_rollback();
            return array('success' => false, 'code' => 'STATUS_UPDATE_FAILED');
        }

        if ($this->db->trans_status() === false) {
            $this->db->trans_rollback();
            return array('success' => false, 'code' => 'DATABASE_ERROR');
        }
        $this->db->trans_commit();
        return array('success' => true, 'code' => 'POSTED', 'item_count' => count($items));
    }
    function input_transaksi($data)
    {
        $this->db->insert('tbpo_transaksi', $data);
    }
    function updatereqnk_stsbr($kd, $data)
    {
        $this->db->where('kd_po_nk', $kd);
        $this->db->where('status', '4');
        return $this->db->update('tbpo_detail_req', $data);
    }
    function getitemreq($kdpo)
    {
        return $this->db->query("SELECT
        a.kd_po AS kdpo,
        a.kd_barang AS kdbarang,
        a.qty AS qty,
        a.satuan AS satuan,
        a.tgl_transaksi AS tgltr
        FROM tbpo_detail_po a
        WHERE a.kd_po = '$kdpo' 
        ");
    }

    function input_tr($data)
    {
        $this->db->insert('tbpo_transaksi', $data);
    }

    // BRACKET END MODEL
}
