<script>
    $(function() {
        $("#list_reqpic").DataTable({
            "pageLength": 5,
            "responsive": true,
            "lengthChange": false,
            "autoWidth": false,
            "order": [] // tidak auto sort saat pertama load
        });
    });
</script>