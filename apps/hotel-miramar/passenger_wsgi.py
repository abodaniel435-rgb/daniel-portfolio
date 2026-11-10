"""cPanel / Passenger entry point for Hotel Miramar SG.
cPanel Setup Python App expects `passenger_wsgi.py` with `application` callable.
Place this file in the application root on cPanel (same folder as run.py / config.py).
"""
import os
import sys

BASE_DIR = os.path.dirname(__file__)
if BASE_DIR not in sys.path:
    sys.path.insert(0, BASE_DIR)

# Default to production on hosting. Override in cPanel env if needed.
os.environ.setdefault('FLASK_ENV', 'production')

from app import create_app  # noqa: E402

application = create_app(os.environ.get('FLASK_ENV', 'production'))

if __name__ == '__main__':
    application.run()
