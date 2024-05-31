<?php
/**
 * Notifications: Bulk-manage action handler.
 *
 * @package BuddyPress
 * @subpackage NotificationsActions
 * @since 3.0.0
 */

/**
 * Handles bulk management (mark as read/unread, delete) of notifications.
 *
 * @since 2.2.0
 */
function bp_notifications_action_bulk_manage() {

	// Bail if not the read or unread screen.
	if ( ! bp_is_notifications_component() || ! ( bp_is_current_action( 'read' ) || bp_is_current_action( 'unread' ) ) ) {
		return;
	}

	// Get the action.
	$action = !empty( $_POST['notification_bulk_action'] ) ? $_POST['notification_bulk_action'] : '';
	$nonce  = !empty( $_POST['notifications_bulk_nonce'] ) ? $_POST['notifications_bulk_nonce'] : '';
	$notifications = !empty( $_POST['notifications'] ) ? $_POST['notifications'] : '';

	// Bail if no action or no IDs.
	if ( ( ! in_array( $action, array( 'delete', 'read', 'unread' ), true ) ) || empty( $notifications ) || empty( $nonce ) ) {
		return;
	}

	// Check the nonce.
	if ( ! wp_verify_nonce( $nonce, 'notifications_bulk_nonce' ) ) {
		bp_core_add_message( __( 'There was a problem managing your notifications.', 'buddypress' ), 'error' );
		return;
	}

	$notifications = wp_parse_id_list( $notifications );

	// Delete, mark as read or unread depending on the user 'action'.
	switch ( $action ) {
		case 'delete':
			bp_notifications_delete_notifications_by_ids( $notifications );
			bp_core_add_message( __( 'Notifications deleted.', 'buddypress' ) );
			break;

		case 'read':
			bp_notifications_mark_notifications_by_ids( $notifications, false );
			bp_core_add_message( __( 'Notifications marked as read', 'buddypress' ) );
			break;

		case 'unread':
			bp_notifications_mark_notifications_by_ids( $notifications, true );
			bp_core_add_message( __( 'Notifications marked as unread.', 'buddypress' ) );
			break;
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
add_action( 'bp_actions', 'bp_notifications_action_bulk_manage' );
