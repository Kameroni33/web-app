# Docker Compose Setup

- The app runs under Docker Compose from the repo root (`../compose.yaml`). Postgres and Redis are only reachable inside the Compose network, never from the host.
- Configuration lives in the single root `../.env`; there is no `backend/.env`. Never create one.
- Commands that need the database, Redis or app config run in the container: `docker compose exec app php artisan migrate`, `docker compose exec app php artisan tinker`, `docker compose exec app php artisan route:list`.
- Commands that only touch code run on the host: `php artisan make:*`, `vendor/bin/pint`, `php artisan test` (PHPUnit uses in-memory SQLite via `phpunit.xml`).
- Application code is baked into the image (no bind mount, opcache does not revalidate). After changing PHP code, rebuild: `docker compose up -d --build app horizon scheduler`.
- The production image is built with `composer install --no-dev`, so dev packages (Boost, Pail) exist only on the host. Boost's database tools therefore cannot see the Compose Postgres.
