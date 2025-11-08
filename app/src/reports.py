from flask import Blueprint, render_template, g, request
from auth import require_auth
import sqlite3
import pathlib
APP_DIR = pathlib.Path(__file__).resolve().parents[1]
DB_FILE = APP_DIR / "db" / "oopsy.db"

reports_bp = Blueprint("reports", __name__)

def get_db():
    return sqlite3.connect(str(DB_FILE))

@reports_bp.route("/reports")
@require_auth
def list_reports():
    # List reports belonging to the logged-in user.
    conn = get_db()
    username = g.user["sub"]
    cur = conn.execute("SELECT id FROM users WHERE username=?", (username,))
    user_row = cur.fetchone()
    if not user_row:
        return "User not found", 404
    user_id = user_row[0]
    cur = conn.execute("SELECT id, title, created_at FROM reports WHERE owner_id=?", (user_id,))
    rows = cur.fetchall()
    return render_template("patient_dashboard.html", reports=rows, user=username)

@reports_bp.route("/report")
@require_auth
def view_report():
    # IDOR vulnerability: returns any report by id without verifying ownership.
    report_id = request.args.get("id")
    conn = get_db()
    cur = conn.execute("SELECT id, title, content, owner_id FROM reports WHERE id=?", (report_id,))
    row = cur.fetchone()
    if not row:
        return "Report not found", 404
    # Deliberately do NOT check that row['owner_id'] matches current user.
    return render_template("report_view.html", report=row)
