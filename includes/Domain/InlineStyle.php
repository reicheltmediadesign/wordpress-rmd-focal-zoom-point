<?php
/**
 * Helpers for the value of a style attribute.
 *
 * @package RMD\FocalZoomPoint
 */

namespace RMD\FocalZoomPoint\Domain;

final class InlineStyle {

	/**
	 * Appends declarations to an existing style attribute value.
	 */
	public static function append( string $style, string $declarations ): string {
		$style        = trim( $style );
		$declarations = trim( $declarations );
		if ( '' === $declarations ) {
			return $style;
		}
		if ( '' === $style ) {
			return $declarations;
		}
		return rtrim( $style, "; \t\n" ) . '; ' . $declarations;
	}

	/**
	 * Whether a style attribute value sets the given property, e.g. an
	 * object-position from the cover block's own focal point picker.
	 */
	public static function has_property( string $style, string $property ): bool {
		return 1 === preg_match( '/(^|;)\s*' . preg_quote( $property, '/' ) . '\s*:/i', $style );
	}
}
