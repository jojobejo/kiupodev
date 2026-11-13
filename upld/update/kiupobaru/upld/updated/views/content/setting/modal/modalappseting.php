<div class="modal fade" id="modalAddItem">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">Add Module</h4>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <?php echo form_open_multipart('addnewmodule'); ?>
                <div class="form-group">
                    <div class="row">
                        <label class="col-sm-3 control-label text-right" for="kd_user">Nama Module<span class="required">*</span></label>
                        <div class="col-sm-8">
                            <input class="form-control" type="text" id="nmbarang" name="nmbarang" value="" placeholder="Inputkan Nama Modul" />
                        </div>
                    </div>
                </div>
                <div class="form-group">
                    <div class="row">
                        <label class="col-sm-3 control-label text-right" for="kd_user">Pilih Apps<span class="required">*</span></label>
                        <div class="col-sm-8">
                            <select class="form-control" id="slectapps" name="slectapps">
                                <option value="0" selected disabled>-- Pilih Apps --</option>
                                <option value="1">Komersil</option>
                                <option value="2">Non Komersil</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="form-group" hidden>
                    <div class="row">
                        <label class="col-sm-3 control-label text-right" for="kd_user">-<span class="required">*</span></label>
                        <div class="col-sm-8">
                            <input class="form-control" type="text" id="genkd" name="genkd" value="<?= $genkdmod ?>" placeholder="Inputkan Nama Modul" readonly />
                            <input class="form-control" type="text" id="jnismod" name="jnismod" value="1" readonly />
                            <input class="form-control" type="text" id="inputer" name="inputer" value="<?= $this->session->userdata('kode') ?>" readonly />
                            <?php
                            date_default_timezone_set("Asia/Jakarta");
                            $now = date("Y-m-d H:i:s");
                            ?>
                            <input class="form-control" type="text" id="dtinput" name="dtinput" value="<?= $now ?>" readonly />
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