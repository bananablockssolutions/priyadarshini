<?php
/** Order allocation arithmetic and storage (SRS DB-08..DB-10, 3.5.4). */
// SAFETY: these tests DELETE data (artisans, reviews, allocations, and test products/comments). Run only on a throwaway site.
if ( getenv( 'PD_TESTS_WIPE' ) !== 'yes' ) { fwrite( STDERR, "Refusing to run: this test deletes data. Use a throwaway site and set PD_TESTS_WIPE=yes.\n" ); exit( 3 ); }
$_SERVER['HTTP_HOST']='127.0.0.1:8088'; $_SERVER['REQUEST_URI']='/'; $_SERVER['SERVER_NAME']='127.0.0.1';
if ( ! function_exists( 'add_action' ) ) { require rtrim( getenv( 'WP_ROOT' ) ?: '/home/user/pd-dev/wp', '/' ) . '/wp-load.php'; } // already loaded under `wp eval-file`
use PD\Orders\Allocator as A; use PD\Orders\Store; use PD\Orders\Tables;
$fail=0; function t($ok,$m){ global $fail; echo ($ok?'PASS ':'FAIL ').$m."\n"; if(!$ok)$fail=1; }

// 1. spread: exact sum, proportional, no negative
mt_srand(3); $bad=0; for($n=0;$n<5000;$n++){ $w=array(); for($k=0,$c=mt_rand(1,9);$k<$c;$k++) $w[]=mt_rand(1,500000); $amt=mt_rand(0,array_sum($w)); $s=A::spread($amt,$w); if(array_sum($s)!==$amt||min($s)<0||array_sum(array_map(fn($a,$b)=>$a>$b?1:0,$s,$w))) $bad++; }
t($bad===0,'spread: 5000 random cases sum exactly, none negative, none above its line');

// 2. random orders reconcile to the paisa
$bad=0; $first=null; for($n=0;$n<3000;$n++){
  $lines=array(); $nl=mt_rand(1,9); $arts=range(1,mt_rand(1,4)); $sub=0; $tax=0;
  for($k=0;$k<$nl;$k++){ $s=mt_rand(100,2500000); $tx=intdiv($s*mt_rand(0,18),100); $lines[]=array('item_id'=>$n*100+$k,'product_id'=>$k+1,'artisan_id'=>$arts[array_rand($arts)],'qty'=>mt_rand(1,3),'subtotal'=>$s,'tax'=>$tx); $sub+=$s; $tax+=$tx; }
  $disc=mt_rand(0,1)?mt_rand(0,$sub):0; $present=array_unique(array_column($lines,'artisan_id')); $ship=array(); $shipT=0; foreach($present as $a){ $v=mt_rand(0,12000); $ship[$a]=$v; $shipT+=$v; }
  $al=A::allocate($lines,$ship,$disc,mt_rand(0,3000)); $p=A::reconcile($al,$sub,$disc,$shipT,$tax); if($p){ $bad++; $first??=array($p,$lines,$disc); } }
t($bad===0,'3000 random multi-artisan orders reconcile to the paisa (items after discount, shipping, tax, commission, payable)');

// 3. a worked example anyone can check by hand
$al=A::allocate(array(
 array('item_id'=>1,'product_id'=>10,'artisan_id'=>184,'qty'=>1,'subtotal'=>240000,'tax'=>12000),
 array('item_id'=>2,'product_id'=>11,'artisan_id'=>185,'qty'=>2,'subtotal'=>160000,'tax'=>8000),
 array('item_id'=>3,'product_id'=>12,'artisan_id'=>184,'qty'=>1,'subtotal'=>100000,'tax'=>5000)),
 array(184=>6000,185=>4500), 20000, 1000);
// discount 20000 over 240000/160000/100000 (total 500000) = 9600/6400/4000
t($al[184]['items_subtotal']===(240000-9600)+(100000-4000) && $al[184]['items_subtotal']===326400,'worked example: artisan 184 items 3264.00');
t($al[184]['commission']===23040+9600 && $al[184]['commission']===32640,'worked example: 10% commission 326.40');
t($al[184]['artisan_payable']===326400+6000-32640 && $al[185]['artisan_payable']===153600+4500-15360,'worked example: payable = items + shipping - commission');
t(A::reconcile($al,500000,20000,10500,25000)===array(),'worked example reconciles');

// 4. refunds take back exactly the commission, however they are split
$bad=0; for($n=0;$n<2000;$n++){ $net=mt_rand(100,2000000); $comm=intdiv($net*mt_rand(0,3000)+5000,10000); $left=$net; $back=0; $done=0; while($left>0){ $r=mt_rand(1,$left); $back+=A::refund_commission($net,$comm,$done,$r); $done+=$r; $left-=$r; } if($back!==$comm) $bad++; }
t($bad===0,'2000 random partial-refund sequences return exactly the whole commission');
$thrown=false; try{ A::refund_commission(1000,100,900,200); }catch(\InvalidArgumentException $e){ $thrown=true; } t($thrown,'refund above the item is refused');
$thrown=false; try{ A::allocate(array(array('item_id'=>1,'product_id'=>1,'artisan_id'=>1,'qty'=>1,'subtotal'=>100,'tax'=>0)),array(2=>50),0,1000); }catch(\InvalidArgumentException $e){ $thrown=true; } t($thrown,'shipping for an artisan with no items is refused');

// 5. storage, idempotent
PD\Schema::install(); global $wpdb;
foreach([Tables::allocations(),Tables::items(),Tables::shipments()] as $tb) $wpdb->query("DELETE FROM $tb");
$ids=Store::save(7001,$al); $again=Store::save(7001,$al);
t(count($ids)===2 && $ids===$again,'saved 2 allocations for the order; saving again changes nothing');
t((int)$wpdb->get_var('SELECT COUNT(*) FROM '.Tables::allocations())===2 && (int)$wpdb->get_var('SELECT COUNT(*) FROM '.Tables::items())===3,'2 allocations and 3 allocation items stored');
$sum=$wpdb->get_row('SELECT SUM(items_subtotal) s, SUM(shipping_amount) sh, SUM(tax_amount) tx FROM '.Tables::allocations(),ARRAY_A);
t((int)$sum['s']===480000 && (int)$sum['sh']===10500 && (int)$sum['tx']===25000,'stored totals match the order: items 4800.00, shipping 105.00, tax 250.00');
t($wpdb->get_col('SHOW COLUMNS FROM '.Tables::allocations())&&!in_array('invoice_no',array_map(fn($r)=>$r,$wpdb->get_col('SHOW COLUMNS FROM '.Tables::allocations())),true),'no invoice number on allocations (one invoice per order, CHK-08)');
// a failing save rolls back completely
$broken=$al; $broken[999]=array('artisan_id'=>999,'items'=>array(array('item_id'=>1,'product_id'=>1,'qty'=>1,'line_subtotal'=>1,'discount_share'=>0,'tax'=>0,'commission'=>0)),'items_subtotal'=>1,'commission'=>0,'shipping_amount'=>0,'tax_amount'=>0,'artisan_payable'=>1); // item_id 1 already used by order 7001
$wpdb->suppress_errors(true); $threw=false; try{ Store::save(7002,$broken); }catch(\Throwable $e){ $threw=true; } $wpdb->suppress_errors(false);
t($threw && (int)$wpdb->get_var('SELECT COUNT(*) FROM '.Tables::allocations().' WHERE wc_order_id=7002')===0,'a failed save rolls back: no half-saved order');
exit($fail?1:0);
