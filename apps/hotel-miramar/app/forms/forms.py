"""
WTForms for Hotel Miramar SG application.
"""
from flask_wtf import FlaskForm
from flask_wtf.file import FileField, FileAllowed
from wtforms import (
    StringField, PasswordField, SubmitField, TextAreaField,
    IntegerField, DecimalField, BooleanField, SelectField,
    DateField, HiddenField
)
from wtforms.validators import (
    DataRequired, Email, EqualTo, Length, Optional, NumberRange,
    ValidationError
)
from app.models.models import User


class RegistrationForm(FlaskForm):
    first_name = StringField('First Name', validators=[DataRequired(), Length(1, 80)])
    last_name = StringField('Last Name', validators=[DataRequired(), Length(1, 80)])
    email = StringField('Email', validators=[DataRequired(), Email(), Length(1, 120)])
    phone = StringField('Phone', validators=[Optional(), Length(0, 30)])
    password = PasswordField('Password', validators=[DataRequired(), Length(6, 128)])
    confirm_password = PasswordField(
        'Confirm Password',
        validators=[DataRequired(), EqualTo('password', message='Passwords must match')]
    )
    submit = SubmitField('Create Account')

    def validate_email(self, field):
        if User.query.filter_by(email=field.data.lower()).first():
            raise ValidationError('Email already registered.')


class LoginForm(FlaskForm):
    email = StringField('Email', validators=[DataRequired(), Email()])
    password = PasswordField('Password', validators=[DataRequired()])
    remember = BooleanField('Remember Me')
    submit = SubmitField('Sign In')


class PasswordRecoveryForm(FlaskForm):
    email = StringField('Email', validators=[DataRequired(), Email()])
    submit = SubmitField('Send Reset Link')


class ResetPasswordForm(FlaskForm):
    password = PasswordField('New Password', validators=[DataRequired(), Length(6, 128)])
    confirm_password = PasswordField(
        'Confirm Password',
        validators=[DataRequired(), EqualTo('password')]
    )
    submit = SubmitField('Reset Password')


class ProfileForm(FlaskForm):
    first_name = StringField('First Name', validators=[DataRequired(), Length(1, 80)])
    last_name = StringField('Last Name', validators=[DataRequired(), Length(1, 80)])
    phone = StringField('Phone', validators=[Optional(), Length(0, 30)])
    address = StringField('Address', validators=[Optional(), Length(0, 255)])
    submit = SubmitField('Update Profile')


class ChangePasswordForm(FlaskForm):
    current_password = PasswordField('Current Password', validators=[DataRequired()])
    new_password = PasswordField('New Password', validators=[DataRequired(), Length(6, 128)])
    confirm_password = PasswordField(
        'Confirm New Password',
        validators=[DataRequired(), EqualTo('new_password')]
    )
    submit = SubmitField('Change Password')


class RoomSearchForm(FlaskForm):
    check_in = DateField('Check-in', validators=[DataRequired()], format='%Y-%m-%d')
    check_out = DateField('Check-out', validators=[DataRequired()], format='%Y-%m-%d')
    guests = IntegerField('Guests', default=1, validators=[NumberRange(1, 10)])
    room_type = SelectField('Room Type', choices=[
        ('', 'Any Type'),
        ('Standard', 'Standard'),
        ('Deluxe', 'Deluxe'),
        ('Suite', 'Suite'),
        ('Presidential', 'Presidential')
    ], validators=[Optional()])
    submit = SubmitField('Search Rooms')


class BookingForm(FlaskForm):
    room_id = HiddenField(validators=[DataRequired()])
    check_in = DateField('Check-in', validators=[DataRequired()], format='%Y-%m-%d')
    check_out = DateField('Check-out', validators=[DataRequired()], format='%Y-%m-%d')
    num_guests = IntegerField('Number of Guests', default=1, validators=[DataRequired(), NumberRange(1, 10)])
    special_requests = TextAreaField('Special Requests', validators=[Optional(), Length(0, 500)])
    submit = SubmitField('Request Booking')


class ContactForm(FlaskForm):
    name = StringField('Your Name', validators=[DataRequired(), Length(1, 120)])
    email = StringField('Email', validators=[DataRequired(), Email()])
    subject = StringField('Subject', validators=[Optional(), Length(0, 200)])
    message = TextAreaField('Message', validators=[DataRequired(), Length(10, 2000)])
    submit = SubmitField('Send Message')


class FeedbackForm(FlaskForm):
    name = StringField('Your Name', validators=[DataRequired(), Length(1, 120)])
    email = StringField('Email', validators=[Optional(), Email()])
    rating = SelectField('Rating', choices=[(5, '5 - Excellent'), (4, '4 - Very Good'),
                                            (3, '3 - Good'), (2, '2 - Fair'), (1, '1 - Poor')],
                         coerce=int, default=5)
    message = TextAreaField('Your Feedback', validators=[DataRequired(), Length(10, 1000)])
    submit = SubmitField('Submit Feedback')


# ---------- Admin Forms ----------

class RoomForm(FlaskForm):
    name = StringField('Room Name', validators=[DataRequired(), Length(1, 120)])
    room_type = SelectField('Type', choices=[
        ('Standard', 'Standard'),
        ('Deluxe', 'Deluxe'),
        ('Suite', 'Suite'),
        ('Presidential', 'Presidential')
    ], validators=[DataRequired()])
    description = TextAreaField('Description', validators=[Optional()])
    price_per_night = DecimalField('Price per Night (SGD)', places=2,
                                   validators=[DataRequired(), NumberRange(min=1)])
    max_occupancy = IntegerField('Max Occupancy', default=2,
                                 validators=[DataRequired(), NumberRange(1, 12)])
    amenities = TextAreaField('Amenities (comma-separated)', validators=[Optional()])
    image = FileField('Room Image', validators=[
        FileAllowed(['jpg', 'jpeg', 'png', 'gif', 'webp'], 'Images only!')
    ])
    is_available = BooleanField('Available for Booking', default=True)
    is_featured = BooleanField('Featured on Homepage', default=False)
    submit = SubmitField('Save Room')


class SliderForm(FlaskForm):
    title = StringField('Title', validators=[Optional(), Length(0, 120)])
    caption = StringField('Caption', validators=[Optional(), Length(0, 255)])
    image = FileField('Slider Image', validators=[
        FileAllowed(['jpg', 'jpeg', 'png', 'gif', 'webp'], 'Images only!')
    ])
    link_url = StringField('Link URL', validators=[Optional(), Length(0, 255)])
    sort_order = IntegerField('Sort Order', default=0)
    is_active = BooleanField('Active', default=True)
    submit = SubmitField('Save Slider')


class BookingStatusForm(FlaskForm):
    status = SelectField('Status', choices=[
        ('pending', 'Pending'),
        ('approved', 'Approved'),
        ('disapproved', 'Disapproved'),
        ('cancelled', 'Cancelled')
    ], validators=[DataRequired()])
    admin_notes = TextAreaField('Admin Notes', validators=[Optional()])
    submit = SubmitField('Update Status')
