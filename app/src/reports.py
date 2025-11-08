# app/src/reports.py
from flask import Blueprint, render_template, g, request
from auth import require_auth
import sqlite3
import pathlib
import os

APP_DIR = pathlib.Path(__file__).resolve().parents[1]
DB_FILE = APP_DIR / "db" / "oopsy.db"

reports_bp = Blueprint("reports", __name__)

def get_db():
    conn = sqlite3.connect(str(DB_FILE))
    conn.row_factory = sqlite3.Row
    return conn

@reports_bp.route("/reports")
@require_auth
def list_reports():
    conn = get_db()
    role = g.user["role"]
    username = g.user["sub"]

    # Render dashboard according to role
    if role == "patient":
        # Show own reports
        cur = conn.execute("SELECT id FROM users WHERE username=?", (username,))
        user_row = cur.fetchone()
        if not user_row:
            return "User not found", 404
        user_id = user_row[0]
        cur = conn.execute("SELECT id, title, created_at FROM reports WHERE owner_id=?", (user_id,))
        rows = cur.fetchall()
        return render_template("patient_dashboard.html", reports=rows, user=username)

    elif role == "doctor":
        return render_template("doctor_dashboard.html", user=username)

    elif role == "pharmacist":
        return render_template("pharmachist_dashboard.html", user=username)

    else:
        return "Unknown role", 403

@reports_bp.route("/report")
@require_auth
def view_report():
    # IDOR intentional: returns any report by id without checking ownership
    report_id = request.args.get("id")
    if not report_id:
        return "Missing report id", 400

    conn = get_db()
    # Join with users to get owner metadata (id, username, name, surname, email)
    cur = conn.execute("""
        SELECT r.id AS id,
               r.title AS title,
               r.content AS content,
               r.created_at AS created_at,
               u.id AS owner_id,
               u.username AS owner_username,
               u.name AS owner_name,
               u.surname AS owner_surname,
               u.email AS owner_email
        FROM reports r
        JOIN users u ON r.owner_id = u.id
        WHERE r.id = ?
    """, (report_id,))
    row = cur.fetchone()
    if not row:
        return "Report not found", 404

    owner_full = None
    if row["owner_name"] or row["owner_surname"]:
        owner_full = f"{row['owner_name'] or ''} {row['owner_surname'] or ''}".strip()
    else:
        owner_full = row["owner_username"]

    return render_template(
        "report_view.html",
        report_id=row["id"],
        title=row["title"],
        content=row["content"],
        created_at=row["created_at"],
        owner_id=row["owner_id"],
        owner_username=row["owner_username"],
        owner_full=owner_full,
        owner_email=row["owner_email"]
    )
