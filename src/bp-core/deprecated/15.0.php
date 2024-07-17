<?php
/**
 * Deprecated functions.
 *
 * @package BuddyPress
 * @deprecated 15.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Should we use the REST Endpoints of built BuddyPress?
 *
 * If the BP REST plugin is active, it overrides BuddyPress REST endpoints.
 * This allows us to carry on maintaining all the BP REST API endpoints from
 * the BP REST plugin on GitHub.
 *
 * Since 15.0.0, the REST Endpoints are baked into BuddyPress core.
 *
 * @since 5.0.0
 * @deprecated 15.0.0
 *
 * @return bool Whether to use the REST Endpoints of built BuddyPress.
 */
function bp_rest_in_buddypress() {
	_deprecated_function( __FUNCTION__, '15.0.0' );

	return true;
}

/**
 * Is the BP REST plugin is active?
 *
 * Since 15.0.0, the REST API plugin was baked into BuddyPress core.
 *
 * @since 5.0.0
 * @deprecated 15.0.0
 *
 * @return bool True if the BP REST plugin is active. False otherwise.
 */
function bp_rest_is_plugin_active() {
	_deprecated_function( __FUNCTION__, '15.0.0' );

	return (bool) has_action( 'bp_rest_api_init', 'bp_rest' );
}
