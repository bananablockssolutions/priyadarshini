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

	/**
	 * Illustration: the picture on a menu card, which becomes the cover of the page it opens. The craft's own
	 * illustration when one is set; otherwise a placeholder scene drawn from the motifs on a pigment ground.
	 * The frame and the ink triangle border come from CSS, so a scene can be cropped to any ratio.
	 * @param array{cover?:int,scene?:string,accent?:string,alt?:string} $item
	 */
	public static function illustration( array $item, string $vt = '', bool $dest = false, bool $eager = false, string $class = '' ): string {
		$accent = self::accent( (string) ( $item['accent'] ?? 'gulabi' ) );
		$cover  = (int) ( $item['cover'] ?? 0 );
		$inner  = $cover ? wp_get_attachment_image(
			$cover,
			'large',
			false,
			array( 'alt' => (string) ( $item['alt'] ?? '' ), 'loading' => $eager ? 'eager' : 'lazy', 'decoding' => 'async', 'class' => 'pd-ill__img', 'sizes' => $dest ? '100vw' : '(min-width: 760px) 30vw, 90vw' )
		) : '';
		if ( ! $inner ) {
			$inner = self::scene( (string) ( $item['scene'] ?? 'fish' ) );
		}
		return sprintf(
			'<span class="pd-ill%1$s" data-accent="%2$s"%3$s%4$s>%5$s</span>',
			$class ? ' ' . esc_attr( $class ) : '',
			esc_attr( $accent ),
			$vt ? ' data-pd-vt="' . esc_attr( $vt ) . '"' : '',
			$dest ? ' data-pd-vt-dest' : '',
			$inner
		);
	}

	/** Placeholder scene, 600 by 400, composed around the centre so it crops well (slice). */
	private static function scene( string $scene ): string {
		$scene = 'plant' === $scene ? 'leaf' : $scene;
		$scene = isset( self::MOTIFS[ $scene ] ) ? $scene : 'fish';
		[ , , $w, $h ] = array_map( 'floatval', explode( ' ', self::MOTIFS[ $scene ] ) );
		$scale = min( 250 / $w, 190 / $h );
		$mw    = round( $w * $scale );
		$mh    = round( $h * $scale );
		$main  = sprintf( '<use href="#pd-m-%1$s" x="%2$s" y="%3$s" width="%4$s" height="%5$s"/>', $scene, 300 - $mw / 2, 196 - $mh / 2, $mw, $mh );
		$side  = 'leaf' === $scene ? 'sun' : 'leaf';
		[ , , $sw, $sh ] = array_map( 'floatval', explode( ' ', self::MOTIFS[ $side ] ) );
		$ss    = min( 74 / $sw, 120 / $sh );
		$sw    = round( $sw * $ss );
		$sh    = round( $sh * $ss );
		$sides = sprintf(
			'<use href="#pd-m-%1$s" x="%2$s" y="%3$s" width="%4$s" height="%5$s"/><g transform="translate(600 0) scale(-1 1)"><use href="#pd-m-%1$s" x="%2$s" y="%3$s" width="%4$s" height="%5$s"/></g>',
			$side,
			118 - $sw / 2,
			218 - $sh / 2,
			$sw,
			$sh
		);
		$dots = '';
		foreach ( array( array( 60, 70 ), array( 540, 70 ), array( 40, 300 ), array( 560, 300 ), array( 150, 90 ), array( 450, 90 ), array( 210, 330 ), array( 390, 330 ) ) as $i => [ $x, $y ] ) {
			$dots .= sprintf( '<circle cx="%d" cy="%d" r="%s" class="pd-ill__%s"/>', $x, $y, $i % 2 ? '5' : '7', $i % 3 ? 'dot' : 'dot2' );
		}
		return '<svg class="pd-ill__scene" viewBox="0 0 600 400" preserveAspectRatio="xMidYMid slice" aria-hidden="true" focusable="false">'
			. '<rect width="600" height="400" class="pd-ill__ground"/>'
			. '<circle cx="300" cy="196" r="150" class="pd-ill__halo"/>'
			. '<circle cx="300" cy="196" r="168" class="pd-ill__ring"/>'
			. '<circle cx="300" cy="196" r="182" class="pd-ill__ring2"/>'
			. $dots . $sides . $main
			. '<path class="pd-ill__water" d="M-10 372q25-14 50 0t50 0 50 0 50 0 50 0 50 0 50 0 50 0 50 0 50 0 50 0 50 0 50 0M-10 392q25-14 50 0t50 0 50 0 50 0 50 0 50 0 50 0 50 0 50 0 50 0 50 0 50 0 50 0"/>'
			. '</svg>';
	}
}
