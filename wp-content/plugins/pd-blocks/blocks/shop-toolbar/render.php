<?php
/** pd/shop-toolbar: count of pieces and makers, and the grid / list switch (store pd/catalogue). */
use PD\Blocks\State;

defined( 'ABSPATH' ) || exit;

State::require( 'pd/catalogue' );
global $wp_query;
$pieces = (int) ( $wp_query->found_posts ?? 0 );
$makers = 0;
if ( $pieces && is_tax( 'product_cat' ) ) {
	global $wpdb;
	$term   = get_queried_object();
	$makers = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(DISTINCT pm.meta_value) FROM {$wpdb->postmeta} pm JOIN {$wpdb->term_relationships} tr ON tr.object_id = pm.post_id JOIN {$wpdb->term_taxonomy} tt ON tt.term_taxonomy_id = tr.term_taxonomy_id JOIN {$wpdb->posts} p ON p.ID = pm.post_id WHERE pm.meta_key = '_pd_artisan_post_id' AND pm.meta_value > 0 AND tt.term_id = %d AND p.post_status = 'publish'", $term->term_id ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
}
$text = sprintf( _n( '%d piece', '%d pieces', $pieces, 'pd-blocks' ), $pieces ) . ( $makers ? ' · ' . sprintf( _n( '%d maker', '%d makers', $makers, 'pd-blocks' ), $makers ) : '' );
?>
<div <?php echo get_block_wrapper_attributes( array( 'class' => 'pd-toolbar' ) ); ?> data-wp-interactive="pd/catalogue" data-pd-reveal>
	<span class="pd-toolbar__count"><?php echo esc_html( $text ); ?></span>
	<div class="pd-toolbar__views" role="group" aria-label="<?php esc_attr_e( 'View', 'pd-blocks' ); ?>">
		<button type="button" aria-label="<?php esc_attr_e( 'Grid', 'pd-blocks' ); ?>" aria-pressed="true" data-wp-bind--aria-pressed="state.isGrid" data-wp-on--click="actions.showGrid"><svg viewBox="0 0 18 18" fill="currentColor" aria-hidden="true" focusable="false"><rect x="1" y="1" width="7" height="7"/><rect x="10" y="1" width="7" height="7"/><rect x="1" y="10" width="7" height="7"/><rect x="10" y="10" width="7" height="7"/></svg></button>
		<button type="button" aria-label="<?php esc_attr_e( 'List', 'pd-blocks' ); ?>" aria-pressed="false" data-wp-bind--aria-pressed="state.isList" data-wp-on--click="actions.showList"><svg viewBox="0 0 18 18" fill="currentColor" aria-hidden="true" focusable="false"><rect x="1" y="2" width="4" height="3"/><rect x="7" y="2" width="10" height="3"/><rect x="1" y="8" width="4" height="3"/><rect x="7" y="8" width="10" height="3"/><rect x="1" y="14" width="4" height="3"/><rect x="7" y="14" width="10" height="3"/></svg></button>
	</div>
</div>
