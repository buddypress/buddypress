<?php
/**
 * BuddyPress Member Screens
 *
 * Handlers for member screens that aren't handled elsewhere
 *
 * @package BuddyPress
 * @subpackage Members
 */

// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

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

/**
 * Handles the display of the members directory index
 *
 * @global object $bp
 *
 * @uses bp_is_user()
 * @uses bp_is_current_component()
 * @uses do_action()
 * @uses bp_core_load_template()
 * @uses apply_filters()
 */
function bp_members_screen_index() {
	if ( !bp_is_user() && bp_is_members_component() ) {
		bp_update_is_directory( true, 'members' );

		do_action( 'bp_members_screen_index' );

		bp_core_load_template( apply_filters( 'bp_members_screen_index', 'members/index' ) );
	}
}
add_action( 'bp_screens', 'bp_members_screen_index' );


?>
