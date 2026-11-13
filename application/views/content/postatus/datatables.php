<script>
    $(function() {
        $("#tballstatus").DataTable({
            "responsive": true,
            "lengthChange": false,
            "autoWidth": false,
        }).buttons().container().appendTo('#example1_wrapper .col-md-6:eq(0)');

        $("#tbdone").DataTable({
            "responsive": true,
            "lengthChange": false,
            "pageLength": 5,
            "autoWidth": false,
        }).buttons().container().appendTo('#example1_wrapper .col-md-6:eq(0)');

        $("#tbprogress").DataTable({
            "responsive": true,
            "lengthChange": false,
            "pageLength": 5,
            "autoWidth": false,
        }).buttons().container().appendTo('#example1_wrapper .col-md-6:eq(0)');

        $("#tbreject").DataTable({
            "responsive": true,
            "lengthChange": false,
            "pageLength": 5,
            "autoWidth": false,
        }).buttons().container().appendTo('#example1_wrapper .col-md-6:eq(0)');
        $("#tbhistori").DataTable({
            "responsive": true,
            "lengthChange": false,
            "pageLength": 10,
            "autoWidth": false,
        }).buttons().container().appendTo('#example1_wrapper .col-md-6:eq(0)');
        $("#tbusersts").DataTable({
            "responsive": true,
            "lengthChange": false,
            "pageLength": 10,
            "autoWidth": false,
        }).buttons().container().appendTo('#example1_wrapper .col-md-6:eq(0)');
    });
</script>