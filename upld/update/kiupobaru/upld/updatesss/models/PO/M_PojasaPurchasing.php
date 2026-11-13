<?php
defined('BASEPATH') or exit('No direct script access allowed');

class M_PojasaPurchasing extends CI_Model
{
    private $planningStatuses = array(
        'MENUNGGU_PURCHASING_AWAL', 'MENUNGGU_PURCHASING', 'MENUNGGU_PURCHASING_DIROPS',
        'REVISI_PURCHASING_KADEP', 'REVISI_PURCHASING_DIROPS', 'REVISI_PURCHASING_DIRUT',
    );

    public function __construct()
    {
        parent::__construct();
        $this->load->model('PO/M_PojasaCore');
        $this->load->helper(array('pojasa_authorization', 'pojasa_status'));
    }

    public function schema_ready()
    {
        return $this->M_PojasaCore->tables_ready()
            && $this->db->table_exists('tbpo_jasa_vendor_comparison')
            && $this->db->field_exists('is_active', 'tbpo_jasa_vendor_comparison')
            && $this->db->field_exists('manual_vendor_name', 'tbpo_jasa_vendor_comparison')
            && $this->db->field_exists('source_supplier_code', 'tbpo_jasa_vendor')
            && $this->db->field_exists('allocation_token', 'tbpo_jasa_stock_allocation')
            && $this->db->field_exists('idempotency_token', 'tbpo_jasa_draft_pembelian');
    }

    public function can_access($context, $request)
    {
        if (!$request || !empty($request->deleted_at)) {
            return false;
        }
        if (in_array($context['role'], array('ADMIN', 'PURCHASING', 'DIREKTUR'), true)) {
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

    public function can_manage($context, $request)
    {
        return $this->can_access($context, $request)
            && in_array($context['role'], array('ADMIN', 'PURCHASING'), true)
            && in_array($request->status, $this->planningStatuses, true);
    }

    public function get_request($requestCode, $context)
    {
        $this->db->select('r.*, v.nama_vendor, v.nama_pic vendor_pic, v.no_telpon vendor_phone, v.email vendor_email, v.alamat_vendor');
        $this->db->from('tbpo_jasa_request r');
        $this->db->join('tbpo_jasa_vendor v', 'v.kd_vendor_jasa = r.kd_vendor_jasa', 'left');
        $this->db->where('r.kd_po_jasa', $requestCode);
        $request = $this->db->get()->row();
        return $this->can_access($context, $request) ? $request : null;
    }

    public function effective_revision($request)
    {
        return !empty($request->edit_revision_no) ? (int) $request->edit_revision_no : max(1, (int) $request->revision_no);
    }

    public function search_vendors($term, $limit = 20)
    {
        $term = trim((string) $term);
        $limit = max(1, min(30, (int) $limit));
        $result = array();
        $this->db->select('id_vendor_jasa, kd_vendor_jasa, nama_vendor, alamat_vendor, no_telpon, email, vendor_type, source_type, source_supplier_code');
        $this->db->from('tbpo_jasa_vendor');
        $this->db->where(array('status_vendor' => 'AKTIF', 'deleted_at' => null));
        if ($term !== '') {
            $this->db->group_start()->like('nama_vendor', $term)->or_like('kd_vendor_jasa', $term)->group_end();
        }
        $this->db->order_by('nama_vendor', 'ASC')->limit($limit);
        foreach ($this->db->get()->result_array() as $row) {
            $result[] = array(
                'id' => 'JASA:' . $row['kd_vendor_jasa'], 'source_type' => 'JASA',
                'source_code' => $row['kd_vendor_jasa'], 'text' => $row['nama_vendor'],
                'subtitle' => trim(($row['vendor_type'] ?: 'VENDOR') . ' · ' . ($row['alamat_vendor'] ?: '-')),
            );
        }
        $remaining = max(0, $limit - count($result));
        if ($remaining > 0) {
            $this->db->select('s.kd_suplier, s.nama_suplier, s.alamat_suplier, s.no_telpon, s.email');
            $this->db->from('tbpo_suplier s');
            $this->db->join('tbpo_jasa_vendor j', 'j.source_supplier_code=s.kd_suplier AND j.deleted_at IS NULL', 'left');
            $this->db->where('j.id_vendor_jasa IS NULL', null, false);
            if ($term !== '') {
                $this->db->group_start()->like('s.nama_suplier', $term)->or_like('s.kd_suplier', $term)->group_end();
            }
            $this->db->order_by('s.nama_suplier', 'ASC')->limit($remaining);
            foreach ($this->db->get()->result_array() as $row) {
                $result[] = array(
                    'id' => 'MASTER:' . $row['kd_suplier'], 'source_type' => 'MASTER',
                    'source_code' => $row['kd_suplier'], 'text' => trim($row['nama_suplier']),
                    'subtitle' => 'Master supplier · ' . trim($row['alamat_suplier'] ?: '-'),
                );
            }
        }
        return $result;
    }

    public function create_manual_vendor($context, $data)
    {
        $normalized = $this->normalizeName($data['nama_vendor']);
        $existing = $this->db->where(array('nama_vendor_normalized' => $normalized, 'deleted_at' => null))->get('tbpo_jasa_vendor')->row();
        if ($existing) {
            return array('success' => true, 'code' => 'EXISTING', 'vendor' => $existing);
        }
        $this->db->trans_begin();
        $code = $this->M_PojasaCore->generateAtomicNumber('VENDOR', 'ALL', 'VJ', 4, array(
            array('table' => 'tbpo_jasa_vendor', 'column' => 'kd_vendor_jasa'),
        ));
        if (!$code) {
            $this->db->trans_rollback();
            return array('success' => false, 'code' => 'NUMBER_ERROR');
        }
        $this->db->insert('tbpo_jasa_vendor', array(
            'kd_vendor_jasa' => $code, 'source_type' => 'MANUAL', 'source_supplier_code' => null,
            'vendor_type' => $data['vendor_type'], 'nama_vendor' => $data['nama_vendor'],
            'nama_vendor_normalized' => $normalized, 'kategori_jasa' => $data['kategori_jasa'],
            'nama_pic' => $data['nama_pic'], 'no_telpon' => $data['no_telpon'], 'email' => $data['email'],
            'alamat_vendor' => $data['alamat_vendor'], 'npwp' => $data['npwp'], 'status_vendor' => 'AKTIF',
            'created_by' => $context['kode_user'], 'created_name' => $context['nama_user'],
        ));
        $id = (int) $this->db->insert_id();
        if ($this->db->trans_status() === false) {
            $this->db->trans_rollback();
            return array('success' => false, 'code' => 'DATABASE_ERROR');
        }
        $this->db->trans_commit();
        return array('success' => true, 'code' => 'CREATED', 'vendor' => $this->db->get_where('tbpo_jasa_vendor', array('id_vendor_jasa' => $id))->row());
    }

    public function save_comparison($context, $requestCode, $sourceType, $sourceCode, $data, $document = null)
    {
        $this->db->trans_begin();
        $request = $this->lockRequest($requestCode);
        if (!$this->can_manage($context, $request)) {
            $this->db->trans_rollback();
            return array('success' => false, 'code' => 'FORBIDDEN');
        }
        $manualName = trim((string) (isset($data['manual_vendor_name']) ? $data['manual_vendor_name'] : ''));
        $vendor = $sourceType === 'MANUAL' ? null : $this->resolveVendor($context, $sourceType, $sourceCode);
        if ($sourceType !== 'MANUAL' && !$vendor) {
            $this->db->trans_rollback();
            return array('success' => false, 'code' => 'VENDOR_NOT_FOUND');
        }
        $revision = $this->effective_revision($request);
        $existing = null;
        if ($vendor) {
            $existing = $this->db->query(
                'SELECT * FROM tbpo_jasa_vendor_comparison WHERE kd_po_jasa=? AND revision_no=? AND id_vendor_jasa=? FOR UPDATE',
                array($requestCode, $revision, $vendor->id_vendor_jasa)
            )->row();
        }
        $documentId = $existing && (int) $existing->is_active === 1 ? $existing->id_dokumen : null;
        if ($document) {
            $document['kd_po_jasa'] = $requestCode;
            $document['revision_no'] = $revision;
            $document['uploaded_by'] = (int) $context['id_user'];
            $this->db->insert('tbpo_jasa_dokumen', $document);
            $documentId = (int) $this->db->insert_id();
            if ($existing && $existing->id_dokumen) {
                $this->db->where('id_dokumen', (int) $existing->id_dokumen)->update('tbpo_jasa_dokumen', array(
                    'is_active' => 0, 'archived_at' => date('Y-m-d H:i:s'), 'archived_by' => (int) $context['id_user'],
                ));
            }
        }
        $row = array(
            'id_vendor_jasa' => $vendor ? (int) $vendor->id_vendor_jasa : null,
            'manual_vendor_name' => $vendor ? null : ($manualName !== '' ? $manualName : null),
            'vendor_name_snapshot' => $vendor ? $vendor->nama_vendor : ($manualName !== '' ? $manualName : 'Belum ditentukan'), 'quotation_no' => $data['quotation_no'] ?: null,
            'quotation_date' => $data['quotation_date'] ?: null, 'amount' => $data['amount'],
            'lead_time_days' => $data['lead_time_days'], 'offer_detail' => $data['offer_detail'] ?: null,
            'id_dokumen' => $documentId, 'is_active' => 1, 'archived_by' => null,
            'archived_at' => null, 'archive_reason' => null, 'updated_by' => (int) $context['id_user'],
        );
        if ($existing) {
            $row['version'] = (int) $existing->version + 1;
            $this->db->where('id_vendor_comparison', (int) $existing->id_vendor_comparison)->update('tbpo_jasa_vendor_comparison', $row);
            $comparisonId = (int) $existing->id_vendor_comparison;
        } else {
            $row += array(
                'kd_po_jasa' => $requestCode, 'revision_no' => $revision,
                'created_by' => (int) $context['id_user'],
            );
            $this->db->insert('tbpo_jasa_vendor_comparison', $row);
            $comparisonId = (int) $this->db->insert_id();
        }
        $this->log($context, $request, 'PENAWARAN_VENDOR_DISIMPAN', array('id_vendor_comparison' => $comparisonId));
        if ($this->db->trans_status() === false) {
            $this->db->trans_rollback();
            return array('success' => false, 'code' => 'DATABASE_ERROR');
        }
        $this->db->trans_commit();
        return array('success' => true, 'code' => $existing ? 'UPDATED' : 'CREATED', 'id' => $comparisonId);
    }

    public function delete_comparison($context, $requestCode, $comparisonId)
    {
        $this->db->trans_begin();
        $request = $this->lockRequest($requestCode);
        if (!$this->can_manage($context, $request)) {
            $this->db->trans_rollback();
            return array('success' => false, 'code' => 'FORBIDDEN');
        }
        $row = $this->db->query('SELECT * FROM tbpo_jasa_vendor_comparison WHERE id_vendor_comparison=? AND kd_po_jasa=? FOR UPDATE', array($comparisonId, $requestCode))->row();
        if (!$row || (int) $row->is_active !== 1 || (int) $row->is_selected === 1) {
            $this->db->trans_rollback();
            return array('success' => false, 'code' => $row ? 'SELECTED_VENDOR_LOCKED' : 'NOT_FOUND');
        }
        $this->db->where('id_vendor_comparison', (int) $comparisonId)->update('tbpo_jasa_vendor_comparison', array(
            'is_active' => 0, 'archived_by' => (int) $context['id_user'],
            'archived_at' => date('Y-m-d H:i:s'), 'archive_reason' => 'Dihapus dari perbandingan oleh Purchasing.',
            'updated_by' => (int) $context['id_user'], 'version' => (int) $row->version + 1,
        ));
        if ($row->id_dokumen) {
            $this->db->where('id_dokumen', (int) $row->id_dokumen)->update('tbpo_jasa_dokumen', array('is_active' => 0, 'archived_at' => date('Y-m-d H:i:s'), 'archived_by' => (int) $context['id_user']));
        }
        $this->log($context, $request, 'PENAWARAN_VENDOR_DIHAPUS', array('id_vendor_comparison' => (int) $comparisonId));
        if ($this->db->trans_status() === false) {
            $this->db->trans_rollback();
            return array('success' => false, 'code' => 'DATABASE_ERROR');
        }
        $this->db->trans_commit();
        return array('success' => true, 'code' => 'DELETED');
    }

    public function select_comparison($context, $requestCode, $comparisonId, $reason)
    {
        $this->db->trans_begin();
        $request = $this->lockRequest($requestCode);
        if (!$this->can_manage($context, $request)) {
            $this->db->trans_rollback();
            return array('success' => false, 'code' => 'FORBIDDEN');
        }
        $revision = $this->effective_revision($request);
        $comparison = $this->db->query(
            'SELECT c.*, v.kd_vendor_jasa FROM tbpo_jasa_vendor_comparison c LEFT JOIN tbpo_jasa_vendor v ON v.id_vendor_jasa=c.id_vendor_jasa WHERE c.id_vendor_comparison=? AND c.kd_po_jasa=? AND c.revision_no=? AND c.is_active=1 FOR UPDATE',
            array($comparisonId, $requestCode, $revision)
        )->row();
        if (!$comparison) {
            $this->db->trans_rollback();
            return array('success' => false, 'code' => 'NOT_FOUND');
        }
        $this->db->where(array('kd_po_jasa' => $requestCode, 'revision_no' => $revision, 'is_active' => 1))->update('tbpo_jasa_vendor_comparison', array('is_selected' => 0, 'selection_reason' => null));
        $this->db->where('id_vendor_comparison', (int) $comparisonId)->update('tbpo_jasa_vendor_comparison', array(
            'is_selected' => 1, 'selection_reason' => $reason, 'updated_by' => (int) $context['id_user'], 'version' => (int) $comparison->version + 1,
        ));
        $this->db->where('id_po_jasa', (int) $request->id_po_jasa)->update('tbpo_jasa_request', array(
            'kd_vendor_jasa' => $comparison->kd_vendor_jasa ?: null,
            'vendor_usulan' => $comparison->kd_vendor_jasa ? null : $comparison->vendor_name_snapshot,
            'vendor_selection_reason' => $reason,
            'status_version' => (int) $request->status_version + 1,
        ));
        $this->log($context, $request, 'VENDOR_DIPILIH', array('id_vendor_comparison' => (int) $comparisonId, 'reason' => $reason));
        if ($this->db->trans_status() === false) {
            $this->db->trans_rollback();
            return array('success' => false, 'code' => 'DATABASE_ERROR');
        }
        $this->db->trans_commit();
        return array('success' => true, 'code' => 'SELECTED', 'status_version' => (int) $request->status_version + 1);
    }

    public function search_stock($term, $limit = 20)
    {
        $term = trim((string) $term);
        $this->db->select('id_brg_nk, kode_barangs, kode_barang, nama_barang, deskripsi, nama_lokasi, satuan, qty_ready');
        $this->db->from('v_stockbarangnk');
        if ($term !== '') {
            $this->db->group_start()->like('nama_barang', $term)->or_like('kode_barangs', $term)->or_like('kode_barang', $term)->group_end();
        }
        $this->db->order_by('nama_barang', 'ASC')->limit(max(1, min(30, (int) $limit)));
        $rows = $this->db->get()->result_array();
        foreach ($rows as &$row) {
            $reserved = $this->reservedStock((int) $row['id_brg_nk']);
            $row['physical_stock'] = (float) $row['qty_ready'];
            $row['active_reservation'] = $reserved;
            $row['available_stock'] = max(0, (float) $row['qty_ready'] - $reserved);
        }
        return $rows;
    }

    public function allocate_stock($context, $requestCode, $materialId, $stockId, $quantity, $token)
    {
        $this->db->trans_begin();
        $request = $this->lockRequest($requestCode);
        if (!$this->can_manage($context, $request)) {
            $this->db->trans_rollback();
            return array('success' => false, 'code' => 'FORBIDDEN');
        }
        $existing = $this->db->get_where('tbpo_jasa_stock_allocation', array('allocation_token' => $token))->row();
        if ($existing) {
            if ($existing->kd_po_jasa !== $requestCode || (int) $existing->id_material !== (int) $materialId
                || (int) $existing->id_brg_nk !== (int) $stockId || abs((float) $existing->qty_allocation - (float) $quantity) > 0.000001
                || (int) $existing->allocated_by !== (int) $context['id_user']) {
                $this->db->trans_rollback();
                return array('success' => false, 'code' => 'IDEMPOTENCY_CONFLICT');
            }
            $this->db->trans_commit();
            return array('success' => true, 'code' => 'IDEMPOTENT_REPLAY', 'id' => (int) $existing->id_allocation);
        }
        $revision = $this->effective_revision($request);
        $material = $this->db->query('SELECT * FROM tbpo_jasa_material WHERE id_material=? AND kd_po_jasa=? AND revision_no=? AND is_active=1 FOR UPDATE', array($materialId, $requestCode, $revision))->row();
        $stockItem = $this->db->query('SELECT * FROM tbpo_barang_nk WHERE id_brg_nk=? FOR UPDATE', array($stockId))->row();
        if (!$material || !$stockItem) {
            $this->db->trans_rollback();
            return array('success' => false, 'code' => 'NOT_FOUND');
        }
        $ownReserved = $this->materialReserved($materialId);
        $needRemaining = max(0, (float) $material->qty_kebutuhan - $ownReserved - $this->activeDraftQuantity($materialId));
        $available = max(0, $this->physicalStock($stockItem->kd_barang) - $this->reservedStock($stockId));
        if ($quantity <= 0 || $quantity - $needRemaining > 0.000001) {
            $this->db->trans_rollback();
            return array('success' => false, 'code' => 'QUANTITY_INVALID');
        }
        if ($quantity - $available > 0.000001) {
            $this->db->trans_rollback();
            return array('success' => false, 'code' => 'STOCK_CONFLICT', 'available' => $available);
        }
        $this->db->insert('tbpo_jasa_stock_allocation', array(
            'kd_po_jasa' => $requestCode, 'id_material' => (int) $materialId, 'id_brg_nk' => (int) $stockId,
            'qty_allocation' => $quantity, 'status_allocation' => 'ACTIVE', 'allocation_token' => $token,
            'allocated_by' => (int) $context['id_user'], 'allocated_at' => date('Y-m-d H:i:s'),
        ));
        $allocationId = (int) $this->db->insert_id();
        if (empty($material->id_brg_nk)) {
            $this->db->where('id_material', (int) $materialId)->update('tbpo_jasa_material', array('id_brg_nk' => (int) $stockId, 'sumber_material' => 'STOK'));
        }
        $this->log($context, $request, 'STOK_DIRESERVASI', array('id_allocation' => $allocationId, 'id_material' => (int) $materialId, 'id_brg_nk' => (int) $stockId, 'qty' => $quantity));
        if ($this->db->trans_status() === false) {
            $this->db->trans_rollback();
            return array('success' => false, 'code' => 'DATABASE_ERROR');
        }
        $this->db->trans_commit();
        return array('success' => true, 'code' => 'ALLOCATED', 'id' => $allocationId, 'available_stock' => $available - $quantity);
    }

    public function release_stock($context, $requestCode, $allocationId, $reason, $token)
    {
        $this->db->trans_begin();
        $request = $this->lockRequest($requestCode);
        if (!$this->can_access($context, $request) || !in_array($context['role'], array('ADMIN', 'PURCHASING'), true)) {
            $this->db->trans_rollback();
            return array('success' => false, 'code' => 'FORBIDDEN');
        }
        $replay = $this->db->get_where('tbpo_jasa_stock_allocation', array('release_token' => $token))->row();
        if ($replay) {
            if ($replay->kd_po_jasa !== $requestCode || (int) $replay->id_allocation !== (int) $allocationId
                || (int) $replay->released_by !== (int) $context['id_user']) {
                $this->db->trans_rollback();
                return array('success' => false, 'code' => 'IDEMPOTENCY_CONFLICT');
            }
            $this->db->trans_commit();
            return array('success' => true, 'code' => 'IDEMPOTENT_REPLAY', 'id' => (int) $replay->id_allocation);
        }
        $allocation = $this->db->query('SELECT * FROM tbpo_jasa_stock_allocation WHERE id_allocation=? AND kd_po_jasa=? FOR UPDATE', array($allocationId, $requestCode))->row();
        if (!$allocation || !in_array($allocation->status_allocation, array('ACTIVE', 'PARTIAL'), true)) {
            $this->db->trans_rollback();
            return array('success' => false, 'code' => 'ALLOCATION_INVALID');
        }
        $remaining = max(0, (float) $allocation->qty_allocation - (float) $allocation->qty_received - (float) $allocation->qty_released);
        if ($remaining <= 0) {
            $this->db->trans_rollback();
            return array('success' => false, 'code' => 'ALLOCATION_INVALID');
        }
        $this->db->where('id_allocation', (int) $allocationId)->update('tbpo_jasa_stock_allocation', array(
            'qty_released' => (float) $allocation->qty_released + $remaining, 'status_allocation' => 'RELEASED',
            'version' => (int) $allocation->version + 1, 'released_by' => (int) $context['id_user'],
            'released_at' => date('Y-m-d H:i:s'), 'release_token' => $token, 'release_reason' => $reason,
        ));
        $this->log($context, $request, 'RESERVASI_STOK_DILEPAS', array('id_allocation' => (int) $allocationId, 'qty_released' => $remaining, 'reason' => $reason));
        if ($this->db->trans_status() === false) {
            $this->db->trans_rollback();
            return array('success' => false, 'code' => 'DATABASE_ERROR');
        }
        $this->db->trans_commit();
        return array('success' => true, 'code' => 'RELEASED', 'id' => (int) $allocationId);
    }

    public function save_purchase_draft($context, $requestCode, $materialId, $data, $token)
    {
        $this->db->trans_begin();
        $request = $this->lockRequest($requestCode);
        if (!$this->can_manage($context, $request)) {
            $this->db->trans_rollback();
            return array('success' => false, 'code' => 'FORBIDDEN');
        }
        $replay = $this->db->get_where('tbpo_jasa_draft_pembelian', array('idempotency_token' => $token))->row();
        if ($replay) {
            if ($replay->kd_po_jasa !== $requestCode || (int) $replay->id_material !== (int) $materialId
                || abs((float) $replay->qty - (float) $data['qty']) > 0.000001
                || (int) $replay->created_by !== (int) $context['id_user']) {
                $this->db->trans_rollback();
                return array('success' => false, 'code' => 'IDEMPOTENCY_CONFLICT');
            }
            $this->db->trans_commit();
            return array('success' => true, 'code' => 'IDEMPOTENT_REPLAY', 'id' => (int) $replay->id_draft_pembelian);
        }
        $revision = $this->effective_revision($request);
        $material = $this->db->query('SELECT * FROM tbpo_jasa_material WHERE id_material=? AND kd_po_jasa=? AND revision_no=? AND is_active=1 FOR UPDATE', array($materialId, $requestCode, $revision))->row();
        if (!$material) {
            $this->db->trans_rollback();
            return array('success' => false, 'code' => 'NOT_FOUND');
        }
        $existing = $this->db->query('SELECT * FROM tbpo_jasa_draft_pembelian WHERE kd_po_jasa=? AND id_material=? FOR UPDATE', array($requestCode, $materialId))->row();
        $reserved = $this->materialReserved($materialId);
        $availableStock = $this->availableStockForMaterial($material);
        $activeDraft = $this->activeDraftQuantity($materialId, $existing ? (int) $existing->id_draft_pembelian : null);
        $shortage = max(0, (float) $material->qty_kebutuhan - $availableStock - $reserved - $activeDraft);
        if ((bool) $this->config->item('pojasa_draft_diagnostic')) {
            log_message('debug', 'POJASA_DRAFT_VALIDATION ' . json_encode(array(
                'qty_kebutuhan' => (float) $material->qty_kebutuhan, 'stok_tersedia' => $availableStock, 'reservasi_aktif' => $reserved,
                'draft_aktif' => $activeDraft, 'kekurangan' => $shortage, 'qty_input' => (float) $data['qty'],
                'kd_po_jasa' => $requestCode, 'id_material' => (int) $materialId,
            )));
        }
        if ($shortage <= 0 || $data['qty'] <= 0 || $data['qty'] - $shortage > 0.000001) {
            $this->db->trans_rollback();
            return array('success' => false, 'code' => 'DRAFT_QUANTITY_INVALID', 'shortage' => $shortage);
        }
        $row = array(
            'revision_no' => $revision, 'id_vendor_jasa' => $data['id_vendor_jasa'], 'qty' => $data['qty'],
            'harga_estimasi' => $data['harga_estimasi'], 'keterangan' => $data['keterangan'],
            'status_draft' => 'DRAFT', 'idempotency_token' => $token,
            'cancelled_by' => null, 'cancelled_at' => null, 'cancel_reason' => null,
        );
        if ($existing) {
            $row['version'] = (int) $existing->version + 1;
            $this->db->where('id_draft_pembelian', (int) $existing->id_draft_pembelian)->update('tbpo_jasa_draft_pembelian', $row);
            $id = (int) $existing->id_draft_pembelian;
        } else {
            $row += array('kd_po_jasa' => $requestCode, 'id_material' => (int) $materialId, 'created_by' => (int) $context['id_user']);
            $this->db->insert('tbpo_jasa_draft_pembelian', $row);
            $id = (int) $this->db->insert_id();
        }
        $this->db->where('id_material', (int) $materialId)->update('tbpo_jasa_material', array('sumber_material' => $reserved > 0 ? 'CAMPURAN' : 'PEMBELIAN'));
        $this->log($context, $request, 'DRAFT_PEMBELIAN_DIBUAT', array('id_draft_pembelian' => $id, 'id_material' => (int) $materialId, 'qty' => $data['qty']));
        if ($this->db->trans_status() === false) {
            $this->db->trans_rollback();
            return array('success' => false, 'code' => 'DATABASE_ERROR');
        }
        $this->db->trans_commit();
        return array('success' => true, 'code' => $existing ? 'UPDATED' : 'CREATED', 'id' => $id, 'shortage' => $shortage);
    }

    public function cancel_purchase_draft($context, $requestCode, $draftId, $reason)
    {
        $this->db->trans_begin();
        $request = $this->lockRequest($requestCode);
        if (!$this->can_access($context, $request) || !in_array($context['role'], array('ADMIN', 'PURCHASING'), true)) {
            $this->db->trans_rollback();
            return array('success' => false, 'code' => 'FORBIDDEN');
        }
        $draft = $this->db->query('SELECT * FROM tbpo_jasa_draft_pembelian WHERE id_draft_pembelian=? AND kd_po_jasa=? FOR UPDATE', array($draftId, $requestCode))->row();
        if (!$draft || $draft->status_draft !== 'DRAFT') {
            $this->db->trans_rollback();
            return array('success' => false, 'code' => 'NOT_FOUND');
        }
        $this->db->where('id_draft_pembelian', (int) $draftId)->update('tbpo_jasa_draft_pembelian', array(
            'status_draft' => 'CANCELLED', 'version' => (int) $draft->version + 1,
            'cancelled_by' => (int) $context['id_user'], 'cancelled_at' => date('Y-m-d H:i:s'), 'cancel_reason' => $reason,
        ));
        $this->log($context, $request, 'DRAFT_PEMBELIAN_DIBATALKAN', array('id_draft_pembelian' => (int) $draftId, 'reason' => $reason));
        if ($this->db->trans_status() === false) {
            $this->db->trans_rollback();
            return array('success' => false, 'code' => 'DATABASE_ERROR');
        }
        $this->db->trans_commit();
        return array('success' => true, 'code' => 'CANCELLED');
    }

    public function spk_revision_ready()
    {
        return $this->db->table_exists('tbpo_jasa_spk_revision')
            && $this->db->table_exists('tbpo_jasa_spk_archive')
            && $this->db->field_exists('spk_version_no', 'tbpo_jasa_spk');
    }

    public function submit_spk_revision($context, $requestCode, $startDate, $endDate, $reason, $token)
    {
        if (!$this->spk_revision_ready()) return array('success' => false, 'code' => 'SCHEMA_NOT_READY');
        if (!$this->validBusinessDate($startDate) || !$this->validBusinessDate($endDate) || $endDate < $startDate || trim((string) $reason) === '') {
            return array('success' => false, 'code' => 'SCHEDULE_INVALID');
        }
        $this->db->trans_begin();
        $request = $this->lockRequest($requestCode);
        if (!$request || !in_array($context['role'], array('ADMIN', 'PURCHASING'), true) || empty($request->no_spk)) {
            $this->db->trans_rollback(); return array('success' => false, 'code' => 'FORBIDDEN');
        }
        $replay = $this->db->get_where('tbpo_jasa_spk_revision', array('submit_token' => $token))->row();
        if ($replay) {
            if ($replay->kd_po_jasa !== $requestCode || (int) $replay->requested_by !== (int) $context['id_user']) {
                $this->db->trans_rollback(); return array('success' => false, 'code' => 'IDEMPOTENCY_CONFLICT');
            }
            $this->db->trans_commit(); return array('success' => true, 'code' => 'IDEMPOTENT_REPLAY');
        }
        $spk = $this->db->query('SELECT * FROM tbpo_jasa_spk WHERE kd_po_jasa=? FOR UPDATE', array($requestCode))->row();
        $latest = $this->db->query('SELECT * FROM tbpo_jasa_spk_revision WHERE kd_po_jasa=? ORDER BY id_spk_revision DESC LIMIT 1 FOR UPDATE', array($requestCode))->row();
        if (!$spk || ($latest && $latest->status === 'MENUNGGU')) {
            $this->db->trans_rollback(); return array('success' => false, 'code' => $spk ? 'REVISION_PENDING' : 'NOT_FOUND');
        }
        $now = date('Y-m-d H:i:s');
        if ($latest && $latest->status === 'REVISI') {
            $this->db->insert('tbpo_jasa_spk_revision', array(
                'kd_po_jasa' => $requestCode, 'no_spk' => $spk->no_spk,
                'from_version_no' => max(1, (int) $spk->spk_version_no),
                'old_start_date' => $request->tgl_mulai_pekerjaan, 'old_end_date' => $request->tgl_selesai_pekerjaan,
                'new_start_date' => $startDate, 'new_end_date' => $endDate, 'reason' => $reason,
                'status' => 'MENUNGGU', 'requested_by' => (int) $context['id_user'], 'requested_name' => $context['nama_user'],
                'requested_at' => $now, 'submit_token' => $token, 'submit_count' => (int) $latest->submit_count + 1,
            ));
            $revisionId = (int) $this->db->insert_id();
            $event = 'REVISI_SPK_DIAJUKAN_ULANG';
        } else {
            $this->db->insert('tbpo_jasa_spk_revision', array(
                'kd_po_jasa' => $requestCode, 'no_spk' => $spk->no_spk,
                'from_version_no' => max(1, (int) $spk->spk_version_no),
                'old_start_date' => $request->tgl_mulai_pekerjaan, 'old_end_date' => $request->tgl_selesai_pekerjaan,
                'new_start_date' => $startDate, 'new_end_date' => $endDate, 'reason' => $reason,
                'status' => 'MENUNGGU', 'requested_by' => (int) $context['id_user'],
                'requested_name' => $context['nama_user'], 'requested_at' => $now, 'submit_token' => $token,
            ));
            $revisionId = (int) $this->db->insert_id();
            $event = 'REVISI_SPK_DIAJUKAN';
        }
        $this->log($context, $request, $event, array('id_spk_revision' => $revisionId, 'before' => array($request->tgl_mulai_pekerjaan, $request->tgl_selesai_pekerjaan), 'after' => array($startDate, $endDate), 'reason' => $reason, 'spk_version' => (int) $spk->spk_version_no));
        $this->notifySpkRevision($request, $event, 'Revisi jadwal SPK diajukan dan menunggu keputusan Direktur.');
        if ($this->db->trans_status() === false) { $this->db->trans_rollback(); return array('success' => false, 'code' => 'DATABASE_ERROR'); }
        $this->db->trans_commit(); return array('success' => true, 'code' => 'SUBMITTED');
    }

    public function decide_spk_revision($context, $requestCode, $revisionId, $action, $note, $token)
    {
        if (!$this->spk_revision_ready()) return array('success' => false, 'code' => 'SCHEMA_NOT_READY');
        $this->db->trans_begin();
        $request = $this->lockRequest($requestCode);
        if (!$request || $context['role'] !== 'DIREKTUR') { $this->db->trans_rollback(); return array('success' => false, 'code' => 'FORBIDDEN'); }
        $replay = $this->db->get_where('tbpo_jasa_spk_revision', array('decision_token' => $token))->row();
        if ($replay) {
            if ($replay->kd_po_jasa !== $requestCode || (int) $replay->decision_by !== (int) $context['id_user']) {
                $this->db->trans_rollback(); return array('success' => false, 'code' => 'IDEMPOTENCY_CONFLICT');
            }
            $this->db->trans_commit(); return array('success' => true, 'code' => 'IDEMPOTENT_REPLAY');
        }
        $revision = $this->db->query('SELECT * FROM tbpo_jasa_spk_revision WHERE id_spk_revision=? AND kd_po_jasa=? FOR UPDATE', array($revisionId, $requestCode))->row();
        $spk = $this->db->query('SELECT * FROM tbpo_jasa_spk WHERE kd_po_jasa=? FOR UPDATE', array($requestCode))->row();
        if (!$revision || !$spk || $revision->status !== 'MENUNGGU') { $this->db->trans_rollback(); return array('success' => false, 'code' => 'REVISION_NOT_PENDING'); }
        $now = date('Y-m-d H:i:s');
        $status = $action === 'ACC' ? 'DISETUJUI' : ($action === 'REVISI' ? 'REVISI' : 'DITOLAK');
        $newVersion = max(1, (int) $spk->spk_version_no);
        if ($action === 'ACC') {
            $this->db->insert('tbpo_jasa_spk_archive', array(
                'id_spk_source' => (int) $spk->id_spk, 'kd_po_jasa' => $requestCode, 'no_spk' => $spk->no_spk,
                'request_revision_no' => (int) $spk->revision_no, 'spk_version_no' => $newVersion,
                'issued_by' => (int) $spk->issued_by, 'issued_at' => $spk->issued_at, 'snapshot_json' => $spk->snapshot_json,
                'template_version' => $spk->template_version, 'archived_by' => (int) $context['id_user'], 'archived_at' => $now,
            ));
            $newVersion++;
            $snapshot = json_decode($spk->snapshot_json, true);
            if (!is_array($snapshot)) $snapshot = array();
            if (!isset($snapshot['request']) || !is_array($snapshot['request'])) $snapshot['request'] = array();
            $snapshot['request']['tgl_mulai_pekerjaan'] = $revision->new_start_date;
            $snapshot['request']['tgl_selesai_pekerjaan'] = $revision->new_end_date;
            $snapshot['spk_version_no'] = $newVersion;
            $snapshot['revision_label'] = 'Rev. ' . ($newVersion - 1);
            $snapshot['schedule_revision'] = array('reason' => $revision->reason, 'approved_by' => $context['nama_user'], 'approved_at' => $now);
            $this->db->where('id_spk', (int) $spk->id_spk)->update('tbpo_jasa_spk', array(
                'spk_version_no' => $newVersion, 'issued_by' => (int) $context['id_user'], 'issued_at' => $now,
                'snapshot_json' => json_encode($snapshot), 'document_status' => 'ISSUED',
            ));
            $this->db->where(array('id_po_jasa' => (int) $request->id_po_jasa, 'status_version' => (int) $request->status_version))->update('tbpo_jasa_request', array(
                'tgl_mulai_pekerjaan' => $revision->new_start_date, 'tgl_selesai_pekerjaan' => $revision->new_end_date,
                'tgl_target' => $revision->new_end_date, 'status_version' => (int) $request->status_version + 1,
            ));
            if ($this->db->affected_rows() !== 1) { $this->db->trans_rollback(); return array('success' => false, 'code' => 'CONCURRENT_UPDATE'); }
        }
        $this->db->where('id_spk_revision', (int) $revisionId)->update('tbpo_jasa_spk_revision', array(
            'status' => $status, 'decision_by' => (int) $context['id_user'], 'decision_name' => $context['nama_user'],
            'decision_at' => $now, 'decision_note' => $note ?: null, 'decision_token' => $token,
            'row_version' => (int) $revision->row_version + 1,
        ));
        $event = 'REVISI_SPK_' . $action;
        $this->log($context, $request, $event, array('id_spk_revision' => (int) $revisionId, 'before' => array($revision->old_start_date, $revision->old_end_date), 'after' => array($revision->new_start_date, $revision->new_end_date), 'reason' => $revision->reason, 'decision_note' => $note, 'spk_version' => $newVersion));
        $this->notifySpkRevision($request, $event, 'Revisi jadwal SPK: ' . $status . '.');
        if ($this->db->trans_status() === false) { $this->db->trans_rollback(); return array('success' => false, 'code' => 'DATABASE_ERROR'); }
        $this->db->trans_commit(); return array('success' => true, 'code' => $status, 'spk_version_no' => $newVersion);
    }

    public function state($request)
    {
        $revision = $this->effective_revision($request);
        $scopes = $this->db->order_by('line_no', 'ASC')->get_where('tbpo_jasa_scope', array(
            'kd_po_jasa' => $request->kd_po_jasa, 'revision_no' => $revision, 'is_active' => 1,
        ))->result_array();
        $comparisons = $this->db->select('c.*,v.kd_vendor_jasa,v.vendor_type,v.source_type,v.source_supplier_code,d.file_original,d.mime_type')
            ->from('tbpo_jasa_vendor_comparison c')->join('tbpo_jasa_vendor v', 'v.id_vendor_jasa=c.id_vendor_jasa', 'left')
            ->join('tbpo_jasa_dokumen d', 'd.id_dokumen=c.id_dokumen', 'left')
            ->where(array('c.kd_po_jasa' => $request->kd_po_jasa, 'c.revision_no' => $revision, 'c.is_active' => 1))
            ->order_by('c.is_selected', 'DESC')->order_by('c.amount', 'ASC')->get()->result_array();
        $materials = $this->db->order_by('line_no', 'ASC')->get_where('tbpo_jasa_material', array(
            'kd_po_jasa' => $request->kd_po_jasa, 'revision_no' => $revision, 'is_active' => 1,
        ))->result_array();
        foreach ($materials as &$material) {
            $material['allocations'] = $this->db->select('a.*,b.nama_barang,b.kd_barang,b.kd_br_adm,s.nm_satuan')
                ->from('tbpo_jasa_stock_allocation a')->join('tbpo_barang_nk b', 'b.id_brg_nk=a.id_brg_nk')
                ->join('tbpo_satuan s', 's.id_satuan=b.satuan', 'left')->where('a.id_material', (int) $material['id_material'])
                ->order_by('a.id_allocation', 'DESC')->get()->result_array();
            $material['draft'] = $this->db->select('d.*,v.nama_vendor')->from('tbpo_jasa_draft_pembelian d')
                ->join('tbpo_jasa_vendor v', 'v.id_vendor_jasa=d.id_vendor_jasa', 'left')
                ->where(array('d.kd_po_jasa' => $request->kd_po_jasa, 'd.id_material' => (int) $material['id_material']))->get()->row_array();
            $material['reserved_qty'] = $this->materialReserved((int) $material['id_material']);
            $material['qty_ready'] = $this->availableStockForMaterial((object) $material);
            $material['active_draft_qty'] = $this->activeDraftQuantity((int) $material['id_material']);
            $material['remaining_need'] = max(0, (float) $material['qty_kebutuhan'] - (float) $material['qty_ready'] - (float) $material['reserved_qty'] - (float) $material['active_draft_qty']);
        }
        $spk = $this->db->get_where('tbpo_jasa_spk', array('kd_po_jasa' => $request->kd_po_jasa))->row_array();
        $spkRevisions = array();
        $spkArchives = array();
        if ($this->spk_revision_ready()) {
            $spkRevisions = $this->db->order_by('id_spk_revision', 'DESC')->get_where('tbpo_jasa_spk_revision', array('kd_po_jasa' => $request->kd_po_jasa))->result_array();
            $spkArchives = $this->db->order_by('spk_version_no', 'DESC')->get_where('tbpo_jasa_spk_archive', array('kd_po_jasa' => $request->kd_po_jasa))->result_array();
        }
        $execution = array('progress' => array(), 'costs' => array(), 'receipts' => array(), 'cost_summary' => null);
        if ($this->db->field_exists('cost_source', 'tbpo_jasa_biaya_aktual')) {
            $execution['progress'] = $this->db->order_by('tgl_progress', 'DESC')->order_by('id_progress_jasa', 'DESC')->get_where('tbpo_jasa_progress', array('kd_po_jasa' => $request->kd_po_jasa))->result_array();
            $execution['costs'] = $this->db->select('c.*,d.file_original')->from('tbpo_jasa_biaya_aktual c')->join('tbpo_jasa_dokumen d', 'd.id_dokumen=c.id_dokumen_bukti', 'left')->where('c.kd_po_jasa', $request->kd_po_jasa)->order_by('c.tgl_biaya', 'DESC')->get()->result_array();
            $execution['receipts'] = $this->db->query(
                'SELECT h.receipt_at,h.no_receipt,d.qty_received,d.id_transnk,m.nama_material,m.satuan FROM tbpo_jasa_stock_receipt h '
                . 'JOIN tbpo_jasa_stock_receipt_detail d ON d.id_receipt=h.id_receipt JOIN tbpo_jasa_material m ON m.id_material=d.id_material '
                . 'WHERE h.kd_po_jasa=? ORDER BY h.receipt_at DESC,d.id_receipt_detail DESC', array($request->kd_po_jasa)
            )->result_array();
            $actual = 0.0;
            foreach ($execution['costs'] as $cost) { $actual += (float) $cost['nominal']; }
            $approved = $request->estimasi_disetujui === null ? (float) $request->estimasi_total : (float) $request->estimasi_disetujui;
            $execution['cost_summary'] = array('requested' => (float) $request->estimasi_total, 'approved' => $approved, 'actual' => round($actual, 2), 'variance' => round($approved - $actual, 2));
        }
        return array('revision_no' => $revision, 'comparisons' => $comparisons, 'scopes' => $scopes, 'materials' => $materials, 'spk' => $spk,
            'spk_revisions' => $spkRevisions, 'spk_archives' => $spkArchives, 'execution' => $execution);
    }

    public function get_spk($requestCode, $context, $versionNo = null)
    {
        $request = $this->get_request($requestCode, $context);
        if (!$request || $request->status !== 'SPK_TERBIT' && !$request->no_spk) {
            return null;
        }
        $spk = $this->db->get_where('tbpo_jasa_spk', array('kd_po_jasa' => $requestCode))->row();
        if (!$spk) {
            return null;
        }
        if ($versionNo !== null && (int) $versionNo !== (int) $spk->spk_version_no) {
            if (!$this->spk_revision_ready()) return null;
            $archive = $this->db->get_where('tbpo_jasa_spk_archive', array('kd_po_jasa' => $requestCode, 'spk_version_no' => (int) $versionNo))->row();
            if (!$archive) return null;
            $archive->revision_no = $archive->request_revision_no;
            $archive->id_spk = $archive->id_spk_source;
            $archive->document_status = 'ARCHIVED';
            $spk = $archive;
        }
        $snapshot = json_decode($spk->snapshot_json, true);
        return array('request' => $request, 'spk' => $spk, 'snapshot' => is_array($snapshot) ? $snapshot : array());
    }

    private function resolveVendor($context, $sourceType, $sourceCode)
    {
        if ($sourceType === 'JASA') {
            return $this->db->get_where('tbpo_jasa_vendor', array('kd_vendor_jasa' => $sourceCode, 'status_vendor' => 'AKTIF', 'deleted_at' => null))->row();
        }
        if ($sourceType !== 'MASTER') {
            return null;
        }
        $vendor = $this->db->get_where('tbpo_jasa_vendor', array('source_supplier_code' => $sourceCode, 'deleted_at' => null))->row();
        if ($vendor) {
            return $vendor;
        }
        $supplier = $this->db->get_where('tbpo_suplier', array('kd_suplier' => $sourceCode))->row();
        if (!$supplier) {
            return null;
        }
        $code = $this->M_PojasaCore->generateAtomicNumber('VENDOR', 'ALL', 'VJ', 4, array(array('table' => 'tbpo_jasa_vendor', 'column' => 'kd_vendor_jasa')));
        if (!$code) {
            return null;
        }
        $this->db->insert('tbpo_jasa_vendor', array(
            'kd_vendor_jasa' => $code, 'source_type' => 'MASTER_SUPPLIER', 'source_supplier_code' => $sourceCode,
            'vendor_type' => 'VENDOR', 'nama_vendor' => trim($supplier->nama_suplier),
            'nama_vendor_normalized' => $this->normalizeName($supplier->nama_suplier),
            'no_telpon' => trim($supplier->no_telpon), 'email' => trim($supplier->email),
            'alamat_vendor' => trim($supplier->alamat_suplier), 'status_vendor' => 'AKTIF',
            'created_by' => $context['kode_user'], 'created_name' => $context['nama_user'],
        ));
        return $this->db->get_where('tbpo_jasa_vendor', array('id_vendor_jasa' => (int) $this->db->insert_id()))->row();
    }

    private function lockRequest($requestCode)
    {
        return $this->db->query('SELECT * FROM tbpo_jasa_request WHERE kd_po_jasa=? FOR UPDATE', array($requestCode))->row();
    }

    private function physicalStock($stockCode)
    {
        $row = $this->db->query(
            "SELECT COALESCE(SUM(CASE WHEN kd_akun IN ('11511','11513') THEN tr_qty WHEN kd_akun IN ('11512','11514') THEN -tr_qty ELSE 0 END),0) qty FROM tbpo_transaksi WHERE kd_barang=? AND kd_akun IN ('11511','11512','11513','11514')",
            array($stockCode)
        )->row();
        return $row ? (float) $row->qty : 0.0;
    }

    private function reservedStock($stockId)
    {
        $row = $this->db->query(
            "SELECT COALESCE(SUM(qty_allocation-qty_received-qty_released),0) qty FROM tbpo_jasa_stock_allocation WHERE id_brg_nk=? AND status_allocation IN ('ACTIVE','PARTIAL')",
            array((int) $stockId)
        )->row();
        return $row ? max(0, (float) $row->qty) : 0.0;
    }

    private function availableStockForMaterial($material)
    {
        $stockId = (int) (is_object($material) ? $material->id_brg_nk : $material['id_brg_nk']);
        if ($stockId <= 0) return 0.0;
        $stock = $this->db->query('SELECT qty_ready FROM v_stockbarangnk WHERE id_brg_nk=? LIMIT 1', array($stockId))->row();
        return max(0, (float) ($stock ? $stock->qty_ready : 0) - $this->reservedStock($stockId));
    }

    private function materialReserved($materialId)
    {
        $row = $this->db->query(
            "SELECT COALESCE(SUM(qty_allocation-qty_received-qty_released),0) qty FROM tbpo_jasa_stock_allocation WHERE id_material=? AND status_allocation IN ('ACTIVE','PARTIAL')",
            array((int) $materialId)
        )->row();
        return $row ? max(0, (float) $row->qty) : 0.0;
    }

    private function activeDraftQuantity($materialId, $excludeDraftId = null)
    {
        $sql = "SELECT COALESCE(SUM(qty),0) qty FROM tbpo_jasa_draft_pembelian WHERE id_material=? AND status_draft='DRAFT'";
        $binds = array((int) $materialId);
        if ($excludeDraftId) {
            $sql .= ' AND id_draft_pembelian<>?';
            $binds[] = (int) $excludeDraftId;
        }
        $row = $this->db->query($sql, $binds)->row();
        return $row ? max(0, (float) $row->qty) : 0.0;
    }

    private function log($context, $request, $event, $metadata)
    {
        return $this->M_PojasaCore->append_activity_log(array(
            'kd_po_jasa' => $request->kd_po_jasa, 'revision_no' => $this->effective_revision($request),
            'event_type' => $event, 'from_status' => $request->status, 'to_status' => $request->status,
            'actor_id' => (int) $context['id_user'], 'actor_code' => $context['kode_user'],
            'actor_name' => $context['nama_user'], 'actor_role' => $context['role'],
            'actor_departemen' => $context['departemen'], 'metadata_json' => json_encode($metadata),
        ));
    }

    private function notifySpkRevision($request, $event, $message)
    {
        $users = $this->db->query(
            "SELECT DISTINCT id_user FROM tbpo_user WHERE kode_user=? OR (aksess_lv=2 AND UPPER(TRIM(departement))='PURCHASING') OR (aksess_lv=3 AND UPPER(TRIM(departement))='DIREKTUR') OR (aksess_lv=5 AND UPPER(TRIM(departement))=UPPER(TRIM(?)))",
            array($request->kd_user, $request->departemen)
        )->result();
        $rows = array();
        foreach ($users as $user) {
            $rows[] = array('kd_po_jasa' => $request->kd_po_jasa, 'event_type' => $event,
                'recipient_user_id' => (int) $user->id_user, 'title' => 'Revisi SPK PO Jasa',
                'message' => $message . ' ' . $request->no_spk, 'target_url' => base_url('pojasa/purchasing/detail/' . $request->kd_po_jasa));
        }
        return $this->M_PojasaCore->create_notifications($rows);
    }

    private function normalizeName($name)
    {
        return mb_strtoupper(preg_replace('/\s+/', ' ', trim((string) $name)), 'UTF-8');
    }

    private function validBusinessDate($value)
    {
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) $value)) return false;
        $parts = explode('-', $value);
        return checkdate((int) $parts[1], (int) $parts[2], (int) $parts[0]);
    }
}
