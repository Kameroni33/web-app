#!/usr/bin/env bash
# Deploy the latest main on the production server.
# Usage (on the server, from anywhere): ~/web-app/scripts/deploy.sh
#
# Steps: back up the database, pull main, rebuild the images, restart what
# changed, run migrations, then tidy old images. Safe to re-run.
set -euo pipefail
root="$(cd "$(dirname "$0")/.." && pwd)"
cd "$root"

if [[ -n "$(git status --porcelain --untracked-files=no)" ]]; then
  echo "The server's checkout has local changes; refusing to deploy over them:" >&2
  git status --short --untracked-files=no >&2
  exit 1
fi

if docker compose ps --status running --services | grep -qx postgres; then
  echo "==> Backing up the database first"
  "$root/scripts/backup.sh"
else
  echo "==> Postgres isn't running yet (first deploy?); skipping the backup"
fi

echo "==> Pulling main"
before=$(git rev-parse --short HEAD)
git pull --ff-only --quiet origin main
after=$(git rev-parse --short HEAD)
echo "    $before -> $after"

echo "==> Building images (a few minutes on a small server)"
docker compose build --pull

echo "==> Restarting changed containers"
docker compose up -d --remove-orphans

echo "==> Running migrations"
docker compose exec -T app php artisan migrate --force

echo "==> Removing unused images"
docker image prune -f >/dev/null

docker compose ps --format "table {{.Service}}\t{{.Status}}"
echo "Deployed $after. Open the app and tap \"Reload to update\" if it appears."
