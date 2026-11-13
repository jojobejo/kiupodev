<div class="modal fade" id="modalAddUser">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">Tambah User Baru</h4>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <?php echo form_open_multipart('adduser'); ?>
                <div class="form-group">
                    <div class="row">
                        <label class="col-sm-3 control-label text-right" for="kd_user">Kode User<span class="required">*</span></label>
                        <div class="col-sm-8"><input class="form-control" type="text" id="kode_isi" name="kode_isi" value="" /></div>
                    </div>
                </div>
                <div class="form-group">
                    <div class="row">
                        <label class="col-sm-3 control-label text-right" for="id_bar">Nama User<span class="required">*</span></label>
                        <div class="col-sm-8"><input class="form-control" type="text" id="nama_isi" name="nama_isi" value="" placeholder="input Nama Pengguna" /></div>
                    </div>
                </div>
                <div class="form-group">
                    <div class="row">
                        <label class="col-sm-3 control-label text-right" for="id_bar">Departemen<span class="required">*</span></label>
                        <div class="col-sm-8">
                            <select class="form-control" name="departemen_isi" id="departemen_isi">
                                <option value="-" selected>-- Pilih Departemen --</option>
                                <option value="ADMIN">ADMIN</option>
                                <option value="KEUANGAN">KEUANGAN</option>
                                <option value="PURCHASING">PURCHASING</option>
                                <option value="DIREKTUR">DIREKTUR</option>
                                <option value="DIREKTUR OPRASIONAL">DIREKTUR OPRASIONAL</option>
                                <option value="IT">IT</option>
                                <option value="SALES">SALES</option>
                                <option value="LOGISTIK">LOGISTIK</option>
                                <option value="HRD">HRD</option>
                                <option value="GA">GA</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="form-group">
                    <div class="row">
                        <label class="col-sm-3 control-label text-right" for="id_bar">Username<span class="required">*</span></label>
                        <div class="col-sm-8"><input class="form-control" type="text" id="username_isi" name="username_isi" value="" placeholder="Input Username" /></div>
                    </div>
                </div>
                <div class="form-group">
                    <div class="row">
                        <label class="col-sm-3 control-label text-right" for="id_bar">Password<span class="required">*</span></label>
                        <div class="col-sm-8"><input class="form-control" type="password" id="password_isi" name="password_isi" value="" placeholder="Input Password" /></div>
                    </div>
                </div>
                <div class="form-group">
                    <div class="row">
                        <label class="col-sm-3 control-label text-right" for="id_bar">Level User<span class="required">*</span></label>
                        <div class="col-sm-8"><select class="form-control" name="level_isi" id="level_isi">
                                <option value="1">1 - Admin Full Akses</option>
                                <option value="2">2 - Purchasing/Keuangan</option>
                                <option value="3">3 - Direktur</option>
                                <option value="4">4 - PIC/Inputer</option>
                                <option value="5">5 - Kadep</option>
                                <option value="6">6 - Direktur Oprasional</option>
                            </Select></div>
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

<?php
$departemenOptions = array('ADMIN', 'KEUANGAN', 'PURCHASING', 'DIREKTUR', 'DIREKTUR OPRASIONAL', 'IT', 'SALES', 'LOGISTIK', 'HRD', 'GA');
$levelOptions = array(
    '1' => '1 - Admin Full Akses',
    '2' => '2 - Purchasing/Keuangan',
    '3' => '3 - Direktur',
    '4' => '4 - PIC/Inputer',
    '5' => '5 - Kadep',
    '6' => '6 - Direktur Oprasional',
);
?>

<?php foreach ($user as $u) : ?>
<div class="modal fade" id="modalViewUser<?= $u->id_user ?>">
    <div class="modal-dialog modal-md">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">Detail User</h4>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <dl class="row mb-0">
                    <dt class="col-sm-4">Kode User</dt>
                    <dd class="col-sm-8"><?= html_escape($u->kode_user) ?></dd>
                    <dt class="col-sm-4">Nama User</dt>
                    <dd class="col-sm-8"><?= html_escape($u->nama_user) ?></dd>
                    <dt class="col-sm-4">Username</dt>
                    <dd class="col-sm-8"><?= html_escape($u->username) ?></dd>
                    <dt class="col-sm-4">Departemen</dt>
                    <dd class="col-sm-8"><?= html_escape($u->departement) ?></dd>
                    <dt class="col-sm-4">Level</dt>
                    <dd class="col-sm-8"><?= isset($levelOptions[(string) $u->aksess_lv]) ? $levelOptions[(string) $u->aksess_lv] : html_escape($u->aksess_lv) ?></dd>
                </dl>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalEditUser<?= $u->id_user ?>">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">Edit User</h4>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <?php echo form_open_multipart('edituser'); ?>
            <div class="modal-body">
                <input type="hidden" name="id_isi" value="<?= $u->id_user ?>">
                <div class="form-group">
                    <div class="row">
                        <label class="col-sm-3 control-label text-right">Kode User<span class="required">*</span></label>
                        <div class="col-sm-8"><input class="form-control" type="text" name="kode_isi" value="<?= html_escape($u->kode_user) ?>" required /></div>
                    </div>
                </div>
                <div class="form-group">
                    <div class="row">
                        <label class="col-sm-3 control-label text-right">Nama User<span class="required">*</span></label>
                        <div class="col-sm-8"><input class="form-control" type="text" name="nama_isi" value="<?= html_escape($u->nama_user) ?>" required /></div>
                    </div>
                </div>
                <div class="form-group">
                    <div class="row">
                        <label class="col-sm-3 control-label text-right">Departemen<span class="required">*</span></label>
                        <div class="col-sm-8">
                            <select class="form-control" name="departemen_isi" required>
                                <?php foreach ($departemenOptions as $dep) : ?>
                                    <option value="<?= $dep ?>" <?= strtoupper($u->departement) === $dep ? 'selected' : '' ?>><?= $dep ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="form-group">
                    <div class="row">
                        <label class="col-sm-3 control-label text-right">Username<span class="required">*</span></label>
                        <div class="col-sm-8"><input class="form-control" type="text" name="username_isi" value="<?= html_escape($u->username) ?>" required /></div>
                    </div>
                </div>
                <div class="form-group">
                    <div class="row">
                        <label class="col-sm-3 control-label text-right">Password Baru</label>
                        <div class="col-sm-8"><input class="form-control" type="password" name="password_isi" value="" placeholder="Kosongkan jika tidak diganti" /></div>
                    </div>
                </div>
                <div class="form-group">
                    <div class="row">
                        <label class="col-sm-3 control-label text-right">Level User<span class="required">*</span></label>
                        <div class="col-sm-8">
                            <select class="form-control" name="level_isi" required>
                                <?php foreach ($levelOptions as $lv => $label) : ?>
                                    <option value="<?= $lv ?>" <?= (string) $u->aksess_lv === (string) $lv ? 'selected' : '' ?>><?= $label ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer justify-content-between">
                <button type="button" class="btn btn-default" data-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
            </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="modalDeleteUser<?= $u->id_user ?>">
    <div class="modal-dialog modal-md">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">Hapus User</h4>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <p>Hapus user <strong><?= html_escape($u->nama_user) ?></strong>?</p>
            </div>
            <div class="modal-footer justify-content-between">
                <button type="button" class="btn btn-default" data-dismiss="modal">Batal</button>
                <a href="<?= base_url('deleteuser/' . $u->id_user) ?>" class="btn btn-danger">Hapus</a>
            </div>
        </div>
    </div>
</div>
<?php endforeach; ?>
