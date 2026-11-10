"""
Hotel Miramar SG - Application Entry Point
Auto-creates database tables and sample data on first run.
"""
import os
from app import create_app, db
from app.models.models import User, Room, Feedback

app = create_app(os.environ.get('FLASK_ENV', 'development'))


def seed_database():
    """Create tables and insert sample data if empty."""
    # Ensure database folder exists
    db_dir = os.path.join(os.path.dirname(__file__), 'database')
    os.makedirs(db_dir, exist_ok=True)

    db.create_all()

    # Initial administrator comes from the environment, never from source.
    if not User.query.filter_by(is_admin=True).first():
        admin_email = os.environ.get('ADMIN_EMAIL', 'abodaniel435@gmail.com').strip().lower()
        admin_password = os.environ.get('ADMIN_PASSWORD', '').strip()
        if not admin_password:
            print('[seed] ADMIN_PASSWORD is not set - no administrator account created.')
            print('[seed] Set ADMIN_EMAIL and ADMIN_PASSWORD in .env, then run: flask init-db')
        else:
            admin = User(
                email=admin_email,
                first_name=os.environ.get('ADMIN_FIRST_NAME', 'Abo'),
                last_name=os.environ.get('ADMIN_LAST_NAME', 'Daniel'),
                is_admin=True,
                phone=os.environ.get('ADMIN_PHONE', '+65 6123 4567')
            )
            admin.set_password(admin_password)
            db.session.add(admin)
            print(f'[seed] Administrator created: {admin_email}')

    # Sample rooms
    if Room.query.count() == 0:
        rooms = [
            Room(
                name='Harbour View Standard',
                room_type='Standard',
                description='Comfortable standard room with harbour glimpses, queen bed, work desk, and modern amenities. Perfect for business travellers and couples.',
                price_per_night=180.00,
                max_occupancy=2,
                amenities='Free Wi-Fi, Air Conditioning, Flat-screen TV, Mini Bar, En-suite Bathroom, Tea/Coffee Maker',
                image_filename='harbour-view-standard.jpg',
                is_available=True,
                is_featured=True
            ),
            Room(
                name='Marina Deluxe',
                room_type='Deluxe',
                description='Spacious deluxe room featuring floor-to-ceiling windows overlooking Marina Bay. King bed, seating area, and premium bath products.',
                price_per_night=280.00,
                max_occupancy=2,
                amenities='Free Wi-Fi, Air Conditioning, 55" Smart TV, Mini Bar, Rain Shower, Bathrobe & Slippers, Nespresso Machine',
                image_filename='marina-deluxe.jpg',
                is_available=True,
                is_featured=True
            ),
            Room(
                name='Executive Suite',
                room_type='Suite',
                description='Elegant suite with separate living area, panoramic city views, and exclusive Executive Lounge access.',
                price_per_night=450.00,
                max_occupancy=3,
                amenities='Free Wi-Fi, Lounge Access, Separate Living Room, King Bed, Bathtub & Rain Shower, 24h Butler Service',
                image_filename='executive-suite.jpg',
                is_available=True,
                is_featured=True
            ),
            Room(
                name='Presidential Suite',
                room_type='Presidential',
                description='The pinnacle of luxury at Hotel Miramar SG. Private terrace, dining room, two bedrooms, and dedicated butler.',
                price_per_night=980.00,
                max_occupancy=4,
                amenities='Private Terrace, Dining Room, Two Bedrooms, Full Butler Service, Jacuzzi, Wine Cellar Access, Airport Transfer',
                image_filename='presidential-suite.jpg',
                is_available=True,
                is_featured=False
            ),
            Room(
                name='Garden Courtyard Standard',
                room_type='Standard',
                description='Quiet courtyard-facing standard room with soft natural light, twin beds option, and calming garden views.',
                price_per_night=165.00,
                max_occupancy=2,
                amenities='Free Wi-Fi, Air Conditioning, Flat-screen TV, Mini Fridge, En-suite Bathroom',
                image_filename='garden-courtyard-standard.jpg',
                is_available=True,
                is_featured=False
            ),
        ]
        for r in rooms:
            db.session.add(r)
        print(f'[seed] {len(rooms)} sample rooms added')

    # Sample feedback
    if Feedback.query.count() == 0:
        samples = [
            Feedback(name='Sarah Chen', email='sarah@example.com', rating=5,
                     message='Absolutely stunning stay. The Marina Deluxe exceeded expectations and the staff were impeccable.', is_approved=True),
            Feedback(name='James Wong', email='james@example.com', rating=5,
                     message='Best location in Singapore. Walking distance to everything yet peaceful. Will return.', is_approved=True),
            Feedback(name='Priya Nair', email='priya@example.com', rating=4,
                     message='Beautiful rooms and excellent breakfast. Highly recommend the Executive Suite.', is_approved=True),
        ]
        for f in samples:
            db.session.add(f)
        print('[seed] Sample feedback added')

    db.session.commit()
    print('[seed] Database ready.')


@app.cli.command('init-db')
def init_db():
    """Initialize database tables and seed demo data (development only)."""
    seed_database()


# Auto-seed when the app starts
with app.app_context():
    try:
        db.create_all()
        if app.config.get('SEED_DEMO_DATA'):
            seed_database()
    except Exception as e:
        print(f'[seed] Warning: {e}')


if __name__ == '__main__':
    import os
    port = int(os.environ.get('HOTEL_PORT', '5000'))
    app.run(host='0.0.0.0', port=port, debug=True)
