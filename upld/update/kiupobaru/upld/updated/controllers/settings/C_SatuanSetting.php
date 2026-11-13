<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 *
 */
class C_SatuanSetting extends CI_Controller

{

    function __construct()
    {
        parent::__construct();
        $this->load->model('Setting/M_SatuanSetting');
        $this->load->library('form_validation');
    }

    public function index()
    {

        $data['title']  = 'Satuan Setting';
        $data['satuan']    = $this->M_SatuanSetting->getAll()->result();

        $this->load->view('partial/header', $data);
        $this->load->view('partial/sidebar');
        $this->load->view('content/setting/satuanbody', $data);
        $this->load->view('partial/footer');
    }

    public function addSatuanBarang()
    {
        $satuanBarang    = $this->input->post('nm_barang');

        $data = array(
            'nm_satuan'   => $satuanBarang,

        );
        $this->M_SatuanSetting->addSatuan($data);

        redirect('satuansetting');
    }

    public function editSatuan()
    {
        $satuanBr    = $this->input->post('nm_isi');
        $idsatuan          = $this->input->post('id_isi');

        $data = array(
            'id_satuan'   => $idsatuan,
            'nm_satuan'   => $satuanBr

        );
        $this->M_SatuanSetting->editSatuan($idsatuan, $data);

        redirect('satuansetting');
    }
    public function hapusSatuan($id)
    {
        $this->M_SatuanSetting->hapusSatuan($id);

        redirect('satuansetting');
    }
}
