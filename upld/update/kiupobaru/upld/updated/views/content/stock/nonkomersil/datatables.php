<script>
    $(document).ready(function() {
        function toggleStockLoading(show) {
            var $table = $("#list_stocknonkomersil");
            if (!$table.length) return;
            var $wrapper = $table.closest(".dataTables_wrapper");
            if (!$wrapper.length) return;
            var $overlay = $wrapper.find(".dt-loading-overlay");
            if (!$overlay.length) {
                $wrapper.css("position", "relative");
                $overlay = $(
                    '<div class="dt-loading-overlay">' +
                    '  <div class="dt-loading-box">' +
                    '    <div class="dt-loading-spinner"></div>' +
                    '    <div class="dt-loading-text">Memuat data...</div>' +
                    '  </div>' +
                    '</div>'
                );
                $wrapper.append($overlay);
            }
            if (show) {
                $overlay.addClass("show");
            } else {
                $overlay.removeClass("show");
            }
        }

        $("#daterange").datepicker({
            dateFormat: "yy-mm-dd",
            changeMonth: true,
            changeYear: true
        });

        $("#filter").click(function() {
            var startDate = $("#start_date").val();
            var endDate = $("#end_date").val();

            if (startDate !== "" && endDate !== "") {
                $.ajax({
                    url: "your_controller/getDataByDate", // Sesuaikan dengan URL controller Anda
                    type: "POST",
                    data: {
                        start_date: startDate,
                        end_date: endDate
                    },
                    success: function(data) {
                        $("#result").html(data);
                    }
                });
            } else {
                alert("Silakan pilih rentang tanggal terlebih dahulu");
            }
        });

        if ($("#stocknk_filter_form").length && $("#list_stocknonkomersil").length) {
            var $stockTable = $("#list_stocknonkomersil");
            var isAdminView = $stockTable.data("admin-view") == 1;
            var ajaxUrl = $stockTable.data("ajax-url");
            var detailBaseUrl = "<?= base_url('detailtransaksi/'); ?>";
            var updateLokasiUrl = "<?= base_url('stocknonkomersil/update_lokasi'); ?>";
            var updateMinimumStockUrl = "<?= base_url('stocknonkomersil/update_minimum_stock'); ?>";

            var tableStock = $stockTable.DataTable({
                "responsive": true,
                "processing": true,
                "serverSide": true,
                "lengthChange": false,
                "autoWidth": false,
                "aaSorting": [],
                "ajax": {
                    "url": ajaxUrl,
                    "type": "GET",
                    "data": function(d) {
                        d.lokasi = $("#filter_lokasi").val();
                        d.status_stock = $("#filter_status_stock").val();
                    }
                },
                "columns": isAdminView ? [
                    {
                        "data": "kode_barang",
                        "render": function(data) {
                            return escHtml(data);
                        }
                    },
                    {
                        "data": "nama_barang",
                        "render": function(data) {
                            return escHtml(data);
                        }
                    },
                    {
                        "data": "deskripsi",
                        "render": function(data) {
                            return escHtml(data);
                        }
                    },
                    {
                        "data": null,
                        "render": function(_, __, row) {
                            return buildQtyCell(row.qty_ready, row.status_stock);
                        }
                    },
                    {
                        "data": "batch_lifo_aktif",
                        "render": function(data) { return escHtml(formatQty(data)); }
                    },
                    {
                        "data": "harga_lifo_aktif",
                        "render": function(data) { return data === null ? '-' : escHtml(formatCurrency(data)); }
                    },
                    {
                        "data": "nilai_lifo",
                        "render": function(data) { return escHtml(formatCurrency(data)); }
                    },
                    {
                        "data": null,
                        "render": function(_, __, row) { return buildLifoStatus(row.qty_lifo_perlu_harga); }
                    },
                    {
                        "data": "minimum_stock",
                        "render": function(data) {
                            return escHtml(formatQty(data));
                        }
                    },
                    {
                        "data": "qty_saran_po",
                        "render": function(data) {
                            return escHtml(formatQty(data));
                        }
                    },
                    {
                        "data": "status_stock",
                        "render": function(data) {
                            return buildStatusCell(data);
                        }
                    },
                    {
                        "data": "satuan",
                        "render": function(data) {
                            return escHtml(data);
                        }
                    },
                    {
                        "data": "nama_lokasi",
                        "render": function(data) {
                            return escHtml(data);
                        }
                    },
                    {
                        "data": null,
                        "orderable": false,
                        "searchable": false,
                        "render": function(_, __, row) {
                            return '<a href="' + detailBaseUrl + encodeURIComponent(row.kode_barangs) + '" id="btndetailbrs" class="btn btn-block btn-primary"><i class="fas fa-eye"></i></a>' +
                                '<button type="button" class="btn btn-block btn-warning btn-lokasi" data-toggle="modal" data-target="#modalUpdateLokasi" data-kode-barang="' + escHtml(row.kode_barang) + '" data-nama-barang="' + escHtml(row.nama_barang) + '" data-id-lokasi="' + escHtml(row.id_lokasi) + '"><i class="fas fa-map-marker-alt"></i></button>' +
                                '<button type="button" class="btn btn-block btn-info btn-minimum-stock" data-toggle="modal" data-target="#modalMinimumStock" data-kode-barang="' + escHtml(row.kode_barangs) + '" data-nama-barang="' + escHtml(row.nama_barang) + '" data-minimum-stock="' + escHtml(row.minimum_stock) + '"><i class="fas fa-boxes"></i></button>';
                        }
                    }
                ] : [
                    {
                        "data": "kode_barang",
                        "render": function(data) {
                            return escHtml(data);
                        }
                    },
                    {
                        "data": "nama_barang",
                        "render": function(data) {
                            return escHtml(data);
                        }
                    },
                    {
                        "data": "deskripsi",
                        "render": function(data) {
                            return escHtml(data);
                        }
                    },
                    {
                        "data": null,
                        "render": function(_, __, row) {
                            return buildQtyCell(row.qty_ready, row.status_stock);
                        }
                    },
                    {
                        "data": "minimum_stock",
                        "render": function(data) {
                            return escHtml(formatQty(data));
                        }
                    },
                    {
                        "data": "qty_saran_po",
                        "render": function(data) {
                            return escHtml(formatQty(data));
                        }
                    },
                    {
                        "data": "status_stock",
                        "render": function(data) {
                            return buildStatusCell(data);
                        }
                    },
                    {
                        "data": "satuan",
                        "render": function(data) {
                            return escHtml(data);
                        }
                    },
                    {
                        "data": "nama_lokasi",
                        "render": function(data) {
                            return escHtml(data);
                        }
                    }
                ],
                "language": {
                    "emptyTable": "Memuat data...",
                    "zeroRecords": "Memuat data...",
                    "processing": "Memuat data..."
                }
            });

            $stockTable.on("processing.dt", function(_, __, processing) {
                toggleStockLoading(processing);
                $("#btn_reload_stock").prop("disabled", processing).text(processing ? "Loading..." : "Reload Cepat");
            });

            function escHtml(text) {
                if (text === null || text === undefined) return "";
                return $("<div>").text(text).html();
            }

            function buildQtyCell(qty, status) {
                var qtyNum = parseFloat(qty || 0);
                if (qtyNum <= 0) {
                    return '<span class="stock-qty stock-qty-habis">' + escHtml(qty) + '</span>';
                }
                if (status === "hampir_habis") {
                    return '<span class="stock-qty stock-qty-hampir-habis">' + escHtml(qty) + '</span>';
                }
                return escHtml(qty);
            }

            function buildStatusCell(status) {
                if (status === "habis") {
                    return '<span class="badge badge-danger">Habis - Harus Di-PO</span>';
                }
                if (status === "hampir_habis") {
                    return '<span class="badge badge-warning">Hampir Habis - Harus Di-PO</span>';
                }
                return '<span class="badge badge-success">Aman</span>';
            }

            function formatQty(qty) {
                var value = parseFloat(qty || 0);
                return Number.isInteger(value) ? value.toString() : value.toFixed(2);
            }

            function formatCurrency(value) {
                return 'Rp ' + Math.round(parseFloat(value || 0)).toLocaleString('id-ID');
            }

            function buildLifoStatus(qtyPerluHarga) {
                return parseFloat(qtyPerluHarga || 0) > 0
                    ? '<span class="badge badge-danger">Perlu Harga</span>'
                    : '<span class="badge badge-success">Lengkap</span>';
            }

            function loadStockData() {
                tableStock.ajax.reload(null, true);
            }

            $("#filter_lokasi").on("change", loadStockData);
            $("#filter_status_stock").on("change", loadStockData);
            $("#btn_reload_stock").on("click", loadStockData);
            $("#btn_reset_filter").on("click", function() {
                $("#filter_lokasi").val("");
                $("#filter_status_stock").val("");
                loadStockData();
            });

            $(document).on("click", ".btn-minimum-stock", function() {
                $("#minimum_stock_kode_barang").val($(this).data("kode-barang"));
                $("#minimum_stock_nama_barang").text($(this).data("nama-barang") || "-");
                $("#minimum_stock_value").val($(this).data("minimum-stock") || 0);
            });

            $("#formMinimumStock").on("submit", function(e) {
                e.preventDefault();
                var $btn = $("#btn_save_minimum_stock");
                $btn.prop("disabled", true).text("Menyimpan...");

                $.ajax({
                    url: updateMinimumStockUrl,
                    type: "POST",
                    dataType: "json",
                    data: $(this).serialize(),
                    success: function(res) {
                        if (res && res.status) {
                            $("#modalMinimumStock").modal("hide");
                            tableStock.ajax.reload(null, false);
                        } else {
                            alert("Gagal update minimum stock.");
                        }
                    },
                    error: function() {
                        alert("Gagal update minimum stock.");
                    },
                    complete: function() {
                        $btn.prop("disabled", false).text("Simpan");
                    }
                });
            });

            $(document).on("click", ".btn-lokasi", function() {
                var kodeBarang = $(this).data("kode-barang");
                var namaBarang = $(this).data("nama-barang");
                var idLokasi = $(this).data("id-lokasi");

                $("#lokasi_kode_barang").val(kodeBarang);
                $("#lokasi_nama_barang").text(namaBarang || "-");
                $("#lokasi_id").val(idLokasi || "");
            });

            $("#formUpdateLokasi").on("submit", function(e) {
                e.preventDefault();
                var $btn = $("#btn_save_lokasi");
                $btn.prop("disabled", true).text("Menyimpan...");

                $.ajax({
                    url: updateLokasiUrl,
                    type: "POST",
                    dataType: "json",
                    data: $(this).serialize(),
                    success: function(res) {
                        if (res && res.status) {
                            $("#modalUpdateLokasi").modal("hide");
                            tableStock.ajax.reload(null, false);
                        } else {
                            alert("Gagal update lokasi.");
                        }
                    },
                    error: function() {
                        alert("Gagal update lokasi.");
                    },
                    complete: function() {
                        $btn.prop("disabled", false).text("Simpan");
                    }
                });
            });

        } else if ($("#list_stocknonkomersil").length) {
            $("#list_stocknonkomersil").DataTable({
                "responsive": true,
                "lengthChange": false,
                "autoWidth": false,
                "aaSorting": [],
            }).buttons().container().appendTo('#example1_wrapper .col-md-6:eq(0)');
        }

        $(function() {
            $("#list_stocknonkomersil1").DataTable({
                "responsive": true,
                "lengthChange": false,
                "autoWidth": false,
                "aaSorting": [],
            }).buttons().container().appendTo('#example1_wrapper .col-md-6:eq(0)');
        });
        $(function() {
            $("#tracking_list_nonkomersil").DataTable({
                "responsive": true,
                "lengthChange": false,
                "autoWidth": false,
                "aaSorting": [],
            }).buttons().container().appendTo('#example1_wrapper .col-md-6:eq(0)');
        });

    });
</script>

<?php $lokasi_modal = isset($lokasi_option_modal) ? $lokasi_option_modal : (isset($lokasi_option) ? $lokasi_option : []); ?>
<div class="modal fade" id="modalUpdateLokasi" tabindex="-1" role="dialog" aria-labelledby="modalUpdateLokasiLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form id="formUpdateLokasi">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalUpdateLokasiLabel">Update Lokasi Barang</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="kode_barang" id="lokasi_kode_barang">
                    <div class="form-group">
                        <label class="mb-1"><b>Barang</b></label>
                        <div id="lokasi_nama_barang">-</div>
                    </div>
                    <div class="form-group">
                        <label class="mb-1"><b>Pilih Lokasi</b></label>
                        <select name="id_lokasi" id="lokasi_id" class="form-control" required>
                            <option value="">Pilih Lokasi</option>
                            <?php foreach ($lokasi_modal as $l) : ?>
                                <option value="<?= $l->id_lokasi; ?>"><?= $l->nama_lokasi; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                    <button type="submit" id="btn_save_lokasi" class="btn btn-primary">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="modalMinimumStock" tabindex="-1" role="dialog" aria-labelledby="modalMinimumStockLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form id="formMinimumStock">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalMinimumStockLabel">Atur Minimum Stock</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="kode_barang" id="minimum_stock_kode_barang">
                    <div class="form-group">
                        <label class="mb-1"><b>Barang</b></label>
                        <div id="minimum_stock_nama_barang">-</div>
                    </div>
                    <div class="form-group">
                        <label for="minimum_stock_value"><b>Minimum Stock</b></label>
                        <input type="number" name="minimum_stock" id="minimum_stock_value" class="form-control" min="0" step="0.01" required>
                        <small class="form-text text-muted">Stok sama dengan atau di bawah nilai ini akan ditandai harus di-PO.</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                    <button type="submit" id="btn_save_minimum_stock" class="btn btn-primary">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
    .stock-qty {
        display: block;
        padding: 0.25rem;
        border-radius: 0.2rem;
        font-weight: 700;
    }

    .stock-qty-habis {
        color: #ffffff;
        background: #dc3545;
    }

    .stock-qty-hampir-habis {
        color: #212529;
        background: #ffc107;
    }

    .dt-loading-overlay {
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        display: none;
        align-items: center;
        justify-content: center;
        background: rgba(255, 255, 255, 0.7);
        z-index: 10;
    }

    .dt-loading-overlay.show {
        display: flex;
    }

    .dt-loading-box {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 10px 14px;
        border-radius: 8px;
        background: #ffffff;
        border: 1px solid #e2e2e2;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
    }

    .dt-loading-spinner {
        width: 20px;
        height: 20px;
        border-radius: 50%;
        border: 3px solid #d9d9d9;
        border-top-color: #007bff;
        animation: dtspin 0.9s linear infinite;
    }

    .dt-loading-text {
        font-weight: 600;
        color: #333333;
    }

    @keyframes dtspin {
        from {
            transform: rotate(0deg);
        }

        to {
            transform: rotate(360deg);
        }
    }
</style>
