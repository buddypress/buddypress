<?php
/**
 * Blogs: User's "Sites" screen handler
 *
 * @package BuddyPress
 * @subpackage BlogsScreens
 * @since 3.0.0
 */

/**
 * Load the "My Blogs" screen.
 *
 * @since 1.0.0
 */
function bp_blogs_screen_my_blogs() {
	if ( ! is_multisite() ) {
		return;
	}

	/**
	 * Fires right before the loading of the My Blogs screen template file.
	 *
	 * @since 1.0.0
	 */
	do_action( 'bp_blogs_screen_my_blogs' );

	$templates = array(
		/**
		 * Filters the template to load for the "My blogs" screen.
		 *
		 * @since 1.0.0
		 *
		 * @param string $template Path to the activity template to load.
		 */
		apply_filters( 'bp_blogs_template_my_blogs', 'members/single/home' ),
		'members/single/index',
	);

	bp_core_load_template( $templates );
}
