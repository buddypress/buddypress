<?php
/*****
 * WordPress Abstraction
 *
 * The functions within this file will detect the version of WordPress you are running
 * and will alter the environment so BuddyPress can run regardless.
 *
 * The code below mostly contains function mappings. This code is subject to change once
 * the 3.0 WordPress version merge takes place.
 */

if ( !bp_core_is_multisite() ) {
	$wpdb->base_prefix = $wpdb->prefix;
	$wpdb->blogid = 1;
}

function bp_core_is_multisite() {
	if ( function_exists( 'is_multisite' ) )
		return is_multisite();

	if ( !function_exists( 'wpmu_signup_blog' ) )
		return false;

	return true;
}

function bp_core_get_status_sql( $prefix = false ) {
	if ( !bp_core_is_multisite() )
		return "{$prefix}user_status = 0";
	else
		return "{$prefix}spam = 0 AND {$prefix}deleted = 0 AND {$prefix}user_status = 0";
}

if ( !function_exists( 'get_blog_option' ) ) {
	function get_blog_option( $blog_id, $option_name, $default = false ) {
		return get_option( $option_name, $default );
	}
}

if ( !function_exists( 'add_blog_option' ) ) {
	function add_blog_option( $blog_id, $option_name, $option_value ) {
		return add_option( $option_name, $option_value );
	}
}

if ( !function_exists( 'update_blog_option' ) ) {
	function update_blog_option( $blog_id, $option_name, $option_value ) {
		return update_option( $option_name, $option_value );
	}
}

if ( !function_exists( 'switch_to_blog' ) ) {
	function switch_to_blog() {
		return 1;
	}
}

if ( !function_exists( 'restore_current_blog' ) ) {
	function restore_current_blog() {
		return 1;
	}
}

if ( !function_exists( 'get_blogs_of_user' ) ) {
	function get_blogs_of_user() {
		return false;
	}
}

if ( !function_exists( 'is_site_admin' ) ) {
	function is_site_admin( $user_id = false ) {
		if ( current_user_can( 'manage_options' ) )
			return true;

		return false;
	}
}

if ( !function_exists( 'get_current_user_id' ) ) {
	function get_current_user_id() {
		global $current_user;
		get_currentuserinfo();
		return $current_user->data->ID;
	}
}

if ( !function_exists( 'update_blog_status' ) ) {
	function update_blog_status() {
		return true;
	}
}
