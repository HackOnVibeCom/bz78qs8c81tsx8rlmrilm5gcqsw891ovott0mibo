#!/bin/sh
set -eu

cd /var/www/html
mkdir -p storage/logs
php scripts/migrate.php
chown -R www-data:www-data storage

exec "$@"
