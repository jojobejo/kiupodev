<?php
defined('BASEPATH') or exit('No direct script access allowed');

class C_Suplier extends CI_Controller

{


    function __construct()
    {
        parent::__construct();
        $this->load->model('Suplier/M_Suplier');
        $this->load->library('form_validation');
    }

    public function index()
    {

        $data['title'] = 'Suplier';
        $data["Suplier"] = $this->M_Suplier->getAll();

        $this->load->view('partial/header', $data);
        $this->load->view('partial/sidebar');
        $this->load->view('content/suplier/body', $data);
        $this->load->view('partial/footer');
    }
    
}
