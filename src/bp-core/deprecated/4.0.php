<?php
/**
 * Deprecated functions.
 *
 * @package BuddyPress
 * @deprecated 4.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Print moment.js config in page footer.
 *
 * @since 2.7.0
 * @deprecated 4.0.0 BuddyPress requires WordPress 4.6 or later.
 *
 * @access private
 */
function _bp_core_moment_js_config_footer() {
	_deprecated_function( __FUNCTION__, '4.0' );
}
