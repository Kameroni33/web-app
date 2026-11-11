#!/usr/bin/env bash
# Cut a release: bump the version, commit, and tag it.
#
# Versions are YEAR.M.P (calendar versioning): P counts releases within the
# month and resets when the month changes, e.g. 2026.11.0, 2026.11.1, 2026.12.0.
# The version lives in frontend/package.json (no zero-padded month, so it stays
# valid semver) and is shown in the app's footer.
#
# Usage: scripts/release.sh            # next version
#        scripts/release.sh 2026.11.4  # explicit version
# Then push with tags and deploy (git pull && docker compose up -d --build).
set -euo pipefail
cd "$(dirname "$0")/.."

if [[ -n "$(git status --porcelain)" ]]; then
  echo "Working tree has uncommitted changes; commit or stash them first." >&2
  exit 1
fi

current=$(node -p "require('./frontend/package.json').version")
this_month="$(date +%Y).$(date +%-m)"

if [[ $# -ge 1 ]]; then
  next=$1
elif [[ $current == "$this_month".* ]]; then
  next="$this_month.$(( ${current##*.} + 1 ))"
else
  next="$this_month.0"
fi

if [[ ! $next =~ ^[0-9]{4}\.([1-9]|1[0-2])\.[0-9]+$ ]]; then
  echo "Version must be YEAR.M.P with an unpadded month, e.g. $this_month.0 (got $next)." >&2
  exit 1
fi
if git rev-parse -q --verify "refs/tags/v$next" >/dev/null; then
  echo "Tag v$next already exists." >&2
  exit 1
fi

(cd frontend && npm version "$next" --no-git-tag-version >/dev/null)
git add frontend/package.json frontend/package-lock.json
git commit -q -m "Release v$next"
git tag -a "v$next" -m "Release v$next"
echo "Released v$next (was $current). Push with: git push --follow-tags"
