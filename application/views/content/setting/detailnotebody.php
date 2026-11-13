<?php foreach ($note as $n) : ?>
    <div class="content-wrapper">
        <div class="content-header">
            <div class="container-fluid">
                <div class="row mb-2">
                    <div class="col-sm-6">
                        <div class="row">
                            <a href="<?= base_url('notetemplate') ?>">
                                <i class="fa fa-arrow-left  ml-4 mr-4 mt-2"></i>
                            </a>
                            <h1 class="m-0">Note Setting - <?= $n->nama_note ?></h1>
                        </div>
                    </div><!-- /.col -->
                </div><!-- /.row -->

                <?php $this->load->view('content/setting/modal/modalnotetemplate') ?>
                <section class="m-2">
                    <a class="btn btn-block btn-success btn-sm mb-2 mt-2 " data-toggle="modal" data-target="#modalAddItem<?= $n->id_nt_template ?>">
                        <i class="fas fa-check-double"></i>
                        Update Isi Note Template
                    </a>
                    <table class="table table-bordered">
                        <tr>
                            <td style="width: 18%;">Tujuan Penerima</td>
                            <td style="width: 2%;"> : </td>
                            <td><?= $n->shipment_to ?> </td>
                        </tr>
                        <tr>
                            <td style="width: 18%;">Alamat Tujuan</td>
                            <td style="width: 2%;"> : </td>
                            <td> <?= $n->alamat_ship ?></td>
                        </tr>
                        <tr>
                            <td style="width: 18%;">Koordinator Penerima</td>
                            <td style="width: 2%;"> : </td>
                            <td> <?= $n->cp_shipment ?></td>
                        </tr>
                        <tr>
                            <td style="width: 18%;">Telefon Penerima</td>
                            <td style="width: 2%;"> : </td>
                            <td> <?= $n->no_cp ?></td>
                        </tr>
                        <tr>
                            <td style="width: 18%;">Keterangan Lainya</td>
                            <td style="width: 2%;"> : </td>
                            <td> <?= nl2br($n->ket_1) ?></td>
                        </tr>
                    </table>
                </section>
                <h3>Tampilan Note Setting</h3>
                <table class="table table-bordered">
                    <tr>
                        <td style="text-align: justify; background-color: yellow;">
                            * SHIP TO : <br>
                            KARISMA INDOAGRO UNIVERSAL <br>
                            &nbsp;&nbsp;&nbsp;<?= $n->shipment_to ?><br>
                            &nbsp;&nbsp;&nbsp;<?= $n->alamat_ship ?> <br>
                            * Sebelum kirim barang mohon konfirmasi terlebih dahulu <br>
                            &nbsp;&nbsp;&nbsp;<?= $n->cp_shipment ?> <br>
                            &nbsp;&nbsp;&nbsp;<?= $n->no_cp ?> <br>
                            <?= nl2br($n->ket_1) ?><br>
                        </td>
                    </tr>
                </table>
            </div><!-- /.container-fluid -->
        </div>
        <!-- /.content-header -->
    </div>
<?php endforeach; ?>