<nav class="main-header navbar navbar-expand navbar-white navbar-light">
  <!-- Left navbar links -->
  <ul class="navbar-nav">
    <li class="nav-item">
      <a class="nav-link" data-widget="pushmenu" href="#" role="button"><i class="fas fa-bars"></i></a>
    </li>
    <li class="nav-item d-none d-sm-inline-block">
      <a href="<?= base_url('dashboard') ?>" class="nav-link">Home</a>
    </li>
  </ul>
</nav>

<aside class="main-sidebar sidebar-dark-primary elevation-4">
  <a href="<?= base_url('dashboard') ?>" class="brand-link">
    <img src="<?= base_url('assets/images/Karisma.png') ?>" alt="AdminLTE Logo" class="brand-image img-circle elevation-3" style="opacity: .8">
    <span class="brand-text font-weight-light">Purchase Order</span>
  </a>
  <!-- Sidebar -->
  <div class="sidebar">
    <!-- Sidebar Menu -->
    <nav class="mt-2">
      <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu" data-accordion="false">
        <!-- Add icons to the links using the .nav-icon class
               with font-awesome or any other icon font library -->
        <div class="user-panel mt-3 pb-3 mb-3 d-flex">
          <div class="image">
            <img src="<?= base_url('assets/images/user.png') ?>" class="img-circle elevation-1" alt="User Image">
          </div>
          <div class="info">
            <a href="#" class="d-block"><?= $this->session->userdata('nama_user') ?></a>
          </div>
        </div>
        <li class="nav-item">
          <a href="<?= base_url('logout') ?>" class="nav-link">
            <i class="nav-icon fas fa-pallet"></i>
            <p>
              Master Barang
            </p>
          </a>
        </li>
        <li class="nav-item">
          <a href="<?= base_url('logout') ?>" class="nav-link">
            <i class="nav-icon fas fa-boxes"></i>
            <p>
              ICS Tracking
            </p>
          </a>
        </li>
        <li class="nav-item">
          <a href="<?= base_url('logout') ?>" class="nav-link">
            <i class="nav-icon fas fa-dolly-flatbed"></i>
            <p>
              Daily ICS
            </p>
          </a>
        </li>
        <li class="nav-item">
          <a href="<?= base_url('logout') ?>" class="nav-link">
            <i class="nav-icon fas fa-unlock"></i>
            <p>
              Log Out
            </p>
          </a>
        </li>
      </ul>
    </nav>
    <!-- /.sidebar-menu -->
  </div>
  <!-- /.sidebar -->
</aside>