<div class="content-wrapper">
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0">
                    </h1>
                </div><!-- /.col -->
            </div><!-- /.row -->

            <div style="display: flex; text-align: center;">
                <a href="<?= base_url('dashboard') ?>">
                    <i class="fa fa-arrow-left  ml-4 mr-4 mt-2"></i>
                </a>
                <h3 class="">Purchase Order Non Komersial</h3>
            </div>
            <a href="<?= base_url('pononkomersil/list_stocknkpo') ?> " class="btn btn-primary mb-2 mt-2">
                <i class="fas fa-folder-plus"></i> &nbsp; Tambah Barang
            </a>
            <a href="#" class="btn btn-primary mb-2 mt-2" data-toggle="modal" data-target="#add_nt_pembelian">
                <i class="fas fa-folder-plus"></i> &nbsp; Note Pembelian
            </a>

            <?php $this->load->view('content/po/modal/modalnonkomersil') ?>
            <form action="#">
                <div class="row mb-2">
                    <div class="col-md-3" hidden>
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text"><i class="fas fa-clipboard"></i></span>
                            </div>
                            <input type="text" class="form-control" placeholder="Nomor PO" value="<?= $nopo ?>" name="po_isi" id="po_isi" readonly>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text"><i class="fas fa-clipboard"></i></span>
                            </div>
                            <input type="text" class="form-control" placeholder="Nomor Po" value="<?= $nopk ?>" name="no_po_isi" id="no_po_isi" readonly>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text"><i class="fas fa-user"></i></span>
                            </div>
                            <input type="text" class="form-control" placeholder="Nama Pengaju" value="<?= $nmuser ?>" name="nm_user" id="nm_user" readonly>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text"><i class="fas fa-landmark"></i></span>
                            </div>
                            <input type="text" class="form-control" placeholder="Departemen" value="<?= $depuser ?>" name="dep_isi" id="dep_isi" readonly>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text"><i class="fas fa-shopping-basket"></i></span>
                            </div>
                            <input type="text" class="form-control" placeholder="Tujuan Pembelian" value="" name="tujuan_isi" id="tujuan_isi">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text"><i class="fas fa-calendar"></i></span>
                            </div>
                            <input type="date" class="form-control" placeholder="Tanggal Transaksi" value="" name="tgl_isi" id="tgl_isi">
                        </div>
                    </div>
                </div>

                <table id="" class="table table-striped">
                    <thead style="background-color: #212529; color:white;">
                        <tr>
                            <td>No</td>
                            <td>Nama Barang</td>
                            <td>Deskripsi</td>
                            <td>Keterangan</td>
                            <td>Qty</td>
                            <td>Harga Satuan</td>
                            <td>Total Harga</td>
                            <!-- <td>Gambar Pendukung</td> -->
                            <td>#</td>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $no = 1;
                        foreach ($tmp as $tmp) : ?>
                            <tr>
                                <td><?= $no++; ?></td>
                                <td><?= $tmp->nama_barang ?></td>
                                <td><?= $tmp->deskripsi ?></td>
                                <td><?= $tmp->keterangan ?></td>
                                <td><?= $tmp->qty ?></td>
                                <td><?= $tmp->hrg_satuan ?></td>
                                <td><?= $tmp->total_harga ?></td>
                                <td>
                                    <a href="#" class="btn btn-warning btn-sm " data-toggle="modal" data-target="#edititem<?= $tmp->id_tmp_nk ?>">
                                        <i class="fa fa-solid fa-pencil-alt"></i>
                                    </a>
                                    <a href="#" class="btn btn-danger btn-sm " data-toggle="modal" data-target="#hapusitem<?= $tmp->id_tmp_nk ?>">
                                        <i class="fa fa-solid fa-trash-alt"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <tr>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                        </tr>
                        <?php foreach ($total as $tot) : ?>
                            <tr>
                                <td colspan="6" style="text-align: end; padding-right:3%; font-weight: bold;">Total Harga</td>
                                <td colspan="3" style="font-weight: bold;">Rp. <?= number_format($tot->total_harga) ?>
                                    <input type="number" class="form-control" id="jmlitem" name="jmlitem" value="<?= $tot->total_item ?>" readonly hidden>
                                    <input type="number" class="form-control" id="jmlharga" name="jmlharga" value="<?= $tot->total_harga ?>" readonly hidden>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <table id="" class="table table-striped mt-2">
                    <thead style="background-color: #212529; color:white;">
                        <tr>
                            <td colspan="2" style="text-align: center;">Note Pemebelian</td>
                        </tr>
                    </thead>
                    <?php foreach ($tmpntpembelian as $np) : ?>
                        <tbody>
                            <tr>
                                <td><?= $np->keterangan ?></td>
                                <td style="text-align: center;">
                                    <a href="#" class="btn btn-warning btn-sm " data-toggle="modal" data-target="#edititem<?= $np->id_tmp_nt_pembelian ?>">
                                        <i class="fa fa-solid fa-pencil-alt"></i>
                                    </a>
                                    <a href="<?= base_url("hapus_note_pembelian_tmp/" . $np->id_tmp_nt_pembelian) ?>" class="btn btn-danger btn-sm ">
                                        <i class="fa fa-solid fa-trash-alt"></i>
                                    </a>
                                </td>
                            </tr>
                        </tbody>
                    <?php endforeach; ?>
                </table>

                <div class="btnBawah">
                    <button class="btn btn-warning mr-2" type="reset">Reset</button>
                    <button type="button" id="selesaink" class="btn btn-primary">
                        <i class="fa fa-print pr-1"></i>Rekam Order
                    </button>
                </div>
        </div>

        </form>

    </div><!-- /.container-fluid -->

</div>

<!-- /.content-header -->
</div>