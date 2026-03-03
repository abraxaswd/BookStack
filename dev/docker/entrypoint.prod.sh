#!/bin/bash

set -e

if [[ -n "$1" ]]; then
    exec "$@"
else
    wait-for-it "${DB_HOST:-db}:${DB_PORT:-3306}" -t 45
    php artisan migrate --database=mysql --force
    chown -R www-data storage public/uploads bootstrap/cache
    exec apache2-foreground
fi
