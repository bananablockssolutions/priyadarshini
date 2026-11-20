<?php
namespace PD\Blocks;

defined( 'ABSPATH' ) || exit;

final class Plugin {

	/** Registered blocks (BLOCKS.md section 4). Grows as blocks are built. */
	private const BLOCKS = array( 'follow-button', 'artisan-card', 'app-shell', 'routed-main', 'work-card', 'work-gallery', 'buy-box', 'cover', 'craft-makers', 'shop-toolbar', 'filters-rail', 'work-page', 'cart', 'artisan-list', 'artisan-page' );

	public static function boot(): void {
		if ( ! class_exists( '\PD\Plugin' ) ) {
			add_action( 'admin_notices', static function () {
				echo '<div class="notice notice-error"><p>Priyadarshini Blocks needs the Priyadarshini Marketplace plugin to be active.</p></div>';
			} );
			return;
		}
		add_action( 'init', array( self::class, 'register' ) );
		add_action( 'wp_enqueue_scripts', array( self::class, 'assets' ) );
		add_action( 'wp_head', array( self::class, 'splash_gate' ), 1 );
		add_action( 'wp_footer', array( self::class, 'motif_sprite' ), 1 );
		add_filter( 'query_loop_block_query_vars', array( self::class, 'shelf_query' ), 10, 2 );
		add_action( 'pre_get_posts', array( self::class, 'catalogue_filters' ) );
		State::boot();
	}

	public static function register(): void {
		wp_register_script(
			'pd-blocks-editor',
			plugins_url( 'assets/editor.js', DIR . '/pd-blocks.php' ),
			array( 'wp-blocks', 'wp-element', 'wp-block-editor', 'wp-server-side-render', 'wp-components' ),
			VERSION,
			true
		);
		foreach ( self::BLOCKS as $name ) {
			register_block_type( DIR . '/blocks/' . $name );
		}
	}

	/**
	 * One stylesheet for every pd block, loaded on every page, so a page reached by client navigation never misses styles.
	 * It is small; per-block splitting can come later if it grows.
	 */
	public static function assets(): void {
		wp_register_style( 'pd-blocks', plugins_url( 'assets/blocks.css', DIR . '/pd-blocks.php' ), array(), VERSION );
		wp_enqueue_style( 'pd-blocks' );
	}

	/**
	 * Splash gate (UI-08): decided before first paint, once per tab session, never under reduced motion.
	 * Add ?pd-nosplash to a URL to skip it (tests, screenshots).
	 */
	public static function splash_gate(): void {
		if ( is_admin() || ! wp_is_block_theme() ) {
			return;
		}
		echo "<script>try{var d=document.documentElement;if(!sessionStorage.getItem('pd.splashSeen')&&!matchMedia('(prefers-reduced-motion: reduce)').matches&&!/[?&]pd-nosplash/.test(location.search)){d.classList.add('pd-splash-on');}sessionStorage.setItem('pd.splashSeen','1');}catch(e){}</script>\n";
	}

	/** Placeholder Madhubani motifs, printed once outside the routed region so every page can <use> them. */
	public static function motif_sprite(): void {
		if ( is_admin() ) {
			return;
		}
		$svg = file_get_contents( DIR . '/assets/motifs.svg' ); // phpcs:ignore WordPress.WP.AlternativeFunctions
		echo $svg ? $svg : ''; // phpcs:ignore WordPress.Security.EscapeOutput -- our own static file
	}

	/**
	 * Works shelf of one artisan: a core/query over products with "pdShelf":"current-artisan" in its query attribute
	 * lists only the works of the artisan whose page this is (BLOCKS.md variation pd-artisan-shelf, first cut).
	 */
	public static function shelf_query( array $query, $block ): array {
		$q = $block instanceof \WP_Block ? (array) ( $block->context['query'] ?? array() ) : array();
		if ( 'current-artisan' === ( $q['pdShelf'] ?? '' ) ) {
			$artisan = is_singular( 'pd_artisan' ) ? (int) get_queried_object_id() : 0;
			$query['meta_query']   = isset( $query['meta_query'] ) ? (array) $query['meta_query'] : array();
			$query['meta_query'][] = array( 'key' => '_pd_artisan_post_id', 'value' => $artisan, 'type' => 'NUMERIC' );
			$query['meta_query']   = array_merge( $query['meta_query'], self::filter_meta() ); // the artisan page's Filters rail
		}
		return $query;
	}

	/** Filters from pd/filters-rail on product archives: ready to ship, made on order, under a price. */
	public static function catalogue_filters( \WP_Query $q ): void {
		if ( is_admin() || ! $q->is_main_query() || ! ( $q->is_tax( 'product_cat' ) || $q->is_post_type_archive( 'product' ) ) ) {
			return;
		}
		$meta = array_merge( (array) $q->get( 'meta_query' ), self::filter_meta() );
		if ( $meta ) {
			$q->set( 'meta_query', $meta );
		}
	}

	/** Meta query clauses for the Filters sheet (pd_avail[], pd_under) in the current address. */
	public static function filter_meta(): array {
		$avail = isset( $_GET['pd_avail'] ) ? array_intersect( (array) wp_unslash( $_GET['pd_avail'] ), array( 'ready', 'order' ) ) : array(); // phpcs:ignore WordPress.Security.NonceVerification,WordPress.Security.ValidatedSanitizedInput
		$under = isset( $_GET['pd_under'] ) ? absint( $_GET['pd_under'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification
		$meta  = array();
		if ( 1 === count( $avail ) ) {
			$meta[] = 'order' === reset( $avail )
				? array( 'key' => '_pd_made_on_order', 'value' => '1' )
				: array( 'relation' => 'OR', array( 'key' => '_pd_made_on_order', 'compare' => 'NOT EXISTS' ), array( 'key' => '_pd_made_on_order', 'value' => '1', 'compare' => '!=' ) );
		}
		if ( $under ) {
			$meta[] = array( 'key' => '_price', 'value' => $under, 'compare' => '<', 'type' => 'NUMERIC' );
		}
		return $meta;
	}
}
