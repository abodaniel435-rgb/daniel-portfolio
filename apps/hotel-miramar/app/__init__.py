"""
Hotel Miramar SG - Application Factory
"""
import os
from flask import Flask
from flask_sqlalchemy import SQLAlchemy
from flask_login import LoginManager
from flask_migrate import Migrate
from flask_wtf.csrf import CSRFProtect
from config import config

db = SQLAlchemy()
login_manager = LoginManager()
migrate = Migrate()
csrf = CSRFProtect()

login_manager.login_view = 'auth.login'
login_manager.login_message_category = 'info'
login_manager.login_message = 'Please log in to access this page.'


def create_app(config_name='default'):
    app = Flask(__name__)
    app.config.from_object(config[config_name])

    if config_name == 'production' and app.config['SECRET_KEY'] == 'miramar-sg-dev-secret-key-change-in-production':
        raise RuntimeError('Set SECRET_KEY before running Hotel Miramar SG in production.')

    # Ensure upload folder exists
    os.makedirs(app.config['UPLOAD_FOLDER'], exist_ok=True)
    os.makedirs(os.path.join(app.root_path, 'static', 'images', 'rooms'), exist_ok=True)
    os.makedirs(os.path.join(app.root_path, 'static', 'images', 'slider'), exist_ok=True)

    db.init_app(app)
    login_manager.init_app(app)
    migrate.init_app(app, db)
    csrf.init_app(app)

    # Register blueprints
    from app.routes.public import public_bp
    from app.routes.auth import auth_bp
    from app.routes.customer import customer_bp
    from app.routes.admin import admin_bp

    app.register_blueprint(public_bp)
    app.register_blueprint(auth_bp, url_prefix='/auth')
    app.register_blueprint(customer_bp, url_prefix='/customer')
    app.register_blueprint(admin_bp, url_prefix='/admin')

    # Context processors
    @app.context_processor
    def inject_hotel_info():
        return {
            'hotel_name': app.config['HOTEL_NAME'],
            'hotel_tagline': app.config['HOTEL_TAGLINE'],
            'hotel_email': app.config['HOTEL_EMAIL'],
            'hotel_phone': app.config['HOTEL_PHONE'],
            'hotel_address': app.config['HOTEL_ADDRESS'],
        }

    @app.after_request
    def apply_security_headers(response):
        """Low-risk browser protections that work with the existing frontend."""
        response.headers.setdefault('X-Content-Type-Options', 'nosniff')
        response.headers.setdefault('X-Frame-Options', 'SAMEORIGIN')
        response.headers.setdefault('Referrer-Policy', 'strict-origin-when-cross-origin')
        response.headers.setdefault('Permissions-Policy', 'camera=(), microphone=(), geolocation=()')
        if config_name == 'production':
            response.headers.setdefault('Strict-Transport-Security', 'max-age=31536000; includeSubDomains')
        return response

    # Error handlers
    @app.errorhandler(404)
    def not_found(e):
        from flask import render_template
        return render_template('errors/404.html'), 404

    @app.errorhandler(500)
    def server_error(e):
        from flask import render_template
        return render_template('errors/500.html'), 500

    return app
