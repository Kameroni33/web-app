# Docs

| Doc | Read it when |
| --- | --- |
| [FIRST_DEPLOYMENT.md](FIRST_DEPLOYMENT.md) | Setting up the AWS server for the first time, or rebuilding it from scratch |
| [AWS.md](AWS.md) | Deploying, reading logs, backups and restores, resizing, troubleshooting the server |
| [CLOUDFLARE.md](CLOUDFLARE.md) | DNS, the tunnel, who can reach the site (Access), email, caching |
| [IMPLEMENTATION_PLAN.md](IMPLEMENTATION_PLAN.md) | Why the app is built the way it is, and what's planned |

Scripts mentioned in these docs live in [`../scripts`](../scripts):

| Script | Where it runs | What it does |
| --- | --- | --- |
| `server-setup.sh` | Server, once | Swap, Docker, log caps, AWS CLI, auto-updates, backup cron job |
| `deploy.sh` | Server | Back up, pull `main`, rebuild, restart, migrate |
| `backup.sh` | Server (cron, 03:15 UTC) | `pg_dump` → `backups/` (14 days) → S3 |
| `db-dump.sh` / `db-restore.sh` | Either | Portable Postgres dump and restore |
| `release.sh` | Laptop | Bump the version, commit and tag a release |
