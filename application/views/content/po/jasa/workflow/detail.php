<?php
$h = static function ($value) { return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8'); };
$money = static function ($value) { return 'Rp ' . number_format((float) $value, 0, ',', '.'); };
$vendorName = $request->nama_vendor ?: $request->vendor_usulan;
$finalApprovalView = !empty($final_approval_view);
$finalScopeTotal = isset($purchasing_totals['jasa']) ? (float) $purchasing_totals['jasa'] : (float) $request->estimasi_total_jasa;
$finalMaterialTotal = isset($purchasing_totals['material']) ? (float) $purchasing_totals['material'] : (float) $request->estimasi_total_bahan;
$finalTotal = isset($purchasing_totals['grand_total']) ? (float) $purchasing_totals['grand_total'] : (float) $request->estimasi_total;
$showDevPurchaseButton = (bool) $this->config->item('pojasa_enable_dev_purchase_button')
  && !$finalApprovalView
  && in_array($workflow_role, array('ADMIN', 'PURCHASING'), true);
$canIssueSpkPurchase = in_array($workflow_role, array('ADMIN', 'PURCHASING'), true)
  && $request->status === 'MENUNGGU_PENERBITAN_PURCHASING';
$purchaseSubmission = !empty($purchase_integration['submission']) ? $purchase_integration['submission'] : null;
$purchaseDetailUrl = in_array($workflow_role, array('PURCHASING', 'ADMIN'), true) && !empty($purchaseSubmission['kd_po_nk'])
  ? base_url('detailponk/' . rawurlencode($purchaseSubmission['kd_po_nk']))
  : null;
$revisionSources = array(
  'REVISI_PURCHASING_KADEP' => 'KADEP',
  'REVISI_PURCHASING_DIROPS' => 'DIREKTUR_OPERASIONAL',
  'REVISI_PURCHASING_DIRUT' => 'DIREKTUR',
);
$isPurchasingRevision = isset($revisionSources[$request->status]);
$revisionSource = $isPurchasingRevision ? $revisionSources[$request->status] : null;
$purchasingReviewSaved = $isPurchasingRevision
  ? (!empty($request->purchasing_revision_saved_at) && $request->purchasing_revision_status === $request->status)
  : !empty($request->reviewed_at_purchasing);
$purchasingReviewBadgeClass = $purchasingReviewSaved ? 'badge-success' : 'badge-warning';
$purchasingReviewBadgeText = $purchasingReviewSaved ? 'Sudah direview & tersimpan' : 'Belum direview & belum tersimpan';
$revisionNote = '';
if ($isPurchasingRevision && !empty($history['revisions'])) {
  foreach ($history['revisions'] as $revision) {
    if (isset($revision['revision_source']) && $revision['revision_source'] === $revisionSource) {
      $revisionNote = (string) $revision['alasan_revisi'];
      break;
    }
  }
}
$revisionSourceLabel = array(
  'KADEP' => 'Kadep',
  'DIREKTUR_OPERASIONAL' => 'Direktur Operasional',
  'DIREKTUR' => 'Direktur',
);
$purchasingRevisionNotes = array();
foreach ($history['logs'] as $log) {
  if ($log['event_type'] === 'REVISI_PURCHASING_DISIMPAN' || $log['event_type'] === 'REVISI_PURCHASING_PURCHASING_DISIMPAN') {
    $purchasingRevisionNotes[] = $log;
  }
}
?>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css">
<style>
  /* Workspace detail khusus Purchasing; tidak mengikuti tema tabel/card Bootstrap aplikasi. */
  #workflowScopeTab.purchasing-detail-page { padding: 18px 14px 4px; background: #fff; }
  #workflowScopeTab .purchasing-note-panel { margin: 0 0 22px; padding: 0 0 18px; border: 0; border-bottom: 1px solid #dbe4ef; border-radius: 0; background: transparent; box-shadow: none; }
  #workflowScopeTab .purchasing-section { margin: 0 0 28px; padding: 0; border: 0; border-radius: 0; background: transparent; box-shadow: none; }
  #workflowScopeTab .purchasing-section + .purchasing-section { padding-top: 22px; border-top: 1px solid #dbe4ef; }
  #workflowScopeTab .purchasing-section-head { display: flex; align-items: center; justify-content: space-between; gap: 16px; margin-bottom: 14px; }
  #workflowScopeTab .purchasing-section-title { margin: 0; color: #172b4d; font-size: 22px; font-weight: 750; letter-spacing: -.2px; }
  #workflowScopeTab .purchasing-section-tools { display: flex; flex-wrap: wrap; justify-content: flex-end; gap: 7px; }
  #workflowScopeTab .purchasing-section-tools .btn { margin: 0 !important; border-radius: 8px; font-size: 13px; font-weight: 700; box-shadow: none; }
  #workflowScopeTab .purchasing-section-tools .workflow-toggle-section { border-color: #e3a400; color: #9b6500; background: #fff; }
  #workflowScopeTab .purchasing-section-tools .workflow-toggle-section.btn-secondary { border-color: #6f7d8b; background: #6f7d8b; color: #fff; }
  #workflowScopeTab .purchasing-section-tools .workflow-add-row { border-color: #1266d8; background: #1266d8; }
  #workflowScopeTab .purchasing-section-tools .workflow-save-section { border-color: #138a5a; background: #138a5a; }
  #workflowScopeTab .purchasing-review-toolbar { display: flex; align-items: center; justify-content: flex-end; flex-wrap: wrap; gap: 8px; margin: -4px 0 12px; }
  #workflowScopeTab .purchasing-input-panel { margin: 0 0 28px; padding: 0 0 22px; border-bottom: 1px solid #dbe4ef; }
  #workflowScopeTab .purchasing-input-panel .purchasing-input-tools { display: flex; align-items: center; justify-content: flex-end; flex-wrap: wrap; gap: 8px; margin-bottom: 16px; }
  #workflowScopeTab .purchasing-input-panel .purchasing-input-tools .btn { border-radius: 8px; font-size: 13px; font-weight: 700; }
  #workflowScopeTab .purchasing-section-head + .purchasing-note-panel { display: none; }
  #workflowScopeTab .workflow-review-status { padding: 7px 10px; border-radius: 7px; font-size: 12px; font-weight: 800; }
  #workflowScopeTab .workflow-save-review { border-radius: 8px; font-size: 13px; font-weight: 700; }
  #workflowScopeTab .purchasing-custom-table { width: 100%; margin: 0; border: 0; border-collapse: separate; border-spacing: 0 8px; background: transparent; font-size: 16px; }
  #workflowScopeTab .purchasing-custom-table thead th { padding: 0 11px 8px; border: 0; background: transparent; color: #52647e; font-size: 13px; font-weight: 800; letter-spacing: .045em; text-transform: uppercase; vertical-align: bottom; }
  #workflowScopeTab .purchasing-custom-table tbody td { padding: 14px 11px; border-top: 1px solid #e5ebf3; border-bottom: 1px solid #e5ebf3; background: #fff; vertical-align: top; }
  #workflowScopeTab .purchasing-custom-table tbody td:first-child { border-left: 1px solid #e5ebf3; border-radius: 10px 0 0 10px; text-align: center; color: #38506d; font-weight: 800; }
  #workflowScopeTab .purchasing-custom-table tbody td:last-child { border-right: 1px solid #e5ebf3; border-radius: 0 10px 10px 0; }
  #workflowScopeTab .purchasing-custom-table tbody tr:hover td { border-color: #cbdcf1; background: #fbfdff; }
  #workflowScopeTab .workflow-cell-label { margin-bottom: 4px; color: #8290a4; font-size: 12px; font-weight: 800; letter-spacing: .04em; }
  #workflowScopeTab .workflow-summary-value { color: #1f3d63; font-size: 16px; }
  #workflowScopeTab .form-control { border: 1px solid #d8e1ed; border-radius: 7px; background: #f7f9fc; color: #243b58; box-shadow: none; font-size: 15px; }
  #workflowScopeTab .form-control[readonly] { border-color: transparent; background: #f2f5f8; color: #526273; }
  #workflowScopeTab textarea.form-control { resize: vertical; min-height: 50px; }
  #workflowScopeTab .workflow-edit-active { border-color: #e6a400 !important; background: #fff7df !important; color: #3c4858 !important; box-shadow: 0 0 0 3px rgba(230, 164, 0, .12) !important; }
  #workflowScopeTab .workflow-edit-active::placeholder { color: #9b7b38; }
  #workflowScopeTab .workflow-source-status, #workflowScopeTab .js-source { display: inline-block; max-width: 150px; padding: 6px 8px; border-radius: 6px; background: #e8f0fb; color: #315b91; font-size: 10px; font-weight: 800; line-height: 1.2; }
  #workflowScopeTab .workflow-purchasing-estimate { color: #128157; }
  #workflowScopeTab .workflow-table-actions { width: 72px; text-align: center; }
  #workflowScopeTab .workflow-table-actions .btn { display: inline-flex; align-items: center; justify-content: center; width: 29px; height: 29px; margin: 0 0 5px; border-radius: 7px; }
  #workflowScopeTab .workflow-empty td { border-radius: 10px !important; color: #8795a8; text-align: center !important; }
  @media (max-width: 900px) { #workflowScopeTab.purchasing-detail-page { padding-left: 8px; padding-right: 8px; } #workflowScopeTab .purchasing-section-head { align-items: flex-start; flex-direction: column; } #workflowScopeTab .purchasing-section-tools, #workflowScopeTab .purchasing-review-toolbar { justify-content: flex-start; } }
  #workflowMaterialTable .workflow-material-description,
  #workflowMaterialTable .workflow-source-status,
  #workflowMaterialTable .workflow-qty-need,
  #workflowMaterialTable .workflow-wrap-header,
  #workflowScopeTable .workflow-wrap-header {
    white-space: normal;
    overflow-wrap: anywhere;
    word-break: break-word;
  }
  #workflowMaterialTable .workflow-source-status .badge { white-space: normal; text-align: left; }
  #workflowScopeTable, #workflowMaterialTable { font-size: 16px; }
  #workflowMaterialTable .workflow-availability-column { min-width: 250px; }
  #workflowMaterialTable .workflow-availability-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 0; }
  #workflowMaterialTable .workflow-availability-grid .form-control { min-width: 0; border-radius: 0; }
  #workflowMaterialTable .workflow-availability-grid .form-control:first-child { border-radius: 7px 0 0 7px; }
  #workflowMaterialTable .workflow-availability-grid .form-control:last-child { border-left: 0; border-radius: 0 7px 7px 0; }
  .workflow-stock-select-wrap { min-width: 260px; }
  .select2-container { width: 100% !important; }
  .select2-container .select2-selection--single {
    height: 31px !important;
    border: 1px solid #d8e1ed !important;
    border-radius: 7px !important;
    background-color: #f7f9fc !important;
    padding: 2px 8px !important;
  }
  .select2-container--default .select2-selection--single .select2-selection__rendered {
    line-height: 25px !important;
    color: #243b58 !important;
    font-size: 13px !important;
    padding-left: 0 !important;
  }
  .select2-container--default .select2-selection--single .select2-selection__arrow {
    height: 29px !important;
  }
  .select2-container--default .select2-selection--single .select2-selection__clear {
    margin-right: 18px !important;
    color: #8898aa !important;
    font-size: 16px !important;
  }
  .select2-dropdown {
    border: 1px solid #cbdcf1 !important;
    border-radius: 7px !important;
    box-shadow: 0 6px 16px rgba(0,0,0,.1) !important;
    font-size: 13px !important;
    z-index: 9999 !important;
  }
  .select2-container--default .select2-results__option--highlighted[aria-selected] {
    background-color: #1266d8 !important;
  }
  .workflow-cell-label { display:block; color:#6c757d; font-size:12px; font-weight:600; text-transform:uppercase; margin-bottom:2px; }
  .workflow-summary-value { font-weight:600; white-space:nowrap; font-size:16px; }
  .workflow-table-detail { min-width:230px; }
  .workflow-table-purchasing { min-width:250px; }
  .workflow-table-actions { min-width:62px; white-space:nowrap; }
  /* Tampilan khusus approver: mandiri dari gaya card, grid, dan tabel Bootstrap aplikasi. */
  .workflow-approval-view { min-height: 100vh; background: #f4f7fb; color: #1a2a43; }
  .workflow-approval-view .content-header { padding: 28px 32px 24px; border-top: 1px solid #dfe3e8; border-bottom: 1px solid #dfe3e8; background: #f5f6f8; color: #20262e; }
  .workflow-approval-view .content-header .container-fluid,
  .workflow-approval-view .content > .container-fluid { max-width: 1440px; margin: 0 auto; }
  .workflow-approval-view .content-header .row,
  .workflow-approval-view .content > .container-fluid > .row { display: flex; flex-wrap: wrap; margin: 0; }
  .workflow-approval-view .content-header .col-sm-7,
  .workflow-approval-view .content-header .col-sm-5 { max-width: none; flex: 1 1 auto; padding: 0; }
  .workflow-approval-view .content-header h1 { margin: 0 0 7px !important; color: #20262e; font-size: 30px; font-weight: 500; letter-spacing: -.02em; }
  .workflow-approval-view .content-header .text-muted { color: #6c7783 !important; font-size: 16px; font-weight: 500; }
  .workflow-approval-view .content-header .badge { display: inline-flex; padding: 5px 9px; margin-left: 8px; border-radius: 5px; background: #16a4bb; color: #fff; font-size: 11px; font-weight: 800; letter-spacing: .02em; }
  .workflow-approval-view .content-header .btn { padding: 9px 15px; border: 1px solid #d7dce2; border-radius: 6px; background: #fff; color: #3f4750; box-shadow: none; font-size: 16px; }
  .workflow-approval-view .content-header .btn-success { border-color: #78d6b2; background: #1f8a63; color: #fff; }
  .workflow-approval-view .content { padding: 26px 32px 48px; }
  .workflow-approval-view .content > .container-fluid > .row > .col-lg-8 { flex: 1 1 68%; max-width: 68%; padding: 0 12px 0 0; }
  .workflow-approval-view .content > .container-fluid > .row > .col-lg-4 { flex: 1 1 32%; max-width: 32%; padding: 0; }
  .workflow-approval-view .card,
  .workflow-approval-view .small-box { margin: 0 0 20px; border: 1px solid #dce5f0; border-radius: 12px; background: #fff; box-shadow: 0 8px 24px rgba(26,42,67,.06); }
  .workflow-approval-view .card-outline.card-primary { border-top: 4px solid #1f5f8b; }
  .workflow-approval-view .card-header { padding: 18px 22px; border: 0; border-bottom: 1px solid #e6edf5; background: transparent; }
  .workflow-approval-view .card-title { color: #183b5b; font-size: 15px; font-weight: 800; letter-spacing: .04em; text-transform: uppercase; }
  .workflow-approval-view .card-body { padding: 22px; }
  .workflow-approval-view dl { display: grid; grid-template-columns: 120px 1fr; gap: 12px 16px; }
  .workflow-approval-view dl dt,
  .workflow-approval-view dl dd { margin: 0; padding: 0; }
  .workflow-approval-view dl dt[class*="col-"],
  .workflow-approval-view dl dd[class*="col-"] { width: auto !important; max-width: none !important; min-width: 0; flex: none !important; }
  .workflow-approval-view dl dt { color: #6f8095; font-weight: 700; }
  .workflow-approval-view dl dd { color: #1a2a43; font-weight: 600; }
  .workflow-approval-view dl dt,
  .workflow-approval-view dl dd,
  .workflow-approval-view .card-body p,
  .workflow-approval-view #workflowScopeTab table th,
  .workflow-approval-view #workflowScopeTab table td { white-space: nowrap !important; overflow-wrap: normal !important; word-break: normal !important; }
  .workflow-approval-view .card-body { overflow-x: auto; }
  .workflow-approval-view .small-box { min-height: 170px; padding: 25px; overflow: hidden; background: linear-gradient(135deg, #1c5279, #2378a8) !important; color: #fff; }
  .workflow-approval-view .small-box .inner { position: relative; z-index: 1; }
  .workflow-approval-view .small-box h3 { margin: 0 0 8px; color: #fff; font-size: 31px; font-weight: 800; letter-spacing: -.03em; }
  .workflow-approval-view .small-box p { margin: 0; color: #d7efff; font-size: 13px; font-weight: 700; text-transform: uppercase; letter-spacing: .06em; }
  .workflow-approval-view .small-box .icon { right: 18px; top: 18px; color: rgba(255,255,255,.16); }
  .workflow-approval-view .approval-cost-breakdown { position: relative; z-index: 1; display: grid; gap: 7px; margin-top: 18px; padding-top: 14px; border-top: 1px solid rgba(255,255,255,.24); }
  .workflow-approval-view .approval-cost-breakdown div { display: flex; justify-content: space-between; gap: 14px; color: #d7efff; font-size: 12px; white-space: nowrap; }
  .workflow-approval-view .approval-cost-breakdown strong { color: #fff; font-size: 13px; font-variant-numeric: tabular-nums; }
  .workflow-approval-view > .content .card:has(.nav-pills) { border: 0; background: transparent; box-shadow: none; }
  .workflow-approval-view > .content .card:has(.nav-pills) > .card-header { padding: 0; border: 0; }
  .workflow-approval-view > .content .card:has(.nav-pills) > .card-body { padding: 0; }
  .workflow-approval-view .nav-pills { display: flex; gap: 8px; padding: 0; border-bottom: 1px solid #dce5f0; }
  .workflow-approval-view .nav-pills .nav-link { padding: 12px 15px; border-radius: 8px 8px 0 0; color: #58708b; font-size: 13px; font-weight: 700; }
  .workflow-approval-view .nav-pills .nav-link.active { background: #183b5b; color: #fff; }
  .workflow-approval-view .tab-content { margin-top: 18px; border: 1px solid #dce5f0; border-radius: 12px; background: #fff; box-shadow: 0 8px 24px rgba(26,42,67,.05); }
  .workflow-approval-view #workflowScopeTab.purchasing-detail-page { padding: 26px; background: transparent; }
  .workflow-approval-view #workflowScopeTab .purchasing-section { margin-bottom: 30px; padding: 0; border: 0; }
  .workflow-approval-view #workflowScopeTab .purchasing-section + .purchasing-section { padding-top: 26px; border-top: 1px solid #e4ebf3; }
  .workflow-approval-view #workflowScopeTab .purchasing-section-title { color: #173d5e; font-size: 18px; }
  .workflow-approval-view #workflowScopeTab .purchasing-section-head .btn { padding: 8px 12px; border: 1px solid #bfd0df; border-radius: 7px; background: #fff; color: #254e70; font-size: 12px; font-weight: 700; }
  .workflow-approval-view #workflowScopeTab .table-responsive { overflow: auto; border: 1px solid #dce5f0; border-radius: 9px; }
  .workflow-approval-view #workflowScopeTab table { width: 100%; margin: 0; border-collapse: separate; border-spacing: 0; background: #fff; }
  .workflow-approval-view #workflowScopeTab table th { padding: 13px 15px; border: 0; border-bottom: 1px solid #dce5f0; background: #edf4f9; color: #46627b; font-size: 11px; font-weight: 800; letter-spacing: .07em; text-transform: uppercase; }
  .workflow-approval-view #workflowScopeTab table td { padding: 15px; border: 0; border-bottom: 1px solid #edf1f5; color: #263d55; vertical-align: top; }
  .workflow-approval-view #workflowScopeTab table tbody tr:last-child td { border-bottom: 0; }
  .workflow-approval-view #workflowScopeTab table tbody tr:hover td { background: #f8fbfd; }
  .workflow-approval-view #workflowScopeTab table td strong { color: #183b5b; font-weight: 750; }
  .workflow-approval-view #workflowScopeTab table td.text-right { font-variant-numeric: tabular-nums; font-weight: 700; white-space: nowrap; }
  .workflow-approval-view #workflowDocumentTab,
  .workflow-approval-view #workflowHistoryTab,
  .workflow-approval-view #workflowRevisionTab { padding: 24px; }
  @media (max-width: 900px) { .workflow-approval-view .content-header, .workflow-approval-view .content { padding-left: 16px; padding-right: 16px; } .workflow-approval-view .content > .container-fluid > .row > .col-lg-8, .workflow-approval-view .content > .container-fluid > .row > .col-lg-4 { flex-basis: 100%; max-width: 100%; padding: 0; } .workflow-approval-view .content-header .text-right { margin-top: 15px; text-align: left !important; } .workflow-approval-view .nav-pills { overflow-x: auto; flex-wrap: nowrap; } .workflow-approval-view #workflowScopeTab.purchasing-detail-page { padding: 18px; } }
</style>
<div class="content-wrapper<?= $finalApprovalView ? ' workflow-approval-view' : '' ?>">
<div class="px-3 pt-3"><a class="btn btn-outline-primary btn-sm" href="<?= base_url('pojasa/settlement/'.rawurlencode($request->kd_po_jasa)) ?>">BAST dan Pembayaran</a></div>
  <div class="content-header"><div class="container-fluid"><div class="row mb-2"><div class="col-sm-7"><h1 class="m-0"><?= $finalApprovalView ? 'Detail Request PO Jasa' : 'Detail Workflow PO Jasa' ?></h1><span class="text-muted"><?= $h($request->kd_po_jasa) ?></span> <span id="workflowDetailStatus" class="badge badge-info"><?= $h($request->status) ?></span></div><div class="col-sm-5 text-right"><a href="<?= base_url('pojasa/workflow') ?>" class="btn btn-default"><i class="fas fa-arrow-left mr-1"></i> <?= $finalApprovalView ? 'Daftar' : 'Daftar kerja' ?></a><?php if (!$finalApprovalView && in_array($workflow_role, array('ADMIN', 'PURCHASING', 'DIREKTUR'), true)) : ?><a href="<?= base_url('pojasa/purchasing/detail/' . rawurlencode($request->kd_po_jasa)) ?>" class="btn btn-warning"><i class="fas fa-shopping-cart mr-1"></i> Purchasing</a><?php endif; ?><?php if ($showDevPurchaseButton) : ?><button type="button" id="workflowDevPurchaseSubmit" class="btn btn-outline-danger" data-code="<?= $h($request->kd_po_jasa) ?>" title="Tombol sementara untuk pengujian data PO Pembelian otomatis"><i class="fas fa-vial mr-1"></i> DEV</button><?php endif; ?><?php if (!$finalApprovalView && !empty($request->no_spk)) : ?><a href="<?= base_url('pojasa/spk/' . rawurlencode($request->kd_po_jasa)) ?>" class="btn btn-info"><i class="fas fa-file-contract mr-1"></i> SPK</a><?php endif; ?><button type="button" id="workflowDetailProcess" class="btn btn-success btn-workflow-process <?= $allowed_actions ? '' : 'd-none' ?>" data-code="<?= $h($request->kd_po_jasa) ?>"><i class="fas fa-clipboard-check mr-1"></i> Proses</button></div></div></div></div>
  <?php if ($canIssueSpkPurchase) : ?><div class="container-fluid mb-2 text-right"><button type="button" id="workflowIssueSpkPurchase" class="btn btn-primary btn-workflow-process" data-code="<?= $h($request->kd_po_jasa) ?>"><i class="fas fa-file-signature mr-1"></i> Terbitkan SPK &amp; PO Pembelian</button></div><?php endif; ?>
  <section class="content"><div class="container-fluid">
    <div class="row"><div class="col-lg-8"><div class="card card-outline card-primary"><div class="card-header"><h3 class="card-title">Informasi Request</h3></div><div class="card-body">
      <div class="row"><div class="col-md-6"><dl class="row mb-0"><dt class="col-5">PIC</dt><dd class="col-7"><?= $h($request->nm_user) ?></dd><dt class="col-5">Departemen</dt><dd class="col-7"><?= $h($request->departemen) ?></dd><dt class="col-5">Tanggal</dt><dd class="col-7"><?= $h($request->tgl_request) ?></dd></dl></div><div class="col-md-6"><dl class="row mb-0"><dt class="col-5">Vendor</dt><dd class="col-7"><span id="workflowDetailVendor"><?= $h($vendorName ?: '-') ?></span><?php if ($can_edit_purchasing) : ?><div class="mt-2"><select id="workflowVendorChoice" class="form-control form-control-sm workflow-purchasing-form-input" disabled><option value="">Pilih vendor aktif</option><?php foreach ($vendors as $vendor) : ?><option value="<?= $h($vendor['kd_vendor_jasa']) ?>" <?= (string) $request->kd_vendor_jasa === (string) $vendor['kd_vendor_jasa'] ? 'selected' : '' ?>><?= $h($vendor['nama_vendor']) ?><?= !empty($vendor['kategori_jasa']) ? ' · ' . $h($vendor['kategori_jasa']) : '' ?></option><?php endforeach; ?></select><div class="mt-2"><span class="badge <?= $purchasingReviewBadgeClass ?> workflow-review-status"><i class="fas <?= $purchasingReviewSaved ? 'fa-check-circle' : 'fa-exclamation-circle' ?> mr-1"></i><span class="workflow-review-status-text"><?= $purchasingReviewBadgeText ?></span></span><div class="mt-2"><button type="button" id="workflowVendorList" class="btn btn-outline-primary btn-sm mb-1"><i class="fas fa-list mr-1"></i>Lihat list vendor</button> <button type="button" class="btn btn-outline-warning btn-sm workflow-toggle-purchasing-form"><i class="fas fa-edit mr-1"></i>Edit</button> <button type="button" class="btn btn-success btn-sm workflow-save-section workflow-save-purchasing-form" disabled><i class="fas fa-save mr-1"></i>Simpan</button></div></div></div><?php endif; ?></dd><dt class="col-5">Jadwal</dt><dd class="col-7"><?= $h(($request->tgl_mulai_pekerjaan ?: '-') . ' s/d ' . ($request->tgl_selesai_pekerjaan ?: '-')) ?></dd><dt class="col-5">Revisi</dt><dd class="col-7" id="workflowDetailRevision"><?= (int) $request->revision_no ?></dd></dl></div></div><hr><strong>Tujuan pekerjaan</strong><p style="white-space:pre-wrap" class="mb-2"><?= $h($request->tujuan_pekerjaan) ?></p><strong>Lokasi</strong><p class="mb-0"><?= $h($request->lokasi_pekerjaan ?: '-') ?></p><?php if (in_array($workflow_role, array('PURCHASING', 'ADMIN'), true)) : ?><hr><div class="d-flex align-items-center justify-content-between flex-wrap"><div class="mb-2 mb-md-0"><strong>PO Pembelian</strong><div><?= $purchaseSubmission ? $h($purchaseSubmission['kd_po_nk'] . ' · ' . (!empty($purchaseSubmission['po_status']) ? $purchaseSubmission['po_status'] : $purchaseSubmission['status'])) : 'Belum ada PO pembelian yang diajukan.' ?></div></div><?php if ($purchaseDetailUrl) : ?><a href="<?= $purchaseDetailUrl ?>" class="btn btn-success"><i class="fas fa-shopping-cart mr-1"></i> Lihat PO Pembelian</a><?php endif; ?></div><?php endif; ?>
    </div></div><?php if ($workflow_role === 'PIC' && $request->status === 'MENUNGGU_KONFIRMASI_PIC') : ?><div class="card card-outline card-warning"><div class="card-header"><h3 class="card-title"><i class="fas fa-comment-dots mr-1"></i>Catatan Purchasing</h3></div><div class="card-body"><dl class="row mb-3"><dt class="col-sm-4">Diinput oleh</dt><dd class="col-sm-8"><?= $h($purchasingReviewNote ? ($purchasingReviewNote->actor_name ?: $purchasingReviewNote->actor_code) : ($request->reviewed_by_purchasing ?: '-')) ?></dd><dt class="col-sm-4">Waktu review</dt><dd class="col-sm-8"><?= $h($purchasingReviewNote ? $purchasingReviewNote->created_at : ($request->reviewed_at_purchasing ?: '-')) ?></dd></dl><strong>Catatan untuk PIC</strong><p class="mb-3" style="white-space:pre-wrap"><?= $h($purchasingReviewNote && trim((string) $purchasingReviewNote->catatan) !== '' ? $purchasingReviewNote->catatan : 'Tidak ada catatan tambahan dari Purchasing.') ?></p><?php if (trim((string) $request->purchasing_note) !== '') : ?><strong>Ringkasan perubahan item</strong><p class="mb-0" style="white-space:pre-wrap"><?= $h($request->purchasing_note) ?></p><?php endif; ?></div></div><?php endif; ?></div><div class="col-lg-4"><div class="small-box bg-info"><div class="inner"><h3 id="workflowDetailGrandTotal"><?= $money(($finalApprovalView || in_array($workflow_role, array('PURCHASING', 'ADMIN'), true)) ? $finalTotal : $request->estimasi_total) ?></h3><p><?= $finalApprovalView ? 'Total estimasi biaya' : (in_array($workflow_role, array('PURCHASING', 'ADMIN'), true) ? 'Total Harga Purchasing' : 'Total estimasi PIC') ?></p><?php if ($finalApprovalView) : ?><div class="approval-cost-breakdown"><div><span>Scope pekerjaan</span><strong><?= $money($finalScopeTotal) ?></strong></div><div><span>Material / alat dan bahan</span><strong><?= $money($finalMaterialTotal) ?></strong></div></div><?php endif; ?></div><div class="icon"><i class="fas fa-calculator"></i></div></div><?php if (!$finalApprovalView) : ?><?php if (!in_array($workflow_role, array('PURCHASING', 'ADMIN'), true)) : ?><div class="card"><div class="card-body p-2"><div class="d-flex justify-content-between"><span>Jasa</span><strong id="workflowDetailScopeTotal"><?= $money($request->estimasi_total_jasa) ?></strong></div><div class="d-flex justify-content-between"><span>Material</span><strong id="workflowDetailMaterialTotal"><?= $money($request->estimasi_total_bahan) ?></strong></div></div></div><?php endif; ?><div class="small-box bg-success mt-3"><div class="inner"><h3 id="workflowPurchasingGrandTotal"><?= $request->estimasi_purchasing === null ? '-' : $money($request->estimasi_purchasing) ?></h3><p>Rincian Harga Purchasing</p></div><div class="icon"><i class="fas fa-calculator"></i></div></div><div class="card"><div class="card-body p-2"><div class="d-flex justify-content-between"><span>Jasa</span><strong id="workflowPurchasingScopeTotal"><?= $money($purchasing_totals['jasa']) ?></strong></div><div class="d-flex justify-content-between"><span>Material</span><strong id="workflowPurchasingMaterialTotal"><?= $money($purchasing_totals['material']) ?></strong></div></div></div><?php endif; ?></div></div>

    <?php if (!empty($pickup_state['pickup'])) : ?><div class="alert alert-info d-flex justify-content-between align-items-center"><span>Permintaan pengambilan: <strong><?= $h($pickup_state['pickup']['status']) ?></strong></span><?php if ($workflow_role === 'KADEP' && $pickup_state['pickup']['status'] === 'MENUNGGU_ACC_KADEP') : ?><span><button class="btn btn-success btn-sm workflow-pickup-decision" data-action="ACC">ACC Pengambilan</button> <button class="btn btn-danger btn-sm workflow-pickup-decision" data-action="REJECT">Tolak</button></span><?php endif; ?></div><?php endif; ?>
    <input type="hidden" id="workflowDetailCode" value="<?= $h($request->kd_po_jasa) ?>"><input type="hidden" id="workflowDetailVersion" value="<?= (int) $request->status_version ?>"><input type="hidden" id="workflowDetailExpectedStatus" value="<?= $h($request->status) ?>">

    <div class="card"><div class="card-header p-2 d-flex align-items-center justify-content-between"><ul class="nav nav-pills"><li class="nav-item"><a class="nav-link active" data-toggle="tab" href="#workflowScopeTab">Scope & Material</a></li><li class="nav-item"><a class="nav-link" data-toggle="tab" href="#workflowDocumentTab">Dokumen</a></li><li class="nav-item"><a class="nav-link" data-toggle="tab" href="#workflowHistoryTab">Timeline Approval</a></li><li class="nav-item"><a class="nav-link" data-toggle="tab" href="#workflowRevisionTab">Revisi</a></li></ul></div><div class="card-body"><div class="tab-content">
      <div class="tab-pane active purchasing-detail-page" id="workflowScopeTab">
        <?php if ($finalApprovalView) : ?>
          <section class="purchasing-section"><div class="purchasing-section-head"><h5 class="purchasing-section-title">Dokumen dari PIC</h5></div><div class="row"><?php if (!$documents) : ?><div class="col-12 text-muted">Belum ada dokumen pendukung.</div><?php endif; ?><?php foreach ($documents as $document) : ?><div class="col-md-6 mb-2"><div class="border rounded p-2"><strong><?= $h($document['file_original']) ?></strong><div class="small text-muted"><?= $h($document['jenis_dokumen']) ?> · <?= $h($document['uploader_name'] ?: '-') ?> · <?= $h($document['created_at']) ?></div><a class="btn btn-sm btn-outline-primary mt-2" href="<?= base_url('pojasa/workflow/document/' . (int) $document['id_dokumen'] . '/download') ?>"><i class="fas fa-download mr-1"></i>Unduh dokumen</a></div></div><?php endforeach; ?></div></section>
          <div class="mb-3"><h5 class="mb-0">Data final untuk approval</h5></div>
          <section class="purchasing-section">
            <div class="purchasing-section-head"><h5 class="purchasing-section-title">Scope pekerjaan</h5><button type="button" class="btn btn-outline-secondary btn-sm" data-toggle="modal" data-target="#workflowOriginalRequestModal" title="Lihat request awal"><i class="fas fa-eye mr-1"></i> Lihat request awal</button></div>
            <div class="table-responsive"><table class="table table-sm table-bordered"><thead><tr><th>Detail Scope</th><th class="text-right">QTY</th><th class="text-right">Harga Satuan</th><th class="text-right">Total Harga</th><th>Keterangan</th></tr></thead><tbody>
              <?php if (!$scopes) : ?><tr><td colspan="5" class="text-center text-muted">Belum ada scope.</td></tr><?php endif; ?>
              <?php foreach ($scopes as $row) : ?><?php $unitPrice = $row['harga_pembanding_purchasing']; ?><tr><td><strong><?= $h($row['nama_scope']) ?></strong></td><td class="text-right"><?= $h($row['qty']) ?> <?= $h($row['satuan']) ?></td><td class="text-right"><?= $unitPrice === null ? '-' : $money($unitPrice) ?></td><td class="text-right"><?= $unitPrice === null ? '-' : $money((float) $row['qty'] * (float) $unitPrice) ?></td><td style="white-space:pre-wrap"><?= $h($row['deskripsi'] ?: '-') ?></td></tr><?php endforeach; ?>
            </tbody></table></div>
          </section>
          <section class="purchasing-section">
            <div class="purchasing-section-head"><h5 class="purchasing-section-title">Material / alat dan bahan</h5></div>
            <div class="table-responsive"><table class="table table-sm table-bordered"><thead><tr><th>Detail Scope</th><th class="text-right">QTY</th><th class="text-right">Harga Satuan</th><th class="text-right">Total Harga</th><th>Keterangan</th></tr></thead><tbody>
              <?php if (!$materials) : ?><tr><td colspan="5" class="text-center text-muted">Belum ada material.</td></tr><?php endif; ?>
              <?php foreach ($materials as $row) : ?><?php $unitPrice = $row['harga_pembanding_purchasing']; ?><tr><td><strong><?= $h($row['nama_material']) ?></strong></td><td class="text-right"><?= $h($row['qty_kebutuhan']) ?> <?= $h($row['satuan']) ?></td><td class="text-right"><?= $unitPrice === null ? '-' : $money($unitPrice) ?></td><td class="text-right"><?= $unitPrice === null ? '-' : $money((float) $row['qty_kebutuhan'] * (float) $unitPrice) ?></td><td style="white-space:pre-wrap"><?= $h($row['deskripsi'] ?: '-') ?></td></tr><?php endforeach; ?>
            </tbody></table></div>
          </section>
        <?php else : ?>
        <?php if ($can_edit_purchasing) : ?><section class="purchasing-input-panel"><div class="purchasing-section-head"><h5 class="purchasing-section-title">Jadwal & review Purchasing</h5></div><?php if ($isPurchasingRevision) : ?><label class="mb-0" for="workflowPurchasingNote">Catatan perbaikan <span class="text-danger">*</span></label><textarea id="workflowPurchasingNote" rows="3" class="form-control mt-2 workflow-purchasing-form-input" required placeholder="Tulis tindak lanjut perbaikan." readonly></textarea><?php endif; ?><div class="row mt-3"><div class="col-md-6"><label for="workflowStartDate">Tanggal mulai <span class="text-danger">*</span></label><input id="workflowStartDate" type="date" class="form-control workflow-schedule-input workflow-purchasing-form-input" value="<?= $h($request->tgl_mulai_pekerjaan) ?>" readonly required></div><div class="col-md-6"><label for="workflowEndDate">Tanggal selesai <span class="text-danger">*</span></label><input id="workflowEndDate" type="date" class="form-control workflow-schedule-input workflow-purchasing-form-input" value="<?= $h($request->tgl_selesai_pekerjaan) ?>" readonly required></div></div><small class="form-text text-muted">Tanggal mulai dan selesai wajib diisi sebelum OK Purchasing.</small></section><?php endif; ?>
        <section class="purchasing-section"><div class="purchasing-section-head"><h5 class="purchasing-section-title">Scope pekerjaan</h5><?php if ($can_edit_purchasing) : ?><div class="purchasing-section-tools"><button type="button" class="btn btn-outline-warning btn-sm workflow-toggle-section" data-section="scope" data-save="#workflowSaveScope"><i class="fas fa-edit mr-1"></i>Edit</button><button type="button" class="btn btn-primary btn-sm workflow-add-row" data-section="scope"><i class="fas fa-plus mr-1"></i>Tambah</button><button type="button" id="workflowSaveScope" class="btn btn-success btn-sm workflow-save-section d-none"><i class="fas fa-save mr-1"></i>Simpan</button></div><?php endif; ?></div>
        <?php if ($can_edit_purchasing) : ?><div class="purchasing-review-toolbar"><span class="badge <?= $purchasingReviewBadgeClass ?> workflow-review-status"><i class="fas <?= $purchasingReviewSaved ? 'fa-check-circle' : 'fa-exclamation-circle' ?> mr-1"></i><span class="workflow-review-status-text"><?= $purchasingReviewBadgeText ?></span></span><button type="button" class="btn btn-success btn-sm workflow-save-section workflow-save-review" data-review-section="scope" disabled><i class="fas fa-save mr-1"></i>Simpan review</button></div><?php endif; ?>
        <div class="table-responsive"><table id="workflowScopeTable" class="purchasing-custom-table"><thead><tr><th>Detail Scope</th><th>QTY</th><th>Harga Satuan</th><th>Total Harga</th><th>Keterangan</th><?php if ($can_edit_purchasing) : ?><th>Aksi</th><?php endif; ?></tr></thead><tbody><?php if (!$scopes) : ?><tr class="workflow-empty"><td colspan="<?= $can_edit_purchasing ? 6 : 5 ?>" class="text-center text-muted">Belum ada scope.</td></tr><?php endif; ?><?php foreach ($scopes as $row) : ?><?php $unitPrice = $row['harga_pembanding_purchasing']; ?><tr data-id="<?= (int) $row['id_scope'] ?>" data-new="0"><td><?php if ($can_edit_purchasing) : ?><input class="form-control form-control-sm workflow-name mb-1" maxlength="255" value="<?= $h($row['nama_scope']) ?>" readonly><?php else : ?><strong><?= $h($row['nama_scope']) ?></strong><?php endif; ?></td><td class="text-right"><?php if ($can_edit_purchasing) : ?><div class="d-flex"><input type="number" min="0.01" step="0.01" class="form-control form-control-sm workflow-qty mr-1" value="<?= $h($row['qty']) ?>" readonly><input class="form-control form-control-sm workflow-unit" maxlength="50" value="<?= $h($row['satuan']) ?>" readonly></div><?php else : ?><?= $h($row['qty']) ?> <?= $h($row['satuan']) ?><?php endif; ?></td><td><?php if ($can_edit_purchasing) : ?><input type="text" inputmode="numeric" class="form-control form-control-sm workflow-comparison-price" value="<?= $h($unitPrice) ?>" placeholder="Harga satuan" readonly><?php else : ?><div class="text-right"><?= $unitPrice === null ? '-' : $money($unitPrice) ?></div><?php endif; ?></td><td class="text-right"><span class="workflow-purchasing-estimate"><?= $unitPrice === null ? '-' : $money((float) $row['qty'] * (float) $unitPrice) ?></span></td><td><?php if ($can_edit_purchasing) : ?><textarea class="form-control form-control-sm workflow-description" rows="2" maxlength="5000" readonly><?= $h($row['deskripsi']) ?></textarea><?php else : ?><span style="white-space:pre-wrap"><?= $h($row['deskripsi'] ?: '-') ?></span><?php endif; ?></td><?php if ($can_edit_purchasing) : ?><td class="workflow-table-actions"><button type="button" class="btn btn-xs btn-outline-danger workflow-delete-row" title="Hapus"><i class="fas fa-trash"></i></button></td><?php endif; ?></tr><?php endforeach; ?></tbody></table></div></section>
        <section class="purchasing-section"><div class="purchasing-section-head"><h5 class="purchasing-section-title">Material / alat dan bahan</h5><?php if ($can_edit_purchasing) : ?><div class="purchasing-section-tools"><button type="button" id="workflowDraftPurchaseList" class="btn btn-outline-primary btn-sm text-nowrap"><i class="fas fa-shopping-cart mr-1"></i>Draft PO</button><button type="button" class="btn btn-outline-warning btn-sm workflow-toggle-section" data-section="material" data-save="#workflowSaveMaterial"><i class="fas fa-edit mr-1"></i>Edit</button><button type="button" class="btn btn-primary btn-sm workflow-add-row" data-section="material"><i class="fas fa-plus mr-1"></i>Tambah</button><button type="button" id="workflowSaveMaterial" class="btn btn-success btn-sm workflow-save-section d-none"><i class="fas fa-save mr-1"></i>Simpan</button></div><?php endif; ?></div>
        <?php if ($can_edit_purchasing) : ?><div class="purchasing-note-panel"><?php if ($isPurchasingRevision) : ?><div class="alert alert-warning mb-3"><strong><i class="fas fa-comment-dots mr-1"></i>Catatan revisi <?= $h(isset($revisionSourceLabel[$revisionSource]) ? $revisionSourceLabel[$revisionSource] : $revisionSource) ?></strong><p class="mb-0 mt-2" style="white-space:pre-wrap"><?= $h($revisionNote !== '' ? $revisionNote : 'Catatan revisi tidak tersedia.') ?></p></div><label class="mb-0" for="workflowPurchasingNote">Catatan perbaikan Purchasing <span class="text-danger">*</span></label><small class="form-text text-muted">Klik Edit untuk mengisi catatan perbaikan, lalu Simpan untuk memperbarui data revisi.</small><textarea id="workflowPurchasingNote" rows="3" class="form-control mt-2" required placeholder="Tulis catatan perbaikan Purchasing di sini." readonly></textarea><?php endif; ?><div class="row mt-3"><div class="col-md-6"><label for="workflowStartDate">Tanggal mulai <span class="text-danger">*</span></label><input id="workflowStartDate" type="date" class="form-control workflow-schedule-input" value="<?= $h($request->tgl_mulai_pekerjaan) ?>" readonly required></div><div class="col-md-6"><label for="workflowEndDate">Tanggal selesai <span class="text-danger">*</span></label><input id="workflowEndDate" type="date" class="form-control workflow-schedule-input" value="<?= $h($request->tgl_selesai_pekerjaan) ?>" readonly required></div></div><small class="form-text text-muted">Tanggal mulai dan selesai wajib diisi sebelum request dapat diajukan.</small></div><?php endif; ?>
        <?php if ($can_edit_purchasing) : ?><div class="purchasing-review-toolbar"><span class="badge <?= $purchasingReviewBadgeClass ?> workflow-review-status"><i class="fas <?= $purchasingReviewSaved ? 'fa-check-circle' : 'fa-exclamation-circle' ?> mr-1"></i><span class="workflow-review-status-text"><?= $purchasingReviewBadgeText ?></span></span><button type="button" class="btn btn-success btn-sm workflow-save-section workflow-save-review" data-review-section="material" disabled><i class="fas fa-save mr-1"></i>Simpan review</button></div><?php endif; ?>
        <div class="table-responsive"><table id="workflowMaterialTable" class="purchasing-custom-table"><thead><tr><th>Detail Scope</th><th>QTY</th><th>Harga Satuan</th><th>Total Harga</th><th>Keterangan</th><?php if ($can_edit_purchasing) : ?><th>Aksi</th><?php endif; ?></tr></thead><tbody><?php if (!$materials) : ?><tr class="workflow-empty"><td colspan="<?= $can_edit_purchasing ? 6 : 5 ?>" class="text-center text-muted">Belum ada material.</td></tr><?php endif; ?><?php foreach ($materials as $row) : ?><?php $unitPrice = $row['harga_pembanding_purchasing']; ?><tr data-id="<?= (int) $row['id_material'] ?>" data-material="<?= (int) $row['id_material'] ?>" data-new="0"><td><input type="hidden" class="workflow-id-brg-nk" value="<?= (int) (isset($row['id_brg_nk']) ? $row['id_brg_nk'] : 0) ?>"><input type="hidden" class="workflow-ref-type" value="<?= $h(isset($row['reference_type']) ? $row['reference_type'] : 'MANUAL') ?>"><?php if ($can_edit_purchasing) : ?><input class="form-control form-control-sm workflow-name mb-1" maxlength="255" value="<?= $h($row['nama_material']) ?>" readonly><?php else : ?><strong><?= $h($row['nama_material']) ?></strong><?php endif; ?></td><td><?php if ($can_edit_purchasing) : ?><div class="d-flex"><input type="number" min="0.01" step="0.01" class="form-control form-control-sm workflow-qty mr-1" value="<?= $h($row['qty_kebutuhan']) ?>" readonly><input class="form-control form-control-sm workflow-unit" maxlength="50" value="<?= $h($row['satuan']) ?>" readonly></div><small class="text-muted js-source"><?= $h($row['computed_source']) ?></small><input type="hidden" class="js-ready" value="<?= $h($row['qty_ready']) ?>"><small class="text-muted d-block">Stok tersedia: <span class="js-available"><?= $h($row['qty_ready']) ?></span></small><?php else : ?><?= $h($row['qty_kebutuhan']) ?> <?= $h($row['satuan']) ?><?php endif; ?></td><td><?php if ($can_edit_purchasing) : ?><input type="text" inputmode="numeric" class="form-control form-control-sm workflow-comparison-price" value="<?= $h($unitPrice) ?>" placeholder="Harga satuan" readonly><?php else : ?><div class="text-right"><?= $unitPrice === null ? '-' : $money($unitPrice) ?></div><?php endif; ?></td><td class="text-right"><span class="workflow-purchasing-estimate"><?= $unitPrice === null ? '-' : $money((float) $row['qty_kebutuhan'] * (float) $unitPrice) ?></span></td><td><?php if ($can_edit_purchasing) : ?><textarea class="form-control form-control-sm workflow-description" rows="2" maxlength="5000" readonly><?= $h($row['deskripsi']) ?></textarea><?php else : ?><span style="white-space:pre-wrap"><?= $h($row['deskripsi'] ?: '-') ?></span><?php endif; ?></td><?php if ($can_edit_purchasing) : ?><td class="workflow-table-actions"><button type="button" class="btn btn-xs btn-outline-primary workflow-add-draft" title="Tambah ke draft pembelian" <?= $row['remaining_need'] <= 0 ? 'disabled' : '' ?> data-qty="<?= $h($row['remaining_need']) ?>" data-price="<?= $h($unitPrice) ?>"><i class="fas fa-cart-plus"></i></button> <button type="button" class="btn btn-xs btn-outline-danger workflow-delete-row" title="Hapus"><i class="fas fa-trash"></i></button></td><?php endif; ?></tr><?php endforeach; ?></tbody></table></div></section><?php endif; ?></div>
      <div class="tab-pane" id="workflowDocumentTab"><div class="row">
        <?php if (!$documents) : ?><div class="col-12 text-muted text-center">Belum ada dokumen.</div><?php endif; ?>
        <?php foreach ($documents as $document) : ?>
          <?php $ext = strtolower(pathinfo($document['file_original'], PATHINFO_EXTENSION)); $isImage = in_array($ext, array('jpg','jpeg','png'), true); $isPdf = $ext === 'pdf'; ?>
          <div class="col-md-4 mb-3"><div class="border rounded p-2 h-100">
            <?php if ($isImage) : ?><button type="button" class="btn btn-link p-0 w-100 workflow-document-preview" data-type="image" data-url="<?= base_url('pojasa/workflow/document/' . (int) $document['id_dokumen'] . '/preview') ?>" data-title="<?= $h($document['file_original']) ?>"><img src="<?= base_url('pojasa/workflow/document/' . (int) $document['id_dokumen'] . '/preview') ?>" alt="Thumbnail dokumen" class="img-fluid rounded" style="height:150px;width:100%;object-fit:cover"></button><?php else : ?><div class="text-center text-secondary py-3"><i class="fas <?= $isPdf ? 'fa-file-pdf text-danger' : 'fa-file-alt' ?> fa-3x"></i></div><?php endif; ?>
            <strong class="d-block text-truncate"><?= $h($document['file_original']) ?></strong>
            <small class="text-muted d-block"><?= $h(strtoupper($ext)) ?> · <?= $h($document['jenis_dokumen']) ?> · Revisi <?= (int) $document['revision_no'] ?></small>
            <small class="text-muted d-block">Uploader: <?= $h($document['uploader_name'] ?: '-') ?> · <?= $h($document['created_at']) ?> · <?= number_format((int) $document['file_size_bytes'] / 1024, 1, ',', '.') ?> KB</small>
            <?php if ($isImage || $isPdf) : ?><button type="button" class="btn btn-xs btn-info mt-2 workflow-document-preview" data-type="<?= $isPdf ? 'pdf' : 'image' ?>" data-url="<?= base_url('pojasa/workflow/document/' . (int) $document['id_dokumen'] . '/preview') ?>" data-title="<?= $h($document['file_original']) ?>">Preview</button><?php endif; ?>
            <a class="btn btn-xs btn-secondary mt-2" href="<?= base_url('pojasa/workflow/document/' . (int) $document['id_dokumen'] . '/download') ?>">Download</a>
          </div></div>
        <?php endforeach; ?>
      </div></div>
      <div class="tab-pane" id="workflowHistoryTab"><div class="table-responsive"><table class="table table-sm table-bordered"><thead><tr><th>Waktu</th><th>Tahap</th><th>Aksi</th><th>Status</th><th>Aktor</th><th>Catatan</th></tr></thead><tbody id="workflowDetailApprovals"><?php if (!$history['approvals']) : ?><tr><td colspan="6" class="text-center text-muted">Belum ada approval.</td></tr><?php endif; ?><?php foreach ($history['approvals'] as $row) : ?><tr><td><?= $h($row['created_at']) ?></td><td><?= $h($row['approval_stage']) ?></td><td><?= $h($row['action']) ?></td><td><?= $h($row['from_status']) ?> → <?= $h($row['to_status']) ?></td><td><?= $h($row['actor_code']) ?> (<?= $h($row['actor_role']) ?>)</td><td><?= $h($row['catatan']) ?></td></tr><?php endforeach; ?></tbody></table></div></div>
      <div class="tab-pane" id="workflowRevisionTab"><h6 class="mb-2">Permintaan revisi</h6><div class="table-responsive"><table class="table table-sm table-bordered"><thead><tr><th>Revisi</th><th>Sumber</th><th>Diminta</th><th>Alasan</th><th>Diajukan ulang</th></tr></thead><tbody id="workflowDetailRevisions"><?php if (!$history['revisions']) : ?><tr><td colspan="5" class="text-center text-muted">Belum ada revisi.</td></tr><?php endif; ?><?php foreach ($history['revisions'] as $row) : ?><tr><td><?= (int) $row['revision_no'] ?></td><td><?= $h($row['revision_source']) ?></td><td><?= $h($row['requested_at']) ?></td><td><?= $h($row['alasan_revisi']) ?></td><td><?= $h($row['submitted_at'] ?: '-') ?></td></tr><?php endforeach; ?></tbody></table></div><h6 class="mt-4 mb-2">Catatan perbaikan Purchasing</h6><div class="table-responsive"><table class="table table-sm table-bordered"><thead><tr><th>Revisi</th><th>Waktu disimpan</th><th>Purchasing</th><th>Catatan perbaikan</th></tr></thead><tbody id="workflowDetailPurchasingRevisionNotes"><?php if (!$purchasingRevisionNotes) : ?><tr><td colspan="4" class="text-center text-muted">Belum ada catatan perbaikan dari Purchasing.</td></tr><?php endif; ?><?php foreach ($purchasingRevisionNotes as $row) : ?><tr><td><?= (int) $row['revision_no'] ?></td><td><?= $h($row['created_at']) ?></td><td><?= $h($row['actor_name'] ?: $row['actor_code']) ?></td><td style="white-space:pre-wrap"><?= $h($row['catatan']) ?></td></tr><?php endforeach; ?></tbody></table></div></div>
    </div></div></div>
  </div></section>
</div>
<?php $this->load->view('content/po/jasa/workflow/modal'); ?>
<?php if ($finalApprovalView) : ?>
<div class="modal fade" id="workflowOriginalRequestModal" tabindex="-1" role="dialog" aria-hidden="true"><div class="modal-dialog modal-lg" role="document"><div class="modal-content"><div class="modal-header"><h5 class="modal-title"><i class="fas fa-eye mr-1"></i>Request awal PIC</h5><button type="button" class="close" data-dismiss="modal"><span>&times;</span></button></div><div class="modal-body"><div class="row"><div class="col-md-6"><dl class="row mb-0"><dt class="col-sm-5">PIC</dt><dd class="col-sm-7"><?= $h($request->nm_user) ?></dd><dt class="col-sm-5">Departemen</dt><dd class="col-sm-7"><?= $h($request->departemen) ?></dd><dt class="col-sm-5">Tanggal request</dt><dd class="col-sm-7"><?= $h($request->tgl_request) ?></dd></dl></div></div><hr><strong>Tujuan pekerjaan</strong><p class="mb-3" style="white-space:pre-wrap"><?= $h($request->tujuan_pekerjaan ?: '-') ?></p><strong>Catatan PIC</strong><p class="mb-0" style="white-space:pre-wrap"><?= $h($request->catatan_pic ?: '-') ?></p></div></div></div></div>
<?php endif; ?>
<div class="modal fade" id="workflowDocumentModal" tabindex="-1" role="dialog" aria-hidden="true"><div class="modal-dialog modal-xl" role="document"><div class="modal-content"><div class="modal-header"><h5 class="modal-title" id="workflowDocumentTitle">Preview dokumen</h5><button type="button" class="close" data-dismiss="modal"><span>&times;</span></button></div><div class="modal-body text-center" id="workflowDocumentBody"></div></div></div></div>
<div class="modal fade" id="workflowPlanningModal" tabindex="-1" role="dialog" aria-hidden="true"><div class="modal-dialog modal-lg" role="document"><div class="modal-content"><div class="modal-header"><h5 class="modal-title">Detail pemenuhan material</h5><button type="button" class="close" data-dismiss="modal"><span>&times;</span></button></div><div class="modal-body"><div class="table-responsive"><table class="table table-sm table-bordered"><thead id="workflowPlanningHead"></thead><tbody id="workflowPlanningBody"></tbody></table></div></div></div></div></div>

<?php if (in_array($workflow_role, array('PURCHASING', 'ADMIN'), true)) : ?>
<style>
#workflowVendorModal .modal-content {border:0;border-radius:14px;overflow:hidden;box-shadow:0 18px 60px rgba(23,43,77,.2)}
#workflowVendorModal .modal-header {background:#f3f7fc;border-bottom:1px solid #e2eaf4;padding:20px 24px}
#workflowVendorModal .modal-title {font-weight:700;color:#193b60}
#workflowVendorModal .modal-body {padding:24px;background:#fff}
#workflowVendorModal .vendor-toolbar {display:flex;gap:12px;flex-wrap:wrap;align-items:center;justify-content:space-between;margin-bottom:18px}
#workflowVendorModal .vendor-search {position:relative;flex:1;max-width:420px;min-width:180px}
#workflowVendorModal .vendor-search i {position:absolute;left:13px;top:13px;color:#8093a8}
#workflowVendorModal .vendor-search input {padding-left:36px;border-radius:8px}
#workflowVendorModal .vendor-editor {border:1px solid #d6e4f3;border-radius:10px;padding:20px;background:#f8fbff;margin-bottom:22px}
#workflowVendorModal .vendor-editor-head {display:flex;justify-content:space-between;align-items:center;margin-bottom:18px}
#workflowVendorModal .vendor-editor h6 {font-weight:700;color:#193b60;margin:0}
#workflowVendorModal .vendor-editor label {font-size:13px;color:#506681}
#workflowVendorModal .vendor-editor .form-control {border-radius:7px}
#workflowVendorModal .vendor-editor textarea {resize:vertical}
#workflowVendorModal .vendor-editor .form-control[readonly] {background:#fff;border-color:#e0e8f1;color:#304c69}
#workflowVendorModal .vendor-table-wrap {border:1px solid #e2e9f2;border-radius:9px;padding:0 10px 12px}
#workflowVendorTable {border:0;margin-top:0!important}
#workflowVendorTable th {background:#f4f7fb;color:#526982;border-top:0;font-size:12px;padding:13px 10px;white-space:nowrap}
#workflowVendorTable td {vertical-align:middle;padding:12px 10px;border-color:#edf1f6;font-size:13px}
#workflowVendorTable .vendor-name {font-weight:700;color:#20476e}
#workflowVendorTable .vendor-description {display:block;color:#78899b;max-width:210px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;font-size:12px;margin-top:3px}
#workflowVendorTable tr.vendor-active td {background:#eaf4ff}
#workflowVendorTable .vendor-actions {white-space:nowrap}
#workflowVendorModal .vendor-notice {border-radius:8px;padding:11px 14px;margin-bottom:16px}
#workflowVendorModal .vendor-counter {font-size:12px;color:#687f97;background:#e5edf8;padding:4px 9px;border-radius:20px}
#workflowVendorModal .vendor-footer {display:flex;justify-content:flex-end;gap:8px;border-top:1px solid #e0e8f2;padding-top:14px}
@media(max-width:767px) {#workflowVendorModal .modal-body{padding:16px} #workflowVendorModal .vendor-search{max-width:none;flex-basis:100%} #workflowVendorModal .vendor-editor{padding:14px}}
</style>
<div class="modal fade" id="workflowVendorModal" tabindex="-1" role="dialog" aria-labelledby="workflowVendorTitle" aria-hidden="true"><div class="modal-dialog modal-xl modal-dialog-scrollable"><div class="modal-content">
<div class="modal-header"><div><h5 class="modal-title" id="workflowVendorTitle"><i class="fas fa-address-book mr-2"></i>Daftar vendor jasa <span id="workflowVendorCount" class="vendor-counter">0 vendor</span></h5><div class="small text-muted mt-1">Kelola informasi dan kontak vendor jasa.</div></div><button type="button" class="close" data-dismiss="modal" aria-label="Tutup"><span>&times;</span></button></div>
<div class="modal-body">
<div id="workflowVendorNotice" class="vendor-notice" role="status" aria-live="polite" style="display:none"></div>
<div class="vendor-toolbar"><div class="vendor-search"><i class="fas fa-search" aria-hidden="true"></i><input id="workflowVendorSearch" class="form-control" placeholder="Cari nama, kontak, atau deskripsi vendor" aria-label="Cari vendor"></div><div><button type="button" id="workflowVendorRefresh" class="btn btn-outline-secondary mr-1" title="Muat ulang vendor"><i class="fas fa-sync-alt"></i></button><button type="button" id="workflowVendorNew" class="btn btn-primary"><i class="fas fa-plus mr-1"></i>Tambah vendor</button></div></div>
<div id="workflowVendorEditor" class="vendor-editor" style="display:none"><div class="vendor-editor-head"><h6 id="workflowVendorFormTitle">Tambah vendor</h6><button type="button" class="btn btn-sm btn-link vendor-cancel" aria-label="Tutup form vendor"><i class="fas fa-times"></i></button></div>
<form id="workflowVendorForm"><input type="hidden" name="id_vendor_jasa"><input type="hidden" name="version"><div class="row">
<?php foreach (array('nama_vendor'=>'Nama vendor', 'alamat_vendor'=>'Alamat vendor', 'no_telpon'=>'Nomor telepon', 'nama_pic'=>'Nama kontak', 'email'=>'Email vendor', 'no_fax'=>'Fax vendor') as $field=>$label) : ?><div class="col-md-6 form-group"><label for="vendorField_<?= $field ?>"><?= $h($label) ?><?= in_array($field, array('email', 'no_fax'), true) ? ' <small class="text-muted">(opsional)</small>' : '' ?><?= $field === 'nama_vendor' ? ' <span class="text-danger">*</span>' : '' ?></label><input id="vendorField_<?= $field ?>" name="<?= $field ?>" type="<?= $field === 'email' ? 'email' : 'text' ?>" class="form-control" maxlength="<?= $field === 'alamat_vendor' ? 5000 : ($field === 'nama_vendor' ? 150 : ($field === 'email' ? 120 : ($field === 'nama_pic' ? 100 : 50))) ?>" <?= $field === 'nama_vendor' ? 'required' : '' ?>></div><?php endforeach; ?>
<div class="col-12 form-group"><label for="vendorField_deskripsi_vendor">Deskripsi vendor</label><textarea id="vendorField_deskripsi_vendor" name="deskripsi_vendor" class="form-control" rows="3" maxlength="5000" placeholder="Bidang jasa, keahlian, atau informasi tambahan vendor"></textarea><small id="workflowVendorDescriptionCount" class="form-text text-muted text-right">0 / 5000</small></div>
</div><div class="vendor-footer"><button type="button" class="btn btn-outline-secondary vendor-cancel">Batal</button><button type="button" id="workflowVendorEditDetail" class="btn btn-warning" style="display:none"><i class="fas fa-edit mr-1"></i>Edit vendor</button><button type="submit" id="workflowVendorSubmit" class="btn btn-success"><i class="fas fa-save mr-1"></i>Simpan vendor</button></div></form></div>
<div id="workflowVendorLoading" class="text-center text-muted py-3" role="status" style="display:none"><i class="fas fa-spinner fa-spin mr-2"></i>Memuat vendor...</div>
<div class="vendor-table-wrap table-responsive"><table id="workflowVendorTable" class="table table-hover" style="width:100%"><thead><tr><th>Nama vendor</th><th>Alamat</th><th>Telepon</th><th>Kontak</th><th>Email</th><th>Fax</th><th>Aksi</th></tr></thead><tbody id="workflowVendorRows"></tbody></table></div>
</div></div></div></div>
<?php endif; ?>
