<div class="content-wrapper">
    <div class="content-header">
        <div class="container-fluid">
            <?php $this->load->view('content/po/_po_summary_helpers') ?>
            <?php
            $poAutoKeteranganHargaPpn = '';
            if (!empty($tmp)) {
                foreach ($tmp as $poTmpItem) {
                    if (!empty($poTmpItem->is_bonus)) {
                        continue;
                    }

                    $poTmpPpnMode = isset($poTmpItem->keterangan_harga_ppn) ? strtolower(trim((string) $poTmpItem->keterangan_harga_ppn)) : '';
                    if (in_array($poTmpPpnMode, array('exclude', 'include'), true)) {
                        $poAutoKeteranganHargaPpn = $poTmpPpnMode;
                        break;
                    }
                }
            }
            $poAutoTaxValue = $poAutoKeteranganHargaPpn === 'exclude' ? 11 : 0;
            ?>
            <?php if ($this->session->flashdata('error')) : ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?= htmlspecialchars($this->session->flashdata('error'), ENT_QUOTES, 'UTF-8') ?>
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
            <?php endif; ?>
            <style>
                .po-table-wrap {
                    overflow-x: auto;
                }

                .po-input-table {
                    font-size: 14px;
                    min-width: 1240px;
                }

                .po-input-table td {
                    padding: .5rem .6rem;
                    vertical-align: middle;
                    white-space: nowrap;
                }

                .po-input-table thead td {
                    font-weight: 600;
                    text-align: center;
                }

                .po-input-table .col-item {
                    max-width: 380px;
                    overflow: hidden;
                    text-overflow: ellipsis;
                }

                .po-input-table .text-number {
                    text-align: right;
                }

                .po-input-table tbody td:first-child,
                .po-input-table tbody td:nth-child(3),
                .po-input-table tbody td:last-child {
                    text-align: center;
                }

                .po-input-table .action-cell {
                    display: flex;
                    gap: .25rem;
                    align-items: center;
                    justify-content: center;
                }

                .po-input-table .btn-icon {
                    align-items: center;
                    display: inline-flex;
                    height: 32px;
                    justify-content: center;
                    padding: 0;
                    width: 32px;
                }

                .po-muted {
                    color: #6c757d;
                    display: block;
                    font-size: 12px;
                    line-height: 1.35;
                    margin-top: 2px;
                }

                .po-money-line {
                    display: block;
                    white-space: nowrap;
                }

                .po-price-stack {
                    line-height: 1.25;
                    white-space: nowrap;
                }

                .po-price-main {
                    display: block;
                    font-weight: 600;
                }

                .po-price-sub {
                    color: #6c757d;
                    display: block;
                    font-size: 12px;
                    margin-top: 2px;
                }

                .po-mode-badge {
                    display: inline-block;
                    font-size: 11px;
                    font-weight: 700;
                    letter-spacing: 0;
                    padding: .2rem .4rem;
                }

                .po-ppn-tabs {
                    margin: 0 0 .75rem;
                }

                .po-ppn-tabs .nav-link {
                    border-radius: 6px 6px 0 0;
                    color: #495057;
                    font-weight: 700;
                    letter-spacing: 0;
                }

                .po-ppn-tabs .nav-link.active {
                    color: #007bff;
                }

                .po-summary-card {
                    border: 1px solid #dee2e6;
                    border-radius: 6px;
                    margin-top: 1rem;
                }

                .po-summary-row {
                    align-items: center;
                    border-bottom: 1px solid #edf0f2;
                    display: flex;
                    justify-content: space-between;
                    padding: .55rem .75rem;
                }

                .po-summary-row:last-child {
                    border-bottom: 0;
                }

                .po-summary-grand {
                    background: #e9f7ef;
                    color: #155724;
                    font-size: 16px;
                    font-weight: 700;
                }

                .po-price-flag {
                    color: #dc3545;
                    font-weight: 700;
                    margin: 0 0 .75rem;
                }

            </style>
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0">
                    </h1>
                </div><!-- /.col -->
            </div><!-- /.row -->
            <div style="display: flex; text-align: center;">
                <a href="<?= base_url('purchase') ?>">
                    <i class="fa fa-arrow-left  ml-4 mr-4 mt-2"></i>
                </a>
                <?php $this->load->view('content/po/modal/msuplier') ?>
                <?php foreach ($kode_suplier as $b) : ?>
                    <h3 class=""><?= $b->nama_suplier ?> </h3>
                    &nbsp;
                    <a href="#" class=" ml-3 btn btn-warning " data-toggle="modal" data-target="#editSuplier<?= $b->id_suplier ?>">
                        <i class="fa fa-solid fa-pencil-alt"></i>
                    </a>
            </div>
            <div class="row">
                <div class="col-md">
                    <a href="<?= base_url('purchase/listBarang/') . $b->kd_suplier ?>" class="btn btn-primary mb-2 mt-2 btn-block">
                        <i class="fas fa-folder-plus"></i> &nbsp; Tambah Barang
                    </a>
                </div>

                <div class="col-md">
                    <a class="btn btn-primary mb-2 mt-2 btn-block" data-toggle="modal" data-target="#modalnotebarang">
                        <i class="fas fa-notes-medical"> </i>
                        Tambah Note Barang
                    </a>
                </div>
                <div class="col-md">
                    <a class="btn btn-primary mb-2 mt-2 btn-block" data-toggle="modal" data-target="#modaldiskon">
                        <i class="fas fa-tags"> </i>
                        Tambah Diskon
                    </a>
                </div>
                <div class="col-md">
                    <a class="btn btn-primary mb-2 mt-2 btn-block" data-toggle="modal" data-target="#modaldiskonmerk">
                        <i class="fas fa-tag"> </i>
                        Diskon Merk
                    </a>
                </div>
            </div>

        </div>
    <?php endforeach; ?>

    <form action="#">
        <div class="row mb-2">
            <div class="col-md">
                <div class="input-group">
                    <div class="input-group-prepend">
                        <select class="custom-select" id="kode_po_isi" name="kode_po_isi" style="max-width: 80px;">
                            <option value="Q" selected>Q</option>
                            <option value="A">A</option>
                        </select>
                    </div>
                    <div class="input-group-prepend">
                        <span class="input-group-text"><i class="fas fa-clipboard"></i></span>
                    </div>
                    <input type="text" class="form-control" placeholder="Nomor PO" value="<?= isset($nomor_po_otomatis) ? html_escape($nomor_po_otomatis) : '' ?>" name="po_isi" id="po_isi" data-nomor-po-awal="<?= isset($nomor_po_otomatis) ? html_escape($nomor_po_otomatis) : '' ?>">
                </div>
                <small id="po_isi_feedback" class="form-text text-danger" style="display:none;"></small>
            </div>
            <div class="col-md">
                <div class="input-group">
                    <div class="input-group-prepend">
                        <span class="input-group-text"><i class="fas fa-calendar"></i></span>
                    </div>
                    <input type="date" class="form-control" placeholder="Tanggal Transaksi" value="" name="tgl_isi" id="tgl_isi">
                </div>
            </div>
            <div class="col-md">
                <div class="input-group">
                    <div class="input-group-prepend">
                        <span class="input-group-text"><i class="fas fa-truck"></i></span>
                    </div>
                    <input type="text" class="form-control" placeholder="Franko Pengiriman" value="" name="gdgpengiriman" id="gdgpengiriman">
                </div>
            </div>
            <div class="col-md">
                <div class="input-group">
                    <div class="input-group-prepend">
                        <span class="input-group-text"><i class="fas fa-hourglass-half"></i></span>
                    </div>
                    <input type="text" class="form-control" placeholder="Tempo Pembayaran" value="" name="tmpo" id="tmpo">
                </div>
            </div>

            <div class="col-md" hidden>
                <div class="input-group">
                    <div class="input-group-prepend">
                        <span class="input-group-text"><i class="fas fa-hourglass-half"></i></span>
                    </div>
                    <input type="number" class="form-control" id="taxisi_in" name="taxisi_in" value="<?= $poAutoTaxValue ?>" readonly hidden>
                </div>
            </div>

            <div class="col-md">
                <div class="input-group">
                    <div class="input-group-prepend">
                        <span class="input-group-text"><i class="fas fa-calendar"></i></span>
                    </div>
                    <input type="text" class="form-control" placeholder="Tanggal Transaksi" value="<?= $kdpo ?>" name="kd_po_isi" id="kd_po_isi" readonly>
                </div>
            </div>
        </div>

        <?php $this->load->view('content/po/modalpo') ?>

        <?php
        list($poItemRows, $poSummary) = po_build_item_rows($tmp, $tmpdiskon, 'tmp', $poAutoTaxValue);
        $poDiscountRows = po_build_discount_rows($tmpdiskon, $poItemRows, 'tmp', $poAutoTaxValue);
        foreach ($poDiscountRows as &$poDiscountRow) {
            $poDiscountRow['label'] = po_remove_item_name_prefix($poDiscountRow['label'], $poItemRows);
        }
        unset($poDiscountRow);
        $poGlobalDiscountTotal = 0;
        foreach ($poDiscountRows as $poDiscountRow) {
            if (po_value($poDiscountRow, 'discount_scope', '') === 'global') {
                $poGlobalDiscountTotal += po_num(po_value($poDiscountRow, 'total_discount', 0));
            }
        }
        $poSummary = po_apply_discount_rows_summary($poSummary, $poDiscountRows);
        $poSummary = po_add_tax_summary($poSummary, $poAutoTaxValue);
        $poKeteranganHargaPpn = '';
        foreach ($poItemRows as $poItemRow) {
            if (!empty($poItemRow['is_bonus'])) {
                continue;
            }

            $poSource = $poItemRow['source'];
            $poMode = isset($poSource->keterangan_harga_ppn) ? strtolower(trim((string) $poSource->keterangan_harga_ppn)) : '';
            if (in_array($poMode, array('exclude', 'include'), true)) {
                $poKeteranganHargaPpn = $poMode;
                break;
            }
        }
        $poPpnConversionPercent = (float) $poAutoTaxValue > 0 ? (float) $poAutoTaxValue : 11;
        $poPpnConversionMultiplier = 1 + ($poPpnConversionPercent / 100);
        $poPrimaryPpnTab = $poKeteranganHargaPpn === 'include' ? 'include' : 'exclude';
        $poPpnTabModes = $poPrimaryPpnTab === 'include'
            ? array('include', 'exclude')
            : array('exclude', 'include');
        $poPpnTabLabels = array(
            'include' => 'INCLUDE',
            'exclude' => 'EXCLUDE',
        );
        ?>
        <?php if ($poSummary['has_validation_error']) : ?>
            <div class="alert alert-danger">
                <?php foreach ($poSummary['validation_errors'] as $error) : ?>
                    <div><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        <?php if ($poKeteranganHargaPpn !== '') : ?>
            <div class="po-price-flag">
                Keterangan: Data order ini menggunakan harga <?= strtoupper($poKeteranganHargaPpn) ?> PPN
            </div>
        <?php endif; ?>
        <ul class="nav nav-tabs po-ppn-tabs" id="poPpnTab" role="tablist">
            <?php foreach ($poPpnTabModes as $poTabIndex => $poPpnTabMode) : ?>
                <li class="nav-item">
                    <a class="nav-link <?= $poTabIndex === 0 ? 'active' : '' ?>" id="po-ppn-<?= $poPpnTabMode ?>-tab" data-toggle="tab" href="#po-ppn-<?= $poPpnTabMode ?>" role="tab" aria-controls="po-ppn-<?= $poPpnTabMode ?>" aria-selected="<?= $poTabIndex === 0 ? 'true' : 'false' ?>">
                        <?= $poPpnTabLabels[$poPpnTabMode] ?> PPN
                    </a>
                </li>
            <?php endforeach; ?>
        </ul>
        <div class="tab-content" id="poPpnTabContent">
            <?php foreach ($poPpnTabModes as $poTabIndex => $poPpnTabMode) : ?>
                <?php
                $poIsIncludeTab = $poPpnTabMode === 'include';
                $poTabTaxPercent = $poIsIncludeTab ? 0 : $poPpnConversionPercent;
                $poTabSummaryBefore = 0;
                $poTabSummaryAfter = 0;
                ?>
                <div class="tab-pane fade <?= $poTabIndex === 0 ? 'show active' : '' ?>" id="po-ppn-<?= $poPpnTabMode ?>" role="tabpanel" aria-labelledby="po-ppn-<?= $poPpnTabMode ?>-tab">
                    <div class="table-responsive po-table-wrap">
                        <table id="table_form_input_po_<?= $poPpnTabMode ?>" class="table table-sm table-striped po-input-table">
                            <thead style="background-color: #212529; color:white;">
                                <tr>
                                    <td>No</td>
                                    <td>Nama Barang</td>
                                    <td>Keterangan Harga</td>
                                    <td>Satuan</td>
                                    <td>Qty</td>
                                    <td>Qty Kecil</td>
                                    <td>Harga Input Tersimpan</td>
                                    <td>Harga Satuan</td>
                                    <td>Harga Satuan Kecil</td>
                                    <td>Harga Setelah Diskon</td>
                                    <td>Total Harga</td>
                                    <td>Total Harga Setelah Diskon</td>
                                    <td>#</td>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $no = 1; ?>
                                <?php foreach ($poItemRows as $row) : ?>
                                    <?php $t = $row['source']; ?>
                                    <?php
                                    $poRowPpnMode = isset($t->keterangan_harga_ppn) ? strtolower(trim((string) $t->keterangan_harga_ppn)) : '';
                                    if (!in_array($poRowPpnMode, array('exclude', 'include'), true)) {
                                        $poRowPpnMode = $row['is_bonus'] ? '' : 'exclude';
                                    }

                                    $poRowModeLabel = $poRowPpnMode !== '' ? strtoupper($poRowPpnMode) . ' PPN' : '-';
                                    $poRowModeClass = $poRowPpnMode === 'include' ? 'badge-info' : ($poRowPpnMode === 'exclude' ? 'badge-warning' : 'badge-secondary');
                                    $poRowHargaSatuanInput = $row['harga_satuan_simpan'];
                                    $poRowHargaSatuanKecilInput = $row['harga_satuan_kecil_simpan'];
                                    if ($poIsIncludeTab) {
                                        if ($poRowPpnMode === 'include') {
                                            $poRowHargaSatuanView = $poRowHargaSatuanInput;
                                            $poRowHargaSatuanKecilView = $poRowHargaSatuanKecilInput;
                                        } else {
                                            $poRowHargaSatuanView = $row['harga_satuan'] * $poPpnConversionMultiplier;
                                            $poRowHargaSatuanKecilView = $row['harga_satuan_kecil'] * $poPpnConversionMultiplier;
                                        }
                                        $poRowDiskonPerUnitView = $row['diskon_per_unit'] * $poPpnConversionMultiplier;
                                    } else {
                                        $poRowHargaSatuanView = $row['harga_satuan'];
                                        $poRowHargaSatuanKecilView = $row['harga_satuan_kecil'];
                                        $poRowDiskonPerUnitView = $row['diskon_per_unit'];
                                    }
                                    $poRowHargaFinalView = $row['is_bonus'] ? 0 : max($poRowHargaSatuanKecilView - $poRowDiskonPerUnitView, 0);
                                    $poRowTotalBeforeView = $row['is_bonus'] ? 0 : ($row['qty_kecil'] * $poRowHargaSatuanKecilView);
                                    $poRowTotalAfterView = $row['is_bonus'] ? 0 : ($row['qty_kecil'] * $poRowHargaFinalView);
                                    $poTabSummaryBefore += $poRowTotalBeforeView;
                                    $poTabSummaryAfter += $poRowTotalAfterView;
                                    $poRowInputNote = $poRowPpnMode === 'include'
                                        ? 'Tersimpan Include'
                                        : ($poRowPpnMode === 'exclude' ? 'Tersimpan Exclude' : 'Bonus');
                                    ?>
                                    <tr>
                                        <td class="text-center"><?= $no++; ?></td>
                                        <td class="col-item" title="<?= htmlspecialchars($row['nama_barang'], ENT_QUOTES, 'UTF-8') ?>">
                                            <?= htmlspecialchars($row['nama_barang'], ENT_QUOTES, 'UTF-8') ?>
                                            <?php if ($row['is_bonus']) : ?>
                                                <span class="badge badge-primary ml-1">BONUS</span>
                                                <?php if ($row['bonus_note'] !== '') : ?>
                                                    <span class="po-muted"><?= htmlspecialchars($row['bonus_note'], ENT_QUOTES, 'UTF-8') ?></span>
                                                <?php endif; ?>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge po-mode-badge <?= $poRowModeClass ?>"><?= htmlspecialchars($poRowModeLabel, ENT_QUOTES, 'UTF-8') ?></span>
                                        </td>
                                        <td><?= htmlspecialchars($row['satuan'], ENT_QUOTES, 'UTF-8') ?></td>
                                        <td class="text-number"><?= po_qty($row['qty']) ?></td>
                                        <td class="text-number"><?= po_qty($row['qty_kecil']) ?></td>
                                        <td class="text-number">
                                            <span class="po-price-stack">
                                                <span class="po-price-main"><?= po_money($poRowHargaSatuanInput) ?></span>
                                                <span class="po-price-sub"><?= htmlspecialchars($poRowInputNote, ENT_QUOTES, 'UTF-8') ?></span>
                                            </span>
                                        </td>
                                        <td class="text-number"><?= po_money($poRowHargaSatuanView) ?></td>
                                        <td class="text-number"><?= po_money($poRowHargaSatuanKecilView) ?></td>
                                        <td class="text-number"><?= po_money($poRowHargaFinalView) ?></td>
                                        <td class="text-number"><?= po_money($poRowTotalBeforeView) ?></td>
                                        <td class="text-number"><?= po_money($poRowTotalAfterView) ?></td>
                                        <td>
                                            <div class="action-cell">
                                                <a href="#" class="btn btn-warning btn-sm btn-icon" data-toggle="modal" data-target="#modalEdit<?= $t->id_tmp ?>" title="Edit">
                                                    <i class="fa fa-solid fa-pencil-alt"></i>
                                                </a>
                                                <a href="#" class="btn btn-danger btn-sm btn-icon" data-toggle="modal" data-target="#hapusChart<?= $t->id_tmp ?>" title="Hapus">
                                                    <i class="fa fa-solid fa-trash-alt"></i>
                                                </a>
                                                <?php if (!$row['is_bonus']) : ?>
                                                    <a class="btn btn-sm btn-info btn-icon" data-toggle="modal" data-target="#diskonBarangNominal<?= $t->id_tmp ?>" title="Tambah Diskon Nominal">
                                                        <i class="fas fa-tags"></i>
                                                    </a>
                                                    <a class="btn btn-sm bg-lightblue btn-icon" data-toggle="modal" data-target="#diskonBarangPersen<?= $t->id_tmp ?>" title="Tambah Diskon Persentase">
                                                        <i class="fas fa-percent"></i>
                                                    </a>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <?php
                    $poTabItemDiscount = max($poTabSummaryBefore - $poTabSummaryAfter, 0);
                    $poTabSummaryDiscount = $poTabItemDiscount + $poGlobalDiscountTotal;
                    $poTabSummaryAfter = max($poTabSummaryBefore - $poTabSummaryDiscount, 0);
                    $poTabTaxValue = $poTabSummaryAfter * ($poTabTaxPercent / 100);
                    $poTabGrandTotal = $poTabSummaryAfter + $poTabTaxValue;
                    ?>
                    <div class="po-summary-card">
                        <div class="po-summary-row">
                            <span>Total Harga Sebelum Diskon</span>
                            <strong><?= po_money($poTabSummaryBefore) ?></strong>
                        </div>
                        <div class="po-summary-row">
                            <span>Total Diskon</span>
                            <strong><span class="badge badge-success"><?= po_money($poTabSummaryDiscount) ?></span></strong>
                        </div>
                        <div class="po-summary-row">
                            <span>Total Harga Setelah Diskon</span>
                            <strong><?= po_money($poTabSummaryAfter) ?></strong>
                        </div>
                        <div class="po-summary-row">
                            <span>Tax <?= po_qty($poTabTaxPercent) ?>%</span>
                            <strong><?= po_money($poTabTaxValue) ?></strong>
                        </div>
                        <div class="po-summary-row po-summary-grand">
                            <span>Grand Total Harga</span>
                            <span><?= po_money_round($poTabGrandTotal) ?></span>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <input type="text" class="form-control" id="kdsuplier" name="kdsuplier" value="<?= htmlspecialchars($kdsuplier, ENT_QUOTES, 'UTF-8') ?>" hidden readonly>
        <?php foreach ($total as $tot) : ?>
            <input type="number" class="form-control" id="jmlitem" name="jmlitem" value="<?= $tot->total_item ?>" readonly hidden>
        <?php endforeach; ?>
        <input type="number" class="form-control" id="jmlharga" name="jmlharga" value="<?= $poSummary['total_before_discount'] ?>" readonly hidden>

        <div class="table-responsive">
            <table class="table table-striped mt-2">
                <thead style="background-color: #212529; color:white;">
                    <tr>
                        <td colspan="4" style="text-align: center;">LIST DISKON</td>
                    </tr>
                    <tr>
                        <td style="text-align: center;">Deskripsi Diskon</td>
                        <td style="text-align: center;">Nominal Diskon</td>
                        <td style="text-align: center;">Value</td>
                        <td style="text-align: center;"></td>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($poDiscountRows)) : ?>
                        <?php foreach ($poDiscountRows as $d) : ?>
                            <tr>
                                <td style="text-align: center;"><?= htmlspecialchars($d['label'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td style="text-align: center;"><?= po_money($d['nominal']) ?></td>
                                <td style="text-align: center;"><?= po_money($d['total_discount']) ?></td>
                                <td style="text-align: center;">
                                    <?php if (!$d['is_bonus_item']) : ?>
                                        <a href="#" class="btn btn-warning btn-sm" data-toggle="modal" data-target="#editdiskon<?= $d['id'] ?>">
                                            <i class="fa fa-solid fa-pencil-alt"></i>
                                        </a>
                                        <a href="#" class="btn btn-danger btn-sm" data-toggle="modal" data-target="#hapusdiskon<?= $d['id'] ?>">
                                            <i class="fa fa-solid fa-trash-alt"></i>
                                        </a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else : ?>
                        <tr>
                            <td colspan="4" class="text-center text-muted">Belum ada diskon.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <table id="" class="table table-striped mt-2">
            <thead style="background-color: #212529; color:white;">
                <tr>
                    <td style="text-align: center;">LIST Note Barang</td>
                </tr>
                <tr>
                    <td>Deskripsi Note</td>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($tmpnote as $n) : ?>
                    <tr>
                        <td>
                            <div class="row">
                                <div class="col">
                                    <?= $n->isi_note ?>
                                </div>
                                <div class="col">
                                    <a href="#" class="btn btn-warning btn-sm " data-toggle="modal" data-target="#editnote<?= $n->id_nt_tmp_barang ?>">
                                        <i class="fa fa-solid fa-pencil-alt"></i>
                                    </a>
                                    <a href="#" class="btn btn-danger btn-sm " data-toggle="modal" data-target="#hapusnote<?= $n->id_nt_tmp_barang ?>">
                                        <i class="fa fa-solid fa-trash-alt"></i>
                                    </a>
                                </div>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <div class="btnBawah">
            <button class="btn btn-warning mr-2" type="reset">Reset</button>
            <button type="button" id="selesai" class="btn btn-primary">
                <i class="fa fa-print pr-1"></i>Rekam Order
            </button>
        </div>
    </div>

    </form>

</div><!-- /.container-fluid -->

</div>

<!-- /.content-header -->
</div>
