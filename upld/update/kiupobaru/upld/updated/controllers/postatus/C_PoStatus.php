<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 *
 */
class C_PoStatus extends CI_Controller

{
    private $bonusFlagValue = 1;
    private $ppnPersen = 11;

    function __construct()
    {
        parent::__construct();
        $this->load->model('PO/M_Postatus');
        $this->load->model('PO/M_Purchase');
        $this->load->helper('download');
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

    private function getPonkStatusRow($kdpo)
    {
        $status = $this->M_Postatus->getdataStatusnk($kdpo);
        return !empty($status) ? $status[0] : null;
    }

    private function canInputHargaNyata($status)
    {
        return in_array($status, array('ACC DIREKTUR', 'PROSES PEMBELIAN'), true);
    }

    private function validateHargaNyataBeforePembelian($kdpo, $statusRow)
    {
        if (!$statusRow || (int) $statusRow->status_hrg_nyata !== 1) {
            return array('status' => true, 'message' => '');
        }

        $summary = $this->M_Postatus->get_harga_nyata_summary($kdpo);
        if ($summary && (int) $summary->belum_input > 0) {
            return array(
                'status' => false,
                'message' => 'Lengkapi Qty Nyata dan Harga Nyata semua item sebelum proses pembelian.'
            );
        }

        return $this->M_Postatus->can_proses_pembelian_harga_nyata($kdpo);
    }

    private function excludePpn($value, $taxPercent)
    {
        $taxRate = $this->parseNumericInput($taxPercent) / 100;
        return $taxRate > 0 ? (float) $value / (1 + $taxRate) : (float) $value;
    }

    private function includePpn($value, $taxPercent)
    {
        $taxRate = $this->parseNumericInput($taxPercent) / 100;
        return $this->parseNumericInput($value) * (1 + $taxRate);
    }

    private function taxPercentForPpnSnapshot($ppnMode, $taxPercent)
    {
        $taxPercent = $this->parseNumericInput($taxPercent);

        if ($taxPercent <= 0 && strtolower(trim((string) $ppnMode)) === 'include') {
            return $this->ppnPersen;
        }

        return $taxPercent;
    }

    private function hargaExcludePpnByMode($harga, $ppnMode, $taxPercent)
    {
        $harga = $this->parseNumericInput($harga);

        return $ppnMode === 'include' ? $this->excludePpn($harga, $taxPercent) : $harga;
    }

    private function isTaxElevenPercent($taxPercent)
    {
        return abs($this->parseNumericInput($taxPercent) - 11) < 0.00001;
    }

    private function getKeteranganHargaPpnDetailPo($kdpo)
    {
        if (!$this->db->field_exists('keterangan_harga_ppn', 'tbpo_detail_po')) {
            return '';
        }

        $this->db->select('keterangan_harga_ppn');
        $this->db->from('tbpo_detail_po');
        $this->db->where('kd_po', $kdpo);
        $this->db->where("TRIM(COALESCE(keterangan_harga_ppn, '')) <> ''", null, false);

        if ($this->db->field_exists('is_bonus', 'tbpo_detail_po')) {
            $this->db->where('COALESCE(is_bonus, 0) = 0', null, false);
        }

        $this->db->order_by('id_det_po', 'ASC');
        $this->db->limit(1);
        $row = $this->db->get()->row();
        $mode = $row ? strtolower(trim((string) $row->keterangan_harga_ppn)) : '';

        return in_array($mode, array('exclude', 'include'), true) ? $mode : '';
    }

    private function validateKeteranganHargaPpnDetailPo($kdpo, $ppnMode, $redirectUrl)
    {
        $existingMode = $this->getKeteranganHargaPpnDetailPo($kdpo);

        if ($existingMode !== '' && $existingMode !== $ppnMode) {
            $this->session->set_flashdata('error', 'Data PO sudah menggunakan keterangan harga ' . strtoupper($existingMode) . ' PPN. Barang revisi harus menggunakan ' . strtoupper($existingMode) . ' PPN juga.');
            redirect($redirectUrl);
            return false;
        }

        return true;
    }

    private function diskonExcludeTax($nominal, $taxPercent)
    {
        $nominal = $this->parseNumericInput($nominal);
        $taxRate = $this->parseNumericInput($taxPercent) / 100;
        return $taxRate > 0 ? $nominal / (1 + $taxRate) : $nominal;
    }

    private function satuanPerluKonversi($satuan)
    {
        return in_array(strtolower(trim((string) $satuan)), array('box', 'ltr', 'kg'), true);
    }

    private function satuanPakaiKemasan($satuan)
    {
        return in_array(strtolower(trim((string) $satuan)), array('ltr', 'kg'), true);
    }

    private function hitungQtyHargaKecil($kodeBarang, $kodeSuplier, $satuan, $qty, $hargaSatuan)
    {
        $satuan = strtolower(trim((string) $satuan));
        $qty = $this->parseNumericInput($qty);
        $hargaSatuan = $this->parseNumericInput($hargaSatuan);

        if (!$this->satuanPerluKonversi($satuan)) {
            return array(
                'success' => true,
                'qty_kecil' => $qty,
                'harga_satuan_kecil' => $hargaSatuan,
            );
        }

        if (!$this->db->field_exists('isi', 'tbpo_barang') || !$this->db->field_exists('kemasan', 'tbpo_barang')) {
            return array(
                'success' => false,
                'message' => 'Data isi atau kemasan barang belum disetting',
            );
        }

        $barang = $this->M_Purchase->getBarangByKode($kodeBarang, $kodeSuplier);
        $isi = $barang && isset($barang->isi) ? $this->parseNumericInput($barang->isi) : 0;
        $kemasan = $barang && isset($barang->kemasan) ? $this->parseNumericInput($barang->kemasan) : 0;

        if ($this->satuanPakaiKemasan($satuan)) {
            if ($kemasan <= 0) {
                return array(
                    'success' => false,
                    'message' => 'Data kemasan barang belum disetting',
                );
            }

            $konversiKemasan = $kemasan / 1000;

            return array(
                'success' => true,
                'qty_kecil' => $qty / $konversiKemasan,
                'harga_satuan_kecil' => $hargaSatuan * $konversiKemasan,
                'isi' => $isi,
                'kemasan' => $kemasan,
            );
        }

        if ($isi <= 0) {
            return array(
                'success' => false,
                'message' => 'Data isi barang belum disetting',
            );
        }

        return array(
            'success' => true,
            'qty_kecil' => $isi * $qty,
            'harga_satuan_kecil' => $hargaSatuan / $isi,
            'isi' => $isi,
            'kemasan' => $kemasan,
        );
    }

    private function buildTrackingSnapshot($data)
    {
        if (empty($data)) {
            return null;
        }

        return json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    private function logPoActivity($kdpo, $activity, $oldData = null, $newData = null)
    {
        $namaUser = (string) $this->session->userdata('nama_user');
        $kodeUser = (string) $this->session->userdata('kode');
        $hasUserLogColumn = $this->db->field_exists('user_log', 'tbpo_tracking_po');
        $hasKodeUserColumn = $this->db->field_exists('kode_user', 'tbpo_tracking_po');
        $hasDataLamaColumn = $this->db->field_exists('data_lama', 'tbpo_tracking_po');
        $hasDataBaruColumn = $this->db->field_exists('data_baru', 'tbpo_tracking_po');
        $oldSnapshot = $oldData !== null ? $this->buildTrackingSnapshot($oldData) : null;
        $newSnapshot = $newData !== null ? $this->buildTrackingSnapshot($newData) : null;

        if (!$hasUserLogColumn || !$hasKodeUserColumn) {
            $sessionUser = trim($kodeUser . ' - ' . $namaUser, ' -');
            if ($sessionUser !== '') {
                $activity = $activity . ' | User: ' . $sessionUser;
            }
        }

        if (!$hasDataLamaColumn && $oldSnapshot !== null) {
            $activity = $activity . ' | Data Lama: ' . $oldSnapshot;
        }

        if (!$hasDataBaruColumn && $newSnapshot !== null) {
            $activity = $activity . ' | Data Baru: ' . $newSnapshot;
        }

        $logData = array(
            'kd_po' => $kdpo,
            'status' => $activity,
        );

        if ($hasUserLogColumn && $namaUser !== '') {
            $logData['user_log'] = $namaUser;
        }
        if ($hasKodeUserColumn && $kodeUser !== '') {
            $logData['kode_user'] = $kodeUser;
        }
        if ($hasDataLamaColumn && $oldSnapshot !== null) {
            $logData['data_lama'] = $oldSnapshot;
        }
        if ($hasDataBaruColumn && $newSnapshot !== null) {
            $logData['data_baru'] = $newSnapshot;
        }

        $this->M_Postatus->updateLog($logData);
    }

    public function index()
    {
        $data['title'] = 'PO Status';
        $data['po']    = $this->M_Postatus->getpotoday()->result();

        $this->load->view('partial/header', $data);
        $this->load->view('partial/sidebar');
        $this->load->view('content/postatus/bodytoday', $data);
        $this->load->view('partial/footer');
        $this->load->view('content/postatus/datatables');
    }

    public function postatus()
    {
        $data['title'] = 'PO Status';
        $data['po']    = $this->M_Postatus->getAll();

        $this->load->view('partial/header', $data);
        $this->load->view('partial/sidebar');
        $this->load->view('content/postatus/body', $data);
        $this->load->view('partial/footer');
        $this->load->view('content/postatus/datatables');
    }

    public function searchdatepo()
    {

        $date1  = $this->input->post('dt1');
        $date2  = $this->input->post('dt2');

        $data['title'] = 'PO Status';
        $data['response1'] = $date1;
        $data['response2'] = $date2;
        $data['po']    = $this->M_Postatus->srcdatepo($date1, $date2);

        $this->load->view('partial/header', $data);
        $this->load->view('partial/sidebar');
        $this->load->view('content/postatus/bodysearchdate', $data);
        $this->load->view('partial/footer');
        $this->load->view('content/postatus/datatables');
    }
    public function getOnProgress()
    {

        $data['title'] = 'PO Status On Progress';
        $data['po']    = $this->M_Postatus->getOnProgress();

        $this->load->view('partial/header', $data);
        $this->load->view('partial/sidebar');
        $this->load->view('content/postatus/bodyOnProgress', $data);
        $this->load->view('partial/footer');
        $this->load->view('content/postatus/datatables');
    }
    public function getDone()
    {

        $data['title'] = 'PO Status Done';
        $data['po']    = $this->M_Postatus->getDone();

        $this->load->view('partial/header', $data);
        $this->load->view('partial/sidebar');
        $this->load->view('content/postatus/bodyDone', $data);
        $this->load->view('partial/footer');
        $this->load->view('content/postatus/datatables');
    }
    public function getDoneToday()
    {
        $data['title'] = 'PO Status Done Today';
        $data['po']    = $this->M_Postatus->getDoneToday()->result();

        $this->load->view('partial/header', $data);
        $this->load->view('partial/sidebar');
        $this->load->view('content/postatus/bodytodaydone', $data);
        $this->load->view('partial/footer');
        $this->load->view('content/postatus/datatables');
    }
    public function getOnProgresToday()
    {
        $data['title'] = 'PO Status Done Today';
        $data['po']    = $this->M_Postatus->getOnProgressToday()->result();

        $this->load->view('partial/header', $data);
        $this->load->view('partial/sidebar');
        $this->load->view('content/postatus/bodytodayonprogress', $data);
        $this->load->view('partial/footer');
        $this->load->view('content/postatus/datatables');
    }
    public function getRejectToday()
    {
        $data['title'] = 'PO Status Done Today';
        $data['po']    = $this->M_Postatus->getRejectToday()->result();

        $this->load->view('partial/header', $data);
        $this->load->view('partial/sidebar');
        $this->load->view('content/postatus/bodytodayreject', $data);
        $this->load->view('partial/footer');
        $this->load->view('content/postatus/datatables');
    }

    public function getReject()
    {

        $data['title'] = 'PO Status Reject';
        $data['po']    = $this->M_Postatus->getReject();

        $this->load->view('partial/header', $data);
        $this->load->view('partial/sidebar');
        $this->load->view('content/postatus/bodyReject', $data);
        $this->load->view('partial/footer');
        $this->load->view('content/postatus/datatables');
    }

    public function detailPO($kdpo)
    {
        $data['title'] = 'PO Status';
        $this->syncDiskonDetailPO($kdpo);
        $data['detail'] = $this->M_Postatus->getDetail($kdpo);
        $data['status'] = $this->M_Postatus->getdataStatus($kdpo);
        $data['noted']  = $this->M_Postatus->getNoted($kdpo);
        $data['tax']    = $this->M_Postatus->getTax();
        $data['satuan'] = $this->M_Postatus->getSatuan();
        $data['log']    = $this->M_Postatus->getLog($kdpo);
        $data['total']  = $this->M_Postatus->sumTransaksiPenjualan($kdpo);
        $data['diskon'] = $this->M_Postatus->getDiskon($kdpo);
        $data['historiDiskon'] = $this->M_Postatus->getHistoriDiskonPo($kdpo);
        $data['nextNomorDiskon'] = $this->M_Postatus->getNextNomorDiskon($kdpo);
        $data['merkBarangPo'] = $this->M_Postatus->getMerkBarangPo($kdpo);
        $data['totalDiskon'] = $this->M_Postatus->totalDiskon($kdpo);
        $data['notebarang'] = $this->M_Postatus->get_note_barang($kdpo);
        $data['kdpo'] = $this->M_Postatus->kdpo($kdpo);
        $data['ntformat'] = $this->M_Postatus->ntformat();

        $this->load->view('partial/header', $data);
        $this->load->view('partial/sidebar');
        $this->load->view('content/postatus/detailpo', $data);
        $this->load->view('partial/footer');
        $this->load->view('content/postatus/ajaxstatus');
    }

    public function update_printout_po()
    {
        $kdpo   = $this->input->post('kdpo');
        $kdnt   = trim((string) $this->input->post('frmt_option'));

        if ($kdnt === '' || $kdnt === '-') {
            $this->session->set_flashdata('error', 'Shipment Setting belum dipilih. Silakan pilih format shipment terlebih dahulu.');
            redirect('detailPO/' . $kdpo);
            return;
        }

        $datanote = array(
            'kd_printout_note' => $kdnt
        );
        $this->M_Postatus->update_pr_po($kdpo, $datanote);

        redirect('detailPO/' . $kdpo);
    }
    public function unpostpo($kdpo)
    {
        $departement    = $this->session->userdata('kode');
        $namauser       = $this->session->userdata('nama_user');

        $addNoteKeuangan = array(
            'kd_po'     => $kdpo,
            'isi_note'  => 'UNPOST - PO',
            'kd_user'   => $departement,
            'nama_user'   => $namauser,
            'note_for'  => '1',
            'update_status' => '1'
        );

        $noteUpdateKeuangan = array(
            'status'    => 'ON PROGRESS'
        );
        $this->M_Postatus->addNote($addNoteKeuangan);
        $this->M_Postatus->updateStatus($kdpo, $noteUpdateKeuangan);

        redirect('detailPO/' . $kdpo);
    }


    public function repostpo()
    {
        date_default_timezone_set("Asia/Jakarta");
        $kdpolama   = $this->input->post('kd_lama');
        $nopo       = $this->input->post('nopo');
        $nmuser     = $this->session->userdata('nama_user');
        $user       = $this->session->userdata('kode');

        if (empty($kdpolama)) {
            echo json_encode(array('msg' => 'error'));
            return;
        }

        $rekamData = array(
            'status'        => 'PO REVISI'
        );
        $this->M_Postatus->updateStatus($kdpolama, $rekamData);

        $updatenote = array(
            'kd_po' => $kdpolama,
            'isi_note' => 'Revisi PO',
            'kd_user' => $user,
            'nama_user' => $nmuser,
            'note_for' => '1',
            'update_status' => '1'
        );
        $this->M_Postatus->addNote($updatenote);

        $msg = "success";
        $data = array('msg' => $msg, 'nopo' => $nopo);
        echo json_encode($data);
    }
    public function edit_no_po()
    {
        $idpo = $this->input->post('id_po');
        $kdpo = $this->input->post('kdpo');
        $nopo = strtoupper(trim((string) $this->input->post('nopo')));
        $status = $this->M_Postatus->getdataStatus($kdpo);
        $po = !empty($status) ? $status[0] : null;

        if (!$po) {
            $this->session->set_flashdata('error', 'Data PO tidak ditemukan.');
            redirect('detailPO/' . $kdpo);
            return;
        }

        if (!preg_match('/^[QA]\d{3}\/KIU\/(I|II|III|IV|V|VI|VII|VIII|IX|X|XI|XII)\/\d{4}[A-Z]?$/', $nopo)) {
            $this->session->set_flashdata('error', 'Format Nomor PO harus seperti Q001/KIU/VII/2026 atau Q001/KIU/VII/2026A.');
            redirect('detailPO/' . $kdpo);
            return;
        }

        if ($this->nomorPoExistsExceptCurrent($nopo, $kdpo)) {
            $this->session->set_flashdata('error', 'Nomor PO sudah digunakan. Silakan gunakan nomor berikutnya atau suffix alfabet yang tersedia.');
            redirect('detailPO/' . $kdpo);
            return;
        }

        if ($this->nomorPoBaseSupplierExistsExceptCurrent($nopo, $po->kd_suplier, $kdpo)) {
            $this->session->set_flashdata('error', 'Nomor PO sudah digunakan supplier ini. Silakan gunakan nomor berikutnya.');
            redirect('detailPO/' . $kdpo);
            return;
        }

        $dataedited = array(
            'no_po' => $nopo
        );

        $this->M_Postatus->editnopo($idpo, $dataedited);
        $this->M_Postatus->editnopodet($kdpo, $dataedited);
        $this->session->set_flashdata('success', 'Nomor PO berhasil diperbarui.');
        redirect('detailPO/' . $kdpo);
    }

    private function nomorPoExistsExceptCurrent($noPo, $kdpo)
    {
        $this->db->from('tbpo_po');
        $this->db->where('no_po', trim((string) $noPo));
        $this->db->where('kd_po !=', $kdpo);
        return $this->db->count_all_results() > 0;
    }

    private function nomorPoBaseSupplierExistsExceptCurrent($noPo, $kdSuplier, $kdpo)
    {
        $noPo = strtoupper(trim((string) $noPo));
        if (!preg_match('/^([QA])(\d{3})\/KIU\/([IVXLCDM]+)\/(\d{4})([A-Z]?)$/', $noPo, $matches)) {
            return false;
        }

        $this->db->from('tbpo_po');
        $this->db->where('kd_suplier', $kdSuplier);
        $this->db->where('kd_po !=', $kdpo);
        $this->db->where("no_po REGEXP '^" . $this->db->escape_str($matches[1] . $matches[2]) . "/KIU/" . $this->db->escape_str($matches[3]) . "/" . $this->db->escape_str($matches[4]) . "[A-Z]?$'", null, false);
        return $this->db->count_all_results() > 0;
    }
    public function hapuspo($kdpo)
    {
        $this->M_Postatus->deletepo($kdpo);
        $this->M_Postatus->deletepodet($kdpo);
        redirect('postatus');
    }

    public function printOrder($kdpo)
    {
        $data = $this->getPrintOrderData($kdpo);

        $this->load->view('partial/header', $data);
        $this->load->view('content/postatus/printorder', $data);
        $this->load->view('partial/footerprint');
    }

    private function normalizePrintPpnMode($mode)
    {
        $mode = strtolower(trim((string) $mode));
        return $mode === 'exclude' ? 'exclude' : 'include';
    }

    public function print_po($kdpo, $ppnMode = 'include')
    {
        $this->syncDiskonDetailPO($kdpo);
        $data = $this->getPrintOrderData($kdpo, true);
        $data['printPpnMode'] = $this->normalizePrintPpnMode($ppnMode);
        $data['printSummary'] = $this->buildPrintPoSummary($data['status'], $data['total'], $data['totalDiskon'], $data['detail']);

        $this->load->view('partial/header', $data);
        $this->load->view('content/postatus/print_po_internal', $data);
        $this->load->view('partial/footerprint');
    }

    public function print_po_supplier($kdpo, $ppnMode = 'include')
    {
        $this->syncDiskonDetailPO($kdpo);
        $data = $this->getPrintOrderData($kdpo, true);
        $data['hideBonusDiscountRows'] = true;
        $data['isSupplierPrint'] = true;
        $data['printPpnMode'] = $this->normalizePrintPpnMode($ppnMode);

        $this->load->view('partial/header', $data);
        $this->load->view('content/postatus/printorder', $data);
        $this->load->view('partial/footerprint');
    }

    private function getPrintOrderData($kdpo, $useInternalPrintStatus = false)
    {
        return array(
            'title' => 'PRINT ORDER',
            'detail' => $this->M_Postatus->getDetail($kdpo),
            'status' => $useInternalPrintStatus ? $this->M_Postatus->getDataStatusPrint($kdpo) : $this->M_Postatus->getDataStatuss($kdpo),
            'total' => $this->M_Postatus->sumTransaksiPenjualan($kdpo),
            'CountItem' => $this->M_Postatus->CountItem($kdpo)->result(),
            'diskon' => $this->M_Postatus->getDiskon($kdpo),
            'totalDiskon' => $this->M_Postatus->totalDiskon($kdpo),
            'totalnote' => $this->M_Postatus->totalnote($kdpo),
            'notesuplier' => $this->M_Postatus->get_note_barang($kdpo)
        );
    }

    private function buildPrintPoSummary($status, $total, $totalDiskon, $detail = array())
    {
        $po = !empty($status) ? $status[0] : null;
        $totalHargaTanpaDiskon = !empty($total) ? (float) $total[0]->total_harga : 0;
        $totalHargaDenganDiskon = 0;
        if (!empty($detail)) {
            foreach ($detail as $item) {
                $isBonus = isset($item->is_bonus) && (int) $item->is_bonus === 1;
                $totalHargaDenganDiskon += $isBonus ? 0 : (isset($item->total_harga_setelah_diskon) && (float) $item->total_harga_setelah_diskon > 0 ? (float) $item->total_harga_setelah_diskon : (float) $item->hrg_total);
            }
        } else {
            $totalDiskonNominal = !empty($totalDiskon) ? (float) $totalDiskon[0]->total_diskon : 0;
            $totalHargaDenganDiskon = max($totalHargaTanpaDiskon - $totalDiskonNominal, 0);
        }
        $taxPersen = $po ? (float) $po->tax : 0;
        $taxRate = $taxPersen / 100;
        $taxTanpaDiskon = $totalHargaTanpaDiskon * $taxRate;
        $taxDenganDiskon = $totalHargaDenganDiskon * $taxRate;

        return array(
            'total_harga_tanpa_diskon' => $totalHargaTanpaDiskon,
            'total_harga_dengan_diskon' => $totalHargaDenganDiskon,
            'tax_persen' => $taxPersen,
            'tax_tanpa_diskon' => $taxTanpaDiskon,
            'tax_dengan_diskon' => $taxDenganDiskon,
            'grand_total_tanpa_diskon' => $totalHargaTanpaDiskon + $taxTanpaDiskon,
            'grand_total_dengan_diskon' => $totalHargaDenganDiskon + $taxDenganDiskon
        );
    }

    public function printOrdernk($kdpo)
    {
        $data['title'] = 'PRINT ORDER';
        $data['detail'] = $this->M_Postatus->getDetailnk($kdpo);
        $data['status'] = $this->M_Postatus->getDataStatussnk($kdpo)->result();
        $data['total']  = $this->M_Postatus->sumTransaksiPenjualannk($kdpo);
        $data['CountItem'] = $this->M_Postatus->CountItem($kdpo)->result();
        $data['diskon'] = $this->M_Postatus->getDiskonnk($kdpo);
        $data['totalDiskon'] = $this->M_Postatus->totalDiskon($kdpo);
        $data['notepm'] = $this->M_Postatus->get_note_pembelian($kdpo);

        $this->load->view('partial/header', $data);
        $this->load->view('content/postatus/printordernk', $data);
        $this->load->view('partial/footerprint');
    }

    public function printOrderBaru($kdpo)
    {
        $data['title'] = 'PRINT ORDER';
        $data['detail'] = $this->M_Postatus->getDetail($kdpo);
        $data['status'] = $this->M_Postatus->getdataStatus($kdpo);
        $data['total']  = $this->M_Postatus->sumTransaksiPenjualan($kdpo);
        $data['diskon'] = $this->M_Postatus->getDiskon($kdpo);
        $data['totalDiskon'] = $this->M_Postatus->totalDiskon($kdpo);

        $this->load->view('partial/headerprint', $data);
        $this->load->view('content/postatus/printorderBaru', $data);
        $this->load->view('partial/footerPrint');
    }

    public function onhandpo($kdpo)
    {
        $validation = $this->validateOnHandPo($kdpo);

        if (!$validation['success']) {
            $this->session->set_flashdata('error', $validation['message']);
            redirect('detailPO/' . $kdpo);
            return;
        }

        $result = $this->processOnHandPo($kdpo);
        if (!$result['success']) {
            $this->session->set_flashdata('error', $result['message']);
            redirect('detailPO/' . $kdpo);
            return;
        }

        redirect('postatus');
    }

    public function onhandpo_ajax()
    {
        $kdpo = $this->input->post('kdpo');

        $validation = $this->validateOnHandPo($kdpo);
        if (!$validation['success']) {
            echo json_encode($validation);
            return;
        }

        $result = $this->processOnHandPo($kdpo);
        echo json_encode($result);
    }

    private function validateOnHandPo($kdpo)
    {
        if ((int) $this->session->userdata('lv') >= 3) {
            return array(
                'success' => false,
                'message' => 'Anda tidak memiliki akses untuk update ON HAND.'
            );
        }

        $status = $this->M_Postatus->getdataStatus($kdpo);
        $po = !empty($status) ? $status[0] : null;

        if (!$po) {
            return array(
                'success' => false,
                'message' => 'Data PO tidak ditemukan.'
            );
        }

        if ($po->status != 'ON DELIVERY') {
            return array(
                'success' => false,
                'message' => 'Status order terbaru bukan ON DELIVERY.',
                'status' => $po->status
            );
        }

        $shipment = trim((string) $po->kd_printout_note);
        if ($shipment == '' || $shipment == '-') {
            return array(
                'success' => false,
                'message' => 'Silakan pilih / setting format shipment terlebih dahulu.',
                'status' => $po->status
            );
        }

        return array(
            'success' => true,
            'message' => 'OK',
            'status' => $po->status
        );
    }

    private function processOnHandPo($kdpo)
    {
        date_default_timezone_set("Asia/Jakarta");
        $itemconfirm    = $this->M_Postatus->getitemreq($kdpo)->result();
        $now            = date('Y-m-d');
        $now1           = date('Y-m-d h:m:s');

        $updatestatus = array(
            'kd_po'     => $kdpo,
            'status'    => 'DONE'
        );
        $notedirektur = array(
            'kd_po'     => $kdpo,
            'isi_note'  => 'PO DONE',
            'kd_user'   => $this->session->userdata('kode'),
            'nama_user' => $this->session->userdata('nama_user'),
            'note_for'  => '1',
            'update_status' => '1'
        );

        if (!$itemconfirm) {
            return array(
                'success' => false,
                'message' => 'Data item PO tidak ditemukan.'
            );
        }

        $this->M_Postatus->konfirmPo($kdpo, $updatestatus);
        $this->M_Postatus->addNote($notedirektur);

        foreach ($itemconfirm as $t) {
            $inserttransaksi = array(
                'kd_akun'           => '11411',
                'kd_po_nk'          => $t->kdpo,
                'kd_barang'         => $t->kdbarang,
                'kd_barangsys'      => 'KDPO',
                'keterangan'        => 'KETPO',
                'kat_barang'        => 'POKOMERSIL',
                'tr_qty'            => $t->qty,
                'satuan'            => $t->satuan,
                'tgl_transaksi'     => $now,
                'inputer'           => $this->session->userdata('kode'),
                'req_by'            => 'PONONKOMERSIL',
                'create_at'         => $now1,
                'last_updated_by'   => $this->session->userdata('kode')
            );
            $this->M_Postatus->input_tr($inserttransaksi);
        }

        return array(
            'success' => true,
            'message' => 'Status PO berhasil diubah menjadi DONE.',
            'status' => 'DONE'
        );
    }

    public function porepost()
    {
        $kdpoid         = $this->input->post('kdpo');
        $notes          = $this->input->post('noteDitektur');
        $departement    = $this->session->userdata('kode');
        $namauser       = $this->session->userdata('nama_user');

        $dataKonfirm = array(
            'kd_po_nk' => $kdpoid,
            'status' => 'ON PROGRESS'
        );
        $notedirektur = array(
            'kd_po'     => $kdpoid,
            'isi_note'  => 'PO REPOST - ' . $notes,
            'kd_user'   => $departement,
            'nama_user'   => $namauser,
            'note_for'  => '1',
            'update_status' => '1'
        );

        $this->M_Postatus->pendingordernk($kdpoid, $dataKonfirm);
        $this->M_Postatus->addNote($notedirektur);
        redirect('postatusnk');
    }

    public function poconfirmacc($kdpo)
    {
        $isAjax          = $this->input->is_ajax_request();
        $departement    = $this->session->userdata('kode');
        $namauser       = $this->session->userdata('nama_user');
        $status         = $this->M_Postatus->getdataStatus($kdpo);
        $po             = !empty($status) ? $status[0] : null;

        if (!$po || $po->status !== 'ACC DIREKTUR') {
            if ($isAjax) {
                echo json_encode(array('success' => false, 'message' => 'Status PO tidak valid untuk proses PO CONFIRM.'));
                return;
            }
            $this->session->set_flashdata('error', 'Status PO tidak valid untuk proses PO CONFIRM.');
            redirect('detailPO/' . $kdpo);
            return;
        }

        $shipment = trim((string) $po->kd_printout_note);
        if ($shipment === '' || $shipment === '-') {
            if ($isAjax) {
                echo json_encode(array('success' => false, 'message' => 'Shipment Setting belum dipilih. Silakan pilih format shipment terlebih dahulu.'));
                return;
            }
            $this->session->set_flashdata('error', 'Shipment Setting belum dipilih. Silakan pilih format shipment terlebih dahulu.');
            redirect('detailPO/' . $kdpo);
            return;
        }

        $dataKonfirm = array(
            'kd_po'     => $kdpo,
            'status'    => 'DONE'
        );

        $notedirektur = array(
            'kd_po'     => $kdpo,
            'isi_note'  => 'PO - DONE',
            'kd_user'   => $departement,
            'nama_user'   => $namauser,
            'note_for'  => '1',
            'update_status' => '1'
        );

        $this->M_Postatus->konfirmPo($kdpo, $dataKonfirm);
        $this->M_Postatus->addNote($notedirektur);

        if ($isAjax) {
            echo json_encode(array('success' => true, 'message' => 'Data PO telah diselesaikan.'));
            return;
        }

        redirect('detailPO/' . $kdpo);
    }

    public function konfirmasiOrder($kdpo, $kddirektur)
    {
        $departement    = $this->session->userdata('kode');
        $namauser       = $this->session->userdata('nama_user');

        $dataKonfirm = array(
            'kd_po' => $kdpo,
            'acc_with' => $kddirektur,
            'status' => 'ACC DIREKTUR'
        );

        $notedirektur = array(
            'kd_po'     => $kdpo,
            'isi_note'  => 'PO ACCEPT',
            'kd_user'   => $departement,
            'nama_user'   => $namauser,
            'note_for'  => '1',
            'update_status' => '1'
        );

        $this->M_Postatus->konfirmPo($kdpo, $dataKonfirm);
        $this->M_Postatus->addNote($notedirektur);
        redirect('postatus');
    }

    public function tolakOrder($kdpo, $kddirektur)
    {
        $departement    = $this->session->userdata('kode');
        $namauser       = $this->session->userdata('nama_user');

        $dataKonfirm = array(
            'kd_po' => $kdpo,
            'acc_with' => $kddirektur,
            'status' => 'REJECT'
        );
        $notedirektur = array(
            'kd_po'     => $kdpo,
            'isi_note'  => 'PO REJECT',
            'kd_user'   => $departement,
            'nama_user'   => $namauser,
            'note_for'  => '1',
            'update_status' => '1'
        );

        $this->M_Postatus->tolakPo($kdpo, $dataKonfirm);
        $this->M_Postatus->addNote($notedirektur);
        redirect('postatus');
    }

    public function cancel_po()
    {
        $kdpo           = $this->input->post('kdpo');
        $kddirektur     = $this->session->userdata('kode');
        $namauser       = $this->session->userdata('nama_user');
        $nt_cancel      = $this->input->post('nt_cancel');

        $dataKonfirm = array(
            'kd_po' => $kdpo,
            'acc_with' => $kddirektur,
            'status' => 'CANCEL'
        );
        $notedirektur = array(
            'kd_po'     => $kdpo,
            'isi_note'  => 'PO CANCEL - ' . $nt_cancel,
            'kd_user'   => $kddirektur,
            'nama_user'   => $namauser,
            'note_for'  => '1',
            'update_status' => '1'
        );

        $this->M_Postatus->tolakPo($kdpo, $dataKonfirm);
        $this->M_Postatus->addNote($notedirektur);
        redirect('postatus');
    }

    public function addNote()
    {
        $kdpo           = $this->input->post('kdpo');
        $note           = $this->input->post('note_direktur');
        $stslogin       = $this->session->userdata('lv');
        $departement    = $this->session->userdata('kode');
        $namauser       = $this->session->userdata('nama_user');

        if ($stslogin == '2') {
            $addNoteKeuangan = array(
                'kd_po'     => $kdpo,
                'isi_note'  => $note,
                'kd_user'   => $departement,
                'nama_user'   => $namauser,
                'note_for'  => '2',
                'update_status' => 'ON PROGRESS'
            );

            $noteUpdateKeuangan = array(
                'status'    => 'ON PROGRESS'
            );

            $this->M_Postatus->addNote($addNoteKeuangan);
            $this->M_Postatus->updateStatus($kdpo, $noteUpdateKeuangan);
        } else if ($stslogin == '3') {
            $addnoteDirektur = array(
                'kd_po'     => $kdpo,
                'isi_note'  => $note,
                'kd_user'   => $departement,
                'nama_user'   => $namauser,
                'note_for'  => '2',
                'update_status' => '1'
            );
            $noteUpdateDirektur = array(
                'status'    => 'NOTE DIREKTUR'
            );
            $this->M_Postatus->addNote($addnoteDirektur);
            $this->M_Postatus->updateStatus($kdpo, $noteUpdateDirektur);
        }
        redirect('detailPO/' . $kdpo);
    }

    public function shipment_to()
    {
        $kddpo          = $this->input->post('update_shipment');
        $shipment_to    = $this->input->post('template_isi');
        $printMode      = $this->normalizePrintPpnMode($this->input->post('print_mode'));

        $dataupdated = array(
            'kd_printout_note'  => $shipment_to
        );

        $this->M_Postatus->updateshipment($kddpo, $dataupdated);

        redirect('print_po_supplier/' . $kddpo . '/' . $printMode);
    }

    public function revisiPO()
    {
        $idpo       = $this->input->post('idpo');
        $kdpo       = $this->input->post('kdpo');
        $satuan     = $this->input->post('satuan_isi');
        $qty        = $this->parseNumericInput($this->input->post('qty_isi'));
        $isBonus    = $this->isBonusInput($this->input->post('is_bonus'));
        $hargaQty   = $isBonus ? 0 : $this->parseNumericInput($this->input->post('hrg_isi'));
        $bonusNote  = trim((string) $this->input->post('bonus_keterangan'));
        $hargahasil = $hargaQty * $qty;
        $oldItem    = $this->M_Postatus->getDetailItemById($idpo);
        $status     = $this->M_Postatus->getdataStatus($kdpo);
        $tax        = !empty($status) && isset($status[0]->tax) ? $status[0]->tax : 0;
        $taxForConversion = (float) $tax > 0 ? $tax : 11;
        $ppnMode = $oldItem && isset($oldItem->keterangan_harga_ppn) ? strtolower(trim((string) $oldItem->keterangan_harga_ppn)) : '';
        $ppnMode = in_array($ppnMode, array('exclude', 'include'), true) ? $ppnMode : 'exclude';
        $konversi   = $oldItem ? $this->hitungQtyHargaKecil($oldItem->kd_barang, $oldItem->kd_suplier, $satuan, $qty, $hargaQty) : array(
            'success' => true,
            'qty_kecil' => $qty,
            'harga_satuan_kecil' => $hargaQty,
        );

        if (!$konversi['success']) {
            $this->session->set_flashdata('error', $konversi['message']);
            redirect('detailPO/' . $kdpo);
            return;
        }

        $hargaSatuanExclude = $isBonus ? 0 : ($ppnMode === 'include' ? $this->excludePpn($hargaQty, $taxForConversion) : $hargaQty);
        $hargaSatuanKecilExclude = $isBonus ? 0 : ($ppnMode === 'include' ? $this->excludePpn($konversi['harga_satuan_kecil'], $taxForConversion) : $konversi['harga_satuan_kecil']);

        $data = array(
            'satuan'        => $satuan,
            'qty'           => $qty,
            'qty_kecil'     => $konversi['qty_kecil'],
            'isi'           => isset($konversi['isi']) ? $konversi['isi'] : 0,
            'kemasan'       => isset($konversi['kemasan']) ? $konversi['kemasan'] : 0,
            'hrg_satuan'    => $hargaQty,
            'harga_satuan_exclude' => $hargaSatuanExclude,
            'harga_satuan_kecil' => $konversi['harga_satuan_kecil'],
            'harga_satuan_kecil_exclude' => $hargaSatuanKecilExclude,
            'hrg_diskon'    => $hargaSatuanKecilExclude,
            'hrg_total'     => $hargahasil,
            'hrg_total_diskon' => $hargaSatuanKecilExclude * $konversi['qty_kecil'],
            'is_bonus'      => $isBonus,
            'keterangan_bonus' => $isBonus ? $bonusNote : '',
        );

        $this->M_Postatus->revisiPO($idpo, $data);
        $this->syncDiskonDetailPO($kdpo);
        $this->logPoActivity(
            $kdpo,
            'Edit detail PO: ' . ($oldItem ? $oldItem->nama_barang : ('ID ' . $idpo)),
            $oldItem ? array(
                'satuan' => $oldItem->satuan,
                'qty' => $oldItem->qty,
                'qty_kecil' => isset($oldItem->qty_kecil) ? $oldItem->qty_kecil : $oldItem->qty,
                'hrg_satuan' => $oldItem->hrg_satuan,
                'harga_satuan_exclude' => isset($oldItem->harga_satuan_exclude) ? $oldItem->harga_satuan_exclude : null,
                'harga_satuan_kecil' => isset($oldItem->harga_satuan_kecil) ? $oldItem->harga_satuan_kecil : $oldItem->hrg_satuan,
                'harga_satuan_kecil_exclude' => isset($oldItem->harga_satuan_kecil_exclude) ? $oldItem->harga_satuan_kecil_exclude : null,
                'is_bonus' => isset($oldItem->is_bonus) ? $oldItem->is_bonus : 0,
                'keterangan_bonus' => isset($oldItem->keterangan_bonus) ? $oldItem->keterangan_bonus : null,
                'keterangan_harga_ppn' => isset($oldItem->keterangan_harga_ppn) ? $oldItem->keterangan_harga_ppn : null,
            ) : null,
            array(
                'satuan' => $satuan,
                'qty' => $qty,
                'qty_kecil' => $konversi['qty_kecil'],
                'hrg_satuan' => $hargaQty,
                'harga_satuan_exclude' => $hargaSatuanExclude,
                'harga_satuan_kecil' => $konversi['harga_satuan_kecil'],
                'harga_satuan_kecil_exclude' => $hargaSatuanKecilExclude,
                'is_bonus' => $isBonus,
                'keterangan_bonus' => $isBonus ? $bonusNote : null,
                'keterangan_harga_ppn' => $ppnMode,
            )
        );
        redirect('detailPO/' . $kdpo);
    }

    public function listBarangRevisi($kdsuplier, $kdpo)
    {
        $data['title']          = 'Add Item List';
        $data['status']         = $this->M_Postatus->getdataStatus($kdpo);
        $data['kode_suplier']   = $this->M_Purchase->Suplier($kdsuplier)->result();
        $data['barang']         = $this->M_Purchase->getBarangSup($kdsuplier)->result();
        $data['tax']            = $this->M_Purchase->getTax();
        $data['satuan']         = $this->M_Purchase->getSatuan();
        $data['tmp']            = $this->M_Purchase->getTmpOrder($kdsuplier);
        $data['detail_po_keterangan_harga_ppn'] = $this->getKeteranganHargaPpnDetailPo($kdpo);

        $this->load->view('partial/header', $data);
        $this->load->view('partial/sidebar');
        $this->load->view('content/postatus/listbarangRevisi', $data);
        $this->load->view('partial/footer');
        $this->load->view('content/po/datatables');
        $this->load->view('content/postatus/ajaxstatus');
    }

    public function tambahBarangRevisi()
    {
        $kdpo       = $this->input->post('kd_po');
        $nopo       = $this->input->post('no_po');
        $tgl        = $this->input->post('tgl_transaksi');
        $suplier    = $this->input->post('kd_sup');
        $kdbarang   = $this->input->post('kd_isi');
        $nmbarang   = $this->input->post('nama_isi');
        $satuan     = $this->input->post('satuan_isi');
        $qty        = $this->parseNumericInput($this->input->post('qty_isi'));
        $isBonus    = $this->isBonusInput($this->input->post('is_bonus'));
        $bonusNote  = trim((string) $this->input->post('bonus_keterangan'));
        $status     = $this->M_Postatus->getdataStatus($kdpo);
        $tax        = !empty($status) && isset($status[0]->tax) ? $status[0]->tax : 0;
        $ppnMode    = strtolower(trim((string) $this->input->post('ppn_mode')));
        $ppnMode    = in_array($ppnMode, array('exclude', 'include'), true) ? $ppnMode : 'exclude';

        if (!$isBonus && !$this->validateKeteranganHargaPpnDetailPo($kdpo, $ppnMode, 'addBarangRevisi/' . $suplier . '/' . $kdpo)) {
            return;
        }

        $hargaInput = $this->parseNumericInput($this->input->post('hrg_isi'));
        $hargaQty   = $isBonus ? 0 : $hargaInput;
        $hargahasil = $hargaQty * $qty;
        $konversi   = $this->hitungQtyHargaKecil($kdbarang, $suplier, $satuan, $qty, $hargaQty);

        if (!$konversi['success']) {
            $this->session->set_flashdata('error', $konversi['message']);
            redirect('addBarangRevisi/' . $suplier . '/' . $kdpo);
            return;
        }

        $taxForConversion = (float) $tax > 0 ? $tax : 11;
        $hargaSatuanKecilExclude = $isBonus ? 0 : $this->hargaExcludePpnByMode($konversi['harga_satuan_kecil'], $ppnMode, $taxForConversion);
        $hargaSatuanExclude = $isBonus ? 0 : $this->hargaExcludePpnByMode($hargaQty, $ppnMode, $taxForConversion);

        $data = array(
            'kd_po'         => $kdpo,
            'no_po'         => $nopo,
            'tgl_transaksi' => $tgl,
            'kd_suplier'    => $suplier,
            'kd_barang'     => $kdbarang,
            'nama_barang'   => $nmbarang,
            'satuan'        => $satuan,
            'qty'           => $qty,
            'qty_kecil'     => $konversi['qty_kecil'],
            'isi'           => isset($konversi['isi']) ? $konversi['isi'] : 0,
            'kemasan'       => isset($konversi['kemasan']) ? $konversi['kemasan'] : 0,
            'hrg_satuan'    => $hargaQty,
            'harga_satuan_exclude' => $hargaSatuanExclude,
            'harga_satuan_kecil' => $konversi['harga_satuan_kecil'],
            'harga_satuan_kecil_exclude' => $hargaSatuanKecilExclude,
            'hrg_diskon'    => $hargaSatuanKecilExclude,
            'hrg_total'     => $hargahasil,
            'hrg_total_diskon' => $hargaSatuanKecilExclude * $konversi['qty_kecil'],
            'is_bonus'      => $isBonus,
            'keterangan_bonus' => $isBonus ? $bonusNote : '',
            'keterangan_harga_ppn' => $isBonus ? '' : $ppnMode,

        );
        $this->M_Postatus->addRevisiChart($data);
        $this->syncDiskonDetailPO($kdpo);
        $this->logPoActivity($kdpo, 'Tambah item ' . ($isBonus ? 'bonus' : 'PO') . ': ' . $nmbarang, null, $data);
        redirect('detailPO/' . $kdpo);
    }

    public function NoteUpdateKeuangan()
    {
        $kdpo   = $this->input->post('kdpo');
        $note   = $this->input->post('noteDitektur');
        $nama   = $this->session->userdata('nama_user');

        $addNote = array(
            'kd_po'     => $kdpo,
            'kd_user'   => $nama,
            'isi_note'  => $note
        );

        $updateStatus = array(
            'status'    => 'UPDATE'
        );

        $updateLog = array(
            'kd_po'     => $kdpo,
            'status'    => 'UPDATE'
        );

        $this->M_Postatus->updateLog($updateLog);
        $this->M_Postatus->addNote($addNote);
        $this->M_Postatus->updateStatus($kdpo, $updateStatus);

        redirect('detailPO/' . $kdpo);
    }

    public function hapusBarangPO($id, $kdpo)
    {
        $oldItem = $this->M_Postatus->getDetailItemById($id);
        $this->M_Postatus->hapusBarang($id);
        $this->syncDiskonDetailPO($kdpo);
        $this->logPoActivity($kdpo, 'Hapus detail PO: ' . ($oldItem ? $oldItem->nama_barang : ('ID ' . $id)), $oldItem ? (array) $oldItem : null, null);
        redirect('detailPO/' . $kdpo);
    }

    public function tambahTax()
    {
        $kdpo       = $this->input->post('kdpo');
        $tax        = $this->parseNumericInput($this->input->post('tax_isi_status'));

        if ($tax < 0) {
            $this->session->set_flashdata('error', 'Nilai tax tidak boleh kurang dari 0.');
            redirect('detailPO/' . $kdpo);
            return;
        }

        $updateHarga = array(
            'kd_po' => $kdpo,
            'tax'   => $tax
        );

        $this->M_Postatus->updateTax($kdpo, $updateHarga);
        $this->syncDiskonDetailPO($kdpo);
        $this->logPoActivity($kdpo, 'Update tax PO', null, $updateHarga);

        redirect('detailPO/' . $kdpo);
    }

    public function tempoPembayaran()
    {
        $kdpo       = $this->input->post('kdpo');
        $hari        = $this->input->post('tempo_isi');

        $updateTempo = array(
            'kd_po' => $kdpo,
            'tmpo_pembayaran'   => $hari
        );

        $this->M_Postatus->updateTax($kdpo, $updateTempo);
        $this->logPoActivity($kdpo, 'Update tempo pembayaran', null, $updateTempo);

        redirect('detailPO/' . $kdpo);
    }

    public function frankoPengiriman()
    {
        $kdpo       = $this->input->post('kdpo');
        $gdg        = $this->input->post('gdg_isi');

        $updateFranko = array(
            'kd_po' => $kdpo,
            'gdg_pengiriman'   => $gdg
        );

        $this->M_Postatus->updateTax($kdpo, $updateFranko);
        $this->logPoActivity($kdpo, 'Update franko pengiriman', null, $updateFranko);

        redirect('detailPO/' . $kdpo);
    }

    public function tambahDiskon()
    {
        $kdpo = $this->input->post('kdpo');
        $keterangan = $this->normalisasiKeteranganDiskon($this->input->post('keterangan_isi'));
        $nominal = $this->parseNumericInput($this->input->post('nominal_isi'));

        $addDiskon = array(
            'kd_po'         => $kdpo,
            'keterangan'    => $keterangan,
            'nominal'       => $nominal
        );

        $this->M_Postatus->insertDiskon($addDiskon);
        $this->syncDiskonDetailPO($kdpo);
        $this->logPoActivity($kdpo, 'Tambah diskon PO', null, $addDiskon);

        redirect('detailPO/' . $kdpo);
    }

    private function normalisasiKeteranganDiskon($keterangan)
    {
        $keterangan = trim((string) $keterangan);
        $keterangan = preg_replace('/^Diskon\s+\d+(?:\s*-\s*)?/i', '', $keterangan);

        return trim($keterangan);
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

    private function getDiskonMerkIdMarker($text)
    {
        if (preg_match('/\[DISKON_MERK:(\d+)\]/', (string) $text, $match)) {
            return (int) $match[1];
        }

        return null;
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
            return array('success' => false, 'message' => 'Satuan diskon wajib dipilih.');
        }

        if ($nominal <= 0) {
            return array('success' => false, 'message' => 'Nominal diskon harus numeric dan lebih besar dari 0.');
        }

        if ($isi <= 0) {
            return array('success' => false, 'message' => 'Data isi barang belum disetting.');
        }

        if ($satuanDiskon === 'BOX') {
            $diskonSatuanKecil = $nominal / $isi;
        } elseif ($satuanDiskon === 'PCS') {
            $diskonSatuanKecil = $nominal;
        } else {
            if ($kemasan <= 0) {
                return array('success' => false, 'message' => 'Data kemasan barang belum disetting.');
            }

            $diskonSatuanKecil = $nominal * ($kemasan / 1000);
        }

        return array(
            'success' => true,
            'diskon_satuan_kecil' => $diskonSatuanKecil,
        );
    }

    private function diskonPerSatuanDetailPOResult($namaBarang, $qty, $diskonList, $hargaSatuanKecil = 0, $idDetPo = null, $merkBarang = '', $item = null, $taxPercent = 0)
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
            $diskonRowDet = $this->getDiskonRowMarker($diskon->keterangan, 'DET');
            if ($diskonRowDet !== null && $idDetPo !== null && $diskonRowDet !== (int) $idDetPo) {
                continue;
            }

            $diskonMerk = $this->getDiskonMerkMarker($diskon->keterangan);
            if ($diskonMerk !== null) {
                if ($merkBarang === '' || strcasecmp($diskonMerk, $merkBarang) !== 0) {
                    continue;
                }

                $satuanDiskon = $this->getDiskonSatuanMarker($diskon->keterangan);
                $isi = $item && isset($item->isi) ? $item->isi : 0;
                $kemasan = $item && isset($item->kemasan) ? $item->kemasan : 0;
                $konversi = $this->hitungDiskonSatuanKecil($diskon->nominal, $satuanDiskon, $isi, $kemasan, $taxPercent);

                if (!$konversi['success']) {
                    continue;
                }

                $diskonSatuanKecil = min((float) $konversi['diskon_satuan_kecil'], $hargaBerjalan);
                $hargaBerjalan -= $diskonSatuanKecil;
                $hargaBerjalan = max($hargaBerjalan, 0);
                $metadata = array(
                    'id_diskon_merk' => $this->getDiskonMerkIdMarker($diskon->keterangan),
                    'satuan_diskon' => $satuanDiskon,
                    'nominal_diskon' => (float) $diskon->nominal,
                    'diskon_satuan_kecil' => $diskonSatuanKecil,
                );
                continue;
            }

            $prefixDiskonNominal = $namaBarang . ' - ';
            $prefixDiskonPersen = 'Diskon Barang - ' . $namaBarang . ' ';
            $prefixDiskonPersenDetail = 'Diskon Barang-' . $namaBarang . '(';

            if (strpos($diskon->keterangan, $prefixDiskonNominal) === 0) {
                $hargaBerjalan -= $this->diskonExcludeTax($diskon->nominal, $taxPercent);
                $hargaBerjalan = max($hargaBerjalan, 0);
            } elseif (strpos($diskon->keterangan, $prefixDiskonPersen) === 0 || strpos($diskon->keterangan, $prefixDiskonPersenDetail) === 0) {
                $persenDiskon = $this->getPersentaseDiskon($diskon->keterangan);
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

    private function diskonPerSatuanDetailPO($namaBarang, $qty, $diskonList, $hargaSatuanKecil = 0, $idDetPo = null, $merkBarang = '', $taxPercent = 0)
    {
        $result = $this->diskonPerSatuanDetailPOResult($namaBarang, $qty, $diskonList, $hargaSatuanKecil, $idDetPo, $merkBarang, null, $taxPercent);
        return $result['diskon_per_satuan'];
    }

    private function syncDiskonDetailPO($kdpo)
    {
        $detail = $this->M_Postatus->getDetail($kdpo);
        $diskon = $this->M_Postatus->getDiskon($kdpo);
        $status = $this->M_Postatus->getdataStatus($kdpo);
        $tax = !empty($status) && isset($status[0]->tax) ? $status[0]->tax : 0;
        $taxForConversion = (float) $tax > 0 ? $tax : 11;
        $totalHarga = 0;
        $totalHargaDiskon = 0;
        $totalHargaInclude = 0;
        $totalHargaExlude = 0;
        $totalHargaDiskonInclude = 0;
        $totalHargaDiskonExlude = 0;
        $keteranganHargaPpnHeader = $this->getKeteranganHargaPpnDetailPo($kdpo);

        foreach ($detail as $item) {
            $isBonus = isset($item->is_bonus) ? (int) $item->is_bonus : 0;
            $qtyKecil = isset($item->qty_kecil) && (float) $item->qty_kecil > 0 ? $item->qty_kecil : $item->qty;
            $keteranganHargaPpn = isset($item->keterangan_harga_ppn) ? strtolower(trim((string) $item->keterangan_harga_ppn)) : '';
            $hargaSatuanExclude = isset($item->harga_satuan_exclude) && ((float) $item->harga_satuan_exclude > 0 || $isBonus)
                ? $item->harga_satuan_exclude
                : ($keteranganHargaPpn === 'include' ? $this->excludePpn($item->hrg_satuan, $taxForConversion) : $item->hrg_satuan);
            $hargaSatuanKecilSimpan = isset($item->harga_satuan_kecil) && ((float) $item->harga_satuan_kecil > 0 || $isBonus) ? $item->harga_satuan_kecil : $item->hrg_satuan;
            $hargaSatuanKecil = isset($item->harga_satuan_kecil_exclude) && ((float) $item->harga_satuan_kecil_exclude > 0 || $isBonus)
                ? $item->harga_satuan_kecil_exclude
                : ($keteranganHargaPpn === 'include' ? $this->excludePpn($hargaSatuanKecilSimpan, $taxForConversion) : $hargaSatuanKecilSimpan);
            $merkBarang = isset($item->merk_barang) ? $item->merk_barang : '';
            $diskonResult = $isBonus
                ? array('diskon_per_satuan' => 0, 'metadata' => array('id_diskon_merk' => null, 'satuan_diskon' => null, 'nominal_diskon' => 0, 'diskon_satuan_kecil' => 0))
                : $this->diskonPerSatuanDetailPOResult($item->nama_barang, $item->qty, $diskon, $hargaSatuanKecil, $item->id_det_po, $merkBarang, $item, $tax);
            $diskonPerSatuan = $diskonResult['diskon_per_satuan'];
            $diskonMetadata = $diskonResult['metadata'];
            $hargaDiskonInclude = $isBonus ? 0 : max($hargaSatuanKecil - $diskonPerSatuan, 0);
            $hargaSatuanKecilExclude = $isBonus ? 0 : $hargaSatuanKecil;
            $hargaDiskon = $isBonus ? 0 : $hargaDiskonInclude;
            $hargaTotalDiskon = $isBonus ? 0 : ($hargaDiskon * $qtyKecil);
            $taxSnapshot = $this->taxPercentForPpnSnapshot($keteranganHargaPpn, $tax);
            $hargaTotalExlude = $isBonus ? 0 : ($hargaSatuanKecilExclude * $qtyKecil);
            $hargaTotalInclude = $isBonus ? 0 : ($keteranganHargaPpn === 'include'
                ? $item->hrg_total
                : $this->includePpn($hargaTotalExlude, $taxSnapshot));
            $hargaTotalDiskonExlude = $hargaTotalDiskon;
            $hargaTotalDiskonInclude = $isBonus ? 0 : $this->includePpn($hargaTotalDiskonExlude, $taxSnapshot);
            $totalHarga += $isBonus ? 0 : ($hargaSatuanKecil * $qtyKecil);
            $totalHargaDiskon += $hargaTotalDiskon;
            $totalHargaInclude += $hargaTotalInclude;
            $totalHargaExlude += $hargaTotalExlude;
            $totalHargaDiskonInclude += $hargaTotalDiskonInclude;
            $totalHargaDiskonExlude += $hargaTotalDiskonExlude;

            if ($keteranganHargaPpnHeader === '' && in_array($keteranganHargaPpn, array('include', 'exclude'), true)) {
                $keteranganHargaPpnHeader = $keteranganHargaPpn;
            }

            $this->M_Postatus->update_diskon_item($item->id_det_po, array(
                'harga_satuan_exclude' => $isBonus ? 0 : $hargaSatuanExclude,
                'harga_satuan_kecil_exclude' => $hargaSatuanKecilExclude,
                'hrg_diskon' => $hargaDiskon,
                'hrg_total_diskon' => $hargaTotalDiskon,
                'id_diskon_merk' => $diskonMetadata['id_diskon_merk'],
                'satuan_diskon' => $diskonMetadata['satuan_diskon'],
                'nominal_diskon' => $diskonMetadata['nominal_diskon'],
                'diskon_satuan_kecil' => $diskonMetadata['diskon_satuan_kecil'],
                'harga_satuan_kecil_setelah_diskon' => $hargaDiskonInclude,
                'total_harga_setelah_diskon' => $hargaDiskonInclude * $qtyKecil
            ));
        }

        $this->M_Postatus->updateTax($kdpo, array(
            'total_harga' => $totalHarga,
            'total_harga_diskon' => $totalHargaDiskon,
            'keterangan_harga_ppn' => $keteranganHargaPpnHeader,
            'total_harga_include' => $totalHargaInclude,
            'total_harga_exlude' => $totalHargaExlude,
            'total_harga_diskon_include' => $totalHargaDiskonInclude,
            'total_harga_diskon_exlude' => $totalHargaDiskonExlude,
            'hrg_pajak' => $totalHargaDiskon * ((float) $tax / 100)
        ));
    }

    public function editDiskon()
    {
        $iddiskon  = $this->input->post('id_diskon');
        $kdpo = $this->input->post('kdpo');
        $keterangan = $this->normalisasiKeteranganDiskon($this->input->post('keterangan_isi'));
        $rowMarker = trim((string) $this->input->post('row_marker'));
        $merkMarker = trim((string) $this->input->post('merk_marker'));
        $satuanMarker = trim((string) $this->input->post('satuan_marker'));
        $diskonMerkMarker = trim((string) $this->input->post('diskon_merk_marker'));
        $nominal = $this->parseNumericInput($this->input->post('nominal_isi'));
        $status = $this->M_Postatus->getdataStatus($kdpo);
        $poTax = !empty($status) && isset($status[0]->tax) ? $status[0]->tax : 0;

        if ($merkMarker !== '' && $satuanMarker !== '') {
            $merkBarang = $this->getDiskonMerkMarker($merkMarker);
            $satuanDiskon = $this->getDiskonSatuanMarker($satuanMarker);
            $items = $this->M_Postatus->get_items_po_by_merk($kdpo, $merkBarang);
            $diskon = array_filter($this->M_Postatus->getDiskon($kdpo), function ($itemDiskon) use ($iddiskon) {
                return (int) $itemDiskon->id_diskon !== (int) $iddiskon;
            });

            foreach ($items as $item) {
                $konversi = $this->hitungDiskonSatuanKecil($nominal, $satuanDiskon, isset($item->isi) ? $item->isi : 0, isset($item->kemasan) ? $item->kemasan : 0, $poTax);

                if (!$konversi['success']) {
                    $this->session->set_flashdata('error', $konversi['message'] . ' Barang: ' . $item->nama_barang);
                    redirect('detailPO/' . $kdpo);
                    return;
                }

                $hargaSatuanKecil = isset($item->harga_satuan_kecil) && (float) $item->harga_satuan_kecil > 0 ? $item->harga_satuan_kecil : $item->hrg_satuan;
                $diskonResult = $this->diskonPerSatuanDetailPOResult($item->nama_barang, $item->qty, $diskon, $hargaSatuanKecil, $item->id_det_po, $merkBarang, $item, $poTax);
                $diskonBerjalan = $diskonResult['diskon_per_satuan'];
                if ((float) $hargaSatuanKecil - $diskonBerjalan - (float) $konversi['diskon_satuan_kecil'] < 0) {
                    $this->session->set_flashdata('error', 'Harga setelah diskon tidak boleh minus pada barang ' . $item->nama_barang);
                    redirect('detailPO/' . $kdpo);
                    return;
                }
            }
        }

        if ($rowMarker !== '' && strpos($keterangan, $rowMarker) === false) {
            $keterangan .= ' ' . $rowMarker;
        }
        if ($merkMarker !== '' && strpos($keterangan, $merkMarker) === false) {
            $keterangan .= ' ' . $merkMarker;
        }
        if ($satuanMarker !== '' && strpos($keterangan, $satuanMarker) === false) {
            $keterangan .= ' ' . $satuanMarker;
        }
        if ($diskonMerkMarker !== '' && strpos($keterangan, $diskonMerkMarker) === false) {
            $keterangan .= ' ' . $diskonMerkMarker;
        }

        $editDiskon = array(
            'keterangan'    => $keterangan,
            'nominal'       => $nominal
        );

        $this->M_Postatus->editDiskon($iddiskon, $editDiskon);
        $idDiskonMerk = $this->getDiskonMerkIdMarker($diskonMerkMarker);
        if ($idDiskonMerk !== null) {
            $this->M_Postatus->update_diskon_merk($idDiskonMerk, array('nominal_diskon' => $nominal));
        }
        $this->syncDiskonDetailPO($kdpo);
        $this->logPoActivity($kdpo, 'Edit diskon PO', null, $editDiskon);

        redirect('detailPO/' . $kdpo);
    }

    public function hapusDiskon($id, $kdpo)
    {
        $this->M_Postatus->hapusDiskon($id);
        $this->syncDiskonDetailPO($kdpo);
        $this->logPoActivity($kdpo, 'Hapus diskon PO ID ' . $id);
        redirect('detailPO/' . $kdpo);
    }
    public function hapusDiskonNK($id, $kdponk)
    {
        $this->M_Postatus->hapusDiskonNK($id);
        redirect('detailponk/' . $kdponk);
    }

    public function postatusallnk()
    {
        $data['title'] = 'PO Status';
        $data['po']    = $this->M_Postatus->getAllNk()->result();

        $this->load->view('partial/header', $data);
        $this->load->view('partial/sidebar');
        $this->load->view('content/postatus/nonkomersilstatusall', $data);
        $this->load->view('partial/footer');
        $this->load->view('content/postatus/datatables');
    }

    public function postatusnk()
    {
        //VIEW-PURCHASING
        if (is_super_admin()) {

            $data['title'] = 'PO Status';
            $kd =  $this->session->userdata('kode');
            $tglstart   = $this->input->post('tglstart');
            $tglend     = $this->input->post('tglend');
            $_SESSION['vartgl1'] = $tglstart;
            $_SESSION['vartgl2'] = $tglend;

            $data['po']      = $this->M_Postatus->getAllNk()->result();
            $data['ponk']    = $this->M_Postatus->ponkgetAllNK_keu_purchasing($kd)->result();

            $this->load->view('partial/header', $data);
            $this->load->view('partial/sidebar');
            $this->load->view('content/postatus/nonkomersilstatus', $data);
            $this->load->view('partial/footer');
            $this->load->view('content/postatus/datatables');
        }

        //VIEW-PURCHASING
        elseif ($this->session->userdata('lv') == '2') {

            $data['title'] = 'PO Status';
            $dp = $this->session->userdata('departemen');
            $lv = $this->session->userdata('level');
            $kd =  $this->session->userdata('kode');
            $tglstart   = $this->input->post('tglstart');
            $tglend     = $this->input->post('tglend');
            $_SESSION['vartgl1'] = $tglstart;
            $_SESSION['vartgl2'] = $tglend;

            $data['po']    = $this->M_Postatus->getAllNK_keu_purchasing()->result();
            $data['ponk']    = $this->M_Postatus->ponkgetAllNK_keu_purchasing($kd)->result();

            $this->load->view('partial/header', $data);
            $this->load->view('partial/sidebar');
            $this->load->view('content/postatus/nonkomersilstatus', $data);
            $this->load->view('partial/footer');
            $this->load->view('content/postatus/datatables');
        }

        //VIEW-DIREKTUR
        elseif (in_array((string) $this->session->userdata('lv'), array('3', '6'), true)) {

            $data['title'] = 'PO Status';
            $dp = $this->session->userdata('departemen');
            $lv = $this->session->userdata('level');

            $data['po']    = $this->M_Postatus->getAllNK_direktur()->result();

            $this->load->view('partial/header', $data);
            $this->load->view('partial/sidebar');
            $this->load->view('content/postatus/nonkomersilstatus', $data);
            $this->load->view('partial/footer');
            $this->load->view('content/postatus/datatables');
        }

        //VIEW-KARYAWAN 
        elseif ($this->session->userdata('lv') == '4') {

            $data['title'] = 'PO Status';
            $dp = $this->session->userdata('departemen');
            $lv = $this->session->userdata('level');
            $kd = $this->session->userdata('kode');

            $data['po']    = $this->M_Postatus->getAllNK_kar($kd)->result();

            $this->load->view('partial/header', $data);
            $this->load->view('partial/sidebar');
            $this->load->view('content/postatus/nonkomersilstatus', $data);
            $this->load->view('partial/footer');
            $this->load->view('content/postatus/datatables');
        } elseif ($this->session->userdata('lv') == '5' && $this->session->userdata('kode') == 'KADEP02') {

            $data['title'] = 'PO Status';
            $dp = $this->session->userdata('departemen');
            $lv = $this->session->userdata('level');

            $data['po']    = $this->M_Postatus->getall_nk_promosi_seed()->result();
            $data['ponk']    = $this->M_Postatus->getAllNK_keu()->result();

            $this->load->view('partial/header', $data);
            $this->load->view('partial/sidebar');
            $this->load->view('content/postatus/nonkomersilstatus', $data);
            $this->load->view('partial/footer');
            $this->load->view('content/postatus/datatables');
        }

        //VIEW-KADEP-SEED
        elseif ($this->session->userdata('lv') == '5' && $this->session->userdata('kode') == 'KADEP08') {

            $data['title'] = 'PO Status';

            $dp = $this->session->userdata('departemen');
            $lv = $this->session->userdata('level');
            $data['po']    = $this->M_Postatus->getall_nk_kadep_keu_sales()->result();
            $data['ponk']    = $this->M_Postatus->getAllNK_keu()->result();

            $this->load->view('partial/header', $data);
            $this->load->view('partial/sidebar');
            $this->load->view('content/postatus/nonkomersilstatus', $data);
            $this->load->view('partial/footer');
            $this->load->view('content/postatus/datatables');
        }

        //VIEW-KADEP-SEED
        elseif ($this->session->userdata('lv') == '5' && $this->session->userdata('kode') == 'KADEP07') {

            $data['title'] = 'PO Status';

            $dp = $this->session->userdata('departemen');
            $lv = $this->session->userdata('level');
            $data['po']    = $this->M_Postatus->getall_nk_promosi_seed()->result();
            $data['ponk']    = $this->M_Postatus->getAllNK_keu()->result();

            $this->load->view('partial/header', $data);
            $this->load->view('partial/sidebar');
            $this->load->view('content/postatus/nonkomersilstatus', $data);
            $this->load->view('partial/footer');
            $this->load->view('content/postatus/datatables');
        }

        //VIEW-KADEP-SEED
        elseif ($this->session->userdata('lv') == '5' && $this->session->userdata('kode') == 'KADEP09') {

            $data['title'] = 'PO Status';

            $dp = $this->session->userdata('departemen');
            $lv = $this->session->userdata('level');
            $data['po']    = $this->M_Postatus->getall_nk_promosi_cp()->result();
            $data['ponk']    = $this->M_Postatus->getAllNK_keu()->result();

            $this->load->view('partial/header', $data);
            $this->load->view('partial/sidebar');
            $this->load->view('content/postatus/nonkomersilstatus', $data);
            $this->load->view('partial/footer');
            $this->load->view('content/postatus/datatables');
        }

        //VIEW-KADEP-SEED
        elseif ($this->session->userdata('lv') == '5' && $this->session->userdata('kode') == 'KADEP10') {

            $data['title'] = 'PO Status';

            $dp = $this->session->userdata('departemen');
            $lv = $this->session->userdata('level');
            $data['po']    = $this->M_Postatus->getAllNK_keu_purchasing()->result();
            $data['ponk']    = $this->M_Postatus->getAllNK_keu()->result();

            $this->load->view('partial/header', $data);
            $this->load->view('partial/sidebar');
            $this->load->view('content/postatus/nonkomersilstatus', $data);
            $this->load->view('partial/footer');
            $this->load->view('content/postatus/datatables');
        }

        //VIEW-KADEP-KEUANGAN
        elseif ($this->session->userdata('lv') == '5' && $this->session->userdata('departemen') == 'KEUANGAN') {

            $data['title'] = 'PO Status';
            $dp = $this->session->userdata('departemen');
            $lv = $this->session->userdata('level');

            $data['po']    = $this->M_Postatus->getAllNK_kadep($dp)->result();
            $data['ponk']    = $this->M_Postatus->getAllNK_keu()->result();

            $this->load->view('partial/header', $data);
            $this->load->view('partial/sidebar');
            $this->load->view('content/postatus/nonkomersilstatus', $data);
            $this->load->view('partial/footer');
            $this->load->view('content/postatus/datatables');
        }

        //VIEW-KADEP-GA
        elseif ($this->session->userdata('lv') == '5' && $this->session->userdata('kode') == 'KADEP05') {

            $data['title'] = 'PO Status';
            $dp = $this->session->userdata('departemen');
            $lv = $this->session->userdata('level');

            $data['po']    = $this->M_Postatus->getAllNK_kadep($dp)->result();
            $data['ponk']    = $this->M_Postatus->getAllNK_keu()->result();

            $this->load->view('partial/header', $data);
            $this->load->view('partial/sidebar');
            $this->load->view('content/postatus/nonkomersilstatus', $data);
            $this->load->view('partial/footer');
            $this->load->view('content/postatus/datatables');
        }

        //VIEW-KADEP-LOGISTIK
        elseif ($this->session->userdata('lv') == '5' && $this->session->userdata('kode') == 'KADEP03') {

            $data['title'] = 'PO Status';
            $dp = $this->session->userdata('departemen');
            $lv = $this->session->userdata('level');

            $data['po']    = $this->M_Postatus->getAllNK_kadep($dp)->result();
            $data['ponk']    = $this->M_Postatus->getAllNK_keu()->result();

            $this->load->view('partial/header', $data);
            $this->load->view('partial/sidebar');
            $this->load->view('content/postatus/nonkomersilstatus', $data);
            $this->load->view('partial/footer');
            $this->load->view('content/postatus/datatables');
        }

        // VIEW KADEP != KEUANGAN
        elseif ($this->session->userdata('lv') == '5' && $this->session->userdata('departemen') != 'KEUANGAN') {
            $data['title'] = 'PO Status';
            $dp = $this->session->userdata('departemen');
            $lv = $this->session->userdata('level');

            $data['po']    = $this->M_Postatus->getall_nk_kadep_keu_sales()->result();
            $data['ponk']    = $this->M_Postatus->getAllNK_keu()->result();

            $this->load->view('partial/header', $data);
            $this->load->view('partial/sidebar');
            $this->load->view('content/postatus/nonkomersilstatus', $data);
            $this->load->view('partial/footer');
            $this->load->view('content/postatus/datatables');
        }
    }

    public function stsviewpo($stsget)
    {
        $data['title'] = 'PO Status';

        $sts  = $stsget;
        $dp = $this->session->userdata('kode');

        if ($sts == '1') {
            $data['po']    = $this->M_Postatus->getuserdone($dp, $sts)->result();
            $this->load->view('partial/header', $data);
            $this->load->view('partial/sidebar');
            $this->load->view('content/postatus/viewhistoryponk', $data);
            $this->load->view('partial/footer');
            $this->load->view('content/postatus/datatables');
        } else if ($sts == '2') {
            $data['po']    = $this->M_Postatus->getuserdone($dp, $sts)->result();
            $this->load->view('partial/header', $data);
            $this->load->view('partial/sidebar');
            $this->load->view('content/postatus/viewhistoryponk', $data);
            $this->load->view('partial/footer');
            $this->load->view('content/postatus/datatables');
        }
    }

    public function detailponk($kd)
    {
        $data['title'] = 'PO Status';
        $data['kd'] = $kd;
        $data['detail'] = $this->M_Postatus->getDetailnk($kd);
        $data['status'] = $this->M_Postatus->getdataStatusnk($kd);
        $data['log']    = $this->M_Postatus->getNoted($kd);
        $data['total']  = $this->M_Postatus->sumTransaksiPenjualannk($kd);
        $data['totalnyata']  = $this->M_Postatus->sumharganyata($kd);
        $data['kdbarang']  = $this->M_Postatus->generatekd();
        $data['flupload']  = $this->M_Postatus->flupload($kd)->result();
        $data['fluploadbukti']  = $this->M_Postatus->fluploadbukti($kd)->result();
        $data['picSupportingDocuments'] = $this->M_Postatus->get_pic_supporting_documents($kd);
        $data['tax']    = $this->M_Postatus->getTax();
        $data['diskon'] = $this->M_Postatus->getDiskon($kd);
        $data['totalDiskon'] = $this->M_Postatus->totalDiskon($kd);
        $data['hrgnyata'] = $this->M_Postatus->counhrgnyata($kd);
        $data['ntpembelian'] = $this->M_Postatus->get_note_pembelian($kd);
        $data['hargaNyataSummary'] = $this->M_Postatus->get_harga_nyata_summary($kd);
        $data['manualMasterItems'] = $this->M_Postatus->get_manual_master_required_for_ponk($kd);
        $data['manualMasterCode'] = $this->M_Postatus->generate_unique_manual_master_code();
        $data['kategoriBarangNk'] = $this->M_Postatus->get_kategori_barang_nk();
        $data['satuanBarangNk'] = $this->M_Postatus->get_satuan_barang_nk();

        $this->load->view('partial/header', $data);
        $this->load->view('partial/sidebar');
        $this->load->view('content/postatus/detailponk', $data);
        $this->load->view('partial/footer');
    }

    public function simpan_master_barang_manual_pojasa()
    {
        if (!is_super_admin() && !in_array((string) $this->session->userdata('lv'), array('1', '2'), true)) {
            show_error('Akses ditolak.', 403);
            return;
        }

        $kdpo = trim((string) $this->input->post('kd_po_nk', true));
        $materialId = (int) $this->input->post('id_material', true);
        $result = $this->M_Postatus->save_manual_master_barang_pojasa($kdpo, $materialId, array(
            'kat_barang' => $this->input->post('kat_barang', true),
            'nama_barang' => $this->input->post('nama_barang', true),
            'descnk' => $this->input->post('descnk', true),
            'satuan' => $this->input->post('satuan', true),
        ), (string) $this->session->userdata('kode'));

        $this->session->set_flashdata($result['status'] ? 'success' : 'error', $result['message']);
        redirect('detailponk/' . $kdpo);
    }

    private function blockedPonkEditStatuses()
    {
        return array(
            'PENGAJUAN DIBATALKAN'
        );
    }

    private function canUpdatePonkPengajuan($status)
    {
        return !in_array($status, $this->blockedPonkEditStatuses(), true);
    }

    private function responseJson($status, $message)
    {
        return $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode(array(
                'status' => $status,
                'message' => $message
            )));
    }

    private function validatePonkForAjax($kd_po_req)
    {
        $kodeUser = $this->session->userdata('kode');

        if (empty($kodeUser)) {
            return array(false, null, 'Session login tidak valid');
        }

        if (empty($kd_po_req)) {
            return array(false, null, 'Kode PO request tidak boleh kosong');
        }

        $ponk = $this->M_Postatus->get_ponk_by_req($kd_po_req);
        if (empty($ponk)) {
            return array(false, null, 'Data PO NK tidak ditemukan');
        }

        if (!$this->canUpdatePonkPengajuan($ponk->status)) {
            return array(false, $ponk, 'Status pengajuan tidak dapat diproses');
        }

        return array(true, $ponk, '');
    }

    public function cancel_pengajuan_ponk()
    {
        $kd_po_req = $this->input->post('kd_po_req', true);
        list($isValid, $ponk, $message) = $this->validatePonkForAjax($kd_po_req);

        if (!$isValid) {
            return $this->responseJson(false, $message);
        }

        $updated = $this->M_Postatus->cancel_pengajuan_ponk($ponk->kd_po_req);
        if (!$updated) {
            return $this->responseJson(false, 'Data gagal diperbarui');
        }

        $this->M_Postatus->addNote(array(
            'kd_po' => $ponk->kd_po_nk,
            'isi_note' => 'PO CANCEL - PENGAJUAN DIBATALKAN',
            'kd_user' => $this->session->userdata('kode'),
            'nama_user' => $this->session->userdata('nama_user'),
            'note_for' => '1',
            'update_status' => '1'
        ));

        return $this->responseJson(true, 'Data berhasil diperbarui');
    }

    public function update_tujuan_pembelian_ponk()
    {
        $kd_po_req = $this->input->post('kd_po_req', true);
        $tujuan_pembelian = trim((string) $this->input->post('tujuan_pembelian', true));
        list($isValid, $ponk, $message) = $this->validatePonkForAjax($kd_po_req);

        if (!$isValid) {
            return $this->responseJson(false, $message);
        }

        if ($tujuan_pembelian === '') {
            return $this->responseJson(false, 'Tujuan pembelian tidak boleh kosong');
        }

        $updated = $this->M_Postatus->update_tujuan_pembelian_ponk($ponk->kd_po_req, $tujuan_pembelian);
        if (!$updated) {
            return $this->responseJson(false, 'Data gagal diperbarui');
        }

        $this->M_Postatus->addNote(array(
            'kd_po' => $ponk->kd_po_nk,
            'isi_note' => 'EDIT DATA TUJUAN PEMBELIAN',
            'kd_user' => $this->session->userdata('kode'),
            'nama_user' => $this->session->userdata('nama_user'),
            'note_for' => '1',
            'update_status' => '1'
        ));

        return $this->responseJson(true, 'Data berhasil diperbarui');
    }

    public function edited_fk_nk()
    {
        $kdponk = $this->input->post('kdponk');
        $nmuser = $this->input->post('nm_pengaju');
        $dep    = $this->input->post('dep_isi');
        $tjpem  = $this->input->post('tj_pem');

        $dataedit = array(
            'nm_user'       => $nmuser,
            'departemen'    => $dep,
            'tj_pembelian'  => $tjpem
        );

        $this->M_Postatus->editedponk($kdponk, $dataedit);
        redirect('detailponk/' . $kdponk);
    }
    public function add_item_faktur_nk()
    {
        $kdponk     = $this->input->post('kdponk');
        $kduser     = $this->session->userdata('kode');
        $tgltrsk    = $this->input->post('tgltransaksi');
        $kdbarang   = $this->input->post('kd_adm');
        $kdsys      = $this->input->post('kd_system');
        $nmbarang   = $this->input->post('nmbarang');
        $descbarang = $this->input->post('descisi');
        $ketbarang  = $this->input->post('ketbarang');
        $qtybr      = $this->input->post('qtyisi');
        $lastHarga  = $this->M_Postatus->get_last_harga_barang_nk($kdbarang, $kdsys);
        $hrgsatuan  = $this->parseNumericInput($this->input->post('hrgisi'));
        if ($hrgsatuan <= 0 && $lastHarga && (float) $lastHarga->hrg_satuan > 0) {
            $hrgsatuan = (float) $lastHarga->hrg_satuan;
        }
        $totalhrg   = $qtybr * $hrgsatuan;

        $addbarang = array(
            'kd_po_nk'      => $kdponk,
            'kd_user'       => $kduser,
            'tgl_transaksi' => $tgltrsk,
            'kd_bsys'       => $kdsys,
            'kd_barang'     => $kdbarang,
            'nama_barang'   => $nmbarang,
            'deskripsi'     => $descbarang,
            'keterangan'    => $ketbarang,
            'qty'           => $qtybr,
            'hrg_satuan'    => $hrgsatuan,
            'total_harga'   => $totalhrg,
        );

        if ($lastHarga) {
            if ($this->db->field_exists('harga_lifo', 'tbpo_detail_po_nk')) {
                $addbarang['harga_lifo'] = $lastHarga->hrg_satuan;
            }
            if ($this->db->field_exists('kd_po_lifo_ref', 'tbpo_detail_po_nk')) {
                $addbarang['kd_po_lifo_ref'] = $lastHarga->kd_po_nk;
            }
        }

        $this->M_Postatus->add_faktur_nk($addbarang);
        redirect('detailponk/' . $kdponk);
    }
    public function add_tax_fk_nk()
    {
        $kdponk = $this->input->post('kdponk');
        $tax    = $this->input->post('tax_isi');

        $data_tax = array(
            'tax'   => $tax
        );
        $this->M_Postatus->add_tax_nk($kdponk, $data_tax);
        redirect('detailponk/' . $kdponk);
    }
    public function add_note_pembelian_nk()
    {
        $kdponk = $this->input->post('kdponk');
        $ket    = $this->input->post('ket_isi');

        $data_tax = array(
            'kd_po' => $kdponk,
            'keterangan' => $ket
        );
        $this->M_Postatus->add_note_pembelian_nk($data_tax);
        redirect('detailponk/' . $kdponk);
    }
    public function edit_note_pembelian_nk()
    {
        $id     = $this->input->post('id_isi');
        $kdponk = $this->input->post('kdponk');
        $ket    = $this->input->post('ket_isi');

        $data_tax = array(
            'keterangan' => $ket
        );
        $this->M_Postatus->edit_note_pembelian_nk($id, $data_tax);
        redirect('detailponk/' . $kdponk);
    }
    public function hapus_note_pembelian_nk()
    {
        $id     = $this->input->post('id_isi');
        $kdponk = $this->input->post('kdponk');

        $this->M_Postatus->hapus_note_pembelian_nk($id);

        redirect('detailponk/' . $kdponk);
    }
    public function add_diskon_nk()
    {
        $kdponk = $this->input->post('kdponk');
        $disc   = $this->input->post('desc_isi');
        $nominal = $this->input->post('nominal_isi');

        $datadisc = array(
            'kd_po' => $kdponk,
            'keterangan' => $disc,
            'nominal'   => $nominal
        );

        $this->M_Postatus->insertDiskon($datadisc);
        redirect('detailponk/' . $kdponk);
    }
    public function edit_faktur_item_nk()
    {
        $id         = $this->input->post('idisi');
        $kd         = $this->input->post('kdponk');
        $namabarang = $this->input->post('nama_isi');
        $descbarang = $this->input->post('desc_isi');
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
        $this->M_Postatus->edit_faktur_item_nk($id, $dataBarang);

        redirect('detailponk/' . $kd);
    }

    public function addnopo()
    {

        $kdponk = $this->input->post('kdponk');
        $nopo   = $this->input->post('nopo');


        $dataedit = array(
            'nopo'  => $nopo
        );

        $this->M_Postatus->addnopo($kdponk, $dataedit);
        redirect('detailponk/' . $kdponk);
    }



    public function hapus_faktur_item_nk()
    {
        $this->load->helper("file");
        $id         = $this->input->post('idisi');
        $kd         = $this->input->post('kdponk');
        $flnm   = $this->input->post('nmfile');

        if ($flnm == "Karisma.png") {
            $this->M_Postatus->hapus_faktur_item_nk($id);
            redirect('detailponk/' . $kd);
        } else {
            unlink(FCPATH . "/images/gbrbarang/" . $flnm);
            $this->M_Postatus->hapus_faktur_item_nk($id);
            redirect('detailponk/' . $kd);
        }
    }
    public function notepembelian()
    {
        $kdpo           = $this->input->post('kdpo');
        $namauser       = $this->session->userdata('nama_user');
        $departement    = $this->session->userdata('kode');
        $statusRow      = $this->getPonkStatusRow($kdpo);
        $validHargaNyata = $this->validateHargaNyataBeforePembelian($kdpo, $statusRow);

        if (!$validHargaNyata['status']) {
            $this->session->set_flashdata('error', $validHargaNyata['message']);
            redirect('detailponk/' . $kdpo);
            return;
        }

        $addnoteuser = array(
            'kd_po'     => $kdpo,
            'isi_note'  => 'PROSES PEMBELIAN',
            'kd_user'   => $departement,
            'nama_user'   => $namauser,
            'note_for'  => '1',
            'update_status' => '1'
        );

        $noteupdateuser = array(
            'status'    => 'PROSES PEMBELIAN'
        );
        $this->M_Postatus->addNote($addnoteuser);
        $this->M_Postatus->updateStatusnk($kdpo, $noteupdateuser);
        redirect('detailponk/' . $kdpo);
    }

    public function konfirm_penerimaan()
    {
        date_default_timezone_set("Asia/Jakarta");

        $kdpo           = $this->input->post('kdpo');
        $kdporeq        = $this->input->post('kdporeq');
        $tgl            = $this->input->post('tgl');
        $tjpem          = $this->input->post('tjpembelian');
        $namauser       = $this->session->userdata('nama_user');
        $departement    = $this->session->userdata('kode');

        // PO Pembelian otomatis dari PO Jasa tidak boleh mengikuti penutupan
        // PO non-komersil biasa.  Saat ON HAND, catat pasangan stok masuk dari
        // PO pembelian lalu stok keluar ke PO Jasa yang menjadi sumbernya.
        $pojasaPosting = $this->M_Postatus->post_pojasa_on_hand_stock_pair(
            $kdpo,
            $tgl ?: date('Y-m-d'),
            (string) $this->session->userdata('kode')
        );
        if ($pojasaPosting['success'] || $pojasaPosting['code'] === 'ALREADY_POSTED') {
            if ($pojasaPosting['success']) {
                $this->M_Postatus->addNote(array(
                    'kd_po' => $kdpo,
                    'isi_note' => 'ON HAND PO JASA - stok masuk PO Pembelian dan stok keluar ke PO Jasa',
                    'kd_user' => $departement,
                    'nama_user' => $namauser,
                    'note_for' => '1',
                    'update_status' => '1',
                ));
            }
            $this->session->set_flashdata(
                $pojasaPosting['success'] ? 'success' : 'error',
                $pojasaPosting['success']
                    ? 'ON HAND PO Jasa berhasil dicatat: stok masuk dari PO Pembelian dan stok keluar ke PO Jasa.'
                    : 'ON HAND PO Jasa sudah pernah dicatat; tidak ada transaksi stok tambahan.'
            );
            redirect('detailponk/' . $kdpo);
            return;
        }
        if ($pojasaPosting['code'] !== 'NOT_PO_JASA') {
            $this->session->set_flashdata('error', 'ON HAND PO Jasa gagal diproses: ' . $pojasaPosting['code']);
            redirect('detailponk/' . $kdpo);
            return;
        }

        // Barang PO Jasa yang dibuat manual wajib memiliki master barang
        // sebelum stok dan transaksi penerimaan dibentuk.
        $manualMasterItems = $this->M_Postatus->get_manual_master_required_for_ponk($kdpo);
        if (count($manualMasterItems) > 0) {
            $this->session->set_flashdata(
                'error',
                'Konfirmasi penerimaan belum dapat dilakukan. Terdapat ' . count($manualMasterItems) . ' master barang manual yang belum diinput. Silakan rekam master barang terlebih dahulu.'
            );
            redirect('detailponk/' . $kdpo);
            return;
        }

        $tmp            = $this->M_Postatus->get_br_nk_det($kdpo);
        $now            = date('Y-m-d');

        if ($tmp) {
            foreach ($tmp as $t) {
                $databelink = array(
                    'kd_akun'           => '11511',
                    'kd_po_nk'          => $t->kd_po_nk,
                    'kd_barang'         => $t->kd_barang,
                    'kd_barangsys'      => $t->kd_bsys,
                    'keterangan'        => $t->keterangan,
                    'kat_barang'        => $t->kat_barang,
                    'tr_qty'            => $t->qty,
                    'satuan'            => $t->satuan,
                    'inputer'           => $t->kd_user,
                    'tgl_transaksi'     => $tgl,
                    'create_at'         => $now,
                    'last_updated_by'   => $t->kd_user
                );
                $this->M_Postatus->input_transaksi($databelink);
            }
            $addnoteuser = array(
                'kd_po'     => $kdpo,
                'isi_note'  => 'BARANG DI TERIMA - ADMIN',
                'kd_user'   => $departement,
                'nama_user'   => $namauser,
                'note_for'  => '1',
                'update_status' => '1'
            );
            $noteupdateuser = array(
                'status'    => 'DONE'
            );
            $updatests = array(
                'kd_po_nk'  => $kdporeq,
                'status'    => '1'
            );
            $this->M_Postatus->addNote($addnoteuser);
            $this->M_Postatus->updateStatusnk($kdpo, $noteupdateuser);
            $this->M_Postatus->updatereqnk_stsbr($kdporeq, $updatests);
            redirect('postatusnk');
        }
    }
    public function addnotenk()
    {
        $kdpo           = $this->input->post('kdpo');
        $note           = $this->input->post('noteDitektur');
        $stslogin       = $this->session->userdata('lv');
        $departement    = $this->session->userdata('kode');
        $namauser       = $this->session->userdata('nama_user');

        if ($stslogin == '1') {
            $addNoteKeuangan = array(
                'kd_po'     => $kdpo,
                'isi_note'  => $note,
                'kd_user'   => $departement,
                'nama_user'   => $namauser,
                'note_for'  => '1',
                'update_status' => '1'
            );

            $noteUpdateKeuangan = array(
                'status'    => 'NOTE KEUANGAN'
            );
            $this->M_Postatus->addNote($addNoteKeuangan);
            $this->M_Postatus->updateStatus($kdpo, $noteUpdateKeuangan);
        } else if ($stslogin == '2') {
            $addnoteuser = array(
                'kd_po'     => $kdpo,
                'isi_note'  => 'SEDANG DIAJUKAN',
                'kd_user'   => $departement,
                'nama_user'   => $namauser,
                'note_for'  => '1',
                'update_status' => '1'
            );

            $noteupdateuser = array(
                'status'    => 'SEDANG DIAJUKAN'
            );
            $this->M_Postatus->addNote($addnoteuser);
            $this->M_Postatus->updateStatusnk($kdpo, $noteupdateuser);
        } else if ($stslogin == '3') {
            $addnoteDirektur = array(
                'kd_po'     => $kdpo,
                'isi_note'  => $note,
                'kd_user'   => $departement,
                'nama_user'   => $namauser,
                'note_for'  => '2',
                'update_status' => '1'
            );
            $noteUpdateDirektur = array(
                'status'    => 'NOTE DIREKTUR'
            );
            $this->M_Postatus->addNote($addnoteDirektur);
            $this->M_Postatus->updateStatusnk($kdpo, $noteUpdateDirektur);
        } else if ($stslogin == '4') {
            $addnoteuser = array(
                'kd_po'     => $kdpo,
                'isi_note'  => 'SEDANG DIAJUKAN - KADEP',
                'kd_user'   => $departement,
                'nama_user'   => $namauser,
                'note_for'  => '1',
                'update_status' => '1'
            );

            $noteupdateuser = array(
                'status'    => 'ON PROGRESS - KADEP'
            );
            $this->M_Postatus->addNote($addnoteuser);
            $this->M_Postatus->updateStatusnk($kdpo, $noteupdateuser);
        }
        redirect('detailponk/' . $kdpo);
    }

    public function unpostponk($kdpo)
    {
        $departement    = $this->session->userdata('kode');
        $namauser       = $this->session->userdata('nama_user');

        $addNoteKeuangan = array(
            'kd_po'     => $kdpo,
            'isi_note'  => 'UNPOST - PO',
            'kd_user'   => $departement,
            'nama_user'   => $namauser,
            'note_for'  => '1',
            'update_status' => '1'
        );

        $noteUpdateKeuangan = array(
            'status'    => 'ON PROGRESS'
        );
        $this->M_Postatus->addNote($addNoteKeuangan);
        $this->M_Postatus->updateStatusnk($kdpo, $noteUpdateKeuangan);

        redirect('detailponk/' . $kdpo);
    }
    public function hapusponk($kdpo)
    {
        $this->M_Postatus->deleteponk($kdpo);
        $this->M_Postatus->deletepodetnk($kdpo);
        $this->M_Postatus->deletediskonk($kdpo);
        $this->M_Postatus->deletenotenk($kdpo);
        redirect('postatusnk');
    }

    public function note_barang_suplier()
    {
        $kdpo       = $this->input->post('kdpo');
        $isinote    = $this->input->post('isi_note');

        $datanote = array(
            'kd_po' => $kdpo,
            'isi_note' => $isinote
        );
        $this->M_Postatus->addnotesuplier($datanote);
        $this->logPoActivity($kdpo, 'Tambah note supplier', null, $datanote);
        redirect('detailPO/' . $kdpo);
    }
    public function note_barang_suplier_edit()
    {
        $id         = $this->input->post('idnote');
        $kdpo       = $this->input->post('kdpo');
        $isinote    = $this->input->post('isi_note');

        $datanote = array(
            'kd_po' => $kdpo,
            'isi_note' => $isinote
        );
        $this->M_Postatus->editnotesuplier($id, $datanote);
        $this->logPoActivity($kdpo, 'Edit note supplier', null, $datanote);
        redirect('detailPO/' . $kdpo);
    }
    public function note_barang_suplier_hapus()
    {
        $id         = $this->input->post('idnote');
        $kdpo       = $this->input->post('kdpo');

        $this->M_Postatus->hapusnotesuplier($id);
        $this->logPoActivity($kdpo, 'Hapus note supplier ID ' . $id);
        redirect('detailPO/' . $kdpo);
    }

    public function add_diskon_merk()
    {
        $kdpo = $this->input->post('kdpo');
        $deskripsiDiskon = $this->normalisasiKeteranganDiskon($this->input->post('deskripsi_diskon_merk'));
        $merkBarang = trim((string) $this->input->post('merk_barang'));
        $satuanDiskon = $this->normalisasiSatuanDiskon($this->input->post('satuan_diskon'));
        $nominal = $this->parseNumericInput($this->input->post('nominal_isi'));

        if ($deskripsiDiskon === '') {
            $this->session->set_flashdata('error', 'Deskripsi diskon wajib diisi.');
            redirect('detailPO/' . $kdpo);
            return;
        }

        if ($merkBarang === '') {
            $this->session->set_flashdata('error', 'Merk barang wajib dipilih.');
            redirect('detailPO/' . $kdpo);
            return;
        }

        if ($satuanDiskon === '') {
            $this->session->set_flashdata('error', 'Satuan diskon wajib dipilih.');
            redirect('detailPO/' . $kdpo);
            return;
        }

        if ($nominal <= 0) {
            $this->session->set_flashdata('error', 'Nominal diskon harus numeric dan lebih besar dari 0.');
            redirect('detailPO/' . $kdpo);
            return;
        }

        if (!$this->db->table_exists('tbpo_diskon_merk')) {
            $this->session->set_flashdata('error', 'Tabel tbpo_diskon_merk belum tersedia. Jalankan migrasi database diskon merk terlebih dahulu.');
            redirect('detailPO/' . $kdpo);
            return;
        }

        $items = $this->M_Postatus->get_items_po_by_merk($kdpo, $merkBarang);
        if (empty($items)) {
            $this->session->set_flashdata('error', 'Item PO untuk merk barang tersebut tidak ditemukan.');
            redirect('detailPO/' . $kdpo);
            return;
        }

        $diskon = $this->M_Postatus->getDiskon($kdpo);
        $status = $this->M_Postatus->getdataStatus($kdpo);
        $poTax = !empty($status) && isset($status[0]->tax) ? $status[0]->tax : 0;
        foreach ($items as $item) {
            $konversi = $this->hitungDiskonSatuanKecil($nominal, $satuanDiskon, isset($item->isi) ? $item->isi : 0, isset($item->kemasan) ? $item->kemasan : 0, $poTax);

            if (!$konversi['success']) {
                $this->session->set_flashdata('error', $konversi['message'] . ' Barang: ' . $item->nama_barang);
                redirect('detailPO/' . $kdpo);
                return;
            }

            $hargaSatuanKecil = isset($item->harga_satuan_kecil) && (float) $item->harga_satuan_kecil > 0 ? $item->harga_satuan_kecil : $item->hrg_satuan;
            $diskonResult = $this->diskonPerSatuanDetailPOResult($item->nama_barang, $item->qty, $diskon, $hargaSatuanKecil, $item->id_det_po, $merkBarang, $item, $poTax);
            $diskonBerjalan = $diskonResult['diskon_per_satuan'];
            $hargaSetelahDiskon = (float) $hargaSatuanKecil - $diskonBerjalan - (float) $konversi['diskon_satuan_kecil'];

            if ($hargaSetelahDiskon < 0) {
                $this->session->set_flashdata('error', 'Harga setelah diskon tidak boleh minus pada barang ' . $item->nama_barang);
                redirect('detailPO/' . $kdpo);
                return;
            }
        }

        $noPo = isset($items[0]->no_po) ? $items[0]->no_po : null;
        $diskonMerk = array(
            'no_po' => $noPo,
            'merk_barang' => $merkBarang,
            'satuan_diskon' => $satuanDiskon,
            'nominal_diskon' => $nominal,
            'created_by' => $this->session->userdata('kode') ?: $this->session->userdata('nama_user'),
        );

        $this->db->trans_start();
        $idDiskonMerk = $this->M_Postatus->insert_diskon_merk($diskonMerk);
        $tambahDiskon = array(
            'kd_po' => $kdpo,
            'keterangan' => $deskripsiDiskon . ' [MERK:' . $merkBarang . '] [SATUAN_DISKON:' . $satuanDiskon . '] [DISKON_MERK:' . $idDiskonMerk . ']',
            'nominal' => $nominal
        );
        $this->M_Postatus->insertDiskon($tambahDiskon);
        $this->syncDiskonDetailPO($kdpo);
        $this->logPoActivity($kdpo, 'Tambah diskon merk barang', null, $tambahDiskon);
        $this->db->trans_complete();

        if ($this->db->trans_status() === FALSE) {
            $this->session->set_flashdata('error', 'Diskon merk gagal diterapkan.');
            redirect('detailPO/' . $kdpo);
            return;
        }

        redirect('detailPO/' . $kdpo);
    }

    public function add_diskon_barang()
    {
        $kdsup       = $this->input->post('kdsup');
        $kdpo       = $this->input->post('kdpo');
        $nmbarang       = $this->input->post('nmbarang');
        $idDetPo    = (int) $this->input->post('id_det_po');
        $tax        = $this->parseNumericInput($this->input->post('disc_isi'));
        $hargaA     = $this->parseNumericInput($this->input->post('hrg_satuan_kecil'));
        $diskon     = $this->M_Postatus->getDiskon($kdpo);
        $detailItem = $this->M_Postatus->getDetailItemById($idDetPo);
        $merkBarang = $detailItem && isset($detailItem->merk_barang) ? $detailItem->merk_barang : '';
        $status      = $this->M_Postatus->getdataStatus($kdpo);
        $poTax       = !empty($status) && isset($status[0]->tax) ? $status[0]->tax : 0;
        $diskonResult = $this->diskonPerSatuanDetailPOResult($nmbarang, 1, $diskon, $hargaA, $idDetPo, $merkBarang, $detailItem, $poTax);
        $diskonPerSatuan = $diskonResult['diskon_per_satuan'];
        $hargaSetelahDiskon = max($hargaA - $diskonPerSatuan, 0);
        $hasiltax   = $tax / 100;
        $nominalTax = $hargaSetelahDiskon * $hasiltax;

        $tambahDiskon = array(
            'kd_po' => $kdpo,
            'kd_suplier' => $kdsup,
            'keterangan' => 'Diskon Barang' . '-' . $nmbarang . '(' . $tax . '%' . ') [ROW_DET:' . $idDetPo . ']',
            'nominal' => $nominalTax
        );

        $this->M_Postatus->insertDiskon($tambahDiskon);
        $this->syncDiskonDetailPO($kdpo);
        $this->logPoActivity($kdpo, 'Tambah diskon persen barang', null, $tambahDiskon);

        redirect('detailPO/' . $kdpo);
    }

    public function add_diskon_barangs()
    {
        $kdsup      = $this->input->post('kdsup');
        $kdpo       = $this->input->post('kdpo');
        $nmbarang   = $this->input->post('nmbarang');
        $idDetPo    = (int) $this->input->post('id_det_po');
        $desc       = $this->input->post('desc_isi');
        $nominal    = $this->input->post('disc_isi');

        $tambahDiskon = array(
            'kd_po' => $kdpo,
            'kd_suplier' => $kdsup,
            'keterangan' => $nmbarang . ' ' . '-' . ' ' . $desc . ' [ROW_DET:' . $idDetPo . ']',
            'nominal' => $nominal
        );

        $this->M_Postatus->insertDiskon($tambahDiskon);
        $this->syncDiskonDetailPO($kdpo);
        $this->logPoActivity($kdpo, 'Tambah diskon nominal barang', null, $tambahDiskon);

        redirect('detailPO/' . $kdpo);
    }

    public function get_server_all_po()
    {
        $list = $this->M_Postatus->get_datatables();
        $data = array();
        $no = $_POST['start'];
        foreach ($list as $l) {
            $row = array();
            $row[]  = $l->id_po;
            $data[] = $row;
        }
        $output = array(
            "draw" => $_POST['draw'],
            "recordTotal" => $this->M_Postatus->count_all(),
            "recordsFiltered" => $this->M_Postatus->count_filtered(),
            "data" => $data,
        );
        echo json_encode($output);
    }
    public function repostponk($kdpo)
    {
        $departement    = $this->session->userdata('kode');
        $namauser       = $this->session->userdata('nama_user');

        $dataKonfirm = array(
            'kd_po_nk' => $kdpo,
            'status' => 'ON PROGRESS - KADEP'
        );

        $notedirektur = array(
            'kd_po'     => $kdpo,
            'isi_note'  => 'REPOST PO',
            'kd_user'   => $departement,
            'nama_user'   => $namauser,
            'note_for'  => '1',
            'update_status' => '1'
        );

        $this->M_Postatus->konfirmPonk($kdpo, $dataKonfirm);
        $this->M_Postatus->addNote($notedirektur);
        redirect('postatusnk');
    }
    public function konfirmasiOrderNK($kdpo, $kddirektur)
    {
        $departement    = $this->session->userdata('kode');
        $namauser       = $this->session->userdata('nama_user');

        $dataKonfirm = array(
            'kd_po_nk' => $kdpo,
            'acc_with_kadep' => $kddirektur,
            'status' => 'ACC-KADEP'
        );

        $notedirektur = array(
            'kd_po'     => $kdpo,
            'isi_note'  => 'PO ACCEPT KADEP',
            'kd_user'   => $departement,
            'nama_user'   => $namauser,
            'note_for'  => '1',
            'update_status' => '1'
        );

        $this->M_Postatus->konfirmPonk($kdpo, $dataKonfirm);
        $this->M_Postatus->addNote($notedirektur);
        redirect('postatusnk');
    }
    public function konfirmasiOrderdirNK($kdpo, $kddirektur)
    {
        $departement    = $this->session->userdata('kode');
        $namauser       = $this->session->userdata('nama_user');

        $dataKonfirm = array(
            'kd_po_nk' => $kdpo,
            'acc_with' => $kddirektur,
            'status' => 'ACC DIREKTUR'
        );

        $notedirektur = array(
            'kd_po'     => $kdpo,
            'isi_note'  => 'PO ACCEPT DIREKTUR',
            'kd_user'   => $departement,
            'nama_user'   => $namauser,
            'note_for'  => '1',
            'update_status' => '1'
        );

        $this->M_Postatus->konfirmPonk($kdpo, $dataKonfirm);
        $this->M_Postatus->addNote($notedirektur);
        redirect('postatusnk');
    }

    public function tolakordernk($kdponk, $kduser)

    {
        $namauser       = $this->session->userdata('nama_user');

        $dataKonfirm = array(
            'kd_po_nk' => $kdponk,
            'status' => 'REJECT'
        );
        $notedirektur = array(
            'kd_po'     => $kdponk,
            'isi_note'  => 'PO REJECT',
            'kd_user'   => $kduser,
            'nama_user'   => $namauser,
            'note_for'  => '1',
            'update_status' => '1'
        );

        $this->M_Postatus->tolakPonk($kdponk, $dataKonfirm);
        $this->M_Postatus->addNote($notedirektur);
        redirect('detailponk/' . $kdponk);
    }

    public function pendingordernk()
    {
        $kdpo           = $this->input->post('kdpo');
        $note           = $this->input->post('noteDitektur');
        $departement    = $this->session->userdata('kode');
        $namauser       = $this->session->userdata('nama_user');

        $dataKonfirm = array(
            'kd_po_nk' => $kdpo,
            'status' => 'PENDING'
        );
        $notedirektur = array(
            'kd_po'     => $kdpo,
            'isi_note'  => 'PO PENDING - ' . $note,
            'kd_user'   => $departement,
            'nama_user'   => $namauser,
            'note_for'  => '1',
            'update_status' => '1'
        );

        $this->M_Postatus->pendingordernk($kdpo, $dataKonfirm);
        $this->M_Postatus->addNote($notedirektur);
        redirect('detailponk/' . $kdpo);
    }
    public function porevisi()
    {
        $kdpoid         = $this->input->post('kdpo');
        $notes         = $this->input->post('noteisi');
        $departement    = $this->session->userdata('kode');
        $namauser       = $this->session->userdata('nama_user');

        $dataKonfirm = array(
            'kd_po_nk' => $kdpoid,
            'status' => 'PO REVISI'
        );
        $notedirektur = array(
            'kd_po'     => $kdpoid,
            'isi_note'  => 'PO REVISI - ' . $notes,
            'kd_user'   => $departement,
            'nama_user'   => $namauser,
            'note_for'  => '1',
            'update_status' => '1'
        );

        $this->M_Postatus->pendingordernk($kdpoid, $dataKonfirm);
        $this->M_Postatus->addNote($notedirektur);
        redirect('postatusnk');
    }

    public function insert_note_setting()
    {
        $kdpo           = $this->input->post('kdpo');
        $shipment_to    = $this->input->post('shipment_to');
        $alamat_ship    = $this->input->post('alamat_ship');
        $cp_shipment    = $this->input->post('cp_shipment');
        $no_cp          = $this->input->post('no_cp');

        $note_printout = array(
            'shipment_to'   => $shipment_to,
            'alamat_ship'   => $alamat_ship,
            'cp_shipment'   => $cp_shipment,
            'no_cp'         => $no_cp
        );

        $this->M_Postatus->insert_setting_note($kdpo, $note_printout);

        redirect('detailponk/' . $kdpo);
    }

    public function edit_harganyata()
    {
        $idpo = $this->input->post('idisi');
        $kdpo = $this->input->post('kdponk');
        $hrgnyata = $this->parseNumericInput($this->input->post('hrg_nyata'));
        $qty = $this->parseNumericInput($this->input->post('qty_nyata'));
        $alasan = trim((string) $this->input->post('alasan_realisasi'));
        $detail = $this->M_Postatus->get_detail_po_nk_row($idpo);
        $statusRow = $this->getPonkStatusRow($kdpo);

        if ((!is_super_admin() && $this->session->userdata('lv') != '2') || !$statusRow || !$this->canInputHargaNyata($statusRow->status)) {
            $this->session->set_flashdata('error', 'Harga nyata hanya dapat diinput Purchasing setelah ACC DIREKTUR.');
            redirect('detailponk/' . $kdpo);
            return;
        }

        if (!$detail || $detail->kd_po_nk !== $kdpo) {
            $this->session->set_flashdata('error', 'Detail item tidak valid untuk PO ini.');
            redirect('detailponk/' . $kdpo);
            return;
        }

        if ($qty <= 0 || $hrgnyata <= 0) {
            $this->session->set_flashdata('error', 'Qty Nyata dan Harga Nyata wajib lebih besar dari 0.');
            redirect('detailponk/' . $kdpo);
            return;
        }

        $total_harga = $qty * $hrgnyata;
        $statusApproval = $hrgnyata > (float) $detail->hrg_satuan ? 'PENDING_DIREKTUR' : 'DISETUJUI_OTOMATIS';

        $dataedited = array(
            'hrg_nyata' => $hrgnyata,
            'qty_nyata' => $qty,
            'total_nyata' => $total_harga,
            'status_approval_harga_nyata' => $statusApproval,
            'alasan_realisasi' => $alasan
        );

        $saved = $this->M_Postatus->simpan_realisasi_harga_nyata($idpo, $dataedited, array(
            'aksi' => $statusApproval,
            'keterangan' => $statusApproval === 'PENDING_DIREKTUR'
                ? 'Harga nyata lebih tinggi dari harga pengajuan dan menunggu approval Direktur.'
                : 'Harga nyata sama atau lebih rendah dari harga pengajuan.',
            'kd_user' => $this->session->userdata('kode'),
            'nama_user' => $this->session->userdata('nama_user')
        ));

        if (!$saved) {
            $this->session->set_flashdata('error', 'Gagal menyimpan harga nyata.');
            redirect('detailponk/' . $kdpo);
            return;
        }

        if ($statusApproval === 'PENDING_DIREKTUR') {
            $this->M_Postatus->addNote(array(
                'kd_po' => $kdpo,
                'isi_note' => 'HARGA NYATA MENUNGGU APPROVAL DIREKTUR - ' . $detail->nama_barang,
                'kd_user' => $this->session->userdata('kode'),
                'nama_user' => $this->session->userdata('nama_user'),
                'note_for' => '1',
                'update_status' => '1'
            ));
            $this->session->set_flashdata('warning', 'Harga nyata lebih tinggi dari harga pengajuan. Menunggu approval Direktur.');
        } else {
            $this->session->set_flashdata('success', 'Harga nyata tersimpan dan tidak membutuhkan approval Direktur.');
        }

        redirect('detailponk/' . $kdpo);
    }

    public function approve_harganyata($id)
    {
        $detail = $this->M_Postatus->get_detail_po_nk_row($id);
        if ((!is_super_admin() && $this->session->userdata('lv') != '3') || !$detail) {
            $this->session->set_flashdata('error', 'Approval harga nyata hanya dapat dilakukan Direktur.');
            redirect('postatusnk');
            return;
        }

        $saved = $this->M_Postatus->approve_harga_nyata_detail($id, 'DISETUJUI_DIREKTUR', array(
            'kd_user' => $this->session->userdata('kode'),
            'nama_user' => $this->session->userdata('nama_user')
        ));

        if (!$saved) {
            $this->session->set_flashdata('error', 'Gagal approval harga nyata. Pastikan migrasi database realisasi sudah dijalankan.');
            redirect('detailponk/' . $detail->kd_po_nk);
            return;
        }

        $this->M_Postatus->addNote(array(
            'kd_po' => $detail->kd_po_nk,
            'isi_note' => 'HARGA NYATA DISETUJUI DIREKTUR - ' . $detail->nama_barang,
            'kd_user' => $this->session->userdata('kode'),
            'nama_user' => $this->session->userdata('nama_user'),
            'note_for' => '1',
            'update_status' => '1'
        ));
        $this->session->set_flashdata('success', 'Harga nyata disetujui Direktur.');
        redirect('detailponk/' . $detail->kd_po_nk);
    }

    public function reject_harganyata($id)
    {
        $detail = $this->M_Postatus->get_detail_po_nk_row($id);
        if ((!is_super_admin() && $this->session->userdata('lv') != '3') || !$detail) {
            $this->session->set_flashdata('error', 'Reject harga nyata hanya dapat dilakukan Direktur.');
            redirect('postatusnk');
            return;
        }

        $saved = $this->M_Postatus->approve_harga_nyata_detail($id, 'DITOLAK_DIREKTUR', array(
            'kd_user' => $this->session->userdata('kode'),
            'nama_user' => $this->session->userdata('nama_user')
        ));

        if (!$saved) {
            $this->session->set_flashdata('error', 'Gagal reject harga nyata. Pastikan migrasi database realisasi sudah dijalankan.');
            redirect('detailponk/' . $detail->kd_po_nk);
            return;
        }

        $this->M_Postatus->addNote(array(
            'kd_po' => $detail->kd_po_nk,
            'isi_note' => 'HARGA NYATA DITOLAK DIREKTUR - ' . $detail->nama_barang,
            'kd_user' => $this->session->userdata('kode'),
            'nama_user' => $this->session->userdata('nama_user'),
            'note_for' => '1',
            'update_status' => '1'
        ));
        $this->session->set_flashdata('error', 'Harga nyata ditolak Direktur. Purchasing perlu revisi harga nyata.');
        redirect('detailponk/' . $detail->kd_po_nk);
    }

    private function uploadPonkEvidenceFile($field, $path)
    {
        if (empty($_FILES[$field]['name'])) {
            return array('success' => false, 'message' => 'File evidence wajib dipilih.');
        }

        if (!is_dir($path) && !@mkdir($path, 0777, true) && !is_dir($path)) {
            return array('success' => false, 'message' => 'Folder tujuan upload tidak dapat dibuat.');
        }

        // Folder dapat telah dibuat oleh proses/web user lain. Beri izin tulis
        // agar Purchasing selalu dapat menyimpan file pada folder departemen/tanggal.
        @chmod($path, 0777);
        if (!is_writable($path)) {
            return array('success' => false, 'message' => 'Folder tujuan upload tidak dapat ditulis.');
        }

        $config = array(
            'upload_path' => $path,
            'allowed_types' => 'jpg|jpeg|png|gif|webp|pdf|csv|xls|xlsx|doc|docx',
            'max_size' => '5120',
            'max_width' => '6000',
            'max_height' => '6000',
            'overwrite' => false,
            'encrypt_name' => true
        );

        $this->load->library('upload');
        $this->upload->initialize($config);

        if (!$this->upload->do_upload($field)) {
            return array('success' => false, 'message' => strip_tags($this->upload->display_errors('', '')));
        }

        return array('success' => true, 'data' => $this->upload->data());
    }

    private function recordPonkEvidenceArchive($kdponk, $jenis, $keterangan, $uploadData, $relativePath, $sourceTable, $sourceId = null)
    {
        $data = array(
            'kd_po_nk' => $kdponk,
            'jenis_evident' => $jenis,
            'keterangan' => $keterangan,
            'file_path' => $relativePath . $uploadData['file_name'],
            'file_name' => pathinfo($uploadData['file_name'], PATHINFO_FILENAME),
            'file_uploaded' => $uploadData['file_name'],
            'file_original' => $uploadData['client_name'],
            'file_ext' => $uploadData['file_ext'],
            'file_size' => $uploadData['file_size'],
            'source_table' => $sourceTable,
            'source_id' => $sourceId,
            'user_upload' => $this->session->userdata('kode')
        );

        return $this->M_Postatus->insert_arsip_evident_ponk($data);
    }

    public function arsip_evident_ponk()
    {
        $data['title'] = 'Arsip Evident PO Non Komersil';
        $data['filters'] = array(
            'tgl_start' => $this->input->get('tgl_start', true),
            'tgl_end' => $this->input->get('tgl_end', true),
            'kd_po_nk' => $this->input->get('kd_po_nk', true)
        );
        $data['arsip'] = $this->M_Postatus->get_arsip_evident_ponk_done($data['filters']);

        $this->load->view('partial/header', $data);
        $this->load->view('partial/sidebar');
        $this->load->view('content/postatus/arsip_evident_ponk', $data);
        $this->load->view('partial/footer');
        $this->load->view('content/postatus/datatables');
    }

    public function edit_gbr_pndukung()
    {
        $idisi      = $this->input->post('id_isi');
        $kdponk     = $this->input->post('kd_po');
        $keterangan = $this->input->post('desc_isi');

        $dataBarang = array(
            'keterangan'       => $keterangan,
        );

        $this->M_Postatus->editflupload($idisi, $dataBarang);

        redirect('detailponk/' . $kdponk);
    }

    public function upbuktipembelian()
    {
        $kdponk       = $this->input->post('kdponk');
        $keterangan   = $this->input->post('desc_isi');
        $userup       = $this->session->userdata('kode');
        $namauser     = $this->session->userdata('nama_user');
        $statusRow    = $this->getPonkStatusRow($kdponk);

        if (!$statusRow || (!is_super_admin() && (string) $this->session->userdata('lv') !== '2')) {
            $this->session->set_flashdata('error', 'Hanya Purchasing yang dapat mengunggah bukti pembelian.');
            redirect('detailponk/' . $kdponk);
            return;
        }

        $validHargaNyata = $this->validateHargaNyataBeforePembelian($kdponk, $statusRow);

        if (!$validHargaNyata['status']) {
            $this->session->set_flashdata('error', $validHargaNyata['message']);
            redirect('detailponk/' . $kdponk);
            return;
        }

        // Gunakan folder yang telah dibuat saat PIC mengunggah dokumen
        // pendukung request ini. Request lama tanpa dokumen tetap memakai
        // struktur folder departemen/tanggal yang sama.
        $relativeDirectory = $this->M_Postatus->get_pic_supporting_directory($kdponk);
        if ($relativeDirectory === null) {
            $department = preg_replace('/[^A-Z0-9_-]+/', '_', strtoupper((string) $statusRow->departemen));
            $department = trim($department, '_') ?: 'UMUM';
            $relativeDirectory = 'assets/request-pendukung/' . $department . '/' . date('Y-m-d') . '/';
        }
        $upload = $this->uploadPonkEvidenceFile('gambar_1', FCPATH . $relativeDirectory);
        if (!$upload['success']) {
            $this->session->set_flashdata('error', $upload['message']);
            redirect('detailponk/' . $kdponk);
            return;
        }
        $image_data1 = $upload['data'];

        $dataupload = array(
            'kd_po_nk'      => $kdponk,
            'keterangan'    => $keterangan,
            'user_upload'   => $userup,
            'file_name'     => $relativeDirectory . $image_data1['file_name'],
            'file_uploaded' => $image_data1['file_name']
        );
        $dataKonfirm = array(
            'kd_po_nk' => $kdponk,
            'status' => 'PROSES PEMBELIAN'
        );
        $notedirektur = array(
            'kd_po'     => $kdponk,
            'isi_note'  => 'PROSES PEMBELIAN',
            'kd_user'   => $userup,
            'nama_user'   => $namauser,
            'note_for'  => '1',
            'update_status' => '1'
        );

        $this->M_Postatus->konfirmPonk($kdponk, $dataKonfirm);
        $this->M_Postatus->addNote($notedirektur);
        $sourceId = $this->M_Postatus->upbuktibeli($dataupload);
        $this->recordPonkEvidenceArchive($kdponk, 'BUKTI PEMBELIAN BARANG', $keterangan, $image_data1, $relativeDirectory, 'tbpo_file_bukti_beli', $sourceId);

        redirect('detailponk/' . $kdponk);
    }

    public function delete_gbr_pendukung()
    {
        $this->load->helper("file");
        $kdpo   = $this->input->post('kd_po');
        $idgbr  = $this->input->post('id_isi');
        $flnm  = $this->input->post('file_nm');
        unlink(FCPATH . "/images/filepndukung/" . $flnm);
        $this->M_Postatus->deletegbrfilependukung($idgbr);
        redirect('detailponk/' . $kdpo);
    }

    public function reuploadgbrflpndukung()
    {
        $this->load->helper("file");
        $idisi      = $this->input->post('id_isi');
        $kdponk     = $this->input->post('kd_po');
        $flnm  = $this->input->post('file_nm');


        if (!empty($_FILES['gambar_1'])) {
            $config['upload_path'] = './images/filepndukung/';
            $config['allowed_types'] = '*';
            $config['max_size'] = '2000';
            $config['max_width'] = '6000';
            $config['max_height'] = '6000';
            $config['overwrite'] = TRUE;
            $config['file_name'] = date('Y') . date('m') . date('U');
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
            'file_uploaded'    => $image_data1['file_name']
        );

        unlink(FCPATH . "/images/filepndukung/" . $flnm);
        $this->M_Postatus->editflupload($idisi, $dataBarang);

        redirect('detailponk/' . $kdponk);
    }

    public function gbruploadpic()
    {
        $this->load->helper("file");
        $idisi      = $this->input->post('id_isi');
        $kdponk     = $this->input->post('kd_po');
        $nmfile     = $this->input->post('nm_file');

        if ($nmfile == 'Karisma.png') {
            if (!empty($_FILES['gambar_1'])) {
                $config['upload_path'] = './images/gbrbarang/';
                $config['allowed_types'] = '*';
                $config['max_size'] = '2000';
                $config['max_width'] = '6000';
                $config['max_height'] = '6000';
                $config['overwrite'] = TRUE;
                $config['file_name'] = date('Y') . date('m') . date('U');
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
                'id_det_po_nk'   => $idisi,
                'gbr_produk'    => $image_data1['file_name']
            );

            $this->M_Postatus->uploadgbr_edited($idisi, $dataBarang);

            redirect('detailponk/' . $kdponk);
        } else {
            if (!empty($_FILES['gambar_1'])) {
                $config['upload_path'] = './images/gbrbarang/';
                $config['allowed_types'] = '*';
                $config['max_size'] = '2000';
                $config['max_width'] = '6000';
                $config['max_height'] = '6000';
                $config['overwrite'] = TRUE;
                $config['file_name'] = date('Y') . date('m') . date('U');
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
                'id_det_po_nk'   => $idisi,
                'gbr_produk'    => $image_data1['file_name']
            );

            unlink(FCPATH . "/images/gbrbarang/" . $nmfile);
            $this->M_Postatus->uploadgbr_edited($idisi, $dataBarang);

            redirect('detailponk/' . $kdponk);
        }
    }
    public function uploadfileponk()
    {
        $kdponk       = $this->input->post('kdisi');
        $keterangan   = $this->input->post('desc_isi');
        $userup       = $this->session->userdata('kode');

        if ((string) $this->session->userdata('lv') !== '2' && !is_super_admin()) {
            show_404();
            return;
        }
        $statusRow = $this->getPonkStatusRow($kdponk);
        if (!$statusRow) {
            show_404();
            return;
        }
        $department = preg_replace('/[^A-Z0-9_-]+/', '_', strtoupper((string) $statusRow->departemen));
        $department = trim($department, '_') ?: 'UMUM';
        $relativeDirectory = 'assets/request-pendukung/' . $department . '/' . date('Y-m-d') . '/';

        $upload = $this->uploadPonkEvidenceFile('gambar_1', FCPATH . $relativeDirectory);
        if (!$upload['success']) {
            $this->session->set_flashdata('error', $upload['message']);
            redirect('detailponk/' . $kdponk);
            return;
        }
        $image_data1 = $upload['data'];

        $dataBarang = array(
            'kd_po_nk'      => $kdponk,
            'keterangan'    => $keterangan,
            'user_upload'   => $userup,
            // Legacy rows use only a basename. New rows retain the relative
            // path so they can be served from the department/date folder.
            'file_name'   => $relativeDirectory . $image_data1['file_name'],
            'file_uploaded'    => $image_data1['file_name']
        );

        $sourceId = $this->M_Postatus->add_file_po_nk($dataBarang);
        $this->recordPonkEvidenceArchive($kdponk, 'FILE PENDUKUNG PENGAJUAN', $keterangan, $image_data1, $relativeDirectory, 'tbpo_file_nk', $sourceId);

        redirect('detailponk/' . $kdponk);
    }

    public function supporting_file($fileId)
    {
        $file = $this->M_Postatus->get_supporting_file((int) $fileId);
        $statusRow = $file ? $this->getPonkStatusRow($file->kd_po_nk) : null;
        $level = (string) $this->session->userdata('lv');
        $canAccess = is_super_admin() || $level === '2'
            || (($level === '4' || $level === '5') && $statusRow && $statusRow->departemen === $this->session->userdata('departemen'));
        if (!$file || !$statusRow || !$canAccess) {
            show_404();
            return;
        }
        $path = realpath(FCPATH . ltrim($file->display_path, '/'));
        $assetBase = realpath(FCPATH . 'assets/request-pendukung');
        $legacyBase = realpath(FCPATH . 'images/filepndukung');
        $withinAsset = $assetBase && $path && strpos($path, $assetBase . DIRECTORY_SEPARATOR) === 0;
        $withinLegacy = $legacyBase && $path && strpos($path, $legacyBase . DIRECTORY_SEPARATOR) === 0;
        if ((!$withinAsset && !$withinLegacy) || !is_file($path)) {
            show_404();
            return;
        }
        $mime = function_exists('finfo_open') ? (new finfo(FILEINFO_MIME_TYPE))->file($path) : 'application/octet-stream';
        $inline = strpos($mime, 'image/') === 0 || in_array($mime, array('application/pdf', 'text/plain'), true);
        $name = str_replace(array('"', "\r", "\n"), '', $file->file_uploaded);
        $this->output->set_content_type($mime)->set_header('X-Content-Type-Options: nosniff')
            ->set_header('Content-Disposition: ' . ($inline ? 'inline' : 'attachment') . '; filename="' . $name . '"')
            ->set_output(file_get_contents($path));
    }

    public function purchase_proof($fileId)
    {
        $file = $this->M_Postatus->get_purchase_proof_file((int) $fileId);
        $statusRow = $file ? $this->getPonkStatusRow($file->kd_po_nk) : null;
        $level = (string) $this->session->userdata('lv');
        $canAccess = is_super_admin() || $level === '2'
            || (($level === '4' || $level === '5') && $statusRow && $statusRow->departemen === $this->session->userdata('departemen'));
        if (!$file || !$statusRow || !$canAccess) {
            show_404();
            return;
        }

        $path = realpath(FCPATH . ltrim($file->display_path, '/'));
        $assetBase = realpath(FCPATH . 'assets/request-pendukung');
        $legacyBase = realpath(FCPATH . 'images/upbukti');
        $withinAsset = $assetBase && $path && strpos($path, $assetBase . DIRECTORY_SEPARATOR) === 0;
        $withinLegacy = $legacyBase && $path && strpos($path, $legacyBase . DIRECTORY_SEPARATOR) === 0;
        if ((!$withinAsset && !$withinLegacy) || !is_file($path)) {
            show_404();
            return;
        }

        $mime = function_exists('finfo_open') ? (new finfo(FILEINFO_MIME_TYPE))->file($path) : 'application/octet-stream';
        $inline = strpos($mime, 'image/') === 0 || in_array($mime, array('application/pdf', 'text/plain'), true);
        $name = str_replace(array('"', "\r", "\n"), '', $file->file_uploaded);
        $this->output->set_content_type($mime)->set_header('X-Content-Type-Options: nosniff')
            ->set_header('Content-Disposition: ' . ($inline ? 'inline' : 'attachment') . '; filename="' . $name . '"')
            ->set_output(file_get_contents($path));
    }

    public function hrgnyataon($kdponk)
    {
        $statusRow = $this->getPonkStatusRow($kdponk);
        if (!$statusRow || (!is_super_admin() && $this->session->userdata('lv') != '2') || !$this->canInputHargaNyata($statusRow->status)) {
            $this->session->set_flashdata('error', 'Harga Nyata hanya dapat diaktifkan setelah ACC DIREKTUR.');
            redirect('detailponk/' . $kdponk);
            return;
        }

        $hrgnyataon = array(
            'status_hrg_nyata' => '1'
        );
        $this->M_Postatus->changestatusnyata($kdponk, $hrgnyataon);
        redirect('detailponk/' . $kdponk);
    }
    public function hrgnyataoff($kdponk)
    {
        $statusRow = $this->getPonkStatusRow($kdponk);
        if (!$statusRow || (!is_super_admin() && $this->session->userdata('lv') != '2') || !$this->canInputHargaNyata($statusRow->status)) {
            $this->session->set_flashdata('error', 'Harga Nyata hanya dapat dinonaktifkan pada status setelah ACC DIREKTUR.');
            redirect('detailponk/' . $kdponk);
            return;
        }

        $summary = $this->M_Postatus->get_harga_nyata_summary($kdponk);
        if ($summary && (int) $summary->total_item > (int) $summary->belum_input) {
            $this->session->set_flashdata('error', 'Harga Nyata sudah mulai diinput sehingga switch tidak dapat dimatikan.');
            redirect('detailponk/' . $kdponk);
            return;
        }

        $hrgnyataoff = array(
            'status_hrg_nyata' => '0'
        );
        $this->M_Postatus->changestatusnyata($kdponk, $hrgnyataoff);
        redirect('detailponk/' . $kdponk);
    }
    public function srcponkbytgl()
    {
        $data['title'] = 'PO Status';
        $dp = $this->session->userdata('departemen');
        $lv = $this->session->userdata('level');
        $tglstart   = $this->input->post('tglstart');
        $tglend     = $this->input->post('tglend');
        $_SESSION['vartgl1'] = $tglstart;
        $_SESSION['vartgl2'] = $tglend;

        $vartgl1           = $_SESSION['vartgl1'];
        $vartgl2            = $_SESSION['vartgl2'];
        $data['vartgl1']    = $vartgl1;
        $data['vartgl2']    = $vartgl2;

        $data['vcari']      = $this->M_Postatus->getdaterangelap($vartgl1, $vartgl2)->result();
        $data['po']    = $this->M_Postatus->getAllNK_keu()->result();
        $data['ponk']    = $this->M_Postatus->getAllNK_keu()->result();

        $this->load->view('partial/header', $data);
        $this->load->view('partial/sidebar');
        $this->load->view('content/postatus/srcnonkomersilstatus', $data);
        $this->load->view('partial/footer');
        $this->load->view('content/postatus/datatables');
    }

    public function historidone($lv, $user)
    {
        $data['title'] = 'PO Status';
        $data['hdone'] = $this->M_Postatus->getdatapodone($lv, $user)->result();
        $tglstart   = $this->input->post('tglstart');
        $tglend     = $this->input->post('tglend');
        $_SESSION['vartgl1'] = $tglstart;
        $_SESSION['vartgl2'] = $tglend;


        $this->load->view('partial/header', $data);
        $this->load->view('partial/sidebar');
        $this->load->view('content/postatus/historidone', $data);
        $this->load->view('partial/footer');
        $this->load->view('content/postatus/datatables');
    }
    public function srcexpdone()
    {
        // DEKLARASI LEVEL

        $userlv = $this->session->userdata('lv');
        $dep = $this->session->userdata('departemen');

        if ($userlv == '2') {

            $tglstart   = $this->input->post('tglstart');
            $tglend     = $this->input->post('tglend');
            $_SESSION['vartgl1'] = $tglstart;
            $_SESSION['vartgl2'] = $tglend;

            $vartgl1            = $_SESSION['vartgl1'];
            $vartgl2            = $_SESSION['vartgl2'];
            $data['vartgl1']    = $vartgl1;
            $data['vartgl2']    = $vartgl2;
            $data['title']      = 'PO Status';

            $data['vcari']      = $this->M_Postatus->srchistoriadmpurchasing($vartgl1, $vartgl2);

            $this->load->view('partial/header', $data);
            $this->load->view('partial/sidebar');
            $this->load->view('content/postatus/srcgetdateponk', $data);
            $this->load->view('partial/footer');
            $this->load->view('content/postatus/datatables');
        } else if ($userlv == '3') {
            $tglstart   = $this->input->post('tglstart');
            $tglend     = $this->input->post('tglend');
            $_SESSION['vartgl1'] = $tglstart;
            $_SESSION['vartgl2'] = $tglend;

            $vartgl1            = $_SESSION['vartgl1'];
            $vartgl2            = $_SESSION['vartgl2'];
            $data['vartgl1']    = $vartgl1;
            $data['vartgl2']    = $vartgl2;
            $data['title']      = 'PO Status';

            $data['vcari']      = $this->M_Postatus->srchistoriadmpurchasing($vartgl1, $vartgl2);

            $this->load->view('partial/header', $data);
            $this->load->view('partial/sidebar');
            $this->load->view('content/postatus/srcgetdateponk', $data);
            $this->load->view('partial/footer');
            $this->load->view('content/postatus/datatables');
        } else if ($userlv == '4') {
            $tglstart   = $this->input->post('tglstart');
            $tglend     = $this->input->post('tglend');
            $_SESSION['vartgl1'] = $tglstart;
            $_SESSION['vartgl2'] = $tglend;

            $vartgl1            = $_SESSION['vartgl1'];
            $vartgl2            = $_SESSION['vartgl2'];
            $data['vartgl1']    = $vartgl1;
            $data['vartgl2']    = $vartgl2;
            $data['title']      = 'PO Status';

            $data['vcari']      = $this->M_Postatus->srcgetdateponk($dep, $vartgl1, $vartgl2);

            $this->load->view('partial/header', $data);
            $this->load->view('partial/sidebar');
            $this->load->view('content/postatus/srcgetdateponk', $data);
            $this->load->view('partial/footer');
            $this->load->view('content/postatus/datatables');
        } else if ($userlv == '5' && $dep == 'KEUANGAN') {
            $tglstart   = $this->input->post('tglstart');
            $tglend     = $this->input->post('tglend');
            $_SESSION['vartgl1'] = $tglstart;
            $_SESSION['vartgl2'] = $tglend;

            $vartgl1            = $_SESSION['vartgl1'];
            $vartgl2            = $_SESSION['vartgl2'];
            $data['vartgl1']    = $vartgl1;
            $data['vartgl2']    = $vartgl2;
            $data['title']      = 'PO Status';

            $data['vcari']      = $this->M_Postatus->srchistoriadmpurchasing($vartgl1, $vartgl2);

            $this->load->view('partial/header', $data);
            $this->load->view('partial/sidebar');
            $this->load->view('content/postatus/srcgetdateponk', $data);
            $this->load->view('partial/footer');
            $this->load->view('content/postatus/datatables');
        } else if ($userlv == '5' && $dep != 'KEUANGAN') {
            $tglstart   = $this->input->post('tglstart');
            $tglend     = $this->input->post('tglend');
            $_SESSION['vartgl1'] = $tglstart;
            $_SESSION['vartgl2'] = $tglend;

            $vartgl1            = $_SESSION['vartgl1'];
            $vartgl2            = $_SESSION['vartgl2'];
            $data['vartgl1']    = $vartgl1;
            $data['vartgl2']    = $vartgl2;
            $data['title']      = 'PO Status';

            $data['vcari']      = $this->M_Postatus->srcgetdateponk($dep, $vartgl1, $vartgl2);

            $this->load->view('partial/header', $data);
            $this->load->view('partial/sidebar');
            $this->load->view('content/postatus/srcgetdateponk', $data);
            $this->load->view('partial/footer');
            $this->load->view('content/postatus/datatables');
        }
    }

    public function downloadfile($path)
    {
        force_download($path);
    }

    public function promosiseed()
    {
        $data['title'] = 'PO Promosi Seed';
        $data['hdone'] = $this->M_Postatus->getdatapodone()->result();

        $this->load->view('partial/header', $data);
        $this->load->view('partial/sidebar');
        $this->load->view('content/postatus/dashboard_seed', $data);
        $this->load->view('partial/footer');
        $this->load->view('content/postatus/datatables');
    }
}
