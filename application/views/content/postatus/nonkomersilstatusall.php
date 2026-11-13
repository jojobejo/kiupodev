<div class="content-wrapper">
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0">PO STATUS</h1>
                </div><!-- /.col -->
            </div><!-- /.row -->
            <table class="table table-bordered table-striped" id="tballstatus">
                <thead>
                    <tr>
                        <td>No</td>
                        <td>Nomor PO</td>
                        <td>Status Order</td>
                        <td>Tanggal PO</td>
                        <td>Nama Pembuat</td>
                        <td>Departement</td>
                        <td>Tujuan Pembelian</td>
                        <td></td>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $no = 1;
                    foreach ($po as $p) : ?>
                        <tr>
                            <td><?= $no++; ?></td>
                            <td><?= $p->nopo ?></td>
                            <td>
                                <div class="row">
                                    <div class="col-md">
                                        <?php if ($p->status == 'ON PROGRESS') : ?>
                                            <a class="btn btn-block btn-warning btn-sm">
                                                <i class="fas fa-clock"></i>&nbsp;
                                                <?= $p->status ?>
                                            </a>
                                        <?php elseif ($p->status == 'NOTE DIREKTUR' && $this->session->userdata('lv') == '2') : ?>
                                            <a class="btn btn-block btn-warning btn-sm">
                                                <i class="fas fa-exclamation"></i>&nbsp;
                                                Terdapat Update Dari Direktur
                                            </a>
                                        <?php elseif ($p->status == 'NOTE DIREKTUR' && $this->session->userdata('lv') == '3') : ?>
                                            <a class="btn btn-block btn-warning btn-sm">
                                                <i class="fas fa-clock"></i>&nbsp;
                                                ON PROGRESS
                                            </a>
                                        <?php elseif ($p->status == 'NOTE KEUANGAN' && $this->session->userdata('lv') == '3') : ?>
                                            <a class="btn btn-block btn-warning btn-sm">
                                                <i class="fas fa-exclamation"></i>&nbsp;
                                                Terdapat Update Dari Keuangan
                                            </a>
                                        <?php elseif ($p->status == 'NOTE KEUANGAN' && $this->session->userdata('lv') == '2') : ?>
                                            <a class="btn btn-block btn-warning btn-sm">
                                                <i class="fas fa-clock"></i>&nbsp;
                                                ON PROGRESS
                                            </a>
                                        <?php elseif ($p->status == 'ON PROGRESS - KADEP' && $this->session->userdata('lv') == '2') : ?>
                                            <a class="btn btn-block btn-warning btn-sm">
                                                <i class="fas fa-clock"></i>&nbsp;
                                                MENUNGGU ACC KADEP
                                            </a>
                                        <?php elseif ($p->status == 'DONE') : ?>
                                            <a class="btn btn-block btn-success btn-sm">
                                                <i class="fas fa-thumbs-up"></i>&nbsp;
                                                <?= $p->status ?>
                                            </a>
                                    <?php elseif ($p->status == 'REJECT') : ?>
                                        <a class="btn btn-block btn-danger btn-sm">
                                            <i class="fas fa-times"></i>&nbsp;
                                            <?= $p->status ?>
                                        </a>
                                    <?php elseif ($p->status == 'PENGAJUAN DIBATALKAN') : ?>
                                        <a class="btn btn-block btn-danger btn-sm">
                                            <i class="fas fa-times-circle"></i>&nbsp;
                                            <?= $p->status ?>
                                        </a>
                                    <?php elseif ($p->status == 'PO REVISI') : ?>
                                        <a class="btn btn-block btn-warning btn-sm">
                                            <i class="fas fa-undo"></i>&nbsp;
                                            <?= $p->status ?>
                                        </a>
                                        <?php elseif ($p->status == 'PENDING') : ?>
                                            <a class="btn btn-block btn-warning btn-sm">
                                                <i class="fas fa-pause"></i>&nbsp;
                                                <?= $p->status ?>
                                            </a>
                                        <?php elseif ($p->status == 'ACC-KADEP') : ?>
                                            <a class="btn btn-block btn-primary btn-sm">
                                                <i class="fas fa-thumbs-up"></i>&nbsp;
                                                <?= $p->status ?>
                                            </a>
                                        <?php elseif ($p->status == 'ON PROGRESS - KADEP' && $this->session->userdata('lv') == '5') : ?>
                                            <a class="btn btn-block btn-warning btn-sm">
                                                <i class="fas fa-clock"></i>&nbsp;
                                                MENUNGGU ACC KADEP
                                            </a>
                                        <?php elseif ($p->status == 'ON PROGRESS - KADEP' && $this->session->userdata('lv') == '4') : ?>
                                            <a class="btn btn-block btn-warning btn-sm">
                                                <i class="fas fa-clock"></i>&nbsp;
                                                MENUNGGU ACC KADEP
                                            </a>
                                        <?php elseif ($p->status == 'SEDANG DIAJUKAN' && $this->session->userdata('lv') != '3') : ?>
                                            <a class="btn btn-block btn-warning btn-sm">
                                                <i class="fas fa-clock"></i>&nbsp;
                                                <?= $p->status ?>
                                            </a>
                                        <?php elseif ($p->status == 'SEDANG DIAJUKAN' && $this->session->userdata('lv') == '3') : ?>
                                            <a class="btn btn-block btn-warning btn-sm">
                                                <i class="fas fa-clock"></i>&nbsp;
                                                PENGAJUAN PEMBELIAN BARU
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </td>
                            <td><?= format_indo($p->tgl_transaksi) ?></td>
                            <td><?= $p->nama_user ?></td>
                            <td><?= $p->departement ?></td>
                            <td><?= $p->tj_pembelian ?></td>
                            <td>
                                <div class="row">
                                    <div class="col-md">
                                        <a class="btn btn-block btn-primary btn-sm" href="<?= base_url('detailponk/') . $p->kd_po_nk ?>">
                                            <i class="fas fa-eye"></i>
                                            Detail
                                        </a>
                                    </div>
                                    <?php if ($this->session->userdata('lv') == '1') : ?>
                                        <div class="col-md">
                                            <a class="btn btn-block btn-success btn-sm" href="<?= base_url('konfirmasiOrderNK/') . $p->kd_po_nk ?>">
                                                <i class="fas fa-clipboard-check"></i>
                                                Accept
                                            </a>
                                        </div>
                                        <div class="col-md">
                                            <a class="btn btn-block btn-warning btn-sm" href="<?= base_url('tolakOrderNK/') . $p->kd_po_nk ?>">
                                                <i class="fas fa-times"></i>
                                                Reject
                                            </a>
                                        </div>
                                    <?php elseif ($this->session->userdata('lv') == '2' && $p->status == 'REJECT') : ?>
                                        <div class="col">
                                            <a class="btn btn-block btn-info btn-sm" href="<?= base_url('unpostponk/') . $p->kd_po_nk ?>">
                                                <i class="fas fa-sync"></i> &nbsp;
                                                UNPOST
                                            </a>
                                        </div>
                                        <div class="col">
                                            <a class="btn btn-block btn-warning btn-sm" href="<?= base_url('hapusponk/') . $p->kd_po_nk ?>">
                                                <i class="fas fa-trash"></i> &nbsp;
                                                DELETE
                                            </a>
                                        </div>
                                    <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div><!-- /.container-fluid -->
    </div>
    <!-- /.content-header -->
</div>
