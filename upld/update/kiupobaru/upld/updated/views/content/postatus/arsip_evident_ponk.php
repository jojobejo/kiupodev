<div class="content-wrapper">
    <div class="content-header">
        <div class="container-fluid">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h3 class="mb-0">Arsip Evident PO Non Komersil Selesai</h3>
                <a href="<?= base_url('postatusnk') ?>" class="btn btn-secondary btn-sm">
                    <i class="fas fa-arrow-left"></i> Kembali
                </a>
            </div>

            <div class="card">
                <div class="card-body">
                    <form method="get" action="<?= base_url('arsip-evident-ponk') ?>" class="row">
                        <div class="col-md-3">
                            <label>Tanggal Start</label>
                            <input type="date" name="tgl_start" class="form-control" value="<?= htmlspecialchars($filters['tgl_start'], ENT_QUOTES, 'UTF-8') ?>">
                        </div>
                        <div class="col-md-3">
                            <label>Tanggal End</label>
                            <input type="date" name="tgl_end" class="form-control" value="<?= htmlspecialchars($filters['tgl_end'], ENT_QUOTES, 'UTF-8') ?>">
                        </div>
                        <div class="col-md-3">
                            <label>Kode PO</label>
                            <input type="text" name="kd_po_nk" class="form-control" value="<?= htmlspecialchars($filters['kd_po_nk'], ENT_QUOTES, 'UTF-8') ?>">
                        </div>
                        <div class="col-md-3 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary mr-2">
                                <i class="fas fa-search"></i> Cari
                            </button>
                            <a href="<?= base_url('arsip-evident-ponk') ?>" class="btn btn-light">Reset</a>
                        </div>
                    </form>
                </div>
            </div>

            <div class="card">
                <div class="card-body">
                    <table class="table table-bordered table-striped" id="tballstatus">
                        <thead>
                            <tr>
                                <td>No</td>
                                <td>Kode PO</td>
                                <td>Nomor PO</td>
                                <td>Tanggal PO</td>
                                <td>Departemen</td>
                                <td>Jenis Evident</td>
                                <td>Keterangan</td>
                                <td>File</td>
                                <td>Upload</td>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $no = 1; ?>
                            <?php foreach ($arsip as $row) : ?>
                                <?php
                                $filePath = trim((string) $row->file_path);
                                $fileUrl = preg_match('#^https?://#', $filePath) ? $filePath : base_url($filePath);
                                ?>
                                <tr>
                                    <td><?= $no++ ?></td>
                                    <td>
                                        <a href="<?= base_url('detailponk/' . $row->kd_po_nk) ?>">
                                            <?= htmlspecialchars($row->kd_po_nk, ENT_QUOTES, 'UTF-8') ?>
                                        </a>
                                    </td>
                                    <td><?= htmlspecialchars($row->nopo, ENT_QUOTES, 'UTF-8') ?></td>
                                    <td><?= htmlspecialchars($row->tgl_transaksi, ENT_QUOTES, 'UTF-8') ?></td>
                                    <td><?= htmlspecialchars($row->departemen, ENT_QUOTES, 'UTF-8') ?></td>
                                    <td><span class="badge badge-info"><?= htmlspecialchars($row->jenis_evident, ENT_QUOTES, 'UTF-8') ?></span></td>
                                    <td><?= htmlspecialchars($row->keterangan, ENT_QUOTES, 'UTF-8') ?></td>
                                    <td>
                                        <a href="<?= $fileUrl ?>" target="_blank" class="btn btn-secondary btn-sm">
                                            <i class="fas fa-external-link-alt"></i> Buka
                                        </a>
                                    </td>
                                    <td><?= htmlspecialchars($row->user_upload, ENT_QUOTES, 'UTF-8') ?><br><small><?= htmlspecialchars($row->create_at, ENT_QUOTES, 'UTF-8') ?></small></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
