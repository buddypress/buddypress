<?php
/**
 * BuddyPress Settings Loader.
 *
 * @package BuddyPress
 * @subpackage SettingsLoader
 * @since 1.5.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Set up the bp-settings component.
 *
 * @since 1.6.0
 */
function bp_setup_settings() {
	buddypress()->settings = new BP_Settings_Component();
}
add_action( 'bp_setup_components', 'bp_setup_settings', 6 );
