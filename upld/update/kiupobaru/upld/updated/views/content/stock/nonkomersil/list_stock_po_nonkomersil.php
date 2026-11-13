<?php $this->load->view('content/stock/nonkomersil/modal/mlist_stok_ponk.php'); ?>

<?php if ($this->uri->segment(3, 0)) :
    $kdbr = $this->uri->segment(5, 0); ?>

    <div class="content-wrapper">
        <div class="content-header">
            <div class="container-fluid">
                <div class="row mb-2">
                    <div class="col-sm-6">
                        <a href="<?= base_url('detailponk/' . $kdbr) ?>" class="btn btn-success"><i class="fas fa-home"></i> BACK</a>
                        <?php if ($this->session->userdata('lv') != '3' || $this->session->userdata('lv') != '5') : ?>
                            <a href="#" class="btn btn-success " data-toggle="modal" data-target="#addmbarangnk"><i class="fas fa-folder-plus"></i> Req Add Master Barang</a>
                        <?php else : ?>
                        <?php endif; ?>
                    </div><!-- /.col -->
                </div><!-- /.row -->


                <!-- VIEW ADMIN PURCHASING -->
                <?php if ($this->session->userdata('lv') == '2' || $this->session->userdata('lv') == '1') : ?>

                    <table class="table table-bordered table-striped" id="list_stocknonkomersil">
                        <thead>
                            <tr>
                                <td>Nama Barang</td>
                                <td>Deskripsi / Spesifikasi</td>
                                <td>Satuan</td>
                                <td>Gambar Produk</td>
                                <td>Aksi</td>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($stocknk as $s) :
                                if ($s->gbr_barang == 'Karisma.png') {
                                    $imagePath = "../images/gbrbarang/masterbr/Karisma.png";
                                } else {
                                    $imagePath = "../images/gbrbarang/masterbr/" . $s->gbr_barang;
                                }
                            ?>
                                <tr>
                                    <td><?= $s->nama_barang ?></td>
                                    <td><?= $s->descnk ?></td>
                                    <td><?= $s->nm_satuan ?></td>
                                    <td>
                                        <a href="<?= $imagePath ?>" class="btn btn-secondary btn-sm btn-block" data-toggle="lightbox">Buka File
                                        </a>
                                    </td>
                                    <td>
                                        <div class="row">
                                            <div class="col">
                                                <a href="#" class="btn btn-block btn-success btn-sm " data-toggle="modal" data-target="#addchartdetponk<?= $s->id_brg_nk ?>">
                                                    <i class="fa fa-solid fa-cart-plus"></i>
                                                </a>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>

                    <!-- VIEW PIC -->

                <?php elseif ($this->session->userdata('lv') == '4') : ?>
                    <table class="table table-bordered table-striped" id="list_stocknonkomersil">
                        <thead>
                            <tr>
                                <td>Nama Barang</td>
                                <td>Deskripsi / Spesifikasi</td>
                                <td>Satuan</td>
                                <td>Gambar Produk</td>
                                <td>Aksi</td>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($stocknk as $s) :
                                if ($s->gbr_barang == 'Karisma.png') {
                                    $imagePath = "../images/gbrbarang/masterbr/Karisma.png";
                                } else {
                                    $imagePath = "../images/gbrbarang/masterbr/" . $s->gbr_barang;
                                }
                            ?>
                                <tr>
                                    <td><?= $s->nama_barang ?></td>
                                    <td><?= $s->descnk ?></td>
                                    <td><?= $s->nm_satuan ?></td>
                                    <td>
                                        <a href="<?= $imagePath ?>" class="btn btn-secondary btn-sm btn-block" data-toggle="lightbox">Buka File
                                        </a>
                                    </td>
                                    <td>
                                        <div class="row">
                                            <div class="col">
                                                <a href="#" class="btn btn-block btn-success btn-sm " data-toggle="modal" data-target="#addchartdetponk<?= $s->id_brg_nk ?>">
                                                    <i class="fa fa-solid fa-cart-plus"></i>
                                                </a>
                                            </div>
                                        </div>
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
<?php else :  ?>
    <div class="content-wrapper">
        <div class="content-header">
            <div class="container-fluid">
                <div class="row mb-2">
                    <div class="col-sm-6">
                        <a href="<?= base_url('pononkomersil') ?>" class="btn btn-success"><i class="fas fa-home"></i> BACK</a>
                        <?php if ($this->session->userdata('lv') != '3' || $this->session->userdata('lc') != '5') : ?>
                            <a href="#" class="btn btn-success " data-toggle="modal" data-target="#addmbarangnk"><i class="fas fa-folder-plus"></i> Req Add Master Barang</a>
                        <?php else : ?>
                        <?php endif; ?>
                    </div><!-- /.col -->
                </div><!-- /.row -->


                <!-- VIEW ADMIN PURCHASING -->
                <?php if ($this->session->userdata('lv') == '2' || $this->session->userdata('lv') == '1') : ?>

                    <table class="table table-bordered table-striped" id="list_stocknonkomersil">
                        <thead>
                            <tr>
                                <td>Nama Barang</td>
                                <td>Deskripsi / Spesifikasi</td>
                                <td>Satuan</td>
                                <td>Gambar Produk</td>
                                <td>Aksi</td>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($stocknk as $s) :
                                if ($s->gbr_barang == 'Karisma.png') {
                                    $imagePath = "../images/gbrbarang/masterbr/Karisma.png";
                                } else {
                                    $imagePath = "../images/gbrbarang/masterbr/" . $s->gbr_barang;
                                }
                            ?>
                                <tr>
                                    <td><?= $s->nama_barang ?></td>
                                    <td><?= $s->descnk ?></td>
                                    <td><?= $s->nm_satuan ?></td>
                                    <td>
                                        <a href="<?= $imagePath ?>" class="btn btn-secondary btn-sm btn-block" data-toggle="lightbox">Buka File
                                        </a>
                                    </td>
                                    <td>
                                        <div class="row">
                                            <div class="col">
                                                <a href="#" class="btn btn-block btn-success btn-sm " data-toggle="modal" data-target="#addchartponk<?= $s->id_brg_nk ?>">
                                                    <i class="fa fa-solid fa-cart-plus"></i>
                                                </a>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>

                    <!-- VIEW PIC -->

                <?php elseif ($this->session->userdata('lv') == '4') : ?>
                    <table class="table table-bordered table-striped" id="list_stocknonkomersil">
                        <thead>
                            <tr>
                                <td>Nama Barang</td>
                                <td>Deskripsi / Spesifikasi</td>
                                <td>Satuan</td>
                                <td>Gambar Produk</td>
                                <td>Aksi</td>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($stocknk as $s) :
                                if ($s->gbr_barang == 'Karisma.png') {
                                    $imagePath = "../images/gbrbarang/masterbr/Karisma.png";
                                } else {
                                    $imagePath = "../images/gbrbarang/masterbr/" . $s->gbr_barang;
                                }
                            ?>
                                <tr>
                                    <td><?= $s->nama_barang ?></td>
                                    <td><?= $s->descnk ?></td>
                                    <td><?= $s->nm_satuan ?></td>
                                    <td>
                                        <a href="<?= $imagePath ?>" class="btn btn-secondary btn-sm btn-block" data-toggle="lightbox">Buka File
                                        </a>
                                    </td>
                                    <td>
                                        <div class="row">
                                            <div class="col">
                                                <a href="#" class="btn btn-block btn-success btn-sm " data-toggle="modal" data-target="#addchartponk<?= $s->id_brg_nk ?>">
                                                    <i class="fa fa-solid fa-cart-plus"></i>
                                                </a>
                                            </div>
                                        </div>
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
<?php endif; ?>