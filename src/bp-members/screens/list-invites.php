<?php
/**
 * Members: Sent Invitations Status
 *
 * @package BuddyPress
 * @subpackage MembersScreens
 * @since 8.0.0
 */

/**
 * Catch and process the Send Invites page.
 *
 * @since 8.0.0
 */
function members_screen_list_sent_invites() {

	/**
	 * Fires before the loading of template for the send membership invitations page.
	 *
	 * @since 8.0.0
	 */
	do_action( 'members_screen_list_sent_invites' );

	/**
	 * Filters the template used to display the send membership invitations page.
	 *
	 * @since 8.0.0
	 *
	 * @param string $template Path to the send membership invitations template to load.
	 */
	bp_core_load_template( apply_filters( 'members_template_list_sent_invites', 'members/single/invitations' ) );
}

/**
 * Handle canceling or resending single invitations.
 *
 * @since 8.0.0
 *
 * @return bool
 */
function bp_members_invitations_action_handling() {

	// Bail if not the read screen.
	if ( ! bp_is_user_members_invitations_list() ) {
		return false;
	}

	// Get the action.
	$action = ! empty( $_GET['action']        ) ? $_GET['action']        : '';
	$nonce  = ! empty( $_GET['_wpnonce']      ) ? $_GET['_wpnonce']      : '';
	$id     = ! empty( $_GET['invitation_id'] ) ? $_GET['invitation_id'] : '';

	// Bail if no action or no ID.
	if ( empty( $action ) || empty( $id ) ) {
		return false;
	}

	if ( 'cancel' === $action ) {
		// Check the nonce and delete the invitation.
		if ( bp_verify_nonce_request( 'bp_members_invitations_cancel_' . $id ) && bp_members_invitations_delete_by_id( $id ) ) {
			bp_core_add_message( __( 'Invitation successfully canceled.', 'buddypress' ) );
		} else {
			bp_core_add_message( __( 'There was a problem canceling that invitation.', 'buddypress' ), 'error' );
		}
	} else if ( 'resend' === $action ) {
		// Check the nonce and resend the invitation.
		if ( bp_verify_nonce_request( 'bp_network_invitation_resend_' . $id ) && bp_members_invitation_resend_by_id( $id ) ) {
			bp_core_add_message( __( 'Invitation successfully resent.', 'buddypress' ) );
		} else {
			bp_core_add_message( __( 'There was a problem resending that invitation.', 'buddypress' ), 'error' );
		}
	} else {
		return false;
	}

	// Redirect.
	$user_id = bp_displayed_user_id();
	bp_core_redirect( bp_get_members_invitations_list_invites_permalink( $user_id ) );
}
add_action( 'bp_actions', 'bp_members_invitations_action_handling' );
