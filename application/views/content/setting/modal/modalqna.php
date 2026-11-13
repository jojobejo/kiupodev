<div class="modal fade" id="modalInputreview">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">Input Review</h4>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <?php echo form_open_multipart('reviewanswer'); ?>
                <div class="form-group">
                    <div class="row">
                        <label class="col-sm-3 control-label text-right" for="kd_user">Penilaian Module<span class="required">*</span></label>
                        <div class="col-sm-8">
                            <select name="nilai" id="nilai" class="form-control">
                                <option value="0" selected disabled>-- PILIH PENILAIAN MODUL INI --</option>
                                <option value="1">1</option>
                                <option value="2">2</option>
                                <option value="3">3</option>
                                <option value="4">4</option>
                                <option value="5">5</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="form-group">
                    <div class="row">
                        <label class="col-sm-3 control-label text-right" for="kd_user">Kritik & Saran<span class="required">*</span></label>
                        <div class="col-sm-8">
                            <textarea name="kritik" id="kritik" class="form-control">

                            </textarea>
                        </div>
                        <div>
                            <?php foreach ($question as $q) : ?>
                                <input type="text" name="kdmodule" class="form-control" readonly><?= $q->kode ?>
                                <input type="text" name="kdreview" class="form-control" readonly><?= $q->kode_r ?>
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