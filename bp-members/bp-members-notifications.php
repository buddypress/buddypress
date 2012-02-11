<?php

/**
 * BuddyPress Member Notifications
 *
 * Functions and filters used for member notification
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

	if ( empty( $date_notified ) )
		$date_notified = bp_core_current_time();

	$notification                   = new BP_Core_Notification;
	$notification->item_id          = $item_id;
	$notification->user_id          = $user_id;
	$notification->component_name   = $component_name;
	$notification->component_action = $component_action;
	$notification->date_notified    = $date_notified;
	$notification->is_new           = 1;

	if ( !empty( $secondary_item_id ) )
		$notification->secondary_item_id = $secondary_item_id;

	if ( $notification->save() )
		return true;

	return false;
}

/**
 * Delete a specific notification by its ID
 *
 * @since BuddyPress (1.0)
 * @param int $id
 * @return boolean True on success, false on fail
 */
function bp_core_delete_notification( $id ) {
	if ( !bp_core_check_notification_access( bp_loggedin_user_id(), $id ) )
		return false;

	return BP_Core_Notification::delete( $id );
}

/**
 * Get a specific notification by its ID
 * 
 * @since BuddyPress (1.0)
 * @param int $id
 * @return BP_Core_Notification 
 */
function bp_core_get_notification( $id ) {
	return new BP_Core_Notification( $id );
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
	global $bp;

	$notifications         = BP_Core_Notification::get_all_for_user( $user_id );
	$grouped_notifications = array(); // Notification groups
	$renderable            = array(); // Renderable notifications

	// Group notifications by component and component_action and provide totals
	for ( $i = 0, $count = count( $notifications ); $i < $count; ++$i ) {
		$notification = $notifications[$i];
		$grouped_notifications[$notification->component_name][$notification->component_action][] = $notification;
	}

	// Bail if no notification groups
	if ( empty( $grouped_notifications ) )
		return false;

	// Calculate a renderable output for each notification type
	foreach ( $grouped_notifications as $component_name => $action_arrays ) {

		// Skip if group is empty
		if ( empty( $action_arrays ) )
			continue;

		// Skip inactive components
		if ( !bp_is_active( $component_name ) )
			continue;

		// Loop through each actionable item and try to map it to a component
		foreach ( (array) $action_arrays as $component_action_name => $component_action_items ) {

			// Get the number of actionable items
			$action_item_count = count( $component_action_items );

			// Skip if the count is less than 1
			if ( $action_item_count < 1 )
				continue;

			// Callback function exists
			if ( isset( $bp->{$component_name}->notification_callback ) && function_exists( $bp->{$component_name}->notification_callback ) ) {

				// Function should return an object
				if ( 'object' == $format ) {

					// Retrieve the content of the notification using the callback
					$content = call_user_func(
						$bp->{$component_name}->notification_callback,
						$component_action_name,
						$component_action_items[0]->item_id,
						$component_action_items[0]->secondary_item_id,
						$action_item_count,
						'array'
					);

					// Create the object to be returned
					$notification_object = new stdClass;

					// Minimal backpat with non-compatible notification
					// callback functions
					if ( is_string( $content ) ) {
						$notification_object->content = $content;
						$notification_object->href    = bp_loggedin_user_domain();
					} else {
						$notification_object->content = $content['text'];
						$notification_object->href    = $content['link'];
					}

					$notification_object->id = $component_action_items[0]->id;
					$renderable[]            = $notification_object;

				// Return an array of content strings
				} else {
					$content      = call_user_func( $bp->{$component_name}->notification_callback, $component_action_name, $component_action_items[0]->item_id, $component_action_items[0]->secondary_item_id, $action_item_count );
					$renderable[] = $content;
				}

			// @deprecated format_notification_function - 1.5
			} elseif ( isset( $bp->{$component_name}->format_notification_function ) && function_exists( $bp->{$component_name}->format_notification_function ) ) {
				$renderable[] = call_user_func( $bp->{$component_name}->format_notification_function, $component_action_name, $component_action_items[0]->item_id, $component_action_items[0]->secondary_item_id, $action_item_count );
			}
		}
	}

	// If renderable is empty array, set to false
	if ( empty( $renderable ) )
		$renderable = false;

	// Filter and return
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
	return BP_Core_Notification::delete_for_user_by_type( $user_id, $component_name, $component_action );
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
	return BP_Core_Notification::delete_for_user_by_item_id( $user_id, $item_id, $component_name, $component_action, $secondary_item_id );
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
	return BP_Core_Notification::delete_all_by_type( $item_id, $component_name, $component_action, $secondary_item_id );
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
	return BP_Core_Notification::delete_from_user_by_type( $user_id, $component_name, $component_action );
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
	if ( !BP_Core_Notification::check_access( $user_id, $notification_id ) )
		return false;

	return true;
}

?>
