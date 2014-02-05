<?php

/**
 * BuddyPress Common Functions.
 *
 * @package BuddyPress
 * @subpackage Functions
 */

// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

/** Versions ******************************************************************/

/**
 * Output the BuddyPress version.
 *
 * @since BuddyPress (1.6.0)
 *
 * @uses bp_get_version() To get the BuddyPress version.
 */
function bp_version() {
	echo bp_get_version();
}
	/**
	 * Return the BuddyPress version.
	 *
	 * @since BuddyPress (1.6.0)
	 *
	 * @return string The BuddyPress version.
	 */
	function bp_get_version() {
		return buddypress()->version;
	}

/**
 * Output the BuddyPress database version.
 *
 * @since BuddyPress (1.6.0)
 *
 * @uses bp_get_db_version() To get the BuddyPress database version.
 */
function bp_db_version() {
	echo bp_get_db_version();
}
	/**
	 * Return the BuddyPress database version.
	 *
	 * @since BuddyPress (1.6.0)
	 * @return string The BuddyPress database version.
	 */
	function bp_get_db_version() {
		return buddypress()->db_version;
	}

/**
 * Output the BuddyPress database version.
 *
 * @since BuddyPress (1.6.0)
 *
 * @uses bp_get_db_version_raw() To get the current database BuddyPress version.
 */
function bp_db_version_raw() {
	echo bp_get_db_version_raw();
}
	/**
	 * Return the BuddyPress database version
	 *
	 * @since BuddyPress (1.6)
	 *
	 * @return string The BuddyPress version direct from the database.
	 */
	function bp_get_db_version_raw() {
		$bp     = buddypress();
		return !empty( $bp->db_version_raw ) ? $bp->db_version_raw : 0;
	}

/** Functions *****************************************************************/

/**
 * Get the $wpdb base prefix, run through the 'bp_core_get_table_prefix' filter.
 *
 * The filter is intended primarily for use in multinetwork installations.
 *
 * @global object $wpdb WordPress database object.
 *
 * @return string Filtered database prefix.
 */
function bp_core_get_table_prefix() {
	global $wpdb;

	return apply_filters( 'bp_core_get_table_prefix', $wpdb->base_prefix );
}

/**
 * Sort an array of objects or arrays by alphabetically sorting by a specific key/property.
 *
 * For instance, if you have an array of WordPress post objects, you can sort
 * them by post_name as follows:
 *     $sorted_posts = bp_alpha_sort_by_key( $posts, 'post_name' );
 *
 * The main purpose for this function is so that you can avoid having to create
 * your own awkward callback function for usort().
 *
 * @since BuddyPress (1.9.0)
 *
 * @param array $items The array to be sorted. Its constituent items can be
 *        either associative arrays or objects.
 * @param string|int $key The array index or property name to sort by.
 * @return array $items The sorted array.
 */
function bp_alpha_sort_by_key( $items, $key ) {
	usort( $items, create_function( '$a, $b', '
		$values = array( 0 => false, 1 => false, );
		foreach ( func_get_args() as $indexi => $index ) {
			if ( isset( $index->' . $key . ' ) ) {
				$values[ $indexi ] = $index->' . $key . ';
			} else if ( isset( $index["' . $key . '"] ) ) {
				$values[ $indexi ] = $index["' . $key . '"];
			}
		}

		if ( $values[0] && $values[1] ) {
			$cmp = strcmp( $values[0], $values[1] );
			if ( 0 > $cmp ) {
				$retval = -1;
			} else if ( 0 < $cmp ) {
				$retval = 1;
			} else {
				$retval = 0;
			}
			return $retval;
		} else {
			return 0;
		}
	') );

	return $items;
}

/**
 * Format numbers the BuddyPress way.
 *
 * @param int $number The number to be formatted.
 * @param bool $decimals Whether to use decimals. See {@link number_format_i18n()}.
 * @return string The formatted number.
 */
function bp_core_number_format( $number, $decimals = false ) {

	// Force number to 0 if needed
	if ( empty( $number ) )
		$number = 0;

	return apply_filters( 'bp_core_number_format', number_format_i18n( $number, $decimals ), $number, $decimals );
}

/**
 * A utility for parsing individual function arguments into an array.
 *
 * The purpose of this function is to help with backward compatibility in cases where
 *
 *   function foo( $bar = 1, $baz = false, $barry = array(), $blip = false ) { // ...
 *
 * is deprecated in favor of
 *
 *   function foo( $args = array() ) {
 *       $defaults = array(
 *           'bar'  => 1,
 *           'arg2' => false,
 *           'arg3' => array(),
 *           'arg4' => false,
 *       );
 *       $r = wp_parse_args( $args, $defaults ); // ...
 *
 * The first argument, $old_args_keys, is an array that matches the parameter positions (keys) to
 * the new $args keys (values):
 *
 *   $old_args_keys = array(
 *       0 => 'bar', // because $bar was the 0th parameter for foo()
 *       1 => 'baz', // because $baz was the 1st parameter for foo()
 *       2 => 'barry', // etc
 *       3 => 'blip'
 *   );
 *
 * For the second argument, $func_args, you should just pass the value of func_get_args().
 *
 * @since BuddyPress (1.6)
 * @param array $old_args_keys Old argument indexs, keyed to their positions.
 * @param array $func_args The parameters passed to the originating function.
 * @return array $new_args The parsed arguments.
 */
function bp_core_parse_args_array( $old_args_keys, $func_args ) {
	$new_args = array();

	foreach( $old_args_keys as $arg_num => $arg_key ) {
		if ( isset( $func_args[$arg_num] ) ) {
			$new_args[$arg_key] = $func_args[$arg_num];
		}
	}

	return $new_args;
}

/**
 * Sanitize an 'order' parameter for use in building SQL queries.
 *
 * Strings like 'DESC', 'desc', ' desc' will be interpreted into 'DESC'.
 * Everything else becomes 'ASC'.
 *
 * @since BuddyPress (1.8.0)
 *
 * @param string $order The 'order' string, as passed to the SQL constructor.
 * @return string The sanitized value 'DESC' or 'ASC'.
 */
function bp_esc_sql_order( $order = '' ) {
	$order = strtoupper( trim( $order ) );
	return 'DESC' === $order ? 'DESC' : 'ASC';
}

/**
 * Are we running username compatibility mode?
 *
 * @since BuddyPress (1.5.0)
 *
 * @uses apply_filters() Filter 'bp_is_username_compatibility_mode' to alter.
 * @todo Move to members component?
 *
 * @return bool False when compatibility mode is disabled, true when enabled.
 *         Default: false.
 */
function bp_is_username_compatibility_mode() {
	return apply_filters( 'bp_is_username_compatibility_mode', defined( 'BP_ENABLE_USERNAME_COMPATIBILITY_MODE' ) && BP_ENABLE_USERNAME_COMPATIBILITY_MODE );
}

/**
 * Should we use the WP Toolbar?
 *
 * The WP Toolbar, introduced in WP 3.1, is fully supported in BuddyPress as
 * of BP 1.5. For BP 1.6, the WP Toolbar is the default.
 *
 * @since BuddyPress (1.5.0)
 *
 * @uses apply_filters() Filter 'bp_use_wp_admin_bar' to alter.
 *
 * @return bool False when WP Toolbar support is disabled, true when enabled.
 *        Default: true.
 */
function bp_use_wp_admin_bar() {
	$use_admin_bar = true;

	// Has the WP Toolbar constant been explicity set?
	if ( defined( 'BP_USE_WP_ADMIN_BAR' ) && ! BP_USE_WP_ADMIN_BAR )
		$use_admin_bar = false;

	// Has the admin chosen to use the BuddyBar during an upgrade?
	elseif ( (bool) bp_get_option( '_bp_force_buddybar', false ) )
		$use_admin_bar = false;

	return apply_filters( 'bp_use_wp_admin_bar', $use_admin_bar );
}

/** Directory *****************************************************************/

/**
 * Fetch a list of BP directory pages from the appropriate meta table.
 *
 * @since BuddyPress (1.5.0)
 *
 * @return array|string An array of page IDs, keyed by component names, or an
 *         empty string if the list is not found.
 */
function bp_core_get_directory_page_ids() {
	$page_ids = bp_get_option( 'bp-pages' );

	// Ensure that empty indexes are unset. Should only matter in edge cases
	if ( !empty( $page_ids ) && is_array( $page_ids ) ) {
		foreach( (array) $page_ids as $component_name => $page_id ) {
			if ( empty( $component_name ) || empty( $page_id ) ) {
				unset( $page_ids[$component_name] );
			}
		}
	}

	return apply_filters( 'bp_core_get_directory_page_ids', $page_ids );
}

/**
 * Store the list of BP directory pages in the appropriate meta table.
 *
 * bp-pages data is stored in site_options (falls back to options on non-MS),
 * in an array keyed by blog_id. This allows you to change your
 * bp_get_root_blog_id() and go through the setup process again.
 *
 * @since BuddyPress (1.5.0)
 *
 * @param array $blog_page_ids The IDs of the WP pages corresponding to BP
 *        component directories.
 */
function bp_core_update_directory_page_ids( $blog_page_ids ) {
	bp_update_option( 'bp-pages', $blog_page_ids );
}

/**
 * Get names and slugs for BuddyPress component directory pages.
 *
 * @since BuddyPress (1.5.0).
 *
 * @return object Page names, IDs, and slugs.
 */
function bp_core_get_directory_pages() {
	global $wpdb;

	// Set pages as standard class
	$pages = new stdClass;

	// Get pages and IDs
	$page_ids = bp_core_get_directory_page_ids();
	if ( !empty( $page_ids ) ) {

		// Always get page data from the root blog, except on multiblog mode, when it comes
		// from the current blog
		$posts_table_name = bp_is_multiblog_mode() ? $wpdb->posts : $wpdb->get_blog_prefix( bp_get_root_blog_id() ) . 'posts';
		$page_ids_sql     = implode( ',', wp_parse_id_list( $page_ids ) );
		$page_names       = $wpdb->get_results( "SELECT ID, post_name, post_parent, post_title FROM {$posts_table_name} WHERE ID IN ({$page_ids_sql}) AND post_status = 'publish' " );

		foreach ( (array) $page_ids as $component_id => $page_id ) {
			foreach ( (array) $page_names as $page_name ) {
				if ( $page_name->ID == $page_id ) {
					if ( !isset( $pages->{$component_id} ) || !is_object( $pages->{$component_id} ) ) {
						$pages->{$component_id} = new stdClass;
					}

					$pages->{$component_id}->name  = $page_name->post_name;
					$pages->{$component_id}->id    = $page_name->ID;
					$pages->{$component_id}->title = $page_name->post_title;
					$slug[]                        = $page_name->post_name;

					// Get the slug
					while ( $page_name->post_parent != 0 ) {
						$parent                 = $wpdb->get_results( $wpdb->prepare( "SELECT post_name, post_parent FROM {$posts_table_name} WHERE ID = %d", $page_name->post_parent ) );
						$slug[]                 = $parent[0]->post_name;
						$page_name->post_parent = $parent[0]->post_parent;
					}

					$pages->{$component_id}->slug = implode( '/', array_reverse( (array) $slug ) );
				}

				unset( $slug );
			}
		}
	}

	return apply_filters( 'bp_core_get_directory_pages', $pages );
}

/**
 * Creates necessary directory pages.
 *
 * Directory pages are those WordPress pages used by BP components to display
 * content (eg, the 'groups' page created by BP).
 *
 * @since BuddyPress (1.7.0)
 *
 * @param array $default_components Components to create pages for.
 * @param string $existing 'delete' if you want to delete existing page
 *        mappings and replace with new ones. Otherwise existing page mappings
 *        are kept, and the gaps filled in with new pages. Default: 'keep'.
 */
function bp_core_add_page_mappings( $components, $existing = 'keep' ) {

	// Make sure that the pages are created on the root blog no matter which Dashboard the setup is being run on
	if ( ! bp_is_root_blog() )
		switch_to_blog( bp_get_root_blog_id() );

	$pages = bp_core_get_directory_page_ids();

	// Delete any existing pages
	if ( 'delete' == $existing ) {
		foreach ( (array) $pages as $page_id ) {
			wp_delete_post( $page_id, true );
		}

		$pages = array();
	}

	$page_titles = array(
		'activity' => _x( 'Activity', 'Page title for the Activity directory.', 'buddypress' ),
		'groups'   => _x( 'Groups', 'Page title for the Groups directory.', 'buddypress' ),
		'sites'    => _x( 'Sites', 'Page title for the Sites directory.', 'buddypress' ),
		'activate' => _x( 'Activate', 'Page title for the user account activation screen.', 'buddypress' ),
		'members'  => _x( 'Members', 'Page title for the Members directory.', 'buddypress' ),
		'register' => _x( 'Register', 'Page title for the user registration screen.', 'buddypress' ),
	);

	$pages_to_create = array();
	foreach ( array_keys( $components ) as $component_name ) {
		if ( ! isset( $pages[ $component_name ] ) && isset( $page_titles[ $component_name ] ) ) {
			$pages_to_create[ $component_name ] = $page_titles[ $component_name ];
		}
	}

	// Register and Activate are not components, but need pages when
	// registration is enabled
	if ( bp_get_signup_allowed() ) {
		foreach ( array( 'register', 'activate' ) as $slug ) {
			if ( ! isset( $pages[ $slug ] ) ) {
				$pages_to_create[ $slug ] = $page_titles[ $slug ];
			}
		}
	}

	// No need for a Sites directory unless we're on multisite
	if ( ! is_multisite() && isset( $pages_to_create['sites'] ) ) {
		unset( $pages_to_create['sites'] );
	}

	// Members must always have a page, no matter what
	if ( ! isset( $pages['members'] ) && ! isset( $pages_to_create['members'] ) ) {
		$pages_to_create['members'] = $page_titles['members'];
	}

	// Create the pages
	foreach ( $pages_to_create as $component_name => $page_name ) {
		$pages[ $component_name ] = wp_insert_post( array(
			'comment_status' => 'closed',
			'ping_status'    => 'closed',
			'post_status'    => 'publish',
			'post_title'     => $page_name,
			'post_type'      => 'page',
		) );
	}

	// Save the page mapping
	bp_update_option( 'bp-pages', $pages );

	// If we had to switch_to_blog, go back to the original site.
	if ( ! bp_is_root_blog() )
		restore_current_blog();
}

/**
 * Create a default component slug from a WP page root_slug.
 *
 * Since 1.5, BP components get their root_slug (the slug used immediately
 * following the root domain) from the slug of a corresponding WP page.
 *
 * E.g. if your BP installation at example.com has its members page at
 * example.com/community/people, $bp->members->root_slug will be
 * 'community/people'.
 *
 * By default, this function creates a shorter version of the root_slug for
 * use elsewhere in the URL, by returning the content after the final '/'
 * in the root_slug ('people' in the example above).
 *
 * Filter on 'bp_core_component_slug_from_root_slug' to override this method
 * in general, or define a specific component slug constant (e.g.
 * BP_MEMBERS_SLUG) to override specific component slugs.
 *
 * @since BuddyPress (1.5.0)
 *
 * @param string $root_slug The root slug, which comes from $bp->pages->[component]->slug.
 * @return string The short slug for use in the middle of URLs.
 */
function bp_core_component_slug_from_root_slug( $root_slug ) {
	$slug_chunks = explode( '/', $root_slug );
 	$slug        = array_pop( $slug_chunks );

 	return apply_filters( 'bp_core_component_slug_from_root_slug', $slug, $root_slug );
}

/**
 * Add support for a top-level ("root") component.
 *
 * This function originally (pre-1.5) let plugins add support for pages in the
 * root of the install. These root level pages are now handled by actual
 * WordPress pages and this function is now a convenience for compatibility
 * with the new method.
 *
 * @param string $slug The slug of the component being added to the root list.
 */
function bp_core_add_root_component( $slug ) {
	$bp = buddypress();

	if ( empty( $bp->pages ) ) {
		$bp->pages = bp_core_get_directory_pages();
	}

	$match = false;

	// Check if the slug is registered in the $bp->pages global
	foreach ( (array) $bp->pages as $key => $page ) {
		if ( $key == $slug || $page->slug == $slug ) {
			$match = true;
		}
	}

	// Maybe create the add_root array
	if ( empty( $bp->add_root ) ) {
		$bp->add_root = array();
	}

	// If there was no match, add a page for this root component
	if ( empty( $match ) ) {
		$bp->add_root[] = $slug;
	}

	// Make sure that this component is registered as requiring a top-level directory
	if ( isset( $bp->{$slug} ) ) {
		$bp->loaded_components[$bp->{$slug}->slug] = $bp->{$slug}->id;
		$bp->{$slug}->has_directory = true;
	}
}

/**
 * Create WordPress pages to be used as BP component directories.
 */
function bp_core_create_root_component_page() {
	global $bp;

	$new_page_ids = array();

	foreach ( (array) $bp->add_root as $slug )
		$new_page_ids[$slug] = wp_insert_post( array( 'comment_status' => 'closed', 'ping_status' => 'closed', 'post_title' => ucwords( $slug ), 'post_status' => 'publish', 'post_type' => 'page' ) );

	$page_ids = array_merge( (array) $new_page_ids, (array) bp_core_get_directory_page_ids() );
	bp_core_update_directory_page_ids( $page_ids );
}

/**
 * Add illegal blog names to WP so that root components will not conflict with blog names on a subdirectory installation.
 *
 * For example, it would stop someone creating a blog with the slug "groups".
 *
 * @todo Deprecate?
 */
function bp_core_add_illegal_names() {
	update_site_option( 'illegal_names', get_site_option( 'illegal_names' ), array() );
}

/**
 * Determine whether BuddyPress should register the bp-themes directory.
 *
 * @since BuddyPress (1.9.0)
 *
 * @return bool True if bp-themes should be registered, false otherwise.
 */
function bp_do_register_theme_directory() {
	$register = 'bp-default' === get_stylesheet() || 'bp-default' === get_template();

	// Legacy sites continue to have the theme registered
	if ( empty( $register ) && ( 1 == get_site_option( '_bp_retain_bp_default' ) ) ) {
		$register = true;
	}

	return apply_filters( 'bp_do_register_theme_directory', $register );
}

/** URI ***********************************************************************/

/**
 * Return the domain for the root blog.
 *
 * eg: http://domain.com OR https://domain.com
 *
 * @uses get_blog_option() WordPress function to fetch blog meta.
 *
 * @return string The domain URL for the blog.
 */
function bp_core_get_root_domain() {

	$domain = get_home_url( bp_get_root_blog_id() );

	return apply_filters( 'bp_core_get_root_domain', $domain );
}

/**
 * Perform a status-safe wp_redirect() that is compatible with BP's URI parser.
 *
 * @uses wp_safe_redirect()
 *
 * @param string $location The redirect URL.
 * @param int $status Optional. The numeric code to give in the redirect
 *        headers. Default: 302.
 */
function bp_core_redirect( $location, $status = 302 ) {

	// On some setups, passing the value of wp_get_referer() may result in an
	// empty value for $location, which results in an error. Ensure that we
	// have a valid URL.
	if ( empty( $location ) )
		$location = bp_get_root_domain();

	// Make sure we don't call status_header() in bp_core_do_catch_uri() as this
	// conflicts with wp_redirect() and wp_safe_redirect().
	buddypress()->no_status_set = true;

	wp_safe_redirect( $location, $status );
	die;
}

/**
 * Return the referrer URL without the http(s)://
 *
 * @return string The referrer URL.
 */
function bp_core_referrer() {
	$referer = explode( '/', wp_get_referer() );
	unset( $referer[0], $referer[1], $referer[2] );
	return implode( '/', $referer );
}

/**
 * Get the path of of the current site.
 *
 * @global object $current_site
 *
 * @return string URL to the current site.
 */
function bp_core_get_site_path() {
	global $current_site;

	if ( is_multisite() )
		$site_path = $current_site->path;
	else {
		$site_path = (array) explode( '/', home_url() );

		if ( count( $site_path ) < 2 )
			$site_path = '/';
		else {
			// Unset the first three segments (http(s)://domain.com part)
			unset( $site_path[0] );
			unset( $site_path[1] );
			unset( $site_path[2] );

			if ( !count( $site_path ) )
				$site_path = '/';
			else
				$site_path = '/' . implode( '/', $site_path ) . '/';
		}
	}

	return apply_filters( 'bp_core_get_site_path', $site_path );
}

/** Time **********************************************************************/

/**
 * Get the current GMT time to save into the DB.
 *
 * @since BuddyPress (1.2.6)
 *
 * @param bool $gmt True to use GMT (rather than local) time. Default: true.
 * @return string Current time in 'Y-m-d h:i:s' format.
 */
function bp_core_current_time( $gmt = true ) {
	// Get current time in MYSQL format
	$current_time = current_time( 'mysql', $gmt );

	return apply_filters( 'bp_core_current_time', $current_time );
}

/**
 * Get an English-language representation of the time elapsed since a given date.
 *
 * Based on function created by Dunstan Orchard - http://1976design.com
 *
 * This function will return an English representation of the time elapsed
 * since a given date.
 * eg: 2 hours and 50 minutes
 * eg: 4 days
 * eg: 4 weeks and 6 days
 *
 * Note that fractions of minutes are not represented in the return string. So
 * an interval of 3 minutes will be represented by "3 minutes ago", as will an
 * interval of 3 minutes 59 seconds.
 *
 * @uses apply_filters() Filter 'bp_core_time_since_pre' to bypass BP's calculations.
 * @uses apply_filters() Filter 'bp_core_time_since' to modify BP's calculations.
 *
 * @param int|string $older_date The earlier time from which you're calculating
 *        the time elapsed. Enter either as an integer Unix timestamp, or as a
 *        date string of the format 'Y-m-d h:i:s'.
 * @param int $newer_date Optional. Unix timestamp of date to compare older
 *        date to. Default: false (current time).
 * @return string String representing the time since the older date, eg
 *         "2 hours and 50 minutes".
 */
function bp_core_time_since( $older_date, $newer_date = false ) {

	// Use this filter to bypass BuddyPress's time_since calculations
	if ( $pre_value = apply_filters( 'bp_core_time_since_pre', false, $older_date, $newer_date ) ) {
		return $pre_value;
	}

	// Setup the strings
	$unknown_text   = apply_filters( 'bp_core_time_since_unknown_text',   __( 'sometime',  'buddypress' ) );
	$right_now_text = apply_filters( 'bp_core_time_since_right_now_text', __( 'right now', 'buddypress' ) );
	$ago_text       = apply_filters( 'bp_core_time_since_ago_text',       __( '%s ago',    'buddypress' ) );

	// array of time period chunks
	$chunks = array(
		YEAR_IN_SECONDS,
		30 * DAY_IN_SECONDS,
		WEEK_IN_SECONDS,
		DAY_IN_SECONDS,
		HOUR_IN_SECONDS,
		MINUTE_IN_SECONDS,
		1
	);

	if ( !empty( $older_date ) && !is_numeric( $older_date ) ) {
		$time_chunks = explode( ':', str_replace( ' ', ':', $older_date ) );
		$date_chunks = explode( '-', str_replace( ' ', '-', $older_date ) );
		$older_date  = gmmktime( (int) $time_chunks[1], (int) $time_chunks[2], (int) $time_chunks[3], (int) $date_chunks[1], (int) $date_chunks[2], (int) $date_chunks[0] );
	}

	/**
	 * $newer_date will equal false if we want to know the time elapsed between
	 * a date and the current time. $newer_date will have a value if we want to
	 * work out time elapsed between two known dates.
	 */
	$newer_date = ( !$newer_date ) ? strtotime( bp_core_current_time() ) : $newer_date;

	// Difference in seconds
	$since = $newer_date - $older_date;

	// Something went wrong with date calculation and we ended up with a negative date.
	if ( 0 > $since ) {
		$output = $unknown_text;

	/**
	 * We only want to output two chunks of time here, eg:
	 * x years, xx months
	 * x days, xx hours
	 * so there's only two bits of calculation below:
	 */
	} else {

		// Step one: the first chunk
		for ( $i = 0, $j = count( $chunks ); $i < $j; ++$i ) {
			$seconds = $chunks[$i];

			// Finding the biggest chunk (if the chunk fits, break)
			$count = floor( $since / $seconds );
			if ( 0 != $count ) {
				break;
			}
		}

		// If $i iterates all the way to $j, then the event happened 0 seconds ago
		if ( !isset( $chunks[$i] ) ) {
			$output = $right_now_text;

		} else {

			// Set output var
			switch ( $seconds ) {
				case YEAR_IN_SECONDS :
					$output = sprintf( _n( '%s year',   '%s years',   $count, 'buddypress' ), $count );
					break;
				case 30 * DAY_IN_SECONDS :
					$output = sprintf( _n( '%s month',  '%s months',  $count, 'buddypress' ), $count );
					break;
				case WEEK_IN_SECONDS :
					$output = sprintf( _n( '%s week',   '%s weeks',   $count, 'buddypress' ), $count );
					break;
				case DAY_IN_SECONDS :
					$output = sprintf( _n( '%s day',    '%s days',    $count, 'buddypress' ), $count );
					break;
				case HOUR_IN_SECONDS :
					$output = sprintf( _n( '%s hour',   '%s hours',   $count, 'buddypress' ), $count );
					break;
				case MINUTE_IN_SECONDS :
					$output = sprintf( _n( '%s minute', '%s minutes', $count, 'buddypress' ), $count );
					break;
				default:
					$output = sprintf( _n( '%s second', '%s seconds', $count, 'buddypress' ), $count );
			}

			// Step two: the second chunk
			// A quirk in the implementation means that this
			// condition fails in the case of minutes and seconds.
			// We've left the quirk in place, since fractions of a
			// minute are not a useful piece of information for our
			// purposes
			if ( $i + 2 < $j ) {
				$seconds2 = $chunks[$i + 1];
				$count2   = floor( ( $since - ( $seconds * $count ) ) / $seconds2 );

				// Add to output var
				if ( 0 != $count2 ) {
					$output .= _x( ',', 'Separator in time since', 'buddypress' ) . ' ';

					switch ( $seconds2 ) {
						case 30 * DAY_IN_SECONDS :
							$output .= sprintf( _n( '%s month',  '%s months',  $count2, 'buddypress' ), $count2 );
							break;
						case WEEK_IN_SECONDS :
							$output .= sprintf( _n( '%s week',   '%s weeks',   $count2, 'buddypress' ), $count2 );
							break;
						case DAY_IN_SECONDS :
							$output .= sprintf( _n( '%s day',    '%s days',    $count2, 'buddypress' ), $count2 );
							break;
						case HOUR_IN_SECONDS :
							$output .= sprintf( _n( '%s hour',   '%s hours',   $count2, 'buddypress' ), $count2 );
							break;
						case MINUTE_IN_SECONDS :
							$output .= sprintf( _n( '%s minute', '%s minutes', $count2, 'buddypress' ), $count2 );
							break;
						default:
							$output .= sprintf( _n( '%s second', '%s seconds', $count2, 'buddypress' ), $count2 );
					}
				}
			}

			// No output, so happened right now
			if ( ! (int) trim( $output ) ) {
				$output = $right_now_text;
			}
		}
	}

	// Append 'ago' to the end of time-since if not 'right now'
	if ( $output != $right_now_text ) {
		$output = sprintf( $ago_text, $output );
	}

	return apply_filters( 'bp_core_time_since', $output, $older_date, $newer_date );
}

/** Messages ******************************************************************/

/**
 * Add a feedback (error/success) message to the WP cookie so it can be displayed after the page reloads.
 *
 * @global BuddyPress $bp The one true BuddyPress instance.
 *
 * @param string $message Feedback message to be displayed.
 * @param string $type Message type. 'updated', 'success', 'error', 'warning'.
 *        Default: 'success'.
 */
function bp_core_add_message( $message, $type = '' ) {
	global $bp;

	// Success is the default
	if ( empty( $type ) )
		$type = 'success';

	// Send the values to the cookie for page reload display
	@setcookie( 'bp-message',      $message, time() + 60 * 60 * 24, COOKIEPATH );
	@setcookie( 'bp-message-type', $type,    time() + 60 * 60 * 24, COOKIEPATH );

	/***
	 * Send the values to the $bp global so we can still output messages
	 * without a page reload
	 */
	$bp->template_message      = $message;
	$bp->template_message_type = $type;
}

/**
 * Set up the display of the 'template_notices' feedback message.
 *
 * Checks whether there is a feedback message in the WP cookie and, if so, adds
 * a "template_notices" action so that the message can be parsed into the
 * template and displayed to the user.
 *
 * After the message is displayed, it removes the message vars from the cookie
 * so that the message is not shown to the user multiple times.
 *
 * @global BuddyPress $bp BuddyPress global settings object.
 * @uses setcookie() Sets a cookie value for the user.
 */
function bp_core_setup_message() {
	global $bp;

	if ( empty( $bp->template_message ) && isset( $_COOKIE['bp-message'] ) )
		$bp->template_message = stripslashes( $_COOKIE['bp-message'] );

	if ( empty( $bp->template_message_type ) && isset( $_COOKIE['bp-message-type'] ) )
		$bp->template_message_type = stripslashes( $_COOKIE['bp-message-type'] );

	add_action( 'template_notices', 'bp_core_render_message' );

	if ( isset( $_COOKIE['bp-message'] ) )
		@setcookie( 'bp-message', false, time() - 1000, COOKIEPATH );
	if ( isset( $_COOKIE['bp-message-type'] ) )
		@setcookie( 'bp-message-type', false, time() - 1000, COOKIEPATH );
}
add_action( 'bp_actions', 'bp_core_setup_message', 5 );

/**
 * Render the 'template_notices' feedback message.
 *
 * The hook action 'template_notices' is used to call this function, it is not
 * called directly.
 *
 * @global BuddyPress $bp The one true BuddyPress instance.
 */
function bp_core_render_message() {
	global $bp;

	if ( !empty( $bp->template_message ) ) :
		$type    = ( 'success' == $bp->template_message_type ) ? 'updated' : 'error';
		$content = apply_filters( 'bp_core_render_message_content', $bp->template_message, $type ); ?>

		<div id="message" class="bp-template-notice <?php echo $type; ?>">

			<?php echo $content; ?>

		</div>

	<?php

		do_action( 'bp_core_render_message' );

	endif;
}

/** Last active ***************************************************************/

/**
 * Listener function for the logged-in user's 'last_activity' metadata.
 *
 * Many functions use a "last active" feature to show the length of time since
 * the user was last active. This function will update that time as a usermeta
 * setting for the user every 5 minutes while the user is actively browsing the
 * site.
 *
 * @uses bp_update_user_meta() BP function to update user metadata in the
 *       usermeta table.
 *
 * @return bool|null Returns false if there is nothing to do.
 */
function bp_core_record_activity() {

	if ( !is_user_logged_in() )
		return false;

	$user_id = bp_loggedin_user_id();

	if ( bp_is_user_inactive( $user_id ) )
		return false;

	$activity = bp_get_user_last_activity( $user_id );

	if ( !is_numeric( $activity ) )
		$activity = strtotime( $activity );

	// Get current time
	$current_time = bp_core_current_time();

	// Use this action to detect the very first activity for a given member
	if ( empty( $activity ) ) {
		do_action( 'bp_first_activity_for_member', $user_id );
	}

	if ( empty( $activity ) || strtotime( $current_time ) >= strtotime( '+5 minutes', $activity ) ) {
		bp_update_user_last_activity( $user_id, $current_time );
	}
}
add_action( 'wp_head', 'bp_core_record_activity' );

/**
 * Format last activity string based on time since date given.
 *
 * @uses bp_core_time_since() This function will return an English
 *       representation of the time elapsed.
 *
 * @param int|string $last_activity_date The date of last activity.
 * @param string $string A sprintf()-able statement of the form '% ago'.
 * @return string $last_active A string of the form '3 years ago'.
 */
function bp_core_get_last_activity( $last_activity_date, $string ) {

	if ( empty( $last_activity_date ) )
		$last_active = __( 'Not recently active', 'buddypress' );
	else
		$last_active = sprintf( $string, bp_core_time_since( $last_activity_date ) );

	return apply_filters( 'bp_core_get_last_activity', $last_active, $last_activity_date, $string );
}

/** Meta **********************************************************************/

/**
 * Get the meta_key for a given piece of user metadata
 *
 * BuddyPress stores a number of pieces of userdata in the WordPress central
 * usermeta table. In order to allow plugins to enable multiple instances of
 * BuddyPress on a single WP installation, BP's usermeta keys are filtered
 * through this function, so that they can be altered on the fly.
 *
 * Plugin authors should use BP's _user_meta() functions, which bakes in
 * bp_get_user_meta_key():
 *    $last_active = bp_get_user_meta( $user_id, 'last_activity', true );
 * If you must use WP's _user_meta() functions directly for some reason, you
 * should use this function to determine the $key parameter, eg
 *    $last_active = get_user_meta( $user_id, bp_get_user_meta_key( 'last_activity' ), true );
 * If using the WP functions, do not not hardcode your meta keys.
 *
 * @since BuddyPress (1.5.0)
 *
 * @uses apply_filters() Filter 'bp_get_user_meta_key' to modify keys individually.
 *
 * @param string $key The usermeta meta_key.
 * @return string $key The usermeta meta_key.
 */
function bp_get_user_meta_key( $key = false ) {
	return apply_filters( 'bp_get_user_meta_key', $key );
}

/**
 * Get a piece of usermeta.
 *
 * This is a wrapper for get_user_meta() that allows for easy use of
 * bp_get_user_meta_key(), thereby increasing compatibility with non-standard
 * BP setups.
 *
 * @since BuddyPress (1.5.0)
 *
 * @see get_user_meta() For complete details about parameters and return values.
 * @uses bp_get_user_meta_key() For a filterable version of the meta key.
 *
 * @param int $user_id The ID of the user whose meta you're fetching.
 * @param string $key The meta key to retrieve.
 * @param bool $single Whether to return a single value.
 * @return mixed Will be an array if $single is false. Will be value of meta data field if $single
 *         is true.
 */
function bp_get_user_meta( $user_id, $key, $single = false ) {
	return get_user_meta( $user_id, bp_get_user_meta_key( $key ), $single );
}

/**
 * Update a piece of usermeta.
 *
 * This is a wrapper for update_user_meta() that allows for easy use of
 * bp_get_user_meta_key(), thereby increasing compatibility with non-standard
 * BP setups.
 *
 * @since BuddyPress (1.5.0)
 *
 * @see update_user_meta() For complete details about parameters and return values.
 * @uses bp_get_user_meta_key() For a filterable version of the meta key.
 *
 * @param int $user_id The ID of the user whose meta you're setting.
 * @param string $key The meta key to set.
 * @param mixed $value Metadata value.
 * @param mixed $prev_value Optional. Previous value to check before removing.
 * @return bool False on failure, true on success.
 */
function bp_update_user_meta( $user_id, $key, $value, $prev_value = '' ) {
	return update_user_meta( $user_id, bp_get_user_meta_key( $key ), $value, $prev_value );
}

/**
 * Delete a piece of usermeta.
 *
 * This is a wrapper for delete_user_meta() that allows for easy use of
 * bp_get_user_meta_key(), thereby increasing compatibility with non-standard
 * BP setups.
 *
 * @since BuddyPress (1.5.0)
 *
 * @see delete_user_meta() For complete details about parameters and return values.
 * @uses bp_get_user_meta_key() For a filterable version of the meta key.
 *
 * @param int $user_id The ID of the user whose meta you're deleting.
 * @param string $key The meta key to delete.
 * @param mixed $value Optional. Metadata value.
 * @return bool False for failure. True for success.
 */
function bp_delete_user_meta( $user_id, $key, $value = '' ) {
	return delete_user_meta( $user_id, bp_get_user_meta_key( $key ), $value );
}

/** Embeds ********************************************************************/

/**
 * Initializes {@link BP_Embed} after everything is loaded.
 *
 * @since BuddyPress (1.5.0)
 *
 * @global BuddyPress $bp BuddyPress global settings.
 */
function bp_embed_init() {
	global $bp;

	if ( empty( $bp->embed ) )
		$bp->embed = new BP_Embed();
}
add_action( 'bp_init', 'bp_embed_init', 9 );

/**
 * Are oembeds allowed in activity items?
 *
 * @since BuddyPress (1.5.0)
 *
 * @return bool False when activity embed support is disabled; true when
 *         enabled. Default: true.
 */
function bp_use_embed_in_activity() {
	return apply_filters( 'bp_use_oembed_in_activity', !defined( 'BP_EMBED_DISABLE_ACTIVITY' ) || !BP_EMBED_DISABLE_ACTIVITY );
}

/**
 * Are oembeds allwoed in activity replies?
 *
 * @since BuddyPress (1.5.0)
 *
 * @return bool False when activity replies embed support is disabled; true
 *         when enabled. Default: true.
 */
function bp_use_embed_in_activity_replies() {
	return apply_filters( 'bp_use_embed_in_activity_replies', !defined( 'BP_EMBED_DISABLE_ACTIVITY_REPLIES' ) || !BP_EMBED_DISABLE_ACTIVITY_REPLIES );
}

/**
 * Are oembeds allowed in forum posts?
 *
 * @since BuddyPress (1.5.0)
 *
 * @return bool False when forum post embed support is disabled; true when
 *         enabled. Default: true.
 */
function bp_use_embed_in_forum_posts() {
	return apply_filters( 'bp_use_embed_in_forum_posts', !defined( 'BP_EMBED_DISABLE_FORUM_POSTS' ) || !BP_EMBED_DISABLE_FORUM_POSTS );
}

/**
 * Are oembeds allowed in private messages?
 *
 * @since BuddyPress (1.5.0)
 *
 * @return bool False when private message embed support is disabled; true when
 *         enabled. Default: true.
 */
function bp_use_embed_in_private_messages() {
	return apply_filters( 'bp_use_embed_in_private_messages', !defined( 'BP_EMBED_DISABLE_PRIVATE_MESSAGES' ) || !BP_EMBED_DISABLE_PRIVATE_MESSAGES );
}

/** Admin *********************************************************************/

/**
 * Output the correct admin URL based on BuddyPress and WordPress configuration.
 *
 * @since BuddyPress (1.5.0)
 *
 * @see bp_get_admin_url() For description of parameters.
 *
 * @param string $path See {@link bp_get_admin_url()}.
 * @param string $scheme See {@link bp_get_admin_url()}.
 */
function bp_admin_url( $path = '', $scheme = 'admin' ) {
	echo bp_get_admin_url( $path, $scheme );
}
	/**
	 * Return the correct admin URL based on BuddyPress and WordPress configuration.
	 *
	 * @since BuddyPress (1.5.0)
	 *
	 * @uses bp_core_do_network_admin()
	 * @uses network_admin_url()
	 * @uses admin_url()
	 *
	 * @param string $path Optional. The sub-path under /wp-admin to be
	 *        appended to the admin URL.
	 * @param string $scheme The scheme to use. Default is 'admin', which
	 *        obeys {@link force_ssl_admin()} and {@link is_ssl()}. 'http'
	 *        or 'https' can be passed to force those schemes.
	 * @return string Admin url link with optional path appended.
	 */
	function bp_get_admin_url( $path = '', $scheme = 'admin' ) {

		// Links belong in network admin
		if ( bp_core_do_network_admin() ) {
			$url = network_admin_url( $path, $scheme );

		// Links belong in site admin
		} else {
			$url = admin_url( $path, $scheme );
		}

		return $url;
	}

/**
 * Should BuddyPress appear in network admin (vs a single site Dashboard)?
 *
 * Because BuddyPress can be installed in multiple ways and with multiple
 * configurations, we need to check a few things to be confident about where
 * to hook into certain areas of WordPress's admin.
 *
 * @since BuddyPress (1.5.0)
 *
 * @uses bp_is_network_activated()
 * @uses bp_is_multiblog_mode()
 *
 * @return bool True if the BP admin screen should appear in the Network Admin,
 *         otherwise false.
 */
function bp_core_do_network_admin() {

	// Default
	$retval = bp_is_network_activated();

	if ( bp_is_multiblog_mode() )
		$retval = false;

	return (bool) apply_filters( 'bp_core_do_network_admin', $retval );
}

/**
 * Return the action name that BuddyPress nav setup callbacks should be hooked to.
 *
 * Functions used to set up BP Dashboard pages (wrapping such admin-panel
 * functions as add_submenu_page()) should use bp_core_admin_hook() for the
 * first parameter in add_action(). BuddyPress will then determine
 * automatically whether to load the panels in the Network Admin. Ie:
 *
 *     add_action( bp_core_admin_hook(), 'myplugin_dashboard_panel_setup' );
 *
 * @return string $hook The proper hook ('network_admin_menu' or 'admin_menu').
 */
function bp_core_admin_hook() {
	$hook = bp_core_do_network_admin() ? 'network_admin_menu' : 'admin_menu';

	return apply_filters( 'bp_core_admin_hook', $hook );
}

/** Multisite *****************************************************************/

/**
 * Is this the root blog?
 *
 * @since BuddyPress (1.5.0)
 *
 * @param int $blog_id Optional. Default: the ID of the current blog.
 * @return bool $is_root_blog Returns true if this is bp_get_root_blog_id().
 */
function bp_is_root_blog( $blog_id = 0 ) {

	// Assume false
	$is_root_blog = false;

	// Use current blog if no ID is passed
	if ( empty( $blog_id ) )
		$blog_id = get_current_blog_id();

	// Compare to root blog ID
	if ( $blog_id == bp_get_root_blog_id() )
		$is_root_blog = true;

	return (bool) apply_filters( 'bp_is_root_blog', (bool) $is_root_blog );
}

/**
 * Get the ID of the root blog.
 *
 * The "root blog" is the blog on a WordPress network where BuddyPress content
 * appears (where member profile URLs resolve, where a given theme is loaded,
 * etc.).
 *
 * @since BuddyPress (1.5.0)
 *
 * @return int The root site ID.
 */
function bp_get_root_blog_id() {
	global $bp;

	return (int) apply_filters( 'bp_get_root_blog_id', (int) $bp->root_blog_id );
}

/**
 * Are we running multiblog mode?
 *
 * Note that BP_ENABLE_MULTIBLOG is different from (but dependent on) WordPress
 * Multisite. "Multiblog" is BuddyPress setup that allows BuddyPress components
 * to be viewed on every blog on the network, each with their own settings.
 *
 * Thus, instead of having all 'boonebgorges' links go to
 *   http://example.com/members/boonebgorges
 * on the root blog, each blog will have its own version of the same content, eg
 *   http://site2.example.com/members/boonebgorges (for subdomains)
 *   http://example.com/site2/members/boonebgorges (for subdirectories)
 *
 * Multiblog mode is disabled by default, meaning that all BuddyPress content
 * must be viewed on the root blog. It's also recommended not to use the
 * BP_ENABLE_MULTIBLOG constant beyond 1.7, as BuddyPress can now be activated
 * on individual sites.
 *
 * Why would you want to use this? Originally it was intended to allow
 * BuddyPress to live in mu-plugins and be visible on mapped domains. This is
 * a very small use-case with large architectural shortcomings, so do not go
 * down this road unless you specifically need to.
 *
 * @since BuddyPress (1.5.0)
 *
 * @uses apply_filters() Filter 'bp_is_multiblog_mode' to alter.
 *
 * @return bool False when multiblog mode is disabled; true when enabled.
 *         Default: false.
 */
function bp_is_multiblog_mode() {

	// Setup some default values
	$retval         = false;
	$is_multisite   = is_multisite();
	$network_active = bp_is_network_activated();
	$is_multiblog   = defined( 'BP_ENABLE_MULTIBLOG' ) && BP_ENABLE_MULTIBLOG;

	// Multisite, Network Activated, and Specifically Multiblog
	if ( $is_multisite && $network_active && $is_multiblog ) {
		$retval = true;

	// Multisite, but not network activated
	} elseif ( $is_multisite && ! $network_active ) {
		$retval = true;
	}

	return apply_filters( 'bp_is_multiblog_mode', $retval );
}

/**
 * Is BuddyPress active at the network level for this network?
 *
 * Used to determine admin menu placement, and where settings and options are
 * stored. If you're being *really* clever and manually pulling BuddyPress in
 * with an mu-plugin or some other method, you'll want to filter
 * 'bp_is_network_activated' and override the auto-determined value.
 *
 * @since BuddyPress (1.7.0)
 *
 * @return bool True if BuddyPress is network activated.
 */
function bp_is_network_activated() {

	// Default to is_multisite()
	$retval  = is_multisite();

	// Check the sitewide plugins array
	$base    = buddypress()->basename;
	$plugins = get_site_option( 'active_sitewide_plugins' );

	// Override is_multisite() if not network activated
	if ( ! is_array( $plugins ) || ! isset( $plugins[$base] ) )
		$retval = false;

	return (bool) apply_filters( 'bp_is_network_activated', $retval );
}

/** Global Manipulators *******************************************************/

/**
 * Set the $bp->is_directory global.
 *
 * @global BuddyPress $bp The one true BuddyPress instance.
 *
 * @param bool $is_directory Optional. Default: false.
 * @param string $component Optional. Component name. Default: the current
 *        component.
 */
function bp_update_is_directory( $is_directory = false, $component = '' ) {
	global $bp;

	if ( empty( $component ) )
		$component = bp_current_component();

	$bp->is_directory = apply_filters( 'bp_update_is_directory', $is_directory, $component );
}

/**
 * Set the $bp->is_item_admin global.
 *
 * @global BuddyPress $bp The one true BuddyPress instance.
 *
 * @param bool $is_item_admin Optional. Default: false.
 * @param string $component. Optional. Component name. Default: the current
 *        component.
 */
function bp_update_is_item_admin( $is_item_admin = false, $component = '' ) {
	global $bp;

	if ( empty( $component ) )
		$component = bp_current_component();

	$bp->is_item_admin = apply_filters( 'bp_update_is_item_admin', $is_item_admin, $component );
}

/**
 * Set the $bp->is_item_mod global.
 *
 * @global BuddyPress $bp The one true BuddyPress instance.
 *
 * @param bool $is_item_mod Optional. Default: false.
 * @param string $component. Optional. Component name. Default: the current
 *        component.
 */
function bp_update_is_item_mod( $is_item_mod = false, $component = '' ) {
	global $bp;

	if ( empty( $component ) )
		$component = bp_current_component();

	$bp->is_item_mod = apply_filters( 'bp_update_is_item_mod', $is_item_mod, $component );
}

/**
 * Trigger a 404.
 *
 * @since BuddyPress (1.5.0)
 *
 * @global WP_Query $wp_query WordPress query object.
 *
 * @param string $redirect If 'remove_canonical_direct', remove WordPress'
 *        "helpful" redirect_canonical action. Default: 'remove_canonical_redirect'.
 */
function bp_do_404( $redirect = 'remove_canonical_direct' ) {
	global $wp_query;

	do_action( 'bp_do_404', $redirect );

	$wp_query->set_404();
	status_header( 404 );
	nocache_headers();

	if ( 'remove_canonical_direct' == $redirect )
		remove_action( 'template_redirect', 'redirect_canonical' );
}

/** Nonces ********************************************************************/

/**
 * Makes sure the user requested an action from another page on this site.
 *
 * To avoid security exploits within the theme.
 *
 * @since BuddyPress (1.6.0)
 *
 * @uses do_action() Calls 'bp_verify_nonce_request' on $action.
 *
 * @param string $action Action nonce.
 * @param string $query_arg where to look for nonce in $_REQUEST.
 * @return bool True if the nonce is verified, otherwise false.
 */
function bp_verify_nonce_request( $action = '', $query_arg = '_wpnonce' ) {

	/** Home URL **************************************************************/

	// Parse home_url() into pieces to remove query-strings, strange characters,
	// and other funny things that plugins might to do to it.
	$parsed_home = parse_url( home_url( '/', ( is_ssl() ? 'https://' : 'http://' ) ) );

	// Maybe include the port, if it's included
	if ( isset( $parsed_home['port'] ) ) {
		$parsed_host = $parsed_home['host'] . ':' . $parsed_home['port'];
	} else {
		$parsed_host = $parsed_home['host'];
	}

	// Set the home URL for use in comparisons
	$home_url = trim( strtolower( $parsed_home['scheme'] . '://' . $parsed_host . $parsed_home['path'] ), '/' );

	/** Requested URL *********************************************************/

	// Maybe include the port, if it's included in home_url()
	if ( isset( $parsed_home['port'] ) ) {
		$request_host = $_SERVER['HTTP_HOST'] . ':' . $_SERVER['SERVER_PORT'];
	} else {
		$request_host = $_SERVER['HTTP_HOST'];
	}

	// Build the currently requested URL
	$scheme        = is_ssl() ? 'https://' : 'http://';
	$requested_url = strtolower( $scheme . $request_host . $_SERVER['REQUEST_URI'] );

	/** Look for match ********************************************************/

	// Filter the requested URL, for configurations like reverse proxying
	$matched_url = apply_filters( 'bp_verify_nonce_request_url', $requested_url );

	// Check the nonce
	$result = isset( $_REQUEST[$query_arg] ) ? wp_verify_nonce( $_REQUEST[$query_arg], $action ) : false;

	// Nonce check failed
	if ( empty( $result ) || empty( $action ) || ( strpos( $matched_url, $home_url ) !== 0 ) ) {
		$result = false;
	}

	// Do extra things
	do_action( 'bp_verify_nonce_request', $action, $result );

	return $result;
}

/** Requests ******************************************************************/

/**
 * Return true|false if this is a POST request
 *
 * @since BuddyPress (1.9.0)
 * @return bool
 */
function bp_is_post_request() {
	return (bool) ( 'POST' === strtoupper( $_SERVER['REQUEST_METHOD'] ) );
}

/**
 * Return true|false if this is a GET request
 *
 * @since BuddyPress (1.9.0)
 * @return bool
 */
function bp_is_get_request() {
	return (bool) ( 'GET' === strtoupper( $_SERVER['REQUEST_METHOD'] ) );
}


/** Miscellaneous hooks *******************************************************/

/**
 * Load the buddypress translation file for current language.
 *
 * @see load_textdomain() for a description of return values.
 *
 * @return bool True on success, false on failure.
 */
function bp_core_load_buddypress_textdomain() {
	// Try to load via load_plugin_textdomain() first, for future
	// wordpress.org translation downloads
	if ( load_plugin_textdomain( 'buddypress', false, 'buddypress/bp-languages' ) ) {
		return true;
	}

	// Nothing found in bp-languages, so try to load from WP_LANG_DIR
	$locale = apply_filters( 'buddypress_locale', get_locale() );
	$mofile = WP_LANG_DIR . '/buddypress-' . $locale . '.mo';

	return load_textdomain( 'buddypress', $mofile );
}
add_action ( 'bp_core_loaded', 'bp_core_load_buddypress_textdomain' );

/**
 * A javascript-free implementation of the search functions in BuddyPress.
 *
 * @param string $slug The slug to redirect to for searching.
 */
function bp_core_action_search_site( $slug = '' ) {

	if ( !bp_is_current_component( bp_get_search_slug() ) )
		return;

	if ( empty( $_POST['search-terms'] ) ) {
		bp_core_redirect( bp_get_root_domain() );
		return;
	}

	$search_terms = stripslashes( $_POST['search-terms'] );
	$search_which = !empty( $_POST['search-which'] ) ? $_POST['search-which'] : '';
	$query_string = '/?s=';

	if ( empty( $slug ) ) {
		switch ( $search_which ) {
			case 'posts':
				$slug = '';
				$var  = '/?s=';

				// If posts aren't displayed on the front page, find the post page's slug.
				if ( 'page' == get_option( 'show_on_front' ) ) {
					$page = get_post( get_option( 'page_for_posts' ) );

					if ( !is_wp_error( $page ) && !empty( $page->post_name ) ) {
						$slug = $page->post_name;
						$var  = '?s=';
					}
				}
				break;

			case 'blogs':
				$slug = bp_is_active( 'blogs' )  ? bp_get_blogs_root_slug()  : '';
				break;

			case 'forums':
				$slug = bp_is_active( 'forums' ) ? bp_get_forums_root_slug() : '';
				$query_string = '/?fs=';
				break;

			case 'groups':
				$slug = bp_is_active( 'groups' ) ? bp_get_groups_root_slug() : '';
				break;

			case 'members':
			default:
				$slug = bp_get_members_root_slug();
				break;
		}

		if ( empty( $slug ) && 'posts' != $search_which ) {
			bp_core_redirect( bp_get_root_domain() );
			return;
		}
	}

	bp_core_redirect( apply_filters( 'bp_core_search_site', home_url( $slug . $query_string . urlencode( $search_terms ) ), $search_terms ) );
}
add_action( 'bp_init', 'bp_core_action_search_site', 7 );

/**
 * Print the generation time in the footer of the site.
 */
function bp_core_print_generation_time() {
?>

<!-- Generated in <?php timer_stop(1); ?> seconds. (<?php echo get_num_queries(); ?> q) -->

	<?php
}
add_action( 'wp_footer', 'bp_core_print_generation_time' );

/** Nav Menu ******************************************************************/

/**
 * Create fake "post" objects for BP's logged-in nav menu for use in the WordPress "Menus" settings page.
 *
 * WordPress nav menus work by representing post or tax term data as a custom
 * post type, which is then used to populate the checkboxes that appear on
 * Dashboard > Appearance > Menu as well as the menu as rendered on the front
 * end. Most of the items in the BuddyPress set of nav items are neither posts
 * nor tax terms, so we fake a post-like object so as to be compatible with the
 * menu.
 *
 * This technique also allows us to generate links dynamically, so that, for
 * example, "My Profile" will always point to the URL of the profile of the
 * logged-in user.
 *
 * @since BuddyPress (1.9.0)
 *
 * @return mixed A URL or an array of dummy pages.
 */
function bp_nav_menu_get_loggedin_pages() {

	// Try to catch the cached version first
	if ( ! empty( buddypress()->wp_nav_menu_items->loggedin ) ) {
		return buddypress()->wp_nav_menu_items->loggedin;
	}

	// Pull up a list of items registered in BP's top-level nav array
	$bp_menu_items = buddypress()->bp_nav;

	// Alphabetize
	$bp_menu_items = bp_alpha_sort_by_key( $bp_menu_items, 'name' );

	// Some BP nav menu items will not be represented in bp_nav, because
	// they are not real BP components. We add them manually here.
	$bp_menu_items[] = array(
		'name' => __( 'Log Out', 'buddypress' ),
		'slug' => 'logout',
		'link' => wp_logout_url(),
	);

	// If there's nothing to show, we're done
	if ( count( $bp_menu_items ) < 1 ) {
		return false;
	}

	$page_args = array();

	foreach ( $bp_menu_items as $bp_item ) {
		$item_name = '';

		// Remove <span>number</span>
		$item_name = preg_replace( '/([.0-9]+)/', '', $bp_item['name'] );
		$item_name = trim( strip_tags( $item_name ) );

		$page_args[ $bp_item['slug'] ] = (object) array(
			'ID'             => -1,
			'post_title'     => $item_name,
			'post_author'    => 0,
			'post_date'      => 0,
			'post_excerpt'   => $bp_item['slug'],
			'post_type'      => 'page',
			'post_status'    => 'publish',
			'comment_status' => 'closed',
			'guid'           => $bp_item['link']
		);
	}

	if ( empty( buddypress()->wp_nav_menu_items ) ) {
		buddypress()->wp_nav_menu_items = new stdClass;
	}

	buddypress()->wp_nav_menu_items->loggedin = $page_args;

	return $page_args;
}

/**
 * Create fake "post" objects for BP's logged-out nav menu for use in the WordPress "Menus" settings page.
 *
 * WordPress nav menus work by representing post or tax term data as a custom
 * post type, which is then used to populate the checkboxes that appear on
 * Dashboard > Appearance > Menu as well as the menu as rendered on the front
 * end. Most of the items in the BuddyPress set of nav items are neither posts
 * nor tax terms, so we fake a post-like object so as to be compatible with the
 * menu.
 *
 * @since BuddyPress (1.9.0)
 *
 * @return mixed A URL or an array of dummy pages.
 */
function bp_nav_menu_get_loggedout_pages() {

	// Try to catch the cached version first
	if ( ! empty( buddypress()->wp_nav_menu_items->loggedout ) ) {
		return buddypress()->wp_nav_menu_items->loggedout;
	}

	$bp_menu_items = array();

	// Some BP nav menu items will not be represented in bp_nav, because
	// they are not real BP components. We add them manually here.
	$bp_menu_items[] = array(
		'name' => __( 'Log In', 'buddypress' ),
		'slug' => 'login',
		'link' => wp_login_url(),
	);

	// The Register page will not always be available (ie, when
	// registration is disabled)
	$bp_directory_page_ids = bp_core_get_directory_page_ids();

	if( ! empty( $bp_directory_page_ids['register'] ) ) {
		$register_page = get_post( $bp_directory_page_ids['register'] );
		$bp_menu_items[] = array(
			'name' => $register_page->post_title,
			'slug' => 'register',
			'link' => get_permalink( $register_page->ID ),
		);
	}

	// If there's nothing to show, we're done
	if ( count( $bp_menu_items ) < 1 ) {
		return false;
	}

	$page_args = array();

	foreach ( $bp_menu_items as $bp_item ) {
		$page_args[ $bp_item['slug'] ] = (object) array(
			'ID'             => -1,
			'post_title'     => $bp_item['name'],
			'post_author'    => 0,
			'post_date'      => 0,
			'post_excerpt'   => $bp_item['slug'],
			'post_type'      => 'page',
			'post_status'    => 'publish',
			'comment_status' => 'closed',
			'guid'           => $bp_item['link']
		);
	}

	if ( empty( buddypress()->wp_nav_menu_items ) ) {
		buddypress()->wp_nav_menu_items = new stdClass;
	}

	buddypress()->wp_nav_menu_items->loggedout = $page_args;

	return $page_args;
}

/**
 * Get the URL for a BuddyPress WP nav menu item, based on slug.
 *
 * BuddyPress-specific WP nav menu items have dynamically generated URLs,
 * based on the identity of the current user. This function lets you fetch the
 * proper URL for a given nav item slug (such as 'login' or 'messages').
 *
 * @since BuddyPress (1.9.0)
 *
 * @param string $slug The slug of the nav item: login, register, or one of the
 *        slugs from buddypress()->bp_nav.
 * @return string $nav_item_url The URL generated for the current user.
 */
function bp_nav_menu_get_item_url( $slug ) {
	$nav_item_url   = '';
	$nav_menu_items = bp_nav_menu_get_loggedin_pages();

	if ( isset( $nav_menu_items[ $slug ] ) ) {
		$nav_item_url = $nav_menu_items[ $slug ]->guid;
	}

	return $nav_item_url;
}
