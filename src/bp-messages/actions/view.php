<?php
/**
 * Messages: View action handler.
 *
 * @package BuddyPress
 * @subpackage MessageActions
 * @since 3.0.0
 */

/**
 * Process a request to view a single message thread.
 *
 * @return bool False if not a single conversation.
 */
function messages_action_conversation() {

	// Bail if not viewing a single conversation.
	if ( ! bp_is_messages_component() || ! bp_is_current_action( 'view' ) ) {
		return false;
	}

	// Get the thread ID from the action variable.
	$thread_id = (int) bp_action_variable( 0 );

	if ( ! messages_is_valid_thread( $thread_id ) || ( ! messages_check_thread_access( $thread_id ) && ! bp_current_user_can( 'bp_moderate' ) ) ) {
		return;
	}

	// Check if a new reply has been submitted.
	if ( isset( $_POST['send'] ) ) {

		// Check the nonce.
		check_admin_referer( 'messages_send_message', 'send_message_nonce' );

		$new_reply = messages_new_message( array(
			'thread_id' => $thread_id,
			'subject'   => ! empty( $_POST['subject'] ) ? $_POST['subject'] : false,
			'content'   => $_POST['content']
		) );

		// Send the reply.
		if ( ! empty( $new_reply ) ) {
			bp_core_add_message( __( 'Your reply was sent successfully', 'buddypress' ) );
		} else {
			bp_core_add_message( __( 'There was a problem sending your reply. Please try again.', 'buddypress' ), 'error' );
		}

		$path_chunks = bp_members_get_path_chunks( array( bp_get_messages_slug(), 'view', $thread_id ) );
		$redirect    = bp_displayed_user_url( $path_chunks );

		bp_core_redirect( $redirect );
	}

	/*
	 * Mark message read, but only run on the logged-in user's profile.
	 * If an admin visits a thread, it shouldn't change the read status.
	 */
	if ( bp_is_my_profile() ) {
		// This is marking the messages as read inside the BP Messages component's recipient table.
		messages_mark_thread_read( $thread_id );
	}

	/**
	 * Fires after processing a view request for a single message thread.
	 *
	 * @since 1.7.0
	 */
	do_action( 'messages_action_conversation' );
}
add_action( 'bp_actions', 'messages_action_conversation' );
