<a href="" class="btn btn-sm btn-success"><i class="fas fa-check"></i></a>
<?php if ($this->session->userdata('lv') == '4' && !is_super_admin()) : ?>
    <div class="content-wrapper">
        <div class="content-header">
            <div class="container-fluid">
                <div class="card">
                    <div class="card-body">
                        <?php if ($this->session->flashdata('error')) : ?>
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <?= htmlspecialchars($this->session->flashdata('error'), ENT_QUOTES, 'UTF-8') ?>
                                <button type="button" class="close" data-dismiss="alert" aria-label="Tutup"><span aria-hidden="true">&times;</span></button>
                            </div>
                        <?php endif; ?>
                        <h3>List Request Status</h3>
                        <?php if ($countreq == '0') : ?>
                            <a href="<?= base_url('listbarangready') ?>" class="btn btn-sm btn-primary mb-2"><i class="fas fa-plus"></i>&nbsp; New Request </a>
                            <a href="<?= base_url('historireqpic') ?>" class="btn btn-sm btn-success mb-2"><i class="fas fa-check"></i>&nbsp;REQ-DONE</a>
                            <a href="<?= base_url('requestpendings') ?>" class="btn btn-sm btn-warning mb-2"><i class="fas fa-pause"></i>&nbsp; REQUEST PENDING</a>
                        <?php else : ?>
                            <a href="<?= base_url('historireqpic') ?>" class="btn btn-sm btn-success mb-2"><i class="fas fa-check"></i>&nbsp;REQ-DONE</a>
                        <?php endif; ?>
                        <table class="table table-bordered table-striped" id="list_reqpic">
                            <thead>
                                <tr>
                                    <td>Tanggal Request</td>
                                    <td>Jam</td>
                                    <td>Total Barang</td>
                                    <td>Keterangan</td>
                                    <td>Status</td>
                                    <td>#</td>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($getallreq as $g) : ?>
                                    <tr>
                                        <td><?= format_tgl_lahir($g->tgl_transaksi) ?></td>
                                        <td><?= format_jam_24($g->create_at) ?></td>
                                        <td><?= $g->jml_item ?></td>
                                        <td><?= $g->tj_pembelian ?></td>
                                        <td>
                                            <div class="row">
                                                <?php if ($g->status == 'ON PROGRESS - BELUM ACC') : ?>
                                                    <div class="col">
                                                        <a class="btn btn-block btn-warning btn-sm">ON PROGRESS - BELUM ACC KADEP</a>
                                                    </div>
                                                <?php elseif ($g->status == 'ON PROGRESS' || $g->status == 'ON PROGRESS - ACC KADEP') : ?>
                                                    <div class="col">
                                                        <a class="btn btn-block btn-secondary btn-sm"><?= $g->status ?></a>
                                                    </div>
                                                <?php elseif ($g->status == 'MENUNGGU ACC KADEP') : ?>
                                                    <div class="col">
                                                        <a class="btn btn-block btn-warning btn-sm"><?= $g->status ?></a>
                                                    </div>
                                                <?php elseif ($g->status == 'REQUEST ACC') : ?>
                                                    <div class="col">
                                                        <a class="btn btn-block btn-warning btn-sm"><?= $g->status ?></a>
                                                    </div>
                                                <?php elseif ($g->status == 'BARANG TERSEDIA') : ?>
                                                    <div class="col">
                                                        <a class="btn btn-block btn-info btn-sm"><?= $g->status ?></a>
                                                    </div>
                                                <?php elseif ($g->status == 'MENUNGGU ACC PENGAMBILAN') : ?>
                                                    <div class="col">
                                                        <a class="btn btn-block btn-warning btn-sm"><?= $g->status ?></a>
                                                    </div>
                                                <?php elseif ($g->status == 'MENUNGGU PENYERAHAN BARANG') : ?>
                                                    <div class="col">
                                                        <a class="btn btn-block btn-secondary btn-sm"><?= $g->status ?></a>
                                                    </div>
                                                <?php elseif ($g->status == 'DONE') : ?>
                                                    <div class="col">
                                                        <a class="btn btn-block btn-success btn-sm"><?= $g->status ?></a>
                                                    </div>
                                                <?php elseif ($g->status == 'PO REVISI') : ?>
                                                    <div class="col">
                                                        <a class="btn btn-block btn-warning btn-sm"><b><?= $g->status ?></b></a>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="row">
                                                <div class="col">
                                                    <a class="btn btn-block btn-primary btn-sm" href="<?= base_url('reqpic/detreqbarangpic/' . $g->kd_po_nk) ?>">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                </div>
                                                <div class="col">
                                                    <a class="btn btn-block btn-danger btn-sm" href="<?= base_url('reqpic/requestpending/' . $g->kd_po_nk) ?>">
                                                        <i class="fas fa-times-circle"></i>
                                                    </a>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <!-- END CARD CONTENT -->
                </div>

                <?php if ($countreq == '1') : ?>
                    <?php $this->load->view('content/po/Reqpic/modalreqedit.php') ?>
                    <div class="card">
                        <div class="card-body">
                            <h3>Draft Request Barang</h3>
                            <div class="row mb-2">
                                <div class="col">
                                    <a href="<?= base_url('listbarangready') ?>" class="btn btn-sm btn-primary"><i class="fas fa-plus"></i>&nbsp; Tambah Barang </a>
                                </div>
                            </div>
                            <?php echo form_open_multipart('addnewreq/' . $this->session->userdata('kode'), array('id' => 'draft-request-form')); ?>
                            <div class="col-lg-9 px-0 mb-4">
                                <div class="form-group row align-items-center mb-3">
                                    <label for="intj" class="col-md-3 col-form-label font-weight-bold">Tujuan Request</label>
                                    <div class="col-md-9">
                                        <input type="text" id="intj" name="intj" class="form-control" placeholder="Input tujuan pengajuan" required>
                                    </div>
                                </div>
                                <div class="form-group row align-items-start mb-0">
                                    <label for="dokumen_pendukung" class="col-md-3 col-form-label font-weight-bold">Dokumen Pendukung</label>
                                    <div class="col-md-9">
                                        <input type="file" id="dokumen_pendukung" name="dokumen_pendukung[]" class="form-control" multiple accept=".jpg,.jpeg,.png,.pdf,.txt,.xls,.xlsx">
                                        <small class="form-text text-muted">Opsional. Format: JPG, JPEG, PNG, PDF, TXT, XLS, XLSX. Maksimal 10 MB per file.</small>
                                    </div>
                                </div>
                            </div>

                            <table class="table table-bordered table-striped" id="list_reqpic">
                                <thead>
                                    <tr>
                                        <td>Nama Barang</td>
                                        <td>Deskripsi</td>
                                        <td>Keterangan</td>
                                        <td>QTY</td>
                                        <td>Satuan</td>
                                        <td>#</td>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($tmpreq as $t) : ?>
                                        <tr>
                                            <td><?= $t->nama_barang ?></td>
                                            <td><?= $t->descnk ?></td>
                                            <td><?= $t->keterangan ?></td>
                                            <td><?= $t->qty ?></td>
                                            <td><?= $t->nm_satuan ?></td>
                                            <td>
                                                <div class="row">
                                                    <div class="col">
                                                        <a class="btn btn-block btn-warning btn-sm" data-toggle="modal" data-target="#edit<?= $t->id_tmp_nk ?>">
                                                            <i class="fas fa-pencil-alt"></i>
                                                        </a>
                                                    </div>
                                                    <div class="col">
                                                        <a class="btn btn-block btn-danger btn-sm" data-toggle="modal" data-target="#hapus<?= $t->id_tmp_nk ?>">
                                                            <i class="fas fa-times-circle"></i>
                                                        </a>
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>
                                </tbody>
                            <?php endforeach; ?>
                            <tfoot>
                                <tr>
                                    <td colspan="6">
                                        <input class="form-control" type="text" id="kdponk" name="kdponk" value="<?= $generatekd ?>" readonly hidden />
                                        <input class="form-control" type="text" id="totbr" name="totbr" value="<?= $jumlahbr ?>" readonly hidden />
                                        <button type="submit" class="btn btn-block btn-primary btn-sm"><i class="fas fa-cloud-upload-alt"></i> AJUKAN REQUEST</button>
                                        </form>
                                    </td>
                                </tr>
                            </tfoot>
                            </table>

                            <div class="modal fade" id="confirmSubmitWithoutDocument" tabindex="-1" role="dialog" aria-labelledby="confirmSubmitWithoutDocumentTitle" aria-hidden="true">
                                <div class="modal-dialog" role="document">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title" id="confirmSubmitWithoutDocumentTitle">Lanjutkan tanpa dokumen pendukung?</h5>
                                            <button type="button" class="close" data-dismiss="modal" aria-label="Tutup"><span aria-hidden="true">&times;</span></button>
                                        </div>
                                        <div class="modal-body">Dokumen pendukung belum dipilih. Apakah Anda yakin ingin tetap mengajukan request?</div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Tidak, kembali</button>
                                            <button type="button" class="btn btn-primary" id="confirm-submit-draft-request">Ya, lanjutkan</button>
                                        </div>
                                    </div>
                                </div>
                            </div>

                        </div>
                        <!-- END CARD CONTENT -->
                    </div>
                <?php else : ?>
                <?php endif; ?>
                <!-- END CONTAINER CONTENT -->
            </div>
        </div>
        <!-- END VIEW CONTENT -->
    </div>

<?php elseif ($this->session->userdata('lv')  == '2' || is_super_admin()) : ?>
    <div class="content-wrapper">
        <div class="content-header">
            <div class="container-fluid">
                <div class="card">
                    <div class="card-body">
                        <div class="row mb-2">
                            <div class="col-sm-3">
                                <h1 class="m-0">List Request PIC</h1>
                            </div>
                            <div class="col-sm-1 mb-2">
                                <a href="<?= base_url('reqpic') ?>" class="btn btn-md btn-primary"><i class="fas fa-home"></i></a>
                            </div>
                            <div class="col-sm mb-2">
                                <a href="<?= base_url('reqpic/admrestock') ?>" class="btn btn-md btn-primary btn-block"><b>Re-Stock</b></a>
                            </div>
                            <div class="col-sm mb-2">
                                <a href="<?= base_url('promosi_seed') ?>" class="btn btn-md btn-success btn-block"><b>Promosi Seed</b></a>
                            </div>
                            <div class="col-sm mb-2">
                                <a href="<?= base_url('promosi_cp') ?>" class="btn btn-md btn-info btn-block"><b>Promosi CP</b></a>
                            </div>
                            <div class="col-sm mb-2">
                                <a href="<?= base_url('reqpicaccreq') ?>" class="btn btn-md btn-warning btn-block"><b>REQUEST ACC</b></a>
                            </div>
                            <div class="col-sm mb-2">
                                <a href="<?= base_url('index_brsedia') ?>" class="btn btn-md btn-info btn-block"><b>BARANG TERSEDIA</b></a>
                            </div>
                            <div class="col-sm mb-2">
                                <a href="<?= base_url('reqpicpickup') ?>" class="btn btn-md btn-secondary btn-block"><b>PENGAMBILAN</b></a>
                            </div>
                            <div class="col-sm mb-2">
                                <a href="<?= base_url('index_done') ?>" class="btn btn-md btn-success btn-block"><b>DONE</b></a>
                            </div>

                        </div> <!-- END ROW -->
                        <table class="table table-bordered" id="list_reqpic">
                            <thead class="table-dark">
                                <tr>
                                    <td>Nama Pengaju</td>
                                    <td>Departemen</td>
                                    <td>Tanggal Transaksi</td>
                                    <td>Jam</td>
                                    <td>Tujuan Pembelian</td>
                                    <td>Status</td>
                                    <td>#</td>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($getlistpic as $g) : ?>
                                    <tr>
                                        <td><?= $g->nm_user ?></td>
                                        <td><?= $g->departemen ?></td>
                                        <td><?= format_tgl_lahir($g->tgl_transaksi) ?></td>
                                        <td><?= format_jam_24($g->create_at) ?></td>
                                        <td><?= $g->tj_pembelian ?></td>
                                        <?php if ($g->status == 'ON PROGRESS - BELUM ACC') : ?>
                                            <td><a class="btn btn-block btn-warning btn-sm"><b>ON PROGRESS - BELUM ACC KADEP</b></a></td>
                                        <?php elseif ($g->status == 'ON PROGRESS - ACC KADEP') : ?>
                                            <td><a class="btn btn-block btn-primary btn-sm"><b>ON PROGRESS - ACC KADEP</b></a></td>
                                        <?php elseif ($g->status == 'ON PROGRESS') : ?>
                                            <td><a class="btn btn-block btn-warning btn-sm"><b><?= $g->status ?></b></a></td>
                                        <?php elseif ($g->status == 'PO REVISI') : ?>
                                            <td><a class="btn btn-block btn-info btn-sm"><b><?= $g->status ?></b></a></td>
                                        <?php endif; ?>
                                        <td>
                                            <div class="row">
                                                <div class="col">
                                                    <a class="btn btn-block btn-primary btn-sm" href="<?= base_url('reqpic/detreqbarangpic/' . $g->kd_po_nk) ?>"><i class="fas fa-eye"></i></a>
                                                </div>
                                            </div>
                                        </td>

                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>

                    </div> <!-- END CARD BODY -->
                </div> <!-- END CARD -->
            </div>
        </div>
    </div>
<?php endif; ?>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var draftForm = $('#draft-request-form');
    if (!draftForm.length) {
        return;
    }

    draftForm.on('submit', function (event) {
        if (draftForm.data('confirmed-without-document')) {
            return;
        }
        var fileInput = document.getElementById('dokumen_pendukung');
        if (fileInput && fileInput.files && fileInput.files.length === 0) {
            event.preventDefault();
            $('#confirmSubmitWithoutDocument').modal('show');
        }
    });

    $('#confirm-submit-draft-request').on('click', function () {
        draftForm.data('confirmed-without-document', true);
        $('#confirmSubmitWithoutDocument').modal('hide');
        draftForm.trigger('submit');
    });
});
</script>
