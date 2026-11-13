<!-- MODAL ADD -->
<div class="modal fade" id="modalAddItem">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">Tambah Note Printout</h4>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <?php echo form_open_multipart('addnotetemplate'); ?>
                <div class="form-group">
                    <div class="row">
                        <label class="col-sm-3 control-label text-right" for="kd_user">Nama Note Template<span class="required">*</span></label>
                        <div class="col-sm-8">
                            <input class="form-control" type="text" id="nm_template" name="nm_template" value="" />
                            <input class="form-control" type="text" id="kd_nt_template" name="kd_nt_template" value="<?= $kdnote ?>" hidden readonly />
                        </div>
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

<!-- MODAL ADD -->
<?php foreach ($note as $n) : ?>
    <div class="modal fade" id="modalAddItem<?= $n->id_nt_template ?>">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title">Update Note Printout - <?= $n->nama_note ?></h4>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <?php echo form_open_multipart('updateisinote'); ?>
                    <div class="form-group">
                        <div class="row">
                            <label class="col-sm-3 control-label text-right" for="kd_user">Tujuan Penerima<span class="required">*</span></label>
                            <div class="col-sm-8">
                                <input class="form-control" type="text" id="shipment_to" name="shipment_to" value="<?= $n->shipment_to ?>" />
                                <input class="form-control" type="text" id="kd_note" name="kd_note" value="<?= $n->kd_nt_template ?>" readonly hidden />
                                <input class="form-control" type="text" id="id_isi" name="id_isi" value="<?= $n->id_nt_template ?>" readonly hidden />
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <div class="row">
                            <label class="col-sm-3 control-label text-right" for="kd_user">Alamat Tujuan<span class="required">*</span></label>
                            <div class="col-sm-8">
                                <input class="form-control" type="text" id="alamat_ship" name="alamat_ship" value="<?= $n->alamat_ship ?>" />
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <div class="row">
                            <label class="col-sm-3 control-label text-right" for="kd_user">Koordinator Penerima<span class="required">*</span></label>
                            <div class="col-sm-8">
                                <input class="form-control" type="text" id="cp_shipment" name="cp_shipment" value="<?= $n->cp_shipment ?>" />
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <div class="row">
                            <label class="col-sm-3 control-label text-right" for="kd_user">Kontak Person Penerima<span class="required">*</span></label>
                            <div class="col-sm-8">
                                <input class="form-control" type="text" id="no_cp" name="no_cp" value="<?= $n->no_cp ?>" />
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <div class="row">
                            <label class="col-sm-3 control-label text-right" for="kd_user">Keterangan Lainya<span class="required">*</span></label>
                            <div class="col-sm-8">
                                <textarea class="form-control" name="ket_1_isi" id="ket_1_isi" cols="30" rows="10"><?= $n->ket_1 ?></textarea>
                            </div>
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
<?php endforeach; ?>