<?php
/** @var array $attributes @var string $content @var WP_Block $block */
use PD\Artisan\Table;
use PD\Blocks\State;

defined( 'ABSPATH' ) || exit;

$id   = (int) ( $block->context['postId'] ?? 0 );
$post = $id ? get_post( $id ) : null;
$ops  = $id ? Table::public_view( $id ) : null;
if ( ! $post || 'pd_artisan' !== $post->post_type || 'publish' !== $post->post_status || ! $ops ) {
	return '';
}
State::require( 'pd/artisan', array( 'ids' => array( $id ) ) );
wp_enqueue_style( 'pd-blocks' );

$name_hi = (string) get_post_meta( $id, '_pd_name_hi', true );
$village = (string) get_post_meta( $id, '_pd_village', true );
$count   = (int) $ops['rating_count'];
$status  = ! (int) $ops['taking_orders'] ? 'Between orders' : ( ! empty( $ops['away_until'] ) && strtotime( $ops['away_until'] ) > time() ? 'Away until ' . wp_date( 'j M', strtotime( $ops['away_until'] ) ) : 'Taking orders' );
$ctx     = wp_interactivity_data_wp_context( array( 'artisanPostId' => $id ) );
$focus   = sanitize_key( $attributes['focusContext'] ?? 'dir' );
?>
<article <?php echo get_block_wrapper_attributes( array( 'class' => 'pd-artisan-card', 'data-accent' => $ops['accent'] ) ); ?>
	data-pd-entity="artisan" data-pd-entity-id="artisan-<?php echo (int) $id; ?>"
	data-wp-interactive="pd/artisan" <?php echo $ctx; // phpcs:ignore WordPress.Security.EscapeOutput ?>>
	<a class="pd-artisan-card__link" href="<?php echo esc_url( get_permalink( $post ) ); ?>"
		data-pd-transition="source" data-pd-focus-id="artisan-<?php echo (int) $id; ?>-link-<?php echo esc_attr( $focus ); ?>">
		<span class="pd-artisan-card__name"><?php echo esc_html( get_the_title( $post ) ); ?></span>
		<?php if ( $name_hi ) : ?><span class="pd-artisan-card__name-hi" lang="hi"><?php echo esc_html( $name_hi ); ?></span><?php endif; ?>
	</a>
	<?php if ( $village ) : ?><p class="pd-artisan-card__place"><?php echo esc_html( $village ); ?></p><?php endif; ?>
	<p class="pd-artisan-card__rating">
		<?php if ( $count > 0 ) : ?>
			<span aria-label="<?php echo esc_attr( sprintf( 'Rated %s out of 5 from %d reviews', number_format_i18n( (float) $ops['rating_avg'], 1 ), $count ) ); ?>"><?php echo esc_html( '★ ' . number_format_i18n( (float) $ops['rating_avg'], 1 ) . ' (' . $count . ')' ); ?></span>
		<?php else : ?>
			<span class="pd-newtag">New</span>
		<?php endif; ?>
	</p>
	<p class="pd-artisan-card__status"><?php echo esc_html( $status ); ?></p>
	<p class="pd-artisan-card__followers" data-wp-text="state.followersLabel"><?php echo esc_html( 1 === (int) $ops['follower_count'] ? '1 follower' : (int) $ops['follower_count'] . ' followers' ); ?></p>
</article>
