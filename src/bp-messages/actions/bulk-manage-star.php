<?php
/**
 * Messages: Bulk-manage star action handler
 *
 * @package BuddyPress
 * @subpackage MessageActions
 * @since 3.0.0
 */

/**
 * Bulk manage handler to set the star status for multiple messages.
 *
 * @since 2.3.0
 */
function bp_messages_star_bulk_manage_handler() {
	if ( empty( $_POST['messages_bulk_nonce' ] ) ) {
		return;
	}

	// Check the nonce.
	if ( ! wp_verify_nonce( $_POST['messages_bulk_nonce'], 'messages_bulk_nonce' ) ) {
		return;
	}

	// Check capability.
	if ( ! is_user_logged_in() || ! bp_core_can_edit_settings() ) {
		return;
	}

	$action  = ! empty( $_POST['messages_bulk_action'] ) ? $_POST['messages_bulk_action'] : '';
	$threads = ! empty( $_POST['message_ids'] ) ? $_POST['message_ids'] : '';
	$threads = wp_parse_id_list( $threads );

	// Bail if action doesn't match our star actions or no IDs.
	if ( false === in_array( $action, array( 'star', 'unstar' ), true ) || empty( $threads ) ) {
		return;
	}

	// It's star time!
	switch ( $action ) {
		case 'star' :
			$count = count( $threads );

			// If we're starring a thread, we only star the first message in the thread.
			foreach ( $threads as $thread ) {
				$thread = new BP_Messages_thread( $thread );
				$mids = wp_list_pluck( $thread->messages, 'id' );

				bp_messages_star_set_action( array(
					'action'     => 'star',
					'message_id' => $mids[0],
				) );
			}

			bp_core_add_message( sprintf( _n( '%s message was successfully starred', '%s messages were successfully starred', $count, 'buddypress' ), $count ) );
			break;

		case 'unstar' :
			$count = count( $threads );

			foreach ( $threads as $thread ) {
				bp_messages_star_set_action( array(
					'action'    => 'unstar',
					'thread_id' => $thread,
					'bulk'      => true
				) );
			}

			bp_core_add_message( sprintf( _n( '%s message was successfully unstarred', '%s messages were successfully unstarred', $count, 'buddypress' ), $count ) );
			break;
	}

	// Redirect back to message box.
	bp_core_redirect( bp_displayed_user_domain() . bp_get_messages_slug() . '/' . bp_current_action() . '/' );
	die();
}
add_action( 'bp_actions', 'bp_messages_star_bulk_manage_handler', 5 );