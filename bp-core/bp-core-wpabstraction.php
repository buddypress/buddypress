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

// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

/**
 * Parso the WordPress core version number into the major release
 *
 * @since BuddyPress (1.5.2)
 * @global string $wp_version
 * @return string 
 */
function bp_get_major_wp_version() {
	global $wp_version;
	
	return substr( $wp_version, 0, ( strpos( $wp_version, '.' ) + 2 ) );
}

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

	if ( !function_exists( 'delete_blog_option' ) ) {
		function delete_blog_option( $blog_id, $option_name ) {
			return delete_option( $option_name );
		}
	}

	if ( !function_exists( 'switch_to_blog' ) ) {
		function switch_to_blog() {
			return bp_get_root_blog_id();
		}
	}

	if ( !function_exists( 'restore_current_blog' ) ) {
		function restore_current_blog() {
			return bp_get_root_blog_id();
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

/**
 * Multibyte encoding fallback functions
 *
 * The PHP multibyte encoding extension is not enabled by default. In cases where it is not enabled,
 * these functions provide a fallback.
 *
 * Borrowed from MediaWiki, under the GPLv2. Thanks!
 */
if ( !function_exists( 'mb_strlen' ) ) {
	/**
	 * Fallback implementation of mb_strlen, hardcoded to UTF-8.
	 * @param string $str
	 * @param string $enc optional encoding; ignored
	 * @return int
	 */
	function mb_strlen( $str, $enc = '' ) {
		$counts = count_chars( $str );
		$total = 0;

		// Count ASCII bytes
		for( $i = 0; $i < 0x80; $i++ ) {
			$total += $counts[$i];
		}

		// Count multibyte sequence heads
		for( $i = 0xc0; $i < 0xff; $i++ ) {
			$total += $counts[$i];
		}
		return $total;
	}
}

if ( !function_exists( 'mb_strpos' ) ) {
	/**
	 * Fallback implementation of mb_strpos, hardcoded to UTF-8.
	 * @param $haystack String
	 * @param $needle String
	 * @param $offset String: optional start position
	 * @param $encoding String: optional encoding; ignored
	 * @return int
	 */
	function mb_strpos( $haystack, $needle, $offset = 0, $encoding = '' ) {
		$needle = preg_quote( $needle, '/' );

		$ar = array();
		preg_match( '/' . $needle . '/u', $haystack, $ar, PREG_OFFSET_CAPTURE, $offset );

		if( isset( $ar[0][1] ) ) {
			return $ar[0][1];
		} else {
			return false;
		}
	}
}

if ( !function_exists( 'mb_strrpos' ) ) {
	/**
	 * Fallback implementation of mb_strrpos, hardcoded to UTF-8.
	 * @param $haystack String
	 * @param $needle String
	 * @param $offset String: optional start position
	 * @param $encoding String: optional encoding; ignored
	 * @return int
	 */
	function mb_strrpos( $haystack, $needle, $offset = 0, $encoding = '' ) {
		$needle = preg_quote( $needle, '/' );

		$ar = array();
		preg_match_all( '/' . $needle . '/u', $haystack, $ar, PREG_OFFSET_CAPTURE, $offset );

		if( isset( $ar[0] ) && count( $ar[0] ) > 0 &&
			isset( $ar[0][count( $ar[0] ) - 1][1] ) ) {
			return $ar[0][count( $ar[0] ) - 1][1];
		} else {
			return false;
		}
	}
}

?>