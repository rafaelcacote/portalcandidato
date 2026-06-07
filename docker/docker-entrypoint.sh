#!/bin/sh
set -e

if [ ! -f /var/www/html/public/build/manifest.json ]; then
    cp -a /opt/public-seed/. /var/www/html/public/
fi

chown -R www-data:www-data storage bootstrap/cache public
chmod -R 775 storage bootstrap/cache

# Symlink public/storage → storage/app/public (fotos de candidatos em /storage/...)
php artisan storage:link --force --no-interaction

exec "$@"
