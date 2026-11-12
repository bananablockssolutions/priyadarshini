#!/usr/bin/env bash
# Builds one deploy branch per plugin: deploy/pd-marketplace, deploy/pd-blocks (and deploy/pd-theme when it exists).
# Each branch holds ONLY that plugin's files at the branch root, so Hostinger's "Deploy from GitHub" can be pointed at
# public_html/wp-content/plugins/<plugin> and can never touch WordPress, WooCommerce, uploads, docs or tests.
# Usage: tools/build-deploy-branches.sh [--push]
set -euo pipefail
repo_root="$(git rev-parse --show-toplevel)"
cd "$repo_root"
src_sha="$(git rev-parse --short HEAD)"
for src in wp-content/plugins/pd-* wp-content/themes/pd-*; do
  [ -d "$src" ] || continue
  name="$(basename "$src")"
  tmp="$(mktemp -d)"
  cp -R --no-preserve=ownership "$src/." "$tmp/"
  find "$tmp" \( -name '.git*' -o -name 'tests' -o -name '*.log' -o -name '.DS_Store' \) -prune -exec rm -rf {} +
  (
    cd "$tmp"
    git init -q -b "deploy/$name"
    git config user.name "${GIT_AUTHOR_NAME:-Claude Code}"
    git config user.email "${GIT_AUTHOR_EMAIL:-noreply@anthropic.com}"
    git add -A
    git commit -q -m "Deploy build of $name from main@${src_sha}"
    echo "deploy/$name: $(git ls-files | wc -l) files, e.g. $(git ls-files | head -3 | tr '\n' ' ')"
    if [ "${1:-}" = "--push" ]; then
      git remote add origin "$(git -C "$repo_root" remote get-url origin)"
      git push --force origin "deploy/$name"
    fi
  )
  rm -rf "$tmp"
done
[ "${1:-}" = "--push" ] || echo "(dry run: nothing pushed; use --push)"
