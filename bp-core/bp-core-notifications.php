<?php

function bp_core_add_notification( $item_id, $user_id, $component_name, $component_action, $secondary_item_id = false, $date_notified = false ) {
	global $bp;

	if ( !$date_notified )
		$date_notified = time();

	$notification = new BP_Core_Notification;
	$notification->item_id = $item_id;
	$notification->user_id = $user_id;
	$notification->component_name = $component_name;
	$notification->component_action = $component_action;
	$notification->date_notified = $date_notified;
	$notification->is_new = 1;

	if ( $secondary_item_id )
		$notification->secondary_item_id = $secondary_item_id;

	if ( !$notification->save() )
		return false;

	return true;
}

function bp_core_delete_notification( $id ) {
	if ( !bp_core_check_notification_access( $bp->loggedin_user->id, $id ) )
		return false;

	return BP_Core_Notification::delete( $id );
}

function bp_core_get_notification( $id ) {
	return new BP_Core_Notification( $id );
}

function bp_core_get_notifications_for_user( $user_id ) {
	global $bp;

	$notifications = BP_Core_Notification::get_all_for_user( $user_id );

	/* Group notifications by component and component_action and provide totals */
	for ( $i = 0; $i < count($notifications); $i++ ) {
		$notification = $notifications[$i];

		$grouped_notifications[$notification->component_name][$notification->component_action][] = $notification;
	}

	if ( !$grouped_notifications )
		return false;

	/* Calculated a renderable outcome for each notification type */
	foreach ( (array)$grouped_notifications as $component_name => $action_arrays ) {
		if ( !$action_arrays )
			continue;

		foreach ( (array)$action_arrays as $component_action_name => $component_action_items ) {
			$action_item_count = count($component_action_items);

			if ( $action_item_count < 1 )
				continue;

			if ( function_exists( $bp->{$component_name}->format_notification_function ) ) {
				$renderable[] = call_user_func( $bp->{$component_name}->format_notification_function, $component_action_name, $component_action_items[0]->item_id, $component_action_items[0]->secondary_item_id, $action_item_count );
			}
		}
	}

	return $renderable;
}

function bp_core_delete_notifications_for_user_by_type( $user_id, $component_name, $component_action ) {
	return BP_Core_Notification::delete_for_user_by_type( $user_id, $component_name, $component_action );
}

function bp_core_delete_notifications_for_user_by_item_id( $user_id, $item_id, $component_name, $component_action, $secondary_item_id = false ) {
	return BP_Core_Notification::delete_for_user_by_item_id( $user_id, $item_id, $component_name, $component_action, $secondary_item_id );
}

function bp_core_delete_all_notifications_by_type( $item_id, $component_name, $component_action = false, $secondary_item_id = false ) {
	return BP_Core_Notification::delete_all_by_type( $item_id, $component_name, $component_action, $secondary_item_id );
}

function bp_core_delete_notifications_from_user( $user_id, $component_name, $component_action ) {
	return BP_Core_Notification::delete_from_user_by_type( $user_id, $component_name, $component_action );
}

function bp_core_check_notification_access( $user_id, $notification_id ) {
	if ( !BP_Core_Notification::check_access( $user_id, $notification_id ) )
		return false;

	return true;
}

?>