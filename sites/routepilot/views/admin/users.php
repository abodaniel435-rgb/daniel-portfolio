<div class="portal">
  <?php include BASE_PATH . '/views/partials/portal_nav.php'; ?>
  <div class="portal-main">
    <div class="page-head">
      <div>
        <h1>Users &amp; couriers</h1>
        <p><?= (int)$page['total'] ?> account<?= (int)$page['total'] === 1 ? '' : 's' ?> · dispatchers and admins are created here, riders may also self-register.</p>
      </div>
    </div>

    <div class="panel">
      <div class="panel-head"><div><h2>Create an account</h2><p class="sub">Share the temporary password securely — the user should change it after first sign-in.</p></div></div>
      <form method="post" action="<?= e(url('/admin/users')) ?>" class="form-grid">
        <?= csrf_field() ?>
        <div class="form-row-3">
          <div>
            <label for="name">Full name</label>
            <input id="name" name="name" value="<?= e(old('name')) ?>" required maxlength="120" placeholder="Tola Adeyemi">
            <?= field_error('name') ?>
          </div>
          <div>
            <label for="email">Email</label>
            <input id="email" name="email" type="email" value="<?= e(old('email')) ?>" required placeholder="staff@routepilot.test">
            <?= field_error('email') ?>
          </div>
          <div>
            <label for="phone">Phone</label>
            <input id="phone" name="phone" value="<?= e(old('phone')) ?>" placeholder="+234 802 000 0000">
          </div>
        </div>
        <div class="form-row-3">
          <div>
            <label for="role">Role</label>
            <select id="role" name="role" required>
              <option value="dispatcher" <?= old('role') === 'dispatcher' ? 'selected' : '' ?>>Dispatcher — full dispatch desk</option>
              <option value="courier" <?= old('role', 'courier') === 'courier' ? 'selected' : '' ?>>Courier — assigned parcels only</option>
              <option value="admin" <?= old('role') === 'admin' ? 'selected' : '' ?>>Administrator — full access</option>
            </select>
            <?= field_error('role') ?>
          </div>
          <div>
            <label for="password">Temporary password</label>
            <input id="password" name="password" type="text" required minlength="10" placeholder="At least 10 characters">
            <?= field_error('password') ?>
          </div>
          <div>
            <label for="zone">Courier zone <span class="muted small">(riders only)</span></label>
            <input id="zone" name="zone" value="<?= e(old('zone', 'Lagos Mainland')) ?>" list="rp-zones" placeholder="Lagos Island">
            <?= field_error('zone') ?>
          </div>
        </div>
        <div class="form-row-3">
          <div>
            <label for="vehicle">Vehicle <span class="muted small">(riders only)</span></label>
            <select id="vehicle" name="vehicle">
              <?php foreach ($vehicles as $vehicle): ?>
                <option value="<?= e($vehicle) ?>" <?= old('vehicle', 'motorcycle') === $vehicle ? 'selected' : '' ?>><?= e(ucfirst($vehicle)) ?></option>
              <?php endforeach; ?>
            </select>
            <?= field_error('vehicle') ?>
          </div>
          <div>
            <label for="plate_number">Plate number <span class="muted small">(optional)</span></label>
            <input id="plate_number" name="plate_number" value="<?= e(old('plate_number')) ?>" placeholder="LSD-482-XA">
          </div>
          <div style="display:flex;align-items:flex-end">
            <button class="btn btn-primary btn-block" type="submit">Create account</button>
          </div>
        </div>
        <datalist id="rp-zones">
          <option value="Lagos Island"></option>
          <option value="Lagos Mainland"></option>
          <option value="Lekki / Ajah"></option>
          <option value="Ikeja / Maryland"></option>
          <option value="Surulere / Yaba"></option>
        </datalist>
        <p class="hint">Courier zone and vehicle are required when creating a rider; they are ignored for other roles.</p>
      </form>
    </div>

    <div class="panel">
      <form class="toolbar" method="get" action="<?= e(url('/admin/users')) ?>">
        <input class="grow" type="search" name="q" value="<?= e($q) ?>" placeholder="Search name or email">
        <select name="role" data-autosubmit aria-label="Filter by role">
          <option value="">All roles</option>
          <?php foreach (['dispatcher', 'courier', 'admin'] as $option): ?>
            <option value="<?= e($option) ?>" <?= $role === $option ? 'selected' : '' ?>><?= e(ucfirst($option)) ?></option>
          <?php endforeach; ?>
        </select>
        <button class="btn btn-ghost btn-sm" type="submit">Filter</button>
        <a class="btn btn-ghost btn-sm" href="<?= e(url('/admin/users')) ?>">Reset</a>
      </form>

      <?php if (!$users): ?>
        <div class="empty"><strong>No accounts match</strong>Adjust the filters or create the account above.</div>
      <?php else: ?>
        <div class="table-wrap">
          <table class="data">
            <thead><tr><th>Name</th><th>Role</th><th>Courier profile</th><th>Open parcels</th><th>Created</th><th>Status</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($users as $row): ?>
              <tr>
                <td>
                  <span class="cell-strong"><?= e($row['name']) ?></span>
                  <span class="cell-sub"><?= e($row['email']) ?><?= $row['phone'] ? ' · ' . e($row['phone']) : '' ?></span>
                </td>
                <td><span class="role-chip"><?= e($row['role']) ?></span></td>
                <td>
                  <?php if ($row['zone']): ?>
                    <?= e(ucfirst((string)$row['vehicle'])) ?> · <?= e($row['zone']) ?>
                    <span class="cell-sub"><?= (int)$row['is_available'] === 1 ? 'Available' : 'Off duty' ?></span>
                  <?php else: ?>
                    <span class="muted">—</span>
                  <?php endif; ?>
                </td>
                <td class="num"><?= $row['zone'] ? (int)$row['open_shipments'] : '—' ?></td>
                <td><?= e(short_date($row['created_at'])) ?><span class="cell-sub"><?= $row['last_login_at'] ? 'Last in ' . e(rp_time_ago($row['last_login_at'])) : 'Never signed in' ?></span></td>
                <td><?= (int)$row['is_active'] === 1 ? '<span class="pill pill-done">Active</span>' : '<span class="pill pill-fail">Disabled</span>' ?></td>
                <td class="num">
                  <?php if ((int)$row['id'] !== Auth::id()): ?>
                    <form method="post" action="<?= e(url('/admin/users/' . $row['id'] . '/toggle')) ?>" class="inline-form" data-confirm="<?= (int)$row['is_active'] === 1 ? 'Deactivate this account?' : 'Reactivate this account?' ?>">
                      <?= csrf_field() ?>
                      <button class="btn btn-ghost btn-xs" type="submit"><?= (int)$row['is_active'] === 1 ? 'Deactivate' : 'Reactivate' ?></button>
                    </form>
                  <?php else: ?>
                    <span class="tag">You</span>
                  <?php endif; ?>
                </td>
              </tr>
            <?php endforeach; ?>
            </tbody>
          </table>
        </div>

        <?php if ($page['pages'] > 1): ?>
          <nav class="pagination" aria-label="User pages">
            <?php for ($i = 1; $i <= $page['pages']; $i++): ?>
              <?php if ($i === $page['current']): ?>
                <span class="current"><?= $i ?></span>
              <?php else: ?>
                <a href="<?= e(url('/admin/users?' . http_build_query(array_filter(['q' => $q, 'role' => $role, 'page' => $i])))) ?>"><?= $i ?></a>
              <?php endif; ?>
            <?php endfor; ?>
          </nav>
        <?php endif; ?>
      <?php endif; ?>
    </div>
  </div>
</div>
