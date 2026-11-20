<?php
/**
 * pd/work-card: one work. Wide screens: a museum label (design system primitive 4), the plate grows into the work page's plate.
 * Phones (approved prototype, sketch 3): square picture with badge, name in two lines, colour dots and sizes, price and Buy.
 * Its picture, name, choices and Buy (data-pd-fly) travel into the same parts of the work page (pd/work-page).
 * @var array $attributes @var string $content @var WP_Block $block
 */
use PD\Blocks\Components;
use PD\Works\View;

defined( 'ABSPATH' ) || exit;

$id   = (int) ( $block->context['postId'] ?? 0 );
$work = $id && 'product' === get_post_type( $id ) ? View::get( $id ) : null;
if ( ! $work ) {
	return '';
}
wp_enqueue_style( 'pd-blocks' );
\PD\Blocks\State::require( 'pd/bag' );
\PD\Blocks\State::require( 'pd/catalogue' );

$maker      = $work['artisan'];
$show_maker = ! empty( $attributes['showMaker'] ) && $maker;
$vt         = 'work-' . $id;
$focus      = sanitize_key( $attributes['focusContext'] ?? 'grid' );
$label      = $work['title'] . ( $maker ? ', by ' . $maker['name'] : '' );
$picked     = \PD\Works\Variants::picked( $work['options'], array() );
?>
<article <?php echo get_block_wrapper_attributes( array( 'class' => 'pd-work-card' ) ); ?>
	data-pd-entity="work" data-pd-entity-id="<?php echo esc_attr( $vt ); ?>" data-pd-shared="<?php echo esc_attr( $vt ); ?>"
	<?php echo $maker ? 'data-accent="' . esc_attr( Components::accent( $maker['accent'] ) ) . '"' : ''; ?>>
	<a class="pd-work-card__link" href="<?php echo esc_url( $work['url'] ); ?>" aria-label="<?php echo esc_attr( $label ); ?>"
		data-pd-focus-id="<?php echo esc_attr( $vt . '-link-' . $focus ); ?>">
		<?php echo Components::plate( (int) $work['imageId'], '', '4-5', $vt, $maker['motif'] ?? 'fish' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
		<?php echo Components::work_pic( $work, 'pd-work-card__pic' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
		<span class="pd-work-card__label">
			<?php if ( $work['number'] ) : ?><span class="pd-catno"><?php echo esc_html( Components::catalogue_line( (int) $work['number'], null ) ); ?></span><?php endif; ?>
			<span class="pd-work-card__title" data-pd-fly="name"><?php echo esc_html( $work['title'] ); ?></span>
			<?php if ( $work['titleHi'] ) : ?><span class="pd-work-card__title-hi" lang="hi"><?php echo esc_html( $work['titleHi'] ); ?></span><?php endif; ?>
		</span>
	</a>
	<?php if ( $show_maker ) : ?>
		<p class="pd-maker-line" data-pd-entity="artisan" data-pd-entity-id="artisan-<?php echo (int) $maker['id']; ?>">
			<?php echo Components::seal( $maker, 'xs', 'artisan-' . (int) $maker['id'] ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
			<a href="<?php echo esc_url( $maker['url'] ); ?>" data-pd-focus-id="<?php echo esc_attr( 'artisan-' . (int) $maker['id'] . '-from-' . $vt ); ?>"><?php echo esc_html( $maker['name'] ); ?></a>
		</p>
	<?php endif; ?>
	<p class="pd-work-card__foot">
		<span class="pd-price"><?php echo wp_kses_post( $work['priceHtml'] ); ?></span>
		<span class="pd-format"><?php echo esc_html( $work['formatLabel'] ); ?></span>
	</p>
	<p class="pd-work-card__meta">
		<?php echo Components::rating( (float) $work['ratingAvg'], (int) $work['ratingCount'] ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
		<span class="pd-avail"><?php echo esc_html( $work['availability'] ); ?></span>
	</p>
	<?php echo Components::pick_form( $work, $picked, 'card' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
</article>
