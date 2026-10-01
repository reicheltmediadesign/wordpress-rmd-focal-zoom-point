<?php
/**
 * Blocks that offer the "Focal point & zoom" panel and a per-use override.
 *
 * Core: the image block. Themes and plugins add their own blocks through the
 * filter rmd_fzp_blocks (block name => name of the attribute that holds the
 * attachment ID), see docs/theme-integration.md.
 *
 * @package RMD\FocalZoomPoint
 */

namespace RMD\FocalZoomPoint;

use RMD\FocalZoomPoint\Domain\FocalPoint;

defined( 'ABSPATH' ) || exit;

final class Blocks {

	/** Block attribute holding the per-use override. */
	public const ATTRIBUTE = 'rmdFzp';

	/** Blocks whose image the plugin wraps in a clipping frame for zoom. */
	public const FRAMED = [ 'core/image', 'core/post-featured-image' ];

	public static function init(): void {
		add_filter( 'register_block_type_args', [ self::class, 'add_attribute' ], 10, 2 );
	}

	/**
	 * Supported blocks.
	 *
	 * @return array<string, string> Block name => attachment ID attribute.
	 */
	public static function supported(): array {
		/**
		 * Filters the blocks that get the "Focal point & zoom" panel.
		 *
		 * @param array<string, string> $blocks Block name => name of the attribute holding the attachment ID.
		 */
		$blocks = apply_filters( 'rmd_fzp_blocks', [ 'core/image' => 'id' ] );

		$clean = [];
		foreach ( (array) $blocks as $name => $attribute ) {
			if ( is_string( $name ) && is_string( $attribute ) && '' !== $attribute ) {
				$clean[ $name ] = $attribute;
			}
		}
		return $clean;
	}

	/**
	 * Registers the override attribute server-side as well, so dynamic blocks
	 * accept it in REST requests and static blocks keep it.
	 *
	 * @param array  $args       Block type arguments.
	 * @param string $block_name Block name.
	 * @return array
	 */
	public static function add_attribute( $args, $block_name ) {
		if ( ! isset( self::supported()[ $block_name ] ) ) {
			return $args;
		}
		$args['attributes']                    = $args['attributes'] ?? [];
		$args['attributes'][ self::ATTRIBUTE ] = [ 'type' => 'object' ];
		return $args;
	}

	/**
	 * Per-use override stored on a block.
	 *
	 * @param array $attrs Block attributes.
	 */
	public static function override( array $attrs ): ?FocalPoint {
		return isset( $attrs[ self::ATTRIBUTE ] ) ? FocalPoint::from( $attrs[ self::ATTRIBUTE ] ) : null;
	}
}
