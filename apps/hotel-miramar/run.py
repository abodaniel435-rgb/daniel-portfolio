"""
Hotel Miramar SG - Application Entry Point
Auto-creates database tables and sample data on first run.
"""
import os

from app import create_app
from app.bootstrap import ensure_database, seed_database

app = create_app(os.environ.get('FLASK_ENV', 'development'))


@app.cli.command('init-db')
def init_db():
    """Initialize database tables and seed demo data (development only)."""
    with app.app_context():
        seed_database(app)


ensure_database(app)


if __name__ == '__main__':
    port = int(os.environ.get('HOTEL_PORT', '5000'))
    app.run(host='0.0.0.0', port=port, debug=True)
