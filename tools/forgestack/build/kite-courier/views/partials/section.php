<?php
$tone = $section['type'] ?? 'editorial';
?>
<?php if ($tone === 'services'): ?>
  <section class="section">
    <div class="section-head">
      <div>
        <p class="eyebrow"><?= e($section['eyebrow'] ?? 'Services') ?></p>
        <h2><?= e($section['title'] ?? '') ?></h2>
      </div>
      <p class="section-lede"><?= e($section['lede'] ?? '') ?></p>
    </div>
    <div class="service-grid">
      <?php foreach ((array)($section['items'] ?? []) as $item): ?>
        <article class="service-card">
          <span class="service-icon" aria-hidden="true"><?= e($item['icon'] ?? '◇') ?></span>
          <h3><?= e($item['title']) ?></h3>
          <p><?= e($item['text']) ?></p>
          <span class="service-meta"><?= e($item['meta'] ?? '') ?></span>
        </article>
      <?php endforeach; ?>
    </div>
  </section>

<?php elseif ($tone === 'editorial'): ?>
  <section class="section">
    <div class="editorial">
      <div class="editorial-copy">
        <p class="eyebrow"><?= e($section['eyebrow'] ?? '') ?></p>
        <h2><?= e($section['title'] ?? '') ?></h2>
        <p class="editorial-body"><?= e($section['body'] ?? '') ?></p>
        <ul class="check-list">
          <?php foreach ((array)($section['bullets'] ?? []) as $bullet): ?>
            <li><?= e($bullet) ?></li>
          <?php endforeach; ?>
        </ul>
      </div>
      <?php if (!empty($section['image'])): ?>
        <figure class="editorial-figure">
          <img src="<?= e($section['image']) ?>" alt="<?= e($section['alt'] ?? '') ?>" loading="lazy" width="720" height="560">
          <figcaption><?= e($section['caption'] ?? '') ?></figcaption>
        </figure>
      <?php endif; ?>
    </div>
  </section>

<?php elseif ($tone === 'process'): ?>
  <section class="section section-band">
    <div class="section-head">
      <div>
        <p class="eyebrow"><?= e($section['eyebrow'] ?? 'How it works') ?></p>
        <h2><?= e($section['title'] ?? '') ?></h2>
      </div>
      <p class="section-lede"><?= e($section['lede'] ?? '') ?></p>
    </div>
    <ol class="process">
      <?php foreach ((array)($section['steps'] ?? []) as $i => $step): ?>
        <li class="process-step">
          <span class="process-index">0<?= $i + 1 ?></span>
          <h3><?= e($step['title']) ?></h3>
          <p><?= e($step['text']) ?></p>
        </li>
      <?php endforeach; ?>
    </ol>
  </section>

<?php elseif ($tone === 'stats'): ?>
  <section class="stats-band" aria-label="Key numbers">
    <div class="stats">
      <?php foreach ((array)($section['items'] ?? []) as $item): ?>
        <div class="stat"><strong><?= e($item['value']) ?></strong><span><?= e($item['label']) ?></span></div>
      <?php endforeach; ?>
    </div>
  </section>

<?php elseif ($tone === 'proof'): ?>
  <section class="section">
    <p class="eyebrow">In their words</p>
    <div class="proof-grid">
      <?php foreach ((array)($section['items'] ?? []) as $item): ?>
        <blockquote class="proof">
          <p>“<?= e($item['quote']) ?>”</p>
          <footer><strong><?= e($item['name']) ?></strong><span><?= e($item['detail']) ?></span></footer>
        </blockquote>
      <?php endforeach; ?>
    </div>
  </section>

<?php elseif ($tone === 'team'): ?>
  <section class="section">
    <div class="section-head">
      <div>
        <p class="eyebrow"><?= e($section['eyebrow'] ?? 'The team') ?></p>
        <h2><?= e($section['title'] ?? '') ?></h2>
      </div>
    </div>
    <div class="team-grid">
      <?php foreach ((array)($section['members'] ?? []) as $member): ?>
        <article class="team-card">
          <span class="team-initial" aria-hidden="true"><?= e(mb_substr($member['name'], 0, 1)) ?></span>
          <h3><?= e($member['name']) ?></h3>
          <span class="team-role"><?= e($member['role']) ?></span>
          <p><?= e($member['bio']) ?></p>
        </article>
      <?php endforeach; ?>
    </div>
  </section>

<?php elseif ($tone === 'faq'): ?>
  <section class="section">
    <div class="section-head">
      <div>
        <p class="eyebrow"><?= e($section['eyebrow'] ?? 'FAQ') ?></p>
        <h2><?= e($section['title'] ?? '') ?></h2>
      </div>
    </div>
    <div class="faq">
      <?php foreach ((array)($section['items'] ?? []) as $item): ?>
        <details class="faq-item">
          <summary><?= e($item['q']) ?></summary>
          <p><?= e($item['a']) ?></p>
        </details>
      <?php endforeach; ?>
    </div>
  </section>

<?php elseif ($tone === 'menu'): ?>
  <section class="section section-band">
    <div class="section-head">
      <div>
        <p class="eyebrow"><?= e($section['eyebrow'] ?? 'Menu') ?></p>
        <h2><?= e($section['title'] ?? '') ?></h2>
      </div>
      <p class="section-lede"><?= e($section['lede'] ?? '') ?></p>
    </div>
    <div class="menu-groups">
      <?php foreach ((array)($section['groups'] ?? []) as $group): ?>
        <div class="menu-group">
          <h3><?= e($group['name']) ?></h3>
          <?php foreach ((array)$group['items'] as $item): ?>
            <div class="menu-item">
              <div><strong><?= e($item['name']) ?></strong><span><?= e($item['desc']) ?></span></div>
              <em><?= e($item['price']) ?></em>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endforeach; ?>
    </div>
  </section>

<?php elseif ($tone === 'coverage'): ?>
  <section class="section">
    <div class="coverage">
      <div>
        <p class="eyebrow"><?= e($section['eyebrow'] ?? 'Coverage') ?></p>
        <h2><?= e($section['title'] ?? '') ?></h2>
        <p class="editorial-body"><?= e($section['body'] ?? '') ?></p>
      </div>
      <ul class="zone-list">
        <?php foreach ((array)($section['zones'] ?? []) as $zone): ?>
          <li><span aria-hidden="true">↗</span><?= e($zone) ?></li>
        <?php endforeach; ?>
      </ul>
    </div>
  </section>

<?php elseif ($tone === 'cta'): ?>
  <section class="cta">
    <div class="cta-inner">
      <div>
        <h2><?= e($section['title'] ?? '') ?></h2>
        <p><?= e($section['body'] ?? '') ?></p>
      </div>
      <div class="cta-actions">
        <a class="btn btn-primary" href="<?= e(url('/book')) ?>"><?= e($section['primary'] ?? cfg('labels.book')) ?></a>
        <a class="btn btn-outline btn-on-dark" href="<?= e(url('/track')) ?>"><?= e($section['secondary'] ?? cfg('labels.track')) ?></a>
      </div>
    </div>
  </section>
<?php endif; ?>
