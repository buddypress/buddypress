<?php

/**
 * BuddyPress Messages Actions
 *
 * Action functions are exactly the same as screen functions, however they do not
 * have a template screen associated with them. Usually they will send the user
 * back to the default screen after execution.
 *
 * @package BuddyPress
 * @subpackage MessagesActions
 */

// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

function messages_action_conversation() {

	if ( !bp_is_messages_component() || !bp_is_current_action( 'view' ) )
		return false;

	$thread_id = (int)bp_action_variable( 0 );

	if ( !$thread_id || !messages_is_valid_thread( $thread_id ) || ( !messages_check_thread_access( $thread_id ) && !bp_current_user_can( 'bp_moderate' ) ) )
		bp_core_redirect( trailingslashit( bp_displayed_user_domain() . bp_get_messages_slug() ) );

	// Check if a new reply has been submitted
	if ( isset( $_POST['send'] ) ) {

		// Check the nonce
		check_admin_referer( 'messages_send_message', 'send_message_nonce' );

		// Send the reply
		if ( messages_new_message( array( 'thread_id' => $thread_id, 'subject' => ! empty( $_POST['subject'] ) ? $_POST['subject'] : false, 'content' => $_POST['content'] ) ) ) {
			bp_core_add_message( __( 'Your reply was sent successfully', 'buddypress' ) );
		} else {
			bp_core_add_message( __( 'There was a problem sending your reply, please try again', 'buddypress' ), 'error' );
		}

		bp_core_redirect( bp_displayed_user_domain() . bp_get_messages_slug() . '/view/' . $thread_id . '/' );
	}

	// Mark message read
	messages_mark_thread_read( $thread_id );

	do_action( 'messages_action_conversation' );
}
add_action( 'bp_actions', 'messages_action_conversation' );

function messages_action_delete_message() {

	if ( !bp_is_messages_component() || bp_is_current_action( 'notices' ) || !bp_is_action_variable( 'delete', 0 ) )
		return false;

	$thread_id = bp_action_variable( 1 );

	if ( !$thread_id || !is_numeric( $thread_id ) || !messages_check_thread_access( $thread_id ) ) {
		bp_core_redirect( trailingslashit( bp_displayed_user_domain() . bp_get_messages_slug() . '/' . bp_current_action() ) );
	} else {
		if ( !check_admin_referer( 'messages_delete_thread' ) )
			return false;

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

function messages_action_bulk_delete() {

	if ( !bp_is_messages_component() || !bp_is_action_variable( 'bulk-delete', 0 ) )
		return false;

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
