<?php
defined('BASEPATH') or exit('No direct script access allowed');

class C_Api extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('Api/M_Api');
    }

    public function get_po()
    {
        return $this->get_data_pre_po_erp();
    }

    public function get_barang()
    {
        return $this->get_data_barang();
    }

    public function get_data_barang()
    {
        if (strtoupper($this->input->method()) === 'OPTIONS') {
            return $this->_json_response([], 204);
        }

        if (strtoupper($this->input->method()) !== 'GET') {
            return $this->_json_response([
                'status'  => false,
                'message' => 'method not allowed',
                'data'    => [],
            ], 405);
        }

        try {
            $params = [
                'search'      => $this->input->get('search', TRUE),
                'kode_barang' => $this->input->get('kode_barang', TRUE),
                'kd_suplier'  => $this->input->get('kd_suplier', TRUE),
                'limit'       => $this->input->get('limit', TRUE),
                'offset'      => $this->input->get('offset', TRUE),
            ];

            $result = $this->M_Api->get_data_barang($params);

            if (empty($result['data'])) {
                return $this->_json_response([
                    'status'        => false,
                    'message'       => 'data kosong',
                    'total_data'    => $result['total_data'],
                    'total_filter'  => $result['total_filter'],
                    'limit'         => $result['limit'],
                    'offset'        => $result['offset'],
                    'data'          => [],
                ], 200);
            }

            return $this->_json_response([
                'status'       => true,
                'message'      => 'success',
                'total_data'   => $result['total_data'],
                'total_filter' => $result['total_filter'],
                'limit'        => $result['limit'],
                'offset'       => $result['offset'],
                'data'         => $result['data'],
            ], 200);
        } catch (Throwable $th) {
            log_message('error', 'API get_data_barang error: ' . $th->getMessage());

            return $this->_json_response([
                'status'  => false,
                'message' => 'terjadi kesalahan pada server',
                'data'    => [],
            ], 500);
        }
    }

    public function get_data_pre_po_erp()
    {
        if (strtoupper($this->input->method()) !== 'GET') {
            return $this->_json_response([
                'status'  => false,
                'message' => 'method not allowed',
                'data'    => [],
            ], 405);
        }

        try {
            $data = $this->M_Api->get_data_pre_po_erp();

            if (empty($data)) {
                return $this->_json_response([
                    'status'  => false,
                    'message' => 'data kosong',
                    'data'    => [],
                ], 200);
            }

            return $this->_json_response([
                'status'     => true,
                'message'    => 'success',
                'total_data' => count($data),
                'data'       => $data,
            ], 200);
        } catch (Throwable $th) {
            log_message('error', 'API get_data_pre_po_erp error: ' . $th->getMessage());

            return $this->_json_response([
                'status'  => false,
                'message' => 'terjadi kesalahan pada server',
                'data'    => [],
            ], 500);
        }
    }

    private function _json_response($response, $http_code = 200)
    {
        $this->output
            ->set_header('Access-Control-Allow-Origin: *')
            ->set_header('Access-Control-Allow-Methods: GET, OPTIONS')
            ->set_header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

        return $this->output
            ->set_status_header($http_code)
            ->set_content_type('application/json', 'utf-8')
            ->set_output(json_encode($response, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }
}
