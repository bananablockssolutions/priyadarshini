<?php
/**
 * pd/cover: the head of a product-type page. It is the menu row grown: the row's picture is the featured image,
 * the row's title is the h1 and the row's sentence is the first line of the intro, which then continues.
 * data-pd-fly marks the three parts so the row's own parts can travel into them on client navigation, and
 * data-pd-reveal marks what arrives after them. Without JavaScript this is simply the top of the page.
 * @var array $attributes
 */
use PD\Blocks\Components;
use PD\Blocks\Menu;

defined( 'ABSPATH' ) || exit;

$section = (string) ( $attributes['section'] ?? 'auto' );
$item    = 'auto' === $section ? Menu::current() : null;
if ( ! $item && defined( 'REST_REQUEST' ) && REST_REQUEST ) {
	$item = Menu::crafts()[0] ?? null; // editor preview outside a type page
}
if ( ! $item ) {
	return '';
}
wp_enqueue_style( 'pd-blocks' );
$h1 = 'pd-cover-h-' . sanitize_html_class( $item['id'] );
?>
<header <?php echo get_block_wrapper_attributes( array( 'class' => 'pd-tcover', 'data-accent' => Components::accent( (string) $item['accent'] ), 'data-pd-cover' => $item['id'] ) ); ?>>
	<div class="pd-tcover__top">
		<div class="pd-tcover__img"><span class="pd-tcover__frame" data-pd-reveal="frame"></span><span class="pd-tcover__ill" data-pd-fly="ill"><?php echo Components::drawing( $item, true ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span></div>
		<div class="pd-tcover__words">
			<h1 class="pd-tcover__ttl" id="<?php echo esc_attr( $h1 ); ?>" data-pd-fly="ttl"><?php echo esc_html( $item['en'] ); ?><?php if ( $item['hi'] ) : ?><span class="pd-hi" lang="hi"><?php echo esc_html( $item['hi'] ); ?></span><?php endif; ?></h1>
			<?php if ( $item['teaser'] ) : ?><p class="pd-tcover__dsc" data-pd-fly="dsc"><?php echo esc_html( $item['teaser'] ); ?></p><?php endif; ?>
		</div>
	</div>
	<?php if ( $item['more'] ) : ?><div class="pd-tcover__more" data-pd-reveal><?php echo wp_kses_post( $item['more'] ); ?></div><?php endif; ?>
</header>
