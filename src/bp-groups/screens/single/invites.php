<?php
/**
 * Groups: Single group "Send Invites" screen handler
 *
 * @package BuddyPress
 * @subpackage GroupsScreens
 * @since 3.0.0
 */

/**
 * Handle the display of a group's Send Invites page.
 *
 * @since 1.0.0
 */
function groups_screen_group_invite() {

	if ( !bp_is_single_item() )
		return false;

	$bp = buddypress();

	if ( bp_is_action_variable( 'send', 0 ) ) {

		if ( !check_admin_referer( 'groups_send_invites', '_wpnonce_send_invites' ) )
			return false;

		if ( !empty( $_POST['friends'] ) ) {
			foreach( (array) $_POST['friends'] as $friend ) {
				groups_invite_user( array( 'user_id' => $friend, 'group_id' => $bp->groups->current_group->id ) );
			}
		}

		// Send the invites.
		groups_send_invites( bp_loggedin_user_id(), $bp->groups->current_group->id );
		bp_core_add_message( __('Group invites sent.', 'buddypress') );

		/**
		 * Fires after the sending of a group invite inside the group's Send Invites page.
		 *
		 * @since 1.0.0
		 *
		 * @param int $id ID of the group whose members are being displayed.
		 */
		do_action( 'groups_screen_group_invite', $bp->groups->current_group->id );
		bp_core_redirect( bp_get_group_permalink( $bp->groups->current_group ) );

	} elseif ( !bp_action_variable( 0 ) ) {

		/**
		 * Filters the template to load for a group's Send Invites page.
		 *
		 * @since 1.0.0
		 *
		 * @param string $value Path to a group's Send Invites template.
		 */
		bp_core_load_template( apply_filters( 'groups_template_group_invite', 'groups/single/home' ) );

	} else {
		bp_do_404();
	}
}

/**
 * Process group invitation removal requests.
 *
 * Note that this function is only used when JS is disabled. Normally, clicking
 * Remove Invite removes the invitation via AJAX.
 *
 * @since 2.0.0
 */
function groups_remove_group_invite() {
	if ( ! bp_is_group_invites() ) {
		return;
	}

	if ( ! bp_is_action_variable( 'remove', 0 ) || ! is_numeric( bp_action_variable( 1 ) ) ) {
		return;
	}

	if ( ! check_admin_referer( 'groups_invite_uninvite_user' ) ) {
		return false;
	}

	$friend_id = intval( bp_action_variable( 1 ) );
	$group_id  = bp_get_current_group_id();
	$message   = __( 'Invite successfully removed', 'buddypress' );
	$redirect  = wp_get_referer();
	$error     = false;

	if ( ! bp_groups_user_can_send_invites( $group_id ) ) {
		$message = __( 'You are not allowed to send or remove invites', 'buddypress' );
		$error = 'error';
	} elseif ( groups_check_for_membership_request( $friend_id, $group_id ) ) {
		$message = __( 'The member requested to join the group', 'buddypress' );
		$error = 'error';
	} elseif ( ! groups_uninvite_user( $friend_id, $group_id ) ) {
		$message = __( 'There was an error removing the invite', 'buddypress' );
		$error = 'error';
	}

	bp_core_add_message( $message, $error );
	bp_core_redirect( $redirect );
}
add_action( 'bp_screens', 'groups_remove_group_invite' );