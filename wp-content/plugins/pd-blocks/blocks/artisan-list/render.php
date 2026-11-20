<?php
/**
 * pd/artisan-list: the Artists tab (owner sketch 5). Ranked artisans first ("#1" is set by hand), then the rest by name.
 * Each card is one link; its portrait, ribbon, name, line and marks (data-pd-fly) grow into the artisan page.
 * @var array $attributes @var string $content @var WP_Block $block
 */
use PD\Artisan\Profile;
use PD\Blocks\Artisans;
use PD\Blocks\Components;

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( Profile::class ) ) {
	return '';
}
wp_enqueue_style( 'pd-blocks' );
$people = array_filter( array_map( array( Profile::class, 'get' ), Profile::ranked_ids() ) );
?>
<section <?php echo get_block_wrapper_attributes( array( 'class' => 'pd-alist' ) ); ?>>
	<h1 class="pd-alist__h"><?php echo esc_html( $attributes['heading'] ?? 'Artists' ); ?></h1>
	<?php if ( '' !== (string) ( $attributes['intro'] ?? '' ) ) : ?><p class="pd-alist__sub"><?php echo esc_html( $attributes['intro'] ); ?></p><?php endif; ?>
	<?php if ( ! $people ) : ?>
		<p class="pd-alist__empty"><?php esc_html_e( 'New artisans are joining soon.', 'pd-blocks' ); ?></p>
	<?php endif; ?>
	<?php foreach ( $people as $a ) : ?>
		<a class="pd-acard" href="<?php echo esc_url( $a['url'] ); ?>" data-pd-shared="artisan-<?php echo (int) $a['id']; ?>"
			data-accent="<?php echo esc_attr( Components::accent( $a['accent'] ) ); ?>" data-pd-focus-id="artisan-<?php echo (int) $a['id']; ?>-link-list">
			<?php echo Artisans::ribbon( $a ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
			<?php echo Artisans::pic( $a ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
			<span class="pd-acard__txt">
				<?php echo Artisans::name( $a, 'span', 'pd-aname pd-acard__name' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
				<?php echo Artisans::line( $a, 'pd-aline pd-acard__line' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
				<?php echo Artisans::marks( $a ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
			</span>
		</a>
	<?php endforeach; ?>
</section>
