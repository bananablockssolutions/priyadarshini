<?php
namespace PD\Reviews;

use PD\Artisan\Table as ArtisanTable;

defined( 'ABSPATH' ) || exit;

/**
 * One interface over two stores (SRS CNT-30..38, DB-14).
 * - Product reviews: WooCommerce comments of type `review` (rating in commentmeta `rating`).
 * - Artisan reviews and replies: our tables.
 * Lists are read from the derived index `pd_review_index` and hydrated from the source stores.
 * Scopes are never mixed in one list.
 */
final class Repository {

	public const PER_PAGE      = 10;
	public const EDIT_WINDOW   = 7 * DAY_IN_SECONDS;
	private const SORTS        = array(
		'recent'  => 'i.created_at DESC, i.typed_id DESC',
		'highest' => 'i.rating DESC, i.created_at DESC, i.typed_id DESC',
		'lowest'  => 'i.rating ASC, i.created_at DESC, i.typed_id DESC',
		'photos'  => 'i.has_photos DESC, i.created_at DESC, i.typed_id DESC',
	);

	/* ------------------------------------------------------------ write: artisan reviews */

	/** @return int|\WP_Error new review id */
	public static function add_artisan_review( int $artisan_id, int $user_id, int $allocation_id, int $rating, string $body, array $opts = array() ) {
		global $wpdb;
		if ( $rating < 1 || $rating > 5 ) {
			return new \WP_Error( 'pd_rating', 'Rating must be 1 to 5.' );
		}
		if ( ! ArtisanTable::get( $artisan_id ) ) {
			return new \WP_Error( 'pd_artisan', 'Unknown artisan.' );
		}
		$quiet = $wpdb->suppress_errors( true ); // the unique key is the rule; a refusal is expected, not an error
		$ok = $wpdb->insert(
			Tables::reviews(),
			array(
				'artisan_id'    => $artisan_id,
				'user_id'       => $user_id,
				'allocation_id' => $allocation_id,
				'rating'        => $rating,
				'body'          => wp_kses( $body, array() ),
				'has_photos'    => empty( $opts['has_photos'] ) ? 0 : 1,
				'custom_piece'  => empty( $opts['custom_piece'] ) ? 0 : 1,
				'language'      => $opts['language'] ?? 'en',
				'created_at'    => $opts['created_at'] ?? current_time( 'mysql', true ),
			)
		);
		$wpdb->suppress_errors( $quiet );
		if ( ! $ok ) {
			return new \WP_Error( 'pd_duplicate', 'This order has already been reviewed for this artisan.' );
		}
		$id = (int) $wpdb->insert_id;
		self::index_artisan_review( $id );
		self::recompute_rating( $artisan_id );
		return $id;
	}

	/** One reply per review, by the artisan who owns it. @return int|\WP_Error */
	public static function reply( int $review_id, int $acting_user_id, string $body ) {
		global $wpdb;
		$rev = $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . Tables::reviews() . ' WHERE id=%d', $review_id ), ARRAY_A );
		if ( ! $rev ) {
			return new \WP_Error( 'pd_missing', 'No such review.' );
		}
		$owner = ArtisanTable::get( (int) $rev['artisan_id'] );
		if ( ! $owner || (int) $owner['user_id'] !== $acting_user_id ) {
			return new \WP_Error( 'pd_forbidden', 'Only the reviewed artisan can reply.' );
		}
		$body = trim( wp_kses( $body, array() ) );
		if ( '' === $body ) {
			return new \WP_Error( 'pd_empty', 'Reply is empty.' );
		}
		$quiet = $wpdb->suppress_errors( true );
		$ok = $wpdb->insert( Tables::replies(), array( 'review_id' => $review_id, 'artisan_id' => (int) $rev['artisan_id'], 'body' => $body, 'created_at' => current_time( 'mysql', true ) ) );
		$wpdb->suppress_errors( $quiet );
		if ( ! $ok ) {
			return new \WP_Error( 'pd_already_replied', 'This review already has a reply.' );
		}
		self::update_reply_rate( (int) $rev['artisan_id'] );
		return (int) $wpdb->insert_id;
	}

	/** Edit own reply within 7 days. @return true|\WP_Error */
	public static function edit_reply( int $review_id, int $acting_user_id, string $body, ?int $now = null ) {
		global $wpdb;
		$row = $wpdb->get_row( $wpdb->prepare( 'SELECT p.*, a.user_id AS owner FROM ' . Tables::replies() . ' p JOIN ' . ArtisanTable::name() . ' a ON a.post_id = p.artisan_id WHERE p.review_id=%d', $review_id ), ARRAY_A );
		if ( ! $row || (int) $row['owner'] !== $acting_user_id ) {
			return new \WP_Error( 'pd_forbidden', 'No reply of yours on this review.' );
		}
		$now = $now ?? time();
		if ( $now - strtotime( $row['created_at'] . ' UTC' ) > self::EDIT_WINDOW ) {
			return new \WP_Error( 'pd_locked', 'Replies can be edited for 7 days.' );
		}
		$wpdb->update( Tables::replies(), array( 'body' => trim( wp_kses( $body, array() ) ), 'edited_at' => gmdate( 'Y-m-d H:i:s', $now ) ), array( 'review_id' => $review_id ) );
		return true;
	}

	/* ------------------------------------------------------------ index */

	public static function index_artisan_review( int $id ): void {
		global $wpdb;
		$r = $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . Tables::reviews() . ' WHERE id=%d', $id ), ARRAY_A );
		$typed = Review::typed_id( 'artisan', $id );
		if ( ! $r || 'published' !== $r['state'] ) {
			$wpdb->delete( Tables::index(), array( 'typed_id' => $typed ) );
			return;
		}
		$wpdb->replace( Tables::index(), array(
			'typed_id' => $typed, 'type' => 'artisan', 'source_id' => $id, 'artisan_id' => (int) $r['artisan_id'], 'product_id' => 0,
			'rating' => (int) $r['rating'], 'has_photos' => (int) $r['has_photos'], 'language' => $r['language'], 'state' => 'published', 'created_at' => $r['created_at'],
		) );
	}

	public static function index_product_review( int $comment_id ): void {
		global $wpdb;
		$c     = get_comment( $comment_id );
		$typed = Review::typed_id( 'product', $comment_id );
		if ( ! $c || 'review' !== $c->comment_type || '1' !== (string) $c->comment_approved ) {
			$wpdb->delete( Tables::index(), array( 'typed_id' => $typed ) );
			return;
		}
		$artisan = (int) get_post_meta( (int) $c->comment_post_ID, '_pd_artisan_post_id', true );
		$rating  = (int) get_comment_meta( $comment_id, 'rating', true );
		if ( ! $artisan || $rating < 1 ) {
			$wpdb->delete( Tables::index(), array( 'typed_id' => $typed ) );
			return;
		}
		$wpdb->replace( Tables::index(), array(
			'typed_id' => $typed, 'type' => 'product', 'source_id' => $comment_id, 'artisan_id' => $artisan, 'product_id' => (int) $c->comment_post_ID,
			'rating' => $rating, 'has_photos' => (int) (bool) get_comment_meta( $comment_id, '_pd_has_photos', true ), 'language' => 'en',
			'state' => 'published', 'created_at' => get_gmt_from_date( $c->comment_date ),
		) );
	}

	/** Rebuild the whole index (or one artisan's) from the sources. Safe to run any time. */
	public static function reindex( ?int $artisan_id = null ): int {
		global $wpdb;
		$where = $artisan_id ? $wpdb->prepare( ' WHERE artisan_id=%d', $artisan_id ) : '';
		$wpdb->query( 'DELETE FROM ' . Tables::index() . $where );
		$n = 0;
		foreach ( $wpdb->get_col( 'SELECT id FROM ' . Tables::reviews() . $where ) as $id ) {
			self::index_artisan_review( (int) $id );
			$n++;
		}
		$args = array( 'type' => 'review', 'status' => 'approve', 'fields' => 'ids', 'number' => 0 );
		foreach ( get_comments( $args ) as $cid ) {
			if ( $artisan_id && (int) get_post_meta( (int) get_comment( $cid )->comment_post_ID, '_pd_artisan_post_id', true ) !== $artisan_id ) {
				continue;
			}
			self::index_product_review( (int) $cid );
			$n++;
		}
		return $n;
	}

	/** Rating cache on the artisan row comes from ARTISAN reviews only (REV rules). */
	public static function recompute_rating( int $artisan_id ): void {
		global $wpdb;
		$row = $wpdb->get_row( $wpdb->prepare( "SELECT COUNT(*) n, AVG(rating) a FROM " . Tables::reviews() . " WHERE artisan_id=%d AND state='published'", $artisan_id ), ARRAY_A );
		ArtisanTable::upsert( $artisan_id, array( 'rating_count' => (int) $row['n'], 'rating_avg' => round( (float) $row['a'], 2 ) ) );
	}

	public static function update_reply_rate( int $artisan_id ): void {
		global $wpdb;
		$n = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM " . Tables::reviews() . " WHERE artisan_id=%d AND state='published'", $artisan_id ) );
		$r = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM " . Tables::replies() . " WHERE artisan_id=%d AND state='published'", $artisan_id ) );
		ArtisanTable::upsert( $artisan_id, array( 'reply_rate' => $n ? (int) round( 100 * $r / $n ) : 0 ) );
	}

	/* ------------------------------------------------------------ read */

	/**
	 * @param array{scope?:string,artisan_id?:int,product_id?:int,sort?:string,rating?:int,photos?:bool,page?:int,per_page?:int} $a
	 * @return array{items:Review[],total:int,pages:int,page:int}
	 */
	public static function list( array $a ): array {
		global $wpdb;
		[ $where, $sort, $per, $page ] = self::criteria( $a );
		$i     = Tables::index();
		$total = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$i} i WHERE {$where}" );
		$pages = max( 1, (int) ceil( $total / $per ) );
		$page  = min( $page, $pages );
		$ids   = $wpdb->get_col( "SELECT i.typed_id FROM {$i} i WHERE {$where} ORDER BY {$sort} LIMIT " . ( ( $page - 1 ) * $per ) . ", {$per}" );
		return array( 'items' => self::hydrate( $ids ), 'total' => $total, 'pages' => $pages, 'page' => $page );
	}

	/** Page number on which a typed review appears under the given list criteria, or null (deep link ?review=). */
	public static function page_of( string $typed_id, array $a ): ?int {
		global $wpdb;
		[ $where, $sort, $per ] = self::criteria( $a );
		$i   = Tables::index();
		$ids = $wpdb->get_col( "SELECT i.typed_id FROM {$i} i WHERE {$where} ORDER BY {$sort}" ); // fine below the keyset threshold (10,000)
		$pos = array_search( $typed_id, $ids, true );
		return false === $pos ? null : (int) floor( $pos / $per ) + 1;
	}

	private static function criteria( array $a ): array {
		global $wpdb;
		$scope = ( $a['scope'] ?? 'artisan' ) === 'product' ? 'product' : 'artisan';
		$w     = array( $wpdb->prepare( 'i.type=%s', $scope ), "i.state='published'" );
		if ( ! empty( $a['artisan_id'] ) ) { $w[] = $wpdb->prepare( 'i.artisan_id=%d', $a['artisan_id'] ); }
		if ( ! empty( $a['product_id'] ) ) { $w[] = $wpdb->prepare( 'i.product_id=%d', $a['product_id'] ); }
		if ( ! empty( $a['rating'] ) )     { $w[] = $wpdb->prepare( 'i.rating=%d', $a['rating'] ); }
		if ( ! empty( $a['photos'] ) )     { $w[] = 'i.has_photos=1'; }
		$sort = self::SORTS[ $a['sort'] ?? 'recent' ] ?? self::SORTS['recent'];
		$per  = max( 1, min( 50, (int) ( $a['per_page'] ?? self::PER_PAGE ) ) );
		$page = max( 1, (int) ( $a['page'] ?? 1 ) );
		return array( implode( ' AND ', $w ), $sort, $per, $page );
	}

	/** @param string[] $typed_ids  @return Review[] in the same order */
	private static function hydrate( array $typed_ids ): array {
		global $wpdb;
		$art = $prod = array();
		foreach ( $typed_ids as $t ) {
			[ $type, $id ] = Review::parse_id( $t );
			'artisan' === $type ? $art[] = $id : $prod[] = $id;
		}
		$by = array();
		if ( $art ) {
			$in   = implode( ',', array_map( 'intval', $art ) );
			$rows = $wpdb->get_results( 'SELECT r.*, p.body AS reply_body, p.created_at AS reply_at, p.edited_at AS reply_edited FROM ' . Tables::reviews() . ' r LEFT JOIN ' . Tables::replies() . " p ON p.review_id=r.id AND p.state='published' WHERE r.id IN ({$in})", ARRAY_A );
			foreach ( $rows as $r ) {
				$by[ Review::typed_id( 'artisan', (int) $r['id'] ) ] = new Review(
					Review::typed_id( 'artisan', (int) $r['id'] ), 'artisan', (int) $r['id'], (int) $r['artisan_id'], 0, (int) $r['rating'], $r['body'],
					(bool) $r['has_photos'], (bool) $r['custom_piece'], self::author( (int) $r['user_id'] ), $r['created_at'],
					null === $r['reply_body'] ? null : array( 'body' => $r['reply_body'], 'created_at' => $r['reply_at'], 'edited_at' => $r['reply_edited'] )
				);
			}
		}
		foreach ( $prod ? get_comments( array( 'comment__in' => $prod, 'type' => 'review', 'status' => 'approve' ) ) : array() as $c ) {
			$cid = (int) $c->comment_ID;
			$by[ Review::typed_id( 'product', $cid ) ] = new Review(
				Review::typed_id( 'product', $cid ), 'product', $cid, (int) get_post_meta( (int) $c->comment_post_ID, '_pd_artisan_post_id', true ), (int) $c->comment_post_ID,
				(int) get_comment_meta( $cid, 'rating', true ), wp_strip_all_tags( $c->comment_content ), (bool) get_comment_meta( $cid, '_pd_has_photos', true ), false,
				self::author( (int) $c->user_id, $c->comment_author ), get_gmt_from_date( $c->comment_date ), null
			);
		}
		$out = array();
		foreach ( $typed_ids as $t ) { if ( isset( $by[ $t ] ) ) { $out[] = $by[ $t ]; } }
		return $out;
	}

	/** "Anita K." from the profile. Never the email or login. */
	private static function author( int $user_id, string $fallback = '' ): string {
		$u    = $user_id ? get_userdata( $user_id ) : null;
		$name = $u ? trim( ( get_user_meta( $user_id, 'first_name', true ) . ' ' . get_user_meta( $user_id, 'last_name', true ) ) ) : $fallback;
		$name = $name ?: ( $u ? $u->display_name : $fallback );
		$name = trim( preg_replace( '/\s+/', ' ', (string) $name ) );
		if ( false !== strpos( $name, '@' ) || '' === $name ) { return 'A buyer'; }
		$parts = explode( ' ', $name );
		return count( $parts ) > 1 ? $parts[0] . ' ' . mb_substr( end( $parts ), 0, 1 ) . '.' : $parts[0];
	}
}
