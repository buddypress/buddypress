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
 *
 * @param array $scripts Data about the scripts to register.
 * @return array Data about the scripts to register.
 */
function bp_members_register_scripts( $scripts = array() ) {
	$scripts['bp-dynamic-members-script'] = array(
		'footer'       => true,
		'file'         => plugins_url( 'js/dynamic-members.js', __FILE__ ),
		'dependencies' => array(
			'bp-dynamic-widget-block-script',
			'wp-i18n',
		),
	);

	return $scripts;
}
add_filter( 'bp_core_register_common_scripts', 'bp_members_register_scripts', 9, 1 );
