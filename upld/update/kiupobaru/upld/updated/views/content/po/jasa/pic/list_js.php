<script>
(function($) {
  'use strict';

  var csrfToken = <?= json_encode($csrf_token) ?>;
  var table = $('#pojasaPicTable').DataTable({
    processing: true,
    serverSide: true,
    responsive: true,
    pageLength: 10,
    order: [[0, 'desc']],
    ajax: {
      url: <?= json_encode(base_url('pojasa/ajax/pic/requests')) ?>,
      type: 'GET',
      data: function(data) {
        data.status = $('#filterStatus').val();
        data.date_from = $('#filterDateFrom').val();
        data.date_to = $('#filterDateTo').val();
      },
      dataSrc: function(response) {
        if (response.csrf_token) {
          csrfToken = response.csrf_token;
        }
        return response.data || [];
      },
      error: function(xhr) {
        showAjaxError(xhr, 'Daftar request gagal dimuat.');
      }
    },
    columns: [
      { data: 'tgl_request' },
      { data: 'vendor' },
      { data: 'total', className: 'text-left' },
      { data: 'status', orderable: false, className: 'pojasa-pic-status-column text-left align-middle' },
      { data: 'po_pembelian_status', orderable: false, searchable: false, className: 'pojasa-pic-status-column text-left align-middle' },
      { data: 'actions', orderable: false, searchable: false, className: 'pojasa-pic-actions-column text-center align-middle' }
    ],
    language: {
      processing: 'Memuat data...',
      emptyTable: 'Belum ada request PO Jasa.',
      zeroRecords: 'Data tidak ditemukan.'
    }
  });

  $('#pojasaPicFilter').on('submit', function(event) {
    event.preventDefault();
    table.ajax.reload();
  });

  $('#btnResetPojasaFilter').on('click', function() {
    $('#filterStatus, #filterDateFrom, #filterDateTo').val('');
    table.search('').ajax.reload();
  });

  $(document).on('click', '.btn-submit-request', function() {
    var code = String($(this).data('code') || '');
    Swal.fire({
      title: 'Ajukan request?',
      text: code + ' akan dikirim ke KADEP dan tidak dapat diedit selama menunggu approval.',
      icon: 'question',
      showCancelButton: true,
      confirmButtonText: 'Ya, ajukan',
      cancelButtonText: 'Batal'
    }).then(function(result) {
      if (!result.value) {
        return;
      }
      postAction(<?= json_encode(base_url('pojasa/ajax/pic/submit')) ?>, { kd_po_jasa: code }, function(response) {
        Swal.fire('Berhasil', response.message, 'success');
        table.ajax.reload(null, false);
      });
    });
  });

  $(document).on('click', '.btn-delete-draft', function() {
    var code = String($(this).data('code') || '');
    Swal.fire({
      title: 'Hapus data PO Jasa?',
      text: code + ' akan dihapus dari daftar. Riwayat audit tetap disimpan.',
      icon: 'warning',
      showCancelButton: true,
      confirmButtonColor: '#dc3545',
      confirmButtonText: 'Ya, hapus',
      cancelButtonText: 'Batal'
    }).then(function(result) {
      if (!result.value) {
        return;
      }
      postAction(<?= json_encode(base_url('pojasa/ajax/pic/draft/delete')) ?>, { kd_po_jasa: code }, function(response) {
        Swal.fire('Terhapus', response.message, 'success');
        table.ajax.reload(null, false);
      });
    });
  });

  function postAction(url, data, success) {
    data.csrf_token = csrfToken;
    $.ajax({
      url: url,
      method: 'POST',
      dataType: 'json',
      headers: { 'X-POJASA-CSRF': csrfToken },
      data: data
    }).done(function(response) {
      if (response.data && response.data.csrf_token) {
        csrfToken = response.data.csrf_token;
      }
      if (response.success) {
        success(response);
      } else {
        showResponseError(response);
      }
    }).fail(function(xhr) {
      showAjaxError(xhr, 'Aksi gagal diproses.');
    });
  }

  function showResponseError(response) {
    var errors = response.errors || {};
    var messages = Object.keys(errors).map(function(key) { return escapeHtml(errors[key]); });
    Swal.fire({
      title: response.code === 'SUBMIT_VALIDATION' ? 'Request belum lengkap' : 'Gagal',
      html: messages.length ? messages.join('<br>') : escapeHtml(response.message || 'Aksi gagal.'),
      icon: response.code === 'SUBMIT_VALIDATION' ? 'warning' : 'error'
    });
  }

  function showAjaxError(xhr, fallback) {
    var response = xhr.responseJSON || {};
    if (xhr.status === 401 && response.redirect) {
      window.location.href = response.redirect;
      return;
    }
    showResponseError({ code: response.code, message: response.message || fallback, errors: response.errors });
  }

  function escapeHtml(value) {
    return $('<div>').text(value === null || value === undefined ? '' : value).html();
  }
})(jQuery);
</script>
