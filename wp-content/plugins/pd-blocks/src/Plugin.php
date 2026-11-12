<?php
namespace PD\Blocks;

defined( 'ABSPATH' ) || exit;

final class Plugin {

	/** Registered blocks (BLOCKS.md section 3). Grows as blocks are built. */
	private const BLOCKS = array( 'follow-button', 'artisan-card' );

	public static function boot(): void {
		if ( ! class_exists( '\PD\Plugin' ) ) {
			add_action( 'admin_notices', static function () {
				echo '<div class="notice notice-error"><p>Priyadarshini Blocks needs the Priyadarshini Marketplace plugin to be active.</p></div>';
			} );
			return;
		}
		add_action( 'init', array( self::class, 'register' ) );
		add_action( 'wp_enqueue_scripts', static function () {
			wp_register_style( 'pd-blocks', plugins_url( 'assets/blocks.css', DIR . '/pd-blocks.php' ), array(), VERSION );
		} );
		State::boot();
	}

	public static function register(): void {
		foreach ( self::BLOCKS as $name ) {
			register_block_type( DIR . '/blocks/' . $name );
		}
	}
}
