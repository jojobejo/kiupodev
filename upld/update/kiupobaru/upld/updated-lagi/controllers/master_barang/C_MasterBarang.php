<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 *
 */
class C_MasterBarang extends CI_Controller

{
    function __construct()
    {
        parent::__construct();
        $this->load->model('Master_barang/M_MasterBarang');
        $this->load->library('form_validation');
    }

    public function index()
    {
        $data['title']      = 'Master Barang';
        $data['barangnk']   = $this->M_MasterBarang->get_all_masterbarang()->result();
        $data['kdbarang']   = $this->M_MasterBarang->generatekdbrnk();
        $data['kdqrcode']   = $this->M_MasterBarang->generate_qrcode();
        $data['katbarang']   = $this->M_MasterBarang->getkatbarang();
        $data['satuan']   = $this->M_MasterBarang->getsatuanbr();

        $this->load->view('partial/header', $data);
        $this->load->view('partial/sidebar');
        $this->load->view('content/mbarang/mbody', $data);
        $this->load->view('partial/footer');
        $this->load->view('content/mbarang/datatables');
    }
    public function masterbarangkomersil()
    {
        $data['title']      = 'Master Barang - Komersil';
        $data['supplier'] = $this->M_MasterBarang->getsuplierall()->result();
        $data['satuan'] = $this->M_MasterBarang->getsatuanbr();

        $this->load->view('partial/header', $data);
        $this->load->view('partial/sidebar');
        $this->load->view('content/mbarang/mbodyk', $data);
        $this->load->view('partial/footer');
        $this->load->view('content/mbarang/datatables');
    }

    public function detailmasterbarangkomersil($id = null)
    {
        $barang = $this->M_MasterBarang->get_masterbarang_komersil_by_id((int)$id);
        if (!$barang) {
            show_404();
            return;
        }

        $data['title'] = 'Detail Barang Komersil';
        $data['barang'] = $barang;
        $data['supplier'] = $this->M_MasterBarang->getsuplierall()->result();
        $data['satuan'] = $this->M_MasterBarang->getsatuanbr();

        $this->load->view('partial/header', $data);
        $this->load->view('partial/sidebar');
        $this->load->view('content/mbarang/detailbarangkomersil', $data);
        $this->load->view('partial/footer');
        $this->load->view('content/mbarang/datatables');
    }

    private function json_response($data, $statusCode = 200)
    {
        $this->output
            ->set_status_header($statusCode)
            ->set_content_type('application/json')
            ->set_output(json_encode($data));
    }

    private function can_manage_masterbarang()
    {
        $level = (string)$this->session->userdata('lv');
        return $level === '1' || $level === '2';
    }

    private function masterbarangkomersil_payload()
    {
        $panjang = max(0, (float)$this->input->post('panjang', true));
        $lebar = max(0, (float)$this->input->post('lebar', true));
        $tinggi = max(0, (float)$this->input->post('tinggi', true));
        $bahanAktif = trim((string)$this->input->post('bahan_aktif', true));
        $satuan = trim((string)$this->input->post('satuan', true));
        $satuanQty = trim((string)$this->input->post('satuan_qty', true));

        return array(
            'kode_barang' => trim((string)$this->input->post('kode_barang', true)),
            'kd_suplier' => trim((string)$this->input->post('kd_suplier', true)),
            'nama_barang' => trim((string)$this->input->post('nama_barang', true)),
            'bahan_aktif' => $bahanAktif,
            'bhn_aktif' => $bahanAktif,
            'satuan' => $satuan,
            'satuan_qty' => $satuanQty !== '' ? $satuanQty : $this->M_MasterBarang->get_satuan_id_by_name($satuan),
            'panjang' => $panjang,
            'lebar' => $lebar,
            'tinggi' => $tinggi,
            'hasil_dimensi' => $panjang * $lebar * $tinggi,
            'berat' => max(0, (float)$this->input->post('berat', true)),
            'isi' => max(0, (float)$this->input->post('isi', true)),
            'kemasan' => max(0, (float)$this->input->post('kemasan', true)),
            'stock_minimum' => max(0, (int)$this->input->post('stock_minimum', true)),
            'merk_barang' => trim((string)$this->input->post('merk_barang', true)),
            'kelompok_barang' => trim((string)$this->input->post('kelompok_barang', true)),
            'kategori_barang' => trim((string)$this->input->post('kategori_barang', true)),
            'produk_fokus' => trim((string)$this->input->post('produk_fokus', true)),
            'is_active' => $this->input->post('is_active', true) === 'F' ? 'F' : 'T',
            'is_lot' => $this->input->post('is_lot', true) === 'T' ? 'T' : 'F'
        );
    }

    private function validate_masterbarangkomersil_payload($payload, $excludeId = null)
    {
        if ($payload['kode_barang'] === '') {
            return 'Kode barang wajib diisi.';
        }

        if ($payload['kd_suplier'] === '') {
            return 'Supplier wajib dipilih.';
        }

        if ($payload['nama_barang'] === '') {
            return 'Nama barang wajib diisi.';
        }

        $existing = $this->M_MasterBarang->get_masterbarang_komersil_by_kode($payload['kode_barang'], $excludeId);
        if ($existing) {
            return 'Kode barang sudah digunakan.';
        }

        return '';
    }

    public function ajax_masterbarangkomersil()
    {
        $draw = $this->input->get('draw');
        $order = $this->input->get('order');
        $search = $this->input->get('search');
        $length = (int)$this->input->get('length');

        $params = array(
            'search' => is_array($search) && isset($search['value']) ? trim((string)$search['value']) : '',
            'start' => max(0, (int)$this->input->get('start')),
            'length' => ($length > 0 && $length <= 100) ? $length : 10,
            'order_column' => is_array($order) && isset($order[0]['column']) ? (int)$order[0]['column'] : 0,
            'order_dir' => is_array($order) && isset($order[0]['dir']) ? (string)$order[0]['dir'] : 'asc'
        );

        $result = $this->M_MasterBarang->get_masterbarang_komersil_datatable($params);

        $this->json_response(array(
            'draw' => (int)$draw,
            'recordsTotal' => $result['records_total'],
            'recordsFiltered' => $result['records_filtered'],
            'data' => $result['data']
        ));
    }

    public function ajax_get_masterbarangkomersil($id = null)
    {
        if (!$this->can_manage_masterbarang()) {
            $this->json_response(array('status' => false, 'message' => 'Akses ditolak.'), 403);
            return;
        }

        $barang = $this->M_MasterBarang->get_masterbarang_komersil_by_id((int)$id);
        if (!$barang) {
            $this->json_response(array('status' => false, 'message' => 'Barang tidak ditemukan.'), 404);
            return;
        }

        $this->json_response(array('status' => true, 'data' => $barang));
    }

    public function ajax_save_masterbarangkomersil()
    {
        if (!$this->can_manage_masterbarang()) {
            $this->json_response(array('status' => false, 'message' => 'Akses ditolak.'), 403);
            return;
        }

        $id = (int)$this->input->post('id_barang', true);
        $payload = $this->masterbarangkomersil_payload();
        $validationError = $this->validate_masterbarangkomersil_payload($payload, $id > 0 ? $id : null);
        if ($validationError !== '') {
            $this->json_response(array('status' => false, 'message' => $validationError), 422);
            return;
        }

        $saved = $id > 0
            ? $this->M_MasterBarang->update_masterbarang_komersil($id, $payload)
            : $this->M_MasterBarang->insert_masterbarang_komersil($payload);

        $this->json_response(array(
            'status' => (bool)$saved,
            'message' => $saved ? 'Data barang berhasil disimpan.' : 'Data barang gagal disimpan.'
        ), $saved ? 200 : 500);
    }

    public function ajax_delete_masterbarangkomersil()
    {
        if (!$this->can_manage_masterbarang()) {
            $this->json_response(array('status' => false, 'message' => 'Akses ditolak.'), 403);
            return;
        }

        $id = (int)$this->input->post('id_barang', true);
        if ($id <= 0) {
            $this->json_response(array('status' => false, 'message' => 'ID barang tidak valid.'), 422);
            return;
        }

        $deleted = $this->M_MasterBarang->hapusBarang($id);
        $this->json_response(array(
            'status' => (bool)$deleted,
            'message' => $deleted ? 'Data barang berhasil dihapus.' : 'Data barang gagal dihapus.'
        ), $deleted ? 200 : 500);
    }
    public function addrequestmasterbarang()
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

        $this->M_MasterBarang->insertTmpmbarang($tmpreqbarang);

        redirect('pononkomersil/list_stocknkpo');
    }
    public function addrequestmasterbarangs()
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

        $this->M_MasterBarang->insertTmpmbarang($tmpreqbarang);

        redirect('vrequestmbarang');
    }

    public function vrequestmbarang()
    {
        $data['title']          = 'Request - Master Barang';

        $kdu    = $this->session->userdata('kode');

        $data['lreqmbarang']    = $this->M_MasterBarang->get_all_req_barang()->result();
        $data['listrebrpic']    = $this->M_MasterBarang->get_all_req_barang_pic($kdu)->result();
        $data['listreqbr']      = $this->M_MasterBarang->get_all_req_barang()->result();
        $data['kdbarang']       = $this->M_MasterBarang->generatekdbrnk();
        $data['kdqrcode']       = $this->M_MasterBarang->generate_qrcode();
        $data['katbarang']      = $this->M_MasterBarang->getkatbarang();
        $data['satuan']         = $this->M_MasterBarang->getsatuanbr();
        $data['pojasa_usulan']  = $this->db->table_exists('tbpo_jasa_material_usulan') ? $this->M_MasterBarang->get_pojasa_material_usulan() : array();

        $this->load->view('partial/header', $data);
        $this->load->view('partial/sidebar');
        $this->load->view('content/mbarang/vreqmbarang', $data);
        $this->load->view('partial/footer');
        $this->load->view('content/mbarang/datatables');
    }

    public function keputusan_usulan_pojasa()
    {
        if (!$this->can_manage_masterbarang() || !$this->db->table_exists('tbpo_jasa_material_usulan')) { show_error('Akses ditolak.', 403); return; }
        $id = (int) $this->input->post('id_usulan_barang', true);
        $action = strtoupper(trim((string) $this->input->post('action', true)));
        $note = trim((string) $this->input->post('catatan_purchasing', true));
        $masterId = (int) $this->input->post('id_brg_nk', true);
        if (!in_array($action, array('MAPPED_EXISTING', 'APPROVED_NEW_MASTER', 'REVISION_REQUIRED', 'REJECTED'), true) || $id <= 0 || (in_array($action, array('MAPPED_EXISTING', 'APPROVED_NEW_MASTER'), true) && $masterId <= 0)) {
            $this->session->set_flashdata('error', 'Keputusan usulan tidak valid.'); redirect('vrequestmbarang'); return;
        }
        $saved = $this->M_MasterBarang->decide_pojasa_material_usulan($id, $action, (int) $this->session->userdata('id'), $note, $masterId ?: null);
        $this->session->set_flashdata($saved ? 'success' : 'error', $saved ? 'Usulan PO Jasa berhasil diperbarui dan transaksi ditautkan bila master dipilih.' : 'Usulan tidak dapat diperbarui.');
        redirect('vrequestmbarang');
    }
    public function aprovedmasterbarang()
    {
        date_default_timezone_set("Asia/Jakarta");

        $id         = $this->input->post('idreq');
        $kdbarang   = $this->input->post('kdbarang');
        $kdbarang1  = $this->input->post('syskdbarang');
        $kdqrcode   = $this->input->post('admkd');
        $katbarang  = $this->input->post('katbr');
        $nmbarang   = $this->input->post('nmbarang');
        $descnk     = $this->input->post('descnk');
        $satuan     = $this->input->post('satuanisi');
        $minimum_stock = max(0, (float)$this->input->post('minimum_stock'));
        $inputer    = $this->input->post('reqby');

        $qrcpath    = $this->M_MasterBarang->_generate_qrcode($nmbarang, $kdqrcode);

        $now = date('Y-m-d H:i:s');

        $dtinputbr = array(
            'kd_barang'     => $kdbarang,
            'kd_br_adm'     => $kdbarang1,
            'kat_barang'    => $katbarang,
            'nama_barang'   => $nmbarang,
            'descnk'        => $descnk,
            'satuan'        => $satuan,
            'minimum_stock' => $minimum_stock,
            'gbr_barang'    => "Karisma.png",
            'qrcode_data'   => $kdqrcode,
            'qrcode_path'   => $qrcpath,
            'inputer'       => $inputer,
            'create_at'     => $now,
            'last_updated'  => $inputer
        );

        $kdgenerate = array(
            'kd_barang'     => $kdbarang1
        );
        $qrcgeneratekd = array(
            'kd_qrcode'     => $kdqrcode
        );

        $this->M_MasterBarang->inputmbarangnk($dtinputbr);
        $this->M_MasterBarang->generatekd($kdgenerate);
        $this->M_MasterBarang->generate_qrc($qrcgeneratekd);
        $this->M_MasterBarang->delreqbarangnk($id);

        redirect('vrequestmbarang');
    }

    public function add_mbarang()
    {

        date_default_timezone_set("Asia/Jakarta");

        $kdbarang   = $this->input->post('kd_isi');
        $kdbarang1  = trim((string)$this->input->post('kd_adm'));
        $kdqrcode   = $this->input->post('qrc_isi');
        $katbarang  = $this->input->post('skatbr');
        $nmbarang   = $this->input->post('nmbarang');
        $descnk     = $this->input->post('descisi');
        $satuan     = $this->input->post('stuanbr');
        $minimum_stock = max(0, (float)$this->input->post('minimum_stock'));
        $inputer    = $this->session->userdata('kode');

        $existingBarang = $this->M_MasterBarang->get_masterbarangnk_by_kd_barang($kdbarang1);
        if ($existingBarang) {
            $this->session->set_flashdata('error', 'Kode barang ' . $kdbarang1 . ' telah di gunakan dengan nama barang: ' . $existingBarang->nama_barang);
            redirect('masterbarangnk');
            return;
        }

        $qrcpath    = $this->M_MasterBarang->_generate_qrcode($nmbarang, $kdqrcode);

        $now = date('Y-m-d H:i:s');

        $dtinputbr = array(
            'kd_barang'     => $kdbarang,
            'kd_br_adm'     => $kdbarang1,
            'kat_barang'    => $katbarang,
            'nama_barang'   => $nmbarang,
            'descnk'        => $descnk,
            'satuan'        => $satuan,
            'minimum_stock' => $minimum_stock,
            'gbr_barang'    => "Karisma.png",
            'qrcode_data'   => $kdqrcode,
            'qrcode_path'   => $qrcpath,
            'inputer'       => $inputer,
            'create_at'     => $now,
            'last_updated'  => $inputer
        );

        $kdgenerate = array(
            'kd_barang'     => $kdbarang
        );
        $qrcgeneratekd = array(
            'kd_qrcode'     => $kdqrcode
        );

        $this->M_MasterBarang->inputmbarangnk($dtinputbr);
        $this->M_MasterBarang->generatekd($kdgenerate);
        $this->M_MasterBarang->generate_qrc($qrcgeneratekd);


        redirect('masterbarangnk');
    }

    public function cek_kode_barangnk()
    {
        $kodeBarang = trim((string)$this->input->post('kd_barang', true));
        if ($kodeBarang === '') {
            $kodeBarang = trim((string)$this->input->post('kd_adm', true));
        }

        $barang = $this->M_MasterBarang->get_masterbarangnk_by_kd_barang($kodeBarang);

        $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode(array(
                'status' => true,
                'used' => (bool)$barang,
                'kode_barang' => $barang ? $barang->kd_barang : $kodeBarang,
                'nama_barang' => $barang ? $barang->nama_barang : ''
            )));
    }

    public function inputqrcbrnk($id, $kdqrcode, $nmbarang)
    {
        $qrcpath    = $this->M_MasterBarang->_generate_qrcode($nmbarang, $kdqrcode);
        $actionby   = $this->session->userdata('kode');

        $upqrcode = array(
            'qrcode_path'   => $qrcpath,
            'qrcode_data'   => $kdqrcode,
            'last_updated'  => $actionby
        );
        $qrcgeneratekd = array(
            'kd_qrcode'     => $kdqrcode
        );
        $this->M_MasterBarang->input_qrcode($id, $upqrcode);
        $this->M_MasterBarang->generate_qrc($qrcgeneratekd);

        redirect('masterbarangnk');
    }


    public function edit_mbarangnk()
    {
        date_default_timezone_set("Asia/Jakarta");
        $id         = $this->input->post('id_isi');
        $kdbarang1  = $this->input->post('kd_adm');
        $katbarang  = $this->input->post('skatbr');
        $nmbarang   = $this->input->post('nmbarang');
        $descnk     = $this->input->post('descisi');
        $satuan     = $this->input->post('stuanbr');
        $minimum_stock = max(0, (float)$this->input->post('minimum_stock'));
        $inputer    = $this->session->userdata('kode');
        $dtinputbr = array(
            'kd_br_adm'     => $kdbarang1,
            'kat_barang'    => $katbarang,
            'nama_barang'   => $nmbarang,
            'descnk'        => $descnk,
            'satuan'        => $satuan,
            'minimum_stock' => $minimum_stock,
            'inputer'       => $inputer,
        );
        $this->M_MasterBarang->edit_mbarangnk($id, $dtinputbr);
        redirect('masterbarangnk');
    }

    public function delmbarangnk()
    {
        $id     = $this->input->post('id_isi');

        $this->M_MasterBarang->hapus_mbarangnk($id);

        redirect('masterbarangnk');
    }

    public function uploadgbrbarang()
    {
        $this->load->helper("file");
        $idisi      = $this->input->post('id_isi');
        $flnm       = $this->input->post('file_nm');
        $flnms      = $this->input->post('file_nms');

        if ($flnm == 'Karisma.png') {
            if (!empty($_FILES['gambar_1'])) {
                $config['upload_path'] = './images/gbrbarang/masterbr/';
                $config['allowed_types'] = '*';
                $config['max_size'] = '2000';
                $config['max_width'] = '6000';
                $config['max_height'] = '6000';
                $config['overwrite'] = TRUE;
                $config['file_name'] = date('U') .   '-' . $flnms;
                $this->load->library('upload', $config);
                $this->upload->initialize($config);;

                if (!$this->upload->do_upload('gambar_1')) {
                    $error = array('error' => $this->upload->display_errors());
                    print_r($error);
                    die;
                } else {
                    if ($this->upload->do_upload('gambar_1')) {
                        $image_data1 = $this->upload->data();
                        $full_path1 = $config['file_name'];
                        $data["gbr_produk"] = $full_path1;
                    }
                }
            }

            $dataBarang = array(
                'gbr_barang'    => $image_data1['file_name']
            );

            $this->M_MasterBarang->uploadfile($idisi, $dataBarang);
            redirect('masterbarangnk');
        } elseif ($flnm != 'Karisma.png') {
            if (!empty($_FILES['gambar_1'])) {
                $config['upload_path'] = './images/gbrbarang/masterbr/';
                $config['allowed_types'] = '*';
                $config['max_size'] = '10000';
                $config['max_width'] = '6000';
                $config['max_height'] = '6000';
                $config['overwrite'] = TRUE;
                $config['file_name'] = date('U') .   '-' . $flnms;
                $this->load->library('upload', $config);
                $this->upload->initialize($config);

                if (!$this->upload->do_upload('gambar_1')) {
                    $error = array('error' => $this->upload->display_errors());
                    print_r($error);
                    die;
                } else {
                    if ($this->upload->do_upload('gambar_1')) {
                        $image_data1 = $this->upload->data();
                        $full_path1 = $config['file_name'];
                        $data["gbr_produk"] = $full_path1;
                    }
                }
            }
            $dataBarang = array(
                'gbr_barang'    => $image_data1['file_name']
            );

            unlink(FCPATH . "/images/gbrbarang/masterbr/" . $flnm);
            $this->M_MasterBarang->uploadfile($idisi, $dataBarang);
            redirect('masterbarangnk');
        }
    }
}
