<?php foreach ($item as $i) : ?>
    <?php $formatQty = function ($qty) { return number_format((float) $qty, 0, ',', ''); }; ?>
    <?php $this->load->view('content/stock/nonkomersil/modal/modalstock.php') ?>
    <div class="content-wrapper">
        <div class="content-header">
            <div class="container-fluid">
                <div class="row mb-2 ml-2">
                    <div class="col-sm-0">
                        <h1 class="m-0">Detail Stock Item : <b style="text-transform:uppercase"><?= $i->nama_barang ?> (<?= $i->satuan ?>) (<?= $i->kode_barang ?>)</b></h1>
                    </div><!-- /.col -->
                    <div class="col-sm-0 ml-2">
                        <a href="<?= base_url('stocknonkomersil') ?>" class="btn btn-sm btn-block btn-success mt-1"><i class="fas fa-undo-alt"></i></a>
                    </div>
                </div><!-- /.row -->

                <div class="card">
                    <div class="card-body">
                        <div class="row align-items-center mb-2">
                            <div class="col-auto">
                                <h1 class="m-0">Stock Tersedia : <b style="text-transform:uppercase"><?= $i->qty_ready ?></b></h1>
                            </div>
                            <div class="col-auto">
                                <a class="btn btn-sm btn-success mt-1" data-toggle="modal" data-target="#adjustmentqty<?= $i->kode_sistem ?>">
                                    <i class="fas fa-plus"></i>
                                </a>
                            </div>

                            <div class="col-auto">
                                <form method="POST" action="<?= base_url('stock/filterqtybytgl'); ?>" class="form-inline">
                                    <label for="start_date" class="mr-2">Tanggal Mulai:</label>
                                    <input type="date" class="form-control mr-3" name="start_date" id="start_date" value="">
                                    <label for="end_date" class="mr-2">Tanggal Akhir:</label>
                                    <input type="date" name="end_date" class="form-control mr-3" id="end_date" value="">
                                    <input type="text" name="kdbarang" class="form-control mr-3" id="kdbarang" value="<?= $i->kode_sistem ?>" hidden>
                                    <button type="submit" class="btn btn-primary">Cari</button>
                                </form>
                            </div>
                        </div>

                        <!-- Tempat menampilkan hasil -->
                        <div id="result"></div>
                        <?php if ($lifo_summary !== null) : ?>
                            <div class="row mb-3">
                                <div class="col-lg-3 col-md-6"><div class="small-box bg-info"><div class="inner"><h4><?= $formatQty($lifo_summary->qty_batch) ?></h4><p>Qty Batch LIFO</p></div></div></div>
                                <div class="col-lg-3 col-md-6"><div class="small-box bg-success"><div class="inner"><h4><?= (int) $lifo_summary->batch_aktif ?></h4><p>Batch Aktif</p></div></div></div>
                                <div class="col-lg-3 col-md-6"><div class="small-box <?= $lifo_last_price !== null ? 'bg-info' : 'bg-secondary' ?>"><div class="inner"><h4><?= $lifo_last_price !== null ? 'Rp ' . number_format($lifo_last_price->harga_satuan, 0, ',', '.') : '-' ?></h4><p>Harga Satuan Terakhir</p></div></div></div>
                                <div class="col-lg-3 col-md-6"><div class="small-box <?= (float) $lifo_summary->qty_perlu_harga > 0 ? 'bg-danger' : 'bg-secondary' ?>"><div class="inner"><h4><?= $formatQty($lifo_summary->qty_perlu_harga) ?></h4><p>Qty Perlu Harga</p></div></div></div>
                            </div>
                            <div class="card card-outline card-info card-tabs mb-4">
                                <div class="card-header p-0 pt-1 border-bottom-0">
                                    <ul class="nav nav-tabs" role="tablist">
                                        <li class="nav-item"><a class="nav-link <?= !empty($history_tab_active) ? '' : 'active' ?>" data-toggle="tab" href="#tab-lapisan-lifo" role="tab">Lapisan Stok LIFO Tersisa</a></li>
                                        <li class="nav-item"><a class="nav-link <?= !empty($history_tab_active) ? 'active' : '' ?>" data-toggle="tab" href="#tab-histori-batch-lifo" role="tab">Histori Batch LIFO Semua Transaksi</a></li>
                                    </ul>
                                </div>
                                <div class="card-body p-0"><div class="tab-content">
                                    <div class="tab-pane <?= !empty($history_tab_active) ? '' : 'active' ?> p-3" id="tab-lapisan-lifo" role="tabpanel">
                            <table class="table table-bordered table-sm mb-0">
                                <thead style="background-color: #212529; color:white;"><tr><td>Urutan</td><td>Tanggal Masuk</td><td>Referensi</td><td>Sumber</td><td>Qty Awal</td><td>Qty Sisa</td><td>Harga / Unit</td><td>Harga Purchasing</td><td>Dasar Harga</td><td>Status Harga</td><?php if ($can_manage_lifo_price) : ?><td>#</td><?php endif; ?></tr></thead>
                                <tbody><?php foreach ($lifo_batches as $index => $batch) : ?><tr>
                                    <td><?= $index + 1 ?></td><td><?= $batch->tgl_efektif ?></td><td><?= $batch->referensi_sumber ?></td><td><?= $batch->jenis_sumber ?></td><td><?= $formatQty($batch->qty_awal) ?></td><td><?= $formatQty($batch->qty_sisa) ?></td>
                                    <td><?= $batch->status_harga === 'VALID' ? 'Rp ' . number_format($batch->harga_satuan, 0, ',', '.') : '<span class="badge badge-danger">Perlu harga</span>' ?></td>
                                    <td><?= $batch->harga_purchasing !== null ? 'Rp ' . number_format($batch->harga_purchasing, 0, ',', '.') : '-' ?></td>
                                    <td><?= htmlspecialchars($batch->label_dasar_harga, ENT_QUOTES, 'UTF-8') ?></td>
                                    <td><?= $batch->status_harga === 'VALID' ? '<span class="badge badge-success">Valid</span>' : '<span class="badge badge-danger">Perlu Harga</span>' ?></td>
                                    <?php if ($can_manage_lifo_price) : ?><td><?php if ($batch->status_harga === 'PERLU_HARGA') : ?><form method="post" action="<?= base_url('stocknonkomersil/save_lifo_price') ?>"><input type="hidden" name="id_batch" value="<?= $batch->id_batch ?>"><input type="hidden" name="kd_barang" value="<?= $i->kode_sistem ?>"><input class="form-control form-control-sm mb-1" name="harga_satuan" type="number" min="1" placeholder="Harga"><input class="form-control form-control-sm mb-1" name="alasan" required placeholder="Alasan"><button class="btn btn-sm btn-warning">Simpan</button></form><?php endif; ?></td><?php endif; ?>
                                </tr><?php endforeach; ?></tbody>
                            </table>
                                    </div>
                                    <div class="tab-pane <?= !empty($history_tab_active) ? 'active' : '' ?>" id="tab-histori-batch-lifo" role="tabpanel">
                                        <div class="table-responsive"><table class="table table-bordered table-sm mb-0">
                                            <thead style="background-color: #212529; color:white;"><tr><td>Tanggal Masuk</td><td>Referensi</td><td>Sumber</td><td>Qty Awal</td><td>Qty Sisa</td><td>Status Batch</td><td>Harga / Unit</td><td>Harga Purchasing</td><td>Dasar Harga</td><td>Status Harga</td></tr></thead>
                                            <tbody><?php if (empty($lifo_batch_history)) : ?><tr><td colspan="10" class="text-center text-muted">Belum ada histori batch LIFO.</td></tr><?php endif; ?><?php foreach ($lifo_batch_history as $batch) : ?><tr>
                                                <td><?= htmlspecialchars($batch->tgl_efektif, ENT_QUOTES, 'UTF-8') ?></td><td><?= htmlspecialchars($batch->referensi_sumber, ENT_QUOTES, 'UTF-8') ?></td><td><?= htmlspecialchars($batch->jenis_sumber, ENT_QUOTES, 'UTF-8') ?></td><td><?= $formatQty($batch->qty_awal) ?></td><td><?= $formatQty($batch->qty_sisa) ?></td><td><?= htmlspecialchars($batch->status_batch, ENT_QUOTES, 'UTF-8') ?></td>
                                                <td><?= $batch->status_harga === 'VALID' ? 'Rp ' . number_format($batch->harga_satuan, 0, ',', '.') : '<span class="badge badge-danger">Perlu harga</span>' ?></td><td><?= $batch->harga_purchasing !== null ? 'Rp ' . number_format($batch->harga_purchasing, 0, ',', '.') : '-' ?></td><td><?= htmlspecialchars($batch->label_dasar_harga, ENT_QUOTES, 'UTF-8') ?></td><td><?= $batch->status_harga === 'VALID' ? '<span class="badge badge-success">Valid</span>' : '<span class="badge badge-danger">Perlu Harga</span>' ?></td>
                                            </tr><?php endforeach; ?></tbody>
                                        </table></div>
                                        <?php if ($history_last_page > 1) : ?>
                                            <nav class="pt-3" aria-label="Navigasi histori batch LIFO"><ul class="pagination justify-content-center mb-2">
                                                <?php for ($page = 1; $page <= $history_last_page; $page++) : ?>
                                                    <li class="page-item <?= $page === $history_page ? 'active' : '' ?>"><a class="page-link" href="<?= base_url('detailtransaksi/' . rawurlencode($i->kode_sistem)) . '?history_page=' . $page ?>"><?= $page ?></a></li>
                                                <?php endfor; ?>
                                            </ul></nav>
                                        <?php endif; ?>
                                        <p class="text-center text-muted small mb-3">Total <?= (int) $history_total ?> batch &mdash; 5 data per halaman</p>
                                    </div>
                                </div></div>
                            </div>
                        <?php elseif ($lifo_last_price !== null) : ?>
                            <div class="alert alert-info mb-3">
                                <b>Harga LIFO terakhir:</b> Rp <?= number_format($lifo_last_price->harga_satuan, 0, ',', '.') ?>
                                &mdash; dasar harga <?= htmlspecialchars($lifo_last_price->dasar_harga, ENT_QUOTES, 'UTF-8') ?>,
                                referensi <?= htmlspecialchars($lifo_last_price->referensi_sumber, ENT_QUOTES, 'UTF-8') ?>.
                            </div>
                        <?php endif; ?>
                        <table class="table table-bordered mb-5">
                            <thead style="background-color: #212529; color:white;">
                                <tr>
                                    <td style="text-align: center;">Kode Transaksi</td>
                                    <td style="text-align: center; width: 5%;">Tanggal Transaksi</td>
                                    <td style="text-align: center;">Kode Akun</td>
                                    <td style="text-align: center;">Keterangan</td>
                                    <td style="text-align: center;">Departemen</td>
                                    <td style="text-align: center;">PIC</td>
                                    <td style="text-align: center;">Referensi</td>
                                    <td style="text-align: center;">Qty</td>
                                    <td style="text-align: center;">Harga Satuan</td>
                                    <td style="text-align: center;">Batch LIFO</td>
                                    <?php if ($this->session->userdata('kode') == 'KEU09') : ?>
                                        <td style="text-align: center;">#</td>
                                    <?php elseif ($this->session->userdata('kode') == 'KEU02') : ?>
                                        <td style="text-align: center;">#</td>
                                    <?php endif; ?>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($stock as $s) :
                                    $qs     = "";
                                    $btn    = "";
                                    $txt    = "";

                                    // KODE AKUN
                                    if ($s->kd_akun == '11512') {
                                        $qs     = "-";
                                        $btn    = "btn btn-block bg-cstm1 btn-sm color-palette";
                                        $txt    = 'Pengurangan Barang';
                                    } elseif ($s->kd_akun == '11511') {
                                        $qs     = '';
                                        $btn    = "btn btn-block btn-sm bg-cstm2 color-palette";
                                        $txt    = 'Penambahan Barang';
                                    } elseif ($s->kd_akun == '11513') {
                                        $qs     = '';
                                        $btn    = "btn btn-block btn-sm bg-cstm3 color-palette";
                                        $txt    = 'adjustmen stock(+)';
                                    } elseif ($s->kd_akun == '11514') {
                                        $qs     = '-';
                                        $btn    = "btn btn-block btn-sm bg-cstm4 color-palette";
                                        $txt    = 'adjustmen stock(-)';
                                    }
                                ?>
                                    <tr>
                                        <td style="text-align: center; width: 5%;"><?= $s->kd_transaksi ?></td>
                                        <td style="text-align: center;"><?= $s->tgl_transaksi ?></td>
                                        <td>
                                            <a href="#" class="<?= $btn ?>"><b style="text-transform:uppercase; color: #212529;"><?= $txt ?></b></a>
                                        </td>
                                        <td style="text-align: center;"><?= $s->ket ?></td>
                                        <?php if ($s->lvusr == "") : ?>
                                            <td style="text-align: center;">ADMIN</td>
                                            <td style="text-align: center;"><?= $s->inpt ?></td>
                                        <?php else : ?>
                                            <td style="text-align: center;"><?= $s->dep ?></td>
                                            <td style="text-align: center;"><?= $s->nmreq ?></td>
                                        <?php endif; ?>
                                        <td style="text-align: center;">
                                            <?php $referenceCode = trim((string) $s->kode_referensi); ?>
                                            <?php if ($s->tipe_referensi === 'PEMBELIAN') : ?>
                                                <a href="<?= base_url('detailponk/' . rawurlencode($referenceCode)) ?>" class="btn btn-outline-primary btn-sm" title="Lihat detail PO Pembelian">
                                                    <i class="fas fa-shopping-cart"></i> PO
                                                </a>
                                            <?php elseif ($s->tipe_referensi === 'PENGAMBILAN') : ?>
                                                <a href="<?= base_url('reqpic/detreqbarangpic/' . rawurlencode($referenceCode)) ?>" class="btn btn-outline-success btn-sm" title="Lihat detail pengambilan barang">
                                                    <i class="fas fa-dolly"></i> Pengambilan
                                                </a>
                                            <?php elseif ($referenceCode !== '') : ?>
                                                <span class="text-muted small" title="Referensi historis tidak ditemukan"><?= htmlspecialchars($referenceCode, ENT_QUOTES, 'UTF-8') ?></span>
                                            <?php else : ?>
                                                <span class="text-muted">-</span>
                                            <?php endif; ?>
                                        </td>
                                        <td style="text-align: center;"><?= $qs . $formatQty($s->qty) . " " . "(" . $s->nm_satuan . ")" ?></td>
                                        <td style="text-align: center;"><?= $s->nominal_lifo !== null ? 'Rp ' . number_format($s->nominal_lifo, 0, ',', '.') : '-' ?></td>
                                        <td style="text-align: center;" class="small"><?= htmlspecialchars($s->batch_lifo ?: '-', ENT_QUOTES, 'UTF-8') ?></td>
                                        <?php if ($this->session->userdata('kode') == 'KEU09') : ?>
                                            <td style="text-align: center;">
                                                <a href="<?= base_url('tr_trash/1/') . $s->id ?>" class="btn btn-danger btn-sm"><i class="fa fa-trash-alt"></i></a>
                                            </td>
                                        <?php elseif ($this->session->userdata('kode') == 'KEU02') : ?>
                                            <td style="text-align: center;">
                                                <a href="<?= base_url('tr_trash/1/') . $s->id ?>" class="btn btn-danger btn-sm"><i class="fa fa-trash-alt"></i></a>
                                            </td>
                                        <?php endif; ?>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        <?php if (!empty($pagination)) : ?>
                            <div class="mt-3">
                                <p class="text-center text-muted mb-2">Total <?= (int) $transaction_total ?> transaksi &mdash; 10 data per halaman</p>
                                <?= $pagination ?>
                            </div>
                        <?php endif; ?>

                        <table class="table table-bordered mt-2">
                            <thead style="background-color: #212529; color:white;">
                                <tr>
                                    <td style="text-align: center;">Kode Transaksi</td>
                                    <td style="text-align: center; width: 5%;">Tanggal Transaksi</td>
                                    <td style="text-align: center;">Kode Akun</td>
                                    <td style="text-align: center;">Keterangan</td>
                                    <td style="text-align: center;">Departemen</td>
                                    <td style="text-align: center;">PIC</td>
                                    <td style="text-align: center;">Qty</td>
                                    <td style="text-align: center;">#</td>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($trash as $trh) : ?>
                                    <tr>
                                        <td><?= $trh->kd_po_nk ?></td>
                                        <td><?= $trh->create_at ?></td>
                                        <td><?= $trh->kd_akun ?></td>
                                        <td><?= $trh->keterangan ?></td>
                                        <td><?= $trh->departemen ?></td>
                                        <td><?= $trh->nm_user ?></td>
                                        <td><?= $trh->tr_qty ?></td>
                                        <td style="text-align: center;"><a href="<?= base_url('tr_trash/2/') . $trh->id_trashbin ?>" class="btn btn-warning btn-sm"><i class="fa fa-redo-alt"></i></a></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="row mr-2">
                    <div class="col-md-8">
                        <h1><b style="text-transform:uppercase">Stock Tracking</b></h1>
                        <div class="noteDirektur">
                            <table class="table table-bordered table-stripeds">
                                <thead style="background-color: #212529; color:white;">
                                    <tr>
                                        <td class="tdnote">ISI NOTE</td>
                                        <td class="tduser">USER</td>
                                        <td style="text-align: center;">TANGGAL</td>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($note as $n) : ?>
                                        <tr>
                                            <td><?= $n->isi_note ?></td>
                                            <td><?= $n->nama_user ?></td>
                                            <td><?= $n->create_at ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div><!-- /.container-fluid -->
        </div>
        <!-- /.content-header -->
    </div>
<?php endforeach; ?>
