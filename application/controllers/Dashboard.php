<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 *
 */
class Dashboard extends CI_Controller

{
    function __construct()
    {
        parent::__construct();
        $this->load->model('M_Dashboard');
        $this->load->model('PO/M_PojasaExecution');
        // Keep Dashboard functional when a deployment still has an older
        // autoload.php that does not list the access helper.
        $this->load->helper('access');
        $this->load->helper('pojasa_authorization');
        $this->load->library('form_validation');
    }

    public function index()
    {
        $pojasaSummary = $this->M_PojasaExecution->dashboard_summary(pojasa_session_context());

        // VIEW PURCHASING

        if (is_super_admin() || $this->session->userdata('lv') == '2') {
            $data['title']  = 'Dashboard';
            $data['pojasa_summary'] = $pojasaSummary;
            $kduser = $this->session->userdata("kode");

            $data['all']        = $this->M_Dashboard->totalAll($kduser)->result();
            $data['done']       = $this->M_Dashboard->totalDone($kduser)->result();
            $data['progress']   = $this->M_Dashboard->totalOnProgress($kduser)->result();
            $data['reject']     = $this->M_Dashboard->totalReject($kduser)->result();
            $data['restock']    = $this->M_Dashboard->totalRestock()->result();
            $data['reqmrbarag'] = $this->M_Dashboard->getreqmasterbarang()->result();

            $this->load->view('partial/header', $data);
            $this->load->view('partial/sidebar');
            $this->load->view('content/dashboard', $data);
            $this->load->view('partial/footer');
        }

        // VIEW KARYAWAN

        elseif ($this->session->userdata('lv') == '4' && $this->session->userdata('kode') != 'KEULOGICS01') {
            $data['title']  = 'Dashboard';
            $data['pojasa_summary'] = $pojasaSummary;
            $kduser = $this->session->userdata("kode");

            $data['all']    = $this->M_Dashboard->totalAll($kduser)->result();
            $data['done']   = $this->M_Dashboard->totalDone($kduser)->result();
            $data['progress'] = $this->M_Dashboard->totalOnProgress($kduser)->result();
            $data['reject'] = $this->M_Dashboard->totalReject($kduser)->result();

            $this->load->view('partial/header', $data);
            $this->load->view('partial/sidebar');
            $this->load->view('content/dashboard', $data);
            $this->load->view('partial/footer');
        }

        // VIEW KADEP
        elseif ($this->session->userdata('lv') == '5') {
            $data['title']  = 'Dashboard';
            $data['pojasa_summary'] = $pojasaSummary;
            $kduser = $this->session->userdata("kode");

            $data['all']    = $this->M_Dashboard->totalAll($kduser)->result();
            $data['done']   = $this->M_Dashboard->totalDone($kduser)->result();
            $data['progress'] = $this->M_Dashboard->totalOnProgress($kduser)->result();
            $data['reject'] = $this->M_Dashboard->totalReject($kduser)->result();

            $this->load->view('partial/header', $data);
            $this->load->view('partial/sidebar');
            $this->load->view('content/dashboard', $data);
            $this->load->view('partial/footer');
        }

        // VIEW DIREKTUR
        elseif (in_array((string) $this->session->userdata('lv'), array('3', '6'), true)) {
            $data['title']  = 'Dashboard';
            $data['pojasa_summary'] = $pojasaSummary;
            $kduser = $this->session->userdata("kode");

            $data['all']    = $this->M_Dashboard->totalAll($kduser)->result();
            $data['done']   = $this->M_Dashboard->totalDone($kduser)->result();
            $data['progress'] = $this->M_Dashboard->totalOnProgress($kduser)->result();
            $data['reject'] = $this->M_Dashboard->totalReject($kduser)->result();

            $this->load->view('partial/header', $data);
            $this->load->view('partial/sidebar');
            $this->load->view('content/dashboard', $data);
            $this->load->view('partial/footer');
        } elseif ($this->session->userdata('lv') == '4' && $this->session->userdata('kode') == 'KEULOGICS01') {

            $data['title']  = 'Dashboard';
            $data['pojasa_summary'] = $pojasaSummary;
            $kduser = $this->session->userdata("kode");

            $data['all']    = $this->M_Dashboard->totalAll($kduser)->result();
            $data['done']   = $this->M_Dashboard->totalDone($kduser)->result();
            $data['progress'] = $this->M_Dashboard->totalOnProgress($kduser)->result();
            $data['reject'] = $this->M_Dashboard->totalReject($kduser)->result();

            $this->load->view('partial/header', $data);
            $this->load->view('partial/sidebar_ics');
            $this->load->view('content/dashboards/dashboard.php', $data);
            $this->load->view('partial/footer');
        }
    }
}
