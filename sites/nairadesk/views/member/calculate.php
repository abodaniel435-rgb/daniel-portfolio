<?php declare(strict_types=1); ?>
<p class="eyebrow">Server-side PAYE</p>
<h1 style="font-size:clamp(1.7rem,3.2vw,2.4rem)"><?= $input ? e($input['full_name'] ?? 'Payslip') : 'One calculation' ?></h1>
<p class="lede" style="max-width:64ch">Nothing here is worked out in the browser. The form posts to <code>POST /api/payroll</code>, the engine reads the bands from MySQL, and the row it writes is the row you can audit afterwards.</p>

<div class="split-page" style="margin-top:26px">
  <div class="panel">
    <form id="paye-form" autocomplete="off">
      <?= csrf_field() ?>
      <div class="grid-2">
        <div class="field"><label for="basic">Basic / month ₦</label><input id="basic" name="basic" inputmode="numeric" required value="<?= e((string)($input['basic'] ?? 120000)) ?>"></div>
        <div class="field"><label for="other">Other ₦</label><input id="other" name="other" inputmode="numeric" value="<?= e((string)($input['other'] ?? 0)) ?>"></div>
      </div>
      <div class="grid-2">
        <div class="field"><label for="housing">Housing ₦</label><input id="housing" name="housing" inputmode="numeric" value="<?= e((string)($input['housing'] ?? 0)) ?>"></div>
        <div class="field"><label for="transport">Transport ₦</label><input id="transport" name="transport" inputmode="numeric" value="<?= e((string)($input['transport'] ?? 0)) ?>"></div>
      </div>
      <label class="check"><input type="checkbox" name="use_pension" value="1" <?= empty($input['use_pension']) || $input['use_pension'] ? 'checked' : '' ?>> Pension</label>
      <div class="field"><label for="pension_rate">Employee pension %</label><input id="pension_rate" name="pension_rate" inputmode="decimal" value="<?= e((string)($input['pension_rate'] ?? 8)) ?>"></div>
      <label class="check"><input type="checkbox" name="use_nhf" value="1" <?= empty($input['use_nhf']) || $input['use_nhf'] ? 'checked' : '' ?>> National Housing Fund</label>
      <label class="check"><input type="checkbox" name="use_rent" value="1" <?= !empty($input['use_rent']) ? 'checked' : '' ?>> Claim rent relief</label>
      <div class="field"><label for="annual_rent">Annual rent ₦</label><input id="annual_rent" name="annual_rent" inputmode="numeric" value="<?= e((string)($input['annual_rent'] ?? 0)) ?>"></div>
      <button class="btn btn-primary" type="submit">Work it out</button>
    </form>
  </div>

  <div class="readout">
    <div class="panel">
      <p class="eyebrow" style="margin-bottom:6px">Net pay / month</p>
      <div class="readout-big" id="r-net">—</div>
      <div id="r-body" style="margin-top:16px">
        <p style="font-size:.9rem;color:var(--ink-3)">Fill the form and submit. The working appears here, band by band.</p>
      </div>
    </div>
  </div>
</div>

<script>
(function () {
  var ENDPOINT = <?= json_encode(url('/api/payroll'), JSON_UNESCAPED_SLASHES) ?>;
  var form = document.getElementById('paye-form');
  var money = function (n) { return '₦' + (Math.round((Number(n) || 0) * 100) / 100).toLocaleString('en-NG', { maximumFractionDigits: 2 }); };
  var row = function (k, v) { return '<div class="readout-row"><span>' + k + '</span><b>' + v + '</b></div>'; };

  form.addEventListener('submit', function (event) {
    event.preventDefault();
    var body = new FormData(form);
    document.getElementById('r-net').textContent = '…';

    fetch(ENDPOINT, {
      method: 'POST',
      credentials: 'same-origin',
      headers: { 'X-Requested-With': 'XMLHttpRequest' },
      body: body
    }).then(function (res) { return res.json(); }).then(function (data) {
      if (!data.ok) {
        var msg = '';
        Object.keys(data.errors || {}).forEach(function (k) { msg += data.errors[k] + ' '; });
        document.getElementById('r-net').textContent = '—';
        document.getElementById('r-body').innerHTML = '<p class="err">' + (msg || 'The server refused that input.') + '</p>';
        return;
      }
      var r = data.result;
      document.getElementById('r-net').textContent = money(r.net);
      var html = '';
      html += row('Gross', money(r.gross));
      html += row('Annual gross', money(r.annualGross));
      html += row('CRA', money(r.cra));
      if (r.rentRelief > 0) { html += row('Rent relief', money(r.rentRelief)); }
      html += row('Pension (employee)', money(r.pensionMonthly));
      html += row('NHF', money(r.nhfMonthly));
      html += row('Taxable / year', money(r.taxable));
      html += row('PAYE / month', money(r.payeMonthly));
      html += row('Marginal rate', r.marginal + '%');
      html += row('Employer cost', money(r.employerCost));
      html += row('Effective tax rate', r.effRate + '%');
      html += '<h3 style="margin:18px 0 8px;font-size:.95rem">Band by band</h3><div class="table-wrap"><table><thead><tr><th>Slice</th><th class="n">Rate</th><th class="n">Taxed</th></tr></thead><tbody>';
      r.steps.forEach(function (s) {
        html += '<tr><td>' + (s.width === null ? 'Balance' : money(s.width)) + '</td><td class="n">' + s.rate + '%</td><td class="n">' + money(s.charge) + '</td></tr>';
      });
      html += '</tbody></table></div>';
      html += '<p style="font-size:.78rem;color:var(--ink-3);margin-top:12px">Stored as calculation #' + data.id + '.</p>';
      document.getElementById('r-body').innerHTML = html;
    }).catch(function () {
      document.getElementById('r-net').textContent = '—';
      document.getElementById('r-body').innerHTML = '<p class="err">The request did not reach the server.</p>';
    });
  });
})();
</script>
