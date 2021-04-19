<?php

/**
 * Deprecated functions.
 *
 * @deprecated 8.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Format 'new_avatar' activity actions.
 *
 * @since 2.0.0
 * @deprecated 8.0.0
 *
 * @param string $action   Static activity action.
 * @param object $activity Activity object.
 * @return string
 */
function bp_xprofile_format_activity_action_new_avatar( $action, $activity ) {
	_deprecated_function( __FUNCTION__, '8.0.0', 'bp_members_format_activity_action_new_avatar()' );
	return bp_members_format_activity_action_new_avatar( $action, $activity );
}

/**
 * Adds an activity stream item when a user has uploaded a new avatar.
 *
 * @since 1.0.0
 * @since 2.3.4 Add new parameter to get the user id the avatar was set for.
 * @deprecated 8.0.0
 *
 * @param int $user_id The user id the avatar was set for.
 * @return bool
 */
function bp_xprofile_new_avatar_activity( $user_id = 0 ) {
	_deprecated_function( __FUNCTION__, '8.0.0', 'bp_members_new_avatar_activity()' );
	return bp_members_new_avatar_activity( $user_id );
}

/**
 * Should the old BuddyBar be forced in place of the WP admin bar?
 *
 * We deprecated the BuddyBar in v2.1.0, but have completely removed it in
 * v8.0.
 *
 * @since 1.6.0
 * @deprecated 8.0.0
 *
 * @return bool
 */
function bp_force_buddybar() {
	_deprecated_function( __FUNCTION__, '8.0' );

	return false;
}
