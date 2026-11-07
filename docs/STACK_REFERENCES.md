# Dependency and source-reference register (2026-11-07)

This is a **small, source-controlled reference index**, not a new package manager, dependency framework, bibliography generator, or a claim that all packages are risk-free. Exact versions and transitive packages are resolved by committed `composer.lock` and `package-lock.json`. Run `composer show --locked` and `npm ls --depth=0` when an exact installed version is needed.

| Layer | Direct package / runtime | Verified source of intent | Primary official reference |
|---|---|---|---|
| PHP app | Laravel `^13.17` | `composer.json`, routes/controllers/Eloquent/Blade | https://laravel.com/docs |
| Admin CMS | Filament `^5.0` | `composer.json`, `app/Filament/Resources` | https://filamentphp.com/docs |
| PHP utility | Laravel Tinker `^3.0` | `composer.json`, explicit admin setup | https://laravel.com/docs/artisan |
| PHP testing | PHPUnit `^12.5.12`, Laravel test helpers | `composer.json`, `tests/Feature` | https://docs.phpunit.de/ |
| PHP formatting | Laravel Pint `^1.27` | `composer.json` | https://laravel.com/docs/pint |
| Frontend | Tailwind CSS `^4.0.0`, `@tailwindcss/vite` `^4.0.0` | `package.json`, `resources/css/app.css` | https://tailwindcss.com/docs |
| Bundler | Vite `^8.0.0`, `laravel-vite-plugin` `^3.1` | `package.json`, `vite.config.js` | https://vite.dev/guide/ |
| DB | PostgreSQL `18` | `compose.yaml`, Laravel migrations | https://www.postgresql.org/docs/18/ |
| Containers | Docker Compose / multi-stage Dockerfile | `compose.yaml`, `Dockerfile` | https://docs.docker.com/compose/ |
| CI/build cache | GitHub Actions, Docker Buildx | `.github/workflows/ci.yml` | https://docs.docker.com/build/ci/github-actions/cache/ |

**Development-only dependencies:** Faker, Pail, Pao, Mockery, Collision and concurrently are present in the manifests; do not add new dependencies merely to document or replace them. Actual package inventory is larger than this direct-dependency table and is fully specified in the lockfiles.

## Principles and acceptance rules

1. Prefer native Laravel, Filament, Blade, Tailwind and browser capabilities first. An added package needs an explicit product gap, maintained upstream, license, compatible PHP/Laravel versions, tests and clear owner/maintenance cost.
2. Use the official docs as a reference for framework behavior. Source code, lockfiles, migrations and passing CI are the evidence of **what this project actually uses**. A documentation link alone does not prove a feature was implemented.
3. Avoid embedding bibliographic references in every ordinary source comment. For non-obvious security, data or framework workarounds, add a **brief** comment and an upstream issue/docs link where it materially explains the decision.
4. Review `composer audit` and `npm audit --omit=dev` before production release; triage actual findings and lockfile upgrades on a dedicated PR. Network advisories, false positives and dev-only dependencies need separate judgment. Do not turn audit reports into unconditional green-pass claims.
5. No hidden dependencies in preview: `scripts/preview-watch.sh` uses Bash, Git and Docker already present in Codespaces; no paid CI action, background cloud account, database reset or new image registry.

## Explicitly not adopted

Separate SPA/React frontend, Redis, search server, user verification service, SEO sitemap package, media transformation library and permission framework. Current scope is handled by Laravel/Filament/Blade and PostgreSQL. Re-evaluate only against a demonstrated feature gap.
