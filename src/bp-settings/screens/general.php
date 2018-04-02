<?php
/**
 * Settings: User's "Settings" screen handler
 *
 * @package BuddyPress
 * @subpackage SettingsScreens
 * @since 3.0.0
 */

/**
 * Show the general settings template.
 *
 * @since 1.5.0
 */
function bp_settings_screen_general() {

	if ( bp_action_variables() ) {
		bp_do_404();
		return;
	}

	/**
	 * Filters the template file path to use for the general settings screen.
	 *
	 * @since 1.6.0
	 *
	 * @param string $value Directory path to look in for the template file.
	 */
	bp_core_load_template( apply_filters( 'bp_settings_screen_general_settings', 'members/single/settings/general' ) );
}

/**
 * Removes 'Email' sub nav, if no component has registered options there.
 *
 * @since 2.2.0
 */
function bp_settings_remove_email_subnav() {
	if ( ! has_action( 'bp_notification_settings' ) ) {
		bp_core_remove_subnav_item( BP_SETTINGS_SLUG, 'notifications' );
	}
}
add_action( 'bp_actions', 'bp_settings_remove_email_subnav' );