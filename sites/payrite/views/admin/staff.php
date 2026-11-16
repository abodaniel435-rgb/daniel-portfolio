<?php
declare(strict_types=1);
/** @var array $accounts @var array $unlinked @var array $employees */
require BASE_PATH . '/views/admin/nav.php';
$orphans = 0;
foreach ($accounts as $account) {
    if ((int)($account['employee_id'] ?? 0) === 0) {
        $orphans++;
    }
}
?>
<section class="section" style="margin-top:0">
  <div class="section-head">
    <div>
      <p class="eyebrow">Staff logins</p>
      <h1>Who can sign in, and what they can see</h1>
      <p class="lede">Registration makes an account; you make the connection between that account and a roster row.
        Until the two are linked the person can sign in and see the explanation on their own pending page, and
        nothing else — a payslip is only ever reachable through a link you set here.</p>
    </div>
    <a class="btn btn-ghost" href="<?= e(url('/register')) ?>">Registration page</a>
  </div>

  <div class="grid-3" style="margin-bottom:24px">
    <div class="stat"><b><?= count($accounts) ?></b><span>staff accounts</span></div>
    <div class="stat stat-red"><b><?= $orphans ?></b><span>not linked to a record</span></div>
    <div class="stat"><b><?= count($unlinked) ?></b><span>roster rows without a login</span></div>
  </div>

  <?php if (!$accounts): ?>
    <div class="verdict info">
      <h4>No staff accounts yet</h4>
      <p>Anyone can register at <span class="mono">/register</span> with a work email. They land here, unlinked, and
        you decide which roster row they belong to. The administrator account is not listed and cannot be disabled
        from this page.</p>
    </div>
  <?php else: ?>
    <table class="table">
      <thead>
        <tr><th>Account</th><th>Email</th><th>Last signed in</th><th>Roster row</th><th>Access</th><th>Link</th></tr>
      </thead>
      <tbody>
        <?php foreach ($accounts as $account): ?>
          <?php $id = (int)$account['id']; $employeeId = (int)($account['employee_id'] ?? 0); ?>
          <tr>
            <td><b><?= e((string)$account['name']) ?></b>
              <div class="tiny mono">user #<?= $id ?><?= (string)($account['phone'] ?? '') !== '' ? ' · ' . e((string)$account['phone']) : '' ?></div></td>
            <td class="tiny mono"><?= e((string)$account['email']) ?></td>
            <td class="tiny"><?= !empty($account['last_login_at']) ? e(short_datetime((string)$account['last_login_at'])) : 'never' ?></td>
            <td>
              <?php if ($employeeId > 0): ?>
                <a href="<?= e(url('/admin/employees/' . $employeeId . '/edit')) ?>"><b><?= e((string)$account['employee_name']) ?></b></a>
                <div class="tiny mono"><?= e((string)$account['staff_no']) ?> · <?= e((string)$account['employee_status']) ?></div>
              <?php else: ?>
                <span class="badge badge-warn">unlinked</span>
              <?php endif; ?>
            </td>
            <td><span class="badge badge-<?= (int)$account['is_active'] === 1 ? 'ok' : 'bad' ?>"><?= (int)$account['is_active'] === 1 ? 'active' : 'disabled' ?></span></td>
            <td>
              <div style="display:flex;gap:8px;flex-wrap:wrap;align-items:center">
                <form method="post" action="<?= e(url('/admin/staff/' . $id . '/status')) ?>" class="inlineform">
                  <?= csrf_field() ?>
                  <button class="btn btn-sm <?= (int)$account['is_active'] === 1 ? 'btn-ghost' : 'btn-ok' ?>" type="submit">
                    <?= (int)$account['is_active'] === 1 ? 'Disable' : 'Enable' ?></button>
                </form>
                <form method="post" action="<?= e(url('/admin/staff/' . $id . '/link')) ?>">
                  <?= csrf_field() ?>
                  <div style="display:flex;gap:6px">
                    <select name="employee_id" aria-label="Roster row for <?= e((string)$account['name']) ?>">
                      <option value="0">— choose —</option>
                      <?php foreach ($employees as $employee): ?>
                        <option value="<?= (int)$employee['id'] ?>" <?= $employeeId === (int)$employee['id'] ? 'selected' : '' ?>>
                          <?= e((string)$employee['name']) ?> (<?= e((string)$employee['staff_no']) ?>)
                        </option>
                      <?php endforeach; ?>
                    </select>
                    <button class="btn btn-sm btn-primary" type="submit">Link</button>
                  </div>
                </form>
              </div>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  <?php endif; ?>

  <?php if ($unlinked): ?>
    <div class="panel" style="margin-top:26px">
      <h3>Roster rows still without a login</h3>
      <p class="tiny" style="margin-top:0">These people are paid but cannot read their own payslips. Either they have
        not registered yet, or they registered and you have not joined them up.</p>
      <div style="display:flex;gap:8px;flex-wrap:wrap;margin-top:10px">
        <?php foreach ($unlinked as $row): ?>
          <span class="chip"><?= e((string)$row['name']) ?> <span class="mono muted"><?= e((string)$row['staff_no']) ?></span></span>
        <?php endforeach; ?>
      </div>
    </div>
  <?php endif; ?>

  <div class="grid-2" style="margin-top:26px">
    <div class="card">
      <h3>Disabling is not deleting</h3>
      <p class="tiny">A disabled account cannot sign in, and its <span class="mono">is_active = 0</span> is checked on
        every attempt. The roster row and every payslip already issued to that person stay, because the year's PAYE
        totals still need them.</p>
    </div>
    <div class="card">
      <h3>Re-linking moves, never duplicates</h3>
      <p class="tiny">Linking an account to a second roster row first clears the old link on both sides, so one
        person can never end up with two records reading the same slips.</p>
    </div>
  </div>
</section>
