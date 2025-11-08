from flask import Blueprint, render_template, request, g, redirect, url_for
from auth import require_auth
import sqlite3, pathlib, os

APP_DIR = pathlib.Path(__file__).resolve().parents[1]
DB_FILE = APP_DIR / "db" / "oopsy.db"

pharmacist_bp = Blueprint("pharmacist", __name__, url_prefix="/pharmacist")

def get_db():
    conn = sqlite3.connect(str(DB_FILE))
    conn.row_factory = sqlite3.Row
    return conn

@pharmacist_bp.route("/dashboard")
@require_auth
def dashboard():
    # Only pharmacists should use this UI in real app; the lab doesn't enforce role checks strictly here.
    return render_template("pharmacist_dashboard.html")

@pharmacist_bp.route("/query")
@require_auth
def query_drugs():
    # Vulnerable SQL: using string formatting rather than parameterized queries.
    q = request.args.get("q", "")
    conn = get_db()
    # Dangerous: this is vulnerable to SQL injection.
    sql = f"SELECT id, name, stock FROM drugs WHERE name LIKE '%{q}%'"
    try:
        cur = conn.execute(sql)
        rows = cur.fetchall()
    except Exception as e:
        return f"SQL error: {e}", 500
    return render_template("drug_query.html", results=rows, q=q)

@pharmacist_bp.route("/drug")
@require_auth
def drug_details():
    # Local File Inclusion style behavior: dangerous read based on user-provided filename.
    fname = request.args.get("file", "")
    # Intentionally vulnerable: no sanitization, can read many files if path traversal used.
    try:
        # Allow reading absolute paths too for lab clarity
        with open(fname, "r", encoding="utf-8", errors="ignore") as f:
            content = f.read()
    except Exception as e:
        content = f"Error reading file: {e}"
    return render_template("drug_details.html", content=content, filename=fname)

@pharmacist_bp.route("/chat", methods=["GET", "POST"])
@require_auth
def chat():
    conn = get_db()
    if request.method == "POST":
        author = g.user["sub"]
        message = request.form.get("message", "")
        # store message raw (stored XSS possible)
        conn.execute("INSERT INTO messages (author, message, created_at) VALUES (?, ?, datetime('now'))", (author, message))
        conn.commit()
        return redirect(url_for("pharmacist.chat"))
    cur = conn.execute("SELECT id, author, message, created_at FROM messages ORDER BY created_at DESC")
    rows = cur.fetchall()
    return render_template("pharmacist_chat.html", messages=rows)
