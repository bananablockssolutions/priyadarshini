<?php
namespace PD\Reviews;

defined( 'ABSPATH' ) || exit;

/** Artisan reviews, replies and the derived read index (SRS DB-14, CNT-30 to CNT-38). */
final class Tables {

	public static function reviews(): string { global $wpdb; return $wpdb->prefix . 'pd_artisan_reviews'; }
	public static function replies(): string { global $wpdb; return $wpdb->prefix . 'pd_artisan_review_replies'; }
	public static function index(): string { global $wpdb; return $wpdb->prefix . 'pd_review_index'; }

	public static function install(): void {
		global $wpdb;
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		$c = $wpdb->get_charset_collate();
		$r = self::reviews();
		$p = self::replies();
		$i = self::index();
		dbDelta( "CREATE TABLE {$r} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			artisan_id bigint(20) unsigned NOT NULL,
			user_id bigint(20) unsigned NOT NULL,
			allocation_id bigint(20) unsigned NOT NULL DEFAULT 0,
			rating tinyint(3) unsigned NOT NULL,
			body text NOT NULL,
			has_photos tinyint(1) NOT NULL DEFAULT 0,
			custom_piece tinyint(1) NOT NULL DEFAULT 0,
			language varchar(8) NOT NULL DEFAULT 'en',
			state varchar(12) NOT NULL DEFAULT 'published',
			created_at datetime NOT NULL,
			edited_at datetime NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY one_per_allocation (user_id, artisan_id, allocation_id),
			KEY artisan_state (artisan_id, state)
		) {$c};" );
		dbDelta( "CREATE TABLE {$p} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			review_id bigint(20) unsigned NOT NULL,
			artisan_id bigint(20) unsigned NOT NULL,
			body text NOT NULL,
			state varchar(12) NOT NULL DEFAULT 'published',
			created_at datetime NOT NULL,
			edited_at datetime NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY one_reply (review_id)
		) {$c};" );
		dbDelta( "CREATE TABLE {$i} (
			typed_id varchar(40) NOT NULL,
			type varchar(10) NOT NULL,
			source_id bigint(20) unsigned NOT NULL,
			artisan_id bigint(20) unsigned NOT NULL,
			product_id bigint(20) unsigned NOT NULL DEFAULT 0,
			rating tinyint(3) unsigned NOT NULL,
			has_photos tinyint(1) NOT NULL DEFAULT 0,
			language varchar(8) NOT NULL DEFAULT 'en',
			state varchar(12) NOT NULL DEFAULT 'published',
			created_at datetime NOT NULL,
			PRIMARY KEY  (typed_id),
			KEY recent (artisan_id, type, state, created_at),
			KEY rated (artisan_id, type, state, rating)
		) {$c};" );
	}
}
