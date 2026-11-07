# Architecture decisions (binding)

## Runtime and portability

Application: Laravel 13 / PHP 8.5 production and CI (Composer supports PHP 8.3+) / PostgreSQL 18 / Blade + Livewire / Tailwind / Filament 5 admin. Frontend builds use Node 26 LTS.

Dev and VPS: Docker Compose with separate **Nginx**, **PHP-FPM** and **PostgreSQL 18** containers. Same PHP 8.5 runtime, database family, extensions and Composer lockfile. Dev overlay mounts files and uses Vite as appropriate. Production serves a built image; no code edits on live hosts.

Control-panel option: install matching PHP/extensions, configure public document root, Composer dependencies, scheduler and PostgreSQL connection. Docker parity is not guaranteed on native panels; feature code must not depend on container-only runtime APIs.

## Infrastructure constraints

Database: PostgreSQL 18 only, private Docker network / local socket. Migrations are schema source of truth. Credentials external to Git. Index common slug/status/brand/category filters. Use database constraints to protect content.

Media: Laravel public filesystem, persistent volume and storage symlink; image resizing/optimization through a compatible package ONLY when content performance requires it.

Cache: file cache; sessions file; sync queue until truly asynchronous jobs are justified. Reevaluate if traffic or task latency warrants it. Avoid extra services at launch.

HTTP: Nginx public-facing, PHP-FPM internal, public root `public/`, deny hidden files. TLS terminates at proxy/reverse proxy before public launch.

Deployment: build/test immutable application image, run migrations intentionally, restart services, smoke test. Keep PostgreSQL and uploads persistent. Do not publish 5432. Do not use `docker compose down -v` against real data.

## Preferred packages

| Need | Preferred choice | Constraint |
|---|---|---|
| CMS | Filament 5 | Confirm Composer compatibility with Laravel 13 |
| Dynamic filters | Livewire | Only where plain server-rendered links/forms fall short |
| Authorization | Laravel Gates/Policies | Spatie permission only if role complexity increases |
| SEO | Laravel layout components and metadata | Add SEO package only if it demonstrably reduces repetition |
| Sitemap | Existing Laravel controller/XML response | Already implemented; add a package only for a demonstrated functional gap |
| Media | Laravel filesystem first | Add Spatie Media Library only if conversions/collections are needed |
| Testing | Laravel tests / PHPUnit or Pest from skeleton | Avoid duplicate test frameworks |
| Formatting | Laravel Pint | Run on edited PHP files |

Avoid dependency-heavy starter kits, service wrappers and reinvented CMS/forms/tables.

## Decisions deferred

Managed services, caching cluster, horizontal scaling, advanced background jobs, multilingual system, automated import pipelines, analytics tracker, enterprise IAM. Document new decisions before introducing them.

## Verified foundation

Laravel 13 skeleton and Composer/npm lockfiles are committed on main in [PR #7](https://github.com/fusumivietnam/student-perks-laravel/pull/7). CI verified PostgreSQL 18 tests/migrations and Docker Compose HTTP smoke. Continue through ordinary PR CI, not scaffold bootstrap.

## Source dependency references

See [STACK_REFERENCES.md](STACK_REFERENCES.md) for the direct dependency inventory, the authority of the two lockfiles, and links to the official vendor documentation. See [SWOT_2026-11-07.md](SWOT_2026-11-07.md) for the current engineering risks and prioritized mitigation. Neither document introduces a runtime dependency.
