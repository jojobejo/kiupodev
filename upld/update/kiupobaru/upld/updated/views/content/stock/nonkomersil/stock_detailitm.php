<?php foreach ($item as $i) : ?>
    <div class="content-wrapper">
        <div class="content-header">
            <div class="container-fluid">
                <div class="row mb-2 ml-2">
                    <div class="col-sm-0">
                        <h1 class="m-0">Detail Stock Item : <b style="text-transform:uppercase"><?= $i->nama_barang ?> (<?= $i->satuan ?>) (<?= $i->kode_barang ?>)</b></h1>
                    </div><!-- /.col -->
                    <div class="col-sm-0 ml-2">
                        <a href="<?= base_url('stocknonkomersil') ?>" class="btn btn-sm btn-block btn-success mt-1"><i class="fas fa-undo-alt"></i></a>
                    </div>
                </div><!-- /.row -->

                <div class="card">
                    <div class="card-body">
                        <div class="row align-items-center mb-2">
                            <div class="row align-items-center mb-2">
                                <div class="col-auto" hidden>
                                    <h1 class="m-0">Tanggal Transaksi : <?= shortdate_indo($start_date) . " " . "-" . " " .  shortdate_indo($end_date)  ?></b></h1>
                                </div>
                                <div class="col-auto">
                                    <h3>Stock Tersedia : <?= $i->hasil ?></h3>
                                </div>
                                <div class="col-auto">
                                    <form method="POST" action="<?= base_url('stock/filterqtybytgl'); ?>" class="form-inline">
                                        <label for="start_date" class="mr-2">Tanggal Mulai:</label>
                                        <input type="date" class="form-control" name="start_date" id="start_date" value="<?= isset($start_date) ? $start_date : '' ?>">
                                        <label for="end_date" class="ml-2 mr-2">Tanggal Akhir:</label>
                                        <input type="date" name="end_date" class="form-control" id="end_date" value="<?= isset($end_date) ? $end_date : '' ?>">
                                        <input type="text" name="kdbarang" class="form-control mr-3" id="kdbarang" value="<?= $i->kode_sistem ?>" hidden>
                                        <button type="submit" class="btn btn-primary ml-2">Cari</button>
                                    </form>
                                </div>
                                <div class="col-auto">
                                    <a href="<?= base_url('detailtransaksi/') . $i->kode_sistem ?>" class="btn btn-block btn-success"><i class="fas fa-home"></i></a>
                                </div>
                            </div>
                        </div>

                        <!-- Tempat menampilkan hasil -->
                        <div id="result"></div>
                        <table class="table table-bordered mb-5">
                            <thead style="background-color: #212529; color:white;">
                                <tr>
                                    <td style="text-align: center;">Kode Transaksi</td>
                                    <td style="text-align: center; width: 5%;">Tanggal Transaksi</td>
                                    <td style="text-align: center;">Kode Akun</td>
                                    <td style="text-align: center;">Keterangan</td>
                                    <td style="text-align: center;">Departemen</td>
                                    <td style="text-align: center;">PIC</td>
                                    <td style="text-align: center;">Qty</td>
                                    <?php if ($this->session->userdata('kode') == 'KEU09') : ?>
                                        <td style="text-align: center;">#</td>
                                    <?php elseif ($this->session->userdata('kode') == 'KEU02') : ?>
                                        <td style="text-align: center;">#</td>
                                    <?php endif; ?>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($stock as $s) :
                                    $qs     = "";
                                    $btn    = "";
                                    $txt    = "";
                                    // KODE AKUN
                                    if ($s->kd_akun == '11512') {
                                        $qs     = "-";
                                        $btn    = "btn btn-block bg-cstm1 btn-sm color-palette";
                                        $txt    = 'Pengurangan Barang';
                                    } elseif ($s->kd_akun == '11511') {
                                        $qs     = '';
                                        $btn    = "btn btn-block btn-sm bg-cstm2 color-palette";
                                        $txt    = 'Penambahan Barang';
                                    } elseif ($s->kd_akun == '11513') {
                                        $qs     = '';
                                        $btn    = "btn btn-block btn-sm bg-cstm3 color-palette";
                                        $txt    = 'adjustmen stock(+)';
                                    } elseif ($s->kd_akun == '11514') {
                                        $qs     = '-';
                                        $btn    = "btn btn-block btn-sm bg-cstm4 color-palette";
                                        $txt    = 'adjustmen stock(-)';
                                    }
                                ?>
                                    <tr>
                                        <td style="text-align: center; width: 5%;"><?= $s->kd_transaksi ?></td>
                                        <td style="text-align: center;"><?= $s->tgl_transaksi ?></td>
                                        <td>
                                            <a href="#" class="<?= $btn ?>"><b style="text-transform:uppercase; color: #212529;"><?= $txt ?></b></a>
                                        </td>
                                        <td style="text-align: center;"><?= $s->ket ?></td>
                                        <?php if ($s->lvusr == "") : ?>
                                            <td style="text-align: center;">ADMIN</td>
                                            <td style="text-align: center;"><?= $s->inpt ?></td>
                                        <?php else : ?>
                                            <td style="text-align: center;"><?= $s->dep ?></td>
                                            <td style="text-align: center;"><?= $s->nmreq ?></td>
                                        <?php endif; ?>
                                        <td style="text-align: center;"><?= $qs . $s->qty . " " . "(" . $s->nm_satuan . ")" ?></td>
                                        <?php if ($this->session->userdata('kode') == 'KEU09') : ?>
                                            <td style="text-align: center;">
                                                <a href="<?= base_url('tr_trash/1/') . $s->id ?>" class="btn btn-danger btn-sm"><i class="fa fa-trash-alt"></i></a>
                                            </td>
                                        <?php elseif ($this->session->userdata('kode') == 'KEU02') : ?>
                                            <td style="text-align: center;">
                                                <a href="<?= base_url('tr_trash/1/') . $s->id ?>" class="btn btn-danger btn-sm"><i class="fa fa-trash-alt"></i></a>
                                            </td>
                                        <?php endif; ?>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        <?php if (!empty($pagination)) : ?>
                            <div class="mt-3">
                                <p class="text-center text-muted mb-2">Total <?= (int) $transaction_total ?> transaksi &mdash; 10 data per halaman</p>
                                <?= $pagination ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div><!-- /.container-fluid -->
        </div>
        <!-- /.content-header -->
    </div>
<?php endforeach; ?>
