<?php
/**
 * Clean-up when the plugin is deleted.
 *
 * The focal points and zoom values stay on the images (post meta _rmd_fzp):
 * they are part of the content, and reinstalling the plugin brings them back.
 * Only the update checker's cached state is removed.
 *
 * @package RMD\FocalZoomPoint
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

if ( is_multisite() ) {
	foreach ( get_sites( [ 'fields' => 'ids' ] ) as $rmd_fzp_site_id ) {
		switch_to_blog( (int) $rmd_fzp_site_id );
		delete_option( 'external_updates-rmd-focal-zoom-point' );
		restore_current_blog();
	}
} else {
	delete_option( 'external_updates-rmd-focal-zoom-point' );
}
