<div class="content-wrapper">
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2 ml-2">
                <div class="col-sm-0">

                </div><!-- /.col -->
            </div><!-- /.row -->

            <!-- VIEW ADMIN PURCHASING -->
            <?php $this->load->view('content/stock/nonkomersil/modal/modaladdporestock.php') ?>
            <div class="card">
                <div class="card-body">
                    <h1 class="m-0">
                        <b style="text-transform:uppercase">List Stock Kosong</b>
                    </h1>
                    <table class="table table-bordered" id="list_stocknonkomersil">
                        <thead style="background-color: #212529; color:white;">
                            <tr>
                                <td>Kode Barang</td>
                                <td>Nama Barang</td>
                                <td>Stock</td>
                                <td>Satuan</td>
                                <td>#</td>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($vstock as $v) : ?>
                                <tr>
                                    <td><?= $v->kdbarang ?></td>
                                    <td><?= $v->nmbarang ?></td>
                                    <td><?= $v->qtyready ?></td>
                                    <td><?= $v->satuan ?></td>
                                    <td>
                                        <a href="" class="btn btn-block btn-info" data-toggle="modal" data-target="#adddraft<?= $v->kdbarangsys ?>"><i class="fas fa-plus-circle"></i></a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="card">
                <div class="card-body">
                    <h1 class="m-0 mb-2">
                        <b style="text-transform:uppercase">Draft Restock</b>
                    </h1>
                    <table class="table table-bordered">
                        <thead style="background-color: #212529; color:white;">
                            <tr>
                                <td>Kode Barang</td>
                                <td>Nama Barang</td>
                                <td>Stock</td>
                                <td>Satuan</td>
                                <td>#</td>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($draftpo as $d) : ?>
                                <tr>
                                    <td><?= $d->kd_barang ?></td>
                                    <td><?= $d->nama_barang ?></td>
                                    <td><?= $d->qty ?></td>
                                    <td><?= $d->nm_satuan ?></td>
                                    <td>
                                        <div class="row">
                                            <div class="col">
                                                <a href="" class="btn btn-block btn-warning"><i class="fas fa-pencil-alt"></i></a>
                                            </div>
                                            <div class="col">
                                                <a href="" class="btn btn-block btn-danger"><i class="fas fa-trash-alt"></i></a>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div><!-- /.container-fluid -->
    </div>

    <!-- /.content-header -->
</div>