#!/usr/bin/env bash
# Dump the app database to a portable pg_dump custom-format file.
# Usage: scripts/db-dump.sh [output-file]   (default: backups/db-<timestamp>.dump)
set -euo pipefail

root="$(cd "$(dirname "$0")/.." && pwd)"
out="${1:-$root/backups/db-$(date +%Y%m%d-%H%M%S).dump}"
mkdir -p "$(dirname "$out")"

cd "$root"
docker compose exec -T postgres sh -c \
  'pg_dump -U "$POSTGRES_USER" -d "$POSTGRES_DB" --format=custom --no-owner --no-acl' \
  > "$out"

echo "Wrote $out ($(du -h "$out" | cut -f1))"
