#!/usr/bin/env bash
# Instale na VPS: cp deploy/vps-deploy.sh /opt/portalcandidato/deploy.sh && chmod +x /opt/portalcandidato/deploy.sh
set -euo pipefail

APP_DIR=/opt/portalcandidato/app
COMPOSE="docker compose --env-file .env -f docker/compose.prod.yml"

cd "$APP_DIR"

git fetch origin
git checkout main
git pull --ff-only

$COMPOSE build app queue scheduler
$COMPOSE run --rm app php artisan migrate --force
$COMPOSE up -d --remove-orphans

# public/ fica em volume persistente; copia assets do build atual da imagem
$COMPOSE exec -T app sh -c 'cp -a /opt/public-seed/. /var/www/html/public/ && chown -R www-data:www-data /var/www/html/public'

$COMPOSE exec -T app php artisan storage:link --force --no-interaction
$COMPOSE exec -T app php artisan config:cache
$COMPOSE exec -T app php artisan route:cache
$COMPOSE exec -T app php artisan view:cache

docker image prune -f
