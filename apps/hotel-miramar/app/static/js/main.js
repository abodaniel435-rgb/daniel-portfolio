/* ==========================================================================
   Hotel Miramar SG — shared interaction layer
   One file for the public site, guest portal and admin console.
   Progressive enhancement only: every form and link works without JS.
   ========================================================================== */
(function () {
  'use strict';

  document.documentElement.classList.add('js');

  var reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

  function toISODate(date) {
    var y = date.getFullYear();
    var m = String(date.getMonth() + 1).padStart(2, '0');
    var d = String(date.getDate()).padStart(2, '0');
    return y + '-' + m + '-' + d;
  }

  function parseISODate(value) {
    if (!value) return null;
    var parts = value.split('-');
    if (parts.length !== 3) return null;
    var date = new Date(Number(parts[0]), Number(parts[1]) - 1, Number(parts[2]));
    return isNaN(date.getTime()) ? null : date;
  }

  function addDays(date, days) {
    var copy = new Date(date.getTime());
    copy.setDate(copy.getDate() + days);
    return copy;
  }

  /* ---------------------------------------------------------------- header */
  function initHeader() {
    var header = document.querySelector('[data-header]');
    if (!header) return;
    var onScroll = function () {
      header.classList.toggle('is-scrolled', window.scrollY > 8);
    };
    window.addEventListener('scroll', onScroll, { passive: true });
    onScroll();
  }

  /* ------------------------------------------------------------ nav drawer */
  function initDrawer(options) {
    var toggle = document.querySelector(options.toggle);
    var panel = document.querySelector(options.panel);
    var scrim = document.querySelector(options.scrim);
    if (!toggle || !panel) return;

    var lastFocused = null;
    var openClass = options.openClass || 'is-open';

    function setOpen(open) {
      panel.classList.toggle(openClass, open);
      if (scrim) scrim.classList.toggle('is-visible', open);
      toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
      document.body.classList.toggle('is-locked', open);
      if (open) {
        lastFocused = document.activeElement;
        var first = panel.querySelector('a, button, input, select, textarea');
        if (first) window.setTimeout(function () { first.focus(); }, 60);
      } else if (lastFocused && typeof lastFocused.focus === 'function') {
        lastFocused.focus();
      }
    }

    toggle.addEventListener('click', function () {
      setOpen(toggle.getAttribute('aria-expanded') !== 'true');
    });

    if (scrim) scrim.addEventListener('click', function () { setOpen(false); });

    panel.addEventListener('click', function (event) {
      var link = event.target.closest('a');
      if (link && !link.hash) setOpen(false);
    });

    document.addEventListener('keydown', function (event) {
      if (event.key === 'Escape' && toggle.getAttribute('aria-expanded') === 'true') {
        setOpen(false);
      }
    });

    window.addEventListener('resize', function () {
      if (window.innerWidth > (options.breakpoint || 1024) &&
          toggle.getAttribute('aria-expanded') === 'true') {
        setOpen(false);
      }
    });
  }

  /* ---------------------------------------------------------- flash stack */
  function initAlerts() {
    document.querySelectorAll('.alert').forEach(function (alert) {
      var close = alert.querySelector('.alert__close');
      if (close) {
        close.addEventListener('click', function () {
          alert.style.transition = 'opacity 220ms ease';
          alert.style.opacity = '0';
          window.setTimeout(function () { alert.remove(); }, 240);
        });
      }
      var tone = alert.getAttribute('data-tone');
      if (tone === 'success' || tone === 'info') {
        window.setTimeout(function () {
          if (!document.body.contains(alert)) return;
          alert.style.transition = 'opacity 400ms ease';
          alert.style.opacity = '0';
          window.setTimeout(function () { alert.remove(); }, 420);
        }, 7000);
      }
    });
  }

  /* ----------------------------------------------------- date constraints */
  function syncDatePair(scope) {
    var checkIn = scope.querySelector('input[name="check_in"]');
    var checkOut = scope.querySelector('input[name="check_out"]');
    if (!checkIn || !checkOut) return;
    var today = new Date();
    today.setHours(0, 0, 0, 0);
    var todayIso = toISODate(today);

    if (!checkIn.min) checkIn.min = todayIso;
    if (!checkIn.value && !checkIn.dataset.keepEmpty) checkIn.value = todayIso;
    if (!checkOut.value && !checkOut.dataset.keepEmpty) checkOut.value = toISODate(addDays(today, 1));

    function refresh() {
      var start = parseISODate(checkIn.value) || today;
      var minimum = toISODate(addDays(start, 1));
      checkOut.min = minimum;
      var end = parseISODate(checkOut.value);
      if (!end || end <= start) checkOut.value = minimum;
      updatePrice(scope);
    }

    checkIn.addEventListener('change', refresh);
    checkOut.addEventListener('change', updatePrice.bind(null, scope));
    refresh();
  }

  function formatMoney(value) {
    return 'S$' + value.toLocaleString('en-SG', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
  }

  function updatePrice(scope) {
    var root = scope.closest('[data-price-root]') || document.querySelector('[data-price-root]');
    if (!root) return;
    var checkIn = scope.querySelector('input[name="check_in"]');
    var checkOut = scope.querySelector('input[name="check_out"]');
    if (!checkIn || !checkOut) return;
    var start = parseISODate(checkIn.value);
    var end = parseISODate(checkOut.value);
    var nights = start && end ? Math.round((end - start) / 86400000) : 0;
    var rate = parseFloat(root.getAttribute('data-rate')) || 0;

    var nightsEl = root.querySelector('[data-nights]');
    var rateEl = root.querySelector('[data-rate-line]');
    var totalEl = root.querySelector('[data-total]');

    if (nightsEl) nightsEl.textContent = nights > 0 ? nights + (nights === 1 ? ' night' : ' nights') : '\u2014';
    if (rateEl) rateEl.textContent = formatMoney(rate);
    if (totalEl) totalEl.textContent = formatMoney(rate * Math.max(nights, 0));
  }

  function initDateScopes() {
    document.querySelectorAll('[data-date-scope]').forEach(syncDatePair);
  }

  /* -------------------------------------------------------------- gallery */
  function initGallery() {
    var main = document.querySelector('[data-gallery-main]');
    var thumbs = document.querySelectorAll('[data-gallery-thumb]');
    if (!main || !thumbs.length) return;
    thumbs.forEach(function (thumb) {
      thumb.addEventListener('click', function () {
        var source = thumb.getAttribute('data-src');
        var alt = thumb.getAttribute('data-alt') || main.getAttribute('alt');
        if (!source) return;
        main.style.opacity = '0.4';
        var img = new Image();
        img.onload = function () {
          main.src = source;
          main.alt = alt;
          main.style.opacity = '1';
        };
        img.onerror = function () { main.style.opacity = '1'; };
        img.src = source;
        thumbs.forEach(function (other) { other.setAttribute('aria-pressed', 'false'); });
        thumb.setAttribute('aria-pressed', 'true');
      });
    });
  }

  /* --------------------------------------------------------------- reveal */
  function initReveal() {
    var items = document.querySelectorAll('.reveal');
    if (!items.length) return;
    if (reduceMotion || !('IntersectionObserver' in window)) {
      items.forEach(function (item) { item.classList.add('is-visible'); });
      return;
    }
    var observer = new IntersectionObserver(function (entries) {
      entries.forEach(function (entry) {
        if (entry.isIntersecting) {
          entry.target.classList.add('is-visible');
          observer.unobserve(entry.target);
        }
      });
    }, { threshold: 0.08, rootMargin: '0px 0px -40px 0px' });
    items.forEach(function (item) { observer.observe(item); });
  }

  /* ----------------------------------------------------------------- init */
  function init() {
    initHeader();
    initDrawer({
      toggle: '[data-nav-toggle]',
      panel: '[data-nav-panel]',
      scrim: '[data-nav-scrim]',
      breakpoint: 1024
    });
    initDrawer({
      toggle: '[data-admin-toggle]',
      panel: '[data-admin-panel]',
      scrim: '[data-admin-scrim]',
      breakpoint: 1024
    });
    initAlerts();
    initDateScopes();
    initGallery();
    initReveal();
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();
