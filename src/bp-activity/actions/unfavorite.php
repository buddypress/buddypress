<?php
/**
 * Activity: Unfavorite action
 *
 * @package BuddyPress
 * @subpackage ActivityActions
 * @since 3.0.0
 */

/**
 * Remove activity from favorites.
 *
 * @since 1.2.0
 *
 * @return bool False on failure.
 */
function bp_activity_action_remove_favorite() {
	if ( ! is_user_logged_in() || ! bp_is_activity_component() || ! bp_is_current_action( 'unfavorite' ) )
		return false;

	// Check the nonce.
	check_admin_referer( 'unmark_favorite' );

	if ( bp_activity_remove_user_favorite( bp_action_variable( 0 ) ) )
		bp_core_add_message( __( 'Activity removed as favorite.', 'buddypress' ) );
	else
		bp_core_add_message( __( 'There was an error removing that activity as a favorite. Please try again.', 'buddypress' ), 'error' );

	bp_core_redirect( wp_get_referer() . '#activity-' . bp_action_variable( 0 ) );
}
add_action( 'bp_actions', 'bp_activity_action_remove_favorite' );