<div class="content-wrapper">
    <div class="content-header">
        <div class="container-fluid">
            <?php if ($this->session->flashdata('error')) : ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?= htmlspecialchars($this->session->flashdata('error'), ENT_QUOTES, 'UTF-8') ?>
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
            <?php endif; ?>
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0">
                    </h1>
                </div><!-- /.col -->
            </div><!-- /.row -->
            <div style="display: flex; text-align: center;">
                <?php foreach ($status as $b) : ?>
                    <a href="<?= base_url('detailPO/') . $b->kd_po ?>">
                        <i class="fa fa-arrow-left  ml-4 mr-4 mt-2"></i>
                    </a>
                    <h3>Kembali</h3>
                <?php endforeach; ?>
            </div>

            <?php $this->load->view('content/postatus/modalAddBarang') ?>

            <table class="table table-bordered table-striped" id="list_suplier">
                <thead>
                    <tr>
                        <td>Nama Barang</td>
                        <td>Isi</td>
                        <td>Kemasan</td>
                        <td>Aksi</td>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($barang as $s) : ?>
                        <tr>
                            <td><?= htmlspecialchars($s->nama_barang, ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= isset($s->isi) ? htmlspecialchars($s->isi, ENT_QUOTES, 'UTF-8') : '-' ?></td>
                            <td><?= isset($s->kemasan) ? htmlspecialchars($s->kemasan, ENT_QUOTES, 'UTF-8') : '-' ?></td>
                            <td>
                                <div class="row">
                                    <div class="col-md">
                                        <a class="btn btn-block btn-success btn-sm" data-toggle="modal" data-target="#modalAddItem<?= $s->id_barang ?>">
                                            <i class="fas fa-check-double"></i>
                                            Tambah Barang Ke Chart
                                        </a>
                                    </div>
                                    <div class="col-md">
                                        <a class="btn btn-block btn-primary btn-sm" data-toggle="modal" data-target="#modalAddBonus<?= $s->id_barang ?>">
                                            <i class="fas fa-gift"></i>
                                            Barang Bonus
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
