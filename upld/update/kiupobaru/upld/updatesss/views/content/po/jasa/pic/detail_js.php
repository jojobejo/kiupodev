<?php if (!empty($execution)) : ?>
<script>
(function ($) {
  'use strict';
  var requestCode = <?= json_encode($request->kd_po_jasa) ?>;
  var csrfToken = <?= json_encode($csrf_token) ?>;
  var stateUrl = <?= json_encode(base_url('pojasa/ajax/pic/execution/' . rawurlencode($request->kd_po_jasa))) ?>;
  var progressUrl = <?= json_encode(base_url('pojasa/ajax/pic/progress/save')) ?>;
  var receiptUrl = <?= json_encode(base_url('pojasa/ajax/stock/confirm-receipt')) ?>;
  var costUrl = <?= json_encode(base_url('pojasa/ajax/cost/save')) ?>;
  var costUpdateUrl = <?= json_encode(base_url('pojasa/ajax/cost/update')) ?>;
  var pickupUrl = <?= json_encode(base_url('pojasa/ajax/pic/pickup/request')) ?>;
  var documentBase = <?= json_encode(base_url('pojasa/pic/document/')) ?>;
  var state = <?= json_encode($this->M_PojasaExecution->get_state(pojasa_session_context(), $request->kd_po_jasa)) ?>;

  function escapeHtml(value) { return $('<div>').text(value == null ? '' : value).html(); }
  function money(value) { return 'Rp ' + Number(value || 0).toLocaleString('id-ID', {maximumFractionDigits: 2}); }
  function uuid() {
    if (window.crypto && window.crypto.randomUUID) return window.crypto.randomUUID();
    return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, function(c) { var r = Math.random() * 16 | 0; return (c === 'x' ? r : (r & 3 | 8)).toString(16); });
  }
  function messageFrom(xhr) {
    var json = xhr && xhr.responseJSON;
    if (json && json.errors) {
      var details = Object.keys(json.errors).map(function (key) { return json.errors[key]; }).join('<br>');
      if (details) return details;
    }
    return json && json.message ? json.message : 'Aksi gagal diproses.';
  }
  function alertResult(icon, title, text) {
    if (window.Swal) return Swal.fire({icon: icon, title: title, html: text});
    window.alert($('<div>').html(text).text());
    return $.Deferred().resolve().promise();
  }
  function confirmAction(title, text) {
    if (window.Swal) return Swal.fire({icon: 'warning', title: title, text: text, showCancelButton: true, confirmButtonText: 'Ya, lanjutkan', cancelButtonText: 'Batal'});
    return $.Deferred().resolve({isConfirmed: window.confirm(text)}).promise();
  }
  function refreshState() {
    return $.ajax({url: stateUrl, dataType: 'json', headers: {'Accept': 'application/json'}}).done(function (response) {
      if (response.success) {
        csrfToken = response.data.csrf_token;
        state = response.data.state;
        renderState();
      }
    });
  }
  function renderState() {
    var summary = state.cost_summary || {};
    $('#pojasaCostSummary [data-cost="requested"]').text(money(summary.requested));
    $('#pojasaCostSummary [data-cost="approved"]').text(money(summary.approved));
    $('#pojasaCostSummary [data-cost="actual"]').text(money(summary.actual));
    $('#pojasaCostSummary [data-cost="variance"]').text(money(summary.variance));
    $('.content-header .badge').first().text(state.request.status);
    $('#pojasaProgressAction').toggle(!!state.can_execute);
    $('#pojasaReceiptAction').toggle(!!state.can_receive && (state.allocations || []).some(function (row) { return Number(row.qty_remaining) > 0; }));
    $('#pojasaCostAction').toggle(!!state.can_cost);

    var progress = (state.progress || []).map(function (row) {
      var evidenceUrl = row.evidence_url || (row.id_dokumen_evidence ? documentBase + Number(row.id_dokumen_evidence) : '');
      var evidence = evidenceUrl ? '<a class="btn btn-xs btn-info" target="_blank" href="' + escapeHtml(evidenceUrl) + '">Lihat</a>' : '-';
      return '<tr><td>' + escapeHtml(row.tgl_progress) + '</td><td style="white-space:pre-wrap">' + escapeHtml(row.aktivitas || row.milestone) + '</td><td>' + escapeHtml(row.progress_persen) + '%</td><td>' + escapeHtml(row.status_progress) + '</td><td style="white-space:pre-wrap">' + escapeHtml(row.kendala) + '</td><td style="white-space:pre-wrap">' + escapeHtml(row.tindak_lanjut) + '</td><td style="white-space:pre-wrap">' + escapeHtml(row.catatan) + '</td><td>' + evidence + '</td></tr>';
    }).join('');
    $('#pojasaProgressBody').html(progress || '<tr><td colspan="8" class="text-center text-muted">Belum ada progress.</td></tr>');

    var receipts = (state.receipts || []).map(function (row) {
      var source = row.record_type === 'REVERSAL' ? 'Reversal ON_HAND Purchasing' : (row.receipt_source === 'ON_HAND_PURCHASING' ? 'ON_HAND Purchasing' : (row.receipt_source === 'ON_HAND_LEGACY_PO' ? 'ON HAND PO Pembelian' : 'Penerimaan PIC'));
      var account = row.kd_akun || (row.receipt_source === 'ON_HAND_PURCHASING' ? '11511' : '11512');
      return '<tr><td>' + escapeHtml(row.receipt_at) + '</td><td>' + escapeHtml(row.no_receipt) + '</td><td>' + escapeHtml(source) + '</td><td>' + escapeHtml(row.nama_material) + '</td><td>' + escapeHtml(row.qty_received + ' ' + row.satuan) + '</td><td>' + escapeHtml(account + ' / ' + row.id_transnk) + '</td></tr>';
    }).join('');
    $('#pojasaReceiptBody').html(receipts || '<tr><td colspan="6" class="text-center text-muted">Belum ada penerimaan material.</td></tr>');

    var costs = (state.costs || []).map(function (row) {
      var evidenceUrl = row.evidence_url || '';
      var evidence = evidenceUrl ? '<a class="btn btn-xs btn-info" href="' + escapeHtml(evidenceUrl) + '">Unduh</a>' : escapeHtml(row.source_document_reference || row.source_po_nk_code || '-');
      var action = (row.record_type || 'MANUAL') === 'MANUAL' ? '<button type="button" class="btn btn-xs btn-warning pojasa-edit-cost" data-id="' + Number(row.id_biaya_aktual) + '">Perbaiki</button>' : '<span class="text-muted">Otomatis</span>';
      return '<tr><td>' + escapeHtml(row.tgl_biaya) + '</td><td>' + escapeHtml(row.source_type || row.cost_source) + '</td><td>' + escapeHtml(row.jenis_biaya) + '</td><td style="white-space:pre-wrap">' + escapeHtml(row.deskripsi) + '</td><td class="text-right">' + money(row.nominal) + '</td><td>' + escapeHtml(row.record_status || '-') + '<br><small>' + escapeHtml(row.verification_status) + '</small></td><td>' + evidence + '</td><td>' + action + '</td></tr>';
    }).join('');
    $('#pojasaCostBody').html(costs || '<tr><td colspan="8" class="text-center text-muted">Belum ada biaya aktual.</td></tr>');

    var allocationRows = (state.allocations || []).filter(function (row) { return Number(row.qty_remaining) > 0; }).map(function (row) {
      return '<tr data-allocation="' + Number(row.id_allocation) + '"><td>' + escapeHtml(row.nama_material) + '</td><td>' + escapeHtml(row.qty_allocation + ' ' + row.satuan) + '</td><td>' + escapeHtml(row.qty_remaining) + '</td><td><input type="number" class="form-control form-control-sm qty-received" min="0" max="' + Number(row.qty_remaining) + '" step="0.01" value="0"></td></tr>';
    }).join('');
    $('#pojasaReceiptInputBody').html(allocationRows || '<tr><td colspan="4" class="text-center text-muted">Tidak ada reservasi yang menunggu penerimaan.</td></tr>');
  }
  $('#pojasaPickupRequest').on('click', function () {
    var button=$(this).prop('disabled',true);
    $.post(pickupUrl,{kd_po_jasa:requestCode,idempotency_token:uuid(),csrf_token:csrfToken},null,'json').done(function(r){alertResult(r.success?'success':'error',r.success?'Berhasil':'Gagal',r.message);if(r.success) window.location.reload();}).fail(function(xhr){alertResult('error','Gagal',messageFrom(xhr));}).always(function(){button.prop('disabled',false);});
  });

  $('#pojasaProgressModal, #pojasaReceiptModal, #pojasaCostModal').on('show.bs.modal', function () {
    $(this).find('[name="idempotency_token"]').val(uuid());
  });
  $('#pojasaCostAction').on('click', function () { var form=$('#pojasaCostForm')[0];form.reset();$(form).find('[name="id_biaya_aktual"]').val('');$('#pojasaCostReasonGroup').addClass('d-none').find('textarea').prop('required',false);$('#pojasaCostEvidenceGroup').removeClass('d-none');$('#pojasaCostModal .modal-title').text('Catat Biaya Aktual'); });
  $('#pojasaCostAllocation').on('change',function(){var parts=String($(this).val()||'').split(':');$('#pojasaCostForm [name="id_scope"]').val(parts[0]==='scope'?parts[1]:'');$('#pojasaCostForm [name="id_material"]').val(parts[0]==='material'?parts[1]:'');});
  $(document).on('click','.pojasa-edit-cost',function(){var id=Number($(this).data('id')),row=null;(state.costs||[]).forEach(function(c){if(Number(c.id_biaya_aktual)===id){row=c;}});if(!row){return;}var form=$('#pojasaCostForm')[0];form.reset();$(form).find('[name="id_biaya_aktual"]').val(id);$(form).find('[name="tgl_biaya"]').val(row.tgl_biaya);$(form).find('[name="cost_source"]').val(row.cost_source);$(form).find('[name="jenis_biaya"]').val(row.jenis_biaya);$(form).find('[name="deskripsi"]').val(row.deskripsi);$(form).find('[name="nominal"]').val(row.nominal);$('#pojasaCostAllocation').val(row.id_scope?'scope:'+row.id_scope:'material:'+row.id_material).trigger('change');$('#pojasaCostReasonGroup').removeClass('d-none').find('textarea').prop('required',true);$('#pojasaCostEvidenceGroup').addClass('d-none');$('#pojasaCostModal .modal-title').text('Perbaiki Biaya Aktual Manual');$('#pojasaCostModal').modal('show');});
  $('#pojasaProgressForm').on('submit', function (event) {
    event.preventDefault(); var form = this;
    confirmAction('Simpan progress?', 'Status request akan mengikuti status pekerjaan yang dipilih.').then(function (answer) {
      if (!answer.isConfirmed) return;
      var data = new FormData(form); data.append('csrf_token', csrfToken);
      $.ajax({url: progressUrl, type: 'POST', data: data, processData: false, contentType: false, dataType: 'json', headers: {'X-POJASA-CSRF': csrfToken, 'Accept': 'application/json'}})
        .done(function (response) { if (!response.success) return alertResult('error', 'Gagal', response.message); csrfToken = response.data.csrf_token; $('#pojasaProgressModal').modal('hide'); form.reset(); refreshState(); alertResult('success', 'Berhasil', response.message); })
        .fail(function (xhr) { alertResult('error', 'Progress gagal', messageFrom(xhr)); });
    });
  });
  $('#pojasaReceiptForm').on('submit', function (event) {
    event.preventDefault(); var form = this; var items = [];
    $('#pojasaReceiptInputBody tr[data-allocation]').each(function () { var qty = Number($(this).find('.qty-received').val()); if (qty > 0) items.push({id_allocation: Number($(this).data('allocation')), qty_received: qty}); });
    if (!items.length) return alertResult('warning', 'Belum ada quantity', 'Isi minimal satu jumlah material yang benar-benar telah diterima.');
    confirmAction('Konfirmasi material diterima?', 'Stok fisik akan berkurang dan penyerahan material akan dicatat.').then(function (answer) {
      if (!answer.isConfirmed) return;
      var data = $(form).serializeArray(); data.push({name: 'items', value: JSON.stringify(items)}, {name: 'csrf_token', value: csrfToken});
      $.ajax({url: receiptUrl, type: 'POST', data: $.param(data), dataType: 'json', headers: {'X-POJASA-CSRF': csrfToken, 'Accept': 'application/json'}})
        .done(function (response) { if (!response.success) return alertResult('error', 'Gagal', response.message); csrfToken = response.data.csrf_token; $('#pojasaReceiptModal').modal('hide'); form.reset(); refreshState(); alertResult('success', 'Berhasil', response.message); })
        .fail(function (xhr) { alertResult('error', 'Penerimaan gagal', messageFrom(xhr)); });
    });
  });
  $('#pojasaCostForm').on('submit', function (event) {
    event.preventDefault(); var form = this;
    if (!$('#pojasaCostAllocation').val()) return alertResult('warning', 'Alokasi wajib', 'Pilih scope atau material yang menjadi tujuan biaya.');
    confirmAction('Simpan biaya aktual?', 'Pastikan bukti adalah pembelian atau nota/invoice baru, bukan material dari stok.').then(function (answer) {
      if (!answer.isConfirmed) return;
      var data = new FormData(form); data.append('csrf_token', csrfToken); var editing=Number($(form).find('[name="id_biaya_aktual"]').val())>0;
      $.ajax({url: editing ? costUpdateUrl : costUrl, type: 'POST', data: data, processData: false, contentType: false, dataType: 'json', headers: {'X-POJASA-CSRF': csrfToken, 'Accept': 'application/json'}})
        .done(function (response) { if (!response.success) return alertResult('error', 'Gagal', response.message); csrfToken = response.data.csrf_token; $('#pojasaCostModal').modal('hide'); form.reset(); refreshState(); alertResult('success', 'Berhasil', response.message); })
        .fail(function (xhr) { alertResult('error', 'Biaya gagal', messageFrom(xhr)); });
    });
  });
  $('[name="evidence"]').on('change', function () { $('#pojasaEvidenceName').text(this.files && this.files[0] ? this.files[0].name : ''); });
  renderState();
})(jQuery);
</script>
<?php endif; ?>
