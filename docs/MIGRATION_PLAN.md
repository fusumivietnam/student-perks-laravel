# Migration and launch plan

## Decision and scope

Move Student Perks Hub from Next.js / Supabase-specific application code toward a maintainable Laravel monolith. Preserve existing production and data until explicit cutover. Prioritize complete user-facing features and admin operations; defer backup automation.

Original source: https://github.com/fusumivietnam/student-perks-hub

## Delivery phases

| Phase | Deliverable | Exit gate |
|---|---|---|
| 0 | Laravel skeleton with passing baseline tests; one CI workflow | Bootstrap PR merged |
| 1 | Data model: brands, categories, offers, articles, pages, users, bookmarks, submissions | Migrations, factories, tests pass |
| 2 | Public UX: home, /offers, offer details, /brands/{slug}, categories, /search, articles, pages | Responsive templates, SEO, route coverage |
| 3 | Admin: Filament resources, role authorization, review/publication workflow, media | CRUD and authorization tests |
| 4 | Accounts: registration/login/reset, /saved, /submit, /account, student verification | Feature and abuse-case tests |
| 5 | Import and verification: source-to-target content and media migration; authentication migration strategy | Reconciled counts, links, approvals |
| 6 | Launch: domain, hosting, HTTPS, caching, jobs, email, observability, smoke and rollback | Sign-off and controlled cutover |

## Route parity inventory

Compare the old app's existing routes and page behavior; do not assume every file implies production readiness. Particular priority: /offers, /search, /brands/{slug}, /saved, /submit, /account, /admin and auth / verification flows. Record missing variants and test redirects before traffic migration.

## Defaults to reduce code maintenance

- Blade + Livewire for customer-facing pages; Tailwind for styling.
- Filament for back-office CRUD, policies and admin forms; avoid writing a custom admin SPA.
- Laravel policies, form requests, Eloquent relationships, notifications and queues instead of bespoke helpers.
- Database: PostgreSQL. Keep Supabase Postgres as an option **only after** reviewing schema / auth / RLS implications.
- SQLite as temporary CI test database, not presumed production database.
- Keep packages minimal and pinned by Composer lockfile. Do not automatically copy npm packages or Next.js code.

## Data migration caveats

The old repository contains `supabase/schemas/`, `supabase/migrations/` and `supabase/seed.sql`; these define the current application database behavior. Inspect tenant/ownership rules, RLS policies, triggers, functions, and storage references before translating into Laravel migrations and policies. Supabase Auth identities and credential hashes require a separate plan, not a straight Eloquent users-table copy.

No destructive migration, production secret reuse, DNS change, or production deployment without explicit checks.

## GitHub delivery policy

- `main` protected after initial setup; work on short-lived feature branches.
- One focused PR per *vertical feature* rather than dozens of infrastructure-only PRs.
- CI: Composer validate, Laravel tests, format lint, minimal frontend build when applicable.
- Merge only when checks pass; squash merge preferred.
- Do not bring legacy scheduled monitor/backup/recovery workflows into new repo.
- No TinyFish dependency.

## Current status

Repository initialized with documentation and a manual workflow, pending actual Laravel skeleton generation, tests and merge. Completion of the repository bootstrap is **not** completion of the application or migration.
