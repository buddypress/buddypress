<?php
/**
 * Activity: User's "Activity > Favorites" screen handler
 *
 * @package BuddyPress
 * @subpackage ActivityScreens
 * @since 3.0.0
 */

/**
 * Load the 'Favorites' activity page.
 *
 * @since 1.2.0
 */
function bp_activity_screen_favorites() {
	bp_update_is_item_admin( bp_current_user_can( 'bp_moderate' ), 'activity' );

	/**
	 * Fires right before the loading of the "Favorites" screen template file.
	 *
	 * @since 1.2.0
	 */
	do_action( 'bp_activity_screen_favorites' );

	$templates = array(
		/**
		 * Filters the template to load for the "Favorites" screen.
		 *
		 * @since 1.2.0
		 *
		 * @param string $template Path to the activity template to load.
		 */
		apply_filters( 'bp_activity_template_favorite_activity', 'members/single/home' ),
		'members/single/index',
	);


	bp_core_load_template( $templates );
}
