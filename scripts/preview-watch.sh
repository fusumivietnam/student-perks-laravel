#!/usr/bin/env bash
# Codespaces-only background updater. Never force-resets branches or deletes volumes.
set -euo pipefail
cd "$(dirname "$0")/.."
source scripts/preview-image.sh

state_dir=".git"
pidfile="$state_dir/preview-watch.pid"
logfile="$state_dir/preview-watch.log"

case "${1:-}" in
  start)
    if [[ -z "${CODESPACE_NAME:-}" ]]; then
      echo "Auto-sync is only started by Codespaces." >&2
      exit 0
    fi
    if [[ -f "$pidfile" ]] && kill -0 "$(cat "$pidfile")" 2>/dev/null; then
      echo "Codespaces preview auto-sync already running."
      exit 0
    fi
    nohup bash scripts/preview-watch.sh run >> "$logfile" 2>&1 < /dev/null &
    echo "$!" > "$pidfile"
    echo "Preview auto-sync started (checks origin/main every 5 minutes). Log: $logfile"
    ;;
  stop)
    if [[ -f "$pidfile" ]] && kill -0 "$(cat "$pidfile")" 2>/dev/null; then
      kill "$(cat "$pidfile")"
    fi
    rm -f "$pidfile"
    echo "Preview auto-sync stopped. No containers or volumes changed."
    ;;
  status)
    if [[ -f "$pidfile" ]] && kill -0 "$(cat "$pidfile")" 2>/dev/null; then
      echo "Preview auto-sync is running (PID $(cat "$pidfile"))."
    else
      echo "Preview auto-sync is not running."
    fi
    ;;
  run)
    [[ -n "${CODESPACE_NAME:-}" ]] || { echo "Codespaces only." >&2; exit 1; }
    trap 'rm -f "$pidfile"' EXIT
    last_failed_revision=""
    while true; do
      # Do not touch a working tree with edits, or a checked-out feature branch.
      if [[ "$(git symbolic-ref --short -q HEAD || true)" != "main" ]] \
         || [[ -n "$(git status --porcelain --untracked-files=no)" ]]; then
        echo "$(date -u +%FT%TZ) Skipping: not on clean main."
      elif ! docker info >/dev/null 2>&1; then
        echo "$(date -u +%FT%TZ) Docker daemon is not ready; retrying on next check."
      elif ! git fetch -q origin main; then
        echo "$(date -u +%FT%TZ) Fetch failed; no files modified."
      else
        remote="$(git rev-parse refs/remotes/origin/main)"
        local_sha="$(git rev-parse HEAD)"
        app_id="$(docker compose ps -q app 2>/dev/null || true)"
        image_sha=""
        if [[ -n "$app_id" ]]; then
          image_sha="$(docker inspect --format '{{index .Config.Labels "org.opencontainers.image.revision"}}' "$app_id" 2>/dev/null || true)"
        fi
        if ! git merge-base --is-ancestor "$local_sha" "$remote"; then
          echo "$(date -u +%FT%TZ) Local main diverged; refusing reset."
        elif [[ "$remote" != "$local_sha" ]] || ! preview_image_matches "$image_sha" "$local_sha"; then
          if [[ "$last_failed_revision" == "$remote" ]]; then
            echo "$(date -u +%FT%TZ) Skipping failed revision ${remote:0:12} until watcher restart or a new commit."
          else
            echo "$(date -u +%FT%TZ) Updating preview to ${remote:0:12}."
            if git merge --ff-only "$remote" && bash scripts/preview.sh; then
              last_failed_revision=""
              echo "$(date -u +%FT%TZ) Preview updated and checks passed."
            else
              last_failed_revision="$remote"
              echo "$(date -u +%FT%TZ) Update failed; see this log. Not retrying the same revision in a loop."
            fi
          fi
        fi
      fi
      sleep 300
    done
    ;;
  *)
    echo "Usage: bash scripts/preview-watch.sh {start|stop|status}" >&2
    exit 2
    ;;
esac
