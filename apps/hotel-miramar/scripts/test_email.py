"""Quick SMTP check for Hotel Miramar booking emails.

Usage:
  .venv/Scripts/python.exe scripts/test_email.py --to you@example.com
  .venv/Scripts/python.exe scripts/test_email.py --to you@example.com --send-real

Without --send-real it only shows current config and renders templates.
With --send-real it actually sends via SMTP using .env settings.
"""
import argparse
import sys
from datetime import date
from decimal import Decimal
from types import SimpleNamespace
from pathlib import Path

PROJECT_ROOT = Path(__file__).resolve().parents[1]
sys.path.insert(0, str(PROJECT_ROOT))

from app import create_app
from app.services.notifications import send_booking_status_email


def main():
    parser = argparse.ArgumentParser()
    parser.add_argument('--to', required=True, help='Recipient email for test')
    parser.add_argument('--send-real', action='store_true')
    args = parser.parse_args()

    app = create_app()
    print(f"MAIL_SERVER={app.config['MAIL_SERVER']}")
    print(f"MAIL_PORT={app.config['MAIL_PORT']}")
    print(f"MAIL_USERNAME={app.config['MAIL_USERNAME']}")
    print(f"MAIL_USE_TLS={app.config['MAIL_USE_TLS']}")
    print(f"MAIL_USE_SSL={app.config['MAIL_USE_SSL']}")
    print(f"MAIL_SUPPRESS_SEND={app.config['MAIL_SUPPRESS_SEND']}")
    print(f"MAIL_DEFAULT_SENDER={app.config['MAIL_DEFAULT_SENDER']}")

    booking = SimpleNamespace(
        id=999,
        status='approved',
        user=SimpleNamespace(first_name='Daniel', email=args.to),
        room=SimpleNamespace(name='Marina Deluxe'),
        check_in=date(2026, 11, 10),
        check_out=date(2026, 11, 13),
        num_guests=2,
        nights=3,
        total_price=Decimal('840.00'),
        admin_notes='We look forward to welcoming you.',
    )
    if not args.send_real:
        print('')
        print('Dry run only (no email sent). Add --send-real to actually send.')
        print('If MAIL_SUPPRESS_SEND=true, sending is blocked by config — set it to false in .env.')
        return 0

    with app.app_context():
        ok = send_booking_status_email(booking)
    if ok:
        print(f'TEST EMAIL SENT to {args.to} — check inbox/spam.')
        return 0
    print('TEST EMAIL NOT SENT — check logs above. Common causes:')
    print(' - MAIL_SUPPRESS_SEND=true in .env')
    print(' - Wrong MAIL_PASSWORD (use Gmail App Password, not normal password)')
    print(' - MAIL_SERVER/PORT wrong for Gmail: smtp.gmail.com:587 + TLS')
    return 1


if __name__ == '__main__':
    raise SystemExit(main())
