<?php
/**
 * Groups: User's "Groups" screen handler
 *
 * @package BuddyPress
 * @subpackage GroupScreens
 * @since 3.0.0
 */

/**
 * Handle the loading of the My Groups page.
 *
 * @since 1.0.0
 */
function groups_screen_my_groups() {

	/**
	 * Fires before the loading of the My Groups page.
	 *
	 * @since 1.1.0
	 */
	do_action( 'groups_screen_my_groups' );

	$templates = array(
		/**
		 * Filters the template to load for the My Groups page.
		 *
		 * @since 1.0.0
		 *
		 * @param string $value Path to the My Groups page template to load.
		 */
		apply_filters( 'groups_template_my_groups', 'members/single/home' ),
		'members/single/index',
	);

	bp_core_load_template( $templates );
}
