<?php
/**
 * Groups: User's "Groups > Invites" screen handler
 *
 * @package BuddyPress
 * @subpackage GroupScreens
 * @since 3.0.0
 */

/**
 * Handle the loading of a user's Groups > Invites page.
 *
 * @since 1.0.0
 */
function groups_screen_group_invites() {
	$group_id = (int) bp_action_variable( 1 );

	if ( bp_is_action_variable( 'accept' ) && is_numeric( $group_id ) ) {
		// Check the nonce.
		if ( ! check_admin_referer( 'groups_accept_invite' ) ) {
			return false;
		}

		if ( ! groups_accept_invite( bp_displayed_user_id(), $group_id ) ) {
			bp_core_add_message( __('Group invite could not be accepted', 'buddypress'), 'error' );
		} else {
			// Record this in activity streams.
			$group = groups_get_group( $group_id );

			/* translators: %s: group link */
			bp_core_add_message( sprintf( __( 'Group invite accepted. View %s.', 'buddypress' ), bp_get_group_link( $group ) ) );

			if ( bp_is_active( 'activity' ) ) {
				groups_record_activity( array(
					'type'    => 'joined_group',
					'item_id' => $group->id
				) );
			}
		}

		if ( isset( $_GET['redirect_to'] ) ) {
			$redirect_to = urldecode( $_GET['redirect_to'] );
		} else {
			$path_chunks = bp_members_get_path_chunks( array( bp_get_groups_slug(), bp_current_action() ) );
			$redirect_to = bp_displayed_user_url( $path_chunks );
		}

		bp_core_redirect( $redirect_to );

	} elseif ( bp_is_action_variable( 'reject' ) && is_numeric( $group_id ) ) {
		// Check the nonce.
		if ( !check_admin_referer( 'groups_reject_invite' ) )
			return false;

		if ( ! groups_reject_invite( bp_displayed_user_id(), $group_id ) ) {
			bp_core_add_message( __( 'Group invite could not be rejected', 'buddypress' ), 'error' );
		} else {
			bp_core_add_message( __( 'Group invite rejected', 'buddypress' ) );
		}

		if ( isset( $_GET['redirect_to'] ) ) {
			$redirect_to = urldecode( $_GET['redirect_to'] );
		} else {
			$path_chunks = bp_members_get_path_chunks( array( bp_get_groups_slug(), bp_current_action() ) );
			$redirect_to = bp_displayed_user_url( $path_chunks );
		}

		bp_core_redirect( $redirect_to );
	}

	/**
	 * Fires before the loading of a users Groups > Invites template.
	 *
	 * @since 1.0.0
	 *
	 * @param int $group_id ID of the group being displayed
	 */
	do_action( 'groups_screen_group_invites', $group_id );

	/**
	 * Filters the template to load for a users Groups > Invites page.
	 *
	 * @since 1.0.0
	 *
	 * @param string $value Path to a users Groups > Invites page template.
	 */
	bp_core_load_template( apply_filters( 'groups_template_group_invites', 'members/single/home' ) );
}
