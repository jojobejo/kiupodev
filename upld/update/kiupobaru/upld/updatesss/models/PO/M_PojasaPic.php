<?php
defined('BASEPATH') or exit('No direct script access allowed');

class M_PojasaPic extends CI_Model
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('PO/M_PojasaCore');
        $this->load->helper(array('pojasa_authorization', 'pojasa_status'));
    }

    public function schema_ready()
    {
        $columns = array(
            'tbpo_jasa_request' => array('vendor_usulan', 'tgl_mulai_pekerjaan', 'tgl_selesai_pekerjaan', 'catatan_pic', 'estimasi_purchasing', 'edit_revision_no', 'draft_saved_at', 'submitted_at'),
            'tbpo_jasa_scope' => array('is_active', 'archived_at', 'archived_by'),
            'tbpo_jasa_material' => array('revision_no', 'line_no', 'harga_estimasi', 'total_estimasi', 'is_active', 'archived_at', 'archived_by', 'reference_type', 'id_usulan_barang', 'kd_barang_snapshot'),
            'tbpo_jasa_material_usulan' => array('kode_usulan', 'status_usulan', 'kd_barang_hasil', 'id_brg_nk_hasil'),
        );
        foreach ($columns as $table => $fields) {
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

    public function get_active_vendors()
    {
        $this->db->select('kd_vendor_jasa, nama_vendor, kategori_jasa');
        $this->db->from('tbpo_jasa_vendor');
        $this->db->where('status_vendor', 'AKTIF');
        $this->db->where('deleted_at IS NULL', null, false);
        $this->db->order_by('nama_vendor', 'ASC');

        return $this->db->get()->result_array();
    }

    public function active_vendor_exists($vendorCode)
    {
        return $this->db
            ->where('kd_vendor_jasa', $vendorCode)
            ->where('status_vendor', 'AKTIF')
            ->where('deleted_at IS NULL', null, false)
            ->count_all_results('tbpo_jasa_vendor') > 0;
    }

    /** Server-side Select2 source. Master specifications are authoritative and read-only for PIC. */
    public function search_material_catalog($term, $page = 1)
    {
        $term = trim((string) $term);
        $page = max(1, (int) $page);
        $limit = 20;
        $offset = ($page - 1) * $limit;
        $this->db->select('b.id_brg_nk, b.kd_barang, b.kd_br_adm, b.nama_barang, b.descnk, COALESCE(s.nm_satuan, \'\') AS satuan');
        $this->db->from('tbpo_barang_nk b');
        $this->db->join('tbpo_satuan s', 's.id_satuan = b.satuan', 'left');
        if ($this->db->field_exists('status_master', 'tbpo_barang_nk')) {
            $this->db->where('b.status_master', 'ACTIVE');
        }
        if ($term !== '') {
            $this->db->group_start()
                ->like('b.kd_barang', $term)
                ->or_like('b.kd_br_adm', $term)
                ->or_like('b.nama_barang', $term)
                ->or_like('b.descnk', $term)
                ->group_end();
        }
        $this->db->order_by('b.nama_barang', 'ASC');
        $rows = $this->db->limit($limit + 1, $offset)->get()->result_array();
        $more = count($rows) > $limit;
        if ($more) array_pop($rows);
        foreach ($rows as &$row) {
            $stock = $this->db->select('qty_ready')->get_where('v_stockbarangnk', array('id_brg_nk' => (int) $row['id_brg_nk']))->row();
            $reserved = $this->db->query("SELECT COALESCE(SUM(qty_allocation-qty_received-qty_released),0) qty FROM tbpo_jasa_stock_allocation WHERE id_brg_nk=? AND status_allocation IN ('ACTIVE','PARTIAL')", array((int) $row['id_brg_nk']))->row();
            $row['qty_ready'] = max(0, (float) ($stock ? $stock->qty_ready : 0) - (float) ($reserved ? $reserved->qty : 0));
        }
        unset($row);
        return array('items' => $rows, 'more' => $more);
    }

    public function get_catalog_material($id)
    {
        $query = $this->db->select('b.id_brg_nk, b.kd_barang, b.kd_br_adm, b.nama_barang, b.descnk, COALESCE(s.nm_satuan, \'\') AS satuan')
            ->from('tbpo_barang_nk b')->join('tbpo_satuan s', 's.id_satuan = b.satuan', 'left')->where('b.id_brg_nk', (int) $id);
        if ($this->db->field_exists('status_master', 'tbpo_barang_nk')) $query->where('b.status_master', 'ACTIVE');
        return $query->limit(1)->get()->row_array();
    }

    public function datatable_requests($context, $params)
    {
        $hasPurchaseIntegration = $this->db->table_exists('tbpo_jasa_purchase_submission');
        $columns = array(
            0 => 'r.tgl_request',
            1 => 'COALESCE(v.nama_vendor, r.vendor_usulan)',
            2 => 'r.estimasi_total',
            3 => 'r.status',
            4 => $hasPurchaseIntegration ? 'pn.status' : 'r.id_po_jasa',
        );
        $baseWhere = array('r.deleted_at IS NULL');
        $binds = array();
        if ($context['role'] !== 'ADMIN') {
            $baseWhere[] = 'r.kd_user = ?';
            $binds[] = $context['kode_user'];
        }
        $totalSql = 'SELECT COUNT(*) AS total FROM tbpo_jasa_request r WHERE ' . implode(' AND ', $baseWhere);
        $total = (int) $this->db->query($totalSql, $binds)->row()->total;

        $where = $baseWhere;
        $filteredBinds = $binds;
        $status = strtoupper(trim((string) $params['status']));
        if ($status !== '' && pojasa_status_is_valid($status)) {
            $where[] = 'r.status = ?';
            $filteredBinds[] = $status;
        }
        if ($params['date_from'] !== '') {
            $where[] = 'r.tgl_request >= ?';
            $filteredBinds[] = $params['date_from'];
        }
        if ($params['date_to'] !== '') {
            $where[] = 'r.tgl_request <= ?';
            $filteredBinds[] = $params['date_to'];
        }
        $search = trim((string) $params['search']);
        if ($search !== '') {
            $like = '%' . $search . '%';
            $where[] = '(r.kd_po_jasa LIKE ? OR r.tujuan_pekerjaan LIKE ? OR r.lokasi_pekerjaan LIKE ? OR r.vendor_usulan LIKE ? OR v.nama_vendor LIKE ?)';
            array_push($filteredBinds, $like, $like, $like, $like, $like);
        }

        $purchaseJoin = $hasPurchaseIntegration
            ? ' LEFT JOIN tbpo_jasa_purchase_submission ps ON ps.kd_po_jasa=r.kd_po_jasa LEFT JOIN tbpo_po_nk pn ON pn.id_po_nk=ps.id_po_nk'
            : '';
        $fromSql = ' FROM tbpo_jasa_request r LEFT JOIN tbpo_jasa_vendor v ON v.kd_vendor_jasa = r.kd_vendor_jasa' . $purchaseJoin . ' WHERE ' . implode(' AND ', $where);
        $filtered = (int) $this->db->query('SELECT COUNT(*) AS total' . $fromSql, $filteredBinds)->row()->total;
        $orderColumn = isset($columns[$params['order_column']]) ? $columns[$params['order_column']] : 'r.id_po_jasa';
        $orderDirection = strtolower($params['order_direction']) === 'asc' ? 'ASC' : 'DESC';
        $sql = 'SELECT r.*, COALESCE(v.nama_vendor, r.vendor_usulan) AS nama_vendor_tampil' . ($purchaseJoin ? ', ps.kd_po_nk, pn.status AS po_pembelian_status' : '') . $fromSql
            . ' ORDER BY ' . $orderColumn . ' ' . $orderDirection . ', r.id_po_jasa DESC LIMIT ?, ?';
        $dataBinds = array_merge($filteredBinds, array((int) $params['start'], (int) $params['length']));
        $rows = $this->db->query($sql, $dataBinds)->result_array();

        return array('total' => $total, 'filtered' => $filtered, 'rows' => $rows);
    }

    public function get_request($requestCode, $context)
    {
        $this->db->select('r.*, v.nama_vendor, v.kategori_jasa');
        $this->db->from('tbpo_jasa_request r');
        $this->db->join('tbpo_jasa_vendor v', 'v.kd_vendor_jasa = r.kd_vendor_jasa', 'left');
        $this->db->where('r.kd_po_jasa', $requestCode);
        $this->db->where('r.deleted_at IS NULL', null, false);
        if ($context['role'] !== 'ADMIN') {
            $this->db->where('r.kd_user', $context['kode_user']);
        }

        return $this->db->get()->row();
    }

    public function can_edit($request, $context)
    {
        return $request
            && pojasa_can_act_on_request($context, $request, 'PIC_OWNER')
            && in_array($request->status, array('DRAFT', 'REVISI_PIC'), true)
            && empty($request->deleted_at);
    }

    public function effective_revision($request)
    {
        return !empty($request->edit_revision_no)
            ? (int) $request->edit_revision_no
            : max(1, (int) $request->revision_no);
    }

    public function get_scopes($requestCode, $revisionNo)
    {
        $this->db->from('tbpo_jasa_scope');
        $this->db->where(array('kd_po_jasa' => $requestCode, 'revision_no' => (int) $revisionNo, 'is_active' => 1));
        $this->db->order_by('line_no', 'ASC');
        return $this->db->get()->result_array();
    }

    public function get_materials($requestCode, $revisionNo)
    {
        $this->db->select('m.*, b.kd_barang, b.kd_br_adm, COALESCE(s.nm_satuan, m.satuan) AS satuan_tampil, u.kode_usulan, u.status_usulan');
        $this->db->from('tbpo_jasa_material m');
        $this->db->join('tbpo_barang_nk b', 'b.id_brg_nk = m.id_brg_nk', 'left');
        $this->db->join('tbpo_satuan s', 's.id_satuan = b.satuan', 'left');
        $this->db->join('tbpo_jasa_material_usulan u', 'u.id_usulan_barang = m.id_usulan_barang', 'left');
        $this->db->where(array('m.kd_po_jasa' => $requestCode, 'm.revision_no' => (int) $revisionNo, 'm.is_active' => 1));
        $this->db->order_by('m.line_no', 'ASC');
        return $this->db->get()->result_array();
    }

    public function get_documents($requestCode)
    {
        $this->db->from('tbpo_jasa_dokumen');
        $this->db->where(array('kd_po_jasa' => $requestCode, 'is_active' => 1));
        $this->db->order_by('id_dokumen', 'DESC');
        return $this->db->get()->result_array();
    }

    public function get_detail_history($requestCode)
    {
        return array(
            'approvals' => $this->db->order_by('id_approval', 'DESC')->get_where('tbpo_jasa_approval', array('kd_po_jasa' => $requestCode))->result_array(),
            'revisions' => $this->db->order_by('id_revisi', 'DESC')->get_where('tbpo_jasa_revisi', array('kd_po_jasa' => $requestCode))->result_array(),
            'progress' => $this->db->order_by('id_progress_jasa', 'DESC')->get_where('tbpo_jasa_progress', array('kd_po_jasa' => $requestCode))->result_array(),
            'costs' => $this->db->order_by('id_biaya_aktual', 'DESC')->get_where('tbpo_jasa_biaya_aktual', array('kd_po_jasa' => $requestCode))->result_array(),
            'logs' => $this->db->order_by('id_log_aktivitas', 'DESC')->get_where('tbpo_jasa_log_aktivitas', array('kd_po_jasa' => $requestCode))->result_array(),
        );
    }

    public function save_draft($context, $requestCode, $header, $scopes, $materials)
    {
        $this->db->trans_begin();
        $isNew = $requestCode === '';
        $request = null;
        $revisionNo = 1;
        if ($isNew) {
            $requestCode = $this->M_PojasaCore->generate_request_number();
            if (!$requestCode) {
                $this->db->trans_rollback();
                return array('success' => false, 'code' => 'NUMBER_ERROR');
            }
            $header = array_merge($header, array(
                'kd_po_jasa' => $requestCode,
                'kd_user' => $context['kode_user'],
                'nm_user' => $context['nama_user'],
                'departemen' => $context['departemen'],
                'status' => 'DRAFT',
                'status_version' => 1,
                'revision_no' => 1,
                'requires_dirut_ops' => 1,
                'draft_saved_at' => date('Y-m-d H:i:s'),
            ));
            $this->db->insert('tbpo_jasa_request', $header);
        } else {
            $request = $this->db->query('SELECT * FROM tbpo_jasa_request WHERE kd_po_jasa = ? FOR UPDATE', array($requestCode))->row();
            if (!$this->can_edit($request, $context)) {
                $this->db->trans_rollback();
                return array('success' => false, 'code' => 'EDIT_FORBIDDEN');
            }
            $revisionNo = $this->effective_revision($request);
            if ($request->status === 'REVISI_PIC' && empty($request->edit_revision_no)) {
                $revisionNo = (int) $request->revision_no + 1;
                $header['edit_revision_no'] = $revisionNo;
            }
            $header['requires_dirut_ops'] = 1;
            $header['draft_saved_at'] = date('Y-m-d H:i:s');
            $this->db->where('id_po_jasa', (int) $request->id_po_jasa);
            $this->db->update('tbpo_jasa_request', $header);
        }

        $scopeTotal = $this->syncScopes($requestCode, $revisionNo, $scopes, $context);
        $materialTotal = $this->syncMaterials($requestCode, $revisionNo, $materials, $context);
        $this->db->where('kd_po_jasa', $requestCode);
        $this->db->update('tbpo_jasa_request', array(
            'estimasi_total_jasa' => $scopeTotal,
            'estimasi_total_bahan' => $materialTotal,
            'estimasi_total' => $scopeTotal + $materialTotal,
            'estimasi_purchasing' => null,
        ));
        $this->appendLog($requestCode, $revisionNo, $isNew ? 'DRAFT_DIBUAT' : 'DRAFT_DISIMPAN', $isNew ? null : $request->status, $isNew ? 'DRAFT' : $request->status, $context, 'Draft request jasa disimpan.');

        if ($this->db->trans_status() === false) {
            $this->db->trans_rollback();
            return array('success' => false, 'code' => 'DATABASE_ERROR');
        }
        $this->db->trans_commit();

        return array(
            'success' => true,
            'code' => $isNew ? 'CREATED' : 'UPDATED',
            'request_code' => $requestCode,
            'status' => $isNew ? 'DRAFT' : $request->status,
            'revision_no' => $revisionNo,
            'totals' => array('jasa' => $scopeTotal, 'material' => $materialTotal, 'grand_total' => $scopeTotal + $materialTotal),
        );
    }

    public function submit_request($context, $requestCode, $allowWithoutDocuments = false)
    {
        $this->db->trans_begin();
        $request = $this->db->query('SELECT * FROM tbpo_jasa_request WHERE kd_po_jasa = ? FOR UPDATE', array($requestCode))->row();
        if (!$this->can_edit($request, $context)) {
            $this->db->trans_rollback();
            return array('success' => false, 'code' => 'EDIT_FORBIDDEN');
        }
        $revisionNo = $this->effective_revision($request);
        if ($request->status === 'REVISI_PIC' && empty($request->edit_revision_no)) {
            $this->db->trans_rollback();
            return array('success' => false, 'code' => 'SUBMIT_VALIDATION', 'errors' => array(
                'revision' => 'Simpan perubahan revisi terlebih dahulu sebelum diajukan ulang.',
            ));
        }
        $validation = $this->validateForSubmit($request, $revisionNo, $allowWithoutDocuments);
        if ($validation !== true) {
            $this->db->trans_rollback();
            return array('success' => false, 'code' => 'SUBMIT_VALIDATION', 'errors' => $validation);
        }

        $fromStatus = $request->status;
        $update = array(
            'status' => 'MENUNGGU_PURCHASING_AWAL',
            'status_version' => (int) $request->status_version + 1,
            'submitted_at' => date('Y-m-d H:i:s'),
            'edit_revision_no' => null,
        );
        if ($fromStatus === 'REVISI_PIC') {
            $update['revision_no'] = $revisionNo;
            $this->db->where(array(
                'kd_po_jasa' => $requestCode,
                'revision_no' => $revisionNo,
                'submitted_at' => null,
            ));
            $this->db->update('tbpo_jasa_revisi', array(
                'submitted_by' => (int) $context['id_user'],
                'submitted_at' => date('Y-m-d H:i:s'),
            ));
        }
        $this->db->where('id_po_jasa', (int) $request->id_po_jasa);
        $this->db->where('status_version', (int) $request->status_version);
        $this->db->update('tbpo_jasa_request', $update);
        if ($this->db->affected_rows() !== 1) {
            $this->db->trans_rollback();
            return array('success' => false, 'code' => 'CONCURRENT_UPDATE');
        }

        $toStatus = $update['status'];
        $this->appendLog($requestCode, $revisionNo, $fromStatus === 'DRAFT' ? 'REQUEST_DIAJUKAN' : 'REVISI_DIAJUKAN_ULANG', $fromStatus, $toStatus, $context, $fromStatus === 'DRAFT' ? 'Request jasa diajukan kepada Purchasing untuk pemeriksaan awal.' : 'PIC mengajukan ulang revisi kepada Purchasing untuk pemeriksaan awal.');
        $this->notifyInitialPurchasing($requestCode, $toStatus);
        if ($this->db->trans_status() === false) {
            $this->db->trans_rollback();
            return array('success' => false, 'code' => 'DATABASE_ERROR');
        }
        $this->db->trans_commit();

        return array('success' => true, 'code' => 'SUBMITTED', 'status' => $toStatus);
    }

    public function soft_delete_draft($context, $requestCode)
    {
        $this->db->trans_begin();
        $request = $this->db->query('SELECT * FROM tbpo_jasa_request WHERE kd_po_jasa = ? FOR UPDATE', array($requestCode))->row();
        if (!$request || $request->status !== 'DRAFT' || !pojasa_can_act_on_request($context, $request, 'PIC_OWNER') || !empty($request->deleted_at)) {
            $this->db->trans_rollback();
            return array('success' => false, 'code' => 'DELETE_FORBIDDEN');
        }
        $this->appendLog($requestCode, (int) $request->revision_no, 'DRAFT_DIHAPUS', 'DRAFT', 'DRAFT', $context, 'Draft disembunyikan melalui soft delete.');
        $this->db->where('id_po_jasa', (int) $request->id_po_jasa);
        $this->db->update('tbpo_jasa_request', array(
            'deleted_at' => date('Y-m-d H:i:s'),
            'deleted_by' => (int) $context['id_user'],
            'status_version' => (int) $request->status_version + 1,
        ));
        if ($this->db->trans_status() === false) {
            $this->db->trans_rollback();
            return array('success' => false, 'code' => 'DATABASE_ERROR');
        }
        $this->db->trans_commit();
        return array('success' => true, 'code' => 'DELETED');
    }

    public function add_documents($context, $requestCode, $documents)
    {
        $this->db->trans_begin();
        $request = $this->db->query('SELECT * FROM tbpo_jasa_request WHERE kd_po_jasa = ? FOR UPDATE', array($requestCode))->row();
        if (!$this->can_edit($request, $context)) {
            $this->db->trans_rollback();
            return array('success' => false, 'code' => 'EDIT_FORBIDDEN');
        }
        $revisionNo = $this->effective_revision($request);
        if ($request->status === 'REVISI_PIC' && empty($request->edit_revision_no)) {
            $revisionNo = (int) $request->revision_no + 1;
        }
        foreach ($documents as $document) {
            $document['kd_po_jasa'] = $requestCode;
            $document['revision_no'] = $revisionNo;
            $document['uploaded_by'] = (int) $context['id_user'];
            $this->db->insert('tbpo_jasa_dokumen', $document);
        }
        $this->appendLog($requestCode, $revisionNo, 'DOKUMEN_DIUNGGAH', $request->status, $request->status, $context, count($documents) . ' dokumen pendukung diunggah.');
        if ($this->db->trans_status() === false) {
            $this->db->trans_rollback();
            return array('success' => false, 'code' => 'DATABASE_ERROR');
        }
        $this->db->trans_commit();
        return array('success' => true, 'code' => 'UPLOADED');
    }

    public function archive_document($context, $documentId)
    {
        $this->db->trans_begin();
        $document = $this->db->query(
            'SELECT d.*, r.kd_user, r.departemen, r.status, r.deleted_at FROM tbpo_jasa_dokumen d JOIN tbpo_jasa_request r ON r.kd_po_jasa = d.kd_po_jasa WHERE d.id_dokumen = ? FOR UPDATE',
            array((int) $documentId)
        )->row();
        if (!$document || !$document->is_active || !$this->can_edit($document, $context)) {
            $this->db->trans_rollback();
            return array('success' => false, 'code' => 'DOCUMENT_DELETE_FORBIDDEN');
        }
        $this->db->where('id_dokumen', (int) $documentId);
        $this->db->update('tbpo_jasa_dokumen', array(
            'is_active' => 0,
            'archived_at' => date('Y-m-d H:i:s'),
            'archived_by' => (int) $context['id_user'],
        ));
        $this->appendLog($document->kd_po_jasa, (int) $document->revision_no, 'DOKUMEN_DIARSIPKAN', $document->status, $document->status, $context, 'Dokumen ' . $document->file_original . ' diarsipkan dari draft.');
        if ($this->db->trans_status() === false) {
            $this->db->trans_rollback();
            return array('success' => false, 'code' => 'DATABASE_ERROR');
        }
        $this->db->trans_commit();
        return array('success' => true, 'code' => 'ARCHIVED', 'request_code' => $document->kd_po_jasa);
    }

    public function get_document($context, $documentId)
    {
        $this->db->select('d.*, r.kd_user, r.deleted_at');
        $this->db->from('tbpo_jasa_dokumen d');
        $this->db->join('tbpo_jasa_request r', 'r.kd_po_jasa = d.kd_po_jasa');
        $this->db->where(array('d.id_dokumen' => (int) $documentId, 'd.is_active' => 1));
        if ($context['role'] !== 'ADMIN') {
            $this->db->where('r.kd_user', $context['kode_user']);
        }
        $this->db->where('r.deleted_at IS NULL', null, false);
        return $this->db->get()->row();
    }

    private function syncScopes($requestCode, $revisionNo, $rows, $context)
    {
        $now = date('Y-m-d H:i:s');
        $this->db->where(array('kd_po_jasa' => $requestCode, 'revision_no' => (int) $revisionNo));
        $this->db->update('tbpo_jasa_scope', array('is_active' => 0, 'archived_at' => $now, 'archived_by' => (int) $context['id_user']));
        $total = 0.0;
        foreach (array_values($rows) as $index => $row) {
            $lineNo = $index + 1;
            $line = array(
                'jenis_scope' => 'JASA',
                'nama_scope' => $row['nama_scope'],
                'deskripsi' => $row['deskripsi'],
                'qty' => $row['qty'],
                'satuan' => $row['satuan'],
                'harga_estimasi' => $row['harga_estimasi'],
                'total_estimasi' => round($row['qty'] * $row['harga_estimasi'], 2),
                'is_active' => 1,
                'archived_at' => null,
                'archived_by' => null,
            );
            $existing = $this->db->get_where('tbpo_jasa_scope', array('kd_po_jasa' => $requestCode, 'revision_no' => $revisionNo, 'line_no' => $lineNo))->row();
            if ($existing) {
                $this->db->where('id_scope', (int) $existing->id_scope)->update('tbpo_jasa_scope', $line);
            } else {
                $line['kd_po_jasa'] = $requestCode;
                $line['revision_no'] = $revisionNo;
                $line['line_no'] = $lineNo;
                $this->db->insert('tbpo_jasa_scope', $line);
            }
            $total += $line['total_estimasi'];
        }
        return round($total, 2);
    }

    private function syncMaterials($requestCode, $revisionNo, $rows, $context)
    {
        $now = date('Y-m-d H:i:s');
        $this->db->where(array('kd_po_jasa' => $requestCode, 'revision_no' => (int) $revisionNo));
        $this->db->update('tbpo_jasa_material', array('is_active' => 0, 'archived_at' => $now, 'archived_by' => (int) $context['id_user']));

        // A draft is tied to the material revision from which it was made.  It
        // cannot remain eligible after PIC starts a newer revision, otherwise a
        // later automatic PO can accidentally submit obsolete material data.
        if ($this->db->field_exists('revision_no', 'tbpo_jasa_draft_pembelian')) {
            $staleDraft = array('status_draft' => 'CANCELLED');
            if ($this->db->field_exists('cancelled_by', 'tbpo_jasa_draft_pembelian')) {
                $staleDraft['cancelled_by'] = (int) $context['id_user'];
                $staleDraft['cancelled_at'] = $now;
                $staleDraft['cancel_reason'] = 'Otomatis dibatalkan karena material direvisi oleh PIC.';
            }
            $this->db->where('kd_po_jasa', $requestCode)
                ->where('revision_no !=', (int) $revisionNo)
                ->where('status_draft', 'DRAFT')
                ->update('tbpo_jasa_draft_pembelian', $staleDraft);
        }
        $total = 0.0;
        foreach (array_values($rows) as $index => $row) {
            $lineNo = $index + 1;
            $existing = $this->db->get_where('tbpo_jasa_material', array('kd_po_jasa' => $requestCode, 'revision_no' => $revisionNo, 'line_no' => $lineNo))->row();
            $proposalId = !empty($row['id_usulan_barang']) ? (int) $row['id_usulan_barang'] : ($existing ? (int) $existing->id_usulan_barang : 0);
            if ($row['reference_type'] === 'MANUAL' && $proposalId <= 0) {
                $proposalId = $this->createMaterialProposal($requestCode, $revisionNo, $row, $context);
            }
            $line = array(
                'id_brg_nk' => $row['id_brg_nk'],
                'reference_type' => $row['reference_type'],
                'id_usulan_barang' => $row['reference_type'] === 'MANUAL' ? $proposalId : null,
                'kd_barang_snapshot' => $row['kd_barang_snapshot'],
                'nama_material' => $row['nama_material'],
                'deskripsi' => $row['deskripsi'],
                'qty_kebutuhan' => $row['qty_kebutuhan'],
                'satuan' => $row['satuan'],
                'harga_estimasi' => $row['harga_estimasi'],
                'total_estimasi' => round($row['qty_kebutuhan'] * $row['harga_estimasi'], 2),
                'sumber_material' => $row['sumber_material'],
                'created_by' => (int) $context['id_user'],
                'is_active' => 1,
                'archived_at' => null,
                'archived_by' => null,
            );
            if ($existing) {
                unset($line['created_by']);
                $this->db->where('id_material', (int) $existing->id_material)->update('tbpo_jasa_material', $line);
            } else {
                $line['kd_po_jasa'] = $requestCode;
                $line['revision_no'] = $revisionNo;
                $line['line_no'] = $lineNo;
                $this->db->insert('tbpo_jasa_material', $line);
            }
            $total += $line['total_estimasi'];
        }
        return round($total, 2);
    }

    private function createMaterialProposal($requestCode, $revisionNo, $row, $context)
    {
        // A random suffix avoids collisions when multiple PIC save drafts concurrently.
        $code = 'USL-NK-' . date('Ym') . '-' . strtoupper(bin2hex(random_bytes(4)));
        $this->db->insert('tbpo_jasa_material_usulan', array(
            'kode_usulan' => $code, 'kd_po_jasa' => $requestCode, 'revision_no' => $revisionNo,
            'nama_barang_input' => $row['nama_material'], 'deskripsi_input' => $row['deskripsi'],
            'satuan_input' => $row['satuan'], 'status_usulan' => 'SUBMITTED',
            'created_by' => (int) $context['id_user'],
        ));
        return (int) $this->db->insert_id();
    }

    private function validateForSubmit($request, $revisionNo, $allowWithoutDocuments = false)
    {
        $errors = array();
        if (trim((string) $request->tujuan_pekerjaan) === '') {
            $errors['tujuan_pekerjaan'] = 'Tujuan pekerjaan wajib diisi.';
        }
        if (empty($request->tgl_mulai_pekerjaan) || empty($request->tgl_selesai_pekerjaan)
            || $request->tgl_selesai_pekerjaan < $request->tgl_mulai_pekerjaan) {
            $errors['jadwal'] = 'Tanggal rencana start pekerjaan dan target penyelesaian wajib diisi dengan urutan yang benar.';
        }
        if (!empty($request->kd_vendor_jasa) && trim((string) $request->vendor_usulan) !== '') {
            $errors['vendor'] = 'Pilih satu sumber vendor: vendor terdaftar atau nama vendor/toko manual.';
        } elseif (empty($request->kd_vendor_jasa) && trim((string) $request->vendor_usulan) === '') {
            $errors['vendor'] = 'Pilih vendor atau isi nama vendor/toko secara manual.';
        } elseif (!empty($request->kd_vendor_jasa) && !$this->active_vendor_exists($request->kd_vendor_jasa)) {
            $errors['vendor'] = 'Vendor yang dipilih tidak tersedia atau tidak aktif.';
        }
        $scopeCount = (int) $this->db->where(array('kd_po_jasa' => $request->kd_po_jasa, 'revision_no' => $revisionNo, 'is_active' => 1))->count_all_results('tbpo_jasa_scope');
        if ($scopeCount < 1) {
            $errors['scope'] = 'Minimal satu scope pekerjaan wajib diisi.';
        }
        $documentCount = (int) $this->db->where(array('kd_po_jasa' => $request->kd_po_jasa, 'is_active' => 1))->count_all_results('tbpo_jasa_dokumen');
        if ($documentCount < 1 && !$allowWithoutDocuments) {
            $errors['documents'] = 'Minimal satu dokumen wajib diunggah sebelum pengajuan.';
        }

        return $errors ? $errors : true;
    }

    private function appendLog($requestCode, $revisionNo, $event, $fromStatus, $toStatus, $context, $note)
    {
        return $this->M_PojasaCore->append_activity_log(array(
            'kd_po_jasa' => $requestCode,
            'revision_no' => $revisionNo,
            'event_type' => $event,
            'from_status' => $fromStatus,
            'to_status' => $toStatus,
            'actor_id' => (int) $context['id_user'],
            'actor_code' => $context['kode_user'],
            'actor_name' => $context['nama_user'],
            'actor_role' => $context['role'],
            'actor_departemen' => $context['departemen'],
            'catatan' => $note,
            'ip_address' => isset($context['ip_address']) ? $context['ip_address'] : null,
            'user_agent' => isset($context['user_agent']) ? $context['user_agent'] : null,
        ));
    }

    private function notifyInitialPurchasing($requestCode, $status = 'MENUNGGU_PURCHASING_AWAL')
    {
        $purchasingUsers = $this->db->query(
            "SELECT id_user FROM tbpo_user WHERE aksess_lv = 2 AND UPPER(TRIM(departement)) = 'PURCHASING'"
        )->result();
        $rows = array();
        foreach ($purchasingUsers as $user) {
            $rows[] = array(
                'kd_po_jasa' => $requestCode,
                'event_type' => $status,
                'recipient_user_id' => (int) $user->id_user,
                'title' => 'Request PO Jasa baru',
                'message' => $requestCode . ' menunggu pemeriksaan awal Purchasing.',
                'target_url' => base_url('pojasa/workflow/detail/' . $requestCode),
            );
        }
        return $this->M_PojasaCore->create_notifications($rows);
    }
}
