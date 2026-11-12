# Private preview — laptop or GitHub Codespaces

Run the **actual Laravel 13 / Filament 5 / PostgreSQL 18 / Nginx** stack before choosing any domain, logo or brand. This preview uses `compose.yaml` unchanged and the same tested Docker image as production. No third-party tunnel, public cloud database or permanent hosting is necessary.

## GitHub Codespaces (recommended for browser-based work)

1. Open [the repository](https://github.com/fusumivietnam/student-perks-laravel) → **Code → Codespaces → Create codespace on main**. The repository's `.devcontainer/devcontainer.json` installs Docker inside the development container and forwards port **8080**.
2. In the Codespaces terminal run **once**:
   ```bash
   bash scripts/preview.sh
   ```
3. Open the **Ports** panel and open the forwarded **8080** link. Keep **Port Visibility = Private**; GitHub authenticates the creator of a private Codespace port.
4. Check `/`, `/offers`, `/brands`, `/search`, `/articles` and `/admin`. Start with empty data; create sample content through Filament to test publishing.
5. The Codespaces `postStartCommand` starts a watcher on startup/resume. Every five minutes it checks clean `main`, fast-forwards when needed and compares runtime-relevant Git changes with the running Docker image. Docs/CI/test-only updates use a **read-only preview health check**, without Docker rebuild, Compose restart or migration. Runtime changes use the normal launcher. Use `bash scripts/preview.sh --update` only for an immediate update; `--rebuild` is an explicit override for uncommitted runtime changes.

**Admin account:** run `docker compose exec app php artisan make:filament-user`, then promote only the intended account using `docker compose exec app php artisan tinker` and `App\\Models\\User::where('email', 'YOUR_EMAIL')->update(['is_admin' => true]);`. There is no default admin password or public registration.

**Privacy:** Codespaces private forwarding is accessible only after GitHub authentication; switching a port to **Public** removes that protection. Do not switch the admin preview to Public. Codespaces may stop when idle, so this is *not* a 24/7 staging host. Preview data lives in Codespaces Docker volumes and may be deleted when the Codespace is deleted. Use non-sensitive test data.

## Codespaces auto-sync: zero repeated commands

The GitHub Codespaces configuration starts `scripts/preview-watch.sh` after startup (no new paid service, no GitHub PAT, no scheduled GitHub Actions). Once enabled, it updates only a **clean checked-out `main`** by `git fetch` + fast-forward. It reuses the existing healthy image after docs/CI/test-only commits and only runs the full launcher for runtime changes or recovery. It **does not** switch branches, reset local changes, delete data or run the demo seeder automatically. Do not commit secrets.

**One-time activation in an existing running Codespace** (its new `postStartCommand` will also activate on the next Codespace restart):

```bash
git pull --ff-only
bash scripts/preview-watch.sh start
```

The watcher checks the checked-out commit and thereafter new commits while the Codespace is running. **If the watcher was already running before PR #46, restart it once after the source update** using `bash scripts/preview-watch.sh stop && bash scripts/preview-watch.sh start` so the running Bash process loads the new logic. Check or disable it without touching the application:

```bash
bash scripts/preview-watch.sh status
tail -n 40 .git/preview-watch.log
bash scripts/preview-watch.sh stop
```

The process skips a dirty working tree or a feature branch. A failed application build for a given commit is reported in the log and not retried on every poll; restart the watcher after resolving the cause. Codespaces stopping or being deleted stops the watcher: it is not a continuously hosted staging environment. To re-enable after stopping, run `start` or restart the Codespace.

**Cache behavior:** `PREVIEW_SOURCE_SHA` is applied as image metadata *after* dependency and extension build steps, preserving cache when only Laravel/Blade/Tailwind source changes. First rebuild after Dockerfile changes may still recompile once; subsequent commits should reuse expensive PHP layers. Docker Buildx GitHub Actions cache is separate from Codespaces-local Docker cache.

## Single-pass M2 preview acceptance (no rebuild loop)

Once the private Codespaces preview is running, complete the following **on the existing Codespace**. The watcher performs source updates automatically; do not manually `git pull` or force `--rebuild` after each merged PR. If it is paused, resume the Codespace first.

1. Inspect the existing read-only server check: `bash scripts/preview-check.sh`. It validates database connectivity, source/image compatibility, Laravel health, public navigation and delivered CSS/JS without modifying volumes or running migrations.
2. After publishing **real reviewed** CMS content, run `docker compose exec -T app php artisan launch:check` once. A BLOCK is an editorial task, **not** a reason to reset data, rebuild, seed fictional discounts or publish placeholder legal text. PASS is a content-only gate, not deployment approval.
3. Using the **private** forwarded port 8080 in an authenticated browser, check desktop width and mobile width: homepage, offer cards, search/filter/pagination, offer detail, brand/category pages, article and all five policy pages. Confirm drafts stay private; check the browser's network requests for CSS and images.
4. Sign in to `/admin` with the existing admin and exercise a disposable article cover and brand logo through the Filament UI, public rendering, draft preview/publish/unpublish and keyboard-only focus/labels. Do not create demo users or paste real credentials into issues/chat.
5. Record concrete evidence in [M2 issue #2](https://github.com/fusumivietnam/student-perks-laravel/issues/2): browser/device and viewport widths, pages/actions checked, defects with URL and screenshot (redact private data). Keep any untested row marked **not verified**. [M4 issue #4](https://github.com/fusumivietnam/student-perks-laravel/issues/4) remains open for final-domain TLS, VPS deployment approval and off-host backup/isolated restore.

**Evidence boundary:** GitHub CI and the existing integration tests do not exercise the owner's private Codespace browser. The GitHub repository connector cannot read the Codespace's private browser session or current Docker/volume state, and there is no automated Production deployment. No extra CI workflow or manual rebuild cycle is required for this acceptance checklist.

## Laptop with Docker

Requirements: Docker Engine / Docker Desktop with Compose, Bash, OpenSSL, curl.

```bash
git clone https://github.com/fusumivietnam/student-perks-laravel.git
cd student-perks-laravel
bash scripts/preview.sh
```

Open **http://localhost:8080**. The script creates untracked `.env` with random local APP_KEY and DB_PASSWORD, keeps existing secrets if already present, starts the stack, runs migrations and checks `/up`.

## Operational notes

- **No production data.** Do not put real student documents, personal details or production credentials into a test Codespace.
- `.env` remains untracked. Never commit or share its contents. Existing `.env` is reused; verify it does not contain production credentials before running.
- Local preview is bound to `127.0.0.1:8080` inside the host; Codespaces exposes it via GitHub's private port forward.
- `APP_URL` is set to the Codespaces HTTPS forward URL when Codespaces environment variables are available. If authentication redirects or links have an incorrect scheme, verify the forwarded port URL and HTTPS/proxy configuration before changing privacy to Public.
- Stop services without deleting volumes: `docker compose down`. Avoid `docker compose down -v` unless you intentionally want to destroy disposable data.
- Preview is a development-only environment; it does not replace final-domain TLS, off-host backup/restore rehearsal or manual mobile/keyboard review.


## One-command read-only acceptance check

After Docker has started (including after a Codespaces pause/resume), run:

```bash
bash scripts/preview-check.sh
```

The check does **not** modify `.env`, run migrations, restart containers, or delete Docker volumes. It verifies the actual PostgreSQL Docker health status and `SELECT 1`, Laravel `/up`, public navigation/search URLs, and that the compiled CSS and JavaScript files are downloadable through Nginx. Codespaces assets must point to the private HTTPS forwarding origin, not `localhost`.

If the check fails at database health, first inspect `docker compose logs db --tail=80`. A PostgreSQL process reporting *ready to accept connections* does not guarantee that authentication and queries finish; check `docker compose exec -T -u postgres db sh -c 'timeout 12s psql -U "$POSTGRES_USER" -d "$POSTGRES_DB" -Atqc "SELECT 1"'`. Avoid `down -v`, database resets, and repeated rebuilds for a database connection issue. On returning to a stopped Codespace, confirm the DB is healthy before running `bash scripts/preview.sh --rebuild` for the PHP code.

The check also compares the Git workspace commit with the revision embedded in the running Laravel Docker image. An image built from an older commit is accepted only if every intervening committed change is outside the runtime inputs (such as docs, CI or tests). Otherwise the diagnostic fails with a clear instruction to run `bash scripts/preview.sh`; it never silently accepts runtime-stale images. A green check confirms server-side delivery only; manually inspect browser layout, mobile breakpoints, keyboard navigation and Filament login.

## Troubleshooting: unstyled page / links to localhost

If the page looks like plain HTML (blue underlined links, serif text, no layout), inspect the page source. If stylesheet, JavaScript or navigation URLs start with `http://localhost:8080` while the browser address is `https://...-8080.app.github.dev`, the HTTPS forwarding origin has not been applied.

In your **existing Codespace** terminal, update the repository and reapply preview settings:

```bash
git pull --ff-only
bash scripts/preview.sh
```

The launcher detects changes to runtime inputs between the image's labeled Git revision and local `HEAD` and rebuilds only when necessary. An image without a valid revision label will be rebuilt once; docs-only updates do not force another rebuild. It keeps PostgreSQL, admin users and uploaded media in the existing named Docker volumes. Never use `down -v`.

Hard refresh the forwarded port page. The generated `/build/assets/*.css`, `/build/assets/*.js` and navigation links should use the same `https://...app.github.dev` origin as the browser, not localhost. Do **not** switch the port to Public just to fix missing styles.

If the problem continues, inspect `grep '^APP_URL=' .env` (safe to display; never display APP_KEY or DB_PASSWORD), `docker compose ps` and the Network panel CSS request status. Reopen the forwarded port **8080** in the Ports panel. If generated URLs are correct but the CSS endpoint is 404, use `bash scripts/preview.sh --rebuild` to refresh the Docker image and asset volume.

## Optional fictional content for M2 UI acceptance

Run only in your existing **local/Codespaces** preview after confirming `bash scripts/preview-check.sh` passes:

```bash
docker compose exec -T app php artisan db:seed --class=PreviewDemoSeeder
```

The explicit `PreviewDemoSeeder` creates **3 fictional brands, 3 categories, 5 offers (4 public + 1 draft), and 2 articles (1 public + 1 draft)**. It never creates or changes users, does not populate legal policies, uses only `https://example.org/` as an inert placeholder link, and preserves existing records on rerun. Slugs start with `demo-`; titles and descriptions explicitly say DEMO and do not imply real partner arrangements.

The seeder refuses environments other than `APP_ENV=local` with localhost or Codespaces `APP_URL`. It is **not** wired to `DatabaseSeeder` or production deployment. Run it only by specifying `--class=PreviewDemoSeeder`. The demo items should be deleted in Filament before any public launch.

Acceptance: visit `/`, `/offers`, `/offers/demo-student-design-kit`, `/brands/demo-cloud-studio`, `/categories/demo-software`, `/articles/demo-how-to-compare-offers`. Confirm draft paths `/offers/demo-unpublished-offer` and `/articles/demo-editorial-draft` return 404. In Filament edit a demo offer title, save, and confirm the public page updates. Re-check mobile and keyboard navigation. Do not treat demo records as legally reviewed discounts.
