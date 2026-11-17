<?php
/**
 * Priyadarshini block theme. Tokens live in theme.json; behaviour lives in pd-marketplace and pd-blocks.
 */
defined( 'ABSPATH' ) || exit;

const PD_THEME_VERSION = '0.2.0';

add_action(
	'after_setup_theme',
	static function () {
		add_theme_support( 'responsive-embeds' );
		add_theme_support( 'wp-block-styles' );
		add_theme_support( 'woocommerce' );
		add_editor_style( 'assets/css/theme.css' );
	}
);

add_action(
	'wp_enqueue_scripts',
	static function () {
		wp_enqueue_style( 'pd-theme', get_theme_file_uri( 'assets/css/theme.css' ), array(), PD_THEME_VERSION );
	}
);

// Style variations from BLOCKS.md section 5.
add_action(
	'init',
	static function () {
		register_block_style( 'core/group', array( 'name' => 'double-rule', 'label' => __( 'Double rule', 'pd-theme' ) ) );
		register_block_style( 'core/group', array( 'name' => 'ledger', 'label' => __( 'Ledger', 'pd-theme' ) ) );
	}
);

// Skip link, first thing in the page. The target is the routed main (#pd-main) printed by pd/routed-main.
add_action(
	'wp_body_open',
	static function () {
		echo '<a class="pd-skip" href="#pd-main">' . esc_html__( 'Skip to content', 'pd-theme' ) . '</a>';
	}
);
