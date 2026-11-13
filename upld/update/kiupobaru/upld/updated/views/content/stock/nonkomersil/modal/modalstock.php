<?php foreach ($item as $i) : ?>
    <!-- MODAL ADD -->
    <div class="modal fade" id="adjustmentqty<?= $i->kode_sistem ?>">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title">Adjustmen Stock - <span style="text-transform:uppercase"><b><?= $i->nama_barang ?></b></span></h4>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <?php echo form_open_multipart('adjustmenqty'); ?>
                    <div class="form-group" hidden>
                        <div class="row">
                            <label class="col-sm-3 control-label text-right" for="kd_user">INPUT SYSTEM<span class="required">*</span></label>
                            <div class="col-sm-8">
                                <input class="form-control" type="text" id="adjustmentkd" name="adjustmentkd" value="<?= $kdgenerate ?>" readonly />
                                <input class="form-control" type="text" id="kdbrsistem" name="kdbrsistem" value="<?= $i->kode_sistem ?>" readonly />
                                <input class="form-control" type="text" id="kdbarang" name="kdbarang" value="<?= $i->kode_barang ?>" readonly />
                                <input class="form-control" type="text" id="katbarang" name="katbarang" value="<?= $i->katbr ?>" readonly />
                                <input class="form-control" type="text" id="satuanid" name="satuanid" value="<?= $i->satuanid ?>" readonly />
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <div class="row">
                            <label class="col-sm-3 control-label text-right" for="kd_user">KODE AKUN<span class="required">*</span></label>
                            <div class="col-sm-8">
                                <select name="kdakun" id="kdakun" class="form-control">
                                    <option value="0" disabled selected> -- PILIH KODE AKUN --</option>
                                    <option value="11513">PENAMBAHAN QTY BARANG PERSEDIAN</option>
                                    <option value="11514">PENGURANGAN QTY BARANG PERSEDIAN</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <div class="row">
                            <label class="col-sm-3 control-label text-right" for="kd_user">ADJUSTMENT QTY<span class="required">*</span></label>
                            <div class="col-sm-8">
                                <input class="form-control" type="number" id="adjqty" name="adjqty" value="" placeholder="INPUT QTY ADJUSTMENT">
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <div class="row">
                            <label class="col-sm-3 control-label text-right" for="kd_user">KETERANGAN ADJUSTMENT<span class="required">*</span></label>
                            <div class="col-sm-8">
                                <textarea name="ket_isi" id="ket_isi" class="form-control" placeholder="INPUT KETERANGAN ADJUSTMENT"></textarea>
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
    </div>
<?php endforeach; ?>