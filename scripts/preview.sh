#!/usr/bin/env bash
# Private preview on a laptop or GitHub Codespaces.
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

# Never transform a production installation into a preview.
if [[ -f .env ]] && grep -Eq '^APP_ENV=production[[:space:]]*$' .env; then
  echo "Refusing production .env. Run preview in a separate checkout." >&2
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

# Preview ports may be forwarded beyond the local machine.
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
  echo "Codespaces: use the forwarded 8080 link in the Ports panel; keep visibility PRIVATE."
else
  echo "Laptop: open http://localhost:8080."
fi

docker compose config --quiet
if [[ "$rebuild" == true ]] || [[ -z "$(docker compose ps -q app)" ]]; then
  docker compose up -d --build
else
  docker compose up -d
fi
docker compose exec -T app php artisan migrate --force
curl -fsS --retry 12 --retry-delay 2 --retry-connrefused http://127.0.0.1:8080/up >/dev/null

echo "Preview is ready. Admin route: /admin (provision a Filament admin yourself)."
echo "Rebuild code/dependencies: bash scripts/preview.sh --rebuild"
