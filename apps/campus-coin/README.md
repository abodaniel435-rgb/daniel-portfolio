# Campus Coin

Campus Coin is a student-first personal finance application. It uses **React** for the web interface, **Django REST Framework** for the API, and **MySQL** for persistent data. PHP is supported in the editor for coursework utilities or a later Laravel migration, but it is intentionally not used as a second backend alongside Django.

## Requirements

- Python 3.10+ and Node.js 18+ on PATH
- MySQL 8.0+ (or MariaDB 11.4+). XAMPP's bundled MariaDB 10.4 is too old for Django 6.1 and will be rejected.

## Quick start (recommended)

1. Start your MySQL server.
2. Import `campus_coin.sql` — it recreates the `campus_coin` database with full schema and demo data. Either use phpMyAdmin's Import tab, or run `mysql -u root -p < campus_coin.sql`. (If a `campus_coin` database already exists on your server, drop or rename it first.)
3. Double-click `start.bat`. On first run it creates the Python virtual environment and installs frontend packages, then opens the API and the website in two console windows.
4. Open http://localhost:5173 and log in with the credentials listed in the project report.

If your MySQL does not listen on `127.0.0.1:3307` with user `campus_coin_app`, edit the `MYSQL_*` lines in `.env` (the script creates it from `.env.example` if missing) and restart the API window.

## Manual start

1. `python -m venv venv`, then `venv\Scripts\python -m pip install -r requirements.txt`
2. In `frontend`: `npm install`
3. Repository root: `venv\Scripts\python manage.py runserver` — API at http://127.0.0.1:8000/api/
4. In `frontend`: `npm run dev` — website at the Vite local address, http://localhost:5173 by default

The API is served on `http://127.0.0.1:8000/api/`; the React client runs on Vite's displayed local address.

If MySQL is temporarily unavailable, `USE_SQLITE=true` in `.env` remains as an emergency local-demo fallback only; MySQL is the project database required by the SRS.

## Implemented foundation

- JWT registration, login, profile retrieval/update, and password-reset endpoints.
- Categories, transactions (including safe soft deletion), budgets, CSV import, rule-based insights, reports, announcements, and a monthly dashboard API.
- MySQL configuration through environment variables and a responsive React dashboard starting point.

## SRS delivery checklist

Still to build: PDF report export, email/production configuration, richer chart visualisations, budget-alert notifications, duplicate-transaction detection, automated tests, sitemap, and the required project documentation/video.
