<?php
namespace PD\Blocks;

use PD\Crafts\Meta as CraftMeta;

defined( 'ABSPATH' ) || exit;

/**
 * The illustrated menu (the haat, SRS DIS-01): crafts first, then the makers and all works.
 * Every item is an illustration, a title in both scripts and a one or two line teaser. The page an item opens
 * starts with the same illustration as its cover and the same words as its intro (pd/cover), so the menu card
 * and the page it opens share one entity id ("craft-12", "section-artisans") for the shared-element transition.
 */
final class Menu {

	/** @return array<int,array> */
	public static function items(): array {
		static $items = null;
		if ( null !== $items ) {
			return $items;
		}
		$items = array();
		foreach ( self::crafts() as $craft ) {
			$items[] = $craft;
		}
		foreach ( self::sections() as $section ) {
			$items[] = $section;
		}
		$items = (array) apply_filters( 'pd_menu_items', $items );
		return $items;
	}

	/** Crafts: WooCommerce product categories, top level, ordered by their menu position. */
	public static function crafts(): array {
		if ( ! taxonomy_exists( 'product_cat' ) || ! class_exists( CraftMeta::class ) ) {
			return array();
		}
		$terms = get_terms(
			array(
				'taxonomy'   => 'product_cat',
				'parent'     => 0,
				'hide_empty' => false,
				'exclude'    => array( (int) get_option( 'default_product_cat' ) ),
			)
		);
		if ( is_wp_error( $terms ) ) {
			return array();
		}
		$out = array();
		foreach ( $terms as $term ) {
			$item = self::craft( $term );
			if ( $item && ! $item['hidden'] ) {
				$out[] = $item;
			}
		}
		usort( $out, static fn( $a, $b ) => array( $a['order'], $a['en'] ) <=> array( $b['order'], $b['en'] ) );
		return $out;
	}

	public static function craft( \WP_Term $term ): ?array {
		$m    = CraftMeta::get( $term );
		$link = get_term_link( $term );
		if ( is_wp_error( $link ) ) {
			return null;
		}
		$desc   = trim( wp_strip_all_tags( (string) $term->description ) );
		$teaser = $m['teaser'] ?: self::first_sentence( $desc );
		return array(
			'id'     => 'craft-' . $term->term_id,
			'group'  => 'crafts',
			'kind'   => 'Collection',
			'kindHi' => 'संग्रह',
			'href'   => $link,
			'en'     => $term->name,
			'hi'     => $m['nameHi'],
			'teaser' => $teaser,
			'more'   => self::continuation( $teaser, (string) $term->description ),
			'cover'  => $m['cover'],
			'scene'  => $m['scene'],
			'accent' => $m['accent'],
			'order'  => $m['order'],
			'hidden' => $m['hidden'],
			'alt'    => $term->name,
		);
	}

	/** The two fixed sections of the menu. Words here are placeholders until the owner writes them. */
	public static function sections(): array {
		$shop = function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'shop' ) : home_url( '/shop/' );
		return array(
			array(
				'id'     => 'section-artisans',
				'group'  => 'makers',
				'kind'   => 'Makers',
				'kindHi' => 'कारीगर',
				'href'   => (string) ( get_post_type_archive_link( 'pd_artisan' ) ?: home_url( '/artisans/' ) ),
				'en'     => 'Artisans',
				'hi'     => 'कारीगर',
				'teaser' => 'Meet the named makers of Bihar, village by village.',
				'more'   => '<p>Every artisan here has a page of their own: their face, their village, who taught them and the awards they have won. Follow the ones you love, and buy or commission straight from their hands.</p>',
				'cover'  => 0,
				'scene'  => 'person',
				'accent' => 'kusum',
				'order'  => 1000,
				'hidden' => false,
				'alt'    => 'Artisans',
			),
			array(
				'id'     => 'section-works',
				'group'  => 'makers',
				'kind'   => 'Works',
				'kindHi' => 'कृतियाँ',
				'href'   => (string) $shop,
				'en'     => 'All works',
				'hi'     => 'सभी कृतियाँ',
				'teaser' => 'Every piece signed by the hand that made it.',
				'more'   => '<p>Paintings, grass work, embroidery and silk, each with its maker’s name and catalogue number. Some are ready to ship; others are made for you once you order.</p>',
				'cover'  => 0,
				'scene'  => 'lotus',
				'accent' => 'gulabi',
				'order'  => 1001,
				'hidden' => false,
				'alt'    => 'All works',
			),
		);
	}

	/** The menu item for the page being rendered, or null. */
	public static function current(): ?array {
		$id = '';
		if ( is_tax( 'product_cat' ) ) {
			$term = get_queried_object();
			return $term instanceof \WP_Term ? self::craft( $term ) : null;
		} elseif ( is_post_type_archive( 'pd_artisan' ) ) {
			$id = 'section-artisans';
		} elseif ( function_exists( 'is_shop' ) && is_shop() ) {
			$id = 'section-works';
		}
		foreach ( self::items() as $item ) {
			if ( $item['id'] === $id ) {
				return $item;
			}
		}
		return null;
	}

	private static function first_sentence( string $text ): string {
		if ( '' === $text ) {
			return '';
		}
		return preg_match( '/^.{20,220}?[.!?।](\s|$)/u', $text, $m ) ? trim( $m[0] ) : wp_trim_words( $text, 24 );
	}

	/** The description, minus the teaser when it opens with the same words, so the page "continues" the card. */
	private static function continuation( string $teaser, string $description ): string {
		$html = trim( $description );
		if ( '' === $html ) {
			return '';
		}
		$plain = trim( wp_strip_all_tags( $html ) );
		if ( '' !== $teaser && str_starts_with( $plain, $teaser ) ) {
			$rest = trim( substr( $plain, strlen( $teaser ) ) );
			return '' === $rest ? '' : wpautop( esc_html( $rest ) );
		}
		return wpautop( wp_kses_post( $html ) );
	}
}
