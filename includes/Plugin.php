<?php
/**
 * Plugin bootstrap: wires all services to WordPress hooks.
 *
 * @package RMD\FocalZoomPoint
 */

namespace RMD\FocalZoomPoint;

defined( 'ABSPATH' ) || exit;

final class Plugin {

	private static bool $booted = false;

	public static function boot(): void {
		if ( self::$booted ) {
			return;
		}
		self::$booted = true;

		add_action( 'init', [ self::class, 'load_textdomain' ], 1 );

		Meta::init();
		Blocks::init();
		Render::init();
		Assets::init();
		Updater::init();

		if ( is_admin() ) {
			Admin\MediaField::init();
			Admin\HelpPage::init();
		}
	}

	public static function load_textdomain(): void {
		load_plugin_textdomain( 'rmd-focal-zoom-point', false, dirname( RMD_FZP_BASENAME ) . '/languages' );
	}
}
