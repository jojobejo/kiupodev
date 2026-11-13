<?php $this->load->view('content/po/_po_summary_helpers') ?>
<div class="wrapper">
    <!-- Main content -->
    <?php foreach ($status as $s) : ?>
        <?php
        $printPpnMode = isset($printPpnMode) && strtolower((string) $printPpnMode) === 'exclude' ? 'exclude' : 'include';
        $printIncludePpn = $printPpnMode === 'include';
        $printPpnLabel = $printIncludePpn ? 'Include PPN' : 'Exclude PPN';
        $printPpnConversionPercent = isset($s->tax) && (float) $s->tax > 0 ? (float) $s->tax : 11;
        $printTaxPercent = $printIncludePpn ? 0 : $printPpnConversionPercent;
        $printTaxMultiplier = 1 + ($printPpnConversionPercent / 100);
        list($poPrintRows, $poPrintSummary) = po_build_item_rows($detail, $diskon, 'detail', $printPpnConversionPercent);
        $poPrintDiscountRows = po_build_discount_rows($diskon, $poPrintRows, 'detail', $printPpnConversionPercent);
        $poPrintSummary = po_apply_discount_rows_summary($poPrintSummary, $poPrintDiscountRows);
        $poPrintSummary = po_add_tax_summary($poPrintSummary, $printTaxPercent);
        $poPrintRows = po_add_tax_to_discounted_item_rows($poPrintRows, $printTaxPercent);
        $poPrintDisplayTaxPercent = $poPrintSummary['tax_percent'];
        $printKeteranganHargaPpn = '';
        foreach ($poPrintRows as $poPrintRow) {
            if (!empty($poPrintRow['is_bonus'])) {
                continue;
            }

            $poPrintSource = $poPrintRow['source'];
            $poPrintMode = isset($poPrintSource->keterangan_harga_ppn) ? strtolower(trim((string) $poPrintSource->keterangan_harga_ppn)) : '';
            if (in_array($poPrintMode, array('exclude', 'include'), true)) {
                $printKeteranganHargaPpn = $poPrintMode;
                break;
            }
        }

        $poPrintDisplayTotalBefore = 0;
        foreach ($poPrintRows as $poPrintRow) {
            if (!empty($poPrintRow['is_bonus'])) {
                continue;
            }

            $poPrintSource = $poPrintRow['source'];
            $poPrintRowPpnMode = isset($poPrintSource->keterangan_harga_ppn) ? strtolower(trim((string) $poPrintSource->keterangan_harga_ppn)) : '';
            if (!in_array($poPrintRowPpnMode, array('exclude', 'include'), true)) {
                $poPrintRowPpnMode = 'exclude';
            }

            if ($printIncludePpn) {
                $poPrintRowHargaSatuanKecilView = $poPrintRowPpnMode === 'include'
                    ? $poPrintRow['harga_satuan_kecil_simpan']
                    : po_include_tax($poPrintRow['harga_satuan_kecil'], $printPpnConversionPercent);
            } else {
                $poPrintRowHargaSatuanKecilView = $poPrintRow['harga_satuan_kecil'];
            }

            $poPrintDisplayTotalBefore += $poPrintRow['qty_kecil'] * $poPrintRowHargaSatuanKecilView;
        }

        $poPrintDisplayTotalDiscount = $poPrintSummary['total_discount'];
        if ($printKeteranganHargaPpn === 'include' && !$printIncludePpn) {
            $poPrintDisplayTotalDiscount = po_exclude_ppn($poPrintDisplayTotalDiscount, $printPpnConversionPercent);
        } elseif ($printKeteranganHargaPpn === 'exclude' && $printIncludePpn) {
            $poPrintDisplayTotalDiscount = po_include_tax($poPrintDisplayTotalDiscount, $printPpnConversionPercent);
        }
        $poPrintDisplayTotalAfter = max($poPrintDisplayTotalBefore - $poPrintDisplayTotalDiscount, 0);
        $poPrintDisplayTaxValue = $printIncludePpn
            ? 0
            : $poPrintDisplayTotalAfter * ($printTaxPercent / 100);
        $poPrintGrandTotalHarga = $printIncludePpn ? $poPrintDisplayTotalAfter : $poPrintDisplayTotalAfter + $poPrintDisplayTaxValue;
        ?>
        <section class="m-4">
            <div class="row">
                <div class="col-12">
                    <h2 class="page-header">
                        <div class="row">
                            <img src="<?= base_url('assets/images/logo1.png') ?>" style="width:120px;height:40px" alt=""></i>
                            <h2>PT. Karisma Indoagro Universal</h2>
                        </div>
                    </h2>
                </div>
            </div>

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
                <div class="col-sm-4 invoice-col text-s">
                    Pemesan
                    <address>
                        <strong>PT.Karisma Indoagro Universal</strong><br>
                        Jl. Semeru No.89, Ajung, Kabupaten Jember<br>
                        Telp 1: (0331) 4833 33 / 4877 88<br>
                        Email: karismaindoagro@gmail.com
                    </address>
                </div>
                <div class="col-sm-4 invoice-col text-s">
                    <br>
                    <b>Nomor Order:</b> <?= $s->no_po ?><br>
                    <b>Tanggal Order:</b> <?= $s->tgl_transaksi ?><br>
                    <img src="<?= base_url('assets/images/logoPT/') . $s->gbr_logo ?>" style="margin-top: 2px;margin-bottom: 5px; width: 200px; height: 75px;" alt="">
                </div>
            </div>

            <div class="row">
                <div class="col-12">
                    <table class="table-bordered text-s listdb" style="width: 100%; table-layout: fixed;">
                        <colgroup>
                            <col style="width: 2%;">
                            <col style="width: 37%;">
                            <col style="width: 5%;">
                            <col style="width: 4%;">
                            <col style="width: 5%;">
                            <col style="width: 12%;">
                            <col style="width: 12%;">
                            <col style="width: 11%;">
                            <col style="width: 12%;">
                        </colgroup>
                        <thead>
                            <tr>
                                <td colspan="9" class="bg-black" style="font-weight: bold; font-size: medium; text-align: center;">FORM PEMESANAN INTERNAL - <?= $printPpnLabel ?></td>
                            </tr>
                            <tr style="text-align: center;">
                                <td>No</td>
                                <td>Nama Barang</td>
                                <td>Satuan</td>
                                <td>Qty</td>
                                <td>Qty Kecil</td>
                                <td hidden>Harga Satuan</td>
                                <td>Harga Satuan Kecil</td>
                                <td>Harga Setelah Diskon</td>
                                <td>Total Harga</td>
                                <td>Total Harga Setelah Diskon</td>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $no = 1; ?>
                            <?php foreach ($poPrintRows as $row) : ?>
                                <?php
                                $poPrintRowSource = $row['source'];
                                $poPrintRowPpnMode = isset($poPrintRowSource->keterangan_harga_ppn) ? strtolower(trim((string) $poPrintRowSource->keterangan_harga_ppn)) : '';
                                if (!in_array($poPrintRowPpnMode, array('exclude', 'include'), true)) {
                                    $poPrintRowPpnMode = $row['is_bonus'] ? '' : 'exclude';
                                }

                                if ($printIncludePpn) {
                                    if ($poPrintRowPpnMode === 'include') {
                                        $displayHargaSatuanKecil = $row['harga_satuan_kecil_simpan'];
                                    } else {
                                        $displayHargaSatuanKecil = po_include_tax($row['harga_satuan_kecil'], $printPpnConversionPercent);
                                    }
                                    $displayDiskonPerUnit = $row['diskon_per_unit'] * $printTaxMultiplier;
                                } else {
                                    $displayHargaSatuanKecil = $row['harga_satuan_kecil'];
                                    $displayDiskonPerUnit = $row['diskon_per_unit'];
                                }

                                $displayHargaFinalUnit = $row['is_bonus'] ? 0 : max($displayHargaSatuanKecil - $displayDiskonPerUnit, 0);
                                $displayTotalBefore = $row['is_bonus'] ? 0 : ($row['qty_kecil'] * $displayHargaSatuanKecil);
                                $displayTotalAfter = $row['is_bonus'] ? 0 : ($row['qty_kecil'] * $displayHargaFinalUnit);
                                ?>
                                <tr>
                                    <td style="text-align: center;"><?= $no++; ?></td>
                                    <td style="word-wrap: break-word;">
                                        <?= htmlspecialchars($row['nama_barang'], ENT_QUOTES, 'UTF-8') ?>
                                        <?php if ($row['is_bonus']) : ?>
                                            <br><small>Bonus<?= $row['bonus_note'] !== '' ? ' - ' . htmlspecialchars($row['bonus_note'], ENT_QUOTES, 'UTF-8') : '' ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td style="text-align: center;"><?= htmlspecialchars($row['satuan'], ENT_QUOTES, 'UTF-8') ?></td>
                                    <td style="text-align: center;"><?= po_qty($row['qty']) ?></td>
                                    <td style="text-align: center;"><?= po_qty($row['qty_kecil']) ?></td>
                                    <td hidden style="text-align: end;">&nbsp;<?= po_money($row['harga_satuan']) ?></td>
                                    <td style="text-align: end;">&nbsp;<?= po_money($displayHargaSatuanKecil) ?></td>
                                    <td style="text-align: end;">&nbsp;<?= po_money($displayHargaFinalUnit) ?></td>
                                    <td style="text-align: end;">&nbsp;<?= po_money($displayTotalBefore) ?></td>
                                    <td style="text-align: end;">&nbsp;<?= po_money($displayTotalAfter) ?></td>
                                </tr>
                            <?php endforeach; ?>
                            <tr>
                                <td colspan="7" style="text-align: end;font-weight: bold;">Total Harga :</td>
                                <td style="text-align:end;font-weight: bold;">&nbsp;<?= po_money($poPrintDisplayTotalBefore) ?></td>
                                <td style="text-align:end;font-weight: bold;">&nbsp;<?= po_money_round($poPrintGrandTotalHarga) ?></td>
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
                                <td colspan="10" class="bg-black color-palette" style="text-align: center; font-weight: bolder;">LIST DISKON</td>
                            </tr>
                            <tr>
                                <td colspan="8" style="text-align: center; font-weight: bold;">Deskripsi Diskon</td>
                                <td style="text-align: center; font-weight: bold;">Nominal</td>
                                <td style="text-align: center; font-weight: bold;">Value</td>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($poPrintDiscountRows as $d) : ?>
                                <tr>
                                    <td colspan="8" style="text-align: end;font-weight: bold;"><?= htmlspecialchars($d['label'], ENT_QUOTES, 'UTF-8') ?> : </td>
                                    <?php
                                    $printDiscountNominal = po_num($d['nominal']);
                                    $printDiscountValue = po_num($d['total_discount']);
                                    if ($printKeteranganHargaPpn === 'include' && !$printIncludePpn) {
                                        $printDiscountNominal = po_exclude_ppn($printDiscountNominal, $printPpnConversionPercent);
                                        $printDiscountValue = po_exclude_ppn($printDiscountValue, $printPpnConversionPercent);
                                    } elseif ($printKeteranganHargaPpn === 'exclude' && $printIncludePpn) {
                                        $printDiscountNominal = po_include_tax($printDiscountNominal, $printPpnConversionPercent);
                                        $printDiscountValue = po_include_tax($printDiscountValue, $printPpnConversionPercent);
                                    }
                                    ?>
                                    <td colspan="1" style="text-align:end">&nbsp;<?= po_money($printDiscountNominal) ?></td>
                                    <td colspan="1" style="text-align:end">&nbsp;<?= po_money($printDiscountValue) ?></td>
                                </tr>
                            <?php endforeach; ?>
                            <tr>
                                <td colspan="10" class="bg-black color-palette" style="text-align: center; font-weight: bolder;">GRAND TOTAL</td>
                            </tr>
                            <tr>
                                <td colspan="9" style="text-align: end; font-weight: bold;">Total Diskon</td>
                                <td colspan="1" style="text-align:end;">&nbsp;<?= po_money_round_up($poPrintDisplayTotalDiscount) ?></td>
                            </tr>
                            <tr>
                                <td colspan="9" style="text-align: end; font-weight: bold;">Total Harga Setelah Diskon</td>
                                <td colspan="1" style="text-align:end;">&nbsp;<?= po_money($poPrintDisplayTotalAfter) ?></td>
                            </tr>
                            <tr>
                                <td colspan="9" style="text-align: end;font-weight: bold;">PPN : <?= po_qty($printTaxPercent) ?>(%)<?= $printIncludePpn ? ' sudah termasuk' : '' ?></td>
                                <td colspan="1" style="text-align:end;">&nbsp;<?= po_money($poPrintDisplayTaxValue) ?></td>
                            </tr>
                            <tr>
                                <td colspan="9" style="text-align: end; font-weight: bold;">Grand Total Harga</td>
                                <td colspan="1" style="text-align:end;">&nbsp;<?= po_money_round($poPrintGrandTotalHarga) ?></td>
                            </tr>
                        </tbody>
                    </table>

                    <table class="table-bordered text-s listdb" style="width: 100%; border-color: black;">
                        <tbody>
                            <?php $noteRows = count($notesuplier) > 0 ? count($notesuplier) : 1; ?>
                            <tr>
                                <td style="text-align: center; background-color: lime; width: 50%; font-weight: bold; color: red;">
                                    MOHON INFORMASI DAHULU,<br>
                                    JIKA EXP DATE KURANG DARI 2 THN<br>
                                    DARI TGL PENGIRIMAN
                                </td>
                                <td colspan="2" class="bg-black color-palette" style="text-align: center; font-weight: bolder;">NOTE UNTUK SUPLIER</td>
                            </tr>
                            <tr>
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
            </div>

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
        </section>
</div>
<?php endforeach; ?>
