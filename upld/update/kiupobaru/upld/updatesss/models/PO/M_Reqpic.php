<?php
defined('BASEPATH') or exit('No direct script access allowed');
/**
 *
 */
class M_Reqpic extends CI_Model
{
    function __construct()
    {
        parent::__construct();
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
        a.qty_ready,
        a.id_satuan
        FROM v_stockbarangnk a
        JOIN tbpo_satuan b ON b.id_satuan = a.id_satuan
        ");
    }

    public function getsatuan()
    {
        return $this->db->query("SELECT
        a.id_satuan AS id,
        a.nm_satuan AS nm_satuan
        FROM tbpo_satuan a
    ");
    }

    public function countRequser($lv, $kd)
    {

        $cd1 = $this->db->query("SELECT 
        COUNT(a.id_tmp_nk) as tot
        FROM tbpo_tmp_item_nk a
        WHERE a.jnis_po = '$lv' AND a.kd_user = '$kd' ");

        foreach ($cd1->result() as $d) {
            $jml = $d->tot;
            if ($jml == 0) {
                $sts = '0';
                return $sts;
            } else if ($jml >= 1) {
                $sts = '1';
                return $sts;
            }
        }
    }

    public function getalltmpreq($kd)
    {
        return $this->db->query("SELECT 
        a.id_tmp_nk , b.nama_barang , b.descnk , a.keterangan , a.qty , c.nm_satuan , a.kd_bsys
        FROM tbpo_tmp_item_nk a
        JOIN tbpo_barang_nk b ON b.kd_barang = a.kd_barang
        JOIN tbpo_satuan c ON c.id_satuan = b.satuan 
        WHERE a.jnis_po = '1' AND a.kd_user = '$kd'
        ");
    }

    public function getallreq($kd)
    {
        return $this->db->query("SELECT 
        a.*
        FROM tbpo_req_nk a
        WHERE a.kd_user = '$kd' 
        AND a.status != 'DONE' AND a.status != 'PENDING'
        ORDER BY a.tgl_transaksi DESC, a.create_at DESC
        ");
    }

    public function getallreq_seed()
    {
        return $this->db->query("SELECT 
        a.*
        FROM tbpo_req_nk a
        WHERE a.departemen = 'PROMOSI SEED'
        AND a.status != 'DONE' AND a.status != 'PENDING'
        ORDER BY a.tgl_transaksi DESC, a.create_at DESC
        ");
    }

    public function getallreq_cp()
    {
        return $this->db->query("SELECT 
        a.*
        FROM tbpo_req_nk a
        WHERE a.departemen = 'PROMOSI CP'
        AND a.status != 'DONE' AND a.status != 'PENDING'
        ORDER BY a.tgl_transaksi DESC, a.create_at DESC
        ");
    }

    public function getallreqpending($user)
    {
        return $this->db->query("SELECT 
        a.*
        FROM tbpo_req_nk a
        WHERE a.kd_user = '$user' 
        AND a.status = 'PENDING'
        ORDER BY a.tgl_transaksi DESC, a.create_at DESC
        ");
    }
    public function getallreqdone($kd)
    {
        return $this->db->query("SELECT 
        a.*
        FROM tbpo_req_nk a
        WHERE a.kd_user = '$kd' 
        AND a.status = 'DONE'
        ORDER BY a.tgl_transaksi DESC, a.create_at DESC
        ");
    }
    public function getallpending($kd)
    {
        return $this->db->query("SELECT 
        a.*
        FROM tbpo_req_nk a
        WHERE a.kd_user = '$kd' 
        AND a.status = 'PENDING'
        ORDER BY a.tgl_transaksi DESC, a.create_at DESC
        ");
    }
    function input_detail_po_nk($data)
    {
        $this->db->insert('tbpo_detail_req', $data);
    }
    function inputponew($data)
    {
        $this->db->insert('tbpo_po_nk', $data);
    }
    public function getrequestbypic($kd)
    {
        $this->db->select('*');
        $this->db->from('tbpo_req_nk');
        $this->db->where('kd_po_nk', $kd);
        $query = $this->db->get()->result();
        return $query;
    }

    public function getrequestrow($kd)
    {
        $this->db->select('*');
        $this->db->from('tbpo_req_nk');
        $this->db->where('kd_po_nk', $kd);
        return $this->db->get()->row();
    }

    /** Dokumen pendukung yang melekat pada pengajuan request PIC. */
    public function add_supporting_document($data)
    {
        return $this->db->insert('tbpo_req_nk_supporting_file', $data);
    }

    public function get_supporting_documents($kdpo)
    {
        return $this->db
            ->where('kd_po_nk', $kdpo)
            ->order_by('id_supporting_file', 'ASC')
            ->get('tbpo_req_nk_supporting_file')
            ->result();
    }

    public function get_supporting_document($id)
    {
        return $this->db
            ->where('id_supporting_file', (int) $id)
            ->get('tbpo_req_nk_supporting_file')
            ->row();
    }
    public function count_acc_req($kd)
    {
        return $this->db->query("SELECT
        COUNT(a.id_det_po_nk) AS total,
        SUM(CASE WHEN a.status = '1' THEN 1 ELSE 0 END) tot_yes,
        SUM(CASE WHEN a.status = '4' THEN 1 ELSE 0 END) tot_no
        FROM tbpo_detail_req a
        WHERE a.kd_po_nk = '$kd'
        ");
    }
    public function countjmlharga($kd)
    {
        return $this->db->query("SELECT
        SUM(a.tr_qty*a.hrg_satuan) AS hrg
        FROM tbpo_transaksi_tmp a
        WHERE a.kd_po_nk = '$kd'
        ");
    }

    public function getreqwherepic($kduser, $kdpo, $sts1)
    {
        return $this->db->query("SELECT 
            a.id_det_po_nk AS id,
            b.nama_barang AS nama_barang,
            b.descnk AS deskripsi,
            a.keterangan AS keterangan,
            a.qty AS qtykebutuhan,
            c.nm_satuan AS nm_satuan,
            COALESCE(stock.qty_ready_stock, 0) AS qty_ready_stock,
            a.status as sts
            FROM tbpo_detail_req a
            JOIN tbpo_barang_nk b ON b.kd_barang = a.kd_bsys
            JOIN tbpo_satuan c ON c.id_satuan = b.satuan 
            LEFT JOIN (
                SELECT kode_barangs, SUM(qty_ready) AS qty_ready_stock
                FROM v_stockbarangnk
                GROUP BY kode_barangs
            ) stock ON stock.kode_barangs = a.kd_bsys
            WHERE a.kd_user = '$kduser' AND a.kd_po_nk = '$kdpo' AND a.status = '$sts1'
        ");
    }
    public function getreqpicnosts($usr, $kd)
    {
        return $this->db->query("SELECT 
            a.id_det_po_nk AS id,
            b.nama_barang AS nama_barang,
            b.descnk AS deskripsi,
            a.keterangan AS keterangan,
            a.qty AS qtykebutuhan,
            c.nm_satuan AS nm_satuan,
            COALESCE(stock.qty_ready_stock, 0) AS qty_ready_stock,
            a.status as sts
            FROM tbpo_detail_req a
            JOIN tbpo_barang_nk b ON b.kd_br_adm = a.kd_bsys
            JOIN tbpo_satuan c ON c.id_satuan = b.satuan 
            LEFT JOIN (
                SELECT kode_barangs, SUM(qty_ready) AS qty_ready_stock
                FROM v_stockbarangnk
                GROUP BY kode_barangs
            ) stock ON stock.kode_barangs = a.kd_bsys
            WHERE a.kd_user = '$usr' AND a.kd_po_nk = '$kd'
        ");
    }
    public function getitmlistpicreq($kd)
    {
        return $this->db->query("SELECT
            a.id_det_po_nk AS idbarang,
            a.kd_bsys AS kodebarang,
            a.kd_po_nk AS kodefaktur,
            a.kd_bsys AS kd_bsys,
            b.kd_barang AS kd_barang,
            b.nama_barang AS nmbarang,
            b.descnk AS deskripsi,
            a.keterangan AS ket,
            a.qty AS qty,
            c.nm_satuan AS nmsatuan,
            COALESCE(stock.qty_ready_stock, 0) AS qty_ready_stock
            FROM tbpo_detail_req a 
            JOIN tbpo_barang_nk b ON b.kd_br_adm = a.kd_bsys
            JOIN tbpo_satuan c ON c.id_satuan = a.satuan
            LEFT JOIN (
                SELECT kode_barangs, SUM(qty_ready) AS qty_ready_stock
                FROM v_stockbarangnk
                GROUP BY kode_barangs
            ) stock ON stock.kode_barangs = a.kd_bsys
            WHERE a.kd_po_nk = '$kd'
        ");
    }
    public function getreqwheres($kd)
    {
        return $this->db->query("SELECT
            x.id_det_po_nk AS id,
            x.kd_po_nk AS kode_po,
            x.kd_barang AS kode_barang,
            x.nama_barang AS nama_barang,
            x.deskripsi AS deskripsi,
            x.keterangan AS keterangan,
            x.status AS sts,
            x.nm_satuan as nm_satuan,
            COALESCE(x.qty_tmp,0) AS qty_tmp,
            COALESCE(x.qty_transaksi_pad,0) qty_adjp,
            COALESCE(x.qty_transaksi_mad,0) qty_adjm,
            COALESCE(x.qty_transaksi_m,0) qty_m,
            COALESCE(x.qty_transaksi_p,0) qty_p,
            COALESCE(x.qty,0) AS qty_req,
            COALESCE(x.qty_ready_stock,0) AS qty_ready_stock,
            IF(x.status = '1', (COALESCE(x.qty_transaksi_pad,0)+COALESCE(x.qty_transaksi_p,0)) - (COALESCE(x.qty_transaksi_mad,0)+COALESCE(x.qty_transaksi_m,0)) - COALESCE(x.qty_tmp,0), (COALESCE(x.qty_transaksi_pad,0)+COALESCE(x.qty_transaksi_p,0)) - (COALESCE(x.qty_transaksi_mad,0)+COALESCE(x.qty_transaksi_m,0))) AS qty_ready
            FROM
            (   SELECT 
                a.id_det_po_nk,
                a.kd_po_nk,
                a.kd_barang,
                a.nama_barang,
                a.deskripsi,
                a.keterangan,
                a.qty,
                a.status,
                f.nm_satuan,
                stock.qty_ready_stock,
                (SELECT SUM(c.tr_qty) FROM tbpo_transaksi_tmp c WHERE c.kd_barang = a.kd_barang GROUP BY a.kd_bsys) AS qty_tmp,
                (SELECT SUM(d.tr_qty) FROM tbpo_transaksi d WHERE d.kd_barang = a.kd_barang GROUP BY a.kd_bsys) AS qty_transaksi,
                (SELECT SUM(g.tr_qty) FROM tbpo_transaksi g WHERE g.kd_barang = a.kd_barang AND g.kd_akun = '11511' GROUP BY a.kd_bsys) AS qty_transaksi_p,
                (SELECT SUM(h.tr_qty) FROM tbpo_transaksi h WHERE h.kd_barang = a.kd_barang AND h.kd_akun = '11512' GROUP BY a.kd_bsys) AS qty_transaksi_m,
                (SELECT SUM(i.tr_qty) FROM tbpo_transaksi i WHERE i.kd_barang = a.kd_barang AND i.kd_akun = '11514' GROUP BY a.kd_bsys) AS qty_transaksi_mad,
                (SELECT SUM(j.tr_qty) FROM tbpo_transaksi j WHERE j.kd_barang = a.kd_barang AND j.kd_akun = '11513' GROUP BY a.kd_bsys) AS qty_transaksi_pad
                FROM tbpo_detail_req a 
                JOIN tbpo_barang_nk e ON e.kd_br_adm = a.kd_bsys
                JOIN tbpo_satuan f ON f.id_satuan = e.satuan 
                LEFT JOIN (
                    SELECT kode_barangs, SUM(qty_ready) AS qty_ready_stock
                    FROM v_stockbarangnk
                    GROUP BY kode_barangs
                ) stock ON stock.kode_barangs = a.kd_bsys
            ) AS x 
            WHERE x.kd_po_nk = '$kd'
            ORDER BY x.id_det_po_nk
        ");
    }
    public function getreqwhere($kd, $sts)
    {
        return $this->db->query("SELECT
            x.id_det_po_nk AS id,
            x.kd_po_nk AS kode_po,
            x.kd_barang AS kode_barang,
            x.nama_barang AS nama_barang,
            x.deskripsi AS deskripsi,
            x.keterangan AS keterangan,
            x.status AS sts,
            x.nm_satuan as nm_satuan,
            COALESCE(x.qty_tmp,0) AS qty_tmp,
            (COALESCE(x.qty_transaksi_p,0) - COALESCE(x.qty_transaksi_m,0)) AS qty_transaksi,
            COALESCE(x.qty,0) AS qty_req,
            COALESCE(x.qty_ready_stock,0) AS qty_ready_stock,
            IF(x.status = '1',(COALESCE(x.qty_transaksi_p,0) - COALESCE(x.qty_transaksi_m,0)) - COALESCE(x.qty_tmp,0),(COALESCE(x.qty_transaksi_p,0) - COALESCE(x.qty_transaksi_m,0))) AS qty_ready
            FROM
            (   SELECT 
                a.id_det_po_nk,
                a.kd_po_nk,
                a.kd_barang,
                a.nama_barang,
                a.deskripsi,
                a.keterangan,
                a.qty,
                a.status,
                f.nm_satuan,
                stock.qty_ready_stock,
                (SELECT SUM(c.tr_qty) FROM tbpo_transaksi_tmp c WHERE c.kd_barangsys = a.kd_bsys GROUP BY a.kd_bsys) AS qty_tmp,
                (SELECT SUM(d.tr_qty) FROM tbpo_transaksi d WHERE d.kd_barangsys = a.kd_bsys GROUP BY a.kd_bsys) AS qty_transaksi,
                (SELECT SUM(g.tr_qty) FROM tbpo_transaksi g WHERE g.kd_barangsys = a.kd_bsys AND g.kd_akun = '11511' GROUP BY a.kd_bsys) AS qty_transaksi_p,
                (SELECT SUM(h.tr_qty) FROM tbpo_transaksi h WHERE h.kd_barangsys = a.kd_bsys AND h.kd_akun = '11512' GROUP BY a.kd_bsys) AS qty_transaksi_m
                FROM tbpo_detail_req a 
                JOIN tbpo_barang_nk e ON e.kd_barang = a.kd_bsys
                JOIN tbpo_satuan f ON f.id_satuan = e.satuan 
                LEFT JOIN (
                    SELECT kode_barangs, SUM(qty_ready) AS qty_ready_stock
                    FROM v_stockbarangnk
                    GROUP BY kode_barangs
                ) stock ON stock.kode_barangs = a.kd_bsys
            ) AS x 
            WHERE x.kd_po_nk = '$kd' AND x.status = '$sts'
            ");
    }

    function hapus_tmp_nk($id)
    {
        $this->db->where('kd_user', $id);
        return $this->db->delete('tbpo_tmp_item_nk');
    }

    /**
     * Check the request-number reservation before a new request is stored.
     * This is a convenience check only; the UNIQUE index on kd_po_nk remains
     * the authoritative protection against concurrent inserts.
     */
    public function is_kdponk_exists($kdponk)
    {
        return $this->db
            ->where('kd_po_nk', $kdponk)
            ->count_all_results('tbpo_req_nk') > 0;
    }

    /**
     * Read one PIC's request draft while holding its rows until the current
     * transaction completes. This prevents two simultaneous submits from
     * copying the same draft into separate requests.
     */
    public function get_tmp_non_komersil_for_update($kd)
    {
        return $this->db->query(
            'SELECT * FROM tbpo_tmp_item_nk WHERE jnis_po = ? AND kd_user = ? FOR UPDATE',
            array('1', $kd)
        )->result();
    }

    /** Delete only the draft rows that were locked and copied for this request. */
    public function hapus_tmp_nk_by_ids($kduser, array $ids)
    {
        if (empty($ids)) {
            return false;
        }

        $this->db->where('jnis_po', '1');
        $this->db->where('kd_user', $kduser);
        $this->db->where_in('id_tmp_nk', $ids);
        return $this->db->delete('tbpo_tmp_item_nk');
    }

    public function getlistpic()
    {
        return $this->db->query("SELECT a.*
            FROM tbpo_req_nk a
            WHERE a.status IN ('ON PROGRESS - BELUM ACC', 'ON PROGRESS', 'ON PROGRESS - ACC KADEP', 'PO REVISI')
            ORDER BY a.tgl_transaksi DESC, a.create_at DESC;
            ");
    }

    public function getlistadm()
    {
        return $this->db->query("SELECT 
        a.kd_po_nk AS kd_po_nk,
        a.nm_user AS nm_user,
        a.departemen AS departemen,
        a.tgl_transaksi AS tgl_transaksi,
        a.create_at AS create_at,
        a.tj_pembelian AS tj_pembelian,
        a.status AS status,
        COALESCE(b.status,0) AS status_po
        FROM tbpo_req_nk a
        LEFT JOIN tbpo_po_nk b ON b.kd_po_req = a.kd_po_nk
        LEFT JOIN tbpo_user c ON c.kode_user = a.kd_user
        WHERE a.departemen = 'KEUANGAN' AND a.status != 'DONE' AND c.aksess_lv = '2' 
        ORDER BY a.tgl_transaksi DESC, a.create_at DESC
        ");
    }

    public function getlistpicreqacc()
    {
        return $this->get_request_list('request_acc');
    }

    /** Daftar request PIC untuk daftar Purchasing. */
    public function get_request_list($scope = 'request_acc')
    {
        $where = '';
        if ($scope === 'request_acc') {
            // Request baru tetap terlihat oleh Purchasing, tetapi tidak dapat
            // diproses sebelum KADEP menyetujui.
            $where = "WHERE TRIM(a.status) IN ('ON PROGRESS - BELUM ACC', 'ON PROGRESS', 'MENUNGGU ACC KADEP', 'REQUEST ACC')";
        }

        return $this->db->query("SELECT
        a.kd_po_nk AS kd_po_nk,
        a.nm_user AS nm_user,
        a.departemen AS departemen,
        a.tgl_transaksi AS tgl_transaksi,
        a.create_at AS create_at,
        a.tj_pembelian AS tj_pembelian,
        a.status AS status,
        COALESCE(b.status,0) AS status_po
        FROM tbpo_req_nk a
        LEFT JOIN tbpo_po_nk b ON b.kd_po_req = a.kd_po_nk
        $where
        ORDER BY a.tgl_transaksi DESC, a.create_at DESC;");
    }

    public function getlistpicreqkadep($departemen, $kodeKadep = '')
    {
        $this->db->select('a.*');
        $this->db->from('tbpo_req_nk a');
        $this->db->where('a.status', 'ON PROGRESS - BELUM ACC');
        if (!is_super_admin()) {
            $this->db->where('a.departemen', $departemen);
        }
        $this->db->order_by('a.tgl_transaksi', 'DESC');
        $this->db->order_by('a.create_at', 'DESC');

        return $this->db->get();
    }

    /** Arsip KADEP: request yang sudah diteruskan Purchasing setelah timeout. */
    public function getlistpicreqkadep_history($departemen, $allDepartments = false)
    {
        $timeoutField = $this->db->field_exists('purchasing_timeout_opened_at', 'tbpo_req_nk')
            ? 'a.purchasing_timeout_opened_at' : 'a.purchasing_opened_at';
        $this->db->select("a.kd_po_nk, a.nm_user, a.departemen, a.tgl_transaksi, a.status, {$timeoutField} AS purchasing_opened_at, u.nama_user AS nama_purchasing", false);
        $this->db->from('tbpo_req_nk a');
        $this->db->join('tbpo_user u', 'u.kode_user = a.acc_with', 'left');
        $this->db->where($timeoutField . ' IS NOT NULL', null, false);
        $this->db->where('a.status !=', 'ON PROGRESS - BELUM ACC');
        if (!$allDepartments) {
            $this->db->where('a.departemen', $departemen);
        }
        $this->db->order_by($timeoutField, 'DESC', false);
        return $this->db->get();
    }

    /** PO yang sudah disiapkan Purchasing dan menunggu persetujuan KADEP. */
    public function get_purchase_waiting_kadep($departemen = null, $allDepartments = false)
    {
        $this->db->select('p.kd_po_nk, p.kd_po_req, p.nopo, p.status, p.tgl_transaksi, p.jml_item, p.total_harga, p.departemen, p.tj_pembelian, r.nm_user AS nama_pengaju');
        $this->db->from('tbpo_po_nk p');
        $this->db->join('tbpo_req_nk r', 'r.kd_po_nk = p.kd_po_req', 'left');
        $this->db->where('p.status', 'ON PROGRESS - KADEP');
        if (!$allDepartments) {
            $this->db->where('p.departemen', $departemen);
        }
        $this->db->order_by('p.tgl_transaksi', 'DESC');
        $this->db->order_by('p.create_at', 'DESC');
        return $this->db->get();
    }

    public function getlistready()
    {
        // return $this->db->get('tbpo_req_nk')->result();
        return $this->db->query("SELECT a.*
            FROM tbpo_req_nk a
            WHERE TRIM(a.status) IN (
                'BARANG TERSEDIA',
                'MENUNGGU PENYERAHAN BARANG',
                'MENUNGGU PENYERAHAN BARAN'
            )
            ORDER BY a.tgl_transaksi DESC, a.create_at DESC;");
    }

    public function getlistpickup()
    {
        return $this->db->query("SELECT a.*
            FROM tbpo_req_nk a
            WHERE TRIM(a.status) IN ('MENUNGGU PENYERAHAN BARANG', 'MENUNGGU PENYERAHAN BARAN')
            ORDER BY a.tgl_transaksi DESC, a.create_at DESC;");
    }

    /** Permohonan pengambilan yang menunggu keputusan KADEP. */
    public function get_pickup_approval_waiting_kadep($departemen, $allDepartments = false)
    {
        $this->db->from('tbpo_req_nk');
        $this->db->where('status', 'MENUNGGU ACC PENGAMBILAN');
        if (!$allDepartments) {
            $this->db->where('departemen', $departemen);
        }
        $this->db->order_by('tgl_transaksi', 'DESC');
        $this->db->order_by('create_at', 'DESC');
        return $this->db->get();
    }

    /**
     * Histori request PIC pada departemen KADEP, beserta PO terakhir yang
     * dibuat Purchasing. Satu request tetap hanya ditampilkan satu kali.
     */
    public function get_pickup_pic_history($departemen, $allDepartments = false)
    {
        $latestPurchase = '(SELECT kd_po_req, MAX(id_po_nk) AS latest_id
            FROM tbpo_po_nk
            GROUP BY kd_po_req) latest_po';

        $this->db->select('r.kd_po_nk, r.nm_user, r.departemen, r.tgl_transaksi, r.tgl_ambil,
            r.create_at, r.tj_pembelian, r.jml_item, r.status AS status_request,
            p.kd_po_nk AS kd_po_pembelian, p.nopo, p.status AS status_pembelian');
        $this->db->from('tbpo_req_nk r');
        $this->db->join($latestPurchase, 'latest_po.kd_po_req = r.kd_po_nk', 'left', false);
        $this->db->join('tbpo_po_nk p', 'p.id_po_nk = latest_po.latest_id', 'left');
        if (!$allDepartments) {
            $this->db->where('r.departemen', $departemen);
        }
        $this->db->order_by('r.tgl_transaksi', 'DESC');
        $this->db->order_by('r.create_at', 'DESC');
        return $this->db->get();
    }

    /** Detail harga aktual pengambilan, berdasarkan snapshot alokasi LIFO. */
    public function get_lifo_pickup_details($kdpo)
    {
        if (!$this->db->table_exists('tbpo_stock_lifo_allocation') || !$this->db->table_exists('tbpo_stock_lifo_batch_nk')) {
            return array();
        }
        return $this->db->query("SELECT t.id_transnk, t.tgl_transaksi, t.kd_barang, t.kd_barangsys, t.tr_qty,
            COALESCE(b.nama_barang, t.kd_barang) AS nama_barang,
            SUM(a.qty_alokasi) AS qty_alokasi,
            SUM(a.nilai_alokasi) AS nilai_total,
            CASE WHEN SUM(a.qty_alokasi) > 0 THEN SUM(a.nilai_alokasi) / SUM(a.qty_alokasi) ELSE NULL END AS harga_satuan,
            GROUP_CONCAT(CONCAT(lb.referensi_sumber, ' | batch ', lb.id_batch, ' | qty ', a.qty_alokasi) ORDER BY a.id_alokasi SEPARATOR '; ') AS keterangan_batch
            FROM tbpo_transaksi t
            LEFT JOIN tbpo_stock_lifo_allocation a ON a.id_transnk_keluar=t.id_transnk
            LEFT JOIN tbpo_stock_lifo_batch_nk lb ON lb.id_batch=a.id_batch
            LEFT JOIN tbpo_barang_nk b ON b.kd_barang=t.kd_barang
            WHERE t.kd_po_nk=? AND t.kd_akun='11512'
            GROUP BY t.id_transnk, t.tgl_transaksi, t.kd_barang, t.kd_barangsys, t.tr_qty, b.nama_barang
            ORDER BY t.id_transnk DESC", array($kdpo))->result();
    }

    /** Request asal sebuah detail item, dipakai untuk validasi aksi Purchasing. */
    public function get_request_by_detail_id($detailId)
    {
        return $this->db
            ->select('r.*')
            ->from('tbpo_detail_req d')
            ->join('tbpo_req_nk r', 'r.kd_po_nk = d.kd_po_nk')
            ->where('d.id_det_po_nk', (int) $detailId)
            ->get()
            ->row();
    }

    /**
     * KADEP mendapat waktu lima menit untuk memutuskan request baru.
     * Setelah itu Purchasing dapat memprosesnya walaupun statusnya belum berubah.
     */
    public function can_purchasing_process($request)
    {
        if (!$request || trim((string) $request->status) !== 'ON PROGRESS - BELUM ACC') {
            return (bool) $request;
        }

        $submittedAt = !empty($request->kadep_submitted_at)
            ? $request->kadep_submitted_at
            : (!empty($request->create_at) ? $request->create_at : null);

        if (empty($submittedAt)) {
            return false;
        }

        try {
            $submittedAt = new DateTimeImmutable($submittedAt, new DateTimeZone('Asia/Jakarta'));
            $availableAt = $submittedAt->modify('+5 minutes');
            $now = new DateTimeImmutable('now', new DateTimeZone('Asia/Jakarta'));
            return $now >= $availableAt;
        } catch (Exception $exception) {
            return false;
        }
    }

    /** Update hanya bila statusnya masih sesuai agar keputusan ganda tidak menimpa data. */
    public function update_request_status_if_current($kdpo, $currentStatus, $data)
    {
        $this->db->where('kd_po_nk', $kdpo);
        $this->db->where('status', $currentStatus);
        $this->db->update('tbpo_req_nk', $data);
        return $this->db->affected_rows() === 1;
    }

    public function getlistdone()
    {
        // return $this->db->get('tbpo_req_nk')->result();
        return $this->db->query("SELECT a.*
            FROM tbpo_req_nk a
            WHERE a.status = 'DONE'
            ORDER BY a.tgl_transaksi DESC, a.create_at DESC;
            ");
    }

    public function gettr($kd)
    {
        return $this->db->query("SELECT
        a.id_transnk,
        a.kd_po_nk,
        b.nama_barang,
        b.descnk,
        a.keterangan,
        a.tr_qty,
        c.nm_satuan
        FROM tbpo_transaksi a 
        JOIN tbpo_barang_nk b ON b.kd_br_adm = a.kd_barang
        JOIN tbpo_satuan c ON c.id_satuan = a.satuan
        WHERE a.kd_po_nk = '$kd'
        GROUP BY a.kd_barangsys
    ");
    }

    public function getdetailreq($kd)
    {
        return $this->db->query("SELECT
        a.kd_po_nk,
        a.kd_bsys,
        b.kd_barang,
        b.nama_barang,
        b.descnk,
        a.keterangan,
        a.qty,
        c.nm_satuan,
        COALESCE(stock.qty_ready_stock, 0) AS qty_ready_stock
        FROM tbpo_detail_req a 
        JOIN tbpo_barang_nk b ON b.kd_br_adm = a.kd_bsys
        JOIN tbpo_satuan c ON c.id_satuan = a.satuan
        LEFT JOIN (
            SELECT kode_barangs, SUM(qty_ready) AS qty_ready_stock
            FROM v_stockbarangnk
            GROUP BY kode_barangs
        ) stock ON stock.kode_barangs = a.kd_bsys
        WHERE a.kd_po_nk = '$kd'

    ");
    }

    public function countjmltmpbr($kd)
    {
        $this->db->select('id_tmp_nk');
        $this->db->from('tbpo_tmp_item_nk');
        $this->db->where('jnis_po', '1');
        $this->db->where('kd_user', $kd);
        $num_results = $this->db->count_all_results();
        return $num_results;
    }

    public function countpend($kd)
    {
        $this->db->select('id_tmp_nk');
        $this->db->from('tbpo_tmp_item_nk');
        $this->db->where('jnis_po', '3');
        $this->db->where('kd_user', $kd);
        $num_results = $this->db->count_all_results();
        return $num_results;
    }

    function get_tmp_non_komersil($kd)
    {
        $this->db->select('*');
        $this->db->from('tbpo_tmp_item_nk');
        $this->db->where('jnis_po', '1');
        $this->db->where('kd_user', $kd);
        $query = $this->db->get()->result();
        return $query;
    }

    public function inputreqbr($data)
    {
        $this->db->insert('tbpo_tmp_item_nk', $data);
    }

    public function inputreq($data)
    {
        $this->db->insert('tbpo_req_nk', $data);
    }

    public function editedreqpic($id, $data)
    {
        $this->db->where('id_tmp_nk', $id);
        return $this->db->update('tbpo_tmp_item_nk', $data);
    }

    public function updatestsitem($id, $data)
    {
        $this->db->where('id_det_po_nk', $id);
        return $this->db->update('tbpo_detail_req', $data);
    }

    public function deletedtmpnkreq($id)
    {
        $this->db->where('id_tmp_nk', $id);
        return $this->db->delete('tbpo_tmp_item_nk');
    }

    public function deletedet($id, $sts)
    {
        $this->db->where('id_tmp_nk', $id);
        return $this->db->delete('tbpo_detail_req');
    }

    public function deletedetailporeqkdall($kd, $sts)
    {
        $this->db->where('kd_po_nk', $kd);
        $this->db->where('status', $sts);
        return $this->db->delete('tbpo_detail_req');
    }

    public function getitemreq($id)
    {
        return $this->db->query("SELECT 
        a.id_det_po_nk AS id,
        a.kd_po_nk as kd_po_nk,
        a.kd_barang as kd_barang,
        a.nama_barang AS nm_barang,
        a.deskripsi AS descnk,
        a.kd_bsys as kd_bsys,
        a.keterangan as ket,
        b.kat_barang as kat_barang,
        a.qty as qty,
        b.satuan as satuan,
        c.kode_user AS kduser,
        c.nama_user AS nmuser,
        c.departement AS dep
        FROM tbpo_detail_req a
        JOIN tbpo_barang_nk b ON b.kd_br_adm = a.kd_bsys
        JOIN tbpo_user c ON c.kode_user = a.kd_user
        WHERE a.id_det_po_nk = '$id'
            ");
    }

    public function deletedetailponk($id)
    {
        $this->db->where('id_det_po_nk', $id);
        return $this->db->delete('tbpo_detail_po_nk');
    }

    public function updatestsponk($id, $data)
    {
        $this->db->where('kd_po_nk', $id);
        return $this->db->update('tbpo_po_nk', $data);
    }

    public function insertpobaru($dt)
    {
        $this->db->insert('tbpo_po_nk', $dt);
    }

    public function deletedetailporeq($id)
    {
        $this->db->where('id_det_po_nk', $id);
        return $this->db->delete('tbpo_detail_req');
    }

    public function insert_tmp_transaksi($data)
    {
        $this->db->insert('tbpo_transaksi_tmp', $data);
    }

    public function insertbrponkpending($data)
    {
        $this->db->insert('tbpo_detail_po_nk', $data);
    }

    public function insert_transaksi($data)
    {
        $this->db->insert('tbpo_transaksi', $data);
    }

    public function getbuystsponk($kd)
    {
        return $this->db->query("SELECT
        a.nopo AS nopo,
        a.status AS status,
        a.tgl_transaksi AS tgltr,
        a.nm_user AS nmuser,
        a.departemen AS dep,
        a.tj_pembelian AS tjbeli,
        a.kd_po_nk AS kdpo,
        a.kd_po_req as kdporeq
        FROM tbpo_po_nk a
        WHERE a.kd_po_req = '$kd'
        ");
    }

    public function getitmponk($kd)
    {
        return $this->db->query("SELECT
        a.*
        FROM tbpo_detail_po_nk a
        WHERE a.kd_po_nk = '$kd'
        ");
    }

    public function getbuystsponks($kd)
    {
        return $this->db->query("SELECT
        COUNT(a.kd_po_req)  as tot
        FROM tbpo_po_nk a
        WHERE a.kd_po_req = '$kd'
        ");
    }

    public function getdatapobaru($kd)
    {
        return $this->db->query("SELECT 
        a.kd_po_req AS kdporeq,
        a.kd_barang AS kdbr,
        a.kd_bsys AS kdbsys,
        a.keterangan AS ket,
        a.kat_barang AS kat,
        a.qty AS trqty,
        a.satuan AS satuan,
        a.kd_user AS kduser
        FROM tbpo_detail_po_nk a
        WHERE a.kd_po_nk = '$kd'
        GROUP BY a.kd_barang
        ");
    }

    public function getdatapobarureq($kd)
    {
        return $this->db->query("SELECT 
        a.kd_po_nk AS kdporeq,
        a.kd_barang AS kdbr,
        a.kd_bsys AS kdbsys,
        a.keterangan AS ket,
        a.kat_barang AS kat,
        a.qty AS trqty,
        a.satuan AS satuan,
        a.kd_user AS kduser,
        b.tj_pembelian AS tjket,
        a.status AS sts
        FROM tbpo_detail_req a
        LEFT JOIN tbpo_po_nk b ON b.kd_po_req = a.kd_po_nk
        WHERE a.kd_po_nk = '$kd' AND a.status = '1'
        GROUP BY a.kd_barang
        ");
    }

    public function getlisttmptr($kd)
    {
        return $this->db->query("SELECT
            a.kd_akun,
            a.kd_po_nk,
            a.kd_barang,
            a.kd_barangsys,
            b.nama_barang,
            b.descnk,
            a.keterangan,
            a.tr_qty,
            a.status,
            a.satuan,
            c.nm_satuan,
            b.kat_barang,
            d.kd_user,
            d.nm_user,
            d.departemen,
            d.tj_pembelian,
            b.gbr_barang,
            a.hrg_satuan
            FROM tbpo_transaksi_tmp a
            JOIN tbpo_barang_nk b ON b.kd_barang = a.kd_barang
            JOIN tbpo_satuan c ON c.id_satuan = b.satuan
            JOIN tbpo_req_nk d ON d.kd_po_nk	= a.kd_po_nk
            WHERE a.kd_po_nk = '$kd'
        ");
    }

    function updatedetreqitm($kd, $data)
    {
        $this->db->where('id_det_po_nk', $kd);
        return $this->db->update('tbpo_detail_req', $data);
    }
    function updatereqnk_sts($kd, $data)
    {
        $this->db->where('kd_po_nk', $kd);
        return $this->db->update('tbpo_detail_req', $data);
    }
    function updatereqnk_stsbr($kd, $data)
    {
        $this->db->where('kd_po_nk', $kd);
        $this->db->where('status', '4');
        return $this->db->update('tbpo_detail_req', $data);
    }
    function updatereqnk($kd, $data)
    {
        $this->db->where('kd_po_nk', $kd);
        return $this->db->update('tbpo_req_nk', $data);
    }
    function input_new_po_req($data)
    {
        $this->db->insert('tbpo_tmp_item_nk', $data);
    }
    function input_tr($data)
    {
        $this->db->insert('tbpo_transaksi', $data);
    }
    function inpt_tr_tmp($data)
    {
        $this->db->insert('tbpo_transaksi_tmp', $data);
    }
    public function deletedtmpnkreqkd($id)
    {
        $this->db->where('kd_po_nk', $id);
        return $this->db->delete('tbpo_tmp_item_nk');
    }
    public function deletetmptrreq($id)
    {
        $this->db->where('kd_po_nk', $id);
        return $this->db->delete('tbpo_transaksi_tmp');
    }
    public function deleteitemtrtmp($kd, $usr, $ket)
    {
        $this->db->where('kd_po_nk', $kd);
        $this->db->where('req_by', $usr);
        $this->db->where('keterangan', $ket);
        return $this->db->delete('tbpo_transaksi');
    }
    function getNoted($kdpo)
    {
        $this->db->select('*');
        $this->db->from('tbpo_note_direktur');
        $this->db->where('kd_po', $kdpo);
        $this->db->where('note_for', '2');
        return $this->db->get()->result();
    }
    function insertTmpmbarang($data)
    {
        $this->db->insert('tbpo_req_masterbarang', $data);
    }
    public function gettotsts($kd)
    {
        return $this->db->query("SELECT
        x.totalbr,
        x.sts1,
        x.sts2,
        x.sts0,
        x.sts3
        FROM
        (
            SELECT
            a.kd_po_nk,
            (SELECT COUNT(b.id_det_po_nk) FROM tbpo_detail_req b WHERE b.kd_po_nk = a.kd_po_nk) as totalbr,
            (SELECT COUNT(c.status) FROM tbpo_detail_req c WHERE c.kd_po_nk = a.kd_po_nk AND c.status = '1') AS sts1,
            (SELECT COUNT(d.status) FROM tbpo_detail_req d WHERE d.kd_po_nk = a.kd_po_nk AND d.status = '2') AS sts2,
            (SELECT COUNT(e.status) FROM tbpo_detail_req e WHERE e.kd_po_nk = a.kd_po_nk AND e.status = '0') AS sts0,
            (SELECT COUNT(f.status) FROM tbpo_detail_req f WHERE f.kd_po_nk = a.kd_po_nk AND f.status = '3') AS sts3
            FROM tbpo_detail_req a
        ) AS x 
        WHERE x.kd_po_nk = '$kd' 
        GROUP BY x.kd_po_nk
        ");
    }
    public function del_itm_det_req_ponk($id)
    {
        $this->db->where('id_det_po_nk', $id);
        return $this->db->delete('tbpo_detail_req');
    }
    public function ls_del_itm_det_req_ponk($kd)
    {
        return $this->db->query("SELECT
        a.*
        FROM tbpo_detail_req a
        WHERE a.kd_po_nk = '$kd' AND a.status = '3'
        ");
    }
    public function insert_tmp_po_persediaan($kd)
    {
        $this->db->insert('tbpo_detail_po_nk', $kd);
    }
    function generatekdponk($data)
    {
        $this->db->insert('tbpo_generate_kd_ponk', $data);
    }
    function kdnonkomersial()
    {
        $cd1 = $this->db->query("SELECT MAX(RIGHT(kd_barang,4)) AS kd_max FROM tbpo_generate_kd_ponk WHERE DATE(create_at)=CURDATE()");
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
        $kdnk1 = 'NPONK' . date('dmy') . $kd1;
        return $kdnk1;
    }
    function kdnewbuy()
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
        $kdnk1 = 'NPONK' . date('dmy') . $kd1;
        return $kdnk1;
    }

    function updated_req_po_nk($kd, $data)
    {
        $this->db->where('kd_po_nk', $kd);
        return $this->db->update('tbpo_req_nk', $data);
    }

    function det_updated_req_po_nk($id, $data)
    {
        $this->db->where('id_det_po_nk', $id);
        return $this->db->update('tbpo_detail_req', $data);
    }

    function det_delete_req_po_nk($id)
    {
        $this->db->where('id_det_po_nk', $id);
        return $this->db->delete('tbpo_detail_req');
    }

    // function viewtot($kd)
    // {
    //     $q1     = $this->db->query("SELECT COUNT(b.id_det_po_nk) AS totdetail FROM tbpo_detail_req b WHERE b.kd_po_nk = '$kd'");
    //     $q1     = $this->db->query("SELECT COUNT(b.id_det_po_nk) AS totalbrtmp FROM tbpo_detail_req b WHERE b.kd_po_nk = '$kd'");
    //     $q2     = $this->db->query("SELECT COUNT(b.id_transnk) AS totpending FROM tbpo_transaksi_tmp b WHERE b.kd_po_nk = '$kd' AND b.status = 'confirm'");
    //     $q3     = $this->db->query("SELECT COUNT(b.id_transnk) AS totpending FROM tbpo_transaksi_tmp b WHERE b.kd_po_nk = '$kd' AND b.status = 'pending'");

    //     if ($q->num_rows() > 0) {
    //         foreach ($q1->result() as $x1) {
    //         }
    //     }
    // }
}


// QUERY SQL 

// SELECT
// x.kd_po_nk AS kode_po,
// x.kd_barang AS kode_barang,
// x.nama_barang AS nama_barang,
// x.deskripsi AS deskripsi,
// COALESCE(x.qty_minus,0) AS qty_minus,
// COALESCE(x.qty_plus,0) AS qty_plus,
// COALESCE(x.qty,0) AS qty_req,
// COALESCE(x.qty_plus,0) - COALESCE(x.qty_minus,0) AS qty_ready
// FROM
// (
//     SELECT 
// 	a.kd_po_nk,
//     a.kd_barang,
//     a.nama_barang,
//     a.deskripsi,
//     a.keterangan,
//     a.qty,
//     a.status,
//     (SELECT SUM(c.tr_qty) FROM tbpo_transaksi_tmp c WHERE c.kd_barangsys = a.kd_bsys GROUP BY a.kd_bsys) AS qty_minus,
//     (SELECT SUM(d.tr_qty) FROM tbpo_transaksi d WHERE d.kd_barangsys = a.kd_bsys GROUP BY a.kd_bsys) AS qty_plus
//     FROM tbpo_detail_req a 
//     JOIN tbpo_barang_nk e ON e.kd_barang = a.kd_bsys
//     GROUP BY a.kd_barang
// ) AS x 
// WHERE x.kd_po_nk = 'PONK2108240002'

// CREATE VIEW STOCK
