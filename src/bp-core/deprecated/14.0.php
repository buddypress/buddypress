<?php
/**
 * Deprecated functions.
 *
 * @package BuddyPress
 * @deprecated 14.0.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Select the right `block_editor_settings` filter according to WP version.
 *
 * @since 8.0.0
 * @deprecated 14.0.0
 */
function bp_block_init_editor_settings_filter() {
	_deprecated_function( __FUNCTION__, '14.0.0' );
}

/**
 * Select the right `block_categories` filter according to WP version.
 *
 * @since 8.0.0
 * @since 12.0.0 This category is left for third party plugin but not used anymmore.
 * @deprecated 14.0.0
 */
function bp_block_init_category_filter() {
	_deprecated_function( __FUNCTION__, '14.0.0' );
}

/**
 * Should we use the WP Toolbar?
 *
 * The WP Toolbar, introduced in WP 3.1, is fully supported in BuddyPress as
 * of BP 1.5. For BP 1.6, the WP Toolbar is the default.
 *
 * @since 1.5.0
 * @deprecated 14.0.0
 *
 * @return bool Default: true. False when WP Toolbar support is disabled.
 */
function bp_use_wp_admin_bar() {
	_deprecated_function( __FUNCTION__, '14.0.0' );

	// Default to true.
	$use_admin_bar = true;

	if ( defined( 'BP_USE_WP_ADMIN_BAR' ) ) {
		_doing_it_wrong( 'BP_USE_WP_ADMIN_BAR', esc_html__( 'The BP_USE_WP_ADMIN_BAR constant is deprecated.', 'buddypress' ), 'BuddyPress 14.0.0' );
	}

	/**
	 * Filters whether or not to use the admin bar.
	 *
	 * @since 1.5.0
	 * @deprecated 14.0.0
	 *
	 * @param bool $use_admin_bar Whether or not to use the admin bar.
	 */
	return apply_filters_deprecated( 'bp_use_wp_admin_bar', array( $use_admin_bar ), '14.0.0' );
}
