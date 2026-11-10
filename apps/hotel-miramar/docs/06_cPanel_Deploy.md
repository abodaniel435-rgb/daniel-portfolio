# cPanel Deployment — Hotel Miramar SG (Flask)

This guide maps your local folder `hotel-miramar/` to a cPanel directory
(usually `public_html/` or a subfolder / Python App root).

## 1. What to upload (File Manager)

Upload everything EXCEPT:
- `.venv/` (rebuilt on server)
- `__pycache__/` + `*.pyc`
- `database/hotel_miramar.db` (dev SQLite — do NOT overwrite production DB)
- `.env` (contains secrets — recreate on server, never upload as-is)

Keep:
- `app/` (routes, models, templates, static)
- `migrations/` (empty now, keep for Flask-Migrate)
- `config.py`, `run.py`, `passenger_wsgi.py` (entry point), `requirements.txt`

`passenger_wsgi.py` is already added for cPanel “Setup Python App”.
It exposes `application = create_app('production')`.

## 2. cPanel Setup Python App steps

1. cPanel > Setup Python App > Create Application:
   - Python version: 3.10+ (3.11 recommended)
   - Application root: e.g. `hotel-miramar` (or `public_html` if root domain)
   - Application URL: your domain / subdomain
   - Application startup file: `passenger_wsgi.py`
   - Application Entry point: `application`
2. In the app’s virtualenv, install deps (Terminal or pip install):
   `pip install -r requirements.txt`
3. Add environment variables in Setup Python App UI:
   - `FLASK_ENV=production`
   - `SECRET_KEY=<long-random-64-chars>` (generate new, never reuse dev key)
   - `DATABASE_URL` — if using MySQL on cPanel:
     `mysql+pymysql://USER:PASS@localhost/DBNAME`
     (requires adding `pymysql` to requirements.txt)
     Otherwise SQLite `database/hotel_miramar.db` is auto-created.
   - `PUBLIC_BASE_URL=https://your-domain.com`
   - SMTP vars from `.env.example` if you want real emails:
     `MAIL_SERVER`, `MAIL_PORT`, `MAIL_USERNAME`, `MAIL_PASSWORD`,
     `MAIL_USE_TLS`, `MAIL_DEFAULT_SENDER`, `MAIL_SUPPRESS_SEND=false`
4. Restart the Python App (Restart button).
5. Visit `https://your-domain.com/` — first run auto-creates tables + demo data
   unless `SEED_DEMO_DATA=false`.

## 3. Static files / uploads

- `app/static/` is served by Flask. For performance you can map
  `public_html/static` but default works.
- Ensure `app/static/uploads/` and `app/static/images/rooms|`slider exist
  and are writable (755 dirs, 644 files).
- Video `app/static/ads/hotel-miramar-ad-final.mp4` is 3.3 MB — fine to upload.

## 4. Security checklist before giving access

- NEVER post cPanel password / API token in git or screenshots.
- Rotate the Gmail App Password currently in local `.env`
  (`MAIL_PASSWORD=egef...` was visible) — it should be revoked in
  Google Account > Security > App passwords and replaced.
- Set `MAIL_SUPPRESS_SEND=true` locally, `false` only on server with real SMTP.
- Set `SECRET_KEY` unique in production (app refuses dev key in prod).

## 5. How I can access your cPanel directory

I run inside your VS Code workspace — I have NO direct link to cPanel
File Manager until you provide one of:

A) cPanel login: `https://yourdomain.com:2083` + username + password
   (+ target folder, e.g. `/home/user/public_html/hotel-miramar`)
B) FTP/SFTP: host + port + username + password + path
C) cPanel API token (WHM/cPanel > Manage API Tokens) + host + username
D) You upload a ZIP I prepare (`cpanel-upload.zip`)

Once you paste (A/B/C) I can list/upload/edit via a Python script
(ftplib / requests to UAPI Fileman) and show you results.
