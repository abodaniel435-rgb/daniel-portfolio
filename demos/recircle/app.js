/* ReCircle — waste value engine.
   Everything (prices, weights, trip maths, ledger, group shares) runs in this browser.
   Nothing is booked, collected or paid: this page prepares records and numbers only. */
(function () {
  'use strict';

  /* ---------------- reference data (all editable by the user) ---------------- */

  var DEFAULT_PRICES = [
    { id: 'pet-clear', name: 'PET bottles — clear', unit: 'kg', price: 120, contam: 4, min: 2 },
    { id: 'pet-colour', name: 'PET bottles — coloured', unit: 'kg', price: 80, contam: 5, min: 2 },
    { id: 'hdpe', name: 'HDPE (jerrycan / bottle)', unit: 'kg', price: 200, contam: 6, min: 2 },
    { id: 'plastic-mixed', name: 'Mixed plastic', unit: 'kg', price: 45, contam: 10, min: 5 },
    { id: 'sachet', name: 'Soft plastic / water sachets', unit: 'kg', price: 15, contam: 14, min: 5 },
    { id: 'carton', name: 'Carton', unit: 'kg', price: 35, contam: 8, min: 5 },
    { id: 'paper-mixed', name: 'Mixed paper', unit: 'kg', price: 45, contam: 9, min: 5 },
    { id: 'newsprint', name: 'Newsprint', unit: 'kg', price: 60, contam: 6, min: 3 },
    { id: 'alu', name: 'Aluminium cans', unit: 'kg', price: 300, contam: 3, min: 0.5 },
    { id: 'iron', name: 'Scrap iron / tin cans', unit: 'kg', price: 45, contam: 5, min: 5 },
    { id: 'copper', name: 'Copper — bright', unit: 'kg', price: 4200, contam: 4, min: 0.2 },
    { id: 'brass', name: 'Brass', unit: 'kg', price: 2600, contam: 5, min: 0.2 },
    { id: 'cable', name: 'E-waste — cable', unit: 'kg', price: 180, contam: 6, min: 1 },
    { id: 'phone-pc', name: 'E-waste — phone (per unit)', unit: 'pc', price: 700, contam: 0, min: 1 },
    { id: 'laptop-pc', name: 'E-waste — laptop board (per unit)', unit: 'pc', price: 3500, contam: 0, min: 1 },
    { id: 'glass', name: 'Glass bottles', unit: 'kg', price: 30, contam: 12, min: 10 },
    { id: 'oil', name: 'Used cooking oil', unit: 'kg', price: 350, contam: 10, min: 2 },
    { id: 'tyre-pc', name: 'Tyres (per piece)', unit: 'pc', price: 250, contam: 0, min: 4 }
  ];

  var DEFAULT_ITEMS = [
    { id: 'sachet', label: 'Water sachet', weight: 0.003, mat: 'sachet' },
    { id: 'pet500', label: '500 ml PET bottle', weight: 0.01, mat: 'pet-clear' },
    { id: 'pet1500', label: '1.5 L PET bottle', weight: 0.03, mat: 'pet-clear' },
    { id: 'jerrycan', label: '20 L HDPE jerrycan', weight: 0.4, mat: 'hdpe' },
    { id: 'milkpouch', label: 'Milk pouch', weight: 0.012, mat: 'sachet' },
    { id: 'tin', label: 'Tin can (food)', weight: 0.06, mat: 'iron' },
    { id: 'can', label: 'Aluminium drink can', weight: 0.014, mat: 'alu' },
    { id: 'glassbottle', label: 'Glass bottle', weight: 0.32, mat: 'glass' },
    { id: 'cartonbox', label: 'Carton box (small)', weight: 0.18, mat: 'carton' },
    { id: 'cartonsheet', label: 'Carton sheet (~1 m²)', weight: 1, mat: 'carton' },
    { id: 'news', label: 'Newspaper', weight: 0.4, mat: 'newsprint' },
    { id: 'cable', label: 'Cable — per metre', weight: 0.09, mat: 'cable' },
    { id: 'oilbtl', label: 'Used cooking oil — 1 L bottle', weight: 0.95, mat: 'oil' },
    { id: 'phone', label: 'Old phone', weight: 0.18, mat: 'phone-pc', per: 'pc' },
    { id: 'laptop', label: 'Old laptop', weight: 2.1, mat: 'laptop-pc', per: 'pc' },
    { id: 'tyre', label: 'Car tyre', weight: 7, mat: 'tyre-pc', per: 'pc' }
  ];

  var DEFAULT_CAL = [
    { day: 'Monday', zone: 'Lekki Phase 1 — blocks A–D', window: '09:00–11:00', note: 'tricycle, gate collection' },
    { day: 'Tuesday', zone: 'Ibadan North — Agboyi/Code 7 line', window: '07:00–09:00', note: 'miles depot open early' },
    { day: 'Wednesday', zone: 'Ibadan North — north routes', window: '11:00–13:00', note: 'carton only' },
    { day: 'Thursday', zone: 'Onitsha — market line, head load', window: '14:00–16:00', note: 'association load shared' },
    { day: 'Friday', zone: 'Abuja — District 2 schools', window: '09:00–11:00', note: 'truck wants 400 kg' },
    { day: 'Saturday', zone: 'Lekki Phase 2 — estate back gate', window: '11:00–13:00', note: 'best day for carton' },
    { day: 'Sunday', zone: 'No collection', window: '—', note: 'quiet hours' }
  ];

  var DEFAULT_MEMBERS = [
    { name: 'Sample: B-block (Adeyemi family)', contrib: 5000, kg: 14, earned: 2100 },
    { name: 'Sample: C-block (Okafor family)', contrib: 5000, kg: 22, earned: 3400 },
    { name: 'Sample: chapel store room', contrib: 8000, kg: 41, earned: 5600 },
    { name: 'Sample: market line share', contrib: 12000, kg: 63, earned: 8900 }
  ];

  var DEFAULT_FACTORS = {
    'pet-clear': 1.2, 'pet-colour': 1.2, hdpe: 1.1, 'plastic-mixed': 0.8, sachet: 0.6,
    carton: 0.9, 'paper-mixed': 1, newsprint: 1.1, alu: 6, iron: 0.4, copper: 1.5,
    brass: 1.2, cable: 1.4, glass: 0.3, oil: 1.8
  };

  var RANGES = [
    { mat: 'PET / HDPE plastic', landfill: 'collection streams commonly report 40–70% of gathered PET reaching a recycler, not 100%', virgin: 'roughly 1–2 kg of virgin resin displaced per kg recovered, depending on wash losses' },
    { mat: 'Carton & paper', landfill: 'fibre diverts fully only while it stays dry; 2–3 reclaims is the usual published life', virgin: 'about 1–1.6 kg of virgin pulp avoided per kg of recovered fibre' },
    { mat: 'Aluminium cans', landfill: 'diversion is close to total — the metal is worth collecting repeatedly', virgin: 'published ranges are large: 4–9 kg CO₂e per kg avoided when replacing primary aluminium' },
    { mat: 'Scrap iron / steel', landfill: 'near-total diversion where a buyer exists; otherwise it is left', virgin: 'about 0.3–1.5 kg of virgin iron per kg of scrap charged to a furnace' },
    { mat: 'Copper & brass', landfill: 'small masses, high value — nearly always recovered when priced properly', virgin: 'ranges cited are wide; smelter route and scrap grade matter more than the figure' },
    { mat: 'Glass', landfill: 'diverted, but heavy and often refused — many buyers charge to take it', virgin: '1 kg of returned cullet displaces roughly 1.2 kg of sand/soda/lime feed' },
    { mat: 'Used cooking oil', landfill: 'kept out of drains and dump sites; a real local benefit', virgin: 'displaces virgin oil or biodiesel feed — do not quote a factor without a source' },
    { mat: 'E-waste & cables', landfill: 'diversion depends entirely on the recycler taking the board, not the weight', virgin: 'contains copper, tin and small gold quantities — cited ranges vary by an order of magnitude' }
  ];

  var VEHICLES = { bike: { label: 'Push / hand cart', perkm: 40 }, trike: { label: 'Tricycle (keke)', perkm: 120 }, truck: { label: '3-ton truck', perkm: 320 } };

  /* ---------------- stores ---------------- */

  var ST = {
    prices: kit.store('recircle:prices', null),
    items: kit.store('recircle:itemweights', null),
    cal: kit.store('recircle:cal', null),
    load: kit.store('recircle:load', null),
    pickup: kit.store('recircle:pickup', null),
    ledger: kit.store('recircle:ledger', null),
    records: kit.store('recircle:records', null),
    group: kit.store('recircle:group', null),
    factors: kit.store('recircle:factors', null),
    opts: kit.store('recircle:opts', null)
  };

  var state = {
    prices: clone(DEFAULT_PRICES),
    items: clone(DEFAULT_ITEMS),
    cal: clone(DEFAULT_CAL),
    counts: { pet1500: 40, can: 20 },
    overrides: { cartonbox: 6 },
    mixedRate: 45,
    loadsPerMonth: 4,
    week: [0, 1.48, 0, 1.2, 0, 4.8, 0],
    loadName: 'Block C — Saturday load',
    ledger: [],
    records: [],
    members: clone(DEFAULT_MEMBERS),
    group: { name: "St. Mary's — facility recycling circle", cycle: 'Michaelmas term', mode: 'kg', keep: 10 },
    factors: Object.assign({}, DEFAULT_FACTORS),
    asOf: '2026-10-15',
    calAsOf: '2026-10-15',
    tol: 12,
    co2: false
  };

  var DAY_NAMES = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
  var priceTouched = false;
  var last = {};

  function clone(o) { return JSON.parse(JSON.stringify(o)); }

  function hydrate() {
    var p = ST.prices.read();
    if (Array.isArray(p) && p.length) state.prices = p;
    var w = ST.items.read();
    if (Array.isArray(w) && w.length) {
      state.items.forEach(function (it) {
        var hit = w.filter(function (x) { return x.id === it.id; })[0];
        if (hit) it.weight = Number(hit.weight) || it.weight;
      });
    }
    var c = ST.cal.read();
    if (Array.isArray(c) && c.length) state.cal = c;
    var l = ST.load.read();
    if (l) {
      if (l.counts) state.counts = l.counts;
      if (l.overrides) state.overrides = l.overrides;
      if (l.mixedRate !== undefined) state.mixedRate = l.mixedRate;
      if (l.loadsPerMonth !== undefined) state.loadsPerMonth = l.loadsPerMonth;
      if (Array.isArray(l.week)) state.week = l.week;
      if (l.name) state.loadName = l.name;
    }
    var pk = ST.pickup.read();
    if (pk) state.pickupVals = pk;
    var lg = ST.ledger.read();
    if (Array.isArray(lg)) state.ledger = lg;
    var rc = ST.records.read();
    if (Array.isArray(rc)) state.records = rc;
    var gm = ST.group.read();
    if (gm) {
      if (Array.isArray(gm.members)) state.members = gm.members;
      if (gm.group) state.group = gm.group;
    }
    var f = ST.factors.read();
    if (f) state.factors = Object.assign({}, DEFAULT_FACTORS, f);
    var o = ST.opts.read();
    if (o) {
      if (o.asOf) state.asOf = o.asOf;
      if (o.calAsOf) state.calAsOf = o.calAsOf;
      if (o.tol !== undefined) state.tol = o.tol;
      if (o.co2 !== undefined) state.co2 = !!o.co2;
    }
  }

  function persist() {
    ST.prices.write(state.prices);
    ST.items.write(state.items.map(function (i) { return { id: i.id, weight: i.weight }; }));
    ST.cal.write(state.cal);
    ST.load.write({
      counts: state.counts, overrides: state.overrides, mixedRate: state.mixedRate,
      loadsPerMonth: state.loadsPerMonth, week: state.week, name: state.loadName
    });
    ST.ledger.write(state.ledger);
    ST.records.write(state.records);
    ST.group.write({ members: state.members, group: state.group });
    ST.factors.write(state.factors);
    ST.opts.write({ asOf: state.asOf, calAsOf: state.calAsOf, tol: state.tol, co2: state.co2 });
  }

  /* ---------------- small utils ---------------- */

  function escapeHtml(text) {
    var div = document.createElement('div');
    div.textContent = String(text);
    return div.innerHTML;
  }

  function el(id) { return document.getElementById(id); }
  function val(id, fallback) { var n = el(id); return n ? kit.num(n.value) : fallback; }
  function txt(id) { var n = el(id); return n ? String(n.value || '').trim() : ''; }

  function kg(value, dp) { return kit.amount(value, dp === undefined ? 2 : dp) + ' kg'; }
  function price(value) {
    var n = Math.round(Number(value) || 0);
    return (n < 0 ? '−₦' : '₦') + kit.amount(Math.abs(n));
  }
  function signed(value, formatter) { return (value >= 0 ? '+' : '−') + formatter(Math.abs(value)); }

  function setFigure(id, value) {
    var node = el(id);
    if (!node) return;
    var text = value === null || value === undefined ? '—' : String(value);
    if (node.textContent === text) return;
    node.textContent = text;
    if (!kit.reduce && last[id] !== undefined && last[id] !== text) {
      node.classList.remove('tick');
      void node.offsetWidth;
      node.classList.add('tick');
    }
    last[id] = text;
  }

  function verdict(boxId, titleId, bodyId, title, body, cls) {
    var box = el(boxId);
    if (box) box.className = 'verdict' + (cls ? ' ' + cls : '');
    setText(titleId, title);
    setText(bodyId, body);
  }

  function setText(id, value) { var n = el(id); if (n) n.textContent = value; }

  function round(value, dp) {
    var f = Math.pow(10, dp === undefined ? 2 : dp);
    return Math.round((Number(value) || 0) * f) / f;
  }

  function priceOf(id) {
    var hit = state.prices.filter(function (p) { return p.id === id; })[0];
    return hit || { id: id, name: id, unit: 'kg', price: 0, contam: 0, min: 0 };
  }

  function parseDay(value) {
    var parts = String(value || '').split('-');
    if (parts.length !== 3) return null;
    var d = new Date(Number(parts[0]), Number(parts[1]) - 1, Number(parts[2]));
    return isNaN(d) ? null : d;
  }

  function monthKey(value) {
    var d = parseDay(value);
    if (!d) return 'undated';
    return d.getFullYear() + '-' + (d.getMonth() + 1 < 10 ? '0' : '') + (d.getMonth() + 1);
  }

  function monthLabel(key) {
    if (key === 'undated') return 'No date given';
    var parts = String(key).split('-');
    var d = new Date(Number(parts[0]), Number(parts[1]) - 1, 1);
    return d.toLocaleDateString('en-NG', { month: 'long', year: 'numeric' });
  }

  function weekStart(d) {
    var copy = new Date(d.getFullYear(), d.getMonth(), d.getDate());
    var day = (copy.getDay() + 6) % 7;
    copy.setDate(copy.getDate() - day);
    return copy;
  }

  /* ---------------- engine 1+2+3: the load ---------------- */

  function computeLoad() {
    var groups = [];
    var byMat = {};
    state.items.forEach(function (item) {
      var qty = Math.max(0, kit.num(state.counts[item.id] || 0));
      var override = state.overrides[item.id];
      var hasOverride = override !== undefined && override !== null && String(override) !== '' && kit.num(override) > 0;
      var countedKg = round(qty * item.weight, 4);
      var kgUse = hasOverride ? kit.num(override) : countedKg;
      var mat = priceOf(item.mat);
      var value = 0;
      if (mat.unit === 'pc') value = qty * mat.price;
      else value = kgUse * mat.price;
      if (!byMat[mat.id]) {
        byMat[mat.id] = {
          id: mat.id, name: mat.name, unit: mat.unit, price: mat.price, contam: mat.contam, min: mat.min,
          kg: 0, value: 0, countedKg: 0, pieces: 0, items: [], overridden: false
        };
        groups.push(byMat[mat.id]);
      }
      var g = byMat[mat.id];
      g.kg = round(g.kg + kgUse, 4);
      g.countedKg = round(g.countedKg + countedKg, 4);
      g.value += value;
      if (mat.unit === 'pc') g.pieces += qty;
      if (hasOverride) g.overridden = true;
      if (qty > 0 || hasOverride) g.items.push({ id: item.id, label: item.label, qty: qty, weight: item.weight, override: hasOverride ? kgUse : null, value: value });
    });

    groups.forEach(function (g) {
      g.wet = g.value * (1 - g.contam / 100);
      g.mixedEach = g.unit === 'pc' ? 0 : g.kg * state.mixedRate;
      g.belowMin = g.kg > 0 && (g.unit === 'pc' ? g.pieces < g.min : g.kg < g.min);
    });

    var totalKg = 0, sorted = 0, mixedKg = 0, wet = 0, pieceValue = 0, pieceCount = 0, countedKg = 0;
    groups.forEach(function (g) {
      totalKg += g.kg;
      countedKg += g.countedKg;
      sorted += g.value;
      wet += g.wet;
      if (g.unit === 'pc') { pieceValue += g.value; pieceCount += g.pieces; }
      else mixedKg += g.kg;
    });

    var mixed = mixedKg * state.mixedRate;
    var uplift = sorted - mixed;
    return {
      groups: groups.filter(function (g) { return g.kg > 0 || g.pieces > 0; }),
      allGroups: groups,
      totalKg: totalKg, countedKg: countedKg, mixedKg: mixedKg,
      sorted: sorted, mixed: mixed, uplift: uplift,
      upliftPct: mixed > 0 ? uplift / mixed * 100 : (sorted > 0 ? 100 : 0),
      blended: totalKg > 0 ? sorted / totalKg : 0,
      wet: wet, penaltyAmount: sorted - wet,
      penaltyPct: sorted > 0 ? (sorted - wet) / sorted * 100 : 0,
      pieceValue: pieceValue, pieceCount: pieceCount,
      belowMin: groups.filter(function (g) { return g.belowMin; }),
      bestRate: groups.filter(function (g) { return g.unit !== 'pc' && g.kg > 0; })
        .reduce(function (a, g) { return Math.max(a, g.price); }, 0),
      month: sorted * state.loadsPerMonth,
      monthUplift: uplift * state.loadsPerMonth,
      term: sorted * 12,
      loads: state.loadsPerMonth
    };
  }

  /* ---------------- counter UI ---------------- */

  function renderItems() {
    var host = el('count-rows');
    if (!host) return;
    host.innerHTML = state.items.map(function (item) {
      var mat = priceOf(item.mat);
      var qty = state.counts[item.id] || 0;
      var override = state.overrides[item.id] === undefined ? '' : state.overrides[item.id];
      var perPiece = item.weight < 1 ? Math.round(item.weight * 1000) + ' g each' : round(item.weight, 2) + ' kg each';
      var priceLabel = mat.unit === 'pc' ? '₦' + kit.amount(mat.price, 0) + ' each' : '₦' + kit.amount(mat.price, 0) + '/kg';
      var unit = mat.unit === 'pc' ? 'pc' : 'kg';
      return '<div class="count-row" data-row="' + item.id + '">' +
        '<label class="cr-label" for="qty-' + item.id + '">' + escapeHtml(item.label) +
        '<span class="cr-meta">' + perPiece + ' · ' + escapeHtml(mat.name) + ' · ' + priceLabel + '</span></label>' +
        '<div class="cr-controls">' +
        '<span class="cr-step">' +
        '<button class="cr-btn" type="button" data-step="-1" data-item="' + item.id + '" aria-label="Fewer ' + escapeHtml(item.label) + '">−</button>' +
        '<input class="cr-qty" id="qty-' + item.id + '" type="number" min="0" max="99999" step="1" value="' + qty + '" data-qty="' + item.id + '" aria-label="Quantity of ' + escapeHtml(item.label) + '" inputmode="numeric">' +
        '<button class="cr-btn" type="button" data-step="1" data-item="' + item.id + '" aria-label="More ' + escapeHtml(item.label) + '">+</button>' +
        '</span>' +
        '<span class="cr-kg"><label class="sr-only" for="kg-' + item.id + '">Actual weighed kilograms for ' + escapeHtml(item.label) + ' (optional override)</label>' +
        '<input class="cr-override" id="kg-' + item.id + '" type="number" min="0" max="9999" step="0.01" placeholder="actual ' + unit + '" value="' + escapeHtml(override) + '" data-override="' + item.id + '" inputmode="decimal">' +
        '</span>' +
        '<span class="cr-out mono" data-out="' + item.id + '">₦0</span>' +
        '</div></div>';
    }).join('');
  }

  function paintItemOutputs(load) {
    state.items.forEach(function (item) {
      var out = document.querySelector('[data-out="' + item.id + '"]');
      var row = document.querySelector('[data-row="' + item.id + '"]');
      if (!out || !row) return;
      var hit = load.allGroups.filter(function (g) {
        return g.items.some(function (i) { return i.id === item.id; });
      })[0];
      var mine = hit ? hit.items.filter(function (i) { return i.id === item.id; })[0] : null;
      var w = mine ? (mine.override !== null ? mine.override : round(item.weight * mine.qty, 3)) : 0;
      out.textContent = price(mine ? mine.value : 0) + ' · ' + kit.amount(w, w < 1 ? 3 : 2) + ' kg';
      row.classList.toggle('is-live', (mine && (mine.qty > 0 || mine.override !== null)) || false);
    });
  }

  function bindCounter() {
    kit.on(el('quick-row'), 'click', '[data-add]', function (e, btn) {
      var id = btn.dataset.add;
      var n = Number(btn.dataset.n || 1);
      state.counts[id] = Math.max(0, (kit.num(state.counts[id] || 0)) + n);
      el('qty-' + id).value = state.counts[id];
      persist();
      paint();
      kit.toast('Added ' + n + ' × ' + itemName(id));
    });

    kit.on(el('count-rows'), 'click', '.cr-btn', function (e, btn) {
      var id = btn.dataset.item;
      var step = Number(btn.dataset.step);
      var item = state.items.filter(function (i) { return i.id === id; })[0];
      var size = item && item.weight < 0.02 ? 10 : 1;
      state.counts[id] = Math.max(0, kit.num(state.counts[id] || 0) + step * size);
      el('qty-' + id).value = state.counts[id];
      persist();
      paint();
    });

    kit.on(el('count-rows'), 'input', '[data-qty]', function (e, input) {
      state.counts[input.dataset.qty] = Math.max(0, Math.round(kit.num(input.value)));
      persist();
      paint();
    });

    kit.on(el('count-rows'), 'input', '[data-override]', function (e, input) {
      var id = input.dataset.override;
      if (String(input.value).trim() === '') delete state.overrides[id];
      else state.overrides[id] = Math.max(0, kit.num(input.value));
      persist();
      paint();
    });

    el('reset-counts').addEventListener('click', function () {
      state.counts = {};
      state.overrides = {};
      el('mixed-rate').value = 45;
      el('loads-month').value = 4;
      state.mixedRate = 45;
      state.loadsPerMonth = 4;
      renderItems();
      persist();
      paint();
      kit.toast('Counter cleared');
    });

    el('save-load').addEventListener('click', function () { saveLoadToLedger(); });
  }

  function itemName(id) {
    var hit = state.items.filter(function (i) { return i.id === id; })[0];
    return hit ? hit.label : id;
  }

  /* ---------------- paint the counter aside ---------------- */

  function paintCounter(load) {
    setFigure('net', price(load.sorted));
    setText('net-note', kit.amount(load.totalKg, 2) + ' kg · ' + price(load.month) + ' a month at ' + load.loads + ' load' + (load.loads === 1 ? '' : 's') + ' a month');
    setFigure('out-kg', kg(load.totalKg));
    setFigure('out-sorted', price(load.sorted));
    setFigure('out-mixed', price(load.mixed));
    setFigure('out-uplift', (load.uplift >= 0 ? '+' : '−') + price(Math.abs(load.uplift)) + ' (' + kit.pct(load.upliftPct, 1) + ')');
    setFigure('out-penalty', '−' + price(load.penaltyAmount) + ' (' + kit.pct(load.penaltyPct, 0) + ' avg)');
    setFigure('out-pcs', load.pieceCount ? load.pieceCount + ' pc = ' + price(load.pieceValue) : 'none in this load');
    setFigure('m-month', price(load.month));
    setFigure('m-term', price(load.term));
    setFigure('m-divert', kg(load.totalKg));

    if (load.totalKg <= 0) {
      verdict('verdict', 'verdict-title', 'verdict-body', 'Nothing counted yet',
        'Add bottles, carton, cans or scrap on the left — the sorting case builds itself from the editable price table.', '');
    } else {
      var mins = load.belowMin.length
        ? ' Note: ' + load.belowMin.map(function (g) { return g.name.toLowerCase() + ' ' + (g.unit === 'pc' ? g.pieces + ' pc' : kg(g.kg)) + ' (buyers want ~' + (g.unit === 'pc' ? g.min + ' pc' : kg(g.min, 1)) + ')'; }).join('; ') + '.'
        : '';
      verdict('verdict', 'verdict-title', 'verdict-body',
        'Twenty minutes of sorting a week is worth ' + price(load.monthUplift) + ' a month',
        'This load fetches ' + price(load.sorted) + ' sorted against ' + price(load.mixed) + ' dumped as mixed plastic and paper — ' +
        price(Math.abs(load.uplift)) + ' more on the load, ' + price(load.monthUplift) + ' a month. Keep it dry: a wet or oily batch loses ' +
        price(load.penaltyAmount) + ' to contamination.' + mins,
        load.uplift > 0 ? 'ok' : (load.sorted > 0 ? 'warn' : ''));
    }

    var loop = el('loop-load');
    if (loop) loop.style.transform = 'rotate(' + Math.round((kit.clamp(load.totalKg / 12, 0, 3) * 120)) + 'deg)';
  }

  /* ---------------- week band ---------------- */

  function renderWeekForm() {
    var host = el('week-form');
    if (!host) return;
    host.innerHTML = state.week.map(function (v, i) {
      return '<label for="week-' + i + '">' + DAY_NAMES[i] +
        '<input id="week-' + i + '" type="number" min="0" max="500" step="0.1" value="' + v + '" data-week="' + i + '" inputmode="decimal"></label>';
    }).join('');
  }

  function paintWeek(load) {
    var total = state.week.reduce(function (a, b) { return a + kit.num(b); }, 0);
    var max = Math.max.apply(null, state.week.map(kit.num).concat([0.001]));
    var band = el('week-band');
    if (band) {
      band.innerHTML = state.week.map(function (v, i) {
        var f = kit.clamp(kit.num(v) / max * 100, 0, 100);
        return '<div class="rc-day"><span class="rc-day-bar"><i class="rc-day-fill" style="--f:' + f.toFixed(1) + '%;--d:' + (i * 0.06).toFixed(2) + 's"></i></span>' +
          '<b>' + (kit.num(v) ? kit.amount(kit.num(v), kit.num(v) < 1 ? 2 : 1) : '') + '</b><span>' + DAY_NAMES[i] + '</span></div>';
      }).join('');
    }
    var cycle = el('cycle-band');
    if (cycle) {
      cycle.innerHTML = state.week.map(function (v, i) {
        var share = total > 0 ? kit.num(v) / total * 100 : 0;
        return '<div class="rc-seg"><b>' + DAY_NAMES[i] + '</b><i style="--f:' + share.toFixed(1) + '%"></i></div>';
      }).join('');
    }
    var cap = el('week-caption');
    if (cap) {
      var rate = load.totalKg > 0 ? load.sorted / load.totalKg : 0;
      var peak = state.week.map(kit.num).indexOf(max);
      cap.textContent = total
        ? 'This week: ' + kg(total) + ' set out · ' + price(total * rate) + ' at your current ' + price(rate) + '/kg mix · peak ' + DAY_NAMES[peak] + ' ' + kg(max) + '. Month equivalent: ' + price(total * rate * state.loadsPerMonth) + '.'
        : 'No kilograms logged in the week yet — the band stays flat.';
    }
  }

  function bindWeek() {
    kit.on(el('week-form'), 'input', '[data-week]', function (e, input) {
      state.week[Number(input.dataset.week)] = Math.max(0, kit.num(input.value));
      persist();
      paint();
    });
    el('week-sync').addEventListener('click', function () {
      var load = computeLoad();
      if (load.totalKg <= 0) { kit.toast('Count a load first'); return; }
      state.week[5] = round(load.totalKg, 2);
      renderWeekForm();
      persist();
      paint();
      kit.toast('Saturday now carries ' + kg(load.totalKg));
    });
    el('week-clear').addEventListener('click', function () {
      state.week = [0, 0, 0, 0, 0, 0, 0];
      renderWeekForm();
      persist();
      paint();
    });
  }

  /* ---------------- sorted vs mixed section ---------------- */

  function paintSplit(load) {
    var rows = load.groups.map(function (g) {
      return { label: escapeHtml(g.name), value: g.value, display: price(g.value) };
    });
    var host = el('split-bars');
    if (!host) return;
    if (!rows.length) { host.innerHTML = '<p class="tiny">Nothing counted yet.</p>'; }
    else kit.bars(host, rows);

    var keep = load.sorted > 0 ? kit.clamp(load.mixed / load.sorted * 100, 0, 100) : 0;
    kit.donut(el('split-donut'), keep, { label: kit.pct(keep, 0) + ' of the sorted value kept if it goes out unsorted' });
    setFigure('donut-label', kit.pct(keep, 0));
    setText('donut-note', load.totalKg > 0
      ? 'Unsorted you keep ' + kit.pct(keep, 0) + ' of what the load is worth: ' + price(load.mixed) + ' instead of ' +
        price(load.sorted) + '. Sorting costs twenty minutes and returns ' + price(load.uplift) + ' per load.'
      : 'Count a load to see the gap.');

    var body = el('split-body');
    var foot = el('split-foot');
    if (!body) return;
    if (!load.groups.length) {
      body.innerHTML = '<tr><td colspan="5" class="tiny">Nothing counted yet.</td></tr>';
      if (foot) foot.innerHTML = '';
    } else {
      body.innerHTML = load.groups.map(function (g) {
        var wet = g.wet;
        return '<tr class="' + (wet < g.mixedEach ? 'is-negative' : '') + '">' +
          '<td>' + escapeHtml(g.name) + (g.belowMin ? ' <span class="badge badge-warn">below usual minimum</span>' : '') + '</td>' +
          '<td class="num">' + kit.amount(g.unit === 'pc' ? g.kg : g.kg, 2) + '</td>' +
          '<td class="num">' + price(g.value) + '</td>' +
          '<td class="num">' + (g.unit === 'pc' ? 'refused' : price(g.mixedEach)) + '</td>' +
          '<td class="num ' + (wet < g.value ? 'neg' : '') + '">' + price(wet) + '</td></tr>';
      }).join('');
      if (foot) {
        foot.innerHTML = '<tr><td>Total</td><td class="num">' + kit.amount(load.totalKg, 2) + '</td>' +
          '<td class="num">' + price(load.sorted) + '</td><td class="num">' + price(load.mixed) + '</td>' +
          '<td class="num">' + price(load.wet) + '</td></tr>';
      }
    }
    setText('penalty-note', 'Weighted contamination penalty on this load: ' + kit.pct(load.penaltyPct, 1) +
      ' — ' + price(load.penaltyAmount) + ' lost if the batch is handed over wet, oily or with food in it. Per-material penalties are editable in the price table; ' +
      'a buyer who refuses to sort a mixed-wet batch simply pays the mixed rate, and sometimes charges you for glass.');
  }

  /* ---------------- engine 4: pickup scheduler ---------------- */

  function computeTrip(load) {
    var distance = val('pick-distance', 0);
    var perKm = val('pick-perkm', 0);
    var tripCost = distance * perKm;
    var offeredInput = el('pick-offered') ? kit.num(el('pick-offered').value) : 0;
    var hasOffered = String(el('pick-offered') ? el('pick-offered').value : '').trim() !== '';
    var sortedOn = el('pick-sorted') ? el('pick-sorted').checked : false;
    var offered = hasOffered ? offeredInput : (sortedOn ? load.blended : state.mixedRate);
    var valueAtOffer = load.totalKg * offered;
    var minKg = offered > 0 ? tripCost / offered : 0;
    var households = kit.clamp(Math.round(kit.num(val('pick-share', 1)) || 1), 1, 50);
    var combinedKg = load.totalKg * households;
    return {
      distance: distance, perKm: perKm, tripCost: tripCost, offered: offered, hasOffered: hasOffered,
      sortedOn: sortedOn, valueAtOffer: valueAtOffer, minKg: minKg, viable: load.totalKg >= minKg && minKg > 0,
      shortfall: minKg - load.totalKg, gapNaira: valueAtOffer - tripCost,
      households: households, combinedKg: combinedKg, combinedClears: combinedKg >= minKg,
      neededPrice: load.totalKg > 0 ? tripCost / load.totalKg : 0,
      minKgOneMaterial: load.bestRate > 0 ? tripCost / load.bestRate : 0,
      bestRate: load.bestRate,
      kgPerThousand: offered > 0 ? 1000 / offered : 0
    };
  }

  function renderCalendarSelect() {
    var sel = el('pick-day');
    if (!sel) return;
    var keepValue = sel.value;
    sel.innerHTML = state.cal.map(function (row, i) {
      return '<option value="' + i + '">' + escapeHtml(row.day + ' — ' + row.zone + ' (' + row.window + ')') + '</option>';
    }).join('');
    var idx = state.cal.findIndex(function (r, i) { return String(i) === keepValue; });
    sel.value = idx >= 0 ? keepValue : '5';
    syncWindowFromCalendar();
  }

  function syncWindowFromCalendar() {
    var sel = el('pick-day');
    var win = el('pick-window');
    if (!sel || !win) return;
    var row = state.cal[Number(sel.value)];
    if (!row || !row.window || row.window === '—') return;
    var found = Array.prototype.slice.call(win.options).some(function (o) { return o.value === row.window || o.textContent === row.window; });
    if (found) {
      Array.prototype.slice.call(win.options).forEach(function (o) { if (o.value === row.window || o.textContent === row.window) win.value = o.value; });
    } else {
      var opt = document.createElement('option');
      opt.textContent = row.window;
      opt.value = row.window;
      win.appendChild(opt);
      win.value = row.window;
    }
  }

  function renderCalendar() {
    var body = el('cal-body');
    if (!body) return;
    body.innerHTML = state.cal.map(function (row, i) {
      return '<tr data-cal="' + i + '">' +
        '<td><input type="text" value="' + escapeHtml(row.day) + '" data-calfield="day" aria-label="Collection day ' + (i + 1) + '"></td>' +
        '<td><input type="text" value="' + escapeHtml(row.zone) + '" data-calfield="zone" aria-label="Area or route ' + (i + 1) + '"></td>' +
        '<td><input type="text" value="' + escapeHtml(row.window) + '" data-calfield="window" aria-label="Usual window ' + (i + 1) + '"></td>' +
        '<td><input type="text" value="' + escapeHtml(row.note) + '" data-calfield="note" aria-label="Notes ' + (i + 1) + '"></td></tr>';
    }).join('');
    if (el('cal-asof')) el('cal-asof').value = state.calAsOf;
  }

  function bindCalendar() {
    kit.on(el('cal-body'), 'input', '[data-calfield]', function (e, input) {
      var i = Number(input.closest('tr').dataset.cal);
      state.cal[i][input.dataset.calfield] = String(input.value);
      persist();
      renderCalendarSelect();
      paintTrip();
    });
    el('cal-asof').addEventListener('input', function () {
      state.calAsOf = this.value || state.calAsOf;
      persist();
      paintTrip();
    });
    el('reset-cal').addEventListener('click', function () {
      state.cal = clone(DEFAULT_CAL);
      persist();
      renderCalendar();
      renderCalendarSelect();
      paint();
      kit.toast('Sample calendar restored');
    });
  }

  function paintTrip() {
    var load = computeLoad();
    var trip = computeTrip(load);
    setFigure('trip-cost', price(trip.tripCost));
    setText('trip-note', kit.amount(trip.distance, 1) + ' km one way × ' + price(trip.perKm) + '/km · ' +
      (VEHICLES[txt('pick-vehicle')] ? VEHICLES[txt('pick-vehicle')].label : 'chosen vehicle'));
    setFigure('load-kg', kg(load.totalKg));
    setFigure('load-value', price(trip.valueAtOffer) + ' at ' + price(trip.offered) + '/kg');
    setFigure('min-kg', kit.amount(trip.minKg, 2) + ' kg');
    setFigure('load-gap', signed(trip.gapNaira, function (v) { return price(v); }));
    setText('share-n', String(trip.households));
    setFigure('shared-kg', trip.households > 1
      ? kg(trip.combinedKg) + (trip.combinedClears ? ' — clears it' : ' — still short')
      : (trip.viable ? 'not needed' : 'needs ' + (Math.ceil(trip.minKg / (load.totalKg || 1)) - 1) + ' more'));
    setFigure('min-kg-one', trip.minKgOneMaterial ? kit.amount(trip.minKgOneMaterial, 2) + ' kg at ' + price(trip.bestRate) + '/kg' : '—');
    setFigure('m-perkm', price(trip.perKm));
    setFigure('m-per-thousand', kit.amount(trip.kgPerThousand, 1) + ' kg');
    setFigure('m-needed-price', load.totalKg > 0 ? price(trip.neededPrice) + '/kg' : '—');

    if (load.totalKg <= 0) {
      verdict('pickup-verdict', 'pickup-title', 'pickup-body', 'Count a load first',
        'The counter decides whether this trip is worth anyone’s time.', '');
      return;
    }
    if (trip.tripCost <= 0) {
      verdict('pickup-verdict', 'pickup-title', 'pickup-body', 'Enter the distance',
        'Trip cost is one-way kilometres × ₦ per km. Without it there is no break-even weight.', '');
      return;
    }
    if (!trip.viable) {
      var need = Math.ceil(trip.minKg / (load.totalKg || 1));
      verdict('pickup-verdict', 'pickup-title', 'pickup-body',
        'Below the minimum viable load — do not call yet',
        'At ' + price(trip.offered) + '/kg this ' + kit.amount(load.totalKg, 2) + ' kg load pays ' + price(trip.valueAtOffer) +
        ' against a ' + price(trip.tripCost) + ' trip: short by ' + price(Math.abs(trip.gapNaira)) + ', or ' +
        kit.amount(trip.shortfall, 2) + ' kg. Hold it a week, or combine with ' + (need - 1) +
        ' more household' + (need - 1 === 1 ? '' : 's') + ' like this one to reach ' + kg(load.totalKg * need) +
        '. Presenting it sorted instead of mixed lifts the price to about ' + price(load.blended) +
        '/kg and the break-even down to ' + kit.amount(trip.tripCost / load.blended, 2) + ' kg.', 'bad');
      return;
    }
    if (trip.gapNaira < trip.tripCost * 0.25) {
      verdict('pickup-verdict', 'pickup-title', 'pickup-body', 'Worth the trip — but only just',
        price(trip.valueAtOffer) + ' against a ' + price(trip.tripCost) + ' trip leaves ' + price(trip.gapNaira) +
        ' of margin, which one wet sack or a refused glass bag would erase. Two more households, or one more week of carton, makes this an easy call.', 'warn');
      return;
    }
    verdict('pickup-verdict', 'pickup-title', 'pickup-body', 'Worth the trip',
      price(trip.valueAtOffer) + ' at the offered price covers the ' + price(trip.tripCost) + ' trip with ' +
      price(trip.gapNaira) + ' to spare — ' + kit.pct(trip.gapNaira / trip.tripCost, 0) + ' over break-even. ' +
      'Anything under ' + kit.amount(trip.minKg, 2) + ' kg at this price is not.', 'ok');
  }

  function recordText(load, trip, when) {
    var row = state.cal[Number(el('pick-day').value)] || { day: '—', zone: '—', window: '—', note: '' };
    return [
      'RECIRCLE — PICKUP REQUEST RECORD',
      'Prepared offline ' + kit.dateLabel(when) + '. Nothing was booked, called or paid.',
      '',
      'Load label  : ' + (state.loadName || '—'),
      'Day / area  : ' + row.day + ' — ' + row.zone,
      'Window      : ' + txt('pick-window'),
      'Pick-up at  : ' + (txt('pick-address') || '—'),
      'Note        : ' + (txt('pick-note') || '—'),
      '',
      'Weight      : ' + kg(load.totalKg) + ' (' + load.groups.length + ' materials)',
      'Sorted value: ' + price(load.sorted) + '   Mixed value: ' + price(load.mixed),
      'Offered     : ' + price(trip.offered) + '/kg = ' + price(trip.valueAtOffer) + ' for this load',
      'Trip cost   : ' + kit.amount(trip.distance, 1) + ' km × ' + price(trip.perKm) + '/km (' + (VEHICLES[txt('pick-vehicle')] || {}).label + ') = ' + price(trip.tripCost),
      'Break-even  : ' + kit.amount(trip.minKg, 2) + ' kg at the offered price',
      'Verdict     : ' + (trip.viable ? 'ABOVE minimum viable load — worth asking' : 'BELOW minimum viable load by ' + kit.amount(trip.shortfall, 2) + ' kg — combine with ' + trip.households + ' household(s) or hold a week'),
      '',
      'Prices are indicative as of ' + kit.dateLabel(state.asOf) + '. Confirm weight, price and day with the collector in person.'
    ].join('\n');
  }

  function makeRecord() {
    var load = computeLoad();
    var trip = computeTrip(load);
    if (load.totalKg <= 0) { kit.toast('Count a load before preparing a record'); return; }
    var when = Date.now();
    var row = state.cal[Number(el('pick-day').value)] || {};
    var rec = {
      id: kit.uid('req'), when: when, day: row.day || '', zone: row.zone || '', window: txt('pick-window'),
      address: txt('pick-address'), note: txt('pick-note'), label: state.loadName,
      kg: round(load.totalKg, 2), sorted: round(load.sorted, 0), offered: round(trip.offered, 2),
      value: round(trip.valueAtOffer, 0), tripCost: round(trip.tripCost, 0), minKg: round(trip.minKg, 2),
      viable: trip.viable, households: trip.households, status: 'prepared — not booked', text: recordText(load, trip, when)
    };
    state.records.push(rec);
    persist();
    paintRecords();
    kit.toast('Record prepared. Nothing has been booked — copy it to the collector yourself.');
  }

  function paintRecords() {
    var out = el('record-out');
    var status = el('record-status');
    if (!out || !status) return;
    if (!state.records.length) {
      out.textContent = '—';
      status.textContent = 'No record prepared yet.';
      status.classList.remove('is-ready');
      return;
    }
    var rec = state.records[state.records.length - 1];
    out.textContent = rec.text;
    status.textContent = state.records.length + ' record' + (state.records.length === 1 ? '' : 's') +
      ' prepared · status: ' + rec.status + ' · ' + kit.dateLabel(rec.when);
    status.classList.add('is-ready');
  }

  function bindPickup() {
    el('make-record').addEventListener('click', makeRecord);
    el('copy-record').addEventListener('click', function () {
      var out = el('record-out');
      if (!state.records.length) { kit.toast('Prepare a record first'); return; }
      try {
        out.focus();
        out.select && out.select();
        if (navigator.clipboard && navigator.clipboard.writeText) {
          navigator.clipboard.writeText(out.textContent).then(function () {
            kit.toast('Record copied to the clipboard');
          }, function () { kit.toast('Copy failed — the record is selected, use Ctrl+C'); });
        } else {
          document.execCommand('copy');
          kit.toast('Record selected — copy it now');
        }
      } catch (err) {
        kit.toast('Copy not available here — the record is selected for manual copy');
      }
    });
    el('print-record').addEventListener('click', function () {
      if (!state.records.length) { kit.toast('Prepare a record first'); return; }
      kit.print();
    });
    el('export-records').addEventListener('click', function () {
      if (!state.records.length) { kit.toast('No records prepared yet'); return; }
      kit.csv([['Prepared', 'Label', 'Day', 'Area', 'Window', 'Pick-up point', 'Kg', 'Offered ₦/kg', '₦ at offered', 'Trip cost ₦', 'Break-even kg', 'Viable', 'Status', 'Note']]
        .concat(state.records.map(function (r) {
          return [kit.dateLabel(r.when), r.label, r.day, r.zone, r.window, r.address, r.kg, r.offered, r.value, r.tripCost, r.minKg, r.viable ? 'yes' : 'no', r.status, r.note];
        })), 'recircle-pickup-requests-' + new Date().toISOString().slice(0, 10) + '.csv');
    });
    el('pick-vehicle').addEventListener('change', function () {
      var v = VEHICLES[this.value];
      if (v) el('pick-perkm').value = v.perkm;
      persistPickup();
      paint();
    });
    el('pick-day').addEventListener('change', function () {
      syncWindowFromCalendar();
      persistPickup();
      paintTrip();
    });
    el('pick-sorted').addEventListener('change', function () {
      if (!String(el('pick-offered').value).trim()) paint();
    });
  }

  function persistPickup() {
    ST.pickup.write({
      day: el('pick-day').value, win: txt('pick-window'), address: txt('pick-address'),
      vehicle: txt('pick-vehicle'), distance: txt('pick-distance'), perkm: txt('pick-perkm'),
      offered: txt('pick-offered'), share: txt('pick-share'), note: txt('pick-note'),
      sorted: el('pick-sorted').checked
    });
  }

  /* ---------------- engine 6: scale / tare sanity ---------------- */

  function computeScale(load) {
    var buyer = val('scale-buyer', 0);
    var own = val('scale-own', load.totalKg);
    var tare = val('scale-tare', 0);
    var pricePerKg = val('scale-price', load.blended);
    var tol = state.tol;
    var rawGap = own - buyer;
    var netPaidKg = Math.max(0, buyer - tare);
    var gapNetKg = own - netPaidKg;
    var paid = netPaidKg * pricePerKg;
    var fair = own * pricePerKg;
    return {
      buyer: buyer, own: own, tare: tare, pricePerKg: pricePerKg, tol: tol,
      rawGap: rawGap, gapNetKg: gapNetKg, netPaidKg: netPaidKg,
      paid: paid, fair: fair, lost: fair - paid,
      pct: own > 0 ? gapNetKg / own * 100 : 0,
      overTol: own > 0 && Math.abs(gapNetKg) / own * 100 > tol,
      material: txt('scale-material') || 'carton',
      buyerName: txt('scale-buyer-name')
    };
  }

  function paintScale(load) {
    var s = computeScale(load);
    if (!priceTouched && el('scale-price')) el('scale-price').value = round(load.blended, 2);
    if (el('scale-derived')) {
      el('scale-derived').textContent = load.totalKg > 0
        ? 'Your counter says ' + kg(load.totalKg) + ' — the item count is the honest second opinion.'
        : 'Your counter is empty, so use your own scale reading.';
    }
    if (el('use-count')) el('use-count').textContent = 'Use my counted load' + (load.totalKg > 0 ? ' (' + kit.amount(load.totalKg, 2) + ' kg)' : '');
    if (el('scale-price')) s.pricePerKg = val('scale-price', load.blended);
    s.paid = s.netPaidKg * s.pricePerKg;
    s.fair = s.own * s.pricePerKg;
    s.lost = s.fair - s.paid;

    setFigure('gap-kg', kit.amount(s.rawGap, 2) + ' kg');
    setFigure('gap-pct', kit.pct(s.pct, 1) + (s.pct >= 0 ? ' under' : ' over'));
    setFigure('gap-tare', kit.amount(s.tare, 2) + ' kg');
    setFigure('gap-net-kg', kit.amount(s.gapNetKg, 2) + ' kg');
    setFigure('gap-naira', price(Math.abs(s.lost)));
    setText('gap-note', s.gapNetKg >= 0
      ? 'paid on ' + kit.amount(s.netPaidKg, 2) + ' kg after his tare, against ' + kit.amount(s.own, 2) + ' kg you counted'
      : 'the scale reads MORE than your count — check the crate was not left on it');
    setFigure('gap-paid', price(s.paid) + ' vs ' + price(s.fair));
    setFigure('s-lost', price(s.lost));
    setFigure('s-month', price(s.lost * state.loadsPerMonth));
    var disputes = state.ledger.filter(isDisputed).length;
    setFigure('s-disputes', String(disputes));

    if (s.own <= 0 && s.buyer <= 0) {
      verdict('scale-verdict', 'scale-title', 'scale-body', 'Enter both weights',
        'His reading, your count, and the crate he deducted. The gap only means something after the tare.', '');
      return;
    }
    if (s.gapNetKg <= 0) {
      verdict('scale-verdict', 'scale-title', 'scale-body', 'No shortfall after the tare',
        'His reading less ' + kit.amount(s.tare, 2) + ' kg of crate leaves ' + price(Math.abs(s.lost)) +
        ' in your favour against your own count. Weigh the next load in front of you anyway.', 'ok');
      return;
    }
    if (!s.overTol) {
      verdict('scale-verdict', 'scale-title', 'scale-body', 'Inside your ' + s.tol + '% tolerance',
        kit.amount(s.gapNetKg, 2) + ' kg (' + kit.pct(Math.abs(s.pct), 1) + ') is a rounding argument, not a dispute — ' +
        price(s.lost) + ' on this load. Log it so the pattern is visible.', 'warn');
      return;
    }
    verdict('scale-verdict', 'scale-title', 'scale-body', 'Dispute: ' + kit.amount(s.gapNetKg, 2) + ' kg is ' + price(s.lost) + ' of material',
      'That is ' + kit.pct(Math.abs(s.pct), 1) + ' below your own count, after the ' + kit.amount(s.tare, 2) +
      ' kg tare. The usual cause is a crate or wet sack counted as material — or a scale that was never zeroed. ' +
      'Log the weigh-in: the ledger flags it, and a pattern of flags against one buyer is the thing you take to the union meeting.', 'bad');
  }

  function bindScale() {
    el('use-count').addEventListener('click', function () {
      var load = computeLoad();
      if (load.totalKg <= 0) { kit.toast('Count a load first'); return; }
      el('scale-own').value = round(load.totalKg, 2);
      paint();
      kit.toast('Own weight set to your counted ' + kg(load.totalKg));
    });
    el('scale-price').addEventListener('input', function () { priceTouched = String(this.value).trim() !== ''; });
    el('log-dispute').addEventListener('click', function () {
      var load = computeLoad();
      var s = computeScale(load);
      var mat = priceOf(s.material);
      var row = {
        id: kit.uid('led'), date: new Date().toISOString().slice(0, 10), material: mat.id,
        materialName: mat.name, unit: mat.unit, kg: round(s.netPaidKg, 2), expectedKg: round(s.own, 2),
        price: round(mat.unit === 'pc' ? mat.price : s.pricePerKg, 2),
        earned: round(s.paid, 0), buyer: s.buyerName || 'scale check',
        checked: 'no', tare: round(s.tare, 2), note: 'scale check: ' + kit.amount(s.gapNetKg, 2) + ' kg short after tare (' + kit.pct(Math.abs(s.pct), 1) + ')',
        added: Date.now(), dispute: s.overTol
      };
      state.ledger.push(row);
      persist();
      paintLedger();
      paint();
      kit.toast(s.overTol ? 'Weigh-in logged and flagged as a dispute' : 'Weigh-in logged (inside tolerance)');
      var body = el('ledger-body');
      if (body && body.firstElementChild) body.firstElementChild.scrollIntoView({ block: 'nearest', behavior: kit.reduce ? 'auto' : 'smooth' });
    });
    el('scale-tol').addEventListener('input', function () {
      state.tol = kit.clamp(kit.num(this.value) || 1, 1, 60);
      persist();
      paint();
    });
    el('scale-material').innerHTML = state.prices.map(function (p) {
      return '<option value="' + p.id + '">' + escapeHtml(p.name) + '</option>';
    }).join('');
    el('scale-material').value = 'carton';
  }

  /* ---------------- engine 5: ledger ---------------- */

  function isDisputed(row) {
    if (row.dispute) return true;
    var expected = kit.num(row.expectedKg);
    var actual = kit.num(row.kg);
    if (!expected || expected <= 0) return false;
    return (expected - actual) / expected * 100 > state.tol;
  }

  function saveLoadToLedger() {
    var load = computeLoad();
    if (load.totalKg <= 0) { kit.toast('Count something before saving a load'); return; }
    var today = new Date().toISOString().slice(0, 10);
    var added = 0;
    var flaggedId = null;
    load.groups.forEach(function (g) {
      var row = {
        id: kit.uid('led'), date: today, material: g.id, materialName: g.name, unit: g.unit,
        kg: round(g.kg, 2), expectedKg: round(g.countedKg, 2), price: round(g.price, 2),
        earned: round(g.value, 0), buyer: '', checked: 'shown',
        note: state.loadName + (g.overridden ? ' (weighed, overriding the count)' : ''),
        added: Date.now(), dispute: false
      };
      if (row.expectedKg > 0 && (row.expectedKg - row.kg) / row.expectedKg * 100 > state.tol) {
        row.dispute = true;
        row.note += ' — weighed ' + kit.pct((row.expectedKg - row.kg) / row.expectedKg * 100, 0) + ' below the item count';
        if (!flaggedId) flaggedId = row.id;
      }
      state.ledger.push(row);
      added++;
    });
    persist();
    paintLedger(flaggedId);
    paint();
    kit.toast('Added ' + added + ' record' + (added === 1 ? '' : 's') + ' to the ledger');
  }

  function addEntry() {
    var mat = priceOf(txt('e-material'));
    var kgValue = val('e-kg', 0);
    var expected = String(el('e-expected').value).trim() === '' ? 0 : kit.num(el('e-expected').value);
    var priceUsed = String(el('e-price').value).trim() === '' ? mat.price : kit.num(el('e-price').value);
    var earned = String(el('e-earned').value).trim() === '' ? kgValue * priceUsed : kit.num(el('e-earned').value);
    if (kgValue <= 0 && earned <= 0) {
      kit.toast('Enter the kilograms, or the naira you were paid');
      el('e-kg').focus();
      return;
    }
    var row = {
      id: kit.uid('led'), date: txt('e-date') || new Date().toISOString().slice(0, 10),
      material: mat.id, materialName: txt('e-material') ? priceOf(txt('e-material')).name : mat.name, unit: mat.unit,
      kg: round(kgValue, 2), expectedKg: round(expected, 2), price: round(priceUsed, 2), earned: round(earned, 0),
      buyer: txt('e-buyer'), checked: txt('e-checked'), note: txt('e-note'), added: Date.now(), dispute: false
    };
    if (row.expectedKg > 0 && (row.expectedKg - row.kg) / row.expectedKg * 100 > state.tol) {
      row.dispute = true;
      row.note = (row.note ? row.note + ' — ' : '') + 'count said ' + kit.amount(row.expectedKg, 2) + ' kg';
    }
    state.ledger.push(row);
    persist();
    paintLedger(row.id);
    paint();
    kit.toast(row.dispute ? 'Record saved and flagged: weighed below your count' : 'Record saved');
    ['e-kg', 'e-expected', 'e-price', 'e-earned', 'e-buyer', 'e-note'].forEach(function (id) { el(id).value = ''; });
  }

  function ledgerTotals(rows) {
    var byMat = {};
    var byMonth = {};
    var totalN = 0, totalKg = 0, disputes = 0;
    rows.forEach(function (r) {
      var key = r.materialName || priceOf(r.material).name;
      if (!byMat[key]) byMat[key] = { name: key, kg: 0, earned: 0, n: 0 };
      byMat[key].kg += kit.num(r.kg);
      byMat[key].earned += kit.num(r.earned);
      byMat[key].n += 1;
      var m = monthKey(r.date);
      byMonth[m] = (byMonth[m] || 0) + kit.num(r.earned);
      totalN += kit.num(r.earned);
      totalKg += kit.num(r.kg);
      if (isDisputed(r)) disputes++;
    });
    return { byMat: byMat, byMonth: byMonth, totalN: totalN, totalKg: totalKg, disputes: disputes };
  }

  function paintLedger(newId) {
    var body = el('ledger-body');
    var foot = el('ledger-foot');
    if (!body) return;
    var rows = state.ledger.slice().sort(function (a, b) {
      return String(a.date) < String(b.date) ? 1 : (String(a.date) > String(b.date) ? -1 : (b.added || 0) - (a.added || 0));
    });
    var totals = ledgerTotals(state.ledger);
    setText('ledger-count', state.ledger.length + ' record' + (state.ledger.length === 1 ? '' : 's') + ' · ' +
      price(totals.totalN) + ' · ' + kg(totals.totalKg) + (totals.disputes ? ' · ' + totals.disputes + ' dispute flag' + (totals.disputes === 1 ? '' : 's') : ''));
    el('ledger-empty').hidden = state.ledger.length > 0;

    if (!rows.length) {
      body.innerHTML = '';
      if (foot) foot.innerHTML = '';
    } else {
      body.innerHTML = rows.map(function (r) {
        var flagged = isDisputed(r);
        var expected = kit.num(r.expectedKg);
        return '<tr data-id="' + r.id + '" class="' + (flagged ? 'is-dispute ' : '') + (r.id === newId ? 'is-new' : '') + '">' +
          '<td class="mono">' + escapeHtml(r.date || '—') + '</td>' +
          '<td>' + escapeHtml(r.materialName || priceOf(r.material).name) +
          (flagged ? '<span class="dispute-tag">dispute</span>' : '') + '</td>' +
          '<td class="num">' + kit.amount(r.kg, 2) + '</td>' +
          '<td class="num">' + (expected > 0 ? kit.amount(expected, 2) + ' <b class="' + (flagged ? 'neg' : 'pos') + '">' + kit.pct((expected - kit.num(r.kg)) / expected * 100, 0) + '</b>' : '—') + '</td>' +
          '<td class="num">' + price(r.price) + (r.unit === 'pc' ? '/pc' : '/kg') + '</td>' +
          '<td class="num"><b>' + price(r.earned) + '</b></td>' +
          '<td>' + escapeHtml(r.buyer || '—') + '</td>' +
          '<td>' + checkedLabel(r.checked) + '</td>' +
          '<td class="tiny">' + escapeHtml(r.note || '') + '</td>' +
          '<td><button class="row-del" type="button" data-del="' + r.id + '" aria-label="Delete the record for ' + escapeHtml(r.materialName || r.material) + ' on ' + escapeHtml(r.date) + '">×</button></td>' +
          '</tr>';
      }).join('');
      if (foot) {
        foot.innerHTML = '<tr><td colspan="2">Total</td><td class="num">' + kit.amount(totals.totalKg, 2) + '</td><td></td><td></td>' +
          '<td class="num">' + price(totals.totalN) + '</td><td colspan="4"></td></tr>';
      }
    }

    var flag = el('dispute-flag');
    if (flag) {
      if (!totals.disputes) {
        flag.className = 'dispute-callout';
        flag.textContent = 'No disputes flagged. A flag appears when the kilograms you sold are more than ' + state.tol + '% below the kilograms your own item count implies — that is how under-weighing gets caught.';
      } else {
        var lost = state.ledger.filter(isDisputed).reduce(function (a, r) {
          return a + Math.max(0, (kit.num(r.expectedKg) - kit.num(r.kg)) * kit.num(r.price));
        }, 0);
        flag.className = 'dispute-callout is-hot';
        flag.innerHTML = '<strong>' + totals.disputes + ' weigh-in' + (totals.disputes === 1 ? '' : 's') +
          ' flagged as under-weighed</strong><span>' + price(lost) + ' of material sits between your count and his scale. ' +
          'Print the statement, take it to the buyer or the union meeting, and stop selling to that scale.</span>';
      }
    }

    var barHost = el('ledger-bars');
    if (barHost) {
      var barRows = Object.keys(totals.byMat).map(function (k) {
        return { label: escapeHtml(k), value: totals.byMat[k].earned, display: price(totals.byMat[k].earned) };
      }).sort(function (a, b) { return b.value - a.value; }).slice(0, 8);
      if (!barRows.length) barHost.innerHTML = '<p class="tiny">Nothing saved yet.</p>';
      else kit.bars(barHost, barRows);
    }

    var spark = el('spark-weekly');
    if (spark) {
      var buckets = {};
      state.ledger.forEach(function (r) {
        var d = parseDay(r.date);
        if (!d) return;
        var ws = weekStart(d).getTime();
        buckets[ws] = (buckets[ws] || 0) + kit.num(r.earned);
      });
      var keys = Object.keys(buckets).map(Number).sort(function (a, b) { return a - b; });
      var values = keys.map(function (k) { return buckets[k]; });
      if (values.length < 2) spark.innerHTML = '';
      else kit.sparkline(spark, values.slice(-12), { dot: 'var(--spark)' });
    }

    setFigure('t-month', price(totals.byMonth[monthKey(new Date().toISOString().slice(0, 10))] || 0));
    setFigure('t-all', price(totals.totalN));
    setFigure('t-kg', kg(totals.totalKg));

    paintMonthOptions();
    paintStatement();
    paintImpact(totals);
  }

  function checkedLabel(value) {
    if (value === 'yes') return 'Yes';
    if (value === 'no') return 'No';
    return 'Not verified';
  }

  function paintMonthOptions() {
    var sel = el('statement-month');
    if (!sel) return;
    var keys = Object.keys(ledgerTotals(state.ledger).byMonth).sort();
    var keep = sel.value;
    sel.innerHTML = '<option value="">All months</option>' + keys.map(function (k) {
      return '<option value="' + k + '">' + escapeHtml(monthLabel(k)) + '</option>';
    }).join('');
    sel.value = keep && (keys.indexOf(keep) >= 0 || keep === '') ? keep : '';
  }

  function paintStatement() {
    var sel = el('statement-month');
    var month = sel ? sel.value : '';
    var rows = state.ledger.filter(function (r) { return !month || monthKey(r.date) === month; });
    var totals = ledgerTotals(rows);
    setText('st-title', month ? 'Monthly statement — ' + monthLabel(month) : 'Monthly statement — all records');
    setText('st-sub', 'ReCircle ledger · prepared ' + kit.dateLabel(new Date()) + ' for “' + (state.loadName || 'this household') +
      '” · ' + rows.length + ' record' + (rows.length === 1 ? '' : 's') + ' · prices indicative as of ' + kit.dateLabel(state.asOf));
    var body = el('st-body');
    var foot = el('st-foot');
    if (!body) return;
    var keys = Object.keys(totals.byMat).sort(function (a, b) { return totals.byMat[b].earned - totals.byMat[a].earned; });
    if (!keys.length) {
      body.innerHTML = '<tr><td colspan="4" class="tiny">No records for this period yet.</td></tr>';
      foot.innerHTML = '';
      return;
    }
    body.innerHTML = keys.map(function (k) {
      var m = totals.byMat[k];
      return '<tr><td>' + escapeHtml(k) + '</td><td class="num">' + kit.amount(m.kg, 2) + '</td>' +
        '<td class="num">' + price(m.earned) + '</td><td class="num">' + m.n + '</td></tr>';
    }).join('');
    foot.innerHTML = '<tr><td>Total diverted</td><td class="num">' + kit.amount(totals.totalKg, 2) + '</td>' +
      '<td class="num">' + price(totals.totalN) + '</td><td class="num">' + rows.length + '</td></tr>';
  }

  function seedLedger() {
    var today = new Date();
    var samples = [
      { off: 21, mat: 'carton', kg: 18, buyer: 'Bava, scrap line', note: 'sample record — dry carton, full price' },
      { off: 21, mat: 'pet-clear', kg: 3.4, buyer: 'Bava, scrap line', note: 'sample record — clear only' },
      { off: 14, mat: 'alu', kg: 1.1, buyer: 'keke, Wednesday route', note: 'sample record' },
      { off: 14, mat: 'carton', kg: 22, buyer: 'keke, Wednesday route', note: 'sample record — wet edge, deducted 2 kg' },
      { off: 7, mat: 'sachet', kg: 6, buyer: 'association collection', note: 'sample record — baled' },
      { off: 7, mat: 'hdpe', kg: 4.2, buyer: 'association collection', note: 'sample record' },
      { off: 0, mat: 'iron', kg: 12, buyer: 'Iyanaho buyer', note: 'sample record — tin cans' },
      { off: 0, mat: 'cable', kg: 1.6, buyer: 'Iyanaho buyer', note: 'sample record — weighed with a coil hook' }
    ];
    samples.forEach(function (s) {
      var mat = priceOf(s.mat);
      var d = new Date(today.getFullYear(), today.getMonth(), today.getDate() - s.off);
      var expected = round(mat.unit === 'pc' ? mat.min : s.kg + 1.8, 2);
      var row = {
        id: kit.uid('led'), date: d.toISOString().slice(0, 10), material: mat.id, materialName: mat.name,
        unit: mat.unit, kg: s.kg, expectedKg: expected, price: mat.price, earned: round(s.kg * mat.price, 0),
        buyer: s.buyer, checked: s.off === 0 ? 'no' : 'shown', note: s.note, added: Date.now() - s.off * 86400000,
        dispute: false
      };
      state.ledger.push(row);
    });
    persist();
    paintLedger();
    paint();
    kit.toast('Sample month added — clearly labelled, delete any row you like');
  }

  function exportLedger() {
    if (!state.ledger.length) { kit.toast('The ledger is empty'); return; }
    kit.csv([['Date', 'Material', 'Unit', 'Kg sold', 'Kg from item count', 'Difference %', 'Price used', 'Naira earned', 'Buyer', 'Scale checked', 'Note', 'Dispute flagged']]
      .concat(state.ledger.slice().sort(function (a, b) { return String(a.date) < String(b.date) ? -1 : 1; }).map(function (r) {
        var expected = kit.num(r.expectedKg);
        var diff = expected > 0 ? round((expected - kit.num(r.kg)) / expected * 100, 1) : '';
        return [r.date, r.materialName || priceOf(r.material).name, r.unit || 'kg', r.kg, expected || '', diff, r.price, round(r.earned, 0), r.buyer || '', checkedLabel(r.checked), r.note || '', isDisputed(r) ? 'YES' : ''];
      })), 'recircle-ledger-' + new Date().toISOString().slice(0, 10) + '.csv');
  }

  function bindLedger() {
    kit.on(el('ledger-body'), 'click', '[data-del]', function (e, btn) {
      var removed = el('ledger-body').querySelector('tr[data-id="' + btn.dataset.del + '"]');
      state.ledger = state.ledger.filter(function (r) { return r.id !== btn.dataset.del; });
      persist();
      if (removed && !kit.reduce) {
        var next = removed.nextElementSibling;
        paintLedger();
        if (next) { next.classList.add('is-new'); }
      } else {
        paintLedger();
      }
      paint();
      kit.toast('Record deleted');
    });
    el('add-entry').addEventListener('click', addEntry);
    el('export-ledger').addEventListener('click', exportLedger);
    el('print-statement').addEventListener('click', function () {
      if (!state.ledger.length) { kit.toast('Nothing to print yet'); return; }
      kit.print();
    });
    el('seed-ledger').addEventListener('click', seedLedger);
    el('clear-ledger').addEventListener('click', function () {
      if (!state.ledger.length) { kit.toast('The ledger is already empty'); return; }
      if (!window.confirm('Remove every saved record from this browser? Export the CSV first if you need it.')) return;
      state.ledger = [];
      persist();
      paintLedger();
      paint();
      kit.toast('Ledger cleared');
    });
    el('statement-month').addEventListener('change', paintStatement);
    if (el('e-date')) el('e-date').value = new Date().toISOString().slice(0, 10);
    el('e-material').innerHTML = state.prices.map(function (p) {
      return '<option value="' + p.id + '">' + escapeHtml(p.name) + ' — ₦' + kit.amount(p.price, 0) + (p.unit === 'pc' ? '/pc' : '/kg') + '</option>';
    }).join('');
    el('e-material').value = 'carton';
    el('e-kg').addEventListener('input', function () {
      var priceField = el('e-price');
      if (String(priceField.value).trim() === '') {
        var mat = priceOf(txt('e-material'));
        var auto = kit.num(this.value) * mat.price;
        el('e-earned').placeholder = auto ? '₦' + kit.amount(auto, 0) : 'auto';
      }
    });
    el('e-material').addEventListener('change', function () {
      el('e-price').value = '';
      var mat = priceOf(txt('e-material'));
      el('e-price').placeholder = '₦' + kit.amount(mat.price, 0) + (mat.unit === 'pc' ? '/pc' : '/kg');
    });
  }

  /* ---------------- engine 8: environment ---------------- */

  function paintImpact(totals) {
    totals = totals || ledgerTotals(state.ledger);
    setFigure('kg-diverted', kg(totals.totalKg));
    var host = el('impact-bars');
    if (host) {
      var rows = Object.keys(totals.byMat).map(function (k) {
        return { label: escapeHtml(k), value: totals.byMat[k].kg, display: kg(totals.byMat[k].kg) };
      }).sort(function (a, b) { return b.value - a.value; }).slice(0, 8);
      if (!rows.length) host.innerHTML = '<p class="tiny">No ledger entries yet — the honest figure stays zero until you record a weight.</p>';
      else kit.bars(host, rows);
    }
    var rangeBody = el('range-rows');
    if (rangeBody && !rangeBody.dataset.painted) {
      rangeBody.innerHTML = RANGES.map(function (r) {
        return '<tr><td>' + escapeHtml(r.mat) + '</td><td>' + escapeHtml(r.landfill) + '</td><td>' + escapeHtml(r.virgin) + '</td></tr>';
      }).join('');
      rangeBody.dataset.painted = '1';
    }
    paintCo2(totals);
  }

  function paintCo2(totals) {
    var toggle = el('co2-toggle');
    var block = el('co2-block');
    if (!toggle || !block) return;
    block.hidden = !toggle.checked;
    setFigure('co2-headline', toggle.checked ? 'rough estimate only' : 'switched off');
    if (!toggle.checked) return;

    var byId = {};
    state.ledger.forEach(function (r) {
      if (r.unit === 'pc') return;
      byId[r.material] = (byId[r.material] || 0) + kit.num(r.kg);
    });
    var body = el('co2-body');
    var out = el('co2-out');
    if (!body || !out) return;
    var ids = Object.keys(byId).filter(function (id) { return priceOf(id).unit !== 'pc'; });
    if (!ids.length) {
      body.innerHTML = '<tr><td colspan="3" class="tiny">No kilogram records yet — there is nothing to model.</td></tr>';
      out.textContent = 'Rough CO₂e estimate: nothing to multiply yet.';
      return;
    }
    body.innerHTML = ids.map(function (id) {
      var f = state.factors[id] === undefined ? 1 : state.factors[id];
      return '<tr data-factor="' + id + '"><td>' + escapeHtml(priceOf(id).name) + ' <span class="tiny mono">' + kit.amount(byId[id], 2) + ' kg</span></td>' +
        '<td class="num"><input type="number" min="0" max="50" step="0.1" value="' + f + '" data-co2="' + id + '" aria-label="Rough factor for ' + escapeHtml(priceOf(id).name) + '"></td>' +
        '<td class="num">' + kit.amount(byId[id] * f, 1) + ' kg CO₂e</td></tr>';
    }).join('');
    var total = ids.reduce(function (a, id) { return a + byId[id] * (state.factors[id] === undefined ? 1 : state.factors[id]); }, 0);
    out.textContent = 'Rough total: about ' + kit.amount(total, 0) + ' kg CO₂e displaced, from your own ' +
      kit.amount(ids.reduce(function (a, id) { return a + byId[id]; }, 0), 2) + ' kg and the factors above. ' +
      'Change a factor and the number moves — which is exactly why this is an estimate and not a measurement.';
  }

  function bindImpact() {
    el('co2-toggle').addEventListener('change', function () {
      state.co2 = this.checked;
      persist();
      paintImpact();
    });
    kit.on(el('co2-body'), 'input', '[data-co2]', function (e, input) {
      state.factors[input.dataset.co2] = Math.max(0, kit.num(input.value));
      persist();
      paintCo2();
    });
  }

  /* ---------------- editable price + weight tables ---------------- */

  function paintPriceTable() {
    var body = el('price-body');
    if (!body) return;
    body.innerHTML = state.prices.map(function (p, i) {
      return '<tr data-price="' + i + '">' +
        '<td><input type="text" value="' + escapeHtml(p.name) + '" data-pfield="name" aria-label="Material name ' + (i + 1) + '"></td>' +
        '<td class="num"><input class="p-price" type="number" min="-5000" max="20000" step="5" value="' + p.price + '" data-pfield="price" aria-label="Price for ' + escapeHtml(p.name) + '"></td>' +
        '<td>' + (p.unit === 'pc' ? 'per piece' : 'per kg') + '</td>' +
        '<td class="num"><input class="p-contam" type="number" min="0" max="100" step="1" value="' + p.contam + '" data-pfield="contam" aria-label="Contamination penalty percent for ' + escapeHtml(p.name) + '">% </td>' +
        '<td class="num"><input class="p-contam" type="number" min="0" max="500" step="0.5" value="' + p.min + '" data-pfield="min" aria-label="Minimum pickup weight for ' + escapeHtml(p.name) + '"></td></tr>';
    }).join('');
    if (el('as-of')) el('as-of').value = state.asOf;
    setText('price-updated', 'Default table: Lagos / Ibadan / Abuja / Onitsha buyer quotes, October 2026.');
  }

  function paintWeightTable() {
    var body = el('weight-body');
    if (!body) return;
    body.innerHTML = state.items.map(function (it, i) {
      var mat = priceOf(it.mat);
      return '<tr data-weight="' + i + '">' +
        '<td>' + escapeHtml(it.label) + '</td>' +
        '<td class="num"><input type="number" min="0.001" max="60" step="0.001" value="' + it.weight + '" data-wfield="weight" data-windex="' + i + '" aria-label="Weight per piece for ' + escapeHtml(it.label) + '"></td>' +
        '<td>' + escapeHtml(mat.name) + ' · ₦' + kit.amount(mat.price, 0) + (mat.unit === 'pc' ? '/pc' : '/kg') + '</td></tr>';
    }).join('');
  }

  function bindTables() {
    kit.on(el('price-body'), 'input', '[data-pfield]', function (e, input) {
      var i = Number(input.closest('tr').dataset.price);
      var field = input.dataset.pfield;
      if (field === 'name') state.prices[i].name = String(input.value);
      else state.prices[i][field] = kit.num(input.value);
      persist();
      refreshSelects();
      paint();
    });
    el('as-of').addEventListener('input', function () {
      state.asOf = this.value || state.asOf;
      persist();
      paintRecords();
      paintLedger();
    });
    el('reset-prices').addEventListener('click', function () {
      state.prices = clone(DEFAULT_PRICES);
      state.asOf = '2026-10-15';
      persist();
      paintPriceTable();
      refreshSelects();
      renderItems();
      paint();
      kit.toast('Default price table restored');
    });
    kit.on(el('weight-body'), 'input', '[data-wfield]', function (e, input) {
      var i = Number(input.dataset.windex);
      state.items[i].weight = Math.max(0.001, kit.num(input.value));
      persist();
      renderItems();
      paint();
    });
  }

  function refreshSelects() {
    var scaleSel = el('scale-material');
    var entrySel = el('e-material');
    var scaleValue = scaleSel ? scaleSel.value : '';
    var entryValue = entrySel ? entrySel.value : '';
    if (scaleSel) {
      scaleSel.innerHTML = state.prices.map(function (p) {
        return '<option value="' + p.id + '">' + escapeHtml(p.name) + '</option>';
      }).join('');
      scaleSel.value = state.prices.some(function (p) { return p.id === scaleValue; }) ? scaleValue : 'carton';
    }
    if (entrySel) {
      entrySel.innerHTML = state.prices.map(function (p) {
        return '<option value="' + p.id + '">' + escapeHtml(p.name) + ' — ₦' + kit.amount(p.price, 0) + (p.unit === 'pc' ? '/pc' : '/kg') + '</option>';
      }).join('');
      entrySel.value = state.prices.some(function (p) { return p.id === entryValue; }) ? entryValue : 'carton';
    }
  }

  /* ---------------- engine 7: group accounts ---------------- */

  function renderMembers() {
    var body = el('member-body');
    if (!body) return;
    body.innerHTML = state.members.map(function (m, i) {
      return '<tr data-member="' + i + '">' +
        '<td><input type="text" value="' + escapeHtml(m.name) + '" data-mfield="name" aria-label="Member name ' + (i + 1) + '"></td>' +
        '<td class="num"><input type="number" min="0" max="2000000" step="100" value="' + (m.contrib || 0) + '" data-mfield="contrib" aria-label="Naira into the float for ' + escapeHtml(m.name) + '"></td>' +
        '<td class="num"><input type="number" min="0" max="99999" step="0.5" value="' + (m.kg || 0) + '" data-mfield="kg" aria-label="Kilograms brought by ' + escapeHtml(m.name) + '"></td>' +
        '<td class="num"><input type="number" min="0" max="5000000" step="100" value="' + (m.earned || 0) + '" data-mfield="earned" aria-label="Naira collected on the material of ' + escapeHtml(m.name) + '"></td>' +
        '<td><button class="row-del row-del-light" type="button" data-member-del="' + i + '" aria-label="Remove ' + escapeHtml(m.name) + '">×</button></td></tr>';
    }).join('');
  }

  function computeGroup() {
    var total = state.members.reduce(function (a, m) { return a + kit.num(m.earned); }, 0);
    var kgTotal = state.members.reduce(function (a, m) { return a + kit.num(m.kg); }, 0);
    var floatTotal = state.members.reduce(function (a, m) { return a + kit.num(m.contrib); }, 0);
    var mode = state.group.mode;
    var keepPct = kit.clamp(kit.num(state.group.keep), 0, 100);
    var kept = mode === 'kept' ? total * keepPct / 100 : 0;
    var distributable = total - kept;
    var n = state.members.length;
    var shares = state.members.map(function (m) {
      if (mode === 'equal') return n ? distributable / n : 0;
      return kgTotal > 0 ? distributable * kit.num(m.kg) / kgTotal : 0;
    });
    var shareSum = shares.reduce(function (a, b) { return a + b; }, 0);
    return {
      total: total, kgTotal: kgTotal, floatTotal: floatTotal, kept: kept, keepPct: mode === 'kept' ? keepPct : 0,
      distributable: distributable, shares: shares, shareSum: shareSum,
      audit: shareSum - distributable, perKg: kgTotal > 0 ? total / kgTotal : 0,
      largest: shares.length ? Math.max.apply(null, shares) : 0, mode: mode, n: n
    };
  }

  function paintGroup() {
    var g = computeGroup();
    setFigure('g-total', price(g.total));
    setText('g-total-note', (state.group.name || 'the group') + ' · ' + (state.group.cycle || 'this cycle') + ' · ' + g.n + ' member' + (g.n === 1 ? '' : 's'));
    setFigure('g-kg', kg(g.kgTotal));
    setFigure('g-float', price(g.floatTotal));
    setFigure('g-kept', price(g.kept) + (g.mode === 'kept' ? ' (' + kit.pct(g.keepPct, 0) + ')' : ' (method keeps none)'));
    setFigure('g-distributable', price(g.distributable));
    setFigure('g-audit', price(g.audit) + (Math.abs(g.audit) < 1 ? ' — reconciles' : ' — check the shares'));
    setFigure('g-members-n', String(g.n));
    setFigure('g-per-kg', g.kgTotal > 0 ? price(g.perKg) + '/kg' : '—');
    setFigure('g-largest', price(g.largest));

    var body = el('share-body');
    var foot = el('share-foot');
    if (body) {
      if (!state.members.length) {
        body.innerHTML = '<tr><td colspan="5" class="tiny">No members yet.</td></tr>';
        if (foot) foot.innerHTML = '';
      } else {
        body.innerHTML = state.members.map(function (m, i) {
          var bal = g.shares[i] - kit.num(m.earned);
          return '<tr><td>' + escapeHtml(m.name || 'Member ' + (i + 1)) + '</td>' +
            '<td class="num">' + kit.amount(m.kg, 1) + '</td>' +
            '<td class="num">' + price(m.earned) + '</td>' +
            '<td class="num"><b>' + price(g.shares[i]) + '</b></td>' +
            '<td class="num ' + (bal < 0 ? 'neg' : 'pos') + '">' + signed(bal, function (v) { return price(v); }) + '</td></tr>';
        }).join('');
        if (foot) {
          foot.innerHTML = '<tr><td>Totals</td><td class="num">' + kit.amount(g.kgTotal, 1) + '</td><td class="num">' + price(g.total) +
            '</td><td class="num">' + price(g.shareSum) + '</td><td class="num">' + signed(g.shareSum - g.total, function (v) { return price(v); }) +
            (g.mode === 'kept' ? ' (kept for the group)' : '') + '</td></tr>';
        }
      }
    }

    if (!g.n) {
      verdict('group-verdict', 'group-title', 'group-body', 'Add members', 'The share-out and the audit line are what you read at the meeting.', '');
    } else if (Math.abs(g.audit) > 1) {
      verdict('group-verdict', 'group-title', 'group-body', 'Shares do not reconcile',
        'The shares add to ' + price(g.shareSum) + ' against ' + price(g.distributable) + ' to distribute. Fix the weights before anyone is paid.', 'bad');
    } else if (g.mode === 'equal') {
      verdict('group-verdict', 'group-title', 'group-body', 'Equal shares: ' + price(g.largest) + ' each',
        'Every member takes ' + price(g.distributable / Math.max(1, g.n)) + ' whatever they brought. Members whose own material raised more than that are subsidising the rest — say it out loud before you vote, and show them the balance column.', g.floatTotal > 0 ? 'ok' : 'warn');
    } else if (g.mode === 'kept') {
      verdict('group-verdict', 'group-title', 'group-body', 'Group keeps ' + kit.pct(g.keepPct, 0) + ' = ' + price(g.kept),
        'That leaves ' + price(g.distributable) + ' shared by kilograms at ' + price(g.perKg) + '/kg. The kept slice is what buys sacks, rope and the tricycle tip next cycle — minute it as the group fund.', 'ok');
    } else {
      verdict('group-verdict', 'group-title', 'group-body', 'Per kilogram: ' + price(g.perKg) + '/kg',
        'Pooled ' + price(g.total) + ' over ' + kg(g.kgTotal) + '. Σ shares − Σ collected = ' + price(g.audit) +
        ', which is the line that ends an argument at the meeting. Float of ' + price(g.floatTotal) +
        ' is held separately and should be returned or spent in front of the group.', 'ok');
    }
  }

  function bindGroup() {
    kit.on(el('member-body'), 'input', '[data-mfield]', function (e, input) {
      var i = Number(input.closest('tr').dataset.member);
      var field = input.dataset.mfield;
      state.members[i][field] = field === 'name' ? String(input.value) : kit.num(input.value);
      persist();
      paintGroup();
    });
    kit.on(el('member-body'), 'click', '[data-member-del]', function (e, btn) {
      state.members.splice(Number(btn.dataset.memberDel), 1);
      persist();
      renderMembers();
      paintGroup();
      kit.toast('Member removed — the sheet recalculated');
    });
    el('add-member').addEventListener('click', function () {
      state.members.push({ name: 'New member', contrib: 0, kg: 0, earned: 0 });
      persist();
      renderMembers();
      paintGroup();
      var inputs = el('member-body').querySelectorAll('[data-mfield="name"]');
      if (inputs.length) { inputs[inputs.length - 1].focus(); inputs[inputs.length - 1].select(); }
    });
    el('group-reset').addEventListener('click', function () {
      state.members = clone(DEFAULT_MEMBERS);
      state.group = { name: "St. Mary's — facility recycling circle", cycle: 'Michaelmas term', mode: 'kg', keep: 10 };
      el('g-name').value = state.group.name;
      el('g-cycle').value = state.group.cycle;
      el('g-mode').value = 'kg';
      el('g-keep').value = 10;
      persist();
      renderMembers();
      paintGroup();
      kit.toast('Sample group restored');
    });
    ['g-name', 'g-cycle', 'g-mode', 'g-keep'].forEach(function (id) {
      var handler = function () {
        state.group.name = txt('g-name');
        state.group.cycle = txt('g-cycle');
        state.group.mode = txt('g-mode') || 'kg';
        state.group.keep = kit.clamp(kit.num(val('g-keep', 0)), 0, 100);
        persist();
        paintGroup();
      };
      el(id).addEventListener('input', handler);
      el(id).addEventListener('change', handler);
    });
    el('group-csv').addEventListener('click', function () {
      var g = computeGroup();
      kit.csv([['Group', state.group.name, 'Cycle', state.group.cycle, 'Method', g.mode === 'equal' ? 'equal shares' : (g.mode === 'kept' ? 'keep ' + g.keepPct + '% then per kg' : 'per kilogram')],
        ['Member', 'Kg brought', 'Naira into float', 'Naira collected on material', 'Share-out', 'Balance vs own material']]
        .concat(state.members.map(function (m, i) {
          return [m.name, round(m.kg, 2), m.contrib, round(m.earned, 0), round(g.shares[i], 0), round(g.shares[i] - kit.num(m.earned), 0)];
        })).concat([['TOTAL', round(g.kgTotal, 2), g.floatTotal, g.total, round(g.shareSum, 0), round(g.shareSum - g.total, 0)],
          ['Kept for the group', '', '', '', round(g.kept, 0), '']]),
        'recircle-group-shareout-' + new Date().toISOString().slice(0, 10) + '.csv');
    });
    el('group-print').addEventListener('click', function () {
      if (!state.members.length) { kit.toast('Add members first'); return; }
      kit.print();
    });
  }

  /* ---------------- worked example ---------------- */

  function paintWorked(load) {
    var host = el('worked');
    if (!host) return;
    var trip = computeTrip(load);
    var steps = [];
    var lineItems = [];
    load.groups.forEach(function (g) {
      g.items.forEach(function (i) {
        lineItems.push({ g: g, i: i });
      });
    });
    if (!lineItems.length) {
      steps.push('Nothing counted yet. A worked example appears as soon as the counter has weight in it.');
    } else {
      lineItems.slice(0, 6).forEach(function (row) {
        var i = row.i, g = row.g;
        if (i.override !== null) {
          steps.push(escapeHtml(i.label) + ': you weighed it — <b>' + kit.amount(i.override, 2) + ' kg</b> at <b>' + price(g.price) +
            '/' + (g.unit === 'pc' ? 'pc' : 'kg') + '</b> = <b>' + price(i.value) + '</b>. Your scale beats my average weight.');
        } else if (g.unit === 'pc') {
          steps.push(i.qty + ' × ' + escapeHtml(i.label) + ' at <b>' + price(g.price) + '</b> each = <b>' + price(i.value) +
            '</b> (' + kit.amount(i.qty * i.weight, 2) + ' kg of weight, priced per piece).');
        } else {
          steps.push(i.qty + ' × ' + escapeHtml(i.label) + ' at ' + (i.weight < 1 ? Math.round(i.weight * 1000) + ' g' : kit.amount(i.weight, 2) + ' kg') +
            ' each = <b>' + kit.amount(round(i.qty * i.weight, 3), 2) + ' kg</b> × <b>' + price(g.price) + '/kg</b> = <b>' + price(i.value) + '</b>.');
        }
      });
      steps.push('Sorted total: <b>' + kit.amount(load.totalKg, 2) + ' kg</b> worth <b>' + price(load.sorted) +
        '</b>. Dumped as mixed plastic and paper at ' + price(state.mixedRate) + '/kg, the same ' + kit.amount(load.mixedKg, 2) +
        ' kg fetches <b>' + price(load.mixed) + '</b> — sorting is worth <b>' + price(load.uplift) + '</b> (' + kit.pct(load.upliftPct, 1) + ') on one load.');
      steps.push('Monthly at ' + load.loads + ' loads: <b>' + price(load.month) + '</b> sorted, <b>' + price(load.mixed * load.loads) +
        '</b> mixed, a difference of <b>' + price(load.monthUplift) + '</b> for about twenty minutes of sorting a week.');
      steps.push('Hand it over wet or oily and the penalties in the price table take <b>' + price(load.penaltyAmount) +
        '</b> (' + kit.pct(load.penaltyPct, 1) + ' weighted), leaving <b>' + price(load.wet) + '</b>.');
      steps.push('Trip cost: ' + kit.amount(trip.distance, 1) + ' km × ' + price(trip.perKm) +
        '/km = <b>' + price(trip.tripCost) + '</b>.');
      steps.push('Break-even weight at the price on the table (' + price(trip.offered) + '/kg): ' + price(trip.tripCost) + ' ÷ ' +
        price(trip.offered) + ' = <b>' + kit.amount(trip.minKg, 2) + ' kg</b>. Your load is ' + kit.amount(load.totalKg, 2) +
        ' kg — <b>' + (trip.viable ? 'above' : 'below') + '</b> it by ' + kit.amount(Math.abs(trip.shortfall), 2) + ' kg, so ' +
        (trip.viable ? 'ask for the pickup' : 'combine with a neighbour or wait another week') + '.');
      if (trip.minKgOneMaterial) {
        steps.push('Same trip on one material only: ' + price(trip.tripCost) + ' ÷ ' + price(trip.bestRate) + '/kg = <b>' +
          kit.amount(trip.minKgOneMaterial, 2) + ' kg</b>. HDPE at ₦200/kg needs ' + kit.amount(trip.tripCost / 200, 2) +
          ' kg; mixed plastic at ₦45/kg needs ' + kit.amount(trip.tripCost / 45, 2) + ' kg.');
      }
      steps.push('If his scale reads ' + kit.amount(val('scale-buyer', 0), 2) + ' kg against your counted ' +
        kit.amount(load.totalKg, 2) + ' kg and he deducts a ' + kit.amount(val('scale-tare', 0), 2) +
        ' kg crate, you have lost <b>' + price(Math.abs(computeScale(load).lost)) + '</b> on this load — that is what the dispute flag in the ledger is for.');
    }
    host.innerHTML = steps.map(function (s) { return '<li>' + s + '</li>'; }).join('');
  }

  /* ---------------- paint orchestration ---------------- */

  function readLoadInputs() {
    state.mixedRate = Math.max(0, val('mixed-rate', 45));
    state.loadsPerMonth = Math.max(0, val('loads-month', 4));
    state.loadName = txt('load-name');
    persistPickup();
  }

  function paint() {
    readLoadInputs();
    var load = computeLoad();
    paintItemOutputs(load);
    paintCounter(load);
    paintWeek(load);
    paintSplit(load);
    paintTrip();
    paintScale(load);
    paintWorked(load);
    paintGroup();
    persist();
  }

  /* ---------------- bindings for live inputs ---------------- */

  var SKIP_ATTRS = ['qty', 'override', 'mfield', 'pfield', 'wfield', 'windex', 'calfield', 'co2', 'week'];

  function bindForms() {
    ['#load-form', '#pickup-form', '#scale-form', '#group-form'].forEach(function (sel) {
      var form = document.querySelector(sel);
      if (!form) return;
      kit.$$(sel + ' input, ' + sel + ' select').forEach(function (input) {
        var skip = SKIP_ATTRS.some(function (attr) { return Object.keys(input.dataset).indexOf(attr) >= 0; });
        if (skip) return;
        input.addEventListener('input', function () { paint(); });
        input.addEventListener('change', function () { paint(); });
      });
    });
  }

  function restoreInputs() {
    el('mixed-rate').value = state.mixedRate;
    el('loads-month').value = state.loadsPerMonth;
    el('load-name').value = state.loadName;
    if (state.pickupVals) {
      var p = state.pickupVals;
      if (p.address !== undefined) el('pick-address').value = p.address;
      if (p.vehicle) el('pick-vehicle').value = p.vehicle;
      if (p.distance !== undefined && p.distance !== '') el('pick-distance').value = p.distance;
      if (p.perkm !== undefined && p.perkm !== '') el('pick-perkm').value = p.perkm;
      if (p.offered !== undefined) el('pick-offered').value = p.offered;
      if (p.share !== undefined && p.share !== '') el('pick-share').value = p.share;
      if (p.note !== undefined) el('pick-note').value = p.note;
      el('pick-sorted').checked = !!p.sorted;
      el('scale-tol').value = state.tol;
    }
    el('scale-tol').value = state.tol;
    el('g-name').value = state.group.name;
    el('g-cycle').value = state.group.cycle;
    el('g-mode').value = state.group.mode;
    el('g-keep').value = state.group.keep;
    el('co2-toggle').checked = state.co2;
  }

  /* ---------------- boot ---------------- */

  kit.boot({
    ready: function () {
      hydrate();
      renderItems();
      renderWeekForm();
      renderCalendar();
      renderCalendarSelect();
      renderMembers();
      paintPriceTable();
      paintWeightTable();
      restoreInputs();
      bindCounter();
      bindWeek();
      bindCalendar();
      bindPickup();
      bindScale();
      bindLedger();
      bindGroup();
      bindTables();
      bindImpact();
      bindForms();
      paintRecords();
      paint();
      paintLedger();
    }
  });
})();
