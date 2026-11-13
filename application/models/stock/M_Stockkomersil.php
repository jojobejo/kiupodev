<?php
defined('BASEPATH') or exit('No direct script access allowed');
/**
 *
 */
class M_Stockkomersil extends CI_Model
{

    private $base_url;
    private $timeout;
    private $api_key;

    public function __construct()
    {
        parent::__construct();
        // Bisa dioverride dari config jika endpoint upstream berubah.
        $configured_url = config_item('stockkomersil_api_url');
        $this->base_url = $configured_url ? $configured_url : 'http://localhost/karismaerp/api/v1/stock';
        $this->timeout  = 10;
        $this->api_key  = 'Bearer KARISMA123';
    }

    private function build_candidate_urls($gudang = null)
    {
        $base = rtrim($this->base_url, '/');
        $urls = array($base);

        if ($gudang === null || $gudang === '') {
            return array_values(array_unique($urls));
        }

        $encoded = rawurlencode($gudang);
        $urls[] = $base . '/' . $encoded;
        $urls[] = $base . '?gudang=' . $encoded;

        // Beberapa implementasi lama mengartikan "all" sebagai gudang induk.
        if ((string) $gudang === 'all') {
            $urls[] = $base . '/2';
            $urls[] = $base . '?gudang=2';
        }

        return array_values(array_unique($urls));
    }

    private function request_stock_upstream($url)
    {
        $headers = ['Accept: application/json'];
        if (!empty($this->api_key)) {
            $headers[] = 'Authorization: ' . $this->api_key;
        }

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL            => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => $this->timeout,
            CURLOPT_HTTPHEADER     => $headers,
            CURLOPT_SSL_VERIFYPEER => false,
        ]);

        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curl_error = curl_error($ch);
        curl_close($ch);

        return array(
            'url' => $url,
            'response' => $response,
            'http_code' => (int) $http_code,
            'curl_error' => $curl_error,
        );
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
    public function getdetbr($id)
    {
        return $this->db->query("SELECT
        b.nama_suplier AS nmsup,
        a.kode_barang AS kdbarang,
        a.nama_barang AS nmbarang,
        C.nm_satuan AS qtysatuan,
        a.qty_min AS qtymin
        FROM tbpo_barang a
        JOIN tbpo_suplier b ON b.kd_suplier = a.kd_suplier
        JOIN tbpo_satuan c ON c.id_satuan = a.satuan_qty
        WHERE a.id_barang = '$id'
        ");
    }

    public function getdetbrkd($id)
    {
        return $this->db->query("SELECT
        a.*,
        b.id_barang,
        b.nama_barang,
        b.qty_min,
        b.qty_all,
        c.nama_suplier,
        d.nm_satuan,
        e.no_po,
        f.nama_user
        FROM tbpo_transaksi a
        JOIN tbpo_barang b ON b.kode_barang = a.kd_barang
        JOIN tbpo_suplier c ON c.kd_suplier = b.kd_suplier
        JOIN tbpo_satuan d ON d.id_satuan = b.satuan_qty
        JOIN tbpo_po e ON e.kd_po =a.kd_po_nk
        JOIN tbpo_user f ON f.kode_user = a.inputer 
        WHERE a.kd_akun = '11411' AND a.kd_barang = '$id'
        ");
    }

    var $table = 'v_brgkomersil';
    var $column_order = array('kdbarang', 'nmsup', 'nm_barang', 'satuan', 'qty', 'qty_min', 'idbrg');
    var $column_search = array('nmsup', 'nm_barang');
    var $order = array('nm_barang' => 'asc');

    private function _get_datatables_query()
    {

        $this->db->from($this->table);
        $i = 0;
        foreach ($this->column_search as $item) // looping awal
        {
            if ($_POST['search']['value']) // jika datatable mengirimkan pencarian dengan metode POST
            {

                if ($i === 0) // looping awal
                {
                    $this->db->group_start();
                    $this->db->like($item, $_POST['search']['value']);
                } else {
                    $this->db->or_like($item, $_POST['search']['value']);
                }

                if (count($this->column_search) - 1 == $i)
                    $this->db->group_end();
            }
            $i++;
        }

        if (isset($_POST['order'])) {
            $this->db->order_by($this->column_order[$_POST['order']['0']['column']], $_POST['order']['0']['dir']);
        } else if (isset($this->order)) {
            $order = $this->order;
            $this->db->order_by(key($order), $order[key($order)]);
        }
    }

    function get_datatables()
    {
        $this->_get_datatables_query();
        if ($_POST['length'] != -1)
            $this->db->limit($_POST['length'], $_POST['start']);
        $query = $this->db->get();
        return $query->result();
    }

    function count_filtered()
    {
        $this->_get_datatables_query();
        $query = $this->db->get();
        return $query->num_rows();
    }

    public function count_all()
    {
        $this->db->from($this->table);
        return $this->db->count_all_results();
    }


    public function fetch_stock($params = [])
    {
        $gudang = isset($params['gudang']) ? (string) $params['gudang'] : null;
        $candidates = $this->build_candidate_urls($gudang);
        $last_attempt = null;

        foreach ($candidates as $candidate_url) {
            $attempt = $this->request_stock_upstream($candidate_url);
            $last_attempt = $attempt;

            if ($attempt['curl_error']) {
                log_message('error', 'cURL Error fetch_stock [' . $candidate_url . ']: ' . $attempt['curl_error']);
                continue;
            }

            if ($attempt['http_code'] !== 200) {
                log_message('error', 'API HTTP Code [' . $candidate_url . ']: ' . $attempt['http_code']);
                continue;
            }

            $response = $attempt['response'];

            // Bersihkan BOM / komentar / byte non-JSON di awal response
            $response = preg_replace('/^\xEF\xBB\xBF/', '', $response);
            $pos_obj = strpos($response, '{');
            $pos_arr = strpos($response, '[');
            $first_json_pos = false;
            if ($pos_obj !== false && $pos_arr !== false) {
                $first_json_pos = min($pos_obj, $pos_arr);
            } elseif ($pos_obj !== false) {
                $first_json_pos = $pos_obj;
            } elseif ($pos_arr !== false) {
                $first_json_pos = $pos_arr;
            }
            if ($first_json_pos !== false && $first_json_pos > 0) {
                $response = substr($response, $first_json_pos);
            }

            $result = json_decode($response, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                log_message('error', 'Invalid JSON from upstream [' . $candidate_url . ']');
                continue;
            }

            return $result;
        }

        if ($last_attempt && $last_attempt['curl_error']) {
            return [
                'status' => false,
                'data' => [],
                'message' => 'Koneksi ke API stock upstream gagal.',
                'upstream' => [
                    'base_url' => $this->base_url,
                    'attempted_urls' => $candidates,
                    'last_error' => $last_attempt['curl_error'],
                ],
            ];
        }

        return [
            'status' => false,
            'data' => [],
            'message' => 'Endpoint API stock upstream tidak ditemukan / tidak valid.',
            'upstream' => [
                'base_url' => $this->base_url,
                'attempted_urls' => $candidates,
                'last_http_code' => $last_attempt ? $last_attempt['http_code'] : null,
            ],
        ];
    }

    public function get_stock_komersil($gudang)
    {
        return $this->fetch_stock(['gudang' => $gudang]);
    }
}
