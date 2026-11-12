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

	public static function boot(): void {
		add_action( 'init', static function () {
			wp_register_script_module(
				'pd/artisan-store',
				plugins_url( 'assets/artisan-store.js', DIR . '/pd-blocks.php' ),
				array( array( 'id' => '@wordpress/interactivity', 'import' => 'dynamic' ) ),
				VERSION
			);
		} );
	}

	/** Seed (once) and make sure the owning store's module loads. @param array<string,mixed> $args */
	public static function require( string $ns, array $args = array() ): void {
		switch ( $ns ) {
			case 'pd/artisan':
				self::artisan( array_map( 'intval', (array) ( $args['ids'] ?? array() ) ) );
				break;
			default:
				_doing_it_wrong( __METHOD__, esc_html( "Unknown store $ns" ), VERSION );
		}
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
