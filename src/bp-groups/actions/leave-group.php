<?php
/**
 * Groups: Leave action
 *
 * @package BuddyPress
 * @subpackage GroupActions
 * @since 3.0.0
 */

/**
 * Catch and process "Leave Group" button clicks.
 *
 * When a group member clicks on the "Leave Group" button from a group's page,
 * this function is run.
 *
 * Note: When leaving a group from the group directory, AJAX is used and
 * another function handles this. See {@link bp_legacy_theme_ajax_joinleave_group()}.
 *
 * @since 1.2.4
 *
 * @return bool
 */
function groups_action_leave_group() {
	if ( ! bp_is_single_item() || ! bp_is_groups_component() || ! bp_is_current_action( 'leave-group' ) ) {
		return false;
	}

	// Nonce check.
	if ( ! check_admin_referer( 'groups_leave_group' ) ) {
		return false;
	}

	// User wants to leave any group.
	if ( groups_is_user_member( bp_loggedin_user_id(), bp_get_current_group_id() ) ) {
		$bp = buddypress();

		// Stop sole admins from abandoning their group.
		$group_admins = groups_get_group_admins( bp_get_current_group_id() );

		if ( 1 == count( $group_admins ) && $group_admins[0]->user_id == bp_loggedin_user_id() ) {
			bp_core_add_message( __( 'This group must have at least one admin', 'buddypress' ), 'error' );
		} elseif ( ! groups_leave_group( $bp->groups->current_group->id ) ) {
			bp_core_add_message( __( 'There was an error leaving the group.', 'buddypress' ), 'error' );
		} else {
			bp_core_add_message( __( 'You successfully left the group.', 'buddypress' ) );
		}

		$group    = groups_get_current_group();
		$redirect = bp_get_group_url( $group );

		if ( ! $group->is_visible ) {
			$redirect = bp_loggedin_user_url( bp_members_get_path_chunks( array( bp_get_groups_slug() ) ) );
		}

		bp_core_redirect( $redirect );
	}

	/** This filter is documented in bp-groups/bp-groups-actions.php */
	bp_core_load_template( apply_filters( 'groups_template_group_home', 'groups/single/home' ) );
}
add_action( 'bp_actions', 'groups_action_leave_group' );

/**
 * Clean up requests/invites when a member leaves a group.
 *
 * @since 5.0.0
 */
function groups_action_clean_up_invites_requests( $user_id, $group_id ) {

	$invites_class = new BP_Groups_Invitation_Manager();
	// Remove invitations/requests where the deleted user is the receiver.
	$invites_class->delete( array(
		'user_id' => $user_id,
		'item_id' => $group_id,
		'type'    => 'all'
	) );
	/**
	 * Remove invitations where the deleted user is the sender.
	 * We'll use groups_uninvite_user() so that notifications will be cleaned up.
	 */
	$pending_invites = groups_get_invites( array(
		'inviter_id' => $user_id,
		'item_id'    => $group_id,
	) );

	if ( $pending_invites ) {
		foreach ( $pending_invites as $invite ) {
			groups_uninvite_user( $invite->user_id, $group_id, $user_id );
		}
	}
}
add_action( 'bp_groups_member_after_delete', 'groups_action_clean_up_invites_requests', 10, 2 );
