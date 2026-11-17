<?php
/**
 * DEV ONLY: demo artisans and works so the theme has something to show. Idempotent (looks up by slug).
 * Run: wp eval-file dev/seed-demo.php   (or include after wp-load.php). Remove with dev/tests/cleanup.php.
 * Names and works are samples, not real people.
 */
use PD\Artisan\Table;
use PD\Works\Meta;

defined( 'ABSPATH' ) || exit;
if ( ! function_exists( 'wc_get_product' ) ) {
	exit( "WooCommerce is needed.\n" );
}

$crafts = array();
foreach ( array( 'madhubani-painting' => 'Madhubani painting', 'sujni-embroidery' => 'Sujni embroidery', 'sikki-grass-work' => 'Sikki grass work' ) as $slug => $name ) {
	$t              = term_exists( $slug, 'product_cat' ) ?: wp_insert_term( $name, 'product_cat', array( 'slug' => $slug ) );
	$crafts[ $slug ] = (int) ( is_array( $t ) ? $t['term_id'] : $t );
}

$artisans = array(
	array( 'demo-meera-devi', 'Meera Devi', 'मीरा देवी', 'Jitwarpur', 'gulabi', 'fish', 'madhubani-painting', 'Every line is drawn with a bamboo pen.' ),
	array( 'demo-kavita-kumari', 'Kavita Kumari', 'कविता कुमारी', 'Bhusura', 'kusum', 'lotus', 'sujni-embroidery', 'Every stitch is something my mother taught me.' ),
	array( 'demo-ramesh-kumar', 'Ramesh Kumar', 'रमेश कुमार', 'Raiyam', 'patta', 'leaf', 'sikki-grass-work', 'Golden grass from the river bank, coiled by hand.' ),
);
$ids = array();
foreach ( $artisans as $i => [ $slug, $name, $hi, $village, $accent, $motif, $craft, $quote ] ) {
	$user = get_user_by( 'login', $slug ) ?: get_user_by( 'id', wp_create_user( $slug, wp_generate_password(), $slug . '@example.invalid' ) );
	$post = get_page_by_path( $slug, OBJECT, 'pd_artisan' );
	$id   = $post ? $post->ID : wp_insert_post( array( 'post_type' => 'pd_artisan', 'post_status' => 'draft', 'post_title' => $name, 'post_name' => $slug, 'post_content' => "<!-- wp:paragraph --><p>{$quote}</p><!-- /wp:paragraph -->" ) );
	Table::upsert( $id, array( 'user_id' => $user->ID, 'verification_status' => 'verified', 'accent' => $accent, 'motif' => $motif, 'taking_orders' => 1, 'rating_avg' => array( 4.8, 4.6, 0 )[ $i ], 'rating_count' => array( 23, 9, 0 )[ $i ], 'follower_count' => array( 140, 52, 3 )[ $i ] ) );
	update_post_meta( $id, '_pd_name_hi', $hi );
	update_post_meta( $id, '_pd_village', $village );
	wp_set_object_terms( $id, $crafts[ $craft ], 'product_cat' );
	wp_update_post( array( 'ID' => $id, 'post_status' => 'publish' ) );
	$ids[ $slug ] = $id;
}

$works = array(
	array( 'demo-fish-and-lotus-pond', 'Fish and Lotus Pond', 'मछली और कमल का तालाब', 'demo-meera-devi', 4200, 'buy_commission', false, 'Handmade paper, natural pigments', '2 weeks', 1 ),
	array( 'demo-kohbar-for-a-wedding', 'Kohbar for a Wedding', 'विवाह का कोहबर', 'demo-meera-devi', 12500, 'commission', false, 'Handmade paper, natural pigments', '5 weeks', 0 ),
	array( 'demo-tree-of-life', 'Tree of Life', 'जीवन वृक्ष', 'demo-meera-devi', 6800, 'buy', true, 'Cotton canvas, natural pigments', '3 weeks', 0 ),
	array( 'demo-sujni-quilt-cover', 'Sujni Quilt Cover', 'सुजनी रज़ाई कवर', 'demo-kavita-kumari', 8900, 'buy_commission', false, 'Cotton, cotton thread', '3 weeks', 1 ),
	array( 'demo-village-story-panel', 'Village Story Panel', 'गाँव की कहानी', 'demo-kavita-kumari', 5400, 'buy', false, 'Cotton, cotton thread', '2 weeks', 2 ),
	array( 'demo-sikki-elephant', 'Sikki Elephant', 'सिक्की हाथी', 'demo-ramesh-kumar', 1800, 'buy', false, 'Sikki grass, dye', '4 days', 3 ),
);
foreach ( $works as [ $slug, $title, $hi, $maker, $price, $format, $on_order, $material, $time, $stock ] ) {
	$post = get_page_by_path( $slug, OBJECT, 'product' );
	$p    = $post ? wc_get_product( $post->ID ) : new WC_Product_Simple();
	$p->set_name( $title );
	$p->set_slug( $slug );
	$p->set_status( 'publish' );
	$p->set_regular_price( (string) $price );
	$p->set_description( "<p>{$title}, made by hand. Sample listing for the theme preview.</p>" );
	$p->set_manage_stock( ! $on_order );
	$p->set_stock_quantity( $on_order ? null : $stock );
	$p->set_stock_status( $on_order || $stock > 0 ? 'instock' : 'outofstock' );
	$p->set_category_ids( array( $crafts[ array( 'demo-meera-devi' => 'madhubani-painting', 'demo-kavita-kumari' => 'sujni-embroidery', 'demo-ramesh-kumar' => 'sikki-grass-work' )[ $maker ] ] ) );
	$pid = $p->save();
	Meta::set( $pid, array( 'artisan' => $ids[ $maker ], 'title_hi' => $hi, 'format' => $format, 'made_on_order' => $on_order, 'material' => $material, 'making_time' => $time ) );
}
echo 'Seeded ' . count( $ids ) . ' artisans and ' . count( $works ) . " works.\n";
