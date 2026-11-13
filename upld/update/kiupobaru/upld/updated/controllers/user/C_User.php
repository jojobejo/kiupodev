<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 *
 */
class C_User extends CI_Controller

{


    function __construct()
    {
        parent::__construct();
        $this->load->model('User/M_User');
        $this->load->model('Admin/M_AdminData');
        $this->load->library('form_validation');
    }

    public function index()
    {
        require_super_admin();

        $data['title'] = 'User Management';
        $data["user"] = $this->M_User->getAll();

        $this->load->view('partial/header', $data);
        $this->load->view('partial/sidebar');
        $this->load->view('content/User/body', $data);
        $this->load->view('partial/footer');
        $this->load->view('content/User/datatables');
    }

    public function AddUser()
    {
        require_super_admin();

        $kd_user    = $this->input->post('kode_isi');
        $nama_user  = $this->input->post("nama_isi");
        $username   = $this->input->post("username_isi");
        $pass       = $this->input->post("password_isi");
        $departemen = $this->input->post("departemen_isi");
        $level       = $this->input->post("level_isi");

        if ($this->M_User->usernameExists($username)) {
            $this->session->set_flashdata("gagal", "Username sudah digunakan");
            redirect('usermanagement');
        }

        if ($kd_user == '' || $nama_user == '' || $username == '' || $pass == '' || $departemen == '-' || $level == '') {
            $this->session->set_flashdata("gagal", "Lengkapi semua data user");
            redirect('usermanagement');
        }

        $password   = password_hash($pass, PASSWORD_DEFAULT);

        $data = array(
            'kode_user'     => $kd_user,
            'nama_user'     => $nama_user,
            'username'      => $username,
            'password'      => $password,
            'aksess_lv'     => $level,
            'departement'   => strtoupper($departemen),
        );

        $this->M_User->addUser($data);
        $this->logAdminActivity('tbpo_user', 'User Management', 'kode_user', $kd_user, 'CREATE', null, $data);
        $this->session->set_flashdata("success", "User berhasil ditambahkan");
        redirect('usermanagement');
    }

    public function EditUser()
    {
        require_super_admin();

        $id_user    = $this->input->post('id_isi');
        $kd_user    = $this->input->post('kode_isi');
        $nama_user  = $this->input->post('nama_isi');
        $username   = $this->input->post("username_isi");
        $pass       = $this->input->post("password_isi");
        $departemen = $this->input->post("departemen_isi");
        $level      = $this->input->post("level_isi");

        if (!$this->M_User->getById($id_user)) {
            $this->session->set_flashdata("gagal", "User tidak ditemukan");
            redirect('usermanagement');
        }

        if ($this->M_User->usernameExists($username, $id_user)) {
            $this->session->set_flashdata("gagal", "Username sudah digunakan");
            redirect('usermanagement');
        }

        $data = array(
            'kode_user'     => $kd_user,
            'nama_user'     => $nama_user,
            'username'      => $username,
            'aksess_lv'     => $level,
            'departement'   => strtoupper($departemen),
        );

        if ($pass !== '') {
            $data['password'] = password_hash($pass, PASSWORD_DEFAULT);
        }

        $oldData = (array) $this->M_User->getById($id_user);
        $this->M_User->editUser($id_user, $data);
        $this->logAdminActivity('tbpo_user', 'User Management', 'id_user', $id_user, 'UPDATE', $oldData, $data);
        $this->session->set_flashdata("success", "User berhasil diubah");
        redirect('usermanagement');
    }

    public function DeleteUser($id_user)
    {
        require_super_admin();

        if ((string) $id_user === (string) $this->session->userdata('id')) {
            $this->session->set_flashdata("gagal", "User yang sedang login tidak dapat dihapus");
            redirect('usermanagement');
        }

        $oldData = (array) $this->M_User->getById($id_user);
        $this->M_User->deleteUser($id_user);
        $this->logAdminActivity('tbpo_user', 'User Management', 'id_user', $id_user, 'DELETE', $oldData, null);
        $this->session->set_flashdata("success", "User berhasil dihapus");
        redirect('usermanagement');
    }

    public function usersetting()
    {
        require_login();

        $kd_user    = $this->session->userdata('kode');

        $data['title']  = 'Account Setting';
        $data['info']   = $this->M_User->getInfoUser($kd_user)->result();

        $this->load->view('partial/header', $data);
        $this->load->view('partial/sidebar');
        $this->load->view('content/User/bodysetting', $data);
        $this->load->view('partial/footer');
    }
    public function editPassword()
    {
        $passold    = $this->input->post('passbaru');
        $kd_user    = $this->input->post('kduser');
        $password   = password_hash($passold, PASSWORD_DEFAULT);

        $changePass = array(
            'password' => $password
        );

        $this->M_User->editPassword($kd_user, $changePass);

        $this->session->set_flashdata("edited", "Data Berhasil Dirubah");
        redirect('Auth');
    }
    
    public function test()
    {

    }

    private function logAdminActivity($tableName, $tableLabel, $primaryKey, $primaryValue, $action, $oldData = null, $newData = null)
    {
        if (!$this->db->table_exists('tbpo_admin_activity_log')) {
            return;
        }

        $this->M_AdminData->addLog(array(
            'table_name' => $tableName,
            'table_label' => $tableLabel,
            'primary_key' => $primaryKey,
            'primary_value' => $primaryValue,
            'action' => $action,
            'old_data' => $oldData !== null ? json_encode($oldData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : null,
            'new_data' => $newData !== null ? json_encode($newData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : null,
            'kode_user' => $this->session->userdata('kode'),
            'username' => $this->session->userdata('username'),
            'nama_user' => $this->session->userdata('nama_user'),
            'ip_address' => $this->input->ip_address(),
            'user_agent' => substr((string) $this->input->user_agent(), 0, 255),
        ));
    }
}
