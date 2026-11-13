<?php
defined('BASEPATH') or exit('No direct script access allowed');
/**
 *
 */
class M_Purchase extends CI_Model
{
    function __construct()
    {
        parent::__construct();
    }

    public function getSuplier()
    {
        return $this->db->get('tbpo_suplier')->result();
    }
    public function getTax()
    {
        return $this->db->get('tbpo_set_tax')->result();
    }

    public function gettmptax($kd)
    {
        $this->db->select('COALESCE(tax, 0) AS tax');
        $this->db->from('tbpo_tmp_tax a');
        $this->db->where('kd_suplier', $kd);
        $query = $this->db->get();

        if ($query->num_rows() > 0) {
            return $query->row()->tax;
        } else {
            return 0;
        }
    }


    public function getSatuan()
    {
        return $this->db->get('tbpo_satuan')->result();
    }
    public function getBarangSup($kd, $kodeAwal = null)
    {
        $this->db->select('*');
        $this->db->from('tbpo_barang');
        $this->db->where('kd_suplier', $kd);

        if ($kodeAwal !== null) {
            $kodeAwal = strtoupper(trim((string) $kodeAwal));
            $allowedKodeAwal = array('Q', 'A', 'Z', 'C', 'X');
            if (!in_array($kodeAwal, $allowedKodeAwal, true)) {
                $kodeAwal = 'Q';
            }

            $this->db->like('kode_barang', $kodeAwal, 'after');
        }

        $this->db->order_by('kode_barang', 'ASC');
        $this->db->order_by('nama_barang', 'ASC');
        $query = $this->db->get();
        return $query;
    }
    public function getBarangByKode($kodeBarang, $kodeSuplier)
    {
        $this->db->select('*');
        $this->db->from('tbpo_barang');
        $this->db->where('kode_barang', $kodeBarang);
        $this->db->where('kd_suplier', $kodeSuplier);
        return $this->db->get()->row();
    }
    public function get_barang_by_kode($kode_barang)
    {
        return $this->db
            ->select('kode_barang, nama_barang, isi, kemasan')
            ->from('tbpo_barang')
            ->where('kode_barang', $kode_barang)
            ->get()
            ->row();
    }
    public function gettaxposup($kd)
    {
        $this->db->select('COUNT(a.id_tmp_tax) as tot');
        $this->db->select('a.tax');
        $this->db->select('a.kd_suplier');
        $this->db->from('tbpo_tmp_tax a');
        $this->db->where('kd_suplier', $kd);
        $query = $this->db->get();
        return $query;
    }
    public function nomorPoExists($noPo)
    {
        $this->db->from('tbpo_po');
        $this->db->where('no_po', trim((string) $noPo));
        return $this->db->count_all_results() > 0;
    }
    public function nomorPoSupplierExists($noPo, $kdSuplier)
    {
        $this->db->from('tbpo_po');
        $this->db->where('no_po', trim((string) $noPo));
        $this->db->where('kd_suplier', $kdSuplier);
        return $this->db->count_all_results() > 0;
    }
    public function nomorPoBaseSupplierExists($noPo, $kdSuplier)
    {
        $noPo = strtoupper(trim((string) $noPo));
        if (!preg_match('/^([QA])(\d{3})\/KIU\/([IVXLCDM]+)\/(\d{4})([A-Z]?)$/', $noPo, $matches)) {
            return false;
        }

        $this->db->from('tbpo_po');
        $this->db->where('kd_suplier', $kdSuplier);
        $this->db->where("no_po REGEXP '^" . $this->db->escape_str($matches[1] . $matches[2]) . "/KIU/" . $this->db->escape_str($matches[3]) . "/" . $this->db->escape_str($matches[4]) . "[A-Z]?$'", null, false);
        return $this->db->count_all_results() > 0;
    }
    public function getNomorPoByBase($kodePo, $nomorBase, $bulanRomawi, $tahun)
    {
        $kodePo = strtoupper(trim((string) $kodePo));
        if (!in_array($kodePo, array('Q', 'A'), true)) {
            $kodePo = 'Q';
        }

        $nomorBase = str_pad((int) $nomorBase, 3, '0', STR_PAD_LEFT);
        $this->db->select('no_po, kd_suplier');
        $this->db->from('tbpo_po');
        $this->db->where("no_po REGEXP '^" . $this->db->escape_str($kodePo . $nomorBase) . "/KIU/" . $this->db->escape_str($bulanRomawi) . "/" . $this->db->escape_str($tahun) . "[A-Z]?$'", null, false);
        return $this->db->get()->result();
    }
    public function getAvailableNomorPoSupplier($kodePo, $nomorBase, $bulanRomawi, $tahun, $kdSuplier)
    {
        $kodePo = strtoupper(trim((string) $kodePo));
        if (!in_array($kodePo, array('Q', 'A'), true)) {
            $kodePo = 'Q';
        }

        $nomorBase = str_pad((int) $nomorBase, 3, '0', STR_PAD_LEFT);
        $rows = $this->getNomorPoByBase($kodePo, $nomorBase, $bulanRomawi, $tahun);
        $usedSuffixes = array();

        foreach ($rows as $row) {
            if (!preg_match('/^' . preg_quote($kodePo . $nomorBase, '/') . '\/KIU\/' . preg_quote($bulanRomawi, '/') . '\/' . preg_quote($tahun, '/') . '([A-Z]?)$/', $row->no_po, $matches)) {
                continue;
            }

            if ((string) $row->kd_suplier === (string) $kdSuplier) {
                return null;
            }

            $usedSuffixes[$matches[1]] = true;
        }

        if (!isset($usedSuffixes[''])) {
            return $kodePo . $nomorBase . '/KIU/' . $bulanRomawi . '/' . $tahun;
        }

        foreach (range('A', 'Z') as $suffix) {
            if (!isset($usedSuffixes[$suffix])) {
                return $kodePo . $nomorBase . '/KIU/' . $bulanRomawi . '/' . $tahun . $suffix;
            }
        }

        return null;
    }
    public function getNextNomorPoSupplier($kdSuplier, $kodePo = 'Q')
    {
        $kodePo = strtoupper(trim((string) $kodePo));
        if (!in_array($kodePo, array('Q', 'A'), true)) {
            $kodePo = 'Q';
        }

        $this->db->select("MAX(CAST(SUBSTRING(SUBSTRING_INDEX(no_po, '/', 1), 2) AS UNSIGNED)) AS nomor_akhir", false);
        $this->db->from('tbpo_po');
        $this->db->where('kd_suplier', $kdSuplier);
        $this->db->like('no_po', $kodePo, 'after');
        $this->db->like('no_po', '/KIU/', 'both');
        $query = $this->db->get();
        $nomorAkhir = 0;

        if ($query->num_rows() > 0) {
            $nomorAkhir = (int) $query->row()->nomor_akhir;
        }

        return $nomorAkhir + 1;
    }
    public function Suplier($kd)
    {
        $this->db->select('*');
        $this->db->from('tbpo_suplier');
        $this->db->where('kd_suplier', $kd);
        $query = $this->db->get();
        return $query;
    }
    public function addSuplier($data)
    {
        $this->db->insert('tbpo_suplier', $data);
    }
    function editSuplier($kd, $data)
    {
        $this->db->where('kd_suplier', $kd);
        return $this->db->update('tbpo_suplier', $data);
    }
    public function addChart($data)
    {
        $this->db->insert('tbpo_tmp_item', $data);
    }
    public function update_tax_tmp($kd, $data)
    {
        $this->db->where('kd_suplier', $kd);
        return $this->db->update('tbpo_tmp_tax', $data);
    }
    public function add_tax_tmp($data)
    {
        $this->db->insert('tbpo_tmp_tax', $data);
    }
    public function set_tmp_tax($kd, $tax)
    {
        $this->db->from('tbpo_tmp_tax');
        $this->db->where('kd_suplier', $kd);
        $exists = $this->db->count_all_results() > 0;

        $data = array(
            'kd_suplier' => $kd,
            'tax' => $tax
        );

        if ($exists) {
            $this->db->where('kd_suplier', $kd);
            return $this->db->update('tbpo_tmp_tax', $data);
        }

        return $this->db->insert('tbpo_tmp_tax', $data);
    }
    public function getTmpOrder($kd)
    {
        $this->db->select('a.*');
        if ($this->db->field_exists('merk_barang', 'tbpo_barang')) {
            $this->db->select('b.merk_barang');
        }
        $this->db->from('tbpo_tmp_item a');
        if ($this->db->field_exists('merk_barang', 'tbpo_barang')) {
            $this->db->join('tbpo_barang b', 'b.kode_barang = a.kode_barang AND b.kd_suplier = a.kode_suplier', 'left');
        }
        $this->db->where('a.kode_suplier', $kd);
        if ($this->db->field_exists('is_bonus', 'tbpo_tmp_item')) {
            $this->db->order_by('COALESCE(a.is_bonus, 0)', 'ASC', false);
        }
        $this->db->order_by('a.id_tmp', 'ASC');
        $query = $this->db->get()->result();
        return $query;
    }

    public function getMerkBarangTmpOrder($kd)
    {
        if (!$this->db->field_exists('merk_barang', 'tbpo_barang')) {
            return array();
        }

        $this->db->select('b.merk_barang');
        $this->db->from('tbpo_tmp_item a');
        $this->db->join('tbpo_barang b', 'b.kode_barang = a.kode_barang AND b.kd_suplier = a.kode_suplier', 'left');
        $this->db->where('a.kode_suplier', $kd);
        $this->db->where('b.merk_barang IS NOT NULL', null, false);
        $this->db->where("TRIM(b.merk_barang) <> ''", null, false);
        $this->db->group_by('b.merk_barang');
        $this->db->order_by('b.merk_barang', 'ASC');
        return $this->db->get()->result();
    }

    public function getTmpItemsByMerk($kdSuplier, $merkBarang)
    {
        if (!$this->db->field_exists('merk_barang', 'tbpo_barang')) {
            return array();
        }

        $this->db->select('a.*, b.merk_barang');
        $this->db->from('tbpo_tmp_item a');
        $this->db->join('tbpo_barang b', 'b.kode_barang = a.kode_barang AND b.kd_suplier = a.kode_suplier', 'left');
        $this->db->where('a.kode_suplier', $kdSuplier);
        $this->db->where('b.merk_barang', $merkBarang);
        if ($this->db->field_exists('is_bonus', 'tbpo_tmp_item')) {
            $this->db->where('COALESCE(a.is_bonus, 0) = 0', null, false);
        }
        $this->db->order_by('a.id_tmp', 'ASC');
        return $this->db->get()->result();
    }

    public function getTmpItemById($idTmp)
    {
        $this->db->select('a.*');
        if ($this->db->field_exists('merk_barang', 'tbpo_barang')) {
            $this->db->select('b.merk_barang');
        }
        $this->db->from('tbpo_tmp_item a');
        if ($this->db->field_exists('merk_barang', 'tbpo_barang')) {
            $this->db->join('tbpo_barang b', 'b.kode_barang = a.kode_barang AND b.kd_suplier = a.kode_suplier', 'left');
        }
        $this->db->where('a.id_tmp', $idTmp);
        return $this->db->get()->row();
    }
    function sumTransaksiPenjualan($id_tmp)
    {
        $this->db->select("SUM(total_harga) as total_harga");
        $this->db->select("COUNT(id_tmp) as total_item");
        $this->db->from('tbpo_tmp_item');
        $this->db->where('kode_suplier', $id_tmp);
        return $this->db->get()->result();
    }

    public function hapusChart($id)
    {
        $this->db->where('id_tmp', $id);
        return $this->db->delete('tbpo_tmp_item');
    }

    public function get_tmp($id_tmp)
    {
        $this->db->select('a.*');
        if ($this->db->field_exists('merk_barang', 'tbpo_barang')) {
            $this->db->select('b.merk_barang');
        }
        $this->db->from('tbpo_tmp_item a');
        if ($this->db->field_exists('merk_barang', 'tbpo_barang')) {
            $this->db->join('tbpo_barang b', 'b.kode_barang = a.kode_barang AND b.kd_suplier = a.kode_suplier', 'left');
        }
        $this->db->where('a.kode_suplier', $id_tmp);
        if ($this->db->field_exists('is_bonus', 'tbpo_tmp_item')) {
            $this->db->order_by('COALESCE(a.is_bonus, 0)', 'ASC', false);
        }
        $this->db->order_by('a.id_tmp', 'ASC');
        return $this->db->get()->result();
    }

    public function inputOrder($data)
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

        $data = $filteredData;
        $this->db->insert('tbpo_detail_po', $data);
        return $this->db->insert_id();
    }
    public function hapusTmp($id_tmp)
    {
        $this->db->where('kode_suplier', $id_tmp);
        return $this->db->delete('tbpo_tmp_item');
    }
    private function getPoPrefixByUser($kduser)
    {
        $kduser = strtoupper(trim((string) $kduser));

        if ($kduser == 'KIUADMIN') {
            return 'KPO';
        }

        if ($kduser == 'KEU01' || preg_match('/^KEU01\d+$/', $kduser)) {
            return 'SKPO';
        }

        if ($kduser == 'KEU02') {
            return 'AKPO';
        }

        if ($kduser == 'KEU03') {
            return 'NKPO';
        }

        if ($kduser == 'KEU04') {
            return 'MKPO';
        }

        return 'KPO';
    }

    function kdpo($kduser, $kdsuplier)
    {
        $cd = $this->db->query("SELECT MAX(RIGHT(kd_po,4)) AS kd_max FROM tbpo_po WHERE DATE(create_at)=CURDATE()");
        $kd = "0001";

        if ($cd->num_rows() > 0) {
            foreach ($cd->result() as $k) {
                $tmp = ((int)$k->kd_max) + 1;
                $kd = sprintf("%04s", $tmp);
            }
        }

        date_default_timezone_set('Asia/Jakarta');
        return $this->getPoPrefixByUser($kduser) . date('dmy') . $kdsuplier . $kd;
    }

    // NON KOMERSIL
    function kdnonkomersial()
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
    function generatekd($data)
    {
        $this->db->insert('tbpo_generate_kd', $data);
    }
    function getkdnoponk()
    {
        $cd = $this->db->query("SELECT MAX(RIGHT(kd_po_nk,4)) AS kd_max FROM tbpo_po_nk WHERE DATE(create_at)=CURDATE()");
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
        $kdnk1 = 'NKPO' . date('dmy') . $kd;
        return $kdnk1;
    }
    function get_tmp_non_komersil($kd)
    {
        $this->db->select('*');
        $this->db->from('tbpo_tmp_item_nk');
        $this->db->where('kd_user', $kd);
        $this->db->where('jnis_po', '0');
        $query = $this->db->get()->result();
        return $query;
    }
    function get_gbrbarang($kd)
    {
        $this->db->select('*');
        $this->db->from('tbpo_barang_nk');
        $this->db->where('', $kd);
        $query = $this->db->get()->result();
        return $query;
    }
    function input_tmp_nk($data)
    {
        $this->db->insert('tbpo_tmp_item_nk', $data);
    }
    function editimgbarang($kdbarang, $data)
    {
        $this->db->where('id_tmp_nk', $kdbarang);
        return $this->db->update('tbpo_tmp_item_nk', $data);
    }
    function input_note_pembelian_nt($data)
    {
        $this->db->insert('tbpo_nt_tmp_pembelian', $data);
    }
    function input_detail_nt_pembelian($data)
    {
        $this->db->insert('tbpo_note_pembelian', $data);
    }
    function delete_detail_nt_pembelian($kduser)
    {
        $this->db->where('kd_user', $kduser);
        return $this->db->delete('tbpo_nt_tmp_pembelian');
    }
    function gettmp_note_pembelian($kd)
    {
        $this->db->select('*');
        $this->db->from('tbpo_nt_tmp_pembelian');
        $this->db->where('kd_user', $kd);
        $query = $this->db->get()->result();
        return $query;
    }

    function edit_input_nk($kd, $data)
    {
        $this->db->where('id_tmp_nk', $kd);
        return $this->db->update('tbpo_tmp_item_nk', $data);
    }
    public function hapus_item_nk($id)
    {
        $this->db->where('id_tmp_nk', $id);
        return $this->db->delete('tbpo_tmp_item_nk');
    }
    public function hapus_tmp_nk_nt_pembelian($id)
    {
        $this->db->where('id_tmp_nt_pembelian ', $id);
        return $this->db->delete('tbpo_nt_tmp_pembelian');
    }

    function sumtransaksink()
    {
        $this->db->select("SUM(total_harga) as total_harga");
        $this->db->select("COUNT(id_tmp_nk) as total_item");
        $this->db->from('tbpo_tmp_item_nk');
        return $this->db->get()->result();
    }
    function input_po_nk($data)
    {
        $this->db->insert('tbpo_po_nk', $data);
    }
    function input_detail_po_nk($data)
    {
        $this->db->insert('tbpo_detail_po_nk', $data);
    }
    function hapus_tmp_nk($id)
    {
        $this->db->where('kd_user', $id);
        return $this->db->delete('tbpo_tmp_item_nk');
    }
    function edit_chart_tmp($kd, $data)
    {
        $this->db->where('id_tmp', $kd);
        return $this->db->update('tbpo_tmp_item', $data);
    }
    function addNote($data)
    {
        $this->db->insert('tbpo_note_direktur', $data);
    }
    function editNoteNk($kd, $data)
    {
        $this->db->where('id_tmp_nt_pembelian ', $kd);
        return $this->db->update('tbpo_nt_tmp_pembelian', $data);
    }
    function getTmpDiskonOrder($kd)
    {
        $this->db->select('*');
        $this->db->from('tbpo_tmp_diskon');
        $this->db->where('kd_suplier', $kd);
        $this->db->order_by('id_tmp_diskon', 'ASC');
        $query = $this->db->get()->result();
        return $query;
    }
    public function add_tmp_note_suplier($data)
    {
        $this->db->insert('tbpo_tmp_note_barang', $data);
    }
    function edit_tmp_note_suplier($kd, $data)
    {
        $this->db->where('id_nt_tmp_barang', $kd);
        return $this->db->update('tbpo_tmp_note_barang', $data);
    }
    function delete_tmp_note_suplier($id)
    {
        return $this->db->delete('tbpo_tmp_note_barang', array('id_nt_tmp_barang' => $id));
    }
    function getTmpNoteOrder($kd)
    {
        $this->db->select('*');
        $this->db->from('tbpo_tmp_note_barang');
        $this->db->where('kd_suplier', $kd);
        $query = $this->db->get()->result();
        return $query;
    }
    public function add_diskon_po($data)
    {
        $this->db->insert('tbpo_tmp_diskon', $data);
    }
    public function add_diskons_po($data)
    {
        $this->db->insert('tbpo_tmp_diskon', $data);
    }
    public function delete_tmp_diskon($id_tmp)
    {
        $this->db->where('kd_suplier', $id_tmp);
        return $this->db->delete('tbpo_tmp_diskon');
    }
    public function delete_tmp_tax($id_tmp)
    {
        $this->db->where('kd_suplier', $id_tmp);
        return $this->db->delete('tbpo_tmp_tax');
    }
    public function delete_tmp_note_sp_i($id_tmp)
    {
        $this->db->where('kd_suplier', $id_tmp);
        return $this->db->delete('tbpo_tmp_note_barang');
    }
    public function input_note($data)
    {
        $this->db->insert('tbpo_note_barang', $data);
    }
    public function edit_note_tmp_barang($id_tmp, $data)
    {
        $this->db->where('id_nt_tmp_barang', $id_tmp);
        return $this->db->update('tbpo_tmp_note_barang', $data);
    }
    public function hapus_note_tmp_barang($id_tmp)
    {
        $this->db->where('id_nt_tmp_barang', $id_tmp);
        return $this->db->delete('tbpo_tmp_note_barang');
    }
    public function input_diskon($data)
    {
        $this->db->insert('tbpo_diskon', $data);
    }
    public function edit_diskon_po($id_tmp, $data)
    {
        $this->db->where('id_tmp_diskon', $id_tmp);
        return $this->db->update('tbpo_tmp_diskon', $data);
    }
    public function hapus_tmp_diskon($id_tmp)
    {
        $this->db->where('id_tmp_diskon', $id_tmp);
        return $this->db->delete('tbpo_tmp_diskon');
    }
    public function input_diskon_tmp_nk($data)
    {
        $this->db->insert('tbpo_tmp_diskon_nk', $data);
    }
    public function get_diskon_tmp($kd)
    {
        $this->db->select('*');
        $this->db->from('tbpo_tmp_diskon_nk a');
        $this->db->where('kd_user', $kd);
        $query = $this->db->get();
        return $query;
    }
}
