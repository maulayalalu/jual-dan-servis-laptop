/* ============================================================
   A-LINKS Main JavaScript
   ============================================================ */

document.addEventListener('DOMContentLoaded', () => {

  /* ── Navbar scroll effect ── */
  const nav = document.getElementById('mainNav');
  if (nav) {
    window.addEventListener('scroll', () => {
      nav.classList.toggle('nav--scrolled', window.scrollY > 40);
    }, { passive: true });
  }

  /* ── Mobile drawer ── */
  const hamburger = document.getElementById('hamburger');
  const drawer    = document.getElementById('navDrawer');
  const drawerClose = document.getElementById('drawerClose');

  if (hamburger && drawer) {
    hamburger.addEventListener('click', () => drawer.classList.add('open'));
    if (drawerClose) drawerClose.addEventListener('click', () => drawer.classList.remove('open'));
    drawer.addEventListener('click', (e) => {
      if (e.target === drawer) drawer.classList.remove('open');
    });
  }

  /* ── Modal helpers ── */
  document.querySelectorAll('[data-modal-open]').forEach(trigger => {
    trigger.addEventListener('click', () => {
      const id = trigger.dataset.modalOpen;
      const targetModal = document.getElementById(id);
      if (targetModal) targetModal.classList.add('open');
    });
  });
  document.querySelectorAll('[data-modal-close]').forEach(btn => {
    btn.addEventListener('click', () => {
      const backdrop = btn.closest('.modal-backdrop');
      if (backdrop) backdrop.classList.remove('open');
    });
  });
  document.querySelectorAll('.modal-backdrop').forEach(backdrop => {
    backdrop.addEventListener('click', (e) => {
      if (e.target === backdrop) backdrop.classList.remove('open');
    });
  });

  /* ── Auto-dismiss alerts ── */
  document.querySelectorAll('.alert[data-autohide]').forEach(alert => {
    setTimeout(() => {
      alert.style.transition = 'opacity 0.4s';
      alert.style.opacity = '0';
      setTimeout(() => alert.remove(), 400);
    }, 4000);
  });

  /* ── Quantity control ── */
  document.querySelectorAll('.qty-control').forEach(ctrl => {
    const display = ctrl.querySelector('.qty-display');
    const input   = ctrl.querySelector('input[name]');
    const plusBtn = ctrl.querySelector('.qty-btn--plus');
    if (plusBtn) {
      plusBtn.addEventListener('click', () => {
      let v = parseInt(display?.textContent || input?.value || '1');
      v = Math.min(v + 1, 99);
      if (display) display.textContent = v;
      if (input)   input.value = v;
      });
    }
    const minusBtn = ctrl.querySelector('.qty-btn--minus');
    if (minusBtn) {
      minusBtn.addEventListener('click', () => {
      let v = parseInt(display?.textContent || input?.value || '1');
      v = Math.max(v - 1, 1);
      if (display) display.textContent = v;
      if (input)   input.value = v;
      });
    }
  });

  /* ── Image preview for file inputs ── */
  document.querySelectorAll('input[type="file"][data-preview]').forEach(input => {
    input.addEventListener('change', () => {
      const file = input.files[0];
      if (!file) return;
      const preview = document.getElementById(input.dataset.preview);
      if (preview) preview.src = URL.createObjectURL(file);
    });
  });

  /* ── Confirm-delete buttons ── */
  document.querySelectorAll('[data-confirm]').forEach(btn => {
    btn.addEventListener('click', (e) => {
      if (!confirm(btn.dataset.confirm || 'Apakah Anda yakin?')) {
        e.preventDefault();
      }
    });
  });

  /* ── Format currency ── */
  window.formatRupiah = (num) =>
    'Rp ' + Number(num).toLocaleString('id-ID');

  document.querySelectorAll('[data-rupiah]').forEach(el => {
    el.textContent = window.formatRupiah(el.textContent.trim());
  });

  /* ── Search filter (client-side table rows) ── */
  const tableSearch = document.getElementById('tableSearch');
  if (tableSearch) {
    tableSearch.addEventListener('input', () => {
      const q = tableSearch.value.toLowerCase();
      document.querySelectorAll('tbody tr').forEach(row => {
        row.style.display = row.textContent.toLowerCase().includes(q) ? '' : 'none';
      });
    });
  }

  /* ── Sidebar active link ── */
  const currentPath = window.location.pathname;
  document.querySelectorAll('.sidebar__link').forEach(link => {
    if (link.getAttribute('href') && currentPath.endsWith(link.getAttribute('href').split('/').pop())) {
      link.classList.add('active');
    }
  });

  /* ── Smooth scroll for anchor links ── */
  document.querySelectorAll('a[href^="#"]').forEach(a => {
    a.addEventListener('click', (e) => {
      const target = document.querySelector(a.getAttribute('href'));
      if (target) { e.preventDefault(); target.scrollIntoView({ behavior: 'smooth' }); }
    });
  });

  console.log('✅ A-LINKS initialized');
});
