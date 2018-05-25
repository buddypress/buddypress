<?php
/**
 * BuddyPress Common Functions.
 *
 * @package BuddyPress
 * @subpackage Functions
 * @since 1.5.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/** Versions ******************************************************************/

/**
 * Output the BuddyPress version.
 *
 * @since 1.6.0
 *
 */
function bp_version() {
	echo bp_get_version();
}
	/**
	 * Return the BuddyPress version.
	 *
	 * @since 1.6.0
	 *
	 * @return string The BuddyPress version.
	 */
	function bp_get_version() {
		return buddypress()->version;
	}

/**
 * Output the BuddyPress database version.
 *
 * @since 1.6.0
 *
 */
function bp_db_version() {
	echo bp_get_db_version();
}
	/**
	 * Return the BuddyPress database version.
	 *
	 * @since 1.6.0
	 *
	 * @return string The BuddyPress database version.
	 */
	function bp_get_db_version() {
		return buddypress()->db_version;
	}

/**
 * Output the BuddyPress database version.
 *
 * @since 1.6.0
 *
 */
function bp_db_version_raw() {
	echo bp_get_db_version_raw();
}
	/**
	 * Return the BuddyPress database version.
	 *
	 * @since 1.6.0
	 *
	 * @return string The BuddyPress version direct from the database.
	 */
	function bp_get_db_version_raw() {
		$bp = buddypress();
		return !empty( $bp->db_version_raw ) ? $bp->db_version_raw : 0;
	}

/** Functions *****************************************************************/

/**
 * Get the $wpdb base prefix, run through the 'bp_core_get_table_prefix' filter.
 *
 * The filter is intended primarily for use in multinetwork installations.
 *
 * @since 1.2.6
 *
 * @global object $wpdb WordPress database object.
 *
 * @return string Filtered database prefix.
 */
function bp_core_get_table_prefix() {
	global $wpdb;

	/**
	 * Filters the $wpdb base prefix.
	 *
	 * Intended primarily for use in multinetwork installations.
	 *
	 * @since 1.2.6
	 *
	 * @param string $base_prefix Base prefix to use.
	 */
	return apply_filters( 'bp_core_get_table_prefix', $wpdb->base_prefix );
}

/**
 * Sort an array of objects or arrays by a specific key/property.
 *
 * The main purpose for this function is so that you can avoid having to create
 * your own awkward callback function for usort().
 *
 * @since 2.2.0
 * @since 2.7.0 Added $preserve_keys parameter.
 *
 * @param array      $items         The items to be sorted. Its constituent items
 *                                  can be either associative arrays or objects.
 * @param string|int $key           The array index or property name to sort by.
 * @param string     $type          Sort type. 'alpha' for alphabetical, 'num'
 *                                  for numeric. Default: 'alpha'.
 * @param bool       $preserve_keys Whether to keep the keys or not.
 *
 * @return array $items The sorted array.
 */
function bp_sort_by_key( $items, $key, $type = 'alpha', $preserve_keys = false ) {
	$callback = function( $a, $b ) use ( $key, $type ) {
		$values = array( 0 => false, 1 => false );
		foreach ( func_get_args() as $indexi => $index ) {
			if ( isset( $index->{$key} ) ) {
				$values[ $indexi ] = $index->{$key};
			} elseif ( isset( $index[ $key ] ) ) {
				$values[ $indexi ] = $index[ $key ];
			}
		}

		if ( isset( $values[0], $values[1] ) ) {
			if ( 'num' === $type ) {
				$cmp = $values[0] - $values[1];
			} else {
				$cmp = strcmp( $values[0], $values[1] );
			}

			if ( 0 > $cmp ) {
				$retval = -1;
			} elseif ( 0 < $cmp ) {
				$retval = 1;
			} else {
				$retval = 0;
			}
			return $retval;
		} else {
			return 0;
		}
	};

	if ( true === $preserve_keys ) {
		uasort( $items, $callback );
	} else {
		usort( $items, $callback );
	}

	return $items;
}

/**
 * Sort an array of objects or arrays by alphabetically sorting by a specific key/property.
 *
 * For instance, if you have an array of WordPress post objects, you can sort
 * them by post_name as follows:
 *     $sorted_posts = bp_alpha_sort_by_key( $posts, 'post_name' );
 *
 * @since 1.9.0
 *
 * @param array      $items The items to be sorted. Its constituent items can be either associative arrays or objects.
 * @param string|int $key   The array index or property name to sort by.
 * @return array $items The sorted array.
 */
function bp_alpha_sort_by_key( $items, $key ) {
	return bp_sort_by_key( $items, $key, 'alpha' );
}

/**
 * Format numbers the BuddyPress way.
 *
 * @since 1.2.0
 *
 * @param int  $number   The number to be formatted.
 * @param bool $decimals Whether to use decimals. See {@link number_format_i18n()}.
 * @return string The formatted number.
 */
function bp_core_number_format( $number = 0, $decimals = false ) {

	// Force number to 0 if needed.
	if ( ! is_numeric( $number ) ) {
		$number = 0;
	}

	/**
	 * Filters the BuddyPress formatted number.
	 *
	 * @since 1.2.4
	 *
	 * @param string $value    BuddyPress formatted value.
	 * @param int    $number   The number to be formatted.
	 * @param bool   $decimals Whether or not to use decimals.
	 */
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
 * @since 1.6.0
 *
 * @param array $old_args_keys Old argument indexs, keyed to their positions.
 * @param array $func_args     The parameters passed to the originating function.
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
 * Merge user defined arguments into defaults array.
 *
 * This function is used throughout BuddyPress to allow for either a string or
 * array to be merged into another array. It is identical to wp_parse_args()
 * except it allows for arguments to be passively or aggressively filtered using
 * the optional $filter_key parameter. If no $filter_key is passed, no filters
 * are applied.
 *
 * @since 2.0.0
 *
 * @param string|array $args       Value to merge with $defaults.
 * @param array        $defaults   Array that serves as the defaults.
 * @param string       $filter_key String to key the filters from.
 * @return array Merged user defined values with defaults.
 */
function bp_parse_args( $args, $defaults = array(), $filter_key = '' ) {

	// Setup a temporary array from $args.
	if ( is_object( $args ) ) {
		$r = get_object_vars( $args );
	} elseif ( is_array( $args ) ) {
		$r =& $args;
	} else {
		wp_parse_str( $args, $r );
	}

	// Passively filter the args before the parse.
	if ( !empty( $filter_key ) ) {

		/**
		 * Filters the arguments key before parsing if filter key provided.
		 *
		 * This is a dynamic filter dependent on the specified key.
		 *
		 * @since 2.0.0
		 *
		 * @param array $r Array of arguments to use.
		 */
		$r = apply_filters( 'bp_before_' . $filter_key . '_parse_args', $r );
	}

	// Parse.
	if ( is_array( $defaults ) && !empty( $defaults ) ) {
		$r = array_merge( $defaults, $r );
	}

	// Aggressively filter the args after the parse.
	if ( !empty( $filter_key ) ) {

		/**
		 * Filters the arguments key after parsing if filter key provided.
		 *
		 * This is a dynamic filter dependent on the specified key.
		 *
		 * @since 2.0.0
		 *
		 * @param array $r Array of parsed arguments.
		 */
		$r = apply_filters( 'bp_after_' . $filter_key . '_parse_args', $r );
	}

	// Return the parsed results.
	return $r;
}

/**
 * Sanitizes a pagination argument based on both the request override and the
 * original value submitted via a query argument, likely to a template class
 * responsible for limiting the resultset of a template loop.
 *
 * @since 2.2.0
 *
 * @param string $page_arg The $_REQUEST argument to look for.
 * @param int    $page     The original page value to fall back to.
 * @return int A sanitized integer value, good for pagination.
 */
function bp_sanitize_pagination_arg( $page_arg = '', $page = 1 ) {

	// Check if request overrides exist.
	if ( isset( $_REQUEST[ $page_arg ] ) ) {

		// Get the absolute integer value of the override.
		$int = absint( $_REQUEST[ $page_arg ] );

		// If override is 0, do not use it. This prevents unlimited result sets.
		// @see https://buddypress.trac.wordpress.org/ticket/5796.
		if ( $int ) {
			$page = $int;
		}
	}

	return intval( $page );
}

/**
 * Sanitize an 'order' parameter for use in building SQL queries.
 *
 * Strings like 'DESC', 'desc', ' desc' will be interpreted into 'DESC'.
 * Everything else becomes 'ASC'.
 *
 * @since 1.8.0
 *
 * @param string $order The 'order' string, as passed to the SQL constructor.
 * @return string The sanitized value 'DESC' or 'ASC'.
 */
function bp_esc_sql_order( $order = '' ) {
	$order = strtoupper( trim( $order ) );
	return 'DESC' === $order ? 'DESC' : 'ASC';
}

/**
 * Escape special characters in a SQL LIKE clause.
 *
 * In WordPress 4.0, like_escape() was deprecated, due to incorrect
 * documentation and improper sanitization leading to a history of misuse. To
 * maintain compatibility with versions of WP before 4.0, we duplicate the
 * logic of the replacement, wpdb::esc_like().
 *
 * @since 2.1.0
 *
 * @see wpdb::esc_like() for more details on proper use.
 *
 * @param string $text The raw text to be escaped.
 * @return string Text in the form of a LIKE phrase. Not SQL safe. Run through
 *                wpdb::prepare() before use.
 */
function bp_esc_like( $text ) {
	global $wpdb;

	if ( method_exists( $wpdb, 'esc_like' ) ) {
		return $wpdb->esc_like( $text );
	} else {
		return addcslashes( $text, '_%\\' );
	}
}

/**
 * Are we running username compatibility mode?
 *
 * @since 1.5.0
 *
 * @todo Move to members component?
 *
 * @return bool False when compatibility mode is disabled, true when enabled.
 *              Default: false.
 */
function bp_is_username_compatibility_mode() {

	/**
	 * Filters whether or not to use username compatibility mode.
	 *
	 * @since 1.5.0
	 *
	 * @param bool $value Whether or not username compatibility mode should be used.
	 */
	return apply_filters( 'bp_is_username_compatibility_mode', defined( 'BP_ENABLE_USERNAME_COMPATIBILITY_MODE' ) && BP_ENABLE_USERNAME_COMPATIBILITY_MODE );
}

/**
 * Should we use the WP Toolbar?
 *
 * The WP Toolbar, introduced in WP 3.1, is fully supported in BuddyPress as
 * of BP 1.5. For BP 1.6, the WP Toolbar is the default.
 *
 * @since 1.5.0
 *
 * @return bool Default: true. False when WP Toolbar support is disabled.
 */
function bp_use_wp_admin_bar() {

	// Default to true (to avoid loading deprecated BuddyBar code).
	$use_admin_bar = true;

	// Has the WP Toolbar constant been explicitly opted into?
	if ( defined( 'BP_USE_WP_ADMIN_BAR' ) ) {
		$use_admin_bar = (bool) BP_USE_WP_ADMIN_BAR;

	// ...or is the old BuddyBar being forced back into use?
	} elseif ( bp_force_buddybar( false ) ) {
		$use_admin_bar = false;
	}

	/**
	 * Filters whether or not to use the admin bar.
	 *
	 * @since 1.5.0
	 *
	 * @param bool $use_admin_bar Whether or not to use the admin bar.
	 */
	return (bool) apply_filters( 'bp_use_wp_admin_bar', $use_admin_bar );
}


/**
 * Return the parent forum ID for the Legacy Forums abstraction layer.
 *
 * @since 1.5.0
 * @since 3.0.0 Supported for compatibility with bbPress 2.
 *
 * @return int Forum ID.
 */
function bp_forums_parent_forum_id() {

	/**
	 * Filters the parent forum ID for the bbPress abstraction layer.
	 *
	 * @since 1.5.0
	 *
	 * @param int BP_FORUMS_PARENT_FORUM_ID The Parent forum ID constant.
	 */
	return apply_filters( 'bp_forums_parent_forum_id', BP_FORUMS_PARENT_FORUM_ID );
}

/** Directory *****************************************************************/

/**
 * Returns an array of core component IDs.
 *
 * @since 2.1.0
 *
 * @return array
 */
function bp_core_get_packaged_component_ids() {
	$components = array(
		'activity',
		'members',
		'groups',
		'blogs',
		'xprofile',
		'friends',
		'messages',
		'settings',
		'notifications',
	);

	return $components;
}

/**
 * Fetch a list of BP directory pages from the appropriate meta table.
 *
 * @since 1.5.0
 *
 * @param string $status 'active' to return only pages associated with active components, 'all' to return all saved
 *                       pages. When running save routines, use 'all' to avoid removing data related to inactive
 *                       components. Default: 'active'.
 * @return array|string An array of page IDs, keyed by component names, or an
 *                      empty string if the list is not found.
 */
function bp_core_get_directory_page_ids( $status = 'active' ) {
	$page_ids = bp_get_option( 'bp-pages', array() );

	// Loop through pages
	foreach ( $page_ids as $component_name => $page_id ) {

		// Ensure that empty indexes are unset. Should only matter in edge cases.
		if ( empty( $component_name ) || empty( $page_id ) ) {
			unset( $page_ids[ $component_name ] );
		}

		// Trashed pages should never appear in results.
		if ( 'trash' == get_post_status( $page_id ) ) {
			unset( $page_ids[ $component_name ] );
		}

		// 'register' and 'activate' do not have components, but should be whitelisted.
		if ( in_array( $component_name, array( 'register', 'activate' ), true ) ) {
			continue;
		}

		// Remove inactive component pages.
		if ( ( 'active' === $status ) && ! bp_is_active( $component_name ) ) {
			unset( $page_ids[ $component_name ] );
		}
	}

	/**
	 * Filters the list of BP directory pages from the appropriate meta table.
	 *
	 * @since 1.5.0
	 * @since 2.9.0 Add $status parameter
	 *
	 * @param array  $page_ids Array of directory pages.
	 * @param string $status   Page status to limit results to
	 */
	return (array) apply_filters( 'bp_core_get_directory_page_ids', $page_ids, $status );
}

/**
 * Get the page ID corresponding to a component directory.
 *
 * @since 2.6.0
 *
 * @param string|null $component The slug representing the component. Defaults to the current component.
 * @return int|false The ID of the directory page associated with the component. False if none is found.
 */
function bp_core_get_directory_page_id( $component = null ) {
	if ( ! $component ) {
		$component = bp_current_component();
	}

	$bp_pages = bp_core_get_directory_page_ids( 'all' );

	$page_id = false;
	if ( $component && isset( $bp_pages[ $component ] ) ) {
		$page_id = (int) $bp_pages[ $component ];
	}

	return $page_id;
}

/**
 * Store the list of BP directory pages in the appropriate meta table.
 *
 * The bp-pages data is stored in site_options (falls back to options on non-MS),
 * in an array keyed by blog_id. This allows you to change your
 * bp_get_root_blog_id() and go through the setup process again.
 *
 * @since 1.5.0
 *
 * @param array $blog_page_ids The IDs of the WP pages corresponding to BP
 *                             component directories.
 */
function bp_core_update_directory_page_ids( $blog_page_ids ) {
	bp_update_option( 'bp-pages', $blog_page_ids );
}

/**
 * Get names and slugs for BuddyPress component directory pages.
 *
 * @since 1.5.0
 *
 * @return object Page names, IDs, and slugs.
 */
function bp_core_get_directory_pages() {
	global $wpdb;

	// Look in cache first.
	$pages = wp_cache_get( 'directory_pages', 'bp_pages' );

	if ( false === $pages ) {

		// Set pages as standard class.
		$pages = new stdClass;

		// Get pages and IDs.
		$page_ids = bp_core_get_directory_page_ids();
		if ( !empty( $page_ids ) ) {

			// Always get page data from the root blog, except on multiblog mode, when it comes
			// from the current blog.
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

						// Get the slug.
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

		wp_cache_set( 'directory_pages', $pages, 'bp_pages' );
	}

	/**
	 * Filters the names and slugs for BuddyPress component directory pages.
	 *
	 * @since 1.5.0
	 *
	 * @param object $pages Object holding page names and slugs.
	 */
	return apply_filters( 'bp_core_get_directory_pages', $pages );
}

/**
 * Creates necessary directory pages.
 *
 * Directory pages are those WordPress pages used by BP components to display
 * content (eg, the 'groups' page created by BP).
 *
 * @since 1.7.0
 *
 * @param array  $components Components to create pages for.
 * @param string $existing   'delete' if you want to delete existing page mappings
 *                           and replace with new ones. Otherwise existing page mappings
 *                           are kept, and the gaps filled in with new pages. Default: 'keep'.
 */
function bp_core_add_page_mappings( $components, $existing = 'keep' ) {

	// If no value is passed, there's nothing to do.
	if ( empty( $components ) ) {
		return;
	}

	// Make sure that the pages are created on the root blog no matter which
	// dashboard the setup is being run on.
	if ( ! bp_is_root_blog() ) {
		switch_to_blog( bp_get_root_blog_id() );
	}

	$pages = bp_core_get_directory_page_ids( 'all' );

	// Delete any existing pages.
	if ( 'delete' === $existing ) {
		foreach ( $pages as $page_id ) {
			wp_delete_post( $page_id, true );
		}

		$pages = array();
	}

	$page_titles = bp_core_get_directory_page_default_titles();

	$pages_to_create = array();
	foreach ( array_keys( $components ) as $component_name ) {
		if ( ! isset( $pages[ $component_name ] ) && isset( $page_titles[ $component_name ] ) ) {
			$pages_to_create[ $component_name ] = $page_titles[ $component_name ];
		}
	}

	// Register and Activate are not components, but need pages when
	// registration is enabled.
	if ( bp_get_signup_allowed() ) {
		foreach ( array( 'register', 'activate' ) as $slug ) {
			if ( ! isset( $pages[ $slug ] ) ) {
				$pages_to_create[ $slug ] = $page_titles[ $slug ];
			}
		}
	}

	// No need for a Sites directory unless we're on multisite.
	if ( ! is_multisite() && isset( $pages_to_create['blogs'] ) ) {
		unset( $pages_to_create['blogs'] );
	}

	// Members must always have a page, no matter what.
	if ( ! isset( $pages['members'] ) && ! isset( $pages_to_create['members'] ) ) {
		$pages_to_create['members'] = $page_titles['members'];
	}

	// Create the pages.
	foreach ( $pages_to_create as $component_name => $page_name ) {
		$exists = get_page_by_path( $component_name );

		// If page already exists, use it.
		if ( ! empty( $exists ) ) {
			$pages[ $component_name ] = $exists->ID;
		} else {
			$pages[ $component_name ] = wp_insert_post( array(
				'comment_status' => 'closed',
				'ping_status'    => 'closed',
				'post_status'    => 'publish',
				'post_title'     => $page_name,
				'post_type'      => 'page',
			) );
		}
	}

	// Save the page mapping.
	bp_update_option( 'bp-pages', $pages );

	// If we had to switch_to_blog, go back to the original site.
	if ( ! bp_is_root_blog() ) {
		restore_current_blog();
	}
}

/**
 * Get the default page titles for BP directory pages.
 *
 * @since 2.7.0
 *
 * @return array
 */
function bp_core_get_directory_page_default_titles() {
	$page_default_titles = array(
		'activity' => _x( 'Activity', 'Page title for the Activity directory.',       'buddypress' ),
		'groups'   => _x( 'Groups',   'Page title for the Groups directory.',         'buddypress' ),
		'blogs'    => _x( 'Sites',    'Page title for the Sites directory.',          'buddypress' ),
		'members'  => _x( 'Members',  'Page title for the Members directory.',        'buddypress' ),
		'activate' => _x( 'Activate', 'Page title for the user activation screen.',   'buddypress' ),
		'register' => _x( 'Register', 'Page title for the user registration screen.', 'buddypress' ),
	);

	/**
	 * Filters the default page titles array
	 *
	 * @since 2.7.0
	 *
	 * @param array $page_default_titles the array of default WP (post_title) titles.
	 */
	return apply_filters( 'bp_core_get_directory_page_default_titles', $page_default_titles );
}

/**
 * Remove the entry from bp_pages when the corresponding WP page is deleted.
 *
 * Bails early on multisite installations when not viewing the root site.
 *
 * @link https://buddypress.trac.wordpress.org/ticket/6226
 *
 * @since 2.2.0
 *
 * @param int $post_id Post ID.
 */
function bp_core_on_directory_page_delete( $post_id ) {

	// Stop if we are not on the main BP root blog.
	if ( ! bp_is_root_blog() ) {
		return;
	}

	$page_ids       = bp_core_get_directory_page_ids( 'all' );
	$component_name = array_search( $post_id, $page_ids );

	if ( ! empty( $component_name ) ) {
		unset( $page_ids[ $component_name ] );
	}

	bp_core_update_directory_page_ids( $page_ids );
}
add_action( 'delete_post', 'bp_core_on_directory_page_delete' );

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
 * @since 1.5.0
 *
 * @param string $root_slug The root slug, which comes from $bp->pages->[component]->slug.
 * @return string The short slug for use in the middle of URLs.
 */
function bp_core_component_slug_from_root_slug( $root_slug ) {
	$slug_chunks = explode( '/', $root_slug );
	$slug        = array_pop( $slug_chunks );

	/**
	 * Filters the default component slug from a WP page root_slug.
	 *
	 * @since 1.5.0
	 *
	 * @param string $slug      Short slug for use in the middle of URLs.
	 * @param string $root_slug The root slug which comes from $bp->pages-[component]->slug.
	 */
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
 * @since 1.0.0
 *
 * @param string $slug The slug of the component being added to the root list.
 */
function bp_core_add_root_component( $slug ) {
	$bp = buddypress();

	if ( empty( $bp->pages ) ) {
		$bp->pages = bp_core_get_directory_pages();
	}

	$match = false;

	// Check if the slug is registered in the $bp->pages global.
	foreach ( (array) $bp->pages as $key => $page ) {
		if ( $key == $slug || $page->slug == $slug ) {
			$match = true;
		}
	}

	// Maybe create the add_root array.
	if ( empty( $bp->add_root ) ) {
		$bp->add_root = array();
	}

	// If there was no match, add a page for this root component.
	if ( empty( $match ) ) {
		$add_root_items   = $bp->add_root;
		$add_root_items[] = $slug;
		$bp->add_root     = $add_root_items;
	}

	// Make sure that this component is registered as requiring a top-level directory.
	if ( isset( $bp->{$slug} ) ) {
		$bp->loaded_components[$bp->{$slug}->slug] = $bp->{$slug}->id;
		$bp->{$slug}->has_directory = true;
	}
}

/**
 * Create WordPress pages to be used as BP component directories.
 *
 * @since 1.5.0
 */
function bp_core_create_root_component_page() {

	// Get BuddyPress.
	$bp = buddypress();

	$new_page_ids = array();

	foreach ( (array) $bp->add_root as $slug ) {
		$new_page_ids[ $slug ] = wp_insert_post( array(
			'comment_status' => 'closed',
			'ping_status'    => 'closed',
			'post_title'     => ucwords( $slug ),
			'post_status'    => 'publish',
			'post_type'      => 'page'
		) );
	}

	$page_ids = array_merge( $new_page_ids, bp_core_get_directory_page_ids( 'all' ) );
	bp_core_update_directory_page_ids( $page_ids );
}

/**
 * Add illegal blog names to WP so that root components will not conflict with blog names on a subdirectory installation.
 *
 * For example, it would stop someone creating a blog with the slug "groups".
 *
 * @since 1.0.0
 *
 * @todo Deprecate?
 */
function bp_core_add_illegal_names() {
	update_site_option( 'illegal_names', get_site_option( 'illegal_names' ), array() );
}

/**
 * Get the 'search' query argument for a given component.
 *
 * @since 2.4.0
 * @since 2.7.0 The `$component` parameter was made optional, with the current component
 *              as the fallback value.
 *
 * @param string|null $component Optional. Component name. Defaults to current component.
 * @return string|bool Query argument on success. False on failure.
 */
function bp_core_get_component_search_query_arg( $component = null ) {
	if ( ! $component ) {
		$component = bp_current_component();
	}

	$query_arg = false;
	if ( isset( buddypress()->{$component}->search_query_arg ) ) {
		$query_arg = sanitize_title( buddypress()->{$component}->search_query_arg );
	}

	/**
	 * Filters the query arg for a component search string.
	 *
	 * @since 2.4.0
	 *
	 * @param string $query_arg Query argument.
	 * @param string $component Component name.
	 */
	return apply_filters( 'bp_core_get_component_search_query_arg', $query_arg, $component );
}

/**
 * Determine whether BuddyPress should register the bp-themes directory.
 *
 * @since 1.9.0
 *
 * @return bool True if bp-themes should be registered, false otherwise.
 */
function bp_do_register_theme_directory() {
	// If bp-default exists in another theme directory, bail.
	// This ensures that the version of bp-default in the regular themes
	// directory will always take precedence, as part of a migration away
	// from the version packaged with BuddyPress.
	foreach ( array_values( (array) $GLOBALS['wp_theme_directories'] ) as $directory ) {
		if ( is_dir( $directory . '/bp-default' ) ) {
			return false;
		}
	}

	// If the current theme is bp-default (or a bp-default child), BP
	// should register its directory.
	$register = 'bp-default' === get_stylesheet() || 'bp-default' === get_template();

	// Legacy sites continue to have the theme registered.
	if ( empty( $register ) && ( 1 == get_site_option( '_bp_retain_bp_default' ) ) ) {
		$register = true;
	}

	/**
	 * Filters whether BuddyPress should register the bp-themes directory.
	 *
	 * @since 1.9.0
	 *
	 * @param bool $register If bp-themes should be registered.
	 */
	return apply_filters( 'bp_do_register_theme_directory', $register );
}

/** URI ***********************************************************************/

/**
 * Return the domain for the root blog.
 *
 * Eg: http://example.com OR https://example.com
 *
 * @since 1.0.0
 *
 * @return string The domain URL for the blog.
 */
function bp_core_get_root_domain() {

	$domain = get_home_url( bp_get_root_blog_id() );

	/**
	 * Filters the domain for the root blog.
	 *
	 * @since 1.0.1
	 *
	 * @param string $domain The domain URL for the blog.
	 */
	return apply_filters( 'bp_core_get_root_domain', $domain );
}

/**
 * Perform a status-safe wp_redirect() that is compatible with BP's URI parser.
 *
 * @since 1.0.0
 *
 * @param string $location The redirect URL.
 * @param int    $status   Optional. The numeric code to give in the redirect
 *                         headers. Default: 302.
 */
function bp_core_redirect( $location = '', $status = 302 ) {

	// On some setups, passing the value of wp_get_referer() may result in an
	// empty value for $location, which results in an error. Ensure that we
	// have a valid URL.
	if ( empty( $location ) ) {
		$location = bp_get_root_domain();
	}

	// Make sure we don't call status_header() in bp_core_do_catch_uri() as this
	// conflicts with wp_redirect() and wp_safe_redirect().
	buddypress()->no_status_set = true;

	wp_safe_redirect( $location, $status );

	// If PHPUnit is running, do not kill execution.
	if ( ! defined( 'BP_TESTS_DIR' ) ) {
		die;
	}
}

/**
 * Return the URL path of the referring page.
 *
 * This is a wrapper for `wp_get_referer()` that sanitizes the referer URL to
 * a webroot-relative path. For example, 'http://example.com/foo/' will be
 * reduced to '/foo/'.
 *
 * @since 2.3.0
 *
 * @return bool|string Returns false on error, a URL path on success.
 */
function bp_get_referer_path() {
	$referer = wp_get_referer();

	if ( false === $referer ) {
		return false;
	}

	// Turn into an absolute path.
	$referer = preg_replace( '|https?\://[^/]+/|', '/', $referer );

	return $referer;
}

/**
 * Get the path of the current site.
 *
 * @since 1.0.0
 *
 * @global object $current_site
 *
 * @return string URL to the current site.
 */
function bp_core_get_site_path() {
	global $current_site;

	if ( is_multisite() ) {
		$site_path = $current_site->path;
	} else {
		$site_path = (array) explode( '/', home_url() );

		if ( count( $site_path ) < 2 ) {
			$site_path = '/';
		} else {
			// Unset the first three segments (http(s)://example.com part).
			unset( $site_path[0] );
			unset( $site_path[1] );
			unset( $site_path[2] );

			if ( !count( $site_path ) ) {
				$site_path = '/';
			} else {
				$site_path = '/' . implode( '/', $site_path ) . '/';
			}
		}
	}

	/**
	 * Filters the path of the current site.
	 *
	 * @since 1.2.0
	 *
	 * @param string $site_path URL to the current site.
	 */
	return apply_filters( 'bp_core_get_site_path', $site_path );
}

/** Time **********************************************************************/

/**
 * Get the current GMT time to save into the DB.
 *
 * @since 1.2.6
 *
 * @param bool   $gmt  True to use GMT (rather than local) time. Default: true.
 * @param string $type See the 'type' parameter in {@link current_time()}.
 *                     Default: 'mysql'.
 * @return string Current time in 'Y-m-d h:i:s' format.
 */
function bp_core_current_time( $gmt = true, $type = 'mysql' ) {

	/**
	 * Filters the current GMT time to save into the DB.
	 *
	 * @since 1.2.6
	 *
	 * @param string $value Current GMT time.
	 */
	return apply_filters( 'bp_core_current_time', current_time( $type, $gmt ) );
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
 * @since 1.0.0
 *
 * @param int|string $older_date The earlier time from which you're calculating
 *                               the time elapsed. Enter either as an integer Unix timestamp,
 *                               or as a date string of the format 'Y-m-d h:i:s'.
 * @param int|bool   $newer_date Optional. Unix timestamp of date to compare older
 *                               date to. Default: false (current time).
 * @return string String representing the time since the older date, eg
 *         "2 hours and 50 minutes".
 */
function bp_core_time_since( $older_date, $newer_date = false ) {

	/**
	 * Filters whether or not to bypass BuddyPress' time_since calculations.
	 *
	 * @since 1.7.0
	 *
	 * @param bool   $value      Whether or not to bypass.
	 * @param string $older_date Earlier time from which we're calculating time elapsed.
	 * @param string $newer_date Unix timestamp of date to compare older time to.
	 */
	$pre_value = apply_filters( 'bp_core_time_since_pre', false, $older_date, $newer_date );
	if ( false !== $pre_value ) {
		return $pre_value;
	}

	/**
	 * Filters the value to use if the time since is unknown.
	 *
	 * @since 1.5.0
	 *
	 * @param string $value String representing the time since the older date.
	 */
	$unknown_text   = apply_filters( 'bp_core_time_since_unknown_text',   __( 'sometime',  'buddypress' ) );

	/**
	 * Filters the value to use if the time since is right now.
	 *
	 * @since 1.5.0
	 *
	 * @param string $value String representing the time since the older date.
	 */
	$right_now_text = apply_filters( 'bp_core_time_since_right_now_text', __( 'right now', 'buddypress' ) );

	/**
	 * Filters the value to use if the time since is some time ago.
	 *
	 * @since 1.5.0
	 *
	 * @param string $value String representing the time since the older date.
	 */
	$ago_text       = apply_filters( 'bp_core_time_since_ago_text',       __( '%s ago',    'buddypress' ) );

	// Array of time period chunks.
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
	$newer_date = ( !$newer_date ) ? bp_core_current_time( true, 'timestamp' ) : $newer_date;

	// Difference in seconds.
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

		// Step one: the first chunk.
		for ( $i = 0, $j = count( $chunks ); $i < $j; ++$i ) {
			$seconds = $chunks[$i];

			// Finding the biggest chunk (if the chunk fits, break).
			$count = floor( $since / $seconds );
			if ( 0 != $count ) {
				break;
			}
		}

		// If $i iterates all the way to $j, then the event happened 0 seconds ago.
		if ( !isset( $chunks[$i] ) ) {
			$output = $right_now_text;

		} else {

			// Set output var.
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
			// purposes.
			if ( $i + 2 < $j ) {
				$seconds2 = $chunks[$i + 1];
				$count2   = floor( ( $since - ( $seconds * $count ) ) / $seconds2 );

				// Add to output var.
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

			// No output, so happened right now.
			if ( ! (int) trim( $output ) ) {
				$output = $right_now_text;
			}
		}
	}

	// Append 'ago' to the end of time-since if not 'right now'.
	if ( $output != $right_now_text ) {
		$output = sprintf( $ago_text, $output );
	}

	/**
	 * Filters the English-language representation of the time elapsed since a given date.
	 *
	 * @since 1.7.0
	 *
	 * @param string $output     Final 'time since' string.
	 * @param string $older_date Earlier time from which we're calculating time elapsed.
	 * @param string $newer_date Unix timestamp of date to compare older time to.
	 */
	return apply_filters( 'bp_core_time_since', $output, $older_date, $newer_date );
}

/**
 * Output an ISO-8601 date from a date string.
 *
 * @since 2.7.0
 *
 * @param string String of date to convert. Timezone should be UTC before using this.
 * @return string|null
 */
 function bp_core_iso8601_date( $timestamp = '' ) {
	echo bp_core_get_iso8601_date( $timestamp );
}
	/**
	 * Return an ISO-8601 date from a date string.
	 *
	 * @since 2.7.0
	 *
	 * @param string String of date to convert. Timezone should be UTC before using this.
	 * @return string
	 */
	 function bp_core_get_iso8601_date( $timestamp = '' ) {
		if ( ! $timestamp ) {
			return '';
		}

		try {
			$date = new DateTime( $timestamp, new DateTimeZone( 'UTC' ) );

		// Not a valid date, so return blank string.
		} catch( Exception $e ) {
			return '';
		}

		return $date->format( DateTime::ISO8601 );
	}

/** Messages ******************************************************************/

/**
 * Add a feedback (error/success) message to the WP cookie so it can be displayed after the page reloads.
 *
 * @since 1.0.0
 *
 * @param string $message Feedback message to be displayed.
 * @param string $type    Message type. 'updated', 'success', 'error', 'warning'.
 *                        Default: 'success'.
 */
function bp_core_add_message( $message, $type = '' ) {

	// Success is the default.
	if ( empty( $type ) ) {
		$type = 'success';
	}

	// Send the values to the cookie for page reload display.
	@setcookie( 'bp-message',      $message, time() + 60 * 60 * 24, COOKIEPATH, COOKIE_DOMAIN, is_ssl() );
	@setcookie( 'bp-message-type', $type,    time() + 60 * 60 * 24, COOKIEPATH, COOKIE_DOMAIN, is_ssl() );

	// Get BuddyPress.
	$bp = buddypress();

	/**
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
 * @since 1.1.0
 *
 */
function bp_core_setup_message() {

	// Get BuddyPress.
	$bp = buddypress();

	if ( empty( $bp->template_message ) && isset( $_COOKIE['bp-message'] ) ) {
		$bp->template_message = stripslashes( $_COOKIE['bp-message'] );
	}

	if ( empty( $bp->template_message_type ) && isset( $_COOKIE['bp-message-type'] ) ) {
		$bp->template_message_type = stripslashes( $_COOKIE['bp-message-type'] );
	}

	add_action( 'template_notices', 'bp_core_render_message' );

	if ( isset( $_COOKIE['bp-message'] ) ) {
		@setcookie( 'bp-message', false, time() - 1000, COOKIEPATH, COOKIE_DOMAIN, is_ssl() );
	}

	if ( isset( $_COOKIE['bp-message-type'] ) ) {
		@setcookie( 'bp-message-type', false, time() - 1000, COOKIEPATH, COOKIE_DOMAIN, is_ssl() );
	}
}
add_action( 'bp_actions', 'bp_core_setup_message', 5 );

/**
 * Render the 'template_notices' feedback message.
 *
 * The hook action 'template_notices' is used to call this function, it is not
 * called directly.
 *
 * @since 1.1.0
 */
function bp_core_render_message() {

	// Get BuddyPress.
	$bp = buddypress();

	if ( !empty( $bp->template_message ) ) :
		$type    = ( 'success' === $bp->template_message_type ) ? 'updated' : 'error';

		/**
		 * Filters the 'template_notices' feedback message content.
		 *
		 * @since 1.5.5
		 *
		 * @param string $template_message Feedback message content.
		 * @param string $type             The type of message being displayed.
		 *                                 Either 'updated' or 'error'.
		 */
		$content = apply_filters( 'bp_core_render_message_content', $bp->template_message, $type ); ?>

		<div id="message" class="bp-template-notice <?php echo esc_attr( $type ); ?>">

			<?php echo $content; ?>

		</div>

	<?php

		/**
		 * Fires after the display of any template_notices feedback messages.
		 *
		 * @since 1.1.0
		 */
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
 * @since 1.0.0
 *
 *       usermeta table.
 *
 * @return false|null Returns false if there is nothing to do.
 */
function bp_core_record_activity() {

	// Bail if user is not logged in.
	if ( ! is_user_logged_in() ) {
		return false;
	}

	// Get the user ID.
	$user_id = bp_loggedin_user_id();

	// Bail if user is not active.
	if ( bp_is_user_inactive( $user_id ) ) {
		return false;
	}

	// Get the user's last activity.
	$activity = bp_get_user_last_activity( $user_id );

	// Make sure it's numeric.
	if ( ! is_numeric( $activity ) ) {
		$activity = strtotime( $activity );
	}

	// Get current time.
	$current_time = bp_core_current_time( true, 'timestamp' );

	// Use this action to detect the very first activity for a given member.
	if ( empty( $activity ) ) {

		/**
		 * Fires inside the recording of an activity item.
		 *
		 * Use this action to detect the very first activity for a given member.
		 *
		 * @since 1.6.0
		 *
		 * @param int $user_id ID of the user whose activity is recorded.
		 */
		do_action( 'bp_first_activity_for_member', $user_id );
	}

	// If it's been more than 5 minutes, record a newer last-activity time.
	if ( empty( $activity ) || ( $current_time >= strtotime( '+5 minutes', $activity ) ) ) {
		bp_update_user_last_activity( $user_id, date( 'Y-m-d H:i:s', $current_time ) );
	}
}
add_action( 'wp_head', 'bp_core_record_activity' );

/**
 * Format last activity string based on time since date given.
 *
 * @since 1.0.0
 *
 *       representation of the time elapsed.
 *
 * @param int|string $last_activity_date The date of last activity.
 * @param string     $string             A sprintf()-able statement of the form 'active %s'.
 * @return string $last_active A string of the form '3 years ago'.
 */
function bp_core_get_last_activity( $last_activity_date = '', $string = '' ) {

	// Setup a default string if none was passed.
	$string = empty( $string )
		? '%s'     // Gettext placeholder.
		: $string;

	// Use the string if a last activity date was passed.
	$last_active = empty( $last_activity_date )
		? __( 'Not recently active', 'buddypress' )
		: sprintf( $string, bp_core_time_since( $last_activity_date ) );

	/**
	 * Filters last activity string based on time since date given.
	 *
	 * @since 1.2.0
	 *
	 * @param string $last_active        Last activity string based on time since date given.
	 * @param string $last_activity_date The date of last activity.
	 * @param string $string             A sprintf()-able statement of the form 'active %s'.
	 */
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
 *    $friend_count = bp_get_user_meta( $user_id, 'total_friend_count', true );
 * If you must use WP's _user_meta() functions directly for some reason, you
 * should use this function to determine the $key parameter, eg
 *    $friend_count = get_user_meta( $user_id, bp_get_user_meta_key( 'total_friend_count' ), true );
 * If using the WP functions, do not not hardcode your meta keys.
 *
 * @since 1.5.0
 *
 * @param string|bool $key The usermeta meta_key.
 * @return string $key The usermeta meta_key.
 */
function bp_get_user_meta_key( $key = false ) {

	/**
	 * Filters the meta_key for a given piece of user metadata.
	 *
	 * @since 1.5.0
	 *
	 * @param string $key The usermeta meta key.
	 */
	return apply_filters( 'bp_get_user_meta_key', $key );
}

/**
 * Get a piece of usermeta.
 *
 * This is a wrapper for get_user_meta() that allows for easy use of
 * bp_get_user_meta_key(), thereby increasing compatibility with non-standard
 * BP setups.
 *
 * @since 1.5.0
 *
 * @see get_user_meta() For complete details about parameters and return values.
 *
 * @param int    $user_id The ID of the user whose meta you're fetching.
 * @param string $key     The meta key to retrieve.
 * @param bool   $single  Whether to return a single value.
 * @return mixed Will be an array if $single is false. Will be value of meta data field if $single
 *               is true.
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
 * @since 1.5.0
 *
 * @see update_user_meta() For complete details about parameters and return values.
 *
 * @param int    $user_id    The ID of the user whose meta you're setting.
 * @param string $key        The meta key to set.
 * @param mixed  $value      Metadata value.
 * @param mixed  $prev_value Optional. Previous value to check before removing.
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
 * @since 1.5.0
 *
 * @see delete_user_meta() For complete details about parameters and return values.
 *
 * @param int    $user_id The ID of the user whose meta you're deleting.
 * @param string $key     The meta key to delete.
 * @param mixed  $value   Optional. Metadata value.
 * @return bool False for failure. True for success.
 */
function bp_delete_user_meta( $user_id, $key, $value = '' ) {
	return delete_user_meta( $user_id, bp_get_user_meta_key( $key ), $value );
}

/** Embeds ********************************************************************/

/**
 * Initializes {@link BP_Embed} after everything is loaded.
 *
 * @since 1.5.0
 */
function bp_embed_init() {

	// Get BuddyPress.
	$bp = buddypress();

	if ( empty( $bp->embed ) ) {
		$bp->embed = new BP_Embed();
	}
}
add_action( 'bp_init', 'bp_embed_init', 9 );

/**
 * Are oembeds allowed in activity items?
 *
 * @since 1.5.0
 *
 * @return bool False when activity embed support is disabled; true when
 *              enabled. Default: true.
 */
function bp_use_embed_in_activity() {

	/**
	 * Filters whether or not oEmbeds are allowed in activity items.
	 *
	 * @since 1.5.0
	 *
	 * @param bool $value Whether or not oEmbeds are allowed.
	 */
	return apply_filters( 'bp_use_oembed_in_activity', !defined( 'BP_EMBED_DISABLE_ACTIVITY' ) || !BP_EMBED_DISABLE_ACTIVITY );
}

/**
 * Are oembeds allowed in activity replies?
 *
 * @since 1.5.0
 *
 * @return bool False when activity replies embed support is disabled; true
 *              when enabled. Default: true.
 */
function bp_use_embed_in_activity_replies() {

	/**
	 * Filters whether or not oEmbeds are allowed in activity replies.
	 *
	 * @since 1.5.0
	 *
	 * @param bool $value Whether or not oEmbeds are allowed.
	 */
	return apply_filters( 'bp_use_embed_in_activity_replies', !defined( 'BP_EMBED_DISABLE_ACTIVITY_REPLIES' ) || !BP_EMBED_DISABLE_ACTIVITY_REPLIES );
}

/**
 * Are oembeds allowed in private messages?
 *
 * @since 1.5.0
 *
 * @return bool False when private message embed support is disabled; true when
 *              enabled. Default: true.
 */
function bp_use_embed_in_private_messages() {

	/**
	 * Filters whether or not oEmbeds are allowed in private messages.
	 *
	 * @since 1.5.0
	 *
	 * @param bool $value Whether or not oEmbeds are allowed.
	 */
	return apply_filters( 'bp_use_embed_in_private_messages', !defined( 'BP_EMBED_DISABLE_PRIVATE_MESSAGES' ) || !BP_EMBED_DISABLE_PRIVATE_MESSAGES );
}

/**
 * Extracts media metadata from a given content.
 *
 * @since 2.6.0
 *
 * @param string     $content The content to check.
 * @param string|int $type    The type to check. Can also use a bitmask. See the class constants in the
 *                             BP_Media_Extractor class for more info.
 * @return false|array If media exists, will return array of media metadata. Else, boolean false.
 */
function bp_core_extract_media_from_content( $content = '', $type = 'all' ) {
	if ( is_string( $type ) ) {
		$class = new ReflectionClass( 'BP_Media_Extractor' );
		$bitmask = $class->getConstant( strtoupper( $type ) );
	} else {
		$bitmask = (int) $type;
	}

	// Type isn't valid, so bail.
	if ( empty( $bitmask ) ) {
		return false;
	}

	$x = new BP_Media_Extractor;
	$media = $x->extract( $content, $bitmask );

	unset( $media['has'] );
	$retval = array_filter( $media );

	return ! empty( $retval ) ? $retval : false;
}

/** Admin *********************************************************************/

/**
 * Output the correct admin URL based on BuddyPress and WordPress configuration.
 *
 * @since 1.5.0
 *
 * @see bp_get_admin_url() For description of parameters.
 *
 * @param string $path   See {@link bp_get_admin_url()}.
 * @param string $scheme See {@link bp_get_admin_url()}.
 */
function bp_admin_url( $path = '', $scheme = 'admin' ) {
	echo esc_url( bp_get_admin_url( $path, $scheme ) );
}
	/**
	 * Return the correct admin URL based on BuddyPress and WordPress configuration.
	 *
	 * @since 1.5.0
	 *
	 *
	 * @param string $path   Optional. The sub-path under /wp-admin to be
	 *                       appended to the admin URL.
	 * @param string $scheme The scheme to use. Default is 'admin', which
	 *                       obeys {@link force_ssl_admin()} and {@link is_ssl()}. 'http'
	 *                       or 'https' can be passed to force those schemes.
	 * @return string Admin url link with optional path appended.
	 */
	function bp_get_admin_url( $path = '', $scheme = 'admin' ) {

		// Links belong in network admin.
		if ( bp_core_do_network_admin() ) {
			$url = network_admin_url( $path, $scheme );

		// Links belong in site admin.
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
 * @since 1.5.0
 *
 * @return bool True if the BP admin screen should appear in the Network Admin,
 *              otherwise false.
 */
function bp_core_do_network_admin() {

	// Default.
	$retval = bp_is_network_activated();

	if ( bp_is_multiblog_mode() ) {
		$retval = false;
	}

	/**
	 * Filters whether or not BuddyPress should appear in network admin.
	 *
	 * @since 1.5.0
	 *
	 * @param bool $retval Whether or not BuddyPress should be in the network admin.
	 */
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
 * @since 1.5.0
 *
 * @return string $hook The proper hook ('network_admin_menu' or 'admin_menu').
 */
function bp_core_admin_hook() {
	$hook = bp_core_do_network_admin() ? 'network_admin_menu' : 'admin_menu';

	/**
	 * Filters the action name that BuddyPress nav setup callbacks should be hooked to.
	 *
	 * @since 1.5.0
	 *
	 * @param string $hook Action name to be attached to.
	 */
	return apply_filters( 'bp_core_admin_hook', $hook );
}

/** Multisite *****************************************************************/

/**
 * Is this the root blog?
 *
 * @since 1.5.0
 *
 * @param int $blog_id Optional. Default: the ID of the current blog.
 * @return bool $is_root_blog Returns true if this is bp_get_root_blog_id().
 */
function bp_is_root_blog( $blog_id = 0 ) {

	// Assume false.
	$is_root_blog = false;

	// Use current blog if no ID is passed.
	if ( empty( $blog_id ) || ! is_int( $blog_id ) ) {
		$blog_id = get_current_blog_id();
	}

	// Compare to root blog ID.
	if ( bp_get_root_blog_id() === $blog_id ) {
		$is_root_blog = true;
	}

	/**
	 * Filters whether or not we're on the root blog.
	 *
	 * @since 1.5.0
	 *
	 * @param bool $is_root_blog Whether or not we're on the root blog.
	 */
	return (bool) apply_filters( 'bp_is_root_blog', (bool) $is_root_blog );
}

/**
 * Get the ID of the root blog.
 *
 * The "root blog" is the blog on a WordPress network where BuddyPress content
 * appears (where member profile URLs resolve, where a given theme is loaded,
 * etc.).
 *
 * @since 1.5.0
 *
 * @return int The root site ID.
 */
function bp_get_root_blog_id() {

	/**
	 * Filters the ID for the root blog.
	 *
	 * @since 1.5.0
	 *
	 * @param int $root_blog_id ID for the root blog.
	 */
	return (int) apply_filters( 'bp_get_root_blog_id', (int) buddypress()->root_blog_id );
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
 * @since 1.5.0
 *
 * @return bool False when multiblog mode is disabled; true when enabled.
 *              Default: false.
 */
function bp_is_multiblog_mode() {

	// Setup some default values.
	$retval         = false;
	$is_multisite   = is_multisite();
	$network_active = bp_is_network_activated();
	$is_multiblog   = defined( 'BP_ENABLE_MULTIBLOG' ) && BP_ENABLE_MULTIBLOG;

	// Multisite, Network Activated, and Specifically Multiblog.
	if ( $is_multisite && $network_active && $is_multiblog ) {
		$retval = true;

	// Multisite, but not network activated.
	} elseif ( $is_multisite && ! $network_active ) {
		$retval = true;
	}

	/**
	 * Filters whether or not we're running in multiblog mode.
	 *
	 * @since 1.5.0
	 *
	 * @param bool $retval Whether or not we're running multiblog mode.
	 */
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
 * @since 1.7.0
 *
 * @return bool True if BuddyPress is network activated.
 */
function bp_is_network_activated() {

	// Default to is_multisite().
	$retval  = is_multisite();

	// Check the sitewide plugins array.
	$base    = buddypress()->basename;
	$plugins = get_site_option( 'active_sitewide_plugins' );

	// Override is_multisite() if not network activated.
	if ( ! is_array( $plugins ) || ! isset( $plugins[ $base ] ) ) {
		$retval = false;
	}

	/**
	 * Filters whether or not we're active at the network level.
	 *
	 * @since 1.7.0
	 *
	 * @param bool $retval Whether or not we're network activated.
	 */
	return (bool) apply_filters( 'bp_is_network_activated', $retval );
}

/** Global Manipulators *******************************************************/

/**
 * Set the "is_directory" global.
 *
 * @since 1.5.0
 *
 * @param bool   $is_directory Optional. Default: false.
 * @param string $component    Optional. Component name. Default: the current
 *                             component.
 */
function bp_update_is_directory( $is_directory = false, $component = '' ) {

	if ( empty( $component ) ) {
		$component = bp_current_component();
	}

	/**
	 * Filters the "is_directory" global value.
	 *
	 * @since 1.5.0
	 *
	 * @param bool   $is_directory Whether or not we're "is_directory".
	 * @param string $component    Component name. Default: the current component.
	 */
	buddypress()->is_directory = apply_filters( 'bp_update_is_directory', $is_directory, $component );
}

/**
 * Set the "is_item_admin" global.
 *
 * @since 1.5.0
 *
 * @param bool   $is_item_admin Optional. Default: false.
 * @param string $component     Optional. Component name. Default: the current
 *                              component.
 */
function bp_update_is_item_admin( $is_item_admin = false, $component = '' ) {

	if ( empty( $component ) ) {
		$component = bp_current_component();
	}

	/**
	 * Filters the "is_item_admin" global value.
	 *
	 * @since 1.5.0
	 *
	 * @param bool   $is_item_admin Whether or not we're "is_item_admin".
	 * @param string $component     Component name. Default: the current component.
	 */
	buddypress()->is_item_admin = apply_filters( 'bp_update_is_item_admin', $is_item_admin, $component );
}

/**
 * Set the "is_item_mod" global.
 *
 * @since 1.5.0
 *
 * @param bool   $is_item_mod Optional. Default: false.
 * @param string $component   Optional. Component name. Default: the current
 *                            component.
 */
function bp_update_is_item_mod( $is_item_mod = false, $component = '' ) {

	if ( empty( $component ) ) {
		$component = bp_current_component();
	}

	/**
	 * Filters the "is_item_mod" global value.
	 *
	 * @since 1.5.0
	 *
	 * @param bool   $is_item_mod Whether or not we're "is_item_mod".
	 * @param string $component   Component name. Default: the current component.
	 */
	buddypress()->is_item_mod = apply_filters( 'bp_update_is_item_mod', $is_item_mod, $component );
}

/**
 * Trigger a 404.
 *
 * @since 1.5.0
 *
 * @global WP_Query $wp_query WordPress query object.
 *
 * @param string $redirect If 'remove_canonical_direct', remove WordPress' "helpful"
 *                         redirect_canonical action. Default: 'remove_canonical_redirect'.
 */
function bp_do_404( $redirect = 'remove_canonical_direct' ) {
	global $wp_query;

	/**
	 * Fires inside the triggering of a 404.
	 *
	 * @since 1.5.0
	 *
	 * @param string $redirect Redirect type used to determine if redirect_canonical
	 *                         function should be be removed.
	 */
	do_action( 'bp_do_404', $redirect );

	$wp_query->set_404();
	status_header( 404 );
	nocache_headers();

	if ( 'remove_canonical_direct' === $redirect ) {
		remove_action( 'template_redirect', 'redirect_canonical' );
	}
}

/** Nonces ********************************************************************/

/**
 * Makes sure the user requested an action from another page on this site.
 *
 * To avoid security exploits within the theme.
 *
 * @since 1.6.0
 *
 * @param string $action    Action nonce.
 * @param string $query_arg Where to look for nonce in $_REQUEST.
 * @return bool True if the nonce is verified, otherwise false.
 */
function bp_verify_nonce_request( $action = '', $query_arg = '_wpnonce' ) {

	/* Home URL **************************************************************/

	// Parse home_url() into pieces to remove query-strings, strange characters,
	// and other funny things that plugins might to do to it.
	$parsed_home = parse_url( home_url( '/', ( is_ssl() ? 'https' : 'http' ) ) );

	// Maybe include the port, if it's included in home_url().
	if ( isset( $parsed_home['port'] ) ) {
		$parsed_host = $parsed_home['host'] . ':' . $parsed_home['port'];
	} else {
		$parsed_host = $parsed_home['host'];
	}

	// Set the home URL for use in comparisons.
	$home_url = trim( strtolower( $parsed_home['scheme'] . '://' . $parsed_host . $parsed_home['path'] ), '/' );

	/* Requested URL *********************************************************/

	// Maybe include the port, if it's included in home_url().
	if ( isset( $parsed_home['port'] ) && false === strpos( $_SERVER['HTTP_HOST'], ':' ) ) {
		$request_host = $_SERVER['HTTP_HOST'] . ':' . $_SERVER['SERVER_PORT'];
	} else {
		$request_host = $_SERVER['HTTP_HOST'];
	}

	// Build the currently requested URL.
	$scheme        = is_ssl() ? 'https://' : 'http://';
	$requested_url = strtolower( $scheme . $request_host . $_SERVER['REQUEST_URI'] );

	/* Look for match ********************************************************/

	/**
	 * Filters the requested URL being nonce-verified.
	 *
	 * Useful for configurations like reverse proxying.
	 *
	 * @since 1.9.0
	 *
	 * @param string $requested_url The requested URL.
	 */
	$matched_url = apply_filters( 'bp_verify_nonce_request_url', $requested_url );

	// Check the nonce.
	$result = isset( $_REQUEST[$query_arg] ) ? wp_verify_nonce( $_REQUEST[$query_arg], $action ) : false;

	// Nonce check failed.
	if ( empty( $result ) || empty( $action ) || ( strpos( $matched_url, $home_url ) !== 0 ) ) {
		$result = false;
	}

	/**
	 * Fires at the end of the nonce verification check.
	 *
	 * @since 1.6.0
	 *
	 * @param string $action Action nonce.
	 * @param bool   $result Boolean result of nonce verification.
	 */
	do_action( 'bp_verify_nonce_request', $action, $result );

	return $result;
}

/** Requests ******************************************************************/

/**
 * Return true|false if this is a POST request.
 *
 * @since 1.9.0
 *
 * @return bool
 */
function bp_is_post_request() {
	return (bool) ( 'POST' === strtoupper( $_SERVER['REQUEST_METHOD'] ) );
}

/**
 * Return true|false if this is a GET request.
 *
 * @since 1.9.0
 *
 * @return bool
 */
function bp_is_get_request() {
	return (bool) ( 'GET' === strtoupper( $_SERVER['REQUEST_METHOD'] ) );
}


/** Miscellaneous hooks *******************************************************/

/**
 * Load the buddypress translation file for current language.
 *
 * @since 1.0.2
 *
 * @see load_textdomain() for a description of return values.
 *
 * @return bool True on success, false on failure.
 */
function bp_core_load_buddypress_textdomain() {
	$domain = 'buddypress';

	/**
	 * Filters the locale to be loaded for the language files.
	 *
	 * @since 1.0.2
	 *
	 * @param string $value Current locale for the install.
	 */
	$mofile_custom = sprintf( '%s-%s.mo', $domain, apply_filters( 'buddypress_locale', get_locale() ) );

	/**
	 * Filters the locations to load language files from.
	 *
	 * @since 2.2.0
	 *
	 * @param array $value Array of directories to check for language files in.
	 */
	$locations = apply_filters( 'buddypress_locale_locations', array(
		trailingslashit( WP_LANG_DIR . '/' . $domain  ),
		trailingslashit( WP_LANG_DIR ),
	) );

	// Try custom locations in WP_LANG_DIR.
	foreach ( $locations as $location ) {
		if ( load_textdomain( 'buddypress', $location . $mofile_custom ) ) {
			return true;
		}
	}

	// Default to WP and glotpress.
	return load_plugin_textdomain( $domain );
}
add_action( 'bp_core_loaded', 'bp_core_load_buddypress_textdomain' );

/**
 * A JavaScript-free implementation of the search functions in BuddyPress.
 *
 * @since 1.0.1
 *
 * @param string $slug The slug to redirect to for searching.
 */
function bp_core_action_search_site( $slug = '' ) {

	if ( ! bp_is_current_component( bp_get_search_slug() ) ) {
		return;
	}

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

	/**
	 * Filters the constructed url for use with site searching.
	 *
	 * @since 1.0.0
	 *
	 * @param string $value        URL for use with site searching.
	 * @param array  $search_terms Array of search terms.
	 */
	bp_core_redirect( apply_filters( 'bp_core_search_site', home_url( $slug . $query_string . urlencode( $search_terms ) ), $search_terms ) );
}
add_action( 'bp_init', 'bp_core_action_search_site', 7 );

/**
 * Remove "prev" and "next" relational links from <head> on BuddyPress pages.
 *
 * WordPress automatically generates these relational links to the current
 * page.  However, BuddyPress doesn't adhere to these links.  In this
 * function, we remove these links when on a BuddyPress page.  This also
 * prevents additional, unnecessary queries from running.
 *
 * @since 2.1.0
 */
function bp_remove_adjacent_posts_rel_link() {
	if ( ! is_buddypress() ) {
		return;
	}

	remove_action( 'wp_head', 'adjacent_posts_rel_link_wp_head', 10 );
}
add_action( 'bp_init', 'bp_remove_adjacent_posts_rel_link' );

/**
 * Strip the span count of a menu item or of a title part.
 *
 * @since 2.2.2
 *
 * @param string $title_part Title part to clean up.
 * @return string
 */
function _bp_strip_spans_from_title( $title_part = '' ) {
	$title = $title_part;
	$span = strpos( $title, '<span' );
	if ( false !== $span ) {
		$title = substr( $title, 0, $span - 1 );
	}
	return trim( $title );
}

/**
 * Get the correct filename suffix for minified assets.
 *
 * @since 2.5.0
 *
 * @return string
 */
function bp_core_get_minified_asset_suffix() {
	$ext = ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? '' : '.min';

	// Ensure the assets can be located when running from /src/.
	if ( defined( 'BP_SOURCE_SUBDIRECTORY' ) && BP_SOURCE_SUBDIRECTORY === 'src' ) {
		$ext = str_replace( '.min', '', $ext );
	}

	return $ext;
}

/**
 * Return a list of component information.
 *
 * @since 2.6.0
 *
 * @param string $type Optional; component type to fetch. Default value is 'all', or 'optional', 'retired', 'required'.
 * @return array Requested components' data.
 */
function bp_core_get_components( $type = 'all' ) {
	$required_components = array(
		'core' => array(
			'title'       => __( 'BuddyPress Core', 'buddypress' ),
			'description' => __( 'It&#8216;s what makes <del>time travel</del> BuddyPress possible!', 'buddypress' )
		),
		'members' => array(
			'title'       => __( 'Community Members', 'buddypress' ),
			'description' => __( 'Everything in a BuddyPress community revolves around its members.', 'buddypress' )
		),
	);

	$retired_components = array(
	);

	$optional_components = array(
		'xprofile' => array(
			'title'       => __( 'Extended Profiles', 'buddypress' ),
			'description' => __( 'Customize your community with fully editable profile fields that allow your users to describe themselves.', 'buddypress' )
		),
		'settings' => array(
			'title'       => __( 'Account Settings', 'buddypress' ),
			'description' => __( 'Allow your users to modify their account and notification settings directly from within their profiles.', 'buddypress' )
		),
		'friends'  => array(
			'title'       => __( 'Friend Connections', 'buddypress' ),
			'description' => __( 'Let your users make connections so they can track the activity of others and focus on the people they care about the most.', 'buddypress' )
		),
		'messages' => array(
			'title'       => __( 'Private Messaging', 'buddypress' ),
			'description' => __( 'Allow your users to talk to each other directly and in private. Not just limited to one-on-one discussions, messages can be sent between any number of members.', 'buddypress' )
		),
		'activity' => array(
			'title'       => __( 'Activity Streams', 'buddypress' ),
			'description' => __( 'Global, personal, and group activity streams with threaded commenting, direct posting, favoriting, and @mentions, all with full RSS feed and email notification support.', 'buddypress' )
		),
		'notifications' => array(
			'title'       => __( 'Notifications', 'buddypress' ),
			'description' => __( 'Notify members of relevant activity with a toolbar bubble and/or via email, and allow them to customize their notification settings.', 'buddypress' )
		),
		'groups'   => array(
			'title'       => __( 'User Groups', 'buddypress' ),
			'description' => __( 'Groups allow your users to organize themselves into specific public, private or hidden sections with separate activity streams and member listings.', 'buddypress' )
		),
		'blogs'    => array(
			'title'       => __( 'Site Tracking', 'buddypress' ),
			'description' => __( 'Record activity for new posts and comments from your site.', 'buddypress' )
		)
	);

	// Add blogs tracking if multisite.
	if ( is_multisite() ) {
		$optional_components['blogs']['description'] = __( 'Record activity for new sites, posts, and comments across your network.', 'buddypress' );
	}

	switch ( $type ) {
		case 'required' :
			$components = $required_components;
			break;
		case 'optional' :
			$components = $optional_components;
			break;
		case 'retired' :
			$components = $retired_components;
			break;
		case 'all' :
		default :
			$components = array_merge( $required_components, $optional_components, $retired_components );
			break;
	}

	/**
	 * Filters the list of component information.
	 *
	 * @since 2.6.0
	 *
	 * @param array  $components Array of component information.
	 * @param string $type       Type of component list requested.
	 *                           Possible values are 'all', 'optional', 'retired', 'required'.
	 */
	return apply_filters( 'bp_core_get_components', $components, $type );
}

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
 * @since 1.9.0
 *
 * @return mixed A URL or an array of dummy pages.
 */
function bp_nav_menu_get_loggedin_pages() {

	// Try to catch the cached version first.
	if ( ! empty( buddypress()->wp_nav_menu_items->loggedin ) ) {
		return buddypress()->wp_nav_menu_items->loggedin;
	}

	// Pull up a list of items registered in BP's primary nav for the member.
	$bp_menu_items = buddypress()->members->nav->get_primary();

	// Some BP nav menu items will not be represented in bp_nav, because
	// they are not real BP components. We add them manually here.
	$bp_menu_items[] = array(
		'name' => __( 'Log Out', 'buddypress' ),
		'slug' => 'logout',
		'link' => wp_logout_url(),
	);

	// If there's nothing to show, we're done.
	if ( count( $bp_menu_items ) < 1 ) {
		return false;
	}

	$page_args = array();

	foreach ( $bp_menu_items as $bp_item ) {

		// Remove <span>number</span>.
		$item_name = _bp_strip_spans_from_title( $bp_item['name'] );

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
 * @since 1.9.0
 *
 * @return mixed A URL or an array of dummy pages.
 */
function bp_nav_menu_get_loggedout_pages() {

	// Try to catch the cached version first.
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
	// registration is disabled).
	$bp_directory_page_ids = bp_core_get_directory_page_ids();

	if( ! empty( $bp_directory_page_ids['register'] ) ) {
		$register_page = get_post( $bp_directory_page_ids['register'] );
		$bp_menu_items[] = array(
			'name' => $register_page->post_title,
			'slug' => 'register',
			'link' => get_permalink( $register_page->ID ),
		);
	}

	// If there's nothing to show, we're done.
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
 * @since 1.9.0
 *
 * @param string $slug The slug of the nav item: login, register, or one of the
 *                     slugs from the members navigation.
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

/** Suggestions***************************************************************/

/**
 * BuddyPress Suggestions API for types of at-mentions.
 *
 * This is used to power BuddyPress' at-mentions suggestions, but it is flexible enough to be used
 * for similar kinds of future requirements, or those implemented by third-party developers.
 *
 * @since 2.1.0
 *
 * @param array $args Array of args for the suggestions.
 * @return array|WP_Error Array of results. If there were any problems, returns a WP_Error object.
 */
function bp_core_get_suggestions( $args ) {
	$args = bp_parse_args( $args, array(), 'get_suggestions' );

	if ( ! $args['type'] ) {
		return new WP_Error( 'missing_parameter' );
	}

	// Members @name suggestions.
	if ( $args['type'] === 'members' ) {
		$class = 'BP_Members_Suggestions';

		// Members @name suggestions for users in a specific Group.
		if ( isset( $args['group_id'] ) ) {
			$class = 'BP_Groups_Member_Suggestions';
		}

	} else {

		/**
		 * Filters the default suggestions service to use.
		 *
		 * Use this hook to tell BP the name of your class
		 * if you've built a custom suggestions service.
		 *
		 * @since 2.1.0
		 *
		 * @param string $value Custom class to use. Default: none.
		 * @param array  $args  Array of arguments for sugggestions.
		 */
		$class = apply_filters( 'bp_suggestions_services', '', $args );
	}

	if ( ! $class || ! class_exists( $class ) ) {
		return new WP_Error( 'missing_parameter' );
	}


	$suggestions = new $class( $args );
	$validation  = $suggestions->validate();

	if ( is_wp_error( $validation ) ) {
		$retval = $validation;
	} else {
		$retval = $suggestions->get_suggestions();
	}

	/**
	 * Filters the available type of at-mentions.
	 *
	 * @since 2.1.0
	 *
	 * @param array|WP_Error $retval Array of results or WP_Error object.
	 * @param array          $args   Array of arguments for suggestions.
	 */
	return apply_filters( 'bp_core_get_suggestions', $retval, $args );
}

/**
 * Set data from the BP root blog's upload directory.
 *
 * Handy for multisite instances because all uploads are made on the BP root
 * blog and we need to query the BP root blog for the upload directory data.
 *
 * This function ensures that we only need to use {@link switch_to_blog()}
 * once to get what we need.
 *
 * @since 2.3.0
 *
 * @return bool|array
 */
function bp_upload_dir() {
	$bp = buddypress();

	if ( empty( $bp->upload_dir ) ) {
		$need_switch = (bool) ( is_multisite() && ! bp_is_root_blog() );

		// Maybe juggle to root blog.
		if ( true === $need_switch ) {
			switch_to_blog( bp_get_root_blog_id() );
		}

		// Get the upload directory (maybe for root blog).
		$wp_upload_dir = wp_upload_dir();

		// Maybe juggle back to current blog.
		if ( true === $need_switch ) {
			restore_current_blog();
		}

		// Bail if an error occurred.
		if ( ! empty( $wp_upload_dir['error'] ) ) {
			return false;
		}

		$bp->upload_dir = $wp_upload_dir;
	}

	return $bp->upload_dir;
}


/** Post Types *****************************************************************/

/**
 * Output the name of the email post type.
 *
 * @since 2.5.0
 */
function bp_email_post_type() {
	echo bp_get_email_post_type();
}
	/**
	 * Returns the name of the email post type.
	 *
	 * @since 2.5.0
	 *
	 * @return string The name of the email post type.
	 */
	function bp_get_email_post_type() {

		/**
		 * Filters the name of the email post type.
		 *
		 * @since 2.5.0
		 *
		 * @param string $value Email post type name.
		 */
		return apply_filters( 'bp_get_email_post_type', buddypress()->email_post_type );
	}

/**
 * Return labels used by the email post type.
 *
 * @since 2.5.0
 *
 * @return array
 */
function bp_get_email_post_type_labels() {

	/**
	 * Filters email post type labels.
	 *
	 * @since 2.5.0
	 *
	 * @param array $value Associative array (name => label).
	 */
	return apply_filters( 'bp_get_email_post_type_labels', array(
		'add_new'               => _x( 'Add New', 'email post type label', 'buddypress' ),
		'add_new_item'          => _x( 'Add a New Email', 'email post type label', 'buddypress' ),
		'all_items'             => _x( 'All Emails', 'email post type label', 'buddypress' ),
		'edit_item'             => _x( 'Edit Email', 'email post type label', 'buddypress' ),
		'filter_items_list'     => _x( 'Filter email list', 'email post type label', 'buddypress' ),
		'items_list'            => _x( 'Email list', 'email post type label', 'buddypress' ),
		'items_list_navigation' => _x( 'Email list navigation', 'email post type label', 'buddypress' ),
		'menu_name'             => _x( 'Emails', 'email post type name', 'buddypress' ),
		'name'                  => _x( 'BuddyPress Emails', 'email post type label', 'buddypress' ),
		'new_item'              => _x( 'New Email', 'email post type label', 'buddypress' ),
		'not_found'             => _x( 'No emails found', 'email post type label', 'buddypress' ),
		'not_found_in_trash'    => _x( 'No emails found in Trash', 'email post type label', 'buddypress' ),
		'search_items'          => _x( 'Search Emails', 'email post type label', 'buddypress' ),
		'singular_name'         => _x( 'Email', 'email post type singular name', 'buddypress' ),
		'uploaded_to_this_item' => _x( 'Uploaded to this email', 'email post type label', 'buddypress' ),
		'view_item'             => _x( 'View Email', 'email post type label', 'buddypress' ),
	) );
}

/**
 * Return array of features that the email post type supports.
 *
 * @since 2.5.0
 *
 * @return array
 */
function bp_get_email_post_type_supports() {

	/**
	 * Filters the features that the email post type supports.
	 *
	 * @since 2.5.0
	 *
	 * @param array $value Supported features.
	 */
	return apply_filters( 'bp_get_email_post_type_supports', array(
		'custom-fields',
		'editor',
		'excerpt',
		'revisions',
		'title',
	) );
}


/** Taxonomies *****************************************************************/

/**
 * Output the name of the email type taxonomy.
 *
 * @since 2.5.0
 */
function bp_email_tax_type() {
	echo bp_get_email_tax_type();
}
	/**
	 * Return the name of the email type taxonomy.
	 *
	 * @since 2.5.0
	 *
	 * @return string The unique email taxonomy type ID.
	 */
	function bp_get_email_tax_type() {

		/**
		 * Filters the name of the email type taxonomy.
		 *
		 * @since 2.5.0
		 *
		 * @param string $value Email type taxonomy name.
		 */
		return apply_filters( 'bp_get_email_tax_type', buddypress()->email_taxonomy_type );
	}

/**
 * Return labels used by the email type taxonomy.
 *
 * @since 2.5.0
 *
 * @return array
 */
function bp_get_email_tax_type_labels() {

	/**
	 * Filters email type taxonomy labels.
	 *
	 * @since 2.5.0
	 *
	 * @param array $value Associative array (name => label).
	 */
	return apply_filters( 'bp_get_email_tax_type_labels', array(
		'add_new_item'          => _x( 'New Email Situation', 'email type taxonomy label', 'buddypress' ),
		'all_items'             => _x( 'All Email Situations', 'email type taxonomy label', 'buddypress' ),
		'edit_item'             => _x( 'Edit Email Situations', 'email type taxonomy label', 'buddypress' ),
		'items_list'            => _x( 'Email list', 'email type taxonomy label', 'buddypress' ),
		'items_list_navigation' => _x( 'Email list navigation', 'email type taxonomy label', 'buddypress' ),
		'menu_name'             => _x( 'Situations', 'email type taxonomy label', 'buddypress' ),
		'name'                  => _x( 'Situation', 'email type taxonomy name', 'buddypress' ),
		'new_item_name'         => _x( 'New email situation name', 'email type taxonomy label', 'buddypress' ),
		'not_found'             => _x( 'No email situations found.', 'email type taxonomy label', 'buddypress' ),
		'no_terms'              => _x( 'No email situations', 'email type taxonomy label', 'buddypress' ),
		'popular_items'         => _x( 'Popular Email Situation', 'email type taxonomy label', 'buddypress' ),
		'search_items'          => _x( 'Search Emails', 'email type taxonomy label', 'buddypress' ),
		'singular_name'         => _x( 'Email', 'email type taxonomy singular name', 'buddypress' ),
		'update_item'           => _x( 'Update Email Situation', 'email type taxonomy label', 'buddypress' ),
		'view_item'             => _x( 'View Email Situation', 'email type taxonomy label', 'buddypress' ),
	) );
}


/** Email *****************************************************************/

/**
 * Get an BP_Email object for the specified email type.
 *
 * This function pre-populates the object with the subject, content, and template from the appropriate
 * email post type item. It does not replace placeholder tokens in the content with real values.
 *
 * @since 2.5.0
 *
 * @param string $email_type Unique identifier for a particular type of email.
 * @return BP_Email|WP_Error BP_Email object, or WP_Error if there was a problem.
 */
function bp_get_email( $email_type ) {
	$switched = false;

	// Switch to the root blog, where the email posts live.
	if ( ! bp_is_root_blog() ) {
		switch_to_blog( bp_get_root_blog_id() );
		$switched = true;
	}

	$args = array(
		'no_found_rows'    => true,
		'numberposts'      => 1,
		'post_status'      => 'publish',
		'post_type'        => bp_get_email_post_type(),
		'suppress_filters' => false,

		'tax_query'        => array(
			array(
				'field'    => 'slug',
				'taxonomy' => bp_get_email_tax_type(),
				'terms'    => $email_type,
			)
		),
	);

	/**
	 * Filters arguments used to find an email post type object.
	 *
	 * @since 2.5.0
	 *
	 * @param array  $args       Arguments for get_posts() used to fetch a post object.
	 * @param string $email_type Unique identifier for a particular type of email.
	 */
	$args = apply_filters( 'bp_get_email_args', $args, $email_type );
	$post = get_posts( $args );
	if ( ! $post ) {
		if ( $switched ) {
			restore_current_blog();
		}

		return new WP_Error( 'missing_email', __FUNCTION__, array( $email_type, $args ) );
	}

	/**
	 * Filters arguments used to create the BP_Email object.
	 *
	 * @since 2.5.0
	 *
	 * @param WP_Post $post       Post object containing the contents of the email.
	 * @param string  $email_type Unique identifier for a particular type of email.
	 * @param array   $args       Arguments used with get_posts() to fetch a post object.
	 * @param WP_Post $post       All posts retrieved by get_posts( $args ). May only contain $post.
	 */
	$post  = apply_filters( 'bp_get_email_post', $post[0], $email_type, $args, $post );
	$email = new BP_Email( $email_type );


	/*
	 * Set some email properties for convenience.
	 */

	// Post object (sets subject, content, template).
	$email->set_post_object( $post );

	/**
	 * Filters the BP_Email object returned by bp_get_email().
	 *
	 * @since 2.5.0
	 *
	 * @param BP_Email $email      An object representing a single email, ready for mailing.
	 * @param string   $email_type Unique identifier for a particular type of email.
	 * @param array    $args       Arguments used with get_posts() to fetch a post object.
	 * @param WP_Post  $post       All posts retrieved by get_posts( $args ). May only contain $post.
	 */
	$retval = apply_filters( 'bp_get_email', $email, $email_type, $args, $post );

	if ( $switched ) {
		restore_current_blog();
	}

	return $retval;
}

/**
 * Send email, similar to WordPress' wp_mail().
 *
 * A true return value does not automatically mean that the user received the
 * email successfully. It just only means that the method used was able to
 * process the request without any errors.
 *
 * @since 2.5.0
 *
 * @param string                   $email_type Type of email being sent.
 * @param string|array|int|WP_User $to         Either a email address, user ID, WP_User object,
 *                                             or an array containg the address and name.
 * @param array                    $args {
 *     Optional. Array of extra parameters.
 *
 *     @type array $tokens Optional. Assocative arrays of string replacements for the email.
 * }
 * @return bool|WP_Error True if the email was sent successfully. Otherwise, a WP_Error object
 *                       describing why the email failed to send. The contents will vary based
 *                       on the email delivery class you are using.
 */
function bp_send_email( $email_type, $to, $args = array() ) {
	static $is_default_wpmail = null;
	static $wp_html_emails    = null;

	// Has wp_mail() been filtered to send HTML emails?
	if ( is_null( $wp_html_emails ) ) {
		/** This filter is documented in wp-includes/pluggable.php */
		$wp_html_emails = apply_filters( 'wp_mail_content_type', 'text/plain' ) === 'text/html';
	}

	// Since wp_mail() is a pluggable function, has it been re-defined by another plugin?
	if ( is_null( $is_default_wpmail ) ) {
		try {
			$mirror            = new ReflectionFunction( 'wp_mail' );
			$is_default_wpmail = substr( $mirror->getFileName(), -strlen( 'pluggable.php' ) ) === 'pluggable.php';
		} catch ( Exception $e ) {
			$is_default_wpmail = true;
		}
	}

	$args = bp_parse_args( $args, array(
		'tokens' => array(),
	), 'send_email' );


	/*
	 * Build the email.
	 */

	$email = bp_get_email( $email_type );
	if ( is_wp_error( $email ) ) {
		return $email;
	}

	// From, subject, content are set automatically.
	$email->set_to( $to );
	$email->set_tokens( $args['tokens'] );

	/**
	 * Gives access to an email before it is sent.
	 *
	 * @since 2.8.0
	 *
	 * @param BP_Email                 $email      The email (object) about to be sent.
	 * @param string                   $email_type Type of email being sent.
	 * @param string|array|int|WP_User $to         Either a email address, user ID, WP_User object,
	 *                                             or an array containg the address and name.
     * @param array                    $args {
	 *     Optional. Array of extra parameters.
	 *
	 *     @type array $tokens Optional. Assocative arrays of string replacements for the email.
	 * }
	 */
	do_action_ref_array( 'bp_send_email', array( &$email, $email_type, $to, $args ) );

	$status = $email->validate();
	if ( is_wp_error( $status ) ) {
		return $status;
	}

	/**
	 * Filter this to skip BP's email handling and instead send everything to wp_mail().
	 *
	 * This is done if wp_mail_content_type() has been configured for HTML,
	 * or if wp_mail() has been redeclared (it's a pluggable function).
	 *
	 * @since 2.5.0
	 *
	 * @param bool $use_wp_mail Whether to fallback to the regular wp_mail() function or not.
	 */
	$must_use_wpmail = apply_filters( 'bp_email_use_wp_mail', $wp_html_emails || ! $is_default_wpmail );

	if ( $must_use_wpmail ) {
		$to = $email->get( 'to' );

		return wp_mail(
			array_shift( $to )->get_address(),
			$email->get( 'subject', 'replace-tokens' ),
			$email->get( 'content_plaintext', 'replace-tokens' )
		);
	}


	/*
	 * Send the email.
	 */

	/**
	 * Filter the email delivery class.
	 *
	 * Defaults to BP_PHPMailer, which as you can guess, implements PHPMailer.
	 *
	 * @since 2.5.0
	 *
	 * @param string       $deliver_class The email delivery class name.
	 * @param string       $email_type    Type of email being sent.
	 * @param array|string $to            Array or comma-separated list of email addresses to the email to.
	 * @param array        $args {
	 *     Optional. Array of extra parameters.
	 *
	 *     @type array $tokens Optional. Assocative arrays of string replacements for the email.
	 * }
	 */
	$delivery_class = apply_filters( 'bp_send_email_delivery_class', 'BP_PHPMailer', $email_type, $to, $args );
	if ( ! class_exists( $delivery_class ) ) {
		return new WP_Error( 'missing_class', __CLASS__, $this );
	}

	$delivery = new $delivery_class();
	$status   = $delivery->bp_email( $email );

	if ( is_wp_error( $status ) ) {

		/**
		 * Fires after BuddyPress has tried - and failed - to send an email.
		 *
		 * @since 2.5.0
		 *
		 * @param WP_Error $status A WP_Error object describing why the email failed to send. The contents
		 *                         will vary based on the email delivery class you are using.
		 * @param BP_Email $email  The email we tried to send.
		 */
		do_action( 'bp_send_email_failure', $status, $email );

	} else {

		/**
		 * Fires after BuddyPress has succesfully sent an email.
		 *
		 * @since 2.5.0
		 *
		 * @param bool     $status True if the email was sent successfully.
		 * @param BP_Email $email  The email sent.
		 */
		do_action( 'bp_send_email_success', $status, $email );
	}

	return $status;
}

/**
 * Return email appearance settings.
 *
 * @since 2.5.0
 * @since 3.0.0 Added "direction" parameter for LTR/RTL email support, and
 *              "link_text_color" to override that in the email body.
 *
 * @return array
 */
function bp_email_get_appearance_settings() {
	$default_args = array(
		'body_bg'           => '#FFFFFF',
		'body_text_color'   => '#555555',
		'body_text_size'    => 15,
		'email_bg'          => '#F7F3F0',
		'footer_bg'         => '#F7F3F0',
		'footer_text_color' => '#525252',
		'footer_text_size'  => 12,
		'header_bg'         => '#F7F3F0',
		'highlight_color'   => '#D84800',
		'header_text_color' => '#000000',
		'header_text_size'  => 30,
		'direction'         => is_rtl() ? 'right' : 'left',

		'footer_text' => sprintf(
			/* translators: email disclaimer, e.g. "© 2016 Site Name". */
			_x( '&copy; %s %s', 'email', 'buddypress' ),
			date_i18n( 'Y' ),
			bp_get_option( 'blogname' )
		),
	);

	$options = bp_parse_args(
		bp_get_option( 'bp_email_options', array() ),
		$default_args,
		'email_appearance_settings'
	);

	// Link text colour defaults to the highlight colour.
	if ( ! isset( $options['link_text_color'] ) ) {
		$options['link_text_color'] = $options['highlight_color'];
	}

	return $options;
}

/**
 * Get the paths to possible templates for the specified email object.
 *
 * @since 2.5.0
 *
 * @param WP_Post $object Post to get email template for.
 * @return array
 */
function bp_email_get_template( WP_Post $object ) {
	$single = "single-{$object->post_type}";

	/**
	 * Filter the possible template paths for the specified email object.
	 *
	 * @since 2.5.0
	 *
	 * @param array   $value  Array of possible template paths.
	 * @param WP_Post $object WP_Post object.
	 */
	return apply_filters( 'bp_email_get_template', array(
		"assets/emails/{$single}-{$object->post_name}.php",
		"{$single}-{$object->post_name}.php",
		"{$single}.php",
		"assets/emails/{$single}.php",
	), $object );
}

/**
 * Replace all tokens in the input text with appropriate values.
 *
 * Intended for use with the email system introduced in BuddyPress 2.5.0.
 *
 * @since 2.5.0
 *
 * @param string $text   Text to replace tokens in.
 * @param array  $tokens Token names and replacement values for the $text.
 * @return string
 */
function bp_core_replace_tokens_in_text( $text, $tokens ) {
	$unescaped = array();
	$escaped   = array();

	foreach ( $tokens as $token => $value ) {
		if ( ! is_string( $value ) && is_callable( $value ) ) {
			$value = call_user_func( $value );
		}

		// Tokens could be objects or arrays.
		if ( ! is_scalar( $value ) ) {
			continue;
		}

		$unescaped[ '{{{' . $token . '}}}' ] = $value;
		$escaped[ '{{' . $token . '}}' ]     = esc_html( $value );
	}

	$text = strtr( $text, $unescaped );  // Do first.
	$text = strtr( $text, $escaped );

	/**
	 * Filters text that has had tokens replaced.
	 *
	 * @since 2.5.0
	 *
	 * @param string $text
	 * @param array $tokens Token names and replacement values for the $text.
	 */
	return apply_filters( 'bp_core_replace_tokens_in_text', $text, $tokens );
}

/**
 * Get a list of emails for populating the email post type.
 *
 * @since 2.5.1
 *
 * @return array
 */
function bp_email_get_schema() {
	return array(
		'activity-comment' => array(
			/* translators: do not remove {} brackets or translate its contents. */
			'post_title'   => __( '[{{{site.name}}}] {{poster.name}} replied to one of your updates', 'buddypress' ),
			/* translators: do not remove {} brackets or translate its contents. */
			'post_content' => __( "{{poster.name}} replied to one of your updates:\n\n<blockquote>&quot;{{usermessage}}&quot;</blockquote>\n\n<a href=\"{{{thread.url}}}\">Go to the discussion</a> to reply or catch up on the conversation.", 'buddypress' ),
			/* translators: do not remove {} brackets or translate its contents. */
			'post_excerpt' => __( "{{poster.name}} replied to one of your updates:\n\n\"{{usermessage}}\"\n\nGo to the discussion to reply or catch up on the conversation: {{{thread.url}}}", 'buddypress' ),
		),
		'activity-comment-author' => array(
			/* translators: do not remove {} brackets or translate its contents. */
			'post_title'   => __( '[{{{site.name}}}] {{poster.name}} replied to one of your comments', 'buddypress' ),
			/* translators: do not remove {} brackets or translate its contents. */
			'post_content' => __( "{{poster.name}} replied to one of your comments:\n\n<blockquote>&quot;{{usermessage}}&quot;</blockquote>\n\n<a href=\"{{{thread.url}}}\">Go to the discussion</a> to reply or catch up on the conversation.", 'buddypress' ),
			/* translators: do not remove {} brackets or translate its contents. */
			'post_excerpt' => __( "{{poster.name}} replied to one of your comments:\n\n\"{{usermessage}}\"\n\nGo to the discussion to reply or catch up on the conversation: {{{thread.url}}}", 'buddypress' ),
		),
		'activity-at-message' => array(
			/* translators: do not remove {} brackets or translate its contents. */
			'post_title'   => __( '[{{{site.name}}}] {{poster.name}} mentioned you in a status update', 'buddypress' ),
			/* translators: do not remove {} brackets or translate its contents. */
			'post_content' => __( "{{poster.name}} mentioned you in a status update:\n\n<blockquote>&quot;{{usermessage}}&quot;</blockquote>\n\n<a href=\"{{{mentioned.url}}}\">Go to the discussion</a> to reply or catch up on the conversation.", 'buddypress' ),
			/* translators: do not remove {} brackets or translate its contents. */
			'post_excerpt' => __( "{{poster.name}} mentioned you in a status update:\n\n\"{{usermessage}}\"\n\nGo to the discussion to reply or catch up on the conversation: {{{mentioned.url}}}", 'buddypress' ),
		),
		'groups-at-message' => array(
			/* translators: do not remove {} brackets or translate its contents. */
			'post_title'   => __( '[{{{site.name}}}] {{poster.name}} mentioned you in an update', 'buddypress' ),
			/* translators: do not remove {} brackets or translate its contents. */
			'post_content' => __( "{{poster.name}} mentioned you in the group \"{{group.name}}\":\n\n<blockquote>&quot;{{usermessage}}&quot;</blockquote>\n\n<a href=\"{{{mentioned.url}}}\">Go to the discussion</a> to reply or catch up on the conversation.", 'buddypress' ),
			/* translators: do not remove {} brackets or translate its contents. */
			'post_excerpt' => __( "{{poster.name}} mentioned you in the group \"{{group.name}}\":\n\n\"{{usermessage}}\"\n\nGo to the discussion to reply or catch up on the conversation: {{{mentioned.url}}}", 'buddypress' ),
		),
		'core-user-registration' => array(
			/* translators: do not remove {} brackets or translate its contents. */
			'post_title'   => __( '[{{{site.name}}}] Activate your account', 'buddypress' ),
			/* translators: do not remove {} brackets or translate its contents. */
			'post_content' => __( "Thanks for registering!\n\nTo complete the activation of your account, go to the following link: <a href=\"{{{activate.url}}}\">{{{activate.url}}}</a>", 'buddypress' ),
			/* translators: do not remove {} brackets or translate its contents. */
			'post_excerpt' => __( "Thanks for registering!\n\nTo complete the activation of your account, go to the following link: {{{activate.url}}}", 'buddypress' )
		),
		'core-user-registration-with-blog' => array(
			/* translators: do not remove {} brackets or translate its contents. */
			'post_title'   => __( '[{{{site.name}}}] Activate {{{user-site.url}}}', 'buddypress' ),
			/* translators: do not remove {} brackets or translate its contents. */
			'post_content' => __( "Thanks for registering!\n\nTo complete the activation of your account and site, go to the following link: <a href=\"{{{activate-site.url}}}\">{{{activate-site.url}}}</a>.\n\nAfter you activate, you can visit your site at <a href=\"{{{user-site.url}}}\">{{{user-site.url}}}</a>.", 'buddypress' ),
			/* translators: do not remove {} brackets or translate its contents. */
			'post_excerpt' => __( "Thanks for registering!\n\nTo complete the activation of your account and site, go to the following link: {{{activate-site.url}}}\n\nAfter you activate, you can visit your site at {{{user-site.url}}}.", 'buddypress' ),
			'args'         => array(
				'multisite' => true,
			),
		),
		'friends-request' => array(
			/* translators: do not remove {} brackets or translate its contents. */
			'post_title'   => __( '[{{{site.name}}}] New friendship request from {{initiator.name}}', 'buddypress' ),
			/* translators: do not remove {} brackets or translate its contents. */
			'post_content' => __( "<a href=\"{{{initiator.url}}}\">{{initiator.name}}</a> wants to add you as a friend.\n\nTo accept this request and manage all of your pending requests, visit: <a href=\"{{{friend-requests.url}}}\">{{{friend-requests.url}}}</a>", 'buddypress' ),
			/* translators: do not remove {} brackets or translate its contents. */
			'post_excerpt' => __( "{{initiator.name}} wants to add you as a friend.\n\nTo accept this request and manage all of your pending requests, visit: {{{friend-requests.url}}}\n\nTo view {{initiator.name}}'s profile, visit: {{{initiator.url}}}", 'buddypress' ),
		),
		'friends-request-accepted' => array(
			/* translators: do not remove {} brackets or translate its contents. */
			'post_title'   => __( '[{{{site.name}}}] {{friend.name}} accepted your friendship request', 'buddypress' ),
			/* translators: do not remove {} brackets or translate its contents. */
			'post_content' => __( "<a href=\"{{{friendship.url}}}\">{{friend.name}}</a> accepted your friend request.", 'buddypress' ),
			/* translators: do not remove {} brackets or translate its contents. */
			'post_excerpt' => __( "{{friend.name}} accepted your friend request.\n\nTo learn more about them, visit their profile: {{{friendship.url}}}", 'buddypress' ),
		),
		'groups-details-updated' => array(
			/* translators: do not remove {} brackets or translate its contents. */
			'post_title'   => __( '[{{{site.name}}}] Group details updated', 'buddypress' ),
			/* translators: do not remove {} brackets or translate its contents. */
			'post_content' => __( "Group details for the group &quot;<a href=\"{{{group.url}}}\">{{group.name}}</a>&quot; were updated:\n<blockquote>{{changed_text}}</blockquote>", 'buddypress' ),
			/* translators: do not remove {} brackets or translate its contents. */
			'post_excerpt' => __( "Group details for the group \"{{group.name}}\" were updated:\n\n{{changed_text}}\n\nTo view the group, visit: {{{group.url}}}", 'buddypress' ),
		),
		'groups-invitation' => array(
			/* translators: do not remove {} brackets or translate its contents. */
			'post_title'   => __( '[{{{site.name}}}] You have an invitation to the group: "{{group.name}}"', 'buddypress' ),
			/* translators: do not remove {} brackets or translate its contents. */
			'post_content' => __( "<a href=\"{{{inviter.url}}}\">{{inviter.name}}</a> has invited you to join the group: &quot;{{group.name}}&quot;.\n<a href=\"{{{invites.url}}}\">Go here to accept your invitation</a> or <a href=\"{{{group.url}}}\">visit the group</a> to learn more.", 'buddypress' ),
			/* translators: do not remove {} brackets or translate its contents. */
			'post_excerpt' => __( "{{inviter.name}} has invited you to join the group: \"{{group.name}}\".\n\nTo accept your invitation, visit: {{{invites.url}}}\n\nTo learn more about the group, visit: {{{group.url}}}.\nTo view {{inviter.name}}'s profile, visit: {{{inviter.url}}}", 'buddypress' ),
		),
		'groups-member-promoted' => array(
			/* translators: do not remove {} brackets or translate its contents. */
			'post_title'   => __( '[{{{site.name}}}] You have been promoted in the group: "{{group.name}}"', 'buddypress' ),
			/* translators: do not remove {} brackets or translate its contents. */
			'post_content' => __( "You have been promoted to <b>{{promoted_to}}</b> in the group &quot;<a href=\"{{{group.url}}}\">{{group.name}}</a>&quot;.", 'buddypress' ),
			/* translators: do not remove {} brackets or translate its contents. */
			'post_excerpt' => __( "You have been promoted to {{promoted_to}} in the group: \"{{group.name}}\".\n\nTo visit the group, go to: {{{group.url}}}", 'buddypress' ),
		),
		'groups-membership-request' => array(
			/* translators: do not remove {} brackets or translate its contents. */
			'post_title'   => __( '[{{{site.name}}}] Membership request for group: {{group.name}}', 'buddypress' ),
			/* translators: do not remove {} brackets or translate its contents. */
			'post_content' => __( "<a href=\"{{{profile.url}}}\">{{requesting-user.name}}</a> wants to join the group &quot;{{group.name}}&quot;. As you are an administrator of this group, you must either accept or reject the membership request.\n\n<a href=\"{{{group-requests.url}}}\">Go here to manage this</a> and all other pending requests.", 'buddypress' ),
			/* translators: do not remove {} brackets or translate its contents. */
			'post_excerpt' => __( "{{requesting-user.name}} wants to join the group \"{{group.name}}\". As you are the administrator of this group, you must either accept or reject the membership request.\n\nTo manage this and all other pending requests, visit: {{{group-requests.url}}}\n\nTo view {{requesting-user.name}}'s profile, visit: {{{profile.url}}}", 'buddypress' ),
		),
		'messages-unread' => array(
			/* translators: do not remove {} brackets or translate its contents. */
			'post_title'   => __( '[{{{site.name}}}] New message from {{sender.name}}', 'buddypress' ),
			/* translators: do not remove {} brackets or translate its contents. */
			'post_content' => __( "{{sender.name}} sent you a new message: &quot;{{usersubject}}&quot;\n\n<blockquote>&quot;{{usermessage}}&quot;</blockquote>\n\n<a href=\"{{{message.url}}}\">Go to the discussion</a> to reply or catch up on the conversation.", 'buddypress' ),
			/* translators: do not remove {} brackets or translate its contents. */
			'post_excerpt' => __( "{{sender.name}} sent you a new message: \"{{usersubject}}\"\n\n\"{{usermessage}}\"\n\nGo to the discussion to reply or catch up on the conversation: {{{message.url}}}", 'buddypress' ),
		),
		'settings-verify-email-change' => array(
			/* translators: do not remove {} brackets or translate its contents. */
			'post_title'   => __( '[{{{site.name}}}] Verify your new email address', 'buddypress' ),
			/* translators: do not remove {} brackets or translate its contents. */
			'post_content' => __( "You recently changed the email address associated with your account on {{site.name}} to {{user.email}}. If this is correct, <a href=\"{{{verify.url}}}\">go here to confirm the change</a>.\n\nOtherwise, you can safely ignore and delete this email if you have changed your mind, or if you think you have received this email in error.", 'buddypress' ),
			/* translators: do not remove {} brackets or translate its contents. */
			'post_excerpt' => __( "You recently changed the email address associated with your account on {{site.name}} to {{user.email}}. If this is correct, go to the following link to confirm the change: {{{verify.url}}}\n\nOtherwise, you can safely ignore and delete this email if you have changed your mind, or if you think you have received this email in error.", 'buddypress' ),
		),
		'groups-membership-request-accepted' => array(
			/* translators: do not remove {} brackets or translate its contents. */
			'post_title'   => __( '[{{{site.name}}}] Membership request for group "{{group.name}}" accepted', 'buddypress' ),
			/* translators: do not remove {} brackets or translate its contents. */
			'post_content' => __( "Your membership request for the group &quot;<a href=\"{{{group.url}}}\">{{group.name}}</a>&quot; has been accepted.", 'buddypress' ),
			/* translators: do not remove {} brackets or translate its contents. */
			'post_excerpt' => __( "Your membership request for the group \"{{group.name}}\" has been accepted.\n\nTo view the group, visit: {{{group.url}}}", 'buddypress' ),
		),
		'groups-membership-request-rejected' => array(
			/* translators: do not remove {} brackets or translate its contents. */
			'post_title'   => __( '[{{{site.name}}}] Membership request for group "{{group.name}}" rejected', 'buddypress' ),
			/* translators: do not remove {} brackets or translate its contents. */
			'post_content' => __( "Your membership request for the group &quot;<a href=\"{{{group.url}}}\">{{group.name}}</a>&quot; has been rejected.", 'buddypress' ),
			/* translators: do not remove {} brackets or translate its contents. */
			'post_excerpt' => __( "Your membership request for the group \"{{group.name}}\" has been rejected.\n\nTo request membership again, visit: {{{group.url}}}", 'buddypress' ),
		),
	);
}

/**
 * Get a list of emails for populating email type taxonomy terms.
 *
 * @since 2.5.1
 * @since 2.7.0 $field argument added.
 *
 * @param string $field Optional; defaults to "description" for backwards compatibility. Other values: "all".
 * @return array {
 *     The array of email types and their schema.
 *
 *     @type string $description The description of the action which causes this to trigger.
 *     @type array  $unsubscribe {
 *         Replacing this with false indicates that a user cannot unsubscribe from this type.
 *
 *         @type string $meta_key The meta_key used to toggle the email setting for this notification.
 *         @type string $message  The message shown when the user has successfully unsubscribed.
 *     }
 */
function bp_email_get_type_schema( $field = 'description' ) {
	$activity_comment = array(
		'description'	=> __( 'A member has replied to an activity update that the recipient posted.', 'buddypress' ),
		'unsubscribe'	=> array(
			'meta_key'	=> 'notification_activity_new_reply',
			'message'	=> __( 'You will no longer receive emails when someone replies to an update or comment you posted.', 'buddypress' ),
			),
	);

	$activity_comment_author = array(
		'description'	=> __( 'A member has replied to a comment on an activity update that the recipient posted.', 'buddypress' ),
		'unsubscribe'	=> array(
			'meta_key'	=> 'notification_activity_new_reply',
			'message'	=> __( 'You will no longer receive emails when someone replies to an update or comment you posted.', 'buddypress' ),
			),
	);

	$activity_at_message = array(
		'description'	=> __( 'Recipient was mentioned in an activity update.', 'buddypress' ),
		'unsubscribe'	=> array(
			'meta_key'	=> 'notification_activity_new_mention',
			'message'	=> __( 'You will no longer receive emails when someone mentions you in an update.', 'buddypress' ),
		),
	);

	$groups_at_message = array(
		'description'	=> __( 'Recipient was mentioned in a group activity update.', 'buddypress' ),
		'unsubscribe'	=> array(
			'meta_key'	=> 'notification_activity_new_mention',
			'message'	=> __( 'You will no longer receive emails when someone mentions you in an update.', 'buddypress' ),
		),
	);

	$core_user_registration = array(
		'description'	=> __( 'Recipient has registered for an account.', 'buddypress' ),
		'unsubscribe'	=> false,
	);

	$core_user_registration_with_blog = array(
		'description'	=> __( 'Recipient has registered for an account and site.', 'buddypress' ),
		'unsubscribe'	=> false,
	);

	$friends_request = array(
		'description'	=> __( 'A member has sent a friend request to the recipient.', 'buddypress' ),
		'unsubscribe'	=> array(
			'meta_key'	=> 'notification_friends_friendship_request',
			'message'	=> __( 'You will no longer receive emails when someone sends you a friend request.', 'buddypress' ),
		),
	);

	$friends_request_accepted = array(
		'description'	=> __( 'Recipient has had a friend request accepted by a member.', 'buddypress' ),
		'unsubscribe'	=> array(
			'meta_key'	=> 'notification_friends_friendship_accepted',
			'message'	=> __( 'You will no longer receive emails when someone accepts your friendship request.', 'buddypress' ),
		),
	);

	$groups_details_updated = array(
		'description'	=> __( "A group's details were updated.", 'buddypress' ),
		'unsubscribe'	=> array(
			'meta_key'	=> 'notification_groups_group_updated',
			'message'	=> __( 'You will no longer receive emails when one of your groups is updated.', 'buddypress' ),
		),
	);

	$groups_invitation = array(
		'description'	=> __( 'A member has sent a group invitation to the recipient.', 'buddypress' ),
		'unsubscribe'	=> array(
			'meta_key'	=> 'notification_groups_invite',
			'message'	=> __( 'You will no longer receive emails when you are invited to join a group.', 'buddypress' ),
		),
	);

	$groups_member_promoted = array(
		'description'	=> __( "Recipient's status within a group has changed.", 'buddypress' ),
		'unsubscribe'	=> array(
			'meta_key'	=> 'notification_groups_admin_promotion',
			'message'	=> __( 'You will no longer receive emails when you have been promoted in a group.', 'buddypress' ),
		),
	);

	$groups_membership_request = array(
		'description'	=> __( 'A member has requested permission to join a group.', 'buddypress' ),
		'unsubscribe'	=> array(
			'meta_key'	=> 'notification_groups_membership_request',
			'message'	=> __( 'You will no longer receive emails when someone requests to be a member of your group.', 'buddypress' ),
		),
	);

	$messages_unread = array(
		'description'	=> __( 'Recipient has received a private message.', 'buddypress' ),
		'unsubscribe'	=> array(
			'meta_key'	=> 'notification_messages_new_message',
			'message'	=> __( 'You will no longer receive emails when someone sends you a message.', 'buddypress' ),
		),
	);

	$settings_verify_email_change = array(
		'description'	=> __( 'Recipient has changed their email address.', 'buddypress' ),
		'unsubscribe'	=> false,
	);

	$groups_membership_request_accepted = array(
		'description'	=> __( 'Recipient had requested to join a group, which was accepted.', 'buddypress' ),
		'unsubscribe'	=> array(
			'meta_key'	=> 'notification_membership_request_completed',
			'message'	=> __( 'You will no longer receive emails when your request to join a group has been accepted or denied.', 'buddypress' ),
		),
	);

	$groups_membership_request_rejected = array(
		'description'	=> __( 'Recipient had requested to join a group, which was rejected.', 'buddypress' ),
		'unsubscribe'	=> array(
			'meta_key'	=> 'notification_membership_request_completed',
			'message'	=> __( 'You will no longer receive emails when your request to join a group has been accepted or denied.', 'buddypress' ),
		),
	);

	$types = array(
		'activity-comment'                   => $activity_comment,
		'activity-comment-author'            => $activity_comment_author,
		'activity-at-message'                => $activity_at_message,
		'groups-at-message'                  => $groups_at_message,
		'core-user-registration'             => $core_user_registration,
		'core-user-registration-with-blog'   => $core_user_registration_with_blog,
		'friends-request'                    => $friends_request,
		'friends-request-accepted'           => $friends_request_accepted,
		'groups-details-updated'             => $groups_details_updated,
		'groups-invitation'                  => $groups_invitation,
		'groups-member-promoted'             => $groups_member_promoted,
		'groups-membership-request'          => $groups_membership_request,
		'messages-unread'                    => $messages_unread,
		'settings-verify-email-change'       => $settings_verify_email_change,
		'groups-membership-request-accepted' => $groups_membership_request_accepted,
		'groups-membership-request-rejected' => $groups_membership_request_rejected,
	);

	if ( $field !== 'all' ) {
		return wp_list_pluck( $types, $field );
	} else {
		return $types;
	}
}

/**
 * Handles unsubscribing user from notification emails.
 *
 * @since 2.7.0
 */
function bp_email_unsubscribe_handler() {
	$emails         = bp_email_get_unsubscribe_type_schema();
	$raw_email_type = ! empty( $_GET['nt'] ) ? $_GET['nt'] : '';
	$raw_hash       = ! empty( $_GET['nh'] ) ? $_GET['nh'] : '';
	$raw_user_id    = ! empty( $_GET['uid'] ) ? absint( $_GET['uid'] ) : 0;
	$new_hash       = hash_hmac( 'sha1', "{$raw_email_type}:{$raw_user_id}", bp_email_get_salt() );

	// Check required values.
	if ( ! $raw_user_id || ! $raw_email_type || ! $raw_hash || ! array_key_exists( $raw_email_type, $emails ) ) {
		$redirect_to = wp_login_url();
		$result_msg  = __( 'Something has gone wrong.', 'buddypress' );
		$unsub_msg   = __( 'Please log in and go to your settings to unsubscribe from notification emails.', 'buddypress' );

	// Check valid hash.
	} elseif ( ! hash_equals( $new_hash, $raw_hash ) ) {
		$redirect_to = wp_login_url();
		$result_msg  = __( 'Something has gone wrong.', 'buddypress' );
		$unsub_msg   = __( 'Please log in and go to your settings to unsubscribe from notification emails.', 'buddypress' );

	// Don't let authenticated users unsubscribe other users' email notifications.
	} elseif ( is_user_logged_in() && get_current_user_id() !== $raw_user_id ) {
		$result_msg  = __( 'Something has gone wrong.', 'buddypress' );
		$unsub_msg   = __( 'Please go to your notifications settings to unsubscribe from emails.', 'buddypress' );

		if ( bp_is_active( 'settings' ) ) {
			$redirect_to = sprintf(
				'%s%s/notifications/',
				bp_core_get_user_domain( get_current_user_id() ),
				bp_get_settings_slug()
			);
		} else {
			$redirect_to = bp_core_get_user_domain( get_current_user_id() );
		}

	} else {
		if ( bp_is_active( 'settings' ) ) {
			$redirect_to = sprintf(
				'%s%s/notifications/',
				bp_core_get_user_domain( $raw_user_id ),
				bp_get_settings_slug()
			);
		} else {
			$redirect_to = bp_core_get_user_domain( $raw_user_id );
		}

		// Unsubscribe.
		$meta_key = $emails[ $raw_email_type ]['unsubscribe']['meta_key'];
		bp_update_user_meta( $raw_user_id, $meta_key, 'no' );

		$result_msg = $emails[ $raw_email_type ]['unsubscribe']['message'];
		$unsub_msg  = __( 'You can change this or any other email notification preferences in your email settings.', 'buddypress' );
	}

	$message = sprintf(
		'%1$s <a href="%2$s">%3$s</a>',
		$result_msg,
		esc_url( $redirect_to ),
		esc_html( $unsub_msg )
	);

	bp_core_add_message( $message );
	bp_core_redirect( bp_core_get_user_domain( $raw_user_id ) );

	exit;
}

/**
 * Creates unsubscribe link for notification emails.
 *
 * @since 2.7.0
 *
 * @param string $redirect_to The URL to which the unsubscribe query string is appended.
 * @param array $args {
 *    Used to build unsubscribe query string.
 *
 *    @type string $notification_type Which notification type is being sent.
 *    @type string $user_id           The ID of the user to whom the notification is sent.
 *    @type string $redirect_to       Optional. The url to which the user will be redirected. Default is the activity directory.
 * }
 * @return string The unsubscribe link.
 */
function bp_email_get_unsubscribe_link( $args ) {
	$emails = bp_email_get_unsubscribe_type_schema();

	if ( empty( $args['notification_type'] ) || ! array_key_exists( $args['notification_type'], $emails ) ) {
		return wp_login_url();
	}

	$email_type  = $args['notification_type'];
	$redirect_to = ! empty( $args['redirect_to'] ) ? $args['redirect_to'] : site_url();
	$user_id     = (int) $args['user_id'];

	// Bail out if the activity type is not un-unsubscribable.
	if ( empty( $emails[ $email_type ]['unsubscribe'] ) ) {
		return '';
	}

	$link = add_query_arg(
		array(
			'action' => 'unsubscribe',
			'nh'     => hash_hmac( 'sha1', "{$email_type}:{$user_id}", bp_email_get_salt() ),
			'nt'     => $args['notification_type'],
			'uid'    => $user_id,
		),
		$redirect_to
	);

	/**
	 * Filters the unsubscribe link.
	 *
	 * @since 2.7.0
	 */
	return apply_filters( 'bp_email_get_link', $link, $redirect_to, $args );
}

/**
 * Get a persistent salt for email unsubscribe links.
 *
 * @since 2.7.0
 *
 * @return string|null Returns null if value isn't set, otherwise string.
 */
function bp_email_get_salt() {
	return bp_get_option( 'bp-emails-unsubscribe-salt', null );
}

/**
 * Get a list of emails for use in our unsubscribe functions.
 *
 * @since 2.8.0
 *
 * @see https://buddypress.trac.wordpress.org/ticket/7431
 *
 * @return array The array of email types and their schema.
 */
function bp_email_get_unsubscribe_type_schema() {
	$emails = bp_email_get_type_schema( 'all' );

	/**
	 * Filters the return of `bp_email_get_type_schema( 'all' )` for use with
	 * our unsubscribe functionality.
	 *
	 * @since 2.8.0
	 *
	 * @param array $emails The array of email types and their schema.
	 */
	return (array) apply_filters( 'bp_email_get_unsubscribe_type_schema', $emails );
}

/**
 * Get BuddyPress content allowed tags.
 *
 * @since  3.0.0
 *
 * @global array $allowedtags KSES allowed HTML elements.
 * @return array              BuddyPress content allowed tags.
 */
function bp_get_allowedtags() {
	global $allowedtags;

	return array_merge_recursive( $allowedtags, array(
		'a' => array(
			'aria-label'      => array(),
			'class'           => array(),
			'data-bp-tooltip' => array(),
			'id'              => array(),
			'rel'             => array(),
		),
		'img' => array(
			'src'    => array(),
			'alt'    => array(),
			'width'  => array(),
			'height' => array(),
			'class'  => array(),
			'id'     => array(),
		),
		'span'=> array(
			'class'          => array(),
			'data-livestamp' => array(),
		),
		'ul' => array(),
		'ol' => array(),
		'li' => array(),
	) );
}

/**
 * Remove script and style tags from a string.
 *
 * @since 3.0.1
 *
 * @param  string $string The string to strip tags from.
 * @return string         The stripped tags string.
 */
function bp_strip_script_and_style_tags( $string ) {
	return preg_replace( '@<(script|style)[^>]*?>.*?</\\1>@si', '', $string );
}
