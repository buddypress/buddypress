<?php
/**
 * Activity: User's "Activity > Likes" screen handler
 *
 * @package BuddyPress
 * @subpackage ActivityScreens
 * @since 14.0.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Load the 'Likes' activity page.
 *
 * @since 14.0.0
 */
function bp_activity_screen_likes() {
	bp_update_is_item_admin( bp_current_user_can( 'bp_moderate' ), 'activity' );

	/**
	 * Fires right before the loading of the "Likes" screen template file.
	 *
	 * @since 14.0.0
	 */
	do_action( 'bp_activity_screen_likes' );

	/**
	 * Filters the template to load for the "Likes" screen.
	 *
	 * @since 14.0.0
	 *
	 * @param string $template Path to the activity template to load.
	 */
	bp_core_load_template( apply_filters( 'bp_activity_template_activity_likes', 'members/single/home' ) );
}
