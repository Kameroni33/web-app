# Docker Compose Setup

- The app runs under Docker Compose from the repo root (`../compose.yaml`). Postgres and Redis are only reachable inside the Compose network, never from the host.
- Configuration lives in the single root `../.env`; there is no `backend/.env`. Never create one.
- Commands that need the database, Redis or app config run in the container: `docker compose exec app php artisan migrate`, `docker compose exec app php artisan tinker`, `docker compose exec app php artisan route:list`.
- Commands that only touch code run on the host: `php artisan make:*`, `vendor/bin/pint`, `php artisan test` (PHPUnit uses in-memory SQLite via `phpunit.xml`).
- In production, application code is baked into the image. On the dev laptop, `compose.dev.yaml` (enabled via `COMPOSE_FILE` in `.env`) bind-mounts `backend/`, so PHP changes apply immediately; run `docker compose restart horizon` after changing jobs or mail.
- Database moves between hosts with `scripts/db-dump.sh` / `scripts/db-restore.sh`.
- The production image is built with `composer install --no-dev`, so dev packages (Boost, Pail) exist only on the host. Boost's database tools therefore cannot see the Compose Postgres.
