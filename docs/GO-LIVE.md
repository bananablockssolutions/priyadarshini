# Go-live checklist for priyadarshini-studio.in (Hostinger)

**First run done 2026-11-12:** plugins deployed, WooCommerce installed, all four test files passed (8, 41, 14, 36), test data cleaned, search engines discouraged. The checklist below is kept for repeat runs on a fresh empty site. The live site was empty then and is treated as staging. Hostinger, WordPress installed, HTTPS works, SFTP, SSH and WP-CLI available. Nothing here has been run on the server yet.

## 0. Before anything
1. hPanel > Websites > Manage > Backups: take a backup (cheap, even for an empty site).
2. hPanel > Advanced > PHP Configuration: PHP 8.1 or newer (8.3 is fine), memory limit 256M or more.
3. WordPress admin > Settings > Reading: tick "Discourage search engines" until launch.
4. hPanel > Advanced > SSH Access: turn SSH on. Note the host, port (65002) and username shown there. Never paste the password in chat.

## 1. Upload (FileZilla, protocol SFTP, port 65002)
Remote folder: `domains/priyadarshini-studio.in/public_html/wp-content/plugins/` (check the exact path in the left pane after you log in).
1. Upload the folders `pd-marketplace` and `pd-blocks` from `C:\Users\abc\Documents\priyadarshini\build\wp-content\plugins\`.
2. Upload the folder `C:\Users\abc\Documents\priyadarshini\build\dev\tests\` to your home folder on the server, NOT inside public_html, and name it `pd-tests`.
3. Do not upload anything else from `dev\`.

## 2. Activate and run the checks (SSH)
Connect: `ssh -p 65002 <user>@<host>` (or use Hostinger's browser terminal), then:
```
cd ~/domains/priyadarshini-studio.in/public_html
wp plugin install woocommerce --activate
wp plugin activate pd-marketplace pd-blocks
export PD_TESTS_WIPE=yes
wp eval-file ~/pd-tests/spike1.php
wp eval-file ~/pd-tests/spike7.php
wp eval-file ~/pd-tests/spike_alloc.php
wp eval-file ~/pd-tests/blocks1.php
```
Every line should start with PASS. Expected counts: spike1 8, spike7 41, spike_alloc 14, blocks1 36. If a file prints "activated ... run again", run it once more. Copy any FAIL lines to the chat.

## 3. Clean up
```
wp eval-file ~/pd-tests/cleanup.php
rm -r ~/pd-tests
```
cleanup.php removes the test artisans, pages, products and users, and refuses to run if the site has WooCommerce orders or a real non-admin user.

## After a run
- Delete the SSH key you added in hPanel > Advanced > SSH Access > SSH keys (it works for every site on the same hosting account, not just this one).
- Results go in `build\dev\results\`.

## Never do
- Mirror-sync the whole `wp-content` (it would overwrite uploads and other plugins).
- Run the test files once real customers, products or orders exist: they delete data.
- Leave `~/pd-tests` on the server after the run.

## Still open
- Commission rate and artisan payout formula (tests use 10% as a stand-in).
- Is Hostinger's own plugin set (LiteSpeed Cache and a Hostinger tools plugin) wanted? Keep them for now.
