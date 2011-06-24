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

/**
 * Only add abstraction functions if WordPress is not in multisite mode
 */
if ( !is_multisite() ) {
	global $wpdb;

	$wpdb->base_prefix = $wpdb->prefix;
	$wpdb->blogid      = BP_ROOT_BLOG;

	if ( !function_exists( 'get_blog_option' ) ) {
		function get_blog_option( $blog_id, $option_name, $default = false ) {
			return get_option( $option_name, $default );
		}
	}
	
	if ( !function_exists( 'update_blog_option' ) ) {
		function update_blog_option( $blog_id, $option_name, $value ) {
			return update_option( $option_name, $value );
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

	if ( !function_exists( 'is_subdomain_install' ) ) {
		function is_subdomain_install() {
			if ( ( defined( 'VHOST' ) && 'yes' == VHOST ) || ( defined( 'SUBDOMAIN_INSTALL' ) && SUBDOMAIN_INSTALL ) )
				return true;

			return false;
		}
	}
}

function bp_core_get_status_sql( $prefix = false ) {
	if ( !is_multisite() )
		return "{$prefix}user_status = 0";
	else
		return "{$prefix}spam = 0 AND {$prefix}deleted = 0 AND {$prefix}user_status = 0";
}
?>