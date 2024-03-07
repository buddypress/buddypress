<?php
/**
 * BuddyPress Members: Invitations screens
 *
 * @package BuddyPress
 * @subpackage MembersScreens
 * @since 8.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Catch and process the Send Invites page.
 *
 * @since 8.0.0
 */
function members_screen_send_invites() {

	// Chack if there's an invitation to send.
	if ( isset( $_REQUEST['action'] ) && 'send-invite' === wp_unslash( $_REQUEST['action'] ) ) {
		$user_id      = bp_displayed_user_id();
		$default_args = array(
			'invitee_email' => '',
			'inviter_id'    => $user_id,
			'content'       => '',
			'send_invite'   => 1,
		);

		$invite_args = bp_parse_args(
			array_map( 'wp_unslash', $_REQUEST ),
			$default_args
		);
		$invite_args = array_intersect_key( $invite_args, $default_args );

		// Check the nonce and delete the invitation.
		if ( bp_verify_nonce_request( 'bp_members_invitation_send_' . $user_id ) && bp_members_invitations_invite_user( $invite_args ) ) {
			bp_core_add_message( __( 'Invitation successfully sent!', 'buddypress' )          );
		} else {
			bp_core_add_message( __( 'There was a problem sending that invitation. The user could already be a member of the site or have chosen not to receive invitations from this site.', 'buddypress' ), 'error' );
		}

		// Redirect.
		bp_core_redirect( bp_get_members_invitations_send_invites_permalink( $user_id ) );
	}

	/**
	 * Fires before the loading of template for the send membership invitations page.
	 *
	 * @since 8.0.0
	 */
	do_action( 'members_screen_send_invites' );

	$templates = array(
		/**
		 * Filters the template used to display the send membership invitations page.
		 *
		 * @since 8.0.0
		 *
		 * @param string $template Path to the send membership invitations template to load.
		 */
		apply_filters( 'members_template_send_invites', 'members/single/home' ),
		'members/single/index',
	);

	bp_core_load_template( $templates );
}

/**
 * Catch and process the Pending Invites page.
 *
 * @since 8.0.0
 */
function members_screen_list_sent_invites() {

	// Chack if there's an invitation to cancel or resend.
	if ( isset( $_GET['action'], $_GET['invitation_id'] ) && $_GET['action'] && $_GET['invitation_id'] ) {
		$action        = wp_unslash( $_GET['action'] );
		$invitation_id = (int) wp_unslash( $_GET['invitation_id'] );
		$user_id       = bp_displayed_user_id();

		if ( 'cancel' === $action ) {
			// Check the nonce and delete the invitation.
			if ( bp_verify_nonce_request( 'bp_members_invitations_cancel_' . $invitation_id ) && bp_members_invitations_delete_by_id( $invitation_id ) ) {
				bp_core_add_message( __( 'Invitation successfully canceled.', 'buddypress' ) );
			} else {
				bp_core_add_message( __( 'There was a problem canceling that invitation.', 'buddypress' ), 'error' );
			}
		} elseif ( 'resend' === $action ) {
			// Check the nonce and resend the invitation.
			if ( bp_verify_nonce_request( 'bp_members_invitation_resend_' . $invitation_id ) && bp_members_invitation_resend_by_id( $invitation_id ) ) {
				bp_core_add_message( __( 'Invitation successfully resent.', 'buddypress' ) );
			} else {
				bp_core_add_message( __( 'There was a problem resending that invitation.', 'buddypress' ), 'error' );
			}
		} else {
			/**
			 * Hook here to handle custom actions.
			 *
			 * @since 8.0.0
			 *
			 * @param string $action        The action name.
			 * @param int    $invitation_id The invitation ID.
			 * @param int    $user_id       The displayed user ID.
			 */
			do_action( 'bp_members_invitations_list_invites_action', $action, $invitation_id, $user_id );
		}

		// Redirect.
		bp_core_redirect( bp_get_members_invitations_list_invites_permalink( $user_id ) );
	}

	/**
	 * Fires before the loading of template for the send membership invitations page.
	 *
	 * @since 8.0.0
	 */
	do_action( 'members_screen_list_sent_invites' );

	$templates = array(
		/**
		 * Filters the template used to display the send membership invitations page.
		 *
		 * @since 8.0.0
		 *
		 * @param string $template Path to the send membership invitations template to load.
		 */
		apply_filters( 'members_template_list_sent_invites', 'members/single/home' ),
		'members/single/index',
	);

	bp_core_load_template( $templates );
}
