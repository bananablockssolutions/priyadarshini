<?php
namespace PD\Orders;

defined( 'ABSPATH' ) || exit;

/**
 * Pure arithmetic: splits ONE order into per-artisan allocations, in integer paise, with no rounding drift.
 * No WordPress calls here, so it can be tested exhaustively.
 *
 * ASSUMPTIONS TO CONFIRM (SRS Appendix B): commission rate; artisan_payable = items after discount
 * + shipping - commission, with tax reported separately and remitted by the platform.
 */
final class Allocator {

	/**
	 * @param array<int,array{item_id:int,product_id:int,artisan_id:int,qty:int,subtotal:int,tax:int}> $lines  subtotal and tax in paise
	 * @param array<int,int> $shipping_by_artisan  paise
	 * @param int            $order_discount       paise, spread over lines in proportion to their subtotal
	 * @param int            $commission_bps       basis points, 1000 = 10%
	 * @return array<int,array>  keyed by artisan_id
	 */
	public static function allocate( array $lines, array $shipping_by_artisan, int $order_discount, int $commission_bps ): array {
		$subtotals = array_column( $lines, 'subtotal' );
		$discount  = self::spread( $order_discount, $subtotals );
		$out       = array();
		foreach ( $lines as $k => $l ) {
			$a    = $l['artisan_id'];
			$net  = $l['subtotal'] - $discount[ $k ];
			$comm = self::bps( $net, $commission_bps );
			$out[ $a ] ??= array( 'artisan_id' => $a, 'items' => array(), 'items_subtotal' => 0, 'commission' => 0, 'shipping_amount' => 0, 'tax_amount' => 0, 'artisan_payable' => 0 );
			$out[ $a ]['items'][]          = array( 'item_id' => $l['item_id'], 'product_id' => $l['product_id'], 'qty' => $l['qty'], 'line_subtotal' => $l['subtotal'], 'discount_share' => $discount[ $k ], 'tax' => $l['tax'], 'commission' => $comm );
			$out[ $a ]['items_subtotal']  += $net;
			$out[ $a ]['commission']      += $comm;
			$out[ $a ]['tax_amount']      += $l['tax'];
		}
		foreach ( $shipping_by_artisan as $a => $amt ) {
			if ( ! isset( $out[ $a ] ) ) {
				throw new \InvalidArgumentException( "Shipping given for artisan $a who has no items." );
			}
			$out[ $a ]['shipping_amount'] = $amt;
		}
		foreach ( $out as $a => &$x ) {
			$x['artisan_payable'] = $x['items_subtotal'] + $x['shipping_amount'] - $x['commission'];
		}
		return $out;
	}

	/** Reconciliation against the WooCommerce order totals. @return string[] problems, empty when it reconciles to the paisa */
	public static function reconcile( array $alloc, int $order_items_subtotal, int $order_discount, int $order_shipping, int $order_tax ): array {
		$p = array();
		$sum = static fn( string $k ) => array_sum( array_column( $alloc, $k ) );
		if ( $sum( 'items_subtotal' ) !== $order_items_subtotal - $order_discount ) { $p[] = 'items_subtotal'; }
		if ( $sum( 'shipping_amount' ) !== $order_shipping ) { $p[] = 'shipping'; }
		if ( $sum( 'tax_amount' ) !== $order_tax ) { $p[] = 'tax'; }
		foreach ( $alloc as $x ) {
			if ( array_sum( array_column( $x['items'], 'commission' ) ) !== $x['commission'] ) { $p[] = "commission:{$x['artisan_id']}"; }
			if ( $x['artisan_payable'] + $x['commission'] !== $x['items_subtotal'] + $x['shipping_amount'] ) { $p[] = "payable:{$x['artisan_id']}"; }
		}
		return $p;
	}

	/** Commission to take back for a refund of $refund paise on an item whose net was $net: proportional, never above what is left. */
	public static function refund_commission( int $net, int $item_commission, int $already_refunded, int $refund ): int {
		if ( $refund <= 0 || $already_refunded + $refund > $net ) {
			throw new \InvalidArgumentException( 'Refund exceeds the item.' );
		}
		$total_back_after = self::div_round( $item_commission * ( $already_refunded + $refund ), $net );
		$total_back_before = self::div_round( $item_commission * $already_refunded, $net );
		return $total_back_after - $total_back_before; // telescoping: full refund returns exactly the whole commission
	}

	/** Largest-remainder split of $amount in proportion to $weights. Sum of result === $amount. */
	public static function spread( int $amount, array $weights ): array {
		$total = array_sum( $weights );
		$out   = array_fill_keys( array_keys( $weights ), 0 );
		if ( $amount <= 0 || $total <= 0 ) { return $out; }
		$rem = array();
		$given = 0;
		foreach ( $weights as $k => $w ) {
			$exact     = $amount * $w;
			$out[ $k ] = intdiv( $exact, $total );
			$rem[ $k ] = $exact % $total;
			$given    += $out[ $k ];
		}
		arsort( $rem );
		foreach ( array_keys( $rem ) as $k ) {
			if ( $given >= $amount ) { break; }
			$out[ $k ]++;
			$given++;
		}
		return $out;
	}

	private static function bps( int $amount, int $bps ): int { return self::div_round( $amount * $bps, 10000 ); }

	/** Round half up. */
	private static function div_round( int $n, int $d ): int { return intdiv( 2 * $n + $d, 2 * $d ); }
}
