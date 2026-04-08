#!/usr/bin/env bash
set -e
cd "$(dirname "$0")"

echo "==> Pull latest (if git repo)..."
git pull origin main 2>/dev/null || true

echo "==> Build images..."
docker compose -f docker-compose.yaml -f docker-compose.prod.yml build --no-cache

echo "==> Start containers..."
docker compose -f docker-compose.yaml -f docker-compose.prod.yml up -d

echo "==> Wait for app to be ready..."
sleep 5

echo "==> Run migrations..."
docker compose -f docker-compose.yaml -f docker-compose.prod.yml run --rm app php bin/console doctrine:migrations:migrate --no-interaction

echo "==> Clear prod cache..."
docker compose -f docker-compose.yaml -f docker-compose.prod.yml run --rm app php bin/console cache:clear --env=prod

echo ""
echo "=== Deploy done. Check: curl http://localhost:8081/api/v1/health ==="
