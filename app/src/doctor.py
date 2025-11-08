from flask import Blueprint, render_template, g
from auth import require_auth
import sqlite3, pathlib
from jinja2 import Template

APP_DIR = pathlib.Path(__file__).resolve().parents[1]
DB_FILE = APP_DIR / "db" / "oopsy.db"

doctor_bp = Blueprint("doctor", __name__, url_prefix="/doctor")

def get_db():
    conn = sqlite3.connect(str(DB_FILE))
    conn.row_factory = sqlite3.Row
    return conn

@doctor_bp.route("/panel")
@require_auth
def panel():
    # List messages; when rendering, we will intentionally evaluate message text as a Jinja2 template.
    conn = get_db()
    cur = conn.execute("SELECT id, author, message, created_at FROM messages ORDER BY created_at DESC")
    msgs = cur.fetchall()
    # Danger: evaluating message via Jinja2 Template - demonstrates SSTI if message contains malicious payload.
    rendered_messages = []
    for m in msgs:
        text = m["message"]
        try:
            # Intentionally vulnerable: render user-provided message as a Jinja2 template.
            rendered = Template(text).render()
        except Exception as e:
            rendered = f"[render error: {e}] {text}"
        rendered_messages.append({"author": m["author"], "rendered": rendered, "when": m["created_at"]})
    return render_template("doctor_dashboard.html", messages=rendered_messages)
