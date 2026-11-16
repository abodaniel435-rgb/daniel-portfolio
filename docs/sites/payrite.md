# PayRite — deployment guide & tech report

## 1. What you are deploying
A Nigerian payroll desk an administrator runs and staff can check: a public PAYE calculator, a roster, draft → lock → void monthly runs, server-computed payslips with the arithmetic printed, editable tax bands and relief constants, payslip e-mail notifications, and an audit trail.

- **Local source:** `sites/payrite`
- **Stack:** PHP 8.2, MySQL, PDO, server-rendered views. No build step.
- **Database:** MySQL / MariaDB — schema applied by the installer from `database/schema.sql`
- **Hosting:** cPanel with PHP 8.1+ and MySQL. No Composer, Node, Redis or Docker.

The browser does no payroll arithmetic. The landing-page calculator, the JSON endpoint and the payslips all call the same `Payroll::compute()` on the server, so a figure a visitor is shown and a figure on a locked payslip cannot disagree.

## 2. Server requirements
| Requirement | Value |
| --- | --- |
| PHP | 8.1 or newer (8.2 tested) |
| Extensions | `pdo_mysql`, `mbstring`, `openssl`, `json` |
| MySQL | 5.7+ / MariaDB 10.3+ |
| Writable path | `storage/` (session files, logs, `installed.lock`) |

## 3. File layout
Upload the project so that **`public/` is the document root**. The application folders stay one level above it and are never web-accessible.

```
public/      document root: index.php, install.php, router.php, .htaccess, assets/
app/         routes, controllers, the Payroll engine, services, demo seed
views/       layout, public pages, desk screens, staff portal
core/        shared framework (do not edit)
database/    schema.sql, seed.sql
tools/       set_password.php, smoke.php
storage/     writable: logs, installed.lock
.env         secrets (never deploy)
```

If the host forces `public_html`, upload the whole project to `/home/USER/payrite` and copy the **contents** of `public/` into `public_html` (or the subdomain root); the bundled `.htaccess` routes every non-file request to `index.php`, which finds the application one level up.

## 4. Database
1. cPanel → **MySQL Databases** → create a database and a user; grant ALL on that database.
2. Note the full names (`account_dbname`, `account_dbuser`) — cPanel prefixes them.
3. Do not import `schema.sql` by hand; the installer applies it and seeds the six PAYE bands, the relief constants and the administrator.

Ten tables: `users`, `login_attempts`, `pay_bands`, `rates`, `employees`, `payroll_runs`, `payslips`, `calculations`, `email_outbox`, `audit_log`.

`payroll_runs.period` is indexed `(period, status)` and deliberately **not** UNIQUE. A month is paid once, and that rule is enforced in the application (`Desk::liveRunByPeriod()`) rather than the table, because a voided run must stay on file as evidence while the same month is rebuilt as a fresh draft.

## 5. Environment variables
Copy `.env.example` to `.env` in the project root and fill in:

| Key | Meaning |
| --- | --- |
| `APP_NAME` | Shown in the masthead and mail subject lines |
| `APP_URL` | Canonical URL of the deployed site, including any subfolder |
| `APP_TIMEZONE` | `Africa/Lagos` — payslip periods and due dates are read in it |
| `APP_DEBUG` | `false` in production |
| `DB_HOST` | Usually `localhost` on cPanel |
| `DB_PORT` | `3306` on cPanel |
| `DB_NAME` / `DB_USER` / `DB_PASS` | Full cPanel database credentials |
| `INSTALL_KEY` | Long random string guarding the installer |
| `ADMIN_NAME` / `ADMIN_EMAIL` | The single authorised administrator (`abodaniel435@gmail.com`) |
| `ADMIN_PASSWORD` | Read once by the installer, hashed, then never stored anywhere |
| `DEMO_PASSWORD` | Optional, CLI installer only — creates the three `@payrite.test` staff logins. Omit it for a real deployment |
| `MAIL_ENABLED` | `false` keeps messages in `email_outbox` without sending |
| `MAIL_FROM` / `MAIL_FROM_NAME` | Sender address on your domain |

Rules: `APP_DEBUG=false` in production, a long random `INSTALL_KEY`, and SPF + DKIM records in place before `MAIL_ENABLED=true`. Never commit `.env`, never put a password in a URL, a form or front-end JavaScript.

There is no SMTP password. Delivery uses PHP's `mail()` — cPanel's own transport — and every message is written to `email_outbox` first, so a mailbox that is not configured yet loses nothing.

## 6. Install
1. Upload, then create `.env` as above.
2. Point the domain/subdomain at `public/`.
3. Run the installer **once**:
   - Browser: `https://yourdomain.com/install.php?key=YOUR_INSTALL_KEY`
   - or SSH: `php /home/USER/payrite/core/cli/install.php`

   On hosts where the password cannot be exported first, pipe it on stdin instead of writing it into any file:
   ```
   ADMIN_PASSWORD='set-it-now' php /home/USER/payrite/core/cli/install.php
   ```
   Set it from your password manager for that single command; never commit the line.
4. The installer writes `storage/installed.lock` and refuses to run twice. After success, delete or block `public/install.php`.
5. Sign in at `/login` with `ADMIN_EMAIL` and the password you set. You land on the payroll desk.
6. To change a forgotten password without re-installing: `php tools/set_password.php user@example.com` — it reads the new password from stdin and records the reset in `audit_log`.

## 7. First ten minutes after install
1. **Desk → Employees → Add employee**: staff number, name, monthly basic, housing, transport, other, annual rent and whether rent relief is claimed.
2. **Desk → Runs → New run**: pick the month (`YYYY-MM`). The draft prices the whole roster with the current bands and constants.
3. Check two or three slips, then **Lock run**. Locking freezes each slip: the components, the taxable figure, the PAYE, and copies of the band table and constants used (`bands_json`, `rules_json`). Later rate edits never rewrite a locked month.
4. **Notify staff** on a locked run: queues one payslip-available e-mail per linked employee.
5. **Desk → Staff → Link**: match a registered `staff` login to its employee row. Until it is linked, that person signs in to an empty portal — no payroll data is ever matched by name or e-mail guesswork.
6. **Desk → Rates**: the six band widths and rates plus the relief constants. Edits apply to the next draft, and are audited.
7. If a month is wrong, **Void run**, then build a fresh draft for the same period. The voided run stays visible as the record of what was cancelled.

## 8. Cron / automation
None required. PayRite has no scheduled tasks — payslips are computed when a run is drafted or locked, and notifications are sent when the administrator presses the button. If you later want reminder e-mails, add a `cron/run.php` and a cPanel job; the framework already ships `core/Automation.php`.

## 9. Post-deploy checks
1. The home page calculator returns gross, PAYE, pension, NHF and net with the working shown — no console errors.
2. `/api/paye?basic=280000&housing=84000&transport=56000&other=20000` returns JSON, and accepts both `annual_rent`/`rent` and `pension_rate`/`pension`.
3. A ₦1,400,000/month package with ₦6,000,000 rent produces net **₦1,056,413.33** — the published worked example. If it differs, the bands or constants were edited.
4. The public calculator logs a `calculations` row; an anonymous one is attributed to "Public calculator".
5. Register a staff account, then confirm it cannot open `/admin` (**403**, not a redirect to the login page).
6. As the administrator, create an employee, draft a run for this month, lock it, and confirm the roster totals equal the sum of the slips.
7. A second run for a month that already has a live run is refused with a message naming the period; after voiding the first, the second is allowed.
8. An unlinked staff account sees no payslips. After linking, the person sees only their own — and only `locked` ones, never a draft.
9. `/admin/audit` lists the create, lock and link events with actor and IP.
10. `email_outbox` holds the notification rows (`MAIL_ENABLED=false`) or the mail actually arrives (`true`).
11. A POST without a CSRF token receives **419**, and a signed-out POST to a staff route is bounced to `/login` before the token is even read.

## 10. Tech report
| Area | Decision |
| --- | --- |
| Architecture | Front controller + explicit routes; controllers separated from views; one payroll engine shared by the public calculator, the desk and the slips |
| Data access | PDO prepared statements only; roster pricing, locking and voiding run inside transactions |
| Money | `DECIMAL(14,2)`/`DECIMAL(16,2)` columns, never floats in the database; the engine rounds once, at the end |
| Tax model | Progressive PAYE across editable bands; consolidated relief capped at annual gross; rent relief at 20% capped at ₦6.25m; pension 8/10%, NHF 2.5% a side |
| Immutability | Locked slips store their own band and constant copies, so history re-renders exactly as computed |
| Auth | `password_hash` (bcrypt) / `password_verify`, session regeneration, MySQL-backed login throttle, server-side role guards before any parsing |
| Roles | `admin` and `staff` only. Registration can never mint an administrator; the role is never read from a form |
| CSRF | Token on every POST, verified after the guard — 419 on failure |
| Output | Escaped through `e()`; roster names, staff numbers and flashes never enter HTML raw |
| Mail | Queued to `email_outbox` first, delivered after commit via `mail()` |
| Audit | Every state change in `audit_log` with actor, target, IP and payload |
| Performance | Indexed hot paths (`employees(status,name)`, `payslips(employee_id,period)`), filemtime cache-busted assets, background plate as one WebP |
| Accessibility | Skip link, labelled fields, `aria-live` result panel, visible focus, `prefers-reduced-motion` neutralises the motion layer, mobile masthead wraps below the brand |
| Testing | `tools/smoke.php` — **147 passed, 0 failed** on a fresh install: every one of the 33 route registrations probed anonymously, as administrator and as staff, plus the engine's band-walking, caps and rounding |

### Known limits
- Figures are the 2026 PITA reference bands and reliefs. They are editable at `/admin/rules` and must be re-checked against current FIRS / state revenue guidance each filing year; nothing fetches them.
- No payslip PDF and no gateway: notifications carry a link to the slip, and remittance is settled outside the app.
- Single-employer by design. Multi-company payroll — a workspace per employer — is NairaDesk, not PayRite.
- No state levies, benefit-in-kind, VAT or withholding, and no ITF / NSITWA line. Figures are indicative, not a tax opinion.
- Mail depends on the host's mail configuration; SPF/DKIM failures land in the outbox rather than the inbox.

## 11. Rollback and backups
Export the database from phpMyAdmin and zip the current files before upgrading; restore both to roll back. `storage/installed.lock` lives outside the database — dropping the DB alone still reports "already installed", so remove the lock file only when you genuinely intend a fresh install.
