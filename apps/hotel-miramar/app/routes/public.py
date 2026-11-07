"""
Public-facing routes for Hotel Miramar SG.
"""
from flask import Blueprint, render_template, redirect, url_for, flash, request, current_app
from flask_login import current_user
from app import db
from app.models.models import Room, Booking, Feedback, ContactMessage, SliderImage
from app.forms.forms import ContactForm, FeedbackForm, RoomSearchForm
from datetime import date, datetime
from sqlalchemy import select

public_bp = Blueprint('public', __name__)


@public_bp.route('/')
def index():
    """Homepage with hero, featured rooms, slider, facilities."""
    featured_rooms = Room.query.filter_by(is_available=True, is_featured=True).limit(3).all()
    if not featured_rooms:
        featured_rooms = Room.query.filter_by(is_available=True).limit(3).all()
    sliders = SliderImage.query.filter_by(is_active=True).order_by(SliderImage.sort_order).all()
    testimonials = Feedback.query.filter_by(is_approved=True).order_by(Feedback.created_at.desc()).limit(3).all()
    search_form = RoomSearchForm()
    return render_template('public/index.html',
                           featured_rooms=featured_rooms,
                           sliders=sliders,
                           testimonials=testimonials,
                           search_form=search_form)


@public_bp.route('/about')
def about():
    return render_template('public/about.html')


@public_bp.route('/rooms')
def rooms():
    """List all available rooms with optional search filters."""
    form = RoomSearchForm(request.args, meta={'csrf': False})
    query = Room.query.filter_by(is_available=True)

    room_type = request.args.get('room_type')
    if room_type:
        query = query.filter_by(room_type=room_type)

    guests = request.args.get('guests', type=int)
    if guests:
        query = query.filter(Room.max_occupancy >= guests)

    check_in_raw = request.args.get('check_in')
    check_out_raw = request.args.get('check_out')
    if check_in_raw or check_out_raw:
        try:
            check_in = datetime.strptime(check_in_raw, '%Y-%m-%d').date()
            check_out = datetime.strptime(check_out_raw, '%Y-%m-%d').date()
            if check_in < date.today() or check_out <= check_in:
                raise ValueError
            overlapping = select(Booking.room_id).where(
                Booking.status == 'approved',
                Booking.check_in < check_out,
                Booking.check_out > check_in
            )
            query = query.filter(~Room.id.in_(overlapping))
        except (TypeError, ValueError):
            flash('Enter valid future check-in and check-out dates to check availability.', 'warning')

    rooms_list = query.order_by(Room.price_per_night).all()
    return render_template('public/rooms.html', rooms=rooms_list, form=form)


@public_bp.route('/rooms/<int:room_id>')
def room_detail(room_id):
    room = Room.query.get_or_404(room_id)
    if not room.is_available and (not current_user.is_authenticated or not current_user.is_admin):
        flash('This room is currently unavailable.', 'warning')
        return redirect(url_for('public.rooms'))
    form = RoomSearchForm()
    return render_template('public/room_detail.html', room=room, form=form)


@public_bp.route('/attractions')
def attractions():
    return render_template('public/attractions.html')


@public_bp.route('/location')
def location():
    return render_template(
        'public/location.html',
        hotel_lat=current_app.config['HOTEL_LAT'],
        hotel_lng=current_app.config['HOTEL_LNG'],
    )


@public_bp.route('/contact', methods=['GET', 'POST'])
def contact():
    form = ContactForm()
    if form.validate_on_submit():
        msg = ContactMessage(
            name=form.name.data,
            email=form.email.data.lower(),
            subject=form.subject.data,
            message=form.message.data
        )
        db.session.add(msg)
        db.session.commit()
        from app.services.notifications import (
            send_contact_admin_notification, send_contact_guest_confirmation
        )
        send_contact_admin_notification(msg)
        send_contact_guest_confirmation(msg)
        flash('Thank you! Your message has been sent. We will respond shortly.', 'success')
        return redirect(url_for('public.contact'))
    return render_template('public/contact.html', form=form)


@public_bp.route('/feedback', methods=['GET', 'POST'])
def feedback():
    form = FeedbackForm()
    if current_user.is_authenticated:
        form.name.data = current_user.full_name
        form.email.data = current_user.email
    if form.validate_on_submit():
        fb = Feedback(
            user_id=current_user.id if current_user.is_authenticated else None,
            name=form.name.data,
            email=form.email.data,
            rating=form.rating.data,
            message=form.message.data,
            is_approved=False
        )
        db.session.add(fb)
        db.session.commit()
        flash('Thank you for your feedback! It will appear after review.', 'success')
        return redirect(url_for('public.index'))
    return render_template('public/feedback.html', form=form)
