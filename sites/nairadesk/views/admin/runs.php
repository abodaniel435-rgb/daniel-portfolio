<?php declare(strict_types=1); ?>
<p class="eyebrow">Administrator</p>
<h1 style="font-size:clamp(1.7rem,3.2vw,2.4rem)">All pay runs</h1>
<p class="lede" style="max-width:64ch">Every frozen month across every workspace, newest first. The line detail belongs to the company that made it, so it is read from that account — this screen is the aggregate view.</p>

<div class="table-wrap" style="margin-top:24px">
  <table>
    <thead>
      <tr><th>Run</th><th>Company</th><th>Period</th><th class="n">Heads</th><th class="n">Gross</th><th class="n">PAYE</th><th class="n">Net</th><th class="n">Employer cost</th><th>Owner</th><th>Created</th></tr>
    </thead>
    <tbody>
    <?php foreach ($rows as $row): ?>
      <tr>
        <td class="n">#<?= (int)$row['id'] ?></td>
        <td><strong><?= e($row['company']) ?></strong></td>
        <td><?= e($row['period']) ?></td>
        <td class="n"><?= (int)$row['headcount'] ?></td>
        <td class="n"><?= e(money($row['gross_total'])) ?></td>
        <td class="n"><?= e(money($row['paye_total'])) ?></td>
        <td class="n"><?= e(money($row['net_total'])) ?></td>
        <td class="n"><?= e(money($row['employer_cost_total'])) ?></td>
        <td><?= e($row['owner']) ?></td>
        <td style="white-space:nowrap"><?= e(short_datetime($row['created_at'])) ?></td>
      </tr>
    <?php endforeach; ?>
    <?php if (!$rows): ?><tr><td colspan="10">No pay runs have been created yet.</td></tr><?php endif; ?>
    </tbody>
  </table>
</div>
