<?php foreach ($lbarangdet as $l) : ?>
    <div class="content-wrapper">
        <div class="content-header">
            <div class="container-fluid">
                <div class="row mb-2">
                    <h3>
                        <b style="text-transform:uppercase" class="ml-3"><?= $l->nama_barang ?></b>
                    </h3>
                </div><!-- /.row -->
                <div class="card">
                    <div class="card-body">
                        <div class="row">
                            <div class="col">
                                <label for="naSupp" class="">STOK RULES : </label>
                                <input type="text" id="naCus" name="naSupp" style="max-width: 550px;" value="<?= $l->qty_min ?>" class="form-control" readonly>
                            </div>
                            <div class="col">
                                <label for="naSupp" class="">STOK TERSEDIA : </label>
                                <input type="text" id="naCus" name="naSupp" style="max-width: 550px;" value="<?= $l->qty_all ?>" class="form-control" readonly>
                            </div>
                            <div class="col">
                                <label for="naSupp" class="">SATUAN QTY : </label>
                                <input type="text" id="naCus" name="naSupp" style="max-width: 550px;" value="<?= $l->nama_barang ?>" class="form-control" readonly>
                            </div>
                            <div class="col">
                                <label for="naSupp" class="">NAMA SUPLIER : </label>
                                <input type="text" id="naCus" name="naSupp" style="max-width: 550px;" value="<?= $l->nama_suplier ?>" class="form-control" readonly>
                            </div>
                        </div>
                        <a href="#" class="btn btn-block btn-primary mt-2">Edit data produk</a>
                    </div>
                </div>
                <div class="card">
                    <div class="card-body">
                        <div class="col mt-2">
                            <h3>
                                <b style="text-transform:uppercase">Histori Transaksi - PO</b>
                            </h3>
                        </div>
                        <div class="col">
                            <table class="table table-bordered">
                                <thead style="background-color: #212529; color:white;">
                                    <tr>
                                        <td>No Faktur</td>
                                        <td>Tanggal Transaksi</td>
                                        <td>Qty</td>
                                        <td>PIC</td>
                                        <td>#</td>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($lbarangdet as $l) : ?>
                                        <tr>
                                            <td><?= $l->no_po ?></td>
                                            <td><?= format_indo($l->tgl_transaksi) ?></td>
                                            <td><?= $l->tr_qty ?> - (<?= $l->nm_satuan ?>)</td>
                                            <td><?= $l->nama_user ?></td>
                                            <td>
                                                <a href="<?= base_url('detailPO/') . $l->kd_po_nk ?>" class="btn btn-block btn-info"><i class="fas fa-eye"></i></a>
                                            </td>

                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div><!-- /.container-fluid -->
        </div>
        <!-- /.content-header -->
    </div>
<?php endforeach; ?>