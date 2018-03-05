<?php
/**
 * Activity: User's "Activity > Groups" screen handler
 *
 * @package BuddyPress
 * @subpackage ActivityScreens
 * @since 3.0.0
 */

/**
 * Load the 'My Groups' activity page.
 *
 * @since 1.2.0
 */
function bp_activity_screen_groups() {
	if ( !bp_is_active( 'groups' ) )
		return false;

	bp_update_is_item_admin( bp_current_user_can( 'bp_moderate' ), 'activity' );

	/**
	 * Fires right before the loading of the "My Groups" screen template file.
	 *
	 * @since 1.2.0
	 */
	do_action( 'bp_activity_screen_groups' );

	/**
	 * Filters the template to load for the "My Groups" screen.
	 *
	 * @since 1.2.0
	 *
	 * @param string $template Path to the activity template to load.
	 */
	bp_core_load_template( apply_filters( 'bp_activity_template_groups_activity', 'members/single/home' ) );
}