<?php
defined('BASEPATH') or exit('No direct script access allowed');

class M_AdminData extends CI_Model
{
    private $tableLabels = array(
        'po_komersil' => array(
            'label' => 'PO Komersil',
            'table' => 'tbpo_po',
        ),
        'detail_po_komersil' => array(
            'label' => 'Detail PO Komersil',
            'table' => 'tbpo_detail_po',
        ),
        'po_nonkomersil' => array(
            'label' => 'PO Non Komersil',
            'table' => 'tbpo_po_nk',
        ),
        'detail_po_nonkomersil' => array(
            'label' => 'Detail PO Non Komersil',
            'table' => 'tbpo_detail_po_nk',
        ),
        'request_pic' => array(
            'label' => 'Request PIC',
            'table' => 'tbpo_req_nk',
        ),
        'detail_request_pic' => array(
            'label' => 'Detail Request PIC',
            'table' => 'tbpo_detail_req',
        ),
        'transaksi_stock' => array(
            'label' => 'Transaksi Stock NK',
            'table' => 'tbpo_transaksi',
        ),
        'transaksi_stock_tmp' => array(
            'label' => 'Draft Transaksi Stock NK',
            'table' => 'tbpo_transaksi_tmp',
        ),
        'file_pendukung_ponk' => array(
            'label' => 'File Pendukung PONK',
            'table' => 'tbpo_file_nk',
        ),
        'bukti_pembelian_ponk' => array(
            'label' => 'Bukti Pembelian PONK',
            'table' => 'tbpo_file_bukti_beli',
        ),
    );

    public function getTables()
    {
        $tables = array();
        foreach ($this->tableLabels as $key => $config) {
            if ($this->db->table_exists($config['table'])) {
                $primaryKey = $this->getPrimaryKey($config['table']);
                $config['primary_key'] = $primaryKey;
                $config['order_by'] = $primaryKey;
                $tables[$key] = $config;
            }
        }

        foreach ($this->db->list_tables() as $table) {
            if (strpos($table, 'tbpo_') !== 0 || $table === 'tbpo_admin_activity_log') {
                continue;
            }

            $exists = false;
            foreach ($tables as $config) {
                if ($config['table'] === $table) {
                    $exists = true;
                    break;
                }
            }
            if ($exists) {
                continue;
            }

            $key = preg_replace('/[^a-z0-9_]/', '_', strtolower(substr($table, 5)));
            $primaryKey = $this->getPrimaryKey($table);
            $tables[$key] = array(
                'label' => ucwords(str_replace('_', ' ', substr($table, 5))),
                'table' => $table,
                'primary_key' => $primaryKey,
                'order_by' => $primaryKey,
            );
        }

        return $tables;
    }

    public function getTableConfig($key)
    {
        $tables = $this->getTables();
        return isset($tables[$key]) ? $tables[$key] : null;
    }

    private function getPrimaryKey($table)
    {
        $query = $this->db->query('SHOW KEYS FROM ' . $this->db->protect_identifiers($table) . " WHERE Key_name = 'PRIMARY'");
        $row = $query->row();

        return $row ? $row->Column_name : null;
    }

    public function getColumns($table)
    {
        return $this->db->field_data($table);
    }

    public function getRows($config, $limit = 500)
    {
        $this->db->from($config['table']);
        if (!empty($config['order_by'])) {
            $this->db->order_by($config['order_by'], 'DESC');
        }
        if ($limit !== null) {
            $this->db->limit((int) $limit);
        }

        return $this->db->get()->result();
    }

    public function getRowByPk($config, $pkValue)
    {
        $this->db->where($config['primary_key'], $pkValue);
        return $this->db->get($config['table'])->row_array();
    }

    public function updateRow($config, $pkValue, $data)
    {
        $this->db->where($config['primary_key'], $pkValue);
        return $this->db->update($config['table'], $data);
    }

    public function addLog($data)
    {
        return $this->db->insert('tbpo_admin_activity_log', $data);
    }

    public function getLogs($limit = 200)
    {
        $this->db->order_by('id_log', 'DESC');
        $this->db->limit((int) $limit);

        return $this->db->get('tbpo_admin_activity_log')->result();
    }
}
