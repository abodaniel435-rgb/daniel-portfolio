/* PayRite — payroll engine. All maths runs in the browser, nothing is sent anywhere. */
(function () {
  'use strict';

  var DEFAULT_BANDS = [
    { width: 300000, rate: 7 },
    { width: 300000, rate: 11 },
    { width: 500000, rate: 15 },
    { width: 500000, rate: 19 },
    { width: 1600000, rate: 21 },
    { width: Infinity, rate: 24 }
  ];

  var bandsStore = kit.store('payrite:bands', null);
  var rosterStore = kit.store('payrite:roster', []);
  var bands = bandsStore.read() || DEFAULT_BANDS.map(function (b) { return { width: b.width, rate: b.rate }; });
  var last = {};

  /* ---------- engine ---------- */

  function normalise(list) {
    var out = list.filter(function (b) { return b.rate >= 0; }).map(function (b) {
      return { width: Number(b.width) > 0 ? Number(b.width) : Infinity, rate: Number(b.rate) || 0 };
    });
    if (!out.length) out = DEFAULT_BANDS.slice();
    if (out[out.length - 1].width !== Infinity) out[out.length - 1] = { width: Infinity, rate: out[out.length - 1].rate };
    return out;
  }

  // Progressive PAYE on annual taxable pay: each slice is taxed at its own rate.
  function payeOn(taxable, table) {
    var remaining = Math.max(0, taxable), tax = 0, marginal = 0;
    for (var i = 0; i < table.length && remaining > 0; i++) {
      var band = table[i];
      var slice = band.width === Infinity ? remaining : Math.min(remaining, band.width);
      tax += slice * band.rate / 100;
      marginal = band.rate;
      remaining -= slice;
    }
    return { annual: tax, marginal: marginal };
  }

  function compute(input, table) {
    var gross = (input.basic + input.housing + input.transport + input.other);
    if (gross <= 0) return { empty: true, gross: 0 };

    var annualGross = gross * 12;
    var cra = 200000 + annualGross * 0.2;
    var rentRelief = (input.useRent && input.rent > 0) ? Math.min(input.rent * 0.2, 6250000) : 0;
    var pensionMonthly = input.usePension ? gross * input.pensionRate / 100 : 0;
    var pensionAnnual = pensionMonthly * 12;
    var nhfMonthly = input.useNhf ? gross * 0.025 : 0;

    var taxable = Math.max(0, annualGross - cra - rentRelief - pensionAnnual);
    var paye = payeOn(taxable, table || bands);
    var payeMonthly = paye.annual / 12;

    var net = gross - payeMonthly - pensionMonthly - nhfMonthly;
    var employerPension = input.usePension ? gross * 0.10 : 0;
    var employerNhf = input.useNhf ? gross * 0.025 : 0;
    var employerCost = gross + employerPension + employerNhf;

    return {
      gross: gross, annualGross: annualGross, cra: cra, rentRelief: rentRelief,
      pensionMonthly: pensionMonthly, pensionAnnual: pensionAnnual,
      nhfMonthly: nhfMonthly, employerPension: employerPension, employerNhf: employerNhf,
      taxable: taxable, taxableMonthly: taxable / 12,
      payeMonthly: payeMonthly, payeAnnual: paye.annual, marginal: paye.marginal,
      net: net, employerCost: employerCost, annualNet: net * 12,
      effRate: payeMonthly / gross * 100,
      input: input
    };
  }

  function readForm() {
    return {
      name: (document.getElementById('employee').value || '').trim(),
      basic: kit.num(document.getElementById('basic').value),
      housing: kit.num(document.getElementById('housing').value),
      transport: kit.num(document.getElementById('transport').value),
      other: kit.num(document.getElementById('other').value),
      rent: kit.num(document.getElementById('rent').value),
      useRent: document.getElementById('use-rent').checked,
      usePension: document.getElementById('use-pension').checked,
      useNhf: document.getElementById('use-nhf').checked,
      pensionRate: kit.clamp(kit.num(document.getElementById('pension-rate').value) || 8, 0, 20),
      staff: kit.clamp(Math.round(kit.num(document.getElementById('staff').value) || 1), 1, 500)
    };
  }

  /* ---------- painting ---------- */

  function setFigure(id, value) {
    var el = document.getElementById(id);
    if (!el) return;
    var text = value === null ? '—' : value;
    if (el.textContent === String(text)) return;
    el.textContent = text;
    if (!kit.reduce && last[id] !== undefined && last[id] !== text) {
      el.classList.remove('tick');
      void el.offsetWidth;
      el.classList.add('tick');
    }
    last[id] = text;
  }

  function paint() {
    var input = readForm();
    var r = compute(input);

    if (r.empty) {
      ['net', 'r-gross', 'r-cra', 'r-rent', 'r-taxable', 'r-paye', 'r-pension', 'r-nhf', 'r-eff', 'r-cost', 'r-annual']
        .forEach(function (id) { setFigure(id, null); });
      document.getElementById('net-note').textContent = 'enter a package to begin';
      verdict('Nothing to compute yet', 'Type the monthly components on the left — the payslip builds as you type.', '');
      kit.$('#split-bars').innerHTML = '<p class="tiny">Awaiting figures.</p>';
      kit.donut(document.getElementById('split-donut'), 0, { label: 'no data' });
      document.getElementById('donut-label').textContent = '—';
      document.getElementById('donut-note').textContent = '—';
      return;
    }

    setFigure('net', kit.money(r.net));
    document.getElementById('net-note').textContent = 'from ' + kit.money(r.gross) + ' gross · ' + kit.money(r.annualNet) + ' a year';
    setFigure('r-gross', kit.money(r.gross));
    setFigure('r-cra', '−' + kit.money(Math.min(r.cra, r.annualGross)) + ' / yr');
    setFigure('r-rent', r.rentRelief ? '−' + kit.money(r.rentRelief) + ' / yr' : 'not claimed');
    setFigure('r-taxable', kit.money(r.taxableMonthly));
    setFigure('r-paye', '−' + kit.money(r.payeMonthly));
    setFigure('r-pension', r.pensionMonthly ? '−' + kit.money(r.pensionMonthly) : 'off');
    setFigure('r-nhf', r.nhfMonthly ? '−' + kit.money(r.nhfMonthly) : 'off');
    setFigure('r-eff', kit.pct(r.effRate, 2));
    setFigure('r-cost', kit.money(r.employerCost));
    setFigure('r-annual', kit.money(r.annualNet));

    var take = r.net / r.gross * 100;
    document.getElementById('donut-label').textContent = kit.pct(take, 1);
    document.getElementById('donut-note').textContent =
      'Of every ₦100 you pay out, ₦' + take.toFixed(1) + ' reaches the employee and ₦' +
      (100 - take).toFixed(1) + ' goes to tax, pension and NHF.';

    kit.bars(document.getElementById('split-bars'), [
      { label: 'Net pay', value: r.net, display: kit.money(r.net) },
      { label: 'PAYE', value: r.payeMonthly, display: kit.money(r.payeMonthly) },
      { label: 'Pension', value: r.pensionMonthly, display: kit.money(r.pensionMonthly) },
      { label: 'NHF', value: r.nhfMonthly, display: kit.money(r.nhfMonthly) }
    ]);
    kit.donut(document.getElementById('split-donut'), take, { label: kit.pct(take, 1) + ' take-home' });

    verdictFor(r);
    compare(input);
    worked(r);
  }

  function verdict(title, body, cls) {
    var box = document.getElementById('verdict');
    box.className = 'verdict' + (cls ? ' ' + cls : '');
    document.getElementById('verdict-title').textContent = title;
    document.getElementById('verdict-body').textContent = body;
  }

  function verdictFor(r) {
    var input = r.input;
    if (input.useRent === false && input.rent > 0) {
      var giveup = compute(Object.assign({}, input, { useRent: true }));
      verdict('Rent relief is switched off', 'Claiming it on ₦' + kit.amount(input.rent) + ' rent would cut PAYE by about ' +
        kit.money(r.payeMonthly - giveup.payeMonthly) + ' a month.', 'warn');
      return;
    }
    if (!input.usePension) {
      verdict('No pension deducted', 'Pension is mandatory for formal employment. Skipping it raises taxable pay — and exposes the business to PenCom penalties.', 'bad');
      return;
    }
    if (r.effRate < 6) {
      verdict('Lightly taxed — expected at this band', 'Effective ' + kit.pct(r.effRate, 2) + ' of gross, top slice taxed at ' + r.marginal +
        '%. Relief is doing most of the work here.', 'ok');
      return;
    }
    if (r.effRate < 12) {
      verdict('Typical for this salary range', 'Effective ' + kit.pct(r.effRate, 2) + ' of gross. The marginal rate is ' + r.marginal +
        '%, so the next ₦100k of basic costs you roughly ₦' + kit.amount(100000 * r.marginal / 100 / 12) + ' more PAYE a month.', '');
      return;
    }
    verdict('Heavy take', 'Effective ' + kit.pct(r.effRate, 2) + ' of gross. Re-check the rent cap (₦6.25m relief) and whether allowances are correctly classified before filing.', 'warn');
  }

  function compare(input) {
    var base = compute(input);
    var rows = [
      { label: 'Everything claimed', r: base },
      { label: 'If rent relief is missed', r: compute(Object.assign({}, input, { useRent: false })) },
      { label: 'If pension is not deducted', r: compute(Object.assign({}, input, { usePension: false })) },
      { label: 'If CRA is forgotten', r: computeNoCra(input) },
      { label: 'Bare package, no reliefs', r: compute(Object.assign({}, input, { useRent: false, usePension: false })) }
    ];
    kit.$('#compare-body').innerHTML = rows.map(function (row) {
      var diff = row.r.payeMonthly - base.payeMonthly;
      return '<tr><td>' + row.label + '</td>' +
        '<td class="num">' + kit.money(row.r.payeMonthly) + '</td>' +
        '<td class="num">' + kit.money(row.r.net) + '</td>' +
        '<td class="num ' + (diff > 0 ? '' : 'best') + '">' + (diff === 0 ? 'baseline' : (diff > 0 ? '+' : '−') + kit.money(Math.abs(diff)) + ' PAYE') + '</td></tr>';
    }).join('');
  }

  // CRA is statutory, but forgetting to apply it is the classic payroll error — so model it.
  function computeNoCra(input) {
    var clone = Object.assign({}, input);
    var r = compute(clone);
    var taxable = Math.max(0, r.annualGross - r.rentRelief - r.pensionAnnual);
    var paye = payeOn(taxable, bands);
    var payeMonthly = paye.annual / 12;
    return Object.assign({}, r, {
      taxable: taxable, payeMonthly: payeMonthly, payeAnnual: paye.annual, marginal: paye.marginal,
      net: r.gross - payeMonthly - r.pensionMonthly - r.nhfMonthly,
      effRate: payeMonthly / r.gross * 100
    });
  }

  function worked(r) {
    var steps = [
      'Gross pay: ₦' + kit.amount(r.input.basic) + ' + ₦' + kit.amount(r.input.housing) + ' + ₦' + kit.amount(r.input.transport) + ' + ₦' + kit.amount(r.input.other) + ' = <b>₦' + kit.amount(r.gross) + '</b> a month.',
      'Annual gross = ₦' + kit.amount(r.gross) + ' × 12 = <b>₦' + kit.amount(r.annualGross) + '</b>.',
      'Consolidated relief = ₦200,000 + 20% of annual gross = <b>₦' + kit.amount(r.cra) + '</b>.',
      r.rentRelief
        ? 'Rent relief = 20% of ₦' + kit.amount(r.input.rent) + ' = <b>₦' + kit.amount(r.rentRelief) + '</b> (cap ₦6,250,000).'
        : 'Rent relief: <b>not claimed</b> — nothing deducted for tenancy.',
      'Employee pension ' + r.input.pensionRate + '% of gross = <b>₦' + kit.amount(r.pensionMonthly) + '</b> a month, ₦' + kit.amount(r.pensionAnnual) + ' a year, deducted before tax.',
      'Taxable pay = annual gross − relief = <b>₦' + kit.amount(r.taxable) + '</b> a year (₦' + kit.amount(r.taxableMonthly) + ' a month).',
      'PAYE applied band by band (top slice at ' + r.marginal + '%) = <b>₦' + kit.amount(r.payeAnnual) + '</b> a year, <b>₦' + kit.amount(r.payeMonthly) + '</b> a month.',
      'Net pay = gross − PAYE − pension − NHF = <b>' + kit.money(r.net) + '</b>. Effective rate <b>' + kit.pct(r.effRate, 2) + '</b>.',
      'Employer side: pension 10% + NHF 2.5% on top of gross → total monthly cost <b>' + kit.money(r.employerCost) + '</b>.'
    ];
    kit.$('#worked').innerHTML = steps.map(function (s) { return '<li>' + s + '</li>'; }).join('');
  }

  /* ---------- editable bands ---------- */

  function paintBands() {
    kit.$('#band-body').innerHTML = bands.map(function (band, i) {
      var lastRow = i === bands.length - 1;
      return '<tr data-band="' + i + '">' +
        '<td>Band ' + (i + 1) + '</td>' +
        '<td class="num">' + (lastRow ? 'Everything above' : '<input type="number" min="1" step="50000" value="' + band.width + '" data-field="width" aria-label="Band ' + (i + 1) + ' width">') + '</td>' +
        '<td><input type="number" min="0" max="40" step="0.5" value="' + band.rate + '" data-field="rate" aria-label="Band ' + (i + 1) + ' rate" style="width:84px">%</td>' +
        '</tr>';
    }).join('');
  }

  function bindBands() {
    kit.on(document.getElementById('band-body'), 'input', 'input', function (e, el) {
      var row = el.closest('tr');
      var i = Number(row.dataset.band);
      bands[i][el.dataset.field] = kit.num(el.value);
      if (el.dataset.field === 'rate') bands[i].rate = kit.clamp(bands[i].rate, 0, 60);
      bandsStore.write(bands);
      paint();
    });
    document.getElementById('reset-bands').addEventListener('click', function () {
      bands = DEFAULT_BANDS.map(function (b) { return { width: b.width, rate: b.rate }; });
      bandsStore.write(bands);
      paintBands();
      paint();
      kit.toast('Default bands restored');
    });
  }

  /* ---------- roster ---------- */

  function roster() { return rosterStore.read() || []; }

  function addRoster() {
    var input = readForm();
    var r = compute(input);
    if (r.empty) {
      kit.toast('Add the salary components first');
      document.getElementById('basic').focus();
      return;
    }
    var list = roster();
    var base = input.name || 'Employee ' + (list.length + 1);
    var firstId = null;
    for (var i = 0; i < input.staff; i++) {
      var row = {
        id: kit.uid('emp'),
        name: input.staff > 1 ? base + ' ' + (i + 1) : base,
        gross: r.gross, paye: r.payeMonthly, pension: r.pensionMonthly,
        nhf: r.nhfMonthly, net: r.net, cost: r.employerCost,
        rent: input.useRent ? r.rentRelief : 0, added: Date.now()
      };
      if (firstId === null) firstId = row.id;
      list.push(row);
    }
    if (!rosterStore.write(list)) { kit.toast('This browser blocked saving; export the CSV instead'); return; }
    paintRoster(firstId);
    paint();
    kit.toast(input.staff > 1 ? 'Added ' + input.staff + ' staff at this package' : 'Saved to the roster');
  }

  function paintRoster(newId) {
    var list = roster();
    var body = kit.$('#roster-body');
    var foot = kit.$('#roster-foot');
    document.getElementById('roster-count').textContent = list.length + (list.length === 1 ? ' employee' : ' employees') +
      (list.length ? ' · ' + kit.money(list.reduce(function (a, b) { return a + b.cost; }, 0)) + ' monthly cost' : '');
    document.getElementById('roster-empty').hidden = list.length > 0;

    if (!list.length) { body.innerHTML = ''; foot.innerHTML = ''; return; }
    body.innerHTML = list.map(function (e) {
      return '<tr' + (e.id === newId ? ' class="is-new"' : '') + '>' +
        '<td>' + escapeHtml(e.name) + '</td>' +
        '<td class="num">' + kit.amount(e.gross) + '</td>' +
        '<td class="num">' + kit.amount(e.paye) + '</td>' +
        '<td class="num">' + kit.amount(e.pension) + '</td>' +
        '<td class="num">' + kit.amount(e.nhf) + '</td>' +
        '<td class="num"><b>' + kit.amount(e.net) + '</b></td>' +
        '<td class="num">' + kit.amount(e.cost) + '</td>' +
        '<td><button class="row-del" type="button" data-del="' + e.id + '" aria-label="Remove ' + escapeHtml(e.name) + '">×</button></td>' +
        '</tr>';
    }).join('');

    var total = list.reduce(function (a, b) {
      return { gross: a.gross + b.gross, paye: a.paye + b.paye, pension: a.pension + b.pension, nhf: a.nhf + b.nhf, net: a.net + b.net, cost: a.cost + b.cost };
    }, { gross: 0, paye: 0, pension: 0, nhf: 0, net: 0, cost: 0 });
    foot.innerHTML = '<tr><td>Total</td><td class="num">' + kit.amount(total.gross) + '</td><td class="num">' + kit.amount(total.paye) +
      '</td><td class="num">' + kit.amount(total.pension) + '</td><td class="num">' + kit.amount(total.nhf) +
      '</td><td class="num">' + kit.amount(total.net) + '</td><td class="num">' + kit.amount(total.cost) + '</td><td></td></tr>';
  }

  function escapeHtml(text) {
    var div = document.createElement('div');
    div.textContent = String(text);
    return div.innerHTML;
  }

  function bindRoster() {
    kit.on(document.getElementById('roster-body'), 'click', '[data-del]', function (e, el) {
      var list = roster().filter(function (row) { return row.id !== el.dataset.del; });
      rosterStore.write(list);
      paintRoster();
    });
    document.getElementById('add-employee').addEventListener('click', addRoster);
    document.getElementById('clear-roster').addEventListener('click', function () {
      if (!roster().length) { kit.toast('The roster is already empty'); return; }
      if (!window.confirm('Remove every saved employee from this browser?')) return;
      rosterStore.clear();
      paintRoster();
      kit.toast('Roster cleared');
    });
    document.getElementById('export-csv').addEventListener('click', function () {
      var list = roster();
      if (!list.length) { kit.toast('Add someone to the roster first'); return; }
      kit.csv([
        ['Employee', 'Gross (month)', 'PAYE', 'Pension', 'NHF', 'Net pay', 'Employer cost', 'Rent relief (annual)']
      ].concat(list.map(function (e) {
        return [e.name, round(e.gross), round(e.paye), round(e.pension), round(e.nhf), round(e.net), round(e.cost), round(e.rent)];
      })), 'payrite-roster-' + new Date().toISOString().slice(0, 10) + '.csv');
    });
    document.getElementById('print-roster').addEventListener('click', function () {
      if (!roster().length) { kit.toast('Nothing to print yet'); return; }
      kit.print();
    });
  }

  function round(n) { return Math.round((Number(n) || 0) * 100) / 100; }

  /* ---------- boot ---------- */

  kit.boot({
    ready: function () {
      paintBands();
      bindBands();
      bindRoster();
      kit.$$('#pay-form input, #pay-form select').forEach(function (el) {
        el.addEventListener('input', paint);
        el.addEventListener('change', paint);
      });
      document.getElementById('pay-form').addEventListener('reset', function () {
        setTimeout(paint, 0);
      });
      document.getElementById('pay-form').addEventListener('submit', function (e) {
        e.preventDefault();
        addRoster();
      });
      paint();
      paintRoster();
    }
  });
})();
