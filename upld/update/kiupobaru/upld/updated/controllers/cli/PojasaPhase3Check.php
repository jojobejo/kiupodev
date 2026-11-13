<?php
defined('BASEPATH') or exit('No direct script access allowed');

class PojasaPhase3Check extends CI_Controller
{
    private $checks = array();

    public function index()
    {
        if (!is_cli()) {
            show_404();
            return;
        }
        $this->load->helper(array('pojasa_authorization', 'pojasa_status', 'pojasa_document'));
        $this->load->model('PO/M_PojasaPic');

        $this->assert('phase3_schema_ready', $this->M_PojasaPic->schema_ready());
        $this->testDocumentPolicy();
        if ($this->M_PojasaPic->schema_ready()) {
            $this->testPicWorkflowWithRollback();
        }

        $failed = array_filter($this->checks, function ($check) {
            return !$check['passed'];
        });
        echo json_encode(array('success' => empty($failed), 'checks' => $this->checks), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL;
        if ($failed) {
            exit(1);
        }
    }

    private function testDocumentPolicy()
    {
        $fixture = FCPATH . 'license.txt';
        $base = array('tmp_name' => $fixture, 'error' => UPLOAD_ERR_OK, 'size' => filesize($fixture));
        $valid = pojasa_validate_document_file(array_merge($base, array('name' => 'ketentuan.txt')));
        $invalidExtension = pojasa_validate_document_file(array_merge($base, array('name' => 'payload.exe')));
        $invalidMime = pojasa_validate_document_file(array_merge($base, array('name' => 'foto.jpg')));
        $oversize = pojasa_validate_document_file(array_merge($base, array('name' => 'ketentuan.txt', 'size' => 10485761)));
        $this->assert('document_text_allowed', $valid['success'], $valid);
        $this->assert('document_extension_blocked', !$invalidExtension['success'], $invalidExtension);
        $this->assert('document_mime_mismatch_blocked', !$invalidMime['success'], $invalidMime);
        $this->assert('document_over_10mb_blocked', !$oversize['success'], $oversize);
    }

    private function testPicWorkflowWithRollback()
    {
        $pic = $this->db->query('SELECT id_user, kode_user, nama_user, departement FROM tbpo_user WHERE aksess_lv = 4 ORDER BY id_user ASC LIMIT 1')->row();
        if (!$pic) {
            $this->assert('pic_fixture_available', false, 'User PIC level 4 tidak tersedia.');
            return;
        }
        $before = (int) $this->db->count_all('tbpo_jasa_request');
        $vendorCountBefore = (int) $this->db->count_all('tbpo_jasa_vendor');
        $context = array(
            'id_user' => (int) $pic->id_user,
            'kode_user' => (string) $pic->kode_user,
            'nama_user' => (string) $pic->nama_user,
            'departemen' => pojasa_normalize_department($pic->departement),
            'role' => 'PIC',
            'ip_address' => '127.0.0.1',
            'user_agent' => 'PojasaPhase3Check',
        );
        $otherContext = $context;
        $otherContext['kode_user'] = 'PHASE3_OTHER_PIC';
        $header = array(
            'kd_vendor_jasa' => null,
            'vendor_usulan' => 'Vendor Uji Fase 3',
            'tgl_request' => '2026-09-10',
            'tgl_target' => '2026-09-20',
            'tgl_mulai_pekerjaan' => '2026-09-11',
            'tgl_selesai_pekerjaan' => '2026-09-20',
            'lokasi_pekerjaan' => 'Lokasi uji',
            'tujuan_pekerjaan' => 'Uji alur request PIC dengan rollback',
            'catatan_pic' => 'PHASE3_ROLLBACK_TEST',
        );
        $scopes = array(array('nama_scope' => 'Instalasi', 'deskripsi' => 'Scope test', 'qty' => 2, 'satuan' => 'LOT', 'harga_estimasi' => 125000));
        $materials = array(array('id_brg_nk' => null, 'nama_material' => 'Material test', 'deskripsi' => 'Material opsional', 'qty_kebutuhan' => 3, 'satuan' => 'PCS', 'harga_estimasi' => 10000, 'sumber_material' => 'PEMBELIAN'));

        $this->db->trans_begin();
        $saved = $this->M_PojasaPic->save_draft($context, '', $header, $scopes, $materials);
        $code = isset($saved['request_code']) ? $saved['request_code'] : '';
        $request = $code ? $this->M_PojasaPic->get_request($code, $context) : null;
        $this->assert('draft_created', $saved['success'] && $request && $request->status === 'DRAFT', $saved);
        $this->assert('manual_vendor_saved_on_request', $request && empty($request->kd_vendor_jasa) && $request->vendor_usulan === 'Vendor Uji Fase 3');
        $this->assert('manual_vendor_does_not_create_master', (int) $this->db->count_all('tbpo_jasa_vendor') === $vendorCountBefore);
        $this->assert('draft_atomic_number_format', (bool) preg_match('/^PJASA\d{10}$/', $code), $code);
        $this->assert('draft_totals_calculated', $request && (float) $request->estimasi_total_jasa === 250000.0 && (float) $request->estimasi_total_bahan === 30000.0 && (float) $request->estimasi_total === 280000.0);
        $this->assert('pic_owner_can_edit_draft', $this->M_PojasaPic->can_edit($request, $context));
        $this->assert('other_pic_cannot_read', $this->M_PojasaPic->get_request($code, $otherContext) === null);

        $list = $this->M_PojasaPic->datatable_requests($context, array('status' => 'DRAFT', 'date_from' => '', 'date_to' => '', 'search' => $code, 'order_column' => 0, 'order_direction' => 'desc', 'start' => 0, 'length' => 10));
        $this->assert('datatable_owner_filter', $list['filtered'] === 1 && count($list['rows']) === 1);

        $withoutDocument = $this->M_PojasaPic->submit_request($context, $code);
        $this->assert('submit_requires_document', !$withoutDocument['success'] && $withoutDocument['code'] === 'SUBMIT_VALIDATION' && isset($withoutDocument['errors']['documents']), $withoutDocument);

        $document = array(
            'jenis_dokumen' => 'DOKUMEN_REQUEST', 'keterangan' => 'Dokumen transactional test',
            'file_path' => 'images/pojasa/PHASE3-TEST/document.txt', 'file_original' => 'document.txt',
            'mime_type' => 'text/plain', 'file_size_bytes' => 10, 'sha256' => str_repeat('3', 64),
        );
        $added = $this->M_PojasaPic->add_documents($context, $code, array($document));
        $documentRow = $this->db->where('kd_po_jasa', $code)->get('tbpo_jasa_dokumen')->row();
        $this->assert('document_record_added', $added['success'] && $documentRow !== null, $added);

        $submitted = $this->M_PojasaPic->submit_request($context, $code);
        $submittedRequest = $this->M_PojasaPic->get_request($code, $context);
        $this->assert('draft_submitted_to_initial_purchasing', $submitted['success'] && $submittedRequest->status === 'MENUNGGU_PURCHASING_AWAL', $submitted);
        $this->assert('submitted_request_locked', !$this->M_PojasaPic->can_edit($submittedRequest, $context));
        $submittedRequest->status = 'PENDING_KADEP';
        $this->assert('pending_kadep_locked', !$this->M_PojasaPic->can_edit($submittedRequest, $context));

        $this->db->where('kd_po_jasa', $code)->update('tbpo_jasa_request', array('status' => 'REVISI_PIC'));
        $this->db->insert('tbpo_jasa_revisi', array(
            'kd_po_jasa' => $code, 'revision_no' => 2, 'revision_source' => 'KADEP',
            'alasan_revisi' => 'Uji revisi PIC', 'requested_by' => (int) $pic->id_user,
        ));
        $revisionSaved = $this->M_PojasaPic->save_draft($context, $code, $header, array(array('nama_scope' => 'Instalasi revisi', 'deskripsi' => 'Revisi', 'qty' => 1, 'satuan' => 'LOT', 'harga_estimasi' => 300000)), $materials);
        $revisionRequest = $this->M_PojasaPic->get_request($code, $context);
        $this->assert('revision_saved_as_new_revision', $revisionSaved['success'] && (int) $revisionRequest->edit_revision_no === 2 && (int) $revisionSaved['revision_no'] === 2, $revisionSaved);
        $revisionSubmitted = $this->M_PojasaPic->submit_request($context, $code);
        $revisionRequest = $this->M_PojasaPic->get_request($code, $context);
        $this->assert('revision_resubmitted', $revisionSubmitted['success'] && (int) $revisionRequest->revision_no === 2 && $revisionRequest->status === 'MENUNGGU_PURCHASING_AWAL', $revisionSubmitted);

        $second = $this->M_PojasaPic->save_draft($context, '', $header, $scopes, array());
        $secondCode = $second['request_code'];
        $this->M_PojasaPic->add_documents($context, $secondCode, array($document));
        $secondDocument = $this->db->where('kd_po_jasa', $secondCode)->get('tbpo_jasa_dokumen')->row();
        $archived = $this->M_PojasaPic->archive_document($context, (int) $secondDocument->id_dokumen);
        $activeDocumentCount = (int) $this->db->where(array('kd_po_jasa' => $secondCode, 'is_active' => 1))->count_all_results('tbpo_jasa_dokumen');
        $this->assert('document_soft_archive', $archived['success'] && $activeDocumentCount === 0, $archived);
        $deleted = $this->M_PojasaPic->soft_delete_draft($context, $secondCode);
        $deletedRequest = $this->db->get_where('tbpo_jasa_request', array('kd_po_jasa' => $secondCode))->row();
        $this->assert('draft_soft_delete', $deleted['success'] && !empty($deletedRequest->deleted_at), $deleted);

        $auditCount = (int) $this->db->where('kd_po_jasa', $code)->count_all_results('tbpo_jasa_log_aktivitas');
        $this->assert('pic_actions_audited', $auditCount >= 5, $auditCount);
        $this->db->trans_rollback();

        $after = (int) $this->db->count_all('tbpo_jasa_request');
        $markerCount = (int) $this->db->where('catatan_pic', 'PHASE3_ROLLBACK_TEST')->count_all_results('tbpo_jasa_request');
        $this->assert('transactional_test_rolled_back', $after === $before && $markerCount === 0, array('before' => $before, 'after' => $after, 'markers' => $markerCount));
    }

    private function assert($name, $passed, $details = null)
    {
        $check = array('name' => $name, 'passed' => (bool) $passed);
        if (!$passed && $details !== null) {
            $check['details'] = $details;
        }
        $this->checks[] = $check;
    }
}
