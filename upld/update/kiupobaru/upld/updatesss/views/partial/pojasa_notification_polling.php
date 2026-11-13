<script>
  (function($) {
    'use strict';

    var endpoint = '<?= base_url('pojasa/ajax/notifications') ?>';
    var readEndpoint = '<?= base_url('pojasa/ajax/notifications/read') ?>';
    var lastId = 0;
    var csrfToken = '';
    var requestActive = false;
    var stopped = false;
    var intervalMs = 45000;
    var timer = null;

    function escapeHtml(value) {
      return $('<div>').text(value === null || value === undefined ? '' : value).html();
    }

    function safeTargetUrl(value) {
      var target = String(value || '#');
      var applicationBase = '<?= rtrim(base_url(), '/') ?>';
      return target === '#' || target.indexOf(applicationBase + '/') === 0 ? target : '#';
    }

    function schedule(delay) {
      if (stopped) {
        return;
      }
      window.clearTimeout(timer);
      timer = window.setTimeout(poll, delay || intervalMs);
    }

    function render(data) {
      var count = Number(data.unread_count || 0);
      var items = Array.isArray(data.items) ? data.items : [];
      var badge = $('#pojasaNotificationBadge');
      csrfToken = data.csrf_token || csrfToken;
      lastId = Math.max(lastId, Number(data.latest_id || 0));

      badge.text(count > 99 ? '99+' : count).toggleClass('d-none', count < 1);
      $('#pojasaNotificationHeader').text(count + ' notifikasi belum dibaca');

      if (!items.length) {
        return;
      }

      var html = '';
      items.forEach(function(item) {
        html += '<a href="' + escapeHtml(safeTargetUrl(item.target_url)) + '" class="dropdown-item pojasa-notification-item" data-id="' + Number(item.id_notifikasi) + '">' +
          '<strong class="d-block text-sm">' + escapeHtml(item.title) + '</strong>' +
          '<span class="text-sm text-muted text-wrap">' + escapeHtml(item.message) + '</span>' +
          '</a><div class="dropdown-divider"></div>';
      });
      var container = $('#pojasaNotificationItems');
      if (!container.find('.pojasa-notification-item').length) {
        container.empty();
      }
      container.prepend(html);
      container.children().slice(40).remove();
    }

    function poll() {
      if (document.hidden || requestActive || !$('#pojasaNotificationWidget').length) {
        schedule(intervalMs);
        return;
      }

      requestActive = true;
      $.ajax({
        url: endpoint,
        method: 'GET',
        dataType: 'json',
        cache: false,
        data: { after_id: lastId, limit: 20 }
      }).done(function(response) {
        if (response && response.success && response.data) {
          render(response.data);
          intervalMs = 45000;
        }
      }).fail(function(xhr) {
        if (xhr.status === 401) {
          stopped = true;
          return;
        }
        intervalMs = Math.min(intervalMs * 2, 300000);
      }).always(function() {
        requestActive = false;
        if (!stopped) {
          schedule(intervalMs);
        }
      });
    }

    $(document).on('click', '.pojasa-notification-item', function(event) {
      var id = Number($(this).data('id') || 0);
      var target = $(this).attr('href') || '#';
      if (!id || !csrfToken) {
        return;
      }
      event.preventDefault();
      $.ajax({
        url: readEndpoint,
        method: 'POST',
        dataType: 'json',
        headers: { 'X-POJASA-CSRF': csrfToken },
        data: { id_notifikasi: id }
      }).done(function(response) {
        if (response && response.success && response.data) {
          render(response.data);
        }
      }).always(function() {
        if (target !== '#') {
          window.location.href = target;
        }
      });
    });

    document.addEventListener('visibilitychange', function() {
      if (!document.hidden) {
        schedule(250);
      }
    });
    window.addEventListener('focus', function() {
      schedule(250);
    });
    $(document).on('pojasa:refresh-notifications', function() {
      schedule(100);
    });

    $(function() {
      schedule(500);
    });
  })(jQuery);
</script>
