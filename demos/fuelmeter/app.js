/* FuelMeter — cost-per-kilometre engine for Nigerian drivers and riders.
   Every figure is computed in the browser from editable reference tables.
   There is no live feed here and no pretending: pump prices are reference
   values you update yourself, with an "as of" date you set. */
(function () {
  'use strict';

  /* ---------------------------------------------------------------- tables */

  var DEFAULT_PROFILES = [
    { id: 'okada', name: 'Dispatch motorcycle (100–200cc)', fuel: 'petrol', kmL: 48, tank: 4, seats: 1, service: 22, tyrePrice: 30000, tyreKm: 15000, oilCost: 4000, oilKm: 2500, fixedMonth: 8000, range: '40–55' },
    { id: 'keke', name: 'Keke NLP tricycle', fuel: 'petrol', kmL: 21, tank: 18, seats: 3, service: 38, tyrePrice: 48000, tyreKm: 12000, oilCost: 4500, oilKm: 2000, fixedMonth: 16000, range: '18–25' },
    { id: 'aygo', name: 'Aygo / Kia Picanto', fuel: 'petrol', kmL: 18, tank: 35, seats: 4, service: 13, tyrePrice: 95000, tyreKm: 40000, oilCost: 14000, oilKm: 5000, fixedMonth: 18000, range: '16–20' },
    { id: 'corolla', name: 'Toyota Corolla', fuel: 'petrol', kmL: 12, tank: 50, seats: 4, service: 18, tyrePrice: 150000, tyreKm: 42000, oilCost: 20000, oilKm: 5000, fixedMonth: 30000, range: '11–13' },
    { id: 'camry', name: 'Toyota Camry', fuel: 'petrol', kmL: 10, tank: 60, seats: 4, service: 26, tyrePrice: 200000, tyreKm: 40000, oilCost: 26000, oilKm: 5000, fixedMonth: 38000, range: '9–11' },
    { id: 'sienna', name: 'Sienna / Hiace', fuel: 'petrol', kmL: 8, tank: 70, seats: 6, service: 34, tyrePrice: 280000, tyreKm: 38000, oilCost: 30000, oilKm: 5000, fixedMonth: 55000, range: '7–9' },
    { id: 'pickup', name: 'Pickup (Hilux / Dyna)', fuel: 'diesel', kmL: 8, tank: 80, seats: 3, service: 40, tyrePrice: 340000, tyreKm: 45000, oilCost: 45000, oilKm: 7000, fixedMonth: 70000, range: '7–9' },
    { id: 'truck', name: '3–5 ton truck', fuel: 'diesel', kmL: 5, tank: 100, seats: 2, service: 70, tyrePrice: 900000, tyreKm: 50000, oilCost: 80000, oilKm: 8000, fixedMonth: 160000, range: '4–6' },
    { id: 'bus', name: 'Bus / coaster (33 seats)', fuel: 'diesel', kmL: 4, tank: 120, seats: 33, service: 95, tyrePrice: 1400000, tyreKm: 45000, oilCost: 140000, oilKm: 10000, fixedMonth: 280000, range: '3–5' }
  ];

  var DEFAULT_PRICES = [
    { id: 'lagos', name: 'Lagos', petrol: 1610, diesel: 1330 },
    { id: 'ogun', name: 'Ogun (Abeokuta / Sango)', petrol: 1640, diesel: 1355 },
    { id: 'oyo', name: 'Oyo (Ibadan)', petrol: 1630, diesel: 1350 },
    { id: 'edo', name: 'Edo (Benin City / Ore)', petrol: 1655, diesel: 1370 },
    { id: 'fct', name: 'Abuja (FCT)', petrol: 1650, diesel: 1360 },
    { id: 'kaduna', name: 'Kaduna', petrol: 1670, diesel: 1375 },
    { id: 'kogi', name: 'Kogi (Lokoja)', petrol: 1665, diesel: 1380 },
    { id: 'anambra', name: 'Anambra (Onitsha)', petrol: 1640, diesel: 1365 },
    { id: 'enugu', name: 'Enugu', petrol: 1635, diesel: 1360 },
    { id: 'rivers', name: 'Rivers (Port Harcourt)', petrol: 1620, diesel: 1340 },
    { id: 'abia', name: 'Abia (Aba)', petrol: 1645, diesel: 1368 },
    { id: 'kano', name: 'Kano', petrol: 1690, diesel: 1395 },
    { id: 'bauchi', name: 'Bauchi', petrol: 1660, diesel: 1378 }
  ];

  var DEFAULT_MODES = [
    { id: 'hail', name: 'Ride-hail, private car (Bolt / Uber)', basis: 'vehicle', min: 2500, perKm: 230, seats: 4, note: 'You buy the whole car, door to door' },
    { id: 'pool', name: 'Ride-share seat (private car doing the corridor)', basis: 'seat', min: 1500, perKm: 38, seats: 4, note: 'One seat sold; slower, detours for pickups' },
    { id: 'bus', name: 'Inter-city bus (VIP / first class)', basis: 'seat', min: 1500, perKm: 28, seats: 1, note: 'Park to park, scheduled departure' },
    { id: 'minibus', name: 'Shared minibus (danfo / 14 seater)', basis: 'seat', min: 1200, perKm: 24, seats: 1, note: 'Leaves when full, luggage by favour' },
    { id: 'keke', name: 'Keke NLP hired whole', basis: 'vehicle', min: 700, perKm: 35, seats: 3, note: 'City and short hops, barred on expressways' },
    { id: 'okada', name: 'Okada', basis: 'seat', min: 800, perKm: 55, seats: 1, note: 'Fastest, highest risk, restricted on many routes' }
  ];

  var DEFAULT_FACTORS = { idle: 0.6, ac: 8, load: 2.5, monthly: 1400, flag: 12, goSlow: 1.25 };

  var TOWNS = [
    { name: 'Lagos', state: 'lagos' }, { name: 'Ibadan', state: 'oyo' }, { name: 'Abeokuta', state: 'ogun' },
    { name: 'Ore', state: 'edo' }, { name: 'Benin City', state: 'edo' }, { name: 'Abuja', state: 'fct' },
    { name: 'Kaduna', state: 'kaduna' }, { name: 'Lokoja', state: 'kogi' }, { name: 'Onitsha', state: 'anambra' },
    { name: 'Enugu', state: 'enugu' }, { name: 'Port Harcourt', state: 'rivers' }, { name: 'Aba', state: 'abia' },
    { name: 'Kano', state: 'kano' }, { name: 'Bauchi', state: 'bauchi' }
  ];

  var ROUTES = [
    ['Lagos', 'Ibadan', 130], ['Lagos', 'Benin City', 245], ['Abuja', 'Kaduna', 170], ['Abuja', 'Lokoja', 240],
    ['Onitsha', 'Enugu', 65], ['Port Harcourt', 'Aba', 60], ['Lagos', 'Abeokuta', 100], ['Kano', 'Bauchi', 180],
    ['Benin City', 'Ore', 130]
  ];

  /* ---------------------------------------------------------------- stores */

  var sProfiles = kit.store('fuelmeter:profiles', null);
  var sPrices = kit.store('fuelmeter:prices', null);
  var sModes = kit.store('fuelmeter:modes', null);
  var sFactors = kit.store('fuelmeter:factors', null);
  var sAsOf = kit.store('fuelmeter:asof', null);
  var sLog = kit.store('fuelmeter:log', []);
  var sFleet = kit.store('fuelmeter:fleet', []);

  function clone(src) { return JSON.parse(JSON.stringify(src)); }
  function isoDaysAgo(n) {
    var d = new Date();
    d.setDate(d.getDate() - n);
    return d.toISOString().slice(0, 10);
  }

  var P = sProfiles.read() || clone(DEFAULT_PROFILES);
  var PR = sPrices.read() || clone(DEFAULT_PRICES);
  var M = sModes.read() || clone(DEFAULT_MODES);
  var F = (function () {
    var stored = sFactors.read() || {};
    var out = {};
    Object.keys(DEFAULT_FACTORS).forEach(function (k) {
      var n = Number(stored[k]);
      out[k] = isFinite(n) && n > 0 ? n : DEFAULT_FACTORS[k];
    });
    return out;
  })();

  function asOf() { return sAsOf.read() || isoDaysAgo(2); }
  function logRows() { return sLog.read() || []; }
  function fleetRows() { return sFleet.read() || []; }

  function profileById(id) {
    for (var i = 0; i < P.length; i++) if (P[i].id === id) return P[i];
    return P[0];
  }
  function priceRow(id) {
    for (var i = 0; i < PR.length; i++) if (PR[i].id === id) return PR[i];
    return PR[0];
  }
  function modeById(id) {
    for (var i = 0; i < M.length; i++) if (M[i].id === id) return M[i];
    return null;
  }
  function pumpPrice(profile, stateId) {
    var row = priceRow(stateId || PR[0].id);
    return (profile.fuel === 'diesel' ? row.diesel : row.petrol) || 0;
  }
  function townState(name) {
    for (var i = 0; i < TOWNS.length; i++) if (TOWNS[i].name === name) return TOWNS[i].state;
    return null;
  }

  /* --------------------------------------------------------------- helpers */

  function esc(text) {
    var div = document.createElement('div');
    div.textContent = String(text);
    return div.innerHTML;
  }
  function value(id) { var el = document.getElementById(id); return el ? el.value : ''; }
  function numOf(id, fallback) {
    var el = document.getElementById(id);
    if (!el) return fallback || 0;
    var n = kit.num(el.value);
    return isFinite(n) ? n : (fallback || 0);
  }
  function checked(id) { var el = document.getElementById(id); return !!(el && el.checked); }
  function round(n) { return Math.round((Number(n) || 0) * 100) / 100; }
  function kmText(naira) { return kit.money(naira); }

  /* the full per-kilometre stack for a profile */
  function stack(profile, stateId, monthlyKm) {
    var price = pumpPrice(profile, stateId);
    var kmL = Math.max(0.5, Number(profile.kmL) || 1);
    var tyrePerKm = profile.tyreKm > 0 ? (profile.tyrePrice || 0) / profile.tyreKm : 0;
    var oilPerKm = profile.oilKm > 0 ? (profile.oilCost || 0) / profile.oilKm : 0;
    var useKm = Math.max(1, monthlyKm || F.monthly);
    var fixedPerKm = (profile.fixedMonth || 0) / useKm;
    var wearPerKm = (profile.service || 0) + tyrePerKm + oilPerKm;
    return {
      price: price, kmL: kmL,
      fuelPerKm: price / kmL,
      service: profile.service || 0,
      tyrePerKm: tyrePerKm, oilPerKm: oilPerKm, wearPerKm: wearPerKm,
      fixedPerKm: fixedPerKm,
      totalPerKm: price / kmL + wearPerKm + fixedPerKm
    };
  }

  /* -------------------------------------------------------- trip computing */

  function readTrip() {
    var profile = profileById(value('t-profile'));
    return {
      profile: profile,
      profileId: profile.id,
      stateId: value('t-state'),
      km: kit.clamp(numOf('t-km', 0), 0, 4000),
      traffic: kit.clamp(numOf('t-traffic', 1), 0.5, 3),
      idle: kit.clamp(numOf('t-idle', 0), 0, 24),
      ac: checked('t-ac'),
      loadKg: kit.clamp(numOf('t-load', 0), 0, 20000),
      pax: kit.clamp(Math.round(numOf('t-pax', 0)), 0, 60),
      tank: kit.clamp(numOf('t-fills', profile.tank || 50), 1, 500),
      ret: checked('t-return')
    };
  }

  function trip(t) {
    var s = stack(t.profile, t.stateId, F.monthly);
    var legs = t.ret ? 2 : 1;
    var distance = t.km * legs;
    if (!(distance > 0)) return { empty: true, input: t, stack: s, distance: 0 };

    var acPenalty = t.ac ? 1 + F.ac / 100 : 1;
    var loadPenalty = 1 + (t.loadKg / 100) * (F.load / 100);
    var factor = t.traffic * acPenalty * loadPenalty;
    var effKmL = s.kmL / factor;
    var idleRate = F.idle * (t.profile.fuel === 'diesel' ? 0.8 : 1);
    var idleHours = t.idle * legs;
    var idleLitres = idleHours * idleRate;
    var litres = distance / effKmL + idleLitres;

    var fuel = litres * s.price;
    var wear = distance * s.wearPerKm;
    var fixed = distance * s.fixedPerKm;
    var total = fuel + wear + fixed;
    var occupants = Math.max(1, Math.min(t.pax + 1, t.profile.seats + 1));

    return {
      empty: false, input: t, stack: s, distance: distance, factor: factor, effKmL: effKmL,
      litres: litres, idleLitres: idleLitres, idlingCost: idleLitres * s.price,
      fuel: fuel, wear: wear, fixed: fixed, total: total,
      perKm: total / distance, fuelPerKm: fuel / distance,
      marginalPerKm: (fuel + wear) / distance,
      occupants: occupants, perSeatKm: total / distance / occupants,
      tankFraction: kit.clamp(litres / t.tank, 0, 1), tanksNeeded: litres / t.tank,
      price: s.price
    };
  }

  /* -------------------------------------------------------- figure painting */

  var lastText = {};
  function setFigure(id, text) {
    var el = document.getElementById(id);
    if (!el) return;
    var out = text === null ? '—' : String(text);
    if (el.textContent === out) return;
    el.textContent = out;
    if (!kit.reduce && lastText[id] !== undefined && lastText[id] !== out) {
      el.classList.remove('tick');
      void el.offsetWidth;
      el.classList.add('tick');
    }
    lastText[id] = out;
  }
  function verdict(id, title, body, cls) {
    var box = document.getElementById(id);
    if (!box) return;
    box.className = 'verdict' + (cls ? ' ' + cls : '');
    var ti = document.getElementById(id + '-title');
    var bo = document.getElementById(id + '-body');
    if (ti) ti.textContent = title;
    if (bo) bo.textContent = body;
  }

  /* ------------------------------------------------------- signature gauge */

  function buildTicks() {
    var holder = document.getElementById('g-ticks');
    if (!holder) return;
    var parts = [];
    for (var i = 0; i <= 8; i++) {
      var deg = -90 + i * 22.5;
      var rad = deg * Math.PI / 180;
      var major = i % 2 === 0;
      var r1 = major ? 62 : 68;
      parts.push('<line x1="' + (100 + r1 * Math.sin(rad)).toFixed(1) + '" y1="' + (106 - r1 * Math.cos(rad)).toFixed(1) +
        '" x2="' + (100 + 79 * Math.sin(rad)).toFixed(1) + '" y2="' + (106 - 79 * Math.cos(rad)).toFixed(1) + '"' +
        (major ? ' class="major"' : '') + '/>');
    }
    holder.innerHTML = parts.join('');
  }

  function paintGauge(r) {
    var needle = document.getElementById('g-needle');
    var arc = document.getElementById('g-arc-live');
    if (!needle || !arc) return;

    var fraction = r.empty ? 1 : kit.clamp(1 - r.tankFraction, 0, 1);
    needle.style.setProperty('--needle', (-90 + fraction * 180).toFixed(1) + 'deg');

    var length = 251.3;
    try { if (arc.getTotalLength) length = arc.getTotalLength(); } catch (e) { length = 251.3; }
    arc.style.strokeDasharray = length.toFixed(1);
    arc.style.strokeDashoffset = (length * (1 - fraction)).toFixed(1);
    arc.style.stroke = fraction > 0.5 ? 'var(--accent)' : (fraction > 0.22 ? '#dd9f3b' : 'var(--brand)');

    if (r.empty) {
      setFigure('g-pct', null);
      document.getElementById('g-note').textContent = 'a full tank, waiting for a distance';
      document.getElementById('g-fills').textContent = 'NO DISTANCE ENTERED';
      return;
    }
    setFigure('g-pct', Math.round(fraction * 100) + '%');
    document.getElementById('g-note').textContent = 'of a ' + kit.amount(r.input.tank, 0) +
      ' L tank left on arrival — ' + kit.amount(r.litres, 2) + ' L burned';
    document.getElementById('g-fills').textContent = r.tanksNeeded <= 1
      ? 'ONE TANK COVERS IT · ' + kit.pct(r.tankFraction * 100, 0) + ' OF A TANK'
      : 'NEEDS ' + Math.ceil(r.tanksNeeded) + ' FILL-UPS · ' + kit.amount(r.tanksNeeded, 2) + ' TANKS';
  }

  /* ------------------------------------------------------------ section 01 */

  function paintTrip() {
    var t = readTrip();
    var r = trip(t);

    if (r.empty) {
      ['tr-total', 'tr-litres', 'tr-fuel', 'tr-wear', 'tr-fixed', 'tr-total-2', 'tr-eff', 'tr-perkm-fuel', 'tr-perkm', 'tr-perseat', 'm-fuelshare', 'm-marginal', 'm-tank']
        .forEach(function (id) { setFigure(id, null); });
      document.getElementById('tr-seat-count').textContent = '';
      document.getElementById('tr-total-note').textContent = 'enter a distance to begin';
      verdict('tr-verdict', 'Pick a route', 'Type a distance or choose a known route — litres, fuel, wear and the true per-kilometre cost appear here.', '');
    } else {
      setFigure('tr-total', kit.money(r.total));
      document.getElementById('tr-total-note').textContent =
        kit.amount(r.distance) + ' km · ' + r.input.profile.name + ' · ' + kit.money(r.price) + '/L ' + r.input.profile.fuel;
      setFigure('tr-litres', kit.amount(r.litres, 2) + ' L');
      setFigure('tr-fuel', kit.money(r.fuel));
      setFigure('tr-wear', kit.money(r.wear));
      setFigure('tr-fixed', kit.money(r.fixed));
      setFigure('tr-total-2', kit.money(r.total));
      setFigure('tr-eff', kit.amount(r.effKmL, 1) + ' km/L');
      setFigure('tr-perkm-fuel', kmText(r.fuelPerKm));
      setFigure('tr-perkm', kmText(r.perKm));
      setFigure('tr-perseat', kmText(r.perSeatKm));
      document.getElementById('tr-seat-count').textContent = '· ' + r.occupants + ' in the car';
      setFigure('m-fuelshare', kit.pct(r.fuel / r.total * 100, 1));
      setFigure('m-marginal', kmText(r.marginalPerKm));
      setFigure('m-tank', kit.pct(r.tankFraction * 100, 0) + ' of tank');
      tripVerdict(r);
    }

    paintGauge(r);
    paintBreakdown(r);
    paintHead();
    paintBudget();
    worked();
  }

  function tripVerdict(r) {
    var t = r.input;
    if (r.tanksNeeded > 1) {
      verdict('tr-verdict', 'This trip is more than one tank',
        'You need ' + kit.amount(r.litres, 1) + ' L — ' + kit.amount(r.tanksNeeded, 2) + ' tanks of ' + t.tank +
        ' L. Carry ' + kit.money(Math.ceil(r.tanksNeeded) * t.tank * r.price) + ' of fuel money, not ' + kit.money(r.fuel) + '.', 'warn');
      return;
    }
    if (r.idlingCost > r.fuel * 0.15) {
      verdict('tr-verdict', 'Idling is eating the trip',
        kit.amount(r.idleLitres, 2) + ' L burned standing still = ' + kit.money(r.idlingCost) + ', ' +
        kit.pct(r.idlingCost / r.fuel * 100, 0) + ' of your fuel bill. A different departure time is the cheapest discount on this road.', 'bad');
      return;
    }
    if (r.perKm > 2.2 * r.fuelPerKm) {
      verdict('tr-verdict', 'Wear is heavy on this vehicle',
        'Fuel is ' + kmText(r.fuelPerKm) + '/km but the true figure is ' + kmText(r.perKm) + '/km. Servicing, tyres, oil and amortised fixed cost add ' +
        kmText(r.perKm - r.fuelPerKm) + ' to every kilometre.', 'warn');
      return;
    }
    verdict('tr-verdict', 'Fuel is ' + kit.pct(r.fuel / r.total * 100, 0) + ' of this trip',
      'Total ' + kit.money(r.total) + ' for ' + kit.amount(r.distance) + ' km = ' + kmText(r.perKm) + ' per km, against ' +
      kmText(r.fuelPerKm) + ' per km if you only counted fuel' +
      (r.occupants > 1 ? '. Shared by ' + r.occupants + ' people it is ' + kit.money(r.total / r.occupants) + ' each.' : '. You are carrying all of it alone.')
      , '');
  }

  /* ------------------------------------------------------------ section 02 */

  function paintBreakdown(r) {
    var bars = document.getElementById('cost-bars');
    var donut = document.getElementById('fuel-donut');
    paintVehicleTable(r.input);

    if (r.empty) {
      bars.innerHTML = '<p class="tiny">Enter a distance in the trip planner and the stack draws itself here.</p>';
      kit.donut(donut, 0, { label: 'no data' });
      setFigure('donut-label', null);
      document.getElementById('donut-note').textContent = '—';
      return;
    }

    var s = r.stack;
    kit.bars(bars, [
      { label: 'Fuel at the pump', value: round(r.fuel), display: kit.money(r.fuel) },
      { label: 'Servicing / repairs', value: round(s.service * r.distance), display: kit.money(s.service * r.distance) },
      { label: 'Tyres', value: round(s.tyrePerKm * r.distance), display: kit.money(s.tyrePerKm * r.distance) },
      { label: 'Oil and filters', value: round(s.oilPerKm * r.distance), display: kit.money(s.oilPerKm * r.distance) },
      { label: 'Insurance / registration', value: round(r.fixed), display: kit.money(r.fixed) }
    ]);

    var share = r.fuel / r.total * 100;
    kit.donut(donut, share, { label: kit.pct(share, 1) + ' fuel', color: 'var(--brand)' });
    setFigure('donut-label', kit.pct(share, 1));
    document.getElementById('donut-note').textContent =
      'Of every ₦100 this trip costs, ₦' + share.toFixed(1) + ' is fuel at the pump and ₦' + (100 - share).toFixed(1) +
      ' is the vehicle wearing out. Price a job on the first number only and you lose money on the second.';
  }

  function paintVehicleTable(t) {
    var body = document.getElementById('vehicle-body');
    var rows = P.map(function (p) {
      var s = stack(p, t.stateId, F.monthly);
      return { p: p, s: s, perSeat: s.totalPerKm / Math.max(1, p.seats) };
    });
    body.innerHTML = rows.map(function (row) {
      return '<tr' + (row.p.id === t.profileId ? ' class="is-current"' : '') + '>' +
        '<td>' + esc(row.p.name) + '</td>' +
        '<td class="num">' + kit.amount(row.s.kmL, 1) + '</td>' +
        '<td class="num">' + kit.amount(row.s.fuelPerKm, 1) + '</td>' +
        '<td class="num">' + kit.amount(row.s.wearPerKm, 1) + '</td>' +
        '<td class="num">' + kit.amount(row.s.fixedPerKm, 1) + '</td>' +
        '<td class="num"><b>' + kit.amount(row.s.totalPerKm, 1) + '</b></td>' +
        '<td class="num">' + kit.amount(row.perSeat, 1) + '</td></tr>';
    }).join('');

    kit.bars(document.getElementById('vehicle-bars'), rows.map(function (row) {
      return {
        label: row.p.name.replace(/\s*\(.*?\)/, ''), value: round(row.s.totalPerKm),
        display: '₦' + kit.amount(row.s.totalPerKm, 1) + '/km'
      };
    }));
  }

  /* ------------------------------------------------------------ section 03 */

  function modeCost(mode, distance, people) {
    var seats = Math.max(1, mode.seats || 1);
    var perPerson, total, perSeat, cars, perVehicle;
    if ((mode.basis || 'vehicle') === 'seat') {
      perPerson = Math.max(mode.min || 0, (mode.perKm || 0) * distance);
      total = perPerson * people;
      perSeat = perPerson;
      cars = people;
      perVehicle = perPerson;
    } else {
      cars = Math.ceil(people / seats);
      perVehicle = Math.max(mode.min || 0, (mode.perKm || 0) * distance);
      total = perVehicle * cars;
      perPerson = total / Math.max(1, people);
      perSeat = perVehicle / seats;
    }
    return {
      total: total, perVehicle: perVehicle, cars: cars, basis: mode.basis || 'vehicle',
      perPerson: perPerson, perSeat: perSeat,
      perKm: total / Math.max(1, distance)
    };
  }

  /* the same fare as a straight line in distance, so crossings can be solved */
  function modeLine(mode, people) {
    if ((mode.basis || 'vehicle') === 'seat') {
      return { slope: (mode.perKm || 0), intercept: (mode.min || 0) };
    }
    var seats = Math.max(1, mode.seats || 1);
    var cars = Math.ceil(people / seats);
    return { slope: (mode.perKm || 0) * cars / people, intercept: (mode.min || 0) * cars / people };
  }

  function paintHead() {
    var t = readTrip();
    var r = trip(t);
    var body = document.getElementById('h-body');
    var people = kit.clamp(Math.round(numOf('h-people', 4)), 1, 60);
    var distance = r.empty ? 0 : r.distance;

    if (r.empty) {
      body.innerHTML = '<tr><td colspan="6">Set a distance in the trip planner first.</td></tr>';
      setFigure('x-value', null);
      document.getElementById('x-note').textContent = 'No trip distance entered yet.';
      document.getElementById('cross-facts').innerHTML = '';
      document.getElementById('h-bars').innerHTML = '';
      document.getElementById('h-note').textContent = '—';
      document.getElementById('h-note-2').textContent = '—';
      return;
    }

    var seatsInCar = t.profile.seats + 1;
    var driveCars = Math.ceil(people / seatsInCar);
    var driveTotal = r.total * driveCars;
    var drive = {
      total: driveTotal,
      perPerson: driveTotal / people,
      perSeat: r.total / seatsInCar,
      perKm: r.perKm * driveCars
    };

    var rows = [{ short: 'Drive your car', name: 'Drive yourself — ' + t.profile.name, cost: drive, note: 'fuel + wear + amortised fixed, ' + driveCars + ' car' + (driveCars > 1 ? 's' : '') }]
      .concat(M.map(function (m) {
        return { short: m.name.split(' (')[0].split(',')[0], name: m.name, cost: modeCost(m, distance, people), note: m.note };
      }));

    var sorted = rows.slice().sort(function (a, b) { return a.cost.perPerson - b.cost.perPerson; });
    var best = sorted[0];

    body.innerHTML = sorted.map(function (row, i) {
      var cls = i === 0 ? 'rank-1' : (i === sorted.length - 1 ? 'rank-last' : '');
      return '<tr><td><b>' + esc(row.name) + '</b><br><span class="tiny">' + esc(row.note) + '</span></td>' +
        '<td class="num">' + kit.money(row.cost.total) + '</td>' +
        '<td class="num"><b class="' + cls + '">' + kit.money(row.cost.perPerson) + '</b></td>' +
        '<td class="num">' + kit.money(row.cost.perSeat) + '</td>' +
        '<td class="num">' + kit.money(row.cost.perKm) + '</td>' +
        '<td>' + (i === 0 ? '1st — cheapest' : String(i + 1)) + '</td></tr>';
    }).join('');

    kit.bars(document.getElementById('h-bars'), sorted.map(function (row) {
      return { label: esc(row.short), value: round(row.cost.perPerson), display: kit.money(row.cost.perPerson) };
    }));

    paintCrossover(r, people, best, drive);

    document.getElementById('h-note').textContent =
      'Cheapest way to move ' + people + ' ' + (people === 1 ? 'person' : 'people') + ' ' + kit.amount(distance) +
      ' km is ' + best.name + ' at ' + kit.money(best.cost.perPerson) + ' each. Driving yourself is ' +
      kit.money(drive.perPerson) + ' per person at the true cost per kilometre, not the fuel-only figure.';
    document.getElementById('h-note-2').textContent =
      'If you priced driving on fuel alone you would quote ' + kit.money(r.fuel * driveCars / people) +
      ' per person — ' + kit.money(drive.perPerson - r.fuel * driveCars / people) + ' too low per head, before a single naira of tyre or oil money.';
  }

  function paintCrossover(r, people, best, drive) {
    var mode = modeById(value('h-compare'));
    var xValue = document.getElementById('x-value');
    var xNote = document.getElementById('x-note');
    var facts = document.getElementById('cross-facts');

    if (!mode) {
      xValue.textContent = '—';
      xNote.textContent = 'Choose a comparator above.';
      facts.innerHTML = '';
      return;
    }

    var seatsInCar = r.input.profile.seats + 1;
    var occupantsPerCar = Math.max(1, Math.min(people, seatsInCar));
    var cars = Math.ceil(people / occupantsPerCar);
    var driveIntercept = r.idlingCost / people;
    var driveSlope = r.perKm * cars / people;

    var seatsPerVehicle = Math.max(1, Math.min(people, mode.seats || 1));
    var line = modeLine(mode, people);
    var modeIntercept = line.intercept;
    var modeSlope = line.slope;

    var slopeDiff = driveSlope - modeSlope;
    var at = function (slope, intercept, d) { return intercept + slope * d; };
    var driveNow = at(driveSlope, driveIntercept, r.distance);
    var modeNow = at(modeSlope, modeIntercept, r.distance);
    var modeShort = mode.name.split(' (')[0].split(',')[0];
    var headline, note;

    if (Math.abs(slopeDiff) < 0.5) {
      headline = 'No crossover exists';
      note = 'Both sides cost almost the same per kilometre per person — ' + kit.money(driveSlope) + ' against ' +
        kit.money(modeSlope) + '. The distance never flips the answer, so decide on time, luggage and certainty.';
    } else {
      var d = (modeIntercept - driveIntercept) / slopeDiff;
      if (d <= 0.5) {
        var cheaperNow = driveNow <= modeNow ? 'Driving your own car' : modeShort;
        headline = driveNow <= modeNow ? 'Driving wins at every distance' : modeShort + ' wins at every distance';
        note = 'The two cost lines only meet behind the starting point, so the ranking never changes on any road you will actually drive. ' +
          cheaperNow + ' is ahead at 1 km and stays ahead at ' + kit.amount(r.distance) + ' km: ' + kit.money(driveSlope) +
          ' per person per km in the ' + r.input.profile.name + ' against ' + kit.money(modeSlope) + ' for ' + modeShort + '.';
      } else if (d > 3000) {
        headline = 'No crossover on any realistic route';
        note = 'The lines would meet at ' + kit.amount(Math.round(d)) + ' km — past Lagos→London territory. Up to any distance you will price today, ' +
          (driveNow <= modeNow ? 'driving stays cheaper' : modeShort + ' stays cheaper') + ' (' + kit.money(driveSlope) + ' against ' +
          kit.money(modeSlope) + ' per person per km).';
      } else if (slopeDiff < 0) {
        headline = 'Driving becomes cheaper above ' + kit.amount(Math.round(d)) + ' km';
        note = 'Below ' + Math.round(d) + ' km the fixed fare of ' + kit.money(mode.min) + ' dominates; above it, ' +
          kit.money(modeSlope) + ' per person per km beats your ' + kit.money(driveSlope) + '. This trip is ' +
          kit.amount(r.distance) + ' km, so ' + (r.distance > d ? 'drive.' : 'take ' + modeShort + '.');
      } else {
        headline = 'Driving only wins below ' + kit.amount(Math.round(d)) + ' km';
        note = 'Your car costs ' + kit.money(driveSlope) + ' per person per km against ' + kit.money(modeSlope) +
          ' on ' + modeShort + '. Past ' + Math.round(d) + ' km ' + modeShort + ' is cheaper however you share the seats — fill more seats, or take it.';
      }
    }

    xValue.textContent = headline;
    xNote.textContent = note;

    facts.innerHTML = [
      { k: 'Drive yourself, per person here', v: kit.money(driveNow) },
      { k: modeShort + ', per person here', v: kit.money(modeNow) },
      { k: 'Your total cost per km', v: kit.money(r.perKm) },
      { k: 'Marginal cost per km (no fixed)', v: kit.money(r.marginalPerKm) },
      { k: 'Cheaper on this trip', v: driveNow <= modeNow ? 'Drive yourself' : modeShort },
      { k: 'Gap over this trip, per person', v: kit.money(Math.abs(driveNow - modeNow)) }
    ].map(function (row) {
      return '<li><span>' + esc(row.k) + '</span><b>' + esc(row.v) + '</b></li>';
    }).join('');
  }

  /* ------------------------------------------------------------ section 04 */

  function paintBudget() {
    var t = readTrip();
    var profile = t.profile;
    var s = stack(profile, t.stateId, F.monthly);
    var tank = Math.max(1, t.tank);
    var oneWay = kit.clamp(numOf('g-commute', 0), 0, 400);
    var days = kit.clamp(Math.round(numOf('g-days', 5)), 0, 7);
    var weeks = kit.clamp(numOf('g-weeks', 4.3), 1, 5);
    var weekendN = kit.clamp(Math.round(numOf('g-weekend-n', 0)), 0, 40);
    var weekendKm = kit.clamp(numOf('g-weekend-km', 0), 0, 2000);
    var buffer = kit.clamp(numOf('g-buffer', 0), 0, 60);
    var income = kit.clamp(numOf('g-income', 0), 0, 50000000);
    var cTraffic = kit.clamp(numOf('g-commute-traffic', 1.12), 0.5, 3);
    var idleH = kit.clamp(numOf('g-idle-h', 0), 0, 12);

    var commuteKm = oneWay * 2 * days * weeks;
    var weekendTotal = weekendN * weekendKm;
    var rawKm = commuteKm + weekendTotal;
    var km = rawKm * (1 + buffer / 100);

    var cityEff = s.kmL / cTraffic;
    var weekendShare = rawKm > 0 ? weekendTotal / rawKm : 0;
    var blendedEff = cityEff * (1 - weekendShare) + s.kmL * weekendShare;
    var idleRate = F.idle * (profile.fuel === 'diesel' ? 0.8 : 1);
    var litres = km / blendedEff + idleH * days * weeks * idleRate;
    var cost = litres * s.price;
    var wearTotal = km * (s.wearPerKm + s.fixedPerKm);
    var share = income > 0 ? cost / income * 100 : 0;

    setFigure('bg-cost', kit.money(cost));
    document.getElementById('bg-note').textContent =
      kit.amount(litres, 1) + ' litres at ' + kit.money(s.price) + '/L ' + profile.fuel +
      (profile.fuel === 'diesel' ? ' — a diesel vehicle is priced on the diesel column' : '');
    setFigure('bg-litres', kit.amount(litres, 1) + ' L');
    setFigure('bg-km', kit.amount(km, 0) + ' km');
    setFigure('bg-commute', rawKm > 0 ? kit.pct(commuteKm / rawKm * 100, 0) + ' of the distance' : 'no commute entered');
    setFigure('bg-share', income > 0 ? kit.pct(share, 1) : '—');
    setFigure('m-baperkm', km > 0 ? kit.money(cost / km) : '—');
    setFigure('m-batotal', kit.money(cost + wearTotal));
    setFigure('m-bafills', litres > 0 ? kit.amount(litres / tank, 1) + ' tanks · every ' + kit.amount(Math.max(1, 30 * tank / litres), 0) + ' days' : '—');

    document.getElementById('bg-bar').style.width = kit.clamp(share, 0, 100).toFixed(1) + '%';
    document.getElementById('bg-bar-note').textContent = income > 0
      ? 'Fuel is ' + kit.pct(share, 1) + ' of the ₦' + kit.amount(income) + ' you bring in. Add ' + kit.money(wearTotal) +
      ' of wear and the vehicle takes ' + kit.pct((cost + wearTotal) / income * 100, 1) + ' of your earnings.'
      : 'Enter monthly earnings and this bar shows fuel as a share of what you actually take home.';

    if (income <= 0) verdict('bg-verdict', 'No income entered', 'Type what the vehicle earns, or your salary, and the share line becomes meaningful.', '');
    else if (share > 35) verdict('bg-verdict', 'Fuel is eating the business',
      kit.pct(share, 1) + ' of earnings goes to the pump before a single naira of tyres or oil. A closer base, a leaner vehicle class or load-sharing beats any fuel-saving trick.', 'bad');
    else if (share > 20) verdict('bg-verdict', 'Fragile but workable',
      kit.pct(share, 1) + ' of earnings on fuel. A 20% pump increase pushes that to ' + kit.pct(share * 1.2, 1) + '% — which is the number to plan against, not today’s.', 'warn');
    else verdict('bg-verdict', 'Fuel is under control',
      kit.pct(share, 1) + ' of earnings, with ' + kit.money(income - cost) + ' left for everything else — including the wear you are deferring.', 'ok');

    kit.$('#scen-body').innerHTML = [0, 10, 15, 20, 30].map(function (rise, i) {
      var price = s.price * (1 + rise / 100);
      var monthCost = litres * price;
      var extra = monthCost - cost;
      return '<tr' + (i === 0 ? ' class="is-current"' : '') + '>' +
        '<td>' + (i === 0 ? 'Today at the pump' : 'Pump +' + rise + '%') + '</td>' +
        '<td class="num">' + kit.money(price) + '</td>' +
        '<td class="num">' + kit.amount(litres, 1) + '</td>' +
        '<td class="num"><b>' + kit.money(monthCost) + '</b></td>' +
        '<td class="num">' + (extra ? '+' + kit.money(extra) : 'baseline') + '</td>' +
        '<td class="num">' + (income > 0 ? kit.pct(monthCost / income * 100, 1) : '—') + '</td></tr>';
    }).join('');
  }

  /* ------------------------------------------------------------ section 05 */

  function learn(list) {
    /* true km/L from consecutive full-tank odometer readings, per vehicle */
    var byVehicle = {};
    list.forEach(function (row) {
      (byVehicle[row.profileId] = byVehicle[row.profileId] || []).push(row);
    });
    var samples = [];
    Object.keys(byVehicle).forEach(function (key) {
      var rows = byVehicle[key].slice().sort(function (a, b) {
        return String(a.date).localeCompare(String(b.date)) || (a.odo || 0) - (b.odo || 0);
      });
      var prev = null;
      rows.forEach(function (row) {
        if (row.full && prev && prev.full && row.odo > prev.odo && row.litres > 0) {
          row._learned = (row.odo - prev.odo) / row.litres;
          samples.push({ profileId: key, value: row._learned, date: row.date });
        }
        prev = row;
      });
    });
    return samples;
  }

  function distanceCovered(rowsAsc) {
    var km = 0;
    for (var i = 1; i < rowsAsc.length; i++) {
      var a = rowsAsc[i - 1], b = rowsAsc[i];
      if (a.profileId === b.profileId && b.odo && a.odo && b.odo > a.odo) km += b.odo - a.odo;
    }
    return km;
  }

  function paintLog(newId) {
    var all = logRows();
    var samples = learn(all);
    var shown = all.slice().sort(function (a, b) {
      return String(b.date).localeCompare(String(a.date)) || (b.odo || 0) - (a.odo || 0);
    });
    var filter = value('log-month') || 'all';
    if (filter !== 'all') shown = shown.filter(function (row) { return String(row.date).slice(0, 7) === filter; });

    document.getElementById('log-body').innerHTML = shown.map(function (row) {
      var p = profileById(row.profileId);
      var perL = row.litres > 0 ? row.cost / row.litres : 0;
      var learned = row._learned
        ? '<b>' + kit.amount(row._learned, 1) + '</b>'
        : '<span class="badge ' + (row.full ? 'badge-ink' : 'badge-warn') + '">' + (row.full ? 'needs previous tank' : 'partial tank') + '</span>';
      return '<tr' + (row.id === newId ? ' class="is-new"' : '') + '>' +
        '<td class="mono">' + esc(row.date) + '</td>' +
        '<td>' + esc(p ? p.name : row.profileId) + '</td>' +
        '<td class="num">' + kit.amount(row.litres, 1) + '</td>' +
        '<td class="num">' + kit.amount(row.cost) + '</td>' +
        '<td class="num">' + kit.amount(perL, 0) + '</td>' +
        '<td class="num">' + kit.amount(row.odo) + '</td>' +
        '<td class="num">' + learned + '</td>' +
        '<td class="tiny">' + esc(row.note || '—') + '</td>' +
        '<td><button class="row-del" type="button" data-del="' + esc(row.id) + '" aria-label="Delete entry ' + esc(row.date) + '">×</button></td>' +
        '</tr>';
    }).join('');

    var litres = shown.reduce(function (a, b) { return a + (b.litres || 0); }, 0);
    var paid = shown.reduce(function (a, b) { return a + (b.cost || 0); }, 0);
    var asc = shown.slice().reverse();
    var km = distanceCovered(asc);
    document.getElementById('log-foot').innerHTML = shown.length
      ? '<tr><td>Total' + (filter !== 'all' ? ' · ' + esc(monthName(filter)) : '') + '</td><td></td>' +
      '<td class="num">' + kit.amount(litres, 1) + '</td><td class="num">' + kit.amount(paid) + '</td>' +
      '<td class="num">' + (litres ? kit.amount(paid / litres, 0) : '—') + '</td>' +
      '<td class="num">' + (km ? kit.amount(km) + ' km' : '—') + '</td>' +
      '<td class="num">' + (km && litres ? kit.amount(km / litres, 1) : '—') + '</td>' +
      '<td colspan="3"></td></tr>'
      : '';

    document.getElementById('log-empty').hidden = all.length > 0;
    document.getElementById('log-count').textContent = all.length + ' entries · ' + samples.length +
      ' usable km/L sample' + (samples.length === 1 ? '' : 's') + (paid ? ' · ' + kit.money(paid) + ' in view' : '');

    paintLearning(samples);
    paintPrintSheet(shown, filter, litres, paid, samples, km);
  }

  function daysInMonth(ym) {
    var parts = String(ym).split('-');
    return new Date(Number(parts[0]), Number(parts[1]), 0).getDate();
  }
  function monthName(ym) {
    var parts = String(ym).split('-');
    var names = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];
    return (names[Number(parts[1]) - 1] || ym) + ' ' + parts[0];
  }

  function paintLearning(samples) {
    var t = readTrip();
    var claimed = Math.max(0.5, t.profile.kmL);
    var mine = samples.filter(function (s) { return s.profileId === t.profileId; });
    var pool = mine.length ? mine : samples;
    var spark = document.getElementById('learned-spark');

    setFigure('learned-claimed', kit.amount(claimed, 1) + ' km/L');

    if (!pool.length) {
      setFigure('learned-big', null);
      document.getElementById('learned-sub').textContent = 'no odometer pairs yet — log two full tanks of the same vehicle';
      setFigure('learned-diff', '—');
      setFigure('learned-count', '0');
      kit.sparkline(spark, []);
      verdict('learned-flag', 'Log two full tanks', 'Fill up, write the odometer, then fill again at the same pump. That pair gives a true km/L figure — the first number here nobody can argue with.', '');
      return;
    }

    var vals = pool.map(function (s) { return s.value; });
    var last3 = vals.slice(-3);
    var learned = last3.reduce(function (a, b) { return a + b; }, 0) / last3.length;
    var baseline = vals.length >= 3 ? vals.slice(0, 3).reduce(function (a, b) { return a + b; }, 0) / 3 : claimed;
    var vsClaimed = (learned / claimed - 1) * 100;
    var vsBaseline = (learned / baseline - 1) * 100;

    setFigure('learned-big', kit.amount(learned, 1) + ' km/L');
    document.getElementById('learned-sub').textContent = 'learned from ' + pool.length + ' odometer pair' + (pool.length === 1 ? '' : 's') +
      ' · ' + (mine.length ? t.profile.name : 'every logged vehicle') + ' · baseline ' + kit.amount(baseline, 1) + ' km/L';
    setFigure('learned-diff', (vsClaimed >= 0 ? '+' : '−') + kit.pct(Math.abs(vsClaimed), 1));
    setFigure('learned-count', String(pool.length));
    kit.sparkline(spark, vals, { dot: vsBaseline <= -F.flag ? '#ff7a5c' : '#4fa8c4' });

    if (vsBaseline <= -F.flag) {
      verdict('learned-flag', 'Your car needs a service — check the tyres',
        'Learned economy is ' + kit.amount(learned, 1) + ' km/L against your own baseline of ' + kit.amount(baseline, 1) +
        ', down ' + kit.pct(Math.abs(vsBaseline), 1) + ' and past the ' + F.flag + '% flag line. Under-inflated tyres, old oil, a dragging brake or a fouled filter are the usual causes.', 'bad');
    } else if (vsBaseline <= -F.flag / 2) {
      verdict('learned-flag', 'Slipping quietly',
        'Down ' + kit.pct(Math.abs(vsBaseline), 1) + ' from baseline (' + kit.amount(baseline, 1) + ' → ' + kit.amount(learned, 1) +
        ' km/L). Check tyre pressure this week: it is free, and it is usually the answer.', 'warn');
    } else if (vsClaimed < -15) {
      verdict('learned-flag', 'Below the claimed figure, steadily',
        'You are getting ' + kit.amount(learned, 1) + ' km/L where the profile claims ' + kit.amount(claimed, 1) +
        '. Price your jobs on your own number, not the showroom one.', 'warn');
    } else {
      verdict('learned-flag', 'Holding its economy',
        'Learned ' + kit.amount(learned, 1) + ' km/L against a claimed ' + kit.amount(claimed, 1) + ' and your baseline of ' +
        kit.amount(baseline, 1) + '. Keep logging — the trend matters more than any single fill.', 'ok');
    }
  }

  function paintMonthFilter() {
    var sel = document.getElementById('log-month');
    var current = sel.value || 'all';
    var months = {};
    logRows().forEach(function (row) { months[String(row.date).slice(0, 7)] = 1; });
    var keys = Object.keys(months).sort().reverse();
    sel.innerHTML = '<option value="all">All months</option>' + keys.map(function (m) {
      return '<option value="' + esc(m) + '"' + (m === current ? ' selected' : '') + '>' + esc(monthName(m)) + '</option>';
    }).join('');
    if (keys.indexOf(current) === -1) sel.value = 'all';
  }

  function paintPrintSheet(rows, filter, litres, paid, samples, km) {
    var sheet = document.getElementById('print-sheet');
    if (!sheet) return;
    var mine = rows.filter(function (r) { return r._learned; });
    var avg = mine.length ? mine.reduce(function (a, b) { return a + b._learned; }, 0) / mine.length : 0;
    sheet.innerHTML =
      '<h3>Monthly fuel sheet</h3>' +
      '<div class="sheet-head"><span><b>FuelMeter</b> · ' + esc(filter === 'all' ? 'all logged months' : monthName(filter)) + '</span>' +
      '<span>Prepared ' + esc(kit.dateLabel(new Date())) + ' · prices as of ' + esc(kit.dateLabel(asOf())) + '</span></div>' +
      '<div class="table-wrap"><table><thead><tr><th>Date</th><th>Vehicle</th><th class="num">Litres</th><th class="num">Paid ₦</th><th class="num">Odometer</th><th class="num">Real km/L</th><th>Note</th><th>Driver signature</th></tr></thead><tbody>' +
      (rows.length ? rows.map(function (r) {
        var p = profileById(r.profileId);
        return '<tr><td>' + esc(r.date) + '</td><td>' + esc(p ? p.name : '') + '</td><td class="num">' + kit.amount(r.litres, 1) +
          '</td><td class="num">' + kit.amount(r.cost) + '</td><td class="num">' + kit.amount(r.odo) + '</td><td class="num">' +
          (r._learned ? kit.amount(r._learned, 1) : '—') + '</td><td>' + esc(r.note || '') + '</td><td style="min-width:110px"></td></tr>';
      }).join('') : '<tr><td colspan="8">No entries in this period.</td></tr>') +
      '</tbody></table></div>' +
      '<p class="sheet-foot">Litres ' + kit.amount(litres, 1) + ' · paid ₦' + kit.amount(paid) +
      (litres ? ' · average ₦' + kit.amount(paid / litres, 0) + '/L' : '') +
      (km ? ' · about ' + kit.amount(km) + ' km covered between readings' : '') +
      (avg ? ' · learned economy ' + kit.amount(avg, 1) + ' km/L' : '') +
      ' · ' + samples.length + ' usable samples. Recorded by the driver at the pump; not an audited document.</p>';
  }

  function addLog() {
    var err = document.getElementById('l-error');
    err.textContent = '';
    var litres = numOf('l-litres', 0);
    var cost = numOf('l-cost', 0);
    var odo = numOf('l-odo', 0);
    if (!(litres > 0)) {
      err.textContent = 'Litres must be more than zero.';
      document.getElementById('l-litres').focus();
      return;
    }
    var list = logRows();
    var maxOdo = list.reduce(function (a, b) { return Math.max(a, b.odo || 0); }, 0);
    if (odo && odo < maxOdo) {
      err.textContent = 'Odometer is below your last reading — the sample will not be used.';
    }
    var entry = {
      id: kit.uid('fill'), date: value('l-date') || isoDaysAgo(0), profileId: value('l-vehicle'),
      litres: round(litres), cost: round(cost), odo: Math.round(odo),
      full: checked('l-full'), note: (value('l-note') || '').trim().slice(0, 40)
    };
    list.push(entry);
    if (!sLog.write(list)) { kit.toast('This browser blocked saving; export the CSV instead'); return; }
    paintMonthFilter();
    paintLog(entry.id);
    kit.toast('Fill-up logged · ₦' + kit.amount(litres > 0 ? cost / litres : 0, 0) + ' per litre');
    document.getElementById('l-note').value = '';
  }

  /* ------------------------------------------------------------ fleet tab */

  function fleetComputed() {
    return fleetRows().map(function (row) {
      var p = profileById(row.profileId);
      if (!p) return null;
      var s = stack(p, row.stateId, Math.max(200, row.km));
      var eff = s.kmL / Math.max(0.5, row.traffic || 1);
      var litres = row.km / eff;
      var fuel = litres * s.price;
      var wear = row.km * (s.wearPerKm + s.fixedPerKm);
      var total = fuel + wear;
      return {
        row: row, p: p, litres: litres, fuel: fuel, wear: wear, total: total,
        perKm: total / Math.max(1, row.km),
        perSeat: total / Math.max(1, row.km) / Math.max(1, row.seats)
      };
    }).filter(Boolean);
  }

  function paintFleet() {
    var computed = fleetComputed();
    var ranked = computed.slice().sort(function (a, b) { return b.perKm - a.perKm; });
    var totals = computed.reduce(function (acc, c) {
      return { cost: acc.cost + c.total, km: acc.km + c.row.km, litres: acc.litres + c.litres };
    }, { cost: 0, km: 0, litres: 0 });

    document.getElementById('fleet-body').innerHTML = computed.length
      ? computed.map(function (c) {
        return '<tr>' +
          '<td><b>' + esc(c.row.name) + '</b></td>' +
          '<td>' + esc(c.p.name) + '</td>' +
          '<td class="num">' + kit.amount(c.row.km) + '</td>' +
          '<td class="num">' + kit.amount(c.litres, 1) + '</td>' +
          '<td class="num">' + kit.amount(c.fuel) + '</td>' +
          '<td class="num">' + kit.amount(c.wear) + '</td>' +
          '<td class="num"><b>' + kit.amount(c.total) + '</b></td>' +
          '<td class="num"><b>' + kit.amount(c.perKm, 1) + '</b></td>' +
          '<td class="num">' + kit.amount(c.perSeat, 1) + '</td>' +
          '<td><button class="row-del" type="button" data-del="' + esc(c.row.id) + '" aria-label="Remove ' + esc(c.row.name) + '">×</button></td>' +
          '</tr>';
      }).join('')
      : '<tr><td colspan="10">No vehicles yet. Add a plate or a name, the profile, and the kilometres it really does.</td></tr>';

    setFigure('fleet-total', kit.money(totals.cost));
    setFigure('fleet-km', kit.amount(totals.km) + ' km');
    setFigure('fleet-avg', totals.km ? kit.money(totals.cost / totals.km) : '—');
    setFigure('fleet-worst', ranked.length ? ranked[0].row.name + ' · ₦' + kit.amount(ranked[0].perKm, 1) + '/km' : '—');
    document.getElementById('fleet-count').textContent = computed.length + ' vehicle' + (computed.length === 1 ? '' : 's') +
      ' · ' + kit.amount(totals.litres, 0) + ' L a month';

    if (ranked.length) {
      kit.bars(document.getElementById('fleet-bars'), ranked.slice(0, 8).map(function (c) {
        return { label: esc(c.row.name), value: round(c.perKm), display: '₦' + kit.amount(c.perKm, 1) + '/km · ₦' + kit.amount(c.total) + '/mo' };
      }));
    } else {
      document.getElementById('fleet-bars').innerHTML = '<p class="tiny">Add vehicles and the ranking appears here, worst ₦/km first.</p>';
    }
  }

  function addFleet() {
    var list = fleetRows();
    var name = (value('f-name') || '').trim().slice(0, 30) || 'Vehicle ' + (list.length + 1);
    list.push({
      id: kit.uid('veh'), name: name, profileId: value('f-profile'),
      km: kit.clamp(numOf('f-km', 0), 1, 40000), stateId: value('f-state'),
      traffic: kit.clamp(numOf('f-traffic', 1), 0.5, 3), seats: kit.clamp(Math.round(numOf('f-seats', 4)), 1, 60)
    });
    if (!sFleet.write(list)) { kit.toast('This browser blocked saving; export the CSV instead'); return; }
    paintFleet();
    document.getElementById('f-name').value = '';
    kit.toast(name + ' added to the fleet');
  }

  /* ------------------------------------------------------------ section 06 */

  function paintPriceTable() {
    document.getElementById('price-body').innerHTML = PR.map(function (row, i) {
      return '<tr data-index="' + i + '">' +
        '<td>' + esc(row.name) + '</td>' +
        '<td class="num"><input type="number" min="100" max="6000" step="5" value="' + row.petrol + '" data-field="petrol" aria-label="' + esc(row.name) + ' petrol price per litre"></td>' +
        '<td class="num"><input type="number" min="100" max="6000" step="5" value="' + row.diesel + '" data-field="diesel" aria-label="' + esc(row.name) + ' diesel price per litre"></td>' +
        '</tr>';
    }).join('');
    var asof = document.getElementById('price-asof');
    if (asof && !asof.value) asof.value = asOf();
    setAsOfLabel();
  }

  function setAsOfLabel() {
    var inline = document.getElementById('t-price-asof-inline');
    if (inline) inline.textContent = 'as of ' + kit.dateLabel(asOf());
  }

  function paintProfileTable() {
    document.getElementById('profile-body').innerHTML = P.map(function (p, i) {
      var cell = function (field, attrs, unit) {
        return '<td class="num"><input type="number" value="' + p[field] + '" data-field="' + field + '" ' + attrs +
          ' aria-label="' + esc(p.name + ' ' + unit) + '"></td>';
      };
      return '<tr data-index="' + i + '"><td>' + esc(p.name) + '<br><span class="tiny">' + esc((p.range || '') + ' km/L · ' + p.fuel) + '</span></td>' +
        cell('kmL', 'min="1" max="120" step="0.5"', 'kilometres per litre claim') +
        cell('tank', 'min="1" max="300" step="1"', 'tank litres') +
        cell('seats', 'min="1" max="60" step="1"', 'passenger seats') +
        cell('service', 'min="0" max="500" step="1"', 'servicing cost per km') +
        cell('tyrePrice', 'min="1000" max="5000000" step="1000"', 'tyre set price') +
        cell('tyreKm', 'min="1000" max="200000" step="500"', 'tyre life in km') +
        cell('oilCost', 'min="0" max="500000" step="500"', 'oil service cost') +
        cell('oilKm', 'min="200" max="30000" step="100"', 'oil interval in km') +
        cell('fixedMonth', 'min="0" max="2000000" step="1000"', 'fixed cost per month') +
        '</tr>';
    }).join('');
  }

  function paintFareTable() {
    document.getElementById('fare-body').innerHTML = M.map(function (m, i) {
      var basis = m.basis || 'vehicle';
      return '<tr data-index="' + i + '">' +
        '<td>' + esc(m.name) + '</td>' +
        '<td class="num"><input type="number" min="0" max="500000" step="50" value="' + (m.min || 0) + '" data-field="min" aria-label="' + esc(m.name) + ' minimum fare"></td>' +
        '<td class="num"><input type="number" min="1" max="3000" step="1" value="' + (m.perKm || 0) + '" data-field="perKm" aria-label="' + esc(m.name) + ' naira per kilometre"></td>' +
        '<td class="num"><input type="number" min="1" max="60" step="1" value="' + (m.seats || 1) + '" data-field="seats" aria-label="' + esc(m.name) + ' seats per vehicle"></td>' +
        '<td><select data-field="basis" aria-label="' + esc(m.name) + ' priced per">' +
        '<option value="seat"' + (basis === 'seat' ? ' selected' : '') + '>per seat</option>' +
        '<option value="vehicle"' + (basis === 'vehicle' ? ' selected' : '') + '>per vehicle</option></select></td>' +
        '<td><input type="text" value="' + esc(m.note) + '" maxlength="70" data-field="note" aria-label="' + esc(m.name) + ' note"></td>' +
        '</tr>';
    }).join('');
  }

  function bindEditableTable(tableId, getList, setList, persist) {
    kit.on(document.getElementById(tableId), 'change', '[data-field]', function (e, el) {
      var row = el.closest('tr');
      var list = getList();
      var item = list[Number(row.getAttribute('data-index'))];
      if (!item) return;
      var field = el.getAttribute('data-field');
      if (field === 'note' || field === 'basis') {
        item[field] = String(el.value).slice(0, 70);
      } else {
        var n = kit.num(el.value);
        item[field] = el.min === '' ? n : kit.clamp(n, kit.num(el.min), kit.num(el.max));
      }
      setList(list);
      persist();
    });
    kit.on(document.getElementById(tableId), 'input', 'input[data-field]', function (e, el) {
      var row = el.closest('tr');
      var list = getList();
      var item = list[Number(row.getAttribute('data-index'))];
      if (!item) return;
      var field = el.getAttribute('data-field');
      if (field === 'note') {
        item.note = String(el.value).slice(0, 70);
      } else {
        var n = kit.num(el.value);
        item[field] = el.min === '' ? n : kit.clamp(n, kit.num(el.min), kit.num(el.max));
      }
      setList(list);
      persist();
    });
  }

  function paintFactors() {
    document.getElementById('k-idle').value = F.idle;
    document.getElementById('k-ac').value = F.ac;
    document.getElementById('k-load').value = F.load;
    document.getElementById('k-monthly').value = F.monthly;
    document.getElementById('k-flag').value = F.flag;
    document.getElementById('k-go-slow').value = F.goSlow;
    document.getElementById('t-ac-pct').textContent = F.ac;
    var slow = document.querySelector('#t-traffic option[value="1.25"]');
    if (slow) { slow.value = String(F.goSlow); slow.textContent = 'Go-slow — multiplier ' + F.goSlow; }
  }

  function refreshStateSelects() {
    ['t-state', 'f-state'].forEach(function (id) {
      var sel = document.getElementById(id);
      if (!sel) return;
      var current = sel.value;
      PR.forEach(function (row) {
        var opt = sel.querySelector('option[value="' + row.id + '"]');
        if (opt) opt.textContent = row.name + ' — ₦' + kit.amount(row.petrol) + ' / ₦' + kit.amount(row.diesel);
      });
      sel.value = current;
    });
  }

  /* --------------------------------------------------------- worked example */

  function worked() {
    var holder = document.getElementById('worked');
    if (!holder) return;
    var t = readTrip();
    var p = t.profile;
    var s = stack(p, t.stateId, F.monthly);
    var distance = Math.max(1, t.km * (t.ret ? 2 : 1));
    var cleanLitres = distance / s.kmL;
    var now = trip(Object.assign({}, t, { km: distance, ret: false }));
    var city = trip(Object.assign({}, t, { km: distance, ret: false, traffic: 1.12, idle: 0, ac: false, loadKg: 0 }));

    var steps = [
      '<b>' + esc(p.name) + '</b> at ' + kit.amount(s.kmL, 1) + ' km/L doing <b>' + kit.amount(distance) + ' km</b> at ' +
      kit.money(s.price) + '/L ' + p.fuel + ' (' + esc(priceRow(t.stateId).name) + ', as of ' + esc(kit.dateLabel(asOf())) + ').',
      'Litres on the claimed figure = distance ÷ km/L = <b>' + kit.amount(cleanLitres, 2) + ' L</b>, which at that pump price is <b>' +
      kit.money(cleanLitres * s.price) + '</b> of fuel.',
      'Your conditions multiply consumption (road ' + kit.amount(t.traffic, 2) + (t.ac ? ', A/C +' + F.ac + '%' : '') +
      (t.loadKg ? ', load ' + kit.amount(t.loadKg) + ' kg' : '') + (t.idle ? ', ' + kit.amount(t.idle, 2) + ' h idling' : '') +
      '): effective <b>' + kit.amount(now.effKmL, 1) + ' km/L</b> → <b>' + kit.amount(now.litres, 2) + ' L</b> → <b>' + kit.money(now.fuel) + '</b>.',
      'Wear you pay anyway: servicing ₦' + kit.amount(s.service, 0) + '/km + tyres ₦' + kit.amount(s.tyrePerKm, 1) +
      '/km (set ₦' + kit.amount(p.tyrePrice) + ' ÷ ' + kit.amount(p.tyreKm) + ' km) + oil ₦' + kit.amount(s.oilPerKm, 1) +
      '/km = <b>₦' + kit.amount(s.wearPerKm, 1) + '/km</b> → <b>' + kit.money(now.wear) + '</b> for this trip.',
      'Insurance, registration and park levies: ₦' + kit.amount(p.fixedMonth) + '/month ÷ ' + kit.amount(F.monthly) +
      ' km/month = <b>₦' + kit.amount(s.fixedPerKm, 1) + '/km</b> → <b>' + kit.money(now.fixed) + '</b>.',
      'TOTAL trip cost = fuel + wear + amortised fixed = <b>' + kit.money(now.total) + '</b>.',
      'Fuel cost per km = <b>₦' + kit.amount(now.fuelPerKm, 1) + '</b>. TOTAL cost per km = <b>₦' + kit.amount(now.perKm, 1) +
      '</b> — shown separately, because the ₦' + kit.amount(now.perKm - now.fuelPerKm, 1) + ' gap is exactly what a fuel-only quote forgets.',
      'Switch the road condition to city stop-go (1.12) and the same distance burns <b>' + kit.amount(city.litres, 2) + ' L</b> = <b>' +
      kit.money(city.fuel) + '</b>, about <b>' + kit.money(city.fuel - cleanLitres * s.price) + '</b> more at this pump.',
      'Shared between <b>' + now.occupants + '</b> people it is <b>' + kit.money(now.total / now.occupants) + ' each</b>, or ₦' +
      kit.amount(now.perSeatKm, 1) + ' per seat per kilometre. Price a commercial job on the ₦' + kit.amount(now.marginalPerKm, 1) +
      '/km marginal figure, not the total, if the fixed cost is already sunk.'
    ];
    holder.innerHTML = steps.map(function (step) { return '<li>' + step + '</li>'; }).join('');
  }

  /* ---------------------------------------------------------------- populate */

  function fillSelect(id, options, selected) {
    var el = document.getElementById(id);
    if (!el) return;
    el.innerHTML = options.map(function (o) {
      return '<option value="' + esc(o.value) + '"' + (String(o.value) === String(selected) ? ' selected' : '') + '>' + esc(o.label) + '</option>';
    }).join('');
  }

  function profileOptions() {
    return P.map(function (p) { return { value: p.id, label: p.name + ' — ' + p.kmL + ' km/L, ' + p.fuel }; });
  }
  function stateOptions() {
    return PR.map(function (r) { return { value: r.id, label: r.name + ' — ₦' + kit.amount(r.petrol) + ' / ₦' + kit.amount(r.diesel) }; });
  }

  function populateSelects() {
    fillSelect('t-profile', profileOptions(), 'corolla');
    fillSelect('t-state', stateOptions(), 'oyo');
    fillSelect('l-vehicle', profileOptions(), 'corolla');
    fillSelect('f-profile', profileOptions(), 'corolla');
    fillSelect('f-state', stateOptions(), 'lagos');
    fillSelect('h-compare', M.map(function (m) { return { value: m.id, label: m.name }; }), 'hail');

    var townOpts = [{ value: '', label: '— not listed —' }].concat(TOWNS.map(function (t) { return { value: t.name, label: t.name }; }));
    fillSelect('t-origin', townOpts, 'Lagos');
    fillSelect('t-dest', townOpts, 'Ibadan');

    var routeOpts = [{ value: 'custom', label: 'Custom distance — I will type it' }];
    ROUTES.forEach(function (row, i) {
      routeOpts.push({ value: String(i), label: row[0] + ' → ' + row[1] + ' · ' + row[2] + ' km' });
      routeOpts.push({ value: 'r' + i, label: row[1] + ' → ' + row[0] + ' · ' + row[2] + ' km' });
    });
    fillSelect('t-route', routeOpts, '0');
    applyRoute('0');
  }

  function findRoute(origin, dest) {
    for (var i = 0; i < ROUTES.length; i++) {
      if (ROUTES[i][0] === origin && ROUTES[i][1] === dest) return { index: i, reverse: false };
      if (ROUTES[i][1] === origin && ROUTES[i][0] === dest) return { index: i, reverse: true };
    }
    return null;
  }

  function applyRoute(key) {
    if (key === 'custom' || key === '' || key === null) return;
    var reverse = String(key).charAt(0) === 'r';
    var row = ROUTES[Number(reverse ? String(key).slice(1) : key)];
    if (!row) return;
    document.getElementById('t-origin').value = reverse ? row[1] : row[0];
    document.getElementById('t-dest').value = reverse ? row[0] : row[1];
    document.getElementById('t-km').value = row[2];
    var state = townState(reverse ? row[0] : row[1]);
    if (state) document.getElementById('t-state').value = state;
  }

  function syncProfileFields() {
    var p = profileById(value('t-profile'));
    document.getElementById('t-fills').value = p.tank;
    document.getElementById('t-profile-detail').textContent =
      'Claims ' + p.kmL + ' km/L (' + (p.range || '') + ') · ' + p.seats + ' passenger seats · ' + p.fuel + ' · tank ' + p.tank + ' L';
  }

  /* ---------------------------------------------------------------- wiring */

  function recompute() {
    refreshStateSelects();
    paintTrip();
    paintFleet();
  }

  function bind() {
    kit.$$('#trip-form input, #trip-form select').forEach(function (el) {
      el.addEventListener('input', paintTrip);
      el.addEventListener('change', paintTrip);
    });
    document.getElementById('t-route').addEventListener('change', function () {
      applyRoute(this.value);
      syncProfileFields();
      paintTrip();
    });
    ['t-origin', 't-dest'].forEach(function (id) {
      document.getElementById(id).addEventListener('change', function () {
        var found = findRoute(value('t-origin'), value('t-dest'));
        var routeSel = document.getElementById('t-route');
        if (found) {
          routeSel.value = found.reverse ? 'r' + found.index : String(found.index);
          applyRoute(routeSel.value);
        } else {
          routeSel.value = 'custom';
          var st = townState(value('t-dest'));
          if (st) document.getElementById('t-state').value = st;
        }
        paintTrip();
      });
    });
    document.getElementById('t-profile').addEventListener('change', function () {
      syncProfileFields();
      paintTrip();
    });
    document.getElementById('t-km').addEventListener('input', function () {
      document.getElementById('t-route').value = 'custom';
    });
    document.getElementById('trip-form').addEventListener('reset', function () {
      setTimeout(function () {
        document.getElementById('t-route').value = '0';
        applyRoute('0');
        syncProfileFields();
        paintTrip();
      }, 0);
    });
    document.getElementById('use-in-budget').addEventListener('click', function () {
      var t = readTrip();
      document.getElementById('g-weekend-km').value = Math.round(t.km * 2);
      paintBudget();
      document.getElementById('budget').scrollIntoView({ behavior: kit.reduce ? 'auto' : 'smooth', block: 'start' });
      kit.toast('Trip distance copied into the monthly budget');
    });

    ['h-people', 'h-compare'].forEach(function (id) {
      document.getElementById(id).addEventListener('input', paintHead);
      document.getElementById(id).addEventListener('change', paintHead);
    });
    kit.$$('#budget-form input, #budget-form select').forEach(function (el) {
      el.addEventListener('input', paintBudget);
      el.addEventListener('change', paintBudget);
    });

    /* fuel log */
    document.getElementById('l-add').addEventListener('click', addLog);
    document.getElementById('log-form').addEventListener('submit', function (e) { e.preventDefault(); addLog(); });
    kit.on(document.getElementById('log-body'), 'click', '[data-del]', function (e, el) {
      var list = logRows().filter(function (row) { return row.id !== el.getAttribute('data-del'); });
      sLog.write(list);
      paintMonthFilter();
      paintLog();
      kit.toast('Entry removed');
    });
    document.getElementById('log-month').addEventListener('change', function () { paintLog(); });
    document.getElementById('log-csv').addEventListener('click', function () {
      var list = logRows();
      if (!list.length) { kit.toast('Log something first'); return; }
      var samples = learn(list);
      kit.csv([['Date', 'Vehicle', 'Claimed km/L', 'Litres', 'Paid naira', 'Naira per litre', 'Odometer km', 'Realised km/L', 'Full tank', 'Note']]
        .concat(list.slice().sort(function (a, b) { return String(a.date).localeCompare(String(b.date)); }).map(function (row) {
          var p = profileById(row.profileId);
          var realised = null;
          samples.forEach(function (s) { if (s.profileId === row.profileId && s.date === row.date) realised = s.value; });
          return [row.date, p ? p.name : row.profileId, p ? p.kmL : '', round(row.litres), round(row.cost),
          round(row.litres > 0 ? row.cost / row.litres : 0), row.odo, realised === null ? '' : round(realised),
          row.full ? 'yes' : 'no', row.note || ''];
        })), 'fuelmeter-log-' + new Date().toISOString().slice(0, 10) + '.csv');
      kit.toast('Log CSV downloaded');
    });
    document.getElementById('log-print').addEventListener('click', function () {
      if (!logRows().length) { kit.toast('Nothing logged to print'); return; }
      document.body.classList.add('print-log');
      kit.print();
    });
    document.getElementById('log-clear').addEventListener('click', function () {
      if (!logRows().length) { kit.toast('The log is already empty'); return; }
      if (!window.confirm('Delete every fill-up logged in this browser?')) return;
      sLog.clear();
      paintMonthFilter();
      paintLog();
      kit.toast('Fuel log cleared');
    });

    /* fleet */
    document.getElementById('f-add').addEventListener('click', addFleet);
    document.getElementById('fleet-form').addEventListener('submit', function (e) { e.preventDefault(); addFleet(); });
    kit.on(document.getElementById('fleet-body'), 'click', '[data-del]', function (e, el) {
      var list = fleetRows().filter(function (row) { return row.id !== el.getAttribute('data-del'); });
      sFleet.write(list);
      paintFleet();
      kit.toast('Vehicle removed');
    });
    document.getElementById('fleet-csv').addEventListener('click', function () {
      var computed = fleetComputed();
      if (!computed.length) { kit.toast('Add a vehicle first'); return; }
      kit.csv([['Vehicle', 'Profile', 'Km per month', 'Litres per month', 'Fuel naira per month', 'Wear + fixed naira per month', 'Total naira per month', 'Naira per km', 'Naira per seat per km', 'Priced in']]
        .concat(computed.map(function (c) {
          return [c.row.name, c.p.name, c.row.km, round(c.litres), round(c.fuel), round(c.wear), round(c.total),
          round(c.perKm), round(c.perSeat), priceRow(c.row.stateId).name];
        })), 'fuelmeter-fleet-' + new Date().toISOString().slice(0, 10) + '.csv');
      kit.toast('Fleet CSV downloaded');
    });
    document.getElementById('fleet-print').addEventListener('click', function () {
      if (!fleetRows().length) { kit.toast('No vehicles to print'); return; }
      document.body.classList.add('print-fleet');
      kit.print();
    });
    document.getElementById('fleet-clear').addEventListener('click', function () {
      if (!fleetRows().length) { kit.toast('The fleet is already empty'); return; }
      if (!window.confirm('Remove every vehicle saved in this browser?')) return;
      sFleet.clear();
      paintFleet();
      kit.toast('Fleet cleared');
    });

    /* assumptions */
    bindEditableTable('price-body', function () { return PR; }, function (list) { PR = list; }, function () { sPrices.write(PR); recompute(); });
    bindEditableTable('profile-body', function () { return P; }, function (list) { P = list; }, function () { sProfiles.write(P); recompute(); });
    bindEditableTable('fare-body', function () { return M; }, function (list) { M = list; }, function () { sModes.write(M); recompute(); });

    var factorIds = { 'k-idle': 'idle', 'k-ac': 'ac', 'k-load': 'load', 'k-monthly': 'monthly', 'k-flag': 'flag', 'k-go-slow': 'goSlow' };
    Object.keys(factorIds).forEach(function (id) {
      document.getElementById(id).addEventListener('input', function () {
        F[factorIds[id]] = kit.clamp(kit.num(this.value), 0.01, 100000);
        sFactors.write(F);
        paintFactors();
        recompute();
      });
    });
    document.getElementById('price-asof').addEventListener('change', function () {
      sAsOf.write(this.value || isoDaysAgo(0));
      recompute();
      kit.toast('Prices marked as of ' + kit.dateLabel(this.value));
    });
    document.getElementById('reset-prices').addEventListener('click', function () {
      PR = clone(DEFAULT_PRICES); sPrices.write(PR); paintPriceTable(); recompute(); kit.toast('Default pump prices restored');
    });
    document.getElementById('reset-profiles').addEventListener('click', function () {
      P = clone(DEFAULT_PROFILES); sProfiles.write(P); paintProfileTable(); populateSelects(); syncProfileFields(); recompute();
      kit.toast('Default vehicle profiles restored');
    });
    document.getElementById('reset-fares').addEventListener('click', function () {
      M = clone(DEFAULT_MODES); sModes.write(M); paintFareTable();
      fillSelect('h-compare', M.map(function (m) { return { value: m.id, label: m.name }; }), value('h-compare'));
      recompute(); kit.toast('Default fare rates restored');
    });
    document.getElementById('reset-factors').addEventListener('click', function () {
      F = clone(DEFAULT_FACTORS); sFactors.write(F); paintFactors(); recompute(); kit.toast('Default physics restored');
    });

    window.addEventListener('afterprint', function () {
      document.body.classList.remove('print-log', 'print-fleet');
    });
  }

  /* ------------------------------------------------------------------- boot */

  kit.boot({
    ready: function () {
      buildTicks();
      populateSelects();
      paintPriceTable();
      paintProfileTable();
      paintFareTable();
      paintFactors();
      document.getElementById('l-date').value = isoDaysAgo(0);
      syncProfileFields();
      bind();
      paintTrip();
      paintMonthFilter();
      paintLog();
      paintFleet();
    }
  });
})();
