<?php
/**
 * pd/work-gallery (first cut): main plate and thumbnails. Thumbnails are links to ?img=n, so the chosen image is shareable
 * and works without JavaScript; client navigation swaps the page in place. Transition destination for work-{id}.
 * @var array $attributes @var string $content @var WP_Block $block
 */
use PD\Blocks\Components;
use PD\Works\View;

defined( 'ABSPATH' ) || exit;

$id   = (int) ( $block->context['postId'] ?? get_queried_object_id() );
$work = $id && 'product' === get_post_type( $id ) ? View::get( $id ) : null;
if ( ! $work ) {
	return '';
}
wp_enqueue_style( 'pd-blocks' );
$product = wc_get_product( $id );
$images  = array_values( array_unique( array_filter( array_merge( array( (int) $work['imageId'] ), array_map( 'intval', $product->get_gallery_image_ids() ) ) ) ) );
$current = isset( $_GET['img'] ) ? max( 0, min( count( $images ) - 1, absint( $_GET['img'] ) ) ) : 0; // phpcs:ignore WordPress.Security.NonceVerification
$main    = $images[ $current ] ?? 0;
$alt     = $work['title'] . ( $work['artisan'] ? ', by ' . $work['artisan']['name'] : '' );
?>
<figure <?php echo get_block_wrapper_attributes( array( 'class' => 'pd-gallery' ) ); ?>
	data-pd-entity="work" data-pd-entity-id="work-<?php echo (int) $id; ?>"
	<?php echo $work['artisan'] ? 'data-accent="' . esc_attr( Components::accent( $work['artisan']['accent'] ) ) . '"' : ''; ?>>
	<?php
	$plate = Components::plate( $main, $alt, '4-5', 'work-' . $id, $work['artisan']['motif'] ?? 'fish', '(min-width: 760px) 50vw, 100vw', true );
	echo str_replace( 'data-pd-vt=', 'data-pd-vt-dest data-pd-vt=', $plate ); // phpcs:ignore WordPress.Security.EscapeOutput
	?>
	<?php if ( count( $images ) > 1 ) : ?>
		<ul class="pd-gallery__thumbs" aria-label="<?php esc_attr_e( 'More pictures of this work', 'pd-blocks' ); ?>">
			<?php foreach ( $images as $i => $img ) : ?>
				<li><a href="<?php echo esc_url( add_query_arg( 'img', $i, $work['url'] ) ); ?>" <?php echo $i === $current ? 'aria-current="true"' : ''; ?>
					aria-label="<?php echo esc_attr( sprintf( 'Picture %d of %d', $i + 1, count( $images ) ) ); ?>"
					data-pd-focus-id="<?php echo esc_attr( 'work-' . $id . '-img-' . $i ); ?>"><?php echo wp_get_attachment_image( $img, 'thumbnail', false, array( 'alt' => '' ) ); ?></a></li>
			<?php endforeach; ?>
		</ul>
	<?php endif; ?>
</figure>
