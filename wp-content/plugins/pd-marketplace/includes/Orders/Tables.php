<?php
namespace PD\Orders;

defined( 'ABSPATH' ) || exit;

/** One WooCommerce order, split into per-artisan allocations (SRS 3.5.4, DB-08..DB-10). Money is stored in paise (integer). */
final class Tables {

	public static function allocations(): string { global $wpdb; return $wpdb->prefix . 'pd_order_allocations'; }
	public static function items(): string { global $wpdb; return $wpdb->prefix . 'pd_order_allocation_items'; }
	public static function shipments(): string { global $wpdb; return $wpdb->prefix . 'pd_shipments'; }

	public static function install(): void {
		global $wpdb;
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		$c = $wpdb->get_charset_collate();
		$a = self::allocations();
		$i = self::items();
		$s = self::shipments();
		dbDelta( "CREATE TABLE {$a} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			wc_order_id bigint(20) unsigned NOT NULL,
			artisan_id bigint(20) unsigned NOT NULL,
			status varchar(20) NOT NULL DEFAULT 'new',
			items_subtotal bigint(20) NOT NULL,
			commission bigint(20) NOT NULL,
			artisan_payable bigint(20) NOT NULL,
			shipping_amount bigint(20) NOT NULL DEFAULT 0,
			tax_amount bigint(20) NOT NULL DEFAULT 0,
			est_ready_date date NULL,
			accepted_at datetime NULL,
			ready_at datetime NULL,
			shipped_at datetime NULL,
			completed_at datetime NULL,
			created_at datetime NOT NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY order_artisan (wc_order_id, artisan_id),
			KEY artisan_status (artisan_id, status)
		) {$c};" );
		dbDelta( "CREATE TABLE {$i} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			allocation_id bigint(20) unsigned NOT NULL,
			wc_order_item_id bigint(20) unsigned NOT NULL,
			product_id bigint(20) unsigned NOT NULL,
			quantity int(10) unsigned NOT NULL,
			line_subtotal bigint(20) NOT NULL,
			discount_share bigint(20) NOT NULL DEFAULT 0,
			tax_amount bigint(20) NOT NULL DEFAULT 0,
			commission bigint(20) NOT NULL DEFAULT 0,
			refunded bigint(20) NOT NULL DEFAULT 0,
			PRIMARY KEY  (id),
			UNIQUE KEY order_item (wc_order_item_id),
			KEY allocation (allocation_id)
		) {$c};" );
		dbDelta( "CREATE TABLE {$s} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			allocation_id bigint(20) unsigned NOT NULL,
			carrier varchar(60) NOT NULL DEFAULT '',
			tracking_no varchar(80) NOT NULL DEFAULT '',
			status varchar(20) NOT NULL DEFAULT 'pending',
			shipped_at datetime NULL,
			delivered_at datetime NULL,
			PRIMARY KEY  (id),
			KEY allocation (allocation_id)
		) {$c};" );
	}
}
