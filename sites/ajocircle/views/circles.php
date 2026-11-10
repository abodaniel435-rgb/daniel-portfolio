<section class="section" style="padding-bottom:36px">
  <div class="page-head" style="margin-bottom:26px">
    <div>
      <p class="eyebrow">Circle directory</p>
      <h1 style="font-size:clamp(1.9rem,3.6vw,2.8rem);margin:0">Circles looking for members</h1>
      <p>Every circle shows its contribution, frequency, capacity and start date before you join.</p>
    </div>
    <?php if (Auth::check()): ?>
      <a class="btn btn-ghost" href="<?= e(url(Auth::role() === 'admin' ? '/admin/circles' : '/member/circles')) ?>">My circles</a>
    <?php endif; ?>
  </div>

  <form class="toolbar" method="get" action="<?= e(url('/circles')) ?>">
    <input type="search" name="q" value="<?= e($search) ?>" placeholder="Search by name or description" aria-label="Search circles">
    <select name="frequency" aria-label="Frequency">
      <option value="">Any frequency</option>
      <?php foreach (ajo_frequencies() as $value => $label): ?>
        <option value="<?= e($value) ?>" <?= $frequency === $value ? 'selected' : '' ?>><?= e($label) ?></option>
      <?php endforeach; ?>
    </select>
    <select name="status" aria-label="Status">
      <option value="">Any status</option>
      <?php foreach (['open' => 'Open', 'active' => 'Active', 'completed' => 'Completed'] as $value => $label): ?>
        <option value="<?= e($value) ?>" <?= $status === $value ? 'selected' : '' ?>><?= e($label) ?></option>
      <?php endforeach; ?>
    </select>
    <button class="btn btn-dark btn-sm" type="submit">Filter</button>
    <?php if ($search !== '' || $frequency !== '' || $status !== ''): ?>
      <a class="btn btn-ghost btn-sm" href="<?= e(url('/circles')) ?>">Reset</a>
    <?php endif; ?>
  </form>

  <?php if (!$circles): ?>
    <div class="empty"><strong>No circles match that search</strong>Try a different frequency or clear the filters.</div>
  <?php else: ?>
    <div class="grid grid-3">
      <?php foreach ($circles as $circle): ?>
        <?php $slotsLeft = max(0, (int)$circle['member_limit'] - (int)$circle['member_count']); ?>
        <article class="circle-card reveal">
          <div class="circle-top">
            <div>
              <h3><a href="<?= e(url('/circles/' . $circle['id'])) ?>" style="color:inherit"><?= e($circle['name']) ?></a></h3>
              <span class="circle-cat"><?= e($circle['category']) ?></span>
            </div>
            <?= pill($circle['status']) ?>
          </div>
          <div class="circle-money">
            <b><?= money((float)$circle['contribution_amount']) ?></b>
            <span>/ <?= e(strtolower(frequency_label($circle['frequency']))) ?></span>
          </div>
          <p class="circle-desc"><?= e($circle['description']) ?></p>
          <div class="bar-label">
            <span><?= (int)$circle['member_count'] ?>/<?= (int)$circle['member_limit'] ?> members</span>
            <span><?= $slotsLeft ?> slot<?= $slotsLeft === 1 ? '' : 's' ?> left</span>
          </div>
          <div class="bar"><i style="width:<?= (int)round((int)$circle['member_count'] / max(1, (int)$circle['member_limit']) * 100) ?>%"></i></div>
          <div class="meta-row">
            <span><?= e($circle['payout_mode'] === 'fixed' ? 'Fixed payout order' : 'Rotating order') ?></span>
            <span>Starts <?= e(short_date($circle['start_date'])) ?></span>
          </div>
          <div class="circle-foot">
            <span style="font-size:.78rem;color:var(--muted);font-weight:700">Pool per round: <?= money((float)$circle['contribution_amount'] * max(1, (int)$circle['member_count'])) ?></span>
            <a class="btn btn-soft btn-sm" href="<?= e(url('/circles/' . $circle['id'])) ?>">Details</a>
          </div>
        </article>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</section>
