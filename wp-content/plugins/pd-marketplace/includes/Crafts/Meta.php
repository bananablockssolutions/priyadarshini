<?php
namespace PD\Crafts;

defined( 'ABSPATH' ) || exit;

/**
 * A craft is a WooCommerce product category (SRS DIS-06). It carries what the illustrated menu and the craft page need:
 * a cover illustration, the name in Devanagari, a one or two line teaser (the menu card text, which the craft page
 * continues), a placeholder scene and pigment for when there is no illustration yet, and a menu position.
 * Edited on Products > Categories.
 */
final class Meta {

	public const NAME_HI = 'pd_name_hi';
	public const TEASER  = 'pd_teaser';
	public const COVER   = 'pd_cover_id';
	public const SCENE   = 'pd_scene';
	public const ACCENT  = 'pd_accent';
	public const ORDER   = 'pd_menu_order';
	public const HIDDEN  = 'pd_menu_hidden';

	public const SCENES  = array( 'fish', 'lotus', 'leaf', 'peacock', 'sun', 'person', 'studio' );
	public const ACCENTS = array( 'gulabi', 'kusum', 'neel', 'patta', 'sindoor', 'haldi' );

	public static function register_hooks(): void {
		add_action( 'init', array( self::class, 'register' ), 20 );
		add_action( 'product_cat_add_form_fields', array( self::class, 'add_fields' ) );
		add_action( 'product_cat_edit_form_fields', array( self::class, 'edit_fields' ) );
		add_action( 'created_product_cat', array( self::class, 'save' ) );
		add_action( 'edited_product_cat', array( self::class, 'save' ) );
		add_action( 'admin_enqueue_scripts', array( self::class, 'admin_assets' ) );
	}

	public static function register(): void {
		if ( ! taxonomy_exists( 'product_cat' ) ) {
			return;
		}
		$auth = static fn() => current_user_can( 'manage_product_terms' );
		foreach ( array( self::NAME_HI, self::TEASER, self::SCENE, self::ACCENT ) as $key ) {
			register_term_meta( 'product_cat', $key, array( 'single' => true, 'type' => 'string', 'show_in_rest' => true, 'auth_callback' => $auth ) );
		}
		foreach ( array( self::COVER, self::ORDER, self::HIDDEN ) as $key ) {
			register_term_meta( 'product_cat', $key, array( 'single' => true, 'type' => 'integer', 'show_in_rest' => true, 'auth_callback' => $auth, 'default' => 0 ) );
		}
	}

	/** Public data for one craft. */
	public static function get( \WP_Term $term ): array {
		$id     = (int) $term->term_id;
		$scene  = (string) get_term_meta( $id, self::SCENE, true );
		$accent = (string) get_term_meta( $id, self::ACCENT, true );
		return array(
			'termId' => $id,
			'nameHi' => (string) get_term_meta( $id, self::NAME_HI, true ),
			'teaser' => (string) get_term_meta( $id, self::TEASER, true ),
			'cover'  => (int) get_term_meta( $id, self::COVER, true ),
			'scene'  => in_array( $scene, self::SCENES, true ) ? $scene : 'fish',
			'accent' => in_array( $accent, self::ACCENTS, true ) ? $accent : 'gulabi',
			'order'  => (int) get_term_meta( $id, self::ORDER, true ),
			'hidden' => (bool) get_term_meta( $id, self::HIDDEN, true ),
		);
	}

	public static function set( int $term_id, array $f ): void {
		$map = array( 'nameHi' => self::NAME_HI, 'teaser' => self::TEASER, 'cover' => self::COVER, 'scene' => self::SCENE, 'accent' => self::ACCENT, 'order' => self::ORDER, 'hidden' => self::HIDDEN );
		foreach ( $map as $k => $key ) {
			if ( array_key_exists( $k, $f ) ) {
				update_term_meta( $term_id, $key, is_int( $f[ $k ] ) || is_bool( $f[ $k ] ) ? (int) $f[ $k ] : sanitize_text_field( (string) $f[ $k ] ) );
			}
		}
	}

	public static function admin_assets( string $hook ): void {
		if ( in_array( $hook, array( 'edit-tags.php', 'term.php' ), true ) && 'product_cat' === ( $_GET['taxonomy'] ?? '' ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			wp_enqueue_media();
			wp_add_inline_script(
				'media-editor',
				"jQuery(function($){\$(document).on('click','.pd-cover-pick',function(e){e.preventDefault();var f=wp.media({title:'Craft illustration',library:{type:'image'},multiple:false});f.on('select',function(){var a=f.state().get('selection').first().toJSON();\$('#pd_cover_id').val(a.id);\$('.pd-cover-preview').html('<img src=\"'+(a.sizes&&a.sizes.medium?a.sizes.medium.url:a.url)+'\" style=\"max-width:240px;height:auto\">');});f.open();});\$(document).on('click','.pd-cover-clear',function(e){e.preventDefault();\$('#pd_cover_id').val('0');\$('.pd-cover-preview').empty();});});"
			);
		}
	}

	private static function fields( array $v, bool $table ): void {
		wp_nonce_field( 'pd_craft', 'pd_craft_nonce' );
		$rows = array(
			array( self::NAME_HI, __( 'Name in Devanagari', 'pd-marketplace' ), '<input type="text" lang="hi" name="pd_name_hi" id="pd_name_hi" value="' . esc_attr( $v['nameHi'] ) . '">', __( 'For example मधुबनी चित्रकला.', 'pd-marketplace' ) ),
			array( self::TEASER, __( 'Menu card text', 'pd-marketplace' ), '<textarea name="pd_teaser" id="pd_teaser" rows="3" maxlength="200">' . esc_textarea( $v['teaser'] ) . '</textarea>', __( 'One or two lines shown under the illustration in the menu. The craft page starts with these words and continues with the Description.', 'pd-marketplace' ) ),
			array(
				self::COVER,
				__( 'Illustration', 'pd-marketplace' ),
				'<input type="hidden" name="pd_cover_id" id="pd_cover_id" value="' . esc_attr( (string) $v['cover'] ) . '"><div class="pd-cover-preview">' . ( $v['cover'] ? wp_get_attachment_image( $v['cover'], 'medium', false, array( 'style' => 'max-width:240px;height:auto' ) ) : '' ) . '</div><p><button class="button pd-cover-pick">' . esc_html__( 'Choose illustration', 'pd-marketplace' ) . '</button> <button class="button-link pd-cover-clear">' . esc_html__( 'Remove', 'pd-marketplace' ) . '</button></p>',
				__( 'The menu card picture, which becomes the cover of the craft page. Wide pictures work best (about 3:2).', 'pd-marketplace' ),
			),
			array( self::SCENE, __( 'Placeholder motif', 'pd-marketplace' ), self::select( 'pd_scene', self::SCENES, $v['scene'] ), __( 'Drawn when there is no illustration yet.', 'pd-marketplace' ) ),
			array( self::ACCENT, __( 'Pigment', 'pd-marketplace' ), self::select( 'pd_accent', self::ACCENTS, $v['accent'] ), '' ),
			array( self::ORDER, __( 'Menu position', 'pd-marketplace' ), '<input type="number" name="pd_menu_order" id="pd_menu_order" value="' . esc_attr( (string) $v['order'] ) . '" min="0" step="1" style="width:6em">', __( 'Lower numbers come first.', 'pd-marketplace' ) ),
			array( self::HIDDEN, __( 'Hide from menu', 'pd-marketplace' ), '<label><input type="checkbox" name="pd_menu_hidden" id="pd_menu_hidden" value="1"' . checked( $v['hidden'], true, false ) . '> ' . esc_html__( 'Do not show this craft in the menu', 'pd-marketplace' ) . '</label>', '' ),
		);
		foreach ( $rows as [ $key, $label, $control, $help ] ) {
			$id = $key;
			if ( $table ) {
				printf( '<tr class="form-field"><th scope="row"><label for="%1$s">%2$s</label></th><td>%3$s%4$s</td></tr>', esc_attr( $id ), esc_html( $label ), $control, $help ? '<p class="description">' . esc_html( $help ) . '</p>' : '' ); // phpcs:ignore WordPress.Security.EscapeOutput
			} else {
				printf( '<div class="form-field"><label for="%1$s">%2$s</label>%3$s%4$s</div>', esc_attr( $id ), esc_html( $label ), $control, $help ? '<p>' . esc_html( $help ) . '</p>' : '' ); // phpcs:ignore WordPress.Security.EscapeOutput
			}
		}
	}

	private static function select( string $name, array $options, string $current ): string {
		$out = '<select name="' . esc_attr( $name ) . '" id="' . esc_attr( $name ) . '">';
		foreach ( $options as $o ) {
			$out .= '<option value="' . esc_attr( $o ) . '"' . selected( $current, $o, false ) . '>' . esc_html( ucfirst( $o ) ) . '</option>';
		}
		return $out . '</select>';
	}

	public static function add_fields(): void {
		self::fields( array( 'nameHi' => '', 'teaser' => '', 'cover' => 0, 'scene' => 'fish', 'accent' => 'gulabi', 'order' => 0, 'hidden' => false ), false );
	}

	public static function edit_fields( \WP_Term $term ): void {
		self::fields( self::get( $term ), true );
	}

	public static function save( int $term_id ): void {
		if ( ! isset( $_POST['pd_craft_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['pd_craft_nonce'] ) ), 'pd_craft' ) || ! current_user_can( 'manage_product_terms' ) ) {
			return;
		}
		$p = wp_unslash( $_POST );
		self::set(
			$term_id,
			array(
				'nameHi' => (string) ( $p['pd_name_hi'] ?? '' ),
				'teaser' => sanitize_textarea_field( (string) ( $p['pd_teaser'] ?? '' ) ),
				'cover'  => absint( $p['pd_cover_id'] ?? 0 ),
				'scene'  => in_array( $p['pd_scene'] ?? '', self::SCENES, true ) ? $p['pd_scene'] : 'fish',
				'accent' => in_array( $p['pd_accent'] ?? '', self::ACCENTS, true ) ? $p['pd_accent'] : 'gulabi',
				'order'  => absint( $p['pd_menu_order'] ?? 0 ),
				'hidden' => ! empty( $p['pd_menu_hidden'] ),
			)
		);
	}
}
