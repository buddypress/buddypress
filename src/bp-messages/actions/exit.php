<?php
/**
 * Messages: Exit action handler.
 *
 * @package BuddyPress
 * @subpackage MessageActions
 * @since 10.0.0
 */

/**
 * Process a request to exit a messages thread.
 *
 * @since 10.0.0
 */
function bp_messages_action_exit_thread() {

	if ( ! bp_is_messages_component() || bp_is_current_action( 'notices' ) || ! bp_is_action_variable( 'exit', 0 ) ) {
		return false;
	}

	$thread_id   = bp_action_variable( 1 );
	$path_chunks = bp_members_get_path_chunks( array( bp_get_messages_slug(), bp_current_action() ) );
	$redirect    = bp_displayed_user_url( $path_chunks );

	if ( ! $thread_id || ! is_numeric( $thread_id ) || ! messages_check_thread_access( $thread_id ) ) {
		bp_core_redirect( $redirect );
	} else {
		if ( ! check_admin_referer( 'bp_messages_exit_thread' ) ) {
			return false;
		}

		// Exit message.
		if ( ! bp_messages_exit_thread( $thread_id ) ) {
			bp_core_add_message( __('There was an error exiting the conversation.', 'buddypress'), 'error' );
		} else {
			bp_core_add_message( __('You have left the message thread.', 'buddypress') );
		}

		bp_core_redirect( $redirect );
	}
}
add_action( 'bp_actions', 'bp_messages_action_exit_thread' );
