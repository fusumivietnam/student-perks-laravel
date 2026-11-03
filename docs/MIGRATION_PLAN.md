# Student Perks Hub — Laravel 13 launch plan

## Product contract (approved 2026-11-03)

A public information website listing student offers, credits, trials, and discounts. All eligibility checks, student verification, sign-up, payment and redemption are performed by the **partner website**. Student Perks Hub is not a verification provider and should not collect student IDs, school documents, or student status evidence.

Build on a **new Laravel foundation**, not a feature-for-feature port of Next.js. Previous UX and editorial content may be referenced selectively; existing production remains untouched until launch.

## Local-first infrastructure

- PHP >=8.3 and latest compatible Laravel 13.x patches via Composer lock.
- SQLite as zero-service dev/test default. Self-managed PostgreSQL is an option locally or on VPS; adopt it when concurrency/content volume warrants it.
- Blade / Livewire / Tailwind for public site; compatible Filament for authenticated admin-only CMS.
- Laravel local disk, file cache and simple queue configuration. Redis, S3, managed PostgreSQL, Cloudflare and other managed options must remain optional.
- Deploy to Nginx + PHP-FPM on a VPS; TLS, least-privilege OS/database account, private DB binding, backups and restore baseline are required before public production, but **advanced backup automation** is deferred.
- Preserve .env secrets outside git. Do not configure Supabase, Vercel, TinyFish or an external database by default.

## Content model

- `brands`: name, slug, logo, website, status, metadata.
- `categories`: name, slug, description, display order.
- `offers`: title, slug, brand, categories, summary, detailed terms, offer type/value, eligibility text, student instructions, partner redemption URL, active period, editorial verification date, publication status and SEO.
- `articles`, `pages`: editorial content, SEO, status and publication date.
- `admins` / Laravel users: restricted administrator accounts only; use policies/roles.
- Optional `offer_clicks` aggregate/event records for privacy-conscious outbound-link analytics (no student tracking needed).

## Public functionality

1. Landing page with featured/new offers and categories.
2. /offers and /offers/{slug}, filters, sorting, expired-offer handling.
3. /brands and /brands/{slug}; /categories/{slug}; /search.
4. /articles, /articles/{slug}, static informational/legal pages.
5. Offer CTA leading directly to verified HTTPS partner destination; `rel="noopener noreferrer sponsored"` when appropriate, no unsafe arbitrary URL redirect.
6. Mobile-responsive, accessible layout; metadata, Open Graph, sitemap, canonical, robots and basic structured data.
7. Clear editorial disclaimer that partner controls eligibility, pricing, verification and redemption.

**Explicit non-goals:** public login/register, student verification, saved offers, user submissions, accounts, student documents, Supabase Auth, ecommerce/payment, custom admin SPA.

## Delivery phases and acceptance gates

| Phase | Scope | Gate |
|---|---|---|
| 0 | Official Laravel 13 foundation, local SQLite, minimal CI | Scaffold PR merged, tests pass |
| 1 | Models, migrations, factories and sample seed content | Database migrate/fresh and model tests pass |
| 2 | Public templates for offer directory, brand/category and search | End-to-end page/SEO/accessibility checks |
| 3 | Filament admin CRUD, media and editorial publication | Policies, CRUD and authenticated access tests |
| 4 | Articles, pages, SEO and outbound-link safeguards | Content and link tests pass |
| 5 | VPS deploy and launch readiness | Smoke tests, HTTPS, server security, baseline backup/restore and rollback plan |

## Delivery rules

One PR per meaningful vertical feature, avoid multiple redundant workflows. Use source-controlled Laravel migrations and testable business logic. Never import legacy Supabase user credentials or student verification functionality. Do not activate paid/managed resources without user approval.

## State

Repository documentation and bootstrap workflow committed; Laravel application generation is not yet verified. Do not describe as production-ready.
