<?php
$features = [
    'bookings' => ['Bookings & tracking', 'Public request form, reference, status timeline and email outbox.'],
    'orders' => ['Orders & stock', 'Catalogue with transactional stock decrement inside MySQL.'],
    'payments' => ['Payments ledger', 'Manual payment capture with real totals and receipts.'],
    'messaging' => ['Contact inbox', 'Validated enquiries saved to the database and answered in admin.'],
    'reports' => ['Reports', 'Live aggregates: volume, status mix, revenue and demand.'],
];
$pages = [
    'home' => ['Home', 'Industry landing page'],
    'about' => ['About', 'Story, numbers, people'],
    'services' => ['Services', 'Treatments, process, pricing'],
    'pricing' => ['Pricing', 'Indicative prices and packages'],
    'faq' => ['FAQ', 'Objection-handling answers'],
    'contact' => ['Contact', 'Validated enquiry form'],
    'gallery' => ['Gallery', 'Deferred: needs an image pipeline'],
    'blog' => ['Blog', 'Deferred: needs an editorial workflow'],
];
$tones = ['calm' => 'Calm', 'confident' => 'Confident', 'warm' => 'Warm', 'technical' => 'Technical', 'premium' => 'Premium'];
$directions = [
    '' => ['Industry default', ['#146b5a', '#c8964f', '#f5f3ec']],
    'Green / natural' => ['Green & natural', ['#146b5a', '#c8964f', '#e6efe9']],
    'Blue / indigo' => ['Blue & indigo', ['#2b4b8f', '#7c9bd8', '#eef1f8']],
    'Warm / terracotta' => ['Warm & terracotta', ['#9a4a2f', '#d59a5f', '#f7ece3']],
    'Neutral / slate' => ['Neutral slate', ['#3f4a4d', '#8fa3a6', '#eef1f1']],
];
$industries = [
    'Dental & physiotherapy clinic' => 'Dental / physio clinic',
    'Courier & logistics' => 'Courier / logistics',
    'Restaurant & hospitality' => 'Restaurant / hospitality',
    'Professional services' => 'Professional services',
];
?>
<div class="wizard-head">
  <div>
    <p class="eyebrow">New build · intake</p>
    <h1>Tell us what the business needs.</h1>
    <p class="lede">Four short stages. The right-hand blueprint derives the pages, data model, roles and automation as you type — before a single file is written.</p>
  </div>
  <a class="text-link" href="/"><?= fs_icon('arrow-left', 15) ?> Back to dashboard</a>
</div>

<div class="wizard-grid">
  <form class="wizard-form" id="brief-form" novalidate autocomplete="off">
    <div class="wizard-card">
      <ol class="stage-nav" aria-label="Brief stages">
        <li class="stage-dot is-active" data-stage-dot="1"><span>01</span><div><b>The business</b><small>Identity &amp; problem</small></div></li>
        <li class="stage-dot" data-stage-dot="2"><span>02</span><div><b>Scope</b><small>Pages &amp; features</small></div></li>
        <li class="stage-dot" data-stage-dot="3"><span>03</span><div><b>Brand &amp; host</b><small>Tone, contact, MySQL</small></div></li>
        <li class="stage-dot" data-stage-dot="4"><span>04</span><div><b>Review</b><small>Generate</small></div></li>
      </ol>

      <section class="wizard-stage is-active" data-stage="1">
        <div class="stage-heading">
          <p class="eyebrow">Stage 01</p>
          <h2>The business and the problem</h2>
          <p>Concrete details produce concrete copy. No “Your Company Name”, no lorem ipsum.</p>
        </div>

        <div class="field-grid">
          <div class="field field-wide">
            <label for="business_name">Business name <span class="req">required</span></label>
            <input id="business_name" name="business_name" maxlength="80" placeholder="e.g. Northgate Dental Studio" required>
            <p class="help">Used in the masthead, page titles, emails and portfolio entry.</p>
          </div>

          <div class="field">
            <label for="industry">Industry <span class="req">required</span></label>
            <select id="industry" name="industry" required>
              <option value="">Choose an industry</option>
              <?php foreach ($industries as $value => $label): ?>
                <option value="<?= fs_e($value) ?>"><?= fs_e($label) ?></option>
              <?php endforeach; ?>
            </select>
            <p class="help">Selects a curated content, palette and data profile.</p>
          </div>

          <div class="field">
            <label for="tone_stage1">Brand tone</label>
            <select id="tone_stage1" name="tone">
              <?php foreach ($tones as $value => $label): ?>
                <option value="<?= fs_e($value) ?>" <?= $value === 'calm' ? 'selected' : '' ?>><?= fs_e($label) ?></option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="field field-wide">
            <label for="idea">The offering, in one line <span class="req">required</span></label>
            <input id="idea" name="idea" maxlength="180" placeholder="A calm dental practice for families in Ikoyi, booked online." required>
            <p class="help">At least 10 characters. This also anchors which industry profile is used.</p>
          </div>

          <div class="field field-wide">
            <label for="problem">What problem does it solve? <span class="req">required</span></label>
            <textarea id="problem" name="problem" rows="3" maxlength="600" placeholder="Patients avoid booking because they cannot see prices, and reception is hard to reach during work hours." required></textarea>
          </div>

          <div class="field field-wide">
            <label for="audience">Who experiences that problem? <span class="req">required</span></label>
            <textarea id="audience" name="audience" rows="2" maxlength="400" placeholder="Working parents in Ikoyi and Lekki who book for the whole family and need clarity before committing." required></textarea>
          </div>
        </div>

        <div class="stage-foot">
          <span class="stage-hint">You can move back at any time. Nothing is written until stage 04.</span>
          <button class="btn btn-primary" type="button" data-next>Continue to scope <?= fs_icon('arrow-right', 16) ?></button>
        </div>
      </section>

      <section class="wizard-stage" data-stage="2" hidden>
        <div class="stage-heading">
          <p class="eyebrow">Stage 02</p>
          <h2>Pages and operating features</h2>
          <p>Home, booking, tracking, contact and the admin desk are structural. Everything else follows this brief.</p>
        </div>

        <div class="field field-wide">
          <label>Pages the site needs <span class="req">required</span></label>
          <div class="choice-grid">
            <?php foreach ($pages as $value => $meta): ?>
              <?php $locked = $value === 'home'; $default = in_array($value, ['home', 'about', 'services', 'faq', 'contact'], true); ?>
              <label class="choice-card <?= in_array($value, ['gallery', 'blog'], true) ? 'is-deferred' : '' ?>">
                <input type="checkbox" name="pages[]" value="<?= fs_e($value) ?>" <?= $default ? 'checked' : '' ?> <?= $locked ? 'checked' : '' ?>>
                <span class="choice-check"><?= fs_icon('check', 12) ?></span>
                <span class="choice-copy"><b><?= fs_e($meta[0]) ?></b><small><?= fs_e($meta[1]) ?></small></span>
              </label>
            <?php endforeach; ?>
          </div>
          <p class="help">Gallery and blog are recorded as deferred with a reason, rather than quietly dropped.</p>
        </div>

        <div class="field field-wide">
          <label>Operating features</label>
          <div class="choice-grid choice-grid-features">
            <?php foreach ($features as $key => $meta): ?>
              <label class="choice-card">
                <input type="checkbox" name="features[]" value="<?= fs_e($key) ?>" <?= in_array($key, ['bookings', 'messaging', 'reports'], true) ? 'checked' : '' ?>>
                <span class="choice-check"><?= fs_icon('check', 12) ?></span>
                <span class="choice-copy"><b><?= fs_e($meta[0]) ?></b><small><?= fs_e($meta[1]) ?></small></span>
              </label>
            <?php endforeach; ?>
            <span class="choice-card is-locked">
              <span class="choice-check is-on"><?= fs_icon('check', 12) ?></span>
              <span class="choice-copy"><b>Authentication &amp; admin</b><small>Always included: hashed passwords, throttled login, CSRF, roles and a real operations desk.</small></span>
            </span>
          </div>
        </div>

        <div class="field field-wide">
          <label for="references">Research &amp; competitor notes</label>
          <textarea id="references" name="references" rows="3" maxlength="1200" placeholder="Competitors reviewed, local expectations, services that must appear, anything the operator learned."></textarea>
          <p class="help">Stored with the project as operator-supplied research. It is not invented for you.</p>
        </div>

        <div class="stage-foot">
          <button class="btn btn-ghost" type="button" data-prev><?= fs_icon('arrow-left', 16) ?> Back</button>
          <button class="btn btn-primary" type="button" data-next>Continue to brand <?= fs_icon('arrow-right', 16) ?></button>
        </div>
      </section>

      <section class="wizard-stage" data-stage="3" hidden>
        <div class="stage-heading">
          <p class="eyebrow">Stage 03</p>
          <h2>Brand, contact and host</h2>
          <p>Colours map to curated palettes, never random values. Contact details flow into the footer, emails and structured data.</p>
        </div>

        <div class="field field-wide">
          <label>Colour direction</label>
          <div class="palette-grid">
            <?php foreach ($directions as $value => $meta): ?>
              <label class="palette-card">
                <input type="radio" name="colour_direction" value="<?= fs_e($value) ?>" <?= $value === '' ? 'checked' : '' ?>>
                <span class="palette-swatch">
                  <?php foreach ($meta[1] as $colour): ?><i style="background:<?= fs_e($colour) ?>"></i><?php endforeach; ?>
                </span>
                <span><?= fs_e($meta[0]) ?></span>
              </label>
            <?php endforeach; ?>
          </div>
        </div>

        <div class="field-grid">
          <div class="field">
            <label for="contact_name">Contact name</label>
            <input id="contact_name" name="contact_name" maxlength="120" placeholder="Practice manager">
          </div>
          <div class="field">
            <label for="contact_email">Public email <span class="req">required</span></label>
            <input id="contact_email" name="contact_email" type="email" maxlength="190" placeholder="hello@business.com" required>
          </div>
          <div class="field">
            <label for="contact_phone">Phone <span class="req">required</span></label>
            <input id="contact_phone" name="contact_phone" maxlength="40" placeholder="+234 801 234 5678" required>
          </div>
          <div class="field">
            <label for="address">Address</label>
            <input id="address" name="address" maxlength="180" placeholder="12 Glover Road, Ikoyi, Lagos">
          </div>
        </div>

        <div class="advanced-block">
          <div class="advanced-head">
            <h3>Administrator &amp; local preview</h3>
            <span class="badge-soft">Secrets stay in .env</span>
          </div>
          <div class="field-grid">
            <div class="field">
              <label for="admin_email">Admin sign-in email</label>
              <input id="admin_email" name="admin_email" type="email" maxlength="190" placeholder="Defaults to the public email">
              <p class="help">Seeded from <code>ADMIN_EMAIL</code> at install.</p>
            </div>
            <div class="field">
              <label for="admin_password">Admin password</label>
              <input id="admin_password" name="admin_password" type="password" minlength="10" autocomplete="new-password" placeholder="Leave blank to generate a strong one">
              <p class="help">Written only to <code>.env</code>; the registry stores no password.</p>
            </div>
            <div class="field">
              <label for="preview_port">Local preview port</label>
              <input id="preview_port" name="preview_port" type="number" min="1024" max="65535" placeholder="e.g. 8110">
              <p class="help">Optional. Used in <code>APP_URL</code> and the run command.</p>
            </div>
            <div class="field field-checks">
              <label class="switch-row">
                <input type="checkbox" name="mysql_available" value="1" checked>
                <span class="switch"></span>
                <span><b>MySQL is available</b><small>If not, generation still proceeds and the database step is marked skipped.</small></span>
              </label>
              <label class="switch-row">
                <input type="checkbox" name="install_now" value="1" checked>
                <span class="switch"></span>
                <span><b>Install into local MySQL now</b><small>Creates the database, applies schema + seed and seeds the administrator from .env.</small></span>
              </label>
            </div>
          </div>
        </div>

        <div class="stage-foot">
          <button class="btn btn-ghost" type="button" data-prev><?= fs_icon('arrow-left', 16) ?> Back</button>
          <button class="btn btn-primary" type="button" data-next>Review the build <?= fs_icon('arrow-right', 16) ?></button>
        </div>
      </section>

      <section class="wizard-stage" data-stage="4" id="review" hidden>
        <div class="stage-heading">
          <p class="eyebrow">Stage 04</p>
          <h2>Review, then write it.</h2>
          <p>Generation copies 40+ templates, composes the schema, installs MySQL when available, runs <code>php -l</code> and secret checks, then writes the docs and manifest.</p>
        </div>

        <dl class="review-list">
          <div><dt>Business</dt><dd data-review="business">—</dd></div>
          <div><dt>Profile</dt><dd data-review="profile">—</dd></div>
          <div><dt>Pages</dt><dd data-review="pages">—</dd></div>
          <div><dt>Features</dt><dd data-review="features">—</dd></div>
          <div><dt>Contact</dt><dd data-review="contact">—</dd></div>
          <div><dt>Palette &amp; type</dt><dd data-review="design">—</dd></div>
          <div><dt>Preview port</dt><dd data-review="port">Not set</dd></div>
          <div><dt>Database</dt><dd data-review="database">—</dd></div>
        </dl>

        <div class="pipeline-plan">
          <p class="eyebrow">What runs next</p>
          <ol>
            <li><span>01–04</span> Store the intake, research notes, derived architecture and preflight.</li>
            <li><span>05–07</span> Apply the design system, copy the frontend templates and shared core.</li>
            <li><span>08–10</span> Compose schema + seed, install MySQL, wire auth and automation.</li>
            <li><span>11–13</span> Lint every PHP file, scan for tokens and secrets, optimise assets.</li>
            <li><span>14–16</span> Write deployment docs, the portfolio entry and the printable report.</li>
          </ol>
        </div>

        <div class="form-error" data-form-error hidden></div>

        <div class="stage-foot stage-foot-generate">
          <button class="btn btn-ghost" type="button" data-prev><?= fs_icon('arrow-left', 16) ?> Back</button>
          <button class="btn btn-primary btn-generate" type="submit" data-generate>
            <?= fs_icon('play', 17) ?> Generate the site
          </button>
        </div>
        <p class="generate-note">Generation never claims success unless the manifest is written. If MySQL is down, the database step is marked skipped — not passed.</p>
      </section>
    </div>
  </form>

  <aside class="preview-panel" aria-label="Live blueprint preview">
    <div class="preview-head">
      <span class="live-dot"></span>
      <span>Live blueprint</span>
      <span class="preview-status" data-preview-status>waiting</span>
    </div>
    <div class="preview-body">
      <p class="preview-kicker" data-preview-profile>Choose an industry</p>
      <h2 class="preview-title" data-preview-name>Your business</h2>
      <p class="preview-tagline" data-preview-tagline>The derived tagline, palette and page map appear here as you type.</p>

      <div class="preview-palette">
        <span class="swatch" data-swatch="brand" style="background:#c8bfae"></span>
        <span class="swatch" data-swatch="deep" style="background:#a89d89"></span>
        <span class="swatch" data-swatch="accent" style="background:#ded6c7"></span>
        <span class="swatch" data-swatch="paper" style="background:#f6f2ea"></span>
        <div class="preview-palette-meta">
          <b data-preview-palette>Palette</b>
          <small data-preview-type>Type pairing</small>
        </div>
      </div>

      <div class="preview-block">
        <h4>Page map <span data-preview-pagecount>0</span></h4>
        <div class="chip-list" data-preview-pages>
          <span class="chip">Waiting for the brief</span>
        </div>
      </div>

      <div class="preview-block">
        <h4>Modules</h4>
        <ul class="preview-features" data-preview-features>
          <li>Auth and admin are always included</li>
        </ul>
      </div>

      <div class="preview-split">
        <div><b data-preview-entities>—</b><span>entities</span></div>
        <div><b data-preview-routes>—</b><span>routes</span></div>
        <div><b data-preview-automation>—</b><span>tasks</span></div>
      </div>

      <p class="preview-note" data-preview-note>Palettes and type pairings are curated, then matched to the industry and your direction.</p>
    </div>
    <div class="preview-foot">
      <?= fs_icon('shield', 15) ?>
      <span>Preflight runs again immediately before generation.</span>
    </div>
  </aside>
</div>

<div class="gen-overlay" data-gen-overlay hidden>
  <div class="gen-card" role="dialog" aria-modal="true" aria-labelledby="gen-title">
    <div class="gen-forge" aria-hidden="true">
      <span></span><span></span><span></span>
    </div>
    <p class="eyebrow">Pipeline running</p>
    <h2 id="gen-title">Writing a real site.</h2>
    <p class="gen-lede">Templates, schema, MySQL, QA and documentation. This usually takes a few seconds.</p>
    <div class="gen-progress"><span></span></div>
    <p class="gen-hint" data-gen-hint>Calling the generator…</p>
    <ul class="gen-phases">
      <li>Copy templates and shared core</li>
      <li>Compose schema and seed data</li>
      <li>Install MySQL when available</li>
      <li>Run php -l, token and secret scans</li>
      <li>Write deployment docs and manifest</li>
    </ul>
    <div class="gen-error" data-gen-error hidden></div>
    <a class="btn btn-ghost gen-close" data-gen-close href="/" hidden>Back to dashboard</a>
  </div>
</div>

<noscript>
  <div class="noscript">The brief wizard needs JavaScript for the live blueprint and async generation. Run ForgeStack in a browser with JS enabled, or use <code>php cli/forge.php new blueprint.json</code>.</div>
</noscript>
