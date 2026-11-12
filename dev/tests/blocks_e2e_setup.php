<?php
/** Creates the dev page used by blocks_e2e.js. Needs blocks1.php to have run (artisans 'Meera Devi' and 'Kavita'). */
if ( getenv( 'PD_TESTS_WIPE' ) !== 'yes' ) { fwrite( STDERR, "Refusing to run: set PD_TESTS_WIPE=yes on a throwaway site.\n" ); exit( 3 ); }
$_SERVER['HTTP_HOST']='127.0.0.1:8088'; $_SERVER['REQUEST_URI']='/'; $_SERVER['SERVER_NAME']='127.0.0.1';
if ( ! function_exists( 'add_action' ) ) { require rtrim( getenv( 'WP_ROOT' ) ?: '/home/user/pd-dev/wp', '/' ) . '/wp-load.php'; }
global $wpdb;
$a1=(int)$wpdb->get_var("SELECT ID FROM {$wpdb->posts} WHERE post_title='Meera Devi' AND post_type='pd_artisan' LIMIT 1");
$a2=(int)$wpdb->get_var("SELECT ID FROM {$wpdb->posts} WHERE post_title LIKE '%Kavita%' AND post_type='pd_artisan' LIMIT 1");
foreach(get_posts(array('post_type'=>'page','name'=>'pd-dev-directory','numberposts'=>-1,'post_status'=>'any','fields'=>'ids')) as $i) wp_delete_post($i,true);
$wpdb->update($wpdb->posts,array('post_title'=>'Kavita Jha'),array('ID'=>$a2)); clean_post_cache($a2);
$content='<!-- wp:query {"query":{"postType":"pd_artisan","perPage":10,"inherit":false,"orderBy":"title","order":"asc"}} --><div class="wp-block-query"><!-- wp:post-template --><!-- wp:pd/artisan-card /--><!-- /wp:post-template --></div><!-- /wp:query -->'
 .'<!-- wp:group --><div class="wp-block-group" id="mirror-a"><!-- wp:pd/follow-button {"artisanPostId":'.$a1.'} /--></div><!-- /wp:group -->'
 .'<!-- wp:group --><div class="wp-block-group" id="mirror-b"><!-- wp:pd/follow-button {"artisanPostId":'.$a1.'} /--></div><!-- /wp:group -->'
 .'<!-- wp:group --><div class="wp-block-group" id="other"><!-- wp:pd/follow-button {"artisanPostId":'.$a2.'} /--></div><!-- /wp:group -->';
$pid=wp_insert_post(array('post_type'=>'page','post_title'=>'PD dev directory','post_name'=>'pd-dev-directory','post_status'=>'publish','post_content'=>$content));
// reset follows so the test starts from a known state
$wpdb->query('DELETE FROM '.PD\Artisan\Follows::table()); PD\Artisan\Table::upsert($a1,array('follower_count'=>0)); PD\Artisan\Table::upsert($a2,array('follower_count'=>4));
echo wp_json_encode(array('page'=>$pid,'a1'=>$a1,'a2'=>$a2)),"\n";
