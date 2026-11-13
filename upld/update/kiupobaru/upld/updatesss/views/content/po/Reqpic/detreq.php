<?php if (($this->session->userdata('lv') == '4' || $this->session->userdata('lv') == '5') && !is_super_admin()) : ?>
    <?php foreach ($status as $s) : ?>
        <div class="content-wrapper">
            <div class="content-header">
                <div class="container-fluid">
                    <?php foreach (array('success' => 'success', 'error' => 'danger') as $flashKey => $alertClass) : ?>
                        <?php if ($this->session->flashdata($flashKey)) : ?>
                            <div class="alert alert-<?= $alertClass ?> alert-dismissible fade show" role="alert">
                                <?= htmlspecialchars($this->session->flashdata($flashKey), ENT_QUOTES, 'UTF-8') ?>
                                <button type="button" class="close" data-dismiss="alert" aria-label="Tutup"><span aria-hidden="true">&times;</span></button>
                            </div>
                        <?php endif; ?>
                    <?php endforeach; ?>
                    <div class="row mb-2">
                        <div class="col-sm-6">
                        </div><!-- /.col -->
                    </div><!-- /.row -->
                    <div class="card">
                        <?php if ($s->status == 'ON PROGRESS - BELUM ACC') : ?>
                            <a class="btn btn-block btn-warning btn-sm" href="javascript:void(0)"><i class="fas fa-user-clock"></i>&nbsp;ON PROGRESS - BELUM ACC KADEP</a>
                        <?php elseif ($s->status == 'ON PROGRESS' || $s->status == 'MENUNGGU ACC KADEP') : ?>
                            <a class="btn btn-block btn-warning btn-sm" href=""><i class="fas fa-exclamation-triangle"></i>&nbsp;<?= $s->status ?>&nbsp;<i class="fas fa-exclamation-triangle"></i></a>
                        <?php elseif ($s->status == 'REQUEST ACC') : ?>
                            <a class="btn btn-block btn-info btn-sm" href=""><i class="fas fa-check-circle"></i>&nbsp;<?= $s->status ?>&nbsp;<i class="fas fa-check-circle"></i></a>
                        <?php elseif (strpos(trim((string) $s->status), 'MENUNGGU PENYERAHAN') === 0) : ?>
                            <a class="btn btn-block btn-secondary btn-sm" href=""><i class="fas fa-truck-loading"></i>&nbsp;<?= $s->status ?>&nbsp;<i class="fas fa-truck-loading"></i></a>
                        <?php elseif (trim((string) $s->status) == 'MENUNGGU ACC PENGAMBILAN') : ?>
                            <a class="btn btn-block btn-warning btn-sm" href=""><i class="fas fa-user-clock"></i>&nbsp;<?= $s->status ?>&nbsp;<i class="fas fa-user-clock"></i></a>
                        <?php elseif ($s->status == 'DONE') : ?>
                            <a class="btn btn-block btn-success btn-sm" href=""><i class="fas fa-check-circle"></i>&nbsp;<?= $s->status ?>&nbsp;<i class="fas fa-check-circle"></i></a>
                        <?php endif; ?>
                        <div class="card-body">
                            <div class="row">
                                <div class="col">
                                    <h1 class="m-0">Detail Request Barang - PIC</h1>
                                </div>
                                <div class="col">
                                    <a class="btn btn-primary btn-sm btn-block" href="<?= base_url($this->session->userdata('lv') == '5' ? 'reqpicacckadep' : 'reqpic') ?>"><i class="fas fa-home"></i> <b>HOMEPAGE</b></a>
                                </div>
                                <?php if ($this->session->userdata('lv') == '5' && in_array($s->status, array('ON PROGRESS - BELUM ACC', 'MENUNGGU ACC KADEP'), true)) : ?>
                                    <div class="col">
                                        <button type="button" class="btn btn-success btn-sm btn-block" data-toggle="modal" data-target="#modalApprovalKadep">
                                            <i class="fas fa-clipboard-check"></i> <b>APPROVAL KADEP</b>
                                        </button>
                                    </div>
                                <?php endif; ?>
                                <?php if ($s->status == 'PENDING') : ?>
                                    <div class="col">
                                        <a href="<?= base_url('updated_po_nk/' . $s->kd_po_nk) ?>" class="btn btn-info btn-sm btn-block"><i class="fas fa-clipboard-check"></i> <b>UPDATED PO</b></a>
                                    </div>
                                <?php elseif ($s->status == 'ON PROGRESS') : ?>
                                    <div class="col">
                                        <a href="<?= base_url('reqpic/requestpending/' . $s->kd_po_nk) ?>" class="btn btn-danger btn-sm btn-block"><i class="fas fa-clipboard-check"></i> <b>PO PENDING</b></a>

                                    </div>
                                <?php endif; ?>

                            </div>

                            <!-- FIELD DATA PENGAJUAN  -->
                            <h3><?= $s->status ?></h3>
                            <div class="row">
                                <div class="col">
                                    <label for="naSupp" class="">Tanggal Transaksi : </label>
                                    <input type="text" id="naCus" name="naSupp" style="max-width: 550px;" value="<?= $s->tgl_transaksi ?>" class="form-control" readonly>
                                </div>
                                <div class="col">
                                    <label for="naSupp" class="">PIC : </label>
                                    <input type="text" id="naCus" name="naSupp" style="max-width: 550px;" value="<?= $s->nm_user ?>" class="form-control" readonly>
                                </div>
                                <div class="col">
                                    <label for="naSupp" class="">Departement : </label>
                                    <input type="text" id="naCus" name="naSupp" style="max-width: 550px;" value="<?= $s->departemen ?>" class="form-control" readonly>
                                </div>
                            </div>
                            <?php $this->load->view('content/po/Reqpic/_supporting_documents', array('supportingDocuments' => $supportingDocuments)); ?>
                            <?php if (!empty($stspo)) : ?>
                                <a href="#" class="btn btn-success btn-block mt-4"><b style="text-transform: uppercase;">Work in Process</b></a>
                                <table class="table table-bordered table-striped mb-2">
                                    <thead style="background-color: #212529; color:white;">
                                        <tr>
                                            <td>Nomor PO</td>
                                            <td>Status Order</td>
                                            <td>Tanggal PO</td>
                                            <td>Nama Pengaju</td>
                                            <td>Departement</td>
                                            <td>Tujuan Pembelian</td>
                                            <td>#</td>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($stspo as $st) : ?>
                                            <?php
                                            $statusClass = 'btn-warning';
                                            $statusIcon = 'fa-clock';
                                            $statusText = $st->status;

                                            if ($st->status === 'DONE') {
                                                $statusClass = 'btn-success';
                                                $statusIcon = 'fa-thumbs-up';
                                            } elseif ($st->status === 'REJECT') {
                                                $statusClass = 'btn-danger';
                                                $statusIcon = 'fa-times';
                                            } elseif (in_array($st->status, array('ACC-KADEP', 'ACC DIREKTUR', 'PROSES PEMBELIAN'), true)) {
                                                $statusClass = 'btn-primary';
                                                $statusIcon = 'fa-thumbs-up';
                                            } elseif ($st->status === 'PO REVISI') {
                                                $statusIcon = 'fa-undo';
                                            } elseif ($st->status === 'PENDING') {
                                                $statusIcon = 'fa-pause';
                                            } elseif ($st->status === 'ON PROGRESS - KADEP') {
                                                $statusText = 'MENUNGGU ACC KADEP';
                                            }
                                            ?>
                                            <tr>
                                                <td><?= htmlspecialchars($st->nopo, ENT_QUOTES, 'UTF-8') ?></td>
                                                <td>
                                                    <a class="btn btn-block <?= $statusClass ?> btn-sm">
                                                        <i class="fas <?= $statusIcon ?>"></i>&nbsp;
                                                        <?= htmlspecialchars($statusText, ENT_QUOTES, 'UTF-8') ?>
                                                    </a>
                                                </td>
                                                <td><?= htmlspecialchars($st->tgltr, ENT_QUOTES, 'UTF-8') ?></td>
                                                <td><?= htmlspecialchars($st->nmuser, ENT_QUOTES, 'UTF-8') ?></td>
                                                <td><?= htmlspecialchars($st->dep, ENT_QUOTES, 'UTF-8') ?></td>
                                                <td><?= htmlspecialchars($st->tjbeli, ENT_QUOTES, 'UTF-8') ?></td>
                                                <td>
                                                    <a href="<?= base_url('detailponk/' . rawurlencode($st->kdpo)) ?>" class="btn btn-block btn-primary btn-sm" target="_blank" rel="noopener" title="Lihat detail PO"><i class="fas fa-eye"></i></a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                                <?php if (!empty($lifoPickup)) : ?>
                                    <div class="alert alert-info small"><i class="fas fa-info-circle"></i> Harga berikut adalah snapshot alokasi batch LIFO pada tanggal transaksi pengambilan, bukan harga master yang berubah setelahnya.</div>
                                    <table class="table table-bordered table-striped mb-2">
                                        <thead style="background-color: #212529; color:white;"><tr><td>Barang</td><td>Tanggal Pengambilan</td><td>Qty</td><td>Harga Batch LIFO</td><td>Nilai Pengambilan</td><td>Keterangan Batch</td></tr></thead>
                                        <tbody><?php foreach ($lifoPickup as $lifo) : ?><tr>
                                            <td><?= htmlspecialchars($lifo->nama_barang, ENT_QUOTES, 'UTF-8') ?></td>
                                            <td><?= htmlspecialchars($lifo->tgl_transaksi, ENT_QUOTES, 'UTF-8') ?></td>
                                            <td class="text-center"><?= number_format($lifo->qty_alokasi ?: $lifo->tr_qty, 2, ',', '.') ?></td>
                                            <td><?= $lifo->harga_satuan !== null ? 'Rp ' . number_format($lifo->harga_satuan, 0, ',', '.') : '-' ?></td>
                                            <td><?= $lifo->nilai_total !== null ? 'Rp ' . number_format($lifo->nilai_total, 0, ',', '.') : '-' ?></td>
                                            <td class="small"><?= htmlspecialchars($lifo->keterangan_batch ?: 'Batch LIFO belum tersedia / harga belum tervalidasi.', ENT_QUOTES, 'UTF-8') ?></td>
                                        </tr><?php endforeach; ?></tbody>
                                    </table>
                                <?php endif; ?>
                            <?php endif; ?>
                            <?php if (in_array($s->status, array('ON PROGRESS', 'ON PROGRESS - BELUM ACC', 'MENUNGGU ACC KADEP'), true)) : ?>
                                <table class="table table-bordered table-striped mt-4 mb-2 ">
                                    <thead style="background-color: #212529; color:white;">
                                        <tr>
                                            <td>Nama Barang</td>
                                            <td>Deskripsi</td>
                                            <td>Keterangan</td>
                                            <td>QTY</td>
                                            <td>Satuan</td>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($datereqpic as $d) : ?>
                                            <tr>
                                                <td><?= $d->nama_barang ?></td>
                                                <td><?= $d->deskripsi ?></td>
                                                <td><?= $d->keterangan ?></td>
                                                <td><?= $d->qtykebutuhan ?></td>
                                                <td><?= $d->nm_satuan ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            <?php elseif ($s->status == 'BARANG TERSEDIA' || trim((string) $s->status) == 'MENUNGGU ACC PENGAMBILAN') : ?>
                                <a href="#" class="btn btn-success btn-block mt-4"><b style="text-transform: uppercase;">item list barang request</b></a>
                                <table class="table table-bordered table-striped mb-2">
                                    <thead style="background-color: #212529; color:white;">
                                        <tr>
                                            <td>Nama Barang</td>
                                            <td>Deskripsi</td>
                                            <td style="width:20%;">Keterangan</td>
                                            <td style="width:10%;text-align: center;">QTY</td>
                                            <td style="width:10%;text-align: center;">Satuan</td>
                                            <td style="text-align: center;">Harga Batch LIFO</td>
                                            <td style="text-align: center;">Keterangan Batch</td>
                                            <td style="width:10%;text-align: center;">#</td>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php $lifoByBarang = array(); foreach ((isset($lifoPickup) ? $lifoPickup : array()) as $lifoRow) { $lifoByBarang[$lifoRow->kd_barangsys] = $lifoRow; $lifoByBarang[$lifoRow->kd_barang] = $lifoRow; } ?>
                                        <?php foreach ($getitmlistpicreq as $d) : ?>
                                            <?php $lifo = isset($lifoByBarang[$d->kd_bsys]) ? $lifoByBarang[$d->kd_bsys] : (isset($lifoByBarang[$d->kd_barang]) ? $lifoByBarang[$d->kd_barang] : null); ?>
                                            <tr>
                                                <td><?= $d->nmbarang ?></td>
                                                <td><?= $d->deskripsi ?></td>
                                                <td><?= $d->ket ?></td>
                                                <td style="text-align: center;"><?= $d->qty ?></td>
                                                <td style="text-align: center;"><?= $d->nmsatuan ?></td>
                                                <td style="text-align: center;"><?= $lifo && $lifo->harga_satuan !== null ? 'Rp ' . number_format($lifo->harga_satuan, 0, ',', '.') : '-' ?></td>
                                                <td class="small"><?= $lifo ? htmlspecialchars($lifo->keterangan_batch ?: 'Batch belum tervalidasi.', ENT_QUOTES, 'UTF-8') : 'Belum ada alokasi batch LIFO.' ?></td>
                                                <td><a href="#" class="btn btn-block btn-success btn-md"><i class="fas fa-check-circle"></i></a></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                                <?php if ($this->session->userdata('lv') == '4' && $s->status == 'BARANG TERSEDIA') : ?>
                                    <form action="<?= base_url('reqpic/pickup-approval/request') ?>" method="post">
                                        <input type="hidden" name="kdponk" value="<?= htmlspecialchars($s->kd_po_nk, ENT_QUOTES, 'UTF-8') ?>">
                                        <button type="submit" class="btn btn-primary btn-block"><i class="fas fa-paper-plane"></i> AJUKAN PERSETUJUAN PENGAMBILAN KE KADEP</button>
                                    </form>
                                <?php endif; ?>
                                <?php if (trim((string) $s->status) == 'MENUNGGU ACC PENGAMBILAN') : ?>
                                    <div class="alert alert-warning mt-4 mb-0"><i class="fas fa-user-clock"></i> Permohonan pengambilan telah dikirim dan sedang menunggu persetujuan KADEP.</div>
                                <?php endif; ?>
                            <?php elseif (strpos(trim((string) $s->status), 'MENUNGGU PENYERAHAN') === 0) : ?>
                                <div class="alert alert-info mt-4 mb-0"><i class="fas fa-clock"></i> Pengajuan pengambilan telah dikirim. Menunggu konfirmasi penyerahan dari Purchasing.</div>
                            <?php elseif ($s->status == 'PENDING') : ?>
                                <a href="#" class="btn btn-warning btn-block mt-4"><b style="text-transform: uppercase;">item list barang request</b></a>
                                <table class="table table-bordered table-striped mb-2">
                                    <thead style="background-color: #212529; color:white;">
                                        <tr>
                                            <td>Nama Barang</td>
                                            <td>Deskripsi</td>
                                            <td style="width:20%;">Keterangan</td>
                                            <td style="width:10%;text-align: center;">QTY</td>
                                            <td style="width:10%;text-align: center;">Satuan</td>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($getitmlistpicreq as $d) : ?>
                                            <tr>
                                                <td><?= $d->nmbarang ?></td>
                                                <td><?= $d->deskripsi ?></td>
                                                <td><?= $d->ket ?></td>
                                                <td style="text-align: center;"><?= $d->qty ?></td>
                                                <td style="text-align: center;"><?= $d->nmsatuan ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            <?php elseif ($s->status == 'REVISI PO') : ?>
                                <!-- MODAL START REVISI -->
                                <?php foreach ($getitmlistpicreq as $d) : ?>
                                    <div class="modal fade" id="modaleditbarang<?= $d->idbarang ?>">
                                        <div class=" modal-dialog modal-lg">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h4 class="modal-title">Edit Data Barang Request</h4>
                                                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                                        <span aria-hidden="true">&times;</span>
                                                    </button>
                                                </div>
                                                <div class="modal-body">
                                                    <?php echo form_open_multipart('updated_det_req_po_nk'); ?>
                                                    <div class="form-group">
                                                        <div class="row">
                                                            <label class="col-sm-2 control-label text-right" for="kd_user">Nama Barang<span class="required">*</span></label>
                                                            <div class="col-sm-10">
                                                                <input class="form-control" type="text" id="nmbarang" name="nmbarang" value="<?= $d->nmbarang ?>" readonly />
                                                                <input class="form-control" type="text" id="idbarang" name="idbarang" value="<?= $d->idbarang ?>" readonly hidden />
                                                                <input class="form-control" type="text" id="kodebarang" name="kodebarang" value="<?= $d->kodefaktur ?>" readonly hidden />
                                                                <input class="form-control" type="text" id="action" name="action" value="edited" readonly hidden />
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="form-group">
                                                        <div class="row">
                                                            <label class="col-sm-2 control-label text-right" for="kd_user">Keterangan<span class="required">*</span></label>
                                                            <div class="col-sm-10">
                                                                <input type="text" class="form-control" id="keterangan" name="keterangan" value="<?= $d->ket ?>">
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="form-group">
                                                        <div class="row">
                                                            <label class="col-sm-2 control-label text-right" for="kd_user">Qty<span class="required">*</span></label>
                                                            <div class="col-sm-10">
                                                                <input type="number" class="form-control" id="qtyisi" name="qtyisi" value="<?= $d->qty ?>">
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

                                <?php foreach ($getitmlistpicreq as $d) : ?>
                                    <div class="modal fade" id="modalhapus<?= $d->idbarang ?>">
                                        <div class=" modal-dialog modal-lg">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h4 class="modal-title">Hapus Data Barang Request</h4>
                                                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                                        <span aria-hidden="true">&times;</span>
                                                    </button>
                                                </div>
                                                <div class="modal-body">
                                                    <?php echo form_open_multipart('updated_det_req_po_nk'); ?>
                                                    <div class="form-group">
                                                        <div class="row">
                                                            <div class="col-sm-10">
                                                                <input class="form-control" type="text" id="idbarang" name="idbarang" value="<?= $d->idbarang ?>" readonly hidden />
                                                                <input class="form-control" type="text" id="kodebarang" name="kodebarang" value="<?= $d->kodefaktur ?>" readonly hidden />
                                                                <input class="form-control" type="text" id="action" name="action" value="delete" readonly hidden />
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="form-group">
                                                        <div class="row">
                                                            <div class="col-sm-10">
                                                                <h3>Data <?= $d->nmbarang ?> akan terhapus permanent</h3>
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
                                    </div>
                                <?php endforeach; ?>
                                <!-- MODAL END REVISI -->

                                <a href="#" class="btn btn-warning btn-block mt-4"><b style="text-transform: uppercase;">item list barang request</b></a>
                                <table class="table table-bordered table-striped mb-2">
                                    <thead style="background-color: #212529; color:white;">
                                        <tr>
                                            <td>Nama Barang</td>
                                            <td>Deskripsi</td>
                                            <td style="width:20%;">Keterangan</td>
                                            <td style="width:10%;text-align: center;">QTY</td>
                                            <td style="width:10%;text-align: center;">Satuan</td>
                                            <td style="text-align: center;">#</td>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($getitmlistpicreq as $d) : ?>
                                            <tr>
                                                <td><?= $d->nmbarang ?></td>
                                                <td><?= $d->deskripsi ?></td>
                                                <td><?= $d->ket ?></td>
                                                <td style="text-align: center;"><?= $d->qty ?></td>
                                                <td style="text-align: center;"><?= $d->nmsatuan ?></td>
                                                <td>
                                                    <div class="row">
                                                        <div class="col">
                                                            <a href="#" class="btn btn-block btn-warning btn-md" data-toggle="modal" data-target="#modaleditbarang<?= $d->idbarang ?>"><i class="fas fa-user-edit"></i></a>
                                                        </div>
                                                        <div class="col">
                                                            <a href="#" class="btn btn-block btn-danger btn-md" data-toggle="modal" data-target="#modalhapus<?= $d->idbarang ?>"><i class="fas fa-trash-alt"></i> </a>
                                                        </div>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            <?php elseif ($s->status == 'REQUEST ACC') : ?>
                                <a href="#" class="btn btn-success btn-block mt-4"><b style="text-transform: uppercase;">item list</b></a>
                                <table class="table table-bordered table-striped mb-2 ">
                                    <thead style="background-color: #212529; color:white;">
                                        <tr>
                                            <td>Nama Barang</td>
                                            <td>Deskripsi</td>
                                            <td style="width:30%">Keterangan</td>
                                            <td style="width:10%">Satuan</td>
                                            <td style="width:10%">QTY</td>
                                            <td style="width:10%">#</td>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($detreq as $d) :
                                            $stkready = $d->qty_ready - $d->qty_req;
                                        ?>
                                            <tr>
                                                <td><?= $d->nama_barang ?></td>
                                                <td><?= $d->deskripsi ?></td>
                                                <td><?= $d->keterangan ?></td>
                                                <td><?= $d->nm_satuan ?></td>
                                                <td><?= $d->qty_req ?></td>
                                                <?php if ($d->sts == '1') : ?>
                                                    <td><a href="#" class="btn btn-block btn-success btn-md"><i class="fas fa-check-circle"></i></a></td>
                                                <?php elseif ($d->sts == '4') : ?>
                                                    <td><a href="#" class="btn btn-block btn-warning btn-md"><i class="fas fa-pause-circle "></i></a></td>
                                                <?php endif; ?>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            <?php elseif ($s->status == 'DONE') : ?>
                                <a href="#" class="btn btn-success btn-block mt-4"><b style="text-transform: uppercase;">REQUEST DONE</b></a>
                                <table class="table table-bordered table-striped mb-2">
                                    <thead style="background-color: #212529; color:white;">
                                        <tr>
                                            <td>Nama Barang</td>
                                            <td>Deskripsi</td>
                                            <td style="width:20%;">Keterangan</td>
                                            <td style="width:10%;text-align: center;">QTY</td>
                                            <td style="width:10%;text-align: center;">Satuan</td>
                                            <td style="text-align: center;">Harga Batch LIFO</td>
                                            <td style="text-align: center;">Keterangan Batch</td>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php $lifoByBarangDone = array(); foreach ((isset($lifoPickup) ? $lifoPickup : array()) as $lifoRow) { $lifoByBarangDone[$lifoRow->kd_barangsys] = $lifoRow; $lifoByBarangDone[$lifoRow->kd_barang] = $lifoRow; } ?>
                                        <?php foreach ($getitmlistpicreq as $d) : ?>
                                            <?php $lifo = isset($lifoByBarangDone[$d->kd_bsys]) ? $lifoByBarangDone[$d->kd_bsys] : (isset($lifoByBarangDone[$d->kd_barang]) ? $lifoByBarangDone[$d->kd_barang] : null); ?>
                                            <tr>
                                                <td><?= $d->nmbarang ?></td>
                                                <td><?= $d->deskripsi ?></td>
                                                <td><?= $d->ket ?></td>
                                                <td style="text-align: center;"><?= $d->qty ?></td>
                                                <td style="text-align: center;"><?= $d->nmsatuan ?></td>
                                                <td style="text-align: center;"><?= $lifo && $lifo->harga_satuan !== null ? 'Rp ' . number_format($lifo->harga_satuan, 0, ',', '.') : '-' ?></td>
                                                <td class="small"><?= $lifo ? htmlspecialchars($lifo->keterangan_batch ?: 'Batch belum tervalidasi.', ENT_QUOTES, 'UTF-8') : 'Belum ada alokasi batch LIFO.' ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php if ($this->session->userdata('lv') == '5' && in_array($s->status, array('ON PROGRESS - BELUM ACC', 'MENUNGGU ACC KADEP'), true)) : ?>
                        <div class="modal fade" id="modalApprovalKadep" tabindex="-1" role="dialog" aria-labelledby="modalApprovalKadepLabel" aria-hidden="true">
                            <div class="modal-dialog" role="document">
                                <form action="<?= base_url('process_req_kadep') ?>" method="post" class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title" id="modalApprovalKadepLabel">Approval Request Barang</h5>
                                        <button type="button" class="close" data-dismiss="modal" aria-label="Tutup"><span aria-hidden="true">&times;</span></button>
                                    </div>
                                    <div class="modal-body">
                                        <input type="hidden" name="kdreqpo" value="<?= htmlspecialchars($s->kd_po_nk, ENT_QUOTES, 'UTF-8') ?>">
                                        <div class="form-group">
                                            <label for="approval_action">Aksi Approval</label>
                                            <select class="form-control" id="approval_action" name="approval_action" required>
                                                <option value="ACC">ACC</option>
                                                <option value="REVISI">REVISI</option>
                                                <option value="REJECT">REJECT</option>
                                            </select>
                                        </div>
                                        <div class="form-group mb-0">
                                            <label for="approval_note">Catatan</label>
                                            <textarea class="form-control" id="approval_note" name="approval_note" rows="4" required></textarea>
                                            <small class="form-text text-muted">Catatan akan ditampilkan pada tabel Note request.</small>
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                                        <button type="submit" class="btn btn-primary">Simpan Approval</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    <?php endif; ?>
                    <div class="row mr-2">
                        <div class="col-md-8">
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
                                        <?php foreach ($log as $l) : ?>
                                            <tr>
                                                <td><?= $l->isi_note ?></td>
                                                <td><?= $l->nama_user ?></td>
                                                <td><?= $l->log_create ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div><!-- /.container-fluid -->
            </div><!-- /.content-header -->
        </div>
    <?php endforeach; ?>
    <!-- ============================================================================================================================================================================== -->
    <!-- ============================================================================================================================================================================== -->
    <!-- ============================================================================================================================================================================== -->
    <!-- ============================================================================================================================================================================== -->
    <!-- ============================================================================================================================================================================== -->
    <!-- ============================================================================================================================================================================== -->
<?php elseif ($this->session->userdata('lv') == '2' || is_super_admin()) : ?>
    <?php $purchasingLocked = isset($purchasingCanProcess) && !$purchasingCanProcess; ?>
    <?php foreach ($status as $s) : ?>
        <div class="content-wrapper">
            <div class="content-header">
                <div class="container-fluid">
                    <?php if ($s->status == 'ON PROGRESS - BELUM ACC') : ?>
                        <a class="btn btn-block btn-warning btn-sm" href="javascript:void(0)"><i class="fas fa-user-clock"></i>&nbsp;ON PROGRESS - BELUM ACC KADEP</a>
                    <?php elseif (in_array($s->status, array('ON PROGRESS', 'ON PROGRESS - ACC KADEP'), true)) : ?>
                        <a class="btn btn-block btn-warning btn-sm" href=""><i class="fas fa-exclamation-triangle"></i>&nbsp;<?= $s->status ?>&nbsp;<i class="fas fa-exclamation-triangle"></i></a>
                    <?php elseif ($s->status == 'REQUEST ACC') : ?>
                        <a class="btn btn-block btn-info btn-sm" href=""><i class="fas fa-check-circle"></i>&nbsp;<?= $s->status ?>&nbsp;<i class="fas fa-check-circle"></i></a>
                    <?php else : ?>
                    <?php endif; ?>

                    <div class="modal fade" id="modalrevisi">
                        <div class="modal-dialog modal-lg">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h4 class="modal-title">Note Revisi</h4>
                                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                        <span aria-hidden="true">&times;</span>
                                    </button>
                                </div>
                                <div class="modal-body">
                                    <?php echo form_open_multipart('po_nk_req_revisi_note'); ?>
                                    <div class="form-group">
                                        <div class="row">
                                            <label class="col-sm-2 control-label text-right" for="kd_user">Note Revisi<span class="required">*</span></label>
                                            <div class="col-sm-10">
                                                <textarea name="porevisi" id="porevisi" class="form-control"></textarea>
                                            </div>
                                            <div hidden>
                                                <input type="text" class="form-control" id="kodeponk" name="kodeponk" value="<?= $s->kd_po_nk ?>">
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
                    <div class="card">
                        <div class="card-body">
                            <?php if ($purchasingLocked) : ?>
                                <div class="alert alert-warning"><i class="fas fa-user-clock"></i> Menunggu ACC KADEP. Aksi Purchasing dibuka setelah 5 menit sejak request diajukan atau setelah KADEP menyetujuinya.</div>
                            <?php endif; ?>
                            <div class="row mb-2">
                                <div class="col">
                                    <h1 class="m-0">Detail Request Barang - PIC</h1>
                                </div>
                                <div class="col">
                                    <a class="btn btn-primary btn-sm btn-block" href="<?= base_url('reqpic') ?>"><i class="fas fa-home"></i> HOME </a>
                                </div>
                                <div class="col">
                                    <?php if (in_array($s->status, array('ON PROGRESS', 'ON PROGRESS - ACC KADEP'), true) || ($s->status == 'ON PROGRESS - BELUM ACC' && !$purchasingLocked)) : ?>
                                        <a class="btn btn-info btn-sm btn-block" href="#" data-toggle="modal" data-target="#modalrevisi"><i class="fas fa-recycle"></i> REVISI </a>
                                    <?php elseif ($s->status == 'PO REVISI') : ?>
                                        <a class="btn btn-warning btn-sm btn-block"><i class="fas fa-user-clock"></i> REVISI </a>
                                    <?php endif; ?>
                                </div>
                                <!-- /.col -->
                            </div><!-- /.row -->
                            <!-- FIELD DATA PENGAJUAN  -->
                            <div class="row mt-3">
                                <div class="col">
                                    <label for="naSupp" class="">Tanggal Transaksi : </label>
                                    <input type="text" id="naCus" name="naSupp" style="max-width: 550px;" value="<?= format_tgl_lahir($s->tgl_transaksi) ?>" class="form-control" readonly>
                                </div>
                                <div class="col">
                                    <label for="naSupp" class="">PIC : </label>
                                    <input type="text" id="naCus" name="naSupp" style="max-width: 550px;" value="<?= $s->nm_user ?>" class="form-control" readonly>
                                </div>
                                <div class="col">
                                    <label for="naSupp" class="">Departement : </label>
                                    <input type="text" id="naCus" name="naSupp" style="max-width: 550px;" value="<?= $s->departemen ?>" class="form-control" readonly>
                                </div>
                            </div>
                            <?php $this->load->view('content/po/Reqpic/_supporting_documents', array('supportingDocuments' => $supportingDocuments)); ?>


                            <!-- STATUS : REQUEST ACC  -->
                            <?php if ($s->status == 'REQUEST ACC') : ?>
                                <!-- =================================================================================================================================== -->
                                <!-- ==================================================V2====================================================================  -->
                                <!-- =================================================================================================================================== -->
                                <a href="#" class="btn btn-success btn-block mt-4"><b style="text-transform: uppercase;">Work in Process</b></a>
                                <table class="table table-bordered table-striped mb-2">
                                    <thead style="background-color: #212529; color:white;">
                                        <tr>
                                            <td>Nomor PO</td>
                                            <td>Status Order</td>
                                            <td>Tanggal PO</td>
                                            <td>Nama Pengaju</td>
                                            <td>Departement</td>
                                            <td>Tujuan Pembelian</td>
                                            <td>#</td>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($stspo as $st) : ?>
                                            <tr>
                                                <td><?= $st->nopo ?></td>
                                                <?php if ($st->status == 'ON PROGRESS') : ?>
                                                    <td>
                                                        <a class="btn btn-block btn-warning btn-sm">
                                                            <i class="fas fa-clock"></i>&nbsp;
                                                            <?= $st->status ?>
                                                        </a>
                                                    </td>
                                                <?php elseif ($st->status == 'NOTE DIREKTUR' && $this->session->userdata('lv') == '2') : ?>
                                                    <td>
                                                        <a class="btn btn-block btn-warning btn-sm">
                                                            <i class="fas fa-exclamation"></i>&nbsp;
                                                            Terdapat Update Dari Direktur
                                                        </a>
                                                    </td>
                                                <?php elseif ($st->status == 'NOTE DIREKTUR' && $this->session->userdata('lv') == '3') : ?>
                                                    <td>
                                                        <a class="btn btn-block btn-warning btn-sm">
                                                            <i class="fas fa-clock"></i>&nbsp;
                                                            ON PROGRESS
                                                        </a>
                                                    </td>
                                                <?php elseif ($st->status == 'NOTE KEUANGAN' && $this->session->userdata('lv') == '3') : ?>
                                                    <td>
                                                        <a class="btn btn-block btn-warning btn-sm">
                                                            <i class="fas fa-exclamation"></i>&nbsp;
                                                            Terdapat Update Dari Keuangan
                                                        </a>
                                                    </td>
                                                <?php elseif ($st->status == 'NOTE KEUANGAN' && $this->session->userdata('lv') == '2') : ?>
                                                    <td>
                                                        <a class="btn btn-block btn-warning btn-sm">
                                                            <i class="fas fa-clock"></i>&nbsp;
                                                            ON PROGRESS
                                                        </a>
                                                    </td>
                                                <?php elseif ($st->status == 'ON PROGRESS - KADEP' && $this->session->userdata('lv') == '2') : ?>
                                                    <td>
                                                        <a class="btn btn-block btn-warning btn-sm">
                                                            <i class="fas fa-clock"></i>&nbsp;
                                                            MENUNGGU ACC KADEP
                                                        </a>
                                                    </td>
                                                <?php elseif ($st->status == 'DONE') : ?>
                                                    <td>
                                                        <a class="btn btn-block btn-success btn-sm">
                                                            <i class="fas fa-thumbs-up"></i>&nbsp;
                                                            <?= $st->status ?>
                                                        </a>
                                                    </td>
                                                <?php elseif ($st->status == 'REJECT') : ?>
                                                    <td>
                                                        <a class="btn btn-block btn-danger btn-sm">
                                                            <i class="fas fa-times"></i>&nbsp;
                                                            <?= $st->status ?>
                                                        </a>
                                                    </td>
                                                <?php elseif ($st->status == 'PO REVISI') : ?>
                                                    <td>
                                                        <a class="btn btn-block btn-warning btn-sm">
                                                            <i class="fas fa-undo"></i>&nbsp;
                                                            <?= $st->status ?>
                                                        </a>
                                                    </td>
                                                <?php elseif ($st->status == 'PENDING') : ?>
                                                    <td>
                                                        <a class="btn btn-block btn-warning btn-sm">
                                                            <i class="fas fa-pause"></i>&nbsp;
                                                            <?= $st->status ?>
                                                        </a>
                                                    </td>
                                                <?php elseif ($st->status == 'ACC-KADEP') : ?>
                                                    <td>
                                                        <a class="btn btn-block btn-primary btn-sm">
                                                            <i class="fas fa-thumbs-up"></i>&nbsp;
                                                            <?= $st->status ?>
                                                        </a>
                                                    </td>
                                                <?php elseif ($st->status == 'PROSES PEMBELIAN') : ?>
                                                    <td>
                                                        <a class="btn btn-block btn-primary btn-sm">
                                                            <i class="fas fa-thumbs-up"></i>&nbsp;
                                                            <?= $st->status ?>
                                                        </a>
                                                    </td>
                                                <?php elseif ($st->status == 'ON PROGRESS - KADEP' && $this->session->userdata('lv') == '5') : ?>
                                                    <td>
                                                        <a class="btn btn-block btn-warning btn-sm">
                                                            <i class="fas fa-clock"></i>&nbsp;
                                                            MENUNGGU ACC KADEP
                                                        </a>
                                                    </td>
                                                <?php elseif ($st->status == 'SEDANG DIAJUKAN' && $this->session->userdata('lv') != '3') : ?>
                                                    <td>
                                                        <a class="btn btn-block btn-warning btn-sm">
                                                            <i class="fas fa-clock"></i>&nbsp;
                                                            <?= $st->status ?>
                                                        </a>
                                                    </td>
                                                <?php elseif ($st->status == 'SEDANG DIAJUKAN' && $this->session->userdata('lv') == '3') : ?>
                                                    <td>
                                                        <a class="btn btn-block btn-warning btn-sm">
                                                            <i class="fas fa-clock"></i>&nbsp;
                                                            PENGAJUAN PEMBELIAN BARU
                                                        </a>
                                                    </td>
                                                <?php elseif ($st->status == 'ACC DIREKTUR' && $this->session->userdata('lv') == '2' || $st->status == 'ACC DIREKTUR' && $this->session->userdata('lv') == '4' || $st->status == 'ACC DIREKTUR' && $this->session->userdata('lv') == '5') : ?>
                                                    <td>
                                                        <a class="btn btn-block btn-primary btn-sm">
                                                            <i class="fas fa-thumbs-up"></i>&nbsp;
                                                            ACC DIREKTUR
                                                        </a>
                                                    </td>
                                                <?php elseif ($st->status == 'PROSES PEMBELIAN' && $this->session->userdata('lv') == '2' || $st->status == 'PROSES PEMBELIAN' && $this->session->userdata('lv') == '4' || $st->status == 'PROSES PEMBELIAN' && $this->session->userdata('lv') == '5') : ?>
                                                    <td>
                                                        <a class="btn btn-block btn-primary btn-sm">
                                                            <i class="fas fa-thumbs-up"></i>&nbsp;
                                                            PROSES PEMBELIAN
                                                        </a>
                                                    </td>
                                                <?php endif; ?>
                                                <td><?= $st->tgltr ?></td>
                                                <td><?= $st->nmuser ?></td>
                                                <td><?= $st->dep ?></td>
                                                <td><?= $st->tjbeli ?></td>
                                                <td>
                                                    <a href="<?= base_url('detailponk/' . $st->kdpo) ?>" class="btn btn-block btn-primary btn-sm" target="_blank"><i class="fas fa-eye"></i></a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>

                                    </tbody>
                                </table>
                                <a href="#" class="btn btn-success btn-block mt-4"><b style="text-transform: uppercase;">item list</b></a>
                                <table class="table table-bordered table-striped mb-2 ">
                                    <thead style="background-color: #212529; color:white;">
                                        <tr>
                                            <td>Nama Barang</td>
                                            <td>Deskripsi</td>
                                            <td style="width:30%">Keterangan</td>
                                            <td style="width:10%">Satuan</td>
                                            <td style="width:10%">QTY</td>
                                            <td style="width:10%">#</td>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($detreq as $d) :
                                            $stkready = $d->qty_ready - $d->qty_req;
                                        ?>
                                            <tr>
                                                <td><?= $d->nama_barang ?></td>
                                                <td><?= $d->deskripsi ?></td>
                                                <td><?= $d->keterangan ?></td>
                                                <td><?= $d->nm_satuan ?></td>
                                                <td><?= $d->qty_req ?></td>
                                                <?php if ($d->sts == '1') : ?>
                                                    <td><a href="#" class="btn btn-block btn-success btn-md"><i class="fas fa-check-circle"></i></a></td>
                                                <?php elseif ($d->sts == '4') : ?>
                                                    <td><a href="#" class="btn btn-block btn-warning btn-md"><i class="fas fa-pause-circle "></i></a></td>
                                                <?php endif; ?>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                                <?php foreach ($countitm as $c) :
                                    $tot    = $c->total;
                                    $toty   = $c->tot_yes + $c->tot_no;
                                    $totacc = $c->tot_yes;
                                    $totpnd = $c->tot_no;
                                ?>
                                    <?php if ($totpnd > '0') : ?>
                                        <?php foreach ($stspo as $st) : ?>
                                            <?php if ($st->status == 'DONE') : ?>
                                                <?php echo form_open_multipart('reqpicconfirmed'); ?>
                                                <?php $now = date("Y-m-d"); ?>
                                                <input type="text" id="kdreqpo" name="kdreqpo" style="max-width: 550px;" value="<?= $s->kd_po_nk ?>" class="form-control" readonly hidden>
                                                <input type="text" id="kdponk" name="kdponk" style="max-width: 550px;" value="<?= $st->kdpo ?>" class="form-control" readonly hidden>
                                                <input type="text" id="tgl" name="tgl" style="max-width: 550px;" value="<?= $now ?>" class="form-control" readonly hidden>
                                                <input type="text" id="pic" name="pic" style="max-width: 550px;" value="<?= $s->nm_user ?>" class="form-control" readonly hidden>
                                                <input type="text" id="kdpic" name="kdpic" style="max-width: 550px;" value="<?= $s->kd_user ?>" class="form-control" readonly hidden>
                                                <input type="text" id="dep" name="dep" style="max-width: 550px;" value="<?= $s->departemen ?>" class="form-control" readonly hidden>
                                                <input type="text" id="tjbuy" name="tjbuy" style="max-width: 550px;" value="<?= $s->tj_pembelian ?>" class="form-control" readonly hidden>
                                                <button type="submit" class="btn btn-block btn-primary btn-md"><B>ORDER CONFIRMED</B></button>
                                            <?php endif; ?>
                                        <?php endforeach; ?>
                                    <?php else : ?>
                                        <?php echo form_open_multipart('reqpicconfirmed_plus'); ?>
                                        <?php $now = date("Y-m-d"); ?>
                                        <input type="text" id="kdreqpo" name="kdreqpo" style="max-width: 550px;" value="<?= $s->kd_po_nk ?>" class="form-control" readonly hidden>
                                        <button type="submit" class="btn btn-block btn-primary btn-md"><B>ORDER CONFIRMED</B></button>
                                    <?php endif; ?>
                                <?php endforeach; ?>

                                <!-- STATUS : ON PROGRESS -->

                            <?php elseif (in_array($s->status, array('ON PROGRESS', 'ON PROGRESS - ACC KADEP'), true) || ($s->status == 'ON PROGRESS - BELUM ACC' && !$purchasingLocked)) : ?>
                                <table class="table table-bordered table-striped mt-4 mb-2 ">

                                    <thead style="background-color: #212529; color:white;">
                                        <tr>
                                            <td>Nama Barang</td>
                                            <td>Deskripsi</td>
                                            <td>Keterangan</td>
                                            <td>QTY</td>
                                            <?php if ($s->tj_pembelian == "Restock By Admin PO") : ?>
                                                <td>Satuan</td>
                                                <td>#</td>
                                            <?php else : ?>
                                                <td style="width: 10%;">Qty Tersedia</td>
                                                <td>Satuan</td>
                                                <td>Status</td>
                                                <td>#</td>
                                            <?php endif; ?>

                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($detreq as $d) :
                                            $tot    = ($d->qty_ready) - ($d->qty_req);
                                        ?>
                                            <!-- MODAL START -->
                                            <div class="modal fade" id="modaledited<?= $d->id ?>">
                                                <div class="modal-dialog modal-lg">
                                                    <div class="modal-content">
                                                        <div class="modal-header">
                                                            <h4 class="modal-title">Pengajuan Tambahan Barang </h4>
                                                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                                                <span aria-hidden="true">&times;</span>
                                                            </button>
                                                        </div>
                                                        <div class="modal-body">
                                                            <?php echo form_open_multipart('actpending/' . $d->id . '/' . $d->kode_po); ?>
                                                            <div class="form-group" hidden>
                                                                <div class="row">
                                                                    <div class="col">
                                                                        <input class="form-control" type="text" id="kdponk" name="qty_isi" value="<?= $d->kode_po ?>" readonly />
                                                                        <input class="form-control" type="text" id="idponk" name="qty_isi" value="<?= $d->id ?>" readonly />
                                                                    </div>
                                                                </div>
                                                            </div>
                                                            <div class="form-group">
                                                                <div class="row">
                                                                    <label class="col-sm-3 control-label text-right" for="kd_user">Qty<span class="required">*</span></label>
                                                                    <div class="col-sm-8"><input class="form-control" type="number" id="qty_isi" name="qty_isi" value="" /></div>
                                                                </div>
                                                            </div>
                                                            <div class="form-group">
                                                                <div class="row">
                                                                    <label class="col-sm-3 control-label text-right" for="kd_user">Harga Satuan<span class="required">*</span></label>
                                                                    <div class="col-sm-8"><input class="form-control" type="number" id="hrg_isi" name="hrg_isi" value="" /></div>
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
                                            </div>

                                            <div class="modal fade" id="restockmodaledited<?= $d->id ?>">
                                                <div class="modal-dialog modal-lg">
                                                    <div class="modal-content">
                                                        <div class="modal-header">
                                                            <h4 class="modal-title">Restock Barang</h4>
                                                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                                                <span aria-hidden="true">&times;</span>
                                                            </button>
                                                        </div>
                                                        <div class="modal-body">
                                                            <?php echo form_open_multipart('actpending/' . $d->id . '/' . $d->kode_po); ?>
                                                            <div class="form-group" hidden>
                                                                <div class="row">
                                                                    <div class="col">
                                                                        <input class="form-control" type="text" id="kdponk" name="qty_isi" value="<?= $d->kode_po ?>" readonly />
                                                                        <input class="form-control" type="text" id="idponk" name="qty_isi" value="<?= $d->id ?>" readonly />
                                                                    </div>
                                                                </div>
                                                            </div>
                                                            <div class="form-group" hidden>
                                                                <div class="row">
                                                                    <label class="col-sm-3 control-label text-right" for="kd_user">Qty<span class="required">*</span></label>
                                                                    <div class="col-sm-8"><input class="form-control" type="number" id="qty_isi" name="qty_isi" value="<?= $d->qty_req ?>" /></div>
                                                                </div>
                                                            </div>
                                                            <div class="form-group">
                                                                <div class="row">
                                                                    <label class="col-sm-3 control-label text-right" for="kd_user">Harga Satuan<span class="required">*</span></label>
                                                                    <div class="col-sm-8"><input class="form-control" type="number" id="hrg_isi" name="hrg_isi" value="" /></div>
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
                                            </div>
                                            <!-- MODAL END -->
                                            <tr>
                                                <td><?= $d->nama_barang ?></td>
                                                <td><?= $d->deskripsi ?></td>
                                                <td><?= $d->keterangan ?></td>
                                                <td><?= $d->qty_req ?></td>
                                                <?php if ($s->tj_pembelian == "Restock By Admin PO") : ?>
                                                <?php else : ?>
                                                    <td><?= $d->qty_ready ?></td>
                                                <?php endif; ?>
                                                <td><?= $d->nm_satuan ?></td>
                                                <?php if ($s->tj_pembelian == "Restock By Admin PO") : ?>
                                                    <?php if ($d->sts == '0') : ?>
                                                        <td><a href="" class="btn btn-block btn-warning btn-sm" data-toggle="modal" data-target="#restockmodaledited<?= $d->id ?>"><i class="fas fa-plus"></i></a></td>
                                                    <?php else : ?>
                                                        <td><a href="" class="btn btn-block btn-info"></a></td>
                                                    <?php endif; ?>

                                                <?php else : ?>
                                                    <?php if ($d->sts != '0') : ?>
                                                        <td><a href="" class="btn btn-info btn-block"></a></td>
                                                    <?php else : ?>
                                                        <?php if ($tot == '0' && $d->sts == '0') : ?>
                                                            <td><a href="" class="btn btn-success btn-block"></a></td>
                                                        <?php elseif ($tot < '0' && $d->sts == '0') : ?>
                                                            <td><a href="" class="btn btn-danger btn-block"></a></td>
                                                        <?php elseif ($tot > '0' && $d->sts == '0') : ?>
                                                            <td><a href="" class="btn btn-success btn-block"></a></td>
                                                        <?php endif; ?>
                                                    <?php endif; ?>
                                                    <?php if ($d->sts == '0') : ?>
                                                        <td>
                                                            <div class="row">
                                                                <div class="col">
                                                                    <a href="<?= base_url('actconfirm/' . $d->id) ?>" class="btn btn-block btn-success btn-sm"><i class="fas fa-check"></i></a>
                                                                </div>
                                                                <div class="col">
                                                                    <a href="" class="btn btn-block btn-warning btn-sm" data-toggle="modal" data-target="#modaledited<?= $d->id ?>"><i class="fas fa-plus"></i></a>
                                                                </div>
                                                            </div>
                                                        </td>
                                                    <?php else : ?>
                                                        <td><a href="#" class="btn btn-block btn-info btn-sm"><i class="fas fa-clipboard-check "></i></a></td>
                                                    <?php endif; ?>
                                                <?php endif; ?>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>

                                <?php foreach ($countitm as $c) :
                                    $tot    = $c->total;
                                    $toty   = $c->tot_yes + $c->tot_no;
                                    $totacc = $c->tot_yes;
                                    $totpnd = $c->tot_no;
                                ?>
                                    <!-- AKSI UNTUK DAPAT MELAKUKAN PO RESTOCK  -->
                                    <?php if ($totpnd > '0') : ?>
                                        <?php echo form_open_multipart('acc_req_admin'); ?>
                                        <?php $now = date("Y-m-d"); ?>
                                        <input type="text" id="kdreqpo" name="kdreqpo" style="max-width: 550px;" value="<?= $s->kd_po_nk ?>" class="form-control" readonly hidden>
                                        <input type="text" id="kdponk" name="kdponk" style="max-width: 550px;" value="<?= $kdponks ?>" class="form-control" readonly hidden>
                                        <input type="text" id="tgl" name="tgl" style="max-width: 550px;" value="<?= $now ?>" class="form-control" readonly hidden>
                                        <input type="text" id="pic" name="pic" style="max-width: 550px;" value="<?= $s->nm_user ?>" class="form-control" readonly hidden>
                                        <input type="text" id="kdpic" name="kdpic" style="max-width: 550px;" value="<?= $s->kd_user ?>" class="form-control" readonly hidden>
                                        <input type="text" id="dep" name="dep" style="max-width: 550px;" value="<?= $s->departemen ?>" class="form-control" readonly hidden>
                                        <input type="number" id="jml" name="jml" style="max-width: 550px;" value="<?= $totpnd ?>" class="form-control" readonly hidden>
                                        <?php foreach ($countjmlharga as $ct) : ?>
                                            <input type="number" id="jmltot" name="jmltot" style="max-width: 550px;" value="<?= $ct->hrg ?>" class="form-control" readonly hidden>
                                        <?php endforeach; ?>
                                        <input type="text" id="tjbuy" name="tjbuy" style="max-width: 550px;" value="<?= $s->tj_pembelian ?>" class="form-control" readonly hidden>
                                        <button type="submit" class="btn btn-block btn-primary btn-md"><B>ORDER CONFIRMED</B></button>
                                        <!-- END AKSI RESTOCK -->

                                        <!-- AKSI TRANSAKSI / STOCK BARANG READY -->
                                    <?php else : ?>
                                        <?php echo form_open_multipart('acc_req_admin_plus'); ?>
                                        <?php $now = date("Y-m-d"); ?>
                                        <input type="text" id="kdreqpo" name="kdreqpo" style="max-width: 550px;" value="<?= $s->kd_po_nk ?>" class="form-control" readonly hidden>
                                        <input type="text" id="kdponk" name="kdponk" style="max-width: 550px;" value="<?= $kdponks ?>" class="form-control" readonly hidden>
                                        <input type="text" id="tgl" name="tgl" style="max-width: 550px;" value="<?= $now ?>" class="form-control" readonly hidden>
                                        <input type="text" id="pic" name="pic" style="max-width: 550px;" value="<?= $s->nm_user ?>" class="form-control" readonly hidden>
                                        <input type="text" id="kdpic" name="kdpic" style="max-width: 550px;" value="<?= $s->kd_user ?>" class="form-control" readonly hidden>
                                        <input type="text" id="dep" name="dep" style="max-width: 550px;" value="<?= $s->departemen ?>" class="form-control" readonly hidden>
                                        <input type="number" id="jml" name="jml" style="max-width: 550px;" value="<?= $totpnd ?>" class="form-control" readonly hidden>
                                        <?php foreach ($countjmlharga as $ct) : ?>
                                            <input type="number" id="jmltot" name="jmltot" style="max-width: 550px;" value="<?= $ct->hrg ?>" class="form-control" readonly hidden>
                                        <?php endforeach; ?>
                                        <input type="text" id="tjbuy" name="tjbuy" style="max-width: 550px;" value="<?= $s->tj_pembelian ?>" class="form-control" readonly hidden>


                                        <button type="submit" class="btn btn-block btn-primary btn-md"><B>ORDER CONFIRMED</B></button>

                                    <?php endif; ?>
                                    <!-- END TRANSAKSI /STOCK BARANG READY -->
                                <?php endforeach; ?>

                                <!-- END ON PROGRESS -->
                                <!-- START PO REVISI -->
                            <?php elseif ($s->status == 'PO REVISI') : ?>
                                <table class="table table-bordered table-striped mt-4 mb-2 ">

                                    <thead style="background-color: #212529; color:white;">
                                        <tr>
                                            <td>Nama Barang</td>
                                            <td>Deskripsi</td>
                                            <td>Keterangan</td>
                                            <td>QTY</td>
                                            <td style="width: 10%;">Qty Tersedia</td>
                                            <td>Satuan</td>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($detreq as $d) :
                                            $tot    = ($d->qty_ready) - ($d->qty_req);
                                        ?>
                                            <tr>
                                                <td><?= $d->nama_barang ?></td>
                                                <td><?= $d->deskripsi ?></td>
                                                <td><?= $d->keterangan ?></td>
                                                <td><?= $d->qty_req ?></td>
                                                <td><?= $d->qty_ready ?></td>
                                                <td><?= $d->nm_satuan ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                                <!-- END PO REVISI -->

                                <!-- STATUS : BARANG TERSEDIA -->
                            <?php elseif ($s->status == 'BARANG TERSEDIA') : ?>
                                <a href="#" class="btn btn-success btn-block mt-4"><b style="text-transform: uppercase;">item list</b></a>
                                <table class="table table-bordered table-striped mb-2">
                                    <thead style="background-color: #212529; color:white;">
                                        <tr>
                                            <td>Nama Barang</td>
                                            <td>Deskripsi</td>
                                            <td style="width:20%;">Keterangan</td>
                                            <td style="width:10%;text-align: center;">QTY</td>
                                            <td style="width:10%;text-align: center;">Satuan</td>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($gettr as $d) : ?>
                                            <tr>
                                                <td><?= $d->nama_barang ?></td>
                                                <td><?= $d->descnk ?></td>
                                                <td><?= $d->keterangan ?></td>
                                                <td style="text-align: center;"><?= $d->qty ?></td>
                                                <td style="text-align: center;"><?= $d->nm_satuan ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                                <div class="alert alert-info mb-0">Menunggu KADEP mengajukan pengambilan barang.</div>

                                <!-- END BARANG TERSEDIA -->

                                <!-- STATUS : MENUNGGU PENYERAHAN BARANG -->
                            <?php elseif (strpos(trim((string) $s->status), 'MENUNGGU PENYERAHAN') === 0) : ?>
                                <div class="alert alert-warning mt-4">KADEP telah mengajukan pengambilan barang. Konfirmasikan setelah barang diserahkan.</div>
                                <?php echo form_open_multipart('reqpicdone'); ?>
                                <input type="hidden" name="kdponk" value="<?= htmlspecialchars($s->kd_po_nk, ENT_QUOTES, 'UTF-8') ?>">
                                <input type="hidden" name="kd_user" value="<?= htmlspecialchars($s->nm_user, ENT_QUOTES, 'UTF-8') ?>">
                                <input type="hidden" name="actdone" value="<?= !empty($stspo) ? '1' : '2' ?>">
                                <button type="submit" class="btn btn-success btn-block"><i class="fas fa-check-circle"></i> KONFIRMASI PENYERAHAN BARANG</button>
                                </form>

                                <!-- STATUS : DONE -->
                            <?php elseif ($s->status == 'DONE') : ?>
                                <a href="#" class="btn btn-success btn-block mt-4"><b style="text-transform: uppercase;">REQUEST DONE</b></a>
                                <table class="table table-bordered table-striped mb-2">
                                    <thead style="background-color: #212529; color:white;">
                                        <tr>
                                            <td>Nama Barang</td>
                                            <td>Deskripsi</td>
                                            <td style="width:30%">Keterangan</td>
                                            <td style="width:10%">QTY</td>
                                            <td style="width:10%">Satuan</td>
                                            <td>Harga Satuan LIFO</td>
                                            <td>Keterangan Batch</td>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php $lifoByBarangPurchasing = array(); foreach ((isset($lifoPickup) ? $lifoPickup : array()) as $lifoRow) { $lifoByBarangPurchasing[$lifoRow->kd_barangsys] = $lifoRow; $lifoByBarangPurchasing[$lifoRow->kd_barang] = $lifoRow; } ?>
                                        <?php foreach ($gettr as $d) : ?>
                                            <?php $lifo = isset($lifoByBarangPurchasing[$d->kd_bsys]) ? $lifoByBarangPurchasing[$d->kd_bsys] : (isset($lifoByBarangPurchasing[$d->kd_barang]) ? $lifoByBarangPurchasing[$d->kd_barang] : null); ?>
                                            <tr>
                                                <td><?= $d->nama_barang ?></td>
                                                <td><?= $d->descnk ?></td>
                                                <td><?= $d->keterangan ?></td>
                                                <td style="text-align: center;"><?= $d->qty ?></td>
                                                <td style="text-align: center;"><?= $d->nm_satuan ?></td>
                                                <td style="text-align: center;"><?= $lifo && $lifo->harga_satuan !== null ? 'Rp ' . number_format($lifo->harga_satuan, 0, ',', '.') : '-' ?></td>
                                                <td class="small"><?= $lifo ? htmlspecialchars($lifo->keterangan_batch ?: 'Batch belum tervalidasi.', ENT_QUOTES, 'UTF-8') : 'Belum ada alokasi batch LIFO.' ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            <?php endif; ?>
                        </div> <!-- END BODY CARD  -->
                    </div> <!-- END CARD -->

                    <!-- START NOTE  -->
                    <div class="row mr-2">
                        <div class="col-md-8">
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
                                        <?php foreach ($log as $l) : ?>
                                            <tr>
                                                <td><?= $l->isi_note ?></td>
                                                <td><?= $l->nama_user ?></td>
                                                <td><?= $l->log_create ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    <!-- END NOTE -->

                </div><!-- /.container-fluid -->
            </div>
            <!-- /.content-header -->
        </div>
    <?php endforeach; ?>
<?php endif; ?>
