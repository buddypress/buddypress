<?php
/**
 * Groups: Single group "Request Membership" screen handler
 *
 * @package BuddyPress
 * @subpackage GroupsScreens
 * @since 3.0.0
 */

/**
 * Handle the display of a group's Request Membership page.
 *
 * @since 1.0.0
 */
function groups_screen_group_request_membership() {

	if ( ! is_user_logged_in() ) {
		return;
	}

	$bp = buddypress();

	if ( 'private' != $bp->groups->current_group->status ) {
		return;
	}

	// If the user is already invited, accept invitation.
	if ( groups_check_user_has_invite( bp_loggedin_user_id(), $bp->groups->current_group->id ) ) {
		if ( groups_accept_invite( bp_loggedin_user_id(), $bp->groups->current_group->id ) ) {
			bp_core_add_message( __( 'Group invite accepted', 'buddypress' ) );
		} else {
			bp_core_add_message( __( 'There was an error accepting the group invitation. Please try again.', 'buddypress' ), 'error' );
		}

		bp_core_redirect( bp_get_group_url( $bp->groups->current_group ) );
	}

	// If the user has submitted a request, send it.
	if ( isset( $_POST['group-request-send']) ) {

		// Check the nonce.
		if ( ! check_admin_referer( 'groups_request_membership' ) ) {
			return;
		}

		// Default arguments for the membership request.
		$request_args = array(
			'user_id'  => bp_loggedin_user_id(),
			'group_id' => $bp->groups->current_group->id
		);

		// If the member added a message to their request include it into the request arguments.
		if ( isset( $_POST['group-request-membership-comments'] ) && $_POST['group-request-membership-comments'] ) {
			$request_args['content'] = wp_strip_all_tags( wp_unslash( $_POST['group-request-membership-comments'] ) );
		}

		if ( ! groups_send_membership_request( $request_args ) ) {
			bp_core_add_message( __( 'There was an error sending your group membership request. Please try again.', 'buddypress' ), 'error' );
		} else {
			bp_core_add_message( __( 'Your membership request was sent to the group administrator successfully. You will be notified when the group administrator responds to your request.', 'buddypress' ) );
		}

		bp_core_redirect( bp_get_group_url( $bp->groups->current_group ) );
	}

	/**
	 * Fires before the loading of a group's Request Memebership page.
	 *
	 * @since 1.0.0
	 *
	 * @param int $id ID of the group currently being displayed.
	 */
	do_action( 'groups_screen_group_request_membership', $bp->groups->current_group->id );

	$templates = array(
		/**
		 * Filters the template to load for a group's Request Membership page.
		 *
		 * @since 1.0.0
		 *
		 * @param string $value Path to a group's Request Membership template.
		 */
		apply_filters( 'groups_template_group_request_membership', 'groups/single/home' ),
		'groups/single/index',
	);

	bp_core_load_template( $templates );
}
