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
 *
 * @param array $scripts Data about the scripts to register.
 * @return array Data about the scripts to register.
 */
function bp_friends_register_scripts( $scripts = array() ) {
	$scripts['bp-friends-script'] = array(
		'file'         => plugins_url( 'js/friends.js', __FILE__ ),
		'dependencies' => array(
			'bp-dynamic-widget-block-script',
			'wp-i18n',
		),
		'footer'       => true,
	);

	return $scripts;
}
add_filter( 'bp_core_register_common_scripts', 'bp_friends_register_scripts', 9, 1 );
