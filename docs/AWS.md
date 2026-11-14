# AWS: what runs where, and how to look after it

The reference for the production server. If you're coming back after months away, read **Quick reference** and **Inventory** first. First-time setup is in [FIRST_DEPLOYMENT.md](FIRST_DEPLOYMENT.md). Cloudflare (DNS, tunnel, Access, email) is in [CLOUDFLARE.md](CLOUDFLARE.md).

## Quick reference

| I want to… | Do this |
| --- | --- |
| Get onto the server | `ssh kam-apps` (or Lightsail console → instance → *Connect using SSH*) |
| Deploy the latest `main` | `ssh kam-apps '~/web-app/scripts/deploy.sh'` |
| See what's running | `cd ~/web-app && docker compose ps` |
| Read logs | `docker compose logs -f --tail=100 app` (or `horizon`, `nginx`, `cloudflared`, `postgres`) |
| Run an artisan command | `docker compose exec app php artisan <command>` |
| Back up right now | `~/web-app/scripts/backup.sh` |
| Restart everything | `docker compose restart` |
| Check disk / memory | `df -h /` · `free -h` · `docker system df` |

## Inventory

Fill this in during the first deployment and keep it current.

| Thing | Value |
| --- | --- |
| AWS sign-in | IAM user `kameron` (AdministratorAccess, MFA) at `https://<account-id>.signin.aws.amazon.com/console`. Root has MFA and is only for account and billing jobs. |
| Laptop CLI | Profile `kam-apps` (ca-central-1), signed in as `kameron` with `aws login --profile kam-apps` (12 h credentials, renewed automatically for 90 days) |
| Agent tooling | AWS Agent Toolkit: `aws-mcp` MCP server + AWS skills for Claude Code, using profile `kam-apps` |
| Region | `ca-central-1` (Canada, Montréal) |
| Lightsail instance | `kam-apps` · Ubuntu LTS · 1 GB plan · created ____ |
| Static IP | `kam-apps-ip` = ____ |
| Firewall | IPv4 + IPv6: SSH 22 only (HTTP 80 removed) |
| Automatic snapshots | Daily at 09:00 UTC, 7 kept |
| S3 backup bucket | `kam-apps-backups-____` · versioned · lifecycle `expire-old-dumps` (90 days; old versions after 7) |
| IAM user for backups | `kam-apps-backup` · policy `kam-apps-backup-upload` (list + upload to `db/` only) · key created ____ |
| Budget | `monthly-cap` · $20 · emails you |
| Server login | `ubuntu`, key-only SSH with your `~/.ssh/id_ed25519` |
| App checkout on server | `/home/ubuntu/web-app` (public GitHub repo, `main`) |

## How it fits together

```
Cloudflare Tunnel ─▶ cloudflared ─▶ nginx :80 ─┬─ static Vue build (PWA)
                                               └─ /api, /sanctum, /up ─▶ app (php-fpm, Laravel)
                     horizon  (queue worker: sign-in emails and other jobs)
                     scheduler (php artisan schedule:work)
                     postgres (all app data, volume "pgdata")
                     redis    (queues, cache, rate limits; volume "redisdata")
```

- Everything runs under Docker Compose, defined in `compose.yaml` at the repo root. There's no other software on the server besides Docker, the AWS CLI and cron.
- **Nothing listens on the internet.** nginx is bound to `127.0.0.1:23417`, for testing from the server itself. `cloudflared` dials *out* to Cloudflare, so the firewall only needs SSH open.
- `cloudflared` only starts when `.env` has `COMPOSE_PROFILES=tunnel`, which only the server has. See [CLOUDFLARE.md → Tunnel](CLOUDFLARE.md#tunnel-how-traffic-reaches-the-server).

### Where the state lives

Only these three things matter. Everything else can be rebuilt from git.

1. **Postgres** (Docker volume `web-app_pgdata`): all accounts, profiles, families and gift lists, including profile photos, which are stored in the database.
2. **`~/web-app/.env`**: secrets and settings. It isn't in git (the repo is public). There's a copy in your password manager, isn't there? (Put one there.)
3. **Redis**: queue jobs, cache and rate-limit counters. Losing it is harmless.

The `storage` volume only holds Laravel's framework cache; logs go to Docker.

### Server files

| Path | What |
| --- | --- |
| `~/web-app/` | Git checkout of `main`. Never edit code here; `deploy.sh` refuses to run if you have. |
| `~/web-app/.env` | Production settings (mode 600) |
| `~/web-app/backups/` | Local dumps, 14 days kept (gitignored) |
| `~/backup.log` | Output of the nightly backup cron job |
| `/swapfile` | 2 GB swap, so image builds fit in 1 GB of RAM |
| `/etc/docker/daemon.json` | Docker log cap (10 MB × 3 per container) |
| `/etc/apt/apt.conf.d/52kam-apps-reboot` | Auto-reboot at 04:00 UTC after security updates |
| `crontab -l` (user `ubuntu`) | `15 3 * * *` runs `scripts/backup.sh` |

### Production `.env`: the settings that differ from the laptop

| Setting | Server | Laptop |
| --- | --- | --- |
| `APP_ENV` / `APP_DEBUG` | `production` / `false` | `local` / `true` |
| `APP_URL` | `https://app.network-kam.com` | `http://localhost:23417` |
| `COMPOSE_PROFILES` | `tunnel` | *(unset)* |
| `COMPOSE_FILE` | *(unset)* | `compose.yaml:compose.dev.yaml` |
| `TUNNEL_TOKEN` | set | *(unset, never on the laptop)* |
| `SESSION_SECURE_COOKIE` | `true` | *(unset)* |
| `MAIL_MAILER` | `smtp` | `log` |
| `BACKUP_S3_BUCKET` | bucket name | *(unset)* |

**Never change `APP_KEY` casually.** It signs sessions, sign-in codes and links. A new key signs everyone out and breaks any emailed link that hasn't been used yet.

## Common tasks

### Deploy a new version

```bash
# laptop: merge to main, optionally cut a release (scripts/release.sh), push
git push --follow-tags
ssh kam-apps '~/web-app/scripts/deploy.sh'
```

`deploy.sh` runs these steps in order:

1. backs up the database;
2. `git pull --ff-only` from `main`;
3. rebuilds the images;
4. `docker compose up -d`, which restarts only the containers that changed;
5. runs migrations;
6. prunes old images.

Phones then show **Reload to update**.

**Rolling back:**

```bash
cd ~/web-app
git log --oneline -5
git checkout <good-commit>
docker compose up -d --build
```

When you're done, go back with `git checkout main`. If a migration has to be undone, restore the pre-deploy dump (below) instead of hand-writing SQL.

### Logs

```bash
docker compose logs --since=1h app horizon     # PHP errors, failed jobs
docker compose logs -f cloudflared             # tunnel connection
docker compose logs nginx | grep ' 5[0-9][0-9] '
```

Laravel logs to stderr, so `docker compose logs` is the only place logs live. They're capped at 30 MB per container and survive restarts, but not `docker compose down`.

### Accounts, families, admins (CLI)

```bash
docker compose exec app php artisan accounts:list
docker compose exec app php artisan accounts:create someone@example.com --name="Sam" --group="Robinson family"
docker compose exec app php artisan accounts:admin you@example.com [--revoke]
docker compose exec app php artisan groups:list
docker compose exec app php artisan groups:add-member "Robinson family" someone@example.com
```

`php artisan list accounts` and `php artisan list groups` show every command.

### Backups

There are two layers:

- **Nightly `pg_dump` at 03:15 UTC.** Stored in `~/web-app/backups/` for 14 days and in S3 `db/` for 90 days (deleted versions stay 7 more days). It's portable to any Postgres 18 host.
- **Lightsail automatic snapshot at 09:00 UTC.** The whole disk, last 7 days. Use it for "the server is broken". Use the dumps for "the data is wrong".

To check backups are working:

- `tail ~/backup.log`;
- or the S3 console → bucket → `db/`;
- or `aws --profile kam-backup s3 ls s3://<bucket>/db/ | tail` on the server.

**Restore the database from a local dump** (bad migration, accidental deletion). The restore script stops the app, replaces the database, and starts the app again:

```bash
cd ~/web-app
ls -lt backups | head
scripts/db-restore.sh backups/db-YYYYMMDD-HHMMSS.dump     # type: yes
docker compose exec -T app php artisan migrate --force
```

**Restore from S3.** The server's key can't download, on purpose. Download the file in the S3 console (or on the laptop with `aws --profile kam-apps s3 cp s3://<bucket>/db/<file> .`), copy it up with `scp <file> kam-apps:~/`, then restore as above.

**The whole server is gone or broken:**

- From a snapshot: Lightsail → Snapshots → *Create new instance* from it. Move the static IP to the new instance. Then make sure only one machine runs `cloudflared`.
- From scratch: follow FIRST_DEPLOYMENT steps 2–8. Restore the latest S3 dump instead of the laptop dump, and use the `.env` from your password manager.

### Resize the server

Lightsail plans can't be resized in place. Instead:

1. Snapshot the instance.
2. *Create new instance* from the snapshot and pick the bigger plan.
3. Move the static IP to the new instance.
4. Stop the old one, check the site, then delete the old one.

The new instance boots with the same disk, so the containers come back on their own.

### Updates

- **Ubuntu security patches** install themselves and reboot at 04:00 UTC when needed. Every container has `restart: unless-stopped`, so the app comes back.
- **Ubuntu release upgrade** (e.g. 24.04 → 26.04) needs `sudo do-release-upgrade`. Snapshot first. Or build a fresh instance on the new release and move the data, the safer way.
- **App dependencies** (Composer, npm, base images) change through normal PRs, then `deploy.sh`. `docker compose build --pull` picks up patched base images.
- **cloudflared** is pinned in `compose.yaml` (`cloudflare/cloudflared:<version>`). Bump the tag in a PR now and then.
- **Postgres major version** (18 → 19) is **not** a tag bump; the data format changes. Run `scripts/db-dump.sh`, change the image tag, delete the `pgdata` volume, start it, then `scripts/db-restore.sh`. Practise on the laptop first.

### Rotate secrets

| Secret | How |
| --- | --- |
| Backup access key | IAM → `kam-apps-backup` → create a new key → `aws configure --profile kam-backup` on the server → test `scripts/backup.sh` → deactivate, then delete, the old key |
| `TUNNEL_TOKEN` | See [CLOUDFLARE.md](CLOUDFLARE.md#tunnel-how-traffic-reaches-the-server) |
| `MAIL_PASSWORD` | See [CLOUDFLARE.md](CLOUDFLARE.md#email-sign-in-codes-and-invites) |
| `DB_PASSWORD` | Change it inside Postgres too: `docker compose exec postgres psql -U app -d laravel -c "ALTER USER app PASSWORD '...'"`, then update `.env` and run `docker compose up -d` |
| SSH key | Lightsail → Account → SSH keys. Or edit `~/.ssh/authorized_keys` on the server (keep a working session open while you test) |

## Troubleshooting

| Symptom | Likely cause → fix |
| --- | --- |
| Cloudflare **Error 1033** / "tunnel not found" | `cloudflared` isn't running or can't connect. Check `docker compose ps cloudflared` and `logs cloudflared`. Is `COMPOSE_PROFILES=tunnel` set? Is the server even up (Lightsail console)? |
| **502 Bad Gateway** | nginx is up, but `app` (php-fpm) is down or crashing. Read `docker compose logs app`. |
| The site loads but "Couldn't load your apps" | The API is failing. `docker compose logs app`. A missing migration? Run `php artisan migrate --force`. |
| Sign-in code email never arrives | Horizon is down, or SMTP is rejected. Check `docker compose logs horizon`, and `php artisan horizon:status`. Also see [CLOUDFLARE.md → Email](CLOUDFLARE.md#email-sign-in-codes-and-invites). |
| Half of visitors see old or odd data | **Two `cloudflared` connectors.** Cloudflare → Tunnels → `web-app` → Connectors. Stop the stray one (probably the laptop). |
| `deploy.sh` dies during `npm run build` (killed) | Out of memory. Check swap with `swapon --show`. Re-run, or move up to the 2 GB plan. |
| Disk full | `docker system prune -af` (removes unused images), `du -sh ~/web-app/backups`, `df -h /` |
| Server unreachable over SSH | Your home IP changed (if SSH is restricted): Lightsail browser SSH → edit the firewall rule. Otherwise reboot it from the Lightsail console. |
| Phones stuck on an old version | Settings → *Check for updates*. Failing that, close and reopen the app. |

## Costs

Approximate; check the AWS Billing console for actual numbers.

| Item | ~Monthly |
| --- | --- |
| Lightsail 1 GB plan (includes disk, static IP, 2 TB transfer) | ~$7 |
| Automatic snapshots (billed per GB stored, incremental) | ~$1–2 |
| S3 backups (a few MB) + requests | < $0.10 |
| **AWS total** | **~$8–10** |

The `monthly-cap` budget emails you if spending passes $20.

## Change log

Add a line whenever infrastructure changes: what changed and why.

| Date | Change |
| --- | --- |
| 2026-11-14 | Planned the move from the home laptop to Lightsail; wrote the scripts and docs. `cloudflared` moved behind the `tunnel` Compose profile. |
| ____ | First deployment to Lightsail (`kam-apps`, ca-central-1). |
