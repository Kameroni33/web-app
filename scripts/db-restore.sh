#!/usr/bin/env bash
# Replace the app database with the contents of a dump from scripts/db-dump.sh.
# Usage: scripts/db-restore.sh <file.dump>
# The postgres service must be running; the Laravel services are stopped
# during the restore and started again afterwards.
set -euo pipefail

file="$(realpath "${1:?usage: scripts/db-restore.sh <file.dump>}")"
root="$(cd "$(dirname "$0")/.." && pwd)"
cd "$root"

read -r -p "This REPLACES the database in this stack with $file. Type 'yes' to continue: " answer
[[ "$answer" == "yes" ]] || { echo "Aborted."; exit 1; }

docker compose stop app horizon scheduler
docker compose exec -T postgres sh -c \
  'pg_restore -U "$POSTGRES_USER" -d "$POSTGRES_DB" --clean --if-exists --no-owner --no-acl --single-transaction' \
  < "$file"
docker compose start app horizon scheduler

echo "Restored. Run: docker compose exec app php artisan migrate --force"
