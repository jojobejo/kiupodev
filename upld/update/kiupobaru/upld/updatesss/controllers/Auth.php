<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 *
 */

class Auth extends CI_Controller
{
    function __construct()
    {
        parent::__construct();
        $this->load->model('Auth/M_Auth');
    }

    function index()
    {
        $this->load->view('partial/Auth/head');
        $this->load->view('content/Auth/body');
        $this->load->view('partial/Auth/footer');
    }

    function process()
    {
        $username = $this->input->post('user_isi');
        $password = $this->input->post('pass_isi');

        $check_username = $this->M_Auth->cek_username($username)->num_rows();

        if ($check_username > 0) {

            $check_password = $this->M_Auth->cek_password($username);

            foreach ($check_password as $key) {
                if ($key->username == $username && password_verify($password, $key->password)) {
                    $data_session = array(
                        'id'            => $key->id_user,
                        'kode'          => $key->kode_user,
                        'username'      => $key->username,
                        'nama_user'     => $key->nama_user,
                        'departemen'    => strtoupper($key->departement),
                        'lv'            => $key->aksess_lv,
                        'is_super_admin'=> ((string) $key->aksess_lv === '1' || strtoupper($key->departement) === 'ADMIN' || strtolower($key->username) === 'admin') ? 1 : 0,
                        'status'        => "is_login"
                    );
                    $this->session->set_userdata($data_session);
                    redirect('dashboard');
                } else {
                    $this->session->set_flashdata("gagal", "password salah !");
                    redirect('Auth');
                }
            }
        } else {
            $this->session->set_flashdata("gagal", "username salah");
            redirect('Auth');
        }
    }

    function logout()
    {
        $this->session->set_flashdata("logout", "Berhasil Log Out");
        $this->session->sess_destroy();
        redirect('Auth');
    }
}
