"""Focused checks for transactional booking email delivery."""
from datetime import date
from decimal import Decimal
from types import SimpleNamespace
import unittest
from unittest.mock import patch

from app import create_app
from app.services.notifications import send_booking_status_email


class BookingNotificationTests(unittest.TestCase):
    def setUp(self):
        self.app = create_app()
        self.app.config.update(
            MAIL_SERVER='smtp.test.local',
            MAIL_PORT=587,
            MAIL_USERNAME=None,
            MAIL_PASSWORD=None,
            MAIL_USE_TLS=False,
            MAIL_USE_SSL=False,
            MAIL_SUPPRESS_SEND=False,
            MAIL_DEFAULT_SENDER='Hotel Miramar SG <reservations@test.local>',
        )
        self.booking = SimpleNamespace(
            id=42,
            status='approved',
            user=SimpleNamespace(first_name='Ada', email='ada@example.test'),
            room=SimpleNamespace(name='Marina Deluxe'),
            check_in=date(2026, 11, 10),
            check_out=date(2026, 11, 13),
            num_guests=2,
            nights=3,
            total_price=Decimal('840.00'),
            admin_notes='We look forward to welcoming you.',
        )

    @patch('app.services.notifications.smtplib.SMTP')
    def test_approved_booking_sends_html_and_plain_text_email(self, smtp):
        client = smtp.return_value.__enter__.return_value
        with self.app.app_context():
            delivered = send_booking_status_email(self.booking)

        self.assertTrue(delivered)
        client.send_message.assert_called_once()
        email = client.send_message.call_args.args[0]
        self.assertEqual(email['To'], 'ada@example.test')
        self.assertIn('confirmed', email['Subject'].lower())
        self.assertIn('Hotel Miramar', email.as_string())

    def test_unconfigured_smtp_does_not_break_booking_flow(self):
        self.app.config['MAIL_SERVER'] = None
        with self.app.app_context():
            delivered = send_booking_status_email(self.booking)
        self.assertFalse(delivered)
