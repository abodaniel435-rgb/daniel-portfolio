/* AjoCircle — small progressive enhancements, no dependencies. */
(() => {
  const reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

  // The plate is a fixed layer, so the scroll offset has to be applied by hand.
  const plate = document.querySelector('.bg-plate');
  if (plate && !reduce) {
    let queued = false;
    const paint = () => {
      plate.style.setProperty('--sy', `${(window.scrollY * 0.055).toFixed(1)}px`);
      queued = false;
    };
    window.addEventListener('scroll', () => {
      if (!queued) { queued = true; requestAnimationFrame(paint); }
    }, { passive: true });
    paint();
  }

  // Blocks reveal as they reach the viewport rather than all at once on load.
  // The class on <html> is what arms the hidden start state, so a browser that
  // never runs this script still shows everything (see the .js-io rules).
  const items = document.querySelectorAll('main .reveal, main .panel, main .card, main .stat, main .table-wrap, main .auth-form');
  if (items.length && !reduce && 'IntersectionObserver' in window) {
    document.documentElement.classList.add('js-io');
    const io = new IntersectionObserver((entries) => {
      entries.forEach((entry) => {
        if (!entry.isIntersecting) return;
        entry.target.classList.add('in');
        io.unobserve(entry.target);
      });
    }, { rootMargin: '0px 0px -10% 0px', threshold: 0.06 });
    items.forEach((el) => io.observe(el));
  }

  const toggle = document.querySelector('.menu-btn');
  const links = document.getElementById('navlinks');
  if (toggle && links) {
    toggle.addEventListener('click', () => {
      const open = links.classList.toggle('open');
      toggle.setAttribute('aria-expanded', String(open));
    });
  }

  document.querySelectorAll('form[data-confirm]').forEach((form) => {
    form.addEventListener('submit', (event) => {
      if (!window.confirm(form.dataset.confirm)) event.preventDefault();
    });
  });

  document.querySelectorAll('[data-print]').forEach((button) => {
    button.addEventListener('click', () => window.print());
  });

  // Pre-select the round a member clicked "Record payment" on.
  const picker = document.querySelector('select[data-preselect]');
  if (picker && picker.dataset.preselect) {
    picker.value = picker.dataset.preselect;
  }

  document.querySelectorAll('.flash').forEach((flash) => {
    setTimeout(() => {
      flash.style.transition = 'opacity .4s, transform .4s';
      flash.style.opacity = '0';
      flash.style.transform = 'translateY(-6px)';
      setTimeout(() => flash.remove(), 450);
    }, 8000);
  });

  // Copy statement / reference values
  document.querySelectorAll('[data-copy]').forEach((node) => {
    node.addEventListener('click', async () => {
      try {
        await navigator.clipboard.writeText(node.dataset.copy);
        const original = node.textContent;
        node.textContent = 'Copied';
        setTimeout(() => { node.textContent = original; }, 1400);
      } catch (error) {
        /* clipboard unavailable — no-op */
      }
    });
  });
})();
