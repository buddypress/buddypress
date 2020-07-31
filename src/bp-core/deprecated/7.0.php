<?php

/**
 * Deprecated functions.
 *
 * @deprecated 7.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

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
 * @param int    $user_id    User ID.
 * @param string $title      The title of the content.
 * @param string $content    The content being posted.
 * @param string $error_type The error type to return. Either 'bool' or 'wp_error'.
 * @return bool|WP_Error True if test is passed, false if fail.
 */
function bp_core_check_for_blacklist( $user_id = 0, $title = '', $content = '', $error_type = 'bool' ) {
	_deprecated_function( __FUNCTION__, '7.0.0', 'bp_core_check_for_disallowed_keys()' );
	return bp_core_check_for_disallowed_keys( $user_id, $title, $content, $error_type );
}
