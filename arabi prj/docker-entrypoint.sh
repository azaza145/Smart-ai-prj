#!/bin/sh
set -e
cd /var/www/html
# Install PHP dependencies if vendor is missing or incomplete (e.g. after volume mount)
# Install PDF + Word dependencies (dompdf, phpoffice/phpword) if missing
if [ ! -f vendor/autoload.php ] || [ ! -d vendor/dompdf ] || [ ! -d vendor/phpoffice ]; then
    composer install --no-interaction --prefer-dist --no-dev 2>/dev/null || true
fi
exec "$@"
