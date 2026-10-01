<?php
/**
 * Plugin Name:       RMD Focal Zoom Point
 * Plugin URI:        https://github.com/reicheltmediadesign/wordpress-rmd-focal-zoom-point
 * Description:       Set a focal point and zoom on every image in the media library or right where it is used. Cropped images keep their important part in view, in any theme.
 * Version:           0.1.1
 * Requires at least: 6.8
 * Requires PHP:      8.1
 * Author:            Philipp Reichelt, reichelt media.design
 * Author URI:        https://reicheltmedia.design
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       rmd-focal-zoom-point
 * Domain Path:       /languages
 * Update URI:        https://github.com/reicheltmediadesign/wordpress-rmd-focal-zoom-point
 *
 * @package RMD\FocalZoomPoint
 */

// This file must stay parseable by PHP 7.x so the version notice below can be shown instead of a fatal error.

defined( 'ABSPATH' ) || exit;

define( 'RMD_FZP_VERSION', '0.1.1' );
define( 'RMD_FZP_FILE', __FILE__ );
define( 'RMD_FZP_DIR', plugin_dir_path( __FILE__ ) );
define( 'RMD_FZP_URL', plugin_dir_url( __FILE__ ) );
define( 'RMD_FZP_BASENAME', plugin_basename( __FILE__ ) );

if ( version_compare( PHP_VERSION, '8.1', '<' ) ) {
	add_action(
		'admin_notices',
		function () {
			echo '<div class="notice notice-error"><p>';
			echo esc_html(
				sprintf(
					/* translators: 1: required PHP version, 2: current PHP version */
					__( 'RMD Focal Zoom Point requires PHP %1$s or newer. This site runs PHP %2$s, so the plugin stays inactive.', 'rmd-focal-zoom-point' ),
					'8.1',
					PHP_VERSION
				)
			);
			echo '</p></div>';
		}
	);
	return;
}

spl_autoload_register(
	function ( $class_name ) {
		$prefix = 'RMD\\FocalZoomPoint\\';
		if ( 0 !== strncmp( $class_name, $prefix, strlen( $prefix ) ) ) {
			return;
		}
		$relative = substr( $class_name, strlen( $prefix ) );
		$file     = RMD_FZP_DIR . 'includes/' . str_replace( '\\', '/', $relative ) . '.php';
		if ( is_readable( $file ) ) {
			require $file;
		}
	}
);

if ( is_readable( RMD_FZP_DIR . 'vendor/autoload.php' ) ) {
	require RMD_FZP_DIR . 'vendor/autoload.php';
}

require RMD_FZP_DIR . 'includes/functions.php';

RMD\FocalZoomPoint\Plugin::boot();
