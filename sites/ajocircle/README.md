# AjoCircle

Digital rotating-savings (ajo / esusu) platform for trusted groups — friends, market
associations, church groups and small-business cooperatives. Every circle gets a
transparent contribution ledger, generated due dates, reminders, verified payment
references and an auditable payout order.

Pure PHP 8.2 + MySQL. No Composer, no Node, no build step. Deploys to cPanel by
uploading the folder and pointing the document root at `public/`.

## What members can do

- Register, browse the public circle directory, open a circle and read its full rotation.
- Join an open circle: capacity is enforced, duplicates are blocked and the position is
  assigned by join order (the database enforces one position per member per circle).
- Receive a generated contribution schedule — one row per round, due date = start date +
  (round − 1) × frequency.
- Record a payment for a round with a reference (bank transfer, mobile money or cash).
  One reference per round; duplicates are refused in SQL and in the controller.
- Track next due contribution, contribution history, payout position and expected payout
  date, total saved, and a printable statement (`/member/statement`, browser print → PDF).

## What organisers can do

- Create circles (amount, frequency, member limit, start date, optional circle owner).
- Open / activate / close circles, verify contributions, mark rounds late, confirm payouts,
  suspend accounts, inspect the email outbox and run automation.
- Schedule one payout per round to the member holding that position; amount is
  contribution × active members. The database has a unique key on (circle_id, round_no).

## Automation (`cron/run.php`)

| Task | Effect |
| --- | --- |
| `mark_late_contributions` | pending contributions past their due date become `late` + email |
| `due_reminders` | reminds members exactly 3 days before a due date (once per round) |
| `flag_payouts_due` | flags scheduled payouts whose date has arrived + notifies the organiser |
| `monthly_member_statements` | one statement email per member per calendar month |
| `daily_admin_digest` | pooled total, overdue totals, verification queue, payouts due |

Run from cPanel cron:

```
php /home/USER/ajocircle/cron/run.php
# or
curl -s "https://yourdomain.com/cron/run.php?key=CRON_SECRET"
```

`storage/automation.log` records every run.

## Local setup (Windows, MySQL 8.4 on port 3307)

```bash
"C:/Program Files/MySQL/MySQL Server 8.4/bin/mysql.exe" -u root -P 3307 -h 127.0.0.1 \
  -e "DROP DATABASE IF EXISTS ajocircle; CREATE DATABASE ajocircle CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
"C:/xampp/php/php.exe" core/cli/install.php
"C:/xampp/php/php.exe" -S 127.0.0.1:8084 -t public public/router.php
```

The installer reads `ADMIN_EMAIL`, `ADMIN_PASSWORD` and `DEMO_PASSWORD` from `.env`.
Remove `storage/installed.lock` to reinstall from scratch.

## Demo accounts

| Role | Email | Password |
| --- | --- | --- |
| Organiser (admin) | `abodaniel435@gmail.com` | `ADMIN_PASSWORD` from `.env` |
| Member | `member@ajocircle.test` | `DEMO_PASSWORD` from `.env` |
| Member | `member2@ajocircle.test` | `DEMO_PASSWORD` from `.env` |
| Member | `member3@ajocircle.test` | `DEMO_PASSWORD` from `.env` |

`member4@…` through `member8@ajocircle.test` also exist with the same demo password.
Demo data: three circles — *Ikeja Market Traders Ajo* (open, weekly ₦5,000),
*Grace Chapel Harvest Circle* (active, monthly ₦20,000, live contributions,
verification queue, one late round, one payout settled and one scheduled) and
*Yaba Business Owners Circle* (completed, all six payouts paid).

## cPanel deployment

1. Upload the project folder (without `.env`, `storage/*.log`, `storage/installed.lock`).
2. Point the domain/subdomain document root at `public/` (or deploy in a subfolder —
   `base_path()` detects it and `APP_URL` pins absolute links).
3. Create a MySQL database + user, then copy `.env.example` to `.env` and fill it in.
4. `chmod 775 storage/` (writable for logs and the install lock).
5. Visit `/install.php` with `INSTALL_KEY`, or run `php core/cli/install.php`.
6. Set `MAIL_ENABLED=true` only after creating the sending mailbox; until then every
   message stays queued in `email_outbox` (fully auditable).
7. Add the cron entry above.

## Structure

```
core/            shared ForgeStack core (bootstrap, Db, Auth, Router, Mailer, Validator)
app/             routes, services (domain rules), automation, controllers, demo seed
views/           layout, public pages, member portal, admin console, print statement
database/        schema.sql (+ seed.sql placeholder)
public/          document root: index.php, install.php, router.php, .htaccess, assets
cron/run.php     automation entry point
storage/         logs + installed.lock (writable)
```

## Security notes

- `password_hash` / `password_verify`, session regeneration, login throttling
  (`login_attempts`, 8 failures per 15 minutes per email/IP).
- CSRF on every POST (`verify_csrf()` + `csrf_field()`), including join, pay, verify,
  payout, status changes, user toggles and automation.
- Server-side role checks (`Auth::requireRole('admin')`), ownership checks on circles.
- Prepared statements only; all output escaped with `e()`.
- Every state change is written to `audit_log` with user id and IP.
