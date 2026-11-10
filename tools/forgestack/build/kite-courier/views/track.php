<?php $site = site_config(); ?>
<section class="page-head page-head-compact">
  <div class="page-head-inner">
    <p class="eyebrow"><?= e($site['labels']['track']) ?></p>
    <h1><?= e($site['labels']['track_title']) ?></h1>
    <p class="page-lede"><?= e($site['labels']['track_intro']) ?></p>
  </div>
</section>

<section class="section section-form">
  <div class="track-shell">
    <form class="track-form card" method="get" action="<?= e(url('/track')) ?>">
      <label for="ref"><?= e($site['labels']['reference']) ?></label>
      <div class="track-input">
        <input id="ref" name="ref" value="<?= e($reference) ?>" placeholder="<?= e(cfg('labels.txn_ref')) ?>-000000-0000" required>
        <button class="btn btn-primary" type="submit">Find</button>
      </div>
    </form>

    <?php if ($reference !== '' && !$record): ?>
      <div class="alert alert-error">No request matches <strong><?= e($reference) ?></strong>. Check the reference in your confirmation email, or <a href="<?= e(url('/contact')) ?>">contact the team</a>.</div>
    <?php endif; ?>

    <?php if ($record): ?>
      <?php
        $first = explode(' ', trim((string)$record['customer_name']))[0];
        $paymentsOn = (bool)cfg('modules.payments', false);
      ?>
      <?php if ($isNew): ?>
        <div class="alert alert-success">Your request is logged. Keep the reference <strong><?= e($record['reference']) ?></strong> — a confirmation email is on its way.</div>
      <?php endif; ?>

      <div class="track-card card">
        <div class="track-head">
          <div>
            <span class="track-ref"><?= e($site['labels']['reference']) ?></span>
            <strong class="mono"><?= e($record['reference']) ?></strong>
          </div>
          <?= status_badge_html((string)$record['status']) ?>
        </div>

        <dl class="detail-grid">
          <div><dt>Name</dt><dd><?= e($first) ?> (privacy)</dd></div>
          <div><dt><?= e($site['labels']['date']) ?></dt><dd><?= e(short_date($record['request_date'])) ?></dd></div>
          <div><dt><?= e($site['labels']['time']) ?></dt><dd><?= e(formatted_time($record['request_time'])) ?></dd></div>
          <div><dt><?= e($site['labels']['choice_a']) ?></dt><dd><?= e($record['choice_a']) ?></dd></div>
          <div><dt><?= e($site['labels']['choice_b']) ?></dt><dd><?= e($record['choice_b'] ?: 'First available') ?></dd></div>
          <div><dt><?= e($site['labels']['quantity']) ?></dt><dd><?= (int)$record['quantity'] ?> <?= e(quantity_unit()) ?></dd></div>
          <?php if ($paymentsOn): ?>
            <div><dt>Amount</dt><dd><?= money_fmt((float)$record['amount']) ?></dd></div>
            <div><dt>Payment</dt><dd><?= $record['payment_status'] === 'paid' ? 'Paid — thank you' : e(ucfirst((string)$record['payment_status'])) ?></dd></div>
          <?php endif; ?>
          <div><dt>Requested</dt><dd><?= e(short_datetime($record['created_at'])) ?></dd></div>
        </dl>

        <h3 class="track-timeline-title">Timeline</h3>
        <ol class="timeline">
          <?php foreach ($timeline as $event): ?>
            <li class="timeline-item">
              <span class="timeline-dot" aria-hidden="true"></span>
              <div>
                <strong><?= e(status_label((string)$event['status'])) ?></strong>
                <span><?= e($event['note']) ?> · <?= e(short_datetime($event['created_at'])) ?> · <?= e($event['source']) ?></span>
              </div>
            </li>
          <?php endforeach; ?>
        </ol>

        <p class="track-note"><?= e($site['labels']['public_note']) ?></p>
      </div>
    <?php elseif ($reference === ''): ?>
      <div class="track-empty">
        <p class="eyebrow">What you will see</p>
        <h2>The live status, not a guess.</h2>
        <p>Every request writes a timeline: received, confirmed, then completed or closed. The page shows the first name only — never contact details.</p>
        <a class="text-link" href="<?= e(url('/book')) ?>">Don’t have a reference? <?= e($site['labels']['book']) ?> →</a>
      </div>
    <?php endif; ?>
  </div>
</section>
