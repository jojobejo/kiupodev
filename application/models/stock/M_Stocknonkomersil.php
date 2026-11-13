<?php
defined('BASEPATH') or exit('No direct script access allowed');
/**
 *
 */
class M_Stocknonkomersil  extends CI_Model
{
    private $stockLifoView = false;
    function __construct()
    {
        parent::__construct();
    }

    public function getAllstockbaran()
    {
        return $this->db->get('')->result();
    }

    private function stock_base_sql()
    {
        $hasLifo = $this->stockLifoView && $this->db->table_exists('tbpo_stock_lifo_batch_nk');
        // Keep the AJAX row schema stable even when the LIFO view is not
        // available for the current user.  A browser with a cached DataTables
        // configuration can still request these properties; omitting them
        // causes "Requested unknown parameter" and prevents the stock table
        // from rendering.
        $lifoSelect = $hasLifo ? ",
            COALESCE(lifo.batch_aktif, 0) AS batch_lifo_aktif,
            COALESCE(lifo.qty_perlu_harga, 0) AS qty_lifo_perlu_harga,
            COALESCE(lifo.nilai_lifo, 0) AS nilai_lifo,
            (SELECT lb.harga_satuan FROM tbpo_stock_lifo_batch_nk lb
                WHERE lb.kd_barang=a.kd_barang AND lb.status_batch='AKTIF' AND lb.status_harga='VALID' AND lb.qty_sisa > 0
                ORDER BY lb.tgl_efektif DESC, lb.id_batch DESC LIMIT 1) AS harga_lifo_aktif"
            : ', 0 AS batch_lifo_aktif, 0 AS qty_lifo_perlu_harga, 0 AS nilai_lifo, NULL AS harga_lifo_aktif';
        $lifoJoin = $hasLifo ? " LEFT JOIN (
            SELECT kd_barang,
                SUM(CASE WHEN status_batch='AKTIF' AND qty_sisa > 0 THEN 1 ELSE 0 END) AS batch_aktif,
                SUM(CASE WHEN status_batch='AKTIF' AND status_harga='PERLU_HARGA' THEN qty_sisa ELSE 0 END) AS qty_perlu_harga,
                SUM(CASE WHEN status_batch='AKTIF' AND status_harga='VALID' THEN qty_sisa * harga_satuan ELSE 0 END) AS nilai_lifo
            FROM tbpo_stock_lifo_batch_nk GROUP BY kd_barang
        ) lifo ON lifo.kd_barang=a.kd_barang" : '';
        $hasRealisasi = $this->db->table_exists('tbpo_realisasi_detail_po_nk');
        $hargaNyata = $hasRealisasi
            ? 'COALESCE(NULLIF(r.harga_nyata, 0), NULLIF(d.hrg_nyata, 0))'
            : 'NULLIF(d.hrg_nyata, 0)';
        $realisasiJoin = $hasRealisasi
            ? 'LEFT JOIN tbpo_realisasi_detail_po_nk r ON r.id_det_po_nk=d.id_det_po_nk'
            : '';
        $hargaTerakhirPembelian = "(SELECT CASE WHEN {$hargaNyata} IS NOT NULL THEN {$hargaNyata} ELSE d.hrg_satuan END
            FROM tbpo_detail_po_nk d
            LEFT JOIN tbpo_po_nk p ON p.kd_po_nk=d.kd_po_nk
            {$realisasiJoin}
            WHERE (d.kd_bsys=a.kd_br_adm OR d.kd_barang=a.kd_barang)
              AND ({$hargaNyata} IS NOT NULL OR d.hrg_satuan > 0)
            ORDER BY p.tgl_transaksi DESC, d.id_det_po_nk DESC LIMIT 1)";
        return "SELECT
            a.kd_barang AS kode_barangs,
            a.kd_br_adm AS kode_barang,
            a.nama_barang,
            a.descnk AS deskripsi,
            a.gbr_barang,
            COALESCE(tr.qty_in, 0) AS qty_in,
            COALESCE(tr.qty_out, 0) AS qty_out,
            (COALESCE(tr.qty_in, 0) - COALESCE(tr.qty_out, 0)) AS qty_ready,
            b.id_satuan,
            b.nm_satuan AS satuan,
            a.id_brg_nk,
            a.kat_barang,
            a.kd_lokasi AS id_lokasi,
            l.nama_lokasi,
            {$hargaTerakhirPembelian} AS harga_terakhir_pembelian,
            COALESCE(a.minimum_stock, 0) AS minimum_stock,
            CASE
                WHEN COALESCE(a.minimum_stock, 0) > 0 THEN GREATEST(COALESCE(a.minimum_stock, 0) - (COALESCE(tr.qty_in, 0) - COALESCE(tr.qty_out, 0)), 0)
                ELSE 0
            END AS qty_saran_po,
            CASE
                WHEN (COALESCE(tr.qty_in, 0) - COALESCE(tr.qty_out, 0)) <= 0 THEN 'habis'
                WHEN (COALESCE(tr.qty_in, 0) - COALESCE(tr.qty_out, 0)) <= COALESCE(a.minimum_stock, 0) THEN 'hampir_habis'
                ELSE 'aman'
            END AS status_stock{$lifoSelect}
        FROM tbpo_barang_nk a
        JOIN tbpo_satuan b ON b.id_satuan = a.satuan
        LEFT JOIN tbpo_barang_nk_lokasi l ON l.id_lokasi = a.kd_lokasi
        LEFT JOIN (
            SELECT
                kd_barang,
                SUM(CASE WHEN kd_akun IN ('11511', '11513') THEN tr_qty ELSE 0 END) AS qty_in,
                SUM(CASE WHEN kd_akun IN ('11512', '11514') THEN tr_qty ELSE 0 END) AS qty_out
            FROM tbpo_transaksi
            WHERE kd_akun IN ('11511', '11512', '11513', '11514')
            GROUP BY kd_barang
        ) tr ON tr.kd_barang = a.kd_barang{$lifoJoin}";
    }

    private function stock_filter_sql($params, &$binds)
    {
        $where = [];
        $lokasi = isset($params['lokasi']) ? trim((string)$params['lokasi']) : '';
        $status_stock = isset($params['status_stock']) ? trim((string)$params['status_stock']) : '';
        $search = isset($params['search']) ? trim((string)$params['search']) : '';

        if ($lokasi !== '') {
            if (ctype_digit($lokasi)) {
                $where[] = 'stock.id_lokasi = ?';
                $binds[] = (int)$lokasi;
            } else {
                $where[] = 'stock.nama_lokasi = ?';
                $binds[] = $lokasi;
            }
        }

        if ($search !== '') {
            $like = '%' . $search . '%';
            $where[] = '(stock.kode_barang LIKE ? OR stock.kode_barangs LIKE ? OR stock.nama_barang LIKE ? OR stock.deskripsi LIKE ? OR stock.satuan LIKE ? OR stock.nama_lokasi LIKE ?)';
            array_push($binds, $like, $like, $like, $like, $like, $like);
        }

        if ($status_stock === 'perlu_po') {
            $where[] = "stock.status_stock IN ('habis', 'hampir_habis')";
        } elseif (in_array($status_stock, ['habis', 'hampir_habis', 'aman'], true)) {
            $where[] = 'stock.status_stock = ?';
            $binds[] = $status_stock;
        }

        return $where ? ' WHERE ' . implode(' AND ', $where) : '';
    }

    private function stock_order_sql($column, $direction)
    {
        $columns = [
            0 => 'stock.kode_barang',
            1 => 'stock.nama_barang',
            2 => 'stock.deskripsi',
            3 => 'stock.qty_ready',
            4 => 'stock.harga_terakhir_pembelian',
            5 => 'stock.qty_lifo_perlu_harga',
            6 => 'stock.minimum_stock',
            7 => 'stock.status_stock',
            8 => 'stock.satuan',
            9 => 'stock.nama_lokasi'
        ];
        if (empty($this->stockLifoView)) {
            $columns = [
                0 => 'stock.kode_barang',
                1 => 'stock.nama_barang',
                2 => 'stock.deskripsi',
                3 => 'stock.qty_ready',
                4 => 'stock.harga_terakhir_pembelian',
                5 => 'stock.minimum_stock',
                6 => 'stock.status_stock',
                7 => 'stock.satuan',
                8 => 'stock.nama_lokasi'
            ];
        }

        $order_column = isset($columns[$column]) ? $columns[$column] : $columns[0];
        $order_dir = strtolower($direction) === 'desc' ? 'DESC' : 'ASC';

        return " ORDER BY {$order_column} {$order_dir}";
    }

    private function has_stock_filters($params)
    {
        return trim((string)(isset($params['lokasi']) ? $params['lokasi'] : '')) !== ''
            || trim((string)(isset($params['status_stock']) ? $params['status_stock'] : '')) !== ''
            || trim((string)(isset($params['search']) ? $params['search'] : '')) !== '';
    }

    public function get_stock_datatable($params)
    {
        $this->stockLifoView = !empty($params['lifo_view']);
        $base_sql = $this->stock_base_sql();
        $binds = [];
        $filter_sql = $this->stock_filter_sql($params, $binds);
        $order_sql = $this->stock_order_sql(
            isset($params['order_column']) ? (int)$params['order_column'] : 0,
            isset($params['order_dir']) ? (string)$params['order_dir'] : 'asc'
        );
        $start = isset($params['start']) ? max(0, (int)$params['start']) : 0;
        $length = isset($params['length']) ? (int)$params['length'] : 10;
        $length = ($length > 0 && $length <= 100) ? $length : 10;

        $data_sql = "SELECT stock.* FROM ({$base_sql}) stock{$filter_sql}{$order_sql} LIMIT ?, ?";
        $data_binds = array_merge($binds, [$start, $length]);

        $count_sql = "SELECT COUNT(*) AS total FROM ({$base_sql}) stock{$filter_sql}";
        $total_sql = 'SELECT COUNT(*) AS total FROM tbpo_barang_nk';

        $records_total = (int)$this->db->query($total_sql)->row()->total;
        $records_filtered = $this->has_stock_filters($params)
            ? (int)$this->db->query($count_sql, $binds)->row()->total
            : $records_total;
        $data = $this->db->query($data_sql, $data_binds)->result();

        return [
            'records_total' => $records_total,
            'records_filtered' => $records_filtered,
            'data' => $data
        ];
    }

    public function v_stock($lokasi = '', $status_stock = '')
    {
        $params = [
            'lokasi' => $lokasi,
            'status_stock' => $status_stock,
            'search' => ''
        ];
        $binds = [];
        $base_sql = $this->stock_base_sql();
        $filter_sql = $this->stock_filter_sql($params, $binds);

        return $this->db->query("SELECT stock.* FROM ({$base_sql}) stock{$filter_sql} ORDER BY stock.kode_barang ASC", $binds)->result();
    }

    public function v_stockzero()
    {
        return $this->db->query("SELECT	
		a.id_satuan		AS idsat,
        a.kode_barangs  as kdbarangsys,
        a.kode_barang   as kdbarang,
        a.nama_barang   as nmbarang,
        a.qty_ready     as qtyready,
        a.satuan        as satuan,
        a.deskripsi		as desk,
        a.kat_barang 	as katbr
        FROM v_stockbarangnk a
        WHERE a.qty_ready <=0
        
        ");
    }
    public function draftpo()
    {
        return $this->db->query("SELECT 
        a.*,b.nm_satuan
        FROM tbpo_tmp_item_nk a
        JOIN tbpo_satuan b ON b.id_satuan = a.satuan
        WHERE a.jnis_po = '3'
        ");
    }

    public function getallbarang()
    {
        return $this->db->query("SELECT 
            * FROM tbpo_barang_nk a
            JOIN tbpo_kat_br b ON b.kd_kat = a.kat_barang
            JOIN tbpo_satuan c ON c.id_satuan = a.satuan
        ");
    }
    public function getAllstocknk()
    {
        return $this->db->query("SELECT 
            a.kd_br_adm AS kode_adm, 
            a.kd_barang as kode_sys, 
            a.nama_barang, 
            a.descnk,
            COALESCE(SUM(c.tr_qty),0) AS qty,
            b.nm_satuan,
            a.gbr_barang,
            a.id_brg_nk
            FROM tbpo_barang_nk a
            JOIN tbpo_satuan b ON b.id_satuan = a.satuan
            LEFT JOIN tbpo_transaksi c ON c.kd_barangsys = a.kd_barang
            GROUP BY a.kd_barang  
        ");
    }
    public function getreqpic($lv)
    {
        return $this->db->query("SELECT * 
            FROM tbpo_req_masterbarang a
            JOIN tbpo_satuan b ON b.id_satuan = a.satuan
            JOIN tbpo_user c ON c.kode_user COLLATE utf8mb4_general_ci = a.req_by COLLATE utf8mb4_general_ci
            WHERE a.req_by = ?
        ", array($lv));
    }

    public function getSatuan()
    {
        return $this->db->get('tbpo_satuan')->result();
    }

    public function get_master_lokasi()
    {
        $this->db->select('*');
        $this->db->from('tbpo_barang_nk_lokasi');
        $this->db->order_by('id_lokasi', 'DESC');
        return $this->db->get()->result();
    }

    public function add_master_lokasi($data)
    {
        return $this->db->insert('tbpo_barang_nk_lokasi', $data);
    }

    public function update_master_lokasi($id, $data)
    {
        $this->db->where('id_lokasi', $id);
        return $this->db->update('tbpo_barang_nk_lokasi', $data);
    }

    public function delete_master_lokasi($id)
    {
        $this->db->where('id_lokasi', $id);
        return $this->db->delete('tbpo_barang_nk_lokasi');
    }

    public function update_lokasi_barang($kode_barang, $id_lokasi)
    {
        $this->db->where('kd_br_adm', $kode_barang);
        return $this->db->update('tbpo_barang_nk', [
            'kd_lokasi' => $id_lokasi
        ]);
    }

    public function get_stock_opname_headers()
    {
        if (!$this->db->table_exists('tbpo_stock_opname_nk')) {
            return array();
        }

        $this->db->select('*');
        $this->db->from('tbpo_stock_opname_nk');
        $this->db->order_by('tgl_opname', 'DESC');
        $this->db->order_by('id_stock_opname', 'DESC');
        return $this->db->get()->result();
    }

    public function get_stock_opname_header($id)
    {
        if (!$this->db->table_exists('tbpo_stock_opname_nk')) {
            return null;
        }

        $this->db->select('*');
        $this->db->from('tbpo_stock_opname_nk');
        $this->db->where('id_stock_opname', $id);
        return $this->db->get()->row();
    }

    public function get_stock_opname_detail($id)
    {
        if (!$this->db->table_exists('tbpo_stock_opname_nk_detail')) {
            return array();
        }

        $this->db->select('*');
        $this->db->from('tbpo_stock_opname_nk_detail');
        $this->db->where('id_stock_opname', $id);
        $this->db->order_by('nama_barang', 'ASC');
        return $this->db->get()->result();
    }

    public function insert_stock_opname($header, $details, $transactions)
    {
        if (!$this->db->table_exists('tbpo_stock_opname_nk') || !$this->db->table_exists('tbpo_stock_opname_nk_detail')) {
            return false;
        }

        $this->db->trans_start();
        $this->db->insert('tbpo_stock_opname_nk', $header);
        $id = $this->db->insert_id();

        foreach ($details as $detail) {
            $detail['id_stock_opname'] = $id;
            $this->db->insert('tbpo_stock_opname_nk_detail', $detail);
        }

        foreach ($transactions as $transaction) {
            $transaction['kd_po_nk'] = 'OPNAMENK' . $id;
            $this->db->insert('tbpo_transaksi', $transaction);
        }

        $this->db->trans_complete();

        return $this->db->trans_status() ? $id : false;
    }

    public function update_minimum_stock($kode_barang, $minimum_stock)
    {
        $this->db->where('kd_barang', $kode_barang);
        return $this->db->update('tbpo_barang_nk', [
            'minimum_stock' => $minimum_stock
        ]);
    }

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

    function insttransaksi($data)
    {
        $this->db->insert('tbpo_transaksi', $data);
    }

    public function get_data_item($kd)
    {
        return $this->db->query("SELECT
        a.kd_barang AS kode_sistem ,
        a.kd_br_adm AS kode_barang,
        a.nama_barang AS nama_barang,
        a.descnk AS deskripsi,
        d.qty_ready AS qty_ready,
        b.nm_satuan AS satuan,
        a.kat_barang AS katbr,
        b.id_satuan AS satuanid
        FROM tbpo_barang_nk a
        JOIN tbpo_satuan b ON b.id_satuan = a.satuan
        JOIN tbpo_kat_br c ON c.kd_kat = a.kat_barang
        JOIN v_stockbarangnk d ON d.kode_barangs = a.kd_barang
        WHERE a.kd_barang = '$kd'
        ");
    }

    // public function get_item_bytgl($tgl1, $tgl2, $kd)
    // {
    //     return $this->db->query("SELECT 
    //     a.kd_barang,
    //     b.nama_barang,
    //     SUM(CASE WHEN a.kd_akun IN ('11511', '11513') THEN a.tr_qty ELSE 0 END) AS qty_in,
    //     SUM(CASE WHEN a.kd_akun IN ('11512', '11514') THEN a.tr_qty ELSE 0 END) AS qty_out,
    //     (SUM(CASE WHEN a.kd_akun IN ('11511', '11513') THEN a.tr_qty ELSE 0 END) - 
    //     SUM(CASE WHEN a.kd_akun IN ('11512', '11514') THEN a.tr_qty ELSE 0 END)) AS hasil
    // FROM tbpo_transaksi a
    // JOIN tbpo_barang_nk b ON b.kd_barang = a.kd_barang
    // WHERE a.tgl_transaksi BETWEEN '$tgl1' AND '$tgl2'
    // AND a.kd_barang = '$kd'
    // GROUP BY a.kd_barang, b.nama_barang;
    //     ");
    // }

    public function get_item_bytgl($tgl1, $tgl2, $kd)
    {
        return $this->db->query("SELECT 
        a.kd_barang AS kode_sistem,
        a.kd_br_adm AS kode_barang,
        a.nama_barang AS nama_barang,
        a.descnk AS deskripsi,
        d.qty_ready AS qty_ready,
        b.nm_satuan AS satuan,
        a.kat_barang AS katbr,
        b.id_satuan AS satuanid,
        COALESCE(SUM(CASE WHEN t.kd_akun IN ('11511', '11513') THEN t.tr_qty ELSE 0 END), 0) AS qty_in,
        COALESCE(SUM(CASE WHEN t.kd_akun IN ('11512', '11514') THEN t.tr_qty ELSE 0 END), 0) AS qty_out,
        (COALESCE(SUM(CASE WHEN t.kd_akun IN ('11511', '11513') THEN t.tr_qty ELSE 0 END), 0) -
        COALESCE(SUM(CASE WHEN t.kd_akun IN ('11512', '11514') THEN t.tr_qty ELSE 0 END), 0)) AS hasil
        FROM tbpo_barang_nk a
        JOIN tbpo_satuan b ON b.id_satuan = a.satuan
        JOIN tbpo_kat_br c ON c.kd_kat = a.kat_barang
        JOIN v_stockbarangnk d ON d.kode_barangs = a.kd_barang
        LEFT JOIN tbpo_transaksi t ON t.kd_barang = a.kd_barang
        AND t.tgl_transaksi BETWEEN '$tgl1' AND '$tgl2' 
        WHERE a.kd_barang = '$kd'
        GROUP BY a.kd_barang, a.kd_br_adm, a.nama_barang, a.descnk, d.qty_ready, b.nm_satuan, a.kat_barang, b.id_satuan;");
    }

    public function getStockByDate($start_date, $end_date)
    {
        $this->db->select('*');
        $this->db->from('tbpo_transaksi');

        if (!empty($start_date) && !empty($end_date)) {
            $this->db->where('tgl_transaksi >=', $start_date);
            $this->db->where('tgl_transaksi <=', $end_date);
        }

        return $this->db->get()->result_array();
    }

    public function get_data_itemtr($id)
    {
        return $this->db->query("SELECT
        a.*
        FROM tbpo_transaksi a
        WHERE a.id_transnk = '$id'
        ");
    }
    public function get_detail_transaksi_itm_date($tgl1, $tgl2, $kd, $limit = null, $offset = 0)
    {
        return $this->get_detail_transaksi_itm($kd, $limit, $offset, $tgl1, $tgl2);
    }

    public function count_detail_transaksi_itm($kd, $tgl1 = null, $tgl2 = null)
    {
        $this->db->from('tbpo_transaksi');
        $this->db->where('kd_barang', $kd);
        if ($tgl1 !== null && $tgl2 !== null) {
            $this->db->where('tgl_transaksi >=', $tgl1);
            $this->db->where('tgl_transaksi <=', $tgl2);
        }

        return (int) $this->db->count_all_results();
    }

    public function get_detail_transaksi_itm($kd, $limit = null, $offset = 0, $tgl1 = null, $tgl2 = null)
    {
        $lifoSelect = ($this->db->table_exists('tbpo_stock_lifo_allocation') && $this->db->table_exists('tbpo_stock_lifo_batch_nk'))
            ? ",(SELECT CASE WHEN SUM(la.qty_alokasi) > 0 THEN SUM(la.nilai_alokasi) / SUM(la.qty_alokasi) ELSE NULL END FROM tbpo_stock_lifo_allocation la WHERE la.id_transnk_keluar=a.id_transnk) AS nominal_lifo
        ,(SELECT GROUP_CONCAT(CONCAT(lb.referensi_sumber, ' (qty ', la.qty_alokasi, ')') SEPARATOR '; ') FROM tbpo_stock_lifo_allocation la JOIN tbpo_stock_lifo_batch_nk lb ON lb.id_batch=la.id_batch WHERE la.id_transnk_keluar=a.id_transnk) AS batch_lifo"
            : ',NULL AS nominal_lifo, NULL AS batch_lifo';
        $date_filter = '';
        $params = array($kd);
        if ($tgl1 !== null && $tgl2 !== null) {
            $date_filter = ' AND a.tgl_transaksi BETWEEN ? AND ?';
            $params[] = $tgl1;
            $params[] = $tgl2;
        }

        $limit_filter = '';
        if ($limit !== null) {
            $limit_filter = ' LIMIT ' . (int) $limit . ' OFFSET ' . max(0, (int) $offset);
        }

        return $this->db->query("SELECT
        a.id_transnk AS id,
        a.kd_po_nk AS kd_transaksi,
        a.kd_akun AS kd_akun,
        a.tgl_transaksi AS tgl_transaksi,
        a.tr_qty AS qty,
        b.nm_satuan AS nm_satuan,
        a.kd_barangsys AS kd_barang,
        a.kd_barang AS kd_barangs,
        f.nama_user AS inpt,
        f.aksess_lv AS lvadm,
        e.aksess_lv AS lvusr,
        e.nama_user AS nmreq,
        e.departement AS dep,
        a.keterangan as ket,
        CASE
            WHEN d.kd_po_nk IS NOT NULL THEN 'PEMBELIAN'
            WHEN c.kd_po_nk IS NOT NULL THEN 'PENGAMBILAN'
            ELSE ''
        END AS tipe_referensi,
        CASE
            WHEN d.kd_po_nk IS NOT NULL THEN d.kd_po_nk
            WHEN c.kd_po_nk IS NOT NULL THEN c.kd_po_nk
            ELSE a.kd_po_nk
        END AS kode_referensi
        {$lifoSelect}
        FROM tbpo_transaksi a 
        JOIN tbpo_satuan b ON b.id_satuan = a.satuan
        /*
         * Legacy tables on production were imported with
         * utf8mb4_general_ci while newer tables use utf8mb4_uca1400_ai_ci.
         * Explicitly using the legacy collation at each string join keeps
         * this history query working until the schema is normalized.
         */
        LEFT JOIN tbpo_req_nk c ON c.kd_po_nk COLLATE utf8mb4_general_ci = a.kd_po_nk COLLATE utf8mb4_general_ci
        LEFT JOIN tbpo_po_nk d ON d.kd_po_nk COLLATE utf8mb4_general_ci = a.kd_po_nk COLLATE utf8mb4_general_ci
        LEFT JOIN tbpo_user e ON e.kode_user COLLATE utf8mb4_general_ci = a.req_by COLLATE utf8mb4_general_ci
        LEFT JOIN tbpo_user f ON f.kode_user COLLATE utf8mb4_general_ci = a.inputer COLLATE utf8mb4_general_ci
        WHERE a.kd_barang = ?{$date_filter}
        ORDER BY a.id_transnk DESC
        {$limit_filter}", $params);
    }
    public function qtyready($kd)
    {
        $cd1 = $this->db->query("SELECT a.qty_ready FROM v_stockbarangnk a WHERE a.kode_barangs = '$kd'");
        return $cd1;
    }
    public function get_detail_br_rev_buy($kdponk, $kdbr)
    {
        return $this->db->query("SELECT
        a.id_transnk AS id_tr,
        b.id_det_po_nk AS id_po,
        b.kd_po_nk AS kd_po,
        b.kd_barang as kd_barang,
        b.nama_barang AS nm_barang,
        a.tr_qty AS qty_tr,
        b.qty AS qty_det
        FROM tbpo_transaksi a
        JOIN tbpo_detail_po_nk b ON b.kd_po_nk = a.kd_po_nk
        WHERE b.kd_po_nk = '$kdponk' AND b.kd_bsys = '$kdbr'
        GROUP BY b.kd_po_nk
        ");
    }
    public function get_detail_br_rev_req($kdponk, $kdbr)
    {
        return $this->db->query("SELECT
        a.id_transnk AS id_tr,
        b.id_det_po_nk AS id_po,
        b.kd_po_nk AS kd_po,
        b.kd_barang as kd_barang,
        b.nama_barang AS nm_barang,
        a.tr_qty AS qty_tr,
        b.qty AS qty_det
        FROM tbpo_transaksi a
        JOIN tbpo_detail_req b ON b.kd_po_nk = a.kd_po_nk
        WHERE b.kd_po_nk = '$kdponk' AND b.kd_bsys = '$kdbr'
        ");
    }
    public function getlistnkreq()
    {
        return $this->db->query("SELECT 
        a.kode_barang AS kode_adm,
        a.kode_barangs AS kode_sys,
        a.nama_barang,
        a.deskripsi AS descnk,
        b.nm_satuan,
        a.gbr_barang,
        a.id_brg_nk,
        a.kat_barang,
        a.qty_ready
        FROM v_stockbarangnk a
        JOIN tbpo_satuan b ON b.id_satuan = a.id_satuan
    ");
    }
    function getgeneratekd()
    {
        $cd = $this->db->query("SELECT MAX(RIGHT(kd_barang,4)) AS kd_max FROM tbpo_generate_kd WHERE DATE(create_at)=CURDATE()");
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
        $kdnk1 = 'ADJQTY' . date('dmy') . $kd;
        return $kdnk1;
    }
    public function insrt_note($data)
    {
        $this->db->insert('tbpo_note_direktur', $data);
    }
    public function inputtmprestock($data)
    {
        $this->db->insert('tbpo_tmp_item_nk', $data);
    }
    public function get_note($kd)
    {
        $this->db->select('*');
        $this->db->from('tbpo_note_direktur a');
        $this->db->where('kd_po', $kd);
        return $this->db->get()->result();
    }
    public function deldettransaksi($id)
    {
        $this->db->where('id_transnk', $id);
        return $this->db->delete('tbpo_transaksi');
    }
    public function insert_trash_bin_tr($data)
    {
        $this->db->insert('tbpo_transaksi_trashbin', $data);
    }
    public function get_data_trash($kd)
    {
        return $this->db->query("SELECT
        a.*,
        a.id_trashbin as id_trashbin,
        b.departement AS departemen,
        b.nama_user AS nm_user
        FROM tbpo_transaksi_trashbin a
        /* tbpo_transaksi_trashbin and tbpo_user use different production collations. */
        JOIN tbpo_user b ON b.kode_user COLLATE utf8mb4_general_ci = a.req_by COLLATE utf8mb4_general_ci
        WHERE a.kd_barang = ?
         ", array($kd));
    }
    public function delete_trash($id)
    {
        $this->db->where('id_trashbin', $id);
        return $this->db->delete('tbpo_transaksi_trashbin');
    }
    public function get_data_trashid($id)
    {
        return $this->db->query("SELECT
        a.*
        FROM tbpo_transaksi_trashbin a
        WHERE a.id_trashbin = '$id'
        ");
    }

    // FITER TGL TRANSAKSI NON KOMERSIL

    public function get_filtered_data($tgl1, $tgl2)
    {
        $this->db->select('a.kd_akun AS jn_transaksi, a.tgl_transaksi, c.departement, c.nama_user, b.nama_barang, a.keterangan, a.tr_qty AS qty');
        $this->db->from('tbpo_transaksi a');
        $this->db->join('tbpo_barang_nk b', 'b.kd_barang = a.kd_barang');
        $this->db->join(
            'tbpo_user c',
            'c.kode_user COLLATE utf8mb4_general_ci = a.req_by COLLATE utf8mb4_general_ci',
            '',
            false
        );
        $this->db->where('a.tgl_transaksi >=', $tgl1);
        $this->db->where('a.tgl_transaksi <=', $tgl2);
        $query = $this->db->get();
        return $query->result();
    }
}

// CREATE VIEW STOCK

// CREATE VIEW v_stockbarangnk AS
// SELECT
// x.kode_barang,
// x.nama_barang,
// x.deskripsi,
// COALESCE(x.qty_in,0) AS qty_in,
// COALESCE(x.qty_out,0) AS qty_out,
// (COALESCE(x.qty_in,0)-COALESCE(x.qty_out,0)) AS qty_ready
// FROM
// (	
//     SELECT
//     a.kd_br_adm AS kode_barang,
//     a.nama_barang AS nama_barang,
//     a.descnk AS deskripsi,
//     (SELECT SUM(d.tr_qty) FROM tbpo_transaksi d WHERE d.kd_barang = a.kd_br_adm AND d.kd_akun = '11512' GROUP BY d.kd_barang)AS qty_out,
//     (SELECT SUM(e.tr_qty) FROM tbpo_transaksi e WHERE e.kd_barang = a.kd_br_adm AND e.kd_akun = '11511' GROUP BY e.kd_barang ) AS qty_in
//     FROM tbpo_barang_nk a
//     JOIN tbpo_satuan b ON b.id_satuan = a.satuan
//     JOIN tbpo_kat_br c ON c.kd_kat = a.kat_barang
//     GROUP BY a.kd_br_adm
// ) AS x

//  QUERY VIEW 

// select
//     x.kode_barangs AS kode_barangs,
//     x.kode_barang AS kode_barang,
//     x.nama_barang AS nama_barang,
//     x.deskripsi AS deskripsi,
//     x.gbr_barang AS gbr_barang,
//     (coalesce(x.qty_in, 0) + coalesce(x.adjqty_in, 0)) AS qty_in,
//     (coalesce(x.qty_out, 0) + coalesce(x.adjqty_out, 0)) AS qty_out,
//     ((coalesce(x.qty_in, 0) + coalesce(x.adjqty_in, 0)) - (coalesce(x.qty_out, 0) + coalesce(x.adjqty_out, 0))) AS qty_ready,
//     x.id_s AS id_satuan,
//     x.satuan AS satuan,
//     x.id_brg_nk AS id_brg_nk,
//     x.kat_barang AS kat_barang
// from
//     (
//         select
//             a.kd_barang AS kode_barangs,
//             a.kd_br_adm AS kode_barang,
//             a.nama_barang AS nama_barang,
//             a.descnk AS deskripsi,
//             b.id_satuan AS id_s,
//             b.nm_satuan AS satuan,
//             a.gbr_barang AS gbr_barang,
//             a.id_brg_nk AS id_brg_nk,
//             a.kat_barang AS kat_barang,
//             (
//                 select
//                     sum(d.tr_qty)
//                 from
//                     kiucoid_po_dev.tbpo_transaksi d
//                 where
//                     d.kd_barang = a.kd_br_adm
//                     and d.kd_akun = '11512'
//                 group by
//                     d.kd_barang
//             ) AS qty_out,
//             (
//                 select
//                     sum(d.tr_qty)
//                 from
//                     kiucoid_po_dev.tbpo_transaksi d
//                 where
//                     d.kd_barang = a.kd_br_adm
//                     and d.kd_akun = '11514'
//                 group by
//                     d.kd_barang
//             ) AS adjqty_out,
//             (
//                 select
//                     sum(e.tr_qty)
//                 from
//                     kiucoid_po_dev.tbpo_transaksi e
//                 where
//                     e.kd_barang = a.kd_br_adm
//                     and e.kd_akun = '11511'
//                 group by
//                     e.kd_barang
//             ) AS qty_in,
//             (
//                 select
//                     sum(e.tr_qty)
//                 from
//                     kiucoid_po_dev.tbpo_transaksi e
//                 where
//                     e.kd_barang = a.kd_br_adm
//                     and e.kd_akun = '11513'
//                 group by
//                     e.kd_barang
//             ) AS adjqty_in
//         from
//             (
//                 (
//                     kiucoid_po_dev.tbpo_barang_nk a
//                     join kiucoid_po_dev.tbpo_satuan b on(b.id_satuan = a.satuan)
//                 )
//                 join kiucoid_po_dev.tbpo_kat_br c on(c.kd_kat = a.kat_barang)
//             )
//         group by
//             a.kd_br_adm
//     ) x
