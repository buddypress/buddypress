<?php
/**
 * Groups: Access protection action handler
 *
 * @package BuddyPress
 * @subpackage GroupActions
 * @since 3.0.0
 */

/**
 * Protect access to single groups.
 *
 * @since 2.1.0
 */
function bp_groups_group_access_protection() {
	if ( ! bp_is_group() ) {
		return;
	}

	$current_group   = groups_get_current_group();
	$user_has_access = $current_group->user_has_access;
	$is_visible      = $current_group->is_visible;
	$no_access_args  = array();

	// The user can know about the group but doesn't have full access.
	if ( ! $user_has_access && $is_visible ) {
		// Always allow access to home and request-membership.
		if ( bp_is_current_action( 'home' ) || bp_is_current_action( 'request-membership' ) ) {
			$user_has_access = true;

		// User doesn't have access, so set up redirect args.
		} elseif ( is_user_logged_in() ) {
			$no_access_args = array(
				'message'  => __( 'You do not have access to this group.', 'buddypress' ),
				'root'     => bp_get_group_permalink( $current_group ) . 'home/',
				'redirect' => false
			);
		}
	}

	// Protect the admin tab from non-admins.
	if ( bp_is_current_action( 'admin' ) && ! bp_is_item_admin() ) {
		$user_has_access = false;
		$no_access_args  = array(
			'message'  => __( 'You are not an admin of this group.', 'buddypress' ),
			'root'     => bp_get_group_permalink( $current_group ),
			'redirect' => false
		);
	}

	/**
	 * Allow plugins to filter whether the current user has access to this group content.
	 *
	 * Note that if a plugin sets $user_has_access to false, it may also
	 * want to change the $no_access_args, to avoid problems such as
	 * logged-in users being redirected to wp-login.php.
	 *
	 * @since 2.1.0
	 *
	 * @param bool  $user_has_access True if the user has access to the
	 *                               content, otherwise false.
	 * @param array $no_access_args  Arguments to be passed to bp_core_no_access() in case
	 *                               of no access. Note that this value is passed by reference,
	 *                               so it can be modified by the filter callback.
	 */
	$user_has_access = apply_filters_ref_array( 'bp_group_user_has_access', array( $user_has_access, &$no_access_args ) );

	// If user has access, we return rather than redirect.
	if ( $user_has_access ) {
		return;
	}

	// Groups that the user cannot know about should return a 404 for non-members.
	// Unset the current group so that you're not redirected
	// to the default group tab.
	if ( ! $is_visible ) {
		buddypress()->groups->current_group = 0;
		buddypress()->is_single_item        = false;
		bp_do_404();
		return;
	} else {
		bp_core_no_access( $no_access_args );
	}

}
add_action( 'bp_actions', 'bp_groups_group_access_protection' );