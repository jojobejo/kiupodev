<?php foreach ($tmpreq as $l) : ?>
    <div class="modal fade" id="edit<?= $l->id_tmp_nk ?>">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title">Add Item Request - <span style="text-transform:uppercase"><b><?= $l->nama_barang ?></b></span></h4>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <?php echo form_open_multipart('editedreqpic'); ?>
                    <div class="form-group" hidden>
                        <div class="row">
                            <div class="col-sm-8">
                                <input class="form-control" type="text" id="id_isi" name="id_isi" value="<?= $l->id_tmp_nk ?>" readonly />
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <div class="row">
                            <label class="col-sm-3 control-label text-right" for="kd_user">Keterangan<span class="required">*</span></label>
                            <div class="col-sm-8"><input class="form-control" type="text" id="ket_isi" name="ket_isi" value="<?= $l->keterangan ?>" placeholder="Inputkan keterangan kebutuhan" /></div>
                        </div>
                    </div>
                    <div class="form-group">
                        <div class="row">
                            <label class="col-sm-3 control-label text-right" for="kd_user">QTY<span class="required">*</span></label>
                            <div class="col-sm-8"><input class="form-control" type="number" id="qty_isi" name="qty_isi" value="<?= $l->qty ?>" placeholder="Inputkan jumlah kebutuhan" /></div>
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

    <!-- DELETE DATA -->
    <div class="modal fade" id="hapus<?= $l->id_tmp_nk ?>">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title"><span style="text-transform:uppercase"><b>DELETE - <?= $l->nama_barang ?></b></span></h4>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <?php echo form_open_multipart('deletedtmpnkreq'); ?>
                    <div class="form-group" hidden>
                        <div class="row">
                            <div class="col-sm-8">
                                <input class="form-control" type="text" id="id_isi" name="id_isi" value="<?= $l->id_tmp_nk ?>" readonly />
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <h3>HAPUS LIST REQUEST BARANG - <?= $l->nama_barang ?></h3>
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
<?php endforeach; ?>