<?php
defined('BASEPATH') or exit('No direct script access allowed');

class C_PojasaPic extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('PO/M_PojasaPic');
        $this->load->model('PO/M_PojasaExecution');
        $this->load->model('PO/M_PojasaIntegration');
        $this->load->model('PO/M_PojasaPickup');
        $this->load->helper(array('pojasa_authorization', 'pojasa_status', 'pojasa_document'));
    }

    public function index()
    {
        if (!$this->guardPage()) {
            return;
        }
        $data = $this->baseData('Request PO Jasa PIC');
        $data['statuses'] = pojasa_statuses();
        $data['status_labels'] = $this->statusLabels();
        $this->render('content/po/jasa/pic/list', $data, 'content/po/jasa/pic/list_js');
    }

    public function create()
    {
        if (!$this->guardPage()) {
            return;
        }
        $data = $this->baseData('Buat Draft PO Jasa');
        $data['request'] = null;
        $data['scopes'] = array();
        $data['materials'] = array();
        $data['documents'] = array();
        $data['vendors'] = $this->M_PojasaPic->get_active_vendors();
        $data['can_edit'] = true;
        $this->render('content/po/jasa/pic/form', $data, 'content/po/jasa/pic/form_js');
    }

    public function edit($requestCode)
    {
        if (!$this->guardPage()) {
            return;
        }
        $context = $this->context();
        $request = $this->M_PojasaPic->get_request($this->normalizeRequestCode($requestCode), $context);
        if (!$request) {
            show_404();
            return;
        }
        if (!$this->M_PojasaPic->can_edit($request, $context)) {
            $this->session->set_flashdata('error', 'Request tidak dapat diedit pada status ' . $request->status . '.');
            redirect('pojasa/pic/detail/' . $request->kd_po_jasa);
            return;
        }
        $revisionNo = $this->M_PojasaPic->effective_revision($request);
        $data = $this->baseData('Edit Draft PO Jasa');
        $data['request'] = $request;
        $data['scopes'] = $this->M_PojasaPic->get_scopes($request->kd_po_jasa, $revisionNo);
        $data['materials'] = $this->M_PojasaPic->get_materials($request->kd_po_jasa, $revisionNo);
        $data['documents'] = $this->M_PojasaPic->get_documents($request->kd_po_jasa);
        $data['vendors'] = $this->M_PojasaPic->get_active_vendors();
        $data['can_edit'] = true;
        $this->render('content/po/jasa/pic/form', $data, 'content/po/jasa/pic/form_js');
    }

    public function detail($requestCode)
    {
        if (!$this->guardPage()) {
            return;
        }
        $context = $this->context();
        $request = $this->M_PojasaPic->get_request($this->normalizeRequestCode($requestCode), $context);
        if (!$request) {
            show_404();
            return;
        }
        $revisionNo = $this->M_PojasaPic->effective_revision($request);
        $data = $this->baseData('Detail Request PO Jasa');
        $data['request'] = $request;
        $data['scopes'] = $this->M_PojasaPic->get_scopes($request->kd_po_jasa, $revisionNo);
        $data['materials'] = $this->M_PojasaPic->get_materials($request->kd_po_jasa, $revisionNo);
        $data['documents'] = $this->M_PojasaPic->get_documents($request->kd_po_jasa);
        $data['history'] = $this->M_PojasaPic->get_detail_history($request->kd_po_jasa);
        $data['can_edit'] = $this->M_PojasaPic->can_edit($request, $context);
        $data['execution'] = $this->M_PojasaExecution->schema_ready()
            ? $this->M_PojasaExecution->get_state($context, $request->kd_po_jasa) : false;
        $data['purchase_integration'] = $this->M_PojasaIntegration->get_submission_state($request->kd_po_jasa);
        $data['pickup_state'] = $this->M_PojasaPickup->state($request->kd_po_jasa);
        $this->render('content/po/jasa/pic/detail', $data, 'content/po/jasa/pic/detail_js');
    }

    public function request_pickup()
    {
        if (!$this->guardAjax('POST', true)) return;
        $code = $this->normalizeRequestCode($this->input->post('kd_po_jasa', true));
        $token = strtolower(trim((string) $this->input->post('idempotency_token', true)));
        if (!$this->validUuid($token)) return $this->json(false, 'VALIDATION_ERROR', 'Token aksi tidak valid.', array(), 422);
        $result = $this->M_PojasaPickup->request($this->context(), $code, $token);
        return $this->json($result['success'], $result['code'], $result['success'] ? 'Permintaan pengambilan dikirim ke Kadep.' : 'Seluruh material harus tersedia dan memiliki master barang sebelum pengambilan diajukan.', array('pickup' => $this->M_PojasaPickup->state($code)), $result['success'] ? 200 : 409);
    }

    public function execution_state($requestCode)
    {
        if (!$this->guardAjax('GET')) {
            return;
        }
        if (!$this->M_PojasaExecution->schema_ready()) {
            return $this->json(false, 'SCHEMA_NOT_READY', 'Schema eksekusi PO Jasa Fase 6 belum tersedia.', array(), 503);
        }
        $state = $this->M_PojasaExecution->get_state($this->context(), $this->normalizeRequestCode($requestCode));
        if (!$state) {
            return $this->json(false, 'NOT_FOUND', 'Request tidak ditemukan atau bukan milik PIC aktif.', array(), 404);
        }
        return $this->json(true, 'OK', 'Data eksekusi berhasil dimuat.', array(
            'state' => $this->executionPayload($state), 'csrf_token' => pojasa_csrf_token(),
        ));
    }

    public function save_progress()
    {
        if (!$this->guardAjax('POST', true)) {
            return;
        }
        if (!$this->M_PojasaExecution->schema_ready()) {
            return $this->json(false, 'SCHEMA_NOT_READY', 'Schema eksekusi PO Jasa Fase 6 belum tersedia.', array(), 503);
        }
        $requestCode = $this->normalizeRequestCode($this->input->post('kd_po_jasa', true));
        $date = trim((string) $this->input->post('tgl_progress', true));
        $percent = $this->parseNumber($this->input->post('progress_persen', true));
        $workStatus = strtoupper(trim((string) $this->input->post('status_progress', true)));
        $activity = trim((string) $this->input->post('aktivitas', true));
        $obstacle = trim((string) $this->input->post('kendala', true));
        $followUp = trim((string) $this->input->post('tindak_lanjut', true));
        $note = trim((string) $this->input->post('catatan', true));
        $token = strtolower(trim((string) $this->input->post('idempotency_token', true)));
        $errors = array();
        if (!$this->validRequestCode($requestCode)) $errors['kd_po_jasa'] = 'Kode request tidak valid.';
        if (!$this->validDate($date)) $errors['tgl_progress'] = 'Tanggal progress wajib berformat YYYY-MM-DD.';
        if ($percent === false || $percent < 0 || $percent > 100) $errors['progress_persen'] = 'Persentase wajib antara 0 sampai 100.';
        if (!in_array($workStatus, array('BELUM_DIMULAI', 'ON_PROGRESS', 'SELESAI', 'DITUTUP'), true)) $errors['status_progress'] = 'Status pekerjaan tidak valid.';
        if ($activity === '' || mb_strlen($activity) > 5000) $errors['aktivitas'] = 'Aktivitas wajib diisi, maksimal 5.000 karakter.';
        if (mb_strlen($obstacle) > 5000) $errors['kendala'] = 'Kendala maksimal 5.000 karakter.';
        if (mb_strlen($followUp) > 5000) $errors['tindak_lanjut'] = 'Tindak lanjut maksimal 5.000 karakter.';
        if (mb_strlen($note) > 5000) $errors['catatan'] = 'Catatan maksimal 5.000 karakter.';
        if (!preg_match('/^[a-f0-9]{8}-[a-f0-9]{4}-[1-5][a-f0-9]{3}-[89ab][a-f0-9]{3}-[a-f0-9]{12}$/i', $token)) $errors['idempotency_token'] = 'Idempotency token wajib berupa UUID.';
        $files = $this->normalizeFiles('evidence');
        if ($files && ($validation = $this->validateFiles($files)) !== true) $errors['evidence'] = $validation;
        if ($errors) {
            return $this->json(false, 'VALIDATION_ERROR', 'Periksa kembali input progress.', array(), 422, $errors);
        }
        $request = $this->M_PojasaPic->get_request($requestCode, $this->context());
        if (!$request) {
            return $this->json(false, 'NOT_FOUND', 'Request tidak ditemukan.', array(), 404);
        }
        $upload = $files ? $this->storeFiles(array($files[0]), $request) : array('success' => true, 'documents' => array(), 'paths' => array());
        if (!$upload['success']) {
            return $this->json(false, 'UPLOAD_ERROR', $upload['message'], array(), 422, array('evidence' => $upload['message']));
        }
        $document = $upload['documents'] ? $upload['documents'][0] : null;
        if ($document) {
            $document['jenis_dokumen'] = 'EVIDENCE_PROGRESS';
            $document['keterangan'] = 'Evidence progress: ' . $activity;
        }
        $result = $this->M_PojasaExecution->save_progress($this->context(), $requestCode, array(
            'tgl_progress' => $date, 'progress_persen' => $percent, 'status_progress' => $workStatus,
            'aktivitas' => $activity, 'kendala' => $obstacle, 'tindak_lanjut' => $followUp,
            'catatan' => $note, 'idempotency_token' => $token,
        ), $document);
        if (!$result['success'] || $result['code'] === 'IDEMPOTENT_REPLAY') {
            $this->removeUploadedFiles($upload['paths']);
        }
        if (!$result['success']) {
            return $this->modelError($result['code']);
        }
        return $this->json(true, $result['code'], 'Progress pekerjaan berhasil disimpan.', array(
            'id_progress' => $result['id'], 'status' => $result['status'], 'csrf_token' => pojasa_csrf_token(),
        ));
    }

    public function datatable()
    {
        if (!$this->guardAjax('GET')) {
            return;
        }
        $dateFrom = trim((string) $this->input->get('date_from', true));
        $dateTo = trim((string) $this->input->get('date_to', true));
        if ($dateFrom !== '' && !$this->validDate($dateFrom)) {
            $dateFrom = '';
        }
        if ($dateTo !== '' && !$this->validDate($dateTo)) {
            $dateTo = '';
        }
        $order = $this->input->get('order');
        $search = $this->input->get('search');
        $params = array(
            'status' => trim((string) $this->input->get('status', true)),
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
            'search' => is_array($search) && isset($search['value']) ? trim((string) $search['value']) : '',
            'order_column' => is_array($order) && isset($order[0]['column']) ? (int) $order[0]['column'] : 0,
            'order_direction' => is_array($order) && isset($order[0]['dir']) ? $order[0]['dir'] : 'desc',
            'start' => max(0, (int) $this->input->get('start', true)),
            'length' => max(1, min(100, (int) $this->input->get('length', true) ?: 10)),
        );
        $result = $this->M_PojasaPic->datatable_requests($this->context(), $params);
        $rows = array();
        foreach ($result['rows'] as $row) {
            $rows[] = $this->datatableRow($row);
        }
        return $this->output->set_content_type('application/json')->set_output(json_encode(array(
            'success' => true,
            'draw' => (int) $this->input->get('draw', true),
            'recordsTotal' => $result['total'],
            'recordsFiltered' => $result['filtered'],
            'data' => $rows,
            'csrf_token' => pojasa_csrf_token(),
        )));
    }

    public function material_catalog()
    {
        if (!$this->guardAjax('GET')) return;
        $result = $this->M_PojasaPic->search_material_catalog(
            $this->input->get('term', true), (int) $this->input->get('page', true)
        );
        $items = array();
        foreach ($result['items'] as $row) {
            $items[] = array(
                'id' => (int) $row['id_brg_nk'],
                'text' => $row['kd_barang'] . ' — ' . $row['nama_barang'],
                'kd_barang' => $row['kd_barang'],
                'nama_barang' => $row['nama_barang'],
                'deskripsi' => $row['descnk'],
                'satuan' => $row['satuan'],
                'qty_ready' => (float) $row['qty_ready'],
            );
        }
        return $this->json(true, 'OK', 'Katalog material dimuat.', array('results' => $items, 'pagination' => array('more' => $result['more'])));
    }

    public function save_draft()
    {
        if (!$this->guardAjax('POST', true)) {
            return;
        }
        $payload = $this->decodePayload();
        if ($payload === false) {
            return $this->json(false, 'VALIDATION_ERROR', 'Payload draft tidak valid.', array(), 422, array('payload' => 'Format JSON tidak valid.'));
        }
        $validated = $this->validateDraftPayload($payload);
        if (!$validated['success']) {
            return $this->json(false, 'VALIDATION_ERROR', 'Periksa kembali data draft.', array(), 422, $validated['errors']);
        }
        $result = $this->M_PojasaPic->save_draft(
            $this->context(),
            isset($payload['kd_po_jasa']) ? $this->normalizeRequestCode($payload['kd_po_jasa']) : '',
            $validated['header'],
            $validated['scopes'],
            $validated['materials']
        );
        if (!$result['success']) {
            return $this->modelError($result['code']);
        }
        return $this->json(true, $result['code'], 'Draft request jasa berhasil disimpan.', array(
            'request_code' => $result['request_code'],
            'status' => $result['status'],
            'revision_no' => $result['revision_no'],
            'totals' => $result['totals'],
            'edit_url' => base_url('pojasa/pic/edit/' . $result['request_code']),
            'detail_url' => base_url('pojasa/pic/detail/' . $result['request_code']),
            'csrf_token' => pojasa_csrf_token(),
        ));
    }

    public function submit()
    {
        if (!$this->guardAjax('POST', true)) {
            return;
        }
        $requestCode = $this->normalizeRequestCode($this->input->post('kd_po_jasa', true));
        if (!$this->validRequestCode($requestCode)) {
            return $this->json(false, 'VALIDATION_ERROR', 'Kode request tidak valid.', array(), 422, array('kd_po_jasa' => 'Kode request wajib diisi.'));
        }
        $allowWithoutDocuments = (string) $this->input->post('allow_without_documents', true) === '1';
        $result = $this->M_PojasaPic->submit_request($this->context(), $requestCode, $allowWithoutDocuments);
        if (!$result['success']) {
            if ($result['code'] === 'SUBMIT_VALIDATION') {
                return $this->json(false, $result['code'], 'Request belum memenuhi syarat pengajuan.', array(), 422, $result['errors']);
            }
            return $this->modelError($result['code']);
        }
        $message = 'Request berhasil diajukan dan menunggu pemeriksaan awal Purchasing.';
        return $this->json(true, 'SUBMITTED', $message, array(
            'request_code' => $requestCode,
            'status' => $result['status'],
            'detail_url' => base_url('pojasa/pic/detail/' . $requestCode),
            'csrf_token' => pojasa_csrf_token(),
        ));
    }

    public function delete_draft()
    {
        if (!$this->guardAjax('POST', true)) {
            return;
        }
        $requestCode = $this->normalizeRequestCode($this->input->post('kd_po_jasa', true));
        if (!$this->validRequestCode($requestCode)) {
            return $this->json(false, 'VALIDATION_ERROR', 'Kode draft tidak valid.', array(), 422);
        }
        $result = $this->M_PojasaPic->soft_delete_draft($this->context(), $requestCode);
        if (!$result['success']) {
            return $this->modelError($result['code']);
        }
        return $this->json(true, 'DELETED', 'Draft berhasil dihapus.', array('csrf_token' => pojasa_csrf_token()));
    }

    public function documents($requestCode)
    {
        if (!$this->guardAjax('GET')) {
            return;
        }
        $context = $this->context();
        $request = $this->M_PojasaPic->get_request($this->normalizeRequestCode($requestCode), $context);
        if (!$request) {
            return $this->json(false, 'NOT_FOUND', 'Request tidak ditemukan.', array(), 404);
        }
        return $this->json(true, 'OK', 'Dokumen berhasil dimuat.', array(
            'items' => $this->documentPayload($this->M_PojasaPic->get_documents($request->kd_po_jasa)),
            'can_edit' => $this->M_PojasaPic->can_edit($request, $context),
            'csrf_token' => pojasa_csrf_token(),
        ));
    }

    public function upload_documents()
    {
        if (!$this->guardAjax('POST', true)) {
            return;
        }
        $requestCode = $this->normalizeRequestCode($this->input->post('kd_po_jasa', true));
        $context = $this->context();
        $request = $this->M_PojasaPic->get_request($requestCode, $context);
        if (!$request || !$this->M_PojasaPic->can_edit($request, $context)) {
            return $this->json(false, 'EDIT_FORBIDDEN', 'Dokumen hanya dapat diunggah pada draft milik PIC atau saat REVISI_PIC.', array(), 403);
        }
        $files = $this->normalizeFiles('documents');
        if (!$files) {
            return $this->json(false, 'VALIDATION_ERROR', 'Pilih minimal satu dokumen.', array(), 422, array('documents' => 'Dokumen belum dipilih.'));
        }
        $precheck = $this->validateFiles($files);
        if ($precheck !== true) {
            return $this->json(false, 'FILE_VALIDATION_ERROR', 'Validasi dokumen gagal.', array(), 422, array('documents' => $precheck));
        }
        $upload = $this->storeFiles($files, $request);
        if (!$upload['success']) {
            return $this->json(false, 'UPLOAD_ERROR', $upload['message'], array(), 422, array('documents' => $upload['message']));
        }
        $result = $this->M_PojasaPic->add_documents($context, $requestCode, $upload['documents']);
        if (!$result['success']) {
            $this->removeUploadedFiles($upload['paths']);
            return $this->modelError($result['code']);
        }
        return $this->json(true, 'UPLOADED', count($upload['documents']) . ' dokumen berhasil diunggah.', array(
            'items' => $this->documentPayload($this->M_PojasaPic->get_documents($requestCode)),
            'csrf_token' => pojasa_csrf_token(),
        ));
    }

    public function delete_document()
    {
        if (!$this->guardAjax('POST', true)) {
            return;
        }
        $documentId = (int) $this->input->post('id_dokumen', true);
        if ($documentId <= 0) {
            return $this->json(false, 'VALIDATION_ERROR', 'Dokumen tidak valid.', array(), 422);
        }
        $result = $this->M_PojasaPic->archive_document($this->context(), $documentId);
        if (!$result['success']) {
            return $this->modelError($result['code']);
        }
        return $this->json(true, 'ARCHIVED', 'Dokumen berhasil dihapus dari draft.', array(
            'items' => $this->documentPayload($this->M_PojasaPic->get_documents($result['request_code'])),
            'csrf_token' => pojasa_csrf_token(),
        ));
    }

    public function view_document($documentId)
    {
        if (!$this->guardRole()) {
            return;
        }
        $document = $this->M_PojasaPic->get_document($this->context(), (int) $documentId);
        if (!$document) {
            show_404();
            return;
        }
        $base = realpath(FCPATH . 'images/pojasa');
        $path = realpath(FCPATH . ltrim($document->file_path, '/'));
        if (!$base || !$path || strpos($path, $base . DIRECTORY_SEPARATOR) !== 0 || !is_file($path)) {
            show_404();
            return;
        }
        $inline = strpos($document->mime_type, 'image/') === 0 || in_array($document->mime_type, array('application/pdf', 'text/plain', 'text/csv'), true);
        $disposition = $inline ? 'inline' : 'attachment';
        $name = str_replace(array('"', "\r", "\n"), '', $document->file_original);
        $this->output
            ->set_content_type($document->mime_type ?: 'application/octet-stream')
            ->set_header('X-Content-Type-Options: nosniff')
            ->set_header('Content-Disposition: ' . $disposition . '; filename="' . $name . '"')
            ->set_output(file_get_contents($path));
    }

    private function render($view, $data, $scriptView = null)
    {
        $this->load->view('partial/header', $data);
        $this->load->view('partial/sidebar', $data);
        $this->load->view($view, $data);
        $this->load->view('partial/footer', $data);
        if ($scriptView) {
            $this->load->view($scriptView, $data);
        }
    }

    private function guardPage()
    {
        if (!$this->guardRole()) {
            show_error('Akses Modul PO Jasa PIC ditolak.', 403);
            return false;
        }
        if (!$this->M_PojasaPic->schema_ready()) {
            show_error('Schema PO Jasa Fase 3 belum tersedia.', 503);
            return false;
        }
        return true;
    }

    private function guardRole()
    {
        $context = $this->context();
        return $this->session->userdata('status') === 'is_login'
            && (int) $context['id_user'] > 0
            && in_array($context['role'], array('PIC', 'ADMIN'), true);
    }

    private function guardAjax($method, $csrf = false)
    {
        if (!$this->input->is_ajax_request() && strpos((string) $this->input->get_request_header('Accept', true), 'application/json') === false) {
            $this->json(false, 'AJAX_REQUIRED', 'Endpoint hanya menerima request AJAX.', array(), 400);
            return false;
        }
        if (!$this->guardRole()) {
            $this->json(false, 'FORBIDDEN', 'Akses PO Jasa PIC ditolak.', array(), 403);
            return false;
        }
        if (strtoupper($this->input->method(true)) !== strtoupper($method)) {
            $this->json(false, 'METHOD_NOT_ALLOWED', 'Metode request tidak diperbolehkan.', array(), 405);
            return false;
        }
        if (!$this->M_PojasaPic->schema_ready()) {
            $this->json(false, 'SCHEMA_NOT_READY', 'Schema PO Jasa Fase 3 belum tersedia.', array(), 503);
            return false;
        }
        if ($csrf) {
            $token = (string) $this->input->get_request_header('X-POJASA-CSRF', true);
            if ($token === '') {
                $token = (string) $this->input->post('csrf_token', true);
            }
            if (!pojasa_csrf_is_valid($token)) {
                $this->json(false, 'CSRF_INVALID', 'Token keamanan tidak valid atau kedaluwarsa.', array(), 403);
                return false;
            }
        }
        return true;
    }

    private function context()
    {
        $context = pojasa_session_context();
        $context['ip_address'] = $this->input->ip_address();
        $context['user_agent'] = substr((string) $this->input->user_agent(), 0, 255);
        return $context;
    }

    private function baseData($title)
    {
        return array(
            'title' => $title,
            'iduser' => $this->session->userdata('id'),
            'kduser' => $this->session->userdata('kode'),
            'nmuser' => $this->session->userdata('nama_user'),
            'depuser' => pojasa_normalize_department($this->session->userdata('departemen')),
            'lv' => $this->session->userdata('lv'),
            'csrf_token' => pojasa_csrf_token(),
        );
    }

    private function decodePayload()
    {
        $raw = $this->input->post('payload');
        if (is_array($raw)) {
            return $raw;
        }
        if (!is_string($raw) || trim($raw) === '') {
            $raw = $this->input->raw_input_stream;
        }
        $payload = json_decode((string) $raw, true);
        return is_array($payload) ? $payload : false;
    }

    private function validateDraftPayload($payload)
    {
        $errors = array();
        $vendorCode = strtoupper(trim((string) (isset($payload['kd_vendor_jasa']) ? $payload['kd_vendor_jasa'] : '')));
        $vendorProposal = trim((string) (isset($payload['vendor_usulan']) ? $payload['vendor_usulan'] : ''));
        $plannedStartDate = trim((string) (isset($payload['tgl_mulai_pekerjaan']) ? $payload['tgl_mulai_pekerjaan'] : ''));
        $plannedCompletionDate = trim((string) (isset($payload['tgl_selesai_pekerjaan']) ? $payload['tgl_selesai_pekerjaan'] : ''));
        $purpose = trim((string) (isset($payload['tujuan_pekerjaan']) ? $payload['tujuan_pekerjaan'] : ''));
        $notes = trim((string) (isset($payload['catatan_pic']) ? $payload['catatan_pic'] : ''));
        if ($vendorCode !== '' && $vendorProposal !== '') {
            $errors['vendor'] = 'Pilih satu sumber vendor: vendor terdaftar atau nama vendor/toko manual.';
        } elseif ($vendorCode !== '' && !$this->M_PojasaPic->active_vendor_exists($vendorCode)) {
            $errors['kd_vendor_jasa'] = 'Vendor yang dipilih tidak tersedia atau tidak aktif.';
        }
        if (mb_strlen($vendorProposal) > 180) {
            $errors['vendor_usulan'] = 'Nama vendor/toko manual maksimal 180 karakter.';
        }
        if (mb_strlen($purpose) > 10000) {
            $errors['tujuan_pekerjaan'] = 'Tujuan pekerjaan maksimal 10.000 karakter.';
        }
        if (mb_strlen($notes) > 10000) {
            $errors['catatan_pic'] = 'Catatan maksimal 10.000 karakter.';
        }
        if (!$this->validDate($plannedStartDate)) {
            $errors['tgl_mulai_pekerjaan'] = 'Tanggal rencana start pekerjaan wajib berformat YYYY-MM-DD.';
        }
        if (!$this->validDate($plannedCompletionDate)) {
            $errors['tgl_selesai_pekerjaan'] = 'Target penyelesaian wajib berformat YYYY-MM-DD.';
        } elseif ($this->validDate($plannedStartDate) && $plannedCompletionDate < $plannedStartDate) {
            $errors['tgl_selesai_pekerjaan'] = 'Target penyelesaian tidak boleh lebih awal dari tanggal rencana start pekerjaan.';
        }

        $scopes = $this->validateLines(isset($payload['scopes']) ? $payload['scopes'] : array(), 'scope', $errors);
        $materials = $this->validateLines(isset($payload['materials']) ? $payload['materials'] : array(), 'material', $errors);
        if ($errors) {
            return array('success' => false, 'errors' => $errors);
        }
        $today = date('Y-m-d');
        return array(
            'success' => true,
            'header' => array(
                'tgl_request' => $today,
                'tgl_target' => $plannedCompletionDate,
                'tgl_mulai_pekerjaan' => $plannedStartDate,
                'tgl_selesai_pekerjaan' => $plannedCompletionDate,
                'kd_vendor_jasa' => $vendorCode !== '' ? $vendorCode : null,
                'vendor_usulan' => $vendorCode === '' && $vendorProposal !== '' ? $vendorProposal : null,
                'tujuan_pekerjaan' => $purpose,
                'catatan_pic' => $notes,
            ),
            'scopes' => $scopes,
            'materials' => $materials,
        );
    }

    private function validateLines($rows, $type, &$errors)
    {
        if (!is_array($rows)) {
            $errors[$type] = 'Format baris tidak valid.';
            return array();
        }
        $clean = array();
        foreach ($rows as $index => $row) {
            if (!is_array($row)) {
                $errors[$type . '_' . $index] = 'Format baris tidak valid.';
                continue;
            }
            $nameKey = $type === 'scope' ? 'nama_scope' : 'nama_material';
            $qtyKey = $type === 'scope' ? 'qty' : 'qty_kebutuhan';
            $name = trim((string) (isset($row[$nameKey]) ? $row[$nameKey] : ''));
            $description = trim((string) (isset($row['deskripsi']) ? $row['deskripsi'] : ''));
            $unit = trim((string) (isset($row['satuan']) ? $row['satuan'] : ''));
            $qty = $this->parseNumber(isset($row[$qtyKey]) ? $row[$qtyKey] : '');
            $price = $this->parseNumber(isset($row['harga_estimasi']) ? $row['harga_estimasi'] : 0);
            $isEmpty = $name === '' && $description === '' && ($qty === false || $qty == 0) && ($price === false || $price == 0);
            if ($isEmpty) {
                continue;
            }
            if ($name === '' || mb_strlen($name) > 180) {
                $errors[$type . '_' . $index . '_name'] = 'Nama wajib diisi, maksimal 180 karakter.';
            }
            if (mb_strlen($description) > 5000) {
                $errors[$type . '_' . $index . '_description'] = 'Deskripsi maksimal 5.000 karakter.';
            }
            if ($qty === false || $qty <= 0 || $qty >= 10000000000000000) {
                $errors[$type . '_' . $index . '_qty'] = 'Qty harus lebih dari 0.';
            }
            if ($price === false || $price < 0 || $price >= 10000000000000000) {
                $errors[$type . '_' . $index . '_price'] = 'Estimasi biaya tidak valid.';
            }
            if ($unit === '' || mb_strlen($unit) > 40) {
                $errors[$type . '_' . $index . '_unit'] = 'Satuan wajib diisi, maksimal 40 karakter.';
            }
            if ($errors) {
                continue;
            }
            if ($type === 'scope') {
                $clean[] = array('nama_scope' => $name, 'deskripsi' => $description, 'qty' => $qty, 'satuan' => $unit, 'harga_estimasi' => $price);
            } else {
                $catalogId = (int) (isset($row['id_brg_nk']) ? $row['id_brg_nk'] : 0);
                $referenceType = strtoupper(trim((string) (isset($row['reference_type']) ? $row['reference_type'] : ($catalogId ? 'MASTER' : 'MANUAL'))));
                if (!in_array($referenceType, array('MASTER', 'MANUAL'), true)) {
                    $errors[$type . '_' . $index . '_reference'] = 'Referensi barang tidak valid.';
                    continue;
                }
                $master = null;
                if ($referenceType === 'MASTER') {
                    $master = $this->M_PojasaPic->get_catalog_material($catalogId);
                    if (!$master) {
                        $errors[$type . '_' . $index . '_reference'] = 'Barang master tidak ditemukan atau tidak aktif.';
                        continue;
                    }
                    // Do not trust browser-sent master text: Purchasing-owned master data wins.
                    $name = trim((string) $master['nama_barang']);
                    $description = trim((string) $master['descnk']);
                    if ((string) $master['satuan'] !== '') $unit = (string) $master['satuan'];
                }
                $clean[] = array(
                    'id_brg_nk' => $master ? (int) $master['id_brg_nk'] : null,
                    'id_usulan_barang' => (int) (isset($row['id_usulan_barang']) ? $row['id_usulan_barang'] : 0),
                    'reference_type' => $referenceType,
                    'kd_barang_snapshot' => $master ? $master['kd_barang'] : null,
                    'nama_material' => $name, 'deskripsi' => $description, 'qty_kebutuhan' => $qty,
                    'satuan' => $unit, 'harga_estimasi' => $price, 'sumber_material' => 'BELUM_DITENTUKAN'
                );
            }
        }
        return $clean;
    }

    private function normalizeFiles($field)
    {
        if (!isset($_FILES[$field])) {
            return array();
        }
        $source = $_FILES[$field];
        if (!is_array($source['name'])) {
            return array($source);
        }
        $files = array();
        foreach ($source['name'] as $index => $name) {
            if ((string) $name === '') {
                continue;
            }
            $files[] = array(
                'name' => $name,
                'type' => $source['type'][$index],
                'tmp_name' => $source['tmp_name'][$index],
                'error' => $source['error'][$index],
                'size' => $source['size'][$index],
            );
        }
        return $files;
    }

    private function validateFiles($files)
    {
        foreach ($files as $file) {
            $validation = pojasa_validate_document_file($file);
            if (!$validation['success']) {
                return $validation['message'];
            }
        }
        return true;
    }

    private function storeFiles($files, $request)
    {
        $department = preg_replace('/[^A-Z0-9_-]+/', '_', pojasa_normalize_department($request->departemen));
        $department = trim($department, '_') ?: 'UMUM';
        $relativeDirectory = 'images/pojasa/' . $department . '/' . date('Y/m/d') . '/';
        $absoluteDirectory = FCPATH . $relativeDirectory;
        if (!is_dir($absoluteDirectory) && !@mkdir($absoluteDirectory, 0777, true) && !is_dir($absoluteDirectory)) {
            return array('success' => false, 'message' => 'Folder penyimpanan tidak dapat dibuat.');
        }
        $config = array(
            'upload_path' => $absoluteDirectory,
            'allowed_types' => 'txt|rtf|csv|pdf|doc|docx|xls|xlsx|jpg|jpeg|png',
            'max_size' => 10240,
            'overwrite' => false,
            'encrypt_name' => true,
            'detect_mime' => true,
        );
        $this->load->library('upload', $config);
        $documents = array();
        $paths = array();
        foreach ($files as $file) {
            $_FILES['pojasa_document'] = $file;
            $this->upload->initialize($config);
            if (!$this->upload->do_upload('pojasa_document')) {
                $this->removeUploadedFiles($paths);
                unset($_FILES['pojasa_document']);
                return array('success' => false, 'message' => $file['name'] . ': ' . strip_tags($this->upload->display_errors('', '')));
            }
            $uploaded = $this->upload->data();
            $relativePath = $relativeDirectory . $uploaded['file_name'];
            $absolutePath = $absoluteDirectory . $uploaded['file_name'];
            $paths[] = $relativePath;
            $documents[] = array(
                'jenis_dokumen' => 'DOKUMEN_REQUEST',
                'keterangan' => trim((string) $this->input->post('keterangan', true)),
                'file_path' => $relativePath,
                'file_original' => substr($uploaded['client_name'], 0, 180),
                'mime_type' => (new finfo(FILEINFO_MIME_TYPE))->file($absolutePath),
                'file_size_bytes' => (int) filesize($absolutePath),
                'sha256' => hash_file('sha256', $absolutePath),
            );
        }
        unset($_FILES['pojasa_document']);
        return array('success' => true, 'documents' => $documents, 'paths' => $paths);
    }

    private function removeUploadedFiles($paths)
    {
        $base = realpath(FCPATH . 'images/pojasa');
        foreach ($paths as $relativePath) {
            $path = realpath(FCPATH . ltrim($relativePath, '/'));
            if ($base && $path && strpos($path, $base . DIRECTORY_SEPARATOR) === 0 && is_file($path)) {
                @unlink($path);
            }
        }
    }

    private function documentPayload($documents)
    {
        $items = array();
        foreach ($documents as $document) {
            $items[] = array(
                'id_dokumen' => (int) $document['id_dokumen'],
                'name' => $document['file_original'],
                'mime_type' => $document['mime_type'],
                'size_bytes' => (int) $document['file_size_bytes'],
                'size_label' => $this->formatBytes((int) $document['file_size_bytes']),
                'revision_no' => (int) $document['revision_no'],
                'created_at' => $document['created_at'],
                'view_url' => base_url('pojasa/pic/document/' . $document['id_dokumen']),
                'is_image' => strpos((string) $document['mime_type'], 'image/') === 0,
            );
        }
        return $items;
    }

    private function executionPayload($state)
    {
        foreach ($state['progress'] as &$row) {
            $row['evidence_url'] = !empty($row['id_dokumen_evidence']) ? base_url('pojasa/pic/document/' . (int) $row['id_dokumen_evidence']) : null;
        }
        unset($row);
        foreach ($state['costs'] as &$row) {
            $row['evidence_url'] = !empty($row['id_dokumen_bukti']) ? base_url('pojasa/pic/document/' . (int) $row['id_dokumen_bukti']) : null;
        }
        unset($row);
        $state['request'] = (array) $state['request'];
        return $state;
    }

    private function datatableRow($row)
    {
        $editable = in_array($row['status'], array('DRAFT', 'REVISI_PIC'), true);
        $detailUrl = $row['status'] === 'MENUNGGU_KONFIRMASI_PIC'
            ? base_url('pojasa/workflow/detail/' . rawurlencode($row['kd_po_jasa']))
            : base_url('pojasa/pic/detail/' . rawurlencode($row['kd_po_jasa']));
        $actions = '<a class="btn btn-info btn-sm mr-1" href="' . $detailUrl . '" title="Detail"><i class="fas fa-eye"></i></a>';
        if ($editable) {
            $actions .= '<a class="btn btn-warning btn-sm mr-1" href="' . base_url('pojasa/pic/edit/' . rawurlencode($row['kd_po_jasa'])) . '" title="Edit"><i class="fas fa-edit"></i></a>';
            $actions .= '<button class="btn btn-success btn-sm mr-1 btn-submit-request" data-code="' . htmlspecialchars($row['kd_po_jasa'], ENT_QUOTES, 'UTF-8') . '" title="Ajukan"><i class="fas fa-paper-plane"></i></button>';
        }
        if ($row['status'] === 'DRAFT') {
            $actions .= '<button class="btn btn-danger btn-sm btn-delete-draft" data-code="' . htmlspecialchars($row['kd_po_jasa'], ENT_QUOTES, 'UTF-8') . '" title="Hapus"><i class="fas fa-trash"></i></button>';
        }
        return array(
            'tgl_request' => $row['tgl_request'],
            'vendor' => htmlspecialchars((string) ($row['nama_vendor_tampil'] ?: '-'), ENT_QUOTES, 'UTF-8'),
            'total' => 'Rp ' . number_format((float) $row['estimasi_total'], 0, ',', '.'),
            'status' => '<span class="badge badge-' . $this->statusColor($row['status']) . '">' . htmlspecialchars($this->statusLabel($row['status']), ENT_QUOTES, 'UTF-8') . '</span>',
            'po_pembelian_status' => !empty($row['kd_po_nk'])
                ? '<span class="badge badge-info">' . htmlspecialchars((string) ($row['po_pembelian_status'] ?: '-'), ENT_QUOTES, 'UTF-8') . '</span>'
                : '-',
            'actions' => '<div class="pojasa-pic-actions">' . $actions . '</div>',
        );
    }

    private function statusLabels()
    {
        return array(
            'DRAFT' => 'Draft',
            'MENUNGGU_PURCHASING_AWAL' => 'Menunggu review awal Purchasing',
            'MENUNGGU_KONFIRMASI_PIC' => 'Menunggu konfirmasi PIC',
            'MENUNGGU_KADEP' => 'Menunggu persetujuan Kadep',
            'PENDING_KADEP' => 'Ditunda Kadep',
            'REVISI_PIC' => 'Perlu revisi PIC',
            'REVISI_PURCHASING_KADEP' => 'Perlu revisi Purchasing dari Kadep',
            'MENUNGGU_PURCHASING' => 'Menunggu review Purchasing',
            'MENUNGGU_DIRUT_OPS' => 'Menunggu persetujuan Direktur Operasional',
            'MENUNGGU_PURCHASING_DIROPS' => 'Menunggu review Purchasing',
            'REVISI_PURCHASING_DIROPS' => 'Perlu revisi Purchasing dari Direktur Operasional',
            'MENUNGGU_DIREKTUR' => 'Menunggu persetujuan Direktur',
            'REVISI_PURCHASING_DIRUT' => 'Perlu revisi Purchasing dari Direktur',
            'MENUNGGU_PENERBITAN_PURCHASING' => 'Menunggu penerbitan SPK oleh Purchasing',
            'SPK_TERBIT' => 'SPK terbit',
            'ON_PROGRESS' => 'Dalam pengerjaan',
            'SELESAI' => 'Pekerjaan selesai',
            'DITUTUP' => 'Ditutup',
            'DITOLAK_KADEP' => 'Ditolak Kadep',
            'DITOLAK_DIRUT_OPS' => 'Ditolak Direktur Operasional',
            'DITOLAK_DIREKTUR' => 'Ditolak Direktur',
        );
    }

    private function statusLabel($status)
    {
        $labels = $this->statusLabels();
        return isset($labels[$status]) ? $labels[$status] : $status;
    }

    private function statusColor($status)
    {
        if (in_array($status, array('DITOLAK_KADEP', 'DITOLAK_DIRUT_OPS', 'DITOLAK_DIREKTUR'), true)) {
            return 'danger';
        }
        if (in_array($status, array('SPK_TERBIT', 'ON_PROGRESS', 'SELESAI', 'DITUTUP'), true)) {
            return 'success';
        }
        if (strpos($status, 'REVISI_') === 0 || strpos($status, 'PENDING_') === 0) {
            return 'warning';
        }
        return $status === 'DRAFT' ? 'secondary' : 'info';
    }

    private function parseNumber($value)
    {
        $value = trim((string) $value);
        $value = str_replace(' ', '', $value);
        if ($value === '') {
            return false;
        }
        if (strpos($value, ',') !== false) {
            $value = str_replace('.', '', $value);
            $value = str_replace(',', '.', $value);
        }
        return is_numeric($value) && is_finite((float) $value) ? round((float) $value, 2) : false;
    }

    private function validDate($value)
    {
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) $value)) {
            return false;
        }
        $parts = explode('-', $value);
        return checkdate((int) $parts[1], (int) $parts[2], (int) $parts[0]);
    }

    private function normalizeRequestCode($value)
    {
        return strtoupper(trim((string) $value));
    }

    private function validRequestCode($value)
    {
        return (bool) preg_match('/^[A-Z0-9_-]{5,30}$/', (string) $value);
    }

    private function formatBytes($bytes)
    {
        if ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2, ',', '.') . ' MB';
        }
        return number_format(max(0, $bytes) / 1024, 1, ',', '.') . ' KB';
    }

    private function modelError($code)
    {
        $map = array(
            'NUMBER_ERROR' => array(500, 'Nomor request tidak dapat dibuat.'),
            'EDIT_FORBIDDEN' => array(403, 'Request bukan milik PIC aktif atau status tidak dapat diedit.'),
            'DELETE_FORBIDDEN' => array(403, 'Hanya draft milik PIC yang dapat dihapus.'),
            'DOCUMENT_DELETE_FORBIDDEN' => array(403, 'Dokumen tidak dapat dihapus pada status ini.'),
            'CONCURRENT_UPDATE' => array(409, 'Request telah berubah. Muat data terbaru.'),
            'INVALID_STATUS' => array(409, 'Status request tidak mengizinkan aksi ini.'),
            'IDEMPOTENCY_CONFLICT' => array(409, 'Token aksi telah dipakai untuk payload atau user yang berbeda.'),
            'PROGRESS_REGRESSION' => array(409, 'Tanggal atau persentase progress tidak boleh mundur.'),
            'PROGRESS_INVALID' => array(422, 'Status pekerjaan dan persentase tidak sesuai alur.'),
            'DATABASE_ERROR' => array(500, 'Transaksi database gagal dan telah dibatalkan.'),
        );
        $item = isset($map[$code]) ? $map[$code] : array(500, 'Aksi PO Jasa gagal diproses.');
        return $this->json(false, $code, $item[1], array(), $item[0]);
    }

    private function json($success, $code, $message, $data = array(), $httpStatus = 200, $errors = array())
    {
        return $this->output
            ->set_status_header($httpStatus)
            ->set_content_type('application/json')
            ->set_output(json_encode(array(
                'success' => (bool) $success,
                'status' => (bool) $success,
                'code' => $code,
                'message' => $message,
                'data' => $data,
                'errors' => $errors ? $errors : new stdClass(),
            )));
    }
}
