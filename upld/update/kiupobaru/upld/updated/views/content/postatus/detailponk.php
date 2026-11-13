<div class="content-wrapper">
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <div style="display: flex; text-align: center;">
                        <a href="<?= base_url('postatusnk') ?>">
                            <i class="fa fa-arrow-left  ml-4 mr-4 mt-2"></i>
                        </a>
                        <h3>Kembali</h3>
                    </div>
                </div><!-- /.col -->
            </div><!-- /.row -->
        </div>
    </div>
    <section class="content">
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
        <?php
        $canManageManualMaster = is_super_admin() || in_array((string) $this->session->userdata('lv'), array('1', '2'), true);
        $manualMasterCount = isset($manualMasterItems) ? count($manualMasterItems) : 0;
        ?>
        <?php if ($manualMasterCount > 0) : ?>
            <div class="alert alert-warning alert-dismissible fade show" role="alert">
                <div class="d-flex align-items-center justify-content-between flex-wrap">
                    <div class="mr-3 mb-2 mb-md-0">
                        <i class="fas fa-exclamation-triangle"></i>
                        Terdapat <strong><?= $manualMasterCount ?></strong> master barang manual dari PO Jasa yang perlu diinput agar seluruh item PO pembelian dapat ditampilkan dan diproses.
                    </div>
                    <?php if ($canManageManualMaster) : ?>
                        <button type="button" class="btn btn-warning btn-sm" data-toggle="modal" data-target="#manualMasterList">
                            <i class="fas fa-list"></i> Lihat List Master Barang Manual
                        </button>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
        <div class="card">
            <div class="m-2">
                <div class="">
                    <div class="row">
                        <?php foreach ($status as $s) : ?>
                            <?php
                            $blockedPonkEditStatuses = array('ACC-KADEP', 'SEDANG DIAJUKAN', 'ON PROGRESS - KADEP', 'ACC DIREKTUR', 'PROSES PEMBELIAN', 'PENGAJUAN DIBATALKAN');
                            $canUpdatePonkPengajuan = !in_array($s->status, $blockedPonkEditStatuses, true);
                            $kdPonkDetail = isset($kd) ? $kd : $s->kd_po_nk;
                            $hargaNyataStatusView = array('ACC DIREKTUR', 'PROSES PEMBELIAN', 'DONE');
                            $hargaNyataStatusInput = array('ACC DIREKTUR', 'PROSES PEMBELIAN');
                            $canShowHargaNyata = (int) $s->status_hrg_nyata === 1 && in_array($s->status, $hargaNyataStatusView, true);
                            $canEditHargaNyata = ($this->session->userdata('lv') == '2' || is_super_admin()) && (int) $s->status_hrg_nyata === 1 && in_array($s->status, $hargaNyataStatusInput, true);
                            $canSwitchHargaNyata = ($this->session->userdata('lv') == '2' || is_super_admin()) && in_array($s->status, $hargaNyataStatusInput, true);
                            ?>
                            <div class="col-2">
                                <label for="naSupp" class="">NOMOR PO : </label>
                                <input type="text" id="naCus" name="naSupp" style="max-width: 550px;" value="<?= $s->nopo ?>" class="form-control" readonly>
                            </div>
                            <div class="col-2">
                                <label for="tgTrans" class="">Tanggal Transaksi : &nbsp;&nbsp; </label>
                                <input type="text" id="tgTrans" name="tgTrans" style="max-width: 250px;" value="<?= $s->tgl_transaksi ?>" class="form-control" readonly>
                            </div>
                            <div class="col-2">
                                <label for="naSupp" class="">Nama Pengaju: </label>
                                <input type="text" id="naCus" name="naSupp" style="max-width: 550px;" value="<?= $s->nm_user ?>" class="form-control" readonly>
                            </div>
                            <div class="col-2">
                                <label for="noInv" class="">Departemen</label>
                                <input type="text" id="noInv" name="noInv" value="<?= $s->departemen ?>" class="form-control" readonly>
                            </div>

                            <!--USER -->

                            <?php if ($this->session->userdata('lv') == '4') : ?>
                                <div class="col-lg">
                                    <label for="tgTrans" class="">Status Order : &nbsp;&nbsp; </label>
                                    <?php if ($s->status == 'DONE') : ?>
                                        <div>
                                            <a href="<?= base_url('printOrdernk/') . $s->kd_po_nk ?>" target="_blank" class="btn btn-success btn-block"><i class="fas fa-print"></i> Cetak Form Order</a>
                                        </div>
                                    <?php elseif ($s->status == 'ON PROGRESS') : ?>
                                        <div>
                                            <a href="#" class="btn btn-warning btn-block"><i class="fas fa-clock"></i> ON PROGRESS</a>
                                        </div>
                                    <?php elseif ($s->status == 'NOTE KEUANGAN') : ?>
                                        <div>
                                            <a href="#" class="btn btn-warning btn-block"><i class="fas fa-clock"></i> ON PROGRESS</a>
                                        </div>
                                    <?php elseif ($s->status == 'UPDATE DIREKTUR') : ?>
                                        <div>
                                            <a href="#" class="btn btn-warning btn-block"><i class="fas fa-exclamation"></i> Terdapat Update</a>
                                        </div>
                                    <?php elseif ($s->status == 'REJECT') : ?>
                                        <div>
                                            <a href="#" class="btn btn-danger btn-block"><i class="fas fa-times"></i> Order Ditolak</a>
                                        </div>
                                    <?php elseif ($s->status == 'PO REVISI') : ?>
                                        <div>
                                            <a href="<?= base_url('repostponk/') . $s->kd_po_nk ?>" class="btn btn-warning btn-block"><i class="fas fa-undo"></i> PO REVISI</a>
                                        </div>
                                    <?php elseif ($s->status == 'PENDING') : ?>
                                        <div class="row">
                                            <div class="col">
                                                <a href="#" class="btn btn-warning btn-block"><i class="fas fa-pause"></i> PO PENDING</a>
                                            </div>
                                            <div class="col">
                                                <a href="<?= base_url('repostponk/') . $s->kd_po_nk ?>" class="btn btn-success btn-block"><i class="fas fa-undo"></i> REPOST PO </a>
                                            </div>
                                        </div>
                                    <?php elseif ($s->status == 'ACC-KADEP') : ?>
                                        <div>
                                            <a href="#" class="btn btn-primary btn-block"><i class="fas fa-thumbs-up"></i> PO ACCEPT KADEP</a>
                                        </div>
                                    <?php elseif ($s->status == 'SEDANG DIAJUKAN') : ?>
                                        <div>
                                            <a href="#" class="btn btn-warning btn-block"><i class="fas fa-clock"></i> SEDANG DIAJUKAN</a>
                                        </div>

                                    <?php elseif ($s->status == 'ON PROGRESS - KADEP') : ?>
                                        <div>
                                            <a href="#" class="btn btn-warning btn-block"><i class="fas fa-clock"></i> MENUNGGU ACC KADEP</a>
                                        </div>
                                    <?php elseif ($s->status == 'ACC DIREKTUR') : ?>
                                        <div>
                                            <a href="#" class="btn btn-primary btn-block"><i class="fas fa-thumbs-up"></i>ACC DIREKTUR</a>
                                        </div>
                                    <?php elseif ($s->status == 'PROSES PEMBELIAN') : ?>
                                        <div>
                                            <a href="#" class="btn btn-warning btn-block"><i class="fas fa-truck-moving"></i>PROSES PEMBELIAN</a>
                                        </div>
                                    <?php elseif ($s->status == 'PENGAJUAN DIBATALKAN') : ?>
                                        <div>
                                            <a href="#" class="btn btn-secondary btn-block"><i class="fas fa-ban"></i> PENGAJUAN DIBATALKAN</a>
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <!--PURCHASING -->

                            <?php elseif ($this->session->userdata('lv') == '2' || is_super_admin()) : ?>
                                <div class="col-lg">
                                    <label for="tgTrans" class="">Status Order : &nbsp;&nbsp; </label>
                                    <?php if ($s->status == 'DONE') : ?>
                                        <div>
                                            <a href="<?= base_url('printOrdernk/') . $s->kd_po_nk ?>" target="_blank" class="btn btn-success btn-block"><i class="fas fa-print"></i> Cetak Form Order</a>
                                        </div>

                                    <?php elseif ($s->status == 'ON PROGRESS') : ?>
                                        <div>
                                            <a href="#" class="btn btn-warning btn-block"><i class="fas fa-clock"></i> ON PROGRESS</a>
                                        </div>
                                    <?php elseif ($s->status == 'NOTE KEUANGAN') : ?>
                                        <div>
                                            <a href="#" class="btn btn-warning btn-block"><i class="fas fa-clock"></i> ON PROGRESS</a>
                                        </div>
                                    <?php elseif ($s->status == 'UPDATE DIREKTUR') : ?>
                                        <div>
                                            <a href="#" class="btn btn-warning btn-block"><i class="fas fa-exclamation"></i> Terdapat Update</a>
                                        </div>
                                    <?php elseif ($s->status == 'REJECT') : ?>
                                        <div>
                                            <a href="#" class="btn btn-danger btn-block"><i class="fas fa-times"></i> Order Ditolak</a>
                                        </div>
                                    <?php elseif ($s->status == 'PO REVISI') : ?>
                                        <div>
                                            <a href="#" class="btn btn-warning btn-block"><i class="fas fa-undo"></i> PO REVISI</a>
                                        </div>
                                    <?php elseif ($s->status == 'PENDING') : ?>
                                        <div>
                                            <a href="#" class="btn btn-warning btn-block"><i class="fas fa-pause"></i> PO PENDING</a>
                                        </div>
                                    <?php elseif ($s->status == 'ON PROGRESS - KADEP') : ?>
                                        <div>
                                            <a href="#" class="btn btn-warning btn-block"><i class="fas fa-pause"></i> ON PROGRESS - KADEP</a>
                                        </div>
                                    <?php elseif ($s->status == 'ACC-KADEP') : ?>
                                        <div>
                                            <a href="#" class="btn btn-primary btn-block"><i class="fas fa-thumbs-up"></i> PO ACCEPT KADEP</a>
                                        </div>
                                    <?php elseif ($s->status == 'SEDANG DIAJUKAN') : ?>
                                        <div>
                                            <a href="#" class="btn btn-primary btn-block"><i class="fas fa-thumbs-up"></i> PO SEDANG DIAJUKAN</a>
                                        </div>
                                    <?php elseif ($s->status == 'ACC DIREKTUR') : ?>
                                        <div>
                                            <a href="#" class="btn btn-primary btn-block"><i class="fas fa-thumbs-up"></i>ACC DIREKTUR</a>
                                        </div>
                                    <?php elseif ($s->status == 'PROSES PEMBELIAN') : ?>
                                        <div>
                                            <a href="#" class="btn btn-warning btn-block"><i class="fas fa-truck-moving"></i> PROSES PEMBELIAN</a>
                                        </div>
                                    <?php elseif ($s->status == 'PENGAJUAN DIBATALKAN') : ?>
                                        <div>
                                            <a href="#" class="btn btn-secondary btn-block"><i class="fas fa-ban"></i> PENGAJUAN DIBATALKAN</a>
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <!--KADEP-->

                            <?php elseif ($this->session->userdata('lv') == '5') : ?>
                                <div class="col-lg">
                                    <label for="tgTrans" class="">Status Order : &nbsp;&nbsp; </label>
                                    <?php if ($s->status == 'DONE') : ?>
                                        <div>
                                            <a href="<?= base_url('printOrdernk/') . $s->kd_po_nk ?>" target="_blank" class="btn btn-success btn-block"><i class="fas fa-print"></i> Cetak Form Order</a>
                                        </div>
                                    <?php elseif ($s->status == 'ACC-KADEP') : ?>
                                        <div class="row">
                                            <div class="col">
                                                <a href="#" class="btn btn-block btn-primary thumbs-up"><i class="fas fa-thumbs-up"></i> ACC - KADEP</a>
                                            </div>
                                            <div class="col">
                                                <a href="#" class="btn btn-block btn-danger" data-toggle="modal" data-target="#modalReject"><i class="fas fa-times"></i> REJECT PO</a>
                                            </div>
                                        </div>
                                    <?php elseif ($s->status == 'UPDATE DIREKTUR') : ?>
                                        <div>
                                            <a href="#" class="btn btn-warning btn-block"><i class="fas fa-exclamation"></i> Terdapat Update</a>
                                        </div>
                                    <?php elseif ($s->status == 'REJECT') : ?>
                                        <div>
                                            <a href="#" class="btn btn-danger btn-block"><i class="fas fa-times"></i> Order Ditolak</a>
                                        </div>
                                    <?php elseif ($s->status == 'PO REVISI') : ?>
                                        <div>
                                            <a href="#" class="btn btn-warning btn-block"><i class="fas fa-undo"></i> PO REVISI</a>
                                        </div>
                                    <?php elseif ($s->status == 'PENDING') : ?>
                                        <div>
                                            <a href="#" class="btn btn-warning btn-block"><i class="fas fa-pause"></i> PO PENDING</a>
                                        </div>
                                    <?php elseif ($s->status == 'ON PROGRESS' || $s->status == 'ON PROGRESS - KADEP') : ?>
                                        <div class="row">
                                            <div class="col">
                                                <a class="btn btn-block btn-success btn-md" href="<?= base_url('konfirmasiOrderNK/') . $s->kd_po_nk . '/' . $this->session->userdata('kode') ?>">
                                                    <i class="fas fa-clipboard-check"></i>
                                                    Accept
                                                </a>
                                            </div>
                                            <div class="col">
                                                <a class="btn btn-block btn-danger btn-md" href="<?= base_url('tolakordernk/') . $s->kd_po_nk . '/' . $this->session->userdata('kode') ?>">
                                                    <i class="fas fa-times"></i> &nbsp;
                                                    Reject
                                                </a>
                                            </div>
                                            <div class="col">
                                                <a class="btn btn-block btn-warning btn-md" data-toggle="modal" data-target="#modalrev<?= $s->kd_po_nk ?>">
                                                    <i class="fab fa-rev"></i> &nbsp;
                                                    Revisi
                                                </a>
                                            </div>
                                            <div class="col">
                                                <a class="btn btn-block btn-warning btn-md" data-toggle="modal" data-target="#modalPending<?= $s->kd_po_nk ?>">
                                                    <i class="fas fa-clock"></i> &nbsp;
                                                    Pending
                                                </a>
                                            </div>
                                        </div>
                                    <?php elseif ($s->status == 'SEDANG DIAJUKAN') : ?>
                                        <div class="row">
                                            <a href="#" class="btn btn-primary btn-block"><i class="fas fa-thumbs-up"></i> PO SEDANG DIAJUKAN</a>
                                        </div>
                                    <?php elseif ($s->status == 'ACC DIREKTUR') : ?>
                                        <div>
                                            <a href="#" class="btn btn-primary btn-block"><i class="fas fa-thumbs-up"></i>ACC DIREKTUR</a>
                                        </div>
                                    <?php elseif ($s->status == 'PROSES PEMBELIAN') : ?>
                                        <div>
                                            <a href="#" class="btn btn-warning btn-block"><i class="fas fa-truck-moving"></i> PROSES PEMBELIAN</a>
                                        </div>
                                    <?php elseif ($s->status == 'PENGAJUAN DIBATALKAN') : ?>
                                        <div>
                                            <a href="#" class="btn btn-secondary btn-block"><i class="fas fa-ban"></i> PENGAJUAN DIBATALKAN</a>
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <!-- DIREKTUR -->

                            <?php elseif ($this->session->userdata('lv') == '3' && $s->status == 'SEDANG DIAJUKAN') : ?>
                                <div class="col">
                                    <div class="row">
                                        <div class="col">
                                            <label for="tgTrans" class="">Status Order : </label>
                                            <a class="btn btn-block btn-success btn-md" href="<?= base_url('konfirmasiOrderdirNK/') . $s->kd_po_nk . '/' . $this->session->userdata('kode') ?>">
                                                <i class="fas fa-clipboard-check"></i>
                                                Accept
                                            </a>
                                        </div>
                                        <div class="col">
                                            <label for="tgTrans" class=""> &nbsp;&nbsp; </label>
                                            <a class="btn btn-block btn-danger btn-md" href="<?= base_url('tolakordernk/') . $s->kd_po_nk . '/' . $this->session->userdata('kode') ?>">
                                                <i class="fas fa-times"></i> &nbsp;
                                                Reject
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            <?php elseif ($this->session->userdata('lv') == '3' && $s->status == 'REJECT') : ?>
                                <div class="col">
                                    <div class="row">
                                        <div class="col">
                                            <label for="tgTrans" class="">Status Order : </label>
                                            <a href="#" class="btn btn-danger btn-block"><i class="fas fa-times"></i> Order Ditolak</a>
                                        </div>
                                    </div>
                                </div>
                            <?php elseif ($this->session->userdata('lv') == '3' && $s->status == 'PENGAJUAN DIBATALKAN') : ?>
                                <div class="col">
                                    <div class="row">
                                        <div class="col">
                                            <label for="tgTrans" class="">Status Order : </label>
                                            <a href="#" class="btn btn-secondary btn-block"><i class="fas fa-ban"></i> PENGAJUAN DIBATALKAN</a>
                                        </div>
                                    </div>
                                </div>
                            <?php endif; ?>

                            <?php if (is_super_admin() && ($s->status == 'ON PROGRESS' || $s->status == 'ON PROGRESS - KADEP')) : ?>
                                <div class="col">
                                    <label for="tgTrans" class="">Approval KADEP : &nbsp;&nbsp; </label>
                                    <div class="row">
                                        <div class="col">
                                            <a class="btn btn-block btn-success btn-md" href="<?= base_url('konfirmasiOrderNK/') . $s->kd_po_nk . '/' . $this->session->userdata('kode') ?>">
                                                <i class="fas fa-clipboard-check"></i>
                                                Accept
                                            </a>
                                        </div>
                                        <div class="col">
                                            <a class="btn btn-block btn-danger btn-md" href="<?= base_url('tolakordernk/') . $s->kd_po_nk . '/' . $this->session->userdata('kode') ?>">
                                                <i class="fas fa-times"></i>
                                                Reject
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            <?php elseif (is_super_admin() && $s->status == 'SEDANG DIAJUKAN') : ?>
                                <div class="col">
                                    <label for="tgTrans" class="">Approval Direktur : &nbsp;&nbsp; </label>
                                    <div class="row">
                                        <div class="col">
                                            <a class="btn btn-block btn-success btn-md" href="<?= base_url('konfirmasiOrderdirNK/') . $s->kd_po_nk . '/' . $this->session->userdata('kode') ?>">
                                                <i class="fas fa-clipboard-check"></i>
                                                Accept
                                            </a>
                                        </div>
                                        <div class="col">
                                            <a class="btn btn-block btn-danger btn-md" href="<?= base_url('tolakordernk/') . $s->kd_po_nk . '/' . $this->session->userdata('kode') ?>">
                                                <i class="fas fa-times"></i>
                                                Reject
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            <?php endif; ?>

                            <?php if ($this->session->userdata('lv') == '4' && $s->status == 'ACC-KADEP') : ?>

                            <?php elseif ($this->session->userdata('lv') == '4' && $s->status == 'ON PROGRESS') : ?>
                                <div class="col">
                                    <div class="row">
                                        <div class="col">
                                            <label for="tgTrans" class="">Konfirmasi Update : &nbsp;&nbsp; </label>
                                            <a class="btn btn-block btn-primary btn-md" data-toggle="modal" data-target="#modalAddNote">
                                                <i class="fas fa-clipboard-check"></i> &nbsp;
                                                Update
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            <?php elseif (($this->session->userdata('lv') == '2' || is_super_admin()) && $s->status == 'ACC-KADEP') : ?>
                                <div class="col">
                                    <label for="tgTrans" class="">Konfirmasi Update : &nbsp;&nbsp; </label>
                                    <div class="row">
                                        <div class="col">
                                            <a class="btn btn-block btn-primary btn-md" data-toggle="modal" data-target="#modalAddNote">
                                                <i class="fas fa-clipboard-check"></i>
                                                PEMBELIAN
                                            </a>
                                        </div>
                                        <div class="col">
                                            <a class="btn btn-block btn-warning btn-md" data-toggle="modal" data-target="#modalrev<?= $s->kd_po_nk ?>">
                                                <i class="fab fa-rev"></i>
                                                PO REVISI
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            <?php elseif (($this->session->userdata('lv') == '2' || is_super_admin()) && $s->status == 'PROSES PEMBELIAN') : ?>
                                <div class="col">
                                    <div class="row">
                                        <div class="col">
                                            <label for="tgTrans" class="">Konfirmasi Update : &nbsp;&nbsp; </label>
                                            <a class="btn btn-block btn-success btn-md" data-toggle="modal" data-target="#modalonhand<?= $s->kd_po_nk ?>">
                                                <i class="fas fa-check-circle"></i> &nbsp;
                                                ON HAND
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            <?php elseif (($this->session->userdata('lv') == '2' || is_super_admin()) && $s->status == 'ACC DIREKTUR') : ?>
                                <div class="col">
                                    <div class="row">
                                        <div class="col">
                                            <label for="tgTrans" class="">Konfirmasi Update : &nbsp;&nbsp; </label>
                                            <a class="btn btn-block btn-primary btn-md" data-toggle="modal" data-target="#ajukanpembelian<?= $s->kd_po_nk ?>">
                                                <i class="fas fa-clipboard-check"></i> &nbsp;
                                                PROSES PEMBELIAN
                                            </a>
                                        </div>
                                    </div>
                                </div>

                            <?php endif; ?>
                            <?php if ($canUpdatePonkPengajuan) : ?>
                                <div class="col-lg">
                                    <label>&nbsp;</label>
                                    <button type="button" class="btn btn-danger btn-block btn-cancel-pengajuan-ponk" data-kd-po-req="<?= htmlspecialchars($s->kd_po_req, ENT_QUOTES, 'UTF-8') ?>">
                                        <i class="fas fa-ban"></i> CANCEL PENGAJUAN
                                    </button>
                                </div>
                            <?php endif; ?>
                    </div>

                </div>
            </div>
            <div class="col">
                <div class="d-flex justify-content-between align-items-center mb-1">
                    <label for="tujuanPembelianPonk" class="mb-0">Tujuan Pembelian</label>
                    <?php if ($canUpdatePonkPengajuan) : ?>
                        <button type="button" class="btn btnAtas btn-sm" data-toggle="modal" data-target="#modalEditTujuanPembelianPonk<?= $s->kd_po_nk ?>">
                            <i class="fas fa-pencil-alt"></i> EDIT DATA
                        </button>
                    <?php endif; ?>
                </div>
                <textarea type="text" id="tujuanPembelianPonk" name="noInv" class="form-control mb-2" readonly><?= htmlspecialchars($s->tj_pembelian, ENT_QUOTES, 'UTF-8') ?></textarea>
            </div>
            <?php if ($canShowHargaNyata && in_array((string) $this->session->userdata('lv'), array('4', '5'), true)) : ?>
                <div class="col-12">
                    <div class="alert alert-info">
                        <?php foreach ($totalnyata as $tn) : ?>
                            <b>Total Harga Nyata:</b> Rp. <?= number_format($tn->total_nyata) ?>
                        <?php endforeach; ?>
                        <?php if (!empty($hargaNyataSummary)) : ?>
                            <span class="ml-3">Belum Input: <?= (int) $hargaNyataSummary->belum_input ?></span>
                            <span class="ml-3">Pending Direktur: <?= (int) $hargaNyataSummary->pending_direktur ?></span>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <?php if ($canUpdatePonkPengajuan) : ?>
            <div class="modal fade" id="modalEditTujuanPembelianPonk<?= $s->kd_po_nk ?>" tabindex="-1" role="dialog" aria-labelledby="modalEditTujuanPembelianPonkLabel<?= $s->kd_po_nk ?>" aria-hidden="true">
                <div class="modal-dialog" role="document">
                    <form class="modal-content form-edit-tujuan-pembelian-ponk">
                        <div class="modal-header">
                            <h5 class="modal-title" id="modalEditTujuanPembelianPonkLabel<?= $s->kd_po_nk ?>">Edit Tujuan Pembelian</h5>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="modal-body">
                            <input type="hidden" name="kd_po_req" value="<?= htmlspecialchars($s->kd_po_req, ENT_QUOTES, 'UTF-8') ?>">
                            <div class="form-group">
                                <label>Tujuan Pembelian</label>
                                <textarea name="tujuan_pembelian" class="form-control" rows="5" required><?= htmlspecialchars($s->tj_pembelian, ENT_QUOTES, 'UTF-8') ?></textarea>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Simpan
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        <?php endif; ?>

        <!-- FITUR ADD -->
        <?php $this->load->view('content/postatus/modal_setting/modalponk') ?>
        <?php if ($this->session->userdata('lv') == '4' && $s->status == 'DONE') : ?>

        <?php elseif ($this->session->userdata('lv') == '4' && $s->status == 'REJECT') : ?>
        <?php elseif ($this->session->userdata('lv') == '5' && $s->status == 'DONE') : ?>
        <?php elseif ($this->session->userdata('lv') == '5' && $s->status == 'REJECT') : ?>
        <?php elseif ($this->session->userdata('lv') == '5' && $s->status != 'DONE') : ?>
        <?php elseif ($this->session->userdata('lv') == '4' && $s->status == 'ACC-KADEP') : ?>
        <?php elseif ($this->session->userdata('lv') == '4' && $s->status == 'ACC DIREKTUR') : ?>
        <?php elseif ($this->session->userdata('lv') == '2' && $s->status == 'SEDANG DIAJUKAN') : ?>
        <?php elseif ($this->session->userdata('lv') == '2' && $s->status == 'ON PROGRESS - KADEP') : ?>
        <?php elseif ($this->session->userdata('lv') == '4' && $s->status == 'SEDANG DIAJUKAN') : ?>
        <?php elseif ($this->session->userdata('lv') == '4' && $s->status == 'PROSES PEMBELIAN') : ?>
        <?php elseif ($this->session->userdata('lv') == '2' && $s->status == 'DONE') : ?>
        <?php elseif ($this->session->userdata('lv') == '2' && $s->status == 'PROSES PEMBELIAN') : ?>
        <?php elseif ($this->session->userdata('lv') == '2' && $s->status == 'ACC DIREKTUR') : ?>

        <?php elseif (($this->session->userdata('lv') == '2' || is_super_admin()) && $s->status != 'ON PROGRESS') : ?>
            <div class="row">
                <div class="col-md mb-2">
                    <a class="btn btnAtas btn-sm btn-block" data-toggle="modal" data-target="#addnopo<?= $s->kd_po_nk ?>">
                        <i class="fas fa-plus"> </i>
                        Input Nomor PO
                    </a>
                </div>
                <div class="col-md">
                    <a class="btn btnAtas btn-sm btn-block" data-toggle="modal" data-target="#addtax">
                        <i class="fas fa-percent"> </i>
                        Setting Tax
                    </a>
                </div>
                <?php if ($canSwitchHargaNyata && $s->status_hrg_nyata == '0') : ?>
                    <div class="col-md">
                        <a href="<?= base_url('hrgnyataon/' . $s->kd_po_nk) ?>" class="btn btn-secondary btn-sm btn-block">
                            <i class="fas fa-toggle-off"></i>
                            Harga Nyata OFF
                        </a>
                    </div>
                <?php elseif ($canSwitchHargaNyata && $s->status_hrg_nyata == '1') : ?>
                    <div class="col-md">
                        <a href="<?= base_url('hrgnyataoff/' . $s->kd_po_nk) ?>" class="btn btn-success btn-sm btn-block">
                            <i class="fas fa-toggle-on"></i>
                            Harga Nyata ON
                        </a>
                    </div>
                <?php endif; ?>
                <div class="col-md">
                    <a class="btn btnAtas btn-sm btn-block" data-toggle="modal" data-target="#add_note_pembelian">
                        <i class="fas fa-vote-yea"> </i>
                        Add Note Pembelian
                    </a>
                </div>
                <div class="col-md">
                    <a class="btn btnAtas btn-sm btn-block" data-toggle="modal" data-target="#addfileupload<?= $s->kd_po_nk ?>">
                        <i class="fa fa-solid fa-file-image"></i>
                        Add file pendukung
                    </a>
                </div>
            </div>
            <!-- ELSEIF == 4 -->
        <?php elseif ($this->session->userdata('lv') == '4' && $s->status != 'DONE' && $s->status != 'ON PROGRESS - KADEP') : ?>
            <div class="row">
                <div class="col-md mb-2">
                    <a class="btn btnAtas btn-sm btn-block" href="<?= base_url('listbarangnk/1/2/3/' . $s->kd_po_nk) ?>">
                        <i class="fas fa-plus"> </i>
                        Tambah Barang
                    </a>
                </div>
                <div class="col-md">
                    <a class="btn btnAtas btn-sm btn-block" data-toggle="modal" data-target="#edponk<?= $s->kd_po_nk ?>">
                        <i class="fas fa-server"> </i>
                        Edit Data Po
                    </a>
                </div>
                <div class="col-md">
                    <a class="btn btnAtas btn-sm btn-block" data-toggle="modal" data-target="#add_note_pembelian">
                        <i class="fas fa-vote-yea"> </i>
                        Tambah Note Pembelian
                    </a>
                </div>
            </div>
        <?php endif; ?>
        <?php if ($canSwitchHargaNyata) : ?>
            <div class="row px-2 pb-2">
                <div class="col-md-3 mb-2">
                    <?php if ((int) $s->status_hrg_nyata === 1) : ?>
                        <a href="<?= base_url('hrgnyataoff/' . $s->kd_po_nk) ?>" class="btn btn-success btn-sm btn-block">
                            <i class="fas fa-toggle-on"></i>
                            Harga Nyata ON
                        </a>
                    <?php else : ?>
                        <a href="<?= base_url('hrgnyataon/' . $s->kd_po_nk) ?>" class="btn btn-secondary btn-sm btn-block">
                            <i class="fas fa-toggle-off"></i>
                            Harga Nyata OFF
                        </a>
                    <?php endif; ?>
                </div>
                <?php if (!empty($hargaNyataSummary) && (int) $s->status_hrg_nyata === 1) : ?>
                    <div class="col-md-9 mb-2">
                        <div class="alert alert-info mb-0 py-2">
                            Item belum input: <?= (int) $hargaNyataSummary->belum_input ?> |
                            Harga lebih tinggi: <?= (int) $hargaNyataSummary->harga_lebih_tinggi ?> |
                            Pending Direktur: <?= (int) $hargaNyataSummary->pending_direktur ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <table class="table table-bordered table-striped ">
            <thead style="background-color: #212529; color:white;">
                <tr>
                    <td>No</td>
                    <td>Nama Barang</td>
                    <td>Deskripsi</td>
                    <td>Keterangan</td>
                    <td>Qty</td>
                    <td>Harga</td>
                    <td>Total Harga</td>

                    <!-- HARGA NYATA -->
                    <?php if ($canShowHargaNyata) : ?>
                        <td>Qty Nyata</td>
                        <td>Harga Nyata</td>
                        <td>Total Harga Nyata</td>
                        <td>Approval Harga</td>
                    <?php endif; ?>

                    <!-- END HARGANYATA -->

                    <td>Gambar Barang</td>
                    <?php if (is_super_admin()) : ?>
                        <td>#</td>
                    <?php elseif ($this->session->userdata('lv') == '4' && $s->status == 'ACC-KADEP') : ?>
                    <?php elseif ($this->session->userdata('lv') == '2' && $s->status == 'ACC-KADEP') : ?>
                        <td>#</td>
                    <?php elseif ($this->session->userdata('lv') == '4' && $s->status == 'ON PROGRESS' || $s->status == 'PO REVISI') : ?>
                        <td>#</td>
                    <?php elseif ($this->session->userdata('lv') == '4' || $this->session->userdata('lv') == '2' && $s->status == 'DONE') : ?>
                    <?php elseif ($this->session->userdata('lv') == '4' || $this->session->userdata('lv') == '2' && $s->status == 'REJECT') : ?>
                    <?php elseif ($this->session->userdata('lv') == '4' || $this->session->userdata('lv') == '2' && $s->status == 'SEDANG DIAJUKAN') : ?>
                    <?php endif; ?>
                </tr>
            </thead>
            <tbody>
                <?php $no = 1;
                            foreach ($detail as $d) :
                                if ($d->gbr_barang == 'Karisma.png') {
                                    $imagePath = "../images/gbrbarang/Karisma.png";
                                } else {
                                    $imagePath = "../images/gbrbarang/masterbr/" . $d->gbr_barang;
                                }
                                $qtyNyata = isset($d->qty_nyata) && (float) $d->qty_nyata > 0 ? (float) $d->qty_nyata : 0;
                                $hargaNyata = isset($d->hrg_nyata) ? (float) $d->hrg_nyata : 0;
                                $totalNyata = isset($d->total_nyata) ? (float) $d->total_nyata : 0;
                                $statusApprovalHarga = isset($d->status_approval_harga_nyata) ? (string) $d->status_approval_harga_nyata : '';

                ?>
                    <tr>
                        <td><?= $no++; ?></td>
                        <td><?= $d->nama_barang ?></td>
                        <td><?= $d->deskripsi ?></td>
                        <td><?= $d->keterangan ?></td>
                        <td><?= $d->qty ?></td>
                        <td>Rp. <?= number_format($d->hrg_satuan) ?></td>
                        <td>Rp. <?= number_format($d->total_harga) ?></td>
                        <!-- HARGA NYATA -->
                        <?php if ($canShowHargaNyata) : ?>
                            <td><?= $qtyNyata > 0 ? number_format($qtyNyata, 2) : '-' ?></td>
                            <td>Rp. <?= number_format($hargaNyata) ?></td>
                            <td>Rp. <?= number_format($totalNyata) ?></td>
                            <td>
                                <?php if ($statusApprovalHarga === 'PENDING_DIREKTUR') : ?>
                                    <span class="badge badge-warning d-block mb-1">PENDING DIREKTUR</span>
                                    <?php if ($this->session->userdata('lv') == '3' || is_super_admin()) : ?>
                                        <a href="<?= base_url('approve_harganyata/' . $d->id_det_po_nk) ?>" class="btn btn-success btn-xs">Approve</a>
                                        <a href="<?= base_url('reject_harganyata/' . $d->id_det_po_nk) ?>" class="btn btn-danger btn-xs">Reject</a>
                                    <?php endif; ?>
                                <?php elseif ($statusApprovalHarga === 'DISETUJUI_DIREKTUR') : ?>
                                    <span class="badge badge-success">DISETUJUI DIREKTUR</span>
                                <?php elseif ($statusApprovalHarga === 'DITOLAK_DIREKTUR') : ?>
                                    <span class="badge badge-danger">DITOLAK DIREKTUR</span>
                                <?php elseif ($statusApprovalHarga === 'DISETUJUI_OTOMATIS') : ?>
                                    <span class="badge badge-info">OTOMATIS</span>
                                <?php else : ?>
                                    <span class="badge badge-secondary">BELUM INPUT</span>
                                <?php endif; ?>
                                <?php if (!empty($d->alasan_realisasi)) : ?>
                                    <div class="small text-muted mt-1"><?= htmlspecialchars($d->alasan_realisasi, ENT_QUOTES, 'UTF-8') ?></div>
                                <?php endif; ?>
                            </td>
                        <?php endif; ?>
                        <!-- END HARGA NYATA -->
                        <td>
                            <!-- GAMBAR -->

                            <?php if (is_super_admin()) : ?>
                                <a href="<?= $imagePath ?>" target="_blank"><img src="<?php echo $imagePath ?>" style="width:50px; height:50px"></a>
                            <?php elseif ($this->session->userdata('lv') == '4' && $s->status == 'ON PROGRESS') : ?>
                                <a href="<?= $imagePath ?>" target="_blank"><img src="<?php echo $imagePath ?>" style="width:50px; height:50px"></a>
                            <?php elseif ($this->session->userdata('lv') == '4' && $s->status == 'ACC-KADEP') : ?>
                                <a href="<?= $imagePath ?>" target="_blank"><img src="<?php echo $imagePath ?>" style="width:50px; height:50px"></a>
                            <?php elseif ($this->session->userdata('lv') == '4' && $s->status == 'ACC DIREKTUR') : ?>
                                <a href="<?= $imagePath ?>" target="_blank"><img src="<?php echo $imagePath ?>" style="width:50px; height:50px"></a>
                            <?php elseif ($this->session->userdata('lv') == '4' && $s->status == 'ON PROGRESS - KADEP') : ?>
                                <a href="<?= $imagePath ?>" target="_blank"><img src="<?php echo $imagePath ?>" style="width:50px; height:50px"></a>
                            <?php elseif ($this->session->userdata('lv') == '4' && $s->status == 'PROSES PEMBELIAN') : ?>
                                <a href="<?= $imagePath ?>" target="_blank"><img src="<?php echo $imagePath ?>" style="width:50px; height:50px"></a>
                            <?php elseif ($this->session->userdata('lv') == '4' && $s->status == 'DONE') : ?>
                                <a href="<?= $imagePath ?>" target="_blank"><img src="<?php echo $imagePath ?>" style="width:50px; height:50px"></a>
                            <?php elseif ($this->session->userdata('lv') == '4' && $s->status == 'SEDANG DIAJUKAN') : ?>
                                <a href="<?= $imagePath ?>" target="_blank"><img src="<?php echo $imagePath ?>" style="width:50px; height:50px"></a>
                            <?php elseif ($this->session->userdata('lv') == '4' && $s->status == 'PO REVISI') : ?>
                                <a href="<?= $imagePath ?>" target="_blank"><img src="<?php echo $imagePath ?>" style="width:50px; height:50px"></a>
                            <?php elseif ($this->session->userdata('lv') == '2' && $s->status == 'SEDANG DIAJUKAN') : ?>
                                <a href="<?= $imagePath ?>" target="_blank"><img src="<?php echo $imagePath ?>" style="width:50px; height:50px"></a>
                            <?php elseif ($this->session->userdata('lv') == '2' && $s->status == 'DONE') : ?>
                                <a href="<?= $imagePath ?>" target="_blank"><img src="<?php echo $imagePath ?>" style="width:50px; height:50px"></a>
                            <?php elseif ($this->session->userdata('lv') == '2' && $s->status == 'PROSES PEMBELIAN') : ?>
                                <a href="<?= $imagePath ?>" target="_blank"><img src="<?php echo $imagePath ?>" style="width:50px; height:50px"></a>
                            <?php elseif ($this->session->userdata('lv') == '2' && $s->status == 'ACC DIREKTUR') : ?>
                                <a href="<?= $imagePath ?>" target="_blank"><img src="<?php echo $imagePath ?>" style="width:50px; height:50px"></a>
                            <?php elseif ($this->session->userdata('lv') == '2' && $s->status == 'ACC-KADEP') : ?>
                                <a href="<?= $imagePath ?>" target="_blank"><img src="<?php echo $imagePath ?>" style="width:50px; height:50px"></a>
                            <?php elseif ($this->session->userdata('lv') == '3' && $s->status == 'SEDANG DIAJUKAN') : ?>
                                <a href="<?= $imagePath ?>" target="_blank"><img src="<?php echo $imagePath ?>" style="width:50px; height:50px"></a>
                            <?php elseif ($this->session->userdata('lv') == '5' && $s->status == 'ON PROGRESS - KADEP') : ?>
                                <a href="<?= $imagePath ?>" target="_blank"><img src="<?php echo $imagePath ?>" style="width:50px; height:50px"></a>
                            <?php elseif ($this->session->userdata('lv') == '5' && $s->status == 'PO REVISI') : ?>
                                <a href="<?= $imagePath ?>" target="_blank"><img src="<?php echo $imagePath ?>" style="width:50px; height:50px"></a>
                            <?php elseif ($this->session->userdata('lv') == '5' && $s->status == 'ON PROGRESS') : ?>
                                <a href="<?= $imagePath ?>" target="_blank"><img src="<?php echo $imagePath ?>" style="width:50px; height:50px"></a>
                            <?php elseif ($this->session->userdata('lv') == '5' && $s->status == 'ACC-KADEP') : ?>
                                <a href="<?= $imagePath ?>" target="_blank"><img src="<?php echo $imagePath ?>" style="width:50px; height:50px"></a>
                            <?php endif; ?>
                        </td>
                        <?php if (is_super_admin() && !in_array($s->status, array('DONE', 'REJECT', 'PENGAJUAN DIBATALKAN'), true)) : ?>
                            <td>
                                <div class="row">
                                    <a class="btn btn-success btn-sm mr-2" data-toggle="modal" data-target="#edititem<?= $d->id_det_po_nk ?>">
                                        <i class="fas fa-pencil-alt"></i>
                                        Edit
                                    </a>
                                    <a class="btn btn-danger btn-sm mr-2" data-toggle="modal" data-target="#hapusitem<?= $d->id_det_po_nk ?>">
                                        <i class="fas fa-trash-alt"></i>
                                        Hapus
                                    </a>
                                    <?php if ($canEditHargaNyata) : ?>
                                        <a class="btn btn-primary btn-sm mr-2" data-toggle="modal" data-target="#hrgnyata<?= $d->id_det_po_nk ?>">
                                            <i class="fas fa-plus"></i>
                                            Add Harganyata
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </td>
                        <?php elseif ($this->session->userdata('lv') == '4' && $s->status == 'PO REVISI') : ?>
                            <td>
                                <div class="row">
                                    <a class="btn btn-success btn-sm mr-2" data-toggle="modal" data-target="#edititem<?= $d->id_det_po_nk ?>">
                                        <i class="fas fa-pencil-alt"></i>
                                        Edit
                                    </a>
                                    <a class="btn btn-danger btn-sm mr-2" data-toggle="modal" data-target="#hapusitem<?= $d->id_det_po_nk ?>">
                                        <i class="fas fa-trash-alt"></i>
                                        Hapus
                                    </a>
                                </div>
                            </td>
                        <?php elseif ($this->session->userdata('lv') == '4' && $s->status == 'ON PROGRESS') : ?>
                            <td>
                                <div class="row">
                                    <a class="btn btn-success btn-sm mr-2" data-toggle="modal" data-target="#edititem<?= $d->id_det_po_nk ?>">
                                        <i class="fas fa-pencil-alt"></i>
                                        Edit
                                    </a>
                                    <a class="btn btn-danger btn-sm mr-2" data-toggle="modal" data-target="#hapusitem<?= $d->id_det_po_nk ?>">
                                        <i class="fas fa-trash-alt"></i>
                                        Hapus
                                    </a>
                                </div>
                            </td>

                        <?php elseif ($this->session->userdata('lv') == '2' && $s->status == 'ACC-KADEP') : ?>
                            <td>
                                <div class="row">
                                    <a class="btn btn-success btn-sm mr-2" data-toggle="modal" data-target="#edititem<?= $d->id_det_po_nk ?>">
                                        <i class="fas fa-pencil-alt"></i>
                                        Edit
                                    </a>
                                    <a class="btn btn-danger btn-sm mr-2" data-toggle="modal" data-target="#hapusitem<?= $d->id_det_po_nk ?>">
                                        <i class="fas fa-trash-alt"></i>
                                        Hapus
                                    </a>
                                    <?php if ($canEditHargaNyata) : ?>
                                        <a class="btn btn-primary btn-sm mr-2" data-toggle="modal" data-target="#hrgnyata<?= $d->id_det_po_nk ?>">
                                            <i class="fas fa-plus"></i>
                                            Add Harganyata
                                        </a>
                                    <?php else : ?>
                                    <?php endif; ?>
                                </div>
                            </td>
                        <?php elseif ($this->session->userdata('lv') == '4' || $this->session->userdata('lv') == '2' && $s->status == 'DONE') : ?>
                        <?php elseif ($this->session->userdata('lv') == '4' || $this->session->userdata('lv') == '2' && $s->status == 'REJECT') : ?>
                        <?php elseif ($this->session->userdata('lv') == '4' || $this->session->userdata('lv') == '2' && $s->status == 'SEDANG DIAJUKAN') : ?>

                        <?php endif; ?>
                    </tr>
                <?php endforeach; ?>
                <!-- VIEW PIC  -->
                <?php if ($this->session->userdata('lv') == '4' && $s->status == 'ACC-KADEP' || $s->status == 'PO REVISI') : ?>
                    <?php foreach ($total as $t) : ?>
                        <tr>
                            <td colspan="6" style="text-align: end; padding-right:3%; font-weight: bold;">Total Harga</td>
                            <td colspan="3" style="font-weight: bold;">Rp. <?= number_format($t->total_harga) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    <tr>
                        <td colspan="9" class="bg-black color-palette" style="text-align: center;">File Pendukung</td>
                    </tr>
                    <?php $this->load->view('content/postatus/_pic_supporting_rows', array('documents' => $picSupportingDocuments, 'colspan' => 9)); ?>
                    <?php foreach ($flupload as $f) :
                                    $imagePath = base_url('postatusnk/supporting-file/' . $f->id_file_nk);
                    ?>
                        <tr>
                            <td colspan="4" style="padding-right:3%; font-weight: bold;"><?= $f->keterangan ?></td>
                            <td colspan="4" style="padding-right:3%; font-weight: bold;">
                                <?php if ($f->kdfile == 'csv' || $f->kdfile == 'pdf') : ?>
                                    <a href="<?= $imagePath ?>" class="btn btn-secondary btn-sm btn-block" target="_blank">Download File</a>
                                <?php elseif (in_array($f->kdfile, array('png', 'jpg', 'jpeg', 'webp'), true)) : ?>
                                    <a href="<?= $imagePath ?>" class="btn btn-secondary btn-sm btn-block" data-toggle="lightbox" data-title="<?= $f->keterangan ?>">Buka File</a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <tr>
                        <td colspan="9" class="bg-black color-palette" style="text-align: center;">LIST DISKON</td>
                    </tr>
                    <?php foreach ($diskon as $d) : ?>
                        <?php if ($diskon > 0) : ?>
                            <tr>
                                <td colspan="6" style="text-align: end; padding-right:3%; font-weight: bold;"><?= $d->keterangan ?> : </td>
                                <td colspan="3" style="font-weight: bold;">
                                    Rp. <?= number_format($d->nominal, 2) ?>
                                    <?php if ($this->session->userdata('lv') < '5' && $s->status == 'DONE') : ?>
                                    <?php elseif ($this->session->userdata('lv') < '5' && $s->status == 'REJECT') : ?>
                                    <?php elseif ($this->session->userdata('lv') < '5' && $s->status != 'DONE') : ?>
                                        <a class="btn  btn-success btn-sm" data-toggle="modal" data-target="#modalDiskonEdit<?= $d->id_diskon ?>">
                                            <i class="fas fa-pencil-alt"></i>
                                        </a>
                                        <a class="btn btn-danger btn-sm" href="<?= base_url('hapusDiskonNk/') . $d->id_diskon . '/' . $d->kd_po ?>">
                                            <i class="fas fa-trash-alt"></i>
                                        </a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endif; ?>
                    <?php endforeach; ?>
                    <tr>
                        <td colspan="9" class="bg-black color-palette" style="text-align: center;">Note Pembelian</td>
                    </tr>
                    <?php foreach ($ntpembelian as $ntpm) : ?>
                        <tr>
                            <td colspan="9" style="padding-right:3%; font-weight: bold;"><?= $ntpm->keterangan ?></td>
                        </tr>
                    <?php endforeach; ?>
                    <tr>
                        <td colspan="9" class="bg-black color-palette" style="text-align: center;">TOTAL HARGA</td>
                    </tr>
                    <?php foreach ($total as $t) :
                                    foreach ($totalDiskon as $d) :
                                        $stlhDiskon = $t->total_harga - $d->total_diskon;
                                        $tax = $s->tax / 100;
                                        $hargaPajak = $stlhDiskon * $tax;
                                        $hargaAll = $stlhDiskon + $hargaPajak; ?>
                            <tr>
                                <td colspan="6" style="text-align: end; padding-right:3%; font-weight: bold;">Total Harga Setelah Diskon :</td>
                                <td colspan="3" style="font-weight: bold;"> Rp.<?= number_format($stlhDiskon, 2) ?> </td>
                            </tr>

                            <tr>
                                <td colspan="6" style="text-align: end; padding-right:3%; font-weight: bold;">Tax : <?= $s->tax ?>(%)</td>
                                <td colspan="3" style="font-weight: bold;"> Rp. <?= number_format($hargaPajak, 2) ?> </td>
                            </tr>

                            <tr>
                                <td colspan="6" style="text-align: end; padding-right:3%; font-weight: bold;">Grand Total Harga</td>
                                <td colspan="3" style="font-weight: bold;">Rp. <?= number_format($hargaAll, 2) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endforeach; ?>

                <?php elseif ($this->session->userdata('lv') == '4' && $s->status == 'SEDANG DIAJUKAN') : ?>
                    <?php foreach ($total as $t) : ?>
                        <tr>
                            <td colspan="6" style="text-align: end; padding-right:3%; font-weight: bold;">Total Harga</td>
                            <td colspan="3" style="font-weight: bold;">Rp. <?= number_format($t->total_harga) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    <tr>
                        <td colspan="9" class="bg-black color-palette" style="text-align: center;">File Pendukung</td>
                    </tr>
                    <?php $this->load->view('content/postatus/_pic_supporting_rows', array('documents' => $picSupportingDocuments, 'colspan' => 9)); ?>
                    <?php foreach ($flupload as $f) :
                                    $imagePath = base_url('postatusnk/supporting-file/' . $f->id_file_nk);
                    ?>
                        <tr>
                            <td colspan="4" style="padding-right:3%; font-weight: bold;"><?= $f->keterangan ?></td>
                            <td colspan="4" style="padding-right:3%; font-weight: bold;">
                                <?php if ($f->kdfile == 'csv' || $f->kdfile == 'pdf') : ?>
                                    <a href="<?= $imagePath ?>" class="btn btn-secondary btn-sm btn-block" target="_blank">Download File</a>
                                <?php elseif (in_array($f->kdfile, array('png', 'jpg', 'jpeg', 'webp'), true)) : ?>
                                    <a href="<?= $imagePath ?>" class="btn btn-secondary btn-sm btn-block" data-toggle="lightbox" data-title="<?= $f->keterangan ?>">Buka File</a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <tr>
                        <td colspan="9" class="bg-black color-palette" style="text-align: center;">LIST DISKON</td>
                    </tr>
                    <?php foreach ($diskon as $d) : ?>
                        <?php if ($diskon > 0) : ?>
                            <tr>
                                <td colspan="6" style="text-align: end; padding-right:3%; font-weight: bold;"><?= $d->keterangan ?> : </td>
                                <td colspan="3" style="font-weight: bold;">
                                    Rp. <?= number_format($d->nominal, 2) ?>
                                    <?php if ($this->session->userdata('lv') < '5' && $s->status == 'DONE') : ?>
                                    <?php elseif ($this->session->userdata('lv') < '5' && $s->status == 'REJECT') : ?>
                                    <?php elseif ($this->session->userdata('lv') < '5' && $s->status != 'DONE') : ?>
                                        <a class="btn  btn-success btn-sm" data-toggle="modal" data-target="#modalDiskonEdit<?= $d->id_diskon ?>">
                                            <i class="fas fa-pencil-alt"></i>
                                        </a>
                                        <a class="btn btn-danger btn-sm" href="<?= base_url('hapusDiskonNk/') . $d->id_diskon . '/' . $d->kd_po ?>">
                                            <i class="fas fa-trash-alt"></i>
                                        </a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endif; ?>
                    <?php endforeach; ?>
                    <tr>
                        <td colspan="9" class="bg-black color-palette" style="text-align: center;">Note Pembelian</td>
                    </tr>
                    <?php foreach ($ntpembelian as $ntpm) : ?>
                        <tr>
                            <td colspan="9" style="padding-right:3%; font-weight: bold;"><?= $ntpm->keterangan ?></td>
                        </tr>
                    <?php endforeach; ?>
                    <tr>
                        <td colspan="9" class="bg-black color-palette" style="text-align: center;">TOTAL HARGA</td>
                    </tr>
                    <?php foreach ($total as $t) :
                                    foreach ($totalDiskon as $d) :
                                        $stlhDiskon = $t->total_harga - $d->total_diskon;
                                        $tax = $s->tax / 100;
                                        $hargaPajak = $stlhDiskon * $tax;
                                        $hargaAll = $stlhDiskon + $hargaPajak; ?>
                            <tr>
                                <td colspan="6" style="text-align: end; padding-right:3%; font-weight: bold;">Total Harga Setelah Diskon :</td>
                                <td colspan="3" style="font-weight: bold;"> Rp.<?= number_format($stlhDiskon, 2) ?> </td>
                            </tr>

                            <tr>
                                <td colspan="6" style="text-align: end; padding-right:3%; font-weight: bold;">Tax : <?= $s->tax ?>(%)</td>
                                <td colspan="3" style="font-weight: bold;"> Rp. <?= number_format($hargaPajak, 2) ?> </td>
                            </tr>

                            <tr>
                                <td colspan="6" style="text-align: end; padding-right:3%; font-weight: bold;">Grand Total Harga</td>
                                <td colspan="3" style="font-weight: bold;">Rp. <?= number_format($hargaAll, 2) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endforeach; ?>

            </tbody>
        </table>
        <!-- AKSES LV 4 || PIC  -->
    <?php elseif ($this->session->userdata('lv') == '4' && $s->status == 'ON PROGRESS') : ?>
        <?php foreach ($total as $t) : ?>
            <tr>
                <td colspan="6" style="text-align: end; padding-right:3%; font-weight: bold;">Total Harga</td>
                <td colspan="4" style="font-weight: bold;">Rp. <?= number_format($t->total_harga) ?></td>
            </tr>
        <?php endforeach; ?>
        <tr>
            <td colspan="10" class="bg-black color-palette" style="text-align: center;">LIST DISKON</td>
        </tr>
        <?php foreach ($diskon as $d) : ?>
            <?php if ($diskon > 0) : ?>
                <tr>
                    <td colspan="6" style="text-align: end; padding-right:3%; font-weight: bold;"><?= $d->keterangan ?> : </td>
                    <td colspan="4" style="font-weight: bold;">
                        Rp. <?= number_format($d->nominal, 2) ?>
                        <?php if ($this->session->userdata('lv') < '5' && $s->status == 'DONE') : ?>
                        <?php elseif ($this->session->userdata('lv') < '5' && $s->status == 'REJECT') : ?>
                        <?php elseif ($this->session->userdata('lv') < '5' && $s->status != 'DONE') : ?>
                            <a class="btn  btn-success btn-sm" data-toggle="modal" data-target="#modalDiskonEdit<?= $d->id_diskon ?>">
                                <i class="fas fa-pencil-alt"></i>
                            </a>
                            <a class="btn btn-danger btn-sm" href="<?= base_url('hapusDiskonNk/') . $d->id_diskon . '/' . $d->kd_po ?>">
                                <i class="fas fa-trash-alt"></i>
                            </a>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endif; ?>
        <?php endforeach; ?>
        <tr>
            <td colspan="9" class="bg-black color-palette" style="text-align: center;">Note Pembelian</td>
        </tr>
        <?php foreach ($ntpembelian as $ntpm) : ?>
            <tr>
                <td colspan="6" style="padding-right:3%; font-weight: bold;"><?= $ntpm->keterangan ?></td>
                <td colspan="3" style="font-weight: bold;">
                    <a class="btn  btn-success btn-sm" data-toggle="modal" data-target="#edit_note_pembelian<?= $ntpm->id_nt_pembelian ?>">
                        <i class="fas fa-pencil-alt"></i>
                    </a>
                    <a class="btn btn-danger btn-sm" data-toggle="modal" data-target="#hapus_note_pembelian<?= $ntpm->id_nt_pembelian ?>">
                        <i class="fas fa-trash-alt"></i>
                    </a>
                </td>
            </tr>
        <?php endforeach; ?>
        <!-- UPLOAD FILE PENDUKUNG -->
        <tr>
            <td colspan="9" class="bg-black color-palette" style="text-align: center;">File Pendukung</td>
        </tr>
        <?php $this->load->view('content/postatus/_pic_supporting_rows', array('documents' => $picSupportingDocuments, 'colspan' => 9)); ?>
        <?php foreach ($flupload as $f) :
                                    $imagePath = base_url('postatusnk/supporting-file/' . $f->id_file_nk);
        ?>
            <tr>
                <td colspan="3" style="padding-right:3%; font-weight: bold;"><?= $f->keterangan ?></td>
                <td colspan="3" style="padding-right:3%; font-weight: bold;">
                    <?php if ($f->kdfile == 'csv' || $f->kdfile == 'pdf') : ?>
                        <a href="<?= $imagePath ?>" class="btn btn-secondary btn-sm btn-block" target="_blank">Download File</a>
                    <?php elseif (in_array($f->kdfile, array('png', 'jpg', 'jpeg', 'webp'), true)) : ?>
                        <a href="<?= $imagePath ?>" class="btn btn-secondary btn-sm btn-block" data-toggle="lightbox" data-title="<?= $f->keterangan ?>">Buka File</a>
                    <?php endif; ?>
                </td>
                <td colspan="3" style="font-weight: bold;">
                    <div class="row">
                        <div class="col">
                            <a class="btn  btn-success btn-sm btn-block" data-toggle="modal" data-target="#edit_gbr_pndukung<?= $f->id_file_nk ?>">
                                <i class="fas fa-pencil-alt"></i>
                            </a>
                        </div>
                        <div class="col">
                            <a class="btn btn-warning btn-sm btn-block" data-toggle="modal" data-target="#reuoploadimg_pndukung<?= $f->id_file_nk ?>">
                                <i class="fas fa-cloud-upload-alt"></i>
                            </a>
                        </div>
                        <div class="col">
                            <a class="btn btn-danger btn-sm btn-block" data-toggle="modal" data-target="#delete_gbr_pendukung<?= $f->id_file_nk ?>">
                                <i class="fas fa-trash-alt"></i>
                            </a>
                        </div>
                    </div>
                </td>
            </tr>
        <?php endforeach; ?>
        <tr>
            <td colspan="9" class="bg-black color-palette" style="text-align: center;">TOTAL HARGA</td>
        </tr>
        <?php foreach ($total as $t) :
                                    foreach ($totalDiskon as $d) :
                                        $stlhDiskon = $t->total_harga - $d->total_diskon;
                                        $tax = $s->tax / 100;
                                        $hargaPajak = $stlhDiskon * $tax;
                                        $hargaAll = $stlhDiskon + $hargaPajak; ?>
                <tr>
                    <td colspan="6" style="text-align: end; padding-right:3%; font-weight: bold;">Total Harga Setelah Diskon :</td>
                    <td colspan="4" style="font-weight: bold;"> Rp.<?= number_format($stlhDiskon, 2) ?> </td>
                </tr>

                <tr>
                    <td colspan="6" style="text-align: end; padding-right:3%; font-weight: bold;">Tax : <?= $s->tax ?>(%)</td>
                    <td colspan="4" style="font-weight: bold;"> Rp. <?= number_format($hargaPajak, 2) ?> </td>
                </tr>

                <tr>
                    <td colspan="6" style="text-align: end; padding-right:3%; font-weight: bold;">Grand Total Harga</td>
                    <td colspan="4" style="font-weight: bold;">Rp. <?= number_format($hargaAll, 2) ?></td>
                </tr>
            <?php endforeach; ?>
        <?php endforeach; ?>
        </tbody>
        </table>
    <?php elseif ($this->session->userdata('lv') == '4' && $s->status == 'ON PROGRESS - KADEP') : ?>
        <?php foreach ($total as $t) : ?>
            <tr>
                <td colspan="6" style="text-align: end; padding-right:3%; font-weight: bold;">Total Harga</td>
                <td colspan="4" style="font-weight: bold;">Rp. <?= number_format($t->total_harga) ?></td>
            </tr>
        <?php endforeach; ?>
        <tr>
            <td colspan="9" class="bg-black color-palette" style="text-align: center;">File Pendukung</td>
        </tr>
        <?php $this->load->view('content/postatus/_pic_supporting_rows', array('documents' => $picSupportingDocuments, 'colspan' => 9)); ?>
        <?php foreach ($flupload as $f) :
                                    $imagePath = base_url('postatusnk/supporting-file/' . $f->id_file_nk);
        ?>
            <tr>
                <td colspan="4" style="padding-right:3%; font-weight: bold;"><?= $f->keterangan ?></td>
                <td colspan="4" style="padding-right:3%; font-weight: bold;">
                    <?php if ($f->kdfile == 'csv' || $f->kdfile == 'pdf') : ?>
                        <a href="<?= $imagePath ?>" class="btn btn-secondary btn-sm btn-block" target="_blank">Download File</a>
                    <?php elseif (in_array($f->kdfile, array('png', 'jpg', 'jpeg', 'webp'), true)) : ?>
                        <a href="<?= $imagePath ?>" class="btn btn-secondary btn-sm btn-block" data-toggle="lightbox" data-title="<?= $f->keterangan ?>">Buka File</a>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
        <tr>
            <td colspan="10" class="bg-black color-palette" style="text-align: center;">LIST DISKON</td>
        </tr>
        <?php foreach ($diskon as $d) : ?>
            <?php if ($diskon > 0) : ?>
                <tr>
                    <td colspan="6" style="text-align: end; padding-right:3%; font-weight: bold;"><?= $d->keterangan ?> : </td>
                    <td colspan="4" style="font-weight: bold;">
                        Rp. <?= number_format($d->nominal, 2) ?>
                        <?php if ($this->session->userdata('lv') < '5' && $s->status == 'DONE') : ?>
                        <?php elseif ($this->session->userdata('lv') < '5' && $s->status == 'REJECT') : ?>
                        <?php elseif ($this->session->userdata('lv') < '5' && $s->status != 'DONE') : ?>
                            <a class="btn  btn-success btn-sm" data-toggle="modal" data-target="#modalDiskonEdit<?= $d->id_diskon ?>">
                                <i class="fas fa-pencil-alt"></i>
                            </a>
                            <a class="btn btn-danger btn-sm" href="<?= base_url('hapusDiskonNk/') . $d->id_diskon . '/' . $d->kd_po ?>">
                                <i class="fas fa-trash-alt"></i>
                            </a>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endif; ?>
        <?php endforeach; ?>
        <tr>
            <td colspan="9" class="bg-black color-palette" style="text-align: center;">Note Pembelian</td>
        </tr>
        <?php foreach ($ntpembelian as $ntpm) : ?>
            <tr>
                <td colspan="9" style="padding-right:3%; font-weight: bold;"><?= $ntpm->keterangan ?></td>
            </tr>
        <?php endforeach; ?>
        <tr>
            <td colspan="9" class="bg-black color-palette" style="text-align: center;">TOTAL HARGA</td>
        </tr>
        <?php foreach ($total as $t) :
                                    foreach ($totalDiskon as $d) :
                                        $stlhDiskon = $t->total_harga - $d->total_diskon;
                                        $tax = $s->tax / 100;
                                        $hargaPajak = $stlhDiskon * $tax;
                                        $hargaAll = $stlhDiskon + $hargaPajak; ?>
                <tr>
                    <td colspan="6" style="text-align: end; padding-right:3%; font-weight: bold;">Total Harga Setelah Diskon :</td>
                    <td colspan="4" style="font-weight: bold;"> Rp.<?= number_format($stlhDiskon, 2) ?> </td>
                </tr>

                <tr>
                    <td colspan="6" style="text-align: end; padding-right:3%; font-weight: bold;">Tax : <?= $s->tax ?>(%)</td>
                    <td colspan="4" style="font-weight: bold;"> Rp. <?= number_format($hargaPajak, 2) ?> </td>
                </tr>

                <tr>
                    <td colspan="6" style="text-align: end; padding-right:3%; font-weight: bold;">Grand Total Harga</td>
                    <td colspan="4" style="font-weight: bold;">Rp. <?= number_format($hargaAll, 2) ?></td>
                </tr>
            <?php endforeach; ?>
        <?php endforeach; ?>
        </tbody>
        </table>

    <?php elseif ($this->session->userdata('lv') == '2' && $s->status == 'ACC-KADEP') : ?>

        <!-- TOTAL HARGA || status_hrg_nyata 1 -->
        <?php if ($this->session->userdata('lv') == '2' && $s->status == 'ACC-KADEP' && $s->status_hrg_nyata == '1') : ?>
            <?php foreach ($total as $t) : ?>
                <tr>
                    <td colspan="8" style="text-align: end; padding-right:3%; font-weight: bold;">Total Harga</td>
                    <td colspan="3" style="font-weight: bold;">Rp. <?= number_format($t->total_harga) ?></td>
                </tr>
            <?php endforeach; ?>
            <?php foreach ($totalnyata as $tn) : ?>
                <tr>
                    <td colspan="8" style="text-align: end; padding-right:3%; font-weight: bold;">Total Harga Nyata</td>
                    <td colspan="3" style="font-weight: bold;">Rp. <?= number_format($tn->total_nyata) ?></td>
                </tr>
            <?php endforeach; ?>
            <tr>
                <td colspan="11" class="bg-black color-palette" style="text-align: center;">LIST DISKON</td>
            </tr>
            <?php foreach ($diskon as $d) : ?>
                <?php if ($diskon > 0) : ?>
                    <tr>
                        <td colspan="8" style="text-align: end; padding-right:3%; font-weight: bold;"><?= $d->keterangan ?> : </td>
                        <td colspan="3" style="font-weight: bold;">
                            Rp. <?= number_format($d->nominal, 2) ?>
                            <?php if ($this->session->userdata('lv') < '5' && $s->status == 'DONE') : ?>
                            <?php elseif ($this->session->userdata('lv') < '5' && $s->status == 'REJECT') : ?>
                            <?php elseif ($this->session->userdata('lv') < '5' && $s->status != 'DONE') : ?>
                                <a class="btn  btn-success btn-sm" data-toggle="modal" data-target="#modalDiskonEdit<?= $d->id_diskon ?>">
                                    <i class="fas fa-pencil-alt"></i>
                                </a>
                                <a class="btn btn-danger btn-sm" href="<?= base_url('hapusDiskonNk/') . $d->id_diskon . '/' . $d->kd_po ?>">
                                    <i class="fas fa-trash-alt"></i>
                                </a>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endif; ?>
            <?php endforeach; ?>
            <tr>
                <td colspan="11" class="bg-black color-palette" style="text-align: center;">Note Pembelian</td>
            </tr>
            <?php foreach ($ntpembelian as $ntpm) : ?>
                <tr>
                    <td colspan="8" style="padding-right:3%; font-weight: bold;"><?= $ntpm->keterangan ?></td>
                    <td colspan="3" style="font-weight: bold;">
                        <a class="btn  btn-success btn-sm" data-toggle="modal" data-target="#edit_note_pembelian<?= $ntpm->id_nt_pembelian ?>">
                            <i class="fas fa-pencil-alt"></i>
                        </a>
                        <a class="btn btn-danger btn-sm" data-toggle="modal" data-target="#hapus_note_pembelian<?= $ntpm->id_nt_pembelian ?>">
                            <i class="fas fa-trash-alt"></i>
                        </a>
                    </td>
                </tr>
            <?php endforeach; ?>
            <tr>
                <td colspan="11" class="bg-black color-palette" style="text-align: center;">File Pendukung</td>
            </tr>
            <?php $this->load->view('content/postatus/_pic_supporting_rows', array('documents' => $picSupportingDocuments, 'colspan' => 11)); ?>
            <?php foreach ($flupload as $f) : ?>
                <tr>
                    <td colspan="8" style="padding-right:3%; font-weight: bold;"><?= $f->keterangan ?></td>
                    <td colspan="3" style="font-weight: bold;">

                        <a class="btn  btn-success btn-sm" data-toggle="modal" data-target="#edit_note_pembelian<?= $f->id_file_nk ?>">
                            <i class="fas fa-pencil-alt"></i>
                        </a>
                        <a class="btn btn-danger btn-sm" data-toggle="modal" data-target="#hapus_note_pembelian<?= $f->id_file_nk ?>">
                            <i class="fas fa-trash-alt"></i>
                        </a>
                    </td>
                </tr>
            <?php endforeach; ?>
            <tr>
                <td colspan="11" class="bg-black color-palette" style="text-align: center;">TOTAL HARGA</td>
            </tr>
            <?php foreach ($totalnyata as $t) :
                                        foreach ($totalDiskon as $d) :
                                            $stlhDiskon = $t->total_nyata - $d->total_diskon;
                                            $tax = $s->tax / 100;
                                            $hargaPajak = $stlhDiskon * $tax;
                                            $hargaAll = $stlhDiskon + $hargaPajak; ?>
                    <tr>
                        <td colspan="8" style="text-align: end; padding-right:3%; font-weight: bold;">Total Harga Setelah Diskon :</td>
                        <td colspan="3" style="font-weight: bold;"> Rp.<?= number_format($stlhDiskon, 2) ?> </td>
                    </tr>

                    <tr>
                        <td colspan="8" style="text-align: end; padding-right:3%; font-weight: bold;">Tax : <?= $s->tax ?>(%)</td>
                        <td colspan="3" style="font-weight: bold;"> Rp. <?= number_format($hargaPajak, 2) ?> </td>
                    </tr>

                    <tr>
                        <td colspan="8" style="text-align: end; padding-right:3%; font-weight: bold;">Grand Total Harga</td>
                        <td colspan="3" style="font-weight: bold;">Rp. <?= number_format($hargaAll, 2) ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endforeach; ?>

        <?php elseif ($this->session->userdata('lv') == '2' && $s->status == 'SEDANG DIAJUKAN' && $s->status_hrg_nyata == '1') : ?>
            <?php foreach ($total as $t) : ?>
                <tr>
                    <td colspan="6" style="text-align: end; padding-right:3%; font-weight: bold;">Total Harga</td>
                    <td colspan="1" style="font-weight: bold;">Rp. <?= number_format($t->total_harga) ?></td>
                </tr>
            <?php endforeach; ?>
            <tr>
                <td colspan="7" class="bg-black color-palette" style="text-align: center;">LIST DISKON</td>
            </tr>
            <?php foreach ($diskon as $d) : ?>
                <?php if ($diskon > 0) : ?>
                    <tr>
                        <td colspan="6" style="text-align: end; padding-right:3%; font-weight: bold;"><?= $d->keterangan ?> : </td>
                        <td colspan="1" style="font-weight: bold;">
                            Rp. <?= number_format($d->nominal, 2) ?>
                        </td>
                    </tr>
                <?php endif; ?>
            <?php endforeach; ?>
            <tr>
                <td colspan="7" class="bg-black color-palette" style="text-align: center;">Note Pembelian</td>
            </tr>
            <?php foreach ($ntpembelian as $ntpm) : ?>
                <tr>
                    <td colspan="7" style="padding-right:3%; font-weight: bold;"><?= $ntpm->keterangan ?></td>
                </tr>
            <?php endforeach; ?>
            <tr>
                <td colspan="7" class="bg-black color-palette" style="text-align: center;">TOTAL HARGA</td>
            </tr>
            <?php foreach ($total as $t) :
                                        foreach ($totalDiskon as $d) :
                                            $stlhDiskon = $t->total_harga - $d->total_diskon;
                                            $tax = $s->tax / 100;
                                            $hargaPajak = $stlhDiskon * $tax;
                                            $hargaAll = $stlhDiskon + $hargaPajak; ?>
                    <tr>
                        <td colspan="6" style="text-align: end; padding-right:3%; font-weight: bold;">Total Harga Setelah Diskon :</td>
                        <td colspan="1" style="font-weight: bold;"> Rp.<?= number_format($stlhDiskon, 2) ?> </td>
                    </tr>

                    <tr>
                        <td colspan="6" style="text-align: end; padding-right:3%; font-weight: bold;">Tax : <?= $s->tax ?>(%)</td>
                        <td colspan="1" style="font-weight: bold;"> Rp. <?= number_format($hargaPajak, 2) ?> </td>
                    </tr>

                    <tr>
                        <td colspan="6" style="text-align: end; padding-right:3%; font-weight: bold;">Grand Total Harga</td>
                        <td colspan="1" style="font-weight: bold;">Rp. <?= number_format($hargaAll, 2) ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endforeach; ?>
            </tbody>
            </table>



            <!-- TOTAL HARGA || status_hrg_nyata 0 -->
        <?php elseif ($this->session->userdata('lv') == '2' && $s->status == 'ACC-KADEP' && $s->status_hrg_nyata == '0') : ?>
            <?php foreach ($total as $t) : ?>
                <tr>
                    <td colspan="6" style="text-align: end; padding-right:3%; font-weight: bold;">Total Harga</td>
                    <td colspan="3" style="font-weight: bold;">Rp. <?= number_format($t->total_harga) ?></td>
                </tr>
            <?php endforeach; ?>
            <tr>
                <td colspan="9" class="bg-black color-palette" style="text-align: center;">LIST DISKON</td>
            </tr>
            <?php foreach ($diskon as $d) : ?>
                <?php if ($diskon > 0) : ?>
                    <tr>
                        <td colspan="6" style="text-align: end; padding-right:3%; font-weight: bold;"><?= $d->keterangan ?> : </td>
                        <td colspan="3" style="font-weight: bold;">
                            Rp. <?= number_format($d->nominal, 2) ?>
                            <?php if ($this->session->userdata('lv') < '5' && $s->status == 'DONE') : ?>
                            <?php elseif ($this->session->userdata('lv') < '5' && $s->status == 'REJECT') : ?>
                            <?php elseif ($this->session->userdata('lv') < '5' && $s->status != 'DONE') : ?>
                                <a class="btn  btn-success btn-sm" data-toggle="modal" data-target="#modalDiskonEdit<?= $d->id_diskon ?>">
                                    <i class="fas fa-pencil-alt"></i>
                                </a>
                                <a class="btn btn-danger btn-sm" href="<?= base_url('hapusDiskonNk/') . $d->id_diskon . '/' . $d->kd_po ?>">
                                    <i class="fas fa-trash-alt"></i>
                                </a>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endif; ?>
            <?php endforeach; ?>
            <tr>
                <td colspan="9" class="bg-black color-palette" style="text-align: center;">Note Pembelian</td>
            </tr>
            <?php foreach ($ntpembelian as $ntpm) : ?>
                <tr>
                    <td colspan="6" style="padding-right:3%; font-weight: bold;"><?= $ntpm->keterangan ?></td>
                    <td colspan="3" style="font-weight: bold;">
                        <a class="btn  btn-success btn-sm" data-toggle="modal" data-target="#edit_note_pembelian<?= $ntpm->id_nt_pembelian ?>">
                            <i class="fas fa-pencil-alt"></i>
                        </a>
                        <a class="btn btn-danger btn-sm" data-toggle="modal" data-target="#hapus_note_pembelian<?= $ntpm->id_nt_pembelian ?>">
                            <i class="fas fa-trash-alt"></i>
                        </a>
                    </td>
                </tr>
            <?php endforeach; ?>
            <tr>
                <td colspan="9" class="bg-black color-palette" style="text-align: center;">File Pendukung</td>
            </tr>
            <?php $this->load->view('content/postatus/_pic_supporting_rows', array('documents' => $picSupportingDocuments, 'colspan' => 9)); ?>
            <?php foreach ($flupload as $f) :
                                        $imagePath = base_url('postatusnk/supporting-file/' . $f->id_file_nk);
            ?>
                <tr>
                    <td colspan="3" style="padding-right:3%; font-weight: bold;"><?= $f->keterangan ?></td>
                    <td colspan="3" style="padding-right:3%; font-weight: bold;">
                        <?php if ($f->kdfile == 'csv' || $f->kdfile == 'pdf') : ?>
                            <a href="<?= $imagePath ?>" class="btn btn-secondary btn-sm btn-block" target="_blank">Download File</a>
                        <?php elseif (in_array($f->kdfile, array('png', 'jpg', 'jpeg', 'webp'), true)) : ?>
                            <a href="<?= $imagePath ?>" class="btn btn-secondary btn-sm btn-block" data-toggle="lightbox" data-title="<?= $f->keterangan ?>">Buka File</a>
                        <?php endif; ?>
                    </td>
                    <td colspan="3" style="font-weight: bold;">
                        <div class="row">
                            <div class="col">
                                <a class="btn  btn-success btn-sm btn-block" data-toggle="modal" data-target="#edit_gbr_pndukung<?= $f->id_file_nk ?>">
                                    <i class="fas fa-pencil-alt"></i>
                                </a>
                            </div>
                            <div class="col">
                                <a class="btn btn-danger btn-sm btn-block" data-toggle="modal" data-target="#delete_gbr_pendukung<?= $f->id_file_nk ?>">
                                    <i class="fas fa-trash-alt"></i>
                                </a>
                            </div>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
            <tr>
                <td colspan="9" class="bg-black color-palette" style="text-align: center;">TOTAL HARGA</td>
            </tr>
            <?php foreach ($total as $t) :
                                        foreach ($totalDiskon as $d) :
                                            $stlhDiskon = $t->total_harga - $d->total_diskon;
                                            $tax = $s->tax / 100;
                                            $hargaPajak = $stlhDiskon * $tax;
                                            $hargaAll = $stlhDiskon + $hargaPajak; ?>
                    <tr>
                        <td colspan="6" style="text-align: end; padding-right:3%; font-weight: bold;">Total Harga Setelah Diskon :</td>
                        <td colspan="3" style="font-weight: bold;"> Rp.<?= number_format($stlhDiskon, 2) ?> </td>
                    </tr>

                    <tr>
                        <td colspan="6" style="text-align: end; padding-right:3%; font-weight: bold;">Tax : <?= $s->tax ?>(%)</td>
                        <td colspan="3" style="font-weight: bold;"> Rp. <?= number_format($hargaPajak, 2) ?> </td>
                    </tr>

                    <tr>
                        <td colspan="6" style="text-align: end; padding-right:3%; font-weight: bold;">Grand Total Harga</td>
                        <td colspan="3" style="font-weight: bold;">Rp. <?= number_format($hargaAll, 2) ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endforeach; ?>
        <?php endif; ?>
        </tbody>
        </table>
        <!-- FILE PENDUKUNG || VIEW ADMIN || SEDANG DIAJUKAN -->
    <?php elseif ($this->session->userdata('lv') == '2' && $s->status == 'SEDANG DIAJUKAN') : ?>
        <tr>
            <td colspan="9" class="bg-black color-palette" style="text-align: center;">File Pendukung</td>
        </tr>
        <?php $this->load->view('content/postatus/_pic_supporting_rows', array('documents' => $picSupportingDocuments, 'colspan' => 9)); ?>
        <?php foreach ($flupload as $f) :
                                    $imagePath = base_url('postatusnk/supporting-file/' . $f->id_file_nk);
        ?>
            <tr>
                <td colspan="3" style="padding-right:3%; font-weight: bold;"><?= $f->keterangan ?></td>
                <td colspan="6" style="padding-right:3%; font-weight: bold;">
                    <?php if ($f->kdfile == 'csv' || $f->kdfile == 'pdf') : ?>
                        <a href="<?= $imagePath ?>" class="btn btn-secondary btn-sm btn-block" target="_blank">Download File</a>
                    <?php elseif (in_array($f->kdfile, array('png', 'jpg', 'jpeg', 'webp'), true)) : ?>
                        <a href="<?= $imagePath ?>" class="btn btn-secondary btn-sm btn-block" data-toggle="lightbox" data-title="<?= $f->keterangan ?>">Buka File</a>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>

        <!-- PROSES PEMBELIAN || LV 2 ADMIN -->
    <?php elseif ($this->session->userdata('lv') == '2' && $s->status == 'PROSES PEMBELIAN') : ?>
        <tr>
            <td colspan="10" class="bg-black color-palette" style="text-align: center;">Bukti Pembelian</td>
        </tr>
        <?php foreach ($fluploadbukti as $fb) :
                                    $imagePathb = base_url('postatusnk/purchase-proof/' . $fb->id_fk_bukti);
        ?>
            <tr>
                <td colspan="5" style="padding-right:3%; font-weight: bold;"><?= $fb->keterangan ?></td>
                <td colspan="5" style="padding-right:3%; font-weight: bold;">
                    <?php if ($fb->kdfile == 'csv' || $fb->kdfile == 'pdf') : ?>
                        <a href="<?= $imagePathb ?>" class="btn btn-secondary btn-sm btn-block" target="_blank">Buka File</a>
                    <?php elseif (in_array($fb->kdfile, array('png', 'jpg', 'jpeg', 'webp'), true)) : ?>
                        <a href="<?= $imagePathb ?>" class="btn btn-secondary btn-sm btn-block" data-toggle="lightbox" data-title="<?= $fb->keterangan ?>">Buka File</a>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>

        <!-- ACC DIREKTUR || LV 2 ADMIN -->
    <?php elseif ($this->session->userdata('lv') == '2' && $s->status == 'ACC DIREKTUR') : ?>
        <?php foreach ($total as $t) : ?>
            <tr>
                <td colspan="8" style="text-align: end; padding-right:3%; font-weight: bold;">Total Harga</td>
                <td colspan="2" style="font-weight: bold;">Rp. <?= number_format($t->total_harga) ?></td>
            </tr>
        <?php endforeach; ?>
        <?php foreach ($totalnyata as $tn) : ?>
            <tr>
                <td colspan="8" style="text-align: end; padding-right:3%; font-weight: bold;">Total Harga Nyata</td>
                <td colspan="2" style="font-weight: bold;">Rp. <?= number_format($tn->total_nyata) ?></td>
            </tr>
        <?php endforeach; ?>
        <!-- BUKTI PEMBELIAN -->
        <tr>
            <td colspan="10" class="bg-black color-palette" style="text-align: center;">Bukti Pembelian</td>
        </tr>
        <?php foreach ($fluploadbukti as $fb) :
                                    $imagePathb = base_url('postatusnk/purchase-proof/' . $fb->id_fk_bukti);
        ?>
            <tr>
                <td colspan="5" style="padding-right:3%; font-weight: bold;"><?= $fb->keterangan ?></td>
                <td colspan="5" style="padding-right:3%; font-weight: bold;">
                    <?php if ($fb->kdfile == 'csv' || $fb->kdfile == 'pdf') : ?>
                        <a href="<?= $imagePathb ?>" class="btn btn-secondary btn-sm btn-block" target="_blank">Buka File</a>
                    <?php elseif (in_array($fb->kdfile, array('png', 'jpg', 'jpeg', 'webp'), true)) : ?>
                        <a href="<?= $imagePathb ?>" class="btn btn-secondary btn-sm btn-block" data-toggle="lightbox" data-title="<?= $fb->keterangan ?>">Buka File</a>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
        <tr>
            <td colspan="10" class="bg-black color-palette" style="text-align: center;">Note Pembelian</td>
        </tr>
        <?php foreach ($ntpembelian as $ntpm) : ?>
            <tr>
                <td colspan="10" style="padding-right:3%; font-weight: bold;"><?= $ntpm->keterangan ?></td>
            </tr>
        <?php endforeach; ?>
        <tr>
            <td colspan="10" class="bg-black color-palette" style="text-align: center;">TOTAL HARGA</td>
        </tr>
        <?php foreach ($total as $t) :
                                    foreach ($totalDiskon as $d) :
                                        $stlhDiskon = $t->total_harga - $d->total_diskon;
                                        $tax = $s->tax / 100;
                                        $hargaPajak = $stlhDiskon * $tax;
                                        $hargaAll = $stlhDiskon + $hargaPajak; ?>
                <tr>
                    <td colspan="8" style="text-align: end; padding-right:3%; font-weight: bold;">Total Harga Setelah Diskon :</td>
                    <td colspan="2" style="font-weight: bold;"> Rp.<?= number_format($stlhDiskon, 2) ?> </td>
                </tr>

                <tr>
                    <td colspan="8" style="text-align: end; padding-right:3%; font-weight: bold;">Tax : <?= $s->tax ?>(%)</td>
                    <td colspan="2" style="font-weight: bold;"> Rp. <?= number_format($hargaPajak, 2) ?> </td>
                </tr>

                <tr>
                    <td colspan="8" style="text-align: end; padding-right:3%; font-weight: bold;">Grand Total Harga</td>
                    <td colspan="2" style="font-weight: bold;">Rp. <?= number_format($hargaAll, 2) ?></td>
                </tr>
            <?php endforeach; ?>
        <?php endforeach; ?>
        </tbody>
        </table>

        <!-- PROSES PEMBELIAN || LV 4 USER -->
    <?php elseif ($this->session->userdata('lv') == '4' && $s->status == 'PROSES PEMBELIAN') : ?>
        <tr>
            <td colspan="10" class="bg-black color-palette" style="text-align: center;">Bukti Pembelian</td>
        </tr>
        <?php foreach ($fluploadbukti as $fb) :
                                    $imagePathb = base_url('postatusnk/purchase-proof/' . $fb->id_fk_bukti);
        ?>
            <tr>
                <td colspan="5" style="padding-right:3%; font-weight: bold;"><?= $fb->keterangan ?></td>
                <td colspan="5" style="padding-right:3%; font-weight: bold;">
                    <?php if ($fb->kdfile == 'csv' || $fb->kdfile == 'pdf') : ?>
                        <a href="<?= $imagePathb ?>" class="btn btn-secondary btn-sm btn-block" target="_blank">Buka File</a>
                    <?php elseif (in_array($fb->kdfile, array('png', 'jpg', 'jpeg', 'webp'), true)) : ?>
                        <a href="<?= $imagePathb ?>" class="btn btn-secondary btn-sm btn-block" data-toggle="lightbox" data-title="<?= $fb->keterangan ?>">Buka File</a>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>

        <!-- ACC DIREKTUR || LV 4 USER -->

    <?php elseif ($this->session->userdata('lv') == '4' && $s->status == 'ACC DIREKTUR') : ?>
        <?php foreach ($total as $t) : ?>
            <tr>
                <td colspan="6" style="text-align: end; padding-right:3%; font-weight: bold;">Total Harga</td>
                <td colspan="2" style="font-weight: bold;">Rp. <?= number_format($t->total_harga) ?></td>
            </tr>
        <?php endforeach; ?>
        <!-- BUKTI PEMBELIAN -->
        <tr>
            <td colspan="8" class="bg-black color-palette" style="text-align: center;">Bukti Pembelian</td>
        </tr>
        <?php foreach ($fluploadbukti as $fb) :
                                    $imagePathb = base_url('postatusnk/purchase-proof/' . $fb->id_fk_bukti);
        ?>
            <tr>
                <td colspan="4" style="padding-right:3%; font-weight: bold;"><?= $fb->keterangan ?></td>
                <td colspan="4" style="padding-right:3%; font-weight: bold;">
                    <?php if ($fb->kdfile == 'csv' || $fb->kdfile == 'pdf') : ?>
                        <a href="<?= $imagePathb ?>" class="btn btn-secondary btn-sm btn-block" target="_blank">Buka File</a>
                    <?php elseif (in_array($fb->kdfile, array('png', 'jpg', 'jpeg', 'webp'), true)) : ?>
                        <a href="<?= $imagePathb ?>" class="btn btn-secondary btn-sm btn-block" data-toggle="lightbox" data-title="<?= $fb->keterangan ?>">Buka File</a>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
        <tr>
            <td colspan="8" class="bg-black color-palette" style="text-align: center;">Note Pembelian</td>
        </tr>
        <?php foreach ($ntpembelian as $ntpm) : ?>
            <tr>
                <td colspan="8" style="padding-right:3%; font-weight: bold;"><?= $ntpm->keterangan ?></td>
            </tr>
        <?php endforeach; ?>
        <tr>
            <td colspan="8" class="bg-black color-palette" style="text-align: center;">TOTAL HARGA</td>
        </tr>
        <?php foreach ($total as $t) :
                                    foreach ($totalDiskon as $d) :
                                        $stlhDiskon = $t->total_harga - $d->total_diskon;
                                        $tax = $s->tax / 100;
                                        $hargaPajak = $stlhDiskon * $tax;
                                        $hargaAll = $stlhDiskon + $hargaPajak; ?>
                <tr>
                    <td colspan="6" style="text-align: end; padding-right:3%; font-weight: bold;">Total Harga Setelah Diskon :</td>
                    <td colspan="2" style="font-weight: bold;"> Rp.<?= number_format($stlhDiskon, 2) ?> </td>
                </tr>

                <tr>
                    <td colspan="6" style="text-align: end; padding-right:3%; font-weight: bold;">Tax : <?= $s->tax ?>(%)</td>
                    <td colspan="2" style="font-weight: bold;"> Rp. <?= number_format($hargaPajak, 2) ?> </td>
                </tr>

                <tr>
                    <td colspan="6" style="text-align: end; padding-right:3%; font-weight: bold;">Grand Total Harga</td>
                    <td colspan="2" style="font-weight: bold;">Rp. <?= number_format($hargaAll, 2) ?></td>
                </tr>
            <?php endforeach; ?>
        <?php endforeach; ?>
        </tbody>
        </table>

        <!-- ACC DIREKTUR || LV 5 KADEP -->

    <?php elseif ($this->session->userdata('lv') == '2' && $s->status == 'SEDANG DIAJUKAN' && $s->status_hrg_nyata == '1') : ?>
        <?php foreach ($total as $t) : ?>
            <tr>
                <td colspan="8" style="text-align: end; padding-right:3%; font-weight: bold;">Total Harga</td>
                <td colspan="2" style="font-weight: bold;">Rp. <?= number_format($t->total_harga) ?></td>
            </tr>
        <?php endforeach; ?>
        <?php foreach ($totalnyata as $tn) : ?>
            <tr>
                <td colspan="8" style="text-align: end; padding-right:3%; font-weight: bold;">Total Harga Nyata</td>
                <td colspan="2" style="font-weight: bold;">Rp. <?= number_format($tn->total_nyata) ?></td>
            </tr>
        <?php endforeach; ?>
        <tr>
            <td colspan="10" class="bg-black color-palette" style="text-align: center;">LIST DISKON</td>
        </tr>
        <?php foreach ($diskon as $d) : ?>
            <?php if ($diskon > 0) : ?>
                <tr>
                    <td colspan="8" style="text-align: end; padding-right:3%; font-weight: bold;"><?= $d->keterangan ?> : </td>
                    <td colspan="2" style="font-weight: bold;">
                        Rp. <?= number_format($d->nominal, 2) ?>
                    </td>
                </tr>
            <?php endif; ?>
        <?php endforeach; ?>
        <tr>
            <td colspan="10" class="bg-black color-palette" style="text-align: center;">Note Pembelian</td>
        </tr>
        <?php foreach ($ntpembelian as $ntpm) : ?>
            <tr>
                <td colspan="10" style="padding-right:3%; font-weight: bold;"><?= $ntpm->keterangan ?></td>
            </tr>
        <?php endforeach; ?>
        <tr>
            <td colspan="10" class="bg-black color-palette" style="text-align: center;">TOTAL HARGA</td>
        </tr>
        <?php foreach ($total as $t) :
                                    foreach ($totalDiskon as $d) :
                                        $stlhDiskon = $t->total_harga - $d->total_diskon;
                                        $tax = $s->tax / 100;
                                        $hargaPajak = $stlhDiskon * $tax;
                                        $hargaAll = $stlhDiskon + $hargaPajak; ?>
                <tr>
                    <td colspan="8" style="text-align: end; padding-right:3%; font-weight: bold;">Total Harga Setelah Diskon :</td>
                    <td colspan="2" style="font-weight: bold;"> Rp.<?= number_format($stlhDiskon, 2) ?> </td>
                </tr>

                <tr>
                    <td colspan="8" style="text-align: end; padding-right:3%; font-weight: bold;">Tax : <?= $s->tax ?>(%)</td>
                    <td colspan="2" style="font-weight: bold;"> Rp. <?= number_format($hargaPajak, 2) ?> </td>
                </tr>

                <tr>
                    <td colspan="8" style="text-align: end; padding-right:3%; font-weight: bold;">Grand Total Harga</td>
                    <td colspan="2" style="font-weight: bold;">Rp. <?= number_format($hargaAll, 2) ?></td>
                </tr>

            <?php endforeach; ?>
        <?php endforeach; ?>
        </tbody>
        </table>

        <!-- DIREKTUR -->
    <?php elseif ($this->session->userdata('lv') == '3') : ?>
        <?php foreach ($total as $t) : ?>
            <tr>
                <td colspan="7" style="text-align: end; padding-right:3%; font-weight: bold;">Total Harga</td>
                <td colspan="1" style="font-weight: bold;">Rp. <?= number_format($t->total_harga) ?></td>
            </tr>
        <?php endforeach; ?>
        <tr>
            <td colspan="9" class="bg-black color-palette" style="text-align: center;">File Pendukung</td>
        </tr>
        <?php $this->load->view('content/postatus/_pic_supporting_rows', array('documents' => $picSupportingDocuments, 'colspan' => 9)); ?>
        <?php foreach ($flupload as $f) :
                                    $imagePath = base_url('postatusnk/supporting-file/' . $f->id_file_nk);
        ?>
            <tr>
                <td colspan="4" style="padding-right:3%; font-weight: bold;"><?= $f->keterangan ?></td>
                <td colspan="4" style="padding-right:3%; font-weight: bold;">
                    <?php if ($f->kdfile == 'csv' || $f->kdfile == 'pdf') : ?>
                        <a href="<?= $imagePath ?>" class="btn btn-secondary btn-sm btn-block" target="_blank">Download File</a>
                    <?php elseif (in_array($f->kdfile, array('png', 'jpg', 'jpeg', 'webp'), true)) : ?>
                        <a href="<?= $imagePath ?>" class="btn btn-secondary btn-sm btn-block" data-toggle="lightbox" data-title="<?= $f->keterangan ?>">Buka File</a>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
        <tr>
            <td colspan="8" class="bg-black color-palette" style="text-align: center;">LIST DISKON</td>
        </tr>
        <?php foreach ($diskon as $d) : ?>
            <?php if ($diskon > 0) : ?>
                <tr>
                    <td colspan="7" style="text-align: end; padding-right:3%; font-weight: bold;"><?= $d->keterangan ?> : </td>
                    <td colspan="1" style="font-weight: bold;">
                        Rp. <?= number_format($d->nominal, 2) ?>
                    </td>
                </tr>
            <?php endif; ?>
        <?php endforeach; ?>
        <tr>
            <td colspan="8" class="bg-black color-palette" style="text-align: center;">Note Pembelian</td>
        </tr>
        <?php foreach ($ntpembelian as $ntpm) : ?>
            <tr>
                <td colspan="8" style="padding-right:3%; font-weight: bold;"><?= $ntpm->keterangan ?></td>
            </tr>
        <?php endforeach; ?>
        <tr>
            <td colspan="8" class="bg-black color-palette" style="text-align: center;">TOTAL HARGA</td>
        </tr>
        <?php foreach ($total as $t) :
                                    foreach ($totalDiskon as $d) :
                                        $stlhDiskon = $t->total_harga - $d->total_diskon;
                                        $tax = $s->tax / 100;
                                        $hargaPajak = $stlhDiskon * $tax;
                                        $hargaAll = $stlhDiskon + $hargaPajak; ?>
                <tr>
                    <td colspan="7" style="text-align: end; padding-right:3%; font-weight: bold;">Total Harga Setelah Diskon :</td>
                    <td colspan="1" style="font-weight: bold;"> Rp.<?= number_format($stlhDiskon, 2) ?> </td>
                </tr>

                <tr>
                    <td colspan="7" style="text-align: end; padding-right:3%; font-weight: bold;">Tax : <?= $s->tax ?>(%)</td>
                    <td colspan="1" style="font-weight: bold;"> Rp. <?= number_format($hargaPajak, 2) ?> </td>
                </tr>

                <tr>
                    <td colspan="7" style="text-align: end; padding-right:3%; font-weight: bold;">Grand Total Harga</td>
                    <td colspan="1" style="font-weight: bold;">Rp. <?= number_format($hargaAll, 2) ?></td>
                </tr>
            <?php endforeach; ?>
        <?php endforeach; ?>
        </tbody>
        </table>
        <!-- KADEP -->
    <?php elseif ($this->session->userdata('lv') == '5') : ?>
        <?php foreach ($total as $t) : ?>
            <tr>
                <td colspan="6" style="text-align: end; padding-right:3%; font-weight: bold;">Total Harga</td>
                <td colspan="2" style="font-weight: bold;">Rp. <?= number_format($t->total_harga) ?></td>
            </tr>
        <?php endforeach; ?>
        <tr>
            <td colspan="9" class="bg-black color-palette" style="text-align: center;">File Pendukung</td>
        </tr>
        <?php $this->load->view('content/postatus/_pic_supporting_rows', array('documents' => $picSupportingDocuments, 'colspan' => 9)); ?>
        <?php foreach ($flupload as $f) :
                                    $imagePath = base_url('postatusnk/supporting-file/' . $f->id_file_nk);
        ?>
            <tr>
                <td colspan="4" style="padding-right:3%; font-weight: bold;"><?= $f->keterangan ?></td>
                <td colspan="4" style="padding-right:3%; font-weight: bold;">
                    <?php if ($f->kdfile == 'csv' || $f->kdfile == 'pdf') : ?>
                        <a href="<?= $imagePath ?>" class="btn btn-secondary btn-sm btn-block" target="_blank">Download File</a>
                    <?php elseif (in_array($f->kdfile, array('png', 'jpg', 'jpeg', 'webp'), true)) : ?>
                        <a href="<?= $imagePath ?>" class="btn btn-secondary btn-sm btn-block" data-toggle="lightbox" data-title="<?= $f->keterangan ?>">Buka File</a>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
        <tr>
            <td colspan="8" class="bg-black color-palette" style="text-align: center;">LIST DISKON</td>
        </tr>
        <?php foreach ($diskon as $d) : ?>
            <?php if ($diskon > 0) : ?>
                <tr>
                    <td colspan="7" style="text-align: end; padding-right:3%; font-weight: bold;"><?= $d->keterangan ?> : </td>
                    <td colspan="1" style="font-weight: bold;">
                        Rp. <?= number_format($d->nominal, 2) ?>
                    </td>
                </tr>
            <?php endif; ?>
        <?php endforeach; ?>
        <tr>
            <td colspan="8" class="bg-black color-palette" style="text-align: center;">Note Pembelian</td>
        </tr>
        <?php foreach ($ntpembelian as $ntpm) : ?>
            <tr>
                <td colspan="8" style="padding-right:3%; font-weight: bold;"><?= $ntpm->keterangan ?></td>
            </tr>
        <?php endforeach; ?>
        <tr>
            <td colspan="8" class="bg-black color-palette" style="text-align: center;">TOTAL HARGA</td>
        </tr>
        <?php foreach ($total as $t) :
                                    foreach ($totalDiskon as $d) :
                                        $stlhDiskon = $t->total_harga - $d->total_diskon;
                                        $tax = $s->tax / 100;
                                        $hargaPajak = $stlhDiskon * $tax;
                                        $hargaAll = $stlhDiskon + $hargaPajak; ?>
                <tr>
                    <td colspan="7" style="text-align: end; padding-right:3%; font-weight: bold;">Total Harga Setelah Diskon :</td>
                    <td colspan="1" style="font-weight: bold;"> Rp.<?= number_format($stlhDiskon, 2) ?> </td>
                </tr>

                <tr>
                    <td colspan="7" style="text-align: end; padding-right:3%; font-weight: bold;">Tax : <?= $s->tax ?>(%)</td>
                    <td colspan="1" style="font-weight: bold;"> Rp. <?= number_format($hargaPajak, 2) ?> </td>
                </tr>

                <tr>
                    <td colspan="7" style="text-align: end; padding-right:3%; font-weight: bold;">Grand Total Harga</td>
                    <td colspan="1" style="font-weight: bold;">Rp. <?= number_format($hargaAll, 2) ?></td>
                </tr>
            <?php endforeach; ?>
        <?php endforeach; ?>
        </tbody>
        </table>
    <?php elseif ($this->session->userdata('lv') < '3' && $s->status == 'DONE') : ?>
        <?php foreach ($total as $t) : ?>
            <tr>
                <td colspan="7" style="text-align: end; padding-right:3%; font-weight: bold;">Total Harga</td>
                <td colspan="1" style="font-weight: bold;">Rp. <?= number_format($t->total_harga) ?></td>
            </tr>
        <?php endforeach; ?>
        <tr>
            <td colspan="8" class="bg-black color-palette" style="text-align: center;">LIST DISKON</td>
        </tr>
        <?php foreach ($diskon as $d) : ?>
            <?php if ($diskon > 0) : ?>
                <tr>
                    <td colspan="7" style="text-align: end; padding-right:3%; font-weight: bold;"><?= $d->keterangan ?> : </td>
                    <td colspan="1" style="font-weight: bold;">
                        Rp. <?= number_format($d->nominal, 2) ?>
                    </td>
                </tr>
            <?php endif; ?>
        <?php endforeach; ?>
        <tr>
            <td colspan="8" class="bg-black color-palette" style="text-align: center;">Note Pembelian</td>
        </tr>
        <?php foreach ($ntpembelian as $ntpm) : ?>
            <tr>
                <td colspan="8" style="padding-right:3%; font-weight: bold;"><?= $ntpm->keterangan ?></td>
            </tr>
        <?php endforeach; ?>
        <tr>
            <td colspan="8" class="bg-black color-palette" style="text-align: center;">TOTAL HARGA</td>
        </tr>
        <?php foreach ($total as $t) :
                                    foreach ($totalDiskon as $d) :
                                        $stlhDiskon = $t->total_harga - $d->total_diskon;
                                        $tax = $s->tax / 100;
                                        $hargaPajak = $stlhDiskon * $tax;
                                        $hargaAll = $stlhDiskon + $hargaPajak; ?>
                <tr>
                    <td colspan="7" style="text-align: end; padding-right:3%; font-weight: bold;">Total Harga Setelah Diskon :</td>
                    <td colspan="1" style="font-weight: bold;"> Rp.<?= number_format($stlhDiskon, 2) ?> </td>
                </tr>

                <tr>
                    <td colspan="7" style="text-align: end; padding-right:3%; font-weight: bold;">Tax : <?= $s->tax ?>(%)</td>
                    <td colspan="1" style="font-weight: bold;"> Rp. <?= number_format($hargaPajak, 2) ?> </td>
                </tr>

                <tr>
                    <td colspan="7" style="text-align: end; padding-right:3%; font-weight: bold;">Grand Total Harga</td>
                    <td colspan="1" style="font-weight: bold;">Rp. <?= number_format($hargaAll, 2) ?></td>
                </tr>
            <?php endforeach; ?>
        <?php endforeach; ?>
        </tbody>
        </table>
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
    </section>
    <!-- /.content-header -->

</div>
<?php if ($manualMasterCount > 0 && $canManageManualMaster) : ?>
    <div class="modal fade" id="manualMasterList" tabindex="-1" role="dialog" aria-labelledby="manualMasterListTitle" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="manualMasterListTitle">Master Barang Manual PO Jasa</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Tutup"><span aria-hidden="true">&times;</span></button>
                </div>
                <div class="modal-body p-0">
                    <div class="table-responsive">
                        <table class="table table-bordered mb-0">
                            <thead class="thead-light"><tr><th>#</th><th>Nama Material</th><th>Deskripsi</th><th>Satuan</th><th>Qty</th><th>Aksi</th></tr></thead>
                            <tbody>
                                <?php foreach ($manualMasterItems as $manualIndex => $manualItem) : ?>
                                    <tr>
                                        <td><?= $manualIndex + 1 ?></td>
                                        <td><?= htmlspecialchars((string) ($manualItem->nama_material ?: $manualItem->nama_barang), ENT_QUOTES, 'UTF-8') ?></td>
                                        <td><?= htmlspecialchars((string) ($manualItem->deskripsi_material ?: $manualItem->deskripsi), ENT_QUOTES, 'UTF-8') ?></td>
                                        <td><?= htmlspecialchars((string) $manualItem->satuan_material, ENT_QUOTES, 'UTF-8') ?></td>
                                        <td><?= number_format((float) $manualItem->qty, 2, ',', '.') ?></td>
                                        <td><button type="button" class="btn btn-primary btn-sm btn-open-manual-master" data-form="#manualMasterForm<?= (int) $manualItem->source_material_id ?>"><i class="fas fa-edit"></i> Input Master</button></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php foreach ($manualMasterItems as $manualItem) : ?>
        <?php
        $manualName = (string) ($manualItem->nama_material ?: $manualItem->nama_barang);
        $manualDescription = (string) ($manualItem->deskripsi_material ?: $manualItem->deskripsi);
        ?>
        <div class="modal fade" id="manualMasterForm<?= (int) $manualItem->source_material_id ?>" tabindex="-1" role="dialog" aria-hidden="true">
            <div class="modal-dialog modal-lg" role="document">
                <div class="modal-content">
                    <form method="post" action="<?= base_url('postatusnk/master-barang-manual') ?>">
                        <div class="modal-header">
                            <h5 class="modal-title">Input Master Barang Non Komersil</h5>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Tutup"><span aria-hidden="true">&times;</span></button>
                        </div>
                        <div class="modal-body">
                            <input type="hidden" name="kd_po_nk" value="<?= htmlspecialchars((string) $kd, ENT_QUOTES, 'UTF-8') ?>">
                            <input type="hidden" name="id_material" value="<?= (int) $manualItem->source_material_id ?>">
                            <div class="alert alert-info py-2">Material PO Jasa: <strong><?= htmlspecialchars($manualName, ENT_QUOTES, 'UTF-8') ?></strong></div>
                            <div class="form-group"><label>Kode Barang</label><input class="form-control" name="kd_br_adm" value="<?= htmlspecialchars((string) $manualMasterCode, ENT_QUOTES, 'UTF-8') ?>" readonly><small class="form-text text-muted">Digenerate otomatis dan diverifikasi kembali saat disimpan.</small></div>
                            <div class="form-row">
                                <div class="form-group col-md-6"><label>Kategori Barang <span class="text-danger">*</span></label><select class="form-control" name="kat_barang" required><option value="">Pilih kategori</option><?php foreach ($kategoriBarangNk as $kategori) : ?><option value="<?= htmlspecialchars((string) $kategori->kd_kat, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars((string) $kategori->nama_kategori, ENT_QUOTES, 'UTF-8') ?></option><?php endforeach; ?></select></div>
                                <div class="form-group col-md-6"><label>Satuan <span class="text-danger">*</span></label><select class="form-control" name="satuan" required><option value="">Pilih satuan</option><?php foreach ($satuanBarangNk as $satuan) : ?><option value="<?= (int) $satuan->id_satuan ?>"<?= strtoupper(trim((string) $satuan->nm_satuan)) === strtoupper(trim((string) $manualItem->satuan_material)) ? ' selected' : '' ?>><?= htmlspecialchars((string) $satuan->nm_satuan, ENT_QUOTES, 'UTF-8') ?></option><?php endforeach; ?></select></div>
                            </div>
                            <div class="form-group"><label>Nama Barang <span class="text-danger">*</span></label><input class="form-control" name="nama_barang" maxlength="180" value="<?= htmlspecialchars($manualName, ENT_QUOTES, 'UTF-8') ?>" required></div>
                            <div class="form-group"><label>Deskripsi / Spesifikasi</label><textarea class="form-control" name="descnk" rows="3"><?= htmlspecialchars($manualDescription, ENT_QUOTES, 'UTF-8') ?></textarea></div>
                            <input type="hidden" name="minimum_stock" value="0">
                        </div>
                        <div class="modal-footer"><button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button><button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Rekam</button></div>
                    </form>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
<?php endif; ?>
<script>
    (function initPonkDetailAjax() {
        if (typeof window.jQuery === 'undefined') {
            setTimeout(initPonkDetailAjax, 50);
            return;
        }

        var $ = window.jQuery;
        var cancelPengajuanUrl = '<?= base_url('cancel_pengajuan_ponk') ?>';
        var updateTujuanUrl = '<?= site_url('postatusnk/update-tujuan-pembelian') ?>';

        function showPonkAlert(icon, message, callback) {
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    icon: icon,
                    title: icon === 'success' ? 'Berhasil' : 'Gagal',
                    text: message
                }).then(function() {
                    if (typeof callback === 'function') {
                        callback();
                    }
                });
                return;
            }

            alert(message);
            if (typeof callback === 'function') {
                callback();
            }
        }

        $(document).off('click.ponkCancel', '.btn-cancel-pengajuan-ponk').on('click.ponkCancel', '.btn-cancel-pengajuan-ponk', function() {
            var kdPoReq = $(this).attr('data-kd-po-req');
            var processCancel = function() {
                $.ajax({
                    url: cancelPengajuanUrl,
                    type: 'POST',
                    dataType: 'json',
                    data: {
                        kd_po_req: kdPoReq
                    },
                    success: function(response) {
                        if (response.status) {
                            showPonkAlert('success', response.message, function() {
                                location.reload();
                            });
                            return;
                        }

                        showPonkAlert('error', response.message);
                    },
                    error: function() {
                        showPonkAlert('error', 'Terjadi kesalahan saat memproses data');
                    }
                });
            };

            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    icon: 'warning',
                    title: 'Cancel pengajuan?',
                    text: 'Status PO NK akan diubah menjadi PENGAJUAN DIBATALKAN.',
                    showCancelButton: true,
                    confirmButtonText: 'Ya, cancel',
                    cancelButtonText: 'Batal'
                }).then(function(result) {
                    if (result.isConfirmed) {
                        processCancel();
                    }
                });
                return;
            }

            if (confirm('Cancel pengajuan ini?')) {
                processCancel();
            }
        });

        $(document).off('submit.ponkTujuan', '.form-edit-tujuan-pembelian-ponk').on('submit.ponkTujuan', '.form-edit-tujuan-pembelian-ponk', function(e) {
            e.preventDefault();

            var $form = $(this);
            var $submitButton = $form.find('button[type="submit"]');
            $submitButton.prop('disabled', true);

            $.ajax({
                url: updateTujuanUrl,
                type: 'POST',
                dataType: 'json',
                data: $form.serialize(),
                success: function(response) {
                    if (response.status) {
                        $form.closest('.modal').modal('hide');
                        showPonkAlert('success', response.message, function() {
                            location.reload();
                        });
                        return;
                    }

                    showPonkAlert('error', response.message);
                },
                error: function() {
                    showPonkAlert('error', 'Terjadi kesalahan saat menyimpan data');
                },
                complete: function() {
                    $submitButton.prop('disabled', false);
                }
            });
        });

        $(document).off('click.ponkManualMaster', '.btn-open-manual-master').on('click.ponkManualMaster', '.btn-open-manual-master', function() {
            var target = $(this).data('form');
            $('#manualMasterList').one('hidden.bs.modal', function() {
                $(target).modal('show');
            }).modal('hide');
        });
    })();
</script>
<?php endforeach; ?>
