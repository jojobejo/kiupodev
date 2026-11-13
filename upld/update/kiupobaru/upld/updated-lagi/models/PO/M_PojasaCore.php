<?php
defined('BASEPATH') or exit('No direct script access allowed');

class M_PojasaCore extends CI_Model
{
    private $requiredTables = array(
        'tbpo_jasa_request',
        'tbpo_jasa_scope',
        'tbpo_jasa_material',
        'tbpo_jasa_dokumen',
        'tbpo_jasa_vendor',
        'tbpo_jasa_stock_allocation',
        'tbpo_jasa_draft_pembelian',
        'tbpo_jasa_approval',
        'tbpo_jasa_revisi',
        'tbpo_jasa_spk',
        'tbpo_jasa_progress',
        'tbpo_jasa_biaya_aktual',
        'tbpo_jasa_log_aktivitas',
        'tbpo_jasa_notifikasi',
        'tbpo_jasa_number_counter',
        'tbpo_jasa_stock_receipt',
        'tbpo_jasa_stock_receipt_detail',
    );

    public function tables_ready()
    {
        return count($this->missing_tables()) === 0;
    }

    public function missing_tables()
    {
        $missing = array();
        foreach ($this->requiredTables as $table) {
            if (!$this->db->table_exists($table)) {
                $missing[] = $table;
            }
        }

        return $missing;
    }

    public function get_request($requestCode)
    {
        $this->db->from('tbpo_jasa_request');
        $this->db->where('kd_po_jasa', trim((string) $requestCode));

        return $this->db->get()->row();
    }

    public function generate_request_number($date = null)
    {
        $date = $this->normalizeDate($date);
        $prefix = 'PJASA' . date('dmy', strtotime($date));

        return $this->generateAtomicNumber('REQUEST', date('Ymd', strtotime($date)), $prefix, 4, array(
            array('table' => 'tbpo_jasa_request', 'column' => 'kd_po_jasa'),
        ));
    }

    public function generate_spk_number($date = null)
    {
        $date = $this->normalizeDate($date);
        $prefix = 'SPKJ' . date('dmy', strtotime($date));

        return $this->generateAtomicNumber('SPK', date('Ymd', strtotime($date)), $prefix, 4, array(
            array('table' => 'tbpo_jasa_request', 'column' => 'no_spk'),
            array('table' => 'tbpo_jasa_spk', 'column' => 'no_spk'),
        ));
    }

    public function generate_receipt_number($date = null)
    {
        $date = $this->normalizeDate($date);

        return $this->generateAtomicNumber('RECEIPT', date('Ymd', strtotime($date)), 'TRMJ-' . date('Ymd', strtotime($date)) . '-', 4);
    }

    public function generateAtomicNumber($counterType, $periodKey, $prefix, $padding, $seedSources = array())
    {
        $ownsTransaction = !$this->db->trans_active();
        if ($ownsTransaction) {
            $this->db->trans_begin();
        }

        $seed = $this->findExistingSequence($prefix, $seedSources);
        $this->db->query(
            'INSERT INTO tbpo_jasa_number_counter (counter_type, period_key, last_number, updated_at) '
            . 'VALUES (?, ?, LAST_INSERT_ID(?), NOW()) '
            . 'ON DUPLICATE KEY UPDATE last_number = LAST_INSERT_ID(last_number + 1), updated_at = NOW()',
            array($counterType, $periodKey, $seed + 1)
        );
        $row = $this->db->query('SELECT LAST_INSERT_ID() AS next_number')->row();

        if (!$row) {
            if ($ownsTransaction) {
                $this->db->trans_rollback();
            }
            return false;
        }

        $next = (int) $row->next_number;

        if ($this->db->trans_status() === false) {
            if ($ownsTransaction) {
                $this->db->trans_rollback();
            }
            return false;
        }

        if ($ownsTransaction) {
            $this->db->trans_commit();
        }

        return $prefix . str_pad((string) $next, (int) $padding, '0', STR_PAD_LEFT);
    }

    public function record_event($activity, $notifications = array())
    {
        $ownsTransaction = !$this->db->trans_active();
        if ($ownsTransaction) {
            $this->db->trans_begin();
        }

        $success = $this->append_activity_log($activity)
            && $this->create_notifications($notifications)
            && $this->db->trans_status();

        if (!$success) {
            if ($ownsTransaction) {
                $this->db->trans_rollback();
            }
            return false;
        }

        if ($ownsTransaction) {
            $this->db->trans_commit();
        }

        return true;
    }

    public function save_actual_cost($context, $requestCode, $cost, $document)
    {
        $this->db->trans_begin();
        $request = $this->db->query(
            'SELECT * FROM tbpo_jasa_request WHERE kd_po_jasa = ? FOR UPDATE',
            array($requestCode)
        )->row();

        if (!$request || !pojasa_can_act_on_request($context, $request, 'COST_INPUT')) {
            $this->db->trans_rollback();
            return array('success' => false, 'code' => 'FORBIDDEN');
        }
        if (!in_array($request->status, array('SPK_TERBIT', 'ON_PROGRESS', 'SELESAI'), true)) {
            $this->db->trans_rollback();
            return array('success' => false, 'code' => 'INVALID_STATUS');
        }

        $cost['id_scope'] = isset($cost['id_scope']) && (int) $cost['id_scope'] > 0 ? (int) $cost['id_scope'] : null;
        $cost['id_material'] = isset($cost['id_material']) && (int) $cost['id_material'] > 0 ? (int) $cost['id_material'] : null;
        if ($cost['id_scope'] && !(int) $this->db->where(array('id_scope' => $cost['id_scope'], 'kd_po_jasa' => $requestCode, 'is_active' => 1))->count_all_results('tbpo_jasa_scope')) {
            $this->db->trans_rollback();
            return array('success' => false, 'code' => 'COST_ALLOCATION_INVALID');
        }
        if ($cost['id_material'] && !(int) $this->db->where(array('id_material' => $cost['id_material'], 'kd_po_jasa' => $requestCode, 'is_active' => 1))->count_all_results('tbpo_jasa_material')) {
            $this->db->trans_rollback();
            return array('success' => false, 'code' => 'COST_ALLOCATION_INVALID');
        }

        $payloadHash = hash('sha256', json_encode(array(
            $requestCode, (int) $context['id_user'], $cost['tgl_biaya'], $cost['cost_source'],
            isset($cost['id_draft_pembelian']) ? (int) $cost['id_draft_pembelian'] : null,
            $cost['jenis_biaya'], $cost['deskripsi'], (float) $cost['nominal'], $document['sha256'],
            $cost['id_scope'], $cost['id_material'],
        )));
        $existing = $this->db->get_where('tbpo_jasa_biaya_aktual', array(
            'idempotency_token' => $cost['idempotency_token'],
        ))->row();
        if ($existing) {
            if ($existing->kd_po_jasa !== $requestCode || (int) $existing->input_by !== (int) $context['id_user'] || !hash_equals((string) $existing->payload_hash, $payloadHash)) {
                $this->db->trans_rollback();
                return array('success' => false, 'code' => 'IDEMPOTENCY_CONFLICT');
            }
            $this->db->trans_commit();
            return array('success' => true, 'code' => 'IDEMPOTENT_REPLAY', 'id' => (int) $existing->id_biaya_aktual);
        }

        if (!in_array($cost['cost_source'], array('PEMBELIAN_BARU', 'NOTA_INVOICE_BARU'), true)) {
            $this->db->trans_rollback();
            return array('success' => false, 'code' => 'COST_SOURCE_INVALID');
        }
        if (!empty($cost['id_draft_pembelian'])) {
            $draft = $this->db->get_where('tbpo_jasa_draft_pembelian', array(
                'id_draft_pembelian' => (int) $cost['id_draft_pembelian'], 'kd_po_jasa' => $requestCode,
            ))->row();
            if (!$draft || $draft->status_draft === 'CANCELLED') {
                $this->db->trans_rollback();
                return array('success' => false, 'code' => 'PURCHASE_DRAFT_INVALID');
            }
        } else {
            $cost['id_draft_pembelian'] = null;
        }
        $cost['payload_hash'] = $payloadHash;
        $this->load->model('PO/M_PojasaIntegration');
        if (!$this->M_PojasaIntegration->schema_ready()) {
            $this->db->trans_rollback();
            return array('success' => false, 'code' => 'SCHEMA_NOT_READY');
        }
        $spk = $this->db->get_where('tbpo_jasa_spk', array('kd_po_jasa' => $requestCode))->row();
        $cost['source_type'] = 'MANUAL';
        $cost['record_type'] = 'MANUAL';
        $cost['record_status'] = $this->M_PojasaIntegration->evaluate_manual_cost_status($request, $cost['id_material'], (float) $cost['nominal'], null, $cost['id_scope']);
        $cost['id_spk'] = $spk ? (int) $spk->id_spk : null;
        $cost['spk_version_no'] = $spk ? max(1, (int) $spk->spk_version_no) : null;

        $document['kd_po_jasa'] = $requestCode;
        $document['revision_no'] = (int) $request->revision_no;
        $document['uploaded_by'] = (int) $context['id_user'];
        $this->db->insert('tbpo_jasa_dokumen', $document);
        $documentId = (int) $this->db->insert_id();

        $cost['kd_po_jasa'] = $requestCode;
        $cost['revision_no'] = (int) $request->revision_no;
        $cost['id_dokumen_bukti'] = $documentId;
        $cost['input_by'] = (int) $context['id_user'];
        $cost['input_name'] = (string) $context['nama_user'];
        $this->db->insert('tbpo_jasa_biaya_aktual', $cost);
        $costId = (int) $this->db->insert_id();
        $this->db->insert('tbpo_jasa_cost_audit', array(
            'id_biaya_aktual' => $costId, 'kd_po_jasa' => $requestCode, 'event_type' => 'CREATED',
            'before_json' => null, 'after_json' => json_encode($cost), 'reason' => null,
            'actor_id' => (int) $context['id_user'], 'actor_role' => $context['role'],
        ));

        $this->append_activity_log(array(
            'kd_po_jasa' => $requestCode,
            'revision_no' => (int) $request->revision_no,
            'event_type' => 'BIAYA_AKTUAL_DICATAT',
            'from_status' => $request->status,
            'to_status' => $request->status,
            'actor_id' => (int) $context['id_user'],
            'actor_code' => (string) $context['kode_user'],
            'actor_name' => (string) $context['nama_user'],
            'actor_role' => (string) $context['role'],
            'actor_departemen' => (string) $context['departemen'],
            'catatan' => 'Biaya aktual jasa dicatat dengan bukti wajib.',
            'metadata_json' => json_encode(array('id_biaya_aktual' => $costId, 'nominal' => $cost['nominal'], 'record_status' => $cost['record_status'], 'id_scope' => $cost['id_scope'], 'id_material' => $cost['id_material'])),
        ));
        $actorLabel = $context['role'] === 'ADMIN' ? 'Admin' : 'PIC';
        $this->notifyPurchasing($requestCode, 'BIAYA_AKTUAL_DICATAT', 'Biaya aktual PO Jasa', $actorLabel . ' mencatat biaya aktual untuk ' . $requestCode . '.', base_url('pojasa/purchasing/detail/' . $requestCode));
        if ($cost['record_status'] === 'MENUNGGU_PERSETUJUAN_REVISI') {
            $this->notifyCostOverbudget($request, $costId, (float) $cost['nominal']);
        }

        if ($this->db->trans_status() === false) {
            $this->db->trans_rollback();
            return array('success' => false, 'code' => 'DATABASE_ERROR');
        }
        $this->db->trans_commit();

        return array('success' => true, 'code' => 'CREATED', 'id' => $costId, 'record_status' => $cost['record_status']);
    }

    public function update_actual_cost($context, $requestCode, $costId, $changes, $reason)
    {
        $this->db->trans_begin();
        $request = $this->db->query('SELECT * FROM tbpo_jasa_request WHERE kd_po_jasa=? FOR UPDATE', array($requestCode))->row();
        $cost = $this->db->query('SELECT * FROM tbpo_jasa_biaya_aktual WHERE id_biaya_aktual=? AND kd_po_jasa=? FOR UPDATE', array((int) $costId, $requestCode))->row();
        if (!$request || !$cost || $cost->record_type !== 'MANUAL'
            || !pojasa_can_act_on_request($context, $request, 'COST_INPUT')
            || ($context['role'] !== 'ADMIN' && (int) $cost->input_by !== (int) $context['id_user'])) {
            $this->db->trans_rollback();
            return array('success' => false, 'code' => 'FORBIDDEN');
        }
        if (trim((string) $reason) === '') {
            $this->db->trans_rollback();
            return array('success' => false, 'code' => 'NOTE_REQUIRED');
        }
        $materialId = isset($changes['id_material']) && (int) $changes['id_material'] > 0 ? (int) $changes['id_material'] : null;
        $scopeId = isset($changes['id_scope']) && (int) $changes['id_scope'] > 0 ? (int) $changes['id_scope'] : null;
        if ($materialId && !(int) $this->db->where(array('id_material' => $materialId, 'kd_po_jasa' => $requestCode, 'is_active' => 1))->count_all_results('tbpo_jasa_material')) {
            $this->db->trans_rollback();
            return array('success' => false, 'code' => 'COST_ALLOCATION_INVALID');
        }
        if ($scopeId && !(int) $this->db->where(array('id_scope' => $scopeId, 'kd_po_jasa' => $requestCode, 'is_active' => 1))->count_all_results('tbpo_jasa_scope')) {
            $this->db->trans_rollback();
            return array('success' => false, 'code' => 'COST_ALLOCATION_INVALID');
        }
        $this->load->model('PO/M_PojasaIntegration');
        $newStatus = $this->M_PojasaIntegration->evaluate_manual_cost_status($request, $materialId, (float) $changes['nominal'], (int) $costId, $scopeId);
        $before = (array) $cost;
        $update = array(
            'tgl_biaya' => $changes['tgl_biaya'], 'cost_source' => $changes['cost_source'],
            'jenis_biaya' => $changes['jenis_biaya'], 'deskripsi' => $changes['deskripsi'],
            'nominal' => (float) $changes['nominal'], 'id_scope' => $scopeId, 'id_material' => $materialId,
            'record_status' => $newStatus, 'row_version' => (int) $cost->row_version + 1,
            'updated_by' => (int) $context['id_user'], 'updated_at' => date('Y-m-d H:i:s'),
            'verification_status' => 'BELUM_DIVERIFIKASI', 'verified_by' => null,
            'verified_at' => null, 'verification_note' => null,
        );
        $this->db->where(array('id_biaya_aktual' => (int) $costId, 'row_version' => (int) $cost->row_version))->update('tbpo_jasa_biaya_aktual', $update);
        if ($this->db->affected_rows() !== 1) {
            $this->db->trans_rollback();
            return array('success' => false, 'code' => 'CONCURRENT_UPDATE');
        }
        $this->db->insert('tbpo_jasa_cost_audit', array(
            'id_biaya_aktual' => (int) $costId, 'kd_po_jasa' => $requestCode, 'event_type' => 'UPDATED',
            'before_json' => json_encode($before), 'after_json' => json_encode($update), 'reason' => $reason,
            'actor_id' => (int) $context['id_user'], 'actor_role' => $context['role'],
        ));
        $this->append_activity_log(array(
            'kd_po_jasa' => $requestCode, 'revision_no' => (int) $request->revision_no,
            'event_type' => 'BIAYA_AKTUAL_DIPERBAIKI', 'from_status' => $request->status, 'to_status' => $request->status,
            'actor_id' => (int) $context['id_user'], 'actor_code' => $context['kode_user'],
            'actor_name' => $context['nama_user'], 'actor_role' => $context['role'],
            'actor_departemen' => $context['departemen'], 'catatan' => $reason,
            'metadata_json' => json_encode(array('id_biaya_aktual' => (int) $costId, 'before' => $before, 'after' => $update)),
        ));
        $this->notifyPurchasing($requestCode, 'BIAYA_AKTUAL_DIPERBAIKI', 'Perbaikan biaya aktual PO Jasa', 'Biaya manual ' . $requestCode . ' diperbaiki.', base_url('pojasa/purchasing/detail/' . $requestCode));
        if ($newStatus === 'MENUNGGU_PERSETUJUAN_REVISI') {
            $this->notifyCostOverbudget($request, (int) $costId, (float) $changes['nominal']);
        }
        if ($this->db->trans_status() === false) {
            $this->db->trans_rollback();
            return array('success' => false, 'code' => 'DATABASE_ERROR');
        }
        $this->db->trans_commit();
        return array('success' => true, 'code' => 'UPDATED', 'id' => (int) $costId, 'record_status' => $newStatus);
    }

    public function verify_actual_cost($context, $requestCode, $costId, $status, $note)
    {
        $this->db->trans_begin();
        if (!in_array($context['role'], array('ADMIN', 'PURCHASING'), true)) {
            $this->db->trans_rollback();
            return array('success' => false, 'code' => 'FORBIDDEN');
        }
        $request = $this->db->query('SELECT * FROM tbpo_jasa_request WHERE kd_po_jasa=? FOR UPDATE', array($requestCode))->row();
        $cost = $this->db->query('SELECT * FROM tbpo_jasa_biaya_aktual WHERE id_biaya_aktual=? AND kd_po_jasa=? FOR UPDATE', array((int) $costId, $requestCode))->row();
        if (!$request || !$cost || !in_array($status, array('DIVERIFIKASI', 'PERLU_PERBAIKAN'), true)) {
            $this->db->trans_rollback();
            return array('success' => false, 'code' => 'VALIDATION_ERROR');
        }
        if ($status === 'PERLU_PERBAIKAN' && trim((string) $note) === '') {
            $this->db->trans_rollback();
            return array('success' => false, 'code' => 'NOTE_REQUIRED');
        }
        $this->db->where('id_biaya_aktual', (int) $costId)->update('tbpo_jasa_biaya_aktual', array(
            'verification_status' => $status, 'verified_by' => (int) $context['id_user'],
            'verified_at' => date('Y-m-d H:i:s'), 'verification_note' => $note ?: null,
        ));
        $this->db->insert('tbpo_jasa_cost_audit', array(
            'id_biaya_aktual' => (int) $costId, 'kd_po_jasa' => $requestCode, 'event_type' => 'VERIFIED',
            'before_json' => json_encode(array('verification_status' => $cost->verification_status)),
            'after_json' => json_encode(array('verification_status' => $status)), 'reason' => $note ?: null,
            'actor_id' => (int) $context['id_user'], 'actor_role' => $context['role'],
        ));
        $this->append_activity_log(array(
            'kd_po_jasa' => $requestCode, 'revision_no' => (int) $request->revision_no,
            'event_type' => 'BIAYA_AKTUAL_DIVERIFIKASI', 'from_status' => $request->status, 'to_status' => $request->status,
            'actor_id' => (int) $context['id_user'], 'actor_code' => $context['kode_user'],
            'actor_name' => $context['nama_user'], 'actor_role' => $context['role'],
            'actor_departemen' => $context['departemen'], 'catatan' => $note,
            'metadata_json' => json_encode(array('id_biaya_aktual' => (int) $costId, 'verification_status' => $status)),
        ));
        if ($this->db->trans_status() === false) {
            $this->db->trans_rollback();
            return array('success' => false, 'code' => 'DATABASE_ERROR');
        }
        $this->db->trans_commit();
        return array('success' => true, 'code' => $status);
    }

    public function process_approval($context, $requestCode, $action, $note, $actionToken, $expectedStatus = null, $expectedVersion = null)
    {
        $this->db->trans_begin();
        $request = $this->db->query(
            'SELECT * FROM tbpo_jasa_request WHERE kd_po_jasa = ? FOR UPDATE',
            array($requestCode)
        )->row();
        if (!$request) {
            $this->db->trans_rollback();
            return array('success' => false, 'code' => 'NOT_FOUND');
        }

        $existing = $this->db->get_where('tbpo_jasa_approval', array('action_token' => $actionToken))->row();
        if ($existing) {
            if ($existing->kd_po_jasa !== $requestCode) {
                $this->db->trans_rollback();
                return array('success' => false, 'code' => 'IDEMPOTENCY_CONFLICT');
            }
            if ((int) $existing->actor_id !== (int) $context['id_user']) {
                $this->db->trans_rollback();
                return array('success' => false, 'code' => 'FORBIDDEN');
            }
            $spk = $this->db->get_where('tbpo_jasa_spk', array('kd_po_jasa' => $requestCode))->row();
            $this->db->trans_commit();
            return array(
                'success' => true,
                'code' => 'IDEMPOTENT_REPLAY',
                'status' => $existing->to_status,
                'no_spk' => $spk ? $spk->no_spk : null,
                'status_version' => (int) $request->status_version,
            );
        }

        if (($expectedStatus !== null && strtoupper(trim((string) $expectedStatus)) !== $request->status)
            || ($expectedVersion !== null && (int) $expectedVersion !== (int) $request->status_version)) {
            $this->db->trans_rollback();
            return array('success' => false, 'code' => 'CONCURRENT_UPDATE');
        }

        $stage = pojasa_workflow_stage($request->status);
        $capability = $stage;
        if (!$stage || !pojasa_can_act_on_request($context, $request, $capability)) {
            $this->db->trans_rollback();
            return array('success' => false, 'code' => 'FORBIDDEN');
        }
        if (!in_array($action, pojasa_workflow_actions($request->status), true)) {
            $this->db->trans_rollback();
            return array('success' => false, 'code' => 'INVALID_ACTION');
        }
        if ($stage === 'PURCHASING' && $request->status === 'MENUNGGU_PURCHASING_AWAL'
            && $action === 'UPDATE' && empty($request->reviewed_at_purchasing)) {
            $this->db->trans_rollback();
            return array('success' => false, 'code' => 'PURCHASING_REVIEW_NOT_SAVED');
        }
        $picConfirmation = $stage === 'PIC' && $action === 'ACC';
        if ((in_array($action, array('REVISI', 'REJECT', 'UPDATE'), true) || $picConfirmation) && trim((string) $note) === '') {
            $this->db->trans_rollback();
            return array('success' => false, 'code' => 'NOTE_REQUIRED');
        }

        $approvalRevision = (int) $request->revision_no;
        $isPurchasingRevisionSubmit = $stage === 'PURCHASING'
            && in_array($request->status, array(
                'REVISI_PURCHASING_KADEP', 'REVISI_PURCHASING_DIROPS', 'REVISI_PURCHASING_DIRUT',
            ), true)
            && $action === 'SUBMIT';
        if ($isPurchasingRevisionSubmit) {
            if (empty($request->edit_revision_no)
                || $request->purchasing_revision_status !== $request->status
                || empty($request->purchasing_revision_saved_at)) {
                $this->db->trans_rollback();
                return array('success' => false, 'code' => 'REVISION_NOT_SAVED');
            }
            $approvalRevision = (int) $request->edit_revision_no;
        }
        if ($stage === 'PURCHASING' && in_array($action, array('SUBMIT', 'TERBITKAN'), true)
            && (empty($request->tgl_mulai_pekerjaan) || empty($request->tgl_selesai_pekerjaan)
                || $request->tgl_selesai_pekerjaan < $request->tgl_mulai_pekerjaan)) {
            $this->db->trans_rollback();
            return array('success' => false, 'code' => 'SCHEDULE_REQUIRED');
        }

        $nextStatus = pojasa_next_status(
            $request->status,
            $action,
            $request->departemen,
            (int) $request->dirut_ops_approved_revision === (int) $request->revision_no
        );
        if ($nextStatus === false) {
            $this->db->trans_rollback();
            return array('success' => false, 'code' => 'INVALID_ACTION');
        }

        $now = date('Y-m-d H:i:s');
        $requestUpdate = array(
            'status' => $nextStatus,
            'status_version' => (int) $request->status_version + 1,
        );
        if ($stage === 'PURCHASING' && $action === 'SUBMIT') {
            $requestUpdate['submitted_by_purchasing'] = (string) $context['kode_user'];
            $requestUpdate['submitted_at_purchasing'] = $now;
            if ($isPurchasingRevisionSubmit) {
                $requestUpdate['revision_no'] = $approvalRevision;
                $requestUpdate['edit_revision_no'] = null;
                $requestUpdate['purchasing_revision_saved_at'] = null;
                $requestUpdate['purchasing_revision_status'] = null;
                $this->db->where(array(
                    'kd_po_jasa' => $requestCode,
                    'revision_no' => $approvalRevision,
                    'submitted_at' => null,
                ));
                $this->db->update('tbpo_jasa_revisi', array(
                    'submitted_by' => (int) $context['id_user'],
                    'submitted_at' => $now,
                ));
            }
        }
        if ($action === 'REVISI') {
            $requestUpdate['edit_revision_no'] = null;
            $requestUpdate['purchasing_revision_saved_at'] = null;
            $requestUpdate['purchasing_revision_status'] = null;
        }
        $spkNumber = null;
        $purchaseSubmission = null;
        if ($stage === 'KADEP' && $action === 'ACC') {
            $requestUpdate['acc_with_kadep'] = (string) $context['kode_user'];
            $requestUpdate['acc_at_kadep'] = $now;
        }
        if ($stage === 'DIREKTUR_OPERASIONAL' && $action === 'ACC') {
            $requestUpdate['acc_with_direktur_oprasional'] = (string) $context['kode_user'];
            $requestUpdate['acc_at_direktur_oprasional'] = $now;
            $requestUpdate['dirut_ops_approved_revision'] = $approvalRevision;
        }
        if ($stage === 'DIREKTUR' && $action === 'ACC') {
            $requestUpdate['acc_with_direktur'] = (string) $context['kode_user'];
            $requestUpdate['acc_at_direktur'] = $now;
        }
        if ($stage === 'PURCHASING' && $action === 'TERBITKAN') {
            $spkNumber = $this->generate_spk_number(date('Y-m-d'));
            if ($spkNumber === false) {
                $this->db->trans_rollback();
                return array('success' => false, 'code' => 'NUMBER_ERROR');
            }
            $requestUpdate['no_spk'] = $spkNumber;
            $requestUpdate['generated_by'] = (string) $context['kode_user'];
            $requestUpdate['generated_at'] = $now;
            if ($this->db->field_exists('estimasi_disetujui', 'tbpo_jasa_request')) {
                $requestUpdate['estimasi_disetujui'] = $this->db->field_exists('estimasi_purchasing', 'tbpo_jasa_request')
                    && $request->estimasi_purchasing !== null
                    ? (float) $request->estimasi_purchasing
                    : (float) $request->estimasi_total;
            }
            $this->db->insert('tbpo_jasa_spk', array(
                'kd_po_jasa' => $requestCode,
                'no_spk' => $spkNumber,
                'revision_no' => $approvalRevision,
                'issued_by' => (int) $context['id_user'],
                'issued_at' => $now,
                'snapshot_json' => $this->buildSpkSnapshot($requestCode, $request, $spkNumber, $now),
                'template_version' => 'DRAFT-SPK-COMPAT-V1',
                'document_status' => 'ISSUED',
            ));
        }

        $this->db->where('id_po_jasa', (int) $request->id_po_jasa);
        $this->db->where('status_version', (int) $request->status_version);
        $this->db->update('tbpo_jasa_request', $requestUpdate);
        if ($this->db->affected_rows() !== 1) {
            $this->db->trans_rollback();
            return array('success' => false, 'code' => 'CONCURRENT_UPDATE');
        }

        if ($stage === 'PURCHASING' && $action === 'TERBITKAN') {
            $this->load->model('PO/M_PojasaIntegration');
            $purchaseSubmission = $this->M_PojasaIntegration->create_purchase_submission($context, $requestCode);
            if (!$purchaseSubmission['success']) {
                $this->db->trans_rollback();
                return $purchaseSubmission;
            }
        }

        if (strpos($nextStatus, 'DITOLAK_') === 0) {
            $this->releasePlanningOnTerminal($requestCode, $context, $request->status, $nextStatus);
        }

        $this->db->insert('tbpo_jasa_approval', array(
            'kd_po_jasa' => $requestCode,
            'revision_no' => $approvalRevision,
            'approval_stage' => $stage,
            'action' => $action,
            'from_status' => $request->status,
            'to_status' => $nextStatus,
            'actor_id' => (int) $context['id_user'],
            'actor_code' => (string) $context['kode_user'],
            'actor_role' => (string) $context['role'],
            'actor_departemen' => (string) $context['departemen'],
            'catatan' => $note,
            'action_token' => $actionToken,
            'created_at' => $now,
        ));
        if ($action === 'REVISI') {
            $revisionTarget = (int) $request->revision_no + 1;
            $this->db->insert('tbpo_jasa_revisi', array(
                'kd_po_jasa' => $requestCode,
                'revision_no' => $revisionTarget,
                'revision_source' => $stage,
                'alasan_revisi' => $note,
                'snapshot_json' => json_encode($request),
                'requested_by' => (int) $context['id_user'],
                'requested_at' => $now,
            ));
        }
        $this->append_activity_log(array(
            'kd_po_jasa' => $requestCode,
            'revision_no' => $approvalRevision,
            'event_type' => $stage . '_' . $action,
            'from_status' => $request->status,
            'to_status' => $nextStatus,
            'actor_id' => (int) $context['id_user'],
            'actor_code' => (string) $context['kode_user'],
            'actor_name' => (string) $context['nama_user'],
            'actor_role' => (string) $context['role'],
            'actor_departemen' => (string) $context['departemen'],
            'catatan' => $note,
            'metadata_json' => json_encode(array('action_token' => $actionToken, 'no_spk' => $spkNumber)),
        ));
        $this->createApprovalNotifications($request, $nextStatus, $spkNumber);

        if ($this->db->trans_status() === false) {
            $this->db->trans_rollback();
            return array('success' => false, 'code' => 'DATABASE_ERROR');
        }
        $this->db->trans_commit();

        return array(
            'success' => true,
            'code' => 'UPDATED',
            'status' => $nextStatus,
            'no_spk' => $spkNumber,
            'purchase_submission' => $purchaseSubmission,
            'status_version' => (int) $request->status_version + 1,
        );
    }

    public function confirm_stock_receipt($context, $requestCode, $receiptAt, $note, $items, $idempotencyToken)
    {
        $this->db->trans_begin();
        $request = $this->db->query(
            'SELECT * FROM tbpo_jasa_request WHERE kd_po_jasa = ? FOR UPDATE',
            array($requestCode)
        )->row();

        if (!$request || !pojasa_can_act_on_request($context, $request, 'PIC_OWNER')) {
            $this->db->trans_rollback();
            return array('success' => false, 'code' => 'FORBIDDEN');
        }
        if (!in_array($request->status, array('SPK_TERBIT', 'ON_PROGRESS'), true)) {
            $this->db->trans_rollback();
            return array('success' => false, 'code' => 'INVALID_STATUS');
        }

        usort($items, function ($left, $right) {
            return (int) $left['id_allocation'] - (int) $right['id_allocation'];
        });
        $payloadHash = hash('sha256', json_encode(array($requestCode, (int) $context['id_user'], substr($receiptAt, 0, 10), $note, $items)));
        $existing = $this->db->get_where('tbpo_jasa_stock_receipt', array(
            'idempotency_token' => $idempotencyToken,
        ))->row();
        if ($existing) {
            if ($existing->kd_po_jasa !== $requestCode || (int) $existing->confirmed_by !== (int) $context['id_user'] || !hash_equals((string) $existing->payload_hash, $payloadHash)) {
                $this->db->trans_rollback();
                return array('success' => false, 'code' => 'IDEMPOTENCY_CONFLICT');
            }
            $this->db->trans_commit();
            return array('success' => true, 'code' => 'IDEMPOTENT_REPLAY', 'id' => (int) $existing->id_receipt, 'number' => $existing->no_receipt);
        }

        $receiptNumber = $this->generate_receipt_number(substr($receiptAt, 0, 10));
        if ($receiptNumber === false) {
            $this->db->trans_rollback();
            return array('success' => false, 'code' => 'NUMBER_ERROR');
        }
        $this->db->insert('tbpo_jasa_stock_receipt', array(
            'no_receipt' => $receiptNumber,
            'kd_po_jasa' => $requestCode,
            'receipt_at' => $receiptAt,
            'confirmed_by' => (int) $context['id_user'],
            'confirmed_name' => (string) $context['nama_user'],
            'catatan' => $note,
            'idempotency_token' => $idempotencyToken,
            'payload_hash' => $payloadHash,
        ));
        $receiptId = (int) $this->db->insert_id();

        foreach ($items as $item) {
            $allocation = $this->db->query(
                'SELECT a.*, m.id_material, b.kd_barang, b.kd_br_adm, b.kat_barang, b.satuan '
                . 'FROM tbpo_jasa_stock_allocation a '
                . 'JOIN tbpo_jasa_material m ON m.id_material = a.id_material '
                . 'JOIN tbpo_barang_nk b ON b.id_brg_nk = a.id_brg_nk '
                . 'WHERE a.id_allocation = ? FOR UPDATE',
                array((int) $item['id_allocation'])
            )->row();
            $quantity = (float) $item['qty_received'];
            if (!$allocation || $allocation->kd_po_jasa !== $requestCode || $allocation->status_allocation === 'RELEASED') {
                $this->db->trans_rollback();
                return array('success' => false, 'code' => 'ALLOCATION_INVALID');
            }

            $remaining = (float) $allocation->qty_allocation - (float) $allocation->qty_received - (float) $allocation->qty_released;
            if ($quantity <= 0 || $quantity - $remaining > 0.000001) {
                $this->db->trans_rollback();
                return array('success' => false, 'code' => 'QUANTITY_INVALID');
            }

            $this->db->query('SELECT id_brg_nk FROM tbpo_barang_nk WHERE id_brg_nk = ? FOR UPDATE', array((int) $allocation->id_brg_nk));
            $stock = $this->db->query(
                "SELECT COALESCE(SUM(CASE WHEN kd_akun IN ('11511','11513') THEN tr_qty ELSE -tr_qty END), 0) AS qty "
                . "FROM tbpo_transaksi WHERE kd_barang = ? AND kd_akun IN ('11511','11512','11513','11514')",
                array($allocation->kd_barang)
            )->row();
            if (!$stock || (float) $stock->qty + 0.000001 < $quantity) {
                $this->db->trans_rollback();
                return array('success' => false, 'code' => 'STOCK_INSUFFICIENT');
            }

            $this->db->insert('tbpo_transaksi', array(
                'kd_akun' => '11512',
                'kd_po_nk' => substr($requestCode, 0, 25),
                'kd_barang' => (string) $allocation->kd_barang,
                'kd_barangsys' => (string) $allocation->kd_br_adm,
                'keterangan' => 'Material PO Jasa diterima oleh PIC - ' . $requestCode,
                'kat_barang' => (string) $allocation->kat_barang,
                'tr_qty' => $quantity,
                'satuan' => (int) $allocation->satuan,
                'inputer' => substr((string) $context['kode_user'], 0, 25),
                'req_by' => substr((string) $request->kd_user, 0, 25),
                'tgl_transaksi' => substr($receiptAt, 0, 10),
                'create_at' => substr($receiptAt, 0, 10),
                'last_updated_by' => substr((string) $context['kode_user'], 0, 25),
            ));
            $transactionId = (int) $this->db->insert_id();
            $newReceived = (float) $allocation->qty_received + $quantity;
            $newStatus = abs($newReceived + (float) $allocation->qty_released - (float) $allocation->qty_allocation) < 0.000001 ? 'RECEIVED' : 'PARTIAL';
            $this->db->where('id_allocation', (int) $allocation->id_allocation);
            $this->db->update('tbpo_jasa_stock_allocation', array(
                'qty_received' => $newReceived,
                'status_allocation' => $newStatus,
                'version' => (int) $allocation->version + 1,
            ));
            $this->db->insert('tbpo_jasa_stock_receipt_detail', array(
                'id_receipt' => $receiptId,
                'id_allocation' => (int) $allocation->id_allocation,
                'id_material' => (int) $allocation->id_material,
                'id_brg_nk' => (int) $allocation->id_brg_nk,
                'qty_received' => $quantity,
                'id_transnk' => $transactionId,
                'posting_token' => substr(hash('sha256', $idempotencyToken . '|' . $allocation->id_allocation), 0, 36),
            ));
        }

        $this->append_activity_log(array(
            'kd_po_jasa' => $requestCode,
            'revision_no' => (int) $request->revision_no,
            'event_type' => 'MATERIAL_DITERIMA',
            'from_status' => $request->status,
            'to_status' => $request->status,
            'actor_id' => (int) $context['id_user'],
            'actor_code' => (string) $context['kode_user'],
            'actor_name' => (string) $context['nama_user'],
            'actor_role' => (string) $context['role'],
            'actor_departemen' => (string) $context['departemen'],
            'catatan' => 'PIC mengonfirmasi material telah diterima; transaksi stok keluar dibuat.',
            'metadata_json' => json_encode(array('id_receipt' => $receiptId, 'no_receipt' => $receiptNumber)),
        ));
        $this->notifyPurchasing($requestCode, 'MATERIAL_DITERIMA', 'Material PO Jasa diterima', 'PIC mengonfirmasi penerimaan material untuk ' . $requestCode . '.', base_url('pojasa/purchasing/detail/' . $requestCode));

        if ($this->db->trans_status() === false) {
            $this->db->trans_rollback();
            return array('success' => false, 'code' => 'DATABASE_ERROR');
        }
        $this->db->trans_commit();

        return array('success' => true, 'code' => 'CREATED', 'id' => $receiptId, 'number' => $receiptNumber);
    }

    public function append_activity_log($data)
    {
        $allowed = array(
            'kd_po_jasa', 'revision_no', 'event_type', 'from_status', 'to_status',
            'actor_id', 'actor_code', 'actor_name', 'actor_role', 'actor_departemen',
            'catatan', 'metadata_json', 'ip_address', 'user_agent', 'created_at',
        );
        $row = array_intersect_key($data, array_flip($allowed));
        if (empty($row['event_type'])) {
            return false;
        }
        if (empty($row['created_at'])) {
            $row['created_at'] = date('Y-m-d H:i:s');
        }

        return $this->db->insert('tbpo_jasa_log_aktivitas', $row);
    }

    public function create_notifications($rows)
    {
        if (empty($rows)) {
            return true;
        }

        return $this->db->insert_batch('tbpo_jasa_notifikasi', $rows) !== false;
    }

    public function get_user_notifications($userId, $afterId = 0, $limit = 20)
    {
        $this->db->from('tbpo_jasa_notifikasi');
        $this->db->where('recipient_user_id', (int) $userId);
        if ((int) $afterId > 0) {
            $this->db->where('id_notifikasi >', (int) $afterId);
        }
        $this->db->order_by('id_notifikasi', 'DESC');
        $this->db->limit(max(1, min((int) $limit, 50)));

        return $this->db->get()->result_array();
    }

    public function count_unread_notifications($userId)
    {
        $this->db->from('tbpo_jasa_notifikasi');
        $this->db->where('recipient_user_id', (int) $userId);
        $this->db->where('is_read', 0);

        return (int) $this->db->count_all_results();
    }

    public function mark_notifications_read($userId, $ids)
    {
        $ids = array_values(array_unique(array_filter(array_map('intval', (array) $ids), function ($id) {
            return $id > 0;
        })));
        if (empty($ids)) {
            return false;
        }

        $this->db->where('recipient_user_id', (int) $userId);
        $this->db->where_in('id_notifikasi', $ids);

        return $this->db->update('tbpo_jasa_notifikasi', array(
            'is_read' => 1,
            'read_at' => date('Y-m-d H:i:s'),
        ));
    }

    private function normalizeDate($date)
    {
        $date = trim((string) $date);
        if ($date === '' || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            return date('Y-m-d');
        }

        $parts = explode('-', $date);
        return checkdate((int) $parts[1], (int) $parts[2], (int) $parts[0]) ? $date : date('Y-m-d');
    }

    private function findExistingSequence($prefix, $sources)
    {
        $maximum = 0;
        foreach ((array) $sources as $source) {
            if (empty($source['table']) || empty($source['column'])) {
                continue;
            }
            if (!$this->db->table_exists($source['table']) || !$this->db->field_exists($source['column'], $source['table'])) {
                continue;
            }

            $table = $this->db->escape_identifiers($source['table']);
            $column = $this->db->escape_identifiers($source['column']);
            $row = $this->db->query(
                "SELECT COALESCE(MAX(CAST(RIGHT({$column}, 4) AS UNSIGNED)), 0) AS maximum FROM {$table} WHERE {$column} LIKE ?",
                array($prefix . '____')
            )->row();
            $maximum = max($maximum, $row ? (int) $row->maximum : 0);
        }

        return $maximum;
    }

    private function notifyPurchasing($requestCode, $eventType, $title, $message, $targetUrl)
    {
        $users = $this->db->query(
            "SELECT id_user FROM tbpo_user WHERE aksess_lv = 2 AND UPPER(TRIM(departement)) = 'PURCHASING'"
        )->result();
        $rows = array();
        foreach ($users as $user) {
            $rows[] = array(
                'kd_po_jasa' => $requestCode,
                'event_type' => $eventType,
                'recipient_user_id' => (int) $user->id_user,
                'title' => $title,
                'message' => $message,
                'target_url' => $targetUrl,
            );
        }

        return $this->create_notifications($rows);
    }

    private function notifyCostOverbudget($request, $costId, $amount)
    {
        $conditions = array(
            'kode_user = ?',
            "(aksess_lv = 2 AND UPPER(TRIM(departement)) = 'PURCHASING')",
            "(aksess_lv = 3 AND UPPER(TRIM(departement)) = 'DIREKTUR')",
        );
        $binds = array($request->kd_user);
        if (pojasa_requires_dirut_ops($request->departemen)) {
            $conditions[] = "(aksess_lv = 6 AND UPPER(TRIM(departement)) IN ('DIREKTUR OPERASIONAL','DIREKTUR OPRASIONAL'))";
        }
        $users = $this->db->query('SELECT DISTINCT id_user FROM tbpo_user WHERE ' . implode(' OR ', $conditions), $binds)->result();
        $rows = array();
        foreach ($users as $user) {
            $rows[] = array(
                'kd_po_jasa' => $request->kd_po_jasa,
                'event_type' => 'BIAYA_OVERBUDGET',
                'recipient_user_id' => (int) $user->id_user,
                'title' => 'Biaya aktual melebihi anggaran',
                'message' => $request->kd_po_jasa . ' memiliki biaya Rp ' . number_format($amount, 0, ',', '.') . ' yang menunggu Revisi SPK Biaya.',
                'target_url' => base_url('pojasa/workflow/detail/' . $request->kd_po_jasa . '#workflowSpkChangeTab'),
            );
        }
        return $this->create_notifications($rows);
    }

    private function buildSpkSnapshot($requestCode, $request, $spkNumber, $issuedAt)
    {
        $revisionNo = max(1, (int) $request->revision_no);
        $scope = $this->db->get_where('tbpo_jasa_scope', array(
            'kd_po_jasa' => $requestCode, 'revision_no' => $revisionNo, 'is_active' => 1,
        ))->result_array();
        $materials = $this->db->get_where('tbpo_jasa_material', array(
            'kd_po_jasa' => $requestCode, 'revision_no' => $revisionNo, 'is_active' => 1,
        ))->result_array();
        $vendor = null;
        if (!empty($request->kd_vendor_jasa)) {
            $vendor = $this->db->get_where('tbpo_jasa_vendor', array('kd_vendor_jasa' => $request->kd_vendor_jasa))->row_array();
        }
        $vendorComparison = array();
        if ($this->db->table_exists('tbpo_jasa_vendor_comparison')) {
            $vendorComparison = $this->db->order_by('is_selected', 'DESC')->order_by('amount', 'ASC')->get_where(
                'tbpo_jasa_vendor_comparison',
                array('kd_po_jasa' => $requestCode, 'revision_no' => $revisionNo, 'is_active' => 1)
            )->result_array();
        }

        return json_encode(array(
            'template_status' => 'DRAFT_NOT_OFFICIAL',
            'no_spk' => $spkNumber,
            'issued_at' => $issuedAt,
            'request' => $request,
            'vendor' => $vendor,
            'vendor_comparison' => $vendorComparison,
            'scope' => $scope,
            'materials' => $materials,
        ));
    }

    private function createApprovalNotifications($request, $nextStatus, $spkNumber)
    {
        $users = array();
        if (in_array($nextStatus, array(
            'MENUNGGU_PURCHASING_AWAL',
            'MENUNGGU_PURCHASING',
            'REVISI_PURCHASING_KADEP',
            'REVISI_PURCHASING_DIROPS',
            'REVISI_PURCHASING_DIRUT',
            'MENUNGGU_PENERBITAN_PURCHASING',
        ), true)) {
            $users = $this->db->query("SELECT id_user FROM tbpo_user WHERE aksess_lv = 2 AND UPPER(TRIM(departement)) = 'PURCHASING'")->result();
        } elseif ($nextStatus === 'MENUNGGU_KONFIRMASI_PIC') {
            $users = $this->db->query('SELECT id_user FROM tbpo_user WHERE kode_user = ?', array($request->kd_user))->result();
        } elseif ($nextStatus === 'MENUNGGU_KADEP') {
            $users = $this->db->query(
                'SELECT id_user FROM tbpo_user WHERE aksess_lv = 5 AND UPPER(TRIM(departement)) = ?',
                array(pojasa_normalize_department($request->departemen))
            )->result();
        } elseif ($nextStatus === 'MENUNGGU_DIRUT_OPS') {
            $users = $this->db->query("SELECT id_user FROM tbpo_user WHERE aksess_lv = 6 AND UPPER(TRIM(departement)) IN ('DIREKTUR OPERASIONAL','DIREKTUR OPRASIONAL')")->result();
        } elseif ($nextStatus === 'MENUNGGU_DIREKTUR') {
            $users = $this->db->query("SELECT id_user FROM tbpo_user WHERE aksess_lv = 3 AND UPPER(TRIM(departement)) = 'DIREKTUR'")->result();
        } else {
            $users = $this->db->query('SELECT id_user FROM tbpo_user WHERE kode_user = ?', array($request->kd_user))->result();
        }

        $message = 'Status ' . $request->kd_po_jasa . ' berubah menjadi ' . $nextStatus . '.';
        if ($spkNumber) {
            $message .= ' SPK ' . $spkNumber . ' diterbitkan otomatis.';
        }
        $rows = array();
        foreach ($users as $user) {
            $rows[] = array(
                'kd_po_jasa' => $request->kd_po_jasa,
                'event_type' => $nextStatus,
                'recipient_user_id' => (int) $user->id_user,
                'title' => 'Status PO Jasa',
                'message' => $message,
                'target_url' => $nextStatus === 'REVISI_PIC' || strpos($nextStatus, 'REVISI_PURCHASING_') === 0 || strpos($nextStatus, 'DITOLAK_') === 0 || in_array($nextStatus, array('PENDING_KADEP', 'SPK_TERBIT'), true)
                    ? base_url('pojasa/pic/detail/' . $request->kd_po_jasa)
                    : base_url('pojasa/workflow/detail/' . $request->kd_po_jasa),
            );
        }

        return $this->create_notifications($rows);
    }

    private function releasePlanningOnTerminal($requestCode, $context, $fromStatus, $toStatus)
    {
        $result = array('allocations' => 0, 'drafts' => 0);
        if ($this->db->table_exists('tbpo_jasa_stock_allocation')
            && $this->db->field_exists('release_reason', 'tbpo_jasa_stock_allocation')) {
            $this->db->query(
                "UPDATE tbpo_jasa_stock_allocation SET qty_released=GREATEST(qty_allocation-qty_received,0), status_allocation='RELEASED', version=version+1, released_by=?, released_at=NOW(), release_reason=? WHERE kd_po_jasa=? AND status_allocation IN ('ACTIVE','PARTIAL')",
                array((int) $context['id_user'], 'Otomatis dilepas karena status ' . $toStatus, $requestCode)
            );
            $result['allocations'] = $this->db->affected_rows();
        }
        if ($this->db->table_exists('tbpo_jasa_draft_pembelian')
            && $this->db->field_exists('cancel_reason', 'tbpo_jasa_draft_pembelian')) {
            $this->db->query(
                "UPDATE tbpo_jasa_draft_pembelian SET status_draft='CANCELLED', version=version+1, cancelled_by=?, cancelled_at=NOW(), cancel_reason=? WHERE kd_po_jasa=? AND status_draft='DRAFT'",
                array((int) $context['id_user'], 'Otomatis dibatalkan karena status ' . $toStatus, $requestCode)
            );
            $result['drafts'] = $this->db->affected_rows();
        }
        if ($result['allocations'] > 0 || $result['drafts'] > 0) {
            $this->append_activity_log(array(
                'kd_po_jasa' => $requestCode, 'event_type' => 'PERENCANAAN_PURCHASING_DILEPAS',
                'from_status' => $fromStatus, 'to_status' => $toStatus,
                'actor_id' => (int) $context['id_user'], 'actor_code' => (string) $context['kode_user'],
                'actor_name' => (string) $context['nama_user'], 'actor_role' => (string) $context['role'],
                'actor_departemen' => (string) $context['departemen'],
                'catatan' => 'Reservasi stok dilepas dan draft pembelian aktif dibatalkan otomatis.',
                'metadata_json' => json_encode($result),
            ));
        }
        return $result;
    }
}
