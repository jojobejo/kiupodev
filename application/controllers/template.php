<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 *
 */
class nama_controler extends CI_Controller

{

    function __construct()
    {
        parent::__construct();
        $this->load->model('/');
        $this->load->library('form_validation');
    }

    public function index()
    {

        $data['title'] = 'User';
        $data["user"] = $this->M_User->getAll();

        $this->load->view('partial/header', $data);
        $this->load->view('partial/sidebar');
        $this->load->view('content/', $data);
        $this->load->view('partial/footer');
    }
}
