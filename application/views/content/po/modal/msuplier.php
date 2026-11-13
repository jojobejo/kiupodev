<!-- MODAL ADD -->
<?php foreach ($kode_suplier as $b) : ?>
    <div class="modal fade" id="editSuplier<?= $b->id_suplier ?>">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title">Tambah Barang</h4>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <?php echo form_open_multipart('editSuplier'); ?>
                    <div class="form-group" hidden>
                        <div class="row">
                            <label class="col-sm-3 control-label text-right" for="kd_user">kode_suplier<span class="required">*</span></label>
                            <div class="col-sm-8"><input class="form-control" type="text" id="kd_sup" name="kd_sup" value="<?= $b->kd_suplier ?>" /></div>
                        </div>
                    </div>
                    <div class="form-group">
                        <div class="row">
                            <label class="col-sm-3 control-label text-right" for="kd_user">Nama Suplier<span class="required">*</span></label>
                            <div class="col-sm-8"><input class="form-control" type="text" id="nama_isi" name="nama_isi" value="<?= $b->nama_suplier ?>" /></div>
                        </div>
                    </div>
                    <div class="form-group">
                        <div class="row">
                            <label class="col-sm-3 control-label text-right" for="kd_user">Alamat Suplier<span class="required">*</span></label>
                            <div class="col-sm-8"><input class="form-control" type="text" id="alamat_isi" name="alamat_isi" value="<?= $b->alamat_suplier ?>" /></div>
                        </div>
                    </div>
                    <div class="form-group">
                        <div class="row">
                            <label class="col-sm-3 control-label text-right" for="kd_user">Nomor Telfon Suplier<span class="required">*</span></label>
                            <div class="col-sm-8"><input class="form-control" type="text" id="telp_isi" name="telp_isi" value="<?= $b->no_telpon ?>" /></div>
                        </div>
                    </div>
                    <div class="form-group">
                        <div class="row">
                            <label class="col-sm-3 control-label text-right" for="kd_user">Nomor Fax Suplier<span class="required">*</span></label>
                            <div class="col-sm-8"><input class="form-control" type="text" id="fax_isi" name="fax_isi" value="<?= $b->no_fax ?>" /></div>
                        </div>
                    </div>
                    <div class="form-group">
                        <div class="row">
                            <label class="col-sm-3 control-label text-right" for="kd_user">Email Suplier<span class="required">*</span></label>
                            <div class="col-sm-8"><input class="form-control" type="text" id="email_isi" name="email_isi" value="<?= $b->email ?>" /></div>
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

<div class="modal fade" id="modalnotebarang">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">Tambah Note Suplier</h4>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <?php echo form_open_multipart('addnotebarangsupliertmp'); ?>
                <div class="form-group" hidden>
                    <div class="row">
                        <label class="col-sm-3 control-label text-right" for="kd_user">kode_suplier<span class="required">*</span></label>
                        <div class="col-sm-8"><input class="form-control" type="text" id="kd_sup" name="kd_sup" value="<?= $kdsuplier ?>" readonly /></div>
                    </div>
                </div>
                <div class="form-group">
                    <div class="row">
                        <label class="col-sm-3 control-label text-right" for="kd_user">Deskripsi Note<span class="required">*</span></label>
                        <div class="col-sm-8">
                            <textarea name="isi" id="isi" cols="30" rows="10" class="form-control"></textarea>
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

<div class="modal fade" id="modaldiskon">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">Tambah Diskon PO</h4>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <?php echo form_open_multipart('add_diskon_po'); ?>
                <div class="form-group" hidden>
                    <div class="row">
                        <label class="col-sm-3 control-label text-right" for="kd_user">kode_suplier<span class="required">*</span></label>
                        <div class="col-sm-8"><input class="form-control" type="text" id="kd_sup" name="kd_sup" value="<?= $kdsuplier ?>" readonly /></div>
                    </div>
                </div>
                <div class="form-group">
                    <div class="row">
                        <label class="col-sm-3 control-label text-right" for="kd_user">Deskripsi Diskon<span class="required">*</span></label>
                        <div class="col-sm-8"><input class="form-control" type="text" id="deskripsi_isi" name="deskripsi_isi" value="" /></div>
                    </div>
                </div>
                <div class="form-group">
                    <div class="row">
                        <label class="col-sm-3 control-label text-right" for="kd_user">Nominal Diskon<span class="required">*</span></label>
                        <div class="col-sm-8"><input class="form-control number-format" type="text" inputmode="decimal" id="nominal_isi" name="nominal_isi" value="" autocomplete="off" /></div>
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

<div class="modal fade" id="modaldiskonmerk">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">Tambah Diskon Merk Barang</h4>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <?php echo form_open_multipart('add_diskon_merk_tmp'); ?>
                <input type="hidden" name="kd_sup" value="<?= $kdsuplier ?>" />
                <div class="form-group">
                    <div class="row">
                        <label class="col-sm-3 control-label text-right" for="merk_barang">Merk Barang<span class="required">*</span></label>
                        <div class="col-sm-8">
                            <select name="merk_barang" id="merk_barang" class="form-control" required>
                                <option value="">Pilih Merk Barang</option>
                                <?php if (!empty($merkBarangTmp)) : ?>
                                    <?php foreach ($merkBarangTmp as $merk) : ?>
                                        <option value="<?= htmlspecialchars($merk->merk_barang, ENT_QUOTES, 'UTF-8') ?>">
                                            <?= htmlspecialchars($merk->merk_barang, ENT_QUOTES, 'UTF-8') ?>
                                        </option>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="form-group">
                    <div class="row">
                        <label class="col-sm-3 control-label text-right" for="deskripsi_diskon_merk">Deskripsi Diskon<span class="required">*</span></label>
                        <div class="col-sm-8">
                            <input class="form-control" type="text" id="deskripsi_diskon_merk" name="deskripsi_isi" value="" required />
                        </div>
                    </div>
                </div>
                <div class="form-group">
                    <div class="row">
                        <label class="col-sm-3 control-label text-right">Satuan Diskon<span class="required">*</span></label>
                        <div class="col-sm-8">
                            <label class="mr-3"><input type="radio" name="satuan_diskon" value="BOX" required> Box</label>
                            <label class="mr-3"><input type="radio" name="satuan_diskon" value="PCS" required> Pcs</label>
                            <label class="mr-3"><input type="radio" name="satuan_diskon" value="LTR" required> Ltr</label>
                            <label class="mr-3"><input type="radio" name="satuan_diskon" value="KG" required> Kg</label>
                        </div>
                    </div>
                </div>
                <div class="form-group">
                    <div class="row">
                        <label class="col-sm-3 control-label text-right" for="nominal_isi">Nominal Diskon<span class="required">*</span></label>
                        <div class="col-sm-8"><input class="form-control number-format" type="text" inputmode="decimal" id="nominal_isi" name="nominal_isi" value="" autocomplete="off" required /></div>
                    </div>
                </div>
            </div>
            <div class="modal-footer justify-content-between">
                <button type="button" class="btn btn-default" data-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-primary">Simpan</button>
            </div>
            </form>
        </div>
    </div>
</div>

<?php foreach ($tmpdiskon as $td) : ?>
    <?php
    $rowMarker = '';
    if (preg_match('/\[ROW_(TMP|DET):\d+\]/', $td->nama_diskon, $markerMatch)) {
        $rowMarker = $markerMatch[0];
    }
    $merkMarker = '';
    if (preg_match('/\[MERK:[^\]]+\]/', $td->nama_diskon, $merkMarkerMatch)) {
        $merkMarker = $merkMarkerMatch[0];
    }
    $satuanMarker = '';
    if (preg_match('/\[SATUAN_DISKON:(BOX|PCS|LTR|KG)\]/i', $td->nama_diskon, $satuanMarkerMatch)) {
        $satuanMarker = $satuanMarkerMatch[0];
    }
    $diskonMerkMarker = '';
    if (preg_match('/\[DISKON_MERK:\d+\]/', $td->nama_diskon, $diskonMerkMarkerMatch)) {
        $diskonMerkMarker = $diskonMerkMarkerMatch[0];
    }
    $namaDiskonDisplay = preg_replace('/\s*-\s*Diskon Merk\s+.*?\s+\((BOX|PCS|LTR|KG)\)(?=\s*\[MERK:)/i', '', $td->nama_diskon);
    $namaDiskonDisplay = preg_replace('/\s*\[ROW_(TMP|DET):\d+\]/', '', $namaDiskonDisplay);
    $namaDiskonDisplay = preg_replace('/\s*\[MERK:[^\]]+\]/', '', $namaDiskonDisplay);
    $namaDiskonDisplay = preg_replace('/\s*\[SATUAN_DISKON:(BOX|PCS|LTR|KG)\]/i', '', $namaDiskonDisplay);
    $namaDiskonDisplay = preg_replace('/\s*\[DISKON_MERK:\d+\]/', '', $namaDiskonDisplay);
    ?>
    <div class="modal fade" id="editdiskon<?= $td->id_tmp_diskon ?>">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title">Edit Diskon</h4>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <?php echo form_open_multipart('edit_diskon_po'); ?>
                    <div class="form-group" hidden>
                        <div class="row">
                            <label class="col-sm-3 control-label text-right" for="kd_user">kode_suplier<span class="required">*</span></label>
                            <div class="col-sm-8"><input class="form-control" type="text" id="kd_sup" name="kd_sup" value="<?= $kdsuplier ?>" readonly /></div>
                            <div class="col-sm-8"><input class="form-control" type="text" id="id_isi" name="id_isi" value="<?= $td->id_tmp_diskon ?>" readonly /></div>
                            <div class="col-sm-8"><input class="form-control" type="text" id="row_marker" name="row_marker" value="<?= $rowMarker ?>" readonly /></div>
                            <div class="col-sm-8"><input class="form-control" type="text" id="merk_marker" name="merk_marker" value="<?= $merkMarker ?>" readonly /></div>
                            <div class="col-sm-8"><input class="form-control" type="text" id="satuan_marker" name="satuan_marker" value="<?= $satuanMarker ?>" readonly /></div>
                            <div class="col-sm-8"><input class="form-control" type="text" id="diskon_merk_marker" name="diskon_merk_marker" value="<?= $diskonMerkMarker ?>" readonly /></div>
                        </div>
                    </div>
                    <div class="form-group">
                        <div class="row">
                            <label class="col-sm-3 control-label text-right" for="kd_user">Deskripsi Diskon<span class="required">*</span></label>
                            <div class="col-sm-8"><input class="form-control" type="text" id="deskripsi_isi" name="deskripsi_isi" value="<?= $namaDiskonDisplay ?>" /></div>
                        </div>
                    </div>
                    <div class="form-group">
                        <div class="row">
                            <label class="col-sm-3 control-label text-right" for="kd_user">Nominal Diskon<span class="required">*</span></label>
                            <div class="col-sm-8"><input class="form-control number-format" type="text" inputmode="decimal" id="nominal_isi" name="nominal_isi" value="<?= rtrim(rtrim(number_format((float) $td->nominal, 12, ',', '.'), '0'), ',') ?>" autocomplete="off" /></div>
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

    <div class="modal fade" id="hapusdiskon<?= $td->id_tmp_diskon ?>">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title">Hapus Diskon</h4>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <?php echo form_open_multipart('hapus_diskon_po'); ?>
                    <div class="form-group" hidden>
                        <div class="row">
                            <label class="col-sm-3 control-label text-right" for="kd_user">kode_suplier<span class="required">*</span></label>
                            <div class="col-sm-8"><input class="form-control" type="text" id="kd_sup" name="kd_sup" value="<?= $kdsuplier ?>" readonly /></div>
                            <div class="col-sm-8"><input class="form-control" type="text" id="id_isi" name="id_isi" value="<?= $td->id_tmp_diskon ?>" readonly /></div>
                        </div>
                    </div>
                    <h3>Data akan terhapus secara permanen !!</h3>
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
<?php foreach ($tmpnote as $tn) : ?>
    <div class="modal fade" id="editnote<?= $tn->id_nt_tmp_barang ?>">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title">Edit Note Suplier</h4>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <?php echo form_open_multipart('edit_note_tmp_barang'); ?>
                    <div class="form-group" hidden>
                        <div class="row">
                            <label class="col-sm-3 control-label text-right" for="kd_user">kode_suplier<span class="required">*</span></label>
                            <div class="col-sm-8"><input class="form-control" type="text" id="kd_sup" name="kd_sup" value="<?= $kdsuplier ?>" readonly /></div>
                            <div class="col-sm-8"><input class="form-control" type="text" id="id_isi" name="id_isi" value="<?= $tn->id_nt_tmp_barang ?>" readonly /></div>
                        </div>
                    </div>
                    <div class="form-group">
                        <div class="row">
                            <label class="col-sm-3 control-label text-right" for="kd_user">Deskripsi Note<span class="required">*</span></label>
                            <div class="col-sm-8"><textarea name="isi" id="isi" cols="30" rows="10" class="form-control"><?= $tn->isi_note ?></textarea>
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
    <div class="modal fade" id="hapusnote<?= $tn->id_nt_tmp_barang ?>">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title">Hapus Note Suplier</h4>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <?php echo form_open_multipart('hapus_note_tmp_barang'); ?>
                    <div class="form-group" hidden>
                        <div class="row">
                            <label class="col-sm-3 control-label text-right" for="kd_user">kode_suplier<span class="required">*</span></label>
                            <div class="col-sm-8"><input class="form-control" type="text" id="kd_sup" name="kd_sup" value="<?= $kdsuplier ?>" readonly /></div>
                            <div class="col-sm-8"><input class="form-control" type="text" id="id_isi" name="id_isi" value="<?= $tn->id_nt_tmp_barang ?>" readonly /></div>
                        </div>
                    </div>
                    <h3>Data akan terhapus secara permanen !!</h3>
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
<?php foreach ($tmp as $d) : ?>
    <div class="modal fade" id="diskonBarangPersen<?= $d->id_tmp ?>">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title">Tambah Diskon Persentase Barang</h4>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <?php echo form_open_multipart('add_diskon_barang_tmp'); ?>
                    <div class="form-group">
                        <div class="row">
                            <label class="col-sm-2" for="kd_user">Persentase Diskon<span class="required">*</span></label>
                            <div class="col-sm-9">
                                <input class="form-control" type="number" id="disc_isi" name="disc_isi" value="" step=".01" />
                                <input class="form-control" type="text" id="id_tmp" name="id_tmp" value="<?= $d->id_tmp ?>" readonly hidden />
                                <input class="form-control" type="text" id="hrg_satuan_kecil" name="hrg_satuan_kecil" value="<?= $d->harga_satuan_kecil ?>" readonly hidden />
                                <input class="form-control" type="text" id="kdsup" name="kdsup" value="<?= $d->kode_suplier ?>" readonly hidden />
                                <input class="form-control" type="text" id="nmbarang" name="nmbarang" value="<?= $d->nama_barang ?>" readonly hidden />
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

<?php foreach ($tmp as $d) : ?>
    <div class="modal fade" id="diskonBarangNominal<?= $d->id_tmp ?>">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title">Tambah Diskon Nominal Barang</h4>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <?php echo form_open_multipart('add_diskon_barangs_tmp'); ?>
                    <div class="form-group">
                        <div class="row">
                            <label class="col-sm-3" for="kd_user">Deskripsi Diskon<span class="required">*</span></label>
                            <div class="col-sm-8">
                                <input class="form-control" type="text" id="desc_isi" name="desc_isi" value="" />
                                <input class="form-control" type="text" id="id_tmp" name="id_tmp" value="<?= $d->id_tmp ?>" readonly hidden />
                                <input class="form-control" type="text" id="kdsup" name="kdsup" value="<?= $d->kode_suplier ?>" readonly hidden />
                                <input class="form-control" type="text" id="nmbarang" name="nmbarang" value="<?= $d->nama_barang ?>" readonly hidden />
                            </div>

                        </div>
                    </div>
                    <div class="form-group">
                        <div class="row">
                            <label class="col-sm-3" for="kd_user">Nominal Diskon<span class="required">*</span></label>
                            <div class="col-sm-8">
                                <input class="form-control number-format" type="text" inputmode="decimal" id="disc_isi" name="disc_isi" value="" autocomplete="off" />
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
