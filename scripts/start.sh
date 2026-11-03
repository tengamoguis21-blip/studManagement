#!/usr/bin/env bash
set -euo pipefail
cd "$(dirname "$0")/.."
test -f vendor/autoload.php || exit 0
export XDEBUG_MODE=off
unset PHP_CLI_SERVER_WORKERS
php scripts/configure-codespace.php
php artisan config:clear
if ! php -r '$socket = @fsockopen("127.0.0.1", 8000); if ($socket) { fclose($socket); exit(0); } exit(1);'; then
    # Use Laravel's own development router directly, without an intermediate
    # Artisan process tied to the devcontainer lifecycle shell.
    (cd public && setsid -f php -S 0.0.0.0:8000 ../vendor/laravel/framework/src/Illuminate/Foundation/resources/server.php >../storage/logs/server.log 2>&1 </dev/null)
    echo 'Started Student Management on port 8000.'
else
    echo 'Student Management is already listening on port 8000.'
fi
for attempt in {1..30}; do
    if curl --max-time 2 --silent --fail http://127.0.0.1:8000/up >/dev/null; then
        echo 'Student Management health check passed.'
        exit 0
    fi
    sleep 1
done
tail -20 storage/logs/server.log
echo 'Student Management failed to become ready.' >&2
exit 1