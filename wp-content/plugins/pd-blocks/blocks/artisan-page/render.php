<?php
/**
 * pd/artisan-page: the artisan page on a phone (owner sketch 5, approved prototype). The page's article carries
 * data-pd-hero, so the Artists card grows into it and Back shrinks it into the card again. Its works follow it in the
 * template (toolbar, Filters rail and the grid), "like a product category". data-pd-back-label lets a work opened from
 * here say "‹ Meera" on its back link.
 * @var array $attributes @var string $content @var WP_Block $block
 */
use PD\Artisan\Profile;
use PD\Blocks\Artisans;
use PD\Blocks\Components;

defined( 'ABSPATH' ) || exit;

$id = (int) ( $block->context['postId'] ?? get_queried_object_id() );
$a  = $id && class_exists( Profile::class ) ? Profile::get( $id ) : null;
if ( ! $a ) {
	return '';
}
wp_enqueue_style( 'pd-blocks' );
$list   = (string) ( get_post_type_archive_link( 'pd_artisan' ) ?: home_url( '/artisans/' ) );
$bio    = trim( (string) get_post_field( 'post_content', $id ) );
$bio    = '' !== $bio ? do_blocks( wp_kses_post( $bio ) ) : '';
$follow = render_block( array( 'blockName' => 'pd/follow-button', 'attrs' => array( 'artisanPostId' => $id ), 'innerBlocks' => array(), 'innerHTML' => '', 'innerContent' => array() ) );
?>
<article <?php echo get_block_wrapper_attributes( array( 'class' => 'pd-apage' ) ); ?> data-pd-shared="artisan-<?php echo (int) $id; ?>" data-pd-hero
	data-pd-back-label="<?php echo esc_attr( $a['first'] ); ?>" data-accent="<?php echo esc_attr( Components::accent( $a['accent'] ) ); ?>">
	<a class="pd-apage__back" href="<?php echo esc_url( $list ); ?>" data-pd-back data-pd-reveal><span aria-hidden="true">‹ </span><?php esc_html_e( 'Artists', 'pd-blocks' ); ?></a>
	<div class="pd-apage__hero">
		<div class="pd-apage__frame">
			<span class="pd-apage__mat" data-pd-reveal aria-hidden="true"></span>
			<?php echo Artisans::pic( $a, true ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
			<?php echo Artisans::ribbon( $a ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
		</div>
		<?php echo Artisans::name( $a, 'h1', 'pd-aname pd-apage__name' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
		<div class="pd-apage__act" data-pd-reveal>
			<?php echo $follow; // phpcs:ignore WordPress.Security.EscapeOutput -- block output ?>
			<a class="pd-apage__comm" href="#commission"><?php esc_html_e( 'Commission a work', 'pd-blocks' ); ?></a>
		</div>
		<?php echo Artisans::marks( $a ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
	</div>
	<div class="pd-apage__bio">
		<?php echo Artisans::line( $a, 'pd-aline pd-apage__line' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
		<?php if ( $bio ) : ?><div class="pd-apage__text" data-pd-reveal><?php echo $bio; // phpcs:ignore WordPress.Security.EscapeOutput -- kses above ?></div><?php endif; ?>
		<?php if ( $a['quote'] ) : ?><p class="pd-apage__quote" data-pd-reveal>“<?php echo esc_html( $a['quote'] ); ?>”</p><?php endif; ?>
		<?php if ( $a['village'] ) : ?><p class="pd-apage__place" data-pd-reveal><?php echo esc_html( $a['village'] ); ?></p><?php endif; ?>
	</div>
	<span id="commission" class="pd-apage__anchor"></span>
</article>
