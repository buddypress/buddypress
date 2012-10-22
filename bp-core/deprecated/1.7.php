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
