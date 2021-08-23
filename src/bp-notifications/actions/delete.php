<?php
/**
 * Notifications: Delete action handler.
 *
 * @package BuddyPress
 * @subpackage NotificationsActions
 * @since 3.0.0
 */

/**
 * Handle deleting single notifications.
 *
 * @since 1.9.0
 *
 * @return bool
 */
function bp_notifications_action_delete() {

	// Bail if not the read or unread screen.
	if ( ! bp_is_notifications_component() || ! ( bp_is_current_action( 'read' ) || bp_is_current_action( 'unread' ) ) ) {
		return false;
	}

	// Get the action.
	$action = ! empty( $_GET['action']          ) ? $_GET['action']          : '';
	$nonce  = ! empty( $_GET['_wpnonce']        ) ? $_GET['_wpnonce']        : '';
	$id     = ! empty( $_GET['notification_id'] ) ? $_GET['notification_id'] : '';

	// Bail if no action or no ID.
	if ( ( 'delete' !== $action ) || empty( $id ) || empty( $nonce ) ) {
		return false;
	}

	// Check the nonce and delete the notification.
	if ( bp_verify_nonce_request( 'bp_notification_delete_' . $id ) && bp_notifications_delete_notification( $id ) ) {
		bp_core_add_message( __( 'Notification successfully deleted.', 'buddypress' ) );
	} else {
		bp_core_add_message( __( 'There was a problem deleting that notification.', 'buddypress' ), 'error' );
	}

	// URL to redirect to.
	if ( bp_is_current_action( 'unread' ) ) {
		$redirect = bp_get_notifications_unread_permalink( bp_displayed_user_id() );
	} elseif ( bp_is_current_action( 'read' ) ) {
		$redirect = bp_get_notifications_read_permalink( bp_displayed_user_id() );
	}

	// Redirect.
	bp_core_redirect( $redirect );
}
add_action( 'bp_actions', 'bp_notifications_action_delete' );
