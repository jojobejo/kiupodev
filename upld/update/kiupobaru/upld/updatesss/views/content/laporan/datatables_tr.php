<script>
    $(document).ready(function() {
        var table = $('#tabel-stock').DataTable({
            processing: true,
            serverSide: false,
            ajax: {
                url: '<?= base_url("get_allstock_ajax") ?>',
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
                    data: 6
                }
            ],
            searching: false,
            paging: true
        });

        $('#formFilter').on('submit', function(e) {
            e.preventDefault();
            table.ajax.reload();
        });
    });
</script>