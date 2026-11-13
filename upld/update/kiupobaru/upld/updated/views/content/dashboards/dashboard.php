<div class="content-wrapper">
    <div class="content-header">
        <div class="container-fluid">
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