#!/usr/bin/env bash
set -euo pipefail
cd "$(dirname "$0")/.."
composer install --no-interaction --prefer-dist
test -f .env || cp .env.example .env
if ! grep -q '^APP_KEY=base64:' .env; then php artisan key:generate --force; fi
php artisan config:clear
php -r 'foreach (["pdo_pgsql","mbstring","xml","curl","zip","intl","bcmath"] as $extension) { if (!extension_loaded($extension)) { fwrite(STDERR, "Missing PHP extension: $extension\n"); exit(1); } }'
bash scripts/start.sh