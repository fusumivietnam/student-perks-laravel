# Student Perks Hub — single canonical roadmap

This is the **only** authoritative project milestone and execution-status document. GitHub code/tests on `main` establish implementation reality; GitHub Issues/PRs/Actions provide evidence. Do not duplicate this board in `README.md`, older plans, GitHub Project fields or chat. Policy: [SOURCE_OF_TRUTH.md](SOURCE_OF_TRUTH.md).

Last repository review: 2026-11-03. All statuses below are based on committed source; no Laravel or Docker runtime success has been verified.

| ID | Priority | Milestone / deliverable | Exit criteria | Status | Evidence / blocker |
|---|---|---|---|---|---|
| M0 | P0 | Laravel 13 skeleton, PHP-FPM + Nginx Docker, PostgreSQL 18 | Framework in `main`, Composer/npm locks, migrations/tests and Docker build green | BLOCKED | Bootstrap workflow committed but not executed; no green build evidence |
| M1 | P0 | Database and Filament CMS for brands/categories/offers | Migrations, factories, admin CRUD, authorization and PostgreSQL tests pass | NOT STARTED | Depends on M0 |
| M2 | P0 | Public directory and search | Homepage, offer list/detail, brand/category routes, filter/search and responsive UX pass | NOT STARTED | Depends on M1 |
| M3 | P0 | Editorial publishing and SEO | Articles/pages, media, sitemap, metadata, safe partner CTA and tests pass | NOT STARTED | Depends on M2 |
| M4 | P0 | VPS launch-ready deployment | TLS, secured config, persistence, production smoke, baseline backup/restore and rollback verified | NOT STARTED | Depends on M0–M3 |

## Current blocking dependency

**M0:** Trigger [Bootstrap Laravel 13 (PostgreSQL 18)](https://github.com/fusumivietnam/student-perks-laravel/actions/workflows/bootstrap-laravel.yml) on `main`; inspect run logs and generated PR, then merge only after PostgreSQL tests and Docker build pass. Current Docker files are committed but not validated.

## Current repo evidence

- Architecture and product guardrails: [AGENTS.md](../AGENTS.md), [PRODUCT.md](PRODUCT.md), [ARCHITECTURE.md](ARCHITECTURE.md), [DELIVERY.md](DELIVERY.md), [SOURCE_OF_TRUTH.md](SOURCE_OF_TRUTH.md)
- Dev/prod Docker configuration and Nginx/PHP-FPM: `Dockerfile`, `compose.yaml`, `compose.dev.yaml`, `docker/nginx/default.conf`
- GitHub Actions: `.github/workflows/bootstrap-laravel.yml`, `.github/workflows/ci.yml`

**Not verified:** Docker build/boot, HTTP behavior, PHP application bootstrap, PostgreSQL migration/test runs, Filament installation, public/admin routes, VPS deployment. Source/configuration commits are not equivalent to delivered functionality.

## Update protocol

Update this file in the same PR that materially advances a milestone. Use only statuses `NOT STARTED`, `IN PROGRESS`, `BLOCKED`, `DONE`, `DEFERRED`, supported by GitHub PR/commit/Actions links. Mark `DONE` only after actual acceptance evidence. Link Issues as execution tasks, not second roadmaps. Only one next blocking action is maintained here. Chat summaries must link to this file rather than maintain separate roadmap versions.
