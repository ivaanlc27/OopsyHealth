from flask import Blueprint, request, redirect, url_for, render_template, make_response, g
import sqlite3
import os
import pathlib
import jwt
import datetime
import bcrypt
from functools import wraps

# ----------------------
# Configuration
# ----------------------
auth_bp = Blueprint("auth", __name__, url_prefix="/auth")
APP_DIR = pathlib.Path(__file__).resolve().parents[1]
DB_FILE = APP_DIR / "db" / "oopsy.db"
JWT_SECRET = os.getenv("OOPSY_JWT_SECRET")

# ----------------------
# Database helper
# ----------------------
def get_db():
    conn = sqlite3.connect(str(DB_FILE))
    conn.row_factory = sqlite3.Row
    return conn

# ----------------------
# Login route
# ----------------------
@auth_bp.route("/login", methods=["GET", "POST"])
def login():
    if request.method == "GET":
        return render_template("landing.html", error=None)

    username = request.form.get("username", "").strip()
    password = request.form.get("password", "")

    conn = get_db()
    cur = conn.execute(
        "SELECT id, username, role, password_hash FROM users WHERE username=?",
        (username,)
    )
    row = cur.fetchone()
    if not row:
        return render_template("landing.html", error="Invalid credentials")

    stored_hash = row["password_hash"]
    if not stored_hash:
        return render_template("landing.html", error="Account has no password set")

    try:
        if not bcrypt.checkpw(password.encode('utf-8'), stored_hash.encode('utf-8')):
            return render_template("landing.html", error="Invalid credentials")
    except Exception:
        return render_template("landing.html", error="Authentication error")

    # Password correct -> issue JWT
    payload = {
        "sub": row["username"],
        "role": row["role"],
        "iat": datetime.datetime.utcnow().timestamp(),
        "exp": (datetime.datetime.utcnow() + datetime.timedelta(hours=2)).timestamp()
    }
    token = jwt.encode(payload, JWT_SECRET, algorithm="HS256")

    # Redirect to dashboard according to role
    role_dashboard = {
        "patient": "reports.list_reports",
        "doctor": "doctor.panel",
        "pharmacist": "pharmacist.dashboard"
    }
    redirect_endpoint = role_dashboard.get(row["role"], "reports.list_reports")

    resp = make_response(redirect(url_for(redirect_endpoint)))
    resp.set_cookie("oopsy_jwt", token)
    return resp

# ----------------------
# Logout route
# ----------------------
@auth_bp.route("/logout")
def logout():
    resp = make_response(redirect(url_for("auth.login")))
    resp.set_cookie("oopsy_jwt", "", expires=0)
    return resp

# ----------------------
# Auth decorator
# ----------------------
def require_auth(f):
    @wraps(f)
    def inner(*args, **kwargs):
        token = request.cookies.get("oopsy_jwt")
        if not token:
            return redirect(url_for("auth.login"))
        try:
            data = jwt.decode(token, JWT_SECRET, algorithms=["HS256"])
        except Exception:
            return redirect(url_for("auth.login"))
        g.user = data  # stores username and role
        return f(*args, **kwargs)
    return inner
