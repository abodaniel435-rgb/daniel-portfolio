"""cPanel / Passenger entry point for Campus Coin.

cPanel "Setup Python App" expects a `passenger_wsgi.py` in the application root
exposing a WSGI callable named `application`.
"""
import os
import sys

BASE_DIR = os.path.dirname(os.path.abspath(__file__))
if BASE_DIR not in sys.path:
    sys.path.insert(0, BASE_DIR)

os.environ.setdefault('DJANGO_SETTINGS_MODULE', 'config.settings')

# Shared hosting rarely has MySQL client headers to compile `mysqlclient`
# against; PyMySQL is a pure-Python drop-in.
try:
    import pymysql

    pymysql.install_as_MySQLdb()
except ImportError:
    pass

from django.core.wsgi import get_wsgi_application  # noqa: E402

application = get_wsgi_application()
