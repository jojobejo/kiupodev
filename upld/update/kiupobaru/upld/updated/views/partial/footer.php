<footer class="main-footer">
    <strong>Copyright &copy; 2023 <a href="https://kiu.co.id">PT.Karisma Indoagro Universal</a>.</strong>
    All rights reserved.
    <div class="float-right d-none d-sm-inline-block">
        <b>Version</b> BETA
    </div>
</footer>

<!-- /.control-sidebar -->
</div>
<!-- ./wrapper -->

<!-- jQuery -->
<script src="<?= base_url('assets/plugins/jquery/jquery.min.js') ?>"></script>
<!-- jQuery UI 1.11.4 -->
<script src="<?= base_url('assets/plugins/jquery-ui/jquery-ui.min.js') ?>"></script>
<!-- Bootstrap 4 -->
<script src="<?= base_url('assets/plugins/bootstrap/js/bootstrap.bundle.min.js') ?>"></script>
<!-- ChartJS -->
<script src="<?= base_url('assets/plugins/chart.js/Chart.min.js') ?>"></script>
<script src="<?php echo base_url('assets/dist/js/adminlte.js') ?>"></script>

<!-- Sweet Alert 2-->
<script src="<?= base_url('assets/plugins/sweetalert2/sweetalert2.min.js') ?>"></script>

<!-- DataTables  & Plugins -->
<script src="<?= base_url('assets/plugins/datatables/jquery.dataTables.min.js') ?>"></script>
<script src="<?= base_url('assets/plugins/datatables-bs4/js/dataTables.bootstrap4.min.js') ?>"></script>
<script src="<?= base_url('assets/plugins/datatables-responsive/js/dataTables.responsive.min.js') ?>"></script>
<script src="<?= base_url('assets/plugins/datatables-responsive/js/responsive.bootstrap4.min.js') ?>"></script>
<script src="<?= base_url('assets/plugins/datatables-buttons/js/dataTables.buttons.min.js') ?>"></script>
<script src="<?= base_url('assets/plugins/datatables-buttons/js/buttons.bootstrap4.min.js') ?>"></script>
<script src="<?= base_url('assets/plugins/datatables-buttons/js/buttons.html5.min.js') ?>"></script>
<script src="<?= base_url('assets/plugins/datatables-buttons/js/buttons.print.min.js') ?>"></script>
<script src="<?= base_url('assets/plugins/datatables-buttons/js/buttons.colVis.min.js') ?>"></script>

<script src="<?= base_url('assets/plugins/ekko-lightbox/ekko-lightbox.min.js') ?>"></script>


<!-- JS Date Range Picker -->
<script src="<?= base_url('assets/plugins/daterangepicker/daterangepicker.js') ?>"></script>

<!-- bs-custom-file-input -->
<script src="<?= base_url('assets/plugins/bs-custom-file-input/bs-custom-file-input.min.js') ?>"></script>

<?php $this->load->view('partial/pojasa_notification_polling'); ?>



</body>

</html>
<script>
    $(document).ready(function() {

        $(".tax_isi_value ").on("change", function() {
            var $el = $(this).closest('select')
            var ppn = $("this").val();
            var hasil = ppn / 100;
            $('.hasil_ppn').val(hasil);
        });

    });

    $(function() {
        bsCustomFileInput.init();
        $('#reservation').daterangepicker()

        $(document).on('click', '[data-toggle="lightbox"]', function(event) {
            event.preventDefault();
            $(this).ekkoLightbox({
                alwaysShowClose: true
            });
        });

        $('.btn[data-filter]').on('click', function() {
            $('.btn[data-filter]').removeClass('active');
            $(this).addClass('active');
        });
    })
</script>
<script>
    $(document).ready(function() {
        var table = $('#tabel-stock').DataTable({
            processing: true,
            serverSide: false,
            ajax: {
                url: '<?= base_url("laporan/C_Laporan/get_allstock_ajax") ?>',
                type: 'POST',
                data: function(d) {
                    d.tglstart = $('#tglstart').val();
                    d.tglend = $('#tglend').val();
                },
                dataSrc: 'data'
            },
            columns: [{
                    data: 0
                },
                {
                    data: 1
                },
                {
                    data: 2
                },
                {
                    data: 3
                },
                {
                    data: 4
                },
                {
                    data: 5
                },
                {
                    data: 6,
                    render: function(data, type, row) {
                        // data disini adalah nilai jn_transaksi (11512, dll)
                        switch (data) {
                            case '11512':
                                return '<button class="btn btn-block bg-cstm1 btn-sm color-palette">Pengurangan Barang</button>';
                            case '11511':
                                return '<button class="btn btn-block btn-sm bg-cstm2 color-palette">Penambahan Barang</button>';
                            case '11513':
                                return '<button class="btn btn-block btn-sm bg-cstm3 color-palette">Adjustmen Stock(+)</button>';
                            case '11514':
                                return '<button class="btn btn-block btn-sm bg-cstm4 color-palette">Adjustmen Stock(-)</button>';
                            default:
                                return '<button class="btn btn-block btn-sm btn-secondary color-palette">Lainnya</button>';
                        }
                    }
                }
            ],
            searching: true,
            paging: true
        });

        $('#formFilter').on('submit', function(e) {
            e.preventDefault();
            table.ajax.reload();
        });

        $('#btnExportExcel').click(function(e) {
            e.preventDefault();
            const tglstart = $('#tglstart').val();
            const tglend = $('#tglend').val();

            if (!tglstart || !tglend) {
                alert("Tanggal harus diisi!");
                return;
            }

            const url = `<?= base_url('exported_tr_allnk') ?>?tglstart=${tglstart}&tglend=${tglend}`;
            window.location.href = url;
        });

    });
</script>
