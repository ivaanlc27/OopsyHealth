# app/src/inbox.py
from flask import Blueprint, render_template, g, redirect, url_for
from auth import require_auth
import sqlite3, pathlib

APP_DIR = pathlib.Path(__file__).resolve().parents[1]
DB_FILE = APP_DIR / "db" / "oopsy.db"

inbox_bp = Blueprint("inbox", __name__, url_prefix="/inbox")

def get_db():
    conn = sqlite3.connect(str(DB_FILE))
    conn.row_factory = sqlite3.Row
    return conn

@inbox_bp.route("/")
@require_auth
def list_inbox():
    username = g.user["sub"]
    conn = get_db()
    cur = conn.execute("SELECT id FROM users WHERE username=?", (username,))
    user = cur.fetchone()
    if not user:
        return "User not found", 404
    user_id = user["id"]
    cur = conn.execute("SELECT id, subject, sent_at FROM inbox WHERE owner_id=? ORDER BY sent_at DESC", (user_id,))
    rows = cur.fetchall()
    return render_template("inbox_list.html", messages=rows, user=username)

@inbox_bp.route("/view/<int:msg_id>")
@require_auth
def view_message(msg_id):
    username = g.user["sub"]
    conn = get_db()
    # Ensure message belongs to user (basic check)
    cur = conn.execute("""
        SELECT i.id, i.subject, i.content, i.sent_at
        FROM inbox i
        JOIN users u ON i.owner_id = u.id
        WHERE i.id = ? AND u.username = ?
    """, (msg_id, username))
    msg = cur.fetchone()
    if not msg:
        return "Message not found or access denied", 404
    return render_template("inbox_view.html", message=msg)
