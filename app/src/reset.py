from flask import Blueprint, render_template, request, redirect, url_for
import sqlite3, pathlib, secrets, datetime

APP_DIR = pathlib.Path(__file__).resolve().parents[1]
DB_FILE = APP_DIR / "db" / "oopsy.db"

reset_bp = Blueprint("reset", __name__, url_prefix="/reset")

def get_db():
    conn = sqlite3.connect(str(DB_FILE))
    conn.row_factory = sqlite3.Row
    return conn

@reset_bp.route("/request", methods=["GET", "POST"])
def request_reset():
    if request.method == "GET":
        return render_template("reset_request.html")
    email = request.form.get("email", "").strip().lower()
    if not email:
        return "Email required", 400

    conn = get_db()
    # find user by email
    cur = conn.execute("SELECT id, username FROM users WHERE email=?", (email,))
    user = cur.fetchone()
    # create a token regardless of whether user exists (mirrors some real systems),
    # but only insert inbox message if user exists.
    import secrets, datetime
    token = secrets.token_urlsafe(16)
    expiry = (datetime.datetime.utcnow() + datetime.timedelta(minutes=15)).isoformat()
    conn.execute("INSERT INTO reset_tokens (token, expiry) VALUES (?, ?)", (token, expiry))
    if user:
        owner_id = user["id"]
        subject = "Password reset token"
        content = f"Your password reset token is: {token}\nIt expires at {expiry} (UTC)."
        conn.execute("INSERT INTO inbox (owner_id, subject, content, sent_at) VALUES (?, ?, ?, datetime('now'))", (owner_id, subject, content))
    conn.commit()
    # For privacy, do not echo the token on the web page; instruct user to check inbox.
    return render_template("reset_requested_inbox.html")

@reset_bp.route("/use", methods=["GET", "POST"])
def use_token():
    if request.method == "GET":
        return render_template("reset_use.html")
    token = request.form.get("token")
    # Validate token exists and not expired (but not bound to user)
    conn = get_db()
    cur = conn.execute("SELECT token, expiry FROM reset_tokens WHERE token=?", (token,))
    row = cur.fetchone()
    if not row:
        return "Invalid token", 400
    expiry = datetime.datetime.fromisoformat(row[1])
    if expiry < datetime.datetime.utcnow():
        return "Token expired", 400
    # Token valid. Ask for OTP (3 digits)
    # Generate an OTP and store it for the email the user provides next step (simulated send)
    # For simplicity, we create OTP here and show it in the next page.
    otp = f"{secrets.randbelow(1000):03d}"  # 3-digit OTP
    # store OTP with the 'email' provided in the form (simulate sending)
    email = request.form.get("email", "")
    conn.execute("INSERT OR REPLACE INTO otps (email, code, expiry) VALUES (?, ?, ?)", (email, otp, (datetime.datetime.utcnow() + datetime.timedelta(minutes=5)).isoformat()))
    conn.commit()
    return render_template("reset_otp.html", otp=otp, token=token, email=email)

@reset_bp.route("/confirm", methods=["POST"])
def confirm_reset():
    token = request.form.get("token")
    email = request.form.get("email")
    otp = request.form.get("otp")
    new_password = request.form.get("new_password")
    conn = get_db()
    cur = conn.execute("SELECT code, expiry FROM otps WHERE email=?", (email,))
    row = cur.fetchone()
    if not row:
        return "No OTP for that email", 400
    if row[0] != otp:
        return "Invalid OTP", 400
    expiry = datetime.datetime.fromisoformat(row[1])
    if expiry < datetime.datetime.utcnow():
        return "OTP expired", 400
    # Vulnerability: token was not bound to a specific user. For demo, allow password reset for any account by email.
    # Find user by email and update password.
    conn.execute("UPDATE users SET password=? WHERE email=?", (new_password, email))
    conn.commit()
    return "Password updated (lab simulation). You can now login with the new password."
