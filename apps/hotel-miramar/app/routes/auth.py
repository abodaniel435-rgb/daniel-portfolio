"""
Authentication routes: register, login, logout, password recovery.
"""
import secrets
from datetime import datetime, timedelta
from urllib.parse import urljoin, urlparse
from flask import Blueprint, render_template, redirect, url_for, flash, request, current_app
from flask_login import login_user, logout_user, login_required, current_user
from app import db
from app.models.models import User, PasswordResetToken
from app.forms.forms import (
    RegistrationForm, LoginForm, PasswordRecoveryForm, ResetPasswordForm
)
from app.services.notifications import send_password_reset_email

auth_bp = Blueprint('auth', __name__)


def is_safe_redirect_url(target):
    """Only allow post-login redirects that remain on this site."""
    if not target:
        return False
    host_url = urlparse(request.host_url)
    candidate = urlparse(urljoin(request.host_url, target))
    return candidate.scheme in {'http', 'https'} and candidate.netloc == host_url.netloc


@auth_bp.route('/register', methods=['GET', 'POST'])
def register():
    if current_user.is_authenticated:
        return redirect(url_for('customer.dashboard'))
    form = RegistrationForm()
    if form.validate_on_submit():
        user = User(
            email=form.email.data.lower().strip(),
            first_name=form.first_name.data.strip(),
            last_name=form.last_name.data.strip(),
            phone=form.phone.data
        )
        user.set_password(form.password.data)
        db.session.add(user)
        db.session.commit()
        flash('Registration successful! Please log in.', 'success')
        return redirect(url_for('auth.login'))
    return render_template('auth/register.html', form=form)


@auth_bp.route('/login', methods=['GET', 'POST'])
def login():
    if current_user.is_authenticated:
        if current_user.is_admin:
            return redirect(url_for('admin.dashboard'))
        return redirect(url_for('customer.dashboard'))
    form = LoginForm()
    if form.validate_on_submit():
        user = User.query.filter_by(email=form.email.data.lower().strip()).first()
        if user and user.check_password(form.password.data) and user.is_active:
            login_user(user, remember=form.remember.data)
            requested_next = request.args.get('next')
            next_page = requested_next if is_safe_redirect_url(requested_next) else None
            if user.is_admin:
                return redirect(next_page or url_for('admin.dashboard'))
            return redirect(next_page or url_for('customer.dashboard'))
        flash('Invalid email or password.', 'danger')
    return render_template('auth/login.html', form=form)


@auth_bp.route('/logout')
@login_required
def logout():
    logout_user()
    flash('You have been logged out.', 'info')
    return redirect(url_for('public.index'))


@auth_bp.route('/password-recovery', methods=['GET', 'POST'])
def password_recovery():
    if current_user.is_authenticated:
        return redirect(url_for('customer.dashboard'))
    form = PasswordRecoveryForm()
    if form.validate_on_submit():
        user = User.query.filter_by(email=form.email.data.lower().strip()).first()
        if user:
            # Invalidate old tokens
            PasswordResetToken.query.filter_by(user_id=user.id, used=False).update({'used': True})
            token_str = secrets.token_urlsafe(32)
            token = PasswordResetToken(
                user_id=user.id,
                token=token_str,
                expires_at=datetime.utcnow() + timedelta(hours=1)
            )
            db.session.add(token)
            db.session.commit()
            reset_path = url_for('auth.reset_password', token=token_str)
            base_url = current_app.config['PUBLIC_BASE_URL']
            reset_url = f'{base_url}{reset_path}' if base_url else url_for(
                'auth.reset_password', token=token_str, _external=True
            )
            send_password_reset_email(user, reset_url)
        # Do not reveal whether an account exists or whether SMTP is configured.
        flash('If that email is registered, a reset link has been sent.', 'info')
        return redirect(url_for('auth.login'))
    return render_template('auth/password_recovery.html', form=form)


@auth_bp.route('/reset-password/<token>', methods=['GET', 'POST'])
def reset_password(token):
    if current_user.is_authenticated:
        return redirect(url_for('customer.dashboard'))
    token_obj = PasswordResetToken.query.filter_by(token=token).first()
    if not token_obj or not token_obj.is_valid():
        flash('Invalid or expired reset link.', 'danger')
        return redirect(url_for('auth.password_recovery'))
    form = ResetPasswordForm()
    if form.validate_on_submit():
        user = token_obj.user
        user.set_password(form.password.data)
        token_obj.used = True
        db.session.commit()
        flash('Password updated successfully. Please log in.', 'success')
        return redirect(url_for('auth.login'))
    return render_template('auth/reset_password.html', form=form)
