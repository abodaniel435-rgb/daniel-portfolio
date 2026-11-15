<?php declare(strict_types=1); ?>
<p class="eyebrow"><?= $company ? e($company['name']) : 'No workspace' ?></p>
<h1 style="font-size:clamp(1.7rem,3.2vw,2.4rem)">Pay runs</h1>

<?php if ($company): ?>
<div class="split-page" style="margin-top:24px">
  <div class="panel">
    <h3>Run this month</h3>
    <form method="post" action="<?= e(url('/runs')) ?>">
      <?= csrf_field() ?>
      <div class="field"><label for="period">Period</label>
        <input id="period" name="period" type="month" required value="<?= e(date('Y-m')) ?>"></div>
      <p class="err"><?= field_error('period') ?></p>
      <p style="font-size:.86rem;color:var(--ink-3)">Every person on the roster is priced with the statutory table as it stands right now, and the result is frozen into the run.</p>
      <button class="btn btn-primary" type="submit">Create run</button>
    </form>
  </div>
  <div>
    <h2>History</h2>
    <div class="table-wrap">
      <table>
        <thead><tr><th>Period</th><th class="n">Heads</th><th class="n">Gross</th><th class="n">PAYE</th><th class="n">Net</th><th class="n">Employer cost</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($runs as $run): ?>
          <tr>
            <td><a href="<?= e(url('/runs/' . $run['id'])) ?>"><?= e($run['period']) ?></a></td>
            <td class="n"><?= (int)$run['headcount'] ?></td>
            <td class="n"><?= e(money($run['gross_total'])) ?></td>
            <td class="n"><?= e(money($run['paye_total'])) ?></td>
            <td class="n"><?= e(money($run['net_total'])) ?></td>
            <td class="n"><?= e(money($run['employer_cost_total'])) ?></td>
            <td class="n">
              <form method="post" action="<?= e(url('/runs/' . $run['id'] . '/delete')) ?>" class="inlineform"
                    onsubmit="return confirm('Delete the <?= e($run['period']) ?> run and every line in it?')">
                <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$run['id'] ?>">
                <button class="btn btn-ghost btn-sm" type="submit">Delete</button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
        <?php if (!$runs): ?><tr><td colspan="7">No runs yet.</td></tr><?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
<?php else: ?>
  <div class="panel"><p>Create a workspace from the <a href="<?= e(url('/dashboard')) ?>">dashboard</a> first.</p></div>
<?php endif; ?>
