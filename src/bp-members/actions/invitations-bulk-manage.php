<?php
/**
 * Member Invitations: Bulk-manage action handler
 *
 * @package BuddyPress
 * @subpackage MembersActions
 * @since 8.0.0
 */

/**
 * Handles bulk management (resend, cancellation) of invitations.
 *
 * @since 8.0.0
 *
 * @return bool
 */
function bp_members_invitations_action_bulk_manage() {

	// Bail if not the user's invitations screen.
	if ( ! bp_is_my_profile() && ! bp_current_user_can( 'bp_moderate' ) ) {
		return false;
	}

	// Get the parameters.
	$action      = ! empty( $_POST['invitation_bulk_action'] ) ? $_POST['invitation_bulk_action'] : '';
	$nonce       = ! empty( $_POST['invitations_bulk_nonce'] ) ? $_POST['invitations_bulk_nonce'] : '';
	$invitations = ! empty( $_POST['members_invitations']    ) ? $_POST['members_invitations']    : '';

	// Bail if no action or no IDs.
	if ( ( ! in_array( $action, array( 'cancel', 'resend' ), true ) ) || empty( $invitations ) || empty( $nonce ) ) {
		return false;
	}

	// Check the nonce.
	if ( ! wp_verify_nonce( $nonce, 'invitations_bulk_nonce' ) ) {
		bp_core_add_message( __( 'There was a problem managing your invitations.', 'buddypress' ), 'error' );
		return false;
	}

	$invitations = wp_parse_id_list( $invitations );

	// Cancel or resend depending on the user 'action'.
	switch ( $action ) {
		case 'cancel' :
			$success = 0;
			foreach ( $invitations as $invite_id ) {
				if ( bp_members_invitations_delete_by_id( $invite_id ) ) {
					$success++;
				}
			}
			$message = sprintf(
				esc_html(
					/* translators: %d: the number of invitations that were canceled. */
					_n( '%d invitation was canceled.', '%d invitations were canceled.', $success, 'buddypress' )
				),
				$success
			);
			bp_core_add_message( $message );
			break;

		case 'resend' :
			$success = 0;
			foreach ( $invitations as $invite_id ) {
				if ( bp_members_invitation_resend_by_id( $invite_id ) ) {
					$success++;
				}
			}
			$message = sprintf(
				esc_html(
					/* translators: %d: the number of invitations that were resent. */
					_n( '%d invitation was resent.', '%d invitations were resent.', $success, 'buddypress' )
				),
				$success
			);
			bp_core_add_message( $message );
			break;
	}

	$invite_slug = bp_get_members_invitations_slug();
	$action_slug = bp_current_action();
	$path_chunks = bp_members_get_path_chunks( array( $invite_slug, $action_slug ) );

	// Redirect.
	bp_core_redirect( bp_displayed_user_url( $path_chunks ) );
}
add_action( 'bp_actions', 'bp_members_invitations_action_bulk_manage' );
