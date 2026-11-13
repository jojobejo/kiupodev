<div class="content-wrapper">
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0">User Management</h1>
                </div><!-- /.col -->
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="<?= base_url('dashboard') ?>">Dashboard</a></li>
                        <li class="breadcrumb-item active">User Management</li>
                    </ol>
                </div><!-- /.col -->
            </div><!-- /.row -->
            <?php if ($this->session->flashdata('success')) : ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?= $this->session->flashdata('success') ?>
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
            <?php endif; ?>
            <?php if ($this->session->flashdata('gagal')) : ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?= $this->session->flashdata('gagal') ?>
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
            <?php endif; ?>
            <div>
                <?php $this->load->view('content/User/modal/modalUser', array('user' => $user)) ?>
                <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#modalAddUser">
                    <i class="fas fa-plus"></i>
                    Tambah User Baru
                </button>
            </div>
            <table class="table table-bordered table-striped" id="tbuser">
                <thead>
                    <tr>
                        <td>Kode User</td>
                        <td>Nama Pengguna</td>
                        <td>Username</td>
                        <td>Departemen</td>
                        <td>Level</td>
                        <td>Aksi</td>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($user as $s) : ?>
                        <tr>
                            <td><?= html_escape($s->kode_user) ?></td>
                            <td><?= html_escape($s->nama_user) ?></td>
                            <td><?= html_escape($s->username) ?></td>
                            <td><?= html_escape($s->departement) ?></td>
                            <td>
                                <?php if ((string) $s->aksess_lv === '1') : ?>
                                    <span class="badge badge-danger">Admin</span>
                                <?php elseif ((string) $s->aksess_lv === '2') : ?>
                                    <span class="badge badge-primary">Purchasing/Keuangan</span>
                                <?php elseif ((string) $s->aksess_lv === '3') : ?>
                                    <span class="badge badge-info">Direktur</span>
                                <?php elseif ((string) $s->aksess_lv === '4') : ?>
                                    <span class="badge badge-secondary">PIC/Inputer</span>
                                <?php elseif ((string) $s->aksess_lv === '5') : ?>
                                    <span class="badge badge-warning">Kadep</span>
                                <?php else : ?>
                                    <span class="badge badge-light"><?= html_escape($s->aksess_lv) ?></span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <a href="#" class="btn btn-primary btn-sm" data-toggle="modal" data-target="#modalViewUser<?= $s->id_user ?>">
                                    <i class="fa fa-solid fa-eye"></i>
                                </a>
                                <a href="#" class="btn btn-warning btn-sm" data-toggle="modal" data-target="#modalEditUser<?= $s->id_user ?>">
                                    <i class="fa fa-solid fa-pencil-alt"></i>
                                </a>
                                <?php if ((string) $s->id_user !== (string) $this->session->userdata('id')) : ?>
                                <a href="#" class="btn btn-danger btn-sm" data-toggle="modal" data-target="#modalDeleteUser<?= $s->id_user ?>">
                                    <i class="fa fa-solid fa-trash-alt"></i>
                                </a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

        </div><!-- /.container-fluid -->
    </div>
    <!-- /.content-header -->
</div>
