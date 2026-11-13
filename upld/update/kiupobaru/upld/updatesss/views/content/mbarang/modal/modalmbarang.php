<!-- MODAL ADD -->
<div class="modal fade" id="addmbarangnk">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">Add Master Barang Baru</h4>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <?php echo form_open_multipart('add_mbarang', array('id' => 'form_add_mbarang')); ?>
                <div class="form-group">
                    <div class="row">
                        <label class="col-sm-3 control-label text-right" for="add_kd_adm">Kode Barang<span class="required">*</span></label>
                        <div class="col-sm-8">
                            <input class="form-control" type="text" id="add_kd_isi" name="kd_isi" value="<?= $kdbarang ?>" hidden />
                            <input class="form-control" type="text" id="add_qrc_isi" name="qrc_isi" value="<?= $kdqrcode ?>" hidden />
                            <input class="form-control" type="text" id="add_kd_adm" name="kd_adm" value="" data-check-url="<?= base_url('masterbarangnk/check-kode') ?>" autocomplete="off" />
                            <small id="add_kd_adm_feedback" class="form-text d-none"></small>
                        </div>
                    </div>
                </div>
                <div class="form-group">
                    <div class="row">
                        <label class="col-sm-3 control-label text-right" for="add_skatbr">Kategori Barang<span class="required">*</span></label>
                        <div class="col-sm-8">
                            <select name="skatbr" id="add_skatbr" class="form-control">
                                <option value="0">Pilih kategori barang</option>
                                <?php foreach ($katbarang as $k) : ?>
                                    <option value="<?= $k->kd_kat ?>"><?= $k->nama_kategori ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="form-group">
                    <div class="row">
                        <label class="col-sm-3 control-label text-right" for="add_nmbarang">Nama Barang<span class="required">*</span></label>
                        <div class="col-sm-8">
                            <input class="form-control" type="text" id="add_nmbarang" name="nmbarang" value="" />
                        </div>
                    </div>
                </div>
                <div class="form-group">
                    <div class="row">
                        <label class="col-sm-3 control-label text-right" for="add_descisi">Deskripsi<span class="required">*</span></label>
                        <div class="col-sm-8"><textarea name="descisi" id="add_descisi" class="form-control"></textarea></div>
                    </div>
                </div>
                <div class="form-group">
                    <div class="row">
                        <label class="col-sm-3 control-label text-right" for="add_stuanbr">Satuan<span class="required">*</span></label>
                        <div class="col-sm-8">
                            <select name="stuanbr" id="add_stuanbr" class="form-control">
                                <?php foreach ($satuan as $s) : ?>
                                    <option value="<?= $s->id_satuan ?>"><?= $s->nm_satuan ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="form-group">
                    <div class="row">
                        <label class="col-sm-3 control-label text-right" for="add_minimum_stock">Minimum Stock<span class="required">*</span></label>
                        <div class="col-sm-8">
                            <input class="form-control" type="number" id="add_minimum_stock" name="minimum_stock" value="0" min="0" step="0.01" required />
                            <small class="form-text text-muted">Barang masuk daftar perlu PO saat stok sama dengan atau di bawah nilai ini.</small>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer justify-content-between">
                <button type="button" class="btn btn-default" data-dismiss="modal">Batal</button>
                <button type="submit" id="add_mbarang_submit" class="btn btn-primary">Simpan</button>
            </div>
            </form>
        </div>
    </div>
</div>

<!-- MODAL EDIT -->
<div class="modal fade" id="editbarang">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">Edit Data</h4>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <?php echo form_open_multipart('edit_mbarangnk'); ?>
                <div class="form-group">
                    <div class="row">
                        <label class="col-sm-3 control-label text-right" for="edit_kd_adm">Kode Barang<span class="required">*</span></label>
                        <div class="col-sm-8">
                            <input class="form-control" type="text" id="edit_id_isi" name="id_isi" value="" hidden />
                            <input class="form-control" type="text" id="edit_kd_adm" name="kd_adm" value="" />
                        </div>
                    </div>
                </div>
                <div class="form-group">
                    <div class="row">
                        <label class="col-sm-3 control-label text-right" for="edit_skatbr">Kategori Barang<span class="required">*</span></label>
                        <div class="col-sm-8">
                            <select name="skatbr" id="edit_skatbr" class="form-control">
                                <?php foreach ($katbarang as $k) : ?>
                                    <option value="<?= $k->kd_kat ?>"><?= $k->nama_kategori ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="form-group">
                    <div class="row">
                        <label class="col-sm-3 control-label text-right" for="edit_nmbarang">Nama Barang<span class="required">*</span></label>
                        <div class="col-sm-8">
                            <input class="form-control" type="text" id="edit_nmbarang" name="nmbarang" value="" />
                        </div>
                    </div>
                </div>
                <div class="form-group">
                    <div class="row">
                        <label class="col-sm-3 control-label text-right" for="edit_descisi">Deskripsi<span class="required">*</span></label>
                        <div class="col-sm-8"><textarea name="descisi" id="edit_descisi" class="form-control"></textarea></div>
                    </div>
                </div>
                <div class="form-group">
                    <div class="row">
                        <label class="col-sm-3 control-label text-right" for="edit_stuanbr">Satuan<span class="required">*</span></label>
                        <div class="col-sm-8">
                            <select name="stuanbr" id="edit_stuanbr" class="form-control">
                                <?php foreach ($satuan as $s) : ?>
                                    <option value="<?= $s->id_satuan ?>"><?= $s->nm_satuan ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="form-group">
                    <div class="row">
                        <label class="col-sm-3 control-label text-right" for="edit_minimum_stock">Minimum Stock<span class="required">*</span></label>
                        <div class="col-sm-8">
                            <input class="form-control" type="number" id="edit_minimum_stock" name="minimum_stock" value="0" min="0" step="0.01" required />
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
    </div>
</div>

<!-- MODAL DELETE -->
<div class="modal fade" id="hapusbarang">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">Hapus Data</h4>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <?php echo form_open_multipart('delmbarangnk'); ?>
                <div class="form-group">
                    <div class="row">
                        <div class="col-sm-8">
                            <input class="form-control" type="text" id="delete_id_isi" name="id_isi" value="" hidden />
                            <h3> MASTER BARANG AKAN TERHAPUS SECARA PERMANENT !!</h3>
                            <p id="delete_barang_name" class="mb-0"></p>
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
    </div>
</div>

<!-- MODAL UPLOAD -->
<div class="modal fade" id="uploadmbrang">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">Upload Gambar Barang</h4>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <?php echo form_open_multipart('uploadmbarangnk'); ?>
                <div class="form-group" hidden>
                    <div class="row">
                        <label class="col-sm-3 control-label text-right" for="upload_id_isi">Nama Barang<span class="required">*</span></label>
                        <input class="form-control" type="text" id="upload_id_isi" name="id_isi" value="" />
                        <input class="form-control" type="text" id="upload_file_nm" name="file_nm" value="">
                        <input class="form-control" type="text" id="upload_file_nms" name="file_nms" value="">
                    </div>
                </div>
                <div class="form-group">
                    <div class="input-group">
                        <div class="custom-file">
                            <label class="custom-file-label" for="upload_gambar_1">Choose file</label>
                            <input type="file" class="custom-file-input" id="upload_gambar_1" name="gambar_1">
                        </div>
                        <div class="input-group-append">
                            <span class="input-group-text">Upload</span>
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
    </div>
</div>
