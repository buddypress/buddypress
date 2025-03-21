<?php
/**
 * BuddyPress Activity Caching.
 *
 * @package BuddyPress
 * @subpackage Activity
 * @since 1.6.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Slurp up activitymeta for a specified set of activity items.
 *
 * It grabs all activitymeta associated with all of the activity items passed
 * in $activity_ids and adds it to the WP cache. This improves efficiency when
 * using querying activitymeta inline.
 *
 * @since 1.6.0
 *
 * @param int|string|array|bool $activity_ids Accepts a single activity ID, or a comma-
 *                                            separated list or array of activity ids.
 */
function bp_activity_update_meta_cache( $activity_ids = false ) {
	$bp = buddypress();

	$cache_args = array(
		'object_ids'       => $activity_ids,
		'object_type'      => $bp->activity->id,
		'object_column'    => 'activity_id',
		'cache_group'      => 'activity_meta',
		'meta_table'       => $bp->activity->table_name_meta,
		'cache_key_prefix' => 'bp_activity_meta',
	);

	bp_update_meta_cache( $cache_args );
}

/**
 * Clear a cached activity item when that item is updated.
 *
 * @since 2.0.0
 *
 * @param BP_Activity_Activity $activity Activity object.
 */
function bp_activity_clear_cache_for_activity( $activity ) {
	wp_cache_delete( $activity->id, 'bp_activity' );
	wp_cache_delete( 'bp_activity_sitewide_front', 'bp' );

	// Clear the comments cache for the parent activity ID.
	if ( 'activity_comment' === $activity->type && ! empty( $activity->item_id ) ) {
		wp_cache_delete( $activity->item_id, 'bp_activity_comments' );
	}
}
add_action( 'bp_activity_after_save', 'bp_activity_clear_cache_for_activity' );

/**
 * Clear cached data for deleted activity items.
 *
 * @since 2.0.0
 *
 * @param array $deleted_ids IDs of deleted activity items.
 */
function bp_activity_clear_cache_for_deleted_activity( $deleted_ids ) {
	foreach ( (array) $deleted_ids as $deleted_id ) {
		wp_cache_delete( $deleted_id, 'bp_activity' );
	}
}
add_action( 'bp_activity_deleted_activities', 'bp_activity_clear_cache_for_deleted_activity' );

/**
 * Reset cache incrementor for the Activity component.
 *
 * Called whenever an activity item is created, updated, or deleted, this
 * function effectively invalidates all cached results of activity queries.
 *
 * @since 2.7.0
 *
 * @return bool
 */
function bp_activity_reset_cache_incrementor() {
	$without_last_activity = bp_core_reset_incrementor( 'bp_activity' );
	$with_last_activity    = bp_core_reset_incrementor( 'bp_activity_with_last_activity' );
	return $without_last_activity && $with_last_activity;
}
add_action( 'bp_activity_delete', 'bp_activity_reset_cache_incrementor' );
add_action( 'bp_activity_add', 'bp_activity_reset_cache_incrementor' );
add_action( 'added_activity_meta', 'bp_activity_reset_cache_incrementor' );
add_action( 'updated_activity_meta', 'bp_activity_reset_cache_incrementor' );
add_action( 'deleted_activity_meta', 'bp_activity_reset_cache_incrementor' );
