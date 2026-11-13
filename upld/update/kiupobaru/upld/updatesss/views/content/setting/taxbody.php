<div class="content-wrapper">
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0">Tax Setting</h1>
                </div><!-- /.col -->
            </div><!-- /.row -->
            <?php $this->load->view('content/setting/modal/modaltax') ?>
            <div class=" m-2">
                <a class="btn btn-block btn-success btn-sm " data-toggle="modal" data-target="#modalAddItem">
                    <i class="fas fa-check-double"></i>
                    Tambah Satuan Pajak
                </a>
            </div>
            <table class="table table-bordered table-striped" id="list_suplier">
                <thead>
                    <tr>
                        <td>Satuan Tax</td>
                        <td>Aksi</td>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <?php foreach ($tax as $t) : ?>
                            <td><?= $t->nm_tax ?> % </td>
                            <td>
                                <div class="row">
                                    <div class="col-md">
                                        <a class="btn btn-block btn-warning btn-sm" data-toggle="modal" data-target="#modalEdit<?= $t->id_tax ?>">
                                            <i class="fas fa-check-double"></i>
                                            Edit Satuan Pajak
                                        </a>
                                    </div>
                                    <div class="col-md">
                                        <a class="btn btn-block btn-danger btn-sm" data-toggle="modal" data-target="#hapusPajak<?= $t->id_tax ?>">
                                            <i class="fas fa-check-double"></i>
                                            Hapus Pajak
                                        </a>
                                    </div>
                                </div>
                            </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div><!-- /.container-fluid -->
    </div>
    <!-- /.content-header -->
</div>