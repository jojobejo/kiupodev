<?php if (!empty($pojasa_summary)) :
  $pojasaRole = pojasa_role_from_context($this->session->userdata('lv'), $this->session->userdata('departemen'));
  $pojasaUrl = $pojasaRole === 'PIC' ? base_url('pojasa/pic') : base_url('pojasa/workflow');
?>
<div class="card card-outline card-primary mb-3">
  <div class="card-header"><h3 class="card-title"><i class="fas fa-tools mr-1"></i> PO Jasa</h3></div>
  <div class="card-body pb-1"><div class="row">
    <div class="col-lg-3 col-6"><div class="small-box bg-info"><div class="inner"><h3><?= (int) $pojasa_summary['total'] ?></h3><p>Total request</p></div><div class="icon"><i class="fas fa-file-alt"></i></div><a href="<?= $pojasaUrl ?>" class="small-box-footer">Buka modul <i class="fas fa-arrow-circle-right"></i></a></div></div>
    <div class="col-lg-3 col-6"><div class="small-box bg-warning"><div class="inner"><h3><?= (int) $pojasa_summary['menunggu'] ?></h3><p>Menunggu tindakan</p></div><div class="icon"><i class="fas fa-hourglass-half"></i></div><a href="<?= $pojasaUrl ?>" class="small-box-footer">Lihat daftar <i class="fas fa-arrow-circle-right"></i></a></div></div>
    <div class="col-lg-3 col-6"><div class="small-box bg-primary"><div class="inner"><h3><?= (int) $pojasa_summary['berjalan'] ?></h3><p>SPK / berjalan</p></div><div class="icon"><i class="fas fa-tasks"></i></div><a href="<?= $pojasaUrl ?>" class="small-box-footer">Lihat progress <i class="fas fa-arrow-circle-right"></i></a></div></div>
    <div class="col-lg-3 col-6"><div class="small-box bg-success"><div class="inner"><h3><?= (int) $pojasa_summary['selesai'] ?></h3><p>Selesai / ditutup</p></div><div class="icon"><i class="fas fa-check-circle"></i></div><a href="<?= $pojasaUrl ?>" class="small-box-footer">Lihat histori <i class="fas fa-arrow-circle-right"></i></a></div></div>
  </div></div>
</div>
<?php endif; ?>
