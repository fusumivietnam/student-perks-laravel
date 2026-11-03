# Student Perks Hub — Laravel 13 launch plan

## Approved product contract (2026-11-03)

Public directory of student discounts, subscriptions, credits and trials. The website publishes editorial information and outbound links to verified partner destinations. Partners alone manage eligibility, student verification, purchase and redemption. Never collect student cards or other student verification evidence.

## Database decision — final

**PostgreSQL 18.x** is the sole database engine for local development, CI, staging and production. Use an ordinary self-managed PostgreSQL instance locally and on a VPS. Do not add SQLite, MySQL, Supabase Auth or hosted database dependencies. Future managed PostgreSQL is an optional change of hosting, not a change of database family. Maintain forward-compatible migrations, avoid relying on superuser permissions and test the actual production database engine. PostgreSQL major upgrades will still require standard maintenance and testing.

## Infrastructure

- Laravel 13.x, PHP >= 8.3; Eloquent ORM and source-controlled migrations
- PostgreSQL 18 with restricted application role; localhost/private interface, strong credentials
- Blade + Livewire + Tailwind CSS for public UX; Filament for authenticated admin-only CMS
- Local filesystem for media and file cache; Redis, S3 and managed hosting remain optional
- VPS: Nginx + PHP-FPM + self-managed PostgreSQL, HTTPS and minimal scheduled jobs
- Required before production: basic consistent backups, restore test, secure secrets, firewall and rollback plan; defer advanced automated backup/recovery platforms
- No TinyFish, Supabase, or Vercel infrastructure required

## Content schema

- `brands`: name, slug, logo, website, status, SEO
- `categories`: name, slug, description, order
- `offers`: title, slug, brand, categories, summary, terms, offer value/type, eligibility text, partner instructions and HTTPS redemption URL, availability window, last editorial review, publication and SEO
- `articles`, `pages`: editorial content, publish status, SEO
- `users`: admin accounts only, guarded with policies/roles
- Optional aggregate outbound link counters without student profiling

## Public functionality

Homepage; /offers, /offers/{slug}, brand/category directories and detail, /search, articles and informational/legal pages. Filter/sort/search offers, show expiry and eligibility conditions, and direct CTA links to partner websites. Responsive and accessible UI, canonical/OG/sitemap and clear disclaimers. No internal student verification, public registration, saved offers, student submissions or payments.

## Delivery gates

| Phase | Deliverable | Acceptance criteria |
|---|---|---|
| 0 | Laravel 13 + PostgreSQL 18 skeleton | PostgreSQL tests pass and PR merged |
| 1 | Schema, models, seed factories | PostgreSQL migrations and CRUD tests |
| 2 | Offers, categories, brands, search | Mobile UX and route/SEO tests |
| 3 | Filament CMS and editorial workflow | Admin policies, CRUD and media tests |
| 4 | Articles, pages, legal copy, outbound links | Content/link/SEO tests |
| 5 | VPS deployment | TLS, smoke, security, backup/restore baseline and rollback |

One meaningful PR per vertical slice, one minimal CI pipeline, avoid infrastructure duplication. The previous Next.js deployment stays untouched until explicit production cutover. No managed paid resources without user approval.

## Current status

Documentation and framework bootstrap workflow exist. Application scaffold and PostgreSQL tests have **not** been run yet.
