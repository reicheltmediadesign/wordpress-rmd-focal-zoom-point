/**
 * The @wordpress/scripts default config with the block editor integration as
 * its only entry (build/editor.js).
 */
const path = require( 'path' );
const defaultConfig = require( '@wordpress/scripts/config/webpack.config' );

module.exports = {
	...defaultConfig,
	entry: {
		editor: path.resolve( __dirname, 'src/editor/index.js' ),
	},
};
