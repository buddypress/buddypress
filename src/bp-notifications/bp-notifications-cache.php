<?php

/**
 * Functions related to notifications caching.
 *
 * @since BuddyPress (2.0.0)
 */

/**
 * Slurp up metadata for a set of notifications.
 *
 * It grabs all notification meta associated with all of the notifications
 * passed in $notification_ids and adds it to WP cache. This improves efficiency
 * when using notification meta within a loop context.
 *
 * @since BuddyPress (2.3.0)
 *
 * @param int|str|array $notification_ids Accepts a single notification_id, or a
 *                                        comma-separated list or array of
 *                                        notification ids.
 */
function bp_notifications_update_meta_cache( $notification_ids = false ) {
	bp_update_meta_cache( array(
		'object_ids' 	   => $notification_ids,
		'object_type' 	   => buddypress()->notifications->id,
		'cache_group'      => 'notification_meta',
		'object_column'    => 'notification_id',
		'meta_table' 	   => buddypress()->notifications->table_name_meta,
		'cache_key_prefix' => 'bp_notifications_meta'
	) );
}

/**
 * Invalidate 'all_for_user_' cache when saving.
 *
 * @since BuddyPress (2.0.0)
 *
 * @param BP_Notifications_Notification $n Notification object.
 */
function bp_notifications_clear_all_for_user_cache_after_save( BP_Notifications_Notification $n ) {
	wp_cache_delete( 'all_for_user_' . $n->user_id, 'bp_notifications' );
}
add_action( 'bp_notification_after_save', 'bp_notifications_clear_all_for_user_cache_after_save' );

/**
 * Invalidate the 'all_for_user_' cache when deleting.
 *
 * @since BuddyPress (2.0.0)
 *
 * @param int $args Notification deletion arguments.
 */
function bp_notifications_clear_all_for_user_cache_before_delete( $args ) {
	// Pull up a list of items matching the args (those about te be deleted)
	$ns = BP_Notifications_Notification::get( $args );

	$user_ids = array();
	foreach ( $ns as $n ) {
		$user_ids[] = $n->user_id;
	}

	foreach ( array_unique( $user_ids ) as $user_id ) {
		wp_cache_delete( 'all_for_user_' . $user_id, 'bp_notifications' );
	}
}
add_action( 'bp_notification_before_delete', 'bp_notifications_clear_all_for_user_cache_before_delete' );
