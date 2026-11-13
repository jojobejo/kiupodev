<?php
defined('BASEPATH') or exit('No direct script access allowed');
/**
 *
 */
class M_Dashboard extends CI_Model
{
    function __construct()
    {
        parent::__construct();
    }

    function totalAll($kduser)
    {
        return $this->db->query("SELECT COUNT(a.id_po_nk)AS total
        FROM tbpo_po_nk a 
        WHERE a.kd_user = '$kduser'
        ");
    }
    function totalDone($kduser)
    {
        return $this->db->query("SELECT COUNT(a.id_po_nk)as tdone
        FROM tbpo_po_nk a
        WHERE a.status = 'DONE'
        AND a.kd_user = '$kduser'
        ");
    }
    function totalOnProgress($kduser)
    {
        return $this->db->query("SELECT COUNT(a.id_po_nk) as tprogress
        FROM tbpo_po_nk a
        WHERE a.status = 'ON PROGRESS'
        AND a.kd_user = '$kduser'
        ");
    }
    function totalReject($kduser)
    {
        return $this->db->query("SELECT COUNT(a.id_po_nk) as treject
        FROM tbpo_po_nk a
        WHERE a.status = 'REJECT'
        AND a.kd_user = '$kduser'
        ");
    }
    function totalRestock()
    {
        return $this->db->query("SELECT
        COUNT(a.qty_ready) AS cqty
        FROM v_stockbarangnk a
        WHERE a.qty_ready <= 0
        ");
    }
    function getreqmasterbarang()
    {
        return $this->db->query("SELECT
        COUNT(a.id_reqmbarang) AS totreq
        FROM tbpo_req_masterbarang a
        ");
    }
}
