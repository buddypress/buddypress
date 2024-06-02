<?php
/**
 * Blogs: Create screen handler
 *
 * @package BuddyPress
 * @subpackage BlogsScreens
 * @since 3.0.0
 */

/**
 * Load the "Create a Blog" screen.
 *
 * @since 1.0.0
 */
function bp_blogs_screen_create_a_blog() {

	if ( ! is_multisite() || ! bp_is_blogs_component() || ! bp_is_current_action( 'create' ) || ! is_user_logged_in() || ! bp_blog_signup_enabled() ) {
		return;
	}

	/**
	 * Fires right before the loading of the Create A Blog screen template file.
	 *
	 * @since 1.0.0
	 */
	do_action( 'bp_blogs_screen_create_a_blog' );

	bp_core_load_template( apply_filters( 'bp_blogs_template_create_a_blog', 'blogs/create' ) );
}
add_action( 'bp_screens', 'bp_blogs_screen_create_a_blog', 3 );
