<?php
$h=function($value){return htmlspecialchars((string)$value,ENT_QUOTES,'UTF-8');};
$money=function($value){return 'Rp '.number_format((float)$value,2,',','.');};
$paymentOpen=$can_manage && $request->no_spk && in_array($request->status,array('SPK_TERBIT','ON_PROGRESS','SELESAI','DITUTUP'),true);
?>
<div class="content-wrapper"><section class="content-header"><div class="container-fluid">
  <h1>BAST dan Pembayaran PO Jasa</h1>
  <p><?= $h($request->kd_po_jasa) ?> · <?= $h($request->status) ?> · <?= $h($request->nm_user) ?></p>
  <a class="btn btn-secondary btn-sm" href="<?= base_url('pojasa/workflow/detail/'.$request->kd_po_jasa) ?>">Detail pekerjaan</a>
  <?php if($request->no_spk): ?><a class="btn btn-outline-primary btn-sm" href="<?= base_url('pojasa/spk/'.$request->kd_po_jasa) ?>">Lihat SPK</a><?php endif; ?>
</div></section><section class="content"><div class="container-fluid">
  <div id="settlement-message" class="alert d-none" role="status"></div>
  <div class="card"><div class="card-header"><h2 class="card-title">Berita Acara Serah Terima</h2></div><div class="card-body">
  <?php if($bast): ?>
    <dl><dt>No. BAST</dt><dd><?= $h($bast->no_bast) ?></dd><dt>Tanggal</dt><dd><?= $h($bast->tgl_bast) ?></dd>
    <dt>Penerima pekerjaan</dt><dd><?= $h($bast->penerima_pekerjaan) ?></dd><dt>Catatan</dt><dd><?= $h($bast->catatan_bast) ?></dd>
    <dt>Ditutup oleh</dt><dd><?= $h($bast->created_name) ?></dd></dl>
  <?php elseif($can_manage && $request->status==='SELESAI'): ?>
    <p>Progres selesai 100%. Simpan BAST untuk menutup pekerjaan.</p>
    <form id="settlement-bast" data-url="<?= base_url('pojasa/bast/complete') ?>">
      <input type="hidden" name="status_version" value="<?= (int)$request->status_version ?>">
      <div class="row"><div class="form-group col-md-4"><label for="no-bast">No. BAST</label><input id="no-bast" class="form-control" name="no_bast" maxlength="80" required></div>
      <div class="form-group col-md-4"><label for="tgl-bast">Tanggal BAST</label><input id="tgl-bast" class="form-control" type="date" name="tgl_bast" value="<?= date('Y-m-d') ?>" required></div>
      <div class="form-group col-md-4"><label for="penerima-bast">Penerima pekerjaan</label><input id="penerima-bast" class="form-control" name="penerima_pekerjaan" maxlength="120" required></div></div>
      <div class="form-group"><label for="catatan-bast">Catatan BAST</label><textarea id="catatan-bast" class="form-control" name="catatan_bast" maxlength="10000"></textarea></div>
      <button class="btn btn-success" type="submit">Simpan BAST dan tutup pekerjaan</button>
    </form>
  <?php else: ?><p>BAST belum tersedia. PIC menyelesaikan progres sampai 100%, lalu Purchasing/Admin memproses BAST.</p><?php endif; ?>
  </div></div>

  <div class="card"><div class="card-header"><h2 class="card-title">Tracking invoice dan pembayaran vendor</h2></div><div class="card-body">
    <p>Total tagihan: <strong id="total-tagihan"><?= $h($money($summary->total_tagihan)) ?></strong> · Total dibayar: <strong id="total-bayar"><?= $h($money($summary->total_bayar)) ?></strong> · Sisa: <strong id="sisa-bayar"><?= $h($money($summary->sisa_bayar)) ?></strong></p>
    <div class="table-responsive"><table class="table table-bordered" id="settlement-payments"><thead><tr><th>Invoice</th><th>Tanggal</th><th>Jatuh tempo</th><th>Tagihan</th><th>Dibayar kumulatif</th><th>Status</th><?php if($paymentOpen): ?><th>Aksi</th><?php endif; ?></tr></thead><tbody>
    <?php foreach($payments as $index=>$payment): ?><tr><td><?= $h($payment->no_invoice) ?></td><td><?= $h($payment->tgl_invoice) ?></td><td><?= $h($payment->jatuh_tempo) ?></td><td><?= $h($money($payment->nominal_tagihan)) ?></td><td><?= $h($money($payment->nominal_bayar)) ?></td><td><?= $h($payment->status_bayar) ?></td><?php if($paymentOpen): ?><td><button type="button" class="btn btn-sm btn-primary settlement-edit" data-index="<?= $index ?>">Perbarui</button></td><?php endif; ?></tr><?php endforeach; ?>
    </tbody></table></div>
    <?php if($paymentOpen): ?>
    <form id="settlement-payment" data-url="<?= base_url('pojasa/payment/save') ?>">
      <h3 id="payment-title" class="h5">Catat invoice baru</h3><input type="hidden" name="id_payment_jasa" value="0"><input type="hidden" name="row_version" value="0">
      <div class="row"><div class="form-group col-md-4"><label for="no-invoice">No. invoice</label><input id="no-invoice" class="form-control" name="no_invoice" maxlength="100" required></div>
      <div class="form-group col-md-4"><label for="tgl-invoice">Tanggal invoice</label><input id="tgl-invoice" class="form-control" type="date" name="tgl_invoice" value="<?= date('Y-m-d') ?>" required></div>
      <div class="form-group col-md-4"><label for="due-invoice">Jatuh tempo</label><input id="due-invoice" class="form-control" type="date" name="jatuh_tempo" value="<?= date('Y-m-d') ?>" required></div></div>
      <div class="row"><div class="form-group col-md-6"><label for="tagihan-invoice">Nominal tagihan</label><input id="tagihan-invoice" class="form-control" type="number" min="0.01" step="0.01" name="nominal_tagihan" required></div>
      <div class="form-group col-md-6"><label for="bayar-invoice">Total dibayar untuk invoice ini (kumulatif)</label><input id="bayar-invoice" class="form-control" type="number" min="0" step="0.01" name="nominal_bayar" value="0" required></div></div>
      <div class="form-group"><label for="catatan-payment">Catatan pembayaran</label><textarea id="catatan-payment" class="form-control" name="catatan_payment" maxlength="10000"></textarea></div>
      <button class="btn btn-primary" type="submit">Simpan invoice/pembayaran</button> <button id="payment-new" class="btn btn-secondary" type="button">Invoice baru</button>
    </form>
    <?php endif; ?>
  </div></div>
  <div class="card"><div class="card-header"><h2 class="card-title">Evaluasi vendor</h2></div><div class="card-body">
    <?php if($evaluation): ?><p>Vendor: <strong><?= $h($evaluation->vendor_name_snapshot) ?></strong> · Nilai rata-rata: <strong><?= $h($evaluation->total_score) ?></strong></p><p><?= $h($evaluation->catatan_evaluasi) ?></p><?php endif; ?>
    <?php if($can_manage && $bast && $request->status==='DITUTUP'): ?>
    <form id="settlement-evaluation" data-url="<?= base_url('pojasa/evaluation/save') ?>">
      <input type="hidden" name="row_version" value="<?= $evaluation?(int)$evaluation->row_version:0 ?>">
      <div class="row"><?php foreach(array('kualitas_score'=>'Kualitas','ketepatan_waktu_score'=>'Ketepatan waktu','biaya_score'=>'Biaya') as $field=>$label): ?>
      <div class="form-group col-md-4"><label for="<?= $field ?>"><?= $label ?> (1–5)</label><input class="form-control" id="<?= $field ?>" name="<?= $field ?>" type="number" min="1" max="5" step="0.01" required value="<?= $evaluation?$h($evaluation->$field):'' ?>"></div><?php endforeach; ?></div>
      <div class="form-group"><label for="catatan-evaluasi">Catatan evaluasi</label><textarea class="form-control" id="catatan-evaluasi" name="catatan_evaluasi" maxlength="10000"><?= $evaluation?$h($evaluation->catatan_evaluasi):'' ?></textarea></div>
      <button class="btn btn-primary" type="submit">Simpan evaluasi vendor</button>
    </form><?php elseif(!$evaluation): ?><p>Purchasing/Admin mengevaluasi vendor setelah BAST menutup pekerjaan.</p><?php endif; ?>
  </div></div>
  <div class="card"><div class="card-header"><h2 class="card-title">Riwayat perubahan pembayaran</h2></div><div class="card-body table-responsive"><table class="table table-sm"><thead><tr><th>Waktu</th><th>Invoice</th><th>Aksi</th><th>Petugas</th><th>Total dibayar sesudah perubahan</th></tr></thead><tbody>
    <?php foreach($audit as $entry): $after=json_decode($entry->after_json,true); ?><tr><td><?= $h($entry->created_at) ?></td><td><?= $h(isset($after['no_invoice'])?$after['no_invoice']:'') ?></td><td><?= $h($entry->event_type) ?></td><td><?= $h($entry->actor_name) ?></td><td><?= $h($money(isset($after['nominal_bayar'])?$after['nominal_bayar']:0)) ?></td></tr><?php endforeach; ?>
  </tbody></table></div></div>
</div></section></div>
<script>
(function(){
  var code=<?= json_encode($request->kd_po_jasa,JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT) ?>;
  var csrf=<?= json_encode($csrf_token) ?>;
  var invoices=<?= json_encode($payments,JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT) ?>;
  function uuid(){return '10000000-1000-4000-8000-100000000000'.replace(/[018]/g,function(c){return (c^crypto.getRandomValues(new Uint8Array(1))[0]&15>>c/4).toString(16);});}
  document.querySelectorAll('#settlement-bast,#settlement-payment,#settlement-evaluation').forEach(function(form){
    form.addEventListener('submit',function(e){
      e.preventDefault(); var data=new URLSearchParams(new FormData(form));
      data.set('kd_po_jasa',code);data.set('csrf_token',csrf);
      // Keep the token on a failed network attempt; a changed payload gets a new token.
      var body=data.toString();if(form.dataset.payload!==body){form.dataset.token=uuid();form.dataset.payload=body;}
      data.set('idempotency_token',form.dataset.token);
      var button=form.querySelector('[type=submit]');button.disabled=true;
      fetch(form.dataset.url,{method:'POST',headers:{'X-Requested-With':'XMLHttpRequest','X-POJASA-CSRF':csrf},body:data})
      .then(function(response){return response.json();}).then(function(result){
        if(result.success){window.location.reload();return;}
        var box=document.getElementById('settlement-message');box.className='alert alert-danger';box.textContent=result.message;
      }).catch(function(){var box=document.getElementById('settlement-message');box.className='alert alert-danger';box.textContent='Penyimpanan belum dapat dipastikan. Coba kembali dengan data yang sama.';})
      .finally(function(){button.disabled=false;});
    });
  });
  var form=document.getElementById('settlement-payment');
  if(form){
    document.querySelectorAll('.settlement-edit').forEach(function(button){button.addEventListener('click',function(){
      var row=invoices[Number(button.dataset.index)];
      ['id_payment_jasa','row_version','no_invoice','tgl_invoice','jatuh_tempo','nominal_tagihan','nominal_bayar','catatan_payment'].forEach(function(key){form.elements[key].value=row[key]===null?'':row[key];});
      form.elements.no_invoice.readOnly=true;document.getElementById('payment-title').textContent='Perbarui pembayaran invoice '+row.no_invoice;form.scrollIntoView({behavior:'smooth'});
    });});
    document.getElementById('payment-new').addEventListener('click',function(){form.reset();form.elements.id_payment_jasa.value='0';form.elements.row_version.value='0';form.elements.no_invoice.readOnly=false;document.getElementById('payment-title').textContent='Catat invoice baru';});
  }
})();
</script>
