<?php
namespace PD\Works;

use PD\Artisan\PostType;

defined( 'ABSPATH' ) || exit;

/**
 * A work is a WooCommerce product tied to one artisan (SRS DB-14, BUY-01, BUY-09, DB-15).
 * Public meta only. The admin box on the product screen sets the maker, buying format and Devanagari title.
 */
final class Meta {

	public const ARTISAN   = '_pd_artisan_post_id';
	public const TITLE_HI  = '_pd_title_hi';
	public const NUMBER    = '_pd_catalogue_no';
	public const FORMAT    = '_pd_format';          // buy | buy_commission | commission (BUY-01)
	public const ON_ORDER  = '_pd_made_on_order';   // '1' when the piece is made after the order (BUY-09)
	public const MATERIAL  = '_pd_material';
	public const TIME      = '_pd_making_time';
	public const BADGE     = '_pd_badge';           // short tag on the card and page picture, e.g. "Award winner"

	/** Suggested badge words (owner, 2026-11-20). Any short text is allowed. */
	public const BADGES = array( 'Featured', 'Made on order', 'Award winner', 'New', 'One of a kind', 'Last piece' );

	public const FORMATS = array(
		'buy'            => 'Buy now only',
		'buy_commission' => 'Buy now and Commission now',
		'commission'     => 'Commission now only',
	);

	public static function register_hooks(): void {
		add_action( 'init', array( self::class, 'register' ), 20 );
		add_action( 'add_meta_boxes_product', array( self::class, 'add_box' ) );
		add_action( 'save_post_product', array( self::class, 'save' ), 10, 2 );
	}

	public static function register(): void {
		if ( ! post_type_exists( 'product' ) ) {
			return;
		}
		$auth = static fn() => current_user_can( 'edit_products' );
		register_post_meta( 'product', self::ARTISAN, array( 'single' => true, 'type' => 'integer', 'show_in_rest' => true, 'auth_callback' => $auth, 'default' => 0 ) );
		register_post_meta( 'product', self::NUMBER, array( 'single' => true, 'type' => 'integer', 'show_in_rest' => true, 'auth_callback' => $auth, 'default' => 0 ) );
		foreach ( array( self::TITLE_HI, self::FORMAT, self::ON_ORDER, self::MATERIAL, self::TIME, self::BADGE ) as $key ) {
			register_post_meta( 'product', $key, array( 'single' => true, 'type' => 'string', 'show_in_rest' => true, 'auth_callback' => $auth ) );
		}
	}

	public static function add_box(): void {
		add_meta_box( 'pd-work', __( 'Priyadarshini: maker and format', 'pd-marketplace' ), array( self::class, 'box' ), 'product', 'side', 'high' );
	}

	public static function box( \WP_Post $post ): void {
		$artisan  = (int) get_post_meta( $post->ID, self::ARTISAN, true );
		$format   = (string) get_post_meta( $post->ID, self::FORMAT, true ) ?: 'buy';
		$artisans = get_posts( array( 'post_type' => PostType::NAME, 'post_status' => array( 'publish', 'draft' ), 'numberposts' => 500, 'orderby' => 'title', 'order' => 'ASC' ) );
		wp_nonce_field( 'pd_work_' . $post->ID, '_pd_work_nonce' );
		?>
		<p><label for="pd-artisan"><strong><?php esc_html_e( 'Made by', 'pd-marketplace' ); ?></strong></label><br>
			<select id="pd-artisan" name="pd_artisan" style="width:100%">
				<option value="0"><?php esc_html_e( 'Choose the artisan', 'pd-marketplace' ); ?></option>
				<?php foreach ( $artisans as $a ) : ?>
					<option value="<?php echo (int) $a->ID; ?>" <?php selected( $artisan, $a->ID ); ?>><?php echo esc_html( get_the_title( $a ) ); ?></option>
				<?php endforeach; ?>
			</select></p>
		<p><label for="pd-title-hi"><strong><?php esc_html_e( 'Title in Devanagari', 'pd-marketplace' ); ?></strong></label><br>
			<input id="pd-title-hi" name="pd_title_hi" lang="hi" type="text" style="width:100%" value="<?php echo esc_attr( (string) get_post_meta( $post->ID, self::TITLE_HI, true ) ); ?>"></p>
		<fieldset><legend><strong><?php esc_html_e( 'Buying format', 'pd-marketplace' ); ?></strong></legend>
			<?php foreach ( self::FORMATS as $value => $label ) : ?>
				<label style="display:block"><input type="radio" name="pd_format" value="<?php echo esc_attr( $value ); ?>" <?php checked( $format, $value ); ?>> <?php echo esc_html( $label ); ?></label>
			<?php endforeach; ?>
		</fieldset>
		<p><label><input type="checkbox" name="pd_made_on_order" value="1" <?php checked( '1', get_post_meta( $post->ID, self::ON_ORDER, true ) ); ?>> <?php esc_html_e( 'Made on order (made after the buyer orders)', 'pd-marketplace' ); ?></label></p>
		<p><label for="pd-material"><strong><?php esc_html_e( 'Material', 'pd-marketplace' ); ?></strong></label><br>
			<input id="pd-material" name="pd_material" type="text" style="width:100%" value="<?php echo esc_attr( (string) get_post_meta( $post->ID, self::MATERIAL, true ) ); ?>" placeholder="Handmade paper, natural pigments"></p>
		<p><label for="pd-time"><strong><?php esc_html_e( 'Time to make', 'pd-marketplace' ); ?></strong></label><br>
			<input id="pd-time" name="pd_making_time" type="text" style="width:100%" value="<?php echo esc_attr( (string) get_post_meta( $post->ID, self::TIME, true ) ); ?>" placeholder="3 weeks"></p>
		<p><label for="pd-badge"><strong><?php esc_html_e( 'Badge on the picture', 'pd-marketplace' ); ?></strong></label><br>
			<input id="pd-badge" name="pd_badge" type="text" list="pd-badges" maxlength="24" style="width:100%" value="<?php echo esc_attr( (string) get_post_meta( $post->ID, self::BADGE, true ) ); ?>" placeholder="<?php esc_attr_e( 'Leave empty for none', 'pd-marketplace' ); ?>">
			<datalist id="pd-badges"><?php foreach ( self::BADGES as $b ) : ?><option value="<?php echo esc_attr( $b ); ?>"><?php endforeach; ?></datalist></p>
		<?php if ( $n = (int) get_post_meta( $post->ID, self::NUMBER, true ) ) : ?>
			<p><?php echo esc_html( sprintf( 'Work No. %03d of this artisan', $n ) ); ?></p>
		<?php endif; ?>
		<?php
	}

	public static function save( int $post_id, \WP_Post $post ): void {
		if ( ! isset( $_POST['_pd_work_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['_pd_work_nonce'] ) ), 'pd_work_' . $post_id ) ) {
			return;
		}
		if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}
		$artisan = absint( $_POST['pd_artisan'] ?? 0 );
		if ( $artisan && PostType::NAME !== get_post_type( $artisan ) ) {
			$artisan = 0;
		}
		$format = sanitize_key( wp_unslash( $_POST['pd_format'] ?? 'buy' ) );
		self::set( $post_id, array(
			'artisan'       => $artisan,
			'title_hi'      => sanitize_text_field( wp_unslash( $_POST['pd_title_hi'] ?? '' ) ),
			'format'        => isset( self::FORMATS[ $format ] ) ? $format : 'buy',
			'made_on_order' => ! empty( $_POST['pd_made_on_order'] ),
			'material'      => sanitize_text_field( wp_unslash( $_POST['pd_material'] ?? '' ) ),
			'making_time'   => sanitize_text_field( wp_unslash( $_POST['pd_making_time'] ?? '' ) ),
			'badge'         => mb_substr( sanitize_text_field( wp_unslash( $_POST['pd_badge'] ?? '' ) ), 0, 24 ),
		) );
	}

	/** Writes the work fields. Used by the admin box, tests and seeding. @param array<string,mixed> $f */
	public static function set( int $product_id, array $f ): void {
		$prev = (int) get_post_meta( $product_id, self::ARTISAN, true );
		if ( array_key_exists( 'artisan', $f ) ) {
			update_post_meta( $product_id, self::ARTISAN, (int) $f['artisan'] );
			if ( (int) $f['artisan'] && ( (int) $f['artisan'] !== $prev || ! get_post_meta( $product_id, self::NUMBER, true ) ) ) {
				update_post_meta( $product_id, self::NUMBER, self::next_number( (int) $f['artisan'] ) );
			}
		}
		$map = array( 'title_hi' => self::TITLE_HI, 'format' => self::FORMAT, 'material' => self::MATERIAL, 'making_time' => self::TIME, 'badge' => self::BADGE );
		foreach ( $map as $k => $meta ) {
			if ( array_key_exists( $k, $f ) ) {
				update_post_meta( $product_id, $meta, (string) $f[ $k ] );
			}
		}
		if ( array_key_exists( 'made_on_order', $f ) ) {
			update_post_meta( $product_id, self::ON_ORDER, $f['made_on_order'] ? '1' : '' );
		}
	}

	/** Catalogue numbers count up per artisan and are never reused: Work No. 014 is that maker's fourteenth listed work. */
	public static function next_number( int $artisan_id ): int {
		global $wpdb;
		$max = (int) $wpdb->get_var( $wpdb->prepare(
			"SELECT MAX(CAST(n.meta_value AS UNSIGNED)) FROM {$wpdb->postmeta} n
			 INNER JOIN {$wpdb->postmeta} a ON a.post_id = n.post_id AND a.meta_key = %s AND a.meta_value = %s
			 WHERE n.meta_key = %s",
			self::ARTISAN, (string) $artisan_id, self::NUMBER
		) );
		return $max + 1;
	}
}
