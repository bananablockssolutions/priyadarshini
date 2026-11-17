<?php
namespace PD\Blocks;

use PD\Works\View;

defined( 'ABSPATH' ) || exit;

/** What the current route is, for the shell's context row and the routed main (public data only). */
final class Route {

	/**
	 * The 44px context row: a maker Seal and line on maker and work pages, a plain line elsewhere.
	 * @return array{text:string,hi:string,maker:?array}
	 */
	public static function context( string $mode = 'full' ): array {
		$out = array( 'text' => 'Handmade in Bihar, by named makers', 'hi' => '', 'maker' => null );
		if ( 'calm' === $mode ) {
			$out['text'] = function_exists( 'is_order_received_page' ) && is_order_received_page() ? 'Order placed' : 'Checkout · Secure payment';
			return $out;
		}
		if ( is_singular( 'pd_artisan' ) ) {
			$maker = View::maker( (int) get_queried_object_id() );
			if ( $maker ) {
				$craft        = self::craft_name( (int) $maker['id'] );
				$out['maker'] = $maker;
				$out['text']  = $maker['name'] . ( $craft ? ' · ' . $craft : '' ) . ( $maker['village'] ? ' · ' . $maker['village'] : '' );
				$out['hi']    = $maker['nameHi'];
			}
		} elseif ( is_singular( 'product' ) ) {
			$work = View::get( (int) get_queried_object_id() );
			if ( $work ) {
				$out['maker'] = $work['artisan'];
				$out['text']  = Components::catalogue_line( (int) $work['number'], $work['artisan'] ) ?: $work['title'];
			}
		} elseif ( is_post_type_archive( 'pd_artisan' ) ) {
			$n           = (int) wp_count_posts( 'pd_artisan' )->publish;
			$out['text'] = sprintf( 'Artisans · %d %s', $n, 1 === $n ? 'maker' : 'makers' );
			$out['hi']   = 'कारीगर';
		} elseif ( function_exists( 'is_shop' ) && is_shop() ) {
			$out['text'] = 'Works · made by hand, signed by their makers';
			$out['hi']   = 'कृतियाँ';
		} elseif ( is_tax( 'product_cat' ) ) {
			$out['text'] = single_term_title( '', false ) . ' · craft';
		} elseif ( function_exists( 'is_cart' ) && is_cart() ) {
			$out['text'] = 'Your bag · grouped by maker';
		} elseif ( is_search() ) {
			$out['text'] = sprintf( 'Search · “%s”', get_search_query( false ) );
		} elseif ( is_404() ) {
			$out['text'] = 'Page not found';
		}
		return $out;
	}

	public static function craft_name( int $artisan_id ): string {
		$terms = taxonomy_exists( 'product_cat' ) ? get_the_terms( $artisan_id, 'product_cat' ) : false;
		return $terms && ! is_wp_error( $terms ) ? $terms[0]->name : '';
	}

	/** Pages that need a full load (WooCommerce cart, checkout and account scripts; variable products) are not swapped in place. */
	public static function routable(): bool {
		$ok = true;
		if ( function_exists( 'is_cart' ) && ( is_cart() || is_checkout() || is_account_page() ) ) {
			$ok = false;
		}
		if ( $ok && is_singular( 'product' ) && function_exists( 'wc_get_product' ) ) {
			$p  = wc_get_product( get_queried_object_id() );
			$ok = $p && in_array( $p->get_type(), array( 'simple', 'external' ), true );
		}
		return (bool) apply_filters( 'pd_routable', $ok );
	}

	public static function path(): string {
		$uri = isset( $_SERVER['REQUEST_URI'] ) ? wp_unslash( $_SERVER['REQUEST_URI'] ) : '/'; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
		$p   = wp_parse_url( $uri );
		return ( $p['path'] ?? '/' ) . ( isset( $p['query'] ) ? '?' . $p['query'] : '' );
	}
}
