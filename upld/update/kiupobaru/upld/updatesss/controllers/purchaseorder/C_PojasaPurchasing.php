<?php
defined('BASEPATH') or exit('No direct script access allowed');

class C_PojasaPurchasing extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('PO/M_PojasaPurchasing');
        $this->load->model('PO/M_PojasaIntegration');
        $this->load->model('PO/M_PojasaSpkChange');
        $this->load->model('PO/M_PojasaPickup');
        $this->load->helper(array('pojasa_authorization', 'pojasa_status', 'pojasa_document'));
    }

    public function detail($requestCode)
    {
        if (!$this->guardPage(false)) {
            return;
        }
        $context = $this->context();
        $request = $this->M_PojasaPurchasing->get_request($this->code($requestCode), $context);
        if (!$request) {
            show_404();
            return;
        }
        $data = $this->baseData('Purchasing PO Jasa');
        $data['request'] = $request;
        $data['purchasing_state'] = $this->statePayload($request, $context);
        $data['can_manage'] = $this->M_PojasaPurchasing->can_manage($context, $request);
        $this->load->view('partial/header', $data);
        $this->load->view('partial/sidebar', $data);
        $this->load->view('content/po/jasa/purchasing/detail', $data);
        $this->load->view('partial/footer', $data);
        $this->load->view('content/po/jasa/purchasing/purchasing_js', $data);
    }

    public function state($requestCode)
    {
        if (!$this->guardAjax('GET', false, false)) {
            return;
        }
        $context = $this->context();
        $request = $this->M_PojasaPurchasing->get_request($this->code($requestCode), $context);
        if (!$request) {
            return $this->json(false, 'NOT_FOUND', 'Request tidak ditemukan.', array(), 404);
        }
        return $this->json(true, 'OK', 'Data Purchasing dimuat.', $this->statePayload($request, $context));
    }

    public function pickup_prepare()
    {
        if (!$this->guardAjax('POST', true, true)) return;
        $code = $this->code($this->input->post('kd_po_jasa', true));
        $result = $this->M_PojasaPickup->prepare($this->context(), $code, trim((string)$this->input->post('note', true)));
        return $this->json($result['success'], $result['code'], $result['success'] ? 'Barang ditandai siap diambil.' : 'Permintaan belum siap diproses.', array('pickup' => $this->M_PojasaPickup->state($code)), $result['success'] ? 200 : 403);
    }

    public function vendor_search()
    {
        if (!$this->guardAjax('GET', false, true)) {
            return;
        }
        $term = trim((string) $this->input->get('q', true));
        if (mb_strlen($term) > 100) {
            return $this->json(false, 'VALIDATION_ERROR', 'Kata pencarian terlalu panjang.', array(), 422);
        }
        return $this->json(true, 'OK', 'Vendor ditemukan.', array('items' => $this->M_PojasaPurchasing->search_vendors($term), 'csrf_token' => pojasa_csrf_token()));
    }

    public function vendor_create()
    {
        if (!$this->guardAjax('POST', true, true)) {
            return;
        }
        return $this->json(false, 'MASTER_VENDOR_WORKFLOW_REQUIRED', 'Vendor manual penawaran tidak didaftarkan ke master. Gunakan field vendor manual pada modal penawaran.', array(), 409);
    }

    public function comparison_save()
    {
        if (!$this->guardAjax('POST', true, true)) { return; }
        $requestCode = $this->code($this->input->post('kd_po_jasa', true));
        $sourceType = strtoupper(trim((string) $this->input->post('source_type', true)));
        $sourceCode = strtoupper(trim((string) $this->input->post('source_code', true)));
        $manualVendorName = trim((string) $this->input->post('manual_vendor_name', true));
        if ($sourceType === '' && $sourceCode === '') {
            $sourceType = 'MANUAL';
        }
        $quotationDate = trim((string) $this->input->post('quotation_date', true));
        $amount = $this->amount($this->input->post('amount', true));
        $leadRaw = trim((string) $this->input->post('lead_time_days', true));
        $data = array(
            'quotation_no' => trim((string) $this->input->post('quotation_no', true)),
            'quotation_date' => $quotationDate,
            'amount' => $amount,
            'lead_time_days' => $leadRaw === '' ? null : (int) $leadRaw,
            'offer_detail' => trim((string) $this->input->post('offer_detail', true)),
            'manual_vendor_name' => $manualVendorName,
        );
        $errors = array();
        if (!preg_match('/^[A-Z0-9_-]{5,30}$/', $requestCode)) { $errors['kd_po_jasa'] = 'Kode request tidak valid.'; }
        if (!in_array($sourceType, array('MASTER', 'JASA', 'MANUAL'), true)) { $errors['vendor'] = 'Sumber vendor tidak valid.'; }
        if (in_array($sourceType, array('MASTER', 'JASA'), true) && !preg_match('/^[A-Z0-9._-]{1,25}$/', $sourceCode)) { $errors['vendor'] = 'Pilih vendor dari hasil pencarian.'; }
        if ($sourceType === 'MANUAL' && mb_strlen($manualVendorName) > 180) { $errors['manual_vendor_name'] = 'Nama vendor manual maksimal 180 karakter.'; }
        if ($amount === false || $amount <= 0 || $amount >= 10000000000000000) { $errors['amount'] = 'Nilai penawaran harus lebih dari 0.'; }
        if ($quotationDate !== '' && !$this->validDate($quotationDate)) { $errors['quotation_date'] = 'Tanggal penawaran tidak valid.'; }
        if (mb_strlen($data['quotation_no']) > 100 || mb_strlen($data['offer_detail']) > 5000) { $errors['offer_detail'] = 'Nomor/detail penawaran terlalu panjang.'; }
        if ($data['lead_time_days'] !== null && ($data['lead_time_days'] < 0 || $data['lead_time_days'] > 3650)) { $errors['lead_time_days'] = 'Lead time tidak valid.'; }
        $document = null;
        $storedPath = null;
        if (!empty($_FILES['quotation_file']['name'])) {
            $validation = pojasa_validate_document_file($_FILES['quotation_file']);
            if (!$validation['success']) { $errors['quotation_file'] = $validation['message']; }
            if (!$errors) {
                $upload = $this->storeDocument($_FILES['quotation_file'], $requestCode, $validation);
                if (!$upload['success']) { $errors['quotation_file'] = $upload['message']; }
                else { $document = $upload['document']; $storedPath = $upload['path']; }
            }
        }
        if ($errors) { return $this->json(false, 'VALIDATION_ERROR', 'Periksa data penawaran.', array(), 422, $errors); }
        $result = $this->M_PojasaPurchasing->save_comparison($this->context(), $requestCode, $sourceType, $sourceCode, $data, $document);
        if (!$result['success']) {
            if ($storedPath) { $this->removeUpload($storedPath); }
            return $this->modelError($result);
        }
        return $this->freshStateResponse($requestCode, $result['code'], 'Penawaran vendor berhasil disimpan.');
    }

    public function comparison_delete()
    {
        if (!$this->guardAjax('POST', true, true)) { return; }
        $requestCode = $this->code($this->input->post('kd_po_jasa', true));
        $id = (int) $this->input->post('id_vendor_comparison', true);
        if ($id <= 0) { return $this->json(false, 'VALIDATION_ERROR', 'Penawaran tidak valid.', array(), 422); }
        $result = $this->M_PojasaPurchasing->delete_comparison($this->context(), $requestCode, $id);
        if (!$result['success']) { return $this->modelError($result); }
        return $this->freshStateResponse($requestCode, 'DELETED', 'Penawaran dihapus.');
    }

    public function comparison_select()
    {
        if (!$this->guardAjax('POST', true, true)) { return; }
        $requestCode = $this->code($this->input->post('kd_po_jasa', true));
        $id = (int) $this->input->post('id_vendor_comparison', true);
        $reason = trim((string) $this->input->post('selection_reason', true));
        if ($id <= 0 || $reason === '' || mb_strlen($reason) > 5000) {
            return $this->json(false, 'VALIDATION_ERROR', 'Alasan pemilihan vendor wajib diisi, maksimal 5.000 karakter.', array(), 422);
        }
        $result = $this->M_PojasaPurchasing->select_comparison($this->context(), $requestCode, $id, $reason);
        if (!$result['success']) { return $this->modelError($result); }
        return $this->freshStateResponse($requestCode, 'SELECTED', 'Vendor terpilih berhasil ditetapkan.');
    }

    public function stock_search()
    {
        if (!$this->guardAjax('GET', false, true)) { return; }
        $term = trim((string) $this->input->get('q', true));
        if (mb_strlen($term) > 100) { return $this->json(false, 'VALIDATION_ERROR', 'Kata pencarian terlalu panjang.', array(), 422); }
        return $this->json(true, 'OK', 'Stok ditemukan.', array('items' => $this->M_PojasaPurchasing->search_stock($term), 'csrf_token' => pojasa_csrf_token()));
    }

    public function stock_allocate()
    {
        if (!$this->guardAjax('POST', true, true)) { return; }
        $requestCode = $this->code($this->input->post('kd_po_jasa', true));
        $materialId = (int) $this->input->post('id_material', true);
        $stockId = (int) $this->input->post('id_brg_nk', true);
        $quantity = $this->amount($this->input->post('qty', true));
        $token = strtolower(trim((string) $this->input->post('idempotency_token', true)));
        if ($materialId <= 0 || $stockId <= 0 || $quantity === false || $quantity <= 0 || !$this->validUuid($token)) {
            return $this->json(false, 'VALIDATION_ERROR', 'Material, barang stok, quantity, dan token wajib valid.', array(), 422);
        }
        $result = $this->M_PojasaPurchasing->allocate_stock($this->context(), $requestCode, $materialId, $stockId, $quantity, $token);
        if (!$result['success']) { return $this->modelError($result); }
        return $this->freshStateResponse($requestCode, $result['code'], 'Stok berhasil direservasi tanpa mengurangi stok fisik.');
    }

    public function stock_release()
    {
        if (!$this->guardAjax('POST', true, true)) { return; }
        $requestCode = $this->code($this->input->post('kd_po_jasa', true));
        $allocationId = (int) $this->input->post('id_allocation', true);
        $reason = trim((string) $this->input->post('reason', true));
        $token = strtolower(trim((string) $this->input->post('idempotency_token', true)));
        if ($allocationId <= 0 || $reason === '' || mb_strlen($reason) > 5000 || !$this->validUuid($token)) {
            return $this->json(false, 'VALIDATION_ERROR', 'Alokasi, alasan release, dan token wajib valid.', array(), 422);
        }
        $result = $this->M_PojasaPurchasing->release_stock($this->context(), $requestCode, $allocationId, $reason, $token);
        if (!$result['success']) { return $this->modelError($result); }
        return $this->freshStateResponse($requestCode, $result['code'], 'Reservasi stok berhasil dilepas.');
    }

    public function draft_save()
    {
        if (!$this->guardAjax('POST', true, true)) { return; }
        $requestCode = $this->code($this->input->post('kd_po_jasa', true));
        $materialId = (int) $this->input->post('id_material', true);
        $qty = $this->amount($this->input->post('qty', true));
        $price = $this->amount($this->input->post('harga_estimasi', true));
        $vendorId = (int) $this->input->post('id_vendor_jasa', true);
        $note = trim((string) $this->input->post('keterangan', true));
        $token = strtolower(trim((string) $this->input->post('idempotency_token', true)));
        if ($materialId <= 0 || $qty === false || $qty <= 0 || $price === false || $price < 0 || mb_strlen($note) > 5000 || !$this->validUuid($token)) {
            return $this->json(false, 'VALIDATION_ERROR', 'Data draft pembelian tidak valid.', array(), 422);
        }
        if ($vendorId > 0 && (int) $this->db->where(array('id_vendor_jasa' => $vendorId, 'status_vendor' => 'AKTIF'))->count_all_results('tbpo_jasa_vendor') !== 1) {
            return $this->json(false, 'VALIDATION_ERROR', 'Vendor draft tidak valid.', array(), 422);
        }
        $result = $this->M_PojasaPurchasing->save_purchase_draft($this->context(), $requestCode, $materialId, array(
            'qty' => $qty, 'harga_estimasi' => $price, 'id_vendor_jasa' => $vendorId > 0 ? $vendorId : null, 'keterangan' => $note ?: null,
        ), $token);
        if (!$result['success']) { return $this->modelError($result); }
        return $this->freshStateResponse($requestCode, $result['code'], 'Draft pembelian berhasil disimpan.');
    }

    public function draft_cancel()
    {
        if (!$this->guardAjax('POST', true, true)) { return; }
        $requestCode = $this->code($this->input->post('kd_po_jasa', true));
        $draftId = (int) $this->input->post('id_draft_pembelian', true);
        $reason = trim((string) $this->input->post('reason', true));
        if ($draftId <= 0 || $reason === '' || mb_strlen($reason) > 5000) {
            return $this->json(false, 'VALIDATION_ERROR', 'Draft dan alasan pembatalan wajib valid.', array(), 422);
        }
        $result = $this->M_PojasaPurchasing->cancel_purchase_draft($this->context(), $requestCode, $draftId, $reason);
        if (!$result['success']) { return $this->modelError($result); }
        return $this->freshStateResponse($requestCode, 'CANCELLED', 'Draft pembelian berhasil dihapus.');
    }

    public function purchase_submit()
    {
        if (!$this->guardAjax('POST', true, true)) return;
        $requestCode = $this->code($this->input->post('kd_po_jasa', true));
        $token = strtolower(trim((string) $this->input->post('idempotency_token', true)));
        if (!$this->validUuid($token)) return $this->json(false, 'VALIDATION_ERROR', 'Token aksi tidak valid.', array(), 422);
        $result = $this->M_PojasaIntegration->create_purchase_submission($this->context(), $requestCode, $token);
        if (!$result['success']) return $this->modelError($result);
        return $this->freshStateResponse($requestCode, $result['code'], $result['code'] === 'NO_ACTIVE_DRAFT' ? 'Tidak ada draft aktif untuk diajukan.' : 'PO Pembelian berhasil disinkronkan.');
    }

    public function purchase_delete()
    {
        if (!$this->guardAjax('POST', true, true)) return;
        $requestCode = $this->code($this->input->post('kd_po_jasa', true));
        $reason = trim((string) $this->input->post('reason', true));
        if ($reason === '' || mb_strlen($reason) > 5000) {
            return $this->json(false, 'VALIDATION_ERROR', 'Alasan penghapusan PO Pembelian wajib diisi.', array(), 422);
        }
        $result = $this->M_PojasaIntegration->delete_purchase_submission($this->context(), $requestCode, $reason);
        if (!$result['success']) return $this->modelError($result);
        return $this->freshStateResponse($requestCode, $result['code'], 'PO Pembelian dihapus; draft material dikembalikan untuk diperbaiki atau diajukan ulang.');
    }

    public function dev_purchase_submit()
    {
        if (!$this->guardAjax('POST', true, true)) return;
        if (!(bool) $this->config->item('pojasa_enable_dev_purchase_button')) {
            return $this->json(false, 'FORBIDDEN', 'Fitur DEV generate PO Pembelian sedang dinonaktifkan.', array(), 403);
        }
        $context = $this->context();
        if (!in_array($context['role'], array('ADMIN', 'PURCHASING'), true)) {
            return $this->json(false, 'FORBIDDEN', 'Fitur DEV hanya untuk Admin atau Purchasing.', array(), 403);
        }
        $requestCode = $this->code($this->input->post('kd_po_jasa', true));
        $token = strtolower(trim((string) $this->input->post('idempotency_token', true)));
        if (!$this->validUuid($token)) return $this->json(false, 'VALIDATION_ERROR', 'Token aksi tidak valid.', array(), 422);
        $result = $this->M_PojasaIntegration->create_purchase_submission($context, $requestCode, $token, true);
        if (!$result['success']) return $this->modelError($result);
        return $this->json(true, $result['code'], $result['code'] === 'NO_ACTIVE_DRAFT' ? 'Tidak ada draft aktif untuk diuji.' : 'PO Pembelian DEV berhasil dibuat tanpa menunggu SPK.', array('kd_po_nk' => $result['kd_po_nk'], 'id_po_nk' => isset($result['id_po_nk']) ? $result['id_po_nk'] : null, 'csrf_token' => pojasa_csrf_token()));
    }

    public function purchase_receive()
    {
        if (!$this->guardAjax('POST', true, true)) return;
        $requestCode = $this->code($this->input->post('kd_po_jasa', true));
        $detailId = (int) $this->input->post('id_detail_po_nk', true);
        $qty = $this->amount($this->input->post('qty_received', true));
        $price = $this->amount($this->input->post('unit_price_actual', true));
        $date = trim((string) $this->input->post('on_hand_date', true));
        $document = trim((string) $this->input->post('document_reference', true));
        $token = strtolower(trim((string) $this->input->post('idempotency_token', true)));
        if ($detailId <= 0 || $qty === false || $qty <= 0 || $price === false || $price < 0 || !$this->validDate($date) || mb_strlen($document) > 255 || !$this->validUuid($token)) {
            return $this->json(false, 'VALIDATION_ERROR', 'Detail, quantity, harga aktual, tanggal ON_HAND, dan token wajib valid.', array(), 422);
        }
        $result = $this->M_PojasaIntegration->receive_purchase($this->context(), $requestCode, $detailId, $qty, $price, $date, $document, $token);
        if (!$result['success']) return $this->modelError($result);
        return $this->freshStateResponse($requestCode, $result['code'], 'Penerimaan, biaya aktual, dan transaksi stok masuk (akun 11511) berhasil dibentuk otomatis.');
    }

    public function purchase_reverse()
    {
        if (!$this->guardAjax('POST', true, true)) return;
        $requestCode = $this->code($this->input->post('kd_po_jasa', true));
        $receiptId = (int) $this->input->post('id_purchase_receipt', true);
        $reason = trim((string) $this->input->post('reason', true));
        $token = strtolower(trim((string) $this->input->post('idempotency_token', true)));
        if ($receiptId <= 0 || $reason === '' || mb_strlen($reason) > 5000 || !$this->validUuid($token)) return $this->json(false, 'VALIDATION_ERROR', 'Penerimaan, alasan reversal, dan token wajib valid.', array(), 422);
        $result = $this->M_PojasaIntegration->reverse_receipt($this->context(), $requestCode, $receiptId, $reason, $token);
        if (!$result['success']) return $this->modelError($result);
        return $this->freshStateResponse($requestCode, $result['code'], 'Reversal penerimaan, biaya, dan transaksi penyesuaian stok keluar (akun 11514) berhasil dicatat.');
    }

    public function change_submit()
    {
        if (!$this->guardAjax('POST', true, true)) return;
        $requestCode = $this->code($this->input->post('kd_po_jasa', true));
        $type = strtoupper(trim((string) $this->input->post('change_type', true)));
        $reason = trim((string) $this->input->post('reason', true));
        $token = strtolower(trim((string) $this->input->post('idempotency_token', true)));
        $raw = $this->input->post('payload');
        $payload = is_array($raw) ? $raw : json_decode((string) $raw, true);
        if (!in_array($type, array('DATA', 'COST'), true) || !is_array($payload) || $reason === '' || mb_strlen($reason) > 5000 || !$this->validUuid($token)) return $this->json(false, 'VALIDATION_ERROR', 'Jenis, isi, alasan revisi, dan token wajib valid.', array(), 422);
        $result = $this->M_PojasaSpkChange->submit($this->context(), $requestCode, $type, $payload, $reason, $token);
        if (!$result['success']) return $this->modelError($result);
        return $this->freshStateResponse($requestCode, $result['code'], 'Perubahan SPK berhasil diajukan.');
    }

    public function change_decide()
    {
        if (!$this->guardAjax('POST', true, false)) return;
        $requestCode = $this->code($this->input->post('kd_po_jasa', true));
        $changeId = (int) $this->input->post('id_spk_change', true);
        $action = strtoupper(trim((string) $this->input->post('action', true)));
        $note = trim((string) $this->input->post('note', true));
        $token = strtolower(trim((string) $this->input->post('idempotency_token', true)));
        if ($changeId <= 0 || !in_array($action, array('ACC', 'REVISI', 'REJECT'), true) || !$this->validUuid($token) || ($action !== 'ACC' && $note === '') || mb_strlen($note) > 5000) return $this->json(false, 'VALIDATION_ERROR', 'Keputusan revisi tidak valid.', array(), 422);
        $result = $this->M_PojasaSpkChange->decide($this->context(), $requestCode, $changeId, $action, $note, $token);
        if (!$result['success']) return $this->modelError($result);
        return $this->freshStateResponse($requestCode, $result['code'], 'Keputusan perubahan SPK berhasil disimpan.');
    }

    public function spk_revision_submit()
    {
        if (!$this->guardAjax('POST', true, false)) return;
        $requestCode = $this->code($this->input->post('kd_po_jasa', true));
        $start = trim((string) $this->input->post('new_start_date', true));
        $end = trim((string) $this->input->post('new_end_date', true));
        $reason = trim((string) $this->input->post('reason', true));
        $token = strtolower(trim((string) $this->input->post('idempotency_token', true)));
        if (!$this->validDate($start) || !$this->validDate($end) || $end < $start || $reason === '' || mb_strlen($reason) > 5000 || !$this->validUuid($token)) {
            return $this->json(false, 'VALIDATION_ERROR', 'Jadwal baru, alasan, dan token wajib valid; tanggal selesai tidak boleh lebih awal.', array(), 422);
        }
        $result = $this->M_PojasaPurchasing->submit_spk_revision($this->context(), $requestCode, $start, $end, $reason, $token);
        if (!$result['success']) return $this->modelError($result);
        return $this->freshStateResponse($requestCode, $result['code'], 'Revisi SPK berhasil diajukan kepada Direktur.');
    }

    public function spk_revision_decide()
    {
        if (!$this->guardAjax('POST', true, false)) return;
        $requestCode = $this->code($this->input->post('kd_po_jasa', true));
        $revisionId = (int) $this->input->post('id_spk_revision', true);
        $action = strtoupper(trim((string) $this->input->post('action', true)));
        $note = trim((string) $this->input->post('note', true));
        $token = strtolower(trim((string) $this->input->post('idempotency_token', true)));
        if ($revisionId <= 0 || !in_array($action, array('ACC', 'REVISI', 'REJECT'), true) || !$this->validUuid($token)
            || (in_array($action, array('REVISI', 'REJECT'), true) && $note === '') || mb_strlen($note) > 5000) {
            return $this->json(false, 'VALIDATION_ERROR', 'Aksi dan catatan keputusan Revisi SPK tidak valid.', array(), 422);
        }
        $result = $this->M_PojasaPurchasing->decide_spk_revision($this->context(), $requestCode, $revisionId, $action, $note, $token);
        if (!$result['success']) return $this->modelError($result);
        return $this->freshStateResponse($requestCode, $result['code'], 'Keputusan Revisi SPK berhasil disimpan.');
    }

    public function spk_view($requestCode)
    {
        if (!$this->guardPage(false)) { return; }
        $data = $this->spkData($requestCode);
        if (!$data) { show_404(); return; }
        $data += $this->baseData('SPK PO Jasa');
        $data['back_url'] = $this->context()['role'] === 'PIC'
            ? base_url('pojasa/pic/detail/' . rawurlencode($data['spk']->kd_po_jasa))
            : base_url('pojasa/workflow/detail/' . rawurlencode($data['spk']->kd_po_jasa));
        $this->load->view('partial/header', $data);
        $this->load->view('partial/sidebar', $data);
        $this->load->view('content/po/jasa/spk/view', $data);
        $this->load->view('partial/footer', $data);
    }

    public function spk_print($requestCode)
    {
        if (!$this->guardPage(false)) { return; }
        $data = $this->spkData($requestCode);
        if (!$data) { show_404(); return; }
        $data['title'] = 'SPK ' . $data['spk']->no_spk;
        $this->load->view('partial/headerprint', $data);
        $this->load->view('content/po/jasa/spk/document', $data);
        $this->load->view('partial/footerprint', $data);
    }

    public function spk_download($requestCode)
    {
        if (!$this->guardPage(false)) { return; }
        $data = $this->spkData($requestCode);
        if (!$data) { show_404(); return; }
        $html = $this->load->view('content/po/jasa/spk/download', $data, true);
        $name = preg_replace('/[^A-Za-z0-9_-]+/', '_', $data['spk']->no_spk) . '.html';
        $this->output->set_content_type('text/html', 'utf-8')
            ->set_header('X-Content-Type-Options: nosniff')
            ->set_header('Content-Disposition: attachment; filename="' . $name . '"')
            ->set_output($html);
    }

    public function spk_version_view($requestCode, $versionNo)
    {
        if (!$this->guardPage(false)) return;
        $data = $this->spkData($requestCode, (int) $versionNo);
        if (!$data) { show_404(); return; }
        $data += $this->baseData('Arsip SPK PO Jasa');
        $data['back_url'] = base_url('pojasa/purchasing/detail/' . rawurlencode($data['spk']->kd_po_jasa));
        $this->load->view('partial/header', $data); $this->load->view('partial/sidebar', $data);
        $this->load->view('content/po/jasa/spk/view', $data); $this->load->view('partial/footer', $data);
    }

    public function spk_version_print($requestCode, $versionNo)
    {
        if (!$this->guardPage(false)) return;
        $data = $this->spkData($requestCode, (int) $versionNo);
        if (!$data) { show_404(); return; }
        $data['title'] = 'Arsip SPK ' . $data['spk']->no_spk;
        $this->load->view('partial/headerprint', $data);
        $this->load->view('content/po/jasa/spk/document', $data);
        $this->load->view('partial/footerprint', $data);
    }

    private function freshStateResponse($requestCode, $code, $message)
    {
        $context = $this->context();
        $request = $this->M_PojasaPurchasing->get_request($requestCode, $context);
        return $this->json(true, $code, $message, $this->statePayload($request, $context));
    }

    private function statePayload($request, $context)
    {
        $state = $this->M_PojasaPurchasing->state($request);
        $state['request'] = array(
            'kd_po_jasa' => $request->kd_po_jasa, 'status' => $request->status,
            'status_version' => (int) $request->status_version, 'vendor' => $request->nama_vendor ?: $request->vendor_usulan,
            'vendor_selection_reason' => $request->vendor_selection_reason,
            'tgl_mulai_pekerjaan' => $request->tgl_mulai_pekerjaan,
            'tgl_selesai_pekerjaan' => $request->tgl_selesai_pekerjaan,
        );
        $state['can_manage'] = $this->M_PojasaPurchasing->can_manage($context, $request);
        $state['can_delete_planning'] = in_array($context['role'], array('ADMIN', 'PURCHASING'), true);
        $state['can_submit_spk_revision'] = in_array($context['role'], array('ADMIN', 'PURCHASING'), true) && !empty($request->no_spk);
        $state['can_approve_spk_revision'] = $context['role'] === 'DIREKTUR';
        $state['document_url'] = base_url('pojasa/workflow/document/');
        $state['purchase_integration'] = $this->M_PojasaIntegration->get_submission_state($request->kd_po_jasa);
        $state['can_delete_purchase'] = in_array($context['role'], array('ADMIN', 'PURCHASING'), true)
            && !empty($state['purchase_integration']['submission'])
            && empty($state['purchase_integration']['receipts'])
            && empty($state['purchase_integration']['adjustments']);
        $state['pickup'] = $this->M_PojasaPickup->state($request->kd_po_jasa);
        $state['spk_changes'] = $this->M_PojasaSpkChange->state($context, $request);
        $state['spk_urls'] = array('view' => base_url('pojasa/spk/' . $request->kd_po_jasa), 'print' => base_url('pojasa/spk/' . $request->kd_po_jasa . '/print'), 'download' => base_url('pojasa/spk/' . $request->kd_po_jasa . '/download'));
        $state['csrf_token'] = pojasa_csrf_token();
        return $state;
    }

    private function spkData($requestCode, $versionNo = null)
    {
        $data = $this->M_PojasaPurchasing->get_spk($this->code($requestCode), $this->context(), $versionNo);
        if (!$data) { return null; }
        return array('request' => $data['request'], 'spk' => $data['spk'], 'snapshot' => $data['snapshot']);
    }

    private function storeDocument($file, $requestCode, $validation)
    {
        $dir = 'images/pojasa/PENAWARAN/' . date('Y/m/d') . '/';
        $absolute = FCPATH . $dir;
        if (!is_dir($absolute) && !@mkdir($absolute, 0777, true) && !is_dir($absolute)) {
            return array('success' => false, 'message' => 'Folder penawaran tidak dapat dibuat.');
        }
        $name = bin2hex(random_bytes(16)) . '.' . $validation['extension'];
        $path = $absolute . $name;
        if (!move_uploaded_file($file['tmp_name'], $path)) {
            return array('success' => false, 'message' => 'File penawaran gagal disimpan.');
        }
        return array('success' => true, 'path' => $dir . $name, 'document' => array(
            'jenis_dokumen' => 'PENAWARAN_VENDOR', 'keterangan' => 'Dokumen penawaran vendor',
            'file_path' => $dir . $name, 'file_original' => basename($file['name']),
            'mime_type' => $validation['mime_type'], 'file_size_bytes' => (int) $file['size'], 'sha256' => hash_file('sha256', $path),
        ));
    }

    private function removeUpload($relativePath)
    {
        $base = realpath(FCPATH . 'images/pojasa');
        $file = realpath(FCPATH . ltrim($relativePath, '/'));
        if ($base && $file && strpos($file, $base . DIRECTORY_SEPARATOR) === 0 && is_file($file)) { @unlink($file); }
    }

    private function guardPage($purchasingOnly)
    {
        $context = $this->context();
        $allowed = $purchasingOnly ? in_array($context['role'], array('ADMIN', 'PURCHASING'), true) : $context['role'] !== 'NONE';
        if ($this->session->userdata('status') !== 'is_login' || (int) $context['id_user'] <= 0 || !$allowed) {
            show_error('Akses PO Jasa ditolak.', 403); return false;
        }
        if (!$this->M_PojasaPurchasing->schema_ready()) { show_error('Schema PO Jasa Fase 5 belum tersedia.', 503); return false; }
        return true;
    }

    private function guardAjax($method, $csrf, $purchasingOnly)
    {
        if (!$this->input->is_ajax_request() && strpos((string) $this->input->get_request_header('Accept', true), 'application/json') === false) { $this->json(false, 'AJAX_REQUIRED', 'Endpoint hanya menerima AJAX.', array(), 400); return false; }
        $context = $this->context();
        if ($this->session->userdata('status') !== 'is_login' || (int) $context['id_user'] <= 0 || ($purchasingOnly && !in_array($context['role'], array('ADMIN', 'PURCHASING'), true))) { $this->json(false, 'FORBIDDEN', 'Akses Purchasing ditolak.', array(), 403); return false; }
        if (strtoupper($this->input->method(true)) !== strtoupper($method)) { $this->json(false, 'METHOD_NOT_ALLOWED', 'Metode tidak diperbolehkan.', array(), 405); return false; }
        if (!$this->M_PojasaPurchasing->schema_ready()) { $this->json(false, 'SCHEMA_NOT_READY', 'Schema Fase 5 belum tersedia.', array(), 503); return false; }
        if ($csrf) {
            $token = (string) $this->input->get_request_header('X-POJASA-CSRF', true);
            if ($token === '') { $token = (string) $this->input->post('csrf_token', true); }
            if (!pojasa_csrf_is_valid($token)) { $this->json(false, 'CSRF_INVALID', 'Token keamanan tidak valid.', array(), 403); return false; }
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
        return array('title' => $title, 'iduser' => $this->session->userdata('id'), 'kduser' => $this->session->userdata('kode'), 'nmuser' => $this->session->userdata('nama_user'), 'depuser' => pojasa_normalize_department($this->session->userdata('departemen')), 'lv' => $this->session->userdata('lv'), 'csrf_token' => pojasa_csrf_token());
    }

    private function modelError($result)
    {
        $map = array(
            'FORBIDDEN' => array(403, 'Aksi Purchasing tidak diizinkan.'), 'NOT_FOUND' => array(404, 'Data tidak ditemukan.'),
            'VENDOR_NOT_FOUND' => array(422, 'Vendor tidak ditemukan.'), 'SELECTED_VENDOR_LOCKED' => array(409, 'Vendor terpilih tidak dapat dihapus.'),
            'QUANTITY_INVALID' => array(422, 'Quantity melebihi sisa kebutuhan material.'), 'STOCK_CONFLICT' => array(409, 'Stok tersedia telah berubah atau tidak mencukupi.'),
            'ALLOCATION_INVALID' => array(409, 'Reservasi tidak aktif atau sudah dilepas.'), 'DRAFT_QUANTITY_INVALID' => array(422, 'Draft hanya boleh dibuat sebesar kekurangan stok.'),
            'IDEMPOTENCY_CONFLICT' => array(409, 'Token aksi sudah digunakan untuk data yang berbeda.'),
            'NUMBER_ERROR' => array(500, 'Nomor vendor tidak dapat dibuat.'), 'DATABASE_ERROR' => array(500, 'Transaksi database gagal dan dibatalkan.'),
            'SCHEMA_NOT_READY' => array(503, 'Migration Revisi SPK belum diterapkan.'), 'REVISION_PENDING' => array(409, 'Masih ada Revisi SPK yang menunggu keputusan Direktur.'),
            'REVISION_NOT_PENDING' => array(409, 'Pengajuan Revisi SPK tidak lagi menunggu keputusan.'), 'CONCURRENT_UPDATE' => array(409, 'Data berubah bersamaan; muat ulang data.'),
            'SCHEDULE_INVALID' => array(422, 'Jadwal Revisi SPK tidak valid.'),
            'SPK_NOT_FOUND' => array(409, 'SPK belum terbit.'), 'LEGACY_INTEGER_REQUIRED' => array(422, 'Quantity dan harga harus bilangan bulat agar kompatibel dengan PO Pembelian lama.'),
            'PURCHASE_DELETE_NOT_ALLOWED' => array(409, 'PO hanya dapat dihapus saat masih berstatus PROSES PEMBELIAN.'),
            'PURCHASE_DELETE_RECEIPT_EXISTS' => array(409, 'PO tidak dapat dihapus karena sudah memiliki penerimaan. Lakukan reversal penerimaan sesuai prosedur.'),
            'PURCHASE_DELETE_ADJUSTMENT_EXISTS' => array(409, 'PO tidak dapat dihapus karena sudah memiliki catatan perubahan/penyesuaian.'),
            'KADEP_NOT_FOUND' => array(409, 'KADEP untuk departemen pengaju belum tersedia; PO Pembelian otomatis tidak dapat dibuat.'),
            'PURCHASE_DETAIL_INVALID' => array(409, 'Detail PO Pembelian tidak valid.'), 'MANUAL_MASTER_REQUIRED' => array(409, 'Barang manual harus ditautkan ke master barang sebelum penerimaan dicatat.'), 'RECEIPT_QUANTITY_INVALID' => array(422, 'Quantity penerimaan melebihi sisa yang belum diterima.'),
            'RECEIPT_NOT_REVERSIBLE' => array(409, 'Penerimaan tidak dapat dibalik atau sudah pernah direversal.'),
            'REVISION_INVALID' => array(422, 'Isi perubahan SPK tidak valid.'), 'REVISION_ITEM_INVALID' => array(422, 'Baris perubahan SPK tidak valid.'),
            'NO_CHANGES' => array(422, 'Tidak ada perubahan yang diajukan.'), 'REVISION_COST_INVALID' => array(422, 'Biaya overbudget yang diajukan tidak valid.'),
            'APPROVED_BUDGET_TOO_LOW' => array(422, 'Anggaran baru tidak boleh lebih rendah dari biaya aktual.'), 'SPK_VERSION_CONFLICT' => array(409, 'Versi SPK sudah berubah; muat ulang data.'),
        );
        $item = isset($map[$result['code']]) ? $map[$result['code']] : array(500, 'Aksi Purchasing gagal.');
        return $this->json(false, $result['code'], $item[1], isset($result['available']) ? array('available_stock' => $result['available']) : array(), $item[0]);
    }

    private function json($success, $code, $message, $data = array(), $http = 200, $errors = array())
    {
        return $this->output->set_status_header($http)->set_content_type('application/json')->set_output(json_encode(array('success' => (bool) $success, 'status' => (bool) $success, 'code' => $code, 'message' => $message, 'data' => $data, 'errors' => $errors ?: new stdClass())));
    }

    private function code($value) { return strtoupper(trim((string) $value)); }
    private function validUuid($value) { return (bool) preg_match('/^[a-f0-9]{8}-[a-f0-9]{4}-[1-5][a-f0-9]{3}-[89ab][a-f0-9]{3}-[a-f0-9]{12}$/i', $value); }
    private function validDate($value) { if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) { return false; } $p = explode('-', $value); return checkdate((int) $p[1], (int) $p[2], (int) $p[0]); }
    private function amount($value) { $value = str_replace(' ', '', trim((string) $value)); if ($value === '') { return false; } if (strpos($value, ',') !== false) { $value = str_replace('.', '', $value); $value = str_replace(',', '.', $value); } return is_numeric($value) && is_finite((float) $value) ? round((float) $value, 2) : false; }
}
