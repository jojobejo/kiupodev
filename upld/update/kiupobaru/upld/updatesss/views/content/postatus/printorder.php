<?php $this->load->view('content/po/_po_summary_helpers') ?>
<div class="wrapper">
    <!-- Main content -->
    <?php foreach ($status as $s) : ?>
        <?php
        $supplierPrint = !empty($isSupplierPrint);
        $printPpnMode = isset($printPpnMode) && strtolower((string) $printPpnMode) === 'exclude' ? 'exclude' : 'include';
        $supplierPpnMode = $supplierPrint ? $printPpnMode : 'exclude';
        $supplierIncludePpn = $supplierPrint && $supplierPpnMode === 'include';
        $supplierPpnLabel = $supplierIncludePpn ? 'Include PPN' : 'Exclude PPN';
        $supplierEffectiveTaxPercent = $supplierPrint ? ($supplierIncludePpn ? 0 : 11) : po_num($s->tax);
        list($poPrintRows, $poPrintSummary) = po_build_item_rows($detail, $diskon, 'detail', $supplierEffectiveTaxPercent, $supplierIncludePpn);
        $poPrintDiscountRows = po_build_discount_rows($diskon, $poPrintRows, 'detail', $supplierEffectiveTaxPercent);
        $poPrintSummary = po_apply_discount_rows_summary($poPrintSummary, $poPrintDiscountRows);
        $poPrintSummary = po_add_tax_summary($poPrintSummary, $supplierEffectiveTaxPercent);
        $supplierTaxPercent = po_num($poPrintSummary['tax_percent']);
        $supplierTaxMultiplier = 1 + ($supplierTaxPercent / 100);
        $supplierGrandTotal = 0;
        $supplierKeteranganHargaPpn = '';
        foreach ($poPrintRows as $poPrintRow) {
            if (!empty($poPrintRow['is_bonus'])) {
                continue;
            }

            $poPrintSource = $poPrintRow['source'];
            $poPrintMode = isset($poPrintSource->keterangan_harga_ppn) ? strtolower(trim((string) $poPrintSource->keterangan_harga_ppn)) : '';
            if (in_array($poPrintMode, array('exclude', 'include'), true)) {
                $supplierKeteranganHargaPpn = $poPrintMode;
                break;
            }
        }

        $supplierDisplayTotalBefore = $supplierIncludePpn ? $poPrintSummary['total_before_discount'] * $supplierTaxMultiplier : $poPrintSummary['total_before_discount'];
        $supplierDisplayTotalDiscount = $poPrintSummary['total_discount'];
        if ($supplierKeteranganHargaPpn === 'include' && !$supplierIncludePpn) {
            $supplierDisplayTotalDiscount = po_exclude_ppn($supplierDisplayTotalDiscount, $supplierTaxPercent);
        } elseif ($supplierKeteranganHargaPpn === 'exclude' && $supplierIncludePpn) {
            $supplierDisplayTotalDiscount = po_include_tax($supplierDisplayTotalDiscount, $supplierTaxPercent);
        }
        $supplierDisplayTotalAfter = max($supplierDisplayTotalBefore - $supplierDisplayTotalDiscount, 0);
        $supplierDisplayTaxValue = $supplierIncludePpn
            ? $supplierDisplayTotalAfter - po_exclude_ppn($supplierDisplayTotalAfter, $supplierTaxPercent)
            : $supplierDisplayTotalAfter * ($supplierTaxPercent / 100);
        ?>
        <section class="m-4">
            <!-- title row -->
            <div class="row">
                <div class="col-12">
                    <h2 class="page-header">
                        <div class="row">
                            <img src="<?= base_url('assets/images/logo1.png') ?>" style="width:120px;height:40px" alt=""></i>
                            <h2>PT. Karisma Indoagro Universal</h2>
                        </div>
                    </h2>
                </div>
                <!-- /.col -->
            </div>
            <!-- info row -->
            <div class="row invoice-info">
                <div class="col-sm-4 invoice-col text-s">
                    Kepada Yth.
                    <address>
                        <strong><?= $s->nama_suplier ?></strong><br>
                        <?= $s->alamat_suplier ?><br>
                        Telp : <?= $s->no_telpon ?>,<br>
                        Fax : <?= $s->no_fax ?><br>
                        Email : <?= $s->email ?>
                    </address>
                </div>
                <!-- /.col -->
                <div class="col-sm-4 invoice-col text-s">
                    Pemesan
                    <address>
                        <strong>PT.Karisma Indoagro Universal</strong><br>
                        Jl. Semeru No.89, Ajung, Kabupaten Jember<br>
                        Telp 1: (0331) 4833 33 / 4877 88<br>
                        Email: karismaindoagro@gmail.com
                    </address>

                </div>

                <!-- /.col -->
                <div class="col-sm-4 invoice-col text-s">
                    <br>
                    <b>Nomor Order:</b> <?= $s->no_po ?><br>
                    <b>Tanggal Order:</b> <?= $s->tgl_transaksi ?><br>
                    <img src="<?= base_url('assets/images/logoPT/') . $s->gbr_logo ?>" style="margin-top: 2px;margin-bottom: 5px; width: 200px; height: 75px;" alt="">
                </div>
                <?php if ($s->status == 'CANCEL') : ?>
                    <button class="btn btn-lg btn-block bg-danger m-2">
                        <i class="fas fa-times"></i>
                        &nbsp;
                        <b>PO CANCEL</b>
                        &nbsp;
                        <i class="fas fa-times"></i>
                    </button>
                <?php endif; ?>
                <!-- /.col -->

            </div>
            <?php foreach ($CountItem as $c) :
                if ($c->total_item == '15') {
                    $a = '18';
                } else if ($c->total_item == '14') {
                    $a = '18';
                } else if ($c->total_item == '13') {
                    $a = '18';
                } else if ($c->total_item == '12') {
                    $a = '18';
                } else if ($c->total_item == '11') {
                    $a = '18';
                } else if ($c->total_item == '10') {
                    $a = '18';
                } else if ($c->total_item == '9') {
                    $a = '18';
                } else if ($c->total_item == '8') {
                    $a = '18';
                } else if ($c->total_item == '7') {
                    $a = '18';
                } else if ($c->total_item == '6') {
                    $a = '18';
                } else if ($c->total_item == '5') {
                    $a = '18';
                } else if ($c->total_item == '4') {
                    $a = '18';
                } else if ($c->total_item == '3') {
                    $a = '18';
                } else if ($c->total_item == '2') {
                    $a = '18';
                } else if ($c->total_item == '1') {
                    $a = '15';
                } else {
                    $a = '14';
                }
            ?>
                <!-- /.row -->
            <?php endforeach; ?>

            <div class="row">
                <div class="col-12">
                    <table class="table-bordered text-s listdb">
                        <thead>
                            <tr>
                                <td colspan="6" class="bg-black" style="font-weight: bold; font-size: medium; text-align: center;">FORM PEMESANAN</td>
                            </tr>
                            <tr style="text-align: center;">
                                <td style="width: 1%;">No</td>
                                <td>Nama Barang</td>
                                <td>Satuan</td>
                                <td style="width: 10%;">Qty</td>
                                <td>Harga Satuan</td>
                                <td style="width: <?= $a ?>%;">Total Harga</td>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $no = 1; ?>
                            <?php foreach ($poPrintRows as $row) : ?>
                                <?php
                                if ($row['is_bonus']) {
                                    continue;
                                }
                                $supplierUnitPrice = $supplierIncludePpn
                                    ? $row['harga_satuan'] * $supplierTaxMultiplier
                                    : $row['harga_satuan'];
                                $supplierRowTotal = $row['qty'] * $supplierUnitPrice;
                                $supplierGrandTotal += $supplierRowTotal;
                                ?>
                                <tr>
                                    <td><?= $no++; ?></td>
                                    <td><?= htmlspecialchars($row['nama_barang'], ENT_QUOTES, 'UTF-8') ?></td>
                                    <td style="text-align: center;"><?= htmlspecialchars($row['satuan'], ENT_QUOTES, 'UTF-8') ?></td>
                                    <td style="text-align: center;"><?= po_qty($row['qty']) ?></td>
                                    <td style="text-align: end;">&nbsp;<?= $supplierPrint ? po_money($supplierUnitPrice) : po_money($row['harga_satuan']) ?></td>
                                    <td style="text-align:end">&nbsp;<?= $supplierPrint ? po_money($supplierRowTotal) : po_money($row['total_before']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                            <tr>
                                <td colspan="5" style="text-align: end; padding-right:5%; font-weight: bold;">Total Harga</td>
                                <td style="text-align:end ">&nbsp;<?= $supplierPrint ? po_money($supplierDisplayTotalBefore) : po_money($poPrintSummary['total_before_discount']) ?></td>
                            </tr>
                        </tbody>
                    </table>
                    <table class="table-bordered text-s listdb" style="width: 100%;">
                        <thead>
                            <tr>
                                <th class="bg-black color-palette" style="text-align: center;">FRANGKO PENGIRIMAN</th>
                                <th class="bg-black color-palette" style="text-align: center;">TEMPO PEMBAYARAN</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td style="text-align: center;"><?= $s->gdg_pengiriman ?></td>
                                <td style="text-align: center;"><?= $s->tmpo_pembayaran ?> Hari</td>
                            </tr>
                        </tbody>
                    </table>
                    <table class="table-bordered text-s listdb" style="width: 100%;">
                        <thead>
                            <tr>
                                <td colspan="6" class="bg-black color-palette" style="text-align: center; font-weight: bolder;">LIST DISKON</td>
                            </tr>
                            <tr>
                                <td colspan="4" style="text-align: center; font-weight: bold;">Deskripsi Diskon</td>
                                <td style="text-align: center; font-weight: bold;">Nominal</td>
                                <td style="text-align: center; font-weight: bold;">Value</td>
                            </tr>
                            <?php foreach ($poPrintDiscountRows as $d) : ?>
                                <?php if (!empty($hideBonusDiscountRows) && $d['is_bonus_item']) continue; ?>
                                <?php if (!empty($d['label'])) : ?>
                                    <tr>
                                        <td colspan="4" style="text-align: end;font-weight: bold;"><?= htmlspecialchars($d['label'], ENT_QUOTES, 'UTF-8') ?> : </td>
                                        <?php
                                        $supplierDiscountNominal = po_num($d['nominal']);
                                        $supplierDiscountValue = po_num($d['total_discount']);
                                        if ($supplierKeteranganHargaPpn === 'include' && !$supplierIncludePpn) {
                                            $supplierDiscountNominal = po_exclude_ppn($supplierDiscountNominal, $supplierTaxPercent);
                                            $supplierDiscountValue = po_exclude_ppn($supplierDiscountValue, $supplierTaxPercent);
                                        } elseif ($supplierKeteranganHargaPpn === 'exclude' && $supplierIncludePpn) {
                                            $supplierDiscountNominal = po_include_tax($supplierDiscountNominal, $supplierTaxPercent);
                                            $supplierDiscountValue = po_include_tax($supplierDiscountValue, $supplierTaxPercent);
                                        }
                                        ?>
                                        <td colspan="1" style="text-align:end">&nbsp;<?= po_money($supplierDiscountNominal) ?></td>
                                        <td colspan="1" style="text-align:end">&nbsp;<?= po_money($supplierDiscountValue) ?></td>
                                    </tr>
                                <?php endif; ?>
                            <?php endforeach; ?>

                            <?php
                            $poGrandTotalAfterDiscount = $supplierDisplayTotalAfter;
                            $poGrandTotalTax = $supplierDisplayTaxValue;
                            $poGrandTotalHarga = $supplierPrint
                                ? ($supplierIncludePpn ? $poGrandTotalAfterDiscount : $poGrandTotalAfterDiscount + $poGrandTotalTax)
                                : $poPrintSummary['grand_total_with_discount'];
                            ?>

                            <tr>
                                <td colspan="6" class="bg-black color-palette" style="text-align: center; font-weight: bolder;">GRAND TOTAL</td>
                            </tr>
                            <?php if ($supplierPrint && $supplierIncludePpn) : ?>
                                <tr>
                                <td colspan="5" style="text-align: end; font-weight: bold;">Total Harga Setelah Diskon</td>
                                <td colspan="1" style="text-align:end;">&nbsp;<?= po_money($poGrandTotalHarga) ?></td>
                                </tr>
                                <tr>
                                <td colspan="5" style="text-align: end;font-weight: bold;">PPN : <?= po_qty($supplierTaxPercent) ?>(%) sudah termasuk</td>
                                <td colspan="1" style="text-align:end;">&nbsp;<?= po_money($poGrandTotalTax) ?> </td>
                                </tr>
                            <?php elseif ($supplierPrint) : ?>
                                <tr>
                                    <td colspan="5" style="text-align: end; font-weight: bold;">Total Harga Setelah Diskon</td>
                                    <td colspan="1" style="text-align:end;">&nbsp;<?= po_money($poGrandTotalAfterDiscount) ?></td>
                                </tr>
                                <tr>
                                    <td colspan="5" style="text-align: end;font-weight: bold;">PPN : <?= po_qty($supplierTaxPercent) ?>(%)</td>
                                    <td colspan="1" style="text-align:end;">&nbsp;<?= po_money($poGrandTotalTax) ?> </td>
                                </tr>
                            <?php else : ?>
                                <tr>
                                    <td colspan="5" style="text-align: end;font-weight: bold;">Tax : <?= po_qty($poPrintSummary['tax_percent']) ?>(%)</td>
                                    <td colspan="1" style="text-align:end;">&nbsp;<?= po_money($poPrintSummary['tax_with_discount']) ?> </td>
                                </tr>
                            <?php endif; ?>
                            <tr>
                                <td colspan="5" style="text-align: end; font-weight: bold;">Grand Total Harga</td>
                                <td colspan="1" style="text-align:end;">&nbsp;<?= $supplierPrint ? po_money_round($poGrandTotalHarga) : po_money($poPrintSummary['grand_total_with_discount']) ?></td>
                            </tr>
                        </thead>
                    </table>
                    <table class="table-bordered text-s listdb" style="width: 100%; border-color: black;">
                        <tbody>
                            <?php foreach ($totalDiskon as $t) :
                                if ($t->total_item == '7') {
                                    $b = '14';
                                } else if ($t->total_item == '6') {
                                    $b = '13';
                                } else if ($t->total_item == '5') {
                                    $b = '12';
                                } else if ($t->total_item == '4') {
                                    $b = '11';
                                } else if ($t->total_item == '3') {
                                    $b = '10';
                                } else if ($t->total_item == '2') {
                                    $b = '9';
                                } else if ($t->total_item == '1') {
                                    $b = '8';
                                } else {
                                    $b = '15';
                                }
                                foreach ($totalnote as $tn) {
                                    if ($tn->total_nt_item == '1') {
                                        $c = '1';
                                    } else if ($tn->total_nt_item == '2') {
                                        $c = '2';
                                    } else if ($tn->total_nt_item == '3') {
                                        $c = '3';
                                    } else if ($tn->total_nt_item == '4') {
                                        $c = '4';
                                    } else if ($tn->total_nt_item == '5') {
                                        $c = '5';
                                    } else if ($tn->total_nt_item == '6') {
                                        $c = '6';
                                    } else if ($tn->total_nt_item == '7') {
                                        $c = '7';
                                    } else if ($tn->total_nt_item == '8') {
                                        $c = '8';
                                    } else {
                                        $c = '9';
                                    }

                                    $d = $b + $c;
                                }

                            ?>
                            <?php endforeach; ?>
                            <tr>

                                <td style="text-align: center; background-color: lime; width: 50%; font-weight: bold; color: red;">
                                    MOHON INFORMASI DAHULU,<br>
                                    JIKA EXP DATE KURANG DARI 2 THN<br>
                                    DARI TGL PENGIRIMAN
                                </td>
                                <td colspan="2" class="bg-black color-palette" style="text-align: center; font-weight: bolder;">NOTE UNTUK SUPLIER</td>
                            </tr>
                            <tr>
                                <?php $noteRows = count($notesuplier) > 0 ? count($notesuplier) : 1; ?>
                                <td rowspan="<?= $noteRows ?>" style="text-align: justify; background-color: yellow;width: 50%;">
                                    * SHIP TO : <br>
                                    KARISMA INDOAGRO UNIVERSAL <br>
                                    &nbsp;&nbsp;&nbsp;<?= $s->shipment_to ?><br>
                                    &nbsp;&nbsp;&nbsp;<?= $s->alamat_ship ?> <br>
                                    * Sebelum kirim barang mohon konfirmasi terlebih dahulu <br>
                                    &nbsp;&nbsp;&nbsp;<?= $s->cp_shipment ?> <br>
                                    &nbsp;&nbsp;&nbsp;<?= $s->no_cp ?> <br>
                                    <?= nl2br($s->ket_1) ?><br>
                                </td>
                                <?php if (count($notesuplier) > 0) : ?>
                                    <td colspan="2" class="bg-orange"><?= nl2br($notesuplier[0]->isi_note); ?></td>
                                <?php else : ?>
                                    <td colspan="2" class="bg-orange">&nbsp;</td>
                                <?php endif; ?>
                            </tr>
                            <?php foreach ($notesuplier as $index => $ns) : ?>
                                <?php if ($index === 0) : ?>
                                    <?php continue; ?>
                                <?php endif; ?>
                                <tr>
                                    <td colspan="2" class="bg-orange"><?= nl2br($ns->isi_note); ?></td>
                                </tr>
                            <?php endforeach; ?>

                        </tbody>
                    </table>
                </div>
                <!-- /.col -->
            </div>
            <!-- /.row -->

            <div class="row">
                <div class="col">
                    <table class="table-bordered text-s listdb" width='100%' height=''>
                        <tr>
                            <td align="center" style="width: 50%;">Pemesan,</br><img src=" <?= base_url('assets/images/qrcode/') . $s->acc_with ?>.png" style="width: 170px; height: 170px;"></br><u>( <?= $s->nama_user ?> )</u></td>
                            <td align="center">Disetujui,</br></br></br></br></br></br></br></br><u>( <?= $s->nama_suplier ?> )</u></td>
                        </tr>
                    </table>
                </div>

            </div>
            <!-- /.row -->
        </section>
        <!-- /.content -->
</div>
<?php endforeach; ?>
