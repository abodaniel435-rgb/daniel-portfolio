"""Campus Coin project package.

Shared hosting on cPanel rarely has a C compiler, so ``mysqlclient`` cannot be
built there. PyMySQL is a pure-Python MySQL driver and Django's MySQL backend
imports ``MySQLdb``, which PyMySQL provides through ``install_as_MySQLdb()``.

The shim is installed only when the project is actually configured for MySQL,
so the SQLite fallback used for local testing is left completely untouched.
If ``mysqlclient`` happens to be installed instead, PyMySQL is simply not
present and this block is skipped.
"""

import os

_TRUE = {"1", "true", "yes", "on"}


def _using_mysql() -> bool:
    if os.getenv("USE_SQLITE", "false").strip().lower() in _TRUE:
        return False
    return os.getenv("DB_ENGINE", "mysql").strip().lower().startswith("mysql")


if _using_mysql():
    try:
        import pymysql
    except ImportError:  # pragma: no cover - mysqlclient is in use instead
        pass
    else:
        pymysql.install_as_MySQLdb()
