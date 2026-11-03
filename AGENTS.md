# Student Perks Hub — execution rules for AI coding agents

Read this file before making ANY code or infrastructure change. Source of truth: `docs/PRODUCT.md`, `docs/ARCHITECTURE.md`, `docs/DELIVERY.md` and `docs/ROADMAP.md`.

## Mission

Ship a fast, SEO-friendly, maintainable PUBLIC directory of student/education offers. Visitors read terms and click through to partner sites. Partners handle verification, payment and redemption.

## Non-negotiable scope

- Laravel 13, PostgreSQL 18, Blade + Livewire, Tailwind, Filament admin.
- Docker Compose local/VPS with Nginx + PHP-FPM; native PHP-FPM deployment must remain possible on compatible control panels.
- No public accounts, student verification, student ID uploads, user submissions, saved offers, checkout or bespoke membership system.
- No Redis, SaaS database, Supabase, Vercel, TinyFish, custom admin SPA, microservices or queue workers without a proven requirement.
- No surprise paid services or production secrets in git.

## Before implementing

1. Inspect actual repository state and `docs/ROADMAP.md`, never assume previously planned features are implemented.
2. Choose the next incomplete *vertical slice* that reaches a usable UI, not isolated infrastructure.
3. Search Laravel/Filament/Livewire capabilities before writing custom frameworks or CRUD.
4. Check package compatibility with Laravel 13/PHP 8.3, existing Composer lock, license, security and maintenance.
5. Keep public URLs, content rules and acceptance criteria from `docs/PRODUCT.md` stable.

## Implementation rules

- Eloquent models + relationships, Form Requests / Filament validation, policies, Blade components; use service classes only for genuinely shared domain logic.
- Filament Resources for admin CRUD; reuse tables/forms/actions and policies.
- Default server-side rendering; Livewire only for dynamic UI, no separate React frontend.
- PostgreSQL-specific behavior must be tested against PostgreSQL, never substitute SQLite.
- Use migrations, indexes and constraints; no manual production SQL drift.
- Store partner URLs as validated HTTPS destinations; no open redirects, no collecting student verification data.
- Use simple file cache and filesystem media, with storage persistent across container restarts.
- No overengineering, speculative abstraction, new package or CI workflow without reason.
- Follow official project conventions; enforce tests and formatting on changed code.

## Completion criteria

For a feature: migration/model, admin editing, public display (where relevant), authorization/input validation, responsive UX, tests, SEO/redirect safety as applicable. Update `docs/ROADMAP.md` with tested facts, not estimated completion percentages. No claim of 'done' without passing checks.

For infrastructure: reproducible Docker build, PostgreSQL migrations and tests, health endpoint, persistent data, no public database port, documented setup and recovery.

## Delivery

Prefer one cohesive feature PR over many micro-PRs. Run relevant checks before merge; do not override failed checks. Keep main releasable. Do not alter old Next.js repository or launch DNS without explicit authorization. Production changes require backup, rollback and approval.

If blocked by unavailable tools, make best possible commits, state exactly what is unverified, and provide a single concrete next manual action.
