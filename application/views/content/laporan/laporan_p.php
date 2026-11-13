<div class="content-wrapper">
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0">Laporan Pembelian</h1>
                </div><!-- /.col -->
            </div><!-- /.row -->
        </div><!-- /.container-fluid -->
    </div>
    <!-- /.content-header -->

    <section class="content">
        <div class="container-fluid">

            <div class="card">
                <div class="card-body">
                    <?php echo form_open_multipart('srclapbeli'); ?>
                    <div class="row">
                        <div class="col-sm-6">
                            <!-- text input -->
                            <div class="form-group">
                                <label>Tanggal Start :</label>
                                <input type="date" class="form-control" placeholder="Tanggal Transaksi" value="" name="tglstart" id="tglstart">
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="form-group">
                                <label>Tanggal End :</label>
                                <input type="date" class="form-control" placeholder="Tanggal Transaksi" value="" name="tglend" id="tglend">
                            </div>
                        </div>
                    </div>
                    <div>
                        <button type="submit" class="btn btn-primary btn-block">
                            <i class="fas fa-search"></i>
                            Cari Data
                        </button>
                    </div>
                    </form>
                </div>
            </div>
            <div class="card">
                <div class="card-header">
                    <h4>Laporan Pembelian Tanggal : </h4>
                </div>
                <div class="card-body">
                    <div class="col mt-2">
                        <table class="table table-bordered">
                            <thead>
                                <tr class="bg-gray color-palette">
                                    <td>NOPO</td>
                                    <td>Tanggal Transaksi</td>
                                    <td>PIC</td>
                                    <td>Departemen</td>
                                    <td>Nama Barang</td>
                                    <td>Qty</td>
                                    <td>Harga Satuan</td>
                                    <td>Total Harga</td>
                                </tr>
                            </thead>
                        </table>
                    </div>
                </div>
            </div>



        </div>
    </section>
</div>