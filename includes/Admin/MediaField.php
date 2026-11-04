<?php
/**
 * "Focal point & zoom" field in the media library (modal and attachment edit
 * screen), with a preview per crop variant. Driven by assets/js/media.js.
 *
 * The visible controls edit one target at a time: the general values ("All
 * crops") or one variant. The values themselves sit in hidden inputs, one
 * set per target; the inputs of a variant without own values are disabled
 * and therefore not submitted.
 *
 * @package RMD\FocalZoomPoint
 */

namespace RMD\FocalZoomPoint\Admin;

use RMD\FocalZoomPoint\Domain\FocalPoint;
use RMD\FocalZoomPoint\Domain\ImageFocus;
use RMD\FocalZoomPoint\Meta;
use RMD\FocalZoomPoint\Variants;
use WP_Post;

defined( 'ABSPATH' ) || exit;

final class MediaField {

	public const FIELD = 'rmd_fzp';

	/**
	 * Target edited when the field was saved, per attachment ID. The media
	 * modal renders the field again after saving, in the same request.
	 *
	 * @var array<int, string>
	 */
	private static array $active = [];

	public static function init(): void {
		add_filter( 'attachment_fields_to_edit', [ self::class, 'field' ], 10, 2 );
		add_filter( 'attachment_fields_to_save', [ self::class, 'save' ], 10, 2 );
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

		$focus    = Meta::get( $post->ID ) ?? ImageFocus::empty();
		$variants = Variants::all();
		$active   = self::$active[ $post->ID ] ?? '';
		if ( '' !== $active && ! isset( $variants[ $active ] ) ) {
			$active = '';
		}
		$current = '' !== $active && $focus->has_variant( $active ) ? $focus->variants[ $active ] : $focus->base;
		$url     = wp_get_attachment_image_url( $post->ID, 'medium_large' );
		$name    = 'attachments[' . $post->ID . '][' . self::FIELD . ']';

		ob_start();
		?>
		<div class="<?php echo esc_attr( self::field_class( $focus, $active ) ); ?>" data-rmd-fzp-field style="<?php echo esc_attr( $current->declarations() ); ?>">
			<input type="hidden" name="<?php echo esc_attr( $name . '[active]' ); ?>" value="<?php echo esc_attr( $active ); ?>" data-rmd-fzp-active>
			<?php self::store_inputs( $name, '', $focus->base, true ); ?>
			<?php foreach ( $variants as $key => $variant ) : ?>
				<?php self::store_inputs( $name . '[variants][' . $key . ']', $key, $focus->variants[ $key ] ?? $focus->base, $focus->has_variant( $key ) ); ?>
			<?php endforeach; ?>

			<?php if ( $variants ) : ?>
				<div class="rmd-fzp-field__targets" role="group" aria-label="<?php esc_attr_e( 'Values for', 'rmd-focal-zoom-point' ); ?>">
					<button type="button" class="button" data-rmd-fzp-target="" aria-pressed="<?php echo '' === $active ? 'true' : 'false'; ?>"><?php esc_html_e( 'All crops', 'rmd-focal-zoom-point' ); ?></button>
					<?php foreach ( $variants as $key => $variant ) : ?>
						<button type="button" class="button<?php echo $focus->has_variant( $key ) ? ' has-own' : ''; ?>" data-rmd-fzp-target="<?php echo esc_attr( $key ); ?>" aria-pressed="<?php echo $key === $active ? 'true' : 'false'; ?>"><?php echo esc_html( $variant->label ); ?></button>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>

			<div class="rmd-fzp-field__picker" data-rmd-fzp-picker>
				<img src="<?php echo esc_url( $url ); ?>" alt="" draggable="false">
				<span class="rmd-fzp-field__dot" aria-hidden="true"></span>
			</div>
			<p class="description rmd-fzp-field__hint-all"><?php esc_html_e( 'Click the most important part of the image or drag the dot. Wherever the image is cropped, this part stays in view. Zoom enlarges the image around it – this also lets you move the focus sideways in a tall image shown in a square or circle.', 'rmd-focal-zoom-point' ); ?></p>
			<p class="description rmd-fzp-field__hint-variant"><?php esc_html_e( 'These values apply only to this crop. As long as you do not change them, it uses the values of “All crops”.', 'rmd-focal-zoom-point' ); ?></p>
			<div class="rmd-fzp-field__controls">
				<label><?php esc_html_e( 'Horizontal', 'rmd-focal-zoom-point' ); ?> <input type="number" min="0" max="100" step="0.1" value="<?php echo esc_attr( (string) $current->x ); ?>" data-rmd-fzp-x> %</label>
				<label><?php esc_html_e( 'Vertical', 'rmd-focal-zoom-point' ); ?> <input type="number" min="0" max="100" step="0.1" value="<?php echo esc_attr( (string) $current->y ); ?>" data-rmd-fzp-y> %</label>
				<label class="rmd-fzp-field__zoom"><?php esc_html_e( 'Zoom', 'rmd-focal-zoom-point' ); ?>
					<input type="range" min="<?php echo esc_attr( (string) FocalPoint::MIN_ZOOM ); ?>" max="<?php echo esc_attr( (string) FocalPoint::MAX_ZOOM ); ?>" step="0.05" value="<?php echo esc_attr( (string) $current->zoom ); ?>" data-rmd-fzp-zoom-range aria-hidden="true" tabindex="-1">
					<input type="number" min="<?php echo esc_attr( (string) FocalPoint::MIN_ZOOM ); ?>" max="<?php echo esc_attr( (string) FocalPoint::MAX_ZOOM ); ?>" step="0.05" value="<?php echo esc_attr( (string) $current->zoom ); ?>" data-rmd-fzp-zoom> ×
				</label>
				<button type="button" class="button" data-rmd-fzp-reset><?php esc_html_e( 'Reset', 'rmd-focal-zoom-point' ); ?></button>
				<button type="button" class="button rmd-fzp-field__inherit" data-rmd-fzp-inherit><?php esc_html_e( 'Use values of “All crops”', 'rmd-focal-zoom-point' ); ?></button>
			</div>
			<div class="rmd-fzp-field__previews">
				<?php foreach ( $variants as $key => $variant ) : ?>
					<?php $point = $focus->variants[ $key ] ?? $focus->base; ?>
					<figure class="rmd-fzp-preview<?php echo $focus->has_variant( $key ) ? ' has-own' : ''; ?>" data-rmd-fzp-preview="<?php echo esc_attr( $key ); ?>">
						<button type="button" class="rmd-fzp-frame rmd-fzp-preview__frame<?php echo $variant->round ? ' is-round' : ''; ?>" style="<?php echo esc_attr( 'aspect-ratio: ' . $variant->ratio . '; ' . $point->declarations() ); ?>" data-rmd-fzp-target="<?php echo esc_attr( $key ); ?>" aria-pressed="<?php echo $key === $active ? 'true' : 'false'; ?>" aria-label="<?php echo esc_attr( $variant->label ); ?>">
							<img class="rmd-fzp-zoom" src="<?php echo esc_url( $url ); ?>" alt="">
						</button>
						<figcaption><?php echo esc_html( $variant->label ); ?> <span class="rmd-fzp-preview__own"><?php esc_html_e( 'own values', 'rmd-focal-zoom-point' ); ?></span></figcaption>
					</figure>
				<?php endforeach; ?>
			</div>
			<p class="description"><?php esc_html_e( 'Applies wherever this image is used. Click a preview to set own values for that crop. In the block editor the values can be adjusted for a single use.', 'rmd-focal-zoom-point' ); ?></p>
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
	 * Classes of the field; assets/js/media.js keeps them up to date.
	 *
	 * @param ImageFocus $focus  Stored values.
	 * @param string     $active Edited target.
	 */
	private static function field_class( ImageFocus $focus, string $active ): string {
		if ( '' === $active ) {
			return 'rmd-fzp-field has-own';
		}
		return 'rmd-fzp-field is-variant' . ( $focus->has_variant( $active ) ? ' has-own' : '' );
	}

	/**
	 * Hidden inputs holding the values of one target.
	 *
	 * @param string     $name    Input name prefix.
	 * @param string     $target  Variant key, '' for the general values.
	 * @param FocalPoint $point   Values shown.
	 * @param bool       $enabled Submitted with the form (own values).
	 */
	private static function store_inputs( string $name, string $target, FocalPoint $point, bool $enabled ): void {
		foreach ( $point->to_array() as $axis => $value ) {
			printf(
				'<input type="hidden" name="%1$s" value="%2$s" data-rmd-fzp-store="%3$s" data-rmd-fzp-axis="%4$s"%5$s>',
				esc_attr( $name . '[' . $axis . ']' ),
				esc_attr( (string) $value ),
				esc_attr( $target ),
				esc_attr( $axis ),
				$enabled ? '' : ' disabled'
			);
		}
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
		$id     = (int) $post['ID'];
		$values = wp_unslash( $values );

		$active = Variants::key( $values['active'] ?? null );
		if ( null !== $active ) {
			self::$active[ $id ] = $active;
		}

		$base = FocalPoint::from( $values );
		if ( ! $base ) {
			return $post;
		}
		$focus = ImageFocus::from( [ 'variants' => $values['variants'] ?? [] ] + $base->to_array() ) ?? ImageFocus::of( $base );

		// Variants of another theme are not in the form; keep their values.
		$stored = Meta::get( $id );
		if ( $stored ) {
			$focus = $focus->keeping_unknown( $stored, array_keys( Variants::all() ) );
		}

		Meta::save( $id, $focus );
		return $post;
	}
}
