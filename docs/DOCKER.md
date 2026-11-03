# Docker: identical application image, local and VPS

## Architecture

A single application Dockerfile uses official FrankenPHP (Caddy + PHP 8.3), Composer and a Node build stage; PostgreSQL 18 runs in the companion service. **Development and production use the same application runtime and database engine.** A local overlay mounts source code for fast edits; production runs the immutable compiled image.

No managed services, Redis, Nginx, separate queue worker, or Octane needed for initial launch.

## First local boot (after Laravel scaffold PR merges)

1. Install Docker Engine + Compose plugin (or Docker Desktop).
2. `cp .env.docker.example .env`
3. Generate `APP_KEY` with `docker run --rm dunglas/frankenphp:1-php8.3 php -r 'echo "base64:".base64_encode(random_bytes(32)).PHP_EOL;'` and set it in `.env`.
4. Generate a strong database password (e.g., password manager) and set `DB_PASSWORD`. Never commit `.env`.
5. `docker compose -f compose.yaml -f compose.dev.yaml up -d --build`
6. `docker compose -f compose.yaml -f compose.dev.yaml exec app php artisan migrate --force`
7. Open http://localhost:8080

For continuous frontend changes run Node/Vite locally, or rebuild the Docker image after frontend changes. The production image always builds frontend assets as part of Docker build.

## VPS deployment

Set `APP_ENV=production`, `APP_DEBUG=false`, a production `APP_URL`, unique `APP_KEY` and strong `DB_PASSWORD` in the VPS's untracked `.env`. Use `docker compose up -d --build`, then run reviewed migrations via `docker compose exec app php artisan migrate --force`. Put a TLS terminator / HTTPS reverse proxy in front before exposing the site publicly; do not publicly expose database ports. The compose HTTP port should bind only to localhost when a separate reverse proxy is installed (e.g. `APP_HTTP_PORT=127.0.0.1:8080`).

**Never** use `docker compose down -v` on a system with valuable data. Back up PostgreSQL and uploaded media, and test restores, before production changes. Switching between the dev overlay and production mode may replace local source and vendor mounts, but never deliberately remove database volumes.

## Important

The initial default Laravel `Caddyfile` is provided by FrankenPHP's official image. A production site still needs public HTTPS and a real domain. If development requires a live Vite hot-reload service, add a dev-only Node service later. Do not expose PostgreSQL 5432 publicly.

Container parity reduces differences between environments but does not guarantee release parity unless the same commit and image digest are deployed.
