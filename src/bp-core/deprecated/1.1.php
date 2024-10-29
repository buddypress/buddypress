<?php
/**
 * Deprecated Functions
 *
 * @package BuddyPress
 * @deprecated 1.1.0
 */

/**
 * Output whether signup is allowed.
 *
 * @since 1.1.0
 * @deprecated 15.0.0
 */
function bp_signup_allowed() {
	// phpcs:ignore WordPress.Security.EscapeOutput
	_deprecated_function( __FUNCTION__, '15.0.0', 'bp_get_signup_allowed()' );

	echo bp_get_signup_allowed();
}
