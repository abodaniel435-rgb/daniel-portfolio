"""Transactional email notifications for Hotel Miramar SG.

The module uses Python's standard SMTP client so the application does not need
another mail framework. Delivery errors are logged, never allowed to undo a
confirmed database transaction.
"""
from email.message import EmailMessage
import smtplib

from flask import current_app, render_template


STATUS_COPY = {
    'pending': ('Booking request received', 'Your request is awaiting review by our reservations team.'),
    'approved': ('Your booking is confirmed', 'We are delighted to confirm your stay at Hotel Miramar SG.'),
    'disapproved': ('Update on your booking request', 'Unfortunately, we are unable to confirm this request.'),
    'cancelled': ('Your booking has been cancelled', 'Your booking is now cancelled and the room has been released.'),
}


def _send_message(message, event_name):
    """Hand a fully composed message to SMTP without affecting application data."""
    if current_app.config['MAIL_SUPPRESS_SEND']:
        current_app.logger.info('Email suppressed: %s -> %s', event_name, message['To'])
        return False

    server_name = current_app.config['MAIL_SERVER']
    if not server_name:
        current_app.logger.warning('%s email not sent: MAIL_SERVER is not configured.', event_name)
        return False

    try:
        smtp_class = smtplib.SMTP_SSL if current_app.config['MAIL_USE_SSL'] else smtplib.SMTP
        with smtp_class(server_name, current_app.config['MAIL_PORT'], timeout=current_app.config['MAIL_TIMEOUT']) as client:
            if current_app.config['MAIL_USE_TLS'] and not current_app.config['MAIL_USE_SSL']:
                client.starttls()
            if current_app.config['MAIL_USERNAME']:
                client.login(current_app.config['MAIL_USERNAME'], current_app.config['MAIL_PASSWORD'] or '')
            client.send_message(message)
        current_app.logger.info('%s email sent -> %s', event_name, message['To'])
        return True
    except (OSError, smtplib.SMTPException):
        current_app.logger.exception('Could not send %s email.', event_name)
        return False


def send_booking_status_email(booking):
    """Send a polished receipt/status update for a persisted booking.

    Returns True on successful SMTP handoff. Missing SMTP setup and delivery
    failures are logged and return False, so booking operations remain reliable.
    """
    status_title, status_message = STATUS_COPY.get(
        booking.status, ('Booking status update', 'There has been an update to your booking.')
    )
    subject = f'{status_title} — {current_app.config["HOTEL_NAME"]} (#{booking.id})'
    text_body = render_template(
        'emails/booking_status.txt', booking=booking, status_title=status_title,
        status_message=status_message
    )
    html_body = render_template(
        'emails/booking_status.html', booking=booking, status_title=status_title,
        status_message=status_message
    )

    message = EmailMessage()
    message['Subject'] = subject
    message['From'] = current_app.config['MAIL_DEFAULT_SENDER']
    message['To'] = booking.user.email
    message.set_content(text_body)
    message.add_alternative(html_body, subtype='html')

    return _send_message(message, f'booking #{booking.id}')


def send_contact_admin_notification(contact_msg):
    """Notify the hotel inbox instantly when a Contact Us message arrives.

    Sends to MAIL_USERNAME (your real Gmail) so you actually see it.
    Sets Reply-To to the guest so you can hit Reply directly.
    """
    admin_inbox = current_app.config.get('MAIL_USERNAME') or current_app.config['HOTEL_EMAIL']
    subject = f'New contact message: {contact_msg.subject or "(no subject)"} — from {contact_msg.name}'
    text_body = render_template('emails/contact_admin.txt', msg=contact_msg)
    html_body = render_template('emails/contact_admin.html', msg=contact_msg)

    message = EmailMessage()
    message['Subject'] = subject
    message['From'] = current_app.config['MAIL_DEFAULT_SENDER']
    message['To'] = admin_inbox
    # Let admin hit Reply and reach the guest directly.
    message['Reply-To'] = contact_msg.email
    message.set_content(text_body)
    message.add_alternative(html_body, subtype='html')

    return _send_message(message, f'contact admin #{contact_msg.id}')


def send_contact_guest_confirmation(contact_msg):
    """Confirm receipt to the guest who just wrote via Contact Us."""
    subject = f'We received your message — {current_app.config["HOTEL_NAME"]}'
    text_body = render_template('emails/contact_guest.txt', msg=contact_msg)
    html_body = render_template('emails/contact_guest.html', msg=contact_msg)

    message = EmailMessage()
    message['Subject'] = subject
    message['From'] = current_app.config['MAIL_DEFAULT_SENDER']
    message['To'] = contact_msg.email
    message.set_content(text_body)
    message.add_alternative(html_body, subtype='html')

    return _send_message(message, f'contact guest #{contact_msg.id}')


def send_password_reset_email(user, reset_url):
    """Send a one-hour password-reset link without exposing it in the browser."""
    message = EmailMessage()
    message['Subject'] = f'Password reset — {current_app.config["HOTEL_NAME"]}'
    message['From'] = current_app.config['MAIL_DEFAULT_SENDER']
    message['To'] = user.email
    text_body = render_template('emails/password_reset.txt', user=user, reset_url=reset_url)
    html_body = render_template('emails/password_reset.html', user=user, reset_url=reset_url)
    message.set_content(text_body)
    message.add_alternative(html_body, subtype='html')
    return _send_message(message, f'password reset for user #{user.id}')
