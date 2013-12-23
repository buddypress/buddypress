<?php

/**
 * BuddyPress Member Notifications Functions.
 *
 * Functions and filters used in the Notifications component.
 *
 * @package BuddyPress
 * @subpackage NotificationsFunctions
 */

// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

/**
 * Add a notification for a specific user, from a specific component.
 *
 * @since BuddyPress (1.9.0)
 *
 * @param array $args {
 *     Array of arguments describing the notification. All are optional.
 *     @type int $user_id ID of the user to associate the notificiton with.
 *     @type int $item_id ID of the item to associate the notification with.
 *     @type int $secondary_item_id ID of the secondary item to associate the
 *           notification with.
 *     @type string $component_name Name of the component to associate the
 *           notification with.
 *     @type string $component_action Name of the action to associate the
 *           notification with.
 *     @type string $date_notified Timestamp for the notification.
 * }
 * @return int|bool ID of the newly created notification on success, false
 *         on failure.
 */
function bp_notifications_add_notification( $args = array() ) {

	$r = wp_parse_args( $args, array(
		'user_id'           => 0,
		'item_id'           => 0,
		'secondary_item_id' => 0,
		'component_name'    => '',
		'component_action'  => '',
		'date_notified'     => bp_core_current_time(),
		'is_new'            => 1,
	) );

	// Setup the new notification
	$notification                    = new BP_Notifications_Notification;
	$notification->user_id           = $r['user_id'];
	$notification->item_id           = $r['item_id'];
	$notification->secondary_item_id = $r['secondary_item_id'];
	$notification->component_name    = $r['component_name'];
	$notification->component_action  = $r['component_action'];
	$notification->date_notified     = $r['date_notified'];
	$notification->is_new            = $r['is_new'];

	// Save the new notification
	return $notification->save();
}

/**
 * Get a specific notification by its ID.
 *
 * @since BuddyPress (1.9.0)
 *
 * @param int $id ID of the notification.
 * @return BP_Notifications_Notification
 */
function bp_notifications_get_notification( $id ) {
	return new BP_Notifications_Notification( $id );
}

/**
 * Delete a specific notification by its ID.
 *
 * @since BuddyPress (1.9.0)
 *
 * @param int $id ID of the notification to delete.
 * @return bool True on success, false on failure.
 */
function bp_notifications_delete_notification( $id ) {
	if ( ! bp_notifications_check_notification_access( bp_loggedin_user_id(), $id ) ) {
		return false;
	}

	return BP_Notifications_Notification::delete( array( 'id' => $id ) );
}

/**
 * Mark notification read/unread for a user by ID.
 *
 * Used when clearing out notifications for a specific notification item.
 *
 * @since BuddyPress (1.9.0)
 *
 * @param int $user_id ID of the user whose notifications are being deleted.
 * @param int $is_new 0 for read, 1 for unread
 * @return bool True on success, false on failure.
 */
function bp_notifications_mark_notification( $id, $is_new = false ) {
	if ( ! bp_notifications_check_notification_access( bp_loggedin_user_id(), $id ) ) {
		return false;
	}

	return BP_Notifications_Notification::update(
		array( 'is_new' => $is_new ),
		array( 'id'     => $id     )
	);
}

/**
 * Get notifications for a specific user.
 *
 * @since BuddyPress (1.9.0)
 *
 * @param int $user_id ID of the user whose notification are being fetched.
 * @param string $format Format of the returned values. 'simple' returns HTML,
 *        while 'object' returns a structured object for parsing.
 * @return mixed Object or array on success, false on failure.
 */
function bp_notifications_get_notifications_for_user( $user_id, $format = 'simple' ) {

	// Setup local variables
	$bp                    = buddypress();
	$notifications         = BP_Notifications_Notification::get( array(
		'user_id' => $user_id
	) );
	$grouped_notifications = array(); // Notification groups
	$renderable            = array(); // Renderable notifications

	// Group notifications by component and component_action and provide totals
	for ( $i = 0, $count = count( $notifications ); $i < $count; ++$i ) {
		$notification = $notifications[$i];
		$grouped_notifications[$notification->component_name][$notification->component_action][] = $notification;
	}

	// Bail if no notification groups
	if ( empty( $grouped_notifications ) ) {
		return false;
	}

	// Calculate a renderable output for each notification type
	foreach ( $grouped_notifications as $component_name => $action_arrays ) {

		// Skip if group is empty
		if ( empty( $action_arrays ) ) {
			continue;
		}

		// Loop through each actionable item and try to map it to a component
		foreach ( (array) $action_arrays as $component_action_name => $component_action_items ) {

			// Get the number of actionable items
			$action_item_count = count( $component_action_items );

			// Skip if the count is less than 1
			if ( $action_item_count < 1 ) {
				continue;
			}

			// Callback function exists
			if ( isset( $bp->{$component_name}->notification_callback ) && is_callable( $bp->{$component_name}->notification_callback ) ) {

				// Function should return an object
				if ( 'object' === $format ) {

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

			// Allow non BuddyPress components to hook in
			} else {

				// The array to reference with apply_filters_ref_array()
				$ref_array = array(
					$component_action_name,
					$component_action_items[0]->item_id,
					$component_action_items[0]->secondary_item_id,
					$action_item_count,
					$format
				);

				// Function should return an object
				if ( 'object' === $format ) {

					// Retrieve the content of the notification using the callback
					$content = apply_filters_ref_array( 'bp_notifications_get_notifications_for_user', $ref_array );

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
					$renderable[] = apply_filters_ref_array( 'bp_notifications_get_notifications_for_user', $ref_array );
				}
			}
		}
	}

	// If renderable is empty array, set to false
	if ( empty( $renderable ) ) {
		$renderable = false;
	}

	// Filter and return
	return apply_filters( 'bp_core_get_notifications_for_user', $renderable, $user_id, $format );
}

/** Delete ********************************************************************/

/**
 * Delete notifications for a user by type.
 *
 * Used when clearing out notifications for a specific component when the user
 * has visited that component.
 *
 * @since BuddyPress (1.9.0)
 *
 * @param int $user_id ID of the user whose notifications are being deleted.
 * @param string $component_name Name of the associated component.
 * @param string $component_action Name of the associated action.
 * @return bool True on success, false on failure.
 */
function bp_notifications_delete_notifications_by_type( $user_id, $component_name, $component_action ) {
	return BP_Notifications_Notification::delete( array(
		'user_id'          => $user_id,
		'component_name'   => $component_name,
		'component_action' => $component_action,
	) );
}

/**
 * Delete notifications for an item ID.
 *
 * Used when clearing out notifications for a specific component when the user
 * has visited that component.
 *
 * @since BuddyPress (1.9.0)
 *
 * @param int $user_id ID of the user whose notifications are being deleted.
 * @param int $item_id ID of the associated item.
 * @param string $component_name Name of the associated component.
 * @param string $component_action Name of the associated action.
 * @param int $secondary_item_id ID of the secondary associated item.
 * @return bool True on success, false on failure.
 */
function bp_notifications_delete_notifications_by_item_id( $user_id, $item_id, $component_name, $component_action, $secondary_item_id = false ) {
	return BP_Notifications_Notification::delete( array(
		'user_id'           => $user_id,
		'item_id'           => $item_id,
		'secondary_item_id' => $secondary_item_id,
		'component_name'    => $component_name,
		'component_action'  => $component_action,
	) );
}

/**
 * Delete all notifications by type.
 *
 * Used when clearing out notifications for an entire component.
 *
 * @since BuddyPress (1.9.0)
 *
 * @param int $user_id ID of the user whose notifications are being deleted.
 * @param string $component_name Name of the associated component.
 * @param string $component_action Optional. Name of the associated action.
 * @param int $secondary_item_id Optional. ID of the secondary associated item.
 * @return bool True on success, false on failure.
 */
function bp_notifications_delete_all_notifications_by_type( $item_id, $component_name, $component_action = false, $secondary_item_id = false ) {
	return BP_Notifications_Notification::delete( array(
		'item_id'           => $item_id,
		'secondary_item_id' => $secondary_item_id,
		'component_name'    => $component_name,
		'component_action'  => $component_action,
	) );
}

/**
 * Delete all notifications from a user.
 *
 * Used when clearing out all notifications for a user, when deleted or spammed.
 *
 * @todo This function assumes that items with the user_id in the item_id slot
 *       are associated with that user. However, this will only be true with
 *       certain components (such as Friends). Use with caution!
 *
 * @since BuddyPress (1.9.0)
 *
 * @param int $user_id ID of the user whose associated items are beind deleted.
 * @param string $component_name Name of the associated component.
 * @param string $component_action Name of the associated action.
 * @return bool True on success, false on failure.
 */
function bp_notifications_delete_notifications_from_user( $user_id, $component_name, $component_action ) {
	return BP_Notifications_Notification::delete( array(
		'item_id'           => $user_id,
		'component_name'    => $component_name,
		'component_action'  => $component_action,
	) );
}

/** Mark **********************************************************************/

/**
 * Mark notifications read/unread for a user by type.
 *
 * Used when clearing out notifications for a specific component when the user
 * has visited that component.
 *
 * @since BuddyPress (1.9.0)
 *
 * @param int $user_id ID of the user whose notifications are being deleted.
 * @param string $component_name Name of the associated component.
 * @param string $component_action Name of the associated action.
 * @param int $is_new 0 for read, 1 for unread
 * @return bool True on success, false on failure.
 */
function bp_notifications_mark_notifications_by_type( $user_id, $component_name, $component_action, $is_new = false ) {
	return BP_Notifications_Notification::update(
		array(
			'is_new' => $is_new
		),
		array(
			'user_id'          => $user_id,
			'component_name'   => $component_name,
			'component_action' => $component_action
		)
	);
}

/**
 * Mark notifications read/unread for an item ID.
 *
 * Used when clearing out notifications for a specific component when the user
 * has visited that component.
 *
 * @since BuddyPress (1.9.0)
 *
 * @param int $user_id ID of the user whose notifications are being deleted.
 * @param int $item_id ID of the associated item.
 * @param string $component_name Name of the associated component.
 * @param string $component_action Name of the associated action.
 * @param int $secondary_item_id ID of the secondary associated item.
 * @param int $is_new 0 for read, 1 for unread
 * @return bool True on success, false on failure.
 */
function bp_notifications_mark_notifications_by_item_id( $user_id, $item_id, $component_name, $component_action, $secondary_item_id = false, $is_new = false ) {
	return BP_Notifications_Notification::update(
		array(
			'is_new' => $is_new
		),
		array(
			'user_id'           => $user_id,
			'item_id'           => $item_id,
			'secondary_item_id' => $secondary_item_id,
			'component_name'    => $component_name,
			'component_action'  => $component_action
		)
	);
}

/**
 * Mark all notifications read/unread by type.
 *
 * Used when clearing out notifications for an entire component.
 *
 * @since BuddyPress (1.9.0)
 *
 * @param int $user_id ID of the user whose notifications are being deleted.
 * @param int $is_new 0 for read, 1 for unread
 * @param string $component_name Name of the associated component.
 * @param string $component_action Optional. Name of the associated action.
 * @param int $secondary_item_id Optional. ID of the secondary associated item.
 * @param int $is_new 0 for read, 1 for unread
 * @return bool True on success, false on failure.
 */
function bp_notifications_mark_all_notifications_by_type( $item_id, $component_name, $component_action = false, $secondary_item_id = false, $is_new = false ) {
	return BP_Notifications_Notification::update(
		array(
			'is_new' => $is_new
		),
		array(
			'item_id'           => $item_id,
			'secondary_item_id' => $secondary_item_id,
			'component_name'    => $component_name,
			'component_action'  => $component_action
		)
	);
}

/**
 * Mark all notifications read/unread from a user.
 *
 * Used when clearing out all notifications for a user, when deleted or spammed.
 *
 * @todo This function assumes that items with the user_id in the item_id slot
 *       are associated with that user. However, this will only be true with
 *       certain components (such as Friends). Use with caution!
 *
 * @since BuddyPress (1.9.0)
 *
 * @param int $user_id ID of the user whose associated items are beind deleted.
 * @param int $is_new 0 for read, 1 for unread
 * @param string $component_name Name of the associated component.
 * @param string $component_action Name of the associated action.
 * @param int $is_new 0 for read, 1 for unread
 * @return bool True on success, false on failure.
 */
function bp_notifications_mark_notifications_from_user( $user_id, $component_name, $component_action, $is_new = false ) {
	return BP_Notifications_Notification::update(
		array(
			'is_new' => $is_new
		),
		array(
			'item_id'          => $user_id,
			'component_name'   => $component_name,
			'component_action' => $component_action
		)
	);
}

/** Helpers *******************************************************************/

/**
 * Check if a user has access to a specific notification.
 *
 * Used before deleting a notification for a user.
 *
 * @since BuddyPress (1.9.0)
 *
 * @param int $user_id ID of the user being checked.
 * @param int $notification_id ID of the notification being checked.
 * @return bool True if the notification belongs to the user, otherwise false.
 */
function bp_notifications_check_notification_access( $user_id, $notification_id ) {
	return (bool) BP_Notifications_Notification::check_access( $user_id, $notification_id );
}

/**
 * Get a count of unread notification items for a user.
 *
 * @since BuddyPress (1.9.0)
 *
 * @param int $user_id ID of the user whose unread notifications are being
 *        counted.
 * @return int Unread notification count.
 */
function bp_notifications_get_unread_notification_count( $user_id = 0 ) {

	// Default to displayed user if no ID is passed
	if ( empty( $user_id ) ) {
		$user_id = ( bp_displayed_user_id() ) ? bp_displayed_user_id() : bp_loggedin_user_id();
	}

	// Get the notifications, and count them
	$notifications = BP_Notifications_Notification::get( array(
		'user_id' => $user_id,
	) );

	$count = ! empty( $notifications ) ? count( $notifications ) : 0;

	return apply_filters( 'bp_notifications_get_total_notification_count', $count );
}

/**
 * Return an array of component names that are currently active and have
 * registered Notifications callbacks.
 *
 * @since BuddyPress (1.9.1)
 *
 * @see http://buddypress.trac.wordpress.org/ticket/5300
 */
function bp_notifications_get_registered_components() {

	// Get the active components
	$active_components = buddypress()->active_components;

	// Get the component ID's from the active_components array
	$component_names   = array_keys( $active_components );

	// Return active components with registered notifications callbacks
	return apply_filters( 'bp_notifications_get_component_names', $component_names );
}
