const path = require( 'path' );

/**
 * WordPress Dependencies
 */
const defaultConfig = require( '@wordpress/scripts/config/webpack.config.js' );
const DependencyExtractionWebpackPlugin = require( '@wordpress/dependency-extraction-webpack-plugin' );

module.exports = {
    ...defaultConfig,
	...{
        entry: {
            "block-data/index": {
				import: './src/js/blocks/bp-core/block-assets/block-data.js',
				library: {
					name: [ 'bp', 'blockData' ],
					type: 'window',
				},
			},
			"block-components/index": {
				import: './src/js/blocks/bp-core/block-components/block-components.js',
				library: {
					name: [ 'bp', 'blockComponents' ],
					type: 'window',
				},
			},
			"block-collection/index": './src/js/blocks/bp-core/block-collection/block-collection.js',
			"login-form/index": './src/js/blocks/bp-core/login-form/login-form.js',
			"primary-nav/index": './src/js/blocks/bp-core/primary-nav/primary-nav.js',
			"dynamic-widget-block/index": {
				import: './src/js/blocks/bp-core/dynamic-widget/dynamic-widget-block.js',
				library: {
					name: [ 'bp', 'dynamicWidgetBlock' ],
					type: 'window',
				},
			},
        },
		output: {
			filename: '[name].js',
			path: path.join( __dirname, '..', '..', '..', '..', 'src', 'bp-core', 'blocks' ),
		}
    },
	plugins: [
		...defaultConfig.plugins.filter(
			( plugin ) =>
				plugin.constructor.name !== 'DependencyExtractionWebpackPlugin'
		),
		new DependencyExtractionWebpackPlugin( {
			requestToExternal( request ) {
				if ( request === '@buddypress/block-components' ) {
					return [ 'bp', 'blockComponents' ];
				} else if ( request === '@buddypress/block-data' ) {
					return [ 'bp', 'blockData' ];
				} else if ( request === '@buddypress/dynamic-widget-block' ) {
					return [ 'bp', 'dynamicWidgetBlock' ];
				}
			},
			requestToHandle( request ) {
				if ( request === '@buddypress/block-components' ) {
					return 'bp-block-components';
				} else if ( request === '@buddypress/block-data' ) {
					return 'bp-block-data';
				} else if ( request === '@buddypress/dynamic-widget-block' ) {
					return 'bp-dynamic-widget-block';
				}
			}
		} )
	],
}
