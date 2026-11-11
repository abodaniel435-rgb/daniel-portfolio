/* CropKeep — post-harvest loss engine. Every figure is computed in this browser.
   Loss rates are mid-points of published Nigerian post-harvest ranges, not measurements. */
(function () {
  'use strict';

  /* ---------- reference data (indicative, editable below) ---------- */

  var CROPS = [
    { id: 'tomato', name: 'Tomato', klass: 'perish', baseline: 40, range: [35, 45], floor: 8, days: 2.5, baseTemp: 28, chill: 10, ambient: 28,
      note: 'Very high perishability. Field heat plus noon harvesting is where the loss starts.' },
    { id: 'pepper', name: 'Habanero / scotch bonnet pepper', klass: 'perish', baseline: 35, range: [30, 40], floor: 9, days: 8, baseTemp: 26, chill: 7, ambient: 29,
      note: 'Transpiration and stem mould. Vented stacks in a covered load change the answer.' },
    { id: 'gardenegg', name: 'Garden egg', klass: 'perish', baseline: 32, range: [28, 38], floor: 10, days: 6, baseTemp: 26, chill: 7, ambient: 29,
      note: 'Bruises in a sack and rots at the bruise. Crates are the single biggest fix.' },
    { id: 'greens', name: 'Leafy greens (ugwu, ugu, spinach)', klass: 'perish', baseline: 45, range: [40, 50], floor: 15, days: 1.6, baseTemp: 25, chill: 4, ambient: 28,
      note: 'Wilts in hours. Same-day market or a shade stall, nothing else.' },
    { id: 'strawberry', name: 'Strawberry', klass: 'perish', baseline: 42, range: [35, 50], floor: 8, days: 2.5, baseTemp: 20, chill: 1, ambient: 24,
      note: 'Botrytis moves fast in the humid highlands. Needs shade, clean trays and speed.' },
    { id: 'banana', name: 'Banana / plantain', klass: 'perish', baseline: 28, range: [25, 35], floor: 10, days: 10, baseTemp: 20, chill: 13, ambient: 26,
      note: 'Ethylene-driven ripening; the whole bunch follows the first ripe finger.' },
    { id: 'mango', name: 'Mango', klass: 'perish', baseline: 26, range: [20, 30], floor: 8, days: 8, baseTemp: 22, chill: 5, ambient: 29,
      note: 'Anthracnose plus heat. Hot-water treatment is the standard commercial fix.' },
    { id: 'cassava', name: 'Cassava tubers', klass: 'perish', baseline: 30, range: [25, 35], floor: 10, days: 2.5, baseTemp: 27, chill: 10, ambient: 28,
      note: 'Primary physiological deterioration starts within 48–72 h of harvest. Process fast.' },
    { id: 'potato', name: 'Irish potato', klass: 'perish', baseline: 15, range: [12, 20], floor: 6, days: 35, baseTemp: 20, chill: 4, ambient: 24,
      note: 'Dark storage, ventilation and no light. Greening and sprouting are the losses.' },
    { id: 'onion', name: 'Onion (cured bulbs)', klass: 'dry', baseline: 18, range: [15, 25], floor: 8, days: 60, baseTemp: 25, chill: 2, ambient: 28,
      note: 'Cure properly, then a dry raised store. Sprouting is a temperature story.' },
    { id: 'maize', name: 'Maize grain', klass: 'dry', baseline: 18, range: [15, 20], floor: 3, days: 180, baseTemp: 28, chill: 0, ambient: 28,
      note: 'Weevils and moisture. Above about 13% moisture, aflatoxin risk rises — that needs a lab test.' },
    { id: 'rice', name: 'Rice paddy', klass: 'dry', baseline: 15, range: [12, 18], floor: 3, days: 210, baseTemp: 28, chill: 0, ambient: 28,
      note: 'Drying to safe moisture before storage is the whole game.' },
    { id: 'groundnut', name: 'Groundnut', klass: 'dry', baseline: 20, range: [15, 25], floor: 4, days: 150, baseTemp: 27, chill: 0, ambient: 28,
      note: 'The highest aflatoxin-risk crop here. Dry fast, store dry, test before sale.' },
    { id: 'soybean', name: 'Soybean', klass: 'dry', baseline: 16, range: [12, 20], floor: 3, days: 180, baseTemp: 25, chill: 0, ambient: 27,
      note: 'Seed coat damage in shelling; store cool and dry for germination value.' }
  ];

  var CREDITS = [
    { id: 'morning', label: 'Harvested in the cool morning, before 10am', small: 'The alternative is midday harvest: the crop leaves the field already hot.', pts: 5 },
    { id: 'shade', label: 'Shade within 30 minutes of cutting', small: 'Field heat is the single fastest rot accelerator on this page.', pts: 6 },
    { id: 'crates', label: 'Crates, not sacks', small: 'A sack bruises and traps heat; a crate stacks with airflow.', pts: 8 },
    { id: 'graded', label: 'Graded and de-leafed', small: 'Removed leaves and damaged fruit stop mould spreading.', pts: 4 },
    { id: 'transport', label: 'Covered transport, stacked with vents', small: 'Sun, dust and wind scorch in an open truck bed.', pts: 6 },
    { id: 'unload', label: 'Unloaded at the destination into shade', small: 'The last hour on the quayside in full sun is expensive.', pts: 4 }
  ];

  var OPTIONS = [
    { id: 'hermetic', name: 'Hermetic PICS / MD2 bags', sub: 'Sealed laminate bags that suffocate weevils without chemicals',
      classes: ['dry'], achieved: { dry: 3.5 }, priceId: 'bagPrice', unit: 'per 50 kg bag', unitsBy: 'kgBag', capacity: 50,
      seasons: 1, power: 'none', note: 'Reusable for about three seasons if handled carefully. Grain must be dried to safe moisture before sealing.' },
    { id: 'store', name: 'Improved raised / cement store with drying rack', sub: 'Damp-proof floor, raised racks, vermin-proof walls',
      classes: ['dry'], achieved: { dry: 6 }, priceId: 'storePrice', unit: 'built', unitsBy: 'store', capacity: 2000,
      seasons: 10, power: 'none', note: 'A structure, not a purchase: the ₦380,000 indicative figure is labour plus materials at 2026 rates.' },
    { id: 'dryer', name: 'Solar crop dryer, 200 kg tray', sub: 'Dries in daylight without sand, mould or night re-wetting',
      classes: ['dry', 'perish'], achieved: { dry: 5, perish: 25 }, priceId: 'dryerPrice', unit: 'dryer', unitsBy: 'dryer', capacity: 200,
      seasons: 5, power: 'none', note: 'Throughput is per day of sun. Only counts if you actually have a buyer for dried product.' },
    { id: 'cooler', name: 'Charcoal cooler / CoolPort', sub: 'Evaporative cooling through wet sand and charcoal — holds about 15 °C below ambient',
      classes: ['perish'], achieved: { perish: 15 }, priceId: 'coolerPrice', unit: 'unit', unitsBy: 'cooler', capacity: 1000,
      seasons: 5, power: 'none', note: 'Works best in dry heat. Needs topping with water twice a day and packing in the shade.' },
    { id: 'coolbot', name: 'CoolBot cold room', sub: 'Domestic air-conditioner unit reflowed into a insulated box, ~8 °C',
      classes: ['perish'], achieved: { perish: 6 }, priceId: 'coolbotPrice', unit: 'room', unitsBy: 'coolbot', capacity: 3000,
      seasons: 7, power: 'grid', note: 'Roughly 1.5 kW while running. On a weak grid it needs a stable supply or a decent solar and battery setup.' },
    { id: 'evap', name: 'Evaporative brick cool chamber ("zero-energy fridge")', sub: 'Wet sand jacket around an inner pot — 8–14 °C when the air is dry',
      classes: ['perish'], achieved: { perish: 22 }, priceId: 'evapPrice', unit: 'chamber', unitsBy: 'evap', capacity: 600,
      seasons: 4, power: 'none', note: 'Cheap and effective in the harmattan; loses most of its advantage in humid wet-season Port Harcourt air.' },
    { id: 'van', name: 'Refrigerated van hire for the trip', sub: 'Pay per trip instead of buying cold',
      classes: ['perish'], achieved: { perish: 20 }, priceId: 'vanPrice', unit: 'per trip', unitsBy: 'trips', capacity: 1500,
      seasons: 1, power: 'gen', note: 'Only cools the journey. If the crop goes into the van hot and unshaded, most of the benefit is spent before it moves.' },
    { id: 'processing', name: 'Immediate processing — paste, powder, gari', sub: 'Convert the perishable into a shelf-stable product',
      classes: ['perish', 'dry'], achieved: { perish: 8, dry: 6 }, priceId: 'processPrice', unit: 'line', unitsBy: 'processing', capacity: 1500,
      seasons: 6, power: 'solar', note: 'Kills the loss problem and creates a market problem: you now have to sell a processed good.' },
    { id: 'coldstore', name: 'Aggregate to a shared cold store', sub: 'Weekly rent on someone else\'s cold room',
      classes: ['perish'], achieved: { perish: 12 }, priceId: 'coldStoreWeekly', unit: 'per week', unitsBy: 'weeks', capacity: 2000,
      seasons: 1, power: 'grid', note: 'Only works with a functioning cooperative or aggregator — the ₦6,000 is your share of one week.' }
  ];

  var DEFAULT_CROP_FIELDS = ['baseline', 'floor', 'days', 'baseTemp'];
  var DEFAULT_PRICES = {
    bagPrice: 3500, storePrice: 380000, dryerPrice: 145000, coolerPrice: 190000, coolbotPrice: 950000,
    evapPrice: 120000, vanPrice: 30000, processPrice: 260000, coldStoreWeekly: 6000
  };

  var DEFAULTS = {
    q10: 10, seasonMonths: 4, bagSizeKg: 50, capacitySlack: 10, vanLowPrice: 18000, vanHighPrice: 45000
  };

  /* ---------- state ---------- */

  var storeCrops = kit.store('cropkeep:crops', null);
  var storePrices = kit.store('cropkeep:prices', null);
  var storeAssump = kit.store('cropkeep:assumptions', null);
  var storeCredits = kit.store('cropkeep:credits', null);
  var storeBatch = kit.store('cropkeep:batches', []);

  var crops = loadCrops();
  var prices = loadPrices();
  var assump = loadAssump();
  var creditPts = loadCredits();
  var chosen = null;
  var lastResult = null;
  var lastFigures = {};

  function el(id) { return document.getElementById(id); }
  function val(id, fallback) { var n = el(id); return n ? kit.num(n.value) : fallback; }
  function round(n) { return Math.round((Number(n) || 0) * 100) / 100; }

  function loadCrops() {
    var saved = storeCrops.read() || {};
    return CROPS.map(function (c) {
      var s = saved[c.id] || {};
      var out = Object.assign({}, c);
      DEFAULT_CROP_FIELDS.forEach(function (f) {
        var v = Number(s[f]);
        if (isFinite(v) && v >= 0) out[f] = v;
      });
      out.floor = Math.min(out.floor, out.baseline);
      return out;
    });
  }
  function loadPrices() {
    var saved = storePrices.read() || {};
    var out = {};
    Object.keys(DEFAULT_PRICES).forEach(function (k) {
      var v = Number(saved[k]);
      out[k] = isFinite(v) && v >= 0 ? v : DEFAULT_PRICES[k];
    });
    return out;
  }
  function loadAssump() {
    var saved = storeAssump.read() || {};
    var out = {};
    Object.keys(DEFAULTS).forEach(function (k) {
      var v = Number(saved[k]);
      out[k] = isFinite(v) && v >= 0 ? v : DEFAULTS[k];
    });
    return out;
  }
  function loadCredits() {
    var saved = storeCredits.read();
    var map = {};
    CREDITS.forEach(function (c) { map[c.id] = c.pts; });
    if (Array.isArray(saved)) {
      saved.forEach(function (row) {
        if (row && map[row.id] !== undefined) {
          var v = Number(row.pts);
          if (isFinite(v) && v >= 0) map[row.id] = v;
        }
      });
    }
    return map;
  }
  /* store() hands back its fallback array by reference, so copy it: otherwise a
     logged batch would survive a "clear" for the rest of the session. */
  function batches() {
    var list = storeBatch.read();
    return Array.isArray(list) ? list.slice() : [];
  }

  function cropById(id) {
    for (var i = 0; i < crops.length; i++) { if (crops[i].id === id) return crops[i]; }
    return crops[0];
  }
  function escapeText(text) {
    var div = document.createElement('div');
    div.textContent = String(text);
    return div.innerHTML;
  }
  function escapeAttr(text) {
    return String(text).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
  }

  /* ---------- engine ---------- */

  function q10Life(crop, tempC) {
    var factor = Math.pow(0.5, (Number(tempC) - crop.baseTemp) / (assump.q10 || 10));
    return crop.days * factor;
  }

  function earnedCredits(ticksOn) {
    var pts = 0, lines = [];
    CREDITS.forEach(function (c) {
      if (ticksOn[c.id]) { pts += creditPts[c.id]; lines.push({ id: c.id, label: c.label, pts: creditPts[c.id] }); }
    });
    return { pts: pts, lines: lines };
  }

  function unitsNeeded(o, input, peakWeek) {
    switch (o.unitsBy) {
      case 'kgBag': return Math.ceil(input.kg / assump.bagSizeKg);
      case 'store': return Math.max(1, Math.ceil(input.kg / o.capacity));
      case 'dryer': return Math.max(1, Math.ceil(peakWeek / o.capacity));
      case 'cooler': return Math.max(1, Math.ceil(input.kg / o.capacity));
      case 'coolbot': return Math.max(1, Math.ceil(input.kg / o.capacity));
      case 'evap': return Math.max(1, Math.ceil(input.kg / o.capacity));
      case 'trips': return Math.max(1, Math.ceil(input.kg / o.capacity) * input.cycles);
      case 'processing': return Math.max(1, Math.ceil(peakWeek / o.capacity));
      case 'weeks': return Math.max(1, Math.ceil(input.cycles * 1));
      default: return 1;
    }
  }

  function seasonCost(o, input, peakWeek) {
    var unitCost = prices[o.priceId] * unitsNeeded(o, input, peakWeek);
    var durable = o.seasons > 1;
    return { unitCost: unitCost, durable: durable, seasonCost: durable ? unitCost / o.seasons : unitCost, units: unitsNeeded(o, input, peakWeek) };
  }

  function achievedLoss(o, crop) {
    return o.achieved[crop.klass];
  }

  function scoreOption(o, input, crop, cost, penalty) {
    var notes = [], score = 100;
    var excluded = null;

    if (o.classes.indexOf(crop.klass) === -1) {
      excluded = crop.klass === 'perish'
        ? 'Grain technology — a perishable loses its water and colour in days, not months.'
        : 'Cooling technology — stored grain loses most of its weight to weevils and mould, not temperature.';
      return { score: 0, excluded: excluded, notes: notes };
    }

    if (o.power === 'grid' && input.grid === 'none') {
      excluded = 'No reliable power at the store — this option is a compressor with nothing to plug into.';
      return { score: 0, excluded: excluded, notes: notes };
    }
    if (o.power === 'grid' && input.grid === 'solar') { score -= 22; notes.push('Your solar setup would have to carry the compressor — check the inverter size before you commit.'); }
    if (o.power === 'grid' && input.grid === 'gen') { score -= 8; notes.push('Runs on the generator, so its running cost is really fuel cost.'); }
    if (o.power === 'solar' && input.grid === 'none') { score -= 12; notes.push('Processing needs power you do not have yet — diesel or a small solar line has to be budgeted.'); }

    if (input.budget > 0 && cost.unitCost > input.budget * 2) {
      excluded = 'Budget too low this season: it needs ' + kit.money(cost.unitCost) + ' against your ' + kit.money(input.budget) + ' available.';
      return { score: 0, excluded: excluded, notes: notes };
    }
    if (input.budget > 0 && cost.seasonCost > input.budget) { score -= 18; notes.push('Its seasonal cost alone exceeds the cash you set aside.'); }
    if (cost.units > 3) { score -= 10; notes.push('Your volume needs ' + cost.units + ' units, which is a different purchase conversation.'); }

    if (crop.klass === 'perish') {
      if (input.days <= 1 && o.id !== 'hermetic') { score -= 25; notes.push('A same-day market: handling earns more here than hardware.'); }
      if (input.days >= 2) { score += 6; notes.push('Two or more nights in transit is exactly what this buys you.'); }
    }
    if (crop.klass === 'dry' && input.days <= 1) { score -= 4; }

    score -= penalty || 0;
    return { score: kit.clamp(Math.round(score), 0, 100), excluded: excluded, notes: notes };
  }

  function compute(input, peakWeek) {
    var crop = input.crop;
    var earned = earnedCredits(input.ticks);
    var value = input.kg * input.price;
    var baselineNaira = value * crop.baseline / 100;
    var adjustedPct = kit.clamp(crop.baseline - earned.pts, crop.floor, 100);
    var adjustedNaira = value * adjustedPct / 100;
    var recovered = baselineNaira - adjustedNaira;

    var lifeNow = q10Life(crop, input.temp);
    var marginNow = lifeNow - input.days;

    var rows = OPTIONS.map(function (o) {
      var ach = achievedLoss(o, crop);
      var cost = seasonCost(o, input, peakWeek);
      var lossWith = ach === undefined ? null : kit.clamp(Math.max(crop.floor, ach - earned.pts * 0.4), crop.floor, 100);
      var pointsSaved = lossWith === null ? 0 : Math.max(0, adjustedPct - lossWith);
      var avoidedCycle = value * pointsSaved / 100;
      var avoidedSeason = avoidedCycle * input.cycles;
      var net = avoidedSeason - cost.seasonCost;
      /* quality premium is shown as upside only: counting it would move the
         payback figure, and a crate that "sells better" is a claim, not a weight */
      var upsideCycle = avoidedCycle * input.premium;
      var upsideSeason = upsideCycle * input.cycles;
      var cycles = avoidedCycle > 0 ? cost.unitCost / avoidedCycle : null;
      var months = avoidedSeason > 0 ? cost.unitCost / avoidedSeason * assump.seasonMonths : null;
      var fit = scoreOption(o, input, crop, cost, 0);
      var life = o.achieved[crop.klass] === undefined ? null : q10Life(crop, optionTemp(o, input));
      return {
        o: o, ach: ach, cost: cost, lossWith: lossWith, pointsSaved: pointsSaved,
        avoidedCycle: avoidedCycle, avoidedSeason: avoidedSeason, net: net,
        upsideCycle: upsideCycle, upsideSeason: upsideSeason,
        paybackCycles: cycles, paybackMonths: months, fit: fit, life: life,
        eligible: fit.excluded === null
      };
    }).filter(function (row) { return row.ach !== undefined; });

    var ranked = rows.slice().sort(function (a, b) { return b.net - a.net; });
    var eligible = ranked.filter(function (r) { return r.eligible; });
    var rejected = ranked.filter(function (r) { return !r.eligible; });
    var top3 = eligible.slice(0, 3);

    return {
      input: input, crop: crop, earned: earned, value: value,
      baselineNaira: baselineNaira, adjustedPct: adjustedPct, adjustedNaira: adjustedNaira, recovered: recovered,
      lifeNow: lifeNow, marginNow: marginNow, rows: rows, ranked: ranked, eligible: eligible, rejected: rejected,
      top3: top3, peakWeek: peakWeek
    };
  }

  function optionTemp(o, input) {
    if (o.id === 'cooler') return Math.max(8, input.temp - 15);
    if (o.id === 'evap') return Math.max(9, input.temp - 12);
    if (o.id === 'coolbot') return 8;
    if (o.id === 'van') return 10;
    if (o.id === 'coldstore') return 8;
    return input.temp;
  }

  function readInput() {
    var ticks = {};
    CREDITS.forEach(function (c) {
      var box = el('credit-' + c.id);
      ticks[c.id] = box ? box.checked : false;
    });
    var grid = el('grid') ? el('grid').value : 'none';
    return {
      crop: cropById(el('crop').value),
      kg: val('batch-kg', 0),
      price: val('value-kg', 0),
      days: val('days-to-market', 1),
      temp: val('storage-temp', 28),
      baselineOverride: el('baseline-loss') ? kit.num(el('baseline-loss').value) : null,
      cycles: kit.clamp(Math.round(val('cycles', 8)), 1, 40),
      budget: val('budget', 0),
      grid: grid,
      premium: val('value-multiplier', 0) / 100,
      label: el('batch-name') ? String(el('batch-name').value || '').trim() : '',
      ticks: ticks
    };
  }

  /* ---------- painting ---------- */

  function setFigure(id, value) {
    var node = el(id);
    if (!node) return;
    var text = value === null ? '—' : String(value);
    if (node.textContent === text) return;
    node.textContent = text;
    if (!kit.reduce && lastFigures[id] !== undefined && lastFigures[id] !== text) {
      node.classList.remove('tick');
      void node.offsetWidth;
      node.classList.add('tick');
    }
    lastFigures[id] = text;
  }

  function badge(node, cls) { node.className = 'verdict' + (cls ? ' ' + cls : ''); }

  function paintOutputs(r) {
    var input = r.input, crop = r.crop;

    el('batch-totals').textContent = kit.amount(input.kg) + ' kg × ' + kit.money(input.price) + ' = ' + kit.money(r.value);
    setFigure('crop-out', kit.money(r.value));
    setFigure('baseline-out', kit.pct(crop.baseline, 1) + ' · ' + kit.money(r.baselineNaira));
    setFigure('handling-out', r.earned.lines.length ? '−' + kit.pct(r.earned.pts, 0) + ' (' + r.earned.lines.length + ' of ' + CREDITS.length + ')' : 'none claimed');
    setFigure('adjusted-out', kit.pct(r.adjustedPct, 1) + ' · ' + kit.money(r.adjustedNaira));
    setFigure('loss-out', kit.money(r.adjustedNaira));
    setFigure('shelf-out', kit.amount(r.lifeNow, 2) + ' days');
    setFigure('days-out', kit.amount(input.days, 1) + ' days');
    setFigure('recovered-out', r.recovered > 0 ? kit.money(r.recovered) : kit.money(0));

    var pctBar = kit.clamp(r.adjustedPct / Math.max(crop.baseline, 1) * 100, 0, 100);
    el('decay-fill').style.width = pctBar.toFixed(1) + '%';
    el('decay-goal').style.left = 'calc(' + kit.clamp(crop.baseline / Math.max(crop.baseline, 1) * 100, 0, 100) + '% - 1px)';
    el('decay-caption').textContent = 'Unimproved ' + kit.pct(crop.baseline, 0) + ' → ' + kit.pct(r.adjustedPct, 1) +
      ' with your handling. The bar is drawn against the unimproved figure, so a shorter bar means money kept.';

    survival(r);
    el('crop-help').textContent = crop.note + ' Class: ' + (crop.klass === 'perish' ? 'perishable' : 'dry stored') +
      '. Published unimproved range ' + crop.range[0] + '–' + crop.range[1] + '%.';
    el('baseline-help').textContent = 'Literature mid-point for ' + crop.name.toLowerCase() + ' is ' + crop.baseline +
      '%; measured loss in your own field is better — enter it here and use it.';

    var best = r.top3[0];
    setFigure('avoided-cycle', best ? kit.money(best.avoidedCycle) : '—');
    setFigure('net-season', best ? kit.money(best.net) : '—');

    paintMatrix(r);
    paintPicks(r);
    paintWhyRest(r);
    paintDetail(r);
    paintLadder(r);
    worked(r);
    paintPlanner(r);
  }

  function survival(r) {
    var box = el('survival'), title = el('verdict-title'), body = el('verdict-body');
    var input = r.input, crop = r.crop;
    var margin = r.marginNow;
    var label = crop.klass === 'dry' ? 'Quality and weight held' : 'Will it survive the trip?';

    if (crop.klass === 'dry') {
      badge(box, 'ok');
      title.textContent = label + ' — stored-grain maths';
      body.textContent = 'For ' + crop.name.toLowerCase() + ' the clock is months, not days: ' + kit.amount(r.lifeNow, 0) +
        ' days of safe keeping at ' + kit.amount(input.temp, 0) + ' °C against a ' + kit.amount(input.days, 1) +
        '-day sale window. Loss here comes from moisture, insects and mould — the storage option matters more than the transport.';
      return;
    }
    if (margin >= input.days * 0.5) {
      badge(box, 'ok');
      title.textContent = label + ' — yes, with ' + kit.amount(margin, 1) + ' days to spare';
      body.textContent = 'At ' + kit.amount(input.temp, 0) + ' °C the batch still has ' + kit.amount(r.lifeNow, 1) +
        ' days of life when a buyer needs it in ' + kit.amount(input.days, 1) + '. You can sell on your own terms.';
      return;
    }
    if (margin >= 0) {
      badge(box, 'warn');
      title.textContent = label + ' — barely, by ' + kit.amount(margin, 1) + ' days';
      body.textContent = 'It arrives with ' + kit.amount(r.lifeNow - input.days, 1) + ' days left, which means a same-day sale at whatever the gate offers. That is the panic price, and it is a temperature problem, not a market problem.';
      return;
    }
    badge(box, 'bad');
    title.textContent = label + ' — no, it is ' + kit.amount(Math.abs(margin), 1) + ' days late';
    body.textContent = 'At ' + kit.amount(input.temp, 0) + ' °C the crop has ' + kit.amount(r.lifeNow, 1) +
      ' days of life but the sale needs ' + kit.amount(input.days, 1) + '. Cool it or shorten the trip: ' +
      (r.top3[0] ? r.top3[0].o.name + ' would give it ' + kit.amount(q10Life(crop, optionTemp(r.top3[0].o, input)), 1) + ' days at about ' + kit.amount(optionTemp(r.top3[0].o, input), 0) + ' °C.' : 'a shade-and-crate fix is the first move.');
  }

  function fitClass(score) { return score >= 80 ? 'fit-good' : score >= 55 ? 'fit-mid' : 'fit-poor'; }

  function paintMatrix(r) {
    var body = el('matrix-body');
    var pickId = chosen ? chosen.id : (r.top3[0] ? r.top3[0].o.id : null);
    body.innerHTML = r.ranked.map(function (row) {
      var o = row.o;
      var fit = row.eligible ? row.fit.score : 0;
      return '<tr data-opt="' + o.id + '" class="' + (row.eligible ? '' : 'is-out') + (o.id === pickId ? ' is-pick' : '') + '">' +
        '<td class="o-name">' + escapeText(o.name) + '<small>' + escapeText(o.sub) + '</small></td>' +
        '<td><span class="fit ' + (row.eligible ? fitClass(fit) : 'fit-poor') + '">' + (row.eligible ? fit : 'out') + '</span></td>' +
        '<td class="num">' + kit.money(row.cost.seasonCost) + (row.cost.durable ? '<small> (' + kit.money(row.cost.unitCost) + ' / ' + row.cost.units + ' unit' + (row.cost.units > 1 ? 's' : '') + ' over ' + row.o.seasons + ' seasons)</small>' : '') + '</td>' +
        '<td class="num">' + (row.lossWith === null ? 'n/a' : kit.pct(row.lossWith, 1)) + '</td>' +
        '<td class="num">' + (row.pointsSaved ? '−' + kit.pct(row.pointsSaved, 1) : '—') + '</td>' +
        '<td class="num">' + kit.money(row.avoidedCycle) + '</td>' +
        '<td class="num">' + kit.money(row.avoidedSeason) + '</td>' +
        '<td class="num"><b>' + kit.money(row.net) + '</b></td>' +
        '<td class="num">' + (row.paybackCycles === null ? '—' : row.paybackCycles < 0.05 ? 'under 1 batch' : kit.amount(row.paybackCycles, 1) + ' cycles') + '</td>' +
        '<td><input class="pick-radio" type="radio" name="pick" value="' + o.id + '"' + (o.id === pickId ? ' checked' : '') + ' aria-label="Select ' + escapeAttr(o.name) + '"></td>' +
        '</tr>';
    }).join('');
  }

  function paintPicks(r) {
    var host = el('top-picks');
    if (!r.top3.length) {
      host.innerHTML = '<div class="rank-card"><h3>Nothing fits this answer set</h3><p>Every option was excluded by your own constraints — most often crop class or power. Try a handling fix first; they are on the left of this page and cost nothing.</p></div>';
      return;
    }
    host.innerHTML = r.top3.map(function (row, i) {
      var o = row.o;
      var why = o.id === 'cooler' && row.avoidedCycle > 0
        ? 'Ranked by net naira left this season after paying for the thing itself.'
        : 'Payback ' + (row.paybackCycles === null ? '—' : kit.amount(row.paybackCycles, 1) + ' cycles') + ' at ' + kit.amount(r.input.kg) + ' kg a cycle.';
      return '<article class="rank-card reveal is-in' + (i === 0 ? ' is-top' : '') + '">' +
        '<span class="rank-medal">' + (i + 1) + '</span>' +
        '<h3>' + escapeText(o.name) + '</h3>' +
        '<p class="rank-sub">' + escapeText(o.sub) + '</p>' +
        '<b class="rank-num">' + kit.money(row.net) + '</b>' +
        '<span class="rank-sub">net this season</span>' +
        '<p class="tiny">' + escapeText(why) + ' Loss goes from ' + kit.pct(r.adjustedPct, 1) + ' to ' + kit.pct(row.lossWith, 1) +
        ', ' + kit.money(row.avoidedCycle) + ' avoided per cycle.</p>' +
        '<p class="tiny">' + escapeText(row.fit.notes[0] || o.note) + '</p>' +
        '</article>';
    }).join('');
  }

  function paintWhyRest(r) {
    var host = el('why-rest');
    var top = r.top3.map(function (x) { return x.o.id; });
    var rest = r.ranked.filter(function (x) { return top.indexOf(x.o.id) === -1; });
    if (!rest.length) { host.innerHTML = '<li>Nothing was left out — every suitable option made the top three.</li>'; return; }
    host.innerHTML = rest.map(function (row) {
      var reason = row.eligible
        ? (row.net <= 0
          ? 'Costs ' + kit.money(row.cost.seasonCost) + ' a season against ' + kit.money(row.avoidedSeason) + ' avoided — it does not pay for itself at ' + kit.amount(r.input.kg) + ' kg a cycle.'
          : kit.money(row.net) + ' net, which is ' + kit.money(r.top3[0].net - row.net) + ' behind ' + r.top3[0].o.name + ' for this volume.')
        : row.fit.excluded;
      return '<li><b>' + escapeText(row.o.name) + ':</b> ' + escapeText(reason) + '</li>';
    }).join('');
  }

  function selectedRow(r) {
    if (chosen) {
      for (var i = 0; i < r.rows.length; i++) { if (r.rows[i].o.id === chosen.id) return r.rows[i]; }
    }
    return r.top3[0] || r.ranked[0] || null;
  }

  function paintDetail(r) {
    var row = selectedRow(r);
    var host = el('option-card');
    if (!row) {
      host.querySelector('#option-name').textContent = 'Select an option';
      host.querySelector('#option-hint').textContent = 'No option available for this crop.';
      return;
    }
    var o = row.o, input = r.input, crop = r.crop;
    el('option-name').textContent = o.name;
    el('option-sub').textContent = o.sub + ' · ' + o.unit + ' ' + kit.money(prices[o.priceId]);
    var badgeNode = el('option-badge');
    badgeNode.textContent = row.eligible ? 'fit ' + row.fit.score : 'excluded';
    badgeNode.className = 'badge ' + (row.eligible ? (row.fit.score >= 80 ? 'badge-ok' : 'badge-warn') : 'badge-bad');
    el('option-hint').textContent = row.eligible
      ? (row.fit.notes[0] || 'Suits your crop class, volume and constraints as entered.')
      : row.fit.excluded;
    el('option-cost').textContent = kit.money(row.cost.seasonCost);
    el('option-avoided').textContent = kit.money(row.avoidedCycle);
    el('option-cycles').textContent = row.paybackCycles === null ? '—'
      : kit.amount(row.paybackCycles, 1) + ' cycles';
    el('option-months').textContent = row.paybackMonths === null ? '—'
      : kit.amount(row.paybackMonths, 1) + ' months';

    kit.bars(el('option-bars'), [
      { label: 'Loss now', value: r.adjustedPct, display: kit.pct(r.adjustedPct, 1) },
      { label: 'Loss with it', value: row.lossWith || 0, display: kit.pct(row.lossWith || 0, 1) },
      { label: 'Points saved', value: row.pointsSaved, display: '−' + kit.pct(row.pointsSaved, 1) },
      { label: 'Naira avoided / cycle', value: row.avoidedCycle, display: kit.money(row.avoidedCycle) },
      { label: 'Net this season', value: row.net, display: kit.money(row.net) }
    ]);

    el('option-co2').textContent = co2line(o, input, row);
    el('option-note').textContent = o.note + (input.premium > 0
      ? ' Not counted above: if the crate that survives sells as grade-A at your ' + kit.pct(input.premium * 100, 0) +
        ' premium, that is roughly ' + kit.money(row.upsideSeason) + ' a season of extra upside on top of the loss avoided.'
      : '');
    chosen = { id: o.id };
    paintMatrix(r);
  }

  function co2line(o, input, row) {
    if (o.id === 'coolbot') {
      var kwh = 1.5 * 8 * 30 * row.cost.units * (assump.seasonMonths);
      if (input.grid === 'grid') {
        return 'Indicative only: about ' + kit.amount(kwh * 0.4, 0) + ' kg CO₂e per season of running (grid electricity at roughly 0.4 kg/kWh, compressor 1.5 kW for 8 h a day). A grid cold room is usually worse than a passive chamber on emissions.';
      }
      if (input.grid === 'solar') {
        return 'Indicative only: about ' + kit.amount(kwh * 0.4, 0) + ' kg CO₂e a season if that load runs on fuel, near zero on solar — the honest answer depends entirely on which kilowatt-hours you actually use.';
      }
      return 'Runs on a generator, so the emissions are fuel burn: roughly ' + kit.amount(kwh * 0.4, 0) + ' kg CO₂e a season at 0.4 kg/kWh — indicative, not a measurement.';
    }
    if (o.id === 'van') {
      return 'Indicative only: a refrigerated van on a Zaria–Lagos round trip burns on the order of 100 kg CO₂e of diesel. Compare that against the diesel the farmer would otherwise spend on panic-price re-trips — this tool does not pretend to know either.';
    }
    if (o.id === 'cooler' || o.id === 'evap') {
      return 'Passive, no fuel. The charcoal itself is the emission and the deforestation story — about 2–3 kg CO₂e per kg of charcoal made, so a season of refills is real but small next to a generator.';
    }
    if (o.id === 'hermetic' || o.id === 'store' || o.id === 'dryer' || o.id === 'processing' || o.id === 'coldstore') {
      return 'No operating emissions modelled here. What this tool can honestly say is the naira line; the carbon line would need your actual energy meter.';
    }
    return 'No energy claim made for this option.';
  }

  /* ---------- shelf-life ladder ---------- */

  function paintLadder(r) {
    var crop = r.crop, input = r.input;
    var temps = crop.klass === 'dry' ? [35, 30, 25, 20, 15] : [35, 30, 28, 25, 20, 15, 12, 8];
    var maxLife = Math.max.apply(null, temps.map(function (t) { return q10Life(crop, t); }));
    el('ladder-caption').textContent = crop.name + ' — base ' + crop.days + ' days at ' + crop.baseTemp +
      ' °C, so ' + (crop.klass === 'dry' ? 'safe storing' : 'shelf life') + ' roughly doubles for every ' + assump.q10 + ' °C you take off.';
    el('temp-ladder').innerHTML = temps.map(function (t) {
      var life = q10Life(crop, t);
      var chill = crop.chill > 0 && t < crop.chill;
      var live = Math.abs(t - input.temp) < 1;
      return '<div class="ladder-row' + (live ? ' is-live' : '') + '">' +
        '<span class="l-temp">' + t + ' °C</span>' +
        '<span class="l-bar"><i data-width="' + (life / maxLife * 100).toFixed(1) + '"></i></span>' +
        '<span class="l-days">' + kit.amount(life, life < 10 ? 1 : 0) + ' d</span>' +
        '<span class="l-flag">' + (chill ? 'chilling injury' : (live ? 'where you are' : '')) + '</span>' +
        '</div>';
    }).join('');
    window.requestAnimationFrame(function () {
      kit.$$('#temp-ladder .l-bar i').forEach(function (node) { node.style.width = node.dataset.width + '%'; });
    });

    var rows = [{ label: 'Your ' + kit.amount(input.temp, 0) + ' °C now', life: r.lifeNow }];
    ['cooler', 'evap', 'coolbot', 'van'].forEach(function (id) {
      var o = OPTIONS.filter(function (x) { return x.id === id; })[0];
      if (o && o.classes.indexOf(crop.klass) !== -1) rows.push({ label: o.name.split(' /')[0].split(' —')[0], life: q10Life(crop, optionTemp(o, input)) });
    });
    rows.push({ label: 'Graded & shaded only', life: q10Life(crop, Math.max(crop.chill || 4, input.temp - 4)) });
    var max = Math.max.apply(null, rows.map(function (x) { return x.life; })) || 1;
    kit.bars(el('shelf-bars'), rows.map(function (x) {
      return { label: x.label, value: x.life / max * 100, display: kit.amount(x.life, 1) + ' d' };
    }));

    var testT = val('cool-temp', 13);
    var testLife = q10Life(crop, testT);
    el('cool-temp-out').textContent = kit.amount(testT, 0) + ' °C would leave ' + kit.amount(testLife, 1) + ' days of life — ' +
      (testLife - input.days >= 0 ? 'enough for the ' + kit.amount(input.days, 1) + '-day trip with ' + kit.amount(testLife - input.days, 1) + ' days spare.'
        : 'still ' + kit.amount(input.days - testLife, 1) + ' days short of the trip.');
  }

  /* ---------- planner ---------- */

  function readWeeks() {
    var weeks = [];
    for (var i = 1; i <= 5; i++) {
      var node = el('week-' + i);
      weeks.push(node ? Math.max(0, kit.num(node.value)) : 0);
    }
    return weeks;
  }

  /* The five week inputs are built once and then only their labels/flags are
     refreshed: rebuilding the markup on every keystroke would drop focus from
     the input the user is typing into. */
  var WEEK_DEFAULTS = [620, 940, 1150, 880, 540];

  function buildWeeks() {
    var host = el('week-inputs');
    if (!host || host.querySelector('input')) return;
    host.innerHTML = WEEK_DEFAULTS.map(function (kg, i) {
      return '<div class="week-row" data-week="' + (i + 1) + '">' +
        '<label class="w-meta" for="week-' + (i + 1) + '">Week ' + (i + 1) +
        '<small id="week-date-' + (i + 1) + '"></small></label>' +
        '<input type="number" min="0" max="50000" step="50" id="week-' + (i + 1) + '" value="' + kg +
        '" inputmode="numeric" aria-label="Kilograms harvested in week ' + (i + 1) + '">' +
        '<span class="week-flag is-clear" id="week-flag-' + (i + 1) + '">holds</span>' +
        '</div>';
    }).join('');
  }

  function paintPlanner(r) {
    var weeks = readWeeks();
    var row = selectedRow(r);
    var cap = row ? row.o.capacity * row.cost.units * (1 + assump.capacitySlack / 100) : 0;
    var peak = Math.max.apply(null, weeks) || 1;
    var start = el('harvest-start').value ? new Date(el('harvest-start').value) : new Date();
    var risk = [];

    buildWeeks();
    kit.$$('#week-inputs .week-row').forEach(function (node) {
      var i = Number(node.dataset.week) - 1;
      var over = cap > 0 && weeks[i] > cap;
      if (over) risk.push(i + 1);
      var dateNode = el('week-date-' + (i + 1));
      if (dateNode) dateNode.textContent = kit.dateLabel(new Date(start.getTime() + i * 7 * 86400000));
      node.classList.toggle('is-risk', over);
      var flag = el('week-flag-' + (i + 1));
      if (flag) {
        flag.className = 'week-flag' + (over ? '' : ' is-clear');
        flag.textContent = over ? 'over by ' + kit.amount(weeks[i] - cap) + ' kg' : 'holds';
      }
    });

    kit.bars(el('planner-bars'), weeks.map(function (kg, i) {
      return { label: 'Week ' + (i + 1), value: kg, display: kit.amount(kg) + ' kg' };
    }));

    el('capacity-line').textContent = row
      ? 'Selected option: ' + row.o.name + ' — ' + row.cost.units + ' unit' + (row.cost.units > 1 ? 's' : '') +
        ' of ' + kit.amount(row.o.capacity) + ' kg ≈ ' + kit.amount(cap) + ' kg of capacity, including ' + assump.capacitySlack + '% slack. Peak week is ' + kit.amount(peak) + ' kg.'
      : 'Select an option in the matrix to size the capacity.';

    var panic = el('panic-line');
    if (!risk.length) {
      panic.className = 'panic-note is-clear';
      panic.innerHTML = '<b>No panic week.</b> Every week of this flow fits inside the storage you selected, so you can hold and sell rather than dump at the gate.';
    } else {
      panic.className = 'panic-note';
      panic.innerHTML = '<b>Panic-selling risk: week' + (risk.length > 1 ? 's ' : ' ') + risk.join(', ') + '.</b> Volume beats capacity by ' +
        kit.amount(Math.max.apply(null, risk.map(function (i) { return weeks[i - 1] - cap; }))) +
        ' kg at its worst. That surplus goes to market in the same week and takes the price down with it — the fix is either more capacity or spreading the pick.';
    }
    el('planner-note').textContent = 'Flow is five editable weeks; edit them to match your plot. Capacity is derived from the option you selected in the matrix, so changing options changes which week breaks.';
  }

  /* ---------- worked example ---------- */

  function worked(r) {
    var input = r.input, crop = r.crop;
    var credits = r.earned.lines.map(function (l) { return escapeText(l.label) + ' −' + l.pts; }).join('; ');
    var row = selectedRow(r);
    var steps = [
      'The batch: <b>' + kit.amount(input.kg) + ' kg</b> × ' + kit.money(input.price) + ' = <b>' + kit.money(r.value) + '</b> of value standing in the sun.',
      'Unimproved loss for ' + escapeText(crop.name.toLowerCase()) + ' is <b>' + crop.baseline + '%</b> (published range ' + crop.range[0] + '–' + crop.range[1] + '%), so the default outcome is ' + kit.amount(input.kg) + ' × ' + kit.money(input.price) + ' × ' + (crop.baseline / 100) + ' = <b>' + kit.money(r.baselineNaira) + '</b> lost.',
      credits
        ? 'Your handling, in points: ' + credits + '. Total −' + kit.pct(r.earned.pts, 0) + ', giving <b>' + kit.pct(r.adjustedPct, 1) + '</b> loss = <b>' + kit.money(r.adjustedNaira) + '</b>. That is <b>' + kit.money(r.recovered) + '</b> recovered on one cycle.'
        : 'No handling credits claimed, so the projection stays at the literature figure of <b>' + kit.pct(crop.baseline, 0) + '</b> = <b>' + kit.money(r.baselineNaira) + '</b>.',
      'Q10 check: life = ' + crop.days + ' × 0.5^((' + kit.amount(input.temp, 0) + ' − ' + crop.baseTemp + ') / ' + assump.q10 + ') = <b>' + kit.amount(r.lifeNow, 2) + ' days</b> of shelf life at ' + kit.amount(input.temp, 0) + ' °C. The sale needs ' + kit.amount(input.days, 1) + ' days — margin <b>' + kit.amount(r.marginNow, 1) + ' days</b>.',
      row
        ? 'Best-ranked suitable option: <b>' + escapeText(row.o.name) + '</b>. Loss would sit at <b>' + kit.pct(row.lossWith, 1) + '</b>, i.e. ' + kit.pct(row.pointsSaved, 1) + ' saved = <b>' + kit.money(row.avoidedCycle) + '</b> avoided per cycle.'
        : 'No option is eligible against your constraints as entered — that is a valid answer, and the handling fixes on the left still apply.',
      row
        ? 'Season arithmetic: ' + kit.money(row.avoidedCycle) + ' × ' + input.cycles + ' cycles = <b>' + kit.money(row.avoidedSeason) + '</b> avoided, less <b>' + kit.money(row.cost.seasonCost) + '</b> of seasonal cost (' + kit.money(row.cost.unitCost) + (row.cost.durable ? ' spread over ' + row.o.seasons + ' seasons' : ' this season only') + ') = net <b>' + kit.money(row.net) + '</b>.'
        : '',
      row && row.paybackCycles !== null
        ? 'Payback: ' + kit.money(row.cost.unitCost) + ' ÷ ' + kit.money(row.avoidedCycle) + ' = <b>' + kit.amount(row.paybackCycles, 1) + ' cycles</b>, or about ' + kit.amount(row.paybackMonths, 1) + ' months if the season is ' + assump.seasonMonths + ' months. The headline case: a ₦190,000 charcoal cooler against ₦45,000 recovered per cycle is <b>4.2 cycles</b>.'
        : '',
      'Planner: peak week <b>' + kit.amount(r.peakWeek) + ' kg</b> against ' + (row ? kit.amount(row.o.capacity * row.cost.units) + ' kg of capacity from ' + row.cost.units + ' unit' + (row.cost.units > 1 ? 's' : '') : 'no selected capacity') + '. Any week above capacity is a forced sale at the gate.',
      'Every percentage on this page is a field estimate from published post-harvest ranges. Your ridge, your variety and your rain year will disagree with it — so weigh three batches into the ledger and run your own numbers.'
    ];
    el('worked').innerHTML = steps.filter(function (s) { return s !== ''; }).map(function (s) { return '<li>' + s + '</li>'; }).join('');
  }

  /* ---------- editable rules ---------- */

  function paintCropTable() {
    var host = el('crop-body');
    var activeId = el('crop').value;
    host.innerHTML = crops.map(function (c) {
      function cell(field, min, max, step, label) {
        return '<td class="num"><input type="number" min="' + min + '" max="' + max + '" step="' + step + '" value="' + c[field] +
          '" data-crop="' + c.id + '" data-field="' + field + '" aria-label="' + escapeAttr(label + ' for ' + c.name) + '"></td>';
      }
      return '<tr data-row="' + c.id + '"' + (c.id === activeId ? ' class="is-crop"' : '') + '>' +
        '<td>' + escapeText(c.name) + '<small class="tiny"> · ' + (c.klass === 'perish' ? 'perishable' : 'dry') + '</small></td>' +
        cell('baseline', 0, 90, 1, 'Baseline loss %') + cell('floor', 0, 90, 1, 'Floor loss %') +
        cell('days', 0.2, 400, 0.1, 'Shelf life days') + cell('baseTemp', -5, 45, 1, 'Base temperature °C') + '</tr>';
    }).join('');
  }

  function paintCreditRules() {
    el('credit-defaults').innerHTML = CREDITS.map(function (c) {
      return '<li><span>' + escapeText(c.label) + '</span>' +
        '<input type="number" min="0" max="40" step="1" value="' + creditPts[c.id] + '" data-credit="' + c.id + '" aria-label="Loss points for: ' + escapeAttr(c.label) + '"></li>';
    }).join('');
  }

  function paintConstGrids() {
    el('const-price').innerHTML = Object.keys(DEFAULT_PRICES).map(function (k) {
      return '<label>' + escapeText(priceLabel(k)) + '<input type="number" min="0" max="5000000" step="500" value="' + prices[k] +
        '" data-price="' + k + '" aria-label="' + escapeAttr(priceLabel(k)) + ' in naira"></label>';
    }).join('');
    el('const-sizing').innerHTML = Object.keys(DEFAULTS).map(function (k) {
      return '<label>' + escapeText(assumpLabel(k)) + '<input type="number" min="0" max="1000" step="' + (k === 'q10' || k === 'seasonMonths' ? 1 : 5) + '" value="' + assump[k] +
        '" data-assump="' + k + '" aria-label="' + escapeAttr(assumpLabel(k)) + '"></label>';
    }).join('');
    var crop = cropById(el('crop').value);
    el('const-achieved').innerHTML = OPTIONS.filter(function (o) { return o.achieved[crop.klass] !== undefined; }).map(function (o) {
      var key = o.id + '-' + crop.klass;
      var value = crop.klass === 'perish' ? o.achieved.perish : o.achieved.dry;
      return '<label>' + escapeText(o.name) + ' — achieved loss <span class="tiny">(' + (crop.klass === 'perish' ? 'perishable' : 'dry') + ')</span>' +
        '<input type="number" min="0" max="80" step="0.5" value="' + value + '" data-ach="' + key + '" aria-label="Achieved loss percentage for ' + escapeAttr(o.name) + '"></label>';
    }).join('');
  }

  function priceLabel(k) {
    return { bagPrice: 'Hermetic bag (per 50 kg)', storePrice: 'Improved store (built)', dryerPrice: 'Solar tray dryer', coolerPrice: 'Charcoal cooler / CoolPort',
      coolbotPrice: 'CoolBot cold room', evapPrice: 'Evaporative brick chamber', vanPrice: 'Refrigerated van (per trip)', processPrice: 'Processing line',
      coldStoreWeekly: 'Shared cold store (per week)' }[k] || k;
  }
  function assumpLabel(k) {
    return { q10: 'Q10 temperature step (°C)', seasonMonths: 'Season length (months)', bagSizeKg: 'Hermetic bag size (kg)',
      capacitySlack: 'Capacity headroom (%)', vanLowPrice: 'Van hire low (₦ per trip)', vanHighPrice: 'Van hire high (₦ per trip)' }[k] || k;
  }

  function paintLimits() {
    var items = [
      'Loss percentages are field estimates from published post-harvest literature ranges for Nigerian crops. They shift with season, variety, altitude, ridge position and who did the picking.',
      'A proper cold-chain audit — logger in the crate, twice a season — or your state agricultural development programme will confirm figures this page can only estimate.',
      'Aflatoxin cannot be assessed by a calculator. Groundnut and maize need laboratory testing before you claim they are safe to sell or eat.',
      'Q10 is a rule of thumb from respiratory physiology, not a model of your specific cultivar; chilling-sensitive crops stop improving below their own damage threshold.',
      'Prices are indicative 2026 market rates. Get local quotations: a 20–30% spread between suppliers is normal, and none of it is visible from here.',
      'Nothing is uploaded. Batches live in this browser only — clearing site data clears the ledger, so export the CSV before you change phones.'
    ];
    el('limits').innerHTML = items.map(function (t) { return '<li>' + escapeText(t) + '</li>'; }).join('');
  }

  function bindRules() {
    kit.on(el('crop-body'), 'input', '[data-crop]', function (e, input) {
      var crop = cropById(input.dataset.crop);
      var field = input.dataset.field;
      var value = kit.num(input.value);
      if (value < 0) return;
      crop[field] = value;
      if (field === 'floor') crop.floor = Math.min(crop.floor, crop.baseline);
      var map = {};
      crops.forEach(function (c) { map[c.id] = { baseline: c.baseline, floor: c.floor, days: c.days, baseTemp: c.baseTemp }; });
      storeCrops.write(map);
      paint();
    });
    kit.on(el('credit-defaults'), 'input', '[data-credit]', function (e, input) {
      creditPts[input.dataset.credit] = kit.clamp(kit.num(input.value), 0, 40);
      storeCredits.write(CREDITS.map(function (c) { return { id: c.id, pts: creditPts[c.id] }; }));
      paintCredits();
      paint();
    });
    kit.on(el('const-price'), 'input', '[data-price]', function (e, input) {
      prices[input.dataset.price] = Math.max(0, kit.num(input.value));
      storePrices.write(prices);
      paint();
    });
    kit.on(el('const-sizing'), 'input', '[data-assump]', function (e, input) {
      assump[input.dataset.assump] = Math.max(0, kit.num(input.value));
      storeAssump.write(assump);
      paint();
    });
    kit.on(el('const-achieved'), 'input', '[data-ach]', function (e, input) {
      var parts = input.dataset.ach.split('-');
      var o = OPTIONS.filter(function (x) { return x.id === parts[0]; })[0];
      if (!o) return;
      o.achieved[parts[1]] = kit.clamp(kit.num(input.value), 0, 80);
      paint();
    });
    el('restore-credits').addEventListener('click', function () {
      CREDITS.forEach(function (c) { creditPts[c.id] = c.pts; });
      storeCredits.write(CREDITS.map(function (c) { return { id: c.id, pts: c.pts }; }));
      paintCredits();
      paintCreditRules();
      paint();
      kit.toast('Default handling credits restored');
    });
    el('reset-assumptions').addEventListener('click', function () {
      crops = loadCropsDefault();
      prices = Object.assign({}, DEFAULT_PRICES);
      assump = Object.assign({}, DEFAULTS);
      CREDITS.forEach(function (c) { creditPts[c.id] = c.pts; });
      OPTIONS.forEach(function (o, i) { o.achieved = JSON.parse(JSON.stringify(ORIGINAL_ACHIEVED[i])); });
      storeCrops.clear(); storePrices.clear(); storeAssump.clear();
      storeCredits.write(CREDITS.map(function (c) { return { id: c.id, pts: c.pts }; }));
      el('storage-temp').value = cropById(el('crop').value).ambient;
      el('baseline-loss').value = cropById(el('crop').value).baseline;
      paintCropTable(); paintCreditRules(); paintConstGrids(); paintCredits();
      paint();
      kit.toast('All defaults restored');
    });
  }

  /* deep copy: the achieved-loss editor mutates o.achieved in place, so keeping
     the live references here would make "restore defaults" restore nothing */
  var ORIGINAL_ACHIEVED = OPTIONS.map(function (o) { return JSON.parse(JSON.stringify(o.achieved)); });
  function loadCropsDefault() {
    return CROPS.map(function (c) { return Object.assign({}, c); });
  }

  /* ---------- credits UI ---------- */

  function paintCredits() {
    var host = el('credit-list');
    host.innerHTML = CREDITS.map(function (c) {
      var on = el('credit-' + c.id) ? el('credit-' + c.id).checked : false;
      return '<li class="' + (on ? 'is-on' : '') + '">' +
        '<input type="checkbox" id="credit-' + c.id + '"' + (on ? ' checked' : '') + ' aria-label="' + escapeAttr(c.label) + '">' +
        '<label for="credit-' + c.id + '">' + escapeText(c.label) + '<small>' + escapeText(c.small) + '</small></label>' +
        '<span class="credit-pts">−' + creditPts[c.id] + ' pts</span></li>';
    }).join('');
  }

  function setCreditState(state) {
    CREDITS.forEach(function (c) {
      var box = el('credit-' + c.id);
      if (box) box.checked = !!state[c.id];
    });
    paintCredits();
    paint();
  }

  /* ---------- batch ledger ---------- */

  function logBatch() {
    var r = lastResult;
    if (!r || r.input.kg <= 0) { kit.toast('Enter the harvest weight first'); el('batch-kg').focus(); return; }
    var row = selectedRow(r);
    var list = batches();
    var label = r.input.label || (r.crop.name + ' batch ' + (list.length + 1));
    list.push({
      id: kit.uid('batch'), date: Date.now(), label: label, crop: r.crop.name, cropId: r.crop.id, klass: r.crop.klass,
      kg: round(r.input.kg), price: round(r.input.price), value: Math.round(r.value),
      storage: row ? row.o.name : 'Handling only', lossPct: round(r.adjustedPct), lossNaira: Math.round(r.adjustedNaira),
      baselinePct: r.crop.baseline, actual: null, shelfDays: round(r.lifeNow), marginDays: round(r.marginNow),
      daysToMarket: r.input.days, temp: r.input.temp
    });
    if (!storeBatch.write(list)) { kit.toast('This browser blocked saving — export the CSV instead'); return; }
    paintBatches(list[list.length - 1].id);
    kit.toast('Batch logged. Weigh the actual loss after the sale.');
  }

  function paintBatches(newId) {
    var list = batches();
    var body = el('batch-body');
    var foot = el('batch-foot');
    var saved = list.reduce(function (a, b) { return a + (b.actual === null ? 0 : b.lossNaira - b.actual); }, 0);
    var measured = list.filter(function (b) { return b.actual !== null; }).length;

    el('batch-count').textContent = list.length + (list.length === 1 ? ' batch' : ' batches') +
      (measured ? ' · this season you saved ' + kit.money(saved) + ' against projection' : '');
    el('batch-empty').hidden = list.length > 0;
    paintRecord(list);

    if (!list.length) { body.innerHTML = ''; foot.innerHTML = ''; return; }

    body.innerHTML = list.map(function (b) {
      var diff = b.actual === null ? null : b.lossNaira - b.actual;
      return '<tr' + (b.id === newId ? ' class="is-new"' : '') + ' data-batch="' + b.id + '">' +
        '<td>' + escapeText(b.label) + '</td>' +
        '<td class="num">' + kit.dateLabel(b.date) + '</td>' +
        '<td>' + escapeText(b.crop) + '</td>' +
        '<td class="num">' + kit.amount(b.kg) + '</td>' +
        '<td class="num">' + kit.money(b.value) + '</td>' +
        '<td>' + escapeText(b.storage) + '</td>' +
        '<td class="num">' + kit.money(b.lossNaira) + ' <small class="tiny">(' + kit.pct(b.lossPct, 1) + ')</small></td>' +
        '<td class="actual-cell' + (b.actual === null ? ' is-empty' : '') + '"><input type="number" min="0" step="500" value="' +
        (b.actual === null ? '' : b.actual) + '" data-actual="' + b.id + '" placeholder="after sale" aria-label="Actual loss in naira for batch ' + escapeAttr(b.label) + '"></td>' +
        '<td class="num ' + (diff > 0 ? 'pos' : diff < 0 ? 'neg' : '') + '">' + (diff === null ? 'not weighed' : (diff >= 0 ? '+' : '−') + kit.money(Math.abs(diff))) + '</td>' +
        '<td><button class="row-del" type="button" data-del="' + b.id + '" aria-label="Remove ' + escapeAttr(b.label) + '">×</button></td>' +
        '</tr>';
    }).join('');

    var tKg = list.reduce(function (a, b) { return a + b.kg; }, 0);
    var tValue = list.reduce(function (a, b) { return a + b.value; }, 0);
    var tProj = list.reduce(function (a, b) { return a + b.lossNaira; }, 0);
    var tActual = list.reduce(function (a, b) { return a + (b.actual || 0); }, 0);
    foot.innerHTML = '<tr><td>Total</td><td class="num">' + list.length + '</td><td>' + (list.length > 1 ? measured + ' weighed' : '') +
      '</td><td class="num">' + kit.amount(tKg) + '</td><td class="num">' + kit.money(tValue) + '</td><td>—</td><td class="num">' +
      kit.money(tProj) + '</td><td class="num">' + (measured ? kit.money(tActual) : '—') + '</td><td class="num">' +
      (measured ? (saved >= 0 ? '+' : '−') + kit.money(Math.abs(saved)) : '—') + '</td><td></td></tr>';
  }

  function paintRecord(list) {
    var meta = el('record-meta');
    var dl = el('record-list');
    if (!list.length) { meta.textContent = 'No batches logged yet.'; dl.innerHTML = ''; return; }
    meta.textContent = list.length + (list.length === 1 ? ' batch' : ' batches') + ' · hand this sheet to the store manager · prepared ' + kit.dateLabel(Date.now());
    dl.innerHTML = list.map(function (b) {
      return '<dt>' + escapeText(b.label) + '</dt><dd>' + escapeText(b.crop) + ' · ' + kit.amount(b.kg) + ' kg at ' + kit.money(b.price) +
        '/kg = ' + kit.money(b.value) + ' · storage: ' + escapeText(b.storage) + ' · projected loss ' + kit.pct(b.lossPct, 1) + ' = ' +
        kit.money(b.lossNaira) + ' · actual: ' + (b.actual === null ? 'not weighed' : kit.money(b.actual)) + ' · difference: ' +
        (b.actual === null ? '—' : kit.money(b.lossNaira - b.actual)) + ' · shelf life on arrival ' + kit.amount(b.shelfDays, 1) +
        ' d vs ' + kit.amount(b.daysToMarket, 1) + ' d trip at ' + kit.amount(b.temp, 0) + ' °C · dated ' + kit.dateLabel(b.date) + '</dd>';
    }).join('') + '<dt>Season position</dt><dd>Projected loss ' + kit.money(list.reduce(function (a, b) { return a + b.lossNaira; }, 0)) +
      ' · weighed actual ' + kit.money(list.reduce(function (a, b) { return a + (b.actual || 0); }, 0)) + '</dd>';
  }

  function bindBatches() {
    kit.on(el('batch-body'), 'input', '[data-actual]', function (e, input) {
      var list = batches();
      list.forEach(function (b) {
        if (b.id === input.dataset.actual) b.actual = input.value === '' ? null : Math.max(0, kit.num(input.value));
      });
      storeBatch.write(list);
      paintBatches();
    });
    kit.on(el('batch-body'), 'change', '[data-actual]', function (e, input) {
      var list = batches();
      list.forEach(function (b) {
        if (b.id === input.dataset.actual) b.actual = input.value === '' ? null : Math.max(0, kit.num(input.value));
      });
      storeBatch.write(list);
      paintBatches();
    });
    kit.on(el('batch-body'), 'click', '[data-del]', function (e, btn) {
      var list = batches().filter(function (b) { return b.id !== btn.dataset.del; });
      storeBatch.write(list);
      paintBatches();
      kit.toast('Batch removed');
    });
    el('log-batch').addEventListener('click', logBatch);
    el('clear-batches').addEventListener('click', function () {
      if (!batches().length) { kit.toast('The ledger is already empty'); return; }
      if (!window.confirm('Remove every saved batch from this browser?')) return;
      storeBatch.clear();
      paintBatches();
      kit.toast('Ledger cleared');
    });
    el('export-csv').addEventListener('click', function () {
      var list = batches();
      if (!list.length) { kit.toast('Log a batch first'); return; }
      kit.csv([
        ['Batch', 'Date', 'Crop', 'Class', 'kg', 'Value per kg (NGN)', 'Batch value (NGN)', 'Storage chosen', 'Unimproved loss %', 'Projected loss %', 'Projected loss (NGN)', 'Actual loss (NGN)', 'Difference (NGN)', 'Shelf life on arrival (days)', 'Days to market', 'Storage temp (C)']
      ].concat(list.map(function (b) {
        return [b.label, kit.dateLabel(b.date), b.crop, b.klass, b.kg, b.price, b.value, b.storage, b.baselinePct, round(b.lossPct), b.lossNaira,
          b.actual === null ? '' : b.actual, b.actual === null ? '' : b.lossNaira - b.actual, b.shelfDays, b.daysToMarket, b.temp];
      })).concat([[
        'TOTAL', '', '', '', round(list.reduce(function (a, b) { return a + b.kg; }, 0)), '',
        list.reduce(function (a, b) { return a + b.value; }, 0), '', '', '',
        list.reduce(function (a, b) { return a + b.lossNaira; }, 0),
        list.reduce(function (a, b) { return a + (b.actual || 0); }, 0),
        list.reduce(function (a, b) { return a + (b.actual === null ? 0 : b.lossNaira - b.actual); }, 0), '', '', ''
      ]]), 'cropkeep-batches-' + new Date().toISOString().slice(0, 10) + '.csv');
    });
    el('print-record').addEventListener('click', function () {
      if (!batches().length) { kit.toast('Log a batch to print the record'); return; }
      kit.print();
    });
  }

  /* ---------- boot ---------- */

  function fillCrops() {
    var select = el('crop');
    select.innerHTML = crops.map(function (c) {
      return '<option value="' + c.id + '">' + escapeText(c.name) + '</option>';
    }).join('');
    select.value = 'tomato';
  }

  function paintFormForCrop() {
    var crop = cropById(el('crop').value);
    el('storage-temp').value = crop.ambient;
    el('baseline-loss').value = crop.baseline;
  }

  function paint() {
    var input = readInput();
    /* the editable baseline field overrides the literature mid-point */
    if (input.baselineOverride !== null && input.baselineOverride >= 0) input.crop = Object.assign({}, input.crop, { baseline: input.baselineOverride });
    var weeks = readWeeks();
    lastResult = compute(input, Math.max.apply(null, weeks) || 0);
    paintResult(lastResult);
  }

  function paintResult(r) {
    paintOutputs(r);
  }

  kit.boot({
    ready: function () {
      fillCrops();
      paintFormForCrop();
      paintCredits();
      paintCropTable();
      paintCreditRules();
      paintConstGrids();
      paintLimits();
      bindRules();
      bindBatches();

      buildWeeks();

      el('credit-list').addEventListener('change', function (e) {
        if (e.target && e.target.matches('input[type=checkbox]')) {
          var li = e.target.closest('li');
          if (li) li.classList.toggle('is-on', e.target.checked);
          paint();
        }
      });

      kit.on(document.getElementById('options'), 'change', '[name=pick]', function (e, input) {
        chosen = { id: input.value };
        if (lastResult) paintResult(lastResult);
      });
      kit.on(el('matrix-body'), 'click', 'tr[data-opt]', function (e, row) {
        var radio = row.querySelector('[name=pick]');
        if (radio) { radio.checked = true; chosen = { id: radio.value }; paintResult(lastResult); }
      });
      el('select-option').addEventListener('click', function () {
        kit.toast('Planner now sized against this option');
        document.getElementById('planner').scrollIntoView({ behavior: kit.reduce ? 'auto' : 'smooth', block: 'start' });
      });

      kit.$$('#loss-form [data-credit-preset]').forEach(function (btn) {
        btn.addEventListener('click', function () {
          kit.$$('#loss-form [data-credit-preset]').forEach(function (b) { b.classList.remove('is-on'); });
          btn.classList.add('is-on');
          var which = btn.dataset.creditPreset;
          var state = {};
          if (which === 'crates') { state = { morning: true, shade: true, crates: true, transport: true }; }
          else if (which === 'all') { CREDITS.forEach(function (c) { state[c.id] = true; }); }
          setCreditState(state);
        });
      });

      kit.$$('#loss-form input, #loss-form select, #planner input, #shelf input').forEach(function (node) {
        node.addEventListener('input', paint);
        node.addEventListener('change', paint);
      });
      el('crop').addEventListener('change', function () {
        paintFormForCrop();
        chosen = null;
        paintCropTable();
        paintConstGrids();
        paint();
      });

      paint();
      paintBatches();
    }
  });
})();
