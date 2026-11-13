<?php
defined('BASEPATH') or exit('No direct script access allowed');

class C_Formulapo extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('Setting/M_Formulapo');
        $this->load->library('form_validation');

        if ($this->session->userdata('departemen') != 'KEUANGAN' || $this->session->userdata('lv') != '2') {
            show_error('Access denied', 403);
        }
    }

    public function index()
    {
        $data['title'] = 'Formula PO';

        $this->load->view('partial/header', $data);
        $this->load->view('partial/sidebar');
        $this->load->view('settings/formulapo/index', $data);
        $this->load->view('partial/footer');
    }

    public function ajax_list_formula()
    {
        $this->json_response(TRUE, 'Success', $this->M_Formulapo->get_formula_all());
    }

    public function ajax_get_formula($id_formula = NULL)
    {
        $id_formula = (int) $id_formula;
        $formula = $this->M_Formulapo->get_formula_by_id($id_formula);

        if (!$formula) {
            $this->json_response(FALSE, 'Formula tidak ditemukan');
            return;
        }

        $this->json_response(TRUE, 'Success', array(
            'formula' => $formula,
            'variables' => $this->M_Formulapo->get_variables_by_formula($id_formula)
        ));
    }

    public function ajax_save_formula()
    {
        if ($this->input->method(TRUE) != 'POST') {
            $this->json_response(FALSE, 'Method tidak valid');
            return;
        }

        $post = $this->input->post(NULL, TRUE);
        $id_formula = isset($post['id_formula']) ? (int) $post['id_formula'] : 0;
        $rounding_mode = isset($post['rounding_mode']) ? $post['rounding_mode'] : 'none';
        $status = isset($post['status']) ? (int) $post['status'] : 1;

        if (!in_array($rounding_mode, array('none', 'round', 'ceil', 'floor'))) {
            $this->json_response(FALSE, 'Rounding mode tidak valid');
            return;
        }

        $formula_data = array(
            'id_formula' => $id_formula,
            'kode_formula' => isset($post['kode_formula']) ? strtoupper(trim($post['kode_formula'])) : '',
            'nama_formula' => isset($post['nama_formula']) ? trim($post['nama_formula']) : '',
            'deskripsi' => isset($post['deskripsi']) ? trim($post['deskripsi']) : NULL,
            'formula_expression' => isset($post['formula_expression']) ? trim($post['formula_expression']) : '',
            'output_label' => isset($post['output_label']) ? trim($post['output_label']) : '',
            'output_unit' => isset($post['output_unit']) ? trim($post['output_unit']) : NULL,
            'rounding_mode' => $rounding_mode,
            'decimal_place' => isset($post['decimal_place']) ? (int) $post['decimal_place'] : 2,
            'status' => $status == 1 ? 1 : 0
        );

        if ($formula_data['kode_formula'] == '' || !preg_match('/^[A-Z0-9_]+$/', $formula_data['kode_formula'])) {
            $this->json_response(FALSE, 'Kode formula wajib diisi dengan huruf, angka, atau underscore');
            return;
        }

        if ($formula_data['nama_formula'] == '' || $formula_data['formula_expression'] == '' || $formula_data['output_label'] == '') {
            $this->json_response(FALSE, 'Nama, expression, dan output label wajib diisi');
            return;
        }

        if ($formula_data['decimal_place'] < 0 || $formula_data['decimal_place'] > 6) {
            $this->json_response(FALSE, 'Decimal place harus 0 sampai 6');
            return;
        }

        $variables = $this->build_variables_from_post($post);
        if (empty($variables)) {
            $this->json_response(FALSE, 'Minimal satu variable wajib diisi');
            return;
        }

        $sample_input = array();
        foreach ($variables as $variable) {
            $sample_input[$variable['variable_key']] = $variable['default_value'] !== NULL ? $variable['default_value'] : 1;
        }

        $calculate_check = $this->M_Formulapo->calculate_formula($formula_data['formula_expression'], $sample_input);
        if (!$calculate_check['status']) {
            $this->json_response(FALSE, $calculate_check['message']);
            return;
        }

        $saved_id = $this->M_Formulapo->save_formula_with_variables($formula_data, $variables);
        if (!$saved_id) {
            $this->json_response(FALSE, 'Gagal menyimpan formula');
            return;
        }

        $this->json_response(TRUE, 'Success', array('id_formula' => $saved_id));
    }

    public function ajax_delete_formula()
    {
        if ($this->input->method(TRUE) != 'POST') {
            $this->json_response(FALSE, 'Method tidak valid');
            return;
        }

        $id_formula = (int) $this->input->post('id_formula', TRUE);
        if ($id_formula <= 0) {
            $this->json_response(FALSE, 'ID formula tidak valid');
            return;
        }

        if (!$this->M_Formulapo->delete_formula($id_formula)) {
            $this->json_response(FALSE, 'Gagal menghapus formula');
            return;
        }

        $this->json_response(TRUE, 'Success');
    }

    public function ajax_get_variables($id_formula = NULL)
    {
        $id_formula = (int) $id_formula;
        $formula = $this->M_Formulapo->get_formula_by_id($id_formula);

        if (!$formula) {
            $this->json_response(FALSE, 'Formula tidak ditemukan');
            return;
        }

        $this->json_response(TRUE, 'Success', array(
            'formula' => $formula,
            'variables' => $this->M_Formulapo->get_variables_by_formula($id_formula)
        ));
    }

    public function ajax_calculate()
    {
        if ($this->input->method(TRUE) != 'POST') {
            $this->json_response(FALSE, 'Method tidak valid');
            return;
        }

        $id_formula = (int) $this->input->post('id_formula', TRUE);
        $formula = $this->M_Formulapo->get_formula_by_id($id_formula);

        if (!$formula || (int) $formula->status != 1) {
            $this->json_response(FALSE, 'Formula tidak aktif atau tidak ditemukan');
            return;
        }

        $variables = $this->M_Formulapo->get_variables_by_formula($id_formula);
        $input_data = $this->input->post('input', TRUE);
        if (!is_array($input_data)) {
            $input_data = array();
        }

        $clean_input = array();
        foreach ($variables as $variable) {
            $value = isset($input_data[$variable->variable_key]) ? $input_data[$variable->variable_key] : '';
            if ($value === '' && $variable->default_value !== NULL) {
                $value = $variable->default_value;
            }

            if ((int) $variable->is_required == 1 && $value === '') {
                $this->json_response(FALSE, $variable->variable_label . ' wajib diisi');
                return;
            }

            if ($value !== '' && !is_numeric($value)) {
                $this->json_response(FALSE, $variable->variable_label . ' harus numeric');
                return;
            }

            if ($value !== '') {
                $clean_input[$variable->variable_key] = $value;
            }
        }

        $result = $this->M_Formulapo->calculate_formula($formula->formula_expression, $clean_input);
        if (!$result['status']) {
            $this->json_response(FALSE, $result['message']);
            return;
        }

        $result_value = $this->apply_rounding($result['result'], $formula->rounding_mode, (int) $formula->decimal_place);

        $this->json_response(TRUE, 'Success', array(
            'result_value' => $result_value,
            'result_label' => $formula->output_label,
            'result_unit' => $formula->output_unit,
            'expression' => $formula->formula_expression,
            'input' => $clean_input
        ));
    }

    public function ajax_save_result()
    {
        if ($this->input->method(TRUE) != 'POST') {
            $this->json_response(FALSE, 'Method tidak valid');
            return;
        }

        $id_po_detail = $this->input->post('id_po_detail', TRUE);
        $id_formula = (int) $this->input->post('id_formula', TRUE);
        $input_data = $this->input->post('input', TRUE);

        if (!is_array($input_data)) {
            $input_data = array();
        }

        $formula = $this->M_Formulapo->get_formula_by_id($id_formula);
        if (!$formula) {
            $this->json_response(FALSE, 'Formula tidak ditemukan');
            return;
        }

        $calculate = $this->calculate_by_formula($formula, $input_data);
        if (!$calculate['status']) {
            $this->json_response(FALSE, $calculate['message']);
            return;
        }

        $data = array(
            'id_po_detail' => $id_po_detail !== '' ? (int) $id_po_detail : NULL,
            'id_formula' => $id_formula,
            'input_json' => json_encode($calculate['data']['input']),
            'formula_expression' => $formula->formula_expression,
            'result_value' => $calculate['data']['result_value'],
            'result_label' => $formula->output_label,
            'result_unit' => $formula->output_unit,
            'created_by' => $this->session->userdata('kode')
        );

        if (!$this->M_Formulapo->save_result($data)) {
            $this->json_response(FALSE, 'Gagal menyimpan hasil formula');
            return;
        }

        $this->json_response(TRUE, 'Success', $data);
    }

    public function seed_default_formula()
    {
        $seeds = $this->default_formula_data();
        $inserted = 0;

        foreach ($seeds as $seed) {
            $exists = $this->db->where('kode_formula', $seed['formula']['kode_formula'])
                ->get('tbpo_formula')
                ->row();

            if ($exists) {
                continue;
            }

            $this->M_Formulapo->save_formula_with_variables($seed['formula'], $seed['variables']);
            $inserted++;
        }

        $this->json_response(TRUE, 'Success', array('inserted' => $inserted));
    }

    private function build_variables_from_post($post)
    {
        $keys = isset($post['variable_key']) && is_array($post['variable_key']) ? $post['variable_key'] : array();
        $labels = isset($post['variable_label']) && is_array($post['variable_label']) ? $post['variable_label'] : array();
        $types = isset($post['input_type']) && is_array($post['input_type']) ? $post['input_type'] : array();
        $units = isset($post['unit']) && is_array($post['unit']) ? $post['unit'] : array();
        $defaults = isset($post['default_value']) && is_array($post['default_value']) ? $post['default_value'] : array();
        $required = isset($post['is_required']) && is_array($post['is_required']) ? $post['is_required'] : array();
        $sorts = isset($post['sort_order']) && is_array($post['sort_order']) ? $post['sort_order'] : array();
        $variables = array();
        $used_keys = array();

        foreach ($keys as $index => $key) {
            $key = trim($key);
            $label = isset($labels[$index]) ? trim($labels[$index]) : '';
            $type = isset($types[$index]) ? $types[$index] : 'decimal';

            if ($key == '' && $label == '') {
                continue;
            }

            if (!preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $key)) {
                return array();
            }

            if ($label == '' || in_array($key, $used_keys) || !in_array($type, array('number', 'decimal', 'currency'))) {
                return array();
            }

            $default_value = isset($defaults[$index]) ? trim($defaults[$index]) : '';
            if ($default_value !== '' && !is_numeric($default_value)) {
                return array();
            }

            $variables[] = array(
                'variable_key' => $key,
                'variable_label' => $label,
                'input_type' => $type,
                'unit' => isset($units[$index]) && trim($units[$index]) !== '' ? trim($units[$index]) : NULL,
                'default_value' => $default_value !== '' ? $default_value : NULL,
                'is_required' => isset($required[$index]) && $required[$index] == '1' ? 1 : 0,
                'sort_order' => isset($sorts[$index]) ? (int) $sorts[$index] : $index
            );
            $used_keys[] = $key;
        }

        return $variables;
    }

    private function calculate_by_formula($formula, $input_data)
    {
        $variables = $this->M_Formulapo->get_variables_by_formula($formula->id_formula);
        $clean_input = array();

        foreach ($variables as $variable) {
            $value = isset($input_data[$variable->variable_key]) ? $input_data[$variable->variable_key] : '';
            if ($value === '' && $variable->default_value !== NULL) {
                $value = $variable->default_value;
            }
            if ((int) $variable->is_required == 1 && $value === '') {
                return array('status' => FALSE, 'message' => $variable->variable_label . ' wajib diisi');
            }
            if ($value !== '' && !is_numeric($value)) {
                return array('status' => FALSE, 'message' => $variable->variable_label . ' harus numeric');
            }
            if ($value !== '') {
                $clean_input[$variable->variable_key] = $value;
            }
        }

        $result = $this->M_Formulapo->calculate_formula($formula->formula_expression, $clean_input);
        if (!$result['status']) {
            return $result;
        }

        return array(
            'status' => TRUE,
            'message' => 'Success',
            'data' => array(
                'result_value' => $this->apply_rounding($result['result'], $formula->rounding_mode, (int) $formula->decimal_place),
                'input' => $clean_input
            )
        );
    }

    private function apply_rounding($value, $rounding_mode, $decimal_place)
    {
        if ($rounding_mode == 'round') {
            return round($value, $decimal_place);
        }
        if ($rounding_mode == 'ceil') {
            $multiplier = pow(10, $decimal_place);
            return ceil($value * $multiplier) / $multiplier;
        }
        if ($rounding_mode == 'floor') {
            $multiplier = pow(10, $decimal_place);
            return floor($value * $multiplier) / $multiplier;
        }

        return (float) $value;
    }

    private function json_response($status, $message, $data = NULL)
    {
        $response = array(
            'status' => $status,
            'message' => $message,
            'data' => $data
        );

        $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode($response));
    }

    private function default_formula_data()
    {
        return array(
            array(
                'formula' => array('kode_formula' => 'HARGA_SATUAN', 'nama_formula' => 'Harga Per Satuan Terkecil', 'deskripsi' => NULL, 'formula_expression' => 'harga_per_box / isi_per_dos', 'output_label' => 'Harga Satuan', 'output_unit' => 'Rupiah', 'rounding_mode' => 'none', 'decimal_place' => 2, 'status' => 1),
                'variables' => array(
                    array('variable_key' => 'harga_per_box', 'variable_label' => 'Harga Per Box', 'input_type' => 'currency', 'unit' => 'Rupiah', 'default_value' => NULL, 'is_required' => 1, 'sort_order' => 1),
                    array('variable_key' => 'isi_per_dos', 'variable_label' => 'Isi Per Dos', 'input_type' => 'decimal', 'unit' => 'pcs', 'default_value' => NULL, 'is_required' => 1, 'sort_order' => 2)
                )
            ),
            array(
                'formula' => array('kode_formula' => 'HARGA_KEMASAN_DARI_KG_LITER', 'nama_formula' => 'Harga botol dari harga Kg/Liter', 'deskripsi' => NULL, 'formula_expression' => 'harga_per_kg_liter * isi_kemasan / 1000', 'output_label' => 'Harga Per Kemasan', 'output_unit' => 'Rupiah', 'rounding_mode' => 'none', 'decimal_place' => 2, 'status' => 1),
                'variables' => array(
                    array('variable_key' => 'harga_per_kg_liter', 'variable_label' => 'Harga Per Kg/Liter', 'input_type' => 'currency', 'unit' => 'Rupiah', 'default_value' => NULL, 'is_required' => 1, 'sort_order' => 1),
                    array('variable_key' => 'isi_kemasan', 'variable_label' => 'Isi Kemasan', 'input_type' => 'decimal', 'unit' => 'ml/gr', 'default_value' => NULL, 'is_required' => 1, 'sort_order' => 2)
                )
            ),
            array(
                'formula' => array('kode_formula' => 'TOTAL_DOS_TANPA_INNER', 'nama_formula' => 'Total Liter/Kg menjadi dos tanpa inner', 'deskripsi' => NULL, 'formula_expression' => 'total_liter_kg * 1000 / isi_kemasan / isi_per_dos', 'output_label' => 'Jumlah Dos', 'output_unit' => 'Dos', 'rounding_mode' => 'none', 'decimal_place' => 2, 'status' => 1),
                'variables' => array(
                    array('variable_key' => 'total_liter_kg', 'variable_label' => 'Total Liter/Kg', 'input_type' => 'decimal', 'unit' => 'L/Kg', 'default_value' => NULL, 'is_required' => 1, 'sort_order' => 1),
                    array('variable_key' => 'isi_kemasan', 'variable_label' => 'Isi Kemasan', 'input_type' => 'decimal', 'unit' => 'ml/gr', 'default_value' => NULL, 'is_required' => 1, 'sort_order' => 2),
                    array('variable_key' => 'isi_per_dos', 'variable_label' => 'Isi Per Dos', 'input_type' => 'decimal', 'unit' => 'pcs', 'default_value' => NULL, 'is_required' => 1, 'sort_order' => 3)
                )
            ),
            array(
                'formula' => array('kode_formula' => 'TOTAL_DOS_DENGAN_INNER', 'nama_formula' => 'Total Liter/Kg menjadi dos dengan inner', 'deskripsi' => NULL, 'formula_expression' => 'total_liter_kg * 1000 / isi_kemasan / isi_per_inner / inner_per_dos', 'output_label' => 'Jumlah Dos', 'output_unit' => 'Dos', 'rounding_mode' => 'none', 'decimal_place' => 2, 'status' => 1),
                'variables' => array(
                    array('variable_key' => 'total_liter_kg', 'variable_label' => 'Total Liter/Kg', 'input_type' => 'decimal', 'unit' => 'L/Kg', 'default_value' => NULL, 'is_required' => 1, 'sort_order' => 1),
                    array('variable_key' => 'isi_kemasan', 'variable_label' => 'Isi Kemasan', 'input_type' => 'decimal', 'unit' => 'ml/gr', 'default_value' => NULL, 'is_required' => 1, 'sort_order' => 2),
                    array('variable_key' => 'isi_per_inner', 'variable_label' => 'Isi Per Inner', 'input_type' => 'decimal', 'unit' => 'pcs', 'default_value' => NULL, 'is_required' => 1, 'sort_order' => 3),
                    array('variable_key' => 'inner_per_dos', 'variable_label' => 'Inner Per Dos', 'input_type' => 'decimal', 'unit' => 'inner', 'default_value' => NULL, 'is_required' => 1, 'sort_order' => 4)
                )
            ),
            array(
                'formula' => array('kode_formula' => 'ISI_DOS_TANPA_INNER', 'nama_formula' => 'Isi 1 dos dalam Liter/Kg tanpa inner', 'deskripsi' => NULL, 'formula_expression' => 'isi_per_dos * isi_kemasan / 1000', 'output_label' => 'Isi Per Dos', 'output_unit' => 'L/Kg', 'rounding_mode' => 'none', 'decimal_place' => 2, 'status' => 1),
                'variables' => array(
                    array('variable_key' => 'isi_per_dos', 'variable_label' => 'Isi Per Dos', 'input_type' => 'decimal', 'unit' => 'pcs', 'default_value' => NULL, 'is_required' => 1, 'sort_order' => 1),
                    array('variable_key' => 'isi_kemasan', 'variable_label' => 'Isi Kemasan', 'input_type' => 'decimal', 'unit' => 'ml/gr', 'default_value' => NULL, 'is_required' => 1, 'sort_order' => 2)
                )
            ),
            array(
                'formula' => array('kode_formula' => 'ISI_DOS_DENGAN_INNER', 'nama_formula' => 'Isi 1 dos dalam Liter/Kg dengan inner', 'deskripsi' => NULL, 'formula_expression' => 'isi_per_inner * inner_per_dos * isi_kemasan / 1000', 'output_label' => 'Isi Per Dos', 'output_unit' => 'L/Kg', 'rounding_mode' => 'none', 'decimal_place' => 2, 'status' => 1),
                'variables' => array(
                    array('variable_key' => 'isi_per_inner', 'variable_label' => 'Isi Per Inner', 'input_type' => 'decimal', 'unit' => 'pcs', 'default_value' => NULL, 'is_required' => 1, 'sort_order' => 1),
                    array('variable_key' => 'inner_per_dos', 'variable_label' => 'Inner Per Dos', 'input_type' => 'decimal', 'unit' => 'inner', 'default_value' => NULL, 'is_required' => 1, 'sort_order' => 2),
                    array('variable_key' => 'isi_kemasan', 'variable_label' => 'Isi Kemasan', 'input_type' => 'decimal', 'unit' => 'ml/gr', 'default_value' => NULL, 'is_required' => 1, 'sort_order' => 3)
                )
            )
        );
    }
}
