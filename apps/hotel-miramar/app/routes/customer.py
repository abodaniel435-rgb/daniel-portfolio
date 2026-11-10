"""
Customer portal routes: dashboard, bookings, profile.
"""
from datetime import date, datetime
from decimal import Decimal
from flask import Blueprint, render_template, redirect, url_for, flash, request, abort
from flask_login import login_required, current_user
from app import db
from app.models.models import Room, Booking, User
from app.forms.forms import BookingForm, ProfileForm, ChangePasswordForm, RoomSearchForm
from app.services.notifications import send_booking_status_email
from sqlalchemy import select

customer_bp = Blueprint('customer', __name__)


def customer_required(f):
    """Ensure user is logged in and not treating admin routes specially."""
    from functools import wraps
    @wraps(f)
    def decorated(*args, **kwargs):
        if not current_user.is_authenticated:
            return redirect(url_for('auth.login'))
        return f(*args, **kwargs)
    return decorated


@customer_bp.route('/dashboard')
@login_required
def dashboard():
    if current_user.is_admin:
        return redirect(url_for('admin.dashboard'))
    recent_bookings = current_user.bookings.order_by(Booking.created_at.desc()).limit(5).all()
    pending_count = current_user.bookings.filter_by(status='pending').count()
    approved_count = current_user.bookings.filter_by(status='approved').count()
    return render_template('customer/dashboard.html',
                           recent_bookings=recent_bookings,
                           pending_count=pending_count,
                           approved_count=approved_count)


@customer_bp.route('/search', methods=['GET', 'POST'])
@login_required
def search_rooms():
    form = RoomSearchForm()
    rooms_list = []
    if form.validate_on_submit() or request.method == 'GET' and request.args.get('check_in'):
        if request.method == 'GET':
            try:
                form.check_in.data = datetime.strptime(request.args.get('check_in'), '%Y-%m-%d').date() if request.args.get('check_in') else None
                form.check_out.data = datetime.strptime(request.args.get('check_out'), '%Y-%m-%d').date() if request.args.get('check_out') else None
            except ValueError:
                flash('Please enter valid check-in and check-out dates.', 'warning')
                return render_template('customer/search.html', form=form, rooms=rooms_list)
            form.guests.data = request.args.get('guests', type=int) or 1
            form.room_type.data = request.args.get('room_type') or ''

        check_in = form.check_in.data
        check_out = form.check_out.data
        guests = form.guests.data or 1
        room_type = form.room_type.data

        if check_in and check_out and check_out > check_in and check_in >= date.today():
            query = Room.query.filter_by(is_available=True).filter(Room.max_occupancy >= guests)
            if room_type:
                query = query.filter_by(room_type=room_type)

            # Exclude rooms with approved bookings that overlap
            overlapping = select(Booking.room_id).where(
                Booking.status == 'approved',
                Booking.check_in < check_out,
                Booking.check_out > check_in
            )
            query = query.filter(~Room.id.in_(overlapping))
            rooms_list = query.order_by(Room.price_per_night).all()
        else:
            flash('Please provide valid future dates (check-out after check-in).', 'warning')

    return render_template('customer/search.html', form=form, rooms=rooms_list)


@customer_bp.route('/book/<int:room_id>', methods=['GET', 'POST'])
@login_required
def book_room(room_id):
    if current_user.is_admin:
        flash('Admins manage bookings from the admin panel.', 'info')
        return redirect(url_for('admin.bookings'))
    room = Room.query.get_or_404(room_id)
    if not room.is_available:
        flash('This room is not available.', 'warning')
        return redirect(url_for('public.rooms'))

    form = BookingForm()
    form.room_id.data = room_id

    # Pre-fill from query params
    if request.method == 'GET':
        try:
            if request.args.get('check_in'):
                form.check_in.data = datetime.strptime(request.args['check_in'], '%Y-%m-%d').date()
            if request.args.get('check_out'):
                form.check_out.data = datetime.strptime(request.args['check_out'], '%Y-%m-%d').date()
            if request.args.get('guests'):
                form.num_guests.data = int(request.args['guests'])
        except (TypeError, ValueError):
            flash('Some pre-filled booking details were invalid. Please enter them again.', 'warning')

    if form.validate_on_submit():
        check_in = form.check_in.data
        check_out = form.check_out.data
        num_guests = form.num_guests.data

        # Validation
        if check_out <= check_in:
            flash('Check-out must be after check-in.', 'danger')
            return render_template('customer/book.html', form=form, room=room)
        if check_in < date.today():
            flash('Check-in cannot be in the past.', 'danger')
            return render_template('customer/book.html', form=form, room=room)
        if num_guests > room.max_occupancy:
            flash(f'This room allows maximum {room.max_occupancy} guests.', 'danger')
            return render_template('customer/book.html', form=form, room=room)

        # Check availability (approved bookings only)
        conflict = Booking.query.filter(
            Booking.room_id == room_id,
            Booking.status == 'approved',
            Booking.check_in < check_out,
            Booking.check_out > check_in
        ).first()
        if conflict:
            flash('Sorry, this room is already booked for the selected dates.', 'danger')
            return render_template('customer/book.html', form=form, room=room)

        nights = (check_out - check_in).days
        total = Decimal(str(room.price_per_night)) * nights

        booking = Booking(
            user_id=current_user.id,
            room_id=room_id,
            check_in=check_in,
            check_out=check_out,
            num_guests=num_guests,
            total_price=total,
            status='pending',
            special_requests=form.special_requests.data
        )
        db.session.add(booking)
        db.session.commit()
        send_booking_status_email(booking)
        flash('Booking request submitted! Awaiting admin approval.', 'success')
        return redirect(url_for('customer.my_bookings', confirmed=booking.id))

    return render_template('customer/book.html', form=form, room=room)


@customer_bp.route('/bookings')
@login_required
def my_bookings():
    if current_user.is_admin:
        return redirect(url_for('admin.bookings'))
    bookings = current_user.bookings.order_by(Booking.created_at.desc()).all()
    return render_template('customer/bookings.html', bookings=bookings)


@customer_bp.route('/bookings/<int:booking_id>/cancel', methods=['POST'])
@login_required
def cancel_booking(booking_id):
    booking = Booking.query.get_or_404(booking_id)
    if booking.user_id != current_user.id:
        abort(403)
    if booking.status not in ('pending', 'approved'):
        flash('This booking cannot be cancelled.', 'warning')
        return redirect(url_for('customer.my_bookings'))
    booking.status = 'cancelled'
    db.session.commit()
    send_booking_status_email(booking)
    flash('Booking cancelled successfully.', 'success')
    return redirect(url_for('customer.my_bookings'))


@customer_bp.route('/profile', methods=['GET', 'POST'])
@login_required
def profile():
    form = ProfileForm(obj=current_user)
    pwd_form = ChangePasswordForm()
    if form.validate_on_submit() and 'update_profile' in request.form:
        current_user.first_name = form.first_name.data
        current_user.last_name = form.last_name.data
        current_user.phone = form.phone.data
        current_user.address = form.address.data
        db.session.commit()
        flash('Profile updated.', 'success')
        return redirect(url_for('customer.profile'))
    return render_template('customer/profile.html', form=form, pwd_form=pwd_form)


@customer_bp.route('/change-password', methods=['POST'])
@login_required
def change_password():
    form = ChangePasswordForm()
    if form.validate_on_submit():
        if not current_user.check_password(form.current_password.data):
            flash('Current password is incorrect.', 'danger')
        else:
            current_user.set_password(form.new_password.data)
            db.session.commit()
            flash('Password changed successfully.', 'success')
    else:
        for err in form.errors.values():
            flash(err[0], 'danger')
    return redirect(url_for('customer.profile'))
