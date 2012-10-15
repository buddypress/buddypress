<?php

/**
 * BuddyPress Blogs Screens
 *
 * @package BuddyPress
 * @subpackage BlogsScreens
 */

// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

function bp_blogs_screen_my_blogs() {
	if ( !is_multisite() )
		return false;

	do_action( 'bp_blogs_screen_my_blogs' );

	bp_core_load_template( apply_filters( 'bp_blogs_template_my_blogs', 'members/single/home' ) );
}

function bp_blogs_screen_create_a_blog() {

	if ( !is_multisite() ||  !bp_is_blogs_component() || !bp_is_current_action( 'create' ) )
		return false;

	if ( !is_user_logged_in() || !bp_blog_signup_enabled() )
		return false;

	do_action( 'bp_blogs_screen_create_a_blog' );

	bp_core_load_template( apply_filters( 'bp_blogs_template_create_a_blog', 'blogs/create' ) );
}
add_action( 'bp_screens', 'bp_blogs_screen_create_a_blog', 3 );

function bp_blogs_screen_index() {
	if ( is_multisite() && bp_is_blogs_component() && !bp_current_action() ) {
		bp_update_is_directory( true, 'blogs' );

		do_action( 'bp_blogs_screen_index' );

		bp_core_load_template( apply_filters( 'bp_blogs_screen_index', 'blogs/index' ) );
	}
}
add_action( 'bp_screens', 'bp_blogs_screen_index', 2 );

/** Theme Compatability *******************************************************/

/**
 * The main theme compat class for BuddyPress Activity
 *
 * This class sets up the necessary theme compatability actions to safely output
 * group template parts to the_title and the_content areas of a theme.
 *
 * @since BuddyPress (1.7)
 */
class BP_Blogs_Theme_Compat {

	/**
	 * Setup the groups component theme compatibility
	 *
	 * @since BuddyPress (1.7)
	 */
	public function __construct() {
		add_action( 'bp_setup_theme_compat', array( $this, 'is_blogs' ) );
	}

	/**
	 * Are we looking at something that needs group theme compatability?
	 *
	 * @since BuddyPress (1.7)
	 */
	public function is_blogs() {

		// Bail if not looking at a group
		if ( ! bp_is_blogs_component() )
			return;

		// Bail if looking at a users sites
		if ( bp_is_user() )
			return;

		// Blog Directory
		if ( is_multisite() && ! bp_current_action() ) {
			bp_update_is_directory( true, 'blogs' );

			do_action( 'bp_blogs_screen_index' );

			add_action( 'bp_template_include_reset_dummy_post_data', array( $this, 'directory_dummy_post' ) );
			add_filter( 'bp_replace_the_content',                    array( $this, 'directory_content'    ) );

		// Create blog
		} elseif ( is_user_logged_in() && bp_blog_signup_enabled() ) {
			add_action( 'bp_template_include_reset_dummy_post_data', array( $this, 'create_dummy_post' ) );
			add_filter( 'bp_replace_the_content',                    array( $this, 'create_content'    ) );			
		}
	}

	/** Directory *************************************************************/

	/**
	 * Update the global $post with directory data
	 *
	 * @since BuddyPress (1.7)
	 */
	public function directory_dummy_post() {

		// Title based on ability to create blogs
		if ( is_user_logged_in() && bp_blog_signup_enabled() ) {
			$title = __( 'Blogs', 'buddypress' ) . '&nbsp;<a class="button" href="' . trailingslashit( bp_get_root_domain() . '/' . bp_get_blogs_root_slug() . '/create' ) . '">' . __( 'Create a Blog', 'buddypress' ) . '</a>';
		} else {
			$title = __( 'Blogs', 'buddypress' );
		}

		bp_theme_compat_reset_post( array(
			'ID'             => 0,
			'post_title'     => $title,
			'post_author'    => 0,
			'post_date'      => 0,
			'post_content'   => '',
			'post_type'      => 'bp_blogs',
			'post_status'    => 'publish',
			'is_archive'     => true,
			'comment_status' => 'closed'
		) );
	}

	/**
	 * Filter the_content with the groups index template part
	 *
	 * @since BuddyPress (1.7)
	 */
	public function directory_content() {
		bp_buffer_template_part( 'blogs/index' );
	}
	
	/** Create ****************************************************************/

	/**
	 * Update the global $post with create screen data
	 *
	 * @since BuddyPress (1.7)
	 */
	public function create_dummy_post() {

		// Title based on ability to create blogs
		if ( is_user_logged_in() && bp_blog_signup_enabled() ) {
			$title = '<a class="button bp-title-button" href="' . trailingslashit( bp_get_root_domain() . '/' . bp_get_blogs_root_slug() ) . '">' . __( 'Blogs', 'buddypress' ) . '</a>&nbsp;' . __( 'Create a Blog', 'buddypress' );
		} else {
			$title = __( 'Blogs', 'buddypress' );
		}

		bp_theme_compat_reset_post( array(
			'ID'             => 0,
			'post_title'     => $title,
			'post_author'    => 0,
			'post_date'      => 0,
			'post_content'   => '',
			'post_type'      => 'bp_group',
			'post_status'    => 'publish',
			'is_archive'     => true,
			'comment_status' => 'closed'
		) );
	}

	/**
	 * Filter the_content with the create screen template part
	 *
	 * @since BuddyPress (1.7)
	 */
	public function create_content() {
		bp_buffer_template_part( 'blogs/create' );
	}
}
new BP_Blogs_Theme_Compat();
