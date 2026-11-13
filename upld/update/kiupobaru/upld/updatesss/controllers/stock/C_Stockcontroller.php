<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 *
 */
class C_Stockcontroller extends CI_Controller

{

    function __construct()
    {
        parent::__construct();
        $this->load->model('stock/M_Stockcontroller');
        $this->load->library('form_validation');
    }

    public function index()
    {

        $data['title'] = 'List Stock Non Komersil';
        $data['stocknk'] = $this->M_Stockcontroller->getAllTransaksi();

        $this->load->view('partial/header', $data);
        $this->load->view('partial/sidebar');
        $this->load->view('content/stock/nonkomersil/stockcontroller.php', $data);
        $this->load->view('partial/footer');
        $this->load->view('content/stock/nonkomersil/datatables');
    }
}
