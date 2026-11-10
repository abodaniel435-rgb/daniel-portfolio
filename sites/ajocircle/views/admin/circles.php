<div class="portal">
  <?php include BASE_PATH . '/views/partials/portal_nav.php'; ?>
  <div class="portal-main">
    <div class="page-head">
      <div>
        <h1>Circles</h1>
        <p>Create a circle, open it to members, activate the rotation and close it when every payout is settled.</p>
      </div>
      <a class="btn btn-ghost btn-sm" href="<?= e(url('/circles')) ?>">Public directory</a>
    </div>

    <div class="panel">
      <div class="panel-head">
        <h2>Create a circle</h2>
        <span class="badge badge-neutral">Opens immediately</span>
      </div>
      <form method="post" action="<?= e(url('/admin/circles')) ?>" class="form-grid">
        <?= csrf_field() ?>
        <div class="form-row">
          <div>
            <label for="name">Circle name</label>
            <input id="name" name="name" type="text" required maxlength="140" value="<?= e(old('name')) ?>" placeholder="Surulere Tailors Ajo">
            <?= field_error('name') ?>
          </div>
          <div>
            <label for="category">Category</label>
            <select id="category" name="category">
              <?php foreach (ajo_categories() as $category): ?>
                <option value="<?= e($category) ?>"><?= e($category) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>
        <div>
          <label for="description">Description</label>
          <textarea id="description" name="description" required maxlength="2000" placeholder="Who is this circle for, how often do members pay, and who verifies contributions?"><?= e(old('description')) ?></textarea>
          <?= field_error('description') ?>
        </div>
        <div class="form-row-3">
          <div>
            <label for="contribution_amount">Contribution (₦)</label>
            <input id="contribution_amount" name="contribution_amount" type="number" min="500" step="100" required value="<?= e(old('contribution_amount', '10000')) ?>">
            <p class="hint">Amount each member pays every round.</p>
          </div>
          <div>
            <label for="frequency">Frequency</label>
            <select id="frequency" name="frequency" required>
              <option value="weekly">Weekly</option>
              <option value="monthly" selected>Monthly</option>
            </select>
          </div>
          <div>
            <label for="member_limit">Member limit</label>
            <input id="member_limit" name="member_limit" type="number" min="2" max="52" required value="<?= e(old('member_limit', '12')) ?>">
            <p class="hint">Also the number of rounds.</p>
          </div>
        </div>
        <div class="form-row">
          <div>
            <label for="start_date">Start date (round 1 due)</label>
            <input id="start_date" name="start_date" type="date" required value="<?= e(old('start_date', date('Y-m-d', strtotime('+14 days')))) ?>">
            <?= field_error('start_date') ?>
          </div>
          <div>
            <label for="owner_id">Circle owner (optional)</label>
            <select id="owner_id" name="owner_id">
              <option value="">Administrators only</option>
              <?php foreach ($owners as $owner): ?>
                <option value="<?= (int)$owner['id'] ?>"><?= e($owner['name']) ?> · <?= e($owner['email']) ?></option>
              <?php endforeach; ?>
            </select>
            <p class="hint">An owner can verify contributions and schedule payouts for this circle.</p>
          </div>
        </div>
        <div>
          <button class="btn btn-primary" type="submit">Create circle</button>
        </div>
      </form>
    </div>

    <div class="panel">
      <div class="panel-head">
        <h2>All circles</h2>
        <form method="get" action="<?= e(url('/admin/circles')) ?>" class="toolbar" style="margin:0">
          <select name="status" aria-label="Status">
            <option value="">Any status</option>
            <?php foreach (['open' => 'Open', 'active' => 'Active', 'completed' => 'Completed'] as $value => $label): ?>
              <option value="<?= e($value) ?>" <?= $status === $value ? 'selected' : '' ?>><?= e($label) ?></option>
            <?php endforeach; ?>
          </select>
          <button class="btn btn-dark btn-sm" type="submit">Filter</button>
        </form>
      </div>

      <?php if (!$circles): ?>
        <div class="empty"><strong>No circles yet</strong>Create the first circle using the form above.</div>
      <?php else: ?>
        <div class="table-wrap">
          <table class="data">
            <thead><tr><th>Circle</th><th>Status</th><th>Members</th><th class="right">Contribution</th><th class="right">Collected</th><th>Start</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($circles as $circle): ?>
              <tr>
                <td><strong><?= e($circle['name']) ?></strong><span class="sub"><?= e($circle['category']) ?> · <?= e(frequency_label($circle['frequency'])) ?> · <?= (int)$circle['member_limit'] ?> rounds</span></td>
                <td><?= pill($circle['status']) ?></td>
                <td><?= (int)$circle['member_count'] ?>/<?= (int)$circle['member_limit'] ?></td>
                <td class="right num"><?= money((float)$circle['contribution_amount']) ?></td>
                <td class="right num"><?= money((float)$circle['collected']) ?></td>
                <td class="nowrap"><?= e(short_date($circle['start_date'])) ?></td>
                <td class="right"><a class="btn btn-ghost btn-sm" href="<?= e(url('/admin/circles/' . $circle['id'])) ?>">Manage</a></td>
              </tr>
            <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </div>
  </div>
</div>
