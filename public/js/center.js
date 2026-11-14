(function () {
  const root = document.documentElement;
  const themeKey = 'knooz-theme';
  const csrf = document.querySelector('meta[name="csrf-token"]')?.content;

  function applyTheme(theme) {
    root.setAttribute('data-theme', theme);
    localStorage.setItem(themeKey, theme);
  }

  function currentTheme() {
    return root.getAttribute('data-theme') || localStorage.getItem(themeKey) || 'light';
  }

  const stored = localStorage.getItem(themeKey);
  if (stored && !root.getAttribute('data-theme')) {
    applyTheme(stored);
  }

  document.querySelectorAll('[data-theme-toggle]').forEach((btn) => {
    btn.addEventListener('click', async () => {
      const next = currentTheme() === 'dark' ? 'light' : 'dark';
      applyTheme(next);

      const url = btn.getAttribute('data-theme-url');
      if (url && csrf) {
        try {
          await fetch(url, {
            method: 'POST',
            headers: {
              'Content-Type': 'application/json',
              Accept: 'application/json',
              'X-CSRF-TOKEN': csrf,
              'X-Requested-With': 'XMLHttpRequest',
            },
            body: JSON.stringify({ theme: next }),
          });
        } catch (e) {
          // localStorage already saved
        }
      }
    });
  });

  const sidebar = document.getElementById('centerSidebar')
    || document.querySelector('.admin-sidebar');
  const overlay = document.getElementById('sidebarOverlay');

  function openSidebar() {
    sidebar?.classList.add('open');
    overlay?.classList.add('show');
    document.body.classList.add('sidebar-open');
  }

  function closeSidebar() {
    sidebar?.classList.remove('open');
    overlay?.classList.remove('show');
    document.body.classList.remove('sidebar-open');
  }

  function toggleSidebar() {
    if (sidebar?.classList.contains('open')) {
      closeSidebar();
    } else {
      openSidebar();
    }
  }

  document.querySelectorAll('[data-sidebar-toggle]').forEach((btn) => {
    btn.addEventListener('click', (e) => {
      e.preventDefault();
      e.stopPropagation();
      toggleSidebar();
    });
  });

  overlay?.addEventListener('click', (e) => {
    e.preventDefault();
    closeSidebar();
  });

  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') {
      closeSidebar();
    }
  });

  sidebar?.querySelectorAll('a.nav-link').forEach((link) => {
    link.addEventListener('click', () => {
      if (window.matchMedia('(max-width: 991.98px)').matches) {
        closeSidebar();
      }
    });
  });

  window.addEventListener('resize', () => {
    if (!window.matchMedia('(max-width: 991.98px)').matches) {
      closeSidebar();
    }
  });
})();
