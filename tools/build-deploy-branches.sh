#!/usr/bin/env bash
# Builds one deploy branch per plugin: deploy/pd-marketplace, deploy/pd-blocks (and deploy/pd-theme when it exists).
# Each branch holds ONLY that plugin's files at the branch root, so Hostinger's "Deploy from GitHub" can be pointed at
# public_html/wp-content/plugins/<plugin> and can never touch WordPress, WooCommerce, uploads, docs or tests.
# Usage: tools/build-deploy-branches.sh [--push]
set -euo pipefail
repo_root="$(git rev-parse --show-toplevel)"
cd "$repo_root"
src_sha="$(git rev-parse --short HEAD)"
origin_url="$(git remote get-url origin)"

# Start the throwaway repo in the current directory ON TOP OF the branch's previous tip, so every deploy branch keeps
# one straight history. Hostinger's Deploy runs a git pull; a force-pushed fresh (orphan) commit each time gives it
# unrelated histories and the pull fails, leaving the old files on the server (found 2026-11-17).
start_on_previous_tip() {
  local branch="$1"
  git init -q -b "$branch"
  git config user.name "${GIT_AUTHOR_NAME:-Claude Code}"
  git config user.email "${GIT_AUTHOR_EMAIL:-noreply@anthropic.com}"
  git remote add origin "$origin_url"
  if git fetch -q --depth=1 origin "$branch" 2>/dev/null; then
    git reset -q --soft FETCH_HEAD   # index = previous tree; `git add -A` then records changes and deletions
    echo "deploy/${branch#deploy/}: building on previous tip $(git rev-parse --short FETCH_HEAD)"
  fi
}
commit_and_push() {
  local branch="$1" msg="$2" push="$3"
  git add -A
  if git rev-parse -q --verify HEAD >/dev/null && git diff --cached --quiet; then
    echo "$branch: no changes"
    return 0
  fi
  git commit -q -m "$msg"
  if [ "$push" = "--push" ]; then
    git push origin "$branch"   # never --force: the server's checkout must always be an ancestor
  fi
}
for src in wp-content/plugins/pd-* wp-content/themes/pd-*; do
  [ -d "$src" ] || continue
  name="$(basename "$src")"
  tmp="$(mktemp -d)"
  cp -R --no-preserve=ownership "$src/." "$tmp/"
  find "$tmp" \( -name '.git*' -o -name 'tests' -o -name '*.log' -o -name '.DS_Store' \) -prune -exec rm -rf {} +
  (
    cd "$tmp"
    start_on_previous_tip "deploy/$name"
    commit_and_push "deploy/$name" "Deploy build of $name from main@${src_sha}" "${1:-}"
    echo "deploy/$name: $(git ls-files | wc -l) files, e.g. $(git ls-files | head -3 | tr '\n' ' ')"
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
  start_on_previous_tip deploy/wp-content
  mkdir -p uploads && echo x > uploads/never-tracked.jpg   # proof the whitelist works: must NOT be added
  git add -A
  if git ls-files | grep -q '^uploads/'; then echo "ERROR: uploads would be tracked"; exit 1; fi
  commit_and_push deploy/wp-content "Deploy build of wp-content (pd-* only) from main@${src_sha}" "${1:-}"
  echo "deploy/wp-content: $(git ls-files | wc -l) files tracked, uploads excluded"
)
rm -rf "$tmp"
[ "${1:-}" = "--push" ] || echo "(dry run: nothing pushed; use --push)"
