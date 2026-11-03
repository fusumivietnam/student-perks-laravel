# Student Perks Hub — implementation roadmap

## Product definition (approved)

Public editorial directory listing student/education promotions. Visitors browse/search offers, learn terms and follow trusted links to partner sites. **Partners** handle registration, student verification, payment and redemption. There is no student membership, internal verification or document collection.

## Foundation

- Laravel 13.x + PHP 8.3 + Blade/Livewire + Tailwind/Vite.
- **PostgreSQL 18 is the only database engine** used in dev, CI, staging and production.
- **Docker Compose is the standard deployment unit** everywhere; the same app Dockerfile/runtime is built in dev/prod. A dev-only overlay bind mounts code.
- FrankenPHP (Caddy + PHP runtime) runs Laravel; PostgreSQL uses private Docker networking and a persistent named volume.
- Filesystem media and file cache. No Redis, S3, mail service or dedicated workers as baseline.
- Production needs HTTPS proxy/domain, restricted ports, secrets, minimum backup/restore and rollback procedures; advanced managed operations are later.

## Preferred Laravel packages (avoid reinventing implementation)

| Feature | Package/pattern | Launch decision |
|---|---|---|
| Administration | `filament/filament:^5` | Install first after scaffold; admin login, resources, tables, forms |
| UI interactions | `livewire/livewire` | Use for search/filter/pagination, prefer simple Blade otherwise |
| Role/authorization | Built-in Laravel policies and Filament panel gates | Start here; consider `spatie/laravel-permission` only for multiple roles |
| SEO | `artesaos/seotools` or simple Laravel metadata | Evaluate only if manual meta markup becomes repetitive |
| Images | Built-in Laravel filesystem + image conversion only if needed | Avoid early cloud and media abstractions |
| Sitemap | `spatie/laravel-sitemap` if generation is needed | Add during SEO phase |
| Redirects/slug | Laravel routing and model route binding | No custom routing framework |
| Backups | Basic PostgreSQL pg_dump and stored files | Essential before live launch; postpone complex orchestration |
| Queue/cache | Laravel sync/file defaults | Introduce Redis/worker based on measured need |

Check exact Composer compatibility and install only purposeful packages. Do not start with a giant dependency list.

## Data model

Brands, categories, offers, articles, pages and admin users. Offer fields include title, slug, brand, categories, offer terms, eligibility wording, partner HTTPS destination, published status, active dates, source update date and SEO text. Optional basic anonymous outbound metrics; no student tracking.

## Vertical slices

| Phase | Deliverable | Test gate |
|---|---|---|
| 0 | Official Laravel 13 skeleton + Docker + PostgreSQL 18 | Composer install, migrations, Laravel tests, Docker build |
| 1 | Migrations, factories and Filament resources for offers/brands/categories | Admin CRUD, authorization, DB tests |
| 2 | Public offer listing, details, category/brand pages and search | Responsive, functional navigation and SEO smoke |
| 3 | Articles/pages, content publishing, media, sitemap and outbound safety | CRUD, SEO and link tests |
| 4 | VPS deployment and launch readiness | TLS, production env, backup/restore, monitoring baseline, rollback |

No changes to the previous Next.js production system without an approved cutover. No paid managed services without an explicit choice.

## Status

Docker source files committed and ready for framework bootstrap. Laravel executable image and framework tests are **not yet verified**.
