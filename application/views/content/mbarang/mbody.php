<div class="content-wrapper">
    <div class="content-header">
        <div class="container-fluid">
            <?php if ($this->session->flashdata('error')) : ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?= htmlspecialchars($this->session->flashdata('error'), ENT_QUOTES, 'UTF-8') ?>
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
            <?php endif; ?>
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0">
                    </h1>
                </div><!-- /.col -->
            </div><!-- /.row -->

            <?php $this->load->view('content/mbarang/modal/modalmbarang.php'); ?>

            <!-- VIEW ADMIN PURCHASING -->
            <?php if ($this->session->userdata('lv') == '2' || $this->session->userdata('lv') == '1') : ?>

                <table class="table table-bordered table-striped" id="list_mbarangnk">
                    <thead>
                        <div class="row">
                            <a class="btn btn-info btn-md ml-2" data-toggle="modal" data-target="#addmbarangnk">
                                <i class="fas fa-plus"></i>
                                Add Barang
                            </a>
                        </div>
                        <tr>
                            <td>No</td>
                            <td>Kode Barang</td>
                            <td>Kode Sistem</td>
                            <td>Nama Barang</td>
                            <td>Deskripsi / Spesifikasi</td>
                            <td>Satuan</td>
                            <td>Minimum Stock</td>
                            <td>Gambar Produk</td>
                            <td>QR CODE</td>
                            <td>Aksi</td>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $no = 1;
                        foreach ($barangnk as $brnk) :
                            if ($brnk->gbr_barang == 'Karisma.png') {
                                $imagePath = "images/gbrbarang/masterbr/Karisma.png";
                            } else {
                                $imagePath = "images/gbrbarang/masterbr/" . $brnk->gbr_barang;
                            }
                            $nullimg    = "./images/qrcodebr/";
                            $imgqrcode  = "./images/qrcodebr/" . $brnk->qrcode_path;

                            $str    = $brnk->nama_barang;
                            $strnew = str_replace(' ', '', $str);
                            $idBarang = htmlspecialchars((string) $brnk->id_brg_nk, ENT_QUOTES, 'UTF-8');
                            $kodeAdm = htmlspecialchars((string) $brnk->kd_br_adm, ENT_QUOTES, 'UTF-8');
                            $katBarang = htmlspecialchars((string) $brnk->kat_barang, ENT_QUOTES, 'UTF-8');
                            $namaBarang = htmlspecialchars((string) $brnk->nama_barang, ENT_QUOTES, 'UTF-8');
                            $descBarang = htmlspecialchars((string) $brnk->descnk, ENT_QUOTES, 'UTF-8');
                            $satuanBarang = htmlspecialchars((string) $brnk->id_satuan, ENT_QUOTES, 'UTF-8');
                            $minimumStock = htmlspecialchars((string) $brnk->minimum_stock, ENT_QUOTES, 'UTF-8');
                            $gbrBarang = htmlspecialchars((string) $brnk->gbr_barang, ENT_QUOTES, 'UTF-8');
                        ?>
                            <tr>
                                <td><?= $no++; ?></td>
                                <td><?= $brnk->kd_barang ?></td>
                                <td><?= $brnk->kd_br_adm ?></td>
                                <td><?= $brnk->nama_barang ?></td>
                                <td><?= $brnk->descnk ?></td>
                                <td><?= $brnk->nm_satuan ?></td>
                                <td><?= number_format((float)$brnk->minimum_stock, 2, ',', '.') ?></td>
                                <td>
                                    <a href="<?= $imagePath ?>" class="btn btn-secondary btn-sm btn-block" data-toggle="lightbox" data-title="<?= $brnk->nama_barang ?>">Buka File
                                    </a>
                                </td>
                                <?php if ($imgqrcode == $nullimg) : ?>
                                    <td><a href="#" class="btn btn-danger btn-sm btn-block"><i class="fas fa-times-circle"></i></a></td>
                                <?php else : ?>
                                    <td><a href="<?= $imgqrcode ?>" class="btn btn-success btn-sm btn-block" data-toggle="lightbox" data-title="<?= $brnk->nama_barang . '(' . $brnk->kd_br_adm . ')' ?>">Buka File</a></td>
                                <?php endif; ?>
                                <td>
                                    <div class="row">
                                        <div class="col">
                                            <a href="#" class="btn btn-block btn-warning btn-sm js-edit-barang" data-toggle="modal" data-target="#editbarang" data-id="<?= $idBarang ?>" data-kd-adm="<?= $kodeAdm ?>" data-kategori="<?= $katBarang ?>" data-nama="<?= $namaBarang ?>" data-desc="<?= $descBarang ?>" data-satuan="<?= $satuanBarang ?>" data-minimum-stock="<?= $minimumStock ?>">
                                                <i class="fa fa-solid fa-pencil-alt"></i>
                                            </a>
                                        </div>
                                        <div class="col-md">
                                            <a href="#" class="btn btn-block btn-danger btn-sm js-delete-barang" data-toggle="modal" data-target="#hapusbarang" data-id="<?= $idBarang ?>" data-nama="<?= $namaBarang ?>">
                                                <i class="fa fa-solid fa-trash-alt"></i>
                                            </a>
                                        </div>
                                        <div class="col-md">
                                            <a href="#" class="btn btn-block btn-success btn-sm js-upload-barang" data-toggle="modal" data-target="#uploadmbrang" data-id="<?= $idBarang ?>" data-file="<?= $gbrBarang ?>" data-kd-adm="<?= $kodeAdm ?>">
                                                <i class="fas fa-camera"></i>
                                            </a>
                                        </div>
                                        <?php if ($imgqrcode == $nullimg) : ?>
                                            <div class="col-md">
                                                <a href="<?= base_url('genqrcode/') . $brnk->id_brg_nk . "/" . $kdqrcode . "/" . $strnew ?>" class="btn btn-block btn-info btn-sm ">
                                                    <i class="fas fa-qrcode"></i>
                                                </a>
                                            </div>
                                        <?php endif; ?>
                                    </div>

                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php elseif ($this->session->userdata('lv') != '2' || $this->session->userdata('lv') != '1') : ?>
            <?php endif; ?>
        </div><!-- /.container-fluid -->
    </div>

    <!-- /.content-header -->
</div>
