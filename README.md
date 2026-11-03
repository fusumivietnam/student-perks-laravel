# Student Perks Hub — Laravel

Self-hostable directory of student discounts, credits, trials, and education offers. **We publish information and link visitors to the partner's offer page.** Student status verification, purchases, enrollment and redemption happen exclusively at the partner, not on this website.

## Stack and deployment

- **Laravel 13.x** (PHP >= 8.3), Blade, Livewire, Tailwind CSS.
- Filament admin CMS (install an officially compatible release after framework bootstrap).
- **Local-first**: SQLite by default for lightweight development, optionally locally installed PostgreSQL on laptop or VPS.
- Production VPS: PHP-FPM + Nginx, PostgreSQL or SQLite depending on operational needs; database must not be exposed publicly.
- Laravel filesystem for media, file cache, database-backed sessions/jobs only as needed. Redis, S3, Supabase, cloud databases, managed hosting are optional future adapters, **not dependencies**.
- No public user registration, logins, bookmarks or student verification in launch scope. Admin authentication only.

## Bootstrap (one time)

Run [Bootstrap Laravel](https://github.com/fusumivietnam/student-perks-laravel/actions/workflows/bootstrap-laravel.yml) on main from GitHub Actions. This generates an official Laravel **13.x** application on `bootstrap/laravel-foundation`, tests it and opens a PR for review. GitHub Actions needs write and pull-request creation permissions. It does not deploy anything.

After merge, on a local machine with PHP 8.3+, Composer and Node.js:

```bash
composer install
cp .env.example .env
php artisan key:generate
touch database/database.sqlite
php artisan migrate
npm ci
npm run build
php artisan serve
```

For local PostgreSQL, create a local user and database, change `DB_CONNECTION=pgsql`, `DB_HOST=127.0.0.1`, `DB_PORT=5432`, `DB_DATABASE`, `DB_USERNAME` and `DB_PASSWORD` in your uncommitted `.env`, then run `php artisan migrate`.

## Product

Visitors browse brands, categories, searchable offers and informational pages. Each offer describes eligibility, availability, terms, redemption steps and the official external partner link, with a clear "verify / redeem on partner website" action. Admins curate and publish content through Filament.

Original project for editorial/UX reference: https://github.com/fusumivietnam/student-perks-hub

See [docs/MIGRATION_PLAN.md](docs/MIGRATION_PLAN.md) for launch milestones. We are **building a new product foundation**, not lifting the previous auth stack, Supabase RLS policies, or elaborate CI/recovery automation.
