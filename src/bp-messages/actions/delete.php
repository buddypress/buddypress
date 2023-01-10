<?php
/**
 * Messages: Delete action handler.
 *
 * @package BuddyPress
 * @subpackage MessageActions
 * @since 3.0.0
 */

/**
 * Process a request to delete a message.
 */
function messages_action_delete_message() {

	if ( ! bp_is_messages_component() || bp_is_current_action( 'notices' ) || ! bp_is_action_variable( 'delete', 0 ) ) {
		return;
	}

	$thread_id = bp_action_variable( 1 );

	if ( ! $thread_id || ! is_numeric( $thread_id ) || ! messages_check_thread_access( $thread_id ) ) {
		bp_core_redirect( trailingslashit( bp_displayed_user_domain() . bp_get_messages_slug() . '/' . bp_current_action() ) );
	} else {
		if ( ! check_admin_referer( 'messages_delete_thread' ) ) {
			return;
		}

		// Delete message.
		if ( ! messages_delete_thread( $thread_id ) ) {
			bp_core_add_message( __('There was an error deleting that message.', 'buddypress'), 'error' );
		} else {
			bp_core_add_message( __('Message deleted.', 'buddypress') );
		}
		bp_core_redirect( trailingslashit( bp_displayed_user_domain() . bp_get_messages_slug() . '/' . bp_current_action() ) );
	}
}
add_action( 'bp_actions', 'messages_action_delete_message' );
