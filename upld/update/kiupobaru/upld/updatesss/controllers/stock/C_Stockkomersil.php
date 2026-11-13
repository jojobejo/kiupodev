<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 *
 */
class C_Stockkomersil extends CI_Controller

{


    function __construct()
    {
        parent::__construct();
        $this->load->model('stock/M_Stockkomersil');
        $this->load->library('form_validation');
        $this->load->library('pagination');
        $this->load->helper('url');
    }

    public function index()
    {
        $data['title'] = 'User';
        $data['source'] = $this->input->get('source', true);

        $this->load->view('partial/header', $data);
        $this->load->view('partial/sidebar');
        $this->load->view('content/stock/komersil/bodystock.php', $data);
        $this->load->view('content/stock/komersil/footer.php', $data);
    }

    private function _resolve_source($source)
    {
        if ($source === 'online') {
            return true;
        }
        if ($source === 'local') {
            return false;
        }
        return (ENVIRONMENT === 'production');
    }

    private function _pick_value($row, $keys)
    {
        foreach ($keys as $key) {
            if (isset($row[$key]) && $row[$key] !== '') {
                return $row[$key];
            }
        }
        return '-';
    }

    public function api_stock_per_gudang()
    {
        $source = $this->input->get('source', true);
        $use_online = $this->_resolve_source($source);
        $result = $this->M_Stockkomersil->fetch_stock_per_gudang($use_online);

        $rows = array();
        foreach ($result['data'] as $item) {
            if (!is_array($item)) {
                continue;
            }
            $kdbarang = $this->_pick_value($item, array('kdbarang', 'kode_barang', 'kd_barang', 'id_barang'));
            $nm_barang = $this->_pick_value($item, array('nm_barang', 'nama_barang', 'nama'));
            $satuan = $this->_pick_value($item, array('satuan', 'nm_satuan', 'nama_satuan'));
            $qty = $this->_pick_value($item, array('qty', 'qty_all', 'stok', 'stock'));
            $qty_min = $this->_pick_value($item, array('qty_min', 'minimum', 'min_qty'));

            $action = '-';
            if ($kdbarang !== '-' && $kdbarang !== '') {
                $action = '<a href="' . base_url('brgdetkomersil/' . $kdbarang) . '" id="confirms" class="btn btn-info btn-sm d-flex justify-content-center"><i class="fas fa-eye"></i></a>';
            }

            $rows[] = array(
                $kdbarang,
                $nm_barang,
                $satuan,
                $qty,
                $qty_min,
                $action,
            );
        }

        $output = array(
            'data' => $rows,
        );
        if (!$result['ok']) {
            $output['error'] = $result['error'];
        }

        $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode($output));
    }
    public function servergetallkomersil()
    {
        $list = $this->M_Stockkomersil->get_datatables();
        $data = array();
        $no = $_POST['start'];
        foreach ($list as $field) {
            $row = array();
            $row[] = $field->kdbarang;
            $row[] = $field->nm_barang;
            $row[] = $field->satuan;
            $row[] = $field->qty;
            $row[] = $field->qty_min;
            $row[] =
                '<a href="' . base_url('brgdetkomersil/' . $field->kdbarang . '') . '" id="confirms" class="btn btn-info btn-sm d-flex justify-content-center"><i class="fas fa-eye"></i></a>';
            $data[] = $row;
        }
        $output = array(
            "draw" => $_POST['draw'],
            "recordsTotal" => $this->M_Stockkomersil->count_all(),
            "recordsFiltered" => $this->M_Stockkomersil->count_filtered(),
            "data" => $data,
        );
        //output dalam format JSON
        echo json_encode($output);
    }

    public function brgdetkomersil($id)
    {
        $data['title']          = 'Detail Product';
        $data['lbarangdet']     = $this->M_Stockkomersil->getdetbrkd($id)->result();

        $this->load->view('partial/header', $data);
        $this->load->view('partial/sidebar');
        $this->load->view('content/stock/komersil/detstock.php', $data);
        $this->load->view('content/stock/komersil/footer.php', $data);
    }


    public function api_stock_komersil()
    {
        $gudang = $this->input->get('gudang', true);

        if ($gudang !== null && $gudang !== '') {
            if ($gudang === 'all') {
                $gudang = 2;
            }
            if (!is_numeric($gudang)) {
                $this->output
                    ->set_content_type('application/json')
                    ->set_output(json_encode([
                        'status' => false,
                        'message' => 'Invalid parameter',
                        'data' => [],
                    ]));
                return;
            }

            $result = $this->M_Stockkomersil->get_stock_komersil($gudang);
            $this->output
                ->set_content_type('application/json')
                ->set_output(json_encode($result));
            return;
        }

        $data['title']          = 'Detail Product';

        $this->load->view('partial/header', $data);
        $this->load->view('partial/sidebar');
        $this->load->view('content/stock/komersil/apistock.php', $data);
        $this->load->view('content/stock/komersil/footer.php', $data);
    }

    public function api_stock($segment = null)
    {
        $this->output->set_content_type('application/json');

        $gudang = $this->input->get('gudang');

        $params = [];
        if (!empty($gudang)) {
            $params['gudang'] = $gudang;
        }

        $data = $this->M_Ics->get_stock($params);

        $this->output->set_output(json_encode([
            'status' => $data ? true : false,
            'data'   => $data
        ]));
    }

    public function get_data_stock_per_gudang()
    {
        if (!$this->input->is_ajax_request()) {
            show_error('Direct access not allowed', 403);
        }

        $gudang = $this->input->get('gudang');

        $params = [];
        if (!empty($gudang) && $gudang !== 'all') {
            $params['gudang'] = $gudang;
        }

        $result = $this->M_Stockkomersil->fetch_stock($params);

        echo json_encode([
            'status'  => $result['status'] ?? false,
            'message' => $result['message'] ?? '',
            'data'    => $result['data'] ?? [],
        ]);
    }
}
