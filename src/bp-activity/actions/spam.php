<?php
/**
 * Activity: Spam action
 *
 * @package BuddyPress
 * @subpackage ActivityActions
 * @since 3.0.0
 */

/**
 * Mark specific activity item as spam and redirect to previous page.
 *
 * @since 1.6.0
 *
 * @param int $activity_id Activity id to be deleted. Defaults to 0.
 * @return bool False on failure.
 */
function bp_activity_action_spam_activity( $activity_id = 0 ) {
	$bp = buddypress();

	// Not viewing activity, or action is not spam, or Akismet isn't present.
	if ( !bp_is_activity_component() || !bp_is_current_action( 'spam' ) || empty( $bp->activity->akismet ) )
		return false;

	if ( empty( $activity_id ) && bp_action_variable( 0 ) )
		$activity_id = (int) bp_action_variable( 0 );

	// Not viewing a specific activity item.
	if ( empty( $activity_id ) )
		return false;

	// Is the current user allowed to spam items?
	if ( !bp_activity_user_can_mark_spam() )
		return false;

	// Load up the activity item.
	$activity = new BP_Activity_Activity( $activity_id );
	if ( empty( $activity->id ) )
		return false;

	// Check nonce.
	check_admin_referer( 'bp_activity_akismet_spam_' . $activity->id );

	/**
	 * Fires before the marking activity as spam so plugins can modify things if they want to.
	 *
	 * @since 1.6.0
	 *
	 * @param int    $activity_id Activity ID to be marked as spam.
	 * @param object $activity    Activity object for the ID to be marked as spam.
	 */
	do_action( 'bp_activity_before_action_spam_activity', $activity->id, $activity );

	// Mark as spam.
	bp_activity_mark_as_spam( $activity );
	$activity->save();

	// Tell the user the spamming has been successful.
	bp_core_add_message( __( 'The activity item has been marked as spam and is no longer visible.', 'buddypress' ) );

	/**
	 * Fires after the marking activity as spam so plugins can act afterwards based on the activity.
	 *
	 * @since 1.6.0
	 *
	 * @param int $activity_id Activity ID that was marked as spam.
	 * @param int $user_id     User ID associated with activity.
	 */
	do_action( 'bp_activity_action_spam_activity', $activity_id, $activity->user_id );

	// Check for the redirect query arg, otherwise let WP handle things.
	if ( !empty( $_GET['redirect_to'] ) )
		bp_core_redirect( esc_url( $_GET['redirect_to'] ) );
	else
		bp_core_redirect( wp_get_referer() );
}
add_action( 'bp_actions', 'bp_activity_action_spam_activity' );