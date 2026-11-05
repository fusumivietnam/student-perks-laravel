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
5. For changes requiring an image rebuild run `bash scripts/preview.sh --rebuild`. For regular restarts use `bash scripts/preview.sh`.

**Admin account:** run `docker compose exec app php artisan make:filament-user`, then promote only the intended account using `docker compose exec app php artisan tinker` and `App\\Models\\User::where('email', 'YOUR_EMAIL')->update(['is_admin' => true]);`. There is no default admin password or public registration.

**Privacy:** Codespaces private forwarding is accessible only after GitHub authentication; switching a port to **Public** removes that protection. Do not switch the admin preview to Public. Codespaces may stop when idle, so this is *not* a 24/7 staging host. Preview data lives in Codespaces Docker volumes and may be deleted when the Codespace is deleted. Use non-sensitive test data.

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
