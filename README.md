# Student Perks Hub — Laravel

Self-hosted information directory for student discounts, credits, trials and education offers. Verification and redemption happen on partner websites, not here.

## Locked stack

- Laravel 13.x (PHP >= 8.3), Blade, Livewire, Tailwind CSS
- Filament for admin CMS (compatible release)
- **PostgreSQL 18.x is the sole supported database for development, CI, staging and production**
- Self-managed local PostgreSQL and later VPS PostgreSQL; managed PostgreSQL is an optional hosting choice, not a required service
- Laravel local media and file cache; no Redis/S3/Supabase/Vercel requirement
- No public accounts, student verification, user submissions or saved offers at launch; admin login only

## One-time framework bootstrap

Run [Bootstrap Laravel](https://github.com/fusumivietnam/student-perks-laravel/actions/workflows/bootstrap-laravel.yml) on main. It generates Laravel 13 on a separate branch, configures PostgreSQL and tests against the PostgreSQL 18 service before opening a pull request. GitHub Actions must be allowed to write contents and create PRs.

## Local development — PostgreSQL only

Install PHP 8.3+, Composer, Node.js and a **locally running PostgreSQL 18** server. Create a database and non-superuser application role (substitute a strong locally managed password):

```sql
CREATE ROLE student_perks LOGIN PASSWORD 'CHANGE_ME';
CREATE DATABASE student_perks OWNER student_perks;
```

After the framework bootstrap PR is merged:

```bash
composer install
cp .env.example .env
php artisan key:generate
# Set DB_PASSWORD in .env to your local database role password.
php artisan migrate
npm ci
npm run build
php artisan serve
```

The scaffolded `.env.example` will use `DB_CONNECTION=pgsql`, `DB_HOST=127.0.0.1`, `DB_PORT=5432`, `DB_DATABASE=student_perks`, `DB_USERNAME=student_perks` and an empty `DB_PASSWORD`. Keep secrets in uncommitted `.env`. PostgreSQL should bind only to localhost / private networks.

See [docs/MIGRATION_PLAN.md](docs/MIGRATION_PLAN.md). Existing Next.js source is an optional UX/editorial reference, **not** an authentication or database migration requirement.
