<script>
    $(function() {
        var columns = <?= json_encode(array_map(function ($column) use ($active_table) {
            return array(
                'name' => $column->name,
                'type' => $column->type,
                'primary' => $column->name === $active_table['primary_key'],
                'auto_increment' => $column->name === $active_table['primary_key']
            );
        }, $columns), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
        var primaryKey = <?= json_encode($active_table['primary_key']) ?>;

        $('#adminDataTable').DataTable({
            responsive: false,
            lengthChange: true,
            autoWidth: false,
            scrollX: true,
            pageLength: 25
        });

        $('#adminLogTable').DataTable({
            responsive: false,
            lengthChange: true,
            autoWidth: false,
            scrollX: true,
            order: [[0, 'desc']],
            pageLength: 10
        });

        $('.btn-edit-admin-data').on('click', function() {
            var row = JSON.parse(atob($(this).attr('data-row')));
            var $fields = $('#adminDataFields');
            $fields.empty();
            $('#adminDataPkValue').val(row[primaryKey]);

            columns.forEach(function(column) {
                var value = row[column.name] === null || row[column.name] === undefined ? '' : row[column.name];
                var readonly = column.primary && column.auto_increment ? ' readonly' : '';
                var help = column.primary && column.auto_increment ? '<small class="text-muted">Primary key auto increment dipakai sebagai identitas baris.</small>' : '';
                var inputName = column.primary && column.auto_increment ? '' : ' name="fields[' + column.name + ']"';
                var fieldHtml = '';

                if (String(value).length > 120 || column.type === 'text' || column.type === 'mediumtext' || column.type === 'longtext') {
                    fieldHtml = '<textarea class="form-control" rows="3"' + inputName + readonly + '></textarea>';
                } else {
                    fieldHtml = '<input type="text" class="form-control"' + inputName + readonly + '>';
                }

                var $wrap = $('<div class="col-md-6 mb-3">' +
                    '<label></label>' +
                    fieldHtml +
                    help +
                    '</div>');

                $wrap.find('label').text(column.name);
                $wrap.find('.form-control').val(value);
                $fields.append($wrap);
            });

            $('#modalEditAdminData').modal('show');
        });
    });
</script>
