<?php
namespace PD\Blocks;

defined( 'ABSPATH' ) || exit;

/**
 * Internal view components (BLOCKS.md section 6). Plain data in, escaped markup out. Not registered blocks.
 */
final class Components {

	private const MOTIFS = array(
		'fish'    => '0 0 90 60',
		'leaf'    => '0 0 60 90',
		'plant'   => '0 0 60 90',
		'sun'     => '0 0 80 80',
		'lotus'   => '0 0 90 70',
		'peacock' => '0 0 100 80',
		'person'  => '0 0 80 100',
		'studio'  => '0 0 100 70',
	);

	public const ACCENTS = array( 'gulabi', 'kusum', 'neel', 'patta', 'sindoor', 'haldi' ); // BRD-08, final set

	public static function accent( string $a ): string {
		return in_array( $a, self::ACCENTS, true ) ? $a : 'gulabi';
	}

	/** Placeholder Madhubani motif from the sprite printed in the footer. */
	public static function motif( string $name, string $class = 'pd-motif' ): string {
		$name = 'plant' === $name ? 'leaf' : $name;
		$box  = self::MOTIFS[ $name ] ?? self::MOTIFS['fish'];
		$name = isset( self::MOTIFS[ $name ] ) ? $name : 'fish';
		return sprintf( '<svg class="%s" viewBox="%s" aria-hidden="true" focusable="false"><use href="#pd-m-%s"/></svg>', esc_attr( $class ), esc_attr( $box ), esc_attr( $name ) );
	}

	/**
	 * Artisan Seal mark: pigment disc, double ring, monogram. Sizes xs 30, sm 56, md 96.
	 * @param array{initial:string,accent:string,motif?:string} $maker
	 */
	public static function seal( array $maker, string $size = 'xs', string $vt = '' ): string {
		$motif = 'xs' === $size ? '' : '<span class="pd-seal__motif">' . self::motif( (string) ( $maker['motif'] ?? 'fish' ) ) . '</span>';
		return sprintf(
			'<span class="pd-seal pd-seal--%1$s" data-accent="%2$s"%4$s aria-hidden="true"><span class="pd-seal__mark" lang="hi">%3$s</span>%5$s</span>',
			esc_attr( $size ),
			esc_attr( self::accent( (string) $maker['accent'] ) ),
			esc_html( (string) $maker['initial'] ),
			$vt ? ' data-pd-vt="' . esc_attr( $vt ) . '"' : '',
			$motif
		);
	}

	/** Plate: framed image area. Ratio 4/5 for works and portraits. Falls back to the maker's motif when there is no photo. */
	public static function plate( int $image_id, string $alt, string $ratio = '4-5', string $vt = '', string $motif = 'fish', string $sizes = '(min-width: 760px) 25vw, 50vw', bool $eager = false ): string {
		$inner = $image_id
			? wp_get_attachment_image( $image_id, 'woocommerce_single', false, array( 'alt' => $alt, 'sizes' => $sizes, 'loading' => $eager ? 'eager' : 'lazy', 'decoding' => 'async', 'class' => 'pd-plate__img' ) )
			: self::motif( $motif, 'pd-motif pd-plate__motif' );
		if ( $image_id && ! $inner ) {
			$inner = self::motif( $motif, 'pd-motif pd-plate__motif' );
		}
		return sprintf(
			'<span class="pd-plate pd-plate--%1$s"%2$s>%3$s</span>',
			esc_attr( $ratio ),
			$vt ? ' data-pd-vt="' . esc_attr( $vt ) . '"' : '',
			$inner
		);
	}

	/** "Work No. 014 · by Meera Devi": one string everywhere. */
	public static function catalogue_line( int $number, ?array $maker ): string {
		$parts = array();
		if ( $number > 0 ) {
			$parts[] = sprintf( 'Work No. %03d', $number );
		}
		if ( $maker ) {
			$parts[] = 'by ' . $maker['name'];
		}
		return implode( ' · ', $parts );
	}

	/** Haldi stars with an accessible label, or the New tag under 3 reviews (REV-08). */
	public static function rating( float $avg, int $count, string $what = 'work' ): string {
		if ( $count < 3 ) {
			return '<span class="pd-newtag">New</span>';
		}
		$label = sprintf( 'Rated %s out of 5 from %d reviews', number_format_i18n( $avg, 1 ), $count );
		return sprintf(
			'<span class="pd-rating" role="img" aria-label="%1$s"><span class="pd-rating__stars" style="--pd-r:%2$s" aria-hidden="true"></span><span aria-hidden="true">%3$s (%4$d)</span></span>',
			esc_attr( $label ),
			esc_attr( (string) round( $avg / 5 * 100, 1 ) . '%' ),
			esc_html( number_format_i18n( $avg, 1 ) ),
			$count
		);
	}
}
