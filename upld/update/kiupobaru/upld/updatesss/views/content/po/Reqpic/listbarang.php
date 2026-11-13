<div class="content-wrapper">
    <div class="content-header">
        <?php $this->load->view('content/po/Reqpic/modalreq.php') ?>
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <?php if ($this->session->userdata('lv') == '2') : ?>
                        <a href="<?= base_url('reqpic/admrestock') ?>" class="btn btn-success btn-block"><i class="fas fa-home"></i> BACK</a>
                    <?php else : ?>
                        <a href="<?= base_url('reqpic') ?>" class="btn btn-success btn-block"><i class="fas fa-home"></i> BACK</a>
                    <?php endif; ?>
                </div><!-- /.col -->
                <div class="col-sm-6">
                    <a href="#" class="btn btn-primary btn-block" data-toggle="modal" data-target="#reqmasterbarang"><i class="fas fa-folder-plus">&nbsp;</i>Req Master Barang</a>
                </div><!-- /.col -->
            </div><!-- /.row -->
            <!-- END CONTAINER CONTENT -->
            <div class="card">
                <div class="card-body">
                    <table class="table table-bordered table-striped" id="list_stocknonkomersil" name="">
                        <thead>
                            <tr>
                                <td>Nama Barang</td>
                                <td>Deskripsi / Spesifikasi</td>
                                <td style="text-align: center;">Stock</td>
                                <td style="text-align: center;">Satuan</td>
                                <td style="text-align: center;">Gambar Produk</td>
                                <td style="text-align: center;">#</td>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($lstock as $l) :
                                if ($l->gbr_barang == 'Karisma.png') {
                                    $imagePath = "images/gbrbarang/masterbr/Karisma.png";
                                } else {
                                    $imagePath = "images/gbrbarang/masterbr/" . $l->gbr_barang;
                                }
                            ?>
                                <tr>
                                    <td><?= $l->nama_barang ?></td>
                                    <td><?= $l->descnk ?></td>
                                    <?php if ($l->qty_ready == '0') : ?>
                                        <td style="text-align: center;background-color:#e7a532;"><?= $l->qty_ready ?></td>
                                    <?php else : ?>
                                        <td style="text-align: center;background-color: ;"><?= $l->qty_ready ?></td>
                                    <?php endif ?>
                                    <td style="text-align: center;"><?= $l->nm_satuan ?></td>
                                    <td>
                                        <a href="<?= $imagePath ?>" class="btn btn-secondary btn-sm btn-block" data-toggle="lightbox">Buka File</a>
                                    </td>
                                    <td>
                                        <a href="#" class="btn btn-block btn-success btn-sm js-add-request-item" data-toggle="modal" data-target="#addreq"
                                            data-kode-sys="<?= htmlspecialchars($l->kode_sys, ENT_QUOTES, 'UTF-8') ?>"
                                            data-kode-adm="<?= htmlspecialchars($l->kode_adm, ENT_QUOTES, 'UTF-8') ?>"
                                            data-satuan="<?= htmlspecialchars($l->id_satuan, ENT_QUOTES, 'UTF-8') ?>"
                                            data-kategori="<?= htmlspecialchars($l->kat_barang, ENT_QUOTES, 'UTF-8') ?>"
                                            data-nama="<?= htmlspecialchars($l->nama_barang, ENT_QUOTES, 'UTF-8') ?>"
                                            data-deskripsi="<?= htmlspecialchars($l->descnk, ENT_QUOTES, 'UTF-8') ?>">
                                            <i class="fa fa-solid fa-cart-plus"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <!-- END VIEW CONTENT -->
</div>
