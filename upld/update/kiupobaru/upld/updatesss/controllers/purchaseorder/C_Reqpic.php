<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 *
 */
class C_Reqpic extends CI_Controller

{
    function __construct()
    {
        parent::__construct();
        $this->load->model('PO/M_Reqpic');
        $this->load->model('PO/M_Purchase');
        $this->load->model('PO/M_Postatus');
        $this->load->model('stock/M_StockLifo');
        $this->load->library('form_validation');
    }

    public function index()
    {
        $kduser = $this->session->userdata('kode');

        $data['title']      = 'PO Request By PIC ';
        $data['tmpreq']     = $this->M_Reqpic->getalltmpreq($kduser)->result();
        $data['getallreq']  = $this->M_Reqpic->getallreq($kduser)->result();
        $data['countreq']   = $this->M_Reqpic->countRequser('1', $kduser);
        $data['generatekd'] = (strtoupper($kduser) == 'KARYAWAN4' ? 'GA' : '') . $this->M_Reqpic->kdnonkomersial();
        $data['jumlahbr']   = $this->M_Reqpic->countjmltmpbr($kduser);
        $data['getlistpic'] = $this->M_Reqpic->getlistpic()->result();

        $this->load->view('partial/header', $data);
        $this->load->view('partial/sidebar');
        $this->load->view('content/po/Reqpic/body', $data);
        $this->load->view('partial/footer');
        $this->load->view('content/po/Reqpic/datatablesreq');
    }
    public function historireqpic()
    {
        $kduser = $this->session->userdata('kode');

        $data['title']      = 'PO Request By PIC ';
        $data['getallreq']  = $this->M_Reqpic->getallreqdone($kduser)->result();

        $this->load->view('partial/header', $data);
        $this->load->view('partial/sidebar');
        $this->load->view('content/po/Reqpic/historipodonepic', $data);
        $this->load->view('partial/footer');
        $this->load->view('content/po/Reqpic/datatablesreq');
    }

    public function requestpendings()
    {
        $kduser = $this->session->userdata('kode');

        $data['title']      = 'PO Request By PIC ';
        $data['getallreq']  = $this->M_Reqpic->getallreqpending($kduser)->result();

        $this->load->view('partial/header', $data);
        $this->load->view('partial/sidebar');
        $this->load->view('content/po/Reqpic/requestpending', $data);
        $this->load->view('partial/footer');
        $this->load->view('content/po/Reqpic/datatablesreq');
    }

    public function requestpending($kdpo)
    {
        $kodeuser   = $this->session->userdata('kode');
        $nmuser     = $this->session->userdata('nama_user');

        $data = array(
            'status'    => 'PENDING'
        );

        $noterevisi = array(
            'kd_po'         => $kdpo,
            'isi_note'      => 'PO PENDING',
            'kd_user'       => $kodeuser,
            'nama_user'     => $nmuser,
            'note_for'      => '2',
            'update_status' => '2',
        );
        $this->M_Purchase->addNote($noterevisi);
        $this->M_Reqpic->updated_req_po_nk($kdpo, $data);
        redirect('reqpic');
    }

    public function index_brsedia()
    {
        $kduser = $this->session->userdata('kode');

        $data['title']      = 'PO Request By PIC ';
        $data['tmpreq']     = $this->M_Reqpic->getalltmpreq($kduser)->result();
        $data['getallreq']  = $this->M_Reqpic->getallreq($kduser)->result();
        $data['countreq']   = $this->M_Reqpic->countRequser('1', $kduser);
        $data['generatekd'] = $this->M_Purchase->kdnonkomersial();
        $data['jumlahbr']   = $this->M_Reqpic->countjmltmpbr($kduser);
        $data['getlistpic'] = $this->M_Reqpic->getlistready()->result();

        $this->load->view('partial/header', $data);
        $this->load->view('partial/sidebar');
        $this->load->view('content/po/Reqpic/view_req/vreq2', $data);
        $this->load->view('partial/footer');
        $this->load->view('content/po/Reqpic/datatablesreq');
    }

    public function index_done()
    {
        $kduser = $this->session->userdata('kode');

        $data['title']      = 'PO Request By PIC ';
        $data['tmpreq']     = $this->M_Reqpic->getalltmpreq($kduser)->result();
        $data['getallreq']  = $this->M_Reqpic->getallreq($kduser)->result();
        $data['countreq']   = $this->M_Reqpic->countRequser('1', $kduser);
        $data['generatekd'] = $this->M_Purchase->kdnonkomersial();
        $data['jumlahbr']   = $this->M_Reqpic->countjmltmpbr($kduser);
        $data['getlistpic'] = $this->M_Reqpic->getlistdone()->result();

        $this->load->view('partial/header', $data);
        $this->load->view('partial/sidebar');
        $this->load->view('content/po/Reqpic/view_req/vreq3', $data);
        $this->load->view('partial/footer');
        $this->load->view('content/po/Reqpic/datatablesreq');
    }

    /** Daftar pengajuan pengambilan yang harus dikonfirmasi oleh Purchasing. */
    public function index_pickup()
    {
        if ((string) $this->session->userdata('lv') !== '2' && !is_super_admin()) {
            show_404();
            return;
        }

        $data['title'] = 'Konfirmasi Penyerahan Barang';
        $data['getlistpic'] = $this->M_Reqpic->getlistpickup()->result();
        $this->load->view('partial/header', $data);
        $this->load->view('partial/sidebar');
        $this->load->view('content/po/Reqpic/pickup', $data);
        $this->load->view('partial/footer');
        $this->load->view('content/po/Reqpic/datatablesreq');
    }

    /** Daftar permohonan pengambilan barang dari PIC untuk KADEP terkait. */
    public function index_pickup_approval_kadep()
    {
        if ((string) $this->session->userdata('lv') !== '5' && !is_super_admin()) {
            show_404();
            return;
        }

        $data['title'] = 'Approval Pengambilan Barang';
        $data['requests'] = $this->M_Reqpic->get_pickup_approval_waiting_kadep(
            $this->session->userdata('departemen'),
            is_super_admin()
        )->result();
        $this->load->view('partial/header', $data);
        $this->load->view('partial/sidebar');
        $this->load->view('content/po/Reqpic/pickup_approval_kadep', $data);
        $this->load->view('partial/footer');
        $this->load->view('content/po/Reqpic/datatablesreq');
    }

    /** Histori seluruh pengajuan PIC dan PO untuk departemen KADEP. */
    public function history_pickup_pic()
    {
        if ((string) $this->session->userdata('lv') !== '5' && !is_super_admin()) {
            show_404();
            return;
        }

        $data['title'] = 'Histori Pengambilan PIC';
        $data['requests'] = $this->M_Reqpic->get_pickup_pic_history(
            $this->session->userdata('departemen'),
            is_super_admin()
        )->result();

        $this->load->view('partial/header', $data);
        $this->load->view('partial/sidebar');
        $this->load->view('content/po/Reqpic/history_pickup_pic', $data);
        $this->load->view('partial/footer');
        $this->load->view('content/po/Reqpic/datatablesreq');
    }

    public function index_accreq()
    {
        $kduser = $this->session->userdata('kode');

        $data['title']      = 'PO Request By PIC ';
        $data['tmpreq']     = $this->M_Reqpic->getalltmpreq($kduser)->result();
        $data['getallreq']  = $this->M_Reqpic->getallreq($kduser)->result();
        $data['countreq']   = $this->M_Reqpic->countRequser('1', $kduser);
        $data['generatekd'] = $this->M_Purchase->kdnonkomersial();
        $data['jumlahbr']   = $this->M_Reqpic->countjmltmpbr($kduser);

        $this->load->view('partial/header', $data);
        $this->load->view('partial/sidebar');
        $this->load->view('content/po/Reqpic/view_req/vreq1', $data);
        $this->load->view('partial/footer');
    }

    /** Data daftar request Purchasing; dipanggil DataTables tanpa reload halaman. */
    public function ajax_request_list()
    {
        if ((string) $this->session->userdata('lv') !== '2' && !is_super_admin()) {
            $this->output->set_status_header(403)->set_content_type('application/json')
                ->set_output(json_encode(array('data' => array())));
            return;
        }

        $scope = $this->input->get('scope', true) === 'all' ? 'all' : 'request_acc';
        $requests = $this->M_Reqpic->get_request_list($scope)->result();
        $rows = array();
        foreach ($requests as $request) {
            $status = trim((string) $request->status);
            $statusPo = trim((string) $request->status_po);
            $rows[] = array(
                htmlspecialchars($request->nm_user, ENT_QUOTES, 'UTF-8'),
                htmlspecialchars($request->departemen, ENT_QUOTES, 'UTF-8'),
                htmlspecialchars(function_exists('format_tgl_lahir') ? format_tgl_lahir($request->tgl_transaksi) : $request->tgl_transaksi, ENT_QUOTES, 'UTF-8'),
                htmlspecialchars(function_exists('format_jam_24') ? format_jam_24($request->create_at) : '-', ENT_QUOTES, 'UTF-8'),
                htmlspecialchars($request->tj_pembelian, ENT_QUOTES, 'UTF-8'),
                $this->request_status_badge($status),
                $this->purchase_status_badge($statusPo),
                '<a class="btn btn-block btn-primary btn-sm" href="' . base_url('reqpic/detreqbarangpic/' . rawurlencode($request->kd_po_nk)) . '" title="Detail request"><i class="fas fa-eye"></i></a>',
            );
        }
        $this->output->set_content_type('application/json')->set_output(json_encode(array('data' => $rows)));
    }

    private function request_status_badge($status)
    {
        if ($status === 'ON PROGRESS - BELUM ACC') {
            return '<span class="btn btn-block btn-warning btn-sm m-1"><b>ON PROGRESS - BELUM ACC</b></span>';
        }
        if ($status === 'ON PROGRESS') {
            return '<span class="btn btn-block btn-primary btn-sm m-1"><b>ON PROGRESS - TELAH ACC KADEP</b></span>';
        }
        if ($status === 'ON PROGRESS - ACC KADEP') {
            return '<span class="btn btn-block btn-primary btn-sm m-1"><b>ON PROGRESS - ACC KADEP</b></span>';
        }
        $class = in_array($status, array('REQUEST ACC', 'MENUNGGU ACC KADEP'), true) ? 'btn-warning' : 'btn-secondary';
        return '<span class="btn btn-block ' . $class . ' btn-sm m-1"><b>' . htmlspecialchars($status, ENT_QUOTES, 'UTF-8') . '</b></span>';
    }

    /** Server-side gate; tombol yang disembunyikan tidak cukup melindungi URL POST. */
    private function require_purchasing_request_access($kdpo)
    {
        if ((string) $this->session->userdata('lv') !== '2' && !is_super_admin()) {
            show_404();
            return false;
        }

        $request = $this->M_Reqpic->getrequestrow($kdpo);
        if (!$this->M_Reqpic->can_purchasing_process($request)) {
            $this->session->set_flashdata('error', 'Request masih menunggu ACC KADEP. Purchasing dapat memprosesnya setelah 5 menit sejak request diajukan atau setelah KADEP menyetujuinya.');
            redirect('reqpic/detreqbarangpic/' . rawurlencode($kdpo));
            return false;
        }

        // Bila pintu Purchasing terbuka karena timeout (bukan keputusan KADEP),
        // simpan jejaknya agar KADEP masih dapat melihatnya pada tab histori.
        if ($request && trim((string) $request->status) === 'ON PROGRESS - BELUM ACC'
            && empty($request->purchasing_opened_at)) {
            $openedAt = date('Y-m-d H:i:s');
            $timeoutUpdate = array('purchasing_opened_at' => $openedAt);
            if ($this->db->field_exists('purchasing_timeout_opened_at', 'tbpo_req_nk')) {
                $timeoutUpdate['purchasing_timeout_opened_at'] = $openedAt;
            }
            $this->M_Reqpic->updatereqnk($kdpo, $timeoutUpdate);
            $this->M_Purchase->addNote(array(
                'kd_po' => $kdpo,
                'isi_note' => 'PURCHASING MEMPROSES REQUEST SETELAH BATAS WAKTU APPROVAL KADEP 5 MENIT',
                'kd_user' => $this->session->userdata('kode'),
                'nama_user' => $this->session->userdata('nama_user'),
                'note_for' => '2',
                'update_status' => '2',
            ));
        }
        return true;
    }

    private function require_purchasing_detail_access($detailId)
    {
        $request = $this->M_Reqpic->get_request_by_detail_id($detailId);
        if (!$request) {
            show_error('Detail request tidak ditemukan.', 404);
            return false;
        }
        return $this->require_purchasing_request_access($request->kd_po_nk);
    }

    private function purchase_status_badge($status)
    {
        $classes = array(
            'ON PROGRESS - KADEP' => 'btn-warning', 'ACC-KADEP' => 'btn-primary', 'SEDANG DIAJUKAN' => 'btn-warning',
            'ACC DIREKTUR' => 'btn-primary', 'PROSES PEMBELIAN' => 'btn-primary', 'DONE' => 'btn-success', 'REJECT' => 'btn-danger',
        );
        if ($status === '0' || $status === '') {
            return '<span class="btn btn-block btn-secondary btn-sm"><b>BARANG READY</b></span>';
        }
        return '<span class="btn btn-block ' . (isset($classes[$status]) ? $classes[$status] : 'btn-secondary') . ' btn-sm"><b>'
            . htmlspecialchars($status, ENT_QUOTES, 'UTF-8') . '</b></span>';
    }

    public function index_acckadep()
    {
        if ((string) $this->session->userdata('lv') !== '5' && !is_super_admin()) {
            show_404();
            return;
        }

        // Inbox utama KADEP: seluruh pengajuan pembelian dari PIC harus
        // diputuskan KADEP sebelum dapat diproses Purchasing.
        $data['title'] = 'Approval Request PIC';
        $data['requests'] = $this->M_Reqpic->getlistpicreqkadep(
            $this->session->userdata('departemen'),
            $this->session->userdata('kode')
        )->result();
        $data['history'] = $this->M_Reqpic->getlistpicreqkadep_history(
            $this->session->userdata('departemen'),
            is_super_admin()
        )->result();
        $this->load->view('partial/header', $data);
        $this->load->view('partial/sidebar');
        $this->load->view('content/po/Reqpic/kadep_request_approval', $data);
        $this->load->view('partial/footer');
        $this->load->view('content/po/Reqpic/datatablesreq');
    }

    public function list_barang_ready()
    {
        $data['title']      = 'List Barang PO';
        $data['lstock']     = $this->M_Reqpic->getlistnkreq()->result();
        $data['satuan']     = $this->M_Reqpic->getsatuan()->result();

        $this->load->view('partial/header', $data);
        $this->load->view('partial/sidebar');
        $this->load->view('content/po/Reqpic/listbarang', $data);
        $this->load->view('partial/footer');
        $this->load->view('content/po/datatables');
    }

    public function addrequestmasterbarangready()
    {
        $inputby    = $this->session->userdata('kode');
        $nmbarang   = $this->input->post('nmbarang');
        $descnk     = $this->input->post('descisi');
        $satuan     = $this->input->post('stuanbr');

        $tmpreqbarang = array(
            'nama_barang'   => $nmbarang,
            'deskripsi'     => $descnk,
            'satuan'        => $satuan,
            'req_by'        => $inputby
        );

        $this->M_Reqpic->insertTmpmbarang($tmpreqbarang);

        redirect('listbarangready');
    }
    public function addtmpreqbarang()
    {
        $kduser     = $this->session->userdata('kode');
        $nmbarang   = $this->input->post('nm_barang');
        $desc       = $this->input->post('descnk_isi');
        $ket        = $this->input->post('ket_isi');
        $qty        = $this->input->post('qty_isi');
        $kdbarang   = $this->input->post('kdbys');
        $kdsys      = $this->input->post('kdbr');
        $katbarang  = $this->input->post('katbr');
        $idsat      = $this->input->post('idsat');

        $inputtmp = array(
            'jnis_po'       => '1',
            'nama_barang'   => $nmbarang,
            'deskripsi'     => $desc,
            'keterangan'    => $ket,
            'qty'           => $qty,
            'satuan'        => $idsat,
            'hrg_satuan'    => '0',
            'total_harga'   => '0',
            'kd_bsys'       => $kdsys,
            'kd_barang'     => $kdbarang,
            'kat_barang'    => $katbarang,
            'kd_user'       => $kduser
        );
        $this->M_Reqpic->inputreqbr($inputtmp);
        redirect('listbarangready');
    }

    public function addtmpreqbarang_seed()
    {
        $kduser     = $this->session->userdata('kode');
        $nmbarang   = $this->input->post('nm_barang');
        $desc       = $this->input->post('descnk_isi');
        $ket        = $this->input->post('ket_isi');
        $qty        = $this->input->post('qty_isi');
        $kdbarang   = $this->input->post('kdbys');
        $kdsys      = $this->input->post('kdbr');
        $katbarang  = $this->input->post('katbr');
        $idsat      = $this->input->post('idsat');

        $inputtmp = array(
            'jnis_po'       => '1',
            'nama_barang'   => $nmbarang,
            'deskripsi'     => $desc,
            'keterangan'    => $ket,
            'qty'           => $qty,
            'satuan'        => $idsat,
            'hrg_satuan'    => '0',
            'total_harga'   => '0',
            'kd_bsys'       => $kdsys,
            'kd_barang'     => $kdbarang,
            'kat_barang'    => $katbarang,
            'kd_user'       => $kduser
        );
        $this->M_Reqpic->inputreqbr($inputtmp);
        redirect('list_barang_ready_seed');
    }

    public function addtmpreqbarang_cp()
    {
        $kduser     = $this->session->userdata('kode');
        $nmbarang   = $this->input->post('nm_barang');
        $desc       = $this->input->post('descnk_isi');
        $ket        = $this->input->post('ket_isi');
        $qty        = $this->input->post('qty_isi');
        $kdbarang   = $this->input->post('kdbys');
        $kdsys      = $this->input->post('kdbr');
        $katbarang  = $this->input->post('katbr');
        $idsat      = $this->input->post('idsat');

        $inputtmp = array(
            'jnis_po'       => '1',
            'nama_barang'   => $nmbarang,
            'deskripsi'     => $desc,
            'keterangan'    => $ket,
            'qty'           => $qty,
            'satuan'        => $idsat,
            'hrg_satuan'    => '0',
            'total_harga'   => '0',
            'kd_bsys'       => $kdsys,
            'kd_barang'     => $kdbarang,
            'kat_barang'    => $katbarang,
            'kd_user'       => $kduser
        );
        $this->M_Reqpic->inputreqbr($inputtmp);
        redirect('list_barang_ready_cp');
    }

    public function editedreqpic()
    {
        $akses  = $this->session->userdata('lv');

        $idnk   = $this->input->post('id_isi');
        $ket    = $this->input->post('ket_isi');
        $qty    = $this->input->post('qty_isi');

        $tmpedited = array(
            'keterangan'    => $ket,
            'qty'           => $qty
        );

        $this->M_Reqpic->editedreqpic($idnk, $tmpedited);
        if ($akses == '2') {
            redirect('reqpic/admrestock');
        } else {
            redirect('reqpic');
        }
    }
    public function deletedtmpnkreq()
    {
        $akses  = $this->session->userdata('lv');
        $idnk   = $this->input->post('id_isi');
        $this->M_Reqpic->deletedtmpnkreq($idnk);

        if ($akses == 2) {
            redirect('reqpic/admrestock');
        } else {
            redirect('reqpic');
        }
    }

    public function list_barang_ready_seed()
    {
        $data['title']      = 'List Barang PO';
        $data['lstock']     = $this->M_Reqpic->getlistnkreq()->result();
        $data['satuan']     = $this->M_Reqpic->getsatuan()->result();

        $this->load->view('partial/header', $data);
        $this->load->view('partial/sidebar');
        $this->load->view('content/po/Reqpic/listbarang_seed', $data);
        $this->load->view('partial/footer');
        $this->load->view('content/po/datatables');
    }

    public function add_promosi_seed($kduser)
    {
        date_default_timezone_set("Asia/Jakarta");
        $nmuser = $this->session->userdata('nama_user');
        $dep    = $this->session->userdata('departemen');
        $kdus   = $this->session->userdata('kode');
        $kdponk = $this->input->post('kdponk');
        $totbr  = $this->input->post('totbr');
        $tjuan  = $this->input->post('intj');

        $now    = date('Y-m-d');

        $tmp    = $this->M_Reqpic->get_tmp_non_komersil($kduser);

        $inpdataponk = array(
            'jns_po'        => '2',
            'kd_po_nk'      => $kdponk,
            'kd_user'       => $kduser,
            'nm_user'       => $nmuser,
            'tgl_transaksi' => $now,
            'jml_item'      => $totbr,
            'status'        => 'ON PROGRESS',
            'departemen'    => 'PROMOSI SEED',
            'tj_pembelian'  => $tjuan
        );
        $this->M_Reqpic->inputreq($inpdataponk);

        $generatekd = array(
            'kd_barang' => $kdponk
        );
        $this->M_Reqpic->generatekdponk($generatekd);

        $inputnt    = array(
            'kd_po'         => $kdponk,
            'isi_note'      => $tjuan,
            'kd_user'       => $kdus,
            'nama_user'     => $nmuser,
            'note_for'      => '2',
            'update_status' => '2',

        );
        $this->M_Purchase->addNote($inputnt);

        if ($tmp) {
            foreach ($tmp as $t) {
                $listdetreq = array(
                    'kd_po_nk'          => $kdponk,
                    'kd_user'           => $kduser,
                    'tgl_transaksi'     => $now,
                    'kd_bsys'           => $t->kd_bsys,
                    'kd_barang'         => $t->kd_barang,
                    'nama_barang'       => $t->nama_barang,
                    'deskripsi'         => $t->deskripsi,
                    'keterangan'        => $t->keterangan,
                    'qty'               => $t->qty,
                    'satuan'            => $t->satuan,
                    'kat_barang'        => $t->kat_barang,
                    'status'            => '0'
                );
                $this->M_Reqpic->input_detail_po_nk($listdetreq);
            }
        }
        $this->M_Reqpic->hapus_tmp_nk($kduser);
        redirect('promosi_seed');
    }

    public function addnewreq($kduser)
    {
        date_default_timezone_set("Asia/Jakarta");
        $session_kduser = $this->session->userdata('kode');

        // Never use a user code from the URL to read or delete another PIC's draft.
        if (empty($session_kduser) || $kduser !== $session_kduser) {
            show_error('Pengajuan hanya dapat diproses dari draft milik PIC yang sedang login.', 403, 'Akses ditolak');
            return;
        }

        $kduser = $session_kduser;
        $nmuser = $this->session->userdata('nama_user');
        $dep    = $this->session->userdata('departemen');
        $kdus   = $this->session->userdata('kode');
        $tjuan  = $this->input->post('intj');

        $now    = date('Y-m-d');

        $tmp = array();
        $kdponk = '';
        $success = false;
        $message = 'Request gagal disimpan';
        $db_debug = $this->db->db_debug;
        $this->db->db_debug = false;

        for ($i = 0; $i < 5; $i++) {
            $kdponk = $this->generate_kdponk_req($kdus, $i);

            if ($this->M_Reqpic->is_kdponk_exists($kdponk)) {
                continue;
            }

            $this->db->trans_begin();

            // Lock the current PIC's rows before copying them. A second submit
            // must wait, then sees an empty draft instead of merging its data.
            $tmp = $this->M_Reqpic->get_tmp_non_komersil_for_update($kduser);
            if (empty($tmp)) {
                $this->db->trans_rollback();
                $message = 'Draft request sudah kosong atau sedang diproses.';
                break;
            }

            // Semua request PIC terlebih dahulu masuk ke inbox KADEP.
            $statusAwal = 'ON PROGRESS - BELUM ACC';

            $inpdataponk = array(
                'jns_po'        => '2',
                'kd_po_nk'      => $kdponk,
                'kd_user'       => $kduser,
                'nm_user'       => $nmuser,
                'tgl_transaksi' => $now,
                // Count on the server so a submitted form cannot change it.
                'jml_item'      => count($tmp),
                'status'        => $statusAwal,
                'kadep_submitted_at' => date('Y-m-d H:i:s'),
                'departemen'    => $dep,
                'tj_pembelian'  => $tjuan
            );
            $this->M_Reqpic->inputreq($inpdataponk);

            $insert_error = $this->db->error();
            if (!empty($insert_error['code'])) {
                $this->db->trans_rollback();

                if ($insert_error['code'] == 1062) {
                    continue;
                }

                $message = $insert_error['message'];
                break;
            }

            $generatekd = array(
                'kd_barang' => $kdponk
            );
            $this->M_Reqpic->generatekdponk($generatekd);

            $inputnt    = array(
                'kd_po'         => $kdponk,
                'isi_note'      => 'REQUEST BARU - MENUNGGU ACC KADEP',
                'kd_user'       => $kdus,
                'nama_user'     => $nmuser,
                'note_for'      => '2',
                'update_status' => '2',

            );
            $this->M_Purchase->addNote($inputnt);

            // Dokumen pendukung bersifat opsional. Jika ada file yang dipilih,
            // tetap simpan dengan alur dan folder dokumen PIC yang sama.
            $documentUpload = $this->has_supporting_upload()
                ? $this->store_supporting_documents($dep)
                : array('success' => true, 'message' => '', 'documents' => array(), 'paths' => array());
            if (!$documentUpload['success']) {
                $this->db->trans_rollback();
                $message = $documentUpload['message'];
                break;
            }
            foreach ($documentUpload['documents'] as $document) {
                $document['kd_po_nk'] = $kdponk;
                $document['departemen'] = $dep;
                $document['uploaded_by'] = $kduser;
                $this->M_Reqpic->add_supporting_document($document);
            }

            if ($tmp) {
                foreach ($tmp as $t) {
                    $listdetreq = array(
                        'kd_po_nk'          => $kdponk,
                        'kd_user'           => $kduser,
                        'tgl_transaksi'     => $now,
                        'kd_bsys'           => $t->kd_bsys,
                        'kd_barang'         => $t->kd_barang,
                        'nama_barang'       => $t->nama_barang,
                        'deskripsi'         => $t->deskripsi,
                        'keterangan'        => $t->keterangan,
                        'qty'               => $t->qty,
                        'satuan'            => $t->satuan,
                        'kat_barang'        => $t->kat_barang,
                        'status'            => '0'
                    );
                    $this->M_Reqpic->input_detail_po_nk($listdetreq);
                }
            }
            $tmp_ids = array();
            foreach ($tmp as $draft) {
                $tmp_ids[] = $draft->id_tmp_nk;
            }
            $this->M_Reqpic->hapus_tmp_nk_by_ids($kduser, $tmp_ids);

            if ($this->db->trans_status() === FALSE) {
                $this->db->trans_rollback();
                $this->remove_supporting_files($documentUpload['paths']);
                continue;
            }

            $this->db->trans_commit();
            $success = true;
            $message = 'Request berhasil disimpan';
            break;
        }

        $this->db->db_debug = $db_debug;

        if ($this->input->is_ajax_request()) {
            $this->output
                ->set_content_type('application/json')
                ->set_output(json_encode(array(
                    'status'          => $success,
                    'message'         => $message,
                    'kdponk'          => $kdponk,
                    'tgl_transaksi'   => function_exists('format_tgl_lahir') ? format_tgl_lahir($now) : $now,
                    'jml_item'        => $success ? count($tmp) : 0,
                    'tj_pembelian'    => $tjuan,
                    'detail_url'      => base_url('reqpic/detreqbarangpic/' . $kdponk),
                    'pending_url'     => base_url('reqpic/requestpending/' . $kdponk)
                )));
            return;
        }

        if (!$success) {
            $this->session->set_flashdata('error', $message);
        }

        redirect('reqpic');
    }

    /** Menyimpan file pada assets/request-pendukung/{departemen}/{YYYY-MM-DD}/. */
    private function store_supporting_documents($department)
    {
        $files = isset($_FILES['dokumen_pendukung']) ? $_FILES['dokumen_pendukung'] : array();
        $names = isset($files['name']) && is_array($files['name']) ? $files['name'] : array();
        $departmentName = preg_replace('/[^A-Z0-9_-]+/', '_', strtoupper((string) $department));
        $departmentName = trim($departmentName, '_') ?: 'UMUM';
        $relativeDirectory = 'assets/request-pendukung/' . $departmentName . '/' . date('Y-m-d') . '/';
        $absoluteDirectory = FCPATH . $relativeDirectory;

        if (!is_dir($absoluteDirectory) && !@mkdir($absoluteDirectory, 0775, true) && !is_dir($absoluteDirectory)) {
            return array('success' => false, 'message' => 'Folder dokumen pendukung tidak dapat dibuat.', 'documents' => array(), 'paths' => array());
        }

        $config = array(
            'upload_path' => $absoluteDirectory,
            'allowed_types' => 'jpg|jpeg|png|pdf|txt|xls|xlsx',
            'max_size' => 10240,
            'overwrite' => false,
            'encrypt_name' => true,
            'detect_mime' => true,
        );
        $this->load->library('upload', $config);
        $documents = array();
        $paths = array();

        foreach ($names as $index => $name) {
            if (empty($name)) {
                continue;
            }
            $_FILES['reqpic_supporting_document'] = array(
                'name' => $name,
                'type' => $files['type'][$index],
                'tmp_name' => $files['tmp_name'][$index],
                'error' => $files['error'][$index],
                'size' => $files['size'][$index],
            );
            $this->upload->initialize($config);
            if (!$this->upload->do_upload('reqpic_supporting_document')) {
                $this->remove_supporting_files($paths);
                unset($_FILES['reqpic_supporting_document']);
                return array('success' => false, 'message' => $name . ': ' . strip_tags($this->upload->display_errors('', '')), 'documents' => array(), 'paths' => array());
            }

            $uploaded = $this->upload->data();
            $absolutePath = $absoluteDirectory . $uploaded['file_name'];
            $relativePath = $relativeDirectory . $uploaded['file_name'];
            $mimeType = function_exists('finfo_open') ? (new finfo(FILEINFO_MIME_TYPE))->file($absolutePath) : $uploaded['file_type'];
            $paths[] = $relativePath;
            $documents[] = array(
                'file_path' => $relativePath,
                'file_original' => substr(basename($uploaded['client_name']), 0, 180),
                'mime_type' => $mimeType,
                'file_size_bytes' => (int) filesize($absolutePath),
                'sha256' => hash_file('sha256', $absolutePath),
            );
        }
        unset($_FILES['reqpic_supporting_document']);

        if (empty($documents)) {
            return array('success' => false, 'message' => 'Dokumen pendukung wajib diunggah.', 'documents' => array(), 'paths' => array());
        }
        return array('success' => true, 'message' => '', 'documents' => $documents, 'paths' => $paths);
    }

    private function has_supporting_upload()
    {
        return isset($_FILES['dokumen_pendukung']['name'])
            && is_array($_FILES['dokumen_pendukung']['name'])
            && (bool) array_filter($_FILES['dokumen_pendukung']['name']);
    }

    private function remove_supporting_files(array $paths)
    {
        $base = realpath(FCPATH . 'assets/request-pendukung');
        foreach ($paths as $relativePath) {
            $path = realpath(FCPATH . ltrim($relativePath, '/'));
            if ($base && $path && strpos($path, $base . DIRECTORY_SEPARATOR) === 0 && is_file($path)) {
                @unlink($path);
            }
        }
    }

    /** Menampilkan dokumen setelah validasi akses berdasarkan departemen. */
    public function supporting_document($documentId)
    {
        $document = $this->M_Reqpic->get_supporting_document((int) $documentId);
        $request = $document ? $this->M_Reqpic->getrequestrow($document->kd_po_nk) : null;
        $level = (string) $this->session->userdata('lv');
        $canAccess = is_super_admin() || $level === '2'
            || (($level === '4' || $level === '5') && $request && $request->departemen === $this->session->userdata('departemen'));

        if (!$document || !$request || !$canAccess) {
            show_404();
            return;
        }

        $base = realpath(FCPATH . 'assets/request-pendukung');
        $path = realpath(FCPATH . ltrim($document->file_path, '/'));
        if (!$base || !$path || strpos($path, $base . DIRECTORY_SEPARATOR) !== 0 || !is_file($path)) {
            show_404();
            return;
        }

        $inline = strpos($document->mime_type, 'image/') === 0 || in_array($document->mime_type, array('application/pdf', 'text/plain'), true);
        $name = str_replace(array('"', "\r", "\n"), '', $document->file_original);
        $this->output
            ->set_content_type($document->mime_type ?: 'application/octet-stream')
            ->set_header('X-Content-Type-Options: nosniff')
            ->set_header('X-Frame-Options: SAMEORIGIN')
            ->set_header('Cache-Control: private, no-store, max-age=0')
            ->set_header('Content-Disposition: ' . ($inline ? 'inline' : 'attachment') . '; filename="' . $name . '"')
            ->set_output(file_get_contents($path));
    }

    public function acc_req_kadep()
    {
        if ((string) $this->session->userdata('lv') !== '5' && !is_super_admin()) {
            show_404();
            return;
        }

        $kdreqpo = $this->input->post('kdreqpo', true);
        $request = $this->M_Reqpic->getrequestrow($kdreqpo);

        if (!$request || !in_array($request->status, array('MENUNGGU ACC KADEP', 'ON PROGRESS - BELUM ACC'), true)) {
            $this->session->set_flashdata('error', 'Request tidak valid untuk approval KADEP.');
            redirect('reqpicacckadep');
            return;
        }

        if (!is_super_admin() && $request->departemen !== $this->session->userdata('departemen')) {
            $this->session->set_flashdata('error', 'Request berbeda departemen.');
            redirect('reqpicacckadep');
            return;
        }

        $this->M_Reqpic->updatereqnk($kdreqpo, array(
            'status' => 'ON PROGRESS - ACC KADEP',
            'acc_with' => $this->session->userdata('kode'),
            'purchasing_opened_at' => date('Y-m-d H:i:s'),
        ));
        $this->M_Purchase->addNote(array(
            'kd_po' => $kdreqpo,
            'isi_note' => 'REQUEST PIC TELAH DI ACC KADEP',
            'kd_user' => $this->session->userdata('kode'),
            'nama_user' => $this->session->userdata('nama_user'),
            'note_for' => '2',
            'update_status' => '2',
        ));

        redirect('reqpicacckadep');
    }

    public function reject_req_kadep()
    {
        if ((string) $this->session->userdata('lv') !== '5' && !is_super_admin()) {
            show_404();
            return;
        }

        $kdreqpo = $this->input->post('kdreqpo', true);
        $note = trim((string) $this->input->post('note_reject', true));
        $request = $this->M_Reqpic->getrequestrow($kdreqpo);

        if (!$request || !in_array($request->status, array('MENUNGGU ACC KADEP', 'ON PROGRESS - BELUM ACC'), true)) {
            $this->session->set_flashdata('error', 'Request tidak valid untuk reject KADEP.');
            redirect('reqpicacckadep');
            return;
        }

        if (!is_super_admin() && $request->departemen !== $this->session->userdata('departemen')) {
            $this->session->set_flashdata('error', 'Request berbeda departemen.');
            redirect('reqpicacckadep');
            return;
        }

        $this->M_Reqpic->updatereqnk($kdreqpo, array(
            'status' => 'PENDING',
            'acc_with' => $this->session->userdata('kode')
        ));
        $this->M_Purchase->addNote(array(
            'kd_po' => $kdreqpo,
            'isi_note' => 'REQUEST PIC DITOLAK KADEP' . ($note !== '' ? ' - ' . $note : ''),
            'kd_user' => $this->session->userdata('kode'),
            'nama_user' => $this->session->userdata('nama_user'),
            'note_for' => '2',
            'update_status' => '2',
        ));

        redirect('reqpicacckadep');
    }

    /**
     * Approval request barang non-komersil dari halaman detail KADEP.
     * Catatan selalu dicatat pada log note request agar dapat dilihat PIC.
     */
    public function process_req_kadep()
    {
        if ((string) $this->session->userdata('lv') !== '5' && !is_super_admin()) {
            show_404();
            return;
        }

        $kdreqpo = $this->input->post('kdreqpo', true);
        $action = strtoupper(trim((string) $this->input->post('approval_action', true)));
        $note = trim((string) $this->input->post('approval_note', true));
        $request = $this->M_Reqpic->getrequestrow($kdreqpo);
        $actions = array(
            'ACC' => array('status' => 'ON PROGRESS - ACC KADEP', 'note' => 'REQUEST PIC TELAH DI ACC KADEP'),
            'REJECT' => array('status' => 'PENDING', 'note' => 'REQUEST PIC DITOLAK KADEP'),
            'REVISI' => array('status' => 'REVISI PO', 'note' => 'REQUEST PIC REVISI KADEP'),
        );

        if (!$request || !in_array($request->status, array('MENUNGGU ACC KADEP', 'ON PROGRESS - BELUM ACC'), true) || !isset($actions[$action])) {
            $this->session->set_flashdata('error', 'Request atau aksi approval tidak valid.');
            redirect('reqpicacckadep');
            return;
        }

        if (!is_super_admin() && $request->departemen !== $this->session->userdata('departemen')) {
            $this->session->set_flashdata('error', 'Request berbeda departemen.');
            redirect('reqpicacckadep');
            return;
        }

        if ($note === '') {
            $this->session->set_flashdata('error', 'Catatan approval wajib diisi.');
            redirect('reqpic/detreqbarangpic/' . $kdreqpo);
            return;
        }

        $this->db->trans_begin();
        $approvalUpdate = array(
            'status' => $actions[$action]['status'],
            'acc_with' => $this->session->userdata('kode'),
        );
        if ($action === 'ACC') {
            $approvalUpdate['purchasing_opened_at'] = date('Y-m-d H:i:s');
        }
        $this->M_Reqpic->updatereqnk($kdreqpo, $approvalUpdate);
        $this->M_Purchase->addNote(array(
            'kd_po' => $kdreqpo,
            'isi_note' => $actions[$action]['note'] . ' - ' . $note,
            'kd_user' => $this->session->userdata('kode'),
            'nama_user' => $this->session->userdata('nama_user'),
            'note_for' => '2',
            'update_status' => '2',
        ));

        if ($this->db->trans_status() === FALSE) {
            $this->db->trans_rollback();
            $this->session->set_flashdata('error', 'Approval gagal disimpan.');
        } else {
            $this->db->trans_commit();
            $this->session->set_flashdata('success', 'Approval KADEP berhasil disimpan.');
        }

        redirect('reqpic/detreqbarangpic/' . $kdreqpo);
    }

    private function generate_kdponk_req($kode_user, $offset = 0)
    {
        // Format: NPONK{ddmmyy}{KODE-PIC}{urutan-4-digit}, e.g.
        // NPONK160926KARYAWAN40001. PIC identity keeps simultaneous
        // submissions from different PICs in separate number namespaces.
        $pic_code = preg_replace('/[^A-Z0-9]/', '', strtoupper((string) $kode_user));
        $pic_code = substr($pic_code !== '' ? $pic_code : 'PIC', 0, 10);
        $prefix = 'NPONK' . date('dmy') . $pic_code;

        $cd1 = $this->db->query(
            'SELECT MAX(RIGHT(kd_barang, 4)) AS kd_max FROM tbpo_generate_kd_ponk WHERE kd_barang LIKE ?',
            array($prefix . '%')
        );
        $kd1 = "0001";

        if ($cd1->num_rows() > 0) {
            foreach ($cd1->result() as $k) {
                $tmp = ((int)$k->kd_max) + 1 + $offset;
                $kd1 = sprintf("%04s", $tmp);
            }
        }

        return $prefix . $kd1;
    }

    public function detreqbarangpic($kdpo)
    {
        $kduser = $this->session->userdata('kode');
        $sts1   = '1';
        $sts2   = '3';
        $sts0   = '0';

        // FUNGSI PIC 
        if ($this->session->userdata('lv') == '4') {
            $data['title']              = 'PO Detail Req PIC';
            $data['status']             = $this->M_Reqpic->getrequestbypic($kdpo);
            $data['stspo']              = $this->M_Reqpic->getbuystsponk($kdpo)->result();
            $data['stspo1']             = $this->M_Reqpic->getbuystsponks($kdpo)->result();
            $data['datereqpic']         = $this->M_Reqpic->getreqpicnosts($kduser, $kdpo)->result();
            $data['getitmlistpicreq']   = $this->M_Reqpic->getitmlistpicreq($kdpo)->result();
            $data['detreqpic1']         = $this->M_Reqpic->getreqwherepic($kduser, $kdpo, $sts1)->result();
            $data['detreqpic2']         = $this->M_Reqpic->getreqwherepic($kduser, $kdpo, $sts2)->result();
            $data['detreqpic0']         = $this->M_Reqpic->getreqwherepic($kduser, $kdpo, $sts0)->result();
            $data['listtr']             = $this->M_Reqpic->getlisttmptr($kdpo)->result();
            $data['totsts']             = $this->M_Reqpic->gettotsts($kdpo)->result();
            $data['detreq']             = $this->M_Reqpic->getreqwheres($kdpo)->result();
            $data['log']                = $this->M_Reqpic->getNoted($kdpo);
            $data['supportingDocuments'] = $this->M_Reqpic->get_supporting_documents($kdpo);
            $data['lifoPickup']          = $this->M_Reqpic->get_lifo_pickup_details($kdpo);

            $this->load->view('partial/header', $data);
            $this->load->view('partial/sidebar');
            $this->load->view('content/po/Reqpic/detreq', $data);
            $this->load->view('partial/footer');
        }

        // FUNGSI ADM PURCHASING
        elseif ($this->session->userdata('lv') == '2' || is_super_admin()) {
            $data['title']      = 'PO Detail Req PIC';
            $data['stspo']      = $this->M_Reqpic->getbuystsponk($kdpo)->result();

            $data['status']          = $this->M_Reqpic->getrequestbypic($kdpo);
            $data['kdponks']         = $this->M_Purchase->getkdnoponk();
            $data['detreq']          = $this->M_Reqpic->getreqwheres($kdpo)->result();
            $data['detreq1']         = $this->M_Reqpic->getreqwhere($kdpo, $sts1)->result();
            $data['detreq2']         = $this->M_Reqpic->getreqwhere($kdpo, $sts2)->result();
            $data['detreq0']         = $this->M_Reqpic->getreqwhere($kdpo, $sts0)->result();
            $data['listtr']          = $this->M_Reqpic->getlisttmptr($kdpo)->result();
            $data['totsts']          = $this->M_Reqpic->gettotsts($kdpo)->result();
            $data['countitm']        = $this->M_Reqpic->count_acc_req($kdpo)->result();
            $data['countjmlharga']   = $this->M_Reqpic->countjmlharga($kdpo)->result();
            $data['log']             = $this->M_Reqpic->getNoted($kdpo);
            $data['gettrs']          = $this->M_Reqpic->gettr($kdpo)->result();
            $data['gettr']           = $this->M_Reqpic->getdetailreq($kdpo)->result();
            $data['supportingDocuments'] = $this->M_Reqpic->get_supporting_documents($kdpo);
            $data['lifoPickup']          = $this->M_Reqpic->get_lifo_pickup_details($kdpo);
            $data['purchasingCanProcess'] = $this->M_Reqpic->can_purchasing_process(
                $this->M_Reqpic->getrequestrow($kdpo)
            );

            $this->load->view('partial/header', $data);
            $this->load->view('partial/sidebar');
            $this->load->view('content/po/Reqpic/detreq', $data);
            $this->load->view('partial/footer');
        }
        elseif ($this->session->userdata('lv') == '5') {
            $request = $this->M_Reqpic->getrequestrow($kdpo);
            if (!$request || $request->departemen !== $this->session->userdata('departemen')) {
                show_404();
                return;
            }

            $data['title']              = 'PO Detail Req PIC';
            $data['status']             = $this->M_Reqpic->getrequestbypic($kdpo);
            $data['stspo']              = $this->M_Reqpic->getbuystsponk($kdpo)->result();
            $data['stspo1']             = $this->M_Reqpic->getbuystsponks($kdpo)->result();
            $data['datereqpic']         = $this->M_Reqpic->getreqpicnosts($request->kd_user, $kdpo)->result();
            $data['getitmlistpicreq']   = $this->M_Reqpic->getitmlistpicreq($kdpo)->result();
            $data['detreqpic1']         = $this->M_Reqpic->getreqwherepic($request->kd_user, $kdpo, $sts1)->result();
            $data['detreqpic2']         = $this->M_Reqpic->getreqwherepic($request->kd_user, $kdpo, $sts2)->result();
            $data['detreqpic0']         = $this->M_Reqpic->getreqwherepic($request->kd_user, $kdpo, $sts0)->result();
            $data['listtr']             = $this->M_Reqpic->getlisttmptr($kdpo)->result();
            $data['totsts']             = $this->M_Reqpic->gettotsts($kdpo)->result();
            $data['detreq']             = $this->M_Reqpic->getreqwheres($kdpo)->result();
            $data['log']                = $this->M_Reqpic->getNoted($kdpo);
            $data['supportingDocuments'] = $this->M_Reqpic->get_supporting_documents($kdpo);
            $data['lifoPickup']          = $this->M_Reqpic->get_lifo_pickup_details($kdpo);

            $this->load->view('partial/header', $data);
            $this->load->view('partial/sidebar');
            $this->load->view('content/po/Reqpic/detreq', $data);
            $this->load->view('partial/footer');
        }
    }
    public function confirmreq()
    {
        date_default_timezone_set("Asia/Jakarta");
        $idponkss       = $this->input->post('idponkss');
        if (!$this->require_purchasing_detail_access($idponkss)) {
            return;
        }
        $kd_user        = $this->input->post('kduserss');
        $itemconfirm    = $this->M_Reqpic->getitemreq($idponkss)->result();
        $now            = date('Y-m-d');

        if ($itemconfirm) {
            foreach ($itemconfirm as $i) {

                $updatedpostmp = array(
                    'kd_akun'           => '11512',
                    'kd_po_nk'          => $i->kd_po_nk,
                    'kd_barang'         => $i->kd_barang,
                    'kd_barangsys'      => $i->kd_bsys,
                    'keterangan'        => $i->ket,
                    'kat_barang'        => $i->kat_barang,
                    'tr_qty'            => $i->qty,
                    'satuan'            => $i->satuan,
                    'status'            => 'confirm',
                    'inputer'           => $kd_user,
                    'create_at'         => $now,
                    'last_updated_by'   => $kd_user
                );
                $detitmreq = array(
                    'status'    => '1'
                );
            }
            $this->M_Reqpic->updatestsitem($idponkss, $detitmreq);
            $this->M_Reqpic->inpt_tr_tmp($updatedpostmp);

            redirect('reqpic/detreqbarangpic/' . $i->kd_po_nk);
        }
    }
    public function actconfirm($id)
    {
        date_default_timezone_set("Asia/Jakarta");
        if (!$this->require_purchasing_detail_access($id)) {
            return;
        }
        $itempnd        = $this->M_Reqpic->getitemreq($id)->result();
        $now            = date('Y-m-d h:m:s');
        $now1           = date('Y-m-d');

        if ($itempnd) {
            foreach ($itempnd as $i) {
                $updatedpostmp = array(
                    'kd_akun'           => '11512',
                    'kd_po_nk'          => $i->kd_po_nk,
                    'kd_barang'         => $i->kd_barang,
                    'kd_barangsys'      => $i->kd_bsys,
                    'keterangan'        => 'tmp-req',
                    'kat_barang'        => $i->kat_barang,
                    'tr_qty'            => $i->qty,
                    'satuan'            => $i->satuan,
                    'inputer'           => $this->session->userdata('kode'),
                    'req_by'            => $i->kduser,
                    'tgl_transaksi'     => $now1,
                    'create_at'         => $now,
                    'last_updated_by'   => $this->session->userdata('kode')
                );
                $updtstatus = array(
                    'status'    => '1'
                );
                $kode_po    = $i->kd_po_nk;
                // $this->M_Reqpic->insert_transaksi($updatedpostmp);
                $this->M_Reqpic->updatedetreqitm($id, $updtstatus);
                redirect('reqpic/detreqbarangpic/' . $kode_po);
            }
        }
    }
    public function actpending($id, $kd)
    {
        date_default_timezone_set("Asia/Jakarta");
        if (!$this->require_purchasing_detail_access($id)) {
            return;
        }
        $qty        = $this->input->post('qty_isi');
        $hrgsatuan  = $this->input->post('hrg_isi');
        $itempnd    = $this->M_Reqpic->getitemreq($id)->result();
        $now        = date('Y-m-d h:m:s');

        if ($itempnd) {
            foreach ($itempnd as $i) {
                $updatedpostmp = array(
                    'kd_akun'           => '11512',
                    'kd_po_nk'          => $i->kd_po_nk,
                    'kd_barang'         => $i->kd_barang,
                    'kd_barangsys'      => $i->kd_bsys,
                    'kat_barang'        => $i->kat_barang,
                    'tr_qty'            => $qty,
                    'satuan'            => $i->satuan,
                    'hrg_satuan'        => $hrgsatuan,
                    'status'            => 'pending',
                    'keterangan'        => $i->ket,
                    'inputer'           => $this->session->userdata('kode'),
                    'create_at'         => $now,
                    'last_updated_by'   => $this->session->userdata('kode')
                );

                $updtstatus = array(
                    'status'    => '4'
                );

                $this->M_Reqpic->insert_tmp_transaksi($updatedpostmp);
                $this->M_Reqpic->updatedetreqitm($id, $updtstatus);
                redirect('reqpic/detreqbarangpic/' . $kd);
            }
        }
    }

    public function pendingreq()
    {
        date_default_timezone_set("Asia/Jakarta");

        $idponks        = $this->input->post('pndidponks');
        $kdponk         = $this->input->post('pndkdponk');
        if (!$this->require_purchasing_request_access($kdponk)) {
            return;
        }
        $kdponks        = $this->input->post('pndkdponks');
        $jml            = $this->input->post('pndjmls');
        $kd_user        = $this->input->post('pndkduser');
        $nm_user        = $this->input->post('pndnmuser');
        $departemen     = $this->input->post('pnddep');
        $tjbeli         = $this->input->post('pndtjbeli');
        $totn           = $this->input->post('pndtotn');
        $itemconfirm    = $this->M_Reqpic->getitemreq($idponks)->result();
        $now            = date('Y-m-d');

        if ($totn == '0') {

            $newponk = array(
                'jns_po'            => '2',
                'kd_po_nk'          => $kdponks,
                'kd_po_req'         => $kdponk,
                'nopo'              => '-',
                'kd_user'           => $kd_user,
                'nm_user'           => $nm_user,
                'tgl_transaksi'     => $now,
                'jml_item'          => $jml,
                'total_harga'       => '0',
                'status'            => 'ON PROGRESS - KADEP',
                'departemen'        => $departemen,
                'tj_pembelian'      => $tjbeli,
                'tax'               => '0',
                'hrg_pajak'         => '0',
                'hrg_nyata'         => '0',
                'status_hrg_nyata'  => '0',
                'acc_with'          => '',
                'acc_with_kadep'    => ''
            );

            if ($itemconfirm) {
                foreach ($itemconfirm as $i) {
                    $updatedpostmp = array(
                        'kd_akun'           => '11512',
                        'kd_po_nk'          => $i->kd_po_nk,
                        'kd_barang'         => $i->kd_barang,
                        'kd_barangsys'      => $i->kd_bsys,
                        'kat_barang'        => $i->kat_barang,
                        'tr_qty'            => $i->qty,
                        'satuan'            => $i->satuan,
                        'status'            => 'pending',
                        'keterangan'        => $i->ket,
                        'inputer'           => $kd_user,
                        'create_at'         => $now,
                        'last_updated_by'   => $kd_user
                    );
                    $dataitm = array(
                        'kd_po_nk'      => $kdponks,
                        'kd_po_req'     => $kdponk,
                        'kd_user'       => $kd_user,
                        'tgl_transaksi' => $now,
                        'kd_bsys'       => $i->kd_bsys,
                        'kd_barang'     => $i->kd_barang,
                        'nama_barang'   => $i->nm_barang,
                        'deskripsi'     => $i->descnk,
                        'keterangan'    => $i->ket,
                        'qty'           => $i->qty,
                        'hrg_satuan'    => '0',
                        'hrg_nyata'     => '0',
                        'total_harga'   => '0',
                        'total_nyata'   => '0',
                        'gbr_produk'    => 'Karisma.png',
                    );
                    $this->M_Reqpic->insert_tmp_transaksi($updatedpostmp);
                    $this->M_Reqpic->insertbrponkpending($dataitm);
                }
            }
            $detitmreq = array(
                'status'    => '3'
            );
            $this->M_Reqpic->insertpobaru($newponk);
            $this->M_Reqpic->updatestsitem($idponks, $detitmreq);
            redirect('reqpic/detreqbarangpic/' . $kdponk);
        } else {
            if ($itemconfirm) {
                foreach ($itemconfirm as $i) {
                    $updatedpostmps = array(
                        'kd_akun'           => '11512',
                        'kd_po_nk'          => $i->kd_po_nk,
                        'kd_barang'         => $i->kd_barang,
                        'kd_barangsys'      => $i->kd_bsys,
                        'keterangan'        => $i->ket,
                        'kat_barang'        => $i->kat_barang,
                        'tr_qty'            => $i->qty,
                        'satuan'            => $i->satuan,
                        'status'            => 'pending',
                        'inputer'           => $kd_user,
                        'create_at'         => $now,
                        'last_updated_by'   => $kd_user
                    );
                    $dataitms = array(
                        'kd_po_nk'      => $kdponks,
                        'kd_po_req'     => $kdponk,
                        'kd_user'       => $kd_user,
                        'tgl_transaksi' => $now,
                        'kd_bsys'       => $i->kd_bsys,
                        'kd_barang'     => $i->kd_barang,
                        'nama_barang'   => $i->nm_barang,
                        'deskripsi'     => $i->descnk,
                        'keterangan'    => $i->ket,
                        'qty'           => $i->qty,
                        'hrg_satuan'    => '0',
                        'hrg_nyata'     => '0',
                        'total_harga'   => '0',
                        'total_nyata'   => '0',
                        'gbr_produk'    => 'Karisma.png',
                    );
                    $this->M_Reqpic->insert_tmp_transaksi($updatedpostmps);
                    $this->M_Reqpic->insertbrponkpending($dataitms);
                }
            }
            $detitmreq = array(
                'status'    => '3'
            );

            $this->M_Reqpic->updatestsitem($idponks, $detitmreq);
            redirect('reqpic/detreqbarangpic/' . $kdponk);
        }
    }
    public function pendingreq1()
    {
        date_default_timezone_set("Asia/Jakarta");

        $idponks        = $this->input->post('idpnd');
        $kdponk         = $this->input->post('kdponkpnd');
        if (!$this->require_purchasing_request_access($kdponk)) {
            return;
        }
        $kdponks        = $this->input->post('kdponkspnd');
        $jml            = $this->input->post('jmlspnd');
        $kd_user        = $this->input->post('kduserpnd');
        $nm_user        = $this->input->post('nmuserpnd');
        $departemen     = $this->input->post('deppnd');
        $tjbeli         = $this->input->post('tjbelipnd');
        $totn           = $this->input->post('totnpnd');
        $itemconfirm    = $this->M_Reqpic->getitemreq($idponks)->result();
        $now            = date('Y-m-d');

        if ($totn == '0') {

            $newponk = array(
                'jns_po'            => '2',
                'kd_po_nk'          => $kdponks,
                'kd_po_req'         => $kdponk,
                'nopo'              => '-',
                'kd_user'           => $kd_user,
                'nm_user'           => $nm_user,
                'tgl_transaksi'     => $now,
                'jml_item'          => $jml,
                'total_harga'       => '0',
                'status'            => 'ON PROGRESS - KADEP',
                'departemen'        => $departemen,
                'tj_pembelian'      => $tjbeli,
                'tax'               => '0',
                'hrg_pajak'         => '0',
                'hrg_nyata'         => '0',
                'status_hrg_nyata'  => '0',
                'acc_with'          => '',
                'acc_with_kadep'    => ''
            );

            if ($itemconfirm) {
                foreach ($itemconfirm as $i) {
                    $updatedpostmp = array(
                        'kd_akun'           => '11512',
                        'kd_po_nk'          => $i->kd_po_nk,
                        'kd_barang'         => $i->kd_barang,
                        'kd_barangsys'      => $i->kd_bsys,
                        'keterangan'        => $i->ket,
                        'kat_barang'        => $i->kat_barang,
                        'tr_qty'            => $i->qty,
                        'satuan'            => $i->satuan,
                        'status'            => 'pending',
                        'inputer'           => $kd_user,
                        'create_at'         => $now,
                        'last_updated_by'   => $kd_user
                    );
                    $dataitm = array(
                        'kd_po_nk'      => $kdponks,
                        'kd_po_req'     => $kdponk,
                        'kd_user'       => $kd_user,
                        'tgl_transaksi' => $now,
                        'kd_bsys'       => $i->kd_bsys,
                        'kd_barang'     => $i->kd_barang,
                        'nama_barang'   => $i->nm_barang,
                        'deskripsi'     => $i->descnk,
                        'keterangan'    => $i->ket,
                        'qty'           => $i->qty,
                        'hrg_satuan'    => '0',
                        'hrg_nyata'     => '0',
                        'total_harga'   => '0',
                        'total_nyata'   => '0',
                        'gbr_produk'    => 'Karisma.png',
                    );
                    $this->M_Reqpic->insert_tmp_transaksi($updatedpostmp);
                    $this->M_Reqpic->insertbrponkpending($dataitm);
                }
            }
            $detitmreq = array(
                'status'    => '3'
            );
            $this->M_Reqpic->insertpobaru($newponk);
            $this->M_Reqpic->updatestsitem($idponks, $detitmreq);
            redirect('reqpic/detreqbarangpic/' . $kdponk);
        } else {
            if ($itemconfirm) {
                foreach ($itemconfirm as $i) {
                    $updatedpostmps = array(
                        'kd_akun'           => '11512',
                        'kd_po_nk'          => $i->kd_po_nk,
                        'kd_barang'         => $i->kd_barang,
                        'kd_barangsys'      => $i->kd_bsys,
                        'keterangan'        => $i->ket,
                        'kat_barang'        => $i->kat_barang,
                        'tr_qty'            => $i->qty,
                        'satuan'            => $i->satuan,
                        'status'            => 'pending',
                        'inputer'           => $kd_user,
                        'create_at'         => $now,
                        'last_updated_by'   => $kd_user
                    );
                    $dataitms = array(
                        'kd_po_nk'      => $kdponks,
                        'kd_po_req'     => $kdponk,
                        'kd_user'       => $kd_user,
                        'tgl_transaksi' => $now,
                        'kd_bsys'       => $i->kd_bsys,
                        'kd_barang'     => $i->kd_barang,
                        'nama_barang'   => $i->nm_barang,
                        'deskripsi'     => $i->descnk,
                        'keterangan'    => $i->ket,
                        'qty'           => $i->qty,
                        'hrg_satuan'    => '0',
                        'hrg_nyata'     => '0',
                        'total_harga'   => '0',
                        'total_nyata'   => '0',
                        'gbr_produk'    => 'Karisma.png',
                    );
                    $this->M_Reqpic->insert_tmp_transaksi($updatedpostmps);
                    $this->M_Reqpic->insertbrponkpending($dataitms);
                }
            }
            $detitmreq = array(
                'status'    => '3'
            );

            $this->M_Reqpic->updatestsitem($idponks, $detitmreq);
            redirect('reqpic/detreqbarangpic/' . $kdponk);
        }
    }
    public function acc_req_admin_plus()
    {
        //PIC ADMIN
        $kdreqpo    = $this->input->post('kdreqpo');
        if (!$this->require_purchasing_request_access($kdreqpo)) {
            return;
        }
        $updatests  = array(
            'status'    => 'REQUEST ACC',
            'acc_with'  => $this->session->userdata('kode')
        );

        $inputnt    = array(
            'kd_po'         => $kdreqpo,
            'isi_note'      => 'PENGAJUAN DITERIMA',
            'kd_user'       => $this->session->userdata('kode'),
            'nama_user'     => $this->session->userdata('nama_user'),
            'note_for'      => '2',
            'update_status' => '2',
        );
        $this->M_Reqpic->updatereqnk($kdreqpo, $updatests);
        $this->M_Purchase->addNote($inputnt);
        $this->M_Reqpic->deletetmptrreq($kdreqpo);
        redirect('reqpic/detreqbarangpic/' . $kdreqpo);
    }

    public function acc_req_admin()
    {
        //PIC ADMIN
        $useradmin  =  $this->session->userdata('nama_user');
        $kdadmin  =  $this->session->userdata('kode');

        $kdponk     = $this->input->post('kdponk');
        $kdreqpo    = $this->input->post('kdreqpo');
        if (!$this->require_purchasing_request_access($kdreqpo)) {
            return;
        }
        $kduser     = $this->input->post('kdpic');
        $nmuser     = $this->input->post('pic');
        $jmlitm     = $this->input->post('jml');
        $dep        = $this->input->post('dep');
        $jmltot     = $this->input->post('jmltot');
        $tjpem      = $this->input->post('tjbuy');
        $trtmp      = $this->M_Reqpic->getlisttmptr($kdreqpo)->result();
        $now        = date('Y-m-d h:m:s');
        $now1       = date('Y-m-d');

        $insrtpembelianpo = array(
            'jns_po'            => '2',
            'kd_po_nk'          => $kdponk,
            'kd_po_req'         => $kdreqpo,
            'nopo'              => '-',
            'kd_user'           => $kdadmin,
            'nm_user'           => $useradmin,
            'tgl_transaksi'     => $now1,
            'jml_item'          => $jmlitm,
            'total_harga'       => $jmltot,
            'status'            => 'ON PROGRESS - KADEP',
            'departemen'        => $dep,
            'tj_pembelian'      => $tjpem,
            'tax'               => '0',
            'hrg_pajak'         => '0',
            'hrg_nyata'         => '0',
            'status_hrg_nyata'  => '0',
            'acc_with'          => $this->session->userdata('kode'),
            'acc_with_kadep'    => ''
        );
        $this->M_Reqpic->inputponew($insrtpembelianpo);

        $updatests  = array(
            'status'    => 'REQUEST ACC',
            'acc_with'  => $this->session->userdata('kode')
        );

        $this->M_Reqpic->updatereqnk($kdreqpo, $updatests);
        // ==================================================================================
        $inputnt    = array(
            'kd_po'         => $kdreqpo,
            'isi_note'      => 'PENGAJUAN DITERIMA',
            'kd_user'       => $this->session->userdata('kode'),
            'nama_user'     => $this->session->userdata('nama_user'),
            'note_for'      => '2',
            'update_status' => '2',
        );
        $this->M_Purchase->addNote($inputnt);

        $inputntpobaru    = array(
            'kd_po'         => $kdponk,
            'isi_note'      => 'PO Re-Stock Barang Kebutuhan',
            'kd_user'       => $this->session->userdata('kode'),
            'nama_user'     => $this->session->userdata('nama_user'),
            'note_for'      => '2',
            'update_status' => '2',
        );

        $updatests = array(
            'kd_po_nk'  => $kdreqpo,
            'status'    => '4'
        );

        $this->M_Purchase->addNote($inputntpobaru);
        $this->M_Reqpic->updatereqnk_stsbr($kdreqpo, $updatests);

        if ($trtmp) {
            foreach ($trtmp as $i) {

                $qty    = $i->tr_qty;
                $hrgs   = $i->hrg_satuan;
                $tothrg = ($qty * $hrgs);

                $datainputdetailpo = array(
                    'kd_po_nk'      => $kdponk,
                    'kd_po_req'     => $kdreqpo,
                    'kd_user'       => $kduser,
                    'tgl_transaksi' => $now1,
                    'kd_bsys'       => $i->kd_barangsys,
                    'kd_barang'     => $i->kd_barang,
                    'nama_barang'   => $i->nama_barang,
                    'deskripsi'     => $i->descnk,
                    'keterangan'    => $i->keterangan,
                    'qty'           => $i->tr_qty,
                    'satuan'        => $i->satuan,
                    'kat_barang'    => $i->kat_barang,
                    'hrg_satuan'    => $i->hrg_satuan,
                    'hrg_nyata'     => '0',
                    'total_harga'   => $tothrg,
                    'total_nyata'   => '0',
                    'gbr_produk'    => 'Karisma.png',
                );
                $this->M_Reqpic->insertbrponkpending($datainputdetailpo);
                $this->M_Reqpic->deletetmptrreq($kdreqpo);
            }
            redirect('reqpic');
        }
        redirect('reqpic/detreqbarangpic/' . $kdreqpo);
    }

    public function accreqpic()
    {
        $kdponk     = $this->input->post('kdponk');
        if (!$this->require_purchasing_request_access($kdponk)) {
            return;
        }
        $kdponks    = $this->input->post('kdponks');
        $jml        = $this->input->post('jmls');
        $tgl        = $this->input->post('tgl');
        $kd_user    = $this->input->post('kduser');
        $nm_user    = $this->input->post('nmuser');
        $departemen = $this->input->post('dep');
        $tjbeli     = $this->input->post('tjbeli');
        $kduser     = $this->session->userdata('kode');
        $nmuser     = $this->session->userdata('nama_user');
        $totn     = $this->input->post('totn');
        $trtmp      = $this->M_Reqpic->getlisttmptr($kdponk)->result();
        $now        = date('Y-m-d');

        $updatests  = array(
            'status'    => 'REQUEST ACC',
            'acc_with'  => $kduser
        );
        $this->M_Reqpic->updatereqnk($kdponk, $updatests);
        // ==================================================================================
        $inputnt    = array(
            'kd_po'         => $kdponk,
            'isi_note'      => 'PENGAJUAN DITERIMA',
            'kd_user'       => $kduser,
            'nama_user'     => $nmuser,
            'note_for'      => '2',
            'update_status' => '2',
        );
        $this->M_Purchase->addNote($inputnt);
        // ==================================================================================

        if ($totn > 1) {
            $inrtponk = array(
                'jns_po'            => '2',
                'kd_po_nk'          => $kdponks,
                'kd_po_req'         => $kdponk,
                'nopo'              => '-',
                'kd_user'           => $kd_user,
                'nm_user'           => $nm_user,
                'tgl_transaksi'     => $now,
                'jml_item'          => $jml,
                'total_harga'       => '0',
                'status'            => 'ON PROGRESS - KADEP',
                'departemen'        => $departemen,
                'tj_pembelian'      => $tjbeli,
                'tax'               => '0',
                'hrg_pajak'         => '0',
                'hrg_nyata'         => '0',
                'status_hrg_nyata'  => '0',
                'acc_with'          => '',
                'acc_with_kadep'    => ''
            );
            $inputnknote    = array(
                'kd_po'         => $kdponks,
                'isi_note'      => 'PO-PERSEDIAAN BARANG',
                'kd_user'       => $kduser,
                'nama_user'     => $nmuser,
                'note_for'      => '2',
                'update_status' => '2',
            );

            $this->M_Reqpic->inputponew($inrtponk);
            $this->M_Purchase->addNote($inputnknote);

            if ($trtmp) {
                foreach ($trtmp as $t) {
                    if ($t->status == 'confirm') {
                        $dtitm  = array(
                            'kd_akun'           => '11512',
                            'kd_po_nk'          => $t->kd_po_nk,
                            'kd_barang'         => $t->kd_barang,
                            'kd_barangsys'      => $t->kd_barangsys,
                            'keterangan'        => $t->keterangan,
                            'kat_barang'        => $t->kat_barang,
                            'tr_qty'            => $t->tr_qty,
                            'satuan'            => $t->satuan,
                            'inputer'           => $kduser,
                            'tgl_transaksi'     => $tgl,
                            'create_at'         => $now,
                            'last_updated_by'   => $kduser
                        );
                        $this->M_Reqpic->insert_transaksi($dtitm);
                    } else {
                        $dtinsrtponk = array(
                            'kd_po_nk'      => $kdponks,
                            'kd_po_req'     => $kdponk,
                            'kd_user'       => $t->kd_user,
                            'tgl_transaksi' => $now,
                            'kd_bsys'       => $t->kd_barangsys,
                            'kd_barang'     => $t->kd_barang,
                            'nama_barang'   => $t->nama_barang,
                            'deskripsi'     => $t->descnk,
                            'keterangan'    => $t->keterangan,
                            'qty'           => $t->tr_qty,
                            'hrg_satuan'    => '0',
                            'hrg_nyata'     => '0',
                            'total_harga'   => '0',
                            'total_nyata'   => '0',
                            'gbr_produk'    => $t->gbr_barang,
                        );
                        $this->M_Reqpic->insert_tmp_po_persediaan($dtinsrtponk);
                        $this->M_Reqpic->deletetmptrreq($kdponk);
                    }
                }
            }
        } else {
            if ($trtmp) {
                foreach ($trtmp as $t) {
                    if ($t->status == 'confirm') {
                        $dtitm  = array(
                            'kd_akun'           => '11512',
                            'kd_po_nk'          => $t->kd_po_nk,
                            'kd_barang'         => $t->kd_barang,
                            'kd_barangsys'      => $t->kd_barangsys,
                            'keterangan'        => $t->keterangan,
                            'kat_barang'        => $t->kat_barang,
                            'tr_qty'            => $t->tr_qty,
                            'satuan'            => $t->satuan,
                            'inputer'           => $kduser,
                            'tgl_transaksi'     => $tgl,
                            'create_at'         => $now,
                            'last_updated_by'   => $kduser
                        );
                        $this->M_Reqpic->insert_transaksi($dtitm);
                    } else {
                        $dtinsrtponk = array(
                            'kd_po_nk'      => $kdponks,
                            'kd_po_req'     => $kdponk,
                            'kd_user'       => $t->kd_user,
                            'tgl_transaksi' => $now,
                            'kd_bsys'       => $t->kd_barangsys,
                            'kd_barang'     => $t->kd_barang,
                            'nama_barang'   => $t->nama_barang,
                            'deskripsi'     => $t->descnk,
                            'keterangan'    => $t->keterangan,
                            'qty'           => $t->tr_qty,
                            'hrg_satuan'    => '0',
                            'hrg_nyata'     => '0',
                            'total_harga'   => '0',
                            'total_nyata'   => '0',
                            'gbr_produk'    => $t->gbr_barang,
                        );
                        $this->M_Reqpic->insert_tmp_po_persediaan($dtinsrtponk);
                        $this->M_Reqpic->deletetmptrreq($kdponk);
                    }
                }
            }
        }
        redirect('reqpic');
    }

    public function reqpicconfirmed()
    {
        if ((string) $this->session->userdata('lv') !== '2' && !is_super_admin()) {
            show_404();
            return;
        }
        $kdadmin    = $this->session->userdata('kode');
        $nmadmin    = $this->session->userdata('nama_user');
        $kdporeq    = $this->input->post('kdreqpo');
        $kdponk     = $this->input->post('kdponk');
        $request    = $this->M_Reqpic->getrequestrow($kdporeq);
        if (!$request || $request->status !== 'REQUEST ACC') {
            show_error('Request tidak valid untuk konfirmasi barang tersedia.', 422, 'Status request tidak valid');
            return;
        }
        $now        = date('Y-m-d h:m:s');
        $now1       = date('Y-m-d');
        $tmp        = $this->M_Reqpic->getdatapobaru($kdponk)->result();

        $updatests  = array(
            'status'    => 'BARANG TERSEDIA',
            'acc_with'  => $kdadmin
        );
        $this->M_Reqpic->updatereqnk($kdporeq, $updatests);

        $updatepobeli = array(
            'status'    => 'DONE'
        );
        $this->M_Reqpic->updatestsponk($kdponk, $updatepobeli);

        $addnoteuser = array(
            'kd_po'         => $kdponk,
            'isi_note'      => 'BARANG DI TERIMA - ADMIN',
            'kd_user'       => $kdadmin,
            'nama_user'     => $nmadmin,
            'note_for'      => '1',
            'update_status' => '1'
        );
        $addnoteuser1 = array(
            'kd_po'         => $kdporeq,
            'isi_note'      => 'BARANG DI TERIMA - ADMIN',
            'kd_user'       => $kdadmin,
            'nama_user'     => $nmadmin,
            'note_for'      => '2',
            'update_status' => '2'
        );
        $this->M_Purchase->addNote($addnoteuser);
        $this->M_Purchase->addNote($addnoteuser1);

        if ($tmp) {
            foreach ($tmp as $t) {
                $databelink = array(
                    'kd_akun'           => '11511',
                    'kd_po_nk'          => $t->kdporeq,
                    'kd_barang'         => $t->kdbr,
                    'kd_barangsys'      => $t->kdbsys,
                    'keterangan'        => $t->ket,
                    'kat_barang'        => $t->kat,
                    'tr_qty'            => $t->trqty,
                    'satuan'            => $t->satuan,
                    'tgl_transaksi'     => $now1,
                    'inputer'           => $this->session->userdata('kode'),
                    'req_by'            => $t->kduser,
                    'create_at'         => $now,
                    'last_updated_by'   => $this->session->userdata('kode')
                );
                $this->M_Reqpic->input_tr($databelink);
            }
            redirect('reqpic/detreqbarangpic/' . $kdporeq);
        }
    }
    public function reqpicconfirmed_plus()
    {
        if ((string) $this->session->userdata('lv') !== '2' && !is_super_admin()) {
            show_404();
            return;
        }
        $kdadmin    = $this->session->userdata('kode');
        $nmadmin    = $this->session->userdata('nama_user');
        $kdporeq    = $this->input->post('kdreqpo');
        $request    = $this->M_Reqpic->getrequestrow($kdporeq);
        if (!$request || $request->status !== 'REQUEST ACC') {
            show_error('Request tidak valid untuk konfirmasi barang tersedia.', 422, 'Status request tidak valid');
            return;
        }

        $updatests  = array(
            'status'    => 'BARANG TERSEDIA',
            'acc_with'  => $kdadmin
        );
        $this->M_Reqpic->updatereqnk($kdporeq, $updatests);

        $addnoteuser1 = array(
            'kd_po'         => $kdporeq,
            'isi_note'      => 'BARANG DI TERIMA - ADMIN',
            'kd_user'       => $kdadmin,
            'nama_user'     => $nmadmin,
            'note_for'      => '2',
            'update_status' => '2'
        );
        $this->M_Purchase->addNote($addnoteuser1);

        redirect('reqpic/detreqbarangpic/' . $kdporeq);
    }

    /** PIC mengajukan persetujuan KADEP sebelum barang tersedia diserahkan. */
    public function request_pickup_approval_pic()
    {
        if ((string) $this->session->userdata('lv') !== '4' && !is_super_admin()) {
            show_404();
            return;
        }

        $kdponk = $this->input->post('kdponk', true);
        $request = $this->M_Reqpic->getrequestrow($kdponk);
        if (!$request || $request->status !== 'BARANG TERSEDIA'
            || (!is_super_admin() && $request->kd_user !== $this->session->userdata('kode'))) {
            $this->session->set_flashdata('error', 'Request tidak valid untuk pengajuan pengambilan barang.');
            redirect('reqpic/detreqbarangpic/' . $kdponk);
            return;
        }

        $this->db->trans_begin();
        $updated = $this->M_Reqpic->update_request_status_if_current($kdponk, 'BARANG TERSEDIA', array(
            'status' => 'MENUNGGU ACC PENGAMBILAN',
        ));
        if (!$updated) {
            $this->db->trans_rollback();
            $this->session->set_flashdata('error', 'Status request telah berubah. Silakan muat ulang halaman.');
            redirect('reqpic/detreqbarangpic/' . $kdponk);
            return;
        }
        $this->M_Purchase->addNote(array(
            'kd_po' => $kdponk,
            'isi_note' => 'PENGAJUAN PERSETUJUAN PENGAMBILAN BARANG OLEH PIC',
            'kd_user' => $this->session->userdata('kode'),
            'nama_user' => $this->session->userdata('nama_user'),
            'note_for' => '2',
            'update_status' => '2',
        ));
        if ($this->db->trans_status() === FALSE) {
            $this->db->trans_rollback();
            $this->session->set_flashdata('error', 'Permohonan pengambilan barang gagal disimpan.');
        } else {
            $this->db->trans_commit();
            $this->session->set_flashdata('success', 'Permohonan pengambilan berhasil dikirim ke KADEP.');
        }
        redirect('reqpic/detreqbarangpic/' . $kdponk);
    }

    /** KADEP menyetujui atau menolak permohonan pengambilan dari PIC. */
    public function decide_pickup_approval_kadep()
    {
        if ((string) $this->session->userdata('lv') !== '5' && !is_super_admin()) {
            show_404();
            return;
        }

        $kdponk = $this->input->post('kdponk', true);
        $decision = strtoupper((string) $this->input->post('decision', true));
        $note = trim((string) $this->input->post('note', true));
        $request = $this->M_Reqpic->getrequestrow($kdponk);
        if (!$request || trim((string) $request->status) !== 'MENUNGGU ACC PENGAMBILAN'
            || (!is_super_admin() && $request->departemen !== $this->session->userdata('departemen'))
            || !in_array($decision, array('ACC', 'TOLAK'), true)
            || ($decision === 'TOLAK' && $note === '')) {
            $this->session->set_flashdata('error', 'Keputusan pengambilan tidak valid. Catatan penolakan wajib diisi.');
            redirect('reqpicpickupapproval');
            return;
        }

        $approved = $decision === 'ACC';
        $this->db->trans_begin();
        $updated = $this->M_Reqpic->update_request_status_if_current(
            $kdponk,
            'MENUNGGU ACC PENGAMBILAN',
            $approved ? array('status' => 'MENUNGGU PENYERAHAN BARANG', 'acc_with' => $this->session->userdata('kode')) : array('status' => 'BARANG TERSEDIA')
        );
        if (!$updated) {
            $this->db->trans_rollback();
            $this->session->set_flashdata('error', 'Status request telah berubah. Keputusan tidak disimpan.');
            redirect('reqpicpickupapproval');
            return;
        }
        $this->M_Purchase->addNote(array(
            'kd_po' => $kdponk,
            'isi_note' => $approved
                ? 'PENGAJUAN PENGAMBILAN BARANG DISETUJUI KADEP' . ($note !== '' ? ': ' . $note : '')
                : 'PENGAJUAN PENGAMBILAN BARANG DITOLAK KADEP: ' . $note,
            'kd_user' => $this->session->userdata('kode'),
            'nama_user' => $this->session->userdata('nama_user'),
            'note_for' => '2',
            'update_status' => '2',
        ));
        if ($this->db->trans_status() === FALSE) {
            $this->db->trans_rollback();
            $this->session->set_flashdata('error', 'Keputusan pengambilan gagal disimpan.');
        } else {
            $this->db->trans_commit();
            $this->session->set_flashdata('success', $approved
                ? 'Pengambilan disetujui dan telah diteruskan ke Purchasing.'
                : 'Pengambilan ditolak. Request dikembalikan ke status BARANG TERSEDIA.');
        }
        redirect('reqpicpickupapproval');
    }

    /** Kompatibilitas URL lama; pengajuan kini dilakukan oleh PIC. */
    public function request_pickup_kadep()
    {
        $this->request_pickup_approval_pic();
    }

    public function reqpicdone()
    {
        $kdponk     = $this->input->post('kdponk');
        $pic        = $this->input->post('kd_user');
        $actdone    = $this->input->post('actdone');
        $now        = date('Y-m-d');
        $kduser     = $this->session->userdata('kode');
        $nmuser     = $this->session->userdata('nama_user');
        $request    = $this->M_Reqpic->getrequestrow($kdponk);
        $pickupWaitingStatuses = array('MENUNGGU PENYERAHAN BARANG', 'MENUNGGU PENYERAHAN BARAN');
        if (((string) $this->session->userdata('lv') !== '2' && !is_super_admin()) || !$request || !in_array(trim((string) $request->status), $pickupWaitingStatuses, true)) {
            show_error('Hanya Purchasing yang dapat mengonfirmasi penyerahan barang.', 403, 'Akses ditolak');
            return;
        }
        $tmp        = $this->M_Reqpic->getdatapobarureq($kdponk)->result();

        if ($actdone == '1') {
            $updatests  = array(
                'tgl_ambil' => $now,
                'status'    => 'DONE',
                'acc_with'  => $kduser
            );
            $this->M_Reqpic->updatereqnk($kdponk, $updatests);

            $inputnt    = array(
                'kd_po'         => $kdponk,
                'isi_note'      => 'BARANG DISERAHKAN PURCHASING - ' . $pic,
                'kd_user'       => $kduser,
                'nama_user'     => $nmuser,
                'note_for'      => '2',
                'update_status' => '2',
            );
            $this->M_Purchase->addNote($inputnt);

            if ($tmp) {
                foreach ($tmp as $t) {
                    if ($t->tjket == 'Restock By Admin PO') {
                        redirect('reqpic/detreqbarangpic/' . $kdponk);
                    } else {
                        if ($t->ket == 'tmp-req') {
                            $dataconfirm = array(
                                'kd_akun'           => '11512',
                                'kd_po_nk'          => $t->kdporeq,
                                'kd_barang'         => $t->kdbr,
                                'kd_barangsys'      => $t->kdbsys,
                                'keterangan'        => $t->ket,
                                'kat_barang'        => $t->kat,
                                'tr_qty'            => $t->trqty,
                                'satuan'            => $t->satuan,
                                'tgl_transaksi'     => $now,
                                'inputer'           => $this->session->userdata('kode'),
                                'req_by'            => $t->kduser,
                                'create_at'         => $now,
                                'last_updated_by'   => $this->session->userdata('kode')
                            );
                            $sts = 'tmp-req';

                            $this->M_Reqpic->input_tr($dataconfirm);
                            if ($this->M_StockLifo->schema_ready()) {
                                $this->M_StockLifo->allocate_new_outgoing((int) $this->db->insert_id());
                            }
                            $this->M_Reqpic->deleteitemtrtmp($kdponk, $kduser, $sts);
                        } else {
                            $dataconfirm = array(
                                'kd_akun'           => '11512',
                                'kd_po_nk'          => $t->kdporeq,
                                'kd_barang'         => $t->kdbr,
                                'kd_barangsys'      => $t->kdbsys,
                                'keterangan'        => $t->ket,
                                'kat_barang'        => $t->kat,
                                'tr_qty'            => $t->trqty,
                                'satuan'            => $t->satuan,
                                'tgl_transaksi'     => $now,
                                'inputer'           => $this->session->userdata('kode'),
                                'req_by'            => $t->kduser,
                                'create_at'         => $now,
                                'last_updated_by'   => $this->session->userdata('kode')
                            );
                            $this->M_Reqpic->input_tr($dataconfirm);
                            if ($this->M_StockLifo->schema_ready()) {
                                $this->M_StockLifo->allocate_new_outgoing((int) $this->db->insert_id());
                            }
                        }
                    }
                }
                redirect('reqpic/detreqbarangpic/' . $kdponk);
            }
        } elseif ($actdone == '2') {

            $updatests  = array(
                'tgl_ambil' => $now,
                'status'    => 'DONE',
                'acc_with'  => $kduser
            );
            $this->M_Reqpic->updatereqnk($kdponk, $updatests);
            $inputnt    = array(
                'kd_po'         => $kdponk,
                'isi_note'      => 'BARANG DISERAHKAN PURCHASING - ' . $pic,
                'kd_user'       => $kduser,
                'nama_user'     => $nmuser,
                'note_for'      => '2',
                'update_status' => '2',
            );
            $this->M_Purchase->addNote($inputnt);

            if ($tmp) {
                foreach ($tmp as $t) {
                    $dataconfirm = array(
                        'kd_akun'           => '11512',
                        'kd_po_nk'          => $t->kdporeq,
                        'kd_barang'         => $t->kdbr,
                        'kd_barangsys'      => $t->kdbsys,
                        'keterangan'        => $t->ket,
                        'kat_barang'        => $t->kat,
                        'tr_qty'            => $t->trqty,
                        'satuan'            => $t->satuan,
                        'tgl_transaksi'     => $now,
                        'inputer'           => $this->session->userdata('kode'),
                        'req_by'            => $t->kduser,
                        'create_at'         => $now,
                        'last_updated_by'   => $this->session->userdata('kode')
                    );
                    $this->M_Reqpic->input_tr($dataconfirm);
                    if ($this->M_StockLifo->schema_ready()) {
                        $this->M_StockLifo->allocate_new_outgoing((int) $this->db->insert_id());
                    }
                }
                redirect('reqpic/detreqbarangpic/' . $kdponk);
            }
        }
        redirect('reqpic/detreqbarangpic/' . $kdponk);
    }

    public function po_nk_req_revisi_note()
    {
        $kdponk     = $this->input->post('kodeponk');
        if (!$this->require_purchasing_request_access($kdponk)) {
            return;
        }
        $isinote    = $this->input->post('porevisi');
        $kodeuser   = $this->session->userdata('kode');
        $nmuser     = $this->session->userdata('nama_user');

        $editeddata = array(
            'status'    => 'PO REVISI'
        );

        $noterevisi = array(
            'kd_po'         => $kdponk,
            'isi_note'      => 'PO REVISI - ' . $isinote,
            'kd_user'       => $kodeuser,
            'nama_user'     => $nmuser,
            'note_for'      => '2',
            'update_status' => '2',
        );

        $this->M_Purchase->addNote($noterevisi);
        $this->M_Reqpic->updated_req_po_nk($kdponk, $editeddata);
        redirect('reqpic');
    }

    public function updated_po_nk($kdpo)
    {
        $kodeuser   = $this->session->userdata('kode');
        $nmuser     = $this->session->userdata('nama_user');

        $data = array(
            'status'    => 'ON PROGRESS'
        );

        $noterevisi = array(
            'kd_po'         => $kdpo,
            'isi_note'      => 'PO REPOST - ' . $nmuser,
            'kd_user'       => $kodeuser,
            'nama_user'     => $nmuser,
            'note_for'      => '2',
            'update_status' => '2',
        );

        $this->M_Purchase->addNote($noterevisi);
        $this->M_Reqpic->updated_req_po_nk($kdpo, $data);
        redirect('reqpic');
    }

    public function updated_det_req_po_nk()
    {
        $kdpo   = $this->input->post('kodebarang');
        $idpo   = $this->input->post('idbarang');
        $action = $this->input->post('action');

        switch ($action) {
            case 'edited':

                $keterangan = $this->input->post('keterangan');
                $qty        = $this->input->post('qtyisi');

                $data   = array(
                    'keterangan'    => $keterangan,
                    'qty'           => $qty
                );

                $this->M_Reqpic->det_updated_req_po_nk($idpo, $data);
                redirect('reqpic/detreqbarangpic/' . $kdpo);
                break;
            case 'delete':
                $this->M_Reqpic->det_delete_req_po_nk($idpo);
                redirect('reqpic/detreqbarangpic/' . $kdpo);
                break;
        }
    }

    public function admrestock()
    {
        $kduser = $this->session->userdata('kode');

        $data['title']      = 'PO Request By PIC ';

        $data['tmpreq']     = $this->M_Reqpic->getalltmpreq($kduser)->result();

        $data['countreq']   = $this->M_Reqpic->countRequser('1', $kduser);
        $data['generatekd'] = $this->M_Reqpic->kdnonkomersial();
        $data['jumlahbr']   = $this->M_Reqpic->countjmltmpbr($kduser);
        $data['getlistadm'] = $this->M_Reqpic->getlistadm($kduser)->result();

        $this->load->view('partial/header', $data);
        $this->load->view('partial/sidebar');
        $this->load->view('content/po/Reqpic/restockpo.php', $data);
        $this->load->view('partial/footer');
        $this->load->view('content/po/Reqpic/datatablesreq');
    }

    public function promosiseed()
    {
        $kduser = $this->session->userdata('kode');

        $data['title']      = 'PO Request By PIC ';

        $data['tmpreq']     = $this->M_Reqpic->getalltmpreq($kduser)->result();
        $data['getallreq']  = $this->M_Reqpic->getallreq_seed($kduser)->result();
        $data['countreq']   = $this->M_Reqpic->countRequser('1', $kduser);
        $data['generatekd'] = $this->M_Reqpic->kdnonkomersial();
        $data['jumlahbr']   = $this->M_Reqpic->countjmltmpbr($kduser);
        $data['getlistpic'] = $this->M_Reqpic->getlistpic()->result();

        $this->load->view('partial/header', $data);
        $this->load->view('partial/sidebar');
        $this->load->view('content/po/Reqpic/promosiseed.php', $data);
        $this->load->view('partial/footer');
        $this->load->view('content/po/Reqpic/datatablesreq');
    }

    public function promosicp()
    {
        $kduser = $this->session->userdata('kode');

        $data['title']      = 'PO Request By PIC ';

        $data['tmpreq']     = $this->M_Reqpic->getalltmpreq($kduser)->result();
        $data['getallreq']  = $this->M_Reqpic->getallreq_cp($kduser)->result();
        $data['countreq']   = $this->M_Reqpic->countRequser('1', $kduser);
        $data['generatekd'] = $this->M_Reqpic->kdnonkomersial();
        $data['jumlahbr']   = $this->M_Reqpic->countjmltmpbr($kduser);
        $data['getlistpic'] = $this->M_Reqpic->getlistpic()->result();

        $this->load->view('partial/header', $data);
        $this->load->view('partial/sidebar');
        $this->load->view('content/po/Reqpic/promosicp.php', $data);
        $this->load->view('partial/footer');
        $this->load->view('content/po/Reqpic/datatablesreq');
    }

    public function add_promosi_cp($kduser)
    {
        date_default_timezone_set("Asia/Jakarta");
        $nmuser = $this->session->userdata('nama_user');
        $dep    = $this->session->userdata('departemen');
        $kdus   = $this->session->userdata('kode');
        $kdponk = $this->input->post('kdponk');
        $totbr  = $this->input->post('totbr');
        $tjuan  = $this->input->post('intj');

        $now    = date('Y-m-d');

        $tmp    = $this->M_Reqpic->get_tmp_non_komersil($kduser);

        $inpdataponk = array(
            'jns_po'        => '2',
            'kd_po_nk'      => $kdponk,
            'kd_user'       => $kduser,
            'nm_user'       => $nmuser,
            'tgl_transaksi' => $now,
            'jml_item'      => $totbr,
            'status'        => 'ON PROGRESS',
            'departemen'    => 'PROMOSI CP',
            'tj_pembelian'  => $tjuan
        );
        $this->M_Reqpic->inputreq($inpdataponk);

        $generatekd = array(
            'kd_barang' => $kdponk
        );
        $this->M_Reqpic->generatekdponk($generatekd);

        $inputnt    = array(
            'kd_po'         => $kdponk,
            'isi_note'      => $tjuan,
            'kd_user'       => $kdus,
            'nama_user'     => $nmuser,
            'note_for'      => '2',
            'update_status' => '2',

        );
        $this->M_Purchase->addNote($inputnt);

        if ($tmp) {
            foreach ($tmp as $t) {
                $listdetreq = array(
                    'kd_po_nk'          => $kdponk,
                    'kd_user'           => $kduser,
                    'tgl_transaksi'     => $now,
                    'kd_bsys'           => $t->kd_bsys,
                    'kd_barang'         => $t->kd_barang,
                    'nama_barang'       => $t->nama_barang,
                    'deskripsi'         => $t->deskripsi,
                    'keterangan'        => $t->keterangan,
                    'qty'               => $t->qty,
                    'satuan'            => $t->satuan,
                    'kat_barang'        => $t->kat_barang,
                    'status'            => '0'
                );
                $this->M_Reqpic->input_detail_po_nk($listdetreq);
            }
        }
        $this->M_Reqpic->hapus_tmp_nk($kduser);
        redirect('promosi_cp');
    }

    public function list_barang_ready_cp()
    {
        $data['title']      = 'List Barang PO';
        $data['lstock']     = $this->M_Reqpic->getlistnkreq()->result();
        $data['satuan']     = $this->M_Reqpic->getsatuan()->result();

        $this->load->view('partial/header', $data);
        $this->load->view('partial/sidebar');
        $this->load->view('content/po/Reqpic/listbarang_cp', $data);
        $this->load->view('partial/footer');
        $this->load->view('content/po/datatables');
    }
}
