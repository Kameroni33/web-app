#!/usr/bin/env bash
# Nightly database backup: dump to backups/, copy to S3, prune old local copies.
# Usage: scripts/backup.sh
#
# The S3 copy happens only when BACKUP_S3_BUCKET is set in the root .env.
# It uses the AWS CLI profile "kam-backup" (aws configure --profile kam-backup),
# whose IAM user may only list the bucket and upload to it. Old copies in S3
# are removed by the bucket's lifecycle rule, not by this script.
set -euo pipefail
export PATH="/usr/local/bin:/usr/bin:/bin:$PATH" # cron has a minimal PATH

root="$(cd "$(dirname "$0")/.." && pwd)"
cd "$root"
keep_days=14

env_value() { grep -E "^$1=" .env 2>/dev/null | tail -1 | cut -d= -f2- | tr -d '"' || true; }

file="$root/backups/db-$(date -u +%Y%m%d-%H%M%S).dump"
"$root/scripts/db-dump.sh" "$file"

bucket="$(env_value BACKUP_S3_BUCKET)"
if [[ -n "$bucket" ]]; then
  aws --profile "${BACKUP_AWS_PROFILE:-kam-backup}" s3 cp --only-show-errors \
    "$file" "s3://$bucket/db/$(basename "$file")"
  echo "Uploaded to s3://$bucket/db/$(basename "$file")"
else
  echo "BACKUP_S3_BUCKET is not set in .env; kept the local copy only."
fi

find "$root/backups" -name 'db-*.dump' -mtime +"$keep_days" -delete
