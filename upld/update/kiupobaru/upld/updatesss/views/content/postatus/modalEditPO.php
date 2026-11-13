<?php foreach ($detail as $d) : ?>
    <div class="modal fade" id="modalEdit<?= $d->id_det_po ?>">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title">Tambah Note</h4>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <?php echo form_open_multipart('revisiPO'); ?>
                    <input type="hidden" name="is_bonus" value="<?= isset($d->is_bonus) ? (int) $d->is_bonus : 0 ?>">
                    <div class="form-group" hidden>
                        <div class="row">
                            <label class="col-sm-3 control-label text-right" for="kd_user">Satuan<span class="required">*</span></label>
                            <div class="col-sm-8"><input class="form-control" type="text" id="kdpo" name="kdpo" value="<?= $d->kd_po ?>" readonly />
                            </div>
                        </div>
                    </div>
                    <div class="form-group" hidden>
                        <div class="row">
                            <label class="col-sm-3 control-label text-right" for="kd_user">Satuan<span class="required">*</span></label>
                            <div class="col-sm-8"><input class="form-control" type="text" id="idpo" name="idpo" value="<?= $d->id_det_po ?>" readonly />
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <div class="row">
                            <label class="col-sm-3 control-label text-right" for="kd_user">Satuan<span class="required">*</span></label>
                            <div class="col-sm-8">
                                <select name="satuan_isi" id="satuan_isi" class="form-control">
                                    <option value="<?= $d->satuan ?>"><?= $d->satuan ?></option>
                                    <?php foreach ($satuan as $s) : ?>
                                        <option value="<?= $s->nm_satuan ?>"> <?= $s->nm_satuan ?></option>
                                    <?php endforeach;  ?>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <div class="row">
                            <label class="col-sm-3 control-label text-right" for="kd_user">Qty<span class="required">*</span></label>
                            <div class="col-sm-8"><input class="form-control" type="number" id="qty_isi" name="qty_isi" value="<?= $d->qty ?>" step="0.000000000001" /></div>
                        </div>
                    </div>
                    <?php if (isset($d->is_bonus) && (int) $d->is_bonus === 1) : ?>
                        <input type="hidden" name="hrg_isi" value="0">
                        <div class="form-group">
                            <div class="row">
                                <label class="col-sm-3 control-label text-right" for="kd_user">Keterangan Bonus<span class="required">*</span></label>
                                <div class="col-sm-8"><textarea class="form-control" name="bonus_keterangan" rows="3"><?= isset($d->keterangan_bonus) ? $d->keterangan_bonus : '' ?></textarea></div>
                            </div>
                        </div>
                    <?php else : ?>
                        <div class="form-group">
                            <div class="row">
                                <label class="col-sm-3 control-label text-right" for="kd_user">Harga Satuan<span class="required">*</span></label>
                                <div class="col-sm-8"><input class="form-control" type="number" id="hrg_isi" name="hrg_isi" value="<?= $d->hrg_satuan ?>" step="0.000000000001" /></div>
                            </div>
                        </div>
                    <?php endif; ?>
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

<?php foreach ($status as $s) : ?>
    <div class="modal fade" id="modalAddNote">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title">Tambah Note</h4>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <?php echo form_open_multipart('NoteDirektur'); ?>
                    <div class="form-group" hidden>
                        <div class="row">
                            <label class="col-sm-2 control-label text-right" for="kd_user">Kd<span class="required">*</span></label>
                            <div class="col-sm-9"><input type="text" id="kdpo" name="kdpo" value="<?= $s->kd_po ?>" readonly></div>
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
<?php foreach ($status as $s) : ?>
    <div class="modal fade" id="modaleditnopo<?= $s->id_po ?>">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title">Edit No Po</h4>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <?php echo form_open_multipart('edit_no_po'); ?>
                    <div class="form-group" hidden>
                        <div class="row">
                            <label class="col-sm-2 control-label text-right" for="kd_user">Kd<span class="required">*</span></label>
                            <div class="col-sm-9"><input type="text" id="kdpo" name="kdpo" value="<?= $s->kd_po ?>" readonly></div>
                            <div class="col-sm-9"><input type="text" id="id_po" name="id_po" value="<?= $s->id_po ?>" readonly></div>
                        </div>
                    </div>
                    <div class="form-group">
                        <div class="row">
                            <label class="col-sm-2 control-label text-right" for="kd_user">No Po<span class="required">*</span></label>
                            <div class="col-sm-9">
                                <input class="form-control text-uppercase" type="text" id="nopo<?= $s->id_po ?>" name="nopo" value="<?= $s->no_po ?>" pattern="[QA][0-9]{3}/KIU/(I|II|III|IV|V|VI|VII|VIII|IX|X|XI|XII)/[0-9]{4}[A-Z]?" title="Format Nomor PO harus seperti Q001/KIU/VII/2026 atau Q001/KIU/VII/2026A" required>
                                <small class="form-text text-muted">Format: Q001/KIU/VII/2026 atau Q001/KIU/VII/2026A</small>
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

<?php foreach ($status as $s) : ?>
    <div class="modal fade" id="modalcancelpo<?= $s->id_po ?>">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title">Cancel PO</h4>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <?php echo form_open_multipart('cancel_po'); ?>
                    <div class="form-group" hidden>
                        <div class="row">
                            <label class="col-sm-2 control-label text-right" for="kd_user">Kd<span class="required">*</span></label>
                            <div class="col-sm-9"><input type="text" id="kdpo" name="kdpo" value="<?= $s->kd_po ?>" readonly></div>
                            <div class="col-sm-9"><input type="text" id="id_po" name="id_po" value="<?= $s->id_po ?>" readonly></div>
                        </div>
                    </div>
                    <div class="form-group">
                        <div class="row">
                            <label class="col-sm-2 control-label text-right" for="kd_user">Note Cancel<span class="required">*</span></label>
                            <div class="col-sm-9">
                                <textarea name="nt_cancel" id="nt_cancel" cols="30" rows="10" class="form-control"></textarea>
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
