<div class="content-wrapper">
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0">Note Setting</h1>
                </div><!-- /.col -->
            </div><!-- /.row -->

            <?php $this->load->view('content/setting/modal/modalnotetemplate') ?>

            <div class=" m-2">
                <a class="btn btn-block btn-success btn-sm " data-toggle="modal" data-target="#modalAddItem">
                    <i class="fas fa-check-double"></i>
                    Tambah Template Note
                </a>
            </div>
            <table class="table table-bordered table-striped" id="list_suplier">
                <thead>
                    <tr>
                        <td>Nama Format Template</td>
                        <td style="text-align: center;">Aksi</td>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <?php foreach ($note as $n) : ?>
                            <td><?= $n->nama_note ?></td>
                            <td>
                                <div class="row">
                                    <div class="col-md">
                                        <a class="btn btn-block btn-primary btn-sm" href="<?= base_url('notetemplate/') . $n->kd_nt_template ?>">
                                            <i class="fas fa-eye-alt"></i>
                                            Detail Template
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