/* AfriKit pages — behaviour for the editorial / data / record pages.
   Depends on kit.js (global `kit`). No third-party libraries. */
(function (global) {
  'use strict';

  var $ = kit.$, $$ = kit.$$;

  function escapeHtml(s) {
    return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) {
      return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
    });
  }

  function num(v) {
    if (typeof v === 'number') return v;
    var n = parseFloat(String(v).replace(/[^0-9.eE\-+]/g, ''));
    return isNaN(n) ? null : n;
  }

  /* ---------- reading progress ---------- */

  function progress() {
    var bar = $('.progress');
    if (!bar) return;
    var doc = document.documentElement, queued = false;
    function paint() {
      queued = false;
      var max = doc.scrollHeight - global.innerHeight;
      bar.style.transform = 'scaleX(' + (max > 0 ? Math.min(1, Math.max(0, global.scrollY / max)) : 1) + ')';
    }
    global.addEventListener('scroll', function () {
      if (!queued) { queued = true; global.requestAnimationFrame(paint); }
    }, { passive: true });
    global.addEventListener('resize', paint);
    paint();
  }

  /* ---------- contents: build then track the visible heading ---------- */

  function toc() {
    var list = $('.toc ol');
    if (!list) return;
    var heads = $$('main h2[id]');
    if (!list.children.length) {
      list.innerHTML = heads.map(function (h) {
        return '<li><a href="#' + h.id + '">' + escapeHtml(h.textContent.replace(/^\s*\d+\s*·\s*/, '').trim()) + '</a></li>';
      }).join('');
    }
    var links = $$('a', list);
    if (!links.length || !('IntersectionObserver' in global)) return;
    var io = new IntersectionObserver(function (entries) {
      entries.forEach(function (e) {
        if (!e.isIntersecting) return;
        links.forEach(function (l) { l.classList.toggle('is-here', l.getAttribute('href') === '#' + e.target.id); });
      });
    }, { rootMargin: '-40% 0px -50% 0px' });
    heads.forEach(function (h) { io.observe(h); });
  }

  /* ---------- live filtering over a data table ---------- */

  function filter(tableSel, opts) {
    var table = $(tableSel);
    if (!table) return null;
    opts = opts || {};
    var body = $('tbody', table);
    var input = opts.input ? $(opts.input) : null;
    var select = opts.select ? $(opts.select) : null;
    var counter = opts.count ? $(opts.count) : null;
    var rows = $$('tr', body);
    var empty = document.createElement('tr');
    empty.className = 'empty-row';
    empty.innerHTML = '<td colspan="' + table.tHead.rows[0].cells.length + '">' +
      escapeHtml(opts.empty || 'Nothing here matches that search.') + '</td>';
    empty.hidden = true;
    body.appendChild(empty);

    function apply() {
      var words = (input ? input.value : '').trim().toLowerCase().split(/\s+/).filter(Boolean);
      var sel = select ? select.value : '';
      var selCol = select && select.dataset.col != null ? Number(select.dataset.col) : -1;
      var shown = 0;
      rows.forEach(function (tr) {
        if (tr === empty) return;
        var ok = !words.length || words.every(function (w) { return tr.textContent.toLowerCase().indexOf(w) > -1; });
        if (ok && sel && sel !== 'all' && selCol > -1) {
          var cell = tr.cells[selCol];
          ok = !!cell && cell.textContent.trim().toLowerCase() === sel.toLowerCase();
        }
        tr.hidden = !ok;
        if (ok) shown++;
      });
      empty.hidden = shown !== 0;
      if (counter) counter.textContent = shown + ' / ' + rows.length;
      return shown;
    }

    if (input) input.addEventListener('input', apply);
    if (select) select.addEventListener('change', apply);
    apply();
    return { apply: apply, rows: rows };
  }

  /* ---------- click-to-sort headers (th[data-sort="colIndex"]) ---------- */

  function sortable(tableSel, onChange) {
    var table = $(tableSel);
    if (!table) return;
    var body = $('tbody', table);
    $$('th[data-sort]', table.tHead).forEach(function (th) {
      var col = Number(th.dataset.sort);
      th.tabIndex = 0;
      th.setAttribute('role', 'button');
      function run() {
        var dir = th.classList.contains('is-asc') ? -1 : 1;
        $$('th', table.tHead).forEach(function (h) { h.classList.remove('is-asc', 'is-desc'); });
        th.classList.add(dir === 1 ? 'is-asc' : 'is-desc');
        var rows = $$('tr', body).filter(function (tr) { return !tr.classList.contains('empty-row'); });
        rows.sort(function (a, b) {
          var av = a.cells[col] ? a.cells[col].textContent.trim() : '';
          var bv = b.cells[col] ? b.cells[col].textContent.trim() : '';
          var an = num(av), bn = num(bv);
          if (an !== null && bn !== null) return (an - bn) * dir;
          return av.localeCompare(bv, 'en', { numeric: true, sensitivity: 'base' }) * dir;
        });
        rows.forEach(function (tr) { tr.style.animation = 'none'; body.appendChild(tr); });
        global.requestAnimationFrame(function () {
          rows.forEach(function (tr) { tr.style.animation = ''; });
        });
        if (onChange) onChange();
      }
      th.addEventListener('click', run);
      th.addEventListener('keydown', function (e) {
        if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); run(); }
      });
    });
  }

  /* ---------- the record page: saved cases from the tool ---------- */

  function cases(key, cols, opts) {
    opts = opts || {};
    var store = kit.store(key, []);
    var body = opts.body ? $(opts.body) : null;
    if (!body) return null;

    function cell(row, col) {
      var v = row[col.k];
      if (v === undefined || v === null || v === '') return '<span class="muted">—</span>';
      return col.fmt ? col.fmt(v, row) : escapeHtml(v);
    }
    function render() {
      var rows = store.read().slice();
      if (opts.sortBy) rows.sort(function (a, b) { return String(b[opts.sortBy]).localeCompare(String(a[opts.sortBy])); });
      if (opts.limit) rows = rows.slice(0, opts.limit);
      body.innerHTML = rows.map(function (r) {
        return '<tr>' + cols.map(function (c) {
          return '<td' + (c.n ? ' class="n"' : '') + '>' + cell(r, c) + '</td>';
        }).join('') + '<td class="n"><button class="btn btn-ghost btn-sm" type="button" data-del="' +
          escapeHtml(r.id) + '" aria-label="Remove this entry">&times;</button></td></tr>';
      }).join('');
      if (!rows.length) {
        body.innerHTML = '<tr><td colspan="' + (cols.length + 1) + '"><div class="case-empty">' +
          '<h3>' + escapeHtml(opts.emptyTitle || 'No saved entries yet') + '</h3><p>' +
          escapeHtml(opts.emptyText || 'Run the calculator and save a case — it lands here, on this device only.') + '</p></div></td></tr>';
      }
      if (opts.summary) {
        var foot = opts.summary ? $(opts.summary) : null;
        if (foot) foot.innerHTML = summaryFor(rows);
      }
      return rows;
    }
    function summaryFor(rows) {
      if (opts.summaryFn) return opts.summaryFn(rows);
      return '<span class="tag">' + rows.length + ' saved</span>';
    }
    kit.on(body, 'click', '[data-del]', function (e, btn) {
      var keep = store.read().filter(function (r) { return r.id !== btn.dataset.del; });
      store.write(keep);
      render();
      kit.toast('Entry removed');
    });
    render();
    return { render: render, all: function () { return store.read(); } };
  }

  /* ---------- export helpers used by record pages ---------- */

  function exportCsv(handle, filename) {
    if (!handle) return;
    var rows = handle.all();
    if (!rows.length) { kit.toast('Nothing to export yet'); return; }
    var keys = Object.keys(rows[0]);
    kit.csv([keys].concat(rows.map(function (r) { return keys.map(function (k) { return r[k]; }); })), filename);
    kit.toast('Downloaded ' + filename);
  }

  function auto() {
    kit.on(document, 'click', '[data-copy]', function (e, btn) {
      var src = $(btn.getAttribute('data-copy'));
      if (!src) return;
      var text = (src.innerText || src.textContent || '').trim();
      if (global.navigator && navigator.clipboard) {
        navigator.clipboard.writeText(text).then(function () { kit.toast('Copied to your clipboard'); },
          function () { kit.toast('Select the text and copy it manually'); });
      } else { kit.toast('Select the text and copy it manually'); }
    });
    kit.on(document, 'click', '[data-print]', function () { kit.print(); });
    kit.on(document, 'click', '[data-csv]', function (e, btn) {
      if (btn.dataset.csv && api.exports && api.exports[btn.dataset.csv]) api.exports[btn.dataset.csv]();
    });
    kit.on(document, 'click', '[data-clear]', function (e, btn) {
      var name = btn.getAttribute('data-clear');
      if (!api.stores[name]) return;
      if (!global.confirm('Clear every saved entry on this device? This cannot be undone.')) return;
      api.stores[name].write([]);
      if (api.rerender[name]) api.rerender[name]();
      kit.toast('Cleared');
    });
  }

  var api = { exports: {}, stores: {}, rerender: {} };

  global.pages = {
    progress: progress, toc: toc, filter: filter, sortable: sortable, cases: cases,
    exportCsv: exportCsv, escape: escapeHtml, num: num, hooks: api,
    boot: function (setup) {
      kit.boot({
        ready: function () {
          progress();
          toc();
          auto();
          if (setup) setup(api);
        }
      });
    }
  };
})(window);
