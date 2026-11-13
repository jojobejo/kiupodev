<?php
defined('BASEPATH') or exit('No direct script access allowed');

class M_PojasaSpkChange extends CI_Model
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('PO/M_PojasaCore');
        $this->load->model('PO/M_PojasaIntegration');
        $this->load->helper(array('pojasa_authorization', 'pojasa_status'));
    }

    public function schema_ready()
    {
        return $this->db->table_exists('tbpo_jasa_spk_change')
            && $this->db->table_exists('tbpo_jasa_spk_change_item')
            && $this->db->table_exists('tbpo_jasa_spk_change_approval')
            && $this->M_PojasaIntegration->schema_ready();
    }

    public function submit($context, $requestCode, $changeType, $input, $reason, $token)
    {
        if (!$this->schema_ready()) return array('success' => false, 'code' => 'SCHEMA_NOT_READY');
        $this->db->trans_begin();
        $request = $this->db->query('SELECT * FROM tbpo_jasa_request WHERE kd_po_jasa=? FOR UPDATE', array($requestCode))->row();
        $spk = $this->db->query('SELECT * FROM tbpo_jasa_spk WHERE kd_po_jasa=? FOR UPDATE', array($requestCode))->row();
        if (!$request || !$spk || !in_array($context['role'], array('ADMIN', 'PURCHASING'), true)
            || !in_array($request->status, array('SPK_TERBIT', 'ON_PROGRESS', 'SELESAI'), true)) {
            $this->db->trans_rollback();
            return array('success' => false, 'code' => 'FORBIDDEN');
        }
        $replay = $this->db->get_where('tbpo_jasa_spk_change', array('submit_token' => $token))->row();
        if ($replay) {
            if ($replay->kd_po_jasa !== $requestCode || (int) $replay->requested_by !== (int) $context['id_user']) {
                $this->db->trans_rollback(); return array('success' => false, 'code' => 'IDEMPOTENCY_CONFLICT');
            }
            $this->db->trans_commit();
            return array('success' => true, 'code' => 'IDEMPOTENT_REPLAY', 'id_change' => (int) $replay->id_spk_change, 'status' => $replay->status);
        }
        $pending = $this->db->query("SELECT id_spk_change FROM tbpo_jasa_spk_change WHERE kd_po_jasa=? AND status IN ('MENUNGGU_DIROPS','MENUNGGU_DIREKTUR') FOR UPDATE", array($requestCode))->row();
        if ($pending) {
            $this->db->trans_rollback(); return array('success' => false, 'code' => 'REVISION_PENDING');
        }
        $changeType = strtoupper(trim((string) $changeType));
        if (!in_array($changeType, array('DATA', 'COST'), true) || trim((string) $reason) === '') {
            $this->db->trans_rollback(); return array('success' => false, 'code' => 'REVISION_INVALID');
        }
        $old = $this->snapshot($request, $spk);
        $built = $changeType === 'DATA'
            ? $this->buildDataChange($request, $old, $input)
            : $this->buildCostChange($request, $old, $input);
        if (!$built['success']) {
            $this->db->trans_rollback(); return $built;
        }
        $stage = pojasa_requires_dirut_ops($request->departemen) ? 'DIREKTUR_OPERASIONAL' : 'DIREKTUR';
        $status = $stage === 'DIREKTUR_OPERASIONAL' ? 'MENUNGGU_DIROPS' : 'MENUNGGU_DIREKTUR';
        $latestRevision = $this->db->order_by('id_spk_change', 'DESC')->get_where('tbpo_jasa_spk_change', array('kd_po_jasa' => $requestCode, 'status' => 'REVISI'))->row();
        $this->db->insert('tbpo_jasa_spk_change', array(
            'kd_po_jasa' => $requestCode, 'id_spk' => (int) $spk->id_spk, 'no_spk' => $spk->no_spk,
            'from_version_no' => max(1, (int) $spk->spk_version_no), 'change_type' => $changeType,
            'old_snapshot_json' => json_encode($old), 'new_snapshot_json' => json_encode($built['snapshot']),
            'reason' => $reason, 'quantity_impact' => $built['quantity_impact'], 'cost_impact' => $built['cost_impact'],
            'status' => $status, 'current_stage' => $stage, 'requested_by' => (int) $context['id_user'],
            'requested_name' => $context['nama_user'], 'submit_token' => $token,
            'supersedes_change_id' => $latestRevision ? (int) $latestRevision->id_spk_change : null,
        ));
        $changeId = (int) $this->db->insert_id();
        foreach ($built['items'] as $item) {
            $item['id_spk_change'] = $changeId;
            $this->db->insert('tbpo_jasa_spk_change_item', $item);
        }
        $this->audit($context, $request, 'REVISI_SPK_' . $changeType . '_DIAJUKAN', array(
            'id_spk_change' => $changeId, 'status' => $status,
            'quantity_impact' => $built['quantity_impact'], 'cost_impact' => $built['cost_impact'], 'reason' => $reason,
        ));
        $this->notifyStage($request, $status, 'Revisi SPK ' . ($changeType === 'COST' ? 'Biaya' : 'Data'), $reason);
        if ($this->db->trans_status() === false) {
            $this->db->trans_rollback(); return array('success' => false, 'code' => 'DATABASE_ERROR');
        }
        $this->db->trans_commit();
        return array('success' => true, 'code' => 'SUBMITTED', 'id_change' => $changeId, 'status' => $status);
    }

    public function decide($context, $requestCode, $changeId, $action, $note, $token)
    {
        if (!$this->schema_ready()) return array('success' => false, 'code' => 'SCHEMA_NOT_READY');
        $this->db->trans_begin();
        $request = $this->db->query('SELECT * FROM tbpo_jasa_request WHERE kd_po_jasa=? FOR UPDATE', array($requestCode))->row();
        $change = $this->db->query('SELECT * FROM tbpo_jasa_spk_change WHERE id_spk_change=? AND kd_po_jasa=? FOR UPDATE', array((int) $changeId, $requestCode))->row();
        if (!$request || !$change) {
            $this->db->trans_rollback(); return array('success' => false, 'code' => 'NOT_FOUND');
        }
        $replay = $this->db->get_where('tbpo_jasa_spk_change_approval', array('action_token' => $token))->row();
        if ($replay) {
            if ((int) $replay->id_spk_change !== (int) $changeId || (int) $replay->actor_id !== (int) $context['id_user']) {
                $this->db->trans_rollback(); return array('success' => false, 'code' => 'IDEMPOTENCY_CONFLICT');
            }
            $this->db->trans_commit(); return array('success' => true, 'code' => 'IDEMPOTENT_REPLAY', 'status' => $replay->to_status);
        }
        $requiredRole = $change->current_stage;
        if ($context['role'] !== $requiredRole || !in_array($action, array('ACC', 'REVISI', 'REJECT'), true)
            || !in_array($change->status, array('MENUNGGU_DIROPS', 'MENUNGGU_DIREKTUR'), true)) {
            $this->db->trans_rollback(); return array('success' => false, 'code' => 'FORBIDDEN');
        }
        if ($action !== 'ACC' && trim((string) $note) === '') {
            $this->db->trans_rollback(); return array('success' => false, 'code' => 'NOTE_REQUIRED');
        }
        $fromStatus = $change->status;
        $final = false;
        if ($action === 'ACC' && $requiredRole === 'DIREKTUR_OPERASIONAL') {
            $toStatus = 'MENUNGGU_DIREKTUR';
            $nextStage = 'DIREKTUR';
        } elseif ($action === 'ACC') {
            $toStatus = 'DISETUJUI';
            $nextStage = 'SELESAI';
            $final = true;
        } elseif ($action === 'REVISI') {
            $toStatus = 'REVISI';
            $nextStage = 'PURCHASING';
        } else {
            $toStatus = 'DITOLAK';
            $nextStage = 'SELESAI';
        }
        $newVersion = (int) $change->from_version_no;
        if ($final) {
            $applied = $this->applyApprovedChange($context, $request, $change);
            if (!$applied['success']) {
                $this->db->trans_rollback(); return $applied;
            }
            $newVersion = $applied['spk_version_no'];
        } elseif ($action === 'REJECT' && $change->change_type === 'COST') {
            $snapshot = json_decode($change->new_snapshot_json, true);
            $costIds = isset($snapshot['cost_ids']) && is_array($snapshot['cost_ids']) ? array_map('intval', $snapshot['cost_ids']) : array();
            if ($costIds) {
                $this->db->where_in('id_biaya_aktual', $costIds)->where('kd_po_jasa', $requestCode)
                    ->update('tbpo_jasa_biaya_aktual', array('record_status' => 'MELEBIHI_ANGGARAN_DITOLAK'));
            }
        }
        $this->db->where(array('id_spk_change' => (int) $changeId, 'row_version' => (int) $change->row_version))->update('tbpo_jasa_spk_change', array(
            'status' => $toStatus, 'current_stage' => $nextStage, 'row_version' => (int) $change->row_version + 1,
            'final_decision_by' => $nextStage === 'SELESAI' ? (int) $context['id_user'] : null,
            'final_decision_at' => $nextStage === 'SELESAI' ? date('Y-m-d H:i:s') : null,
            'final_note' => $nextStage === 'SELESAI' ? ($note ?: null) : null,
        ));
        if ($this->db->affected_rows() !== 1) {
            $this->db->trans_rollback(); return array('success' => false, 'code' => 'CONCURRENT_UPDATE');
        }
        $this->db->insert('tbpo_jasa_spk_change_approval', array(
            'id_spk_change' => (int) $changeId, 'approval_stage' => $requiredRole, 'action' => $action,
            'from_status' => $fromStatus, 'to_status' => $toStatus, 'actor_id' => (int) $context['id_user'],
            'actor_code' => $context['kode_user'], 'actor_role' => $context['role'], 'note' => $note ?: null,
            'action_token' => $token,
        ));
        $this->audit($context, $request, 'REVISI_SPK_' . $action, array(
            'id_spk_change' => (int) $changeId, 'change_type' => $change->change_type,
            'from_status' => $fromStatus, 'to_status' => $toStatus, 'note' => $note, 'spk_version_no' => $newVersion,
        ));
        $this->notifyStage($request, $toStatus, 'Keputusan Revisi SPK', $toStatus . ($note ? ': ' . $note : ''));
        if ($this->db->trans_status() === false) {
            $this->db->trans_rollback(); return array('success' => false, 'code' => 'DATABASE_ERROR');
        }
        $this->db->trans_commit();
        return array('success' => true, 'code' => $toStatus, 'status' => $toStatus, 'spk_version_no' => $newVersion);
    }

    public function state($context, $request)
    {
        if (!$this->schema_ready()) return array('ready' => false, 'changes' => array());
        $changes = $this->db->order_by('id_spk_change', 'DESC')->get_where('tbpo_jasa_spk_change', array('kd_po_jasa' => $request->kd_po_jasa))->result_array();
        foreach ($changes as &$change) {
            $change['items'] = $this->db->order_by('id_change_item', 'ASC')->get_where('tbpo_jasa_spk_change_item', array('id_spk_change' => (int) $change['id_spk_change']))->result_array();
            $change['approvals'] = $this->db->order_by('id_change_approval', 'ASC')->get_where('tbpo_jasa_spk_change_approval', array('id_spk_change' => (int) $change['id_spk_change']))->result_array();
        }
        $pendingCosts = $this->db->order_by('id_biaya_aktual', 'ASC')->get_where('tbpo_jasa_biaya_aktual', array(
            'kd_po_jasa' => $request->kd_po_jasa, 'record_status' => 'MENUNGGU_PERSETUJUAN_REVISI',
        ))->result_array();
        return array(
            'ready' => true, 'changes' => $changes, 'pending_costs' => $pendingCosts,
            'can_submit' => in_array($context['role'], array('ADMIN', 'PURCHASING'), true)
                && in_array($request->status, array('SPK_TERBIT', 'ON_PROGRESS', 'SELESAI'), true),
            'can_decide' => in_array($context['role'], array('DIREKTUR_OPERASIONAL', 'DIREKTUR'), true),
        );
    }

    private function buildDataChange($request, $old, $input)
    {
        $new = $old;
        $items = array();
        $quantityImpact = 0;
        $costImpact = 0;
        foreach (array('tgl_mulai_pekerjaan', 'tgl_selesai_pekerjaan') as $field) {
            if (isset($input[$field]) && (string) $input[$field] !== (string) $old['request'][$field]) {
                $new['request'][$field] = (string) $input[$field];
                $items[] = $this->changeItem('REQUEST', null, $field, $old['request'][$field], $input[$field]);
            }
        }
        if (!$this->validDate($new['request']['tgl_mulai_pekerjaan']) || !$this->validDate($new['request']['tgl_selesai_pekerjaan'])
            || $new['request']['tgl_selesai_pekerjaan'] < $new['request']['tgl_mulai_pekerjaan']) {
            return array('success' => false, 'code' => 'SCHEDULE_INVALID');
        }
        foreach (array('scopes' => 'SCOPE', 'materials' => 'MATERIAL') as $collection => $entityType) {
            if (!isset($input[$collection]) || !is_array($input[$collection])) continue;
            $index = array();
            foreach ($new[$collection] as $key => $row) $index[(int) $row['id']] = $key;
            foreach ($input[$collection] as $candidate) {
                $id = isset($candidate['id']) ? (int) $candidate['id'] : 0;
                if (!$id || !isset($index[$id])) return array('success' => false, 'code' => 'REVISION_ITEM_INVALID');
                $key = $index[$id];
                $allowed = $collection === 'scopes'
                    ? array('name', 'description', 'qty', 'unit', 'estimated_price')
                    : array('name', 'description', 'qty', 'unit', 'estimated_price');
                $oldRow = $new[$collection][$key];
                foreach ($allowed as $field) {
                    if (!array_key_exists($field, $candidate)) continue;
                    $value = in_array($field, array('qty', 'estimated_price'), true) ? round((float) $candidate[$field], 2) : trim((string) $candidate[$field]);
                    if (($field === 'qty' && $value <= 0) || ($field === 'estimated_price' && $value < 0)
                        || ($field === 'name' && ($value === '' || mb_strlen($value) > 180))
                        || ($field === 'unit' && ($value === '' || mb_strlen($value) > 40))) {
                        return array('success' => false, 'code' => 'REVISION_ITEM_INVALID');
                    }
                    if ((string) $value === (string) $oldRow[$field]) continue;
                    $qtyDelta = $field === 'qty' ? $value - (float) $oldRow[$field] : 0;
                    $oldTotal = (float) $oldRow['qty'] * (float) $oldRow['estimated_price'];
                    $temp = $new[$collection][$key];
                    $temp[$field] = $value;
                    $newTotal = (float) $temp['qty'] * (float) $temp['estimated_price'];
                    $deltaCost = $newTotal - $oldTotal;
                    $items[] = $this->changeItem($entityType, $id, $field, $oldRow[$field], $value, $qtyDelta, $deltaCost);
                    $new[$collection][$key][$field] = $value;
                    $quantityImpact += $qtyDelta;
                    $costImpact += $deltaCost;
                    $oldRow = $new[$collection][$key];
                }
            }
        }
        if (!$items) return array('success' => false, 'code' => 'NO_CHANGES');
        return array('success' => true, 'snapshot' => $new, 'items' => $items,
            'quantity_impact' => round($quantityImpact, 2), 'cost_impact' => round($costImpact, 2));
    }

    private function buildCostChange($request, $old, $input)
    {
        $costIds = isset($input['cost_ids']) && is_array($input['cost_ids']) ? array_values(array_unique(array_filter(array_map('intval', $input['cost_ids'])))) : array();
        $approved = isset($input['approved_total']) ? round((float) $input['approved_total'], 2) : -1;
        if (!$costIds || $approved < 0) return array('success' => false, 'code' => 'REVISION_COST_INVALID');
        $costs = $this->db->where('kd_po_jasa', $request->kd_po_jasa)->where('record_status', 'MENUNGGU_PERSETUJUAN_REVISI')->where_in('id_biaya_aktual', $costIds)->get('tbpo_jasa_biaya_aktual')->result_array();
        if (count($costs) !== count($costIds)) return array('success' => false, 'code' => 'REVISION_COST_INVALID');
        $actual = (float) $this->db->query('SELECT COALESCE(SUM(nominal),0) total FROM tbpo_jasa_biaya_aktual WHERE kd_po_jasa=?', array($request->kd_po_jasa))->row()->total;
        if ($approved + 0.000001 < $actual) return array('success' => false, 'code' => 'APPROVED_BUDGET_TOO_LOW');
        $oldApproved = $request->estimasi_disetujui === null ? (float) $request->estimasi_total : (float) $request->estimasi_disetujui;
        $new = $old;
        $new['approved_total'] = $approved;
        $new['cost_ids'] = $costIds;
        $new['pending_costs'] = $costs;
        return array('success' => true, 'snapshot' => $new,
            'items' => array($this->changeItem('COST', null, 'approved_total', $oldApproved, $approved, 0, $approved - $oldApproved)),
            'quantity_impact' => 0, 'cost_impact' => round($approved - $oldApproved, 2));
    }

    private function applyApprovedChange($context, $request, $change)
    {
        $spk = $this->db->query('SELECT * FROM tbpo_jasa_spk WHERE id_spk=? FOR UPDATE', array((int) $change->id_spk))->row();
        if (!$spk || (int) $spk->spk_version_no !== (int) $change->from_version_no) return array('success' => false, 'code' => 'SPK_VERSION_CONFLICT');
        $new = json_decode($change->new_snapshot_json, true);
        if (!is_array($new)) return array('success' => false, 'code' => 'REVISION_INVALID');
        if (!(int) $this->db->where(array('kd_po_jasa' => $request->kd_po_jasa, 'spk_version_no' => (int) $spk->spk_version_no))->count_all_results('tbpo_jasa_spk_archive')) {
            $this->db->insert('tbpo_jasa_spk_archive', array(
                'id_spk_source' => (int) $spk->id_spk, 'kd_po_jasa' => $request->kd_po_jasa,
                'no_spk' => $spk->no_spk, 'request_revision_no' => (int) $spk->revision_no,
                'spk_version_no' => (int) $spk->spk_version_no, 'issued_by' => (int) $spk->issued_by,
                'issued_at' => $spk->issued_at, 'snapshot_json' => $spk->snapshot_json,
                'template_version' => $spk->template_version, 'archived_by' => (int) $context['id_user'],
            ));
        }
        if ($change->change_type === 'DATA') {
            foreach ($new['scopes'] as $row) {
                $this->db->where(array('id_scope' => (int) $row['id'], 'kd_po_jasa' => $request->kd_po_jasa))->update('tbpo_jasa_scope', array(
                    'nama_scope' => $row['name'], 'deskripsi' => $row['description'], 'qty' => $row['qty'],
                    'satuan' => $row['unit'], 'harga_estimasi' => $row['estimated_price'],
                    'total_estimasi' => round((float) $row['qty'] * (float) $row['estimated_price'], 2),
                ));
            }
            foreach ($new['materials'] as $row) {
                $before = $this->db->get_where('tbpo_jasa_material', array('id_material' => (int) $row['id']))->row_array();
                $after = array('nama_material' => $row['name'], 'deskripsi' => $row['description'],
                    'qty_kebutuhan' => $row['qty'], 'satuan' => $row['unit'], 'harga_estimasi' => $row['estimated_price']);
                $after['total_estimasi'] = round((float) $after['qty_kebutuhan'] * (float) $after['harga_estimasi'], 2);
                $this->db->where(array('id_material' => (int) $row['id'], 'kd_po_jasa' => $request->kd_po_jasa))->update('tbpo_jasa_material', $after);
                $changed = !$before
                    || (string) $before['nama_material'] !== (string) $after['nama_material']
                    || (string) $before['deskripsi'] !== (string) $after['deskripsi']
                    || abs((float) $before['qty_kebutuhan'] - (float) $after['qty_kebutuhan']) > 0.000001
                    || (string) $before['satuan'] !== (string) $after['satuan']
                    || abs((float) $before['harga_estimasi'] - (float) $after['harga_estimasi']) > 0.000001;
                if ($changed && !$this->M_PojasaIntegration->sync_material_change($context, $request->kd_po_jasa, (int) $change->id_spk_change, (int) $row['id'], $before, $after)) {
                    return array('success' => false, 'code' => 'LEGACY_INTEGER_REQUIRED');
                }
            }
            $scopeTotal = (float) $this->db->query('SELECT COALESCE(SUM(total_estimasi),0) total FROM tbpo_jasa_scope WHERE kd_po_jasa=? AND revision_no=? AND is_active=1', array($request->kd_po_jasa, (int) $request->revision_no))->row()->total;
            $materialTotal = (float) $this->db->query('SELECT COALESCE(SUM(total_estimasi),0) total FROM tbpo_jasa_material WHERE kd_po_jasa=? AND revision_no=? AND is_active=1', array($request->kd_po_jasa, (int) $request->revision_no))->row()->total;
            $this->db->where('id_po_jasa', (int) $request->id_po_jasa)->update('tbpo_jasa_request', array(
                'tgl_mulai_pekerjaan' => $new['request']['tgl_mulai_pekerjaan'],
                'tgl_selesai_pekerjaan' => $new['request']['tgl_selesai_pekerjaan'],
                'tgl_target' => $new['request']['tgl_selesai_pekerjaan'],
                'estimasi_total_jasa' => $scopeTotal, 'estimasi_total_bahan' => $materialTotal,
                'estimasi_total' => $scopeTotal + $materialTotal, 'estimasi_disetujui' => $scopeTotal + $materialTotal,
                'status_version' => (int) $request->status_version + 1,
            ));
        } else {
            $costIds = array_map('intval', $new['cost_ids']);
            $this->db->where_in('id_biaya_aktual', $costIds)->where('kd_po_jasa', $request->kd_po_jasa)->update('tbpo_jasa_biaya_aktual', array('record_status' => 'DISETUJUI_REVISI'));
            $this->db->where('id_po_jasa', (int) $request->id_po_jasa)->update('tbpo_jasa_request', array(
                'estimasi_disetujui' => (float) $new['approved_total'], 'status_version' => (int) $request->status_version + 1,
            ));
        }
        $newVersion = (int) $spk->spk_version_no + 1;
        $freshRequest = $this->db->get_where('tbpo_jasa_request', array('kd_po_jasa' => $request->kd_po_jasa))->row();
        $snapshot = $this->snapshot($freshRequest, $spk);
        $snapshot['spk_version_no'] = $newVersion;
        $snapshot['revision_label'] = 'Rev. ' . ($newVersion - 1);
        $snapshot['approved_change_id'] = (int) $change->id_spk_change;
        $this->db->where('id_spk', (int) $spk->id_spk)->update('tbpo_jasa_spk', array(
            'spk_version_no' => $newVersion, 'issued_by' => (int) $context['id_user'],
            'issued_at' => date('Y-m-d H:i:s'), 'snapshot_json' => json_encode($snapshot), 'document_status' => 'ISSUED',
        ));
        return array('success' => true, 'spk_version_no' => $newVersion);
    }

    private function snapshot($request, $spk)
    {
        $scopes = array();
        foreach ($this->db->order_by('line_no', 'ASC')->get_where('tbpo_jasa_scope', array('kd_po_jasa' => $request->kd_po_jasa, 'revision_no' => (int) $request->revision_no, 'is_active' => 1))->result_array() as $row) {
            $scopes[] = array('id' => (int) $row['id_scope'], 'line_no' => (int) $row['line_no'], 'name' => $row['nama_scope'],
                'description' => $row['deskripsi'], 'qty' => (float) $row['qty'], 'unit' => $row['satuan'], 'estimated_price' => (float) $row['harga_estimasi']);
        }
        $materials = array();
        foreach ($this->db->order_by('line_no', 'ASC')->get_where('tbpo_jasa_material', array('kd_po_jasa' => $request->kd_po_jasa, 'revision_no' => (int) $request->revision_no, 'is_active' => 1))->result_array() as $row) {
            $materials[] = array('id' => (int) $row['id_material'], 'line_no' => (int) $row['line_no'], 'name' => $row['nama_material'],
                'description' => $row['deskripsi'], 'qty' => (float) $row['qty_kebutuhan'], 'unit' => $row['satuan'], 'estimated_price' => (float) $row['harga_estimasi']);
        }
        return array(
            'request' => array('kd_po_jasa' => $request->kd_po_jasa, 'tgl_mulai_pekerjaan' => $request->tgl_mulai_pekerjaan,
                'tgl_selesai_pekerjaan' => $request->tgl_selesai_pekerjaan, 'estimasi_total' => (float) $request->estimasi_total,
                'estimasi_disetujui' => $request->estimasi_disetujui === null ? (float) $request->estimasi_total : (float) $request->estimasi_disetujui),
            'spk' => array('id_spk' => (int) $spk->id_spk, 'no_spk' => $spk->no_spk, 'spk_version_no' => max(1, (int) $spk->spk_version_no)),
            'scopes' => $scopes, 'materials' => $materials,
        );
    }

    private function changeItem($entityType, $entityId, $field, $old, $new, $quantityImpact = 0, $costImpact = 0)
    {
        return array('entity_type' => $entityType, 'entity_id' => $entityId, 'field_name' => $field,
            'old_value' => is_scalar($old) || $old === null ? (string) $old : json_encode($old),
            'new_value' => is_scalar($new) || $new === null ? (string) $new : json_encode($new),
            'quantity_impact' => round($quantityImpact, 2), 'cost_impact' => round($costImpact, 2));
    }

    private function audit($context, $request, $event, $metadata)
    {
        return $this->M_PojasaCore->append_activity_log(array(
            'kd_po_jasa' => $request->kd_po_jasa, 'revision_no' => (int) $request->revision_no,
            'event_type' => $event, 'from_status' => $request->status, 'to_status' => $request->status,
            'actor_id' => (int) $context['id_user'], 'actor_code' => $context['kode_user'], 'actor_name' => $context['nama_user'],
            'actor_role' => $context['role'], 'actor_departemen' => $context['departemen'], 'metadata_json' => json_encode($metadata),
        ));
    }

    private function notifyStage($request, $status, $title, $message)
    {
        if ($status === 'MENUNGGU_DIROPS') {
            $users = $this->db->query("SELECT id_user FROM tbpo_user WHERE aksess_lv=6 AND UPPER(TRIM(departement)) IN ('DIREKTUR OPERASIONAL','DIREKTUR OPRASIONAL')")->result();
        } elseif ($status === 'MENUNGGU_DIREKTUR') {
            $users = $this->db->query("SELECT id_user FROM tbpo_user WHERE aksess_lv=3 AND UPPER(TRIM(departement))='DIREKTUR'")->result();
        } else {
            $users = $this->db->query("SELECT DISTINCT id_user FROM tbpo_user WHERE kode_user=? OR (aksess_lv=2 AND UPPER(TRIM(departement))='PURCHASING')", array($request->kd_user))->result();
        }
        $rows = array();
        foreach ($users as $user) {
            $rows[] = array('kd_po_jasa' => $request->kd_po_jasa, 'event_type' => 'REVISI_SPK_' . $status,
                'recipient_user_id' => (int) $user->id_user, 'title' => $title, 'message' => $message,
                'target_url' => base_url('pojasa/workflow/detail/' . $request->kd_po_jasa . '#workflowSpkChangeTab'));
        }
        return $this->M_PojasaCore->create_notifications($rows);
    }

    private function validDate($value)
    {
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) $value)) return false;
        $parts = explode('-', $value);
        return checkdate((int) $parts[1], (int) $parts[2], (int) $parts[0]);
    }
}
