<?php
/**
 * pd/work-page: the work page on a phone (approved prototype, sketch 3). No maker name here (owner, 2026-11-20).
 * The page's main element carries data-pd-hero, so Back can fly its parts into the card they came from.
 * Choices in the address (?attribute_pa_colour=...) arrive picked, which is how picks carry over from the card.
 * @var array $attributes @var string $content @var WP_Block $block
 */
use PD\Blocks\Components;
use PD\Blocks\Menu;
use PD\Blocks\State;
use PD\Works\Variants;
use PD\Works\View;

defined( 'ABSPATH' ) || exit;

$id   = (int) ( $block->context['postId'] ?? get_queried_object_id() );
$work = $id && 'product' === get_post_type( $id ) ? View::get( $id ) : null;
if ( ! $work ) {
	return '';
}
State::require( 'pd/bag' );
State::require( 'pd/catalogue' );
wp_enqueue_style( 'pd-blocks' );

$type    = Menu::type_of( $id );
$back    = $type ? $type['href'] : ( function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'shop' ) : home_url( '/' ) );
$picked  = Variants::picked( $work['options'], $_GET ); // phpcs:ignore WordPress.Security.NonceVerification -- display only
$product = wc_get_product( $id );
$desc    = trim( (string) $product->get_description() );
$desc    = '' !== $desc ? wpautop( do_blocks( wp_kses_post( $desc ) ) ) : '';
$vt      = 'work-' . $id;
?>
<article <?php echo get_block_wrapper_attributes( array( 'class' => 'pd-wpage' ) ); ?> data-pd-shared="<?php echo esc_attr( $vt ); ?>" data-pd-hero
	<?php echo $work['artisan'] ? 'data-accent="' . esc_attr( Components::accent( $work['artisan']['accent'] ) ) . '"' : ''; ?>>
	<a class="pd-wpage__back" href="<?php echo esc_url( $back ); ?>" data-pd-back data-pd-reveal><span aria-hidden="true">‹ </span><?php echo esc_html( $type ? $type['en'] : __( 'All works', 'pd-blocks' ) ); ?></a>
	<?php echo Components::work_pic( $work, 'pd-wpage__pic', true ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
	<h1 class="pd-wpage__name" data-pd-fly="name"><?php echo esc_html( $work['title'] ); ?></h1>
	<?php if ( $work['titleHi'] ) : ?><p class="pd-wpage__hi" lang="hi" data-pd-reveal><?php echo esc_html( $work['titleHi'] ); ?></p><?php endif; ?>
	<?php if ( $desc ) : ?><div class="pd-wpage__desc" data-pd-reveal><?php echo $desc; // phpcs:ignore WordPress.Security.EscapeOutput -- kses above ?></div><?php endif; ?>
	<?php echo Components::pick_form( $work, $picked, 'page' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
	<p class="pd-wpage__note" data-pd-reveal><?php echo esc_html( $work['availability'] ); ?><?php echo $work['material'] ? ' · ' . esc_html( $work['material'] ) : ''; ?></p>
	<?php if ( in_array( $work['format'], array( 'buy_commission', 'commission' ), true ) && $work['artisan'] ) : ?>
		<a class="pd-wpage__commission" data-pd-reveal href="<?php echo esc_url( $work['artisan']['url'] . '#commission' ); ?>"><?php echo esc_html( $work['canBuy'] ? __( 'Commission one made for you', 'pd-blocks' ) : __( 'Commission one like it', 'pd-blocks' ) ); ?></a>
	<?php endif; ?>
</article>
