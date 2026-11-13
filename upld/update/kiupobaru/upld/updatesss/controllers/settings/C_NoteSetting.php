<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 *
 */
class C_NoteSetting extends CI_Controller

{

    function __construct()
    {
        parent::__construct();
        $this->load->model('Setting/M_NoteSeting');
        $this->load->library('form_validation');
    }

    public function index()
    {

        $data['title']  = 'Note Setting';
        $data['note']   = $this->M_NoteSeting->get_all_nt_setting();
        $data['kdnote']   = $this->M_NoteSeting->generate_kd();

        $this->load->view('partial/header', $data);
        $this->load->view('partial/sidebar');
        $this->load->view('content/setting/notebody', $data);
        $this->load->view('partial/footer');
    }

    public function add_note_template()
    {
        $kdnote = $this->input->post('kd_nt_template');
        $nmnote = $this->input->post('nm_template');

        $datanote = array(
            'kd_nt_template'    => $kdnote,
            'nama_note'         => $nmnote
        );

        $this->M_NoteSeting->insert_nm_template($datanote);
        redirect('notetemplate');
    }

    public function detail_note_template($kdnote)
    {
        $data['title']  = 'Note Setting';
        $data['note']   = $this->M_NoteSeting->get_nt_setting($kdnote);
        $data['kdnote']   = $this->M_NoteSeting->generate_kd();

        $this->load->view('partial/header', $data);
        $this->load->view('partial/sidebar');
        $this->load->view('content/setting/detailnotebody', $data);
        $this->load->view('partial/footer');
    }

    public function update_note_template()
    {
        $id         = $this->input->post('id_isi');
        $kdnote     = $this->input->post('kd_note');
        $tjpenerima = $this->input->post('shipment_to');
        $almt       = $this->input->post('alamat_ship');
        $nmkoor     = $this->input->post('cp_shipment');
        $cptlp      = $this->input->post('no_cp');
        $ket1       = $this->input->post('ket_1_isi');

        $datanote = array(
            'shipment_to'   => $tjpenerima,
            'alamat_ship'   => $almt,
            'cp_shipment'   => $nmkoor,
            'no_cp'         => $cptlp,
            'ket_1'         => $ket1,
        );

        $this->M_NoteSeting->edit_nm_template($id, $datanote);
        redirect('notetemplate/') . $kdnote;
    }


    public function addPajak()
    {
        $satuanPajak    = $this->input->post('pajak_isi');

        $data = array(
            'nm_tax'   => $satuanPajak,

        );
        $this->M_NoteSeting->addPajak($data);

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
        $this->M_NoteSeting->editPajak($idtax, $data);

        redirect('taxseting');
    }
    public function hapusPajak($id)
    {
        $this->M_NoteSeting->hapusPajak($id);

        redirect('taxseting');
    }
}
