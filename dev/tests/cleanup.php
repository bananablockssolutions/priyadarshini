<?php
/**
 * Removes everything the dev tests create, so a test run on a fresh site leaves it clean.
 * Run: PD_TESTS_WIPE=yes wp eval-file cleanup.php
 * Refuses to run if the site has any WooCommerce order, or any user who is not one of the test users and is not an administrator.
 */
if ( getenv( 'PD_TESTS_WIPE' ) !== 'yes' ) { fwrite( STDERR, "Refusing to run: set PD_TESTS_WIPE=yes.\n" ); exit( 3 ); }
$_SERVER['HTTP_HOST'] = $_SERVER['HTTP_HOST'] ?? '127.0.0.1'; $_SERVER['REQUEST_URI'] = $_SERVER['REQUEST_URI'] ?? '/'; $_SERVER['SERVER_NAME'] = $_SERVER['SERVER_NAME'] ?? '127.0.0.1';
if ( ! function_exists( 'add_action' ) ) { require rtrim( getenv( 'WP_ROOT' ) ?: '/home/user/pd-dev/wp', '/' ) . '/wp-load.php'; }
global $wpdb;
require_once ABSPATH . 'wp-admin/includes/user.php';

$test_users = array( 'buyer', 'buyer2', 'meera2', 'kav', 'nv', 'anita', 'raj', 'mailname', 'meera', 'other' );
if ( function_exists( 'wc_get_orders' ) && wc_get_orders( array( 'limit' => 1, 'return' => 'ids' ) ) ) { fwrite( STDERR, "Refusing: this site has WooCommerce orders.\n" ); exit( 4 ); }
foreach ( get_users( array( 'fields' => array( 'ID', 'user_login' ) ) ) as $u ) {
	if ( in_array( $u->user_login, $test_users, true ) ) { continue; }
	if ( ! user_can( $u->ID, 'manage_options' ) ) { fwrite( STDERR, "Refusing: real non-admin user '{$u->user_login}' exists.\n" ); exit( 5 ); }
}

$n = array( 'artisans' => 0, 'pages' => 0, 'products' => 0, 'comments' => 0, 'users' => 0 );
foreach ( get_posts( array( 'post_type' => 'pd_artisan', 'numberposts' => -1, 'post_status' => 'any', 'fields' => 'ids' ) ) as $id ) { wp_delete_post( $id, true ); $n['artisans']++; }
foreach ( get_posts( array( 'post_type' => 'page', 'name' => 'pd-dev-directory', 'numberposts' => -1, 'post_status' => 'any', 'fields' => 'ids' ) ) as $id ) { wp_delete_post( $id, true ); $n['pages']++; }
foreach ( get_posts( array( 'post_type' => 'product', 'numberposts' => -1, 'post_status' => 'any', 'fields' => 'ids', 'meta_key' => '_pd_artisan_post_id' ) ) as $id ) { wp_delete_post( $id, true ); $n['products']++; }
foreach ( get_posts( array( 'post_type' => 'product', 'numberposts' => -1, 'post_status' => 'any', 'fields' => 'ids', 's' => 'Work ' ) ) as $id ) { if ( preg_match( '/^(Work \d|Other work)$/', get_the_title( $id ) ) ) { wp_delete_post( $id, true ); $n['products']++; } }
foreach ( get_comments( array( 'type' => 'review', 'number' => 0, 'fields' => 'ids', 'status' => 'all' ) ) as $cid ) { if ( in_array( get_comment( $cid )->comment_content, array(), true ) || str_starts_with( get_comment( $cid )->comment_content, 'Piece review' ) || in_array( get_comment( $cid )->comment_content, array( 'x', 'z' ), true ) ) { wp_delete_comment( $cid, true ); $n['comments']++; } }
foreach ( $test_users as $login ) { $u = get_user_by( 'login', $login ); if ( $u && ! user_can( $u->ID, 'manage_options' ) ) { wp_delete_user( $u->ID ); $n['users']++; } }
foreach ( array( 'pd_artisans', 'pd_artisan_reviews', 'pd_artisan_review_replies', 'pd_review_index', 'pd_order_allocations', 'pd_order_allocation_items', 'pd_shipments', 'pd_follows' ) as $t ) { $wpdb->query( "DELETE FROM {$wpdb->prefix}{$t}" ); }
$tax = taxonomy_exists( 'product_cat' ) ? get_terms( array( 'taxonomy' => 'product_cat', 'hide_empty' => false, 'fields' => 'all' ) ) : array();
foreach ( (array) $tax as $term ) { if ( in_array( $term->slug, array( 'madhubani', 'sujini', 'sikki', 'tikuli', 'manjusha' ), true ) ) { wp_delete_term( $term->term_id, 'product_cat' ); } }
echo 'cleaned: ' . wp_json_encode( $n ) . "\n";
