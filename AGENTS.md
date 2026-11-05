# Student Perks Hub — execution rules for AI coding agents

Read this file before making ANY code or infrastructure change. **GitHub is the only SOT; chat is unverified input.** First read `docs/SOURCE_OF_TRUTH.md`. GitHub Projects V2 is the intended central roadmap/board (see `docs/GITHUB_PROJECT.md`); GitHub Issues/PRs are canonical work items. No manual or custom-generated Markdown roadmap. Until the Project is connected, use GitHub Issues as the live task list. `docs/PRODUCT.md`, `docs/ARCHITECTURE.md`, and `docs/DELIVERY.md` define scope and engineering rules. Code and tests merged to `main` establish implementation truth.

## Mission

Ship a fast, SEO-friendly, maintainable PUBLIC directory of student/education offers. Visitors read terms and click through to partner sites. Partners handle verification, payment and redemption.

## Non-negotiable scope

- Laravel 13, PostgreSQL 18, Blade + Livewire, Tailwind, Filament admin.
- Docker Compose local/VPS with Nginx + PHP-FPM; native PHP-FPM deployment must remain possible on compatible control panels.
- No public accounts, student verification, student ID uploads, user submissions, saved offers, checkout or bespoke membership system.
- No Redis, SaaS database, Supabase, Vercel, TinyFish, custom admin SPA, microservices or queue workers without a proven requirement.
- No surprise paid services or production secrets in git.

## Before implementing

1. Inspect actual GitHub `main`, GitHub Project if connected, relevant issues/PRs and Actions evidence; never assume prior chat or planned features are implemented.
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

For a feature: migration/model, admin editing, public display (where relevant), authorization/input validation, responsive UX, tests, SEO/redirect safety as applicable. Update the GitHub Issue/PR with verified facts and Project status when available. No Markdown roadmap updates or estimated percentages. No claim of 'done' without passing checks.

For infrastructure: reproducible Docker build, PostgreSQL migrations and tests, health endpoint, persistent data, no public database port, documented setup and recovery.

## Resource and execution efficiency (mandatory)

- First check whether code, CI, package or GitHub native automation already solves the task; do not recreate it.
- Prefer one coherent, testable feature PR over sequences of small governance/formatting PRs.
- Do not repeat GitHub API queries, Actions dispatches, Docker builds or tests without a changed input, failed result or explicit verification need.
- Use GitHub Project native workflows for task progress; no scheduled or custom markdown status generator.
- CI must ignore documentation-only commits where safe, cancel superseded PR runs, cache dependency downloads and keep least-privilege tokens.
- Build Docker images only when code, dependencies or Docker inputs change; avoid unnecessary service containers and redundant runtime matrices.
- Inspect workflow queues/usage before triggering jobs. Never trigger repeated runs to bypass queued capacity.
- Cache Composer/npm/Docker layers when justified by observed repeated costs. Avoid caches whose restore/save overhead exceeds benefit.
- Maintain necessary correctness, security and acceptance gates: efficiency must not mean skipping required tests.
- Document any newly proposed service, action or automation with concrete benefit, cost/maintenance and a simpler alternative.

## Ponytail engineering rule (mandatory)

Follow the [Ponytail ladder](https://ponytail.dev/): first ask whether code is needed; reuse existing code; use PHP/Laravel native features; use Filament/Livewire built-ins and installed dependencies; only then write the minimum missing code. Avoid layers, helper classes, new dependencies or CI workflows without a concrete need. Preserve validation, access control, security, data integrity, accessibility and tests on nontrivial logic. Record intentionally deferred features and unverified checks in the PR. No benchmark percentage is assumed for this project.

## Delivery

Prefer one cohesive feature PR over many micro-PRs. Run relevant checks before merge; do not override failed checks. Keep main releasable. Do not alter old Next.js repository or launch DNS without explicit authorization. Production changes require backup, rollback and approval.

If blocked by unavailable tools, make best possible commits, state exactly what is unverified, and provide a single concrete next manual action.
