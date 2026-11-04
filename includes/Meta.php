<?php
/**
 * Storage of the focal point and zoom on the attachment: general values plus
 * own values per crop variant.
 *
 * @package RMD\FocalZoomPoint
 */

namespace RMD\FocalZoomPoint;

use RMD\FocalZoomPoint\Domain\FocalPoint;
use RMD\FocalZoomPoint\Domain\ImageFocus;

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
		$point = [
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
		];

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
						'properties'           => $point + [
							'variants' => [
								'type'                 => 'object',
								'additionalProperties' => [
									'type'                 => 'object',
									'additionalProperties' => false,
									'properties'           => $point,
								],
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
	 * @return array
	 */
	public static function sanitize( $value ): array {
		return ( ImageFocus::from( $value ) ?? ImageFocus::empty() )->to_array();
	}

	/**
	 * Everything stored on an image.
	 *
	 * @return ImageFocus|null Null if the image has nothing stored.
	 */
	public static function get( int $attachment_id ): ?ImageFocus {
		if ( $attachment_id <= 0 ) {
			return null;
		}
		$focus = ImageFocus::from( get_post_meta( $attachment_id, self::KEY, true ) );
		return $focus && ! $focus->is_empty() ? $focus : null;
	}

	/**
	 * Values for one use of an image.
	 *
	 * @param int         $attachment_id Attachment ID.
	 * @param string|null $variant       Crop variant of the use, null if unknown.
	 * @return FocalPoint|null Null if there is nothing to apply.
	 */
	public static function point( int $attachment_id, ?string $variant = null ): ?FocalPoint {
		$focus = self::get( $attachment_id );
		return $focus ? $focus->resolve( $variant ) : null;
	}

	/**
	 * Stores the values; the center without zoom and without variants removes
	 * the meta.
	 */
	public static function save( int $attachment_id, ImageFocus $focus ): void {
		if ( $focus->is_empty() ) {
			delete_post_meta( $attachment_id, self::KEY );
			return;
		}
		update_post_meta( $attachment_id, self::KEY, $focus->to_array() );
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
