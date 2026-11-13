<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 *
 */
class C_Stocknonkomersil extends CI_Controller

{
    private function can_manage_lifo()
    {
        return in_array((string) $this->session->userdata('lv'), array('1', '2'), true)
            || in_array((string) $this->session->userdata('kode'), array('KEU02', 'KEU09'), true);
    }

    function __construct()
    {
        parent::__construct();
        $this->load->model('stock/M_Stocknonkomersil');
        $this->load->model('stock/M_StockLifo');
        $this->load->model('PO/M_Postatus');
        $this->load->model('PO/M_Purchase');
        $this->load->library('form_validation');
        $this->load->library('pagination');
    }

    public function index()
    {
        $lokasi = trim((string)$this->input->get('lokasi', true));

        $data['title'] = 'List Stock Non Komersil';
        $data['selected_lokasi'] = $lokasi;
        $data['lokasi_option'] = $this->M_Stocknonkomersil->get_master_lokasi();
        $data['lokasi_option_modal'] = $data['lokasi_option'];
        $data['stocknk'] = array();

        $this->load->view('partial/header', $data);
        $this->load->view('partial/sidebar');
        $this->load->view('content/stock/nonkomersil/body.php', $data);
        $this->load->view('partial/footer');
        $this->load->view('content/stock/nonkomersil/datatables');
    }

    public function list_stock_non_komersil_po()
    {
        $data['title'] = 'list stock tersedia';
        $data['stocknk'] = $this->M_Stocknonkomersil->getallbarang()->result();
        foreach ($data['stocknk'] as $stock) {
            $lastHarga = $this->M_Postatus->get_last_harga_barang_nk($stock->kd_br_adm, $stock->kd_barang);
            $stock->harga_lifo = $lastHarga ? (float) $lastHarga->hrg_satuan : 0;
            $stock->harga_lifo_po = $lastHarga ? $lastHarga->kd_po_nk : '';
        }
        $data['satuan'] = $this->M_Stocknonkomersil->getSatuan();

        $this->load->view('partial/header', $data);
        $this->load->view('partial/sidebar');
        $this->load->view('content\stock\nonkomersil\list_stock_po_nonkomersil.php', $data);
        $this->load->view('partial/footer');
        $this->load->view('content/stock/nonkomersil/datatables');
    }

    public function tmp_add_barang_komersil()
    {
        $kdbarang   = $this->input->post('kd_isi');
        $namabarang = $this->input->post('nama_isi');
        $descbarang  = $this->input->post('desc_isi');
        $ketbarang  = $this->input->post('ket_isi');
        $qtybarang  = $this->input->post('qty_isi');
        $hrgsatuan  = $this->input->post('hrg_isi');
        $kduser     = $this->session->userdata('kode');
        $totalharga = $qtybarang * $hrgsatuan;

        $dataBarang = array(
            'nama_barang'   => $namabarang,
            'deskripsi'     => $descbarang,
            'keterangan'    => $ketbarang,
            'qty'           => $qtybarang,
            'hrg_satuan'    => $hrgsatuan,
            'total_harga'   => $totalharga,
            'kd_barang'     => $kdbarang,
            'kd_user'       => $kduser,
            'gbr_produk'    => 'Karisma.png'
        );
        $kdgenerate = array(
            'kd_barang' => $kdbarang
        );

        $this->M_Purchase->generatekd($kdgenerate);
        $this->M_Purchase->input_tmp_nk($dataBarang);

        redirect('pononkomersil');
    }
    public function detailtransaksi($kdbarang)
    {
        $data['title']      = 'Detail Stock Barang';
        $data['kdgenerate'] = $this->M_Stocknonkomersil->getgeneratekd();
        $data['item']       = $this->M_Stocknonkomersil->get_data_item($kdbarang)->result();
        $data['stock']      = $this->get_transaction_page($kdbarang);
        $data['note']       = $this->M_Stocknonkomersil->get_note($kdbarang);
        $data['trash']      = $this->M_Stocknonkomersil->get_data_trash($kdbarang)->result();
        $data['lifo_summary'] = $this->M_StockLifo->get_summary($kdbarang);
        $data['lifo_batches'] = $this->M_StockLifo->get_active_batches($kdbarang);
        $historyPerPage = 5;
        $historyTotal = $this->M_StockLifo->count_batch_history_lifo($kdbarang);
        $historyPage = max(1, (int) $this->input->get('history_page'));
        $historyLastPage = max(1, (int) ceil($historyTotal / $historyPerPage));
        if ($historyPage > $historyLastPage) {
            $historyPage = $historyLastPage;
        }
        $data['lifo_batch_history'] = $this->M_StockLifo->get_batch_history_lifo($kdbarang, $historyPerPage, ($historyPage - 1) * $historyPerPage);
        $data['history_total'] = $historyTotal;
        $data['history_page'] = $historyPage;
        $data['history_last_page'] = $historyLastPage;
        $data['history_tab_active'] = $this->input->get('history_page') !== null;
        $data['lifo_last_price'] = $this->M_StockLifo->get_latest_price($kdbarang);
        $data['can_manage_lifo_price'] = $this->can_manage_lifo();

        $this->load->view('partial/header', $data);
        $this->load->view('partial/sidebar');
        $this->load->view('content/stock/nonkomersil/detail_itm_tr.php', $data);
        $this->load->view('partial/footer');
        $this->load->view('content/stock/nonkomersil/datatables');
    }

    public function trash_transaksi($key, $id)
    {
        $action = $key;

        switch ($action) {
                // 1 = delete transaksi
                // 2 = redo transaksi
            case '1':
                $item_tr = $this->M_Stocknonkomersil->get_data_itemtr($id)->result();
                if ($item_tr) {
                    foreach ($item_tr as $t) {
                        $trashdata = array(
                            'kd_akun'           => $t->kd_akun,
                            'kd_po_nk'          => $t->kd_po_nk,
                            'kd_barang'         => $t->kd_barang,
                            'kd_barangsys'      => $t->kd_barangsys,
                            'keterangan'        => $t->keterangan,
                            'kat_barang'        => $t->kat_barang,
                            'tr_qty'            => $t->tr_qty,
                            'satuan'            => $t->satuan,
                            'inputer'           => $t->inputer,
                            'req_by'            => $t->req_by,
                            'tgl_transaksi'     => $t->tgl_transaksi,
                            'create_at'         => $t->create_at,
                            'last_updated_by'   => $this->session->userdata('kode')
                        );
                        $this->M_Stocknonkomersil->insert_trash_bin_tr($trashdata);
                        $this->M_Stocknonkomersil->deldettransaksi($id);
                        redirect('detailtransaksi/' . $t->kd_barang);
                    }
                }
                break;
            case '2':
                $item_trs = $this->M_Stocknonkomersil->get_data_trashid($id)->result();
                if ($item_trs) {
                    foreach ($item_trs as $t) {
                        $trashdata = array(
                            'kd_akun'           => $t->kd_akun,
                            'kd_po_nk'          => $t->kd_po_nk,
                            'kd_barang'         => $t->kd_barang,
                            'kd_barangsys'      => $t->kd_barangsys,
                            'keterangan'        => $t->keterangan,
                            'kat_barang'        => $t->kat_barang,
                            'tr_qty'            => $t->tr_qty,
                            'satuan'            => $t->satuan,
                            'inputer'           => $t->inputer,
                            'req_by'            => $t->req_by,
                            'tgl_transaksi'     => $t->tgl_transaksi,
                            'create_at'         => $t->create_at,
                            'last_updated_by'   => $this->session->userdata('kode')
                        );
                        $this->M_Stocknonkomersil->insttransaksi($trashdata);
                        $this->M_Stocknonkomersil->delete_trash($id);
                        redirect('detailtransaksi/' . $t->kd_barang);
                    }
                }
                break;
        }
    }

    public function revisitr($akun, $kdpo, $kdbr)
    {
        $data['title']      = 'Revisi Qty Persedian';
        if ($akun == '11511') {
            $data['stockdet']   = $this->M_Stocknonkomersil->get_detail_br_rev_buy($kdpo, $kdbr)->result();
        } else {
            $data['stockdet']   = $this->M_Stocknonkomersil->get_detail_br_rev_req($kdpo, $kdbr)->result();
        }


        $this->load->view('partial/header', $data);
        $this->load->view('partial/sidebar');
        $this->load->view('content/stock/nonkomersil/revisitr', $data);
        $this->load->view('partial/footer');
        $this->load->view('content/stock/nonkomersil/datatables');
    }
    public function adjustmenqty()
    {
        date_default_timezone_set("Asia/Jakarta");
        $adjustmentkd   = $this->input->post('adjustmentkd');
        $kdbrsistem     = $this->input->post('kdbrsistem');
        $kdbarang       = $this->input->post('kdbarang');
        $katbarang      = $this->input->post('katbarang');
        $kdakun         = $this->input->post('kdakun');
        $satuanid       = $this->input->post('satuanid');
        $adjqty         = $this->input->post('adjqty');
        $ket            = $this->input->post('ket_isi');
        $now            = date('Y-m-d H:i:s');
        $now1           = date('Y-m-d');

        if (!in_array($kdakun, array('11513', '11514'), true) || (float) $adjqty <= 0) {
            $this->session->set_flashdata('error', 'Kode akun adjustment dan qty positif wajib diisi.');
            redirect('detailtransaksi/' . $kdbrsistem);
            return;
        }
        if ($kdakun === '11514' && $this->M_StockLifo->schema_ready()) {
            $availability = $this->M_StockLifo->can_allocate_outgoing($kdbrsistem, $adjqty);
            if (!$availability['allowed']) {
                $this->session->set_flashdata('error', $availability['message'] . ' Lengkapi harga batch pada Detail Transaksi terlebih dahulu.');
                redirect('detailtransaksi/' . $kdbrsistem);
                return;
            }
        }

        $generatekd         = array(
            'kd_barang'     => $adjustmentkd
        );

        $insrtadjustment    = array(
            'kd_akun'           => $kdakun,
            'kd_po_nk'          => $adjustmentkd,
            'kd_barang'         => $kdbrsistem,
            'kd_barangsys'      => $kdbarang,
            'keterangan'        => $ket,
            'kat_barang'        => $katbarang,
            'tr_qty'            => $adjqty,
            'satuan'            => $satuanid,
            'inputer'           => $this->session->userdata('kode'),
            'req_by'            => '-',
            'tgl_transaksi'     => $now1,
            'create_at'         => $now,
            'last_updated_by'   => $this->session->userdata('kode'),
            'update_at'         => $now
        );

        $this->db->trans_begin();
        $this->M_Stocknonkomersil->generatekd($generatekd);
        $this->M_Stocknonkomersil->insttransaksi($insrtadjustment);
        $transactionId = (int) $this->db->insert_id();
        if ($this->M_StockLifo->schema_ready()) {
            $lifoResult = $kdakun === '11513'
                ? $this->M_StockLifo->register_incoming_transaction($transactionId, $this->session->userdata('kode'))
                : $this->M_StockLifo->allocate_new_outgoing($transactionId);
            if (!$lifoResult['success']) {
                $this->db->trans_rollback();
                $this->session->set_flashdata('error', $lifoResult['message']);
                redirect('detailtransaksi/' . $kdbrsistem);
                return;
            }
        }
        $this->db->trans_commit();

        if ($kdakun == '11513') {
            $inputnote = array(
                'kd_po'         => $kdbrsistem,
                'isi_note'      => 'ADJUSTMENT PENAMBAHAN QTY - ' . $ket,
                'kd_user'       => $this->session->userdata('kode'),
                'nama_user'     => $this->session->userdata('nama_user'),
                'note_for'      => '3',
                'update_status' => '3',
                'create_at'     => $now
            );
            $this->M_Stocknonkomersil->insrt_note($inputnote);
            redirect('detailtransaksi/' . $kdbrsistem);
        } else if ($kdakun == '11514') {
            $inputnote = array(
                'kd_po'         => $kdbrsistem,
                'isi_note'      => 'ADJUSTMENT PENGURANGAN QTY - ' . $ket,
                'kd_user'       => $this->session->userdata('kode'),
                'nama_user'     => $this->session->userdata('nama_user'),
                'note_for'      => '3',
                'update_status' => '3',
                'create_at'     => $now
            );
            $this->M_Stocknonkomersil->insrt_note($inputnote);
            redirect('detailtransaksi/' . $kdbrsistem);
        }
    }

    public function save_lifo_price()
    {
        $kodeBarang = $this->input->post('kd_barang', true);
        if (!$this->can_manage_lifo()) {
            show_error('Anda tidak berwenang mengubah harga batch LIFO.', 403);
            return;
        }
        if (!$this->M_StockLifo->schema_ready()) {
            $this->session->set_flashdata('error', 'Schema LIFO belum tersedia.');
            redirect('detailtransaksi/' . $kodeBarang);
            return;
        }
        $result = $this->M_StockLifo->set_manual_price(
            (int) $this->input->post('id_batch'), $this->input->post('harga_satuan'),
            $this->input->post('alasan', true), $this->session->userdata('kode')
        );
        $this->session->set_flashdata($result['success'] ? 'success' : 'error', $result['message']);
        redirect('detailtransaksi/' . $kodeBarang);
    }
    public function nkrestok()
    {
        $data['title']      = 'List Stock Non Komersil';
        $data['vstock']     = $this->M_Stocknonkomersil->v_stockzero()->result();
        $data['draftpo']    = $this->M_Stocknonkomersil->draftpo()->result();

        $this->load->view('partial/header', $data);
        $this->load->view('partial/sidebar');
        $this->load->view('content/stock/nonkomersil/listnostock.php', $data);
        $this->load->view('partial/footer');
        $this->load->view('content/stock/nonkomersil/datatables');
    }
    public function indraftrestock()
    {
        $kduser     = $this->session->userdata('kode');
        $kdbsys     = $this->input->post('kdbys');
        $kdbr       = $this->input->post('kdbr');
        $katbarang  = $this->input->post('katbr');
        $nmbarang   = $this->input->post('nm_barang');
        $descnk     = $this->input->post('descnk_isi');
        $ket        = $this->input->post('ket_isi');
        $qty        = $this->input->post('qty_isi');
        $satuan     = $this->input->post('satqty');
        $hrgsatuan  = $this->input->post('hrgsat');

        $hrgtot     = $qty * $hrgsatuan;

        $inputdt    = array(
            'jnis_po'       => '3',
            'nama_barang'   => $nmbarang,
            'deskripsi'     => $descnk,
            'keterangan'    => $ket,
            'qty'           => $qty,
            'satuan'        => $satuan,
            'hrg_satuan'    => $hrgsatuan,
            'total_harga'   => $hrgtot,
            'kd_bsys'       => $kdbsys,
            'kd_barang'     => $kdbr,
            'kat_barang'    => $katbarang,
            'kd_user'       => $kduser
        );
        $this->M_Stocknonkomersil->inputtmprestock($inputdt);
        redirect('nkrestok');
    }

    public function filterqtybytgl()
    {
        // POST is used by the search form, while GET keeps the filter intact
        // when a pagination link is opened.
        $start_date = $this->input->post('start_date', true) ?: $this->input->get('start_date', true);
        $end_date = $this->input->post('end_date', true) ?: $this->input->get('end_date', true);
        $kdbarang = $this->input->post('kdbarang', true) ?: $this->input->get('kdbarang', true);

        $data['title']      = 'Detail Stock Barang By Tanggal';
        $data['start_date'] = $start_date;
        $data['end_date']   = $end_date;

        $data['item']       = $this->M_Stocknonkomersil->get_item_bytgl($start_date, $end_date, $kdbarang)->result();
        $data['note']       = $this->M_Stocknonkomersil->get_note($kdbarang);
        $data['stock']      = $this->get_transaction_page($kdbarang, $start_date, $end_date);

        $this->load->view('partial/header', $data);
        $this->load->view('partial/sidebar');
        $this->load->view('content/stock/nonkomersil/stock_detailitm', $data);
        $this->load->view('partial/footer');
        $this->load->view('content/stock/nonkomersil/datatables');
    }

    /**
     * Fetch and configure a ten-row transaction history page.
     */
    private function get_transaction_page($kdbarang, $start_date = null, $end_date = null)
    {
        $per_page = 10;
        $offset = max(0, (int) $this->input->get('per_page'));
        $total_rows = $this->M_Stocknonkomersil->count_detail_transaksi_itm($kdbarang, $start_date, $end_date);

        if ($offset >= $total_rows && $total_rows > 0) {
            $offset = (int) (floor(($total_rows - 1) / $per_page) * $per_page);
        }

        $config = array(
            'base_url' => $start_date === null
                ? base_url('detailtransaksi/' . rawurlencode($kdbarang))
                : base_url('stock/filterqtybytgl'),
            'total_rows' => $total_rows,
            'per_page' => $per_page,
            'page_query_string' => true,
            'query_string_segment' => 'per_page',
            'reuse_query_string' => true,
            'full_tag_open' => '<nav aria-label="Navigasi halaman"><ul class="pagination justify-content-center">',
            'full_tag_close' => '</ul></nav>',
            'attributes' => array('class' => 'page-link'),
            'num_tag_open' => '<li class="page-item">',
            'num_tag_close' => '</li>',
            'cur_tag_open' => '<li class="page-item active" aria-current="page"><span class="page-link">',
            'cur_tag_close' => '</span></li>',
            'next_tag_open' => '<li class="page-item">',
            'next_tag_close' => '</li>',
            'prev_tag_open' => '<li class="page-item">',
            'prev_tag_close' => '</li>',
            'first_tag_open' => '<li class="page-item">',
            'first_tag_close' => '</li>',
            'last_tag_open' => '<li class="page-item">',
            'last_tag_close' => '</li>',
        );

        if ($start_date !== null) {
            // Make POST-submitted filter values available to CI Pagination's
            // reuse_query_string option for the first page as well.
            $_GET['start_date'] = $start_date;
            $_GET['end_date'] = $end_date;
            $_GET['kdbarang'] = $kdbarang;
        }

        $this->pagination->initialize($config);
        $this->load->vars(array(
            'pagination' => $this->pagination->create_links(),
            'transaction_total' => $total_rows,
        ));

        return $this->M_Stocknonkomersil->get_detail_transaksi_itm($kdbarang, $per_page, $offset, $start_date, $end_date)->result();
    }
    public function tr_allstock()
    {
        $data['title']      = 'List Transaksi Allstock';

        $this->load->view('partial/header', $data);
        $this->load->view('partial/sidebar');
        $this->load->view('content/stock/nonkomersil/histori_stock_all_ponk', $data);
        $this->load->view('partial/footer');
    }

    public function fetch_tracking_data()
    {
        $tgl1 = $this->input->get('tanggal_1');
        $tgl2 = $this->input->get('tanggal_2');

        $data = $this->M_Stocknonkomersil->get_filtered_data($tgl1, $tgl2);
        echo json_encode($data);
    }

    public function master_lokasi()
    {
        $data['title'] = 'Master Lokasi';
        $data['lokasi'] = $this->M_Stocknonkomersil->get_master_lokasi();

        $this->load->view('partial/header', $data);
        $this->load->view('partial/sidebar');
        $this->load->view('content/stock/nonkomersil/master_lokasi', $data);
        $this->load->view('partial/footer');
        $this->load->view('content/stock/nonkomersil/datatables');
    }

    public function ajax_stocknonkomersil()
    {
        $lokasi = trim((string)$this->input->get('lokasi', true));
        $status_stock = trim((string)$this->input->get('status_stock', true));
        $draw = $this->input->get('draw');

        if ($draw !== null) {
            $order = $this->input->get('order');
            $search = $this->input->get('search');
            $length = (int)$this->input->get('length');

            $params = [
                'lokasi' => $lokasi,
                'status_stock' => $status_stock,
                'search' => is_array($search) && isset($search['value']) ? trim((string)$search['value']) : '',
                'start' => max(0, (int)$this->input->get('start')),
                'length' => ($length > 0 && $length <= 100) ? $length : 10,
                'order_column' => is_array($order) && isset($order[0]['column']) ? (int)$order[0]['column'] : 0,
                'order_dir' => is_array($order) && isset($order[0]['dir']) ? (string)$order[0]['dir'] : 'asc',
                'lifo_view' => $this->can_manage_lifo()
            ];

            $result = $this->M_Stocknonkomersil->get_stock_datatable($params);
            $this->output
                ->set_content_type('application/json')
                ->set_output(json_encode([
                    'draw' => (int)$draw,
                    'recordsTotal' => $result['records_total'],
                    'recordsFiltered' => $result['records_filtered'],
                    'data' => $result['data']
                ]));
            return;
        }

        $stock = $this->M_Stocknonkomersil->v_stock($lokasi, $status_stock);
        $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode([
                'status' => true,
                'data' => $stock
            ]));
    }

    public function update_minimum_stock()
    {
        $level = (string)$this->session->userdata('lv');
        if ($level !== '1' && $level !== '2') {
            $this->output
                ->set_status_header(403)
                ->set_content_type('application/json')
                ->set_output(json_encode(['status' => false]));
            return;
        }

        $kode_barang = trim((string)$this->input->post('kode_barang', true));
        $minimum_stock = (float)$this->input->post('minimum_stock');

        $updated = false;
        if ($kode_barang !== '' && $minimum_stock >= 0) {
            $updated = $this->M_Stocknonkomersil->update_minimum_stock($kode_barang, $minimum_stock);
        }

        $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode([
                'status' => (bool)$updated
            ]));
    }

    public function update_lokasi_barang()
    {
        $kode_barang = trim((string)$this->input->post('kode_barang', true));
        $id_lokasi = (int)$this->input->post('id_lokasi');

        $updated = false;
        if ($kode_barang !== '' && $id_lokasi > 0) {
            $updated = $this->M_Stocknonkomersil->update_lokasi_barang($kode_barang, $id_lokasi);
        }

        if ($this->input->is_ajax_request()) {
            $this->output
                ->set_content_type('application/json')
                ->set_output(json_encode([
                    'status' => (bool)$updated
                ]));
            return;
        }

        redirect('stocknonkomersil');
    }

    public function add_master_lokasi()
    {
        $nama_lokasi = trim($this->input->post('nama_lokasi'));
        if ($nama_lokasi !== '') {
            $this->M_Stocknonkomersil->add_master_lokasi([
                'nama_lokasi' => $nama_lokasi
            ]);
        }

        redirect('master_lokasi');
    }

    public function edit_master_lokasi()
    {
        $id_lokasi = $this->input->post('id_lokasi');
        $nama_lokasi = trim($this->input->post('nama_lokasi'));

        if ($id_lokasi && $nama_lokasi !== '') {
            $this->M_Stocknonkomersil->update_master_lokasi($id_lokasi, [
                'nama_lokasi' => $nama_lokasi
            ]);
        }

        redirect('master_lokasi');
    }

    public function hapus_master_lokasi($id)
    {
        $this->M_Stocknonkomersil->delete_master_lokasi($id);
        redirect('master_lokasi');
    }

    private function parseStockNumber($value)
    {
        $value = trim((string) $value);
        $value = str_replace(' ', '', $value);

        if (strpos($value, ',') !== false) {
            $value = str_replace('.', '', $value);
            $value = str_replace(',', '.', $value);
        }

        return (float) $value;
    }

    public function stock_opname()
    {
        if (!in_array((string) $this->session->userdata('lv'), array('1', '2'), true)) {
            show_404();
            return;
        }

        $data['title'] = 'Stock Opname Non Komersil';
        $data['stock'] = $this->M_Stocknonkomersil->v_stock();
        $data['opname'] = $this->M_Stocknonkomersil->get_stock_opname_headers();
        $data['tables_ready'] = $this->db->table_exists('tbpo_stock_opname_nk') && $this->db->table_exists('tbpo_stock_opname_nk_detail');

        $this->load->view('partial/header', $data);
        $this->load->view('partial/sidebar');
        $this->load->view('content/stock/nonkomersil/stock_opname', $data);
        $this->load->view('partial/footer');
        $this->load->view('content/stock/nonkomersil/datatables');
    }

    public function save_stock_opname()
    {
        if (!in_array((string) $this->session->userdata('lv'), array('1', '2'), true)) {
            show_404();
            return;
        }

        if (!$this->db->table_exists('tbpo_stock_opname_nk') || !$this->db->table_exists('tbpo_stock_opname_nk_detail')) {
            $this->session->set_flashdata('error', 'Tabel stock opname belum tersedia. Jalankan migration stock opname PO Non Komersil terlebih dahulu.');
            redirect('stockopnamenk');
            return;
        }

        date_default_timezone_set("Asia/Jakarta");
        $kodeBarang = $this->input->post('kode_barang');
        $kodeBarangSys = $this->input->post('kode_barangs');
        $namaBarang = $this->input->post('nama_barang');
        $qtySistem = $this->input->post('qty_sistem');
        $qtyFisik = $this->input->post('qty_fisik');
        $satuan = $this->input->post('satuan');
        $idSatuan = $this->input->post('id_satuan');
        $katBarang = $this->input->post('kat_barang');
        $keterangan = $this->input->post('keterangan_detail');
        $tglOpname = $this->input->post('tgl_opname', true);
        $catatan = trim((string) $this->input->post('catatan', true));
        $now = date('Y-m-d H:i:s');
        $tglTransaksi = $tglOpname ?: date('Y-m-d');

        $details = array();
        $transactions = array();
        $totalItem = 0;
        $totalSelisihPlus = 0;
        $totalSelisihMinus = 0;

        foreach ((array) $kodeBarang as $idx => $kode) {
            $kode = trim((string) $kode);
            if ($kode === '') {
                continue;
            }

            $sistem = $this->parseStockNumber(isset($qtySistem[$idx]) ? $qtySistem[$idx] : 0);
            $rawFisik = isset($qtyFisik[$idx]) ? trim((string) $qtyFisik[$idx]) : '';
            $fisik = $rawFisik === '' ? $sistem : $this->parseStockNumber($rawFisik);
            if ($fisik < 0) {
                continue;
            }

            $selisih = $fisik - $sistem;
            $kodeSys = isset($kodeBarangSys[$idx]) ? $kodeBarangSys[$idx] : '';

            $details[] = array(
                'kode_barang' => $kode,
                'kode_barangs' => $kodeSys,
                'nama_barang' => isset($namaBarang[$idx]) ? $namaBarang[$idx] : '',
                'qty_sistem' => $sistem,
                'qty_fisik' => $fisik,
                'selisih' => $selisih,
                'satuan' => isset($satuan[$idx]) ? $satuan[$idx] : '',
                'keterangan' => isset($keterangan[$idx]) ? $keterangan[$idx] : ''
            );

            if ($selisih != 0) {
                $transactions[] = array(
                    'kd_akun' => $selisih > 0 ? '11513' : '11514',
                    'kd_barang' => $kodeSys,
                    'kd_barangsys' => $kode,
                    'keterangan' => 'STOCK OPNAME - ' . (isset($keterangan[$idx]) && trim($keterangan[$idx]) !== '' ? $keterangan[$idx] : $catatan),
                    'kat_barang' => isset($katBarang[$idx]) ? $katBarang[$idx] : '',
                    'tr_qty' => abs($selisih),
                    'satuan' => isset($idSatuan[$idx]) ? $idSatuan[$idx] : '',
                    'inputer' => $this->session->userdata('kode'),
                    'req_by' => 'STOCK OPNAME',
                    'tgl_transaksi' => $tglTransaksi,
                    'create_at' => $now,
                    'last_updated_by' => $this->session->userdata('kode'),
                    'update_at' => $now
                );

                if ($selisih > 0) {
                    $totalSelisihPlus += $selisih;
                } else {
                    $totalSelisihMinus += abs($selisih);
                }
            }

            $totalItem++;
        }

        if (empty($details)) {
            $this->session->set_flashdata('error', 'Tidak ada data stock opname yang dapat disimpan.');
            redirect('stockopnamenk');
            return;
        }

        $header = array(
            'tgl_opname' => $tglTransaksi,
            'catatan' => $catatan,
            'total_item' => $totalItem,
            'total_selisih_plus' => $totalSelisihPlus,
            'total_selisih_minus' => $totalSelisihMinus,
            'created_by' => $this->session->userdata('kode'),
            'created_name' => $this->session->userdata('nama_user'),
            'created_at' => $now
        );

        $idOpname = $this->M_Stocknonkomersil->insert_stock_opname($header, $details, $transactions);
        if (!$idOpname) {
            $this->session->set_flashdata('error', 'Stock opname gagal disimpan.');
            redirect('stockopnamenk');
            return;
        }

        $this->session->set_flashdata('success', 'Stock opname berhasil disimpan.');
        redirect('stockopnamenk/detail/' . $idOpname);
    }

    public function detail_stock_opname($id)
    {
        if (!in_array((string) $this->session->userdata('lv'), array('1', '2'), true)) {
            show_404();
            return;
        }

        $data['title'] = 'Detail Stock Opname Non Komersil';
        $data['header'] = $this->M_Stocknonkomersil->get_stock_opname_header($id);
        $data['detail'] = $this->M_Stocknonkomersil->get_stock_opname_detail($id);

        if (!$data['header']) {
            show_404();
            return;
        }

        $this->load->view('partial/header', $data);
        $this->load->view('partial/sidebar');
        $this->load->view('content/stock/nonkomersil/stock_opname_detail', $data);
        $this->load->view('partial/footer');
        $this->load->view('content/stock/nonkomersil/datatables');
    }
}
