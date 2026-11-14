(function () {
  const csrf = document.querySelector('meta[name="csrf-token"]')?.content;

  function headers(json = true) {
    const h = {
      Accept: 'application/json',
      'X-Requested-With': 'XMLHttpRequest',
    };
    if (csrf) h['X-CSRF-TOKEN'] = csrf;
    if (json) h['Content-Type'] = 'application/json';
    return h;
  }

  function escapeHtml(value) {
    return String(value)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#039;');
  }

  function setBadge(root, count) {
    const badge = root.querySelector('[data-notification-badge]');
    if (!badge) return;
    if (count > 0) {
      badge.classList.remove('d-none');
      badge.textContent = count > 99 ? '99+' : String(count);
    } else {
      badge.classList.add('d-none');
      badge.textContent = '0';
    }
  }

  function renderList(root, payload) {
    const list = root.querySelector('[data-notification-list]');
    if (!list) return;

    setBadge(root, payload.unread_count || 0);

    if (!payload.items || payload.items.length === 0) {
      list.innerHTML = '<div class="notification-state text-muted small p-3">لا توجد إشعارات</div>';
      return;
    }

    list.innerHTML = payload.items.map((item) => {
      const unreadClass = item.is_read ? '' : 'is-unread';
      return `
        <a class="notification-item ${unreadClass}" href="${item.open_url}">
          <div class="notification-item-icon"><i class="bi ${item.icon || 'bi-bell'}"></i></div>
          <div>
            <div class="notification-item-title">${escapeHtml(item.title || '')}</div>
            <div class="notification-item-body">${escapeHtml(item.body || '')}</div>
            <div class="notification-item-meta">
              <span>${escapeHtml(item.type_label || '')}</span>
              <span>${escapeHtml(item.created_at_human || '')}</span>
            </div>
          </div>
        </a>
      `;
    }).join('');
  }

  async function loadPreview(root) {
    const list = root.querySelector('[data-notification-list]');
    const url = root.getAttribute('data-preview-url');
    if (!url || !list) return;

    list.innerHTML = '<div class="notification-state text-muted small p-3">جاري التحميل...</div>';

    try {
      const res = await fetch(url + (url.includes('?') ? '&' : '?') + 'limit=8', {
        headers: headers(false),
        credentials: 'same-origin',
      });
      if (!res.ok) throw new Error('failed');
      renderList(root, await res.json());
    } catch (e) {
      list.innerHTML = '<div class="notification-state text-danger small p-3">تعذر تحميل الإشعارات</div>';
    }
  }

  async function refreshCount(root) {
    const url = root.getAttribute('data-count-url');
    if (!url) return;
    try {
      const res = await fetch(url, { headers: headers(false), credentials: 'same-origin' });
      if (!res.ok) return;
      const data = await res.json();
      setBadge(root, data.unread_count || 0);
    } catch (e) {
      // ignore
    }
  }

  function markAllUrl(root) {
    return (root.getAttribute('data-preview-url') || '').replace(/\/preview$/, '/read-all');
  }

  document.querySelectorAll('[data-notification-dropdown]').forEach((root) => {
    root.addEventListener('show.bs.dropdown', () => loadPreview(root));

    root.querySelector('[data-notification-mark-all]')?.addEventListener('click', async (e) => {
      e.preventDefault();
      e.stopPropagation();
      try {
        const res = await fetch(markAllUrl(root), {
          method: 'POST',
          headers: headers(),
          credentials: 'same-origin',
          body: '{}',
        });
        if (!res.ok) throw new Error('failed');
        await loadPreview(root);
      } catch (err) {
        const list = root.querySelector('[data-notification-list]');
        if (list) {
          list.innerHTML = '<div class="notification-state text-danger small p-3">تعذر تحديث الإشعارات</div>';
        }
      }
    });

    refreshCount(root);
    setInterval(() => refreshCount(root), 45000);
  });
})();
