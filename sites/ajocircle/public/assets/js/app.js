/* AjoCircle — small progressive enhancements, no dependencies. */
(() => {
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
