# Student Perks Hub — Laravel 13

Self-hosted information directory for education discounts, trials, credits and partner offers. The site **publishes offers and sends visitors to partner websites**. Student verification and redemption happen on partners' own sites. No public student accounts or document collection.

## Architecture locked

| Layer | Choice |
|---|---|
| Application | Laravel 13, PHP 8.5, Blade + Livewire |
| Admin CMS | Filament 5 |
| Data | PostgreSQL 18 only, self-hosted |
| Containers | Docker Compose, shared Nginx and PHP-FPM Docker runtime |
| Assets | Tailwind + Vite |
| Media / cache | Local filesystem / Laravel file cache |
| Hosting | Docker VPS with TLS proxy; portable native PHP-FPM hosting |
| Optional later | Redis, object storage, managed PostgreSQL, queue workers |

**No SQLite, MySQL, Supabase, Vercel, TinyFish or student verification services required.**

## Progress

Laravel 13 scaffold is merged to `main` via [PR #7](https://github.com/fusumivietnam/student-perks-laravel/pull/7). [CI #41994566276](https://github.com/fusumivietnam/student-perks-laravel/actions/runs/41994566276) verified PostgreSQL 18 migrations/tests, Docker build, entrypoint assets, and Docker Compose HTTP smoke. One-time bootstrap workflow retired; further changes use ordinary PR CI.

## Private preview (no domain or branding required)

Run the real Docker stack on your computer or in GitHub Codespaces using `bash scripts/preview.sh`. Codespaces port 8080 stays **Private** by default. See [Preview guide](docs/PREVIEW.md) for setup, admin access and safe test-data handling.

## Docker quick start

```bash
cp .env.docker.example .env
# Set APP_KEY (random 32-byte base64 Laravel key) and DB_PASSWORD in .env
docker compose -f compose.yaml -f compose.dev.yaml up -d --build
docker compose -f compose.yaml -f compose.dev.yaml exec app php artisan migrate --force
```

Then open http://localhost:8080. All application PHP runs in Docker; installing PHP/PostgreSQL directly on your host is unnecessary.

For production use `docker compose up -d --build` with a distinct production `.env`, HTTPS reverse proxy and preserved Docker volumes. Complete the [M4 production release checklist](docs/PRODUCTION.md) before exposing the service. See [Docker guide](docs/DOCKER.md) and [GitHub Project setup](docs/GITHUB_PROJECT.md).

## Filament CMS admin (M1)

After setting up Docker, create an administrator using Laravel and Filament's built-in commands (no public registration):

```bash
docker compose exec app php artisan make:filament-user
docker compose exec app php artisan tinker
```

Inside Tinker, promote **only** your intended admin email: `App\\Models\\User::where('email', 'admin@example.com')->update(['is_admin' => true]);` (replace the example address). Then sign in at `/admin`. Regular users cannot access the admin panel; never seed default production credentials.

## GitHub source of truth

The GitHub repository is authoritative, not chat conversations. [SOT policy](docs/SOURCE_OF_TRUTH.md) defines precedence. Roadmap management belongs in **GitHub Projects V2**, with [GitHub Issues](https://github.com/fusumivietnam/student-perks-laravel/issues) and linked PRs as work items. See [Project configuration and integration status](docs/GITHUB_PROJECT.md). [Project #5](https://github.com/users/fusumivietnam/projects/5) is the registered central planning URL. Board configuration and automation are not yet verified.

## Development policy

Feature-first delivery: Filament resources for content, Laravel form requests/policies, Eloquent relationships and Livewire reusable components. Avoid custom-built CRUD panels, student identity systems and unnecessary background services. Reference the [old frontend](https://github.com/fusumivietnam/student-perks-hub) for visual/editorial ideas only.
