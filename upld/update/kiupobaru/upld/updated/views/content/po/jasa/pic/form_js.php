<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
(function($) {
  'use strict';

  var csrfToken = <?= json_encode($csrf_token) ?>;
  var requestCode = <?= json_encode($request ? (string) $request->kd_po_jasa : '') ?>;
  var documents = [];
  var busy = false;
  var endpoints = {
    save: <?= json_encode(base_url('pojasa/ajax/pic/draft/save')) ?>,
    submit: <?= json_encode(base_url('pojasa/ajax/pic/submit')) ?>,
    upload: <?= json_encode(base_url('pojasa/ajax/pic/documents/upload')) ?>,
    removeDocument: <?= json_encode(base_url('pojasa/ajax/pic/documents/delete')) ?>,
    documents: <?= json_encode(base_url('pojasa/ajax/pic/documents/')) ?>
    , materialCatalog: <?= json_encode(base_url('pojasa/ajax/pic/material-catalog')) ?>
  };

  calculateAll();
  initializeMaterialCatalog($('#materialTable tbody'));
  if (requestCode) {
    loadDocuments();
  }

  $('#vendorCode').on('change', function() {
    if ($(this).val()) {
      $('#vendorProposal').val('');
    }
  });
  $('#vendorProposal').on('input', function() {
    if ($.trim($(this).val()) !== '') {
      $('#vendorCode').val('');
    }
  });
  $('#documentFiles').on('change', function() {
    var count = this.files ? this.files.length : 0;
    var label = count === 1 ? this.files[0].name : (count > 1 ? count + ' file dipilih' : 'Pilih satu atau beberapa file');
    $(this).next('.custom-file-label').text(label);
  });

  $('#addScopeRow').on('click', function() {
    $('#scopeTable tbody').append($('#scopeRowTemplate').html());
  });
  $('#addMaterialRow').on('click', function() {
    var row = $($('#materialRowTemplate').html());
    $('#materialTable tbody').append(row);
    initializeMaterialCatalog(row);
  });
  $(document).on('click', '.remove-line', function() {
    $(this).closest('tr').remove();
    calculateAll();
  });
  $(document).on('input change', '.line-qty, .line-price', calculateAll);

  $('#saveDraft').on('click', function() {
    saveDraft(false);
  });

  $('#submitRequest').on('click', function() {
    var errors = validateSubmitForm();
    if (errors.length) {
      showIncompleteFormAlert(errors);
      return;
    }
    if (!documents.length) {
      Swal.fire({
        title: 'Dokumen pendukung belum ada',
        text: 'Belum ada dokumen pendukung yang diunggah. Apakah request tetap ingin dilanjutkan?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Ya, tetap lanjutkan',
        cancelButtonText: 'Tidak, kembali'
      }).then(function(result) {
        if (result.value) confirmSubmit(true);
      });
      return;
    }
    confirmSubmit(false);
  });

  function confirmSubmit(allowWithoutDocuments) {
    Swal.fire({
      title: 'Ajukan request?',
      text: 'Data terbaru akan disimpan, lalu request dikirim ke Purchasing. Selama menunggu approval request tidak dapat diedit.',
      icon: 'question',
      showCancelButton: true,
      confirmButtonText: 'Ya, simpan dan ajukan',
      cancelButtonText: 'Batal'
    }).then(function(result) {
      if (!result.value) {
        return;
      }
      saveDraft(true, allowWithoutDocuments);
    });
  }

  $('#uploadDocuments').on('click', function() {
    var input = $('#documentFiles')[0];
    if (!requestCode) {
      Swal.fire('Simpan draft dahulu', 'Nomor request diperlukan sebelum upload dokumen.', 'warning');
      return;
    }
    if (!input.files || !input.files.length) {
      Swal.fire('Dokumen belum dipilih', 'Pilih minimal satu dokumen untuk diunggah.', 'warning');
      return;
    }
    for (var i = 0; i < input.files.length; i++) {
      if (input.files[i].size > 10 * 1024 * 1024) {
        Swal.fire('Ukuran file terlalu besar', input.files[i].name + ' melebihi maksimum 10 MB.', 'warning');
        return;
      }
    }
    var formData = new FormData();
    formData.append('kd_po_jasa', requestCode);
    formData.append('keterangan', $('#documentNote').val());
    formData.append('csrf_token', csrfToken);
    for (var fileIndex = 0; fileIndex < input.files.length; fileIndex++) {
      formData.append('documents[]', input.files[fileIndex]);
    }
    setBusy(true);
    $('#uploadProgress').removeClass('d-none').find('.progress-bar').css('width', '0%').text('0%');
    $.ajax({
      url: endpoints.upload,
      method: 'POST',
      data: formData,
      dataType: 'json',
      processData: false,
      contentType: false,
      headers: { 'X-POJASA-CSRF': csrfToken },
      xhr: function() {
        var xhr = $.ajaxSettings.xhr();
        if (xhr.upload) {
          xhr.upload.addEventListener('progress', function(event) {
            if (event.lengthComputable) {
              var percent = Math.round((event.loaded / event.total) * 100);
              $('#uploadProgress .progress-bar').css('width', percent + '%').text(percent + '%');
            }
          });
        }
        return xhr;
      }
    }).done(function(response) {
      updateCsrf(response);
      if (!response.success) {
        showResponseError(response);
        setBusy(false);
        return;
      }
      documents = (response.data && response.data.items) || [];
      renderDocuments(true);
      $('#documentFiles').val('').next('.custom-file-label').text('Pilih satu atau beberapa file');
      $('#documentNote').val('');
      Swal.fire('Berhasil', response.message, 'success');
    }).fail(function(xhr) {
      showAjaxError(xhr, 'Upload dokumen gagal.');
    }).always(function() {
      setBusy(false);
      setTimeout(function() { $('#uploadProgress').addClass('d-none'); }, 600);
    });
  });

  $(document).on('click', '.delete-document', function() {
    var id = Number($(this).data('id'));
    var name = String($(this).data('name') || 'dokumen');
    Swal.fire({
      title: 'Hapus dokumen?',
      text: name + ' akan diarsipkan dari draft.',
      icon: 'warning',
      showCancelButton: true,
      confirmButtonColor: '#dc3545',
      confirmButtonText: 'Ya, hapus',
      cancelButtonText: 'Batal'
    }).then(function(result) {
      if (!result.value) {
        return;
      }
      post(endpoints.removeDocument, { id_dokumen: id }).done(function(response) {
        if (!response.success) {
          showResponseError(response);
          return;
        }
        documents = (response.data && response.data.items) || [];
        renderDocuments(true);
        Swal.fire('Terhapus', response.message, 'success');
      }).fail(function(xhr) {
        showAjaxError(xhr, 'Dokumen gagal dihapus.');
      });
    });
  });

  function saveDraft(andSubmit, allowWithoutDocuments) {
    if (busy) {
      return;
    }
    if (andSubmit && !$('#vendorCode').val() && $.trim($('#vendorProposal').val()) === '') {
      Swal.fire('Vendor wajib ditentukan', 'Pilih vendor terdaftar atau isi nama vendor/toko secara manual.', 'warning');
      return;
    }
    setBusy(true);
    $.ajax({
      url: endpoints.save,
      method: 'POST',
      dataType: 'json',
      headers: { 'X-POJASA-CSRF': csrfToken },
      data: {
        csrf_token: csrfToken,
        payload: JSON.stringify(collectPayload())
      }
    }).done(function(response) {
      updateCsrf(response);
      if (!response.success) {
        showResponseError(response);
        return;
      }
      activateSavedDraft(response.data);
      if (andSubmit) {
        submitSavedRequest(allowWithoutDocuments === true);
      } else {
        Swal.fire({ title: 'Draft tersimpan', text: response.message, icon: 'success', timer: 1600, showConfirmButton: false });
      }
    }).fail(function(xhr) {
      showAjaxError(xhr, 'Draft gagal disimpan.');
      if (andSubmit) {
        setBusy(false);
      }
    }).always(function() {
      if (!andSubmit) {
        setBusy(false);
      }
    });
  }

  function submitSavedRequest(allowWithoutDocuments) {
    post(endpoints.submit, { kd_po_jasa: requestCode, allow_without_documents: allowWithoutDocuments ? 1 : 0 }).done(function(response) {
      updateCsrf(response);
      if (!response.success) {
        if (response.code === 'SUBMIT_VALIDATION' && response.errors && response.errors.documents) {
          Swal.fire('Dokumen wajib', escapeHtml(response.errors.documents), 'warning');
        } else {
          showResponseError(response);
        }
        return;
      }
      $('#requestStatusBadge').removeClass().addClass('badge badge-warning').text(response.data.status);
      disableEditor();
      Swal.fire({
        title: 'Request diajukan',
        text: response.message,
        icon: 'success',
        confirmButtonText: 'Mengerti'
      }).then(function() {
        window.location.href = <?= json_encode(base_url('pojasa/pic')) ?>;
      });
    }).fail(function(xhr) {
      showAjaxError(xhr, 'Request gagal diajukan.');
    }).always(function() {
      setBusy(false);
    });
  }

  function activateSavedDraft(data) {
    requestCode = data.request_code;
    $('#requestCode').val(requestCode);
    $('#requestCodeLabel').text(requestCode);
    $('#requestStatusBadge').text(data.status);
    $('#documentDraftHint').addClass('d-none');
    $('#documentFiles, #documentNote, #uploadDocuments, #submitRequest').prop('disabled', false);
    if (data.edit_url && window.history && window.history.replaceState) {
      window.history.replaceState({}, document.title, data.edit_url);
    }
    calculateAll();
    loadDocuments();
  }

  function collectPayload() {
    var scopes = [];
    $('#scopeTable tbody tr').each(function() {
      scopes.push({
        nama_scope: $(this).find('.line-name').val(),
        deskripsi: $(this).find('.line-description').val(),
        qty: $(this).find('.line-qty').val(),
        satuan: $(this).find('.line-unit').val(),
        harga_estimasi: $(this).find('.line-price').val()
      });
    });
    var materials = [];
    $('#materialTable tbody tr').each(function() {
      materials.push({
        id_brg_nk: $(this).find('.material-catalog').val(),
        id_usulan_barang: $(this).find('.material-proposal-id').val(),
        reference_type: $(this).find('.material-reference-type').val(),
        nama_material: $(this).find('.line-name').val(),
        deskripsi: $(this).find('.line-description').val(),
        qty_kebutuhan: $(this).find('.line-qty').val(),
        satuan: $(this).find('.line-unit').val(),
        harga_estimasi: $(this).find('.line-price').val()
      });
    });
    return {
      kd_po_jasa: requestCode,
      kd_vendor_jasa: $('#vendorCode').val(),
      vendor_usulan: $('#vendorProposal').val(),
      tgl_mulai_pekerjaan: $('#plannedStartDate').val(),
      tgl_selesai_pekerjaan: $('#plannedCompletionDate').val(),
      tujuan_pekerjaan: $('#workPurpose').val(),
      catatan_pic: $('#picNotes').val(),
      scopes: scopes,
      materials: materials
    };
  }

  function validateSubmitForm() {
    var errors = [];
    var firstField = null;
    function missing(label, field) {
      errors.push(label);
      if (!firstField && field && field.length) firstField = field;
    }
    function blank(value) { return $.trim(String(value || '')) === ''; }

    var vendorCode = $('#vendorCode').val();
    var vendorProposal = $('#vendorProposal').val();
    if (!vendorCode && blank(vendorProposal)) missing('Vendor terdaftar atau Nama vendor / toko manual', $('#vendorCode'));
    if (vendorCode && !blank(vendorProposal)) missing('Pilih hanya satu sumber vendor', $('#vendorCode'));
    if (blank($('#plannedStartDate').val())) missing('Tanggal rencana start pekerjaan', $('#plannedStartDate'));
    if (blank($('#plannedCompletionDate').val())) missing('Target penyelesaian', $('#plannedCompletionDate'));
    if ($('#plannedStartDate').val() && $('#plannedCompletionDate').val() && $('#plannedCompletionDate').val() < $('#plannedStartDate').val()) {
      missing('Target penyelesaian tidak boleh lebih awal dari tanggal rencana start pekerjaan', $('#plannedCompletionDate'));
    }
    if (blank($('#workPurpose').val())) missing('Tujuan pekerjaan', $('#workPurpose'));

    var scopeRows = 0;
    $('#scopeTable tbody tr').each(function(index) {
      var row = $(this);
      var values = [row.find('.line-name').val(), row.find('.line-description').val(), row.find('.line-qty').val(), row.find('.line-unit').val(), row.find('.line-price').val()];
      if (values.every(blank)) return;
      scopeRows++;
      validateLine(row, 'Scope pekerjaan baris ' + (index + 1), errors, function(field) {
        if (!firstField) firstField = field;
      });
    });
    if (!scopeRows) missing('Minimal satu baris Scope Pekerjaan', $('#addScopeRow'));

    $('#materialTable tbody tr').each(function(index) {
      var row = $(this);
      var values = [row.find('.line-name').val(), row.find('.line-description').val(), row.find('.line-qty').val(), row.find('.line-unit').val(), row.find('.line-price').val()];
      if (values.every(blank)) return;
      validateLine(row, 'Material / Alat dan Bahan baris ' + (index + 1), errors, function(field) {
        if (!firstField) firstField = field;
      });
    });
    validateSubmitForm.firstField = firstField;
    return errors;
  }

  function validateLine(row, label, errors, setFirstField) {
    var fields = [
      { selector: '.line-name', label: 'Nama' },
      { selector: '.line-description', label: 'Deskripsi' },
      { selector: '.line-qty', label: 'Qty' },
      { selector: '.line-unit', label: 'Satuan' },
      { selector: '.line-price', label: 'Harga estimasi' }
    ];
    fields.forEach(function(item) {
      var field = row.find(item.selector);
      if ($.trim(String(field.val() || '')) === '') {
        errors.push(label + ': ' + item.label);
        setFirstField(field);
      }
    });
  }

  function showIncompleteFormAlert(errors) {
    var list = '<ul class="text-left mb-0 pl-4">' + errors.map(function(error) {
      return '<li>' + escapeHtml(error) + '</li>';
    }).join('') + '</ul>';
    Swal.fire({
      title: 'Form belum lengkap',
      html: 'Lengkapi isian berikut sebelum mengajukan request:' + list,
      icon: 'warning',
      confirmButtonText: 'Mengerti'
    }).then(function() {
      if (validateSubmitForm.firstField) validateSubmitForm.firstField.trigger('focus');
    });
  }

  function initializeMaterialCatalog(container) {
    container.find('.material-catalog').each(function() {
      var select = $(this);
      if (select.hasClass('select2-hidden-accessible')) return;
      select.select2({
        width: '100%', placeholder: 'Cari kode atau nama barang master', allowClear: true,
        ajax: {
          url: endpoints.materialCatalog, dataType: 'json', delay: 250,
          data: function(params) { return { term: params.term || '', page: params.page || 1 }; },
          processResults: function(response) { return response && response.success ? response.data : { results: [] }; }
        },
        minimumInputLength: 1,
        templateResult: function(item) {
          if (item.loading) return item.text;
          var qty = Number(item.qty_ready || 0);
          var color = qty > 0 ? 'success' : 'danger';
          return $('<div class="d-flex justify-content-between align-items-center"><span><strong></strong><small class="d-block text-muted"></small></span><span class="badge badge-' + color + ' ml-2"></span></div>')
            .find('strong').text(item.text || '').end()
            .find('small').text(item.deskripsi || '').end()
            .find('.badge').text('Qty ready: ' + qty.toLocaleString('id-ID', {maximumFractionDigits: 2}) + (item.satuan ? ' ' + item.satuan : '')).end();
        },
        templateSelection: function(item) { return item.text || ''; }
      });
      var selectedId = select.data('selected-id');
      var selectedText = select.data('selected-text');
      if (selectedId && selectedText) {
        select.append(new Option(selectedText, selectedId, true, true)).trigger('change.select2');
      }
      select.on('select2:select', function(event) {
        var item = event.params.data || {};
        var row = select.closest('.material-row');
        row.find('.material-reference-type').val('MASTER');
        row.find('.material-proposal-id').val('');
        row.find('.material-proposal-label').empty();
        row.find('.line-name').val(item.nama_barang || '').prop('readonly', true);
        row.find('.line-description').val(item.deskripsi || '').prop('readonly', true);
        if (item.satuan) row.find('.line-unit').val(item.satuan);
      }).on('select2:clear', function() {
        var row = select.closest('.material-row');
        row.find('.material-reference-type').val('MANUAL');
        row.find('.line-name, .line-description').prop('readonly', false);
      });
    });
  }

  function calculateAll() {
    var scope = calculateTable('#scopeTable');
    var material = calculateTable('#materialTable');
    $('#scopeTotal').text(formatRupiah(scope));
    $('#materialTotal').text(formatRupiah(material));
  }

  function calculateTable(selector) {
    var total = 0;
    $(selector + ' tbody tr').each(function() {
      var qty = parseFloat($(this).find('.line-qty').val()) || 0;
      var price = parseFloat($(this).find('.line-price').val()) || 0;
      var subtotal = qty * price;
      total += subtotal;
      $(this).find('.line-subtotal').text(formatRupiah(subtotal));
    });
    return total;
  }

  function loadDocuments() {
    if (!requestCode) {
      return;
    }
    $.ajax({ url: endpoints.documents + encodeURIComponent(requestCode), method: 'GET', dataType: 'json' })
      .done(function(response) {
        updateCsrf(response);
        if (response.success) {
          documents = (response.data && response.data.items) || [];
          renderDocuments(response.data.can_edit);
        }
      }).fail(function(xhr) { showAjaxError(xhr, 'Daftar dokumen gagal dimuat.'); });
  }

  function renderDocuments(canEdit) {
    var list = $('#documentList').empty();
    if (!documents.length) {
      list.append($('<div>', { 'class': 'col-12 text-muted', text: 'Belum ada dokumen.' }));
      return;
    }
    documents.forEach(function(item) {
      var card = $('<div>', { 'class': 'col-md-4 mb-3' });
      var body = $('<div>', { 'class': 'border rounded p-2 h-100' });
      if (item.is_image) {
        body.append($('<a>', { href: item.view_url, target: '_blank' }).append($('<img>', { src: item.view_url, alt: item.name, 'class': 'img-fluid rounded mb-2', style: 'max-height:130px;width:100%;object-fit:cover' })));
      } else {
        body.append($('<div>', { 'class': 'text-center text-secondary py-3' }).append($('<i>', { 'class': 'fas fa-file-alt fa-3x' })));
      }
      body.append($('<div>', { 'class': 'font-weight-bold text-truncate', title: item.name, text: item.name }));
      body.append($('<small>', { 'class': 'text-muted d-block', text: item.size_label + ' · revisi ' + item.revision_no }));
      var buttons = $('<div>', { 'class': 'mt-2' }).append($('<a>', { href: item.view_url, target: '_blank', 'class': 'btn btn-xs btn-info mr-1', text: 'Lihat' }));
      if (canEdit) {
        buttons.append($('<button>', { type: 'button', 'class': 'btn btn-xs btn-danger delete-document', 'data-id': item.id_dokumen, 'data-name': item.name, text: 'Hapus' }));
      }
      body.append(buttons);
      list.append(card.append(body));
    });
  }

  function post(url, data) {
    data.csrf_token = csrfToken;
    return $.ajax({ url: url, method: 'POST', dataType: 'json', headers: { 'X-POJASA-CSRF': csrfToken }, data: data })
      .done(updateCsrf);
  }

  function updateCsrf(response) {
    if (response && response.data && response.data.csrf_token) {
      csrfToken = response.data.csrf_token;
    } else if (response && response.csrf_token) {
      csrfToken = response.csrf_token;
    }
  }

  function setBusy(state) {
    busy = state;
    $('#saveDraft, #submitRequest, #uploadDocuments').prop('disabled', state);
    if (!state && requestCode) {
      $('#saveDraft, #submitRequest, #uploadDocuments').prop('disabled', false);
    }
  }

  function disableEditor() {
    $('#pojasaPicForm :input, #scopeTable :input, #materialTable :input, #addScopeRow, #addMaterialRow, #saveDraft, #submitRequest, #documentFiles, #documentNote, #uploadDocuments, .delete-document').prop('disabled', true);
  }

  function showResponseError(response) {
    var errors = response.errors || {};
    var messages = Object.keys(errors).map(function(key) { return escapeHtml(errors[key]); });
    Swal.fire({ title: 'Data belum dapat diproses', html: messages.length ? messages.join('<br>') : escapeHtml(response.message || 'Aksi gagal.'), icon: response.code === 'SUBMIT_VALIDATION' || response.code === 'VALIDATION_ERROR' ? 'warning' : 'error' });
  }

  function showAjaxError(xhr, fallback) {
    var response = xhr.responseJSON || {};
    if (xhr.status === 401 && response.redirect) {
      window.location.href = response.redirect;
      return;
    }
    showResponseError({ code: response.code, message: response.message || fallback, errors: response.errors });
  }

  function formatRupiah(value) {
    return 'Rp ' + Math.round(Number(value) || 0).toLocaleString('id-ID');
  }

  function escapeHtml(value) {
    return $('<div>').text(value === null || value === undefined ? '' : value).html();
  }
})(jQuery);
</script>
