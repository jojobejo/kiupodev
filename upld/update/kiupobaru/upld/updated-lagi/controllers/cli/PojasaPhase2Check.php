<?php
defined('BASEPATH') or exit('No direct script access allowed');

class PojasaPhase2Check extends CI_Controller
{
    private $checks = array();

    public function index()
    {
        if (!is_cli()) {
            show_404();
            return;
        }

        $this->load->helper(array('pojasa_authorization', 'pojasa_status'));
        $this->load->model('PO/M_PojasaCore');
        $this->load->model('PO/M_Pojasa');

        $this->assert('schema_ready', $this->M_PojasaCore->tables_ready(), $this->M_PojasaCore->missing_tables());
        if ($this->M_PojasaCore->tables_ready()) {
            $this->testStatusAndAuthorization();
            $this->testTransactionalFoundation();
        }

        $directorOpsCount = (int) $this->db->query(
            "SELECT COUNT(*) AS total FROM tbpo_user WHERE aksess_lv = 6 AND UPPER(TRIM(departement)) IN ('DIREKTUR OPERASIONAL', 'DIREKTUR OPRASIONAL')"
        )->row()->total;
        $failed = array_filter($this->checks, function ($check) {
            return !$check['passed'];
        });

        echo json_encode(array(
            'success' => empty($failed),
            'checks' => $this->checks,
            'prerequisites' => array(
                'director_ops_level_6_user_present' => $directorOpsCount > 0,
                'note' => $directorOpsCount > 0
                    ? 'User Direktur Operasional tersedia.'
                    : 'Setup user level 6 Direktur Operasional diperlukan sebelum UAT alur IT/HRD/GA.',
            ),
        ), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL;

        if ($failed) {
            exit(1);
        }
    }

    private function testStatusAndAuthorization()
    {
        $this->assert('status_count', count(pojasa_statuses()) === 18, pojasa_statuses());
        $this->assert('department_typo_normalized', pojasa_normalize_department('Direktur Oprasional') === 'DIREKTUR OPERASIONAL');
        $this->assert('finance_level_2_excluded', pojasa_role_from_context(2, 'KEUANGAN') === 'NONE');
        $this->assert('purchasing_exact_policy', pojasa_role_from_context(2, 'PURCHASING') === 'PURCHASING');
        $this->assert('director_ops_level_6_policy', pojasa_role_from_context(6, 'DIREKTUR OPRASIONAL') === 'DIREKTUR_OPERASIONAL');
        $this->assert('director_ops_no_pending', pojasa_next_status('MENUNGGU_DIRUT_OPS', 'PENDING', 'IT') === false);
        $this->assert('director_no_pending', pojasa_next_status('MENUNGGU_DIREKTUR', 'PENDING', 'SALES') === false);
        $this->assert('director_acc_issues_spk', pojasa_next_status('MENUNGGU_DIREKTUR', 'ACC', 'SALES') === 'SPK_TERBIT');
        $this->assert('pic_routes_to_initial_purchasing', pojasa_next_status('DRAFT', 'SUBMIT', 'SALES') === 'MENUNGGU_PURCHASING_AWAL');
        $this->assert('initial_purchasing_routes_to_kadep', pojasa_next_status('MENUNGGU_PURCHASING_AWAL', 'SUBMIT', 'SALES') === 'MENUNGGU_KADEP');
        $this->assert('post_kadep_purchasing_routes_to_ops', pojasa_next_status('MENUNGGU_PURCHASING', 'SUBMIT', 'SALES', false) === 'MENUNGGU_DIRUT_OPS');
        $this->assert('post_ops_purchasing_routes_to_director', pojasa_next_status('MENUNGGU_PURCHASING_DIROPS', 'SUBMIT', 'SALES') === 'MENUNGGU_DIREKTUR');
        $this->assert('legacy_post_ops_status_routes_to_director', pojasa_next_status('MENUNGGU_PURCHASING', 'SUBMIT', 'IT', true) === 'MENUNGGU_DIREKTUR');

        $request = (object) array('kd_user' => 'PIC001', 'departemen' => 'IT');
        $picOwner = array('role' => 'PIC', 'kode_user' => 'PIC001', 'departemen' => 'IT');
        $otherPic = array('role' => 'PIC', 'kode_user' => 'PIC002', 'departemen' => 'IT');
        $purchasing = array('role' => 'PURCHASING', 'kode_user' => 'PUR001', 'departemen' => 'PURCHASING');
        $admin = array('role' => 'ADMIN', 'kode_user' => 'ADM001', 'departemen' => 'ADMIN');
        $this->assert('cost_pic_owner_allowed', pojasa_can_act_on_request($picOwner, $request, 'COST_INPUT'));
        $this->assert('cost_other_pic_denied', !pojasa_can_act_on_request($otherPic, $request, 'COST_INPUT'));
        $this->assert('cost_purchasing_input_denied', !pojasa_can_act_on_request($purchasing, $request, 'COST_INPUT'));
        $this->assert('cost_admin_allowed', pojasa_can_act_on_request($admin, $request, 'COST_INPUT'));
    }

    private function testTransactionalFoundation()
    {
        $this->db->trans_begin();

        $requestNumbers = array();
        $spkNumbers = array();
        for ($index = 0; $index < 20; $index++) {
            $requestNumbers[] = $this->M_PojasaCore->generate_request_number('2026-09-02');
            $spkNumbers[] = $this->M_PojasaCore->generate_spk_number('2026-09-02');
        }
        $this->assert('request_numbers_unique', count(array_unique($requestNumbers)) === 20, $requestNumbers);
        $this->assert('spk_numbers_unique', count(array_unique($spkNumbers)) === 20, $spkNumbers);
        $this->assert('request_number_legacy_format', (bool) preg_match('/^PJASA020926\d{4}$/', $requestNumbers[0]));
        $this->assert('spk_number_legacy_format', (bool) preg_match('/^SPKJ020926\d{4}$/', $spkNumbers[0]));
        $legacyRequestNumber = $this->M_Pojasa->generate_kd_po_jasa();
        $legacySpkNumber = $this->M_Pojasa->generate_no_spk();
        $this->assert('legacy_request_generator_uses_atomic_core', !in_array($legacyRequestNumber, $requestNumbers, true) && (bool) preg_match('/^PJASA\d{10}$/', $legacyRequestNumber));
        $this->assert('legacy_spk_generator_uses_atomic_core', !in_array($legacySpkNumber, $spkNumbers, true) && (bool) preg_match('/^SPKJ\d{10}$/', $legacySpkNumber));

        $user = $this->db->query('SELECT id_user FROM tbpo_user ORDER BY id_user ASC LIMIT 1')->row();
        $marker = 'PHASE2_TEST_' . str_replace('.', '', uniqid('', true));
        $notifications = $user ? array(array(
            'kd_po_jasa' => null,
            'event_type' => $marker,
            'recipient_user_id' => (int) $user->id_user,
            'title' => 'PO Jasa Phase 2 test',
            'message' => 'Transactional notification smoke test.',
            'target_url' => null,
        )) : array();
        $recorded = $this->M_PojasaCore->record_event(array(
            'kd_po_jasa' => null,
            'event_type' => $marker,
            'catatan' => 'Transactional audit log smoke test.',
        ), $notifications);
        $logCount = (int) $this->db->where('event_type', $marker)->count_all_results('tbpo_jasa_log_aktivitas');
        $notificationCount = (int) $this->db->where('event_type', $marker)->count_all_results('tbpo_jasa_notifikasi');
        $this->assert('audit_log_transactional_insert', $recorded && $logCount === 1);
        $this->assert('notification_transactional_insert', !$user || $notificationCount === 1);

        $director = $this->db->query(
            "SELECT id_user, kode_user, nama_user, departement FROM tbpo_user WHERE aksess_lv = 3 AND UPPER(TRIM(departement)) = 'DIREKTUR' ORDER BY id_user ASC LIMIT 1"
        )->row();
        if ($director) {
            $approvalRequest = $requestNumbers[1];
            $this->db->insert('tbpo_jasa_request', array(
                'kd_po_jasa' => $approvalRequest,
                'kd_vendor_jasa' => null,
                'kd_user' => 'PHASE2PIC',
                'nm_user' => 'Phase 2 Test PIC',
                'departemen' => 'SALES',
                'tgl_request' => '2026-09-02',
                'tgl_target' => '2026-09-30',
                'tujuan_pekerjaan' => 'Director approval transaction smoke test',
                'estimasi_total' => 0,
                'status' => 'MENUNGGU_DIREKTUR',
            ));
            $directorContext = array(
                'id_user' => (int) $director->id_user,
                'kode_user' => (string) $director->kode_user,
                'nama_user' => (string) $director->nama_user,
                'departemen' => pojasa_normalize_department($director->departement),
                'role' => 'DIREKTUR',
            );
            $approvalToken = '3d85e9a7-7891-4869-9420-1932ec60f000';
            $approval = $this->M_PojasaCore->process_approval(
                $directorContext,
                $approvalRequest,
                'ACC',
                'Director approval smoke test.',
                $approvalToken
            );
            $approvedRequest = $this->M_PojasaCore->get_request($approvalRequest);
            $spkCount = (int) $this->db->where(array('kd_po_jasa' => $approvalRequest, 'no_spk' => $approval['no_spk']))->count_all_results('tbpo_jasa_spk');
            $approvalCount = (int) $this->db->where(array('kd_po_jasa' => $approvalRequest, 'action_token' => $approvalToken))->count_all_results('tbpo_jasa_approval');
            $this->assert(
                'director_acc_atomic_spk',
                $approval['success']
                    && $approval['status'] === 'SPK_TERBIT'
                    && $approvedRequest->status === 'SPK_TERBIT'
                    && $approvedRequest->no_spk === $approval['no_spk']
                    && $spkCount === 1
                    && $approvalCount === 1,
                $approval
            );
            $approvalReplay = $this->M_PojasaCore->process_approval(
                $directorContext,
                $approvalRequest,
                'ACC',
                'Replay must not issue another SPK.',
                $approvalToken
            );
            $spkAfterReplay = (int) $this->db->where('kd_po_jasa', $approvalRequest)->count_all_results('tbpo_jasa_spk');
            $this->assert('director_approval_idempotency', $approvalReplay['success'] && $approvalReplay['code'] === 'IDEMPOTENT_REPLAY' && $spkAfterReplay === 1, $approvalReplay);
        } else {
            $this->assert('director_fixture_available', false, 'User Direktur level 3 tidak tersedia.');
        }

        $stock = $this->db->query(
            "SELECT b.* FROM tbpo_barang_nk b JOIN tbpo_transaksi t ON t.kd_barang = b.kd_barang "
            . "AND t.kd_akun IN ('11511','11512','11513','11514') GROUP BY b.id_brg_nk "
            . "HAVING SUM(CASE WHEN t.kd_akun IN ('11511','11513') THEN t.tr_qty ELSE -t.tr_qty END) > 1 LIMIT 1"
        )->row();
        if ($user && $stock) {
            $testRequest = $requestNumbers[0];
            $this->db->insert('tbpo_jasa_request', array(
                'kd_po_jasa' => $testRequest,
                'kd_vendor_jasa' => null,
                'kd_user' => 'PHASE2PIC',
                'nm_user' => 'Phase 2 Test PIC',
                'departemen' => 'IT',
                'tgl_request' => '2026-09-02',
                'tgl_target' => '2026-09-30',
                'tujuan_pekerjaan' => 'Transactional Phase 2 smoke test',
                'estimasi_total' => 0,
                'status' => 'SPK_TERBIT',
                'requires_dirut_ops' => 1,
                'dirut_ops_approved_revision' => 1,
            ));
            $this->db->insert('tbpo_jasa_material', array(
                'kd_po_jasa' => $testRequest,
                'id_brg_nk' => (int) $stock->id_brg_nk,
                'nama_material' => 'Phase 2 stock receipt test',
                'qty_kebutuhan' => 0.01,
                'satuan' => (string) $stock->satuan,
                'sumber_material' => 'STOK',
                'created_by' => (int) $user->id_user,
            ));
            $materialId = (int) $this->db->insert_id();
            $this->db->insert('tbpo_jasa_stock_allocation', array(
                'kd_po_jasa' => $testRequest,
                'id_material' => $materialId,
                'id_brg_nk' => (int) $stock->id_brg_nk,
                'qty_allocation' => 0.01,
                'allocated_by' => (int) $user->id_user,
            ));
            $allocationId = (int) $this->db->insert_id();
            $adminContext = array(
                'id_user' => (int) $user->id_user,
                'kode_user' => 'KIUADMIN',
                'nama_user' => 'Admin',
                'departemen' => 'ADMIN',
                'role' => 'ADMIN',
            );
            $receiptToken = '5ab3dc1e-a228-47ea-95ae-c7d225585000';
            $receipt = $this->M_PojasaCore->confirm_stock_receipt(
                $adminContext,
                $testRequest,
                '2026-09-02 12:00:00',
                'Material diterima pada smoke test.',
                array(array('id_allocation' => $allocationId, 'qty_received' => 0.01)),
                $receiptToken
            );
            $stockOutCount = (int) $this->db->where(array('kd_po_nk' => $testRequest, 'kd_akun' => '11512'))->count_all_results('tbpo_transaksi');
            $stockInCount = (int) $this->db->where(array('kd_po_nk' => $testRequest, 'kd_akun' => '11511'))->count_all_results('tbpo_transaksi');
            $this->assert('receipt_posts_only_11512', $receipt['success'] && $stockOutCount === 1 && $stockInCount === 0, $receipt);
            $receiptReplay = $this->M_PojasaCore->confirm_stock_receipt(
                $adminContext,
                $testRequest,
                '2026-09-02 12:00:00',
                'Material diterima pada smoke test.',
                array(array('id_allocation' => $allocationId, 'qty_received' => 0.01)),
                $receiptToken
            );
            $stockOutAfterReplay = (int) $this->db->where(array('kd_po_nk' => $testRequest, 'kd_akun' => '11512'))->count_all_results('tbpo_transaksi');
            $this->assert('receipt_idempotency', $receiptReplay['success'] && $receiptReplay['code'] === 'IDEMPOTENT_REPLAY' && $stockOutAfterReplay === 1, $receiptReplay);

            $cost = $this->M_PojasaCore->save_actual_cost($adminContext, $testRequest, array(
                'tgl_biaya' => '2026-09-02',
                'cost_source' => 'PEMBELIAN_BARU',
                'id_draft_pembelian' => null,
                'jenis_biaya' => 'SMOKE TEST',
                'deskripsi' => 'Actual cost transaction smoke test.',
                'nominal' => 1000,
                'idempotency_token' => 'cbcae4bc-1d07-42d3-93c6-c196298bf000',
            ), array(
                'jenis_dokumen' => 'BUKTI_BIAYA_AKTUAL',
                'keterangan' => 'Smoke test evidence',
                'file_path' => 'images/pojasa/PHASE2-TEST/evidence.pdf',
                'file_original' => 'evidence.pdf',
                'mime_type' => 'application/pdf',
                'file_size_bytes' => 1,
                'sha256' => str_repeat('0', 64),
            ));
            $costEvidenceCount = $cost['success'] ? (int) $this->db->query(
                'SELECT COUNT(*) AS total FROM tbpo_jasa_biaya_aktual b JOIN tbpo_jasa_dokumen d ON d.id_dokumen = b.id_dokumen_bukti WHERE b.id_biaya_aktual = ?',
                array((int) $cost['id'])
            )->row()->total : 0;
            $this->assert('actual_cost_requires_evidence_relation', $cost['success'] && $costEvidenceCount === 1, $cost);
            $purchasingContext = array(
                'id_user' => (int) $user->id_user,
                'kode_user' => 'PURTEST',
                'nama_user' => 'Purchasing Test',
                'departemen' => 'PURCHASING',
                'role' => 'PURCHASING',
            );
            $deniedCost = $this->M_PojasaCore->save_actual_cost($purchasingContext, $testRequest, array(
                'idempotency_token' => 'c13cd86f-39e5-40d6-93b4-7beb66abb000',
            ), array());
            $this->assert('actual_cost_purchasing_model_guard', !$deniedCost['success'] && $deniedCost['code'] === 'FORBIDDEN', $deniedCost);
        } else {
            $this->assert('receipt_and_cost_fixture_available', false, 'User atau stok positif tidak tersedia.');
        }

        $this->db->trans_rollback();
        $rolledBackLogCount = (int) $this->db->where('event_type', $marker)->count_all_results('tbpo_jasa_log_aktivitas');
        $rolledBackNotificationCount = (int) $this->db->where('event_type', $marker)->count_all_results('tbpo_jasa_notifikasi');
        $this->assert('smoke_test_data_rolled_back', $rolledBackLogCount === 0 && $rolledBackNotificationCount === 0);
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
