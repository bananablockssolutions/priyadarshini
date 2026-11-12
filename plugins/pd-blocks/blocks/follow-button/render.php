<?php
/** @var array $attributes @var string $content @var WP_Block $block */
use PD\Artisan\Follows;
use PD\Blocks\State;

defined( 'ABSPATH' ) || exit;

$id = (int) ( $attributes['artisanPostId'] ?? 0 );
if ( ! $id && 'pd_artisan' === ( $block->context['postType'] ?? '' ) ) {
	$id = (int) ( $block->context['postId'] ?? 0 );
}
$ops = $id > 0 ? \PD\Artisan\Table::public_view( $id ) : null;
if ( ! $ops || 'verified' !== $ops['verification_status'] ) {
	return '';
}
State::require( 'pd/artisan', array( 'ids' => array( $id ) ) );

$following = Follows::is_following( get_current_user_id(), $id );
$ctx       = wp_interactivity_data_wp_context( array( 'artisanPostId' => $id ) );
?>
<form <?php echo get_block_wrapper_attributes( array( 'class' => 'pd-follow' ) ); ?>
	method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>"
	data-wp-interactive="pd/artisan" <?php echo $ctx; // phpcs:ignore WordPress.Security.EscapeOutput -- built by core ?>
	data-wp-on--submit="actions.toggleFollow">
	<input type="hidden" name="action" value="pd_follow">
	<input type="hidden" name="artisan" value="<?php echo esc_attr( (string) $id ); ?>">
	<input type="hidden" name="follow" value="<?php echo $following ? '0' : '1'; ?>">
	<?php wp_nonce_field( 'pd_follow_' . $id, '_wpnonce', true, true ); ?>
	<button type="submit" class="pd-btn pd-btn--secondary"
		aria-pressed="<?php echo $following ? 'true' : 'false'; ?>"
		data-wp-bind--aria-pressed="state.isFollowing"
		data-wp-text="state.followLabel"><?php echo $following ? 'Following' : 'Follow'; ?></button>
</form>
