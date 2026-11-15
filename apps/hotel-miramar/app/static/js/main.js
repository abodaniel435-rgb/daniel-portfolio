/* ==========================================================================
   Hotel Miramar SG — shared interaction layer
   One file for the public site, guest portal and admin console.
   Progressive enhancement only: every form and link works without JS.
   ========================================================================== */
(function () {
  'use strict';

  document.documentElement.classList.add('js');

  var mqReduce = window.matchMedia('(prefers-reduced-motion: reduce)');
  var reduceMotion = mqReduce.matches;
  // Motion vocabulary is opt-in: .mo only ever exists when motion is allowed.
  if (!reduceMotion) {
    document.documentElement.classList.add('mo');
  }
  if (typeof mqReduce.addEventListener === 'function') {
    mqReduce.addEventListener('change', function (event) {
      document.documentElement.classList.toggle('mo', !event.matches);
    });
  }

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
  function showAll(items) {
    Array.prototype.forEach.call(items, function (item) {
      item.classList.add('is-visible', 'in');
    });
  }

  function initReveal() {
    // Portal, auth and console pages share these blocks — mark them here so
    // every template inherits the same entry without editing 20 files.
    [
      ['.page-intro .eyebrow', 'wipe'],
      ['.page-intro h1', 'wipe'],
      ['.auth-card__head .eyebrow', 'wipe'],
      ['.auth-card__head h1', 'wipe'],
      ['.auth-media__caption h2', 'up'],
      ['.admin-page-head h1', 'up'],
      ['.empty-state h2', 'up']
    ].forEach(function (spec) {
      document.querySelectorAll(spec[0] + ':not([data-reveal])').forEach(function (el) {
        el.setAttribute('data-reveal', spec[1]);
        if (!el.hasAttribute('data-fx') && el.tagName === 'H1') el.setAttribute('data-fx', 'decode');
      });
    });

    // Console and portal templates are not hand-marked: give their top-level
    // blocks the same choreography instead of editing 20 templates.
    var autoGroups = [
      ['.stat-grid', 'rise', 90],
      ['.portal-actions', 'rise', 70],
      ['.booking-list > *', 'wipe', 90],
      ['.queue-list > *', 'wipe', 60],
      ['.admin-content > *', 'rise', 0],
      ['.admin-page-head', 'up', 0],
      ['.filter-tabs', 'wipe', 0],
      ['.chip-row', 'wipe', 40],
      ['.kv', 'wipe', 40],
      ['.tick-list', 'wipe', 40],
      ['.auth-card', 'rise', 0],
      ['.confirmation', 'wipe', 0],
      ['.stepper', 'wipe', 60]
    ];
    autoGroups.forEach(function (spec) {
      var step = spec[2];
      document.querySelectorAll(spec[0] + ':not([data-reveal]):not(.reveal)').forEach(function (el) {
        el.setAttribute('data-reveal', spec[1]);
      });
      // Index-based delay inside each group.
      var seen = [];
      document.querySelectorAll(spec[0]).forEach(function (el) {
        var parent = el.parentElement;
        if (!parent) return;
        if (seen.indexOf(parent) === -1) seen.push(parent);
      });
      seen.forEach(function (parent) {
        var kids = parent.querySelectorAll(':scope > [data-reveal]');
        Array.prototype.forEach.call(kids, function (kid, index) {
          if (!kid.style.getPropertyValue('--d')) {
            kid.style.setProperty('--d', (index * (step || 110)) + 'ms');
          }
        });
      });
    });

    var items = document.querySelectorAll('.reveal, [data-reveal]');
    if (!items.length) return;
    if (reduceMotion || !('IntersectionObserver' in window)) {
      showAll(items);
      return;
    }

    // Choreography: siblings inside a [data-stagger] group get an index delay.
    document.querySelectorAll('[data-stagger]').forEach(function (group) {
      var step = parseFloat(group.getAttribute('data-stagger')) || 90;
      Array.prototype.forEach.call(group.children, function (child, index) {
        if (child.hasAttribute('data-reveal') || child.classList.contains('reveal')) {
          child.style.setProperty('--d', (index * step) + 'ms');
        }
      });
    });

    var pending = Array.prototype.slice.call(items);

    function reveal(el) {
      // Revealed once, then forgotten: nothing animates a second time.
      el.classList.add('is-visible', 'in');
    }

    var observer = null;
    if ('IntersectionObserver' in window) {
      observer = new IntersectionObserver(function (entries) {
        entries.forEach(function (entry) {
          if (!entry.isIntersecting) return;
          reveal(entry.target);
          var at = pending.indexOf(entry.target);
          if (at > -1) pending.splice(at, 1);
          observer.unobserve(entry.target);
        });
      }, { threshold: 0.06, rootMargin: '0px 0px -40px 0px' });
      items.forEach(function (item) { observer.observe(item); });
    }

    // Safety net: some environments throttle IntersectionObserver delivery
    // (headless renders, very old blink builds). A rAF-throttled rect check on
    // scroll/resize guarantees nothing can stay permanently invisible.
    var queued = false;
    function checkPending() {
      queued = false;
      if (!pending.length) return;
      var vh = window.innerHeight || document.documentElement.clientHeight;
      for (var i = pending.length - 1; i >= 0; i--) {
        var rect = pending[i].getBoundingClientRect();
        if (rect.top < vh * 0.94 && rect.bottom > 0) {
          reveal(pending[i]);
          if (observer) observer.unobserve(pending[i]);
          pending.splice(i, 1);
        }
      }
    }
    function schedulePending() {
      if (queued) return;
      queued = true;
      if ('requestAnimationFrame' in window) requestAnimationFrame(checkPending);
      else checkPending();
    }
    window.addEventListener('scroll', schedulePending, { passive: true });
    window.addEventListener('resize', schedulePending, { passive: true });
    schedulePending();
  }

  /* ---------------------------------------------------- decode headline */
  var GLYPHS = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ&/\u00b7\u2014\u2022#*';

  // Works on the text nodes inside the heading so <br> and <em> survive.
  function initDecode() {
    var targets = document.querySelectorAll('[data-fx="decode"]');
    if (!targets.length || reduceMotion) return;

    targets.forEach(function (el) {
      var walker = document.createTreeWalker(el, NodeFilter.SHOW_TEXT, null);
      var slots = [];
      var node = walker.nextNode();
      while (node) {
        if (node.nodeValue && node.nodeValue.length) slots.push({ node: node, text: node.nodeValue });
        node = walker.nextNode();
      }

      var total = slots.reduce(function (sum, slot) { return sum + slot.text.length; }, 0);
      if (!total) return;

      // Settle time per character, left to right, over roughly 700ms.
      var cursor = 0;
      slots.forEach(function (slot) {
        slot.out = slot.text.split('');
        slot.settle = slot.text.split('').map(function (ch) {
          cursor += 1;
          if (ch === ' ' || ch === '\u00a0') return 0;
          return 60 + (cursor / total) * 540 + Math.random() * 80;
        });
      });

      var start = null;
      function frame(now) {
        if (start === null) start = now;
        var elapsed = now - start;
        if (elapsed > 700) {
          slots.forEach(function (slot) { slot.node.nodeValue = slot.text; });
          return;
        }
        slots.forEach(function (slot) {
          for (var i = 0; i < slot.out.length; i++) {
            var ch = slot.text.charAt(i);
            slot.out[i] = (ch === ' ' || elapsed >= slot.settle[i])
              ? ch
              : GLYPHS.charAt(Math.floor(Math.random() * GLYPHS.length));
          }
          slot.node.nodeValue = slot.out.join('');
        });
        window.requestAnimationFrame(frame);
      }
      window.requestAnimationFrame(frame);
    });
  }

  /* --------------------------------------------------- pointer sheen */
  function initPointerSheen() {
    if (reduceMotion || !window.PointerEvent) return;
    var frame = null;
    var target = null;
    var point = { x: 0, y: 0 };

    function apply() {
      frame = null;
      if (!target) return;
      var rect = target.getBoundingClientRect();
      if (!rect.width || !rect.height) return;
      target.style.setProperty('--mx', (((point.x - rect.left) / rect.width) * 100).toFixed(2) + '%');
      target.style.setProperty('--my', (((point.y - rect.top) / rect.height) * 100).toFixed(2) + '%');
    }

    // One delegated listener for the whole page, rAF-throttled.
    document.addEventListener('pointermove', function (event) {
      if (event.pointerType === 'touch') return;
      var card = event.target && event.target.closest
        ? event.target.closest('.fx-sheen, .room-card, .panel, .stat-card, .summary-card, .booking-card')
        : null;
      if (!card) return;
      target = card;
      point = { x: event.clientX, y: event.clientY };
      if (frame === null) frame = window.requestAnimationFrame(apply);
    }, { passive: true });
  }

  /* ---------------------------------------------- scroll progress line */
  function initScrollProgress() {
    var bar = document.querySelector('[data-scroll-progress]');
    if (!bar) return;
    if (typeof CSS !== 'undefined' && CSS.supports && CSS.supports('animation-timeline', 'scroll(root)')) {
      bar.setAttribute('data-native', 'true');
      return;
    }
    var ticking = false;
    function update() {
      ticking = false;
      var doc = document.documentElement;
      var range = doc.scrollHeight - doc.clientHeight;
      var ratio = range > 0 ? Math.min(Math.max(doc.scrollTop / range, 0), 1) : 0;
      bar.style.setProperty('--scroll-p', ratio.toFixed(4));
    }
    window.addEventListener('scroll', function () {
      if (!ticking) { ticking = true; window.requestAnimationFrame(update); }
    }, { passive: true });
    window.addEventListener('resize', update, { passive: true });
    update();
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
    initDecode();
    initPointerSheen();
    initScrollProgress();
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();
