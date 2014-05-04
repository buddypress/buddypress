<?php

/**
 * BuddyPress Settings Screens
 *
 * @package BuddyPress
 * @subpackage SettingsScreens
 */

// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

/**
 * Show the general settings template
 *
 * @since BuddyPress (1.5)
 */
function bp_settings_screen_general() {

	if ( bp_action_variables() ) {
		bp_do_404();
		return;
	}

	bp_core_load_template( apply_filters( 'bp_settings_screen_general_settings', 'members/single/settings/general' ) );
}

/**
 * Show the notifications settings template
 *
 * @since BuddyPress (1.5)
 */
function bp_settings_screen_notification() {

	if ( bp_action_variables() ) {
		bp_do_404();
		return;
	}

	bp_core_load_template( apply_filters( 'bp_settings_screen_notification_settings', 'members/single/settings/notifications' ) );
}

/**
 * Show the delete-account settings template
 *
 * @since BuddyPress (1.5)
 */
function bp_settings_screen_delete_account() {

	if ( bp_action_variables() ) {
		bp_do_404();
		return;
	}

	// Load the template
	bp_core_load_template( apply_filters( 'bp_settings_screen_delete_account', 'members/single/settings/delete-account' ) );
}

/**
 * Show the capabilities settings template
 *
 * @since BuddyPress (1.6)
 */
function bp_settings_screen_capabilities() {

	if ( bp_action_variables() ) {
		bp_do_404();
		return;
	}

	// Load the template
	bp_core_load_template( apply_filters( 'bp_settings_screen_capabilities', 'members/single/settings/capabilities' ) );
}
