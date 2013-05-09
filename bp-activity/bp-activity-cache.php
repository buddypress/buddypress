<?php

/**
 * Functions related to the BuddyPress Activity component and the WP Cache
 *
 * @since BuddyPress (1.6)
 */

// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

/**
 * Slurps up activitymeta
 *
 * This function is called in two places in the BP_Groups_Group class:
 *   - in the populate() method, when single group objects are populated
 *   - in the get() method, when multiple groups are queried
 *
 * It grabs all groupmeta associated with all of the groups passed in $group_ids and adds it to
 * the WP cache. This improves efficiency when using groupmeta inline
 *
 * @param int|str|array $group_ids Accepts a single group_id, or a comma-separated list or array of
 *    group ids
 */
function bp_activity_update_meta_cache( $activity_ids = false ) {
	global $bp;

	$cache_args = array(
		'object_ids' 	   => $activity_ids,
		'object_type' 	   => $bp->activity->id,
		'object_column'    => 'activity_id',
		'meta_table' 	   => $bp->activity->table_name_meta,
		'cache_key_prefix' => 'bp_activity_meta'
	);

	bp_update_meta_cache( $cache_args );
}

/**
 * Clear the cache for all metadata of a given activity
 *
 * @param int $activity_id
 */
function bp_activity_clear_meta_cache_for_activity( $activity_id ) {
	global $wp_object_cache;

	if ( is_object( $wp_object_cache ) && ! empty( $wp_object_cache->cache['bp'] ) ) {
		foreach ( $wp_object_cache->cache['bp'] as $ckey => $cvalue ) {
			if ( 0 === strpos( $ckey, 'bp_activity_meta_' . $activity_id ) ) {
				wp_cache_delete( $ckey, 'bp' );
			}
		}
	}
}
