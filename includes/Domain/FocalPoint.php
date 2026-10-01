<?php
/**
 * Focal point and zoom of an image. The only place that defines the data
 * shape: every write path (media library field, REST, block attribute,
 * theme API) goes through FocalPoint::from().
 *
 * WordPress-free so it can be unit tested with plain PHPUnit.
 *
 * @package RMD\FocalZoomPoint
 */

namespace RMD\FocalZoomPoint\Domain;

final class FocalPoint {

	public const MIN_ZOOM = 1.0;
	public const MAX_ZOOM = 3.0;

	private function __construct(
		public readonly float $x,
		public readonly float $y,
		public readonly float $zoom
	) {}

	/**
	 * Center, no zoom: what an image without stored values looks like.
	 */
	public static function center(): self {
		return new self( 50.0, 50.0, 1.0 );
	}

	/**
	 * Builds a normalized point from untrusted input.
	 *
	 * Accepts an array (or object) with numeric x and y in percent and an
	 * optional zoom factor. Values are clamped to 0–100 and 1–3 and rounded
	 * to one (position) or two (zoom) decimals.
	 *
	 * @param mixed $value Raw value.
	 * @return self|null Null if x or y is missing or not numeric.
	 */
	public static function from( mixed $value ): ?self {
		if ( is_object( $value ) ) {
			$value = get_object_vars( $value );
		}
		if ( ! is_array( $value ) || ! isset( $value['x'], $value['y'] ) ) {
			return null;
		}
		if ( ! is_numeric( $value['x'] ) || ! is_numeric( $value['y'] ) ) {
			return null;
		}

		$zoom = isset( $value['zoom'] ) && is_numeric( $value['zoom'] ) ? (float) $value['zoom'] : self::MIN_ZOOM;

		return new self(
			round( self::clamp( (float) $value['x'], 0.0, 100.0 ), 1 ),
			round( self::clamp( (float) $value['y'], 0.0, 100.0 ), 1 ),
			round( self::clamp( $zoom, self::MIN_ZOOM, self::MAX_ZOOM ), 2 )
		);
	}

	/**
	 * True for the center without zoom; such a point is not stored.
	 */
	public function is_default(): bool {
		return 50.0 === $this->x && 50.0 === $this->y && self::MIN_ZOOM === $this->zoom;
	}

	public function has_zoom(): bool {
		return $this->zoom > self::MIN_ZOOM;
	}

	/**
	 * @return array{x: float, y: float, zoom: float}
	 */
	public function to_array(): array {
		return [
			'x'    => $this->x,
			'y'    => $this->y,
			'zoom' => $this->zoom,
		];
	}

	/**
	 * CSS custom properties describing this point.
	 *
	 * @param float|null $ratio Width / height of the image, if known.
	 * @return array<string, string> Property name => value.
	 */
	public function custom_properties( ?float $ratio = null ): array {
		$x = self::number( $this->x ) . '%';
		$y = self::number( $this->y ) . '%';

		$properties = [
			'--rmd-fzp-x'    => $x,
			'--rmd-fzp-y'    => $y,
			'--rmd-fzp-pos'  => $x . ' ' . $y,
			'--rmd-fzp-zoom' => self::number( $this->zoom ),
		];
		if ( null !== $ratio && $ratio > 0 ) {
			$properties['--rmd-fzp-ratio'] = self::number( round( $ratio, 4 ), 4 );
		}
		return $properties;
	}

	/**
	 * Declarations for a style attribute.
	 *
	 * @param bool       $object_position Also set object-position (for <img>).
	 * @param float|null $ratio           Width / height of the image, if known.
	 */
	public function declarations( bool $object_position = false, ?float $ratio = null ): string {
		$parts = [];
		if ( $object_position ) {
			$parts[] = 'object-position: ' . self::number( $this->x ) . '% ' . self::number( $this->y ) . '%';
		}
		foreach ( $this->custom_properties( $ratio ) as $name => $value ) {
			$parts[] = $name . ': ' . $value;
		}
		return implode( '; ', $parts ) . ';';
	}

	private static function clamp( float $value, float $min, float $max ): float {
		if ( is_nan( $value ) ) {
			return $min;
		}
		return min( $max, max( $min, $value ) );
	}

	/**
	 * Shortest decimal notation with a dot: 50.0 → "50", 12.5 → "12.5".
	 */
	private static function number( float $value, int $decimals = 2 ): string {
		$text = rtrim( rtrim( number_format( $value, $decimals, '.', '' ), '0' ), '.' );
		return '-0' === $text ? '0' : $text;
	}
}
