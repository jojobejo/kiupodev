<a href="" class="btn btn-sm btn-success"><i class="fas fa-check"></i></a>
<div class="content-wrapper">
    <div class="content-header">
        <div class="container-fluid">
            <div class="card">
                <div class="card-body">
                    <h3>List Request Status</h3>
                    <?php if ($countreq == '0') : ?>
                        <a href="<?= base_url('list_barang_ready_cp') ?>" class="btn btn-sm btn-primary mb-2"><i class="fas fa-plus"></i>&nbsp; New Request </a>
                        <a href="<?= base_url('historireqpic') ?>" class="btn btn-sm btn-success mb-2"><i class="fas fa-check"></i>&nbsp;REQ-DONE</a>
                        <a href="<?= base_url('requestpendings') ?>" class="btn btn-sm btn-warning mb-2"><i class="fas fa-pause"></i>&nbsp; REQUEST PENDING</a>
                    <?php else : ?>
                        <a href="<?= base_url('historireqpic') ?>" class="btn btn-sm btn-success mb-2"><i class="fas fa-check"></i>&nbsp;REQ-DONE</a>
                    <?php endif; ?>
                    <table class="table table-bordered table-striped" id="list_reqpic">

                        <thead>
                            <tr>
                                <td>Tanggal Request</td>
                                <td>Jam</td>
                                <td>Total Barang</td>
                                <td>Keterangan</td>
                                <td>Status</td>
                                <td>#</td>
                            </tr>
                        </thead>

                        <tbody>
                            <?php foreach ($getallreq as $g) : ?>
                                <tr>
                                    <td><?= format_tgl_lahir($g->tgl_transaksi) ?></td>
                                    <td><?= format_jam_24($g->create_at) ?></td>
                                    <td><?= $g->jml_item ?></td>
                                    <td><?= $g->tj_pembelian ?></td>
                                    <td>
                                        <div class="row">
                                            <?php if ($g->status == 'ON PROGRESS') : ?>
                                                <div class="col">
                                                    <a class="btn btn-block btn-secondary btn-sm"><?= $g->status ?></a>
                                                </div>
                                            <?php elseif ($g->status == 'REQUEST ACC') : ?>
                                                <div class="col">
                                                    <a class="btn btn-block btn-warning btn-sm"><?= $g->status ?></a>
                                                </div>
                                            <?php elseif ($g->status == 'BARANG TERSEDIA') : ?>
                                                <div class="col">
                                                    <a class="btn btn-block btn-info btn-sm"><?= $g->status ?></a>
                                                </div>
                                            <?php elseif ($g->status == 'DONE') : ?>
                                                <div class="col">
                                                    <a class="btn btn-block btn-success btn-sm"><?= $g->status ?></a>
                                                </div>
                                            <?php elseif ($g->status == 'PO REVISI') : ?>
                                                <div class="col">
                                                    <a class="btn btn-block btn-warning btn-sm"><b><?= $g->status ?></b></a>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="row">
                                            <div class="col">
                                                <a class="btn btn-block btn-primary btn-sm" href="<?= base_url('reqpic/detreqbarangpic/' . $g->kd_po_nk) ?>">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                            </div>
                                            <div class="col">
                                                <a class="btn btn-block btn-danger btn-sm" href="<?= base_url('reqpic/requestpending/' . $g->kd_po_nk) ?>">
                                                    <i class="fas fa-times-circle"></i>
                                                </a>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <!-- END CARD CONTENT -->
            </div>

            <?php if ($countreq == '1') : ?>
                <?php $this->load->view('content/po/Reqpic/modalreqedit.php') ?>
                <div class="card">
                    <div class="card-body">
                        <h3>Draft Request Barang</h3>
                        <div class="row mb-2">
                            <div class="col">
                                <a href="<?= base_url('list_barang_ready_cp') ?>" class="btn btn-sm btn-primary"><i class="fas fa-plus"></i>&nbsp; Tambah Barang </a>
                            </div>
                        </div>

                        <?php echo form_open_multipart('add_promosi_cp/' . $this->session->userdata('kode')); ?>
                        <div class="col mb-2 mt-5">
                            <div class="row">
                                <div class="col-md-auto">
                                    <label for="naSupp" class="">Tujuan Request : </label>
                                </div>
                                <div class="col-md">
                                    <input type="text" id="intj" name="intj" style="max-width: 550px;" value="" class="form-control" placeholder="Input Tujuan Pengajuan">
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
                    <!-- END CARD CONTENT -->
                </div>
            <?php else : ?>
            <?php endif; ?>
            <!-- END CONTAINER CONTENT -->
        </div>
    </div>
    <!-- END VIEW CONTENT -->
</div>
