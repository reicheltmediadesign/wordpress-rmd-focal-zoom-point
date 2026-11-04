<?php
/**
 * A crop variant: a typical way a theme crops images (wide banner, portrait,
 * circle). Each variant is a preview in the media library and can have its
 * own focal point on an image. Uses whose crop is known (block aspect ratio,
 * "Rounded" style, explicit key from the theme) take the closest variant.
 *
 * WordPress-free so it can be unit tested with plain PHPUnit.
 *
 * @package RMD\FocalZoomPoint
 */

namespace RMD\FocalZoomPoint\Domain;

final class Variant {

	private const KEY_PATTERN   = '/^[a-z0-9][a-z0-9_-]{0,39}$/';
	private const RATIO_PATTERN = '#^\s*(\d+(?:\.\d+)?)\s*(?:/\s*(\d+(?:\.\d+)?)\s*)?$#';

	/**
	 * @param string $key    Lowercase key, used in the meta and in theme code.
	 * @param string $label  Shown in the media library and the block editor.
	 * @param string $ratio  CSS aspect-ratio of the preview, e.g. "16 / 9".
	 * @param float  $aspect Width / height.
	 * @param bool   $round  Circular crop.
	 */
	private function __construct(
		public readonly string $key,
		public readonly string $label,
		public readonly string $ratio,
		public readonly float $aspect,
		public readonly bool $round
	) {}

	public static function is_key( string $key ): bool {
		return 1 === preg_match( self::KEY_PATTERN, $key );
	}

	/**
	 * Width / height from "16 / 9", "16/9", "1.5" or a number.
	 *
	 * @param mixed $value Raw value, e.g. a block's aspectRatio attribute.
	 * @return float|null Null for "auto", invalid or non-positive values.
	 */
	public static function parse_ratio( mixed $value ): ?float {
		if ( is_int( $value ) || is_float( $value ) ) {
			return $value > 0 ? (float) $value : null;
		}
		if ( ! is_string( $value ) || ! preg_match( self::RATIO_PATTERN, $value, $match ) ) {
			return null;
		}
		$width  = (float) $match[1];
		$height = isset( $match[2] ) ? (float) $match[2] : 1.0;
		return $width > 0 && $height > 0 ? $width / $height : null;
	}

	/**
	 * Builds the list of variants from untrusted definitions (filter output).
	 *
	 * Each entry: [ 'key' => 'round', 'label' => 'Circle', 'ratio' => '1',
	 * 'round' => true ]. Without a key, one is derived from the label. Entries
	 * without a label are skipped, invalid ratios become 1, the first entry
	 * with a key wins.
	 *
	 * @param mixed $definitions Raw list.
	 * @return array<string, self> Key => variant, in the given order.
	 */
	public static function list_from( mixed $definitions ): array {
		$list = [];
		foreach ( is_array( $definitions ) ? $definitions : [] as $definition ) {
			if ( ! is_array( $definition ) || ! isset( $definition['label'] ) || ! is_scalar( $definition['label'] ) ) {
				continue;
			}
			$label = trim( (string) $definition['label'] );
			if ( '' === $label ) {
				continue;
			}

			$key = isset( $definition['key'] ) && is_string( $definition['key'] ) ? $definition['key'] : self::key_from( $label );
			if ( ! self::is_key( $key ) || isset( $list[ $key ] ) ) {
				continue;
			}

			$ratio  = isset( $definition['ratio'] ) && is_scalar( $definition['ratio'] ) ? trim( (string) $definition['ratio'] ) : '1';
			$aspect = self::parse_ratio( $ratio );
			if ( null === $aspect ) {
				$ratio  = '1';
				$aspect = 1.0;
			}

			$list[ $key ] = new self( $key, $label, $ratio, $aspect, ! empty( $definition['round'] ) );
		}
		return $list;
	}

	/**
	 * Variant for a use with a known crop.
	 *
	 * Round uses take a round variant, others the variant with the closest
	 * aspect ratio (round variants count as squares).
	 *
	 * @param array<string, self> $variants Available variants.
	 * @param float|null          $aspect   Width / height of the crop, null if unknown.
	 * @param bool                $round    The crop is a circle.
	 * @return string|null Variant key, null if nothing matches.
	 */
	public static function match( array $variants, ?float $aspect, bool $round ): ?string {
		$candidates = $variants;
		if ( $round ) {
			$candidates = array_filter( $variants, static fn( self $variant ): bool => $variant->round );
			if ( null === $aspect ) {
				$aspect = 1.0;
			}
		}
		if ( null === $aspect || $aspect <= 0 || ! $candidates ) {
			return null;
		}

		$best     = null;
		$distance = INF;
		foreach ( $candidates as $variant ) {
			$current = abs( log( $variant->aspect / $aspect ) );
			if ( $current < $distance - 1e-9 ) {
				$best     = $variant->key;
				$distance = $current;
			}
		}
		return $best;
	}

	/**
	 * @return array{key: string, label: string, ratio: float, round: bool} For the editor script.
	 */
	public function to_script(): array {
		return [
			'key'   => $this->key,
			'label' => $this->label,
			'ratio' => round( $this->aspect, 4 ),
			'round' => $this->round,
		];
	}

	private static function key_from( string $label ): string {
		return trim( (string) preg_replace( '/[^a-z0-9_]+/', '-', strtolower( $label ) ), '-' );
	}
}
