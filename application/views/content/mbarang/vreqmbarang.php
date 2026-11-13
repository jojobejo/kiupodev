<div class="content-wrapper">
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <?php if ($this->session->userdata('lv') == '2' || $this->session->userdata('lv') == 1) : ?>
                        <h3>List Request Master Barang</h3>
                    <?php elseif ($this->session->userdata('lv') == '4') : ?>
                        <h3>Request Master Barang</h3>
                        <a href="#" class="btn btn-primary btn-sm " data-toggle="modal" data-target="#reqmnumb">
                            <i class="fas fa-plus"></i> Add Request Master Barang
                        </a>
                    <?php endif; ?>
                </div><!-- /.col -->
            </div><!-- /.row -->
            <!-- VIEW ADMIN PURCHASING -->
            <?php if ($this->session->userdata('lv') == '2' || $this->session->userdata('lv') == '1') : ?>

                <?php if (!empty($pojasa_usulan)) : ?>
                <h4 class="mt-4">Usulan Material PO Jasa dari PIC</h4>
                <div class="table-responsive"><table class="table table-bordered table-sm"><thead><tr><th>Kode usulan</th><th>Request / PIC</th><th>Nama & spesifikasi</th><th>Satuan</th><th>Status</th><th>Keputusan Purchasing</th></tr></thead><tbody>
                <?php foreach ($pojasa_usulan as $usulan) : ?><tr><td><?= htmlspecialchars($usulan->kode_usulan) ?></td><td><?= htmlspecialchars($usulan->kd_po_jasa . ' / ' . $usulan->nm_user) ?></td><td><strong><?= htmlspecialchars($usulan->nama_barang_input) ?></strong><br><small><?= nl2br(htmlspecialchars($usulan->deskripsi_input)) ?></small></td><td><?= htmlspecialchars($usulan->satuan_input) ?></td><td><?= htmlspecialchars($usulan->status_usulan) ?><?= $usulan->kd_barang_hasil ? '<br><small>' . htmlspecialchars($usulan->kd_barang_hasil) . '</small>' : '' ?></td><td><?php if (in_array($usulan->status_usulan, array('SUBMITTED','REVISION_REQUIRED'))) : ?><form method="post" action="<?= base_url('vrequestmbarang/pojasa/decide') ?>"><input type="hidden" name="id_usulan_barang" value="<?= (int)$usulan->id_usulan_barang ?>"><select name="action" class="form-control form-control-sm mb-1"><option value="MAPPED_EXISTING">Tautkan ke master tersedia</option><option value="APPROVED_NEW_MASTER">Setujui master baru (buat master dahulu)</option><option value="REVISION_REQUIRED">Minta revisi PIC</option><option value="REJECTED">Tolak sebagai master</option></select><select name="id_brg_nk" class="form-control form-control-sm mb-1"><option value="">Pilih master hasil keputusan</option><?php foreach ($barangnk as $master) : ?><option value="<?= (int)$master->id_brg_nk ?>"><?= htmlspecialchars($master->kd_barang . ' — ' . $master->nama_barang) ?></option><?php endforeach; ?></select><input name="catatan_purchasing" class="form-control form-control-sm mb-1" maxlength="5000" placeholder="Catatan Purchasing"><button class="btn btn-success btn-sm btn-block">Simpan</button></form><?php else : ?><?= nl2br(htmlspecialchars($usulan->catatan_purchasing ?: '-')) ?><?php endif; ?></td></tr><?php endforeach; ?>
                </tbody></table></div>
                <?php endif; ?>

                <?php $this->load->view('content/mbarang/mreqbarang.php') ?>

                <table class="table table-bordered table-striped" id="list_req">
                    <thead>
                        <tr>
                            <td>Nama Inputer</td>
                            <td>Departemen</td>
                            <td>Nama Barang</td>
                            <td>Deskripsi / Spesifikasi</td>
                            <td>Satuan</td>
                            <td>Aksi</td>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($listreqbr as $l) : ?>
                            <tr>
                                <td><?= $l->nama_user ?></td>
                                <td><?= $l->departement ?></td>
                                <td><?= $l->nama_barang ?></td>
                                <td><?= $l->deskripsi ?></td>
                                <td><?= $l->nm_satuan ?></td>
                                <td>
                                    <a href="#" class="btn btn-block btn-success btn-sm " data-toggle="modal" data-target="#approvedbrgnk<?= $l->id_reqmbarang ?>">
                                        <i class="fas fa-check-circle"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <!-- VIEW PIC -->

            <?php elseif ($this->session->userdata('lv') == '4') : ?>

                <table class="table table-bordered table-striped" id="list_req">
                    <thead>
                        <tr>
                            <td>Nama Barang</td>
                            <td>Deskripsi / Spesifikasi</td>
                            <td>Satuan</td>
                            <td>#</td>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($listrebrpic as $lb) : ?>
                            <tr>
                                <td><?= $lb->nama_barang ?></td>
                                <td><?= $lb->deskripsi ?></td>
                                <td><?= $lb->nm_satuan ?></td>
                                <td>
                                    <a href="#" class="btn btn-block btn-warning btn-sm">
                                        <i class="fas fa-sync"></i>
                                        ON PROGRESS
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

            <?php endif; ?>
        </div><!-- /.container-fluid -->
    </div>

    <!-- /.content-header -->
</div>
