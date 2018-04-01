<?php
/**
 * Groups: Directory screen handler
 *
 * @package BuddyPress
 * @subpackage GroupScreens
 * @since 3.0.0
 */

/**
 * Handle the display of the Groups directory index.
 *
 * @since 1.0.0
 */
function groups_directory_groups_setup() {
	if ( bp_is_groups_directory() ) {
		bp_update_is_directory( true, 'groups' );

		/**
		 * Fires before the loading of the Groups directory index.
		 *
		 * @since 1.1.0
		 */
		do_action( 'groups_directory_groups_setup' );

		/**
		 * Filters the template to load for the Groups directory index.
		 *
		 * @since 1.0.0
		 *
		 * @param string $value Path to the groups directory index template to load.
		 */
		bp_core_load_template( apply_filters( 'groups_template_directory_groups', 'groups/index' ) );
	}
}
add_action( 'bp_screens', 'groups_directory_groups_setup', 2 );