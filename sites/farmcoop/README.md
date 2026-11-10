# FarmCoop

A produce aggregation marketplace for smallholder farmers and bulk buyers. Farmers publish live
harvest quantities; buyers reserve them at a published unit price; every order is stock-checked
inside a database transaction so two buyers can never claim the same crate.

Pure PHP 8.2 + MySQL. No Composer, no Node, no build step — upload and run on cPanel.

---

## What is inside

| Area | Summary |
| --- | --- |
| `public/` | Document root. `index.php` front controller, `install.php` one-time installer, `.htaccess` rewrites + security headers, `assets/` CSS and JS |
| `app/` | `routes.php`, `services.php` (domain helpers), `automation.php`, `controllers/` (home, auth, farmer, buyer, admin), demo users + seed |
| `views/` | Layout, public pages, farmer/buyer/admin portals, shared partials |
| `core/` | ForgeStack shared core — copied verbatim, **do not edit** |
| `database/` | `schema.sql` (all tables) and `seed.sql` (Nigerian states) |
| `cron/run.php` | Automation entry point (CLI or `?key=CRON_SECRET`) |
| `storage/` | Writable: `installed.lock`, `automation.log` |

### Roles and flows

- **Farmer** — register with role choice + farm profile, publish listings (produce, category,
  quantity, unit, unit price, available-from, pickup window, location, description), pause /
  resume / reserve listings, accept / reject / mark-ready / complete orders, see earnings.
- **Buyer** — browse the public marketplace with category, state and text filters, open a listing,
  place an order (pickup or delivery, requested date, notes) and follow it to completion; cancel
  while pending.
- **Admin** — GMV, active farmers and buyers, pending orders, live listings, 7-day order chart,
  user suspension, farm verification, all listings and orders, email outbox, run automation.

### Listing and order states

- Listing: `open` → `paused` / `reserved` / `sold` / `expired`.
  `reserved` means the farmer is holding the harvest for an off-platform buyer.
- Order: `pending` → `accepted` → `ready` → `completed`, with `rejected` and `cancelled` as exits.
  Rejection and cancellation return the quantity to the listing and reopen it if it had sold out.

Every state change is written to `order_events` and to `audit_log`, and every notification is
queued into `email_outbox` before any delivery attempt.

---

## Local install (already done in this workspace)

Local MySQL 8.4 runs on port **3307** (`root`, empty password).

```bash
# 1. Create the database
"C:/Program Files/MySQL/MySQL Server 8.4/bin/mysql.exe" -u root -P 3307 -h 127.0.0.1 \
  -e "DROP DATABASE IF EXISTS farmcoop; CREATE DATABASE farmcoop CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

# 2. Install (applies schema.sql + seed.sql, creates admin and demo data)
cd new-sites/farmcoop
"C:/xampp/php/php.exe" core/cli/install.php

# 3. Serve
"C:/xampp/php/php.exe" -S 127.0.0.1:8082 -t public public/router.php
```

`.env` for this workspace already points at `http://127.0.0.1:8082`, database `farmcoop` on
port 3307. The passwords live only in `.env` (`ADMIN_PASSWORD`, `DEMO_PASSWORD`) and are not repeated in this file.

### Demo accounts

| Role | Email | Password |
| --- | --- | --- |
| Admin | `abodaniel435@gmail.com` | `ADMIN_PASSWORD` from `.env` |
| Farmer | `farmer@farmcoop.test` | `DEMO_PASSWORD` from `.env` |
| Buyer | `buyer@farmcoop.test` | `DEMO_PASSWORD` from `.env` |
| Farmer | `halima@farmcoop.test` | `DEMO_PASSWORD` from `.env` |
| Farmer | `emeka@farmcoop.test` | `DEMO_PASSWORD` from `.env` |
| Buyer | `restaurant@farmcoop.test` | `DEMO_PASSWORD` from `.env` |

Demo seed: 3 farms and 6 Nigerian produce listings (Roma tomatoes, tatashe peppers, Ofada rice,
yellow maize, plantain, Puna yam) plus two example orders in different states.

---

## Deploying to cPanel

1. Upload the project so that **`public/`** is the document root (or point the subdomain at it).
   If the host forces `public_html`, upload the whole folder and add a rewrite from the domain
   root into `public/` — never expose `app/`, `core/`, `storage/` or `.env` directly; the bundled
   `.htaccess` denies dotfiles and directory indexes.
2. Create a MySQL database and user in cPanel, then copy `.env.example` to `.env` and fill in
   `APP_URL`, `DB_*`, `ADMIN_EMAIL`, `ADMIN_PASSWORD`, `INSTALL_KEY` and `CRON_SECRET`.
   Leave `APP_DEBUG=false` and `MAIL_ENABLED=false` until the sending mailbox exists.
3. Run the installer once — either visit `https://yourdomain.com/install.php?key=INSTALL_KEY`
   or run `php core/cli/install.php` from SSH.
4. Delete or block `public/install.php` after install (the installer refuses to run twice; it
   checks `storage/installed.lock`).
5. Ensure `storage/` is writable (755/775) so the automation lock file and log can be written.
6. Add a cPanel cron job every 15 minutes:
   `php /home/USER/farmcoop/cron/run.php` — or curl `/cron/run.php?key=CRON_SECRET`.
7. Set `MAIL_ENABLED=true` once `MAIL_FROM` is a real mailbox on the domain.

### Automation suite (`app/automation.php`)

| Task | Behaviour |
| --- | --- |
| `expire_stale_listings` | Listings whose available date passed 7+ days ago with no pending/accepted/ready orders become `expired`; open listings at zero stock become `sold` |
| `remind_pending_orders` | Orders pending more than 24h email the farmer once (`reminder_sent_at`) |
| `close_unconfirmed_pickups` | `ready` orders 5+ days past the requested date are completed and both parties notified |
| `daily_digest` | One email a day to `ADMIN_EMAIL`: new orders, pending value, completed GMV, live listings, unverified farms, latest orders |

Run history is appended to `storage/automation.log`. The admin outbox page also has a
**Run automation now** button.

---

## Security notes

- `password_hash` / `password_verify`, session regeneration on login, throttling at 8 failed
  attempts per 15 minutes, suspended accounts blocked at the guard.
- CSRF token on every POST (`csrf_field()` + `verify_csrf()`), including admin actions and
  public order tracking.
- Prepared statements only, through `Db::*`; no string-interpolated user input in SQL.
- Role checks happen server-side in every controller (`Auth::requireRole`), not in the browser.
- All output escaped with `e()`; images validated as absolute URLs.
- Stock is decremented with `SELECT ... FOR UPDATE` inside `Db::transaction`, so concurrent
  checkouts cannot oversell a listing.
- `.env` holds every secret and is never committed; `.htaccess` denies dotfiles.

## Known gaps

- Payments are out of scope: settlement happens off-platform and the admin GMV figure reflects
  completed orders only.
- Mail is queued only; actual delivery depends on the host's `mail()` and `MAIL_ENABLED=true`.
- Listing photos are external URLs (Unsplash in the demo seed); no upload pipeline.
