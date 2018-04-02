<?php
/**
 * Notifications: Bulk-manage action handler
 *
 * @package BuddyPress
 * @subpackage NotificationsActions
 * @since 3.0.0
 */

/**
 * Handles bulk management (mark as read/unread, delete) of notifications.
 *
 * @since 2.2.0
 *
 * @return bool
 */
function bp_notifications_action_bulk_manage() {

	// Bail if not the read or unread screen.
	if ( ! bp_is_notifications_component() || ! ( bp_is_current_action( 'read' ) || bp_is_current_action( 'unread' ) ) ) {
		return false;
	}

	// Get the action.
	$action = !empty( $_POST['notification_bulk_action'] ) ? $_POST['notification_bulk_action'] : '';
	$nonce  = !empty( $_POST['notifications_bulk_nonce'] ) ? $_POST['notifications_bulk_nonce'] : '';
	$notifications = !empty( $_POST['notifications'] ) ? $_POST['notifications'] : '';

	// Bail if no action or no IDs.
	if ( ( ! in_array( $action, array( 'delete', 'read', 'unread' ) ) ) || empty( $notifications ) || empty( $nonce ) ) {
		return false;
	}

	// Check the nonce.
	if ( ! wp_verify_nonce( $nonce, 'notifications_bulk_nonce' ) ) {
		bp_core_add_message( __( 'There was a problem managing your notifications.', 'buddypress' ), 'error' );
		return false;
	}

	$notifications = wp_parse_id_list( $notifications );

	// Delete, mark as read or unread depending on the user 'action'.
	switch ( $action ) {
		case 'delete' :
			foreach ( $notifications as $notification ) {
				bp_notifications_delete_notification( $notification );
			}
			bp_core_add_message( __( 'Notifications deleted.', 'buddypress' ) );
		break;

		case 'read' :
			foreach ( $notifications as $notification ) {
				bp_notifications_mark_notification( $notification, false );
			}
			bp_core_add_message( __( 'Notifications marked as read', 'buddypress' ) );
		break;

		case 'unread' :
			foreach ( $notifications as $notification ) {
				bp_notifications_mark_notification( $notification, true );
			}
			bp_core_add_message( __( 'Notifications marked as unread.', 'buddypress' ) );
		break;
	}

	// Redirect.
	bp_core_redirect( bp_displayed_user_domain() . bp_get_notifications_slug() . '/' . bp_current_action() . '/' );
}
add_action( 'bp_actions', 'bp_notifications_action_bulk_manage' );