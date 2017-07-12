<?php
/**
 * BuddyPress Core Caching Functions.
 *
 * Caching functions handle the clearing of cached objects and pages on specific
 * actions throughout BuddyPress.
 *
 * @package BuddyPress
 * @supackage Cache
 * @since 1.5.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Prune the WP Super Cache.
 *
 * When WP Super Cache is installed, this function will clear cached pages
 * so that success/error messages or time-sensitive content are not cached.
 *
 * @since 1.0.0
 *
 * @see prune_super_cache()
 *
 * @return int
 */
function bp_core_clear_cache() {
	global $cache_path;

	if ( function_exists( 'prune_super_cache' ) ) {

		/**
		 * Fires before the pruning of WP Super Cache.
		 *
		 * @since 1.0.0
		 */
		do_action( 'bp_core_clear_cache' );
		return prune_super_cache( $cache_path, true );
	}
}

/**
 * Clear all cached objects for a user, or those that a user is part of.
 *
 * @since 1.0.0
 *
 * @param string $user_id User ID to delete cache for.
 */
function bp_core_clear_user_object_cache( $user_id ) {
	wp_cache_delete( 'bp_user_' . $user_id, 'bp' );
}

/**
 * Clear member count caches and transients.
 *
 * @since 1.6.0
 */
function bp_core_clear_member_count_caches() {
	wp_cache_delete( 'bp_total_member_count', 'bp' );
	delete_transient( 'bp_active_member_count' );
}
add_action( 'bp_core_activated_user',         'bp_core_clear_member_count_caches' );
add_action( 'bp_core_process_spammer_status', 'bp_core_clear_member_count_caches' );
add_action( 'bp_core_deleted_account',        'bp_core_clear_member_count_caches' );
add_action( 'bp_first_activity_for_member',   'bp_core_clear_member_count_caches' );
add_action( 'deleted_user',                   'bp_core_clear_member_count_caches' );

/**
 * Clear the directory_pages cache when one of the pages is updated.
 *
 * @since 2.0.0
 *
 * @param int $post_id ID of the page that was saved.
 */
function bp_core_clear_directory_pages_cache_page_edit( $post_id = 0 ) {

	// Bail if BP is not defined here.
	if ( ! buddypress() ) {
		return;
	}

	// Bail if not on the root blog
	if ( ! bp_is_root_blog() ) {
		return;
	}

	$page_ids = bp_core_get_directory_page_ids( 'all' );

	// Bail if post ID is not a directory page
	if ( ! in_array( $post_id, $page_ids ) ) {
		return;
	}

	wp_cache_delete( 'directory_pages', 'bp_pages' );
}
add_action( 'save_post', 'bp_core_clear_directory_pages_cache_page_edit' );

/**
 * Clear the directory_pages cache when the bp-pages option is updated.
 *
 * @since 2.0.0
 *
 * @param string $option Option name.
 */
function bp_core_clear_directory_pages_cache_settings_edit( $option ) {
	if ( 'bp-pages' === $option ) {
		wp_cache_delete( 'directory_pages', 'bp_pages' );
	}
}
add_action( 'update_option', 'bp_core_clear_directory_pages_cache_settings_edit' );

/**
 * Clear the root_blog_options cache when any of its options are updated.
 *
 * @since 2.0.0
 *
 * @param string $option Option name.
 */
function bp_core_clear_root_options_cache( $option ) {
	foreach ( array( 'add_option', 'add_site_option', 'update_option', 'update_site_option' ) as $action ) {
		remove_action( $action, 'bp_core_clear_root_options_cache' );
	}

	// Surrounding code prevents infinite loops on WP < 4.4.
	$keys = array_keys( bp_get_default_options() );

	foreach ( array( 'add_option', 'add_site_option', 'update_option', 'update_site_option' ) as $action ) {
		add_action( $action, 'bp_core_clear_root_options_cache' );
	}

	$keys = array_merge( $keys, array(
		'registration',
		'avatar_default',
		'tags_blog_id',
		'sitewide_tags_blog',
		'registration',
		'fileupload_mask',
	) );

	if ( in_array( $option, $keys ) ) {
		wp_cache_delete( 'root_blog_options', 'bp' );
	}
}
add_action( 'update_option', 'bp_core_clear_root_options_cache' );
add_action( 'update_site_option', 'bp_core_clear_root_options_cache' );
add_action( 'add_option', 'bp_core_clear_root_options_cache' );
add_action( 'add_site_option', 'bp_core_clear_root_options_cache' );

/**
 * Determine which items from a list do not have cached values.
 *
 * @since 2.0.0
 *
 * @param array  $item_ids    ID list.
 * @param string $cache_group The cache group to check against.
 * @return array
 */
function bp_get_non_cached_ids( $item_ids, $cache_group ) {
	$uncached = array();

	foreach ( $item_ids as $item_id ) {
		$item_id = (int) $item_id;
		if ( false === wp_cache_get( $item_id, $cache_group ) ) {
			$uncached[] = $item_id;
		}
	}

	return $uncached;
}

/**
 * Update the metadata cache for the specified objects.
 *
 * Based on WordPress's {@link update_meta_cache()}, this function primes the
 * cache with metadata related to a set of objects. This is typically done when
 * querying for a loop of objects; pre-fetching metadata for each queried
 * object can lead to dramatic performance improvements when using metadata
 * in the context of template loops.
 *
 * @since 1.6.0
 *
 * @global object $wpdb WordPress database object for queries..
 *
 * @param array $args {
 *     Array of arguments.
 *     @type array|string $object_ids       List of object IDs to fetch metadata for.
 *                                          Accepts an array or a comma-separated list of numeric IDs.
 *     @type string       $object_type      The type of object, eg 'groups' or 'activity'.
 *     @type string       $meta_table       The name of the metadata table being queried.
 *     @type string       $object_column    Optional. The name of the database column where IDs
 *                                          (those provided by $object_ids) are found. Eg, 'group_id'
 *                                          for the groups metadata tables. Default: $object_type . '_id'.
 *     @type string       $cache_key_prefix Optional. The prefix to use when creating
 *                                          cache key names. Default: the value of $meta_table.
 * }
 * @return false|array Metadata cache for the specified objects, or false on failure.
 */
function bp_update_meta_cache( $args = array() ) {
	global $wpdb;

	$defaults = array(
		'object_ids' 	   => array(), // Comma-separated list or array of item ids.
		'object_type' 	   => '',      // Canonical component id: groups, members, etc.
		'cache_group'      => '',      // Cache group.
		'meta_table' 	   => '',      // Name of the table containing the metadata.
		'object_column'    => '',      // DB column for the object ids (group_id, etc).
		'cache_key_prefix' => ''       // Prefix to use when creating cache key names. Eg 'bp_groups_groupmeta'.
	);
	$r = wp_parse_args( $args, $defaults );
	extract( $r );

	if ( empty( $object_ids ) || empty( $object_type ) || empty( $meta_table ) || empty( $cache_group ) ) {
		return false;
	}

	if ( empty( $cache_key_prefix ) ) {
		$cache_key_prefix = $meta_table;
	}

	if ( empty( $object_column ) ) {
		$object_column = $object_type . '_id';
	}

	if ( ! $cache_group ) {
		return false;
	}

	$object_ids   = wp_parse_id_list( $object_ids );
	$uncached_ids = bp_get_non_cached_ids( $object_ids, $cache_group );

	$cache = array();

	// Get meta info.
	if ( ! empty( $uncached_ids ) ) {
		$id_list   = join( ',', wp_parse_id_list( $uncached_ids ) );
		$meta_list = $wpdb->get_results( esc_sql( "SELECT {$object_column}, meta_key, meta_value FROM {$meta_table} WHERE {$object_column} IN ({$id_list})" ), ARRAY_A );

		if ( ! empty( $meta_list ) ) {
			foreach ( $meta_list as $metarow ) {
				$mpid = intval( $metarow[$object_column] );
				$mkey = $metarow['meta_key'];
				$mval = $metarow['meta_value'];

				// Force subkeys to be array type.
				if ( !isset( $cache[$mpid] ) || !is_array( $cache[$mpid] ) )
					$cache[$mpid] = array();
				if ( !isset( $cache[$mpid][$mkey] ) || !is_array( $cache[$mpid][$mkey] ) )
					$cache[$mpid][$mkey] = array();

				// Add a value to the current pid/key.
				$cache[$mpid][$mkey][] = $mval;
			}
		}

		foreach ( $uncached_ids as $uncached_id ) {
			// Cache empty values as well.
			if ( ! isset( $cache[ $uncached_id ] ) ) {
				$cache[ $uncached_id ] = array();
			}

			wp_cache_set( $uncached_id, $cache[ $uncached_id ], $cache_group );
		}
	}

	return $cache;
}

/**
 * Gets a value that has been cached using an incremented key.
 *
 * A utility function for use by query methods like BP_Activity_Activity::get().
 *
 * @since 2.7.0
 * @see bp_core_set_incremented_cache()
 *
 * @param string $key   Unique key for the query. Usually a SQL string.
 * @param string $group Cache group. Eg 'bp_activity'.
 * @return array|bool False if no cached values are found, otherwise an array of IDs.
 */
function bp_core_get_incremented_cache( $key, $group ) {
	$cache_key = bp_core_get_incremented_cache_key( $key, $group );
	return wp_cache_get( $cache_key, $group );
}

/**
 * Caches a value using an incremented key.
 *
 * An "incremented key" is a cache key that is hashed with a unique incrementor,
 * allowing for bulk invalidation.
 *
 * Use this method when caching data that should be invalidated whenever any
 * object of a given type is created, updated, or deleted. This usually means
 * data related to object queries, which can only reliably cached until the
 * underlying set of objects has been modified. See, eg, BP_Activity_Activity::get().
 *
 * @since 2.7.0
 *
 * @param string $key   Unique key for the query. Usually a SQL string.
 * @param string $group Cache group. Eg 'bp_activity'.
 * @param array  $ids   Array of IDs.
 * @return bool
 */
function bp_core_set_incremented_cache( $key, $group, $ids ) {
	$cache_key = bp_core_get_incremented_cache_key( $key, $group );
	return wp_cache_set( $cache_key, $ids, $group );
}

/**
 * Gets the key to be used when caching a value using an incremented cache key.
 *
 * The $key is hashed with a component-specific incrementor, which is used to
 * invalidate multiple caches at once.

 * @since 2.7.0
 *
 * @param string $key   Unique key for the query. Usually a SQL string.
 * @param string $group Cache group. Eg 'bp_activity'.
 * @return string
 */
function bp_core_get_incremented_cache_key( $key, $group ) {
	$incrementor = bp_core_get_incrementor( $group );
	$cache_key = md5( $key . $incrementor );
	return $cache_key;
}

/**
 * Gets a group-specific cache incrementor.
 *
 * The incrementor is paired with query identifiers (like SQL strings) to
 * create cache keys that can be invalidated en masse.
 *
 * If an incrementor does not yet exist for the given `$group`, one will
 * be created.
 *
 * @since 2.7.0
 *
 * @param string $group Cache group. Eg 'bp_activity'.
 * @return string
 */
function bp_core_get_incrementor( $group ) {
	$incrementor = wp_cache_get( 'incrementor', $group );
	if ( ! $incrementor ) {
		$incrementor = microtime();
		wp_cache_set( 'incrementor', $incrementor, $group );
	}

	return $incrementor;
}

/**
 * Reset a group-specific cache incrementor.
 *
 * Call this function when all incrementor-based caches associated with a given
 * cache group should be invalidated.
 *
 * @since 2.7.0
 *
 * @param string $group Cache group. Eg 'bp_activity'.
 * @return bool True on success, false on failure.
 */
function bp_core_reset_incrementor( $group ) {
	return wp_cache_delete( 'incrementor', $group );
}
