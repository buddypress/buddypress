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
 */
function bp_version() {
	echo esc_html( bp_get_version() );
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
 */
function bp_db_version() {
	echo esc_html( bp_get_db_version() );
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
 */
function bp_db_version_raw() {
	echo esc_html( bp_get_db_version_raw() );
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

/**
 * Output a BuddyPress major version.
 *
 * @since 11.0.0
 *
 * @param string $version BuddyPress version.
 */
function bp_major_version( $version = '' ) {
	echo esc_html( bp_get_major_version( $version ) );
}

	/**
	 * Return a BuddyPress major version.
	 *
	 * @since 11.0.0
	 *
	 * @param string $version BuddyPress version.
	 * @return string The corresponding BuddyPress major version.
	 */
	function bp_get_major_version( $version = '' ) {
		if ( ! $version ) {
			$version = bp_get_version();
		}

		$last_wp_like_major_versions = '2.9';
		$float_version               = (float) $version;

		if ( 1 !== version_compare( $version, $last_wp_like_major_versions ) ) {
			$major_version = (string) $float_version;
		} else {
			$major_version = (int) $float_version . '.0';
		}

		return $major_version;
	}

/**
 * Output the BuddyPress version used for its first install.
 *
 * @since 11.0.0
 */
function bp_initial_version() {
	echo esc_html( bp_get_initial_version() );
}

	/**
	 * Return the BuddyPress version used for its first install.
	 *
	 * @since 11.0.0
	 *
	 * @return string The BuddyPress version used for its first install.
	 */
	function bp_get_initial_version() {
		return bp_get_option( '_bp_initial_major_version', '0' );
	}

/**
 * Check whether the current version of WP exceeds a given version.
 *
 * @since 7.0.0
 *
 * @param string $version WP version, in "PHP-standardized" format.
 * @param string $compare Optional. Comparison operator. Default '>='.
 * @return bool
 */
function bp_is_running_wp( $version, $compare = '>=' ) {
	return version_compare( $GLOBALS['wp_version'], $version, $compare );
}

/** Functions *****************************************************************/

/**
 * Get the BuddyPress URL Parser in use.
 *
 * @since 12.0.0
 *
 * @return string The name of the parser in use.
 */
function bp_core_get_query_parser() {
	/**
	 * Which parser is in use? `rewrites` or `legacy`?
	 *
	 * @todo Remove the Pretty URLs check used during BP Rewrites merge process.
	 *
	 * @since 12.0.0
	 *
	 * @param string $parser The parser to use to decide the hook to attach key actions to.
	 *                       Possible values are `rewrites` or `legacy`.
	 */
	return apply_filters( 'bp_core_get_query_parser', 'rewrites' );
}

/**
 * Get the $wpdb base prefix, run through the 'bp_core_get_table_prefix' filter.
 *
 * The filter is intended primarily for use in multinetwork installations.
 *
 * @since 1.2.6
 *
 * @global wpdb $wpdb WordPress database object.
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
 *       $r = bp_parse_args( $args, $defaults ); // ...
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
 * @param array $old_args_keys Old argument indexes, keyed to their positions.
 * @param array $func_args     The parameters passed to the originating function.
 * @return array $new_args The parsed arguments.
 */
function bp_core_parse_args_array( $old_args_keys, $func_args ) {
	$new_args = array();

	foreach ( $old_args_keys as $arg_num => $arg_key ) {
		if ( isset( $func_args[ $arg_num ] ) ) {
			$new_args[ $arg_key ] = $func_args[ $arg_num ];
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
	if ( ! empty( $filter_key ) ) {

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
	if ( is_array( $defaults ) && ! empty( $defaults ) ) {
		$r = array_merge( $defaults, $r );
	}

	// Aggressively filter the args after the parse.
	if ( ! empty( $filter_key ) ) {

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
 * responsible for limiting the result set of a template loop.
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
 * @global wpdb $wpdb WordPress database object.
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
	}

	return addcslashes( $text, '_%\\' );
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

	// Default to true.
	$use_admin_bar = true;

	// Has the WP Toolbar constant been explicitly opted into?
	if ( defined( 'BP_USE_WP_ADMIN_BAR' ) ) {
		$use_admin_bar = (bool) BP_USE_WP_ADMIN_BAR;
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
 * @since 10.0.0 Eventually switch the current site to BP root's one on multisite configs.
 *
 * @param string $status 'active' to return only pages associated with active components, 'all' to return all saved
 *                       pages. When running save routines, use 'all' to avoid removing data related to inactive
 *                       components. Default: 'active'.
 * @return array|string An array of page IDs, keyed by component names, or an
 *                      empty string if the list is not found.
 */
function bp_core_get_directory_page_ids( $status = 'active' ) {
	$page_ids = bp_get_option( 'bp-pages', array() );
	$switched = false;

	/*
	 * Make sure to switch the current site to BP root's one, if needed.
	 *
	 * @see https://buddypress.trac.wordpress.org/ticket/8592
	 */
	if ( is_multisite() ) {
		$bp_site_id = bp_get_root_blog_id();

		if ( $bp_site_id !== get_current_blog_id() ) {
			switch_to_blog( $bp_site_id );
			$switched = true;
		}
	}

	// Loop through pages.
	foreach ( $page_ids as $component_name => $page_id ) {

		// Ensure that empty indexes are unset. Should only matter in edge cases.
		if ( empty( $component_name ) || empty( $page_id ) ) {
			unset( $page_ids[ $component_name ] );
		}

		// Trashed pages should never appear in results.
		if ( 'trash' == get_post_status( $page_id ) ) {
			unset( $page_ids[ $component_name ] );
		}

		// 'register' and 'activate' do not have components, but are allowed as special cases.
		if ( in_array( $component_name, array( 'register', 'activate' ), true ) ) {
			continue;
		}

		// Remove inactive component pages.
		if ( ( 'active' === $status ) && ! bp_is_active( $component_name ) ) {
			unset( $page_ids[ $component_name ] );
		}
	}

	if ( true === $switched ) {
		restore_current_blog();
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
 * Get the component ID corresponding to a directory page ID.
 *
 * @since 12.0.0
 *
 * @param int $page_id The ID of the directory page associated with the component.
 * @return int|false The slug representing the component. False if none is found.
 */
function bp_core_get_component_from_directory_page_id( $page_id = 0 ) {
	$bp_pages = bp_core_get_directory_page_ids( 'all' );

	$component = false;
	foreach ( $bp_pages as $component_id => $p_id) {
		if ( $page_id === $p_id ) {
			$component = $component_id;
			break;
		}
	}

	return $component;
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
 * Get the BP Directory pages allowed stati.
 *
 * @since 11.0.0
 *
 * @return array The BP Directory pages allowed stati.
 */
function bp_core_get_directory_pages_stati() {
	$default_page_status = array( 'publish' );

	/**
	 * Filter here to edit the allowed BP Directory pages stati.
	 *
	 * @since 11.0.0
	 *
	 * @param array $default_page_status The default allowed BP Directory pages stati.
	 */
	$page_stati = (array) apply_filters( 'bp_core_get_directory_pages_stati', $default_page_status );

	// Validate the post stati, making sure each status is registered.
	foreach ( $page_stati as $page_status_key => $page_status ) {
		if ( ! get_post_status_object( $page_status ) ) {
			unset( $page_stati[ $page_status_key ] );
		}
	}

	if ( ! $page_stati ) {
		$page_stati = $default_page_status;
	}

	return $page_stati;
}

/**
 * Get the directory pages post type.
 *
 * @since 12.0.0
 *
 * @return string The post type to use for directory pages.
 */
function bp_core_get_directory_post_type() {
	$post_type = 'buddypress';

	/**
	 * Filter here to edit the post type to use for directory pages.
	 *
	 * @since 12.0.0
	 *
	 * @param string $post_type The post type to use for directory pages.
	 */
	return apply_filters( 'bp_core_get_directory_post_type', $post_type );
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
		$pages                 = new stdClass;
		$switched_to_root_blog = false;

		// Make sure the current blog is set to the root blog.
		if ( ! bp_is_root_blog() && ! bp_is_multiblog_mode() ) {
			switch_to_blog( bp_get_root_blog_id() );
			$switched_to_root_blog = true;
		}

		// Get pages and IDs.
		$page_ids = bp_core_get_directory_page_ids();
		if ( ! empty( $page_ids ) ) {

			// Always get page data from the root blog, except on multiblog mode, when it comes
			// from the current blog.
			$posts_table_name = bp_is_multiblog_mode() ? $wpdb->posts : $wpdb->get_blog_prefix( bp_get_root_blog_id() ) . 'posts';
			$page_ids_sql     = implode( ',', wp_parse_id_list( $page_ids ) );
			$page_stati_sql   = '\'' . implode( '\', \'', array_map( 'sanitize_key', bp_core_get_directory_pages_stati() ) ) . '\'';
			$page_names       = $wpdb->get_results( "SELECT ID, post_name, post_parent, post_title, post_status FROM {$posts_table_name} WHERE ID IN ({$page_ids_sql}) AND post_status IN ({$page_stati_sql}) " );

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

						$pages->{$component_id}->slug         = implode( '/', array_reverse( (array) $slug ) );
						$pages->{$component_id}->custom_slugs = get_post_meta( $page_name->ID, '_bp_component_slugs', true );
						$pages->{$component_id}->visibility   = $page_name->post_status;
					}

					unset( $slug );
				}
			}
		}

		if ( $switched_to_root_blog ) {
			restore_current_blog();
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
 * @since 12.0.0 Adds the `$return_pages` parameter.
 *
 * @param array   $components   Components to create pages for.
 * @param string  $existing     'delete' if you want to delete existing page mappings
 *                              and replace with new ones. Otherwise existing page mappings
 *                              are kept, and the gaps filled in with new pages. Default: 'keep'.
 * @param boolean $return_pages Whether to return the page mapping or not.
 * @return void|array
 */
function bp_core_add_page_mappings( $components, $existing = 'keep', $return_pages = false ) {

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
	if ( $return_pages ) {
		$components_title = wp_list_pluck( $components, 'title' );
		if ( ! $components_title ) {
			$components_title = $components;
		}

		// In this case the `$components` array uses Page titles as values.
		$page_titles = bp_parse_args( $page_titles, $components_title );
	}

	$pages_to_create = array();
	foreach ( array_keys( $components ) as $component_name ) {
		if ( ! isset( $pages[ $component_name ] ) && isset( $page_titles[ $component_name ] ) ) {
			$pages_to_create[ $component_name ] = $page_titles[ $component_name ];
		}
	}

	// Register and Activate are not components, but need pages when
	// registration is enabled.
	if ( bp_allow_access_to_registration_pages() ) {
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
	foreach ( $pages_to_create as $component_name => $page_title ) {
		$existing_id = bp_core_get_directory_page_id( $component_name );

		// If page already exists, use it.
		if ( ! empty( $existing_id ) ) {
			$pages[ $component_name ] = (int) $existing_id;
		} else {
			$postarr = array(
				'comment_status' => 'closed',
				'ping_status'    => 'closed',
				'post_status'    => 'publish',
				'post_title'     => $page_title,
				'post_type'      => bp_core_get_directory_post_type(),
			);

			if ( isset( $components[ $component_name ]['name'] ) ) {
				$postarr['post_name'] = $components[ $component_name ]['name'];
			}

			$pages[ $component_name ] = wp_insert_post( $postarr );
		}
	}

	// Save the page mapping.
	bp_update_option( 'bp-pages', $pages );

	// If we had to switch_to_blog, go back to the original site.
	if ( ! bp_is_root_blog() ) {
		restore_current_blog();
	}

	if ( $return_pages ) {
		return $pages;
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
 * Make sure Components directory page `post_name` are unique.
 *
 * Goal is to avoid a slug conflict between a Page and a Component's directory page `post_name`.
 *
 * @since 12.0.0
 *
 * @param string $slug          The post slug.
 * @param int    $post_ID       Post ID.
 * @param string $post_status   The post status.
 * @param string $post_type     Post type.
 * @param int    $post_parent   Post parent ID.
 * @param string $original_slug The original post slug.
 */
function bp_core_set_unique_directory_page_slug( $slug = '', $post_ID = 0, $post_status = '', $post_type = '', $post_parent = 0, $original_slug = '' ) {
	if ( ( 'buddypress' === $post_type || 'page' === $post_type ) && $slug === $original_slug && ! $post_parent ) {
		$pages = get_posts(
			array(
				'post__not_in' => array( $post_ID ),
				'post_status'  => bp_core_get_directory_pages_stati(),
				'post_type'    => array( 'buddypress', 'page' ),
				'post_parent'  => 0,     // Only get a top level page.
				'name'         => $slug, // Only get the same name page.
			)
		);

		$illegal_names = wp_list_pluck( $pages, 'post_name' );
		if ( is_multisite() && ! is_subdomain_install() ) {
			$current_site = get_current_site();
			$site         = get_site_by_path( $current_site->domain, trailingslashit( $current_site->path ) . $slug );

			if ( isset( $site->blog_id ) && 1 !== (int) $site->blog_id ) {
				$illegal_names[] = $slug;
			}
		}

		if ( in_array( $slug, $illegal_names, true ) ) {
			$suffix = 2;
			do {
				$alt_post_name   = _truncate_post_slug( $slug, 200 - ( strlen( $suffix ) + 1 ) ) . "-$suffix";
				$post_name_check = in_array( $alt_post_name, $illegal_names, true );
				$suffix++;
			} while ( $post_name_check );
			$slug = $alt_post_name;
		}
	}

	return $slug;
}
add_filter( 'wp_unique_post_slug', 'bp_core_set_unique_directory_page_slug', 10, 6 );

/**
 * Checks if a component's directory is set as the site's homepage.
 *
 * @since 12.0.0
 *
 * @param string   $component The component ID.
 * @return boolean            True if a component's directory is set as the site's homepage.
 *                            False otherwise.
 */
function bp_is_directory_homepage( $component = '' ) {
	$is_directory_homepage = false;
	$is_page_on_front      = 'page' === get_option( 'show_on_front', 'posts' );
	$page_id_on_front      = get_option( 'page_on_front', 0 );
	$directory_pages       = bp_core_get_directory_pages();

	if ( $is_page_on_front && isset( $directory_pages->{$component} ) && (int) $page_id_on_front === (int) $directory_pages->{$component}->id ) {
		$is_directory_homepage = true;
	}

	return $is_directory_homepage;
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
 * Get a list of all active component objects.
 *
 * @since 8.0.0
 *
 * @param array $args {
 *     Optional. An array of key => value arguments to match against the component objects.
 *     Default empty array.
 *
 *     @type string $name          Translatable name for the component.
 *     @type string $id            Unique ID for the component.
 *     @type string $slug          Unique slug for the component, for use in query strings and URLs.
 *     @type bool   $has_directory True if the component has a top-level directory. False otherwise.
 *     @type string $root_slug     Slug used by the component's directory page.
 * }
 * @param string $output   Optional. The type of output to return. Accepts 'ids'
 *                         or 'objects'. Default 'ids'.
 * @param string $operator Optional. The logical operation to perform. 'or' means only one
 *                         element from the array needs to match; 'and' means all elements
 *                         must match. Accepts 'or' or 'and'. Default 'and'.
 * @return array A list of component ids or objects.
 */
function bp_core_get_active_components( $args = array(), $output = 'ids', $operator = 'and' ) {
	$bp = buddypress();

	$active_components = array_keys( $bp->active_components );

	$xprofile_id = array_search( 'xprofile', $active_components, true );
	if ( false !== $xprofile_id ) {
		$active_components[ $xprofile_id ] = 'profile';
	}

	$components = array();
	foreach ( $active_components as $id ) {
		if ( isset( $bp->{$id} ) && $bp->{$id} instanceof BP_Component ) {
			$components[ $id ] = $bp->{$id};
		}
	}

	$components = wp_filter_object_list( $components, $args, $operator );

	if ( 'ids' === $output ) {
		$components = wp_list_pluck( $components, 'id' );
	}

	return $components;
}

/** URI ***********************************************************************/

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
		$location = bp_get_root_url();
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
 * Calculate the human time difference between two dates.
 *
 * Based on function created by Dunstan Orchard - http://1976design.com
 *
 * @since 8.0.0
 *
 * @param array $args {
 *     An array of arguments. All arguments are technically optional.
 *
 *     @type int|string $older_date  An integer Unix timestamp or a date string of the format 'Y-m-d h:i:s'.
 *     @type int|string $newer_date  An integer Unix timestamp or a date string of the format 'Y-m-d h:i:s'.
 *     @type int        $time_chunks The number of time chunks to get (1 or 2).
 * }
 * @return null|array|false Null if there's no time diff. An array containing 1 or 2 chunks
 *                          of human time. False if travelling into the future.
 */
function bp_core_time_diff( $args = array() ) {
	$retval = null;
	$r      = bp_parse_args(
		$args,
		array(
			'older_date'     => 0,
			'newer_date'     => bp_core_current_time( true, 'timestamp' ),
			'time_chunks'    => 2,
		)
	);

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

	foreach ( array( 'older_date', 'newer_date' ) as $date ) {
		if ( ! $r[ $date ] ) {
			$r[ $date ] = 0;
			continue;
		}

		if ( preg_match( '/^\d{4}-\d{2}-\d{2}[ ]\d{2}:\d{2}:\d{2}$/', $r[ $date ] ) ) {
			$time_chunks = explode( ':', str_replace( ' ', ':', $r[ $date ] ) );
			$date_chunks = explode( '-', str_replace( ' ', '-', $r[ $date ] ) );
			$r[ $date ]  = gmmktime(
				(int) $time_chunks[1],
				(int) $time_chunks[2],
				(int) $time_chunks[3],
				(int) $date_chunks[1],
				(int) $date_chunks[2],
				(int) $date_chunks[0]
			);
		} elseif ( ! is_int( $r[ $date ] ) ) {
			$r[ $date ] = 0;
		}
	}

	// Difference in seconds.
	$diff = $r['newer_date'] - $r['older_date'];

	/**
	 * We only want to return one or two chunks of time here, eg:
	 * - `array( 'x years', 'xx months' )`,
	 * - `array( 'x days', 'xx hours' )`.
	 * So there's only two bits of calculation below.
	 */
	if ( 0 <= $diff && (int) $r['time_chunks'] ) {
		// Step one: the first chunk.
		for ( $i = 0, $j = count( $chunks ); $i < $j; ++$i ) {
			$seconds = $chunks[$i];

			// Finding the biggest chunk (if the chunk fits, break).
			$count = floor( $diff / $seconds );
			if ( 0 != $count ) {
				break;
			}
		}

		// Add the first chunk of time diff.
		if ( isset( $chunks[ $i ] ) ) {
			$retval = array();

			switch ( $seconds ) {
				case YEAR_IN_SECONDS :
					/* translators: %s: the number of years. */
					$retval[] = sprintf( _n( '%s year', '%s years', $count, 'buddypress' ), $count );
					break;
				case 30 * DAY_IN_SECONDS :
					/* translators: %s: the number of months. */
					$retval[] = sprintf( _n( '%s month', '%s months', $count, 'buddypress' ), $count );
					break;
				case WEEK_IN_SECONDS :
					/* translators: %s: the number of weeks. */
					$retval[]= sprintf( _n( '%s week', '%s weeks', $count, 'buddypress' ), $count );
					break;
				case DAY_IN_SECONDS :
					/* translators: %s: the number of days. */
					$retval[] = sprintf( _n( '%s day', '%s days', $count, 'buddypress' ), $count );
					break;
				case HOUR_IN_SECONDS :
					/* translators: %s: the number of hours. */
					$retval[] = sprintf( _n( '%s hour', '%s hours', $count, 'buddypress' ), $count );
					break;
				case MINUTE_IN_SECONDS :
					/* translators: %s: the number of minutes. */
					$retval[] = sprintf( _n( '%s minute', '%s minutes', $count, 'buddypress' ), $count );
					break;
				default:
					/* translators: %s: the number of seconds. */
					$retval[] = sprintf( _n( '%s second', '%s seconds', $count, 'buddypress' ), $count );
			}

			/**
			 * Step two: the second chunk.
			 *
			 * A quirk in the implementation means that this condition fails in the case of minutes and seconds.
			 * We've left the quirk in place, since fractions of a minute are not a useful piece of information
			 * for our purposes.
			 */
			if ( 2 === (int) $r['time_chunks'] && $i + 2 < $j ) {
				$seconds2 = $chunks[$i + 1];
				$count2   = floor( ( $diff - ( $seconds * $count ) ) / $seconds2 );

				// Add the second chunk of time diff.
				if ( 0 !== (int) $count2 ) {

					switch ( $seconds2 ) {
						case 30 * DAY_IN_SECONDS :
							/* translators: %s: the number of months. */
							$retval[] = sprintf( _n( '%s month', '%s months', $count2, 'buddypress' ), $count2 );
							break;
						case WEEK_IN_SECONDS :
							/* translators: %s: the number of weeks. */
							$retval[] = sprintf( _n( '%s week', '%s weeks', $count2, 'buddypress' ), $count2 );
							break;
						case DAY_IN_SECONDS :
							/* translators: %s: the number of days. */
							$retval[] = sprintf( _n( '%s day', '%s days',  $count2, 'buddypress' ), $count2 );
							break;
						case HOUR_IN_SECONDS :
							/* translators: %s: the number of hours. */
							$retval[] = sprintf( _n( '%s hour', '%s hours', $count2, 'buddypress' ), $count2 );
							break;
						case MINUTE_IN_SECONDS :
							/* translators: %s: the number of minutes. */
							$retval[] = sprintf( _n( '%s minute', '%s minutes', $count2, 'buddypress' ), $count2 );
							break;
						default:
							/* translators: %s: the number of seconds. */
							$retval[] = sprintf( _n( '%s second', '%s seconds', $count2, 'buddypress' ), $count2 );
					}
				}
			}
		}
	} else {
		// Something went wrong with date calculation and we ended up with a negative date.
		$retval = false;
	}

	return $retval;
}

/**
 * Get an English-language representation of the time elapsed since a given date.
 *
 * This function will return an English representation of the time elapsed
 * since a given date.
 * eg: 2 hours, 50 minutes
 * eg: 4 days
 * eg: 4 weeks, 6 days
 *
 * Note that fractions of minutes are not represented in the return string. So
 * an interval of 3 minutes will be represented by "3 minutes ago", as will an
 * interval of 3 minutes 59 seconds.
 *
 * @since 1.0.0
 * @since 8.0.0 Move the time difference calculation into `bp_core_time_diff()`.
 *
 * @param int|string $older_date The earlier time from which you're calculating
 *                               the time elapsed. Enter either as an integer Unix timestamp,
 *                               or as a date string of the format 'Y-m-d h:i:s'.
 * @param int|bool   $newer_date Optional. Unix timestamp of date to compare older
 *                               date to. Default: false (current time).
 * @return string String representing the time since the older date, eg
 *         "2 hours, 50 minutes".
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

	$newer_date = (int) $newer_date;
	$args       = array(
		'older_date' => $older_date,
	);

	if ( $newer_date) {
		$args['newer_date'] = $newer_date;
	}

	// Calculate the time difference.
	$time_diff = bp_core_time_diff( $args );

	/**
	 * Filters the value to use if the time since is some time ago.
	 *
	 * @since 1.5.0
	 *
	 * @param string $value String representing the time since the older date.
	 */
	$ago_text = apply_filters(
		'bp_core_time_since_ago_text',
		/* translators: %s: the human time diff. */
		__( '%s ago', 'buddypress' )
	);

	/**
	 * Filters the value to use if the time since is right now.
	 *
	 * @since 1.5.0
	 *
	 * @param string $value String representing the time since the older date.
	 */
	$output = apply_filters( 'bp_core_time_since_right_now_text', __( 'right now', 'buddypress' ) );

	if ( is_array( $time_diff ) ) {
		$separator = _x( ',', 'Separator in time since', 'buddypress' ) . ' ';
		$diff_text = implode( $separator, $time_diff );
		$output    = sprintf( $ago_text, $diff_text );
	} elseif ( false === $time_diff ) {
		/**
		 * Filters the value to use if the time since is unknown.
		 *
		 * @since 1.5.0
		 *
		 * @param string $value String representing the time since the older date.
		 */
		$unknown_text = apply_filters( 'bp_core_time_since_unknown_text', __( 'sometime',  'buddypress' ) );
		$output       = sprintf( $ago_text, $unknown_text );
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
 * Get an age to display according to the birth date.
 *
 * @since 8.0.0
 *
 * @param int|string $birth_date A timestamp or a MySQL formatted date.
 * @return string The age to display.
 */
function bp_core_time_old( $birth_date ) {
	$time_diff = bp_core_time_diff( array( 'older_date' => $birth_date, 'time_chunks' => 1 ) );
	$retval    = '&mdash;';

	if ( $time_diff ) {
		$age = reset( $time_diff );

		/**
		 * Filters the value to use to display the age.
		 *
		 * @since 8.0.0
		 *
		 * @param string $value String representing the time since the older date.
		 * @param int    $age   The age.
		 */
		$age_text = apply_filters(
			'bp_core_time_old_text',
			/* translators: %d: the age . */
			__( '%s old', 'buddypress' ),
			$age
		);

		$retval = sprintf( $age_text, $age );
	}

	return $retval;
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
	echo esc_attr( bp_core_get_iso8601_date( $timestamp ) );
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

			<?php
				// Escaping is done in `bp-core/bp-core-filters.php`.
				// phpcs:ignore WordPress.Security.EscapeOutput
				echo $content;
			?>

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
 * @param int|string $last_activity_date The date of last activity.
 * @param string     $string             A sprintf()-able statement of the form 'Active %s'.
 * @return string $last_active A string of the form '3 years ago'.
 */
function bp_core_get_last_activity( $last_activity_date = '', $string = '' ) {

	// Setup a default string if none was passed.
	$string = empty( $string )
		? '%s'     // Gettext library's placeholder.
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
	 * @param string $string             A sprintf()-able statement of the form 'Active %s'.
	 */
	return apply_filters( 'bp_core_get_last_activity', $last_active, $last_activity_date, $string );
}

/** Meta **********************************************************************/

/**
 * Get the meta_key for a given piece of user metadata
 *
 * BuddyPress stores a number of pieces of user data in the WordPress central
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
 * @since 10.0.0 Add support for Activity search.
 *
 * @param string $slug The slug to redirect to for searching.
 */
function bp_core_action_search_site( $slug = '' ) {

	if ( ! bp_is_current_component( bp_get_search_slug() ) ) {
		return;
	}

	// Set default search URL.
	$url = bp_get_root_url();

	if ( empty( $_POST['search-terms'] ) ) {
		bp_core_redirect( $url );
		return;
	}

	$search_terms         = sanitize_text_field( wp_unslash( $_POST['search-terms'] ) );
	$encoded_search_terms = urlencode( $search_terms );
	$search_which         = '';

	if ( ! empty( $_POST['search-which'] ) ) {
		$search_which = sanitize_key( wp_unslash( $_POST['search-which'] ) );
	}

	if ( empty( $slug ) ) {
		switch ( $search_which ) {
			case 'posts':
				$url = home_url();

				// If posts aren't displayed on the front page, find the post page's slug.
				if ( 'page' === get_option( 'show_on_front' ) ) {
					$page = get_post( get_option( 'page_for_posts' ) );

					if ( ! is_wp_error( $page ) && ! empty( $page->post_name ) ) {
						$slug = $page->post_name;
						$url  = get_post_permalink( $page );
					}
				}

				$url = add_query_arg( 's', $encoded_search_terms, $url );
				break;

			case 'activity':
				if ( bp_is_active( 'activity' ) ) {
					$slug = bp_get_activity_root_slug();
					$url  = add_query_arg( 'activity_search', $encoded_search_terms, bp_get_activity_directory_permalink() );
				}
				break;

			case 'blogs':
				if ( bp_is_active( 'blogs' ) ) {
					$slug = bp_get_blogs_root_slug();
					$url  = add_query_arg( 'sites_search', $encoded_search_terms, bp_get_blogs_directory_url() );
				}
				break;

			case 'groups':
				if ( bp_is_active( 'groups' ) ) {
					$slug = bp_get_groups_root_slug();
					$url  = add_query_arg( 'groups_search', $encoded_search_terms, bp_get_groups_directory_url() );
				}
				break;

			case 'members':
			default:
				$slug = bp_get_members_root_slug();
				$url  = add_query_arg( 'members_search', $encoded_search_terms, bp_get_members_directory_permalink() );
				break;
		}

		if ( empty( $slug ) && 'posts' !== $search_which ) {
			bp_core_redirect( bp_get_root_url() );
			return;
		}
	}

	/**
	 * Filters the constructed url for use with site searching.
	 *
	 * @since 1.0.0
	 *
	 * @param string $url        URL for use with site searching.
	 * @param array  $search_terms Array of search terms.
	 */
	bp_core_redirect( apply_filters( 'bp_core_search_site', $url, $search_terms ) );
}

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
	$bp = buddypress();

	// Try to catch the cached version first.
	if ( ! empty( $bp->wp_nav_menu_items->loggedin ) ) {
		return $bp->wp_nav_menu_items->loggedin;
	}

	// Pull up a list of items registered in BP's primary nav for the member.
	$bp_menu_items = array();

	if ( 'rewrites' !== bp_core_get_query_parser() ) {
		$primary_items     = $bp->members->nav->get_primary();
		$user_is_displayed = bp_is_user();

		foreach( $primary_items as $primary_item ) {
			$current_user_link = $primary_item['link'];

			// When displaying a user, reset the primary item link.
			if ( $user_is_displayed ) {
				$current_user_link = bp_loggedin_user_url( bp_members_get_path_chunks( array( $primary_item['slug'] ) ) );
			}

			$bp_menu_items[] = array(
				'name' => $primary_item['name'],
				'slug' => $primary_item['slug'],
				'link' => $current_user_link,
			);
		}
	} else {
		$members_navigation = bp_get_component_navigations();

		// Remove the members component navigation when needed.
		if ( bp_is_active( 'xprofile' ) ) {
			unset( $members_navigation['members'] );
		}

		foreach ( $members_navigation as $component_id => $member_navigation ) {
			if ( ! isset( $member_navigation['main_nav'] ) ) {
				continue;
			}

			$bp_menu_items[] = array(
				'name' => $member_navigation['main_nav']['name'],
				'slug' => $member_navigation['main_nav']['slug'],
				'link' => bp_loggedin_user_url( bp_members_get_path_chunks( array( $member_navigation['main_nav']['slug'] ) ) ),
			);
		}
	}

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
			'post_type'      => 'bp_nav_menu_item',
			'post_status'    => 'publish',
			'comment_status' => 'closed',
			'guid'           => $bp_item['link']
		);
	}

	if ( empty( $bp->wp_nav_menu_items ) ) {
		buddypress()->wp_nav_menu_items = new stdClass;
	}

	$bp->wp_nav_menu_items->loggedin = $page_args;

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
	$bp = buddypress();

	// Try to catch the cached version first.
	if ( ! empty( $bp->wp_nav_menu_items->loggedout ) ) {
		return $bp->wp_nav_menu_items->loggedout;
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
			'post_type'      => 'bp_nav_menu_item',
			'post_status'    => 'publish',
			'comment_status' => 'closed',
			'guid'           => $bp_item['link']
		);
	}

	if ( empty( $bp->wp_nav_menu_items ) ) {
		$bp->wp_nav_menu_items = new stdClass;
	}

	$bp->wp_nav_menu_items->loggedout = $page_args;

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
	$args = bp_parse_args(
		$args,
		array(),
		'get_suggestions'
	);

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
		 * @param array  $args  Array of arguments for suggestions.
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
 * Register Ajax actions needing the BP URI globals to be set.
 *
 * @since 12.0.0
 *
 * @param string $ajax_action The ajax action needing the BP URI globals to be set.
 * @return boolean            True if the ajax action was registered. False otherwise.
 */
function bp_ajax_register_action( $ajax_action = '' ) {
	// Checks the ajax action is registered.
	if ( bp_ajax_action_is_registered( $ajax_action ) ) {
		return false;
	}

	buddypress()->ajax_actions[] = $ajax_action;
	return true;
}

/**
 * Is the requested ajax action registered?
 *
 * @since 12.0.0
 *
 * @param string $ajax_action The ajax action to check.
 * @return boolean            True if the ajax action is registered. False otherwise
 */
function bp_ajax_action_is_registered( $ajax_action = '' ) {
	$registered_ajax_actions = buddypress()->ajax_actions;

	return in_array( $ajax_action, $registered_ajax_actions, true );
}

/**
 * AJAX endpoint for Suggestions API lookups.
 *
 * @since 2.1.0
 * @since 4.0.0 Moved here to make sure this function is available
 *              even if the Activity component is not active.
 */
function bp_ajax_get_suggestions() {
	if ( ! bp_is_user_active() || empty( $_GET['term'] ) || empty( $_GET['type'] ) ) {
		wp_send_json_error( 'missing_parameter' );
		exit;
	}

	$args = array(
		'term' => sanitize_text_field( $_GET['term'] ),
		'type' => sanitize_text_field( $_GET['type'] ),
	);

	// Support per-Group suggestions.
	if ( ! empty( $_GET['group-id'] ) ) {
		$args['group_id'] = absint( $_GET['group-id'] );
	}

	$results = bp_core_get_suggestions( $args );

	if ( is_wp_error( $results ) ) {
		wp_send_json_error( $results->get_error_message() );
		exit;
	}

	wp_send_json_success( $results );
}
add_action( 'wp_ajax_bp_get_suggestions', 'bp_ajax_get_suggestions' );

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
	echo esc_html( bp_get_email_post_type() );
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
 * Returns the BP Taxonomy common arguments.
 *
 * @since 7.0.0
 *
 * @return array The BP Taxonomy common arguments.
 */
function bp_get_taxonomy_common_args() {
	return array(
		'public'        => false,
		'show_in_rest'  => false,
		'query_var'     => false,
		'rewrite'       => false,
		'show_in_menu'  => false,
		'show_tagcloud' => false,
		'show_ui'       => bp_is_root_blog() && bp_current_user_can( 'bp_moderate' ),
	);
}

/**
 * Returns the BP Taxonomy common labels.
 *
 * @since 7.0.0
 *
 * @return array The BP Taxonomy common labels.
 */
function bp_get_taxonomy_common_labels() {
	return array(
		'bp_type_name'           => _x( 'Plural Name', 'BP Type name label', 'buddypress' ),
		'bp_type_singular_name'  => _x( 'Singular name', 'BP Type singular name label', 'buddypress' ),
		'bp_type_has_directory'  => _x( 'Has Directory View', 'BP Type has directory checkbox label', 'buddypress' ),
		'bp_type_directory_slug' => _x( 'Custom type directory slug', 'BP Type slug label', 'buddypress' ),
	);
}

/**
 * Output the name of the email type taxonomy.
 *
 * @since 2.5.0
 */
function bp_email_tax_type() {
	echo esc_html( bp_get_email_tax_type() );
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

/**
 * Return arguments used by the email type taxonomy.
 *
 * @since 7.0.0
 *
 * @return array
 */
function bp_get_email_tax_type_args() {

	/**
	 * Filters emails type taxonomy args.
	 *
	 * @since 7.0.0
	 *
	 * @param array $value Associative array (key => arg).
	 */
	return apply_filters(
		'bp_register_email_tax_type',
		array_merge(
			array(
				'description'   => _x( 'BuddyPress email types', 'email type taxonomy description', 'buddypress' ),
				'labels'        => bp_get_email_tax_type_labels(),
				'meta_box_cb'   => 'bp_email_tax_type_metabox',
			),
			bp_get_taxonomy_common_args()
		)
	);
}

/**
 * Returns the default BuddyPress type metadata schema.
 *
 * @since 7.0.0
 *
 * @param  boolean $suppress_filters Whether to suppress filters. Default `false`.
 * @param  string  $type_taxonomy    Optional. the Type's taxonomy name.
 * @return array                     The default BuddyPress type metadata schema.
 */
function bp_get_type_metadata_schema( $suppress_filters = false, $type_taxonomy = '' ) {
	$schema = array(
		'bp_type_singular_name' => array(
			'description'       => __( 'The name of this type in singular form. ', 'buddypress' ),
			'type'              => 'string',
			'single'            => true,
			'sanitize_callback' => 'sanitize_text_field',
		),
		'bp_type_name' => array(
			'description'       => __( 'The name of this type in plural form.', 'buddypress' ),
			'type'              => 'string',
			'single'            => true,
			'sanitize_callback' => 'sanitize_text_field',
		),
		'bp_type_has_directory' => array(
			'description'       => __( 'Make a list matching this type available on the directory.', 'buddypress' ),
			'type'              => 'boolean',
			'single'            => true,
			'sanitize_callback' => 'absint',
		),
		'bp_type_directory_slug' => array(
			'label'             => __( 'Type slug', 'buddypress' ),
			'description'       => __( 'Enter if you want the type slug to be different from its ID.', 'buddypress' ),
			'type'              => 'string',
			'single'            => true,
			'sanitize_callback' => 'sanitize_title',
		),
	);

	if ( true === $suppress_filters ) {
		return $schema;
	}

	/**
	 * Filter here to add new meta to the BuddyPress type metadata.
	 *
	 * @since 7.0.0
	 *
	 * @param array  $schema        Associative array (name => arguments).
	 * @param string $type_taxonomy The Type's taxonomy name.
	 */
	return apply_filters( 'bp_get_type_metadata_schema', $schema, $type_taxonomy );
}

/**
 * Registers a meta key for BuddyPress types.
 *
 * @since 7.0.0
 *
 * @param string $type_tax The BuddyPress type taxonomy.
 * @param string $meta_key The meta key to register.
 * @param array  $args     Data used to describe the meta key when registered. See
 *                         {@see register_meta()} for a list of supported arguments.
 * @return bool True if the meta key was successfully registered, false if not.
 */
function bp_register_type_meta( $type_tax, $meta_key, array $args ) {
	$taxonomies = wp_list_pluck( bp_get_default_taxonomies(), 'component' );

	if ( ! isset( $taxonomies[ $type_tax ] ) ) {
		return false;
	}

	return register_term_meta( $type_tax, $meta_key, $args );
}

/**
 * Update a list of metadata for a given type ID and a given taxonomy.
 *
 * @since 7.0.0
 *
 * @param  integer $type_id    The database ID of the BP Type.
 * @param  string  $taxonomy   The BP Type taxonomy.
 * @param  array   $type_metas An associative array (meta_key=>meta_value).
 * @return boolean             False on failure. True otherwise.
 */
function bp_update_type_metadata( $type_id = 0, $taxonomy = '', $type_metas = array() ) {
	if ( ! $type_id || ! $taxonomy || ! is_array( $type_metas ) ) {
		return false;
	}

	foreach ( $type_metas as $meta_key => $meta_value ) {
		if ( ! registered_meta_key_exists( 'term', $meta_key, $taxonomy ) ) {
			continue;
		}

		update_term_meta( $type_id, $meta_key, $meta_value );
	}

	return true;
}

/**
 * Get types for a given BP Taxonomy.
 *
 * @since 7.0.0
 *
 * @param string $taxonomy The taxonomy to transform terms in types for.
 * @param array  $types    Existing types to merge with the types found into the database.
 *                         For instance this function is used internally to merge Group/Member
 *                         types registered using code with the ones created by the administrator
 *                         from the Group/Member types Administration screen. If not provided, only
 *                         Types created by the administrator will be returned.
 *                         Optional.
 * @return array           The types of the given taxonomy.
 */
function bp_get_taxonomy_types( $taxonomy = '', $types = array() ) {
	if ( ! $taxonomy ) {
		return $types;
	}

	$db_types = wp_cache_get( $taxonomy, 'bp_object_terms' );

	if ( ! $db_types ) {
		$terms = bp_get_terms(
			array(
				'taxonomy' => $taxonomy,
			)
		);

		if ( ! is_array( $terms ) || ! $terms ) {
			return $types;
		}

		$db_types      = array();
		$type_metadata = array_keys( get_registered_meta_keys( 'term', $taxonomy ) );

		foreach ( $terms as $term ) {
			$type_name                      = $term->name;
			$db_types[ $type_name ]         = new stdClass();
			$db_types[ $type_name ]->db_id  = $term->term_id;
			$db_types[ $type_name ]->labels = array();
			$db_types[ $type_name ]->name   = $type_name;

			if ( $type_metadata ) {
				foreach ( $type_metadata as $meta_key ) {
					$type_key = str_replace( 'bp_type_', '', $meta_key );
					if ( in_array( $type_key, array( 'name', 'singular_name' ), true ) ) {
						$db_types[ $type_name ]->labels[ $type_key ] = get_term_meta( $term->term_id, $meta_key, true );
					} else {
						$db_types[ $type_name ]->{$type_key} = get_term_meta( $term->term_id, $meta_key, true );
					}
				}

				if ( ! empty( $db_types[ $type_name ]->has_directory ) && empty( $db_types[ $type_name ]->directory_slug ) ) {
					$db_types[ $type_name ]->directory_slug = $term->slug;
				}
			}
		}

		wp_cache_set( $taxonomy, $db_types, 'bp_object_terms' );
	}

	if ( is_array( $db_types ) ) {
		foreach ( $db_types as $db_type_name => $db_type ) {
			// Override props of registered by code types if customized by the admun user.
			if ( isset( $types[ $db_type_name ] ) && isset( $types[ $db_type_name ]->code ) && $types[ $db_type_name ]->code ) {
				// Merge Labels.
				if ( $db_type->labels ) {
					foreach ( $db_type->labels as $key_label => $value_label ) {
						if ( '' !== $value_label ) {
							$types[ $db_type_name ]->labels[ $key_label ] = $value_label;
						}
					}
				}

				// Merge other properties.
				foreach ( get_object_vars( $types[ $db_type_name ] ) as $key_prop => $value_prop ) {
					if ( 'labels' === $key_prop || 'name' === $key_prop ) {
						continue;
					}

					if ( isset( $db_type->{$key_prop} ) && '' !== $db_type->{$key_prop} ) {
						$types[ $db_type_name  ]->{$key_prop} = $db_type->{$key_prop};
					}
				}

				unset( $db_types[ $db_type_name ] );
			}
		}
	}

	return array_merge( $types, (array) $db_types );
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
 *                                             or an array containing the address and name.
 * @param array                    $args {
 *     Optional. Array of extra parameters.
 *
 *     @type array $tokens Optional. Associative arrays of string replacements for the email.
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

	$args = bp_parse_args(
		$args,
		array(
			'tokens' => array(),
		),
		'send_email'
	);

	/*
	 * Build the email.
	 */
	$email = bp_get_email( $email_type );
	if ( is_wp_error( $email ) ) {
		return $email;
	}

	// From, subject, content are set automatically.
	if ( 'settings-verify-email-change' === $email_type && isset( $args['tokens']['displayname'] ) ) {
		$email->set_to( $to, $args['tokens']['displayname'] );
	// Emails sent to nonmembers will have no recipient.name populated.
	} else if ( 'bp-members-invitation' === $email_type ) {
		$email->set_to( $to, $to );
	} else {
		$email->set_to( $to );
	}

	$email->set_tokens( $args['tokens'] );

	/**
	 * Gives access to an email before it is sent.
	 *
	 * @since 2.8.0
	 *
	 * @param BP_Email                 $email      The email (object) about to be sent.
	 * @param string                   $email_type Type of email being sent.
	 * @param string|array|int|WP_User $to         Either a email address, user ID, WP_User object,
	 *                                             or an array containing the address and name.
     * @param array                    $args {
	 *     Optional. Array of extra parameters.
	 *
	 *     @type array $tokens Optional. Associative arrays of string replacements for the email.
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
	 *     @type array $tokens Optional. Associative arrays of string replacements for the email.
	 * }
	 */
	$delivery_class = apply_filters( 'bp_send_email_delivery_class', 'BP_PHPMailer', $email_type, $to, $args );
	if ( ! class_exists( $delivery_class ) ) {
		return new WP_Error( 'missing_class', 'No class found by that name', $delivery_class );
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
		 * Fires after BuddyPress has successfully sent an email.
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
	$footer_text = array(
		sprintf(
			/* translators: 1. Copyright year, 2. Site name */
			_x( '&copy; %1$s %2$s', 'copyright text for email footers', 'buddypress' ),
			date_i18n( 'Y' ),
			bp_get_option( 'blogname' )
		)
	);

	$privacy_policy_url = get_privacy_policy_url();
	if ( $privacy_policy_url ) {
		$footer_text[] = sprintf(
			'<a href="%s">%s</a>',
			esc_url( $privacy_policy_url ),
			esc_html__( 'Privacy Policy', 'buddypress' )
		);
	}

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

		'footer_text' => implode( ' &middot; ', $footer_text ),
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
 * @since 10.0.0 Added members-membership-request and
 *               members-membership-request-rejected email types.
 *
 * @return array
 */
function bp_email_get_schema() {

	/**
	 * Filters the list of `bp_email_get_schema()` allowing anyone to add/remove emails.
	 *
	 * @since 7.0.0
	 *
	 * @param array $emails The array of emails schema.
	 */
	return (array) apply_filters( 'bp_email_get_schema', array(
		'core-user-activation' => array(
			/* translators: do not remove {} brackets or translate its contents. */
			'post_title'   => __( '[{{{site.name}}}] Welcome!', 'buddypress' ),
			/* translators: do not remove {} brackets or translate its contents. */
			'post_content' => __( "Welcome to {{site.name}}!\n\nVisit your <a href=\"{{{profile.url}}}\">profile</a>, where you can tell us more about yourself, change your preferences, or make new connections, to get started.\n\nForgot your password? Don't worry, you can reset it with your email address from <a href=\"{{{lostpassword.url}}}\">this page</a> of our site", 'buddypress' ),
			/* translators: do not remove {} brackets or translate its contents. */
			'post_excerpt' => __( "Welcome to {{site.name}}!\n\nVisit your profile, where you can tell us more about yourself, change your preferences, or make new connections, to get started: {{{profile.url}}}\n\nForgot your password? Don't worry, you can reset it with your email address from this page of our site: {{{lostpassword.url}}}", 'buddypress' ),
		),
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
			'post_content' => __( "Thanks for registering!\n\nTo complete the activation of your account, go to the following link and click on the <strong>Activate</strong> button:\n<a href=\"{{{activate.url}}}\">{{{activate.url}}}</a>\n\nIf the 'Activation Key' field is empty, copy and paste the following into the field - {{key}}", 'buddypress' ),
			/* translators: do not remove {} brackets or translate its contents. */
			'post_excerpt' => __( "Thanks for registering!\n\nTo complete the activation of your account, go to the following link and click on the 'Activate' button: {{{activate.url}}}\n\nIf the 'Activation Key' field is empty, copy and paste the following into the field - {{key}}", 'buddypress' )
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
			'post_content' => __( "<a href=\"{{{inviter.url}}}\">{{inviter.name}}</a> has invited you to join the group: &quot;{{group.name}}&quot;.\n\n{{invite.message}}\n\n<a href=\"{{{invites.url}}}\">Go here to accept your invitation</a> or <a href=\"{{{group.url}}}\">visit the group</a> to learn more.", 'buddypress' ),
			/* translators: do not remove {} brackets or translate its contents. */
			'post_excerpt' => __( "{{inviter.name}} has invited you to join the group: \"{{group.name}}\".\n\n{{invite.message}}\n\nTo accept your invitation, visit: {{{invites.url}}}\n\nTo learn more about the group, visit: {{{group.url}}}.\nTo view {{inviter.name}}'s profile, visit: {{{inviter.url}}}", 'buddypress' ),
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
			'post_content' => __( "<a href=\"{{{profile.url}}}\">{{requesting-user.name}}</a> wants to join the group &quot;{{group.name}}&quot;.\n {{request.message}}\n As you are an administrator of this group, you must either accept or reject the membership request.\n\n<a href=\"{{{group-requests.url}}}\">Go here to manage this</a> and all other pending requests.", 'buddypress' ),
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
		'groups-membership-request-accepted-by-admin' => array(
			/* translators: do not remove {} brackets or translate its contents. */
			'post_title'   => __( '[{{{site.name}}}] Membership request for group "{{group.name}}" accepted', 'buddypress' ),
			/* translators: do not remove {} brackets or translate its contents. */
			'post_content' => __( "An administrator accepted an invitation to join &quot;<a href=\"{{{group.url}}}\">{{group.name}}</a>&quot; on your behalf.\n\nIf you disagree with this, you can leave the group at anytime visiting your <a href=\"{{{leave-group.url}}}\">groups memberships page</a>.", 'buddypress' ),
			/* translators: do not remove {} brackets or translate its contents. */
			'post_excerpt' => __( "An administrator accepted an invitation to join \"{{group.name}}\" on your behalf.\n\nIf you disagree with this, you can leave the group at anytime visiting your groups memberships page: {{{leave-group.url}}}", 'buddypress' ),
		),
		'groups-membership-request-rejected-by-admin' => array(
			/* translators: do not remove {} brackets or translate its contents. */
			'post_title'   => __( '[{{{site.name}}}] Membership request for group "{{group.name}}" rejected', 'buddypress' ),
			/* translators: do not remove {} brackets or translate its contents. */
			'post_content' => __( "An administrator rejected an invitation to join &quot;<a href=\"{{{group.url}}}\">{{group.name}}</a>&quot; on your behalf.\n\nIf you disagree with this, please contact the site administrator.", 'buddypress' ),
			/* translators: do not remove {} brackets or translate its contents. */
			'post_excerpt' => __( "An administrator rejected an invitation to join \"{{group.name}}\" on your behalf.\n\nIf you disagree with this, please contact the site administrator.", 'buddypress' ),
		),
		'bp-members-invitation' => array(
			/* translators: do not remove {} brackets or translate its contents. */
			'post_title'   => __( '{{inviter.name}} has invited you to join {{site.name}}', 'buddypress' ),
			/* translators: do not remove {} brackets or translate its contents. */
			'post_content' => __( "<a href=\"{{{inviter.url}}}\">{{inviter.name}}</a> has invited you to join the site: &quot;{{site.name}}&quot;.\n\n{{usermessage}}\n\n<a href=\"{{{invite.accept_url}}}\">Accept your invitation</a> or <a href=\"{{{site.url}}}\">visit the site</a> to learn more.", 'buddypress' ),
			/* translators: do not remove {} brackets or translate its contents. */
			'post_excerpt' => __( "{{inviter.name}} has invited you to join the site \"{{site.name}}\".\n\n{{usermessage}}\n\nTo accept your invitation, visit: {{{invite.accept_url}}}\n\nTo learn more about the site, visit: {{{site.url}}}.\nTo view {{inviter.name}}'s profile, visit: {{{inviter.url}}}", 'buddypress' ),
		),
		'members-membership-request' => array(
			/* translators: do not remove {} brackets or translate its contents. */
			'post_title'   => __( '{{requesting-user.user_login}} would like to join {{site.name}}', 'buddypress' ),
			/* translators: do not remove {} brackets or translate its contents. */
			'post_content' => __( "{{requesting-user.user_login}} would like to join the site: &quot;{{site.name}}&quot;.\n\n<a href=\"{{{manage.url}}}\">Manage the request</a>.", 'buddypress' ),
			/* translators: do not remove {} brackets or translate its contents. */
			'post_excerpt' => __( "{{requesting-user.user_login}} would like to join the site \"{{site.name}}\".\n\nTo manage the request, visit: {{{manage.url}}}.", 'buddypress' ),
		),
		'members-membership-request-rejected' => array(
			/* translators: do not remove {} brackets or translate its contents. */
			'post_title'   => __( 'Your request to join {{site.name}} has been declined', 'buddypress' ),
			/* translators: do not remove {} brackets or translate its contents. */
			'post_content' => __( "Sorry, your request to join the site &quot;{{site.name}}&quot; has been declined.", 'buddypress' ),
			/* translators: do not remove {} brackets or translate its contents. */
			'post_excerpt' => __( "Sorry, your request to join the site \"{{site.name}}\" has been declined.", 'buddypress' ),
		),
	) );
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
		'description'	   => __( 'A member has replied to an activity update that the recipient posted.', 'buddypress' ),
		'named_salutation' => true,
		'unsubscribe'	   => array(
			'meta_key' => 'notification_activity_new_reply',
			'message'  => __( 'You will no longer receive emails when someone replies to an update or comment you posted.', 'buddypress' ),
		),
	);

	$activity_comment_author = array(
		'description'	   => __( 'A member has replied to a comment on an activity update that the recipient posted.', 'buddypress' ),
		'named_salutation' => true,
		'unsubscribe'	   => array(
			'meta_key' => 'notification_activity_new_reply',
			'message'  => __( 'You will no longer receive emails when someone replies to an update or comment you posted.', 'buddypress' ),
		),
	);

	$activity_at_message = array(
		'description'	   => __( 'Recipient was mentioned in an activity update.', 'buddypress' ),
		'named_salutation' => true,
		'unsubscribe'	   => array(
			'meta_key' => 'notification_activity_new_mention',
			'message'  => __( 'You will no longer receive emails when someone mentions you in an update.', 'buddypress' ),
		),
	);

	$groups_at_message = array(
		'description'	   => __( 'Recipient was mentioned in a group activity update.', 'buddypress' ),
		'named_salutation' => true,
		'unsubscribe'	   => array(
			'meta_key' => 'notification_activity_new_mention',
			'message'  => __( 'You will no longer receive emails when someone mentions you in an update.', 'buddypress' ),
		),
	);

	$core_user_registration = array(
		'description'	   => __( 'Recipient has registered for an account.', 'buddypress' ),
		'named_salutation' => true,
		'unsubscribe'	   => false,
	);

	$core_user_registration_with_blog = array(
		'description'	   => __( 'Recipient has registered for an account and site.', 'buddypress' ),
		'named_salutation' => true,
		'unsubscribe'	   => false,
	);

	$friends_request = array(
		'description'	   => __( 'A member has sent a friend request to the recipient.', 'buddypress' ),
		'named_salutation' => true,
		'unsubscribe'	   => array(
			'meta_key' => 'notification_friends_friendship_request',
			'message'  => __( 'You will no longer receive emails when someone sends you a friend request.', 'buddypress' ),
		),
	);

	$friends_request_accepted = array(
		'description'	   => __( 'Recipient has had a friend request accepted by a member.', 'buddypress' ),
		'named_salutation' => true,
		'unsubscribe'	   => array(
			'meta_key' => 'notification_friends_friendship_accepted',
			'message'  => __( 'You will no longer receive emails when someone accepts your friendship request.', 'buddypress' ),
		),
	);

	$groups_details_updated = array(
		'description'	   => __( "A group's details were updated.", 'buddypress' ),
		'named_salutation' => true,
		'unsubscribe'	   => array(
			'meta_key' => 'notification_groups_group_updated',
			'message'  => __( 'You will no longer receive emails when one of your groups is updated.', 'buddypress' ),
		),
	);

	$groups_invitation = array(
		'description'	   => __( 'A member has sent a group invitation to the recipient.', 'buddypress' ),
		'named_salutation' => true,
		'unsubscribe'	   => array(
			'meta_key' => 'notification_groups_invite',
			'message'  => __( 'You will no longer receive emails when you are invited to join a group.', 'buddypress' ),
		),
	);

	$groups_member_promoted = array(
		'description'	   => __( "Recipient's status within a group has changed.", 'buddypress' ),
		'named_salutation' => true,
		'unsubscribe' => array(
			'meta_key' => 'notification_groups_admin_promotion',
			'message'  => __( 'You will no longer receive emails when you have been promoted in a group.', 'buddypress' ),
		),
	);

	$groups_membership_request = array(
		'description'	   => __( 'A member has requested permission to join a group.', 'buddypress' ),
		'named_salutation' => true,
		'unsubscribe'	   => array(
			'meta_key' => 'notification_groups_membership_request',
			'message'  => __( 'You will no longer receive emails when someone requests to be a member of your group.', 'buddypress' ),
		),
	);

	$messages_unread = array(
		'description'	   => __( 'Recipient has received a private message.', 'buddypress' ),
		'named_salutation' => true,
		'unsubscribe'	   => array(
			'meta_key' => 'notification_messages_new_message',
			'message'  => __( 'You will no longer receive emails when someone sends you a message.', 'buddypress' ),
		),
	);

	$settings_verify_email_change = array(
		'description'	   => __( 'Recipient has changed their email address.', 'buddypress' ),
		'named_salutation' => true,
		'unsubscribe'	   => false,
	);

	$groups_membership_request_accepted = array(
		'description'	   => __( 'Recipient had requested to join a group, which was accepted.', 'buddypress' ),
		'named_salutation' => true,
		'unsubscribe'	   => array(
			'meta_key' => 'notification_membership_request_completed',
			'message'  => __( 'You will no longer receive emails when your request to join a group has been accepted or denied.', 'buddypress' ),
		),
	);

	$groups_membership_request_rejected = array(
		'description'	   => __( 'Recipient had requested to join a group, which was rejected.', 'buddypress' ),
		'named_salutation' => true,
		'unsubscribe'	   => array(
			'meta_key' => 'notification_membership_request_completed',
			'message'  => __( 'You will no longer receive emails when your request to join a group has been accepted or denied.', 'buddypress' ),
		),
	);

	$groups_membership_request_accepted_by_admin = array(
		'description'	   => __( 'Recipient had requested to join a group, which was accepted by admin.', 'buddypress' ),
		'named_salutation' => true,
		'unsubscribe'	   => false,
	);

	$groups_membership_request_rejected_by_admin = array(
		'description'	   => __( 'Recipient had requested to join a group, which was rejected by admin.', 'buddypress' ),
		'named_salutation' => true,
		'unsubscribe'	   => false,
	);

	$core_user_activation = array(
		'description'	   => __( 'Recipient has successfully activated an account.', 'buddypress' ),
		'named_salutation' => true,
		'unsubscribe'	   => false,
	);

	$members_invitation = array(
		'description'	   => __( 'A site member has sent a site invitation to the recipient.', 'buddypress' ),
		'named_salutation' => false,
		'unsubscribe'	   => array(
			'meta_key' => 'notification_bp_members_invite',
			'message'  => __( 'You will no longer receive emails when you are invited to join this site.', 'buddypress' ),
		),
	);

	$members_membership_request = array(
		'description'	   => __( 'Someone has requested membership on this site.', 'buddypress' ),
		'named_salutation' => true,
		'unsubscribe'	   => array(
			'meta_key' => 'notification_members_membership_request',
			'message'  => __( 'You will no longer receive emails when people submit requests to join this site.', 'buddypress' ),
		),
	);

	$members_membership_request_rejected = array(
		'description'	   => __( 'A site membership request has been rejected.', 'buddypress' ),
		'named_salutation' => false,
		'unsubscribe'	   => false,
	);

	$types = array(
		'activity-comment'                            => $activity_comment,
		'activity-comment-author'                     => $activity_comment_author,
		'activity-at-message'                         => $activity_at_message,
		'groups-at-message'                           => $groups_at_message,
		'core-user-registration'                      => $core_user_registration,
		'core-user-registration-with-blog'            => $core_user_registration_with_blog,
		'friends-request'                             => $friends_request,
		'friends-request-accepted'                    => $friends_request_accepted,
		'groups-details-updated'                      => $groups_details_updated,
		'groups-invitation'                           => $groups_invitation,
		'groups-member-promoted'                      => $groups_member_promoted,
		'groups-membership-request'                   => $groups_membership_request,
		'messages-unread'                             => $messages_unread,
		'settings-verify-email-change'                => $settings_verify_email_change,
		'groups-membership-request-accepted'          => $groups_membership_request_accepted,
		'groups-membership-request-rejected'          => $groups_membership_request_rejected,
		'core-user-activation'                        => $core_user_activation,
		'bp-members-invitation'                       => $members_invitation,
		'members-membership-request'                  => $members_membership_request,
		'members-membership-request-rejected'         => $members_membership_request_rejected,
		'groups-membership-request-accepted-by-admin' => $groups_membership_request_accepted_by_admin,
		'groups-membership-request-rejected-by-admin' => $groups_membership_request_rejected_by_admin,
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
	$raw_user_email = ! empty( $_GET['uem'] ) ? $_GET['uem'] : '';
	$raw_member_id  = ! empty( $_GET['mid'] ) ? absint( $_GET['mid'] ) : 0;
	$redirect_to    = '';

	$new_hash = '';
	if ( ! empty( $raw_user_id ) ) {
		$new_hash = hash_hmac( 'sha1', "{$raw_email_type}:{$raw_user_id}", bp_email_get_salt() );
	} else if ( ! empty( $raw_user_email ) ) {
		$new_hash = hash_hmac( 'sha1', "{$raw_email_type}:{$raw_user_email}", bp_email_get_salt() );
	}

	// Check required values.
	if ( ( ! $raw_user_id && ! $raw_user_email ) || ! $raw_email_type || ! $raw_hash || ! array_key_exists( $raw_email_type, $emails ) ) {
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
			$redirect_to = bp_members_get_user_url(
				get_current_user_id(),
				bp_members_get_path_chunks( array( bp_get_settings_slug(), 'notifications' ) )
			);
		} else {
			$redirect_to = bp_members_get_user_url( get_current_user_id() );
		}

	// This is an unsubscribe request from a nonmember.
	} else if ( $raw_user_email ) {
		// Unsubscribe.
		if ( bp_user_has_opted_out() ) {
			$result_msg = $emails[ $raw_email_type ]['unsubscribe']['message'];
			$unsub_msg  = __( 'You have already unsubscribed from all communication from this site.', 'buddypress' );
		} else {
			$optout_args = array(
				'email_address' => $raw_user_email,
				'user_id'       => $raw_member_id,
				'email_type'    => $raw_email_type,
				'date_modified' => bp_core_current_time(),
			);
			bp_add_optout( $optout_args );
			$result_msg = $emails[ $raw_email_type ]['unsubscribe']['message'];
			$unsub_msg  = __( 'You have been unsubscribed.', 'buddypress' );
		}

	// This is an unsubscribe request from a current member.
	} else {
		if ( bp_is_active( 'settings' ) ) {
			$redirect_to = bp_members_get_user_url(
				$raw_user_id,
				bp_members_get_path_chunks( array( bp_get_settings_slug(), 'notifications' ) )
			);
		} else {
			$redirect_to = bp_members_get_user_url( $raw_user_id );
		}

		// Unsubscribe.
		$meta_key = $emails[ $raw_email_type ]['unsubscribe']['meta_key'];
		bp_update_user_meta( $raw_user_id, $meta_key, 'no' );

		$result_msg = $emails[ $raw_email_type ]['unsubscribe']['message'];
		$unsub_msg  = __( 'You can change this or any other email notification preferences in your email settings.', 'buddypress' );
	}

	if ( $raw_user_id && $redirect_to ) {
		$message = sprintf(
			'%1$s <a href="%2$s">%3$s</a>',
			$result_msg,
			esc_url( $redirect_to ),
			esc_html( $unsub_msg )
		);

		// Template notices are only displayed on BP pages.
		bp_core_add_message( $message );
		bp_core_redirect( bp_members_get_user_url( $raw_user_id ) );

		exit;
	} else {
		wp_die(
			sprintf( '%1$s %2$s', esc_html( $unsub_msg ), esc_html( $result_msg ) ),
			esc_html( $unsub_msg ),
			array(
				'link_url'  => esc_url( home_url() ),
				'link_text' => esc_html__( 'Go to website\'s home page.', 'buddypress' ),
			)
		);
	}
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
 *    @type string $email             Optional. The email address of the user to whom the notification is sent.
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

	$link = '';
	// Case where the recipient is a member of the site.
	if ( ! empty( $user_id ) ) {
		$link = add_query_arg(
			array(
				'action' => 'unsubscribe',
				'nh'     => hash_hmac( 'sha1', "{$email_type}:{$user_id}", bp_email_get_salt() ),
				'nt'     => $args['notification_type'],
				'uid'    => $user_id,
			),
			$redirect_to
		);

	// Case where the recipient is not a member of the site.
	} else if ( ! empty( $args['email_address'] ) ) {
		$email_address = $args['email_address'];
		$member_id     = (int) $args['member_id'];
		$link          = add_query_arg(
			array(
				'action' => 'unsubscribe',
				'nh'     => hash_hmac( 'sha1', "{$email_type}:{$email_address}", bp_email_get_salt() ),
				'nt'     => $args['notification_type'],
				'mid'    => $member_id,
				'uem'    => $email_address,
			),
			$redirect_to
		);
	}

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
 * Gets the BP Email type of a BP Email ID or object.
 *
 * @since 8.0.0
 *
 * @param int|WP_Post $email Optional. BP Email ID or BP Email object. Defaults to global $post.
 * @return string The type of the BP Email object.
 */
function bp_email_get_type( $email = null ) {
	$email = get_post( $email );

	if ( ! $email ) {
		return '';
	}

	$types = bp_get_object_terms( $email->ID, bp_get_email_tax_type(), array( 'fields' => 'slugs' ) );
	$type  = reset( $types );

	return $type;
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

/**
 * Checks whether the current installation is "large".
 *
 * By default, an installation counts as "large" if there are 10000 users or more.
 * Filter 'bp_is_large_install' to adjust.
 *
 * @since 4.1.0
 *
 * @return bool
 */
function bp_is_large_install() {
	// Use the Multisite function if available.
	if ( function_exists( 'wp_is_large_network' ) ) {
		$is_large = wp_is_large_network( 'users' );
	} else {
		$is_large = bp_core_get_total_member_count() > 10000;
	}

	/**
	 * Filters whether the current installation is "large".
	 *
	 * @since 4.1.0
	 *
	 * @param bool $is_large True if the network is "large".
	 */
	return (bool) apply_filters( 'bp_is_large_install', $is_large );
}

/**
 * Add a new BP_Optout.
 *
 * @since 8.0.0
 *
 * @param array $args {
 *     An array of arguments describing the new opt-out.
 *     @type string $email_address Email address of user who has opted out.
 *     @type int    $user_id       Optional. ID of user whose communication
 *                                 prompted the user to opt-out.
 *     @type string $email_type    Optional. Name of the email type that
 *                                 prompted the user to opt-out.
 *     @type string $date_modified Optional. Specify a time, else now will be used.
 * }
 * @return false|int False on failure, ID of new (or existing) opt-out if successful.
 */
function bp_add_optout( $args = array() ) {
	$optout = new BP_Optout();
	$r      = bp_parse_args(
		$args, array(
			'email_address' => '',
			'user_id'       => 0,
			'email_type'    => '',
			'date_modified' => bp_core_current_time(),
		),
		'add_optout'
	);

	// Opt-outs must have an email address.
	if ( empty( $r['email_address'] ) ) {
		return false;
	}

	// Avoid creating duplicate opt-outs.
	$optout_id = $optout->optout_exists(
		array(
			'email_address' => $r['email_address'],
			'user_id'       => $r['user_id'],
			'email_type'    => $r['email_type'],
		)
	);

	if ( ! $optout_id ) {
		// Set up the new opt-out.
		$optout->email_address = $r['email_address'];
		$optout->user_id       = $r['user_id'];
		$optout->email_type    = $r['email_type'];
		$optout->date_modified = $r['date_modified'];

		$optout_id = $optout->save();
	}

	return $optout_id;
}

/**
 * Find matching BP_Optouts.
 *
 * @since 8.0.0
 *
 * @see BP_Optout::get() for a description of parameters and return values.
 *
 * @param array $args See {@link BP_Optout::get()}.
 * @return array See {@link BP_Optout::get()}.
 */
function bp_get_optouts( $args = array() ) {
	$optout_class = new BP_Optout();
	return $optout_class::get( $args );
}

/**
 * Check an email address to see if that individual has opted out.
 *
 * @since 8.0.0
 *
 * @param string $email_address Email address to check.
 * @return bool True if the user has opted out, false otherwise.
 */
function bp_user_has_opted_out( $email_address = '' ) {
	$optout_class = new BP_Optout();
	$optout_id    = $optout_class->optout_exists(
		array(
			'email_address' => $email_address,
		)
	);
	return (bool) $optout_id;
}

/**
 * Delete a BP_Optout by ID.
 *
 * @since 8.0.0
 *
 * @param int $id ID of the optout to delete.
 * @return bool True on success, false on failure.
 */
function bp_delete_optout_by_id( $id = 0 ) {
	$optout_class = new BP_Optout();
	return $optout_class::delete_by_id( $id );
}

/**
 * Get the list of versions needing their deprecated functions to be loaded.
 *
 * @since 11.0.0
 *
 * @return array The list of versions needing their deprecated functions to be loaded.
 */
function bp_get_deprecated_functions_versions() {
	$ignore_deprecated = null;

	// Do ignore deprecated => ignore all deprecated code.
	if ( defined( 'BP_IGNORE_DEPRECATED' ) && BP_IGNORE_DEPRECATED ) {
		$ignore_deprecated = (bool) BP_IGNORE_DEPRECATED;
	}

	// Do not ignore deprecated => load all deprecated code.
	if ( defined( 'BP_LOAD_DEPRECATED' ) && BP_LOAD_DEPRECATED ) {
		$ignore_deprecated = ! (bool) BP_LOAD_DEPRECATED;
	}

	/*
	 * Respect the site owner's choice to ignore deprecated functions.
	 * Return an empty array to inform no deprecated version files should be loaded.
	 */
	if ( true === $ignore_deprecated ) {
		return array();
	}

	// List of versions containing deprecated functions.
	$deprecated_functions_versions = array(
		1.2,
		1.5,
		1.6,
		1.7,
		1.9,
		2.0,
		2.1,
		2.2,
		2.3,
		2.4,
		2.5,
		2.6,
		2.7,
		2.8,
		2.9,
		3.0,
		4.0,
		6.0,
		7.0,
		8.0,
		9.0,
		10.0,
		11.0,
		12.0,
	);

	/*
	 * Respect the site owner's choice to load all deprecated functions.
	 * Return an empty array to inform no deprecated version files should be loaded.
	 */
	if ( false === $ignore_deprecated ) {
		return $deprecated_functions_versions;
	}

	/*
	 * Unless the `BP_IGNORE_DEPRECATED` constant is used & set to false, the development
	 * version of BuddyPress do not load deprecated functions.
	 */
	if ( defined( 'BP_SOURCE_SUBDIRECTORY' ) && BP_SOURCE_SUBDIRECTORY === 'src' ) {
		return array();
	}

	/*
	 * If the constant is not defined, put our logic in place so that only the
	 * 2 last versions deprecated functions will be loaded for upgraded installs.
	 */
	$initial_version        = (float) bp_get_initial_version();
	$current_major_version  = (float) bp_get_major_version( bp_get_version() );
	$load_latest_deprecated = $initial_version < $current_major_version;

	// New installs.
	if ( ! $load_latest_deprecated ) {
		// Run some additional checks if PHPUnit is running.
		if ( defined( 'BP_TESTS_DIR' ) ) {
			$deprecated_files = array_filter(
				array_map(
					function( $file ) {
						if ( false !== strpos( $file, '.php' ) ) {
							return (float) str_replace( '.php', '', $file );
						};
					},
					scandir( buddypress()->plugin_dir . 'bp-core/deprecated' )
				)
			);

			if ( array_diff( $deprecated_files, $deprecated_functions_versions ) ) {
				return false;
			}
		}

		// Only load 12.0 deprecated functions.
		return array( 12.0 );
	}

	$index_first_major = array_search( $initial_version, $deprecated_functions_versions, true );
	if ( false === $index_first_major ) {
		return array_splice( $deprecated_functions_versions, -2 );
	}

	$latest_deprecated_functions_versions = array_splice( $deprecated_functions_versions, $index_first_major );

	if ( 2 <= count( $latest_deprecated_functions_versions ) ) {
		$latest_deprecated_functions_versions = array_splice( $latest_deprecated_functions_versions, -2 );
	}

	$index_initial_version = array_search( $initial_version, $latest_deprecated_functions_versions, true );
	if ( false !== $index_initial_version ) {
		unset( $latest_deprecated_functions_versions[ $index_initial_version ] );
	}

	return $latest_deprecated_functions_versions;
}

/**
 * Get the BuddyPress Post Type site ID.
 *
 * @since 12.0.0
 *
 * @return int The site ID the BuddyPress Post Type should be registered on.
 */
function bp_get_post_type_site_id() {
	$site_id = bp_get_root_blog_id();

	/**
	 * Filter here to edit the site ID.
	 *
	 * @todo This will need to be improved to take in account
	 * specific configurations like multiblog.
	 *
	 * @since 12.0.0
	 *
	 * @param integer $site_id The site ID to register the post type on.
	 */
	return (int) apply_filters( 'bp_get_post_type_site_id', $site_id );
}

/**
 * Returns registered navigation items for all or a specific component.
 *
 * @since 12.0.0
 *
 * @param string $component The component ID.
 * @return array            The list of registered navigation items.
 */
function bp_get_component_navigations( $component = '' ) {
	$args = array();
	if ( $component ) {
		$args['id'] = $component;
	}

	$components  = bp_core_get_active_components( $args, 'objects' );
	$navigations = array();

	foreach ( $components as $key_component => $component ) {
		if ( isset( $component->main_nav['rewrite_id'] ) ) {
			$navigations[ $key_component ]['main_nav'] = $component->main_nav;
		}

		if ( isset( $component->sub_nav ) && is_array( $component->sub_nav ) && $component->sub_nav ) {
			$navigations[ $key_component ]['sub_nav'] = $component->sub_nav;
		}
	}

	// We possibly need to move some members nav items.
	if ( isset( $navigations['members']['sub_nav'], $navigations['profile']['sub_nav'] ) ) {
		$profile_subnav_slugs = wp_list_pluck( $navigations['profile']['sub_nav'], 'slug' );

		foreach ( $navigations['members']['sub_nav'] as $members_subnav ) {
			if ( 'profile' === $members_subnav['parent_slug'] && ! in_array( $members_subnav['slug'], $profile_subnav_slugs, true ) ) {
				$navigations['profile']['sub_nav'][] = $members_subnav;
			}
		}
	}

	return $navigations;
}

/**
 * Get the community visibility value calculated from the
 * saved visibility setting.
 *
 * @since 12.0.0
 *
 * @param string $component Whether we want the visibility for a single component
 *                          or for all components.
 *
 * @return arrary|string $retval The calculated visbility settings for the site.
 */
function bp_get_community_visibility( $component = 'global' ) {
	$retval = ( 'all' === $component ) ? array( 'global' => 'anyone' ) : 'anyone';
	if ( 'rewrites' !== bp_core_get_query_parser() ) {
		return $retval;
	}

	$saved_value = (array) bp_get_option( '_bp_community_visibility', array() );

	// If the global value has not been set, we assume that the site is open.
	if ( ! isset( $saved_value['global'] ) ) {
		$saved_value['global'] = 'anyone';
	}

	if ( 'all' === $component ) {
		// Build the component list.
		$retval = array(
			'global' => $saved_value['global']
		);
		$directory_pages = bp_core_get_directory_pages();
		foreach ( $directory_pages as $component_id => $component_page ) {
			if ( in_array( $component_id, array( 'register', 'activate' ), true ) ) {
				continue;
			}
			$retval[ $component_id ] = isset( $saved_value[ $component_id ] ) ? $saved_value[ $component_id ] : $saved_value['global'];
		}
	} else {
		// We are checking a particular component.
		// Fall back to the global value if not set.
		$retval = isset( $saved_value[ $component ] ) ? $saved_value[ $component ] : $saved_value['global'];
	}

	/**
	 * Filter the community visibility value calculated from the
	 * saved visibility setting.
	 *
	 * @since 12.0.0
	 *
	 * @param arrary|string $retval    The calculated visbility settings for the site.
	 * @param string        $component The component value to get the visibility for.
	 */
	return apply_filters( 'bp_get_community_visibility', $retval, $component );
}

/**
 * Returns the list of unread Admin Notification IDs.
 *
 * @since 11.4.0
 *
 * @return array The list of unread Admin Notification IDs.
 */
function bp_core_get_unread_admin_notifications() {
	return (array) bp_get_option( 'bp_unread_admin_notifications', array() );
}

/**
 * Dismisses an Admin Notification.
 *
 * @since 11.4.0
 *
 * @param string $notification_id The Admin Notification to dismiss.
 */
function bp_core_dismiss_admin_notification( $notification_id = '' ) {
	$unread    = bp_core_get_unread_admin_notifications();
	$remaining = array_diff( $unread, array( $notification_id ) );
	bp_update_option( 'bp_unread_admin_notifications', $remaining );
}

/**
 * @since 11.4.0
 *
 * @return array The list of Admin notifications.
 */
function bp_core_get_admin_notifications() {
	$unreads = bp_core_get_unread_admin_notifications();
	if ( ! $unreads ) {
		return array();
	}

	$admin_notifications = array(
		'bp100-welcome-addons' => (object) array(
			'id'      => 'bp100-welcome-addons',
			'href'    => add_query_arg(
				array(
					'tab' => 'bp-add-ons',
					'n'   => 'bp100-welcome-addons',
				),
				bp_get_admin_url( 'plugin-install.php' )
			),
			'text'    => __( 'Discover BuddyPress Add-ons', 'buddypress' ),
			'title'   => __( 'Hello BuddyPress Add-ons!', 'buddypress' ),
			'content' => __( 'Add-ons are features as Plugins or Blocks maintained by the BuddyPress development team & hosted on the WordPress.org plugins directory.', 'buddypress' ) .
			             __( 'Thanks to this new tab inside your Dashboard screen to add plugins, you’ll be able to find them faster and eventually contribute to beta features early to give the BuddyPress development team your feedbacks.', 'buddypress' ),
			'version' => 10.0,
		),
		'bp114-prepare-for-rewrites' => (object) array(
			'id'      => 'bp114-prepare-for-rewrites',
			'href'    => add_query_arg(
				array(
					'tab'  => 'bp-add-ons',
					'show' => 'bp-classic',
					'n'    => 'bp114-prepare-for-rewrites'
				),
				bp_get_admin_url( 'plugin-install.php' )
			),
			'text'    => __( 'Get The BP Classic Add-on', 'buddypress' ),
			'title'   => __( 'Get ready for the brand-new BP Rewrites API!', 'buddypress' ),
			'content' => __( 'Our next major version (12.0.0) will introduce several large changes that could be incompatible with your site\'s configuration. To prevent problems, we\'ve built the BP Classic Add-on, which you may want to proactively install if any of the following cases:', 'buddypress' ) . '<br><br>' .
				'<strong>' . __( 'Some of your BuddyPress plugins have not been updated lately.', 'buddypress' ) . '</strong><br>' .
				__( 'BuddyPress 12.0.0 introduces the BP Rewrites API, which completely changes the way BuddyPress URLs are built and routed. This fundamental change requires most BuddyPress plugins to update how they deal with BuddyPress URLs. If your BuddyPress plugins have not been updated in the last few months, they are probably not ready for BuddyPress 12.0.0.', 'buddypress' ) . '<br><br>' .
				'<strong>' . __( 'You are still using the BP Default theme.', 'buddypress' ) . '</strong><br><br>' .
				'<strong>' . __( 'You still use a BP Legacy Widget.', 'buddypress' ) . '</strong><br><br>' .
				__( 'If any of the above items are true, we strongly advise you to install and activate the Classic Add-on before updating to BuddyPress 12.0.0.', 'buddypress' ),
				'version' => 11.4,
		),
		'bp120-new-installs-warning' => (object) array(
			'id'      => 'bp120-new-installs-warning',
			'href'    => add_query_arg(
				array(
					'tab'  => 'bp-add-ons',
					'show' => 'bp-classic',
					'n'    => 'bp120-new-installs-warning'
				),
				bp_get_admin_url( 'plugin-install.php' )
			),
			'text'    => __( 'Get The BP Classic Add-on', 'buddypress' ),
			'title'   => __( 'Thank you for installing BuddyPress 12.0!', 'buddypress' ),
			'content' => __( 'BuddyPress 12.0 introduces major core changes, overhauling the way that BuddyPress builds and parses URLs.', 'buddypress' ) . '<br><br>' .
				__( 'If you find that your site is not working correctly with the new version, try installing the new BP Classic Add-on that adds backwards compatibility for plugins and themes that have not yet been updated to work with BuddyPress 12.0.', 'buddypress' ),
				'version' => 12.0,
		),
	);

	// Only keep unread notifications.
	foreach ( array_keys( $admin_notifications ) as $notification_id ) {
		if ( ! in_array( $notification_id, $unreads, true ) ) {
			unset( $admin_notifications[ $notification_id ] );
		}
	}

	return $admin_notifications;
}
