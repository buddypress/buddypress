<?php
/**
 * BuddyPress Blogs Screens.
 *
 * @package BuddyPress
 * @subpackage BlogsScreens
 * @since 1.5.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

if ( ! buddypress()->do_autoload ) {
	require dirname( __FILE__ ) . '/classes/class-bp-blogs-theme-compat.php';
}

/**
 * Load the "My Blogs" screen.
 */
function bp_blogs_screen_my_blogs() {
	if ( !is_multisite() )
		return false;

	/**
	 * Fires right before the loading of the My Blogs screen template file.
	 *
	 * @since 1.0.0
	 */
	do_action( 'bp_blogs_screen_my_blogs' );

	bp_core_load_template( apply_filters( 'bp_blogs_template_my_blogs', 'members/single/home' ) );
}

/**
 * Load the "Create a Blog" screen.
 */
function bp_blogs_screen_create_a_blog() {

	if ( !is_multisite() ||  !bp_is_blogs_component() || !bp_is_current_action( 'create' ) )
		return false;

	if ( !is_user_logged_in() || !bp_blog_signup_enabled() )
		return false;

	/**
	 * Fires right before the loading of the Create A Blog screen template file.
	 *
	 * @since 1.0.0
	 */
	do_action( 'bp_blogs_screen_create_a_blog' );

	bp_core_load_template( apply_filters( 'bp_blogs_template_create_a_blog', 'blogs/create' ) );
}
add_action( 'bp_screens', 'bp_blogs_screen_create_a_blog', 3 );

/**
 * Load the top-level Blogs directory.
 */
function bp_blogs_screen_index() {
	if ( bp_is_blogs_directory() ) {
		bp_update_is_directory( true, 'blogs' );

		/**
		 * Fires right before the loading of the top-level Blogs screen template file.
		 *
		 * @since 1.0.0
		 */
		do_action( 'bp_blogs_screen_index' );

		bp_core_load_template( apply_filters( 'bp_blogs_screen_index', 'blogs/index' ) );
	}
}
add_action( 'bp_screens', 'bp_blogs_screen_index', 2 );

/** Theme Compatibility *******************************************************/

new BP_Blogs_Theme_Compat();
