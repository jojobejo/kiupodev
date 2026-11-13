<div class="content-wrapper">
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                </div><!-- /.col -->
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="#">Dashboard</a></li>
                        <li class="breadcrumb-item active">User</li>
                    </ol>
                </div><!-- /.col -->
            </div><!-- /.row -->
            <div>
                <div class="card">
                    <div class="card-header">
                        <h3>Account Setting</h3>
                    </div>
                    <div class="card-body">
                        <?php foreach ($info as $i) : ?>
                            <div class="row">
                                <div class="col-md">
                                    <label class="col-sm-3 control-label" for="kd_user">Nama Pengguna<span class="required"></span></label>
                                    <input class="form-control" type="text" id="kode_isi" name="kode_isi" value="<?= $i->nama_user ?>" readonly />
                                </div>
                                <div class="col-md">
                                    <label class="col-sm-3 control-label" for="kd_user">Jabatan<span class="required"></span></label>
                                    <input class="form-control" type="text" id="kode_isi" name="kode_isi" value="<?= $i->departement ?>" readonly />
                                </div>
                            </div>
                            <div>
                                <label class="col-sm-3 control-label" for="kd_user">Username<span class="required"></span></label>
                                <input class="form-control" type="text" id="kode_isi" name="kode_isi" value="<?= $i->username ?>" />
                                <label class="col-sm-3 control-label" for="kd_user" hidden>Password<span class="required"></span></label>
                                <input class="form-control" type="password" id="kode_isi" name="kode_isi" value="kiudirektur" placeholder="" readonly hidden/>
                                <label class="col-sm-3 control-label" for="kd_user">Password Baru<span class="required"></span></label>
                                <?php echo form_open_multipart('editPass'); ?>
                                <input class="form-control" type="password" id="passbaru" name="passbaru" value="kiudirektur" />
                                <input class="form-control" type="text" id="kduser" name="kduser" value="<?= $i->kode_user ?>" hidden />
                            </div>
                            <div class="modal-footer">
                                <button type="submit" class="btn btn-primary btn-block">Simpan</button>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

        </div><!-- /.container-fluid -->
    </div>
    <!-- /.content-header -->
</div>