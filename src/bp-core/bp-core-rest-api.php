<?php
/**
 * Core REST API functions.
 *
 * @package BuddyPress
 * @subpackage Core
 * @since 5.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Is the BP REST plugin is active?
 *
 * @since 5.0.0
 *
 * @return boolean True if the BP REST plugin is active. False otherwise.
 */
function bp_rest_is_plugin_active() {
    return (bool) has_action( 'bp_rest_api_init', 'bp_rest', 5 );
}

/**
 * Check the availability of the BP REST API.
 *
 * @since 5.0.0
 *
 * @return boolean True if the BP REST API is available. False otherwise.
 */
function bp_rest_api_is_available() {
    /**
     * Filter here to disable the BP REST API.
     *
     * The BP REST API requires at least WordPress 4.7.0
     *
     * @since 5.0.0
     *
     * @param boolean $value True if the BP REST API is available. False otherwise.
     */
    return apply_filters( 'bp_rest_api_is_available', function_exists( 'create_initial_rest_routes' ) && bp_rest_is_plugin_active() );
}

/**
 * Register the jQuery.ajax wrapper for BP REST API requests.
 *
 * @since 5.0.0
 */
function bp_rest_api_register_request_script() {
    if ( ! bp_rest_api_is_available() ) {
        return;
    }

    $dependencies = array( 'jquery' );

    // The wrapper for WP REST API requests was introduced in WordPress 4.9.0
    if ( wp_script_is( 'wp-api-request', 'registered' ) ) {
        $dependencies = array( 'wp-api-request' );
    }

    wp_register_script(
        'bp-api-request',
        sprintf( '%1$sbp-core/js/bp-api-request%2$s.js', buddypress()->plugin_url, bp_core_get_minified_asset_suffix() ),
        $dependencies,
        bp_get_version(),
        true
    );
    wp_localize_script(
        'bp-api-request',
        'bpApiSettings',
        array(
            'root'  => esc_url_raw( get_rest_url() ),
            'nonce' => wp_create_nonce( 'wp_rest' ),
        )
    );
}
add_action( 'bp_init', 'bp_rest_api_register_request_script' );
