<div class="content-wrapper">
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2 ml-2">
                <div class="col-sm-6">
                    <h1 class="m-0"><b style="text-transform:uppercase">Master Lokasi</b></h1>
                </div>
            </div>

            <div class="card">
                <div class="card-body">
                    <button class="btn btn-success mb-3" data-toggle="modal" data-target="#modalAddLokasi">
                        <i class="fas fa-plus"></i> Tambah Lokasi
                    </button>

                    <table class="table table-bordered table-striped" id="list_stocknonkomersil1">
                        <thead>
                            <tr>
                                <th width="10%">No</th>
                                <th>Nama Lokasi</th>
                                <th width="25%">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $no = 1; ?>
                            <?php foreach ($lokasi as $l) : ?>
                                <tr>
                                    <td><?= $no++; ?></td>
                                    <td><?= $l->nama_lokasi; ?></td>
                                    <td>
                                        <button class="btn btn-warning btn-sm" data-toggle="modal" data-target="#modalEditLokasi<?= $l->id_lokasi; ?>">
                                            Edit
                                        </button>
                                        <button class="btn btn-danger btn-sm" data-toggle="modal" data-target="#modalHapusLokasi<?= $l->id_lokasi; ?>">
                                            Hapus
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalAddLokasi">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">Tambah Lokasi</h4>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <?= form_open('add_master_lokasi'); ?>
            <div class="modal-body">
                <div class="form-group">
                    <label>Nama Lokasi</label>
                    <input type="text" name="nama_lokasi" class="form-control" required>
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

<?php foreach ($lokasi as $l) : ?>
    <div class="modal fade" id="modalEditLokasi<?= $l->id_lokasi; ?>">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title">Edit Lokasi</h4>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <?= form_open('edit_master_lokasi'); ?>
                <div class="modal-body">
                    <input type="hidden" name="id_lokasi" value="<?= $l->id_lokasi; ?>">
                    <div class="form-group">
                        <label>Nama Lokasi</label>
                        <input type="text" name="nama_lokasi" class="form-control" value="<?= $l->nama_lokasi; ?>" required>
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

    <div class="modal fade" id="modalHapusLokasi<?= $l->id_lokasi; ?>">
        <div class="modal-dialog modal-confirm">
            <div class="modal-content">
                <div class="modal-header flex-column">
                    <h4 class="modal-title w-100">Hapus Lokasi</h4>
                    <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                </div>
                <div class="modal-body">
                    <p>Apakah anda yakin menghapus lokasi <?= $l->nama_lokasi; ?>?</p>
                </div>
                <div class="modal-footer justify-content-center">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                    <a href="<?= base_url('hapus_master_lokasi/' . $l->id_lokasi); ?>" class="btn btn-danger">Hapus</a>
                </div>
            </div>
        </div>
    </div>
<?php endforeach; ?>
