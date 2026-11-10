# RoutePilot

Last-mile dispatch and public parcel tracking for small logistics operators in Lagos.
Built on the ForgeStack shared PHP core: plain PHP 8.2 + MySQL, no Composer, no build step.

RoutePilot replaces the WhatsApp dispatch group with an auditable operations desk:

- **Dispatch** books a shipment once and RoutePilot issues a unique tracking code (`RP-YYMMDD-XXXX`).
- **Couriers** see only the parcels assigned to them and push every scan with a location + note.
- **Customers** open a public tracking page (no login) with a route timeline, ETA copy and privacy-safe recipient details.
- **Administrators** manage people, revenue and the email outbox, and run automation on demand.

## Roles

| Role | Access |
| --- | --- |
| `dispatcher` | Dispatch board, shipments, new shipment, couriers, status updates |
| `courier` | Own queue only (`/courier/queue`), own parcel detail, own availability |
| `admin` | Everything, including the dispatch desk, users, ledger, outbox, automation |
| public | Home page and `/track?code=...` |

Dispatcher and admin accounts are created by an administrator. Couriers may self-register
from `/register` (that is the only public registration role).

## Core flows

1. **Create shipment** — sender name/phone, recipient name/phone/email, origin, destination,
   package description, weight, service (`same_day` / `express` / `standard`), fee, pickup date.
   A unique tracking code is generated, the recipient is emailed the tracking link.
2. **Assign courier** — active, available riders only; daily capacity is enforced server-side.
3. **Courier status updates** — `picked_up → in_transit → out_for_delivery → delivered` (or `failed`
   with a mandatory reason). Every update writes a `tracking_events` row with user, location,
   note and timestamp, and emails the recipient.
4. **Public tracking** — `/track?code=RP-...` shows the timeline, current status, ETA copy,
   origin/destination and the recipient's **first name only**.
5. **Dispatch board** — KPI cards, active shipments, assign/unassign, courier workload, filters.
6. **Admin console** — user/courier management, revenue ledger, email outbox, automation.

## Automation

`app/automation.php` returns named tasks run by `Automation::run('RoutePilot')`:

| Task | Behaviour |
| --- | --- |
| `flag_overdue_pickups` | Pickup date passed while still unassigned → flag + automation event |
| `flag_delayed_transit` | `in_transit` with no scan for 3+ days → flag + event once, delay email |
| `eta_reminders` | `out_for_delivery` parcels → "arriving today" email once |
| `daily_digest` | Daily operations summary emailed to the active administrator |

Run manually:

```bash
php cron/run.php
# or, from cPanel cron:
# curl -s "https://yourdomain.com/routepilot/cron/run.php?key=CRON_SECRET"
```

Every run is logged to `storage/automation.log` and shown on the admin overview.

## Local installation

```bash
# 1. create the database
"C:/Program Files/MySQL/MySQL Server 8.4/bin/mysql.exe" -u root -P 3307 -h 127.0.0.1 \
  -e "DROP DATABASE IF EXISTS routepilot; CREATE DATABASE routepilot CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

# 2. configure .env (APP_URL, DB_*, ADMIN_PASSWORD, DEMO_PASSWORD) then install
cd new-sites/routepilot
"C:/xampp/php/php.exe" core/cli/install.php

# 3. serve
"C:/xampp/php/php.exe" -S 127.0.0.1:8083 -t public public/router.php
```

`public/install.php` offers the same installer through the browser (protected by `INSTALL_KEY`).

The installer applies `database/schema.sql`, `database/seed.sql`, creates the administrator from
`ADMIN_EMAIL`/`ADMIN_PASSWORD`, then the demo accounts and demo Lagos shipments.

### Demo credentials (local `.env`)

| Account | Email | Password |
| --- | --- | --- |
| Administrator | `abodaniel435@gmail.com` | `ADMIN_PASSWORD` from `.env` |
| Dispatcher | `dispatcher@routepilot.test` | `DEMO_PASSWORD` from `.env` |
| Courier 1 | `courier@routepilot.test` | `DEMO_PASSWORD` from `.env` |
| Courier 2 | `courier2@routepilot.test` | `DEMO_PASSWORD` from `.env` |

Demo data includes five shipments (delivered, delayed in transit, out for delivery,
unassigned/overdue pickup, failed) so every screen has realistic content on first load.

## cPanel deployment

1. Upload the project to e.g. `/home/user/routepilot`, keeping `public/` as the document root
   (`public_html` contents = the contents of `public/`, with `app/`, `core/`, `views/`,
   `database/`, `cron/` and `storage/` one level above).
2. Create a MySQL database and user in cPanel; copy `.env.example` to `.env` and fill in
   `APP_URL`, `DB_*`, `MAIL_ENABLED`, `MAIL_FROM`, `INSTALL_KEY`, `CRON_SECRET`.
3. Run `php core/cli/install.php` from the cPanel terminal (or open `/install.php`).
4. Ensure `storage/` is writable (0775) and add a cron job:
   `*/15 * * * * /usr/local/bin/php /home/user/routepilot/cron/run.php`
5. Delete `public/install.php` and `storage/installed.lock` should stay in place to block reinstall.

`public/.htaccess` handles rewrites, security headers and denies access to dotfiles,
`.sql`, `.md`, `.log` and `.lock` files.

## Structure

```
routepilot/
  core/                 shared ForgeStack core (never edited)
  app/
    routes.php          all GET/POST routes
    services.php        domain helpers, tracking codes, statuses, emails
    automation.php      named automation tasks
    demo_users.php      demo accounts
    demo_seed.php       couriers + five demo shipments
    controllers/        home, auth, dispatcher, courier, admin
  views/                layout + public, auth, dispatch, courier, admin screens
  database/schema.sql   required core tables + couriers, shipments, tracking_events
  database/seed.sql     reference notes
  public/               document root (front controller, router, .htaccess, assets)
  cron/run.php          automation entry point (CLI or ?key=)
  storage/              logs, cron.lock, installed.lock
```

## Database

Required core tables: `users`, `login_attempts`, `email_outbox`, `audit_log`.
Domain tables:

- `couriers` — rider profile (vehicle, zone, plate, daily capacity, availability) linked to `users`.
- `shipments` — tracking code, parties, route, service, fee, status, courier, automation flags.
- `tracking_events` — immutable scan log (status, location, note, source, author, timestamp).

All tables are InnoDB/utf8mb4 with foreign keys and indexes on the hot paths
(status, courier, pickup date, event timeline, outbox status).

## Notes on security

- Prepared statements only (`Db::run/one/all/value/insert/update`).
- CSRF token on every POST; session regeneration on login; throttled sign-ins.
- Role checks happen server-side in each controller (`Auth::requireRole`).
- Escaped output through `e()`; public tracking exposes only the recipient's first name.
- All state changes are written to `audit_log` with user, IP and timestamp.
- No secrets in code — `.env` is excluded from deployment archives.
