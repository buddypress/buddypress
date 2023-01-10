<?php
/**
 * Messages: Bulk-delete action handler.
 *
 * @package BuddyPress
 * @subpackage MessageActions
 * @since 3.0.0
 */

/**
 * Process a request to bulk delete messages.
 */
function messages_action_bulk_delete() {

	if ( ! bp_is_messages_component() || ! bp_is_action_variable( 'bulk-delete', 0 ) ) {
		return;
	}

	$thread_ids = $_POST['thread_ids'];

	if ( ! $thread_ids || ! messages_check_thread_access( $thread_ids ) ) {
		bp_core_redirect( trailingslashit( bp_displayed_user_domain() . bp_get_messages_slug() . '/' . bp_current_action() ) );
	} else {
		if ( ! check_admin_referer( 'messages_delete_thread' ) ) {
			return;
		}

		if ( ! messages_delete_thread( $thread_ids ) ) {
			bp_core_add_message( __( 'There was an error deleting messages.', 'buddypress' ), 'error' );
		} else {
			bp_core_add_message( __( 'Messages deleted.', 'buddypress' ) );
		}

		bp_core_redirect( trailingslashit( bp_displayed_user_domain() . bp_get_messages_slug() . '/' . bp_current_action() ) );
	}
}
add_action( 'bp_actions', 'messages_action_bulk_delete' );
