<?php
defined('BASEPATH') or exit('No direct script access allowed');

class M_Formulapo extends CI_Model
{
    public function __construct()
    {
        parent::__construct();
    }

    public function get_formula_all()
    {
        return $this->db->select('*')
            ->from('tbpo_formula')
            ->order_by('id_formula', 'DESC')
            ->get()
            ->result();
    }

    public function get_formula_by_id($id_formula)
    {
        return $this->db->where('id_formula', $id_formula)
            ->get('tbpo_formula')
            ->row();
    }

    public function insert_formula($data)
    {
        $this->db->insert('tbpo_formula', $data);
        return $this->db->insert_id();
    }

    public function update_formula($id_formula, $data)
    {
        $this->db->where('id_formula', $id_formula);
        return $this->db->update('tbpo_formula', $data);
    }

    public function delete_formula($id_formula)
    {
        $this->db->where('id_formula', $id_formula);
        return $this->db->delete('tbpo_formula');
    }

    public function get_variables_by_formula($id_formula)
    {
        return $this->db->where('id_formula', $id_formula)
            ->order_by('sort_order', 'ASC')
            ->order_by('id_variable', 'ASC')
            ->get('tbpo_formula_variable')
            ->result();
    }

    public function insert_variable($data)
    {
        return $this->db->insert('tbpo_formula_variable', $data);
    }

    public function delete_variables_by_formula($id_formula)
    {
        $this->db->where('id_formula', $id_formula);
        return $this->db->delete('tbpo_formula_variable');
    }

    public function save_formula_with_variables($formula_data, $variables)
    {
        $this->db->trans_start();

        $id_formula = isset($formula_data['id_formula']) ? (int) $formula_data['id_formula'] : 0;
        unset($formula_data['id_formula']);

        if ($id_formula > 0) {
            $formula_data['updated_at'] = date('Y-m-d H:i:s');
            $this->update_formula($id_formula, $formula_data);
            $this->delete_variables_by_formula($id_formula);
        } else {
            $id_formula = $this->insert_formula($formula_data);
        }

        foreach ($variables as $variable) {
            $variable['id_formula'] = $id_formula;
            $this->insert_variable($variable);
        }

        $this->db->trans_complete();

        if ($this->db->trans_status() === FALSE) {
            return FALSE;
        }

        return $id_formula;
    }

    public function calculate_formula($expression, $input)
    {
        $tokens = $this->tokenize_expression($expression);
        if ($tokens === FALSE) {
            return array('status' => FALSE, 'message' => 'Formula invalid');
        }

        $replaced_tokens = array();
        foreach ($tokens as $token) {
            if ($token['type'] == 'identifier') {
                if (!array_key_exists($token['value'], $input) || $input[$token['value']] === '') {
                    return array('status' => FALSE, 'message' => 'Variable ' . $token['value'] . ' belum diisi');
                }

                if (!is_numeric($input[$token['value']])) {
                    return array('status' => FALSE, 'message' => 'Variable ' . $token['value'] . ' harus numeric');
                }

                $replaced_tokens[] = array('type' => 'number', 'value' => (float) $input[$token['value']]);
            } else {
                $replaced_tokens[] = $token;
            }
        }

        $rpn = $this->to_rpn($replaced_tokens);
        if ($rpn === FALSE) {
            return array('status' => FALSE, 'message' => 'Formula invalid');
        }

        return $this->evaluate_rpn($rpn);
    }

    public function save_result($data)
    {
        return $this->db->insert('tbpo_formula_result', $data);
    }

    public function get_formula_result_by_po_detail($id_po_detail)
    {
        return $this->db->select('r.*, f.kode_formula, f.nama_formula')
            ->from('tbpo_formula_result r')
            ->join('tbpo_formula f', 'f.id_formula = r.id_formula', 'left')
            ->where('r.id_po_detail', $id_po_detail)
            ->order_by('r.id_result', 'DESC')
            ->get()
            ->result();
    }

    private function tokenize_expression($expression)
    {
        if (!preg_match('/^[A-Za-z0-9_\s\+\-\*\/\(\)\.]+$/', $expression)) {
            return FALSE;
        }

        $tokens = array();
        $length = strlen($expression);
        $index = 0;
        $previous = NULL;

        while ($index < $length) {
            $char = $expression[$index];

            if (ctype_space($char)) {
                $index++;
                continue;
            }

            if (preg_match('/[A-Za-z_]/', $char)) {
                $start = $index;
                $index++;
                while ($index < $length && preg_match('/[A-Za-z0-9_]/', $expression[$index])) {
                    $index++;
                }
                $tokens[] = array('type' => 'identifier', 'value' => substr($expression, $start, $index - $start));
                $previous = 'value';
                continue;
            }

            if (ctype_digit($char) || ($char == '.' && $index + 1 < $length && ctype_digit($expression[$index + 1])) ||
                ($char == '-' && ($previous === NULL || $previous == 'operator' || $previous == '(') && $index + 1 < $length && (ctype_digit($expression[$index + 1]) || $expression[$index + 1] == '.'))) {
                $start = $index;
                if ($char == '-') {
                    $index++;
                }
                $dot_count = 0;
                while ($index < $length && (ctype_digit($expression[$index]) || $expression[$index] == '.')) {
                    if ($expression[$index] == '.') {
                        $dot_count++;
                    }
                    if ($dot_count > 1) {
                        return FALSE;
                    }
                    $index++;
                }
                $number = substr($expression, $start, $index - $start);
                if (!is_numeric($number)) {
                    return FALSE;
                }
                $tokens[] = array('type' => 'number', 'value' => (float) $number);
                $previous = 'value';
                continue;
            }

            if (in_array($char, array('+', '-', '*', '/'))) {
                if ($previous === NULL || $previous == 'operator' || $previous == '(') {
                    return FALSE;
                }
                $tokens[] = array('type' => 'operator', 'value' => $char);
                $previous = 'operator';
                $index++;
                continue;
            }

            if ($char == '(' || $char == ')') {
                $tokens[] = array('type' => $char, 'value' => $char);
                $previous = $char == '(' ? '(' : 'value';
                $index++;
                continue;
            }

            return FALSE;
        }

        if (empty($tokens) || $previous == 'operator' || $previous == '(') {
            return FALSE;
        }

        return $tokens;
    }

    private function to_rpn($tokens)
    {
        $output = array();
        $operators = array();
        $precedence = array('+' => 1, '-' => 1, '*' => 2, '/' => 2);

        foreach ($tokens as $token) {
            if ($token['type'] == 'number') {
                $output[] = $token;
            } elseif ($token['type'] == 'operator') {
                while (!empty($operators)) {
                    $top = end($operators);
                    if ($top['type'] != 'operator' || $precedence[$top['value']] < $precedence[$token['value']]) {
                        break;
                    }
                    $output[] = array_pop($operators);
                }
                $operators[] = $token;
            } elseif ($token['type'] == '(') {
                $operators[] = $token;
            } elseif ($token['type'] == ')') {
                $found_open = FALSE;
                while (!empty($operators)) {
                    $top = array_pop($operators);
                    if ($top['type'] == '(') {
                        $found_open = TRUE;
                        break;
                    }
                    $output[] = $top;
                }
                if (!$found_open) {
                    return FALSE;
                }
            }
        }

        while (!empty($operators)) {
            $top = array_pop($operators);
            if ($top['type'] == '(' || $top['type'] == ')') {
                return FALSE;
            }
            $output[] = $top;
        }

        return $output;
    }

    private function evaluate_rpn($tokens)
    {
        $stack = array();

        foreach ($tokens as $token) {
            if ($token['type'] == 'number') {
                $stack[] = $token['value'];
                continue;
            }

            if (count($stack) < 2) {
                return array('status' => FALSE, 'message' => 'Formula invalid');
            }

            $right = array_pop($stack);
            $left = array_pop($stack);

            if ($token['value'] == '+') {
                $stack[] = $left + $right;
            } elseif ($token['value'] == '-') {
                $stack[] = $left - $right;
            } elseif ($token['value'] == '*') {
                $stack[] = $left * $right;
            } elseif ($token['value'] == '/') {
                if ((float) $right == 0.0) {
                    return array('status' => FALSE, 'message' => 'Pembagian dengan nol tidak diperbolehkan');
                }
                $stack[] = $left / $right;
            }
        }

        if (count($stack) != 1 || !is_numeric($stack[0])) {
            return array('status' => FALSE, 'message' => 'Formula invalid');
        }

        return array('status' => TRUE, 'message' => 'Success', 'result' => $stack[0]);
    }
}
