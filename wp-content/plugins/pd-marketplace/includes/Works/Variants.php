<?php
namespace PD\Works;

defined( 'ABSPATH' ) || exit;

/**
 * Colour and size choices of a work (approved prototype, sketch 3): colour dots, then sizes.
 * A work with choices is a WooCommerce variable product whose variations use the global attributes pa_colour and pa_size.
 * Each colour term may carry a hex value (term meta pd_hex, edited on Products > Attributes > Colour); pigment names fall back to the palette.
 */
final class Variants {

	public const HEX = 'pd_hex';

	/** Pigment fallbacks, so a colour named after the palette needs no hex. */
	private const PIGMENTS = array(
		'sindoor' => '#C8321F', 'red' => '#C8321F', 'neel' => '#25306B', 'indigo' => '#25306B', 'blue' => '#25306B',
		'haldi' => '#E9A30C', 'yellow' => '#E9A30C', 'patta' => '#3E7B3A', 'green' => '#3E7B3A', 'gulabi' => '#D9467A', 'pink' => '#D9467A',
		'kusum' => '#E26A1B', 'orange' => '#E26A1B', 'kajal' => '#2A1E17', 'black' => '#2A1E17', 'kagaz' => '#E9DCC3', 'cream' => '#E9DCC3', 'white' => '#FFFAF0',
	);

	public static function register_hooks(): void {
		add_action( 'init', array( self::class, 'register' ), 30 );
		add_action( 'pa_colour_add_form_fields', array( self::class, 'add_field' ) );
		add_action( 'pa_colour_edit_form_fields', array( self::class, 'edit_field' ) );
		add_action( 'created_pa_colour', array( self::class, 'save' ) );
		add_action( 'edited_pa_colour', array( self::class, 'save' ) );
		add_action( 'wp_loaded', array( self::class, 'resolve_variation' ), 19 ); // before WooCommerce's add_to_cart_action (20)
	}

	/**
	 * Our colour and size forms post the picked attributes without a variation id (they work without JavaScript).
	 * Find the matching variation, so WooCommerce's own form handler can add it.
	 */
	public static function resolve_variation(): void {
		// phpcs:disable WordPress.Security.NonceVerification -- WooCommerce's add-to-cart form carries no nonce either
		if ( empty( $_REQUEST['add-to-cart'] ) || ! empty( $_REQUEST['variation_id'] ) || ! function_exists( 'wc_get_product' ) ) {
			return;
		}
		$product = wc_get_product( absint( wp_unslash( $_REQUEST['add-to-cart'] ) ) );
		if ( ! $product || ! $product->is_type( 'variable' ) ) {
			return;
		}
		$attrs = array();
		foreach ( $_REQUEST as $key => $value ) {
			if ( is_string( $key ) && str_starts_with( $key, 'attribute_' ) && is_string( $value ) ) {
				$attrs[ sanitize_title( $key ) ] = sanitize_title( wp_unslash( $value ) );
			}
		}
		$id = $attrs ? ( new \WC_Product_Data_Store_CPT() )->find_matching_product_variation( $product, $attrs ) : 0;
		if ( $id ) {
			$_REQUEST['variation_id'] = (string) $id;
			$_POST['variation_id']    = (string) $id;
		}
		// phpcs:enable
	}

	public static function register(): void {
		if ( taxonomy_exists( 'pa_colour' ) ) {
			register_term_meta( 'pa_colour', self::HEX, array( 'single' => true, 'type' => 'string', 'show_in_rest' => true, 'auth_callback' => static fn() => current_user_can( 'manage_product_terms' ) ) );
		}
	}

	/**
	 * The choices of a work, in the attribute's term order.
	 * @return array{colour: list<array{slug:string,name:string,hex:string}>, size: list<array{slug:string,name:string}>}
	 */
	public static function options( \WC_Product $p ): array {
		$out = array( 'colour' => array(), 'size' => array() );
		if ( ! $p->is_type( 'variable' ) ) {
			return $out;
		}
		/** @var \WC_Product_Variable $p */
		$attrs = $p->get_variation_attributes();
		foreach ( array( 'colour' => 'pa_colour', 'size' => 'pa_size' ) as $key => $tax ) {
			if ( empty( $attrs[ $tax ] ) || ! taxonomy_exists( $tax ) ) {
				continue;
			}
			$terms = wc_get_product_terms( $p->get_id(), $tax, array( 'fields' => 'all' ) );
			foreach ( $terms as $t ) {
				if ( ! in_array( $t->slug, $attrs[ $tax ], true ) ) {
					continue;
				}
				$row = array( 'slug' => $t->slug, 'name' => $t->name );
				if ( 'colour' === $key ) {
					$row['hex'] = self::hex( $t );
				}
				$out[ $key ][] = $row;
			}
		}
		return $out;
	}

	public static function hex( \WP_Term $t ): string {
		$hex = sanitize_hex_color( (string) get_term_meta( $t->term_id, self::HEX, true ) );
		return $hex ?: ( self::PIGMENTS[ $t->slug ] ?? '#E9DCC3' );
	}

	public static function add_field(): void {
		echo '<div class="form-field"><label for="pd_hex">' . esc_html__( 'Colour', 'pd-marketplace' ) . '</label><input type="color" name="pd_hex" id="pd_hex" value="#C8321F"><p>' . esc_html__( 'Shown as the colour dot on the work card and page.', 'pd-marketplace' ) . '</p></div>';
		wp_nonce_field( 'pd_hex', 'pd_hex_nonce' );
	}

	public static function edit_field( \WP_Term $term ): void {
		printf( '<tr class="form-field"><th scope="row"><label for="pd_hex">%s</label></th><td><input type="color" name="pd_hex" id="pd_hex" value="%s"><p class="description">%s</p></td></tr>', esc_html__( 'Colour', 'pd-marketplace' ), esc_attr( self::hex( $term ) ), esc_html__( 'Shown as the colour dot on the work card and page.', 'pd-marketplace' ) );
		wp_nonce_field( 'pd_hex', 'pd_hex_nonce' );
	}

	public static function save( int $term_id ): void {
		if ( ! isset( $_POST['pd_hex_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['pd_hex_nonce'] ) ), 'pd_hex' ) || ! current_user_can( 'manage_product_terms' ) ) {
			return;
		}
		$hex = sanitize_hex_color( wp_unslash( $_POST['pd_hex'] ?? '' ) );
		if ( $hex ) {
			update_term_meta( $term_id, self::HEX, $hex );
		}
	}

	/** The picks in a request (?attribute_pa_colour=...), kept only when they are real choices of the work. @return array<string,string> */
	public static function picked( array $options, array $query ): array {
		$out = array();
		foreach ( array( 'colour', 'size' ) as $key ) {
			$slugs = array_column( $options[ $key ], 'slug' );
			$want  = sanitize_title( (string) ( $query[ 'attribute_pa_' . $key ] ?? '' ) );
			if ( $slugs ) {
				$out[ $key ] = in_array( $want, $slugs, true ) ? $want : $slugs[0];
			}
		}
		return $out;
	}
}
