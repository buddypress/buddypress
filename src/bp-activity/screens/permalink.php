<?php
/**
 * Activity: Single permalink screen handler
 *
 * @package BuddyPress
 * @subpackage ActivityScreens
 * @since 3.0.0
 */

/**
 * Catch and route requests for single activity item permalinks.
 *
 * @since 1.2.0
 *
 * @return bool False on failure.
 */
function bp_activity_action_permalink_router() {
	// Not viewing activity.
	if ( ! bp_is_activity_component() || ! bp_is_current_action( 'p' ) )
		return false;

	// No activity to display.
	if ( ! bp_action_variable( 0 ) || ! is_numeric( bp_action_variable( 0 ) ) )
		return false;

	// Get the activity details.
	$activity = bp_activity_get_specific( array( 'activity_ids' => bp_action_variable( 0 ), 'show_hidden' => true ) );

	// 404 if activity does not exist.
	if ( empty( $activity['activities'][0] ) ) {
		bp_do_404();
		return;
	} else {
		$activity = $activity['activities'][0];
	}

	// Do not redirect at default.
	$redirect = false;

	// Redirect based on the type of activity.
	if ( bp_is_active( 'groups' ) && $activity->component == buddypress()->groups->id ) {

		// Activity is a user update.
		if ( ! empty( $activity->user_id ) ) {
			$redirect = bp_core_get_user_domain( $activity->user_id, $activity->user_nicename, $activity->user_login ) . bp_get_activity_slug() . '/' . $activity->id . '/';

		// Activity is something else.
		} else {

			// Set redirect to group activity stream.
			if ( $group = groups_get_group( $activity->item_id ) ) {
				$redirect = bp_get_group_permalink( $group ) . bp_get_activity_slug() . '/' . $activity->id . '/';
			}
		}

	// Set redirect to users' activity stream.
	} elseif ( ! empty( $activity->user_id ) ) {
		$redirect = bp_core_get_user_domain( $activity->user_id, $activity->user_nicename, $activity->user_login ) . bp_get_activity_slug() . '/' . $activity->id . '/';
	}

	// If set, add the original query string back onto the redirect URL.
	if ( ! empty( $_SERVER['QUERY_STRING'] ) ) {
		$query_frags = array();
		wp_parse_str( $_SERVER['QUERY_STRING'], $query_frags );
		$redirect = add_query_arg( urlencode_deep( $query_frags ), $redirect );
	}

	/**
	 * Filter the intended redirect url before the redirect occurs for the single activity item.
	 *
	 * @since 1.2.2
	 *
	 * @param array $value Array with url to redirect to and activity related to the redirect.
	 */
	if ( ! $redirect = apply_filters_ref_array( 'bp_activity_permalink_redirect_url', array( $redirect, &$activity ) ) ) {
		bp_core_redirect( bp_get_root_domain() );
	}

	// Redirect to the actual activity permalink page.
	bp_core_redirect( $redirect );
}
add_action( 'bp_actions', 'bp_activity_action_permalink_router' );

/**
 * Load the page for a single activity item.
 *
 * @since 1.2.0
 *
 * @return bool|string Boolean on false or the template for a single activity item on success.
 */
function bp_activity_screen_single_activity_permalink() {
	// No displayed user or not viewing activity component.
	if ( ! bp_is_activity_component() ) {
		return false;
	}

	$action = bp_current_action();
	if ( ! $action || ! is_numeric( $action ) ) {
		return false;
	}

	// Get the activity details.
	$activity = bp_activity_get_specific( array(
		'activity_ids' => $action,
		'show_hidden'  => true,
		'spam'         => 'ham_only',
	) );

	// 404 if activity does not exist.
	if ( empty( $activity['activities'][0] ) || bp_action_variables() ) {
		bp_do_404();
		return;

	} else {
		$activity = $activity['activities'][0];
	}

	/**
	 * Check user access to the activity item.
	 *
	 * @since 3.0.0
	 */
	$has_access = bp_activity_user_can_read( $activity );

	// If activity author does not match displayed user, block access.
	// More info:https://buddypress.trac.wordpress.org/ticket/7048#comment:28
	if ( true === $has_access && bp_displayed_user_id() !== $activity->user_id ) {
		$has_access = false;
	}

	/**
	 * Fires before the loading of a single activity template file.
	 *
	 * @since 1.2.0
	 *
	 * @param BP_Activity_Activity $activity   Object representing the current activity item being displayed.
	 * @param bool                 $has_access Whether or not the current user has access to view activity.
	 */
	do_action( 'bp_activity_screen_single_activity_permalink', $activity, $has_access );

	// Access is specifically disallowed.
	if ( false === $has_access ) {
		// If not logged in, prompt for login.
		if ( ! is_user_logged_in() ) {
			bp_core_no_access();

		// Redirect away.
		} else {
			bp_core_add_message( __( 'You do not have access to this activity.', 'buddypress' ), 'error' );
			bp_core_redirect( bp_loggedin_user_domain() );
		}
	}

	/**
	 * Filters the template to load for a single activity screen.
	 *
	 * @since 1.0.0
	 *
	 * @param string $template Path to the activity template to load.
	 */
	$template = apply_filters( 'bp_activity_template_profile_activity_permalink', 'members/single/activity/permalink' );

	// Load the template.
	bp_core_load_template( $template );
}
add_action( 'bp_screens', 'bp_activity_screen_single_activity_permalink' );