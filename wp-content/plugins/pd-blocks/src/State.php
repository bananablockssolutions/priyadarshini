<?php
namespace PD\Blocks;

use PD\Artisan\Follows;
use PD\Artisan\Table;

defined( 'ABSPATH' ) || exit;

/**
 * The only place that seeds Interactivity state (BLOCKS.md 9.1).
 * Blocks call State::require( 'pd/artisan', [ 'ids' => [ 184 ] ] ); they never call wp_interactivity_state() themselves.
 * Seeding is idempotent per request: a key is seeded once, whichever block asks first.
 */
final class State {

	/** Whole words that must never be part of a shared-state key (SEC-10). Matched on words, so "panel" is fine and "pan" is not. */
	private const FORBIDDEN_WORDS = array( 'address', 'phone', 'email', 'mobile', 'note', 'notes', 'payout', 'bank', 'upi', 'pan', 'gstin', 'aadhaar', 'verification', 'verifier', 'password', 'secret', 'userid', 'verifiedat' );

	/** @var array<string,array<string,bool>> namespace => seeded sub-keys */
	private static array $seeded = array();
	private static array $log    = array();

	/** Store modules: id => [file, dependencies]. Each is the single owner of its keys (BLOCKS.md 9.1). */
	private const MODULES = array(
		'pd/artisan-store'    => array( 'artisan-store.js', array() ),
		'pd/navigation-store' => array( 'navigation-store.js', array( array( 'id' => '@wordpress/interactivity-router', 'import' => 'dynamic' ) ) ),
		'pd/shell-store'      => array( 'shell-store.js', array() ),
		'pd/bag-store'        => array( 'bag-store.js', array() ),
		'pd/catalogue-store'  => array( 'catalogue-store.js', array() ),
	);

	public static function boot(): void {
		add_action( 'init', static function () {
			foreach ( self::MODULES as $id => [ $file, $deps ] ) {
				wp_register_script_module(
					$id,
					plugins_url( 'assets/' . $file, DIR . '/pd-blocks.php' ),
					array_merge( array( '@wordpress/interactivity' ), $deps ),
					VERSION
				);
				// WordPress 6.9+: lets the Interactivity Router load this module when a routed page first needs it.
				if ( method_exists( wp_interactivity(), 'add_client_navigation_support_to_script_module' ) ) {
					wp_interactivity()->add_client_navigation_support_to_script_module( $id );
				}
			}
		} );
	}

	/** Seed (once) and make sure the owning store's module loads. @param array<string,mixed> $args */
	public static function require( string $ns, array $args = array() ): void {
		switch ( $ns ) {
			case 'pd/artisan':
				self::artisan( array_map( 'intval', (array) ( $args['ids'] ?? array() ) ) );
				break;
			case 'pd/navigation':
				self::once( $ns, 'pd/navigation-store', array( self::class, 'navigation' ) );
				break;
			case 'pd/shell':
				self::once( $ns, 'pd/shell-store', array( self::class, 'shell' ) );
				break;
			case 'pd/catalogue':
				self::once( $ns, 'pd/catalogue-store', static fn() => array( 'sheetOpen' => false ) );
				break;
			case 'pd/bag':
				self::once( $ns, 'pd/bag-store', array( self::class, 'bag' ) );
				break;
			default:
				_doing_it_wrong( __METHOD__, esc_html( "Unknown store $ns" ), VERSION );
		}
	}

	private static function once( string $ns, string $module, callable $seed ): void {
		wp_enqueue_script_module( $module );
		if ( empty( self::$seeded[ $ns ]['_base'] ) ) {
			self::$seeded[ $ns ]['_base'] = true;
			self::seed( $ns, $seed() );
		}
	}

	/** pd/navigation: route lifecycle, focus, scroll and announcements. url is reseeded on every route. */
	private static function navigation(): array {
		$url      = home_url( add_query_arg( array() ) );
		$excluded = array( wp_parse_url( admin_url(), PHP_URL_PATH ), wp_parse_url( wp_login_url(), PHP_URL_PATH ), '/wp-json/', '/feed/' );
		if ( function_exists( 'wc_get_page_permalink' ) ) {
			foreach ( array( 'cart', 'checkout', 'myaccount' ) as $page ) {
				$path = wp_parse_url( wc_get_page_permalink( $page ), PHP_URL_PATH );
				if ( $path && '/' !== $path ) {
					$excluded[] = $path;
				}
			}
		}
		return array(
			'url'            => $url,
			'excluded'       => array_values( array_unique( array_filter( $excluded ) ) ),
			'progressVisible' => false,
			'polite'         => '',
			'assertive'      => '',
			// Derived on the server too, so the first paint already marks the current link (see tests/blocks1.php lesson).
			'isCurrent'      => static function () use ( $url ): bool {
				$c = wp_interactivity_get_context();
				return self::same_section( (string) ( $c['href'] ?? '' ), $url );
			},
			'ariaCurrent'    => static function () use ( $url ): ?string {
				$c = wp_interactivity_get_context();
				return self::same_section( (string) ( $c['href'] ?? '' ), $url ) ? 'page' : null;
			},
			// Bottom bar tab: current when the page is in any of the tab's sections.
			'tabCurrent'     => static function () use ( $url ): ?string {
				$c = wp_interactivity_get_context();
				foreach ( (array) ( $c['match'] ?? array() ) as $href ) {
					if ( self::same_section( (string) $href, $url ) ) {
						return 'page';
					}
				}
				return null;
			},
		);
	}

	/** A nav link is current when the page is that section or inside it ("/artisans/" is current on "/artisans/?page=2"). */
	public static function same_section( string $href, string $url ): bool {
		$a = (string) wp_parse_url( $href, PHP_URL_PATH );
		$b = (string) wp_parse_url( $url, PHP_URL_PATH );
		if ( '' === $a || '/' === $a ) {
			return '/' === $b || '' === $b;
		}
		return 0 === strpos( trailingslashit( $b ), trailingslashit( $a ) );
	}

	/** pd/shell: menu, search and shell presentation. UI keys reset on navigation. */
	private static function shell(): array {
		return array(
			'menuOpen'   => false,
			'searchOpen' => false,
			'hidden'     => false,
			'signedIn'   => is_user_logged_in(),
			'menuLabel'  => static function (): string {
				return ! empty( wp_interactivity_state( 'pd/shell' )['menuOpen'] ) ? 'Close menu' : 'Open menu';
			},
		);
	}

	/** pd/bag: a thin mirror of the WooCommerce cart. Count is reseeded from the server on every route. */
	private static function bag(): array {
		$count = 0;
		if ( function_exists( 'WC' ) && WC()->cart ) {
			$count = (int) WC()->cart->get_cart_contents_count();
		}
		return array(
			'count'      => $count,
			'cartUrl'    => function_exists( 'wc_get_cart_url' ) ? wc_get_cart_url() : home_url( '/' ),
			'storeApi'   => esc_url_raw( rest_url( 'wc/store/v1/' ) ),
			'_nonce'     => wp_create_nonce( 'wc_store_api' ),
			'pending'    => false,
			'bump'       => false,
			'countLabel' => static function (): string {
				$n = (int) ( wp_interactivity_state( 'pd/bag' )['count'] ?? 0 );
				return 1 === $n ? 'Bag, 1 work' : "Bag, {$n} works";
			},
		);
	}

	/** Test hook: forget what was seeded in this request. */
	public static function reset(): void {
		self::$seeded = array();
		self::$log    = array();
	}

	/** What was seeded, for tests: list of [namespace, data]. */
	public static function log(): array {
		return self::$log;
	}

	private static function artisan( array $ids ): void {
		$ns = 'pd/artisan';
		wp_enqueue_script_module( 'pd/artisan-store' );
		if ( empty( self::$seeded[ $ns ]['_base'] ) ) {
			self::seed( $ns, array(
				'signedIn' => is_user_logged_in(),
				'loginUrl' => wp_login_url( home_url( add_query_arg( array() ) ) ),
				'restUrl'  => esc_url_raw( rest_url( 'pd/v1/artisans/' ) ),
				'_nonce'   => is_user_logged_in() ? wp_create_nonce( 'wp_rest' ) : '',
				'followed' => (object) array(),
				'followerCount' => (object) array(),
			) + self::artisan_derived() );
			self::$seeded[ $ns ]['_base'] = true;
		}
		$new = array_values( array_filter( array_unique( $ids ), static fn( $id ) => $id > 0 && empty( self::$seeded[ $ns ][ $id ] ) ) );
		if ( ! $new ) {
			return;
		}
		$followed = Follows::followed_map( get_current_user_id(), $new );
		$followed_out = $count_out = array();
		foreach ( $new as $id ) {
			$row = Table::public_view( $id );
			if ( ! $row ) {
				continue;
			}
			$followed_out[ $id ] = $followed[ $id ];
			$count_out[ $id ]    = (int) $row['follower_count'];
			self::$seeded[ $ns ][ $id ] = true;
		}
		if ( $followed_out ) {
			self::seed( $ns, array( 'followed' => $followed_out, 'followerCount' => $count_out ) );
		}
	}

	/**
	 * Derived values, defined here for the server render and again in assets/artisan-store.js for the browser.
	 * Without these the server would blank every data-wp-text / data-wp-bind that reads them (found by tests/blocks1.php).
	 */
	private static function artisan_derived(): array {
		$id = static function (): int {
			$c = wp_interactivity_get_context();
			return (int) ( $c['artisanPostId'] ?? 0 );
		};
		$followers = static function () use ( $id ): int {
			$s = wp_interactivity_state();
			return (int) ( $s['followerCount'][ $id() ] ?? 0 );
		};
		return array(
			'isFollowing'    => static function () use ( $id ): bool {
				$s = wp_interactivity_state();
				return ! empty( $s['followed'][ $id() ] );
			},
			'followers'      => $followers,
			'followersLabel' => static fn(): string => 1 === $followers() ? '1 follower' : $followers() . ' followers',
			'followLabel'    => static function () use ( $id ): string {
				$s = wp_interactivity_state();
				return ! empty( $s['followed'][ $id() ] ) ? 'Following' : 'Follow';
			},
		);
	}

	private static function seed( string $ns, array $data ): void {
		self::assert_public( $data );
		self::$log[] = array( $ns, $data );
		wp_interactivity_state( $ns, $data );
	}

	/** Refuse private-looking keys. Underscore keys are private to their own store and are allowed only for the REST nonce. */
	private static function assert_public( array $data, string $path = '' ): void {
		foreach ( $data as $k => $v ) {
			if ( is_string( $k ) && '_nonce' !== $k && self::is_private_key( $k ) ) {
				_doing_it_wrong( __METHOD__, esc_html( "Private-looking key {$path}{$k} refused in shared state (SEC-10)." ), VERSION );
				throw new \LogicException( "Private key in shared state: {$path}{$k}" );
			}
			if ( is_array( $v ) ) {
				self::assert_public( $v, "{$path}{$k}." );
			}
		}
	}

	public static function is_private_key( string $key ): bool {
		$words = preg_split( '/[^a-z0-9]+/', strtolower( preg_replace( '/(?<=[a-z0-9])(?=[A-Z])/', '_', $key ) ), -1, PREG_SPLIT_NO_EMPTY );
		if ( array_intersect( $words, self::FORBIDDEN_WORDS ) ) {
			return true;
		}
		return in_array( implode( '', array_slice( $words, 0, 2 ) ), array( 'userid', 'verifiedat' ), true );
	}
}
