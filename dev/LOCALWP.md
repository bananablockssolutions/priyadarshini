# Local development with LocalWP

LocalWP gives us real MySQL, a normal PHP, and WooCommerce from wordpress.org, so tests here are closer to the live site than anything run in the cloud.

## One-time setup (about 10 minutes)
1. In Local, create a site named `priyadarshini-dev`: PHP 8.1 or newer, MySQL 8 or MariaDB, nginx or Apache, "Preferred" environment. Choose a throwaway admin login (not a password you use elsewhere).
2. In the site's WordPress admin: **Plugins > Add New > WooCommerce > Install > Activate**. Skip the store setup wizard. Set **Settings > Permalinks** to "Post name".
3. Link our plugin into the site with a junction (Command Prompt; no admin rights needed). Find the site folder with "Go to site folder" in Local.
   `mklink /J "C:\Users\abc\Local Sites\priyadarshini-dev\app\public\wp-content\plugins\pd-marketplace" "C:\Users\abc\Documents\priyadarshini\build\wp-content\plugins\pd-marketplace"`
4. **Plugins**: activate "Priyadarshini Marketplace". It creates its own tables.
5. Do NOT copy `dev\mu-plugins\pd-dev-standins.php` into this site. It is only for the cloud sandbox, which has no WooCommerce.

## Running the tests
The tests DELETE data (artisans, reviews, allocations, test products and review comments), so run them only on this throwaway site. They refuse to run unless you set `PD_TESTS_WIPE=yes`.

In Local, click **Open site shell**, then:
- PowerShell: `$env:PD_TESTS_WIPE="yes"; wp eval-file "C:/Users/abc/Documents/priyadarshini/build/dev/tests/spike1.php"`
- Command Prompt: `set PD_TESTS_WIPE=yes` then `wp eval-file "C:/Users/abc/Documents/priyadarshini/build/dev/tests/spike1.php"`

Run `spike1.php`, `spike7.php` and `spike_alloc.php`. Every line should start with `PASS`. Expected counts: spike1 8, spike7 41, spike_alloc 14. Copy any `FAIL` lines and send them to me.

## What is different from the cloud sandbox
- Real MySQL and real WooCommerce: query times and the `product_cat` taxonomy are the real ones. Spike 1 timing is the number that counts.
- Spike 7 creates its own `Work` products and `review` comments; with WooCommerce active these are real products.

## Keeping it clean
Source stays in `build\wp-content`. Never edit plugin files inside the Local site folder; the junction points back to the source.
