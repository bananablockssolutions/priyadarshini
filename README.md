# Priyadarshini

An artisan-as-brand marketplace for Indian craft, starting with Bihar. WordPress, WooCommerce and our own plugins.

## Layout
- `wp-content/plugins/pd-marketplace` data and rules: artisans, reviews, order allocations, follows, REST.
- `wp-content/plugins/pd-blocks` presentation: registered blocks and Interactivity API stores.
- `wp-content/themes/pd-theme` block theme (not started).
- `dev/` tests, a throwaway-site installer and dev stand-ins. Never deployed. The tests delete data; read `dev/LOCALWP.md` first.
- `docs/` SRS, block architecture, spike results and the go-live checklist.

## Deploying
`tools/build-deploy-branches.sh --push` builds `deploy/<name>` branches (one per plugin or theme, only that code at the branch root) and `deploy/wp-content`: a wp-content layout holding only `plugins/pd-*` and `themes/pd-*`, plus a whitelist `.gitignore`, so uploads (the media library), WooCommerce and other plugins are never tracked. Point Hostinger at `public_html/wp-content` for `deploy/wp-content`, or at `public_html/wp-content/plugins/<plugin>` for a per-plugin branch. Never point it at `public_html`. Trial in an empty scratch folder first: it is not yet known whether Hostinger's pull deletes untracked files.

## Checks
`php dev/tests/allocator_pure.php` needs no WordPress. The other tests in `dev/tests` need a throwaway WordPress with `PD_TESTS_WIPE=yes`.

## Licence
Plugin headers declare GPL-2.0-or-later, as WordPress plugins must. No licence file has been added yet; that is an owner decision.
