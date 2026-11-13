<!-- REQUEST MASTER BARANG -->
<?php foreach ($listreqbr as $l) : ?>
    <div class="modal fade" id="approvedbrgnk<?= $l->id_reqmbarang ?>">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title">Request Add Master Barang Baru</h4>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <?php echo form_open_multipart('aprovedmasterbarang'); ?>
                    <div class="form-group">
                        <div class="row">
                            <label class="col-sm-3 control-label text-right" for="kd_user">Input Kode Barang<span class="required">*</span></label>
                            <div class="col-sm-8">
                                <input class="form-control" type="text" id="kdbarang" name="kdbarang" value="" placeholder="Input Kode Barang" />
                                <input class="form-control" type="text" id="syskdbarangs" name="syskdbarang" value="<?= $kdbarang ?>" hidden />
                                <input class="form-control" type="text" id="admkd" name="admkd" value="<?= $kdqrcode ?>" hidden />
                                <input class="form-control" type="text" id="idreq" name="idreq" value="<?= $l->id_reqmbarang ?>" hidden />
                                <input class="form-control" type="text" id="nmbarang" name="nmbarang" value="<?= $l->nama_barang ?>" hidden/>
                                <input class="form-control" type="text" id="descnk" name="descnk" value="<?= $l->deskripsi ?>" hidden />
                                <input class="form-control" type="text" id="satuanisi" name="satuanisi" value="<?= $l->satuan ?>" hidden />
                                <input class="form-control" type="text" id="reqby" name="reqby" value="<?= $l->req_by ?>" hidden />
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <div class="row">
                            <label class="col-sm-3 control-label text-right" for="kd_user">Kategori Barang<span class="required">*</span></label>
                            <div class="col-sm-8">
                                <select name="katbr" id="katbr" class="form-control">
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
                            <label class="col-sm-3 control-label text-right">Minimum Stock<span class="required">*</span></label>
                            <div class="col-sm-8">
                                <input class="form-control" type="number" name="minimum_stock" value="0" min="0" step="0.01" required />
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

<div class="modal fade" id="reqmnumb">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">Add Request Master Barang</h4>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <?php echo form_open_multipart('add_mbarang_tmps'); ?>
                <div class="form-group" hidden>
                    <div class="row">
                        <label class="col-sm-3 control-label text-right" for="kd_user">Kode Barang<span class="required">*</span></label>
                        <div class="col-sm-8">
                            <input class="form-control" type="text" id="kd_isi" name="kd_isi" value="<?= $kdbarang ?>" hidden />
                        </div>
                    </div>
                </div>
                <div class="form-group">
                    <div class="row">
                        <label class="col-sm-3 control-label text-right" for="kd_user">Nama Barang<span class="required">*</span></label>
                        <div class="col-sm-8">
                            <input class="form-control" type="text" id="nmbarang" name="nmbarang" value="" placeholder="Inputkan Nama Barang" />
                        </div>
                    </div>
                </div>
                <div class="form-group">
                    <div class="row">
                        <label class="col-sm-3 control-label text-right" for="kd_user">Deskripsi<span class="required">*</span></label>
                        <div class="col-sm-8"><textarea name="descisi" id="descisi" class="form-control" placeholder="Inputkan deskripsi barang"></textarea></div>
                    </div>
                </div>
                <div class="form-group">
                    <div class="row">
                        <label class="col-sm-3 control-label text-right" for="kd_user">Satuan<span class="required">*</span></label>
                        <div class="col-sm-8">
                            <select name="stuanbr" id="stuanbr" class="form-control">
                                <option value="0" disabled selected>Pilih Satuan Barang</option>
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
