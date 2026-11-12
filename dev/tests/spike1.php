<?php
/**
 * M-1 spike 1: Artisan CPT plus operational-table querying.
 * Run: php spike1.php   (from pd-dev). Uses a STAND-IN product_cat taxonomy because WooCommerce is not installed in this sandbox.
 */
// SAFETY: these tests DELETE data (artisans, reviews, allocations, and test products/comments). Run only on a throwaway site.
if ( getenv( 'PD_TESTS_WIPE' ) !== 'yes' ) { fwrite( STDERR, "Refusing to run: this test deletes data. Use a throwaway site and set PD_TESTS_WIPE=yes.\n" ); exit( 3 ); }
$_SERVER['HTTP_HOST'] = '127.0.0.1:8088'; $_SERVER['REQUEST_URI'] = '/'; $_SERVER['SERVER_NAME'] = '127.0.0.1';
if ( ! function_exists( 'add_action' ) ) { require rtrim( getenv( 'WP_ROOT' ) ?: '/home/user/pd-dev/wp', '/' ) . '/wp-load.php'; } // already loaded under `wp eval-file`

function t($ok,$msg){ echo ($ok?'PASS ':'FAIL ').$msg."\n"; if(!$ok){$GLOBALS['fail']=1;} }
$fail = 0; require_once ABSPATH.'wp-admin/includes/plugin.php';

if(!is_plugin_active('pd-marketplace/pd-marketplace.php')){ require_once ABSPATH.'wp-admin/includes/plugin.php'; activate_plugin('pd-marketplace/pd-marketplace.php'); echo "activated pd-marketplace; run again\n"; exit(2); }
global $wpdb;
$wpdb->query('DELETE FROM '.\PD\Artisan\Table::name());
foreach (get_posts(array('post_type'=>'pd_artisan','numberposts'=>-1,'post_status'=>'any','fields'=>'ids')) as $id) wp_delete_post($id,true);

$crafts = array();
foreach (array('madhubani','sujini','sikki','tikuli','manjusha') as $c) { $term = term_exists($c,'product_cat') ?: wp_insert_term($c,'product_cat'); $crafts[] = is_array($term)?(int)$term['term_id']:(int)$term; }
$accents = array('gulabi','kusum','neel','patta','sindoor','haldi');

mt_srand(7);
$N = 200; $verified = 0; $takingVerified = 0; $madhubaniTaking = 0;
for ($i=0; $i<$N; $i++) {
  $id = wp_insert_post(array('post_type'=>'pd_artisan','post_title'=>"Artisan $i",'post_status'=>'draft'));
  $isVerified = ($i % 10 !== 0);              // every 10th is unverified
  $taking = ($i % 3 !== 0) ? 1 : 0;
  \PD\Artisan\Table::upsert($id, array('user_id'=>1000+$i,'verification_status'=>$isVerified?'verified':'pending','accent'=>$accents[$i%6],'taking_orders'=>$taking,'rating_avg'=>round(3+mt_rand(0,200)/100,2),'rating_count'=>mt_rand(1,300),'sales_count'=>mt_rand(0,900),'follower_count'=>mt_rand(0,5000)));
  $craft = $crafts[$i%5];
  wp_set_object_terms($id, array($craft), 'product_cat');
  // try to publish: rule DB-13 must hold unverified ones as draft
  wp_update_post(array('ID'=>$id,'post_status'=>'publish'));
  if ($isVerified) { $verified++; if ($taking) $takingVerified++; if ($taking && $i%5===0) $madhubaniTaking++; }
}

$pub = (int) (new WP_Query(array('post_type'=>'pd_artisan','post_status'=>'publish','posts_per_page'=>1)))->found_posts;
t($pub === $verified, "DB-13 publication rule: published $pub, verified $verified");

$q = new WP_Query(array('post_type'=>'pd_artisan','posts_per_page'=>12,'pd_taking_orders'=>1,'pd_orderby'=>'rating','tax_query'=>array(array('taxonomy'=>'product_cat','field'=>'term_id','terms'=>array($crafts[0])))));
t($q->found_posts === $madhubaniTaking, "craft + taking orders filter: found {$q->found_posts}, expected $madhubaniTaking");

$prev = 99; $sorted = true; foreach ($q->posts as $p){ $r=(float)\PD\Artisan\Table::get($p->ID)['rating_avg']; if($r>$prev) $sorted=false; $prev=$r; }
t($sorted && count($q->posts) === min(12,$madhubaniTaking), "rating sort descending on page 1 (".count($q->posts)." rows)");

$p2 = new WP_Query(array('post_type'=>'pd_artisan','posts_per_page'=>12,'paged'=>2,'pd_taking_orders'=>1,'pd_orderby'=>'rating'));
$p1 = new WP_Query(array('post_type'=>'pd_artisan','posts_per_page'=>12,'paged'=>1,'pd_taking_orders'=>1,'pd_orderby'=>'rating'));
t(!array_intersect(wp_list_pluck($p1->posts,'ID'), wp_list_pluck($p2->posts,'ID')), 'pagination: page 1 and 2 do not overlap');
t($p1->found_posts === $takingVerified, "taking-orders total {$p1->found_posts} == $takingVerified");

// timing (200 rows, warm), 20 runs
$ts = array(); for($k=0;$k<20;$k++){ wp_cache_flush(); $s=microtime(true); $x=new WP_Query(array('post_type'=>'pd_artisan','posts_per_page'=>12,'pd_taking_orders'=>1,'pd_orderby'=>'rating','tax_query'=>array(array('taxonomy'=>'product_cat','field'=>'term_id','terms'=>array($crafts[1]))))); $ts[]=(microtime(true)-$s)*1000; }
sort($ts); $med=$ts[10];
t($med < 100, sprintf('query median %.1f ms (limit 100 ms, database: '.(defined('DB_ENGINE')?DB_ENGINE:(isset($GLOBALS['wpdb']->is_mysql)&&$GLOBALS['wpdb']->is_mysql&&strpos($GLOBALS['wpdb']->db_server_info(),'SQLite')===false?'MySQL/MariaDB':'SQLite, indicative only')).')', $med));

// public/private split (DB-12)
$pv = \PD\Artisan\Table::public_view($p1->posts[0]->ID);
t(!isset($pv['user_id']) && !isset($pv['verified_at']) && !isset($pv['verifier']) && isset($pv['rating_avg']), 'public view carries no private columns');

// unmodified core/query works: render block markup through the real block renderer
$html = do_blocks('<!-- wp:query {"query":{"postType":"pd_artisan","perPage":3,"inherit":false}} --><div class="wp-block-query"><!-- wp:post-template --><!-- wp:post-title /--><!-- /wp:post-template --></div><!-- /wp:query -->');
t(substr_count($html,'wp-block-post-title') === 3, 'core/query renders pd_artisan unmodified ('.substr_count($html,'wp-block-post-title').' titles)');

exit($fail?1:0);
