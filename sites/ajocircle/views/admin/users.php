<div class="portal">
  <?php include BASE_PATH . '/views/partials/portal_nav.php'; ?>
  <div class="portal-main">
    <div class="page-head">
      <div>
        <h1>Members and accounts</h1>
        <p>Everyone with access to AjoCircle. Suspending an account blocks sign-in without deleting their ledger.</p>
      </div>
      <form class="toolbar" method="get" action="<?= e(url('/admin/users')) ?>" style="margin:0">
        <input type="search" name="q" value="<?= e($search) ?>" placeholder="Name, email or phone">
        <button class="btn btn-dark btn-sm" type="submit">Search</button>
      </form>
    </div>

    <div class="panel">
      <div class="panel-head">
        <h2>All accounts</h2>
        <span class="badge badge-neutral"><?= count($users) ?> shown</span>
      </div>
      <div class="table-wrap">
        <table class="data">
          <thead><tr><th>Name</th><th>Contact</th><th>Role</th><th>Circles</th><th class="right">Verified savings</th><th>Last sign-in</th><th>Status</th><th></th></tr></thead>
          <tbody>
          <?php foreach ($users as $user): ?>
            <tr>
              <td><strong><?= e($user['name']) ?></strong><span class="sub">Joined <?= e(short_date($user['created_at'])) ?></span></td>
              <td><?= e($user['email']) ?><span class="sub mono"><?= e($user['phone'] ?: '—') ?></span></td>
              <td><span class="badge <?= $user['role'] === 'admin' ? 'badge-dark' : 'badge-info' ?>"><?= e(ucfirst($user['role'])) ?></span></td>
              <td><?= (int)$user['circle_count'] ?></td>
              <td class="right num"><?= money((float)$user['saved']) ?></td>
              <td class="nowrap"><?= e(short_datetime($user['last_login_at'])) ?></td>
              <td><span class="badge <?= (int)$user['is_active'] === 1 ? 'badge-ok' : 'badge-bad' ?>"><?= (int)$user['is_active'] === 1 ? 'Active' : 'Suspended' ?></span></td>
              <td class="right">
                <?php if ((int)$user['id'] === (int)Auth::id()): ?>
                  <span class="sub">You</span>
                <?php else: ?>
                  <form method="post" action="<?= e(url('/admin/users/' . $user['id'] . '/toggle')) ?>" data-confirm="<?= (int)$user['is_active'] === 1 ? 'Suspend' : 'Reactivate' ?> <?= e($user['name']) ?>?">
                    <?= csrf_field() ?>
                    <button class="btn <?= (int)$user['is_active'] === 1 ? 'btn-danger' : 'btn-soft' ?> btn-sm" type="submit">
                      <?= (int)$user['is_active'] === 1 ? 'Suspend' : 'Reactivate' ?>
                    </button>
                  </form>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>
