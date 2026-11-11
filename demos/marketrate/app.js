/* MarketRate — behaviour layer. No dependencies beyond ../_kit/kit.js, no build step.
   Everything on this page is arithmetic on the numbers in the board you are editing. */
(function (global) {
  'use strict';

  var kit = global.kit;
  var $ = kit.$, $$ = kit.$$;

  /* ------------------------------------------------------------------ helpers */

  function esc(text) {
    var node = document.createElement('div');
    node.textContent = text === null || text === undefined ? '' : String(text);
    return node.innerHTML;
  }

  function v(id) {
    var el = $(id);
    return el ? kit.num(el.value) : 0;
  }

  function money(value, dp) { return kit.money(value, 'NGN', dp === undefined ? 0 : dp); }
  function qty(value, dp) { return kit.amount(value, dp === undefined ? 0 : dp); }

  function setv(id, text) {
    var el = $(id);
    if (!el) return el;
    if (String(el.textContent) !== String(text)) {
      el.textContent = text;
      el.classList.remove('tick');
      void el.offsetWidth;
      el.classList.add('tick');
    }
    return el;
  }

  function setplain(id, text) { var el = $(id); if (el) el.textContent = text; return el; }

  function verdict(box, title, body, kind, titleId, bodyId) {
    var el = $(box);
    if (!el) return;
    el.classList.remove('ok', 'warn', 'bad');
    if (kind) el.classList.add(kind);
    var h = $(titleId || '#x-none'), p = $(bodyId || '#x-none');
    if (h) h.textContent = title;
    if (p) p.textContent = body;
  }

  var MONTHS = ['January', 'February', 'March', 'April', 'May', 'June', 'July',
    'August', 'September', 'October', 'November', 'December'];
  var SHORT = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];

  function isoToday() {
    var d = new Date();
    return d.getFullYear() + '-' + pad(d.getMonth() + 1) + '-' + pad(d.getDate());
  }
  function pad(n) { return (n < 10 ? '0' : '') + n; }
  function monthOf(iso) {
    var m = parseInt(String(iso || '').slice(5, 7), 10);
    return isNaN(m) ? new Date().getMonth() : kit.clamp(m - 1, 0, 11);
  }

  function percentile(list, p) {
    var a = list.map(Number).filter(function (n) { return !isNaN(n); }).sort(function (x, y) { return x - y; });
    if (!a.length) return 0;
    if (a.length === 1) return a[0];
    var i = (a.length - 1) * p, lo = Math.floor(i), hi = Math.ceil(i);
    return a[lo] + (a[hi] - a[lo]) * (i - lo);
  }

  function deepCopy(src) {
    return JSON.parse(JSON.stringify(src));
  }

  /* ------------------------------------------------------------------ reference data
     Indicative planning figures, one-off curated, deliberately rounded. They are
     a starting shape for the arithmetic and are meant to be replaced with what you
     actually saw at the market this morning. No source is cited that this page
     cannot stand behind; the honest answer is "go and ask the market union". */

  var REF_UNITS = [
    { name: 'crate', kg: 22, where: 'tomato, pepper, cucumber, melon — the wooden crate' },
    { name: 'mudu', kg: 0.35, where: 'calabash measure for beans, grain, egusi' },
    { name: 'basket', kg: 8, where: 'woven basket of leaf, pepper, mango, fish' },
    { name: 'bag', kg: 50, where: 'rice, maize, onion, flour, beans — the 50 kg bag' },
    { name: 'sack', kg: 100, where: 'bulk grain and tubers at the northern gates' },
    { name: 'keg', kg: 25, where: 'garri, groundnut, flour in the plastic keg' },
    { name: 'bunch', kg: 25, where: 'plantain and banana bunch' },
    { name: 'tubu', kg: 30, where: 'a cluster of yam tubers' },
    { name: 'carton', kg: 10, where: 'frozen fish carton' },
    { name: 'calabash', kg: 12, where: 'egusi, locust bean, dried fish' },
    { name: 'flat', kg: 1.85, where: 'a flat of 30 eggs' },
    { name: 'bird', kg: 2.2, where: 'one live or dressed chicken' },
    { name: 'litre', kg: 1, where: 'oil, water, milk — 1 kg for planning' },
    { name: 'kg', kg: 1, where: 'kilogram, when someone finally quotes it' }
  ];

  var REF_SPOIL = [
    { cls: 'leaf', loss: 25, note: 'ugu, bitter leaf, spinach — sells or wilts inside a day' },
    { cls: 'soft', loss: 20, note: 'tomato, melon, ripe mango, watermelon — hot hauls lose a fifth' },
    { cls: 'semi', loss: 12, note: 'pepper, garden egg, banana, onion — bruises, does not melt' },
    { cls: 'tuber', loss: 10, note: 'yam, cassava, potato — sprouting and rot over weeks' },
    { cls: 'fresh', loss: 4, note: 'eggs, chicken, fish — breakage and cold-chain loss' },
    { cls: 'grain', loss: 4, note: 'maize, rice, millet, beans — weevils and theft, not heat' },
    { cls: 'dry', loss: 2, note: 'garri, flour, palm oil, egusi — stable if kept dry' }
  ];

  var REF_MARKETS = [
    { name: 'Zaria farm gate', city: 'Zaria', mult: 1, tier: 'farm', role: 'reference farm-gate desk — the board’s farm prices are quoted here' },
    { name: 'Baguda gate', city: 'Zaria', mult: 0.94, tier: 'farm', role: 'assembly market, cheapest load in the state' },
    { name: 'Kano farm gate', city: 'Kano', mult: 0.96, tier: 'farm', role: 'northern aggregation point' },
    { name: 'Nsukka line gate', city: 'Nsukka', mult: 0.92, tier: 'farm', role: 'root and pepper onto the Enugu road' },
    { name: 'Sabon Tade', city: 'Zaria', mult: 0.92, tier: 'whole', role: 'Zaria wholesale day market' },
    { name: 'Berkiji Ojo', city: 'Lagos', mult: 0.86, tier: 'whole', role: 'trucks unload here before the city marks it up' },
    { name: 'Agbowo', city: 'Ibadan', mult: 0.82, tier: 'whole', role: 'western-belt wholesale' },
    { name: 'Dei-Armants', city: 'Abuja', mult: 0.92, tier: 'whole', role: 'wholesale for the FCT satellite towns' },
    { name: 'Nkwo Agu', city: 'Onitsha', mult: 0.94, tier: 'whole', role: 'east-side wholesale' },
    { name: 'Munsu', city: 'Kano', mult: 0.9, tier: 'whole', role: 'industrial-route wholesale' },
    { name: 'Okpara', city: 'Enugu', mult: 0.9, tier: 'whole', role: 'en-route wholesale before Ogbete' },
    { name: 'Mile 12', city: 'Lagos', mult: 1, tier: 'retail', role: 'reference city retail desk' },
    { name: 'Oshodi TBS', city: 'Lagos', mult: 0.9, tier: 'retail', role: 'fast, noisy, cheaper on grade' },
    { name: 'Ketu Owo-de', city: 'Lagos', mult: 0.94, tier: 'retail', role: 'north-east Lagos residential' },
    { name: 'Bodija', city: 'Ibadan', mult: 0.96, tier: 'retail', role: 'planned market, moderate rents' },
    { name: 'Dugbe', city: 'Ibadan', mult: 0.9, tier: 'retail', role: 'city-centre resale' },
    { name: 'Wuse Market', city: 'Abuja', mult: 1.06, tier: 'retail', role: 'highest rents in the city, best cold chain' },
    { name: 'Garki Market', city: 'Abuja', mult: 1, tier: 'retail', role: 'second city-centre desk' },
    { name: 'Onitsha Main Market', city: 'Onitsha', mult: 1.02, tier: 'retail', role: 'largest goods flow in the south-east' },
    { name: 'Ogbete', city: 'Enugu', mult: 0.96, tier: 'retail', role: 'coal-city retail' },
    { name: 'Kurmi', city: 'Kano', mult: 0.96, tier: 'retail', role: 'Kano city retail' },
    { name: 'Makurdi Central', city: 'Makurdi', mult: 0.94, tier: 'retail', role: 'Benue’s main produce market' },
    { name: 'Akure Oba', city: 'Akure', mult: 0.9, tier: 'retail', role: 'south-west aggregator' },
    { name: 'Rumuokoro', city: 'Port Harcourt', mult: 1.04, tier: 'retail', role: 'refinery-city retail' },
    { name: 'Okhua', city: 'Benin City', mult: 0.88, tier: 'retail', role: 'middle-belt resale' },
    { name: 'Jos Central', city: 'Jos', mult: 0.92, tier: 'retail', role: 'plateau retail' },
    { name: 'Maiduguri Post', city: 'Maiduguri', mult: 0.88, tier: 'retail', role: 'north-east retail' }
  ];

  var REF_BOARD = [
    { id: 'tomato', name: 'Tomato', note: 'mixed varieties, dry-season load', unit: 'crate', kg: 22, farm: 9000, whole: 17500, retail: 28000, lean: 3, cls: 'soft', mk: ['Zaria farm gate', 'Mile 12', 'Bodija'] },
    { id: 'pepper', name: 'Pepper (rodo)', note: 'fresh, red, Akure-type', unit: 'basket', kg: 8, farm: 6500, whole: 11000, retail: 17000, lean: 5, cls: 'semi', mk: ['Nsukka line gate', 'Mile 12', 'Akure Oba'] },
    { id: 'onion', name: 'Onion (bulb)', note: 'imported + local mix', unit: 'bag', kg: 50, farm: 42000, whole: 55000, retail: 78000, lean: 1, cls: 'semi', mk: ['Kano farm gate', 'Mile 12', 'Kurmi'] },
    { id: 'rice', name: 'Rice (local, milled)', note: '7032-type, 50 kg bag', unit: 'bag', kg: 50, farm: 58000, whole: 66000, retail: 78000, lean: 4, cls: 'grain', mk: ['Makurdi Central', 'Wuse Market', 'Bodija'] },
    { id: 'garri', name: 'Garri (white, fine)', note: 'cassava, dry-fried', unit: 'keg', kg: 25, farm: 12000, whole: 15000, retail: 19000, lean: 8, cls: 'dry', mk: ['Nsukka line gate', 'Okhua', 'Rumuokoro'] },
    { id: 'maize', name: 'Maize (grain)', note: 'for feed and mills', unit: 'bag', kg: 50, farm: 34000, whole: 41000, retail: 50000, lean: 8, cls: 'grain', mk: ['Baguda gate', 'Munsu', 'Dei-Armants'] },
    { id: 'yam', name: 'Yam (Puna)', note: 'cluster of tubers', unit: 'tubu', kg: 30, farm: 24000, whole: 32000, retail: 44000, lean: 1, cls: 'tuber', mk: ['Nsukka line gate', 'Wuse Market', 'Mile 12'] },
    { id: 'cassava', name: 'Cassava (fresh)', note: 'for garri and flour', unit: 'bag', kg: 50, farm: 14000, whole: 19000, retail: 26000, lean: 2, cls: 'tuber', mk: ['Okhua', 'Agbowo', 'Rumuokoro'] },
    { id: 'plantain', name: 'Plantain', note: 'cooked type, bunch', unit: 'bunch', kg: 25, farm: 9000, whole: 13000, retail: 18500, lean: 7, cls: 'semi', mk: ['Nsukka line gate', 'Berkiji Ojo', 'Garki Market'] },
    { id: 'egusi', name: 'Egusi (seed)', note: 'dried, in shell', unit: 'calabash', kg: 12, farm: 26000, whole: 32000, retail: 40000, lean: 9, cls: 'dry', mk: ['Baguda gate', 'Makurdi Central', 'Wuse Market'] },
    { id: 'groundnut', name: 'Groundnut (shelled)', note: 'Kano belt', unit: 'keg', kg: 25, farm: 40000, whole: 48000, retail: 58000, lean: 6, cls: 'grain', mk: ['Kano farm gate', 'Kurmi', 'Sabon Tade'] },
    { id: 'beans', name: 'Beans (brown honey)', note: 'also sold per mudu', unit: 'bag', kg: 50, farm: 78000, whole: 92000, retail: 105000, lean: 6, cls: 'grain', mk: ['Baguda gate', 'Mile 12', 'Ogbete'] },
    { id: 'palmoil', name: 'Palm oil (red)', note: '25 litre keg ≈ 22.7 kg', unit: 'keg', kg: 22.7, farm: 46000, whole: 54000, retail: 64000, lean: 10, cls: 'dry', mk: ['Okhua', 'Nkwo Agu', 'Rumuokoro'] },
    { id: 'eggs', name: 'Eggs (flat of 30)', note: 'hybrid, medium', unit: 'flat', kg: 1.85, farm: 5200, whole: 6000, retail: 7600, lean: 11, cls: 'fresh', mk: ['Kano farm gate', 'Bodija', 'Oshodi TBS'] },
    { id: 'chicken', name: 'Chicken (broiler)', note: 'live, dressed weight', unit: 'bird', kg: 2.2, farm: 6500, whole: 7800, retail: 10000, lean: 11, cls: 'fresh', mk: ['Agbowo', 'Kurmi', 'Wuse Market'] },
    { id: 'catfish', name: 'Catfish (fresh)', note: 'pond, 10 kg basket', unit: 'basket', kg: 10, farm: 22000, whole: 27000, retail: 34000, lean: 0, cls: 'fresh', mk: ['Agbowo', 'Berkiji Ojo', 'Rumuokoro'] },
    { id: 'titus', name: 'Titus (frozen mackerel)', note: 'carton, graded', unit: 'carton', kg: 10, farm: 26000, whole: 31000, retail: 38000, lean: 2, cls: 'fresh', mk: ['Berkiji Ojo', 'Mile 12', 'Ketu Owo-de'] },
    { id: 'ugu', name: 'Ugu / bitter leaf', note: 'basket, cut same morning', unit: 'basket', kg: 4, farm: 3000, whole: 4500, retail: 7000, lean: 3, cls: 'leaf', mk: ['Okhua', 'Dugbe', 'Mile 12'] },
    { id: 'melon', name: 'Watermelon', note: 'crate, mixed sizes', unit: 'crate', kg: 25, farm: 7000, whole: 10000, retail: 15000, lean: 5, cls: 'soft', mk: ['Baguda gate', 'Ketu Owo-de', 'Garki Market'] },
    { id: 'mango', name: 'Mango (Julie)', note: 'basket, peak-season grade', unit: 'basket', kg: 12, farm: 9000, whole: 13000, retail: 19000, lean: 4, cls: 'soft', mk: ['Kano farm gate', 'Jos Central', 'Oshodi TBS'] },
    { id: 'flour', name: 'Cassava flour (high grade)', note: 'bag, sealed', unit: 'bag', kg: 50, farm: 46000, whole: 54000, retail: 64000, lean: 9, cls: 'dry', mk: ['Okhua', 'Agbowo', 'Wuse Market'] },
    { id: 'millet', name: 'Millet (sanmina)', note: 'bag, northern', unit: 'bag', kg: 50, farm: 32000, whole: 38000, retail: 46000, lean: 8, cls: 'grain', mk: ['Maiduguri Post', 'Munsu', 'Kurmi'] }
  ];

  /* inter-city road distances used to pre-fill the trip; obviously indicative */
  var DIST = {
    'Lagos|Zaria': 780, 'Ibadan|Lagos': 130, 'Lagos|Abuja': 760, 'Lagos|Onitsha': 660,
    'Lagos|Kano': 840, 'Lagos|Port Harcourt': 660, 'Lagos|Enugu': 740, 'Lagos|Makurdi': 470,
    'Lagos|Jos': 700, 'Lagos|Akure': 400, 'Lagos|Benin City': 320, 'Lagos|Maiduguri': 1100,
    'Lagos|Nsukka': 700, 'Ibadan|Zaria': 700, 'Ibadan|Abuja': 630, 'Ibadan|Onitsha': 560,
    'Ibadan|Akure': 300, 'Ibadan|Kano': 720, 'Ibadan|Jos': 600, 'Ibadan|Makurdi': 540,
    'Ibadan|Enugu': 640, 'Ibadan|Port Harcourt': 500, 'Ibadan|Benin City': 330,
    'Zaria|Kano': 45, 'Zaria|Abuja': 210, 'Zaria|Jos': 190, 'Zaria|Onitsha': 600,
    'Zaria|Enugu': 660, 'Zaria|Makurdi': 430, 'Zaria|Maiduguri': 700, 'Zaria|Nsukka': 560,
    'Abuja|Jos': 180, 'Abuja|Makurdi': 200, 'Abuja|Enugu': 380, 'Abuja|Onitsha': 430,
    'Abuja|Kano': 330, 'Abuja|Maiduguri': 620, 'Abuja|Nsukka': 400, 'Abuja|Lagos': 760,
    'Kano|Maiduguri': 480, 'Kano|Nsukka': 620, 'Kano|Jos': 250, 'Onitsha|Enugu': 60,
    'Onitsha|Nsukka': 85, 'Onitsha|Port Harcourt': 250, 'Onitsha|Benin City': 210,
    'Onitsha|Maiduguri': 900, 'Enugu|Nsukka': 30, 'Enugu|Port Harcourt': 60,
    'Port Harcourt|Akure': 420, 'Benin City|Port Harcourt': 200, 'Makurdi|Jos': 120,
    'Akure|Benin City': 260, 'Makurdi|Nsukka': 400, 'Jos|Maiduguri': 480
  };

  function distanceBetween(aCity, bCity) {
    if (!aCity || !bCity) return null;
    if (aCity === bCity) return 0;
    return DIST[aCity + '|' + bCity] !== undefined ? DIST[aCity + '|' + bCity]
      : (DIST[bCity + '|' + aCity] !== undefined ? DIST[bCity + '|' + aCity] : null);
  }

  /* ------------------------------------------------------------------ stores */

  var STORE = {
    board: kit.store('marketrate:board', null),
    units: kit.store('marketrate:units', null),
    spoil: kit.store('marketrate:spoil', null),
    markets: kit.store('marketrate:markets', null),
    settings: kit.store('marketrate:settings', null),
    deals: kit.store('marketrate:deals', null),
    log: kit.store('marketrate:log', [])
  };

  var state = {
    rows: [], units: [], spoil: [], markets: [], deals: [], log: [],
    settings: { rate: 244, amp: 0.28 },
    asof: isoToday(),
    dirty: false
  };

  function loadRuleList(store, ref, key) {
    var saved = store.read();
    state[key] = (saved && saved.length === ref.length) ? deepCopy(saved) : deepCopy(ref);
  }

  function refRow(id) {
    for (var i = 0; i < REF_BOARD.length; i++) if (REF_BOARD[i].id === id) return REF_BOARD[i];
    return null;
  }

  var EDIT_FIELDS = ['unit', 'kg', 'farm', 'whole', 'retail', 'lean'];

  function refreshEditedFlags() {
    state.rows.forEach(function (r) {
      var ref = refRow(r.id);
      r.edited = !ref || EDIT_FIELDS.some(function (f) { return String(r[f]) !== String(ref[f]); });
    });
  }

  function loadBoard() {
    var saved = STORE.board.read();
    state.asof = (saved && saved.asof) || isoToday();
    state.rows = deepCopy(REF_BOARD).map(function (row) {
      var o = saved && saved.rows ? saved.rows[row.id] : null;
      if (o) {
        EDIT_FIELDS.forEach(function (f) {
          if (o[f] !== undefined && o[f] !== null && o[f] !== '') row[f] = o[f];
        });
      }
      return row;
    });
    refreshEditedFlags();
    state.dirty = false;
  }

  function loadDeals() {
    var saved = STORE.deals.read();
    state.deals = (saved && saved.length) ? deepCopy(saved) : defaultDeals();
  }

  function defaultDeals() {
    return [
      { label: 'Roadside mudu, counted by hand', price: 1100, unit: 'mudu' },
      { label: 'Full bag off the truck', price: 110000, unit: 'bag' }
    ];
  }

  function saveDeals() { STORE.deals.write(state.deals); }

  /* ------------------------------------------------------------------ lookups */

  function row(id) {
    for (var i = 0; i < state.rows.length; i++) if (state.rows[i].id === id) return state.rows[i];
    return state.rows[0];
  }

  function unit(name) {
    for (var i = 0; i < state.units.length; i++) if (state.units[i].name === name) return state.units[i];
    return null;
  }

  function unitKg(name) {
    var u = unit(name);
    return u ? kit.num(u.kg) : 0;
  }

  var IRREGULAR = { mudu: 'mudu', kg: 'kg', calabash: 'calabashes', litre: 'litres' };
  function upl(count, name) {
    var n = Math.round(Number(count) || 0);
    if (n === 1) return String(name);
    return IRREGULAR[name] || (String(name) + 's');
  }

  function rowKg(r) {
    var kg = kit.num(r.kg);
    return kg > 0 ? kg : unitKg(r.unit);
  }

  function spoilOf(cls) {
    for (var i = 0; i < state.spoil.length; i++) if (state.spoil[i].cls === cls) return kit.num(state.spoil[i].loss);
    return 10;
  }

  function market(name) {
    for (var i = 0; i < state.markets.length; i++) if (state.markets[i].name === name) return state.markets[i];
    return state.markets[0];
  }

  function marketsIn(city) {
    return state.markets.filter(function (m) { return m.city === city; });
  }

  function cities() {
    var seen = {}, out = [];
    state.markets.forEach(function (m) { if (!seen[m.city]) { seen[m.city] = 1; out.push(m.city); } });
    return out.sort();
  }

  /* tier price of a commodity at a specific market: board figure × that market’s
     cost-of-doing-business multiplier */
  function priceAt(r, m) {
    var tier = m.tier === 'farm' ? 'farm' : (m.tier === 'whole' ? 'whole' : 'retail');
    return kit.num(r[tier]) * kit.num(m.mult);
  }

  /* seasonal index: 1 ± swing, peaking in the commodity’s lean month */
  function indexFor(r, month) {
    var amp = kit.num(state.settings.amp);
    var lean = kit.clamp(Math.round(kit.num(r.lean)), 0, 11);
    var m = kit.clamp(Math.round(kit.num(month)), 0, 11);
    return 1 + amp * Math.cos((2 * Math.PI * (m - lean)) / 12);
  }

  function seasonSeries(r) {
    var out = [];
    for (var m = 0; m < 12; m++) out.push(Number(indexFor(r, m).toFixed(3)));
    return out;
  }

  /* ------------------------------------------------------------------ selects */

  function fill(el, list, value, labelFn, valueFn) {
    if (!el) return;
    el.innerHTML = list.map(function (item) {
      var val = valueFn ? valueFn(item) : item;
      return '<option value="' + esc(val) + '">' + esc(labelFn ? labelFn(item) : item) + '</option>';
    }).join('');
    if (value !== undefined && value !== null) el.value = value;
    if (el.selectedIndex < 0 && list.length) el.selectedIndex = 0;
  }

  function fillCommoditySelects() {
    var names = state.rows.slice().sort(function (a, b) { return a.name < b.name ? -1 : 1; });
    ['#deal-commodity', '#h-commodity', '#s-commodity', '#f-commodity'].forEach(function (sel) {
      var el = $(sel);
      var keep = el && el.value ? el.value : preferredId(sel);
      fill(el, names, keep, function (r) { return r.name + ' — ' + r.unit + ' (' + qty(rowKg(r), rowKg(r) < 10 ? 2 : 0) + ' kg)'; }, function (r) { return r.id; });
    });
    /* the log keys on the commodity name so trades and board rows meet */
    var log = $('#l-commodity');
    var logKeep = log && log.value ? log.value : 'Beans (brown honey)';
    fill(log, names, logKeep, function (r) { return r.name; }, function (r) { return r.name; });
  }

  function preferredId(sel) {
    if (sel === '#deal-commodity') return 'beans';
    if (sel === '#s-commodity') return 'maize';
    if (sel === '#f-commodity') return 'rice';
    return 'tomato';
  }

  function fillMarketSelects() {
    var buy = state.markets.filter(function (m) { return m.tier === 'farm' || m.tier === 'whole'; });
    var sell = state.markets.filter(function (m) { return m.tier === 'whole' || m.tier === 'retail'; });
    fillGrouped($('#h-origin'), buy, currentOrDefault('#h-origin', 'Zaria farm gate'), true);
    fillGrouped($('#h-dest'), sell, currentOrDefault('#h-dest', 'Mile 12'), true);
    fillGrouped($('#l-market'), state.markets, currentOrDefault('#l-market', 'Mile 12'), false);
    fill($('#f-city'), cities(), currentOrDefault('#f-city', 'Abuja'));
    fill($('#f-unit'), state.units, currentOrDefault('#f-unit', 'bag'), function (u) { return u.name + ' (' + qty(u.kg, u.kg < 10 ? 2 : 0) + ' kg)'; }, function (u) { return u.name; });
    fill($('#l-unit'), state.units, currentOrDefault('#l-unit', 'bag'), function (u) { return u.name; }, function (u) { return u.name; });
  }

  function currentOrDefault(sel, fallback) {
    var el = $(sel);
    return el && el.value ? el.value : fallback;
  }

  function fillGrouped(el, list, keep, withTier) {
    if (!el) return;
    var byCity = {}, order = [];
    list.forEach(function (m) {
      if (!byCity[m.city]) { byCity[m.city] = []; order.push(m.city); }
      byCity[m.city].push(m);
    });
    order.sort();
    el.innerHTML = order.map(function (city) {
      return '<optgroup label="' + esc(city) + '">' + byCity[city].map(function (m) {
        return '<option value="' + esc(m.name) + '">' + esc(m.name + (withTier ? ' · ' + m.tier + ' · ×' + kit.num(m.mult).toFixed(2) : '')) + '</option>';
      }).join('') + '</optgroup>';
    }).join('');
    if (keep) el.value = keep;
  }

  function fillMonthSelects() {
    var names = MONTHS.map(function (n, i) { return { n: n, i: i }; });
    fill($('#st-buy-month'), names, 10, function (m) { return m.n; }, function (m) { return m.i; });
    fill($('#st-sell-month'), names, 2, function (m) { return m.n; }, function (m) { return m.i; });
  }

  /* ------------------------------------------------------------------ 1. board */

  function boardFilters() {
    return {
      q: String($('#board-q') ? $('#board-q').value : '').trim().toLowerCase(),
      sort: $('#board-sort') ? $('#board-sort').value : 'name'
    };
  }

  function perKg(r) {
    var kg = rowKg(r);
    return kg > 0 ? kit.num(r.retail) / kg : 0;
  }

  function spreadOf(r) {
    var farm = kit.num(r.farm);
    return farm > 0 ? kit.num(r.retail) / farm : 0;
  }

  function gainOf(r) { return kit.num(r.retail) - kit.num(r.farm); }

  function renderBoard() {
    var body = $('#board-body');
    if (!body) return;
    var f = boardFilters();
    var list = state.rows.slice();
    list.sort(function (a, b) {
      if (f.sort === 'kg') return perKg(b) - perKg(a);
      if (f.sort === 'spread') return spreadOf(b) - spreadOf(a);
      if (f.sort === 'gain') return gainOf(b) - gainOf(a);
      return a.name < b.name ? -1 : 1;
    });
    var unitNames = state.units.map(function (u) { return u.name; });
    body.innerHTML = list.map(function (r) {
      var hit = f.q && (r.name + ' ' + r.note + ' ' + r.unit + ' ' + r.mk.join(' ')).toLowerCase().indexOf(f.q) > -1;
      var shown = !f.q || hit;
      return '<tr data-id="' + esc(r.id) + '"' + (r.edited ? ' class="is-edited' + (hit ? ' is-hit' : '') + '"' : (hit ? ' class="is-hit"' : '')) +
        (shown ? '' : ' hidden') + '>' +
        '<th scope="row">' + esc(r.name) + '<span class="b-note">' + esc(r.note) + '</span></th>' +
        '<td><span class="unit-cell"><select class="cell sel" data-f="unit" aria-label="Unit for ' + esc(r.name) + '">' +
          unitNames.map(function (n) { return '<option value="' + esc(n) + '"' + (n === r.unit ? ' selected' : '') + '>' + esc(n) + '</option>'; }).join('') +
        '</select><input class="cell kg-cell" type="number" min="0.05" max="500" step="0.05" value="' + esc(r.kg) + '" data-f="kg" aria-label="Kilograms per unit for ' + esc(r.name) + '"></span></td>' +
        '<td class="num"><input class="cell" type="number" min="0" max="5000000" step="500" value="' + esc(r.farm) + '" data-f="farm" aria-label="Farm gate price per unit for ' + esc(r.name) + '"></td>' +
        '<td class="num"><input class="cell" type="number" min="0" max="5000000" step="500" value="' + esc(r.whole) + '" data-f="whole" aria-label="Wholesale price per unit for ' + esc(r.name) + '"></td>' +
        '<td class="num"><input class="cell" type="number" min="0" max="5000000" step="500" value="' + esc(r.retail) + '" data-f="retail" aria-label="City retail price per unit for ' + esc(r.name) + '"></td>' +
        '<td class="num derived kg-out">' + esc(money(perKg(r))) + '</td>' +
        '<td class="num derived sp-out">' + esc(spreadOf(r).toFixed(2) + '×') + '</td>' +
        '<td>' + esc(MONTHS[kit.clamp(Math.round(r.lean), 0, 11)]) + '</td>' +
        '<td class="b-mk">' + esc(r.mk.join(' · ')) + '</td>' +
        '</tr>';
    }).join('');
    paintBoardFoot();
    paintBoardStatus();
  }

  function paintBoardFoot() {
    var foot = $('#board-foot');
    if (!foot) return;
    var kgList = state.rows.map(perKg).sort(function (a, b) { return a - b; });
    var spList = state.rows.map(spreadOf).sort(function (a, b) { return a - b; });
    var sum = function (f) { return state.rows.reduce(function (t, r) { return t + kit.num(r[f]); }, 0); };
    foot.innerHTML = '<tr><th scope="row">' + esc(qty(state.rows.length)) + ' commodities</th>' +
      '<td class="tiny">board totals</td>' +
      '<td class="num">' + esc(money(sum('farm'))) + '</td>' +
      '<td class="num">' + esc(money(sum('whole'))) + '</td>' +
      '<td class="num">' + esc(money(sum('retail'))) + '</td>' +
      '<td class="num">' + esc(money(percentile(kgList, 0.5))) + '</td>' +
      '<td class="num">' + esc(percentile(spList, 0.5).toFixed(2) + '×') + '</td>' +
      '<td></td>' +
      '<td class="tiny">one of everything moved farm gate → city retail is worth ' +
      esc(money(sum('retail') - sum('farm'))) + ' before a single cost line. Median retail weight ' +
      esc(money(percentile(kgList, 0.5))) + '/kg.</td></tr>';
  }

  function paintBoardStatus() {
    var el = $('#board-status');
    if (!el) return;
    var edited = state.rows.filter(function (r) { return r.edited; }).length;
    var text;
    if (!edited && !state.dirty) text = 'Reference data — indicative, dated ' + kit.dateLabel(state.asof) + '. Edit any price to start using your own.';
    else if (state.dirty) text = edited + ' commodity ' + (edited === 1 ? 'figure' : 'figures') + ' edited and NOT yet saved. Press “Save my prices” to keep them in this browser.';
    else text = edited + ' commodity ' + (edited === 1 ? 'figure' : 'figures') + ' of yours, saved ' + kit.dateLabel(state.asof) + '. Every tool on this page reads your numbers.';
    el.textContent = text;
    el.classList.toggle('is-edited', !!(edited || state.dirty));
  }

  function saveBoard() {
    var rows = {};
    state.rows.forEach(function (r) {
      rows[r.id] = { unit: r.unit, kg: r.kg, farm: r.farm, whole: r.whole, retail: r.retail, lean: r.lean };
    });
    var ok = STORE.board.write({ asof: state.asof, rows: rows });
    state.dirty = false;
    state.rows.forEach(function (r) { r.edited = true; });
    renderBoard();
    kit.toast(ok ? 'Your prices are saved in this browser.' : 'Could not save — this browser is blocking storage.');
  }

  function restoreBoard() {
    STORE.board.clear();
    loadBoard();
    fillCommoditySelects();
    renderBoard();
    recomputeAll();
    kit.toast('Reference data restored.');
  }

  function exportBoard() {
    var f = boardFilters();
    var head = ['Commodity', 'Note', 'Unit', 'Kg per unit', 'Farm gate', 'Wholesale', 'City retail',
      'Retail per kg', 'Spread x', 'Lean season', 'Markets sampled', 'Prices as of'];
    var rows = [head];
    state.rows.forEach(function (r) {
      rows.push([r.name, r.note, r.unit, r.kg, r.farm, r.whole, r.retail,
        Math.round(perKg(r) * 100) / 100, spreadOf(r).toFixed(2), MONTHS[kit.clamp(Math.round(r.lean), 0, 11)],
        r.mk.join(' / '), state.asof]);
    });
    rows.push([]);
    rows.push(['Indicative planning figures, not a quotation. Your edits, exported ' + isoToday() + (f.q ? ' (filter: ' + f.q + ')' : '')]);
    kit.csv(rows, 'marketrate-board-' + isoToday() + '.csv');
    kit.toast('Board exported as CSV.');
  }

  /* ------------------------------------------------------------------ 2. unit cost */

  function renderDealRows() {
    var box = $('#deal-rows');
    if (!box) return;
    box.innerHTML = state.deals.map(function (d, i) {
      return '<div class="deal-row" data-i="' + i + '">' +
        '<label for="deal-label-' + i + '">Offer name</label>' +
        '<input id="deal-label-' + i + '" class="dl" data-f="label" value="' + esc(d.label) + '" maxlength="60" autocomplete="off">' +
        '<label for="deal-price-' + i + '">Price</label>' +
        '<input id="deal-price-' + i + '" class="dp" data-f="price" type="number" min="0" max="5000000" step="50" value="' + esc(d.price) + '" inputmode="numeric">' +
        '<label for="deal-unit-' + i + '">Unit</label>' +
        '<select id="deal-unit-' + i + '" class="du" data-f="unit">' + state.units.map(function (u) {
          return '<option value="' + esc(u.name) + '"' + (u.name === d.unit ? ' selected' : '') + '>' + esc(u.name) + '</option>';
        }).join('') + '</select>' +
        '<span class="deal-out">' + esc(money(0)) + '/kg</span>' +
        '<button class="row-del" type="button" data-drop="' + i + '" aria-label="Remove this offer">&times;</button>' +
        '</div>';
    }).join('');
  }

  function dealModel() {
    var override = v('#unit-kg-override');
    var r = row($('#deal-commodity') ? $('#deal-commodity').value : 'beans');
    return state.deals.map(function (d) {
      var kg = override > 0 ? override : unitKg(d.unit);
      var price = kit.num(d.price);
      return {
        label: d.label, price: price, unit: d.unit, kg: kg,
        perKg: kg > 0 && price > 0 ? price / kg : 0
      };
    });
  }

  function calcDeals() {
    var all = dealModel();
    var list = all.filter(function (d) { return d.perKg > 0; });
    var bars = $('#deal-bars');
    var sorted = list.slice().sort(function (a, b) { return a.perKg - b.perKg; });
    $$('.deal-row').forEach(function (el, i) {
      var d = all[i];
      var out = $('.deal-out', el);
      if (out && d) out.textContent = d.perKg > 0 ? money(d.perKg) + '/kg' : '—';
      if (!d || !sorted.length) return;
      el.classList.toggle('is-best', Math.abs(d.perKg - sorted[0].perKg) < 0.005);
      el.classList.toggle('is-bad', Math.abs(d.perKg - sorted[sorted.length - 1].perKg) < 0.005);
    });

    if (list.length < 2) {
      setv('#deal-best', '—');
      setplain('#deal-best-note', 'enter at least two priced offers');
      verdict('#deal-verdict', 'Not enough to compare', 'Add a second priced offer — this tool ranks identical mass, not identical labels.', null, '#deal-title', '#deal-body');
      if (bars) bars.innerHTML = '';
      setplain('#m-spread', '—'); setplain('#m-total-best', '—'); setplain('#m-total-worst', '—');
      return;
    }

    var best = sorted[0], worst = sorted[sorted.length - 1];
    var need = Math.max(1, v('#deal-quantity'));
    var diff = worst.perKg - best.perKg;

    setv('#deal-best', money(best.perKg) + '/kg');
    setplain('#deal-best-note', best.label + ' — ' + qty(best.kg, best.kg < 10 ? 2 : 0) + ' kg per ' + best.unit);

    var pctAbove = best.perKg > 0 ? (diff / best.perKg) * 100 : 0;
    verdict('#deal-verdict',
      'Buy: ' + best.label,
      worst.label + ' is ' + money(diff) + '/kg dearer (' + kit.pct(pctAbove, 0) + ' above the cheapest). ' +
      'Same mass of goods, so the cheaper-looking number is the expensive one: ' + money(worst.price) + ' per ' +
      worst.unit + ' is ' + money(worst.perKg) + '/kg, against ' + money(best.price) + ' per ' + best.unit +
      ' at ' + money(best.perKg) + '/kg.',
      pctAbove > 25 ? 'bad' : 'warn', '#deal-title', '#deal-body');

    if (bars) {
      kit.bars(bars, sorted.map(function (d) {
        return { label: esc(d.label) + ' <span class="tiny">per ' + esc(d.unit) + '</span>', value: d.perKg, display: money(d.perKg) };
      }));
      var first = $('.bar-row', bars);
      if (first) first.classList.add('is-best');
    }
    setv('#m-spread', money(diff) + '/kg');
    setv('#m-total-best', money(best.perKg * need));
    setv('#m-total-worst', money(worst.perKg * need));
  }

  function addDeal() {
    if (state.deals.length >= 6) { kit.toast('Six offers is the practical limit.'); return; }
    var r = row($('#deal-commodity') ? $('#deal-commodity').value : 'beans');
    state.deals.push({ label: 'Offer ' + (state.deals.length + 1), price: Math.round(kit.num(r.retail) / 2), unit: r.unit });
    saveDeals();
    renderDealRows();
    calcDeals();
  }

  function resetDeals() {
    state.deals = defaultDeals();
    saveDeals();
    renderDealRows();
    calcDeals();
    kit.toast('Example offers restored.');
  }

  /* ------------------------------------------------------------------ 3. haul */

  function haulModel() {
    var r = row($('#h-commodity') ? $('#h-commodity').value : 'tomato');
    var origin = market($('#h-origin') ? $('#h-origin').value : 'Zaria farm gate');
    var dest = market($('#h-dest') ? $('#h-dest').value : 'Mile 12');
    var q = Math.max(1, Math.round(v('#h-qty')));
    var dist = v('#h-dist');
    var rate = v('#h-rate');
    var tripOverride = v('#h-trip');
    var trip = tripOverride > 0 ? tripOverride : rate * dist;
    var levies = v('#h-levy');
    var porter = v('#h-porter');
    var spoilPct = kit.clamp(v('#h-spoil'), 0, 90);
    var spoil = spoilPct / 100;
    var buy = priceAt(r, origin);
    var sell = priceAt(r, dest);
    var contrib = (1 - spoil) * sell - buy - porter;
    var fixed = trip + levies;
    var unitsSold = q * (1 - spoil);
    var goods = q * buy;
    var outlay = goods + trip + levies + porter * q;
    var revenue = unitsSold * sell;
    return {
      row: r, origin: origin, dest: dest, q: q, dist: dist, rate: rate, trip: trip,
      tripOverride: tripOverride, levies: levies, porter: porter, spoilPct: spoilPct, spoil: spoil,
      buy: buy, sell: sell, gross: sell - buy, contrib: contrib, fixed: fixed,
      unitsSold: unitsSold, goods: goods, outlay: outlay, revenue: revenue,
      profit: revenue - outlay,
      perUnit: contrib - (q > 0 ? fixed / q : 0),
      be: contrib > 0 ? Math.ceil(fixed / contrib) : null,
      costSold: unitsSold > 0 ? outlay / unitsSold : 0,
      roi: outlay > 0 ? ((revenue - outlay) / outlay) * 100 : 0
    };
  }

  function calcHaul() {
    var h = haulModel();
    var unitName = h.row.unit;
    var panel = $('.haul-panel');

    setv('#haul-net', money(h.profit));
    setplain('#haul-net-note', (h.profit >= 0 ? 'vs ' + money(h.q * h.buy) + ' sold at ' + h.origin.name : 'a loss of ' + money(Math.abs(h.profit)) + ' against the gate') +
      ' · ' + qty(h.q) + ' ' + upl(h.q, unitName) + ' carried');
    if (panel) panel.classList.toggle('is-loss', h.profit < 0);

    setv('#r-buy', money(h.buy) + ' / ' + unitName);
    setv('#r-sell', money(h.sell) + ' / ' + unitName);
    setv('#r-gross', money(h.gross) + ' / ' + unitName);
    setv('#r-transport', money(h.q > 0 ? h.trip / h.q : 0));
    setv('#r-levy', money(h.q > 0 ? h.levies / h.q : 0));
    setv('#r-porter', money(h.porter));
    setv('#r-spoil', money(h.spoil * h.sell));
    setv('#r-per-unit', money(h.perUnit));
    var strong = $('.line-strong', $('#haul') ? $('#haul').querySelector('.readout') : null);
    if (strong) { strong.classList.toggle('line-neg', h.perUnit < 0); strong.classList.toggle('line-pos', h.perUnit > 0); }

    setv('#m-be', h.be === null ? 'no quantity works' : '≥ ' + qty(h.be) + ' ' + upl(h.be, unitName));
    setv('#m-sell', qty(Math.round(h.unitsSold)) + ' of ' + qty(h.q) + ' ' + upl(h.q, unitName));
    setv('#m-roi', (h.roi >= 0 ? '+' : '') + kit.pct(h.roi, 1));

    if (h.contrib <= 0) {
      verdict('#haul-verdict', 'Do not load the truck',
        'Before the driver is paid, each ' + unitName + ' already loses ' + money(Math.abs(h.contrib)) +
        ': ' + money(h.sell) + ' in, ' + qty(Math.round(h.spoilPct)) + '% of it never sold, ' + money(h.buy) + ' out plus ' +
        money(h.porter) + ' for porters. No quantity fixes that — fix the price, the crop or the distance.',
        'bad', '#haul-title', '#haul-body');
    } else if (h.be !== null && h.q < h.be) {
      verdict('#haul-verdict', 'This load loses money',
        'You clear ' + money(h.contrib) + ' per ' + unitName + ' carried toward the ' + money(h.fixed) +
        ' of trip and levies, so you need ' + qty(h.be) + ' ' + upl(h.be, unitName) + ' to break even. At ' + qty(h.q) +
        ' you are ' + money(Math.abs(h.profit)) + ' short. Fill the truck or sell at ' + h.origin.name + '.',
        'bad', '#haul-title', '#haul-body');
    } else {
      var slack = h.be > 0 ? ((h.q - h.be) / h.q) * 100 : 100;
      verdict('#haul-verdict', 'The trip pays — break-even at ' + qty(h.be) + ' ' + upl(h.be, unitName),
        'The first ' + qty(h.be) + ' ' + upl(h.be, unitName) + ' only pay the driver and the levies; everything past that is ' +
        money(h.contrib) + ' per unit carried. Net extra profit ' + money(h.profit) + ', ' + money(h.perUnit) +
        ' per unit carried, ' + (h.roi >= 0 ? '+' : '') + kit.pct(h.roi, 1) + ' on the money you tied up. Cushion above break-even: ' +
        kit.pct(slack, 0) + ' of the load.',
        slack > 60 ? 'ok' : 'warn', '#haul-title', '#haul-body');
    }

    if (h.origin.city === h.dest.city) {
      setplain('#haul-body', $('#haul-body').textContent + ' Same city, so treat the trip cost as an intracity fare and confirm it locally.');
    }

    var trail = $('#trail-bars');
    if (trail && h.unitsSold > 0) {
      kit.bars(trail, [
        { label: 'City retail price, per ' + esc(unitName) + ' sold', value: Math.round(h.sell), display: money(h.sell) },
        { label: 'Landed cost per ' + esc(unitName) + ' sold', value: Math.round(h.costSold), display: money(h.costSold), cls: 'is-cost' },
        { label: 'Profit per ' + esc(unitName) + ' sold', value: Math.round(h.sell - h.costSold), display: money(h.sell - h.costSold) }
      ]);
    }

    var costs = $('#cost-bars');
    if (costs && h.unitsSold > 0) {
      kit.bars(costs, [
        { label: 'Goods (loss-inflated)', value: Math.round(h.goods / h.unitsSold), display: money(h.goods / h.unitsSold) },
        { label: 'Transport', value: Math.round(h.trip / h.unitsSold), display: money(h.trip / h.unitsSold) },
        { label: 'Levies', value: Math.round(h.levies / h.unitsSold), display: money(h.levies / h.unitsSold) },
        { label: 'Porters', value: Math.round((h.porter * h.q) / h.unitsSold), display: money((h.porter * h.q) / h.unitsSold) }
      ]);
      $$('.bar-row', costs).forEach(function (r) { r.classList.add('is-cost'); });
    }

    setplain('#r-outlay', money(h.outlay));
    setplain('#r-revenue', money(h.revenue));
    setplain('#r-profit', money(h.profit));
    var profitLine = $('#r-profit');
    if (profitLine) profitLine.parentNode.classList.toggle('line-neg', h.profit < 0);

    setplain('#h-spoil-note', h.row.name + ' is a “' + h.row.cls + '” crop: reference loss ' +
      qty(spoilOf(h.row.cls)) + '% on a hot long haul. Change the class rate in section 09 or type your own.');
    paintDistNote();
    renderWorked(h);
  }

  function paintDistNote() {
    var o = market($('#h-origin') ? $('#h-origin').value : ''), d = market($('#h-dest') ? $('#h-dest').value : '');
    var km = distanceBetween(o.city, d.city);
    var note = $('#h-dist-note');
    if (!note) return;
    note.textContent = km === null
      ? 'The ' + o.city + ' → ' + d.city + ' pair is not in the reference table — type the distance you will actually drive.'
      : (km === 0 ? 'Both desks are in ' + o.city + ' — an intracity move, usually 10–25 km.' :
        qty(km) + ' km, taken from the inter-city table (' + o.city + ' → ' + d.city + '). Editable.');
    return km;
  }

  function syncDistanceFromPair() {
    var km = paintDistNote();
    if (km === null || km === undefined) return;
    var dist = $('#h-dist');
    if (!dist) return;
    if (km === 0) { dist.value = 15; } else { dist.value = km; }
    var rate = $('#h-rate');
    if (rate && !state.rateTouched) rate.value = state.settings.rate;
  }

  function resetHaul() {
    ['#h-commodity'].forEach(function (s) { var el = $(s); if (el) el.value = 'tomato'; });
    var o = $('#h-origin'), d = $('#h-dest');
    if (o) o.value = 'Zaria farm gate';
    if (d) d.value = 'Mile 12';
    var map = { '#h-qty': 350, '#h-dist': 780, '#h-rate': 244, '#h-trip': 190000, '#h-levy': 12000, '#h-porter': 300, '#h-spoil': 20 };
    Object.keys(map).forEach(function (k) { var el = $(k); if (el) el.value = map[k]; });
    state.rateTouched = false;
    calcHaul();
    kit.toast('Zaria example restored.');
  }

  function logHaul() {
    var h = haulModel();
    var today = isoToday();
    state.log.unshift({
      id: kit.uid('t'), date: today, name: h.row.name, dir: 'buy',
      qty: h.q, unit: h.row.unit, price: Math.round(h.buy), market: h.origin.name,
      note: 'Haul plan: ' + qty(h.q) + ' ' + upl(h.q, h.row.unit) + ' for the ' + h.origin.city + ' → ' + h.dest.city + ' trip'
    });
    state.log.unshift({
      id: kit.uid('t'), date: today, name: h.row.name, dir: 'sell',
      qty: Math.round(h.unitsSold), unit: h.row.unit, price: Math.round(h.sell), market: h.dest.name,
      note: 'Assumes ' + qty(h.spoilPct) + '% spoilage; trip ' + money(h.trip) + ' + levies ' + money(h.levies) + ' are not in this line'
    });
    STORE.log.write(state.log);
    renderLog();
    kit.toast('Trip logged as a buy and a sell — remember the goods-only margin in the log is not the trip profit.');
  }

  /* ------------------------------------------------------------------ 4. season */

  function renderMonthStrip(r, current) {
    var strip = $('#month-strip');
    if (!strip) return;
    var peak = kit.clamp(Math.round(kit.num(r.lean)), 0, 11);
    strip.setAttribute('role', 'group');
    strip.setAttribute('aria-label', 'Seasonal index by month for ' + r.name);
    strip.innerHTML = MONTHS.map(function (name, i) {
      return '<button type="button" data-m="' + i + '" class="' + (i === current ? 'is-on' : '') + (i === peak ? ' is-peak' : '') + '"' +
        (i === current ? ' aria-current="true"' : '') + ' title="' + esc(name + ' — index ' + indexFor(r, i).toFixed(2)) + '">' +
        '<span class="mo-name">' + esc(SHORT[i]) + '</span><span class="mo-index">' + indexFor(r, i).toFixed(2) + '</span></button>';
    }).join('');
  }

  function calcSeason() {
    var r = row($('#s-commodity') ? $('#s-commodity').value : 'maize');
    var m = kit.clamp(Math.round(v('#s-month')), 0, 11);
    var series = seasonSeries(r);
    var spark = $('#season-spark');
    if (spark) kit.sparkline(spark, series, { dot: 'var(--accent)' });
    renderMonthStrip(r, m);
    setplain('#s-month-name', MONTHS[m] + ' — index ' + indexFor(r, m).toFixed(2) + '× the annual average');

    var idx = series[m];
    var peak = kit.clamp(Math.round(kit.num(r.lean)), 0, 11);
    var board = kit.num(r.retail);
    var today = board * idx;
    if (idx >= 1.12) {
      verdict('#s-verdict', 'Sell, do not store',
        r.name + ' in ' + MONTHS[m] + ' sits at ' + idx.toFixed(2) + '× the annual average — the ' +
        MONTHS[peak] + ' lean season. Applying that shape to your board retail of ' + money(board) +
        ' gives roughly ' + money(today) + ' per ' + r.unit + '. Holding into a peak you are already in buys you nothing.',
        'ok', '#s-title', '#s-body');
    } else if (idx <= 0.88) {
      verdict('#s-verdict', 'Glut: buy, or hold if it stores',
        'Index ' + idx.toFixed(2) + '× — this is the harvest-glut trough for ' + r.name +
        ', about ' + money(board) + ' × ' + idx.toFixed(2) + ' = ' + money(today) + ' per ' + r.unit +
        ' on the board shape. It is the month to buy or to store, provided the crop class (' + r.cls + ') survives the hold.',
        'warn', '#s-title', '#s-body');
    } else {
      verdict('#s-verdict', 'Flat part of the year',
        'Index ' + idx.toFixed(2) + '× for ' + MONTHS[m] + ' — near the annual average, so the calendar is not giving you an edge. ' +
        'Your own cost and the day’s quality decide; the reference peak month for ' + r.name + ' is ' + MONTHS[peak] + '.',
        null, '#s-title', '#s-body');
    }
  }

  function storeModel() {
    var buy = v('#st-buy'), sell = v('#st-sell');
    var loss = kit.clamp(v('#st-loss'), 0, 80) / 100;
    var cost = v('#st-cost'), months = Math.max(1, Math.round(v('#st-months')));
    var fin = kit.clamp(v('#st-finance'), 0, 15) / 100;
    var finance = buy * fin * months;
    var stored = cost * months;
    var tied = buy + stored + finance;
    var net = sell * (1 - loss);
    return {
      buy: buy, sell: sell, loss: loss, cost: cost, months: months, fin: fin,
      finance: finance, stored: stored, tied: tied, net: net,
      profit: net - tied,
      roi: tied > 0 ? ((net - tied) / tied) * 100 : 0,
      beSell: loss < 1 ? tied / (1 - loss) : 0
    };
  }

  function calcStore() {
    var s = storeModel();
    setv('#st-profit', money(s.profit));
    setv('#st-roi', (s.roi >= 0 ? '+' : '') + kit.pct(s.roi, 1));
    setv('#st-be', '+' + kit.pct(s.buy > 0 ? (s.beSell / s.buy - 1) * 100 : 0, 1) + ' (' + money(s.beSell) + ')');

    var r = row($('#s-commodity') ? $('#s-commodity').value : 'maize');
    var bm = kit.clamp(Math.round(v('#st-buy-month')), 0, 11);
    var sm = kit.clamp(Math.round(v('#st-sell-month')), 0, 11);
    var shape = indexFor(r, sm) / indexFor(r, bm);
    var quoted = s.buy > 0 ? s.sell / s.buy : 0;

    if (s.profit <= 0) {
      verdict('#st-verdict', 'You are paying to hold it',
        'After ' + qty(s.loss * 100, 1) + '% loss, ' + money(s.stored) + ' storage and ' + money(s.finance) +
        ' financing, ' + money(s.sell) + ' per unit leaves you ' + money(Math.abs(s.profit)) + ' down. The price would have to reach ' +
        money(s.beSell) + ' — ' + kit.pct(s.buy > 0 ? (s.beSell / s.buy - 1) * 100 : 0, 1) +
        ' above your buy — before storing is worth the risk.',
        'bad', '#st-title', '#st-body');
    } else if (s.roi < 20) {
      verdict('#st-verdict', 'Thin — the risk is not priced',
        money(s.profit) + ' per unit (' + kit.pct(s.roi, 1) + ' on ' + money(s.tied) + ' tied up for ' +
        qty(s.months) + ' months) is inside the noise of one rat, one flood, one week of rain on the road. ' +
        'Store it only if you already own the shed.',
        'warn', '#st-title', '#st-body');
    } else {
      verdict('#st-verdict', 'Storing pays on paper',
        money(s.profit) + ' per unit, ' + kit.pct(s.roi, 1) + ' return on ' + money(s.tied) + ' tied up over ' +
        qty(s.months) + ' months. Dry it to below 13–14% moisture first: aflatoxin is not a cost line, it is a rejection.',
        'ok', '#st-title', '#st-body');
    }

    var body = $('#st-body');
    if (body) {
      body.textContent = body.textContent + ' The reference index says ' + r.name + ' moves ' +
        kit.pct((shape - 1) * 100, 0) + ' from ' + MONTHS[bm] + ' to ' + MONTHS[sm] + '; your two typed prices say ' +
        kit.pct((quoted - 1) * 100, 0) + '. Trust the history you measured, not this shape.';
    }
  }

  /* ------------------------------------------------------------------ 5. negotiation */

  function negModel() {
    var cost = v('#n-cost'), q = Math.max(1, v('#n-qty'));
    var margin = kit.clamp(v('#n-margin'), 0, 300) / 100;
    var buf = kit.clamp(v('#n-anchor'), 0, 200) / 100;
    var open = v('#n-open');
    var floorM = kit.clamp(v('#n-floor'), 0, 100) / 100;
    var target = cost * (1 + margin);
    var anchor = target * (1 + buf);
    var walk = cost * (1 + floorM);
    var steps = [];
    var gap = Math.max(0, anchor - target);
    var asks = ['full volume, cash on collection, no credit',
      'buyer pays the return leg or the porter line',
      'the whole load in one trip, not a split sale'];
    for (var i = 1; i <= 3; i++) {
      steps.push({ price: anchor - gap * (i / 3), give: gap > 0 ? (gap / 3) : 0, ask: asks[i - 1] });
    }
    return {
      cost: cost, q: q, margin: margin, buf: buf, open: open, floorM: floorM,
      target: target, anchor: anchor, walk: walk, gap: gap, steps: steps,
      settle: (target + Math.max(open, walk)) / 2,
      profitAtSettle: (target + Math.max(open, walk)) / 2 - cost
    };
  }

  function calcNegotiation() {
    var n = negModel();
    setv('#g-anchor', money(n.anchor));
    setv('#g-target', money(n.target));
    setv('#g-walk', money(n.walk));
    setv('#g-floor', money(n.cost));

    var bars = $('#neg-bars');
    if (bars) {
      kit.bars(bars, [
        { label: 'Anchor', value: Math.round(n.anchor), display: money(n.anchor) },
        { label: 'Target', value: Math.round(n.target), display: money(n.target) },
        { label: 'Walk-away', value: Math.round(n.walk), display: money(n.walk) },
        { label: 'Your cost', value: Math.round(n.cost), display: money(n.cost), cls: 'is-cost' },
        { label: 'Buyer opened', value: Math.round(n.open), display: money(n.open), cls: 'is-cost' }
      ]);
    }

    var list = $('#neg-steps');
    if (list) {
      list.innerHTML = n.steps.map(function (s, i) {
        return '<li>' + esc(i === 2 ? 'Land at target' : 'Step ' + (i + 1)) + ' at <b>' + money(s.price) + '</b> — ' +
          'you give ' + money(s.give) + ' per unit (' + money(s.give * n.q) + ' on the load)' +
          '<span>Take back: ' + esc(s.ask) + '</span></li>';
      }).join('') +
        '<li>Stop at <b>' + money(n.walk) + '</b>. Below your cost plus ' + kit.pct(n.floorM * 100, 1) +
        ' you are financing the buyer’s margin with your own capital.<span>Walk away and sell at another desk on the board.</span></li>';
    }
  }

  function buildSheet() {
    var n = negModel();
    var h = haulModel();
    var commodity = row($('#h-commodity') ? $('#h-commodity').value : 'tomato');
    var belowCost = n.open < n.cost;
    var lines = [];
    lines.push('MARKETRATE NEGOTIATION SHEET');
    lines.push(commodity.name + ' — ' + qty(n.q) + ' ' + commodity.unit + ' · prepared ' + kit.dateLabel(state.asof));
    lines.push('Prices as of your own board, dated ' + kit.dateLabel(state.asof) + '. Indicative arithmetic, not a quotation.');
    lines.push('');
    lines.push('YOUR FOUR NUMBERS (per ' + commodity.unit + ')');
    lines.push(padRight('Anchor — open here', 36) + money(n.anchor));
    lines.push(padRight('Target — where you want to land', 36) + money(n.target));
    lines.push(padRight('Walk-away — below this, no deal', 36) + money(n.walk));
    lines.push(padRight('Floor — your landed cost', 36) + money(n.cost));
    lines.push(padRight('Buyer’s opening offer', 36) + money(n.open));
    lines.push('');
    lines.push('WHERE THAT LEAVES YOU');
    if (belowCost) {
      lines.push('The buyer’s opening is ' + money(n.cost - n.open) + ' BELOW your cost. That is not a discount');
      lines.push('ask, it is a loss ask. Restate the price per ' + commodity.unit + ' and per kilogram (' +
        money(perKg(commodity)) + '/kg on the board), then stop talking.');
    } else if (n.open >= n.walk && n.open < n.target) {
      lines.push('The buyer’s opening of ' + money(n.open) + ' is already inside your acceptable range:');
      lines.push('it clears your walk-away by ' + money(n.open - n.walk) + '. Concede slowly — the gap to');
      lines.push('target is only ' + money(n.target - n.open) + ' per unit.');
    } else {
      lines.push('The buyer’s opening is ' + money(n.open - n.target) + ' above target, so take the deal');
      lines.push('shape and negotiate the extras: cash timing, volume, who pays the porters.');
    }
    lines.push('');
    lines.push('THE LADDER — concede in this order, be paid for each step');
    n.steps.forEach(function (s, i) {
      lines.push((i + 1) + '. ' + padRight(money(s.price), 12) + 'give ' + money(s.give) + '/unit · ask: ' + s.ask);
    });
    lines.push('');
    lines.push('IF IT STICKS AT ' + money(n.settle).toUpperCase());
    lines.push('Profit per unit ' + money(n.profitAtSettle) + ' · whole load ' + money(n.profitAtSettle * n.q) +
      ' · margin over cost ' + kit.pct(n.cost > 0 ? (n.profitAtSettle / n.cost) * 100 : 0, 1));
    lines.push('');
    lines.push('COST BASE CHECK (from the haul calculator, live)');
    lines.push('Landed cost per ' + commodity.unit + ' sold there: ' + money(h.costSold) +
      ' · trip ' + money(h.trip) + ' · levies ' + money(h.levies) + ' · spoilage ' + qty(h.spoilPct) + '%');
    lines.push('');
    lines.push('Never concede without taking volume, cash timing or a cost transfer back.');
    lines.push('Built in your browser. Nothing was uploaded.');

    var out = $('#neg-text');
    if (out) out.value = lines.join('\n');
    setplain('#neg-status', 'Sheet built ' + new Date().toLocaleTimeString('en-NG', { hour: '2-digit', minute: '2-digit' }) +
      ' · anchor ' + money(n.anchor) + ' → target ' + money(n.target) + ' → walk-away ' + money(n.walk) + '.');
    kit.toast('Negotiation sheet built.');
  }

  function padRight(text, width) {
    var s = String(text);
    while (s.length < width) s += ' ';
    return s;
  }

  function copySheet() {
    var el = $('#neg-text');
    if (!el) return;
    var text = el.value || '';
    if (!text) { kit.toast('Build the sheet first.'); return; }
    function done() {
      setplain('#neg-status', 'Copied. Paste it into your notes or send it to the buyer.');
      kit.toast('Sheet copied to the clipboard.');
    }
    function fallback() {
      el.focus();
      el.select();
      try {
        if (document.execCommand('copy')) done();
        else kit.toast('Select the text and copy it manually — this browser blocks the clipboard.');
      } catch (e) {
        kit.toast('Select the text and copy it manually.');
      }
    }
    if (global.navigator && global.navigator.clipboard && global.navigator.clipboard.writeText) {
      global.navigator.clipboard.writeText(text).then(done, fallback);
    } else fallback();
  }

  function fillCostFromHaul() {
    var h = haulModel();
    if (h.unitsSold <= 0) { kit.toast('Nothing survives to be sold at those numbers.'); return; }
    var c = $('#n-cost'), q = $('#n-qty');
    if (c) c.value = Math.round(h.costSold);
    if (q) q.value = Math.round(h.unitsSold);
    calcNegotiation();
    buildSheet();
    kit.toast('Cost base taken from the haul calculator.');
  }

  /* ------------------------------------------------------------------ 6. fair price */

  function calcFair() {
    var r = row($('#f-commodity') ? $('#f-commodity').value : 'rice');
    var city = $('#f-city') ? $('#f-city').value : cities()[0];
    var tier = $('#f-tier') ? $('#f-tier').value : 'retail';
    var list = marketsIn(city);
    var month = monthOf(state.asof);
    var idx = indexFor(r, month);
    var values = list.map(function (m) { return kit.num(r[tier]) * kit.num(m.mult); });
    var measured = list.length >= 3;
    var lo, hi, mid;
    if (measured) {
      lo = percentile(values, 0.25); hi = percentile(values, 0.75); mid = percentile(values, 0.5);
    } else {
      mid = values.length ? values.reduce(function (a, b) { return a + b; }, 0) / values.length : 0;
      lo = mid * 0.91; hi = mid * 1.09;
    }

    var q = Math.max(0.05, v('#f-qty'));
    var price = v('#f-price');
    var unitName = $('#f-unit') ? $('#f-unit').value : r.unit;
    var kgPerUnit = unitKg(unitName);
    var asked = q > 0 ? price / q : 0;
    var ukg = kgPerUnit > 0 ? asked / kgPerUnit : 0;

    setv('#fair-band', money(lo) + ' – ' + money(hi));
    setplain('#fair-band-note', 'per ' + unitName + ' (' + qty(kgPerUnit, kgPerUnit < 10 ? 2 : 0) + ' kg) of ' +
      r.name + ' at ' + tierLabel(tier) + ' in ' + city + ' · ' +
      (measured
        ? 'middle half of ' + list.length + ' desks sampled there, dated ' + kit.dateLabel(state.asof)
        : 'only ' + list.length + ' desk' + (list.length === 1 ? '' : 's') + ' sampled in this city, so the band is a stated ±9% planning margin, not a measured middle half'));

    var bars = $('#fair-bars');
    if (bars) {
      kit.bars(bars, [
        { label: 'Your quote', value: Math.round(asked), display: money(asked) },
        { label: 'Band top (P75)', value: Math.round(hi), display: money(hi) },
        { label: 'Midpoint', value: Math.round(mid), display: money(mid) },
        { label: 'Band bottom (P25)', value: Math.round(lo), display: money(lo) }
      ]);
      if (asked > hi) { var fr = $('.bar-row', bars); if (fr) fr.classList.add('is-cost'); }
    }

    setv('#m-fair-unit', money(asked) + (kgPerUnit > 0 ? ' · ' + money(ukg) + '/kg' : ''));
    setv('#m-fair-mid', money(mid));
    var outside = asked < lo ? asked - lo : (asked > hi ? asked - hi : 0);
    setv('#m-fair-diff', outside === 0 ? 'inside' : (outside > 0 ? '+' : '') + money(outside));

    if (!price) {
      verdict('#fair-verdict', 'Awaiting a quote', 'Type the total you are being asked to pay.', null, '#fair-title', '#fair-body');
      return;
    }
    var seasonNote = ' ' + MONTHS[month] + ' sits at ' + idx.toFixed(2) + '× the annual average for ' + r.name +
      ', so treat this band as this week’s, not this year’s.';
    if (asked > hi) {
      var over = (asked / hi - 1) * 100;
      verdict('#fair-verdict', over > 15 ? 'Out of the ordinary range' : 'A little high',
        money(asked) + ' per ' + unitName + ' is ' + kit.pct(over, 1) + ' above the top of what ' + city +
        ' desks are asking (' + money(hi) + '). Offer ' + money(mid) + ' and be ready to move one stall down: ' +
        money(lo) + ' is the bottom of the ordinary range. Ask what the grade is before you call it dear.' + seasonNote,
        over > 15 ? 'bad' : 'warn', '#fair-title', '#fair-body');
    } else if (asked < lo) {
      verdict('#fair-verdict', 'Cheaper than the band — check it',
        money(asked) + ' is ' + kit.pct((1 - asked / lo) * 100, 1) + ' under the bottom of the range. Either you have found ' +
        'a genuine bargain, or the ' + unitName + ' is short, old stock, mixed with the previous lot, or not ' + r.name +
        '. Weigh it before you smile — board retail for ' + r.name + ' is ' + money(perKg(r)) + '/kg.' + seasonNote,
        'warn', '#fair-title', '#fair-body');
    } else {
      verdict('#fair-verdict', 'Fair — inside the middle half',
        money(asked) + ' sits between ' + money(lo) + ' and ' + money(hi) + ' for ' + tierLabel(tier) + ' in ' + city +
        '. The price is not the problem now: grade, weight and freshness are. Push for ' + money(mid) +
        ' on the full quantity and you are done.' + seasonNote,
        'ok', '#fair-title', '#fair-body');
    }
  }

  function tierLabel(tier) {
    return tier === 'farm' ? 'farm gate' : (tier === 'whole' ? 'wholesale market' : 'city retail');
  }

  /* ------------------------------------------------------------------ 7. trade log */

  function renderLog() {
    var body = $('#log-body'), empty = $('#log-empty');
    if (!body) return;
    if (!state.log.length) {
      body.innerHTML = '';
      if (empty) empty.hidden = false;
      renderLogStats();
      renderLogMonths();
      return;
    }
    if (empty) empty.hidden = true;
    body.innerHTML = state.log.map(function (t) {
      return '<tr class="is-' + esc(t.dir) + '" data-id="' + esc(t.id) + '">' +
        '<td>' + esc(t.date) + '</td>' +
        '<td>' + esc(t.name) + '</td>' +
        '<td>' + esc(t.dir === 'buy' ? 'Buy' : 'Sell') + '</td>' +
        '<td class="num">' + esc(qty(t.qty, t.qty % 1 ? 2 : 0)) + '</td>' +
        '<td>' + esc(t.unit) + '</td>' +
        '<td class="num">' + esc(money(t.price)) + '</td>' +
        '<td class="num">' + esc(money(t.qty * t.price)) + '</td>' +
        '<td>' + esc(t.market) + '</td>' +
        '<td class="tiny">' + esc(t.note || '') + '</td>' +
        '<td><button class="row-del del-ink" type="button" data-del="' + esc(t.id) + '" aria-label="Delete the trade on ' + esc(t.date) + ' for ' + esc(t.name) + '">&times;</button></td>' +
        '</tr>';
    }).join('');
    renderLogStats();
    renderLogMonths();
  }

  function logStats() {
    var byName = {};
    var buyValue = 0, sellValue = 0;
    state.log.forEach(function (t) {
      var v2 = kit.num(t.qty) * kit.num(t.price);
      if (t.dir === 'buy') buyValue += v2; else sellValue += v2;
      var b = byName[t.name] || (byName[t.name] = { buyQ: 0, buyV: 0, sellQ: 0, sellV: 0 });
      if (t.dir === 'buy') { b.buyQ += kit.num(t.qty); b.buyV += v2; }
      else { b.sellQ += kit.num(t.qty); b.sellV += v2; }
    });
    var names = Object.keys(byName).sort();
    var marginTotal = 0, best = null, worst = null;
    var rows = names.map(function (name) {
      var b = byName[name];
      var avgBuy = b.buyQ > 0 ? b.buyV / b.buyQ : 0;
      var avgSell = b.sellQ > 0 ? b.sellV / b.sellQ : 0;
      var board = 0, br = null;
      for (var i = 0; i < state.rows.length; i++) if (state.rows[i].name === name) { board = kit.num(state.rows[i].retail); br = state.rows[i]; break; }
      var per = (b.buyQ && b.sellQ) ? avgSell - avgBuy : 0;
      if (b.buyQ && b.sellQ) {
        marginTotal += per * b.sellQ;
        var rec = { name: name, per: per, qty: b.sellQ, avgSell: avgSell, avgBuy: avgBuy };
        if (!best || per > best.per) best = rec;
        if (!worst || per < worst.per) worst = rec;
      }
      return { name: name, b: b, avgBuy: avgBuy, avgSell: avgSell, board: board, per: per, br: br };
    });
    return { rows: rows, buyValue: buyValue, sellValue: sellValue, margin: marginTotal, best: best, worst: worst };
  }

  function renderLogStats() {
    var s = logStats();
    setplain('#lg-count', qty(state.log.length));
    setplain('#lg-buy', money(s.buyValue));
    setplain('#lg-sell', money(s.sellValue));
    setplain('#lg-margin', money(s.margin));

    var tbody = $('#log-summary');
    if (tbody) {
      if (!s.rows.length) {
        tbody.innerHTML = '<tr><td colspan="7" class="tiny">No trades on the board yet — the per-commodity comparison appears once you log one.</td></tr>';
      } else {
        tbody.innerHTML = s.rows.map(function (r) {
          return '<tr><td>' + esc(r.name) + '</td>' +
            '<td class="num">' + esc(qty(r.b.buyQ, r.b.buyQ % 1 ? 2 : 0)) + '</td>' +
            '<td class="num">' + esc(r.avgBuy ? money(r.avgBuy) : '—') + '</td>' +
            '<td class="num">' + esc(qty(r.b.sellQ, r.b.sellQ % 1 ? 2 : 0)) + '</td>' +
            '<td class="num">' + esc(r.avgSell ? money(r.avgSell) : '—') + '</td>' +
            '<td class="num">' + esc(r.board ? money(r.board) : 'not on board') + '</td>' +
            '<td class="num">' + esc((r.b.buyQ && r.b.sellQ) ? money(r.per) : '—') + '</td></tr>';
        }).join('');
      }
    }

    var bests = $('#log-bests');
    if (bests) {
      if (!s.best) {
        bests.textContent = 'Log a buy and a sell of the same commodity to see your realised margin against the board.';
      } else {
        bests.innerHTML = 'Best: <b>' + esc(s.best.name) + '</b> at ' + money(s.best.per) + ' per unit over your own average buy (' +
          qty(s.best.qty) + ' units sold). Worst: <b>' + esc(s.worst.name) + '</b> at ' + money(s.worst.per) +
          ' per unit. Realised margin is goods-only — transport, levies and spoilage stay in the haul calculator.';
      }
    }
  }

  function renderLogMonths() {
    var foot = $('#log-month');
    if (!foot) return;
    if (!state.log.length) { foot.innerHTML = ''; return; }
    var map = {}, order = [];
    state.log.forEach(function (t) {
      var key = String(t.date || '').slice(0, 7);
      if (!map[key]) { map[key] = { buy: 0, sell: 0, n: 0 }; order.push(key); }
      map[key].n++;
      map[key][t.dir] += kit.num(t.qty) * kit.num(t.price);
    });
    order.sort();
    foot.innerHTML = order.map(function (k) {
      var label = '—';
      var parts = k.split('-');
      if (parts.length === 2) label = MONTHS[kit.num(parts[1]) - 1] + ' ' + parts[0];
      return '<tr><th scope="row">' + esc(label) + '</th><td></td><td class="num">' + esc(qty(map[k].n)) + '</td>' +
        '<td class="num" colspan="2">' + esc(money(map[k].buy)) + ' bought</td>' +
        '<td class="num" colspan="2">' + esc(money(map[k].sell)) + ' sold</td>' +
        '<td colspan="3" class="tiny">net ' + esc(money(map[k].sell - map[k].buy)) + '</td></tr>';
    }).join('');
  }

  function addTrade() {
    var date = $('#l-date') ? $('#l-date').value : '';
    var qtyv = v('#l-qty'), price = v('#l-price');
    if (!date) { kit.toast('Put a date on it — an undated trade is not a record.'); return; }
    if (qtyv <= 0) { kit.toast('Quantity must be at least 1.'); return; }
    if (price <= 0) { kit.toast('A price of zero is not a trade. Enter what was actually paid.'); return; }
    var nameEl = $('#l-commodity'), unitEl = $('#l-unit'), dirEl = $('#l-dir'), mkEl = $('#l-market'), noteEl = $('#l-notes');
    var nameElOpt = nameEl ? nameEl.options[nameEl.selectedIndex] : null;
    state.log.unshift({
      id: kit.uid('t'),
      date: date,
      name: nameEl ? nameEl.value : '',
      nameLabel: nameElOpt ? nameElOpt.textContent : '',
      dir: dirEl ? dirEl.value : 'sell',
      qty: qtyv,
      unit: unitEl ? unitEl.value : 'kg',
      price: price,
      market: mkEl ? mkEl.value : '',
      note: noteEl ? String(noteEl.value).slice(0, 80) : ''
    });
    var ok = STORE.log.write(state.log);
    renderLog();
    kit.toast(ok ? 'Trade logged.' : 'Logged for this session only — storage is blocked.');
    if (noteEl) noteEl.value = '';
  }

  function deleteTrade(id) {
    state.log = state.log.filter(function (t) { return String(t.id) !== String(id); });
    STORE.log.write(state.log);
    renderLog();
    kit.toast('Trade deleted.');
  }

  function clearLog() {
    if (!state.log.length) { kit.toast('The log is already empty.'); return; }
    if (!global.confirm('Delete all ' + state.log.length + ' logged trades from this browser?')) return;
    state.log = [];
    STORE.log.clear();
    renderLog();
    kit.toast('Trade log cleared.');
  }

  function exportLog() {
    if (!state.log.length) { kit.toast('Nothing to export yet.'); return; }
    var s = logStats();
    var rows = [['Date', 'Commodity', 'Direction', 'Quantity', 'Unit', 'Price per unit', 'Value',
      'Market', 'Avg buy', 'Avg sell', 'Margin per unit', 'Note']];
    state.log.forEach(function (t) {
      var stat = s.rows.filter(function (r) { return r.name === t.name; })[0];
      rows.push([t.date, t.name, t.dir === 'buy' ? 'Bought' : 'Sold', t.qty, t.unit, t.price,
        Math.round(kit.num(t.qty) * kit.num(t.price)), t.market,
        stat && stat.avgBuy ? Math.round(stat.avgBuy) : '', stat && stat.avgSell ? Math.round(stat.avgSell) : '',
        stat && stat.per ? Math.round(stat.per) : '', t.note || '']);
    });
    rows.push([]);
    rows.push(['Totals', '', '', '', '', '', Math.round(s.buyValue + s.sellValue), '', '', '', '',
      'Realised goods-only margin ' + Math.round(s.margin) + '. Your own records, exported ' + isoToday() + '.']);
    kit.csv(rows, 'marketrate-trades-' + isoToday() + '.csv');
    kit.toast('Trade log exported.');
  }

  /* ------------------------------------------------------------------ rules tables */

  function renderRuleTables() {
    var ub = $('#unit-body');
    if (ub) {
      ub.innerHTML = state.units.map(function (u, i) {
        return '<tr><th scope="row">' + esc(u.name) + '</th>' +
          '<td class="num"><input type="number" min="0.05" max="500" step="0.05" value="' + esc(u.kg) + '" data-t="units" data-i="' + i + '" data-f="kg" aria-label="Kilograms per ' + esc(u.name) + '"></td>' +
          '<td class="tiny">' + esc(u.where) + '</td></tr>';
      }).join('');
    }
    var sb = $('#spoil-body');
    if (sb) {
      sb.innerHTML = state.spoil.map(function (s, i) {
        return '<tr><th scope="row">' + esc(s.cls) + '</th>' +
          '<td class="num"><input type="number" min="0" max="90" step="1" value="' + esc(s.loss) + '" data-t="spoil" data-i="' + i + '" data-f="loss" aria-label="Typical loss percent for ' + esc(s.cls) + ' crops"></td>' +
          '<td class="tiny">' + esc(s.note) + '</td></tr>';
      }).join('');
    }
    var mb = $('#market-body');
    if (mb) {
      mb.innerHTML = state.markets.map(function (m, i) {
        return '<tr><th scope="row">' + esc(m.name) + '</th>' +
          '<td>' + esc(m.city) + '</td>' +
          '<td class="num"><input type="number" min="0.3" max="3" step="0.01" value="' + esc(m.mult) + '" data-t="markets" data-i="' + i + '" data-f="mult" aria-label="Multiplier for ' + esc(m.name) + '"></td>' +
          '<td class="tiny">' + esc(m.tier + ' — ' + m.role) + '</td></tr>';
      }).join('');
    }
    var rd = $('#rate-default'), sa = $('#season-amp');
    if (rd) rd.value = state.settings.rate;
    if (sa) sa.value = state.settings.amp;
  }

  function saveRules() {
    STORE.units.write(state.units);
    STORE.spoil.write(state.spoil);
    STORE.markets.write(state.markets);
    STORE.settings.write(state.settings);
  }

  function resetRules(which) {
    if (which === 'units') { STORE.units.clear(); state.units = deepCopy(REF_UNITS); }
    if (which === 'spoil') { STORE.spoil.clear(); state.spoil = deepCopy(REF_SPOIL); }
    if (which === 'markets') {
      STORE.markets.clear();
      state.markets = deepCopy(REF_MARKETS);
      STORE.settings.clear();
      state.settings = { rate: 244, amp: 0.28 };
    }
    saveRules();
    renderRuleTables();
    fillCommoditySelects();
    fillMarketSelects();
    renderDealRows();
    recomputeAll();
    kit.toast('Reference figures restored.');
  }

  /* ------------------------------------------------------------------ worked example */

  function renderWorked(h) {
    var list = $('#worked');
    if (!list) return;
    var u = h.row.unit;
    var p = function (n) { return qty(n) + ' ' + upl(n, u); };
    var steps = [
      'Buy ' + p(h.q) + ' of ' + h.row.name + ' at ' + money(h.buy) + ' at ' + h.origin.name +
        ' = <b>' + money(h.goods) + '</b> of goods. Board weight ' + qty(rowKg(h.row), rowKg(h.row) < 10 ? 2 : 0) +
        ' kg per ' + u + ' = <b>' + money(h.buy / (rowKg(h.row) || 1)) + ' per kg</b> going in.',
      'Sell at ' + h.dest.name + ' for ' + money(h.sell) + ' per ' + u + ' = <b>' + money(h.sell / (rowKg(h.row) || 1)) +
        ' per kg</b> coming out. Gross spread <b>' + money(h.gross) + '</b> per ' + u + ' before any cost.',
      'Transport: ' + (h.tripOverride > 0
        ? 'driver’s quote of <b>' + money(h.trip) + '</b> for the trip'
        : '₦' + qty(h.rate) + '/km × ' + qty(h.dist) + ' km = <b>' + money(h.trip) + '</b>') +
        ' = <b>' + money(h.q > 0 ? h.trip / h.q : 0) + ' per ' + u + '</b> carried.',
      'Levies <b>' + money(h.levies) + '</b> (' + money(h.q > 0 ? h.levies / h.q : 0) + ' per ' + u + ') + porters <b>' +
        money(h.porter) + '</b> per ' + u + ' = <b>' + money(h.porter * h.q) + '</b> on the load.',
      qty(h.spoilPct) + '% spoilage: <b>' + p(Math.round(h.q * h.spoil)) + ' never sell</b>, ' +
        p(Math.round(h.unitsSold)) + ' sell for <b>' + money(h.revenue) + '</b>.',
      'Contribution per ' + u + ' carried = ' + qty(100 - h.spoilPct, 0) + '% × ' + money(h.sell) + ' − ' + money(h.buy) +
        ' − ' + money(h.porter) + ' = <b>' + money(h.contrib) + '</b>. Fixed cost ' + money(h.fixed) + ' ÷ ' + money(h.contrib) +
        ' = break-even at <b>' + (h.be === null ? 'no quantity that works' : qty(h.be) + ' ' + upl(h.be, u)) + '</b>.',
      'Net extra profit vs selling at ' + h.origin.name + ' = <b>' + money(h.profit) + '</b> (' + money(h.perUnit) +
        ' per ' + u + ' carried), on ' + money(h.outlay) + ' of outlay = <b>' + (h.roi >= 0 ? '+' : '') + kit.pct(h.roi, 1) +
        '</b>. Landed cost <b>' + money(h.costSold) + '</b> per ' + u + ' actually sold.'
    ];
    list.innerHTML = steps.map(function (s, i) {
      return '<li' + (i === 5 || i === 6 ? ' class="is-key"' : '') + '>' + s + '</li>';
    }).join('');

    var badge = $('#worked-verdict'), net = $('#worked-net');
    if (badge) {
      badge.textContent = h.contrib <= 0 ? 'Trip never pays' : (h.be !== null && h.q < h.be ? 'Loses at this load' : 'Trip pays');
      badge.className = 'badge ' + (h.contrib <= 0 || (h.be !== null && h.q < h.be) ? 'badge-bad' : (h.profit > 0 ? 'badge-ok' : 'badge-warn'));
    }
    if (net) {
      net.textContent = money(h.profit);
      net.classList.toggle('is-loss', h.profit < 0);
    }
  }

  /* ------------------------------------------------------------------ orchestration */

  function recomputeAll() {
    calcDeals();
    calcHaul();
    calcSeason();
    calcStore();
    calcNegotiation();
    calcFair();
  }

  function markDirty() {
    state.dirty = true;
    paintBoardStatus();
  }

  function wire() {
    /* board */
    kit.on(document, 'input', '#board-body input.cell', function (e, el) {
      var tr = el.closest('tr');
      if (!tr) return;
      var r = row(tr.getAttribute('data-id'));
      if (!r) return;
      var f = el.getAttribute('data-f');
      r[f] = f === 'unit' ? el.value : kit.num(el.value);
      r.edited = true;
      markDirty();
      var kg = rowKg(r);
      var kgOut = $('.kg-out', tr), spOut = $('.spread-out', tr) || $('.sp-out', tr);
      if (kgOut) kgOut.textContent = money(kg > 0 ? kit.num(r.retail) / kg : 0);
      if (spOut) spOut.textContent = (kit.num(r.farm) > 0 ? (kit.num(r.retail) / kit.num(r.farm)).toFixed(2) : '0.00') + '×';
      paintBoardFoot();
      recomputeAll();
    });
    kit.on(document, 'change', '#board-body select.cell', function (e, el) {
      var tr = el.closest('tr');
      if (!tr) return;
      var r = row(tr.getAttribute('data-id'));
      if (!r) return;
      r.unit = el.value;
      r.edited = true;
      markDirty();
      fillCommoditySelects();
      recomputeAll();
    });
    var bq = $('#board-q');
    if (bq) bq.addEventListener('input', renderBoard);
    var bs = $('#board-sort');
    if (bs) bs.addEventListener('change', renderBoard);
    var asof = $('#as-of');
    if (asof) asof.addEventListener('change', function () {
      state.asof = asof.value || isoToday();
      markDirty();
      paintBoardStatus();
      calcFair();
    });
    bind('#save-board', saveBoard);
    bind('#restore-board', restoreBoard);
    bind('#export-board', exportBoard);
    bind('#print-board', function () { kit.print(); });

    /* deals */
    kit.on(document, 'input', '#deal-rows input', function (e, el) {
      var wrap = el.closest('.deal-row');
      if (!wrap) return;
      var i = Number(wrap.getAttribute('data-i'));
      var f = el.getAttribute('data-f');
      if (!state.deals[i]) return;
      state.deals[i][f] = f === 'price' ? kit.num(el.value) : el.value;
      saveDeals();
      calcDeals();
    });
    kit.on(document, 'change', '#deal-rows select', function (e, el) {
      var wrap = el.closest('.deal-row');
      if (!wrap || !state.deals[Number(wrap.getAttribute('data-i'))]) return;
      state.deals[Number(wrap.getAttribute('data-i'))].unit = el.value;
      saveDeals();
      calcDeals();
    });
    kit.on(document, 'click', '#deal-rows [data-drop]', function (e, el) {
      if (state.deals.length <= 2) { kit.toast('Keep at least two offers to compare.'); return; }
      state.deals.splice(Number(el.getAttribute('data-drop')), 1);
      saveDeals();
      renderDealRows();
      calcDeals();
    });
    bind('#add-deal', addDeal);
    bind('#reset-deals', resetDeals);
    var dc = $('#deal-commodity');
    if (dc) dc.addEventListener('change', function () {
      var r = row(dc.value);
      var note = $('#deal-unit-note');
      if (note) {
        note.textContent = 'Board says one ' + r.unit + ' of ' + r.name + ' is ' + qty(rowKg(r), rowKg(r) < 10 ? 2 : 0) +
          ' kg, and retails at ' + money(r.retail) + ' = ' + money(perKg(r)) + '/kg. Deal rows keep their own unit.';
      }
      calcDeals();
    });
    ['#unit-kg-override', '#deal-quantity'].forEach(function (s) { var el = $(s); if (el) el.addEventListener('input', calcDeals); });

    /* haul */
    ['#h-qty', '#h-dist', '#h-rate', '#h-trip', '#h-levy', '#h-porter', '#h-spoil'].forEach(function (s) {
      var el = $(s);
      if (!el) return;
      el.addEventListener('input', function () {
        if (s === '#h-rate') state.rateTouched = true;
        calcHaul();
      });
    });
    var hc = $('#h-commodity');
    if (hc) hc.addEventListener('change', function () {
      var r = row(hc.value);
      var sp = $('#h-spoil');
      if (sp) sp.value = spoilOf(r.cls);
      calcHaul();
    });
    ['#h-origin', '#h-dest'].forEach(function (s) {
      var el = $(s);
      if (el) el.addEventListener('change', function () { syncDistanceFromPair(); calcHaul(); });
    });
    bind('#haul-log', logHaul);
    bind('#haul-reset', resetHaul);

    /* season */
    var sc = $('#s-commodity');
    if (sc) sc.addEventListener('change', function () { calcSeason(); calcStore(); });
    var sm = $('#s-month');
    if (sm) sm.addEventListener('input', function () { calcSeason(); });
    kit.on(document, 'click', '#month-strip button', function (e, el) {
      var m = Number(el.getAttribute('data-m'));
      if (sm) sm.value = m;
      calcSeason();
    });
    bind('#s-today', function () {
      if (sm) sm.value = new Date().getMonth();
      calcSeason();
      kit.toast('Jumped to ' + MONTHS[new Date().getMonth()] + '.');
    });
    ['#st-buy', '#st-sell', '#st-loss', '#st-cost', '#st-finance', '#st-months'].forEach(function (s) {
      var el = $(s); if (el) el.addEventListener('input', calcStore);
    });
    ['#st-buy-month', '#st-sell-month'].forEach(function (s) {
      var el = $(s); if (el) el.addEventListener('change', calcStore);
    });

    /* negotiation */
    ['#n-cost', '#n-qty', '#n-margin', '#n-anchor', '#n-open', '#n-floor'].forEach(function (s) {
      var el = $(s); if (el) el.addEventListener('input', calcNegotiation);
    });
    bind('#neg-build', buildSheet);
    bind('#neg-copy', copySheet);
    bind('#neg-print', function () { buildSheet(); kit.print(); });
    bind('#neg-from-haul', fillCostFromHaul);

    /* fair */
    ['#f-commodity', '#f-city', '#f-qty', '#f-unit', '#f-price', '#f-tier'].forEach(function (s) {
      var el = $(s); if (el) el.addEventListener('input', calcFair);
    });

    /* log */
    bind('#log-add', addTrade);
    bind('#log-export', exportLog);
    bind('#log-print', function () { kit.print(); });
    bind('#log-clear', clearLog);
    kit.on(document, 'click', '#log-body [data-del]', function (e, el) { deleteTrade(el.getAttribute('data-del')); });
    var lq = $('#l-qty'), lp = $('#l-price');
    function suggestFromBoard() {
      var name = $('#l-commodity') ? $('#l-commodity').value : '';
      var r = row(name);
      var dir = $('#l-dir') ? $('#l-dir').value : 'sell';
      if (r && r.id === name) {
        var price = dir === 'buy' ? r.farm : r.retail;
        if (lp && (!lp.value || Number(lp.value) === 0)) lp.value = Math.round(price);
        var lu = $('#l-unit');
        if (lu) lu.value = r.unit;
      }
    }
    ['#l-commodity', '#l-dir'].forEach(function (s) { var el = $(s); if (el) el.addEventListener('change', suggestFromBoard); });

    /* rules */
    kit.on(document, 'input', '#unit-body input, #spoil-body input, #market-body input', function (e, el) {
      var t = el.getAttribute('data-t'), i = Number(el.getAttribute('data-i')), f = el.getAttribute('data-f');
      if (!state[t] || state[t][i] === undefined) return;
      state[t][i][f] = kit.num(el.value);
      saveRules();
      renderBoard();
      recomputeAll();
    });
    var rd = $('#rate-default'), sa2 = $('#season-amp');
    if (rd) rd.addEventListener('input', function () { state.settings.rate = kit.num(rd.value); saveRules(); calcHaul(); });
    if (sa2) sa2.addEventListener('input', function () { state.settings.amp = kit.clamp(kit.num(sa2.value), 0, 0.8); saveRules(); calcSeason(); calcStore(); calcFair(); });
    bind('#reset-units', function () { resetRules('units'); });
    bind('#reset-spoil', function () { resetRules('spoil'); });
    bind('#reset-markets', function () { resetRules('markets'); });
  }

  function bind(sel, fn) {
    var el = $(sel);
    if (el) el.addEventListener('click', fn);
  }

  /* ------------------------------------------------------------------ boot */

  kit.boot({
    ready: function () {
      var savedSettings = STORE.settings.read();
      if (savedSettings) {
        state.settings.rate = savedSettings.rate !== undefined ? kit.num(savedSettings.rate) : 244;
        state.settings.amp = savedSettings.amp !== undefined ? kit.num(savedSettings.amp) : 0.28;
      }
      loadBoard();
      loadRuleList(STORE.units, REF_UNITS, 'units');
      loadRuleList(STORE.spoil, REF_SPOIL, 'spoil');
      loadRuleList(STORE.markets, REF_MARKETS, 'markets');
      loadDeals();
      var savedLog = STORE.log.read();
      state.log = Array.isArray(savedLog) ? savedLog : [];

      var asof = $('#as-of');
      if (asof) asof.value = state.asof;
      var ld = $('#l-date');
      if (ld) ld.value = isoToday();
      var sm = $('#s-month');
      if (sm) sm.value = new Date().getMonth();

      fillCommoditySelects();
      fillMarketSelects();
      fillMonthSelects();
      renderRuleTables();
      renderDealRows();
      renderBoard();
      renderLog();
      wire();

      var dc = $('#deal-commodity');
      if (dc) {
        var dr = row(dc.value);
        var note = $('#deal-unit-note');
        if (note) {
          note.textContent = 'Board says one ' + dr.unit + ' of ' + dr.name + ' is ' +
            qty(rowKg(dr), rowKg(dr) < 10 ? 2 : 0) + ' kg, and retails at ' + money(dr.retail) + ' = ' +
            money(perKg(dr)) + '/kg. Deal rows keep their own unit.';
        }
      }
      syncDistanceFromPair();
      recomputeAll();
      buildSheet();
    }
  });
})(window);
