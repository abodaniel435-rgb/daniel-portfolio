"""
Database models for Hotel Miramar SG Room Booking System.
Normalized relational schema with proper relationships and constraints.
"""
from datetime import datetime, date
from werkzeug.security import generate_password_hash, check_password_hash
from flask_login import UserMixin
from app import db, login_manager


class User(UserMixin, db.Model):
    """Registered customers and administrators."""
    __tablename__ = 'users'

    id = db.Column(db.Integer, primary_key=True)
    email = db.Column(db.String(120), unique=True, nullable=False, index=True)
    password_hash = db.Column(db.String(256), nullable=False)
    first_name = db.Column(db.String(80), nullable=False)
    last_name = db.Column(db.String(80), nullable=False)
    phone = db.Column(db.String(30))
    address = db.Column(db.String(255))
    is_admin = db.Column(db.Boolean, default=False, nullable=False)
    is_active = db.Column(db.Boolean, default=True, nullable=False)
    created_at = db.Column(db.DateTime, default=datetime.utcnow)
    updated_at = db.Column(db.DateTime, default=datetime.utcnow, onupdate=datetime.utcnow)

    bookings = db.relationship('Booking', back_populates='user', lazy='dynamic',
                               cascade='all, delete-orphan')
    feedbacks = db.relationship('Feedback', back_populates='user', lazy='dynamic')

    def set_password(self, password):
        self.password_hash = generate_password_hash(password)

    def check_password(self, password):
        return check_password_hash(self.password_hash, password)

    @property
    def full_name(self):
        return f'{self.first_name} {self.last_name}'

    def __repr__(self):
        return f'<User {self.email}>'


@login_manager.user_loader
def load_user(user_id):
    return User.query.get(int(user_id))


class Room(db.Model):
    """Hotel rooms available for booking."""
    __tablename__ = 'rooms'

    id = db.Column(db.Integer, primary_key=True)
    name = db.Column(db.String(120), nullable=False)
    room_type = db.Column(db.String(50), nullable=False)  # Standard, Deluxe, Suite, etc.
    description = db.Column(db.Text)
    price_per_night = db.Column(db.Numeric(10, 2), nullable=False)
    max_occupancy = db.Column(db.Integer, default=2)
    amenities = db.Column(db.Text)  # comma-separated or JSON-like string
    image_filename = db.Column(db.String(255))
    is_available = db.Column(db.Boolean, default=True, nullable=False)
    is_featured = db.Column(db.Boolean, default=False)
    created_at = db.Column(db.DateTime, default=datetime.utcnow)
    updated_at = db.Column(db.DateTime, default=datetime.utcnow, onupdate=datetime.utcnow)

    bookings = db.relationship('Booking', back_populates='room', lazy='dynamic')

    def __repr__(self):
        return f'<Room {self.name}>'


class Booking(db.Model):
    """Room booking requests and confirmed reservations."""
    __tablename__ = 'bookings'

    id = db.Column(db.Integer, primary_key=True)
    user_id = db.Column(db.Integer, db.ForeignKey('users.id'), nullable=False, index=True)
    room_id = db.Column(db.Integer, db.ForeignKey('rooms.id'), nullable=False, index=True)
    check_in = db.Column(db.Date, nullable=False)
    check_out = db.Column(db.Date, nullable=False)
    num_guests = db.Column(db.Integer, default=1)
    total_price = db.Column(db.Numeric(12, 2), nullable=False)
    status = db.Column(db.String(20), default='pending', nullable=False, index=True)
    # pending | approved | disapproved | cancelled
    special_requests = db.Column(db.Text)
    admin_notes = db.Column(db.Text)
    created_at = db.Column(db.DateTime, default=datetime.utcnow)
    updated_at = db.Column(db.DateTime, default=datetime.utcnow, onupdate=datetime.utcnow)

    user = db.relationship('User', back_populates='bookings')
    room = db.relationship('Room', back_populates='bookings')

    @property
    def nights(self):
        if self.check_in and self.check_out:
            return (self.check_out - self.check_in).days
        return 0

    def __repr__(self):
        return f'<Booking {self.id} {self.status}>'


class Feedback(db.Model):
    """Customer feedback / testimonials."""
    __tablename__ = 'feedbacks'

    id = db.Column(db.Integer, primary_key=True)
    user_id = db.Column(db.Integer, db.ForeignKey('users.id'), nullable=True)
    name = db.Column(db.String(120), nullable=False)
    email = db.Column(db.String(120))
    rating = db.Column(db.Integer, default=5)  # 1-5
    message = db.Column(db.Text, nullable=False)
    is_approved = db.Column(db.Boolean, default=False)
    created_at = db.Column(db.DateTime, default=datetime.utcnow)

    user = db.relationship('User', back_populates='feedbacks')

    def __repr__(self):
        return f'<Feedback {self.id}>'


class ContactMessage(db.Model):
    """Contact Us form submissions."""
    __tablename__ = 'contact_messages'

    id = db.Column(db.Integer, primary_key=True)
    name = db.Column(db.String(120), nullable=False)
    email = db.Column(db.String(120), nullable=False)
    subject = db.Column(db.String(200))
    message = db.Column(db.Text, nullable=False)
    is_read = db.Column(db.Boolean, default=False)
    created_at = db.Column(db.DateTime, default=datetime.utcnow)

    def __repr__(self):
        return f'<ContactMessage {self.id}>'


class SliderImage(db.Model):
    """Homepage slider images managed by admin."""
    __tablename__ = 'slider_images'

    id = db.Column(db.Integer, primary_key=True)
    title = db.Column(db.String(120))
    caption = db.Column(db.String(255))
    image_filename = db.Column(db.String(255), nullable=False)
    link_url = db.Column(db.String(255))
    sort_order = db.Column(db.Integer, default=0)
    is_active = db.Column(db.Boolean, default=True)
    created_at = db.Column(db.DateTime, default=datetime.utcnow)

    def __repr__(self):
        return f'<SliderImage {self.id}>'


class PasswordResetToken(db.Model):
    """Password recovery tokens."""
    __tablename__ = 'password_reset_tokens'

    id = db.Column(db.Integer, primary_key=True)
    user_id = db.Column(db.Integer, db.ForeignKey('users.id'), nullable=False)
    token = db.Column(db.String(100), unique=True, nullable=False, index=True)
    expires_at = db.Column(db.DateTime, nullable=False)
    used = db.Column(db.Boolean, default=False)
    created_at = db.Column(db.DateTime, default=datetime.utcnow)

    user = db.relationship('User')

    def is_valid(self):
        return not self.used and self.expires_at > datetime.utcnow()
