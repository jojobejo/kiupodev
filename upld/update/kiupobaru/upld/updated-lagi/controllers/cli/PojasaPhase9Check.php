<?php
defined('BASEPATH') or exit('No direct script access allowed');

class PojasaPhase9Check extends CI_Controller
{
    private $checks = array();
    private $sequence = 1;
    private $persist = false;
    private $artifacts = array();

    public function index()
    {
        if (!is_cli()) { show_404(); return; }
        $this->persist = in_array('--persist', isset($_SERVER['argv']) ? $_SERVER['argv'] : array(), true);
        $this->load->helper(array('pojasa_authorization', 'pojasa_status'));
        $this->load->model('PO/M_PojasaCore');
        $this->load->model('PO/M_PojasaWorkflow');
        $this->load->model('PO/M_PojasaIntegration');
        $this->load->model('PO/M_Postatus');
        $ready = $this->M_PojasaWorkflow->schema_ready() && $this->M_PojasaIntegration->schema_ready();
        $this->assert('phase9_schema_ready', $ready);
        if ($ready) { $this->testSequentialFlow(); }
        $failed = array_filter($this->checks, function ($check) { return !$check['passed']; });
        echo json_encode(array('success' => !$failed, 'persistent' => $this->persist, 'artifacts' => $this->artifacts, 'checks' => $this->checks), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL;
        if ($failed) { exit(1); }
    }

    private function testSequentialFlow()
    {
        $users = $this->users();
        if (!$users) {
            $this->assert('phase9_users_available', false, 'PIC, KADEP satu departemen, Purchasing, Direktur Operasional, dan Direktur wajib tersedia.');
            return;
        }
        $before = $this->counts();
        if (!$this->persist) {
            $this->db->trans_begin();
        }
        $code = $this->persist ? 'PJTEST' . date('dmyHis') : 'P9TESTFLOW001';
        $this->createRequest($code, $users);

        $directorSkip = $this->approve($users['director'], $code, 'ACC');
        $this->assert('director_cannot_skip_sequence', !$directorSkip['success'] && $directorSkip['code'] === 'FORBIDDEN', $directorSkip);
        $steps = array(
            $this->approve($users['purchasing'], $code, 'UPDATE'),
            $this->approve($users['pic'], $code, 'ACC'),
            $this->approve($users['kadep'], $code, 'ACC'),
            $this->approve($users['dirops'], $code, 'ACC'),
            $this->approve($users['director'], $code, 'ACC'),
        );
        $expected = array('MENUNGGU_KONFIRMASI_PIC', 'MENUNGGU_KADEP', 'MENUNGGU_DIRUT_OPS',
            'MENUNGGU_DIREKTUR', 'SPK_TERBIT');
        $actual = array_map(function ($step) { return isset($step['status']) ? $step['status'] : null; }, $steps);
        $this->assert('mandatory_sequential_statuses', $actual === $expected, array('expected' => $expected, 'actual' => $actual));

        $request = $this->M_PojasaCore->get_request($code);
        $spk = $this->db->get_where('tbpo_jasa_spk', array('kd_po_jasa' => $code))->row();
        $submission = $this->db->get_where('tbpo_jasa_purchase_submission', array('kd_po_jasa' => $code))->row();
        $legacyPo = $submission ? $this->db->get_where('tbpo_po_nk', array('id_po_nk' => $submission->id_po_nk))->row() : null;
        $this->assert('director_issues_spk', $request && $request->status === 'SPK_TERBIT' && $spk && $request->no_spk === $spk->no_spk);
        $this->assert('director_automatically_creates_purchase_order', $submission && $legacyPo
            && $legacyPo->status === 'PROSES PEMBELIAN' && $legacyPo->source_module === 'PO_JASA');

        $history = $this->db->select('approval_stage')->order_by('id_approval', 'ASC')
            ->get_where('tbpo_jasa_approval', array('kd_po_jasa' => $code))->result_array();
        $stages = array_map(function ($row) { return $row['approval_stage']; }, $history);
        $this->assert('approval_history_matches_business_roles', $stages === array(
            'PURCHASING', 'PIC', 'KADEP', 'DIREKTUR_OPERASIONAL', 'DIREKTUR',
        ), $stages);
        $picNotif = (int) $this->db->where(array('kd_po_jasa' => $code,
            'recipient_user_id' => $users['pic']['id_user'], 'event_type' => 'MENUNGGU_KONFIRMASI_PIC'))
            ->count_all_results('tbpo_jasa_notifikasi');
        $this->assert('initial_purchasing_notifies_pic', $picNotif > 0);

        if ($this->persist) {
            $this->testManualMasterAndStoreArtifacts($code, $legacyPo ? $legacyPo->kd_po_nk : '', $users['purchasing']);
            return;
        }

        $this->db->trans_rollback();
        $this->assert('phase9_transaction_rolled_back_cleanly', $before === $this->counts()
            && (int) $this->db->where('kd_po_jasa', $code)->count_all_results('tbpo_jasa_request') === 0);
    }

    private function testManualMasterAndStoreArtifacts($code, $kdPoNk, $purchasing)
    {
        $before = $this->M_Postatus->get_manual_master_required_for_ponk($kdPoNk);
        $this->assert('manual_master_blocks_receipt_before_recording', count($before) === 1, array('unmapped_items' => count($before)));
        if (count($before) !== 1) {
            return;
        }

        $category = $this->db->order_by('kd_kat', 'ASC')->get('tbpo_kat_br')->row();
        $unit = $this->db->where('UPPER(nm_satuan)', 'PCS')->get('tbpo_satuan')->row();
        if (!$unit) {
            $unit = $this->db->order_by('id_satuan', 'ASC')->get('tbpo_satuan')->row();
        }
        $this->assert('master_barang_reference_data_available', (bool) $category && (bool) $unit);
        if (!$category || !$unit) {
            return;
        }

        $masterCode = 'TST' . date('dmyHis');
        $saved = $this->M_Postatus->save_manual_master_barang_pojasa($kdPoNk, (int) $before[0]->source_material_id, array(
            'kd_br_adm' => $masterCode,
            'kat_barang' => $category->kd_kat,
            'nama_barang' => 'DATA UJI E2E - Material Manual PO Jasa',
            'descnk' => 'Master otomatis dibuat saat uji alur E2E ' . $code,
            'satuan' => (int) $unit->id_satuan,
            'minimum_stock' => 0,
        ), $purchasing['kode_user']);
        $this->assert('manual_master_saved_and_linked_to_purchase_item', $saved['status'], $saved);

        $after = $this->M_Postatus->get_manual_master_required_for_ponk($kdPoNk);
        $this->assert('receipt_allowed_after_manual_master_recorded', count($after) === 0, array('unmapped_items' => count($after)));
        $master = $this->db->get_where('tbpo_barang_nk', array('kd_br_adm' => $masterCode))->row();
        $purchase = $this->db->get_where('tbpo_po_nk', array('kd_po_nk' => $kdPoNk))->row();
        $this->artifacts = array(
            'kd_po_jasa' => $code,
            'kd_po_nk' => $kdPoNk,
            'status_po_jasa' => $purchase ? $this->M_PojasaCore->get_request($code)->status : null,
            'status_po_pembelian' => $purchase ? $purchase->status : null,
            'master_barang_manual' => $master ? array('id_brg_nk' => (int) $master->id_brg_nk, 'kd_barang' => $master->kd_barang, 'kd_br_adm' => $master->kd_br_adm) : null,
            'jumlah_detail_po_pembelian' => (int) $this->db->where('kd_po_nk', $kdPoNk)->count_all_results('tbpo_detail_po_nk'),
        );
    }

    private function createRequest($code, $users)
    {
        $this->db->insert('tbpo_jasa_request', array(
            'kd_po_jasa' => $code, 'vendor_usulan' => 'Vendor Phase 9',
            'kd_user' => $users['pic']['kode_user'], 'nm_user' => $users['pic']['nama_user'],
            'departemen' => $users['pic']['departemen'], 'tgl_request' => '2026-09-11', 'tgl_target' => '2026-09-30',
            'tgl_mulai_pekerjaan' => '2026-09-12', 'tgl_selesai_pekerjaan' => '2026-09-30',
            'lokasi_pekerjaan' => 'Kantor', 'tujuan_pekerjaan' => 'Uji alur sequential Phase 9',
            'estimasi_total_jasa' => 100000, 'estimasi_total_bahan' => 50000, 'estimasi_total' => 150000,
            'status' => 'MENUNGGU_PURCHASING_AWAL', 'status_version' => 1, 'revision_no' => 1, 'requires_dirut_ops' => 1,
            'reviewed_by_purchasing' => $users['purchasing']['kode_user'], 'reviewed_at_purchasing' => '2026-09-11 08:00:00',
        ));
        $this->db->insert('tbpo_jasa_scope', array(
            'kd_po_jasa' => $code, 'revision_no' => 1, 'line_no' => 1, 'jenis_scope' => 'JASA',
            'nama_scope' => 'Pemasangan', 'deskripsi' => 'Uji Phase 9', 'qty' => 1, 'satuan' => 'LOT',
            'harga_estimasi' => 100000, 'total_estimasi' => 100000, 'is_active' => 1,
        ));
        $this->db->insert('tbpo_jasa_material', array(
            'kd_po_jasa' => $code, 'revision_no' => 1, 'line_no' => 1, 'nama_material' => 'Material Phase 9',
            'deskripsi' => 'Material uji PO otomatis', 'qty_kebutuhan' => 2, 'satuan' => 'PCS',
            'harga_estimasi' => 25000, 'total_estimasi' => 50000, 'sumber_material' => 'PEMBELIAN',
            'created_by' => $users['pic']['id_user'], 'is_active' => 1,
        ));
        $materialId = (int) $this->db->insert_id();
        $this->db->insert('tbpo_jasa_draft_pembelian', array(
            'kd_po_jasa' => $code, 'revision_no' => 1, 'id_material' => $materialId,
            'qty' => 2, 'harga_estimasi' => 25000, 'keterangan' => 'Draft Phase 9', 'status_draft' => 'DRAFT',
            'version' => 1, 'idempotency_token' => $this->token(), 'created_by' => $users['purchasing']['id_user'],
        ));
    }

    private function approve($context, $code, $action)
    {
        $request = $this->M_PojasaCore->get_request($code);
        $note = in_array($action, array('UPDATE', 'REVISI', 'REJECT'), true) || ($context['role'] === 'PIC' && $action === 'ACC')
            ? 'Uji alur Phase 9' : '';
        return $this->M_PojasaCore->process_approval($context, $code, $action, $note, $this->token(),
            $request ? $request->status : null, $request ? (int) $request->status_version : null);
    }

    private function users()
    {
        $pair = $this->db->query("SELECT p.id_user pic_id,k.id_user kadep_id FROM tbpo_user p JOIN tbpo_user k ON UPPER(TRIM(k.departement))=UPPER(TRIM(p.departement)) AND k.aksess_lv=5 WHERE p.aksess_lv=4 ORDER BY p.id_user LIMIT 1")->row();
        $purchasing = $this->db->query("SELECT * FROM tbpo_user WHERE aksess_lv=2 AND UPPER(TRIM(departement))='PURCHASING' LIMIT 1")->row_array();
        $dirops = $this->db->query("SELECT * FROM tbpo_user WHERE aksess_lv=6 AND UPPER(TRIM(departement)) IN ('DIREKTUR OPERASIONAL','DIREKTUR OPRASIONAL') LIMIT 1")->row_array();
        $director = $this->db->query("SELECT * FROM tbpo_user WHERE aksess_lv=3 AND UPPER(TRIM(departement))='DIREKTUR' LIMIT 1")->row_array();
        if (!$pair || !$purchasing || !$dirops || !$director) { return false; }
        $pic = $this->db->get_where('tbpo_user', array('id_user' => $pair->pic_id))->row_array();
        $kadep = $this->db->get_where('tbpo_user', array('id_user' => $pair->kadep_id))->row_array();
        return array('pic' => $this->context($pic), 'kadep' => $this->context($kadep),
            'purchasing' => $this->context($purchasing), 'dirops' => $this->context($dirops),
            'director' => $this->context($director));
    }

    private function context($user)
    {
        return array('id_user' => (int) $user['id_user'], 'kode_user' => $user['kode_user'],
            'nama_user' => $user['nama_user'], 'departemen' => pojasa_normalize_department($user['departement']),
            'role' => pojasa_role_from_context($user['aksess_lv'], $user['departement']));
    }

    private function token() { return sprintf('90000000-0000-4000-8000-%012d', $this->sequence++); }

    private function counts()
    {
        return array('request' => (int) $this->db->count_all('tbpo_jasa_request'),
            'approval' => (int) $this->db->count_all('tbpo_jasa_approval'),
            'spk' => (int) $this->db->count_all('tbpo_jasa_spk'),
            'submission' => (int) $this->db->count_all('tbpo_jasa_purchase_submission'),
            'po' => (int) $this->db->count_all('tbpo_po_nk'));
    }

    private function assert($name, $passed, $details = null)
    {
        $check = array('name' => $name, 'passed' => (bool) $passed);
        if (!$passed && $details !== null) { $check['details'] = $details; }
        $this->checks[] = $check;
    }
}
