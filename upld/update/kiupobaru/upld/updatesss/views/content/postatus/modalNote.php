    <?php foreach ($status as $s) : ?>
        <div class="modal fade" id="modalAddNote">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h4 class="modal-title">Update PO</h4>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <?php echo form_open_multipart('NoteUpdateKeuangan'); ?>
                        <div class="form-group" hidden>
                            <div class="row">
                                <label class="col-sm-2 control-label text-right" for="kd_user">Kd<span class="required">*</span></label>
                                <div class="col-sm-9"><input type="text" id="kdpo" name="kdpo" value="<?= $s->kd_po ?>" readonly></div>
                            </div>
                        </div>
                        <div class="form-group">
                            <div class="row">
                                <label class="col-sm-2 control-label text-right" for="kd_user">Catatan<span class="required">*</span></label>
                                <div class="col-sm-9"><textarea class="form-control" type="text" id="note_direktur" name="note_direktur" value=""> </textarea></div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer justify-content-between">
                        <button type="button" class="btn btn-default" data-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary" id="btnSaveNote">Simpan</button>
                    </div>
                    </form>
                </div>
                <!-- /.modal-content -->
            </div>
            <!-- /.modal-dialog -->
        </div>
    <?php endforeach; ?>

    <?php foreach ($status as $s) : ?>
        <div class="modal fade" id="modalAddNoteRev">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h4 class="modal-title">Update PO</h4>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <?php echo form_open_multipart('ntupdateporevisi'); ?>
                        <div class="form-group" hidden>
                            <div class="row">
                                <label class="col-sm-2 control-label text-right" for="kd_user">Kd<span class="required">*</span></label>
                                <div class="col-sm-9"><input type="text" id="kdpo" name="kdpo" value="<?= $s->kd_po ?>"></div>
                            </div>
                        </div>
                        <div class="form-group">
                            <div class="row">
                                <label class="col-sm-2 control-label text-right" for="kd_user">Catatan<span class="required">*</span></label>
                                <div class="col-sm-9"><textarea class="form-control" type="text" id="noteDitektur" name="noteDitektur" value=""> </textarea></div>
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