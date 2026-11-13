<?php
if (!function_exists('pojasa_h')) {
    function pojasa_h($value)
    {
        return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('pojasa_money')) {
    function pojasa_money($value)
    {
        return 'Rp. ' . number_format((float) $value, 0, ',', '.');
    }
}

if (!function_exists('pojasa_text')) {
    function pojasa_text($value)
    {
        $value = trim((string) $value);
        return $value === '' ? '-' : pojasa_h($value);
    }
}

$lvSession = (string) $this->session->userdata('lv');
$kodeSession = $this->session->userdata('kode');
$isPicAccount = $lvSession === '4';
$isPurchasingAdmin = in_array($lvSession, array('1', '2'), true);
$isPicOwner = $lvSession === '4' && $request->kd_user === $kodeSession;
$isKadepDirekturPurchasing = in_array($lvSession, array('1', '2', '3', '5'), true);
$isSubmittedToDirektur = $request->status === 'REVIEW DIREKTUR';
$showProgressTracking = $isPicAccount && $request->status === 'SPK TERBIT';
$showVendorCostAudit = !$isPicAccount && !$isKadepDirekturPurchasing;
$showBastCompletion = !$isPicAccount && !$isKadepDirekturPurchasing;
$showVendorPaymentTracking = !$isPicAccount && !$isKadepDirekturPurchasing;
$showVendorEvaluation = !$isPicAccount && !$isKadepDirekturPurchasing;
$showLogProgress = true;
$canApproveKadep = $lvSession === '5' && $request->status === 'ON PROGRESS';
$canApproveDirektur = $lvSession === '3' && $request->status === 'REVIEW DIREKTUR';
$canSubmitDirektur = $isPurchasingAdmin && in_array($request->status, array('ACC-KADEP', 'REVIEW PURCHASING'), true);
$canGenerateSpk = $isPurchasingAdmin && in_array($request->status, array('ACC DIREKTUR', 'PROGRESS VENDOR'), true) && !$request->no_spk;
$canEditScope = $isPurchasingAdmin && !$isSubmittedToDirektur && in_array($request->status, array('ACC-KADEP', 'REVIEW PURCHASING'), true);
$canReviseRequest = $isPicOwner && in_array($request->status, array('REJECT', 'PENDING'), true);
$canProgressInput = in_array($request->status, array('ACC DIREKTUR', 'SPK TERBIT', 'PROGRESS VENDOR'), true) && ($isPurchasingAdmin || $isPicOwner);
$canProjectDocumentInput = $canEditScope;
$canProjectDocumentManage = $canEditScope;
$canPurchasingStageInput = in_array($request->status, array('ACC DIREKTUR', 'SPK TERBIT', 'PROGRESS VENDOR'), true) && $isPurchasingAdmin;
$canPaymentInput = in_array($request->status, array('ACC DIREKTUR', 'SPK TERBIT', 'PROGRESS VENDOR', 'DONE'), true) && $isPurchasingAdmin;
$canEvaluationInput = $request->status === 'DONE' && $isPurchasingAdmin;
$latestProgressPercent = $latest_progress ? (float) $latest_progress->progress_persen : 0;
$biayaRequest = (float) $request->estimasi_total;
$biayaActual = (float) $biaya_summary->total_realisasi;
$selisihActual = $biayaActual - $biayaRequest;
$progressStatusLabel = $request->status ? (string) $request->status : 'BELUM ADA STATUS';
$progressStatusBadge = in_array($request->status, array('ACC-KADEP', 'ACC DIREKTUR', 'SPK TERBIT', 'PROGRESS VENDOR', 'DONE'), true) ? 'success' : ($request->status === 'REJECT' ? 'danger' : 'warning');
?>
<div class="content-wrapper">
    <div class="content-header">
        <div class="container-fluid">
            <div class="d-flex align-items-center mb-3">
                <a href="<?= base_url('pononkomersiljasa') ?>" class="btn btn-secondary btn-sm mr-3">
                    <i class="fas fa-arrow-left"></i>
                </a>
                <h3 class="mb-0">Detail PO Jasa</h3>
            </div>

            <?php if ($this->session->flashdata('error')) : ?>
                <div class="alert alert-danger">
                    <?= pojasa_h($this->session->flashdata('error')) ?>
                </div>
            <?php endif; ?>

            <?php if ($this->session->flashdata('success')) : ?>
                <div class="alert alert-success">
                    <?= pojasa_h($this->session->flashdata('success')) ?>
                </div>
            <?php endif; ?>

            <div class="row">
                <div class="col-md-8">
                    <div class="card">
                        <div class="card-header">
                            <strong><?= pojasa_h($request->kd_po_jasa) ?></strong>
                            <span class="badge badge-<?= in_array($request->status, array('SPK TERBIT', 'PROGRESS VENDOR', 'DONE'), true) ? 'success' : ($request->status === 'REJECT' ? 'danger' : 'warning') ?> ml-2">
                                <?= pojasa_h($request->status) ?>
                            </span>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <dl>
                                        <dt>No SPK</dt>
                                        <dd><?= pojasa_text($request->no_spk) ?></dd>
                                        <dt>Toko / Vendor</dt>
                                        <dd><?= pojasa_text($request->nama_vendor) ?></dd>
                                        <dt>Kategori</dt>
                                        <dd><?= pojasa_text($request->kategori_jasa) ?></dd>
                                        <dt>PIC Vendor</dt>
                                        <dd><?= pojasa_text($request->nama_pic) ?> <?= $request->no_telpon ? '(' . pojasa_h($request->no_telpon) . ')' : '' ?></dd>
                                    </dl>
                                </div>
                                <div class="col-md-6">
                                    <dl>
                                        <dt>Pengaju</dt>
                                        <dd><?= pojasa_text($request->nm_user) ?></dd>
                                        <dt>Departemen</dt>
                                        <dd><?= pojasa_text($request->departemen) ?></dd>
                                        <dt>Tanggal Request</dt>
                                        <dd><?= pojasa_text($request->tgl_request) ?></dd>
                                    </dl>
                                </div>
                            </div>
                            <dl>
                                <dt>Deskripsi Jasa</dt>
                                <dd><?= pojasa_text($request->tujuan_pekerjaan) ?></dd>
                            </dl>
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="card">
                        <div class="card-header">
                            <strong>Aksi Approval</strong>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <strong>Status Progress</strong>
                                    <span class="badge badge-<?= $progressStatusBadge ?>"><?= pojasa_h($progressStatusLabel) ?></span>
                                </div>
                                <small class="text-muted d-block mt-2">
                                    Tahapan approval mengikuti status request, bukan persentase progress vendor.
                                </small>
                            </div>
                            <?php if ($canApproveKadep) : ?>
                                <button type="button" class="btn btn-success btn-block btnApprovalPojasa" data-action="approve_kadep" data-kd-po="<?= pojasa_h($request->kd_po_jasa) ?>">
                                    <i class="fas fa-check"></i> ACC KADEP
                                </button>
                                <button type="button" class="btn btn-warning btn-block btnApprovalPojasa" data-action="pending" data-kd-po="<?= pojasa_h($request->kd_po_jasa) ?>">
                                    <i class="fas fa-pause"></i> Pending
                                </button>
                                <button type="button" class="btn btn-danger btn-block btnApprovalPojasa" data-action="reject" data-kd-po="<?= pojasa_h($request->kd_po_jasa) ?>">
                                    <i class="fas fa-times"></i> Reject
                                </button>
                            <?php elseif ($canApproveDirektur) : ?>
                                <button type="button" class="btn btn-success btn-block btnApprovalPojasa" data-action="approve_direktur" data-kd-po="<?= pojasa_h($request->kd_po_jasa) ?>">
                                    <i class="fas fa-check"></i> ACC DIREKTUR
                                </button>
                                <button type="button" class="btn btn-warning btn-block btnApprovalPojasa" data-action="pending" data-kd-po="<?= pojasa_h($request->kd_po_jasa) ?>">
                                    <i class="fas fa-pause"></i> Pending
                                </button>
                                <button type="button" class="btn btn-danger btn-block btnApprovalPojasa" data-action="reject" data-kd-po="<?= pojasa_h($request->kd_po_jasa) ?>">
                                    <i class="fas fa-times"></i> Reject
                                </button>
                            <?php elseif ($canSubmitDirektur) : ?>
                                <button type="button" class="btn btn-primary btn-block btnApprovalPojasa" data-action="submit_direktur" data-kd-po="<?= pojasa_h($request->kd_po_jasa) ?>">
                                    <i class="fas fa-paper-plane"></i> Ajukan ke Direktur
                                </button>
                                <small class="text-muted d-block mt-2">Pastikan scope dan estimasi biaya sudah direview purchasing sebelum diajukan.</small>
                            <?php elseif ($canGenerateSpk) : ?>
                                <button type="button" class="btn btn-primary btn-block btnGenerateSpkPojasa" data-kd-po="<?= pojasa_h($request->kd_po_jasa) ?>">
                                    <i class="fas fa-file-signature"></i> Generate PO/SPK
                                </button>
                            <?php else : ?>
                                <div class="alert alert-light mb-0">
                                    Belum ada aksi untuk level akses dan status saat ini.
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <?php if ($canReviseRequest) : ?>
                <div class="card">
                    <div class="card-header">
                        <strong>Revisi Request Project Jasa</strong>
                    </div>
                    <div class="card-body">
                        <form id="formRevisePojasa" enctype="multipart/form-data">
                            <input type="hidden" name="kd_po_jasa" value="<?= pojasa_h($request->kd_po_jasa) ?>">
                            <div class="row">
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label>Tanggal Request</label>
                                        <input type="date" class="form-control" name="tgl_request" value="<?= pojasa_h($request->tgl_request) ?>">
                                    </div>
                                </div>
                                <input type="hidden" name="tgl_target" class="pojasa-target-sync" value="<?= pojasa_h($request->tgl_target) ?>">
                                <div class="col-md-9">
                                    <div class="form-group">
                                        <label>Toko / Vendor</label>
                                        <input type="text" class="form-control" name="nama_vendor_jasa" value="<?= pojasa_h($request->nama_vendor) ?>">
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <input type="hidden" name="lokasi_pekerjaan" value="<?= pojasa_h($request->lokasi_pekerjaan ? $request->lokasi_pekerjaan : $request->departemen) ?>">
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <label>Deskripsi Jasa</label>
                                        <input type="text" class="form-control" name="tujuan_pekerjaan" value="<?= pojasa_h($request->tujuan_pekerjaan) ?>">
                                    </div>
                                </div>
                            </div>
                            <div class="table-responsive">
                                <table class="table table-sm table-bordered tbScopeEditorPojasa">
                                    <thead class="thead-dark">
                                        <tr>
                                            <th>Nama Jasa</th>
                                            <th>Deskripsi</th>
                                            <th style="width: 10%">Qty</th>
                                            <th style="width: 15%">Harga</th>
                                            <th style="width: 6%">#</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($details as $detail) : ?>
                                            <tr>
                                                <td>
                                                    <input type="text" class="form-control" name="nama_pekerjaan[]" value="<?= pojasa_h($detail->nama_pekerjaan) ?>">
                                                    <input type="hidden" name="satuan[]" value="<?= pojasa_h($detail->satuan ? $detail->satuan : 'Lot') ?>">
                                                </td>
                                                <td><input type="text" class="form-control" name="deskripsi[]" value="<?= pojasa_h($detail->deskripsi) ?>"></td>
                                                <td><input type="number" min="0" step="0.01" class="form-control" name="qty[]" value="<?= pojasa_h($detail->qty) ?>"></td>
                                                <td><input type="number" min="0" step="0.01" class="form-control" name="hrg_satuan[]" value="<?= pojasa_h($detail->hrg_satuan) ?>"></td>
                                                <td><button type="button" class="btn btn-danger btn-sm btnRemoveEditorScopePojasa"><i class="fas fa-trash"></i></button></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            <button type="button" class="btn btn-primary btn-sm btnAddEditorScopePojasa mb-3">
                                <i class="fas fa-plus"></i> Tambah baris baru
                            </button>
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <h5 class="mb-0">Dokumen Pendukung Revisi</h5>
                                <button type="button" class="btn btn-primary btn-sm btnAddRevisiDokumenPojasa">
                                    <i class="fas fa-plus"></i> Tambah Dokumen
                                </button>
                            </div>
                            <div id="revisiDokumenPojasaRows">
                                <div class="row revisi-dokumen-pojasa-row">
                                    <div class="col-md-5">
                                        <div class="form-group">
                                            <label>File Pendukung</label>
                                            <input type="file" class="form-control" name="dokumen_project_jasa[]" accept=".jpg,.jpeg,.png,.pdf,.doc,.docx,.xls,.xlsx,.txt,.rtf,.csv">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>Keterangan Dokumen</label>
                                            <input type="text" class="form-control" name="keterangan_project_file[]" placeholder="Keterangan dokumen pendukung revisi">
                                        </div>
                                    </div>
                                    <div class="col-md-1">
                                        <label>&nbsp;</label>
                                        <button type="button" class="btn btn-danger btn-block btnRemoveRevisiDokumenPojasa">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <button type="submit" class="btn btn-success">
                                <i class="fas fa-save"></i> Simpan Revisi dan Ajukan Ulang
                            </button>
                        </form>
                    </div>
                </div>
            <?php endif; ?>

            <div class="row">
                <div class="col-md-4">
                    <div class="small-box bg-info">
                        <div class="inner">
                            <h3><?= pojasa_money($biayaRequest) ?></h3>
                            <p>Biaya Request</p>
                        </div>
                        <div class="icon"><i class="fas fa-file-invoice-dollar"></i></div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="small-box bg-warning">
                        <div class="inner">
                            <h3><?= pojasa_money($biayaActual) ?></h3>
                            <p>Biaya Actual Purchasing</p>
                        </div>
                        <div class="icon"><i class="fas fa-calculator"></i></div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="small-box bg-secondary">
                        <div class="inner">
                            <h3><?= pojasa_money($selisihActual) ?></h3>
                            <p>Selisih Actual</p>
                        </div>
                        <div class="icon"><i class="fas fa-balance-scale"></i></div>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <strong>Scope Pekerjaan</strong>
                    <?php if ($canEditScope) : ?>
                        <div class="ml-auto">
                            <button type="button" class="btn btn-warning btn-sm btnToggleScopeEditPojasa">
                                <i class="fas fa-edit"></i> Edit
                            </button>
                            <button type="button" class="btn btn-success btn-sm btnRekamScopePojasa d-none">
                                <i class="fas fa-save"></i> Rekam
                            </button>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="card-body table-responsive">
                    <table class="table table-sm table-bordered table-striped" id="tbScopeReadonlyPojasa">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Nama Jasa</th>
                                <th>Deskripsi</th>
                                <th>Qty</th>
                                <th>Harga</th>
                                <th>Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $no = 1; ?>
                            <?php if (empty($details)) : ?>
                                <tr>
                                    <td colspan="6" class="text-center text-muted">Belum ada data scope pekerjaan.</td>
                                </tr>
                            <?php endif; ?>
                            <?php foreach ($details as $detail) : ?>
                                <tr>
                                    <td><?= $no++ ?></td>
                                    <td><?= pojasa_text($detail->nama_pekerjaan) ?></td>
                                    <td><?= pojasa_text($detail->deskripsi) ?></td>
                                    <td><?= pojasa_text($detail->qty) ?></td>
                                    <td><?= pojasa_money($detail->hrg_satuan) ?></td>
                                    <td><?= pojasa_money($detail->total_harga) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot>
                            <tr>
                                <th colspan="5" class="text-right">Estimasi Total</th>
                                <th id="scopeReadonlyTotalPojasa"><?= pojasa_money($request->estimasi_total) ?></th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
                <?php if ($canEditScope) : ?>
                    <div class="card-body border-top d-none" id="scopeEditorPojasa">
                        <form id="formReviewScopePojasa">
                            <input type="hidden" name="kd_po_jasa" value="<?= pojasa_h($request->kd_po_jasa) ?>">
                            <div class="table-responsive">
                                <table class="table table-sm table-bordered tbScopeEditorPojasa">
                                    <thead class="thead-dark">
                                        <tr>
                                            <th>Nama Jasa</th>
                                            <th>Deskripsi</th>
                                            <th style="width: 10%">Qty</th>
                                            <th style="width: 15%">Harga</th>
                                            <th style="width: 6%">#</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($details)) : ?>
                                            <tr>
                                                <td>
                                                    <input type="text" class="form-control" name="nama_pekerjaan[]" value="">
                                                    <input type="hidden" name="satuan[]" value="Lot">
                                                </td>
                                                <td><input type="text" class="form-control" name="deskripsi[]" value=""></td>
                                                <td><input type="number" min="0" step="0.01" class="form-control" name="qty[]" value="1"></td>
                                                <td><input type="number" min="0" step="0.01" class="form-control" name="hrg_satuan[]" value="0"></td>
                                                <td><button type="button" class="btn btn-danger btn-sm btnRemoveEditorScopePojasa"><i class="fas fa-trash"></i></button></td>
                                            </tr>
                                        <?php endif; ?>
                                        <?php foreach ($details as $detail) : ?>
                                            <tr>
                                                <td>
                                                    <input type="text" class="form-control" name="nama_pekerjaan[]" value="<?= pojasa_h($detail->nama_pekerjaan) ?>">
                                                    <input type="hidden" name="satuan[]" value="<?= pojasa_h($detail->satuan ? $detail->satuan : 'Lot') ?>">
                                                </td>
                                                <td><input type="text" class="form-control" name="deskripsi[]" value="<?= pojasa_h($detail->deskripsi) ?>"></td>
                                                <td><input type="number" min="0" step="0.01" class="form-control" name="qty[]" value="<?= pojasa_h($detail->qty) ?>"></td>
                                                <td><input type="number" min="0" step="0.01" class="form-control" name="hrg_satuan[]" value="<?= pojasa_h($detail->hrg_satuan) ?>"></td>
                                                <td><button type="button" class="btn btn-danger btn-sm btnRemoveEditorScopePojasa"><i class="fas fa-trash"></i></button></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            <button type="button" class="btn btn-primary btn-sm btnAddEditorScopePojasa">
                                <i class="fas fa-plus"></i> Tambah baris baru
                            </button>
                            <button type="submit" class="btn btn-success btn-sm d-none">
                                <i class="fas fa-save"></i> Simpan Review Scope
                            </button>
                        </form>
                    </div>
                <?php endif; ?>
            </div>

            <div class="row">
                <?php if ($showProgressTracking) : ?>
                <div class="col-lg-6">
                    <div class="card">
                        <div class="card-header">
                            <strong>Tracking Progress Vendor</strong>
                        </div>
                        <div class="card-body">
                            <?php if ($canProgressInput) : ?>
                                <form id="formProgressPojasa" class="mb-3">
                                    <input type="hidden" name="kd_po_jasa" value="<?= pojasa_h($request->kd_po_jasa) ?>">
                                    <div class="row">
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label>Tanggal</label>
                                                <input type="date" class="form-control" name="tgl_progress" value="<?= date('Y-m-d') ?>">
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label>Progress %</label>
                                                <input type="number" min="0" max="100" step="1" class="form-control" name="progress_persen" value="<?= number_format($latestProgressPercent, 0, '.', '') ?>">
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label>Status</label>
                                                <select class="form-control" name="status_progress">
                                                    <option value="ON TRACK">ON TRACK</option>
                                                    <option value="TERLAMBAT">TERLAMBAT</option>
                                                    <option value="BUTUH FOLLOW UP">BUTUH FOLLOW UP</option>
                                                    <option value="SELESAI">SELESAI</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label>Milestone</label>
                                        <input type="text" class="form-control" name="milestone" placeholder="Contoh: Pekerjaan tahap awal selesai">
                                    </div>
                                    <div class="form-group">
                                        <label>Catatan</label>
                                        <textarea class="form-control" name="catatan" rows="2"></textarea>
                                    </div>
                                    <button type="submit" class="btn btn-primary btn-sm">
                                        <i class="fas fa-save"></i> Simpan Progress
                                    </button>
                                </form>
                            <?php endif; ?>

                            <div class="table-responsive">
                                <table class="table table-bordered table-striped" id="tbProgressPojasa">
                                    <thead>
                                        <tr>
                                            <th>Tanggal</th>
                                            <th>Milestone</th>
                                            <th>%</th>
                                            <th>Status</th>
                                            <th>User</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($progress)) : ?>
                                            <tr>
                                                <td colspan="5" class="text-center text-muted">Belum ada data progress.</td>
                                            </tr>
                                        <?php endif; ?>
                                        <?php foreach ($progress as $row) : ?>
                                            <tr>
                                                <td><?= pojasa_text($row->tgl_progress) ?></td>
                                                <td><?= pojasa_text($row->milestone) ?><br><small><?= pojasa_text($row->catatan) ?></small></td>
                                                <td><?= pojasa_text($row->progress_persen) ?>%</td>
                                                <td><?= pojasa_text($row->status_progress) ?></td>
                                                <td><?= pojasa_text($row->created_name) ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <div class="<?= $showProgressTracking ? 'col-lg-6' : 'col-lg-12' ?>">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <strong>Dokumen Pendukung</strong>
                            <?php if ($canProjectDocumentInput) : ?>
                                <div class="ml-auto">
                                    <button type="button" class="btn btn-warning btn-sm btnToggleDokumenEditPojasa">
                                        <i class="fas fa-edit"></i> Edit
                                    </button>
                                    <button type="button" class="btn btn-success btn-sm btnRekamDokumenPojasa d-none">
                                        <i class="fas fa-save"></i> Rekam
                                    </button>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="card-body">
                            <?php if ($canProjectDocumentInput) : ?>
                                <form id="formUploadFilePojasa" class="mb-3 d-none" enctype="multipart/form-data">
                                    <input type="hidden" name="kd_po_jasa" value="<?= pojasa_h($request->kd_po_jasa) ?>">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <h5 class="mb-0">Tambah Dokumen</h5>
                                        <button type="button" class="btn btn-primary btn-sm" id="btnAddDokumenJasaDetail">
                                            <i class="fas fa-plus"></i> Tambah Dokumen
                                        </button>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label>Jenis Dokumen</label>
                                                <select class="form-control" name="jenis_dokumen">
                                                    <option value="DOKUMEN PENDUKUNG">DOKUMEN PENDUKUNG</option>
                                                    <option value="PENAWARAN">PENAWARAN</option>
                                                    <option value="KONTRAK">KONTRAK</option>
                                                    <option value="FOTO PROGRESS">FOTO PROGRESS</option>
                                                    <option value="INVOICE">INVOICE</option>
                                                    <option value="BAST">BAST</option>
                                                    <option value="LAINNYA">LAINNYA</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-8" id="dokumenJasaDetailRows">
                                            <div class="row dokumen-jasa-detail-row">
                                                <div class="col-md-6">
                                                    <div class="form-group">
                                                        <label>File</label>
                                                        <input type="file" class="form-control" name="dokumen_jasa[]" accept=".jpg,.jpeg,.png,.pdf,.doc,.docx,.xls,.xlsx,.txt,.rtf,.csv">
                                                    </div>
                                                </div>
                                                <div class="col-md-5">
                                                    <div class="form-group">
                                                        <label>Keterangan</label>
                                                        <input type="text" class="form-control" name="keterangan_file[]" placeholder="Keterangan dokumen">
                                                    </div>
                                                </div>
                                                <div class="col-md-1">
                                                    <label>&nbsp;</label>
                                                    <button type="button" class="btn btn-danger btn-block btnRemoveDokumenJasaDetail">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <button type="submit" class="btn btn-primary btn-sm d-none">
                                        <i class="fas fa-upload"></i> Upload Dokumen
                                    </button>
                                </form>
                            <?php endif; ?>

                            <div class="table-responsive">
                                <table class="table table-bordered table-striped" id="tbFilePojasa">
                                    <thead>
                                        <tr>
                                            <th>Jenis</th>
                                            <th>File</th>
                                            <th>User</th>
                                            <th>Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody id="tbFilePojasaBody">
                                        <?php if (empty($files)) : ?>
                                            <tr>
                                                <td colspan="4" class="text-center text-muted">Belum ada dokumen pendukung.</td>
                                            </tr>
                                        <?php endif; ?>
                                        <?php foreach ($files as $file) : ?>
                                            <tr>
                                                <td><?= pojasa_text($file->jenis_dokumen) ?></td>
                                                <td><?= pojasa_text($file->file_original) ?><br><small><?= pojasa_text($file->keterangan) ?></small></td>
                                                <td><?= pojasa_text($file->uploaded_name) ?></td>
                                                <td>
                                                    <a href="<?= base_url('pojasa/file/view/' . (int) $file->id_file_jasa) ?>" class="btn btn-info btn-sm" target="_blank" title="Tinjau">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                    <a href="<?= base_url('pojasa/file/download/' . (int) $file->id_file_jasa) ?>" class="btn btn-secondary btn-sm" title="Download">
                                                        <i class="fas fa-download"></i>
                                                    </a>
                                                    <?php if ($canProjectDocumentManage) : ?>
                                                        <button type="button" class="btn btn-warning btn-sm document-edit-action d-none" data-toggle="modal" data-target="#modalEditFilePojasa<?= (int) $file->id_file_jasa ?>" title="Ubah">
                                                            <i class="fas fa-edit"></i>
                                                        </button>
                                                        <button type="button" class="btn btn-danger btn-sm btnDeleteFilePojasa document-edit-action d-none" data-id-file="<?= (int) $file->id_file_jasa ?>" title="Hapus">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <?php if ($showVendorCostAudit) : ?>
                <div class="col-lg-7">
                    <div class="card">
                        <div class="card-header">
                            <strong>Audit Biaya Project Vendor</strong>
                        </div>
                        <div class="card-body">
                            <?php if ($canPurchasingStageInput) : ?>
                                <form id="formBiayaPojasa" class="mb-3">
                                    <input type="hidden" name="kd_po_jasa" value="<?= pojasa_h($request->kd_po_jasa) ?>">
                                    <div class="row">
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label>Tanggal</label>
                                                <input type="date" class="form-control" name="tgl_biaya" value="<?= date('Y-m-d') ?>">
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label>Jenis Biaya</label>
                                                <input type="text" class="form-control" name="jenis_biaya" placeholder="Jasa, material, tambahan">
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label>No Invoice</label>
                                                <input type="text" class="form-control" name="no_invoice_vendor">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label>Deskripsi Biaya</label>
                                        <input type="text" class="form-control" name="deskripsi_biaya">
                                    </div>
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label>Nominal Estimasi</label>
                                                <input type="number" min="0" step="0.01" class="form-control biaya-estimasi" name="nominal_estimasi" value="0">
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label>Nominal Realisasi</label>
                                                <input type="number" min="0" step="0.01" class="form-control biaya-realisasi" name="nominal_realisasi" value="0">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="alert alert-light">
                                        Selisih input: <strong id="selisihBiayaPojasa">Rp. 0</strong>
                                    </div>
                                    <button type="submit" class="btn btn-primary btn-sm">
                                        <i class="fas fa-save"></i> Simpan Audit Biaya
                                    </button>
                                </form>
                            <?php endif; ?>

                            <div class="table-responsive">
                                <table class="table table-bordered table-striped" id="tbBiayaPojasa">
                                    <thead>
                                        <tr>
                                            <th>Tanggal</th>
                                            <th>Jenis</th>
                                            <th>Estimasi</th>
                                            <th>Realisasi</th>
                                            <th>Selisih</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($biaya)) : ?>
                                            <tr>
                                                <td colspan="5" class="text-center text-muted">Belum ada audit biaya project vendor.</td>
                                            </tr>
                                        <?php endif; ?>
                                        <?php foreach ($biaya as $row) : ?>
                                            <tr>
                                                <td><?= pojasa_text($row->tgl_biaya) ?></td>
                                                <td><?= pojasa_text($row->jenis_biaya) ?><br><small><?= pojasa_text($row->deskripsi_biaya) ?></small></td>
                                                <td><?= pojasa_money($row->nominal_estimasi) ?></td>
                                                <td><?= pojasa_money($row->nominal_realisasi) ?></td>
                                                <td><?= pojasa_money($row->selisih) ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                    <tfoot>
                                        <tr>
                                            <th colspan="2" class="text-right">Total</th>
                                            <th><?= pojasa_money($biaya_summary->total_estimasi) ?></th>
                                            <th><?= pojasa_money($biaya_summary->total_realisasi) ?></th>
                                            <th><?= pojasa_money($biaya_summary->total_selisih) ?></th>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <?php if ($showBastCompletion) : ?>
                <div class="<?= $showVendorCostAudit ? 'col-lg-5' : 'col-lg-12' ?>">
                    <div class="card">
                        <div class="card-header">
                            <strong>BAST / Project Completion</strong>
                        </div>
                        <div class="card-body">
                            <?php if ($bast) : ?>
                                <dl>
                                    <dt>No BAST</dt>
                                    <dd><?= pojasa_h($bast->no_bast) ?></dd>
                                    <dt>Tanggal BAST</dt>
                                    <dd><?= pojasa_h($bast->tgl_bast) ?></dd>
                                    <dt>Penerima Pekerjaan</dt>
                                    <dd><?= pojasa_h($bast->penerima_pekerjaan) ?></dd>
                                    <dt>Catatan</dt>
                                    <dd><?= pojasa_h($bast->catatan_bast) ?></dd>
                                </dl>
                            <?php elseif ($canPurchasingStageInput) : ?>
                                <form id="formBastPojasa">
                                    <input type="hidden" name="kd_po_jasa" value="<?= pojasa_h($request->kd_po_jasa) ?>">
                                    <div class="form-group">
                                        <label>No BAST</label>
                                        <input type="text" class="form-control" name="no_bast" placeholder="Nomor BAST">
                                    </div>
                                    <div class="form-group">
                                        <label>Tanggal BAST</label>
                                        <input type="date" class="form-control" name="tgl_bast" value="<?= date('Y-m-d') ?>">
                                    </div>
                                    <div class="form-group">
                                        <label>Penerima Pekerjaan</label>
                                        <input type="text" class="form-control" name="penerima_pekerjaan" value="<?= pojasa_h($nmuser) ?>">
                                    </div>
                                    <div class="form-group">
                                        <label>Catatan BAST</label>
                                        <textarea class="form-control" name="catatan_bast" rows="3"></textarea>
                                    </div>
                                    <button type="submit" class="btn btn-success btn-block" <?= $latestProgressPercent < 100 ? 'disabled' : '' ?>>
                                        <i class="fas fa-check-circle"></i> Selesaikan Project
                                    </button>
                                    <?php if ($latestProgressPercent < 100) : ?>
                                        <small class="text-muted d-block mt-2">Progress harus 100% sebelum BAST diselesaikan.</small>
                                    <?php endif; ?>
                                </form>
                            <?php else : ?>
                                <div class="alert alert-light mb-0">
                                    BAST dapat diproses Purchasing/Admin setelah ACC DIREKTUR dan progress mencapai 100%.
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>

            <?php if ($showVendorPaymentTracking || $showVendorEvaluation) : ?>
            <div class="row">
                <div class="col-lg-7">
                    <div class="card">
                        <div class="card-header">
                            <strong>Payment Tracking Vendor</strong>
                        </div>
                        <div class="card-body">
                            <?php if ($canPaymentInput) : ?>
                                <form id="formPaymentPojasa" class="mb-3">
                                    <input type="hidden" name="kd_po_jasa" value="<?= pojasa_h($request->kd_po_jasa) ?>">
                                    <div class="row">
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label>Tanggal Invoice</label>
                                                <input type="date" class="form-control" name="tgl_invoice" value="<?= date('Y-m-d') ?>">
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label>Jatuh Tempo</label>
                                                <input type="date" class="form-control" name="jatuh_tempo">
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label>No Invoice</label>
                                                <input type="text" class="form-control" name="no_invoice">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label>Tagihan</label>
                                                <input type="number" min="0" step="0.01" class="form-control payment-tagihan" name="nominal_tagihan" value="0">
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label>Terbayar</label>
                                                <input type="number" min="0" step="0.01" class="form-control payment-bayar" name="nominal_bayar" value="0">
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label>Status Bayar</label>
                                                <select class="form-control" name="status_bayar">
                                                    <option value="BELUM BAYAR">BELUM BAYAR</option>
                                                    <option value="PARTIAL">PARTIAL</option>
                                                    <option value="LUNAS">LUNAS</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label>Catatan Payment</label>
                                        <input type="text" class="form-control" name="catatan_payment">
                                    </div>
                                    <div class="alert alert-light">
                                        Sisa bayar input: <strong id="sisaPaymentPojasa">Rp. 0</strong>
                                    </div>
                                    <button type="submit" class="btn btn-primary btn-sm">
                                        <i class="fas fa-save"></i> Simpan Payment
                                    </button>
                                </form>
                            <?php endif; ?>

                            <div class="table-responsive">
                                <table class="table table-bordered table-striped" id="tbPaymentPojasa">
                                    <thead>
                                        <tr>
                                            <th>Invoice</th>
                                            <th>Jatuh Tempo</th>
                                            <th>Tagihan</th>
                                            <th>Terbayar</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($payments)) : ?>
                                            <tr>
                                                <td colspan="5" class="text-center text-muted">Belum ada payment tracking vendor.</td>
                                            </tr>
                                        <?php endif; ?>
                                        <?php foreach ($payments as $payment) : ?>
                                            <tr>
                                                <td><?= pojasa_text($payment->no_invoice) ?><br><small><?= pojasa_text($payment->tgl_invoice) ?></small></td>
                                                <td><?= pojasa_text($payment->jatuh_tempo) ?></td>
                                                <td><?= pojasa_money($payment->nominal_tagihan) ?></td>
                                                <td><?= pojasa_money($payment->nominal_bayar) ?></td>
                                                <td><?= pojasa_text($payment->status_bayar) ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                    <tfoot>
                                        <tr>
                                            <th colspan="2" class="text-right">Total</th>
                                            <th><?= pojasa_money($payment_summary->total_tagihan) ?></th>
                                            <th><?= pojasa_money($payment_summary->total_bayar) ?></th>
                                            <th>Sisa <?= pojasa_money($payment_summary->sisa_bayar) ?></th>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-5">
                    <div class="card">
                        <div class="card-header">
                            <strong>Evaluasi Vendor</strong>
                        </div>
                        <div class="card-body">
                            <?php if ($evaluation) : ?>
                                <div class="alert alert-info">
                                    Score Vendor: <strong><?= number_format((float) $evaluation->total_score, 2) ?></strong>
                                </div>
                            <?php endif; ?>

                            <?php if ($canEvaluationInput) : ?>
                                <form id="formEvaluationPojasa">
                                    <input type="hidden" name="kd_po_jasa" value="<?= pojasa_h($request->kd_po_jasa) ?>">
                                    <div class="row">
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label>Kualitas</label>
                                                <input type="number" min="1" max="5" step="1" class="form-control eval-score" name="kualitas_score" value="<?= $evaluation ? pojasa_h($evaluation->kualitas_score) : '5' ?>">
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label>Waktu</label>
                                                <input type="number" min="1" max="5" step="1" class="form-control eval-score" name="ketepatan_waktu_score" value="<?= $evaluation ? pojasa_h($evaluation->ketepatan_waktu_score) : '5' ?>">
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label>Biaya</label>
                                                <input type="number" min="1" max="5" step="1" class="form-control eval-score" name="biaya_score" value="<?= $evaluation ? pojasa_h($evaluation->biaya_score) : '5' ?>">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="alert alert-light">
                                        Score rata-rata input: <strong id="avgEvaluationPojasa">5.00</strong>
                                    </div>
                                    <div class="form-group">
                                        <label>Catatan Evaluasi</label>
                                        <textarea class="form-control" name="catatan_evaluasi" rows="3"><?= $evaluation ? pojasa_h($evaluation->catatan_evaluasi) : '' ?></textarea>
                                    </div>
                                    <button type="submit" class="btn btn-success btn-block">
                                        <i class="fas fa-star"></i> Simpan Evaluasi Vendor
                                    </button>
                                </form>
                            <?php else : ?>
                                <div class="alert alert-light mb-0">
                                    Evaluasi vendor dapat diisi setelah project DONE.
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <?php if ($showLogProgress) : ?>
            <div class="card">
                <div class="card-header">
                    <strong>LOG Progress</strong>
                </div>
                <div class="card-body table-responsive">
                    <table class="table table-bordered table-striped" id="tbNotePojasa">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Waktu</th>
                                <th>User</th>
                                <th>Status</th>
                                <th>Catatan</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $noNote = 1; ?>
                            <?php if (empty($notes)) : ?>
                                <tr>
                                    <td colspan="5" class="text-center text-muted">Belum ada log progress.</td>
                                </tr>
                            <?php endif; ?>
                            <?php foreach ($notes as $note) : ?>
                                <tr>
                                    <td><?= $noNote++ ?></td>
                                    <td><?= pojasa_text($note->create_at) ?></td>
                                    <td><?= pojasa_text($note->nama_user) ?></td>
                                    <td><?= pojasa_text($note->aksi_status) ?></td>
                                    <td><?= pojasa_text($note->isi_note) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php if ($canProjectDocumentManage) : ?>
    <?php foreach ($files as $file) : ?>
        <div class="modal fade" id="modalEditFilePojasa<?= (int) $file->id_file_jasa ?>" tabindex="-1" role="dialog">
            <div class="modal-dialog modal-lg" role="document">
                <div class="modal-content">
                    <form class="formEditFilePojasa" enctype="multipart/form-data">
                        <input type="hidden" name="id_file_jasa" value="<?= (int) $file->id_file_jasa ?>">
                        <div class="modal-header">
                            <h5 class="modal-title">Ubah Dokumen Pendukung</h5>
                            <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                        </div>
                        <div class="modal-body">
                            <div class="form-group">
                                <label>Jenis Dokumen</label>
                                <input type="text" class="form-control" name="jenis_dokumen" value="<?= pojasa_h($file->jenis_dokumen) ?>">
                            </div>
                            <div class="form-group">
                                <label>Keterangan</label>
                                <input type="text" class="form-control" name="keterangan_file" value="<?= pojasa_h($file->keterangan) ?>">
                            </div>
                            <div class="form-group">
                                <label>Replace File</label>
                                <input type="file" class="form-control" name="dokumen_jasa" accept=".jpg,.jpeg,.png,.pdf,.doc,.docx,.xls,.xlsx,.txt,.rtf,.csv">
                                <small class="text-muted">Kosongkan jika hanya mengubah jenis atau keterangan dokumen.</small>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Tutup</button>
                            <button type="submit" class="btn btn-primary">Simpan</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
<?php endif; ?>
