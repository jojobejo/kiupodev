<div class="content-wrapper">
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">

                </div><!-- /.col -->
            </div><!-- /.row -->
        </div><!-- /.container-fluid -->
        <?php foreach ($stockdet as $s) : ?>
            <div class="card">
                <div class="card-header">
                    <h1>Revisi Barang Persedian - </h1>
                </div>
                <div class="card-body">
                    <div class="form-group" hidden>
                        <label for="#">Kode Barang</label>
                        <input type="text" class="form-control" id="kdbarang_isi" name="kdbarang_isi" value="<?= $s->kd_po ?>" readonly >
                        <input type="text" class="form-control" id="id_isi_tr" name="id_isi_tr" value="<?= $s->id_tr ?>" readonly>
                        <input type="text" class="form-control" id="id_isi_po" name="id_isi_po" value="<?= $s->id_po ?>" readonly>
                    </div>
                    <div class="form-group">
                        <label for="#">Nama Barang</label>
                        <input type="text" class="form-control" id="nm_barang" name="nm_barang" value="<?= $s->nm_barang ?>" readonly>
                    </div>
                    <div class="form-group">
                        <label for="#">QTY</label>
                        <input type="text" class="form-control" id="qty_tr" name="qty_tr" value="<?= $s->qty_tr ?>">
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
    <!-- /.content-header -->
</div>