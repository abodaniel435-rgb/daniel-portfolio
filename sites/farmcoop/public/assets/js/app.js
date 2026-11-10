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
      if (!links.contains(event.target) && !toggle.contains(event.target)) {
        links.classList.remove('open');
        toggle.setAttribute('aria-expanded', 'false');
      }
    });
  }

  /* Scroll reveals */
  const reveals = document.querySelectorAll('.reveal');
  if ('IntersectionObserver' in window && reveals.length) {
    const observer = new IntersectionObserver((entries) => {
      entries.forEach((entry) => {
        if (entry.isIntersecting) {
          entry.target.classList.add('in');
          observer.unobserve(entry.target);
        }
      });
    }, { rootMargin: '0px 0px -8% 0px', threshold: 0.08 });
    reveals.forEach((el) => observer.observe(el));
  } else {
    reveals.forEach((el) => el.classList.add('in'));
  }

  /* Destructive actions need a confirmation (on the form or the submitter) */
  document.querySelectorAll('form').forEach((form) => {
    form.addEventListener('submit', (event) => {
      const message = form.dataset.confirm || (event.submitter && event.submitter.dataset.confirm);
      if (message && !window.confirm(message)) {
        event.preventDefault();
      }
    });
  });

  /* Flash messages fade out */
  document.querySelectorAll('.flash').forEach((flash) => {
    setTimeout(() => {
      flash.style.transition = 'opacity .45s, transform .45s';
      flash.style.opacity = '0';
      flash.style.transform = 'translateY(-6px)';
      setTimeout(() => flash.remove(), 500);
    }, 8000);
  });

  /* Checkout: live order value */
  const checkout = document.querySelector('[data-checkout]');
  if (checkout) {
    const qty = checkout.querySelector('input[name="quantity"]');
    const price = parseFloat(checkout.dataset.price || '0');
    const unit = checkout.dataset.unit || '';
    const available = parseFloat(checkout.dataset.available || '0');
    const out = checkout.querySelector('[data-order-total]');
    const helper = checkout.querySelector('[data-order-helper]');
    const delivery = checkout.querySelector('select[name="delivery_method"]');
    const addressWrap = checkout.querySelector('[data-address-wrap]');
    const address = addressWrap ? addressWrap.querySelector('input, textarea') : null;

    const update = () => {
      const value = parseFloat(qty.value || '0');
      const safe = Number.isFinite(value) ? value : 0;
      if (out) {
        out.textContent = '₦' + (safe * price).toLocaleString('en-NG', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
      }
      if (helper) {
        if (safe > available) {
          helper.textContent = 'Only ' + available.toLocaleString('en-NG') + ' ' + unit + ' available on this listing.';
          helper.style.color = '#a9482c';
        } else {
          helper.textContent = 'Available: ' + available.toLocaleString('en-NG') + ' ' + unit;
          helper.style.color = '';
        }
      }
    };

    if (qty) {
      qty.addEventListener('input', update);
      update();
    }

    if (delivery && addressWrap) {
      const toggleAddress = () => {
        const isDelivery = delivery.value === 'delivery';
        addressWrap.classList.toggle('hidden', !isDelivery);
        if (address) address.required = isDelivery;
      };
      delivery.addEventListener('change', toggleAddress);
      toggleAddress();
    }
  }

  /* Register: preselect role from ?role=farmer|buyer */
  const roleInputs = document.querySelectorAll('input[name="role"]');
  if (roleInputs.length) {
    const wanted = new URLSearchParams(window.location.search).get('role');
    if (wanted === 'farmer' || wanted === 'buyer') {
      roleInputs.forEach((input) => {
        input.checked = input.value === wanted;
      });
    }
    const farmFields = document.querySelector('[data-farm-fields]');
    const syncRole = () => {
      const farmer = document.querySelector('input[name="role"]:checked')?.value === 'farmer';
      if (farmFields) {
        farmFields.classList.toggle('hidden', !farmer);
        farmFields.querySelectorAll('input, select').forEach((field) => {
          field.required = farmer && field.dataset.optional !== 'true';
        });
      }
    };
    roleInputs.forEach((input) => input.addEventListener('change', syncRole));
    syncRole();
  }

  /* Copy tracking references */
  document.querySelectorAll('[data-copy]').forEach((button) => {
    button.addEventListener('click', async () => {
      try {
        await navigator.clipboard.writeText(button.dataset.copy);
        const original = button.textContent;
        button.textContent = 'Copied';
        setTimeout(() => { button.textContent = original; }, 1600);
      } catch (error) {
        /* clipboard unavailable — no-op */
      }
    });
  });
})();
