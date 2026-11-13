<div class="content-wrapper">
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0">DATA HISTORI PO</h1>
                </div><!-- /.col -->
            </div><!-- /.row -->
            <div class="row mb-2">
                <div class="col-sm-6">
                    <a href="<?= base_url('postatusnk') ?>" class="btn btn-primary"><i class="fas fa-hand-point-left"></i>&nbsp; HOME &nbsp;</a>
                </div>
            </div>
            <!-- NEW LINE -->
            <div class="card">
                <div class="card-body">
                    <?php echo form_open_multipart('srcexpdone') ?>
                    <div class="row">
                        <div class="col-sm-6">
                            <div class="form-group">
                                <label>Tanggal Start :</label>
                                <input type="date" class="form-control" placeholder="Tanggal Transaksi" value="" name="tglstart" id="tglstart">
                            </div>
                        </div><!-- /.col -->
                        <div class="col-sm-6">
                            <div class="form-group">
                                <label>Tanggal End :</label>
                                <input type="date" class="form-control" placeholder="Tanggal Transaksi" value="" name="tglend" id="tglend">
                            </div>
                        </div><!-- /.col -->
                    </div><!-- /.row -->
                    <div class="mb-2">
                        <button type="submit" class="btn btn-primary btn-block">
                            <i class="fas fa-search"></i>
                            Cari Data
                        </button>
                    </div>
                </div>
            </div>

            <!-- KADEP VIEW -->
            <table class="table table-bordered table-striped" id="tbhistori">
                <thead>
                    <tr>
                        <td>No</td>
                        <td>Tanggal PO</td>
                        <td>Departement</td>
                        <td>Tujuan Pembelian</td>
                        <td>Status Order</td>
                        <td>#</td>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $no = 1;
                    foreach ($vcari as $h) :
                    ?>
                        <tr>
                            <td><?= $no++; ?></td>
                            <td><?= $h->tgl ?></td>
                            <td><?= $h->dep ?></td>
                            <td><?= $h->ket ?></td>
                            <td>
                                <a class="btn btn-block btn-success btn-sm">
                                    <i class="fas fa-thumbs-up"></i>&nbsp;
                                    <?= $h->sts ?>
                                </a>
                            </td>
                            <td>
                                <a class="btn btn-block btn-primary btn-sm" href="<?= base_url('detailponk/') . $h->kdponk ?>">
                                    <i class="fas fa-eye"></i>
                                    Detail
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <!-- /.content-header -->
    </div>
</div>