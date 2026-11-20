<?php
/**
 * pd/craft-makers: artisans tagged with the craft of this page (product_cat on the pd_artisan post), as pd/artisan-card.
 * @var array $attributes
 */
defined( 'ABSPATH' ) || exit;

$term = is_tax( 'product_cat' ) ? get_queried_object() : null;
if ( ! $term instanceof WP_Term ) {
	return '';
}
$ids = get_posts(
	array(
		'post_type'      => 'pd_artisan',
		'post_status'    => 'publish',
		'posts_per_page' => 12,
		'fields'         => 'ids',
		'no_found_rows'  => true,
		'tax_query'      => array( array( 'taxonomy' => 'product_cat', 'terms' => array( (int) $term->term_id ) ) ), // phpcs:ignore WordPress.DB.SlowDBQuery
	)
);
if ( ! $ids ) {
	return '';
}
$cards = '';
foreach ( $ids as $id ) {
	$cards .= ( new WP_Block( array( 'blockName' => 'pd/artisan-card', 'attrs' => array( 'focusContext' => 'craft' ) ), array( 'postId' => (int) $id, 'postType' => 'pd_artisan' ) ) )->render();
}
?>
<section <?php echo get_block_wrapper_attributes( array( 'class' => 'pd-craft-makers' ) ); ?> aria-labelledby="pd-craft-makers-h">
	<h2 class="pd-section-title" id="pd-craft-makers-h"><?php echo esc_html( $attributes['heading'] ?? '' ); ?> <span lang="hi"><?php echo esc_html( $attributes['headingHi'] ?? '' ); ?></span></h2>
	<div class="pd-craft-makers__grid"><?php echo $cards; // phpcs:ignore WordPress.Security.EscapeOutput ?></div>
</section>
