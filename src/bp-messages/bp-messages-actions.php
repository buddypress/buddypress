<?php

/**
 * BuddyPress Messages Actions
 *
 * Action functions are exactly the same as screen functions, however they do
 * not have a template screen associated with them. Usually they will send the
 * user back to the default screen after execution.
 *
 * @package BuddyPress
 * @subpackage MessagesActions
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * Process a request to view a single message thread.
 */
function messages_action_conversation() {

	// Bail if not viewing a single conversation
	if ( ! bp_is_messages_component() || ! bp_is_current_action( 'view' ) ) {
		return false;
	}

	// Get the thread ID from the action variable
	$thread_id = (int) bp_action_variable( 0 );

	if ( ! messages_is_valid_thread( $thread_id ) || ( ! messages_check_thread_access( $thread_id ) && ! bp_current_user_can( 'bp_moderate' ) ) ) {
		bp_core_redirect( trailingslashit( bp_displayed_user_domain() . bp_get_messages_slug() ) );
	}

	// Check if a new reply has been submitted
	if ( isset( $_POST['send'] ) ) {

		// Check the nonce
		check_admin_referer( 'messages_send_message', 'send_message_nonce' );

		$new_reply = messages_new_message( array(
			'thread_id' => $thread_id,
			'subject'   => ! empty( $_POST['subject'] ) ? $_POST['subject'] : false,
			'content'   => $_POST['content']
		) );

		// Send the reply
		if ( ! empty( $new_reply ) ) {
			bp_core_add_message( __( 'Your reply was sent successfully', 'buddypress' ) );
		} else {
			bp_core_add_message( __( 'There was a problem sending your reply. Please try again.', 'buddypress' ), 'error' );
		}

		bp_core_redirect( bp_displayed_user_domain() . bp_get_messages_slug() . '/view/' . $thread_id . '/' );
	}

	// Mark message read
	messages_mark_thread_read( $thread_id );

	/**
	 * Fires after processing a view request for a single message thread.
	 *
	 * @since BuddyPress (1.7.0)
	 */
	do_action( 'messages_action_conversation' );
}
add_action( 'bp_actions', 'messages_action_conversation' );

/**
 * Process a request to delete a message.
 *
 * @return bool False on failure.
 */
function messages_action_delete_message() {

	if ( ! bp_is_messages_component() || bp_is_current_action( 'notices' ) || ! bp_is_action_variable( 'delete', 0 ) ) {
		return false;
	}

	$thread_id = bp_action_variable( 1 );

	if ( !$thread_id || !is_numeric( $thread_id ) || !messages_check_thread_access( $thread_id ) ) {
		bp_core_redirect( trailingslashit( bp_displayed_user_domain() . bp_get_messages_slug() . '/' . bp_current_action() ) );
	} else {
		if ( ! check_admin_referer( 'messages_delete_thread' ) ) {
			return false;
		}

		// Delete message
		if ( !messages_delete_thread( $thread_id ) ) {
			bp_core_add_message( __('There was an error deleting that message.', 'buddypress'), 'error' );
		} else {
			bp_core_add_message( __('Message deleted.', 'buddypress') );
		}
		bp_core_redirect( trailingslashit( bp_displayed_user_domain() . bp_get_messages_slug() . '/' . bp_current_action() ) );
	}
}
add_action( 'bp_actions', 'messages_action_delete_message' );

/**
 * Handle marking a single message thread as read.
 *
 * @since BuddyPress (2.2.0)
 *
 * @return bool|null Returns false on failure. Otherwise redirects back to the
 *         message box URL.
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
	if ( messages_check_thread_access( $id ) ) {
		messages_mark_thread_read( $id );
		bp_core_add_message( __( 'Message marked as read.', 'buddypress' ) );
	} else {
		bp_core_add_message( __( 'There was a problem marking that message.', 'buddypress' ), 'error' );
	}

	// Redirect back to the message box.
	bp_core_redirect( bp_displayed_user_domain() . bp_get_messages_slug() . '/' . bp_current_action() );
}
add_action( 'bp_actions', 'bp_messages_action_mark_read' );

/**
 * Handle marking a single message thread as unread.
 *
 * @since BuddyPress (2.2.0)
 *
 * @return bool|null Returns false on failure. Otherwise redirects back to the
 *         message box URL.
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
	if ( messages_check_thread_access( $id ) ) {
		messages_mark_thread_unread( $id );
		bp_core_add_message( __( 'Message marked unread.', 'buddypress' ) );
	} else {
		bp_core_add_message( __( 'There was a problem marking that message.', 'buddypress' ), 'error' );
	}

	// Redirect back to the message box URL.
	bp_core_redirect( bp_displayed_user_domain() . bp_get_messages_slug() . '/' . bp_current_action() );
}
add_action( 'bp_actions', 'bp_messages_action_mark_unread' );

/**
 * Handle bulk management (mark as read/unread, delete) of message threads.
 *
 * @since BuddyPress (2.2.0)
 *
 * @return bool Returns false on failure. Otherwise redirects back to the
 *         message box URL.
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
		if ( ! messages_check_thread_access( $message ) ) {
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

/**
 * Process a request to bulk delete messages.
 *
 * @return bool False on failure.
 */
function messages_action_bulk_delete() {

	if ( ! bp_is_messages_component() || ! bp_is_action_variable( 'bulk-delete', 0 ) ) {
		return false;
	}

	$thread_ids = $_POST['thread_ids'];

	if ( !$thread_ids || !messages_check_thread_access( $thread_ids ) ) {
		bp_core_redirect( trailingslashit( bp_displayed_user_domain() . bp_get_messages_slug() . '/' . bp_current_action() ) );
	} else {
		if ( !check_admin_referer( 'messages_delete_thread' ) ) {
			return false;
		}

		if ( !messages_delete_thread( $thread_ids ) ) {
			bp_core_add_message( __('There was an error deleting messages.', 'buddypress'), 'error' );
		} else {
			bp_core_add_message( __('Messages deleted.', 'buddypress') );
		}

		bp_core_redirect( trailingslashit( bp_displayed_user_domain() . bp_get_messages_slug() . '/' . bp_current_action() ) );
	}
}
add_action( 'bp_actions', 'messages_action_bulk_delete' );
