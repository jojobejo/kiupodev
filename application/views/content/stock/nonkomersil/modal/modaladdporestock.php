<?php foreach ($vstock as $v) : ?>
    <div class="modal fade" id="adddraft<?= $v->kdbarangsys ?>">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title"><span style="text-transform:uppercase"><b><?= $v->nmbarang ?></b></span></h4>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <?php echo form_open_multipart('indraftrestock'); ?>
                    <div class="form-group" hidden>
                        <div class="row">
                            <div class="col-sm-8">
                                <input class="form-control" type="text" id="kdbys" name="kdbys" value="<?= $v->kdbarangsys ?>" readonly />
                                <input class="form-control" type="text" id="kdbr" name="kdbr" value="<?= $v->kdbarang ?>" readonly />
                                <input class="form-control" type="text" id="katbr" name="katbr" value="<?= $v->katbr ?>" readonly />
                                <input class="form-control" type="text" id="nm_barang" name="nm_barang" value="<?= $v->nmbarang ?>" readonly />
                                <input class="form-control" type="text" id="descnk_isi" name="descnk_isi" value="<?= $v->desk ?>" readonly />
                                <input class="form-control" type="text" id="satqty" name="satqty" value="<?= $v->idsat ?>" readonly />
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <div class="row">
                            <label class="col-sm-3 control-label text-right" for="kd_user">Keterangan<span class="required">*</span></label>
                            <div class="col-sm-8"><input class="form-control" type="text" id="ket_isi" name="ket_isi" value="" placeholder="Inputkan keterangan kebutuhan" /></div>
                        </div>
                    </div>
                    <div class="form-group">
                        <div class="row">
                            <label class="col-sm-3 control-label text-right" for="kd_user">QTY<span class="required">*</span></label>
                            <div class="col-sm-8"><input class="form-control" type="number" id="qty_isi" name="qty_isi" value="" placeholder="Inputkan jumlah kebutuhan" /></div>
                        </div>
                    </div>
                    <div class="form-group">
                        <div class="row">
                            <label class="col-sm-3 control-label text-right" for="kd_user">Harga Satuan<span class="required">*</span></label>
                            <div class="col-sm-8"><input class="form-control" type="number" id="hrgsat" name="hrgsat" value="" placeholder="input harga satuan" /></div>
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