<?php

/**
 * Deprecated functions.
 *
 * @package BuddyPress
 * @deprecated 7.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Display the group meta.
 *
 * @since 3.0.0
 * @deprecated 7.0.0 Use `bp_nouveau_the_group_meta()` instead.
 * @see bp_nouveau_the_group_meta()
 *
 * @return BP_Nouveau_Group_Meta|void The group meta object on single group screens.
 */
function bp_nouveau_group_meta() {
	_deprecated_function( __FUNCTION__, '7.0.0', 'bp_nouveau_the_group_meta()' );

	if ( ! class_exists( 'BP_Nouveau_Group_Meta' ) ) {
		return;
	}

	$group_meta = new BP_Nouveau_Group_Meta();

	if ( ! bp_is_group() ) {
		// phpcs:ignore WordPress.Security.EscapeOutput
		echo $group_meta->meta;
	} else {
		return $group_meta;
	}
}

/**
 * Mark the posted activity as spam, if it contains disallowed keywords.
 *
 * Use bp_activity_check_disallowed_keys() instead.
 *
 * @since 1.6.0
 * @deprecated 7.0.0
 *
 * @param BP_Activity_Activity $activity The activity object to check.
 */
function bp_activity_check_blacklist_keys( $activity ) {
	_deprecated_function( __FUNCTION__, '7.0.0', 'bp_activity_check_disallowed_keys()' );
	return bp_activity_check_disallowed_keys( $activity );
}

/**
 * Check for blocked keys.
 *
 * Use bp_core_check_for_disallowed_keys() instead.
 *
 * @since 1.6.0
 * @since 2.6.0 Added $error_type parameter.
 * @deprecated 7.0.0
 *
 * @param int    $user_id    Optional. User ID.
 * @param string $title      Optional. The title of the content.
 * @param string $content    Optional. The content being posted.
 * @param string $error_type Optional. The error type to return. Either 'bool' or 'wp_error'.
 * @return bool|WP_Error True if test is passed, false if fail.
 */
function bp_core_check_for_blacklist( $user_id = 0, $title = '', $content = '', $error_type = 'bool' ) {
	_deprecated_function( __FUNCTION__, '7.0.0', 'bp_core_check_for_disallowed_keys()' );
	return bp_core_check_for_disallowed_keys( $user_id, $title, $content, $error_type );
}
