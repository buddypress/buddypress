<?php

/**
 * BuddyPress Member Notifications
 *
 * Backwards compatibility functions and filters used for member notifications.
 * Use bp-notifications instead.
 *
 * @package BuddyPress
 * @subpackage MembersNotifications
 */

// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

/**
 * Add a notification for a specific user, from a specific component
 *
 * @since BuddyPress (1.0)
 * @param string $item_id
 * @param int $user_id
 * @param string $component_name
 * @param string $component_action
 * @param string $secondary_item_id
 * @param string $date_notified
 * @return boolean True on success, false on fail
 */
function bp_core_add_notification( $item_id, $user_id, $component_name, $component_action, $secondary_item_id = 0, $date_notified = false ) {

	// Bail if notifications is not active
	if ( ! bp_is_active( 'notifications' ) ) {
		return false;
	}

	return bp_notifications_add_notification( $item_id, $user_id, $component_name, $component_action, $secondary_item_id = 0, $date_notified = false );
}

/**
 * Delete a specific notification by its ID
 *
 * @since BuddyPress (1.0)
 * @param int $id
 * @return boolean True on success, false on fail
 */
function bp_core_delete_notification( $id ) {

	// Bail if notifications is not active
	if ( ! bp_is_active( 'notifications' ) ) {
		return false;
	}

	return bp_notifications_delete_notification( $id );
}

/**
 * Get a specific notification by its ID
 *
 * @since BuddyPress (1.0)
 * @param int $id
 * @return BP_Core_Notification
 */
function bp_core_get_notification( $id ) {

	// Bail if notifications is not active
	if ( ! bp_is_active( 'notifications' ) ) {
		return false;
	}

	return bp_notifications_get_notification( $id );
}

/**
 * Get notifications for a specific user
 *
 * @since BuddyPress (1.0)
 * @global BuddyPress $bp
 * @param int $user_id
 * @param string $format
 * @return boolean Object or array on success, false on fail
 */
function bp_core_get_notifications_for_user( $user_id, $format = 'simple' ) {

	// Bail if notifications is not active
	if ( ! bp_is_active( 'notifications' ) ) {
		return false;
	}

	$renderable = bp_notifications_get_notifications_for_user( $user_id, $format );

	return apply_filters( 'bp_core_get_notifications_for_user', $renderable, $user_id, $format );
}

/**
 * Delete notifications for a user by type
 *
 * Used when clearing out notifications for a specific component when the user
 * has visited that component.
 *
 * @since BuddyPress (1.0)
 * @param int $user_id
 * @param string $component_name
 * @param string $component_action
 * @return boolean True on success, false on fail
 */
function bp_core_delete_notifications_by_type( $user_id, $component_name, $component_action ) {

	// Bail if notifications is not active
	if ( ! bp_is_active( 'notifications' ) ) {
		return false;
	}

	return bp_notifications_delete_notifications_by_type( $user_id, $component_name, $component_action );
}

/**
 * Delete notifications for an item ID
 *
 * Used when clearing out notifications for a specific component when the user
 * has visited that component.
 *
 * @since BuddyPress (1.0)
 * @param int $user_id
 * @param string $component_name
 * @param string $component_action
 * @return boolean True on success, false on fail
 */
function bp_core_delete_notifications_by_item_id( $user_id, $item_id, $component_name, $component_action, $secondary_item_id = false ) {

	// Bail if notifications is not active
	if ( ! bp_is_active( 'notifications' ) ) {
		return false;
	}

	return bp_notifications_delete_notifications_by_item_id( $user_id, $item_id, $component_name, $component_action, $secondary_item_id );
}

/**
 * Delete all notifications for by type
 *
 * Used when clearing out notifications for an entire component
 *
 * @since BuddyPress (1.0)
 * @param int $user_id
 * @param string $component_name
 * @param string $component_action
 * @return boolean True on success, false on fail
 */
function bp_core_delete_all_notifications_by_type( $item_id, $component_name, $component_action = false, $secondary_item_id = false ) {

	// Bail if notifications is not active
	if ( ! bp_is_active( 'notifications' ) ) {
		return false;
	}

	bp_notifications_delete_all_notifications_by_type( $item_id, $component_name, $component_action, $secondary_item_id );
}

/**
 * Delete all notifications for a user
 *
 * Used when clearing out all notifications for a user, whene deleted or spammed
 *
 * @since BuddyPress (1.0)
 * @param int $user_id
 * @param string $component_name
 * @param string $component_action
 * @return boolean True on success, false on fail
 */
function bp_core_delete_notifications_from_user( $user_id, $component_name, $component_action ) {

	// Bail if notifications is not active
	if ( ! bp_is_active( 'notifications' ) ) {
		return false;
	}

	return bp_notifications_delete_notifications_from_user( $user_id, $component_name, $component_action );
}

/**
 * Check if a user has access to a specific notification
 *
 * Used before deleting a notification for a user
 *
 * @since BuddyPress (1.0)
 * @param int $user_id
 * @param int $notification_id
 * @return boolean True on success, false on fail
 */
function bp_core_check_notification_access( $user_id, $notification_id ) {

	// Bail if notifications is not active
	if ( ! bp_is_active( 'notifications' ) ) {
		return false;
	}

	return bp_notifications_check_notification_access( $user_id, $notification_id );
}
