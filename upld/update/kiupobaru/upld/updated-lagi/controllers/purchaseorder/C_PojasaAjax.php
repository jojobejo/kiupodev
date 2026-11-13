<?php
defined('BASEPATH') or exit('No direct script access allowed');

class C_PojasaAjax extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('PO/M_PojasaCore');
        $this->load->model('PO/M_PojasaWorkflow');
        $this->load->helper(array('pojasa_authorization', 'pojasa_status', 'pojasa_document'));
    }

    public function notifications()
    {
        if (!$this->requireAjax() || !$this->requireLoggedUser() || !$this->requireMethod('GET')) {
            return;
        }
        if (!$this->M_PojasaCore->tables_ready()) {
            return $this->jsonResponse(false, 'SCHEMA_NOT_READY', 'Fondasi database PO Jasa belum lengkap.', array(), 503);
        }

        $userId = (int) $this->session->userdata('id');
        $afterId = max(0, (int) $this->input->get('after_id', true));
        $limit = max(1, min(50, (int) $this->input->get('limit', true) ?: 20));
        $items = $this->M_PojasaCore->get_user_notifications($userId, $afterId, $limit);
        $latestId = $afterId;
        foreach ($items as $item) {
            $latestId = max($latestId, (int) $item['id_notifikasi']);
        }

        return $this->jsonResponse(true, 'OK', 'Notifikasi berhasil dimuat.', array(
            'unread_count' => $this->M_PojasaCore->count_unread_notifications($userId),
            'latest_id' => $latestId,
            'items' => $items,
            'csrf_token' => pojasa_csrf_token(),
        ));
    }

    public function mark_notification_read()
    {
        if (!$this->requireAjax() || !$this->requireLoggedUser() || !$this->requireMethod('POST')) {
            return;
        }
        if (!$this->M_PojasaCore->tables_ready()) {
            return $this->jsonResponse(false, 'SCHEMA_NOT_READY', 'Fondasi database PO Jasa belum lengkap.', array(), 503);
        }
        $token = (string) $this->input->get_request_header('X-POJASA-CSRF', true);
        if ($token === '') {
            $token = (string) $this->input->post('csrf_token', true);
        }
        if (!pojasa_csrf_is_valid($token)) {
            return $this->jsonResponse(false, 'CSRF_INVALID', 'Token keamanan tidak valid atau sudah kedaluwarsa.', array(), 403);
        }

        $ids = $this->input->post('ids');
        if (!is_array($ids)) {
            $ids = array($this->input->post('id_notifikasi', true));
        }
        if (!$this->M_PojasaCore->mark_notifications_read((int) $this->session->userdata('id'), $ids)) {
            return $this->jsonResponse(false, 'VALIDATION_ERROR', 'Notifikasi yang akan ditandai belum dipilih.', array(), 422);
        }

        return $this->jsonResponse(true, 'OK', 'Notifikasi telah dibaca.', array(
            'unread_count' => $this->M_PojasaCore->count_unread_notifications((int) $this->session->userdata('id')),
            'csrf_token' => pojasa_csrf_token(),
        ));
    }

    public function approval_action()
    {
        if (!$this->requireAjax() || !$this->requireLoggedUser() || !$this->requireMethod('POST') || !$this->requireCsrf()) {
            return;
        }
        if (!$this->M_PojasaCore->tables_ready()) {
            return $this->jsonResponse(false, 'SCHEMA_NOT_READY', 'Fondasi database PO Jasa belum lengkap.', array(), 503);
        }

        $requestCode = strtoupper(trim((string) $this->input->post('kd_po_jasa', true)));
        $action = strtoupper(trim((string) $this->input->post('action', true)));
        $legacyActions = array(
            'APPROVE_KADEP' => 'ACC',
            'APPROVE_DIREKTUR' => 'ACC',
            'SUBMIT_DIREKTUR' => 'SUBMIT',
        );
        if (isset($legacyActions[$action])) {
            $action = $legacyActions[$action];
        }
        $note = trim((string) $this->input->post('note', true));
        $actionToken = strtolower(trim((string) $this->input->post('idempotency_token', true)));
        $expectedStatus = strtoupper(trim((string) $this->input->post('expected_status', true)));
        $statusVersionRaw = $this->input->post('status_version', true);
        $statusVersion = $statusVersionRaw === null || $statusVersionRaw === '' ? null : (int) $statusVersionRaw;
        $errors = array();
        if (!$this->validRequestCode($requestCode)) {
            $errors['kd_po_jasa'] = 'Kode request tidak valid.';
        }
        if (!in_array($action, array('ACC', 'PENDING', 'REVISI', 'REJECT', 'SUBMIT', 'UPDATE', 'TERBITKAN'), true)) {
            $errors['action'] = 'Aksi approval tidak valid.';
        }
        if (in_array($action, array('REVISI', 'REJECT', 'UPDATE'), true) && $note === '') {
            $errors['note'] = 'Catatan wajib diisi untuk revisi, update, atau reject.';
        }
        if (mb_strlen($note) > 5000) {
            $errors['note'] = 'Catatan maksimal 5.000 karakter.';
        }
        if (!$this->validIdempotencyToken($actionToken)) {
            $errors['idempotency_token'] = 'Idempotency token wajib berupa UUID.';
        }
        if ($expectedStatus !== '' && !pojasa_status_is_valid($expectedStatus)) {
            $errors['expected_status'] = 'Status asal tidak valid.';
        }
        if ($statusVersion !== null && $statusVersion < 1) {
            $errors['status_version'] = 'Versi status tidak valid.';
        }
        if ($errors) {
            return $this->jsonResponse(false, 'VALIDATION_ERROR', 'Periksa kembali aksi approval.', array(), 422, $errors);
        }

        $context = pojasa_session_context();
        // The Purchasing UI warning is enforced server-side as well.  The
        // request cannot move to PIC while an item's cart/draft remains active.
        if ($context['role'] === 'PURCHASING' && $action === 'UPDATE') {
            $request = $this->M_PojasaWorkflow->get_request($requestCode, $context);
            if ($request && $request->status === 'MENUNGGU_PURCHASING_AWAL') {
                $pending = 0;
                $pendingMaterials = array();
                foreach ($this->M_PojasaWorkflow->get_materials($requestCode, $this->M_PojasaWorkflow->effective_revision($request)) as $material) {
                    if ((float) $material['remaining_need'] > 0.000001) {
                        $pending++;
                        $pendingMaterials[] = (string) $material['nama_material'];
                    }
                }
                if ($pending > 0) {
                    return $this->jsonResponse(false, 'PURCHASE_DRAFT_REVIEW_REQUIRED',
                        'Masih ada ' . $pending . ' material dengan draft pembelian aktif. Tinjau dan cek ulang seluruh draft sebelum melanjutkan workflow.',
                        array('pending_material_count' => $pending, 'pending_materials' => $pendingMaterials), 409);
                }
            }
        }

        $result = $this->M_PojasaCore->process_approval(
            $context,
            $requestCode,
            $action,
            $note,
            $actionToken,
            $expectedStatus !== '' ? $expectedStatus : null,
            $statusVersion
        );
        if (!$result['success']) {
            return $this->businessError($result['code']);
        }

        $message = $result['status'] === 'MENUNGGU_PENERBITAN_PURCHASING'
            ? 'ACC Direktur berhasil memperbarui status request. Penerbitan SPK dan PO Pembelian akan diproses oleh Purchasing.'
            : ($result['status'] === 'SPK_TERBIT'
            ? 'SPK dan PO Pembelian berhasil diterbitkan oleh Purchasing.'
            : 'Status approval PO Jasa berhasil diperbarui.');
        return $this->jsonResponse(true, $result['code'], $message, array(
            'status' => $result['status'],
            'no_spk' => $result['no_spk'],
            'status_version' => $result['status_version'],
            'csrf_token' => pojasa_csrf_token(),
        ));
    }

    public function manual_spk_not_allowed()
    {
        if (!$this->requireAjax() || !$this->requireLoggedUser() || !$this->requireMethod('POST') || !$this->requireCsrf()) {
            return;
        }

        return $this->jsonResponse(
            false,
            'MANUAL_SPK_DISABLED',
            'SPK diterbitkan oleh Purchasing setelah ACC Direktur melalui tombol Terbitkan SPK & PO Pembelian pada workflow.',
            array(),
            409
        );
    }

    public function save_cost()
    {
        if (!$this->requireAjax() || !$this->requireLoggedUser() || !$this->requireMethod('POST') || !$this->requireCsrf()) {
            return;
        }
        if (!$this->M_PojasaCore->tables_ready()) {
            return $this->jsonResponse(false, 'SCHEMA_NOT_READY', 'Fondasi database PO Jasa belum lengkap.', array(), 503);
        }

        $requestCode = strtoupper(trim((string) $this->input->post('kd_po_jasa', true)));
        if (!$this->validRequestCode($requestCode)) {
            return $this->jsonResponse(false, 'VALIDATION_ERROR', 'Periksa kembali input biaya aktual.', array(), 422, array(
                'kd_po_jasa' => 'Kode request tidak valid.',
            ));
        }
        $request = $this->M_PojasaCore->get_request($requestCode);
        $context = pojasa_session_context();
        if (!$request) {
            return $this->jsonResponse(false, 'NOT_FOUND', 'Request PO Jasa tidak ditemukan.', array(), 404);
        }
        if (!pojasa_can_act_on_request($context, $request, 'COST_INPUT')) {
            return $this->jsonResponse(false, 'FORBIDDEN', 'Biaya aktual hanya dapat diinput PIC pemilik request atau Admin.', array(), 403);
        }

        $costDate = trim((string) $this->input->post('tgl_biaya', true));
        $costSource = strtoupper(trim((string) $this->input->post('cost_source', true)));
        $purchaseDraftId = (int) $this->input->post('id_draft_pembelian', true);
        $scopeId = (int) $this->input->post('id_scope', true);
        $materialId = (int) $this->input->post('id_material', true);
        $costType = trim((string) $this->input->post('jenis_biaya', true));
        $description = trim((string) $this->input->post('deskripsi', true));
        $amount = $this->parseAmount($this->input->post('nominal', true));
        $idempotencyToken = strtolower(trim((string) $this->input->post('idempotency_token', true)));
        $errors = array();
        if (!$this->validDate($costDate)) {
            $errors['tgl_biaya'] = 'Tanggal biaya wajib berformat YYYY-MM-DD.';
        }
        if (!in_array($costSource, array('PEMBELIAN_BARU', 'NOTA_INVOICE_BARU'), true)) {
            $errors['cost_source'] = 'Sumber biaya hanya boleh pembelian baru atau nota/invoice baru; material stok tidak boleh dihitung.';
        }
        if ($costType === '' || mb_strlen($costType) > 100) {
            $errors['jenis_biaya'] = 'Jenis biaya wajib diisi, maksimal 100 karakter.';
        }
        if ($description === '' || mb_strlen($description) > 5000) {
            $errors['deskripsi'] = 'Deskripsi wajib diisi, maksimal 5.000 karakter.';
        }
        if ($amount === false || $amount <= 0 || $amount >= 10000000000000000) {
            $errors['nominal'] = 'Nominal harus berupa angka lebih dari 0.';
        }
        if (!$this->validIdempotencyToken($idempotencyToken)) {
            $errors['idempotency_token'] = 'Idempotency token wajib berupa UUID.';
        }
        if (empty($_FILES['bukti']['name'])) {
            $errors['bukti'] = 'Bukti biaya aktual wajib dilampirkan.';
        }
        if ($errors) {
            return $this->jsonResponse(false, 'VALIDATION_ERROR', 'Periksa kembali input biaya aktual.', array(), 422, $errors);
        }

        $upload = $this->uploadCostEvidence($request, $costDate);
        if (!$upload['success']) {
            return $this->jsonResponse(false, 'UPLOAD_ERROR', $upload['message'], array(), 422, array('bukti' => $upload['message']));
        }

        $result = $this->M_PojasaCore->save_actual_cost($context, $requestCode, array(
            'tgl_biaya' => $costDate,
            'cost_source' => $costSource,
            'id_draft_pembelian' => $purchaseDraftId > 0 ? $purchaseDraftId : null,
            'id_scope' => $scopeId > 0 ? $scopeId : null,
            'id_material' => $materialId > 0 ? $materialId : null,
            'jenis_biaya' => $costType,
            'deskripsi' => $description,
            'nominal' => $amount,
            'idempotency_token' => $idempotencyToken,
        ), array(
            'jenis_dokumen' => 'BUKTI_BIAYA_AKTUAL',
            'keterangan' => 'Bukti biaya aktual: ' . $costType,
            'file_path' => $upload['relative_path'],
            'file_original' => $upload['original_name'],
            'mime_type' => $upload['mime_type'],
            'file_size_bytes' => $upload['file_size_bytes'],
            'sha256' => $upload['sha256'],
        ));
        if (!$result['success'] || $result['code'] === 'IDEMPOTENT_REPLAY') {
            $this->removeNewUpload($upload['relative_path']);
        }
        if (!$result['success']) {
            return $this->businessError($result['code']);
        }

        return $this->jsonResponse(true, $result['code'], 'Biaya aktual dan bukti berhasil disimpan.', array(
            'id_biaya_aktual' => $result['id'],
            'request_code' => $requestCode,
            'record_status' => isset($result['record_status']) ? $result['record_status'] : 'DICATAT',
            'csrf_token' => pojasa_csrf_token(),
        ));
    }

    public function update_cost()
    {
        if (!$this->requireAjax() || !$this->requireLoggedUser() || !$this->requireMethod('POST') || !$this->requireCsrf()) {
            return;
        }
        $requestCode = strtoupper(trim((string) $this->input->post('kd_po_jasa', true)));
        $costId = (int) $this->input->post('id_biaya_aktual', true);
        $date = trim((string) $this->input->post('tgl_biaya', true));
        $source = strtoupper(trim((string) $this->input->post('cost_source', true)));
        $type = trim((string) $this->input->post('jenis_biaya', true));
        $description = trim((string) $this->input->post('deskripsi', true));
        $amount = $this->parseAmount($this->input->post('nominal', true));
        $reason = trim((string) $this->input->post('reason', true));
        $errors = array();
        if (!$this->validRequestCode($requestCode) || $costId <= 0) $errors['cost'] = 'Referensi biaya tidak valid.';
        if (!$this->validDate($date)) $errors['tgl_biaya'] = 'Tanggal biaya tidak valid.';
        if (!in_array($source, array('PEMBELIAN_BARU', 'NOTA_INVOICE_BARU'), true)) $errors['cost_source'] = 'Sumber biaya manual tidak valid.';
        if ($type === '' || mb_strlen($type) > 100) $errors['jenis_biaya'] = 'Jenis biaya wajib diisi, maksimal 100 karakter.';
        if ($description === '' || mb_strlen($description) > 5000) $errors['deskripsi'] = 'Deskripsi wajib diisi, maksimal 5.000 karakter.';
        if ($amount === false || $amount <= 0) $errors['nominal'] = 'Nominal harus lebih dari 0.';
        if ($reason === '' || mb_strlen($reason) > 5000) $errors['reason'] = 'Alasan perbaikan wajib diisi, maksimal 5.000 karakter.';
        if ($errors) return $this->jsonResponse(false, 'VALIDATION_ERROR', 'Periksa kembali perbaikan biaya.', array(), 422, $errors);
        $result = $this->M_PojasaCore->update_actual_cost(pojasa_session_context(), $requestCode, $costId, array(
            'tgl_biaya' => $date, 'cost_source' => $source, 'jenis_biaya' => $type,
            'deskripsi' => $description, 'nominal' => $amount,
            'id_scope' => (int) $this->input->post('id_scope', true),
            'id_material' => (int) $this->input->post('id_material', true),
        ), $reason);
        if (!$result['success']) return $this->businessError($result['code']);
        return $this->jsonResponse(true, $result['code'], 'Biaya aktual manual berhasil diperbaiki.', array(
            'id_biaya_aktual' => $result['id'], 'record_status' => $result['record_status'], 'csrf_token' => pojasa_csrf_token(),
        ));
    }

    public function verify_cost()
    {
        if (!$this->requireAjax() || !$this->requireLoggedUser() || !$this->requireMethod('POST') || !$this->requireCsrf()) return;
        $requestCode = strtoupper(trim((string) $this->input->post('kd_po_jasa', true)));
        $costId = (int) $this->input->post('id_biaya_aktual', true);
        $status = strtoupper(trim((string) $this->input->post('verification_status', true)));
        $note = trim((string) $this->input->post('note', true));
        if (!$this->validRequestCode($requestCode) || $costId <= 0 || !in_array($status, array('DIVERIFIKASI', 'PERLU_PERBAIKAN'), true) || mb_strlen($note) > 5000) {
            return $this->jsonResponse(false, 'VALIDATION_ERROR', 'Data verifikasi biaya tidak valid.', array(), 422);
        }
        $result = $this->M_PojasaCore->verify_actual_cost(pojasa_session_context(), $requestCode, $costId, $status, $note);
        if (!$result['success']) return $this->businessError($result['code']);
        return $this->jsonResponse(true, $result['code'], 'Verifikasi biaya berhasil disimpan.', array('csrf_token' => pojasa_csrf_token()));
    }

    public function confirm_stock_receipt()
    {
        if (!$this->requireAjax() || !$this->requireLoggedUser() || !$this->requireMethod('POST') || !$this->requireCsrf()) {
            return;
        }
        if (!$this->M_PojasaCore->tables_ready()) {
            return $this->jsonResponse(false, 'SCHEMA_NOT_READY', 'Fondasi database PO Jasa belum lengkap.', array(), 503);
        }

        $requestCode = strtoupper(trim((string) $this->input->post('kd_po_jasa', true)));
        $receiptDate = trim((string) $this->input->post('receipt_at', true));
        $note = trim((string) $this->input->post('catatan', true));
        $idempotencyToken = strtolower(trim((string) $this->input->post('idempotency_token', true)));
        $rawItems = $this->input->post('items');
        if (is_string($rawItems)) {
            $rawItems = json_decode($rawItems, true);
        }

        $errors = array();
        if (!$this->validRequestCode($requestCode)) {
            $errors['kd_po_jasa'] = 'Kode request tidak valid.';
        }
        if (!$this->validDate($receiptDate)) {
            $errors['receipt_at'] = 'Tanggal penerimaan wajib berformat YYYY-MM-DD.';
        }
        if (mb_strlen($note) > 5000) {
            $errors['catatan'] = 'Catatan maksimal 5.000 karakter.';
        }
        if (!$this->validIdempotencyToken($idempotencyToken)) {
            $errors['idempotency_token'] = 'Idempotency token wajib berupa UUID.';
        }

        $items = array();
        foreach ((array) $rawItems as $rawItem) {
            $allocationId = isset($rawItem['id_allocation']) ? (int) $rawItem['id_allocation'] : 0;
            $quantity = isset($rawItem['qty_received']) ? $this->parseAmount($rawItem['qty_received']) : false;
            if ($allocationId <= 0 || $quantity === false || $quantity <= 0) {
                $errors['items'] = 'Setiap item harus memiliki alokasi dan jumlah diterima lebih dari 0.';
                break;
            }
            if (!isset($items[$allocationId])) {
                $items[$allocationId] = array('id_allocation' => $allocationId, 'qty_received' => 0.0);
            }
            $items[$allocationId]['qty_received'] += $quantity;
        }
        if (!$items || count($items) > 100) {
            $errors['items'] = 'Minimal satu dan maksimal 100 alokasi dapat dikonfirmasi.';
        }
        if ($errors) {
            return $this->jsonResponse(false, 'VALIDATION_ERROR', 'Periksa kembali data penerimaan material.', array(), 422, $errors);
        }

        $request = $this->M_PojasaCore->get_request($requestCode);
        $context = pojasa_session_context();
        if (!$request) {
            return $this->jsonResponse(false, 'NOT_FOUND', 'Request PO Jasa tidak ditemukan.', array(), 404);
        }
        if (!pojasa_can_act_on_request($context, $request, 'PIC_OWNER')) {
            return $this->jsonResponse(false, 'FORBIDDEN', 'Penerimaan material hanya dapat dikonfirmasi PIC pemilik request atau Admin.', array(), 403);
        }

        $result = $this->M_PojasaCore->confirm_stock_receipt(
            $context,
            $requestCode,
            $receiptDate . ' ' . date('H:i:s'),
            $note,
            array_values($items),
            $idempotencyToken
        );
        if (!$result['success']) {
            return $this->businessError($result['code']);
        }

        return $this->jsonResponse(true, $result['code'], 'Material diterima PIC dan transaksi stok keluar berhasil dicatat.', array(
            'id_receipt' => $result['id'],
            'no_receipt' => $result['number'],
            'csrf_token' => pojasa_csrf_token(),
        ));
    }

    private function requireMethod($method)
    {
        if (strtoupper($this->input->method(true)) === strtoupper($method)) {
            return true;
        }

        $this->jsonResponse(false, 'METHOD_NOT_ALLOWED', 'Metode request tidak diperbolehkan.', array(), 405);
        return false;
    }

    private function requireAjax()
    {
        if ($this->input->is_ajax_request()
            || strpos((string) $this->input->get_request_header('Accept', true), 'application/json') !== false) {
            return true;
        }

        $this->jsonResponse(false, 'AJAX_REQUIRED', 'Endpoint ini hanya menerima request AJAX.', array(), 400);
        return false;
    }

    private function requireLoggedUser()
    {
        if ($this->session->userdata('status') === 'is_login' && (int) $this->session->userdata('id') > 0) {
            return true;
        }

        $this->jsonResponse(false, 'AUTH_REQUIRED', 'Session login tidak ditemukan atau sudah berakhir.', array(), 401);
        return false;
    }

    private function requireCsrf()
    {
        $token = (string) $this->input->get_request_header('X-POJASA-CSRF', true);
        if ($token === '') {
            $token = (string) $this->input->post('csrf_token', true);
        }
        if (pojasa_csrf_is_valid($token)) {
            return true;
        }

        $this->jsonResponse(false, 'CSRF_INVALID', 'Token keamanan tidak valid atau sudah kedaluwarsa.', array(), 403);
        return false;
    }

    private function validRequestCode($value)
    {
        return (bool) preg_match('/^[A-Z0-9_-]{5,30}$/', (string) $value);
    }

    private function validDate($value)
    {
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) $value)) {
            return false;
        }
        $parts = explode('-', $value);
        return checkdate((int) $parts[1], (int) $parts[2], (int) $parts[0]);
    }

    private function validIdempotencyToken($value)
    {
        return (bool) preg_match('/^[a-f0-9]{8}-[a-f0-9]{4}-[1-5][a-f0-9]{3}-[89ab][a-f0-9]{3}-[a-f0-9]{12}$/i', (string) $value);
    }

    private function parseAmount($value)
    {
        $value = trim((string) $value);
        $value = str_replace(' ', '', $value);
        if ($value === '') {
            return false;
        }
        if (strpos($value, ',') !== false) {
            $value = str_replace('.', '', $value);
            $value = str_replace(',', '.', $value);
        } elseif (preg_match('/^\d{1,3}(\.\d{3})+$/', $value)) {
            $value = str_replace('.', '', $value);
        }

        return is_numeric($value) && is_finite((float) $value) ? round((float) $value, 2) : false;
    }

    private function uploadCostEvidence($request, $date)
    {
        $validation = pojasa_validate_document_file($_FILES['bukti'], 10485760);
        if (!$validation['success']) {
            return $validation;
        }
        $department = preg_replace('/[^A-Z0-9_-]+/', '_', pojasa_normalize_department($request->departemen));
        $department = trim($department, '_') ?: 'UMUM';
        $timestamp = strtotime($date) ?: time();
        $relativeDirectory = 'images/pojasa/' . $department . '/' . date('Y/m/d', $timestamp) . '/';
        $absoluteDirectory = FCPATH . $relativeDirectory;
        if (!is_dir($absoluteDirectory) && !@mkdir($absoluteDirectory, 0777, true) && !is_dir($absoluteDirectory)) {
            return array('success' => false, 'message' => 'Folder bukti biaya tidak dapat dibuat.');
        }

        $config = array(
            'upload_path' => $absoluteDirectory,
            'allowed_types' => 'jpg|jpeg|png|pdf|doc|docx|xls|xlsx|txt|rtf|csv',
            'max_size' => 10240,
            'overwrite' => false,
            'encrypt_name' => true,
        );
        $this->load->library('upload', $config);
        $this->upload->initialize($config);
        if (!$this->upload->do_upload('bukti')) {
            return array('success' => false, 'message' => strip_tags($this->upload->display_errors('', '')));
        }

        $file = $this->upload->data();
        $absolutePath = $absoluteDirectory . $file['file_name'];
        return array(
            'success' => true,
            'relative_path' => $relativeDirectory . $file['file_name'],
            'original_name' => $file['client_name'],
            'mime_type' => $validation['mime_type'],
            'file_size_bytes' => (int) round(((float) $file['file_size']) * 1024),
            'sha256' => hash_file('sha256', $absolutePath),
        );
    }

    private function removeNewUpload($relativePath)
    {
        $base = realpath(FCPATH . 'images/pojasa');
        $file = realpath(FCPATH . ltrim((string) $relativePath, '/'));
        if ($base && $file && strpos($file, $base . DIRECTORY_SEPARATOR) === 0 && is_file($file)) {
            @unlink($file);
        }
    }

    private function businessError($code)
    {
        $messages = array(
            'NOT_FOUND' => array(404, 'Request PO Jasa tidak ditemukan.'),
            'FORBIDDEN' => array(403, 'Akses terhadap request ditolak.'),
            'INVALID_STATUS' => array(409, 'Status request tidak mengizinkan aksi ini.'),
            'INVALID_ACTION' => array(409, 'Aksi tidak sesuai dengan status atau role aktif.'),
            'NOTE_REQUIRED' => array(422, 'Catatan wajib diisi untuk revisi, update, atau konfirmasi PIC.'),
            'PURCHASING_REVIEW_NOT_SAVED' => array(409, 'Simpan seluruh review Purchasing sebelum mengirim update ke PIC.'),
            'REVISION_NOT_SAVED' => array(409, 'Perbaikan revisi Purchasing wajib disimpan sebelum diajukan ulang.'),
            'SCHEDULE_REQUIRED' => array(422, 'Purchasing wajib menyimpan tanggal mulai dan selesai yang valid sebelum pengajuan final.'),
            'IDEMPOTENCY_CONFLICT' => array(409, 'Idempotency token sudah digunakan untuk request lain.'),
            'COST_SOURCE_INVALID' => array(422, 'Material stok nonkomersial tidak boleh dihitung sebagai biaya aktual.'),
            'PURCHASE_DRAFT_INVALID' => array(422, 'Referensi draft pembelian tidak valid atau telah dibatalkan.'),
            'COST_ALLOCATION_INVALID' => array(422, 'Alokasi biaya ke scope/material tidak valid.'),
            'SCHEMA_NOT_READY' => array(503, 'Migration PO Jasa terbaru belum lengkap.'),
            'PROGRESS_REGRESSION' => array(409, 'Tanggal atau persentase progress tidak boleh mundur.'),
            'PROGRESS_INVALID' => array(409, 'Kombinasi status pekerjaan dan persentase tidak valid.'),
            'CONCURRENT_UPDATE' => array(409, 'Request telah berubah oleh proses lain. Muat ulang data terbaru.'),
            'ALLOCATION_INVALID' => array(422, 'Alokasi stok tidak valid atau sudah dilepas.'),
            'QUANTITY_INVALID' => array(422, 'Jumlah diterima melebihi sisa alokasi.'),
            'STOCK_INSUFFICIENT' => array(409, 'Stok nonkomersial tidak cukup untuk penerimaan ini.'),
            'NUMBER_ERROR' => array(500, 'Nomor dokumen tidak dapat dibuat.'),
            'DATABASE_ERROR' => array(500, 'Transaksi database gagal dan telah dibatalkan.'),
        );
        $item = isset($messages[$code]) ? $messages[$code] : array(500, 'Aksi PO Jasa gagal diproses.');

        return $this->jsonResponse(false, $code, $item[1], array(), $item[0]);
    }

    private function jsonResponse($success, $code, $message, $data = array(), $httpStatus = 200, $errors = array())
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
