<?php
/**
 * pd/cover: the page a menu card opens starts with that card's illustration as its cover and continues the card's words.
 * The illustration carries the shared-element name of the card (data-pd-vt, data-pd-vt-dest), so on client navigation
 * it grows out of the card into this place. Without JavaScript this is simply the top of the page.
 * @var array $attributes
 */
use PD\Blocks\Components;
use PD\Blocks\Menu;

defined( 'ABSPATH' ) || exit;

$section = (string) ( $attributes['section'] ?? 'auto' );
$item    = null;
if ( 'auto' === $section ) {
	$item = Menu::current();
} else {
	foreach ( Menu::items() as $candidate ) {
		if ( 'section-' . $section === $candidate['id'] ) {
			$item = $candidate;
		}
	}
}
if ( ! $item && defined( 'REST_REQUEST' ) && REST_REQUEST ) {
	$item = Menu::items()[0] ?? null; // editor preview outside a craft page
}
if ( ! $item ) {
	return '';
}
wp_enqueue_style( 'pd-blocks' );
$h1 = 'pd-cover-h-' . sanitize_html_class( $item['id'] );
?>
<header <?php echo get_block_wrapper_attributes( array( 'class' => 'pd-cover', 'data-accent' => Components::accent( (string) $item['accent'] ), 'aria-labelledby' => $h1 ) ); ?>>
	<?php echo Components::illustration( $item, (string) $item['id'], true, true, 'pd-cover__art' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
	<div class="pd-cover__text">
		<p class="pd-cover__kind"><span><?php echo esc_html( $item['kind'] ); ?></span> <span lang="hi"><?php echo esc_html( $item['kindHi'] ); ?></span></p>
		<h1 class="pd-cover__title" id="<?php echo esc_attr( $h1 ); ?>"><?php echo esc_html( $item['en'] ); ?></h1>
		<?php if ( $item['hi'] ) : ?><p class="pd-cover__title-hi" lang="hi"><?php echo esc_html( $item['hi'] ); ?></p><?php endif; ?>
		<div class="pd-cover__intro">
			<?php if ( $item['teaser'] ) : ?><p class="pd-cover__teaser"><?php echo esc_html( $item['teaser'] ); ?></p><?php endif; ?>
			<?php if ( $item['more'] ) : ?><div class="pd-cover__more"><?php echo wp_kses_post( $item['more'] ); ?></div><?php endif; ?>
		</div>
	</div>
</header>
