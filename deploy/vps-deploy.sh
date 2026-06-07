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

$COMPOSE exec -T app php artisan storage:link --force --no-interaction
$COMPOSE exec -T app php artisan config:cache
$COMPOSE exec -T app php artisan route:cache
$COMPOSE exec -T app php artisan view:cache

docker image prune -f
