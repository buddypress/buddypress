<?php
/**
 * Deprecated Functions
 *
 * @package BuddyPress
 * @subpackage Core
 * @deprecated Since 1.7
 */

// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;


function xprofile_get_profile() {
	_deprecated_function( __FUNCTION__, '1.7' );
	bp_locate_template( array( 'profile/profile-loop.php' ), true );
}

function bp_get_profile_header() {
	_deprecated_function( __FUNCTION__, '1.7' );
	bp_locate_template( array( 'profile/profile-header.php' ), true );
}

function bp_exists( $component_name ) {
	_deprecated_function( __FUNCTION__, '1.7' );
	if ( function_exists( $component_name . '_install' ) )
		return true;

	return false;
}

function bp_get_plugin_sidebar() {
	_deprecated_function( __FUNCTION__, '1.7' );
	bp_locate_template( array( 'plugin-sidebar.php' ), true );
}

/**
 * On multiblog installations you must first allow themes to be activated and
 * show up on the theme selection screen. This function will let the BuddyPress
 * bundled themes show up on the root blog selection screen and bypass this
 * step. It also means that the themes won't show for selection on other blogs.
 *
 * @package BuddyPress Core
 */
function bp_core_allow_default_theme( $themes ) {
	_deprecated_function( __FUNCTION__, '1.7' );

	if ( !bp_current_user_can( 'bp_moderate' ) )
		return $themes;

	if ( bp_get_root_blog_id() != get_current_blog_id() )
		return $themes;

	if ( isset( $themes['bp-default'] ) )
		return $themes;

	$themes['bp-default'] = true;

	return $themes;
}
