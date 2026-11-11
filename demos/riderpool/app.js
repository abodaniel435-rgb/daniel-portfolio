/* RiderPool — trip costing, fare splitting, stop economics, manifest and ledger.
   Everything is computed here in the browser. No server, no matching, no payment. */
(function () {
  'use strict';

  /* ---------- reference tables (all editable by the user) ---------- */

  var DEFAULT_CORRIDORS = [
    { name: 'Benin City → Lagos', km: 245, hours: 4 },
    { name: 'Lagos → Ibadan', km: 130, hours: 2 },
    { name: 'Ibadan → Abuja', km: 485, hours: 7 },
    { name: 'Abuja → Kaduna', km: 190, hours: 3 },
    { name: 'Abuja → Lafia', km: 195, hours: 3.5 },
    { name: 'Abuja → Enugu', km: 280, hours: 5 },
    { name: 'Port Harcourt → Lagos', km: 470, hours: 8 },
    { name: 'Port Harcourt → Benin City', km: 210, hours: 4 },
    { name: 'Onitsha → Lagos', km: 300, hours: 6 },
    { name: 'Kano → Abuja', km: 330, hours: 5.5 },
    { name: 'Warri → Benin City', km: 145, hours: 3 },
    { name: 'Abeokuta → Lagos', km: 100, hours: 2 },
    { name: 'Ilorin → Abuja', km: 295, hours: 5 },
    { name: 'Uyo → Port Harcourt', km: 150, hours: 3 }
  ];

  var DEFAULT_CLASSES = [
    { name: 'Sienna / Hiace', seats: 6, kml: 9, wear: 49, tolls: 5000, levy: 3000 },
    { name: 'Saloon (Corolla / Camry)', seats: 4, kml: 12, wear: 26, tolls: 4000, levy: 2500 },
    { name: 'SUV (RAV4 / Highlander)', seats: 5, kml: 9.5, wear: 45, tolls: 5000, levy: 3000 },
    { name: 'Hilux double cab', seats: 4, kml: 10, wear: 55, tolls: 4000, levy: 3000 },
    { name: '13-seater Kiss', seats: 12, kml: 6.5, wear: 70, tolls: 6000, levy: 4500 },
    { name: '30-seater bus', seats: 29, kml: 4.5, wear: 120, tolls: 12000, levy: 9000 },
    { name: 'Keke NAA', seats: 2, kml: 18, wear: 9, tolls: 500, levy: 1000 },
    { name: 'Okada', seats: 1, kml: 28, wear: 4, tolls: 300, levy: 800 }
  ];

  var DEFAULT_PRICES = [
    { city: 'Lagos', petrol: 1650, ago: 1550 },
    { city: 'Benin City', petrol: 1700, ago: 1600 },
    { city: 'Ibadan', petrol: 1650, ago: 1560 },
    { city: 'Abuja', petrol: 1700, ago: 1620 },
    { city: 'Port Harcourt', petrol: 1750, ago: 1650 },
    { city: 'Onitsha', petrol: 1750, ago: 1650 },
    { city: 'Enugu', petrol: 1750, ago: 1660 },
    { city: 'Kaduna', petrol: 1800, ago: 1700 },
    { city: 'Kano', petrol: 1800, ago: 1700 },
    { city: 'Warri', petrol: 1750, ago: 1650 }
  ];

  var DEFAULT_PARCELS = [
    { label: 'Small parcel (backpack size)', rate: 800 },
    { label: 'Carton', rate: 2000 },
    { label: '30 kg sack', rate: 3500 },
    { label: 'Freezer-size box', rate: 9000 }
  ];

  var DEFAULT_ADJ = { front: 8, last: -7, child: -50, student: -15, night: 10, lug: 2000, detour: 18, detourKm: 11 };

  var DEFAULT_STOPS = [
    { name: 'Benin park (start)', km: 0, detourKm: 0, detourMin: 0 },
    { name: 'Okela', km: 18, detourKm: 3, detourMin: 7 },
    { name: 'Evbuotubu', km: 48, detourKm: 0, detourMin: 4 },
    { name: 'Ore', km: 85, detourKm: 5, detourMin: 9 },
    { name: 'Ijebu-Ode', km: 108, detourKm: 0, detourMin: 5 },
    { name: 'Sagamu', km: 130, detourKm: 11, detourMin: 18 },
    { name: 'Iyana Ilogbo', km: 185, detourKm: 0, detourMin: 4 },
    { name: 'Sango', km: 225, detourKm: 6, detourMin: 14 },
    { name: 'CMS (finish)', km: 245, detourKm: 0, detourMin: 0 }
  ];

  var DEFAULT_SEATS = [
    { name: 'Ada', phone: '', position: 'Row 2 left', type: 'Adult', boards: 0, alights: 8, paid: false },
    { name: 'Femi', phone: '', position: 'Row 2 middle', type: 'Adult', boards: 0, alights: 7, paid: false },
    { name: 'Chidi', phone: '', position: 'Row 2 right', type: 'Student', boards: 3, alights: 8, paid: false },
    { name: 'Tunde', phone: '', position: 'Front (navigator)', type: 'Front row', boards: 1, alights: 5, paid: true },
    { name: 'Blessing', phone: '', position: 'Row 3 left', type: 'Adult', boards: 0, alights: 8, paid: false },
    { name: 'Kene', phone: '', position: 'Row 3 middle', type: 'Last row', boards: 4, alights: 6, paid: false }
  ];

  var POSITIONS = ['Front (navigator)', 'Row 2 left', 'Row 2 middle', 'Row 2 right', 'Row 3 left', 'Row 3 middle', 'Row 3 right', 'Row 4 left', 'Row 4 middle', 'Row 4 right', 'Standing / extra'];
  var TYPES = ['Adult', 'Student', 'Child on lap', 'Front row', 'Last row', 'Luggage space'];

  /* sample board — fiction, written into the source file */
  var SAMPLE_TRIPS = [
    { corridor: 'Benin City → Lagos', day: 'Today', departs: '06:00', cls: 'Sienna / Hiace', left: 2, fare: 9000 },
    { corridor: 'Benin City → Lagos', day: 'Today', departs: '07:30', cls: '13-seater Kiss', left: 5, fare: 7500 },
    { corridor: 'Benin City → Lagos', day: 'Tomorrow', departs: '05:30', cls: 'SUV (RAV4 / Highlander)', left: 3, fare: 11000 },
    { corridor: 'Benin City → Lagos', day: 'Saturday', departs: '06:30', cls: 'Saloon (Corolla / Camry)', left: 1, fare: 12000 },
    { corridor: 'Lagos → Ibadan', day: 'Today', departs: '08:00', cls: 'Sienna / Hiace', left: 4, fare: 4000 },
    { corridor: 'Lagos → Ibadan', day: 'Tomorrow', departs: '09:15', cls: '30-seater bus', left: 12, fare: 3500 },
    { corridor: 'Onitsha → Lagos', day: 'Today', departs: '05:00', cls: 'Sienna / Hiace', left: 6, fare: 12000 },
    { corridor: 'Onitsha → Lagos', day: 'Saturday', departs: '06:00', cls: '13-seater Kiss', left: 9, fare: 9500 },
    { corridor: 'Abuja → Kaduna', day: 'Today', departs: '07:00', cls: 'Saloon (Corolla / Camry)', left: 3, fare: 5000 },
    { corridor: 'Abuja → Kaduna', day: 'Tomorrow', departs: '13:00', cls: 'Keke NAA', left: 2, fare: 1500 },
    { corridor: 'Port Harcourt → Lagos', day: 'Today', departs: '04:00', cls: 'SUV (RAV4 / Highlander)', left: 5, fare: 15000 },
    { corridor: 'Port Harcourt → Lagos', day: 'Saturday', departs: '16:00', cls: '30-seater bus', left: 21, fare: 11000 },
    { corridor: 'Ibadan → Abuja', day: 'Tomorrow', departs: '06:00', cls: 'Sienna / Hiace', left: 6, fare: 18000 },
    { corridor: 'Kano → Abuja', day: 'Today', departs: '05:30', cls: '13-seater Kiss', left: 11, fare: 9000 },
    { corridor: 'Warri → Benin City', day: 'Saturday', departs: '08:30', cls: 'Sienna / Hiace', left: 4, fare: 6000 }
  ];

  /* ---------- stores ---------- */

  var stores = {
    corridors: kit.store('riderpool:corridors', null),
    classes: kit.store('riderpool:classes', null),
    prices: kit.store('riderpool:prices', null),
    parcels: kit.store('riderpool:parcels', null),
    adj: kit.store('riderpool:adj', null),
    safety: kit.store('riderpool:safety', null),
    stops: kit.store('riderpool:stops', null),
    seats: kit.store('riderpool:seats', null),
    asof: kit.store('riderpool:asof', null),
    trip: kit.store('riderpool:trip', null),
    ledger: kit.store('riderpool:ledger', []),
    travel: kit.store('riderpool:travel', []),
    checks: kit.store('riderpool:checks', null)
  };

  function list(key, def) {
    var read = stores[key].read();
    return Array.isArray(read) && read.length ? read : def.map(clone);
  }
  function clone(o) { return JSON.parse(JSON.stringify(o)); }

  var corridors = list('corridors', DEFAULT_CORRIDORS);
  var classes = list('classes', DEFAULT_CLASSES);
  var prices = list('prices', DEFAULT_PRICES);
  var parcels = list('parcels', DEFAULT_PARCELS);
  var stops = list('stops', DEFAULT_STOPS);
  var seats = list('seats', DEFAULT_SEATS);
  var adj = Object.assign({}, DEFAULT_ADJ, stores.adj.read() || {});
  var safety = Object.assign({ checkin: 45, minNet: 0 }, stores.safety.read() || {});
  var asof = stores.asof.read() || '2026-10-20';
  var lastTrip = {};
  var flashed = {};

  /* ---------- tiny helpers ---------- */

  function el(id) { return document.getElementById(id); }
  function val(id, fallback) { var node = el(id); return node ? String(node.value || '') : (fallback || ''); }
  function numVal(id, fallback) { var node = el(id); if (!node) return fallback || 0; var n = kit.num(node.value); return isNaN(n) ? (fallback || 0) : n; }
  function chk(id, fallback) { var node = el(id); return node ? !!node.checked : !!fallback; }
  function put(id, text) { var node = el(id); if (node && node.textContent !== String(text)) node.textContent = String(text); }
  function round(n) { return Math.round((Number(n) || 0) * 100) / 100; }
  function money(n) { return kit.money(Math.round(Number(n) || 0)); }
  function signed(n, decimals) {
    var v = Number(n) || 0;
    var text = decimals === undefined ? money(Math.abs(v)) : kit.money(Math.abs(v), decimals);
    return (v < 0 ? '−' : v > 0 ? '+' : '') + text;
  }
  function escapeHtml(text) { var d = document.createElement('div'); d.textContent = String(text == null ? '' : text); return d.innerHTML; }
  function toMin(t) { var p = String(t || '06:00').split(':'); return (Number(p[0]) || 0) * 60 + (Number(p[1]) || 0); }
  function hhmm(mins) {
    var m = ((Math.round(mins) % 1440) + 1440) % 1440;
    var h = Math.floor(m / 60), mm = m % 60;
    return (h < 10 ? '0' : '') + h + ':' + (mm < 10 ? '0' : '') + mm;
  }
  function nairaPerKmTable() { var p = prices.filter(function (x) { return x.petrol > 0; }); return p.length ? p : DEFAULT_PRICES; }
  function classByName(name) {
    for (var i = 0; i < classes.length; i++) if (classes[i].name === name) return classes[i];
    return classes[0] || DEFAULT_CLASSES[0];
  }
  function corridorByName(name) {
    for (var i = 0; i < corridors.length; i++) if (corridors[i].name === name) return corridors[i];
    return null;
  }
  function pickOptions(select, items, current, labelFn) {
    if (!select) return;
    select.innerHTML = items.map(function (item) {
      var label = labelFn ? labelFn(item) : item.name;
      return '<option value="' + escapeHtml(label) + '"' + (label === current ? ' selected' : '') + '>' + escapeHtml(label) + '</option>';
    }).join('');
  }
  function fill(id, text) {
    var node = el(id);
    if (!node) return;
    var value = text === null || text === undefined || text === '' ? '—' : text;
    if (node.textContent === String(value)) return;
    node.textContent = String(value);
    if (!kit.reduce && flashed[id] !== undefined && flashed[id] !== String(value)) {
      node.classList.remove('tick');
      void node.offsetWidth;
      node.classList.add('tick');
    }
    flashed[id] = String(value);
  }

  /* ---------- trip engine ---------- */

  function tripInput() {
    var cls = classByName(val('r-class'));
    return {
      corridor: val('r-corridor'),
      clsName: cls.name,
      from: val('r-from', 'Benin City'),
      to: val('r-to', 'Lagos'),
      km: kit.clamp(numVal('r-km', 1), 1, 2000),
      hours: kit.clamp(numVal('r-hours', 0.25), 0.25, 30),
      seats: kit.clamp(Math.round(numVal('r-seats', 1)), 1, 40),
      sold: kit.clamp(Math.round(numVal('r-sold', 0)), 0, 40),
      date: val('r-date') || isoToday(),
      time: val('r-time') || '06:00',
      pickup: val('r-pickup', 'the motor park'),
      drop: val('r-drop', 'the destination park'),
      price: kit.clamp(numVal('r-price', 1650), 200, 6000),
      kml: kit.clamp(numVal('r-kml', 9), 1, 60),
      wear: kit.clamp(numVal('r-wear', 0), 0, 2000),
      tolls: numVal('r-tolls', 0),
      levy: numVal('r-levy', 0),
      driver: numVal('r-driver', 0),
      fare: numVal('r-fare', 0),
      buffer: kit.clamp(numVal('r-buffer', 10), 0, 60),
      recover: kit.clamp(numVal('r-recover', 0), 0, 100),
      legs: chk('r-return') ? 2 : 1,
      night: chk('r-night'),
      lugseat: chk('r-lugseat'),
      parcelCounts: [Math.round(numVal('r-p1', 0)), Math.round(numVal('r-p2', 0)), Math.round(numVal('r-p3', 0)), Math.round(numVal('r-p4', 0))]
    };
  }

  function isoToday() { return new Date().toISOString().slice(0, 10); }

  function costTrip(i) {
    var dist = i.km * i.legs;
    var litres = dist / Math.max(i.kml, 0.5);
    var fuel = litres * i.price;
    var wear = dist * i.wear;
    var extras = (i.tolls + i.levy) * i.legs;
    var cut = i.driver * i.legs;
    var nightBonus = i.night ? cut * adj.night / 100 : 0;
    var recover = (fuel + wear + extras + cut + nightBonus) * i.recover / 100;
    var total = fuel + wear + extras + cut + nightBonus + recover;

    var parcelRevenue = 0;
    i.parcelCounts.forEach(function (count, idx) {
      parcelRevenue += count * (parcels[idx] ? parcels[idx].rate : 0);
    });
    if (i.lugseat) parcelRevenue += adj.lug;

    var units = Math.max(1, i.seats);
    var bodies = Math.max(0, units - (i.lugseat ? 1 : 0));
    var toCover = Math.max(0, total - parcelRevenue);
    var perSeat = toCover / units;

    return {
      i: i, dist: dist, litres: litres, fuel: fuel, wear: wear, extras: extras, cut: cut + nightBonus,
      nightBonus: nightBonus, recover: recover, total: total, parcelRevenue: parcelRevenue,
      units: units, bodies: bodies, toCover: toCover, perSeat: perSeat,
      perKm: total / Math.max(dist, 1),
      perSeatKm: toCover / Math.max(units * dist, 1),
      profitFull: i.fare * units + parcelRevenue - total,
      beSeats: i.fare > 0 ? Math.ceil(toCover / i.fare) : Infinity,
      stranded: (units - i.sold) * perSeat
    };
  }

  /* seat type adjustments, applied to the asking fare */
  function seatPriceFor(type, c) {
    var base = c.i.fare;
    switch (type) {
      case 'Front row': return base * (1 + adj.front / 100);
      case 'Last row': return base * (1 + adj.last / 100);
      case 'Child on lap': return base * (1 + adj.child / 100);
      case 'Student': return base * (1 + adj.student / 100);
      case 'Luggage space': return adj.lug;
      default: return base;
    }
  }

  /* ---------- riders, distance shares and the four methods ---------- */

  function stopKm(idx) {
    var s = stops[kit.clamp(idx, 0, stops.length - 1)];
    return s ? Math.max(0, s.km) : 0;
  }

  function riders(c) {
    return seats.slice(0, c.units).map(function (s, n) {
      var b = stopKm(s.boards), a = stopKm(s.alights);
      var km = Math.max(0, a - b);
      return {
        n: n, name: s.name || ('Seat ' + (n + 1)), type: s.type, position: s.position,
        boards: s.boards, alights: s.alights, boardName: (stops[s.boards] || {}).name || '—',
        alightName: (stops[s.alights] || {}).name || '—', km: km, paid: !!s.paid
      };
    });
  }

  var METHODS = [
    { id: 'even', name: 'Even split', note: 'the same amount from every body' },
    { id: 'distance', name: 'Distance share', note: 'cost split by kilometres actually ridden' },
    { id: 'zone', name: 'Zone fare', note: 'asking fare pro-rated by distance, rounded' },
    { id: 'park', name: 'Park rule', note: 'full fare past 60% of the route, half before it' }
  ];

  function splitMethods(c, people) {
    var total = c.toCover;
    var kmSum = people.reduce(function (a, p) { return a + p.km; }, 0);
    var dist = Math.max(c.dist, 1);
    var n = Math.max(people.length, 1);
    var fareRate = c.i.fare / dist;
    var out = {};

    out.even = people.map(function () { return total / n; });
    out.distance = people.map(function (p) { return kmSum > 0 ? total * p.km / kmSum : total / n; });
    out.zone = people.map(function (p) { return Math.round(fareRate * p.km / 100) * 100; });
    out.park = people.map(function (p) { return p.km / dist >= 0.6 ? c.i.fare : c.i.fare / 2; });

    return METHODS.map(function (m) {
      var amounts = out[m.id] || [];
      var collected = amounts.reduce(function (a, b) { return a + b; }, 0);
      var drifts = amounts.map(function (amt, idx) {
        var share = kmSum > 0 ? total * people[idx].km / kmSum : total / n;
        return amt - share;
      });
      var abs = drifts.map(Math.abs);
      return {
        id: m.id, name: m.name, note: m.note, amounts: amounts, collected: collected,
        gap: collected - total,
        avg: abs.length ? abs.reduce(function (a, b) { return a + b; }, 0) / abs.length : 0,
        worst: abs.length ? Math.max.apply(null, abs) : 0,
        share: people.map(function (p, idx) { return kmSum > 0 ? total * p.km / kmSum : total / n; })
      };
    });
  }

  function bestMethod(rows) {
    var ranked = rows.map(function (r) {
      var shortfall = Math.max(0, -r.gap);
      return { r: r, score: r.avg + r.worst * 0.35 + shortfall / Math.max(r.amounts.length, 1) / 40 };
    }).sort(function (a, b) { return a.score - b.score; });
    return ranked[0].r;
  }

  /* ---------- stop economics and the ETA chain ---------- */

  function stopEconomics(c, people, chosen) {
    var perDetourKm = c.i.price / Math.max(c.i.kml, 0.5) + c.i.wear;
    var speed = Math.max(numVal('seq-speed', 62), 5);
    var stopMin = kit.clamp(numVal('seq-stopmin', 6), 0, 45);
    var ordered = stops.map(function (s, idx) { return { s: s, idx: idx }; })
      .sort(function (a, b) { return a.s.km - b.s.km; });

    var rows = ordered.map(function (entry) {
      var s = entry.s;
      var boards = people.filter(function (p) { return p.boards === entry.idx; });
      var alights = people.filter(function (p) { return p.alights === entry.idx; });
      var revenue = boards.reduce(function (a, p) {
        var idx = people.indexOf(p);
        return a + (chosen.amounts[idx] || 0);
      }, 0);
      var extraMin = (Number(s.detourMin) || 0) + (boards.length || alights.length ? stopMin : 0);
      var cost = (Number(s.detourKm) || 0) * perDetourKm;
      return {
        idx: entry.idx, name: s.name, km: s.km, detourKm: Number(s.detourKm) || 0,
        extraMin: extraMin, cost: cost, boards: boards.length, alights: alights.length,
        revenue: revenue, net: revenue - cost, bodies: boards.length
      };
    });

    var cumKm = 0, cumMin = 0, lost = 0;
    var depart = toMin(c.i.time);
    rows.forEach(function (r) {
      cumKm += Math.max(0, r.km - cumKm) + r.detourKm;
      cumMin += r.extraMin;
      var plain = cumKm / speed * 60 + cumMin;
      lost += r.detourKm / speed * 60 + r.detourKm * 0 + r.extraMin * 0;
      r.plain = plain;
      r.buffered = plain * (1 + c.i.buffer / 100);
      r.arrive = depart + r.buffered;
      r.legs = cumKm;
    });
    rows.lostMin = lost;
    return { rows: rows, speed: speed, stopMin: stopMin, perDetourKm: perDetourKm, lostMin: lost };
  }

  /* ---------- the trip code: same inputs, same six characters ---------- */

  function tripCode(c) {
    var seed = [c.i.from, c.i.to, c.i.date, c.i.clsName, c.units, Math.round(c.total)].join('|');
    var h = 2166136261;
    for (var i = 0; i < seed.length; i++) { h ^= seed.charCodeAt(i); h = Math.imul(h, 16777619); }
    var alpha = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
    var out = '', n = Math.abs(h);
    for (var k = 0; k < 6; k++) { out += alpha.charAt(n % alpha.length); n = Math.floor(n / alpha.length) + n * 7 + 13; }
    return out;
  }

  /* ---------- selects that feed the builder ---------- */

  var engine = {
    tripInput: tripInput, costTrip: costTrip, riders: riders, splitMethods: splitMethods,
    bestMethod: bestMethod, stopEconomics: stopEconomics, tripCode: tripCode, seatPriceFor: seatPriceFor
  };

  function paintSelects() {
    pickOptions(el('r-corridor'), corridors);
    pickOptions(el('r-class'), classes);
    pickOptions(el('r-price-from'), nairaPerKmTable(), null, function (p) { return p.city + ' · ₦' + kit.amount(p.petrol) + '/L'; });
    var dayOptions = ['Today', 'Tomorrow', 'Saturday', 'Sunday'];
    pickOptions(el('bd-day'), dayOptions.map(function (d) { return { name: d }; }));
    pickOptions(el('bd-corridor'), [{ name: 'All corridors' }].concat(corridors.map(function (c) { return { name: c.name }; })));
  }

  /* ---------- section 02: trip cost, break-even, fare ladder ---------- */

  function paintTrip(c) {
    var line = c.recover > 0;
    fill('f-total', money(c.total));
    put('f-total-note', 'true cost of the trip, every line counted' +
      (c.i.legs === 2 ? ' · both legs' : ' · one way'));
    fill('f-litres-inline', kit.amount(c.litres, 1) + ' L');
    fill('f-fuel', money(c.fuel));
    fill('f-wear', money(c.wear));
    fill('f-extras', money(c.extras));
    fill('f-cut', money(c.cut) + (c.nightBonus ? ' (incl. ₦' + kit.amount(c.nightBonus) + ' night bonus)' : ''));

    var recoverRow = el('f-recover-line');
    if (recoverRow) {
      recoverRow.classList.toggle('is-off', !line);
      var recoverB = recoverRow.querySelector('b');
      if (recoverB) recoverB.textContent = line ? money(c.recover) : 'not loaded';
    }
    fill('f-cover', money(c.toCover));
    put('f-cover-note', c.parcelRevenue > 0 ? 'after ' + money(c.parcelRevenue) + ' of parcel space is netted off' : 'seats alone must bring this in');
    fill('f-seats', c.i.lugseat ? c.units + ' units (' + c.bodies + ' bodies + luggage space)' : String(c.units));
    fill('f-perseat', money(c.perSeat));
    fill('f-perkm', money(c.perKm));
    fill('f-perseatkm', kit.money(c.perSeatKm, 2) + ' / seat-km');
    fill('f-profit', (c.profitFull >= 0 ? '+' : '−') + money(Math.abs(c.profitFull)));

    var rate = c.i.fare > 0 ? Math.min(1, c.toCover / (c.i.fare * c.units)) : 1;
    var barPct = Math.min(100, rate * 100);
    var soldPct = Math.min(100, c.i.sold / Math.max(c.units, 1) * 100);
    var bar = el('be-bar');
    var cap = el('be-cap');
    if (bar) bar.style.width = barPct.toFixed(1) + '%';
    if (bar) bar.parentElement.classList.toggle('is-over', isFinite(c.beSeats) && c.beSeats > c.units);
    if (cap) cap.style.left = Math.max(4, Math.min(99, soldPct)).toFixed(1) + '%';
    fill('be-fare', money(c.i.fare) + ' a seat');
    fill('be-seats', isFinite(c.beSeats) ? String(c.beSeats) : '∞');
    put('be-sub', isFinite(c.beSeats)
      ? (c.beSeats === 1 ? 'seat must sell to cover the trip' : 'seats must sell to cover the trip')
      : 'set an asking fare to see the break-even');
    put('be-honest', isFinite(c.beSeats) && c.beSeats > c.units
      ? 'At ₦' + kit.amount(c.i.fare) + ' you would need ' + c.beSeats + ' seats and only have ' + c.units +
      '. Either the fare is wrong or the trip is a loss: the honest number is ₦' + kit.amount(Math.ceil(c.perSeat / 100) * 100) + ' a seat.'
      : 'You have ' + c.units + ' seats on sale and expect to sell ' + c.i.sold + '. Empty seats leave ₦' +
      kit.amount(Math.round(c.stranded)) + ' of cost on the floor.');

    fill('m-litres', kit.amount(c.litres, 1) + ' L');
    fill('m-viable', money(Math.ceil(c.perSeat / 100) * 100));
    fill('m-empty', money(c.perSeat));

    paintLadder(c);
    fareVerdict(c);
    put('r-seats-help', classByName(c.i.clsName).seats + ' seats for this class · ' +
      (c.i.lugseat ? 'one sold as luggage space, so ' + c.bodies + ' bodies fit' : 'driver excluded'));
    put('r-class-help', classByName(c.i.clsName).kml + ' km/L · ₦' + classByName(c.i.clsName).wear + '/km wear · ' +
      'prices and wear are editable under Assumptions');
  }

  function paintLadder(c) {
    put('fl-sold', String(c.i.sold));
    var fares = [];
    var step = Math.max(500, Math.round(c.perSeat / 20 / 500) * 500);
    fares.push({ f: Math.ceil(c.perSeat / 100) * 100, tag: 'cost per seat' });
    if (c.i.fare > 0) fares.push({ f: c.i.fare, tag: 'your asking fare' });
    fares.push({ f: Math.round((c.i.fare + step) / 100) * 100, tag: 'asking + one step' });
    fares.push({ f: Math.round(c.perSeat * 1.25 / 100) * 100, tag: '25% over cost' });
    fares.push({ f: Math.round(c.perSeat * 1.5 / 100) * 100, tag: '50% over cost' });
    var seen = {};
    var rows = fares.filter(function (x) {
      if (!x.f || x.f <= 0 || seen[x.f]) return false;
      seen[x.f] = 1; return true;
    }).sort(function (a, b) { return a.f - b.f; });

    kit.$('#ladder-body').innerHTML = rows.map(function (row) {
      var need = Math.ceil(c.toCover / row.f);
      var fits = need <= c.units;
      var outcome = row.f * c.i.sold + c.parcelRevenue - c.total;
      return '<tr><td>' + money(row.f) + ' <span class="tiny">' + escapeHtml(row.tag) + '</span></td>' +
        '<td class="num">' + (need > c.units ? need + ' <span class="tiny">too many</span>' : need) + '</td>' +
        '<td class="num">' + (fits ? '<span class="pill-yes">fits</span>' : '<span class="pill-no">no</span>') + '</td>' +
        '<td class="num">' + (outcome >= 0 ? '+' : '−') + money(Math.abs(outcome)) + '</td></tr>';
    }).join('');
  }

  function fareVerdict(c) {
    var perSeat = c.perSeat, fare = c.i.fare;
    if (fare <= 0) {
      return verdict('fare-verdict', 'No asking fare yet', 'Type what a seat should cost to see break-even, profit and the ladder.', '');
    }
    if (fare < perSeat * 0.8) {
      var shortfall = Math.abs(fare * c.i.sold + c.parcelRevenue - c.total);
      return verdict('fare-verdict', 'This fare loses money before the driver eats',
        'Each seat carries ' + money(perSeat) + ' of cost and you are asking ' + money(fare) +
        '. At ' + c.i.sold + ' seats sold the trip is ' + money(shortfall) +
        ' short. The corridor does not pay at this price unless parcels fill the gap.', 'bad');
    }
    if (fare < perSeat) {
      return verdict('fare-verdict', 'Close, but the last seats are doing the work',
        'Cost per seat is ' + money(perSeat) + ' against a ' + money(fare) + ' fare — you need ' + c.beSeats +
        ' of ' + c.units + ' seats sold to be level. One family backing out puts you under.', 'warn');
    }
    if (fare > perSeat * 2.2) {
      return verdict('fare-verdict', 'Comfortable fare, hard sell',
        'You are asking ' + money(fare) + ' against a ' + money(perSeat) + ' cost. That is a margin, not a mistake — but check what the park quotes for this corridor before you count on filling it.', 'warn');
    }
    return verdict('fare-verdict', 'The fare covers the trip',
      money(fare) + ' a seat against ' + money(perSeat) + ' of cost each, so ' + c.beSeats +
      ' seats break even and you have ' + c.units + '. Selling all of them leaves ' +
      (c.profitFull >= 0 ? '+' : '−') + money(Math.abs(c.profitFull)) + ' in the car.', 'ok');
  }

  function verdict(idRoot, title, body, cls) {
    var box = el(idRoot);
    if (box) box.className = 'verdict' + (cls ? ' ' + cls : '');
    put(idRoot + '-title', title);
    put(idRoot + '-body', body);
  }

  /* ---------- section 03: seat prices, parcels, detour arithmetic ---------- */

  function paintPricing(c) {
    put('adj-base', money(c.i.fare));
    var inUse = {};
    seats.slice(0, c.units).forEach(function (s) { inUse[s.type] = true; });

    var rows = [
      { type: 'Front row', label: 'Front row (navigator)', adjTxt: '+' + adj.front + '%', who: 'Widest legroom, best view, first out' },
      { type: 'Adult', label: 'Middle rows', adjTxt: '—', who: 'The baseline seat everyone else is measured against' },
      { type: 'Last row', label: 'Last row behind the axle', adjTxt: adj.last + '%', who: 'Bumps, heat from the boot, last to alight' },
      { type: 'Student', label: 'Student', adjTxt: adj.student + '%', who: 'Regular on a termly corridor, pays cash at the park' },
      { type: 'Child on lap', label: 'Child on a lap', adjTxt: adj.child + '%', who: 'No seat consumed — but it is still a body' },
      { type: 'Luggage space', label: 'Space sold as a seat', adjTxt: 'flat', who: 'A crate, not a person. Counts as one unit' }
    ];
    kit.$('#seat-price-body').innerHTML = rows.map(function (r) {
      var price = seatPriceFor(r.type, c);
      return '<tr' + (inUse[r.type] ? ' class="is-on"' : '') + '><td>' + r.label + '</td>' +
        '<td class="num">' + r.adjTxt + '</td>' +
        '<td class="num">' + money(price) + '</td>' +
        '<td>' + r.who + '</td></tr>';
    }).join('');

    var parcelTotal = 0;
    kit.$('#parcel-body').innerHTML = parcels.map(function (p, idx) {
      var count = c.i.parcelCounts[idx] || 0;
      var rev = count * p.rate;
      parcelTotal += rev;
      return '<tr><td>' + escapeHtml(p.label) + '</td><td class="num">' + money(p.rate) + '</td>' +
        '<td class="num">' + count + '</td><td class="num">' + money(rev) + '</td></tr>';
    }).join('');
    kit.$('#parcel-foot').innerHTML = '<tr><td>Space revenue</td><td></td><td class="num">' +
      c.i.parcelCounts.reduce(function (a, b) { return a + b; }, 0) + '</td><td class="num">' + money(parcelTotal) + '</td></tr>';

    parcels.forEach(function (p, idx) { put('r-p' + (idx + 1) + '-rate', money(p.rate)); });

    var aboard = Math.max(1, c.i.sold);
    var detourKm = Math.max(0, adj.detourKm);
    var perSeatCharge = adj.detour * detourKm;
    var totalCharge = perSeatCharge * aboard;
    var realCost = detourKm * (c.i.price / Math.max(c.i.kml, 0.5) + c.i.wear);
    fill('dt-km', detourKm + ' km off the line');
    fill('dt-per', money(perSeatCharge) + ' a seat');
    fill('dt-total', money(totalCharge) + ' across ' + aboard + ' riders');
    fill('dt-real', money(realCost));
    put('dt-note', totalCharge >= realCost
      ? 'The diversion recovers itself ' + round(totalCharge / Math.max(realCost, 1)) + '× over at ₦' + adj.detour +
      '/km per seat. The minutes are still everyone\'s, though — about ' + Math.round(detourKm / 40 * 60) + ' extra on the road.'
      : 'At ₦' + adj.detour + '/km per seat the charge (' + money(totalCharge) + ') does not cover the fuel and wear of the detour (' +
      money(realCost) + '). Either charge more or drop the pickup.');

    var premium = Math.max(0, adj.front) + Math.max(0, adj.night);
    var discount = Math.abs(Math.min(0, adj.last)) + Math.abs(Math.min(0, adj.student)) + Math.abs(Math.min(0, adj.child));
    if (premium - discount > 12) {
      verdict('adj-verdict', 'Your premiums outrun your discounts', 'Rounding up ' + premium +
        '% against ' + discount + '% down means the average seat quietly costs more than the asking fare. That is a fare rise wearing a fairness label.', 'warn');
    } else if (Math.abs(premium - discount) <= 12) {
      verdict('adj-verdict', 'The board is close to neutral', 'Premiums of ' + premium + '% against discounts of ' +
        discount + '%. The asking fare of ' + money(c.i.fare) + ' is still the number the pool is built on, which is what honest adjustments should do.', 'ok');
    } else {
      verdict('adj-verdict', 'Discounts are winning', 'You are giving away ' + discount + '% and collecting ' + premium +
        '% back. Fine as a policy, but then cost per seat is not the asking fare — it is higher.', '');
    }
  }

  /* ---------- section 04: stop and seat editors, the four methods ---------- */

  function paintStops() {
    var options = function (current) {
      return stops.map(function (s, idx) {
        return '<option value="' + idx + '"' + (idx === current ? ' selected' : '') + '>' + escapeHtml(s.name) + '</option>';
      }).join('');
    };
    kit.$('#stops-body').innerHTML = stops.map(function (s, idx) {
      return '<tr data-stop="' + idx + '"><td><input class="wide" value="' + escapeHtml(s.name) + '" data-field="name" aria-label="Stop ' + (idx + 1) + ' name"></td>' +
        '<td class="stops-cell"><input type="number" min="0" max="2000" step="5" value="' + s.km + '" data-field="km" aria-label="Kilometres along for ' + escapeHtml(s.name) + '"></td>' +
        '<td class="stops-cell"><input type="number" min="0" max="80" step="1" value="' + (s.detourKm || 0) + '" data-field="detourKm" aria-label="Detour kilometres for ' + escapeHtml(s.name) + '"></td>' +
        '<td class="stops-cell"><input type="number" min="0" max="90" step="1" value="' + (s.detourMin || 0) + '" data-field="detourMin" aria-label="Detour minutes for ' + escapeHtml(s.name) + '"></td>' +
        '<td><button class="btn btn-ghost btn-sm" type="button" data-delstop="' + idx + '" aria-label="Remove ' + escapeHtml(s.name) + '">×</button></td></tr>';
    }).join('');
    window.__riderpoolStopOptions = options;
  }

  function paintSeats(c) {
    var options = window.__riderpoolStopOptions ? window.__riderpoolStopOptions() : '';
    var rows = seats.slice(0, c.units).map(function (s, idx) {
      var b = stopKm(s.boards), a = stopKm(s.alights);
      return '<tr data-seat="' + idx + '">' +
        '<td><input value="' + escapeHtml(s.name) + '" data-field="name" placeholder="Seat ' + (idx + 1) + '" aria-label="Rider name, seat ' + (idx + 1) + '"></td>' +
        '<td><select data-field="boards" aria-label="Where ' + escapeHtml(s.name || ('seat ' + (idx + 1))) + ' boards">' + options + '</select></td>' +
        '<td><select data-field="alights" aria-label="Where ' + escapeHtml(s.name || ('seat ' + (idx + 1))) + ' alights">' + options + '</select></td>' +
        '<td><select data-field="position" aria-label="Seat position">' + POSITIONS.map(function (p) {
          return '<option' + (p === s.position ? ' selected' : '') + '>' + p + '</option>';
        }).join('') + '</select></td>' +
        '<td><select data-field="type" aria-label="Seat type">' + TYPES.map(function (t) {
          return '<option' + (t === s.type ? ' selected' : '') + '>' + t + '</option>';
        }).join('') + '</select></td>' +
        '<td class="seats-cell">' + Math.max(0, a - b) + '</td></tr>';
    }).join('');
    kit.$('#seats-body').innerHTML = rows;
    var extra = c.units - seats.length;
    put('seats-note', seats.length + ' riders listed for ' + c.units + ' seats on sale' +
      (extra > 0 ? ' · ' + extra + ' seat' + (extra === 1 ? '' : 's') + ' still unnamed, priced at the asking fare' : '') +
      '. Names and phone numbers are only ever printed on your own manifest.');
  }

  function paintSplit(c) {
    var people = riders(c);
    var rows = splitMethods(c, people);
    var chosen = bestMethod(rows);
    window.__riderpoolState = { people: people, rows: rows, chosen: chosen };

    kit.$('#mode-body').innerHTML = rows.map(function (r) {
      var short = r.gap < -1;
      var over = r.gap > 1;
      var verdictTxt = r.id === chosen.id ? 'Closest to cost causation'
        : short ? 'Leaves ' + money(Math.abs(r.gap)) + ' of the trip unpaid'
        : over ? 'Over-collects by ' + money(r.gap) : 'Collects the cost, but drifts';
      return '<tr' + (r.id === chosen.id ? ' class="method-best"' : '') + '><td><b>' + r.name + '</b><div class="tiny">' + r.note + '</div></td>' +
        '<td class="num">' + money(r.collected) + '</td>' +
        '<td class="num">' + (over ? '+' : short ? '−' : '') + money(Math.abs(r.gap)) + '</td>' +
        '<td class="num">' + money(r.avg) + '</td>' +
        '<td class="num">' + money(r.worst) + '</td>' +
        '<td>' + verdictTxt + '</td></tr>';
    }).join('');

    put('mode-note', 'Cost causation is the yardstick: a rider\'s fair share is the whole cost to cover (' + money(c.toCover) +
      ') multiplied by the kilometres they rode, divided by everyone\'s kilometres. Drift is measured in naira against that share.');

    var head = '<tr><th>Rider</th><th class="num">Boards</th><th class="num">Alights</th><th class="num">km</th><th class="num">Fair share</th>' +
      rows.map(function (r) { return '<th class="num">' + r.name + '</th>'; }).join('') + '</tr>';
    kit.$('#split-head').innerHTML = head;

    kit.$('#split-body').innerHTML = people.map(function (p, idx) {
      var amounts = rows.map(function (r) { return r.amounts[idx] || 0; });
      var lo = Math.min.apply(null, amounts), hi = Math.max.apply(null, amounts);
      return '<tr><td>' + escapeHtml(p.name) + ' <span class="tiny">' + escapeHtml(p.type) + '</span></td>' +
        '<td class="num">' + escapeHtml(p.boardName) + '</td>' +
        '<td class="num">' + escapeHtml(p.alightName) + '</td>' +
        '<td class="num">' + p.km + '</td>' +
        '<td class="num">' + money(rows[1].share[idx]) + '</td>' +
        amounts.map(function (amt) {
          var cls = amt === lo && lo !== hi ? ' class="is-lowest"' : amt === hi && lo !== hi ? ' class="is-highest"' : '';
          return '<td class="num"' + cls + '>' + money(amt) + '</td>';
        }).join('') + '</tr>';
    }).join('');

    kit.$('#split-foot').innerHTML = '<tr><td>Collected</td><td></td><td></td><td class="num">' +
      people.reduce(function (a, p) { return a + p.km; }, 0) + '</td><td class="num">' + money(c.toCover) + '</td>' +
      rows.map(function (r) { return '<td class="num">' + money(r.collected) + '</td>'; }).join('') + '</tr>';

    kit.bars(el('split-bars'), people.map(function (p, idx) {
      return { label: escapeHtml(p.name), value: Math.max(0, chosen.amounts[idx] || 0), display: money(chosen.amounts[idx] || 0) };
    }));

    var worst = people.map(function (p, idx) {
      return { p: p, drift: (chosen.amounts[idx] || 0) - chosen.share[idx] };
    }).sort(function (a, b) { return Math.abs(b.drift) - Math.abs(a.drift); })[0];
    if (!worst) {
      verdict('split-verdict', 'Add riders to compare methods', 'The seat list above is empty, so there is nothing to split.', '');
    } else if (Math.abs(worst.drift) < 400) {
      verdict('split-verdict', chosen.name + ' is the honest answer here', 'Nobody drifts more than ' + money(Math.abs(worst.drift)) +
        ' from their fair share — the riders are close enough in distance that any method works. Show this table in the group before money changes hands.', 'ok');
    } else {
      verdict('split-verdict', chosen.name + ' is the fairest of the four, and it is still not neutral',
        worst.p.name + ' (' + worst.p.km + ' km) pays ' + money(Math.abs(worst.drift)) + ' ' + (worst.drift > 0 ? 'more' : 'less') +
        ' than cost causation says. The short-distance riders are subsidising the long ones under every method except distance share.', 'warn');
    }
    return chosen;
  }

  /* ---------- section 05: stop sequence and the ETA chain ---------- */

  function paintSequence(c, chosen) {
    var people = (window.__riderpoolState || {}).people || riders(c);
    var seq = stopEconomics(c, people, chosen);
    var flagged = seq.rows.filter(function (r) { return r.net < safety.minNet || (r.detourKm > 0 && r.revenue < r.cost); });

    kit.$('#seq-body').innerHTML = seq.rows.map(function (r, i) {
      var bad = r.detourKm > 0 && r.revenue < r.cost;
      return '<tr' + (bad || r.net < safety.minNet ? ' class="is-flagged"' : '') + '><td class="num">' + (i + 1) + '</td>' +
        '<td>' + escapeHtml(r.name) + (r.detourKm ? ' <span class="tiny">+' + r.detourKm + ' km off line</span>' : '') + '</td>' +
        '<td class="num">' + r.km + '</td>' +
        '<td class="num">' + r.detourKm + '</td>' +
        '<td class="num">' + Math.round(r.extraMin) + '</td>' +
        '<td class="num">' + money(r.cost) + '</td>' +
        '<td class="num">' + r.boards + (r.alights ? ' <span class="tiny">/' + r.alights + ' off</span>' : '') + '</td>' +
        '<td class="num">' + money(r.revenue) + '</td>' +
        '<td class="num' + (bad ? ' is-bad' : '') + '">' + (r.net >= 0 ? '+' : '−') + money(Math.abs(r.net)) + '</td></tr>';
    }).join('');

    var sumCost = seq.rows.reduce(function (a, r) { return a + r.cost; }, 0);
    var sumRev = seq.rows.reduce(function (a, r) { return a + r.revenue; }, 0);
    var sumMin = seq.rows.reduce(function (a, r) { return a + r.extraMin; }, 0);
    kit.$('#seq-foot').innerHTML = '<tr><td></td><td>Whole run</td><td class="num">' + Math.round(c.dist) + ' km</td>' +
      '<td class="num">' + seq.rows.reduce(function (a, r) { return a + r.detourKm; }, 0) + '</td>' +
      '<td class="num">' + Math.round(sumMin) + '</td><td class="num">' + money(sumCost) + '</td>' +
      '<td class="num">' + people.length + '</td><td class="num">' + money(sumRev) + '</td>' +
      '<td class="num">' + (sumRev - sumCost >= 0 ? '+' : '−') + money(Math.abs(sumRev - sumCost)) + '</td></tr>';

    var warn = el('seq-warn');
    if (warn) warn.className = 'tiny seq-warn' + (flagged.length ? ' is-bad' : '');
    put('seq-warn', flagged.length
      ? flagged.length + ' stop' + (flagged.length === 1 ? '' : 's') + ' cost more than the riders who board there bring in: ' +
      flagged.map(function (r) { return r.name; }).join(', ') + '. Drop the pickup, or charge the detour the way section 03 lets you.'
      : 'Every stop with a detour pays for itself at ₦' + round(seq.perDetourKm) + ' a kilometre of fuel and wear. Sort order is by kilometres along.');

    var last = seq.rows[seq.rows.length - 1];
    put('tl-title', c.i.from + ' → ' + c.i.to + ' · ' + Math.round(c.dist) + ' km at ' + seq.speed + ' km/h');
    put('tl-sub', 'Departure window opens ' + c.i.time + ' · buffer ' + c.i.buffer + '% · ' + seq.stopMin + ' min per stop · ' + seq.rows.length + ' stops');
    put('cruise-label', 'Average speed used for the ETA chain: ' + seq.speed + ' km per hour');

    var html = '<span class="tl-runner" aria-hidden="true"></span>';
    html += '<div class="tl-row" style="--i:0"><span class="tl-time">' + hhmm(toMin(c.i.time)) + '</span>' +
      '<div class="tl-body"><span class="tl-dot"></span><span class="tl-name">Wheels-off · ' + escapeHtml(c.i.pickup || 'the park') + '</span>' +
      '<span class="tl-meta">km 0 · ' + people.length + ' aboard · ' + c.i.parcelCounts.reduce(function (a, b) { return a + b; }, 0) + ' parcels</span></div></div>';
    seq.rows.slice(1).forEach(function (r, i) {
      var cls = [];
      if (r.boards) cls.push('is-boarding');
      if (r.alights) cls.push('is-alighting');
      if (r.detourKm > 0) cls.push('is-detour');
      var meta = ['km ' + r.km];
      if (r.detourKm) meta.push('+' + r.detourKm + ' km detour');
      if (r.extraMin) meta.push('+' + Math.round(r.extraMin) + ' min');
      if (r.boards) meta.push(r.boards + ' board');
      if (r.alights) meta.push(r.alights + ' alight');
      html += '<div class="tl-row ' + cls.join(' ') + '" style="--i:' + (i + 1) + '"><span class="tl-time">' + hhmm(r.arrive) + '</span>' +
        '<div class="tl-body"><span class="tl-dot"></span><span class="tl-name">' + escapeHtml(r.name) + '</span>' +
        '<span class="tl-meta">' + meta.join(' · ') + '</span></div></div>';
    });
    kit.$('#timeline').innerHTML = html;

    var plainArrive = toMin(c.i.time) + (last ? last.plain : 0);
    var buffered = toMin(c.i.time) + (last ? last.buffered : 0);
    fill('eta-depart', hhmm(toMin(c.i.time)));
    fill('eta-plain', hhmm(plainArrive));
    put('eta-buffer-pct', String(c.i.buffer));
    fill('eta-buffered', hhmm(buffered));
    var detourMin = seq.rows.reduce(function (a, r) { return a + r.detourKm / seq.speed * 60; }, 0);
    fill('eta-lost', Math.round(detourMin) + ' min');
    var checkin = c.i.time + ' ± ' + safety.checkin + ' min';
    fill('eta-checkin', checkin);
    fill('checkin-window', checkin);
    window.__riderpoolEta = hhmm(plainArrive) + ' plain, ' + hhmm(buffered) + ' buffered';
    put('tl-note', 'Riders should hear the buffered time, not the raw one. ' +
      'Arrival at ' + (last ? escapeHtml(last.name) : 'the destination') + ' is ' + hhmm(buffered) +
      ' with the ' + c.i.buffer + '% buffer, and the toll queue is not in that number.');
    return { plainArrive: plainArrive, buffered: buffered };
  }

  /* ---------- section 06: trip card, checklists, manifest, print ---------- */

  function paintCard(c, chosen) {
    var code = tripCode(c);
    put('trip-code', code);
    var people = riders(c);
    var lines = [];
    lines.push('RIDERPOOL · TRIP CARD · ' + code);
    lines.push('');
    lines.push(c.i.from.toUpperCase() + '  →  ' + c.i.to.toUpperCase() + '   (' + Math.round(c.dist) + ' km' + (c.i.legs === 2 ? ', both legs' : ', one way') + ')');
    lines.push('Date ' + kit.dateLabel(c.i.date) + ' · window opens ' + c.i.time + ' · ' + escapeHtml(c.i.clsName));
    lines.push('Pick up at: ' + (c.i.pickup || 'to be agreed') + '   Drop at: ' + (c.i.drop || 'to be agreed'));
    lines.push('');
    lines.push('THE MONEY');
    lines.push('  Fuel (' + kit.amount(c.litres, 1) + ' L)          ' + money(c.fuel));
    lines.push('  Wear share (' + money(c.wear) + ')');
    lines.push('  Tolls + park levy     ' + money(c.extras));
    lines.push('  Driver\'s cut          ' + money(c.cut));
    if (c.recover > 0) lines.push('  Return-leg recovery ' + money(c.recover));
    lines.push('  TRIP COST             ' + money(c.total));
    if (c.parcelRevenue > 0) lines.push('  less parcels          −' + money(c.parcelRevenue));
    lines.push('  Seats must bring      ' + money(c.toCover));
    lines.push('');
    lines.push('  Cost per seat         ' + money(c.perSeat));
    lines.push('  Asking fare           ' + money(c.i.fare));
    lines.push('  Break-even at that fare: ' + (isFinite(c.beSeats) ? c.beSeats : '—') + ' of ' + c.units + ' seats');
    lines.push('');
    lines.push('WHO PAYS WHAT (' + chosen.name + ')');
    people.forEach(function (p, idx) {
      lines.push('  ' + padRight(p.name, 14) + padRight(String(p.km) + ' km', 9) + money(chosen.amounts[idx] || 0) +
        '  ' + (p.paid ? 'paid' : 'owing') + '  ' + p.boardName + ' → ' + p.alightName);
    });
    lines.push('');
    lines.push('Arrive: ' + (window.__riderpoolEta || 'see the ETA chain above') + ' · check-in window ' + safety.checkin + ' min either side');
    lines.push('Anything that changes — a seat, a stop, the fuel price — changes the code. Quote the code, not the screenshot.');
    kit.$('#card-out').textContent = lines.join('\n');
    return code;
  }

  function padRight(text, width) {
    var s = String(text).slice(0, width);
    while (s.length < width) s += ' ';
    return s;
  }

  function paintChecks() {
    var saved = stores.checks.read() || {};
    var d = 0, r = 0;
    for (var i = 1; i <= 10; i++) {
      var box = el('d' + i);
      if (!box) continue;
      box.checked = !!saved['d' + i];
      if (box.checked) d++;
    }
    for (var j = 1; j <= 6; j++) {
      var q = el('q' + j);
      if (!q) continue;
      q.checked = !!saved['q' + j];
      if (q.checked) r++;
    }
    put('dcheck-score', d + ' / 10');
    put('rcheck-score', r + ' / 6');
    var dbar = el('dcheck-bar');
    var rbar = el('rcheck-bar');
    if (dbar) dbar.style.width = (d / 10 * 100) + '%';
    if (rbar) rbar.style.width = (r / 6 * 100) + '%';

    var box = el('dcheck-verdict');
    if (box) box.className = 'verdict' + (d >= 10 ? ' ok' : d >= 6 ? ' warn' : d >= 1 ? ' bad' : '');
    if (d === 10) { put('dcheck-title', 'Every line ticked'); put('dcheck-body', 'The car is as ready as you can make it on paper. Screenshot it, keep the paper, and go.'); }
    else if (d >= 6) { put('dcheck-title', d + ' of 10 checked'); put('dcheck-body', 'The unchecked ones are the ones that matter at 2am: belts, spare tyre, the cut-off hour, and the number someone at home holds.'); }
    else if (d >= 1) { put('dcheck-title', 'Barely started'); put('dcheck-body', 'Seat belts and the spare tyre come before the fare conversation. Work the list top to bottom.'); }
    else { put('dcheck-title', 'Nothing checked yet'); put('dcheck-body', 'Work the list before the car moves. It is your list, on your paper.'); }
  }

  function paintManifest(c, chosen) {
    var people = riders(c);
    kit.$('#manifest-body').innerHTML = people.map(function (p, idx) {
      var s = seats[idx] || {};
      var owed = Math.max(0, Math.round(chosen.amounts[idx] || 0));
      return '<tr' + (s.paid ? ' class="is-paid"' : '') + ' data-seat="' + idx + '">' +
        '<td class="num">' + (idx + 1) + '</td>' +
        '<td><input value="' + escapeHtml(s.name || '') + '" data-field="name" placeholder="name" aria-label="Rider name, seat ' + (idx + 1) + '"></td>' +
        '<td><input value="' + escapeHtml(s.phone || '') + '" data-field="phone" placeholder="phone" maxlength="20" aria-label="Phone for seat ' + (idx + 1) + '"></td>' +
        '<td>' + escapeHtml(s.position || '—') + '</td>' +
        '<td>' + escapeHtml(p.boardName) + '</td>' +
        '<td>' + escapeHtml(p.alightName) + '</td>' +
        '<td class="num">' + money(owed) + '</td>' +
        '<td><button class="pay-btn' + (s.paid ? ' is-paid' : '') + '" type="button" data-paid="' + idx + '">' + (s.paid ? 'Paid' : 'Not yet') + '</button></td>' +
        '<td><span class="sig-box" aria-hidden="true"></span></td></tr>';
    }).join('');
    var totalOwed = people.reduce(function (a, p, idx) { return a + Math.max(0, Math.round(chosen.amounts[idx] || 0)); }, 0);
    var paidSoFar = people.reduce(function (a, p, idx) { return a + (p.paid ? Math.max(0, Math.round(chosen.amounts[idx] || 0)) : 0); }, 0);
    kit.$('#manifest-foot').innerHTML = '<tr><td></td><td colspan="5">' + people.length + ' seats · ' + escapeHtml(c.i.from) + ' → ' + escapeHtml(c.i.to) +
      ' · ' + (window.__riderpoolCode || '') + '</td><td class="num">' + money(totalOwed) + '</td>' +
      '<td class="num">' + money(paidSoFar) + '</td><td></td></tr>';
    put('manifest-note', totalOwed - paidSoFar > 0
      ? money(totalOwed - paidSoFar) + ' still owing across ' + people.filter(function (p) { return !p.paid; }).length + ' seats. Print this sheet; the signature box is the receipt.'
      : 'Every seat on this manifest is marked paid.');
  }

  function paintPrintSheet(c, chosen, code) {
    var people = riders(c);
    var sheet = el('print-sheet');
    if (!sheet) return;
    var rows = people.map(function (p, idx) {
      return '<tr><td>' + (idx + 1) + '</td><td>' + escapeHtml(p.name) + '</td><td>' + escapeHtml(p.position) + '</td>' +
        '<td>' + escapeHtml(p.boardName) + '</td><td>' + escapeHtml(p.alightName) + '</td><td>' + p.km + '</td>' +
        '<td>' + money(chosen.amounts[idx] || 0) + '</td><td>' + (p.paid ? 'paid' : 'owing') + '</td><td><span class="sig-line"></span></td></tr>';
    }).join('');
    sheet.innerHTML = '<div class="sheet-head"><div><h3>RiderPool trip card · ' + code + '</h3>' +
      '<div>' + escapeHtml(c.i.from) + ' → ' + escapeHtml(c.i.to) + ' · ' + kit.dateLabel(c.i.date) + ' · window opens ' + c.i.time + ' · ' + escapeHtml(c.i.clsName) + '</div></div>' +
      '<div class="tiny">Pick up: ' + escapeHtml(c.i.pickup) + '<br>Drop: ' + escapeHtml(c.i.drop) + '</div></div>' +
      '<p class="tiny">Trip cost ' + money(c.total) + ' · seats must bring ' + money(c.toCover) + ' · cost per seat ' + money(c.perSeat) +
      ' · asking ' + money(c.i.fare) + ' · split by ' + chosen.name + '</p>' +
      '<table><thead><tr><th>#</th><th>Rider</th><th>Position</th><th>Pickup</th><th>Drop-off</th><th>km</th><th>Owes</th><th>Status</th><th>Signature</th></tr></thead><tbody>' +
      rows + '</tbody></table><div class="sheet-foot">Generated in the browser by RiderPool · figures are estimates, not a fare ruling · nothing here is a booking or a payment.</div>';
  }

  /* ---------- section 07: the ledger ---------- */

  function ledger() { var l = stores.ledger.read(); return Array.isArray(l) ? l : []; }

  function tripSnapshot(c, chosen, code) {
    var people = riders(c);
    return {
      id: kit.uid('trip'), code: code, at: Date.now(), date: c.i.date,
      from: c.i.from, to: c.i.to, route: c.i.from + ' → ' + c.i.to,
      cls: c.i.clsName, km: Math.round(c.dist), units: c.units, sold: c.i.sold,
      fare: c.i.fare, cost: Math.round(c.total), toCover: Math.round(c.toCover),
      fuel: Math.round(c.fuel), litres: round(c.litres), perSeat: Math.round(c.perSeat),
      method: chosen.name,
      seats: people.map(function (p, idx) {
        return {
          name: p.name, phone: (seats[idx] || {}).phone || '', position: p.position,
          board: p.boardName, alight: p.alightName, km: p.km,
          owed: Math.max(0, Math.round(chosen.amounts[idx] || 0)), paid: !!p.paid
        };
      })
    };
  }

  function chosenFor(c) {
    var state = window.__riderpoolState || {};
    return state.chosen || engine.bestMethod(engine.splitMethods(c, engine.riders(c)));
  }

  function saveTrip() {
    var c = engine.costTrip(engine.tripInput());
    var chosen = chosenFor(c);
    var list = ledger();
    var code = window.__riderpoolCode || engine.tripCode(c);
    var row = tripSnapshot(c, chosen, code);
    var dupe = list.filter(function (t) { return t.code === code && t.date === c.i.date; })[0];
    if (dupe) {
      row.id = dupe.id;
      list[list.indexOf(dupe)] = row;
      stores.ledger.write(list);
      paintLedger(row.id);
      kit.toast('Trip ' + code + ' updated in the ledger');
      return;
    }
    list.push(row);
    if (!stores.ledger.write(list)) { kit.toast('This browser blocked saving — export the CSV instead'); return; }
    paintLedger(row.id);
    kit.toast('Saved to the ledger as ' + code);
  }

  function monthKey(dateStr) { return String(dateStr || '').slice(0, 7); }

  function paintLedger(newId) {
    var list = ledger();
    var select = el('lg-month');
    var months = {};
    list.forEach(function (t) { if (t.date) months[monthKey(t.date)] = 1; });
    var keys = Object.keys(months).sort().reverse();
    var current = select && select.value ? select.value : 'all';
    if (select) {
      select.innerHTML = '<option value="all">All months</option>' + keys.map(function (m) {
        return '<option value="' + m + '">' + m + '</option>';
      }).join('');
      select.value = months[current] ? current : 'all';
    }
    var filter = select ? select.value : 'all';
    var view = filter === 'all' ? list : list.filter(function (t) { return monthKey(t.date) === filter; });

    put('lg-count', list.length + (list.length === 1 ? ' trip saved' : ' trips saved') +
      (filter !== 'all' ? ' · showing ' + view.length + ' in ' + filter : ''));
    var empty = el('ledger-empty');
    if (empty) empty.hidden = view.length > 0;

    kit.$('#ledger-body').innerHTML = view.map(function (t, idx) {
      var paid = t.seats.reduce(function (a, s) { return a + (s.paid ? s.owed : 0); }, 0);
      var owed = t.seats.reduce(function (a, s) { return a + (s.paid ? 0 : s.owed); }, 0);
      var kids = t.seats.map(function (s, si) {
        return '<tr><td>' + escapeHtml(s.name || ('Seat ' + (si + 1))) + '</td><td>' + escapeHtml(s.position || '') + '</td>' +
          '<td>' + escapeHtml(s.board || '') + ' → ' + escapeHtml(s.alight || '') + '</td><td>' + s.km + ' km</td>' +
          '<td>' + money(s.owed) + '</td><td><button class="pay-btn' + (s.paid ? ' is-paid' : '') + '" type="button" data-tpaid="' +
          escapeHtml(t.id) + '" data-tseat="' + si + '">' + (s.paid ? 'Paid' : 'Not yet') + '</button></td></tr>';
      }).join('');
      return '<tr' + (t.id === newId ? ' class="is-new"' : '') + ' data-trip="' + escapeHtml(t.id) + '">' +
        '<td>' + kit.dateLabel(t.date) + '</td>' +
        '<td><button class="ledger-toggle" type="button" data-toggle="' + escapeHtml(t.id) + '">' + escapeHtml(t.route) + '</button></td>' +
        '<td class="mono">' + escapeHtml(t.code || '') + '</td>' +
        '<td class="num">' + t.seats.length + '</td>' +
        '<td class="num">' + money(t.fare) + '</td>' +
        '<td class="num">' + money(t.cost) + '</td>' +
        '<td class="num">' + money(paid) + '</td>' +
        '<td class="num">' + (owed > 0 ? '<span class="badge badge-warn"> ' + money(owed) + '</span>' : '<span class="badge badge-ok">settled</span>') + '</td>' +
        '<td><button class="row-del" type="button" data-deltrip="' + escapeHtml(t.id) + '" aria-label="Delete trip ' + escapeHtml(t.code || '') + '">×</button></td></tr>' +
        '<tr class="kids" data-kids="' + escapeHtml(t.id) + '" hidden><td colspan="9"><table class="kid-table"><thead><tr>' +
        '<th>Rider</th><th>Position</th><th>Leg</th><th>km</th><th>Owes</th><th>Status</th></tr></thead><tbody>' + kids + '</tbody></table></td></tr>';
    }).join('');

    var totals = view.reduce(function (a, t) {
      var paid = t.seats.reduce(function (x, s) { return x + (s.paid ? s.owed : 0); }, 0);
      var km = t.seats.reduce(function (x, s) { return x + (s.paid ? s.km : 0); }, 0);
      return { paid: a.paid + paid, owed: a.owed + (t.toCover - paid), fuel: a.fuel + (t.fuel || 0), km: a.km + km, cost: a.cost + (t.cost || 0), n: a.n + 1 };
    }, { paid: 0, owed: 0, fuel: 0, km: 0, cost: 0, n: 0 });

    fill('lg-trips', String(totals.n));
    fill('lg-paid', money(totals.paid));
    fill('lg-owed', money(Math.max(0, totals.owed)));
    fill('lg-fuel', money(totals.fuel));
    fill('lg-net', totals.n ? signed((totals.paid - totals.cost) / totals.n) : '—');
    fill('lg-perkm', totals.km ? signed((totals.paid - totals.cost) / totals.km, 2) : '—');

    kit.$('#ledger-foot').innerHTML = view.length
      ? '<tr><td>Total in view</td><td></td><td></td><td class="num">' + view.reduce(function (a, t) { return a + t.seats.length; }, 0) +
      '</td><td class="num"></td><td class="num">' + money(totals.cost) + '</td><td class="num">' + money(totals.paid) +
      '</td><td class="num">' + money(Math.max(0, totals.owed)) + '</td><td></td></tr>'
      : '';
  }

  function ledgerCsv() {
    var list = ledger();
    if (!list.length) { kit.toast('Nothing saved yet'); return; }
    var rows = [['Date', 'Code', 'Route', 'Vehicle', 'km', 'Seats', 'Asking fare', 'Trip cost', 'Cost per seat', 'Rider', 'Position', 'Leg', 'Rider km', 'Owes', 'Paid']];
    list.forEach(function (t) {
      t.seats.forEach(function (s) {
        rows.push([t.date, t.code, t.route, t.cls, t.km, t.seats.length, t.fare, t.cost, t.perSeat,
          s.name || '', s.position || '', (s.board || '') + ' → ' + (s.alight || ''), s.km, s.owed, s.paid ? 'paid' : 'owing']);
      });
    });
    kit.csv(rows, 'riderpool-ledger-' + isoToday() + '.csv');
  }

  /* ---------- my travel (passenger log) ---------- */

  function travel() { var l = stores.travel.read(); return Array.isArray(l) ? l : []; }

  function addTravel() {
    var entry = {
      id: kit.uid('ride'), date: val('v-date') || isoToday(), route: val('v-route', 'a corridor'),
      fare: numVal('v-fare', 0), km: numVal('v-km', 0), mode: val('v-mode', 'Inter-city bus'),
      luggage: numVal('v-luggage', 0)
    };
    var err = el('tv-error');
    if (entry.fare <= 0 || entry.km <= 0) {
      if (err) err.textContent = 'Put in what you paid and how far you rode.';
      return;
    }
    if (err) err.textContent = '';
    var list = travel();
    list.push(entry);
    if (!stores.travel.write(list)) { kit.toast('This browser blocked saving'); return; }
    paintTravel(entry.id);
    kit.toast('Logged ' + entry.route);
  }

  function paintTravel(newId) {
    var list = travel();
    var rows = list.map(function (e) { return { e: e, per: (e.fare + (e.luggage || 0)) / Math.max(e.km, 1) }; });
    var spent = rows.reduce(function (a, r) { return a + r.e.fare + (r.e.luggage || 0); }, 0);
    var km = rows.reduce(function (a, r) { return a + r.e.km; }, 0);
    var best = rows.slice().sort(function (a, b) { return a.per - b.per; })[0];

    put('tv-note', list.length + (list.length === 1 ? ' entry' : ' entries'));
    fill('tv-avg', rows.length ? kit.money(spent / Math.max(km, 1), 2) : '—');
    put('tv-sub', rows.length ? 'across ' + list.length + ' rides and ' + kit.amount(km) + ' km you paid ' + money(spent) : 'no entries yet');
    fill('tv-count', String(list.length));
    fill('tv-spent', money(spent));
    fill('tv-best', best ? kit.money(best.per, 2) : '—');
    kit.sparkline(el('tv-spark'), rows.map(function (r) { return round(r.per); }), { dot: '#dd9f3b' });

    kit.$('#tv-body').innerHTML = rows.map(function (r) {
      return '<tr' + (r.e.id === newId ? ' class="is-new"' : '') + '><td>' + kit.dateLabel(r.e.date) + '</td>' +
        '<td>' + escapeHtml(r.e.route) + '</td><td>' + escapeHtml(r.e.mode) + '</td>' +
        '<td class="num">' + money(r.e.fare) + '</td><td class="num">' + r.e.km + '</td>' +
        '<td class="num">' + kit.money(r.per, 2) + '</td>' +
        '<td>' + (r.e.luggage ? money(r.e.luggage) : '—') + '</td>' +
        '<td><button class="row-del" type="button" data-deltravel="' + escapeHtml(r.e.id) + '" aria-label="Delete this entry">×</button></td></tr>';
    }).join('');
    kit.$('#tv-foot').innerHTML = rows.length
      ? '<tr><td>Total</td><td></td><td></td><td class="num">' + money(spent) + '</td><td class="num">' + kit.amount(km) +
        '</td><td class="num">' + kit.money(spent / Math.max(km, 1), 2) + '</td><td></td><td></td></tr>'
      : '';

    var box = el('tv-verdict');
    if (!rows.length) {
      if (box) box.className = 'verdict';
      put('tv-title', 'Nothing logged yet');
      put('tv-body', 'Log two trips and this compares what you paid with what the seat genuinely costs.');
      return;
    }
    var c = engine.costTrip(engine.tripInput());
    var delta = (spent / Math.max(km, 1)) - c.perSeat / Math.max(c.dist, 1) * 1;
    var yourRate = spent / Math.max(km, 1);
    var seatRate = c.perSeat / Math.max(c.dist, 1);
    if (box) box.className = 'verdict' + (yourRate > seatRate * 1.25 ? ' warn' : yourRate < seatRate * 0.8 ? ' ok' : '');
    put('tv-title', 'You pay ' + kit.money(yourRate, 2) + ' a kilometre');
    put('tv-body', 'The seat you are pricing above costs ' + kit.money(seatRate, 2) + ' a kilometre to run (' + money(c.perSeat) +
      ' over ' + Math.round(c.dist) + ' km). ' +
      (yourRate > seatRate * 1.15
        ? 'You are paying ' + money((yourRate - seatRate) * 100) + ' more per 100 km than the seat costs to provide — that is the park margin, or an empty car you are filling.'
        : yourRate < seatRate * 0.85
          ? 'You are paying under cost. Someone is absorbing it — usually the driver, until the fare rises.'
          : 'You are close to cost. That is what a fair corridor fare looks like.'));
  }

  /* ---------- section 08: sample board ---------- */

  function paintBoard() {
    var corridor = val('bd-corridor', 'All corridors');
    var day = val('bd-day', 'Today');
    var sort = val('bd-sort', 'fare-asc');
    var price = (nairaPerKmTable()[0] || { petrol: 1650 }).petrol;

    var rows = SAMPLE_TRIPS.map(function (t) {
      var cor = corridorByName(t.corridor) || corridors[0];
      var c = engine.costTrip({
        corridor: t.corridor, clsName: t.cls, from: t.corridor.split(' → ')[0], to: t.corridor.split(' → ')[1],
        km: cor ? cor.km : 200, hours: cor ? cor.hours : 4, seats: classByName(t.cls).seats, sold: classByName(t.cls).seats,
        date: isoToday(), time: t.departs, pickup: '', drop: '', price: price,
        kml: classByName(t.cls).kml, wear: classByName(t.cls).wear, tolls: classByName(t.cls).tolls,
        levy: classByName(t.cls).levy, driver: Math.round(classByName(t.cls).seats * 2200), fare: t.fare,
        buffer: 10, recover: 0, legs: 1, night: false, lugseat: false, parcelCounts: [0, 0, 0, 0]
      });
      return {
        t: t, km: Math.round(c.dist), perSeat: c.perSeat, perKm: t.fare / Math.max(c.dist, 1),
        margin: t.fare - c.perSeat, cost: c.total
      };
    }).filter(function (r) {
      return (corridor === 'All corridors' || r.t.corridor === corridor) && r.t.day === day;
    });

    var byPerKm = rows.slice().sort(function (a, b) { return a.perKm - b.perKm; });
    var bestPerKm = byPerKm.length ? byPerKm[0].t : null;

    rows.sort(function (a, b) {
      if (sort === 'fare-desc') return b.t.fare - a.t.fare;
      if (sort === 'seats') return b.t.left - a.t.left;
      if (sort === 'value') return a.perKm - b.perKm;
      return a.t.fare - b.t.fare;
    });

    put('bd-count', rows.length + ' sample rows on ' + (corridor === 'All corridors' ? 'every corridor' : escapeHtml(corridor)) +
      ' · ' + day + ' · none of these trips exist');

    kit.$('#board-body').innerHTML = rows.length ? rows.map(function (r) {
      var loss = r.margin < 0;
      var badge = loss ? '<span class="badge badge-loss">below cost</span>'
        : r.margin > r.perSeat * 0.35 ? '<span class="badge badge-cheap">good value</span>'
          : '<span class="badge badge-warn">thin margin</span>';
      var isBest = bestPerKm && bestPerKm.corridor === r.t.corridor && bestPerKm.departs === r.t.departs && bestPerKm.fare === r.t.fare;
      return '<tr' + (isBest ? ' class="is-best"' : '') + '><td class="mono">' + r.t.departs + ' · ' + escapeHtml(r.t.day) + '</td>' +
        '<td>' + escapeHtml(r.t.corridor) + '</td><td>' + escapeHtml(r.t.cls) + '</td>' +
        '<td class="num">' + r.t.left + '</td><td class="num">' + money(r.t.fare) + '</td>' +
        '<td class="num">' + r.km + '</td><td class="num">' + kit.money(r.perKm, 2) + '</td>' +
        '<td class="num">' + money(r.perSeat) + '</td><td>' + badge + '</td>' +
        '<td><button class="ledger-toggle" type="button" data-join="' + escapeHtml(r.t.corridor + '|' + r.t.departs) + '">Copy into my ledger</button></td></tr>';
    }).join('') : '<tr><td colspan="10" class="tiny">No sample rows on that corridor and day. Try All corridors.</td></tr>';
  }

  function joinSample(key) {
    var parts = String(key).split('|');
    var t = SAMPLE_TRIPS.filter(function (x) { return x.corridor === parts[0] && x.departs === parts[1]; })[0];
    if (!t) return;
    var cor = corridorByName(t.corridor) || corridors[0];
    var cls = classByName(t.cls);
    var c = engine.costTrip({
      corridor: t.corridor, clsName: t.cls, from: t.corridor.split(' → ')[0], to: t.corridor.split(' → ')[1],
      km: cor.km, hours: cor.hours, seats: cls.seats, sold: cls.seats, date: isoToday(), time: t.departs,
      pickup: 'to be agreed', drop: 'to be agreed', price: (nairaPerKmTable()[0] || { petrol: 1650 }).petrol,
      kml: cls.kml, wear: cls.wear, tolls: cls.tolls, levy: cls.levy, driver: Math.round(cls.seats * 2200),
      fare: t.fare, buffer: 10, recover: 0, legs: 1, night: false, lugseat: false, parcelCounts: [0, 0, 0, 0]
    });
    var chosen = chosenFor(c);
    var list = ledger();
    var row = tripSnapshot(c, chosen, 'S-' + engine.tripCode(c).slice(0, 5));
    row.sample = true;
    row.seats = row.seats.map(function (s, i) {
      return { name: i < t.left ? 'Seat ' + (i + 1) : 'empty', phone: '', position: s.position, board: s.board, alight: s.alight, km: s.km, owed: s.owed, paid: false };
    });
    list.push(row);
    stores.ledger.write(list);
    paintLedger(row.id);
    kit.toast('Copied in as pending. It contacts nobody — there is nobody to contact.');
  }

  /* ---------- section 09: editable assumption tables ---------- */

  function paintAssumptions() {
    kit.$('#corridor-body').innerHTML = corridors.map(function (c, idx) {
      return '<tr data-row="' + idx + '" data-table="corridors">' +
        '<td><input class="wide" value="' + escapeHtml(c.name) + '" data-field="name" aria-label="Corridor ' + (idx + 1) + ' name"></td>' +
        '<td><input type="number" min="1" max="2000" step="5" value="' + c.km + '" data-field="km" aria-label="Kilometres"></td>' +
        '<td><input type="number" min="0.25" max="30" step="0.25" value="' + c.hours + '" data-field="hours" aria-label="Hours"></td></tr>';
    }).join('');

    kit.$('#class-body').innerHTML = classes.map(function (c, idx) {
      return '<tr data-row="' + idx + '" data-table="classes"><td>' + escapeHtml(c.name) + '</td>' +
        '<td><input type="number" min="1" max="40" step="1" value="' + c.seats + '" data-field="seats" aria-label="Seats for ' + escapeHtml(c.name) + '"></td>' +
        '<td><input type="number" min="1" max="60" step="0.5" value="' + c.kml + '" data-field="kml" aria-label="km per litre"></td>' +
        '<td><input type="number" min="0" max="2000" step="1" value="' + c.wear + '" data-field="wear" aria-label="Wear naira per km"></td>' +
        '<td><input type="number" min="0" max="200000" step="250" value="' + c.tolls + '" data-field="tolls" aria-label="Tolls"></td>' +
        '<td><input type="number" min="0" max="200000" step="250" value="' + c.levy + '" data-field="levy" aria-label="Levy"></td></tr>';
    }).join('');

    kit.$('#price-body').innerHTML = prices.map(function (p, idx) {
      return '<tr data-row="' + idx + '" data-table="prices"><td>' + escapeHtml(p.city) + '</td>' +
        '<td><input type="number" min="200" max="6000" step="10" value="' + p.petrol + '" data-field="petrol" aria-label="Petrol price in ' + escapeHtml(p.city) + '"></td>' +
        '<td><input type="number" min="200" max="6000" step="10" value="' + p.ago + '" data-field="ago" aria-label="AGO price in ' + escapeHtml(p.city) + '"></td></tr>';
    }).join('');

    kit.$('#parcel-rate-body').innerHTML = parcels.map(function (p, idx) {
      return '<tr data-row="' + idx + '" data-table="parcels"><td>' + escapeHtml(p.label) + '</td>' +
        '<td><input type="number" min="0" max="50000" step="100" value="' + p.rate + '" data-field="rate" aria-label="Rate for ' + escapeHtml(p.label) + '"></td></tr>';
    }).join('');

    var adjRows = [
      { k: 'front', label: 'Front row premium', unit: '%' },
      { k: 'last', label: 'Last row discount', unit: '%' },
      { k: 'child', label: 'Child on a lap', unit: '%' },
      { k: 'student', label: 'Student', unit: '%' },
      { k: 'night', label: 'Night surcharge', unit: '%' },
      { k: 'lug', label: 'Luggage sold as a seat', unit: '₦' },
      { k: 'detour', label: 'Detour charge', unit: '₦/km/seat' },
      { k: 'detourKm', label: 'Typical diversion', unit: 'km' }
    ];
    kit.$('#adj-body').innerHTML = adjRows.map(function (r) {
      return '<tr data-adjrow="' + r.k + '" data-table="adj"><td>' + r.label + '</td>' +
        '<td><input type="number" min="-100" max="50000" step="1" value="' + adj[r.k] + '" aria-label="' + r.label + '"></td>' +
        '<td>' + r.unit + '</td></tr>';
    }).join('');

    var asofInput = el('rp-asof');
    if (asofInput) asofInput.value = asof;
    if (el('s-checkin')) el('s-checkin').value = safety.checkin;
    if (el('s-minspeed')) el('s-minspeed').value = safety.minNet;
  }

  /* ---------- worked example ---------- */

  function paintWorked(c, chosen) {
    var steps = [
      'Corridor <b>' + Math.round(c.i.km) + ' km</b> in a ' + escapeHtml(c.i.clsName) + ' at ' + c.i.kml + ' km/L burns <b>' + kit.amount(c.litres, 1) + ' litres</b>, and at ₦' + kit.amount(c.i.price) + ' a litre that is <b>' + money(c.fuel) + '</b>.',
      'Wear is the line everybody forgets: ' + Math.round(c.dist) + ' km × ₦' + kit.amount(c.i.wear) + ' = <b>' + money(c.wear) + '</b> of tyres, oil, servicing and repairs.',
      'Tolls ₦' + kit.amount(c.i.tolls) + ' and park levy ₦' + kit.amount(c.i.levy) + ' come to <b>' + money(c.extras) + '</b>' + (c.i.legs === 2 ? ' across both legs' : '') + '. The driver takes <b>' + money(c.cut) + '</b>' + (c.nightBonus ? ', night bonus included' : '') + '.',
      'True cost of the trip: <b>' + money(c.total) + '</b>. Parcels and luggage space bring <b>' + money(c.parcelRevenue) + '</b>, so the seats must carry <b>' + money(c.toCover) + '</b>.',
      'Spread over ' + c.units + ' seats that is <b>' + money(c.perSeat) + ' a seat</b> — ' + kit.money(c.perSeatKm, 2) + ' per seat-kilometre.',
      'At the asking fare of <b>' + money(c.i.fare) + '</b> you break even at <b>' + (isFinite(c.beSeats) ? c.beSeats : '—') + ' of ' + c.units + ' seats</b>. Selling ' + c.i.sold + ' leaves <b>' + (c.profitFull >= 0 ? '+' : '−') + money(Math.abs(c.profitFull)) + '</b>.',
      'The detour to ' + escapeHtml((stops.filter(function (s) { return s.detourKm > 0; })[0] || { name: 'a pickup' }).name) + ' is ' + adj.detourKm + ' km off the line: <b>' + money(adj.detourKm * (c.i.price / c.i.kml + c.i.wear)) + '</b> of fuel and wear, plus about ' + Math.round(adj.detourKm / 40 * 60) + ' minutes everyone shares.',
      'Split by <b>' + escapeHtml(chosen.name) + '</b> the car collects <b>' + money(chosen.collected) + '</b>, ' + (Math.abs(chosen.gap) < 2 ? 'exactly the cost' : (chosen.gap > 0 ? 'over by ' : 'short by ') + money(Math.abs(chosen.gap))) + ', with an average drift of <b>' + money(chosen.avg) + '</b> from cost causation.',
      'The code <b>' + escapeHtml(window.__riderpoolCode || engine.tripCode(c)) + '</b> comes from these inputs. Change a seat, a stop or the fuel price and the code changes with them — which is how two people know they are pricing the same trip.'
    ];
    kit.$('#worked').innerHTML = steps.map(function (s) { return '<li>' + s + '</li>'; }).join('');
  }

  /* ---------- bindings ---------- */

  function bind() {
    var recompute = debounce(paintAll, 90);

    ['trip-form', 'adj-form'].forEach(function (formId) {
      var form = el(formId);
      if (!form) return;
      form.addEventListener('input', recompute);
      form.addEventListener('change', recompute);
      form.addEventListener('submit', function (e) { e.preventDefault(); });
    });
    ['r-corridor', 'r-class', 'seq-speed', 'seq-stopmin', 'bd-corridor', 'bd-day', 'bd-sort'].forEach(function (id) {
      var node = el(id);
      if (!node) return;
      node.addEventListener('change', function () {
        if (id === 'bd-corridor' || id === 'bd-day' || id === 'bd-sort') return paintBoard();
        if (id === 'r-corridor') applyCorridor();
        if (id === 'r-class') applyClass();
        paintAll();
      });
    });
    ['seq-speed', 'seq-stopmin'].forEach(function (id) {
      var node = el(id);
      if (node) node.addEventListener('input', debounce(function () {
        var c = engine.costTrip(engine.tripInput());
        paintSequence(c, chosenFor(c));
      }, 80));
    });

    if (el('r-price-from')) el('r-price-from').addEventListener('change', function () {
      var city = val('r-price-from').split(' · ')[0];
      var row = prices.filter(function (p) { return p.city === city; })[0];
      if (row && el('r-price')) { el('r-price').value = row.petrol; paintAll(); }
    });

    if (el('trip-reset')) el('trip-reset').addEventListener('click', function () {
      setValues({
        'r-corridor': 'Benin City → Lagos', 'r-class': 'Sienna / Hiace', 'r-from': 'Benin City', 'r-to': 'Lagos',
        'r-km': 245, 'r-hours': 4, 'r-seats': 6, 'r-sold': 5, 'r-pickup': 'Oak Avenue, Benin', 'r-drop': 'CMS / Ikeja, Lagos',
        'r-price': 1650, 'r-kml': 9, 'r-wear': 49, 'r-tolls': 5000, 'r-levy': 3000, 'r-driver': 25000, 'r-fare': 9000,
        'r-buffer': 10, 'r-recover': 0, 'r-p1': 0, 'r-p2': 0, 'r-p3': 0, 'r-p4': 0
      });
      ['r-return', 'r-night', 'r-lugseat'].forEach(function (id) { if (el(id)) el(id).checked = false; });
      applyCorridor();
      paintAll();
      kit.toast('Back to the Benin → Lagos example');
    });

    kit.on(document, 'input', '#stops-body input', function (e, node) {
      var idx = Number(node.closest('tr').dataset.stop);
      var field = node.dataset.field;
      stops[idx][field] = field === 'name' ? node.value : kit.num(node.value);
      stores.stops.write(stops);
      debounce(paintAll, 200)();
    });
    kit.on(document, 'click', '[data-delstop]', function (e, node) {
      if (stops.length <= 2) { kit.toast('Keep at least a start and a finish'); return; }
      var idx = Number(node.dataset.delstop);
      stops.splice(idx, 1);
      seats.forEach(function (s) {
        if (s.boards > idx) s.boards--;
        if (s.alights > idx) s.alights--;
        s.boards = kit.clamp(s.boards, 0, stops.length - 1);
        s.alights = kit.clamp(s.alights, 0, stops.length - 1);
      });
      stores.stops.write(stops);
      stores.seats.write(seats);
      paintStops();
      paintAll();
    });
    if (el('st-add')) el('st-add').addEventListener('click', function () {
      var lastKm = stops.length ? stops[stops.length - 1].km : 0;
      stops.push({ name: 'New stop', km: Math.round(lastKm * 0.6), detourKm: 0, detourMin: 4 });
      stops.sort(function (a, b) { return a.km - b.km; });
      stores.stops.write(stops);
      seats.forEach(function (s) { s.alights = kit.clamp(s.alights, 0, stops.length - 1); });
      paintStops();
      paintAll();
      kit.toast('Stop added — rename it and set the kilometres');
    });
    if (el('st-reset')) el('st-reset').addEventListener('click', function () {
      stops = DEFAULT_STOPS.map(clone);
      stores.stops.write(stops);
      paintStops();
      paintAll();
      kit.toast('Sample stops restored');
    });

    kit.on(document, 'input', '#seats-body input', function (e, node) {
      var idx = Number(node.closest('tr').dataset.seat);
      seats[idx][node.dataset.field] = node.value;
      stores.seats.write(seats);
      debounce(paintSplitAndCard, 200)();
    });
    kit.on(document, 'change', '#seats-body select', function (e, node) {
      var idx = Number(node.closest('tr').dataset.seat);
      seats[idx][node.dataset.field] = node.dataset.field === 'boards' || node.dataset.field === 'alights' ? Number(node.value) : node.value;
      stores.seats.write(seats);
      paintAll();
    });

    kit.on(document, 'click', '#manifest-body [data-paid]', function (e, node) {
      var idx = Number(node.dataset.paid);
      seats[idx].paid = !seats[idx].paid;
      stores.seats.write(seats);
      var c = engine.costTrip(engine.tripInput());
      var chosen = chosenFor(c);
      paintManifest(c, chosen);
      paintCard(c, chosen);
    });

    ['d1', 'd2', 'd3', 'd4', 'd5', 'd6', 'd7', 'd8', 'd9', 'd10', 'q1', 'q2', 'q3', 'q4', 'q5', 'q6'].forEach(function (id) {
      var node = el(id);
      if (!node) return;
      node.addEventListener('change', function () {
        var saved = stores.checks.read() || {};
        saved[id] = node.checked;
        stores.checks.write(saved);
        paintChecks();
      });
    });

    if (el('card-copy')) el('card-copy').addEventListener('click', function () {
      var text = (el('card-out') || {}).textContent || '';
      if (navigator.clipboard && navigator.clipboard.writeText) {
        navigator.clipboard.writeText(text).then(function () { kit.toast('Trip card copied — paste it into the group'); },
          function () { kit.toast('Select the card text and copy it yourself'); });
      } else { kit.toast('Select the card text and copy it yourself'); }
    });
    if (el('card-print')) el('card-print').addEventListener('click', function () { printWith('print-manifest'); });
    if (el('manifest-print')) el('manifest-print').addEventListener('click', function () { printWith('print-manifest'); });
    if (el('manifest-csv')) el('manifest-csv').addEventListener('click', function () {
      var c = engine.costTrip(engine.tripInput());
      var chosen = chosenFor(c);
      var people = engine.riders(c);
      var rows = [['Seat', 'Rider', 'Phone', 'Position', 'Pickup', 'Drop-off', 'km', 'Owes', 'Status']];
      people.forEach(function (p, idx) {
        rows.push([idx + 1, p.name, (seats[idx] || {}).phone || '', p.position, p.boardName, p.alightName, p.km,
          Math.max(0, Math.round(chosen.amounts[idx] || 0)), p.paid ? 'paid' : 'owing']);
      });
      kit.csv(rows, 'riderpool-manifest-' + (window.__riderpoolCode || 'trip') + '.csv');
    });
    if (el('ledger-save')) el('ledger-save').addEventListener('click', saveTrip);
    if (el('lg-save')) el('lg-save').addEventListener('click', saveTrip);
    if (el('lg-month')) el('lg-month').addEventListener('change', function () { paintLedger(); });
    if (el('lg-csv')) el('lg-csv').addEventListener('click', ledgerCsv);
    if (el('lg-print')) el('lg-print').addEventListener('click', function () { printWith('print-ledger'); });
    if (el('lg-clear')) el('lg-clear').addEventListener('click', function () {
      if (!ledger().length) { kit.toast('The ledger is already empty'); return; }
      if (!confirm('Delete every saved trip on this device? Export the CSV first if you need them.')) return;
      stores.ledger.write([]);
      paintLedger();
      kit.toast('Ledger cleared');
    });
    kit.on(document, 'click', '[data-deltrip]', function (e, node) {
      var list = ledger().filter(function (t) { return t.id !== node.dataset.deltrip; });
      stores.ledger.write(list);
      paintLedger();
      kit.toast('Trip removed');
    });
    kit.on(document, 'click', '[data-toggle]', function (e, node) {
      var kids = kit.$('#ledger-body tr[data-kids="' + node.dataset.toggle + '"]');
      if (kids) kids.hidden = !kids.hidden;
    });
    kit.on(document, 'click', '[data-tpaid]', function (e, node) {
      var tripId = node.dataset.tpaid, seatIdx = Number(node.dataset.tseat);
      var list = ledger();
      var trip = list.filter(function (t) { return t.id === tripId; })[0];
      if (!trip || !trip.seats[seatIdx]) return;
      trip.seats[seatIdx].paid = !trip.seats[seatIdx].paid;
      stores.ledger.write(list);
      paintLedger();
    });
    kit.on(document, 'click', '[data-join]', function (e, node) { joinSample(node.dataset.join); });

    if (el('tv-form')) el('tv-form').addEventListener('submit', function (e) { e.preventDefault(); addTravel(); });
    if (el('tv-add')) el('tv-add').addEventListener('click', function (e) { e.preventDefault(); addTravel(); });
    if (el('tv-csv')) el('tv-csv').addEventListener('click', function () {
      var list = travel();
      if (!list.length) { kit.toast('Log a ride first'); return; }
      kit.csv([['Date', 'Route', 'Mode', 'Paid', 'km', 'Naira per km', 'Luggage']].concat(list.map(function (e2) {
        return [e2.date, e2.route, e2.mode, e2.fare, e2.km, round((e2.fare + (e2.luggage || 0)) / Math.max(e2.km, 1)), e2.luggage || 0];
      })), 'riderpool-my-travel-' + isoToday() + '.csv');
    });
    if (el('tv-clear')) el('tv-clear').addEventListener('click', function () {
      if (!travel().length) { kit.toast('Nothing logged yet'); return; }
      if (!confirm('Clear your personal travel log from this browser?')) return;
      stores.travel.write([]);
      paintTravel();
      kit.toast('Travel log cleared');
    });
    kit.on(document, 'click', '[data-deltravel]', function (e, node) {
      var list = travel().filter(function (t) { return t.id !== node.dataset.deltravel; });
      stores.travel.write(list);
      paintTravel();
    });

    if (el('seq-reset')) el('seq-reset').addEventListener('click', function () {
      stops = DEFAULT_STOPS.map(clone);
      seats.forEach(function (s) { s.boards = 0; s.alights = stops.length - 1; });
      stores.stops.write(stops);
      stores.seats.write(seats);
      paintStops();
      paintAll();
      kit.toast('Sample sequence restored');
    });
    if (el('seq-optimize')) el('seq-optimize').addEventListener('click', function () {
      var order = stops.map(function (s, i) { return i; })
        .sort(function (a, b) { return stops[a].km - stops[b].km; });
      var remap = {};
      order.forEach(function (oldIdx, newIdx) { remap[oldIdx] = newIdx; });
      stops = order.map(function (i) { return clone(stops[i]); });
      seats.forEach(function (s) {
        s.boards = remap[s.boards] === undefined ? 0 : remap[s.boards];
        s.alights = remap[s.alights] === undefined ? stops.length - 1 : remap[s.alights];
      });
      stores.stops.write(stops);
      stores.seats.write(seats);
      paintStops();
      paintAll();
      kit.toast('Stops sorted into the order the car will do them');
    });

    var tableMap = { corridors: corridors, classes: classes, prices: prices, parcels: parcels };
    kit.on(document, 'input', '#corridor-body input, #class-body input, #price-body input, #parcel-rate-body input', function (e, node) {
      var row = node.closest('tr');
      var table = tableMap[row.dataset.table];
      var item = table[Number(row.dataset.row)];
      if (!item) return;
      var field = node.dataset.field;
      item[field] = field === 'name' ? node.value : kit.num(node.value);
      stores[row.dataset.table].write(table);
      if (field === 'name') paintSelects();
      paintAll();
    });
    kit.on(document, 'input', '#adj-body input', function (e, node) {
      var key = node.closest('tr').dataset.adjrow;
      adj[key] = kit.num(node.value);
      stores.adj.write(adj);
      ['a-' + key].forEach(function (id) { if (el(id)) el(id).value = adj[key]; });
      paintAll();
    });
    [['front', 'a-front'], ['last', 'a-last'], ['child', 'a-child'], ['student', 'a-student'],
    ['night', 'a-night'], ['lug', 'a-lug'], ['detour', 'a-detour'], ['detourKm', 'a-detour-km']].forEach(function (pair) {
      var node = el(pair[1]);
      if (node) node.addEventListener('input', function () {
        adj[pair[0]] = kit.num(node.value);
        stores.adj.write(adj);
        paintAll();
      });
    });
    ['s-checkin', 's-minspeed'].forEach(function (id) {
      var node = el(id);
      if (!node) return;
      node.addEventListener('input', function () {
        safety.checkin = kit.clamp(numVal('s-checkin', 45), 5, 180);
        safety.minNet = Math.max(0, numVal('s-minspeed', 0));
        stores.safety.write(safety);
        paintAll();
      });
    });
    if (el('rp-asof')) el('rp-asof').addEventListener('change', function () {
      asof = val('rp-asof');
      stores.asof.write(asof);
      kit.toast('Reference tables marked current as of ' + kit.dateLabel(asof));
    });
    [['reset-corridors', 'corridors', DEFAULT_CORRIDORS], ['reset-classes', 'classes', DEFAULT_CLASSES],
    ['reset-prices', 'prices', DEFAULT_PRICES], ['reset-parcels', 'parcels', DEFAULT_PARCELS]].forEach(function (spec) {
      var node = el(spec[0]);
      if (!node) return;
      node.addEventListener('click', function () {
        stores[spec[1]].clear();
        tableMap[spec[1]] = spec[2].map(clone);
        if (spec[1] === 'corridors') corridors = tableMap.corridors;
        if (spec[1] === 'classes') classes = tableMap.classes;
        if (spec[1] === 'prices') prices = tableMap.prices;
        if (spec[1] === 'parcels') parcels = tableMap.parcels;
        paintSelects();
        paintAssumptions();
        paintAll();
        kit.toast('Defaults restored');
      });
    });
    if (el('reset-adj')) el('reset-adj').addEventListener('click', function () {
      adj = Object.assign({}, DEFAULT_ADJ);
      stores.adj.write(adj);
      paintAssumptions();
      paintAll();
      kit.toast('Default adjustments restored');
    });
  }

  function paintSplitAndCard() {
    var c = engine.costTrip(engine.tripInput());
    var chosen = paintSplit(c);
    paintSeats(c);
    paintCard(c, chosen);
    paintManifest(c, chosen);
    paintPrintSheet(c, chosen, window.__riderpoolCode || '');
  }

  function applyCorridor() {
    var cor = corridorByName(val('r-corridor'));
    if (!cor) return;
    if (el('r-km')) el('r-km').value = cor.km;
    if (el('r-hours')) el('r-hours').value = cor.hours;
    var parts = String(cor.name).split(' → ');
    if (el('r-from') && parts[0]) el('r-from').value = parts[0];
    if (el('r-to') && parts[1]) el('r-to').value = parts[1];
    stops = DEFAULT_STOPS.map(function (s) { return { name: s.name, km: Math.round(s.km * cor.km / 245), detourKm: s.detourKm, detourMin: s.detourMin }; });
    stores.stops.write(stops);
    seats.forEach(function (s) { s.alights = kit.clamp(s.alights, 0, stops.length - 1); });
    paintStops();
  }

  function applyClass() {
    var cls = classByName(val('r-class'));
    if (!cls) return;
    if (el('r-kml')) el('r-kml').value = cls.kml;
    if (el('r-wear')) el('r-wear').value = cls.wear;
    if (el('r-tolls')) el('r-tolls').value = cls.tolls;
    if (el('r-levy')) el('r-levy').value = cls.levy;
    if (el('r-seats')) el('r-seats').value = cls.seats;
  }

  function setValues(map) {
    Object.keys(map).forEach(function (id) { if (el(id)) el(id).value = map[id]; });
  }

  function printWith(bodyClass) {
    document.body.classList.add(bodyClass);
    setTimeout(function () {
      kit.print();
      setTimeout(function () { document.body.classList.remove(bodyClass); }, 400);
    }, 60);
  }

  function debounce(fn, ms) {
    var timer;
    return function () {
      clearTimeout(timer);
      timer = setTimeout(fn, ms);
    };
  }

  /* PART-3 */

  /* ---------- boot ---------- */

  function paintAll() {
    var c = engine.costTrip(engine.tripInput());
    syncSeatsTo(c.units);
    paintTrip(c);
    paintPricing(c);
    paintSeats(c);
    var chosen = paintSplit(c);
    paintSequence(c, chosen);
    var code = paintCard(c, chosen);
    window.__riderpoolCode = code;
    paintManifest(c, chosen);
    paintPrintSheet(c, chosen, code);
    paintLedger();
    paintTravel();
    paintBoard();
    paintWorked(c, chosen);
    stores.trip.write({ units: c.units });
  }

  function syncSeatsTo(units) {
    while (seats.length < units) {
      var n = seats.length;
      seats.push({
        name: '', phone: '', position: POSITIONS[Math.min(n + 1, POSITIONS.length - 1)],
        type: 'Adult', boards: 0, alights: stops.length - 1, paid: false
      });
    }
    if (seats.length > units + 6) seats.length = units + 6;
    stores.seats.write(seats);
  }

  kit.boot({
    ready: function () {
      paintSelects();
      paintStops();
      paintChecks();
      paintAssumptions();
      bind();
      var savedTrip = stores.trip.read();
      if (savedTrip && savedTrip.date) el('r-date').value = savedTrip.date;
      else el('r-date').value = isoToday();
      paintAll();
    }
  });
}());
