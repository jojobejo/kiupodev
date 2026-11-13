<script>
    $(function() {
        if ($("#list_mbarangkomersil").length) {
            var $tableKomersil = $("#list_mbarangkomersil");
            var isBrowserLayout = $("#detail_barang_komersil").length > 0 && $("#mbk_detail_form").length > 0;
            var tableKomersil = $tableKomersil.DataTable({
                "responsive": true,
                "processing": true,
                "serverSide": true,
                "lengthChange": !isBrowserLayout,
                "autoWidth": false,
                "pageLength": isBrowserLayout ? 8 : 10,
                "aaSorting": [],
                "ajax": {
                    "url": $tableKomersil.data("ajax-url"),
                    "type": "GET",
                    "dataSrc": function(json) {
                        $("#mbk_list_count").text((json.recordsFiltered || 0) + " data");
                        return json.data || [];
                    }
                },
                "columns": isBrowserLayout ? [{
                    "data": null,
                    "orderable": false,
                    "render": function(_, __, row) {
                        return renderBarangCard(row);
                    }
                }] : [{
                    "data": "kode_barang",
                    "render": escHtml
                },
                {
                    "data": "nama_barang",
                    "render": escHtml
                },
                {
                    "data": "bahan_aktif",
                    "render": escHtml
                },
                {
                    "data": "nm_satuan",
                    "render": escHtml
                },
                {
                    "data": "nama_suplier",
                    "render": escHtml
                },
                {
                    "data": "panjang",
                    "render": formatNumber
                },
                {
                    "data": "lebar",
                    "render": formatNumber
                },
                {
                    "data": "tinggi",
                    "render": formatNumber
                },
                {
                    "data": "stock_minimum",
                    "render": formatNumber
                },
                {
                    "data": null,
                    "orderable": false,
                    "searchable": false,
                    "render": function(_, __, row) {
                        var id = escHtml(row.id_barang);
                        return '<a class="btn btn-info btn-sm" href="' + $tableKomersil.data("detail-url") + id + '" title="Detail"><i class="fas fa-eye"></i></a>';
                    }
                }],
                "createdRow": function(row, data) {
                    $(row).attr("data-id", data.id_barang);
                },
                "drawCallback": function() {
                    if (!isBrowserLayout) return;
                    var $selected = $tableKomersil.find("tbody tr.selected");
                    if (!$selected.length) {
                        var $first = $tableKomersil.find("tbody tr").first();
                        if ($first.length && $first.data("id")) {
                            selectBarangRow($first);
                        }
                    }
                },
                "language": {
                    "processing": "Memuat data...",
                    "emptyTable": "Belum ada data barang komersil.",
                    "zeroRecords": "Data tidak ditemukan."
                }
            });

            if (isBrowserLayout) {
                $tableKomersil.on("click", "tbody tr", function() {
                    selectBarangRow($(this));
                });
            }

            function escHtml(text) {
                if (text === null || text === undefined) return "";
                return $("<div>").text(text).html();
            }

            function productImageUrl(fileName) {
                var value = $.trim(String(fileName || ""));
                if (!value || value === "Karisma.png") {
                    return $tableKomersil.data("placeholder-image") || "";
                }

                if (/^(https?:)?\/\//i.test(value) || value.indexOf("/") === 0) {
                    return value;
                }

                return ($tableKomersil.data("image-base") || "") + encodeURIComponent(value);
            }

            function renderBarangCard(row) {
                var imageUrl = productImageUrl(row.gbr_barang);
                var imageHtml = imageUrl
                    ? '<img src="' + escHtml(imageUrl) + '" alt="' + escHtml(row.nama_barang || "Gambar barang") + '">'
                    : '<i class="fas fa-box-open"></i>';

                return '<div class="mbk-item">' +
                    '<div class="mbk-item-image">' + imageHtml + '</div>' +
                    '<div class="mbk-item-body">' +
                    '<div class="mbk-item-code">' + escHtml(row.kode_barang || "-") + '</div>' +
                    '<div class="mbk-item-name">' + escHtml(row.nama_barang || "-") + '</div>' +
                    '<div class="mbk-item-supplier">' + escHtml(row.nama_suplier || row.kd_suplier || "-") + '</div>' +
                    '</div>' +
                    '</div>';
            }

            function selectBarangRow($row) {
                var data = tableKomersil.row($row).data();
                if (!data || !data.id_barang) return;

                $tableKomersil.find("tbody tr").removeClass("selected");
                $row.addClass("selected");
                $("#detail_barang_komersil").data("id", data.id_barang);
                if (typeof window.loadBarangKomersilDetail === "function") {
                    window.loadBarangKomersilDetail(data.id_barang);
                }
            }

            function formatNumber(value) {
                var number = parseFloat(value || 0);
                return Number.isInteger(number) ? number.toString() : number.toFixed(2);
            }
        }

        if ($("#detail_barang_komersil").length) {
            var $detailKomersil = $("#detail_barang_komersil");

            function escHtml(text) {
                if (text === null || text === undefined) return "";
                return $("<div>").text(text).html();
            }

            function showAlert(type, title, message) {
                if (typeof Swal !== "undefined") {
                    Swal.fire({
                        icon: type,
                        title: title,
                        text: message,
                        timer: type === "success" ? 1600 : undefined,
                        showConfirmButton: type !== "success"
                    });
                    return;
                }

                alert(message);
            }

            function detailImageUrl(fileName) {
                var $table = $("#list_mbarangkomersil");
                var value = $.trim(String(fileName || ""));
                if (!value || value === "Karisma.png") {
                    return $table.data("placeholder-image") || "";
                }

                if (/^(https?:)?\/\//i.test(value) || value.indexOf("/") === 0) {
                    return value;
                }

                return ($table.data("image-base") || "") + encodeURIComponent(value);
            }

            function updateProductImage(data) {
                var $imageBox = $("#mbk_product_image");
                if (!$imageBox.length) return;

                var imageUrl = detailImageUrl(data.gbr_barang);
                if (!imageUrl) {
                    $imageBox.text("Belum ada gambar barang");
                    return;
                }

                $imageBox.html('<img src="' + escHtml(imageUrl) + '" alt="' + escHtml(data.nama_barang || "Gambar barang") + '">');
            }

            function showDetailForm() {
                $("#mbk_detail_empty").hide();
                $("#mbk_detail_form").show();
            }

            function loadBarangKomersil(callback, id) {
                var selectedId = id || $detailKomersil.data("id");
                if (!selectedId) return;

                $.ajax({
                    url: $detailKomersil.data("get-url") + selectedId,
                    method: "GET",
                    dataType: "json",
                    success: function(response) {
                        if (response && response.status) {
                            $detailKomersil.data("id", response.data.id_barang);
                            callback(response.data);
                            return;
                        }

                        showAlert("error", "Gagal", response && response.message ? response.message : "Data tidak dapat dibuka.");
                    },
                    error: function(xhr) {
                        var response = xhr.responseJSON || {};
                        showAlert("error", "Gagal", response.message || "Data tidak dapat dibuka.");
                    }
                });
            }

            window.loadBarangKomersilDetail = function(id) {
                loadBarangKomersil(function(data) {
                    showDetailForm();
                    updateDetailView(data);
                }, id);
            };

            function setDetailField(field, value) {
                $('[data-field="' + field + '"]').each(function() {
                    var $field = $(this);
                    if ($field.is(":checkbox")) {
                        $field.prop("checked", String(value || "") === String($field.data("checked-value") || "T"));
                        return;
                    }

                    if ($field.is("input, textarea, select")) {
                        if ($field.is("select") && value !== null && value !== undefined && $field.find("option").filter(function() {
                            return $(this).val() === String(value);
                        }).length === 0) {
                            $field.html('<option value="' + escHtml(value) + '">' + escHtml(value) + '</option>');
                        }
                        $field.val(value);
                        return;
                    }

                    $field.text(value);
                });
            }

            function updateDetailView(data) {
                setDetailField("kode_barang", data.kode_barang || "");
                setDetailField("nama_barang", data.nama_barang || "");
                setDetailField("bahan_aktif", data.bahan_aktif || "");
                setDetailField("satuan", data.nm_satuan || data.satuan || "");
                setDetailField("merk_barang", data.merk_barang || "");
                setDetailField("stock_minimum", data.stock_minimum || 0);
                setDetailField("panjang", data.panjang || 0);
                setDetailField("lebar", data.lebar || 0);
                setDetailField("tinggi", data.tinggi || 0);
                setDetailField("berat", data.berat || 0);
                setDetailField("isi", data.isi || 0);
                setDetailField("kemasan", data.kemasan || 0);
                setDetailField("is_active_label", data.is_active === "F" ? "Nonaktif" : "Aktif");
                setDetailField("kelompok_barang", data.kelompok_barang || "");
                setDetailField("kategori_barang", data.kategori_barang || "");
                setDetailField("produk_fokus", data.produk_fokus || "");
                setDetailField("kd_suplier", data.kd_suplier || "");
                setDetailField("is_lot", data.is_lot || "F");
                setDetailField("is_active", data.is_active || "T");
                $("#detail_satuan_options").val(data.nm_satuan || data.satuan || "");
                updateProductImage(data);
            }

            function getDetailValue(field) {
                var $field = $('[data-field="' + field + '"]').first();
                if (!$field.length) return "";
                return $field.is(":checkbox") ? $field.prop("checked") : $field.val();
            }

            function collectDetailPayload() {
                var $satuan = $('[data-field="satuan"]').first();
                var $selectedSatuan = $satuan.find("option:selected");

                return {
                    id_barang: $detailKomersil.data("id"),
                    kode_barang: getDetailValue("kode_barang"),
                    nama_barang: getDetailValue("nama_barang"),
                    kd_suplier: getDetailValue("kd_suplier"),
                    bahan_aktif: getDetailValue("bahan_aktif"),
                    satuan: getDetailValue("satuan"),
                    satuan_qty: $selectedSatuan.data("id") || "",
                    merk_barang: getDetailValue("merk_barang"),
                    stock_minimum: getDetailValue("stock_minimum"),
                    panjang: getDetailValue("panjang"),
                    lebar: getDetailValue("lebar"),
                    tinggi: getDetailValue("tinggi"),
                    berat: getDetailValue("berat"),
                    isi: getDetailValue("isi"),
                    kemasan: getDetailValue("kemasan"),
                    is_active: getDetailValue("is_active") ? "F" : "T",
                    is_lot: getDetailValue("is_lot") ? "T" : "F",
                    kelompok_barang: getDetailValue("kelompok_barang"),
                    kategori_barang: getDetailValue("kategori_barang"),
                    produk_fokus: getDetailValue("produk_fokus")
                };
            }

            function saveDetailBarangKomersil() {
                var $button = $("#btn_edit_detail_komersil");
                $button.prop("disabled", true).text("Merekam...");

                $.ajax({
                    url: $detailKomersil.data("save-url"),
                    method: "POST",
                    dataType: "json",
                    data: collectDetailPayload(),
                    success: function(response) {
                        if (response && response.status) {
                            showAlert("success", "Berhasil", response.message || "Data berhasil disimpan.");
                            loadBarangKomersil(updateDetailView);
                            return;
                        }
                        showAlert("error", "Gagal", response && response.message ? response.message : "Data gagal disimpan.");
                    },
                    error: function(xhr) {
                        var response = xhr.responseJSON || {};
                        showAlert("error", "Gagal", response.message || "Data gagal disimpan.");
                    },
                    complete: function() {
                        $button.prop("disabled", false).text("Rekam");
                    }
                });
            }

            $("#btn_edit_detail_komersil").on("click", function() {
                saveDetailBarangKomersil();
            });

            $("#btn_cancel_detail_komersil").on("click", function() {
                loadBarangKomersil(function(data) {
                    showDetailForm();
                    updateDetailView(data);
                });
            });

            $("#btn_new_detail_komersil").on("click", function() {
                $detailKomersil.data("id", "");
                $("#list_mbarangkomersil tbody tr").removeClass("selected");
                showDetailForm();
                updateDetailView({
                    id_barang: "",
                    kode_barang: "",
                    nama_barang: "",
                    bahan_aktif: "",
                    satuan: "",
                    merk_barang: "",
                    stock_minimum: 0,
                    panjang: 0,
                    lebar: 0,
                    tinggi: 0,
                    berat: 0,
                    isi: 0,
                    kemasan: 0,
                    is_active: "T",
                    is_lot: "F",
                    kelompok_barang: "",
                    kategori_barang: "",
                    produk_fokus: "",
                    kd_suplier: "",
                    gbr_barang: ""
                });
            });

            $("#btn_delete_detail_komersil").on("click", function() {
                loadBarangKomersil(function(data) {
                    if (typeof Swal === "undefined") {
                        if (!confirm("Hapus " + (data.nama_barang || "barang ini") + "?")) {
                            return;
                        }

                        deleteBarangKomersil(data.id_barang);
                        return;
                    }

                    Swal.fire({
                        icon: "warning",
                        title: "Hapus Barang?",
                        text: "Data " + (data.nama_barang || "barang ini") + " akan dihapus.",
                        showCancelButton: true,
                        confirmButtonText: "Hapus",
                        cancelButtonText: "Batal",
                        confirmButtonColor: "#dc3545"
                    }).then(function(result) {
                        if (!result.isConfirmed) return;
                        deleteBarangKomersil(data.id_barang);
                    });
                });
            });

            function deleteBarangKomersil(id) {
                $.ajax({
                    url: $detailKomersil.data("delete-url"),
                    method: "POST",
                    dataType: "json",
                    data: {
                        id_barang: id
                    },
                    success: function(response) {
                        if (response && response.status) {
                            if (typeof Swal !== "undefined") {
                                Swal.fire("Berhasil", response.message || "Data berhasil dihapus.", "success").then(function() {
                                    window.location.href = $detailKomersil.data("list-url");
                                });
                                return;
                            }

                            window.location.href = $detailKomersil.data("list-url");
                            return;
                        }
                        showAlert("error", "Gagal", response && response.message ? response.message : "Data gagal dihapus.");
                    },
                    error: function(xhr) {
                        var response = xhr.responseJSON || {};
                        showAlert("error", "Gagal", response.message || "Data gagal dihapus.");
                    }
                });
            }
        }

        $("#list_mbarangnk").DataTable({
            "responsive": true,
            "lengthChange": false,
            "autoWidth": false,
            "aaSorting": [],
        }).buttons().container().appendTo('#list_mbarangnk_wrapper .col-md-6:eq(0)');

        $('#editbarang').on('show.bs.modal', function(event) {
            var button = $(event.relatedTarget);

            $('#edit_id_isi').val(button.attr('data-id'));
            $('#edit_kd_adm').val(button.attr('data-kd-adm'));
            $('#edit_skatbr').val(button.attr('data-kategori'));
            $('#edit_nmbarang').val(button.attr('data-nama'));
            $('#edit_descisi').val(button.attr('data-desc'));
            $('#edit_stuanbr').val(button.attr('data-satuan'));
            $('#edit_minimum_stock').val(button.attr('data-minimum-stock'));
        });

        $('#hapusbarang').on('show.bs.modal', function(event) {
            var button = $(event.relatedTarget);

            $('#delete_id_isi').val(button.attr('data-id'));
            $('#delete_barang_name').text(button.attr('data-nama'));
        });

        $('#uploadmbrang').on('show.bs.modal', function(event) {
            var button = $(event.relatedTarget);

            $('#upload_id_isi').val(button.attr('data-id'));
            $('#upload_file_nm').val(button.attr('data-file'));
            $('#upload_file_nms').val(button.attr('data-kd-adm'));
            $('#upload_gambar_1').val('');
            $('#uploadmbrang .custom-file-label').text('Choose file');
        });

        var kodeBarangCheckTimer = null;
        var kodeBarangIsUsed = false;
        var kodeBarangRequest = null;

        function setKodeBarangState(state, message) {
            var input = $('#add_kd_adm');
            var feedback = $('#add_kd_adm_feedback');
            var submitButton = $('#add_mbarang_submit');

            input.removeClass('is-invalid is-valid');
            feedback.removeClass('d-none text-danger text-success text-muted');

            if (state === 'used') {
                input.addClass('is-invalid');
                feedback.addClass('text-danger').text(message);
                submitButton.prop('disabled', true);
                kodeBarangIsUsed = true;
                return;
            }

            if (state === 'available') {
                input.addClass('is-valid');
                feedback.addClass('text-success').text(message);
                submitButton.prop('disabled', false);
                kodeBarangIsUsed = false;
                return;
            }

            if (state === 'checking') {
                feedback.addClass('text-muted').text(message);
                submitButton.prop('disabled', true);
                kodeBarangIsUsed = false;
                return;
            }

            feedback.addClass('d-none').text('');
            submitButton.prop('disabled', false);
            kodeBarangIsUsed = false;
        }

        $('#add_kd_adm').on('input', function() {
            var input = $(this);
            var kodeBarang = $.trim(input.val());
            var checkUrl = input.attr('data-check-url');

            clearTimeout(kodeBarangCheckTimer);

            if (kodeBarangRequest) {
                kodeBarangRequest.abort();
                kodeBarangRequest = null;
            }

            if (kodeBarang === '') {
                setKodeBarangState('empty', '');
                return;
            }

            setKodeBarangState('checking', 'Memeriksa kode barang...');

            kodeBarangCheckTimer = setTimeout(function() {
                kodeBarangRequest = $.ajax({
                    url: checkUrl,
                    method: 'POST',
                    dataType: 'json',
                    data: {
                        kd_barang: kodeBarang
                    },
                    success: function(response) {
                        if (response && response.used) {
                            setKodeBarangState('used', 'Kode barang telah di gunakan dengan nama barang: ' + response.nama_barang);
                            return;
                        }

                        setKodeBarangState('available', 'Kode barang dapat digunakan.');
                    },
                    error: function(xhr, status) {
                        if (status === 'abort') {
                            return;
                        }

                        setKodeBarangState('empty', '');
                    },
                    complete: function() {
                        kodeBarangRequest = null;
                    }
                });
            }, 350);
        });

        $('#addmbarangnk').on('hidden.bs.modal', function() {
            clearTimeout(kodeBarangCheckTimer);

            if (kodeBarangRequest) {
                kodeBarangRequest.abort();
                kodeBarangRequest = null;
            }

            setKodeBarangState('empty', '');
        });

        $('#form_add_mbarang').on('submit', function(event) {
            if (kodeBarangIsUsed) {
                event.preventDefault();
                $('#add_kd_adm').focus();
            }
        });
    });
    $(function() {
        $("#list_req").DataTable({
            "responsive": true,
            "lengthChange": false,
            "autoWidth": false,
            "aaSorting": [],
        }).buttons().container().appendTo('#example1_wrapper .col-md-6:eq(0)');
    });
</script>
