<body class="hold-transition login-page">
    <div class="login-box">
        <div class="login-logo">
            <img src="" alt="">
        </div>
        <!-- /.login-logo -->
        <div class="card">
            <div class="card-body login-card-body">
                <p class="login-box-msg">Sign in terlebih dahulu</p>
                <form action="<?= base_url('login') ?>" method="post">
                    <div class="input-group mb-3">
                        <input type="text" class="form-control" placeholder="Username" id="user_isi" name="user_isi">
                        <div class="input-group-append">
                            <div class="input-group-text">
                                <span class="fas fa-user"></span>
                            </div>
                        </div>
                    </div>
                    <div class="input-group mb-3">
                        <input type="password" class="form-control" placeholder="Password" id="pass_isi" name="pass_isi">
                        <div class="input-group-append">
                            <div class="input-group-text">
                                <span class="fas fa-lock"></span>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md">
                            <button type="submit" class="btn btn-primary btn-block">Sign In</button>
                        </div>
                    </div>
                </form>
                <?php if ($this->session->flashdata("gagal")) : ?>
                    <div class="alert alert-danger alert-dismissible fade show mt-3" role="alert">
                        <?php echo "<center>" . $this->session->flashdata("gagal") . "</center>" ?>
                    </div>
                <?php elseif ($this->session->flashdata("logout")) : ?>
                    <div class="alert alert-success alert-dismissible fade show mt-3" role="alert">
                        <?php echo "<center>" . $this->session->flashdata("logout") . "</center>" ?>
                    </div>
                    <?php elseif ($this->session->flashdata("edited")) : ?>
                    <div class="alert alert-warning alert-dismissible fade show mt-3" role="alert">
                        <?php echo "<center>" . $this->session->flashdata("edited") . "</center>" ?>
                    </div>
                <?php endif ?>
            </div>
            <!-- /.login-card-body -->
        </div>
    </div>
    <!-- /.login-box -->