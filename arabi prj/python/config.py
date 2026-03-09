"""Read DB credentials from environment (set by PHP/Docker)."""
import os

DB_CONFIG = {
    "host": os.environ.get("DB_HOST", "mysql"),
    "port": int(os.environ.get("DB_PORT", "3306")),
    "database": os.environ.get("DB_NAME", "smartrecruit"),
    "user": os.environ.get("DB_USER", "smartrecruit"),
    "password": os.environ.get("DB_PASS", "smartrecruit_secret"),
}

DEFAULT_CSV_PATH = os.environ.get("CSV_DATASET_PATH", "/var/www/html/dataset_cvs_5000.csv")
