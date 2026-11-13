<?php
defined('BASEPATH') or exit('No direct script access allowed');

class C_Pojasa extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('PO/M_Pojasa');
        $this->load->library('form_validation');
    }

    public function index()
    {
        if (!$this->guardModuleAccess()) {
            return;
        }

        $data = $this->baseData('Purchase Order Jasa');
        $data['tables_ready'] = $this->M_Pojasa->tables_ready();
        $data['missing_tables'] = $data['tables_ready'] ? array() : $this->M_Pojasa->missing_tables();
        $data['vendors'] = array();
        $data['active_vendors'] = array();
        $data['requests'] = array();
        $data['summary'] = array(
            'total' => 0,
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

        if ($data['tables_ready']) {
            $lv = $this->session->userdata('lv');
            $kode = $this->session->userdata('kode');
            $departemen = $this->session->userdata('departemen');
            $data['vendors'] = $this->M_Pojasa->get_all_vendors();
            $data['active_vendors'] = $this->M_Pojasa->get_active_vendors();
            $data['requests'] = $this->M_Pojasa->get_accessible_requests($lv, $kode, $departemen);
            $data['summary'] = $this->M_Pojasa->dashboard_summary($lv, $kode, $departemen);
        }

        $this->load->view('partial/header', $data);
        $this->load->view('partial/sidebar');
        $this->load->view('content/po/jasa/index', $data);
        $this->load->view('partial/footer');
        $this->load->view('content/po/jasa/ajax', $data);
    }

    public function detail($kd_po_jasa)
    {
        if (!$this->M_Pojasa->tables_ready()) {
            $this->session->set_flashdata('error', 'Tabel PO Jasa belum tersedia. Jalankan migration database Tahap 1 sampai Tahap 3 terlebih dahulu.');
            redirect('pononkomersiljasa');
            return;
        }

        $request = $this->M_Pojasa->get_request($kd_po_jasa);
        if (!$request) {
            show_404();
            return;
        }

        if (!$this->canAccessRequest($request)) {
            show_404();
            return;
        }

        $data = $this->baseData('Detail PO Jasa');
        $data['request'] = $request;
        $data['active_vendors'] = $this->M_Pojasa->get_active_vendors();
        $data['details'] = $this->M_Pojasa->get_request_details($kd_po_jasa);
        $data['notes'] = $this->M_Pojasa->get_notes($kd_po_jasa);
        $data['progress'] = $this->M_Pojasa->get_progress($kd_po_jasa);
        $data['latest_progress'] = $this->M_Pojasa->latest_progress($kd_po_jasa);
        $data['files'] = $this->M_Pojasa->get_files($kd_po_jasa);
        $data['biaya'] = $this->M_Pojasa->get_biaya($kd_po_jasa);
        $data['biaya_summary'] = $this->M_Pojasa->biaya_summary($kd_po_jasa);
        $data['bast'] = $this->M_Pojasa->get_bast($kd_po_jasa);
        $data['payments'] = $this->M_Pojasa->get_payments($kd_po_jasa);
        $data['payment_summary'] = $this->M_Pojasa->payment_summary($kd_po_jasa);
        $data['evaluation'] = $this->M_Pojasa->get_evaluation($kd_po_jasa);

        $this->load->view('partial/header', $data);
        $this->load->view('partial/sidebar');
        $this->load->view('content/po/jasa/detail', $data);
        $this->load->view('partial/footer');
        $this->load->view('content/po/jasa/ajax', $data);
    }

    public function report()
    {
        if (!$this->guardModuleAccess()) {
            return;
        }

        if (!$this->M_Pojasa->tables_ready()) {
            $this->session->set_flashdata('error', 'Tabel PO Jasa belum tersedia. Jalankan migration database Tahap 1, Tahap 2, dan Tahap 3 terlebih dahulu.');
            redirect('pononkomersiljasa');
            return;
        }

        $filters = array(
            'tgl_start' => $this->input->get('tgl_start', true),
            'tgl_end' => $this->input->get('tgl_end', true),
            'kd_vendor_jasa' => $this->input->get('kd_vendor_jasa', true),
            'departemen' => $this->input->get('departemen', true),
            'lv' => $this->session->userdata('lv'),
            'kode_user' => $this->session->userdata('kode'),
            'access_departemen' => $this->session->userdata('departemen')
        );

        $data = $this->baseData('Report PO Jasa');
        $data['filters'] = $filters;
        $data['vendors'] = $this->M_Pojasa->get_all_vendors();
        $data['done_projects'] = $this->M_Pojasa->report_done_projects($filters);
        $data['vendor_performance'] = $this->M_Pojasa->vendor_performance($filters);
        $data['report_summary'] = $this->M_Pojasa->report_summary($filters);

        $this->load->view('partial/header', $data);
        $this->load->view('partial/sidebar');
        $this->load->view('content/po/jasa/report', $data);
        $this->load->view('partial/footer');
        $this->load->view('content/po/jasa/ajax', $data);
    }

    public function save_vendor()
    {
        if (!$this->guardTablesJson()) {
            return;
        }

        if (!$this->canManageVendor()) {
            $statusVendor = 'REQUEST';
        } else {
            $statusVendor = $this->input->post('status_vendor', true) === 'NONAKTIF' ? 'NONAKTIF' : 'AKTIF';
        }

        $nama_vendor = trim((string) $this->input->post('nama_vendor', true));
        if ($nama_vendor === '') {
            return $this->jsonResponse(false, 'Nama vendor wajib diisi.');
        }

        $data = array(
            'kd_vendor_jasa' => $this->M_Pojasa->generate_kd_vendor(),
            'nama_vendor' => $nama_vendor,
            'kategori_jasa' => trim((string) $this->input->post('kategori_jasa', true)),
            'nama_pic' => trim((string) $this->input->post('nama_pic', true)),
            'no_telpon' => trim((string) $this->input->post('no_telpon', true)),
            'email' => trim((string) $this->input->post('email', true)),
            'alamat_vendor' => trim((string) $this->input->post('alamat_vendor', true)),
            'npwp' => trim((string) $this->input->post('npwp', true)),
            'status_vendor' => $statusVendor,
            'created_by' => $this->session->userdata('kode')
        );

        $this->appendVendorAuditColumns($data, $statusVendor);

        if (!$this->M_Pojasa->insert_vendor($data)) {
            return $this->jsonResponse(false, 'Vendor jasa gagal disimpan.');
        }

        $message = $statusVendor === 'REQUEST'
            ? 'Request vendor jasa berhasil dikirim. Purchasing/Admin perlu ACC sebelum vendor dapat dipakai.'
            : 'Vendor jasa berhasil disimpan.';

        return $this->jsonResponse(true, $message);
    }

    public function update_vendor()
    {
        if (!$this->guardTablesJson()) {
            return;
        }

        if (!$this->canManageVendor()) {
            return $this->jsonResponse(false, 'Akses master vendor jasa hanya untuk Admin/Purchasing.');
        }

        $kd_vendor_jasa = $this->input->post('kd_vendor_jasa', true);
        $nama_vendor = trim((string) $this->input->post('nama_vendor', true));
        if ($kd_vendor_jasa === '' || $nama_vendor === '') {
            return $this->jsonResponse(false, 'Kode dan nama vendor wajib diisi.');
        }

        $data = array(
            'nama_vendor' => $nama_vendor,
            'kategori_jasa' => trim((string) $this->input->post('kategori_jasa', true)),
            'nama_pic' => trim((string) $this->input->post('nama_pic', true)),
            'no_telpon' => trim((string) $this->input->post('no_telpon', true)),
            'email' => trim((string) $this->input->post('email', true)),
            'alamat_vendor' => trim((string) $this->input->post('alamat_vendor', true)),
            'npwp' => trim((string) $this->input->post('npwp', true)),
            'status_vendor' => $this->input->post('status_vendor', true) === 'NONAKTIF' ? 'NONAKTIF' : 'AKTIF',
            'updated_by' => $this->session->userdata('kode')
        );

        if (!$this->M_Pojasa->update_vendor($kd_vendor_jasa, $data)) {
            return $this->jsonResponse(false, 'Vendor jasa gagal diperbarui.');
        }

        return $this->jsonResponse(true, 'Vendor jasa berhasil diperbarui.');
    }

    public function vendor_approval()
    {
        if (!$this->guardTablesJson()) {
            return;
        }

        if (!$this->canManageVendor()) {
            return $this->jsonResponse(false, 'ACC request vendor hanya untuk Admin/Purchasing.');
        }

        $kd_vendor_jasa = $this->input->post('kd_vendor_jasa', true);
        $action = $this->input->post('action', true);
        if ($kd_vendor_jasa === '' || !in_array($action, array('approve', 'reject'), true)) {
            return $this->jsonResponse(false, 'Data approval vendor tidak valid.');
        }

        $vendor = $this->M_Pojasa->get_vendor($kd_vendor_jasa);
        if (!$vendor) {
            return $this->jsonResponse(false, 'Vendor jasa tidak ditemukan.');
        }

        if ($vendor->status_vendor !== 'REQUEST') {
            return $this->jsonResponse(false, 'Hanya vendor dengan status REQUEST yang dapat diproses.');
        }

        $status = $action === 'approve' ? 'AKTIF' : 'REJECT';
        $data = array(
            'status_vendor' => $status,
            'updated_by' => $this->session->userdata('kode')
        );
        $this->appendVendorApprovalColumns($data);

        if (!$this->M_Pojasa->update_vendor($kd_vendor_jasa, $data)) {
            return $this->jsonResponse(false, 'Approval vendor jasa gagal diproses.');
        }

        return $this->jsonResponse(true, $status === 'AKTIF' ? 'Request vendor jasa berhasil di-ACC.' : 'Request vendor jasa ditolak.');
    }

    public function delete_vendor()
    {
        if (!$this->guardTablesJson()) {
            return;
        }

        if (!$this->canManageVendor()) {
            return $this->jsonResponse(false, 'Akses master vendor jasa hanya untuk Admin/Purchasing.');
        }

        $kd_vendor_jasa = $this->input->post('kd_vendor_jasa', true);
        if ($kd_vendor_jasa === '') {
            return $this->jsonResponse(false, 'Kode vendor tidak valid.');
        }

        if (!$this->M_Pojasa->delete_vendor($kd_vendor_jasa)) {
            return $this->jsonResponse(false, 'Vendor tidak dapat dihapus karena sudah dipakai pada request jasa.');
        }

        return $this->jsonResponse(true, 'Vendor jasa berhasil dihapus.');
    }

    public function save_request()
    {
        if (!$this->guardTablesJson()) {
            return;
        }

        if (!$this->canCreateRequest()) {
            return $this->jsonResponse(false, 'Request PO Jasa hanya dapat dibuat oleh PIC atau Admin/Purchasing.');
        }

        $tgl_request = $this->normalizeRequestDate($this->input->post('tgl_request', true));
        $tgl_target = $this->normalizeTargetDate($this->input->post('tgl_target', true), $tgl_request);
        $lokasi_pekerjaan = $this->normalizeLokasiPekerjaan($this->input->post('lokasi_pekerjaan', true));
        $tujuan = trim((string) $this->input->post('tujuan_pekerjaan', true));

        $vendorResult = $this->resolveRequestVendor(
            $this->input->post('kd_vendor_jasa', true),
            $this->input->post('nama_vendor_jasa', true)
        );

        if (!$vendorResult['success']) {
            return $this->jsonResponse(false, $vendorResult['message']);
        }

        if ($tgl_request === '' || $tgl_target === '' || $tujuan === '') {
            return $this->jsonResponse(false, 'Tanggal request dan deskripsi jasa wajib diisi.');
        }

        if ($tgl_target < $tgl_request) {
            return $this->jsonResponse(false, 'Target selesai tidak boleh lebih awal dari tanggal request.');
        }

        $details = $this->buildRequestDetails(array('allow_zero_price' => true));
        if (empty($details['rows'])) {
            return $this->jsonResponse(false, 'Minimal satu scope pekerjaan wajib diisi.');
        }

        date_default_timezone_set('Asia/Jakarta');
        $kd_po_jasa = $this->M_Pojasa->generate_kd_po_jasa();
        $kd_user = $this->session->userdata('kode');
        $nama_user = $this->session->userdata('nama_user');
        $departemen = $this->session->userdata('departemen');

        $header = array(
            'kd_po_jasa' => $kd_po_jasa,
            'kd_vendor_jasa' => $vendorResult['vendor']->kd_vendor_jasa,
            'kd_user' => $kd_user,
            'nm_user' => $nama_user,
            'departemen' => $departemen,
            'tgl_request' => $tgl_request,
            'tgl_target' => $tgl_target,
            'lokasi_pekerjaan' => $lokasi_pekerjaan,
            'tujuan_pekerjaan' => $tujuan,
            'estimasi_total' => $details['total'],
            'status' => 'ON PROGRESS'
        );
        $this->appendRequestSplitTotals($header, $details);

        $rows = array();
        foreach ($details['rows'] as $row) {
            $row['kd_po_jasa'] = $kd_po_jasa;
            $rows[] = $row;
        }

        $files = $this->collectPojasaUploads($kd_po_jasa, 'dokumen_project_jasa', 'DOKUMEN PENDUKUNG', $this->input->post('keterangan_project_file'), $departemen, $tgl_request);
        if (!$files['success']) {
            return $this->jsonResponse(false, $files['message']);
        }

        if (empty($files['rows'])) {
            return $this->jsonResponse(false, 'Dokumen pendukung wajib diupload sebelum request diajukan ke KADEP.');
        }

        $note = $this->noteData($kd_po_jasa, 'Request Pekerjaan Jasa Baru', 'ON PROGRESS');
        if (!$this->M_Pojasa->insert_request($header, $rows, $note, $files['rows'])) {
            return $this->jsonResponse(false, 'Request PO Jasa gagal disimpan.');
        }

        return $this->jsonResponse(true, 'Request PO Jasa berhasil disimpan.', array(
            'redirect' => base_url('pojasa/detail/' . $kd_po_jasa)
        ));
    }

    public function revise_request()
    {
        if (!$this->guardTablesJson()) {
            return;
        }

        $kd_po_jasa = $this->input->post('kd_po_jasa', true);
        $request = $this->M_Pojasa->get_request($kd_po_jasa);
        if (!$request) {
            return $this->jsonResponse(false, 'Data PO Jasa tidak ditemukan.');
        }

        if (!$this->canReviseRequest($request)) {
            return $this->jsonResponse(false, 'Revisi hanya dapat dilakukan PIC pemilik request ketika status REJECT atau PENDING.');
        }

        $tgl_request = $this->normalizeRequestDate($this->input->post('tgl_request', true), $request->tgl_request);
        $tgl_target = $this->normalizeTargetDate($this->input->post('tgl_target', true), $tgl_request);
        $lokasi_pekerjaan = $this->normalizeLokasiPekerjaan($this->input->post('lokasi_pekerjaan', true), $request->lokasi_pekerjaan);
        $tujuan = trim((string) $this->input->post('tujuan_pekerjaan', true));

        $vendorResult = $this->resolveRequestVendor(
            $this->input->post('kd_vendor_jasa', true),
            $this->input->post('nama_vendor_jasa', true)
        );

        if (!$vendorResult['success']) {
            return $this->jsonResponse(false, $vendorResult['message']);
        }

        if ($tgl_request === '' || $tgl_target === '' || $tujuan === '') {
            return $this->jsonResponse(false, 'Tanggal request dan deskripsi jasa wajib diisi.');
        }

        if ($tgl_target < $tgl_request) {
            return $this->jsonResponse(false, 'Target selesai tidak boleh lebih awal dari tanggal request.');
        }

        $details = $this->buildRequestDetails(array('allow_zero_price' => true));
        if (empty($details['rows'])) {
            return $this->jsonResponse(false, 'Minimal satu scope pekerjaan wajib diisi.');
        }

        $rows = array();
        foreach ($details['rows'] as $row) {
            $row['kd_po_jasa'] = $kd_po_jasa;
            $rows[] = $row;
        }

        $files = $this->collectPojasaUploads($kd_po_jasa, 'dokumen_project_jasa', 'DOKUMEN REVISI', $this->input->post('keterangan_project_file'), $request->departemen, $request->tgl_request);
        if (!$files['success']) {
            return $this->jsonResponse(false, $files['message']);
        }

        $header = array(
            'kd_vendor_jasa' => $vendorResult['vendor']->kd_vendor_jasa,
            'tgl_request' => $tgl_request,
            'tgl_target' => $tgl_target,
            'lokasi_pekerjaan' => $lokasi_pekerjaan,
            'tujuan_pekerjaan' => $tujuan,
            'estimasi_total' => $details['total'],
            'status' => 'ON PROGRESS'
        );
        $this->appendRequestSplitTotals($header, $details);

        if (!$this->M_Pojasa->update_request_revision($kd_po_jasa, $header, $rows, $this->noteData($kd_po_jasa, 'Revisi request dikirim ulang ke KADEP', 'ON PROGRESS'), $files['rows'])) {
            return $this->jsonResponse(false, 'Revisi PO Jasa gagal disimpan.');
        }

        return $this->jsonResponse(true, 'Revisi PO Jasa berhasil dikirim ulang ke KADEP.', array(
            'redirect' => base_url('pojasa/detail/' . $kd_po_jasa)
        ));
    }

    public function save_scope_review()
    {
        if (!$this->guardTablesJson()) {
            return;
        }

        if (!$this->canPurchasingAdmin()) {
            return $this->jsonResponse(false, 'Review scope hanya dapat dilakukan Purchasing/Admin.');
        }

        $kd_po_jasa = $this->input->post('kd_po_jasa', true);
        $request = $this->M_Pojasa->get_request($kd_po_jasa);
        if (!$request) {
            return $this->jsonResponse(false, 'Data PO Jasa tidak ditemukan.');
        }

        if (!in_array($request->status, array('ACC-KADEP', 'REVIEW PURCHASING'), true)) {
            return $this->jsonResponse(false, 'Scope hanya dapat direview setelah ACC KADEP dan sebelum diajukan ke direktur.');
        }

        $details = $this->buildRequestDetails();
        if (empty($details['rows'])) {
            return $this->jsonResponse(false, 'Minimal satu scope pekerjaan wajib diisi dengan nilai biaya lebih dari 0.');
        }

        $rows = array();
        foreach ($details['rows'] as $row) {
            $row['kd_po_jasa'] = $kd_po_jasa;
            $rows[] = $row;
        }

        $update = array(
            'estimasi_total' => $details['total'],
            'status' => 'REVIEW PURCHASING'
        );
        $this->appendRequestSplitTotals($update, $details);
        $this->appendPurchasingReviewColumns($update);

        if (!$this->M_Pojasa->replace_request_details($kd_po_jasa, $rows, $update, $this->noteData($kd_po_jasa, 'Purchasing review scope dan estimasi biaya', 'REVIEW PURCHASING'))) {
            return $this->jsonResponse(false, 'Review scope PO Jasa gagal disimpan.');
        }

        return $this->jsonResponse(true, 'Review scope dan estimasi biaya berhasil disimpan.', array(
            'redirect' => base_url('pojasa/detail/' . $kd_po_jasa)
        ));
    }

    public function approval()
    {
        if (!$this->guardTablesJson()) {
            return;
        }

        $kd_po_jasa = $this->input->post('kd_po_jasa', true);
        $action = $this->input->post('action', true);
        $note = trim((string) $this->input->post('note', true));
        $request = $this->M_Pojasa->get_request($kd_po_jasa);

        if (!$request) {
            return $this->jsonResponse(false, 'Data PO Jasa tidak ditemukan.');
        }

        $transition = $this->approvalTransition($request, $action);
        if (!$transition['success']) {
            return $this->jsonResponse(false, $transition['message']);
        }

        $update = $transition['update'];
        $noteText = $transition['note'];
        if ($note !== '') {
            $noteText .= ' - ' . $note;
        }

        if (!$this->M_Pojasa->update_status($kd_po_jasa, $update, $this->noteData($kd_po_jasa, $noteText, $update['status']))) {
            return $this->jsonResponse(false, 'Status PO Jasa gagal diperbarui.');
        }

        return $this->jsonResponse(true, 'Status PO Jasa berhasil diperbarui.', array(
            'redirect' => base_url('pojasa/detail/' . $kd_po_jasa)
        ));
    }

    public function generate_spk()
    {
        if (!$this->guardTablesJson()) {
            return;
        }

        if ((string) $this->session->userdata('lv') !== '2' && (string) $this->session->userdata('lv') !== '1') {
            return $this->jsonResponse(false, 'Generate PO/SPK Jasa hanya dapat dilakukan Purchasing/Admin.');
        }

        $kd_po_jasa = $this->input->post('kd_po_jasa', true);
        $request = $this->M_Pojasa->get_request($kd_po_jasa);
        if (!$request) {
            return $this->jsonResponse(false, 'Data PO Jasa tidak ditemukan.');
        }

        if (!in_array($request->status, array('ACC DIREKTUR', 'PROGRESS VENDOR'), true) || $request->no_spk) {
            return $this->jsonResponse(false, 'PO/SPK Jasa hanya dapat diterbitkan setelah ACC DIREKTUR dan sebelum nomor SPK pernah dibuat.');
        }

        $no_spk = $this->M_Pojasa->generate_no_spk();
        $update = array(
            'no_spk' => $no_spk,
            'generated_by' => $this->session->userdata('kode'),
            'generated_at' => date('Y-m-d H:i:s')
        );

        if ($request->status === 'ACC DIREKTUR') {
            $update['status'] = 'SPK TERBIT';
        }

        $statusNote = isset($update['status']) ? $update['status'] : $request->status;
        if (!$this->M_Pojasa->update_status($kd_po_jasa, $update, $this->noteData($kd_po_jasa, 'PO/SPK Jasa Terbit: ' . $no_spk, $statusNote))) {
            return $this->jsonResponse(false, 'PO/SPK Jasa gagal diterbitkan.');
        }

        return $this->jsonResponse(true, 'PO/SPK Jasa berhasil diterbitkan.', array(
            'redirect' => base_url('pojasa/detail/' . $kd_po_jasa)
        ));
    }

    public function save_progress()
    {
        if (!$this->guardTablesJson()) {
            return;
        }

        $kd_po_jasa = $this->input->post('kd_po_jasa', true);
        $request = $this->M_Pojasa->get_request($kd_po_jasa);
        if (!$request) {
            return $this->jsonResponse(false, 'Data PO Jasa tidak ditemukan.');
        }

        if (!$this->canUpdateProgress($request)) {
            return $this->jsonResponse(false, 'Tracking progress vendor hanya dapat diinput oleh PIC terkait atau Admin/Purchasing.');
        }

        if (!$this->canProcessStage2($request)) {
            return $this->jsonResponse(false, 'Progress vendor hanya dapat diinput setelah ACC DIREKTUR dan sebelum DONE.');
        }

        $tgl_progress = $this->input->post('tgl_progress', true);
        $milestone = trim((string) $this->input->post('milestone', true));
        $progress_persen = $this->parseNumericInput($this->input->post('progress_persen', true));
        $status_progress = trim((string) $this->input->post('status_progress', true));
        $catatan = trim((string) $this->input->post('catatan', true));

        if ($tgl_progress === '' || $milestone === '' || $status_progress === '') {
            return $this->jsonResponse(false, 'Tanggal, milestone, dan status progress wajib diisi.');
        }

        if ($progress_persen < 0 || $progress_persen > 100) {
            return $this->jsonResponse(false, 'Progress persen wajib berada di antara 0 sampai 100.');
        }

        $data = array(
            'kd_po_jasa' => $kd_po_jasa,
            'tgl_progress' => $tgl_progress,
            'milestone' => $milestone,
            'progress_persen' => $progress_persen,
            'status_progress' => $status_progress,
            'catatan' => $catatan,
            'created_by' => $this->session->userdata('kode'),
            'created_name' => $this->session->userdata('nama_user')
        );

        $update = array();
        if (in_array($request->status, array('ACC DIREKTUR', 'SPK TERBIT'), true) && $progress_persen > 0) {
            $update = array('status' => 'PROGRESS VENDOR');
        }

        if (!$this->M_Pojasa->insert_progress_and_note($kd_po_jasa, $data, $update, $this->noteData($kd_po_jasa, 'Update progress vendor: ' . $milestone . ' (' . $progress_persen . '%)', empty($update) ? $request->status : $update['status']))) {
            return $this->jsonResponse(false, 'Progress vendor gagal disimpan.');
        }

        return $this->jsonResponse(true, 'Progress vendor berhasil disimpan.', array(
            'redirect' => base_url('pojasa/detail/' . $kd_po_jasa)
        ));
    }

    public function upload_file()
    {
        if (!$this->guardTablesJson()) {
            return;
        }

        $kd_po_jasa = $this->input->post('kd_po_jasa', true);
        $request = $this->M_Pojasa->get_request($kd_po_jasa);
        if (!$request) {
            return $this->jsonResponse(false, 'Data PO Jasa tidak ditemukan.');
        }

        if (!$this->canUploadProjectDocument($request)) {
            return $this->jsonResponse(false, 'Upload dokumen pendukung hanya dapat dilakukan oleh PIC terkait atau Admin/Purchasing sebelum diajukan ke direktur dan sebelum project DONE.');
        }

        if (empty($_FILES['dokumen_jasa']['name'])) {
            return $this->jsonResponse(false, 'File dokumen wajib dipilih.');
        }

        $jenis_dokumen = trim((string) $this->input->post('jenis_dokumen', true));
        $files = $this->collectPojasaUploads($kd_po_jasa, 'dokumen_jasa', $jenis_dokumen === '' ? 'DOKUMEN PENDUKUNG' : $jenis_dokumen, $this->input->post('keterangan_file'), $request->departemen, $request->tgl_request);
        if (!$files['success']) {
            return $this->jsonResponse(false, $files['message']);
        }

        if (empty($files['rows'])) {
            return $this->jsonResponse(false, 'File dokumen wajib dipilih.');
        }

        if (!$this->M_Pojasa->insert_files_and_note($files['rows'], $this->noteData($kd_po_jasa, 'Upload dokumen pendukung jasa: ' . count($files['rows']) . ' file', $request->status))) {
            return $this->jsonResponse(false, 'Dokumen pendukung PO Jasa gagal disimpan.');
        }

        return $this->jsonResponse(true, 'Dokumen pendukung PO Jasa berhasil diupload.', array(
            'files' => $this->M_Pojasa->get_files($kd_po_jasa)
        ));
    }

    public function update_file()
    {
        if (!$this->guardTablesJson()) {
            return;
        }

        $fileContext = $this->fileContextFromPost();
        if (!$fileContext['success']) {
            return $this->jsonResponse(false, $fileContext['message']);
        }

        $file = $fileContext['file'];
        $request = $fileContext['request'];
        $jenis_dokumen = trim((string) $this->input->post('jenis_dokumen', true));
        $keterangan = trim((string) $this->input->post('keterangan_file', true));

        if ($jenis_dokumen === '') {
            return $this->jsonResponse(false, 'Jenis dokumen wajib diisi.');
        }

        $update = array(
            'jenis_dokumen' => $jenis_dokumen,
            'keterangan' => $keterangan
        );

        if (!$this->M_Pojasa->update_file_and_note($file->id_file_jasa, $update, $this->noteData($request->kd_po_jasa, 'Update data dokumen pendukung: ' . $file->file_original, $request->status))) {
            return $this->jsonResponse(false, 'Data dokumen pendukung gagal diperbarui.');
        }

        return $this->jsonResponse(true, 'Data dokumen pendukung berhasil diperbarui.', array(
            'files' => $this->M_Pojasa->get_files($request->kd_po_jasa)
        ));
    }

    public function replace_file()
    {
        if (!$this->guardTablesJson()) {
            return;
        }

        $fileContext = $this->fileContextFromPost();
        if (!$fileContext['success']) {
            return $this->jsonResponse(false, $fileContext['message']);
        }

        if (empty($_FILES['dokumen_jasa']['name'])) {
            return $this->jsonResponse(false, 'File pengganti wajib dipilih.');
        }

        $file = $fileContext['file'];
        $request = $fileContext['request'];
        $uploadResult = $this->uploadPojasaFile('dokumen_jasa', $request->departemen, $request->tgl_request);
        if (!$uploadResult['success']) {
            return $this->jsonResponse(false, $uploadResult['message']);
        }

        $jenis_dokumen = trim((string) $this->input->post('jenis_dokumen', true));
        $keterangan = trim((string) $this->input->post('keterangan_file', true));
        $update = array(
            'jenis_dokumen' => $jenis_dokumen === '' ? $file->jenis_dokumen : $jenis_dokumen,
            'keterangan' => $keterangan,
            'file_name' => $uploadResult['file_name'],
            'file_original' => $uploadResult['client_name'],
            'file_ext' => $uploadResult['file_ext'],
            'file_size' => $uploadResult['file_size'],
            'uploaded_by' => $this->session->userdata('kode'),
            'uploaded_name' => $this->session->userdata('nama_user')
        );

        if (!$this->M_Pojasa->update_file_and_note($file->id_file_jasa, $update, $this->noteData($request->kd_po_jasa, 'Replace dokumen pendukung: ' . $file->file_original, $request->status))) {
            $this->deletePhysicalPojasaFile($uploadResult['file_name']);
            return $this->jsonResponse(false, 'Dokumen pendukung gagal direplace.');
        }

        $this->deletePhysicalPojasaFile($file->file_name);

        return $this->jsonResponse(true, 'Dokumen pendukung berhasil direplace.', array(
            'files' => $this->M_Pojasa->get_files($request->kd_po_jasa)
        ));
    }

    public function delete_file()
    {
        if (!$this->guardTablesJson()) {
            return;
        }

        $fileContext = $this->fileContextFromPost();
        if (!$fileContext['success']) {
            return $this->jsonResponse(false, $fileContext['message']);
        }

        $file = $fileContext['file'];
        $request = $fileContext['request'];
        if (!$this->M_Pojasa->delete_file_and_note($file->id_file_jasa, $this->noteData($request->kd_po_jasa, 'Hapus dokumen pendukung: ' . $file->file_original, $request->status))) {
            return $this->jsonResponse(false, 'Dokumen pendukung gagal dihapus.');
        }

        $this->deletePhysicalPojasaFile($file->file_name);

        return $this->jsonResponse(true, 'Dokumen pendukung berhasil dihapus.', array(
            'redirect' => base_url('pojasa/detail/' . $request->kd_po_jasa)
        ));
    }

    public function view_file($id_file_jasa)
    {
        $this->serve_file($id_file_jasa, false);
    }

    public function download_file($id_file_jasa)
    {
        $this->serve_file($id_file_jasa, true);
    }

    public function save_biaya()
    {
        if (!$this->guardTablesJson()) {
            return;
        }

        $kd_po_jasa = $this->input->post('kd_po_jasa', true);
        $request = $this->M_Pojasa->get_request($kd_po_jasa);
        if (!$request) {
            return $this->jsonResponse(false, 'Data PO Jasa tidak ditemukan.');
        }

        if (!$this->canPurchasingAdmin() || !$this->canProcessStage2($request)) {
            return $this->jsonResponse(false, 'Audit biaya hanya dapat diinput Purchasing/Admin setelah ACC DIREKTUR dan sebelum DONE.');
        }

        $jenis_biaya = trim((string) $this->input->post('jenis_biaya', true));
        $deskripsi_biaya = trim((string) $this->input->post('deskripsi_biaya', true));
        $nominal_estimasi = $this->parseNumericInput($this->input->post('nominal_estimasi', true));
        $nominal_realisasi = $this->parseNumericInput($this->input->post('nominal_realisasi', true));
        $no_invoice = trim((string) $this->input->post('no_invoice_vendor', true));
        $tgl_biaya = $this->input->post('tgl_biaya', true);

        if ($jenis_biaya === '' || $deskripsi_biaya === '' || $tgl_biaya === '') {
            return $this->jsonResponse(false, 'Jenis biaya, deskripsi, dan tanggal biaya wajib diisi.');
        }

        if ($nominal_estimasi < 0 || $nominal_realisasi < 0) {
            return $this->jsonResponse(false, 'Nominal estimasi dan realisasi tidak boleh minus.');
        }

        if ($nominal_estimasi == 0 && $nominal_realisasi == 0) {
            return $this->jsonResponse(false, 'Minimal salah satu nominal biaya harus lebih dari 0.');
        }

        $biaya = array(
            'kd_po_jasa' => $kd_po_jasa,
            'tgl_biaya' => $tgl_biaya,
            'jenis_biaya' => $jenis_biaya,
            'deskripsi_biaya' => $deskripsi_biaya,
            'nominal_estimasi' => $nominal_estimasi,
            'nominal_realisasi' => $nominal_realisasi,
            'selisih' => $nominal_realisasi - $nominal_estimasi,
            'no_invoice_vendor' => $no_invoice,
            'created_by' => $this->session->userdata('kode'),
            'created_name' => $this->session->userdata('nama_user')
        );

        if (!$this->M_Pojasa->insert_biaya_and_note($biaya, $this->noteData($kd_po_jasa, 'Audit biaya jasa: ' . $jenis_biaya, $request->status))) {
            return $this->jsonResponse(false, 'Audit biaya PO Jasa gagal disimpan.');
        }

        return $this->jsonResponse(true, 'Audit biaya PO Jasa berhasil disimpan.', array(
            'redirect' => base_url('pojasa/detail/' . $kd_po_jasa)
        ));
    }

    public function complete_bast()
    {
        if (!$this->guardTablesJson()) {
            return;
        }

        $kd_po_jasa = $this->input->post('kd_po_jasa', true);
        $request = $this->M_Pojasa->get_request($kd_po_jasa);
        if (!$request) {
            return $this->jsonResponse(false, 'Data PO Jasa tidak ditemukan.');
        }

        if (!$this->canPurchasingAdmin() || !$this->canProcessStage2($request)) {
            return $this->jsonResponse(false, 'BAST hanya dapat diproses Purchasing/Admin setelah ACC DIREKTUR dan sebelum DONE.');
        }

        $latestProgress = $this->M_Pojasa->latest_progress($kd_po_jasa);
        if (!$latestProgress || (float) $latestProgress->progress_persen < 100) {
            return $this->jsonResponse(false, 'Project hanya dapat DONE setelah progress mencapai 100%.');
        }

        if ($this->M_Pojasa->get_bast($kd_po_jasa)) {
            return $this->jsonResponse(false, 'BAST untuk PO Jasa ini sudah pernah dibuat.');
        }

        $no_bast = trim((string) $this->input->post('no_bast', true));
        $tgl_bast = $this->input->post('tgl_bast', true);
        $penerima_pekerjaan = trim((string) $this->input->post('penerima_pekerjaan', true));
        $catatan_bast = trim((string) $this->input->post('catatan_bast', true));

        if ($no_bast === '' || $tgl_bast === '' || $penerima_pekerjaan === '') {
            return $this->jsonResponse(false, 'No BAST, tanggal BAST, dan penerima pekerjaan wajib diisi.');
        }

        $bast = array(
            'kd_po_jasa' => $kd_po_jasa,
            'no_bast' => $no_bast,
            'tgl_bast' => $tgl_bast,
            'penerima_pekerjaan' => $penerima_pekerjaan,
            'catatan_bast' => $catatan_bast,
            'created_by' => $this->session->userdata('kode'),
            'created_name' => $this->session->userdata('nama_user')
        );

        $update = array(
            'status' => 'DONE'
        );

        if ($this->db->field_exists('completed_at', 'tbpo_jasa_request')) {
            $update['completed_at'] = date('Y-m-d H:i:s');
        }

        if (!$this->M_Pojasa->insert_bast_and_done($kd_po_jasa, $bast, $this->noteData($kd_po_jasa, 'BAST selesai: ' . $no_bast, 'DONE'), $update)) {
            return $this->jsonResponse(false, 'BAST PO Jasa gagal disimpan.');
        }

        return $this->jsonResponse(true, 'BAST berhasil disimpan dan project PO Jasa menjadi DONE.', array(
            'redirect' => base_url('pojasa/detail/' . $kd_po_jasa)
        ));
    }

    public function save_payment()
    {
        if (!$this->guardTablesJson()) {
            return;
        }

        $kd_po_jasa = $this->input->post('kd_po_jasa', true);
        $request = $this->M_Pojasa->get_request($kd_po_jasa);
        if (!$request) {
            return $this->jsonResponse(false, 'Data PO Jasa tidak ditemukan.');
        }

        if (!$this->canPurchasingAdmin()) {
            return $this->jsonResponse(false, 'Payment tracking vendor hanya dapat diinput Purchasing/Admin.');
        }

        if (!$this->canProcessPayment($request)) {
            return $this->jsonResponse(false, 'Payment tracking hanya dapat diinput setelah ACC DIREKTUR.');
        }

        $tgl_invoice = $this->input->post('tgl_invoice', true);
        $jatuh_tempo = $this->input->post('jatuh_tempo', true);
        $no_invoice = trim((string) $this->input->post('no_invoice', true));
        $nominal_tagihan = $this->parseNumericInput($this->input->post('nominal_tagihan', true));
        $nominal_bayar = $this->parseNumericInput($this->input->post('nominal_bayar', true));
        $status_bayar = $this->input->post('status_bayar', true);

        if ($tgl_invoice === '' || $jatuh_tempo === '' || $no_invoice === '') {
            return $this->jsonResponse(false, 'Tanggal invoice, jatuh tempo, dan no invoice wajib diisi.');
        }

        if ($nominal_tagihan <= 0 || $nominal_bayar < 0) {
            return $this->jsonResponse(false, 'Nominal tagihan wajib lebih dari 0 dan nominal bayar tidak boleh minus.');
        }

        if (!in_array($status_bayar, array('BELUM BAYAR', 'PARTIAL', 'LUNAS'), true)) {
            return $this->jsonResponse(false, 'Status bayar tidak valid.');
        }

        if ($nominal_bayar > $nominal_tagihan) {
            return $this->jsonResponse(false, 'Nominal bayar tidak boleh lebih besar dari nominal tagihan.');
        }

        if ($nominal_bayar >= $nominal_tagihan && $status_bayar !== 'LUNAS') {
            $status_bayar = 'LUNAS';
        } elseif ($nominal_bayar > 0 && $status_bayar === 'BELUM BAYAR') {
            $status_bayar = 'PARTIAL';
        }

        $payment = array(
            'kd_po_jasa' => $kd_po_jasa,
            'tgl_invoice' => $tgl_invoice,
            'jatuh_tempo' => $jatuh_tempo,
            'no_invoice' => $no_invoice,
            'nominal_tagihan' => $nominal_tagihan,
            'nominal_bayar' => $nominal_bayar,
            'status_bayar' => $status_bayar,
            'catatan_payment' => trim((string) $this->input->post('catatan_payment', true)),
            'created_by' => $this->session->userdata('kode'),
            'created_name' => $this->session->userdata('nama_user')
        );

        if (!$this->M_Pojasa->insert_payment_and_note($payment, $this->noteData($kd_po_jasa, 'Payment tracking vendor: ' . $no_invoice . ' - ' . $status_bayar, $request->status))) {
            return $this->jsonResponse(false, 'Payment tracking PO Jasa gagal disimpan.');
        }

        return $this->jsonResponse(true, 'Payment tracking PO Jasa berhasil disimpan.', array(
            'redirect' => base_url('pojasa/detail/' . $kd_po_jasa)
        ));
    }

    public function save_evaluation()
    {
        if (!$this->guardTablesJson()) {
            return;
        }

        $kd_po_jasa = $this->input->post('kd_po_jasa', true);
        $request = $this->M_Pojasa->get_request($kd_po_jasa);
        if (!$request) {
            return $this->jsonResponse(false, 'Data PO Jasa tidak ditemukan.');
        }

        if (!$this->canPurchasingAdmin()) {
            return $this->jsonResponse(false, 'Evaluasi vendor hanya dapat diinput Purchasing/Admin.');
        }

        if ($request->status !== 'DONE') {
            return $this->jsonResponse(false, 'Evaluasi vendor hanya dapat diinput setelah project DONE.');
        }

        $kualitas = $this->parseNumericInput($this->input->post('kualitas_score', true));
        $waktu = $this->parseNumericInput($this->input->post('ketepatan_waktu_score', true));
        $biaya = $this->parseNumericInput($this->input->post('biaya_score', true));

        if (!$this->validScore($kualitas) || !$this->validScore($waktu) || !$this->validScore($biaya)) {
            return $this->jsonResponse(false, 'Score evaluasi wajib di antara 1 sampai 5.');
        }

        $totalScore = round(($kualitas + $waktu + $biaya) / 3, 2);
        $evaluation = array(
            'kd_po_jasa' => $kd_po_jasa,
            'kd_vendor_jasa' => $request->kd_vendor_jasa,
            'kualitas_score' => $kualitas,
            'ketepatan_waktu_score' => $waktu,
            'biaya_score' => $biaya,
            'total_score' => $totalScore,
            'catatan_evaluasi' => trim((string) $this->input->post('catatan_evaluasi', true)),
            'evaluated_by' => $this->session->userdata('kode'),
            'evaluated_name' => $this->session->userdata('nama_user')
        );

        if (!$this->M_Pojasa->save_evaluation_and_note($evaluation, $this->noteData($kd_po_jasa, 'Evaluasi vendor tersimpan. Score: ' . $totalScore, 'DONE'))) {
            return $this->jsonResponse(false, 'Evaluasi vendor gagal disimpan.');
        }

        return $this->jsonResponse(true, 'Evaluasi vendor berhasil disimpan.', array(
            'redirect' => base_url('pojasa/detail/' . $kd_po_jasa)
        ));
    }

    private function baseData($title)
    {
        return array(
            'title' => $title,
            'depuser' => $this->session->userdata('departemen'),
            'nmuser' => $this->session->userdata('nama_user'),
            'kduser' => $this->session->userdata('kode'),
            'lv' => $this->session->userdata('lv'),
            'can_create_request' => $this->canCreateRequest()
        );
    }

    private function guardModuleAccess()
    {
        if ($this->canAccessModule()) {
            return true;
        }

        $this->session->set_flashdata('error', 'Akses PO Jasa hanya untuk PIC, KADEP, DIREKTUR, Admin, atau Purchasing.');
        redirect('dashboard');

        return false;
    }

    private function canAccessModule()
    {
        return in_array((string) $this->session->userdata('lv'), array('1', '2', '3', '4', '5'), true);
    }

    private function canCreateRequest()
    {
        return in_array((string) $this->session->userdata('lv'), array('1', '2', '4'), true);
    }

    private function canAccessRequest($request)
    {
        $lv = (string) $this->session->userdata('lv');
        $kode = $this->session->userdata('kode');
        $departemen = $this->session->userdata('departemen');

        if (in_array($lv, array('1', '2', '3'), true)) {
            return true;
        }

        if ($lv === '4') {
            return $request->kd_user === $kode;
        }

        if ($lv === '5') {
            return $request->departemen === $departemen;
        }

        return false;
    }

    private function canManageVendor()
    {
        $lv = (string) $this->session->userdata('lv');

        return $lv === '1' || $lv === '2';
    }

    private function canPurchasingAdmin()
    {
        return $this->canManageVendor();
    }

    private function canProcessStage2($request)
    {
        return in_array($request->status, array('ACC DIREKTUR', 'SPK TERBIT', 'PROGRESS VENDOR'), true);
    }

    private function canProcessPayment($request)
    {
        return in_array($request->status, array('ACC DIREKTUR', 'SPK TERBIT', 'PROGRESS VENDOR', 'DONE'), true);
    }

    private function canUpdateProgress($request)
    {
        $lv = (string) $this->session->userdata('lv');
        $kode = $this->session->userdata('kode');

        return in_array($lv, array('1', '2'), true) || ($lv === '4' && $request->kd_user === $kode);
    }

    private function canUploadProjectDocument($request)
    {
        $lv = (string) $this->session->userdata('lv');

        return in_array($lv, array('1', '2'), true) && in_array($request->status, array('ACC-KADEP', 'REVIEW PURCHASING'), true);
    }

    private function canReviewProjectDocument($request)
    {
        return $this->canAccessRequest($request) || in_array((string) $this->session->userdata('lv'), array('1', '2', '3'), true);
    }

    private function canReviseRequest($request)
    {
        $lv = (string) $this->session->userdata('lv');
        $kode = $this->session->userdata('kode');

        return $lv === '4' && $request->kd_user === $kode && in_array($request->status, array('REJECT', 'PENDING'), true);
    }

    private function resolveRequestVendor($vendorCode, $vendorName)
    {
        $vendorCode = trim((string) $vendorCode);
        $vendorName = trim((string) $vendorName);
        $vendorReference = $vendorCode !== '' ? $vendorCode : $vendorName;

        if ($vendorReference !== '') {
            $vendor = $this->M_Pojasa->get_vendor($vendorReference);
            if ($vendor && $vendor->status_vendor === 'AKTIF') {
                return array('success' => true, 'vendor' => $vendor);
            }
        }

        if ($vendorName === '') {
            return array('success' => false, 'message' => 'Toko / Vendor wajib diisi.');
        }

        $vendor = $this->M_Pojasa->get_active_vendor_by_name($vendorName);
        if ($vendor) {
            return array('success' => true, 'vendor' => $vendor);
        }

        $data = array(
            'kd_vendor_jasa' => $this->M_Pojasa->generate_kd_vendor(),
            'nama_vendor' => $vendorName,
            'kategori_jasa' => '',
            'nama_pic' => '',
            'no_telpon' => '',
            'email' => '',
            'alamat_vendor' => '',
            'npwp' => '',
            'status_vendor' => 'AKTIF',
            'created_by' => $this->session->userdata('kode')
        );
        $this->appendVendorAuditColumns($data, 'AKTIF');

        if (!$this->M_Pojasa->insert_vendor($data)) {
            return array('success' => false, 'message' => 'Toko / Vendor gagal disimpan.');
        }

        $vendor = $this->M_Pojasa->get_vendor($data['kd_vendor_jasa']);
        if (!$vendor) {
            return array('success' => false, 'message' => 'Toko / Vendor gagal dimuat setelah disimpan.');
        }

        return array('success' => true, 'vendor' => $vendor);
    }

    private function guardTablesJson()
    {
        if ($this->M_Pojasa->tables_ready()) {
            return true;
        }

        $missing = implode(', ', $this->M_Pojasa->missing_tables());
        $this->jsonResponse(false, 'Tabel PO Jasa belum tersedia: ' . $missing . '. Jalankan migration database Tahap 1 sampai Tahap 3 terlebih dahulu.');

        return false;
    }

    private function buildRequestDetails($options = array())
    {
        $allowZeroPrice = !empty($options['allow_zero_price']);
        $nama = $this->input->post('nama_pekerjaan');
        $deskripsi = $this->input->post('deskripsi');
        $qty = $this->input->post('qty');
        $satuan = $this->input->post('satuan');
        $harga = $this->input->post('hrg_satuan');
        $jenis = $this->input->post('jenis_detail');
        $supportSplitDetail = $this->db->field_exists('jenis_detail', 'tbpo_jasa_request_detail');
        $rows = array();
        $total = 0;
        $totalJasa = 0;
        $totalBahan = 0;

        if (!is_array($nama)) {
            return array('rows' => array(), 'total' => 0);
        }

        foreach ($nama as $index => $namaPekerjaan) {
            $namaPekerjaan = trim((string) $namaPekerjaan);
            $qtyValue = $this->parseNumericInput(isset($qty[$index]) ? $qty[$index] : 0);
            $hargaValue = $this->parseNumericInput(isset($harga[$index]) ? $harga[$index] : 0);
            if ($qtyValue <= 0) {
                if (!$allowZeroPrice) {
                    continue;
                }

                $qtyValue = 1;
            }
            $subtotal = $qtyValue * $hargaValue;

            if ($namaPekerjaan === '') {
                continue;
            }

            if (!$allowZeroPrice && $hargaValue <= 0) {
                continue;
            }

            $jenisDetail = isset($jenis[$index]) && strtoupper((string) $jenis[$index]) === 'BAHAN' ? 'BAHAN' : 'JASA';
            $row = array(
                'nama_pekerjaan' => $namaPekerjaan,
                'deskripsi' => isset($deskripsi[$index]) ? trim((string) $deskripsi[$index]) : '',
                'qty' => $qtyValue,
                'satuan' => isset($satuan[$index]) && trim((string) $satuan[$index]) !== '' ? trim((string) $satuan[$index]) : 'Lot',
                'hrg_satuan' => $hargaValue,
                'total_harga' => $subtotal
            );
            if ($supportSplitDetail) {
                $row['jenis_detail'] = $jenisDetail;
            }

            $rows[] = $row;
            $total += $subtotal;
            if ($jenisDetail === 'BAHAN') {
                $totalBahan += $subtotal;
            } else {
                $totalJasa += $subtotal;
            }
        }

        return array(
            'rows' => $rows,
            'total' => $total,
            'total_jasa' => $totalJasa,
            'total_bahan' => $totalBahan
        );
    }

    private function appendRequestSplitTotals(&$data, $details)
    {
        if ($this->db->field_exists('estimasi_total_jasa', 'tbpo_jasa_request')) {
            $data['estimasi_total_jasa'] = isset($details['total_jasa']) ? $details['total_jasa'] : 0;
        }

        if ($this->db->field_exists('estimasi_total_bahan', 'tbpo_jasa_request')) {
            $data['estimasi_total_bahan'] = isset($details['total_bahan']) ? $details['total_bahan'] : 0;
        }
    }

    private function normalizeRequestDate($value, $fallback = '')
    {
        $value = trim((string) $value);
        if ($value !== '') {
            return $value;
        }

        $fallback = trim((string) $fallback);
        if ($fallback !== '') {
            return $fallback;
        }

        return date('Y-m-d');
    }

    private function normalizeTargetDate($value, $requestDate)
    {
        $value = trim((string) $value);

        return $value !== '' ? $value : $requestDate;
    }

    private function normalizeLokasiPekerjaan($value, $fallback = '')
    {
        $value = trim((string) $value);
        if ($value !== '') {
            return $value;
        }

        $fallback = trim((string) $fallback);
        if ($fallback !== '') {
            return $fallback;
        }

        $departemen = trim((string) $this->session->userdata('departemen'));

        return $departemen !== '' ? $departemen : 'UMUM';
    }

    private function approvalTransition($request, $action)
    {
        $lv = (string) $this->session->userdata('lv');
        $kode = $this->session->userdata('kode');
        $departemen = $this->session->userdata('departemen');
        $now = date('Y-m-d H:i:s');

        if ($action === 'approve_kadep' && $lv === '5' && $request->status === 'ON PROGRESS') {
            if ($request->departemen !== $departemen) {
                return array(
                    'success' => false,
                    'message' => 'KADEP hanya dapat approval request dari departemennya.'
                );
            }

            return array(
                'success' => true,
                'update' => array(
                    'status' => 'ACC-KADEP',
                    'acc_with_kadep' => $kode,
                    'acc_at_kadep' => $now
                ),
                'note' => 'PO Jasa ACCEPT KADEP'
            );
        }

        if ($action === 'submit_direktur' && in_array($lv, array('1', '2'), true) && in_array($request->status, array('ACC-KADEP', 'REVIEW PURCHASING'), true)) {
            $update = array(
                'status' => 'REVIEW DIREKTUR'
            );
            $this->appendPurchasingSubmitColumns($update, $kode, $now);

            return array(
                'success' => true,
                'update' => $update,
                'note' => 'Purchasing ajukan PO Jasa ke direktur'
            );
        }

        if ($action === 'approve_direktur' && $lv === '3' && $request->status === 'REVIEW DIREKTUR') {
            return array(
                'success' => true,
                'update' => array(
                    'status' => 'ACC DIREKTUR',
                    'acc_with_direktur' => $kode,
                    'acc_at_direktur' => $now
                ),
                'note' => 'PO Jasa ACCEPT DIREKTUR'
            );
        }

        if ($action === 'reject' && (($lv === '5' && $request->status === 'ON PROGRESS') || ($lv === '3' && $request->status === 'REVIEW DIREKTUR'))) {
            if ($lv === '5' && $request->departemen !== $departemen) {
                return array(
                    'success' => false,
                    'message' => 'KADEP hanya dapat reject request dari departemennya.'
                );
            }

            return array(
                'success' => true,
                'update' => array('status' => 'REJECT'),
                'note' => 'PO Jasa REJECT'
            );
        }

        if ($action === 'pending' && (($lv === '5' && $request->status === 'ON PROGRESS') || ($lv === '3' && $request->status === 'REVIEW DIREKTUR'))) {
            if ($lv === '5' && $request->departemen !== $departemen) {
                return array(
                    'success' => false,
                    'message' => 'KADEP hanya dapat pending request dari departemennya.'
                );
            }

            return array(
                'success' => true,
                'update' => array('status' => 'PENDING'),
                'note' => 'PO Jasa PENDING'
            );
        }

        return array(
            'success' => false,
            'message' => 'Akses, status, atau aksi approval tidak valid.'
        );
    }

    private function noteData($kd_po_jasa, $isi_note, $status)
    {
        return array(
            'kd_po_jasa' => $kd_po_jasa,
            'isi_note' => $isi_note,
            'kd_user' => $this->session->userdata('kode'),
            'nama_user' => $this->session->userdata('nama_user'),
            'aksi_status' => $status
        );
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

    private function validScore($value)
    {
        return $value >= 1 && $value <= 5;
    }

    private function uploadPojasaFile($field, $departemen = '', $tanggal = '')
    {
        if (!isset($_FILES[$field])) {
            return array('success' => false, 'message' => 'File dokumen tidak ditemukan.');
        }

        $fileSize = isset($_FILES[$field]['size']) ? (int) $_FILES[$field]['size'] : 0;
        $originalName = isset($_FILES[$field]['name']) ? (string) $_FILES[$field]['name'] : '';
        $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        if ($this->isImageExtension($ext) && $fileSize > (3 * 1024 * 1024)) {
            return array('success' => false, 'message' => 'Ukuran dokumen gambar tidak boleh lebih dari 3 MB.');
        }

        $relativePath = $this->pojasaArchiveRelativePath($departemen, $tanggal);
        $uploadPath = FCPATH . $relativePath;
        if (!$this->ensurePojasaUploadDirectory($uploadPath)) {
            return array(
                'success' => false,
                'message' => 'Folder upload PO Jasa tidak dapat dibuat/ditulis. Periksa permission folder images/pojasa.'
            );
        }

        $config = array(
            'upload_path' => $uploadPath,
            'allowed_types' => 'jpg|jpeg|png|pdf|doc|docx|xls|xlsx|txt|rtf|csv',
            'max_size' => '0',
            'overwrite' => false,
            'encrypt_name' => true
        );

        $this->load->library('upload', $config);
        $this->upload->initialize($config);

        if (!$this->upload->do_upload($field)) {
            return array(
                'success' => false,
                'message' => strip_tags($this->upload->display_errors('', ''))
            );
        }

        $data = $this->upload->data();

        return array(
            'success' => true,
            'file_name' => $relativePath . $data['file_name'],
            'client_name' => $data['client_name'],
            'file_ext' => $data['file_ext'],
            'file_size' => $data['file_size']
        );
    }

    private function collectPojasaUploads($kd_po_jasa, $field, $jenis_dokumen, $keterangan, $departemen = '', $tanggal = '')
    {
        if (empty($_FILES[$field]['name'])) {
            return array('success' => true, 'rows' => array());
        }

        $rows = array();
        $original = $_FILES[$field];
        $isMultiple = is_array($original['name']);
        $total = $isMultiple ? count($original['name']) : 1;

        for ($i = 0; $i < $total; $i++) {
            $name = $isMultiple ? $original['name'][$i] : $original['name'];
            if ($name === '') {
                continue;
            }

            $_FILES[$field] = array(
                'name' => $name,
                'type' => $isMultiple ? $original['type'][$i] : $original['type'],
                'tmp_name' => $isMultiple ? $original['tmp_name'][$i] : $original['tmp_name'],
                'error' => $isMultiple ? $original['error'][$i] : $original['error'],
                'size' => $isMultiple ? $original['size'][$i] : $original['size']
            );

            $uploadResult = $this->uploadPojasaFile($field, $departemen, $tanggal);
            if (!$uploadResult['success']) {
                $_FILES[$field] = $original;
                return array('success' => false, 'message' => $uploadResult['message'], 'rows' => array());
            }

            $keteranganValue = is_array($keterangan)
                ? (isset($keterangan[$i]) ? trim((string) $keterangan[$i]) : '')
                : trim((string) $keterangan);

            $rows[] = array(
                'kd_po_jasa' => $kd_po_jasa,
                'jenis_dokumen' => $jenis_dokumen,
                'keterangan' => $keteranganValue,
                'file_name' => $uploadResult['file_name'],
                'file_original' => $uploadResult['client_name'],
                'file_ext' => $uploadResult['file_ext'],
                'file_size' => $uploadResult['file_size'],
                'uploaded_by' => $this->session->userdata('kode'),
                'uploaded_name' => $this->session->userdata('nama_user')
            );
        }

        $_FILES[$field] = $original;

        return array('success' => true, 'rows' => $rows);
    }

    private function fileContextFromPost()
    {
        $id_file_jasa = (int) $this->input->post('id_file_jasa', true);
        if ($id_file_jasa <= 0) {
            return array('success' => false, 'message' => 'Data dokumen tidak valid.');
        }

        $file = $this->M_Pojasa->get_file($id_file_jasa);
        if (!$file) {
            return array('success' => false, 'message' => 'Dokumen pendukung tidak ditemukan.');
        }

        $request = $this->M_Pojasa->get_request($file->kd_po_jasa);
        if (!$request || !$this->canUploadProjectDocument($request)) {
            return array('success' => false, 'message' => 'Ubah dokumen pendukung hanya dapat dilakukan PIC terkait atau Admin/Purchasing sebelum diajukan ke direktur dan sebelum project DONE.');
        }

        return array('success' => true, 'file' => $file, 'request' => $request);
    }

    private function serve_file($id_file_jasa, $download)
    {
        if (!$this->M_Pojasa->tables_ready()) {
            show_404();
            return;
        }

        $file = $this->M_Pojasa->get_file((int) $id_file_jasa);
        if (!$file) {
            show_404();
            return;
        }

        $request = $this->M_Pojasa->get_request($file->kd_po_jasa);
        if (!$request || !$this->canReviewProjectDocument($request)) {
            show_404();
            return;
        }

        $path = $this->resolvePojasaFilePath($file->file_name);
        if (!$path || !is_file($path)) {
            show_404();
            return;
        }

        $name = $file->file_original ? $file->file_original : basename($path);
        if ($download) {
            $this->load->helper('download');
            force_download($name, file_get_contents($path));
            return;
        }

        $mime = function_exists('mime_content_type') ? mime_content_type($path) : 'application/octet-stream';
        $this->output
            ->set_content_type($mime)
            ->set_header('Content-Disposition: inline; filename="' . str_replace('"', '', $name) . '"')
            ->set_output(file_get_contents($path));
    }

    private function pojasaArchiveRelativePath($departemen, $tanggal)
    {
        $departemen = $this->sanitizePathSegment($departemen === '' ? 'UMUM' : $departemen);
        $timestamp = strtotime($tanggal);
        if (!$timestamp) {
            $timestamp = time();
        }

        return 'images/pojasa/' . $departemen . '/' . date('Y', $timestamp) . '/' . date('m', $timestamp) . '/' . date('d', $timestamp) . '/';
    }

    private function sanitizePathSegment($value)
    {
        $value = strtoupper(trim((string) $value));
        $value = preg_replace('/[^A-Z0-9_-]+/', '_', $value);

        return trim($value, '_') !== '' ? trim($value, '_') : 'UMUM';
    }

    private function isImageExtension($ext)
    {
        return in_array(strtolower((string) $ext), array('jpg', 'jpeg', 'png'), true);
    }

    private function resolvePojasaFilePath($fileName)
    {
        $fileName = ltrim((string) $fileName, '/');
        $candidates = array(
            FCPATH . $fileName,
            FCPATH . 'images/pojasa/' . $fileName
        );

        foreach ($candidates as $candidate) {
            $real = realpath($candidate);
            $base = realpath(FCPATH . 'images/pojasa');
            if ($real && $base && strpos($real, $base) === 0) {
                return $real;
            }
        }

        return false;
    }

    private function ensurePojasaUploadDirectory($uploadPath)
    {
        if (!is_dir($uploadPath) && !@mkdir($uploadPath, 0777, true) && !is_dir($uploadPath)) {
            return false;
        }

        @chmod($uploadPath, 0777);

        return is_dir($uploadPath) && is_writable($uploadPath);
    }

    private function deletePhysicalPojasaFile($fileName)
    {
        $path = $this->resolvePojasaFilePath($fileName);
        if ($path && is_file($path)) {
            @unlink($path);
        }
    }

    private function appendVendorAuditColumns(&$data, $statusVendor)
    {
        if ($this->db->field_exists('created_name', 'tbpo_jasa_vendor')) {
            $data['created_name'] = $this->session->userdata('nama_user');
        }

        if ($this->db->field_exists('requested_by', 'tbpo_jasa_vendor')) {
            $data['requested_by'] = $this->session->userdata('kode');
        }

        if ($this->db->field_exists('requested_name', 'tbpo_jasa_vendor')) {
            $data['requested_name'] = $this->session->userdata('nama_user');
        }

        if ($this->db->field_exists('requested_departemen', 'tbpo_jasa_vendor')) {
            $data['requested_departemen'] = $this->session->userdata('departemen');
        }

        if ($statusVendor === 'AKTIF') {
            $this->appendVendorApprovalColumns($data);
        }
    }

    private function appendVendorApprovalColumns(&$data)
    {
        if ($this->db->field_exists('approved_by', 'tbpo_jasa_vendor')) {
            $data['approved_by'] = $this->session->userdata('kode');
        }

        if ($this->db->field_exists('approved_name', 'tbpo_jasa_vendor')) {
            $data['approved_name'] = $this->session->userdata('nama_user');
        }

        if ($this->db->field_exists('approved_at', 'tbpo_jasa_vendor')) {
            $data['approved_at'] = date('Y-m-d H:i:s');
        }
    }

    private function appendPurchasingReviewColumns(&$data)
    {
        if ($this->db->field_exists('reviewed_by_purchasing', 'tbpo_jasa_request')) {
            $data['reviewed_by_purchasing'] = $this->session->userdata('kode');
        }

        if ($this->db->field_exists('reviewed_at_purchasing', 'tbpo_jasa_request')) {
            $data['reviewed_at_purchasing'] = date('Y-m-d H:i:s');
        }
    }

    private function appendPurchasingSubmitColumns(&$data, $kode, $now)
    {
        if ($this->db->field_exists('submitted_by_purchasing', 'tbpo_jasa_request')) {
            $data['submitted_by_purchasing'] = $kode;
        }

        if ($this->db->field_exists('submitted_at_purchasing', 'tbpo_jasa_request')) {
            $data['submitted_at_purchasing'] = $now;
        }
    }

    private function jsonResponse($success, $message, $extra = array())
    {
        $payload = array_merge(array(
            'success' => (bool) $success,
            'status' => (bool) $success,
            'message' => $message
        ), $extra);

        return $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode($payload));
    }
}
