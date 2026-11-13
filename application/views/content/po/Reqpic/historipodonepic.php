<a href="" class="btn btn-sm btn-success"><i class="fas fa-check"></i></a>
<div class="content-wrapper">
    <div class="content-header">
        <div class="container-fluid">
            <div class="card">
                <div class="card-body">
                    <a href="<?= base_url('reqpic') ?>" class="btn btn-sm btn-primary mb-2"><i class="fas fa-home"></i>&nbsp;</a>
                    <table class="table table-bordered table-striped" id="list_reqpic">
                        <thead>
                            <tr>
                                <td>Tanggal Request</td>
                                <td>Jam</td>
                                <td>Total Barang</td>
                                <td>Keterangan</td>
                                <td>Status</td>
                                <td>#</td>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($getallreq as $g) : ?>
                                <tr>
                                    <td><?= format_tgl_lahir($g->tgl_transaksi) ?></td>
                                    <td><?= format_jam_24($g->create_at) ?></td>
                                    <td><?= $g->jml_item ?></td>
                                    <td><?= $g->tj_pembelian ?></td>
                                    <td>
                                        <div class="row">
                                            <?php if ($g->status == 'ON PROGRESS') : ?>
                                                <div class="col">
                                                    <a class="btn btn-block btn-secondary btn-sm"><?= $g->status ?></a>
                                                </div>
                                            <?php elseif ($g->status == 'REQUEST ACC') : ?>
                                                <div class="col">
                                                    <a class="btn btn-block btn-warning btn-sm"><?= $g->status ?></a>
                                                </div>
                                            <?php elseif ($g->status == 'BARANG TERSEDIA') : ?>
                                                <div class="col">
                                                    <a class="btn btn-block btn-info btn-sm"><?= $g->status ?></a>
                                                </div>
                                            <?php elseif ($g->status == 'DONE') : ?>
                                                <div class="col">
                                                    <a class="btn btn-block btn-success btn-sm"><?= $g->status ?></a>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td>
                                        <a class="btn btn-block btn-primary btn-sm" href="<?= base_url('reqpic/detreqbarangpic/' . $g->kd_po_nk) ?>">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <!-- END CARD CONTENT -->
            </div>
        </div>
    </div>
</div>
