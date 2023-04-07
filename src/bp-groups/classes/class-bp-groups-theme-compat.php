<?php
/**
 * BuddyPress Groups Theme Compat.
 *
 * @package BuddyPress
 * @since 1.7.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * The main theme compat class for BuddyPress Groups.
 *
 * This class sets up the necessary theme compatibility actions to safely output
 * group template parts to the_title and the_content areas of a theme.
 *
 * @since 1.7.0
 */
class BP_Groups_Theme_Compat {

	/**
	 * Set up theme compatibility for the Groups component.
	 *
	 * @since 1.7.0
	 */
	public function __construct() {
		add_action( 'bp_setup_theme_compat', array( $this, 'is_group' ) );
	}

	/**
	 * Are we looking at something that needs group theme compatibility?
	 *
	 * @since 1.7.0
	 */
	public function is_group() {

		// Bail if not looking at a group.
		if ( ! bp_is_groups_component() ) {
			return;
		}

		// Group Directory.
		if ( bp_is_groups_directory() ) {
			bp_update_is_directory( true, 'groups' );

			/**
			 * Fires at the start of the group theme compatibility setup.
			 *
			 * @since 1.1.0
			 */
			do_action( 'groups_directory_groups_setup' );

			add_filter( 'bp_get_buddypress_template',                array( $this, 'directory_template_hierarchy' ) );
			add_action( 'bp_template_include_reset_dummy_post_data', array( $this, 'directory_dummy_post' ) );
			add_filter( 'bp_replace_the_content',                    array( $this, 'directory_content'    ) );

		// Creating a group.
		} elseif ( bp_is_groups_component() && bp_is_current_action( 'create' ) ) {
			add_filter( 'bp_get_buddypress_template',                array( $this, 'create_template_hierarchy' ) );
			add_action( 'bp_template_include_reset_dummy_post_data', array( $this, 'create_dummy_post' ) );
			add_filter( 'bp_replace_the_content',                    array( $this, 'create_content'    ) );

		// Group page.
		} elseif ( bp_is_single_item() ) {
			add_filter( 'bp_get_buddypress_template',                array( $this, 'single_template_hierarchy' ) );
			add_action( 'bp_template_include_reset_dummy_post_data', array( $this, 'single_dummy_post' ) );
			add_filter( 'bp_replace_the_content',                    array( $this, 'single_content'    ) );

		}
	}

	/** Directory *********************************************************/

	/**
	 * Add template hierarchy to theme compat for the group directory page.
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
		// Set up the template hierarchy.
		$new_templates = array();
		if ( '' !== bp_get_current_group_directory_type() ) {
			$new_templates[] = 'groups/index-directory-type-' . sanitize_file_name( bp_get_current_group_directory_type() ) . '.php';
		}
		$new_templates[] = 'groups/index-directory.php';

		/**
		 * Filters the Groups directory page template hierarchy based on priority.
		 *
		 * @since 1.8.0
		 *
		 * @param array $value Array of default template files to use.
		 */
		$new_templates = apply_filters( 'bp_template_hierarchy_groups_directory', $new_templates );

		// Merge new templates with existing stack.
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
			'post_title'     => bp_get_directory_title( 'groups' ),
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
		return bp_buffer_template_part( 'groups/index', null, false );
	}

	/** Create ************************************************************/

	/**
	 * Add custom template hierarchy to theme compat for the group create page.
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
		 * Filters the Groups create page template hierarchy based on priority.
		 *
		 * @since 1.8.0
		 *
		 * @param array $value Array of default template files to use.
		 */
		$new_templates = apply_filters( 'bp_template_hierarchy_groups_create', array(
			'groups/index-create.php'
		) );

		// Merge new templates with existing stack.
		// @see bp_get_theme_compat_templates().
		$templates = array_merge( $new_templates, $templates );

		return $templates;
	}

	/**
	 * Update the global $post with create screen data.
	 *
	 * @since 1.7.0
	 */
	public function create_dummy_post() {

		$title = _x( 'Groups', 'Group creation page', 'buddypress' );

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
		return bp_buffer_template_part( 'groups/create', null, false );
	}

	/** Single ************************************************************/

	/**
	 * Add custom template hierarchy to theme compat for group pages.
	 *
	 * This is to mirror how WordPress has
	 * {@link https://codex.wordpress.org/Template_Hierarchy template hierarchy}.
	 *
	 * @since 1.8.0
	 *
	 * @param string $templates The templates from bp_get_theme_compat_templates().
	 * @return array $templates Array of custom templates to look for.
	 */
	public function single_template_hierarchy( $templates ) {
		// Setup some variables we're going to reference in our custom templates.
		$group = groups_get_current_group();

		/**
		 * Filters the Groups single pages template hierarchy based on priority.
		 *
		 * @since 1.8.0
		 *
		 * @param array $value Array of default template files to use.
		 */
		$new_templates = apply_filters( 'bp_template_hierarchy_groups_single_item', array(
			'groups/single/index-id-'     . (int) bp_get_current_group_id()                   . '.php',
			'groups/single/index-slug-'   . sanitize_file_name( bp_get_current_group_slug() ) . '.php',
			'groups/single/index-action-' . sanitize_file_name( bp_current_action() )         . '.php',
			'groups/single/index-status-' . sanitize_file_name( $group->status )              . '.php',
			'groups/single/index.php'
		) );

		// Merge new templates with existing stack.
		// @see bp_get_theme_compat_templates().
		$templates = array_merge( (array) $new_templates, $templates );

		return $templates;
	}

	/**
	 * Update the global $post with single group data.
	 *
	 * @since 1.7.0
	 */
	public function single_dummy_post() {
		bp_theme_compat_reset_post( array(
			'ID'             => 0,
			'post_title'     => bp_get_current_group_name(),
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
	 * Filter the_content with the single group template part.
	 *
	 * @since 1.7.0
	 */
	public function single_content() {
		return bp_buffer_template_part( 'groups/single/home', null, false );
	}
}
