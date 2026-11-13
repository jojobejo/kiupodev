<?php
defined('BASEPATH') or exit('No direct script access allowed');

class C_AdminData extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('Admin/M_AdminData');
        $this->load->library('form_validation');
    }

    public function index($tableKey = 'po_komersil')
    {
        require_super_admin();

        $config = $this->M_AdminData->getTableConfig($tableKey);
        if (!$config) {
            show_404();
            return;
        }

        $data['title'] = 'Admin View Data';
        $data['tables'] = $this->M_AdminData->getTables();
        $data['active_key'] = $tableKey;
        $data['active_table'] = $config;
        $data['columns'] = $this->M_AdminData->getColumns($config['table']);
        $limitInput = $this->input->get('limit', true);
        $data['limit'] = in_array($limitInput, array('500', '2000', 'all'), true) ? $limitInput : '500';
        $data['rows'] = $this->M_AdminData->getRows($config, $data['limit'] === 'all' ? null : (int) $data['limit']);
        $data['logs'] = $this->M_AdminData->getLogs(100);

        $this->load->view('partial/header', $data);
        $this->load->view('partial/sidebar');
        $this->load->view('content/admin/data_view', $data);
        $this->load->view('partial/footer');
        $this->load->view('content/admin/data_view_js', $data);
    }

    public function update()
    {
        require_super_admin();

        $tableKey = $this->input->post('table_key', true);
        $pkValue = $this->input->post('pk_value', true);
        $fields = $this->input->post('fields');
        $config = $this->M_AdminData->getTableConfig($tableKey);

        if (!$config || empty($config['primary_key']) || $pkValue === null || !is_array($fields)) {
            $this->session->set_flashdata('error', 'Data edit tidak valid.');
            redirect('admin-data');
            return;
        }

        $oldData = $this->M_AdminData->getRowByPk($config, $pkValue);
        if (!$oldData) {
            $this->session->set_flashdata('error', 'Data tidak ditemukan.');
            redirect('admin-data/table/' . $tableKey);
            return;
        }

        $columns = $this->M_AdminData->getColumns($config['table']);
        $editableColumns = array();
        foreach ($columns as $column) {
            if ($column->name === $config['primary_key']) {
                continue;
            }
            $editableColumns[$column->name] = $column;
        }

        $newData = array();
        foreach ($fields as $field => $value) {
            if (!isset($editableColumns[$field])) {
                continue;
            }
            if ($config['table'] === 'tbpo_user' && $field === 'password' && $value !== '' && !$this->looksLikePasswordHash($value)) {
                $value = password_hash($value, PASSWORD_DEFAULT);
            }
            $newData[$field] = $value;
        }

        if (empty($newData)) {
            $this->session->set_flashdata('error', 'Tidak ada field yang dapat diupdate.');
            redirect('admin-data/table/' . $tableKey);
            return;
        }

        $changedOld = array();
        $changedNew = array();
        foreach ($newData as $field => $value) {
            $oldValue = array_key_exists($field, $oldData) ? (string) $oldData[$field] : '';
            if ($oldValue !== (string) $value) {
                $changedOld[$field] = array_key_exists($field, $oldData) ? $oldData[$field] : null;
                $changedNew[$field] = $value;
            }
        }

        if (empty($changedNew)) {
            $this->session->set_flashdata('success', 'Tidak ada perubahan data.');
            redirect('admin-data/table/' . $tableKey);
            return;
        }

        $this->db->trans_begin();
        $this->M_AdminData->updateRow($config, $pkValue, $newData);
        $this->writeLog($config, $pkValue, 'UPDATE', $changedOld, $changedNew);

        if ($this->db->trans_status() === false) {
            $this->db->trans_rollback();
            $this->session->set_flashdata('error', 'Gagal menyimpan perubahan.');
            redirect('admin-data/table/' . $tableKey);
            return;
        }

        $this->db->trans_commit();
        $this->session->set_flashdata('success', 'Data berhasil diupdate dan aktivitas tersimpan di log.');
        redirect('admin-data/table/' . $tableKey);
    }

    private function writeLog($config, $pkValue, $action, $oldData, $newData)
    {
        $this->M_AdminData->addLog(array(
            'table_name' => $config['table'],
            'table_label' => $config['label'],
            'primary_key' => $config['primary_key'],
            'primary_value' => $pkValue,
            'action' => $action,
            'old_data' => json_encode($oldData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'new_data' => json_encode($newData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'kode_user' => $this->session->userdata('kode'),
            'username' => $this->session->userdata('username'),
            'nama_user' => $this->session->userdata('nama_user'),
            'ip_address' => $this->input->ip_address(),
            'user_agent' => substr((string) $this->input->user_agent(), 0, 255),
        ));
    }

    private function looksLikePasswordHash($value)
    {
        return preg_match('/^\\$(2y|2a|2b|argon2i|argon2id)\\$/', (string) $value) === 1;
    }
}
