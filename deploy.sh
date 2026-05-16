#!/bin/bash
#
# Deploy script para producao (Digital Ocean droplet).
# Uso: ./deploy.sh
#
# Pressupoe: git pull ja foi feito, ou sera feito aqui.

set -euo pipefail

COMPOSE_FILE="docker-compose.prod.yml"

echo "==> Puxando ultima versao do repositorio..."
git pull origin main

echo "==> Rebuildando imagem da aplicacao..."
docker compose -f "$COMPOSE_FILE" build app

echo "==> Subindo containers (db + app) com recriacao da app..."
docker compose -f "$COMPOSE_FILE" up -d --force-recreate app

echo "==> Garantindo migrations e caches apos deploy..."
docker compose -f "$COMPOSE_FILE" exec -T app php artisan migrate --force
docker compose -f "$COMPOSE_FILE" exec -T app php artisan optimize:clear
docker compose -f "$COMPOSE_FILE" exec -T app php artisan config:cache
docker compose -f "$COMPOSE_FILE" exec -T app php artisan route:cache
docker compose -f "$COMPOSE_FILE" exec -T app php artisan view:cache

echo "==> Limpando imagens antigas..."
docker image prune -f

echo "==> Status dos containers:"
docker compose -f "$COMPOSE_FILE" ps

echo ""
echo "==> Deploy finalizado. Logs: docker compose -f $COMPOSE_FILE logs -f app"
