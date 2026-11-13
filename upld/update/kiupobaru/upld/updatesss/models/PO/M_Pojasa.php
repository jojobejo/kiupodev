<?php
defined('BASEPATH') or exit('No direct script access allowed');

class M_Pojasa extends CI_Model
{
    private $requiredTables = array(
        'tbpo_jasa_vendor',
        'tbpo_jasa_request',
        'tbpo_jasa_request_detail',
        'tbpo_jasa_note',
        'tbpo_jasa_progress',
        'tbpo_jasa_file',
        'tbpo_jasa_biaya',
        'tbpo_jasa_bast',
        'tbpo_jasa_payment',
        'tbpo_jasa_evaluation'
    );

    public function __construct()
    {
        parent::__construct();
        $this->load->database();
        $this->db->reconnect();
    }

    public function tables_ready()
    {
        foreach ($this->requiredTables as $table) {
            if (!$this->db->table_exists($table)) {
                return false;
            }
        }

        return true;
    }

    public function missing_tables()
    {
        $missing = array();
        foreach ($this->requiredTables as $table) {
            if (!$this->db->table_exists($table)) {
                $missing[] = $table;
            }
        }

        return $missing;
    }

    public function get_active_vendors()
    {
        $this->db->from('tbpo_jasa_vendor');
        $this->db->where('status_vendor', 'AKTIF');
        $this->db->order_by('nama_vendor', 'ASC');

        return $this->db->get()->result();
    }

    public function get_all_vendors()
    {
        $this->db->from('tbpo_jasa_vendor');
        $this->db->order_by('id_vendor_jasa', 'DESC');

        return $this->db->get()->result();
    }

    public function get_vendor($kd_vendor_jasa)
    {
        $this->db->from('tbpo_jasa_vendor');
        $this->db->where('kd_vendor_jasa', $kd_vendor_jasa);

        return $this->db->get()->row();
    }

    public function get_active_vendor_by_name($nama_vendor)
    {
        $this->db->from('tbpo_jasa_vendor');
        $this->db->where('LOWER(nama_vendor)', strtolower(trim((string) $nama_vendor)));
        $this->db->where('status_vendor', 'AKTIF');

        return $this->db->get()->row();
    }

    public function insert_vendor($data)
    {
        return $this->db->insert('tbpo_jasa_vendor', $data);
    }

    public function update_vendor($kd_vendor_jasa, $data)
    {
        $this->db->where('kd_vendor_jasa', $kd_vendor_jasa);

        return $this->db->update('tbpo_jasa_vendor', $data);
    }

    public function delete_vendor($kd_vendor_jasa)
    {
        if ($this->vendor_has_request($kd_vendor_jasa)) {
            return false;
        }

        $this->db->where('kd_vendor_jasa', $kd_vendor_jasa);

        return $this->db->delete('tbpo_jasa_vendor');
    }

    public function vendor_has_request($kd_vendor_jasa)
    {
        $this->db->from('tbpo_jasa_request');
        $this->db->where('kd_vendor_jasa', $kd_vendor_jasa);

        return $this->db->count_all_results() > 0;
    }

    public function generate_kd_vendor()
    {
        $row = $this->db->query("SELECT MAX(RIGHT(kd_vendor_jasa, 4)) AS kd_max FROM tbpo_jasa_vendor")->row();
        $next = $row && $row->kd_max ? ((int) $row->kd_max) + 1 : 1;

        return 'VJ' . sprintf('%04d', $next);
    }

    public function generate_kd_po_jasa()
    {
        $this->load->model('PO/M_PojasaCore');
        return $this->M_PojasaCore->generate_request_number();
    }

    public function generate_no_spk()
    {
        $this->load->model('PO/M_PojasaCore');
        return $this->M_PojasaCore->generate_spk_number();
    }

    public function insert_request($header, $details, $note, $files = array())
    {
        $this->db->trans_start();
        $this->db->insert('tbpo_jasa_request', $header);
        foreach ($details as $detail) {
            $this->db->insert('tbpo_jasa_request_detail', $detail);
        }
        foreach ($files as $file) {
            $this->db->insert('tbpo_jasa_file', $file);
        }
        $this->db->insert('tbpo_jasa_note', $note);
        $this->db->trans_complete();

        return $this->db->trans_status();
    }

    public function update_request_revision($kd_po_jasa, $header, $details, $note, $files = array())
    {
        $this->db->trans_start();
        $this->db->where('kd_po_jasa', $kd_po_jasa);
        $this->db->update('tbpo_jasa_request', $header);

        $this->db->where('kd_po_jasa', $kd_po_jasa);
        $this->db->delete('tbpo_jasa_request_detail');
        foreach ($details as $detail) {
            $this->db->insert('tbpo_jasa_request_detail', $detail);
        }

        foreach ($files as $file) {
            $this->db->insert('tbpo_jasa_file', $file);
        }

        $this->db->insert('tbpo_jasa_note', $note);
        $this->db->trans_complete();

        return $this->db->trans_status();
    }

    public function replace_request_details($kd_po_jasa, $details, $request_update, $note)
    {
        $this->db->trans_start();
        $this->db->where('kd_po_jasa', $kd_po_jasa);
        $this->db->delete('tbpo_jasa_request_detail');

        foreach ($details as $detail) {
            $this->db->insert('tbpo_jasa_request_detail', $detail);
        }

        $this->db->where('kd_po_jasa', $kd_po_jasa);
        $this->db->update('tbpo_jasa_request', $request_update);
        $this->db->insert('tbpo_jasa_note', $note);
        $this->db->trans_complete();

        return $this->db->trans_status();
    }

    public function get_accessible_requests($lv, $kode_user, $departemen)
    {
        $this->db->select('r.*, v.nama_vendor, v.kategori_jasa, v.nama_pic, v.no_telpon');
        $this->db->from('tbpo_jasa_request r');
        $this->db->join('tbpo_jasa_vendor v', 'v.kd_vendor_jasa = r.kd_vendor_jasa', 'left');
        if ($this->db->table_exists('tbpo_jasa_purchase_submission')) {
            $this->db->select('ps.kd_po_nk, pn.status AS po_pembelian_status');
            $this->db->join('tbpo_jasa_purchase_submission ps', 'ps.kd_po_jasa=r.kd_po_jasa', 'left');
            $this->db->join('tbpo_po_nk pn', 'pn.id_po_nk=ps.id_po_nk', 'left');
        }

        if ((string) $lv === '4') {
            $this->db->where('r.kd_user', $kode_user);
        } elseif ((string) $lv === '5') {
            $this->db->where('r.departemen', $departemen);
        }

        $this->db->order_by('r.id_po_jasa', 'DESC');

        return $this->db->get()->result();
    }

    public function get_request($kd_po_jasa)
    {
        $this->db->select('r.*, v.nama_vendor, v.kategori_jasa, v.nama_pic, v.no_telpon, v.email, v.alamat_vendor, v.npwp');
        $this->db->from('tbpo_jasa_request r');
        $this->db->join('tbpo_jasa_vendor v', 'v.kd_vendor_jasa = r.kd_vendor_jasa', 'left');
        $this->db->where('r.kd_po_jasa', $kd_po_jasa);

        return $this->db->get()->row();
    }

    public function get_request_details($kd_po_jasa)
    {
        $this->db->from('tbpo_jasa_request_detail');
        $this->db->where('kd_po_jasa', $kd_po_jasa);
        $this->db->order_by('id_detail_jasa', 'ASC');

        return $this->db->get()->result();
    }

    public function get_notes($kd_po_jasa)
    {
        $this->db->from('tbpo_jasa_note');
        $this->db->where('kd_po_jasa', $kd_po_jasa);
        $this->db->order_by('id_note_jasa', 'ASC');

        return $this->db->get()->result();
    }

    public function update_status($kd_po_jasa, $data, $note)
    {
        $this->db->trans_start();
        $this->db->where('kd_po_jasa', $kd_po_jasa);
        $this->db->update('tbpo_jasa_request', $data);
        $this->db->insert('tbpo_jasa_note', $note);
        $this->db->trans_complete();

        return $this->db->trans_status();
    }

    public function dashboard_summary($lv, $kode_user, $departemen)
    {
        $requests = $this->get_accessible_requests($lv, $kode_user, $departemen);
        $summary = array(
            'total' => count($requests),
            'on_progress' => 0,
            'acc_kadep' => 0,
            'pengajuan_direktur' => 0,
            'acc_direktur' => 0,
            'spk_terbit' => 0,
            'progress_vendor' => 0,
            'done' => 0,
            'payment_pending' => 0,
            'payment_done' => 0,
            'overdue_active' => 0,
            'avg_vendor_score' => 0
        );

        foreach ($requests as $request) {
            if ($request->status === 'ON PROGRESS') {
                $summary['on_progress']++;
            } elseif ($request->status === 'ACC-KADEP') {
                $summary['acc_kadep']++;
            } elseif ($request->status === 'PENGAJUAN DIREKTUR') {
                $summary['pengajuan_direktur']++;
            } elseif ($request->status === 'ACC DIREKTUR') {
                $summary['acc_direktur']++;
            } elseif ($request->status === 'SPK TERBIT') {
                $summary['spk_terbit']++;
            } elseif ($request->status === 'PROGRESS VENDOR') {
                $summary['progress_vendor']++;
            } elseif ($request->status === 'DONE') {
                $summary['done']++;
            }

            if (in_array($request->status, array('ACC DIREKTUR', 'SPK TERBIT', 'PROGRESS VENDOR'), true) && $request->tgl_target < date('Y-m-d')) {
                $summary['overdue_active']++;
            }
        }

        $summary['payment_pending'] = $this->count_accessible_payments($lv, $kode_user, $departemen, array('BELUM BAYAR', 'PARTIAL'));
        $summary['payment_done'] = $this->count_accessible_payments($lv, $kode_user, $departemen, array('LUNAS'));
        $summary['avg_vendor_score'] = $this->avg_accessible_evaluation($lv, $kode_user, $departemen);

        return $summary;
    }

    private function apply_access_filter($lv, $kode_user, $departemen, $alias)
    {
        if ((string) $lv === '4') {
            $this->db->where($alias . '.kd_user', $kode_user);
        } elseif ((string) $lv === '5') {
            $this->db->where($alias . '.departemen', $departemen);
        }
    }

    public function count_accessible_payments($lv, $kode_user, $departemen, $statuses)
    {
        $this->db->from('tbpo_jasa_payment p');
        $this->db->join('tbpo_jasa_request r', 'r.kd_po_jasa = p.kd_po_jasa');
        $this->db->where_in('p.status_bayar', $statuses);
        $this->apply_access_filter($lv, $kode_user, $departemen, 'r');

        return $this->db->count_all_results();
    }

    public function avg_accessible_evaluation($lv, $kode_user, $departemen)
    {
        $this->db->select('AVG(e.total_score) AS avg_score');
        $this->db->from('tbpo_jasa_evaluation e');
        $this->db->join('tbpo_jasa_request r', 'r.kd_po_jasa = e.kd_po_jasa');
        $this->apply_access_filter($lv, $kode_user, $departemen, 'r');
        $row = $this->db->get()->row();

        return $row && $row->avg_score !== null ? round((float) $row->avg_score, 2) : 0;
    }

    public function insert_progress($data)
    {
        return $this->db->insert('tbpo_jasa_progress', $data);
    }

    public function insert_progress_and_note($kd_po_jasa, $progress, $request_update, $note)
    {
        $this->db->trans_start();
        $this->db->insert('tbpo_jasa_progress', $progress);
        if (!empty($request_update)) {
            $this->db->where('kd_po_jasa', $kd_po_jasa);
            $this->db->update('tbpo_jasa_request', $request_update);
        }
        $this->db->insert('tbpo_jasa_note', $note);
        $this->db->trans_complete();

        return $this->db->trans_status();
    }

    public function get_progress($kd_po_jasa)
    {
        $this->db->from('tbpo_jasa_progress');
        $this->db->where('kd_po_jasa', $kd_po_jasa);
        $this->db->order_by('tgl_progress', 'DESC');
        $this->db->order_by('id_progress_jasa', 'DESC');

        return $this->db->get()->result();
    }

    public function latest_progress($kd_po_jasa)
    {
        $this->db->from('tbpo_jasa_progress');
        $this->db->where('kd_po_jasa', $kd_po_jasa);
        $this->db->order_by('tgl_progress', 'DESC');
        $this->db->order_by('id_progress_jasa', 'DESC');
        $this->db->limit(1);

        return $this->db->get()->row();
    }

    public function insert_file($data)
    {
        return $this->db->insert('tbpo_jasa_file', $data);
    }

    public function insert_file_and_note($file, $note)
    {
        $this->db->trans_start();
        $this->db->insert('tbpo_jasa_file', $file);
        $this->db->insert('tbpo_jasa_note', $note);
        $this->db->trans_complete();

        return $this->db->trans_status();
    }

    public function insert_files_and_note($files, $note)
    {
        $this->db->trans_start();
        foreach ($files as $file) {
            $this->db->insert('tbpo_jasa_file', $file);
        }
        $this->db->insert('tbpo_jasa_note', $note);
        $this->db->trans_complete();

        return $this->db->trans_status();
    }

    public function get_file($id_file_jasa)
    {
        $this->db->from('tbpo_jasa_file');
        $this->db->where('id_file_jasa', $id_file_jasa);

        return $this->db->get()->row();
    }

    public function update_file_and_note($id_file_jasa, $file, $note)
    {
        $this->db->trans_start();
        $this->db->where('id_file_jasa', $id_file_jasa);
        $this->db->update('tbpo_jasa_file', $file);
        $this->db->insert('tbpo_jasa_note', $note);
        $this->db->trans_complete();

        return $this->db->trans_status();
    }

    public function delete_file_and_note($id_file_jasa, $note)
    {
        $this->db->trans_start();
        $this->db->where('id_file_jasa', $id_file_jasa);
        $this->db->delete('tbpo_jasa_file');
        $this->db->insert('tbpo_jasa_note', $note);
        $this->db->trans_complete();

        return $this->db->trans_status();
    }

    public function get_files($kd_po_jasa)
    {
        $this->db->from('tbpo_jasa_file');
        $this->db->where('kd_po_jasa', $kd_po_jasa);
        $this->db->order_by('id_file_jasa', 'DESC');

        return $this->db->get()->result();
    }

    public function insert_biaya($data)
    {
        return $this->db->insert('tbpo_jasa_biaya', $data);
    }

    public function insert_biaya_and_note($biaya, $note)
    {
        $this->db->trans_start();
        $this->db->insert('tbpo_jasa_biaya', $biaya);
        $this->db->insert('tbpo_jasa_note', $note);
        $this->db->trans_complete();

        return $this->db->trans_status();
    }

    public function get_biaya($kd_po_jasa)
    {
        $this->db->from('tbpo_jasa_biaya');
        $this->db->where('kd_po_jasa', $kd_po_jasa);
        $this->db->order_by('id_biaya_jasa', 'DESC');

        return $this->db->get()->result();
    }

    public function biaya_summary($kd_po_jasa)
    {
        $this->db->select('COALESCE(SUM(nominal_estimasi), 0) AS total_estimasi');
        $this->db->select('COALESCE(SUM(nominal_realisasi), 0) AS total_realisasi');
        $this->db->select('COALESCE(SUM(selisih), 0) AS total_selisih');
        $this->db->from('tbpo_jasa_biaya');
        $this->db->where('kd_po_jasa', $kd_po_jasa);
        $row = $this->db->get()->row();

        return $row ? $row : (object) array(
            'total_estimasi' => 0,
            'total_realisasi' => 0,
            'total_selisih' => 0
        );
    }

    public function insert_bast_and_done($kd_po_jasa, $bast, $note, $update)
    {
        $this->db->trans_start();
        $this->db->insert('tbpo_jasa_bast', $bast);
        $this->db->where('kd_po_jasa', $kd_po_jasa);
        $this->db->update('tbpo_jasa_request', $update);
        $this->db->insert('tbpo_jasa_note', $note);
        $this->db->trans_complete();

        return $this->db->trans_status();
    }

    public function get_bast($kd_po_jasa)
    {
        $this->db->from('tbpo_jasa_bast');
        $this->db->where('kd_po_jasa', $kd_po_jasa);
        $this->db->order_by('id_bast_jasa', 'DESC');
        $this->db->limit(1);

        return $this->db->get()->row();
    }

    public function insert_payment_and_note($payment, $note)
    {
        $this->db->trans_start();
        $this->db->insert('tbpo_jasa_payment', $payment);
        $this->db->insert('tbpo_jasa_note', $note);
        $this->db->trans_complete();

        return $this->db->trans_status();
    }

    public function get_payments($kd_po_jasa)
    {
        $this->db->from('tbpo_jasa_payment');
        $this->db->where('kd_po_jasa', $kd_po_jasa);
        $this->db->order_by('id_payment_jasa', 'DESC');

        return $this->db->get()->result();
    }

    public function payment_summary($kd_po_jasa)
    {
        $this->db->select('COALESCE(SUM(nominal_tagihan), 0) AS total_tagihan');
        $this->db->select('COALESCE(SUM(nominal_bayar), 0) AS total_bayar');
        $this->db->from('tbpo_jasa_payment');
        $this->db->where('kd_po_jasa', $kd_po_jasa);
        $row = $this->db->get()->row();

        $row = $row ? $row : (object) array('total_tagihan' => 0, 'total_bayar' => 0);
        $row->sisa_bayar = (float) $row->total_tagihan - (float) $row->total_bayar;

        return $row;
    }

    public function get_evaluation($kd_po_jasa)
    {
        $this->db->from('tbpo_jasa_evaluation');
        $this->db->where('kd_po_jasa', $kd_po_jasa);
        $this->db->limit(1);

        return $this->db->get()->row();
    }

    public function save_evaluation_and_note($evaluation, $note)
    {
        $this->db->trans_start();
        $existing = $this->get_evaluation($evaluation['kd_po_jasa']);
        if ($existing) {
            $this->db->where('kd_po_jasa', $evaluation['kd_po_jasa']);
            $this->db->update('tbpo_jasa_evaluation', $evaluation);
        } else {
            $this->db->insert('tbpo_jasa_evaluation', $evaluation);
        }
        $this->db->insert('tbpo_jasa_note', $note);
        $this->db->trans_complete();

        return $this->db->trans_status();
    }

    public function report_done_projects($filters)
    {
        $this->db->select('r.*, v.nama_vendor, v.kategori_jasa, b.no_bast, b.tgl_bast, e.total_score, e.kualitas_score, e.ketepatan_waktu_score, e.biaya_score');
        $this->db->select('COALESCE(bs.total_realisasi, 0) AS total_realisasi');
        $this->db->select('COALESCE(bs.total_selisih, 0) AS total_selisih');
        $this->db->from('tbpo_jasa_request r');
        $this->db->join('tbpo_jasa_vendor v', 'v.kd_vendor_jasa = r.kd_vendor_jasa', 'left');
        $this->db->join('tbpo_jasa_bast b', 'b.kd_po_jasa = r.kd_po_jasa', 'left');
        $this->db->join('tbpo_jasa_evaluation e', 'e.kd_po_jasa = r.kd_po_jasa', 'left');
        $this->db->join('(SELECT kd_po_jasa, SUM(nominal_realisasi) AS total_realisasi, SUM(selisih) AS total_selisih FROM tbpo_jasa_biaya GROUP BY kd_po_jasa) bs', 'bs.kd_po_jasa = r.kd_po_jasa', 'left');
        $this->db->where('r.status', 'DONE');
        $this->apply_report_filters($filters);
        $this->db->order_by('b.tgl_bast', 'DESC');
        $this->db->order_by('r.id_po_jasa', 'DESC');

        return $this->db->get()->result();
    }

    public function vendor_performance($filters)
    {
        $this->db->select('v.kd_vendor_jasa, v.nama_vendor, v.kategori_jasa');
        $this->db->select('COUNT(r.id_po_jasa) AS total_project');
        $this->db->select('COALESCE(SUM(r.estimasi_total), 0) AS total_estimasi');
        $this->db->select('COALESCE(SUM(bs.total_realisasi), 0) AS total_realisasi');
        $this->db->select('COALESCE(AVG(e.total_score), 0) AS avg_score');
        $this->db->select('COALESCE(AVG(DATEDIFF(b.tgl_bast, r.tgl_request)), 0) AS avg_durasi_hari');
        $this->db->from('tbpo_jasa_vendor v');
        $this->db->join('tbpo_jasa_request r', "r.kd_vendor_jasa = v.kd_vendor_jasa AND r.status = 'DONE'", 'left');
        $this->db->join('tbpo_jasa_bast b', 'b.kd_po_jasa = r.kd_po_jasa', 'left');
        $this->db->join('tbpo_jasa_evaluation e', 'e.kd_po_jasa = r.kd_po_jasa', 'left');
        $this->db->join('(SELECT kd_po_jasa, SUM(nominal_realisasi) AS total_realisasi FROM tbpo_jasa_biaya GROUP BY kd_po_jasa) bs', 'bs.kd_po_jasa = r.kd_po_jasa', 'left');
        $this->apply_report_filters($filters);
        $this->db->group_by('v.kd_vendor_jasa, v.nama_vendor, v.kategori_jasa');
        $this->db->order_by('avg_score', 'DESC');
        $this->db->order_by('total_project', 'DESC');

        return $this->db->get()->result();
    }

    private function apply_report_filters($filters)
    {
        if (isset($filters['lv'], $filters['kode_user'], $filters['access_departemen'])) {
            $this->apply_access_filter($filters['lv'], $filters['kode_user'], $filters['access_departemen'], 'r');
        }

        if (!empty($filters['kd_vendor_jasa'])) {
            $this->db->where('v.kd_vendor_jasa', $filters['kd_vendor_jasa']);
        }

        if (!empty($filters['departemen'])) {
            $this->db->where('r.departemen', $filters['departemen']);
        }

        if (!empty($filters['tgl_start'])) {
            $this->db->where('r.tgl_request >=', $filters['tgl_start']);
        }

        if (!empty($filters['tgl_end'])) {
            $this->db->where('r.tgl_request <=', $filters['tgl_end']);
        }
    }

    public function report_summary($filters)
    {
        $projects = $this->report_done_projects($filters);
        $summary = array(
            'total_project_done' => count($projects),
            'total_estimasi' => 0,
            'total_realisasi' => 0,
            'total_selisih' => 0,
            'avg_score' => 0
        );
        $scoreCount = 0;

        foreach ($projects as $project) {
            $summary['total_estimasi'] += (float) $project->estimasi_total;
            $summary['total_realisasi'] += (float) $project->total_realisasi;
            $summary['total_selisih'] += (float) $project->total_selisih;
            if ($project->total_score !== null) {
                $summary['avg_score'] += (float) $project->total_score;
                $scoreCount++;
            }
        }

        if ($scoreCount > 0) {
            $summary['avg_score'] = round($summary['avg_score'] / $scoreCount, 2);
        }

        return $summary;
    }
}
