<?php
namespace PD\Artisan;

defined( 'ABSPATH' ) || exit;

/**
 * Public identity of an artisan (SRS DB-11, DB-13). Operational data lives in pd_artisans.
 */
final class PostType {

	public const NAME = 'pd_artisan';

	public static function register_hooks(): void {
		add_action( 'init', array( self::class, 'register' ), 5 );
		add_action( 'init', array( self::class, 'attach_craft_taxonomy' ), 20 );
	}

	public static function register(): void {
		register_post_type(
			self::NAME,
			array(
				'labels'       => array(
					'name'          => __( 'Artisans', 'pd-marketplace' ),
					'singular_name' => __( 'Artisan', 'pd-marketplace' ),
				),
				'public'       => true,
				'show_in_rest' => true,
				'has_archive'  => 'artisans',
				'rewrite'      => array( 'slug' => 'a', 'with_front' => false ),
				'supports'     => array( 'title', 'editor', 'thumbnail', 'excerpt', 'revisions', 'custom-fields' ),
				'menu_icon'    => 'dashicons-art',
				'capability_type' => array( 'pd_artisan', 'pd_artisans' ),
				'map_meta_cap' => true,
			)
		);

		foreach ( array( 'pd_district' => 'Districts', 'pd_language' => 'Languages' ) as $tax => $label ) {
			register_taxonomy(
				$tax,
				self::NAME,
				array(
					'label'        => $label,
					'public'       => true,
					'hierarchical' => false,
					'show_in_rest' => true,
					'rewrite'      => array( 'slug' => substr( $tax, 3 ) ),
				)
			);
		}

		foreach ( array( '_pd_name_hi', '_pd_village', '_pd_village_hi', '_pd_lineage' ) as $key ) {
			register_post_meta(
				self::NAME,
				$key,
				array(
					'single'       => true,
					'type'         => 'string',
					'show_in_rest' => true,
					'auth_callback' => static fn() => current_user_can( 'edit_pd_artisans' ),
				)
			);
		}
	}

	/** Craft is the shared product category taxonomy (SRS DB-11, M-1 spike 1). */
	public static function attach_craft_taxonomy(): void {
		if ( taxonomy_exists( 'product_cat' ) ) {
			register_taxonomy_for_object_type( 'product_cat', self::NAME );
		}
	}
}
