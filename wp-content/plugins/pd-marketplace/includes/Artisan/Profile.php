<?php
namespace PD\Artisan;

defined( 'ABSPATH' ) || exit;

/**
 * Public profile of an artisan for the Artists list and the artisan page (owner sketch 5, 2026-11-20):
 * portrait (featured image), name and Devanagari name, a short line (excerpt), bio (content), quote, village,
 * awards (medals), artforms (the crafts ticked under Products > Categories) and a rank the team sets by hand ("#1").
 * Only public fields (DB-12). Edited in the "Priyadarshini: profile" box on the artisan screen.
 */
final class Profile {

	public const RANK   = '_pd_rank';
	public const AWARDS = '_pd_awards';
	public const QUOTE  = '_pd_quote';

	public static function register_hooks(): void {
		add_action( 'init', array( self::class, 'register' ), 21 );
		add_action( 'add_meta_boxes_' . PostType::NAME, array( self::class, 'add_box' ) );
		add_action( 'save_post_' . PostType::NAME, array( self::class, 'save' ), 10, 2 );
	}

	public static function register(): void {
		$auth = static fn() => current_user_can( 'edit_pd_artisans' );
		register_post_meta( PostType::NAME, self::RANK, array( 'single' => true, 'type' => 'integer', 'show_in_rest' => true, 'auth_callback' => $auth, 'default' => 0 ) );
		register_post_meta( PostType::NAME, self::QUOTE, array( 'single' => true, 'type' => 'string', 'show_in_rest' => true, 'auth_callback' => $auth ) );
		register_post_meta(
			PostType::NAME,
			self::AWARDS,
			array(
				'single'        => true,
				'type'          => 'array',
				'auth_callback' => $auth,
				'show_in_rest'  => array( 'schema' => array( 'type' => 'array', 'items' => array( 'type' => 'string' ) ) ),
				'default'       => array(),
			)
		);
	}

	/** @return array<string,mixed>|null Public profile of a published, verified artisan. */
	public static function get( int $id ): ?array {
		$post = get_post( $id );
		$ops  = Table::public_view( $id );
		if ( ! $post || PostType::NAME !== $post->post_type || 'publish' !== $post->post_status || ! $ops ) {
			return null;
		}
		$terms = taxonomy_exists( 'product_cat' ) ? get_the_terms( $id, 'product_cat' ) : array();
		$forms = array();
		foreach ( is_array( $terms ) ? $terms : array() as $t ) {
			$forms[] = array( 'termId' => (int) $t->term_id, 'name' => $t->name ) + ( class_exists( '\PD\Crafts\Meta' ) ? \PD\Crafts\Meta::get( $t ) : array() );
		}
		$name_hi = (string) get_post_meta( $id, '_pd_name_hi', true );
		return array(
			'id'        => $id,
			'name'      => get_the_title( $post ),
			'first'     => strtok( get_the_title( $post ), ' ' ) ?: get_the_title( $post ),
			'nameHi'    => $name_hi,
			'initial'   => \PD\Works\View::initial( $name_hi, get_the_title( $post ) ),
			'url'       => get_permalink( $post ),
			'line'      => has_excerpt( $post ) ? get_the_excerpt( $post ) : '',
			'imageId'   => (int) get_post_thumbnail_id( $post ),
			'village'   => (string) get_post_meta( $id, '_pd_village', true ),
			'quote'     => (string) get_post_meta( $id, self::QUOTE, true ),
			'rank'      => (int) get_post_meta( $id, self::RANK, true ),
			'awards'    => self::awards( $id ),
			'forms'     => $forms,
			'accent'    => (string) $ops['accent'],
			'motif'     => (string) $ops['motif'],
			'followers' => (int) $ops['follower_count'],
			'verified'  => 'verified' === $ops['verification_status'],
		);
	}

	/** @return string[] One line per award, for example "National Award 2019". */
	public static function awards( int $id ): array {
		$v = get_post_meta( $id, self::AWARDS, true );
		return array_values( array_filter( array_map( 'strval', is_array( $v ) ? $v : array() ) ) );
	}

	/**
	 * Published artisans for the Artists list: ranked ones first (1, 2, 3...), then the rest by name.
	 * @return int[]
	 */
	public static function ranked_ids( int $limit = 200 ): array {
		$ids = get_posts( array( 'post_type' => PostType::NAME, 'post_status' => 'publish', 'numberposts' => $limit, 'fields' => 'ids', 'orderby' => 'title', 'order' => 'ASC', 'no_found_rows' => true ) );
		$rank = array();
		foreach ( $ids as $id ) {
			$r           = (int) get_post_meta( $id, self::RANK, true );
			$rank[ $id ] = $r > 0 ? $r : PHP_INT_MAX;
		}
		$pos = array_flip( $ids );
		usort( $ids, static fn( $a, $b ) => array( $rank[ $a ], $pos[ $a ] ) <=> array( $rank[ $b ], $pos[ $b ] ) );
		return array_map( 'intval', $ids );
	}

	public static function set( int $id, array $f ): void {
		if ( array_key_exists( 'rank', $f ) ) {
			update_post_meta( $id, self::RANK, max( 0, (int) $f['rank'] ) );
		}
		if ( array_key_exists( 'quote', $f ) ) {
			update_post_meta( $id, self::QUOTE, sanitize_text_field( (string) $f['quote'] ) );
		}
		if ( array_key_exists( 'awards', $f ) ) {
			$lines = is_array( $f['awards'] ) ? $f['awards'] : preg_split( '/\R/', (string) $f['awards'] );
			update_post_meta( $id, self::AWARDS, array_slice( array_values( array_filter( array_map( 'sanitize_text_field', $lines ) ) ), 0, 12 ) );
		}
		foreach ( array( 'nameHi' => '_pd_name_hi', 'village' => '_pd_village' ) as $k => $key ) {
			if ( array_key_exists( $k, $f ) ) {
				update_post_meta( $id, $key, sanitize_text_field( (string) $f[ $k ] ) );
			}
		}
	}

	public static function add_box(): void {
		add_meta_box( 'pd-artisan-profile', __( 'Priyadarshini: profile', 'pd-marketplace' ), array( self::class, 'box' ), PostType::NAME, 'normal', 'high' );
	}

	public static function box( \WP_Post $post ): void {
		wp_nonce_field( 'pd_profile_' . $post->ID, '_pd_profile_nonce' );
		$v = static fn( string $k ) => esc_attr( (string) get_post_meta( $post->ID, $k, true ) );
		?>
		<p><?php esc_html_e( 'The portrait is the Featured image (square works best). The short line under the name on the Artists list is the Excerpt; the bio is the main text. Artforms are the crafts ticked under Product categories.', 'pd-marketplace' ); ?></p>
		<table class="form-table" role="presentation">
			<tr><th><label for="pd-name-hi"><?php esc_html_e( 'Name in Devanagari', 'pd-marketplace' ); ?></label></th><td><input id="pd-name-hi" name="pd_name_hi" lang="hi" class="regular-text" value="<?php echo $v( '_pd_name_hi' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>"></td></tr>
			<tr><th><label for="pd-village"><?php esc_html_e( 'Village and district', 'pd-marketplace' ); ?></label></th><td><input id="pd-village" name="pd_village" class="regular-text" value="<?php echo $v( '_pd_village' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>" placeholder="Jitwarpur, Madhubani"></td></tr>
			<tr><th><label for="pd-rank"><?php esc_html_e( 'Rank on the Artists list', 'pd-marketplace' ); ?></label></th><td><input id="pd-rank" name="pd_rank" type="number" min="0" step="1" style="width:6em" value="<?php echo $v( self::RANK ) ?: '0'; // phpcs:ignore WordPress.Security.EscapeOutput ?>"> <span class="description"><?php esc_html_e( 'Shown as a ribbon "#1", "#2"... 0 means no ribbon; unranked artisans follow the ranked ones.', 'pd-marketplace' ); ?></span></td></tr>
			<tr><th><label for="pd-awards"><?php esc_html_e( 'Awards', 'pd-marketplace' ); ?></label></th><td><textarea id="pd-awards" name="pd_awards" rows="3" class="large-text" placeholder="National Award 2019&#10;State Award 2014"><?php echo esc_textarea( implode( "\n", self::awards( $post->ID ) ) ); ?></textarea><p class="description"><?php esc_html_e( 'One per line. Each shows as a medal.', 'pd-marketplace' ); ?></p></td></tr>
			<tr><th><label for="pd-quote"><?php esc_html_e( 'Quote', 'pd-marketplace' ); ?></label></th><td><input id="pd-quote" name="pd_quote" class="large-text" value="<?php echo $v( self::QUOTE ); // phpcs:ignore WordPress.Security.EscapeOutput ?>" placeholder="Every line is drawn with a bamboo pen."></td></tr>
		</table>
		<?php
	}

	public static function save( int $id, \WP_Post $post ): void {
		if ( ! isset( $_POST['_pd_profile_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['_pd_profile_nonce'] ) ), 'pd_profile_' . $id ) || ! current_user_can( 'edit_post', $id ) || wp_is_post_revision( $id ) ) {
			return;
		}
		$p = wp_unslash( $_POST );
		self::set(
			$id,
			array(
				'nameHi'  => (string) ( $p['pd_name_hi'] ?? '' ),
				'village' => (string) ( $p['pd_village'] ?? '' ),
				'rank'    => absint( $p['pd_rank'] ?? 0 ),
				'awards'  => (string) ( $p['pd_awards'] ?? '' ),
				'quote'   => (string) ( $p['pd_quote'] ?? '' ),
			)
		);
	}
}
