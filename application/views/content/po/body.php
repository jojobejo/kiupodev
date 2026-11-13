<div class="content-wrapper">
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0">
                    </h1>
                </div><!-- /.col -->
            </div><!-- /.row -->

            <!-- MODAL ADD -->
            <div class="modal fade" id="add_suplier">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h4 class="modal-title">Add Suplier Baru</h4>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="modal-body">
                            <?php echo form_open_multipart('add_suplier'); ?>
                            <div class="form-group">
                                <div class="row">
                                    <label class="col-sm-3 control-label text-right" for="kd_user">Kode Suplier<span class="required">*</span></label>
                                    <div class="col-sm-8">
                                        <input class="form-control" type="text" id="kd_isi" name="kd_isi" value="" />
                                    </div>
                                </div>
                            </div>
                            <div class="form-group">
                                <div class="row">
                                    <label class="col-sm-3 control-label text-right" for="kd_user">Nama Suplier<span class="required">*</span></label>
                                    <div class="col-sm-8"><input class="form-control" type="text" id="nm_isi" name="nm_isi" value="" /></div>
                                </div>
                            </div>
                            <div class="form-group">
                                <div class="row">
                                    <label class="col-sm-3 control-label text-right" for="kd_user">Alamat Suplier<span class="required">*</span></label>
                                    <div class="col-sm-8"><textarea name="almt_isi" id="almt_isi" class="form-control"></textarea></div>
                                </div>
                            </div>
                            <div class="form-group">
                                <div class="row">
                                    <label class="col-sm-3 control-label text-right" for="kd_user">No Telpon<span class="required">*</span></label>
                                    <div class="col-sm-8"><input class="form-control" type="text" id="tlp_isi" name="tlp_isi" value="" /></div>
                                </div>
                            </div>
                            <div class="form-group">
                                <div class="row">
                                    <label class="col-sm-3 control-label text-right" for="kd_user">No Fax<span class="required">*</span></label>
                                    <div class="col-sm-8"><input class="form-control" type="text" id="fax_isi" name="fax_isi" value="" /></div>
                                </div>
                            </div>
                            <div class="form-group">
                                <div class="row">
                                    <label class="col-sm-3 control-label text-right" for="kd_user">Email<span class="required">*</span></label>
                                    <div class="col-sm-8"><input class="form-control" type="text" id="em_isi" name="em_isi" value="" /></div>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer justify-content-between">
                            <button type="button" class="btn btn-default" data-dismiss="modal">Batal</button>
                            <button type="submit" class="btn btn-primary">Simpan</button>
                        </div>
                        </form>
                    </div>
                    <!-- /.modal-content -->
                </div>
                <!-- /.modal-dialog -->
            </div>

            <table class="table table-bordered table-striped" id="list_suplier">
                <thead>
                    <div class="row">
                        <h3>Pilih Suplier</h3>
                        <a class="btn btn-info btn-md ml-3" data-toggle="modal" data-target="#add_suplier">
                            <i class="fas fa-plus"></i>
                            Add Suplier Baru
                        </a>
                    </div>
                    <tr>
                        <td>Nama Suplier</td>
                        <td>Aksi</td>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <?php foreach ($suplier as $s) : ?>
                            <td><?= $s->nama_suplier ?></td>
                            <td>
                                <a href="<?= base_url('purchase/sup/') . $s->kd_suplier ?>" class="btn btn-block btn-success btn-sm ">
                                    <i class="fas fa-check-double"></i>
                                    Pilih Suplier
                                </a>
                            </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div><!-- /.container-fluid -->

    </div>

    <!-- /.content-header -->
</div>