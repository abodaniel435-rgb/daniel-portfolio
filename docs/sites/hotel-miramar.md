# Hotel Miramar SG — deployment guide & tech report

## 1. What you are deploying

A boutique hotel website and reservations back office for Marina Bay, Singapore.

**Guest side:** home page with a live availability search, room and suite listings with live nightly rates, room detail pages, a booking flow with date/guest validation and a confirmation page with a reference, gallery, neighbourhood guide, contact form, and guest reviews.

**Staff side:** a protected admin area with a dashboard (occupancy, revenue, arrivals), booking management (confirm, check-in, check-out, cancel), room and rate management, guest reviews moderation, and availability controls.

- **Local source:** `sites/hotel-miramar`
- **Stack:** Python 3.10+ · Flask · Flask-SQLAlchemy · Flask-Migrate · Flask-WTF · Jinja templates
- **Database:** MySQL / MariaDB in production (SQLite for local development)
- **Hosting:** cPanel with the *Setup Python App* (Passenger) feature. No Node, Redis, Docker or background workers required.

## 2. Server requirements

| Requirement | Value |
| --- | --- |
| Python | 3.10 – 3.12 recommended (3.11 tested on the host) |
| cPanel feature | **Setup Python App** (Phusion Passenger) |
| Database | MySQL 5.7+ / MariaDB 10.3+ |
| Extensions | `pymysql` (pure Python, installed from `requirements.txt`) |
| Writable paths | `database/` (SQLite only), `app/static/uploads/`, `instance/` |

Passenger hosts the app through `passenger_wsgi.py`, which calls `create_app('production')`.

## 3. File layout

```
run.py                  dev entry point + seeding CLI
passenger_wsgi.py       Passenger entry point (production)
config.py               Development / Production config classes
requirements.txt
app/
  __init__.py           create_app factory
  models/               User, Room, Booking, Feedback, Amenity…
  routes/               public, auth, booking, admin blueprints
  forms/                WTForms with server-side validation
  services/             availability, pricing, mail
  templates/            Jinja layouts and pages
  static/               css, js, images, fonts, uploads/
database/               local SQLite file (not used in production)
migrations/             Flask-Migrate versions
docs/                   synopsis, CRS, ERD, cPanel notes
.env                    secrets (never upload)
```

## 4. Database on cPanel

1. cPanel → **MySQL Databases** → create a database, e.g. `hotel_miramar`, and a user with a strong password. Add the user to the database with **ALL PRIVILEGES**.
2. Note the prefixed names (`cpuser_miramar`, `cpuser_hotel`).
3. Set `DATABASE_URL` (see below) so SQLAlchemy uses MySQL through the pure-Python `PyMySQL` driver:

   ```
   DATABASE_URL=mysql+pymysql://cpuser_hotel:DBPASSWORD@localhost/cpuser_miramar
   ```

4. Create the schema with migrations, not by hand:

   ```bash
   source /home/CPUSER/virtualenv/hotel-miramar/3.11/bin/activate
   cd /home/CPUSER/hotel-miramar
   FLASK_APP=run.py flask db upgrade
   ```

5. Seed the five room categories and the initial administrator:

   ```bash
   FLASK_APP=run.py flask init-db
   ```

   `init-db` is safe to re-run: it only inserts when the matching tables are empty.

**Demo data warning:** `DevelopmentConfig.SEED_DEMO_DATA` is `true`; production defaults to `false`. Seeding is explicit through `flask init-db`.

## 5. Environment variables

Copy `.env.example` to `.env` **outside** the web root, or paste the same keys into cPanel → Setup Python App → *Environment variables*. `.env` is loaded with `override=True`, so file values beat stale shell exports.

| Key | Example | Notes |
| --- | --- | --- |
| `FLASK_ENV` | `production` | Must be `production` on the host |
| `SECRET_KEY` | 64 random bytes | Never reuse the development key |
| `DATABASE_URL` | `mysql+pymysql://…` | Omit to fall back to SQLite (not for production) |
| `PUBLIC_BASE_URL` | `https://hotelmiramarsg.com` | Used in emails and absolute links |
| `MAIL_SERVER` | `smtp.gmail.com` | Or the host's SMTP |
| `MAIL_PORT` | `587` | |
| `MAIL_USERNAME` | mailbox address | |
| `MAIL_PASSWORD` | Gmail **app password** | Never the account password |
| `MAIL_USE_TLS` | `true` | |
| `MAIL_DEFAULT_SENDER` | `Hotel Miramar SG <…>` | |
| `MAIL_SUPPRESS_SEND` | `false` | `true` only while testing |
| `ADMIN_EMAIL` | `abodaniel435@gmail.com` | Initial administrator |
| `ADMIN_PASSWORD` | long random string | Used only when the first seed runs |
| `ADMIN_FIRST_NAME` / `ADMIN_LAST_NAME` | `Abo` / `Daniel` | Optional |
| `HOTEL_PORT` | `5001` | Local only |

Rules: `FLASK_ENV=production`, a real `SECRET_KEY`, a Gmail **app password** (16 characters, 2-Step Verification on), and `MAIL_SUPPRESS_SEND=false` only once the mailbox is confirmed. Never commit `.env`.

## 6. cPanel deployment — step by step

1. **Upload.** Zip the project root (without `.env`, `__pycache__`, `database/*.db`) and upload it to `/home/CPUSER/hotel-miramar`. Extract there.
2. **Create the Python app.** cPanel → **Setup Python App** → *Create application*:
   - Python version: 3.11 (or the newest offered)
   - Application root: `hotel-miramar`
   - Application URL: the domain or subdomain
   - Application startup file: `passenger_wsgi.py`
   - Application Entry point: `application`
3. **Install dependencies.** In the app's virtualenv:

   ```bash
   source /home/CPUSER/virtualenv/hotel-miramar/3.11/bin/activate
   pip install --upgrade pip
   pip install -r requirements.txt
   ```

4. **Environment.** Paste the `.env` keys into the app's environment variables, or upload `.env` into `/home/CPUSER/hotel-miramar`.
5. **Database.** Create it in cPanel, then run `flask db upgrade` and `flask init-db` (section 4).
6. **Static files.** Passenger serves `/static/` through Flask by default. For speed, add an alias in the app root or leave it — the assets are small (fonts are local WOFF2).
7. **Restart.** cPanel → Setup Python App → **Restart**. Then open the domain.
8. **HTTPS.** Enable AutoSSL / Let's Encrypt. `ProductionConfig` sets `SESSION_COOKIE_SECURE=True`, so the site must be served over HTTPS or logins will not stick.

### Single-domain vs subdomain

The app is self-contained (templates and static files ship with Flask), so it can run at the domain root or on a subdomain such as `stay.example.com`. No Apache rewrite file is needed; Passenger forwards all requests to `passenger_wsgi.py`.

## 7. Local development

```bash
python -m venv .venv
.venv/Scripts/activate          # Windows
pip install -r requirements.txt
copy .env.example .env          # fill SECRET_KEY and ADMIN_PASSWORD
python run.py                   # http://127.0.0.1:5000
```

`config.py` binds `127.0.0.1:5001` only if `HOTEL_PORT` is set; otherwise run.py uses port 5000.

## 8. Post-deployment checks

| # | Check | Expected |
| --- | --- | --- |
| 1 | Open `/` | Hero, availability bar, featured rooms render with images |
| 2 | Search 2 nights, 2 guests | Room list filters and shows live nightly rates |
| 3 | Open a room, book | Validation blocks past dates and empty guest details |
| 4 | Complete a booking | Confirmation page shows a reference; row exists in `bookings` |
| 5 | Sign in as `ADMIN_EMAIL` | Lands on `/admin/dashboard`; other accounts cannot reach `/admin` |
| 6 | Confirm / check-in the booking | Status changes and is visible on the guest confirmation page |
| 7 | Submit a review | Appears as pending; approving it publishes it on the home page |
| 8 | Trigger a password reset | Email arrives (or is suppressed), link resets the password |
| 9 | Log out, hit `/admin` directly | Redirected to sign-in, not served admin HTML |
| 10 | Resize to 390 px | Nav collapses to a menu, tables scroll, cards stack |

## 9. Tech report

**Problem.** Independent hotels lose direct bookings to OTAs that charge 15–25 % commission and hide the guest relationship. A fast, trustworthy direct-booking site with a small back office recovers margin and gives staff one place to manage inventory.

**Approach.** Server-rendered Flask pages keep the stack simple enough for shared cPanel hosting while still giving real reservations logic: availability checks against overlapping bookings, nightly pricing, reference generation, and email confirmations. Admin routes are protected by `@admin_required`, every mutating form carries CSRF, and passwords are stored with Werkzeug `scrypt`.

**Key features**
- Live availability search (dates, guests, room type) with overlap detection
- Five room categories with galleries, amenities and per-night pricing
- Booking flow with confirmation reference and email receipt
- Staff dashboard: occupancy, revenue, arrivals, recent bookings
- Booking lifecycle: pending → confirmed → checked-in → checked-out / cancelled
- Room and rate management, availability toggles
- Guest review submission with moderation
- Password reset by signed, time-limited token
- Responsive layout, local fonts, lazy-loaded imagery

**Security**
- `scrypt` password hashing; no plaintext or MD5 anywhere
- CSRF on every form (Flask-WTF)
- Session cookies `HttpOnly`, `SameSite=Lax`, `Secure` in production
- Role check on every `/admin/*` route, not just the navigation
- Secrets only from the environment; `.env` excluded from the deployment zip
- Server-side validation for dates, occupancy, email and numeric fields

**cPanel fit.** Pure-Python dependencies (`PyMySQL`, no compiler), one Passenger entry point, migrations instead of manual SQL, and no background workers. Email uses SMTP, so no external queue is needed.

**Known limits.** Payment capture is intentionally not wired to a live gateway (the booking flow is pay-on-arrival with a reference); adding Stripe/Paystack later only needs a service and a webhook route. Large image galleries would benefit from a CDN if traffic grows.
