<div class="content-wrapper">
    <div class="content-header">
        <div class="container-fluid">
            <?php foreach (array('success' => 'success', 'warning' => 'warning', 'error' => 'danger') as $flashKey => $alertClass) : ?>
                <?php if ($this->session->flashdata($flashKey)) : ?>
                    <div class="alert alert-<?= $alertClass ?> alert-dismissible fade show" role="alert">
                        <?= htmlspecialchars($this->session->flashdata($flashKey), ENT_QUOTES, 'UTF-8') ?>
                        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                <?php endif; ?>
            <?php endforeach; ?>

            <div class="d-flex justify-content-between align-items-center mb-3">
                <h3 class="mb-0">Stock Opname PO Non Komersil</h3>
                <a href="<?= base_url('stocknonkomersil') ?>" class="btn btn-secondary btn-sm">
                    <i class="fas fa-arrow-left"></i> Kembali
                </a>
            </div>

            <?php if (!$tables_ready) : ?>
                <div class="alert alert-warning">Tabel stock opname belum tersedia. Jalankan migration `db/2026/po_nonkomersil_tambahan_20260902.sql` terlebih dahulu.</div>
            <?php endif; ?>

            <div class="card">
                <div class="card-body">
                    <form action="<?= base_url('stockopnamenk/save') ?>" method="post">
                        <div class="row mb-3">
                            <div class="col-md-3">
                                <label>Tanggal Opname</label>
                                <input type="date" name="tgl_opname" class="form-control" value="<?= date('Y-m-d') ?>" required>
                            </div>
                            <div class="col-md-9">
                                <label>Catatan</label>
                                <input type="text" name="catatan" class="form-control" placeholder="Catatan stock opname">
                            </div>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-bordered table-striped" id="list_stocknonkomersil1">
                                <thead>
                                    <tr>
                                        <td>Kode Barang</td>
                                        <td>Nama Barang</td>
                                        <td>Stock Sistem</td>
                                        <td>Stock Fisik</td>
                                        <td>Satuan</td>
                                        <td>Keterangan</td>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($stock as $s) : ?>
                                        <tr>
                                            <td>
                                                <?= htmlspecialchars($s->kode_barang, ENT_QUOTES, 'UTF-8') ?>
                                                <input type="hidden" name="kode_barang[]" value="<?= htmlspecialchars($s->kode_barang, ENT_QUOTES, 'UTF-8') ?>">
                                                <input type="hidden" name="kode_barangs[]" value="<?= htmlspecialchars($s->kode_barangs, ENT_QUOTES, 'UTF-8') ?>">
                                                <input type="hidden" name="kat_barang[]" value="<?= htmlspecialchars($s->kat_barang, ENT_QUOTES, 'UTF-8') ?>">
                                            </td>
                                            <td>
                                                <?= htmlspecialchars($s->nama_barang, ENT_QUOTES, 'UTF-8') ?>
                                                <input type="hidden" name="nama_barang[]" value="<?= htmlspecialchars($s->nama_barang, ENT_QUOTES, 'UTF-8') ?>">
                                            </td>
                                            <td>
                                                <?= htmlspecialchars($s->qty_ready, ENT_QUOTES, 'UTF-8') ?>
                                                <input type="hidden" name="qty_sistem[]" value="<?= htmlspecialchars($s->qty_ready, ENT_QUOTES, 'UTF-8') ?>">
                                            </td>
                                            <td><input type="number" name="qty_fisik[]" class="form-control form-control-sm" min="0" step="0.01" value="<?= htmlspecialchars($s->qty_ready, ENT_QUOTES, 'UTF-8') ?>"></td>
                                            <td>
                                                <?= htmlspecialchars($s->satuan, ENT_QUOTES, 'UTF-8') ?>
                                                <input type="hidden" name="satuan[]" value="<?= htmlspecialchars($s->satuan, ENT_QUOTES, 'UTF-8') ?>">
                                                <input type="hidden" name="id_satuan[]" value="<?= htmlspecialchars($s->id_satuan, ENT_QUOTES, 'UTF-8') ?>">
                                            </td>
                                            <td><input type="text" name="keterangan_detail[]" class="form-control form-control-sm"></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>

                        <button type="submit" class="btn btn-primary" <?= $tables_ready ? '' : 'disabled' ?>>
                            <i class="fas fa-save"></i> Simpan Stock Opname
                        </button>
                    </form>
                </div>
            </div>

            <div class="card">
                <div class="card-body">
                    <h5>Histori Stock Opname</h5>
                    <table class="table table-bordered table-striped" id="tracking_list_nonkomersil">
                        <thead>
                            <tr>
                                <td>Tanggal</td>
                                <td>Total Item</td>
                                <td>Selisih Plus</td>
                                <td>Selisih Minus</td>
                                <td>User</td>
                                <td>#</td>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($opname as $o) : ?>
                                <tr>
                                    <td><?= htmlspecialchars($o->tgl_opname, ENT_QUOTES, 'UTF-8') ?></td>
                                    <td><?= (int) $o->total_item ?></td>
                                    <td><?= htmlspecialchars($o->total_selisih_plus, ENT_QUOTES, 'UTF-8') ?></td>
                                    <td><?= htmlspecialchars($o->total_selisih_minus, ENT_QUOTES, 'UTF-8') ?></td>
                                    <td><?= htmlspecialchars($o->created_name, ENT_QUOTES, 'UTF-8') ?></td>
                                    <td><a href="<?= base_url('stockopnamenk/detail/' . $o->id_stock_opname) ?>" class="btn btn-primary btn-sm"><i class="fas fa-eye"></i></a></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
