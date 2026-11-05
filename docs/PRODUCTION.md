# Production deployment and release checks (M4)

## Deployment boundary

CI builds and tests images but **does not deploy**. Deploy to a VPS only after confirming TLS domain, server access and approved production rollout. PHP 8.5-FPM, PostgreSQL 18, Node 26 build, Nginx. App listens on `127.0.0.1:8080` by default so TLS must terminate at a separately configured, trusted reverse proxy. Do not publish port 5432.

## One-time VPS setup

1. Install supported Docker Engine with Compose plugin and provision persistent disk for Docker volumes.
2. Copy `.env.production.example` to an untracked `.env`. Set `APP_URL=https://your-domain`, strong unique `DB_PASSWORD`, and a random Laravel APP_KEY, generated with `printf 'base64:%s\n' "$(openssl rand -base64 32)"`. Never rotate an existing APP_KEY casually: this invalidates encrypted sessions/data.
3. Configure and test HTTPS certificates in the VPS reverse proxy before exposing public DNS. Forward `Host` and `X-Forwarded-Proto` accurately; configure trusted proxies only for actual trusted proxy IPs, not arbitrary internet clients.
4. Confirm `docker compose config --quiet`, then `docker compose up -d --build`.
5. Run migrations intentionally: `docker compose exec -T app php artisan migrate --force`. Create admin with Filament CLI and set `is_admin=true` **only for the intended account**, as described in README. Never seed default production credentials.
6. Populate reviewed Brands/Categories/Offers and legal Pages (privacy, terms, contact, about) in Filament before launch. Test their public routes, outbound HTTPS links, robots and sitemap.
7. Smoke-check `/up`, `/`, `/offers`, `/articles`, `/admin/login` and media URLs over the final HTTPS domain. Test desktop/mobile and keyboard access manually; CI HTTP checks alone do not verify visual accessibility.

## Safe repeatable release

Before any migration or application image replacement, make a **verified recoverable** database and media backup; maintain at least one out-of-host copy. For a small deployment, a manual dump is acceptable initially:

```bash
# Execute on the VPS from project directory; treat outputs as sensitive.
docker compose exec -T db sh -c 'exec pg_dump -U "$POSTGRES_USER" -d "$POSTGRES_DB" -Fc' > student-perks-predeploy.dump
# Back up the persistent uploaded media (do not delete or reset the Docker volume).
docker compose run --rm --no-deps --entrypoint tar app -czf - -C /var/www/html/storage app/public > student-perks-media.tar.gz
```

Verify both files are nonempty and copy to secure off-host storage. A dump file on the VPS alone is **not** a disaster recovery backup. Restrict access and retention for both files.

After approval and backup, update image and services, migrate and smoke:

```bash
docker compose build app
docker compose up -d --no-build
docker compose exec -T app php artisan migrate --force
curl -f https://your-domain/up
```

**Rollback:** deploy the previously verified Git commit/image. If a migration changed the schema incompatibly, rolling back the app image alone may be unsafe. Restore the pre-deploy database dump and uploads in a **controlled maintenance window**, only after verifying destination identity and recovery impact. Never use `docker compose down -v` or `migrate:fresh` in production.

Restore rehearsal (in **isolated throwaway environment only**) must be completed before declaring M4 DONE: `pg_restore --clean --if-exists` into a dedicated disposable DB, extract media backup into isolated media volume, and verify sample rows/media links. Do not issue `pg_restore --clean` against production.

## Readiness gate

- [ ] Real VPS provisioned, TLS and domain verified.
- [ ] Production secrets configured, only localhost proxy port exposed, no database exposure.
- [ ] PostgreSQL and uploads volumes survive restart/redeploy.
- [ ] Legal/editorial content ready and admin login secured.
- [ ] Off-host backup copied and restore rehearsal successful.
- [ ] Responsive/keyboard/manual browser acceptance complete.
- [ ] Final-domain HTTPS smoke, migration and rollback plan verified.

CI proves the container build, migrations, unit/integration tests, secure Nginx responses and local HTTP smoke. **It cannot prove** TLS, off-host backup, domain or actual VPS deployment.
