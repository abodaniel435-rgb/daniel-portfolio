# NairaDesk

Payroll back office for Nigerian employers. The PAYE calculator that started life as the
[PayRite](../../demos/payrite/index.html) front-end demo is now a real full-stack product:
the arithmetic runs in PHP against statutory figures read from MySQL, every calculation is
stored, and a pay run freezes the lines it produced so last March still shows what was paid
in March.

Pure PHP 8.1+ + MySQL/MariaDB. No Composer, no Node, no build step. Deploys to cPanel by
uploading the folder and pointing the document root at `public/`.

## The problem it solves

A spreadsheet payslip cannot answer the three questions that matter when a labour officer,
an auditor or a departing employee asks: what rate was applied, what relief was claimed, and
who changed the statutory table afterwards. PayRite answered the first two in the browser and
kept nothing. NairaDesk answers all three from the server and keeps the evidence.

## What an employer can do

- Register — the account is signed in immediately and gets its own workspace.
- Add staff with basic/housing/transport/other components, pension election and rate, NHF and
  a rent-relief claim.
- Run one calculation on `POST /api/payroll` and see the band-by-band working, not just a
  total. The row it writes is the row you can audit afterwards.
- Create a monthly pay run: every employee is priced with the rate table as it stands at that
  moment and the full result is frozen into `pay_run_lines`.
- Reopen any run and see the frozen figures — editing a statutory rate afterwards changes
  nothing on a stored run.
- Delete a roster entry without losing history: a frozen line outlives the employee row it
  came from (`LEFT JOIN`, so the totals and the visible lines still agree).

## What an administrator can do

- `GET /admin` — live counts of accounts, workspaces, runs and logged calculations.
- `GET /admin/users` — every account, paginated, with enable/disable (you cannot disable
  yourself).
- `GET /admin/rates` — the statutory table itself: six PAYE band rates, five band widths, the
  Consolidated Relief Allowance fixed amount and percentage, rent-relief percentage and
  ceiling, employee and employer pension, NHF. Only values that actually differ are written,
  each change is audited, and the next calculation uses it.
- `GET /admin/audit` — every write, its actor, target and payload.
- `GET /admin/runs` — every pay run across every company.

## Why the rates are data

A relief ceiling moves between filing seasons. Shipped as a PHP constant that means a redeploy
for one number — and any deployment you forgot to update quietly calculates last year's tax.
Here the schedule is a table, the calculator is code, and the two change independently.

## Engine parity

`app/engines/Payroll.php` is a line-for-line port of the PayRite browser calculator: CRA is
`200,000 + 20% of annual gross`, rent relief is `min(20% of annual rent, 6,250,000)`, pension
comes off before tax and NHF comes off net.

```bash
"C:/xampp/php/php.exe" tools/engine_parity.php
```

That test cuts the calculator out of `demos/payrite/app.js` while it runs and executes the real
thing under Node against `Payroll::compute()` over 52 salary/relief combinations — 672 figures,
0 mismatches. No copy of the JavaScript is checked in, so if the demo is ever refactored the
anchors vanish and the test fails loudly rather than comparing a stale snapshot.
`tools/smoke.php` re-checks the HTTP response against the engine on every run.

## Tests

```bash
"C:/xampp/php/php.exe" -S 127.0.0.1:5058 -t public public/router.php   # terminal 1
"C:/xampp/php/php.exe" tools/smoke.php                                 # terminal 2
```

66 assertions over real HTTP: guest redirects, 404s, CSRF refusal (419) and forged tokens,
registration and workspace rules, the JSON API against the engine, duplicate pay-run months,
tenant isolation (another account cannot open your run, a member cannot reach `/admin`),
bcrypt-only storage, cookie flags, sign-out, the admin console, a live rate edit that the next
calculation picks up, audit contents, and the 8-failures-per-15-minutes lockout.

The administrator section signs in with a **throwaway admin the test creates and deletes**, so
no real password is ever needed to run it. Current status: **66 passed, 0 failed**.

## Local setup (Windows + XAMPP)

```bash
"C:/xampp/mysql/bin/mysql.exe" -u root \
  -e "CREATE DATABASE IF NOT EXISTS nairadesk CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
copy .env.example .env       # then set DB_*, INSTALL_KEY and ADMIN_EMAIL
"C:/xampp/php/php.exe" core/cli/install.php
"C:/xampp/php/php.exe" -S 127.0.0.1:5058 -t public public/router.php
```

`ADMIN_PASSWORD` is read from the process environment for the single install run and is
**never written to `.env`** — the installer hashes it with bcrypt and only the hash reaches
MySQL:

```bash
ADMIN_PASSWORD="your-long-admin-password" php core/cli/install.php
```

Remove `storage/installed.lock` to reinstall.

## cPanel deployment

1. Upload the folder without `.env`, `storage/installed.lock` and `storage/*.log`.
2. Point the domain or subdomain document root at `public/`. A subfolder works too —
   `base_path()` derives the prefix from `SCRIPT_NAME`, so every generated link, form action
   and fetch endpoint is correct without configuration.
3. Create a MySQL database and user in cPanel, grant all privileges, copy `.env.example` to
   `.env` and fill in `DB_*`, a fresh random `INSTALL_KEY` and `ADMIN_EMAIL`.
4. `chmod 775 storage/`.
5. Open `/install.php`, enter the install key and the administrator password once. The
   installer applies `database/schema.sql` + `seed.sql`, creates the admin, and writes the
   lock. It never echoes the password back into the page.
6. Delete `public/install.php` (or leave it — with the lock present it refuses to run).

## Structure

```
core/            shared ForgeStack core (bootstrap, Db, Auth, Router, Validator, Installer)
app/engines/     Payroll.php — the PAYE/CRA/relief/pension/NHF arithmetic
app/             routes.php, services.php (Desk), controllers/home|auth|work|admin.php
views/           layout, public pages, auth, member workspace, admin console
database/        schema.sql, seed.sql (the statutory table)
public/          document root: index.php, install.php, router.php, .htaccess, assets
tools/smoke.php  end-to-end HTTP test suite
storage/         installed.lock + logs (writable, gitignored)
```

## Security notes

- `password_hash()` (bcrypt) / `password_verify()`, automatic rehash on cost change, and
  `session_regenerate_id(true)` on both sign-in and registration.
- Login throttle: 8 failures per 15 minutes per email **or IP** (`login_attempts` holds no
  credential).
- `verify_csrf()` on every POST, including the JSON API; a missing or forged token is a 419
  before any controller runs.
- Role checks are server-side (`Auth::requireRole('admin')`); a member gets a 403, not a
  hidden button.
- Multi-tenancy is enforced by resolving the company from the session user id on every
  request — `company_id` is never read from the URL or a form.
- Prepared statements only; all output escaped with `e()`; `password` is not a column name in
  any table.
- Every write is recorded in `audit_log` with actor, target and JSON payload.
