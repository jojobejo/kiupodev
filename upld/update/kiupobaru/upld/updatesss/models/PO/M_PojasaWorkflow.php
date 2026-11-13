<?php
defined('BASEPATH') or exit('No direct script access allowed');

class M_PojasaWorkflow extends CI_Model
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('PO/M_PojasaCore');
        $this->load->helper(array('pojasa_authorization', 'pojasa_status'));
    }

    public function schema_ready()
    {
        return $this->M_PojasaCore->tables_ready()
            && $this->db->field_exists('estimasi_purchasing', 'tbpo_jasa_request')
            && $this->db->field_exists('purchasing_note', 'tbpo_jasa_request')
            && $this->db->field_exists('purchasing_revision_saved_at', 'tbpo_jasa_request')
            && $this->db->field_exists('purchasing_revision_status', 'tbpo_jasa_request')
            && $this->db->field_exists('harga_pembanding_purchasing', 'tbpo_jasa_material')
            && $this->db->field_exists('added_by_purchasing', 'tbpo_jasa_material')
            && $this->db->field_exists('harga_pembanding_purchasing', 'tbpo_jasa_scope')
            && $this->db->field_exists('added_by_purchasing', 'tbpo_jasa_scope')
            && $this->db->field_exists('keterangan_purchasing', 'tbpo_jasa_material');
    }

    public function role_allowed($context)
    {
        return in_array($context['role'], array('ADMIN', 'PIC', 'KADEP', 'PURCHASING', 'DIREKTUR_OPERASIONAL', 'DIREKTUR'), true);
    }

    public function work_statuses($context)
    {
        $map = array(
            'PIC' => array('MENUNGGU_KONFIRMASI_PIC'),
            'KADEP' => array('MENUNGGU_KADEP', 'PENDING_KADEP'),
            'PURCHASING' => array(
                'MENUNGGU_PURCHASING_AWAL', 'MENUNGGU_PURCHASING', 'REVISI_PURCHASING_KADEP',
                'REVISI_PURCHASING_DIROPS', 'REVISI_PURCHASING_DIRUT', 'MENUNGGU_PENERBITAN_PURCHASING', 'SPK_TERBIT',
            ),
            'DIREKTUR_OPERASIONAL' => array('MENUNGGU_DIRUT_OPS', 'SPK_TERBIT'),
            'DIREKTUR' => array('MENUNGGU_DIREKTUR', 'SPK_TERBIT'),
            'ADMIN' => array(
                'MENUNGGU_PURCHASING_AWAL', 'MENUNGGU_PURCHASING', 'REVISI_PURCHASING_KADEP', 'REVISI_PURCHASING_DIROPS', 'REVISI_PURCHASING_DIRUT', 'MENUNGGU_PENERBITAN_PURCHASING', 'MENUNGGU_KADEP', 'PENDING_KADEP',
                'MENUNGGU_KONFIRMASI_PIC',
                'MENUNGGU_DIRUT_OPS', 'MENUNGGU_DIREKTUR', 'SPK_TERBIT',
            ),
        );

        return isset($map[$context['role']]) ? $map[$context['role']] : array();
    }

    public function can_access_request($context, $request)
    {
        if (!$request || !$this->role_allowed($context) || !empty($request->deleted_at)) {
            return false;
        }
        if ($context['role'] === 'ADMIN' || $context['role'] === 'PURCHASING' || $context['role'] === 'DIREKTUR') {
            return true;
        }
        if ($context['role'] === 'PIC') {
            return (string) $request->kd_user === (string) $context['kode_user'];
        }
        if ($context['role'] === 'KADEP') {
            return pojasa_normalize_department($request->departemen) === $context['departemen'];
        }
        if ($context['role'] === 'DIREKTUR_OPERASIONAL') {
            return true;
        }

        return false;
    }

    public function allowed_actions($context, $request)
    {
        if (!$this->can_access_request($context, $request)) {
            return array();
        }
        $stage = pojasa_workflow_stage($request->status);
        if (!$stage) {
            return array();
        }
        if ($context['role'] !== 'ADMIN' && $context['role'] !== $stage) {
            return array();
        }
        return pojasa_workflow_actions($request->status);
    }

    public function can_edit_purchasing($context, $request)
    {
        return $this->can_access_request($context, $request)
            && in_array($context['role'], array('PURCHASING', 'ADMIN'), true)
            && in_array($request->status, array(
                'MENUNGGU_PURCHASING_AWAL', 'REVISI_PURCHASING_KADEP',
                'REVISI_PURCHASING_DIROPS', 'REVISI_PURCHASING_DIRUT',
            ), true);
    }

    public function get_latest_purchasing_review_note($requestCode)
    {
        if (!$this->db->table_exists('tbpo_jasa_log_aktivitas')) {
            return null;
        }

        return $this->db
            ->select('catatan, actor_code, actor_name, created_at')
            ->from('tbpo_jasa_log_aktivitas')
            ->where('kd_po_jasa', $requestCode)
            ->where_in('event_type', array('REVIEW_PURCHASING_DISIMPAN', 'REVISI_PURCHASING_DISIMPAN', 'REVIEW_PURCHASING_PURCHASING_DISIMPAN', 'REVISI_PURCHASING_PURCHASING_DISIMPAN'))
            ->order_by('id_log_aktivitas', 'DESC')
            ->limit(1)
            ->get()
            ->row();
    }

    public function datatable_requests($context, $params)
    {
        $statuses = $this->work_statuses($context);
        if (!$statuses) {
            return array('total' => 0, 'filtered' => 0, 'rows' => array());
        }
        $columns = array(
            0 => 'r.tgl_request', 1 => 'r.kd_po_jasa', 2 => 'r.nm_user',
            3 => 'r.departemen', 4 => 'COALESCE(v.nama_vendor, r.vendor_usulan)',
            5 => 'r.estimasi_total', 6 => 'r.status',
        );
        $placeholders = implode(',', array_fill(0, count($statuses), '?'));
        $where = array('r.deleted_at IS NULL', 'r.status IN (' . $placeholders . ')');
        $binds = $statuses;
        if ($context['role'] === 'KADEP') {
            $where[] = 'UPPER(TRIM(r.departemen)) = ?';
            $binds[] = $context['departemen'];
        }
        $baseWhere = $where;
        $baseBinds = $binds;
        $from = ' FROM tbpo_jasa_request r LEFT JOIN tbpo_jasa_vendor v ON v.kd_vendor_jasa = r.kd_vendor_jasa';
        $total = (int) $this->db->query('SELECT COUNT(*) total' . $from . ' WHERE ' . implode(' AND ', $baseWhere), $baseBinds)->row()->total;

        $filterStatus = strtoupper(trim((string) $params['status']));
        if ($filterStatus !== '' && in_array($filterStatus, $statuses, true)) {
            $where[] = 'r.status = ?';
            $binds[] = $filterStatus;
        }
        if ($params['date_from'] !== '') {
            $where[] = 'r.tgl_request >= ?';
            $binds[] = $params['date_from'];
        }
        if ($params['date_to'] !== '') {
            $where[] = 'r.tgl_request <= ?';
            $binds[] = $params['date_to'];
        }
        if ($params['search'] !== '') {
            $like = '%' . $params['search'] . '%';
            $where[] = '(r.kd_po_jasa LIKE ? OR r.nm_user LIKE ? OR r.departemen LIKE ? OR r.tujuan_pekerjaan LIKE ? OR v.nama_vendor LIKE ? OR r.vendor_usulan LIKE ?)';
            array_push($binds, $like, $like, $like, $like, $like, $like);
        }
        $filtered = (int) $this->db->query('SELECT COUNT(*) total' . $from . ' WHERE ' . implode(' AND ', $where), $binds)->row()->total;
        $order = isset($columns[$params['order_column']]) ? $columns[$params['order_column']] : 'r.id_po_jasa';
        $direction = strtolower($params['order_direction']) === 'asc' ? 'ASC' : 'DESC';
        $sql = 'SELECT r.*, COALESCE(v.nama_vendor, r.vendor_usulan) nama_vendor_tampil' . $from
            . ' WHERE ' . implode(' AND ', $where)
            . ' ORDER BY ' . $order . ' ' . $direction . ', r.id_po_jasa DESC LIMIT ?, ?';
        $rows = $this->db->query($sql, array_merge($binds, array((int) $params['start'], (int) $params['length'])))->result_array();

        return array('total' => $total, 'filtered' => $filtered, 'rows' => $rows);
    }

    public function get_request($requestCode, $context)
    {
        $this->db->select('r.*, v.nama_vendor, v.kategori_jasa');
        $this->db->from('tbpo_jasa_request r');
        $this->db->join('tbpo_jasa_vendor v', 'v.kd_vendor_jasa = r.kd_vendor_jasa', 'left');
        $this->db->where('r.kd_po_jasa', $requestCode);
        $request = $this->db->get()->row();

        return $this->can_access_request($context, $request) ? $request : null;
    }

    public function effective_revision($request)
    {
        return !empty($request->edit_revision_no) ? (int) $request->edit_revision_no : max(1, (int) $request->revision_no);
    }

    public function get_scopes($requestCode, $revisionNo)
    {
        return $this->db->order_by('line_no', 'ASC')->get_where('tbpo_jasa_scope', array(
            'kd_po_jasa' => $requestCode, 'revision_no' => (int) $revisionNo, 'is_active' => 1,
        ))->result_array();
    }

    public function get_materials($requestCode, $revisionNo)
    {
        $rows = $this->db->select('m.*, COALESCE(s.nm_satuan, m.satuan) AS satuan')
            ->from('tbpo_jasa_material m')
            ->join('tbpo_barang_nk b', 'b.id_brg_nk=m.id_brg_nk', 'left')
            ->join('tbpo_satuan s', 's.id_satuan=b.satuan', 'left')
            ->where(array('m.kd_po_jasa' => $requestCode, 'm.revision_no' => (int) $revisionNo, 'm.is_active' => 1))
            ->order_by('m.line_no', 'ASC')->get()->result_array();
        foreach ($rows as &$row) {
            $reserved = $this->db->query(
                "SELECT COALESCE(SUM(qty_allocation-qty_received-qty_released),0) qty FROM tbpo_jasa_stock_allocation WHERE id_material=? AND status_allocation IN ('ACTIVE','PARTIAL')",
                array((int) $row['id_material'])
            )->row();
            $draft = $this->db->query(
                "SELECT COALESCE(SUM(qty),0) qty FROM tbpo_jasa_draft_pembelian WHERE id_material=? AND status_draft='DRAFT'",
                array((int) $row['id_material'])
            )->row();
            $row['reserved_qty'] = max(0, (float) ($reserved ? $reserved->qty : 0));
            $row['active_draft_qty'] = max(0, (float) ($draft ? $draft->qty : 0));
            $stock = !empty($row['id_brg_nk']) ? $this->db->query('SELECT qty_ready FROM v_stockbarangnk WHERE id_brg_nk=? LIMIT 1', array((int) $row['id_brg_nk']))->row() : null;
            $stockReserved = !empty($row['id_brg_nk']) ? $this->db->query(
                "SELECT COALESCE(SUM(qty_allocation-qty_received-qty_released),0) qty FROM tbpo_jasa_stock_allocation WHERE id_brg_nk=? AND status_allocation IN ('ACTIVE','PARTIAL')",
                array((int) $row['id_brg_nk'])
            )->row() : null;
            $row['qty_ready'] = max(0, (float) ($stock ? $stock->qty_ready : 0) - (float) ($stockReserved ? $stockReserved->qty : 0));
            // qty_ready adalah stok bebas (seluruh reservasi sudah dikurangi).
            // Reservasi untuk material ini ditambahkan kembali sebagai pemenuhan
            // agar draft hanya berisi kekurangan yang benar-benar perlu dibeli.
            $row['remaining_need'] = max(0, round((float) $row['qty_kebutuhan'] - $row['qty_ready'] - $row['reserved_qty'] - $row['active_draft_qty'], 2));
            $row['fulfillment_status'] = $row['remaining_need'] <= 0.000001
                ? 'TERPENUHI' : (($row['reserved_qty'] + $row['active_draft_qty']) > 0 ? 'SEBAGIAN' : 'BELUM_DIRENCANAKAN');
            $row['source_status'] = strtoupper((string) $row['reference_type']) === 'MASTER' && !empty($row['id_brg_nk'])
                ? 'STOK PURCHASING NON-KOMERSIL' : 'BARANG MASTER MANUAL';
            $row['computed_source'] = $row['source_status'];
            $row['planning_locked'] = ($row['reserved_qty'] + $row['active_draft_qty']) > 0;
        }
        unset($row);
        return $rows;
    }

    public function get_purchasing_totals($requestCode, $revisionNo)
    {
        $scope = $this->db->query(
            'SELECT COALESCE(SUM(qty * CASE WHEN COALESCE(harga_pembanding_purchasing, 0) > 0 THEN harga_pembanding_purchasing ELSE harga_estimasi END), 0) total FROM tbpo_jasa_scope WHERE kd_po_jasa=? AND revision_no=? AND is_active=1',
            array($requestCode, (int) $revisionNo)
        )->row();
        $material = $this->db->query(
            'SELECT COALESCE(SUM(qty_kebutuhan * CASE WHEN COALESCE(harga_pembanding_purchasing, 0) > 0 THEN harga_pembanding_purchasing ELSE harga_estimasi END), 0) total FROM tbpo_jasa_material WHERE kd_po_jasa=? AND revision_no=? AND is_active=1',
            array($requestCode, (int) $revisionNo)
        )->row();
        $scopeTotal = (float) ($scope ? $scope->total : 0);
        $materialTotal = (float) ($material ? $material->total : 0);

        return array('jasa' => $scopeTotal, 'material' => $materialTotal, 'grand_total' => $scopeTotal + $materialTotal);
    }

    public function get_documents($requestCode)
    {
        return $this->db->select('d.*,u.nama_user AS uploader_name')
            ->from('tbpo_jasa_dokumen d')->join('tbpo_user u', 'u.id_user=d.uploaded_by', 'left')
            ->where(array('d.kd_po_jasa' => $requestCode, 'd.is_active' => 1))
            ->order_by('d.id_dokumen', 'DESC')->get()->result_array();
    }

    public function get_document($documentId, $context)
    {
        $this->db->select('d.*, r.departemen, r.kd_user, r.status, r.deleted_at, u.nama_user AS uploader_name');
        $this->db->from('tbpo_jasa_dokumen d');
        $this->db->join('tbpo_jasa_request r', 'r.kd_po_jasa = d.kd_po_jasa');
        $this->db->join('tbpo_user u', 'u.id_user = d.uploaded_by', 'left');
        $this->db->where(array('d.id_dokumen' => (int) $documentId, 'd.is_active' => 1));
        $document = $this->db->get()->row();

        return $this->can_access_request($context, $document) ? $document : null;
    }

    public function document_can_preview($document)
    {
        $extension = strtolower(pathinfo((string) $document->file_original, PATHINFO_EXTENSION));
        return in_array($extension, array('jpg', 'jpeg', 'png', 'pdf'), true)
            && (strpos((string) $document->mime_type, 'image/') === 0 || $document->mime_type === 'application/pdf');
    }

    public function get_planning_detail($requestCode, $materialId, $type)
    {
        $material = $this->db->get_where('tbpo_jasa_material', array(
            'kd_po_jasa' => $requestCode, 'id_material' => (int) $materialId, 'is_active' => 1,
        ))->row_array();
        if (!$material) return array();
        if ($type === 'allocation') {
            return $this->db->select('a.*,b.kd_barang,b.nama_barang,u.nama_user AS created_name')
                ->from('tbpo_jasa_stock_allocation a')->join('tbpo_barang_nk b', 'b.id_brg_nk=a.id_brg_nk', 'left')
                ->join('tbpo_user u', 'u.id_user=a.allocated_by', 'left')
                ->where('a.id_material', (int) $materialId)->order_by('a.id_allocation', 'DESC')->get()->result_array();
        }
        if ($type === 'draft') {
            return $this->db->select('d.*,v.nama_vendor,u.nama_user AS created_name')
                ->from('tbpo_jasa_draft_pembelian d')->join('tbpo_jasa_vendor v', 'v.id_vendor_jasa=d.id_vendor_jasa', 'left')
                ->join('tbpo_user u', 'u.id_user=d.created_by', 'left')
                ->where('d.id_material', (int) $materialId)->order_by('d.id_draft_pembelian', 'DESC')->get()->result_array();
        }
        return array();
    }

    public function get_active_purchase_drafts($requestCode, $revisionNo)
    {
        return $this->db->select('d.id_draft_pembelian,d.id_material,d.qty,d.harga_estimasi,d.keterangan,d.created_at,d.updated_at,d.version,m.nama_material,m.satuan')
            ->from('tbpo_jasa_draft_pembelian d')
            ->join('tbpo_jasa_material m', 'm.id_material=d.id_material AND m.kd_po_jasa=d.kd_po_jasa AND m.revision_no=d.revision_no AND m.is_active=1')
            ->where(array('d.kd_po_jasa' => $requestCode, 'd.revision_no' => (int) $revisionNo, 'd.status_draft' => 'DRAFT'))
            ->order_by('m.line_no', 'ASC')->get()->result_array();
    }

    public function get_history($requestCode)
    {
        return array(
            'approvals' => $this->db->order_by('id_approval', 'DESC')->get_where('tbpo_jasa_approval', array('kd_po_jasa' => $requestCode))->result_array(),
            'revisions' => $this->db->order_by('id_revisi', 'DESC')->get_where('tbpo_jasa_revisi', array('kd_po_jasa' => $requestCode))->result_array(),
            'logs' => $this->db->order_by('id_log_aktivitas', 'DESC')->get_where('tbpo_jasa_log_aktivitas', array('kd_po_jasa' => $requestCode))->result_array(),
        );
    }

    public function get_active_vendors()
    {
        return $this->db->select('kd_vendor_jasa, nama_vendor, kategori_jasa')
            ->order_by('nama_vendor', 'ASC')->get_where('tbpo_jasa_vendor', array('status_vendor' => 'AKTIF', 'deleted_at' => null))->result_array();
    }

    public function save_purchasing_review($context, $requestCode, $payload, $expectedStatus, $expectedVersion)
    {
        $this->db->trans_begin();
        $request = $this->db->query('SELECT * FROM tbpo_jasa_request WHERE kd_po_jasa = ? FOR UPDATE', array($requestCode))->row();
        if (!$this->can_edit_purchasing($context, $request)) {
            $this->db->trans_rollback();
            return array('success' => false, 'code' => 'FORBIDDEN');
        }
        if ($request->status !== $expectedStatus || (int) $request->status_version !== (int) $expectedVersion) {
            $this->db->trans_rollback();
            return array('success' => false, 'code' => 'CONCURRENT_UPDATE');
        }
        $isRevision = in_array($request->status, array(
            'REVISI_PURCHASING_KADEP', 'REVISI_PURCHASING_DIROPS', 'REVISI_PURCHASING_DIRUT',
        ), true);
        $segment = isset($payload['segment']) ? (string) $payload['segment'] : '';
        if (!in_array($segment, array('purchasing', 'scope', 'material'), true)) {
            $this->db->trans_rollback();
            return array('success' => false, 'code' => 'VALIDATION_ERROR');
        }
        if ($isRevision && $segment === 'purchasing' && trim((string) $payload['note']) === '') {
            $this->db->trans_rollback();
            return array('success' => false, 'code' => 'NOTE_REQUIRED');
        }
        $sourceRevision = (int) $request->revision_no;
        $targetRevision = $isRevision
            ? (!empty($request->edit_revision_no) ? (int) $request->edit_revision_no : $sourceRevision + 1)
            : $sourceRevision;
        if ($isRevision) {
            $this->copyRevisionRows($requestCode, $sourceRevision, $targetRevision, $context);
        }
        if ($segment === 'scope') {
            foreach ($payload['deleted_scopes'] as $deleted) $this->archiveReviewRow('tbpo_jasa_scope', 'id_scope', $deleted, $context);
            foreach ($payload['scopes'] as $row) $this->saveReviewScope($requestCode, $targetRevision, $row, $context);
        }
        if ($segment === 'material') {
            foreach ($payload['deleted_materials'] as $deleted) $this->archiveReviewRow('tbpo_jasa_material', 'id_material', $deleted, $context);
            foreach ($payload['materials'] as $row) $this->saveReviewMaterial($requestCode, $targetRevision, $row, $context);
        }
        $scopeTotal = (float) $this->db->query(
            'SELECT COALESCE(SUM(total_estimasi),0) total FROM tbpo_jasa_scope WHERE kd_po_jasa=? AND revision_no=? AND is_active=1',
            array($requestCode, $targetRevision)
        )->row()->total;
        $materialTotal = (float) $this->db->query(
            'SELECT COALESCE(SUM(total_estimasi),0) total FROM tbpo_jasa_material WHERE kd_po_jasa=? AND revision_no=? AND is_active=1',
            array($requestCode, $targetRevision)
        )->row()->total;
        $update = array(
            'estimasi_purchasing' => round($scopeTotal + $materialTotal, 2),
            'status_version' => (int) $request->status_version + 1,
        );
        if ($segment === 'purchasing') {
            $update['kd_vendor_jasa'] = $payload['kd_vendor_jasa'] !== '' ? $payload['kd_vendor_jasa'] : $request->kd_vendor_jasa;
            $update['purchasing_note'] = trim((string) $payload['note']) ?: null;
            $update['tgl_mulai_pekerjaan'] = $payload['tgl_mulai_pekerjaan'];
            $update['tgl_selesai_pekerjaan'] = $payload['tgl_selesai_pekerjaan'];
            $update['tgl_target'] = $payload['tgl_selesai_pekerjaan'];
            $update['reviewed_by_purchasing'] = (string) $context['kode_user'];
            $update['reviewed_at_purchasing'] = date('Y-m-d H:i:s');
        }
        if ($isRevision) {
            $update['edit_revision_no'] = $targetRevision;
        }
        if ($isRevision && $segment === 'purchasing') {
            $update['purchasing_revision_saved_at'] = date('Y-m-d H:i:s');
            $update['purchasing_revision_status'] = $request->status;
        }
        $this->db->where(array('id_po_jasa' => (int) $request->id_po_jasa, 'status_version' => (int) $request->status_version));
        $this->db->update('tbpo_jasa_request', $update);
        if ($this->db->affected_rows() !== 1) {
            $this->db->trans_rollback();
            return array('success' => false, 'code' => 'CONCURRENT_UPDATE');
        }
        $this->M_PojasaCore->append_activity_log(array(
            'kd_po_jasa' => $requestCode, 'revision_no' => $targetRevision,
            'event_type' => ($isRevision ? 'REVISI_PURCHASING_' : 'REVIEW_PURCHASING_') . strtoupper($segment) . '_DISIMPAN',
            'from_status' => $request->status, 'to_status' => $request->status,
            'actor_id' => (int) $context['id_user'], 'actor_code' => $context['kode_user'],
            'actor_name' => $context['nama_user'], 'actor_role' => $context['role'],
            'actor_departemen' => $context['departemen'], 'catatan' => $payload['note'],
        ));
        if ($this->db->trans_status() === false) {
            $this->db->trans_rollback();
            return array('success' => false, 'code' => 'DATABASE_ERROR');
        }
        $this->db->trans_commit();

        return array(
            'success' => true, 'code' => 'SAVED', 'status' => $request->status,
            'status_version' => (int) $request->status_version + 1,
            'revision_no' => $targetRevision,
            'totals' => array('jasa' => $scopeTotal, 'material' => $materialTotal, 'grand_total' => $scopeTotal + $materialTotal),
        );
    }

    private function copyRevisionRows($requestCode, $sourceRevision, $targetRevision, $context)
    {
        $scopeExists = (int) $this->db->where(array('kd_po_jasa' => $requestCode, 'revision_no' => $targetRevision))->count_all_results('tbpo_jasa_scope');
        if ($scopeExists === 0) {
            $this->db->query(
                'INSERT INTO tbpo_jasa_scope (kd_po_jasa,revision_no,line_no,jenis_scope,nama_scope,deskripsi,qty,satuan,harga_estimasi,harga_pembanding_purchasing,total_estimasi,harga_nyata,keterangan_purchasing,added_by_purchasing,is_active) '
                . 'SELECT kd_po_jasa,?,line_no,jenis_scope,nama_scope,deskripsi,qty,satuan,harga_estimasi,harga_pembanding_purchasing,total_estimasi,harga_nyata,keterangan_purchasing,added_by_purchasing,1 '
                . 'FROM tbpo_jasa_scope WHERE kd_po_jasa=? AND revision_no=? AND is_active=1',
                array($targetRevision, $requestCode, $sourceRevision)
            );
        }
        $materialExists = (int) $this->db->where(array('kd_po_jasa' => $requestCode, 'revision_no' => $targetRevision))->count_all_results('tbpo_jasa_material');
        if ($materialExists === 0) {
            $this->db->query(
                'INSERT INTO tbpo_jasa_material (kd_po_jasa,revision_no,line_no,id_brg_nk,reference_type,nama_material,deskripsi,qty_kebutuhan,satuan,harga_estimasi,harga_pembanding_purchasing,total_estimasi,sumber_material,keterangan_purchasing,created_by,added_by_purchasing,is_active) '
                . 'SELECT kd_po_jasa,?,line_no,id_brg_nk,reference_type,nama_material,deskripsi,qty_kebutuhan,satuan,harga_estimasi,harga_pembanding_purchasing,total_estimasi,sumber_material,keterangan_purchasing,?,added_by_purchasing,1 '
                . 'FROM tbpo_jasa_material WHERE kd_po_jasa=? AND revision_no=? AND is_active=1',
                array($targetRevision, (int) $context['id_user'], $requestCode, $sourceRevision)
            );
        }
    }

    private function scopeValue($requestCode, $revisionNo, $lineNo, $field)
    {
        $row = $this->db->select($field)->get_where('tbpo_jasa_scope', array('kd_po_jasa' => $requestCode, 'revision_no' => (int) $revisionNo, 'line_no' => (int) $lineNo))->row_array();
        return $row && array_key_exists($field, $row) ? $row[$field] : null;
    }

    private function materialValue($requestCode, $revisionNo, $lineNo, $field)
    {
        $row = $this->db->select($field)->get_where('tbpo_jasa_material', array('kd_po_jasa' => $requestCode, 'revision_no' => (int) $revisionNo, 'line_no' => (int) $lineNo))->row_array();
        return $row && array_key_exists($field, $row) ? $row[$field] : null;
    }

    private function archiveReviewRow($table, $key, $deleted, $context)
    {
        $this->db->where($key, (int) $deleted['id'])->update($table, array('is_active' => 0, 'archived_at' => date('Y-m-d H:i:s'), 'archived_by' => (int) $context['id_user'], 'deleted_reason_purchasing' => $deleted['reason']));
    }

    private function saveReviewScope($requestCode, $revisionNo, $row, $context)
    {
        $basePrice = $row['is_new'] ? (float) $row['harga_pembanding'] : null;
        $data = array('nama_scope' => $row['nama_scope'], 'deskripsi' => $row['deskripsi'], 'qty' => $row['qty'], 'satuan' => $row['satuan'], 'harga_pembanding_purchasing' => $row['harga_pembanding'], 'keterangan_purchasing' => $row['keterangan_purchasing']);
        if ($row['is_new']) {
            $data += array('kd_po_jasa' => $requestCode, 'revision_no' => $revisionNo, 'line_no' => $this->nextLine('tbpo_jasa_scope', $requestCode, $revisionNo), 'jenis_scope' => 'JASA', 'harga_estimasi' => $basePrice, 'total_estimasi' => round($basePrice * $row['qty'], 2), 'added_by_purchasing' => 1, 'is_active' => 1);
            $this->db->insert('tbpo_jasa_scope', $data); return;
        }
        $current = $this->db->get_where('tbpo_jasa_scope', array('id_scope' => (int) $row['id'], 'kd_po_jasa' => $requestCode, 'revision_no' => $revisionNo, 'is_active' => 1))->row_array();
        if ($current) { $data['total_estimasi'] = round((float) $current['harga_estimasi'] * $row['qty'], 2); $this->db->where('id_scope', (int) $row['id'])->update('tbpo_jasa_scope', $data); }
    }

    private function saveReviewMaterial($requestCode, $revisionNo, $row, $context)
    {
        $basePrice = $row['is_new'] ? (float) $row['harga_pembanding'] : null;
        $data = array('nama_material' => $row['nama_material'], 'deskripsi' => $row['deskripsi'], 'qty_kebutuhan' => $row['qty'], 'satuan' => $row['satuan'], 'harga_pembanding_purchasing' => $row['harga_pembanding'], 'keterangan_purchasing' => $row['keterangan_purchasing']);
        if ($row['is_new']) {
            $data += array('kd_po_jasa' => $requestCode, 'revision_no' => $revisionNo, 'line_no' => $this->nextLine('tbpo_jasa_material', $requestCode, $revisionNo), 'id_brg_nk' => null, 'reference_type' => 'MANUAL', 'harga_estimasi' => $basePrice, 'total_estimasi' => round($basePrice * $row['qty'], 2), 'sumber_material' => 'MANUAL', 'created_by' => (int) $context['id_user'], 'added_by_purchasing' => 1, 'is_active' => 1);
            $this->db->insert('tbpo_jasa_material', $data); return;
        }
        $current = $this->db->get_where('tbpo_jasa_material', array('id_material' => (int) $row['id'], 'kd_po_jasa' => $requestCode, 'revision_no' => $revisionNo, 'is_active' => 1))->row_array();
        if ($current) { $data['total_estimasi'] = round((float) $current['harga_estimasi'] * $row['qty'], 2); $this->db->where('id_material', (int) $row['id'])->update('tbpo_jasa_material', $data); }
    }

    private function nextLine($table, $requestCode, $revisionNo)
    {
        $row = $this->db->query('SELECT COALESCE(MAX(line_no),0)+1 AS line_no FROM ' . $table . ' WHERE kd_po_jasa=? AND revision_no=?', array($requestCode, $revisionNo))->row();
        return (int) $row->line_no;
    }

    private function buildPurchasingNote($requestCode, $revisionNo)
    {
        $items = array();
        foreach ($this->db->select('nama_scope,keterangan_purchasing')->get_where('tbpo_jasa_scope', array('kd_po_jasa' => $requestCode, 'revision_no' => $revisionNo, 'is_active' => 1))->result_array() as $row) {
            if (trim((string) $row['keterangan_purchasing']) !== '') $items[] = $row['nama_scope'] . ' - ' . $row['keterangan_purchasing'];
        }
        foreach ($this->db->select('nama_material,keterangan_purchasing')->get_where('tbpo_jasa_material', array('kd_po_jasa' => $requestCode, 'revision_no' => $revisionNo, 'is_active' => 1))->result_array() as $row) {
            if (trim((string) $row['keterangan_purchasing']) !== '') $items[] = $row['nama_material'] . ' - ' . $row['keterangan_purchasing'];
        }
        return implode("\n", array_map(function ($item, $index) { return ($index + 1) . '. ' . $item; }, $items, array_keys($items)));
    }
}
