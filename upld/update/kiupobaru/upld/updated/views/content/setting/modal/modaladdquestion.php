<div class="modal fade" id="modalAddItem">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">Tambah Pertanyaan Baru</h4>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <?php echo form_open_multipart('addqbaru'); ?>
                <div class="form-group">
                    <div class="row">
                        <label class="col-sm-3 control-label text-right" for="kd_user">Input Pertanyaan<span class="required">*</span></label>
                        <div class="col-sm-8">
                            <input class="form-control" type="text" id="question" name="question" value="" placeholder="Inputkan Pertanyaan Baru" />
                            <input class="form-control" type="text" id="kdreview" name="kdreview" value="<?= $kdreview ?>" placeholder="Inputkan Pertanyaan Baru" hidden />
                            <?php foreach ($judul as $j) : ?>
                                <input class="form-control" type="text" id="kdmodule" name="kdmodule" value="<?= $j->kode ?>" placeholder="Inputkan Pertanyaan Baru" hidden />
                            <?php endforeach; ?>
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