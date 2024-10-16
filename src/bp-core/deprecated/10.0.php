<?php
/**
 * Deprecated functions.
 *
 * @package BuddyPress
 * @deprecated 10.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Returns the name of the hook to use once a WordPress Site is inserted into the Database.
 *
 * WordPress 5.1.0 deprecated the `wpmu_new_blog` action. As BuddyPress is supporting WordPress back
 * to 4.9.0, this function makes sure we are using the new hook `wp_initialize_site` when the current
 * WordPress version is upper or equal to 5.1.0 and that we keep on using `wpmu_new_blog` for earlier
 * versions of WordPress.
 *
 * @since 6.0.0
 * @deprecated 10.0.0
 *
 * @return string The name of the hook to use.
 */
function bp_insert_site_hook() {
	_deprecated_function( __FUNCTION__, '10.0.0' );

	$wp_hook = 'wpmu_new_blog';

	if ( function_exists( 'wp_insert_site' ) ) {
		$wp_hook = 'wp_initialize_site';
	}

	return $wp_hook;
}

/**
 * Returns the name of the hook to use once a WordPress Site is deleted.
 *
 * WordPress 5.1.0 deprecated the `delete_blog` action. As BuddyPress is supporting WordPress back
 * to 4.9.0, this function makes sure we are using the new hook `wp_validate_site_deletion` when the
 * current WordPress version is upper or equal to 5.1.0 and that we keep on using `delete_blog` for
 * earlier versions of WordPress.
 *
 * @since 6.0.0
 * @deprecated 10.0.0
 *
 * @return string The name of the hook to use.
 */
function bp_delete_site_hook() {
	_deprecated_function( __FUNCTION__, '10.0.0' );

	$wp_hook = 'delete_blog';

	if ( function_exists( 'wp_delete_site' ) ) {
		$wp_hook = 'wp_validate_site_deletion';
	}

	return $wp_hook;
}

/**
 * Register the jQuery.ajax wrapper for BP REST API requests.
 *
 * @since 5.0.0
 * @deprecated 10.0.0
 */
function bp_rest_api_register_request_script() {
	_deprecated_function( __FUNCTION__, '10.0.0' );

	if ( ! bp_rest_api_is_available() ) {
		return;
	}

	wp_register_script(
		'bp-api-request',
		sprintf( '%1$sbp-core/js/bp-api-request%2$s.js', buddypress()->plugin_url, bp_core_get_minified_asset_suffix() ),
		array( 'jquery', 'wp-api-request' ),
		bp_get_version(),
		true
	);

	wp_localize_script(
		'bp-api-request',
		'bpApiSettings',
		array(
			'unexpectedError'   => __( 'An unexpected error occurred. Please try again.', 'buddypress' ),
			'deprecatedWarning' => __( 'The bp.apiRequest function is deprecated since BuddyPress 10.0.0, please use wp.apiRequest instead.', 'buddypress' ),
		)
	);
}
