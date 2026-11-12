<?php
namespace PD\Artisan;

defined( 'ABSPATH' ) || exit;

/** Follow relation (SRS FOL). Source of truth for artisan.followed; follower_count on pd_artisans is a cache recomputed from here. */
final class Follows {

	public static function table(): string { global $wpdb; return $wpdb->prefix . 'pd_follows'; }

	public static function install(): void {
		global $wpdb;
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		$t = self::table();
		dbDelta( "CREATE TABLE {$t} (
			user_id bigint(20) unsigned NOT NULL,
			artisan_id bigint(20) unsigned NOT NULL,
			created_at datetime NOT NULL,
			PRIMARY KEY  (user_id, artisan_id),
			KEY artisan (artisan_id)
		) " . $wpdb->get_charset_collate() . ';' );
	}

	public static function is_following( int $user_id, int $artisan_id ): bool {
		global $wpdb;
		return $user_id > 0 && (bool) $wpdb->get_var( $wpdb->prepare( 'SELECT 1 FROM ' . self::table() . ' WHERE user_id=%d AND artisan_id=%d', $user_id, $artisan_id ) );
	}

	/** Which of these artisans the user follows. @param int[] $artisan_ids @return array<int,bool> */
	public static function followed_map( int $user_id, array $artisan_ids ): array {
		global $wpdb;
		$map = array_fill_keys( array_map( 'intval', $artisan_ids ), false );
		if ( $user_id > 0 && $map ) {
			$in = implode( ',', array_keys( $map ) );
			foreach ( $wpdb->get_col( $wpdb->prepare( 'SELECT artisan_id FROM ' . self::table() . " WHERE user_id=%d AND artisan_id IN ({$in})", $user_id ) ) as $id ) {
				$map[ (int) $id ] = true;
			}
		}
		return $map;
	}

	/** Set follow state (idempotent). Artisans cannot follow themselves. @return array{followed:bool,followerCount:int}|\WP_Error */
	public static function set( int $user_id, int $artisan_id, bool $follow ) {
		global $wpdb;
		$row = Table::get( $artisan_id );
		if ( ! $row || 'verified' !== $row['verification_status'] ) {
			return new \WP_Error( 'pd_artisan', 'Unknown artisan.', array( 'status' => 404 ) );
		}
		if ( (int) $row['user_id'] === $user_id ) {
			return new \WP_Error( 'pd_self', 'You cannot follow your own page.', array( 'status' => 409 ) );
		}
		if ( $follow ) {
			if ( ! self::is_following( $user_id, $artisan_id ) ) {
				$quiet = $wpdb->suppress_errors( true ); // a racing duplicate hits the primary key and is harmless
				$wpdb->insert( self::table(), array( 'user_id' => $user_id, 'artisan_id' => $artisan_id, 'created_at' => current_time( 'mysql', true ) ) );
				$wpdb->suppress_errors( $quiet );
			}
		} else {
			$wpdb->delete( self::table(), array( 'user_id' => $user_id, 'artisan_id' => $artisan_id ) );
		}
		return self::recount( $artisan_id, $user_id );
	}

	private static function recount( int $artisan_id, int $user_id ): array {
		global $wpdb;
		$n = (int) $wpdb->get_var( $wpdb->prepare( 'SELECT COUNT(*) FROM ' . self::table() . ' WHERE artisan_id=%d', $artisan_id ) );
		Table::upsert( $artisan_id, array( 'follower_count' => $n ) );
		return array( 'followed' => self::is_following( $user_id, $artisan_id ), 'followerCount' => $n );
	}
}
