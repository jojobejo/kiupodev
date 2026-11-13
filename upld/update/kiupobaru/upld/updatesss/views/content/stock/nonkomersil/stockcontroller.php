<div class="content-wrapper">
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0">
                    </h1>
                </div><!-- /.col -->
            </div><!-- /.row -->

            <!-- VIEW ADMIN PURCHASING -->
            <?php if ($this->session->userdata('lv') == '2' || $this->session->userdata('lv') == '1') : ?>

                <table class="table table-bordered table-striped" id="list_stocknonkomersil">
                    <thead>
                        <tr>
                            <td>Kode Faktur</td>
                            <td>Kode Barang</td>
                            <td>Nama Barang</td>
                            <td>QTY</td>
                            <td>Satuan</td>
                            <td>Tanggal Transaksi</td>
                            <td>Aksi</td>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                        </tr>
                    </tbody>
                </table>
            <?php elseif ($this->session->userdata('lv') != '2' || $this->session->userdata('lv') != '1') : ?>
            <?php endif; ?>
        </div><!-- /.container-fluid -->
    </div>

    <!-- /.content-header -->
</div>