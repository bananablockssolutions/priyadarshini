<?php
namespace PD;

use PD\Artisan\PostType;
use PD\Artisan\Query;

defined( 'ABSPATH' ) || exit;

final class Plugin {

	public static function boot(): void {
		PostType::register_hooks();
		Query::register_hooks();
		Rest::register_hooks();
		if ( get_option( 'pd_db_version' ) !== DB_VERSION ) {
			Schema::install();
		}
	}

	public static function activate(): void {
		Schema::install();
		PostType::register_hooks();
		PostType::register();
		flush_rewrite_rules();
	}
}
