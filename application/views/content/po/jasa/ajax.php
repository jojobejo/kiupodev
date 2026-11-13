<script>
    $(function() {
        if ($.fn.DataTable) {
            $('#tbPojasa, #tbVendorJasa, #tbNotePojasa, #tbProgressPojasa, #tbFilePojasa, #tbBiayaPojasa, #tbPaymentPojasa, #tbReportDonePojasa, #tbVendorPerformancePojasa').DataTable({
                responsive: true,
                lengthChange: false,
                autoWidth: false,
                pageLength: 10
            });
        }

        function formatRupiah(value) {
            var number = Number(value || 0);
            return 'Rp. ' + number.toLocaleString('id-ID', {
                maximumFractionDigits: 0
            });
        }

        function escapeHtml(value) {
            return $('<div>').text(value === null || value === undefined || value === '' ? '-' : value).html();
        }

        function renderReadonlyScopeFromEditor() {
            var rows = [];
            var total = 0;

            $('#formReviewScopePojasa .tbScopeEditorPojasa tbody tr').each(function() {
                var nama = $(this).find('[name="nama_pekerjaan[]"]').val() || '';
                var deskripsi = $(this).find('[name="deskripsi[]"]').val() || '';
                var qty = Number($(this).find('[name="qty[]"]').val() || 0);
                var harga = Number($(this).find('[name="hrg_satuan[]"]').val() || 0);
                var subtotal = qty * harga;

                if (!nama) {
                    return;
                }

                total += subtotal;
                rows.push({
                    nama: nama,
                    deskripsi: deskripsi,
                    qty: qty,
                    harga: harga,
                    subtotal: subtotal
                });
            });

            var html = '';
            if (!rows.length) {
                html = '<tr><td colspan="6" class="text-center text-muted">Belum ada data scope pekerjaan.</td></tr>';
            } else {
                rows.forEach(function(row, index) {
                    html += '<tr>' +
                        '<td>' + (index + 1) + '</td>' +
                        '<td>' + escapeHtml(row.nama) + '</td>' +
                        '<td>' + escapeHtml(row.deskripsi) + '</td>' +
                        '<td>' + escapeHtml(row.qty.toFixed(2)) + '</td>' +
                        '<td>' + formatRupiah(row.harga) + '</td>' +
                        '<td>' + formatRupiah(row.subtotal) + '</td>' +
                        '</tr>';
                });
            }

            $('#tbScopeReadonlyPojasa tbody').html(html);
            $('#scopeReadonlyTotalPojasa').text(formatRupiah(total));
        }

        function renderDocumentRows(files) {
            if (!Array.isArray(files)) {
                return;
            }

            var html = '';
            var modalHtml = '';
            if (!files.length) {
                html = '<tr><td colspan="4" class="text-center text-muted">Belum ada dokumen pendukung.</td></tr>';
            } else {
                files.forEach(function(file) {
                    var id = Number(file.id_file_jasa || 0);
                    html += '<tr>' +
                        '<td>' + escapeHtml(file.jenis_dokumen) + '</td>' +
                        '<td>' + escapeHtml(file.file_original) + '<br><small>' + escapeHtml(file.keterangan) + '</small></td>' +
                        '<td>' + escapeHtml(file.uploaded_name) + '</td>' +
                        '<td>' +
                        '<a href="<?= base_url('pojasa/file/view/') ?>' + id + '" class="btn btn-info btn-sm" target="_blank" title="Tinjau"><i class="fas fa-eye"></i></a> ' +
                        '<a href="<?= base_url('pojasa/file/download/') ?>' + id + '" class="btn btn-secondary btn-sm" title="Download"><i class="fas fa-download"></i></a> ' +
                        <?php if ($canProjectDocumentManage) : ?>
                        '<button type="button" class="btn btn-warning btn-sm document-edit-action d-none" data-toggle="modal" data-target="#modalEditFilePojasa' + id + '" title="Ubah"><i class="fas fa-edit"></i></button> ' +
                        '<button type="button" class="btn btn-danger btn-sm btnDeleteFilePojasa document-edit-action d-none" data-id-file="' + id + '" title="Hapus"><i class="fas fa-trash"></i></button>' +
                        <?php else : ?>
                        '' +
                        <?php endif; ?>
                        '</td>' +
                        '</tr>';

                    <?php if ($canProjectDocumentManage) : ?>
                    modalHtml += '<div class="modal fade dynamic-file-modal-pojasa" id="modalEditFilePojasa' + id + '" tabindex="-1" role="dialog">' +
                        '<div class="modal-dialog modal-lg" role="document">' +
                        '<div class="modal-content">' +
                        '<form class="formEditFilePojasa" enctype="multipart/form-data">' +
                        '<input type="hidden" name="id_file_jasa" value="' + id + '">' +
                        '<div class="modal-header">' +
                        '<h5 class="modal-title">Ubah Dokumen Pendukung</h5>' +
                        '<button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>' +
                        '</div>' +
                        '<div class="modal-body">' +
                        '<div class="form-group">' +
                        '<label>Jenis Dokumen</label>' +
                        '<input type="text" class="form-control" name="jenis_dokumen" value="' + escapeHtml(file.jenis_dokumen) + '">' +
                        '</div>' +
                        '<div class="form-group">' +
                        '<label>Keterangan</label>' +
                        '<input type="text" class="form-control" name="keterangan_file" value="' + escapeHtml(file.keterangan) + '">' +
                        '</div>' +
                        '<div class="form-group">' +
                        '<label>Replace File</label>' +
                        '<input type="file" class="form-control" name="dokumen_jasa" accept=".jpg,.jpeg,.png,.pdf,.doc,.docx,.xls,.xlsx,.txt,.rtf,.csv">' +
                        '<small class="text-muted">Kosongkan jika hanya mengubah jenis atau keterangan dokumen.</small>' +
                        '</div>' +
                        '</div>' +
                        '<div class="modal-footer">' +
                        '<button type="button" class="btn btn-secondary" data-dismiss="modal">Tutup</button>' +
                        '<button type="submit" class="btn btn-primary">Simpan</button>' +
                        '</div>' +
                        '</form>' +
                        '</div>' +
                        '</div>' +
                        '</div>';
                    <?php endif; ?>
                });
            }

            if ($.fn.DataTable && $.fn.DataTable.isDataTable('#tbFilePojasa')) {
                $('#tbFilePojasa').DataTable().destroy();
            }

            $('#tbFilePojasaBody').html(html);
            if ($.fn.DataTable) {
                $('#tbFilePojasa').DataTable({
                    responsive: true,
                    lengthChange: false,
                    autoWidth: false,
                    pageLength: 10
                });
            }

            $('.dynamic-file-modal-pojasa').remove();
            if (modalHtml) {
                $('body').append(modalHtml);
            }
        }

        function hitungUlangReqScopeBaruPojasa() {
            var grandTotalBaru = 0;

            $('.req-scope-table-baru').each(function() {
                var totalPerTabelBaru = 0;
                var targetTotalBaru = $(this).data('total-target');

                $(this).find('tbody tr').each(function() {
                    var qtyBaru = Number($(this).find('.req-scope-qty-baru').val() || 0);
                    var biayaBaru = Number($(this).find('.req-scope-biaya-baru').val() || 0);
                    var subtotalBaru = qtyBaru * biayaBaru;
                    totalPerTabelBaru += subtotalBaru;
                    $(this).find('.req-scope-total-baru').val(formatRupiah(subtotalBaru));
                });

                grandTotalBaru += totalPerTabelBaru;
                if (targetTotalBaru) {
                    $(targetTotalBaru).text(formatRupiah(totalPerTabelBaru));
                }
            });

            $('#grandTotalReqScopeBaru').text(formatRupiah(grandTotalBaru));
        }

        function syncHiddenTarget(form) {
            var tanggalRequest = $(form).find('[name="tgl_request"]').val();
            if (tanggalRequest) {
                $(form).find('.pojasa-target-sync').val(tanggalRequest);
            }
        }

        function normalizeHiddenPojasaInputs(form) {
            var $form = $(form);
            var defaultLokasi = <?= json_encode((string) $depuser) ?> || 'UMUM';

            syncHiddenTarget(form);

            $form.find('input[type="hidden"][name="lokasi_pekerjaan"]').each(function() {
                if (!$(this).val()) {
                    $(this).val(defaultLokasi);
                }
            });

            $form.find('input[type="hidden"][name="satuan[]"]').each(function() {
                if (!$(this).val()) {
                    $(this).val('Lot');
                }
            });

            $form.find('input[type="hidden"][name="qty[]"]').each(function() {
                if (!$(this).val() || Number($(this).val()) <= 0) {
                    $(this).val('1');
                }
            });

            $form.find('input[type="hidden"][name="hrg_satuan[]"]').each(function() {
                if ($(this).val() === '') {
                    $(this).val('0');
                }
            });

            $form.find('input[type="hidden"][name="nama_pekerjaan[]"]').each(function() {
                if ($(this).val()) {
                    return;
                }

                var $row = $(this).closest('tr, .row');
                var deskripsi = $row.find('[name="deskripsi[]"]').val() || $form.find('[name="tujuan_pekerjaan"]').val() || 'Jasa';
                $(this).val(deskripsi);
            });
        }

        $('#formRequestPojasa, #formRevisePojasa').each(function() {
            syncHiddenTarget(this);
        });

        $(document).on('change input', '[name="tgl_request"]', function() {
            syncHiddenTarget($(this).closest('form'));
        });

        $(document).on('click', '.btnReqScopeTambahBaru', function() {
            var selectorTabelBaru = $(this).data('table-target');
            var tabelBaru = $(selectorTabelBaru);
            var jenisDetailBaru = tabelBaru.attr('id') === 'tbReqScopeAlatBahanBaru' ? 'BAHAN' : 'JASA';
            var barisBaru = tabelBaru.find('tbody tr:first').clone();
            barisBaru.find('input').val('');
            barisBaru.find('.req-scope-qty-baru').val('1');
            barisBaru.find('[name="jenis_detail[]"]').val(jenisDetailBaru);
            barisBaru.find('[name="satuan[]"]').val('Lot');
            barisBaru.find('.req-scope-biaya-baru').val('0');
            barisBaru.find('.req-scope-total-baru').val(formatRupiah(0));
            tabelBaru.find('tbody').append(barisBaru);
            hitungUlangReqScopeBaruPojasa();
        });

        $(document).on('click', '.btnReqScopeHapusBaru', function() {
            var tbodyBaru = $(this).closest('tbody');
            if (tbodyBaru.find('tr').length > 1) {
                $(this).closest('tr').remove();
                hitungUlangReqScopeBaruPojasa();
            }
        });

        $('#btnReqDokumenTambahBaru').on('click', function() {
            var barisDokumenBaru = $('#reqDokumenPendukungRowsBaru .req-dokumen-pendukung-row-baru:first').clone();
            barisDokumenBaru.find('input').val('');
            $('#reqDokumenPendukungRowsBaru').append(barisDokumenBaru);
        });

        $(document).on('click', '.btnReqDokumenHapusBaru', function() {
            if ($('#reqDokumenPendukungRowsBaru .req-dokumen-pendukung-row-baru').length > 1) {
                $(this).closest('.req-dokumen-pendukung-row-baru').remove();
            }
        });

        $('#btnAddDokumenJasaDetail').on('click', function() {
            var row = $('#dokumenJasaDetailRows .dokumen-jasa-detail-row:first').clone();
            row.find('input').val('');
            $('#dokumenJasaDetailRows').append(row);
        });

        $(document).on('click', '.btnRemoveDokumenJasaDetail', function() {
            if ($('#dokumenJasaDetailRows .dokumen-jasa-detail-row').length > 1) {
                $(this).closest('.dokumen-jasa-detail-row').remove();
            }
        });

        $('.btnAddRevisiDokumenPojasa').on('click', function() {
            var row = $('#revisiDokumenPojasaRows .revisi-dokumen-pojasa-row:first').clone();
            row.find('input').val('');
            $('#revisiDokumenPojasaRows').append(row);
        });

        $(document).on('click', '.btnRemoveRevisiDokumenPojasa', function() {
            if ($('#revisiDokumenPojasaRows .revisi-dokumen-pojasa-row').length > 1) {
                $(this).closest('.revisi-dokumen-pojasa-row').remove();
            }
        });

        $(document).on('input', '.req-scope-qty-baru, .req-scope-biaya-baru', hitungUlangReqScopeBaruPojasa);
        hitungUlangReqScopeBaruPojasa();

        $('.btnAddEditorScopePojasa').on('click', function() {
            var table = $(this).closest('form').find('.tbScopeEditorPojasa tbody');
            var row = table.find('tr:first').clone();
            row.find('input').val('');
            row.find('[name="qty[]"]').val('1');
            row.find('[name="satuan[]"]').val('Lot');
            row.find('[name="hrg_satuan[]"]').val('0');
            table.append(row);
        });

        $(document).on('click', '.btnRemoveEditorScopePojasa', function() {
            var tbody = $(this).closest('tbody');
            if (tbody.find('tr').length > 1) {
                $(this).closest('tr').remove();
            }
        });

        $(document).on('click', '.btnToggleScopeEditPojasa', function(e) {
            e.preventDefault();
            $('#scopeEditorPojasa').removeClass('d-none').show();
            $('.btnRekamScopePojasa').removeClass('d-none').show();
            $(this).addClass('d-none');
        });

        $(document).on('click', '.btnRekamScopePojasa', function(e) {
            e.preventDefault();
            $('#formReviewScopePojasa').trigger('submit');
        });

        $(document).on('click', '.btnToggleDokumenEditPojasa', function(e) {
            e.preventDefault();
            $('#formUploadFilePojasa').removeClass('d-none').show();
            $('.document-edit-action').removeClass('d-none').show();
            $('.btnRekamDokumenPojasa').removeClass('d-none').show();
            $(this).addClass('d-none');
        });

        $(document).on('click', '.btnRekamDokumenPojasa', function(e) {
            e.preventDefault();
            $('#formUploadFilePojasa').trigger('submit');
        });

        function recalculateSelisihBiaya() {
            var estimasi = Number($('.biaya-estimasi').val() || 0);
            var realisasi = Number($('.biaya-realisasi').val() || 0);
            $('#selisihBiayaPojasa').text(formatRupiah(realisasi - estimasi));
        }

        $(document).on('input', '.biaya-estimasi, .biaya-realisasi', recalculateSelisihBiaya);
        recalculateSelisihBiaya();

        function recalculatePayment() {
            var tagihan = Number($('.payment-tagihan').val() || 0);
            var bayar = Number($('.payment-bayar').val() || 0);
            $('#sisaPaymentPojasa').text(formatRupiah(tagihan - bayar));
        }

        function recalculateEvaluation() {
            var total = 0;
            var count = 0;
            $('.eval-score').each(function() {
                total += Number($(this).val() || 0);
                count++;
            });
            var avg = count > 0 ? total / count : 0;
            $('#avgEvaluationPojasa').text(avg.toFixed(2));
        }

        $(document).on('input', '.payment-tagihan, .payment-bayar', recalculatePayment);
        $(document).on('input', '.eval-score', recalculateEvaluation);
        recalculatePayment();
        recalculateEvaluation();

        function showMessage(success, message, redirect) {
            if (typeof Swal !== 'undefined') {
                Swal.fire(success ? 'Berhasil' : 'Gagal', message, success ? 'success' : 'error').then(function() {
                    if (success && redirect) {
                        window.location.href = redirect;
                    } else if (success) {
                        window.location.reload();
                    }
                });
            } else {
                alert(message);
                if (success && redirect) {
                    window.location.href = redirect;
                } else if (success) {
                    window.location.reload();
                }
            }
        }

        function postForm(url, data) {
            $.ajax({
                url: url,
                method: 'POST',
                data: data,
                dataType: 'json',
                beforeSend: function() {
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            title: 'Memproses',
                            allowOutsideClick: false,
                            didOpen: function() {
                                Swal.showLoading();
                            }
                        });
                    }
                },
                success: function(response) {
                    showMessage(response.success || response.status, response.message || 'Proses selesai.', response.redirect);
                },
                error: function() {
                    showMessage(false, 'Terjadi gangguan koneksi atau server tidak mengembalikan JSON.');
                }
            });
        }

        function postFormNoReload(url, data, afterSuccess) {
            $.ajax({
                url: url,
                method: 'POST',
                data: data,
                dataType: 'json',
                beforeSend: function() {
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            title: 'Memproses',
                            allowOutsideClick: false,
                            didOpen: function() {
                                Swal.showLoading();
                            }
                        });
                    }
                },
                success: function(response) {
                    var success = response.success || response.status;
                    var message = response.message || 'Proses selesai.';
                    if (typeof Swal !== 'undefined') {
                        Swal.fire(success ? 'Berhasil' : 'Gagal', message, success ? 'success' : 'error');
                    } else {
                        alert(message);
                    }
                    if (success && typeof afterSuccess === 'function') {
                        afterSuccess(response);
                    }
                },
                error: function() {
                    if (typeof Swal !== 'undefined') {
                        Swal.fire('Gagal', 'Terjadi gangguan koneksi atau server tidak mengembalikan JSON.', 'error');
                    } else {
                        alert('Terjadi gangguan koneksi atau server tidak mengembalikan JSON.');
                    }
                }
            });
        }

        function postMultipart(url, form) {
            normalizeHiddenPojasaInputs(form);

            $.ajax({
                url: url,
                method: 'POST',
                data: new FormData(form),
                dataType: 'json',
                processData: false,
                contentType: false,
                beforeSend: function() {
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            title: 'Mengupload',
                            allowOutsideClick: false,
                            didOpen: function() {
                                Swal.showLoading();
                            }
                        });
                    }
                },
                success: function(response) {
                    showMessage(response.success || response.status, response.message || 'Upload selesai.', response.redirect);
                },
                error: function() {
                    showMessage(false, 'Upload gagal atau server tidak mengembalikan JSON.');
                }
            });
        }

        function postMultipartNoReload(url, form, afterSuccess) {
            normalizeHiddenPojasaInputs(form);

            $.ajax({
                url: url,
                method: 'POST',
                data: new FormData(form),
                dataType: 'json',
                processData: false,
                contentType: false,
                beforeSend: function() {
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            title: 'Mengupload',
                            allowOutsideClick: false,
                            didOpen: function() {
                                Swal.showLoading();
                            }
                        });
                    }
                },
                success: function(response) {
                    var success = response.success || response.status;
                    var message = response.message || 'Upload selesai.';
                    if (typeof Swal !== 'undefined') {
                        Swal.fire(success ? 'Berhasil' : 'Gagal', message, success ? 'success' : 'error');
                    } else {
                        alert(message);
                    }
                    if (success && typeof afterSuccess === 'function') {
                        afterSuccess(response);
                    }
                },
                error: function() {
                    if (typeof Swal !== 'undefined') {
                        Swal.fire('Gagal', 'Upload gagal atau server tidak mengembalikan JSON.', 'error');
                    } else {
                        alert('Upload gagal atau server tidak mengembalikan JSON.');
                    }
                }
            });
        }

        $('#formAddVendorJasa').on('submit', function(e) {
            e.preventDefault();
            normalizeHiddenPojasaInputs(this);
            postForm('<?= base_url('pojasa/vendor/save') ?>', $(this).serialize());
        });

        $('.formEditVendorJasa').on('submit', function(e) {
            e.preventDefault();
            normalizeHiddenPojasaInputs(this);
            postForm('<?= base_url('pojasa/vendor/update') ?>', $(this).serialize());
        });

        $('.btnApproveVendorJasa, .btnRejectVendorJasa').on('click', function() {
            var kdVendor = $(this).data('kd-vendor');
            var action = $(this).hasClass('btnApproveVendorJasa') ? 'approve' : 'reject';
            var title = action === 'approve' ? 'ACC request vendor jasa?' : 'Tolak request vendor jasa?';

            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    title: title,
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonText: 'Proses',
                    cancelButtonText: 'Batal'
                }).then(function(result) {
                    if (result.isConfirmed) {
                        postForm('<?= base_url('pojasa/vendor/approval') ?>', {
                            kd_vendor_jasa: kdVendor,
                            action: action
                        });
                    }
                });
            } else if (confirm(title)) {
                postForm('<?= base_url('pojasa/vendor/approval') ?>', {
                    kd_vendor_jasa: kdVendor,
                    action: action
                });
            }
        });

        $('.btnDeleteVendorJasa').on('click', function() {
            var kdVendor = $(this).data('kd-vendor');
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    title: 'Hapus vendor jasa?',
                    text: 'Vendor yang sudah dipakai pada request tidak dapat dihapus.',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Hapus',
                    cancelButtonText: 'Batal'
                }).then(function(result) {
                    if (result.isConfirmed) {
                        postForm('<?= base_url('pojasa/vendor/delete') ?>', {
                            kd_vendor_jasa: kdVendor
                        });
                    }
                });
            } else if (confirm('Hapus vendor jasa?')) {
                postForm('<?= base_url('pojasa/vendor/delete') ?>', {
                    kd_vendor_jasa: kdVendor
                });
            }
        });

        $('#formRequestPojasa').on('submit', function(e) {
            e.preventDefault();
            normalizeHiddenPojasaInputs(this);
            postMultipart('<?= base_url('pojasa/request/save') ?>', this);
        });

        $('#formRevisePojasa').on('submit', function(e) {
            e.preventDefault();
            normalizeHiddenPojasaInputs(this);
            postMultipart('<?= base_url('pojasa/request/revise') ?>', this);
        });

        $('#formReviewScopePojasa').on('submit', function(e) {
            e.preventDefault();
            normalizeHiddenPojasaInputs(this);
            postFormNoReload('<?= base_url('pojasa/scope/save') ?>', $(this).serialize(), function() {
                renderReadonlyScopeFromEditor();
                $('#scopeEditorPojasa').addClass('d-none');
                $('.btnRekamScopePojasa').addClass('d-none');
                $('.btnToggleScopeEditPojasa').removeClass('d-none');
            });
        });

        $('.btnApprovalPojasa').on('click', function() {
            var kdPo = $(this).data('kd-po');
            var action = $(this).data('action');
            var note = '';

            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    title: 'Proses approval PO Jasa?',
                    input: 'textarea',
                    inputLabel: 'Catatan',
                    inputPlaceholder: 'Opsional, isi alasan pending/reject atau catatan approval',
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonText: 'Proses',
                    cancelButtonText: 'Batal'
                }).then(function(result) {
                    if (result.isConfirmed) {
                        note = result.value || '';
                        postForm('<?= base_url('pojasa/approval') ?>', {
                            kd_po_jasa: kdPo,
                            action: action,
                            note: note
                        });
                    }
                });
            } else if (confirm('Proses approval PO Jasa?')) {
                postForm('<?= base_url('pojasa/approval') ?>', {
                    kd_po_jasa: kdPo,
                    action: action,
                    note: note
                });
            }
        });

        $('.btnGenerateSpkPojasa').on('click', function() {
            var kdPo = $(this).data('kd-po');
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    title: 'Generate PO/SPK Jasa?',
                    text: 'Nomor SPK akan diterbitkan setelah ACC DIREKTUR.',
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonText: 'Generate',
                    cancelButtonText: 'Batal'
                }).then(function(result) {
                    if (result.isConfirmed) {
                        postForm('<?= base_url('pojasa/generate-spk') ?>', {
                            kd_po_jasa: kdPo
                        });
                    }
                });
            } else if (confirm('Generate PO/SPK Jasa?')) {
                postForm('<?= base_url('pojasa/generate-spk') ?>', {
                    kd_po_jasa: kdPo
                });
            }
        });

        $('#formProgressPojasa').on('submit', function(e) {
            e.preventDefault();
            normalizeHiddenPojasaInputs(this);
            postForm('<?= base_url('pojasa/progress/save') ?>', $(this).serialize());
        });

        $('#formUploadFilePojasa').on('submit', function(e) {
            e.preventDefault();
            postMultipartNoReload('<?= base_url('pojasa/file/upload') ?>', this, function(response) {
                renderDocumentRows(response.files || []);
                $('#formUploadFilePojasa')[0].reset();
                $('#formUploadFilePojasa').addClass('d-none');
                $('.document-edit-action').addClass('d-none');
                $('.btnRekamDokumenPojasa').addClass('d-none');
                $('.btnToggleDokumenEditPojasa').removeClass('d-none');
            });
        });

        $(document).on('submit', '.formEditFilePojasa', function(e) {
            e.preventDefault();
            var hasFile = $(this).find('[name="dokumen_jasa"]').val() !== '';
            var form = this;
            postMultipartNoReload(hasFile ? '<?= base_url('pojasa/file/replace') ?>' : '<?= base_url('pojasa/file/update') ?>', form, function(response) {
                renderDocumentRows(response.files || []);
                $(form).closest('.modal').modal('hide');
            });
        });

        $('.btnDeleteFilePojasa').on('click', function() {
            var idFile = $(this).data('id-file');
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    title: 'Hapus dokumen pendukung?',
                    text: 'Data dokumen dan file arsipnya akan dihapus.',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Hapus',
                    cancelButtonText: 'Batal'
                }).then(function(result) {
                    if (result.isConfirmed) {
                        postForm('<?= base_url('pojasa/file/delete') ?>', {
                            id_file_jasa: idFile
                        });
                    }
                });
            } else if (confirm('Hapus dokumen pendukung?')) {
                postForm('<?= base_url('pojasa/file/delete') ?>', {
                    id_file_jasa: idFile
                });
            }
        });

        $('#formBiayaPojasa').on('submit', function(e) {
            e.preventDefault();
            normalizeHiddenPojasaInputs(this);
            postForm('<?= base_url('pojasa/biaya/save') ?>', $(this).serialize());
        });

        $('#formBastPojasa').on('submit', function(e) {
            e.preventDefault();
            normalizeHiddenPojasaInputs(this);
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    title: 'Selesaikan project jasa?',
                    text: 'Status PO Jasa akan menjadi DONE.',
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonText: 'Selesaikan',
                    cancelButtonText: 'Batal'
                }).then(function(result) {
                    if (result.isConfirmed) {
                        postForm('<?= base_url('pojasa/bast/complete') ?>', $('#formBastPojasa').serialize());
                    }
                });
            } else if (confirm('Selesaikan project jasa?')) {
                postForm('<?= base_url('pojasa/bast/complete') ?>', $(this).serialize());
            }
        });

        $('#formPaymentPojasa').on('submit', function(e) {
            e.preventDefault();
            normalizeHiddenPojasaInputs(this);
            postForm('<?= base_url('pojasa/payment/save') ?>', $(this).serialize());
        });

        $('#formEvaluationPojasa').on('submit', function(e) {
            e.preventDefault();
            normalizeHiddenPojasaInputs(this);
            postForm('<?= base_url('pojasa/evaluation/save') ?>', $(this).serialize());
        });
    });
</script>
