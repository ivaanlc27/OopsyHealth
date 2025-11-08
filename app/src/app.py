from flask import Flask, render_template, send_from_directory
import os
from auth import auth_bp
from reports import reports_bp
from uploads import uploads_bp
from reset import reset_bp
from pharmacist import pharmacist_bp
from doctor import doctor_bp
from inbox import inbox_bp
import sqlite3
import pathlib
import time

APP_DIR = pathlib.Path(__file__).resolve().parents[1]
DB_DIR = APP_DIR / "db"
DB_FILE = DB_DIR / "oopsy.db"

def init_db():
    """Initialize SQLite DB from db/schema.sql + db/seed.sql if DB file doesn't exist."""
    if DB_FILE.exists():
        return
    DB_DIR.mkdir(exist_ok=True)
    schema_path = pathlib.Path(__file__).resolve().parents[2] / "db" / "schema.sql"
    seed_path = pathlib.Path(__file__).resolve().parents[2] / "db" / "seed.sql"
    import sqlite3
    with sqlite3.connect(str(DB_FILE)) as conn:
        with open(schema_path, "r", encoding="utf-8") as f:
            conn.executescript(f.read())
        with open(seed_path, "r", encoding="utf-8") as f:
            conn.executescript(f.read())
    print("Initialized DB at", DB_FILE)

def create_app():
    init_db()
    app = Flask(__name__, template_folder=str(APP_DIR / "templates"), static_folder=str(APP_DIR / "static"))
    app.register_blueprint(auth_bp)
    app.register_blueprint(reports_bp)
    app.register_blueprint(uploads_bp)
    app.register_blueprint(reset_bp)
    app.register_blueprint(pharmacist_bp)
    app.register_blueprint(doctor_bp)
    app.register_blueprint(inbox_bp)

    @app.route("/")
    def landing():
        return render_template("landing.html")

    # Serve uploaded files directly (intentionally exposed for lab)
    @app.route("/uploads/<path:filename>")
    def uploaded_file(filename):
        uploads_folder = APP_DIR / "uploads"
        return send_from_directory(str(uploads_folder), filename)

    return app

if __name__ == "__main__":
    app = create_app()
    app.run(host="0.0.0.0", port=5000, debug=True)
