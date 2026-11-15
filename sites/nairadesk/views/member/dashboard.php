<?php declare(strict_types=1); ?>
<p class="eyebrow">Signed in as <?= e(Auth::user()['name']) ?></p>
<h1 style="font-size:clamp(1.8rem,3.4vw,2.6rem)"><?= $company ? e($company['name']) : 'Set up your workspace' ?></h1>

<?php if (!$company): ?>
  <div class="panel" style="max-width:520px;margin-top:22px">
    <h3>New business</h3>
    <form method="post" action="<?= e(url('/company')) ?>">
      <?= csrf_field() ?>
      <div class="field"><label for="name">Business name</label><input id="name" name="name" required maxlength="160"></div>
      <div class="grid-2">
        <div class="field"><label for="tin">TIN</label><input id="tin" name="tin" maxlength="40"></div>
        <div class="field"><label for="state">State</label><input id="state" name="state" value="Lagos" maxlength="60"></div>
      </div>
      <button class="btn btn-primary" type="submit">Create workspace</button>
    </form>
  </div>
<?php else: ?>
  <div class="stat-row">
    <div class="stat"><b><?= $headcount ?></b><span>people on the roster</span></div>
    <div class="stat"><b><?= count($runs) ? e(money($runs[0]['net_total'])) : '—' ?></b><span>net paid in the latest run</span></div>
    <div class="stat"><b><?= count($runs) ? e($runs[0]['period']) : '—' ?></b><span>period of that run</span></div>
  </div>

  <div class="split-page" style="margin-top:38px">
    <div>
      <div class="panel">
        <h3>Next</h3>
        <p style="font-size:.92rem">Add the people you pay, then run the month. A run can be repeated as often as you like — the last one wins.</p>
        <div style="display:flex;gap:10px;flex-wrap:wrap">
          <a class="btn btn-primary btn-sm" href="<?= e(url('/staff')) ?>">Staff</a>
          <a class="btn btn-ghost btn-sm" href="<?= e(url('/calculate')) ?>">One calculation</a>
          <a class="btn btn-ghost btn-sm" href="<?= e(url('/runs')) ?>">Pay runs</a>
        </div>
      </div>
    </div>
    <div>
      <h2>Recent server work</h2>
      <?php if (!$recent): ?><p>Nothing yet. Every calculation the server performs lands here, with the inputs that produced it.</p><?php endif; ?>
      <div class="table-wrap">
        <table>
          <thead><tr><th>When</th><th>Tool</th><th>Label</th><th class="n">Headline</th></tr></thead>
          <tbody>
          <?php foreach ($recent as $row): ?>
            <tr><td><?= e(short_datetime($row['created_at'])) ?></td><td><?= e($row['tool']) ?></td>
                <td><?= e($row['label']) ?></td><td class="n"><?= e($row['headline']) ?></td></tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
<?php endif; ?>
