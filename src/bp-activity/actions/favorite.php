<?php
/**
 * Activity: Favorite action
 *
 * @package BuddyPress
 * @subpackage ActivityActions
 * @since 3.0.0
 */

/**
 * Mark activity as favorite.
 *
 * @since 1.2.0
 *
 * @return bool False on failure.
 */
function bp_activity_action_mark_favorite() {
	if ( !is_user_logged_in() || !bp_is_activity_component() || !bp_is_current_action( 'favorite' ) )
		return false;

	// Check the nonce.
	check_admin_referer( 'mark_favorite' );

	if ( bp_activity_add_user_favorite( bp_action_variable( 0 ) ) )
		bp_core_add_message( __( 'Activity marked as favorite.', 'buddypress' ) );
	else
		bp_core_add_message( __( 'There was an error marking that activity as a favorite. Please try again.', 'buddypress' ), 'error' );

	bp_core_redirect( wp_get_referer() . '#activity-' . bp_action_variable( 0 ) );
}
add_action( 'bp_actions', 'bp_activity_action_mark_favorite' );