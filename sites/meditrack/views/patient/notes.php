<div class="portal">
  <?php include BASE_PATH . '/views/partials/portal_nav.php'; ?>
  <div class="portal-main">
    <div class="page-head">
      <div>
        <h1>Consultation notes</h1>
        <p>Summaries from your completed visits. Keep them for your records.</p>
      </div>
    </div>

    <?php if (!$notes): ?>
      <div class="empty"><strong>No consultation notes yet</strong>Notes appear here after a visit is completed by your doctor.</div>
    <?php else: foreach ($notes as $note): ?>
      <article class="panel">
        <div class="panel-head">
          <div>
            <h2 style="margin-bottom:2px"><?= e($note['diagnosis']) ?></h2>
            <p style="margin:0;color:var(--muted);font-size:.86rem"><?= e($note['doctor_name']) ?> · <?= e($note['specialty']) ?> · <?= e(short_date($note['appointment_date'])) ?></p>
          </div>
          <span class="badge badge-ok"><?= e($note['reference']) ?></span>
        </div>
        <p style="font-size:.92rem;white-space:pre-line"><?= e($note['notes']) ?></p>
        <?php if ($note['prescription']): ?>
          <div class="note"><strong>Prescription</strong><br><span style="white-space:pre-line"><?= e($note['prescription']) ?></span></div>
        <?php endif; ?>
        <?php if ($note['follow_up_date']): ?>
          <p style="margin:16px 0 0;font-size:.86rem;color:var(--muted)">Follow-up recommended: <strong style="color:var(--forest)"><?= e(short_date($note['follow_up_date'])) ?></strong></p>
        <?php endif; ?>
      </article>
    <?php endforeach; endif; ?>
  </div>
</div>
