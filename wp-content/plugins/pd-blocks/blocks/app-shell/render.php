<?php
/**
 * pd/app-shell: persistent header (BLOCKS.md 10.4). Never replaced by the router; only the small "pd-context" region inside it is.
 * @var array $attributes
 */
use PD\Blocks\Components;
use PD\Blocks\Menu;
use PD\Blocks\Route;
use PD\Blocks\State;

defined( 'ABSPATH' ) || exit;

$mode = 'calm' === ( $attributes['mode'] ?? 'full' ) ? 'calm' : 'full';
State::require( 'pd/navigation' );
State::require( 'pd/shell' );
State::require( 'pd/bag' );
wp_enqueue_style( 'pd-blocks' );

$nav = apply_filters(
	'pd_shell_nav',
	array(
		array( 'href' => home_url( '/artisans/' ), 'en' => 'Artisans', 'hi' => 'कारीगर' ),
		array( 'href' => function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'shop' ) : home_url( '/shop/' ), 'en' => 'Works', 'hi' => 'कृतियाँ' ),
	)
);
$ctx      = Route::context( $mode );
$cart_url = function_exists( 'wc_get_cart_url' ) ? wc_get_cart_url() : home_url( '/' );
$menu     = 'full' === $mode ? Menu::items() : array();
$groups   = array(
	'crafts' => array( 'Collections', 'संग्रह' ),
	'makers' => array( 'Makers and works', 'कारीगर और कृतियाँ' ),
);
?>
<div <?php echo get_block_wrapper_attributes( array( 'class' => 'pd-shell' ) ); ?>
	data-mode="<?php echo esc_attr( $mode ); ?>"
	data-wp-interactive="pd/shell"
	data-wp-class--is-hidden="state.shellHidden"
	data-wp-on--keydown="actions.onKeydown"
	data-wp-init--listen="callbacks.listen"
	data-wp-init--nav="pd/navigation::callbacks.boot"
	data-wp-init--bag="pd/bag::callbacks.listen"
	data-wp-watch--route="callbacks.resetOnRoute"
	data-wp-watch--bag="pd/bag::callbacks.reseed">
	<header class="pd-shell__bar" data-wp-class--is-menu-open="state.menuOpen">
		<a class="pd-brandmark" href="<?php echo esc_url( home_url( '/' ) ); ?>" data-accent="gulabi" aria-label="<?php esc_attr_e( 'Priyadarshini, home', 'pd-blocks' ); ?>">
			<?php echo Components::motif( 'fish' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
			<span class="pd-brandmark__words"><span class="pd-brandmark__en">Priyadarshini</span><span class="pd-brandmark__hi" lang="hi">प्रियदर्शिनी</span></span>
		</a>
		<nav class="pd-shell__nav" id="pd-shell-nav" aria-label="<?php esc_attr_e( 'Main', 'pd-blocks' ); ?>">
			<ul>
				<?php foreach ( $nav as $item ) : ?>
					<li><a href="<?php echo esc_url( $item['href'] ); ?>"
						data-wp-interactive="pd/navigation" <?php echo wp_interactivity_data_wp_context( array( 'href' => esc_url_raw( $item['href'] ) ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
						data-wp-bind--aria-current="state.ariaCurrent"
						data-wp-on--click="pd/shell::actions.onNavClick"><span><?php echo esc_html( $item['en'] ); ?></span><?php if ( ! empty( $item['hi'] ) ) : ?><span class="pd-hi" lang="hi"><?php echo esc_html( $item['hi'] ); ?></span><?php endif; ?></a></li>
				<?php endforeach; ?>
			</ul>
		</nav>
		<div class="pd-shell__tools">
			<a class="pd-iconbtn" id="pd-shell-search-btn" href="<?php echo esc_url( add_query_arg( 's', '', home_url( '/' ) ) ); ?>"
				aria-controls="pd-shell-ctx" aria-expanded="false"
				data-wp-bind--aria-expanded="state.searchOpen"
				data-wp-on--click="actions.openSearch"><?php esc_html_e( 'Search', 'pd-blocks' ); ?></a>
			<a class="pd-iconbtn pd-baglink" href="<?php echo esc_url( $cart_url ); ?>"
				data-wp-interactive="pd/bag"
				data-wp-bind--aria-label="state.countLabel"
				data-pd-vt="bag"><?php esc_html_e( 'Bag', 'pd-blocks' ); ?>&nbsp;<span class="pd-bagc" aria-hidden="true" data-wp-text="state.count" data-wp-class--is-bump="state.bump">0</span></a>
			<a class="pd-iconbtn pd-menubtn" id="pd-shell-menu-btn" href="#pd-menu" role="button" aria-controls="pd-menu" aria-expanded="false"
				data-wp-bind--aria-expanded="state.menuOpen"
				data-wp-on--click="actions.toggleMenu"><?php echo Components::motif( 'lotus', 'pd-motif pd-menubtn__mark' ); // phpcs:ignore WordPress.Security.EscapeOutput ?><span data-wp-text="state.menuLabel">Menu</span></a>
		</div>
		<div class="pd-progress" aria-hidden="true" data-wp-class--is-on="pd/navigation::state.progressVisible"></div>
	</header>
	<?php if ( 'full' === $mode && $menu ) : ?>
	<nav class="pd-menu" id="pd-menu" aria-label="<?php esc_attr_e( 'Crafts and makers', 'pd-blocks' ); ?>"
		data-wp-class--is-open="state.menuOpen">
		<div class="pd-menu__inner">
			<?php foreach ( $groups as $group => $label ) : ?>
				<?php $list = array_filter( $menu, static fn( $i ) => ( $i['group'] ?? 'crafts' ) === $group ); ?>
				<?php if ( ! $list ) { continue; } ?>
				<section class="pd-menu__group" aria-labelledby="pd-menu-<?php echo esc_attr( $group ); ?>">
					<h2 class="pd-menu__label" id="pd-menu-<?php echo esc_attr( $group ); ?>"><span><?php echo esc_html( $label[0] ); ?></span> <span lang="hi"><?php echo esc_html( $label[1] ); ?></span></h2>
					<ul class="pd-menu__grid" role="list">
						<?php foreach ( $list as $item ) : ?>
						<li data-pd-entity="menu" data-pd-entity-id="<?php echo esc_attr( $item['id'] ); ?>" data-accent="<?php echo esc_attr( Components::accent( (string) $item['accent'] ) ); ?>">
							<a class="pd-menucard" href="<?php echo esc_url( $item['href'] ); ?>" data-pd-from-menu data-pd-focus-id="menu-<?php echo esc_attr( $item['id'] ); ?>"
								data-wp-interactive="pd/navigation" <?php echo wp_interactivity_data_wp_context( array( 'href' => esc_url_raw( $item['href'] ) ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
								data-wp-bind--aria-current="state.ariaCurrent">
								<?php echo Components::illustration( $item, (string) $item['id'], false, false, 'pd-menucard__art' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
								<span class="pd-menucard__words">
									<span class="pd-menucard__title"><?php echo esc_html( $item['en'] ); ?></span>
									<?php if ( $item['hi'] ) : ?><span class="pd-menucard__title-hi" lang="hi"><?php echo esc_html( $item['hi'] ); ?></span><?php endif; ?>
									<?php if ( $item['teaser'] ) : ?><span class="pd-menucard__teaser"><?php echo esc_html( $item['teaser'] ); ?></span><?php endif; ?>
								</span>
							</a>
						</li>
						<?php endforeach; ?>
					</ul>
				</section>
			<?php endforeach; ?>
			<p class="pd-menu__close"><a class="pd-btn pd-btn--text" href="#" data-wp-on--click="actions.closeMenuAndReturn"><?php esc_html_e( 'Close menu', 'pd-blocks' ); ?></a></p>
		</div>
	</nav>
	<?php endif; ?>
	<div class="pd-shell__ctx" id="pd-shell-ctx" data-wp-class--is-search="state.searchOpen">
		<div class="pd-ctx" data-wp-interactive="pd/shell" data-wp-router-region="pd-context" data-wp-bind--hidden="state.searchOpen">
			<?php if ( $ctx['maker'] ) : ?>
				<a class="pd-ctx__maker" href="<?php echo esc_url( $ctx['maker']['url'] ); ?>"><?php echo Components::seal( $ctx['maker'], 'xs' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></a>
			<?php endif; ?>
			<span class="pd-ctx__text"><?php echo esc_html( $ctx['text'] ); ?></span>
			<?php if ( $ctx['hi'] ) : ?><span class="pd-ctx__hi" lang="hi"><?php echo esc_html( $ctx['hi'] ); ?></span><?php endif; ?>
		</div>
		<?php if ( 'full' === $mode ) : ?>
		<form class="pd-shell__search" role="search" method="get" action="<?php echo esc_url( home_url( '/' ) ); ?>" data-pd-route hidden data-wp-bind--hidden="!state.searchOpen">
			<label class="screen-reader-text" for="pd-shell-search-input"><?php esc_html_e( 'Search artisans and works', 'pd-blocks' ); ?></label>
			<input class="pd-input" id="pd-shell-search-input" type="search" name="s" placeholder="<?php esc_attr_e( 'Search artisans and works', 'pd-blocks' ); ?>" autocomplete="off">
			<button class="pd-btn pd-btn--small" type="submit"><?php esc_html_e( 'Search', 'pd-blocks' ); ?></button>
			<button class="pd-btn pd-btn--text" type="button" data-wp-on--click="actions.closeSearch"><?php esc_html_e( 'Cancel', 'pd-blocks' ); ?></button>
		</form>
		<?php endif; ?>
	</div>
	<div class="screen-reader-text" role="status" aria-live="polite" aria-atomic="true" data-wp-interactive="pd/navigation" data-wp-text="state.polite"></div>
	<div class="screen-reader-text" role="alert" aria-atomic="true" data-wp-interactive="pd/navigation" data-wp-text="state.assertive"></div>
	<?php if ( 'full' === $mode ) : ?>
	<div class="pd-splash" aria-hidden="true" data-wp-init="callbacks.splash">
		<svg class="pd-splash__motif" viewBox="0 0 90 60" data-accent="gulabi" aria-hidden="true" focusable="false">
			<g class="pd-splash__fill"><path d="M6 30C20 8 46 8 62 30C46 52 20 52 6 30Z" fill="var(--accent)"/><path d="M62 30L84 12L78 30L84 48Z" fill="var(--card)"/></g>
			<path class="pd-splash__line" d="M6 30C20 8 46 8 62 30C46 52 20 52 6 30Z"/><path class="pd-splash__line" d="M62 30L84 12L78 30L84 48Z"/>
			<path class="pd-splash__line" d="M30 20q6 10 0 20M40 17q6 13 0 26M50 20q6 10 0 20"/><circle class="pd-splash__line" cx="19" cy="27" r="3.4"/>
		</svg>
		<button class="pd-btn pd-btn--text pd-splash__skip" type="button" tabindex="-1"><?php esc_html_e( 'Skip', 'pd-blocks' ); ?></button>
	</div>
	<?php endif; ?>
</div>
