<?php
/**
 * BuddyPress Member Notifications
 *
 * Backwards compatibility functions and filters used for member notifications.
 * Use bp-notifications instead.
 *
 * @package BuddyPress
 * @deprecated 1.9.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Add a notification for a specific user, from a specific component.
 *
 * @deprecated Deprecated since BuddyPress 1.9.0. Use
 *             bp_notifications_add_notification() instead.
 *
 * @since 1.0.0
 *
 * @param int          $item_id           ID of the associated item.
 * @param int          $user_id           ID of the user receiving the notification.
 * @param string       $component_name    Name of the component creating the notification.
 * @param string       $component_action  Name of the component action.
 * @param int          $secondary_item_id Optional. ID of the associated secondary item.
 * @param false|string $date_notified     Optional. Date the notification was created.
 * @param int          $is_new            Optional. Whether the notification is unread.
 * @return int|false Notification ID on success, false on failure.
 */
function bp_core_add_notification( $item_id, $user_id, $component_name, $component_action, $secondary_item_id = 0, $date_notified = false, $is_new = 1 ) {

	// Bail if notifications is not active.
	if ( ! bp_is_active( 'notifications' ) ) {
		return false;
	}

	// Trigger the deprecated function notice.
	_deprecated_function( __FUNCTION__, '1.9', 'bp_notifications_add_notification()' );

	// Notifications must always have a time.
	if ( false === $date_notified ) {
		$date_notified = bp_core_current_time();
	}

	// Add the notification.
	return bp_notifications_add_notification( array(
		'item_id'           => $item_id,
		'user_id'           => $user_id,
		'component_name'    => $component_name,
		'component_action'  => $component_action,
		'secondary_item_id' => $secondary_item_id,
		'date_notified'     => $date_notified,
		'is_new'            => $is_new
	) );
}

/**
 * Delete a specific notification by its ID.
 *
 * @deprecated Deprecated since BuddyPress 1.9.0. Use
 *             bp_notifications_delete_notification() instead.
 *
 * @since 1.0.0
 *
 * @param int $id ID of notification.
 * @return false|int Number of rows affected on success, false on failure.
 */
function bp_core_delete_notification( $id ) {

	// Bail if notifications is not active.
	if ( ! bp_is_active( 'notifications' ) ) {
		return false;
	}

	// Trigger the deprecated function notice.
	_deprecated_function( __FUNCTION__, '1.9', 'bp_notifications_delete_notification()' );

	return BP_Notifications_Notification::delete_by_id( $id );
}

/**
 * Get a specific notification by its ID.
 *
 * @deprecated Deprecated since BuddyPress 1.9.0. Use
 *             bp_notifications_get_notification() instead.
 *
 * @since 1.0.0
 * @param int $id ID of notification.
 * @return false|BP_Notifications_Notification Notification object on success, false on failure.
 */
function bp_core_get_notification( $id ) {

	// Bail if notifications is not active.
	if ( ! bp_is_active( 'notifications' ) ) {
		return false;
	}

	// Trigger the deprecated function notice.
	_deprecated_function( __FUNCTION__, '1.9', 'bp_notifications_get_notification()' );

	return bp_notifications_get_notification( $id );
}

/**
 * Get notifications for a specific user.
 *
 * @deprecated Deprecated since BuddyPress 1.9.0. Use
 *             bp_notifications_get_notifications_for_user() instead.
 *
 * @since 1.0.0
 * @param int    $user_id ID of user.
 * @param string $format  Optional. Format of the returned notifications.
 * @return array|false Array of notifications on success, false on failure.
 */
function bp_core_get_notifications_for_user( $user_id, $format = 'string' ) {

	// Bail if notifications is not active.
	if ( ! bp_is_active( 'notifications' ) ) {
		return false;
	}

	// Trigger the deprecated function notice.
	_deprecated_function( __FUNCTION__, '1.9', 'bp_notifications_get_notifications_for_user()' );

	return bp_notifications_get_notifications_for_user( $user_id, $format );
}

/** Delete ********************************************************************/

/**
 * Delete notifications for a user by type.
 *
 * Used when clearing out notifications for a specific component when the user
 * has visited that component.
 *
 * @deprecated Deprecated since BuddyPress 1.9.0. Use
 *             bp_notifications_delete_notifications_by_type() instead.
 *
 * @since 1.0.0
 * @param int    $user_id          ID of the user receiving the notifications.
 * @param string $component_name   Name of the component that created the notifications.
 * @param string $component_action Name of the component action.
 * @return false|int Number of rows affected on success, false on failure.
 */
function bp_core_delete_notifications_by_type( $user_id, $component_name, $component_action ) {

	// Bail if notifications is not active.
	if ( ! bp_is_active( 'notifications' ) ) {
		return false;
	}

	// Trigger the deprecated function notice.
	_deprecated_function( __FUNCTION__, '1.9', 'bp_notifications_delete_notifications_by_type()' );

	return bp_notifications_delete_notifications_by_type( $user_id, $component_name, $component_action );
}

/**
 * Delete notifications for an item ID.
 *
 * Used when clearing out notifications for a specific component when the user
 * has visited that component.
 *
 * @deprecated Deprecated since BuddyPress 1.9.0. Use
 *             bp_notifications_delete_notifications_by_item_id() instead.
 *
 * @since 1.0.0
 *
 * @param int       $user_id           ID of the user receiving the notifications.
 * @param int       $item_id           ID of the associated item.
 * @param string    $component_name    Name of the component that created the notifications.
 * @param string    $component_action  Name of the component action.
 * @param false|int $secondary_item_id Optional. Secondary item ID.
 * @return false|int Number of rows affected on success, false on failure.
 */
function bp_core_delete_notifications_by_item_id( $user_id, $item_id, $component_name, $component_action, $secondary_item_id = false ) {

	// Bail if notifications is not active.
	if ( ! bp_is_active( 'notifications' ) ) {
		return false;
	}

	// Trigger the deprecated function notice.
	_deprecated_function( __FUNCTION__, '1.9', 'bp_notifications_delete_notifications_by_item_id()' );

	return bp_notifications_delete_notifications_by_item_id( $user_id, $item_id, $component_name, $component_action, $secondary_item_id );
}

/**
 * Delete all notifications for by type.
 *
 * @deprecated Deprecated since BuddyPress 1.9.0. Use
 *             bp_notifications_delete_all_notifications_by_type() instead.
 *
 * @since 1.0.0
 *
 * @param int          $item_id           ID of the associated item.
 * @param string       $component_name    Name of the component that created the notifications.
 * @param false|string $component_action  Optional. Name of the component action.
 * @param false|int    $secondary_item_id Optional. Secondary item ID.
 * @return false|null False when Notifications is inactive; otherwise null.
 */
function bp_core_delete_all_notifications_by_type( $item_id, $component_name, $component_action = false, $secondary_item_id = false ) {

	// Bail if notifications is not active.
	if ( ! bp_is_active( 'notifications' ) ) {
		return false;
	}

	// Trigger the deprecated function notice.
	_deprecated_function( __FUNCTION__, '1.9', 'bp_notifications_delete_all_notifications_by_type()' );

	bp_notifications_delete_all_notifications_by_type( $item_id, $component_name, $component_action, $secondary_item_id );
}

/**
 * Delete all notifications for a user.
 *
 * Used when clearing out all notifications for a user, when deleted or spammed.
 *
 * @deprecated Deprecated since BuddyPress 1.9.0. Use
 *             bp_notifications_delete_notifications_from_user() instead.
 *
 * @since 1.0.0
 * @param int    $user_id          ID of the user whose associated items are being deleted.
 * @param string $component_name   Name of the component that created the notifications.
 * @param string $component_action Name of the component action.
 * @return false|int Number of rows affected on success, false on failure.
 */
function bp_core_delete_notifications_from_user( $user_id, $component_name, $component_action ) {

	// Bail if notifications is not active.
	if ( ! bp_is_active( 'notifications' ) ) {
		return false;
	}

	// Trigger the deprecated function notice.
	_deprecated_function( __FUNCTION__, '1.9', 'bp_notifications_delete_notifications_from_user()' );

	return bp_notifications_delete_notifications_from_user( $user_id, $component_name, $component_action );
}

/** Helpers *******************************************************************/

/**
 * Check if a user has access to a specific notification.
 *
 * Used before deleting a notification for a user.
 *
 * @deprecated Deprecated since BuddyPress 1.9.0. Use
 *             bp_notifications_check_notification_access() instead.
 *
 * @since 1.0.0
 * @param int $user_id         ID of the user.
 * @param int $notification_id ID of the notification.
 * @return bool Whether the user has access to the notification.
 */
function bp_core_check_notification_access( $user_id, $notification_id ) {

	// Bail if notifications is not active.
	if ( ! bp_is_active( 'notifications' ) ) {
		return false;
	}

	// Trigger the deprecated function notice.
	_deprecated_function( __FUNCTION__, '1.9', 'bp_notifications_check_notification_access()' );

	return bp_notifications_check_notification_access( $user_id, $notification_id );
}
