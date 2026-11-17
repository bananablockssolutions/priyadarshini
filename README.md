# Priyadarshini

An artisan-as-brand marketplace for Indian craft, starting with Bihar. WordPress, WooCommerce and our own plugins.

## Layout
- `wp-content/plugins/pd-marketplace` data and rules: artisans, reviews, order allocations, follows, REST.
- `wp-content/plugins/pd-blocks` presentation: registered blocks and Interactivity API stores.
- `wp-content/themes/pd-theme` block theme.
- `dev/` tests, a throwaway-site installer and dev stand-ins. Never deployed. The tests delete data; read `dev/LOCALWP.md` first.
- `docs/` SRS, block architecture, spike results and the go-live checklist.

## pd-theme (0.2.0) and pd-blocks (0.2.0)
`wp-content/themes/pd-theme`: block theme with the Pigment palette, Yatra One, Mukta and Kalam (self-hosted, OFL, Latin plus Devanagari), paper texture, templates for home, artisans, artisan, works, craft, work, bag, checkout (calm header), order confirmation, search, pages and posts. Every template is `pd/app-shell` (persistent header) + `pd/routed-main` (the region client navigation swaps) + footer.

`pd-blocks` 0.2.0 adds `pd/app-shell`, `pd/routed-main`, `pd/work-card`, `pd/work-gallery` and a first `pd/buy-box`, with Interactivity API stores `pd/navigation` (Router navigation, focus, Back, shared-element transitions, live regions), `pd/shell` (menu, search in the context row, hide on scroll, splash) and `pd/bag` (mirror of the WooCommerce cart over the Store API). `pd-marketplace` 0.4.0 adds the product box "Priyadarshini: maker and format" (artisan, Devanagari title, buying format, made on order, material, time to make, catalogue number per artisan).

Demo content: `dev/seed-demo.php` (three sample artisans, six works). Browser checks: `dev/tests/theme_e2e.js`.

## Deploying
`tools/build-deploy-branches.sh --push` builds `deploy/<name>` branches (one per plugin or theme, only that code at the branch root) and `deploy/wp-content`: a wp-content layout holding only `plugins/pd-*` and `themes/pd-*`, plus a whitelist `.gitignore`, so uploads (the media library), WooCommerce and other plugins are never tracked. Point Hostinger at `public_html/wp-content` for `deploy/wp-content`, or at `public_html/wp-content/plugins/<plugin>` for a per-plugin branch. Never point it at `public_html`. A push to `main` rebuilds the deploy branches automatically (`.github/workflows/build-deploy.yml`); deploying to the server stays a manual click in hPanel. Trial in an empty scratch folder first: it is not yet known whether Hostinger's pull deletes untracked files.

## Checks
`php dev/tests/allocator_pure.php` needs no WordPress. The other tests in `dev/tests` need a throwaway WordPress with `PD_TESTS_WIPE=yes`.

## Licence
Plugin headers declare GPL-2.0-or-later, as WordPress plugins must. No licence file has been added yet; that is an owner decision.
