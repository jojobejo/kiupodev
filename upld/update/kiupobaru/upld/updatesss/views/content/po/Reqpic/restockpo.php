<a href="" class="btn btn-sm btn-success"><i class="fas fa-check"></i></a>
<div class="content-wrapper">
    <div class="content-header">
        <div class="container-fluid">
            <div class="card">
                <div class="card-body">
                    <div class="row mb-2 ml-2">
                        <a href="<?= base_url('reqpic') ?>" class="btn btn-md btn-primary"><i class="fas fa-home"></i></a>
                        <div class="col-sm-3">
                            <h1 class="m-0">Re-Stock Admin</h1>
                        </div>
                        <div class="col-sm-3">
                            <a href="<?= base_url('listbarangready') ?>" class="btn btn-md btn-primary"><i class="fas fa-boxes"></i> List Barang</a>
                        </div>
                    </div> <!-- END ROW -->
                    <table class="table table-bordered" id="list_reqpic">
                        <thead class="table-dark">
                            <tr>
                                <td>Nama Pengaju</td>
                                <td>Departemen</td>
                                <td>Tanggal Transaksi</td>
                                <td>Jam</td>
                                <td style="width: min-content;">Tujuan Pembelian</td>
                                <td>Status</td>
                                <td>Status PO</td>
                                <td>#</td>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($getlistadm as $g) : ?>
                                <tr>
                                    <td><?= $g->nm_user ?></td>
                                    <td><?= $g->departemen ?></td>
                                    <td><?= format_tgl_lahir($g->tgl_transaksi) ?></td>
                                    <td><?= format_jam_24($g->create_at) ?></td>
                                    <td style="width: min-content;"><?= $g->tj_pembelian ?></td>
                                    <td>
                                        <a class="btn btn-block btn-warning btn-sm m-1"><b><?= $g->status ?></b></a>
                                    </td>
                                    <td>
                                        <?php if ($g->status_po == 'ON PROGRESS - KADEP') : ?>
                                            <a class="btn btn-block btn-warning btn-sm"><b><?= $g->status_po ?></b></a>
                                        <?php elseif ($g->status_po == 'ACC-KADEP') : ?>
                                            <a class="btn btn-block btn-primary btn-sm"><b><?= $g->status_po ?></b></a>
                                        <?php elseif ($g->status_po == 'SEDANG DIAJUKAN') : ?>
                                            <a class="btn btn-block btn-warning btn-sm"><b><?= $g->status_po ?></b></a>
                                        <?php elseif ($g->status_po == 'ACC DIREKTUR') : ?>
                                            <a class="btn btn-block btn-primary btn-sm"><b><?= $g->status_po ?></b></a>
                                        <?php elseif ($g->status_po == 'PROSES PEMBELIAN') : ?>
                                            <a class="btn btn-block btn-primary btn-sm"><b><?= $g->status_po ?></b></a>
                                        <?php elseif ($g->status_po == 'DONE') : ?>
                                            <a class="btn btn-block btn-success btn-sm"><b><?= $g->status_po ?></b></a>
                                        <?php elseif ($g->status_po == 'REJECT') : ?>
                                            <a class="btn btn-block btn-danger btn-sm"><b><?= $g->status_po ?></b></a>
                                        <?php elseif ($g->status_po == '0') : ?>
                                            <a class="btn btn-block btn-secondary btn-sm"><b>BARANG READY</b></a>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="row">
                                            <div class="col">
                                                <a class="btn btn-block btn-primary btn-sm" href="<?= base_url('reqpic/detreqbarangpic/' . $g->kd_po_nk) ?>"><i class="fas fa-eye"></i></a>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>

                </div> <!-- END CARD BODY -->
            </div> <!-- END CARD -->


            <div class="row mb-2">
                <div class="col-auto">
                    <a href="javascript:void(0)" class="btn btn-sm btn-primary" id="btn-restock">
                        <i class="fas fa-plus"></i> Restock
                    </a>
                </div>
                <div class="col-auto">
                    <a href="javascript:void(0)" class="btn btn-sm btn-success" id="btn-request">
                        <i class="fas fa-plus"></i> Pengajuan ATK & RTK
                    </a>
                </div>
            </div>

            <?php $this->load->view('content/po/Reqpic/modalreqedit.php') ?>

            <!-- CARD RESTOCK -->
            <div class="card mt-3" id="card-restock" style="display:none;">
                <div class="card-body">
                    <div class="row mb-2">
                        <div class="col">
                            <a href="<?= base_url('listbarangready') ?>" class="btn btn-sm btn-primary"><i class="fas fa-plus"></i>&nbsp; Tambah Barang </a>
                        </div>
                    </div>
                    <?php echo form_open_multipart('addnewreq/' . $this->session->userdata('kode')); ?>
                    <div class="col mb-2 mt-5">
                        <div class="row">
                            <div class="col-md-auto">
                                <label for="naSupp" class="">Tujuan Request : </label>
                            </div>
                            <div class="col-md">
                                <input type="text" id="intj" name="intj" style="max-width: 550px;" value="Restock By Admin PO" class="form-control" placeholder="Input Tujuan Pengajuan" readonly>
                            </div>
                        </div>
                    </div>

                    <table class="table table-bordered table-striped" id="list_reqpic">
                        <thead>
                            <tr>
                                <td>Nama Barang</td>
                                <td>Deskripsi</td>
                                <td>Keterangan</td>
                                <td>QTY</td>
                                <td>Satuan</td>
                                <td>#</td>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($tmpreq as $t) : ?>
                                <tr>
                                    <td><?= $t->nama_barang ?></td>
                                    <td><?= $t->descnk ?></td>
                                    <td><?= $t->keterangan ?></td>
                                    <td><?= $t->qty ?></td>
                                    <td><?= $t->nm_satuan ?></td>
                                    <td>
                                        <div class="row">
                                            <div class="col">
                                                <a class="btn btn-block btn-warning btn-sm" data-toggle="modal" data-target="#edit<?= $t->id_tmp_nk ?>">
                                                    <i class="fas fa-pencil-alt"></i>
                                                </a>
                                            </div>
                                            <div class="col">
                                                <a class="btn btn-block btn-danger btn-sm" data-toggle="modal" data-target="#hapus<?= $t->id_tmp_nk ?>">
                                                    <i class="fas fa-times-circle"></i>
                                                </a>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                        </tbody>
                    <?php endforeach; ?>
                    <tfoot>
                        <tr>
                            <td colspan="6">
                                <input class="form-control" type="text" id="kdponk" name="kdponk" value="<?= $generatekd ?>" readonly hidden />
                                <input class="form-control" type="text" id="totbr" name="totbr" value="<?= $jumlahbr ?>" readonly hidden />
                                <button type="submit" class="btn btn-block btn-primary btn-sm"><i class="fas fa-cloud-upload-alt"></i></button>
                                </form>
                            </td>
                        </tr>
                    </tfoot>
                    </table>
                </div>
            </div>

            <!-- CARD REQUEST -->
            <div class="card mt-3" id="card-request" style="display:none;">
                <div class="card-body">
                    <div class="row mb-2">
                        <div class="col">
                            <a href="<?= base_url('listbarangready') ?>" class="btn btn-sm btn-primary"><i class="fas fa-plus"></i>&nbsp; Tambah Barang </a>
                        </div>
                    </div>
                    <?php echo form_open_multipart('addnewreq/' . $this->session->userdata('kode')); ?>
                    <div class="col mb-2 mt-5">
                        <div class="row">
                            <div class="col-md-auto">
                                <label for="naSupp" class="">Tujuan Request : </label>
                            </div>
                            <div class="col-md">
                                <input type="text" id="intj" name="intj" style="max-width: 550px;" value="Request ATK/RTK Purchasing" class="form-control" placeholder="Input Tujuan Pengajuan" readonly>
                            </div>
                        </div>
                    </div>

                    <table class="table table-bordered table-striped" id="list_reqpic">
                        <thead>
                            <tr>
                                <td>Nama Barang</td>
                                <td>Deskripsi</td>
                                <td>Keterangan</td>
                                <td>QTY</td>
                                <td>Satuan</td>
                                <td>#</td>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($tmpreq as $t) : ?>
                                <tr>
                                    <td><?= $t->nama_barang ?></td>
                                    <td><?= $t->descnk ?></td>
                                    <td><?= $t->keterangan ?></td>
                                    <td><?= $t->qty ?></td>
                                    <td><?= $t->nm_satuan ?></td>
                                    <td>
                                        <div class="row">
                                            <div class="col">
                                                <a class="btn btn-block btn-warning btn-sm" data-toggle="modal" data-target="#edit<?= $t->id_tmp_nk ?>">
                                                    <i class="fas fa-pencil-alt"></i>
                                                </a>
                                            </div>
                                            <div class="col">
                                                <a class="btn btn-block btn-danger btn-sm" data-toggle="modal" data-target="#hapus<?= $t->id_tmp_nk ?>">
                                                    <i class="fas fa-times-circle"></i>
                                                </a>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                        </tbody>
                    <?php endforeach; ?>
                    <tfoot>
                        <tr>
                            <td colspan="6">
                                <input class="form-control" type="text" id="kdponk" name="kdponk" value="<?= $generatekd ?>" readonly hidden />
                                <input class="form-control" type="text" id="totbr" name="totbr" value="<?= $jumlahbr ?>" readonly hidden />
                                <button type="submit" class="btn btn-block btn-primary btn-sm"><i class="fas fa-cloud-upload-alt"></i></button>
                                </form>
                            </td>
                        </tr>
                    </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener("DOMContentLoaded", function() {

        const btnRestock = document.getElementById("btn-restock");
        const btnRequest = document.getElementById("btn-request");

        const cardRestock = document.getElementById("card-restock");
        const cardRequest = document.getElementById("card-request");

        btnRestock.addEventListener("click", function() {
            cardRestock.style.display = "block";
            cardRequest.style.display = "none";
        });

        btnRequest.addEventListener("click", function() {
            cardRequest.style.display = "block";
            cardRestock.style.display = "none";
        });

    });
</script>
