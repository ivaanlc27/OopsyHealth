from flask import Blueprint, request, redirect, url_for, render_template, g, current_app
from auth import require_auth
import pathlib, os, shutil, sqlite3, uuid

APP_DIR = pathlib.Path(__file__).resolve().parents[1]
UPLOADS_DIR = APP_DIR / "uploads"
DB_FILE = APP_DIR / "db" / "oopsy.db"
UPLOADS_DIR.mkdir(parents=True, exist_ok=True)

uploads_bp = Blueprint("uploads", __name__, url_prefix="/uploads")

# Server-side blacklist (intentionally weak)
BLACKLIST_EXT = {'.php', '.php3', '.php4', '.php5', '.phtml', '.phar', '.phpt'}

def get_db():
    return sqlite3.connect(str(DB_FILE))

@uploads_bp.route("/new", methods=["GET", "POST"])
@require_auth
def upload_new():
    if request.method == "GET":
        return render_template("upload_form.html")
    f = request.files.get("file")
    if not f:
        return "No file uploaded", 400
    filename = f.filename
    ext = os.path.splitext(filename)[1].lower()
    # Server-side blacklist only (vulnerable): blocks some PHP extensions but allows others like .test or .txt and also .htaccess
    if ext in BLACKLIST_EXT:
        return "File type not allowed", 400
    # Generate a safe-but-predictable filename to store
    new_name = f"{uuid.uuid4().hex}{ext}"
    dest = UPLOADS_DIR / new_name
    f.save(str(dest))
    # store upload record
    conn = get_db()
    # owner_id is resolved from username
    cur = conn.execute("SELECT id FROM users WHERE username=?", (g.user["sub"],))
    owner = cur.fetchone()
    owner_id = owner[0] if owner else None
    conn.execute("INSERT INTO uploads (owner_id, filename, path) VALUES (?, ?, ?)", (owner_id, filename, f"uploads/{new_name}"))
    conn.commit()
    return f"Uploaded to /uploads/{new_name} (original name: {filename})"

@uploads_bp.route("/list")
@require_auth
def list_uploads():
    conn = get_db()
    cur = conn.execute("SELECT id, filename, path, uploaded_at FROM uploads")
    rows = cur.fetchall()
    return render_template("upload_list.html", uploads=rows)
