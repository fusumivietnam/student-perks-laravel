#!/usr/bin/env bash
# Read-only diagnostic for the private Laravel preview; never changes Docker volumes.
set -euo pipefail
cd "$(dirname "$0")/.."

fail() { echo "FAIL: $*" >&2; exit 1; }
command -v docker >/dev/null || fail "Docker is not installed."
command -v curl >/dev/null || fail "curl is not installed."
docker compose version >/dev/null || fail "Docker Compose is not available."
[[ -f .env ]] || fail "Missing .env; run bash scripts/preview.sh first."

db_id="$(docker compose ps -q db)"
[[ -n "$db_id" ]] || fail "Database container is absent; inspect docker compose ps."
db_health="$(docker inspect --format "{{if .State.Health}}{{.State.Health.Status}}{{else}}unknown{{end}}" "$db_id")"
if [[ "$db_health" != "healthy" ]]; then
  echo "Database health: $db_health" >&2
  fail "Check docker compose logs db --tail=80; do not delete Docker volumes."
fi
echo "PASS: PostgreSQL Docker health is healthy."

if ! docker compose exec -T -u postgres db sh -c 'PGCONNECT_TIMEOUT=5 timeout 12s psql -U "$POSTGRES_USER" -d "$POSTGRES_DB" -Atqc "SELECT 1"' | grep -qx 1; then
  fail "PostgreSQL cannot complete SELECT 1. Inspect database logs."
fi
echo "PASS: PostgreSQL query SELECT 1."

local_url="http://127.0.0.1:8080"
if ! curl -fsS --connect-timeout 3 --max-time 15 "$local_url/up" >/dev/null; then
  fail "Laravel /up is unavailable; check docker compose ps and docker compose logs app --tail=80."
fi
echo "PASS: Laravel /up."

html="$(curl -fsS --connect-timeout 3 --max-time 15 "$local_url/")" || fail "Homepage request failed."
expected_url="$(sed -n 's/^APP_URL=//p' .env | tail -n 1)"
expected_url="${expected_url%/}"
[[ "$expected_url" == http://* || "$expected_url" == https://* ]] || fail "APP_URL in .env is invalid."

if [[ -n "${CODESPACE_NAME:-}" && "$expected_url" != https://*.app.github.dev ]]; then
  fail "Codespaces APP_URL must be its private HTTPS .app.github.dev origin; rerun bash scripts/preview.sh."
fi

for target in "href=\"$expected_url/offers\"" "action=\"$expected_url/search\""; do
  if [[ "$html" != *"$target"* ]]; then
    fail "Navigation/search targets do not use APP_URL ($expected_url). Restart the app with the latest image: bash scripts/preview.sh --rebuild."
  fi
done
echo "PASS: Navigation/search links use APP_URL."

for extension in css js; do
  asset_url="$(printf "%s" "$html" | grep -oE '(href|src)="[^"]+/build/assets/[^"]+\.'"$extension"'"' | head -n 1 | cut -d '"' -f 2 || true)"
  [[ -n "$asset_url" ]] || fail "Missing $extension asset link in homepage HTML."
  [[ "$asset_url" == "$expected_url"/build/assets/* ]] || fail "$extension asset uses incorrect origin: $asset_url (expected $expected_url)."
  asset_path="${asset_url#"$expected_url"}"
  curl -fsS --connect-timeout 3 --max-time 15 -o /dev/null "$local_url$asset_path" || fail "$extension asset not served by Nginx: $asset_path"
  echo "PASS: $extension asset has the right origin and HTTP 200."
done

echo "PASS: Private preview checks complete. Manually inspect responsive layout and /admin in the browser."
