<div class="content-wrapper">
    <div class="content-header">
        <div class="container-fluid">
            <?php if ($this->session->flashdata('success')) : ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?= htmlspecialchars($this->session->flashdata('success'), ENT_QUOTES, 'UTF-8') ?>
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
            <?php endif; ?>

            <div class="d-flex justify-content-between align-items-center mb-3">
                <h3 class="mb-0">Detail Stock Opname</h3>
                <a href="<?= base_url('stockopnamenk') ?>" class="btn btn-secondary btn-sm">
                    <i class="fas fa-arrow-left"></i> Kembali
                </a>
            </div>

            <div class="card">
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3">
                            <label>Tanggal Opname</label>
                            <input type="text" class="form-control" value="<?= htmlspecialchars($header->tgl_opname, ENT_QUOTES, 'UTF-8') ?>" readonly>
                        </div>
                        <div class="col-md-3">
                            <label>Total Item</label>
                            <input type="text" class="form-control" value="<?= (int) $header->total_item ?>" readonly>
                        </div>
                        <div class="col-md-3">
                            <label>Selisih Plus</label>
                            <input type="text" class="form-control" value="<?= htmlspecialchars($header->total_selisih_plus, ENT_QUOTES, 'UTF-8') ?>" readonly>
                        </div>
                        <div class="col-md-3">
                            <label>Selisih Minus</label>
                            <input type="text" class="form-control" value="<?= htmlspecialchars($header->total_selisih_minus, ENT_QUOTES, 'UTF-8') ?>" readonly>
                        </div>
                    </div>
                    <div class="mt-3">
                        <label>Catatan</label>
                        <textarea class="form-control" rows="2" readonly><?= htmlspecialchars($header->catatan, ENT_QUOTES, 'UTF-8') ?></textarea>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-body">
                    <table class="table table-bordered table-striped" id="list_stocknonkomersil1">
                        <thead>
                            <tr>
                                <td>Kode Barang</td>
                                <td>Nama Barang</td>
                                <td>Qty Sistem</td>
                                <td>Qty Fisik</td>
                                <td>Selisih</td>
                                <td>Satuan</td>
                                <td>Keterangan</td>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($detail as $d) : ?>
                                <tr>
                                    <td><?= htmlspecialchars($d->kode_barang, ENT_QUOTES, 'UTF-8') ?></td>
                                    <td><?= htmlspecialchars($d->nama_barang, ENT_QUOTES, 'UTF-8') ?></td>
                                    <td><?= htmlspecialchars($d->qty_sistem, ENT_QUOTES, 'UTF-8') ?></td>
                                    <td><?= htmlspecialchars($d->qty_fisik, ENT_QUOTES, 'UTF-8') ?></td>
                                    <td>
                                        <span class="badge <?= (float) $d->selisih < 0 ? 'badge-danger' : ((float) $d->selisih > 0 ? 'badge-success' : 'badge-secondary') ?>">
                                            <?= htmlspecialchars($d->selisih, ENT_QUOTES, 'UTF-8') ?>
                                        </span>
                                    </td>
                                    <td><?= htmlspecialchars($d->satuan, ENT_QUOTES, 'UTF-8') ?></td>
                                    <td><?= htmlspecialchars($d->keterangan, ENT_QUOTES, 'UTF-8') ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
