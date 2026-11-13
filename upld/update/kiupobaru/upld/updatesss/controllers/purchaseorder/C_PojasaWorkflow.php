<?php
defined('BASEPATH') or exit('No direct script access allowed');

class C_PojasaWorkflow extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('PO/M_PojasaWorkflow');
        $this->load->model('PO/M_PojasaIntegration');
        $this->load->model('PO/M_PojasaPickup');
        $this->load->model('PO/M_PojasaDemo');
        $this->load->helper(array('pojasa_authorization', 'pojasa_status'));
    }

    public function index()
    {
        if (!$this->guardPage()) {
            return;
        }
        $data = $this->baseData('Workflow Approval PO Jasa');
        $data['work_statuses'] = $this->M_PojasaWorkflow->work_statuses($this->context());
        $data['can_create_demo'] = $this->context()['role'] === 'PURCHASING' && $this->M_PojasaDemo->schema_ready();
        $this->render('content/po/jasa/workflow/list', $data, 'content/po/jasa/workflow/workflow_js');
    }

    public function detail($requestCode)
    {
        if (!$this->guardPage()) {
            return;
        }
        $context = $this->context();
        $request = $this->M_PojasaWorkflow->get_request($this->normalizeCode($requestCode), $context);
        if (!$request) {
            show_404();
            return;
        }
        $revision = $this->M_PojasaWorkflow->effective_revision($request);
        $data = $this->baseData('Detail Workflow PO Jasa');
        $data['request'] = $request;
        $data['scopes'] = $this->M_PojasaWorkflow->get_scopes($request->kd_po_jasa, $revision);
        $data['materials'] = $this->M_PojasaWorkflow->get_materials($request->kd_po_jasa, $revision);
        $data['purchasing_totals'] = $this->M_PojasaWorkflow->get_purchasing_totals($request->kd_po_jasa, $revision);
        $data['documents'] = $this->M_PojasaWorkflow->get_documents($request->kd_po_jasa);
        $data['history'] = $this->M_PojasaWorkflow->get_history($request->kd_po_jasa);
        $data['allowed_actions'] = $this->M_PojasaWorkflow->allowed_actions($context, $request);
        $data['can_edit_purchasing'] = $this->M_PojasaWorkflow->can_edit_purchasing($context, $request);
        $data['final_approval_view'] = in_array($context['role'], array('KADEP', 'DIREKTUR_OPERASIONAL', 'DIREKTUR'), true);
        $data['vendors'] = $this->M_PojasaWorkflow->get_active_vendors();
        $data['pickup_state'] = $this->M_PojasaPickup->state($request->kd_po_jasa);
        $data['purchase_integration'] = $this->M_PojasaIntegration->get_submission_state($request->kd_po_jasa);
        $data['purchasingReviewNote'] = ($context['role'] === 'PIC' && $request->status === 'MENUNGGU_KONFIRMASI_PIC')
            ? $this->M_PojasaWorkflow->get_latest_purchasing_review_note($request->kd_po_jasa)
            : null;
        $this->render('content/po/jasa/workflow/detail', $data, 'content/po/jasa/workflow/workflow_js');
    }

    public function pickup_decision()
    {
        if (!$this->guardAjax('POST', true)) return;
        $code = $this->normalizeCode($this->input->post('kd_po_jasa', true));
        $action = strtoupper(trim((string)$this->input->post('action', true)));
        if (!in_array($action, array('ACC', 'REJECT'), true)) return $this->json(false, 'VALIDATION_ERROR', 'Keputusan tidak valid.', array(), 422);
        $result = $this->M_PojasaPickup->decide($this->context(), $code, $action, trim((string)$this->input->post('note', true)));
        return $this->json($result['success'], $result['code'], $result['success'] ? 'Keputusan pengambilan tersimpan.' : 'Anda tidak berwenang memutuskan pengambilan ini.', array('pickup' => $this->M_PojasaPickup->state($code)), $result['success'] ? 200 : 403);
    }

    public function create_demo()
    {
        if (!$this->guardAjax('POST', true)) return;
        $context = $this->context();
        if ($context['role'] !== 'PURCHASING') {
            return $this->json(false, 'FORBIDDEN', 'Automasi demo hanya dapat dijalankan oleh Purchasing.', array(), 403);
        }
        $result = $this->M_PojasaDemo->create($context);
        if (!$result['success']) {
            $message = $result['code'] === 'STOCK_NOT_FOUND'
                ? 'Tidak ada barang nonkomersial dengan stok tersedia untuk dijadikan demo.'
                : 'Automasi demo gagal dibuat (' . $result['code'] . ').';
            return $this->json(false, $result['code'], $message, array(), $result['code'] === 'STOCK_NOT_FOUND' ? 422 : 500);
        }
        return $this->json(true, 'CREATED', 'Data demo PO Jasa, SPK, master barang baru, dan PO Pembelian berhasil dibuat.', $result);
    }

    public function datatable()
    {
        if (!$this->guardAjax('GET')) {
            return;
        }
        $order = $this->input->get('order');
        $search = $this->input->get('search');
        $dateFrom = trim((string) $this->input->get('date_from', true));
        $dateTo = trim((string) $this->input->get('date_to', true));
        $params = array(
            'status' => strtoupper(trim((string) $this->input->get('status', true))),
            'date_from' => $this->validDate($dateFrom) ? $dateFrom : '',
            'date_to' => $this->validDate($dateTo) ? $dateTo : '',
            'search' => is_array($search) && isset($search['value']) ? trim((string) $search['value']) : '',
            'order_column' => is_array($order) && isset($order[0]['column']) ? (int) $order[0]['column'] : 0,
            'order_direction' => is_array($order) && isset($order[0]['dir']) ? $order[0]['dir'] : 'desc',
            'start' => max(0, (int) $this->input->get('start', true)),
            'length' => max(1, min(100, (int) $this->input->get('length', true) ?: 10)),
        );
        $result = $this->M_PojasaWorkflow->datatable_requests($this->context(), $params);
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

    public function state($requestCode)
    {
        if (!$this->guardAjax('GET')) {
            return;
        }
        $context = $this->context();
        $request = $this->M_PojasaWorkflow->get_request($this->normalizeCode($requestCode), $context);
        if (!$request) {
            return $this->json(false, 'NOT_FOUND', 'Request tidak ditemukan atau tidak dapat diakses.', array(), 404);
        }
        return $this->json(true, 'OK', 'Status workflow berhasil dimuat.', $this->statePayload($request, $context));
    }

    public function save_purchasing_review()
    {
        if (!$this->guardAjax('POST', true)) {
            return;
        }
        $context = $this->context();
        $requestCode = $this->normalizeCode($this->input->post('kd_po_jasa', true));
        $expectedStatus = strtoupper(trim((string) $this->input->post('expected_status', true)));
        $expectedVersion = (int) $this->input->post('status_version', true);
        $request = $this->M_PojasaWorkflow->get_request($requestCode, $context);
        if (!$request || !$this->M_PojasaWorkflow->can_edit_purchasing($context, $request)) {
            return $this->json(false, 'FORBIDDEN', 'Review Purchasing tidak diizinkan untuk request ini.', array(), 403);
        }
        if ($expectedStatus === '' || $expectedVersion <= 0) {
            return $this->json(false, 'VALIDATION_ERROR', 'Status-versi request wajib dikirim.', array(), 422);
        }
        $raw = $this->input->post('payload');
        $payload = is_array($raw) ? $raw : json_decode((string) $raw, true);
        if (!is_array($payload)) {
            return $this->json(false, 'VALIDATION_ERROR', 'Payload review tidak valid.', array(), 422);
        }
        $validation = $this->validateReview($request, $payload);
        if (!$validation['success']) {
            return $this->json(false, 'VALIDATION_ERROR', 'Periksa kembali review Purchasing.', array(), 422, $validation['errors']);
        }
        $result = $this->M_PojasaWorkflow->save_purchasing_review(
            $context, $requestCode, $validation['payload'], $expectedStatus, $expectedVersion
        );
        if (!$result['success']) {
            return $this->businessError($result['code']);
        }
        $fresh = $this->M_PojasaWorkflow->get_request($requestCode, $context);
        return $this->json(true, 'SAVED', 'Review/perbaikan Purchasing berhasil disimpan.', array_merge(
            $this->statePayload($fresh, $context),
            array('totals' => $result['totals'], 'revision_no' => $result['revision_no'])
        ));
    }

    public function document($documentId)
    {
        return $this->serveDocument($documentId, 'download');
    }

    public function document_preview($documentId)
    {
        return $this->serveDocument($documentId, 'preview');
    }

    public function document_download($documentId)
    {
        return $this->serveDocument($documentId, 'download');
    }

    public function planning_detail($type, $materialId)
    {
        if (!$this->guardAjax('GET')) return;
        $type = strtolower(trim((string) $type));
        if (!in_array($type, array('allocation', 'draft'), true)) {
            return $this->json(false, 'VALIDATION_ERROR', 'Jenis detail tidak valid.', array(), 422);
        }
        $code = $this->normalizeCode($this->input->get('kd_po_jasa', true));
        $request = $this->M_PojasaWorkflow->get_request($code, $this->context());
        if (!$request) return $this->json(false, 'FORBIDDEN', 'Request tidak dapat diakses.', array(), 403);
        return $this->json(true, 'OK', 'Detail pemenuhan dimuat.', array(
            'items' => $this->M_PojasaWorkflow->get_planning_detail($code, (int) $materialId, $type),
            'csrf_token' => pojasa_csrf_token(),
        ));
    }

    private function serveDocument($documentId, $mode)
    {
        if (!$this->guardRole()) {
            show_error('Akses dokumen ditolak.', 403);
            return;
        }
        $document = $this->M_PojasaWorkflow->get_document((int) $documentId, $this->context());
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
        $previewable = $this->M_PojasaWorkflow->document_can_preview($document);
        if ($mode === 'preview' && !$previewable) {
            show_error('Tipe dokumen ini hanya dapat diunduh.', 415);
            return;
        }
        $inline = $mode === 'preview';
        $name = str_replace(array('"', "\r", "\n"), '', $document->file_original);
        $this->output->set_content_type($document->mime_type ?: 'application/octet-stream')
            ->set_header('X-Content-Type-Options: nosniff')
            ->set_header('X-Frame-Options: SAMEORIGIN')
            ->set_header('Cache-Control: private, no-store, max-age=0')
            ->set_header('Content-Disposition: ' . ($inline ? 'inline' : 'attachment') . '; filename="' . $name . '"')
            ->set_output(file_get_contents($path));
    }

    private function validateReview($request, $payload)
    {
        $errors = array();
        $segment = strtolower(trim((string) (isset($payload['segment']) ? $payload['segment'] : '')));
        if (!in_array($segment, array('purchasing', 'scope', 'material'), true)) {
            $errors['segment'] = 'Segmen review tidak valid.';
        }
        $vendorCode = strtoupper(trim((string) (isset($payload['kd_vendor_jasa']) ? $payload['kd_vendor_jasa'] : '')));
        $note = trim((string) (isset($payload['note']) ? $payload['note'] : ''));
        $startDate = trim((string) (isset($payload['tgl_mulai_pekerjaan']) ? $payload['tgl_mulai_pekerjaan'] : ''));
        $endDate = trim((string) (isset($payload['tgl_selesai_pekerjaan']) ? $payload['tgl_selesai_pekerjaan'] : ''));
        if ($vendorCode !== '' && (int) $this->db->where(array('kd_vendor_jasa' => $vendorCode, 'status_vendor' => 'AKTIF'))->where('deleted_at IS NULL', null, false)->count_all_results('tbpo_jasa_vendor') !== 1) {
            $errors['vendor'] = 'Vendor tidak aktif atau tidak ditemukan.';
        }
        if (mb_strlen($note) > 5000) {
            $errors['note'] = 'Catatan maksimal 5.000 karakter.';
        }
        if ($segment === 'purchasing' && in_array($request->status, array('REVISI_PURCHASING_KADEP', 'REVISI_PURCHASING_DIROPS', 'REVISI_PURCHASING_DIRUT'), true) && $note === '') {
            $errors['note'] = 'Catatan perbaikan wajib diisi sebelum revisi disimpan.';
        }
        if ($segment === 'purchasing' && ($startDate === '' || $endDate === '')) {
            $errors['jadwal'] = 'Tanggal mulai dan selesai wajib diisi.';
        } elseif ($segment === 'purchasing' && (!$this->validDate($startDate) || !$this->validDate($endDate))) {
            $errors['jadwal'] = 'Tanggal mulai dan selesai harus valid.';
        } elseif ($segment === 'purchasing' && $endDate < $startDate) {
            $errors['jadwal'] = 'Tanggal selesai tidak boleh lebih awal dari tanggal mulai.';
        }
        $revision = $this->M_PojasaWorkflow->effective_revision($request);
        $scopeMap = array();
        foreach ($this->M_PojasaWorkflow->get_scopes($request->kd_po_jasa, $revision) as $row) {
            $scopeMap[(int) $row['id_scope']] = $row;
        }
        $materialMap = array();
        foreach ($this->M_PojasaWorkflow->get_materials($request->kd_po_jasa, $revision) as $row) {
            $materialMap[(int) $row['id_material']] = $row;
        }
        $scopes = $segment === 'scope' ? $this->validateReviewRows(isset($payload['scopes']) ? $payload['scopes'] : array(), $scopeMap, true, $errors) : array();
        $materials = $segment === 'material' ? $this->validateReviewRows(isset($payload['materials']) ? $payload['materials'] : array(), $materialMap, false, $errors) : array();
        $deletedScopes = $segment === 'scope' ? $this->validateDeletedRows(isset($payload['deleted_scopes']) ? $payload['deleted_scopes'] : array(), $scopeMap, 'scope', $errors) : array();
        $deletedMaterials = $segment === 'material' ? $this->validateDeletedRows(isset($payload['deleted_materials']) ? $payload['deleted_materials'] : array(), $materialMap, 'material', $errors) : array();
        return array(
            'success' => !$errors,
            'errors' => $errors,
            'payload' => array('segment' => $segment, 'kd_vendor_jasa' => $vendorCode, 'note' => $note, 'tgl_mulai_pekerjaan' => $startDate ?: null, 'tgl_selesai_pekerjaan' => $endDate ?: null, 'scopes' => $scopes, 'materials' => $materials, 'deleted_scopes' => $deletedScopes, 'deleted_materials' => $deletedMaterials),
        );
    }

    private function validateReviewRows($rows, $sourceMap, $isScope, &$errors)
    {
        if (!is_array($rows) || count($rows) > 500) {
            $errors[$isScope ? 'scopes' : 'materials'] = 'Baris review tidak valid atau melebihi 500.';
            return array();
        }
        $clean = array();
        foreach ($rows as $index => $row) {
            $id = isset($row['id']) ? (int) $row['id'] : 0;
            $isNew = !empty($row['is_new']);
            if (!$isNew && ($id <= 0 || !isset($sourceMap[$id]))) {
                $errors[($isScope ? 'scope_' : 'material_') . $index] = 'Referensi baris tidak ditemukan.'; continue;
            }
            $source = $isNew ? array() : $sourceMap[$id];
            $price = $this->parseCurrencyAmount(isset($row['harga_pembanding']) ? $row['harga_pembanding'] : '');
            $sourceQty = $isScope ? (isset($source['qty']) ? $source['qty'] : '') : (isset($source['qty_kebutuhan']) ? $source['qty_kebutuhan'] : '');
            $qty = $this->parseAmount(isset($row['qty']) ? $row['qty'] : $sourceQty);
            $nameKey = $isScope ? 'nama_scope' : 'nama_material';
            $name = trim((string) (isset($row[$nameKey]) ? $row[$nameKey] : (isset($source[$nameKey]) ? $source[$nameKey] : '')));
            $description = trim((string) (isset($row['deskripsi']) ? $row['deskripsi'] : (isset($source['deskripsi']) ? $source['deskripsi'] : '')));
            $unit = trim((string) (isset($row['satuan']) ? $row['satuan'] : (isset($source['satuan']) ? $source['satuan'] : '')));
            if (($price !== false && ($price < 0 || $price >= 10000000000000000)) || ($isNew && ($price === false || $price < 0)) || $qty === false || $qty <= 0) {
                $errors[($isScope ? 'scope_' : 'material_') . $index] = 'Harga pembanding atau quantity tidak valid.';
                continue;
            }
            if ($name === '' || mb_strlen($name) > 255 || mb_strlen($description) > 5000 || $unit === '' || mb_strlen($unit) > 50) {
                $errors[($isScope ? 'scope_' : 'material_') . $index] = 'Nama, deskripsi, quantity, atau satuan tidak valid.';
                continue;
            }
            if (!$isScope && !$isNew && !empty($source['planning_locked']) && (
                $name !== (string) $source['nama_material'] || $description !== (string) $source['deskripsi'] ||
                abs($qty - (float) $source['qty_kebutuhan']) > 0.000001 || $unit !== (string) $source['satuan']
            )) {
                $errors['material_locked_' . $index] = 'Identitas dan quantity material terkunci karena sudah memiliki reservasi/draft aktif.';
                continue;
            }
            $cleanRow = array(
                'id' => $id, 'is_new' => $isNew,
                'qty' => $qty,
                'harga_pembanding' => $price === false ? null : $price,
                $nameKey => $name,
                'deskripsi' => $description !== '' ? $description : null,
                'satuan' => $unit,
            );
            $purchasingNote = trim((string) (isset($row['keterangan_purchasing']) ? $row['keterangan_purchasing'] : ''));
            if (mb_strlen($purchasingNote) > 5000 || ($price !== false && $purchasingNote === '')) {
                $errors[($isScope ? 'scope_note_' : 'material_note_') . $index] = 'Keterangan Purchasing wajib diisi saat harga pembanding diisi.';
                continue;
            }
            $cleanRow['keterangan_purchasing'] = $purchasingNote !== '' ? $purchasingNote : null;
            if (!$isScope && !$isNew) { $cleanRow['id_brg_nk'] = $source['id_brg_nk']; $cleanRow['reference_type'] = $source['reference_type']; }
            $clean[] = $cleanRow;
        }
        return $clean;
    }

    private function validateDeletedRows($rows, $sourceMap, $type, &$errors)
    {
        if (!is_array($rows) || count($rows) > 500) { $errors['deleted_' . $type] = 'Data penghapusan tidak valid.'; return array(); }
        $clean = array();
        foreach ($rows as $index => $row) {
            $id = isset($row['id']) ? (int) $row['id'] : 0; $reason = trim((string) (isset($row['reason']) ? $row['reason'] : ''));
            if ($id <= 0 || !isset($sourceMap[$id]) || $reason === '' || mb_strlen($reason) > 5000) { $errors['deleted_' . $type . '_' . $index] = 'Alasan penghapusan ' . $type . ' wajib diisi.'; continue; }
            if ($type === 'material' && !empty($sourceMap[$id]['planning_locked'])) { $errors['deleted_material_' . $index] = 'Material yang sudah memiliki reservasi/draft aktif tidak dapat dihapus.'; continue; }
            $clean[] = array('id' => $id, 'reason' => $reason);
        }
        return $clean;
    }

    private function statePayload($request, $context)
    {
        $history = $this->M_PojasaWorkflow->get_history($request->kd_po_jasa);
        $revisionNo = $this->M_PojasaWorkflow->effective_revision($request);
        $materials = $this->M_PojasaWorkflow->get_materials($request->kd_po_jasa, $revisionNo);
        $draftReviewCount = 0;
        $draftReviewMaterials = array();
        foreach ($materials as $material) {
            if ((float) $material['remaining_need'] > 0.000001) {
                $draftReviewCount++;
                $draftReviewMaterials[] = (string) $material['nama_material'];
            }
        }
        $purchasingTotals = $this->M_PojasaWorkflow->get_purchasing_totals($request->kd_po_jasa, $revisionNo);
        $isFinalApprovalView = in_array($context['role'], array('KADEP', 'DIREKTUR_OPERASIONAL', 'DIREKTUR'), true);
        $isPurchasingRevision = in_array($request->status, array('REVISI_PURCHASING_KADEP', 'REVISI_PURCHASING_DIROPS', 'REVISI_PURCHASING_DIRUT'), true);
        $revisionReady = !$isPurchasingRevision || (
            !empty($request->edit_revision_no)
            && !empty($request->purchasing_revision_saved_at)
            && $request->purchasing_revision_status === $request->status
        );
        $purchasingRevisionNotes = array();
        $reviewSegments = array('purchasing' => false, 'scope' => false, 'material' => false);
        $reviewPrefix = $isPurchasingRevision ? 'REVISI_PURCHASING_' : 'REVIEW_PURCHASING_';
        foreach ($history['logs'] as $log) {
            if ($log['event_type'] === 'REVISI_PURCHASING_DISIMPAN' || $log['event_type'] === 'REVISI_PURCHASING_PURCHASING_DISIMPAN') {
                $purchasingRevisionNotes[] = $log;
            }
            foreach (array('purchasing', 'scope', 'material') as $segment) {
                if ($log['event_type'] === $reviewPrefix . strtoupper($segment) . '_DISIMPAN'
                    && (int) $log['revision_no'] === $revisionNo) {
                    $reviewSegments[$segment] = true;
                }
            }
        }
        if (!$isPurchasingRevision && !empty($request->reviewed_at_purchasing)
            && !array_filter($reviewSegments)) {
            $reviewSegments = array('purchasing' => true, 'scope' => true, 'material' => true);
        }
        return array(
            'request' => array(
                'kd_po_jasa' => $request->kd_po_jasa, 'status' => $request->status,
                'status_version' => (int) $request->status_version, 'revision_no' => (int) $request->revision_no,
                'nm_user' => $request->nm_user, 'departemen' => $request->departemen,
                'tujuan_pekerjaan' => $request->tujuan_pekerjaan,
                'purchasing_note' => $request->purchasing_note,
                'reviewed_at_purchasing' => $request->reviewed_at_purchasing,
                'purchasing_revision_saved_at' => $request->purchasing_revision_saved_at,
                'purchasing_revision_status' => $request->purchasing_revision_status,
                'vendor' => $request->nama_vendor ?: $request->vendor_usulan,
                'estimasi_total' => (float) $request->estimasi_total,
                'estimasi_purchasing' => $request->estimasi_purchasing === null ? null : (float) $request->estimasi_purchasing,
                'estimasi_final' => (float) $purchasingTotals['grand_total'],
                'estimasi_final_jasa' => (float) $purchasingTotals['jasa'],
                'estimasi_final_material' => (float) $purchasingTotals['material'],
                'no_spk' => $request->no_spk,
            ),
            'allowed_actions' => $this->M_PojasaWorkflow->allowed_actions($context, $request),
            'can_edit_purchasing' => $this->M_PojasaWorkflow->can_edit_purchasing($context, $request),
            'draft_review_required' => $context['role'] === 'PURCHASING'
                && $request->status === 'MENUNGGU_PURCHASING_AWAL'
                && $draftReviewCount > 0,
            'draft_review_count' => $draftReviewCount,
            'draft_review_materials' => $draftReviewMaterials,
            'review_segments' => $reviewSegments,
            'revision_ready' => $revisionReady,
            'final_approval_view' => $isFinalApprovalView,
            'detail_url' => base_url('pojasa/workflow/detail/' . $request->kd_po_jasa),
            'approvals' => $history['approvals'], 'revisions' => $history['revisions'],
            'purchasing_revision_notes' => $purchasingRevisionNotes,
            'purchasing_totals' => $purchasingTotals,
            'materials' => $materials,
            'purchase_drafts' => $this->M_PojasaWorkflow->get_active_purchase_drafts($request->kd_po_jasa, $revisionNo),
            'csrf_token' => pojasa_csrf_token(),
        );
    }

    private function datatableRow($row)
    {
        $request = (object) $row;
        $actions = $this->M_PojasaWorkflow->allowed_actions($this->context(), $request);
        $process = $actions ? '<button type="button" class="btn btn-success btn-sm btn-workflow-process" data-code="' . htmlspecialchars($row['kd_po_jasa'], ENT_QUOTES, 'UTF-8') . '"><i class="fas fa-tasks"></i></button>' : '';
        return array(
            'tgl_request' => $row['tgl_request'],
            'kd_po_jasa' => htmlspecialchars($row['kd_po_jasa'], ENT_QUOTES, 'UTF-8'),
            'pic' => htmlspecialchars($row['nm_user'], ENT_QUOTES, 'UTF-8'),
            'departemen' => htmlspecialchars($row['departemen'], ENT_QUOTES, 'UTF-8'),
            'vendor' => htmlspecialchars((string) $row['nama_vendor_tampil'], ENT_QUOTES, 'UTF-8'),
            'total' => 'Rp ' . number_format((float) $row['estimasi_total'], 0, ',', '.'),
            'status' => '<span class="badge badge-' . $this->statusColor($row['status']) . '">' . htmlspecialchars($row['status'], ENT_QUOTES, 'UTF-8') . '</span>',
            'actions' => '<a class="btn btn-info btn-sm mr-1" href="' . base_url('pojasa/workflow/detail/' . rawurlencode($row['kd_po_jasa'])) . '"><i class="fas fa-eye"></i></a>' . $process,
        );
    }

    private function render($view, $data, $script = null)
    {
        $this->load->view('partial/header', $data);
        $this->load->view('partial/sidebar', $data);
        $this->load->view($view, $data);
        $this->load->view('partial/footer', $data);
        if ($script) {
            $this->load->view($script, $data);
        }
    }

    private function baseData($title)
    {
        return array(
            'title' => $title, 'iduser' => $this->session->userdata('id'),
            'kduser' => $this->session->userdata('kode'), 'nmuser' => $this->session->userdata('nama_user'),
            'depuser' => pojasa_normalize_department($this->session->userdata('departemen')),
            'lv' => $this->session->userdata('lv'), 'csrf_token' => pojasa_csrf_token(),
            'workflow_role' => $this->context()['role'],
        );
    }

    private function context()
    {
        $context = pojasa_session_context();
        $context['ip_address'] = $this->input->ip_address();
        $context['user_agent'] = substr((string) $this->input->user_agent(), 0, 255);
        return $context;
    }

    private function guardRole()
    {
        return $this->session->userdata('status') === 'is_login'
            && (int) $this->session->userdata('id') > 0
            && $this->M_PojasaWorkflow->role_allowed($this->context());
    }

    private function guardPage()
    {
        if (!$this->guardRole()) {
            show_error('Akses workflow PO Jasa ditolak.', 403);
            return false;
        }
        if (!$this->M_PojasaWorkflow->schema_ready()) {
            show_error('Schema workflow PO Jasa Fase 4 belum tersedia.', 503);
            return false;
        }
        return true;
    }

    private function guardAjax($method, $csrf = false)
    {
        if (!$this->input->is_ajax_request() && strpos((string) $this->input->get_request_header('Accept', true), 'application/json') === false) {
            $this->json(false, 'AJAX_REQUIRED', 'Endpoint hanya menerima AJAX.', array(), 400);
            return false;
        }
        if (!$this->guardRole()) {
            $this->json(false, 'FORBIDDEN', 'Akses workflow ditolak.', array(), 403);
            return false;
        }
        if (strtoupper($this->input->method(true)) !== strtoupper($method)) {
            $this->json(false, 'METHOD_NOT_ALLOWED', 'Metode request tidak diperbolehkan.', array(), 405);
            return false;
        }
        if (!$this->M_PojasaWorkflow->schema_ready()) {
            $this->json(false, 'SCHEMA_NOT_READY', 'Schema workflow belum tersedia.', array(), 503);
            return false;
        }
        if ($csrf) {
            $token = (string) $this->input->get_request_header('X-POJASA-CSRF', true);
            if ($token === '') {
                $token = (string) $this->input->post('csrf_token', true);
            }
            if (!pojasa_csrf_is_valid($token)) {
                $this->json(false, 'CSRF_INVALID', 'Token keamanan tidak valid.', array(), 403);
                return false;
            }
        }
        return true;
    }

    private function businessError($code)
    {
        $map = array(
            'FORBIDDEN' => array(403, 'Akses terhadap request ditolak.'),
            'CONCURRENT_UPDATE' => array(409, 'Request sudah berubah. Muat status terbaru.'),
            'NOTE_REQUIRED' => array(422, 'Catatan revisi/perbaikan wajib diisi.'),
            'DATABASE_ERROR' => array(500, 'Transaksi database gagal dan dibatalkan.'),
        );
        $item = isset($map[$code]) ? $map[$code] : array(500, 'Review Purchasing gagal diproses.');
        return $this->json(false, $code, $item[1], array(), $item[0]);
    }

    private function json($success, $code, $message, $data = array(), $httpStatus = 200, $errors = array())
    {
        return $this->output->set_status_header($httpStatus)->set_content_type('application/json')->set_output(json_encode(array(
            'success' => (bool) $success, 'status' => (bool) $success, 'code' => $code,
            'message' => $message, 'data' => $data, 'errors' => $errors ? $errors : new stdClass(),
        )));
    }

    private function normalizeCode($value)
    {
        return strtoupper(trim((string) $value));
    }

    private function validDate($value)
    {
        if ($value === '') {
            return false;
        }
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            return false;
        }
        $parts = explode('-', $value);
        return checkdate((int) $parts[1], (int) $parts[2], (int) $parts[0]);
    }

    private function parseAmount($value)
    {
        $value = str_replace(' ', '', trim((string) $value));
        if ($value === '') {
            return false;
        }
        if (strpos($value, ',') !== false) {
            $value = str_replace('.', '', $value);
            $value = str_replace(',', '.', $value);
        }
        return is_numeric($value) && is_finite((float) $value) ? round((float) $value, 2) : false;
    }

    /** Harga pembanding ditampilkan dengan pemisah ribuan Indonesia, mis. 150.000. */
    private function parseCurrencyAmount($value)
    {
        $value = str_replace(' ', '', trim((string) $value));
        if ($value === '') return false;
        if (preg_match('/^\d{1,3}(?:\.\d{3})+$/', $value)) {
            $value = str_replace('.', '', $value);
        }
        return $this->parseAmount($value);
    }

    private function statusColor($status)
    {
        if (strpos($status, 'DITOLAK_') === 0) {
            return 'danger';
        }
        if (strpos($status, 'REVISI_') === 0 || $status === 'PENDING_KADEP') {
            return 'warning';
        }
        if ($status === 'SPK_TERBIT') {
            return 'success';
        }
        return 'info';
    }
}
