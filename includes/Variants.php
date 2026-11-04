<?php
/**
 * The crop variants of the site: previews in the media library, own focal
 * points per variant, and the variant a use of an image falls into.
 *
 * Themes replace the defaults with their own crops through the filter
 * rmd_fzp_variants, see docs/theme-integration.md.
 *
 * @package RMD\FocalZoomPoint
 */

namespace RMD\FocalZoomPoint;

use RMD\FocalZoomPoint\Domain\Variant;

defined( 'ABSPATH' ) || exit;

final class Variants {

	/** @var array<string, Variant>|null */
	private static ?array $cache = null;

	/**
	 * @return array<string, Variant> Key => variant.
	 */
	public static function all(): array {
		if ( null !== self::$cache && did_action( 'init' ) ) {
			return self::$cache;
		}

		$defaults = [
			[
				'key'   => 'wide',
				'label' => __( 'Wide banner', 'rmd-focal-zoom-point' ),
				'ratio' => '16 / 5',
			],
			[
				'key'   => 'landscape',
				'label' => __( 'Landscape', 'rmd-focal-zoom-point' ),
				'ratio' => '3 / 2',
			],
			[
				'key'   => 'portrait',
				'label' => __( 'Portrait', 'rmd-focal-zoom-point' ),
				'ratio' => '3 / 4',
			],
			[
				'key'   => 'round',
				'label' => __( 'Square & circle', 'rmd-focal-zoom-point' ),
				'ratio' => '1',
				'round' => true,
			],
		];

		/**
		 * Filters the crop variants: previews in the media library, each of
		 * which can get its own focal point. Themes replace them with the
		 * crops they actually use.
		 *
		 * @param array $variants List of [ 'key' => 'round', 'label' => string, 'ratio' => '16 / 9', 'round' => bool ].
		 */
		$variants = apply_filters( 'rmd_fzp_variants', $defaults );

		// Name before 0.2.0. Its entries have no keys; they are derived from the labels.
		$variants = apply_filters_deprecated( 'rmd_fzp_previews', [ $variants ], '0.2.0', 'rmd_fzp_variants' );

		$list = Variant::list_from( $variants );
		if ( did_action( 'init' ) ) {
			self::$cache = $list;
		}
		return $list;
	}

	public static function get( ?string $key ): ?Variant {
		return null === $key ? null : ( self::all()[ $key ] ?? null );
	}

	/**
	 * Key of a known variant, null for anything else.
	 *
	 * @param mixed $key Raw key.
	 */
	public static function key( mixed $key ): ?string {
		return is_string( $key ) && isset( self::all()[ $key ] ) ? $key : null;
	}

	/**
	 * Variant for a crop with the given aspect ratio or a circle.
	 *
	 * @param float|null $aspect Width / height of the crop, null if unknown.
	 * @param bool       $round  Circular crop.
	 */
	public static function match( ?float $aspect, bool $round ): ?string {
		if ( null === $aspect && ! $round ) {
			return null;
		}
		return Variant::match( self::all(), $aspect, $round );
	}
}
