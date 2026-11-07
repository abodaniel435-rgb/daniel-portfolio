"""Database bootstrap shared by the dev server (run.py) and Passenger (passenger_wsgi.py)."""
import os

from app import db
from app.models.models import Feedback, Room, User


def seed_database(app):
    """Create tables and insert sample data if empty."""
    db_dir = os.path.join(os.path.dirname(os.path.dirname(__file__)), 'database')
    os.makedirs(db_dir, exist_ok=True)

    db.create_all()

    if not User.query.filter_by(is_admin=True).first():
        admin = User(
            email='admin@hotelmiramarsg.com',
            first_name='Admin',
            last_name='Miramar',
            is_admin=True,
            phone='+65 6123 4567',
        )
        admin.set_password('admin123')
        db.session.add(admin)
        print('[seed] Admin created: admin@hotelmiramarsg.com / admin123')

    if Room.query.count() == 0:
        rooms = [
            Room(
                name='Harbour View Standard',
                room_type='Standard',
                description='Comfortable standard room with harbour glimpses, queen bed, work desk, and modern amenities. Perfect for business travellers and couples.',
                price_per_night=180.00,
                max_occupancy=2,
                amenities='Free Wi-Fi, Air Conditioning, Flat-screen TV, Mini Bar, En-suite Bathroom, Tea/Coffee Maker',
                is_available=True,
                is_featured=True,
            ),
            Room(
                name='Marina Deluxe',
                room_type='Deluxe',
                description='Spacious deluxe room featuring floor-to-ceiling windows overlooking Marina Bay. King bed, seating area, and premium bath products.',
                price_per_night=280.00,
                max_occupancy=2,
                amenities='Free Wi-Fi, Air Conditioning, 55" Smart TV, Mini Bar, Rain Shower, Bathrobe & Slippers, Nespresso Machine',
                is_available=True,
                is_featured=True,
            ),
            Room(
                name='Executive Suite',
                room_type='Suite',
                description='Elegant suite with separate living area, panoramic city views, and exclusive Executive Lounge access.',
                price_per_night=450.00,
                max_occupancy=3,
                amenities='Free Wi-Fi, Lounge Access, Separate Living Room, King Bed, Bathtub & Rain Shower, 24h Butler Service',
                is_available=True,
                is_featured=True,
            ),
            Room(
                name='Presidential Suite',
                room_type='Presidential',
                description='The pinnacle of luxury at Hotel Miramar SG. Private terrace, dining room, two bedrooms, and dedicated butler.',
                price_per_night=980.00,
                max_occupancy=4,
                amenities='Private Terrace, Dining Room, Two Bedrooms, Full Butler Service, Jacuzzi, Wine Cellar Access, Airport Transfer',
                is_available=True,
                is_featured=False,
            ),
            Room(
                name='Garden Courtyard Standard',
                room_type='Standard',
                description='Quiet courtyard-facing standard room with soft natural light, twin beds option, and calming garden views.',
                price_per_night=165.00,
                max_occupancy=2,
                amenities='Free Wi-Fi, Air Conditioning, Flat-screen TV, Mini Fridge, En-suite Bathroom',
                is_available=True,
                is_featured=False,
            ),
        ]
        for room in rooms:
            db.session.add(room)
        print(f'[seed] {len(rooms)} sample rooms added')

    if Feedback.query.count() == 0:
        samples = [
            Feedback(name='Sarah Chen', email='sarah@example.com', rating=5,
                     message='Absolutely stunning stay. The Marina Deluxe exceeded expectations and the staff were impeccable.', is_approved=True),
            Feedback(name='James Wong', email='james@example.com', rating=5,
                     message='Best location in Singapore. Walking distance to everything yet peaceful. Will return.', is_approved=True),
            Feedback(name='Priya Nair', email='priya@example.com', rating=4,
                     message='Beautiful rooms and excellent breakfast. Highly recommend the Executive Suite.', is_approved=True),
        ]
        for feedback in samples:
            db.session.add(feedback)
        print('[seed] Sample feedback added')

    db.session.commit()
    print('[seed] Database ready.')


def ensure_database(app):
    """Idempotent: safe to call on every boot (dev reload, Passenger respawn, CLI)."""
    try:
        with app.app_context():
            db.create_all()
            if app.config.get('SEED_DEMO_DATA'):
                seed_database(app)
    except Exception as error:  # noqa: BLE001 - boot must not die on a transient DB lock
        print(f'[seed] Warning: {error}')
