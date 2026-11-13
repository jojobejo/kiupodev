<?php foreach ($stocknk as $s) : ?>

    <!-- MODAL ADD -->
    <div class="modal fade" id="addchartponk<?= $s->id_brg_nk ?>">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title">Add Barang - <?= strtoupper($s->nama_barang) ?></h4>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <?php echo form_open_multipart('pononkomersil/list_stocknkpo/addtmpbarangnk'); ?>
                    <div class="form-group" hidden>
                        <div class="row">
                            <label class="col-sm-3 control-label text-right" for="kd_user">INPUT SYSTEM<span class="required">*</span></label>
                            <div class="col-sm-8">
                                <input class="form-control" type="text" id="kd_system" name="kd_system" value="<?= $s->kd_barang ?>" />
                                <input class="form-control" type="text" id="kd_adm" name="kd_adm" value="<?= $s->kd_br_adm ?>" />
                                <input class="form-control" type="text" id="katbrg" name="katbrg" value="<?= $s->kat_barang ?>" />
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <div class="row">
                            <label class="col-sm-3 control-label text-right" for="kd_user">Nama Barang<span class="required">*</span></label>
                            <div class="col-sm-8">
                                <input class="form-control" type="text" id="nmbarang" name="nmbarang" value="<?= $s->nama_barang ?>" readonly />
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <div class="row">
                            <label class="col-sm-3 control-label text-right" for="kd_user">Deskripsi<span class="required">*</span></label>
                            <div class="col-sm-8"><textarea name="descisi" id="descisi" class="form-control" readonly><?= $s->descnk ?></textarea></div>
                        </div>
                    </div>
                    <div class="form-group">
                        <div class="row">
                            <label class="col-sm-3 control-label text-right" for="kd_user">keterangan<span class="required">*</span></label>
                            <div class="col-sm-8"><textarea name="ketbarang" id="ketbarang" class="form-control" placeholder="isi dengan keterangan kebutuhan pembelian / spesifikasi tertentu"></textarea></div>
                        </div>
                    </div>
                    <div class="form-group">
                        <div class="row">
                            <label class="col-sm-3 control-label text-right" for="kd_user">QTY<span class="required">*</span></label>
                            <div class="col-sm-8">
                                <input class="form-control" type="number" id="qtyisi" name="qtyisi" value="" placeholder="Input QTY" />
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <div class="row">
                            <label class="col-sm-3 control-label text-right" for="kd_user">Harga Satuan<span class="required">*</span></label>
                            <div class="col-sm-8">
                                <input class="form-control" type="number" id="hrgisi" name="hrgisi" value="<?= isset($s->harga_lifo) && $s->harga_lifo > 0 ? $s->harga_lifo : '' ?>" placeholder="Input harga barang Rp. " />
                                <?php if (isset($s->harga_lifo) && $s->harga_lifo > 0) : ?>
                                    <small class="form-text text-muted">Harga LIFO dari PO terakhir <?= htmlspecialchars($s->harga_lifo_po, ENT_QUOTES, 'UTF-8') ?></small>
                                <?php endif; ?>
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
    <!-- REQUEST MASTER BARANG -->
    <div class="modal fade" id="addmbarangnk">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title">Request Add Master Barang Baru</h4>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <?php echo form_open_multipart('add_mbarang_tmp'); ?>

                    <div class="form-group">
                        <div class="row">
                            <label class="col-sm-3 control-label text-right" for="kd_user">Nama Barang<span class="required">*</span></label>
                            <div class="col-sm-8">
                                <input class="form-control" type="text" id="nmbarang" name="nmbarang" value="" />
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <div class="row">
                            <label class="col-sm-3 control-label text-right" for="kd_user">Deskripsi<span class="required">*</span></label>
                            <div class="col-sm-8"><textarea name="descisi" id="descisi" class="form-control"></textarea></div>
                        </div>
                    </div>
                    <div class="form-group">
                        <div class="row">
                            <label class="col-sm-3 control-label text-right" for="kd_user">Satuan<span class="required">*</span></label>
                            <div class="col-sm-8">
                                <select name="stuanbr" id="stuanbr" class="form-control">
                                    <?php foreach ($satuan as $s) : ?>
                                        <option id="stuanbrpil" name="stuanbrpil" value="<?= $s->id_satuan ?>"><?= $s->nm_satuan ?></option>
                                    <?php endforeach; ?>
                                </select>
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

<!-- MODAL UNTUK FITUR ADD BARANG PO STATUS -->
<?php $this->load->model('PO/M_Postatus');

?>
<?php foreach ($stocknk as $s) :
    $kdponk = $this->uri->segment(5, 0);
    $det = $this->M_Postatus->getDetailnktgl($kdponk);
?>
    <div class="modal fade" id="addchartdetponk<?= $s->id_brg_nk ?>">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title">Add Barang - <?= strtoupper($s->nama_barang) ?></h4>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <?php echo form_open_multipart('add_faktur_item_nk'); ?>
                    <div class="form-group" hidden>
                        <div class="row">
                            <label class="col-sm-3 control-label text-right" for="kd_user">INPUT SYSTEM<span class="required">*</span></label>
                            <div class="col-sm-8">
                                <input class="form-control" type="text" id="kdponk" name="kdponk" value="<?= $kdponk ?>" />
                                <input class="form-control" type="text" id="kd_system" name="kd_system" value="<?= $s->kd_barang ?>" />
                                <input class="form-control" type="text" id="kd_adm" name="kd_adm" value="<?= $s->kd_br_adm ?>" />
                                <input class="form-control" type="text" id="katbrg" name="katbrg" value="<?= $s->kat_barang ?>" />
                                <?php foreach ($det as $d) : ?>
                                    <input class="form-control" type="text" id="tgltransaksi" name="tgltransaksi" value="<?= $d->tgl_transaksi ?>" />
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <div class="row">
                            <label class="col-sm-3 control-label text-right" for="kd_user">Nama Barang<span class="required">*</span></label>
                            <div class="col-sm-8">
                                <input class="form-control" type="text" id="nmbarang" name="nmbarang" value="<?= $s->nama_barang ?>" readonly />
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <div class="row">
                            <label class="col-sm-3 control-label text-right" for="kd_user">Deskripsi<span class="required">*</span></label>
                            <div class="col-sm-8"><textarea name="descisi" id="descisi" class="form-control" readonly><?= $s->descnk ?></textarea></div>
                        </div>
                    </div>
                    <div class="form-group">
                        <div class="row">
                            <label class="col-sm-3 control-label text-right" for="kd_user">keterangan<span class="required">*</span></label>
                            <div class="col-sm-8"><textarea name="ketbarang" id="ketbarang" class="form-control" placeholder="isi dengan keterangan kebutuhan pembelian / spesifikasi tertentu"></textarea></div>
                        </div>
                    </div>
                    <div class="form-group">
                        <div class="row">
                            <label class="col-sm-3 control-label text-right" for="kd_user">QTY<span class="required">*</span></label>
                            <div class="col-sm-8">
                                <input class="form-control" type="number" id="qtyisi" name="qtyisi" value="" placeholder="Input QTY" />
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <div class="row">
                            <label class="col-sm-3 control-label text-right" for="kd_user">Harga Satuan<span class="required">*</span></label>
                            <div class="col-sm-8">
                                <input class="form-control" type="number" id="hrgisi" name="hrgisi" value="<?= isset($s->harga_lifo) && $s->harga_lifo > 0 ? $s->harga_lifo : '' ?>" placeholder="Input harga barang Rp. " />
                                <?php if (isset($s->harga_lifo) && $s->harga_lifo > 0) : ?>
                                    <small class="form-text text-muted">Harga LIFO dari PO terakhir <?= htmlspecialchars($s->harga_lifo_po, ENT_QUOTES, 'UTF-8') ?></small>
                                <?php endif; ?>
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
