<?php
/**
 * pd/routed-main: the router region "pd-main" (BLOCKS.md 10.4). Replaced on every client navigation.
 * data-pd-path lets the navigation store know the swap has landed; data-pd-routable="0" asks for a full page load instead.
 * @var array $attributes @var string $content @var WP_Block $block
 */
use PD\Blocks\Route;
use PD\Blocks\State;

defined( 'ABSPATH' ) || exit;

State::require( 'pd/navigation' );
$title = wp_get_document_title();
?>
<main <?php echo get_block_wrapper_attributes( array( 'id' => 'pd-main', 'class' => 'pd-main' ) ); ?>
	data-wp-interactive="pd/navigation"
	data-wp-router-region="pd-main"
	data-pd-path="<?php echo esc_attr( Route::path() ); ?>"
	data-pd-title="<?php echo esc_attr( html_entity_decode( $title, ENT_QUOTES ) ); ?>"
	data-pd-routable="<?php echo Route::routable() ? '1' : '0'; ?>">
	<?php echo $content; // phpcs:ignore WordPress.Security.EscapeOutput -- rendered inner blocks ?>
</main>
