<?php
/**
 * Media → Focal Point & Zoom: how editors use the plugin, followed by the
 * theme integration guide for developers. The guide is rendered from
 * docs/theme-integration.md, the same file GitHub shows.
 *
 * @package RMD\FocalZoomPoint
 */

namespace RMD\FocalZoomPoint\Admin;

use RMD\FocalZoomPoint\Domain\Markdown;

defined( 'ABSPATH' ) || exit;

final class HelpPage {

	public const SLUG = 'rmd-fzp-help';

	public static function init(): void {
		add_action( 'admin_menu', [ self::class, 'menu' ] );
		add_action( 'admin_enqueue_scripts', [ self::class, 'styles' ] );
		add_filter( 'plugin_action_links_' . RMD_FZP_BASENAME, [ self::class, 'action_link' ] );
	}

	public static function menu(): void {
		add_submenu_page(
			'upload.php',
			__( 'Focal Point & Zoom', 'rmd-focal-zoom-point' ),
			__( 'Focal Point & Zoom', 'rmd-focal-zoom-point' ),
			'upload_files',
			self::SLUG,
			[ self::class, 'render' ]
		);
	}

	/**
	 * @param string[] $links Plugin action links.
	 * @return string[]
	 */
	public static function action_link( $links ): array {
		$links[] = '<a href="' . esc_url( admin_url( 'upload.php?page=' . self::SLUG ) ) . '">' . esc_html__( 'Help', 'rmd-focal-zoom-point' ) . '</a>';
		return $links;
	}

	public static function styles( string $hook ): void {
		if ( false === strpos( $hook, self::SLUG ) ) {
			return;
		}
		wp_add_inline_style(
			'wp-admin',
			'.rmd-fzp-help{max-width:60em}.rmd-fzp-help h2{margin-top:2em;padding-top:1em;border-top:1px solid #dcdcde}.rmd-fzp-help h2:first-of-type{border:0;margin-top:1em;padding-top:0}.rmd-fzp-help table{margin:1em 0}.rmd-fzp-help td,.rmd-fzp-help th{vertical-align:top}.rmd-fzp-help pre{background:#f6f7f7;border:1px solid #dcdcde;padding:12px 14px;overflow:auto}.rmd-fzp-help pre code{background:none;padding:0}.rmd-fzp-help blockquote{margin:1em 0;padding:.2em 1em;border-left:4px solid #72aee6;background:#f0f6fc}.rmd-fzp-help__dev{margin-top:3em}.rmd-fzp-help__dev ul{list-style:disc;padding-left:2em}.rmd-fzp-help__dev ol{list-style:decimal;padding-left:2em}'
		);
	}

	public static function render(): void {
		if ( ! current_user_can( 'upload_files' ) ) {
			wp_die( esc_html__( 'You are not allowed to view this page.', 'rmd-focal-zoom-point' ) );
		}

		echo '<div class="wrap rmd-fzp-help"><h1>' . esc_html__( 'Focal Point & Zoom', 'rmd-focal-zoom-point' ) . '</h1>';

		echo '<h2>' . esc_html__( 'Using it', 'rmd-focal-zoom-point' ) . '</h2>';
		echo '<p>' . esc_html__( 'Themes often crop images: a wide banner, a square card, a round portrait. The focal point marks the part of an image that must stay visible; zoom enlarges the image around it.', 'rmd-focal-zoom-point' ) . '</p>';
		echo '<ul class="ul-disc">';
		echo '<li>' . esc_html__( 'Media library: open an image and use the field "Focal point & zoom". Click the important part or drag the dot, set the zoom and check the previews. The values are saved with the image and apply wherever it is used.', 'rmd-focal-zoom-point' ) . '</li>';
		echo '<li>' . esc_html__( 'Block editor: select an image block (or a block your theme supports) and open the panel "Focal point & zoom" in the sidebar. Changes there are saved to the image right away. Switch on "Adjust for this block only" to use different values in this one place.', 'rmd-focal-zoom-point' ) . '</li>';
		echo '<li>' . esc_html__( 'Zoom also lets you move the focus in a direction where the image would otherwise not reach, e.g. sideways in a tall portrait shown as a circle.', 'rmd-focal-zoom-point' ) . '</li>';
		echo '<li>' . esc_html__( 'Images that are not cropped look the same as before.', 'rmd-focal-zoom-point' ) . '</li>';
		echo '</ul>';

		echo '<div class="rmd-fzp-help__dev">';
		echo '<p><em>' . esc_html__( 'The following guide for theme developers is only available in English.', 'rmd-focal-zoom-point' ) . '</em></p>';
		$guide = RMD_FZP_DIR . 'docs/theme-integration.md';
		if ( is_readable( $guide ) ) {
			// Markdown::to_html() escapes all text and only emits its own tags.
			echo Markdown::to_html( (string) file_get_contents( $guide ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped, WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		} else {
			echo '<p>' . esc_html__( 'The theme integration guide (docs/theme-integration.md) is missing from this installation.', 'rmd-focal-zoom-point' ) . '</p>';
		}
		echo '</div></div>';
	}
}
