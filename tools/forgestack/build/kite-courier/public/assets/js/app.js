/* ForgeStack generated site enhancements. Progressive: every form works without JS. */
(function () {
  'use strict';

  var reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

  /* Mobile navigation */
  var menuBtn = document.querySelector('.menu-btn');
  var navLinks = document.getElementById('navlinks');
  if (menuBtn && navLinks) {
    menuBtn.addEventListener('click', function () {
      var open = menuBtn.getAttribute('aria-expanded') === 'true';
      menuBtn.setAttribute('aria-expanded', String(!open));
      navLinks.classList.toggle('is-open', !open);
      document.body.classList.toggle('nav-open', !open);
    });
    navLinks.addEventListener('click', function (event) {
      if (event.target.tagName === 'A') {
        menuBtn.setAttribute('aria-expanded', 'false');
        navLinks.classList.remove('is-open');
        document.body.classList.remove('nav-open');
      }
    });
    document.addEventListener('keydown', function (event) {
      if (event.key === 'Escape') {
        menuBtn.setAttribute('aria-expanded', 'false');
        navLinks.classList.remove('is-open');
        document.body.classList.remove('nav-open');
      }
    });
  }

  /* Booking: reflect the selected service price next to the submit button. */
  var priceSelect = document.querySelector('[data-price-selector]');
  var priceTotal = document.querySelector('[data-price-total]');
  function updatePrice() {
    if (!priceSelect || !priceTotal) return;
    var option = priceSelect.options[priceSelect.selectedIndex];
    var price = option ? parseFloat(option.getAttribute('data-price') || '0') : 0;
    if (!price) {
      priceTotal.textContent = priceTotal.getAttribute('data-empty') || 'No payment is taken on this website.';
      return;
    }
    var currency = priceTotal.getAttribute('data-currency') || '₦';
    priceTotal.textContent = currency + price.toLocaleString(undefined, {
      minimumFractionDigits: 0,
      maximumFractionDigits: 0
    });
  }
  if (priceSelect && priceTotal) {
    priceTotal.setAttribute('data-empty', priceTotal.textContent.trim());
    priceSelect.addEventListener('change', updatePrice);
    updatePrice();
  }

  /* Shop: product picker updates the select, total and hint. */
  var productSelect = document.getElementById('product_id');
  var qtyInput = document.querySelector('[data-order-qty]');
  var orderTotal = document.querySelector('[data-order-total]');
  var pickButtons = document.querySelectorAll('[data-pick-product]');
  function updateOrderTotal() {
    if (!productSelect || !orderTotal) return;
    var option = productSelect.options[productSelect.selectedIndex];
    var price = option ? parseFloat(option.getAttribute('data-price') || '0') : 0;
    var qty = qtyInput ? parseInt(qtyInput.value, 10) || 1 : 1;
    if (!price) {
      orderTotal.textContent = '—';
      return;
    }
    var currency = orderTotal.getAttribute('data-currency') || '₦';
    orderTotal.textContent = currency + (price * qty).toLocaleString(undefined, {
      minimumFractionDigits: 0,
      maximumFractionDigits: 0
    });
  }
  if (productSelect) {
    productSelect.addEventListener('change', updateOrderTotal);
    if (qtyInput) qtyInput.addEventListener('input', updateOrderTotal);
    updateOrderTotal();
  }
  Array.prototype.forEach.call(pickButtons, function (button) {
    button.addEventListener('click', function () {
      if (!productSelect) return;
      productSelect.value = button.getAttribute('data-pick-product');
      productSelect.dispatchEvent(new Event('change', { bubbles: true }));
      var form = document.getElementById('order-form');
      if (form) {
        form.scrollIntoView({ behavior: reduceMotion ? 'auto' : 'smooth', block: 'center' });
        var first = form.querySelector('select, input');
        if (first) first.focus({ preventScroll: true });
      }
    });
  });

  /* Reveal sections on scroll without blocking paint. */
  var revealables = document.querySelectorAll('[data-reveal]');
  if (revealables.length) {
    if ('IntersectionObserver' in window && !reduceMotion) {
      var observer = new IntersectionObserver(function (entries) {
        entries.forEach(function (entry) {
          if (entry.isIntersecting) {
            entry.target.classList.add('is-revealed');
            observer.unobserve(entry.target);
          }
        });
      }, { rootMargin: '0px 0px -8% 0px', threshold: 0.08 });
      Array.prototype.forEach.call(revealables, function (el) {
        el.classList.add('reveal');
        observer.observe(el);
      });
    } else {
      Array.prototype.forEach.call(revealables, function (el) {
        el.classList.add('is-revealed');
      });
    }
  }
})();
