#!/usr/bin/env python3
# app/src/init_db.py
"""
Initialize the SQLite database for OopsyHealth.

Behaviour:
- If the DB file already exists, it will be removed (fresh start).
- Then schema.sql and seed.sql are executed to create a fresh database.
- Prints status messages for visibility in container logs.
"""

import sqlite3
import pathlib
import sys
import os
import traceback

DB_DIR = pathlib.Path("/app/db")
DB_PATH = DB_DIR / "oopsy.db"
SCHEMA_PATH = DB_DIR / "schema.sql"
SEED_PATH = DB_DIR / "seed.sql"

def abort(msg: str, code: int = 1):
    print("ERROR:", msg, file=sys.stderr)
    sys.exit(code)

def ensure_paths():
    if not DB_DIR.exists():
        try:
            DB_DIR.mkdir(parents=True, exist_ok=True)
            print(f"Created DB directory: {DB_DIR}")
        except Exception as e:
            abort(f"Could not create DB directory {DB_DIR}: {e}")

    if not SCHEMA_PATH.exists():
        abort(f"Schema file not found at {SCHEMA_PATH}")
    if not SEED_PATH.exists():
        abort(f"Seed file not found at {SEED_PATH}")

def remove_existing_db():
    if DB_PATH.exists():
        try:
            DB_PATH.unlink()
            print(f"Removed existing database: {DB_PATH}")
        except Exception as e:
            abort(f"Failed to remove existing database {DB_PATH}: {e}")

def create_db():
    try:
        print("Initializing new database...")
        conn = sqlite3.connect(str(DB_PATH))
        with open(SCHEMA_PATH, "r", encoding="utf-8") as f:
            schema_sql = f.read()
        with open(SEED_PATH, "r", encoding="utf-8") as f:
            seed_sql = f.read()

        cur = conn.cursor()
        cur.executescript(schema_sql)
        cur.executescript(seed_sql)
        conn.commit()
        conn.close()
        print(f"Database initialized at {DB_PATH}")
    except Exception as e:
        print("Exception during DB initialization:", file=sys.stderr)
        traceback.print_exc()
        abort("Database initialization failed")

def main():
    ensure_paths()
    remove_existing_db()
    create_db()

if __name__ == "__main__":
    main()
