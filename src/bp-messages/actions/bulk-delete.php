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
 *
 * @return bool False on failure.
 */
function messages_action_bulk_delete() {

	if ( ! bp_is_messages_component() || ! bp_is_action_variable( 'bulk-delete', 0 ) ) {
		return false;
	}

	$thread_ids  = $_POST['thread_ids'];
	$path_chunks = bp_members_get_path_chunks( array( bp_get_messages_slug(), bp_current_action() ) );
	$redirect    = bp_displayed_user_url( $path_chunks );

	if ( ! $thread_ids || ! messages_check_thread_access( $thread_ids ) ) {
		bp_core_redirect( $redirect );
	} else {
		if ( ! check_admin_referer( 'messages_delete_thread' ) ) {
			return false;
		}

		if ( ! messages_delete_thread( $thread_ids ) ) {
			bp_core_add_message( __( 'There was an error deleting messages.', 'buddypress' ), 'error' );
		} else {
			bp_core_add_message( __( 'Messages deleted.', 'buddypress' ) );
		}

		bp_core_redirect( $redirect );
	}
}
add_action( 'bp_actions', 'messages_action_bulk_delete' );
