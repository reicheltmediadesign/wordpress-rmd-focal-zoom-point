<?php
/**
 * Public PHP API for themes and plugins, see docs/theme-integration.md.
 *
 * Guard calls with function_exists( 'rmd_fzp_point' ) so the theme keeps
 * working while the plugin is inactive.
 *
 * @package RMD\FocalZoomPoint
 */

use RMD\FocalZoomPoint\Domain\FocalPoint;
use RMD\FocalZoomPoint\Meta;
use RMD\FocalZoomPoint\Render;

defined( 'ABSPATH' ) || exit;

/**
 * Focal point and zoom of an image.
 *
 * @param int        $attachment_id Attachment ID.
 * @param array|null $override      Values for this use (x, y in percent, zoom 1–3); null = the image's values.
 * @return array{x: float, y: float, zoom: float} Center and zoom 1 if nothing is set.
 */
function rmd_fzp_point( int $attachment_id, ?array $override = null ): array {
	$point = ( null !== $override ? FocalPoint::from( $override ) : null ) ?? Meta::get( $attachment_id ) ?? FocalPoint::center();
	return $point->to_array();
}

/**
 * Custom properties for a style attribute: --rmd-fzp-x, --rmd-fzp-y,
 * --rmd-fzp-pos, --rmd-fzp-zoom and --rmd-fzp-ratio. Meant for elements that
 * show the image as a CSS background.
 *
 * @param int        $attachment_id    Attachment ID.
 * @param array|null $override         Values for this use; null = the image's values.
 * @param bool       $object_position  Also return object-position (for an <img>).
 * @return string Declarations, empty if the image has neither focal point nor zoom.
 */
function rmd_fzp_style( int $attachment_id, ?array $override = null, bool $object_position = false ): string {
	$point = ( null !== $override ? FocalPoint::from( $override ) : null ) ?? Meta::get( $attachment_id );
	if ( ! $point ) {
		return '';
	}
	return $point->declarations( $object_position, Meta::ratio( $attachment_id ) );
}

/**
 * Adds focal point and zoom to an <img> tag a theme builds itself (images
 * from wp_get_attachment_image() get them automatically).
 *
 * @param string     $img_html      Markup with the <img>.
 * @param int        $attachment_id Attachment ID.
 * @param array|null $override      Values for this use; null = the image's values.
 * @return string
 */
function rmd_fzp_image_tag( string $img_html, int $attachment_id, ?array $override = null ): string {
	$custom = null !== $override ? FocalPoint::from( $override ) : null;
	$point  = $custom ?? Meta::get( $attachment_id );
	return $point ? Render::apply_to_image( $img_html, $point, null !== $custom ) : $img_html;
}
