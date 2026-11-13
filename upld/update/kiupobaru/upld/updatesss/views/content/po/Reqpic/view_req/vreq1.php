<div class="content-wrapper">
    <div class="content-header">
        <div class="container-fluid">
            <div class="card">
                <div class="card-body">
                    <div class="row mb-2">
                        <div class="col-sm-4">
                            <h1 class="m-0">List Request PIC</h1>
                        </div>
                        <div class="col-sm mb-2">
                            <a href="<?= base_url('reqpic') ?>" class="btn btn-md btn-primary btn-block"><i class="fas fa-home"></i></a>
                        </div>
                        <div class="col-sm mb-2">
                            <button type="button" class="btn btn-md btn-warning btn-block js-request-scope active" data-scope="request_acc"><b>REQUEST ACC</b></button>
                        </div>
                        <div class="col-sm mb-2">
                            <button type="button" class="btn btn-md btn-secondary btn-block js-request-scope" data-scope="all"><b>ALL PENGAJUAN</b></button>
                        </div>
                        <div class="col-sm mb-2">
                            <a href="<?= base_url('index_brsedia') ?>" class="btn btn-md btn-info btn-block"><b>BARANG TERSEDIA</b></a>
                        </div>
                        <div class="col-sm mb-2">
                            <a href="<?= base_url('index_done') ?>" class="btn btn-md btn-success btn-block"><b>DONE</b></a>
                        </div>
                    </div> <!-- END ROW -->
                    <table class="table table-bordered" id="list_reqpic">
                        <thead class="table-dark">
                            <tr>
                                <td>Nama Pengaju</td>
                                <td>Departemen</td>
                                <td>Tanggal Transaksi</td>
                                <td>Jam</td>
                                <td style="width: min-content;">Tujuan Pembelian</td>
                                <td>Status</td>
                                <td>Status PO</td>
                                <td>#</td>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>

                </div> <!-- END CARD BODY -->
            </div> <!-- END CARD -->
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var table = $('#list_reqpic').DataTable({
        responsive: true,
        lengthChange: false,
        autoWidth: false,
        pageLength: 10,
        order: [],
        ajax: {
            url: <?= json_encode(base_url('reqpic/ajax/request-list')) ?>,
            data: function (data) { data.scope = $('#list_reqpic').data('scope') || 'request_acc'; }
        }
    });

    $(document).on('click', '.js-request-scope', function () {
        var button = $(this);
        $('#list_reqpic').data('scope', button.data('scope'));
        $('.js-request-scope').removeClass('active btn-warning').addClass('btn-secondary');
        button.removeClass('btn-secondary').addClass('active btn-warning');
        table.ajax.reload();
    });
});
</script>
