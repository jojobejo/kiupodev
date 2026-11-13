<?php
defined('BASEPATH') or exit('No direct script access allowed');

class M_PojasaExecution extends CI_Model
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('PO/M_PojasaCore');
        $this->load->helper(array('pojasa_authorization', 'pojasa_status'));
    }

    public function schema_ready()
    {
        $required = array(
            'tbpo_jasa_request' => array('estimasi_disetujui', 'closed_at', 'closed_by'),
            'tbpo_jasa_progress' => array('aktivitas', 'kendala', 'tindak_lanjut', 'id_dokumen_evidence', 'created_by_user_id', 'payload_hash'),
            'tbpo_jasa_biaya_aktual' => array('cost_source', 'id_draft_pembelian', 'payload_hash'),
            'tbpo_jasa_stock_receipt' => array('payload_hash'),
        );
        foreach ($required as $table => $fields) {
            if (!$this->db->table_exists($table)) {
                return false;
            }
            foreach ($fields as $field) {
                if (!$this->db->field_exists($field, $table)) {
                    return false;
                }
            }
        }
        return $this->M_PojasaCore->tables_ready();
    }

    public function get_state($context, $requestCode)
    {
        $request = $this->M_PojasaCore->get_request($requestCode);
        if (!$request || !empty($request->deleted_at) || !pojasa_can_act_on_request($context, $request, 'PIC_OWNER')) {
            return false;
        }

        $progress = $this->db->query(
            'SELECT p.*, d.file_original evidence_name, d.mime_type evidence_mime '
            . 'FROM tbpo_jasa_progress p LEFT JOIN tbpo_jasa_dokumen d ON d.id_dokumen = p.id_dokumen_evidence '
            . 'WHERE p.kd_po_jasa = ? ORDER BY p.tgl_progress DESC, p.id_progress_jasa DESC',
            array($requestCode)
        )->result_array();
        $allocations = $this->db->query(
            'SELECT a.*, m.nama_material, m.satuan, b.kd_barang, b.kd_br_adm, '
            . '(a.qty_allocation - a.qty_received - a.qty_released) qty_remaining '
            . 'FROM tbpo_jasa_stock_allocation a '
            . 'JOIN tbpo_jasa_material m ON m.id_material = a.id_material '
            . 'JOIN tbpo_barang_nk b ON b.id_brg_nk = a.id_brg_nk '
            . "WHERE a.kd_po_jasa = ? AND a.status_allocation IN ('ACTIVE','PARTIAL') "
            . 'ORDER BY m.line_no, a.id_allocation',
            array($requestCode)
        )->result_array();
        $hasPurchaseReceipts = $this->db->table_exists('tbpo_jasa_purchase_receipt')
            && $this->db->field_exists('id_transnk', 'tbpo_jasa_purchase_receipt');
        $hasLegacyOnHandPosting = $this->db->table_exists('tbpo_po_nk')
            && $this->db->field_exists('source_module', 'tbpo_po_nk')
            && $this->db->field_exists('source_material_id', 'tbpo_detail_po_nk');
        $receiptBindings = array($requestCode);
        $receiptSql =
            "SELECT h.receipt_at,h.no_receipt,d.qty_received,d.id_allocation,m.nama_material,m.satuan,d.id_transnk,"
            . "'PENERIMAAN_PIC' receipt_source,'RECEIPT' record_type,'11512' kd_akun "
            . 'FROM tbpo_jasa_stock_receipt h '
            . 'JOIN tbpo_jasa_stock_receipt_detail d ON d.id_receipt = h.id_receipt '
            . 'JOIN tbpo_jasa_material m ON m.id_material = d.id_material '
            . 'WHERE h.kd_po_jasa = ? ';
        if ($hasPurchaseReceipts) {
            $receiptSql .= "UNION ALL SELECT pr.on_hand_date,pr.receipt_reference,pr.qty_received,NULL,m.nama_material,m.satuan,pr.id_transnk,"
                    . "'ON_HAND_PURCHASING',pr.record_type,t.kd_akun FROM tbpo_jasa_purchase_receipt pr "
                    . 'JOIN tbpo_jasa_material m ON m.id_material=pr.id_material '
                    . 'LEFT JOIN tbpo_transaksi t ON t.id_transnk=pr.id_transnk '
                    . 'WHERE pr.kd_po_jasa=? ';
            $receiptBindings[] = $requestCode;
        }
        if ($hasLegacyOnHandPosting) {
            // Legacy Purchasing ON HAND posts the physical issue directly to
            // tbpo_transaksi.  Expose that outgoing leg in the same PIC table
            // using its existing receipt headers, without treating it as a
            // PIC-entered receipt.
            $receiptSql .= "UNION ALL SELECT t.tgl_transaksi,CONCAT('ONHAND-',p.kd_po_nk),t.tr_qty,NULL,"
                . "m.nama_material,m.satuan,t.id_transnk,'ON_HAND_LEGACY_PO','RECEIPT',t.kd_akun "
                . 'FROM tbpo_transaksi t '
                . "JOIN tbpo_po_nk p ON p.kd_po_req=t.kd_po_nk AND p.source_module='PO_JASA' "
                . 'JOIN tbpo_detail_po_nk d ON d.kd_po_nk=p.kd_po_nk AND d.kd_barang=t.kd_barang '
                . 'JOIN tbpo_jasa_material m ON m.id_material=d.source_material_id '
                . "WHERE t.kd_po_nk=? AND t.kd_akun='11512' "
                . "AND t.keterangan LIKE CONCAT('ON_HAND PO Jasa ',p.kd_po_nk,' - stok keluar ke PO Jasa %') ";
            $receiptBindings[] = $requestCode;
        }
        $receipts = $this->db->query(
            $receiptSql . 'ORDER BY receipt_at DESC, id_transnk DESC',
            $receiptBindings
        )->result_array();
        $costs = $this->db->query(
            'SELECT c.*, d.file_original evidence_name, d.mime_type evidence_mime '
            . 'FROM tbpo_jasa_biaya_aktual c LEFT JOIN tbpo_jasa_dokumen d ON d.id_dokumen = c.id_dokumen_bukti '
            . 'WHERE c.kd_po_jasa = ? ORDER BY c.tgl_biaya DESC, c.id_biaya_aktual DESC',
            array($requestCode)
        )->result_array();
        $actual = 0.0;
        foreach ($costs as $cost) {
            $actual += (float) $cost['nominal'];
        }
        $approved = $request->estimasi_disetujui === null ? (float) $request->estimasi_total : (float) $request->estimasi_disetujui;

        return array(
            'request' => $request,
            'progress' => $progress,
            'allocations' => $allocations,
            'receipts' => $receipts,
            'costs' => $costs,
            'cost_summary' => array(
                'requested' => (float) $request->estimasi_total,
                'approved' => $approved,
                'actual' => round($actual, 2),
                'variance' => round($approved - $actual, 2),
            ),
            // Admin retains the approved backend support override, but business input controls are exposed to the PIC only.
            'can_execute' => $context['role'] === 'PIC' && in_array($request->status, array('SPK_TERBIT', 'ON_PROGRESS', 'SELESAI'), true),
            'can_receive' => $context['role'] === 'PIC' && in_array($request->status, array('SPK_TERBIT', 'ON_PROGRESS'), true),
            'can_cost' => $context['role'] === 'PIC' && in_array($request->status, array('SPK_TERBIT', 'ON_PROGRESS', 'SELESAI'), true),
        );
    }

    public function save_progress($context, $requestCode, $input, $document = null)
    {
        $this->db->trans_begin();
        $request = $this->db->query('SELECT * FROM tbpo_jasa_request WHERE kd_po_jasa = ? FOR UPDATE', array($requestCode))->row();
        if (!$request || !pojasa_can_act_on_request($context, $request, 'PIC_OWNER')) {
            $this->db->trans_rollback();
            return array('success' => false, 'code' => 'FORBIDDEN');
        }
        if (!in_array($request->status, array('SPK_TERBIT', 'ON_PROGRESS', 'SELESAI'), true)) {
            $this->db->trans_rollback();
            return array('success' => false, 'code' => 'INVALID_STATUS');
        }

        $payloadHash = hash('sha256', json_encode(array(
            $requestCode, (int) $context['id_user'], $input['tgl_progress'], $input['progress_persen'],
            $input['status_progress'], $input['aktivitas'], $input['kendala'], $input['tindak_lanjut'], $input['catatan'],
        )));
        $existing = $this->db->get_where('tbpo_jasa_progress', array('idempotency_token' => $input['idempotency_token']))->row();
        if ($existing) {
            if ($existing->kd_po_jasa !== $requestCode || (int) $existing->created_by_user_id !== (int) $context['id_user'] || !hash_equals((string) $existing->payload_hash, $payloadHash)) {
                $this->db->trans_rollback();
                return array('success' => false, 'code' => 'IDEMPOTENCY_CONFLICT');
            }
            $this->db->trans_commit();
            return array('success' => true, 'code' => 'IDEMPOTENT_REPLAY', 'id' => (int) $existing->id_progress_jasa, 'status' => $existing->status_sesudah);
        }

        $latest = $this->db->query(
            'SELECT * FROM tbpo_jasa_progress WHERE kd_po_jasa = ? ORDER BY tgl_progress DESC, id_progress_jasa DESC LIMIT 1 FOR UPDATE',
            array($requestCode)
        )->row();
        if ($latest && ((float) $input['progress_persen'] + 0.000001 < (float) $latest->progress_persen || $input['tgl_progress'] < $latest->tgl_progress)) {
            $this->db->trans_rollback();
            return array('success' => false, 'code' => 'PROGRESS_REGRESSION');
        }

        $nextStatus = $this->resolveRequestStatus($request->status, $input['status_progress'], (float) $input['progress_persen']);
        if ($nextStatus === false) {
            $this->db->trans_rollback();
            return array('success' => false, 'code' => 'PROGRESS_INVALID');
        }

        $documentId = null;
        if ($document) {
            $document['kd_po_jasa'] = $requestCode;
            $document['revision_no'] = (int) $request->revision_no;
            $document['uploaded_by'] = (int) $context['id_user'];
            $this->db->insert('tbpo_jasa_dokumen', $document);
            $documentId = (int) $this->db->insert_id();
        }

        $progressRow = array(
            'kd_po_jasa' => $requestCode,
            'revision_no' => (int) $request->revision_no,
            'tgl_progress' => $input['tgl_progress'],
            'milestone' => $input['aktivitas'],
            'aktivitas' => $input['aktivitas'],
            'kendala' => $input['kendala'],
            'tindak_lanjut' => $input['tindak_lanjut'],
            'progress_persen' => $input['progress_persen'],
            'status_progress' => $input['status_progress'],
            'status_sebelum' => $request->status,
            'status_sesudah' => $nextStatus,
            'idempotency_token' => $input['idempotency_token'],
            'payload_hash' => $payloadHash,
            'catatan' => $input['catatan'],
            'id_dokumen_evidence' => $documentId,
            'created_by_user_id' => (int) $context['id_user'],
            'created_by' => (string) $context['kode_user'],
            'created_name' => (string) $context['nama_user'],
        );
        if ($this->db->field_exists('spk_version_no', 'tbpo_jasa_progress')) {
            $spk = $this->db->get_where('tbpo_jasa_spk', array('kd_po_jasa' => $requestCode))->row();
            $progressRow['spk_version_no'] = $spk && isset($spk->spk_version_no) ? max(1, (int) $spk->spk_version_no) : 1;
        }
        $this->db->insert('tbpo_jasa_progress', $progressRow);
        $progressId = (int) $this->db->insert_id();

        $requestUpdate = array('status' => $nextStatus, 'status_version' => (int) $request->status_version + 1);
        if ($nextStatus === 'SELESAI' && empty($request->completed_at)) {
            $requestUpdate['completed_at'] = date('Y-m-d H:i:s');
        }
        if ($nextStatus === 'DITUTUP') {
            $requestUpdate['closed_at'] = date('Y-m-d H:i:s');
            $requestUpdate['closed_by'] = (int) $context['id_user'];
        }
        $this->db->where(array('id_po_jasa' => (int) $request->id_po_jasa, 'status_version' => (int) $request->status_version));
        $this->db->update('tbpo_jasa_request', $requestUpdate);
        if ($this->db->affected_rows() !== 1) {
            $this->db->trans_rollback();
            return array('success' => false, 'code' => 'CONCURRENT_UPDATE');
        }

        $this->M_PojasaCore->append_activity_log(array(
            'kd_po_jasa' => $requestCode, 'revision_no' => (int) $request->revision_no,
            'event_type' => 'PROGRESS_' . $input['status_progress'], 'from_status' => $request->status, 'to_status' => $nextStatus,
            'actor_id' => (int) $context['id_user'], 'actor_code' => $context['kode_user'], 'actor_name' => $context['nama_user'],
            'actor_role' => $context['role'], 'actor_departemen' => $context['departemen'], 'catatan' => $input['catatan'],
            'metadata_json' => json_encode(array('id_progress' => $progressId, 'persen' => (float) $input['progress_persen'], 'evidence_id' => $documentId)),
        ));
        $this->notifyExecution($request, $input['status_progress'], (float) $input['progress_persen']);

        if ($this->db->trans_status() === false) {
            $this->db->trans_rollback();
            return array('success' => false, 'code' => 'DATABASE_ERROR');
        }
        $this->db->trans_commit();
        return array('success' => true, 'code' => 'CREATED', 'id' => $progressId, 'status' => $nextStatus);
    }

    public function dashboard_summary($context)
    {
        if (!$this->schema_ready()) {
            return null;
        }
        $where = array('deleted_at IS NULL');
        $binds = array();
        if ($context['role'] === 'PIC') {
            $where[] = 'kd_user = ?';
            $binds[] = $context['kode_user'];
        } elseif ($context['role'] === 'KADEP') {
            $where[] = 'UPPER(TRIM(departemen)) = ?';
            $binds[] = $context['departemen'];
        } elseif ($context['role'] === 'DIREKTUR_OPERASIONAL') {
            $where[] = "UPPER(TRIM(departemen)) IN ('IT','HRD','GA')";
        } elseif (!in_array($context['role'], array('ADMIN', 'PURCHASING', 'DIREKTUR'), true)) {
            return null;
        }
        $row = $this->db->query(
            "SELECT COUNT(*) total, SUM(status IN ('MENUNGGU_PURCHASING_AWAL','MENUNGGU_KADEP','PENDING_KADEP','MENUNGGU_PURCHASING','MENUNGGU_DIRUT_OPS','MENUNGGU_PURCHASING_DIROPS','MENUNGGU_DIREKTUR','MENUNGGU_PENERBITAN_PURCHASING')) menunggu, "
            . "SUM(status IN ('SPK_TERBIT','ON_PROGRESS')) berjalan, SUM(status IN ('SELESAI','DITUTUP')) selesai "
            . 'FROM tbpo_jasa_request WHERE ' . implode(' AND ', $where),
            $binds
        )->row_array();
        return array_map('intval', $row ?: array('total' => 0, 'menunggu' => 0, 'berjalan' => 0, 'selesai' => 0));
    }

    private function resolveRequestStatus($currentStatus, $workStatus, $percent)
    {
        if ($workStatus === 'BELUM_DIMULAI' && $currentStatus === 'SPK_TERBIT' && abs($percent) < 0.000001) {
            return 'SPK_TERBIT';
        }
        if ($workStatus === 'ON_PROGRESS' && in_array($currentStatus, array('SPK_TERBIT', 'ON_PROGRESS'), true) && $percent > 0 && $percent < 100) {
            return 'ON_PROGRESS';
        }
        if ($workStatus === 'SELESAI' && in_array($currentStatus, array('SPK_TERBIT', 'ON_PROGRESS'), true) && abs($percent - 100) < 0.000001) {
            return 'SELESAI';
        }
        if ($workStatus === 'DITUTUP' && $currentStatus === 'SELESAI' && abs($percent - 100) < 0.000001) {
            return 'DITUTUP';
        }
        return false;
    }

    private function notifyExecution($request, $statusProgress, $percent)
    {
        $department = pojasa_normalize_department($request->departemen);
        $sql = "SELECT id_user FROM tbpo_user WHERE (aksess_lv = 2 AND UPPER(TRIM(departement)) = 'PURCHASING') "
            . "OR (aksess_lv = 3 AND UPPER(TRIM(departement)) = 'DIREKTUR') "
            . 'OR (aksess_lv = 5 AND UPPER(TRIM(departement)) = ?)';
        if (in_array($department, array('IT', 'HRD', 'GA'), true)) {
            $sql .= " OR (aksess_lv = 6 AND UPPER(TRIM(REPLACE(departement, 'OPRASIONAL', 'OPERASIONAL'))) = 'DIREKTUR OPERASIONAL')";
        }
        $recipients = $this->db->query($sql, array($department))->result_array();
        $rows = array();
        foreach ($recipients as $recipient) {
            $rows[] = array(
                'kd_po_jasa' => $request->kd_po_jasa, 'event_type' => 'PROGRESS_' . $statusProgress,
                'recipient_user_id' => (int) $recipient['id_user'], 'title' => 'Progress PO Jasa',
                'message' => $request->kd_po_jasa . ' diperbarui menjadi ' . $statusProgress . ' (' . $percent . '%).',
                'target_url' => base_url('pojasa/workflow/detail/' . $request->kd_po_jasa),
            );
        }
        return $this->M_PojasaCore->create_notifications($rows);
    }
}
