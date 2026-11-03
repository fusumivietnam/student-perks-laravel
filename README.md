# Student Perks Hub — Laravel 13

Self-hosted information directory for education discounts, trials, credits and partner offers. The site **publishes offers and sends visitors to partner websites**. Student verification and redemption happen on partners' own sites. No public student accounts or document collection.

## Architecture locked

| Layer | Choice |
|---|---|
| Application | Laravel 13, PHP 8.3, Blade + Livewire |
| Admin CMS | Filament 5 (compatible release, installed after scaffold) |
| Data | PostgreSQL 18 only, self-hosted |
| Containers | Docker Compose, shared Nginx and PHP-FPM Docker runtime |
| Assets | Tailwind + Vite |
| Media / cache | Local filesystem / Laravel file cache |
| Hosting | Docker VPS with TLS proxy; portable native PHP-FPM hosting |
| Optional later | Redis, object storage, managed PostgreSQL, queue workers |

**No SQLite, MySQL, Supabase, Vercel, TinyFish or student verification services required.**

## Progress

This repository currently contains **Docker infrastructure and project documentation**, not yet an installed Laravel app. Scaffold must be generated and reviewed first; `docker compose build` cannot succeed until `artisan`, `composer.lock`, `package-lock.json` and the app source exist.

Run [Bootstrap Laravel 13](https://github.com/fusumivietnam/student-perks-laravel/actions/workflows/bootstrap-laravel.yml) once on main. It generates Laravel 13 and tests on PostgreSQL 18 in GitHub Actions, then opens a PR.

## Docker quick start (after merging scaffold PR)

```bash
cp .env.docker.example .env
# Set APP_KEY (random 32-byte base64 Laravel key) and DB_PASSWORD in .env
docker compose -f compose.yaml -f compose.dev.yaml up -d --build
docker compose -f compose.yaml -f compose.dev.yaml exec app php artisan migrate --force
```

Then open http://localhost:8080. All application PHP runs in Docker; installing PHP/PostgreSQL directly on your host is unnecessary.

For production use `docker compose up -d --build` with a distinct production `.env`, HTTPS reverse proxy and preserved Docker volumes. See [Docker guide](docs/DOCKER.md) and [GitHub Project setup](docs/GITHUB_PROJECT.md).

## GitHub source of truth

The GitHub repository is authoritative, not chat conversations. [SOT policy](docs/SOURCE_OF_TRUTH.md) defines precedence. Roadmap management belongs in **GitHub Projects V2**, with [GitHub Issues](https://github.com/fusumivietnam/student-perks-laravel/issues) and linked PRs as work items. See [Project configuration and integration status](docs/GITHUB_PROJECT.md). The Project has not yet been created/connected; do not claim otherwise.

## Development policy

Feature-first delivery: Filament resources for content, Laravel form requests/policies, Eloquent relationships and Livewire reusable components. Avoid custom-built CRUD panels, student identity systems and unnecessary background services. Reference the [old frontend](https://github.com/fusumivietnam/student-perks-hub) for visual/editorial ideas only.
