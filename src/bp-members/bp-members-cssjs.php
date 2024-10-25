<?php
/**
 * BP Members component CSS/JS.
 *
 * @package BuddyPress
 * @subpackage MembersScripts
 * @since 9.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Registers the script to manage the dynamic part of the Dynamic Members widget/block.
 *
 * @since 9.0.0
 * @since 12.0.0 Uses the `@wordpress/scripts` `index.asset.php` generated file to get dependencies.
 *
 * @param array $scripts Data about the scripts to register.
 * @return array Data about the scripts to register.
 */
function bp_members_register_scripts( $scripts = array() ) {
	$path         = sprintf( '/%1$s/%2$s/notices/', bp_rest_namespace(), bp_rest_version() );
	$notices_data = array(
		'path'        => ltrim( $path, '/' ),
		'dismissPath' => ltrim( $path, '/' ) . 'dismiss',
		'root'        => esc_url_raw( get_rest_url() ),
		'nonce'       => wp_create_nonce( 'wp_rest' ),
	);

	$scripts['bp-notices-controller'] = array(
		'file'         => plugins_url( 'blocks/notices-center/controller.js', __FILE__ ),
		'dependencies' => array(),
		'footer'       => true,
		'localize'     => array(
			'name' => 'bpNoticesCenterSettings',
			'data' => $notices_data,
		),
	);

	if ( bp_support_blocks() ) {
		$asset      = array(
			'dependencies' => array(),
			'version'      => ''
		);
		$asset_path = trailingslashit( dirname( __FILE__ ) ) . 'blocks/dynamic-widget/index.asset.php';

		if ( file_exists( $asset_path ) ) {
			$asset = require $asset_path;
		}

		$scripts['bp-dynamic-members-script'] = array(
			'file'         => plugins_url( 'blocks/dynamic-widget/index.js', __FILE__ ),
			'dependencies' => $asset['dependencies'],
			'version'      => $asset['version'],
			'footer'       => true,
		);

		$cnb_asset      = array(
			'dependencies' => array(),
			'version'      => ''
		);
		$cnb_asset_path = trailingslashit( dirname( __FILE__ ) ) . 'blocks/close-notices-block/index.asset.php';

		if ( file_exists( $cnb_asset_path ) ) {
			$cnb_asset = require $cnb_asset_path;
		}

		$scripts['bp-sitewide-notices-script'] = array(
			'file'         => plugins_url( 'blocks/close-notices-block/index.js', __FILE__ ),
			'dependencies' => $cnb_asset['dependencies'],
			'version'      => $cnb_asset['version'],
			'footer'       => true,
		);
	}

	$nc_asset      = array(
		'dependencies' => array(),
		'version'      => ''
	);
	$nc_asset_path = trailingslashit( dirname( __FILE__ ) ) . 'blocks/notices-center/index.asset.php';

	if ( file_exists( $nc_asset_path ) ) {
		$nc_asset = require $nc_asset_path;
	}

	$scripts['bp-notices-center-script'] = array(
		'file'         => plugins_url( 'blocks/notices-center/index.js', __FILE__ ),
		'dependencies' => $nc_asset['dependencies'],
		'version'      => $nc_asset['version'],
		'footer'       => true,
	);

	return $scripts;
}
add_filter( 'bp_core_register_common_scripts', 'bp_members_register_scripts', 9, 1 );
