<?php
/**
 * Members: Directory screen handler
 *
 * @package BuddyPress
 * @subpackage MembersScreens
 * @since 3.0.0
 */

/**
 * Handle the display of the members directory index.
 *
 * @since 1.5.0
 */
function bp_members_screen_index() {
	if ( bp_is_members_directory() ) {
		bp_update_is_directory( true, 'members' );

		/**
		 * Fires right before the loading of the Member directory index screen template file.
		 *
		 * @since 1.5.0
		 */
		do_action( 'bp_members_screen_index' );

		/**
		 * Filters the template to load for the Member directory page screen.
		 *
		 * @since 1.5.0
		 *
		 * @param string $value Path to the member directory template to load.
		 */
		bp_core_load_template( apply_filters( 'bp_members_screen_index', 'members/index' ) );
	}
}
add_action( 'bp_screens', 'bp_members_screen_index' );