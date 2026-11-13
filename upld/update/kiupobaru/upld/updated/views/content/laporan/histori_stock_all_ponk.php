<div class="content-wrapper">
    <div class="content-header">
        <div class="container-fluid">
            <h1 class="m-0">Laporan Transaksi All Barang Non Komersil</h1>
        </div>
    </div>

    <section class="content">
        <div class="container-fluid">
            <div class="card">
                <div class="card-body">
                    <form id="formFilter">
                        <div class="row">
                            <div class="col-sm-6">
                                <div class="form-group">
                                    <label>Tanggal Start :</label>
                                    <input type="date" class="form-control" name="tglstart" id="tglstart">
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <div class="form-group">
                                    <label>Tanggal End :</label>
                                    <input type="date" class="form-control" name="tglend" id="tglend">
                                </div>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary btn-block">
                            <i class="fas fa-search"></i> Cari Data
                        </button>
                    </form>
                </div>
            </div>

            <div class="card mt-3">
                <div class="card-header">
                    <h4>Data Transaksi</h4>
                </div>
                <div class="card-body">
                    <a id="btnExportExcel" class="btn btn-success ml-2" href="#">
                        Export Excel
                    </a>
                    <div class="col mt-2">

                    </div>
                    <table id="tabel-stock" class="table table-bordered table-striped">
                        <thead>
                            <tr class="bg-secondary text-white">
                                <th>#</th>
                                <th>Tanggal Transaksi</th>
                                <th>Departemen</th>
                                <th>Nama Barang</th>
                                <th>Keterangan</th>
                                <th>Qty</th>
                                <th>Jenis Transaksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- kosong saat load awal -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </section>
</div>