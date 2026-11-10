<?php
/**
 * Docs — writes the human deliverables for a generated site: deployment guide,
 * tech-stack report, printable technical report, site README and portfolio entry.
 * Every environment variable used by core/bootstrap.php is documented here.
 */
declare(strict_types=1);

final class Docs
{
    public const ENV_KEYS = [
        'APP_NAME', 'APP_URL', 'APP_TIMEZONE', 'APP_DEBUG',
        'DB_HOST', 'DB_PORT', 'DB_NAME', 'DB_USER', 'DB_PASS',
        'MAIL_ENABLED', 'MAIL_FROM', 'MAIL_FROM_NAME',
        'INSTALL_KEY', 'ADMIN_NAME', 'ADMIN_EMAIL', 'ADMIN_PASSWORD', 'CRON_SECRET',
    ];

    public function deployment(array $project): string
    {
        $bp = $project['blueprint'];
        $gen = $bp['generation'];
        $intake = $bp['intake'];
        $name = $intake['business_name'];
        $slug = $gen['slug'];
        $table = $bp['architecture']['entities'][4]['table'] ?? '';
        $tables = (string)($project['database']['tables'] ? implode(', ', (array)$project['database']['tables']) : 'see database/schema.sql');
        $dbStatus = (string)($project['database']['label'] ?? 'not installed yet');

        return <<<MD
# Deployment guide — {$name}

This package is a complete PHP {$this->phpWord()} / MySQL application. It runs on ordinary
cPanel shared hosting with Apache. No Composer, Node, Redis, cron daemon or SSH access is
required.

Build: `{$slug}` · Database: `{$gen['db_name']}` · Status: {$dbStatus}

## 1. What is in the package

```
public/          document root (index.php, install.php, .htaccess, assets)
app/             routes, controllers, services and generated config.php
views/           server-rendered templates
core/            ForgeStack core (Db, Auth, Router, Mailer, Installer, helpers)
database/        schema.sql, seed.sql and module fragments
storage/         writable runtime directory (logs, install lock)
cron/run.php     automation entry point for cPanel cron
docs/            this guide, tech-stack report, printable technical report
.env.example     every setting with placeholders
```

## 2. Create the database in cPanel

1. cPanel → **MySQL Databases**.
2. Create a database, e.g. `{$gen['db_name']}`. cPanel prefixes it with your account name,
   so the real name may look like `cpuser_{$gen['db_name']}`.
3. Create a database user with a strong password and add it to the database with **All
   Privileges**.
4. Note the host (usually `localhost`), database name, user and password.

## 3. Upload the files

1. cPanel → **File Manager** → `public_html` (or a subfolder for staging).
2. Upload the contents of this build folder. Do not upload a parent folder that contains
   `.env` backups or local notes.
3. Point the domain's document root at `public/`. If you cannot change the document root,
   upload the *contents of `public/`* into `public_html/` and move the other folders one
   level above `public_html` — then edit `public/index.php` so the `require` path points to
   the parent directory. The supported layout is document root = `public/`.

## 4. Configure `.env`

Copy `.env.example` to `.env` **in the application root** (one level above `public/`) and
fill in real values. The file must not be downloadable; `.htaccess` denies dotfiles and
`*.env`. Required variables:

| Variable | Purpose | Example |
|---|---|---|
| `APP_NAME` | Site name in titles and email | `{$this->envExample($name)}` |
| `APP_URL` | Canonical URL, no trailing slash | `https://example.com` |
| `APP_TIMEZONE` | PHP timezone for dates and cron | `Africa/Lagos` |
| `APP_DEBUG` | Verbose errors; keep `false` in production | `false` |
| `DB_HOST` | MySQL host from cPanel | `localhost` |
| `DB_PORT` | MySQL port | `3306` |
| `DB_NAME` | Full cPanel database name | `cpuser_{$gen['db_name']}` |
| `DB_USER` | Full cPanel database user | `cpuser_site` |
| `DB_PASS` | Database password | *(secret)* |
| `MAIL_ENABLED` | `true` to attempt PHP `mail()`; `false` queues only | `false` |
| `MAIL_FROM` | Envelope/from address | `no-reply@example.com` |
| `MAIL_FROM_NAME` | From display name | `{$this->envExample($name)}` |
| `INSTALL_KEY` | One-time installer password | *(long random)* |
| `ADMIN_NAME` | Seeded administrator name | `Site Administrator` |
| `ADMIN_EMAIL` | Seeded administrator login | `owner@example.com` |
| `ADMIN_PASSWORD` | Seeded administrator password, min 10 chars | *(secret)* |
| `CRON_SECRET` | Guards HTTP cron calls | *(long random)* |

`ADMIN_PASSWORD` is read once at install time, hashed with `password_hash()` and never
written to the database in plain text. Rotate `INSTALL_KEY` and `CRON_SECRET` after launch.

## 5. Run the installer

Visit `https://yourdomain.com/install.php?key=YOUR_INSTALL_KEY`. The installer:

- checks the installation key against `INSTALL_KEY`;
- applies `database/schema.sql` (and `database/seed.sql` if you leave the seed box ticked);
- creates the administrator from `ADMIN_EMAIL` / `ADMIN_PASSWORD`;
- writes `storage/installed.lock`.

When it finishes, **delete `public/install.php`** and remove `INSTALL_KEY` from `.env`.
Sign in at `/login` with the administrator account.

The current build reports: **{$dbStatus}**. Tables: {$tables}.

## 6. Permissions and security

- `storage/` must be writable by the PHP user (0755, or 0775 under PHP-FPM).
- Keep `.env` outside the document root and never commit it.
- `.htaccess` sets security headers, denies dotfiles and blocks `.sql`, `.md`, `.log`,
  `.lock`, `.env` downloads, and disables directory indexes.
- Every POST is CSRF-checked. Sign-in attempts are throttled. Session IDs regenerate on
  login. Administrator routes call `Auth::requireRole('admin')` server-side.
- No card data is collected or stored. Payments are recorded manually by staff.

## 7. Cron jobs

Add these in cPanel → **Cron Jobs**. Use the command form where possible:

```
/usr/local/bin/php /home/USER/path/to/cron/run.php
```

Run every 15 minutes for reminders, and once daily for the digest, closing past requests,
low-stock alerts and payment follow-ups:

```
*/15 * * * * /usr/local/bin/php /home/USER/path/to/cron/run.php
0 7 * * *    /usr/local/bin/php /home/USER/path/to/cron/run.php
```

If the host only allows HTTP cron, call
`https://yourdomain.com/cron/run.php?key=YOUR_CRON_SECRET`. Change `CRON_SECRET` regularly.
Tasks are idempotent: reminder timestamps stop duplicate sends.

## 8. Email

With `MAIL_ENABLED=false` every notification is queued in `email_outbox` and visible under
**Admin → Email outbox**. Add SPF/DKIM records for your domain before setting
`MAIL_ENABLED=true`, then send a test from the outbox workflow. A failed `mail()` call is
recorded with its error and can be retried by re-queueing.

## 9. Backups and updates

- cPanel → **Backup**: download a full backup after go-live, then weekly.
- Back up both the database and the application folder (`storage/` included).
- To deploy a change, upload to a staging subdomain first. The schema uses
  `CREATE TABLE IF NOT EXISTS`; review any new migration before running it on live data.
- Never edit files in `core/` unless you also update ForgeStack's shared core.

## 10. Go-live checklist

- [ ] `APP_DEBUG=false`, `APP_URL` is the real HTTPS URL.
- [ ] Installer deleted; `INSTALL_KEY` removed or rotated.
- [ ] Administrator password is unique and stored in a password manager.
- [ ] `storage/` is writable; `installed.lock` exists.
- [ ] Test booking → reference → tracking page.
- [ ] Test contact form → admin inbox → email_outbox row.
- [ ] Sign in, change a status, confirm the audit log and timeline update.
- [ ] Cron runs without errors (`storage/automation.log`).
- [ ] Backups scheduled and a restore has been rehearsed.

Generated by ForgeStack {$this->version()}.
MD;
    }

    public function techStack(array $project): string
    {
        $bp = $project['blueprint'];
        $intake = $bp['intake'];
        $entities = $bp['architecture']['entities'];
        $rows = '';
        foreach ($entities as $entity) {
            $rows .= '| ' . $entity['table'] . ' | ' . $entity['purpose'] . ' | ' . implode(', ', $entity['fields']) . " |\n";
        }
        $features = implode(', ', $intake['features']);
        $routes = '';
        foreach ($bp['architecture']['routes'] as $route) {
            $routes .= '- `' . $route[0] . ' ' . $route[1] . '` → `' . $route[2] . "()`\n";
        }
        $modules = '';
        foreach ((array)$project['config']['modules'] as $key => $on) {
            $modules .= '- ' . $key . ': ' . ($on ? 'enabled' : 'not included') . "\n";
        }

        return <<<MD
# Technical specification — {$intake['business_name']}

**Industry profile:** {$bp['profile_label']} · **Tone:** {$intake['tone']}
**Palette:** {$bp['architecture']['palette_name']} · **Type:** {$bp['architecture']['typography']}
**Generated:** {$project['created_at']} · **Build:** `{$bp['generation']['slug']}`

## Purpose

{$intake['idea']}

Problem addressed: {$intake['problem']}

Audience: {$intake['audience']}

## Stack

- PHP 8.1+ (built and tested on 8.2), plain classes with no framework and no Composer.
- MySQL 5.7+/8.x or MariaDB 10.3+, utf8mb4, InnoDB, PDO prepared statements.
- Apache with `mod_rewrite` and `mod_headers`; `.htaccess` provides routing and headers.
- Server-rendered views, one hand-written CSS design system and one small progressive JS file.
- No Node build step, no Redis, no queue worker, no external SaaS requirement.
- cPanel cron (CLI preferred, HTTP fallback) for scheduled automation.

## Feature modules

{$modules}
Intake features: {$features}.

## Data model

| Table | Purpose | Key fields |
|---|---|---|
{$rows}
Indexes cover status, date and customer lookups. Foreign keys use `ON DELETE SET NULL` for
people and `ON DELETE CASCADE` for child rows. Stock decrements run inside a transaction with
`SELECT ... FOR UPDATE` and a conditional `UPDATE` so the last item cannot oversell.

## Routes

{$routes}

## Security

- `password_hash()` / `password_verify()` with automatic rehash; no plain-text passwords.
- Login throttling by email + IP using `login_attempts`.
- `session_regenerate_id(true)` on sign-in; CSRF token verified on every POST.
- Role checks happen server-side in controllers, not in navigation only.
- Output escaped through `e()`; SQL only through prepared statements.
- Uploads are not part of this build; no user-controlled files are executed.
- Security headers and dotfile/SQL denial in `public/.htaccess`.

## Automation

Each task is idempotent and writes a line to `storage/automation.log`:

MD
        . $this->automationList($bp) . <<<MD

## Operational notes

- `docs/DEPLOYMENT.md` lists every environment variable and the cPanel steps.
- `docs/technical-report.html` is a printable report for the client.
- Seed data is reference content, clearly named, and can be removed before launch.
- The generated application is self-contained: `core/` is copied in, pinned to
  ForgeStack core `{$this->version()}`.

Generated by ForgeStack {$this->version()}.
MD;
    }

    public function technicalReport(array $project): string
    {
        $bp = $project['blueprint'];
        $intake = $bp['intake'];
        $name = $this->h($intake['business_name']);
        $slug = $bp['generation']['slug'];
        $arch = $bp['architecture'];
        $pages = '';
        foreach ($arch['pages'] as $page) {
            $status = $page['status'] === 'limited' ? 'Limited (messaging not selected)' : 'Generated';
            $pages .= '<tr><td><code>' . $this->h($page['path']) . '</code></td><td>' . $this->h($page['title']) . '</td><td>' . $this->h($page['purpose']) . '</td><td>' . $this->h($status) . '</td></tr>';
        }
        $deferred = '';
        foreach ($arch['deferred_pages'] as $item) {
            $deferred .= '<li><strong>' . $this->h($item['page']) . '</strong> — ' . $this->h($item['reason']) . '</li>';
        }
        $entities = '';
        foreach ($arch['entities'] as $entity) {
            $entities .= '<tr><td><code>' . $this->h($entity['table']) . '</code></td><td>' . $this->h($entity['purpose']) . '</td></tr>';
        }
        $roles = '';
        foreach ($arch['roles'] as $role) {
            $roles .= '<li><strong>' . $this->h($role['name']) . '</strong> — ' . $this->h($role['access']) . ($role['seeded'] ? ' (seeded)' : '') . '</li>';
        }
        $automation = '';
        foreach ($arch['automation'] as $task) {
            $automation .= '<li><strong>' . $this->h($task['label']) . '</strong> (' . $this->h($task['schedule']) . ') — ' . $this->h($task['behaviour']) . '</li>';
        }
        $steps = '';
        foreach ($project['steps'] as $i => $step) {
            $steps .= '<tr><td>' . ($i + 1) . '</td><td>' . $this->h($step['label']) . '</td><td><span class="st ' . $this->h($step['status']) . '">' . $this->h(ucfirst($step['status'])) . '</span></td><td>' . $this->h($step['log']) . '</td><td>' . $this->h(substr((string)$step['at'], 11, 8)) . '</td></tr>';
        }
        $qa = '';
        foreach ((array)($project['qa']['checks'] ?? []) as $check) {
            $qa .= '<li><span class="st ' . ($check['ok'] ? 'done' : 'failed') . '">' . ($check['ok'] ? 'Pass' : 'Fail') . '</span> ' . $this->h($check['label']) . ' — ' . $this->h((string)$check['detail']) . '</li>';
        }
        $tables = $this->h(implode(', ', (array)($project['database']['tables'] ?? [])) ?: 'see database/schema.sql');
        $featureList = '';
        foreach ($intake['features'] as $feature) {
            $labels = Blueprint::featureLabels();
            $featureList .= '<li><strong>' . $this->h($labels[$feature][0] ?? $feature) . '</strong> — ' . $this->h($labels[$feature][1] ?? '') . '</li>';
        }
        $manifest = (int)($project['manifest']['count'] ?? 0);
        $bytes = fs_format_bytes((int)($project['manifest']['bytes'] ?? 0));
        $palette = fs_palettes()[$arch['palette']] ?? null;

        return <<<HTML
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex">
<title>Technical report — {$name}</title>
<style>
  :root { --ink:#17211d; --muted:#5b6a63; --line:#dbe3dc; --paper:#f6f4ee; --brand:#146b5a; --soft:#edf3ee; }
  * { box-sizing:border-box; }
  body { margin:0; background:var(--paper); color:var(--ink); font:15px/1.65 "Segoe UI",system-ui,sans-serif; }
  main { max-width:920px; margin:0 auto; padding:48px 30px 70px; }
  header { border-bottom:3px solid var(--brand); padding-bottom:22px; margin-bottom:30px; }
  .kicker { letter-spacing:.16em; text-transform:uppercase; font-size:.7rem; font-weight:700; color:var(--brand); }
  h1 { font-size:2rem; margin:.3em 0 .2em; letter-spacing:-.02em; }
  h2 { font-size:1.15rem; margin:2.2em 0 .7em; padding-bottom:6px; border-bottom:1px solid var(--line); }
  .meta { display:flex; flex-wrap:wrap; gap:8px 22px; color:var(--muted); font-size:.86rem; }
  .grid { display:grid; grid-template-columns:repeat(2,1fr); gap:12px; margin:16px 0; }
  .card { background:#fff; border:1px solid var(--line); border-radius:10px; padding:14px 16px; }
  .card b { display:block; font-size:.72rem; text-transform:uppercase; letter-spacing:.1em; color:var(--muted); margin-bottom:4px; }
  table { width:100%; border-collapse:collapse; background:#fff; font-size:.85rem; }
  th,td { text-align:left; padding:9px 10px; border-bottom:1px solid var(--line); vertical-align:top; }
  th { background:var(--soft); font-size:.72rem; text-transform:uppercase; letter-spacing:.08em; }
  code { font:0.88em ui-monospace,Consolas,monospace; background:#eef3ef; padding:1px 5px; border-radius:4px; }
  ul { padding-left:20px; }
  .st { display:inline-block; font-size:.68rem; font-weight:700; text-transform:uppercase; letter-spacing:.06em; padding:2px 7px; border-radius:99px; background:#e8eee9; }
  .st.done { background:#dff1e8; color:#0c5a43; }
  .st.skipped { background:#fdf1d8; color:#7a5208; }
  .st.failed { background:#fbe3e1; color:#8a2118; }
  .swatches { display:flex; gap:8px; flex-wrap:wrap; margin-top:8px; }
  .sw { width:52px; height:34px; border-radius:7px; border:1px solid rgba(0,0,0,.08); }
  footer { margin-top:36px; padding-top:16px; border-top:1px solid var(--line); color:var(--muted); font-size:.78rem; }
  .noprint { margin:0 0 22px; }
  button { font:600 .85rem system-ui,sans-serif; padding:9px 16px; border:0; border-radius:8px; background:var(--brand); color:#fff; cursor:pointer; }
  @media print { body{background:#fff} main{padding:0} .noprint{display:none} .card,table{break-inside:avoid} }
</style>
</head>
<body>
<main>
  <button class="noprint" onclick="window.print()">Print this report</button>
  <header>
    <p class="kicker">ForgeStack technical report</p>
    <h1>{$name}</h1>
    <div class="meta">
      <span>Build <strong>{$slug}</strong></span>
      <span>{$this->h($intake['industry'])}</span>
      <span>{$this->h($arch['profile_label'])}</span>
      <span>Generated {$this->h($project['created_at'])}</span>
    </div>
  </header>

  <h2>1. Brief</h2>
  <p>{$this->h($intake['idea'])}</p>
  <p><strong>Problem:</strong> {$this->h($intake['problem'])}</p>
  <p><strong>Who experiences it:</strong> {$this->h($intake['audience'])}</p>
  <p><strong>Tone:</strong> {$this->h($intake['tone'])} · <strong>Palette:</strong> {$this->h($arch['palette_name'])} · <strong>Type:</strong> {$this->h($arch['typography'])}</p>

  <h2>2. Deliverables</h2>
  <div class="grid">
    <div class="card"><b>Files</b>{$manifest} deployed files · {$bytes}</div>
    <div class="card"><b>Database</b>{$this->h((string)($project['database']['label'] ?? 'not installed'))}</div>
    <div class="card"><b>Pages</b>{$this->h((string)count($arch['pages']))} routed pages</div>
    <div class="card"><b>Automation</b>{$this->h((string)count($arch['automation']))} idempotent tasks</div>
  </div>
  <p>Tables: {$tables}</p>

  <h2>3. Page map</h2>
  <table><thead><tr><th>Path</th><th>Title</th><th>Purpose</th><th>Status</th></tr></thead><tbody>{$pages}</tbody></table>
  {$this->deferredBlock($deferred)}

  <h2>4. Features in scope</h2>
  <ul>{$featureList}</ul>

  <h2>5. Data model</h2>
  <table><thead><tr><th>Table</th><th>Purpose</th></tr></thead><tbody>{$entities}</tbody></table>

  <h2>6. Roles</h2>
  <ul>{$roles}</ul>

  <h2>7. Automation</h2>
  <ul>{$automation}</ul>

  <h2>8. Quality checks</h2>
  <ul>{$qa}</ul>

  <h2>9. Generation log</h2>
  <table><thead><tr><th>#</th><th>Step</th><th>Status</th><th>Log</th><th>Time</th></tr></thead><tbody>{$steps}</tbody></table>

  <h2>10. Deployment</h2>
  <p>Upload the build, point the document root at <code>public/</code>, create a MySQL database
  in cPanel, copy <code>.env.example</code> to <code>.env</code>, run
  <code>install.php?key=…</code>, delete the installer and add the cron entries. The full
  guide with every environment variable is in <code>docs/DEPLOYMENT.md</code>.</p>
  <p><strong>Out of scope:</strong> card payments and gateway integration, image uploads/gallery,
  blog CMS, SMS, multi-language, and migrations after launch.</p>

  <footer>Generated by ForgeStack {$this->version()} · {$this->h($project['id'])} · This report contains no passwords or secrets.</footer>
</main>
</body>
</html>
HTML;
    }

    public function siteReadme(array $project): string
    {
        $bp = $project['blueprint'];
        $intake = $bp['intake'];
        $slug = $bp['generation']['slug'];
        $port = (int)($bp['generation']['port'] ?? 0);
        return <<<MD
# {$intake['business_name']}

{$bp['architecture']['tagline']}

A self-contained PHP + MySQL site generated by ForgeStack from a {$intake['industry']} intake.
The stack is deliberately small: no Composer, no Node, no Redis, no external build.

## Local preview

```bash
# from this folder, after the database is installed
php -S 127.0.0.1:{$port} -t public public/router.php
```

Then open http://127.0.0.1:{$port}. On first visit you are redirected to `install.php`;
the installation key is `INSTALL_KEY` in `.env`.

## What is real

- Booking/reservation/shipment requests are validated server-side and stored with a reference.
- Every request queues a real row in `email_outbox` and an entry in `record_events`.
- Tracking pages expose the first name only, never contact details.
- The admin desk reads live aggregates, not placeholder numbers.
- Stock is decremented inside a transaction; the last item cannot be oversold.
- Authentication uses `password_hash()`, throttled logins, CSRF on every POST and server-side roles.

## Structure

```
public/     front controller, installer, assets
app/        config.php, routes, controllers, services
views/      templates and admin views
core/       ForgeStack core copy
database/   schema + seed
storage/    writable runtime directory
cron/       automation runner
docs/       deployment guide, tech stack, printable report
```

## Configuration

All settings live in `.env`; `.env.example` documents every variable. The administrator is
seeded from `ADMIN_EMAIL` / `ADMIN_PASSWORD` and never hardcoded in PHP. Do not commit `.env`.

See `docs/DEPLOYMENT.md` for cPanel steps, cron and the go-live checklist.
MD;
    }

    public function portfolio(array $project, string $thumbnail, string $liveLink): array
    {
        $bp = $project['blueprint'];
        $intake = $bp['intake'];
        $features = [];
        $labels = Blueprint::featureLabels();
        foreach ($intake['features'] as $feature) {
            $features[] = $labels[$feature][0] ?? $feature;
        }
        return [
            'name' => $intake['business_name'],
            'description' => $bp['architecture']['tagline'] . ' ' . $intake['idea'],
            'category' => $bp['profile_label'],
            'problem' => $intake['problem'],
            'technologies' => ['PHP 8', 'MySQL', 'Apache', 'cPanel', 'Vanilla CSS', 'Vanilla JS'],
            'key_features' => $features,
            'thumbnail' => $thumbnail,
            'live_link' => $liveLink,
            'source_link' => 'build/' . $bp['generation']['slug'],
            'generated_at' => $project['created_at'],
        ];
    }

    public function checksums(array $manifest): string
    {
        $lines = [];
        foreach ((array)($manifest['files'] ?? []) as $path => $meta) {
            $lines[] = $meta['sha256'] . '  ' . $path;
        }
        return implode("\n", $lines) . "\n";
    }

    private function automationList(array $bp): string
    {
        $out = '';
        foreach ($bp['architecture']['automation'] as $task) {
            $out .= '- **' . $task['label'] . '** (' . $task['schedule'] . '): ' . $task['behaviour'] . "\n";
        }
        return $out;
    }

    private function deferredBlock(string $deferred): string
    {
        return $deferred === '' ? '' : '<h2>Deferred pages</h2><ul>' . $deferred . '</ul>';
    }

    private function h(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    private function envExample(string $value): string
    {
        return str_replace(['"', "\n"], ['\"', ' '], $value);
    }

    private function phpWord(): string
    {
        return '8.1+';
    }

    private function version(): string
    {
        return FS_VERSION . ' (core ' . $this->coreVersion() . ')';
    }

    private function coreVersion(): string
    {
        return '1.0.0';
    }
}
