<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 *
 */
class C_Appsrated extends CI_Controller

{

    function __construct()
    {
        parent::__construct();
        $this->load->model('Setting/M_Appsrated');
        $this->load->library('form_validation');
    }

    public function index()
    {

        if ($this->session->userdata('lv') == '2') {

            $data['title']          = 'Module List';
            $data['getallmodule']   = $this->M_Appsrated->getAllModule();
            $data['genkdmod']       = $this->M_Appsrated->genkdmodnew();

            $this->load->view('partial/header', $data);
            $this->load->view('partial/sidebar');
            $this->load->view('content/setting/bodyappsrated', $data);
            $this->load->view('partial/footer');
        } elseif ($this->session->userdata('lv') == '4') {

            $kduser     = $this->session->userdata('kode');

            $data['title']  = 'Sosialisasi - New Module';
            $data['module'] = $this->M_Appsrated->getAllModule();
            $data['sos']    = $this->M_Appsrated->getDataSos($kduser)->result();
            $data['sos']    = $this->M_Appsrated->getDataSos($kduser)->result();

            $this->load->view('partial/header', $data);
            $this->load->view('partial/sidebar');
            $this->load->view('content/setting/bodyappsrated', $data);
            $this->load->view('partial/footer');
        }
    }
    public function addnewmodule()
    {
        $kdmod      = $this->input->post('genkd');
        $typemod    = $this->input->post('slectapps');
        $nmmod      = $this->input->post('nmbarang');
        $sts        = $this->input->post('jnismod');
        $createat   = $this->input->post('dtinput');
        $inptby     = $this->input->post('inputer');

        $datainpt   = array(
            'kdq_rate'      => $kdmod,
            'type_m'        => $typemod,
            'nm_module'     => $nmmod,
            'm_status'      => $sts,
            'create_at'     => $createat,
            'lastupdated'   => $inptby
        );
        $this->M_Appsrated->inputnew($datainpt);
        redirect('reviewapps');
    }
    public function modulereview($kd)
    {
        //INIT
        $user   = $this->session->userdata('kode');

        $data['title']          = 'Module Review';
        if ($this->session->userdata('lv') == '2') {
            $data['getallreview']   = $this->M_Appsrated->getallreviewmd($kd)->result();
            $data['kdreview']       = $this->M_Appsrated->generatekdreview($kd);
            $data['judul']          = $this->M_Appsrated->getjdl($kd)->result();
            $data['question']       = $this->M_Appsrated->getquestion($kd)->result();
        } elseif ($this->session->userdata('lv') == '4') {
            $data['getallreviewpic']   = $this->M_Appsrated->getallreviewmdpic($kd, $user)->result();
        }

        $this->load->view('partial/header', $data);
        $this->load->view('partial/sidebar');
        $this->load->view('content/setting/bodyreviewquest', $data);
        $this->load->view('partial/footer');
    }
    function addqbaru()
    {
        date_default_timezone_set("Asia/Jakarta");
        $kdreview   = $this->input->post('kdreview');
        $kdmodule   = $this->input->post('kdmodule');
        $question   = $this->input->post('question');
        $user       = $this->session->userdata('kode');
        $now = date("Y-m-d H:i:s");

        $inputdtquestion = array(
            'kd_reviewq'    => $kdreview,
            'kd_module'     => $kdmodule,
            'question'      => $question,
            'inputer'       => $user,
            'create_at'     => $now
        );

        $this->M_Appsrated->inputquestion($inputdtquestion);
        redirect('detailreview/' . $kdmodule);
    }
    public function questionreviewpic($kd)
    {
        //INIT
        $user   = $this->session->userdata('kode');

        $data['title']          = 'Module Review Rating';
        $data['judul']          = $this->M_Appsrated->getjdl($kd)->result();
        $data['countqna']       = $this->M_Appsrated->countqna($kd, $user)->result();
        $data['question']       = $this->M_Appsrated->getquestion($kd)->result();
        $data['answer']         = $this->M_Appsrated->getqnapic($user, $kd)->result();
        $data['getanswer']      = $this->M_Appsrated->getanswerpic($kd, $user)->result();
        $data['getnilai']       = $this->M_Appsrated->getnilaipic($kd, $user)->result();

        $this->load->view('partial/header', $data);
        $this->load->view('partial/sidebar');
        $this->load->view('content/setting/bodyqna', $data);
        $this->load->view('partial/footer');
    }


    public function reviewanswer()
    {
        date_default_timezone_set("Asia/Jakarta");
        $kdmodule   = $this->input->post('kodem');
        $kdreview   = $this->input->post('koder');
        $asnwer     = $this->input->post('asnwer');
        $nilai      = $this->input->post('nilai');
        $user       = $this->input->post('user');
        $now = date("Y-m-d H:i:s");

        foreach ($kdmodule as $key => $value) {
            $data[]  = array(
                'kd_module' => $value,
                'kd_reviewq' => $kdreview[$key],
                'isi_review' => $asnwer[$key],
                'nilai' => $nilai,
                'inputer' => $user,
                'create_at' => $now
            );
        }
        $this->M_Appsrated->inputanswer($data);
        redirect('questionreviewpic/' . $kdmodule);
    }
    public function addconfirmsos()
    {

        $kduser = $this->input->post('kduser');
        $tgl    = $this->input->post('tgl');

        $inputtososial = array(
            'module'            => "1",
            'kd_user'           => $kduser,
            'create_at'         => $tgl,
            'status_nkomersil'  => "1",
        );

        $this->M_Appsrated->inputsos($inputtososial);
        redirect('reviewapps');
    }
}
