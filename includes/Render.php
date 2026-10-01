<?php
/**
 * Output: puts the focal point and zoom on images wherever WordPress renders
 * them.
 *
 * - <img> gets an inline object-position, the custom properties
 *   --rmd-fzp-x/-y/-pos/-zoom and, with zoom, the class rmd-fzp-zoom. An
 *   object-position that is already set (cover block, media & text with
 *   their own focal point) is kept, except for a per-use override.
 * - Zoom only takes effect inside an element with the class rmd-fzp-frame
 *   (assets/css/focal-zoom.css). The plugin wraps images of the core image
 *   and featured image blocks itself; themes mark their own containers.
 * - Blocks registered through rmd_fzp_blocks without a matching <img>
 *   (background images) get the custom properties on their wrapper.
 *
 * @package RMD\FocalZoomPoint
 */

namespace RMD\FocalZoomPoint;

use RMD\FocalZoomPoint\Domain\FocalPoint;
use RMD\FocalZoomPoint\Domain\InlineStyle;
use WP_HTML_Tag_Processor;

defined( 'ABSPATH' ) || exit;

final class Render {

	/** Marks markup the plugin already handled. */
	public const MARKER = 'data-rmd-fzp';

	public const ZOOM_CLASS  = 'rmd-fzp-zoom';
	public const FRAME_CLASS = 'rmd-fzp-frame';
	public const WRAP_CLASS  = 'rmd-fzp-wrap';

	/** Key in the $attr argument of wp_get_attachment_image() for an override (array) or false to skip. */
	public const ATTR_KEY = 'rmd_fzp';

	public static function init(): void {
		add_filter( 'wp_get_attachment_image_attributes', [ self::class, 'image_attributes' ], 10, 2 );
		add_filter( 'wp_content_img_tag', [ self::class, 'content_image' ], 10, 3 );
		add_filter( 'render_block', [ self::class, 'block' ], 10, 2 );
	}

	/**
	 * Images rendered with wp_get_attachment_image(): featured images, theme
	 * templates, server-rendered blocks.
	 *
	 * @param array    $attr       Image attributes.
	 * @param \WP_Post $attachment Attachment.
	 * @return array
	 */
	public static function image_attributes( $attr, $attachment ) {
		$override = null;
		if ( array_key_exists( self::ATTR_KEY, $attr ) ) {
			$raw = $attr[ self::ATTR_KEY ];
			unset( $attr[ self::ATTR_KEY ] );
			if ( false === $raw ) {
				return $attr;
			}
			$override = FocalPoint::from( $raw );
		}

		$point = $override ?? Meta::get( (int) $attachment->ID );
		if ( ! $point ) {
			return $attr;
		}

		$style = isset( $attr['style'] ) ? (string) $attr['style'] : '';
		if ( ! $override && InlineStyle::has_property( $style, 'object-position' ) ) {
			return $attr;
		}

		$attr['style'] = InlineStyle::append( $style, $point->declarations( true ) );
		if ( $point->has_zoom() ) {
			$attr['class'] = trim( ( $attr['class'] ?? '' ) . ' ' . self::ZOOM_CLASS );
		}
		$attr[ self::MARKER ] = '1';
		return $attr;
	}

	/**
	 * Images in post content, found by WordPress through their wp-image-{ID}
	 * class.
	 *
	 * @param string $image         Image tag.
	 * @param string $context       Filter context.
	 * @param int    $attachment_id Attachment ID, 0 if unknown.
	 * @return string
	 */
	public static function content_image( $image, $context, $attachment_id ) {
		$point = Meta::get( (int) $attachment_id );
		if ( ! $point || false !== strpos( $image, self::MARKER ) ) {
			return $image;
		}
		return self::apply_to_image( $image, $point, false );
	}

	/**
	 * Supported blocks: per-use override, frame for zoom, wrapper properties
	 * for background images.
	 *
	 * @param string $content Rendered block HTML.
	 * @param array  $block   Parsed block.
	 * @return string
	 */
	public static function block( $content, $block ) {
		$name = $block['blockName'] ?? '';
		if ( '' === $content || ! is_string( $name ) || '' === $name ) {
			return $content;
		}

		if ( 'core/post-featured-image' === $name ) {
			// The image already carries the values from wp_get_attachment_image().
			return self::wrap_zoomed_image( $content );
		}

		$supported = Blocks::supported();
		if ( ! isset( $supported[ $name ] ) ) {
			return $content;
		}

		$attrs    = is_array( $block['attrs'] ?? null ) ? $block['attrs'] : [];
		$id       = (int) ( $attrs[ $supported[ $name ] ] ?? 0 );
		$override = Blocks::override( $attrs );
		$point    = $override ?? Meta::get( $id );
		if ( ! $point ) {
			return $content;
		}

		$image_class = $id > 0 ? 'wp-image-' . $id : null;
		if ( 'core/image' === $name || self::has_image( $content, $image_class ) ) {
			$content = self::apply_to_image( $content, $point, null !== $override, $image_class );
			return in_array( $name, Blocks::FRAMED, true ) ? self::wrap_zoomed_image( $content ) : $content;
		}

		return self::apply_to_wrapper( $content, $point, $id > 0 ? Meta::ratio( $id ) : null );
	}

	/**
	 * Adds the values to the first <img> (optionally the one with the given
	 * class) in $html.
	 *
	 * @param string      $html        Markup.
	 * @param FocalPoint  $point       Values.
	 * @param bool        $force       Override an object-position and earlier values.
	 * @param string|null $image_class Only an image with this class.
	 */
	public static function apply_to_image( string $html, FocalPoint $point, bool $force, ?string $image_class = null ): string {
		$tags  = new WP_HTML_Tag_Processor( $html );
		$query = [ 'tag_name' => 'IMG' ];
		if ( null !== $image_class ) {
			$query['class_name'] = $image_class;
		}
		if ( ! $tags->next_tag( $query ) ) {
			return $html;
		}

		$style = (string) $tags->get_attribute( 'style' );
		if ( ! $force && ( null !== $tags->get_attribute( self::MARKER ) || InlineStyle::has_property( $style, 'object-position' ) ) ) {
			return $html;
		}

		$tags->set_attribute( 'style', InlineStyle::append( $style, $point->declarations( true ) ) );
		if ( $point->has_zoom() ) {
			$tags->add_class( self::ZOOM_CLASS );
		} else {
			$tags->remove_class( self::ZOOM_CLASS );
		}
		$tags->set_attribute( self::MARKER, '1' );
		return $tags->get_updated_html();
	}

	/**
	 * Adds the custom properties to the first tag in $html, for blocks that
	 * show the image as a background.
	 */
	public static function apply_to_wrapper( string $html, FocalPoint $point, ?float $ratio = null ): string {
		$tags = new WP_HTML_Tag_Processor( $html );
		if ( ! $tags->next_tag() ) {
			return $html;
		}
		$tags->set_attribute( 'style', InlineStyle::append( (string) $tags->get_attribute( 'style' ), $point->declarations( false, $ratio ) ) );
		if ( $point->has_zoom() ) {
			$tags->add_class( self::ZOOM_CLASS );
		}
		$tags->set_attribute( self::MARKER, '1' );
		return $tags->get_updated_html();
	}

	/**
	 * Wraps the first zoomed <img> in a clipping frame.
	 */
	public static function wrap_zoomed_image( string $html ): string {
		if ( false === strpos( $html, self::ZOOM_CLASS ) || false !== strpos( $html, self::WRAP_CLASS ) ) {
			return $html;
		}
		return (string) preg_replace(
			'/<img\b[^>]*\bclass="[^"]*\b' . self::ZOOM_CLASS . '\b[^"]*"[^>]*>/i',
			'<span class="' . self::FRAME_CLASS . ' ' . self::WRAP_CLASS . '">$0</span>',
			$html,
			1
		);
	}

	private static function has_image( string $html, ?string $image_class ): bool {
		$tags  = new WP_HTML_Tag_Processor( $html );
		$query = [ 'tag_name' => 'IMG' ];
		if ( null !== $image_class ) {
			$query['class_name'] = $image_class;
		}
		return $tags->next_tag( $query );
	}
}
