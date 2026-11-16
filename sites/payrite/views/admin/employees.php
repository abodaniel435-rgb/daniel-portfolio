<?php
declare(strict_types=1);
/** @var array $employees @var array $cost @var string $status @var array $counts */
require BASE_PATH . '/views/admin/nav.php';
$filters = ['' => 'All', 'active' => 'Active', 'paused' => 'Paused', 'left' => 'Left'];
?>
<section class="section" style="margin-top:0">
  <div class="section-head">
    <div>
      <p class="eyebrow">Roster</p>
      <h1>Everyone the run will payslip</h1>
      <p class="lede">An employee row is the payroll input: the four components of the package, the rent on file,
        whether relief is claimed, the pension category and the status that decides whether this month includes
        them. The projected columns below are computed on this request, not stored.</p>
    </div>
    <a class="btn btn-primary" href="<?= e(url('/admin/employees/new')) ?>">Add someone</a>
  </div>

  <div class="grid-4" style="margin-bottom:22px">
    <div class="stat"><b><?= (int)$counts['active'] ?></b><span>active</span></div>
    <div class="stat"><b><?= (int)$counts['paused'] ?></b><span>paused</span></div>
    <div class="stat"><b><?= (int)$counts['left'] ?></b><span>left</span></div>
    <div class="stat stat-mint"><b><?= e(money((float)$cost['cost'])) ?></b><span>total employer cost / month</span></div>
  </div>

  <nav class="tabs" aria-label="Filter by status">
    <?php foreach ($filters as $key => $label): ?>
      <a class="chip<?= $status === $key ? ' is-active' : '' ?>"
         href="<?= e(url('/admin/employees')) ?><?= $key !== '' ? '?status=' . $key : '' ?>"><?= e($label) ?>
        <?= $key !== '' ? '(' . (int)$counts[$key] . ')' : '' ?></a>
    <?php endforeach; ?>
  </nav>

  <?php if (!$employees): ?>
    <div class="verdict warn">
      <h4><?= $status === '' ? 'Nobody on the roster yet' : 'No ' . e($status) . ' employees' ?></h4>
      <p>A payroll run only includes rows with status <span class="mono">active</span>, which is how a leaver stops
        being paid without their history being deleted. Their slips stay so the year's totals still add up.</p>
    </div>
  <?php else: ?>
    <table class="table">
      <thead>
        <tr><th>Name</th><th>Dept.</th><th class="num">Gross</th><th class="num">PAYE</th><th class="num">Net</th>
          <th class="num">Cost</th><th>Slip login</th><th>Status</th><th></th></tr>
      </thead>
      <tbody>
        <?php foreach ($employees as $row): ?>
          <tr>
            <td><a href="<?= e(url('/admin/employees/' . (int)$row['id'] . '/edit')) ?>"><b><?= e((string)$row['name']) ?></b></a>
              <div class="tiny mono"><?= e((string)$row['staff_no']) ?></div>
              <?php if ((string)$row['query_note'] !== ''): ?>
                <div class="tiny" style="color:var(--brass-dark)">query: <?= e(mb_strimwidth((string)$row['query_note'], 0, 60, '…')) ?></div>
              <?php endif; ?></td>
            <td class="tiny"><?= e((string)($row['department'] ?: 'General')) ?></td>
            <td class="num"><?= e(money((float)$row['gross'])) ?></td>
            <td class="num"><?= e(money((float)$row['paye'])) ?></td>
            <td class="num"><b><?= e(money((float)$row['net'])) ?></b></td>
            <td class="num"><?= e(money((float)$row['cost'])) ?></td>
            <td><?= (int)($row['user_id'] ?? 0) > 0
                ? '<span class="badge badge-ok">linked</span>'
                : '<a class="badge badge-neutral" href="' . e(url('/admin/staff')) . '">none</a>' ?></td>
            <td><span class="badge badge-<?= $row['status'] === 'active' ? 'ok' : ($row['status'] === 'paused' ? 'warn' : 'neutral') ?>"><?= e((string)$row['status']) ?></span></td>
            <td>
              <div style="display:flex;gap:8px;align-items:center">
                <a class="btn btn-sm btn-ghost" href="<?= e(url('/admin/employees/' . (int)$row['id'] . '/edit')) ?>">Edit</a>
                <?php if ($row['status'] === 'active'): ?>
                  <form method="post" action="<?= e(url('/admin/employees/' . (int)$row['id'] . '/status')) ?>" class="inlineform">
                    <?= csrf_field() ?><input type="hidden" name="status" value="paused">
                    <button class="btn btn-sm btn-ghost" type="submit">Pause</button>
                  </form>
                <?php elseif ($row['status'] === 'paused'): ?>
                  <form method="post" action="<?= e(url('/admin/employees/' . (int)$row['id'] . '/status')) ?>" class="inlineform">
                    <?= csrf_field() ?><input type="hidden" name="status" value="active">
                    <button class="btn btn-sm btn-ok" type="submit">Resume</button>
                  </form>
                <?php else: ?>
                  <form method="post" action="<?= e(url('/admin/employees/' . (int)$row['id'] . '/status')) ?>" class="inlineform"
                        onsubmit="return confirm('Bring <?= e(addslashes((string)$row['name'])) ?> back onto the roster?') ">
                    <?= csrf_field() ?><input type="hidden" name="status" value="active">
                    <button class="btn btn-sm btn-ghost" type="submit">Reinstate</button>
                  </form>
                <?php endif; ?>
              </div>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
      <tfoot>
        <tr>
          <td colspan="2"><?= count($employees) ?> shown</td>
          <td class="num"><?= e(money((float)$cost['gross'])) ?></td>
          <td class="num"><?= e(money((float)$cost['paye'])) ?></td>
          <td class="num"><?= e(money((float)$cost['net'])) ?></td>
          <td class="num"><?= e(money((float)$cost['cost'])) ?></td>
          <td colspan="3" class="tiny">If everyone active were payslip this month.</td>
        </tr>
      </tfoot>
    </table>
    <p class="tiny">Changing a package here does not touch a run that has already been built. Build a draft, check it,
      and void the old one if the month has to be recalculated.</p>
  <?php endif; ?>
</section>
