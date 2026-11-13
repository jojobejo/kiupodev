<div class="content-wrapper">
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0">Admin View Data</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="<?= base_url('dashboard') ?>">Dashboard</a></li>
                        <li class="breadcrumb-item active">Admin View Data</li>
                    </ol>
                </div>
            </div>

            <?php foreach (array('success' => 'success', 'error' => 'danger') as $flashKey => $alertClass) : ?>
                <?php if ($this->session->flashdata($flashKey)) : ?>
                    <div class="alert alert-<?= $alertClass ?> alert-dismissible fade show" role="alert">
                        <?= html_escape($this->session->flashdata($flashKey)) ?>
                        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>
    </div>

    <section class="content">
        <div class="container-fluid">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Data Tanpa Filter: <?= html_escape($active_table['label']) ?></h3>
                </div>
                <div class="card-body">
                    <div class="btn-group mb-3" role="group" aria-label="Limit data admin">
                        <a href="<?= base_url('admin-data/table/' . $active_key) ?>?limit=500" class="btn btn-sm <?= $limit === '500' ? 'btn-secondary' : 'btn-outline-secondary' ?>">500</a>
                        <a href="<?= base_url('admin-data/table/' . $active_key) ?>?limit=2000" class="btn btn-sm <?= $limit === '2000' ? 'btn-secondary' : 'btn-outline-secondary' ?>">2000</a>
                        <a href="<?= base_url('admin-data/table/' . $active_key) ?>?limit=all" class="btn btn-sm <?= $limit === 'all' ? 'btn-secondary' : 'btn-outline-secondary' ?>">Semua Data</a>
                    </div>
                    <div class="row mb-3">
                        <?php foreach ($tables as $key => $table) : ?>
                            <div class="col-md-3 col-sm-6 mb-2">
                                <a href="<?= base_url('admin-data/table/' . $key) ?>?limit=<?= html_escape($limit) ?>" class="btn btn-sm btn-block <?= $key === $active_key ? 'btn-primary' : 'btn-outline-primary' ?>">
                                    <?= html_escape($table['label']) ?>
                                </a>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-bordered table-striped table-sm" id="adminDataTable">
                            <thead class="thead-dark">
                                <tr>
                                    <?php foreach ($columns as $column) : ?>
                                        <th><?= html_escape($column->name) ?></th>
                                    <?php endforeach; ?>
                                    <th style="width: 90px;">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($rows as $row) : ?>
                                    <?php
                                    $rowArray = (array) $row;
                                    $encodedRow = base64_encode(json_encode($rowArray, JSON_UNESCAPED_SLASHES));
                                    ?>
                                    <tr>
                                        <?php foreach ($columns as $column) : ?>
                                            <?php
                                            $value = isset($rowArray[$column->name]) ? (string) $rowArray[$column->name] : '';
                                            $shortValue = strlen($value) > 80 ? substr($value, 0, 80) . '...' : $value;
                                            ?>
                                            <td><?= html_escape($shortValue) ?></td>
                                        <?php endforeach; ?>
                                        <td>
                                            <?php if (!empty($active_table['primary_key'])) : ?>
                                                <button type="button" class="btn btn-warning btn-sm btn-edit-admin-data" data-row="<?= $encodedRow ?>">
                                                    <i class="fas fa-pencil-alt"></i>
                                                    Edit
                                                </button>
                                            <?php else : ?>
                                                <span class="badge badge-secondary">View Only</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <small class="text-muted">Data ditampilkan tanpa filter departemen, username, user, atau role. Tabel tanpa primary key disajikan view only.</small>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Log Aktivitas Admin</h3>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped table-sm" id="adminLogTable">
                            <thead class="thead-dark">
                                <tr>
                                    <th>Tanggal</th>
                                    <th>User</th>
                                    <th>Tabel</th>
                                    <th>Primary Key</th>
                                    <th>Aksi</th>
                                    <th>Data Lama</th>
                                    <th>Data Baru</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($logs as $log) : ?>
                                    <tr>
                                        <td><?= html_escape($log->created_at) ?></td>
                                        <td><?= html_escape($log->kode_user . ' - ' . $log->nama_user) ?></td>
                                        <td><?= html_escape($log->table_label . ' (' . $log->table_name . ')') ?></td>
                                        <td><?= html_escape($log->primary_key . ' = ' . $log->primary_value) ?></td>
                                        <td><?= html_escape($log->action) ?></td>
                                        <td><code><?= html_escape($log->old_data) ?></code></td>
                                        <td><code><?= html_escape($log->new_data) ?></code></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>

<div class="modal fade" id="modalEditAdminData" tabindex="-1" role="dialog" aria-labelledby="modalEditAdminDataLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl" role="document">
        <?php echo form_open('admin-data/update', array('class' => 'modal-content')); ?>
            <div class="modal-header">
                <h5 class="modal-title" id="modalEditAdminDataLabel">Edit <?= html_escape($active_table['label']) ?></h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <input type="hidden" name="table_key" value="<?= html_escape($active_key) ?>">
                <input type="hidden" name="pk_value" id="adminDataPkValue" value="">
                <div class="row" id="adminDataFields"></div>
            </div>
            <div class="modal-footer justify-content-between">
                <button type="button" class="btn btn-default" data-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i>
                    Simpan Perubahan
                </button>
            </div>
        </form>
    </div>
</div>
