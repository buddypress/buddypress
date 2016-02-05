<?php
/**
 * BuddyPress Blogs Theme Compatibility.
 *
 * @package BuddyPress
 * @subpackage BlogsScreens
 * @since 1.5.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * The main theme compat class for BuddyPress Blogs.
 *
 * This class sets up the necessary theme compatibility actions to safely output
 * group template parts to the_title and the_content areas of a theme.
 *
 * @since 1.7.0
 */
class BP_Blogs_Theme_Compat {

	/**
	 * Set up theme compatibility for the Blogs component.
	 *
	 * @since 1.7.0
	 */
	public function __construct() {
		add_action( 'bp_setup_theme_compat', array( $this, 'is_blogs' ) );
	}

	/**
	 * Are we looking at something that needs Blogs theme compatibility?
	 *
	 * @since 1.7.0
	 */
	public function is_blogs() {

		// Bail if not looking at a group.
		if ( ! bp_is_blogs_component() )
			return;

		// Bail if looking at a users sites.
		if ( bp_is_user() )
			return;

		// Blog Directory.
		if ( is_multisite() && ! bp_current_action() ) {
			bp_update_is_directory( true, 'blogs' );

			/**
			 * Fires if in the blog directory and BuddyPress needs Blog theme compatibility,
			 * before the actions and filters are added.
			 *
			 * @since 1.5.0
			 */
			do_action( 'bp_blogs_screen_index' );

			add_filter( 'bp_get_buddypress_template',                array( $this, 'directory_template_hierarchy' ) );
			add_action( 'bp_template_include_reset_dummy_post_data', array( $this, 'directory_dummy_post' ) );
			add_filter( 'bp_replace_the_content',                    array( $this, 'directory_content'    ) );

		// Create blog.
		} elseif ( is_user_logged_in() && bp_blog_signup_enabled() ) {
			add_filter( 'bp_get_buddypress_template',                array( $this, 'create_template_hierarchy' ) );
			add_action( 'bp_template_include_reset_dummy_post_data', array( $this, 'create_dummy_post' ) );
			add_filter( 'bp_replace_the_content',                    array( $this, 'create_content'    ) );
		}
	}

	/** Directory *************************************************************/

	/**
	 * Add template hierarchy to theme compat for the blog directory page.
	 *
	 * This is to mirror how WordPress has
	 * {@link https://codex.wordpress.org/Template_Hierarchy template hierarchy}.
	 *
	 * @since 1.8.0
	 *
	 * @param string $templates The templates from bp_get_theme_compat_templates().
	 * @return array $templates Array of custom templates to look for.
	 */
	public function directory_template_hierarchy( $templates ) {

		/**
		 * Filters the custom templates used for theme compat with the blog directory page.
		 *
		 * @since 1.8.0
		 *
		 * @param array $value Array of template paths to add to template list to look for.
		 */
		$new_templates = apply_filters( 'bp_template_hierarchy_blogs_create', array(
			'blogs/index-directory.php'
		) );

		// Merge new templates with existing stack
		// @see bp_get_theme_compat_templates().
		$templates = array_merge( (array) $new_templates, $templates );

		return $templates;
	}

	/**
	 * Update the global $post with directory data.
	 *
	 * @since 1.7.0
	 */
	public function directory_dummy_post() {

		bp_theme_compat_reset_post( array(
			'ID'             => 0,
			'post_title'     => __( 'Sites', 'buddypress' ),
			'post_author'    => 0,
			'post_date'      => 0,
			'post_content'   => '',
			'post_type'      => 'page',
			'post_status'    => 'publish',
			'is_page'        => true,
			'comment_status' => 'closed'
		) );
	}

	/**
	 * Filter the_content with the groups index template part.
	 *
	 * @since 1.7.0
	 */
	public function directory_content() {
		return bp_buffer_template_part( 'blogs/index', null, false );
	}

	/** Create ****************************************************************/

	/**
	 * Add custom template hierarchy to theme compat for the blog create page.
	 *
	 * This is to mirror how WordPress has
	 * {@link https://codex.wordpress.org/Template_Hierarchy template hierarchy}.
	 *
	 * @since 1.8.0
	 *
	 * @param string $templates The templates from bp_get_theme_compat_templates().
	 * @return array $templates Array of custom templates to look for.
	 */
	public function create_template_hierarchy( $templates ) {

		/**
		 * Filters the custom templates used for theme compat with the blog create page.
		 *
		 * @since 1.8.0
		 *
		 * @param array $value Array of template paths to add to template list to look for.
		 */
		$new_templates = apply_filters( 'bp_template_hierarchy_blogs_create', array(
			'blogs/index-create.php'
		) );

		// Merge new templates with existing stack
		// @see bp_get_theme_compat_templates().
		$templates = array_merge( (array) $new_templates, $templates );

		return $templates;
	}

	/**
	 * Update the global $post with create screen data.
	 *
	 * @since 1.7.0
	 */
	public function create_dummy_post() {

		// Title based on ability to create blogs.
		if ( is_user_logged_in() && bp_blog_signup_enabled() ) {
			$title = __( 'Create a Site', 'buddypress' );
		} else {
			$title = __( 'Sites', 'buddypress' );
		}

		bp_theme_compat_reset_post( array(
			'ID'             => 0,
			'post_title'     => $title,
			'post_author'    => 0,
			'post_date'      => 0,
			'post_content'   => '',
			'post_type'      => 'page',
			'post_status'    => 'publish',
			'is_page'        => true,
			'comment_status' => 'closed'
		) );
	}

	/**
	 * Filter the_content with the create screen template part.
	 *
	 * @since 1.7.0
	 */
	public function create_content() {
		return bp_buffer_template_part( 'blogs/create', null, false );
	}
}
