<?php
namespace PD;

use PD\Artisan\Follows;

defined( 'ABSPATH' ) || exit;

/** Public REST surface owned by this plugin (SRS 3.5.5). Every write needs a signed-in user and the REST nonce. */
final class Rest {

	public static function register_hooks(): void {
		add_action( 'rest_api_init', array( self::class, 'routes' ) );
		add_action( 'admin_post_pd_follow', array( self::class, 'follow_form' ) );      // no-JS fallback
		add_action( 'admin_post_nopriv_pd_follow', array( self::class, 'follow_form' ) );
	}

	public static function routes(): void {
		register_rest_route( 'pd/v1', '/artisans/(?P<id>\d+)/follow', array(
			array(
				'methods'             => 'POST',
				'permission_callback' => static fn() => is_user_logged_in(),
				'args'                => array( 'follow' => array( 'type' => 'boolean', 'required' => true ) ),
				'callback'            => static function ( \WP_REST_Request $r ) {
					$res = Follows::set( get_current_user_id(), (int) $r['id'], (bool) $r['follow'] );
					return is_wp_error( $res ) ? $res : rest_ensure_response( $res );
				},
			),
		) );
	}

	/** Form post: sign-in first, then toggle, then back to where the buyer was. */
	public static function follow_form(): void {
		$artisan = (int) ( $_POST['artisan'] ?? 0 );
		$back    = wp_get_referer() ?: home_url( '/' );
		if ( ! is_user_logged_in() ) {
			wp_safe_redirect( wp_login_url( $back ) );
			exit;
		}
		check_admin_referer( 'pd_follow_' . $artisan );
		Follows::set( get_current_user_id(), $artisan, ! empty( $_POST['follow'] ) );
		wp_safe_redirect( $back );
		exit;
	}
}
