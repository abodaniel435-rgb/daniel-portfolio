# ForgeStack — deployment guide & tech report

## 1. What it is

ForgeStack is a local **site factory**: a business brief goes in, a deployable PHP + MySQL application comes out.

- Four-stage intake wizard (business, scope, brand/host, review) with a live blueprint that derives pages, entities, routes, roles and scheduled tasks while you type.
- On submit it runs a visible 16-step pipeline with real timestamps and per-step logs.
- Output is a complete application: front controller, router, controllers, views, schema, seed data, auth, admin desk, automation, docs, manifest, checksums and a portfolio entry.

- **Local source:** `agent/forgestack`
- **Built with:** PHP 8.2, MySQL, PDO, vanilla JavaScript (no framework, no build step)
- **Interfaces:** browser console at `/` and CLI (`php cli/forge.php new brief.json`)
- **Registry:** `agent/forgestack/data/projects.json`; builds land in `agent/forgestack/build/<slug>`

## 2. Requirements

| Requirement | Value |
| --- | --- |
| PHP | 8.2+ with `pdo_mysql`, `json`, `mbstring` |
| MySQL | 5.7+ / MariaDB 10.3+ (optional — a missing database is a *warning*, not a failure) |
| cPanel | any standard PHP host; the app is a plain front controller |
| Extensions | none beyond a stock PHP build |

No Node, no Docker, no Redis, no framework runtime. The generated sites and the generator both run on shared hosting.

## 3. Run it locally

```bash
cd agent/forgestack
php -S 127.0.0.1:8120 -t public public/router.php
# open http://127.0.0.1:8120
```

Database credentials come from the environment, falling back to the local MySQL 8.4 instance on port 3307:

```bash
FORGE_DB_HOST=127.0.0.1 FORGE_DB_PORT=3307 FORGE_DB_USER=root FORGE_DB_PASS= php -S 127.0.0.1:8120 -t public public/router.php
```

CLI, useful for scripted runs and CI:

```bash
php cli/forge.php preflight
php cli/forge.php new brief.json
php cli/forge.php list
```

## 4. Deploying to cPanel — read this first

ForgeStack **writes files and creates databases**. If it is reachable on the public internet, anyone can generate a site, fill your disk and create databases with your MySQL user. Treat it as an admin tool:

1. Upload the package to a directory that is **not** `public_html` (for example `/home/USER/forgestack`).
2. Add HTTP Basic Auth in front of it — cPanel → **Directory Privacy** on the subdomain, or an `.htaccess` with `AuthType Basic` / `Require valid-user`.
3. Optionally restrict by IP: `Require ip 102.89.x.x` in the same `.htaccess`.
4. Set the document root to `public/` (subdomain add-on) or copy `public/` into a subfolder and point the subdomain at it.
5. Set the database environment variables in `.htaccess` (ForgeStack reads both `getenv()` and `$_SERVER`, so `SetEnv` works on mod_php, LSAPI and FPM):

```apache
SetEnv FORGE_DB_HOST localhost
SetEnv FORGE_DB_PORT 3306
SetEnv FORGE_DB_USER cpaneluser_forge
SetEnv FORGE_DB_PASS "the-password-from-cPanel"
# SetEnv FORGE_BASE_URL https://tools.example.com
```

| Variable | Purpose |
| --- | --- |
| `FORGE_DB_HOST` | MySQL host, usually `localhost` on cPanel |
| `FORGE_DB_PORT` | `3306` on cPanel |
| `FORGE_DB_USER` | cPanel MySQL user |
| `FORGE_DB_PASS` | that user's password |
| `FORGE_BASE_URL` | optional public URL used in generated docs |

Keep `.htaccess` out of any public archive — it contains the password.

6. Ensure the PHP process can write to `data/`, `build/` and `data/tmp/`.
7. Open `/preflight`; PHP version, PDO, MySQL and write permissions must show green before the first build.

**If MySQL is not available**, generation still runs: the database step is marked `skipped — schema included`, and the package ships `database/schema.sql` for phpMyAdmin import. ForgeStack never reports a skipped step as passed.

## 5. What a build produces

A generated project is self-contained: `public/index.php` front controller, `app/` (routes, controllers, services, automation), `core/` (auth, router, DB, mailer, validator, installer), `views/`, `database/` (schema + seeds), `cron/run.php`, `docs/`, `DEPLOYMENT.md`, `TECH_STACK.md`, `.env.example`, `manifest.json` with SHA-256 per file, `portfolio-entry.json` and a printable report.

All generated builds share the same hardening baseline: `password_hash()`/`password_verify`, login throttling, session regeneration, CSRF token on every POST, prepared statements, server-side validation, role checks, and an administrator seeded from `.env` — never a hardcoded password.

## 6. Tech report

| Layer | Choice | Why |
| --- | --- | --- |
| Language | PHP 8.2 | Runs everywhere cPanel does; no daemon required |
| Data | MySQL via PDO | Prepared statements, transactions, portable to MariaDB |
| Frontend | Hand-written CSS + progressive JS | ~37 KB CSS / 4.6 KB JS in generated sites; no framework tax |
| Architecture | Blueprint → Profile → Generator → Store | Same classes serve the web UI and the CLI, so behaviour cannot drift |
| QA | `php -l`, token/secret scan, preflight re-check | A build is not "done" until the manifest exists |
| Privacy | Registry stores no passwords | Generated credentials live only in the site's `.env` |

Known limits, stated plainly: ForgeStack does not design logos or source photography; it composes from curated industry profiles and hand-written templates. It produces sites in the styles it ships — it is a factory, not a general-purpose designer.
