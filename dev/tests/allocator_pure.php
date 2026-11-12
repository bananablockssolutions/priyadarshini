<?php
/** Pure-PHP checks of PD\Orders\Allocator. Needs no WordPress, so CI can run it. Run: php dev/tests/allocator_pure.php */
define( 'ABSPATH', __DIR__ . '/' );
require __DIR__ . '/../../wp-content/plugins/pd-marketplace/includes/Orders/Allocator.php';
use PD\Orders\Allocator as A;
$fail = 0; function t( $ok, $m ) { global $fail; echo ( $ok ? 'PASS ' : 'FAIL ' ) . $m . "\n"; if ( ! $ok ) { $fail = 1; } }

mt_srand( 3 ); $bad = 0;
for ( $n = 0; $n < 5000; $n++ ) { $w = array(); for ( $k = 0, $c = mt_rand( 1, 9 ); $k < $c; $k++ ) { $w[] = mt_rand( 1, 500000 ); } $amt = mt_rand( 0, array_sum( $w ) ); $s = A::spread( $amt, $w ); if ( array_sum( $s ) !== $amt || min( $s ) < 0 ) { $bad++; } }
t( 0 === $bad, 'spread: 5000 random cases sum exactly and are never negative' );

$bad = 0;
for ( $n = 0; $n < 3000; $n++ ) {
	$lines = array(); $arts = range( 1, mt_rand( 1, 4 ) ); $sub = 0; $tax = 0;
	for ( $k = 0, $nl = mt_rand( 1, 9 ); $k < $nl; $k++ ) { $s = mt_rand( 100, 2500000 ); $tx = intdiv( $s * mt_rand( 0, 18 ), 100 ); $lines[] = array( 'item_id' => $n * 100 + $k, 'product_id' => $k + 1, 'artisan_id' => $arts[ array_rand( $arts ) ], 'qty' => mt_rand( 1, 3 ), 'subtotal' => $s, 'tax' => $tx ); $sub += $s; $tax += $tx; }
	$disc = mt_rand( 0, 1 ) ? mt_rand( 0, $sub ) : 0; $ship = array(); $shipT = 0;
	foreach ( array_unique( array_column( $lines, 'artisan_id' ) ) as $a ) { $v = mt_rand( 0, 12000 ); $ship[ $a ] = $v; $shipT += $v; }
	if ( A::reconcile( A::allocate( $lines, $ship, $disc, mt_rand( 0, 3000 ) ), $sub, $disc, $shipT, $tax ) ) { $bad++; }
}
t( 0 === $bad, '3000 random multi-artisan orders reconcile to the paisa' );

$al = A::allocate( array(
	array( 'item_id' => 1, 'product_id' => 10, 'artisan_id' => 184, 'qty' => 1, 'subtotal' => 240000, 'tax' => 12000 ),
	array( 'item_id' => 2, 'product_id' => 11, 'artisan_id' => 185, 'qty' => 2, 'subtotal' => 160000, 'tax' => 8000 ),
	array( 'item_id' => 3, 'product_id' => 12, 'artisan_id' => 184, 'qty' => 1, 'subtotal' => 100000, 'tax' => 5000 ) ), array( 184 => 6000, 185 => 4500 ), 20000, 1000 );
t( 326400 === $al[184]['items_subtotal'] && 32640 === $al[184]['commission'], 'worked example: artisan 184 items 3264.00, commission 326.40' );
t( 326400 + 6000 - 32640 === $al[184]['artisan_payable'] && array() === A::reconcile( $al, 500000, 20000, 10500, 25000 ), 'worked example: payable and reconciliation' );

$bad = 0;
for ( $n = 0; $n < 2000; $n++ ) { $net = mt_rand( 100, 2000000 ); $comm = intdiv( $net * mt_rand( 0, 3000 ) + 5000, 10000 ); $left = $net; $back = 0; $done = 0; while ( $left > 0 ) { $r = mt_rand( 1, $left ); $back += A::refund_commission( $net, $comm, $done, $r ); $done += $r; $left -= $r; } if ( $back !== $comm ) { $bad++; } }
t( 0 === $bad, '2000 random partial-refund sequences return exactly the whole commission' );
$thrown = false; try { A::refund_commission( 1000, 100, 900, 200 ); } catch ( \InvalidArgumentException $e ) { $thrown = true; } t( $thrown, 'refund above the item is refused' );
exit( $fail );
