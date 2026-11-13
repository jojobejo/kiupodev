<script>
(function($) {
  'use strict';
  var csrfToken = <?= json_encode($csrf_token) ?>;
  var currentState = null;
  var selectedAction = '';
  var endpoints = {
    table: <?= json_encode(base_url('pojasa/ajax/workflow/requests')) ?>,
    state: <?= json_encode(base_url('pojasa/ajax/workflow/state/')) ?>,
    action: <?= json_encode(base_url('pojasa/ajax/approval/action')) ?>,
    review: <?= json_encode(base_url('pojasa/ajax/workflow/purchasing/save')) ?>,
    planning: <?= json_encode(base_url('pojasa/ajax/workflow/planning/')) ?>,
    draft: <?= json_encode(base_url('pojasa/ajax/purchasing/draft/save')) ?>,
    draftCancel: <?= json_encode(base_url('pojasa/ajax/purchasing/draft/cancel')) ?>,
    devPurchaseSubmit: <?= json_encode(base_url('pojasa/ajax/purchasing/purchase/dev-submit')) ?>,
    pickupDecision: <?= json_encode(base_url('pojasa/ajax/workflow/pickup/decision')) ?>
    ,createDemo: <?= json_encode(base_url('pojasa/ajax/workflow/demo/create')) ?>
  };
  var actionMeta = {
    ACC: { label: 'ACC', icon: 'fa-check', color: 'success' },
    REVISI: { label: 'REVISI', icon: 'fa-edit', color: 'warning' },
    PENDING: { label: 'PENDING', icon: 'fa-pause', color: 'secondary' },
    REJECT: { label: 'REJECT', icon: 'fa-times', color: 'danger' },
    UPDATE: { label: 'KIRIM UPDATE KE PIC', icon: 'fa-share', color: 'primary' },
    SUBMIT: { label: 'AJUKAN', icon: 'fa-paper-plane', color: 'primary' },
    TERBITKAN: { label: 'TERBITKAN SPK & PO', icon: 'fa-file-signature', color: 'primary' }
  };

  var table = null;
  if ($('#workflowIssueSpkPurchase').length) { $('#workflowDetailProcess').addClass('d-none'); }
  if ($('#workflowDetailCode').length) { loadState(String($('#workflowDetailCode').val() || ''), false); }
  $(document).on('click', '.workflow-pickup-decision', function () {
    var action=$(this).data('action');
    $.ajax({url:endpoints.pickupDecision,type:'POST',dataType:'json',headers:{'X-POJASA-CSRF':csrfToken},data:{kd_po_jasa:$('#workflowDetailCode').val(),action:action,csrf_token:csrfToken}}).done(function(r){if(r.success){Swal.fire('Berhasil',r.message,'success').then(function(){window.location.reload();});}else{showResponseError(r);}}).fail(function(xhr){showAjaxError(xhr,'Keputusan pengambilan gagal.');});
  });
  $('.workflow-delete-row').prop('disabled', true);
  if ($('#workflowTable').length) {
    table = $('#workflowTable').DataTable({
      processing: true, serverSide: true, responsive: true, pageLength: 10, order: [[0, 'desc']],
      ajax: {
        url: endpoints.table, type: 'GET',
        data: function(data) {
          data.status = $('#workflowFilterStatus').val();
          data.date_from = $('#workflowDateFrom').val();
          data.date_to = $('#workflowDateTo').val();
        },
        dataSrc: function(response) { csrfToken = response.csrf_token || csrfToken; return response.data || []; },
        error: function(xhr) { showAjaxError(xhr, 'Daftar kerja gagal dimuat.'); }
      },
      columns: [
        { data: 'tgl_request' }, { data: 'kd_po_jasa' }, { data: 'pic' }, { data: 'departemen' },
        { data: 'vendor' }, { data: 'total', className: 'text-right' },
        { data: 'status', orderable: false }, { data: 'actions', orderable: false, searchable: false }
      ],
      language: { processing: 'Memuat data...', emptyTable: 'Tidak ada request dalam daftar kerja.', zeroRecords: 'Data tidak ditemukan.' }
    });
    $('#workflowFilter').on('submit', function(event) { event.preventDefault(); table.ajax.reload(); });
    $('#workflowFilterReset').on('click', function() { $('#workflowFilterStatus, #workflowDateFrom, #workflowDateTo').val(''); table.search('').ajax.reload(); });
  }

  $(document).on('click', '#workflowCreateDemo', function() {
    var button = $(this);
    Swal.fire({
      title: 'Buat data demo?',
      html: 'Membuat <b>master barang baru</b>, kebutuhan material yang melebihi stok, SPK berstatus <b>SPK_TERBIT</b>, dan PO Pembelian berstatus <b>PROSES PEMBELIAN</b>. Semua data diberi penanda DEMO.',
      icon: 'warning', showCancelButton: true, confirmButtonText: 'Ya, buat demo', cancelButtonText: 'Batal'
    }).then(function(result) {
      if (!result.value) return;
      button.prop('disabled', true);
      $.ajax({url:endpoints.createDemo, method:'POST', dataType:'json', headers:{'X-POJASA-CSRF':csrfToken}, data:{csrf_token:csrfToken}})
        .done(function(response) {
          updateCsrf(response);
          if (!response.success) { showResponseError(response); return; }
          var data=response.data||{};
          Swal.fire('Demo berhasil dibuat', 'PO Jasa: '+(data.kd_po_jasa||'-')+'<br>SPK: '+(data.no_spk||'-')+'<br>PO Pembelian: '+(data.kd_po_nk||'-')+'<br>Master baru: '+(data.kd_barang_baru||'-'), 'success');
          if (table) table.ajax.reload(null, false);
        }).fail(function(xhr) { showAjaxError(xhr, 'Pembuatan data demo gagal.'); })
        .always(function() { button.prop('disabled', false); });
    });
  });

  $(document).on('click', '.btn-workflow-process', function() {
    loadState(String($(this).data('code') || ''), true);
  });

  // Temporary DEV control: this deliberately invokes the normal, audited
  // automatic-purchase endpoint so the saved database records can be tested.
  $(document).on('click', '#workflowDevPurchaseSubmit', function() {
    var button = $(this), code = String(button.data('code') || '');
    if (!code) { return; }
    Swal.fire({
      title: 'Jalankan generate PO Pembelian?',
      text: 'Mode DEV membuat PO Pembelian untuk pengujian tanpa menunggu SPK. Draft aktif tidak dikunci dan proses Purchasing normal tetap dapat dijalankan.',
      icon: 'warning', showCancelButton: true, confirmButtonText: 'Ya, generate', cancelButtonText: 'Batal'
    }).then(function(result) {
      if (!result.value) { return; }
      button.prop('disabled', true);
      $.ajax({
        url: endpoints.devPurchaseSubmit, method: 'POST', dataType: 'json', headers: {'X-POJASA-CSRF': csrfToken},
        data: {csrf_token: csrfToken, kd_po_jasa: code, idempotency_token: uuid()}
      }).done(function(response) {
        updateCsrf(response);
        if (!response.success) { showResponseError(response); return; }
        var poCode = response.data && response.data.kd_po_nk ? ' Nomor PO: ' + response.data.kd_po_nk : '';
        Swal.fire('DEV berhasil', response.message + poCode, 'success');
        loadState(code, false);
      }).fail(function(xhr) { showAjaxError(xhr, 'Generate PO Pembelian DEV gagal.'); })
        .always(function() { button.prop('disabled', false); });
    });
  });

  $(document).on('click', '.workflow-action-choice', function() {
    selectedAction = String($(this).data('action') || '');
    $('#workflowSelectedAction').val(selectedAction);
    $('.workflow-action-choice').removeClass('active');
    $(this).addClass('active');
    var required = selectedAction === 'REVISI' || selectedAction === 'REJECT' || selectedAction === 'UPDATE' || (selectedAction === 'ACC' && currentState && currentState.request.status === 'MENUNGGU_KONFIRMASI_PIC');
    var noteLabel = selectedAction === 'ACC' && currentState && currentState.request.status === 'MENUNGGU_KONFIRMASI_PIC' ? 'Catatan kesepakatan PIC' : 'Catatan';
    $('#workflowNoteLabel').html(noteLabel + (required ? ' <span class="text-danger">*</span>' : ''));
    $('#workflowExecuteAction').prop('disabled', false).removeClass().addClass('btn btn-' + actionMeta[selectedAction].color);
  });

  $('#workflowExecuteAction').on('click', function() {
    if (!currentState || !selectedAction) {
      return;
    }
    var note = $.trim($('#workflowActionNote').val());
    var needsNote = selectedAction === 'REVISI' || selectedAction === 'REJECT' || selectedAction === 'UPDATE' || (selectedAction === 'ACC' && currentState.request.status === 'MENUNGGU_KONFIRMASI_PIC');
    if (needsNote && !note) {
      if (selectedAction === 'UPDATE') {
        showWorkflowUpdateError({ code: 'NOTE_REQUIRED', message: 'Catatan update wajib diisi sebelum data dikirim ke PIC.' });
      } else {
        Swal.fire('Catatan wajib', 'Isi alasan sebelum menjalankan aksi ' + selectedAction + '.', 'warning');
      }
      return;
    }
    var meta = actionMeta[selectedAction];
    var isDirectorApproval = selectedAction === 'ACC' && currentState.request.status === 'MENUNGGU_DIREKTUR';
    Swal.fire({
      title: isDirectorApproval ? 'Setujui request Direktur?' : meta.label + ' request?',
      text: confirmationText(selectedAction, currentState.request),
      icon: selectedAction === 'REJECT' ? 'warning' : 'question',
      showCancelButton: true, confirmButtonText: isDirectorApproval ? 'Ya, setujui' : 'Ya, proses', cancelButtonText: 'Batal',
      confirmButtonColor: selectedAction === 'REJECT' ? '#dc3545' : undefined
    }).then(function(result) {
      if (!result.value) { return; }
      executeAction(note);
    });
  });

  $(document).on('click', '.workflow-save-section', function() {
    var button = $(this), segment = String(button.data('review-section') || (button.hasClass('workflow-save-purchasing-form') ? 'purchasing' : (button.attr('id') === 'workflowSaveScope' ? 'scope' : 'material')));
    var payload = { segment: segment, note: $('#workflowPurchasingNote').val(), tgl_mulai_pekerjaan: $('#workflowStartDate').val(), tgl_selesai_pekerjaan: $('#workflowEndDate').val(), scopes: collectReviewRows('#workflowScopeTable'), materials: collectReviewRows('#workflowMaterialTable'), deleted_scopes: $('#workflowScopeTable').data('deleted') || [], deleted_materials: $('#workflowMaterialTable').data('deleted') || [] };
    var isPurchasingRevision = ['REVISI_PURCHASING_KADEP', 'REVISI_PURCHASING_DIROPS', 'REVISI_PURCHASING_DIRUT']
      .indexOf(String($('#workflowDetailExpectedStatus').val() || '')) !== -1;
    if (segment === 'purchasing' && isPurchasingRevision && !$.trim(payload.note)) {
      Swal.fire('Catatan perbaikan wajib', 'Isi Catatan perbaikan Purchasing sebelum menyimpan revisi.').then(function() {
        $('#workflowPurchasingNote').trigger('focus');
      });
      return;
    }
    if (segment === 'purchasing' && (!payload.tgl_mulai_pekerjaan || !payload.tgl_selesai_pekerjaan)) {
      Swal.fire('Jadwal wajib diisi', 'Isi tanggal mulai dan tanggal selesai sebelum menyimpan.').then(function() {
        $(payload.tgl_mulai_pekerjaan ? '#workflowEndDate' : '#workflowStartDate').trigger('focus');
      });
      return;
    }
    if (segment === 'purchasing' && payload.tgl_selesai_pekerjaan < payload.tgl_mulai_pekerjaan) {
      Swal.fire('Jadwal tidak valid', 'Tanggal selesai tidak boleh lebih awal dari tanggal mulai.').then(function() {
        $('#workflowEndDate').trigger('focus');
      });
      return;
    }
    button.prop('disabled', true);
    $.ajax({
      url: endpoints.review, method: 'POST', dataType: 'json', headers: { 'X-POJASA-CSRF': csrfToken },
      data: {
        csrf_token: csrfToken, kd_po_jasa: $('#workflowDetailCode').val(),
        expected_status: $('#workflowDetailExpectedStatus').val(), status_version: $('#workflowDetailVersion').val(),
        payload: JSON.stringify(payload)
      }
    }).done(function(response) {
      updateCsrf(response);
      if (!response.success) { showResponseError(response); return; }
      applyDetailState(response.data);
      if (response.data.totals) { renderTotals(response.data.totals); }
      if (segment === 'purchasing') {
        $('.workflow-save-purchasing-form').prop('disabled', true);
        $('.workflow-purchasing-form-input').prop('readonly', true).removeClass('workflow-edit-active');
        $('.workflow-toggle-purchasing-form').data('editing', false).removeClass('btn-secondary').addClass('btn-outline-warning');
      } else {
        var tableSelector = segment === 'scope' ? '#workflowScopeTable' : '#workflowMaterialTable';
        $(tableSelector).data('editing', false).find('input, textarea').prop('readonly', true).removeClass('workflow-edit-active');
        $(tableSelector).find('.workflow-delete-row, .workflow-add-draft').prop('disabled', true);
        $('.workflow-save-review[data-review-section="' + segment + '"]').prop('disabled', true);
        $('.workflow-toggle-section[data-section="' + segment + '"]').removeClass('btn-secondary active').addClass('btn-outline-warning');
        $(segment === 'scope' ? '#workflowSaveScope' : '#workflowSaveMaterial').addClass('d-none');
      }
      Swal.fire({ title: 'Perubahan tersimpan', text: response.message, icon: 'success', timer: 1700, showConfirmButton: false });
      $(document).trigger('pojasa:refresh-notifications');
    }).fail(function(xhr) { showAjaxError(xhr, 'Review Purchasing gagal disimpan.'); })
      .always(function() { button.prop('disabled', false); });
  });

  $(document).on('click', '.workflow-toggle-edit', function() { var button=$(this), target = $(button.data('target')), editing=target.prop('readonly'); target.prop('readonly', !editing); button.toggleClass('btn-outline-warning', !editing).toggleClass('btn-secondary', editing); $(button.data('save')).toggleClass('d-none', !editing); });
  $(document).on('click', '.workflow-toggle-section', function() { var button=$(this), section=button.data('section'), table = section === 'scope' ? $('#workflowScopeTable') : $('#workflowMaterialTable'), editing = !!table.data('editing'); table.find('input, textarea').prop('readonly', editing).toggleClass('workflow-edit-active', !editing); table.find('.workflow-delete-row').prop('disabled', editing); if(section==='material'){ $('#workflowPurchasingNote, .workflow-schedule-input').prop('readonly', editing).toggleClass('workflow-edit-active', !editing); table.find('.workflow-add-draft').each(function(){ $(this).prop('disabled', !editing ? !(Number($(this).data('qty')) > 0) : true); });} table.data('editing', !editing); button.toggleClass('btn-outline-warning', editing).toggleClass('btn-secondary', !editing); $(button.data('save')).toggleClass('d-none', editing); });
  $(document).on('click', '.workflow-toggle-section', function() {
    var section = String($(this).data('section') || '');
    var table = section === 'scope' ? $('#workflowScopeTable') : $('#workflowMaterialTable');
    var editing = !!table.data('editing');
    $('.workflow-save-review[data-review-section="' + section + '"]').prop('disabled', !editing);
    if (section === 'material') {
      $('.workflow-purchasing-form-input').prop('readonly', true).removeClass('workflow-edit-active');
      $('.workflow-save-purchasing-form').prop('disabled', true);
      $('.workflow-toggle-purchasing-form').data('editing', false).removeClass('btn-secondary').addClass('btn-outline-warning');
    }
    if (editing) { setPurchasingReviewStatus(false, section); }
  });
  $(document).on('click', '.workflow-toggle-purchasing-form', function() {
    var button = $(this), editing = !!button.data('editing');
    $('.workflow-purchasing-form-input').prop('readonly', editing).toggleClass('workflow-edit-active', !editing);
    $('.workflow-save-purchasing-form').prop('disabled', editing);
    button.data('editing', !editing).toggleClass('btn-outline-warning', editing).toggleClass('btn-secondary', !editing);
    if (!editing) { setPurchasingReviewStatus(false, 'purchasing'); }
  });
  $(document).on('input change', '.workflow-edit-active', function() {
    var section = $(this).hasClass('workflow-purchasing-form-input') ? 'purchasing' : ($(this).closest('#workflowScopeTable').length ? 'scope' : 'material');
    setPurchasingReviewStatus(false, section);
    if (section === 'purchasing') { $('.workflow-save-purchasing-form').prop('disabled', false); }
    else { $('.workflow-save-review[data-review-section="' + section + '"]').prop('disabled', false); }
  });
  $(document).on('click', '.workflow-add-row', function() { addReviewRow($(this).data('section')); });
  $(document).on('click', '.workflow-delete-row', function() {
    var row = $(this).closest('tr'), table = row.closest('table'), id = Number(row.data('id'));
    if (!id) { row.remove(); return; }
    Swal.fire({title:'Hapus item?',input:'textarea',inputPlaceholder:'Alasan penghapusan (wajib)',showCancelButton:true,confirmButtonText:'Hapus',confirmButtonColor:'#dc3545',preConfirm:function(value){if(!$.trim(value)){Swal.showValidationMessage('Alasan wajib diisi.');return false;}return value;}}).then(function(result) { if (!result.value) return; var deleted=table.data('deleted')||[]; deleted.push({id:id,reason:result.value}); table.data('deleted',deleted); row.remove(); });
  });
  $('.workflow-comparison-price').each(function(){ formatPriceInput($(this)); updatePurchasingEstimate($(this).closest('tr')); });
  previewPurchasingTotals();
  $(document).on('input', '.workflow-qty, .workflow-comparison-price', function() { var row=$(this).closest('tr'), price=row.data('new') ? priceValue(row.find('.workflow-comparison-price').val()) : Number(row.find('.workflow-estimate').data('price')), qty=Number(row.find('.workflow-qty').val()); row.find('.workflow-estimate').text(formatRupiah((price||0)*(qty||0))); updatePurchasingEstimate(row); previewTotals(); previewPurchasingTotals(); });
  $(document).on('blur', '.workflow-comparison-price', function(){ formatPriceInput($(this)); });
  $(document).on('click', '.workflow-add-draft:not(:disabled)', function() { var button=$(this).prop('disabled',true), row=button.closest('tr'), comparison=priceValue(row.find('.workflow-comparison-price').val()), price=comparison !== null ? comparison : Number(row.find('.workflow-estimate').data('price')); $.ajax({url:endpoints.draft,method:'POST',dataType:'json',headers:{'X-POJASA-CSRF':csrfToken},data:{csrf_token:csrfToken,kd_po_jasa:$('#workflowDetailCode').val(),id_material:row.data('material'),qty:button.data('qty'),harga_estimasi:price,id_vendor_jasa:'',keterangan:'Dibuat dari review Purchasing.',idempotency_token:uuid()}}).done(function(response){updateCsrf(response);if(!response.success){showResponseError(response);button.prop('disabled',false);return;}Swal.fire('Draft pembelian dibuat',response.message,'success');loadState($('#workflowDetailCode').val(),false);}).fail(function(xhr){button.prop('disabled',false);showAjaxError(xhr,'Draft pembelian gagal dibuat.');}); });

  $('#workflowDraftPurchaseList').on('click', function() {
    var code = $('#workflowDetailCode').val();
    if (currentState && currentState.request && currentState.request.kd_po_jasa === code) {
      renderPurchaseDraftRows(currentState.purchase_drafts || []);
      $('#workflowDraftPurchaseModal').modal('show');
      return;
    }
    $.ajax({url:endpoints.state + encodeURIComponent(code), method:'GET', dataType:'json', cache:false})
      .done(function(response) {
        updateCsrf(response);
        if (!response.success) { showResponseError(response); return; }
        currentState = response.data;
        applyDetailState(response.data);
        renderPurchaseDraftRows(response.data.purchase_drafts || []);
        $('#workflowDraftPurchaseModal').modal('show');
      }).fail(function(xhr) { showAjaxError(xhr, 'Draft PO Pembelian gagal dimuat.'); });
  });
  $(document).on('click', '.workflow-revise-draft', function() {
    var button = $(this), draftId = Number(button.data('draft-id')), material = String(button.data('material') || '');
    if (!draftId) { return; }
    // Bootstrap traps focus inside its open modal. Close the draft list first
    // so the SweetAlert textarea can receive keyboard focus normally.
    $('#workflowDraftPurchaseModal').one('hidden.bs.modal', function() {
      Swal.fire({
        title: 'Revisi draft pembelian?',
        html: 'Draft <strong>' + escapeHtml(material) + '</strong> akan dibatalkan dan dapat dibuat ulang dari tombol keranjang material.',
        input: 'textarea', inputPlaceholder: 'Alasan revisi (wajib)', showCancelButton: true,
        confirmButtonText: 'Ya, revisi draft', confirmButtonColor: '#dc3545',
        preConfirm: function(reason) { if (!$.trim(reason)) { Swal.showValidationMessage('Alasan revisi wajib diisi.'); return false; } return reason; }
      }).then(function(result) {
        if (!result.value) { $('#workflowDraftPurchaseModal').modal('show'); return; }
        button.prop('disabled', true);
        $.ajax({url:endpoints.draftCancel, method:'POST', dataType:'json', headers:{'X-POJASA-CSRF':csrfToken}, data:{csrf_token:csrfToken, kd_po_jasa:$('#workflowDetailCode').val(), id_draft_pembelian:draftId, reason:result.value}})
          .done(function(response) {
            updateCsrf(response);
            if (!response.success) { button.prop('disabled', false); showResponseError(response); return; }
            Swal.fire('Draft direvisi', 'Draft dibatalkan. Silakan gunakan tombol keranjang pada material untuk menginput ulang quantity.', 'success');
            loadState($('#workflowDetailCode').val(), false);
          }).fail(function(xhr) { button.prop('disabled', false); showAjaxError(xhr, 'Draft pembelian gagal direvisi.'); });
      });
    }).modal('hide');
  });

  $(document).on('click', '.workflow-document-preview', function() {
    var button = $(this), url = String(button.data('url') || ''), type = String(button.data('type') || '');
    $('#workflowDocumentTitle').text(button.data('title') || 'Preview dokumen');
    $('#workflowDocumentBody').html(type === 'pdf'
      ? '<iframe title="Preview PDF" src="' + escapeHtml(url) + '" style="width:100%;height:70vh;border:0"></iframe>'
      : '<img src="' + escapeHtml(url) + '" alt="Preview dokumen" class="img-fluid">');
    $('#workflowDocumentModal').modal('show');
  });
  $('#workflowDocumentModal').on('hidden.bs.modal', function() { $('#workflowDocumentBody').empty(); });

  $(document).on('click', '.workflow-planning-detail', function() {
    var row = $(this).closest('tr'), type = String($(this).data('type') || ''), materialId = Number(row.data('material'));
    $('#workflowPlanningHead').html(type === 'allocation' ? '<tr><th>Barang</th><th>Qty alokasi</th><th>Diterima/Dilepas</th><th>Status</th><th>Pembuat</th><th>Waktu/Riwayat</th></tr>' : '<tr><th>Vendor</th><th>Qty/Harga</th><th>Status</th><th>Keterangan</th><th>Pembuat</th><th>Waktu/Riwayat</th></tr>');
    $('#workflowPlanningBody').html('<tr><td colspan="6" class="text-center">Memuat...</td></tr>');
    $('#workflowPlanningModal').modal('show');
    $.ajax({ url: endpoints.planning + encodeURIComponent(type) + '/' + materialId, method: 'GET', dataType: 'json', data: { kd_po_jasa: $('#workflowDetailCode').val() } })
      .done(function(response) {
        updateCsrf(response); var body = $('#workflowPlanningBody').empty(), items = response.success ? (response.data.items || []) : [];
        if (!items.length) { body.html('<tr><td colspan="6" class="text-center text-muted">Belum ada data.</td></tr>'); return; }
        items.forEach(function(item) {
          body.append(type === 'allocation'
            ? '<tr><td>' + escapeHtml(item.nama_barang || item.kd_barang || '-') + '</td><td>' + escapeHtml(item.qty_allocation) + '</td><td>' + escapeHtml(item.qty_received) + ' / ' + escapeHtml(item.qty_released) + '</td><td>' + escapeHtml(item.status_allocation) + '</td><td>' + escapeHtml(item.created_name || '-') + '</td><td>' + escapeHtml(item.allocated_at || '-') + '<br><small>Versi ' + Number(item.version || 1) + (item.release_reason ? ' · ' + escapeHtml(item.release_reason) : '') + '</small></td></tr>'
            : '<tr><td>' + escapeHtml(item.nama_vendor || '-') + '</td><td>' + escapeHtml(item.qty) + ' · ' + formatRupiah(item.harga_estimasi) + '</td><td>' + escapeHtml(item.status_draft) + '</td><td>' + escapeHtml(item.keterangan || '-') + '</td><td>' + escapeHtml(item.created_name || '-') + '</td><td>' + escapeHtml(item.created_at || '-') + '<br><small>Versi ' + Number(item.version || 1) + (item.updated_at ? ' · diubah ' + escapeHtml(item.updated_at) : '') + (item.cancel_reason ? ' · ' + escapeHtml(item.cancel_reason) : '') + '</small></td></tr>');
        });
      }).fail(function(xhr) { $('#workflowPlanningModal').modal('hide'); showAjaxError(xhr, 'Detail pemenuhan gagal dimuat.'); });
  });

  function loadState(code, openModal) {
    if (!code) { return; }
    $('#workflowModalLoading').removeClass('d-none');
    $('#workflowModalContent').addClass('d-none');
    if (openModal) { $('#workflowActionModal').modal('show'); }
    $.ajax({ url: endpoints.state + encodeURIComponent(code), method: 'GET', dataType: 'json', cache: false })
      .done(function(response) {
        updateCsrf(response);
        if (!response.success) { $('#workflowActionModal').modal('hide'); showResponseError(response); return; }
       currentState = response.data;
       if (openModal && response.data.draft_review_required) {
          renderModal(response.data);
          showWorkflowUpdateError({
            code: 'PURCHASE_DRAFT_REVIEW_REQUIRED',
            data: { pending_material_count: response.data.draft_review_count, pending_materials: response.data.draft_review_materials || [] }
          });
          if ($('#workflowDetailCode').val() === code) { applyDetailState(response.data); }
          return;
        }
        renderModal(response.data);
        if ($('#workflowDetailCode').val() === code) { applyDetailState(response.data); }
      }).fail(function(xhr) { $('#workflowActionModal').modal('hide'); showAjaxError(xhr, 'Status workflow gagal dimuat.'); });
  }

  function renderModal(state) {
    selectedAction = '';
    var request = state.request;
    $('#workflowModalCode').val(request.kd_po_jasa);
    $('#workflowModalStatus').val(request.status);
    $('#workflowModalVersion').val(request.status_version);
    $('#workflowSummaryCode').text(request.kd_po_jasa);
    $('#workflowSummaryPic').text(request.nm_user || '-');
    $('#workflowSummaryDepartment').text(request.departemen || '-');
    $('#workflowSummaryStatus').text(request.status);
    $('#workflowSummaryVendor').text(request.vendor || '-');
    var isFinalApproval = !!state.final_approval_view;
    $('#workflowSummaryTotalLabel').text(isFinalApproval ? 'Estimasi Final' : 'Estimasi');
    $('#workflowSummaryTotal').text(formatRupiah(isFinalApproval ? request.estimasi_final : request.estimasi_total));
    $('#workflowSummaryPurpose').text(request.tujuan_pekerjaan || '-');
    $('#workflowOpenDetail').attr('href', state.detail_url);
    $('#workflowActionNote').val('');
    clearWorkflowActionError();
    $('#workflowExecuteAction').prop('disabled', true).removeClass().addClass('btn btn-primary');
    var choices = $('#workflowActionChoices').empty();
    var actions = state.allowed_actions || [];
    var revisionBlocked = actions.indexOf('SUBMIT') !== -1 && !state.revision_ready;
    $('#workflowRevisionSaveHint').toggleClass('d-none', !revisionBlocked);
    if (!revisionBlocked) {
      actions.forEach(function(action) {
        var meta = actionMeta[action];
        if (!meta) { return; }
        choices.append('<button type="button" class="btn btn-outline-' + meta.color + ' mr-2 mb-2 workflow-action-choice" data-action="' + action + '"><i class="fas ' + meta.icon + ' mr-1"></i>' + actionLabel(action, request) + '</button>');
      });
    }
    if (!actions.length) { choices.html('<span class="text-muted">Tidak ada aksi untuk status dan role aktif.</span>'); }
    renderApprovalRows('#workflowModalHistory', state.approvals || [], 5, true);
    $('#workflowModalLoading').addClass('d-none');
    $('#workflowModalContent').removeClass('d-none');
  }

  function executeAction(note) {
    var button = $('#workflowExecuteAction').prop('disabled', true);
    $.ajax({
      url: endpoints.action, method: 'POST', dataType: 'json', headers: { 'X-POJASA-CSRF': csrfToken },
      data: {
        csrf_token: csrfToken, kd_po_jasa: currentState.request.kd_po_jasa,
        action: selectedAction, note: note, idempotency_token: uuid(),
        expected_status: currentState.request.status, status_version: currentState.request.status_version
      }
    }).done(function(response) {
      updateCsrf(response);
      if (!response.success) {
        if (selectedAction === 'UPDATE') { showWorkflowUpdateError(response); }
        else { showResponseError(response); }
        if (response.code === 'CONCURRENT_UPDATE') { loadState(currentState.request.kd_po_jasa, false); }
        return;
      }
      var code = currentState.request.kd_po_jasa;
      $('#workflowActionModal').modal('hide');
      Swal.fire('Berhasil', response.message + (response.data.no_spk ? ' Nomor SPK: ' + response.data.no_spk : ''), 'success');
      if (table) { table.ajax.reload(null, false); }
      if ($('#workflowDetailCode').val() === code) { loadState(code, false); }
      $(document).trigger('pojasa:refresh-notifications');
    }).fail(function(xhr) {
      var response = xhr.responseJSON || {};
      if (selectedAction === 'UPDATE') { showWorkflowUpdateError(response); }
      else { showAjaxError(xhr, 'Aksi workflow gagal diproses.'); }
    })
      .always(function() { button.prop('disabled', false); });
  }

  function applyDetailState(state) {
    if (!state || !state.request || !$('#workflowDetailCode').length) { return; }
    $('#workflowDetailStatus').text(state.request.status);
    $('#workflowDetailVersion').val(state.request.status_version);
    $('#workflowDetailExpectedStatus').val(state.request.status);
    $('#workflowDetailRevision').text(state.request.revision_no);
    $('#workflowDetailVendor').text(state.request.vendor || '-');
    $('#workflowPurchasingNote').val(state.request.purchasing_note || '');
    renderPurchasingReviewStatus(state.request, state.review_segments || null);
    if (state.purchasing_totals) { renderPurchasingTotals(state.purchasing_totals); }
    var actions = state.allowed_actions || [];
    $('#workflowDetailProcess').toggleClass('d-none', !actions.length || state.request.status === 'MENUNGGU_PENERBITAN_PURCHASING').data('code', state.request.kd_po_jasa);
    $('#workflowIssueSpkPurchase').toggleClass('d-none', state.request.status !== 'MENUNGGU_PENERBITAN_PURCHASING').data('code', state.request.kd_po_jasa);
    (state.materials || []).forEach(function(material) {
      var row = $('#workflowMaterialTable tbody tr[data-material="' + Number(material.id_material) + '"]');
      row.find('.js-reserved').text(material.reserved_qty); row.find('.js-draft').text(material.active_draft_qty); row.find('.js-ready').val(material.qty_ready);
      row.find('.js-remaining').text(material.remaining_need); row.find('.js-source').text(material.computed_source);
      row.find('.js-fulfillment').text(material.fulfillment_status);
      var draftButton = row.find('.workflow-add-draft');
      var remainingNeed = Number(material.remaining_need || 0);
      draftButton.data('qty', remainingNeed).attr('data-qty', remainingNeed);
      if ($('#workflowMaterialTable').data('editing')) {
        draftButton.prop('disabled', !(remainingNeed > 0));
      }
    });
    renderApprovalRows('#workflowDetailApprovals', state.approvals || [], 0, false);
    renderRevisionRows(state.revisions || []);
    renderPurchasingRevisionNotes(state.purchasing_revision_notes || []);
  }

  function renderPurchaseDraftRows(drafts) {
    var body = $('#workflowDraftPurchaseBody').empty();
    if (!drafts.length) { body.append('<tr><td colspan="5" class="text-center text-muted">Belum ada draft PO Pembelian aktif.</td></tr>'); return; }
    drafts.forEach(function(draft) {
      body.append('<tr><td><strong>' + escapeHtml(draft.nama_material) + '</strong><br><small class="text-muted">' + escapeHtml(draft.satuan || '-') + '</small></td><td class="text-right">' + Number(draft.qty || 0).toLocaleString('id-ID', {maximumFractionDigits: 2}) + '</td><td class="text-right">' + formatRupiah(draft.harga_estimasi || 0) + '</td><td>' + escapeHtml(draft.keterangan || '-') + '</td><td><button type="button" class="btn btn-xs btn-outline-warning workflow-revise-draft" data-draft-id="' + Number(draft.id_draft_pembelian) + '" data-material="' + escapeHtml(draft.nama_material) + '"><i class="fas fa-edit mr-1"></i>Revisi</button></td></tr>');
    });
  }

  function renderApprovalRows(selector, rows, limit, modal) {
    var target = $(selector).empty();
    var items = limit ? rows.slice(0, limit) : rows;
    if (!items.length) { target.append('<tr><td colspan="' + (modal ? 5 : 6) + '" class="text-center text-muted">Belum ada approval.</td></tr>'); return; }
    items.forEach(function(row) {
      var cells = '<td>' + escapeHtml(row.created_at) + '</td><td>' + escapeHtml(row.approval_stage) + '</td><td>' + escapeHtml(row.action) + '</td><td>' + escapeHtml(row.from_status) + ' → ' + escapeHtml(row.to_status) + '</td>';
      if (!modal) { cells += '<td>' + escapeHtml(row.actor_code) + ' (' + escapeHtml(row.actor_role) + ')</td>'; }
      cells += '<td>' + escapeHtml(row.catatan || '-') + '</td>';
      target.append('<tr>' + cells + '</tr>');
    });
  }

  function renderRevisionRows(rows) {
    var target = $('#workflowDetailRevisions');
    if (!target.length) { return; }
    target.empty();
    if (!rows.length) { target.append('<tr><td colspan="5" class="text-center text-muted">Belum ada revisi.</td></tr>'); return; }
    rows.forEach(function(row) {
      target.append('<tr><td>' + Number(row.revision_no || 0) + '</td><td>' + escapeHtml(row.revision_source) + '</td><td>' + escapeHtml(row.requested_at) + '</td><td>' + escapeHtml(row.alasan_revisi) + '</td><td>' + escapeHtml(row.submitted_at || '-') + '</td></tr>');
    });
  }

  function renderPurchasingRevisionNotes(rows) {
    var target = $('#workflowDetailPurchasingRevisionNotes');
    if (!target.length) { return; }
    target.empty();
    if (!rows.length) { target.append('<tr><td colspan="4" class="text-center text-muted">Belum ada catatan perbaikan dari Purchasing.</td></tr>'); return; }
    rows.forEach(function(row) {
      target.append('<tr><td>' + Number(row.revision_no || 0) + '</td><td>' + escapeHtml(row.created_at) + '</td><td>' + escapeHtml(row.actor_name || row.actor_code || '-') + '</td><td style="white-space:pre-wrap">' + escapeHtml(row.catatan || '-') + '</td></tr>');
    });
  }

  function renderPurchasingReviewStatus(request, reviewSegments) {
    if (reviewSegments) {
      ['purchasing', 'scope', 'material'].forEach(function(segment) {
        setPurchasingReviewStatus(!!reviewSegments[segment], segment);
      });
      return;
    }
    var revisionStatuses = ['REVISI_PURCHASING_KADEP', 'REVISI_PURCHASING_DIROPS', 'REVISI_PURCHASING_DIRUT'];
    var isRevision = revisionStatuses.indexOf(String(request.status || '')) !== -1;
    var saved = isRevision
      ? !!request.purchasing_revision_saved_at && request.purchasing_revision_status === request.status
      : !!request.reviewed_at_purchasing;
    ['purchasing', 'scope', 'material'].forEach(function(segment) { setPurchasingReviewStatus(saved, segment); });
  }

  function setPurchasingReviewStatus(saved, segment) {
    var indexes = { purchasing: 0, scope: 1, material: 2 };
    var badge = segment ? $('.workflow-review-status').eq(indexes[segment]) : $('.workflow-review-status');
    if (!badge.length) { return; }
    badge.toggleClass('badge-success', !!saved).toggleClass('badge-warning', !saved);
    badge.find('i').toggleClass('fa-check-circle', !!saved).toggleClass('fa-exclamation-circle', !saved);
    badge.find('.workflow-review-status-text').text(saved ? 'Sudah direview & tersimpan' : 'Belum direview & belum tersimpan');
  }

  function previewTotals() {
    var scope = sumTable('#workflowScopeTable');
    var material = sumTable('#workflowMaterialTable');
    renderTotals({ jasa: scope, material: material, grand_total: scope + material });
  }

  function sumTable(selector) {
    var total = 0;
    $(selector + ' tbody tr[data-id]').each(function() {
      var qty = $(this).find('.workflow-qty').length ? Number($(this).find('.workflow-qty').val()) : (Number($(this).children('td').eq(2).text()) || 0);
      var price = $(this).data('new') ? priceValue($(this).find('.workflow-comparison-price').val()) : Number($(this).find('.workflow-estimate').data('price'));
      total += qty * price;
    });
    return total;
  }

  function collectReviewRows(selector) {
    var rows=[];
    $(selector+' tbody tr[data-id], '+selector+' tbody tr[data-new="1"]').each(function(){var row=$(this), price=priceValue(row.find('.workflow-comparison-price').val());rows.push({id:Number(row.data('id'))||0,is_new:Number(row.data('new'))===1,nama_scope:selector==='#workflowScopeTable'?row.find('.workflow-name').val():undefined,nama_material:selector==='#workflowMaterialTable'?row.find('.workflow-name').val():undefined,deskripsi:row.find('.workflow-description').val(),qty:row.find('.workflow-qty').val(),satuan:row.find('.workflow-unit').val(),harga_pembanding:price === null ? '' : price,keterangan_purchasing:row.find('.workflow-purchasing-note').val()});});
    return rows;
  }

  function addReviewRow(section) {
    var isScope=section==='scope', table=isScope?$('#workflowScopeTable'):$('#workflowMaterialTable');
    table.find('.workflow-empty').remove();
    var action='<button type="button" class="btn btn-xs btn-outline-danger workflow-delete-row" title="Hapus"><i class="fas fa-trash"></i></button>';
    var html=isScope
      ? '<tr data-id="0" data-new="1"><td class="workflow-number">Baru</td><td class="workflow-table-detail"><input class="form-control form-control-sm workflow-name mb-1" maxlength="255" placeholder="Nama scope"><textarea class="form-control form-control-sm workflow-description" rows="2" maxlength="5000" placeholder="Deskripsi"></textarea></td><td><input type="number" min="0.01" step="0.01" class="form-control form-control-sm workflow-qty mb-1" placeholder="Qty"><input class="form-control form-control-sm workflow-unit" maxlength="50" placeholder="Satuan"></td><td><span class="workflow-cell-label">Harga PIC</span><div class="text-muted text-right">-</div><span class="workflow-cell-label mt-2">Estimasi PIC</span><div class="workflow-estimate text-right" data-price="0">Rp 0</div></td><td><div class="workflow-purchasing-estimate text-right">-</div></td><td class="workflow-table-purchasing"><span class="workflow-cell-label">Harga Pembanding</span><input type="text" inputmode="numeric" class="form-control form-control-sm workflow-comparison-price mb-2" placeholder="Harga satuan"><span class="workflow-cell-label">Keterangan</span><textarea class="form-control form-control-sm workflow-purchasing-note" rows="2" maxlength="5000" placeholder="Wajib jika harga pembanding diisi"></textarea></td><td class="workflow-table-actions">'+action+'</td></tr>'
      : '<tr data-id="0" data-material="0" data-new="1"><td class="workflow-table-detail"><input class="form-control form-control-sm workflow-name mb-1" maxlength="255" placeholder="Nama material"><textarea class="form-control form-control-sm workflow-description" rows="2" maxlength="5000" placeholder="Deskripsi"></textarea><input class="form-control form-control-sm workflow-unit mt-1" maxlength="50" placeholder="Satuan"></td><td class="workflow-stock-column"><span class="workflow-cell-label">Sumber / Status</span><span class="badge badge-secondary">BARANG MASTER MANUAL</span><span class="workflow-cell-label mt-2">Stok tersedia</span><input type="number" min="0" step="0.01" class="form-control form-control-sm js-ready" value="0" placeholder="0" readonly></td><td class="workflow-need-column"><input type="number" min="0" step="0.01" class="form-control form-control-sm workflow-qty" placeholder="0"></td><td><span class="workflow-cell-label">Harga PIC</span><div class="text-muted text-right">-</div><span class="workflow-cell-label mt-2">Estimasi PIC</span><div class="workflow-estimate text-right" data-price="0">Rp 0</div></td><td><div class="workflow-purchasing-estimate text-right">-</div></td><td class="workflow-table-purchasing"><span class="workflow-cell-label">Harga Pembanding</span><input type="text" inputmode="numeric" class="form-control form-control-sm workflow-comparison-price mb-2" placeholder="Harga satuan"><span class="workflow-cell-label">Keterangan</span><textarea class="form-control form-control-sm workflow-purchasing-note" rows="2" maxlength="5000" placeholder="Wajib jika harga pembanding diisi"></textarea></td><td class="workflow-table-actions">'+action+'</td></tr>';
    table.find('tbody').append(html); table.find('tbody tr:last .workflow-delete-row').prop('disabled', !table.data('editing')); table.find('tbody tr:last input, tbody tr:last textarea').toggleClass('workflow-edit-active', !!table.data('editing'));
  }

  function renderTotals(totals) {
    $('#workflowDetailScopeTotal').text(formatRupiah(totals.jasa));
    $('#workflowDetailMaterialTotal').text(formatRupiah(totals.material));
    $('#workflowDetailGrandTotal').text(formatRupiah(totals.grand_total));
  }

  function renderPurchasingTotals(totals) {
    $('#workflowPurchasingScopeTotal').text(formatRupiah(totals.jasa));
    $('#workflowPurchasingMaterialTotal').text(formatRupiah(totals.material));
    $('#workflowPurchasingGrandTotal').text(formatRupiah(totals.grand_total));
  }

  function previewPurchasingTotals() {
    var totalFor = function(selector) {
      var total = 0;
      $(selector + ' tbody tr[data-id], ' + selector + ' tbody tr[data-new="1"]').each(function() {
        var row = $(this), qty = Number(row.find('.workflow-qty').val()) || 0;
        var purchasingPrice = priceValue(row.find('.workflow-comparison-price').val());
        var picPrice = Number(row.find('.workflow-estimate').data('price')) || 0;
        total += qty * (purchasingPrice !== null && purchasingPrice > 0 ? purchasingPrice : picPrice);
      });
      return total;
    };
    var jasa = totalFor('#workflowScopeTable'), material = totalFor('#workflowMaterialTable');
    renderPurchasingTotals({ jasa: jasa, material: material, grand_total: jasa + material });
  }

  function confirmationText(action, request) {
    if (action === 'ACC' && request.status === 'MENUNGGU_DIREKTUR') { return 'ACC Direktur hanya memperbarui status persetujuan request. Penerbitan SPK dan PO Pembelian otomatis akan diproses oleh Purchasing.'; }
    if (action === 'TERBITKAN') { return 'SPK akan diterbitkan dan draft material aktif akan dibuatkan menjadi PO Pembelian secara otomatis.'; }
    if (action === 'PENDING') { return 'Request akan tetap terkunci untuk PIC sampai KADEP memberi tindakan lanjutan.'; }
    if (action === 'REVISI') { return 'Request akan dikembalikan ke pihak yang wajib melakukan perbaikan.'; }
    if (action === 'REJECT') { return 'Request akan masuk ke status penolakan terminal.'; }
    if (action === 'UPDATE' && request.status === 'MENUNGGU_PURCHASING_AWAL') { return 'Seluruh update Purchasing akan dikirim ke PIC untuk ditinjau dan dikonfirmasi.'; }
    if (action === 'ACC' && request.status === 'MENUNGGU_KONFIRMASI_PIC') { return 'PIC menyatakan data Purchasing telah disepakati. Request dikembalikan ke Purchasing untuk diajukan ke KADEP.'; }
    if (action === 'SUBMIT') { return 'Request akan diajukan kembali ke approver yang meminta revisi.'; }
    return 'Status request akan diperbarui.';
  }

  function actionLabel(action, request) {
    if (request.status === 'MENUNGGU_KONFIRMASI_PIC' && action === 'ACC') { return 'KONFIRMASI KESEPAKATAN'; }
    if (request.status === 'MENUNGGU_KONFIRMASI_PIC' && action === 'REVISI') { return 'REVISI DATA'; }
    return actionMeta[action].label;
  }

  function updateCsrf(response) {
    if (response && response.data && response.data.csrf_token) { csrfToken = response.data.csrf_token; }
    else if (response && response.csrf_token) { csrfToken = response.csrf_token; }
  }

  function showResponseError(response) {
    var errors = response.errors || {};
    var messages = Object.keys(errors).map(function(key) { return escapeHtml(errors[key]); });
    Swal.fire({ title: response.code === 'CONCURRENT_UPDATE' ? 'Data sudah berubah' : 'Aksi gagal', html: messages.length ? messages.join('<br>') : escapeHtml(response.message || 'Aksi tidak dapat diproses.'), icon: response.code === 'VALIDATION_ERROR' || response.code === 'NOTE_REQUIRED' ? 'warning' : 'error' });
  }

  function clearWorkflowActionError() {
    $('#workflowActionError').addClass('d-none');
    $('#workflowActionErrorDetail').empty();
    $('#workflowActionNote').removeClass('is-invalid');
    $('#workflowOpenDetail').removeClass('btn-danger').addClass('btn-info').html('<i class="fas fa-eye mr-1"></i> Buka detail');
    $('#workflowActionChoices [data-action="UPDATE"]').removeClass('btn-danger').addClass('btn-outline-primary');
  }

  function showWorkflowUpdateError(response) {
    var code = String(response.code || '');
    var data = response.data || {};
    var detail = '';
    clearWorkflowActionError();
    if (code === 'PURCHASE_DRAFT_REVIEW_REQUIRED') {
      var materials = Array.isArray(data.pending_materials) ? data.pending_materials.filter(Boolean) : [];
      var materialList = materials.length ? '<ul class="mb-2 pl-3">' + materials.map(function(name) { return '<li>' + escapeHtml(name) + '</li>'; }).join('') + '</ul>' : '';
      detail = 'Material berikut masih memiliki kebutuhan yang belum dibuat atau ditinjau pada Draft PO Pembelian. Buka detail, klik <strong>Edit</strong>, lalu selesaikan tombol keranjang pada material tersebut.' + materialList;
      $('#workflowOpenDetail').removeClass('btn-info').addClass('btn-danger').html('<i class="fas fa-exclamation-triangle mr-1"></i> Buka detail &amp; perbaiki');
      $('#workflowActionChoices [data-action="UPDATE"]').removeClass('btn-outline-primary').addClass('btn-danger');
    } else if (code === 'NOTE_REQUIRED' || (response.errors && response.errors.note)) {
      detail = escapeHtml(response.message || 'Catatan update wajib diisi sebelum data dikirim ke PIC.');
      $('#workflowActionNote').addClass('is-invalid').trigger('focus');
    } else {
      detail = escapeHtml(response.message || 'Periksa kembali data Purchasing yang wajib diselesaikan sebelum mengirim update ke PIC.');
    }
    $('#workflowActionErrorDetail').html(detail);
    $('#workflowActionError').removeClass('d-none');
  }

  function showAjaxError(xhr, fallback) {
    var response = xhr.responseJSON || {};
    if (xhr.status === 401 && response.redirect) { window.location.href = response.redirect; return; }
    showResponseError({ code: response.code, message: response.message || fallback, errors: response.errors });
  }

  function formatRupiah(value) { return 'Rp ' + Math.round(Number(value) || 0).toLocaleString('id-ID'); }
  function priceValue(value) {
    var raw=String(value === undefined || value === null ? '' : value).replace(/[^0-9,.-]/g,'').trim();
    if(!raw) return null;
    if(raw.indexOf(',') >= 0) raw=raw.replace(/\./g,'').replace(',', '.');
    else if(/^\d{1,3}(?:\.\d{3})+$/.test(raw)) raw=raw.replace(/\./g,'');
    /* Nilai DECIMAL dari database, misalnya 150000.00, mempertahankan titik desimalnya. */
    var number=Number(raw);
    return isFinite(number) && number >= 0 ? number : null;
  }
  function formatPriceInput(input) { var value=priceValue(input.val()); input.val(value === null ? '' : Math.round(value).toLocaleString('id-ID')); }
  function updatePurchasingEstimate(row) { var purchasingPrice=priceValue(row.find('.workflow-comparison-price').val()), picPrice=Number(row.find('.workflow-estimate').data('price')) || 0, effectivePrice=purchasingPrice !== null && purchasingPrice > 0 ? purchasingPrice : picPrice, qty=Number(row.find('.workflow-qty').val()); row.find('.workflow-purchasing-price').text(formatRupiah(effectivePrice)); row.find('.workflow-purchasing-estimate').text(formatRupiah((qty || 0) * effectivePrice)); }
  function escapeHtml(value) { return $('<div>').text(value === null || value === undefined ? '' : value).html(); }
  function uuid() {
    if (window.crypto && window.crypto.getRandomValues) {
      var bytes = new Uint8Array(16); window.crypto.getRandomValues(bytes); bytes[6] = (bytes[6] & 15) | 64; bytes[8] = (bytes[8] & 63) | 128;
      return Array.prototype.map.call(bytes, function(byte, index) { return (index === 4 || index === 6 || index === 8 || index === 10 ? '-' : '') + ('0' + byte.toString(16)).slice(-2); }).join('');
    }
    return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, function(char) { var value = Math.random() * 16 | 0; return (char === 'x' ? value : (value & 3 | 8)).toString(16); });
  }
})(jQuery);
</script>
