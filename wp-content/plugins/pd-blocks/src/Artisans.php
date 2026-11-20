<?php
namespace PD\Blocks;

defined( 'ABSPATH' ) || exit;

/**
 * Artisan parts shared by the Artists list card and the artisan page (owner sketch 5, approved prototype).
 * Each part carries data-pd-fly so the card's portrait, ribbon, name, line and medals grow into the page's.
 */
final class Artisans {

	private const HEX   = array( 'gulabi' => '#D9467A', 'kusum' => '#E26A1B', 'neel' => '#25306B', 'patta' => '#3E7B3A', 'sindoor' => '#C8321F', 'haldi' => '#E9A30C' );
	private const TINT  = array( 'gulabi' => '#F7D5DF', 'kusum' => '#F4DCC8', 'neel' => '#DCDDEA', 'patta' => '#DCE6D3', 'sindoor' => '#F4D3CC', 'haldi' => '#F6E2B7' );
	private const MEDAL = '<svg viewBox="0 0 20 20" fill="none" stroke="#2A1E17" stroke-width="1.6" aria-hidden="true" focusable="false"><circle cx="10" cy="12" r="5" fill="#E9A30C"/><path d="M7 2l3 5 3-5" stroke="#C8321F" stroke-width="2"/></svg>';

	/** The square portrait: the featured image, or a drawn placeholder in the maker's pigment until there is a photo. */
	public static function pic( array $a, bool $eager = false ): string {
		$img = $a['imageId'] ? wp_get_attachment_image( (int) $a['imageId'], 'woocommerce_single', false, array( 'alt' => '', 'class' => 'pd-apic__img', 'sizes' => '(min-width: 760px) 30vw, 70vw', 'loading' => $eager ? 'eager' : 'lazy', 'decoding' => 'async' ) ) : '';
		return '<span class="pd-apic" data-pd-fly="img">' . ( $img ?: self::face( (string) $a['accent'] ) ) . '</span>';
	}

	private static function face( string $accent ): string {
		$k    = '#2A1E17';
		$sari = self::HEX[ $accent ] ?? self::HEX['gulabi'];
		$tint = self::TINT[ $accent ] ?? self::TINT['gulabi'];
		return '<svg viewBox="0 0 100 100" aria-hidden="true" focusable="false"><rect width="100" height="100" fill="' . $tint . '"/><path d="M14 100 C18 74 34 64 50 64 C66 64 82 74 86 100Z" fill="' . $sari . '" stroke="' . $k . '" stroke-width="2.5"/><path d="M50 64 C40 64 36 76 34 100 M50 64 C60 64 64 76 66 100" fill="none" stroke="#E9A30C" stroke-width="2.5"/><ellipse cx="50" cy="40" rx="17" ry="20" fill="#C98B5E" stroke="' . $k . '" stroke-width="2.5"/><path d="M33 38 C33 18 67 18 67 38 C64 28 58 24 50 24 C42 24 36 28 33 38Z" fill="' . $k . '"/><path d="M41 38 q3 -3 6 0 M53 38 q3 -3 6 0" stroke="' . $k . '" stroke-width="2" fill="none"/><circle cx="44" cy="41" r="1.6" fill="' . $k . '"/><circle cx="56" cy="41" r="1.6" fill="' . $k . '"/><circle cx="50" cy="31" r="2" fill="#C8321F"/><path d="M45 50 q5 4 10 0" stroke="#C8321F" stroke-width="2" fill="none"/></svg>';
	}

	/** The rank ribbon ("#1"), set by hand by the team; nothing when the artisan has no rank. */
	public static function ribbon( array $a ): string {
		if ( (int) $a['rank'] < 1 ) {
			return '';
		}
		/* translators: %d: rank on the Artists list */
		return '<span class="pd-rib" data-pd-fly="rib" role="img" aria-label="' . esc_attr( sprintf( __( 'Ranked %d', 'pd-blocks' ), (int) $a['rank'] ) ) . '">#' . (int) $a['rank'] . '<small aria-hidden="true">★</small></span>';
	}

	/** Medals for awards (with their names on the page), then a small icon for each artform. */
	public static function marks( array $a ): string {
		if ( ! $a['awards'] && ! $a['forms'] ) {
			return '';
		}
		$out = '<span class="pd-amarks" data-pd-fly="marks">';
		foreach ( $a['awards'] as $aw ) {
			$out .= '<span class="pd-amark pd-amark--award"><i title="' . esc_attr( $aw ) . '">' . self::MEDAL . '</i><span class="pd-amark__lbl">' . esc_html( $aw ) . '</span><span class="screen-reader-text pd-amark__sr">' . esc_html( $aw ) . '</span></span>';
		}
		foreach ( $a['forms'] as $f ) {
			$out .= '<span class="pd-amark pd-amark--form"><i title="' . esc_attr( $f['name'] ) . '">' . Components::drawing( array( 'scene' => $f['scene'] ?? '', 'accent' => $f['accent'] ?? 'gulabi' ) ) . '</i><span class="screen-reader-text">' . esc_html( $f['name'] ) . '</span></span>';
		}
		return $out . '</span>';
	}

	public static function name( array $a, string $tag, string $class ): string {
		return '<' . $tag . ' class="' . esc_attr( $class ) . '" data-pd-fly="name">' . esc_html( $a['name'] )
			. ( $a['nameHi'] ? '<span class="pd-aname__hi" lang="hi">' . esc_html( $a['nameHi'] ) . '</span>' : '' ) . '</' . $tag . '>';
	}

	public static function line( array $a, string $class ): string {
		return '' !== $a['line'] ? '<span class="' . esc_attr( $class ) . '" data-pd-fly="line">' . esc_html( $a['line'] ) . '</span>' : '';
	}
}
