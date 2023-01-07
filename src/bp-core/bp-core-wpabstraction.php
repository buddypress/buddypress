<?php
/**
 * WordPress Abstraction.
 *
 * The functions within this file will detect the version of WordPress you are
 * running and will alter the environment so BuddyPress can run regardless.
 *
 * The code below mostly contains function mappings. This file is subject to
 * change at any time.
 *
 * @package BuddyPress
 * @subpackage WPAbstraction
 * @since 1.2.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Parse the WordPress core version number into the major release.
 *
 * @since 1.5.2
 *
 * @global string $wp_version WP Version number.
 *
 * @return double
 */
function bp_get_major_wp_version() {
	global $wp_version;

	return (float) $wp_version;
}

/*
 * Only add MS-specific abstraction functions if WordPress is not in multisite mode.
 */
if ( !is_multisite() ) {
	global $wpdb;

	$wpdb->base_prefix = $wpdb->prefix;
	$wpdb->blogid      = BP_ROOT_BLOG;

	if ( !function_exists( 'get_blog_option' ) ) {

		/**
		 * Retrieve blog option.
		 *
		 * @since 1.0.0
		 *
		 * @see get_blog_option()
		 *
		 * @param int    $blog_id     Blog ID to fetch for. Not used.
		 * @param string $option_name Option name to fetch.
		 * @param bool   $default     Whether or not default.
		 * @return mixed
		 */
		function get_blog_option( $blog_id, $option_name, $default = false ) {
			return get_option( $option_name, $default );
		}
	}

	if ( ! function_exists( 'add_blog_option' ) ) {

		/**
		 * Add blog option.
		 *
		 * @since 1.2.0
		 *
		 * @see add_blog_option()
		 *
		 * @param int    $blog_id      Blog ID to add for. Not used.
		 * @param string $option_name  Option name to add.
		 * @param mixed  $option_value Option value to add.
		 * @return mixed
		 */
		function add_blog_option( $blog_id, $option_name, $option_value ) {
			return add_option( $option_name, $option_value );
		}
	}

	if ( !function_exists( 'update_blog_option' ) ) {

		/**
		 * Update blog option.
		 *
		 * @since 1.2.0
		 *
		 * @see update_blog_option()
		 *
		 * @param int    $blog_id     Blog ID to update for. Not used.
		 * @param string $option_name Option name to update.
		 * @param mixed  $value       Option value to update.
		 * @return mixed
		 */
		function update_blog_option( $blog_id, $option_name, $value ) {
			return update_option( $option_name, $value );
		}
	}

	if ( !function_exists( 'delete_blog_option' ) ) {

		/**
		 * Delete blog option.
		 *
		 * @since 1.5.0
		 *
		 * @see delete_blog_option()
		 *
		 * @param int    $blog_id     Blog ID to delete for. Not used.
		 * @param string $option_name Option name to delete.
		 * @return mixed
		 */
		function delete_blog_option( $blog_id, $option_name ) {
			return delete_option( $option_name );
		}
	}

	if ( !function_exists( 'switch_to_blog' ) ) {

		/**
		 * Switch to specified blog.
		 *
		 * @since 1.2.0
		 *
		 * @see switch_to_blog()
		 *
		 * @param mixed $new_blog   New blog to switch to. Not used.
		 * @param null  $deprecated Whether or not deprecated. Not used.
		 * @return int
		 */
		function switch_to_blog( $new_blog, $deprecated = null ) {
			return bp_get_root_blog_id();
		}
	}

	if ( !function_exists( 'restore_current_blog' ) ) {

		/**
		 * Restore current blog.
		 *
		 * @since 1.2.0
		 *
		 * @see restore_current_blog()
		 *
		 * @return int
		 */
		function restore_current_blog() {
			return bp_get_root_blog_id();
		}
	}

	if ( !function_exists( 'get_blogs_of_user' ) ) {

		/**
		 * Retrive blogs associated with user.
		 *
		 * @since 1.2.0
		 *
		 * @see get_blogs_of_user()
		 *
		 * @param int  $user_id ID of the user. Not used.
		 * @param bool $all     Whether or not to return all. Not used.
		 * @return bool
		 */
		function get_blogs_of_user( $user_id, $all = false ) {
			return false;
		}
	}

	if ( !function_exists( 'update_blog_status' ) ) {

		/**
		 * Whether or not to update blog status.
		 *
		 * @since 1.2.0
		 *
		 * @see update_blog_status()
		 *
		 * @param int    $blog_id    Blog to update status for. Not used.
		 * @param mixed  $pref       Preference. Not used.
		 * @param string $value      Value. Not used.
		 * @param null   $deprecated Whether or not deprecated. Not used.
		 * @return bool
		 */
		function update_blog_status( $blog_id, $pref, $value, $deprecated = null ) {
			return true;
		}
	}

	if ( !function_exists( 'is_subdomain_install' ) ) {

		/**
		 * Whether or not if subdomain install.
		 *
		 * @since 1.2.5.1
		 *
		 * @see is_subdomain_install()
		 *
		 * @return bool
		 */
		function is_subdomain_install() {
			if ( ( defined( 'VHOST' ) && 'yes' == VHOST ) || ( defined( 'SUBDOMAIN_INSTALL' ) && SUBDOMAIN_INSTALL ) )
				return true;

			return false;
		}
	}
}

/**
 * Get SQL chunk for filtering spam users from member queries.
 *
 * @internal
 * @todo Why is this function defined in this file?
 *
 * @param string|bool $prefix Global table prefix.
 * @return string SQL chunk.
 */
function bp_core_get_status_sql( $prefix = false ) {
	if ( !is_multisite() )
		return "{$prefix}user_status = 0";
	else
		return "{$prefix}spam = 0 AND {$prefix}deleted = 0 AND {$prefix}user_status = 0";
}

/**
 * Multibyte encoding fallback functions.
 *
 * The PHP multibyte encoding extension is not enabled by default. In cases where it is not enabled,
 * these functions provide a fallback.
 *
 * Borrowed from MediaWiki, under the GPLv2. Thanks!
 */
if ( !function_exists( 'mb_strlen' ) ) {

	/**
	 * Fallback implementation of mb_strlen(), hardcoded to UTF-8.
	 *
	 * @param string $str String to be measured.
	 * @param string $enc Optional. Encoding type. Ignored.
	 * @return int String length.
	 */
	function mb_strlen( $str, $enc = '' ) {
		$counts = count_chars( $str );
		$total = 0;

		// Count ASCII bytes.
		for( $i = 0; $i < 0x80; $i++ ) {
			$total += $counts[$i];
		}

		// Count multibyte sequence heads.
		for( $i = 0xc0; $i < 0xff; $i++ ) {
			$total += $counts[$i];
		}
		return $total;
	}
}

if ( !function_exists( 'mb_strpos' ) ) {

	/**
	 * Fallback implementation of mb_strpos(), hardcoded to UTF-8.
	 *
	 * @param string $haystack String to search in.
	 * @param string $needle String to search for.
	 * @param int    $offset Optional. Start position for the search. Default: 0.
	 * @param string $encoding Optional. Encoding type. Ignored.
	 * @return int|false Position of needle in haystack if found, else false.
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
	 * Fallback implementation of mb_strrpos(), hardcoded to UTF-8.
	 *
	 * @param string $haystack String to search in.
	 * @param string $needle String to search for.
	 * @param int    $offset Optional. Start position for the search. Default: 0.
	 * @param string $encoding Optional. Encoding type. Ignored.
	 * @return string|false Position of last needle in haystack if found, else false.
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

/**
 * Catch the new site data for a later use.
 *
 * @since 6.0.0
 */
function bp_catch_site_data( $errors = null, $data = array() ) {
	buddypress()->new_site_data = $data;
}
add_action( 'wp_validate_site_data', 'bp_catch_site_data', 10, 2 );

/**
 * Fires a BuddyPress hook when a new WordPress site is inserted into the database.
 *
 * This hook makes sure BuddyPress is back compatible with WordPress versions < 5.1.0.
 *
 * @since 6.0.0
 *
 * @param int|WP_Site $site            The Site ID or the WP Site object.
 * @param int|array   $args_or_user_id An array of Site arguments or the User ID.
 * @param string      $domain          Site domain.
 * @param string      $path            Site path.
 * @param int         $network_id      Network ID. Only relevant on multi-network installations.
 * @param array       $meta            Meta data. Used to set initial site options.
 */
function bp_insert_site( $site, $args_or_user_id = null, $domain = '', $path = '', $network_id = 0, $meta = array() ) {
	if ( $site instanceof WP_Site ) {
		$bp         = buddypress();
		$site_id    = $site->id;
		$domain     = $site->domain;
		$path       = $site->path;
		$network_id = $site->network_id;
		$args       = (array) $args_or_user_id;

		$user_id = 0;
		if ( isset( $args['user_id'] ) && $args['user_id'] ) {
			$user_id = (int) $args['user_id'];
		}

		$meta = array();
		if ( isset( $args['options'] ) && $args['options'] ) {
			$meta = (array) $args['options'];

			if ( ! array_key_exists( 'WPLANG', $meta ) ) {
				$meta['WPLANG'] = get_network_option( $site->network_id, 'WPLANG' );
			}

			if ( isset( $bp->new_site_data ) ) {
				$meta = array_merge( $bp->new_site_data, $meta );
			}
		}
	} else {
		$site_id = $site;
		$user_id = (int) $args_or_user_id;
	}

	/**
	 * Fires when a new WordPress site has been inserted into the database.
	 *
	 * @since 6.0.0
	 *
	 * @param int    $site_id    Site ID.
	 * @param int    $user_id    User ID.
	 * @param string $domain     Site domain.
	 * @param string $path       Site path.
	 * @param int    $network_id Network ID. Only relevant on multi-network installations.
	 * @param array  $meta       Meta data. Used to set initial site options.
	 */
	do_action( 'bp_insert_site', $site_id, $user_id, $domain, $path, $network_id, $meta );
}
add_action( 'wp_initialize_site', 'bp_insert_site' );

/**
 * Makes sure the `bp_delete_site` hook is fired if site's deletion
 * was performed without dropping tables.
 *
 * @since 6.0.0
 *
 * @param WP_Site $site The site object.
 */
function bp_delete_site_no_tables_drop( $site ) {
	if ( isset( $site->deleted ) && 1 === (int) $site->deleted ) {
		return bp_delete_site( $site->id, false );
	}
}
add_action( 'wp_update_site', 'bp_delete_site_no_tables_drop', 10, 1 );

/**
 * Fires a BuddyPress hook when a new WordPress site is deleted.
 *
 * This hook makes sure BuddyPress is back compatible with WordPress versions < 5.1.0.
 *
 * @since 6.0.0
 *
 * @param int|WP_Error $site_id_or_error A WP Error object or the site ID.
 * @param bool|WP_Site $drop_or_site     A WP Site object or a boolean to inform whether site's table should be dropped.
 */
function bp_delete_site( $site_id_or_error, $drop_or_site = false ) {
	if ( $drop_or_site instanceof WP_Site ) {
		if ( ! empty( $site_id_or_error->errors ) ) {
			return;
		}

		$site_id = (int) $drop_or_site->id;
		$drop    = true;
	} else {
		$site_id = (int) $site_id_or_error;
		$drop    = (bool) $drop_or_site;
	}

	/**
	 * Fires when a WordPress site is deleted.
	 *
	 * @since 6.0.0
	 *
	 * @param int  $site_id The site ID.
	 * @param bool $drop    True if site's table should be dropped. Default is false.
	 */
	do_action( 'bp_delete_site', $site_id, $drop );
}
add_action( 'wp_validate_site_deletion', 'bp_delete_site', 10, 2 );
