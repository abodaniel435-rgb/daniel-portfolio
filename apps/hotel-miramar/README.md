# Hotel Miramar SG — Room Booking System

## Zero-Stress Start (any laptop, VS Code)

You only need **Python 3.10+** installed. No manual venv, pip, or env setup.

1. Open this `hotel-miramar` folder in VS Code.
2. Click **Trust** if asked, then **Allow** the `Start Hotel Miramar` automatic task.
3. Wait ~1-2 min on first run (creates `.venv` + installs requirements once).
4. **Ctrl+Click** the link in the terminal:
   **http://127.0.0.1:5000**

That is it. The database + demo admin/rooms are auto-created.

Other ways to start:
- Press `F5` / Run > `Hotel Miramar: Run Website` (auto-opens browser).
- `Ctrl+Shift+P` > `Tasks: Run Task` > `Start Hotel Miramar` or `Open Hotel Miramar in Browser`.
- Manual terminal from project root: `python scripts/dev_server.py --open-browser` (Windows) or `python3 scripts/dev_server.py --open-browser` (Mac/Linux).

To stop: focus the server terminal and press `Ctrl+C`.

> Python noise (`.venv/`, `__pycache__/`, `*.pyc`, `.db`) is hidden in Explorer via `.vscode/settings.json` and ignored by git. Do not delete `.venv` — it is auto-rebuilt if missing. If you copied this folder from another laptop, just open it and let the task rebuild `.venv`.

## Booking emails

Guests receive a branded email when their booking request is received and again
whenever an administrator changes its status (including approval). `.env` is
auto-created from `.env.example` on first run with safe local defaults
(`MAIL_SUPPRESS_SEND=true` = no real email sent).

To send real mail, edit `.env` and enter the SMTP credentials supplied by your
email provider. Keep `MAIL_SUPPRESS_SEND=true` while testing locally; set it to
`false` only when a real SMTP server is configured.

For production, set `FLASK_ENV=production`, use a long unique `SECRET_KEY`,
and serve the application behind HTTPS. The application refuses the built-in
development secret in production.

### Admin Login
- **Email:** admin@hotelmiramarsg.com  
- **Password:** admin123  

### Stop the server
Press `Ctrl+C` in the terminal.

---

## Stack
- Python 3 + Flask
- SQLAlchemy (SQLite by default; MySQL optional via DATABASE_URL)
- Flask-Login, Flask-WTF
- HTML5 / CSS3 / JavaScript

## Project Structure
```
hotel-miramar/
├── app/
│   ├── routes/       # public, auth, customer, admin
│   ├── models/       # database models
│   ├── forms/        # WTForms
│   ├── templates/    # Jinja2 HTML
│   └── static/       # css, js, images
├── database/         # SQLite file (auto-created)
├── docs/             # Aptech documentation
├── config.py
├── run.py
└── requirements.txt
```
