<?php
/**
 * Priyadarshini block theme. Tokens live in theme.json; behaviour lives in pd-marketplace and pd-blocks.
 */
defined( 'ABSPATH' ) || exit;

add_action(
	'after_setup_theme',
	static function () {
		add_theme_support( 'responsive-embeds' );
		add_theme_support( 'wp-block-styles' );
	}
);

// Skip link, first thing in the page (the target is the main landmark in each template).
add_action(
	'wp_body_open',
	static function () {
		echo '<a class="pd-skip" href="#pd-main">' . esc_html__( 'Skip to content', 'pd-theme' ) . '</a>';
	}
);
