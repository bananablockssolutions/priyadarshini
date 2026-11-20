<?php
/**
 * pd/buy-box (first cut): title in both scripts, maker, facts, price, availability and the three ways to buy (BUY-01, BUY-02).
 * Every action works without JavaScript: Buy now and Add to bag are WooCommerce form posts; Add to bag is upgraded to pd/bag.add.
 * Still to come (slice 2): PIN code delivery check, commission presets, disclosures, return rule, GI badge.
 * @var array $attributes @var string $content @var WP_Block $block
 */
use PD\Blocks\Components;
use PD\Blocks\Route;
use PD\Blocks\State;
use PD\Works\View;

defined( 'ABSPATH' ) || exit;

$id   = (int) ( $block->context['postId'] ?? get_queried_object_id() );
$work = $id && 'product' === get_post_type( $id ) ? View::get( $id ) : null;
if ( ! $work ) {
	return '';
}
State::require( 'pd/bag' );
wp_enqueue_style( 'pd-blocks' );

$maker      = $work['artisan'];
$craft      = $maker ? Route::craft_name( (int) $maker['id'] ) : '';
$terms      = get_the_terms( $id, 'product_cat' );
$craft      = $craft ?: ( $terms && ! is_wp_error( $terms ) ? $terms[0]->name : '' );
$commission = in_array( $work['format'], array( 'buy_commission', 'commission' ), true ) && $maker;
$page       = get_page_by_path( 'commission' );
$comm_url   = $page ? add_query_arg( 'work', $id, get_permalink( $page ) ) : ( $maker ? $maker['url'] . '#commission' : '' );
$checkout   = function_exists( 'wc_get_checkout_url' ) ? wc_get_checkout_url() : '';
$ctx        = wp_interactivity_data_wp_context( array( 'workId' => $id, 'title' => $work['title'] ) );
?>
<div <?php echo get_block_wrapper_attributes( array( 'class' => 'pd-buy' ) ); ?>
	data-wp-interactive="pd/bag" <?php echo $ctx; // phpcs:ignore WordPress.Security.EscapeOutput ?>
	<?php echo $maker ? 'data-accent="' . esc_attr( Components::accent( $maker['accent'] ) ) . '"' : ''; ?>>
	<?php if ( $work['number'] ) : ?><p class="pd-catno"><?php echo esc_html( Components::catalogue_line( (int) $work['number'], null ) ); ?></p><?php endif; ?>
	<h1 class="pd-buy__title"><?php echo esc_html( $work['title'] ); ?></h1>
	<?php if ( $work['titleHi'] ) : ?><p class="pd-buy__title-hi" lang="hi"><?php echo esc_html( $work['titleHi'] ); ?></p><?php endif; ?>
	<?php if ( $maker ) : ?>
		<p class="pd-maker-line" data-pd-entity="artisan" data-pd-entity-id="artisan-<?php echo (int) $maker['id']; ?>">
			<?php echo Components::seal( $maker, 'xs', 'artisan-' . (int) $maker['id'] ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
			<span>by <a href="<?php echo esc_url( $maker['url'] ); ?>"><?php echo esc_html( $maker['name'] ); ?></a><?php if ( $maker['nameHi'] ) : ?> <span lang="hi">(<?php echo esc_html( $maker['nameHi'] ); ?>)</span><?php endif; ?></span>
		</p>
	<?php endif; ?>
	<dl class="pd-facts">
		<?php if ( $craft ) : ?><div><dt>Craft</dt><dd><?php echo esc_html( $craft ); ?></dd></div><?php endif; ?>
		<?php if ( $work['material'] ) : ?><div><dt>Material</dt><dd><?php echo esc_html( $work['material'] ); ?></dd></div><?php endif; ?>
		<?php if ( $work['makingTime'] ) : ?><div><dt>Time to make</dt><dd><?php echo esc_html( $work['makingTime'] ); ?></dd></div><?php endif; ?>
		<?php if ( $maker && $maker['village'] ) : ?><div><dt>Made in</dt><dd><?php echo esc_html( $maker['village'] ); ?></dd></div><?php endif; ?>
	</dl>
	<p class="pd-buy__price pd-price"><?php echo wp_kses_post( $work['priceHtml'] ); ?></p>
	<p class="pd-taxnote">Price includes GST. Shipping is shown before you pay.</p>
	<p class="pd-stock"><span class="pd-format"><?php echo esc_html( $work['formatLabel'] ); ?></span> <span class="pd-avail"><?php echo esc_html( $work['availability'] ); ?></span></p>
	<div class="pd-buy__actions">
		<?php if ( $work['canBuy'] && $checkout ) : ?>
			<?php $picked = \PD\Works\Variants::picked( $work['options'], $_GET ); // phpcs:ignore WordPress.Security.NonceVerification ?>
			<form method="post" action="<?php echo esc_url( $work['url'] ); ?>" class="pd-buy__form" data-wp-on--submit="actions.submitAdd">
				<?php foreach ( array( 'colour' => __( 'Colour', 'pd-blocks' ), 'size' => __( 'Size', 'pd-blocks' ) ) as $key => $legend ) : ?>
					<?php if ( $work['options'][ $key ] ) : ?>
					<fieldset class="pd-buy__opts"><legend><?php echo esc_html( $legend ); ?></legend>
						<?php foreach ( $work['options'][ $key ] as $o ) : ?>
							<label class="pd-chip"><input type="radio" name="attribute_pa_<?php echo esc_attr( $key ); ?>" value="<?php echo esc_attr( $o['slug'] ); ?>" <?php checked( $picked[ $key ] ?? '', $o['slug'] ); ?>><?php if ( 'colour' === $key ) : ?><i style="background:<?php echo esc_attr( $o['hex'] ); ?>"></i><?php endif; ?><?php echo esc_html( $o['name'] ); ?></label>
						<?php endforeach; ?>
					</fieldset>
					<?php endif; ?>
				<?php endforeach; ?>
				<input type="hidden" name="add-to-cart" value="<?php echo (int) $id; ?>">
				<input type="hidden" name="quantity" value="1">
				<button class="pd-btn pd-btn--primary" type="submit" formaction="<?php echo esc_url( $checkout ); ?>" data-pd-buy-now><?php esc_html_e( 'Buy now', 'pd-blocks' ); ?></button>
				<button class="pd-btn" type="submit" aria-busy="false" data-wp-bind--aria-busy="state.pending"><?php esc_html_e( 'Add to bag', 'pd-blocks' ); ?></button>
			</form>
		<?php endif; ?>
		<?php if ( $commission && $comm_url ) : ?>
			<a class="pd-btn <?php echo $work['canBuy'] ? '' : 'pd-btn--primary'; ?>" href="<?php echo esc_url( $comm_url ); ?>"><?php echo $work['canBuy'] ? esc_html__( 'Commission now', 'pd-blocks' ) : esc_html__( 'Commission one like it', 'pd-blocks' ); ?></a>
		<?php endif; ?>
	</div>
	<?php if ( $work['ratingCount'] > 0 || $maker ) : ?>
		<p class="pd-buy__rating"><?php echo Components::rating( (float) $work['ratingAvg'], (int) $work['ratingCount'] ); // phpcs:ignore WordPress.Security.EscapeOutput ?></p>
	<?php endif; ?>
</div>
