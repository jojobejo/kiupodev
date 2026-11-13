<?php
defined('BASEPATH') or exit('No direct script access allowed');

class M_PojasaIntegration extends CI_Model
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('PO/M_PojasaCore');
        $this->load->helper(array('pojasa_authorization', 'pojasa_status'));
    }

    public function schema_ready()
    {
        foreach (array(
            'tbpo_jasa_purchase_submission', 'tbpo_jasa_purchase_submission_detail',
            'tbpo_jasa_purchase_receipt', 'tbpo_jasa_purchase_adjustment',
        ) as $table) {
            if (!$this->db->table_exists($table)) {
                return false;
            }
        }

        return $this->db->field_exists('source_module', 'tbpo_po_nk')
            && $this->db->field_exists('source_material_id', 'tbpo_detail_po_nk')
            && $this->db->field_exists('source_type', 'tbpo_jasa_biaya_aktual')
            && $this->db->field_exists('revision_no', 'tbpo_jasa_draft_pembelian')
            && $this->db->field_exists('id_transnk', 'tbpo_jasa_purchase_receipt');
    }

    public function create_purchase_submission($context, $requestCode, $token = null, $developmentMode = false)
    {
        $ownsTransaction = !$this->db->trans_active();
        if ($ownsTransaction) {
            $this->db->trans_begin();
        }
        if (!$this->schema_ready()) {
            return $this->finishFailure($ownsTransaction, 'SCHEMA_NOT_READY');
        }

        $request = $this->db->query('SELECT * FROM tbpo_jasa_request WHERE kd_po_jasa=? FOR UPDATE', array($requestCode))->row();
        $spk = $this->db->query('SELECT * FROM tbpo_jasa_spk WHERE kd_po_jasa=? FOR UPDATE', array($requestCode))->row();
        if (!$request || (!$developmentMode && (!$spk || empty($request->no_spk)))) {
            return $this->finishFailure($ownsTransaction, 'SPK_NOT_FOUND');
        }
        if (!in_array($context['role'], array('ADMIN', 'PURCHASING'), true)) {
            return $this->finishFailure($ownsTransaction, 'FORBIDDEN');
        }
        // A request can retain drafts from an earlier PIC revision.  A purchase
        // submission must only ever use the material set that is active now.
        $activeRevision = !empty($request->edit_revision_no)
            ? (int) $request->edit_revision_no
            : max(1, (int) $request->revision_no);

        $sourceReference = $developmentMode
            ? 'PO_JASA_DEV:' . $requestCode . ':REV:' . $activeRevision
            : $this->submissionReference($requestCode, $spk);
        $existing = $developmentMode
            ? $this->db->get_where('tbpo_po_nk', array('source_module' => 'PO_JASA_DEV', 'source_reference' => $sourceReference))->row()
            : $this->db->get_where('tbpo_jasa_purchase_submission', array('source_reference' => $sourceReference))->row();
        if ($existing) {
            return $this->finishSuccess($ownsTransaction, array(
                'success' => true, 'code' => $developmentMode ? 'DEV_IDEMPOTENT_REPLAY' : 'IDEMPOTENT_REPLAY',
                'id_submission' => $developmentMode ? null : (int) $existing->id_submission,
                'id_po_nk' => (int) $existing->id_po_nk,
                'kd_po_nk' => $existing->kd_po_nk,
            ));
        }
        if (!$developmentMode && $token) {
            $tokenReplay = $this->db->get_where('tbpo_jasa_purchase_submission', array('idempotency_token' => $token))->row();
            if ($tokenReplay) {
                if ($tokenReplay->kd_po_jasa !== $requestCode) {
                    return $this->finishFailure($ownsTransaction, 'IDEMPOTENCY_CONFLICT');
                }
                return $this->finishSuccess($ownsTransaction, array(
                    'success' => true, 'code' => 'IDEMPOTENT_REPLAY',
                    'id_submission' => (int) $tokenReplay->id_submission, 'kd_po_nk' => $tokenReplay->kd_po_nk,
                ));
            }
        }
        $kadepCode = $this->departmentHeadCode($request);
        if ($kadepCode === null) {
            return $this->finishFailure($ownsTransaction, 'KADEP_NOT_FOUND');
        }

        $drafts = $this->db->query(
            "SELECT d.*,m.nama_material,m.deskripsi,m.satuan,m.id_brg_nk,b.kd_br_adm,b.kd_barang,b.kat_barang,b.satuan AS stock_unit "
            . "FROM tbpo_jasa_draft_pembelian d JOIN tbpo_jasa_material m ON m.id_material=d.id_material "
            . "AND m.kd_po_jasa=d.kd_po_jasa AND m.revision_no=d.revision_no AND m.is_active=1 "
            . "LEFT JOIN tbpo_barang_nk b ON b.id_brg_nk=m.id_brg_nk "
            . "WHERE d.kd_po_jasa=? AND d.revision_no=? AND d.status_draft='DRAFT' ORDER BY d.id_draft_pembelian ASC FOR UPDATE",
            array($requestCode, $activeRevision)
        )->result();
        if (!$drafts) {
            return $this->finishSuccess($ownsTransaction, array('success' => true, 'code' => 'NO_ACTIVE_DRAFT', 'id_submission' => null, 'kd_po_nk' => null));
        }
        foreach ($drafts as $draft) {
            if (!$this->isWholeNumber($draft->qty) || !$this->isWholeNumber($draft->harga_estimasi)) {
                return $this->finishFailure($ownsTransaction, 'LEGACY_INTEGER_REQUIRED');
            }
        }

        $today = date('Y-m-d');
        $purchaseCode = $this->M_PojasaCore->generateAtomicNumber(
            'PURCHASE_NK', date('Ymd'), 'NKPO' . date('dmy'), 4,
            array(array('table' => 'tbpo_po_nk', 'column' => 'kd_po_nk'))
        );
        if (!$purchaseCode) {
            return $this->finishFailure($ownsTransaction, 'NUMBER_ERROR');
        }
        $total = 0;
        foreach ($drafts as $draft) {
            $total += (float) $draft->qty * (float) $draft->harga_estimasi;
        }
        $this->db->insert('tbpo_po_nk', array(
            'jns_po' => 2, 'kd_po_nk' => $purchaseCode, 'kd_po_req' => $requestCode,
            // The purchasing user creates the document, but the legacy PO owner
            // remains the PIC who submitted the originating PO Jasa request.
            'nopo' => $developmentMode ? 'DEV-' . $requestCode : 'AUTO-' . $spk->no_spk, 'kd_user' => $request->kd_user,
            'nm_user' => $request->nm_user, 'tgl_transaksi' => $today, 'jml_item' => count($drafts),
            'total_harga' => (int) round($total), 'status' => 'PROSES PEMBELIAN',
            'departemen' => $request->departemen,
            'tj_pembelian' => $developmentMode
                ? 'Pengajuan DEV material PO Jasa ' . $requestCode . ' tanpa SPK'
                : 'Pengajuan otomatis material PO Jasa ' . $requestCode . ' / SPK ' . $spk->no_spk,
            'tax' => 0, 'hrg_pajak' => 0, 'hrg_nyata' => 0, 'status_hrg_nyata' => 0,
            'acc_with' => $context['kode_user'], 'acc_with_kadep' => $kadepCode,
            'source_module' => $developmentMode ? 'PO_JASA_DEV' : 'PO_JASA', 'source_reference' => $sourceReference,
            'source_spk_no' => $developmentMode ? null : $spk->no_spk,
            'source_spk_version' => $developmentMode ? null : max(1, (int) $spk->spk_version_no),
        ));
        $poId = (int) $this->db->insert_id();
        $submissionId = null;
        if (!$developmentMode) {
            $this->db->insert('tbpo_jasa_purchase_submission', array(
                'kd_po_jasa' => $requestCode, 'id_spk' => (int) $spk->id_spk,
                'no_spk' => $spk->no_spk, 'spk_version_no' => max(1, (int) $spk->spk_version_no),
                'source_reference' => $sourceReference, 'id_po_nk' => $poId, 'kd_po_nk' => $purchaseCode,
                'status' => 'PROSES_PEMBELIAN', 'idempotency_token' => $token ?: null,
                'created_by' => (int) $context['id_user'],
            ));
            $submissionId = (int) $this->db->insert_id();
        }

        foreach ($drafts as $draft) {
            $detailReference = $sourceReference . ':DRAFT:' . (int) $draft->id_draft_pembelian;
            $unitId = $draft->stock_unit ? (int) $draft->stock_unit : $this->unitId($draft->satuan);
            $this->db->insert('tbpo_detail_po_nk', array(
                'kd_po_nk' => $purchaseCode, 'kd_po_req' => $requestCode, 'kd_user' => $context['kode_user'],
                'tgl_transaksi' => $today, 'kd_bsys' => $draft->kd_br_adm ?: 'POJASA',
                'kd_barang' => $draft->kd_barang ?: ('JS' . (int) $draft->id_material),
                'nama_barang' => $draft->nama_material, 'deskripsi' => $draft->deskripsi ?: '',
                'keterangan' => $draft->keterangan ?: ('Material PO Jasa ' . $requestCode),
                'qty' => (int) round($draft->qty), 'satuan' => $unitId,
                'kat_barang' => $draft->kat_barang ?: 'PO_JASA',
                'hrg_satuan' => (int) round($draft->harga_estimasi), 'hrg_nyata' => 0,
                'total_harga' => (int) round((float) $draft->qty * (float) $draft->harga_estimasi),
                'total_nyata' => 0, 'gbr_produk' => 'Karisma.png',
                'source_module' => $developmentMode ? 'PO_JASA_DEV' : 'PO_JASA', 'source_reference' => $detailReference,
                'source_material_id' => (int) $draft->id_material, 'source_draft_id' => (int) $draft->id_draft_pembelian,
            ));
            $detailId = (int) $this->db->insert_id();
            if (!$developmentMode) {
                $this->db->insert('tbpo_jasa_purchase_submission_detail', array(
                    'id_submission' => $submissionId, 'kd_po_jasa' => $requestCode,
                    'id_material' => (int) $draft->id_material, 'id_draft_pembelian' => (int) $draft->id_draft_pembelian,
                    'id_detail_po_nk' => $detailId, 'qty_submitted' => (float) $draft->qty,
                    'estimated_unit_price' => (float) $draft->harga_estimasi, 'fulfillment_status' => 'DIAJUKAN',
                ));
                $this->db->where('id_draft_pembelian', (int) $draft->id_draft_pembelian)->update('tbpo_jasa_draft_pembelian', array(
                    'status_draft' => 'DIAJUKAN_KE_PO_PEMBELIAN', 'submitted_po_nk_id' => $poId,
                    'submitted_po_nk_code' => $purchaseCode, 'submitted_detail_po_nk_id' => $detailId,
                    'submitted_at' => date('Y-m-d H:i:s'), 'version' => (int) $draft->version + 1,
                ));
            }
        }

        $this->log($context, $request, $developmentMode ? 'PO_PEMBELIAN_DEV_DIBUAT' : 'PO_PEMBELIAN_OTOMATIS_DIBUAT', array(
            'id_submission' => $submissionId, 'id_po_nk' => $poId, 'kd_po_nk' => $purchaseCode,
            'source_reference' => $sourceReference, 'draft_count' => count($drafts), 'development_mode' => $developmentMode,
        ));
        if (!$developmentMode) {
            $this->notify($request, 'PO_PEMBELIAN_OTOMATIS_DIBUAT', 'Pengajuan pembelian PO Jasa',
                $purchaseCode . ' dibuat otomatis dari ' . $requestCode . '.', array('PURCHASING'));
        }

        if ($this->db->trans_status() === false) {
            return $this->finishFailure($ownsTransaction, 'DATABASE_ERROR');
        }
        return $this->finishSuccess($ownsTransaction, array(
            'success' => true, 'code' => $developmentMode ? 'DEV_CREATED' : 'CREATED', 'id_submission' => $submissionId,
            'id_po_nk' => $poId, 'kd_po_nk' => $purchaseCode,
        ));
    }

    public function receive_purchase($context, $requestCode, $detailId, $quantity, $unitPrice, $date, $documentReference, $token)
    {
        $this->db->trans_begin();
        if (!$this->schema_ready() || !in_array($context['role'], array('ADMIN', 'PURCHASING'), true)) {
            $this->db->trans_rollback();
            return array('success' => false, 'code' => 'FORBIDDEN');
        }
        $replay = $this->db->get_where('tbpo_jasa_purchase_receipt', array('idempotency_token' => $token))->row();
        if ($replay) {
            if ($replay->kd_po_jasa !== $requestCode || (int) $replay->id_detail_po_nk !== (int) $detailId) {
                $this->db->trans_rollback();
                return array('success' => false, 'code' => 'IDEMPOTENCY_CONFLICT');
            }
            $this->db->trans_commit();
            return array('success' => true, 'code' => 'IDEMPOTENT_REPLAY', 'id_receipt' => (int) $replay->id_purchase_receipt);
        }
        $row = $this->db->query(
            'SELECT sd.*,m.id_brg_nk,s.id_po_nk,s.kd_po_nk,s.id_spk,s.spk_version_no,p.status po_status,r.*, '
            . 'b.kd_barang,b.kd_br_adm,b.kat_barang,b.satuan AS stock_satuan '
            . 'FROM tbpo_jasa_purchase_submission_detail sd JOIN tbpo_jasa_purchase_submission s ON s.id_submission=sd.id_submission '
            . 'JOIN tbpo_po_nk p ON p.id_po_nk=s.id_po_nk JOIN tbpo_jasa_material m ON m.id_material=sd.id_material '
            . 'LEFT JOIN tbpo_barang_nk b ON b.id_brg_nk=m.id_brg_nk JOIN tbpo_jasa_request r ON r.kd_po_jasa=sd.kd_po_jasa '
            . 'WHERE sd.id_detail_po_nk=? AND sd.kd_po_jasa=? FOR UPDATE',
            array((int) $detailId, $requestCode)
        )->row();
        if ($row && empty($row->id_brg_nk)) {
            $this->db->trans_rollback();
            return array('success' => false, 'code' => 'MANUAL_MASTER_REQUIRED');
        }
        if (!$row || in_array($row->po_status, array('PENGAJUAN DIBATALKAN', 'REJECT'), true)) {
            $this->db->trans_rollback();
            return array('success' => false, 'code' => 'PURCHASE_DETAIL_INVALID');
        }
        $remaining = round((float) $row->qty_submitted - (float) $row->qty_received, 2);
        if ($quantity <= 0 || $quantity > $remaining + 0.000001 || $unitPrice < 0) {
            $this->db->trans_rollback();
            return array('success' => false, 'code' => 'RECEIPT_QUANTITY_INVALID', 'remaining' => $remaining);
        }
        $receiptReference = 'POJASA-ONHAND-' . (int) $detailId . '-' . strtolower($token);
        $total = round((float) $quantity * (float) $unitPrice, 2);
        $this->db->insert('tbpo_jasa_purchase_receipt', array(
            'id_submission_detail' => (int) $row->id_submission_detail, 'kd_po_jasa' => $requestCode,
            'id_material' => (int) $row->id_material, 'id_detail_po_nk' => (int) $detailId,
            'receipt_reference' => $receiptReference, 'record_type' => 'RECEIPT',
            'qty_received' => $quantity, 'unit_price_actual' => $unitPrice, 'total_actual' => $total,
            'on_hand_date' => $date, 'document_reference' => $documentReference ?: null,
            'idempotency_token' => $token, 'processed_by' => (int) $context['id_user'],
        ));
        $receiptId = (int) $this->db->insert_id();
        $transactionId = $this->postStockTransaction(
            $row, $quantity, '11511', $date, $context,
            'Penerimaan ON_HAND PO Jasa ' . $row->kd_po_nk . ' - ' . $receiptReference
        );
        if (!$transactionId) {
            $this->db->trans_rollback();
            return array('success' => false, 'code' => 'STOCK_POSTING_FAILED');
        }
        $this->db->where('id_purchase_receipt', $receiptId)->update('tbpo_jasa_purchase_receipt', array('id_transnk' => $transactionId));
        $newReceived = round((float) $row->qty_received + (float) $quantity, 2);
        $detailStatus = $newReceived + 0.000001 >= (float) $row->qty_submitted ? 'ON_HAND' : 'PARTIAL_ON_HAND';
        $this->db->where('id_submission_detail', (int) $row->id_submission_detail)->update('tbpo_jasa_purchase_submission_detail', array(
            'qty_received' => $newReceived, 'fulfillment_status' => $detailStatus,
        ));

        $costStatus = $this->budgetStatus($row, (int) $row->id_material, $total);
        $this->db->insert('tbpo_jasa_biaya_aktual', array(
            'kd_po_jasa' => $requestCode, 'revision_no' => (int) $row->revision_no,
            'tgl_biaya' => $date, 'cost_source' => 'PO_PEMBELIAN', 'source_type' => 'PO_PEMBELIAN',
            'record_type' => 'AUTOMATIC', 'record_status' => $costStatus,
            'jenis_biaya' => 'Material PO Pembelian',
            'deskripsi' => 'Penerimaan ' . $quantity . ' unit dari ' . $row->kd_po_nk,
            'nominal' => $total, 'id_dokumen_bukti' => null, 'id_material' => (int) $row->id_material,
            'id_draft_pembelian' => (int) $row->id_draft_pembelian,
            'id_spk' => (int) $row->id_spk, 'spk_version_no' => (int) $row->spk_version_no,
            'source_po_nk_id' => (int) $row->id_po_nk, 'source_po_nk_code' => $row->kd_po_nk,
            'source_detail_po_nk_id' => (int) $detailId, 'source_receipt_reference' => $receiptReference,
            'source_document_reference' => $documentReference ?: null,
            'qty_actual' => $quantity, 'unit_price_actual' => $unitPrice,
            'input_by' => (int) $context['id_user'], 'input_name' => $context['nama_user'],
            'verification_status' => 'OTOMATIS_TERVERIFIKASI', 'verified_by' => (int) $context['id_user'],
            'verified_at' => date('Y-m-d H:i:s'), 'verification_note' => 'Dibentuk otomatis saat ON_HAND.',
            'idempotency_token' => $token, 'payload_hash' => hash('sha256', $receiptReference . '|' . $quantity . '|' . $unitPrice),
        ));
        $costId = (int) $this->db->insert_id();

        $receivedTotals = $this->db->query(
            'SELECT COALESCE(SUM(total_actual),0) total,COALESCE(SUM(qty_received),0) qty FROM tbpo_jasa_purchase_receipt WHERE id_submission_detail=?',
            array((int) $row->id_submission_detail)
        )->row();
        $average = abs((float) $receivedTotals->qty) > 0.000001 ? (float) $receivedTotals->total / (float) $receivedTotals->qty : 0;
        $this->db->where('id_det_po_nk', (int) $detailId)->update('tbpo_detail_po_nk', array(
            'hrg_nyata' => (int) round($average), 'total_nyata' => (int) round($receivedTotals->total),
        ));
        $pendingCount = (int) $this->db->where('id_submission', (int) $row->id_submission)
            ->where('fulfillment_status !=', 'ON_HAND')->count_all_results('tbpo_jasa_purchase_submission_detail');
        $submissionStatus = $pendingCount === 0 ? 'ON_HAND' : 'PARTIAL_ON_HAND';
        $this->db->where('id_submission', (int) $row->id_submission)->update('tbpo_jasa_purchase_submission', array('status' => $submissionStatus));
        // PO Pembelian yang dibuat oleh PO Jasa harus selalu berada pada antrian
        // monitoring pembelian. Status pemenuhan material tetap dicatat terpisah
        // pada submission/detail, sehingga header legacy tidak boleh berubah DONE.
        $this->db->where('id_po_nk', (int) $row->id_po_nk)->update('tbpo_po_nk', array('status' => 'PROSES PEMBELIAN'));

        $this->log($context, $row, 'PO_PEMBELIAN_ON_HAND', array(
            'id_purchase_receipt' => $receiptId, 'id_biaya_aktual' => $costId,
            'id_detail_po_nk' => (int) $detailId, 'id_transnk' => $transactionId, 'quantity' => $quantity,
            'unit_price' => $unitPrice, 'total' => $total, 'status' => $costStatus,
        ));
        $this->notify($row, 'PO_PEMBELIAN_ON_HAND', 'Biaya otomatis PO Jasa',
            'Penerimaan ' . $row->kd_po_nk . ' dicatat sebesar Rp ' . number_format($total, 0, ',', '.') . '.',
            $costStatus === 'MENUNGGU_PERSETUJUAN_REVISI'
                ? array('PIC', 'PURCHASING', 'APPROVER') : array('PIC', 'PURCHASING'));

        if ($this->db->trans_status() === false) {
            $this->db->trans_rollback();
            return array('success' => false, 'code' => 'DATABASE_ERROR');
        }
        $this->db->trans_commit();
        return array('success' => true, 'code' => $detailStatus, 'id_receipt' => $receiptId, 'id_cost' => $costId, 'id_transnk' => $transactionId, 'cost_status' => $costStatus);
    }

    public function reverse_receipt($context, $requestCode, $receiptId, $reason, $token)
    {
        $this->db->trans_begin();
        if (!in_array($context['role'], array('ADMIN', 'PURCHASING'), true) || trim((string) $reason) === '') {
            $this->db->trans_rollback();
            return array('success' => false, 'code' => 'FORBIDDEN');
        }
        $replay = $this->db->get_where('tbpo_jasa_purchase_receipt', array('idempotency_token' => $token))->row();
        if ($replay) {
            $this->db->trans_commit();
            return array('success' => true, 'code' => 'IDEMPOTENT_REPLAY', 'id_receipt' => (int) $replay->id_purchase_receipt);
        }
        $original = $this->db->query('SELECT * FROM tbpo_jasa_purchase_receipt WHERE id_purchase_receipt=? AND kd_po_jasa=? FOR UPDATE', array((int) $receiptId, $requestCode))->row();
        if (!$original || $original->record_type !== 'RECEIPT'
            || $this->db->where('reversal_of_id', (int) $receiptId)->count_all_results('tbpo_jasa_purchase_receipt') > 0) {
            $this->db->trans_rollback();
            return array('success' => false, 'code' => 'RECEIPT_NOT_REVERSIBLE');
        }
        $reference = 'POJASA-REVERSAL-' . (int) $receiptId . '-' . strtolower($token);
        $this->db->insert('tbpo_jasa_purchase_receipt', array(
            'id_submission_detail' => (int) $original->id_submission_detail, 'kd_po_jasa' => $requestCode,
            'id_material' => (int) $original->id_material, 'id_detail_po_nk' => (int) $original->id_detail_po_nk,
            'receipt_reference' => $reference, 'record_type' => 'REVERSAL', 'reversal_of_id' => (int) $receiptId,
            'qty_received' => -(float) $original->qty_received, 'unit_price_actual' => (float) $original->unit_price_actual,
            'total_actual' => -(float) $original->total_actual, 'on_hand_date' => date('Y-m-d'),
            'document_reference' => $reason, 'idempotency_token' => $token, 'processed_by' => (int) $context['id_user'],
        ));
        $reversalId = (int) $this->db->insert_id();
        $request = $this->db->get_where('tbpo_jasa_request', array('kd_po_jasa' => $requestCode))->row();
        $stockItem = $this->db->query(
            'SELECT b.kd_barang,b.kd_br_adm,b.kat_barang,b.satuan AS stock_satuan,s.kd_po_nk '
            . 'FROM tbpo_jasa_purchase_submission_detail sd '
            . 'JOIN tbpo_jasa_purchase_submission s ON s.id_submission=sd.id_submission '
            . 'JOIN tbpo_jasa_material m ON m.id_material=sd.id_material '
            . 'JOIN tbpo_barang_nk b ON b.id_brg_nk=m.id_brg_nk '
            . 'WHERE sd.id_submission_detail=? FOR UPDATE',
            array((int) $original->id_submission_detail)
        )->row();
        if (!$stockItem) {
            $this->db->trans_rollback();
            return array('success' => false, 'code' => 'MANUAL_MASTER_REQUIRED');
        }
        $stockItem->kd_user = $request->kd_user;
        $stockTransactionId = $this->postStockTransaction(
            $stockItem, (float) $original->qty_received, '11514', date('Y-m-d'), $context,
            'Reversal ON_HAND PO Jasa ' . $stockItem->kd_po_nk . ' - ' . $reference
        );
        if (!$stockTransactionId) {
            $this->db->trans_rollback();
            return array('success' => false, 'code' => 'STOCK_POSTING_FAILED');
        }
        $this->db->where('id_purchase_receipt', $reversalId)->update('tbpo_jasa_purchase_receipt', array('id_transnk' => $stockTransactionId));
        $spk = $this->db->get_where('tbpo_jasa_spk', array('kd_po_jasa' => $requestCode))->row();
        $sourceCost = $this->db->get_where('tbpo_jasa_biaya_aktual', array('source_receipt_reference' => $original->receipt_reference))->row();
        $this->db->insert('tbpo_jasa_biaya_aktual', array(
            'kd_po_jasa' => $requestCode, 'revision_no' => (int) $request->revision_no, 'tgl_biaya' => date('Y-m-d'),
            'cost_source' => 'PO_PEMBELIAN', 'source_type' => 'PO_PEMBELIAN', 'record_type' => 'REVERSAL',
            'record_status' => 'PENYESUAIAN', 'jenis_biaya' => 'Reversal penerimaan PO Pembelian',
            'deskripsi' => $reason, 'nominal' => -(float) $original->total_actual, 'id_material' => (int) $original->id_material,
            'id_spk' => $spk ? (int) $spk->id_spk : null, 'spk_version_no' => $spk ? (int) $spk->spk_version_no : null,
            'source_detail_po_nk_id' => (int) $original->id_detail_po_nk, 'source_receipt_reference' => $reference,
            'qty_actual' => -(float) $original->qty_received, 'unit_price_actual' => (float) $original->unit_price_actual,
            'parent_cost_id' => $sourceCost ? (int) $sourceCost->id_biaya_aktual : null,
            'input_by' => (int) $context['id_user'], 'input_name' => $context['nama_user'],
            'verification_status' => 'OTOMATIS_TERVERIFIKASI', 'verified_by' => (int) $context['id_user'],
            'verified_at' => date('Y-m-d H:i:s'), 'verification_note' => $reason,
            'idempotency_token' => $token, 'payload_hash' => hash('sha256', $reference),
        ));
        $costId = (int) $this->db->insert_id();
        $detail = $this->db->query('SELECT * FROM tbpo_jasa_purchase_submission_detail WHERE id_submission_detail=? FOR UPDATE', array((int) $original->id_submission_detail))->row();
        $newReceived = max(0, (float) $detail->qty_received - (float) $original->qty_received);
        $this->db->where('id_submission_detail', (int) $detail->id_submission_detail)->update('tbpo_jasa_purchase_submission_detail', array(
            'qty_received' => $newReceived,
            'fulfillment_status' => $newReceived <= 0.000001 ? 'DIAJUKAN' : ($newReceived + 0.000001 >= (float) $detail->qty_submitted ? 'ON_HAND' : 'PARTIAL_ON_HAND'),
        ));
        $totals = $this->db->query(
            'SELECT COALESCE(SUM(total_actual),0) total,COALESCE(SUM(qty_received),0) qty FROM tbpo_jasa_purchase_receipt WHERE id_submission_detail=?',
            array((int) $detail->id_submission_detail)
        )->row();
        $average = abs((float) $totals->qty) > 0.000001 ? (float) $totals->total / (float) $totals->qty : 0;
        $this->db->where('id_det_po_nk', (int) $original->id_detail_po_nk)->update('tbpo_detail_po_nk', array(
            'hrg_nyata' => (int) round($average), 'total_nyata' => (int) round($totals->total),
        ));
        $pendingCount = (int) $this->db->where('id_submission', (int) $detail->id_submission)
            ->where('fulfillment_status !=', 'ON_HAND')->count_all_results('tbpo_jasa_purchase_submission_detail');
        $submissionStatus = $pendingCount === 0 ? 'ON_HAND' : ($newReceived > 0 ? 'PARTIAL_ON_HAND' : 'PROSES_PEMBELIAN');
        $submission = $this->db->get_where('tbpo_jasa_purchase_submission', array('id_submission' => (int) $detail->id_submission))->row();
        $this->db->where('id_submission', (int) $detail->id_submission)->update('tbpo_jasa_purchase_submission', array('status' => $submissionStatus));
        if ($submission) {
            // Reversal tidak mengubah header PO Jasa menjadi DONE; status
            // penerimaan tetap tersedia di tbpo_jasa_purchase_submission.
            $this->db->where('id_po_nk', (int) $submission->id_po_nk)->update('tbpo_po_nk', array('status' => 'PROSES PEMBELIAN'));
        }
        $this->log($context, $request, 'PO_PEMBELIAN_ON_HAND_DIBALIK', array('original_receipt_id' => (int) $receiptId, 'reversal_receipt_id' => $reversalId, 'id_biaya_aktual' => $costId, 'id_transnk' => $stockTransactionId, 'reason' => $reason));
        $this->notify($request, 'PO_PEMBELIAN_ON_HAND_DIBALIK', 'Penyesuaian biaya PO Jasa', 'Penerimaan dibalik: ' . $reason, array('PIC', 'PURCHASING'));
        if ($this->db->trans_status() === false) {
            $this->db->trans_rollback();
            return array('success' => false, 'code' => 'DATABASE_ERROR');
        }
        $this->db->trans_commit();
        return array('success' => true, 'code' => 'REVERSED', 'id_receipt' => $reversalId, 'id_cost' => $costId, 'id_transnk' => $stockTransactionId);
    }

    public function get_submission_state($requestCode)
    {
        if (!$this->schema_ready()) {
            return array('submission' => null, 'details' => array(), 'receipts' => array(), 'adjustments' => array());
        }
        $submission = $this->db->select('s.*, p.status AS po_status')
            ->from('tbpo_jasa_purchase_submission s')
            ->join('tbpo_po_nk p', 'p.id_po_nk = s.id_po_nk', 'left')
            ->where('s.kd_po_jasa', $requestCode)
            ->order_by('s.id_submission', 'DESC')
            ->limit(1)
            ->get()
            ->row_array();
        if (!$submission) {
            return array('submission' => null, 'details' => array(), 'receipts' => array(), 'adjustments' => array());
        }
        $details = $this->db->select('sd.*,m.nama_material,m.deskripsi,m.satuan,d.hrg_nyata,d.total_nyata')
            ->from('tbpo_jasa_purchase_submission_detail sd')->join('tbpo_jasa_material m', 'm.id_material=sd.id_material')
            ->join('tbpo_detail_po_nk d', 'd.id_det_po_nk=sd.id_detail_po_nk')
            ->where('sd.id_submission', (int) $submission['id_submission'])->order_by('sd.id_submission_detail', 'ASC')->get()->result_array();
        $receipts = $this->db->order_by('id_purchase_receipt', 'DESC')->get_where('tbpo_jasa_purchase_receipt', array('kd_po_jasa' => $requestCode))->result_array();
        $adjustments = $this->db->order_by('id_adjustment', 'DESC')->get_where('tbpo_jasa_purchase_adjustment', array('kd_po_jasa' => $requestCode))->result_array();
        return array('submission' => $submission, 'details' => $details, 'receipts' => $receipts, 'adjustments' => $adjustments);
    }

    /**
     * Removes an automatically generated legacy PO only before it has any
     * receipt or adjustment. The originating purchase drafts are reopened so
     * Purchasing can revise the remaining quantity and submit a new PO.
     */
    public function delete_purchase_submission($context, $requestCode, $reason)
    {
        if (!$this->schema_ready() || !in_array($context['role'], array('ADMIN', 'PURCHASING'), true)) {
            return array('success' => false, 'code' => 'FORBIDDEN');
        }

        $this->db->trans_begin();
        $request = $this->db->query('SELECT * FROM tbpo_jasa_request WHERE kd_po_jasa=? FOR UPDATE', array($requestCode))->row();
        if (!$request) {
            $this->db->trans_rollback();
            return array('success' => false, 'code' => 'NOT_FOUND');
        }
        $submission = $this->db->query(
            'SELECT * FROM tbpo_jasa_purchase_submission WHERE kd_po_jasa=? ORDER BY id_submission DESC LIMIT 1 FOR UPDATE',
            array($requestCode)
        )->row();
        if (!$submission) {
            $this->db->trans_rollback();
            return array('success' => false, 'code' => 'NOT_FOUND');
        }
        $purchase = $this->db->query('SELECT * FROM tbpo_po_nk WHERE id_po_nk=? FOR UPDATE', array((int) $submission->id_po_nk))->row();
        if (!$purchase || $purchase->source_module !== 'PO_JASA' || $purchase->status !== 'PROSES PEMBELIAN') {
            $this->db->trans_rollback();
            return array('success' => false, 'code' => 'PURCHASE_DELETE_NOT_ALLOWED');
        }
        $details = $this->db->query(
            'SELECT * FROM tbpo_jasa_purchase_submission_detail WHERE id_submission=? FOR UPDATE',
            array((int) $submission->id_submission)
        )->result();
        $detailIds = array_map(function ($detail) { return (int) $detail->id_submission_detail; }, $details);
        if ($detailIds) {
            $receiptCount = (int) $this->db->where_in('id_submission_detail', $detailIds)->count_all_results('tbpo_jasa_purchase_receipt');
            if ($receiptCount > 0) {
                $this->db->trans_rollback();
                return array('success' => false, 'code' => 'PURCHASE_DELETE_RECEIPT_EXISTS');
            }
            $adjustmentCount = (int) $this->db->where_in('id_submission_detail', $detailIds)->count_all_results('tbpo_jasa_purchase_adjustment');
            if ($adjustmentCount > 0) {
                $this->db->trans_rollback();
                return array('success' => false, 'code' => 'PURCHASE_DELETE_ADJUSTMENT_EXISTS');
            }
        }

        foreach ($details as $detail) {
            $draft = $this->db->query('SELECT * FROM tbpo_jasa_draft_pembelian WHERE id_draft_pembelian=? FOR UPDATE', array((int) $detail->id_draft_pembelian))->row();
            if ($draft && $draft->status_draft === 'DIAJUKAN_KE_PO_PEMBELIAN') {
                $this->db->where('id_draft_pembelian', (int) $draft->id_draft_pembelian)->update('tbpo_jasa_draft_pembelian', array(
                    'status_draft' => 'DRAFT', 'submitted_po_nk_id' => null, 'submitted_po_nk_code' => null,
                    'submitted_detail_po_nk_id' => null, 'submitted_at' => null, 'version' => (int) $draft->version + 1,
                ));
            }
        }
        if ($detailIds) {
            $this->db->where_in('id_submission_detail', $detailIds)->delete('tbpo_jasa_purchase_submission_detail');
        }
        $this->db->where('id_submission', (int) $submission->id_submission)->delete('tbpo_jasa_purchase_submission');
        $this->db->where(array('kd_po_nk' => $purchase->kd_po_nk, 'source_module' => 'PO_JASA'))->delete('tbpo_detail_po_nk');
        $this->db->where(array('id_po_nk' => (int) $purchase->id_po_nk, 'source_module' => 'PO_JASA'))->delete('tbpo_po_nk');
        $this->log($context, $request, 'PO_PEMBELIAN_DIHAPUS', array(
            'id_submission' => (int) $submission->id_submission, 'id_po_nk' => (int) $purchase->id_po_nk,
            'kd_po_nk' => $purchase->kd_po_nk, 'reason' => $reason,
        ));
        if ($this->db->trans_status() === false) {
            $this->db->trans_rollback();
            return array('success' => false, 'code' => 'DATABASE_ERROR');
        }
        $this->db->trans_commit();
        return array('success' => true, 'code' => 'PURCHASE_DELETED');
    }

    public function sync_material_change($context, $requestCode, $changeId, $materialId, $before, $after)
    {
        $mapping = $this->db->select('sd.*,s.id_po_nk,p.status po_status')
            ->from('tbpo_jasa_purchase_submission_detail sd')->join('tbpo_jasa_purchase_submission s', 's.id_submission=sd.id_submission')
            ->join('tbpo_po_nk p', 'p.id_po_nk=s.id_po_nk')->where(array('sd.kd_po_jasa' => $requestCode, 'sd.id_material' => (int) $materialId))->get()->row();
        if (!$mapping) {
            return true;
        }
        $status = $mapping->po_status === 'DONE' ? 'MENUNGGU_PENYESUAIAN' : 'DITERAPKAN';
        $this->db->insert('tbpo_jasa_purchase_adjustment', array(
            'kd_po_jasa' => $requestCode, 'id_spk_change' => (int) $changeId,
            'id_submission_detail' => (int) $mapping->id_submission_detail,
            'adjustment_type' => 'MATERIAL', 'before_json' => json_encode($before),
            'after_json' => json_encode($after), 'status' => $status, 'created_by' => (int) $context['id_user'],
        ));
        if ($status === 'DITERAPKAN') {
            if (!$this->isWholeNumber($after['qty_kebutuhan']) || !$this->isWholeNumber($after['harga_estimasi'])) {
                return false;
            }
            $this->db->where('id_det_po_nk', (int) $mapping->id_detail_po_nk)->update('tbpo_detail_po_nk', array(
                'nama_barang' => $after['nama_material'], 'deskripsi' => $after['deskripsi'],
                'qty' => (int) round($after['qty_kebutuhan']), 'hrg_satuan' => (int) round($after['harga_estimasi']),
                'total_harga' => (int) round((float) $after['qty_kebutuhan'] * (float) $after['harga_estimasi']),
            ));
            $this->db->where('id_submission_detail', (int) $mapping->id_submission_detail)->update('tbpo_jasa_purchase_submission_detail', array(
                'qty_submitted' => (float) $after['qty_kebutuhan'], 'estimated_unit_price' => (float) $after['harga_estimasi'],
            ));
        }
        return $this->db->trans_status();
    }

    public function evaluate_manual_cost_status($request, $materialId, $additionalAmount, $excludeCostId = null, $scopeId = null)
    {
        return $this->budgetStatus($request, $materialId, $additionalAmount, $excludeCostId, $scopeId);
    }

    private function budgetStatus($request, $materialId, $additionalAmount, $excludeCostId = null, $scopeId = null)
    {
        $approvedTotal = $request->estimasi_disetujui === null ? (float) $request->estimasi_total : (float) $request->estimasi_disetujui;
        $this->db->select('COALESCE(SUM(nominal),0) total', false)->from('tbpo_jasa_biaya_aktual')->where('kd_po_jasa', $request->kd_po_jasa);
        if ($excludeCostId) {
            $this->db->where('id_biaya_aktual !=', (int) $excludeCostId);
        }
        $actual = (float) $this->db->get()->row()->total + (float) $additionalAmount;
        $over = $actual > $approvedTotal + 0.000001;
        if ($materialId) {
            $material = $this->db->get_where('tbpo_jasa_material', array('id_material' => (int) $materialId, 'kd_po_jasa' => $request->kd_po_jasa, 'is_active' => 1))->row();
            if ($material) {
                $this->db->select('COALESCE(SUM(nominal),0) total', false)->from('tbpo_jasa_biaya_aktual')->where(array('kd_po_jasa' => $request->kd_po_jasa, 'id_material' => (int) $materialId));
                if ($excludeCostId) {
                    $this->db->where('id_biaya_aktual !=', (int) $excludeCostId);
                }
                $materialActual = (float) $this->db->get()->row()->total + (float) $additionalAmount;
                $over = $over || $materialActual > (float) $material->total_estimasi + 0.000001;
            }
        }
        if ($scopeId) {
            $scope = $this->db->get_where('tbpo_jasa_scope', array('id_scope' => (int) $scopeId, 'kd_po_jasa' => $request->kd_po_jasa, 'is_active' => 1))->row();
            if ($scope) {
                $this->db->select('COALESCE(SUM(nominal),0) total', false)->from('tbpo_jasa_biaya_aktual')->where(array('kd_po_jasa' => $request->kd_po_jasa, 'id_scope' => (int) $scopeId));
                if ($excludeCostId) $this->db->where('id_biaya_aktual !=', (int) $excludeCostId);
                $scopeActual = (float) $this->db->get()->row()->total + (float) $additionalAmount;
                $over = $over || $scopeActual > (float) $scope->total_estimasi + 0.000001;
            }
        }
        return $over ? 'MENUNGGU_PERSETUJUAN_REVISI' : 'DICATAT';
    }

    private function submissionReference($requestCode, $spk)
    {
        return 'PO_JASA:' . $requestCode . ':SPK:' . $spk->no_spk . ':V' . max(1, (int) $spk->spk_version_no);
    }

    private function departmentHeadCode($request)
    {
        // The approved KADEP is the authoritative source.  The lookup supports
        // legacy requests that predate the approval-audit column.
        $approvedCode = trim((string) $request->acc_with_kadep);
        if ($approvedCode !== '') {
            return $approvedCode;
        }
        $head = $this->db->query(
            'SELECT kode_user FROM tbpo_user WHERE aksess_lv=5 AND UPPER(TRIM(departement))=UPPER(TRIM(?)) ORDER BY id_user ASC LIMIT 1',
            array($request->departemen)
        )->row();
        return $head ? (string) $head->kode_user : null;
    }

    private function unitId($unitName)
    {
        $row = $this->db->query('SELECT id_satuan FROM tbpo_satuan WHERE UPPER(TRIM(nm_satuan))=UPPER(TRIM(?)) ORDER BY id_satuan ASC LIMIT 1', array((string) $unitName))->row();
        return $row ? (int) $row->id_satuan : 0;
    }

    /**
     * Posts the authoritative stock movement for a Purchasing ON_HAND event.
     * 11511 is stock-in from purchase; 11514 is the matching stock-out
     * adjustment when that ON_HAND event is reversed.
     */
    private function postStockTransaction($item, $quantity, $accountCode, $date, $context, $description)
    {
        if (empty($item->kd_barang) || empty($item->kd_br_adm) || empty($item->kat_barang)
            || (int) $item->stock_satuan < 1 || !in_array($accountCode, array('11511', '11514'), true)) {
            return false;
        }
        $this->db->insert('tbpo_transaksi', array(
            'kd_akun' => $accountCode,
            'kd_po_nk' => substr((string) $item->kd_po_nk, 0, 25),
            'kd_barang' => substr((string) $item->kd_barang, 0, 25),
            'kd_barangsys' => substr((string) $item->kd_br_adm, 0, 25),
            'keterangan' => $description,
            'kat_barang' => substr((string) $item->kat_barang, 0, 25),
            'tr_qty' => (float) $quantity,
            'satuan' => (int) $item->stock_satuan,
            'inputer' => substr((string) $context['kode_user'], 0, 25),
            'req_by' => substr((string) $item->kd_user, 0, 25),
            'tgl_transaksi' => substr((string) $date, 0, 10),
            'create_at' => substr((string) $date, 0, 10),
            'last_updated_by' => substr((string) $context['kode_user'], 0, 25),
        ));
        return $this->db->affected_rows() === 1 ? (int) $this->db->insert_id() : false;
    }

    private function isWholeNumber($value)
    {
        return is_numeric($value) && abs((float) $value - round((float) $value)) < 0.000001;
    }

    private function log($context, $request, $event, $metadata)
    {
        return $this->M_PojasaCore->append_activity_log(array(
            'kd_po_jasa' => $request->kd_po_jasa, 'revision_no' => max(1, (int) $request->revision_no),
            'event_type' => $event, 'from_status' => $request->status, 'to_status' => $request->status,
            'actor_id' => (int) $context['id_user'], 'actor_code' => $context['kode_user'],
            'actor_name' => $context['nama_user'], 'actor_role' => $context['role'],
            'actor_departemen' => $context['departemen'], 'metadata_json' => json_encode($metadata),
        ));
    }

    private function notify($request, $event, $title, $message, $targets)
    {
        $where = array();
        $binds = array();
        if (in_array('PIC', $targets, true)) {
            $where[] = 'kode_user=?';
            $binds[] = $request->kd_user;
        }
        if (in_array('PURCHASING', $targets, true)) {
            $where[] = "(aksess_lv=2 AND UPPER(TRIM(departement))='PURCHASING')";
        }
        if (in_array('APPROVER', $targets, true)) {
            if (pojasa_requires_dirut_ops($request->departemen)) {
                $where[] = "(aksess_lv=6 AND UPPER(TRIM(departement)) IN ('DIREKTUR OPERASIONAL','DIREKTUR OPRASIONAL'))";
            }
            $where[] = "(aksess_lv=3 AND UPPER(TRIM(departement))='DIREKTUR')";
        }
        if (!$where) {
            return true;
        }
        $users = $this->db->query('SELECT DISTINCT id_user FROM tbpo_user WHERE ' . implode(' OR ', $where), $binds)->result();
        $rows = array();
        foreach ($users as $user) {
            $rows[] = array(
                'kd_po_jasa' => $request->kd_po_jasa, 'event_type' => $event,
                'recipient_user_id' => (int) $user->id_user, 'title' => $title, 'message' => $message,
                'target_url' => base_url('pojasa/workflow/detail/' . $request->kd_po_jasa),
            );
        }
        return $this->M_PojasaCore->create_notifications($rows);
    }

    private function finishFailure($ownsTransaction, $code)
    {
        if ($ownsTransaction) {
            $this->db->trans_rollback();
        }
        return array('success' => false, 'code' => $code);
    }

    private function finishSuccess($ownsTransaction, $result)
    {
        if ($this->db->trans_status() === false) {
            return $this->finishFailure($ownsTransaction, 'DATABASE_ERROR');
        }
        if ($ownsTransaction) {
            $this->db->trans_commit();
        }
        return $result;
    }
}
