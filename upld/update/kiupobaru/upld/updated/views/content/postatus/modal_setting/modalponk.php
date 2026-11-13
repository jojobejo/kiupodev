<?php foreach ($status as $s) : ?>
    <div class="modal fade" id="addmodalbarang">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title">Add Item Baru</h4>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <?php echo form_open_multipart('add_faktur_item_nk'); ?>
                    <div class="form-group">
                        <div class="row">
                            <label class="col-sm-3 control-label text-right" for="kd_user">Nama Barang<span class="required">*</span></label>
                            <div class="col-sm-8"><input class="form-control" type="text" id="nama_isi" name="nama_isi" value="" /></div>
                            <input class="form-control" type="text" id="kdponk" name="kdponk" value="<?= $s->kd_po_nk ?>" readonly hidden />
                            <input class="form-control" type="text" id="kdbrponk" name="kdbrponk" value="<?= $kdbarang ?>" readonly hidden />
                            <input class="form-control" type="text" id="tgltr" name="tgltr" value="<?= date('Y-m-d') ?>" readonly hidden />
                        </div>
                    </div>
                    <div class="form-group">
                        <div class="row">
                            <label class="col-sm-3 control-label text-right" for="kd_user">Deskripsi / Spesifikasi<span class="required">*</span></label>
                            <div class="col-sm-8"><textarea name="desc_isi" id="desc_isi" class="form-control"></textarea></div>
                        </div>
                    </div>
                    <div class="form-group">
                        <div class="row">
                            <label class="col-sm-3 control-label text-right" for="kd_user">Keterangan<span class="required">*</span></label>
                            <div class="col-sm-8"><textarea name="ket_isi" id="ket_isi" class="form-control"></textarea></div>
                        </div>
                    </div>
                    <div class="form-group">
                        <div class="row">
                            <label class="col-sm-3 control-label text-right" for="kd_user">Qty<span class="required">*</span></label>
                            <div class="col-sm-8"><input class="form-control" type="number" id="qty_isi" name="qty_isi" value="" step="0.000000000001" /></div>
                        </div>
                    </div>
                    <div class="form-group">
                        <div class="row">
                            <label class="col-sm-3 control-label text-right" for="kd_user">Harga Satuan<span class="required">*</span></label>
                            <div class="col-sm-8"><input class="form-control" type="number" id="hrg_isi" name="hrg_isi" value="" step="0.000000000001" /></div>
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
<div class="modal fade" id="addtax">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">Input Data Tax</h4>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <?php echo form_open_multipart('add_tax_nk'); ?>
                <div class="form-group">
                    <div class="row">
                        <input class="form-control" type="text" id="kdponk" name="kdponk" value="<?= $s->kd_po_nk ?>" readonly hidden />
                        <input class="form-control" type="text" id="kdbrponk" name="kdbrponk" value="<?= $kdbarang ?>" readonly hidden />
                        <input class="form-control" type="text" id="tgltr" name="tgltr" value="<?= date('Y-m-d') ?>" readonly hidden />
                    </div>
                </div>
                <div class="form-group">
                    <div class="row">
                        <label class="col-sm-3 control-label text-right" for="kd_user">Pilih Satuan Pajak<span class="required">*</span></label>
                        <div class="col-sm-8">
                            <select name="tax_isi" id="tax_isi" class="form-control">
                                <option value="" class="form-control">Pilih Tax</option>
                                <?php foreach ($tax as $t) : ?>
                                    <option value="<?= $t->nm_tax ?>"><?= $t->nm_tax ?> %</option>
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
<div class="modal fade" id="adddiskon">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">Input Data Diskon</h4>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <?php echo form_open_multipart('add_diskon_nk'); ?>
                <div class="form-group">
                    <div class="row">
                        <input class="form-control" type="text" id="kdponk" name="kdponk" value="<?= $s->kd_po_nk ?>" readonly hidden />
                    </div>
                </div>
                <div class="form-group">
                    <div class="row">
                        <label class="col-sm-3 control-label text-right" for="kd_user">Deskripsi Diskon<span class="required">*</span></label>
                        <div class="col-sm-8">
                            <input type="text" name="desc_isi" id="desc_isi" class="form-control">
                        </div>
                    </div>
                </div>
                <div class="form-group">
                    <div class="row">
                        <label class="col-sm-3 control-label text-right" for="kd_user">Nominal Diskon<span class="required">*</span></label>
                        <div class="col-sm-8">
                            <input type="number" name="nominal_isi" id="nominal_isi" class="form-control">
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
<?php foreach ($detail as $s) : ?>
    <div class="modal fade" id="edititem<?= $s->id_det_po_nk ?>">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title">Edit Item</h4>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <?php echo form_open_multipart('edit_faktur_item_nk'); ?>
                    <div class="form-group">
                        <div class="row">
                            <label class="col-sm-3 control-label text-right" for="kd_user">Nama Barang<span class="required">*</span></label>
                            <div class="col-sm-8"><input class="form-control" type="text" id="nama_isi" name="nama_isi" value="<?= $s->nama_barang ?>" /></div>
                            <input class="form-control" type="text" id="kdponk" name="kdponk" value="<?= $s->kd_po_nk ?>" readonly hidden />
                            <input class="form-control" type="text" id="idisi" name="idisi" value="<?= $s->id_det_po_nk ?>" readonly hidden />
                        </div>
                    </div>
                    <div class="form-group">
                        <div class="row">
                            <label class="col-sm-3 control-label text-right" for="kd_user">Deskripsi / Spesifikasi<span class="required">*</span></label>
                            <div class="col-sm-8"><textarea name="desc_isi" id="desc_isi" class="form-control"><?= $s->deskripsi ?></textarea></div>
                        </div>
                    </div>
                    <div class="form-group">
                        <div class="row">
                            <label class="col-sm-3 control-label text-right" for="kd_user">Keterangan<span class="required">*</span></label>
                            <div class="col-sm-8"><textarea name="ket_isi" id="ket_isi" class="form-control"><?= $s->keterangan ?></textarea></div>
                        </div>
                    </div>
                    <div class="form-group">
                        <div class="row">
                            <label class="col-sm-3 control-label text-right" for="kd_user">Qty<span class="required">*</span></label>
                            <div class="col-sm-8"><input class="form-control" type="number" id="qty_isi" name="qty_isi" value="<?= $s->qty ?>" /></div>
                        </div>
                    </div>
                    <div class="form-group">
                        <div class="row">
                            <label class="col-sm-3 control-label text-right" for="kd_user">Harga Satuan<span class="required">*</span></label>
                            <div class="col-sm-8"><input class="form-control" type="number" id="hrg_isi" name="hrg_isi" value="<?= $s->hrg_satuan ?>" /></div>
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
    <div class="modal fade" id="modalAddNote">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title">AJUKAN PEMBELIAN</h4>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <?php echo form_open_multipart('noteupdatenk'); ?>
                    <div class="form-group" hidden>
                        <div class="row">
                            <label class="col-sm-2 control-label text-right" for="kd_user">Kd<span class="required">*</span></label>
                            <div class="col-sm-9"><input type="text" id="kdpo" name="kdpo" value="<?= $s->kd_po_nk ?>" readonly></div>
                        </div>
                    </div>
                    <div class="form-group">
                        <h3>KONFIRMASI PENGAJUAN PO :<?= $s->nopo ?> </h3>
                    </div>
                </div>
                <div class="modal-footer justify-content-between">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Ajukan</button>
                </div>
                </form>
            </div>
            <!-- /.modal-content -->
        </div>
        <!-- /.modal-dialog -->
    </div>
<?php endforeach; ?>

<?php foreach ($detail as $s) : ?>
    <div class="modal fade" id="hapusitem<?= $s->id_det_po_nk ?>">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title">Edit Item</h4>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <?php echo form_open_multipart('hapus_faktur_item_nk'); ?>
                    <div class="form-group">
                        <div class="row">
                            <input class="form-control" type="text" id="kdponk" name="kdponk" value="<?= $s->kd_po_nk ?>" readonly hidden />
                            <input class="form-control" type="text" id="idisi" name="idisi" value="<?= $s->id_det_po_nk ?>" readonly hidden />
                            <input class="form-control" type="text" id="nmfile" name="nmfile" value="<?= $s->gbr_produk ?>" readonly hidden />
                        </div>
                    </div>
                    <div class="form-group">
                        <div class="row">
                            <h3>item akan terhapus secara permanen !</h3>
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
    <div class="modal fade" id="edponk<?= $s->kd_po_nk ?>">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title">Add Item Baru</h4>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <?php echo form_open_multipart('editedponk'); ?>
                    <div class="form-group" hidden>
                        <div class="row">
                            <label class="col-sm-3 control-label text-right" for="kd_user">Nama Barang<span class="required">*</span></label>
                            <div class="col-sm-8"><input class="form-control" type="text" id="nama_isi" name="nama_isi" value="" /></div>
                            <input class="form-control" type="text" id="kdponk" name="kdponk" value="<?= $s->kd_po_nk ?>" readonly hidden />
                            <input class="form-control" type="text" id="kdbrponk" name="kdbrponk" value="<?= $kdbarang ?>" readonly hidden />
                            <input class="form-control" type="text" id="tgltr" name="tgltr" value="<?= date('Y-m-d') ?>" readonly hidden />
                        </div>
                    </div>
                    <div class="form-group">
                        <div class="row">
                            <label class="col-sm-3 control-label text-right" for="kd_user">Nama Pengaju<span class="required">*</span></label>
                            <div class="col-sm-8">
                                <input class="form-control" type="text" id="nm_pengaju" name="nm_pengaju" value="<?= $s->nm_user ?>" />
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <div class="row">
                            <label class="col-sm-3 control-label text-right" for="kd_user">Departemen<span class="required">*</span></label>
                            <div class="col-sm-8">
                                <input class="form-control" type="text" id="dep_isi" name="dep_isi" value="<?= $s->departemen ?>" />
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <div class="row">
                            <label class="col-sm-3 control-label text-right" for="kd_user">Tujuan Pembelian<span class="required">*</span></label>
                            <div class="col-sm-8">
                                <textarea name="tj_pem" id="tj_pem" cols="30" rows="10" class="form-control"><?= $s->tj_pembelian ?></textarea>
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

<!-- INPUT NOMOR PO -->
<?php foreach ($status as $s) : ?>
    <div class="modal fade" id="addnopo<?= $s->kd_po_nk ?>">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title">Input Nomor PO</h4>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <?php echo form_open_multipart('addnopo'); ?>
                    <div class="form-group" hidden>
                        <div class="row">
                            <label class="col-sm-3 control-label text-right" for="kd_user">Nama Barang<span class="required">*</span></label>
                            <div class="col-sm-8"><input class="form-control" type="text" id="nama_isi" name="nama_isi" value="" /></div>
                            <input class="form-control" type="text" id="kdponk" name="kdponk" value="<?= $s->kd_po_nk ?>" readonly hidden />
                        </div>
                    </div>
                    <div class="form-group">
                        <div class="row">
                            <label class="col-sm-3 control-label text-right" for="kd_user">Nomor PO<span class="required">*</span></label>
                            <div class="col-sm-8">
                                <input class="form-control" type="text" id="nopo" name="nopo" value="<?= $s->nopo ?>" />
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

<!-- MODAL NOTE PEMBELIAN -->

<div class="modal fade" id="add_note_pembelian">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">Add Note Pembelian</h4>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <?php echo form_open_multipart('add_note_pembelian_nk'); ?>
                <div class="form-group">
                    <div class="row">
                        <input class="form-control" type="text" id="kdponk" name="kdponk" value="<?= $s->kd_po_nk ?>" readonly hidden />
                    </div>
                </div>
                <div class="form-group">
                    <div class="row">
                        <label class="col-sm-3 control-label text-right" for="kd_user">Keterangan<span class="required">*</span></label>
                        <div class="col-sm-8">
                            <textarea name="ket_isi" id="ket_isi" cols="30" rows="10" class="form-control"></textarea>
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
<?php foreach ($ntpembelian as $np) : ?>
    <div class="modal fade" id="edit_note_pembelian<?= $np->id_nt_pembelian ?>">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title">Edit Note Pembelian</h4>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <?php echo form_open_multipart('edit_note_pembelian_nk'); ?>
                    <div class="form-group">
                        <div class="row">
                            <input class="form-control" type="text" id="kdponk" name="kdponk" value="<?= $s->kd_po_nk ?>" readonly hidden />
                            <input class="form-control" type="text" id="id_isi" name="id_isi" value="<?= $np->id_nt_pembelian ?>" readonly hidden />
                        </div>
                    </div>
                    <div class="form-group">
                        <div class="row">
                            <label class="col-sm-3 control-label text-right" for="kd_user">Keterangan<span class="required">*</span></label>
                            <div class="col-sm-8">
                                <textarea name="ket_isi" id="ket_isi" cols="30" rows="10" class="form-control"><?= $np->keterangan ?></textarea>
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

<?php foreach ($ntpembelian as $np) : ?>
    <div class="modal fade" id="hapus_note_pembelian<?= $np->id_nt_pembelian ?>">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title">Hapus Note Pembelian</h4>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <?php echo form_open_multipart('hapus_note_pembelian_nk'); ?>
                    <div class="form-group">
                        <div class="row">
                            <input class="form-control" type="text" id="kdponk" name="kdponk" value="<?= $s->kd_po_nk ?>" readonly hidden />
                            <input class="form-control" type="text" id="id_isi" name="id_isi" value="<?= $np->id_nt_pembelian ?>" readonly hidden />
                        </div>
                    </div>
                    <div class="form-group">
                        <div class="row">
                            <h3>item akan terhapus secara permanen !</h3>
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
    <div class="modal fade" id="modalReject">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title">TOLAK ORDER PEMBELIAN</h4>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <?php echo form_open_multipart('tolakOrderNK'); ?>
                    <div class="form-group" hidden>
                        <div class="row">
                            <label class="col-sm-2 control-label text-right" for="kd_user">Kd<span class="required">*</span></label>
                            <div class="col-sm-9"><input type="text" id="kdpo" name="kdpo" value="<?= $s->kd_po_nk ?>" readonly></div>
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
                    <button type="submit" class="btn btn-primary">Update</button>
                </div>
                </form>
            </div>
            <!-- /.modal-content -->
        </div>
        <!-- /.modal-dialog -->
    </div>
<?php endforeach; ?>

<?php foreach ($status as $s) : ?>
    <div class="modal fade" id="modalPending<?= $s->kd_po_nk ?>">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title">PENDING ORDER PEMBELIAN</h4>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <?php echo form_open_multipart('pendingordernk'); ?>
                    <div class="form-group" hidden>
                        <div class="row">
                            <label class="col-sm-2 control-label text-right" for="kd_user">Kd<span class="required">*</span></label>
                            <div class="col-sm-9"><input type="text" id="kdpo" name="kdpo" value="<?= $s->kd_po_nk ?>" readonly></div>
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
                    <button type="submit" class="btn btn-primary">Update</button>
                </div>
                </form>
            </div>
            <!-- /.modal-content -->
        </div>
        <!-- /.modal-dialog -->
    </div>
<?php endforeach; ?>

<?php foreach ($status as $s) : ?>
    <div class="modal fade" id="modalReject">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title">Konfirmasi On Hand</h4>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <?php echo form_open_multipart('tolakOrderNK'); ?>
                    <div class="form-group" hidden>
                        <div class="row">
                            <label class="col-sm-2 control-label text-right" for="kd_user">Kd<span class="required">*</span></label>
                            <div class="col-sm-9"><input type="text" id="kdpo" name="kdpo" value="<?= $s->kd_po_nk ?>" readonly></div>
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
                    <button type="submit" class="btn btn-primary">Update</button>
                </div>
                </form>
            </div>
            <!-- /.modal-content -->
        </div>
        <!-- /.modal-dialog -->
    </div>
<?php endforeach; ?>

<?php foreach ($status as $s) : ?>
    <div class="modal fade" id="modalAddNotepembelian">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title">Konfirmasi Pembelian</h4>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <?php echo form_open_multipart('noteupdatenk_pembelian'); ?>
                    <div class="form-group" hidden>
                        <div class="row">
                            <label class="col-sm-2 control-label text-right" for="kd_user">Kd<span class="required">*</span></label>
                            <div class="col-sm-9"><input type="text" id="kdpo" name="kdpo" value="<?= $s->kd_po_nk ?>" readonly></div>
                        </div>
                    </div>
                    <div class="form-group">
                        <h3>KONFIRMASI PO , PROSES PEMBELIAN : <?= $s->nopo ?> </h3>
                    </div>
                </div>
                <div class="modal-footer justify-content-between">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Ajukan</button>
                </div>
                </form>
            </div>
            <!-- /.modal-content -->
        </div>
        <!-- /.modal-dialog -->
    </div>
<?php endforeach; ?>

<?php foreach ($status as $s) : ?>
    <div class="modal fade" id="modalonhand<?= $s->kd_po_nk ?>">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title">Konfirmasi Penerimaan Barang</h4>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>

                <div class="modal-body">
                    <?php echo form_open_multipart('konfirm_penerimaan'); ?>
                    <div class="form-group" hidden>
                        <div class="row">
                            <label class="col-sm-2 control-label text-right" for="kd_user">Kd<span class="required">*</span></label>
                            <div class="col-sm-9"><input type="text" id="kdpo" name="kdpo" value="<?= $s->kd_po_nk ?>" readonly></div>
                            <div class="col-sm-9"><input type="text" id="kdporeq" name="kdporeq" value="<?= $s->kd_po_req ?>"></div>
                            <div class="col-sm-9"><input type="text" id="tgl" name="tgl" value="<?= $s->tgl_transaksi ?>" readonly></div>
                            <div class="col-sm-9"><input type="text" id="tjpembelian" name="tjpembelian" value="<?= $s->tj_pembelian ?>" readonly></div>
                        </div>
                    </div>
                    <div class="form-group">
                        <h3>KONFIRMASI PO , BARANG TELAH DITERIMA </h3>
                    </div>
                </div>

                <div class="modal-footer justify-content-between">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Konfirmasi</button>
                </div>
                </form>
            </div>
            <!-- /.modal-content -->
        </div>
        <!-- /.modal-dialog -->
    </div>
<?php endforeach; ?>

<?php foreach ($status as $s) : ?>
    <div class="modal fade" id="modalrev<?= $s->kd_po_nk ?>">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title">REVISI ORDER PEMBELIAN</h4>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <?php echo form_open_multipart('porevisi'); ?>
                    <div class="form-group" hidden>
                        <div class="row">
                            <label class="col-sm-2 control-label text-right" for="kd_user">Kd<span class="required">*</span></label>
                            <div class="col-sm-9"><input type="text" id="kdpo" name="kdpo" value="<?= $s->kd_po_nk ?>" readonly></div>
                        </div>
                    </div>
                    <div class="form-group">
                        <div class="row">
                            <label class="col-sm-2 control-label text-right" for="kd_user">Catatan<span class="required">*</span></label>
                            <div class="col-sm-9"><textarea class="form-control" type="text" id="noteisi" name="noteisi" value=""> </textarea></div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer justify-content-between">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Update</button>
                </div>
                </form>
            </div>
            <!-- /.modal-content -->
        </div>
        <!-- /.modal-dialog -->
    </div>
<?php endforeach; ?>
<?php foreach ($status as $s) : ?>
    <div class="modal fade" id="modalrev<?= $s->kd_po_nk ?>">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title">REVISI ORDER PEMBELIAN</h4>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <?php echo form_open_multipart('porevisi'); ?>
                    <div class="form-group" hidden>
                        <div class="row">
                            <label class="col-sm-2 control-label text-right" for="kd_user">Kd<span class="required">*</span></label>
                            <div class="col-sm-9"><input type="text" id="kdpo" name="kdpo" value="<?= $s->kd_po_nk ?>" readonly></div>
                        </div>
                    </div>
                    <div class="form-group">
                        <div class="row">
                            <label class="col-sm-2 control-label text-right" for="kd_user">Catatan<span class="required">*</span></label>
                            <div class="col-sm-9"><textarea class="form-control" type="text" id="noteisi" name="noteisi" value=""> </textarea></div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer justify-content-between">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Update</button>
                </div>
                </form>
            </div>
            <!-- /.modal-content -->
        </div>
        <!-- /.modal-dialog -->
    </div>
<?php endforeach; ?>

<?php foreach ($detail as $s) : ?>
    <?php
    $qtyNyataModal = isset($s->qty_nyata) && (float) $s->qty_nyata > 0 ? (float) $s->qty_nyata : (float) $s->qty;
    $alasanRealisasiModal = isset($s->alasan_realisasi) ? $s->alasan_realisasi : '';
    ?>
    <div class="modal fade" id="hrgnyata<?= $s->id_det_po_nk ?>">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title">Input Realisasi Pembelian</h4>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <?php echo form_open_multipart('edit_harganyata'); ?>
                    <div class="form-group">
                        <div class="row">
                            <input class="form-control" type="text" id="kdponk" name="kdponk" value="<?= $s->kd_po_nk ?>" readonly hidden />
                            <input class="form-control" type="text" id="idisi" name="idisi" value="<?= $s->id_det_po_nk ?>" readonly hidden />
                            <input class="form-control" type="text" id="qty_isi" name="qty_isi" value="<?= $s->qty ?>" readonly hidden />
                        </div>
                    </div>
                    <div class="form-group">
                        <div class="row">
                            <label class="col-sm-3 control-label text-right">Qty Pengajuan</label>
                            <div class="col-sm-8"><input class="form-control" type="number" value="<?= $s->qty ?>" readonly /></div>
                        </div>
                    </div>
                    <div class="form-group">
                        <div class="row">
                            <label class="col-sm-3 control-label text-right">Harga Pengajuan</label>
                            <div class="col-sm-8"><input class="form-control" type="text" value="Rp. <?= number_format($s->hrg_satuan) ?>" readonly /></div>
                        </div>
                    </div>
                    <div class="form-group">
                        <div class="row">
                            <label class="col-sm-3 control-label text-right" for="qty_nyata">Qty Nyata<span class="required">*</span></label>
                            <div class="col-sm-8"><input class="form-control" type="number" step="0.01" id="qty_nyata" name="qty_nyata" value="<?= $qtyNyataModal ?>" /></div>
                        </div>
                    </div>
                    <div class="form-group">
                        <div class="row">
                            <label class="col-sm-3 control-label text-right" for="hrg_nyata">Harga Nyata<span class="required">*</span></label>
                            <div class="col-sm-8"><input class="form-control" type="number" step="0.01" id="hrg_nyata" name="hrg_nyata" value="<?= $s->hrg_nyata ?>" /></div>
                        </div>
                    </div>
                    <div class="form-group">
                        <div class="row">
                            <label class="col-sm-3 control-label text-right" for="alasan_realisasi">Alasan Selisih</label>
                            <div class="col-sm-8"><textarea class="form-control" id="alasan_realisasi" name="alasan_realisasi" rows="3"><?= htmlspecialchars($alasanRealisasiModal, ENT_QUOTES, 'UTF-8') ?></textarea></div>
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

<?php foreach ($detail as $t) : ?>
    <div class="modal fade" id="uploadgbritem<?= $t->id_det_po_nk ?>">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title">Edit Upload Gambar</h4>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <?php echo form_open_multipart('gbruploadpic'); ?>
                    <div class="form-group" hidden>
                        <div class="row">
                            <label class="col-sm-3 control-label text-right" for="kd_user">Nama Barang<span class="required">*</span></label>
                            <div class="col-sm-8"><input class="form-control" type="text" id="nama_isi" name="nama_isi" value="<?= $t->nama_barang ?>" />
                                <input class="form-control" type="text" id="id_isi" name="id_isi" value="<?= $t->id_det_po_nk ?>" readonly hidden />
                                <input class="form-control" type="text" id="kd_po" name="kd_po" value="<?= $t->kd_po_nk ?>">
                                <input class="form-control" type="text" id="nm_file" name="nm_file" value="<?= $t->gbr_produk ?>">
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <div class="row">
                            <label class="col-sm-3 control-label text-right" for="kd_user">Upload Gambar<span class="required">*</span></label>
                            <div class="col-sm-8"><input type="file" class="custom-file-input" id="gambar_1" name="gambar_1" accept="img/*">
                                <label class="custom-file-label" for="customFile">Pilih Gambar</label>
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
    <div class="modal fade" id="addfileupload<?= $s->kd_po_nk ?>">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title">Input File Pendukung</h4>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <?php echo form_open_multipart('uploadfileponk'); ?>
                    <div class="form-group" hidden>
                        <div class="row">
                            <div class="col-sm-8">
                                <input class="form-control" type="text" id="kdisi" name="kdisi" value="<?= $s->kd_po_nk ?>" readonly hidden />
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <div class="row">
                            <label class="col-sm-3 control-label text-right" for="kd_user">Keterangan<span class="required">*</span></label>
                            <div class="col-sm-8">
                                <textarea name="desc_isi" id="desc_isi" class="form-control"></textarea>
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <div class="row">
                            <label class="col-sm-3 control-label text-right" for="kd_user">Upload file<span class="required">*</span></label>
                            <div class="col-sm-8"><input type="file" class="custom-file-input" id="gambar_1" name="gambar_1" accept=".jpg,.jpeg,.png,.gif,.webp,.pdf,.csv,.xls,.xlsx,.doc,.docx" required>
                                <label class="custom-file-label" for="customFile">Pilih File</label>
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
<?php foreach ($flupload as $f) :
    $imagePath = base_url('postatusnk/supporting-file/' . $f->id_file_nk); ?>
    <div class="modal fade" id="openflnk<?= $f->id_file_nk ?>">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <div class="row">
                            <img src="<?= $imagePath ?>" style="width:150px; height:150px">
                        </div>
                    </div>
                </div>
                <div class="modal-footer justify-content-between">
                    <button type="button" class="btn btn-default btn-block" data-dismiss="modal">close</button>
                </div>
                </form>
            </div>
            <!-- /.modal-content -->
        </div>
        <!-- /.modal-dialog -->
    </div>
<?php endforeach; ?>


<?php foreach ($flupload as $f) : ?>
    <div class="modal fade" id="edit_gbr_pndukung<?= $f->id_file_nk ?>">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title">Edit File</h4>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <?php echo form_open_multipart('edit_gbr_pndukung'); ?>
                    <div class="form-group" hidden>
                        <div class="row">
                            <label class="col-sm-3 control-label text-right" for="kd_user">Nama Barang<span class="required">*</span></label>
                            <input class="form-control" type="text" id="id_isi" name="id_isi" value="<?= $f->id_file_nk ?>" readonly hidden />
                            <input class="form-control" type="text" id="kd_po" name="kd_po" value="<?= $f->kd_po_nk ?>">
                            <input class="form-control" type="text" id="file_nm" name="file_nm" value="<?= $f->file_uploaded ?>">
                        </div>
                    </div>
                </div>
                <div class="form-group">
                    <div class="row">
                        <label class="col-sm-3 control-label text-right" for="kd_user">Keterangan<span class="required">*</span></label>
                        <div class="col-sm-8">
                            <textarea name="desc_isi" id="desc_isi" class="form-control"><?= $f->keterangan ?></textarea>
                        </div>
                    </div>
                </div>

                <div class="modal-footer justify-content-between">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan</button>
                </div>
            </div>

            </form>
        </div>
        <!-- /.modal-content -->
    </div>
<?php endforeach; ?>

<?php foreach ($flupload as $f) : ?>
    <div class="modal fade" id="reuoploadimg_pndukung<?= $f->id_file_nk ?>">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title">Upload File Pendukung</h4>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <?php echo form_open_multipart('reuploadgbrflpndukung'); ?>
                    <div class="form-group" hidden>
                        <div class="row">
                            <label class="col-sm-3 control-label text-right" for="kd_user">Nama Barang<span class="required">*</span></label>
                            <input class="form-control" type="text" id="id_isi" name="id_isi" value="<?= $f->id_file_nk ?>" readonly hidden />
                            <input class="form-control" type="text" id="kd_po" name="kd_po" value="<?= $f->kd_po_nk ?>">
                            <input class="form-control" type="text" id="file_nm" name="file_nm" value="<?= $f->file_uploaded ?>">
                        </div>
                    </div>
                </div>
                <div class="form-group">
                    <div class="row">
                        <label class="col-sm-3 control-label text-right" for="kd_user">Upload Gambar<span class="required">*</span></label>
                        <div class="col-sm-8"><input type="file" class="custom-file-input" id="gambar_1" name="gambar_1" accept="img/*">
                            <label class="custom-file-label" for="customFile">Pilih Gambar</label>
                        </div>
                    </div>
                </div>

                <div class="modal-footer justify-content-between">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan</button>
                </div>
            </div>

            </form>
        </div>
        <!-- /.modal-content -->
    </div>
<?php endforeach; ?>

<?php foreach ($status as $s) : ?>
    <div class="modal fade" id="ajukanpembelian<?= $s->kd_po_nk ?>">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title">Upload Bukti Pembelian</h4>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <?php echo form_open_multipart('upbuktipembelian'); ?>
                </div>
                <div class="form-group">
                    <div class="row">
                        <label class="col-sm-3 control-label text-right" for="kd_user">Keterangan<span class="required">*</span></label>
                        <div class="col-sm-8">
                            <input type="text" class="custom-file-input" id="kdponk" name="kdponk" value="<?= $s->kd_po_nk ?>" hidden readonly>
                            <textarea name="desc_isi" id="desc_isi" class="form-control"></textarea>
                        </div>
                    </div>
                </div>
                <div class="form-group">
                    <div class="row">
                        <label class="col-sm-3 control-label text-right" for="kd_user">Upload Bukti<span class="required">*</span></label>
                        <div class="col-sm-8"><input type="file" class="custom-file-input" id="gambar_1" name="gambar_1" accept=".jpg,.jpeg,.png,.gif,.webp,.pdf,.csv,.xls,.xlsx,.doc,.docx" required>
                            <label class="custom-file-label" for="gambar_1">Pilih File</label>
                        </div>
                    </div>
                </div>
                <div class="modal-footer justify-content-between">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan</button>
                </div>
            </div>

            </form>
        </div>
        <!-- /.modal-content -->
    </div>
<?php endforeach; ?>

<?php foreach ($flupload as $f) : ?>
    <div class="modal fade" id="delete_gbr_pendukung<?= $f->id_file_nk ?>">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title">Hapus File - <?= $f->keterangan ?></h4>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <?php echo form_open_multipart('delete_gbr_pendukung'); ?>
                    <div class="form-group" hidden>
                        <div class="row">
                            <label class="col-sm-3 control-label text-right" for="kd_user">Nama Barang<span class="required">*</span></label>
                            <input class="form-control" type="text" id="id_isi" name="id_isi" value="<?= $f->id_file_nk ?>" readonly />
                            <input class="form-control" type="text" id="kd_po" name="kd_po" value="<?= $f->kd_po_nk ?>">
                            <input class="form-control" type="text" id="file_nm" name="file_nm" value="<?= $f->file_uploaded ?>">
                        </div>
                    </div>
                    <div class="form-group">
                        <div class="row">
                            <h3>item akan terhapus secara permanen !</h3>
                        </div>
                    </div>
                </div>
                <div class="modal-footer justify-content-between">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan</button>
                </div>
            </div>

            </form>
        </div>
        <!-- /.modal-content -->
    </div>
<?php endforeach; ?>
