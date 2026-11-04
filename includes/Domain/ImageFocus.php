<?php
/**
 * Everything stored on one image: the general focal point and zoom, plus own
 * values for single crop variants (e.g. "round"). Shape of the post meta:
 * { x, y, zoom, variants?: { key: { x, y, zoom } } }.
 *
 * WordPress-free so it can be unit tested with plain PHPUnit.
 *
 * @package RMD\FocalZoomPoint
 */

namespace RMD\FocalZoomPoint\Domain;

final class ImageFocus {

	/**
	 * @param FocalPoint                $base     General values, for every crop without own values.
	 * @param array<string, FocalPoint> $variants Own values per variant key.
	 */
	private function __construct(
		public readonly FocalPoint $base,
		public readonly array $variants
	) {}

	public static function empty(): self {
		return new self( FocalPoint::center(), [] );
	}

	public static function of( FocalPoint $base ): self {
		return new self( $base, [] );
	}

	/**
	 * Builds a normalized value from untrusted input. Invalid variant keys and
	 * points are dropped; a missing general point becomes the center.
	 *
	 * @param mixed $value Raw value.
	 * @return self|null Null if neither the general point nor a variant is valid.
	 */
	public static function from( mixed $value ): ?self {
		if ( is_object( $value ) ) {
			$value = get_object_vars( $value );
		}
		if ( ! is_array( $value ) ) {
			return null;
		}

		$base = FocalPoint::from( $value );

		$variants = [];
		$raw      = $value['variants'] ?? null;
		if ( is_object( $raw ) ) {
			$raw = get_object_vars( $raw );
		}
		if ( is_array( $raw ) ) {
			foreach ( $raw as $key => $point ) {
				$point = is_string( $key ) && Variant::is_key( $key ) ? FocalPoint::from( $point ) : null;
				if ( $point ) {
					$variants[ $key ] = $point;
				}
			}
		}

		if ( ! $base && ! $variants ) {
			return null;
		}
		return new self( $base ?? FocalPoint::center(), $variants );
	}

	/**
	 * Nothing worth storing: center without zoom and no variants.
	 */
	public function is_empty(): bool {
		return $this->base->is_default() && ! $this->variants;
	}

	public function has_variant( string $key ): bool {
		return isset( $this->variants[ $key ] );
	}

	/**
	 * Values for a use of the image: the variant's own values if it has any,
	 * else the general ones.
	 *
	 * @param string|null $variant Variant key, null for the general values.
	 * @return FocalPoint|null Null if the result would be the center without zoom
	 *                         by default, i.e. there is nothing to apply.
	 */
	public function resolve( ?string $variant ): ?FocalPoint {
		if ( null !== $variant && isset( $this->variants[ $variant ] ) ) {
			return $this->variants[ $variant ];
		}
		return $this->base->is_default() ? null : $this->base;
	}

	public function with_base( FocalPoint $base ): self {
		return new self( $base, $this->variants );
	}

	public function with_variant( string $key, FocalPoint $point ): self {
		if ( ! Variant::is_key( $key ) ) {
			return $this;
		}
		$variants         = $this->variants;
		$variants[ $key ] = $point;
		return new self( $this->base, $variants );
	}

	/**
	 * Takes over variants from $previous that are not in $known, e.g. values
	 * for crops of another theme that the media library form does not show.
	 *
	 * @param self     $previous Stored value.
	 * @param string[] $known    Variant keys the form submitted or could submit.
	 */
	public function keeping_unknown( self $previous, array $known ): self {
		$variants = $this->variants;
		foreach ( $previous->variants as $key => $point ) {
			if ( ! in_array( $key, $known, true ) && ! isset( $variants[ $key ] ) ) {
				$variants[ $key ] = $point;
			}
		}
		return new self( $this->base, $variants );
	}

	/**
	 * @return array{x: float, y: float, zoom: float, variants?: array<string, array{x: float, y: float, zoom: float}>}
	 */
	public function to_array(): array {
		$data = $this->base->to_array();
		if ( $this->variants ) {
			$data['variants'] = array_map( static fn( FocalPoint $point ): array => $point->to_array(), $this->variants );
		}
		return $data;
	}
}
