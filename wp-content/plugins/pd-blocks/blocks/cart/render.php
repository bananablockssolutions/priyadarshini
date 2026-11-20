<?php
/**
 * pd/cart: the cart page on a phone (approved prototype, sketch 4). The Bag icon from the header flies down and becomes
 * the big icon in .pd-cart__slot, with C A R T written under it; leaving the page sends it back up (navigation-store.js).
 * Rows come from the WooCommerce cart; Remove is a WooCommerce remove link, upgraded to the Store API by pd/bag.
 * @var array $attributes @var string $content @var WP_Block $block
 */
use PD\Blocks\Components;
use PD\Blocks\State;
use PD\Works\Variants;

defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'WC' ) || ! WC()->cart ) {
	return '';
}
State::require( 'pd/bag' );
wp_enqueue_style( 'pd-blocks' );

$cart  = WC()->cart;
$rows  = array();
$total = 0.0;
foreach ( $cart->get_cart() as $key => $item ) {
	$p = $item['data'] ?? null;
	if ( ! $p instanceof WC_Product ) {
		continue;
	}
	$parent = (int) $item['product_id'];
	$line   = (float) $item['line_subtotal'] + ( wc_prices_include_tax() ? (float) $item['line_subtotal_tax'] : 0 );
	$total += $line;
	$colour = null;
	$size   = '';
	foreach ( (array) ( $item['variation'] ?? array() ) as $attr => $value ) {
		$tax  = substr( (string) $attr, 10 ); // attribute_pa_colour -> pa_colour
		$term = $value && taxonomy_exists( $tax ) ? get_term_by( 'slug', $value, $tax ) : null;
		if ( 'pa_colour' === $tax && $term ) {
			$colour = array( 'name' => $term->name, 'hex' => Variants::hex( $term ) );
		} elseif ( 'pa_size' === $tax ) {
			$size = $term ? $term->name : (string) $value;
		}
	}
	$view   = \PD\Works\View::get( $parent );
	$rows[] = array(
		'key'    => $key,
		'name'   => get_the_title( $parent ),
		'url'    => get_permalink( $parent ),
		'qty'    => (int) $item['quantity'],
		'line'   => $line,
		'colour' => $colour,
		'size'   => $size,
		'view'   => $view,
		'remove' => wc_get_cart_remove_url( $key ),
	);
}
$count = (int) $cart->get_cart_contents_count();
$label = static fn( int $n ): string => 1 === $n ? '1 piece' : $n . ' pieces';
$bag   = '<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false" fill="none" stroke="currentColor" stroke-width="2" stroke-linejoin="round"><path d="M5 8h14l-1.2 12.5H6.2Z"/><path d="M9 8V6a3 3 0 0 1 6 0v2"/></svg>';
?>
<section <?php echo get_block_wrapper_attributes( array( 'class' => 'pd-cart' ) ); ?> data-pd-cart data-wp-interactive="pd/bag">
	<div class="pd-cart__head">
		<span class="pd-cart__slot" data-pd-bag-slot><?php echo $bag; // phpcs:ignore WordPress.Security.EscapeOutput ?></span>
		<h1 class="pd-cart__title" aria-label="<?php esc_attr_e( 'Cart', 'pd-blocks' ); ?>"><?php foreach ( array( 'C', 'A', 'R', 'T' ) as $ch ) : ?><span aria-hidden="true"><?php echo esc_html( $ch ); ?></span><?php endforeach; ?></h1>
		<p class="pd-cart__count" data-pd-reveal data-pd-count><?php echo esc_html( $label( $count ) ); ?></p>
	</div>
	<div class="pd-cart__rows">
		<?php foreach ( $rows as $r ) : ?>
		<div class="pd-crow" data-pd-reveal data-pd-line="<?php echo esc_attr( (string) $r['line'] ); ?>" data-pd-qty="<?php echo (int) $r['qty']; ?>">
			<a class="pd-crow__th" href="<?php echo esc_url( $r['url'] ); ?>" tabindex="-1" aria-hidden="true"><?php echo $r['view'] ? Components::work_pic( array_merge( $r['view'], array( 'badge' => '' ) ), 'pd-crow__pic' ) : ''; // phpcs:ignore WordPress.Security.EscapeOutput ?></a>
			<div class="pd-crow__words">
				<a class="pd-crow__name" href="<?php echo esc_url( $r['url'] ); ?>"><?php echo esc_html( $r['name'] ); ?></a>
				<p class="pd-crow__meta">
					<?php if ( $r['colour'] ) : ?><i style="background:<?php echo esc_attr( $r['colour']['hex'] ); ?>"></i><span class="screen-reader-text"><?php echo esc_html( $r['colour']['name'] ); ?></span><?php endif; ?>
					<?php echo esc_html( trim( $r['size'] . ( $r['qty'] > 1 ? ' × ' . $r['qty'] : '' ) ) ); ?>
				</p>
			</div>
			<div class="pd-crow__end">
				<p class="pd-crow__price"><?php echo esc_html( Components::money( $r['line'] ) ); ?></p>
				<a class="pd-crow__rm" href="<?php echo esc_url( $r['remove'] ); ?>" data-pd-key="<?php echo esc_attr( $r['key'] ); ?>" data-pd-reload
					data-wp-on--click="actions.removeLine" aria-label="<?php echo esc_attr( sprintf( /* translators: %s: work */ __( 'Remove %s', 'pd-blocks' ), $r['name'] ) ); ?>"><?php esc_html_e( 'Remove', 'pd-blocks' ); ?></a>
			</div>
		</div>
		<?php endforeach; ?>
		<p class="pd-cart__empty" data-pd-reveal <?php echo $rows ? 'hidden' : ''; ?>><?php esc_html_e( 'Your bag is empty.', 'pd-blocks' ); ?></p>
	</div>
	<?php if ( $rows ) : ?>
	<div class="pd-cart__end" data-pd-cart-end>
		<p class="pd-cart__total" data-pd-reveal><span><?php esc_html_e( 'Total', 'pd-blocks' ); ?></span><span data-pd-total><?php echo esc_html( Components::money( $total ) ); ?></span></p>
		<p class="pd-cart__note" data-pd-reveal><?php esc_html_e( 'Delivery is free. Each maker ships their own pieces.', 'pd-blocks' ); ?></p>
		<a class="pd-cart__checkout" data-pd-reveal href="<?php echo esc_url( wc_get_checkout_url() ); ?>"><?php esc_html_e( 'Checkout', 'pd-blocks' ); ?></a>
	</div>
	<?php endif; ?>
</section>
