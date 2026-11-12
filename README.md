# Priyadarshini

An artisan-as-brand marketplace for Indian craft, starting with Bihar. WordPress, WooCommerce and our own plugins.

## Layout
- `wp-content/plugins/pd-marketplace` data and rules: artisans, reviews, order allocations, follows, REST.
- `wp-content/plugins/pd-blocks` presentation: registered blocks and Interactivity API stores.
- `wp-content/themes/pd-theme` block theme (not started).
- `dev/` tests, a throwaway-site installer and dev stand-ins. Never deployed. The tests delete data; read `dev/LOCALWP.md` first.
- `docs/` SRS, block architecture, spike results and the go-live checklist.

## Deploying
Hostinger pulls one branch per plugin (`deploy/pd-marketplace`, `deploy/pd-blocks`) built by `tools/build-deploy-branches.sh --push`; each holds only that plugin's files. Set each Hostinger entry's folder to `public_html/wp-content/plugins/<plugin>`, never `public_html`.

Only `wp-content/plugins/pd-*` and `wp-content/themes/pd-theme` go to the server. Never sync the whole `wp-content`. See `docs/GO-LIVE.md`.

## Checks
`php dev/tests/allocator_pure.php` needs no WordPress. The other tests in `dev/tests` need a throwaway WordPress with `PD_TESTS_WIPE=yes`.

## Licence
Plugin headers declare GPL-2.0-or-later, as WordPress plugins must. No licence file has been added yet; that is an owner decision.
