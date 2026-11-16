# PayRite

Payroll for a small Nigerian business: the PAYE bands, the rent relief, the pension and the housing fund, worked out in one pass and saved as a record. It was a front-end calculator before it was rebuilt on ForgeStack; now the arithmetic runs in PHP, the roster and every month's payslips live in MySQL, and an accountant can correct a rate without a redeploy.

Nigeria taxes pay on a marginal band table that changed in 2026, and gives every employee who pays rent a relief worth up to ₦6.25m a year. Spreadsheet payroll gets that wrong quietly. PayRite keeps the bands in rows anyone can read and edits, and stamps every payslip with the band table that produced it, so a slip printed in December still shows the arithmetic that made it.

## What an employee can do

- Sign in and see only their own locked payslips and their own package.
- Read a slip line by line: gross, consolidated relief, rent relief, taxable pay, PAYE by band, pension, housing fund, net.
- See the band table their slip was built with, in the slip itself.
- Leave a query note on their record that appears on the administrator's dashboard.

A draft month is never visible to staff. Payslips appear when the run is locked.

## What an administrator can do

- Keep the roster: employees, their salary components, rent, pension rate and status.
- Build a payroll run for a month — every active employee computed through the live band table inside one database transaction.
- Lock a run (publishes payslips to staff) or void it (withdraws them, keeps the slips as evidence, frees the month to be rebuilt).
- Edit the PAYE bands and the relief constants, with a live ladder that recalculates as you type.
- Manage staff accounts and link an account to a roster record.
- Read the audit trail and the queued notification emails.

## Structure

```
sites/payrite/
  core/                ForgeStack: Db, Auth, Router, Mailer, Validator, Installer
  app/
    engines/Payroll.php  the tax engine — bands, reliefs, deductions, verdicts
    services.php         Desk: every database touch, in one place
    controllers/         home, auth, staff, admin
    routes.php           URL -> controller
    demo_users.php       three staff logins for the demo
    demo_seed.php        eight-person roster and two seeded months
  views/
    home.php             landing + calculator
    rates.php            public band table
    auth/  staff/  admin/
  database/schema.sql    ten tables
  tools/smoke.php        end-to-end test over real HTTP
  public/index.php       the only document root
```

## The PAYE calculation

The engine lives in `app/engines/Payroll.php` and the band table lives in the `pay_bands` table. Payable pay is annualised, then:

```
taxable = max(0, annualGross − min(CRA, annualGross) − rentRelief − annualPension)
CRA     = ₦200,000 + 20% of gross, capped at what is earned
relief  = min(20% of annual rent, ₦6.25m)
PAYE    = the band table walked slice by slice
net     = gross − PAYE/12 − pension − housing fund
```

Each band is a `slice_width` and a `rate`. The last slice is open-ended; the engine forces it, so an edited table cannot stop mid-way and under-tax a high earner. Consolidated relief is capped at the annual gross, so the relief a slip prints can never exceed the pay it is measured against.

## Testing

```
php tools/smoke.php http://127.0.0.1:5075
```

It drives the real HTTP surface — sessions, CSRF tokens, redirects, the JSON API — and then checks what MySQL kept, so a payroll record that looks right on screen but disagrees with the engine is caught. It also runs the engine on its own, without a web server, so a wrong band is pinned to arithmetic rather than a template. 147 assertions, self-contained (it registers throwaway accounts and deletes them, snapshots the band table and puts it back).

## Local setup (Windows + XAMPP)

1. Create the database: `CREATE DATABASE payrite CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;`
2. Copy `.env.example` to `.env`, point `DB_*` at it, leave `MAIL_ENABLED=false`.
3. Install, with the admin password from the environment so it never lands in a file, a command line, or the shell history:

   ```
   ADMIN_PASSWORD="$(php -r 'echo "…";')" DEMO_PASSWORD="$(php -r 'echo "…";')" php core/cli/install.php
   ```

4. Serve it with the repo-root document-killer router:

   ```
   php -S 127.0.0.1:5075 -t public public/router.php
   ```

The installer applies `database/schema.sql`, seeds the bands, creates the single administrator, and if `DEMO_PASSWORD` is set also makes the demo staff and the two seeded months.

## cPanel deployment

Document root is `public/`. Everything above it stays outside the web root. Apache reads `public/.htaccess`; there is no Node process, no Redis, no queue worker. Mail uses PHP's `mail()` through cPanel's own transport, so there is no SMTP password to store — set `MAIL_ENABLED=true` only once the SPF and DKIM records for `MAIL_FROM` are in place. No cron is required: a payroll run is built on demand, not on a schedule.

## Security notes

- Passwords are hashed with bcrypt (`password_hash`); the plain password never reaches MySQL and the stored hash is never shown.
- Registration creates a **staff** account. The role is set by the server and is never read from the posted form; there is no route by which signing up grants admin. The single administrator is created by the installer from `ADMIN_EMAIL`.
- `Auth::register()` validates the role is a safe lowercase slug, so a caller cannot write an arbitrary value into a privilege column.
- Login is throttled in MySQL (`login_attempts`), by email and by IP, not in the browser — six wrong tries in fifteen minutes pauses further attempts.
- Admin routes are behind `Auth::requireRole('admin')`, which is enforced **before** any form data is parsed; a staff member hitting a desk POST gets 403, not a CSRF rejection.
- Every query is a prepared statement; `Db::update()` writes its SET clause in whichever placeholder style the caller used so positional and named parameters cannot be mixed.
- The admin's own account cannot be disabled from the staff-admin screen.
- Validation, CSRF (`csrf_field`/`verify_csrf`, 419 on failure), and session regeneration on sign-in.
- Every significant action is written to `audit_log`, including rejected rate changes.
