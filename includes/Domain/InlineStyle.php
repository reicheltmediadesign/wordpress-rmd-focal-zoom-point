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

	/**
	 * Removes declarations of the given properties and of all properties
	 * starting with one of the prefixes. Semicolons inside parentheses
	 * (e.g. in url()) do not split declarations.
	 *
	 * @param string   $style      Style attribute value.
	 * @param string[] $properties Property names.
	 * @param string[] $prefixes   Property name prefixes, e.g. "--rmd-fzp-".
	 */
	public static function remove( string $style, array $properties, array $prefixes = [] ): string {
		$properties = array_map( 'strtolower', $properties );
		$kept       = [];
		foreach ( (array) preg_split( '/;(?![^(]*\))/', $style ) as $declaration ) {
			$declaration = trim( (string) $declaration );
			if ( '' === $declaration ) {
				continue;
			}
			$name = strtolower( trim( strstr( $declaration, ':', true ) ?: $declaration ) );
			if ( in_array( $name, $properties, true ) ) {
				continue;
			}
			foreach ( $prefixes as $prefix ) {
				if ( str_starts_with( $name, strtolower( $prefix ) ) ) {
					continue 2;
				}
			}
			$kept[] = $declaration;
		}
		return $kept ? implode( '; ', $kept ) . ';' : '';
	}
}
