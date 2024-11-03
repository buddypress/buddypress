<?php
/**
 * Deprecated Functions
 *
 * @package BuddyPress
 * @deprecated 15.0.0
 */

/**
 * Output whether signup is allowed.
 *
 * @since 1.1.0
 * @deprecated 15.0.0
 */
function bp_signup_allowed() {
	_deprecated_function( __FUNCTION__, '15.0.0', 'bp_get_signup_allowed()' );

	// phpcs:ignore WordPress.Security.EscapeOutput
	echo bp_get_signup_allowed();
}
