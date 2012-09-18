<?php

/**
 * BuddyPress Members Actions
 *
 * Action functions are exactly the same as screen functions, however they do not
 * have a template screen associated with them. Usually they will send the user
 * back to the default screen after execution.
 *
 * @package BuddyPress
 * @subpackage MembersActions
 */

// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

/**
 * When a site admin selects "Mark as Spammer/Not Spammer" from the admin menu
 * this action will fire and mark or unmark the user and their blogs as spam.
 * Must be a site admin for this function to run.
 *
 * @package BuddyPress Core
 * @param int $user_id Optional user ID to mark as spam
 * @global object $wpdb Global WordPress Database object
 */
function bp_core_action_set_spammer_status( $user_id = 0 ) {

	// Use displayed user if it's not yourself
	if ( empty( $user_id ) )
		$user_id = bp_displayed_user_id();

	if ( bp_is_current_component( 'admin' ) && ( in_array( bp_current_action(), array( 'mark-spammer', 'unmark-spammer' ) ) ) ) {

		// Check the nonce
		check_admin_referer( 'mark-unmark-spammer' );

		// To spam or not to spam
		$status = bp_is_current_action( 'mark-spammer' ) ? 'spam' : 'ham';

		// The heavy lifting
		bp_core_process_spammer_status( $user_id, $status );

		// Add feedback message. @todo - Error reporting
		if ( 'spam' == $status ) {
			bp_core_add_message( __( 'User marked as spammer. Spam users are visible only to site admins.', 'buddypress' ) );
		} else {
			bp_core_add_message( __( 'User removed as spammer.', 'buddypress' ) );
		}

		// Deprecated. Use bp_core_process_spammer_status.
		$is_spam = 'spam' == $status;
		do_action( 'bp_core_action_set_spammer_status', bp_displayed_user_id(), $is_spam );

		// Redirect back to where we came from
		bp_core_redirect( wp_get_referer() );
	}
}
// Unhooked in BuddyPress (1.6) - moved to settings
//add_action( 'bp_actions', 'bp_core_action_set_spammer_status' );

/**
 * Allows a site admin to delete a user from the adminbar menu.
 *
 * @package BuddyPress Core
 */
function bp_core_action_delete_user() {

	if ( !bp_current_user_can( 'bp_moderate' ) || bp_is_my_profile() || !bp_displayed_user_id() )
		return false;

	if ( bp_is_current_component( 'admin' ) && bp_is_current_action( 'delete-user' ) ) {

		// Check the nonce
		check_admin_referer( 'delete-user' );

		$errors = false;
		do_action( 'bp_core_before_action_delete_user', $errors );

		if ( bp_core_delete_account( bp_displayed_user_id() ) ) {
			bp_core_add_message( sprintf( __( '%s has been deleted from the system.', 'buddypress' ), bp_get_displayed_user_fullname() ) );
		} else {
			bp_core_add_message( sprintf( __( 'There was an error deleting %s from the system. Please try again.', 'buddypress' ), bp_get_displayed_user_fullname() ), 'error' );
			$errors = true;
		}

		do_action( 'bp_core_action_delete_user', $errors );

		if ( $errors )
			bp_core_redirect( bp_displayed_user_domain() );
		else
			bp_core_redirect( bp_loggedin_user_domain() );
	}
}
// Unhooked in BuddyPress (1.6) - moved to settings
//add_action( 'bp_actions', 'bp_core_action_delete_user' );

/**
 * Returns the user_id for a user based on their username.
 *
 * @package BuddyPress Core
 * @param $username str Username to check.
 * @return false on no match
 * @return int the user ID of the matched user.
 */
function bp_core_get_random_member() {
	if ( ! isset( $_GET['random-member'] ) )
		return;

	$user = bp_core_get_users( array( 'type' => 'random', 'per_page' => 1 ) );
	bp_core_redirect( bp_core_get_user_domain( $user['users'][0]->id ) );
}
add_action( 'bp_actions', 'bp_core_get_random_member' );
