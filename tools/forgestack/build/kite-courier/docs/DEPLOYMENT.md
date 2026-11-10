# Deployment guide — Kite Courier

This package is a complete PHP 8.1+ / MySQL application. It runs on ordinary
cPanel shared hosting with Apache. No Composer, Node, Redis, cron daemon or SSH access is
required.

Build: `kite-courier` · Database: `forge_kite_courier` · Status: Installed into 127.0.0.1:3307/forge_kite_courier.

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
2. Create a database, e.g. `forge_kite_courier`. cPanel prefixes it with your account name,
   so the real name may look like `cpuser_forge_kite_courier`.
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
| `APP_NAME` | Site name in titles and email | `Kite Courier` |
| `APP_URL` | Canonical URL, no trailing slash | `https://example.com` |
| `APP_TIMEZONE` | PHP timezone for dates and cron | `Africa/Lagos` |
| `APP_DEBUG` | Verbose errors; keep `false` in production | `false` |
| `DB_HOST` | MySQL host from cPanel | `localhost` |
| `DB_PORT` | MySQL port | `3306` |
| `DB_NAME` | Full cPanel database name | `cpuser_forge_kite_courier` |
| `DB_USER` | Full cPanel database user | `cpuser_site` |
| `DB_PASS` | Database password | *(secret)* |
| `MAIL_ENABLED` | `true` to attempt PHP `mail()`; `false` queues only | `false` |
| `MAIL_FROM` | Envelope/from address | `no-reply@example.com` |
| `MAIL_FROM_NAME` | From display name | `Kite Courier` |
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

The current build reports: **Installed into 127.0.0.1:3307/forge_kite_courier.**. Tables: audit_log, email_outbox, login_attempts, messages, record_events, shipments, users.

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

Generated by ForgeStack 1.0.0 (core 1.0.0).