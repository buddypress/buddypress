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
			'notices-center/controller': {
				import: './src/js/blocks/bp-members/notices-center/notices-controller.js',
				library: {
					name: [ 'bp', 'noticesController' ],
					type: 'window',
				},
			},
            'active-members/index': './src/js/blocks/bp-members/active-members/active-members.js',
			'dynamic-members/index': './src/js/blocks/bp-members/dynamic-members/dynamic-members.js',
			'dynamic-widget/index': './src/js/blocks/bp-members/dynamic-widget/dynamic-members.js',
			'member/index': './src/js/blocks/bp-members/member/member.js',
			'members/index': './src/js/blocks/bp-members/members/members.js',
			'online-members/index': './src/js/blocks/bp-members/online-members/online-members.js',
			'close-notices-block/index': './src/js/blocks/bp-members/close-notices-block/sitewide-notices.js',
			'sitewide-notices/index': './src/js/blocks/bp-members/sitewide-notices/sitewide-notices.js',
			'notices-center/index': './src/js/blocks/bp-members/notices-center/sitewide-notices.js',
        },
		output: {
			filename: '[name].js',
			path: path.join( __dirname, '..', '..', '..', '..', 'src', 'bp-members', 'blocks' ),
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
				} else if ( request === '@buddypress/notices-controller' ) {
					return [ 'bp', 'noticesController' ];
				}
			},
			requestToHandle( request ) {
				if ( request === '@buddypress/block-components' ) {
					return 'bp-block-components';
				} else if ( request === '@buddypress/block-data' ) {
					return 'bp-block-data';
				} else if ( request === '@buddypress/dynamic-widget-block' ) {
					return 'bp-dynamic-widget-block';
				}  else if ( request === '@buddypress/notices-controller' ) {
					return 'bp-notices-controller';
				}
			}
		} )
	],
}
