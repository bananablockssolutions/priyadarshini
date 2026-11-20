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

// Collection cards for the illustrated menu (sample words; the owner writes the real ones).
$craft_words = array(
	'madhubani-painting' => array( 'मधुबनी चित्रकला', 'Fish, lotus and the sun, drawn in double lines with a bamboo pen.', 'For generations the women of Mithila painted these on the walls of the kohbar, the wedding room. Today the same lines are drawn on handmade paper and cloth, every inch filled, nothing left empty. Each painting here is signed by the woman who drew it.', 'fish', 'gulabi', 1 ),
	'sujni-embroidery'   => array( 'सुजनी कढ़ाई', 'Old saris layered and stitched into stories with a running stitch.', 'Sujani began as quilts made for newborns from worn saris. The women of Bhusura now stitch whole village scenes with it: wells, weddings, a girl going to school. One quilt can take a season.', 'lotus', 'kusum', 2 ),
	'sikki-grass-work'   => array( 'सिक्की घास कला', 'Golden river grass, dyed and coiled into toys, boxes and baskets.', 'Sikki grows wild along the rivers of north Bihar. It is cut after the rains, dried until it turns gold, and coiled around a core of munj grass. Elephants, peacocks and lidded boxes for a bride’s trousseau.', 'peacock', 'haldi', 3 ),
);
foreach ( $craft_words as $slug => [ $hi, $teaser, $rest, $scene, $accent, $order ] ) {
	wp_update_term( $crafts[ $slug ], 'product_cat', array( 'description' => $teaser . ' ' . $rest ) );
	if ( class_exists( '\PD\Crafts\Meta' ) ) {
		\PD\Crafts\Meta::set( $crafts[ $slug ], array( 'nameHi' => $hi, 'teaser' => $teaser, 'scene' => $scene, 'accent' => $accent, 'order' => $order, 'hidden' => true ) );
	}
}


// Product types: what the mobile menu lists (approved prototype, 19 Nov). Crafts stay as categories but are hidden from the menu.
$type_words = array(
	'kurti'   => array( 'Kurti', 'कुर्ती', 'Hand-painted kurtis, each signed by the woman who painted it.', 'Cotton and tussar silk, painted by makers in Madhubani and Darbhanga. One kurti takes three to six days and carries the maker’s seal inside the hem.', 'kurti', 'gulabi' ),
	'saree'   => array( 'Saree', 'साड़ी', 'Tussar and cotton sarees with borders painted or stitched by hand.', 'Woven in Bhagalpur, then painted or Sujani-stitched by the maker you see on each piece. Many are made on order to your length and colours.', 'saree', 'haldi' ),
	'dupatta' => array( 'Dupatta', 'दुपट्टा', 'Light dupattas with Sujani stitch and painted pallus.', 'Sujani is a running-stitch quilting craft from Muzaffarpur, once used for baby wraps. Makers now stitch whole stories across a dupatta.', 'dupatta', 'patta' ),
	'wall-art' => array( 'Wall art', 'चित्र', 'Madhubani and Manjusha paintings on handmade paper and cloth.', 'Every painting is one of a kind. The maker’s name, village and the days it took are on the label, and you can commission a new one from the same hand.', 'wallart', 'kusum' ),
	'baskets-and-boxes' => array( 'Baskets & boxes', 'सिक्की', 'Golden sikki grass baskets and boxes, coiled by hand in north Bihar.', 'Sikki grass is cut after the monsoon, dried and dyed, then coiled over a core of munj grass. A lidded box can take a week.', 'basket', 'haldi' ),
);
$types = array();
$n     = 0;
foreach ( $type_words as $slug => [ $name, $hi, $teaser, $rest, $scene, $accent ] ) {
	$t              = term_exists( $slug, 'product_cat' ) ?: wp_insert_term( $name, 'product_cat', array( 'slug' => $slug ) );
	$types[ $slug ] = (int) ( is_array( $t ) ? $t['term_id'] : $t );
	wp_update_term( $types[ $slug ], 'product_cat', array( 'name' => $name, 'description' => $teaser . ' ' . $rest ) );
	if ( class_exists( '\PD\Crafts\Meta' ) ) {
		\PD\Crafts\Meta::set( $types[ $slug ], array( 'nameHi' => $hi, 'teaser' => $teaser, 'scene' => $scene, 'accent' => $accent, 'order' => ++$n, 'hidden' => false ) );
	}
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
	array( 'demo-fish-pair-kurti', 'Fish pair kurti', 'मछली जोड़ा कुर्ती', 'demo-meera-devi', 2400, 'buy', false, 'Cotton, natural pigments', '5 days', 2 ),
	array( 'demo-lotus-pond-kurti', 'Lotus pond kurti', 'कमल ताल कुर्ती', 'demo-kavita-kumari', 2900, 'buy_commission', true, 'Tussar silk', '1 week', 0 ),
	array( 'demo-kohbar-kurti', 'Kohbar kurti', 'कोहबर कुर्ती', 'demo-meera-devi', 3200, 'buy', false, 'Cotton, natural pigments', '6 days', 1 ),
	array( 'demo-tussar-lotus-saree', 'Tussar lotus saree', 'टसर कमल साड़ी', 'demo-kavita-kumari', 9800, 'buy_commission', true, 'Bhagalpuri tussar silk', '3 weeks', 0 ),
	array( 'demo-fish-border-saree', 'Fish border saree', 'मछली किनारी साड़ी', 'demo-meera-devi', 12500, 'buy', false, 'Tussar silk, natural pigments', '3 weeks', 1 ),
	array( 'demo-sujani-story-dupatta', 'Sujani story dupatta', 'सुजनी कथा दुपट्टा', 'demo-kavita-kumari', 1800, 'buy', false, 'Cotton, cotton thread', '1 week', 2 ),
	array( 'demo-lidded-sikki-box', 'Lidded sikki box', 'ढक्कन वाली सिक्की डिब्बी', 'demo-ramesh-kumar', 990, 'buy', false, 'Sikki and munj grass', '1 week', 4 ),
);
$type_of = array(
	'demo-fish-and-lotus-pond' => 'wall-art', 'demo-kohbar-for-a-wedding' => 'wall-art', 'demo-tree-of-life' => 'wall-art', 'demo-village-story-panel' => 'wall-art',
	'demo-sujni-quilt-cover' => 'dupatta', 'demo-sikki-elephant' => 'baskets-and-boxes', 'demo-lidded-sikki-box' => 'baskets-and-boxes',
	'demo-fish-pair-kurti' => 'kurti', 'demo-lotus-pond-kurti' => 'kurti', 'demo-kohbar-kurti' => 'kurti',
	'demo-tussar-lotus-saree' => 'saree', 'demo-fish-border-saree' => 'saree', 'demo-sujani-story-dupatta' => 'dupatta',
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
	$p->set_category_ids( array( $crafts[ array( 'demo-meera-devi' => 'madhubani-painting', 'demo-kavita-kumari' => 'sujni-embroidery', 'demo-ramesh-kumar' => 'sikki-grass-work' )[ $maker ] ], $types[ $type_of[ $slug ] ] ) );
	$pid = $p->save();
	Meta::set( $pid, array( 'artisan' => $ids[ $maker ], 'title_hi' => $hi, 'format' => $format, 'made_on_order' => $on_order, 'material' => $material, 'making_time' => $time ) );
}
echo 'Seeded ' . count( $ids ) . ' artisans and ' . count( $works ) . " works.\n";

// Colours and sizes (approved prototype, sketch 3): kurtis, sarees, dupattas and wall art become variable products.
$attr_ids = array();
foreach ( array( 'colour' => 'Colour', 'size' => 'Size' ) as $slug => $label ) {
	$id = wc_attribute_taxonomy_id_by_name( $slug );
	if ( ! $id ) {
		$id = wc_create_attribute( array( 'name' => $label, 'slug' => $slug, 'type' => 'select', 'order_by' => 'menu_order' ) );
	}
	$attr_ids[ $slug ] = (int) $id;
	if ( ! taxonomy_exists( 'pa_' . $slug ) ) {
		register_taxonomy( 'pa_' . $slug, array( 'product', 'product_variation' ), array( 'hierarchical' => false, 'show_ui' => false, 'query_var' => true, 'rewrite' => false ) );
	}
}
$colour_hex = array( 'sindoor' => array( 'Sindoor red', '#C8321F' ), 'neel' => array( 'Neel blue', '#25306B' ), 'haldi' => array( 'Haldi yellow', '#E9A30C' ), 'gulabi' => array( 'Gulabi pink', '#D9467A' ), 'patta' => array( 'Patta green', '#3E7B3A' ), 'kajal' => array( 'Kajal black', '#2A1E17' ), 'kusum' => array( 'Kusum orange', '#E26A1B' ), 'kagaz' => array( 'Kagaz cream', '#E9DCC3' ) );
foreach ( $colour_hex as $slug => [ $name, $hex ] ) {
	$t = term_exists( $slug, 'pa_colour' ) ?: wp_insert_term( $name, 'pa_colour', array( 'slug' => $slug ) );
	update_term_meta( (int) ( is_array( $t ) ? $t['term_id'] : $t ), 'pd_hex', $hex );
}
$order = 0;
foreach ( array( 's' => 'S', 'm' => 'M', 'l' => 'L', 'xl' => 'XL', '5-5m' => '5.5m', '6-3m' => '6.3m', 'free' => 'Free', 'a4' => 'A4', 'a3' => 'A3' ) as $slug => $name ) {
	$t = term_exists( $slug, 'pa_size' ) ?: wp_insert_term( $name, 'pa_size', array( 'slug' => $slug ) );
	update_term_meta( (int) ( is_array( $t ) ? $t['term_id'] : $t ), 'order', ++$order );
}
$choices = array(
	'demo-fish-pair-kurti'      => array( array( 'sindoor', 'neel', 'haldi' ), array( 's', 'm', 'l', 'xl' ), 'Featured' ),
	'demo-lotus-pond-kurti'     => array( array( 'gulabi', 'patta', 'kajal' ), array( 's', 'm', 'l', 'xl' ), 'Made on order' ),
	'demo-kohbar-kurti'         => array( array( 'kusum', 'neel', 'kagaz' ), array( 's', 'm', 'l', 'xl' ), 'Award winner' ),
	'demo-tussar-lotus-saree'   => array( array( 'haldi', 'sindoor' ), array( '5-5m', '6-3m' ), 'One of a kind' ),
	'demo-fish-border-saree'    => array( array( 'neel', 'gulabi', 'kagaz' ), array( '5-5m', '6-3m' ), 'New' ),
	'demo-sujani-story-dupatta' => array( array( 'patta', 'haldi', 'sindoor' ), array( 'free' ), 'Last piece' ),
	'demo-fish-and-lotus-pond'  => array( array(), array( 'a4', 'a3' ), 'Award winner' ),
	'demo-tree-of-life'         => array( array(), array(), 'Made on order' ),
	'demo-sikki-elephant'       => array( array(), array(), 'New' ),
);
foreach ( $choices as $slug => [ $colours, $sizes, $badge ] ) {
	$post = get_page_by_path( $slug, OBJECT, 'product' );
	if ( ! $post ) {
		continue;
	}
	update_post_meta( $post->ID, Meta::BADGE, $badge );
	if ( ! $colours && ! $sizes ) {
		continue;
	}
	wp_set_object_terms( $post->ID, 'variable', 'product_type' );
	$p     = new WC_Product_Variable( $post->ID );
	$price = (float) get_post_meta( $post->ID, '_regular_price', true ) ?: 2000;
	$attrs = array();
	foreach ( array( 'colour' => $colours, 'size' => $sizes ) as $key => $slugs ) {
		if ( ! $slugs ) {
			continue;
		}
		$ids = array_map( static fn( $s ) => (int) get_term_by( 'slug', $s, 'pa_' . $key )->term_id, $slugs );
		wp_set_object_terms( $post->ID, $ids, 'pa_' . $key );
		$a = new WC_Product_Attribute();
		$a->set_id( $attr_ids[ $key ] );
		$a->set_name( 'pa_' . $key );
		$a->set_options( $ids );
		$a->set_visible( true );
		$a->set_variation( true );
		$attrs[] = $a;
	}
	$p->set_attributes( $attrs );
	$p->save();
	foreach ( $p->get_children() as $child ) {
		wp_delete_post( $child, true );
	}
	foreach ( $colours ?: array( '' ) as $c ) {
		foreach ( $sizes ?: array( '' ) as $z ) {
			$v = new WC_Product_Variation();
			$v->set_parent_id( $post->ID );
			$v->set_attributes( array_filter( array( 'pa_colour' => $c, 'pa_size' => $z ) ) );
			$v->set_regular_price( (string) $price );
			$v->set_stock_status( 'instock' );
			$v->save();
		}
	}
	WC_Product_Variable::sync( $post->ID );
	wc_delete_product_transients( $post->ID );
}
echo "Added colours, sizes and badges.\n";
