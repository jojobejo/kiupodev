<div class="content-wrapper">
    <div class="content-header">
        <div class="container-fluid">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-boxes"></i> Data Stock per Gudang
                    </h5>
                </div>
                <div class="card-body">
                    <!-- Filter -->
                    <div id="filter-section">
                        <div class="form-row align-items-end">
                            <div class="col-md-3">
                                <label for="filter_gudang"><strong>Filter Gudang</strong></label>
                                <select id="filter_gudang" class="form-control form-control-sm">
                                    <option value="all">-- Semua Gudang --</option>
                                    <option value="2">Gdg. Induk</option>
                                    <option value="3">Gdg. Cabang</option>
                                    <!-- Tambahkan opsi gudang sesuai data Anda -->
                                </select>
                            </div>
                            <div class="col-md-2">
                                <button id="btn-filter" class="btn btn-primary btn-sm">
                                    <i class="fas fa-search"></i> Tampilkan
                                </button>
                                <button id="btn-reset" class="btn btn-secondary btn-sm ml-1">
                                    <i class="fas fa-redo"></i> Reset
                                </button>
                            </div>
                            <div class="col-md-5 ml-auto text-right">
                                <small id="info-api" class="text-muted"></small>
                            </div>
                        </div>
                    </div>

                    <!-- Tabel -->
                    <div class="table-responsive">
                        <table id="table-stock" class="table table-bordered table-hover table-sm" style="width: 100%">
                            <thead class="thead-dark">
                                <tr>
                                    <th>#</th>
                                    <th>Kode Barang</th>
                                    <th>Nama Barang</th>
                                    <th>Exp. Date</th>
                                    <th>Gudang</th>
                                    <th class="text-center">QTY</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap4.min.js"></script>
<script src="https://kit.fontawesome.com/a076d05399.js" crossorigin="anonymous"></script>

<script>
    $(function() {

        // ── Inisialisasi DataTables ──────────────────────────────────────
        var table = $('#table-stock').DataTable({
            data: [],
            columns: [{
                    data: null,
                    render: function(d, t, r, m) {
                        return m.row + 1;
                    },
                    orderable: false
                },
                {
                    data: 'kode_barang'
                },
                {
                    data: 'nama_barang'
                },
                {
                    data: 'exp_date'
                },
                {
                    data: 'nm_gudang'
                },
                {
                    data: 'qty',
                    className: 'text-center',
                    render: function(d) {
                        var cls = parseInt(d) > 10 ? 'success' : (parseInt(d) > 0 ? 'warning' : 'danger');
                        return '<span class="badge badge-' + cls + ' badge-qty">' + d + '</span>';
                    }
                }
            ],
            order: [
                [2, 'asc']
            ],
            pageLength: 25,
            lengthMenu: [
                [10, 25, 50, 100, -1],
                [10, 25, 50, 100, 'Semua']
            ],
            language: {
                url: 'https://cdn.datatables.net/plug-ins/1.13.6/i18n/id.json'
            }
        });

        // ── Fungsi Load Data dari API ────────────────────────────────────
        function loadData(gudang) {
            $('#info-api').text('Memuat data...');
            $('#btn-filter').prop('disabled', true);

            $.ajax({
                url: '<?= base_url('api/stockkomersil') ?>',
                type: 'GET',
                data: {
                    gudang: gudang
                },
                dataType: 'json',
                success: function(res) {
                    table.clear();

                    if (res.status && res.data.length > 0) {
                        table.rows.add(res.data).draw();
                        $('#info-api').html(
                            '<span class="text-success"><i class="fas fa-check-circle"></i> ' +
                            res.data.length + ' data berhasil dimuat.</span>'
                        );
                    } else {
                        $('#info-api').html(
                            '<span class="text-warning"><i class="fas fa-exclamation-circle"></i> ' +
                            (res.message || 'Data tidak ditemukan.') + '</span>'
                        );
                    }
                },
                error: function(xhr) {
                    $('#info-api').html('<span class="text-danger"><i class="fas fa-times-circle"></i> Gagal menghubungi API.</span>');
                    console.error('AJAX Error:', xhr.responseText);
                },
                complete: function() {
                    $('#btn-filter').prop('disabled', false);
                }
            });
        }

        // ── Event: Tombol Filter ─────────────────────────────────────────
        $('#btn-filter').on('click', function() {
            loadData($('#filter_gudang').val());
        });

        // ── Event: Tombol Reset ──────────────────────────────────────────
        $('#btn-reset').on('click', function() {
            $('#filter_gudang').val('all');
            loadData('all');
        });

        // ── Load data saat halaman pertama dibuka ────────────────────────
        loadData('all');

    });
</script>