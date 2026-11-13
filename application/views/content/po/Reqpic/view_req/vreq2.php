<div class="content-wrapper">
    <div class="content-header">
        <div class="container-fluid">
            <div class="card">
                <div class="card-body">
                    <div class="row mb-2">
                        <div class="col-sm-4">
                            <h1 class="m-0">List Request PIC</h1>
                        </div>
                        <div class="col-sm mb-2">
                                <a href="<?= base_url('reqpic') ?>" class="btn btn-md btn-primary btn-block"><i class="fas fa-home"></i></a>
                            </div>
                            <div class="col-sm mb-2">
                                <a href="<?= base_url('reqpicaccreq') ?>" class="btn btn-md btn-warning btn-block"><b>REQUEST ACC</b></a>
                            </div>
                            <div class="col-sm mb-2">
                                <a href="<?= base_url('index_brsedia') ?>" class="btn btn-md btn-info btn-block"><b>BARANG TERSEDIA</b></a>
                            </div>
                            <div class="col-sm mb-2">
                                <a href="<?= base_url('index_done') ?>" class="btn btn-md btn-success btn-block"><b>DONE</b></a>
                            </div>
                    </div> <!-- END ROW -->
                    <table class="table table-bordered" id="list_reqpic">
                        <thead class="table-dark">
                            <tr>
                                <td>Nama Pengaju</td>
                                <td>Departemen</td>
                                <td>Tanggal Transaksi</td>
                                <td>Jam</td>
                                <td>Tujuan Pembelian</td>
                                <td>Status</td>
                                <td>#</td>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($getlistpic as $g) : ?>
                                <tr>
                                    <td><?= $g->nm_user ?></td>
                                    <td><?= $g->departemen ?></td>
                                    <td><?= format_tgl_lahir($g->tgl_transaksi) ?></td>
                                    <td><?= format_jam_24($g->create_at) ?></td>
                                    <td><?= $g->tj_pembelian ?></td>
                                    <td>
                                        <a class="btn btn-block <?= strpos(trim((string) $g->status), 'MENUNGGU PENYERAHAN') === 0 ? 'btn-secondary' : 'btn-info' ?> btn-sm"><b><?= htmlspecialchars(trim((string) $g->status), ENT_QUOTES, 'UTF-8') ?></b></a>
                                    </td>
                                    <!-- <?php if ($g->status == 'ON PROGRESS') : ?>
                                            <td><a class="btn btn-block btn-warning btn-sm"><b><?= $g->status ?></b></a></td>
                                        <?php elseif ($g->status == 'REQUEST ACC') : ?>
                                            <td><a class="btn btn-block btn-warning btn-sm"><b><?= $g->status ?></b></a></td>
                                        <?php elseif ($g->status == 'BARANG TERSEDIA') : ?>
                                            <td><a class="btn btn-block btn-info btn-sm"><b><?= $g->status ?></b></a></td>
                                        <?php elseif ($g->status == 'DONE') : ?>
                                            <td><a class="btn btn-block btn-success btn-sm"><b><?= $g->status ?></b></a></td>
                                        <?php endif; ?> -->
                                    <td>
                                        <div class="row">
                                            <div class="col">
                                                <a class="btn btn-block btn-primary btn-sm" href="<?= base_url('reqpic/detreqbarangpic/' . $g->kd_po_nk) ?>"><i class="fas fa-eye"></i></a>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>

                </div> <!-- END CARD BODY -->
            </div> <!-- END CARD -->
        </div>
    </div>
</div>
