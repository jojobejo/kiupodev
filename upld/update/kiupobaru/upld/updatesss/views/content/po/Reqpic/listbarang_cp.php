<div class="content-wrapper">
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">

                    <?php if ($this->session->userdata('lv') == '2') : ?>
                        <a href="<?= base_url('promosi_cp') ?>" class="btn btn-success btn-block"><i class="fas fa-home"></i> BACK</a>
                    <?php else : ?>
                        <a href="<?= base_url('reqpic') ?>" class="btn btn-success btn-block"><i class="fas fa-home"></i> BACK</a>
                    <?php endif; ?>

                </div><!-- /.col -->
                <div class="col-sm-6">
                    <a href="#" class="btn btn-primary btn-block" data-toggle="modal" data-target="#reqmasterbarang"><i class="fas fa-folder-plus">&nbsp;</i>Req Master Barang</a>
                </div><!-- /.col -->
            </div><!-- /.row -->
            <!-- END CONTAINER CONTENT -->

            <?php foreach ($lstock as $l) : ?>
                <div class="modal fade" id="addreq<?= $l->kode_sys ?>">
                    <div class="modal-dialog modal-lg">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h4 class="modal-title">Add Item Request - <span style="text-transform:uppercase"><b><?= $l->nama_barang ?></b></span></h4>
                                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                    <span aria-hidden="true">&times;</span>
                                </button>
                            </div>
                            <div class="modal-body">
                                <?php echo form_open_multipart('addtmpreqbarang_cp'); ?>
                                <div class="form-group">
                                    <div class="row" hidden>
                                        <div class="col-sm-8">
                                            <input class="form-control" type="text" id="kdbys" name="kdbys" value="<?= $l->kode_sys ?>" readonly />
                                            <input class="form-control" type="text" id="kdbr" name="kdbr" value="<?= $l->kode_adm ?>" readonly />
                                            <input class="form-control" type="text" id="idsat" name="idsat" value="<?= $l->id_satuan ?>" readonly />
                                            <input class="form-control" type="text" id="katbr" name="katbr" value="<?= $l->kat_barang ?>" readonly />
                                            <input class="form-control" type="text" id="nm_barang" name="nm_barang" value="<?= $l->nama_barang ?>" readonly />
                                            <input class="form-control" type="text" id="descnk_isi" name="descnk_isi" value="<?= $l->descnk ?>" readonly />
                                        </div>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <div class="row">
                                        <label class="col-sm-3 control-label text-right" for="kd_user">Keterangan<span class="required">*</span></label>
                                        <div class="col-sm-8"><input class="form-control" type="text" id="ket_isi" name="ket_isi" value="" placeholder="Inputkan keterangan kebutuhan" /></div>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <div class="row">
                                        <label class="col-sm-3 control-label text-right" for="kd_user">QTY<span class="required">*</span></label>
                                        <div class="col-sm-8"><input class="form-control" type="number" id="qty_isi" name="qty_isi" value="" placeholder="Inputkan jumlah kebutuhan" /></div>
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
                                        <a href="#" class="btn btn-block btn-success btn-sm " data-toggle="modal" data-target="#addreq<?= $l->kode_sys ?>">
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