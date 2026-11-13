<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 *
 */
class C_TaxSetting extends CI_Controller

{

    function __construct()
    {
        parent::__construct();
        $this->load->model('Setting/M_TaxSetting');
        $this->load->library('form_validation');
    }

    public function index()
    {

        $data['title']  = 'Tax Setting';
        $data['tax']    = $this->M_TaxSetting->getAll()->result();

        $this->load->view('partial/header', $data);
        $this->load->view('partial/sidebar');
        $this->load->view('content/setting/taxbody', $data);
        $this->load->view('partial/footer');
    }

    public function addPajak()
    {
        $satuanPajak    = $this->input->post('pajak_isi');

        $data = array(
            'nm_tax'   => $satuanPajak,

        );
        $this->M_TaxSetting->addPajak($data);

        redirect('taxseting');
    }

    public function editTax()
    {
        $satuanPajak    = $this->input->post('pajak_isi');
        $idtax          = $this->input->post('id_isi');

        $data = array(
            'id_tax'   => $idtax,
            'nm_tax'   => $satuanPajak

        );
        $this->M_TaxSetting->editPajak($idtax,$data);

        redirect('taxseting');
    }
    public function hapusPajak($id)
    {
        $this->M_TaxSetting->hapusPajak($id);

        redirect('taxseting');
    }
}
