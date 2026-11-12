<?php
namespace PD\Orders;

defined( 'ABSPATH' ) || exit;

/** Saves allocations for a WooCommerce order. Idempotent: calling twice for one order changes nothing. */
final class Store {

	/** @return int[] allocation ids by artisan */
	public static function save( int $wc_order_id, array $alloc ): array {
		global $wpdb;
		$ids = array();
		$wpdb->query( 'START TRANSACTION' );
		try {
			foreach ( $alloc as $a => $x ) {
				$existing = (int) $wpdb->get_var( $wpdb->prepare( 'SELECT id FROM ' . Tables::allocations() . ' WHERE wc_order_id=%d AND artisan_id=%d', $wc_order_id, $a ) );
				if ( $existing ) { $ids[ $a ] = $existing; continue; }
				$ok = $wpdb->insert( Tables::allocations(), array(
					'wc_order_id' => $wc_order_id, 'artisan_id' => $a, 'items_subtotal' => $x['items_subtotal'], 'commission' => $x['commission'],
					'artisan_payable' => $x['artisan_payable'], 'shipping_amount' => $x['shipping_amount'], 'tax_amount' => $x['tax_amount'], 'created_at' => current_time( 'mysql', true ),
				) );
				if ( ! $ok ) { throw new \RuntimeException( $wpdb->last_error ); }
				$aid = (int) $wpdb->insert_id;
				foreach ( $x['items'] as $it ) {
					$ok = $wpdb->insert( Tables::items(), array(
						'allocation_id' => $aid, 'wc_order_item_id' => $it['item_id'], 'product_id' => $it['product_id'], 'quantity' => $it['qty'],
						'line_subtotal' => $it['line_subtotal'], 'discount_share' => $it['discount_share'], 'tax_amount' => $it['tax'], 'commission' => $it['commission'],
					) );
					if ( ! $ok ) { throw new \RuntimeException( $wpdb->last_error ); }
				}
				$ids[ $a ] = $aid;
			}
			$wpdb->query( 'COMMIT' );
		} catch ( \Throwable $e ) {
			$wpdb->query( 'ROLLBACK' );
			throw $e;
		}
		return $ids;
	}
}
