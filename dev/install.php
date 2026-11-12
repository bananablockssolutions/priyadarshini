<?php
// Installs a throwaway WordPress on SQLite for spike work. Run: php install.php
$root = __DIR__ . '/wp/';
$cfg = <<<'PHP'
<?php
define('DB_NAME','pd'); define('DB_USER',''); define('DB_PASSWORD',''); define('DB_HOST','');
define('DB_CHARSET','utf8mb4'); define('DB_COLLATE','');
define('AUTH_KEY','x');define('SECURE_AUTH_KEY','x');define('LOGGED_IN_KEY','x');define('NONCE_KEY','x');
define('AUTH_SALT','x');define('SECURE_AUTH_SALT','x');define('LOGGED_IN_SALT','x');define('NONCE_SALT','x');
$table_prefix='wp_';
define('WP_DEBUG',true); define('WP_DEBUG_DISPLAY',true); define('WP_DEBUG_LOG',false);
define('WP_HOME', getenv('PD_URL') ?: 'http://127.0.0.1:8088'); define('WP_SITEURL', WP_HOME);
if(!defined('ABSPATH')) define('ABSPATH', __DIR__.'/');
require_once ABSPATH.'wp-settings.php';
PHP;
file_put_contents($root.'wp-config.php', $cfg);
define('WP_INSTALLING', true);
$_SERVER['HTTP_HOST']='127.0.0.1:8088'; $_SERVER['REQUEST_URI']='/'; $_SERVER['SERVER_NAME']='127.0.0.1';
require $root.'wp-load.php';
require_once ABSPATH.'wp-admin/includes/upgrade.php';
if (!is_blog_installed()) {
  $r = wp_install('Priyadarshini dev', 'admin', 'admin@example.test', true, '', 'devpass');
  echo "installed user {$r['user_id']}\n";
} else { echo "already installed\n"; }
