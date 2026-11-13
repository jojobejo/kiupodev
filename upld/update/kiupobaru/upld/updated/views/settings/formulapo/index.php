<div class="content-wrapper">
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0">Formula PO</h1>
                </div>
                <div class="col-sm-6 text-right">
                    <button type="button" class="btn btn-success btn-sm" id="btnAddFormula">
                        <i class="fas fa-plus"></i> Tambah Formula
                    </button>
                </div>
            </div>
        </div>
    </section>

    <section class="content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-lg-8">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Daftar Formula</h3>
                        </div>
                        <div class="card-body table-responsive">
                            <table class="table table-bordered table-striped" id="tableFormula">
                                <thead>
                                    <tr>
                                        <th>Kode</th>
                                        <th>Nama</th>
                                        <th>Expression</th>
                                        <th>Output</th>
                                        <th>Status</th>
                                        <th style="width: 140px;">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody></tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Test Formula</h3>
                        </div>
                        <div class="card-body">
                            <div class="form-group">
                                <label>Formula</label>
                                <select class="form-control form-control-sm" id="test_id_formula">
                                    <option value="">Pilih Formula</option>
                                </select>
                            </div>
                            <form id="formTestFormula">
                                <div id="testVariableArea"></div>
                                <button type="submit" class="btn btn-primary btn-sm btn-block">
                                    <i class="fas fa-calculator"></i> Hitung
                                </button>
                            </form>
                            <div class="alert alert-info mt-3 d-none" id="testResultBox">
                                <strong id="testResultLabel"></strong>
                                <div class="h4 mb-0" id="testResultValue"></div>
                                <small id="testExpression"></small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>

<div class="modal fade" id="modalFormula" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-xl" role="document">
        <div class="modal-content">
            <form id="formFormula">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalFormulaTitle">Tambah Formula</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="id_formula" id="id_formula">
                    <div class="row">
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>Kode Formula</label>
                                <input type="text" class="form-control form-control-sm" name="kode_formula" id="kode_formula" required>
                            </div>
                        </div>
                        <div class="col-md-5">
                            <div class="form-group">
                                <label>Nama Formula</label>
                                <input type="text" class="form-control form-control-sm" name="nama_formula" id="nama_formula" required>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="form-group">
                                <label>Rounding</label>
                                <select class="form-control form-control-sm" name="rounding_mode" id="rounding_mode">
                                    <option value="none">None</option>
                                    <option value="round">Round</option>
                                    <option value="ceil">Ceil</option>
                                    <option value="floor">Floor</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="form-group">
                                <label>Decimal</label>
                                <input type="number" class="form-control form-control-sm" name="decimal_place" id="decimal_place" value="2" min="0" max="6">
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Deskripsi</label>
                        <textarea class="form-control form-control-sm" name="deskripsi" id="deskripsi" rows="2"></textarea>
                    </div>

                    <div class="form-group">
                        <label>Formula Expression</label>
                        <textarea class="form-control form-control-sm" name="formula_expression" id="formula_expression" rows="2" required></textarea>
                    </div>

                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Output Label</label>
                                <input type="text" class="form-control form-control-sm" name="output_label" id="output_label" required>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Output Unit</label>
                                <input type="text" class="form-control form-control-sm" name="output_unit" id="output_unit">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Status</label>
                                <select class="form-control form-control-sm" name="status" id="status">
                                    <option value="1">Aktif</option>
                                    <option value="0">Tidak Aktif</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <h6 class="mb-0">Variable Input</h6>
                        <button type="button" class="btn btn-info btn-sm" id="btnAddVariable">
                            <i class="fas fa-plus"></i> Tambah Variable
                        </button>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-bordered table-sm" id="tableVariable">
                            <thead>
                                <tr>
                                    <th>Key</th>
                                    <th>Label</th>
                                    <th>Type</th>
                                    <th>Unit</th>
                                    <th>Default</th>
                                    <th>Required</th>
                                    <th>Sort</th>
                                    <th style="width: 50px;">Aksi</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">Tutup</button>
                    <button type="submit" class="btn btn-primary btn-sm">
                        <i class="fas fa-save"></i> Simpan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        var $ = window.jQuery;
        if (!$) {
            return;
        }

        var urlList = '<?= site_url('settings/C_Formulapo/ajax_list_formula') ?>';
        var urlGet = '<?= site_url('settings/C_Formulapo/ajax_get_formula') ?>';
        var urlSave = '<?= site_url('settings/C_Formulapo/ajax_save_formula') ?>';
        var urlDelete = '<?= site_url('settings/C_Formulapo/ajax_delete_formula') ?>';
        var urlVariables = '<?= site_url('settings/C_Formulapo/ajax_get_variables') ?>';
        var urlCalculate = '<?= site_url('settings/C_Formulapo/ajax_calculate') ?>';

        loadFormula();

        $('#btnAddFormula').on('click', function() {
            resetForm();
            $('#modalFormulaTitle').text('Tambah Formula');
            addVariableRow();
            $('#modalFormula').modal('show');
        });

        $('#btnAddVariable').on('click', function() {
            addVariableRow();
        });

        $('#tableVariable').on('click', '.btnRemoveVariable', function() {
            $(this).closest('tr').remove();
        });

        $('#tableFormula').on('click', '.btnEdit', function() {
            var id = $(this).data('id');
            $.getJSON(urlGet + '/' + id, function(response) {
                if (!response.status) {
                    showAlert(response.message, 'error');
                    return;
                }

                resetForm();
                $('#modalFormulaTitle').text('Edit Formula');
                fillFormula(response.data.formula, response.data.variables);
                $('#modalFormula').modal('show');
            });
        });

        $('#tableFormula').on('click', '.btnDelete', function() {
            var id = $(this).data('id');
            if (!confirm('Hapus formula ini?')) {
                return;
            }

            $.post(urlDelete, {
                id_formula: id
            }, function(response) {
                if (response.status) {
                    showAlert('Formula berhasil dihapus', 'success');
                    loadFormula();
                } else {
                    showAlert(response.message, 'error');
                }
            }, 'json');
        });

        $('#formFormula').on('submit', function(e) {
            e.preventDefault();
            $.post(urlSave, $(this).serialize(), function(response) {
                if (response.status) {
                    showAlert('Formula berhasil disimpan', 'success');
                    $('#modalFormula').modal('hide');
                    loadFormula();
                } else {
                    showAlert(response.message, 'error');
                }
            }, 'json');
        });

        $('#test_id_formula').on('change', function() {
            var id = $(this).val();
            $('#testResultBox').addClass('d-none');
            $('#testVariableArea').html('');

            if (id == '') {
                return;
            }

            $.getJSON(urlVariables + '/' + id, function(response) {
                if (!response.status) {
                    showAlert(response.message, 'error');
                    return;
                }

                var html = '';
                $.each(response.data.variables, function(index, item) {
                    var defaultValue = item.default_value !== null ? item.default_value : '';
                    html += '<div class="form-group">';
                    html += '<label>' + escapeHtml(item.variable_label) + ' <small>' + escapeHtml(item.unit || '') + '</small></label>';
                    html += '<input type="number" step="any" class="form-control form-control-sm" name="input[' + escapeHtml(item.variable_key) + ']" value="' + escapeHtml(defaultValue) + '" ' + (item.is_required == 1 ? 'required' : '') + '>';
                    html += '</div>';
                });
                $('#testVariableArea').html(html);
            });
        });

        $('#formTestFormula').on('submit', function(e) {
            e.preventDefault();
            var id = $('#test_id_formula').val();
            if (id == '') {
                showAlert('Pilih formula terlebih dahulu', 'error');
                return;
            }

            $.post(urlCalculate, $(this).serialize() + '&id_formula=' + encodeURIComponent(id), function(response) {
                if (!response.status) {
                    showAlert(response.message, 'error');
                    return;
                }

                $('#testResultLabel').text(response.data.result_label + (response.data.result_unit ? ' (' + response.data.result_unit + ')' : ''));
                $('#testResultValue').text(response.data.result_value);
                $('#testExpression').text(response.data.expression);
                $('#testResultBox').removeClass('d-none');
            }, 'json');
        });

        function loadFormula() {
            $.getJSON(urlList, function(response) {
                if (!response.status) {
                    showAlert(response.message, 'error');
                    return;
                }

                var rows = '';
                var options = '<option value="">Pilih Formula</option>';
                $.each(response.data, function(index, item) {
                    rows += '<tr>';
                    rows += '<td>' + escapeHtml(item.kode_formula) + '</td>';
                    rows += '<td>' + escapeHtml(item.nama_formula) + '</td>';
                    rows += '<td><code>' + escapeHtml(item.formula_expression) + '</code></td>';
                    rows += '<td>' + escapeHtml(item.output_label) + ' ' + escapeHtml(item.output_unit || '') + '</td>';
                    rows += '<td>' + (item.status == 1 ? '<span class="badge badge-success">Aktif</span>' : '<span class="badge badge-secondary">Tidak Aktif</span>') + '</td>';
                    rows += '<td>';
                    rows += '<button type="button" class="btn btn-warning btn-xs btnEdit mr-1" data-id="' + item.id_formula + '"><i class="fas fa-edit"></i> Edit</button>';
                    rows += '<button type="button" class="btn btn-danger btn-xs btnDelete" data-id="' + item.id_formula + '"><i class="fas fa-trash"></i> Hapus</button>';
                    rows += '</td>';
                    rows += '</tr>';

                    if (item.status == 1) {
                        options += '<option value="' + item.id_formula + '">' + escapeHtml(item.kode_formula) + ' - ' + escapeHtml(item.nama_formula) + '</option>';
                    }
                });

                $('#tableFormula tbody').html(rows);
                $('#test_id_formula').html(options);
            });
        }

        function resetForm() {
            $('#formFormula')[0].reset();
            $('#id_formula').val('');
            $('#decimal_place').val('2');
            $('#status').val('1');
            $('#tableVariable tbody').html('');
        }

        function fillFormula(formula, variables) {
            $('#id_formula').val(formula.id_formula);
            $('#kode_formula').val(formula.kode_formula);
            $('#nama_formula').val(formula.nama_formula);
            $('#deskripsi').val(formula.deskripsi);
            $('#formula_expression').val(formula.formula_expression);
            $('#output_label').val(formula.output_label);
            $('#output_unit').val(formula.output_unit);
            $('#rounding_mode').val(formula.rounding_mode);
            $('#decimal_place').val(formula.decimal_place);
            $('#status').val(formula.status);

            $.each(variables, function(index, variable) {
                addVariableRow(variable);
            });
        }

        function addVariableRow(variable) {
            variable = variable || {};
            var sortOrder = variable.sort_order || ($('#tableVariable tbody tr').length + 1);
            var html = '<tr>';
            html += '<td><input type="text" class="form-control form-control-sm" name="variable_key[]" value="' + escapeHtml(variable.variable_key || '') + '" required></td>';
            html += '<td><input type="text" class="form-control form-control-sm" name="variable_label[]" value="' + escapeHtml(variable.variable_label || '') + '" required></td>';
            html += '<td><select class="form-control form-control-sm" name="input_type[]">';
            html += '<option value="number">Number</option><option value="decimal">Decimal</option><option value="currency">Currency</option>';
            html += '</select></td>';
            html += '<td><input type="text" class="form-control form-control-sm" name="unit[]" value="' + escapeHtml(variable.unit || '') + '"></td>';
            html += '<td><input type="number" step="any" class="form-control form-control-sm" name="default_value[]" value="' + escapeHtml(variable.default_value == null ? '' : variable.default_value) + '"></td>';
            html += '<td class="text-center"><input type="hidden" name="is_required[]" value="0"><input type="checkbox" class="is-required-checkbox" value="1" ' + (variable.is_required == 0 ? '' : 'checked') + '></td>';
            html += '<td><input type="number" class="form-control form-control-sm" name="sort_order[]" value="' + escapeHtml(sortOrder) + '"></td>';
            html += '<td><button type="button" class="btn btn-danger btn-xs btnRemoveVariable"><i class="fas fa-trash"></i></button></td>';
            html += '</tr>';

            $('#tableVariable tbody').append(html);
            $('#tableVariable tbody tr:last select[name="input_type[]"]').val(variable.input_type || 'decimal');
            updateRequiredNames();
        }

        $('#tableVariable').on('change', '.is-required-checkbox', function() {
            updateRequiredNames();
        });

        function updateRequiredNames() {
            $('#tableVariable tbody tr').each(function() {
                var checkbox = $(this).find('.is-required-checkbox');
                checkbox.attr('name', 'is_required[]');
                checkbox.prev('input[type="hidden"]').prop('disabled', checkbox.is(':checked'));
            });
        }

        function showAlert(message, type) {
            if (typeof Swal !== 'undefined') {
                Swal.fire(type == 'success' ? 'Success' : 'Error', message, type);
            } else {
                alert(message);
            }
        }

        function escapeHtml(value) {
            return $('<div/>').text(value == null ? '' : value).html();
        }
    });
</script>
