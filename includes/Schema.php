<?php
namespace PD;

defined( 'ABSPATH' ) || exit;

/** Installs every custom table. Runs on activation and when DB_VERSION changes (deploy by FTP needs no manual SQL). */
final class Schema {

	public static function install(): void {
		Artisan\Table::install();
		Reviews\Tables::install();
		Orders\Tables::install();
		Artisan\Follows::install();
		update_option( 'pd_db_version', DB_VERSION );
	}
}
