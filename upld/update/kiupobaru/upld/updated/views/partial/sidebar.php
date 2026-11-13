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
  <?php $this->load->view('partial/pojasa_notification_dropdown'); ?>
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
        <?php if (is_super_admin()) : ?>
          <li class="nav-header">ADMIN FULL AKSES</li>
          <li class="nav-item">
            <a href="#" class="nav-link">
              <i class="nav-icon fas fa-cash-register"></i>
              <p>
                Purchase Order
                <i class="right fas fa-angle-left"></i>
              </p>
            </a>
            <ul class="nav nav-treeview">
              <li class="nav-item">
                <a href="<?= base_url('purchase') ?>" class="nav-link">
                  <i class="far fa-circle nav-icon"></i>
                  <p>Komersil</p>
                </a>
              </li>
              <li class="nav-item">
                <a href="<?= base_url('pononkomersil') ?>" class="nav-link">
                  <i class="far fa-circle nav-icon"></i>
                  <p>Non Komersil Pembelian</p>
                </a>
              </li>
              <li class="nav-item">
                <a href="<?= base_url('pononkomersiljasa') ?>" class="nav-link">
                  <i class="far fa-circle nav-icon"></i>
                  <p>Non Komersil Jasa</p>
                </a>
              </li>
              <li class="nav-item">
                <a href="<?= base_url('reqpic') ?>" class="nav-link">
                  <i class="far fa-circle nav-icon"></i>
                  <p>PO Request PIC</p>
                </a>
              </li>
            </ul>
          </li>
          <li class="nav-item">
            <a href="#" class="nav-link">
              <i class="nav-icon fas fa-money-check"></i>
              <p>
                Purchase Order Status
                <i class="right fas fa-angle-left"></i>
              </p>
            </a>
            <ul class="nav nav-treeview">
              <li class="nav-item">
                <a href="<?= base_url('postatus') ?>" class="nav-link">
                  <i class="far fa-circle nav-icon"></i>
                  <p>Komersil</p>
                </a>
              </li>
              <li class="nav-item">
                <a href="<?= base_url('postatus/today') ?>" class="nav-link">
                  <i class="far fa-circle nav-icon"></i>
                  <p>Komersil Hari Ini</p>
                </a>
              </li>
              <li class="nav-item">
                <a href="<?= base_url('postatusnk') ?>" class="nav-link">
                  <i class="far fa-circle nav-icon"></i>
                  <p>Non Komersil</p>
                </a>
              </li>
              <li class="nav-item">
                <a href="<?= base_url('postatusallnk') ?>" class="nav-link">
                  <i class="far fa-circle nav-icon"></i>
                  <p>Non Komersil Semua</p>
                </a>
              </li>
              <li class="nav-item">
                <a href="<?= base_url('arsip-evident-ponk') ?>" class="nav-link">
                  <i class="far fa-circle nav-icon"></i>
                  <p>Arsip Evident PONK</p>
                </a>
              </li>
            </ul>
          </li>
          <li class="nav-item">
            <a href="#" class="nav-link">
              <i class="nav-icon fas fa-clipboard-check"></i>
              <p>
                Approval
                <i class="right fas fa-angle-left"></i>
              </p>
            </a>
            <ul class="nav nav-treeview">
              <li class="nav-item">
                <a href="<?= base_url('reqpicaccreq') ?>" class="nav-link">
                  <i class="far fa-circle nav-icon"></i>
                  <p>Approval Request Admin</p>
                </a>
              </li>
              <li class="nav-item">
                <a href="<?= base_url('reqpicacckadep') ?>" class="nav-link">
                  <i class="far fa-circle nav-icon"></i>
                  <p>Approval Request Kadep</p>
                </a>
              </li>
            </ul>
          </li>
          <li class="nav-item">
            <a href="#" class="nav-link">
              <i class="nav-icon fas fa-archive"></i>
              <p>
                Master Barang
                <i class="right fas fa-angle-left"></i>
              </p>
            </a>
            <ul class="nav nav-treeview">
              <li class="nav-item">
                <a href="<?= base_url('masterbarangkomersil') ?>" class="nav-link">
                  <i class="far fa-circle nav-icon"></i>
                  <p>Komersil</p>
                </a>
              </li>
              <li class="nav-item">
                <a href="<?= base_url('masterbarangnk') ?>" class="nav-link">
                  <i class="far fa-circle nav-icon"></i>
                  <p>Non Komersil</p>
                </a>
              </li>
              <li class="nav-item">
                <a href="<?= base_url('vrequestmbarang') ?>" class="nav-link">
                  <i class="far fa-circle nav-icon"></i>
                  <p>Request Master Barang</p>
                </a>
              </li>
            </ul>
          </li>
          <li class="nav-item">
            <a href="#" class="nav-link">
              <i class="nav-icon fas fa-cubes"></i>
              <p>
                Stok Barang
                <i class="right fas fa-angle-left"></i>
              </p>
            </a>
            <ul class="nav nav-treeview">
              <li class="nav-item">
                <a href="<?= base_url('stockkomersil') ?>" class="nav-link">
                  <i class="far fa-circle nav-icon"></i>
                  <p>Komersil</p>
                </a>
              </li>
              <li class="nav-item">
                <a href="<?= base_url('stocknonkomersil') ?>" class="nav-link">
                  <i class="far fa-circle nav-icon"></i>
                  <p>Non Komersil</p>
                </a>
              </li>
              <li class="nav-item">
                <a href="<?= base_url('stockopnamenk') ?>" class="nav-link">
                  <i class="far fa-circle nav-icon"></i>
                  <p>Stock Opname NK</p>
                </a>
              </li>
              <li class="nav-item">
                <a href="<?= base_url('master_lokasi') ?>" class="nav-link">
                  <i class="far fa-circle nav-icon"></i>
                  <p>Master Lokasi</p>
                </a>
              </li>
            </ul>
          </li>
          <li class="nav-item">
            <a href="#" class="nav-link">
              <i class="nav-icon fas fa-file-excel"></i>
              <p>
                Laporan
                <i class="right fas fa-angle-left"></i>
              </p>
            </a>
            <ul class="nav nav-treeview">
              <li class="nav-item">
                <a href="<?= base_url('lap_nonkomersil') ?>" class="nav-link">
                  <i class="far fa-circle nav-icon"></i>
                  <p>Laporan Pembelian</p>
                </a>
              </li>
              <li class="nav-item">
                <a href="<?= base_url('tr_allstock') ?>" class="nav-link">
                  <i class="far fa-circle nav-icon"></i>
                  <p>Laporan Transaksi Stock</p>
                </a>
              </li>
            </ul>
          </li>
          <li class="nav-item">
            <a href="#" class="nav-link">
              <i class="nav-icon fas fa-tools"></i>
              <p>
                Setting
                <i class="right fas fa-angle-left"></i>
              </p>
            </a>
            <ul class="nav nav-treeview">
              <li class="nav-item">
                <a href="<?= base_url('taxseting') ?>" class="nav-link">
                  <i class="far fa-circle nav-icon"></i>
                  <p>Tax Setting</p>
                </a>
              </li>
              <li class="nav-item">
                <a href="<?= base_url('satuansetting') ?>" class="nav-link">
                  <i class="far fa-circle nav-icon"></i>
                  <p>Satuan Setting</p>
                </a>
              </li>
              <li class="nav-item">
                <a href="<?= base_url('notetemplate') ?>" class="nav-link">
                  <i class="far fa-circle nav-icon"></i>
                  <p>Note Template</p>
                </a>
              </li>
              <li class="nav-item">
                <a href="<?= base_url('formulapo') ?>" class="nav-link">
                  <i class="far fa-circle nav-icon"></i>
                  <p>Formula PO</p>
                </a>
              </li>
              <li class="nav-item">
                <a href="<?= base_url('reviewapps') ?>" class="nav-link">
                  <i class="far fa-circle nav-icon"></i>
                  <p>Review Apps</p>
                </a>
              </li>
            </ul>
          </li>
          <li class="nav-item">
            <a href="<?= base_url('suplier') ?>" class="nav-link">
              <i class="nav-icon fas fa-truck"></i>
              <p>Suplier</p>
            </a>
          </li>
          <li class="nav-item">
            <a href="<?= base_url('usermanagement') ?>" class="nav-link">
              <i class="nav-icon fas fa-users-cog"></i>
              <p>User Management</p>
            </a>
          </li>
          <li class="nav-item">
            <a href="<?= base_url('admin-data') ?>" class="nav-link">
              <i class="nav-icon fas fa-database"></i>
              <p>Admin View Data</p>
            </a>
          </li>
          <li class="nav-item">
            <a href="<?= base_url('usersetting') ?>" class="nav-link">
              <i class="nav-icon fas fa-cogs"></i>
              <p>Account Setting</p>
            </a>
          </li>
          <li class="nav-item">
            <a href="<?= base_url('logout') ?>" class="nav-link">
              <i class="nav-icon fas fa-unlock"></i>
              <p>Log Out</p>
            </a>
          </li>
        <?php elseif ($this->session->userdata('lv') <= '2' && $this->session->userdata('departemen') == 'KEUANGAN') : ?>
          <li class="nav-item">
            <a href="#" class="nav-link">
              <i class="nav-icon fas fa-cash-register"></i>
              <p>
                Purchase Order
                <i class="right fas fa-angle-left"></i>
              </p>
            </a>
            <ul class="nav nav-treeview">
              <li class="nav-item">
                <a href="<?= base_url('purchase') ?>" class="nav-link">
                  <i class="far fa-circle nav-icon"></i>
                  <p>Komersil</p>
                </a>
              </li>
              <li class="nav-item">
                <a href="#" class="nav-link">
                  <i class="far fa-circle nav-icon"></i>
                  <p>
                    Non Komersil
                    <i class="right fas fa-angle-left"></i>
                  </p>
                </a>
                <ul class="nav nav-treeview" style="display: none;">
                  <li class="nav-item">
                    <a href="<?= base_url('pononkomersiljasa') ?>" class="nav-link">
                      <i class="far fa-dot-circle nav-icon"></i>
                      <p>PO Jasa</p>
                    </a>
                  </li>
                  <li class="nav-item">
                    <a href="<?= base_url('pononkomersil') ?>" class="nav-link">
                      <i class="far fa-dot-circle nav-icon"></i>
                      <p>PO Pembelian</p>
                    </a>
                  </li>
                  <li class="nav-item">
                    <a href="<?= base_url('reqpic') ?>" class="nav-link">
                      <i class="far fa-dot-circle nav-icon"></i>
                      <p>PO Request PIC</p>
                    </a>
                  </li>
                </ul>
              </li>
            </ul>
          </li>
          <li class="nav-item">
            <a href="#" class="nav-link">
              <i class="nav-icon fas fa-money-check"></i>
              <p>
                Purchase Order Status
                <i class="right fas fa-angle-left"></i>
              </p>
            </a>
            <ul class="nav nav-treeview">
              <li class="nav-item">
                <a href="<?= base_url('postatus') ?>" class="nav-link">
                  <i class="far fa-circle nav-icon"></i>
                  <p>Komersil</p>
                </a>
              </li>
              <li class="nav-item">
                <a href="#" class="nav-link">
                  <i class="far fa-circle nav-icon"></i>
                  <p>
                    Non Komersil
                    <i class="right fas fa-angle-left"></i>
                  </p>
                </a>
                <ul class="nav nav-treeview" style="display: none;">
                  <li class="nav-item">
                    <a href="<?= base_url('pononkomersiljasa') ?>" class="nav-link">
                      <i class="far fa-dot-circle nav-icon"></i>
                      <p>PO Jasa</p>
                    </a>
                  </li>
                  <li class="nav-item">
                    <a href="<?= base_url('postatusnk') ?>" class="nav-link">
                      <i class="far fa-dot-circle nav-icon"></i>
                      <p>PO Pembelian</p>
                    </a>
                  </li>
                </ul>
              </li>
            </ul>
          </li>
          <li class="nav-item">
            <a href="#" class="nav-link">
              <i class="nav-icon fas fa-archive"></i>
              <p>
                Master Barang
                <i class="right fas fa-angle-left"></i>
              </p>
            </a>
            <ul class="nav nav-treeview">
              <li class="nav-item">
                <a href="<?= base_url('masterbarangkomersil') ?>" class="nav-link">
                  <i class="far fa-circle nav-icon"></i>
                  <p>Komersil</p>
                </a>
              </li>
              <li class="nav-item">
                <a href="<?= base_url('masterbarangnk') ?>" class="nav-link">
                  <i class="far fa-circle nav-icon"></i>
                  <p>Non Komersil</p>
                </a>
              </li>
            </ul>
          </li>
          <!-- <li class="nav-item">
            <a href="<?= base_url('masterbarangnk') ?>" class="nav-link">
              <i class="nav-icon fas fa-box"></i>
              <p>
                Master Barang
              </p>
            </a>
          </li> -->
          <li class="nav-item">
            <a href="<?= base_url('vrequestmbarang') ?>" class="nav-link">
              <i class="nav-icon fas fa-box-open"></i>
              <p>
                Request Master Barang
              </p>
            </a>
          </li>
          <li class="nav-item">
            <a href="<?= base_url('reqpic') ?>" class="nav-link">
              <i class="nav-icon fas fa-folder-minus"></i>
              <p>PO Request PIC</p>
            </a>
          </li>
          <li class="nav-item">
            <a href="#" class="nav-link">
              <i class="nav-icon fas fa-cubes"></i>
              <p>
                Stok Barang
                <i class="right fas fa-angle-left"></i>
              </p>
            </a>
            <ul class="nav nav-treeview">
              <li class="nav-item">
                <a href="<?= base_url('stockkomersil') ?>" class="nav-link">
                  <i class="far fa-circle nav-icon"></i>
                  <p>Komersil</p>
                </a>
              </li>
              <li class="nav-item">
                <a href="<?= base_url('stocknonkomersil') ?>" class="nav-link">
                  <i class="far fa-circle nav-icon"></i>
                  <p>Non Komersil</p>
                </a>
              </li>
            </ul>
          </li>
          <li class="nav-item">
            <a href="<?= base_url('lap_nonkomersil') ?>" class="nav-link">
              <i class="nav-icon fas fa-file-excel"></i>
              <p>
                Laporan Pembelian
              </p>
            </a>
          </li>
          <li class="nav-item">
            <a href="<?= base_url('taxseting') ?>" class="nav-link">
              <i class="nav-icon fas fa-percent"></i>
              <p>
                Tax Setting
              </p>
            </a>
          </li>
          <li class="nav-item">
            <a href="<?= base_url('satuansetting') ?>" class="nav-link">
              <i class="nav-icon fa fa-weight-hanging"></i>
              <p>
                Satuan Setting
              </p>
            </a>
          </li>
          <li class="nav-item">
            <a href="<?= base_url('notetemplate') ?>" class="nav-link">
              <i class="nav-icon fas fa-pen-nib"></i>
              <p>
                Note Template Setting
              </p>
            </a>
          </li>
          <?php if ($this->session->userdata('departemen') == 'KEUANGAN' && $this->session->userdata('lv') == '2') : ?>
            <li class="nav-item">
              <a href="<?= base_url('formulapo') ?>" class="nav-link">
                <i class="nav-icon fa fa-calculator"></i>
                <p>
                  Formula PO
                </p>
              </a>
            </li>
          <?php endif; ?>
          <li class="nav-item">
            <a href="<?= base_url('usersetting') ?>" class="nav-link">
              <i class="nav-icon fas fa-cogs"></i>
              <p>
                Account Setting
              </p>
            </a>
          </li>
          <li class="nav-item">
            <a href="<?= base_url('reviewapps') ?>" class="nav-link">
              <i class="nav-icon fas fa-star"></i>
              <p>
                Review Apps
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
        <?php elseif ($this->session->userdata('lv') <= '2' && $this->session->userdata('departemen') == 'PURCHASING') : ?>
          <li class="nav-item">
            <a href="#" class="nav-link">
              <i class="nav-icon fas fa-cash-register"></i>
              <p>
                Purchase Order
                <i class="right fas fa-angle-left"></i>
              </p>
            </a>
            <ul class="nav nav-treeview">
              <li class="nav-item">
                <a href="<?= base_url('purchase') ?>" class="nav-link">
                  <i class="far fa-circle nav-icon"></i>
                  <p>Komersil</p>
                </a>
              </li>
              <li class="nav-item">
                <a href="#" class="nav-link">
                  <i class="far fa-circle nav-icon"></i>
                  <p>
                    Non Komersil
                    <i class="right fas fa-angle-left"></i>
                  </p>
                </a>
                <ul class="nav nav-treeview" style="display: none;">
                  <li class="nav-item">
                    <a href="<?= base_url('pojasa/workflow') ?>" class="nav-link">
                      <i class="far fa-dot-circle nav-icon"></i>
                      <p>PO Jasa</p>
                    </a>
                  </li>
                  <li class="nav-item">
                    <a href="<?= base_url('pononkomersil') ?>" class="nav-link">
                      <i class="far fa-dot-circle nav-icon"></i>
                      <p>PO Pembelian</p>
                    </a>
                  </li>
                  <li class="nav-item">
                    <a href="<?= base_url('reqpic') ?>" class="nav-link">
                      <i class="far fa-dot-circle nav-icon"></i>
                      <p>PO Request PIC</p>
                    </a>
                  </li>
                </ul>
              </li>
            </ul>
          </li>
          <li class="nav-item">
            <a href="#" class="nav-link">
              <i class="nav-icon fas fa-money-check"></i>
              <p>
                Purchase Order Status
                <i class="right fas fa-angle-left"></i>
              </p>
            </a>
            <ul class="nav nav-treeview">
              <li class="nav-item">
                <a href="<?= base_url('postatus') ?>" class="nav-link">
                  <i class="far fa-circle nav-icon"></i>
                  <p>Komersil</p>
                </a>
              </li>
              <li class="nav-item">
                <a href="#" class="nav-link">
                  <i class="far fa-circle nav-icon"></i>
                  <p>
                    Non Komersil
                    <i class="right fas fa-angle-left"></i>
                  </p>
                </a>
                <ul class="nav nav-treeview" style="display: none;">
                  <li class="nav-item">
                    <a href="<?= base_url('pojasa/workflow') ?>" class="nav-link">
                      <i class="far fa-dot-circle nav-icon"></i>
                      <p>PO Jasa</p>
                    </a>
                  </li>
                  <li class="nav-item">
                    <a href="<?= base_url('postatusnk') ?>" class="nav-link">
                      <i class="far fa-dot-circle nav-icon"></i>
                      <p>PO Pembelian</p>
                    </a>
                  </li>
                </ul>
              </li>
            </ul>
          </li>
          <li class="nav-item">
            <a href="#" class="nav-link">
              <i class="nav-icon fas fa-archive"></i>
              <p>
                Master Barang
                <i class="right fas fa-angle-left"></i>
              </p>
            </a>
            <ul class="nav nav-treeview">
              <li class="nav-item">
                <a href="<?= base_url('masterbarangkomersil') ?>" class="nav-link">
                  <i class="far fa-circle nav-icon"></i>
                  <p>Komersil</p>
                </a>
              </li>
              <li class="nav-item">
                <a href="<?= base_url('masterbarangnk') ?>" class="nav-link">
                  <i class="far fa-circle nav-icon"></i>
                  <p>Non Komersil</p>
                </a>
              </li>
            </ul>
          </li>
          <li class="nav-item">
            <a href="<?= base_url('vrequestmbarang') ?>" class="nav-link">
              <i class="nav-icon fas fa-box-open"></i>
              <p>
                Request Master Barang
              </p>
            </a>
          </li>
          <li class="nav-item">
            <a href="<?= base_url('reqpic') ?>" class="nav-link">
              <i class="nav-icon fas fa-folder-minus"></i>
              <p>PO Request PIC</p>
            </a>
          </li>
          <li class="nav-item">
            <a href="#" class="nav-link">
              <i class="nav-icon fas fa-cubes"></i>
              <p>
                Stok Barang
                <i class="right fas fa-angle-left"></i>
              </p>
            </a>
            <ul class="nav nav-treeview">
              <li class="nav-item">
                <a href="<?= base_url('stockkomersil') ?>" class="nav-link">
                  <i class="far fa-circle nav-icon"></i>
                  <p>Komersil</p>
                </a>
              </li>
              <li class="nav-item">
                <a href="<?= base_url('stocknonkomersil') ?>" class="nav-link">
                  <i class="far fa-circle nav-icon"></i>
                  <p>Non Komersil</p>
                </a>
              </li>
            </ul>
          </li>
          <li class="nav-item">
            <a href="<?= base_url('lap_nonkomersil') ?>" class="nav-link">
              <i class="nav-icon fas fa-file-excel"></i>
              <p>
                Laporan Pembelian
              </p>
            </a>
          </li>
          <li class="nav-item">
            <a href="<?= base_url('taxseting') ?>" class="nav-link">
              <i class="nav-icon fas fa-percent"></i>
              <p>
                Tax Setting
              </p>
            </a>
          </li>
          <li class="nav-item">
            <a href="<?= base_url('satuansetting') ?>" class="nav-link">
              <i class="nav-icon fa fa-weight-hanging"></i>
              <p>
                Satuan Setting
              </p>
            </a>
          </li>
          <li class="nav-item">
            <a href="<?= base_url('notetemplate') ?>" class="nav-link">
              <i class="nav-icon fas fa-pen-nib"></i>
              <p>
                Note Template Setting
              </p>
            </a>
          </li>
          <li class="nav-item">
            <a href="<?= base_url('usersetting') ?>" class="nav-link">
              <i class="nav-icon fas fa-cogs"></i>
              <p>
                Account Setting
              </p>
            </a>
          </li>
          <li class="nav-item">
            <a href="<?= base_url('reviewapps') ?>" class="nav-link">
              <i class="nav-icon fas fa-star"></i>
              <p>
                Review Apps
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

        <?php elseif ($this->session->userdata('lv') == '4') : ?>
          <!-- <li class="nav-item">
            <a href="<?= base_url('pononkomersil') ?>" class="nav-link">
              <i class="far fa-circle nav-icon"></i>
              <p>Non Komersil</p>
            </a>
          </li> -->
          <li class="nav-item">
            <a href="<?= base_url('postatusnk') ?>" class="nav-link">
              <i class="far fa-circle nav-icon"></i>
              <p>Non Komersil</p>
            </a>
          </li>
          <li class="nav-item">
            <a href="<?= base_url('reqpic') ?>" class="nav-link">
              <i class="nav-icon fas fa-box-open"></i>
              <p>
                PO Request Barang
              </p>
            </a>
          </li>
          <li class="nav-item">
            <a href="<?= base_url('pojasa/pic') ?>" class="nav-link">
              <i class="nav-icon fas fa-tools"></i>
              <p>
                PO Jasa
              </p>
            </a>
          </li>
          <li class="nav-item">
            <a href="<?= base_url('vrequestmbarang') ?>" class="nav-link">
              <i class="nav-icon fas fa-box"></i>
              <p>
                Request Master Barang
              </p>
            </a>
          </li>
          <li class="nav-item">
            <a href="<?= base_url('stocknonkomersil') ?>" class="nav-link">
              <i class="nav-icon fas fa-cubes"></i>
              <p>
                Stock Non Komersil
              </p>
            </a>
          </li>
          <li class="nav-item">
            <a href="<?= base_url('reviewapps') ?>" class="nav-link">
              <i class="nav-icon fas fa-star"></i>
              <p>
                Review Apps
              </p>
            </a>
          </li>
          <li class="nav-item">
            <a href="<?= base_url('usersetting') ?>" class="nav-link">
              <i class="nav-icon fas fa-cogs"></i>
              <p>
                Account Setting
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
          <!-- MENU KADEP -->
        <?php elseif ($this->session->userdata('lv') == '5') : ?>
          <li class="nav-item">
            <a href="<?= base_url('reqpicacckadep') ?>" class="nav-link">
              <i class="nav-icon fas fa-clipboard-check"></i>
              <p>
                Approval Request PIC
              </p>
            </a>
          </li>
          <li class="nav-item">
            <a href="<?= base_url('reqpicpickupapproval') ?>" class="nav-link">
              <i class="nav-icon fas fa-hand-holding"></i>
              <p>
                Approval Pengambilan
              </p>
            </a>
          </li>
          <li class="nav-item">
            <a href="<?= base_url('pojasa/workflow') ?>" class="nav-link">
              <i class="nav-icon fas fa-tools"></i>
              <p>
                PO Jasa
              </p>
            </a>
          </li>
          <li class="nav-item">
            <a href="<?= base_url('usersetting') ?>" class="nav-link">
              <i class="nav-icon fas fa-cogs"></i>
              <p>
                Account Setting
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

        <?php elseif ($this->session->userdata('lv') == '6' && in_array(strtoupper(trim((string) $this->session->userdata('departemen'))), array('DIREKTUR OPERASIONAL', 'DIREKTUR OPRASIONAL'), true)) : ?>
          <li class="nav-item">
            <a href="<?= base_url('pojasa/workflow') ?>" class="nav-link">
              <i class="nav-icon fas fa-clipboard-check"></i>
              <p>Approval PO Jasa</p>
            </a>
          </li>
          <li class="nav-item">
            <a href="<?= base_url('usersetting') ?>" class="nav-link">
              <i class="nav-icon fas fa-cogs"></i>
              <p>Account Setting</p>
            </a>
          </li>
          <li class="nav-item">
            <a href="<?= base_url('logout') ?>" class="nav-link">
              <i class="nav-icon fas fa-unlock"></i>
              <p>Log Out</p>
            </a>
          </li>

        <?php elseif ($this->session->userdata('lv') <= '2' && $this->session->userdata('departemen') == 'KEUANGAN' || $this->session->userdata('departemen') == 'SALES' || $this->session->userdata('departemen') == 'HRD') : ?>
          <!-- <li class="nav-item">
            <a href="<?= base_url('pononkomersil') ?>" class="nav-link">
              <i class="nav-icon fas fa-cash-register"></i>
              <p>
                PO Non Komersil
              </p>
            </a>
          </li>
          <li class="nav-item">
            <a href="<?= base_url('postatusnk') ?>" class="nav-link">
              <i class="nav-icon fas fa-money-check"></i>
              <p>
                PO non Komersil Status <span class="badge badge-warning right">!</span>
              </p>
            </a>
          </li> -->
          <li class="nav-item">
            <a href="<?= base_url('pojasa') ?>" class="nav-link">
              <i class="nav-icon fas fa-tools"></i>
              <p>
                PO Jasa
              </p>
            </a>
          </li>
          <li class="nav-item">
            <a href="<?= base_url('usersetting') ?>" class="nav-link">
              <i class="nav-icon fas fa-cogs"></i>
              <p>
                Account Setting
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
          <!-- MENU DIREKTUR -->
        <?php elseif ($this->session->userdata('lv') == '3' && $this->session->userdata('departemen') == 'KEUANGAN' || $this->session->userdata('departemen') == 'SALES' || $this->session->userdata('departemen') == 'LOGISTIK' || $this->session->userdata('departemen') == 'HRD') : ?>
          <li class="nav-item">
            <a href="<?= base_url('pononkomersil') ?>" class="nav-link">
              <i class="nav-icon fas fa-cash-register"></i>
              <p>
                PO Non Komersil
              </p>
            </a>
          </li>
          <li class="nav-item">
            <a href="<?= base_url('postatusnk') ?>" class="nav-link">
              <i class="nav-icon fas fa-money-check"></i>
              <p>
                PO non Komersil Status <span class="badge badge-warning right">!</span>
              </p>
            </a>
          </li>
          <li class="nav-item">
            <a href="<?= base_url('pojasa') ?>" class="nav-link">
              <i class="nav-icon fas fa-tools"></i>
              <p>
                PO Jasa
              </p>
            </a>
          </li>
          <li class="nav-item">
            <a href="<?= base_url('usersetting') ?>" class="nav-link">
              <i class="nav-icon fas fa-cogs"></i>
              <p>
                Account Setting
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
          <!-- MENU DIREKTUR -->
        <?php elseif ($this->session->userdata('lv') == '3' && $this->session->userdata('departemen') == 'DIREKTUR') : ?>
          <li class="nav-item">
            <a href="<?= base_url('postatusnk') ?>" class="nav-link">
              <i class="nav-icon fas fa-money-check"></i>
              <p>
                PO non Komersil Status <span class="badge badge-warning right">!</span>
              </p>
            </a>
          </li>
          <li class="nav-item">
            <a href="<?= base_url('postatus/today') ?>" class="nav-link">
              <i class="nav-icon fas fa-money-check"></i>
              <p>
                Purchase Order Status
              </p>
            </a>
          </li>
          <li class="nav-item">
            <a href="<?= base_url('pojasa/workflow') ?>" class="nav-link">
              <i class="nav-icon fas fa-tools"></i>
              <p>
                PO Jasa
              </p>
            </a>
          </li>
          <li class="nav-item">
            <a href="<?= base_url('usersetting') ?>" class="nav-link">
              <i class="nav-icon fas fa-cogs"></i>
              <p>
                Account Setting
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
        <?php endif; ?>
      </ul>
    </nav>
    <!-- /.sidebar-menu -->
  </div>
  <!-- /.sidebar -->
</aside>
