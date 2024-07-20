<?php
/**
 * Deprecated functions.
 *
 * @package BuddyPress
 * @deprecated 14.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

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

/**
 * In emails editor, add notice linking to token documentation on Codex.
 *
 * @since 2.5.0
 * @deprecated 14.0.0
 */
function bp_admin_email_add_codex_notice() {
	_deprecated_function( __FUNCTION__, '14.0.0' );

	if ( get_current_screen()->post_type !== bp_get_email_post_type() ) {
		return;
	}

	bp_core_add_admin_notice(
		sprintf(
			// Translators: %s is the url to the BuddyPress codex page about BP Email tokens.
			__( 'Phrases wrapped in braces <code>{{ }}</code> are email tokens. <a href="%s">Learn about tokens on the BuddyPress Codex</a>.', 'buddypress' ),
			esc_url( 'https://codex.buddypress.org/emails/email-tokens/' )
		),
		'error'
	);
}

/**
 * Handle save/update of screen options for the Activity component admin screen.
 *
 * @since 1.6.0
 * @deprecated 14.0.0
 *
 * @param string $value     Will always be false unless another plugin filters it first.
 * @param string $option    Screen option name.
 * @param string $new_value Screen option form value.
 * @return string|int Option value. False to abandon update.
 */
function bp_activity_admin_screen_options( $value, $option, $new_value ) {
	_deprecated_function( __FUNCTION__, '14.0.0', 'bp_admin_set_screen_options' );

	return bp_admin_set_screen_options( $value, $option, $new_value );
}

/**
 * Handle save/update of screen options for the Groups component admin screen.
 *
 * @since 1.7.0
 * @deprecated 14.0.0
 *
 * @param string $value     Will always be false unless another plugin filters it first.
 * @param string $option    Screen option name.
 * @param string $new_value Screen option form value.
 * @return string|int Option value. False to abandon update.
 */
function bp_groups_admin_screen_options( $value, $option, $new_value ) {
	_deprecated_function( __FUNCTION__, '14.0.0', 'bp_admin_set_screen_options' );

	return bp_admin_set_screen_options( $value, $option, $new_value );
}
