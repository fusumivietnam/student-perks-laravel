# Delivery contract — stay focused

## Work order

Follow the SINGLE authoritative `docs/ROADMAP.md` (see `docs/SOURCE_OF_TRUTH.md`) from top to bottom unless fixing a regression/security issue. A vertical slice includes user-visible behavior, admin control, data and tests. Do not spend cycles on repeated governance, backup/recovery orchestration or speculative cloud integrations before a usable launch.

## Feature definition of done

- Public route renders real database content and handles empty/unpublished/expired cases
- Admin can create/edit/publish with Filament where applicable
- Models/migrations/validation/authorization are exercised by tests on PostgreSQL 18
- Responsive, accessible UI with appropriate metadata, links and error states
- CI passes, changes documented and no unrelated functionality introduced

## Mandatory checks

1. `composer validate --strict`
2. `php artisan migrate:fresh --force` against disposable PostgreSQL test database only
3. `php artisan test`
4. `vendor/bin/pint --test` where installed
5. `npm ci && npm run build` if frontend assets change
6. `docker compose config` and container build for infrastructure changes
7. Smoke test relevant public/admin routes before declaring launch-ready

Never run destructive migrations or `migrate:fresh` on a real data database.

## PR policy

One meaningful vertical slice per PR; reduce repeated CI and branches. Commit lock files. A red CI is not a pass. No automatic production deployment or domain cutover. Update the central roadmap in the same delivery change; record only verifiable status and links to actual GitHub PRs, commits and checks. No competing status board in chat or other docs.

## Quality bar

No hardcoded demo data in production UI, no placeholder buttons, no broken links, no dashboard-only feature without public integration when necessary, no needless JS frameworks, no unreviewed open redirects, no student verification workflow.

## Deviation policy

Any new service/package requires evidence of maintenance, compatibility, license and a concrete gap not covered by Laravel/Filament. Update Architecture Decision before adding an extra container. If package is rejected, state why.
