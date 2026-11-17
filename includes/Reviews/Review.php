<?php
namespace PD\Reviews;

defined( 'ABSPATH' ) || exit;

/** Common review shape for both scopes. Public fields only: no email, no user id (SEC-08). */
final class Review {

	public function __construct(
		public readonly string $id,          // review-artisan-45 | review-product-912
		public readonly string $type,        // artisan | product
		public readonly int $source_id,
		public readonly int $artisan_id,
		public readonly int $product_id,
		public readonly int $rating,
		public readonly string $body,
		public readonly bool $has_photos,
		public readonly bool $custom_piece,
		public readonly string $author,      // "Anita K."
		public readonly string $created_at,
		public readonly ?array $reply        // {body, created_at, edited_at} or null
	) {}

	public function to_array(): array {
		return get_object_vars( $this );
	}

	public static function typed_id( string $type, int $source_id ): string {
		return "review-{$type}-{$source_id}";
	}

	/** @return array{0:string,1:int}|null */
	public static function parse_id( string $typed ): ?array {
		return preg_match( '/^review-(artisan|product)-(\d+)$/', $typed, $m ) ? array( $m[1], (int) $m[2] ) : null;
	}
}
