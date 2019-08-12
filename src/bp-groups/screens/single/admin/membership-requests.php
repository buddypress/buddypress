<?php
/**
 * Groups: Single group "Manage > Requests" screen handler
 *
 * @package BuddyPress
 * @subpackage GroupsScreens
 * @since 3.0.0
 */

/**
 * Handle the display of Admin > Membership Requests.
 *
 * @since 1.0.0
 */
function groups_screen_group_admin_requests() {
	$bp = buddypress();

	if ( 'membership-requests' != bp_get_group_current_admin_tab() ) {
		return false;
	}

	if ( ! bp_is_item_admin() || ( 'public' == $bp->groups->current_group->status ) ) {
		return false;
	}

	$request_action = isset( $_GET['action'] ) ? $_GET['action'] : false;
	$user_id        = isset( $_GET['user_id'] ) ? (int) $_GET['user_id'] : false;
	$group_id       = bp_get_current_group_id();

	if ( $request_action && $user_id && $group_id ) {
		if ( 'accept' === $request_action ) {

			// Check the nonce first.
			if ( ! check_admin_referer( 'groups_accept_membership_request' ) ) {
				return false;
			}

			// Accept the membership request.
			if ( ! groups_accept_membership_request( false, $user_id, $group_id ) ) {
				bp_core_add_message( __( 'There was an error accepting the membership request. Please try again.', 'buddypress' ), 'error' );
			} else {
				bp_core_add_message( __( 'Group membership request accepted', 'buddypress' ) );
			}

		} elseif ( 'reject' === $request_action ) {
			/* Check the nonce first. */
			if ( ! check_admin_referer( 'groups_reject_membership_request' ) ) {
				return false;
			}

			// Reject the membership request.
			if ( ! groups_reject_membership_request( false, $user_id, $group_id ) ) {
				bp_core_add_message( __( 'There was an error rejecting the membership request. Please try again.', 'buddypress' ), 'error' );
			} else {
				bp_core_add_message( __( 'Group membership request rejected', 'buddypress' ) );
			}
		}

		// Was the member added to the group?
		$membership_id = groups_is_user_member( $user_id, $group_id );

		/**
		 * Fires before the redirect if a group membership request has been handled.
		 *
		 * @since 1.0.0
		 *
		 * @param int    $id             ID of the group that was edited.
		 * @param string $request_action Membership request action being performed.
		 * @param int    $membership_id  The membership ID of the new user; false if rejected.
		 * @param int    $user_id        The ID of the requesting user.
		 * @param int    $group_id       The ID of the requested group.
		 */
		do_action( 'groups_group_request_managed', $bp->groups->current_group->id, $request_action, $membership_id, $user_id, $group_id );
		bp_core_redirect( bp_get_group_permalink( groups_get_current_group() ) . 'admin/membership-requests/' );
	}

	/**
	 * Fires before the loading of the group membership request page template.
	 *
	 * @since 1.0.0
	 *
	 * @param int $id ID of the group that is being displayed.
	 */
	do_action( 'groups_screen_group_admin_requests', $bp->groups->current_group->id );

	/**
	 * Filters the template to load for a group's membership request page.
	 *
	 * @since 1.0.0
	 *
	 * @param string $value Path to a group's membership request template.
	 */
	bp_core_load_template( apply_filters( 'groups_template_group_admin_requests', 'groups/single/home' ) );
}
add_action( 'bp_screens', 'groups_screen_group_admin_requests' );
