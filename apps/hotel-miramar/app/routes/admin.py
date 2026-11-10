"""
Administrator dashboard and management routes.
"""
import os
import uuid
from functools import wraps
from flask import (
    Blueprint, render_template, redirect, url_for, flash, request,
    current_app, abort
)
from flask_login import login_required, current_user
from werkzeug.utils import secure_filename
from app import db
from app.models.models import (
    User, Room, Booking, Feedback, ContactMessage, SliderImage
)
from app.forms.forms import (
    RoomForm, SliderForm, BookingStatusForm, ProfileForm, ChangePasswordForm
)
from app.services.notifications import send_booking_status_email
from datetime import date
from decimal import Decimal
from sqlalchemy import func

admin_bp = Blueprint('admin', __name__)


def admin_required(f):
    @wraps(f)
    @login_required
    def decorated(*args, **kwargs):
        if not current_user.is_admin:
            abort(403)
        return f(*args, **kwargs)
    return decorated


def allowed_file(filename):
    return '.' in filename and \
           filename.rsplit('.', 1)[1].lower() in current_app.config['ALLOWED_EXTENSIONS']


def save_upload(file, subfolder='rooms'):
    if file and allowed_file(file.filename):
        ext = file.filename.rsplit('.', 1)[1].lower()
        filename = f'{uuid.uuid4().hex}.{ext}'
        folder = os.path.join(current_app.root_path, 'static', 'images', subfolder)
        os.makedirs(folder, exist_ok=True)
        path = os.path.join(folder, filename)
        file.save(path)
        return filename
    return None


@admin_bp.route('/login')
def login():
    """Convenience entry point for staff: /admin/login → shared sign-in."""
    return redirect(url_for('auth.login', next=url_for('admin.dashboard')))


@admin_bp.route('/dashboard')
@admin_required
def dashboard():
    stats = {
        'rooms': Room.query.count(),
        'available_rooms': Room.query.filter_by(is_available=True).count(),
        'customers': User.query.filter_by(is_admin=False).count(),
        'bookings': Booking.query.count(),
        'pending': Booking.query.filter_by(status='pending').count(),
        'approved': Booking.query.filter_by(status='approved').count(),
        'feedback_pending': Feedback.query.filter_by(is_approved=False).count(),
        'unread_messages': ContactMessage.query.filter_by(is_read=False).count(),
    }
    recent_bookings = Booking.query.order_by(Booking.created_at.desc()).limit(8).all()
    return render_template('admin/dashboard.html', stats=stats, recent_bookings=recent_bookings)


# ---------- Rooms ----------
@admin_bp.route('/rooms')
@admin_required
def rooms():
    rooms_list = Room.query.order_by(Room.created_at.desc()).all()
    return render_template('admin/rooms.html', rooms=rooms_list)


@admin_bp.route('/rooms/add', methods=['GET', 'POST'])
@admin_required
def add_room():
    form = RoomForm()
    if form.validate_on_submit():
        room = Room(
            name=form.name.data,
            room_type=form.room_type.data,
            description=form.description.data,
            price_per_night=form.price_per_night.data,
            max_occupancy=form.max_occupancy.data,
            amenities=form.amenities.data,
            is_available=form.is_available.data,
            is_featured=form.is_featured.data
        )
        if form.image.data:
            filename = save_upload(form.image.data, 'rooms')
            if filename:
                room.image_filename = filename
        db.session.add(room)
        db.session.commit()
        flash('Room added successfully.', 'success')
        return redirect(url_for('admin.rooms'))
    return render_template('admin/room_form.html', form=form, title='Add Room')


@admin_bp.route('/rooms/<int:room_id>/edit', methods=['GET', 'POST'])
@admin_required
def edit_room(room_id):
    room = Room.query.get_or_404(room_id)
    form = RoomForm(obj=room)
    if form.validate_on_submit():
        room.name = form.name.data
        room.room_type = form.room_type.data
        room.description = form.description.data
        room.price_per_night = form.price_per_night.data
        room.max_occupancy = form.max_occupancy.data
        room.amenities = form.amenities.data
        room.is_available = form.is_available.data
        room.is_featured = form.is_featured.data
        if form.image.data:
            filename = save_upload(form.image.data, 'rooms')
            if filename:
                room.image_filename = filename
        db.session.commit()
        flash('Room updated successfully.', 'success')
        return redirect(url_for('admin.rooms'))
    return render_template('admin/room_form.html', form=form, title='Edit Room', room=room)


@admin_bp.route('/rooms/<int:room_id>/delete', methods=['POST'])
@admin_required
def delete_room(room_id):
    room = Room.query.get_or_404(room_id)
    # Soft-delete: deactivate if has bookings, else hard delete
    has_bookings = room.bookings.count() > 0
    if has_bookings:
        room.is_available = False
        db.session.commit()
        flash('Room has bookings — marked as unavailable instead of deleted.', 'info')
    else:
        db.session.delete(room)
        db.session.commit()
        flash('Room deleted.', 'success')
    return redirect(url_for('admin.rooms'))


# ---------- Customers ----------
@admin_bp.route('/customers')
@admin_required
def customers():
    customers_list = User.query.filter_by(is_admin=False).order_by(User.created_at.desc()).all()
    return render_template('admin/customers.html', customers=customers_list)


@admin_bp.route('/customers/<int:user_id>/toggle', methods=['POST'])
@admin_required
def toggle_customer(user_id):
    user = User.query.get_or_404(user_id)
    if user.is_admin:
        flash('Cannot modify admin accounts here.', 'warning')
        return redirect(url_for('admin.customers'))
    user.is_active = not user.is_active
    db.session.commit()
    status = 'activated' if user.is_active else 'deactivated'
    flash(f'Customer {status}.', 'success')
    return redirect(url_for('admin.customers'))


# ---------- Bookings ----------
@admin_bp.route('/bookings')
@admin_required
def bookings():
    status_filter = request.args.get('status', '')
    query = Booking.query
    if status_filter:
        query = query.filter_by(status=status_filter)
    bookings_list = query.order_by(Booking.created_at.desc()).all()
    return render_template('admin/bookings.html', bookings=bookings_list, status_filter=status_filter)


@admin_bp.route('/bookings/<int:booking_id>', methods=['GET', 'POST'])
@admin_required
def booking_detail(booking_id):
    booking = Booking.query.get_or_404(booking_id)
    form = BookingStatusForm(obj=booking)
    if form.validate_on_submit():
        new_status = form.status.data
        previous_status = booking.status
        # Re-check availability before approving
        if new_status == 'approved':
            conflict = Booking.query.filter(
                Booking.room_id == booking.room_id,
                Booking.id != booking.id,
                Booking.status == 'approved',
                Booking.check_in < booking.check_out,
                Booking.check_out > booking.check_in
            ).first()
            if conflict:
                flash('Cannot approve: room has a conflicting approved booking.', 'danger')
                return redirect(url_for('admin.booking_detail', booking_id=booking.id))
            if not booking.room.is_available:
                flash('Cannot approve: room is currently marked unavailable.', 'danger')
                return redirect(url_for('admin.booking_detail', booking_id=booking.id))
        booking.status = new_status
        booking.admin_notes = form.admin_notes.data
        db.session.commit()
        if new_status != previous_status:
            send_booking_status_email(booking)
        flash(f'Booking status updated to {new_status}.', 'success')
        return redirect(url_for('admin.bookings'))
    return render_template('admin/booking_detail.html', booking=booking, form=form)


# ---------- Feedback ----------
@admin_bp.route('/feedback')
@admin_required
def feedback():
    items = Feedback.query.order_by(Feedback.created_at.desc()).all()
    return render_template('admin/feedback.html', feedbacks=items)


@admin_bp.route('/feedback/<int:fb_id>/approve', methods=['POST'])
@admin_required
def approve_feedback(fb_id):
    fb = Feedback.query.get_or_404(fb_id)
    fb.is_approved = True
    db.session.commit()
    flash('Feedback approved.', 'success')
    return redirect(url_for('admin.feedback'))


@admin_bp.route('/feedback/<int:fb_id>/delete', methods=['POST'])
@admin_required
def delete_feedback(fb_id):
    fb = Feedback.query.get_or_404(fb_id)
    db.session.delete(fb)
    db.session.commit()
    flash('Feedback deleted.', 'success')
    return redirect(url_for('admin.feedback'))


# ---------- Contact Messages ----------
@admin_bp.route('/messages')
@admin_required
def messages():
    msgs = ContactMessage.query.order_by(ContactMessage.created_at.desc()).all()
    return render_template('admin/messages.html', messages=msgs)


@admin_bp.route('/messages/<int:msg_id>/read', methods=['POST'])
@admin_required
def mark_message_read(msg_id):
    msg = ContactMessage.query.get_or_404(msg_id)
    msg.is_read = True
    db.session.commit()
    return redirect(url_for('admin.messages'))


@admin_bp.route('/messages/<int:msg_id>/delete', methods=['POST'])
@admin_required
def delete_message(msg_id):
    msg = ContactMessage.query.get_or_404(msg_id)
    db.session.delete(msg)
    db.session.commit()
    flash('Message deleted.', 'success')
    return redirect(url_for('admin.messages'))


# ---------- Slider ----------
@admin_bp.route('/slider')
@admin_required
def slider():
    items = SliderImage.query.order_by(SliderImage.sort_order).all()
    return render_template('admin/slider.html', sliders=items)


@admin_bp.route('/slider/add', methods=['GET', 'POST'])
@admin_required
def add_slider():
    form = SliderForm()
    if form.validate_on_submit():
        filename = None
        if form.image.data:
            filename = save_upload(form.image.data, 'slider')
        if not filename:
            flash('Please upload an image.', 'danger')
            return render_template('admin/slider_form.html', form=form, title='Add Slider Image')
        item = SliderImage(
            title=form.title.data,
            caption=form.caption.data,
            image_filename=filename,
            link_url=form.link_url.data,
            sort_order=form.sort_order.data or 0,
            is_active=form.is_active.data
        )
        db.session.add(item)
        db.session.commit()
        flash('Slider image added.', 'success')
        return redirect(url_for('admin.slider'))
    return render_template('admin/slider_form.html', form=form, title='Add Slider Image')


@admin_bp.route('/slider/<int:item_id>/edit', methods=['GET', 'POST'])
@admin_required
def edit_slider(item_id):
    item = SliderImage.query.get_or_404(item_id)
    form = SliderForm(obj=item)
    if form.validate_on_submit():
        item.title = form.title.data
        item.caption = form.caption.data
        item.link_url = form.link_url.data
        item.sort_order = form.sort_order.data or 0
        item.is_active = form.is_active.data
        if form.image.data:
            filename = save_upload(form.image.data, 'slider')
            if filename:
                item.image_filename = filename
        db.session.commit()
        flash('Slider updated.', 'success')
        return redirect(url_for('admin.slider'))
    return render_template('admin/slider_form.html', form=form, title='Edit Slider', slider=item)


@admin_bp.route('/slider/<int:item_id>/delete', methods=['POST'])
@admin_required
def delete_slider(item_id):
    item = SliderImage.query.get_or_404(item_id)
    db.session.delete(item)
    db.session.commit()
    flash('Slider image deleted.', 'success')
    return redirect(url_for('admin.slider'))


# ---------- Admin Profile ----------
@admin_bp.route('/profile', methods=['GET', 'POST'])
@admin_required
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
        return redirect(url_for('admin.profile'))
    return render_template('admin/profile.html', form=form, pwd_form=pwd_form)


@admin_bp.route('/change-password', methods=['POST'])
@admin_required
def change_password():
    form = ChangePasswordForm()
    if form.validate_on_submit():
        if not current_user.check_password(form.current_password.data):
            flash('Current password is incorrect.', 'danger')
        else:
            current_user.set_password(form.new_password.data)
            db.session.commit()
            flash('Password changed.', 'success')
    return redirect(url_for('admin.profile'))
