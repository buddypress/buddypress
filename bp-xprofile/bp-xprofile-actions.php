<?php
/*******************************************************************************
 * Action functions are exactly the same as screen functions, however they do
 * not have a template screen associated with them. Usually they will send the
 * user back to the default screen after execution.
 */

// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

/**
 * This function runs when an action is set for a screen:
 * example.com/members/andy/profile/change-avatar/ [delete-avatar]
 *
 * The function will delete the active avatar for a user.
 *
 * @package BuddyPress Xprofile
 * @global object $bp Global BuddyPress settings object
 * @uses bp_core_delete_avatar() Deletes the active avatar for the logged in user.
 * @uses add_action() Runs a specific function for an action when it fires.
 * @uses bp_core_load_template() Looks for and loads a template file within the current member theme (folder/filename)
 */
function xprofile_action_delete_avatar() {
	global $bp;

	if ( !bp_is_user_change_avatar() || !bp_is_action_variable( 'delete-avatar', 0 ) )
		return false;

	// Check the nonce
	check_admin_referer( 'bp_delete_avatar_link' );

	if ( !bp_is_my_profile() && !is_super_admin() )
		return false;

	if ( bp_core_delete_existing_avatar( array( 'item_id' => $bp->displayed_user->id ) ) )
		bp_core_add_message( __( 'Your avatar was deleted successfully!', 'buddypress' ) );
	else
		bp_core_add_message( __( 'There was a problem deleting that avatar, please try again.', 'buddypress' ), 'error' );

	bp_core_redirect( wp_get_referer() );
}
add_action( 'bp_actions', 'xprofile_action_delete_avatar' );

?>
