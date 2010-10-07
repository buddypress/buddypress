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

if ( !function_exists( 'update_blog_status' ) ) {
	function update_blog_status() {
		return true;
	}
}

if ( !function_exists( 'wpmu_validate_user_signup' ) ) {
	function wpmu_validate_user_signup( $user_name, $user_email ) {
		global $wpdb;

		$errors = new WP_Error();

		$user_email = sanitize_email( $user_email );

		if ( empty( $user_name ) )
		   	$errors->add('user_name', __("Please enter a username"));

		$maybe = array();
		preg_match( "/[a-z0-9]+/", $user_name, $maybe );

		$illegal_names = get_site_option( "illegal_names" );
		if( is_array( $illegal_names ) == false ) {
			$illegal_names = array(  "www", "web", "root", "admin", "main", "invite", "administrator" );
			add_site_option( "illegal_names", $illegal_names );
		}

		if ( !validate_username( $user_name ) || in_array( $user_name, $illegal_names ) == true || $user_name != $maybe[0] ) {
		    $errors->add('user_name', __("Only lowercase letters and numbers allowed"));
		}

		if( strlen( $user_name ) < 4 ) {
		    $errors->add('user_name',  __("Username must be at least 4 characters"));
		}

		if ( strpos( " " . $user_name, "_" ) != false )
			$errors->add('user_name', __("Sorry, usernames may not contain the character '_'!"));

		// all numeric?
		$match = array();
		preg_match( '/[0-9]*/', $user_name, $match );
		if ( $match[0] == $user_name )
			$errors->add('user_name', __("Sorry, usernames must have letters too!"));

		if ( !is_email( $user_email ) )
			$errors->add('user_email', __("Please check your email address."));

		$limited_email_domains = get_site_option( 'limited_email_domains' );
		if ( is_array( $limited_email_domains ) && empty( $limited_email_domains ) == false ) {
			$emaildomain = substr( $user_email, 1 + strpos( $user_email, '@' ) );
			if( in_array( $emaildomain, $limited_email_domains ) == false ) {
				$errors->add('user_email', __("Sorry, that email address is not allowed!"));
			}
		}

		// Check if the username has been used already.
		if ( username_exists($user_name) )
			$errors->add('user_name', __("Sorry, that username already exists!"));

		// Check if the email address has been used already.
		if ( email_exists($user_email) )
			$errors->add('user_email', __("Sorry, that email address is already used!"));

		$result = array('user_name' => $user_name, 'user_email' => $user_email,	'errors' => $errors);

		return apply_filters('wpmu_validate_user_signup', $result);
	}
}
?>