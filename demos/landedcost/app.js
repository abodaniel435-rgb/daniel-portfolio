/* LandedCost — Nigerian import landed-cost engine.
   Every figure on this page is computed in the browser from the rates below,
   all of which are editable in section 10. Nothing is sent anywhere. */
(function () {
  'use strict';

  /* ---------------- domain defaults (editable, persisted) ---------------- */

  var LEVIES = [
    { key: 'surcharge', label: 'Import duty surcharge', base: '% of the import duty', val: 20, step: 0.5 },
    { key: 'etls', label: 'ECOWAS / ETLS levy', base: '% of the CIF value', val: 0.5, step: 0.05 },
    { key: 'ciss', label: 'CISS surveillance fee', base: '% of the CIF value', val: 1, step: 0.05 },
    { key: 'stamp', label: 'Stamp duty', base: '% of the CIF value', val: 0.5, step: 0.05 },
    { key: 'vat', label: 'VAT on the duty-inclusive value', base: '% of (CIF + duty + levies)', val: 7.5, step: 0.1 },
    { key: 'nimasa', label: 'NIMASA / Shipping Council levy', base: '% of the freight paid', val: 3, step: 0.1 }
  ];

  var CATS = [
    { key: 'raw', name: 'Raw materials & industrial inputs', duty: 0, excise: 0 },
    { key: 'mach', name: 'Machinery, pumps & spares', duty: 5, excise: 0 },
    { key: 'food', name: 'Food & processed essentials', duty: 5, excise: 0 },
    { key: 'elec', name: 'Electronics & phone accessories', duty: 10, excise: 0 },
    { key: 'garm', name: 'Garments, bags & footwear', duty: 20, excise: 0 },
    { key: 'hh', name: 'Household goods & plastics', duty: 20, excise: 0 },
    { key: 'cosm', name: 'Cosmetics, soaps & toiletries', duty: 20, excise: 5 },
    { key: 'bev', name: 'Beverages, spirits & tobacco', duty: 20, excise: 20 },
    { key: 'veh', name: 'Vehicles & motorcycle parts', duty: 20, excise: 5 },
    { key: 'build', name: 'Cement, steel & building materials', duty: 5, excise: 0 },
    { key: 'other', name: 'Anything else — check the HS line', duty: 10, excise: 0 }
  ];

  var CHARGES = [
    { key: 'lclRate', label: 'Sea LCL — per CBM', unit: '₦', val: 38000 },
    { key: 'fcl20', label: 'Sea FCL — 20ft container', unit: '₦', val: 1450000 },
    { key: 'fcl40', label: 'Sea FCL — 40ft high-cube', unit: '₦', val: 2300000 },
    { key: 'cbm20', label: 'Usable volume — 20ft', unit: 'CBM', val: 28 },
    { key: 'cbm40', label: 'Usable volume — 40ft HC', unit: 'CBM', val: 68 },
    { key: 'airX', label: 'Air express — per kg', unit: '₦', val: 14500 },
    { key: 'airE', label: 'Air economy — per kg', unit: '₦', val: 10500 },
    { key: 'airS', label: 'Sea-air — per kg', unit: '₦', val: 7200 },
    { key: 'volDiv', label: 'Air volumetric divisor', unit: 'kg/m³', val: 167 },
    { key: 'formM', label: 'Form M / MACIS application', unit: '₦', val: 12500 },
    { key: 'agent', label: 'Clearing agent professional fee', unit: '₦', val: 150000 },
    { key: 'soncap', label: 'SONCAP / product certificate', unit: '₦ per line', val: 75000 },
    { key: 'mson', label: 'MSON — vehicles & machinery', unit: '₦ each', val: 15000 },
    { key: 'thc20', label: 'Terminal handling — 20ft', unit: '₦ per box', val: 260000 },
    { key: 'thc40', label: 'Terminal handling — 40ft', unit: '₦ per box', val: 420000 },
    { key: 'dock', label: 'Delivery order / dock warrant', unit: '₦ per box', val: 45000 },
    { key: 'evac', label: 'Empty box evacuation deposit', unit: '₦ per box', val: 120000 },
    { key: 'nsc', label: 'Wharfage & council charges', unit: '₦ per box', val: 35000 },
    { key: 'cfs', label: 'LCL CFS / warehouse handling', unit: '₦ per CBM', val: 14000 },
    { key: 'airHnd', label: 'Airline & air terminal handling', unit: '₦ per 100 kg', val: 85000 },
    { key: 'dm1', label: 'Demurrage — days 8 to 14', unit: '₦/day/box', val: 18000 },
    { key: 'dm2', label: 'Demurrage — days 15 to 21', unit: '₦/day/box', val: 32000 },
    { key: 'dm3', label: 'Demurrage — days 22 to 28', unit: '₦/day/box', val: 48000 },
    { key: 'dm4', label: 'Demurrage — day 29 onward', unit: '₦/day/box', val: 62000 }
  ];

  var TRUCKS = [
    { key: 'van', name: 'Hiace / shared van (up to 3 CBM)', base: 60000, per: 130 },
    { key: 't3', name: '3-ton truck — intrastate', base: 120000, per: 210 },
    { key: 't7', name: '7-ton truck', base: 210000, per: 320 },
    { key: 't15', name: '15-ton / 20ft box truck', base: 340000, per: 460 },
    { key: 't30', name: '30-ton trailer / 40ft chassis', base: 520000, per: 640 },
    { key: 'cta', name: 'Container tractor with permit', base: 640000, per: 780 }
  ];

  var CURS = {
    USD: { sym: '$', rate: 1520 },
    CNY: { sym: '¥', rate: 212 },
    EUR: { sym: '€', rate: 1655 },
    GBP: { sym: '£', rate: 1965 },
    AED: { sym: 'Dhs', rate: 414 }
  };

  var INCOS = {
    EXW: 'EXW — you are buying at the factory door. Inland haulage to the origin port, origin terminal handling, ocean freight and insurance are all yours, so they all join the assessable value. Expect the CIF to sit well above the invoice.',
    FOB: 'FOB — the seller\u2019s obligation ends once the goods are on board. Your freight and insurance are added on top of the invoice to reach the CIF value customs assesses. This is the usual quotation for Chinese suppliers.',
    CIF: 'CIF — the invoice already contains freight and insurance, so they are NOT added again. The goods value in the invoice is the assessable value. Adding them twice inflates duty by 15\u201330%.',
    DDP: 'DDP — the seller delivers duty-paid and shows you one number. LandedCost unpacks it: it divides your price back through the duty, levy and VAT stack to estimate what is buried inside, then adds your own local costs.'
  };

  var DM_KEYS = ['dm1', 'dm2', 'dm3', 'dm4'];

  /* ---------------- persistence ---------------- */

  var rulesStore = kit.store('landedcost:rules', null);
  var shipsStore = kit.store('landedcost:shipments', []);
  var rules = rulesStore.read();
  var levies = normaliseRules(rules && rules.levies, LEVIES);
  var cats = normaliseRules(rules && rules.cats, CATS);
  var chgs = normaliseRules(rules && rules.charges, CHARGES);
  var last = {};
  var inco = 'FOB';
  var categoryLocked = true;

  function normaliseRules(saved, defaults) {
    if (!Array.isArray(saved) || saved.length !== defaults.length) {
      return defaults.map(function (d) { return Object.assign({}, d); });
    }
    return defaults.map(function (d, i) {
      var row = Object.assign({}, d);
      if (saved[i] && typeof saved[i].val === 'number') row.val = saved[i].val;
      if (saved[i] && typeof saved[i].duty === 'number') row.duty = saved[i].duty;
      if (saved[i] && typeof saved[i].excise === 'number') row.excise = saved[i].excise;
      return row;
    });
  }

  function saveRules() {
    rulesStore.write({ levies: levies, cats: cats, charges: chgs });
  }

  function levy(key) {
    for (var i = 0; i < levies.length; i++) if (levies[i].key === key) return levies[i].val;
    return 0;
  }
  function chg(key) {
    for (var i = 0; i < chgs.length; i++) if (chgs[i].key === key) return chgs[i].val;
    return 0;
  }
  function cat(key) {
    for (var i = 0; i < cats.length; i++) if (cats[i].key === key) return cats[i];
    return cats[3];
  }
  function truck(key) {
    for (var i = 0; i < TRUCKS.length; i++) if (TRUCKS[i].key === key) return TRUCKS[i];
    return TRUCKS[3];
  }

  /* ---------------- form reading ---------------- */

  var Q_IDS = ['quote-name', 'route', 'cur', 'rate', 'price-mode', 'goods', 'qty', 'cartons', 'upc',
    'cartonKg', 'cLength', 'cWidth', 'cHeight', 'freightMode', 'freightFx', 'insMode', 'insFx', 'insPct',
    'category', 'dutyRate', 'originInland', 'originThc', 'lines', 'vehicles'];
  var DM_IDS = ['dwell', 'freeDays', 'detDays', 'detRate', 'containers', 'financeRate'];
  var SE_IDS = ['truck', 'distance', 'porters', 'storageMonths', 'storageRate', 'breakage', 'margin',
    'market', 'daysToSell', 'creditDays'];
  var ALL_IDS = Q_IDS.concat(DM_IDS).concat(SE_IDS);
  var SWITCH_IDS = ['use-excise', 'use-surcharge', 'use-stamp', 'reclaim-vat'];

  function val(id) {
    var el = document.getElementById(id);
    return el ? el.value : '';
  }
  function n(id) { return kit.num(val(id)); }
  function check(id) {
    var el = document.getElementById(id);
    return !!(el && el.checked);
  }

  function readAll() {
    return {
      name: String(val('quote-name')).trim(),
      route: String(val('route')).trim(),
      cur: val('cur') || 'USD',
      rate: Math.max(0, n('rate')),
      priceMode: val('price-mode') || 'total',
      goods: Math.max(0, n('goods')),
      qty: kit.clamp(Math.round(n('qty')) || 1, 1, 200000),
      cartons: kit.clamp(Math.round(n('cartons')) || 1, 1, 20000),
      upc: kit.clamp(Math.round(n('upc')) || 1, 1, 20000),
      cartonKg: Math.max(0, n('cartonKg')),
      cLength: Math.max(1, n('cLength')),
      cWidth: Math.max(1, n('cWidth')),
      cHeight: Math.max(1, n('cHeight')),
      freightMode: val('freightMode') || 'manual',
      freightFx: Math.max(0, n('freightFx')),
      insMode: val('insMode') || 'manual',
      insFx: Math.max(0, n('insFx')),
      insPct: kit.clamp(n('insPct'), 0, 5),
      category: val('category') || 'elec',
      dutyRate: kit.clamp(n('dutyRate'), 0, 60),
      originInland: Math.max(0, n('originInland')),
      originThc: Math.max(0, n('originThc')),
      lines: kit.clamp(Math.round(n('lines')) || 1, 1, 50),
      vehicles: kit.clamp(Math.round(n('vehicles')) || 0, 0, 500),
      useExcise: check('use-excise'),
      useSurcharge: check('use-surcharge'),
      useStamp: check('use-stamp'),
      reclaimVat: check('reclaim-vat'),
      dwell: kit.clamp(Math.round(n('dwell')), 0, 120),
      freeDays: kit.clamp(Math.round(n('freeDays')), 0, 30),
      detDays: kit.clamp(Math.round(n('detDays')), 0, 90),
      detRate: Math.max(0, n('detRate')),
      containers: kit.clamp(Math.round(n('containers')) || 1, 1, 20),
      financeRate: kit.clamp(n('financeRate'), 0, 30),
      truck: val('truck') || 't15',
      distance: kit.clamp(n('distance'), 0, 1500),
      porters: Math.max(0, n('porters')),
      storageMonths: kit.clamp(n('storageMonths'), 0, 12),
      storageRate: Math.max(0, n('storageRate')),
      breakage: kit.clamp(n('breakage'), 0, 30),
      margin: kit.clamp(n('margin'), 0, 300),
      market: Math.max(0, n('market')),
      daysToSell: kit.clamp(Math.round(n('daysToSell')) || 1, 1, 365),
      creditDays: kit.clamp(Math.round(n('creditDays')), 0, 365),
      inco: inco
    };
  }

  /* ---------------- freight & volume ---------------- */

  function freightMenu(v, dims) {
    var boxes20 = Math.max(1, Math.ceil(dims.cbm / Math.max(1, chg('cbm20'))));
    var boxes40 = Math.max(1, Math.ceil(dims.cbm / Math.max(1, chg('cbm40'))));
    return {
      manual: { cost: v.freightFx * v.rate, note: 'The forwarder\u2019s own figure, converted at your rate.' },
      lcl: { cost: dims.cbm * chg('lclRate'), note: kit.amount(dims.cbm, 2) + ' CBM at ₦' + kit.amount(chg('lclRate')) + ' a CBM.', boxes: 0 },
      fcl20: { cost: boxes20 * chg('fcl20'), note: boxes20 + ' \u00d7 20ft at ₦' + kit.amount(chg('fcl20')) + ' each.', boxes: boxes20, size: '20ft' },
      fcl40: { cost: boxes40 * chg('fcl40'), note: boxes40 + ' \u00d7 40ft high-cube at ₦' + kit.amount(chg('fcl40')) + ' each.', boxes: boxes40, size: '40ft' },
      airx: { cost: dims.chargeKg * chg('airX'), note: kit.amount(dims.chargeKg, 0) + ' chargeable kg at ₦' + kit.amount(chg('airX')) + '.', boxes: 0 },
      aire: { cost: dims.chargeKg * chg('airE'), note: kit.amount(dims.chargeKg, 0) + ' chargeable kg at ₦' + kit.amount(chg('airE')) + '.', boxes: 0 },
      airs: { cost: dims.chargeKg * chg('airS'), note: kit.amount(dims.chargeKg, 0) + ' chargeable kg at ₦' + kit.amount(chg('airS')) + '.', boxes: 0 }
    };
  }

  /* ---------------- demurrage brackets ---------------- */

  function demurrageBrackets(dwell, freeDays, boxes) {
    var rows = [], rem = Math.max(0, dwell - freeDays), total = 0;
    for (var i = 0; i < DM_KEYS.length; i++) {
      var rate = chg(DM_KEYS[i]);
      var span = i < 3 ? 7 : Infinity;
      var days = span === Infinity ? rem : Math.min(rem, span);
      var amount = days * rate * boxes;
      total += amount;
      rows.push({
        from: freeDays + i * 7 + 1,
        to: i < 3 ? freeDays + (i + 1) * 7 : null,
        rate: rate, days: days, amount: amount,
        live: days > 0 && rem <= (i + 1) * 7
      });
      rem -= days;
      if (rem < 0) rem = 0;
    }
    return { rows: rows, total: total };
  }

  function demurrageOnly(dwell, freeDays, boxes) {
    return demurrageBrackets(dwell, freeDays, boxes).total;
  }

  /* ---------------- the model ---------------- */

  function compute(v) {
    var cur = CURS[v.cur] || CURS.USD;
    var r = { empty: v.goods <= 0 || v.rate <= 0, cur: cur, v: v };
    if (r.empty) return r;

    var cartonCbm = (v.cLength * v.cWidth * v.cHeight) / 1000000;
    r.cbm = v.cartons * cartonCbm;
    r.cartonCbm = cartonCbm;
    r.grossKg = v.cartons * v.cartonKg;
    r.volKg = r.cbm * chg('volDiv');
    r.chargeKg = Math.max(r.grossKg, r.volKg);

    r.goodsFxTotal = v.priceMode === 'unit' ? v.goods * v.qty : v.goods;
    r.goodsFxUnit = r.goodsFxTotal / v.qty;
    r.goodsN = r.goodsFxTotal * v.rate;
    r.units = v.qty;
    r.cartons = v.cartons;

    var fr = freightMenu(v, r);
    var chosen = fr[v.freightMode] || fr.manual;
    r.freight = chosen.cost;
    r.freightNote = chosen.note;
    r.boxes = chosen.boxes === undefined ? (v.freightMode === 'manual' ? 0 : 0) : chosen.boxes;
    r.boxSize = chosen.size || '20ft';

    r.insurance = v.insMode === 'none' ? 0
      : v.insMode === 'pct' ? r.goodsN * v.insPct / 100
        : v.insFx * v.rate;

    var sellerPays = v.inco === 'CIF' || v.inco === 'DDP';
    r.addedFreight = sellerPays ? 0 : r.freight;
    r.addedInsurance = sellerPays ? 0 : r.insurance;
    r.originExtras = v.inco === 'EXW' ? v.originInland + v.originThc : 0;

    r.invoiceN = r.goodsN + r.originExtras;
    r.cif = r.goodsN + r.addedFreight + r.addedInsurance + r.originExtras;
    r.premium = 0;

    /* duty + levy stack */
    var d = v.dutyRate / 100;
    var stack = 1 + d;
    r.duty = r.cif * d;
    r.surcharge = v.useSurcharge ? r.duty * levy('surcharge') / 100 : 0;
    if (r.surcharge) stack += (v.dutyRate / 100) * (levy('surcharge') / 100);
    r.etls = r.cif * levy('etls') / 100;
    r.ciss = r.cif * levy('ciss') / 100;
    r.stamp = v.useStamp ? r.cif * levy('stamp') / 100 : 0;
    r.excise = v.useExcise ? r.cif * cat(v.category).excise / 100 : 0;
    r.levies = r.etls + r.ciss + r.stamp + r.excise;
    stack += (levy('etls') + levy('ciss') + (v.useStamp ? levy('stamp') : 0) + (v.useExcise ? cat(v.category).excise : 0)) / 100;

    /* DDP: unpack the seller's all-in price into an assessable value */
    if (v.inco === 'DDP') {
      var derived = r.invoiceN / (stack * (1 + levy('vat') / 100));
      r.premium = r.invoiceN - derived;
      r.cif = derived;
      r.duty = derived * d;
      r.surcharge = v.useSurcharge ? r.duty * levy('surcharge') / 100 : 0;
      r.etls = derived * levy('etls') / 100;
      r.ciss = derived * levy('ciss') / 100;
      r.stamp = v.useStamp ? derived * levy('stamp') / 100 : 0;
      r.excise = v.useExcise ? derived * cat(v.category).excise / 100 : 0;
      r.levies = r.etls + r.ciss + r.stamp + r.excise;
      r.goodsN = derived + r.originExtras;
      r.declaredN = derived;
    } else {
      r.declaredN = r.goodsN;
    }

    r.dutyInclusive = r.cif + r.duty + r.surcharge + r.levies;
    r.vat = r.dutyInclusive * levy('vat') / 100;
    r.vatKept = v.reclaimVat ? 0 : r.vat;
    r.taxTotal = r.duty + r.surcharge + r.levies;

    /* port, line and agent charges */
    r.docs = chg('formM') + chg('agent');
    r.nimasa = r.freight * levy('nimasa') / 100;
    r.soncap = chg('soncap') * v.lines;
    r.mson = chg('mson') * v.vehicles;
    r.handling = 0;
    r.handlingLabel = 'Terminal & line handling';
    if (v.freightMode === 'fcl20' || v.freightMode === 'fcl40') {
      var per = v.freightMode === 'fcl20' ? chg('thc20') : chg('thc40');
      r.handling = r.boxes * (per + chg('dock') + chg('evac') + chg('nsc'));
      r.handlingLabel = 'THC, dock warrant, evacuation & wharfage (' + r.boxes + ' \u00d7 ' + r.boxSize + ')';
    } else if (v.freightMode === 'airx' || v.freightMode === 'aire' || v.freightMode === 'airs') {
      r.handling = (r.chargeKg / 100) * chg('airHnd');
      r.handlingLabel = 'Airline & air terminal handling (' + kit.amount(r.chargeKg, 0) + ' kg)';
    } else {
      r.handling = r.cbm * chg('cfs');
      r.handlingLabel = 'LCL CFS / warehouse handling (' + kit.amount(r.cbm, 2) + ' CBM)';
    }
    r.port = r.docs + r.nimasa + r.soncap + r.mson + r.handling;

    /* time cost */
    var dm = demurrageBrackets(v.dwell, v.freeDays, v.containers);
    r.dmRows = dm.rows;
    r.demurrage = dm.total;
    r.detention = v.detDays * v.detRate * v.containers;
    r.time = r.demurrage + r.detention;

    /* local tail */
    var t = truck(v.truck);
    r.truckCost = t.base + t.per * v.distance;
    r.truckName = t.name;
    r.storage = v.storageMonths * v.storageRate;
    r.local = r.truckCost + v.porters + r.storage;

    r.landed = r.declaredN + r.premium + r.originExtras + r.addedFreight + r.addedInsurance +
      r.taxTotal + r.vatKept + r.port + r.time + r.local;
    r.perUnit = r.landed / v.qty;
    r.perCarton = r.landed / v.cartons;
    r.uplift = (r.landed - r.invoiceN) / r.invoiceN * 100;
    r.goodsShare = r.declaredN / r.landed * 100;

    r.trail = buildTrail(r, v);
    return r;
  }

  function buildTrail(r, v) {
    var rows = [], run = 0, i = 0;
    var cur = r.cur;
    function add(line, base, rate, amount, kind) {
      if (kind !== 'subtotal') run += amount;
      i += 1;
      rows.push({ n: i, line: line, base: base, rate: rate, amount: amount, run: run, kind: kind || '' });
    }

    if (v.inco === 'DDP') {
      add('DDP price as quoted', cur.sym + kit.amount(r.goodsFxTotal, 0) + ' × ₦' + kit.amount(v.rate), '—', r.invoiceN);
      add('…unpacked: the seller\u2019s duty, levies and VAT buried in it', 'invoice ÷ the duty + levy + VAT stack', '—', -r.premium, 'less');
      add('Assessable (customs) value', 'unpacked from the seller\u2019s all-in price', '—', r.cif, 'subtotal');
    } else {
      add('Goods — invoice value',
        cur.sym + kit.amount(r.goodsFxTotal, 0) + ' \u00d7 ₦' + kit.amount(v.rate),
        '—', r.goodsN);
      if (v.inco === 'EXW' && v.originInland > 0) add('Inland haulage to the origin port', 'your invoice from the local haulier', '—', v.originInland);
      if (v.inco === 'EXW' && v.originThc > 0) add('Origin terminal handling', 'charged at the loading port', '—', v.originThc);
    }

    add('Freight to Nigeria',
      v.freightMode === 'manual' ? cur.sym + kit.amount(v.freightFx, 0) + ' \u00d7 ₦' + kit.amount(v.rate) : r.freightNote,
      v.freightMode === 'manual' ? '—' : 'calculated',
      r.addedFreight, v.inco === 'CIF' || v.inco === 'DDP' ? 'included' : '');
    add('Marine insurance',
      v.insMode === 'manual' ? cur.sym + kit.amount(v.insFx, 0) + ' \u00d7 ₦' + kit.amount(v.rate)
        : v.insMode === 'pct' ? kit.pct(v.insPct, 2) + ' of the goods value' : 'not purchased',
      '—', r.addedInsurance, v.inco === 'CIF' || v.inco === 'DDP' ? 'included' : '');

    if (v.inco !== 'DDP') {
      add('Assessable value (CIF)', 'the customs value everything below is charged on', '—', r.cif, 'subtotal');
    } else {
      rows[rows.length - 1].amount = r.cif;
      rows[rows.length - 1].run = r.cif;
      run = r.cif;
    }
    add('Import duty', 'on ' + money(r.cif), kit.pct(v.dutyRate, 2), r.duty);
    if (v.useSurcharge) add('Import duty surcharge', 'on the duty of ' + money(r.duty), kit.pct(levy('surcharge'), 2), r.surcharge);
    add('ECOWAS / ETLS levy', 'on ' + money(r.cif), kit.pct(levy('etls'), 2), r.etls);
    add('CISS surveillance fee', 'on ' + money(r.cif), kit.pct(levy('ciss'), 2), r.ciss);
    if (v.useStamp) add('Stamp duty', 'on ' + money(r.cif), kit.pct(levy('stamp'), 2), r.stamp);
    if (v.useExcise) add('Excise duty', 'on ' + money(r.cif) + ' — ' + cat(v.category).name, kit.pct(cat(v.category).excise, 2), r.excise);
    add('Duty-inclusive value', 'CIF + duty + surcharge + levies', '—', r.dutyInclusive, 'subtotal');
    add('VAT', 'on ' + money(r.dutyInclusive), kit.pct(levy('vat'), 2), r.vat);
    if (v.reclaimVat) add('Input VAT you will reclaim', 'VAT-registered purchases', '—', -r.vat, 'less');

    add('Form M + clearing agent', 'fixed charges on this consignment', '—', r.docs);
    add('NIMASA / Shipping Council', 'on the freight of ' + money(r.freight), kit.pct(levy('nimasa'), 2), r.nimasa);
    if (r.soncap) add('SONCAP / product certificate', v.lines + ' invoice line' + (v.lines > 1 ? 's' : ''), '₦' + kit.amount(chg('soncap')) + ' each', r.soncap);
    if (r.mson) add('MSON', v.vehicles + ' vehicle or machine unit', '₦' + kit.amount(chg('mson')) + ' each', r.mson);
    add(r.handlingLabel, 'assessed on the load as configured', '—', r.handling);
    if (r.demurrage) add('Demurrage', v.dwell + ' days at the terminal, ' + v.freeDays + ' free', 'bracketed', r.demurrage);
    if (r.detention) add('Container detention', v.detDays + ' days \u00d7 ' + v.containers + ' box' + (v.containers > 1 ? 'es' : ''), '₦' + kit.amount(v.detRate) + '/day', r.detention);
    add('Truck, port to warehouse', r.truckName + ' \u00b7 ' + v.distance + ' km', '—', r.truckCost);
    if (v.porters) add('Offloading & porters', 'paid at the warehouse', '—', v.porters);
    if (r.storage) add('Storage', v.storageMonths + ' months \u00d7 ₦' + kit.amount(v.storageRate), '—', r.storage);

    return rows;
  }

  /* ---------------- pricing & cash ---------------- */

  function pricing(v, r) {
    if (r.empty) return { empty: true };
    var p = {};
    p.sellable = v.qty * (1 - v.breakage / 100);
    p.unitCost = r.landed / Math.max(1, p.sellable);
    p.targetNet = p.unitCost * (1 + v.margin / 100);
    p.vatOut = p.targetNet * levy('vat') / 100;
    p.targetShelf = p.targetNet + p.vatOut;
    p.beShelf = p.unitCost * (1 + levy('vat') / 100);
    p.usingMarket = v.market > 0;
    p.shelf = p.usingMarket ? v.market : p.targetShelf;
    p.net = p.shelf / (1 + levy('vat') / 100);
    p.vatOnSale = p.shelf - p.net;
    p.profitUnit = p.net - p.unitCost;
    p.profitLoad = p.net * p.sellable - r.landed;
    p.carryDays = v.creditDays;
    p.capitalPerDay = r.landed * (v.financeRate / 100) / 30.44;
    p.carry = p.capitalPerDay * v.creditDays;
    p.netProfit = p.profitLoad - p.carry;
    p.roi = r.landed > 0 ? p.netProfit / r.landed * 100 : 0;
    p.roiGross = r.landed > 0 ? p.profitLoad / r.landed * 100 : 0;
    p.realisedMargin = p.net > 0 ? p.profitUnit / p.unitCost * 100 : 0;
    return p;
  }

  /* ---------------- FX ---------------- */

  function unitCostAt(v, rate) {
    var clone = Object.assign({}, v, { rate: rate });
    var rr = compute(clone);
    if (rr.empty) return null;
    var sellable = Math.max(1, v.qty * (1 - v.breakage / 100));
    return { r: rr, unit: rr.landed / sellable };
  }

  function fxAnalysis(v, r, p) {
    var out = { rows: [], breakEven: null, slope: 0, headroom: null };
    if (r.empty || p.empty) return out;
    var r0 = v.rate;
    var base = unitCostAt(v, r0);
    var high = unitCostAt(v, r0 * 1.25);
    if (!base || !high) return out;
    var slope = (high.unit - base.unit) / (r0 * 0.25);
    out.slope = slope;
    var netPrice = p.net;
    if (slope > 0) out.breakEven = r0 + (netPrice - base.unit) / slope;

    var marks = [
      { label: 'Rate you quoted at', f: 1, here: true },
      { label: 'Naira strengthens 5%', f: 0.95 },
      { label: 'Naira weakens 5%', f: 1.05 },
      { label: 'Naira weakens 10%', f: 1.1 },
      { label: 'Naira weakens 20%', f: 1.2 },
      { label: 'Naira weakens 30%', f: 1.3 }
    ];
    marks.forEach(function (m) {
      var s = unitCostAt(v, r0 * m.f);
      if (!s) return;
      var profit = netPrice - s.unit;
      out.rows.push({
        label: m.label, rate: r0 * m.f, here: !!m.here,
        cif: s.r.cif, tax: s.r.taxTotal, landed: s.r.landed,
        unit: s.unit, profit: profit, dies: profit <= 0
      });
    });
    if (out.breakEven && isFinite(out.breakEven) && out.breakEven > 0) {
      var b = unitCostAt(v, out.breakEven);
      out.rows.push({
        label: 'Your break-even rate', rate: out.breakEven, death: true,
        cif: b.r.cif, tax: b.r.taxTotal, landed: b.r.landed,
        unit: b.unit, profit: 0, dies: false, be: true
      });
      out.headroom = out.breakEven - r0;
      out.headroomPct = out.headroom / r0 * 100;
      out.unitAtBe = b.unit;
    }
    if (out.breakEven && out.breakEven < r0) out.alreadyDead = true;
    var series = [];
    for (var i = 0; i <= 12; i++) {
      var rr = r0 * (0.85 + i * 0.05);
      var s2 = unitCostAt(v, rr);
      series.push(s2 ? netPrice - s2.unit : 0);
    }
    out.series = series;
    out.rates = (function () { var a = []; for (var i = 0; i <= 12; i++) a.push(r0 * (0.85 + i * 0.05)); return a; })();
    var plus20 = unitCostAt(v, r0 * 1.2);
    out.plus20 = plus20 ? netPrice - plus20.unit : null;
    return out;
  }

  /* ---------------- helpers ---------------- */

  function money(value) { return kit.money(Math.round(value)); }
  function signed(value) {
    return (value < 0 ? '\u2212' : '') + kit.money(Math.round(Math.abs(value)));
  }
  function setFigure(id, value) {
    var el = document.getElementById(id);
    if (!el) return;
    var text = value === null || value === undefined ? '—' : String(value);
    if (el.textContent === text) return;
    el.textContent = text;
    if (!kit.reduce && last[id] !== undefined && last[id] !== text) {
      el.classList.remove('tick');
      void el.offsetWidth;
      el.classList.add('tick');
    }
    last[id] = text;
  }
  function escapeHtml(text) {
    var div = document.createElement('div');
    div.textContent = String(text);
    return div.innerHTML;
  }
  function verdict(boxId, titleId, bodyId, title, body, cls) {
    var box = document.getElementById(boxId);
    if (!box) return;
    box.className = 'verdict' + (cls ? ' ' + cls : '');
    document.getElementById(titleId).textContent = title;
    document.getElementById(bodyId).textContent = body;
  }

  /* ---------------- painting ---------------- */

  function paintEmpty() {
    ['lc-landed', 'lc-goods', 'lc-freight', 'lc-insurance', 'lc-cif', 'lc-duty', 'lc-levies', 'lc-vat',
      'lc-port', 'lc-demurrage', 'lc-local', 'lc-unit', 'lc-carton', 'lc-uplift',
      'sv-breakeven', 'sv-unit', 'sv-price', 'sv-vat', 'sv-shelf', 'sv-profit', 'sv-load',
      'sv-roi', 'sv-window', 'sv-carry', 'dm-total', 'dm-day', 'dm-capital', 'dm-share',
      'donut-label', 'trail-label', 'be-rate', 'be-head', 'be-unit', 'be-20', 'xo-head', 'fr-current'
    ].forEach(function (id) { setFigure(id, null); });
    document.getElementById('lc-landed-note').textContent = 'enter an invoice value and a rate to begin';
    kit.$('#duty-body').innerHTML = '<tr><td colspan="6">Awaiting the invoice value and the exchange rate.</td></tr>';
    kit.$('#duty-foot').innerHTML = '';
    kit.$('#print-lines').innerHTML = '';
    document.getElementById('inco-note').textContent = INCOS[inco];
    verdict('lc-verdict', 'lc-verdict-title', 'lc-verdict-body', 'Nothing to cost yet',
      'Type the supplier price and the naira rate on the left. The whole chain — CIF, duty, levies, VAT, port, time and the local tail — rebuilds as you type.', '');
  }

  function paint() {
    var v = readAll();
    var r = compute(v);
    document.getElementById('inco-note').textContent = INCOS[inco];
    syncCurrencyLabels(v);
    syncDutyNote(v);

    if (r.empty) { paintEmpty(); paintSaved(); return; }

    var p = pricing(v, r);
    var fx = fxAnalysis(v, r, p);

    /* --- main readout --- */
    setFigure('lc-landed', money(r.landed));
    document.getElementById('lc-landed-note').textContent =
      '₦' + kit.amount(v.rate) + ' / ' + v.cur + ' · ' + kit.amount(r.units) + ' units · ' +
      kit.pct(r.uplift, 0) + ' on top of the ' + (v.inco === 'DDP' ? 'quoted price' : 'invoice');
    setFigure('lc-goods', money(r.declaredN) + (r.premium ? ' + ' + money(r.premium) + ' buried' : ''));
    setFigure('lc-freight', (r.addedFreight ? money(r.addedFreight) : 'included by seller'));
    setFigure('lc-insurance', (r.addedInsurance ? money(r.addedInsurance) : v.insMode === 'none' ? 'none' : 'included by seller'));
    setFigure('lc-cif', money(r.cif));
    setFigure('lc-duty', money(r.duty + r.surcharge));
    setFigure('lc-levies', money(r.levies));
    setFigure('lc-vat', money(r.vat) + (v.reclaimVat ? ' (reclaimed)' : ''));
    setFigure('lc-port', money(r.port));
    setFigure('lc-demurrage', money(r.time));
    setFigure('lc-local', money(r.local));
    setFigure('lc-unit', money(r.perUnit));
    setFigure('lc-carton', money(r.perCarton));
    setFigure('lc-uplift', kit.pct(r.uplift, 1));

    /* --- assessment trail --- */
    kit.$('#duty-body').innerHTML = r.trail.map(function (row) {
      var cls = row.kind === 'subtotal' ? ' class="is-base"' : row.amount < 0 ? ' class="is-zero"' : '';
      return '<tr' + cls + '><td>' + row.n + '</td><td>' + escapeHtml(row.line) + '</td>' +
        '<td class="num">' + escapeHtml(row.base) + '</td>' +
        '<td class="num">' + escapeHtml(String(row.rate)) + '</td>' +
        '<td class="num">' + (row.amount < 0 ? signed(row.amount) : money(row.amount)) + '</td>' +
        '<td class="num">' + money(row.run) + '</td></tr>';
    }).join('');
    kit.$('#duty-foot').innerHTML = '<tr><td></td><td>Landed cost of this shipment</td><td></td><td></td>' +
      '<td class="num">' + money(r.landed) + '</td><td class="num">' + money(r.landed) + '</td></tr>';

    /* --- groups --- */
    var groups = [
      { label: 'Supplier invoice', value: r.declaredN },
      { label: 'Freight &amp; insurance', value: r.addedFreight + r.addedInsurance },
      { label: 'Duty, surcharge &amp; levies', value: r.taxTotal },
      { label: 'VAT', value: r.vatKept },
      { label: 'Port, line &amp; agent', value: r.port },
      { label: 'Demurrage &amp; detention', value: r.time },
      { label: 'Local tail', value: r.local }
    ];
    if (r.premium) groups.splice(1, 0, { label: 'Buried in the DDP price', value: r.premium });
    kit.bars(document.getElementById('duty-bars'), groups.map(function (g) {
      return { label: g.label, value: g.value, display: money(g.value) };
    }));

    var stackPct = (r.taxTotal + r.vatKept + r.port + r.time + r.local) / r.declaredN * 100;
    setFigure('donut-label', kit.pct(stackPct, 0));
    document.getElementById('donut-note').textContent =
      'For every ₦100 paid to the supplier, ₦' + stackPct.toFixed(0) +
      ' is added by freight, government, the terminal, the clock and the road. ' +
      'Only ₦' + (r.declaredN / r.landed * 100).toFixed(0) + ' of the final ₦100 of landed cost was ever the product.';
    kit.donut(document.getElementById('duty-donut'), kit.clamp(stackPct, 0, 100), { label: kit.pct(stackPct, 0) + ' charge stack', color: 'var(--accent)' });

    /* --- trail charts --- */
    var cum = [];
    var running = 0;
    kit.$$('#duty-body tr').forEach(function () {});
    r.trail.forEach(function (row) {
      if (row.kind === 'subtotal') return;
      running += row.amount;
      cum.push(Math.max(0, running));
    });
    kit.bars(document.getElementById('trail-bars'), [
      { label: 'Goods', value: r.declaredN },
      { label: 'Freight', value: r.addedFreight },
      { label: 'Insurance', value: r.addedInsurance },
      { label: 'Duty + surcharge', value: r.duty + r.surcharge },
      { label: 'Levies', value: r.levies },
      { label: 'VAT', value: r.vatKept },
      { label: 'Port &amp; agent', value: r.port },
      { label: 'Time (demurrage)', value: r.time },
      { label: 'Local tail', value: r.local }
    ].filter(function (row) { return row.value > 0; }).map(function (row) {
      return { label: row.label, value: row.value, display: money(row.value) };
    }));
    setFigure('trail-label', kit.pct(r.goodsShare, 0));
    document.getElementById('trail-note').textContent = r.goodsShare < 50
      ? 'The product is less than half of what you paid. You are in the logistics business as much as the goods business — price off the landed number, never the invoice.'
      : 'The product is still the biggest single line at ' + kit.pct(r.goodsShare, 0) + ', but the ₦' + kit.amount(r.landed - r.declaredN, 0) + ' around it is what moves when the rate, the terminal or the clock changes.';
    kit.donut(document.getElementById('trail-donut'), kit.clamp(r.goodsShare, 0, 100), { label: kit.pct(r.goodsShare, 0) + ' goods' });
    kit.sparkline(document.getElementById('trail-cum'), cum, { dot: 'var(--accent)' });

    /* --- freight section --- */
    paintFreight(v, r);

    /* --- demurrage section --- */
    paintDemurrage(v, r, p);

    /* --- pricing section --- */
    setFigure('sv-breakeven', money(p.beShelf));
    document.getElementById('sv-breakeven-note').textContent =
      'Shelf price that recovers the cost and the output VAT, and earns nothing. ' +
      (p.usingMarket ? 'The market price you entered is ₦' + (v.market - p.beShelf).toLocaleString('en-NG', { maximumFractionDigits: 0 }) + ' ' + (v.market >= p.beShelf ? 'above' : 'below') + ' this.' : 'Your target price sits above it.');
    setFigure('sv-unit', money(p.unitCost));
    setFigure('sv-price', money(p.targetNet));
    setFigure('sv-vat', money(p.vatOnSale));
    setFigure('sv-shelf', money(p.shelf));
    setFigure('sv-profit', money(p.profitUnit));
    setFigure('sv-load', money(p.profitLoad));
    setFigure('sv-roi', kit.pct(p.roi, 1));
    setFigure('sv-window', v.creditDays + ' days');
    setFigure('sv-carry', money(p.carry));
    pricingVerdict(v, r, p, fx);

    /* --- FX section --- */
    paintFx(v, r, p, fx);

    /* --- print lines + worked example --- */
    paintPrintLines(v, r, p);
    paintWorked(v, r, p);
    paintSaved();
  }

  function paintFreight(v, r) {
    var dims = [
      { k: 'Volume', val: kit.amount(r.cbm, 2) + ' CBM', note: r.cartons + ' cartons \u00d7 ' + (r.cartonCbm * 1000000).toFixed(0) + ' cm\u00b3' },
      { k: 'Gross weight', val: kit.amount(r.grossKg, 0) + ' kg', note: 'as invoiced' },
      { k: 'Volumetric weight', val: kit.amount(r.volKg, 0) + ' kg', note: 'CBM \u00d7 ' + chg('volDiv') },
      { k: 'Chargeable kg', val: kit.amount(r.chargeKg, 0) + ' kg', note: 'the greater of the two' },
      { k: 'Of a 20ft box', val: kit.pct(r.cbm / Math.max(1, chg('cbm20')) * 100, 0), note: chg('cbm20') + ' CBM usable' },
      { k: 'Of a 40ft HC', val: kit.pct(r.cbm / Math.max(1, chg('cbm40')) * 100, 0), note: chg('cbm40') + ' CBM usable' }
    ];
    kit.$('#vol-grid').innerHTML = dims.map(function (d) {
      return '<div><span>' + escapeHtml(d.k) + '</span><b>' + escapeHtml(d.val) + '</b><i>' + escapeHtml(d.note) + '</i></div>';
    }).join('');

    var menu = freightMenu(v, r);
    var order = ['lcl', 'fcl20', 'fcl40', 'airx', 'aire', 'airs'];
    var names = {
      lcl: 'Sea LCL', fcl20: 'FCL 20ft', fcl40: 'FCL 40ft HC',
      airx: 'Air express', aire: 'Air economy', airs: 'Sea-air', manual: 'Forwarder\u2019s quote'
    };
    var rows = order.map(function (k) { return { key: k, label: names[k], cost: menu[k].cost }; });
    rows.push({ key: 'manual', label: 'Forwarder\u2019s quote', cost: menu.manual.cost });
    var cheapest = rows.slice().sort(function (a, b) { return a.cost - b.cost; })[0];
    kit.$('#fr-bars').innerHTML = '';
    kit.bars(document.getElementById('fr-bars'), rows.map(function (row) {
      return {
        label: row.key === v.freightMode ? '\u25c6 ' + row.label : row.label,
        value: row.cost, display: money(row.cost)
      };
    }));
    var crossoverCbm = chg('fcl20') / Math.max(1, chg('lclRate'));
    setFigure('xo-head', 'LCL until ' + kit.amount(crossoverCbm, 1) + ' CBM — then a 20ft box');
    document.getElementById('xo-note').textContent =
      'At ₦' + kit.amount(chg('lclRate')) + ' a CBM, an LCL shipment costs the same as a whole 20ft container at ₦' +
      kit.amount(chg('fcl20')) + ' once you pass ' + kit.amount(crossoverCbm, 1) + ' CBM. You are shipping ' +
      kit.amount(r.cbm, 2) + ' CBM, which is ' + kit.pct(r.cbm / Math.max(1, chg('cbm20')) * 100, 0) +
      ' of one box — ' + (r.cbm > crossoverCbm ? 'a full container is the cheaper structure, and it also stops per-CBM CFS charges.' : 'groupage is the right structure today; do not pay for air you cannot fill.') +
      ' Air beats sea only below about ' + kit.amount((chg('fcl20') / Math.max(1, chg('airE'))) / Math.max(0.01, chg('volDiv')) * 1000, 1) + ' CBM for economy rates.';
    document.getElementById('fr-current').textContent = 'Current: ' + names[v.freightMode] +
      ' at ' + money(v.freightMode === 'manual' ? menu.manual.cost : menu[v.freightMode].cost);
    document.getElementById('fr-use').textContent = 'Use ' + names[cheapest.key] + ' — ' + money(cheapest.cost);
    document.getElementById('fr-use').dataset.mode = cheapest.key;
  }

  function paintDemurrage(v, r, p) {
    setFigure('dm-total', money(r.time));
    document.getElementById('dm-note').textContent =
      v.dwell + ' days at the terminal (' + v.freeDays + ' free) · ' + v.detDays + ' detention days · ' +
      v.containers + ' box' + (v.containers > 1 ? 'es' : '') + ' counted';
    var nextRate = bracketRateAt(v.dwell + 1, v.freeDays);
    setFigure('dm-day', money(nextRate * v.containers));
    setFigure('dm-capital', money(r.landed * (v.financeRate / 100) / 30.44));
    setFigure('dm-share', p.profitLoad > 0 ? kit.pct(r.time / p.profitLoad * 100, 0) : 'no profit to eat');

    kit.$('#dm-body').innerHTML =
      '<tr class="is-free"><td>Day 1\u2013' + v.freeDays + '</td><td class="num">free</td>' +
      '<td class="num">' + Math.min(v.dwell, v.freeDays) + '</td><td class="num">\u20bf0'.replace('\u20bf', '₦') + '</td></tr>' +
      r.dmRows.map(function (row) {
        var label = row.to ? 'Day ' + row.from + '\u2013' + row.to : 'Day ' + row.from + ' onward';
        return '<tr' + (row.live ? ' class="is-live"' : '') + '><td>' + label + '</td>' +
          '<td class="num">' + money(row.rate) + '</td>' +
          '<td class="num">' + row.days + '</td>' +
          '<td class="num">' + money(row.amount) + '</td></tr>';
      }).join('');
    kit.$('#dm-foot').innerHTML = '<tr><td>Demurrage subtotal</td><td></td>' +
      '<td class="num">' + Math.max(0, v.dwell - v.freeDays) + '</td>' +
      '<td class="num">' + money(r.demurrage) + '</td></tr>' +
      '<tr><td>Detention (' + v.detDays + ' days \u00d7 ' + v.containers + ')</td><td></td><td></td>' +
      '<td class="num">' + money(r.detention) + '</td></tr>';

    var maxDay = Math.max(21, v.dwell + 9);
    var series = [];
    for (var d = 1; d <= maxDay; d++) series.push(timeCostAt(v, r, d));
    var spark = document.getElementById('dm-spark');
    spark.setAttribute('aria-label', 'Cost of the clock per day in the terminal, day 1 to ' + maxDay);
    kit.sparkline(spark, series, { dot: 'var(--accent)' });

    var eaten = null;
    if (p.profitLoad > 0) {
      for (var dd = 1; dd <= 90; dd++) {
        if (timeCostAt(v, r, dd) >= p.profitLoad) { eaten = dd; break; }
      }
    }
    document.getElementById('dm-break').textContent = v.dwell <= v.freeDays
      ? 'At ' + v.dwell + ' days you are still inside the free period — the clock costs you nothing but the capital, about ' +
        money(r.landed * (v.financeRate / 100) / 30.44 * v.dwell) + ' of your own money at ' + kit.pct(v.financeRate, 1) + ' a month.'
      : 'The clock is already costing ' + money(r.time) + ' and ' + money(r.landed * (v.financeRate / 100) / 30.44 * v.dwell) +
      ' of carried capital. ' + (eaten
        ? 'Your whole gross profit on this load (' + money(p.profitLoad) + ') is gone by day ' + eaten + '.'
        : 'Even at 90 days it has not swallowed the ' + money(p.profitLoad) + ' gross profit on the load — but the delay is not free.');

    kit.$('#dm-tips').innerHTML = demurrageTips(v, r, p).map(function (t) {
      return '<li>' + escapeHtml(t) + '</li>';
    }).join('');

    if (v.dwell <= v.freeDays) {
      verdict('dm-verdict', 'dm-verdict-title', 'dm-verdict-body', 'Inside the free period',
        'Clear and evacuate within ' + v.freeDays + ' days and demurrage never appears on the invoice. Everything you save on the file, you spend on the truck.', 'ok');
    } else if (p.profitLoad > 0 && r.time > p.profitLoad) {
      verdict('dm-verdict', 'dm-verdict-title', 'dm-verdict-body', 'The clock ate the shipment',
        money(r.time) + ' of demurrage and detention against ' + money(p.profitLoad) + ' of gross profit. This container is a loss before it reaches the warehouse.', 'bad');
    } else if (r.time > r.landed * 0.05) {
      verdict('dm-verdict', 'dm-verdict-title', 'dm-verdict-body', 'Time is now a real line',
        money(r.time) + ' is ' + kit.pct(r.time / r.landed * 100, 1) + ' of the landed cost. Day ' + (v.freeDays + 8) +
        ' onward the rate steps to ' + money(chg('dm3')) + ' a day a box — get the file ready before discharge, not after.', 'warn');
    } else {
      verdict('dm-verdict', 'dm-verdict-title', 'dm-verdict-body', 'Modest time cost',
        money(r.time) + ' over ' + v.dwell + ' days. Keep the documents pre-registered and the truck booked and most of it is avoidable.', '');
    }
  }

  function timeCostAt(v, r, d) {
    var boxes = v.containers;
    var dm = demurrageOnly(d, v.freeDays, boxes);
    var det = v.dwell > 0 ? v.detRate * boxes * Math.round(v.detDays * d / v.dwell) : 0;
    var capital = r.landed * (v.financeRate / 100) / 30.44 * d;
    return dm + det + capital;
  }

  function bracketRateAt(day, freeDays) {
    var over = day - freeDays;
    if (over <= 0) return 0;
    if (over <= 7) return chg('dm1');
    if (over <= 14) return chg('dm2');
    if (over <= 21) return chg('dm3');
    return chg('dm4');
  }

  function demurrageTips(v, r, p) {
    var tips = [];
    if (v.dwell > v.freeDays) tips.push('Every day past ' + v.freeDays + ' is priced — the bracket steps again at day ' + (v.freeDays + 8) + ' and ' + (v.freeDays + 15) + '.');
    tips.push('Pre-register Form M, the SONCAP/Product Certificate and the PA form before the vessel sails, not after discharge.');
    tips.push('Submit the completed C1 / GAEC within 48 hours of discharge and track it daily; the longest delays are bank and board approvals, not the terminal.');
    tips.push('Book the truck and the evacuation permit while the goods are still on the water — detention runs until the box is returned empty, not until it leaves the gate.');
    if (v.detention > r.demurrage) tips.push('Detention (' + money(v.detention) + ') now exceeds demurrage. Return the empty box, then settle the paperwork at the agent\u2019s office.');
    if (v.freightMode !== 'fcl20' && v.freightMode !== 'fcl40') tips.push('On groupage you pay cargo rent rather than container demurrage — ask the consolidator for the free-period in writing.');
    if (p.profitLoad > 0 && r.time / p.profitLoad > 0.25) tips.push('Time is ' + kit.pct(r.time / p.profitLoad * 100, 0) + ' of your gross profit. That is the number to argue about with the bank.');
    return tips.slice(0, 6);
  }

  function pricingVerdict(v, r, p, fx) {
    if (p.usingMarket && p.net <= p.unitCost) {
      verdict('sv-verdict', 'sv-verdict-title', 'sv-verdict-body', 'The market price loses money',
        'At ₦' + kit.amount(v.market) + ' on the shelf you recover ₦' + money(p.net) + ' ex-VAT against a landed cost of ₦' +
        money(p.unitCost) + ' a unit. Either the freight, the duty band or the dwell has to move — or this shipment does not go.', 'bad');
      return;
    }
    if (p.carry > p.profitLoad) {
      verdict('sv-verdict', 'sv-verdict-title', 'sv-verdict-body', 'The financing costs more than the margin',
        money(p.profitLoad) + ' of gross profit over ' + v.creditDays + ' days of credit costs ' + money(p.carry) +
        ' at ' + kit.pct(v.financeRate, 1) + ' a month. Shorten the window or take a margin you can actually hold.', 'bad');
      return;
    }
    if (p.realisedMargin < 12) {
      verdict('sv-verdict', 'sv-verdict-title', 'sv-verdict-body', 'Thin once the tail is counted',
        'You keep ' + money(p.profitUnit) + ' a unit after ' + kit.pct(v.breakage, 1) + '% breakage — ' +
        kit.pct(p.realisedMargin, 1) + ' on cost, ' + kit.pct(p.roi, 1) + ' net ROI after the carry. One attachment or one revaluation wipes it.', 'warn');
      return;
    }
    var dead = fx && fx.breakEven ? ' The naira can reach ₦' + kit.amount(fx.breakEven, 0) + ' before this margin disappears.' : '';
    verdict('sv-verdict', 'sv-verdict-title', 'sv-verdict-body', 'This one works — for now',
      money(p.profitUnit) + ' a unit, ' + money(p.profitLoad) + ' on the load, ' + kit.pct(p.roi, 1) +
      ' net ROI over ' + v.creditDays + ' days.' + dead, 'ok');
  }

  function paintFx(v, r, p, fx) {
    if (!fx.rows.length) {
      kit.$('#fx-body').innerHTML = '<tr><td colspan="8">Set an invoice value and a rate to test the FX risk.</td></tr>';
      setFigure('be-rate', null);
      return;
    }
    kit.$('#fx-body').innerHTML = fx.rows.map(function (row) {
      var cls = row.here ? ' class="is-here"' : row.death ? ' class="is-death"' : '';
      return '<tr' + cls + '><td>' + escapeHtml(row.label) + '</td>' +
        '<td class="num">' + kit.amount(row.rate, 0) + '</td>' +
        '<td class="num">' + kit.amount(row.cif, 0) + '</td>' +
        '<td class="num">' + kit.amount(row.tax, 0) + '</td>' +
        '<td class="num">' + kit.amount(row.landed, 0) + '</td>' +
        '<td class="num">' + kit.amount(row.unit, 0) + '</td>' +
        '<td class="num">' + (row.profit >= 0 ? kit.amount(row.profit, 0) : '\u2212' + kit.amount(-row.profit, 0)) + '</td>' +
        '<td>' + (row.be ? 'break-even' : row.dies ? 'loses' : 'survives') + '</td></tr>';
    }).join('');

    kit.sparkline(document.getElementById('fx-spark'), fx.series, { dot: 'var(--accent)' });
    document.getElementById('fx-spark').setAttribute('aria-label',
      'Profit per unit as the rate moves from ₦' + kit.amount(v.rate * 0.85, 0) + ' to ₦' + kit.amount(v.rate * 1.45, 0) + ' a ' + v.cur);
    document.getElementById('fx-note').textContent =
      'The line is straight because the assessable value, the duty, the levies and the VAT all scale with the rate — only your ₦ charges (terminal, agent, truck, demurrage) stay still. ' +
      'Your shelf price is fixed at ' + money(p.shelf) + '; every naira the rate adds comes out of the margin.';

    if (fx.alreadyDead || !fx.breakEven || fx.breakEven <= v.rate) {
      setFigure('be-rate', 'Already gone');
      document.getElementById('be-note').textContent =
        'At ₦' + kit.amount(v.rate) + ' the ' + v.cur + ' you pay already exceeds what this shelf price can absorb — the shipment loses ' +
        money(Math.abs(p.profitUnit)) + ' a unit before it moves.';
      setFigure('be-head', null);
      setFigure('be-unit', null);
      setFigure('be-20', money(fx.plus20 === null ? null : fx.plus20));
      document.getElementById('rate-note').textContent = 'Your margin is already under water at this rate — see section 08.';
      return;
    }
    setFigure('be-rate', '₦' + kit.amount(fx.breakEven, 0) + ' / ' + v.cur);
    document.getElementById('be-note').textContent =
      'The rate at which your landed cost per unit exactly equals the net selling price of ' + money(p.net) +
      '. Everything the naira does beyond this is paid for out of your own pocket.';
    setFigure('be-head', '₦' + kit.amount(fx.headroom, 0) + ' (' + kit.pct(fx.headroomPct, 1) + ')');
    setFigure('be-unit', money(fx.unitAtBe));
    setFigure('be-20', (fx.plus20 === null ? '—' : (fx.plus20 >= 0 ? money(fx.plus20) + ' left' : '\u2212' + money(-fx.plus20) + ' lost')));
    document.getElementById('rate-note').textContent =
      'Quoted at ₦' + kit.amount(v.rate) + '. Margin survives to about ₦' + kit.amount(fx.breakEven, 0) + ' a ' + v.cur + ' — see section 08.';
  }

  function paintPrintLines(v, r, p) {
    if (r.empty) return;
    var lines = r.trail.filter(function (row) { return row.kind !== 'subtotal'; }).map(function (row) {
      return { label: row.line, value: row.amount };
    });
    lines.push({ label: 'Per unit (' + kit.amount(r.units) + ' units)', value: r.perUnit });
    lines.push({ label: 'Per carton (' + kit.amount(r.cartons) + ' cartons)', value: r.perCarton });
    if (p && !p.empty) {
      lines.push({ label: 'Break-even shelf price per unit', value: p.beShelf });
      lines.push({ label: 'Recommended shelf price', value: p.shelf });
    }
    kit.$('#print-lines').innerHTML = lines.map(function (row) {
      return '<tr><td>' + escapeHtml(row.label) + '</td><td class="num">' +
        (row.value < 0 ? signed(row.value) : money(row.value)) + '</td></tr>';
    }).join('') + '<tr class="is-base"><td><b>Landed cost</b></td><td class="num"><b>' + money(r.landed) + '</b></td></tr>';
    document.getElementById('print-meta').textContent =
      (v.name || 'Unnamed shipment') + ' · ' + (v.route || 'route not given') + ' · ' + v.inco + ' · ₦' +
      kit.amount(v.rate) + '/' + v.cur + ' · printed ' + kit.dateLabel(new Date());
  }

  function paintWorked(v, r, p) {
    var cur = CURS[v.cur] || CURS.USD;
    if (r.empty) {
      kit.$('#worked').innerHTML = '<li>Enter an invoice value and an exchange rate and every step below recomputes from your own figures.</li>';
      return;
    }
    var steps = [
      'Invoice value: ' + cur.sym + kit.amount(r.goodsFxTotal, 0) + ' \u00d7 ₦' + kit.amount(v.rate) +
        ' = <b>' + money(r.goodsN) + '</b>' + (v.priceMode === 'unit' ? ' (' + cur.sym + kit.amount(v.goods, 0) + ' a unit \u00d7 ' + kit.amount(v.qty) + ' units)' : '') + '.',
      'Freight ' + (v.freightMode === 'manual' ? cur.sym + kit.amount(v.freightFx, 0) + ' \u00d7 ₦' + kit.amount(v.rate) + ' = ' : '(' + freightName(v.freightMode) + ') ') +
        '<b>' + money(r.freight) + '</b> · insurance <b>' + money(r.insurance) + '</b>' +
        (v.inco === 'CIF' || v.inco === 'DDP' ? ' — already inside the invoice, so not added again.' : '.'),
      'Assessable (CIF) value = <b>' + money(r.cif) + '</b>' + (v.inco === 'EXW' ? ', including your origin haulage and THC.' : '.'),
      'Import duty at ' + kit.pct(v.dutyRate, 2) + ' of CIF = <b>' + money(r.duty) + '</b>.',
      r.surcharge
        ? 'Import duty surcharge at ' + kit.pct(levy('surcharge'), 2) + ' of the duty = <b>' + money(r.surcharge) + '</b>.'
        : 'Surcharge switched off — <b>₦0</b>.',
      'Levies on the CIF: ETLS ' + kit.pct(levy('etls'), 2) + ' = ' + money(r.etls) + ', CISS ' + kit.pct(levy('ciss'), 2) + ' = ' +
        money(r.ciss) + (v.useStamp ? ', stamp ' + kit.pct(levy('stamp'), 2) + ' = ' + money(r.stamp) : '') +
        (r.excise ? ', excise = ' + money(r.excise) : '') + ' → <b>' + money(r.levies) + '</b>.',
      'VAT ' + kit.pct(levy('vat'), 2) + ' of the duty-inclusive value (₦' + kit.amount(r.dutyInclusive, 0) + ') = <b>' +
        money(r.vat) + '</b>' + (v.reclaimVat ? ', reclaimed as input VAT so it leaves the cost.' : '.') +
        (v.inco === 'DDP' ? ' (unpacked from the seller\u2019s all-in price: ' + money(r.premium) + ' of their figure was duty and tax.)' : ''),
      'Add the terminal and documents <b>' + money(r.port) + '</b>, the clock <b>' + money(r.time) + '</b> and the local tail <b>' +
        money(r.local) + '</b> → landed <b>' + money(r.landed) + '</b>, ' + kit.pct(r.uplift, 1) + ' above the invoice.',
      'Divide: ' + money(r.landed) + ' \u00f7 ' + kit.amount(r.units) + ' units = <b>' + money(r.perUnit) + ' a piece</b> (' +
        money(r.perCarton) + ' a carton). After ' + kit.pct(v.breakage, 1) + '% breakage the cost of a unit you can actually sell is <b>' +
        money(p.unitCost) + '</b>, so the shelf has to clear <b>' + money(p.beShelf) + '</b> before you earn anything.'
    ];
    kit.$('#worked').innerHTML = steps.map(function (s) { return '<li>' + s + '</li>'; }).join('');
  }

  function freightName(mode) {
    var names = { manual: 'forwarder\u2019s quote', lcl: 'sea LCL', fcl20: 'one or more 20ft', fcl40: '40ft high-cube', airx: 'air express', aire: 'air economy', airs: 'sea-air' };
    return names[mode] || mode;
  }

  /* ---------------- saved shipments ---------------- */

  function ships() { return shipsStore.read() || []; }

  function saveShipment() {
    var v = readAll();
    var r = compute(v);
    if (r.empty) {
      kit.toast('Enter the invoice value and the rate first');
      document.getElementById('goods').focus();
      return;
    }
    var p = pricing(v, r);
    var fx = fxAnalysis(v, r, p);
    var inputs = {};
    ALL_IDS.forEach(function (id) { inputs[id] = val(id); });
    SWITCH_IDS.forEach(function (id) { inputs[id] = check(id); });
    var list = ships();
    var row = {
      id: kit.uid('lc'),
      at: Date.now(),
      inco: v.inco,
      inputs: inputs,
      name: v.name || 'Unnamed shipment',
      route: v.route,
      cur: v.cur,
      rate: v.rate,
      cif: r.cif,
      tax: r.taxTotal + r.vatKept,
      landed: r.landed,
      unit: r.perUnit,
      be: p.beShelf,
      shelf: p.shelf,
      margin: p.realisedMargin,
      roi: p.roi,
      fxBe: fx.breakEven && fx.breakEven > 0 ? fx.breakEven : null
    };
    list.unshift(row);
    if (list.length > 60) list = list.slice(0, 60);
    if (!shipsStore.write(list)) { kit.toast('This browser blocked saving; print the quotation sheet instead'); return; }
    paintSaved(row.id);
    kit.toast('Shipment saved — ' + money(r.landed) + ' landed');
  }

  function paintSaved(newId) {
    var list = ships();
    var body = kit.$('#sv-body');
    var foot = kit.$('#sv-foot');
    var count = document.getElementById('sv-count');
    var empty = document.getElementById('sv-empty');
    count.textContent = list.length + (list.length === 1 ? ' shipment' : ' shipments') +
      (list.length ? ' · ' + money(list.reduce(function (a, b) { return a + b.landed; }, 0)) + ' of goods to cost' : '');
    empty.hidden = list.length > 0;
    if (!list.length) { body.innerHTML = ''; foot.innerHTML = ''; return; }
    body.innerHTML = list.map(function (e) {
      return '<tr' + (e.id === newId ? ' class="is-new"' : '') + '>' +
        '<td class="num">' + escapeHtml(kit.dateLabel(e.at)) + '</td>' +
        '<td><b>' + escapeHtml(e.name) + '</b><br><span class="tiny">' + escapeHtml((e.route || '') + ' · ' + e.inco + ' · ₦' + kit.amount(e.rate) + '/' + e.cur) + '</span></td>' +
        '<td class="num">' + kit.amount(e.cif, 0) + '</td>' +
        '<td class="num">' + kit.amount(e.tax, 0) + '</td>' +
        '<td class="num"><b>' + kit.amount(e.landed, 0) + '</b></td>' +
        '<td class="num">' + kit.amount(e.unit, 0) + '</td>' +
        '<td class="num">' + kit.amount(e.be, 0) + '</td>' +
        '<td class="num">' + kit.pct(e.margin, 1) + '</td>' +
        '<td class="num">' + (e.fxBe ? '₦' + kit.amount(e.fxBe, 0) : '—') + '</td>' +
        '<td class="row-btns"><button class="row-use" type="button" data-load="' + e.id + '" aria-label="Load ' + escapeHtml(e.name) + ' back into the calculator">Load</button>' +
        '<button class="row-del" type="button" data-del="' + e.id + '" aria-label="Delete ' + escapeHtml(e.name) + '">&times;</button></td>' +
        '</tr>';
    }).join('');
    var t = list.reduce(function (a, b) {
      return { cif: a.cif + b.cif, tax: a.tax + b.tax, landed: a.landed + b.landed };
    }, { cif: 0, tax: 0, landed: 0 });
    foot.innerHTML = '<tr><td>Totals</td><td></td><td class="num">' + kit.amount(t.cif, 0) + '</td>' +
      '<td class="num">' + kit.amount(t.tax, 0) + '</td><td class="num">' + kit.amount(t.landed, 0) + '</td>' +
      '<td colspan="5"></td></tr>';
  }

  function loadShipment(id) {
    var row = ships().filter(function (e) { return e.id === id; })[0];
    if (!row) return;
    ALL_IDS.forEach(function (id2) {
      var el = document.getElementById(id2);
      if (el && row.inputs[id2] !== undefined && !el.disabled) el.value = row.inputs[id2];
    });
    SWITCH_IDS.forEach(function (id2) {
      var el = document.getElementById(id2);
      if (el && typeof row.inputs[id2] === 'boolean') el.checked = row.inputs[id2];
    });
    setInco(row.inco || 'FOB');
    categoryLocked = true;
    paint();
    kit.toast('Loaded “' + row.name + '” from ' + kit.dateLabel(row.at));
    var target = document.getElementById('quote');
    if (target) target.scrollIntoView({ behavior: kit.reduce ? 'auto' : 'smooth', block: 'start' });
  }

  function exportCsv() {
    var list = ships();
    if (!list.length) { kit.toast('Save a shipment first — section 02'); return; }
    kit.csv([
      ['Quote date', 'Reference', 'Route', 'Incoterm', 'Currency', 'Rate', 'CIF', 'Duty + levies + VAT', 'Landed', 'Landed per unit', 'Break-even shelf', 'Shelf priced', 'Margin on cost %', 'Net ROI %', 'FX break-even rate']
    ].concat(list.map(function (e) {
      return [new Date(e.at).toISOString().slice(0, 10), e.name, e.route, e.inco, e.cur, round(e.rate), round(e.cif),
        round(e.tax), round(e.landed), round(e.unit), round(e.be), round(e.shelf), round(e.margin), round(e.roi),
        e.fxBe ? round(e.fxBe) : ''];
    })), 'landedcost-quotes-' + new Date().toISOString().slice(0, 10) + '.csv');
    kit.toast(list.length + ' quote' + (list.length > 1 ? 's' : '') + ' exported');
  }

  function round(x) { return Math.round((Number(x) || 0) * 100) / 100; }

  /* ---------------- printing ---------------- */

  var printMode = null;
  function printAs(mode) {
    var v = readAll(), r = compute(v);
    if (r.empty) { kit.toast('Fill in the shipment before printing'); return; }
    printMode = mode;
    document.body.classList.add('print-' + mode);
    window.addEventListener('afterprint', clearPrint, { once: true });
    kit.print();
    window.setTimeout(clearPrint, 4000);
  }
  function clearPrint() {
    if (!printMode) return;
    document.body.classList.remove('print-' + printMode);
    printMode = null;
  }

  /* ---------------- label syncing ---------------- */

  function syncCurrencyLabels(v) {
    var cur = CURS[v.cur] || CURS.USD;
    var tag = document.getElementById('cur-tag');
    if (tag) tag.textContent = v.cur;
    kit.$$('.cur-tag2, .cur-tag3, .cur-tag4, .cur-tag5, .cur-tag6').forEach(function (el) { el.textContent = v.cur; });
    var gp = document.getElementById('goods-prefix');
    if (gp) gp.textContent = cur.sym;
    var fp = document.getElementById('fr-prefix');
    if (fp) fp.textContent = cur.sym;
    var ip = document.getElementById('in-prefix');
    if (ip) ip.textContent = cur.sym;
    var gl = document.getElementById('goods-label');
    if (gl) gl.innerHTML = 'Supplier price (' + (v.priceMode === 'unit' ? 'per unit' : 'total') +
      ', in <span class="cur-tag2">' + escapeHtml(v.cur) + '</span>)';
    var frField = document.getElementById('freightFx');
    var insField = document.getElementById('insFx');
    var manual = v.freightMode === 'manual';
    if (frField) { frField.disabled = !manual; frField.closest('.field').style.opacity = manual ? '' : '.5'; }
    var insManual = v.insMode === 'manual';
    if (insField) { insField.disabled = !insManual; insField.closest('.field').style.opacity = insManual ? '' : '.5'; }
    var insPctField = document.getElementById('insPct');
    if (insPctField) {
      insPctField.disabled = v.insMode !== 'pct';
      insPctField.closest('.field').style.opacity = v.insMode === 'pct' ? '' : '.5';
    }
    var sellerPays = v.inco === 'CIF' || v.inco === 'DDP';
    if (frField && sellerPays) frField.closest('.field').style.opacity = '.5';
    var originFields = ['originInland', 'originThc'];
    originFields.forEach(function (id) {
      var el = document.getElementById(id);
      if (!el) return;
      var on = v.inco === 'EXW';
      el.disabled = !on;
      el.closest('.field').style.opacity = on ? '' : '.5';
    });
  }

  function syncDutyNote(v) {
    var c = cat(v.category);
    var note = document.getElementById('duty-note');
    if (!note) return;
    if (Math.abs(v.dutyRate - c.duty) > 0.001) {
      note.textContent = 'You typed ' + kit.pct(v.dutyRate, 2) + '; the ' + c.name.toLowerCase() + ' band is ' +
        kit.pct(c.duty, 0) + '. Custom rate kept — the HS line decides the truth.';
    } else {
      note.textContent = c.name + ' — indicative band ' + kit.pct(c.duty, 0) +
        (c.excise ? ', excise ' + kit.pct(c.excise, 0) + ' applies when flagged' : ', no excise') +
        '. Bands collapse a range of HS lines into one figure.';
    }
  }

  function setInco(code) {
    inco = code;
    kit.$$('#inco-strip .inco').forEach(function (btn) {
      var on = btn.dataset.inco === code;
      btn.classList.toggle('is-on', on);
      btn.setAttribute('aria-pressed', String(on));
    });
  }

  /* ---------------- editable rule tables ---------------- */

  function paintRateTables() {
    kit.$('#rate-body').innerHTML = levies.map(function (row, i) {
      return '<tr data-kind="levies" data-i="' + i + '"><td>' + escapeHtml(row.label) + '</td>' +
        '<td>' + escapeHtml(row.base) + '</td>' +
        '<td class="num"><input type="number" min="0" step="' + row.step + '" value="' + row.val +
        '" data-field="val" aria-label="' + escapeHtml(row.label) + '"></td></tr>';
    }).join('');
    kit.$('#cat-body').innerHTML = cats.map(function (row, i) {
      return '<tr data-kind="cats" data-i="' + i + '"><td>' + escapeHtml(row.name) + '</td>' +
        '<td class="num"><input type="number" min="0" max="60" step="1" value="' + row.duty +
        '" data-field="duty" aria-label="' + escapeHtml(row.name) + ' duty percent">%</td>' +
        '<td class="num"><input type="number" min="0" max="60" step="0.5" value="' + row.excise +
        '" data-field="excise" aria-label="' + escapeHtml(row.name) + ' excise percent">' + (row.excise ? '%' : '—') + '</td></tr>';
    }).join('');
    kit.$('#chg-body').innerHTML = chgs.map(function (row, i) {
      return '<tr data-kind="charges" data-i="' + i + '"><td>' + escapeHtml(row.label) + '</td>' +
        '<td class="num"><input type="number" min="0" step="' + (row.val >= 1000 ? 1000 : 1) + '" value="' + row.val +
        '" data-field="val" aria-label="' + escapeHtml(row.label) + ' ' + escapeHtml(row.unit) + '"> ' +
        '<span class="tiny">' + escapeHtml(row.unit) + '</span></td></tr>';
    }).join('');
  }

  function bindRuleTables() {
    ['#rate-body', '#cat-body', '#chg-body'].forEach(function (sel) {
      kit.on(document.querySelector(sel), 'input', 'input', function (e, el) {
        var tr = el.closest('tr');
        var kind = tr.dataset.kind, i = Number(tr.dataset.i), field = el.dataset.field;
        var table = kind === 'levies' ? levies : kind === 'cats' ? cats : chgs;
        var num = kit.num(el.value);
        table[i][field] = kind === 'charges' && num >= 0 ? num : kit.clamp(num, 0, 300);
        saveRules();
        paint();
      });
    });
    document.getElementById('reset-rates').addEventListener('click', function () {
      levies = LEVIES.map(function (d) { return Object.assign({}, d); });
      cats = CATS.map(function (d) { return Object.assign({}, d); });
      chgs = CHARGES.map(function (d) { return Object.assign({}, d); });
      saveRules();
      paintRateTables();
      paint();
      kit.toast('Default rates restored — every charge is back to the indicative figure');
    });
  }

  /* ---------------- defaults / reset ---------------- */

  var DEFAULTS = {
    'quote-name': 'Phone accessories — 40 cartons', route: 'Guangzhou → Apapa', cur: 'USD', rate: '1520',
    'price-mode': 'total', goods: '8000', qty: '400', cartons: '40', upc: '10', cartonKg: '12',
    cLength: '60', cWidth: '40', cHeight: '35', freightMode: 'manual', freightFx: '1400',
    insMode: 'manual', insFx: '120', insPct: '0.6', category: 'elec', dutyRate: '10',
    originInland: '0', originThc: '0', lines: '1', vehicles: '0',
    dwell: '12', freeDays: '7', detDays: '4', detRate: '12000', containers: '1', financeRate: '4',
    truck: 't15', distance: '38', porters: '18000', storageMonths: '0.5', storageRate: '45000',
    breakage: '1.5', margin: '35', market: '0', daysToSell: '55', creditDays: '120'
  };
  var DEFAULT_SWITCHES = { 'use-excise': false, 'use-surcharge': true, 'use-stamp': true, 'reclaim-vat': false };

  function applyDefaults() {
    Object.keys(DEFAULTS).forEach(function (id) {
      var el = document.getElementById(id);
      if (el) el.value = DEFAULTS[id];
    });
    Object.keys(DEFAULT_SWITCHES).forEach(function (id) {
      var el = document.getElementById(id);
      if (el) el.checked = DEFAULT_SWITCHES[id];
    });
    setInco('FOB');
    categoryLocked = false;
    paint();
    categoryLocked = true;
  }

  /* ---------------- boot ---------------- */

  kit.boot({
    ready: function () {
      /* build the option lists from the domain tables */
      kit.$('#category').innerHTML = cats.map(function (c) {
        return '<option value="' + c.key + '"' + (c.key === 'elec' ? ' selected' : '') + '>' +
          escapeHtml(c.name + ' — ' + kit.pct(c.duty, 0)) + '</option>';
      }).join('');
      kit.$('#truck').innerHTML = TRUCKS.map(function (t) {
        return '<option value="' + t.key + '"' + (t.key === 't15' ? ' selected' : '') + '>' +
          escapeHtml(t.name + ' — ₦' + kit.amount(t.base) + ' + ₦' + kit.amount(t.per) + '/km') + '</option>';
      }).join('');

      paintRateTables();
      bindRuleTables();

      /* every input on all three forms recomputes the page */
      ['#q-form', '#dm-form', '#se-form'].forEach(function (sel) {
        var form = document.querySelector(sel);
        if (!form) return;
        kit.$$('input, select', form).forEach(function (el) {
          el.addEventListener('input', paint);
          el.addEventListener('change', paint);
        });
      });

      kit.on(document.getElementById('inco-strip'), 'click', '.inco', function (e, btn) {
        setInco(btn.dataset.inco);
        paint();
      });

      /* category selects the band rate unless the user has typed their own */
      document.getElementById('category').addEventListener('change', function () {
        if (!categoryLocked) return;
        document.getElementById('dutyRate').value = cat(val('category')).duty;
        paint();
      });
      document.getElementById('dutyRate').addEventListener('input', function () { categoryLocked = false; });
      document.getElementById('dutyRate').addEventListener('blur', function () { categoryLocked = true; });

      document.getElementById('cur').addEventListener('change', function () {
        var cur = CURS[val('cur')] || CURS.USD;
        document.getElementById('rate').value = cur.rate;
        paint();
        kit.toast('Rate set to the indicative ' + val('cur') + ' figure — type your bank rate if you have one');
      });

      document.getElementById('fr-use').addEventListener('click', function () {
        var mode = this.dataset.mode;
        if (!mode) return;
        document.getElementById('freightMode').value = mode;
        paint();
        kit.toast('Freight switched to ' + freightName(mode) + ' — the whole chain rebuilt');
      });

      document.getElementById('q-save').addEventListener('click', saveShipment);
      document.getElementById('q-print').addEventListener('click', function () { printAs('quote'); });
      document.getElementById('sv-print').addEventListener('click', function () { printAs('quote'); });
      document.getElementById('sv-csv').addEventListener('click', exportCsv);
      document.getElementById('q-form').addEventListener('reset', function () {
        window.setTimeout(function () { applyDefaults(); kit.toast('Back to the worked example'); }, 0);
      });

      kit.on(document.getElementById('sv-body'), 'click', '[data-del]', function (e, el) {
        var list = ships().filter(function (row) { return row.id !== el.dataset.del; });
        shipsStore.write(list);
        paintSaved();
        kit.toast('Shipment removed');
      });
      kit.on(document.getElementById('sv-body'), 'click', '[data-load]', function (e, el) {
        loadShipment(el.dataset.load);
      });
      document.getElementById('sv-clear').addEventListener('click', function () {
        if (!ships().length) { kit.toast('Nothing is saved yet'); return; }
        if (!window.confirm('Delete every saved shipment from this browser? Export the CSV first if you need them.')) return;
        shipsStore.clear();
        paintSaved();
        kit.toast('Saved shipments cleared');
      });

      document.addEventListener('keydown', function (e) {
        if (e.key === 'p' && (e.ctrlKey || e.metaKey) && e.shiftKey) {
          e.preventDefault();
          printAs('quote');
        }
      });

      applyDefaults();
      paintSaved();
    }
  });
})();
