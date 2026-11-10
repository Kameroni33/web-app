# Deployment

- This app is self-hosted: the Docker Compose stack in `../compose.yaml` runs on a home server and is reachable only through a Cloudflare Tunnel. It is not deployed to Laravel Cloud, Forge, Vapor or any PaaS; do not suggest them.
- A deploy is `git pull` followed by `docker compose up -d --build`, then `docker compose exec app php artisan migrate --force`.
- Migrations must be safe to run against existing data. Never assume a fresh database outside tests.
- State that must survive a move to a new host lives only in Postgres, the `storage` volume and the root `.env` (including `APP_KEY`). Do not write state anywhere else (no host paths, no local files outside `storage/`).
