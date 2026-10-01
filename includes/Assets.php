<?php
/**
 * Assets: the zoom stylesheet (front end and editor canvas), the block
 * editor script and the media library picker.
 *
 * @package RMD\FocalZoomPoint
 */

namespace RMD\FocalZoomPoint;

use RMD\FocalZoomPoint\Domain\FocalPoint;

defined( 'ABSPATH' ) || exit;

final class Assets {

	public const STYLE         = 'rmd-fzp';
	public const EDITOR_SCRIPT = 'rmd-fzp-editor';
	public const MEDIA_SCRIPT  = 'rmd-fzp-media';
	public const MEDIA_STYLE   = 'rmd-fzp-media';

	public static function init(): void {
		add_action( 'init', [ self::class, 'register' ] );
		// Front end and the editor canvas (iframe) alike.
		add_action( 'enqueue_block_assets', [ self::class, 'enqueue_style' ] );
		add_action( 'wp_enqueue_scripts', [ self::class, 'enqueue_style' ] );
		// Before block scripts (priority 10), so the override attribute is added
		// to blocks that themes register in their own editor scripts.
		add_action( 'enqueue_block_editor_assets', [ self::class, 'enqueue_editor' ], 5 );
		add_action( 'wp_enqueue_media', [ self::class, 'enqueue_media' ] );
		add_action( 'admin_enqueue_scripts', [ self::class, 'attachment_screen' ] );
	}

	public static function register(): void {
		wp_register_style( self::STYLE, RMD_FZP_URL . 'assets/css/focal-zoom.css', [], self::version( 'assets/css/focal-zoom.css' ) );

		wp_register_style( self::MEDIA_STYLE, RMD_FZP_URL . 'assets/css/media.css', [ self::STYLE ], self::version( 'assets/css/media.css' ) );
		wp_register_script(
			self::MEDIA_SCRIPT,
			RMD_FZP_URL . 'assets/js/media.js',
			[],
			self::version( 'assets/js/media.js' ),
			[ 'in_footer' => true ]
		);

		$asset = self::build_asset( 'build/editor.js' );
		wp_register_script(
			self::EDITOR_SCRIPT,
			RMD_FZP_URL . 'build/editor.js',
			$asset['dependencies'],
			$asset['version'],
			[ 'in_footer' => true ]
		);
		wp_set_script_translations( self::EDITOR_SCRIPT, 'rmd-focal-zoom-point', RMD_FZP_DIR . 'languages' );
	}

	public static function enqueue_style(): void {
		/**
		 * Filters whether the zoom stylesheet is loaded. Themes that ship the
		 * rules themselves can switch it off.
		 *
		 * @param bool $load Load assets/css/focal-zoom.css.
		 */
		if ( apply_filters( 'rmd_fzp_load_style', true ) ) {
			wp_enqueue_style( self::STYLE );
		}
	}

	public static function enqueue_editor(): void {
		wp_enqueue_script( self::EDITOR_SCRIPT );
		wp_add_inline_script(
			self::EDITOR_SCRIPT,
			'window.rmdFzpConfig = ' . wp_json_encode(
				[
					'blocks'  => Blocks::supported(),
					'minZoom' => FocalPoint::MIN_ZOOM,
					'maxZoom' => FocalPoint::MAX_ZOOM,
				]
			) . ';',
			'before'
		);
	}

	public static function enqueue_media(): void {
		wp_enqueue_style( self::MEDIA_STYLE );
		wp_enqueue_script( self::MEDIA_SCRIPT );
	}

	/**
	 * The attachment edit screen shows the field without loading the media
	 * modal.
	 *
	 * @param string $hook_suffix Current admin page.
	 */
	public static function attachment_screen( $hook_suffix ): void {
		$screen = get_current_screen();
		if ( 'post.php' === $hook_suffix && $screen && 'attachment' === $screen->post_type ) {
			self::enqueue_media();
		}
	}

	/**
	 * Cache-busting version: file modification time while SCRIPT_DEBUG is on,
	 * plugin version otherwise (stable URLs for page caches and CDNs).
	 */
	public static function version( string $relative_path ): string {
		if ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) {
			$file = RMD_FZP_DIR . $relative_path;
			if ( is_readable( $file ) ) {
				$mtime = filemtime( $file );
				if ( false !== $mtime ) {
					return (string) $mtime;
				}
			}
		}
		return RMD_FZP_VERSION;
	}

	/**
	 * Reads a wp-scripts generated *.asset.php file.
	 *
	 * @return array{dependencies: string[], version: string}
	 */
	public static function build_asset( string $relative_js_path ): array {
		$asset_file = RMD_FZP_DIR . preg_replace( '/\.js$/', '.asset.php', $relative_js_path );
		$asset      = is_readable( $asset_file ) ? include $asset_file : [];

		return [
			'dependencies' => is_array( $asset['dependencies'] ?? null ) ? $asset['dependencies'] : [],
			'version'      => (string) ( $asset['version'] ?? RMD_FZP_VERSION ),
		];
	}
}
