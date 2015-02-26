<?php

/**
 * Functions related to notifications caching.
 *
 * @since BuddyPress (2.0.0)
 */

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
