<?php
/**
 * Caching functions handle the clearing of cached objects and pages on specific
 * actions throughout BuddyPress.
 */

// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

/**
 * REQUIRES WP-SUPER-CACHE
 *
 * When wp-super-cache is installed this function will clear cached pages
 * so that success/error messages are not cached, or time sensitive content.
 *
 * @package BuddyPress Core
 */
function bp_core_clear_cache() {
	global $cache_path;

	if ( function_exists( 'prune_super_cache' ) ) {
		do_action( 'bp_core_clear_cache' );
		return prune_super_cache( $cache_path, true );
	}
}

/**
 * Add's 'bp' to global group of network wide cachable objects
 *
 * @package BuddyPress Core
 */
function bp_core_add_global_group() {
	if ( function_exists( 'wp_cache_add_global_groups' ) ) {
		wp_cache_add_global_groups( array( 'bp' ) );
	}
}
add_action( 'bp_loaded', 'bp_core_add_global_group' );

/**
 * Clears all cached objects for a user, or a user is part of.
 *
 * @package BuddyPress Core
 */
function bp_core_clear_user_object_cache( $user_id ) {
	wp_cache_delete( 'bp_user_' . $user_id, 'bp' );
}

/**
 * Clears member count caches and transients
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
 * Update the metadata cache for the specified objects.
 *
 * @since BuddyPress (1.6)
 * @global $wpdb WordPress database object for queries.
 * @param array $args See $defaults definition for more details
 * @return mixed Metadata cache for the specified objects, or false on failure.
 */
function bp_update_meta_cache( $args = array() ) {
	global $wpdb;

	$defaults = array(
		'object_ids' 	   => array(), // Comma-separated list or array of item ids
		'object_type' 	   => '',      // Canonical component id: groups, members, etc
		'meta_table' 	   => '',      // Name of the table containing the metadata
		'object_column'    => '',      // DB column for the object ids (group_id, etc)
		'cache_key_prefix' => ''       // Prefix to use when creating cache key names. Eg
					       //    'bp_groups_groupmeta'
	);
	$r = wp_parse_args( $args, $defaults );
	extract( $r );

	if ( empty( $object_ids ) || empty( $object_type ) || empty( $meta_table ) ) {
		return false;
	}

	if ( empty( $cache_key_prefix ) ) {
		$cache_key_prefix = $meta_table;
	}

	if ( empty( $object_column ) ) {
		$object_column = $object_type . '_id';
	}

	$object_ids = wp_parse_id_list( $object_ids );

	$cache = array();

	// Get meta info
	$id_list   = join( ',', $object_ids );
	$meta_list = $wpdb->get_results( $wpdb->prepare( "SELECT {$object_column}, meta_key, meta_value FROM {$meta_table} WHERE {$object_column} IN ($id_list)", $object_type ), ARRAY_A );

	if ( !empty( $meta_list ) ) {
		foreach ( $meta_list as $metarow ) {
			$mpid = intval( $metarow[$object_column] );
			$mkey = $metarow['meta_key'];
			$mval = $metarow['meta_value'];

			// Force subkeys to be array type:
			if ( !isset( $cache[$mpid] ) || !is_array( $cache[$mpid] ) )
				$cache[$mpid] = array();
			if ( !isset( $cache[$mpid][$mkey] ) || !is_array( $cache[$mpid][$mkey] ) )
				$cache[$mpid][$mkey] = array();

			// Add a value to the current pid/key:
			$cache[$mpid][$mkey][] = $mval;
		}
	}

	foreach ( $object_ids as $id ) {
		if ( ! isset($cache[$id]) )
			$cache[$id] = array();

		foreach( $cache[$id] as $meta_key => $meta_value ) {
			wp_cache_set( $cache_key_prefix . '_' . $id . '_' . $meta_key, $meta_value, 'bp' );
		}
	}

	return $cache;
}
