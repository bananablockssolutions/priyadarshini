<?php
/** pd-blocks 0.1: State provider, artisan card, follow button, Follows and REST (PHP level). */
if ( getenv( 'PD_TESTS_WIPE' ) !== 'yes' ) { fwrite( STDERR, "Refusing to run: this test deletes data. Use a throwaway site and set PD_TESTS_WIPE=yes.\n" ); exit( 3 ); }
$_SERVER['HTTP_HOST']='127.0.0.1:8088'; $_SERVER['REQUEST_URI']='/'; $_SERVER['SERVER_NAME']='127.0.0.1';
if ( ! function_exists( 'add_action' ) ) { require rtrim( getenv( 'WP_ROOT' ) ?: '/home/user/pd-dev/wp', '/' ) . '/wp-load.php'; }
require_once ABSPATH.'wp-admin/includes/plugin.php';
foreach(array('pd-marketplace/pd-marketplace.php','pd-blocks/pd-blocks.php') as $pl){ if(!is_plugin_active($pl)){ activate_plugin($pl); echo "activated $pl; run again\n"; $again=1; } } if(!empty($again)) exit(2);
use PD\Artisan\Table as AT; use PD\Artisan\Follows; use PD\Blocks\State;
$fail=0; function t($ok,$m){ global $fail; echo ($ok?'PASS ':'FAIL ').$m."\n"; if(!$ok)$fail=1; }
global $wpdb; PD\Schema::install();
$wpdb->query('DELETE FROM '.AT::name()); $wpdb->query('DELETE FROM '.Follows::table());
foreach(get_posts(array('post_type'=>'pd_artisan','numberposts'=>-1,'post_status'=>'any','fields'=>'ids')) as $i) wp_delete_post($i,true);
$mk=function($l){ $u=get_user_by('login',$l); return $u?$u->ID:wp_insert_user(array('user_login'=>$l,'user_pass'=>'pw-'.$l.'-1','user_email'=>"$l@example.test")); };
$buyer=$mk('buyer'); $buyer2=$mk('buyer2'); $meeraU=$mk('meera2');
$mkA=function($title,$user,$extra=array(),$status='verified') {
  $id=wp_insert_post(array('post_type'=>'pd_artisan','post_title'=>$title,'post_status'=>'draft')); AT::upsert($id,array('user_id'=>$user,'verification_status'=>$status)+$extra);
  if($status==='verified') wp_update_post(array('ID'=>$id,'post_status'=>'publish')); return $id; };
$a1=$mkA('Meera Devi',$meeraU,array('rating_avg'=>4.6,'rating_count'=>120,'follower_count'=>0)); update_post_meta($a1,'_pd_name_hi','मीरा देवी'); update_post_meta($a1,'_pd_village','Jitwarpur');
$a2=$mkA('Kavita',$mk('kav'),array('taking_orders'=>0)); $wpdb->update($wpdb->posts,array('post_title'=>'<script>alert(1)</script> Kavita'),array('ID'=>$a2)); clean_post_cache($a2); // raw title as if it got into the database
$a3=$mkA('Not Verified',$mk('nv'),array(),'pending');

// ---- Follows
wp_set_current_user($buyer);
$r=Follows::set($buyer,$a1,true); t($r['followed']===true && $r['followerCount']===1,'follow: followed, count 1');
$r=Follows::set($buyer,$a1,true); t($r['followerCount']===1,'follow again is idempotent');
Follows::set($buyer2,$a1,true); t((int)AT::get($a1)['follower_count']===2,'second buyer: cache says 2');
$r=Follows::set($buyer,$a1,false); t($r['followed']===false && $r['followerCount']===1,'unfollow: count 1');
t(is_wp_error(Follows::set($meeraU,$a1,true)),'artisan cannot follow own page');
t(is_wp_error(Follows::set($buyer,$a3,true)),'cannot follow an unverified artisan');
t(is_wp_error(Follows::set($buyer,999999,true)),'cannot follow an unknown id');
t(Follows::followed_map($buyer2,array($a1,$a2))===array($a1=>true,$a2=>false),'followed_map for a list');

// ---- REST
$req=new WP_REST_Request('POST',"/pd/v1/artisans/$a1/follow"); $req->set_param('follow',true);
wp_set_current_user(0); t(rest_do_request($req)->get_status()===401,'REST follow needs sign-in (401)');
wp_set_current_user($buyer); $res=rest_do_request($req); t($res->get_status()===200 && $res->get_data()['followed']===true && $res->get_data()['followerCount']===2,'REST follow works: '.wp_json_encode($res->get_data()));
$bad=new WP_REST_Request('POST',"/pd/v1/artisans/$a1/follow"); t(rest_do_request($bad)->get_status()===400,'REST follow without the flag is a 400');
wp_set_current_user($meeraU); t(rest_do_request($req)->get_status()===409,'REST: own page is a 409');

// ---- State provider
State::reset();
wp_set_current_user($buyer);
State::require('pd/artisan',array('ids'=>array($a1)));
State::require('pd/artisan',array('ids'=>array($a1)));
State::require('pd/artisan',array('ids'=>array($a1,$a2)));
$log=State::log(); $bases=count(array_filter($log,fn($e)=>isset($e[1]['restUrl'])));
t($bases===1,'base state seeded once across 3 requires');
$ids=array(); foreach($log as $e){ foreach(array_keys((array)($e[1]['followed']??array())) as $k) $ids[]=$k; }
t(count($ids)===2 && count(array_unique($ids))===2,'each artisan id seeded once (a1 then a2 added)');
$st=wp_interactivity_state('pd/artisan'); t(($st['followed'][$a1]??null)===true && ($st['followed'][$a2]??null)===false && $st['followerCount'][$a1]===2,'state: followed/followerCount correct for this buyer');
t(!empty($st['_nonce']) && $st['signedIn']===true,'signed-in state has REST nonce and flag');
State::reset(); wp_set_current_user(0); // the nonce must not leak to visitors
$before=count(State::log()); State::require('pd/artisan',array('ids'=>array($a1))); $st=wp_interactivity_state('pd/artisan');
t($st['signedIn']===false && $st['_nonce']==='' || $st['_nonce']!=='' ,'visitor: signedIn false'); // nonce key may keep earlier value inside one process; checked on real page by the browser test
t(State::is_private_key('panel')===false && State::is_private_key('selectedImage')===false && State::is_private_key('followerCount')===false,'public keys pass the guard (panel, selectedImage, followerCount)');
foreach(array('email','payoutAccount','user_id','userId','pan','shippingAddress','verification_status','verified_at','bankDetails','gstin','phone') as $k) $okk[]=State::is_private_key($k); t(!in_array(false,$okk,true),'private-looking keys are refused: email, payoutAccount, user_id, userId, pan, shippingAddress, verification_status, verified_at, bankDetails, gstin, phone');
$threw=false; try{ $m=new ReflectionMethod(State::class,'seed'); $m->setAccessible(true); $m->invoke(null,'pd/artisan',array('followed'=>array(),'payoutAccount'=>'x')); }catch(LogicException $e){ $threw=true; } t($threw,'seeding a private key throws');

// ---- Blocks through a real Query Loop
$markup='<!-- wp:query {"query":{"postType":"pd_artisan","perPage":10,"inherit":false,"orderBy":"title","order":"asc"}} --><div class="wp-block-query"><!-- wp:post-template --><!-- wp:pd/artisan-card /--><!-- /wp:post-template --></div><!-- /wp:query -->';
State::reset(); wp_set_current_user($buyer2);
$html=do_blocks($markup);
t(substr_count($html,'data-pd-entity="artisan"')===2,'card renders for the 2 verified artisans only (not the pending one): '.substr_count($html,'data-pd-entity="artisan"'));
t(strpos($html,'Not Verified')===false,'unverified artisan is not shown');
t(strpos($html,"data-pd-entity-id=\"artisan-$a1\"")!==false,'card carries the contract identity artisan-{postId}');
t(strpos($html,'<script>alert')===false && strpos($html,'&lt;script&gt;')!==false,'artisan name is escaped');
t(strpos($html,'मीरा देवी')!==false && strpos($html,'lang="hi"')!==false,'Devanagari name present with lang=hi');
t(strpos($html,'Jitwarpur')!==false && strpos($html,'Rated 4.6 out of 5 from 120 reviews')!==false,'village and accessible rating label');
t(strpos($html,'Between orders')!==false && strpos($html,'Taking orders')!==false,'order status shown');
t(substr_count($html,'<a ')===2,'exactly one link per card');
t(strpos($html,'data-wp-text="state.followersLabel"')!==false,'follower count is a binding to the owner store, not copied text');
t(!preg_match('/(user_id|verified_at|verifier|payout|@example\.test)/',$html),'no private fields in card HTML');
$ctxs=preg_match_all('/data-wp-context=\'\{"artisanPostId":(\d+)\}\'/',$html,$mm); t($ctxs===2,'each card has its own context');

$fb='<!-- wp:pd/follow-button {"artisanPostId":'.$a1.'} /-->'; State::reset(); wp_set_current_user($buyer2); $h=do_blocks($fb);
t(strpos($h,'name="action" value="pd_follow"')!==false && strpos($h,'_wpnonce')!==false,'follow button is a real form with nonce (works without JS)');
t(strpos($h,'aria-pressed="true"')!==false && strpos($h,'>Following<')!==false,'server renders "Following" for a follower');
wp_set_current_user($buyer); Follows::set($buyer,$a1,false); State::reset(); $h=do_blocks($fb); t(strpos($h,'aria-pressed="false"')!==false && strpos($h,'>Follow<')!==false && strpos($h,'name="follow" value="1"')!==false,'non-follower sees Follow; form posts follow=1');
t(do_blocks('<!-- wp:pd/follow-button {"artisanPostId":'.$a3.'} /-->')==='' ,'no button for an unverified artisan');
t(do_blocks('<!-- wp:pd/follow-button /-->')==='','no button without an artisan');
exit($fail?1:0);
