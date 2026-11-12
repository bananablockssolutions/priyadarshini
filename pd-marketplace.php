<?php
/**
 * Plugin Name: Priyadarshini Marketplace
 * Description: Data and rules for the Priyadarshini artisan marketplace (artisans, reviews, order allocations). No presentation here; blocks live in pd-blocks.
 * Version: 0.3.0
 * Requires PHP: 8.1
 * License: GPL-2.0-or-later
 * Text Domain: pd-marketplace
 */

namespace PD;

defined( 'ABSPATH' ) || exit;

const VERSION = '0.3.0';
const DB_VERSION = '3';

spl_autoload_register(
	static function ( string $class ): void {
		if ( strpos( $class, 'PD\\' ) !== 0 ) {
			return;
		}
		$path = __DIR__ . '/includes/' . str_replace( '\\', '/', substr( $class, 3 ) ) . '.php';
		if ( is_readable( $path ) ) {
			require $path;
		}
	}
);

register_activation_hook( __FILE__, array( Plugin::class, 'activate' ) );
add_action( 'plugins_loaded', array( Plugin::class, 'boot' ) );
