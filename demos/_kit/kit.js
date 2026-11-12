/* AfriKit behaviour layer — no dependencies, no build step.
   Sites call kit.* helpers and add data attributes; everything degrades
   to plain HTML when JavaScript is unavailable. */
(function (global) {
  'use strict';

  var reduce = global.matchMedia && global.matchMedia('(prefers-reduced-motion: reduce)').matches;

  // Set before anything paints, so sections are never shown flat and then hidden.
  if (!reduce) document.documentElement.classList.add('kit-anim');

  /* ---------- formatting ---------- */

  function money(value, currency, decimals) {
    var n = Number(value) || 0;
    var dp = decimals === undefined ? 0 : decimals;
    try {
      return new Intl.NumberFormat('en-NG', {
        style: 'currency', currency: currency || 'NGN',
        minimumFractionDigits: dp, maximumFractionDigits: dp
      }).format(n).replace('NGN', '₦').replace(/₦\s?/, '₦');
    } catch (e) {
      return '₦' + n.toFixed(dp).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
    }
  }

  function amount(value, decimals) {
    var dp = decimals === undefined ? 0 : decimals;
    return (Number(value) || 0).toLocaleString('en-NG', { minimumFractionDigits: dp, maximumFractionDigits: dp });
  }

  function pct(value, decimals) {
    return (Number(value) || 0).toFixed(decimals === undefined ? 1 : decimals) + '%';
  }

  function num(value) { return Number(String(value).replace(/[^0-9.\-]/g, '')) || 0; }

  function clamp(value, min, max) { return Math.min(max, Math.max(min, value)); }

  function slug(text) {
    return String(text).toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-|-$/g, '');
  }

  function dateLabel(value) {
    var d = value instanceof Date ? value : new Date(value);
    if (isNaN(d)) return String(value || '');
    return d.toLocaleDateString('en-NG', { day: 'numeric', month: 'short', year: 'numeric' });
  }

  function uid(prefix) {
    return (prefix || 'id') + '-' + Date.now().toString(36) + '-' + Math.random().toString(36).slice(2, 7);
  }

  /* ---------- storage ---------- */

  function store(key, fallback) {
    var full = 'afrik:' + key;
    // Callers push into what read() returns, so the fallback must never be handed
    // out by reference — a cleared store would still report its old rows.
    function fresh() {
      if (fallback === null || fallback === undefined) return fallback;
      try { return JSON.parse(JSON.stringify(fallback)); } catch (e) { return fallback; }
    }
    return {
      read: function () {
        try {
          var raw = global.localStorage.getItem(full);
          return raw ? JSON.parse(raw) : fresh();
        } catch (e) { return fresh(); }
      },
      write: function (value) {
        try { global.localStorage.setItem(full, JSON.stringify(value)); return true; }
        catch (e) { return false; }
      },
      clear: function () { try { global.localStorage.removeItem(full); } catch (e) {} }
    };
  }

  /* ---------- dom ---------- */

  function $(sel, root) { return (root || document).querySelector(sel); }
  function $$(sel, root) { return Array.prototype.slice.call((root || document).querySelectorAll(sel)); }

  function setText(sel, value, root) {
    var node = typeof sel === 'string' ? $(sel, root) : sel;
    if (node) node.textContent = value;
    return node;
  }

  function on(root, event, selector, handler) {
    root.addEventListener(event, function (e) {
      var target = e.target.closest(selector);
      if (target && root.contains(target)) handler(e, target);
    });
  }

  /* ---------- motion ---------- */

  function reveal(root) {
    var items = $$('.reveal', root);
    if (reduce || !('IntersectionObserver' in global)) {
      items.forEach(function (el) { el.classList.add('is-in'); });
      return;
    }
    var io = new IntersectionObserver(function (entries) {
      entries.forEach(function (entry) {
        if (!entry.isIntersecting) return;
        entry.target.classList.add('is-in');
        io.unobserve(entry.target);
      });
    }, { rootMargin: '0px 0px -8% 0px', threshold: 0 });
    items.forEach(function (el) {
      /* Anything already on screen reveals straight away. Waiting for the
         observer's first callback can leave the top of the page blank when that
         callback lands after the first paint on a slow device. Reading layout
         first keeps the entrance transition rather than jumping to the end. */
      var box = el.getBoundingClientRect();
      if (box.top < global.innerHeight) {
        void el.offsetWidth;
        el.classList.add('is-in');
      } else {
        io.observe(el);
      }
    });

    /* A scroll sweep backs the observer up. Some embedded and narrow-viewport
       renderers skip intersection notifications entirely, and content that never
       announces itself must still appear. */
    var queued = false;
    function sweep() {
      queued = false;
      items.forEach(function (el) {
        if (el.classList.contains('is-in')) return;
        var b = el.getBoundingClientRect();
        /* Also catch anything already scrolled past: a skipped observer callback
           must not leave a block above the viewport permanently transparent. */
        if (b.top < global.innerHeight - 24) el.classList.add('is-in');
      });
    }
    function queue() {
      if (queued) return;
      queued = true;
      global.setTimeout(sweep, 60);
    }
    global.addEventListener('scroll', queue, { passive: true });
    global.addEventListener('resize', queue);
    global.addEventListener('orientationchange', queue);
  }

  function counters(root) {
    $$('[data-count]', root).forEach(function (el) {
      var target = num(el.dataset.count);
      var dp = Number(el.dataset.decimals || 0);
      var prefix = el.dataset.prefix || '';
      var suffix = el.dataset.suffix || '';
      if (reduce) { el.textContent = prefix + amount(target, dp) + suffix; return; }
      var start = null;
      var duration = 1200;
      function frame(ts) {
        if (start === null) start = ts;
        var p = clamp((ts - start) / duration, 0, 1);
        var eased = 1 - Math.pow(1 - p, 3);
        el.textContent = prefix + amount(target * eased, dp) + suffix;
        if (p < 1) global.requestAnimationFrame(frame);
      }
      var io = new IntersectionObserver(function (entries) {
        entries.forEach(function (entry) {
          if (!entry.isIntersecting) return;
          global.requestAnimationFrame(frame);
          io.unobserve(entry.target);
        });
      }, { threshold: 0.4 });
      io.observe(el);
    });
  }

  /* ---------- charts ---------- */

  function sparkline(el, values, options) {
    var opts = options || {};
    var w = 300, h = 74, pad = 6;
    var nums = (values || []).map(Number).filter(function (v) { return !isNaN(v); });
    if (nums.length < 2) { el.innerHTML = ''; return; }
    var min = Math.min.apply(null, nums), max = Math.max.apply(null, nums);
    var span = (max - min) || 1;
    var step = (w - pad * 2) / (nums.length - 1);
    var pts = nums.map(function (v, i) {
      return [pad + i * step, h - pad - ((v - min) / span) * (h - pad * 2)];
    });
    var line = pts.map(function (p, i) { return (i ? 'L' : 'M') + p[0].toFixed(1) + ' ' + p[1].toFixed(1); }).join(' ');
    var area = line + ' L' + pts[pts.length - 1][0].toFixed(1) + ' ' + h + ' L' + pts[0][0].toFixed(1) + ' ' + h + ' Z';
    var last = pts[pts.length - 1];
    el.classList.add('spark');
    el.setAttribute('viewBox', '0 0 ' + w + ' ' + h);
    el.setAttribute('preserveAspectRatio', 'none');
    el.innerHTML = '<path class="area" d="' + area + '"/><path class="line" d="' + line + '"/>' +
      '<circle cx="' + last[0].toFixed(1) + '" cy="' + last[1].toFixed(1) + '" r="4" fill="' + (opts.dot || 'var(--brand)') + '"/>';
  }

  function bars(el, rows) {
    var max = Math.max.apply(null, rows.map(function (r) { return Number(r.value) || 0; })) || 1;
    el.classList.add('bars');
    el.innerHTML = rows.map(function (r) {
      return '<div class="bar-row"><span>' + r.label + '</span>' +
        '<span class="bar-track"><i class="bar-fill" data-width="' + ((Number(r.value) || 0) / max * 100).toFixed(1) + '"></i></span>' +
        '<b class="mono">' + (r.display || amount(r.value)) + '</b></div>';
    }).join('');
    global.requestAnimationFrame(function () {
      $$('.bar-fill', el).forEach(function (fill) { fill.style.width = fill.dataset.width + '%'; });
    });
  }

  function donut(el, percent, options) {
    var opts = options || {};
    var p = clamp(Number(percent) || 0, 0, 100);
    var r = 52, c = 2 * Math.PI * r;
    el.classList.add('donut');
    el.innerHTML = '<svg viewBox="0 0 128 128" width="128" height="128" role="img" aria-label="' + (opts.label || (p + '%')) + '">' +
      '<circle class="track" cx="64" cy="64" r="' + r + '"/>' +
      '<circle class="value" cx="64" cy="64" r="' + r + '" stroke-dasharray="' + c.toFixed(1) + '" stroke-dashoffset="' + c.toFixed(1) + '"/>' +
      '</svg>';
    var value = $('.value', el);
    global.requestAnimationFrame(function () {
      value.style.strokeDashoffset = (c - (p / 100) * c).toFixed(1);
      if (opts.color) value.style.stroke = opts.color;
    });
  }

  /* ---------- interaction ---------- */

  function nav() {
    var button = $('.menu-toggle'), menu = $('#site-nav');
    if (!button || !menu) return;
    button.addEventListener('click', function () {
      var open = button.getAttribute('aria-expanded') === 'true';
      button.setAttribute('aria-expanded', String(!open));
      menu.classList.toggle('is-open', !open);
    });
    $$('a', menu).forEach(function (link) {
      link.addEventListener('click', function () {
        button.setAttribute('aria-expanded', 'false');
        menu.classList.remove('is-open');
      });
    });
  }

  function activeLinks() {
    var links = $$('.nav a[href^="#"]:not(.btn)');
    if (!links.length || !('IntersectionObserver' in global)) return;
    var map = {};
    links.forEach(function (l) { map[l.getAttribute('href').slice(1)] = l; });
    var io = new IntersectionObserver(function (entries) {
      entries.forEach(function (entry) {
        var link = map[entry.target.id];
        if (!link) return;
        if (entry.isIntersecting) {
          links.forEach(function (l) { l.classList.remove('is-active'); });
          link.classList.add('is-active');
        }
      });
    }, { rootMargin: '-45% 0px -50% 0px' });
    Object.keys(map).forEach(function (id) {
      var section = document.getElementById(id);
      if (section) io.observe(section);
    });
  }

  function tabs(root) {
    $$('[data-tabs]', root).forEach(function (group) {
      var buttons = $$('[role="tab"]', group);
      var panels = $$('[role="tabpanel"]', group);
      buttons.forEach(function (button) {
        button.addEventListener('click', function () {
          buttons.forEach(function (b) { b.setAttribute('aria-selected', String(b === button)); });
          panels.forEach(function (p) { p.hidden = p.id !== button.getAttribute('aria-controls'); });
        });
      });
    });
  }

  function modal() {
    var backdrop = $('[data-modal]');
    if (!backdrop) return;
    function close() { backdrop.classList.remove('is-open'); }
    $$('[data-open-modal]', document).forEach(function (trigger) {
      trigger.addEventListener('click', function () { backdrop.classList.add('is-open'); });
    });
    $$('[data-close-modal]', backdrop).forEach(function (trigger) {
      trigger.addEventListener('click', close);
    });
    backdrop.addEventListener('click', function (e) { if (e.target === backdrop) close(); });
    document.addEventListener('keydown', function (e) { if (e.key === 'Escape') close(); });
  }

  var toastTimer;
  function toast(message) {
    var el = $('.toast');
    if (!el) {
      el = document.createElement('div');
      el.className = 'toast';
      el.setAttribute('role', 'status');
      document.body.appendChild(el);
    }
    el.textContent = message;
    el.classList.add('is-on');
    clearTimeout(toastTimer);
    toastTimer = global.setTimeout(function () { el.classList.remove('is-on'); }, 3200);
  }

  /* ---------- forms ---------- */

  function validate(form, rules) {
    var errors = {};
    Object.keys(rules).forEach(function (name) {
      var rule = rules[name];
      var field = form.elements[name];
      if (!field) return;
      var value = field.type === 'checkbox' ? field.checked : String(field.value).trim();
      var message = null;
      if (rule.required && (value === '' || value === false)) message = rule.message || 'Required.';
      else if (rule.min !== undefined && value !== '' && num(value) < rule.min) message = 'Enter at least ' + rule.min + '.';
      else if (rule.max !== undefined && value !== '' && num(value) > rule.max) message = 'Enter ' + rule.max + ' or less.';
      else if (rule.pattern && value !== '' && !rule.pattern.test(value)) message = rule.message || 'Check this value.';
      if (message) errors[name] = message;
    });
    paintErrors(form, errors);
    return errors;
  }

  function paintErrors(form, errors) {
    $$('.err-text', form).forEach(function (n) { n.remove(); });
    $$('.field-error', form).forEach(function (n) { n.classList.remove('field-error'); });
    Object.keys(errors).forEach(function (name) {
      var field = form.elements[name];
      if (!field) return;
      field.classList.add('field-error');
      var note = document.createElement('p');
      note.className = 'err-text';
      note.textContent = errors[name];
      var holder = field.closest('.field') || field.parentNode;
      holder.appendChild(note);
    });
    var first = $('.field-error', form);
    if (first) first.focus();
  }

  /* ---------- export ---------- */

  function csv(rows, filename) {
    var body = rows.map(function (row) {
      return row.map(function (cell) {
        var text = String(cell === undefined || cell === null ? '' : cell);
        return /[",\n]/.test(text) ? '"' + text.replace(/"/g, '""') + '"' : text;
      }).join(',');
    }).join('\n');
    download(filename || 'export.csv', body, 'text/csv;charset=utf-8');
  }

  function download(filename, content, mime) {
    var blob = new Blob([content], { type: mime || 'text/plain' });
    var url = URL.createObjectURL(blob);
    var a = document.createElement('a');
    a.href = url; a.download = filename;
    document.body.appendChild(a); a.click(); a.remove();
    global.setTimeout(function () { URL.revokeObjectURL(url); }, 800);
  }

  function print() { global.print(); }

  /* ---------- boot ---------- */

  /* ---------- motion ----------
     Everything here is additive: with the script missing or the reader asking
     for reduced motion, the page keeps the plain, fully visible layout. */

  function motion() {
    if (reduce) return;
    var root = document.documentElement;
    var doc = document;
    var head = $('.site-head');

    var rail = doc.createElement('div');
    rail.className = 'kit-progress';
    rail.setAttribute('aria-hidden', 'true');
    rail.innerHTML = '<i></i>';
    doc.body.appendChild(rail);
    var railFill = rail.firstChild;

    var spot = doc.createElement('div');
    spot.className = 'kit-spot';
    spot.setAttribute('aria-hidden', 'true');
    doc.body.appendChild(spot);

    var fine = global.matchMedia && global.matchMedia('(pointer: fine)').matches;
    var mx = 0, my = 0, frame = 0;
    function paint() {
      frame = 0;
      var span = doc.documentElement.scrollHeight - global.innerHeight;
      railFill.style.setProperty('--p', span > 0 ? Math.min(1, global.scrollY / span).toFixed(4) : 0);
      if (head) head.classList.toggle('is-stuck', global.scrollY > 12);
      root.style.setProperty('--mx', mx + 'px');
      root.style.setProperty('--my', my + 'px');
    }
    function queue() { if (!frame) frame = global.requestAnimationFrame(paint); }
    global.addEventListener('scroll', queue, { passive: true });
    global.addEventListener('resize', queue);

    if (fine) {
      root.classList.add('kit-pointer');
      doc.addEventListener('pointermove', function (e) { mx = e.clientX; my = e.clientY; queue(); }, { passive: true });
    }

    paint();

    /* siblings arrive one after another rather than all at once */
    $$('.reveal').forEach(function (el) {
      var parent = el.parentElement;
      if (!parent) return;
      var peers = Array.prototype.filter.call(parent.children, function (node) {
        return node.classList && node.classList.contains('reveal');
      });
      var at = peers.indexOf(el);
      if (at > 0) el.style.setProperty('--dl', Math.min(at, 6) * 70 + 'ms');
    });

    /* pointer tilt, only where a pointer can hover */
    if (fine) {
      $$('.card, .tile, .stat').forEach(function (el) {
        el.addEventListener('pointermove', function (e) {
          var box = el.getBoundingClientRect();
          el.style.setProperty('--ry', (((e.clientX - box.left) / box.width - .5) * 4.5).toFixed(2) + 'deg');
          el.style.setProperty('--rx', (((box.top + box.height / 2 - e.clientY) / box.height) * 4.5).toFixed(2) + 'deg');
        });
        el.addEventListener('pointerleave', function () {
          el.style.removeProperty('--rx');
          el.style.removeProperty('--ry');
        });
      });
    }

    /* tables and sparklines arrive when they reach the viewport */
    if ('IntersectionObserver' in global) {
      var io = new IntersectionObserver(function (entries) {
        entries.forEach(function (entry) {
          if (!entry.isIntersecting) return;
          var node = entry.target;
          if (node.tagName === 'TABLE') {
            $$('tbody tr', node).forEach(function (row, i) {
              row.style.setProperty('--i', Math.min(i, 22));
            });
          }
          node.classList.add('is-in');
          io.unobserve(node);
        });
      }, { threshold: .06 });

      $$('table').forEach(function (table) { io.observe(table); });
      $$('.spark').forEach(function (chart) { io.observe(chart); });
    }

    /* a bloom on each readout change, once the number has settled */
    var numbers = $$('.readout-big b, .stat b');
    if (numbers.length && global.MutationObserver) {
      var seen = new WeakMap();
      var observer = new MutationObserver(function (records) {
        records.forEach(function (record) {
          var node = record.target.nodeType === 3 ? record.target.parentNode : record.target;
          if (!node || !node.closest) return;
          var host = node.closest('.readout-big') || node;
          global.clearTimeout(seen.get(host));
          seen.set(host, global.setTimeout(function () {
            host.classList.remove('is-bumped');
            void host.offsetWidth;
            host.classList.add('is-bumped');
            seen.set(host, global.setTimeout(function () {
              host.classList.remove('is-bumped');
            }, 900));
          }, 240));
        });
      });
      numbers.forEach(function (el) {
        observer.observe(el, { childList: true, characterData: true, subtree: true });
      });
    }
  }

  function boot(options) {
    var opts = options || {};
    document.addEventListener('DOMContentLoaded', function () {
      motion();
      nav();
      activeLinks();
      reveal();
      counters();
      tabs(document);
      modal();
      if (opts.ready) opts.ready();
      document.body.classList.add('is-ready');
    });
  }

  global.kit = {
    reduce: reduce,
    $: $, $$: $$, on: on, setText: setText,
    money: money, amount: amount, pct: pct, num: num, clamp: clamp, slug: slug,
    dateLabel: dateLabel, uid: uid, store: store,
    reveal: reveal, counters: counters, sparkline: sparkline, bars: bars, donut: donut,
    tabs: tabs, toast: toast, validate: validate, paintErrors: paintErrors,
    csv: csv, download: download, print: print, motion: motion, boot: boot
  };
})(window);
