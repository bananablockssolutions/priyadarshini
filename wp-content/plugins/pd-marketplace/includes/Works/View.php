<?php
namespace PD\Works;

use PD\Artisan\Table;

defined( 'ABSPATH' ) || exit;

/**
 * Public view of one work for presentation blocks (work card, buy box, bag lines).
 * Only public fields: no cost price, no supplier data, nothing private about the artisan (SEC-10, DB-12).
 */
final class View {

	/** @return array<string,mixed>|null */
	public static function get( int $product_id ): ?array {
		if ( ! function_exists( 'wc_get_product' ) ) {
			return null;
		}
		$p = wc_get_product( $product_id );
		if ( ! $p || 'publish' !== $p->get_status() || 'hidden' === $p->get_catalog_visibility() ) {
			return null;
		}
		$artisan_id = (int) $p->get_meta( Meta::ARTISAN );
		$format     = (string) $p->get_meta( Meta::FORMAT ) ?: 'buy';
		$on_order   = '1' === (string) $p->get_meta( Meta::ON_ORDER );
		$in_stock   = $p->is_in_stock();
		return array(
			'id'           => $product_id,
			'title'        => $p->get_name(),
			'titleHi'      => (string) $p->get_meta( Meta::TITLE_HI ),
			'number'       => (int) $p->get_meta( Meta::NUMBER ),
			'url'          => get_permalink( $product_id ),
			'imageId'      => (int) $p->get_image_id(),
			'price'        => (float) $p->get_price(),
			'priceHtml'    => $p->get_price_html(),
			'format'       => $format,
			'madeOnOrder'  => $on_order,
			'formatLabel'  => self::format_label( $format, $on_order, $in_stock ),
			'inStock'      => $in_stock,
			'canBuy'       => 'commission' !== $format && $in_stock && $p->is_purchasable(),
			'availability' => self::availability( $p, $format, $on_order ),
			'material'     => (string) $p->get_meta( Meta::MATERIAL ),
			'makingTime'   => (string) $p->get_meta( Meta::TIME ),
			'ratingAvg'    => (float) $p->get_average_rating(),
			'ratingCount'  => (int) $p->get_review_count(),
			'artisan'      => $artisan_id ? self::maker( $artisan_id ) : null,
			'badge'        => (string) $p->get_meta( Meta::BADGE ),
			'options'      => Variants::options( $p ),
		);
	}

	/** BUY-09 labels: "Ready to ship", "Made on order", "Commission", or a pair. */
	public static function format_label( string $format, bool $on_order, bool $in_stock ): string {
		$buy = $on_order ? 'Made on order' : 'Ready to ship';
		if ( 'commission' === $format || ! $in_stock && 'buy_commission' === $format ) {
			return 'Commission';
		}
		return 'buy_commission' === $format ? $buy . ' · Commission' : $buy;
	}

	private static function availability( \WC_Product $p, string $format, bool $on_order ): string {
		if ( 'commission' === $format ) {
			return 'Commission only';
		}
		if ( ! $p->is_in_stock() ) {
			return 'buy_commission' === $format ? 'Sold. Commission one like it' : 'Sold';
		}
		if ( $on_order ) {
			return 'Made on order';
		}
		$qty = $p->managing_stock() ? (int) $p->get_stock_quantity() : 0;
		return 1 === $qty ? 'One available' : 'Available';
	}

	/** Public maker fields for a work line: name, Devanagari name, accent, motif, link. @return array<string,mixed>|null */
	public static function maker( int $artisan_id ): ?array {
		$post = get_post( $artisan_id );
		$ops  = Table::public_view( $artisan_id );
		if ( ! $post || 'publish' !== $post->post_status || ! $ops ) {
			return null;
		}
		$name = get_the_title( $post );
		return array(
			'id'       => $artisan_id,
			'name'     => $name,
			'nameHi'   => (string) get_post_meta( $artisan_id, '_pd_name_hi', true ),
			'initial'  => self::initial( (string) get_post_meta( $artisan_id, '_pd_name_hi', true ), $name ),
			'url'      => get_permalink( $post ),
			'accent'   => (string) $ops['accent'],
			'motif'    => (string) $ops['motif'],
			'village'  => (string) get_post_meta( $artisan_id, '_pd_village', true ),
			'verified' => 'verified' === $ops['verification_status'],
		);
	}

	/** Seal monogram: first Devanagari letter (with its vowel sign) when there is a Devanagari name, else the first Latin letter. */
	public static function initial( string $name_hi, string $name ): string {
		if ( '' !== $name_hi && preg_match( '/^\X/u', $name_hi, $m ) ) {
			return $m[0];
		}
		return strtoupper( mb_substr( $name, 0, 1 ) );
	}
}
