<div class="content-wrapper">
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2 ml-2">
                <div class="col-sm-0">

                </div><!-- /.col -->
            </div><!-- /.row -->

            <!-- VIEW ADMIN PURCHASING -->
            <?php if ($this->session->userdata('lv') == '2' || $this->session->userdata('lv') == '1') : ?>
                <div class="card">
                    <div class="card-body">
                        <h1 class="m-0">
                            <b style="text-transform:uppercase">List Stock Barang - stock controller</b>
                        </h1>

                        <a href="<?= base_url('nkrestok') ?>" class="btn btn-md mt-2 mb-2 btn-info"><b style="text-transform:uppercase">List Stock Barang Kosong</b></a>
                        <a href="<?= base_url('exported_allstock') ?>" class="btn btn-md mt-2 mb-2 btn-info"><i class="fas fa-file-alt"></i> &nbsp;<b style="text-transform:uppercase">Export Stock</b></a>
                        <a href="<?= base_url('master_lokasi') ?>" class="btn btn-md mt-2 mb-2 btn-info"><i class="fas fa-file-alt"></i> &nbsp;<b style="text-transform:uppercase">Master Lokasi</b></a>
                        <a href="<?= base_url('tr_allstock') ?>" class="btn btn-md mt-2 mb-2 btn-info"><i class="fas fa-archive"></i> &nbsp;<b style="text-transform:uppercase">Histori All Stock</b></a>
                        <a href="<?= base_url('stockopnamenk') ?>" class="btn btn-md mt-2 mb-2 btn-warning"><i class="fas fa-clipboard-list"></i> &nbsp;<b style="text-transform:uppercase">Stock Opname</b></a>

                        <form id="stocknk_filter_form" class="row mt-3 mb-2">
                            <div class="col-md-4">
                                <label class="mb-1"><b>Filter Lokasi</b></label>
                                <select id="filter_lokasi" name="lokasi" class="form-control">
                                    <option value="">Semua Lokasi</option>
                                    <?php foreach ($lokasi_option as $l) : ?>
                                        <option value="<?= $l->nama_lokasi; ?>" <?= ($selected_lokasi == $l->nama_lokasi) ? 'selected' : ''; ?>>
                                            <?= $l->nama_lokasi; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="mb-1"><b>Status Stock</b></label>
                                <select id="filter_status_stock" class="form-control">
                                    <option value="">Semua Status</option>
                                    <option value="perlu_po">Harus Di-PO</option>
                                    <option value="hampir_habis">Hampir Habis</option>
                                    <option value="habis">Habis</option>
                                    <option value="aman">Aman</option>
                                </select>
                            </div>
                            <div class="col-md-5 d-flex align-items-end">
                                <button type="button" id="btn_reload_stock" class="btn btn-secondary mr-2">Reload Cepat</button>
                                <button type="button" id="btn_reset_filter" class="btn btn-light">Reset</button>
                            </div>
                        </form>

                        <table class="table table-bordered" id="list_stocknonkomersil" data-admin-view="1" data-ajax-url="<?= base_url('stocknonkomersil/data') ?>">
                            <thead>
                                <tr>
                                    <td>Kode Barang</td>
                                    <td>Nama Barang</td>
                                    <td>Deskripsi</td>
                                    <td>Stock</td>
                                    <td>Batch LIFO</td>
                                    <td>Harga LIFO Aktif</td>
                                    <td>Nilai Stok LIFO</td>
                                    <td>Status Harga</td>
                                    <td>Minimum Stock</td>
                                    <td>Saran PO</td>
                                    <td>Status</td>
                                    <td>Satuan</td>
                                    <td>Lokasi</td>
                                    <td>#</td>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>

            <?php elseif ($this->session->userdata('lv') != '2' || $this->session->userdata('lv') != '1') : ?>
                <?php $this->load->view('content/stock/nonkomersil/modal/modal_add_itm.php') ?>
                <div class="card">
                    <div class="card-body">
                        <h1 class="m-0">
                            <b style="text-transform:uppercase">Stock Barang Tersedia</b>
                        </h1>
                        <form id="stocknk_filter_form" class="row mt-3 mb-2">
                            <div class="col-md-4">
                                <label class="mb-1"><b>Filter Lokasi</b></label>
                                <select id="filter_lokasi" name="lokasi" class="form-control">
                                    <option value="">Semua Lokasi</option>
                                    <?php foreach ($lokasi_option as $l) : ?>
                                        <option value="<?= $l->nama_lokasi; ?>" <?= ($selected_lokasi == $l->nama_lokasi) ? 'selected' : ''; ?>>
                                            <?= $l->nama_lokasi; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="mb-1"><b>Status Stock</b></label>
                                <select id="filter_status_stock" class="form-control">
                                    <option value="">Semua Status</option>
                                    <option value="perlu_po">Harus Di-PO</option>
                                    <option value="hampir_habis">Hampir Habis</option>
                                    <option value="habis">Habis</option>
                                    <option value="aman">Aman</option>
                                </select>
                            </div>
                            <div class="col-md-5 d-flex align-items-end">
                                <button type="button" id="btn_reload_stock" class="btn btn-secondary mr-2">Reload</button>
                                <button type="button" id="btn_reset_filter" class="btn btn-light">Reset</button>
                            </div>
                        </form>
                        <table class="table table-bordered" id="list_stocknonkomersil" data-admin-view="0" data-ajax-url="<?= base_url('stocknonkomersil/data') ?>">
                            <thead>
                                <tr>
                                    <td>Kode Barang</td>
                                    <td>Nama Barang</td>
                                    <td>Deskripsi</td>
                                    <td>Stock</td>
                                    <td>Minimum Stock</td>
                                    <td>Saran PO</td>
                                    <td>Status</td>
                                    <td>Satuan</td>
                                    <td>Lokasi</td>
                                    <!-- <td>#</td> -->
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>
            <?php endif; ?>
        </div><!-- /.container-fluid -->
    </div>

    <!-- /.content-header -->
</div>
