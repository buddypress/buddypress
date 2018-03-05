<?php
/**
 * Activity: Delete action
 *
 * @package BuddyPress
 * @subpackage ActivityActions
 * @since 3.0.0
 */

/**
 * Delete specific activity item and redirect to previous page.
 *
 * @since 1.1.0
 *
 * @param int $activity_id Activity id to be deleted. Defaults to 0.
 * @return bool False on failure.
 */
function bp_activity_action_delete_activity( $activity_id = 0 ) {
	// Not viewing activity or action is not delete.
	if ( !bp_is_activity_component() || !bp_is_current_action( 'delete' ) )
		return false;

	if ( empty( $activity_id ) && bp_action_variable( 0 ) )
		$activity_id = (int) bp_action_variable( 0 );

	// Not viewing a specific activity item.
	if ( empty( $activity_id ) )
		return false;

	// Check the nonce.
	check_admin_referer( 'bp_activity_delete_link' );

	// Load up the activity item.
	$activity = new BP_Activity_Activity( $activity_id );

	// Check access.
	if ( ! bp_activity_user_can_delete( $activity ) )
		return false;

	/**
	 * Fires before the deletion so plugins can still fetch information about it.
	 *
	 * @since 1.5.0
	 *
	 * @param int $activity_id The activity ID.
	 * @param int $user_id     The user associated with the activity.
	 */
	do_action( 'bp_activity_before_action_delete_activity', $activity_id, $activity->user_id );

	// Delete the activity item and provide user feedback.
	if ( bp_activity_delete( array( 'id' => $activity_id, 'user_id' => $activity->user_id ) ) )
		bp_core_add_message( __( 'Activity deleted successfully', 'buddypress' ) );
	else
		bp_core_add_message( __( 'There was an error when deleting that activity', 'buddypress' ), 'error' );

	/**
	 * Fires after the deletion so plugins can act afterwards based on the activity.
	 *
	 * @since 1.1.0
	 *
	 * @param int $activity_id The activity ID.
	 * @param int $user_id     The user associated with the activity.
	 */
	do_action( 'bp_activity_action_delete_activity', $activity_id, $activity->user_id );

	// Check for the redirect query arg, otherwise let WP handle things.
	if ( !empty( $_GET['redirect_to'] ) )
		bp_core_redirect( esc_url( $_GET['redirect_to'] ) );
	else
		bp_core_redirect( wp_get_referer() );
}
add_action( 'bp_actions', 'bp_activity_action_delete_activity' );