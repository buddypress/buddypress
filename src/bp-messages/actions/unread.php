<?php
/**
 * Messages: Unread action handler
 *
 * @package BuddyPress
 * @subpackage MessageActions
 * @since 3.0.0
 */

/**
 * Handle marking a single message thread as unread.
 *
 * @since 2.2.0
 *
 * @return false|null Returns false on failure. Otherwise redirects back to the
 *                   message box URL.
 */
function bp_messages_action_mark_unread() {

	if ( ! bp_is_messages_component() || bp_is_current_action( 'notices' ) || ! bp_is_action_variable( 'unread', 0 ) ) {
		return false;
	}

	$action = ! empty( $_GET['action'] ) ? $_GET['action'] : '';
	$nonce  = ! empty( $_GET['_wpnonce'] ) ? $_GET['_wpnonce'] : '';
	$id     = ! empty( $_GET['message_id'] ) ? intval( $_GET['message_id'] ) : '';

	// Bail if no action or no ID.
	if ( 'unread' !== $action || empty( $id ) || empty( $nonce ) ) {
		return false;
	}

	// Check the nonce.
	if ( ! bp_verify_nonce_request( 'bp_message_thread_mark_unread_' . $id ) ) {
		return false;
	}

	// Check access to the message and mark unread.
	if ( messages_check_thread_access( $id ) || bp_current_user_can( 'bp_moderate' ) ) {
		messages_mark_thread_unread( $id );
		bp_core_add_message( __( 'Message marked unread.', 'buddypress' ) );
	} else {
		bp_core_add_message( __( 'There was a problem marking that message.', 'buddypress' ), 'error' );
	}

	// Redirect back to the message box URL.
	bp_core_redirect( bp_displayed_user_domain() . bp_get_messages_slug() . '/' . bp_current_action() );
}
add_action( 'bp_actions', 'bp_messages_action_mark_unread' );