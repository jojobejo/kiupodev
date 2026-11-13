<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<style>
  #pojasaPicTable .pojasa-pic-actions-column {
    width: 1%;
    white-space: nowrap;
  }

  #pojasaPicTable .pojasa-pic-status-column {
    width: 1%;
    white-space: nowrap;
  }

  #pojasaPicTable .pojasa-pic-actions {
    display: inline-flex;
    gap: .25rem;
    white-space: nowrap;
  }

  #pojasaPicTable .pojasa-pic-actions .btn {
    margin-right: 0 !important;
  }
</style>
<div class="content-wrapper">
  <div class="content-header">
    <div class="container-fluid">
      <div class="row mb-2">
        <div class="col-sm-6">
          <h1 class="m-0">Request PO Jasa Saya</h1>
        </div>
        <div class="col-sm-6 text-right">
          <a href="<?= base_url('pojasa/pic/create') ?>" class="btn btn-primary">
            <i class="fas fa-plus-circle mr-1"></i> Buat Request
          </a>
        </div>
      </div>
    </div>
  </div>

  <section class="content">
    <div class="container-fluid">
      <?php if ($this->session->flashdata('error')) : ?>
        <div class="alert alert-danger"><?= htmlspecialchars($this->session->flashdata('error'), ENT_QUOTES, 'UTF-8') ?></div>
      <?php endif; ?>

      <div class="card card-outline card-primary">
        <div class="card-header"><h3 class="card-title"><i class="fas fa-filter mr-1"></i> Filter</h3></div>
        <div class="card-body">
          <form id="pojasaPicFilter" class="row align-items-end">
            <div class="col-md-4">
              <div class="form-group mb-md-0">
                <label>Status</label>
                <select id="filterStatus" class="form-control">
                  <option value="">Semua status</option>
                  <?php foreach ($statuses as $status) : ?>
                    <option value="<?= htmlspecialchars($status, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars(isset($status_labels[$status]) ? $status_labels[$status] : $status, ENT_QUOTES, 'UTF-8') ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
            </div>
            <div class="col-md-3">
              <div class="form-group mb-md-0">
                <label>Tanggal dari</label>
                <input type="date" id="filterDateFrom" class="form-control">
              </div>
            </div>
            <div class="col-md-3">
              <div class="form-group mb-md-0">
                <label>Tanggal sampai</label>
                <input type="date" id="filterDateTo" class="form-control">
              </div>
            </div>
            <div class="col-md-2">
              <button type="submit" class="btn btn-primary btn-block"><i class="fas fa-search mr-1"></i> Terapkan</button>
              <button type="button" id="btnResetPojasaFilter" class="btn btn-default btn-block">Reset</button>
            </div>
          </form>
        </div>
      </div>

      <div class="card">
        <div class="card-body">
          <div class="table-responsive">
            <table id="pojasaPicTable" class="table table-sm table-bordered table-striped w-100">
              <thead>
                <tr>
                  <th>Tanggal</th>
                  <th>Vendor Usulan</th>
                  <th>Total Estimasi</th>
                  <th>Status</th>
                  <th>Status PO Pembelian</th>
                  <th class="pojasa-pic-actions-column">Aksi</th>
                </tr>
              </thead>
            </table>
          </div>
        </div>
      </div>
    </div>
  </section>
</div>
