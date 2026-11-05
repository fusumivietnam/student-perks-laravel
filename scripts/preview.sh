#!/usr/bin/env bash
# Private preview for a laptop or GitHub Codespaces. No domain or hosting service needed.
set -euo pipefail
cd "$(dirname "$0")/.."

rebuild=false
case "${1:-}" in
  "") ;;
  --rebuild) rebuild=true ;;
  *) echo "Usage: bash scripts/preview.sh [--rebuild]" >&2; exit 2 ;;
esac

for command in docker openssl awk curl; do
  command -v "$command" >/dev/null || { echo "Missing required command: $command" >&2; exit 1; }
done
docker compose version >/dev/null

# Keep preview credentials out of Git. Existing credentials are never overwritten.
if [[ -f .env ]] && grep -Eq '^APP_ENV=production[[:space:]]*
  chmod 600 .env
  echo "Created untracked .env with separate preview credentials."
fi

set_env() {
  local key="$1" value="$2" file
  file="$(mktemp)"
  awk -v k="$key" -v v="$value" '
    BEGIN { written=0 }
    index($0, k "=")==1 { if (!written) print k "=" v; written=1; next }
    { print }
    END { if (!written) print k "=" v }
  ' .env > "$file"
  cat "$file" > .env
  rm -f "$file"
}

# Never expose Laravel debug information on a forwarded Codespaces port.
set_env APP_ENV local
set_env APP_DEBUG false
set_env APP_HTTP_PORT 127.0.0.1:8080
if ! grep -q '^APP_KEY=base64:.' .env; then
  set_env APP_KEY "base64:$(openssl rand -base64 32)"
fi
if ! grep -q '^DB_PASSWORD=.' .env; then
  set_env DB_PASSWORD "$(openssl rand -hex 24)"
fi

if [[ -n "${CODESPACE_NAME:-}" ]]; then
  domain="${GITHUB_CODESPACES_PORT_FORWARDING_DOMAIN:-app.github.dev}"
  set_env APP_URL "https://${CODESPACE_NAME}-8080.${domain}"
  echo "Codespaces: open the forwarded 8080 port in the Ports panel; keep its visibility PRIVATE."
else
  # Preserve the local user's APP_URL if it was already changed.
  echo "Local preview: http://localhost:8080"
fi

docker compose config --quiet

if [[ "$rebuild" == true ]] || [[ -z "$(docker compose ps -q app)" ]]; then
  docker compose up -d --build
else
  docker compose up -d
fi

docker compose exec -T app php artisan migrate --force

# Health checks use localhost; forwarded HTTPS may require GitHub login.
curl -fsS --retry 12 --retry-delay 2 --retry-connrefused http://127.0.0.1:8080/up >/dev/null
echo "Preview is ready. Use the 8080 link in the Codespaces Ports panel or http://localhost:8080."
echo "Admin: /admin (create an account with Filament; only is_admin=true may enter)."
echo "To rebuild after code/dependency changes: bash scripts/preview.sh --rebuild"
 .env; then
  echo "Refusing to run preview against an existing production .env. Use a separate checkout." >&2
  exit 1
fi
if [[ ! -f .env ]]; then
  cp .env.docker.example .env
  chmod 600 .env
  echo "Created untracked .env with separate preview credentials."
fi

set_env() {
  local key="$1" value="$2" file
  file="$(mktemp)"
  awk -v k="$key" -v v="$value" '
    BEGIN { written=0 }
    index($0, k "=")==1 { if (!written) print k "=" v; written=1; next }
    { print }
    END { if (!written) print k "=" v }
  ' .env > "$file"
  cat "$file" > .env
  rm -f "$file"
}

# Never expose Laravel debug information on a forwarded Codespaces port.
set_env APP_ENV local
set_env APP_DEBUG false
set_env APP_HTTP_PORT 127.0.0.1:8080
if ! grep -q '^APP_KEY=base64:.' .env; then
  set_env APP_KEY "base64:$(openssl rand -base64 32)"
fi
if ! grep -q '^DB_PASSWORD=.' .env; then
  set_env DB_PASSWORD "$(openssl rand -hex 24)"
fi

if [[ -n "${CODESPACE_NAME:-}" ]]; then
  domain="${GITHUB_CODESPACES_PORT_FORWARDING_DOMAIN:-app.github.dev}"
  set_env APP_URL "https://${CODESPACE_NAME}-8080.${domain}"
  echo "Codespaces: open the forwarded 8080 port in the Ports panel; keep its visibility PRIVATE."
else
  # Preserve the local user's APP_URL if it was already changed.
  echo "Local preview: http://localhost:8080"
fi

docker compose config --quiet

if [[ "$rebuild" == true ]] || [[ -z "$(docker compose ps -q app)" ]]; then
  docker compose up -d --build
else
  docker compose up -d
fi

docker compose exec -T app php artisan migrate --force

# Health checks use localhost; forwarded HTTPS may require GitHub login.
curl -fsS --retry 12 --retry-delay 2 --retry-connrefused http://127.0.0.1:8080/up >/dev/null
echo "Preview is ready. Use the 8080 link in the Codespaces Ports panel or http://localhost:8080."
echo "Admin: /admin (create an account with Filament; only is_admin=true may enter)."
echo "To rebuild after code/dependency changes: bash scripts/preview.sh --rebuild"
