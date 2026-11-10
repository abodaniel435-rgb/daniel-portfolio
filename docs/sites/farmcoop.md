# FarmCoop — deployment guide & tech report

## 1. What you are deploying
A produce aggregation marketplace: farmers publish live harvest quantities, buyers reserve them at a published price, and every checkout is stock-checked inside a database transaction.

- **Local source:** `new-sites/farmcoop`
- **Stack:** PHP 8.2, MySQL, PDO, server-rendered views, cron automation
- **Database:** MySQL / MariaDB — schema included in `database/schema.sql`
- **Hosting:** cPanel with PHP 8.1+ and MySQL. No Composer, Node, Redis or Docker.

## 2. Server requirements
| Requirement | Value |
| --- | --- |
| PHP | 8.1 or newer (8.2 tested) |
| Extensions | `pdo_mysql`, `mbstring`, `openssl`, `json` |
| MySQL | 5.7+ / MariaDB 10.3+ |
| Writable path | `storage/` (logs, lock files) |

## 3. File layout
Upload the project so that **`public/` is the document root**. The application folders stay one level above it and are never web-accessible.

```
public/      document root: index.php, install.php, .htaccess, assets/
app/         routes, controllers, services, automation, demo seed
views/       layouts and pages
core/        shared framework (do not edit)
database/    schema.sql, seed.sql
cron/        run.php
storage/     writable: logs, locks
.env         secrets (never deploy)
```

If the host forces `public_html`, upload the whole project to `/home/USER/farmcoop` and copy the **contents** of `public/` into `public_html` (or the subdomain root), then point the front controller at the application folder using the bundled `.htaccess`.

## 4. Database
1. cPanel → **MySQL Databases** → create a database and a user; grant ALL on that database.
2. Note the full names (`account_dbname`, `account_dbuser`) — cPanel prefixes them.
3. Do not import `schema.sql` by hand; the installer applies it.

## 5. Environment variables
Copy `.env.example` to `.env` in the project root and fill in:

| Key | Meaning |
| --- | --- |
| `APP_URL` | Canonical URL of the deployed site, including any subfolder |
| `APP_DEBUG` | `false` in production |
| `DB_HOST` | Usually `localhost` on cPanel |
| `DB_PORT` | `3306` on cPanel |
| `DB_NAME` | Full cPanel database name |
| `DB_USER` | Full cPanel database user |
| `DB_PASS` | Database password |
| `MAIL_ENABLED` | `true` once a mailbox exists |
| `MAIL_FROM` | Sender address on your domain |
| `INSTALL_KEY` | Long random string guarding the installer |
| `ADMIN_EMAIL` | `abodaniel435@gmail.com` unless the client specifies otherwise |
| `ADMIN_PASSWORD` | Set before install; store it in a password manager |
| `CRON_SECRET` | Long random string for the HTTP cron fallback |

Rules: `APP_DEBUG=false` in production, a long random `INSTALL_KEY`, and a real mailbox before `MAIL_ENABLED=true`. Never commit `.env`.

## 6. Install
1. Upload, then create `.env` as above.
2. Point the domain/subdomain at `public/`.
3. Run the installer **once**:
   - Browser: `https://yourdomain.com/install.php?key=YOUR_INSTALL_KEY`
   - or SSH: `php /home/USER/farmcoop/core/cli/install.php`
4. The installer writes `storage/installed.lock` and refuses to run twice. After success, delete or block `public/install.php`.
5. Sign in at `/login` with `ADMIN_EMAIL` and the password you set.

## 7. Cron / automation
Add a cPanel cron job every 15 minutes:

```
/usr/local/bin/php /home/USER/farmcoop/cron/run.php
```

or call `https://yourdomain.com/farmcoop/cron/run.php?key=CRON_SECRET`. Tasks expire stale listings, remind farmers about pending orders, close unconfirmed pickups and send a daily digest to the administrator.

## 8. Post-deploy checks
1. The marketplace lists seeded produce with prices and quantities.
2. Category, state and search filters narrow the list.
3. A buyer can open a listing, choose a quantity and place an order; stock decrements.
4. Two buyers cannot oversell the same quantity (the second checkout is refused).
5. The farmer sees the order and can accept, reject or mark it ready.
6. The buyer can follow the order and cancel while pending.
7. `/admin` rejects farmer and buyer accounts.
8. `email_outbox` contains the order notifications.

## 9. Tech report
| Area | Decision |
| --- | --- |
| Architecture | Front controller + explicit routes, controllers separated from views |
| Data access | PDO prepared statements only, transactions for stock/ledger writes |
| Auth | `password_hash`/`password_verify`, session regeneration, throttled logins, server-side role guards |
| CSRF | Token on every POST, verified before any state change |
| Output | Escaped through `e()`; no raw user input in HTML |
| Mail | Queued to `email_outbox` first, delivery attempted after commit |
| Audit | State changes written to `audit_log` with user, IP and timestamp |
| Performance | Indexed hot paths, no N+1 loops in list views, paginated tables |

### Known limits
- Settlement happens off-platform; the admin GMV figure counts completed orders only.
- Listing photos are external URLs in the demo seed; there is no upload pipeline.
- Mail is queued; delivery depends on the host.

## 10. Rollback and backups
Before upgrading, export the database from phpMyAdmin and zip the current files. To roll back, restore both. Export the database and keep the previous zip. Cancelling the installer lock is not enough — restore the database to roll back records.
