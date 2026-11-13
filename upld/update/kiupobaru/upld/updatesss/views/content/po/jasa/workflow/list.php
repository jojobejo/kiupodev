<div class="content-wrapper">
  <div class="content-header"><div class="container-fluid"><div class="row mb-2"><div class="col-sm-8"><h1 class="m-0">Workflow Approval PO Jasa</h1><small class="text-muted">Daftar approval dan SPK terbit · <?= htmlspecialchars($workflow_role, ENT_QUOTES, 'UTF-8') ?></small></div><?php if (!empty($can_create_demo)) : ?><div class="col-sm-4 text-sm-right mt-2 mt-sm-0"><button id="workflowCreateDemo" class="btn btn-warning"><i class="fas fa-flask mr-1"></i>Buat Data Demo</button></div><?php endif; ?></div></div></div>
  <section class="content"><div class="container-fluid">
    <div class="card card-outline card-primary">
      <div class="card-header"><h3 class="card-title"><i class="fas fa-filter mr-1"></i> Filter daftar kerja</h3></div>
      <div class="card-body"><form id="workflowFilter" class="row align-items-end">
        <div class="col-md-4"><div class="form-group mb-md-0"><label>Status</label><select id="workflowFilterStatus" class="form-control"><option value="">Semua status kerja</option><?php foreach ($work_statuses as $status) : ?><option value="<?= htmlspecialchars($status, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($status, ENT_QUOTES, 'UTF-8') ?></option><?php endforeach; ?></select></div></div>
        <div class="col-md-3"><div class="form-group mb-md-0"><label>Tanggal dari</label><input id="workflowDateFrom" type="date" class="form-control"></div></div>
        <div class="col-md-3"><div class="form-group mb-md-0"><label>Tanggal sampai</label><input id="workflowDateTo" type="date" class="form-control"></div></div>
        <div class="col-md-2"><button class="btn btn-primary btn-block" type="submit">Terapkan</button><button id="workflowFilterReset" class="btn btn-default btn-block" type="button">Reset</button></div>
      </form></div>
    </div>
    <div class="card"><div class="card-body"><div class="table-responsive"><table id="workflowTable" class="table table-sm table-bordered table-striped w-100"><thead><tr><th>Tanggal</th><th>Request</th><th>PIC</th><th>Departemen</th><th>Vendor</th><th>Total</th><th>Status</th><th style="min-width:100px">Aksi</th></tr></thead></table></div></div></div>
  </div></section>
</div>
<?php $this->load->view('content/po/jasa/workflow/modal'); ?>
