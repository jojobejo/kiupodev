<script>
    $(function() {

        var dbzahir;

        dbtracking = $('#dbtracking').DataTable({
            "responsive": true,
            "lengthChange": false,
            "autoWidth": false,
            "processing": true,
            "serverSide": true,
            "order": [],


            "ajax": {
                "url": "<?php echo site_url('serverInputer') ?>",
                "type": "POST"
            },

            "columnDefs": [{
                "targets": [0],
                "orderable": false,
            }, ],
        }).column(0).visible(false).buttons().container().appendTo('#example1_wrapper .col-md-6:eq(0)');

    });
</script>