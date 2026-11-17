<?php
namespace PD\Artisan;

defined( 'ABSPATH' ) || exit;

/**
 * Operational artisan data (SRS DB-11, DB-12). One row per pd_artisan post.
 * Public columns may appear in page output; private columns never leave the server.
 */
final class Table {

	public static function name(): string {
		global $wpdb;
		return $wpdb->prefix . 'pd_artisans';
	}

	public static function install(): void {
		global $wpdb;
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		$t       = self::name();
		$collate = $wpdb->get_charset_collate();
		dbDelta(
			"CREATE TABLE {$t} (
			post_id bigint(20) unsigned NOT NULL,
			user_id bigint(20) unsigned NOT NULL,
			verification_status varchar(20) NOT NULL DEFAULT 'pending',
			verified_at datetime NULL,
			verifier bigint(20) unsigned NULL,
			accent varchar(20) NOT NULL DEFAULT 'gulabi',
			motif varchar(20) NOT NULL DEFAULT 'fish',
			taking_orders tinyint(1) NOT NULL DEFAULT 1,
			lead_time_days smallint(5) unsigned NOT NULL DEFAULT 7,
			away_until date NULL,
			rating_avg decimal(3,2) NOT NULL DEFAULT 0,
			rating_count int(10) unsigned NOT NULL DEFAULT 0,
			reply_rate tinyint(3) unsigned NOT NULL DEFAULT 0,
			sales_count int(10) unsigned NOT NULL DEFAULT 0,
			follower_count int(10) unsigned NOT NULL DEFAULT 0,
			created_at datetime NOT NULL,
			updated_at datetime NOT NULL,
			PRIMARY KEY  (post_id),
			UNIQUE KEY user_id (user_id),
			KEY verification (verification_status),
			KEY directory (taking_orders, rating_avg)
		) {$collate};"
		);
	}

	/** Columns that may appear in public output (DB-12). */
	public const PUBLIC_COLUMNS = array(
		'accent', 'motif', 'taking_orders', 'lead_time_days', 'away_until',
		'rating_avg', 'rating_count', 'reply_rate', 'sales_count', 'follower_count', 'verification_status',
	);

	public static function get( int $post_id ): ?array {
		global $wpdb;
		$row = $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . self::name() . ' WHERE post_id = %d', $post_id ), ARRAY_A );
		return $row ?: null;
	}

	/** Public view of one artisan row: only DB-12 public columns. */
	public static function public_view( int $post_id ): ?array {
		$row = self::get( $post_id );
		return $row ? array_intersect_key( $row, array_flip( self::PUBLIC_COLUMNS ) ) : null;
	}

	public static function upsert( int $post_id, array $data ): void {
		global $wpdb;
		$now = current_time( 'mysql', true );
		if ( self::get( $post_id ) ) {
			$wpdb->update( self::name(), $data + array( 'updated_at' => $now ), array( 'post_id' => $post_id ) );
		} else {
			$wpdb->insert( self::name(), $data + array( 'post_id' => $post_id, 'created_at' => $now, 'updated_at' => $now ) );
		}
	}
}
