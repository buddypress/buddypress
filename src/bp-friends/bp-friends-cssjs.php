<?php
/**
 * BP Friends component CSS/JS.
 *
 * @package BuddyPress
 * @subpackage FriendsScripts
 * @since 9.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Registers the script to manage the dynamic part of the Friends widget/block.
 *
 * @since 9.0.0
 * @since 12.0.0 Uses the `@wordpress/scripts` `index.asset.php` generated file to get dependencies.
 *
 * @param array $scripts Data about the scripts to register.
 * @return array Data about the scripts to register.
 */
function bp_friends_register_scripts( $scripts = array() ) {
	if ( ! bp_support_blocks() ) {
		return $scripts;
	}

	$asset      = array(
		'dependencies' => array(),
		'version'      => ''
	);
	$asset_path = trailingslashit( dirname( __FILE__ ) ) . 'blocks/dynamic-widget/index.asset.php';

	if ( file_exists( $asset_path ) ) {
		$asset = require $asset_path;
	}

	$scripts['bp-friends-script'] = array(
		'file'         => plugins_url( 'blocks/dynamic-widget/index.js', __FILE__ ),
		'dependencies' => $asset['dependencies'],
		'footer'       => true,
	);

	return $scripts;
}
add_filter( 'bp_core_register_common_scripts', 'bp_friends_register_scripts', 9, 1 );
