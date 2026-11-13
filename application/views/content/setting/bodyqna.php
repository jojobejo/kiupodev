<?php $this->load->view('content/setting/modal/modalqna.php') ?>
<div class="content-wrapper">
    <div class="content-header">
        <div class="container-fluid">
            <?php foreach ($countqna as $ctot) :
                $tot_asw = $ctot->tot_asw;
                $tot_question = $ctot->tot_question;
            ?>
                <!-- REVIEW ON PROGRESS  -->
                <?php foreach ($judul as $j) :
                    if ($j->type == '2') {
                        $type = 'NON KOMERSIL';
                    } else {
                        $type = 'KOMERSIL';
                    }
                ?>
                    <h1 class="mb-2 mt-2"><b style="text-transform:uppercase">MODULE(<?= $type ?>) - <?= $j->nm_module ?></b></h1>
                <?php endforeach; ?>
                <div class="card">
                    <div class="card-body">
                        <?php foreach ($getanswer as $gtansw) : ?>
                            <h1 class="mb-4 ml-2">
                                <li><?= $gtansw->question ?></li>
                            </h1>
                            <div class="form-group">
                                <div class="row ml-5 mb-4">
                                    <label class="control-label text-left" for="kd_user"><span class="required"></span></label>
                                    <div class="col">
                                        <textarea name="asnwer[]" id="asnwer" class="form-control" readonly><?= $gtansw->isi_review ?></textarea>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <div class="card">
                    <div class="card-body">
                        <?php foreach ($question as $q) : ?>
                            <h1 class="mb-4 ml-2">
                                <li><?= $q->qeustion ?></li>
                            </h1>
                            <?php echo form_open_multipart('reviewanswer'); ?>
                            <div class="form-group">
                                <div class="row ml-5 mb-4">
                                    <label class="control-label text-left" for="kd_user"><span class="required"></span></label>
                                    <div class="col">
                                        <textarea name="asnwer[]" id="asnwer" class="form-control"></textarea>
                                    </div>
                                    <input type="text" name="kodem[]" id="kodem" class="form-control" value="<?= $q->kode ?>" hidden>
                                    <input type="text" name="koder[]" id="kodem" class="form-control" value="<?= $q->kode_r ?>" hidden>
                                    <input type="text" name="user" id="kodem" class="form-control" value="<?= $this->session->userdata('kode') ?>" hidden>
                                </div>
                            </div>
                        <?php endforeach; ?>
                        <h1 class="mb-4 ml-2">
                            <li>Berikan penilaian anda pada module ini</li>
                        </h1>
                        <?php ?>
                        <div class="row ml-5 mb-4">
                            <div class="col">
                                <select name="nilai" id="nilai" class="form-control">
                                    <?php foreach ($getnilai as $n) : ?>
                                        <option value="<?= $n->nilai ?>" disabled selected><?= $n->nilai ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary btn-block">Simpan</button>
                    </div>
                </div>
        </div><!-- /.container-fluid -->
    <?php endforeach; ?>
    </div>

    <!-- /.content-header -->
</div>