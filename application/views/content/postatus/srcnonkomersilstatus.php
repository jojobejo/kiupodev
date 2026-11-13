<div class="content-wrapper">
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0">PO STATUS</h1>
                </div><!-- /.col -->
            </div><!-- /.row -->
            <!-- NEW LINE -->
            <?php if ($this->session->userdata('lv') == '2') : ?>
                <div class="card">
                    <div class="card-body">
                        <?php echo form_open_multipart('srcponkbytgl'); ?>
                        <div class="row">
                            <div class="col-sm-6">
                                <div class="form-group">
                                    <label>Tanggal Start :</label>
                                    <input type="date" class="form-control" placeholder="Tanggal Transaksi" value="" name="tglstart" id="tglstart">
                                </div>
                            </div><!-- /.col -->
                            <div class="col-sm-6">
                                <div class="form-group">
                                    <label>Tanggal End :</label>
                                    <input type="date" class="form-control" placeholder="Tanggal Transaksi" value="" name="tglend" id="tglend">
                                </div>
                            </div><!-- /.col -->
                        </div><!-- /.row -->
                        <div class="mb-2">
                            <button type="submit" class="btn btn-primary btn-block">
                                <i class="fas fa-search"></i>
                                Cari Data
                            </button>
                        </div>
                    </div>
                </div>
                <!-- TABLE VIEW -->
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
                        foreach ($vcari as $p) : ?>
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
                                            <?php elseif ($p->status == 'PROSES PEMBELIAN') : ?>
                                                <a class="btn btn-block btn-primary btn-sm">
                                                    <i class="fas fa-thumbs-up"></i>&nbsp;
                                                    <?= $p->status ?>
                                                </a>
                                            <?php elseif ($p->status == 'ON PROGRESS - KADEP' && $this->session->userdata('lv') == '5') : ?>
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
                                            <?php elseif ($p->status == 'ACC DIREKTUR' && $this->session->userdata('lv') == '2' || $p->status == 'ACC DIREKTUR' && $this->session->userdata('lv') == '4' || $p->status == 'ACC DIREKTUR' && $this->session->userdata('lv') == '5') : ?>
                                                <a class="btn btn-block btn-primary btn-sm">
                                                    <i class="fas fa-thumbs-up"></i>&nbsp;
                                                    ACC DIREKTUR
                                                </a>
                                            <?php elseif ($p->status == 'PROSES PEMBELIAN' && $this->session->userdata('lv') == '2' || $p->status == 'PROSES PEMBELIAN' && $this->session->userdata('lv') == '4' || $p->status == 'PROSES PEMBELIAN' && $this->session->userdata('lv') == '5') : ?>
                                                <a class="btn btn-block btn-primary btn-sm">
                                                    <i class="fas fa-thumbs-up"></i>&nbsp;
                                                    PROSES PEMBELIAN
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
            <?php else : ?>
            <?php endif; ?>

            <!-- KADEP VIEW -->
            <?php if ($this->session->userdata('lv') == '5' && $this->session->userdata('departemen') == 'KEUANGAN') : ?>
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
                        foreach ($ponk as $p) : ?>
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
                                            <?php elseif ($p->status == 'PROSES PEMBELIAN') : ?>
                                                <a class="btn btn-block btn-primary btn-sm">
                                                    <i class="fas fa-thumbs-up"></i>&nbsp;
                                                    <?= $p->status ?>
                                                </a>
                                            <?php elseif ($p->status == 'ON PROGRESS - KADEP' && $this->session->userdata('lv') == '5') : ?>
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
                                            <?php elseif ($p->status == 'ACC DIREKTUR' && $this->session->userdata('lv') == '2' || $p->status == 'ACC DIREKTUR' && $this->session->userdata('lv') == '4' || $p->status == 'ACC DIREKTUR' && $this->session->userdata('lv') == '5') : ?>
                                                <a class="btn btn-block btn-primary btn-sm">
                                                    <i class="fas fa-thumbs-up"></i>&nbsp;
                                                    ACC DIREKTUR
                                                </a>
                                            <?php elseif ($p->status == 'PROSES PEMBELIAN' && $this->session->userdata('lv') == '2' || $p->status == 'PROSES PEMBELIAN' && $this->session->userdata('lv') == '4' || $p->status == 'PROSES PEMBELIAN' && $this->session->userdata('lv') == '5') : ?>
                                                <a class="btn btn-block btn-primary btn-sm">
                                                    <i class="fas fa-thumbs-up"></i>&nbsp;
                                                    PROSES PEMBELIAN
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
            <?php elseif ($this->session->userdata('lv') == '5' || $this->session->userdata('lv') == '4' || $this->session->userdata('lv') == '3') : ?>
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
                        foreach ($po as $p1) : ?>
                            <tr>
                                <td><?= $no++; ?></td>
                                <td><?= $p1->nopo ?></td>
                                <td>
                                    <div class="row">
                                        <div class="col-md">
                                            <?php if ($p1->status == 'ON PROGRESS') : ?>
                                                <a class="btn btn-block btn-warning btn-sm">
                                                    <i class="fas fa-clock"></i>&nbsp;
                                                    <?= $p1->status ?>
                                                </a>
                                            <?php elseif ($p1->status == 'NOTE DIREKTUR' && $this->session->userdata('lv') == '2') : ?>
                                                <a class="btn btn-block btn-warning btn-sm">
                                                    <i class="fas fa-exclamation"></i>&nbsp;
                                                    Terdapat Update Dari Direktur
                                                </a>
                                            <?php elseif ($p1->status == 'NOTE DIREKTUR' && $this->session->userdata('lv') == '3') : ?>
                                                <a class="btn btn-block btn-warning btn-sm">
                                                    <i class="fas fa-clock"></i>&nbsp;
                                                    ON PROGRESS
                                                </a>
                                            <?php elseif ($p1->status == 'NOTE KEUANGAN' && $this->session->userdata('lv') == '3') : ?>
                                                <a class="btn btn-block btn-warning btn-sm">
                                                    <i class="fas fa-exclamation"></i>&nbsp;
                                                    Terdapat Update Dari Keuangan
                                                </a>
                                            <?php elseif ($p1->status == 'NOTE KEUANGAN' && $this->session->userdata('lv') == '2') : ?>
                                                <a class="btn btn-block btn-warning btn-sm">
                                                    <i class="fas fa-clock"></i>&nbsp;
                                                    ON PROGRESS
                                                </a>
                                            <?php elseif ($p1->status == 'ON PROGRESS - KADEP' && $this->session->userdata('lv') == '2') : ?>
                                                <a class="btn btn-block btn-warning btn-sm">
                                                    <i class="fas fa-clock"></i>&nbsp;
                                                    MENUNGGU ACC KADEP
                                                </a>
                                            <?php elseif ($p1->status == 'DONE') : ?>
                                                <a class="btn btn-block btn-success btn-sm">
                                                    <i class="fas fa-thumbs-up"></i>&nbsp;
                                                    <?= $p1->status ?>
                                                </a>
                                            <?php elseif ($p1->status == 'REJECT') : ?>
                                                <a class="btn btn-block btn-danger btn-sm">
                                                    <i class="fas fa-times"></i>&nbsp;
                                                    <?= $p1->status ?>
                                                </a>
                                            <?php elseif ($p1->status == 'PENGAJUAN DIBATALKAN') : ?>
                                                <a class="btn btn-block btn-danger btn-sm">
                                                    <i class="fas fa-times-circle"></i>&nbsp;
                                                    <?= $p1->status ?>
                                                </a>
                                            <?php elseif ($p1->status == 'PO REVISI') : ?>
                                                <a class="btn btn-block btn-warning btn-sm">
                                                    <i class="fas fa-undo"></i>&nbsp;
                                                    <?= $p1->status ?>
                                                </a>
                                            <?php elseif ($p1->status == 'PENDING') : ?>
                                                <a class="btn btn-block btn-warning btn-sm">
                                                    <i class="fas fa-pause"></i>&nbsp;
                                                    <?= $p1->status ?>
                                                </a>
                                            <?php elseif ($p1->status == 'ACC-KADEP') : ?>
                                                <a class="btn btn-block btn-primary btn-sm">
                                                    <i class="fas fa-thumbs-up"></i>&nbsp;
                                                    <?= $p1->status ?>
                                                </a>
                                            <?php elseif ($p1->status == 'ON PROGRESS - KADEP' && $this->session->userdata('lv') == '5') : ?>
                                                <a class="btn btn-block btn-warning btn-sm">
                                                    <i class="fas fa-clock"></i>&nbsp;
                                                    MENUNGGU ACC KADEP
                                                </a>
                                            <?php elseif ($p1->status == 'ON PROGRESS - KADEP' && $this->session->userdata('lv') == '4') : ?>
                                                <a class="btn btn-block btn-warning btn-sm">
                                                    <i class="fas fa-clock"></i>&nbsp;
                                                    MENUNGGU ACC KADEP
                                                </a>
                                            <?php elseif ($p1->status == 'SEDANG DIAJUKAN' && $this->session->userdata('lv') != '3') : ?>
                                                <a class="btn btn-block btn-warning btn-sm">
                                                    <i class="fas fa-clock"></i>&nbsp;
                                                    <?= $p1->status ?>
                                                </a>
                                            <?php elseif ($p1->status == 'SEDANG DIAJUKAN' && $this->session->userdata('lv') == '3') : ?>
                                                <a class="btn btn-block btn-warning btn-sm">
                                                    <i class="fas fa-clock"></i>&nbsp;
                                                    PENGAJUAN PEMBELIAN BARU
                                                </a>
                                            <?php elseif ($p1->status == 'ACC DIREKTUR' && $this->session->userdata('lv') == '2' || $p1->status == 'ACC DIREKTUR' && $this->session->userdata('lv') == '4' || $p1->status == 'ACC DIREKTUR' && $this->session->userdata('lv') == '5') : ?>
                                                <a class="btn btn-block btn-primary btn-sm">
                                                    <i class="fas fa-thumbs-up"></i>&nbsp;
                                                    ACC DIREKTUR
                                                </a>
                                            <?php elseif ($p1->status == 'PROSES PEMBELIAN' && $this->session->userdata('lv') == '2' || $p1->status == 'PROSES PEMBELIAN' && $this->session->userdata('lv') == '4' || $p1->status == 'PROSES PEMBELIAN' && $this->session->userdata('lv') == '5') : ?>
                                                <a class="btn btn-block btn-primary btn-sm">
                                                    <i class="fas fa-truck-moving"></i>&nbsp;
                                                    PROSES PEMBELIAN
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </td>
                                <td><?= format_indo($p1->tgl_transaksi) ?></td>
                                <td><?= $p1->nama_user ?></td>
                                <td><?= $p1->departement ?></td>
                                <td><?= $p1->tj_pembelian ?></td>
                                <td>
                                    <div class="row">
                                        <div class="col-md">
                                            <a class="btn btn-block btn-primary btn-sm" href="<?= base_url('detailponk/') . $p1->kd_po_nk ?>">
                                                <i class="fas fa-eye"></i>
                                                Detail
                                            </a>
                                        </div>
                                        <?php if ($this->session->userdata('lv') == '1') : ?>
                                            <div class="col-md">
                                                <a class="btn btn-block btn-success btn-sm" href="<?= base_url('konfirmasiOrderNK/') . $p1->kd_po_nk ?>">
                                                    <i class="fas fa-clipboard-check"></i>
                                                    Accept
                                                </a>
                                            </div>
                                            <div class="col-md">
                                                <a class="btn btn-block btn-warning btn-sm" href="<?= base_url('tolakOrderNK/') . $p1->kd_po_nk ?>">
                                                    <i class="fas fa-times"></i>
                                                    Reject
                                                </a>
                                            </div>
                                        <?php elseif ($this->session->userdata('lv') == '2' && $p1->status == 'REJECT') : ?>
                                            <div class="col">
                                                <a class="btn btn-block btn-info btn-sm" href="<?= base_url('unpostponk/') . $p1->kd_po_nk ?>">
                                                    <i class="fas fa-sync"></i> &nbsp;
                                                    UNPOST
                                                </a>
                                            </div>
                                            <div class="col">
                                                <a class="btn btn-block btn-warning btn-sm" href="<?= base_url('hapusponk/') . $p1->kd_po_nk ?>">
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
    <?php endif; ?>
    </div>
    <!-- /.content-header -->
</div>
