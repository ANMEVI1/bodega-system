#!/bin/sh
set -eu

mkdir -p \
    storage/app/private \
    storage/app/public \
    storage/framework/cache/data \
    storage/framework/sessions \
    storage/framework/views \
    storage/logs \
    bootstrap/cache

composer install --no-interaction --prefer-dist

if [ ! -s storage/app/.docker-app-key ]; then
    php artisan key:generate --show > storage/app/.docker-app-key
fi

export APP_KEY="$(cat storage/app/.docker-app-key)"
mkdir -p "$(dirname "$DB_DATABASE")"
touch "$DB_DATABASE"

php artisan migrate --force --seed

exec php artisan serve --host=0.0.0.0 --port=8000
