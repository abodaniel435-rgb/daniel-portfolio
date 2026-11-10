(() => {
  'use strict';

  /* Mobile navigation */
  const toggle = document.querySelector('.menu-btn');
  const links = document.getElementById('navlinks');
  if (toggle && links) {
    toggle.addEventListener('click', () => {
      const open = links.classList.toggle('open');
      toggle.setAttribute('aria-expanded', String(open));
    });
    document.addEventListener('click', (event) => {
      if (!links.classList.contains('open')) return;
      if (links.contains(event.target) || toggle.contains(event.target)) return;
      links.classList.remove('open');
      toggle.setAttribute('aria-expanded', 'false');
    });
  }

  /* Auto-hide flash messages */
  document.querySelectorAll('.flash').forEach((flash) => {
    setTimeout(() => {
      flash.style.transition = 'opacity .4s ease, transform .4s ease';
      flash.style.opacity = '0';
      flash.style.transform = 'translateY(-6px)';
      setTimeout(() => flash.remove(), 420);
    }, 7000);
  });

  /* Confirmation for destructive actions */
  document.querySelectorAll('form[data-confirm]').forEach((form) => {
    form.addEventListener('submit', (event) => {
      if (!window.confirm(form.dataset.confirm)) event.preventDefault();
    });
  });

  /* Copy tracking links / codes */
  document.querySelectorAll('[data-copy]').forEach((button) => {
    button.addEventListener('click', async () => {
      const value = button.dataset.copy || '';
      try {
        await navigator.clipboard.writeText(value);
      } catch {
        const field = document.createElement('textarea');
        field.value = value;
        document.body.appendChild(field);
        field.select();
        document.execCommand('copy');
        field.remove();
      }
      const original = button.textContent;
      button.textContent = 'Copied';
      setTimeout(() => { button.textContent = original; }, 1600);
    });
  });

  /* Filter selects submit their GET form immediately */
  document.querySelectorAll('[data-autosubmit]').forEach((select) => {
    select.addEventListener('change', () => {
      const form = select.closest('form');
      if (form) form.submit();
    });
  });

  /* New shipment: live service promise + fee suggestion */
  const createForm = document.querySelector('[data-shipment-form]');
  if (createForm) {
    const service = createForm.querySelector('select[name="service"]');
    const promise = createForm.querySelector('[data-service-promise]');
    const weight = createForm.querySelector('input[name="weight_kg"]');
    const fee = createForm.querySelector('input[name="fee"]');
    const suggestion = createForm.querySelector('[data-fee-suggestion]');
    const baseRates = { same_day: 2600, express: 3200, standard: 1800 };
    const promises = {
      same_day: 'Delivered before 9pm today across Lagos.',
      express: 'Priority rider, delivered next working day.',
      standard: 'Consolidated routing, 2–4 working days.',
    };

    const update = () => {
      const key = service ? service.value : 'standard';
      if (promise) promise.textContent = promises[key] || '';
      const kg = parseFloat(weight && weight.value ? weight.value : '0') || 0;
      const quote = Math.round(((baseRates[key] || 1800) + kg * 380) / 50) * 50;
      if (suggestion) {
        suggestion.textContent = quote.toLocaleString('en-NG', { style: 'currency', currency: 'NGN', maximumFractionDigits: 0 });
        suggestion.dataset.value = String(quote);
      }
    };
    if (service) service.addEventListener('change', update);
    if (weight) weight.addEventListener('input', update);
    if (suggestion) {
      suggestion.addEventListener('click', () => {
        if (fee && suggestion.dataset.value) {
          fee.value = suggestion.dataset.value;
          fee.focus();
        }
      });
    }
    update();
  }

  /* Courier status forms: prefill a sensible default note */
  document.querySelectorAll('[data-status-form]').forEach((form) => {
    const select = form.querySelector('select[name="status"]');
    const note = form.querySelector('input[name="note"]');
    if (select && note) {
      select.addEventListener('change', () => {
        if (note.value.trim() !== '') return;
        const hints = {
          picked_up: 'Parcel collected and sealed with RoutePilot tape.',
          in_transit: 'Moved to the next sorting point on the route.',
          out_for_delivery: 'Final leg — rider is close to the delivery address.',
          delivered: 'Delivered and confirmed with the recipient.',
          failed: 'Recipient unreachable — parcel returning to hub.',
        };
        if (hints[select.value]) note.value = hints[select.value];
      });
    }
  });
})();
