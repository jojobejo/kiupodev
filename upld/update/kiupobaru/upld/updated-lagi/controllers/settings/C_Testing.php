<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 *
 */
class C_Testing extends CI_Controller

{

    function __construct()
    {
        parent::__construct();
        $this->load->model('PO/M_Reqpic');
        $this->load->model('PO/M_Purchase');
        $this->load->model('PO/M_Postatus');
        $this->load->library('form_validation');
    }

    public function index()
    {

        $data['title'] = 'Testing Function';


        $this->load->view('partial/header', $data);
        $this->load->view('partial/sidebar');
        $this->load->view('content/setting/testing', $data);
        $this->load->view('partial/footer');
    }
}
