from flask import Blueprint, request, redirect, url_for, render_template, make_response, g
import sqlite3
import os
import pathlib
import jwt
import datetime

auth_bp = Blueprint("auth", __name__, url_prefix="/auth")
APP_DIR = pathlib.Path(__file__).resolve().parents[1]
DB_FILE = APP_DIR / "db" / "oopsy.db"
JWT_SECRET = os.getenv("OOPSY_JWT_SECRET")

def get_db():
    conn = sqlite3.connect(str(DB_FILE))
    conn.row_factory = sqlite3.Row
    return conn

@auth_bp.route("/login", methods=["GET", "POST"])
def login():
    if request.method == "GET":
        return render_template("landing.html", error=None)
    username = request.form.get("username", "")
    password = request.form.get("password", "")
    conn = get_db()
    cur = conn.execute("SELECT id, username, role FROM users WHERE username=? AND password=?", (username, password))
    row = cur.fetchone()
    if not row:
        return render_template("landing.html", error="Invalid credentials")
    # Issue JWT (HMAC-SHA256)
    payload = {
        "sub": row["username"],
        "role": row["role"],
        "iat": datetime.datetime.utcnow().timestamp(),
        "exp": (datetime.datetime.utcnow() + datetime.timedelta(hours=2)).timestamp()
    }
    token = jwt.encode(payload, JWT_SECRET, algorithm="HS256")
    resp = make_response(redirect(url_for("reports.list_reports")))
    # store JWT in a cookie (no HttpOnly/Secure flags for lab simplicity)
    resp.set_cookie("oopsy_jwt", token)
    return resp

@auth_bp.route("/logout")
def logout():
    resp = make_response(redirect(url_for("landing")))
    resp.set_cookie("oopsy_jwt", "", expires=0)
    return resp

# Simple decorator to read JWT and set g.user
from functools import wraps
from flask import abort

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
        g.user = data  # note: stores role and sub
        return f(*args, **kwargs)
    return inner
