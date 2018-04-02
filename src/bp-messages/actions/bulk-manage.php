<?php
/**
 * Messages: Bulk-manage action handler
 *
 * @package BuddyPress
 * @subpackage MessageActions
 * @since 3.0.0
 */

/**
 * Handle bulk management (mark as read/unread, delete) of message threads.
 *
 * @since 2.2.0
 *
 * @return bool Returns false on failure. Otherwise redirects back to the
 *              message box URL.
 */
function bp_messages_action_bulk_manage() {

	if ( ! bp_is_messages_component() || bp_is_current_action( 'notices' ) || ! bp_is_action_variable( 'bulk-manage', 0 ) ) {
		return false;
	}

	$action   = ! empty( $_POST['messages_bulk_action'] ) ? $_POST['messages_bulk_action'] : '';
	$nonce    = ! empty( $_POST['messages_bulk_nonce'] ) ? $_POST['messages_bulk_nonce'] : '';
	$messages = ! empty( $_POST['message_ids'] ) ? $_POST['message_ids'] : '';

	$messages = wp_parse_id_list( $messages );

	// Bail if no action or no IDs.
	if ( ( ! in_array( $action, array( 'delete', 'read', 'unread' ) ) ) || empty( $messages ) || empty( $nonce ) ) {
		bp_core_redirect( bp_displayed_user_domain() . bp_get_messages_slug() . '/' . bp_current_action() . '/' );
	}

	// Check the nonce.
	if ( ! wp_verify_nonce( $nonce, 'messages_bulk_nonce' ) ) {
		return false;
	}

	// Make sure the user has access to all notifications before managing them.
	foreach ( $messages as $message ) {
		if ( ! messages_check_thread_access( $message ) && ! bp_current_user_can( 'bp_moderate' ) ) {
			bp_core_add_message( __( 'There was a problem managing your messages.', 'buddypress' ), 'error' );
			bp_core_redirect( bp_displayed_user_domain() . bp_get_messages_slug() . '/' . bp_current_action() . '/' );
		}
	}

	// Delete, mark as read or unread depending on the user 'action'.
	switch ( $action ) {
		case 'delete' :
			foreach ( $messages as $message ) {
				messages_delete_thread( $message );
			}
			bp_core_add_message( __( 'Messages deleted.', 'buddypress' ) );
		break;

		case 'read' :
			foreach ( $messages as $message ) {
				messages_mark_thread_read( $message );
			}
			bp_core_add_message( __( 'Messages marked as read', 'buddypress' ) );
		break;

		case 'unread' :
			foreach ( $messages as $message ) {
				messages_mark_thread_unread( $message );
			}
			bp_core_add_message( __( 'Messages marked as unread.', 'buddypress' ) );
		break;
	}

	// Redirect back to message box.
	bp_core_redirect( bp_displayed_user_domain() . bp_get_messages_slug() . '/' . bp_current_action() . '/' );
}
add_action( 'bp_actions', 'bp_messages_action_bulk_manage' );