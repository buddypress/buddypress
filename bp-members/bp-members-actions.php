<?php
/*******************************************************************************
 * Action Functions
 *
 * Action functions are exactly the same as screen functions, however they do not
 * have a template screen associated with them. Usually they will send the user
 * back to the default screen after execution.
 */

/**
 * Listens to the $bp component and action variables to determine if the user is viewing the members
 * directory page. If they are, it will set up the directory and load the members directory template.
 *
 * @package BuddyPress Core
 * @global object $bp Global BuddyPress settings object
 * @uses wp_enqueue_script() Loads a JS script into the header of the page.
 * @uses bp_core_load_template() Loads a specific template file.
 */
/**
 * When a site admin selects "Mark as Spammer/Not Spammer" from the admin menu
 * this action will fire and mark or unmark the user and their blogs as spam.
 * Must be a site admin for this function to run.
 *
 * @package BuddyPress Core
 * @global object $bp Global BuddyPress settings object
 */
function bp_members_action_set_spammer_status() {
	global $bp, $wpdb, $wp_version;

	if ( !is_super_admin() || bp_is_my_profile() || !$bp->displayed_user->id )
		return false;

	if ( 'admin' == $bp->current_component && ( 'mark-spammer' == $bp->current_action || 'unmark-spammer' == $bp->current_action ) ) {
		// Check the nonce
		check_admin_referer( 'mark-unmark-spammer' );

		// Get the functions file
		if ( is_multisite() )
			require_once( ABSPATH . 'wp-admin/includes/ms.php' );

		if ( 'mark-spammer' == $bp->current_action )
			$is_spam = 1;
		else
			$is_spam = 0;

		// Get the blogs for the user
		$blogs = get_blogs_of_user( $bp->displayed_user->id, true );

		foreach ( (array) $blogs as $key => $details ) {
			// Do not mark the main or current root blog as spam
			if ( 1 == $details->userblog_id || bp_get_root_blog_id() == $details->userblog_id )
				continue;

			// Update the blog status
			update_blog_status( $details->userblog_id, 'spam', $is_spam );
		}

		// Finally, mark this user as a spammer
		if ( is_multisite() )
			update_user_status( $bp->displayed_user->id, 'spam', 1 );

		$wpdb->update( $wpdb->users, array( 'user_status' => $is_spam ), array( 'ID' => $bp->displayed_user->id ) );

		if ( $is_spam )
			bp_core_add_message( __( 'User marked as spammer. Spam users are visible only to site admins.', 'buddypress' ) );
		else
			bp_core_add_message( __( 'User removed as spammer.', 'buddypress' ) );

		// Hide this user's activity
		if ( $is_spam && bp_is_active( 'activity' ) )
			bp_activity_hide_user_activity( $bp->displayed_user->id );

		// We need a special hook for is_spam so that components can delete data at spam time
		if ( $is_spam )
			do_action( 'bp_make_spam_user', $bp->displayed_user->id );
		else
			do_action( 'bp_make_ham_user',  $bp->displayed_user->id );

		do_action( 'bp_members_action_set_spammer_status', $bp->displayed_user->id, $is_spam );
		bp_core_redirect( wp_get_referer() );
	}
}
add_action( 'bp_actions', 'bp_members_action_set_spammer_status' );

/**
 * Allows a site admin to delete a user from the adminbar menu.
 *
 * @package BuddyPress Core
 * @global object $bp Global BuddyPress settings object
 */
function bp_members_action_delete_user() {
	global $bp;

	if ( !is_super_admin() || bp_is_my_profile() || !$bp->displayed_user->id )
		return false;

	if ( 'admin' == $bp->current_component && 'delete-user' == $bp->current_action ) {
		// Check the nonce
		check_admin_referer( 'delete-user' );

		$errors = false;
		do_action( 'bp_members_before_action_delete_user', $errors );

		if ( bp_core_delete_account( $bp->displayed_user->id ) ) {
			bp_core_add_message( sprintf( __( '%s has been deleted from the system.', 'buddypress' ), $bp->displayed_user->fullname ) );
		} else {
			bp_core_add_message( sprintf( __( 'There was an error deleting %s from the system. Please try again.', 'buddypress' ), $bp->displayed_user->fullname ), 'error' );
			$errors = true;
		}

		do_action( 'bp_members_action_delete_user', $errors );

		if ( $errors )
			bp_core_redirect( $bp->displayed_user->domain );
		else
			bp_core_redirect( $bp->loggedin_user->domain );
	}
}
add_action( 'bp_actions', 'bp_members_action_delete_user' );

/**
 * Returns the user_id for a user based on their username.
 *
 * @package BuddyPress Core
 * @param $username str Username to check.
 * @return false on no match
 * @return int the user ID of the matched user.
 */
function bp_core_get_random_member() {
	global $bp;

	if ( isset( $_GET['random-member'] ) ) {
		$user = bp_core_get_users( array( 'type' => 'random', 'per_page' => 1 ) );
		bp_core_redirect( bp_core_get_user_domain( $user['users'][0]->id ) );
	}
}
add_action( 'bp_actions', 'bp_core_get_random_member' );
?>