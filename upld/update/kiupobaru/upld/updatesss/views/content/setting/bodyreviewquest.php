<?php $this->load->view('content/setting/modal/modaladdquestion.php') ?>
<div class="content-wrapper">
    <div class="content-header">
        <div class="container-fluid">
            <?php foreach ($judul as $j) :
                if ($j->type == '2') {
                    $type = 'NON KOMERSIL';
                } else {
                    $type = 'KOMERSIL';
                }
            ?>
                <h1 class="mb-2 mt-2"><b style="text-transform:uppercase">MODULE(<?= $type ?>) - <?= $j->nm_module ?></b></h1>
            <?php endforeach; ?>
            <div class="row mt-2 mb-2">
                <div class="col">
                    <a href="#" class="btn btn-block btn-info" data-toggle="modal" data-target="#modalAddItem">Tambah Pertanyaan</a>
                </div>
            </div>
            <div class="card">
                <div class="card-body">
                    <?php foreach ($question as $q) : ?>
                        <h1 class="mb-2 ml-2">
                            <li><?= $q->qeustion ?></li>
                        </h1>
                        <table class="table table-bordered table-striped mb-2">
                            <thead style="background-color: #212529; color:white;">
                                <tr>
                                    <td>Isi Review</td>
                                    <td>Nilai</td>
                                    <td>#</td>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                </tr>
                            </tbody>
                        </table>
                    <?php endforeach; ?>
                </div>
            </div>
        </div><!-- /.container-fluid -->
    </div>

    <!-- /.content-header -->
</div>