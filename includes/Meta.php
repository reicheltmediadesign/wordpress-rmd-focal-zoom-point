<?php
/**
 * Storage of the focal point and zoom on the attachment.
 *
 * @package RMD\FocalZoomPoint
 */

namespace RMD\FocalZoomPoint;

use RMD\FocalZoomPoint\Domain\FocalPoint;

defined( 'ABSPATH' ) || exit;

final class Meta {

	public const KEY = '_rmd_fzp';

	public static function init(): void {
		add_action( 'init', [ self::class, 'register' ] );
	}

	/**
	 * Registers the meta for the REST API, so the block editor can read and
	 * save it on the media endpoint. Sending null deletes it.
	 */
	public static function register(): void {
		register_post_meta(
			'attachment',
			self::KEY,
			[
				'single'            => true,
				'type'              => 'object',
				'show_in_rest'      => [
					'schema' => [
						'type'                 => 'object',
						'additionalProperties' => false,
						'properties'           => [
							'x'    => [
								'type'    => 'number',
								'minimum' => 0,
								'maximum' => 100,
							],
							'y'    => [
								'type'    => 'number',
								'minimum' => 0,
								'maximum' => 100,
							],
							'zoom' => [
								'type'    => 'number',
								'minimum' => FocalPoint::MIN_ZOOM,
								'maximum' => FocalPoint::MAX_ZOOM,
							],
						],
					],
				],
				'sanitize_callback' => [ self::class, 'sanitize' ],
				'auth_callback'     => static function ( $allowed, $meta_key, $object_id ) {
					return current_user_can( 'edit_post', $object_id );
				},
			]
		);
	}

	/**
	 * @param mixed $value Raw value.
	 * @return array{x: float, y: float, zoom: float}
	 */
	public static function sanitize( $value ): array {
		return ( FocalPoint::from( $value ) ?? FocalPoint::center() )->to_array();
	}

	/**
	 * Stored point of an image.
	 *
	 * @return FocalPoint|null Null if the image has none (center, no zoom).
	 */
	public static function get( int $attachment_id ): ?FocalPoint {
		if ( $attachment_id <= 0 ) {
			return null;
		}
		$point = FocalPoint::from( get_post_meta( $attachment_id, self::KEY, true ) );
		return $point && ! $point->is_default() ? $point : null;
	}

	/**
	 * Stores a point; the center without zoom removes the meta.
	 */
	public static function save( int $attachment_id, FocalPoint $point ): void {
		if ( $point->is_default() ) {
			delete_post_meta( $attachment_id, self::KEY );
			return;
		}
		update_post_meta( $attachment_id, self::KEY, $point->to_array() );
	}

	/**
	 * Width / height of an image from its attachment metadata.
	 */
	public static function ratio( int $attachment_id ): ?float {
		$meta = wp_get_attachment_metadata( $attachment_id );
		if ( ! is_array( $meta ) || empty( $meta['width'] ) || empty( $meta['height'] ) ) {
			return null;
		}
		return (float) $meta['width'] / (float) $meta['height'];
	}
}
