/* SunCost — solar + battery sizing against generator fuel cost.
   Every figure is computed in the browser from the inputs on the page. */
(function () {
  'use strict';

  var CITIES = [
    { name: 'Onuora / Jalingo', psh: 6.1 },
    { name: 'Maiduguri', psh: 6.0 },
    { name: 'Kano', psh: 5.8 },
    { name: 'Kaduna', psh: 5.6 },
    { name: 'Abuja', psh: 5.5 },
    { name: 'Benin City', psh: 5.3 },
    { name: 'Ibadan', psh: 5.2 },
    { name: 'Lagos', psh: 5.1 },
    { name: 'Enugu', psh: 4.9 },
    { name: 'Port Harcourt', psh: 4.7 }
  ];

  var PRESETS = [
    { name: 'Fridge', w: 150, q: 1, h: 24, night: true, motor: true },
    { name: 'Deep freezer', w: 250, q: 1, h: 8, night: false, motor: true },
    { name: 'Standing fan', w: 75, q: 2, h: 12, night: true, motor: true },
    { name: 'LED bulb', w: 9, q: 6, h: 5, night: true, motor: false },
    { name: 'Television', w: 65, q: 1, h: 5, night: false, motor: false },
    { name: 'WiFi router', w: 12, q: 1, h: 24, night: true, motor: false },
    { name: 'Laptop', w: 65, q: 1, h: 4, night: false, motor: false },
    { name: 'Water pump', w: 750, q: 1, h: 0.5, night: false, motor: true },
    { name: 'Air conditioner', w: 1100, q: 1, h: 4, night: false, motor: true },
    { name: 'Pressing iron', w: 1000, q: 1, h: 0.3, night: false, motor: false },
    { name: 'Sewing machine', w: 90, q: 1, h: 6, night: false, motor: true }
  ];

  /* A realistic Ibadan family day: 8,633 Wh before losses. */
  var STARTER = [
    { name: 'Fridge', w: 150, q: 1, h: 24, night: true, motor: true },
    { name: 'Deep freezer', w: 250, q: 1, h: 8, night: false, motor: true },
    { name: 'Standing fan', w: 75, q: 2, h: 12, night: true, motor: true },
    { name: 'LED bulb', w: 9, q: 8, h: 5, night: true, motor: false },
    { name: 'Television', w: 65, q: 1, h: 5, night: false, motor: false },
    { name: 'WiFi router', w: 12, q: 1, h: 24, night: true, motor: false },
    { name: 'Laptop', w: 65, q: 1, h: 4, night: false, motor: false }
  ];

  var RATINGS = [1, 1.5, 2, 3, 3.5, 5, 6, 8, 10, 12];

  var DEFAULTS = {
    lossFactor: 15, sysEff: 75, oversize: 125, pvRatio: 1.5, surgeMult: 3,
    dodLit: 90, dodLead: 80, stringLimit: 200, panelW: 275,
    panelPrice: 180000, lithiumPrice: 340000, tubularPrice: 165000, inverterPrice: 95000,
    hybridInstall: 250000, mounting: 120000, petrol: 1650, diesel: 1950,
    lithiumLife: 8, tubularLife: 3, sunMaint: 25000, genLife: 5,
    downPct: 40, loanMonths: 24, loanRate: 15
  };

  var SPEC_GROUPS = {
    'const-sizing': [
      { k: 'lossFactor', label: 'Inverter + wiring loss factor', unit: '%', min: 0, max: 60, step: 0.5, help: 'Applied to daily and night energy.' },
      { k: 'sysEff', label: 'System efficiency, panel to battery', unit: '%', min: 30, max: 95, step: 1, help: 'Heat, dust, mismatch, charge losses.' },
      { k: 'oversize', label: 'Inverter oversize on peak load', unit: '%', min: 100, max: 250, step: 5, help: '125% is the usual safety habit.' },
      { k: 'pvRatio', label: 'Max PV input per kVA of inverter', unit: '×', min: 0.5, max: 3, step: 0.1, help: 'Hybrid MPPT usually allows ~1.5×.' },
      { k: 'surgeMult', label: 'Motor inrush multiple', unit: '×', min: 1, max: 8, step: 0.5, help: 'Fridge, freezer, pump, AC at switch-on.' },
      { k: 'dodLit', label: 'Depth of discharge — lithium', unit: '%', min: 50, max: 95, step: 1 },
      { k: 'dodLead', label: 'Depth of discharge — tubular', unit: '%', min: 40, max: 90, step: 1 },
      { k: 'stringLimit', label: 'Sensible single-string limit', unit: 'Ah', min: 50, max: 600, step: 10 },
      { k: 'panelW', label: 'Panel rating', unit: 'W', min: 50, max: 700, step: 5 }
    ],
    'const-price': [
      { k: 'panelPrice', label: 'Price per panel', unit: '₦', min: 0, max: 2000000, step: 1000, help: 'Indicative ₦180,000 for a 275 W panel.' },
      { k: 'lithiumPrice', label: 'Lithium (LiFePO₄)', unit: '₦ per kWh', min: 0, max: 2000000, step: 5000 },
      { k: 'tubularPrice', label: 'Tubular lead-acid', unit: '₦ per kWh', min: 0, max: 2000000, step: 5000 },
      { k: 'inverterPrice', label: 'Inverter', unit: '₦ per kVA', min: 0, max: 2000000, step: 5000 },
      { k: 'hybridInstall', label: 'Hybrid unit + labour', unit: '₦', min: 0, max: 5000000, step: 10000 },
      { k: 'mounting', label: 'Mounting, cabling, protection', unit: '₦', min: 0, max: 5000000, step: 10000, help: 'Rails, DC cable, breakers, surge, earth.' },
      { k: 'petrol', label: 'Default petrol price', unit: '₦ per litre', min: 0, max: 20000, step: 50, help: 'Used when you switch fuel type.' },
      { k: 'diesel', label: 'Default diesel (AGO) price', unit: '₦ per litre', min: 0, max: 20000, step: 50 }
    ],
    'const-fin': [
      { k: 'lithiumLife', label: 'Lithium replacement interval', unit: 'yrs', min: 1, max: 20, step: 1 },
      { k: 'tubularLife', label: 'Tubular replacement interval', unit: 'yrs', min: 1, max: 10, step: 1 },
      { k: 'genLife', label: 'Generator replacement interval', unit: 'yrs', min: 1, max: 15, step: 1 },
      { k: 'sunMaint', label: 'Solar upkeep', unit: '₦ per year', min: 0, max: 2000000, step: 5000, help: 'Cleaning, torque checks, one call-out.' },
      { k: 'downPct', label: 'Down payment', unit: '%', min: 0, max: 100, step: 5 },
      { k: 'loanMonths', label: 'Instalment term', unit: 'months', min: 1, max: 120, step: 1 },
      { k: 'loanRate', label: 'Instalment interest', unit: '% per year', min: 0, max: 100, step: 0.5 }
    ]
  };

  var assStore = kit.store('suncost:assumptions', null);
  var loadStore = kit.store('suncost:load', null);
  var quoteStore = kit.store('suncost:quotes', []);
  var pshStore = kit.store('suncost:psh', null);

  var assumptions = normaliseAssumptions(assStore.read());
  var pshTable = readPsh();
  var load = (loadStore.read() || STARTER.map(clone)).map(sanitiseRow);
  var ticks = {};
  var lastResult = null;

  /* ---------- utilities ---------- */

  function el(id) { return document.getElementById(id); }
  function clone(row) { return JSON.parse(JSON.stringify(row)); }
  function round(n) { return Math.round((Number(n) || 0) * 100) / 100; }

  function normaliseAssumptions(saved) {
    var out = {};
    Object.keys(DEFAULTS).forEach(function (k) {
      var v = saved ? Number(saved[k]) : NaN;
      out[k] = isFinite(v) && v >= 0 ? v : DEFAULTS[k];
    });
    return out;
  }

  function readPsh() {
    var saved = pshStore.read() || {};
    var map = {};
    CITIES.forEach(function (c) {
      var v = Number(saved[c.name]);
      map[c.name] = isFinite(v) && v > 0 ? v : c.psh;
    });
    return map;
  }

  function sanitiseRow(row) {
    return {
      name: String(row.name || '').slice(0, 34) || 'Appliance',
      w: kit.clamp(kit.num(row.w), 0, 20000),
      q: kit.clamp(Math.round(kit.num(row.q)), 0, 99),
      h: kit.clamp(kit.num(row.h), 0, 24),
      night: !!row.night,
      motor: !!row.motor
    };
  }

  function escapeText(text) {
    var div = document.createElement('div');
    div.textContent = String(text);
    return div.innerHTML;
  }

  function escapeAttr(text) {
    return String(text).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
  }

  function roundRating(kva) {
    if (!(kva > 0)) return 0;
    for (var i = 0; i < RATINGS.length; i++) {
      if (RATINGS[i] >= kva - 0.0001) return RATINGS[i];
    }
    return RATINGS[RATINGS.length - 1];
  }

  function wh(row) { return row.w * row.q * row.h; }
  function watts(row) { return row.w * row.q; }

  /* ---------- form ---------- */

  function readCase() {
    return {
      city: el('city').value,
      psh: kit.clamp(kit.num(el('sun-hours').value), 0, 12),
      nightHours: kit.clamp(kit.num(el('night-hours').value), 0, 24),
      autonomy: kit.clamp(kit.num(el('autonomy').value), 0, 8),
      chemistry: el('chemistry').value,
      volts: kit.num(el('bank-volts').value) || 48,
      budget: kit.num(el('budget').value),
      label: String(el('quote-label').value || '').trim(),
      fuel: el('fuel-type').value,
      fuelPrice: kit.num(el('fuel-price').value),
      genLh: kit.num(el('gen-lh').value),
      genHours: kit.num(el('gen-hours').value),
      genSvc: kit.num(el('gen-svc').value),
      genPrice: kit.num(el('gen-price').value),
      horizon: kit.num(el('horizon').value) || 5,
      mode: el('fin-mode').value,
      paygRate: kit.num(el('payg-rate').value),
      paygYears: kit.clamp(kit.num(el('payg-years').value), 0.5, 12)
    };
  }

  /* ---------- engine ---------- */

  function compute(cases) {
    var a = assumptions;
    var dailyRaw = 0, peak = 0, nightRaw = 0, motorW = 0, inrush = 0;

    load.forEach(function (row) {
      dailyRaw += wh(row);
      peak += watts(row);
      if (row.motor) { motorW += watts(row); inrush += watts(row) * a.surgeMult; }
      else { inrush += watts(row); }
      if (row.night) nightRaw += row.w * row.q * Math.min(row.h, cases.nightHours);
    });

    var lossMul = 1 + a.lossFactor / 100;
    var daily = dailyRaw / 1000 * lossMul;
    var nightKwh = nightRaw / 1000 * lossMul;

    var idealKw = cases.psh > 0 ? daily / cases.psh : 0;
    var arrayKw = a.sysEff > 0 ? idealKw / (a.sysEff / 100) : 0;
    var panelCount = arrayKw > 0 && a.panelW > 0 ? Math.ceil(arrayKw * 1000 / a.panelW) : 0;
    var arrayActual = panelCount * a.panelW / 1000;

    var kvaByLoad = roundRating(peak * a.oversize / 100 / 1000);
    var kvaByPv = roundRating(arrayKw / a.pvRatio);
    var kva = Math.max(kvaByLoad, kvaByPv);
    var pvLimited = arrayActual > kva * a.pvRatio + 0.001;

    var dod = (cases.chemistry === 'lithium' ? a.dodLit : a.dodLead) / 100;
    var battKwh = dod > 0 ? nightKwh * cases.autonomy / dod : 0;
    var ah = cases.volts > 0 ? battKwh * 1000 / cases.volts : 0;
    var battUnit = cases.chemistry === 'lithium' ? a.lithiumPrice : a.tubularPrice;
    var battCost = battKwh * battUnit;
    var life = cases.chemistry === 'lithium' ? a.lithiumLife : a.tubularLife;

    var lineItems = {
      panels: panelCount * a.panelPrice,
      inverter: kva * a.inverterPrice,
      battery: battCost,
      hybrid: a.hybridInstall,
      mounting: a.mounting
    };
    var capex = lineItems.panels + lineItems.inverter + lineItems.battery + lineItems.hybrid + lineItems.mounting;

    var litresDay = cases.genLh * cases.genHours;
    var fuelDay = litresDay * cases.fuelPrice;
    var svcDay = litresDay * cases.genSvc;
    var genDaily = fuelDay + svcDay;
    var genMonth = genDaily * 365 / 12;
    var genYear = genDaily * 365;

    function solarAt(months) {
      var yrs = months / 12;
      var repl = life > 0 ? Math.floor((yrs - 1e-6) / life) : 0;
      return capex + a.sunMaint * yrs + battCost * repl;
    }
    function generatorAt(months) {
      var yrs = months / 12;
      var repl = a.genLife > 0 ? Math.floor((yrs - 1e-6) / a.genLife) : 0;
      return cases.genPrice * (1 + repl) + genDaily * 365 * yrs;
    }

    var span = Math.max(12, Math.round(cases.horizon * 12));
    var crossover = 0;
    for (var m = 1; m <= 180; m++) {
      if (solarAt(m) <= generatorAt(m)) { crossover = m; break; }
    }

    var points = [];
    var steps = 24;
    for (var i = 0; i <= steps; i++) {
      var months = span * i / steps;
      points.push({ m: months, sun: solarAt(months), gen: generatorAt(months) });
    }

    var solarTotal = solarAt(span);
    var genTotal = generatorAt(span);

    return {
      a: a, c: cases, load: load,
      dailyRaw: dailyRaw / 1000, daily: daily, nightRaw: nightRaw / 1000, nightKwh: nightKwh,
      peak: peak, motorW: motorW, inrush: inrush,
      idealKw: idealKw, arrayKw: arrayKw, panelCount: panelCount, arrayActual: arrayActual,
      kvaByLoad: kvaByLoad, kvaByPv: kvaByPv, kva: kva, pvLimited: pvLimited,
      dod: dod, battKwh: battKwh, ah: ah, battCost: battCost, life: life,
      lineItems: lineItems, capex: capex,
      litresDay: litresDay, fuelDay: fuelDay, genDaily: genDaily, genMonth: genMonth, genYear: genYear,
      solarAt: solarAt, generatorAt: generatorAt, crossover: crossover,
      points: points, span: span, solarTotal: solarTotal, genTotal: genTotal,
      genFuelShare: genYear > 0 ? (genYear * cases.horizon) / genTotal * 100 : 0,
      perYear: cases.horizon > 0 ? solarTotal / cases.horizon : 0,
      difference: genTotal - solarTotal,
      empty: dailyRaw <= 0
    };
  }

  function annuity(principal, annualRatePct, periodsPerYear, years) {
    var n = Math.max(1, Math.round(periodsPerYear * years));
    var i = (annualRatePct / 100) / periodsPerYear;
    if (i <= 0) return principal / n;
    return principal * i / (1 - Math.pow(1 + i, -n));
  }

  /* ---------- painting ---------- */

  function setFigure(id, value) {
    var node = el(id);
    if (!node) return;
    var text = value === null ? '—' : String(value);
    if (node.textContent === text) return;
    node.textContent = text;
    if (!kit.reduce && ticks[id] !== undefined && ticks[id] !== text) {
      node.classList.remove('tick');
      void node.offsetWidth;
      node.classList.add('tick');
    }
    ticks[id] = text;
  }

  function note(id, text, tone) {
    var node = el(id);
    if (!node) return;
    node.textContent = text || '';
    node.classList.toggle('is-on', !!text);
    if (tone) node.setAttribute('data-tone', tone);
  }

  function paintResult(r) {
    el('load-totals').textContent = r.dailyRaw
      ? 'Σ ' + kit.amount(r.dailyRaw * 1000, 0) + ' Wh/day · peak ' + kit.amount(r.peak) + ' W · night-critical ' + kit.amount(r.nightRaw * 1000, 0) + ' Wh'
      : 'no appliances yet — add one below';

    if (r.empty) {
      ['crossover', 'r-daily', 'r-peak', 'r-array', 'r-inverter', 'r-battery', 'r-night', 'r-capex', 'r-peryear', 'r-gen5', 'r-solar5', 'r-diff5']
        .forEach(function (id) { setFigure(id, null); });
      el('crossover-note').textContent = 'add at least one appliance to begin';
      verdict('Nothing to size yet', 'Tick the appliances you actually run. The array, inverter and bank all follow from that list.', '');
      note('bank-warn', '');
      note('surge-note', '');
      el('gen-litres').textContent = '—';
      return;
    }

    var months = r.crossover;
    setFigure('crossover', months ? 'Month ' + months : 'Beyond 15 yrs');
    el('crossover-note').textContent = months
      ? 'Cumulative solar cost falls below the generator in month ' + months +
        ' (' + (months / 12).toFixed(1) + ' years). After that every month is ' + kit.money(r.genMonth) + ' kept in your pocket.'
      : 'Even at these prices the generator stays cheaper for over a decade. Cut your fuel hours or the load before buying.';

    setFigure('r-daily', kit.amount(r.daily, 2) + ' kWh');
    setFigure('r-peak', kit.amount(r.peak) + ' W');
    setFigure('r-array', kit.amount(r.arrayActual, 2) + ' kW · ' + r.panelCount + ' × ' + r.a.panelW + ' W');
    setFigure('r-inverter', r.kva + ' kVA');
    setFigure('r-battery', kit.amount(r.battKwh, 2) + ' kWh · ' + kit.amount(r.ah) + ' Ah @ ' + r.c.volts + ' V');
    setFigure('r-night', kit.amount(r.nightKwh, 2) + ' kWh over ' + kit.amount(r.c.nightHours, 1) + ' h');
    setFigure('r-capex', kit.money(r.capex));
    setFigure('r-peryear', kit.money(r.perYear) + ' / yr');

    el('lab-gen5').textContent = 'Generator, ' + r.c.horizon + ' yrs';
    el('lab-solar5').textContent = 'Solar, ' + r.c.horizon + ' yrs';
    el('lab-diff5').textContent = 'Difference, ' + r.c.horizon + ' yrs';
    setFigure('r-gen5', kit.money(r.genTotal));
    setFigure('r-solar5', kit.money(r.solarTotal));
    setFigure('r-diff5', kit.money(Math.max(0, r.difference)));

    el('gen-litres').textContent = kit.amount(r.litresDay, 2) + ' L/day ≈ ' + kit.money(r.genDaily) + ' a day at the pump';

    note('bank-warn', r.ah > r.a.stringLimit
      ? 'That is ' + kit.amount(r.ah) + ' Ah on one ' + r.c.volts + ' V string — beyond the ' + r.a.stringLimit +
        ' Ah this tool treats as sensible. Go to 48 V, parallel two strings, or trim the night load. Long thin strings are how banks die.'
      : '');
    note('surge-note', r.motorW > 0
      ? 'Motor inrush at switch-on is about ' + kit.amount(r.inrush) + ' W (' + r.a.surgeMult +
        '× the ' + kit.amount(r.motorW) + ' W of motors). A ' + r.kva + ' kVA unit survives roughly ' + kit.amount(r.kva * 2000) +
        ' W for a few seconds — start pumps and ACs one at a time, or fit a soft starter.'
      : '');

    verdictFor(r);
    paintCompare(r);
    paintFinance(r);
    worked(r);
    paintPsh(r);
  }

  function paint() {
    lastResult = compute(readCase());
    paintResult(lastResult);
  }

  function verdict(title, body, cls) {
    var box = el('verdict');
    box.className = 'verdict' + (cls ? ' ' + cls : '');
    el('verdict-title').textContent = title;
    el('verdict-body').textContent = body;
  }

  function verdictFor(r) {
    var cls, title, body;
    var system = systemClass(r);

    if (r.daily <= 3 && r.capex < 1300000) {
      cls = 'ok';
      title = 'Class: mini inverter (600–1000 VA) + 2 tubular batteries';
      body = system.reason + ' Two 12 V 200 Ah tubulars in series give you about 2.4 kWh usable at 24 V — the honest floor for a night of fridge and lights, and the only class that fits under ₦1.3m.';
    } else if (r.daily <= 12) {
      cls = '';
      title = 'Class: 3.5 kVA hybrid inverter + 4.8–5 kWh lithium (or 48 V tubular string)';
      body = system.reason;
    } else {
      cls = '';
      title = 'Class: 5 kVA hybrid + 10 kWh lithium, split across two MPPT inputs';
      body = system.reason;
    }

    if (r.c.budget > 0 && r.capex > r.c.budget * 1.05) {
      cls = 'warn';
      title += ' — over your budget band';
      body = 'The ' + kit.money(r.capex) + ' system is ' + kit.money(r.capex - r.c.budget) +
        ' above your stated budget. Cut the battery bank or the days of autonomy before you touch the array; shrinking panels is what makes a system useless in the dry season. Or price the PAYG column.';
    } else if (r.c.budget > r.capex * 1.6 && r.capex > 0) {
      cls = 'warn';
      body = system.reason + ' Your budget is well above this class — spend the gap on a second day of autonomy or on the array, not on a bigger inverter.';
    }
    if (r.pvLimited) {
      cls = cls || 'warn';
      body += ' Note: a ' + r.kva + ' kVA unit caps PV input near ' + kit.amount(r.kva * r.a.pvRatio, 2) +
        ' kW, but the array is ' + kit.amount(r.arrayActual, 2) + ' kW. Ask for a larger hybrid or accept clipped midday sun.';
    }
    if (r.c.nightHours > 0 && r.nightKwh <= 0) {
      cls = 'bad';
      title = 'Nothing is marked night-critical';
      body = 'The bank came out at zero because no row is ticked for the night. If the fridge, a fan and the router must run after dark, tick them — that is the whole difference between a solar system and a daytime toy.';
    }
    verdict(title, body, cls);
  }

  function systemClass(r) {
    var reason = 'Your day is ' + kit.amount(r.daily, 1) + ' kWh, which needs ' + kit.amount(r.arrayActual, 2) +
      ' kW of panels (' + r.panelCount + ' × ' + r.a.panelW + ' W) at ' + kit.amount(r.c.psh, 1) +
      ' peak sun hours, and ' + kit.amount(r.battKwh, 2) + ' kWh of storage for the ' +
      kit.amount(r.nightKwh, 2) + ' kWh you draw at night.';
    return { name: r.kva + ' kVA / ' + kit.amount(r.battKwh, 1) + ' kWh', reason: reason };
  }

  function paintCompare(r) {
    var spanYears = r.span / 12;
    el('curve-note').textContent = 'Cumulative cost over ' + kit.amount(spanYears, 1) +
      ' years. Generator: ' + kit.money(r.genDaily) + ' a day, every day. Solar: ' + kit.money(r.capex) +
      ' up front, then ' + kit.money(r.a.sunMaint) + ' a year of upkeep' +
      (r.life && r.span / 12 >= r.life ? ' and a battery change at year ' + r.life : ' and no battery change inside the horizon') + '.';

    kit.sparkline(el('gen-spark'), r.points.map(function (p) { return p.gen; }), { dot: 'var(--danger)' });
    kit.sparkline(el('sun-spark'), r.points.map(function (p) { return p.sun; }), { dot: 'var(--brand)' });

    var horizons = [1, 3, 5, 7];
    el('curve-body').innerHTML = horizons.map(function (y) {
      var m = y * 12;
      var g = r.generatorAt(m), s = r.solarAt(m);
      var ahead = g - s;
      return '<tr' + (y === Math.round(r.c.horizon) ? ' class="is-city"' : '') + '><td>' + y + ' year' + (y > 1 ? 's' : '') +
        (y === Math.round(r.c.horizon) ? ' · selected' : '') + '</td>' +
        '<td class="num">' + kit.money(g) + '</td>' +
        '<td class="num">' + kit.money(s) + '</td>' +
        '<td class="num" style="color:' + (ahead >= 0 ? 'var(--ok)' : 'var(--danger)') + '">' +
        (ahead >= 0 ? 'solar by ' : 'generator by ') + kit.money(Math.abs(ahead)) + '</td></tr>';
    }).join('');

    var share = kit.clamp(r.genFuelShare, 0, 100);
    kit.donut(el('cost-donut'), share, { label: kit.pct(share, 0) + ' of generator spend is fuel', color: 'var(--accent)' });
    el('donut-label').textContent = kit.pct(share, 0);
    el('donut-note').textContent = 'Over ' + r.c.horizon + ' years, ' + kit.money(r.genYear * r.c.horizon) +
      ' of the ' + kit.money(r.genTotal) + ' generator bill is fuel and servicing. The machine itself is ' + kit.money(r.c.genPrice) +
      '. Buying the fuel is what makes the solar cheap.';

    kit.bars(el('capex-bars'), [
      { label: 'Panels', value: r.lineItems.panels, display: kit.money(r.lineItems.panels) },
      { label: 'Batteries', value: r.lineItems.battery, display: kit.money(r.lineItems.battery) },
      { label: 'Hybrid + labour', value: r.lineItems.hybrid, display: kit.money(r.lineItems.hybrid) },
      { label: 'Inverter', value: r.lineItems.inverter, display: kit.money(r.lineItems.inverter) },
      { label: 'Mounting + protection', value: r.lineItems.mounting, display: kit.money(r.lineItems.mounting) }
    ]);
  }

  function paintFinance(r) {
    var a = r.a, c = r.c, P = r.capex;
    var tiles = [];
    var headline = '', foot = '';

    if (c.mode === 'payg-daily' || c.mode === 'payg-weekly') {
      var perYear = c.mode === 'payg-daily' ? 365 : 52;
      var unit = c.mode === 'payg-daily' ? 'day' : 'week';
      var pay = annuity(P, c.paygRate, perYear, c.paygYears);
      var total = pay * Math.round(perYear * c.paygYears);
      var genEquivalent = c.mode === 'payg-daily' ? r.genDaily : r.genMonth * 12 / 52;
      headline = kit.money(pay) + ' a ' + unit;
      tiles.push({ head: true, label: 'PAYG instalment', value: kit.money(pay), sub: 'over ' + kit.amount(c.paygYears, 1) + ' years at ' + kit.pct(c.paygRate, 1) + ' a year' });
      tiles.push({ label: 'Total paid', value: kit.money(total), sub: kit.pct(total / P * 100 - 100, 1) + ' more than the cash price' });
      tiles.push({ label: 'Your generator costs', value: kit.money(genEquivalent), sub: 'per ' + unit + ' in fuel and servicing' });
      tiles.push({
        label: 'Headroom', value: (genEquivalent - pay >= 0 ? kit.money(genEquivalent - pay) : '−' + kit.money(pay - genEquivalent)),
        sub: genEquivalent - pay >= 0 ? 'left over each ' + unit + ' versus running the generator' : 'more than the generator costs per ' + unit
      });
      foot = 'Switching to PAYG is usually affordable on day one — ' + kit.money(pay) + ' a ' + unit +
        ' against ' + kit.money(genEquivalent) + ' of fuel — but you pay ' + kit.money(total - P) +
        ' more in total than buying cash. It is a cash-flow decision, not a discount.';
    } else if (c.mode === 'loan') {
      var down = P * a.downPct / 100;
      var balance = P - down;
      var monthly = annuity(balance, a.loanRate, 12, a.loanMonths / 12);
      var allIn = down + monthly * a.loanMonths;
      headline = kit.money(monthly) + ' a month';
      tiles.push({ head: true, label: 'Monthly instalment', value: kit.money(monthly), sub: a.loanMonths + ' months at ' + kit.pct(a.loanRate, 1) + ' a year on the balance' });
      tiles.push({ label: 'Down payment (' + kit.pct(a.downPct, 0) + ')', value: kit.money(down), sub: 'must be raised before installation' });
      tiles.push({ label: 'Total paid', value: kit.money(allIn), sub: kit.pct(allIn / P * 100 - 100, 1) + ' above the cash price' });
      tiles.push({ label: 'Vs generator per month', value: kit.money(r.genMonth), sub: 'the fuel bill this loan replaces' });
      foot = 'You need ' + kit.money(down) + ' in hand. From month one the instalment is ' +
        (monthly < r.genMonth ? 'below' : 'above') + ' your generator spend of ' + kit.money(r.genMonth) + ' a month — after month ' +
        a.loanMonths + ' it is pure saving.';
    } else {
      headline = kit.money(P);
      tiles.push({ head: true, label: 'Cash price', value: kit.money(P), sub: 'the figure the sizing above produced' });
      tiles.push({ label: 'Over ' + kit.amount(c.horizon, 0) + ' years', value: kit.money(r.solarTotal), sub: 'price plus ' + kit.money(a.sunMaint) + ' a year of upkeep and battery changes' });
      tiles.push({ label: 'Generator over the same span', value: kit.money(r.genTotal), sub: 'purchase, fuel and servicing' });
      tiles.push({ label: 'Cash advantage', value: kit.money(Math.max(0, r.difference)), sub: r.crossover ? 'crossover in month ' + r.crossover : 'no crossover inside 15 years' });
      foot = 'Cash is the cheapest option — PAYG and instalments only win on affordability, never on total. If you can raise ' +
        kit.money(P * a.downPct / 100) + ', the loan mode below spreads the rest.';
    }

    var out = el('fin-out');
    out.innerHTML = '';
    tiles.forEach(function (t) {
      var box = document.createElement('div');
      if (t.head) box.className = 'fin-head';
      var s = document.createElement('span');
      s.textContent = t.label;
      var b = document.createElement('b');
      b.textContent = t.value;
      var p = document.createElement('span');
      p.style.textTransform = 'none';
      p.style.letterSpacing = '0';
      p.style.marginTop = '6px';
      p.textContent = t.sub;
      box.appendChild(s);
      box.appendChild(b);
      box.appendChild(p);
      out.appendChild(box);
    });
    var para = document.createElement('p');
    para.textContent = foot;
    out.appendChild(para);
    out.setAttribute('data-headline', headline);
  }

  /* ---------- worked example ---------- */

  function worked(r) {
    var a = r.a, c = r.c;
    var perRow = r.load.filter(function (row) { return wh(row) > 0; }).map(function (row) {
      return escapeText(row.name) + ' ' + row.w + ' W × ' + row.q + ' × ' + row.h + ' h = <b>' + kit.amount(wh(row)) + ' Wh</b>';
    });
    var nightRows = r.load.filter(function (row) { return row.night && watts(row) > 0; }).map(function (row) {
      return escapeText(row.name) + ' ' + kit.amount(watts(row)) + ' W × ' + kit.amount(Math.min(row.h, c.nightHours), 1) + ' h';
    });
    var steps = [
      perRow.length
        ? 'Energy, item by item: ' + perRow.join('; ') + '. Total <b>' + kit.amount(r.dailyRaw * 1000) + ' Wh ≈ ' + kit.amount(r.dailyRaw, 2) + ' kWh</b> a day before losses.'
        : 'No appliances listed yet, so there is no energy to size.',
      r.dailyRaw
        ? 'Inverter and wiring losses: ' + kit.amount(r.dailyRaw, 2) + ' × (1 + ' + kit.pct(a.lossFactor, 0) + ') = <b>' + kit.amount(r.daily, 2) + ' kWh</b> a day that the panels must actually deliver.'
        : 'Add the 15% loss factor once you have a load.',
      r.daily > 0
        ? 'Array: ' + kit.amount(r.daily, 2) + ' ÷ ' + kit.amount(c.psh, 1) + ' peak sun hours = <b>' + kit.amount(r.idealKw, 2) + ' kW</b> ideal, then ÷ ' + (a.sysEff / 100) + ' system efficiency = <b>' + kit.amount(r.arrayKw, 2) + ' kW</b> installed. Rounded up to whole panels: <b>' + r.panelCount + ' × ' + a.panelW + ' W = ' + kit.amount(r.arrayActual, 2) + ' kW</b>.'
        : 'Array sizing waits for a daily figure.',
      'Peak load = every appliance on at once = <b>' + kit.amount(r.peak) + ' W</b>. × ' + (a.oversize / 100) + ' oversize = ' + kit.amount(r.peak * a.oversize / 100) + ' VA, which lands on a <b>' + r.kvaByLoad + ' kVA</b> rating. The ' + kit.amount(r.arrayActual, 2) + ' kW array wants at least <b>' + r.kvaByPv + ' kVA</b> of hybrid (PV capped near ' + a.pvRatio + '× rating), so this system is quoted at <b>' + r.kva + ' kVA</b>.' + (r.motorW ? ' Motor inrush is ' + kit.amount(r.inrush) + ' W at switch-on — headroom, not a rounding error.' : ''),
      nightRows.length
        ? 'Night-critical load over ' + kit.amount(c.nightHours, 1) + ' h: ' + nightRows.join('; ') + ' = <b>' + kit.amount(r.nightRaw, 2) + ' kWh</b>, and <b>' + kit.amount(r.nightKwh, 2) + ' kWh</b> with losses.'
        : 'Nothing is ticked as night-critical, so the bank sizes to zero.',
      'Battery: ' + kit.amount(r.nightKwh, 2) + ' kWh × ' + kit.amount(c.autonomy, 2) + ' day autonomy ÷ ' + kit.pct(r.dod * 100, 0) + ' depth of discharge = <b>' + kit.amount(r.battKwh, 2) + ' kWh</b> of bank = <b>' + kit.amount(r.ah) + ' Ah at ' + c.volts + ' V</b>' + (r.ah > a.stringLimit ? ' — beyond the ' + a.stringLimit + ' Ah single-string limit, so split or raise the voltage' : '') + '.',
      'Price: panels ' + r.panelCount + ' × ' + kit.money(a.panelPrice) + ' = <b>' + kit.money(r.lineItems.panels) + '</b>; inverter ' + r.kva + ' kVA × ' + kit.money(a.inverterPrice) + ' = <b>' + kit.money(r.lineItems.inverter) + '</b>; ' + (c.chemistry === 'lithium' ? 'lithium' : 'tubular') + ' ' + kit.amount(r.battKwh, 2) + ' kWh × ' + kit.money(c.chemistry === 'lithium' ? a.lithiumPrice : a.tubularPrice) + ' = <b>' + kit.money(r.lineItems.battery) + '</b>; hybrid + labour ' + kit.money(a.hybridInstall) + '; mounting, cabling and protection ' + kit.money(a.mounting) + '. Total <b>' + kit.money(r.capex) + '</b>.' + (r.battKwh * (c.chemistry === 'lithium' ? a.lithiumPrice : a.tubularPrice) > r.lineItems.panels ? ' The bank is the biggest line — which is why the night tick matters so much.' : ''),
      'Generator: ' + kit.amount(c.genLh, 2) + ' L/h × ' + kit.amount(c.genHours, 2) + ' h = <b>' + kit.amount(r.litresDay, 2) + ' L</b> a day × ' + kit.money(c.fuelPrice) + ' = <b>' + kit.money(r.fuelDay) + '</b> of fuel, plus ' + kit.money(r.fuelDay ? r.genDaily - r.fuelDay : 0) + ' servicing = <b>' + kit.money(r.genDaily) + '</b> a day, ' + kit.money(r.genMonth) + ' a month, ' + kit.money(r.genYear) + ' a year.',
      'Over ' + kit.amount(c.horizon, 0) + ' years: generator <b>' + kit.money(r.genTotal) + '</b> versus solar <b>' + kit.money(r.solarTotal) + '</b> (' + kit.money(r.perYear) + ' a year) — a difference of <b>' + kit.money(Math.abs(r.difference)) + '</b>.' + (r.crossover ? ' The lines cross in <b>month ' + r.crossover + '</b>: before it the generator has cost less, after it the generator never catches up.' : ' The lines do not cross inside 15 years at these inputs.'),
      'At the defaults above the Ibadan family day is 8,633 Wh before losses — <b>8.6 kWh</b>, <b>9.93 kWh</b> after the 15% factor, needing <b>2.55 kW</b> of array at 5.2 peak sun hours and <b>3.65 kWh</b> of lithium for one night. Match your own numbers against that before you believe any quote.'
    ];
    el('worked').innerHTML = steps.map(function (s) { return '<li>' + s + '</li>'; }).join('');
  }

  /* ---------- load builder ---------- */

  function paintLoadRows() {
    var body = el('load-body');
    body.innerHTML = load.map(function (row, i) {
      var nm = escapeAttr(row.name);
      return '<tr data-i="' + i + '">' +
        '<td class="col-name"><input value="' + nm + '" data-f="name" maxlength="34" aria-label="Appliance ' + (i + 1) + ' name"></td>' +
        '<td class="w-cell"><input type="number" min="0" max="20000" step="5" value="' + row.w + '" data-f="w" inputmode="numeric" aria-label="Watts for ' + nm + '"></td>' +
        '<td class="q-cell"><input type="number" min="0" max="99" step="1" value="' + row.q + '" data-f="q" inputmode="numeric" aria-label="Quantity of ' + nm + '"></td>' +
        '<td class="h-cell"><input type="number" min="0" max="24" step="0.25" value="' + row.h + '" data-f="h" inputmode="decimal" aria-label="Hours per day for ' + nm + '"></td>' +
        '<td class="check"><input type="checkbox" data-f="night"' + (row.night ? ' checked' : '') + ' aria-label="' + nm + ' must run at night"></td>' +
        '<td class="check"><input type="checkbox" data-f="motor"' + (row.motor ? ' checked' : '') + ' aria-label="' + nm + ' is a motor load"></td>' +
        '<td class="num load-wh" data-wh>' + kit.amount(wh(row)) + '</td>' +
        '<td><button class="load-del" type="button" data-del="' + i + '" aria-label="Remove ' + nm + '">×</button></td>' +
        '</tr>';
    }).join('');
  }

  function paintPresets() {
    el('quick-add').innerHTML = PRESETS.map(function (p, i) {
      return '<button class="pill" type="button" data-preset="' + i + '">' + escapeText(p.name) +
        ' <span class="mono tiny">' + p.w + ' W</span></button>';
    }).join('');
  }

  function bindLoad() {
    var body = el('load-body');
    kit.on(el('quick-add'), 'click', '[data-preset]', function (e, btn) {
      var p = PRESETS[Number(btn.dataset.preset)];
      var existing = load.filter(function (row) { return row.name === p.name; })[0];
      if (existing) {
        existing.q = kit.clamp(existing.q + p.q, 0, 99);
      } else {
        load.push(sanitiseRow(p));
      }
      loadStore.write(load);
      paintLoadRows();
      paint();
      kit.toast(existing ? p.name + ' quantity increased to ' + existing.q : p.name + ' added to the load');
    });

    kit.on(body, 'input', 'input', function (e, input) {
      var rowEl = input.closest('tr');
      var i = Number(rowEl.dataset.i);
      var field = input.dataset.f;
      if (!load[i] || !field) return;
      if (field === 'name') load[i].name = input.value.slice(0, 34);
      else if (field === 'night' || field === 'motor') load[i][field] = input.checked;
      else load[i][field] = kit.num(input.value);
      var cell = rowEl.querySelector('[data-wh]');
      if (cell) cell.textContent = kit.amount(wh(load[i]));
      loadStore.write(load);
      paint();
    });

    kit.on(body, 'click', '[data-del]', function (e, btn) {
      var i = Number(btn.dataset.del);
      if (load.length <= 1) { kit.toast('Keep at least one row — set its watts to zero instead'); return; }
      load.splice(i, 1);
      loadStore.write(load);
      paintLoadRows();
      paint();
    });

    el('add-row').addEventListener('click', function () {
      load.push({ name: 'New appliance', w: 100, q: 1, h: 4, night: false, motor: false });
      loadStore.write(load);
      paintLoadRows();
      paint();
      var rows = el('load-body').querySelectorAll('tr');
      var last = rows[rows.length - 1];
      if (last) last.querySelector('input').focus();
    });

    el('reset-load').addEventListener('click', function () {
      load = STARTER.map(clone).map(sanitiseRow);
      loadStore.write(load);
      paintLoadRows();
      paint();
      kit.toast('Starter Ibadan family profile restored');
    });
  }

  /* ---------- assumptions ---------- */

  function paintAssumptions() {
    Object.keys(SPEC_GROUPS).forEach(function (gridId) {
      var host = el(gridId);
      if (!host) return;
      host.innerHTML = SPEC_GROUPS[gridId].map(function (spec) {
        var value = assumptions[spec.k];
        return '<label>' + escapeText(spec.label) + ' <span class="tiny">(' + escapeText(spec.unit) + ')</span>' +
          '<input type="number" min="' + spec.min + '" max="' + spec.max + '" step="' + spec.step + '" value="' + value +
          '" data-key="' + spec.k + '" aria-label="' + escapeAttr(spec.label + ' in ' + spec.unit) + '">' +
          (spec.help ? '<span class="help">' + escapeText(spec.help) + '</span>' : '') + '</label>';
      }).join('');
    });
  }

  function bindAssumptions() {
    kit.on(el('rules'), 'input', '[data-key]', function (e, input) {
      var key = input.dataset.key;
      var value = kit.num(input.value);
      if (!(value >= 0) && value !== 0) return;
      assumptions[key] = value;
      assStore.write(assumptions);
      if (key === 'petrol' || key === 'diesel') {
        var select = el('fuel-type');
        if (select.value === key) el('fuel-price').value = value;
      }
      paint();
    });

    el('reset-assumptions').addEventListener('click', function () {
      assumptions = normaliseAssumptions(null);
      pshTable = readPsh();
      assStore.write(assumptions);
      pshStore.clear();
      paintAssumptions();
      paintPshTable();
      el('sun-hours').value = pshTable[el('city').value];
      paint();
      kit.toast('Default assumptions restored');
    });
  }

  function paintPshTable() {
    var host = el('psh-body');
    if (!host) return;
    var daily = lastResult ? lastResult.daily : 0;
    host.innerHTML = CITIES.map(function (c) {
      var value = pshTable[c.name];
      var need = value > 0 && assumptions.sysEff > 0 ? daily / value / (assumptions.sysEff / 100) : 0;
      return '<tr' + (el('city') && el('city').value === c.name ? ' class="is-city"' : '') + ' data-city="' + escapeAttr(c.name) + '">' +
        '<td>' + escapeText(c.name) + '</td>' +
        '<td class="num"><input type="number" min="2" max="9" step="0.1" value="' + value + '" data-psh="' + escapeAttr(c.name) + '" aria-label="Peak sun hours for ' + escapeAttr(c.name) + '"></td>' +
        '<td class="num">' + (daily > 0 ? kit.amount(need, 2) + ' kW' : '—') + '</td></tr>';
    }).join('');
  }

  function paintPsh(r) {
    if (!r) return;
    var host = el('psh-body');
    if (!host) return;
    kit.$$('tr', host).forEach(function (row) {
      var need = row.dataset.city && r.c.psh > 0 ? r.daily / pshTable[row.dataset.city] / (r.a.sysEff / 100) : 0;
      var cells = row.children;
      if (cells[2]) cells[2].textContent = r.daily > 0 ? kit.amount(need, 2) + ' kW' : '—';
      row.classList.toggle('is-city', !!el('city') && el('city').value === row.dataset.city);
    });
  }

  function bindPsh() {
    kit.on(el('psh-body'), 'input', '[data-psh]', function (e, input) {
      var name = input.dataset.psh;
      var value = kit.num(input.value);
      if (!(value > 0)) return;
      pshTable[name] = value;
      pshStore.write(pshTable);
      if (el('city').value === name) {
        el('sun-hours').value = value;
        cityNote(name, false);
      }
      paint();
    });
  }

  function cityNote(name, isCustom) {
    var node = el('city-note');
    if (!node) return;
    node.textContent = isCustom
      ? 'Custom value — not a published city average. Use it only if you have your own irradiation data.'
      : 'Annual average peak sun hours for ' + name + '. Averages hide the wet season; confirm with a local installer or a one-week data logger.';
  }

  /* ---------- quote ledger ---------- */

  /* store() hands back its fallback array by reference, so copy it: otherwise a
     pushed quote would survive a "clear" for the rest of the session. */
  function quotes() {
    var list = quoteStore.read();
    return Array.isArray(list) ? list.slice() : [];
  }

  function saveQuote() {
    if (!lastResult || lastResult.empty) { kit.toast('Size a load before saving a quote'); el('add-row').focus(); return; }
    var r = lastResult;
    var list = quotes();
    var label = r.c.label || (r.c.city + ' system');
    list.push({
      id: kit.uid('quote'),
      date: Date.now(),
      label: label,
      city: r.c.city,
      psh: r.c.psh,
      daily: round(r.daily),
      arrayKw: round(r.arrayActual),
      panels: r.panelCount,
      kva: r.kva,
      battKwh: round(r.battKwh),
      ah: Math.round(r.ah),
      volts: r.c.volts,
      chem: r.c.chemistry,
      capex: Math.round(r.capex),
      perYear: Math.round(r.perYear),
      genMonth: Math.round(r.genMonth),
      horizon: r.c.horizon,
      crossover: r.crossover
    });
    if (!quoteStore.write(list)) { kit.toast('This browser blocked saving — export the CSV instead'); return; }
    paintQuotes(list[list.length - 1].id);
    kit.toast('Quote saved to the ledger');
  }

  function paintQuotes(newId) {
    var list = quotes();
    var body = el('quote-body');
    var foot = el('quote-foot');
    el('quote-count').textContent = list.length + (list.length === 1 ? ' quote' : ' quotes') +
      (list.length ? ' · ' + kit.money(list.reduce(function (a, b) { return a + b.capex; }, 0)) + ' of systems' : '');
    el('quote-empty').hidden = list.length > 0;
    paintSpec(list);

    if (!list.length) { body.innerHTML = ''; foot.innerHTML = ''; return; }

    body.innerHTML = list.map(function (q) {
      return '<tr' + (q.id === newId ? ' class="is-new"' : '') + ' data-quote="' + q.id + '">' +
        '<td>' + escapeText(q.label) + '</td>' +
        '<td class="num">' + kit.dateLabel(q.date) + '</td>' +
        '<td>' + escapeText(q.city) + '</td>' +
        '<td class="num">' + kit.amount(q.daily, 1) + '</td>' +
        '<td class="num">' + kit.amount(q.arrayKw, 2) + ' kW</td>' +
        '<td class="num">' + q.kva + ' kVA</td>' +
        '<td class="num">' + kit.amount(q.battKwh, 1) + ' kWh</td>' +
        '<td class="num"><b>' + kit.money(q.capex) + '</b></td>' +
        '<td class="num">' + kit.money(q.genMonth) + '</td>' +
        '<td class="num">' + (q.crossover ? 'M' + q.crossover : '—') + '</td>' +
        '<td><button class="row-del" type="button" data-del="' + q.id + '" aria-label="Remove quote ' + escapeAttr(q.label) + '">×</button></td>' +
        '</tr>';
    }).join('');

    var totalCapex = list.reduce(function (a, b) { return a + b.capex; }, 0);
    var totalFuel = list.reduce(function (a, b) { return a + b.genMonth; }, 0);
    var totalPerYear = list.reduce(function (a, b) { return a + (b.perYear || 0); }, 0);
    var avgCross = list.filter(function (q) { return q.crossover; });
    var avg = avgCross.length ? Math.round(avgCross.reduce(function (a, b) { return a + b.crossover; }, 0) / avgCross.length) : 0;
    foot.innerHTML = '<tr><td>Total / average</td><td class="num">' + list.length + '</td><td>' +
      (list.length > 1 ? avg + ' months avg' : '—') + '</td><td class="num">' +
      kit.amount(list.reduce(function (a, b) { return a + b.daily; }, 0), 1) + '</td><td class="num">' +
      kit.amount(list.reduce(function (a, b) { return a + b.arrayKw; }, 0), 2) + ' kW</td><td class="num">' +
      list.reduce(function (a, b) { return a + b.kva; }, 0) + ' kVA</td><td class="num">' +
      kit.amount(list.reduce(function (a, b) { return a + b.battKwh; }, 0), 1) + ' kWh</td><td class="num">' +
      kit.money(totalCapex) + '</td><td class="num">' + kit.money(totalFuel) + '</td><td class="num">' +
      kit.money(totalFuel * 12 - totalPerYear) + ' a year</td><td></td></tr>';
  }

  function paintSpec(list) {
    var dl = el('spec-list');
    var meta = el('spec-meta');
    if (!dl || !meta) return;
    if (!list || !list.length) {
      meta.textContent = 'Nothing saved yet — size a system and press Save this system.';
      dl.innerHTML = '';
      return;
    }
    var rows = list.map(function (q, i) {
      return {
        k: (list.length > 1 ? '#' + (i + 1) + ' ' : '') + q.label,
        v: q.city + ' · ' + kit.amount(q.daily, 1) + ' kWh/day · array ' + kit.amount(q.arrayKw, 2) + ' kW (' + q.panels + ' × ' +
          assumptions.panelW + ' W) · inverter ' + q.kva + ' kVA · bank ' + kit.amount(q.battKwh, 1) + ' kWh = ' + q.ah + ' Ah at ' + q.volts + ' V ' + q.chem +
          ' · price ' + kit.money(q.capex) + ' (' + kit.money(q.perYear) + ' a year over ' + q.horizon + ' yrs) · generator ' +
          kit.money(q.genMonth) + ' a month · crossover month ' + (q.crossover || 'n/a') + ' · dated ' + kit.dateLabel(q.date)
      };
    });
    meta.textContent = list.length + (list.length === 1 ? ' system' : ' systems') + ' in this ledger · prepared ' + kit.dateLabel(Date.now()) + ' · indicative only';
    dl.innerHTML = rows.map(function (row) {
      return '<dt>' + escapeText(row.k) + '</dt><dd>' + escapeText(row.v) + '</dd>';
    }).join('');
  }

  function bindQuotes() {
    kit.on(el('quote-body'), 'click', '[data-del]', function (e, btn) {
      var list = quotes().filter(function (q) { return q.id !== btn.dataset.del; });
      quoteStore.write(list);
      paintQuotes();
      kit.toast('Quote removed');
    });
    el('save-quote').addEventListener('click', saveQuote);
    el('clear-quotes').addEventListener('click', function () {
      if (!quotes().length) { kit.toast('The ledger is already empty'); return; }
      if (!window.confirm('Remove every saved quote from this browser?')) return;
      quoteStore.clear();
      paintQuotes();
      kit.toast('Ledger cleared');
    });
    el('export-csv').addEventListener('click', function () {
      var list = quotes();
      if (!list.length) { kit.toast('Save a quote first'); return; }
      kit.csv([
        ['Quote', 'Date', 'City', 'Peak sun hours', 'Daily kWh', 'Array kW', 'Panels', 'Inverter kVA', 'Battery kWh', 'Battery Ah', 'Bank V', 'Chemistry', 'System price (NGN)', 'Cost per year (NGN)', 'Generator per month (NGN)', 'Horizon years', 'Crossover month']
      ].concat(list.map(function (q) {
        return [q.label, kit.dateLabel(q.date), q.city, round(q.psh), q.daily, q.arrayKw, q.panels, q.kva, q.battKwh, q.ah, q.volts, q.chem, q.capex, q.perYear, q.genMonth, q.horizon, q.crossover || 'none'];
      })).concat([[
        'TOTAL', '', '', '', round(list.reduce(function (a, b) { return a + b.daily; }, 0)), '', '', '', '', '', '', '',
        list.reduce(function (a, b) { return a + b.capex; }, 0), '', list.reduce(function (a, b) { return a + b.genMonth; }, 0), '', ''
      ]]), 'suncost-quotes-' + new Date().toISOString().slice(0, 10) + '.csv');
    });
    el('print-spec').addEventListener('click', function () {
      if (!quotes().length) { kit.toast('Save a quote to print its spec sheet'); return; }
      kit.print();
    });
  }

  /* ---------- boot ---------- */

  function fillCities() {
    var select = el('city');
    select.innerHTML = CITIES.map(function (c) {
      return '<option value="' + escapeAttr(c.name) + '">' + escapeText(c.name) + '</option>';
    }).join('');
    select.value = 'Ibadan';
  }

  function bindCases() {
    var select = el('city');
    select.addEventListener('change', function () {
      el('sun-hours').value = pshTable[select.value];
      cityNote(select.value, false);
      paint();
    });
    el('sun-hours').addEventListener('input', function () { cityNote(select.value, true); });
    el('fuel-type').addEventListener('change', function () {
      el('fuel-price').value = assumptions[this.value === 'petrol' ? 'petrol' : 'diesel'];
      paint();
    });
  }

  kit.boot({
    ready: function () {
      fillCities();
      el('sun-hours').value = pshTable[el('city').value];
      cityNote(el('city').value, false);
      paintPresets();
      paintLoadRows();
      bindLoad();
      paintAssumptions();
      bindAssumptions();
      bindPsh();
      bindQuotes();
      bindCases();
      paintPshTable();

      var form = el('size-form');
      kit.$$('#size-form input, #size-form select, #compare input, #compare select').forEach(function (node) {
        node.addEventListener('input', paint);
        node.addEventListener('change', paint);
      });

      paint();
      paintQuotes();
    }
  });
})();
