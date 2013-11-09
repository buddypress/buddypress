<?php

/**
 * BuddyPress Notifications Actions
 *
 * Action functions are exactly the same as screen functions, however they do not
 * have a template screen associated with them. Usually they will send the user
 * back to the default screen after execution.
 *
 * @package BuddyPress
 * @subpackage NotificationsActions
 */

// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

/**
 * Handle marking single notifications as read
 *
 * @since BuddyPress (1.9.0)
 *
 * @return boolean
 */
function bp_notifications_action_mark_read() {

	// Bail if not the unread screen
	if ( ! bp_is_notifications_component() || ! bp_is_current_action( 'unread' ) ) {
		return false;
	}

	// Get the action
	$action = !empty( $_GET['action']          ) ? $_GET['action']          : '';
	$nonce  = !empty( $_GET['_wpnonce']        ) ? $_GET['_wpnonce']        : '';
	$id     = !empty( $_GET['notification_id'] ) ? $_GET['notification_id'] : '';

	// Bail if no action or no ID
	if ( ( 'read' !== $action ) || empty( $id ) || empty( $nonce ) ) {
		return false;
	}

	// Check the nonce and mark the notification
	if ( bp_verify_nonce_request( 'bp_notification_mark_read_' . $id ) && bp_notifications_mark_notification( $id, false ) ) {
		bp_core_add_message( __( 'Notification successfully marked read.',         'buddypress' )          );
	} else {
		bp_core_add_message( __( 'There was a problem marking that notification.', 'buddypress' ), 'error' );
	}

	// Redirect
	bp_core_redirect( bp_displayed_user_domain() . bp_get_notifications_slug() . '/unread/' );
}
add_action( 'bp_actions', 'bp_notifications_action_mark_read' );

/**
 * Handle marking single notifications as unread
 *
 * @since BuddyPress (1.9.0)
 *
 * @return boolean
 */
function bp_notifications_action_mark_unread() {

	// Bail if not the unread screen
	if ( ! bp_is_notifications_component() || ! bp_is_current_action( 'read' ) ) {
		return false;
	}

	// Get the action
	$action = !empty( $_GET['action']          ) ? $_GET['action']          : '';
	$nonce  = !empty( $_GET['_wpnonce']        ) ? $_GET['_wpnonce']        : '';
	$id     = !empty( $_GET['notification_id'] ) ? $_GET['notification_id'] : '';

	// Bail if no action or no ID
	if ( ( 'unread' !== $action ) || empty( $id ) || empty( $nonce ) ) {
		return false;
	}

	// Check the nonce and mark the notification
	if ( bp_verify_nonce_request( 'bp_notification_mark_unread_' . $id ) && bp_notifications_mark_notification( $id, true ) ) {
		bp_core_add_message( __( 'Notification successfully marked unread.',       'buddypress' )          );
	} else {
		bp_core_add_message( __( 'There was a problem marking that notification.', 'buddypress' ), 'error' );
	}

	// Redirect
	bp_core_redirect( bp_displayed_user_domain() . bp_get_notifications_slug() . '/read/' );
}
add_action( 'bp_actions', 'bp_notifications_action_mark_unread' );

/**
 * Handle marking single notifications as unread
 *
 * @since BuddyPress (1.9.0)
 *
 * @return boolean
 */
function bp_notifications_action_delete() {

	// Bail if not the unread screen
	if ( ! bp_is_notifications_component() || ! ( bp_is_current_action( 'read' ) || bp_is_current_action( 'unread' ) ) ) {
		return false;
	}

	// Get the action
	$action = !empty( $_GET['action']          ) ? $_GET['action']          : '';
	$nonce  = !empty( $_GET['_wpnonce']        ) ? $_GET['_wpnonce']        : '';
	$id     = !empty( $_GET['notification_id'] ) ? $_GET['notification_id'] : '';

	// Bail if no action or no ID
	if ( ( 'delete' !== $action ) || empty( $id ) || empty( $nonce ) ) {
		return false;
	}

	// Check the nonce and delete the notification
	if ( bp_verify_nonce_request( 'bp_notification_delete_' . $id ) && bp_notifications_delete_notification( $id ) ) {
		bp_core_add_message( __( 'Notification successfully deleted.',              'buddypress' )          );
	} else {
		bp_core_add_message( __( 'There was a problem deleting that notification.', 'buddypress' ), 'error' );
	}

	// Redirect
	bp_core_redirect( bp_displayed_user_domain() . bp_get_notifications_slug() . '/' . bp_current_action() . '/' );
}
add_action( 'bp_actions', 'bp_notifications_action_delete' );
