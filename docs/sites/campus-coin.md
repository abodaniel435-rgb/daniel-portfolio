# Campus Coin — deployment guide & tech report

## 1. What you are deploying

A student money-management web app for Nigerian campuses. Students log allowance, part-time income and daily spending, set category budgets and savings jars, split bills with roommates, and get a monthly health score plus rule-based tips. An admin studio shows campus-wide analytics and announcements.

- **Local source:** `sites/campus-coin`
- **Frontend:** React 19 + Vite (built to static files in `frontend/dist`)
- **Backend:** Django 5.2 LTS + Django REST Framework + SimpleJWT
- **Database:** MySQL / MariaDB (SQLite fallback for local demos only)
- **Hosting:** cPanel with *Setup Python App* (Passenger) for the API and plain static hosting for the built SPA

## 2. Server requirements

| Requirement | Value |
| --- | --- |
| Python | 3.10 – 3.12 (3.11 recommended on the host) |
| cPanel feature | **Setup Python App** (Passenger) |
| Database | MySQL 5.7+ / MariaDB 10.3+ |
| Python packages | see `requirements.txt` (Django, DRF, SimpleJWT, django-cors-headers, PyMySQL, python-dotenv) |
| Local build tools | Node 18+ **only** for the one-off `npm run build` (not needed on the host) |

`PyMySQL` is pure Python, so nothing compiles on shared hosting. `config/__init__.py` installs the `MySQLdb` shim only when the MySQL backend is selected.

## 3. File layout

```
manage.py
passenger_wsgi.py          Passenger entry point
requirements.txt
config/                    settings, urls, wsgi
accounts/                  custom user, JWT auth, password reset
core/                      dashboard, budgets, goals, splits, reports, admin analytics
campus_coin.sql            full schema + demo data
campus_coin_pre_upgrade.sql legacy baseline
frontend/                  React source (Vite)
frontend/dist/             production build — this is what you upload as static files
.env                       secrets (never upload)
```

## 4. How the two halves are deployed

The API and the React build are separate artefacts. Two supported layouts:

### A. Subdomain (recommended, simplest)

1. Create `api.example.com` → **Setup Python App**, application root `campus-coin`, startup file `passenger_wsgi.py`.
2. Point the main domain (`example.com`) at the folder that holds `frontend/dist` (upload its contents into `public_html`).
3. Set `DJANGO_ALLOWED_HOSTS=api.example.com`, `FRONTEND_URL=https://example.com`, and `CORS_ALLOWED_ORIGINS=https://example.com`.
4. In the SPA, the API base must point at the subdomain. Build with the base URL:

   ```bash
   cd frontend
   VITE_API_BASE=https://api.example.com npm run build
   ```

### B. Single domain

1. Enable Passenger on the main domain with the app root `campus-coin` and startup file `passenger_wsgi.py`.
2. Upload `frontend/dist/*` into `public_html`.
3. Add this `public_html/.htaccess` so real files (JS, CSS, images, `index.html`) are served by Apache and everything else goes to Passenger:

   ```apache
   RewriteEngine On
   RewriteCond %{REQUEST_FILENAME} -f [OR]
   RewriteCond %{REQUEST_FILENAME} -d
   RewriteRule ^ - [L]
   RewriteRule ^(api|admin)/(.*)$ - [L]
   RewriteRule ^ index.html [L]
   ```

4. Set `DJANGO_ALLOWED_HOSTS=example.com`, `DJANGO_CSRF_TRUSTED_ORIGINS=https://example.com`, `CORS_ALLOW_ALL_ORIGINS=false`, `CORS_ALLOWED_ORIGINS=https://example.com`.

The SPA calls `/api/...` on its own origin by default, which is why layout B needs no build-time URL.

## 5. Database

1. cPanel → **MySQL Databases**: create `campus_coin` and a user with ALL PRIVILEGES.
2. Options:
   - **Fresh install:** run migrations, then seed demo data.
   - **Existing data:** import `campus_coin.sql` through phpMyAdmin (it recreates tables and demo rows).
3. Environment:

   ```
   MYSQL_DATABASE=cpuser_campuscoin
   MYSQL_USER=cpuser_campus
   MYSQL_PASSWORD=strong-password
   MYSQL_HOST=localhost
   MYSQL_PORT=3306
   USE_SQLITE=false
   ```

4. Migrate:

   ```bash
   source /home/CPUSER/virtualenv/campus-coin/3.11/bin/activate
   cd /home/CPUSER/campus-coin
   python manage.py migrate
   ```

**Never** run `migrate` against a database that was imported from `campus_coin.sql` without checking `django_migrations` first — the dump already contains the applied migration rows.

## 6. Environment variables

| Key | Example | Notes |
| --- | --- | --- |
| `DJANGO_SECRET_KEY` | 64 random bytes | Required; the fallback is development-only |
| `DJANGO_DEBUG` | `false` | Never `true` in production |
| `DJANGO_ALLOWED_HOSTS` | `api.example.com,example.com` | Comma separated |
| `DJANGO_CSRF_TRUSTED_ORIGINS` | `https://example.com` | Required behind HTTPS |
| `MYSQL_*` | see above | cPanel-prefixed names |
| `USE_SQLITE` | `false` | MySQL is the required database |
| `FRONTEND_URL` | `https://example.com` | Used in password-reset links |
| `CORS_ALLOW_ALL_ORIGINS` | `false` in production | Only `true` for local dev |
| `CORS_ALLOWED_ORIGINS` | `https://example.com` | |
| `ADMIN_EMAIL` | `abodaniel435@gmail.com` | Initial administrator |
| `ADMIN_PASSWORD` | long random string | Read by the admin bootstrap command |

## 7. Create the administrator (once)

The admin studio and `/api/admin/analytics/` require a staff account. Passwords are hashed by Django (`pbkdf2_sha256`); nothing is created with a default password.

```bash
python manage.py shell -c "
import os
from django.contrib.auth import get_user_model
U = get_user_model()
email = os.environ['ADMIN_EMAIL']
u = U.objects.filter(email__iexact=email).order_by('id').first()
if not u:
    u = U(username=email.split('@')[0], email=email)
    u.set_password(os.environ['ADMIN_PASSWORD'])
u.is_staff = True; u.is_superuser = True; u.is_admin = True
u.save()
print('admin ready:', u.username, u.email)
"
```

Every other account registers through the public form and stays a normal student — admin rights are never granted automatically.

## 8. Static build

```bash
cd frontend
npm ci
npm run build        # writes frontend/dist
```

Upload `frontend/dist/index.html` and `frontend/dist/assets/*` to the web root. Hashed filenames make the assets safe to cache for a year; `index.html` should be `no-cache`.

## 9. Post-deployment checks

| # | Check | Expected |
| --- | --- | --- |
| 1 | Open the site | Landing page renders; sign-in and registration work |
| 2 | Register a new student | Account is **not** staff; dashboard opens with zero state |
| 3 | Sign in as the admin | Admin studio is in the sidebar; `/api/admin/analytics/` returns 200 |
| 4 | Sign in as a student, call the admin API | `403` — the route is permission-protected, not just hidden |
| 5 | Log a transaction | Balance, ledger, budget bars and health score update |
| 6 | Set a budget, overspend it | Budget bar flags the overspend in the guardrails card |
| 7 | Create a savings goal | Goal jar shows progress and deadline |
| 8 | Create a split and settle a member | Member marked settled; split total updates |
| 9 | Request a password reset | Email (or console backend) delivers a working link |
| 10 | Resize to 390 px | Bottom tab bar, single-column cards, scrollable tables |

## 10. Tech report

**Problem.** Students receive a lump allowance and have no visibility over where it goes. Existing finance apps assume salaries, bank feeds and subscriptions; they do not model term dates, shared hostel bills or irregular part-time income.

**Approach.** A JWT API on DRF keeps the rules (budget maths, health score, split settlement) on the server, so the React client stays a presentation layer. The health score is transparent: savings rate, budget adherence, and logging consistency, each with a visible weight. Tips are rule-based and reproducible rather than an opaque "AI" claim.

**Key features**
- Income and expense ledger with categories, recurring flags and CSV import
- Monthly and category budgets with live overspend warnings
- Savings jars with targets, deadlines and progress
- Roommate split and settle flow with per-member settlement
- Spend heatmap (12 months), monthly report, forecast and what-if simulator
- Health score (0–100) with a breakdown, plus rule-based smart tips
- Admin studio: campus-wide spend analytics, announcements
- Password reset by signed token; email-verified account model

**Security**
- PBKDF2-SHA256 password hashing (Django default), no plaintext
- JWT access/refresh with short access lifetime; refresh rotation configured in `SIMPLE_JWT`
- Permission classes on every admin endpoint (`IsAdminUser`), UI hiding is secondary
- Duplicate-email registrations rejected case-insensitively
- Secrets and DB credentials only from environment variables
- CORS locked to the deployed origin in production; CSRF trusted origins set for HTTPS
- Server-side validation of amounts, dates and ownership on every write

**cPanel fit.** Pure-Python MySQL driver, Passenger entry point, migrations instead of ad-hoc SQL, and a static SPA that needs no Node on the host. Scheduled tip generation can run from cPanel cron calling a management command.

**Known limits.** Real bank/statement sync and SMS alerts are out of scope; the CSV import covers statement entry. The demo dataset uses fictional students so no real financial data is exposed.
