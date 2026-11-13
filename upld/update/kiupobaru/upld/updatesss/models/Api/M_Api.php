<?php
defined('BASEPATH') or exit('No direct script access allowed');

class M_Api extends CI_Model
{
    public function get_data_barang($params = [])
    {
        $limit = isset($params['limit']) ? (int)$params['limit'] : 100;
        $limit = $limit > 0 ? min($limit, 500) : 100;
        $offset = isset($params['offset']) ? max(0, (int)$params['offset']) : 0;

        $this->db->select($this->get_barang_select_sql(), false);
        $this->db->from('tbpo_barang a');
        $this->join_barang_supplier();
        $this->apply_barang_filters($params);
        $this->db->group_by('a.id_barang');
        $this->db->order_by('a.nama_barang', 'ASC');
        $this->db->order_by('a.kode_barang', 'ASC');
        $this->db->limit($limit, $offset);
        $data = $this->db->get()->result_array();

        $this->db->from('tbpo_barang a');
        $this->join_barang_supplier();
        $this->apply_barang_filters($params);
        $this->db->select('COUNT(DISTINCT a.id_barang) AS total', false);
        $totalFilterRow = $this->db->get()->row();
        $totalFilter = $totalFilterRow ? (int)$totalFilterRow->total : 0;

        return [
            'total_data'   => (int)$this->db->count_all('tbpo_barang'),
            'total_filter' => $totalFilter,
            'limit'        => $limit,
            'offset'       => $offset,
            'data'         => $data,
        ];
    }

    public function get_data_pre_po_erp()
    {
        $rows = $this->db
            ->select('
                a.no_po,
                a.kd_po,
                a.tgl_transaksi,
                a.kd_suplier,
                a.kd_barang,
                a.satuan,
                a.qty,
                a.hrg_satuan,
                a.hrg_satuan AS harga_satuan,
                a.hrg_satuan AS harga,
                a.hrg_diskon AS harga_diskon,
                a.hrg_total,
                a.hrg_total AS total_harga,
                a.hrg_total_diskon AS total_harga_diskon,
                COALESCE(p.tax, 0) AS tax,
                ((COALESCE(p.tax, 0) / 100) * COALESCE(a.hrg_total_diskon, 0)) AS tax_diskon,
                (COALESCE(a.hrg_total, 0) + ((COALESCE(p.tax, 0) / 100) * COALESCE(a.hrg_total, 0))) AS grand_total,
                (COALESCE(a.hrg_total_diskon, 0) + ((COALESCE(p.tax, 0) / 100) * COALESCE(a.hrg_total_diskon, 0))) AS grand_total_diskon
            ')
            ->from('tbpo_detail_po a')
            ->join('tbpo_po p', 'p.kd_po = a.kd_po', 'left')
            ->where('a.tgl_transaksi IS NOT NULL', null, false)
            ->where("a.tgl_transaksi <>", '0000-00-00')
            ->order_by('a.tgl_transaksi', 'DESC')
            ->order_by('a.kd_po', 'DESC')
            ->get()
            ->result_array();

        if (empty($rows)) {
            return $rows;
        }

        $kdPoList = array_values(array_unique(array_filter(array_column($rows, 'kd_po'))));
        $discountMap = $this->get_discount_history_map($kdPoList);

        foreach ($rows as &$row) {
            $kdPo = $row['kd_po'] ?? '';
            $row['histori_diskon'] = $discountMap[$kdPo] ?? [];
        }
        unset($row);

        return $rows;
    }

    private function get_barang_select_sql()
    {
        $select = [
            'a.id_barang',
            'a.kode_barang',
            'a.kd_suplier',
            $this->db->field_exists('nama_suplier', 'tbpo_suplier') ? "COALESCE(s.nama_suplier, '') AS nama_suplier" : "'' AS nama_suplier",
            'a.nama_barang',
            $this->db->field_exists('isi', 'tbpo_barang') ? 'a.isi' : '0 AS isi',
            $this->db->field_exists('kemasan', 'tbpo_barang') ? 'a.kemasan' : '0 AS kemasan',
            $this->db->field_exists('merk_barang', 'tbpo_barang') ? 'a.merk_barang' : "'' AS merk_barang",
            $this->db->field_exists('bahan_aktif', 'tbpo_barang') ? 'a.bahan_aktif' : ($this->db->field_exists('bhn_aktif', 'tbpo_barang') ? 'a.bhn_aktif AS bahan_aktif' : "'' AS bahan_aktif"),
            $this->db->field_exists('satuan', 'tbpo_barang') ? 'a.satuan' : "'' AS satuan",
            $this->db->field_exists('satuan_qty', 'tbpo_barang') ? 'a.satuan_qty' : 'NULL AS satuan_qty',
            $this->db->field_exists('panjang', 'tbpo_barang') ? 'a.panjang' : '0 AS panjang',
            $this->db->field_exists('lebar', 'tbpo_barang') ? 'a.lebar' : '0 AS lebar',
            $this->db->field_exists('tinggi', 'tbpo_barang') ? 'a.tinggi' : '0 AS tinggi',
            $this->db->field_exists('hasil_dimensi', 'tbpo_barang') ? 'a.hasil_dimensi' : '0 AS hasil_dimensi',
            $this->db->field_exists('berat', 'tbpo_barang') ? 'a.berat' : '0 AS berat',
            $this->db->field_exists('stock_minimum', 'tbpo_barang') ? 'a.stock_minimum' : '0 AS stock_minimum',
            $this->db->field_exists('kelompok_barang', 'tbpo_barang') ? 'a.kelompok_barang' : "'' AS kelompok_barang",
            $this->db->field_exists('kategori_barang', 'tbpo_barang') ? 'a.kategori_barang' : "'' AS kategori_barang",
            $this->db->field_exists('produk_fokus', 'tbpo_barang') ? 'a.produk_fokus' : "'' AS produk_fokus",
            $this->db->field_exists('is_active', 'tbpo_barang') ? 'a.is_active' : "'T' AS is_active",
            $this->db->field_exists('is_lot', 'tbpo_barang') ? 'a.is_lot' : "'F' AS is_lot",
            $this->db->field_exists('create_at', 'tbpo_barang') ? 'a.create_at' : 'NULL AS create_at',
            $this->db->field_exists('update_at', 'tbpo_barang') ? 'a.update_at' : 'NULL AS update_at',
        ];

        return implode(",\n", $select);
    }

    private function join_barang_supplier()
    {
        if ($this->db->table_exists('tbpo_suplier') && $this->db->field_exists('kd_suplier', 'tbpo_suplier')) {
            $this->db->join('tbpo_suplier s', "s.kd_suplier = a.kd_suplier AND a.kd_suplier <> ''", 'left');
        }
    }

    private function apply_barang_filters($params)
    {
        $kodeBarang = isset($params['kode_barang']) ? trim((string)$params['kode_barang']) : '';
        if ($kodeBarang !== '') {
            $this->db->where('a.kode_barang', $kodeBarang);
        }

        $kdSuplier = isset($params['kd_suplier']) ? trim((string)$params['kd_suplier']) : '';
        if ($kdSuplier !== '') {
            $this->db->where('a.kd_suplier', $kdSuplier);
        }

        $search = isset($params['search']) ? trim((string)$params['search']) : '';
        if ($search !== '') {
            $this->db->group_start();
            $this->db->like('a.kode_barang', $search);
            $this->db->or_like('a.nama_barang', $search);
            $this->db->or_like('a.kd_suplier', $search);
            if ($this->db->field_exists('merk_barang', 'tbpo_barang')) {
                $this->db->or_like('a.merk_barang', $search);
            }
            if ($this->db->field_exists('nama_suplier', 'tbpo_suplier')) {
                $this->db->or_like('s.nama_suplier', $search);
            }
            $this->db->group_end();
        }
    }

    private function get_discount_history_map(array $kdPoList)
    {
        if (empty($kdPoList)) {
            return [];
        }

        $map = [];

        foreach (array_chunk($kdPoList, 500) as $chunk) {
            $rows = $this->db
                ->select('
                    d.id_diskon,
                    d.kd_po,
                    d.kd_suplier,
                    p.no_po,
                    p.tgl_transaksi,
                    s.nama_suplier,
                    d.keterangan,
                    d.nominal
                ')
                ->from('tbpo_diskon d')
                ->join('tbpo_po p', 'p.kd_po = d.kd_po', 'left')
                ->join('tbpo_suplier s', 's.kd_suplier = d.kd_suplier', 'left')
                ->where_in('d.kd_po', $chunk)
                ->order_by('d.kd_po', 'ASC')
                ->order_by('d.id_diskon', 'ASC')
                ->get()
                ->result_array();

            foreach ($rows as $row) {
                $map[$row['kd_po']][] = $row;
            }
        }

        return $map;
    }
}
