<?php
/**
 * Akismet support for BuddyPress' Activity Stream.
 *
 * @package BuddyPress
 * @subpackage Activity
 * @since 1.6.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Loads Akismet filtering for activity.
 *
 * @since 1.6.0
 * @since 2.3.0 We only support Akismet 3+.
 */
function bp_activity_setup_akismet() {
	/**
	 * Filters if BuddyPress Activity Akismet support has been disabled by another plugin.
	 *
	 * @since 1.6.0
	 *
	 * @param bool $value Return value of bp_is_akismet_active boolean function.
	 */
	if ( ! apply_filters( 'bp_activity_use_akismet', bp_is_akismet_active() ) ) {
		return;
	}

	// Instantiate Akismet for BuddyPress.
	buddypress()->activity->akismet = new BP_Akismet();
}
add_action( 'bp_activity_setup_globals', 'bp_activity_setup_akismet' );
