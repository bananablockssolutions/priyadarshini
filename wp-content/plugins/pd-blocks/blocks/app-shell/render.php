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
$menu     = 'full' === $mode ? Menu::crafts() : array();
$shop     = function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'shop' ) : home_url( '/shop/' );
$account  = function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'myaccount' ) : wp_login_url();
$artisans = (string) ( get_post_type_archive_link( 'pd_artisan' ) ?: home_url( '/artisans/' ) );
$cat_base = '/' . trim( (string) ( get_option( 'woocommerce_permalinks' )['category_base'] ?? '' ) ?: 'product-category', '/' ) . '/';
$svg      = static fn( string $d ): string => '<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">' . $d . '</svg>';
// Bottom bar (mobile): Home, Explore, Artists, Profile, as in the approved prototype. "match" marks the current tab.
$tabs = array(
	array( 'Home', home_url( '/' ), array( home_url( '/' ) ), $svg( '<path d="M3 11 12 3l9 8"/><path d="M5 9.5V21h5v-6h4v6h5V9.5"/>' ) ),
	array( 'Explore', $shop, array( $shop, home_url( $cat_base ), home_url( '/product/' ) ), $svg( '<circle cx="7" cy="7" r="3.5"/><circle cx="17" cy="7" r="3.5"/><circle cx="7" cy="17" r="3.5"/><circle cx="17" cy="17" r="3.5"/>' ) ),
	array( 'Artists', $artisans, array( $artisans, home_url( '/a/' ) ), $svg( '<path d="M20 3 10 13"/><path d="M10 13c-3 0-5 2-5 4s-1 3-2 4c4 0 9-1 9-5 0-1.2-.8-2.2-2-3Z" fill="currentColor"/>' ) ),
	array( 'Profile', $account, array( $account ), $svg( '<circle cx="12" cy="8" r="4"/><path d="M4 21c1-4.5 4-6.5 8-6.5s7 2 8 6.5"/>' ) ),
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
			<svg class="pd-motif pd-brandmark__logo" viewBox="0 0 40 40" aria-hidden="true" focusable="false"><circle cx="20" cy="20" r="17" fill="none" stroke="#2A1E17" stroke-width="2"/><path d="M9 21 Q20 9 31 21 Q20 31 9 21Z" fill="#C8321F" stroke="#2A1E17" stroke-width="1.6"/><path d="M9 21 L4 16 M9 21 L4 26" stroke="#2A1E17" stroke-width="1.6" stroke-linecap="round"/><circle cx="25" cy="19" r="1.8" fill="#2A1E17"/><path d="M14 18 q2 3 0 6 M18 16 q2 5 0 10" fill="none" stroke="#E9A30C" stroke-width="1.6"/></svg>
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
				data-pd-vt="bag"><svg class="pd-baglink__icon" viewBox="0 0 24 24" aria-hidden="true" focusable="false" fill="none" stroke="currentColor" stroke-width="2" stroke-linejoin="round"><path d="M5 8h14l-1.2 12.5H6.2Z"/><path d="M9 8V6a3 3 0 0 1 6 0v2"/></svg><span class="pd-baglink__word"><?php esc_html_e( 'Bag', 'pd-blocks' ); ?>&nbsp;</span><span class="pd-bagc" aria-hidden="true" data-wp-text="state.count" data-wp-class--is-bump="state.bump">0</span></a>
			<a class="pd-burger" id="pd-shell-menu-btn" href="#pd-menu" role="button" aria-controls="pd-menu" aria-expanded="false" aria-label="<?php esc_attr_e( 'Open menu', 'pd-blocks' ); ?>"
				data-wp-bind--aria-expanded="state.menuOpen"
				data-wp-bind--aria-label="state.menuLabel"
				data-wp-on--click="actions.toggleMenu"><span class="pd-burger__l"></span><span class="pd-burger__l"></span><span class="pd-burger__l"></span></a>
		</div>
		<div class="pd-progress" aria-hidden="true" data-wp-class--is-on="pd/navigation::state.progressVisible"></div>
	</header>
	<?php if ( 'full' === $mode && $menu ) : ?>
	<nav class="pd-menu" id="pd-menu" aria-label="<?php esc_attr_e( 'Shop by type', 'pd-blocks' ); ?>" data-wp-class--is-open="state.menuOpen">
		<span class="pd-menu__bg" aria-hidden="true"></span><span class="pd-menu__side is-l" aria-hidden="true"></span><span class="pd-menu__side is-r" aria-hidden="true"></span>
		<ul class="pd-menu__items" role="list">
			<?php foreach ( $menu as $item ) : ?>
			<li data-accent="<?php echo esc_attr( Components::accent( (string) $item['accent'] ) ); ?>">
				<a class="pd-mrow" href="<?php echo esc_url( $item['href'] ); ?>" data-pd-from-menu data-pd-row="<?php echo esc_attr( $item['id'] ); ?>" data-pd-focus-id="menu-<?php echo esc_attr( $item['id'] ); ?>"
					data-wp-interactive="pd/navigation" <?php echo wp_interactivity_data_wp_context( array( 'href' => esc_url_raw( $item['href'] ) ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
					data-wp-bind--aria-current="state.ariaCurrent">
					<span class="pd-mrow__ill" data-pd-fly="ill"><?php echo Components::drawing( $item ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span>
					<span class="pd-mrow__words">
						<span class="pd-mrow__ttl" data-pd-fly="ttl"><?php echo esc_html( $item['en'] ); ?><?php if ( $item['hi'] ) : ?><span class="pd-hi" lang="hi"><?php echo esc_html( $item['hi'] ); ?></span><?php endif; ?></span>
						<?php if ( $item['teaser'] ) : ?><span class="pd-mrow__dsc" data-pd-fly="dsc"><?php echo esc_html( $item['teaser'] ); ?></span><?php endif; ?>
					</span>
				</a>
			</li>
			<?php endforeach; ?>
		</ul>
		<a class="pd-menu__nojs-close" href="#"><?php esc_html_e( 'Close menu', 'pd-blocks' ); ?></a>
	</nav>
	<span class="pd-dropline" aria-hidden="true"></span>
	<?php endif; ?>
	<?php if ( 'full' === $mode ) : ?>
	<nav class="pd-tabs" aria-label="<?php esc_attr_e( 'App', 'pd-blocks' ); ?>">
		<?php foreach ( $tabs as [ $label, $href, $match, $icon ] ) : ?>
		<a href="<?php echo esc_url( $href ); ?>" data-wp-interactive="pd/navigation" <?php echo wp_interactivity_data_wp_context( array( 'match' => $match ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
			data-wp-bind--aria-current="state.tabCurrent"><?php echo $icon; // phpcs:ignore WordPress.Security.EscapeOutput ?><span><?php echo esc_html( $label ); ?></span></a>
		<?php endforeach; ?>
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
