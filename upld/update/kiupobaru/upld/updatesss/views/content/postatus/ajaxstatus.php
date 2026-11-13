<script>
    $(document).ready(function() {

        var baseUrl = '<?php echo base_url('postatus'); ?>';

        <?php if ($this->session->flashdata('error')) : ?>
            Swal.fire({
                icon: 'warning',
                title: 'Tidak Dapat Diproses',
                text: '<?= htmlspecialchars($this->session->flashdata('error'), ENT_QUOTES, 'UTF-8') ?>'
            });
        <?php endif; ?>

        $(document).on('click', '.btn-select-template', function() {
            var button = $(this);
            $('#kd_po_modal').val(button.data('kdpo') || '');
            $('#update_shipment').val(button.data('kdpo') || '');
            $('#print_mode_modal').val(button.data('print-mode') || 'include');
        });

        $(document).on('click', '.btn-po-confirm', function(event) {
            event.preventDefault();

            var button = $(this);
            var shipment = $.trim(button.data('shipment') || '');
            if (shipment === '' || shipment === '-') {
                Swal.fire({
                    icon: 'warning',
                    title: 'Shipment Setting Belum Dipilih',
                    text: 'Silakan pilih format shipment terlebih dahulu.'
                });
                return;
            }

            Swal.fire({
                icon: 'warning',
                title: 'Selesaikan Data PO?',
                text: 'Data PO akan diselesaikan dan status akan diubah menjadi DONE.',
                showCancelButton: true,
                confirmButtonText: 'Ya, Selesaikan',
                cancelButtonText: 'Batal',
                reverseButtons: true
            }).then(function(result) {
                if (!result.isConfirmed) {
                    return;
                }

                button.addClass('disabled').attr('aria-disabled', 'true');
                Swal.fire({
                    title: 'Memproses...',
                    text: 'Mohon tunggu sebentar.',
                    allowOutsideClick: false,
                    didOpen: function() {
                        Swal.showLoading();
                    }
                });

                $.ajax({
                    url: button.data('url'),
                    type: 'POST',
                    dataType: 'json',
                    success: function(response) {
                        if (!response.success) {
                            button.removeClass('disabled').removeAttr('aria-disabled');
                            Swal.fire({
                                icon: 'warning',
                                title: 'Tidak Dapat Diproses',
                                text: response.message || 'Data PO tidak dapat diselesaikan.'
                            });
                            return;
                        }

                        Swal.fire({
                            icon: 'success',
                            title: 'Berhasil',
                            text: response.message,
                            confirmButtonText: 'OK'
                        }).then(function() {
                            window.location.reload();
                        });
                    },
                    error: function() {
                        button.removeClass('disabled').removeAttr('aria-disabled');
                        Swal.fire({
                            icon: 'error',
                            title: 'Gagal',
                            text: 'Tidak dapat terhubung ke server.'
                        });
                    }
                });
            });
        });

        // === REPOST ===
        $("#repost").on('click', function() {
            var kd_lama = $("#kd_lama").val();
            var kdpo = $("#kdpoisi").val();
            var nopo = $("#nopoisi").val();
            var tgl = $("#tgltisi").val();
            var tmpo = $("#tmpobayarisi").val();
            var gdg = $("#gdgkirimisi").val();
            var jml = $("#jmlitemisi").val();
            var suplier = $("#kdsupisi").val();
            var harga = $("#tothrgisi").val();
            var hargapjk = $("#hrgpjkisi").val();
            var tax = $("#taxisi").val();

            if (jml == 0) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Tidak Dapat Diproses',
                    text: 'Jumlah item masih kosong.'
                });
                return;
            }

            if (nopo == "") {
                Swal.fire({
                    icon: 'warning',
                    title: 'Nomor PO Kosong',
                    text: 'Nomor PO harus diisi.'
                });
                return;
            }

            if (tgl == "") {
                Swal.fire({
                    icon: 'warning',
                    title: 'Tanggal Kosong',
                    text: 'Tanggal order belum diisi.'
                });
                return;
            }

            if (tax == "") {
                Swal.fire({
                    icon: 'warning',
                    title: 'Kolom Pajak Kosong',
                    text: 'Kolom pajak (Tax) wajib diisi.'
                });
                return;
            }

            Swal.fire({
                title: 'Repost Data?',
                text: 'Pastikan semua data sudah benar sebelum dikirim.',
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Ya, kirim',
                cancelButtonText: 'Batal',
                reverseButtons: true
            }).then((result) => {
                if (result.isConfirmed) {
                    Swal.fire({
                        title: 'Memproses...',
                        text: 'Mohon tunggu sebentar.',
                        allowOutsideClick: false,
                        didOpen: () => {
                            Swal.showLoading();
                        }
                    });

                    $.ajax({
                        url: "<?= base_url('repost_po') ?>",
                        type: "POST",
                        data: {
                            kd_lama: kd_lama,
                            kdpo: kdpo,
                            nopo: nopo,
                            tgl: tgl,
                            tmpo: tmpo,
                            gdg: gdg,
                            jml: jml,
                            suplier: suplier,
                            harga: harga,
                            hargapjk: hargapjk,
                            tax: tax
                        },
                        dataType: "JSON",
                        cache: false,
                        success: function(data) {
                            if (data.msg == "success") {
                                Swal.fire({
                                    icon: 'success',
                                    title: 'Berhasil',
                                    text: 'Data PO berhasil diproses!',
                                    timer: 2000,
                                    showConfirmButton: false
                                }).then(() => {
                                    location.href = baseUrl;
                                });
                            } else {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Kesalahan',
                                    text: 'Terjadi kesalahan pada data!'
                                });
                            }
                        },
                        error: function() {
                            Swal.fire({
                                icon: 'error',
                                title: 'Gagal',
                                text: 'Tidak dapat terhubung ke server.'
                            });
                        }
                    });
                }
            });
        });

        $(document).on('click', '.btn-onhand-po', function(e) {
            e.preventDefault();

            var button = $(this);
            var kdpo = button.data('kdpo');
            var shipment = $.trim(button.data('shipment') || '');
            var url = button.data('url');

            if (shipment == '' || shipment == '-') {
                Swal.fire({
                    icon: 'warning',
                    title: 'Shipment Belum Disetting',
                    text: 'Silakan pilih / setting format shipment terlebih dahulu.'
                });
                return;
            }

            Swal.fire({
                title: 'Update ON HAND?',
                text: 'Status PO akan diubah menjadi DONE.',
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Ya, lanjutkan',
                cancelButtonText: 'Batal',
                reverseButtons: true
            }).then((result) => {
                if (!result.isConfirmed) {
                    return;
                }

                Swal.fire({
                    title: 'Memproses...',
                    text: 'Mohon tunggu sebentar.',
                    allowOutsideClick: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });

                $.ajax({
                    url: url,
                    type: 'POST',
                    data: {
                        kdpo: kdpo
                    },
                    dataType: 'JSON',
                    cache: false,
                    success: function(response) {
                        if (response.success) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Berhasil',
                                text: response.message,
                                timer: 2000,
                                showConfirmButton: false
                            });

                            button.closest('.konfirmasi-update-wrapper').html(
                                '<label for="tgTrans" class="">Konfirmasi Update : &nbsp;&nbsp; </label>' +
                                '<a class="btn btn-success btn-block"><i class="fas fa-thumbs-up"></i> PO - DONE</a>'
                            );

                            $('.status-order-badge[data-kdpo="' + kdpo + '"]')
                                .removeClass('btn-warning')
                                .addClass('btn-success')
                                .attr('href', '<?= base_url('printOrder/') ?>' + kdpo)
                                .attr('target', '_blank')
                                .html('<i class="fas fa-print"></i> Cetak Form Order');

                            $('a[data-target="#modalshipment' + kdpo + '"]').closest('.col-md').hide();
                        } else {
                            Swal.fire({
                                icon: 'warning',
                                title: 'Tidak Dapat Diproses',
                                text: response.message || 'Proses update status dibatalkan.'
                            });
                        }
                    },
                    error: function() {
                        Swal.fire({
                            icon: 'error',
                            title: 'Gagal',
                            text: 'Tidak dapat terhubung ke server.'
                        });
                    }
                });
            });
        });


        // === ACC DIREKTUR ===
        // $(document).on('click', '.btn-konfirmasi', function(e) {
        //     e.preventDefault();
        //     var url = $(this).data('url');

        //     Swal.fire({
        //         title: 'Konfirmasi Order?',
        //         text: 'Pastikan data PO sudah benar sebelum dilanjutkan.',
        //         icon: 'question',
        //         showCancelButton: true,
        //         confirmButtonText: 'Ya, lanjutkan',
        //         cancelButtonText: 'Batal',
        //         reverseButtons: true
        //     }).then((result) => {
        //         if (result.isConfirmed) {
        //             Swal.fire({
        //                 title: 'Memproses...',
        //                 text: 'Mohon tunggu sebentar.',
        //                 allowOutsideClick: false,
        //                 didOpen: () => {
        //                     Swal.showLoading();
        //                 }
        //             });

        //             setTimeout(() => {
        //                 window.location.href = url;
        //             }, 800);
        //         }
        //     });
        // });

        // $(document).on('click', '.btn-reject', function(e) {
        //     e.preventDefault();
        //     var url = $(this).data('url');

        //     Swal.fire({
        //         title: 'Tolak Order?',
        //         text: 'Apakah Anda yakin ingin menolak order ini?',
        //         icon: 'warning',
        //         showCancelButton: true,
        //         confirmButtonText: 'Ya, tolak',
        //         cancelButtonText: 'Batal',
        //         reverseButtons: true
        //     }).then((result) => {
        //         if (result.isConfirmed) {
        //             Swal.fire({
        //                 title: 'Memproses...',
        //                 text: 'Order sedang ditolak.',
        //                 allowOutsideClick: false,
        //                 didOpen: () => {
        //                     Swal.showLoading();
        //                 }
        //             });

        //             setTimeout(() => {
        //                 window.location.href = url;
        //             }, 800);
        //         }
        //     });
        // });

    });
</script>


<!-- <script>
    $(document).ready(function() {

        var baseUrl = '<?php echo base_url('postatus'); ?>';

        $("#repost").on('click', function() {
            var kd_lama = $("#kd_lama").val();
            var kdpo = $("#kdpoisi").val();
            var nopo = $("#nopoisi").val();
            var tgl = $("#tgltisi").val();
            var tmpo = $("#tmpobayarisi").val();
            var gdg = $("#gdgkirimisi").val();
            var jml = $("#jmlitemisi").val();
            var suplier = $("#kdsupisi").val();
            var harga = $("#tothrgisi").val();
            var hargapjk = $("#hrgpjkisi").val();
            var tax = $("#taxisi").val();

            if (jml == 0) {
                alert('tidak dapat diproses');
            } else {
                if (nopo == "") {
                    alert('Nomor PO tidak terisi');
                } else if (tgl == "") {
                    alert('tgl order belum terisi');
                } else {
                    $.ajax({
                        url: "<?= base_url('repost_po') ?>",
                        type: "POST",
                        data: {
                            kd_lama: kd_lama,
                            kdpo: kdpo,
                            nopo: nopo,
                            tgl: tgl,
                            tmpo: tmpo,
                            gdg: gdg,
                            jml: jml,
                            suplier: suplier,
                            harga: harga,
                            tax: tax
                        },
                        dataType: "JSON",
                        cache: false,
                        success: function(data) {
                            if (data.msg == "success") {
                                location.href = baseUrl
                            } else {
                                alert('ada kesalahan data')
                            }
                        }
                    })
                }
            }
        })
    });
    
</script> -->
