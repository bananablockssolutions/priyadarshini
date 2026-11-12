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
# One extra branch for the "whole wp-content" mode: wp-content layout, with a whitelist .gitignore so that
# uploads (the media library), other plugins and themes can never become tracked, even by accident.
tmp="$(mktemp -d)"
mkdir -p "$tmp/plugins" "$tmp/themes"
for src in wp-content/plugins/pd-*; do [ -d "$src" ] && cp -R --no-preserve=ownership "$src" "$tmp/plugins/"; done
for src in wp-content/themes/pd-*; do [ -d "$src" ] && cp -R --no-preserve=ownership "$src" "$tmp/themes/"; done
cat > "$tmp/.gitignore" <<'IGN'
# Track only our own code. Everything else in wp-content lives on the server only.
/*
!/.gitignore
!/plugins/
/plugins/*
!/plugins/pd-*/
!/themes/
/themes/*
!/themes/pd-*/
IGN
find "$tmp" \( -name '.git*' ! -name '.gitignore' -o -name 'tests' -o -name '*.log' -o -name '.DS_Store' \) -prune -exec rm -rf {} +
(
  cd "$tmp"
  git init -q -b deploy/wp-content
  git config user.name "${GIT_AUTHOR_NAME:-Claude Code}"
  git config user.email "${GIT_AUTHOR_EMAIL:-noreply@anthropic.com}"
  mkdir -p uploads && echo x > uploads/never-tracked.jpg   # proof the whitelist works: must NOT be added
  git add -A
  git commit -q -m "Deploy build of wp-content (pd-* only) from main@${src_sha}"
  if git ls-files | grep -q '^uploads/'; then echo "ERROR: uploads would be tracked"; exit 1; fi
  echo "deploy/wp-content: $(git ls-files | wc -l) files tracked, uploads excluded"
  if [ "${1:-}" = "--push" ]; then
    git remote add origin "$(git -C "$repo_root" remote get-url origin)"
    git push --force origin deploy/wp-content
  fi
)
rm -rf "$tmp"
[ "${1:-}" = "--push" ] || echo "(dry run: nothing pushed; use --push)"
