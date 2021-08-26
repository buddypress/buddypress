<?php
/**
 * Messages: Read action handler.
 *
 * @package BuddyPress
 * @subpackage MessageActions
 * @since 3.0.0
 */

/**
 * Handle marking a single message thread as read.
 *
 * @since 2.2.0
 *
 * @return bool Returns false on failure. Otherwise redirects back to the
 *              message box URL.
 */
function bp_messages_action_mark_read() {

	if ( ! bp_is_messages_component() || bp_is_current_action( 'notices' ) || ! bp_is_action_variable( 'read', 0 ) ) {
		return false;
	}

	$action = ! empty( $_GET['action'] ) ? $_GET['action'] : '';
	$nonce  = ! empty( $_GET['_wpnonce'] ) ? $_GET['_wpnonce'] : '';
	$id     = ! empty( $_GET['message_id'] ) ? intval( $_GET['message_id'] ) : '';

	// Bail if no action or no ID.
	if ( 'read' !== $action || empty( $id ) || empty( $nonce ) ) {
		return false;
	}

	// Check the nonce.
	if ( ! bp_verify_nonce_request( 'bp_message_thread_mark_read_' . $id ) ) {
		return false;
	}

	// Check access to the message and mark as read.
	if ( messages_check_thread_access( $id ) || bp_current_user_can( 'bp_moderate' ) ) {
		messages_mark_thread_read( $id );
		bp_core_add_message( __( 'Message marked as read.', 'buddypress' ) );
	} else {
		bp_core_add_message( __( 'There was a problem marking that message.', 'buddypress' ), 'error' );
	}

	// Redirect back to the message box.
	bp_core_redirect( bp_displayed_user_domain() . bp_get_messages_slug() . '/' . bp_current_action() );
}
add_action( 'bp_actions', 'bp_messages_action_mark_read' );
