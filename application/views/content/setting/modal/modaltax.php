<!-- MODAL ADD -->
<div class="modal fade" id="modalAddItem">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">Tambah Satuan Pajak</h4>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <?php echo form_open_multipart('addSatuanPajak'); ?>
                <div class="form-group">
                    <div class="row">
                        <label class="col-sm-3 control-label text-right" for="kd_user">Satuan Pajak<span class="required">*</span></label>
                        <div class="col-sm-8"><input class="form-control" type="number" id="pajak_isi" name="pajak_isi" value="" /></div>
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

<?php foreach ($tax as $t) : ?>
    <div class="modal fade" id="modalEdit<?= $t->id_tax ?>">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title">Tambah Satuan Pajak</h4>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <?php echo form_open_multipart('editSatuanPajak'); ?>
                    <div class="form-group" hidden>
                        <div class="row">
                            <label class="col-sm-3 control-label text-right" for="kd_user">id_pajak<span class="required">*</span></label>
                            <div class="col-sm-8"><input class="form-control" type="number" id="id_isi" name="id_isi" value="<?= $t->id_tax ?>" /></div>
                        </div>
                    </div>
                    <div class="form-group">
                        <div class="row">
                            <label class="col-sm-3 control-label text-right" for="kd_user">Satuan Pajak<span class="required">*</span></label>
                            <div class="col-sm-8"><input class="form-control" type="number" id="pajak_isi" name="pajak_isi" value="<?= $t->nm_tax ?>" /></div>
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


<?php foreach ($tax as $t) : ?>
    <div class="modal fade" id="hapusPajak<?= $t->id_tax ?>">
        <div class="modal-dialog modal-confirm">
            <div class="modal-content">
                <div class="modal-header flex-column">
                    <div class="icon-box">
                        <i class="fa fa-remove" style="margin-top: 1px;"></i>
                    </div>
                    <h4 class="modal-title w-100">Hapus Satuan Pajak</h4>
                    <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                </div>
                <div class="modal-body">
                    <p>apakah anda akan menghapus data satuan pajak <?= $t->nm_tax ?> % </p>
                </div>
                <div class="modal-footer justify-content-center">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                    <button type="button" class="btn btn-danger"><a style="text-decoration: none; color:white;" href="<?php echo base_url("hapusPajak/$t->id_tax") ?>">Hapus</a></button>
                </div>
            </div>
            <!-- END CONTENT -->
        </div>
    </div>
<?php endforeach; ?>