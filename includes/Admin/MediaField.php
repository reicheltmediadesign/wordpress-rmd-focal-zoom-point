<?php
/**
 * "Focal point & zoom" field in the media library (modal and attachment edit
 * screen), with previews of typical crops. Driven by assets/js/media.js.
 *
 * @package RMD\FocalZoomPoint
 */

namespace RMD\FocalZoomPoint\Admin;

use RMD\FocalZoomPoint\Domain\FocalPoint;
use RMD\FocalZoomPoint\Meta;
use WP_Post;

defined( 'ABSPATH' ) || exit;

final class MediaField {

	public const FIELD = 'rmd_fzp';

	public static function init(): void {
		add_filter( 'attachment_fields_to_edit', [ self::class, 'field' ], 10, 2 );
		add_filter( 'attachment_fields_to_save', [ self::class, 'save' ], 10, 2 );
	}

	/**
	 * Preview frames shown below the picker.
	 *
	 * @return array<int, array{label: string, ratio: string, round: bool}>
	 */
	public static function previews(): array {
		$defaults = [
			[
				'label' => __( 'Wide banner', 'rmd-focal-zoom-point' ),
				'ratio' => '16 / 5',
				'round' => false,
			],
			[
				'label' => __( 'Landscape', 'rmd-focal-zoom-point' ),
				'ratio' => '3 / 2',
				'round' => false,
			],
			[
				'label' => __( 'Circle', 'rmd-focal-zoom-point' ),
				'ratio' => '1',
				'round' => true,
			],
		];

		/**
		 * Filters the preview frames in the media library. Themes replace them
		 * with the crops they actually use.
		 *
		 * @param array $previews List of [ 'label' => string, 'ratio' => '16 / 9', 'round' => bool ].
		 */
		$previews = apply_filters( 'rmd_fzp_previews', $defaults );

		$clean = [];
		foreach ( (array) $previews as $preview ) {
			if ( ! is_array( $preview ) || empty( $preview['label'] ) ) {
				continue;
			}
			$ratio = trim( (string) ( $preview['ratio'] ?? '1' ) );
			if ( ! preg_match( '#^\d+(\.\d+)?(\s*/\s*\d+(\.\d+)?)?$#', $ratio ) ) {
				$ratio = '1';
			}
			$clean[] = [
				'label' => (string) $preview['label'],
				'ratio' => $ratio,
				'round' => ! empty( $preview['round'] ),
			];
		}
		return $clean;
	}

	/**
	 * @param array   $fields Attachment form fields.
	 * @param WP_Post $post   Attachment.
	 * @return array
	 */
	public static function field( $fields, $post ) {
		if ( ! wp_attachment_is_image( $post ) || ! current_user_can( 'edit_post', $post->ID ) ) {
			return $fields;
		}

		$point = Meta::get( $post->ID ) ?? FocalPoint::center();
		$url   = wp_get_attachment_image_url( $post->ID, 'medium_large' );
		$name  = 'attachments[' . $post->ID . '][' . self::FIELD . ']';

		ob_start();
		?>
		<div class="rmd-fzp-field" data-rmd-fzp-field style="<?php echo esc_attr( $point->declarations() ); ?>">
			<div class="rmd-fzp-field__picker" data-rmd-fzp-picker>
				<img src="<?php echo esc_url( $url ); ?>" alt="" draggable="false">
				<span class="rmd-fzp-field__dot" aria-hidden="true"></span>
			</div>
			<p class="description"><?php esc_html_e( 'Click the most important part of the image or drag the dot. Wherever the image is cropped, this part stays in view. Zoom enlarges the image around it – this also lets you move the focus sideways in a tall image shown in a square or circle.', 'rmd-focal-zoom-point' ); ?></p>
			<div class="rmd-fzp-field__controls">
				<label><?php esc_html_e( 'Horizontal', 'rmd-focal-zoom-point' ); ?> <input type="number" name="<?php echo esc_attr( $name . '[x]' ); ?>" min="0" max="100" step="0.1" value="<?php echo esc_attr( (string) $point->x ); ?>" data-rmd-fzp-x> %</label>
				<label><?php esc_html_e( 'Vertical', 'rmd-focal-zoom-point' ); ?> <input type="number" name="<?php echo esc_attr( $name . '[y]' ); ?>" min="0" max="100" step="0.1" value="<?php echo esc_attr( (string) $point->y ); ?>" data-rmd-fzp-y> %</label>
				<label class="rmd-fzp-field__zoom"><?php esc_html_e( 'Zoom', 'rmd-focal-zoom-point' ); ?>
					<input type="range" min="<?php echo esc_attr( (string) FocalPoint::MIN_ZOOM ); ?>" max="<?php echo esc_attr( (string) FocalPoint::MAX_ZOOM ); ?>" step="0.05" value="<?php echo esc_attr( (string) $point->zoom ); ?>" data-rmd-fzp-zoom-range aria-hidden="true" tabindex="-1">
					<input type="number" name="<?php echo esc_attr( $name . '[zoom]' ); ?>" min="<?php echo esc_attr( (string) FocalPoint::MIN_ZOOM ); ?>" max="<?php echo esc_attr( (string) FocalPoint::MAX_ZOOM ); ?>" step="0.05" value="<?php echo esc_attr( (string) $point->zoom ); ?>" data-rmd-fzp-zoom> ×
				</label>
				<button type="button" class="button" data-rmd-fzp-reset><?php esc_html_e( 'Reset', 'rmd-focal-zoom-point' ); ?></button>
			</div>
			<div class="rmd-fzp-field__previews">
				<?php foreach ( self::previews() as $preview ) : ?>
					<figure class="rmd-fzp-preview">
						<span class="rmd-fzp-frame rmd-fzp-preview__frame<?php echo $preview['round'] ? ' is-round' : ''; ?>" style="<?php echo esc_attr( 'aspect-ratio: ' . $preview['ratio'] ); ?>">
							<img class="rmd-fzp-zoom" src="<?php echo esc_url( $url ); ?>" alt="">
						</span>
						<figcaption><?php echo esc_html( $preview['label'] ); ?></figcaption>
					</figure>
				<?php endforeach; ?>
			</div>
			<p class="description"><?php esc_html_e( 'Applies wherever this image is used. In the block editor it can be adjusted for a single use.', 'rmd-focal-zoom-point' ); ?></p>
		</div>
		<?php

		$fields[ self::FIELD ] = [
			'label' => __( 'Focal point & zoom', 'rmd-focal-zoom-point' ),
			'input' => 'html',
			'html'  => (string) ob_get_clean(),
		];
		return $fields;
	}

	/**
	 * @param array $post       Attachment data.
	 * @param array $attachment Submitted attachment fields.
	 * @return array
	 */
	public static function save( $post, $attachment ) {
		$values = $attachment[ self::FIELD ] ?? null;
		if ( ! is_array( $values ) || empty( $post['ID'] ) || ! current_user_can( 'edit_post', (int) $post['ID'] ) ) {
			return $post;
		}

		$point = FocalPoint::from( wp_unslash( $values ) );
		if ( $point ) {
			Meta::save( (int) $post['ID'], $point );
		}
		return $post;
	}
}
