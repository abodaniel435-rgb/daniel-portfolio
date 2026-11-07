import os
from dotenv import load_dotenv

# override=True so .env always wins over stale shell env vars
# (e.g. old MAIL_SERVER=smtp.example.com stuck in VS Code terminal).
load_dotenv(override=True)

basedir = os.path.abspath(os.path.dirname(__file__))


def env_bool(name, default=False):
    """Read a conventional true/false environment variable."""
    return os.environ.get(name, str(default)).strip().lower() in {'1', 'true', 'yes', 'on'}


class Config:
    """Base configuration for Hotel Miramar SG application."""
    SECRET_KEY = os.environ.get('SECRET_KEY') or 'miramar-sg-dev-secret-key-change-in-production'
    # Cross-platform SQLite path (Windows + Linux/Mac)
    _db_path = os.path.join(basedir, 'database', 'hotel_miramar.db')
    SQLALCHEMY_DATABASE_URI = os.environ.get('DATABASE_URL') or \
        'sqlite:///' + _db_path.replace('\\', '/')
    SQLALCHEMY_TRACK_MODIFICATIONS = False
    SQLALCHEMY_ENGINE_OPTIONS = {'pool_pre_ping': True}
    UPLOAD_FOLDER = os.path.join(basedir, 'app', 'static', 'uploads')
    MAX_CONTENT_LENGTH = 16 * 1024 * 1024
    ALLOWED_EXTENSIONS = {'png', 'jpg', 'jpeg', 'gif', 'webp'}
    PERMANENT_SESSION_LIFETIME = 3600 * 24
    SESSION_COOKIE_HTTPONLY = True
    SESSION_COOKIE_SAMESITE = 'Lax'
    SESSION_COOKIE_SECURE = False  # Keep local HTTP development usable.

    HOTEL_NAME = 'Hotel Miramar SG'
    HOTEL_TAGLINE = 'Luxury Stay in the Heart of Singapore'
    HOTEL_EMAIL = 'reservations@hotelmiramarsg.com'
    HOTEL_PHONE = '+65 6123 4567'
    HOTEL_ADDRESS = '18 Marina Boulevard, Singapore 018980'
    HOTEL_LAT = 1.2834
    HOTEL_LNG = 103.8607

    # SMTP is intentionally environment-driven: credentials never belong in source control.
    MAIL_SERVER = os.environ.get('MAIL_SERVER')
    MAIL_PORT = int(os.environ.get('MAIL_PORT', '587'))
    MAIL_USERNAME = os.environ.get('MAIL_USERNAME')
    MAIL_PASSWORD = os.environ.get('MAIL_PASSWORD')
    MAIL_USE_TLS = env_bool('MAIL_USE_TLS', True)
    MAIL_USE_SSL = env_bool('MAIL_USE_SSL', False)
    MAIL_DEFAULT_SENDER = os.environ.get('MAIL_DEFAULT_SENDER') or HOTEL_EMAIL
    MAIL_TIMEOUT = int(os.environ.get('MAIL_TIMEOUT', '10'))
    MAIL_SUPPRESS_SEND = env_bool('MAIL_SUPPRESS_SEND', False)
    # Set this to the canonical HTTPS domain in production for email links.
    PUBLIC_BASE_URL = os.environ.get('PUBLIC_BASE_URL', '').rstrip('/')


class DevelopmentConfig(Config):
    DEBUG = True
    SEED_DEMO_DATA = env_bool('SEED_DEMO_DATA', True)


class ProductionConfig(Config):
    DEBUG = False
    SESSION_COOKIE_SECURE = True
    SEED_DEMO_DATA = env_bool('SEED_DEMO_DATA', False)


config = {
    'development': DevelopmentConfig,
    'production': ProductionConfig,
    'default': DevelopmentConfig
}
