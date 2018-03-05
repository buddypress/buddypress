<?php
/**
 * Activity: Directory screen handler
 *
 * @package BuddyPress
 * @subpackage ActivityScreens
 * @since 3.0.0
 */

/**
 * Load the Activity directory.
 *
 * @since 1.5.0
 *
 */
function bp_activity_screen_index() {
	if ( bp_is_activity_directory() ) {
		bp_update_is_directory( true, 'activity' );

		/**
		 * Fires right before the loading of the Activity directory screen template file.
		 *
		 * @since 1.5.0
		 */
		do_action( 'bp_activity_screen_index' );

		/**
		 * Filters the template to load for the Activity directory screen.
		 *
		 * @since 1.5.0
		 *
		 * @param string $template Path to the activity template to load.
		 */
		bp_core_load_template( apply_filters( 'bp_activity_screen_index', 'activity/index' ) );
	}
}
add_action( 'bp_screens', 'bp_activity_screen_index' );