<?php
/**
 * pd/filters-rail: the Filters strip and its sheet. The sheet is a GET form, so it works without JavaScript and
 * is applied by the router like any other link. Filters read by Plugin::catalogue_filters().
 */
use PD\Blocks\State;

defined( 'ABSPATH' ) || exit;

State::require( 'pd/catalogue' );
$avail  = isset( $_GET['pd_avail'] ) ? (array) wp_unslash( $_GET['pd_avail'] ) : array(); // phpcs:ignore WordPress.Security.NonceVerification,WordPress.Security.ValidatedSanitizedInput
$under  = isset( $_GET['pd_under'] ) ? absint( $_GET['pd_under'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification
$action = strtok( (string) ( $_SERVER['REQUEST_URI'] ?? '/' ), '?' ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
$active = count( $avail ) + ( $under ? 1 : 0 );
?>
<div <?php echo get_block_wrapper_attributes( array( 'class' => 'pd-rail-wrap' ) ); ?> data-wp-interactive="pd/catalogue" data-pd-reveal>
	<a class="pd-rail" href="#pd-filters" role="button" aria-controls="pd-filters" aria-expanded="false" data-wp-bind--aria-expanded="state.sheetOpen" data-wp-on--click="actions.openSheet">
		<?php esc_html_e( 'Filters', 'pd-blocks' ); ?><?php if ( $active ) : ?> <span class="pd-rail__n">(<?php echo (int) $active; ?>)</span><?php endif; ?> <span aria-hidden="true">≡</span>
	</a>
	<div class="pd-sheet" id="pd-filters" role="dialog" aria-label="<?php esc_attr_e( 'Filters', 'pd-blocks' ); ?>" data-wp-class--is-open="state.sheetOpen" data-wp-on--keydown="actions.sheetKey">
		<a class="pd-sheet__x" href="#" aria-label="<?php esc_attr_e( 'Close filters', 'pd-blocks' ); ?>" data-wp-on--click="actions.closeSheet">×</a>
		<h2 class="pd-sheet__title"><?php esc_html_e( 'Filters', 'pd-blocks' ); ?></h2>
		<form method="get" action="<?php echo esc_url( $action ); ?>" data-pd-route data-wp-on--submit="actions.closeSheet">
			<label><input type="checkbox" name="pd_avail[]" value="ready" <?php checked( in_array( 'ready', $avail, true ) ); ?>> <?php esc_html_e( 'Ready to ship', 'pd-blocks' ); ?></label>
			<label><input type="checkbox" name="pd_avail[]" value="order" <?php checked( in_array( 'order', $avail, true ) ); ?>> <?php esc_html_e( 'Made on order', 'pd-blocks' ); ?></label>
			<label><input type="checkbox" name="pd_under" value="2000" <?php checked( 2000, $under ); ?>> <?php esc_html_e( 'Under ₹2,000', 'pd-blocks' ); ?></label>
			<p class="pd-sheet__actions"><button class="pd-btn pd-btn--small" type="submit"><?php esc_html_e( 'Show pieces', 'pd-blocks' ); ?></button> <a class="pd-btn pd-btn--text" href="<?php echo esc_url( $action ); ?>"><?php esc_html_e( 'Clear', 'pd-blocks' ); ?></a></p>
		</form>
	</div>
</div>
