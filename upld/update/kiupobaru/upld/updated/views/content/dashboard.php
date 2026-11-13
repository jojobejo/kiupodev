<!-- VIEW KARYAWAN -->
<?php if ($this->session->userdata('lv') == '4') : ?>
    <div class="content-wrapper">
        <div class="content-header">
            <div class="container-fluid">
                <?php $this->load->view('content/po/jasa/dashboard_widget'); ?>
                <div class="row mb-2">
                    <div class="col-sm-6">
                        <h1 class="m-0">Dashboard Purchase Order Non Komersil </h1>
                    </div><!-- /.col -->
                </div><!-- /.row -->
                <div class="row">
                    <div class="col-lg-3 col-6">
                        <!-- small box -->
                        <div class="small-box bg-info">
                            <div class="inner">
                                <?php foreach ($all as $a) : ?>
                                    <h3><?= $a->total ?></h3>
                                <?php endforeach; ?>
                                <p>Purchase Order</p>
                            </div>
                            <div class="icon">
                                <i class="fas fa-money-check"></i>
                            </div>
                            <a href="<?= base_url('postatus') ?>" class="small-box-footer">More info <i class="fas fa-arrow-circle-right"></i></a>
                        </div>
                    </div>
                    <!-- ./col -->
                    <div class="col-lg-3 col-6">
                        <!-- small box -->
                        <div class="small-box bg-success">
                            <div class="inner">
                                <?php foreach ($done as $d) : ?>
                                    <h3><?= $d->tdone ?></h3>
                                <?php endforeach; ?>

                                <p>Order Terselesaikan</p>
                            </div>
                            <div class="icon">
                                <i class="fas fa-file-invoice-dollar"></i>
                            </div>
                            <a href="<?= base_url('postatus/done') ?>" class="small-box-footer">More info <i class="fas fa-arrow-circle-right"></i></a>
                        </div>
                    </div>
                    <!-- ./col -->
                    <div class="col-lg-3 col-6">
                        <!-- small box -->
                        <div class="small-box bg-warning">
                            <div class="inner">
                                <?php foreach ($progress as $p) : ?>
                                    <h3><?= $p->tprogress ?></h3>
                                <?php endforeach; ?>

                                <p>Order On Acc Progress</p>
                            </div>
                            <div class="icon">
                                <i class="fas fa-business-time"></i>
                            </div>
                            <a href="<?= base_url('postatus/onprogress') ?>" class="small-box-footer">More info <i class="fas fa-arrow-circle-right"></i></a>
                        </div>
                    </div>
                    <!-- ./col -->
                    <div class="col-lg-3 col-6">
                        <!-- small box -->
                        <div class="small-box bg-danger">
                            <div class="inner">
                                <?php foreach ($reject as $r) : ?>
                                    <h3><?= $r->treject ?></h3>
                                <?php endforeach; ?>

                                <p>Order Ditolak</p>
                            </div>
                            <div class="icon">
                                <i class="ion ion-pie-graph"></i>
                            </div>
                            <a href="<?= base_url('postatus/reject') ?>" class="small-box-footer">More info <i class="fas fa-arrow-circle-right"></i></a>
                        </div>
                    </div>
                    <!-- ./col -->
                </div>
            </div><!-- /.container-fluid -->
        </div>
        <!-- /.content-header -->
    </div>

    <!-- VIEW PURCHASING -->

<?php elseif (is_super_admin() || $this->session->userdata('lv') == '2') : ?>
    <div class="content-wrapper">
        <div class="content-header">
            <div class="container-fluid">
                <?php $this->load->view('content/po/jasa/dashboard_widget'); ?>
                <div class="row mb-2">
                    <div class="col-sm-6">
                        <h1><b style="text-transform:uppercase">Dashboard Purchase Order</b></h1>
                    </div><!-- /.col -->
                </div><!-- /.row -->
                <div class="row">
                    <div class="col-lg-3 col-6">
                        <!-- small box -->
                        <div class="small-box bg-info">
                            <div class="inner">
                                <?php foreach ($all as $a) : ?>
                                    <h3><?= $a->total ?></h3>
                                <?php endforeach; ?>
                                <p>Purchase Order</p>
                            </div>
                            <div class="icon">
                                <i class="fas fa-money-check"></i>
                            </div>
                            <a href="<?= base_url('postatus') ?>" class="small-box-footer">More info <i class="fas fa-arrow-circle-right"></i></a>
                        </div>
                    </div>
                    <!-- ./col -->
                    <div class="col-lg-3 col-6">
                        <!-- small box -->
                        <div class="small-box bg-success">
                            <div class="inner">
                                <?php foreach ($done as $d) : ?>
                                    <h3><?= $d->tdone ?></h3>
                                <?php endforeach; ?>
                                <p>Order Terselesaikan</p>
                            </div>
                            <div class="icon">
                                <i class="fas fa-file-invoice-dollar"></i>
                            </div>
                            <a href="<?= base_url('postatus/done') ?>" class="small-box-footer">More info <i class="fas fa-arrow-circle-right"></i></a>
                        </div>
                    </div>
                    <!-- ./col -->
                    <div class="col-lg-3 col-6">
                        <!-- small box -->
                        <div class="small-box bg-warning">
                            <div class="inner">
                                <?php foreach ($progress as $p) : ?>
                                    <h3><?= $p->tprogress ?></h3>
                                <?php endforeach; ?>

                                <p>Order On Acc Progress</p>
                            </div>
                            <div class="icon">
                                <i class="fas fa-business-time"></i>
                            </div>
                            <a href="<?= base_url('postatus/onprogress') ?>" class="small-box-footer">More info <i class="fas fa-arrow-circle-right"></i></a>
                        </div>
                    </div>
                    <!-- ./col -->
                    <div class="col-lg-3 col-6">
                        <!-- small box -->
                        <div class="small-box bg-danger">
                            <div class="inner">
                                <?php foreach ($reject as $r) : ?>
                                    <h3><?= $r->treject ?></h3>
                                <?php endforeach; ?>
                                <p>Order Ditolak</p>
                            </div>
                            <div class="icon">
                                <i class="ion ion-pie-graph"></i>
                            </div>
                            <a href="<?= base_url('postatus/reject') ?>" class="small-box-footer">More info <i class="fas fa-arrow-circle-right"></i></a>
                        </div>
                    </div>
                    <!-- ./col -->
                </div>
                <div class="col-md">
                    <div class="row mb-2">
                        <div class="col-sm-8">
                            <h1><b style="text-transform:uppercase">restock barang komersil</b></h1>
                        </div><!-- /.col -->
                    </div><!-- /.row -->
                    <div class="row mt-2">
                        <div class="col-lg">
                            <div class="small-box" style="background-color: #f7e591;">
                                <div class="inner">
                                    <?php foreach ($restock as $rstk) : ?>
                                        <h3><?= $rstk->cqty ?> </h3>
                                    <?php endforeach; ?>
                                    <p>Restock Inventory</p>
                                </div>
                                <div class="icon">
                                    <i class="fas fa-boxes"></i>
                                </div>
                                <a href="<?= base_url('') ?>" class="small-box-footer">More info <i class="fas fa-arrow-circle-right"></i></a>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md">
                    <div class="row mb-2">
                        <div class="col-sm-8">
                            <h1><b style="text-transform:uppercase">stok ulang barang persediaan - non komersil</b></h1>
                        </div><!-- /.col -->
                    </div><!-- /.row -->
                    <div class="row mt-2">
                        <div class="col-lg-6 col-6">
                            <div class="small-box" style="background-color: #f7e591;">
                                <div class="inner">
                                    <?php foreach ($restock as $rstk) : ?>
                                        <h3><?= $rstk->cqty ?> </h3>
                                    <?php endforeach; ?>
                                    <p>Restock Inventory</p>
                                </div>
                                <div class="icon">
                                    <i class="fas fa-boxes"></i>
                                </div>
                                <a href="<?= base_url('') ?>" class="small-box-footer">More info <i class="fas fa-arrow-circle-right"></i></a>
                            </div>
                        </div>
                        <div class="col-lg-6 col-6">
                            <div class="small-box" style="background-color: #f7e591;">
                                <div class="inner">
                                    <?php foreach ($reqmrbarag as $req) : ?>
                                        <h3><?= $req->totreq ?> </h3>
                                    <?php endforeach; ?>
                                    <p>Request Master Barang</p>
                                </div>
                                <div class="icon">
                                    <i class="fas fa-boxes"></i>
                                </div>
                                <a href="<?= base_url('vrequestmbarang') ?>" class="small-box-footer">More info <i class="fas fa-arrow-circle-right"></i></a>
                            </div>
                        </div>
                    </div>
                </div>
            </div><!-- /.container-fluid -->
        </div>
        <!-- /.content-header -->
    </div>

    <!-- VIEW KADEP -->

<?php elseif ($this->session->userdata('lv') == '5') : ?>
    <div class="content-wrapper">
        <div class="content-header">
            <div class="container-fluid">
                <?php $this->load->view('content/po/jasa/dashboard_widget'); ?>
                <div class="row mb-2">
                    <div class="col-sm-6">
                        <h1 class="m-0">Dashboard Purchase Order</h1>
                    </div><!-- /.col -->
                </div><!-- /.row -->
                <div class="row">
                    <div class="col-lg-3 col-6">
                        <!-- small box -->
                        <div class="small-box bg-info">
                            <div class="inner">
                                <?php foreach ($all as $a) : ?>
                                    <h3><?= $a->total ?></h3>
                                <?php endforeach; ?>
                                <p>Purchase Order</p>
                            </div>
                            <div class="icon">
                                <i class="fas fa-money-check"></i>
                            </div>
                            <a href="<?= base_url('postatus') ?>" class="small-box-footer">More info <i class="fas fa-arrow-circle-right"></i></a>
                        </div>
                    </div>
                    <!-- ./col -->
                    <div class="col-lg-3 col-6">
                        <!-- small box -->
                        <div class="small-box bg-success">
                            <div class="inner">
                                <?php foreach ($done as $d) : ?>
                                    <h3><?= $d->tdone ?></h3>
                                <?php endforeach; ?>

                                <p>Order Terselesaikan</p>
                            </div>
                            <div class="icon">
                                <i class="fas fa-file-invoice-dollar"></i>
                            </div>
                            <a href="<?= base_url('postatus/done') ?>" class="small-box-footer">More info <i class="fas fa-arrow-circle-right"></i></a>
                        </div>
                    </div>
                    <!-- ./col -->
                    <div class="col-lg-3 col-6">
                        <!-- small box -->
                        <div class="small-box bg-warning">
                            <div class="inner">
                                <?php foreach ($progress as $p) : ?>
                                    <h3><?= $p->tprogress ?></h3>
                                <?php endforeach; ?>

                                <p>Order On Acc Progress</p>
                            </div>
                            <div class="icon">
                                <i class="fas fa-business-time"></i>
                            </div>
                            <a href="<?= base_url('postatus/onprogress') ?>" class="small-box-footer">More info <i class="fas fa-arrow-circle-right"></i></a>
                        </div>
                    </div>
                    <!-- ./col -->
                    <div class="col-lg-3 col-6">
                        <!-- small box -->
                        <div class="small-box bg-danger">
                            <div class="inner">
                                <?php foreach ($reject as $r) : ?>
                                    <h3><?= $r->treject ?></h3>
                                <?php endforeach; ?>

                                <p>Order Ditolak</p>
                            </div>
                            <div class="icon">
                                <i class="ion ion-pie-graph"></i>
                            </div>
                            <a href="<?= base_url('postatus/reject') ?>" class="small-box-footer">More info <i class="fas fa-arrow-circle-right"></i></a>
                        </div>
                    </div>
                    <!-- ./col -->
                </div>
            </div><!-- /.container-fluid -->
        </div>
        <!-- /.content-header -->
    </div>

    <!-- VIEW DIREKTUR -->

<?php elseif (in_array((string) $this->session->userdata('lv'), array('3', '6'), true)) : ?>
    <div class="content-wrapper">
        <div class="content-header">
            <div class="container-fluid">
                <?php $this->load->view('content/po/jasa/dashboard_widget'); ?>
                <div class="row mb-2">
                    <div class="col-sm-6">
                        <h1 class="m-0">Dashboard Purchase Order</h1>
                    </div><!-- /.col -->
                </div><!-- /.row -->
                <div class="row">
                    <div class="col-lg-3 col-6">
                        <!-- small box -->
                        <div class="small-box bg-info">
                            <div class="inner">
                                <?php foreach ($all as $a) : ?>
                                    <h3><?= $a->total ?></h3>
                                <?php endforeach; ?>
                                <p>Purchase Order</p>
                            </div>
                            <div class="icon">
                                <i class="fas fa-money-check"></i>
                            </div>
                            <a href="<?= base_url('postatus') ?>" class="small-box-footer">More info <i class="fas fa-arrow-circle-right"></i></a>
                        </div>
                    </div>
                    <!-- ./col -->
                    <div class="col-lg-3 col-6">
                        <!-- small box -->
                        <div class="small-box bg-success">
                            <div class="inner">
                                <?php foreach ($done as $d) : ?>
                                    <h3><?= $d->tdone ?></h3>
                                <?php endforeach; ?>

                                <p>Order Terselesaikan</p>
                            </div>
                            <div class="icon">
                                <i class="fas fa-file-invoice-dollar"></i>
                            </div>
                            <a href="<?= base_url('postatus/done') ?>" class="small-box-footer">More info <i class="fas fa-arrow-circle-right"></i></a>
                        </div>
                    </div>
                    <!-- ./col -->
                    <div class="col-lg-3 col-6">
                        <!-- small box -->
                        <div class="small-box bg-warning">
                            <div class="inner">
                                <?php foreach ($progress as $p) : ?>
                                    <h3><?= $p->tprogress ?></h3>
                                <?php endforeach; ?>

                                <p>Order On Acc Progress</p>
                            </div>
                            <div class="icon">
                                <i class="fas fa-business-time"></i>
                            </div>
                            <a href="<?= base_url('postatus/onprogress') ?>" class="small-box-footer">More info <i class="fas fa-arrow-circle-right"></i></a>
                        </div>
                    </div>
                    <!-- ./col -->
                    <div class="col-lg-3 col-6">
                        <!-- small box -->
                        <div class="small-box bg-danger">
                            <div class="inner">
                                <?php foreach ($reject as $r) : ?>
                                    <h3><?= $r->treject ?></h3>
                                <?php endforeach; ?>

                                <p>Order Ditolak</p>
                            </div>
                            <div class="icon">
                                <i class="ion ion-pie-graph"></i>
                            </div>
                            <a href="<?= base_url('postatus/reject') ?>" class="small-box-footer">More info <i class="fas fa-arrow-circle-right"></i></a>
                        </div>
                    </div>
                    <!-- ./col -->
                </div>
            </div><!-- /.container-fluid -->
        </div>
        <!-- /.content-header -->
    </div>


<?php endif; ?>
