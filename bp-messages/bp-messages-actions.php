<?php
/*******************************************************************************
 * Action functions are exactly the same as screen functions, however they do not
 * have a template screen associated with them. Usually they will send the user
 * back to the default screen after execution.
 */

// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

function messages_action_view_message() {
	global $thread_id, $bp;

	if ( !bp_is_messages_component() || !bp_is_current_action( 'view' ) )
		return false;

	$thread_id = (int)bp_action_variable( 0 );

	if ( !$thread_id || !messages_is_valid_thread( $thread_id ) || ( !messages_check_thread_access( $thread_id ) && !is_super_admin() ) )
		bp_core_redirect( bp_displayed_user_domain() . bp_get_messages_slug() );

	// Check if a new reply has been submitted
	if ( isset( $_POST['send'] ) ) {

		// Check the nonce
		check_admin_referer( 'messages_send_message', 'send_message_nonce' );

		// Send the reply
		if ( messages_new_message( array( 'thread_id' => $thread_id, 'subject' => $_POST['subject'], 'content' => $_POST['content'] ) ) )
			bp_core_add_message( __( 'Your reply was sent successfully', 'buddypress' ) );
		else
			bp_core_add_message( __( 'There was a problem sending your reply, please try again', 'buddypress' ), 'error' );

		bp_core_redirect( bp_displayed_user_domain() . bp_get_messages_slug() . '/view/' . $thread_id . '/' );
	}

	// Mark message read
	messages_mark_thread_read( $thread_id );

	// Decrease the unread count in the nav before it's rendered
	$name = sprintf( __( 'Messages <span>%s</span>', 'buddypress' ), bp_get_total_unread_messages_count() );

	$bp->bp_nav[$bp->messages->slug]['name'] = $name;

	do_action( 'messages_action_view_message' );

	bp_core_new_subnav_item( array(
		'name'            => sprintf( __( 'From: %s', 'buddypress' ), BP_Messages_Thread::get_last_sender( $thread_id ) ),
		'slug'            => 'view',
		'parent_url'      => trailingslashit( bp_displayed_user_domain() . bp_get_messages_slug() ),
		'parent_slug'     => bp_get_messages_slug(),
		'screen_function' => true,
		'position'        => 40,
		'user_has_access' => bp_is_my_profile(),
		'link'            => bp_displayed_user_domain() . bp_get_messages_slug() . '/view/' . (int) $thread_id
	) );

	bp_core_load_template( apply_filters( 'messages_template_view_message', 'members/single/home' ) );
}
add_action( 'bp_actions', 'messages_action_view_message' );

function messages_action_delete_message() {
	global $thread_id;

	if ( !bp_is_messages_component() || bp_is_current_action( 'notices' ) || !bp_is_action_variable( 'delete', 0 ) )
		return false;

	$thread_id = bp_action_variable( 1 );

	if ( !$thread_id || !is_numeric( $thread_id ) || !messages_check_thread_access( $thread_id ) ) {
		bp_core_redirect( bp_displayed_user_domain() . bp_get_messages_slug() . '/' . bp_current_action() );
	} else {
		if ( !check_admin_referer( 'messages_delete_thread' ) )
			return false;

		// Delete message
		if ( !messages_delete_thread( $thread_id ) ) {
			bp_core_add_message( __('There was an error deleting that message.', 'buddypress'), 'error' );
		} else {
			bp_core_add_message( __('Message deleted.', 'buddypress') );
		}
		bp_core_redirect( bp_displayed_user_domain() . bp_get_messages_slug() . '/' . bp_current_action() );
	}
}
add_action( 'bp_actions', 'messages_action_delete_message' );

function messages_action_bulk_delete() {
	global $thread_ids;

	if ( !bp_is_messages_component() || !bp_is_action_variable( 'bulk-delete', 0 ) )
		return false;

	$thread_ids = $_POST['thread_ids'];

	if ( !$thread_ids || !messages_check_thread_access( $thread_ids ) ) {
		bp_core_redirect( bp_displayed_user_domain() . bp_get_messages_slug() . '/' . bp_current_action() );
	} else {
		if ( !check_admin_referer( 'messages_delete_thread' ) )
			return false;

		if ( !messages_delete_thread( $thread_ids ) )
			bp_core_add_message( __('There was an error deleting messages.', 'buddypress'), 'error' );
		else
			bp_core_add_message( __('Messages deleted.', 'buddypress') );

		bp_core_redirect( bp_displayed_user_domain() . bp_get_messages_slug() . '/' . bp_current_action() );
	}
}
add_action( 'bp_actions', 'messages_action_bulk_delete' );
?>