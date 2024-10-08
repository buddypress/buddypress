<?php
/**
 * Groups: Join action
 *
 * @package BuddyPress
 * @subpackage GroupActions
 * @since 3.0.0
 */

/**
 * Catch and process "Join Group" button clicks.
 *
 * @since 1.0.0
 */
function groups_action_join_group() {

	if ( ! bp_is_single_item() || ! bp_is_groups_component() || ! bp_is_current_action( 'join' ) ) {
		return;
	}

	/*
	 * Ensure that the invite_status key exists, to avoid a group being joinable when its
	 * creation process was interrupted.
	 */
	if ( ! groups_get_groupmeta( bp_get_current_group_id(), 'invite_status' ) ) {
		return;
	}

	// Nonce check.
	if ( ! check_admin_referer( 'groups_join_group' ) ) {
		return;
	}

	$bp = buddypress();

	// Skip if banned or already a member.
	if ( ! groups_is_user_member( bp_loggedin_user_id(), $bp->groups->current_group->id ) && ! groups_is_user_banned( bp_loggedin_user_id(), $bp->groups->current_group->id ) ) {

		// User wants to join a group that requires an invitation to join.
		if ( ! bp_current_user_can( 'groups_join_group', array( 'group_id' => $bp->groups->current_group->id ) ) ) {
			if ( ! groups_check_user_has_invite( bp_loggedin_user_id(), $bp->groups->current_group->id ) ) {
				bp_core_add_message( __( 'There was an error joining the group.', 'buddypress' ), 'error' );
				bp_core_redirect( bp_get_group_url( $bp->groups->current_group ) );
			}
		}

		// User wants to join any group.
		if ( ! groups_join_group( $bp->groups->current_group->id ) ) {
			bp_core_add_message( __( 'There was an error joining the group.', 'buddypress' ), 'error' );
		} else {
			bp_core_add_message( __( 'You joined the group!', 'buddypress' ) );
		}

		bp_core_redirect( bp_get_group_url( $bp->groups->current_group ) );
	}

	$templates = array(
		/**
		 * Filters the template to load for the single group screen.
		 *
		 * @since 1.0.0
		 *
		 * @param string $value Path to the single group template to load.
		 */
		apply_filters( 'groups_template_group_home', 'groups/single/home' ),
		'groups/single/index',
	);

	bp_core_load_template( $templates );
}
add_action( 'bp_actions', 'groups_action_join_group' );
