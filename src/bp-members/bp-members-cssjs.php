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

	$scripts['bp-dynamic-members-script'] = array(
		'file'         => plugins_url( 'blocks/dynamic-widget/index.js', __FILE__ ),
		'dependencies' => $asset['dependencies'],
		'footer'       => true,
	);

	return $scripts;
}
add_filter( 'bp_core_register_common_scripts', 'bp_members_register_scripts', 9, 1 );
