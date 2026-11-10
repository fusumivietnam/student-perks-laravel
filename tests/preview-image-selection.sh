#!/usr/bin/env bash
# Lightweight unit test for the image-reuse gate; no Docker or network required.
set -euo pipefail
repo_root="$(cd "$(dirname "$0")/.." && pwd)"
source "$repo_root/scripts/preview-image.sh"
tmp="$(mktemp -d)"
trap 'rm -rf "$tmp"' EXIT

cd "$tmp"
git init -q
git config user.name "Preview Test"
git config user.email "preview-test@example.invalid"
mkdir -p app docs .github/workflows tests scripts resources
printf '%s\n' '<?php // initial' > app/example.php
git add -A
git commit -qm initial
base="$(git rev-parse HEAD)"

# All these files cannot affect the running Laravel image and must reuse it.
printf '%s\n' 'documentation' > docs/guide.md
printf '%s\n' 'workflow' > .github/workflows/ci.yml
printf '%s\n' 'acceptance test' > tests/FeatureTest.php
printf '%s\n' 'preview launcher' > scripts/preview.sh
git add -A
git commit -qm nonruntime
docs_revision="$(git rev-parse HEAD)"
preview_image_matches "$base" "$docs_revision" || {
  echo "FAIL: documentation/CI/test/preview-only edits caused rebuild" >&2; exit 1;
}

# A runtime file must always trigger a rebuild, even with unrelated docs changes.
printf '%s\n' '<?php // changed' > app/example.php
git add -A
git commit -qm runtime
app_revision="$(git rev-parse HEAD)"
if preview_image_matches "$base" "$app_revision"; then
  echo "FAIL: a changed application file reused stale image" >&2; exit 1
fi
preview_image_matches "$app_revision" "$app_revision" || {
  echo "FAIL: identical revision did not match" >&2; exit 1;
}

# Prevent unsafe reuse on missing labels or after history divergence.
if preview_image_matches unknown "$app_revision"; then
  echo "FAIL: unknown revision accepted" >&2; exit 1
fi
if preview_image_matches "$app_revision" "$docs_revision"; then
  echo "FAIL: newer image accepted for older checkout" >&2; exit 1
fi

printf '%s\n' 'PASS: docs-only reuse, application rebuild, exact match and stale-label guard.'
