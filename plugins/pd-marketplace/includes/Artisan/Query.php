<?php
namespace PD\Artisan;

defined( 'ABSPATH' ) || exit;

/**
 * Lets core WP_Query / core/query filter and sort pd_artisan posts by operational columns (M-1 spike 1).
 * Query args: pd_taking_orders (1), pd_orderby (rating|sales|followers|recent), pd_verified (1).
 */
final class Query {

	private const SORTS = array(
		'rating'    => 'pda.rating_avg DESC, pda.rating_count DESC',
		'sales'     => 'pda.sales_count DESC',
		'followers' => 'pda.follower_count DESC',
		'recent'    => 'pda.created_at DESC',
	);

	public static function register_hooks(): void {
		add_filter( 'posts_clauses', array( self::class, 'clauses' ), 10, 2 );
		add_filter( 'wp_insert_post_data', array( self::class, 'enforce_publication_rule' ), 10, 2 );
	}

	public static function clauses( array $c, \WP_Query $q ): array {
		if ( PostType::NAME !== $q->get( 'post_type' ) ) {
			return $c;
		}
		$orderby = (string) $q->get( 'pd_orderby' );
		$taking  = $q->get( 'pd_taking_orders' );
		$verif   = $q->get( 'pd_verified' );
		if ( '' === $orderby && '' === $taking && '' === $verif ) {
			return $c;
		}
		$t        = Table::name();
		$c['join'] .= " INNER JOIN {$t} pda ON pda.post_id = {$GLOBALS['wpdb']->posts}.ID";
		if ( '' !== $taking ) {
			$c['where'] .= ' AND pda.taking_orders = ' . ( $taking ? 1 : 0 );
		}
		if ( '' !== $verif ) {
			$c['where'] .= " AND pda.verification_status = 'verified'";
		}
		if ( isset( self::SORTS[ $orderby ] ) ) {
			$c['orderby'] = self::SORTS[ $orderby ] . ", {$GLOBALS['wpdb']->posts}.ID ASC";
		}
		return $c;
	}

	/** DB-13: an artisan post is published only while verified; otherwise it is held as draft. */
	public static function enforce_publication_rule( array $data, array $postarr ): array {
		if ( PostType::NAME !== ( $data['post_type'] ?? '' ) || 'publish' !== ( $data['post_status'] ?? '' ) ) {
			return $data;
		}
		$id  = (int) ( $postarr['ID'] ?? 0 );
		$row = $id ? Table::get( $id ) : null;
		if ( ! $row || 'verified' !== $row['verification_status'] ) {
			$data['post_status'] = 'draft';
		}
		return $data;
	}
}
