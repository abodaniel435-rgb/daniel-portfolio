"""Bootstrap the project environment and start the local Flask app.

Zero-stress launcher: creates .venv, installs requirements, opens browser.
Usage: python scripts/dev_server.py [--open-browser] [--port 5000]
"""

from __future__ import annotations

import argparse
import hashlib
import os
import shutil
import socket
import subprocess
import sys
import threading
import time
import webbrowser
from pathlib import Path
from urllib.error import URLError
from urllib.request import urlopen


PROJECT_ROOT = Path(__file__).resolve().parents[1]
VENV_DIR = PROJECT_ROOT / ".venv"
REQUIREMENTS = PROJECT_ROOT / "requirements.txt"
ENV_FILE = PROJECT_ROOT / ".env"
ENV_EXAMPLE = PROJECT_ROOT / ".env.example"
PORT = 5000


def venv_python() -> Path:
    if os.name == "nt":
        return VENV_DIR / "Scripts" / "python.exe"
    return VENV_DIR / "bin" / "python"


def ensure_env_file() -> None:
    if ENV_FILE.exists() or not ENV_EXAMPLE.exists():
        return
    try:
        shutil.copyfile(ENV_EXAMPLE, ENV_FILE)
        print("Created .env from .env.example (safe local defaults).", flush=True)
    except OSError as exc:
        print(f"Warning: could not create .env: {exc}", flush=True)


def ensure_environment() -> Path:
    interpreter = venv_python()
    if not interpreter.exists():
        print(f"First run: creating .venv (one-time, ~1-2 min)...", flush=True)
        subprocess.run(
            [sys.executable, "-m", "venv", str(VENV_DIR)],
            cwd=PROJECT_ROOT,
            check=True,
        )
    else:
        # Copied folder / different laptop / upgraded Python can leave a broken venv.
        # If the venv python cannot run or lacks Flask, rebuild it automatically.
        try:
            check = subprocess.run(
                [str(interpreter), "-c", "import sys, flask; print('venv-ok')"],
                cwd=PROJECT_ROOT,
                capture_output=True,
                text=True,
                timeout=15,
            )
            if check.returncode != 0 or "venv-ok" not in (check.stdout or ""):
                raise RuntimeError("venv broken")
        except Exception:
            print("Detected broken/copied .venv — rebuilding automatically...", flush=True)
            try:
                shutil.rmtree(VENV_DIR, ignore_errors=True)
            except Exception:
                pass
            subprocess.run(
                [sys.executable, "-m", "venv", str(VENV_DIR)],
                cwd=PROJECT_ROOT,
                check=True,
            )
            interpreter = venv_python()

    requirements_hash = hashlib.sha256(REQUIREMENTS.read_bytes()).hexdigest()
    hash_file = VENV_DIR / ".requirements.sha256"
    installed_hash = hash_file.read_text(encoding="utf-8").strip() if hash_file.exists() else ""
    if installed_hash != requirements_hash:
        print("Installing project requirements...", flush=True)
        subprocess.run(
            [str(interpreter), "-m", "pip", "install", "-r", str(REQUIREMENTS)],
            cwd=PROJECT_ROOT,
            check=True,
        )
        hash_file.write_text(requirements_hash, encoding="utf-8")

    return interpreter


def hotel_server_is_running(port: int = PORT) -> bool:
    with socket.socket(socket.AF_INET, socket.SOCK_STREAM) as probe:
        probe.settimeout(1)
        if probe.connect_ex(("127.0.0.1", port)) != 0:
            return False

    try:
        with urlopen(f"http://127.0.0.1:{port}/", timeout=3) as response:
            page = response.read(200_000).decode("utf-8", errors="replace")
            if response.status == 200 and "Hotel Miramar SG" in page:
                return True
    except URLError as error:
        raise RuntimeError(f"Port {port} is occupied but the hotel page did not respond: {error}") from error

    raise RuntimeError(
        f"Port {port} is already serving a different page. Close that process before starting Hotel Miramar."
    )


def open_browser_when_ready(port: int) -> None:
    def _wait_and_open():
        time.sleep(1.0)
        for _ in range(60):
            try:
                with urlopen(f"http://127.0.0.1:{port}/", timeout=2) as r:
                    if r.status == 200:
                        break
            except Exception:
                time.sleep(0.5)
        try:
            webbrowser.open(f"http://127.0.0.1:{port}")
        except Exception:
            pass
    threading.Thread(target=_wait_and_open, daemon=True).start()


def main() -> int:
    parser = argparse.ArgumentParser(description="Start Hotel Miramar SG.")
    parser.add_argument("--open-browser", action="store_true")
    parser.add_argument("--port", type=int, default=PORT)
    args = parser.parse_args()
    port = args.port
    try:
        ensure_env_file()
        interpreter = ensure_environment()
        if hotel_server_is_running(port):
            print(f"Hotel Miramar is already running at http://127.0.0.1:{port}", flush=True)
            if args.open_browser:
                try:
                    webbrowser.open(f"http://127.0.0.1:{port}")
                except Exception:
                    pass
            return 0

        print("==============================================", flush=True)
        print("  Hotel Miramar SG running at:", flush=True)
        print(f"  http://127.0.0.1:{port}  <- Ctrl+Click to open", flush=True)
        print("  Press Ctrl+C to stop", flush=True)
        print("==============================================", flush=True)
        if args.open_browser:
            open_browser_when_ready(port)
        environment = os.environ.copy()
        environment.setdefault("FLASK_ENV", "development")
        environment["HOTEL_PORT"] = str(port)
        return subprocess.call(
            [str(interpreter), str(PROJECT_ROOT / "run.py")],
            cwd=PROJECT_ROOT,
            env=environment,
        )
    except subprocess.CalledProcessError as error:
        print(f"Environment setup failed with exit code {error.returncode}.", file=sys.stderr, flush=True)
        return error.returncode
    except (OSError, RuntimeError) as error:
        print(f"Hotel Miramar could not start: {error}", file=sys.stderr, flush=True)
        return 1
    except KeyboardInterrupt:
        print("\nHotel Miramar startup cancelled.", flush=True)
        return 130


if __name__ == "__main__":
    raise SystemExit(main()) 