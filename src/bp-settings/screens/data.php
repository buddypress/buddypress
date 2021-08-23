<?php
/**
 * Settings: User's "Settings > Export Data" screen handler.
 *
 * @package BuddyPress
 * @subpackage SettingsScreens
 * @since 4.0.0
 */

/**
 * Show the data settings template.
 *
 * @since 4.0.0
 */
function bp_settings_screen_data() {

	if ( bp_action_variables() ) {
		bp_do_404();
		return;
	}

	/**
	 * Filters the template file path to use for the data settings screen.
	 *
	 * @since 4.0.0
	 *
	 * @param string $value Directory path to look in for the template file.
	 */
	bp_core_load_template( apply_filters( 'bp_settings_screen_data', 'members/single/settings/data' ) );
}
