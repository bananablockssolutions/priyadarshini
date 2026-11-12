<?php
/** M-1 spike 7: normalised review thread. Artisan side is real; product side uses WP comments of type `review` on a STAND-IN product post type. */
// SAFETY: these tests DELETE data (artisans, reviews, allocations, and test products/comments). Run only on a throwaway site.
if ( getenv( 'PD_TESTS_WIPE' ) !== 'yes' ) { fwrite( STDERR, "Refusing to run: this test deletes data. Use a throwaway site and set PD_TESTS_WIPE=yes.\n" ); exit( 3 ); }
$_SERVER['HTTP_HOST']='127.0.0.1:8088'; $_SERVER['REQUEST_URI']='/'; $_SERVER['SERVER_NAME']='127.0.0.1';
if ( ! function_exists( 'add_action' ) ) { require rtrim( getenv( 'WP_ROOT' ) ?: '/home/user/pd-dev/wp', '/' ) . '/wp-load.php'; } // already loaded under `wp eval-file`
use PD\Reviews\Repository as R; use PD\Reviews\Review; use PD\Reviews\Tables; use PD\Artisan\Table as AT;
$fail=0; function t($ok,$m){ global $fail; echo ($ok?'PASS ':'FAIL ').$m."\n"; if(!$ok)$fail=1; }
global $wpdb;
PD\Schema::install();
foreach([Tables::reviews(),Tables::replies(),Tables::index(),AT::name()] as $tb) $wpdb->query("DELETE FROM $tb");
foreach(get_posts(array('post_type'=>array('pd_artisan','product'),'numberposts'=>-1,'post_status'=>'any','fields'=>'ids')) as $id) wp_delete_post($id,true);
foreach(get_comments(array('type'=>'review','number'=>0,'fields'=>'ids')) as $cid) wp_delete_comment($cid,true);

// people
$mk=function($login,$first,$last,$email){ $u=get_user_by('login',$login); $id=$u?$u->ID:wp_insert_user(array('user_login'=>$login,'user_pass'=>'x','user_email'=>$email,'first_name'=>$first,'last_name'=>$last,'display_name'=>trim("$first $last"))); return (int)$id; };
$anita=$mk('anita','Anita','Kumari','anita@example.test'); $raj=$mk('raj','Raj','Singh','raj@example.test'); $mailname=$mk('mailname','','','mailname@example.test'); wp_update_user(array('ID'=>$mailname,'display_name'=>'mailname@example.test'));
$meeraUser=$mk('meera','Meera','Devi','meera@example.test'); $other=$mk('other','Other','Artisan','other@example.test');

$art=wp_insert_post(array('post_type'=>'pd_artisan','post_title'=>'Meera Devi','post_status'=>'draft'));
$art2=wp_insert_post(array('post_type'=>'pd_artisan','post_title'=>'Kavita','post_status'=>'draft'));
AT::upsert($art,array('user_id'=>$meeraUser,'verification_status'=>'verified')); AT::upsert($art2,array('user_id'=>$other,'verification_status'=>'verified'));

// 60 artisan reviews for $art, from distinct allocations, spread in time
mt_srand(11); $ids=array(); $ratings=array();
for($i=1;$i<=60;$i++){ $rt=mt_rand(1,5); $ratings[]=$rt; $uid=($i%2)?$anita:$raj;
  $id=R::add_artisan_review($art,$uid,1000+$i,$rt,"Review $i",array('has_photos'=>$i%7===0,'created_at'=>gmdate('Y-m-d H:i:s',strtotime('2026-01-01 UTC')+$i*86400))); $ids[$i]=$id; }
t(!is_wp_error($ids[1]) && count(array_filter($ids,'is_int'))===60,'60 artisan reviews stored');
$dup=R::add_artisan_review($art,$anita,1001,5,'again'); t(is_wp_error($dup)&&$dup->get_error_code()==='pd_duplicate','second review for the same order/artisan is refused');
t(is_wp_error(R::add_artisan_review($art,$anita,9999,6,'x')),'rating 6 refused');
// one review for the other artisan
R::add_artisan_review($art2,$anita,2001,5,'Other artisan');

// 25 product reviews on 5 products by $art, 4 by $art2
$prods=array(); for($p=0;$p<5;$p++){ $pid=wp_insert_post(array('post_type'=>'product','post_title'=>"Work $p",'post_status'=>'publish')); update_post_meta($pid,'_pd_artisan_post_id',$art); $prods[]=$pid; }
$p2=wp_insert_post(array('post_type'=>'product','post_title'=>'Other work','post_status'=>'publish')); update_post_meta($p2,'_pd_artisan_post_id',$art2);
$cids=array(); for($c=0;$c<25;$c++){ $cid=wp_insert_comment(array('comment_post_ID'=>$prods[$c%5],'comment_type'=>'review','comment_approved'=>1,'comment_content'=>"Piece review $c",'user_id'=>$anita,'comment_date'=>gmdate('Y-m-d H:i:s',strtotime('2026-02-01 UTC')+$c*3600),'comment_date_gmt'=>gmdate('Y-m-d H:i:s',strtotime('2026-02-01 UTC')+$c*3600))); update_comment_meta($cid,'rating',1+$c%5); $cids[]=$cid; R::index_product_review($cid); }
$cid_other=wp_insert_comment(array('comment_post_ID'=>$p2,'comment_type'=>'review','comment_approved'=>1,'comment_content'=>'x','user_id'=>$raj)); update_comment_meta($cid_other,'rating',2); R::index_product_review($cid_other);

$a=R::list(array('scope'=>'artisan','artisan_id'=>$art)); $p=R::list(array('scope'=>'product','artisan_id'=>$art));
t($a['total']===60 && $p['total']===25,"scopes separate: artisan {$a['total']}, product {$p['total']}");
t(!array_filter(array_merge($a['items'],$p['items']),fn($r)=>($r->type==='artisan')!==in_array($r,$a['items'],true)),'no mixed list');
t(R::list(array('scope'=>'product','product_id'=>$prods[0]))['total']===5,'product scope can narrow to one work');

// sorting
$hi=R::list(array('artisan_id'=>$art,'sort'=>'highest'))['items']; $ok=true; for($i=1;$i<count($hi);$i++) if($hi[$i]->rating>$hi[$i-1]->rating) $ok=false; t($ok,'sort highest is non-increasing');
$lo=R::list(array('artisan_id'=>$art,'sort'=>'lowest'))['items']; $ok=true; for($i=1;$i<count($lo);$i++) if($lo[$i]->rating<$lo[$i-1]->rating) $ok=false; t($ok,'sort lowest is non-decreasing');
$rc=R::list(array('artisan_id'=>$art,'sort'=>'recent'))['items']; $ok=true; for($i=1;$i<count($rc);$i++) if($rc[$i]->created_at>$rc[$i-1]->created_at) $ok=false; t($ok&&$rc[0]->body==='Review 60','sort recent newest first');
$ph=R::list(array('artisan_id'=>$art,'sort'=>'photos'))['items']; t($ph[0]->has_photos && $ph[7]->has_photos && !$ph[8]->has_photos,'photos sort puts the 8 photo reviews first');
$f=R::list(array('artisan_id'=>$art,'rating'=>5,'per_page'=>50)); $n5=count(array_filter($ratings,fn($x)=>$x===5)); t($f['total']===$n5 && !array_filter($f['items'],fn($r)=>$r->rating!==5),"rating filter 5: $n5");
t(R::list(array('artisan_id'=>$art,'photos'=>true))['total']===8,'photos filter (8 reviews with photos among 60: i%7==0)');

// paging
$seen=array(); for($pg=1;$pg<=6;$pg++){ $r=R::list(array('artisan_id'=>$art,'sort'=>'highest','page'=>$pg)); foreach($r['items'] as $x) $seen[]=$x->id; t(count($r['items'])===10 && $r['pages']===6,"page $pg has 10 of 6 pages") ; }
t(count($seen)===60 && count(array_unique($seen))===60,'6 pages cover all 60 with no overlap');
t(R::list(array('artisan_id'=>$art,'page'=>99))['page']===6,'page beyond end clamps to last');

// deep link
$target=Review::typed_id('artisan',$ids[23]); $pg=R::page_of($target,array('artisan_id'=>$art,'sort'=>'highest')); $got=array_map(fn($x)=>$x->id,R::list(array('artisan_id'=>$art,'sort'=>'highest','page'=>$pg))['items']); t(in_array($target,$got,true),"deep link ?review=$target lands on page $pg");
t(R::page_of('review-artisan-999999',array('artisan_id'=>$art))===null,'deep link to unknown review returns null');
t(Review::parse_id('review-artisan-45')===array('artisan',45) && Review::parse_id('review-x-1')===null && Review::parse_id("review-artisan-1'--")===null,'typed id parsing is strict');

// reply
$rid=$ids[5]; $x=R::reply($rid,$meeraUser,'Thank you!'); t(is_int($x),'artisan replies');
t(R::reply($rid,$meeraUser,'again')->get_error_code()==='pd_already_replied','second reply refused (once only)');
t(R::reply($ids[6],$other,'not mine')->get_error_code()==='pd_forbidden','other artisan cannot reply');
t(R::reply($ids[6],$anita,'buyer')->get_error_code()==='pd_forbidden','buyer cannot reply');
t(R::reply($ids[6],$meeraUser,'   ')->get_error_code()==='pd_empty','empty reply refused');
$tid=Review::typed_id('artisan',$rid); $pg5=R::page_of($tid,array('artisan_id'=>$art)); $shown=array_values(array_filter(R::list(array('artisan_id'=>$art,'page'=>$pg5))['items'],fn($r)=>$r->source_id===$rid))[0]; t($shown->reply && $shown->reply['body']==='Thank you!','reply appears on the review');
t((int)AT::get($art)['reply_rate']===2,'reply_rate cache = 1/60 -> 2%'); // round(100/60)=2
t(R::edit_reply($rid,$meeraUser,'Thanks so much!',time()+6*86400)===true,'edit allowed on day 6');
t(R::edit_reply($rid,$meeraUser,'late',time()+8*86400)->get_error_code()==='pd_locked','edit locked after 7 days');
t(R::edit_reply($rid,$other,'hijack')->get_error_code()==='pd_forbidden','other artisan cannot edit');

// rating cache
$avg=round(array_sum($ratings)/60,2); $row=AT::get($art); t($row['rating_count']==60 && abs($row['rating_avg']-$avg)<0.006,"rating cache {$row['rating_avg']} = avg $avg of 60 artisan reviews (product reviews excluded)");

// index consistency: rebuild equals incremental
$before=$wpdb->get_col('SELECT typed_id FROM '.Tables::index().' ORDER BY typed_id'); $n=R::reindex(); $after=$wpdb->get_col('SELECT typed_id FROM '.Tables::index().' ORDER BY typed_id'); t($before===$after && $n===count($after),"rebuild reproduces the incremental index ($n rows)");
// hiding removes
$wpdb->update(Tables::reviews(),array('state'=>'hidden'),array('id'=>$ids[10])); R::index_artisan_review($ids[10]); t(R::list(array('artisan_id'=>$art,'per_page'=>50))['total']===59,'hidden review leaves the list after re-index');
wp_set_comment_status($cids[0],'hold'); R::index_product_review($cids[0]); t(R::list(array('scope'=>'product','artisan_id'=>$art))['total']===24,'unapproved product review leaves the list');

// privacy
$all=array_merge(R::list(array('artisan_id'=>$art,'per_page'=>50))['items'],R::list(array('scope'=>'product','artisan_id'=>$art,'per_page'=>50))['items']); $json=json_encode(array_map(fn($r)=>$r->to_array(),$all));
t(strpos($json,'@')===false && !preg_match('/"user_id"|"email"|"login"/',$json),'no email, login or user id in public review data');
t($all[0]->author==='Anita K.' || $all[0]->author==='Raj S.','author shown as first name and initial: '.$all[0]->author);
$cm=wp_insert_comment(array('comment_post_ID'=>$prods[1],'comment_type'=>'review','comment_approved'=>1,'comment_content'=>'z','user_id'=>$mailname)); update_comment_meta($cm,'rating',3); R::index_product_review($cm);
$mm=array_values(array_filter(R::list(array('scope'=>'product','artisan_id'=>$art,'per_page'=>50))['items'],fn($r)=>$r->source_id===$cm))[0]; t($mm->author==='A buyer','a display name that is an email is replaced by "A buyer"');
// SQL safety on odd input
t(is_array(R::list(array('artisan_id'=>"1 OR 1=1",'sort'=>"x; DROP TABLE",'page'=>"abc",'scope'=>"'"))),'odd arguments do not break the query');
exit($fail?1:0);
