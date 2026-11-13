<?php
defined('BASEPATH') or exit('No direct script access allowed');
/**
 *
 */
class M_Appsrated extends CI_Model
{
    function __construct()
    {
        parent::__construct();
    }

    public function getAllModule()
    {
        return $this->db->get('tbq_module')->result();
    }
    public function inputnew($data)
    {
        return $this->db->insert('tbq_module', $data);
    }
    function genkdmodnew()
    {
        $cd1 = $this->db->query("SELECT MAX(RIGHT(kd_module,4)) AS kd_max FROM tbq_module WHERE DATE(create_at)=CURDATE()");
        $kd1 = "";
        if ($cd1->num_rows() > 0) {
            foreach ($cd1->result() as $k) {
                $tmp = ((int)$k->kd_max) + 1;
                $kd1 = sprintf("%04s", $tmp);
            }
        } else {
            $kd1 = "0001";
        }
        date_default_timezone_set('Asia/Jakarta');
        $kdnk1 = 'QMODNK' . date('dmy') . $kd1;
        return $kdnk1;
    }
    function generatekdreview()
    {
        $cd1 = $this->db->query("SELECT MAX(RIGHT(kd_reviewq,4)) AS kd_max FROM tbq_review_q WHERE DATE(create_at)=CURDATE()");
        $kd1 = "";
        if ($cd1->num_rows() > 0) {
            foreach ($cd1->result() as $k) {
                $tmp = ((int)$k->kd_max) + 1;
                $kd1 = sprintf("%04s", $tmp);
            }
        } else {
            $kd1 = "0001";
        }
        date_default_timezone_set('Asia/Jakarta');
        $kdnk1 = 'QRVM' . date('dmy') . $kd1;
        return $kdnk1;
    }
    function getquestion($kd)
    {
        return $this->db->query("SELECT 
        a.kd_module AS kode,
        a.kd_reviewq AS kode_r,
        a.question AS qeustion
        FROM tbq_review_q a
        WHERE a.kd_module = '$kd'
        ");
    }
    function getjdl($kd)
    {
        return $this->db->query("SELECT
        a.kd_module AS kode,
        a.nm_module AS nm_module,
        a.type_m AS type
        FROM tbq_module a
        WHERE a.kd_module = '$kd'
        ");
    }
    function getallreviewmd($kd)
    {
        return $this->db->query("SELECT a.*
        FROM tbq_review_q a
        WHERE a.kd_module = '$kd'
        ");
    }
    function inputquestion($data)
    {
        return $this->db->insert('tbq_review_q', $data);
    }
    function inputanswer($data)
    {
        return $this->db->insert_batch('tbq_review_pic', $data);
    }
    function inputsos($data)
    {
        return $this->db->insert('tbpo_sosialisasi', $data);
    }
    function getqnapic($user, $kdm)
    {
        return $this->db->query("SELECT
        a.id_review AS id,
        a.kd_module AS kdm,
        a.isi_review AS isi,
        a.nilai AS nilai
        FROM tbq_review_pic a
        WHERE a.inputer = '$user' AND a.kd_module = '$kdm'
        ");
    }
    function countqna($kd, $user)
    {
        return $this->db->query("SELECT
        x.tot_asw AS tot_aswer,
        COUNT(x.kd_module) AS tot_question
        FROM(
            SELECT
            a.kd_reviewq,
            a.kd_module,
            (SELECT COUNT(b.kd_module) FROM tbq_review_pic b WHERE b.kd_module = a.kd_module AND b.inputer = '$user') AS tot_asw
            FROM tbq_review_q a
        ) AS x
        WHERE x.kd_module = '$kd'
        ");
    }
    function getanswerpic($kd, $usr)
    {
        return $this->db->query("SELECT
        a.question AS question,
        b.isi_review AS isi_review,
        b.nilai AS nilai , 
        b.inputer AS inputer
        FROM tbq_review_q a
        JOIN tbq_review_pic b ON b.kd_reviewq = a.kd_reviewq
        WHERE a.kd_module = '$kd' AND b.inputer = '$usr'
    ");
    }
    function getnilaipic($kd, $usr)
    {
        return $this->db->query("SELECT
        a.question AS question,
        b.isi_review AS isi_review,
        b.nilai AS nilai , 
        b.inputer AS inputer
        FROM tbq_review_q a
        JOIN tbq_review_pic b ON b.kd_reviewq = a.kd_reviewq
        WHERE a.kd_module = '$kd' AND b.inputer = '$usr'
        GROUP BY a.kd_module
    ");
    }
    public function getDataSos($kduser)
    {
        return $this->db->query("SELECT
        a.kd_user AS kduser,
        b.nama_user AS nmuser,
        b.departement AS dep,
        a.create_at AS tgl,
        a.status_nkomersil as sts
        FROM tbpo_sosialisasi a 
        JOIN tbpo_user b ON b.kode_user = a.kd_user
        WHERE a.kd_user = '$kduser'
        ");
    }
}
