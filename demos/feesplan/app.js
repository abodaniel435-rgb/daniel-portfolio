/* FeesPlan — school-fees engine. Every figure is computed in this tab; nothing is uploaded. */
(function () {
  'use strict';

  /* ------------------------------------------------------------------ *
   * indicative bands. Private basic-education fees sit inside the
   * ₦120,000 – ₦1,200,000 a year range; public fees are low but the
   * levy stack (uniform, books, PTA, exam, ICT/lab, transport) is real.
   * ------------------------------------------------------------------ */
  var DEFAULT_BANDS = [
    { key: 'public', name: 'Public school (state)', fees: 12000, terms: 3, ex: { uniform: 4500, books: 6000, pta: 2500, exam: 3000, ict: 2000, transport: 12000, sports: 1500, boarding: 0 } },
    { key: 'lowcost', name: 'Low-cost private', fees: 35000, terms: 3, ex: { uniform: 6500, books: 9000, pta: 3000, exam: 4000, ict: 5000, transport: 18000, sports: 2000, boarding: 0 } },
    { key: 'standard', name: 'Standard private', fees: 120000, terms: 3, ex: { uniform: 12000, books: 18000, pta: 8000, exam: 10000, ict: 15000, transport: 30000, sports: 5000, boarding: 0 } },
    { key: 'premium', name: 'Premium private', fees: 450000, terms: 3, ex: { uniform: 30000, books: 45000, pta: 25000, exam: 20000, ict: 40000, transport: 65000, sports: 15000, boarding: 0 } },
    { key: 'unipub', name: 'University — public', fees: 150000, terms: 2, ex: { uniform: 0, books: 35000, pta: 0, exam: 15000, ict: 25000, transport: 60000, sports: 8000, boarding: 250000 } },
    { key: 'unipriv', name: 'University — private', fees: 650000, terms: 2, ex: { uniform: 0, books: 60000, pta: 0, exam: 30000, ict: 40000, transport: 80000, sports: 10000, boarding: 400000 } }
  ];

  var EXTRAS = [
    { k: 'exam', label: 'Exam fee', when: 'term' },
    { k: 'ict', label: 'ICT / lab levy', when: 'term' },
    { k: 'transport', label: 'Transport', when: 'term' },
    { k: 'sports', label: 'Art, sports & sports day', when: 'term' },
    { k: 'boarding', label: 'Boarding / hostel', when: 'term' },
    { k: 'uniform', label: 'Uniform & shoes', when: 'year' },
    { k: 'books', label: 'Books & materials', when: 'year' },
    { k: 'pta', label: 'PTA / association dues', when: 'year' }
  ];

  var CLASSES = ['Creche', 'Nursery 1', 'Nursery 2', 'Primary 1', 'Primary 2', 'Primary 3', 'Primary 4',
    'Primary 5', 'Primary 6', 'JSS 1', 'JSS 2', 'JSS 3', 'SS 1', 'SS 2', 'SS 3',
    'University 100L', 'University 200L', 'University 300L', 'University 400L'];

  var PERSONAS = {
    ibadan: {
      label: 'Ibadan household — teacher and trader, three children',
      kids: [
        { name: 'Ifeoluwa', cls: 'Primary 4', band: 'public' },
        { name: 'Bunmi', cls: 'JSS 2', band: 'lowcost' },
        { name: 'Dayo', cls: 'SS 1', band: 'standard' }
      ],
      bill: 0, afford: 45000, months: 8
    },
    lagos: {
      label: 'Lagos — single mother, JSS fees due in five weeks',
      kids: [{ name: 'Tolu', cls: 'JSS 2', band: 'standard' }],
      afford: 40000, months: 1.25
    },
    kaduna: {
      label: 'Kaduna — civil servant, salary paid late, two children',
      kids: [
        { name: 'Amina', cls: 'Primary 5', band: 'public' },
        { name: 'Yakubu', cls: 'Nursery 2', band: 'lowcost' }
      ],
      afford: 22000, months: 4
    }
  };

  var bandStore = kit.store('feesplan:bands', null);
  var kidStore = kit.store('feesplan:kids', null);
  var planStore = kit.store('feesplan:plans', []);
  var circleStore = kit.store('feesplan:circle', null);

  var bands = normaliseBands(bandStore.read() || DEFAULT_BANDS);
  var kids = (kidStore.read() || []).map(sanitiseKid);
  if (!kids.length) kids = [newKid('standard', 'JSS 2', 'Tolu'), newKid('lowcost', 'Primary 5', 'Amara')];

  var plan = null;
  var circle = sanitiseCircle(circleStore.read());
  var lastPaint = {};
  var MS_DAY = 86400000;
  var MONTHS_YEAR = 12;

  /* ------------------------------------------------------------------ *
   * small helpers
   ------------------------------------------------------------------ */
  function el(id) { return document.getElementById(id); }
  function val(id) { var n = el(id); return n ? n.value : ''; }
  function numv(id) { return kit.num(val(id)); }
  function money(v) { return kit.money(Math.round(Number(v) || 0)); }
  function round(v) { return Math.round((Number(v) || 0) * 100) / 100; }
  function escapeHtml(text) {
    var div = document.createElement('div');
    div.textContent = String(text);
    return div.innerHTML;
  }
  function today() { var d = new Date(); return new Date(d.getFullYear(), d.getMonth(), d.getDate()); }
  function parseDate(v) {
    if (!v) return null;
    var p = String(v).split('-');
    if (p.length !== 3) return null;
    var d = new Date(Number(p[0]), Number(p[1]) - 1, Number(p[2]));
    return isNaN(d.getTime()) ? null : d;
  }
  function iso(d) {
    if (!d) return '';
    var m = String(d.getMonth() + 1), day = String(d.getDate());
    return d.getFullYear() + '-' + (m.length < 2 ? '0' + m : m) + '-' + (day.length < 2 ? '0' + day : day);
  }
  function addDays(d, n) { return new Date(d.getFullYear(), d.getMonth(), d.getDate() + n); }
  function addMonths(d, n) { return new Date(d.getFullYear(), d.getMonth() + n, d.getDate()); }
  function label(d) { return d ? kit.dateLabel(d) : '—'; }
  function daysBetween(a, b) { return Math.round((b - a) / MS_DAY); }

  /* calendar-accurate month count, with a fractional tail */
  function monthsUntil(due, from) {
    if (!due) return 0;
    from = from || today();
    var m = (due.getFullYear() - from.getFullYear()) * 12 + (due.getMonth() - from.getMonth());
    var anchor = addMonths(from, m);
    if (anchor > due) { m -= 1; anchor = addMonths(from, m); }
    var next = addMonths(from, m + 1);
    var span = next - anchor;
    var frac = span > 0 ? (due - anchor) / span : 0;
    return Math.max(0, m + frac);
  }

  function setFigure(id, value) {
    var node = el(id);
    if (!node) return;
    var text = value === null || value === undefined ? '—' : String(value);
    if (node.textContent === text) return;
    node.textContent = text;
    if (!kit.reduce && lastPaint[id] !== undefined) {
      node.classList.remove('tick');
      void node.offsetWidth;
      node.classList.add('tick');
    }
    lastPaint[id] = text;
  }

  function verdictFor(boxId, titleId, bodyId, title, body, cls) {
    var box = el(boxId);
    if (box) box.className = 'verdict' + (cls ? ' ' + cls : '');
    setFigure(titleId, title);
    setFigure(bodyId, body);
  }

  /* ------------------------------------------------------------------ *
   * band + kid state
   ------------------------------------------------------------------ */
  function normaliseBands(list) {
    var out = (list || []).map(function (b, i) {
      var def = DEFAULT_BANDS[i % DEFAULT_BANDS.length];
      var ex = {};
      EXTRAS.forEach(function (e) { ex[e.k] = Number(b.ex && b.ex[e.k]) || 0; });
      return {
        key: b.key || def.key, name: b.name || def.name,
        fees: Number(b.fees) || 0, terms: Number(b.terms) === 2 ? 2 : 3, ex: ex
      };
    });
    return out.length ? out : DEFAULT_BANDS.map(function (b) { return JSON.parse(JSON.stringify(b)); });
  }
  function bandByKey(key) {
    for (var i = 0; i < bands.length; i++) if (bands[i].key === key) return bands[i];
    return bands[0];
  }
  function sanitiseKid(k) {
    var ex = {};
    EXTRAS.forEach(function (e) { ex[e.k] = Number(k.ex && k.ex[e.k]) || 0; });
    return {
      id: k.id || kit.uid('kid'), name: String(k.name || 'Child'), cls: k.cls || 'JSS 1',
      band: k.band || 'standard', fees: Number(k.fees) || 0, terms: Number(k.terms) === 2 ? 2 : 3,
      ex: ex, custom: !!k.custom
    };
  }
  function newKid(bandKey, cls, name) {
    var b = null;
    for (var i = 0; i < DEFAULT_BANDS.length; i++) if (DEFAULT_BANDS[i].key === bandKey) b = DEFAULT_BANDS[i];
    b = b || DEFAULT_BANDS[2];
    var kid = sanitiseKid({ name: name || 'Child', cls: cls || 'JSS 1', band: b.key });
    kid.fees = b.fees; kid.terms = b.terms;
    EXTRAS.forEach(function (e) { kid.ex[e.k] = b.ex[e.k] || 0; });
    return kid;
  }
  function applyBand(kid) {
    var b = bandByKey(kid.band);
    if (!b) return;
    kid.fees = b.fees; kid.terms = b.terms;
    EXTRAS.forEach(function (e) { kid.ex[e.k] = b.ex[e.k] || 0; });
  }

  function termlyExtras(kid) {
    return EXTRAS.reduce(function (a, e) { return a + (e.when === 'term' ? (Number(kid.ex[e.k]) || 0) : 0); }, 0);
  }
  function yearlyExtras(kid) {
    return EXTRAS.reduce(function (a, e) { return a + (e.when === 'year' ? (Number(kid.ex[e.k]) || 0) : 0); }, 0);
  }
  function kidTerm(kid) {
    var t = Math.max(1, Number(kid.terms) || 3);
    return (Number(kid.fees) || 0) + termlyExtras(kid) + yearlyExtras(kid) / t;
  }
  function kidYear(kid) { return kidTerm(kid) * (Math.max(1, Number(kid.terms) || 3)); }
  function householdTerm() { return kids.reduce(function (a, k) { return a + kidTerm(k); }, 0); }
  function householdYear() { return kids.reduce(function (a, k) { return a + kidYear(k); }, 0); }

  /* ------------------------------------------------------------------ *
   * 02 — planner
   ------------------------------------------------------------------ */
  function paintPresets() {
    var sel = el('band-preset');
    if (!sel) return;
    var current = sel.value;
    sel.innerHTML = '<option value="">Add a child at a band…</option>' + bands.map(function (b) {
      return '<option value="' + b.key + '">' + escapeHtml(b.name) + ' — ' + money(b.fees) + ' / term</option>';
    }).join('');
    sel.value = current;
  }

  function kidCard(kid, index) {
    var opts = CLASSES.map(function (c) {
      return '<option value="' + c + '"' + (c === kid.cls ? ' selected' : '') + '>' + c + '</option>';
    }).join('');
    var bandOpts = bands.map(function (b) {
      return '<option value="' + b.key + '"' + (b.key === kid.band ? ' selected' : '') + '>' + escapeHtml(b.name) + '</option>';
    }).join('');
    var extras = EXTRAS.map(function (e) {
      return '<div class="field"><label for="k' + index + '-' + e.k + '">' + e.label + ' <span class="tiny">(' +
        (e.when === 'term' ? 'every term' : 'once a year') + ')</span></label>' +
        '<div class="input-group"><span class="prefix">₦</span><input id="k' + index + '-' + e.k + '" type="number" min="0" step="500" data-kid="' + index +
        '" data-extra="' + e.k + '" value="' + (Number(kid.ex[e.k]) || 0) + '"></div></div>';
    }).join('');
    return '<article class="kid-card" data-card="' + kid.id + '">' +
      '<div class="kid-top"><h3>' + escapeHtml(kid.name || 'Child ' + (index + 1)) + '</h3>' +
      '<span class="kid-total">' + money(kidTerm(kid)) + ' <span class="tiny">/ term</span></span></div>' +
      '<div class="kid-grid">' +
      '<div class="field"><label for="k' + index + '-name">Child name</label><input id="k' + index + '-name" maxlength="40" data-kid="' + index + '" data-field="name" value="' + escapeHtml(kid.name) + '"></div>' +
      '<div class="field"><label for="k' + index + '-cls">Class</label><select id="k' + index + '-cls" data-kid="' + index + '" data-field="cls">' + opts + '</select></div>' +
      '<div class="field"><label for="k' + index + '-band">School type (band)</label><select id="k' + index + '-band" data-kid="' + index + '" data-field="band">' + bandOpts + '</select></div>' +
      '<div class="field"><label for="k' + index + '-terms">Terms per year</label><select id="k' + index + '-terms" data-kid="' + index + '" data-field="terms"><option value="3"' + (Number(kid.terms) === 3 ? ' selected' : '') + '>Three terms</option><option value="2"' + (Number(kid.terms) === 2 ? ' selected' : '') + '>Two sessions</option></select></div>' +
      '<div class="field wide"><label for="k' + index + '-fees">School fees per term (the number on the letter)</label><div class="input-group"><span class="prefix">₦</span><input id="k' + index + '-fees" type="number" min="0" step="1000" data-kid="' + index + '" data-field="fees" value="' + (Number(kid.fees) || 0) + '"></div></div>' +
      '</div>' +
      '<details class="extras"' + (kid.custom ? ' open' : '') + '><summary>Hidden extras — ' + money(termlyExtras(kid)) + ' a term + ' + money(yearlyExtras(kid)) + ' a year</summary>' +
      '<div class="extras-grid"><p class="group-label">Charged every term</p>' + extras + '</div></details>' +
      '<p class="extras-sum">' + (kid.custom ? 'Edited by you — this child no longer follows the band automatically.' : 'Following the band. Edit any figure to take control.') +
      ' <button class="pay-btn" type="button" data-reband="' + index + '">Use band numbers again</button></p>' +
      '<div class="row" style="margin-top:10px"><button class="kid-del" type="button" data-delkid="' + index + '" aria-label="Remove ' + escapeHtml(kid.name) + ' ×">×</button>' +
      '<span class="tiny">' + money(kidYear(kid)) + ' a year · ' + money(kidYear(kid) / 12) + ' a month</span></div>' +
      '</article>';
  }

  function paintKids() {
    var host = el('kids');
    host.innerHTML = kids.map(kidCard).join('') ||
      '<p class="muted">No children yet — pick a band above or load one of the three households.</p>';
    kidStore.write(kids);

    var body = el('kid-rows');
    if (!kids.length) { body.innerHTML = '<tr><td colspan="7">Add a child to see the household total.</td></tr>'; }
    else {
      body.innerHTML = kids.map(function (k) {
        return '<tr><td>' + escapeHtml(k.name || 'Child') + '</td><td>' + escapeHtml(k.cls) + '</td><td>' + escapeHtml(bandByKey(k.band).name) + '</td>' +
          '<td class="num">' + money(k.fees) + '</td>' +
          '<td class="num">' + money(termlyExtras(k) + yearlyExtras(k) / Math.max(1, k.terms)) + '</td>' +
          '<td class="num"><b>' + money(kidTerm(k)) + '</b></td>' +
          '<td class="num">' + money(kidYear(k)) + '</td></tr>';
      }).join('');
    }
    el('kid-foot').innerHTML = kids.length ? '<tr><td>Household</td><td>' + kids.length + (kids.length === 1 ? ' child' : ' children') +
      '</td><td></td><td class="num">' + money(kids.reduce(function (a, k) { return a + k.fees; }, 0)) + '</td>' +
      '<td class="num">' + money(householdTerm() - kids.reduce(function (a, k) { return a + k.fees; }, 0)) + '</td>' +
      '<td class="num">' + money(householdTerm()) + '</td><td class="num">' + money(householdYear()) + '</td></tr>' : '';
  }

  function paintPlanner() {
    var term = householdTerm(), year = householdYear();
    var feesOnly = kids.reduce(function (a, k) { return a + k.fees; }, 0);
    setFigure('pl-term', money(term));
    setFigure('pl-note', kids.length ? kids.length + (kids.length === 1 ? ' child' : ' children') + ' · ' + money(term * 3) + ' if all three terms look like this one' : 'add a child to begin');
    setFigure('pl-fees', money(feesOnly));
    setFigure('pl-extras', money(term - feesOnly));
    setFigure('pl-year', money(year));
    setFigure('pl-month', money(year / 12));
    setFigure('pl-week', money(year / 52));
    setFigure('pl-count', String(kids.length));

    var rate = kit.clamp(numv('hz-rate'), 0, 60);
    setFigure('pl-rise', kids.length ? '+' + money(year * rate / 100) + ' a year' : '—');
    var top = kids.slice().sort(function (a, b) { return kidYear(b) - kidYear(a); })[0];
    setFigure('pl-top', top ? (top.name || 'Child') + ' · ' + money(kidYear(top)) : '—');

    if (!kids.length) {
      verdictFor('pl-verdict', 'pl-verdict-title', 'pl-verdict-body', 'Nothing entered yet',
        'Add each child with their school band and the extras, and the household number builds up here.', '');
      return;
    }
    var shareExtras = term > 0 ? (term - feesOnly) / term * 100 : 0;
    if (year > 1200000) {
      verdictFor('pl-verdict', 'pl-verdict-title', 'pl-verdict-body', money(year) + ' a year is above the public ₦1.2m band',
        'That is a real number for premium private and university boarding households. It is also ' + money(year / 12) +
        ' a month — the only question is whether the saving starts now or in five weeks\' time.', 'warn');
    } else if (shareExtras > 28) {
      verdictFor('pl-verdict', 'pl-verdict-title', 'pl-verdict-body', 'The levies are ' + kit.pct(shareExtras, 0) + ' of your bill',
        'Fees are ' + money(feesOnly) + ' a term; uniforms, books, PTA dues, exam and ICT levies add ' + money(term - feesOnly) +
        '. Budget the levies separately or they arrive as a surprise in week three.', 'warn');
    } else {
      verdictFor('pl-verdict', 'pl-verdict-title', 'pl-verdict-body', 'Plannable',
        money(term) + ' a term, ' + money(year / 12) + ' a month, ' + money(year / 52) + ' a week. Take that weekly figure to section 04.', 'ok');
    }
  }

  /* ------------------------------------------------------------------ *
   * 03 — six-year horizon
   ------------------------------------------------------------------ */
  function horizon() {
    var rate = kit.clamp(numv('hz-rate'), 0, 60) / 100;
    var years = kit.clamp(Math.round(numv('hz-years') || 6), 1, 12);
    var base = householdYear();
    var rows = [], yearIdx = new Date().getFullYear();
    for (var y = 0; y < years; y++) {
      var factor = Math.pow(1 + rate, y);
      var perKid = kids.map(function (k) { return kidYear(k) * factor; });
      rows.push({ year: yearIdx + y, total: base * factor, perKid: perKid, factor: factor });
    }
    return rows;
  }

  function paintHorizon() {
    var rows = horizon();
    if (!kids.length) {
      el('hz-bars').innerHTML = '<p class="tiny">Add children in section 02 and the forecast builds here.</p>';
      el('hz-kids').innerHTML = '<p class="tiny">No children yet.</p>';
      el('hz-head').innerHTML = ''; el('hz-body').innerHTML = '';
      setFigure('hz-sum', '—'); setFigure('hz-mult', '—'); setFigure('hz-avg', '—');
      el('hz-note').textContent = 'Add children above and this becomes a per-child forecast.';
      return;
    }
    kit.bars(el('hz-bars'), rows.map(function (r) {
      return { label: r.year, value: Math.round(r.total), display: money(r.total) };
    }));
    var lastYear = rows[rows.length - 1];
    kit.bars(el('hz-kids'), kids.map(function (k, i) {
      return { label: escapeHtml(k.name || 'Child ' + (i + 1)), value: Math.round(lastYear.perKid[i]), display: money(lastYear.perKid[i]) };
    }).sort(function (a, b) { return b.value - a.value; }));

    var sum = rows.reduce(function (a, r) { return a + r.total; }, 0);
    setFigure('hz-sum', money(sum));
    setFigure('hz-mult', '×' + (rows[0].total ? (lastYear.total / rows[0].total).toFixed(2) : '0') + ' (' + rows[0].year + ' → ' + lastYear.year + ')');
    setFigure('hz-avg', money(rows[0].total / 12));
    el('hz-note').textContent = 'At ' + kit.pct(numv('hz-rate'), 0) + ' a year, the same children cost ' + money(lastYear.total) +
      ' in ' + lastYear.year + ' — ' + money(lastYear.total - rows[0].total) + ' more than this year.';

    el('hz-head').innerHTML = '<tr><th>Year</th>' + kids.map(function (k, i) {
      return '<th class="num">' + escapeHtml(k.name || 'Child ' + (i + 1)) + '</th>';
    }).join('') + '<th class="num">Household</th><th class="num">Monthly</th></tr>';
    el('hz-body').innerHTML = rows.map(function (r) {
      return '<tr><td>' + r.year + '</td>' + r.perKid.map(function (v) { return '<td class="num">' + money(v) + '</td>'; }).join('') +
        '<td class="num"><b>' + money(r.total) + '</b></td><td class="num">' + money(r.total / 12) + '</td></tr>';
    }).join('');
  }

  /* ------------------------------------------------------------------ *
   * 04 — sinking fund
   ------------------------------------------------------------------ */
  function sinkingDeposit(need, months, annualRate, termWeeks) {
    var n = Math.max(1, Math.ceil(months - 0.001));
    var i = (annualRate || 0) / 100 / MONTHS_YEAR;
    if (i <= 0) return need / n;
    return need * i / (Math.pow(1 + i, n) - 1);
  }

  function sinkingModel() {
    var bill = numv('sf-bill');
    var due = parseDate(val('sf-due')) || addMonths(today(), 8);
    var months = monthsUntil(due, today());
    var have = numv('sf-have');
    var rate = kit.clamp(numv('sf-rate'), 0, 30);
    var weeks = kit.clamp(numv('sf-weeks'), 6, 20);
    var afford = numv('sf-afford');
    var need = Math.max(0, bill - have);
    var deposit = sinkingDeposit(need, months, rate, weeks);
    var daysLeft = Math.max(0, daysBetween(today(), due));
    var weekly = daysLeft > 0 ? need / (daysLeft / 7) : need;
    var savedByDue = have + afford * Math.max(0, months);
    var shortfall = Math.max(0, bill - savedByDue);
    var coverPct = bill > 0 ? kit.clamp(savedByDue / bill * 100, 0, 100) : 0;
    return {
      bill: bill, due: due, months: months, need: need, have: have, rate: rate, weeksTerm: weeks,
      deposit: deposit, daysLeft: daysLeft, weekly: weekly, afford: afford,
      savedByDue: savedByDue, shortfall: shortfall, coverPct: coverPct,
      weeksCovered: bill > 0 ? weeks * (1 - shortfall / bill) : 0,
      weeksUnpaid: bill > 0 ? weeks * (shortfall / bill) : 0,
      parts: [
        { name: '40% — on admission', pct: 40, date: due },
        { name: '30% — mid-term', pct: 30, date: addDays(due, 28) },
        { name: '30% — before examinations', pct: 30, date: addDays(due, 63) }
      ]
    };
  }

  function paintSinking() {
    var m = sinkingModel();
    setFigure('sf-due-note', 'Months from today: ' + m.months.toFixed(1) + ' · ' + m.daysLeft + ' days');
    setFigure('sf-monthly', money(m.deposit));
    setFigure('sf-monthly-note', m.bill > 0
      ? 'to cover ' + money(m.need) + ' by ' + label(m.due) + (m.rate > 0 ? ' at ' + kit.pct(m.rate, 1) + ' a year on the fund' : ' with the fund paying nothing')
      : 'enter the term bill');
    setFigure('sf-m', m.months.toFixed(1) + ' months');
    setFigure('sf-weekly', m.daysLeft > 0 ? money(m.weekly) : money(m.need));
    setFigure('sf-cover', m.bill > 0 ? kit.pct(m.coverPct, 0) + ' of the bill' : '—');
    setFigure('sf-short', m.shortfall > 0 ? money(m.shortfall) : 'none — you are covered');
    setFigure('sf-weeksunpaid', m.shortfall > 0 ? m.weeksUnpaid.toFixed(1) + ' of ' + m.weeksTerm : '0 weeks');

    m.parts.forEach(function (p, i) {
      var amt = m.bill * p.pct / 100;
      var saved = m.have + m.deposit * Math.max(0, monthsUntil(p.date, today()));
      p.amount = amt;
      p.saved = saved;
      setFigure('sf-p' + (i + 1), money(amt));
    });

    if (m.bill <= 0) {
      verdictFor('sf-verdict', 'sf-verdict-title', 'sf-verdict-body', 'Enter the bill and the date',
        'This box tells you plainly whether the date is on your side.', '');
    } else if (m.months <= 0.75) {
      verdictFor('sf-verdict', 'sf-verdict-title', 'sf-verdict-body', money(m.need) + ' is ' + m.daysLeft + ' days away',
        'Saving cannot rescue a deadline this close. Write to the bursar today (section 07) with the 40% you can raise, and use the instalment option in section 05.', 'bad');
    } else if (m.afford > 0 && m.deposit > m.afford) {
      var gap = m.deposit - m.afford;
      verdictFor('sf-verdict', 'sf-verdict-title', 'sf-verdict-body', 'You are ₦' + kit.amount(gap) + ' a month short',
        'You can save ' + money(m.afford) + ' but the bill needs ' + money(m.deposit) + '. You will reach ' + kit.pct(m.coverPct, 0) +
        ' by the due date — leaving ' + money(m.shortfall) + ', about ' + m.weeksUnpaid.toFixed(1) +
        ' weeks of the term unfunded. That is the exact window a child gets kept from examinations, so negotiate it in writing before term starts.', 'warn');
    } else if (m.months >= 6) {
      verdictFor('sf-verdict', 'sf-verdict-title', 'sf-verdict-body', 'This one is beatable',
        money(m.deposit) + ' a month for ' + m.months.toFixed(1) + ' months, or ' + money(m.weekly) +
        ' a week if your income is daily. Start the transfer the day you are paid, not the day the letter comes.', 'ok');
    } else {
      verdictFor('sf-verdict', 'sf-verdict-title', 'sf-verdict-body', 'Tight but doable',
        money(m.deposit) + ' a month for ' + m.months.toFixed(1) + ' months covers the bill. Anything you can pull forward reduces the last two months.', '');
    }

    var cum = 0;
    el('sf-sched').innerHTML = m.parts.map(function (p) {
      cum += p.amount;
      var funded = p.saved >= cum;
      return '<tr><td>' + escapeHtml(p.name) + '</td><td>' + label(p.date) + '</td>' +
        '<td class="num">' + money(p.amount) + '</td><td class="num">' + money(cum) + '</td>' +
        '<td class="num">' + money(Math.max(0, m.bill - cum)) + '</td>' +
        '<td>' + (funded ? '<span class="best">yes — fund reaches ' + money(p.saved) + '</span>'
          : '<span class="bad-num">no — ' + money(Math.max(0, cum - p.saved)) + ' short by then</span>') + '</td></tr>';
    }).join('') || '<tr><td colspan="6">Enter a bill to see the part-payment shape.</td></tr>';
  }

  /* ------------------------------------------------------------------ *
   * 05 — option comparison
   ------------------------------------------------------------------ */
  function optionModel() {
    var B = numv('op-bill');
    var n = kit.clamp(numv('op-months'), 0.25, 36);
    var fr = kit.clamp(numv('op-fundrate'), 0, 30);
    var opp = kit.clamp(numv('op-oppcost'), 0, 120);
    var r = kit.clamp(numv('op-adv-rate'), 0, 60) / 100;
    var A = numv('op-adv-amount'); if (A <= 0) A = B;
    var k = kit.clamp(Math.round(numv('op-adv-months')), 1, 12);
    var s = kit.clamp(numv('op-inst'), 0, 25) / 100;
    var N = kit.clamp(Math.round(numv('op-ajo-n')), 2, 200);
    var turn = kit.clamp(Math.round(numv('op-ajo-turn')), 1, 200);
    var q = kit.clamp(numv('op-ajo-gap'), 0, 60) / 100;
    var weeksToDue = kit.clamp(Math.round(numv('op-ajo-weeks')), 1, 200);
    var termWeeks = kit.clamp(numv('sf-weeks'), 6, 20);
    var afford = numv('sf-afford');

    var dep = sinkingDeposit(B, n, fr);
    var sunk = dep * Math.max(1, Math.ceil(n - 0.001));

    /* salary advance: interest each month on the outstanding balance, principal in k equal parts */
    var advanceInterest = A * r * (k + 1) / 2;
    var oneMonth = A * r;
    var compounded = Math.pow(1 + r, 12) - 1;
    var flat = r * 12;

    var forgone = B * (opp / 100) * (termWeeks / 52);

    /* ajo: pot each week = N × weekly contribution; you need one pot by the deadline */
    var weekly = N > 0 ? B / N : 0;
    var coverage = Math.pow(1 - q, N - 1);
    var withBuffer = 1 - Math.pow(1 - coverage, 2);
    var expectedShort = (N - 1) * q * weekly;
    var ajoExtra = expectedShort * r;
    var inTime = turn <= weeksToDue;

    var rows = [
      {
        key: 'savings', name: 'a — Save monthly into a target-savings / welfare fund',
        cash: B, out: sunk, extra: 0, extraNote: B - sunk > 1 ? money(B - sunk) + ' earned as interest' : 'no interest assumed',
        annual: fr, feasible: afford > 0 ? (dep <= afford ? 'yes' : 'no') : 'yes',
        tag: afford > 0 && dep > afford ? 'part' : 'yes',
        watch: money(dep) + ' a month for ' + n.toFixed(1) + ' months. Zero debt, and the school sees a paid bill on day one.',
        monthly: dep
      },
      {
        key: 'lump', name: 'b — Pay the whole bill from income at term start',
        cash: B, out: B, extra: 0, extraNote: 'no cash cost', forgone: forgone,
        annual: 0, feasible: 'part',
        watch: 'Costs ' + money(forgone) + ' of forgone return at ' + kit.pct(opp, 0) + ' a year — shown, not scolded. Only works if the cash is genuinely sitting there.',
        monthly: 0
      },
      {
        key: 'advance', name: 'c — Salary advance at ' + kit.pct(r * 100, 0) + ' a month',
        cash: B, out: B + advanceInterest, extra: advanceInterest, extraNote: money(A) + ' borrowed, cleared in ' + k + ' month' + (k > 1 ? 's' : ''),
        annual: compounded * 100, feasible: 'yes',
        watch: '₦' + kit.amount(A) + ' for 30 days costs ' + money(oneMonth) + '. Rolled every month that is (1 + ' + (r * 100).toFixed(0) + '%)' +
          '<sup>12</sup> − 1 = ' + kit.pct(compounded * 100, 0) + ' a year (flat ×12 would be ' + kit.pct(flat * 100, 0) + ').',
        monthly: advanceInterest / Math.max(1, n)
      },
      {
        key: 'instalment', name: 'd — Instalment plan agreed with the school' + (s > 0 ? ' (' + kit.pct(s * 100, 1) + ' surcharge)' : ' (no surcharge)'),
        cash: B, out: B * (1 + s), extra: B * s, extraNote: s > 0 ? 'negotiated charge on the bill' : 'the school charges nothing',
        annual: s > 0 ? s * 100 * (52 / termWeeks) : 0, feasible: 'part',
        watch: 'Ask, then get it on paper with dates. Feasibility depends entirely on the school — this tool cannot know their policy.',
        monthly: B * (1 + s) / Math.max(1, n)
      },
      {
        key: 'ajo', name: 'e — School-fee ajo / esusu circle of ' + N + ' members',
        cash: B, out: B + ajoExtra, extra: ajoExtra, extraNote: 'expected gap bridged at ' + kit.pct(r * 100, 0) + ' for a month',
        annual: 0, feasible: inTime ? 'yes' : 'no',
        watch: (inTime
          ? 'Turn lands in week ' + turn + ' of ' + weeksToDue + '. Pot covered on the day ' + kit.pct(coverage * 100, 0) +
            ' of the time; with one buffer week ' + kit.pct(withBuffer * 100, 0) + '.'
          : 'Your turn is week ' + turn + ' but fees are due in week ' + weeksToDue + ' — the circle cannot fund this term.') +
          ' Contribution ' + money(weekly) + ' a week.',
        monthly: weekly * 52 / 12
      }
    ];

    var pool = { yes: 0, part: 1, no: 2 };
    var ranked = rows.slice().sort(function (a, b) { return a.out - b.out; });
    var winner = rows.slice().sort(function (a, b) {
      return (pool[a.feasible] - pool[b.feasible]) || (a.out - b.out);
    })[0];
    var worst = ranked[ranked.length - 1];

    return {
      B: B, n: n, rows: rows, ranked: ranked, winner: winner, worst: worst, r: r, k: k, A: A,
      oneMonth: oneMonth, compounded: compounded, flat: flat, dep: dep,
      weekly: weekly, coverage: coverage, withBuffer: withBuffer, expectedShort: expectedShort,
      inTime: inTime, N: N, turn: turn, q: q, weeksToDue: weeksToDue, s: s, forgone: forgone
    };
  }

  function paintOptions() {
    var m = optionModel();
    setFigure('op-adv-cost', m.A > 0 ? money(m.oneMonth) : '—');
    setFigure('op-adv-apr', m.r > 0 ? kit.pct(m.compounded * 100, 0) + ' a year' : '—');
    var spread = m.winner && m.worst ? m.worst.out - m.winner.out : 0;
    setFigure('op-spread', m.B > 0 ? money(spread) : '—');

    if (m.B <= 0) {
      setFigure('op-winner', '—'); setFigure('op-winner-note', 'enter the bill');
      verdictFor('op-verdict', 'op-verdict-title', 'op-verdict-body', 'Enter the bill',
        'The ranking, the cost of each and the reason appear here.', '');
      el('opt-body').innerHTML = '<tr><td colspan="7">Nothing to compare yet.</td></tr>';
      el('opt-bars').innerHTML = '<p class="tiny">Awaiting a bill.</p>';
      paintAdvanceTable();
      return;
    }

    setFigure('op-winner', m.winner.name.replace(/^[a-e] — /, ''));
    setFigure('op-winner-note', money(m.winner.out) + ' all in, ' + money(m.winner.monthly) + ' a month for ' + m.n.toFixed(1) + ' months');

    var cheapest = m.ranked[0];
    var advice;
    if (m.winner.key === 'savings') {
      advice = 'Saving wins because it costs nothing and there is nobody to disappoint on the first day of term. Set an auto-transfer for the day you are paid: ' +
        money(m.dep) + ' a month until ' + label(parseDate(val('sf-due')) || addMonths(today(), Math.ceil(m.n)));
    } else if (m.winner.key === 'instalment') {
      advice = 'The negotiated instalment plan is the cheapest route here — but it only exists if you ask for it in writing. Section 07 drafts the letter; take the dated plan from section 06 with you.';
    } else if (m.winner.key === 'ajo') {
      advice = 'The circle is cheapest in cash because it charges no interest, and it is the only option that depends on other people. Keep the ledger in section 08 public and ask for a buffer week.';
    } else if (m.winner.key === 'lump') {
      advice = 'Paying at once is cheapest in cash — the cost is the ' + money(m.forgone) + ' the money would have earned in your business. If that money is already working, look at the instalment plan instead.';
    } else {
      advice = 'Even after pricing everything, the advance is the cheapest option here only because the alternatives do not reach the deadline. Treat it as a bridge with a fixed end date, never a payment plan.';
    }
    verdictFor('op-verdict', 'op-verdict-title', 'op-verdict-body',
      'Cheapest feasible: ' + m.winner.name.replace(/^[a-e] — /, '') + ' at ' + money(m.winner.out),
      advice + (m.worst.key !== m.winner.key ? ' The costliest route on this screen, ' + m.worst.name.replace(/^[a-e] — /, '') +
        ', is ' + money(m.worst.out - m.winner.out) + ' more.' : ''),
      m.winner.key === 'advance' ? 'bad' : 'ok');

    el('opt-body').innerHTML = m.ranked.map(function (x, i) {
      var tag = x.feasible === 'yes' ? '<span class="tag-feasible tag-yes">Yes</span>'
        : x.feasible === 'part' ? '<span class="tag-feasible tag-part">Ask the school</span>'
          : '<span class="tag-feasible tag-no">No</span>';
      return '<tr' + (x === m.winner ? ' class="is-best"' : (x.feasible === 'no' ? ' class="is-out"' : '')) + '>' +
        '<td>' + escapeHtml(x.name) + '</td>' +
        '<td class="num">' + money(x.cash) + '</td>' +
        '<td class="num">' + (x.extra > 0 ? '<span class="bad-num">' + money(x.extra) + '</span>' : '—') + (x.forgone ? ' <span class="tiny">†' + money(x.forgone) + ' forgone</span>' : '') + '</td>' +
        '<td class="num"><b>' + money(x.out) + '</b></td>' +
        '<td class="num">' + (x.annual > 0 ? kit.pct(x.annual, 0) : '—') + '</td>' +
        '<td>' + tag + '</td>' +
        '<td class="tiny">' + x.watch + '</td></tr>';
    }).join('');

    kit.bars(el('opt-bars'), m.ranked.map(function (x) {
      return { label: escapeHtml(x.name.split(' — ')[0]) + ' · ' + escapeHtml(x.name.split(' — ')[1].slice(0, 26)), value: Math.round(x.out), display: money(x.out) };
    }));
    paintAdvanceTable();
  }

  function paintAdvanceTable() {
    var rates = [0.10, 0.15, 0.20];
    var chosen = kit.clamp(numv('op-adv-rate'), 0, 60) / 100;
    el('adv-body').innerHTML = rates.map(function (r) {
      var comp = Math.pow(1 + r, 12) - 1;
      return '<tr' + (Math.abs(r - chosen) < 1e-9 ? ' class="is-best"' : '') + '><td>' + kit.pct(r * 100, 0) + ' / month</td>' +
        '<td class="num">' + money(80000 * r) + '</td>' +
        '<td class="num"><span class="apr-huge">' + kit.pct(comp * 100, 0) + '</span></td>' +
        '<td class="num">' + kit.pct(r * 12 * 100, 0) + '</td></tr>';
    }).join('');
  }

  /* ------------------------------------------------------------------ *
   * 06 — dated payment plan
   ------------------------------------------------------------------ */
  var SCHEDULES = {
    '100': function () { return [{ pct: 100, label: 'Full payment', at: 'due' }]; },
    '6040': function () {
      return [{ pct: 60, label: 'On admission', day: 0 }, { pct: 40, label: 'Mid-term', day: 35 }];
    },
    '403030': function () {
      return [
        { pct: 40, label: 'On admission', day: 0 },
        { pct: 30, label: 'Mid-term', day: 28 },
        { pct: 30, label: 'Before examinations', day: 63 }
      ];
    },
    'm4': function () {
      return [0, 28, 56, 84].map(function (d, i) { return { pct: 25, label: 'Monthly payment ' + (i + 1), day: d }; });
    },
    'w13': function () {
      return Array.apply(null, Array(13)).map(function (_, i) { return { pct: 100 / 13, label: 'Week ' + (i + 1) + ' of the term', day: i * 7 }; });
    },
    'w26': function () {
      return Array.apply(null, Array(26)).map(function (_, i) { return { pct: 100 / 26, label: 'Week ' + (i + 1), day: i * 7 }; });
    }
  };

  function buildPlan(preservePaid) {
    var bill = numv('pp-bill');
    var start = parseDate(val('pp-start')) || today();
    var due = parseDate(val('pp-due')) || addMonths(start, 1);
    var kind = val('pp-schedule') || '403030';
    var confirm = kit.clamp(Math.round(numv('pp-confirm')), 0, 21);
    var steps = (SCHEDULES[kind] || SCHEDULES['403030'])();
    var old = preservePaid && plan ? plan.rows : [];
    var running = 0, allocated = 0, rows = [];
    steps.forEach(function (st, i) {
      var amount = Math.round(bill * st.pct / 100);
      if (i === steps.length - 1) amount = Math.round(bill - allocated);
      allocated += amount;
      var date = st.at === 'due' ? due : addDays(start, st.day || 0);
      running += amount;
      rows.push({
        date: iso(date), label: st.label, amount: amount, cum: running, balance: Math.max(0, bill - running),
        confirm: iso(addDays(date, -confirm)), paid: !!(old[i] && old[i].paid)
      });
    });
    plan = {
      name: (val('pp-name') || 'Untitled plan').trim(), bill: bill, schedule: kind,
      start: iso(start), due: iso(due), confirm: confirm, rows: rows, updatedAt: Date.now()
    };
    paintPlan();
    return plan;
  }

  function paintPlan() {
    var body = el('pp-rows'), foot = el('pp-foot');
    if (!plan || !plan.rows.length) {
      body.innerHTML = '<tr><td colspan="8">No plan built yet.</td></tr>';
      foot.innerHTML = '';
      setFigure('pp-balance', '—'); setFigure('pp-note', 'build a plan to begin');
      ['pp-total', 'pp-paid', 'pp-count', 'pp-next', 'pp-vs'].forEach(function (id) { setFigure(id, null); });
      el('pp-bar').style.width = '0%';
      el('ph-meta').textContent = '—';
      verdictFor('pp-verdict', 'pp-verdict-title', 'pp-verdict-body', 'No plan yet',
        'Pick a schedule on the left and press Build the dated plan.', '');
      return;
    }
    body.innerHTML = plan.rows.map(function (r, i) {
      var late = parseDate(r.date) && parseDate(val('pp-due')) && parseDate(r.date) > parseDate(val('pp-due'));
      return '<tr class="' + (r.paid ? 'is-paid' : '') + (late ? ' is-late' : '') + '">' +
        '<td class="mono">' + (i + 1) + '</td>' +
        '<td class="mono">' + label(parseDate(r.date)) + '</td>' +
        '<td>' + escapeHtml(r.label) + '</td>' +
        '<td class="num mono"' + (r.paid ? '' : ' style="font-weight:700"') + '>' + money(r.amount) + '</td>' +
        '<td class="num mono">' + money(r.cum) + '</td>' +
        '<td class="num mono">' + money(r.balance) + '</td>' +
        '<td class="mono tiny">' + label(parseDate(r.confirm)) + '</td>' +
        '<td><label class="pay"><input type="checkbox" data-paid="' + i + '"' + (r.paid ? ' checked' : '') +
        '> ' + (r.paid ? 'Paid — receipt kept' : 'Mark paid') + '</label></td></tr>';
    }).join('');
    var paid = plan.rows.reduce(function (a, r) { return a + (r.paid ? r.amount : 0); }, 0);
    var balance = Math.max(0, plan.bill - paid);
    foot.innerHTML = '<tr><td colspan="3">Total</td><td class="num">' + money(plan.bill) + '</td><td class="num">' +
      money(plan.rows[plan.rows.length - 1].cum) + '</td><td class="num">' + money(plan.rows[plan.rows.length - 1].balance) +
      '</td><td></td><td class="num">' + money(paid) + ' paid</td></tr>';

    setFigure('pp-balance', money(balance));
    setFigure('pp-note', balance > 0 ? 'of ' + money(plan.bill) + ' · ' + plan.name : 'the term is fully covered');
    setFigure('pp-total', money(plan.bill));
    setFigure('pp-paid', money(paid));
    setFigure('pp-count', plan.rows.filter(function (r) { return r.paid; }).length + ' of ' + plan.rows.length + ' made');
    var next = plan.rows.filter(function (r) { return !r.paid; })[0];
    setFigure('pp-next', next ? money(next.amount) + ' by ' + label(parseDate(next.date)) : 'nothing outstanding');
    var last = parseDate(plan.rows[plan.rows.length - 1].date), due = parseDate(plan.due);
    var diff = last && due ? daysBetween(due, last) : 0;
    setFigure('pp-vs', diff > 0 ? diff + ' days LATE' : (Math.abs(diff) + ' days before the deadline'));
    el('pp-bar').style.width = (plan.bill > 0 ? kit.clamp(paid / plan.bill * 100, 0, 100) : 0) + '%';
    el('ph-meta').textContent = plan.name + ' · ' + money(plan.bill) + ' · built ' + label(today());

    if (!next) {
      verdictFor('pp-verdict', 'pp-verdict-title', 'pp-verdict-body', 'Fully paid',
        'Every line on this plan is ticked. Ask the bursar for a written statement of account and keep it with the receipts.', 'ok');
    } else if (diff > 0) {
      verdictFor('pp-verdict', 'pp-verdict-title', 'pp-verdict-body', 'This schedule finishes ' + diff + ' days after the deadline',
        'Shorten the tail or move the first payment forward — the school only needs to see a date they can hold you to.', 'bad');
    } else {
      verdictFor('pp-verdict', 'pp-verdict-title', 'pp-verdict-body', 'Print it, take it to the bursar',
        money(next.amount) + ' next, by ' + label(parseDate(next.date)) + '. A dated plan the school has seen is worth far more than a promise on resumption day.', 'ok');
    }
  }

  /* saved plans -------------------------------------------------------- */
  function savedPlans() { return planStore.read() || []; }
  function paintSaved() {
    var list = savedPlans(), body = el('saved-body'), foot = el('saved-foot');
    el('saved-count').textContent = list.length + (list.length === 1 ? ' plan saved' : ' plans saved');
    el('saved-empty').hidden = list.length > 0;
    if (!list.length) { body.innerHTML = '<tr><td colspan="8">Nothing saved yet.</td></tr>'; foot.innerHTML = ''; return; }
    body.innerHTML = list.map(function (p) {
      var paid = p.rows.reduce(function (a, r) { return a + (r.paid ? r.amount : 0); }, 0);
      return '<tr><td><button class="pay-btn" type="button" data-load="' + p.id + '">' + escapeHtml(p.name) + '</button></td>' +
        '<td>' + escapeHtml(p.child || '—') + '</td>' +
        '<td class="num">' + money(p.bill) + '</td><td class="num">' + money(paid) + '</td>' +
        '<td class="num"><b>' + money(Math.max(0, p.bill - paid)) + '</b></td>' +
        '<td class="num">' + p.rows.filter(function (r) { return r.paid; }).length + '/' + p.rows.length + '</td>' +
        '<td class="tiny">' + label(new Date(p.savedAt)) + '</td>' +
        '<td><button class="row-del" type="button" data-delplan="' + p.id + '" aria-label="Delete plan ' + escapeHtml(p.name) + '">×</button></td></tr>';
    }).join('');
    var tb = list.reduce(function (a, p) { return a + p.bill; }, 0);
    var tp = list.reduce(function (a, p) { return a + p.rows.reduce(function (x, r) { return x + (r.paid ? r.amount : 0); }, 0); }, 0);
    foot.innerHTML = '<tr><td>All saved plans</td><td>' + list.length + '</td><td class="num">' + money(tb) + '</td><td class="num">' +
      money(tp) + '</td><td class="num">' + money(Math.max(0, tb - tp)) + '</td><td colspan="3"></td></tr>';
  }

  /* ------------------------------------------------------------------ *
   * 07 — letters
   ------------------------------------------------------------------ */
  var REASONS = {
    salary: 'My salary is credited after the school\'s deadline this term.',
    business: 'My business is in its slow season and the money lands in ' + kit.dateLabel(addDays(today(), 42)).replace(/,.*/, '') + '.',
    multiple: 'Three children resume in the same week and I would rather tell you now than default later.',
    medical: 'There has been an unexpected medical expense in the family.',
    bereavement: 'We have just had a bereavement in the family.',
    custom: ''
  };

  function letterFields() {
    return {
      parent: val('lt-parent') || 'Parent / guardian',
      phone: val('lt-phone') || '',
      school: val('lt-school') || 'the school',
      address: val('lt-address') || '',
      to: val('lt-to') || 'The Principal',
      child: val('lt-child') || 'my child',
      term: val('lt-term') || 'the coming term',
      total: numv('lt-total'),
      offer: numv('lt-offer'),
      first: parseDate(val('lt-first')) || addDays(today(), 7),
      rest: val('lt-rest') || '',
      reasonKey: val('lt-reason'),
      custom: val('lt-custom')
    };
  }

  function reasonLine(f) {
    return f.reasonKey === 'custom' ? (f.custom || 'Personal circumstances have delayed this payment.') : REASONS[f.reasonKey];
  }

  function scheduleLines(f) {
    if (plan && plan.rows.length) {
      return plan.rows.map(function (r) {
        return '   ' + label(parseDate(r.date)) + ' — ' + money(r.amount) + ' (' + r.label + ')';
      }).join('\n');
    }
    return '   ' + label(f.first) + ' — ' + money(f.offer) + ' (first payment)\n' + (f.rest ? '   Balance — ' + f.rest : '');
  }

  function requestLetter(f) {
    var balance = Math.max(0, f.total - f.offer);
    return f.parent + '\n' + (f.phone ? f.phone + '\n' : '') + label(today()) + '\n\n' +
      f.to + '\n' + f.school + '\n' + f.address + '\n\nDear Sir/Madam,\n\n' +
      'REQUEST FOR PERMISSION TO PAY SCHOOL FEES IN INSTALMENTS — ' + f.child.toUpperCase() + '\n\n' +
      'I write respectfully as the parent/guardian of ' + f.child + ' to ask whether the school will accept the ' +
      f.term + ' fees in instalments rather than in one payment.\n\n' +
      'The total fees for the term are ' + money(f.total) + '. I can pay ' + money(f.offer) + ' on ' + label(f.first) +
      ', which I will bring to the bursary with this letter. That leaves a balance of ' + money(balance) + ', which I propose to clear as follows:\n\n' +
      scheduleLines(f) + '\n\n' +
      'The reason is simple: ' + reasonLine(f) + '\n\n' +
      'I am not asking for a reduction or for the fees to be waived. I am asking for dates I can be held to. If the school ' +
      'would prefer a different shape to the schedule, or needs me to sign an undertaking, I will honour it.\n\n' +
      'I would be grateful for an acknowledgement in writing, and for a receipt for each payment. Thank you for considering my request.\n\n' +
      'Yours faithfully,\n\n' + f.parent + (f.phone ? '\n' + f.phone : '');
  }

  function reminderLetter(f) {
    var sent = addDays(f.first, -7);
    return f.parent + '\n' + (f.phone ? f.phone + '\n' : '') + label(today()) + '\n\n' +
      f.to + '\n' + f.school + '\n' + f.address + '\n\nDear Sir/Madam,\n\n' +
      'FOLLOW-UP: INSTALMENT REQUEST FOR ' + f.child.toUpperCase() + '\n\n' +
      'I wrote to you on ' + label(sent) + ' about the ' + f.term + ' fees of ' + money(f.total) +
      ' for ' + f.child + ', and offered a first payment of ' + money(f.offer) + ' on ' + label(f.first) + '.\n\n' +
      'I have not yet received an answer, and I am following up because I want the money to reach the school on the date I ' +
      'promised rather than after it. The ' + money(f.offer) + ' is set aside and ready.\n\n' +
      'Could you kindly let me know, by text or in writing, whether the schedule below is acceptable, or what the school ' +
      'would prefer instead?\n\n' + scheduleLines(f) + '\n\n' +
      'I am available on ' + (f.phone || 'the number above') + ' and can come to the school at any time that suits you. ' +
      'Thank you for your patience.\n\nYours faithfully,\n\n' + f.parent + (f.phone ? '\n' + f.phone : '');
  }

  function withheldSteps() {
    var f = letterFields();
    var canPayNow = f.offer > 0 ? f.offer : Math.round(f.total * 0.4);
    return [
      'Go to the school in person, the same day. Bring <b>' + money(canPayNow) + '</b> — the largest amount you can honestly raise in 24 hours — and offer it on the desk. A part payment with a receipt changes the conversation from "you owe" to "you are paying".',
      'Ask to see the school\'s <b>fees and examinations policy in writing</b>, and the exact figure the child is withheld over. Note the name of the person you spoke to and the time.',
      'Hand over the <b>instalment request letter</b> above (section 07) and ask for it to be signed or stamped as received. Keep your copy. If they will not accept it, send it as a text or an email so there is a dated record.',
      'Ask for the <b>bursar, not only the class teacher</b>. Teachers are often instructed to enforce; bursars can usually negotiate dates.',
      'Bring the <b>PTA chair or your circle/welfare association</b>. A group request in writing is very rarely ignored, and it protects you from being singled out.',
      'If the child is already home, <b>keep them learning at home</b> and write to the school stating that you are not withholding the child, the school is. Photograph any letter you send.',
      'Escalate in this order: proprietor/director of the school, then the <b>local government education secretary</b>, then the <b>state Ministry of Education / SUBEB</b> complaints desk. Take the receipts and the dated letters.',
      'Return to section 06 the same week and rebuild the plan so this deadline cannot arrive unannounced again. The panic is almost never the money — it is the surprise.'
    ];
  }

  function paintLetters() {
    var f = letterFields();
    setFigure('letter-out', requestLetter(f));
    setFigure('letter-rem', reminderLetter(f));
    el('wh-steps').innerHTML = withheldSteps().map(function (s) { return '<li>' + s + '</li>'; }).join('');
  }

  function copyLetter() {
    var panels = kit.$$('#letters [role="tabpanel"]').filter(function (p) { return !p.hidden; });
    var pre = panels.length ? panels[0].querySelector('pre') : null;
    if (!pre) { kit.toast('Open the request or reminder tab to copy a letter'); return; }
    var text = pre.textContent;
    var done = function () { kit.toast('Letter copied — paste it into a text message or an email'); };
    if (navigator.clipboard && navigator.clipboard.writeText) {
      navigator.clipboard.writeText(text).then(done, function () { fallbackCopy(text, done); });
    } else { fallbackCopy(text, done); }
  }

  function fallbackCopy(text, done) {
    var ta = document.createElement('textarea');
    ta.value = text;
    ta.setAttribute('readonly', 'readonly');
    ta.style.position = 'fixed';
    ta.style.left = '-1200px';
    document.body.appendChild(ta);
    ta.select();
    var ok = false;
    try { ok = document.execCommand('copy'); } catch (e) { ok = false; }
    ta.remove();
    if (ok) done(); else kit.toast('Your browser blocked copying — select the text and copy it manually');
  }

  /* ------------------------------------------------------------------ *
   * 08 — circle / PTA ledger
   ------------------------------------------------------------------ */
  function sanitiseCircle(c) {
    var base = {
      name: 'JSS Block — Term 2 fee circle', child: 'Tolu Adeyemi, JSS 2B', target: 240000,
      payout: iso(addDays(today(), 70)), freq: 'weekly', cycles: 10, current: 1,
      members: [], entries: []
    };
    if (!c) return base;
    base.name = c.name || base.name; base.child = c.child || base.child;
    base.target = Number(c.target) || base.target; base.payout = c.payout || base.payout;
    base.freq = c.freq === 'monthly' ? 'monthly' : 'weekly';
    base.cycles = Number(c.cycles) || base.cycles; base.current = Math.max(1, Number(c.current) || 1);
    base.members = (c.members || []).map(function (m) {
      return { id: m.id || kit.uid('mem'), name: String(m.name || 'Member'), amount: Number(m.amount) || 0 };
    });
    base.entries = (c.entries || []).map(function (e) {
      return { id: e.id || kit.uid('ent'), memberId: e.memberId, name: String(e.name || ''), amount: Number(e.amount) || 0, cycle: Number(e.cycle) || 1, date: e.date || iso(today()) };
    });
    return base;
  }

  function circleRead() {
    circle.name = val('ci-name') || circle.name;
    circle.child = val('ci-child') || circle.child;
    circle.target = numv('ci-target');
    circle.payout = val('ci-payout') || circle.payout;
    circle.freq = val('ci-freq') || 'weekly';
    circle.cycles = kit.clamp(Math.round(numv('ci-weeks')), 1, 60);
  }

  function paidBy(id) {
    return circle.entries.reduce(function (a, e) { return a + (e.memberId === id ? e.amount : 0); }, 0);
  }

  function paintCircle() {
    var total = circle.entries.reduce(function (a, e) { return a + e.amount; }, 0);
    var pot = circle.members.reduce(function (a, m) { return a + m.amount; }, 0);
    var perCycleNeed = circle.members.length ? circle.target / Math.max(1, circle.cycles) / circle.members.length : 0;

    el('ci-members').innerHTML = circle.members.length ? circle.members.map(function (m, i) {
      var paid = paidBy(m.id);
      var expected = m.amount * circle.current;
      var owed = Math.max(0, expected - paid);
      return '<tr class="' + (owed > 0 ? 'member-row is-dues' : 'member-row') + '">' +
        '<td>' + escapeHtml(m.name) + '</td>' +
        '<td class="num"><input type="number" min="0" step="500" value="' + m.amount + '" data-memamt="' + i + '" aria-label="Amount ' + escapeHtml(m.name) + ' pays each cycle"></td>' +
        '<td class="num">' + money(paid) + '</td>' +
        '<td class="num">' + (owed > 0 ? '<span class="bad-num">' + money(owed) + '</span>' : '—') + '</td>' +
        '<td class="num mono tiny">' + cycleCountOf(m.id) + '/' + circle.current + '</td>' +
        '<td class="row-btns"><button class="pay-btn" type="button" data-pay="' + m.id + '">Record ' + money(m.amount) + '</button> ' +
        '<button class="kid-del" type="button" data-delmem="' + m.id + '" aria-label="Remove ' + escapeHtml(m.name) + '">×</button></td></tr>';
    }).join('') : '<tr><td colspan="6">No contributors yet.</td></tr>';

    el('ci-owe').innerHTML = circle.members.length ? circle.members.map(function (m) {
      var paid = paidBy(m.id), expected = m.amount * circle.current, owed = Math.max(0, expected - paid);
      return '<tr><td>' + escapeHtml(m.name) + '</td><td class="num">' + money(expected) + '</td><td class="num">' + money(paid) +
        '</td><td class="num">' + (owed > 0 ? '<b>' + money(owed) + '</b>' : '—') + '</td><td>' +
        (owed > 0 ? '<span class="tag-feasible tag-no">Behind</span>' : '<span class="tag-feasible tag-yes">Up to date</span>') + '</td></tr>';
    }).join('') : '<tr><td colspan="5">Add contributors to build the arrears list.</td></tr>';

    var sorted = circle.entries.slice().sort(function (a, b) { return a.date < b.date ? -1 : a.date > b.date ? 1 : 0; });
    var running = 0;
    el('ci-ledger').innerHTML = sorted.length ? sorted.map(function (e, i) {
      running += e.amount;
      return '<tr><td class="mono">' + (i + 1) + '</td><td class="mono">' + label(parseDate(e.date)) + '</td>' +
        '<td>' + escapeHtml(e.name) + '</td><td class="mono">Cycle ' + e.cycle + '</td>' +
        '<td class="num">' + money(e.amount) + '</td><td class="num"><b>' + money(running) + '</b></td>' +
        '<td class="num">' + money(Math.max(0, circle.target - running)) + '</td></tr>';
    }).join('') : '';
    el('ci-ledger-empty').hidden = sorted.length > 0;

    var pctDone = circle.target > 0 ? kit.clamp(total / circle.target * 100, 0, 100) : 0;
    el('ci-bar').style.width = pctDone + '%';
    var arrears = circle.members.filter(function (m) { return m.amount * circle.current - paidBy(m.id) > 0; });
    var owedTotal = arrears.reduce(function (a, m) { return a + Math.max(0, m.amount * circle.current - paidBy(m.id)); }, 0);
    var expectedByNow = pot * circle.current;
    setFigure('ci-total', money(total));
    setFigure('ci-note', kit.pct(pctDone, 0) + ' of the ' + money(circle.target) + ' term target');
    setFigure('ci-m', String(circle.members.length));
    setFigure('ci-pot', pot ? money(pot) : '—');
    setFigure('ci-cycles', pot ? Math.ceil(circle.target / pot) + ' of ' + circle.cycles : '—');
    setFigure('ci-arrears', arrears.length ? arrears.length + ' member' + (arrears.length > 1 ? 's' : '') + ' · ' + money(owedTotal) : 'none');
    setFigure('ci-entries', String(circle.entries.length));
    setFigure('ci-gaps', String(arrears.length));
    setFigure('ci-when', label(parseDate(circle.payout)));
    setFigure('ci-suggest', 'Suggested ' + (circle.freq === 'monthly' ? 'monthly' : 'weekly') +
      ' amount per contributor to hit the target in ' + circle.cycles + ' cycles: ' +
      (circle.members.length ? money(perCycleNeed) : 'add members first') + (pot ? ' · current pot ' + money(pot) + ' a cycle' : ''));

    if (!circle.members.length) {
      verdictFor('ci-verdict', 'ci-verdict-title', 'ci-verdict-body', 'No circle yet',
        'Add each contributor and record their payments as they arrive.', '');
    } else if (total >= circle.target) {
      verdictFor('ci-verdict', 'ci-verdict-title', 'ci-verdict-body', 'Target reached — pay out',
        money(total) + ' collected for ' + circle.child + '. Hand it over against a signed acknowledgement and post the CSV to the group so the record stays public.', 'ok');
    } else if (total < expectedByNow) {
      verdictFor('ci-verdict', 'ci-verdict-title', 'ci-verdict-body',
        'Behind schedule by ' + money(expectedByNow - total),
        'After ' + circle.current + ' cycle' + (circle.current > 1 ? 's' : '') + ' the circle should have ' + money(expectedByNow) +
        ' in hand and has ' + money(total) + '. ' + (owedTotal > 0 ? money(owedTotal) + ' of that is sitting with ' + arrears.length +
        ' member' + (arrears.length > 1 ? 's' : '') + ' — read the arrears list out at the meeting.' : 'Everyone has paid, so the contribution is simply too small: raise it or extend the cycles.'), 'warn');
    } else {
      var toGo = Math.max(0, circle.target - total);
      verdictFor('ci-verdict', 'ci-verdict-title', 'ci-verdict-body',
        money(toGo) + ' still to raise, on schedule',
        'The circle is putting ' + money(pot) + ' together per ' + (circle.freq === 'monthly' ? 'month' : 'week') +
        '. ' + (owedTotal > 0 ? money(owedTotal) + ' is owed by ' + arrears.length + ' member' + (arrears.length > 1 ? 's' : '') +
          ' — a circle dies one apologetic "next week" at a time, so keep the list public.'
          : 'Every member is up to date. Keep the ledger open at every handover and export the CSV before each payout.'),
        owedTotal > 0 ? 'warn' : 'ok');
    }
    circleStore.write(circle);
  }

  function cycleCountOf(id) {
    var seen = {};
    circle.entries.forEach(function (e) { if (e.memberId === id) seen[e.cycle] = 1; });
    return Object.keys(seen).length;
  }

  /* ------------------------------------------------------------------ *
   * 09 — worked example
   ------------------------------------------------------------------ */
  function paintWorked() {
    var s = sinkingModel();
    var m = optionModel();
    var years = kit.clamp(numv('hz-years') || 6, 1, 12);
    var rate = kit.clamp(numv('hz-rate'), 0, 60) / 100;
    var bill = s.bill || 240000;
    var steps = [
      'The bill is <b>' + money(bill) + '</b> and the term begins <b>' + label(s.due) + '</b> — that is <b>' + s.months.toFixed(1) +
      ' months</b> (' + s.daysLeft + ' days) from today.',
      'Plain division: ' + money(bill) + ' ÷ ' + Math.max(1, Math.ceil(s.months - 0.001)) + ' months = <b>' +
      money(bill / Math.max(1, Math.ceil(s.months - 0.001))) + ' a month</b>. ' +
      (s.rate > 0 ? 'With ' + kit.pct(s.rate, 1) + ' a year earned on the fund, the deposit falls to <b>' + money(s.deposit) + '</b>.'
        : 'With the fund paying nothing, the deposit stays at <b>' + money(s.deposit) + '</b>.'),
      'For a daily or weekly income: ' + money(bill) + ' ÷ ' + (s.daysLeft / 7).toFixed(1) + ' weeks = <b>' + money(s.weekly) + ' a week</b>, about <b>' +
      money(s.weekly / 6) + '</b> a working day.',
      'If you can only commit ' + money(s.afford) + ' a month you reach <b>' + kit.pct(s.coverPct, 0) + '</b> of the bill by the due date, leaving <b>' +
      money(s.shortfall) + '</b> — which is <b>' + s.weeksUnpaid.toFixed(1) + ' of the ' + s.weeksTerm + ' teaching weeks</b> unfunded. That is the number to negotiate.',
      'The advance: <b>₦' + kit.amount(m.A) + '</b> for 30 days at <b>' + kit.pct(m.r * 100, 0) + '</b> costs <b>' + money(m.oneMonth) +
      '</b> and you repay <b>' + money(m.A + m.oneMonth) + '</b>.',
      'Annualised honestly: (1 + ' + m.r.toFixed(2) + ')<sup>12</sup> − 1 = <b>' + kit.pct(m.compounded * 100, 0) + ' a year</b> if you roll it every month' +
      ' (the simple ×12 figure is ' + kit.pct(m.flat * 100, 0) + '). Cleared over ' + m.k + ' months instead, it costs <b>' + money(m.A * m.r * (m.k + 1) / 2) + '</b>.',
      'The same ' + money(bill) + ' through the school\'s own instalment plan at ' + kit.pct(m.s * 100, 1) + ' costs <b>' + money(bill * m.s) + '</b>. The ranking, and the winner, is in section 05: <b>' +
      (m.winner ? escapeHtml(m.winner.name.replace(/^[a-e] — /, '')) : '—') + '</b>.',
      'The circle: ' + m.N + ' members × ' + money(m.weekly) + ' a week puts the whole ' + money(bill) +
      ' together in one pot. It charges no interest. Your turn is week ' + m.turn + ' of ' + m.weeksToDue + ' — covered on the day <b>' +
      kit.pct(m.coverage * 100, 0) + '</b> of the time, <b>' + kit.pct(m.withBuffer * 100, 0) + '</b> with one buffer week.',
      'And the horizon: at ' + kit.pct(rate * 100, 0) + ' a year, ' + money(householdYear() || bill) +
      ' this year becomes <b>' + money((householdYear() || bill) * Math.pow(1 + rate, years - 1)) + '</b> in ' +
      (new Date().getFullYear() + years - 1) + '.'
    ];
    el('worked').innerHTML = steps.map(function (t) { return '<li>' + t + '</li>'; }).join('');
  }

  /* ------------------------------------------------------------------ *
   * paint orchestration
   ------------------------------------------------------------------ */
  var painting = false;
  function paint(opts) {
    if (painting) return;
    painting = true;
    try {
      paintKids();
      paintPlanner();
      paintHorizon();
      paintSinking();
      paintOptions();
      paintWorked();
      paintLetters();
      if (plan) paintPlan();
      if (opts && opts.soft) { /* keep paid flags */ }
    } finally { painting = false; }
  }

  function listenInputs(root, handler) {
    if (!root) return;
    ['input', 'change'].forEach(function (evt) {
      root.addEventListener(evt, function (e) {
        if (e.target.matches('input, select, textarea')) handler(e);
      });
    });
  }

  function readKidForm(target) {
    var i = Number(target.dataset.kid);
    var kid = kids[i];
    if (!kid) return false;
    if (target.dataset.extra) {
      kid.ex[target.dataset.extra] = Math.max(0, kit.num(target.value));
      kid.custom = true;
    } else if (target.dataset.field === 'band') {
      kid.band = target.value;
      if (!kid.custom) applyBand(kid);
    } else if (target.dataset.field === 'terms') {
      kid.terms = Number(target.value) === 2 ? 2 : 3;
      kid.custom = true;
    } else if (target.dataset.field === 'fees') {
      kid.fees = Math.max(0, kit.num(target.value));
      kid.custom = true;
    } else if (target.dataset.field) {
      kid[target.dataset.field] = target.value;
    } else return false;
    return true;
  }

  /* ------------------------------------------------------------------ *
   * wiring
   ------------------------------------------------------------------ */
  function wire() {
    /* children */
    listenInputs(el('kids'), function (e) {
      if (!readKidForm(e.target)) return;
      var focusId = e.target.id;
      var isText = e.target.type === 'text' || e.target.tagName === 'TEXTAREA';
      var caret = isText ? e.target.selectionStart : null;
      paint();
      var next = document.getElementById(focusId);
      if (next && next !== e.target) {
        next.focus();
        if (caret !== null) { try { next.setSelectionRange(caret, caret); } catch (err) {} }
      }
    });
    kit.on(el('kids'), 'click', '[data-delkid]', function (e, t) {
      var i = Number(t.dataset.delkid);
      var removed = kids.splice(i, 1)[0];
      paint();
      kit.toast((removed && removed.name ? removed.name : 'Child') + ' removed');
    });
    kit.on(el('kids'), 'click', '[data-reband]', function (e, t) {
      var kid = kids[Number(t.dataset.reband)];
      if (!kid) return;
      kid.custom = false;
      applyBand(kid);
      paint();
      kit.toast(kid.name + ' reset to the ' + bandByKey(kid.band).name + ' band');
    });

    el('add-child').addEventListener('click', function () {
      kids.push(newKid('standard', 'JSS 1', 'Child ' + (kids.length + 1)));
      paint();
      kit.toast('Child added — pick the school band and edit the figures');
      var card = kit.$$('#kids .kid-card').pop();
      if (card) card.scrollIntoView({ block: 'nearest', behavior: kit.reduce ? 'auto' : 'smooth' });
    });
    el('band-preset').addEventListener('change', function (e) {
      if (!e.target.value) return;
      kids.push(newKid(e.target.value, 'JSS 1', 'Child ' + (kids.length + 1)));
      e.target.value = '';
      paint();
      kit.toast('Added at the ' + bandByKey(kids[kids.length - 1].band).name + ' band');
    });

    kit.on(document, 'click', '[data-persona]', function (e, t) {
      if (t.dataset.persona === 'clear') {
        kids = [];
        paint();
        kit.toast('Children cleared');
        return;
      }
      var p = PERSONAS[t.dataset.persona];
      if (!p) return;
      kids = p.kids.map(function (k) { return newKid(k.band, k.cls, k.name); });
      paint();
      if (p.months) {
        el('sf-due').value = iso(addMonths(today(), Math.floor(p.months)) );
        if (p.months < 2) el('sf-due').value = iso(addDays(today(), Math.round(p.months * 30.44)));
        el('op-months').value = p.months;
        el('sf-afford').value = p.afford;
        el('pp-due').value = el('sf-due').value;
        el('pp-start').value = iso(today());
        syncBill();
        buildPlan(false);
      }
      kit.toast(p.label);
    });

    function syncBill() {
      var term = Math.round(householdTerm());
      if (!term) { kit.toast('Add a child first'); return; }
      ['sf-bill', 'op-bill', 'pp-bill', 'ci-target'].forEach(function (id) { el(id).value = term; });
      el('lt-total').value = term;
      var offer = Math.round(term * 0.4);
      el('lt-offer').value = offer;
      el('lt-rest').value = '₦' + kit.amount(Math.round((term - offer) / 2)) + ' by the middle of the term and ₦' +
        kit.amount(term - offer - Math.round((term - offer) / 2)) + ' before the examinations';
      paint();
      kit.toast('Household term total ' + money(term) + ' sent to the planner sections');
    }
    el('sync-bill').addEventListener('click', syncBill);

    listenInputs(el('sf-form'), function () {
      var bill = numv('sf-bill');
      if (bill > 0) el('op-bill').value = bill;
      paint();
    });
    el('sf-form').addEventListener('reset', function () {
      setTimeout(function () { seedDates(); paint(); }, 0);
    });
    listenInputs(el('opt-form'), paint);
    listenInputs(el('hz-bars').closest('section'), paint);

    /* plan builder */
    listenInputs(el('pp-form'), function (e) {
      if (e.target.dataset && e.target.dataset.paid !== undefined) return;
      buildPlan(true);
    });
    el('pp-build').addEventListener('click', function () { buildPlan(true); kit.toast('Dated plan built'); });
    kit.on(el('pp-rows'), 'change', '[data-paid]', function (e, t) {
      var i = Number(t.dataset.paid);
      if (!plan || !plan.rows[i]) return;
      plan.rows[i].paid = t.checked;
      paintPlan();
      kit.toast(t.checked ? 'Recorded ' + money(plan.rows[i].amount) + ' — keep the receipt' : 'Payment unticked');
    });
    el('pp-print').addEventListener('click', function () {
      if (!plan) buildPlan(false);
      printAs('print-plan');
    });
    el('pp-save').addEventListener('click', function () {
      if (!plan) buildPlan(false);
      var list = savedPlans();
      var row = {
        id: kit.uid('plan'), name: plan.name, child: kids.length ? kids.map(function (k) { return k.name; }).join(', ') : '—',
        bill: plan.bill, schedule: plan.schedule, rows: plan.rows.map(function (r) {
          return { date: r.date, label: r.label, amount: r.amount, cum: r.cum, balance: r.balance, confirm: r.confirm, paid: !!r.paid };
        }), savedAt: Date.now()
      };
      var existing = list.filter(function (p) { return p.name !== row.name; });
      existing.push(row);
      if (!planStore.write(existing)) { kit.toast('This browser blocked saving — export the CSV instead'); return; }
      paintSaved();
      kit.toast('Plan "' + row.name + '" saved in this browser');
    });
    kit.on(el('saved-body'), 'click', '[data-delplan]', function (e, t) {
      var list = savedPlans().filter(function (p) { return p.id !== t.dataset.delplan; });
      planStore.write(list);
      paintSaved();
      kit.toast('Plan deleted');
    });
    kit.on(el('saved-body'), 'click', '[data-load]', function (e, t) {
      var p = savedPlans().filter(function (x) { return x.id === t.dataset.load; })[0];
      if (!p) return;
      el('pp-name').value = p.name; el('pp-bill').value = p.bill;
      el('pp-schedule').value = p.schedule; el('pp-start').value = p.rows[0] ? p.rows[0].date : iso(today());
      var last = p.rows[p.rows.length - 1];
      el('pp-due').value = last ? last.date : iso(addMonths(today(), 1));
      plan = { name: p.name, bill: p.bill, schedule: p.schedule, start: el('pp-start').value, due: el('pp-due').value, confirm: 5, rows: p.rows.slice() };
      paintPlan();
      kit.toast('Loaded "' + p.name + '"');
      el('plan').scrollIntoView({ block: 'start', behavior: kit.reduce ? 'auto' : 'smooth' });
    });
    el('saved-csv').addEventListener('click', function () {
      var list = savedPlans();
      if (!list.length) { kit.toast('No saved plans to export'); return; }
      var rows = [['Plan', 'Child / class', 'Payment', 'Due date', 'Confirm by', 'Amount (NGN)', 'Cumulative (NGN)', 'Balance (NGN)', 'Status']];
      list.forEach(function (p) {
        p.rows.forEach(function (r) {
          rows.push([p.name, p.child, r.label, r.date, r.confirm, round(r.amount), round(r.cum), round(r.balance), r.paid ? 'PAID' : 'unpaid']);
        });
      });
      kit.csv(rows, 'feesplan-payment-plans-' + iso(today()) + '.csv');
      kit.toast('Exported ' + list.length + ' plan' + (list.length > 1 ? 's' : ''));
    });
    el('saved-clear').addEventListener('click', function () {
      if (!savedPlans().length) { kit.toast('Nothing saved yet'); return; }
      planStore.clear();
      paintSaved();
      kit.toast('Saved plans cleared');
    });

    /* horizon csv */
    el('hz-csv').addEventListener('click', function () {
      if (!kids.length) { kit.toast('Add a child to build the funding table'); return; }
      var rows = horizon();
      var head = ['Year'].concat(kids.map(function (k, i) { return (k.name || 'Child ' + (i + 1)) + ' (NGN)'; })).concat(['Household total (NGN)', 'Monthly (NGN)', 'Advance 40% (NGN)', 'Balance 60% (NGN)']);
      var body = rows.map(function (r) {
        return [r.year].concat(r.perKid.map(round)).concat([round(r.total), round(r.total / 12), round(r.total * 0.4 / 3), round(r.total * 0.6 / 3)]);
      });
      kit.csv([head].concat(body), 'feesplan-6-year-funding-' + kit.pct(numv('hz-rate'), 0).replace('.', '-') + '.csv');
      kit.toast('Funding table exported');
    });

    /* letters */
    listenInputs(el('lt-form'), paintLetters);
    el('lt-copy').addEventListener('click', copyLetter);
    el('lt-print').addEventListener('click', function () { printAs('print-letter'); });
    el('lt-fill').addEventListener('click', function () {
      var term = Math.round(householdTerm());
      if (term) {
        el('lt-total').value = term;
        el('lt-offer').value = Math.round(term * 0.4);
        el('lt-rest').value = '₦' + kit.amount(Math.round(term * 0.3)) + ' by the middle of the term and ₦' +
          kit.amount(Math.round(term * 0.3)) + ' before the examinations';
      }
      if (kids[0]) el('lt-child').value = (kids[0].name || 'Child') + ' — ' + kids[0].cls;
      paintLetters();
      kit.toast('Amounts pulled from the planner');
    });

    /* circle */
    listenInputs(el('ci-form'), function (e) {
      if (e.target.dataset && e.target.dataset.memamt !== undefined) {
        circle.members[Number(e.target.dataset.memamt)].amount = Math.max(0, kit.num(e.target.value));
      } else circleRead();
      paintCircle();
    });
    el('ci-add').addEventListener('click', function () {
      var name = (val('ci-member') || '').trim();
      if (!name) { kit.toast('Type a contributor name first'); el('ci-member').focus(); return; }
      var amount = numv('ci-amt') || (circle.members.length ? 0 : 0);
      if (!amount) {
        var potNeed = circle.target / Math.max(1, circle.cycles * Math.max(1, circle.members.length + 1));
        amount = Math.ceil(potNeed / 100) * 100;
      }
      circle.members.push({ id: kit.uid('mem'), name: name, amount: amount });
      el('ci-member').value = ''; el('ci-amt').value = '';
      circleRead();
      paintCircle();
      kit.toast(name + ' added at ' + money(amount) + ' a cycle');
    });
    kit.on(el('ci-members'), 'click', '[data-pay]', function (e, t) {
      var m = circle.members.filter(function (x) { return x.id === t.dataset.pay; })[0];
      if (!m) return;
      circle.entries.push({ id: kit.uid('ent'), memberId: m.id, name: m.name, amount: m.amount, cycle: circle.current, date: iso(today()) });
      paintCircle();
      kit.toast('Recorded ' + money(m.amount) + ' from ' + m.name + ' (cycle ' + circle.current + ')');
    });
    kit.on(el('ci-members'), 'click', '[data-delmem]', function (e, t) {
      var id = t.dataset.delmem;
      var m = circle.members.filter(function (x) { return x.id === id; })[0];
      circle.members = circle.members.filter(function (x) { return x.id !== id; });
      circle.entries = circle.entries.filter(function (x) { return x.memberId !== id; });
      paintCircle();
      kit.toast((m ? m.name : 'Member') + ' and their entries removed');
    });
    var cycleBtn = document.createElement('button');
    cycleBtn.className = 'btn btn-ghost btn-sm';
    cycleBtn.type = 'button';
    cycleBtn.id = 'ci-cycle';
    cycleBtn.textContent = 'Close this cycle →';
    el('ci-form').querySelector('.row.ci-add').parentNode.appendChild(cycleBtn);
    cycleBtn.addEventListener('click', function () {
      circle.current += 1;
      paintCircle();
      kit.toast('Cycle ' + (circle.current - 1) + ' closed. Now at cycle ' + circle.current + '.');
    });
    el('ci-csv').addEventListener('click', function () {
      if (!circle.entries.length) { kit.toast('Nothing recorded yet'); return; }
      circleRead();
      var running = 0;
      var rows = [['Circle', 'For', 'Term target (NGN)', 'Payout date'],
        [circle.name, circle.child, round(circle.target), circle.payout], [],
        ['Entry', 'Date', 'Member', 'Cycle', 'Amount (NGN)', 'Running total (NGN)', 'Balance to target (NGN)']];
      circle.entries.slice().sort(function (a, b) { return a.date < b.date ? -1 : 1; }).forEach(function (e, i) {
        running += e.amount;
        rows.push([i + 1, e.date, e.name, 'Cycle ' + e.cycle, round(e.amount), round(running), round(Math.max(0, circle.target - running))]);
      });
      rows.push([]);
      rows.push(['ARREARS']);
      circle.members.forEach(function (m) {
        var owed = Math.max(0, m.amount * circle.current - paidBy(m.id));
        rows.push(['', iso(today()), m.name, 'cycle ' + circle.current + ' expected', round(m.amount * circle.current), round(paidBy(m.id)), round(owed)]);
      });
      kit.csv(rows, 'feesplan-circle-ledger-' + kit.slug(circle.name) + '-' + iso(today()) + '.csv');
      kit.toast('Transparency export downloaded');
    });
    el('ci-print').addEventListener('click', function () { printAs('print-ledger'); });
    el('ci-reset').addEventListener('click', function () {
      circle = sanitiseCircle(null);
      circleStore.clear();
      el('ci-name').value = circle.name; el('ci-child').value = circle.child;
      el('ci-target').value = circle.target; el('ci-payout').value = circle.payout;
      paintCircle();
      kit.toast('Circle reset');
    });

    /* editable bands */
    paintBandTable();
    kit.on(el('band-body'), 'input', 'input', function (e, t) {
      var i = Number(t.closest('tr').dataset.band);
      var field = t.dataset.bfield;
      var b = bands[i];
      if (!b) return;
      var v = Math.max(0, kit.num(t.value));
      if (field === 'fees') b.fees = v;
      else if (field === 'terms') b.terms = v === 2 ? 2 : 3;
      else if (field === 'termly') {
        var termKeys = EXTRAS.filter(function (x) { return x.when === 'term'; }).map(function (x) { return x.k; });
        var oldSum = termKeys.reduce(function (a, k) { return a + (b.ex[k] || 0); }, 0) || 1;
        var scale = v / oldSum;
        termKeys.forEach(function (k) { b.ex[k] = Math.round((b.ex[k] || 0) * scale); });
      } else if (field === 'yearly') {
        var yearKeys = EXTRAS.filter(function (x) { return x.when === 'year'; }).map(function (x) { return x.k; });
        var oy = yearKeys.reduce(function (a, k) { return a + (b.ex[k] || 0); }, 0) || 1;
        var sc = v / oy;
        yearKeys.forEach(function (k) { b.ex[k] = Math.round((b.ex[k] || 0) * sc); });
      }
      bandStore.write(bands);
      kids.forEach(function (k) { if (!k.custom && k.band === b.key) applyBand(k); });
      paintPresets();
      paint();
    });
    el('reset-bands').addEventListener('click', function () {
      bands = DEFAULT_BANDS.map(function (b) { return JSON.parse(JSON.stringify(b)); });
      bandStore.write(bands);
      kids.forEach(function (k) { if (!k.custom) applyBand(k); });
      paintBandTable();
      paintPresets();
      paint();
      kit.toast('Default bands restored');
    });

    ['add-child', 'band-preset'].forEach(function (id) { el(id).addEventListener('keydown', function (e) { if (e.key === 'Enter') e.preventDefault(); }); });
  }

  function paintBandTable() {
    el('band-body').innerHTML = bands.map(function (b, i) {
      var termly = EXTRAS.filter(function (e) { return e.when === 'term'; }).reduce(function (a, e) { return a + (b.ex[e.k] || 0); }, 0);
      var yearly = EXTRAS.filter(function (e) { return e.when === 'year'; }).reduce(function (a, e) { return a + (b.ex[e.k] || 0); }, 0);
      return '<tr data-band="' + i + '"><td>' + escapeHtml(b.name) + '</td>' +
        '<td class="num"><input type="number" min="0" step="5000" value="' + b.fees + '" data-bfield="fees" aria-label="' + escapeHtml(b.name) + ' fees per term"></td>' +
        '<td class="num"><input type="number" min="0" step="1000" value="' + termly + '" data-bfield="termly" aria-label="' + escapeHtml(b.name) + ' termly extras total"></td>' +
        '<td class="num"><input type="number" min="0" step="1000" value="' + yearly + '" data-bfield="yearly" aria-label="' + escapeHtml(b.name) + ' yearly extras total"></td>' +
        '<td class="num"><input type="number" min="2" max="3" step="1" value="' + b.terms + '" data-bfield="terms" aria-label="' + escapeHtml(b.name) + ' terms per year"></td></tr>';
    }).join('');
  }

  function printAs(mode) {
    document.body.classList.add(mode);
    var clean = function () { document.body.classList.remove(mode); window.removeEventListener('afterprint', clean); };
    window.addEventListener('afterprint', clean);
    kit.print();
    setTimeout(clean, 4000);
  }

  function seedDates() {
    if (!el('sf-due').value) el('sf-due').value = iso(addMonths(today(), 8));
    if (!el('pp-due').value) el('pp-due').value = iso(addMonths(today(), 8));
    if (!el('pp-start').value) el('pp-start').value = iso(addDays(today(), 21));
    if (!el('lt-first').value) el('lt-first').value = iso(addDays(today(), 7));
    if (!el('ci-payout').value) el('ci-payout').value = iso(addDays(today(), 70));
  }

  /* ------------------------------------------------------------------ *
   * boot
   ------------------------------------------------------------------ */
  kit.boot({
    ready: function () {
      seedDates();
      if (!el('pp-name').value) el('pp-name').value = kids.length ? (kids[0].name || 'Child') + ' — ' + kids[0].cls + ', next term' : 'Next term — household plan';
      ['ci-name', 'ci-child', 'ci-target', 'ci-payout', 'ci-freq', 'ci-weeks'].forEach(function (id) {
        var node = el(id);
        if (!node) return;
        if (id === 'ci-name') node.value = circle.name;
        if (id === 'ci-child') node.value = circle.child;
        if (id === 'ci-target') node.value = circle.target;
        if (id === 'ci-payout') node.value = circle.payout;
        if (id === 'ci-freq') node.value = circle.freq;
        if (id === 'ci-weeks') node.value = circle.cycles;
      });
      if (!circle.members.length) {
        ['Auntie Funke', 'Mama Tunde', 'Mr. Bello', 'Mrs. Nwosu', 'Papa Chidi']
          .forEach(function (n, i) { circle.members.push({ id: kit.uid('mem'), name: n, amount: [30000, 25000, 20000, 40000, 25000][i] }); });
        circle.entries.push({ id: kit.uid('ent'), memberId: circle.members[0].id, name: circle.members[0].name, amount: circle.members[0].amount, cycle: 1, date: iso(addDays(today(), -7)) });
        circle.entries.push({ id: kit.uid('ent'), memberId: circle.members[1].id, name: circle.members[1].name, amount: circle.members[1].amount, cycle: 1, date: iso(addDays(today(), -7)) });
      }
      paintPresets();
      wire();
      paint();
      buildPlan(false);
      paintSaved();
      paintCircle();
    }
  });
})();
