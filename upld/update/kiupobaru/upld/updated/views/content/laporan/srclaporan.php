<div class="content-wrapper">
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0">Laporan Pembelian</h1>
                </div><!-- /.col -->
            </div><!-- /.row -->
        </div><!-- /.container-fluid -->
    </div>
    <!-- /.content-header -->

    <section class="content">
        <div class="container-fluid">

            <div class="card">
                <div class="card-body">
                    <?php echo form_open_multipart('srclapbeli'); ?>
                    <div class="row">
                        <div class="col-sm-6">
                            <!-- text input -->
                            <div class="form-group">
                                <label>Tanggal Start :</label>
                                <input type="date" class="form-control" placeholder="Tanggal Transaksi" value="" name="tglstart" id="tglstart">
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="form-group">
                                <label>Tanggal End :</label>
                                <input type="date" class="form-control" placeholder="Tanggal Transaksi" value="" name="tglend" id="tglend">
                            </div>
                        </div>
                    </div>
                    <div>
                        <button type="submit" class="btn btn-primary btn-block">
                            <i class="fas fa-search"></i>
                            Cari Data
                        </button>
                    </div>
                    </form>
                </div>
            </div>
            <div class="card">
                <div class="card-header">
                    <h4>Laporan Pembelian Tanggal : <?= shortdate_indo($vartgl1) ?> &nbsp; - &nbsp; <?= shortdate_indo($vartgl2) ?> </h4>
                </div>
                <div class="card-body">
                    <a class="btn btn-success ml-2" href="<?= base_url('export_laporan_pembelian_nk') ?>">
                        Export Excel
                    </a>
                    <div class="col mt-2">

                        <table class="table table-bordered">
                            <thead>
                                <tr class="bg-gray color-palette">
                                    <!-- <td>status</td>
                                    <td>Kode PO</td> -->
                                    <td>NOPO</td>
                                    <td>Tanggal Transaksi</td>
                                    <td>PIC</td>
                                    <td>Departemen</td>
                                    <td>Nama Barang</td>
                                    <td>Deskripsi</td>
                                    <td>Qty</td>
                                    <td>Harga Satuan</td>
                                    <td>Total Harga</td>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($vcari as $v) : ?>
                                    <tr>
                                        <!-- <td><?= $v->status ?></td>
                                        <td><?= $v->kd_po_nk ?></td> -->
                                        <td><?= $v->nopo ?></td>
                                        <td><?= shortdate_indo($v->tgl_transaksi) ?></td>
                                        <td><?= $v->nama_user ?></td>
                                        <td><?= $v->departement ?></td>
                                        <td><?= $v->nama_barang ?></td>
                                        <td><?= $v->deskripsi ?></td>
                                        <td><?= $v->qty ?></td>
                                        <td>Rp. <?= number_format($v->hrg_satuan) ?></td>
                                        <td>Rp. <?= number_format($v->total_harga) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>



        </div>
    </section>
</div>