# Student Perks Hub — Laravel

New Laravel implementation of Student Perks Hub. This repository replaces application code incrementally; the original [Next.js repository](https://github.com/fusumivietnam/student-perks-hub) remains the reference for UX, routes, business rules and existing data.

## Status

**Bootstrap pending.** This repository initially contains a manual bootstrap workflow and a migration plan. It is **not yet a running Laravel application**. Do not point production traffic here until acceptance tests pass.

## One-time initialization

1. Open [Actions → Bootstrap Laravel](https://github.com/fusumivietnam/student-perks-laravel/actions/workflows/bootstrap-laravel.yml).
2. Select **Run workflow** on `main`.
3. The workflow generates a Laravel 12 skeleton on a separate branch, runs the base test suite, and opens a pull request.
4. Review the PR and CI results, then merge it. The resulting repository supports ordinary Laravel development.

This one-time workflow needs GitHub Actions **Read and write permissions** for `GITHUB_TOKEN` and permission to create pull requests. If org policy blocks automated PR creation, the workflow will fail at that step; its generated branch is retained for opening a PR manually.

## Proposed architecture

- Laravel monolith (PHP + Blade + Livewire for public-facing dynamic pages).
- Filament admin panel, added after the Laravel skeleton is merged.
- PostgreSQL for production; SQLite in CI and local test runs.
- Laravel migrations as the schema source of truth for the new app.
- Conventional Laravel authentication and authorization. Supabase Auth data is **not** silently or automatically migrated.
- Queue, cache and storage providers chosen based on hosting constraints; avoid introducing Redis or a second frontend by default.

## Migration principles

See [docs/MIGRATION_PLAN.md](docs/MIGRATION_PLAN.md). Reuse design and business behavior, **not** the Next.js runtime or its CI/CD workflows. Keep the previous production deployment unchanged until the replacement passes parity and cutover checks.

No TinyFish, backups or scheduled production monitors are part of initial launch scope.
