<?php
/**
 * Members: Send Invitations
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
function members_screen_send_invites() {

	/**
	 * Fires before the loading of template for the send membership invitations page.
	 *
	 * @since 8.0.0
	 */
	do_action( 'members_screen_send_invites' );

	/**
	 * Filters the template used to display the send membership invitations page.
	 *
	 * @since 8.0.0
	 *
	 * @param string $template Path to the send membership invitations template to load.
	 */
	bp_core_load_template( apply_filters( 'members_template_send_invites', 'members/single/invitations' ) );
}

/**
 * Handle sending invitations.
 *
 * @since 8.0.0
 *
 * @return bool
 */
function bp_network_invitations_catch_send_action() {

	// Bail if not the read screen.
	if ( ! bp_is_user_members_invitations_send_screen() ) {
		return false;
	}

	// Get the action.
	$action  = ! empty( $_REQUEST['action']          ) ? $_REQUEST['action']          : '';
	$nonce   = ! empty( $_REQUEST['_wpnonce']        ) ? $_REQUEST['_wpnonce']        : '';
	$email   = ! empty( $_REQUEST['invitee_email']   ) ? $_REQUEST['invitee_email']   : '';
	$message = ! empty( $_REQUEST['invite_message']  ) ? $_REQUEST['invite_message']  : '';

	// Bail if missing required info.
	if ( ( 'send-invite' !== $action ) ) {
		return false;
	}

	$invite_args = array(
		'invitee_email' => $email,
		'inviter_id'    => bp_displayed_user_id(),
		'content'       => $message,
		'send_invite'   => 1
	);

	// Check the nonce and delete the invitation.
	if ( bp_verify_nonce_request( 'bp_members_invitation_send_' . bp_displayed_user_id() ) && bp_members_invitations_invite_user( $invite_args ) ) {
		bp_core_add_message( __( 'Invitation successfully sent!', 'buddypress' )          );
	} else {
		bp_core_add_message( __( 'There was a problem sending that invitation. The user could already be a member of the site or have chosen not to receive invitations from this site.', 'buddypress' ), 'error' );
	}

	// Redirect.
	$user_id = bp_displayed_user_id();
	bp_core_redirect( bp_get_members_invitations_send_invites_permalink( $user_id ) );
}
add_action( 'bp_actions', 'bp_network_invitations_catch_send_action' );
