<?php
/**
 * XProfile: Avatar deletion action handler
 *
 * @package BuddyPress
 * @subpackage XProfileActions
 * @since 3.0.0
 */

/**
 * This function runs when an action is set for a screen:
 * example.com/members/andy/profile/change-avatar/ [delete-avatar]
 *
 * The function will delete the active avatar for a user.
 *
 * @since 1.0.0
 *
 */
function xprofile_action_delete_avatar() {

	if ( ! bp_is_user_change_avatar() || ! bp_is_action_variable( 'delete-avatar', 0 ) ) {
		return false;
	}

	// Check the nonce.
	check_admin_referer( 'bp_delete_avatar_link' );

	if ( ! bp_is_my_profile() && ! bp_current_user_can( 'bp_moderate' ) ) {
		return false;
	}

	if ( bp_core_delete_existing_avatar( array( 'item_id' => bp_displayed_user_id() ) ) ) {
		bp_core_add_message( __( 'Your profile photo was deleted successfully!', 'buddypress' ) );
	} else {
		bp_core_add_message( __( 'There was a problem deleting your profile photo. Please try again.', 'buddypress' ), 'error' );
	}

	bp_core_redirect( wp_get_referer() );
}
add_action( 'bp_actions', 'xprofile_action_delete_avatar' );