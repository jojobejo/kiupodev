<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 *
 */
class C_Order extends CI_Controller

{
    private $bonusFlagValue = 1;
    private $ppnPersen = 11;

    function __construct()
    {
        parent::__construct();
        $this->load->model('PO/M_Purchase');
        $this->load->model('PO/M_Postatus');
        $this->load->model('Master_barang/M_MasterBarang');
        $this->load->library('form_validation');
    }

    private function isBonusInput($value)
    {
        return (int) $value === $this->bonusFlagValue ? 1 : 0;
    }

    private function parseNumericInput($value)
    {
        $value = trim((string) $value);
        $value = str_replace(' ', '', $value);

        if (strpos($value, ',') !== false) {
            $value = str_replace('.', '', $value);
            $value = str_replace(',', '.', $value);
        } elseif (preg_match('/^\d{1,3}(\.\d{3})+$/', $value)) {
            $value = str_replace('.', '', $value);
        }

        return (float) $value;
    }

    private function hargaLifoNk($kodeBarang, $kodeBarangSys = '', $fallback = 0)
    {
        $fallbackHarga = $this->parseNumericInput($fallback);
        if ($fallbackHarga > 0) {
            return $fallbackHarga;
        }

        $lastHarga = $this->M_Postatus->get_last_harga_barang_nk($kodeBarang, $kodeBarangSys);
        if ($lastHarga && (float) $lastHarga->hrg_satuan > 0) {
            return (float) $lastHarga->hrg_satuan;
        }

        return 0;
    }

    private function satuanPerluKonversi($satuan)
    {
        return in_array(strtolower(trim((string) $satuan)), array('box', 'ltr', 'kg'), true);
    }

    private function satuanPakaiKemasan($satuan)
    {
        return in_array(strtolower(trim((string) $satuan)), array('ltr', 'kg'), true);
    }

    private function isSatuanKosong($satuan)
    {
        $satuan = strtolower(trim((string) $satuan));

        return $satuan === '' || $satuan === '-';
    }

    private function validationError($message)
    {
        return array(
            'status' => false,
            'success' => false,
            'message' => $message,
        );
    }

    private function redirectWithError($message, $url)
    {
        $this->session->set_flashdata('error', $message);
        redirect($url);
    }

    private function tableColumnsAvailable($table, $columns)
    {
        foreach ($columns as $column) {
            if (!$this->db->field_exists($column, $table)) {
                return false;
            }
        }

        return true;
    }

    private function hargaKalkulasiByKeteranganPpn($harga, $ppnMode)
    {
        $harga = $this->parseNumericInput($harga);

        return $harga;
    }

    private function hargaDppDariInclude($harga, $taxPercent)
    {
        $harga = $this->parseNumericInput($harga);
        $taxRate = $this->parseNumericInput($taxPercent) / 100;

        if ($taxRate <= 0) {
            $taxRate = $this->ppnPersen / 100;
        }

        return $harga / (1 + $taxRate);
    }

    private function hargaKalkulasiExcludeByMode($harga, $ppnMode, $taxPercent)
    {
        return $ppnMode === 'include'
            ? $this->hargaDppDariInclude($harga, $taxPercent)
            : $this->parseNumericInput($harga);
    }

    private function hargaIncludeDariExclude($harga, $taxPercent)
    {
        $taxRate = $this->parseNumericInput($taxPercent) / 100;

        return $this->parseNumericInput($harga) * (1 + $taxRate);
    }

    private function taxPercentForPpnSnapshot($ppnMode, $taxPercent)
    {
        $taxPercent = $this->parseNumericInput($taxPercent);

        if ($taxPercent <= 0 && strtolower(trim((string) $ppnMode)) === 'include') {
            return $this->ppnPersen;
        }

        return $taxPercent;
    }

    private function isTaxPpnValid($taxPercent)
    {
        return abs($this->parseNumericInput($taxPercent) - $this->ppnPersen) < 0.00001;
    }

    private function taxByKeteranganHargaPpn($ppnMode)
    {
        return strtolower(trim((string) $ppnMode)) === 'exclude' ? $this->ppnPersen : 0;
    }

    private function getKeteranganHargaPpnTmp($kodeSuplier, $excludeIdTmp = 0)
    {
        if (!$this->db->field_exists('keterangan_harga_ppn', 'tbpo_tmp_item')) {
            return '';
        }

        $this->db->select('keterangan_harga_ppn');
        $this->db->from('tbpo_tmp_item');
        $this->db->where('kode_suplier', $kodeSuplier);
        $this->db->where("TRIM(COALESCE(keterangan_harga_ppn, '')) <> ''", null, false);

        if ($this->db->field_exists('is_bonus', 'tbpo_tmp_item')) {
            $this->db->where('COALESCE(is_bonus, 0) = 0', null, false);
        }

        if ((int) $excludeIdTmp > 0) {
            $this->db->where('id_tmp !=', (int) $excludeIdTmp);
        }

        $this->db->order_by('id_tmp', 'ASC');
        $this->db->limit(1);
        $row = $this->db->get()->row();

        return $row ? strtolower(trim((string) $row->keterangan_harga_ppn)) : '';
    }

    private function validateKeteranganHargaPpn($kodeSuplier, $ppnMode, $redirectUrl, $excludeIdTmp = 0)
    {
        $existingMode = $this->getKeteranganHargaPpnTmp($kodeSuplier, $excludeIdTmp);

        if ($existingMode !== '' && $existingMode !== $ppnMode) {
            if ($existingMode === 'exclude' && $ppnMode === 'include') {
                $this->redirectWithError('Data order sudah menggunakan keterangan harga EXCLUDE PPN. Input berikutnya harus menggunakan EXCLUDE PPN juga. Apabila ingin menggunakan INCLUDE PPN, silahkan hapus data sebelumnya.', $redirectUrl);
                return false;
            }

            if ($existingMode === 'include' && $ppnMode === 'exclude') {
                $this->redirectWithError('Data order sudah menggunakan keterangan harga INCLUDE PPN. Input berikutnya harus menggunakan INCLUDE PPN juga. Apabila ingin menggunakan EXCLUDE PPN, silahkan hapus data sebelumnya.', $redirectUrl);
                return false;
            }

            $this->redirectWithError('Keterangan harga harus sama dengan item PO sebelumnya. PO ini sudah menggunakan ' . ucfirst($existingMode) . ' PPN.', $redirectUrl);
            return false;
        }

        return true;
    }

    private function syncTmpTaxByKeteranganHargaPpn($kodeSuplier)
    {
        $existingMode = $this->getKeteranganHargaPpnTmp($kodeSuplier);
        $this->M_Purchase->set_tmp_tax($kodeSuplier, $this->taxByKeteranganHargaPpn($existingMode));
    }

    private function diskonExcludeTax($nominal, $taxPercent)
    {
        return $this->parseNumericInput($nominal);
    }

    private function hitung_qty_harga_kecil($satuan, $qty, $harga_satuan, $isi, $kemasan, $useRumusKg = true)
    {
        $satuan = strtolower(trim((string) $satuan));
        $qty = $this->parseNumericInput($qty);
        $harga_satuan = $this->parseNumericInput($harga_satuan);
        $isi = $this->parseNumericInput($isi);
        $kemasan = $this->parseNumericInput($kemasan);

        $qty_kecil = 0;
        $harga_satuan_kecil = 0;

        if ($satuan == 'box') {
            if ($isi <= 0) {
                return $this->validationError('Data isi barang tidak valid');
            }

            $qty_kecil = $qty * $isi;
            $harga_satuan_kecil = $harga_satuan / $isi;
        } elseif ($satuan == 'ltr' || ($satuan == 'kg' && $useRumusKg)) {
            if ($kemasan <= 0) {
                return $this->validationError('Data kemasan barang tidak valid');
            }

            $konversi_kemasan = $kemasan / 1000;

            if ($konversi_kemasan <= 0) {
                return $this->validationError('Data kemasan barang tidak valid');
            }

            $qty_kecil = $qty / $konversi_kemasan;
            $harga_satuan_kecil = $konversi_kemasan * $harga_satuan;
        } else {
            $qty_kecil = $qty;
            $harga_satuan_kecil = $harga_satuan;
        }

        return array(
            'status' => true,
            'success' => true,
            'qty_kecil' => $qty_kecil,
            'harga_satuan_kecil' => $harga_satuan_kecil,
        );
    }

    private function prepareKonversiBarang($kodeBarang, $satuan, $qty, $hargaSatuan, $isBonus = 0, $kodeSuplier = null, $useRumusKg = true)
    {
        $kodeBarang = trim((string) $kodeBarang);
        $qty = $this->parseNumericInput($qty);
        $hargaSatuan = $this->parseNumericInput($hargaSatuan);

        if ($kodeBarang === '') {
            return $this->validationError('Kode barang wajib diisi');
        }

        if ($this->isSatuanKosong($satuan)) {
            return $this->validationError('Satuan wajib diisi');
        }

        if ($qty <= 0) {
            return $this->validationError('Qty harus lebih besar dari 0');
        }

        if (!$isBonus && $hargaSatuan <= 0) {
            return $this->validationError('Harga satuan harus lebih besar dari 0');
        }

        if (!$this->db->field_exists('isi', 'tbpo_barang') || !$this->db->field_exists('kemasan', 'tbpo_barang')) {
            return $this->validationError('Kolom isi dan kemasan pada master barang belum tersedia');
        }

        $barang = $kodeSuplier
            ? $this->M_Purchase->getBarangByKode($kodeBarang, $kodeSuplier)
            : $this->M_Purchase->get_barang_by_kode($kodeBarang);

        if (!$barang) {
            return $this->validationError('Data barang tidak ditemukan');
        }

        $isi = isset($barang->isi) ? $this->parseNumericInput($barang->isi) : 0;
        $kemasan = isset($barang->kemasan) ? $this->parseNumericInput($barang->kemasan) : 0;
        $konversi = $this->hitung_qty_harga_kecil($satuan, $qty, $hargaSatuan, $isi, $kemasan, $useRumusKg);

        if (!$konversi['status']) {
            return $konversi;
        }

        $konversi['isi'] = $isi;
        $konversi['kemasan'] = $kemasan;

        return $konversi;
    }

    public function index()
    {

        $data['title'] = 'Purchase Order';
        $data["suplier"] = $this->M_Purchase->getSuplier();

        $this->load->view('partial/header', $data);
        $this->load->view('partial/sidebar');
        $this->load->view('content/po/body', $data);
        $this->load->view('partial/footer');
        $this->load->view('content/po/datatables');
    }

    private function bulanRomawi($bulan)
    {
        $bulanRomawi = array(
            1 => 'I',
            2 => 'II',
            3 => 'III',
            4 => 'IV',
            5 => 'V',
            6 => 'VI',
            7 => 'VII',
            8 => 'VIII',
            9 => 'IX',
            10 => 'X',
            11 => 'XI',
            12 => 'XII',
        );

        $bulan = (int) $bulan;
        return isset($bulanRomawi[$bulan]) ? $bulanRomawi[$bulan] : '';
    }

    private function formatNomorPoSupplier($kdSuplier, $kodePo = 'Q')
    {
        date_default_timezone_set('Asia/Jakarta');
        $kodePo = strtoupper(trim((string) $kodePo));
        if (!in_array($kodePo, array('Q', 'A'), true)) {
            $kodePo = 'Q';
        }

        $nomorUrut = $this->M_Purchase->getNextNomorPoSupplier($kdSuplier, $kodePo);
        $bulanRomawi = $this->bulanRomawi(date('n'));
        $tahun = date('Y');

        for ($i = $nomorUrut; $i <= 999; $i++) {
            $nomorPo = $this->M_Purchase->getAvailableNomorPoSupplier($kodePo, $i, $bulanRomawi, $tahun, $kdSuplier);
            if ($nomorPo !== null) {
                return $nomorPo;
            }
        }

        return sprintf('%s%03d/KIU/%s/%s', $kodePo, $nomorUrut, $bulanRomawi, $tahun);
    }

    public function purchaseSuplier($kdsuplier)
    {
        $data['title'] = 'Purchase Order';

        $kduser = $this->session->userdata('kode');

        $data['kdsuplier'] = $kdsuplier;
        $data['kode_suplier'] = $this->M_Purchase->Suplier($kdsuplier)->result();
        $data["barang"] = $this->M_Purchase->getBarangSup($kdsuplier)->result();
        $data['tmp']    = $this->M_Purchase->getTmpOrder($kdsuplier);
        $data['tmpdiskon'] = $this->M_Purchase->getTmpDiskonOrder($kdsuplier);
        $data['merkBarangTmp'] = $this->M_Purchase->getMerkBarangTmpOrder($kdsuplier);
        $data['tmpnote'] = $this->M_Purchase->getTmpNoteOrder($kdsuplier);
        $data['total']  = $this->M_Purchase->sumTransaksiPenjualan($kdsuplier);
        $data['kdpo']   = $this->M_Purchase->kdpo($kduser, $kdsuplier);
        $data['satuan'] = $this->M_Purchase->getSatuan();
        $data['tax']    = $this->M_Purchase->gettmptax($kdsuplier);
        $data['taxx']    = $this->M_Purchase->getTax();
        $data['taxpo'] = $this->M_Purchase->gettaxposup($kdsuplier)->result();
        $data['nomor_po_otomatis'] = $this->formatNomorPoSupplier($kdsuplier);

        $this->load->view('partial/header', $data);
        $this->load->view('partial/sidebar');
        $this->load->view('content/po/purchase', $data);
        $this->load->view('partial/footer');
        $this->load->view('content/po/datatables');
        $this->load->view('content/po/ajaxPO');
    }

    public function checkNomorPo()
    {
        $noPo = $this->input->post('no_po', TRUE);
        $kdSuplier = $this->input->post('kd_suplier', TRUE);
        $kodePo = $this->input->post('kode_po', TRUE);
        $sameSupplier = $kdSuplier !== null && $kdSuplier !== ''
            ? $this->M_Purchase->nomorPoBaseSupplierExists($noPo, $kdSuplier)
            : false;
        echo json_encode(array(
            'exists' => $this->M_Purchase->nomorPoExists($noPo),
            'same_supplier' => $sameSupplier,
            'suggested' => $kdSuplier !== null && $kdSuplier !== '' ? $this->formatNomorPoSupplier($kdSuplier, $kodePo) : null,
        ));
    }

    public function add_tax_tmp()
    {
        $kdsup  = $this->input->post('kd_suplier_isi');
        $this->syncTmpTaxByKeteranganHargaPpn($kdsup);
        redirect('purchase/sup/' . $kdsup);
    }
    public function update_tax_tmp()
    {
        $kdsup  = $this->input->post('kd_suplier_isi');
        $this->syncTmpTaxByKeteranganHargaPpn($kdsup);
        redirect('purchase/sup/' . $kdsup);
    }
    public function listBarang($kdsuplier)
    {
        $kodeFilterOptions = array('Q', 'A', 'Z', 'C', 'X');
        $kodeFilterAktif = strtoupper(trim((string) $this->input->get('kode_awal', TRUE)));
        if (!in_array($kodeFilterAktif, $kodeFilterOptions, true)) {
            $kodeFilterAktif = 'Q';
        }

        $data['title']          = 'Add Item List';
        $data['kode_suplier']   = $this->M_Purchase->Suplier($kdsuplier)->result();
        $data['barang']         = $this->M_Purchase->getBarangSup($kdsuplier, $kodeFilterAktif)->result();
        $data['kode_filter_options'] = $kodeFilterOptions;
        $data['kode_filter_aktif'] = $kodeFilterAktif;
        $data['tax']            = $this->M_Purchase->getTax();
        $data['tax_tmp']        = $this->M_Purchase->gettmptax($kdsuplier);
        $data['satuan']         = $this->M_Purchase->getSatuan();
        $data['tmp']            = $this->M_Purchase->getTmpOrder($kdsuplier);

        $this->load->view('partial/header', $data);
        $this->load->view('partial/sidebar');
        $this->load->view('content/po/listbarang', $data);
        $this->load->view('partial/footer');
        $this->load->view('content/po/datatables');
        $this->load->view('content/po/ajaxPO');
    }

    public function addSuplier()
    {
        $kdsup       = $this->input->post('kd_isi');
        $namasup     = $this->input->post('nm_isi');
        $alamatsup   = $this->input->post('almt_isi');
        $nosup       = $this->input->post('tlp_isi');
        $faxsup      = $this->input->post('fax_isi');
        $emailsup    = $this->input->post('em_isi');

        $dataSup = array(
            'kd_suplier'    => $kdsup,
            'nama_suplier'  => $namasup,
            'alamat_suplier' => $alamatsup,
            'no_telpon'     => $nosup,
            'no_fax'        => $faxsup,
            'email'         => $emailsup
        );

        $this->M_Purchase->addSuplier($dataSup);
        redirect('purchase');
    }

    public function editSuplier()
    {
        $kdsup       = $this->input->post('kd_sup');
        $namasup     = $this->input->post('nama_isi');
        $alamatsup   = $this->input->post('alamat_isi');
        $nosup       = $this->input->post('telp_isi');
        $faxsup      = $this->input->post('fax_isi');
        $emailsup    = $this->input->post('email_isi');

        $dataSup = array(
            'kd_suplier'    => $kdsup,
            'nama_suplier'  => $namasup,
            'alamat_suplier' => $alamatsup,
            'no_telpon'     => $nosup,
            'no_fax'        => $faxsup,
            'email'         => $emailsup
        );

        $this->M_Purchase->editSuplier($kdsup, $dataSup);
        redirect('purchase/sup/' . $kdsup);
    }

    public function addBarang()
    {
        $data['title'] = 'Add Item List';

        $kdbarang   = $this->input->post('kd_isi');
        $namabarang = $this->input->post('nama_isi');
        $kdsuplier  = $this->input->post('kd_sup_isi');
        $isi        = $this->parseNumericInput($this->input->post('isi'));
        $kemasan    = $this->parseNumericInput($this->input->post('kemasan'));

        $dataBarang = array(
            'kode_barang'   => $kdbarang,
            'kd_suplier'    => $kdsuplier,
            'nama_barang'   => $namabarang,
            'isi'           => $isi,
            'kemasan'       => $kemasan
        );

        $this->M_MasterBarang->insertBarang($dataBarang);
        redirect('purchase/listBarang/' . $kdsuplier);
    }

    public function editBarang()
    {
        $idbarang   = $this->input->post('id_isi');
        $kdbarang   = $this->input->post('kd_isi');
        $namabarang = $this->input->post('nama_isi');
        $kdsuplier  = $this->input->post('kd_sup_isi');
        $isi        = $this->parseNumericInput($this->input->post('isi'));
        $kemasan    = $this->parseNumericInput($this->input->post('kemasan'));

        $dataBarang = array(
            'id_barang'     => $idbarang,
            'kode_barang'   => $kdbarang,
            'kd_suplier'    => $kdsuplier,
            'nama_barang'   => $namabarang,
            'isi'           => $isi,
            'kemasan'       => $kemasan
        );

        $this->M_MasterBarang->editBarang($idbarang, $dataBarang);
        redirect('purchase/listBarang/' . $kdsuplier);
    }

    public function editBarangSuplier()
    {
        $idbarang   = $this->input->post('id_isi');
        $kdbarang   = $this->input->post('kd_isi');
        $namabarang = $this->input->post('nama_isi');
        $kdsuplier  = $this->input->post('kd_sup_isi');
        $isi        = $this->parseNumericInput($this->input->post('isi'));
        $kemasan    = $this->parseNumericInput($this->input->post('kemasan'));

        $dataBarang = array(
            'id_barang'     => $idbarang,
            'kode_barang'   => $kdbarang,
            'kd_suplier'    => $kdsuplier,
            'nama_barang'   => $namabarang,
            'isi'           => $isi,
            'kemasan'       => $kemasan
        );

        $this->M_MasterBarang->editBarang($idbarang, $dataBarang);
        redirect('purchase/listBarang/' . $kdsuplier);
    }

    public function hapusBarang($id, $kdsuplier)
    {
        $this->M_MasterBarang->hapusBarang($id);
        redirect('purchase/listBarang/' . $kdsuplier);
    }

    public function addChart()
    {
        $suplier    = $this->input->post('kd_sup', TRUE);
        $kdbarang   = $this->input->post('kd_isi', TRUE);
        $nmbarang   = $this->input->post('nama_isi', TRUE);
        $satuan     = $this->input->post('satuan_isi', TRUE);
        $qty        = $this->parseNumericInput($this->input->post('qty_isi', TRUE));
        $isBonus    = $this->isBonusInput($this->input->post('is_bonus'));
        $ppnMode    = strtolower(trim((string) $this->input->post('ppn_mode', TRUE)));
        $ppnMode    = in_array($ppnMode, array('exclude', 'include'), true) ? $ppnMode : 'exclude';
        $useRumusKgInput = $this->input->post('use_rumus_kg');
        $useRumusKg = $useRumusKgInput === null ? true : $useRumusKgInput === '1';
        $hargaInput = $this->parseNumericInput($this->input->post('hrg_isi', TRUE));
        $hargaQty   = $isBonus ? 0 : $this->hargaKalkulasiByKeteranganPpn($hargaInput, $ppnMode);
        $bonusNote  = trim((string) $this->input->post('bonus_keterangan', TRUE));
        $user       = $this->session->userdata('kode');
        $hargahasil = $hargaQty * $qty;
        $konversi   = $this->prepareKonversiBarang($kdbarang, $satuan, $qty, $hargaQty, $isBonus, $suplier, $useRumusKg);
        $taxAktif   = $this->ppnPersen;

        if (!$isBonus && !$this->validateKeteranganHargaPpn($suplier, $ppnMode, 'purchase/listBarang/' . $suplier)) {
            return;
        }

        if (!$konversi['status']) {
            $this->redirectWithError($konversi['message'], 'purchase/listBarang/' . $suplier);
            return;
        }

        if (!$this->tableColumnsAvailable('tbpo_tmp_item', array('isi', 'kemasan', 'qty_kecil', 'harga_satuan_kecil'))) {
            $this->redirectWithError('Kolom konversi pada tbpo_tmp_item belum tersedia', 'purchase/listBarang/' . $suplier);
            return;
        }

        $data = array(
            'kode_barang'   => $kdbarang,
            'kd_user'       => $user,
            'nama_barang'   => $nmbarang,
            'kode_suplier'  => $suplier,
            'satuan'        => $satuan,
            'qty'           => $qty,
            'isi'           => $konversi['isi'],
            'kemasan'       => $konversi['kemasan'],
            'qty_kecil'     => $konversi['qty_kecil'],
            'harga_satuan'  => $hargaQty,
            'harga_satuan_kecil' => $konversi['harga_satuan_kecil'],
            'total_harga'   => $hargahasil,
            'is_bonus'      => $isBonus,
            'keterangan_bonus' => $isBonus ? $bonusNote : '',
        );

        if ($this->db->field_exists('harga_satuan_exclude', 'tbpo_tmp_item')) {
            $data['harga_satuan_exclude'] = $isBonus ? 0 : $this->hargaKalkulasiExcludeByMode($hargaQty, $ppnMode, $taxAktif);
        }

        if ($this->db->field_exists('harga_satuan_kecil_exclude', 'tbpo_tmp_item')) {
            $data['harga_satuan_kecil_exclude'] = $isBonus ? 0 : $this->hargaKalkulasiExcludeByMode($konversi['harga_satuan_kecil'], $ppnMode, $taxAktif);
        }

        if ($this->db->field_exists('keterangan_harga_ppn', 'tbpo_tmp_item')) {
            $data['keterangan_harga_ppn'] = $isBonus ? '' : $ppnMode;
        }

        $this->db->trans_start();
        $this->M_Purchase->addChart($data);
        $this->syncTmpTaxByKeteranganHargaPpn($suplier);
        $this->db->trans_complete();

        if ($this->db->trans_status() === FALSE) {
            $this->redirectWithError('Barang gagal disimpan', 'purchase/listBarang/' . $suplier);
            return;
        }

        redirect('purchase/listBarang/' . $suplier);
    }

    public function hapusChart($id, $kdsuplier)
    {
        $this->db->trans_start();
        $this->M_Purchase->hapusChart($id);
        $this->syncTmpTaxByKeteranganHargaPpn($kdsuplier);
        $this->db->trans_complete();
        redirect('purchase/sup/' . $kdsuplier);
    }

    private function getPersentaseDiskon($text)
    {
        if (preg_match('/\(([0-9.,]+)%\)/', (string) $text, $match)) {
            return $this->parseNumericInput($match[1]);
        }

        return null;
    }

    private function getDiskonRowMarker($text, $type)
    {
        if (preg_match('/\[ROW_' . preg_quote($type, '/') . ':(\d+)\]/', (string) $text, $match)) {
            return (int) $match[1];
        }

        return null;
    }

    private function getDiskonMerkMarker($text)
    {
        if (preg_match('/\[MERK:([^\]]+)\]/', (string) $text, $match)) {
            return trim($match[1]);
        }

        return null;
    }

    private function getDiskonSatuanMarker($text)
    {
        if (preg_match('/\[SATUAN_DISKON:(BOX|PCS|LTR|KG)\]/i', (string) $text, $match)) {
            return strtoupper($match[1]);
        }

        return 'PCS';
    }

    private function normalisasiSatuanDiskon($satuanDiskon)
    {
        $satuanDiskon = strtoupper(trim((string) $satuanDiskon));
        return in_array($satuanDiskon, array('BOX', 'PCS', 'LTR', 'KG'), true) ? $satuanDiskon : '';
    }

    private function hitungDiskonSatuanKecil($nominal, $satuanDiskon, $isi, $kemasan, $taxPercent = 0)
    {
        $nominal = $this->diskonExcludeTax($nominal, $taxPercent);
        $satuanDiskon = $this->normalisasiSatuanDiskon($satuanDiskon);
        $isi = $this->parseNumericInput($isi);
        $kemasan = $this->parseNumericInput($kemasan);

        if ($satuanDiskon === '') {
            return $this->validationError('Satuan diskon wajib dipilih.');
        }

        if ($nominal <= 0) {
            return $this->validationError('Nominal diskon harus lebih besar dari 0.');
        }

        if ($isi <= 0) {
            return $this->validationError('Data isi barang belum disetting.');
        }

        if ($satuanDiskon === 'BOX') {
            $diskonSatuanKecil = $nominal / $isi;
        } elseif ($satuanDiskon === 'PCS') {
            $diskonSatuanKecil = $nominal;
        } else {
            if ($kemasan <= 0) {
                return $this->validationError('Data kemasan barang belum disetting.');
            }

            $diskonSatuanKecil = $nominal * ($kemasan / 1000);
        }

        return array(
            'status' => true,
            'success' => true,
            'diskon_satuan_kecil' => $diskonSatuanKecil,
        );
    }

    private function hitungDiskonPerSatuanBarangResult($namaBarang, $qty, $diskonList, $hargaSatuanKecil = 0, $idTmp = null, $merkBarang = '', $item = null, $taxPercent = 0)
    {
        $hargaAwal = (float) $hargaSatuanKecil;
        $hargaBerjalan = $hargaAwal;
        $merkBarang = trim((string) $merkBarang);
        $metadata = array(
            'id_diskon_merk' => null,
            'satuan_diskon' => null,
            'nominal_diskon' => 0,
            'diskon_satuan_kecil' => 0,
        );

        foreach ($diskonList as $diskon) {
            $diskonRowTmp = $this->getDiskonRowMarker($diskon->nama_diskon, 'TMP');
            if ($diskonRowTmp !== null && $idTmp !== null && $diskonRowTmp !== (int) $idTmp) {
                continue;
            }

            $diskonMerk = $this->getDiskonMerkMarker($diskon->nama_diskon);
            if ($diskonMerk !== null) {
                if ($merkBarang === '' || strcasecmp($diskonMerk, $merkBarang) !== 0) {
                    continue;
                }

                $satuanDiskon = $this->getDiskonSatuanMarker($diskon->nama_diskon);
                $isi = $item && isset($item->isi) ? $item->isi : 0;
                $kemasan = $item && isset($item->kemasan) ? $item->kemasan : 0;
                $konversi = $this->hitungDiskonSatuanKecil($diskon->nominal, $satuanDiskon, $isi, $kemasan, $taxPercent);

                if (!$konversi['status']) {
                    continue;
                }

                $diskonSatuanKecil = min((float) $konversi['diskon_satuan_kecil'], $hargaBerjalan);
                $hargaBerjalan -= $diskonSatuanKecil;
                $hargaBerjalan = max($hargaBerjalan, 0);
                $metadata = array(
                    'id_diskon_merk' => null,
                    'satuan_diskon' => $satuanDiskon,
                    'nominal_diskon' => (float) $diskon->nominal,
                    'diskon_satuan_kecil' => $diskonSatuanKecil,
                );
                continue;
            }

            $prefixDiskonNominal = $namaBarang . ' - ';
            $prefixDiskonPersen = 'Diskon Barang - ' . $namaBarang . ' ';

            if (strpos($diskon->nama_diskon, $prefixDiskonNominal) === 0) {
                $hargaBerjalan -= $this->diskonExcludeTax($diskon->nominal, $taxPercent);
                $hargaBerjalan = max($hargaBerjalan, 0);
            } elseif (strpos($diskon->nama_diskon, $prefixDiskonPersen) === 0) {
                $persenDiskon = $this->getPersentaseDiskon($diskon->nama_diskon);
                if ($persenDiskon !== null) {
                    $hargaBerjalan -= ($hargaBerjalan * $persenDiskon) / 100;
                    $hargaBerjalan = max($hargaBerjalan, 0);
                } elseif ($qty > 0) {
                    $hargaBerjalan -= (float) $diskon->nominal / $qty;
                    $hargaBerjalan = max($hargaBerjalan, 0);
                }
            }
        }

        return array(
            'diskon_per_satuan' => max($hargaAwal - $hargaBerjalan, 0),
            'metadata' => $metadata,
        );
    }

    private function diskonPerSatuanBarang($namaBarang, $qty, $diskonList, $hargaSatuanKecil = 0, $idTmp = null, $merkBarang = '', $taxPercent = 0)
    {
        $result = $this->hitungDiskonPerSatuanBarangResult($namaBarang, $qty, $diskonList, $hargaSatuanKecil, $idTmp, $merkBarang, null, $taxPercent);
        return $result['diskon_per_satuan'];
    }

    public function rekam_po()
    {
        date_default_timezone_set("Asia/Jakarta");
        $suplier    = $this->input->post('suplier');
        $nopo       = $this->input->post('nopo');
        $tgl        = $this->input->post('tgl');
        $tmpo       = (int) $this->parseNumericInput($this->input->post('tmpo'));
        $gdg        = $this->input->post('gdg');
        $kdpo       = $this->input->post('kdpo');
        $jml        = (int) $this->parseNumericInput($this->input->post('jml'));
        $harga      = $this->parseNumericInput($this->input->post('harga'));
        $tax        = $this->parseNumericInput($this->input->post('tax'));
        $nmuser     = $this->session->userdata('nama_user');
        $user       = $this->session->userdata('kode');
        $tmp        = $this->M_Purchase->get_tmp($suplier);
        $tmpdiskon  = $this->M_Purchase->getTmpDiskonOrder($suplier);
        $tmpnote    = $this->M_Purchase->getTmpNoteOrder($suplier);
        $detailTransaksi = array();
        $processedTmpIds = array();
        $totalHargaDiskon = 0;
        $totalHargaInclude = 0;
        $totalHargaExlude = 0;
        $totalHargaDiskonInclude = 0;
        $totalHargaDiskonExlude = 0;
        $hargaPajak = 0;

        if (!$tmp) {
            echo json_encode(array('msg' => 'empty'));
            return;
        }

        if (!preg_match('/^[QA]\d{3}\/KIU\/(I|II|III|IV|V|VI|VII|VIII|IX|X|XI|XII)\/\d{4}[A-Z]?$/', $nopo)) {
            echo json_encode(array(
                'msg' => 'error',
                'message' => 'Format Nomor PO harus seperti Q001/KIU/VII/2026 atau Q001/KIU/VII/2026A'
            ));
            return;
        }

        if ($this->M_Purchase->nomorPoBaseSupplierExists($nopo, $suplier)) {
            echo json_encode(array(
                'msg' => 'error',
                'message' => 'Nomor PO sudah digunakan supplier ini. Silakan gunakan nomor berikutnya.'
            ));
            return;
        }

        if ($this->M_Purchase->nomorPoExists($nopo)) {
            echo json_encode(array(
                'msg' => 'error',
                'message' => 'Nomor PO sudah digunakan. Silakan gunakan nomor berikutnya atau suffix alfabet yang tersedia.'
            ));
            return;
        }

        $keteranganHargaPpnHeader = $this->getKeteranganHargaPpnTmp($suplier);
        $tax = $this->taxByKeteranganHargaPpn($keteranganHargaPpnHeader);
        $this->M_Purchase->set_tmp_tax($suplier, $tax);

        if (!$this->tableColumnsAvailable('tbpo_detail_po', array('isi', 'kemasan', 'qty_kecil', 'harga_satuan_exclude', 'harga_satuan_kecil', 'harga_satuan_kecil_exclude', 'keterangan_harga_ppn'))) {
            echo json_encode(array(
                'msg' => 'error',
                'message' => 'Kolom konversi dan PPN pada tbpo_detail_po belum tersedia'
            ));
            return;
        }

        foreach ($tmp as $chart) {
            $tmpId = isset($chart->id_tmp) ? (int) $chart->id_tmp : 0;
            if ($tmpId > 0 && isset($processedTmpIds[$tmpId])) {
                continue;
            }
            if ($tmpId > 0) {
                $processedTmpIds[$tmpId] = true;
            }

            $isBonus = isset($chart->is_bonus) ? (int) $chart->is_bonus : 0;
            $qtyKecil = isset($chart->qty_kecil) && (float) $chart->qty_kecil > 0 ? $chart->qty_kecil : $chart->qty;
            $hargaSatuanKecilSimpan = isset($chart->harga_satuan_kecil) && ((float) $chart->harga_satuan_kecil > 0 || $isBonus) ? $chart->harga_satuan_kecil : $chart->harga_satuan;
            $keteranganHargaPpn = isset($chart->keterangan_harga_ppn) ? strtolower(trim((string) $chart->keterangan_harga_ppn)) : '';
            $hargaSatuanExclude = isset($chart->harga_satuan_exclude) && (float) $chart->harga_satuan_exclude > 0
                ? $chart->harga_satuan_exclude
                : ($keteranganHargaPpn === 'include' ? $this->hargaDppDariInclude($chart->harga_satuan, $tax) : $chart->harga_satuan);
            $hargaSatuanKecil = $keteranganHargaPpn === 'include'
                ? $this->hargaDppDariInclude($hargaSatuanKecilSimpan, $tax)
                : $hargaSatuanKecilSimpan;
            $merkBarang = isset($chart->merk_barang) ? $chart->merk_barang : '';
            $diskonResult = $isBonus
                ? array('diskon_per_satuan' => 0, 'metadata' => array('id_diskon_merk' => null, 'satuan_diskon' => null, 'nominal_diskon' => 0, 'diskon_satuan_kecil' => 0))
                : $this->hitungDiskonPerSatuanBarangResult($chart->nama_barang, $chart->qty, $tmpdiskon, $hargaSatuanKecil, $chart->id_tmp, $merkBarang, $chart, $tax);
            $diskonPerSatuan = $diskonResult['diskon_per_satuan'];
            $diskonMetadata = $diskonResult['metadata'];
            $hargaDiskonInclude = $isBonus ? 0 : max($hargaSatuanKecil - $diskonPerSatuan, 0);
            $hargaSatuanKecilExclude = $isBonus ? 0 : $hargaSatuanKecil;
            $hargaDiskon = $isBonus ? 0 : $hargaDiskonInclude;
            $hargaTotalDiskon = $isBonus ? 0 : ($hargaDiskon * $qtyKecil);
            $taxSnapshot = $this->taxPercentForPpnSnapshot($keteranganHargaPpn, $tax);
            $hargaTotalExlude = $isBonus ? 0 : ($hargaSatuanKecilExclude * $qtyKecil);
            $hargaTotalInclude = $isBonus ? 0 : ($keteranganHargaPpn === 'include'
                ? $chart->total_harga
                : $this->hargaIncludeDariExclude($hargaTotalExlude, $taxSnapshot));
            $hargaTotalDiskonExlude = $hargaTotalDiskon;
            $hargaTotalDiskonInclude = $isBonus ? 0 : $this->hargaIncludeDariExclude($hargaTotalDiskonExlude, $taxSnapshot);
            $totalHargaDiskon += $hargaTotalDiskon;
            $totalHargaInclude += $hargaTotalInclude;
            $totalHargaExlude += $hargaTotalExlude;
            $totalHargaDiskonInclude += $hargaTotalDiskonInclude;
            $totalHargaDiskonExlude += $hargaTotalDiskonExlude;

            if ($keteranganHargaPpnHeader === '' && in_array($keteranganHargaPpn, array('include', 'exclude'), true)) {
                $keteranganHargaPpnHeader = $keteranganHargaPpn;
            }

            $detailTransaksi[] = array(
                'no_po'             => $nopo,
                'kd_po'             => $kdpo,
                'tgl_transaksi'     => $tgl,
                'kd_barang'         => $chart->kode_barang,
                'nama_barang'       => $chart->nama_barang,
                'kd_suplier'        => $chart->kode_suplier,
                'satuan'            => $chart->satuan,
                'qty'               => $chart->qty,
                'isi'               => isset($chart->isi) ? $chart->isi : 0,
                'kemasan'           => isset($chart->kemasan) ? $chart->kemasan : 0,
                'qty_kecil'         => $qtyKecil,
                'hrg_satuan'        => $chart->harga_satuan,
                'harga_satuan_exclude' => $isBonus ? 0 : $hargaSatuanExclude,
                'harga_satuan_kecil' => $hargaSatuanKecilSimpan,
                'harga_satuan_kecil_exclude' => $hargaSatuanKecilExclude,
                'hrg_diskon'        => $hargaDiskon,
                'hrg_total'         => $chart->total_harga,
                'hrg_total_diskon'  => $hargaTotalDiskon,
                'id_diskon_merk'    => $diskonMetadata['id_diskon_merk'],
                'satuan_diskon'     => $diskonMetadata['satuan_diskon'],
                'nominal_diskon'    => $diskonMetadata['nominal_diskon'],
                'diskon_satuan_kecil' => $diskonMetadata['diskon_satuan_kecil'],
                'harga_satuan_kecil_setelah_diskon' => $hargaDiskonInclude,
                'total_harga_setelah_diskon' => $hargaDiskonInclude * $qtyKecil,
                'is_bonus'          => $isBonus,
                'keterangan_bonus'  => isset($chart->keterangan_bonus) ? $chart->keterangan_bonus : '',
                'keterangan_harga_ppn' => isset($chart->keterangan_harga_ppn) ? $chart->keterangan_harga_ppn : '',
                'kd_user'           => $user,
                '_id_tmp_source'    => $chart->id_tmp,
            );
        }

        $hargaPajak = $totalHargaDiskon * ((float) $tax / 100);

        $rekamData = array(
            'kd_po'         => $kdpo,
            'no_po'         => $nopo,
            'tgl_transaksi' => $tgl,
            'kd_suplier'    => $suplier,
            'jml_item'      => $jml,
            'total_harga'   => $harga,
            'total_harga_diskon' => $totalHargaDiskon,
            'keterangan_harga_ppn' => $keteranganHargaPpnHeader,
            'total_harga_include' => $totalHargaInclude,
            'total_harga_exlude' => $totalHargaExlude,
            'total_harga_diskon_include' => $totalHargaDiskonInclude,
            'total_harga_diskon_exlude' => $totalHargaDiskonExlude,
            'tmpo_pembayaran' => $tmpo,
            'gdg_pengiriman'  => $gdg,
            'tax'           => $tax,
            'hrg_pajak'     => $hargaPajak,
            'acc_with'      => '',
            'kd_printout_note' => '',
            'status'        => 'ON PROGRESS'
        );
        $this->db->trans_start();
        $this->M_Purchase->inputOrder($rekamData);
        // UPDATE NOTE - REKAM BARU
        $updatenote = array(
            'kd_po' => $kdpo,
            'isi_note' => 'Purchase Order Baru',
            'kd_user' => $user,
            'nama_user' => $nmuser,
            'note_for' => '1',
            'update_status' => '1',
            'create_at' => date('Y-m-d H:i:s')
        );
        $this->M_Purchase->addNote($updatenote);

        $tmpToDetailId = array();
        foreach ($detailTransaksi as $listTransaksi) {
            $idTmpSource = isset($listTransaksi['_id_tmp_source']) ? $listTransaksi['_id_tmp_source'] : null;
            unset($listTransaksi['_id_tmp_source']);
            $idDetPo = $this->M_Purchase->inputDetailPO($listTransaksi);
            if ($idTmpSource !== null) {
                $tmpToDetailId[(int) $idTmpSource] = $idDetPo;
            }
        }
        foreach ($tmpdiskon as $diskon) {
            $keteranganDiskon = $diskon->nama_diskon;
            $diskonRowTmp = $this->getDiskonRowMarker($keteranganDiskon, 'TMP');
            if ($diskonRowTmp !== null && isset($tmpToDetailId[$diskonRowTmp])) {
                $keteranganDiskon = preg_replace('/\[ROW_TMP:' . $diskonRowTmp . '\]/', '[ROW_DET:' . $tmpToDetailId[$diskonRowTmp] . ']', $keteranganDiskon);
            }
            $listdiskon = array(
                'kd_po' => $kdpo,
                'kd_suplier' => $diskon->kd_suplier,
                'keterangan' => $keteranganDiskon,
                'nominal'    => $diskon->nominal
            );
            $this->M_Purchase->input_diskon($listdiskon);
        }
        foreach ($tmpnote as $note) {
            $listnote = array(
                'kd_po' => $kdpo,
                'kd_suplier' => $note->kd_suplier,
                'isi_note'  => $note->isi_note,
                'color_box' => ''
            );
            $this->M_Purchase->input_note($listnote);
        }
        $this->M_Purchase->delete_tmp_diskon($suplier);
        $this->M_Purchase->delete_tmp_note_sp_i($suplier);
        $this->M_Purchase->delete_tmp_tax($suplier);
        $this->M_Purchase->hapusTmp($suplier);
        $this->db->trans_complete();

        if ($this->db->trans_status() === FALSE) {
            echo json_encode(array('msg' => 'error'));
            return;
        }

        $msg = "success";
        $data = array('msg' => $msg, 'nopo' => $nopo);
        echo json_encode($data);
    }


    public function pononkomersil()
    {
        $data['title'] = 'Purchase Order Non Komersil';
        $data['depuser'] = $this->session->userdata('departemen');
        $data['nmuser'] = $this->session->userdata('nama_user');
        $user = $this->session->userdata('kode');
        $dep  = $this->session->userdata('departemen');
        $data['nopk'] = $user . $dep;

        $data['kdbarang'] = $this->M_Purchase->kdnonkomersial();
        $data['tmp']    = $this->M_Purchase->get_tmp_non_komersil($user);
        $data['total'] = $this->M_Purchase->sumtransaksink();
        $data['nopo'] = $this->M_Purchase->getkdnoponk();
        $data['tmpntpembelian'] = $this->M_Purchase->gettmp_note_pembelian($user);

        $this->load->view('partial/header', $data);
        $this->load->view('partial/sidebar');
        $this->load->view('content/po/nonkomersil', $data);
        $this->load->view('partial/footer');
        $this->load->view('content/po/datatables');
        $this->load->view('content/po/ajaxPO');
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
    public function addtmpponk()
    {
        $kdbarang   = $this->input->post('kd_adm');
        $kdbrsys    = $this->input->post('kd_system');
        $katbarang  = $this->input->post('katbrg');
        $namabarang = $this->input->post('nmbarang');
        $descbarang = $this->input->post('descisi');
        $ketbarang  = $this->input->post('ketbarang');
        $qtybarang  = $this->input->post('qtyisi');
        $hrgsatuan  = $this->hargaLifoNk($kdbarang, $kdbrsys, $this->input->post('hrgisi'));
        $kduser     = $this->session->userdata('kode');
        $totalharga = $qtybarang * $hrgsatuan;

        $dataBarang = array(
            'nama_barang'   => $namabarang,
            'deskripsi'     => $descbarang,
            'keterangan'    => $ketbarang,
            'qty'           => $qtybarang,
            'hrg_satuan'    => $hrgsatuan,
            'total_harga'   => $totalharga,
            'kd_bsys'       => $kdbrsys,
            'kd_barang'     => $kdbarang,
            'kat_barang'    => $katbarang,
            'kd_user'       => $kduser
        );

        $this->M_Purchase->input_tmp_nk($dataBarang);

        redirect('pononkomersil/list_stocknkpo');
    }

    public function uploadfilegambaredit()
    {
        $kdbarang   = $this->input->post('kd_isi');
        $idisi      = $this->input->post('id_isi');

        if (!empty($_FILES['gambar_1'])) {
            $config['upload_path'] = './images/imgtmp/';
            $config['allowed_types'] = 'jpg|png|gif';
            $config['max_size'] = '10000';
            $config['max_width'] = '6000';
            $config['max_height'] = '6000';
            $config['overwrite'] = TRUE;
            $config['file_name'] = date('Y') . date('m') . date('U') .   '_' . $_FILES['gambar_1']['name'];
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
            'id_tmp_nk'   => $idisi,
            'gbr_produk'    => $image_data1['file_name']
        );

        $kdgenerate = array(
            'kd_barang' => $kdbarang
        );

        $this->M_Purchase->generatekd($kdgenerate);
        $this->M_Purchase->editimgbarang($idisi, $dataBarang);

        redirect('pononkomersil');
    }
    public function tmp_edit_barang_komersil()
    {
        $id         = $this->input->post('id_isi');
        $namabarang = $this->input->post('nama_isi');
        $descbarang  = $this->input->post('desc_isi');
        $ketbarang  = $this->input->post('ket_isi');
        $qtybarang  = $this->input->post('qty_isi');
        $hrgsatuan  = $this->input->post('hrg_isi');
        $totalharga = $qtybarang * $hrgsatuan;

        $dataBarang = array(
            'nama_barang'   => $namabarang,
            'deskripsi'     => $descbarang,
            'keterangan'    => $ketbarang,
            'qty'           => $qtybarang,
            'hrg_satuan'    => $hrgsatuan,
            'total_harga'   => $totalharga,
        );

        $this->M_Purchase->edit_input_nk($id, $dataBarang);

        redirect('pononkomersil');
    }
    public function tmp_hapus_barang_komersil()
    {
        $id = $this->input->post('id_isi');
        $this->M_Purchase->hapus_item_nk($id);
        redirect('pononkomersil');
    }
    public function add_note_pembelian_tmp()
    {
        $kduser = $this->session->userdata('kode');
        $ket    = $this->input->post('ket_isi');

        $datanote = array(
            'keterangan'   => $ket,
            'kd_user'   => $kduser
        );
        $this->M_Purchase->input_note_pembelian_nt($datanote);
        redirect('pononkomersil');
    }

    public function edit_note_pembelian_tmp()
    {
        $idisi = $this->input->post('id_isi');
        $ket    = $this->input->post('ket_isi');

        $datanote = array(
            'keterangan'   => $ket,
        );

        $this->M_Purchase->editNoteNk($idisi, $datanote);
        redirect('pononkomersil');
    }

    public function hapus_note_pembelian_tmp($id)
    {

        $this->M_Purchase->hapus_tmp_nk_nt_pembelian($id);
        redirect('pononkomersil');
    }

    public function rekam_po_nk()
    {
        // Status jenis po  
        // 1. PO Pembelian Barang
        // 2. PO request 

        date_default_timezone_set("Asia/Jakarta");
        $kdnk       = $this->input->post('kdpo');
        $nopo       = $this->input->post('nopo');
        $tgl        = $this->input->post('tgl');
        $departemen = $this->input->post('departemen');
        $nmuser     = $this->input->post('nm_user');
        $tjuan      = $this->input->post('tujuan');
        $jml        = $this->input->post('jml');
        $hrg        = $this->input->post('harga');
        $kduser     = $this->session->userdata('kode');
        $nmuser1    = $this->session->userdata('nama_user');
        $tmp        = $this->M_Purchase->get_tmp_non_komersil($kduser);
        $tmpntpmbln = $this->M_Purchase->gettmp_note_pembelian($kduser);

        $rekamData = array(
            'jns_po'        => '1',
            'kd_po_nk'      => $kdnk,
            'nopo'          => $nopo,
            'kd_user'       => $kduser,
            'nm_user'       => $nmuser,
            'tgl_transaksi' => $tgl,
            'jml_item'      => $jml,
            'total_harga'   => $hrg,
            'status'        => 'ON PROGRESS',
            'departemen'    => $departemen,
            'tj_pembelian'  => $tjuan
        );

        $this->M_Purchase->input_po_nk($rekamData);

        if ($tmp) {
            foreach ($tmp as $chart) {
                $listTransaksi = array(
                    'kd_po_nk'          => $kdnk,
                    'kd_user'           => $kduser,
                    'tgl_transaksi'     => $tgl,
                    'kd_bsys'           => $chart->kd_bsys,
                    'kd_barang'         => $chart->kd_barang,
                    'nama_barang'       => $chart->nama_barang,
                    'deskripsi'         => $chart->deskripsi,
                    'keterangan'        => $chart->keterangan,
                    'qty'               => $chart->qty,
                    'hrg_satuan'        => $chart->hrg_satuan,
                    'hrg_nyata'         => '0',
                    'total_harga'       => $chart->total_harga,
                    'total_nyata'       => '0',
                );

                if ($this->db->field_exists('harga_lifo', 'tbpo_detail_po_nk') || $this->db->field_exists('kd_po_lifo_ref', 'tbpo_detail_po_nk')) {
                    $lastHarga = $this->M_Postatus->get_last_harga_barang_nk($chart->kd_barang, $chart->kd_bsys);
                    if ($lastHarga) {
                        if ($this->db->field_exists('harga_lifo', 'tbpo_detail_po_nk')) {
                            $listTransaksi['harga_lifo'] = $lastHarga->hrg_satuan;
                        }
                        if ($this->db->field_exists('kd_po_lifo_ref', 'tbpo_detail_po_nk')) {
                            $listTransaksi['kd_po_lifo_ref'] = $lastHarga->kd_po_nk;
                        }
                    }
                }

                $this->M_Purchase->input_detail_po_nk($listTransaksi);
            }

            foreach ($tmpntpmbln as $pm) {
                $notepemeblian = array(
                    'kd_po'         => $kdnk,
                    'keterangan'    => $pm->keterangan,
                    'kd_user'       => $kduser
                );
                $this->M_Purchase->input_detail_nt_pembelian($notepemeblian);
                $this->M_Purchase->delete_detail_nt_pembelian($kduser);
            }

            $updatenote = array(
                'kd_po' => $kdnk,
                'isi_note' => 'Purchase Order Baru',
                'kd_user' => $kduser,
                'nama_user' => $nmuser1,
                'note_for' => '1',
                'update_status' => '1'
            );

            $this->M_Purchase->addNote($updatenote);
            $this->M_Purchase->hapus_tmp_nk($kduser);
            $this->M_Purchase->hapus_tmp_nk_nt_pembelian($kduser);

            $msg = "success";
            $data = array('msg' => $msg, 'nopo' => $kdnk);
            echo json_encode($data);
        }
    }

    public function edit_barang_tmp()
    {
        $id         = $this->input->post('id_isi', TRUE);
        $kdbarang   = $this->input->post('kd_isi', TRUE);
        $supp       = $this->input->post('kd_sup_isi', TRUE);
        $satuan     = $this->input->post('satuan_isi', TRUE);
        $qty        = $this->parseNumericInput($this->input->post('qty_isi', TRUE));
        $isBonus    = $this->isBonusInput($this->input->post('is_bonus'));
        $ppnMode    = strtolower(trim((string) $this->input->post('ppn_mode', TRUE)));
        $ppnMode    = in_array($ppnMode, array('exclude', 'include'), true) ? $ppnMode : 'exclude';
        $useRumusKgInput = $this->input->post('use_rumus_kg');
        $useRumusKg = $useRumusKgInput === null ? true : $useRumusKgInput === '1';
        $hargaInput = $this->parseNumericInput($this->input->post('hrg_isi', TRUE));
        $hrg_satuan = $isBonus ? 0 : $this->hargaKalkulasiByKeteranganPpn($hargaInput, $ppnMode);
        $bonusNote  = trim((string) $this->input->post('bonus_keterangan', TRUE));
        $total      = $qty * $hrg_satuan;
        $konversi   = $this->prepareKonversiBarang($kdbarang, $satuan, $qty, $hrg_satuan, $isBonus, $supp, $useRumusKg);
        $taxAktif   = $this->ppnPersen;

        if (!$isBonus && !$this->validateKeteranganHargaPpn($supp, $ppnMode, 'purchase/sup/' . $supp, $id)) {
            return;
        }

        if (!$konversi['status']) {
            $this->redirectWithError($konversi['message'], 'purchase/sup/' . $supp);
            return;
        }

        if (!$this->tableColumnsAvailable('tbpo_tmp_item', array('isi', 'kemasan', 'qty_kecil', 'harga_satuan_kecil'))) {
            $this->redirectWithError('Kolom konversi pada tbpo_tmp_item belum tersedia', 'purchase/sup/' . $supp);
            return;
        }

        $dataedit = array(
            'satuan'    => $satuan,
            'qty'       => $qty,
            'isi'       => $konversi['isi'],
            'kemasan'   => $konversi['kemasan'],
            'qty_kecil' => $konversi['qty_kecil'],
            'harga_satuan' => $hrg_satuan,
            'harga_satuan_kecil' => $konversi['harga_satuan_kecil'],
            'total_harga' => $total,
            'is_bonus' => $isBonus,
            'keterangan_bonus' => $isBonus ? $bonusNote : ''
        );

        if ($this->db->field_exists('harga_satuan_exclude', 'tbpo_tmp_item')) {
            $dataedit['harga_satuan_exclude'] = $isBonus ? 0 : $this->hargaKalkulasiExcludeByMode($hrg_satuan, $ppnMode, $taxAktif);
        }

        if ($this->db->field_exists('harga_satuan_kecil_exclude', 'tbpo_tmp_item')) {
            $dataedit['harga_satuan_kecil_exclude'] = $isBonus ? 0 : $this->hargaKalkulasiExcludeByMode($konversi['harga_satuan_kecil'], $ppnMode, $taxAktif);
        }

        if ($this->db->field_exists('keterangan_harga_ppn', 'tbpo_tmp_item')) {
            $dataedit['keterangan_harga_ppn'] = $isBonus ? '' : $ppnMode;
        }
        $this->db->trans_start();
        $this->M_Purchase->edit_chart_tmp($id, $dataedit);
        $this->syncTmpTaxByKeteranganHargaPpn($supp);
        $this->db->trans_complete();

        if ($this->db->trans_status() === FALSE) {
            $this->redirectWithError('Barang gagal diupdate', 'purchase/sup/' . $supp);
            return;
        }

        redirect('purchase/sup/' . $supp);
    }

    public function tmp_add_diskon_komersil()
    {
        $kdponk  =  $this->input->post();
        $desc    =  $this->input->post();
        $nominal =  $this->input->post();

        $datadskon = array(
            "kd_po_nk" => $kdponk,
            "deskripsi" => $desc,
            "nominal"   => $nominal
        );
        $this->M_Purchase->add_diskon_tmp($datadskon);
        redirect('pononkomersil/');
    }

    public function addnotebarangsupliertmp()
    {
        $supp       = $this->input->post('kd_sup');
        $isi        = $this->input->post('isi');

        $addnote = array(
            'kd_suplier'    => $supp,
            'isi_note'       => $isi
        );
        $this->M_Purchase->add_tmp_note_suplier($addnote);
        redirect('purchase/sup/' . $supp);
    }
    public function edit_note_tmp_barang()
    {
        $id         = $this->input->post('id_isi');
        $supp       = $this->input->post('kd_sup');
        $isi        = $this->input->post('isi');

        $note = array(
            'kd_suplier'    => $supp,
            'isi_note'       => $isi
        );
        $this->M_Purchase->edit_note_tmp_barang($id, $note);
        redirect('purchase/sup/' . $supp);
    }
    public function hapus_note_tmp_barang()
    {
        $id = $this->input->post('id_isi');
        $supp       = $this->input->post('kd_sup');
        $this->M_Purchase->hapus_note_tmp_barang($id);
        redirect('purchase/sup/' . $supp);
    }
    public function add_diskon_po()
    {
        $supp       = $this->input->post('kd_sup');
        $deskripsi  = $this->input->post('deskripsi_isi');
        $nominal    = $this->parseNumericInput($this->input->post('nominal_isi'));

        $addnote = array(
            'kd_suplier'    => $supp,
            'nama_diskon'   => $deskripsi,
            'nominal'       => $nominal
        );
        $this->M_Purchase->add_diskon_po($addnote);
        redirect('purchase/sup/' . $supp);
    }
    public function edit_diskon_po()
    {
        $id         = $this->input->post('id_isi');
        $supp       = $this->input->post('kd_sup');
        $deskripsi  = $this->input->post('deskripsi_isi');
        $rowMarker  = trim((string) $this->input->post('row_marker'));
        $merkMarker = trim((string) $this->input->post('merk_marker'));
        $satuanMarker = trim((string) $this->input->post('satuan_marker'));
        $diskonMerkMarker = trim((string) $this->input->post('diskon_merk_marker'));
        $nominal    = $this->parseNumericInput($this->input->post('nominal_isi'));
        $poTax      = $this->M_Purchase->gettmptax($supp);

        if ($merkMarker !== '' && $satuanMarker !== '') {
            $merkBarang = $this->getDiskonMerkMarker($merkMarker);
            $satuanDiskon = $this->getDiskonSatuanMarker($satuanMarker);
            $items = $this->M_Purchase->getTmpItemsByMerk($supp, $merkBarang);
            $tmpdiskon = array_filter($this->M_Purchase->getTmpDiskonOrder($supp), function ($diskon) use ($id) {
                return (int) $diskon->id_tmp_diskon !== (int) $id;
            });

            foreach ($items as $item) {
                $konversi = $this->hitungDiskonSatuanKecil($nominal, $satuanDiskon, isset($item->isi) ? $item->isi : 0, isset($item->kemasan) ? $item->kemasan : 0, $poTax);

                if (!$konversi['status']) {
                    $this->redirectWithError($konversi['message'] . ' Barang: ' . $item->nama_barang, 'purchase/sup/' . $supp);
                    return;
                }

                $hargaSatuanKecil = isset($item->harga_satuan_kecil) && (float) $item->harga_satuan_kecil > 0 ? $item->harga_satuan_kecil : $item->harga_satuan;
                $diskonResult = $this->hitungDiskonPerSatuanBarangResult($item->nama_barang, $item->qty, $tmpdiskon, $hargaSatuanKecil, $item->id_tmp, $merkBarang, $item, $poTax);
                $diskonBerjalan = $diskonResult['diskon_per_satuan'];
                if ((float) $hargaSatuanKecil - $diskonBerjalan - (float) $konversi['diskon_satuan_kecil'] < 0) {
                    $this->redirectWithError('Harga setelah diskon tidak boleh minus pada barang ' . $item->nama_barang, 'purchase/sup/' . $supp);
                    return;
                }
            }
        }

        if ($rowMarker !== '' && strpos($deskripsi, $rowMarker) === false) {
            $deskripsi .= ' ' . $rowMarker;
        }
        if ($merkMarker !== '' && strpos($deskripsi, $merkMarker) === false) {
            $deskripsi .= ' ' . $merkMarker;
        }
        if ($satuanMarker !== '' && strpos($deskripsi, $satuanMarker) === false) {
            $deskripsi .= ' ' . $satuanMarker;
        }
        if ($diskonMerkMarker !== '' && strpos($deskripsi, $diskonMerkMarker) === false) {
            $deskripsi .= ' ' . $diskonMerkMarker;
        }

        $addnote = array(
            'kd_suplier'    => $supp,
            'nama_diskon'   => $deskripsi,
            'nominal'       => $nominal
        );
        $this->M_Purchase->edit_diskon_po($id, $addnote);
        redirect('purchase/sup/' . $supp);
    }
    public function hapus_diskon_po()
    {
        $id = $this->input->post('id_isi');
        $supp       = $this->input->post('kd_sup');
        $this->M_Purchase->hapus_tmp_diskon($id);
        redirect('purchase/sup/' . $supp);
    }

    public function add_diskon_merk_tmp()
    {
        $kdsup = $this->input->post('kd_sup');
        $merkBarang = trim((string) $this->input->post('merk_barang'));
        $deskripsi = trim((string) $this->input->post('deskripsi_isi'));
        $satuanDiskon = $this->normalisasiSatuanDiskon($this->input->post('satuan_diskon'));
        $nominal = $this->parseNumericInput($this->input->post('nominal_isi'));
        $poTax = $this->M_Purchase->gettmptax($kdsup);

        if ($merkBarang === '') {
            $this->session->set_flashdata('error', 'Merk barang wajib dipilih.');
            redirect('purchase/sup/' . $kdsup);
            return;
        }

        if ($deskripsi === '') {
            $this->session->set_flashdata('error', 'Deskripsi diskon wajib diisi.');
            redirect('purchase/sup/' . $kdsup);
            return;
        }

        if ($satuanDiskon === '') {
            $this->session->set_flashdata('error', 'Satuan diskon wajib dipilih.');
            redirect('purchase/sup/' . $kdsup);
            return;
        }

        if ($nominal <= 0) {
            $this->session->set_flashdata('error', 'Nominal diskon harus numeric dan lebih besar dari 0.');
            redirect('purchase/sup/' . $kdsup);
            return;
        }

        $items = $this->M_Purchase->getTmpItemsByMerk($kdsup, $merkBarang);
        if (empty($items)) {
            $this->session->set_flashdata('error', 'Item PO untuk merk barang tersebut tidak ditemukan.');
            redirect('purchase/sup/' . $kdsup);
            return;
        }

        $tmpdiskon = $this->M_Purchase->getTmpDiskonOrder($kdsup);
        foreach ($items as $item) {
            $isi = isset($item->isi) ? $item->isi : 0;
            $kemasan = isset($item->kemasan) ? $item->kemasan : 0;
            $konversi = $this->hitungDiskonSatuanKecil($nominal, $satuanDiskon, $isi, $kemasan, $poTax);

            if (!$konversi['status']) {
                $this->session->set_flashdata('error', $konversi['message'] . ' Barang: ' . $item->nama_barang);
                redirect('purchase/sup/' . $kdsup);
                return;
            }

            $hargaSatuanKecil = isset($item->harga_satuan_kecil) && (float) $item->harga_satuan_kecil > 0 ? $item->harga_satuan_kecil : $item->harga_satuan;
            $diskonResult = $this->hitungDiskonPerSatuanBarangResult($item->nama_barang, $item->qty, $tmpdiskon, $hargaSatuanKecil, $item->id_tmp, $merkBarang, $item, $poTax);
            $diskonBerjalan = $diskonResult['diskon_per_satuan'];
            $hargaSetelahDiskon = (float) $hargaSatuanKecil - $diskonBerjalan - (float) $konversi['diskon_satuan_kecil'];

            if ($hargaSetelahDiskon < 0) {
                $this->session->set_flashdata('error', 'Harga setelah diskon tidak boleh minus pada barang ' . $item->nama_barang);
                redirect('purchase/sup/' . $kdsup);
                return;
            }
        }

        $tambahDiskon = array(
            'kd_suplier' => $kdsup,
            'nama_diskon' => $deskripsi . ' [MERK:' . $merkBarang . '] [SATUAN_DISKON:' . $satuanDiskon . ']',
            'nominal' => $nominal
        );

        $this->M_Purchase->add_diskon_po($tambahDiskon);
        redirect('purchase/sup/' . $kdsup);
    }

    public function add_diskon_barang_tmp()
    {
        $kdsup      = $this->input->post('kdsup');
        $nmbarang   = $this->input->post('nmbarang');
        $idTmp      = (int) $this->input->post('id_tmp');
        $tax        = $this->parseNumericInput($this->input->post('disc_isi'));
        $hargaA     = $this->parseNumericInput($this->input->post('hrg_satuan_kecil'));
        $tmpdiskon  = $this->M_Purchase->getTmpDiskonOrder($kdsup);
        $tmpItem    = $this->M_Purchase->getTmpItemById($idTmp);
        $merkBarang = $tmpItem && isset($tmpItem->merk_barang) ? $tmpItem->merk_barang : '';
        $poTax      = $this->M_Purchase->gettmptax($kdsup);
        $diskonResult = $this->hitungDiskonPerSatuanBarangResult($nmbarang, 1, $tmpdiskon, $hargaA, $idTmp, $merkBarang, $tmpItem, $poTax);
        $diskonPerSatuan = $diskonResult['diskon_per_satuan'];
        $hargaSetelahDiskon = max($hargaA - $diskonPerSatuan, 0);
        $hasiltax   = $tax / 100;
        $nominalTax = $hargaSetelahDiskon * $hasiltax;

        $tambahDiskon = array(
            'kd_suplier' => $kdsup,
            'nama_diskon' => 'Diskon Barang' . ' ' . '-' . ' ' . $nmbarang . ' ' . '(' . $tax . '%' . ') [ROW_TMP:' . $idTmp . ']',
            'nominal' => $nominalTax
        );

        $this->M_Purchase->add_diskon_po($tambahDiskon);
        redirect('purchase/sup/' . $kdsup);
    }
    public function add_diskon_barangs_tmp()
    {
        $kdsup      = $this->input->post('kdsup');
        $nmbarang   = $this->input->post('nmbarang');
        $idTmp      = (int) $this->input->post('id_tmp');
        $deskripsi  = $this->input->post('desc_isi');
        $nominal    = $this->parseNumericInput($this->input->post('disc_isi'));

        $tambahDiskon = array(
            'kd_suplier' => $kdsup,
            'nama_diskon' => $nmbarang . ' ' . '-' . ' ' . $deskripsi . ' [ROW_TMP:' . $idTmp . ']',
            'nominal' => $nominal
        );

        $this->M_Purchase->add_diskons_po($tambahDiskon);
        redirect('purchase/sup/' . $kdsup);
    }
}
