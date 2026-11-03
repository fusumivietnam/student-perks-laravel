# Docker Compose — local and VPS

## Runtime

Three services: Nginx (public HTTP), PHP 8.3-FPM Laravel app, PostgreSQL 18 (private). Development and production build the same PHP Dockerfile, with a development-only source bind mount. This allows a later native panel deployment via Nginx/Apache + matching PHP-FPM.

**Prerequisite:** Bootstrap Laravel skeleton and lockfiles first. Until then Docker builds will fail.

## Local development

1. Install Docker Engine/Compose or Docker Desktop.
2. Copy `.env.docker.example` to untracked `.env`; generate a secure 32-byte Laravel `APP_KEY` (format `base64:...`) and strong `DB_PASSWORD`. Never commit either.
3. `docker compose -f compose.yaml -f compose.dev.yaml up -d --build`
4. `docker compose -f compose.yaml -f compose.dev.yaml exec app php artisan migrate --force`
5. `docker compose -f compose.yaml -f compose.dev.yaml exec app php artisan storage:link` if not already present.
6. Open http://localhost:8080.

Nginx reads public assets from the source mount in dev. PHP dependencies install into a Docker vendor volume; when lockfiles change, rebuild/reinstall. Rebuild the app image for frontend changes unless a dev-only Vite service is introduced.

## Production on VPS

Do not use the dev override. Provide production `.env` with `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL` and unique secrets. Deploy a tested image and run reviewed migrations with `docker compose exec app php artisan migrate --force`.

Nginx reads a shared public volume. **Initialization step required for new or updated deployments:** `docker compose run --rm app sh -lc 'cp -a public/. /tmp/public-assets/'` is NOT sufficient for populating a mounted volume (image directory is masked). Prefer an image-backed release task that copies baked `public` assets into the named volume from a temporary path. Until that is implemented and tested, production Compose is infrastructure-in-progress and NOT release-ready.

Bind port only on localhost behind a TLS reverse proxy by default; expose publicly only with configured HTTPS, firewall and trusted proxy behavior. Never publish database port 5432. Keep volumes for PostgreSQL and uploads across deploys.

Mandatory before launch: asset synchronization, rollback, PostgreSQL dump + file media backup with restore rehearsal, SSL, scheduled Laravel tasks if required and smoke tests.

Never run `docker compose down -v` against valuable data.
