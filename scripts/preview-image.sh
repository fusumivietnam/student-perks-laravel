#!/usr/bin/env bash
# Shared by preview launcher, diagnostic and Codespaces watcher.
# Return success when the running image and local commit have identical runtime inputs.
# Dockerfile excludes .github and tests; docs and local preview tooling do not
# affect the Laravel/Nginx runtime. Everything else requires an image rebuild.
preview_image_matches() {
  local image_revision="${1:-}" target_revision="${2:-}"
  [[ "$image_revision" =~ ^[0-9a-f]{40}$ && "$target_revision" =~ ^[0-9a-f]{40}$ ]] || return 1
  git cat-file -e "${image_revision}^{commit}" 2>/dev/null || return 1
  git cat-file -e "${target_revision}^{commit}" 2>/dev/null || return 1
  git merge-base --is-ancestor "$image_revision" "$target_revision" || return 1
  git diff --quiet "$image_revision" "$target_revision" -- . \
    ':(exclude).github/**' \
    ':(exclude)docs/**' \
    ':(exclude)tests/**' \
    ':(exclude)README.md' \
    ':(exclude)AGENTS.md' \
    ':(exclude)scripts/preview.sh' \
    ':(exclude)scripts/preview-check.sh' \
    ':(exclude)scripts/preview-watch.sh' \
    ':(exclude)scripts/preview-image.sh'
}
