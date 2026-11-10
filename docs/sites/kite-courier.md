# Kite Courier — deployment guide & tech report

## 1. What it is

A complete same-day courier website for a Lagos operator: customers book a pickup and get a tracking code, watch its status timeline without an account, and the operator works the queue from a protected admin desk.

It exists to prove ForgeStack end-to-end: the site was **generated from a written brief**, installed into MySQL, QA-checked and then driven through its real user journeys — booking → tracking → staff sign-in → status change → outbox.

- **Source:** `agent/forgestack/build/kite-courier`
- **Built with:** PHP 8.2, MySQL + PDO, vanilla CSS/JS, `.htaccess` front controller
- **Size:** 64 files, ~266 KB, 42 templates composed + shared core
- **Database:** 7 tables, 12 seeded reference records
- **Status:** Build produced, installed and tested locally; packaged for cPanel

## 2. Feature set

| Area | What is real |
| --- | --- |
| Booking | Validated pickup form (sender, recipient, addresses, window, vehicle) → unique reference such as `KES-261109-3FB5` |
| Tracking | Public page renders status, timeline and first name only — no phone or address leaks |
| Admin desk | Records list with filters, status transitions, notes, per-record event log |
| Auth | `password_hash`/`password_verify`, throttled login, session regeneration, CSRF on every POST, roles |
| Automation | Reminder and digest tasks in `app/automation.php`, runnable by cPanel cron |
| Mail | Every message queues to `email_outbox`; `MAIL_ENABLED=false` still records everything |
| Reports | Live aggregates from the database, not sample numbers |

## 3. Local run

```bash
cd agent/forgestack/build/kite-courier
cp .env.example .env          # set DB_NAME=forge_kite_courier, ADMIN_EMAIL, ADMIN_PASSWORD, INSTALL_KEY
php -S 127.0.0.1:8125 -t public public/router.php
```

First request redirects to `install.php?key=INSTALL_KEY`; the installer creates the tables, seeds reference data, writes `storage/installed.lock` and seeds the administrator from `.env`. Local MySQL for this workspace: `127.0.0.1:3307`, database `forge_kite_courier`.

## 4. Deploy to cPanel

The build ships a full guide at `docs/DEPLOYMENT.md`; the short version:

1. **MySQL Databases** — create `forge_kite_courier`, a user, and grant All Privileges. Note the cPanel-prefixed names.
2. **Upload** the build into `/home/USER/kite-courier` and point the subdomain document root at `public/` (preferred). If you cannot move the docroot, see §3 of the bundled guide for the flattened alternative.
3. **`.env`** — copy `.env.example`, set `APP_URL`, `DB_*`, a long `INSTALL_KEY`, and `ADMIN_EMAIL` / `ADMIN_PASSWORD`. `.htaccess` already denies dotfiles and `*.env`.
4. **Install** — visit `https://your-domain/install.php?key=...` once. The lock file prevents re-runs. Rotate or remove `INSTALL_KEY` afterwards.
5. **Cron** — add: `php /home/USER/kite-courier/cron/run.php` (hourly). For HTTP cron, append `?key=CRON_SECRET`.
6. **Smoke test** — book a pickup, open `/track?ref=...`, sign in at `/login`, change the status, confirm the public timeline updates and the outbox row exists.

No Node, Composer, Redis or write access to system PHP is required. PHP 8.1+ with `pdo_mysql` is the floor.

## 5. Tech report

| Layer | Decision | Reason |
| --- | --- | --- |
| Runtime | PHP 8.1+ front controller | Works on any cPanel plan; no daemon |
| Data | MySQL + PDO prepared statements | Transactional stock/status updates, portable to MariaDB |
| Views | Server-rendered PHP templates | First paint without JS; works with JS disabled for core flows |
| CSS/JS | Hand-written, ~37 KB / ~4.6 KB | No framework payload, no build step on the host |
| Security | `password_hash`, throttling, CSRF, roles, `.env` | Baseline every generated site inherits |
| Delivery | `manifest.json` + `checksums.sha256` | Every file's SHA-256 recorded at build time |

Limits worth stating: the map on the tracking page is text-based (no paid maps key), and email is queued rather than sent unless the host's `mail()` works. Both are deliberate shared-hosting choices, documented in the generated `TECH_STACK.md`.
