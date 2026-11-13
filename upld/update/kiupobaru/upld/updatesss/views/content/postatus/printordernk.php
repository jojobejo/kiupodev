<div class="wrapper">
    <!-- Main content -->
    <?php foreach ($status as $s) : ?>
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
                    <br>
                    <b>Nama Pembuat:</b> <?= $s->nm_user ?><br>
                    <b>Departemen / Bagian:</b> <?= $s->departemen ?><br>
                    <b>Tujuan Pembelian:</b> <?= $s->tj_pembelian ?><br>
                    <b>Daftar Pengajuan:</b> 1 <br>
                </div>
                <!-- /.col -->
                <div class="col-sm-4 invoice-col text-s">
                    <br>
                    <b>Nomor Order:</b> <?= $s->nopo ?><br>
                    <b>Tanggal Order:</b> <?= $s->tgl_transaksi ?><br>
                </div>
                <!-- /.col -->

            </div>

            <div class="row">
                <div class="col-12">
                    <table class="table-bordered text-s listdb">
                        <thead>
                            <tr>
                                <td colspan="8" class="bg-black" style="font-weight: bold; font-size: medium; text-align: center;">FORM PEMESANAN</td>
                            </tr>
                            <tr style="text-align: center;">
                                <td style="width: 1%;">No</td>
                                <td>Nama Barang</td>
                                <td>Deskripsi</td>
                                <td>Keterangan</td>
                                <td style="width: 10%;">Jumlah</td>
                                <td>Harga Satuan</td>
                                <td colspan="7" style="width: 15%;">Total Harga</td>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $no = 1;
                            foreach ($detail as $d) : ?>
                                <tr>
                                    <td><?= $no++; ?></td>
                                    <td><?= $d->nama_barang ?></td>
                                    <td style="text-align: center;"><?= $d->deskripsi ?></td>
                                    <td style="text-align: center;"><?= $d->keterangan ?></td>
                                    <td style="text-align: center;"><?= $d->qty ?></td>
                                    <td style="text-align: center;"> &nbsp;Rp. <?= number_format($d->hrg_satuan, 2) ?></td>
                                    <td colspan="7" style="text-align:end">&nbsp;Rp. <?= number_format($d->total_harga, 2) ?></td>
                                </tr>
                            <?php endforeach; ?>
                            <?php
                            foreach ($total as $t) : ?>
                                <tr>
                                    <td colspan="7" style="text-align: end; padding-right:5%; font-weight: bold;">Total Harga</td>
                                    <td style="text-align:end ">&nbsp;Rp. <?= number_format($t->total_harga, 2) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>

                    <table class="table-bordered text-s listdb" style="width: 100%;">
                        <thead>
                            <?php foreach ($diskon as $d) : ?>
                                <?php if ($diskon > 0) : ?>
                                    <tr>
                                        <td colspan="8" class="bg-black color-palette" style="text-align: center; font-weight: bolder;">LIST DISKON</td>
                                    </tr>
                                    <tr>
                                        <td colspan="7" style="text-align: end;font-weight: bold;"><?= $d->keterangan ?> : </td>
                                        <td colspan="1" style="text-align:end">&nbsp;Rp. <?= number_format($d->nominal, 2) ?></td>
                                    </tr>
                                <?php endif; ?>

                            <?php endforeach; ?>
                            <?php foreach ($notepm as $np) : ?>
                                <tr>
                                    <td colspan="8" class="bg-black color-palette" style="text-align: center; font-weight: bolder;">NOTE PEMBELIAN</td>
                                </tr>
                                <tr>
                                    <td colspan="8"><?= nl2br($np->keterangan) ?> </td>
                                </tr>
                            <?php endforeach; ?>
                            <?php foreach ($total as $t) :
                                foreach ($totalDiskon as $d) :
                                    $stlhDiskon = $t->total_harga - $d->total_diskon;
                                    $tax = $s->tax / 100;
                                    $hargaPajak = $stlhDiskon * $tax;
                                    $hargaAll = $stlhDiskon + $hargaPajak; ?>
                                    <tr>
                                        <td colspan="8" class="bg-black color-palette" style="text-align: center; font-weight: bolder;">GRAND TOTAL</td>
                                    </tr>
                                    <tr>
                                        <td colspan="7" style="text-align: end;font-weight: bold;">Total Harga Setelah Diskon :</td>
                                        <td colspan="1" style="text-align:end">&nbsp;Rp.<?= number_format($stlhDiskon, 2) ?> </td>
                                    </tr>
                                    <tr>
                                        <td colspan="7" style="text-align: end;font-weight: bold;">Tax : <?= $s->tax ?>(%)</td>
                                        <td colspan="1" style="text-align:end;">&nbsp;Rp. <?= number_format($hargaPajak, 2) ?> </td>
                                    </tr>
                                    <tr>
                                        <td colspan="7" style="text-align: end; font-weight: bold;">Grand Total Harga</td>
                                        <td colspan="1" style="text-align:end;">&nbsp;Rp. <?= number_format($hargaAll, 2) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endforeach; ?>


                        </thead>
                    </table>

                </div>
                <!-- /.col -->
            </div>
            <!-- /.row -->

            <div class="row">
                <div class="col">
                    <table class="table-bordered text-s listdb" width='100%' height=''>
                        <tr>
                            <td align="center" style="width: 25%;">Yang Mengajukan,</br><img src=" <?= base_url('assets/images/qrcode/') ?>.png" style="width: 170px; height: 170px;"></br><u>( <?= $s->nm_karyawan ?> )</u></td>
                            <td align="center" style="width: 25%;">Mengetahui,</br><img src=" <?= base_url('assets/images/qrcode/')  ?>.png" style="width: 170px; height: 170px;"></br><u>( <?= $s->nm_kadep ?> )</u></td>
                            <td align="center" style="width: 25%;">Menyetujui,</br><img src=" <?= base_url('assets/images/qrcode/') . $s->acc_with ?>.png" style="width: 170px; height: 170px;"></br><u>( <?= $s->nm_direktur ?> )</u></td>
                            <td align="center" style="width: 25%;">Pelaksana,</br><img src=" <?= base_url('assets/images/qrcode/') ?>.png" style="width: 170px; height: 170px;"></br><u>( <?= $this->session->userdata('nama_user') ?> )</u></td>
                        </tr>
                    </table>
                </div>

            </div>
            <!-- /.row -->
        </section>
        <!-- /.content -->
</div>
<?php endforeach; ?>