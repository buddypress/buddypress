<?php

/**
 * Handles the display of the profile page by loading the correct template file.
 *
 * @package BuddyPress Members
 * @uses bp_core_load_template() Looks for and loads a template file within the current member theme (folder/filename)
 */
function bp_members_screen_display_profile() {
	do_action( 'bp_members_screen_display_profile' );
	bp_core_load_template( apply_filters( 'bp_members_screen_display_profile', 'members/single/home' ) );
}

function bp_members_screen_index() {
	global $bp;

	if ( !bp_is_user() && bp_is_current_component( 'members' ) ) {
		$bp->is_directory = true;

		do_action( 'bp_members_screen_index' );

		bp_core_load_template( apply_filters( 'bp_members_screen_index', 'members/index' ) );
	}
}
add_action( 'bp_screens', 'bp_members_screen_index' );


?>
