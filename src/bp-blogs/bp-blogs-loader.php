<?php

/**
 * BuddyPress Blogs Loader
 *
 * The blogs component tracks posts and comments to member activity streams,
 * shows blogs the member can post to in their profiles, and caches useful
 * information from those blogs to make querying blogs in bulk more performant.
 *
 * @package BuddyPress
 * @subpackage Blogs Core
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

class BP_Blogs_Component extends BP_Component {

	/**
	 * Start the blogs component creation process.
	 *
	 * @since BuddyPress (1.5.0)
	 */
	public function __construct() {
		parent::start(
			'blogs',
			__( 'Site Directory', 'buddypress' ),
			buddypress()->plugin_dir,
			array(
				'adminbar_myaccount_order' => 30
			)
		);
	}

	/**
	 * Set up global settings for the blogs component.
	 *
	 * The BP_BLOGS_SLUG constant is deprecated, and only used here for
	 * backwards compatibility.
	 *
	 * @since BuddyPress (1.5.0)
	 *
	 * @see BP_Component::setup_globals() for description of parameters.
	 *
	 * @param array $args See {@link BP_Component::setup_globals()}.
	 */
	public function setup_globals( $args = array() ) {
		$bp = buddypress();

		if ( ! defined( 'BP_BLOGS_SLUG' ) ) {
			define ( 'BP_BLOGS_SLUG', $this->id );
		}

		// Global tables for messaging component
		$global_tables = array(
			'table_name'          => $bp->table_prefix . 'bp_user_blogs',
			'table_name_blogmeta' => $bp->table_prefix . 'bp_user_blogs_blogmeta',
		);

		$meta_tables = array(
			'blog' => $bp->table_prefix . 'bp_user_blogs_blogmeta',
		);

		// All globals for blogs component.
		$args = array(
			'slug'                  => BP_BLOGS_SLUG,
			'root_slug'             => isset( $bp->pages->blogs->slug ) ? $bp->pages->blogs->slug : BP_BLOGS_SLUG,
			'has_directory'         => is_multisite(), // Non-multisite installs don't need a top-level Sites directory, since there's only one site
			'notification_callback' => 'bp_blogs_format_notifications',
			'search_string'         => __( 'Search sites...', 'buddypress' ),
			'autocomplete_all'      => defined( 'BP_MESSAGES_AUTOCOMPLETE_ALL' ),
			'global_tables'         => $global_tables,
			'meta_tables'           => $meta_tables,
		);

		// Setup the globals
		parent::setup_globals( $args );

		/*
		 * Set up the post post type to track.
		 *
		 * In case the config is not multisite, the blog_public option is ignored.
		 */
		if ( 0 !== apply_filters( 'bp_is_blog_public', (int) get_option( 'blog_public' ) ) || ! is_multisite() ) {

			/**
			 * Filters the post types to track for the Blog component.
			 *
			 * @since BuddyPress (1.5.0)
			 *
			 * @param array $value Array of post types to track.
			 */
			$post_types = apply_filters( 'bp_blogs_record_post_post_types', array( 'post' ) );

			foreach ( $post_types as $post_type ) {
				add_post_type_support( $post_type, 'buddypress-activity' );
			}
		}

		// Filter the generic track parameters for the 'post' post type.
		add_filter( 'bp_activity_get_post_type_tracking_args', array( $this, 'post_tracking_args' ), 10, 2 );
	}

	/**
	 * Include bp-blogs files.
	 *
	 * @see BP_Component::includes() for description of parameters.
	 *
	 * @param array $includes See {@link BP_Component::includes()}.
	 */
	public function includes( $includes = array() ) {

		// Files to include
		$includes = array(
			'cache',
			'actions',
			'screens',
			'classes',
			'template',
			'filters',
			'activity',
			'functions',
		);

		if ( is_multisite() ) {
			$includes[] = 'widgets';
		}

		// Include the files
		parent::includes( $includes );
	}

	/**
	 * Set up component navigation for bp-blogs.
	 *
	 * @see BP_Component::setup_nav() for a description of arguments.
	 *
	 * @param array $main_nav Optional. See BP_Component::setup_nav() for
	 *        description.
	 * @param array $sub_nav Optional. See BP_Component::setup_nav() for
	 *        description.
	 */
	public function setup_nav( $main_nav = array(), $sub_nav = array() ) {
		$bp = buddypress();

		/**
		 * Blog/post/comment menus should not appear on single WordPress setups.
		 * Although comments and posts made by users will still show on their
		 * activity stream.
		 */
		if ( ! is_multisite() ) {
			return false;
		}

		// Add 'Sites' to the main navigation
		$main_nav =  array(
			'name'                => sprintf( __( 'Sites <span>%d</span>', 'buddypress' ), bp_get_total_blog_count_for_user() ),
			'slug'                => $this->slug,
			'position'            => 30,
			'screen_function'     => 'bp_blogs_screen_my_blogs',
			'default_subnav_slug' => 'my-sites',
			'item_css_id'         => $this->id
		);

		// Determine user to use
		if ( bp_displayed_user_domain() ) {
			$user_domain = bp_displayed_user_domain();
		} elseif ( bp_loggedin_user_domain() ) {
			$user_domain = bp_loggedin_user_domain();
		} else {
			return;
		}

		$parent_url = trailingslashit( $user_domain . bp_get_blogs_slug() );

		$sub_nav[] = array(
			'name'            => __( 'My Sites', 'buddypress' ),
			'slug'            => 'my-sites',
			'parent_url'      => $parent_url,
			'parent_slug'     => $bp->blogs->slug,
			'screen_function' => 'bp_blogs_screen_my_blogs',
			'position'        => 10
		);

		// Setup navigation
		parent::setup_nav( $main_nav, $sub_nav );
	}

	/**
	 * Set up bp-blogs integration with the WordPress admin bar.
	 *
	 * @since BuddyPress (1.5.0)
	 *
	 * @see BP_Component::setup_admin_bar() for a description of arguments.
	 *
	 * @param array $wp_admin_nav See BP_Component::setup_admin_bar()
	 *        for description.
	 */
	public function setup_admin_bar( $wp_admin_nav = array() ) {
		$bp = buddypress();

		/**
		 * Site/post/comment menus should not appear on single WordPress setups.
		 *
		 * Comments and posts made by users will still show in their activity.
		 */
		if ( ! is_multisite() ) {
			return false;
		}

		// Menus for logged in user
		if ( is_user_logged_in() ) {

			$blogs_link = trailingslashit( bp_loggedin_user_domain() . $this->slug );

			// Add the "Sites" sub menu
			$wp_admin_nav[] = array(
				'parent' => $bp->my_account_menu_id,
				'id'     => 'my-account-' . $this->id,
				'title'  => __( 'Sites', 'buddypress' ),
				'href'   => trailingslashit( $blogs_link )
			);

			// My Sites
			$wp_admin_nav[] = array(
				'parent' => 'my-account-' . $this->id,
				'id'     => 'my-account-' . $this->id . '-my-sites',
				'title'  => __( 'My Sites', 'buddypress' ),
				'href'   => trailingslashit( $blogs_link )
			);

			// Create a Site
			if ( bp_blog_signup_enabled() ) {
				$wp_admin_nav[] = array(
					'parent' => 'my-account-' . $this->id,
					'id'     => 'my-account-' . $this->id . '-create',
					'title'  => __( 'Create a Site', 'buddypress' ),
					'href'   => trailingslashit( bp_get_blogs_directory_permalink() . 'create' )
				);
			}
		}

		parent::setup_admin_bar( $wp_admin_nav );
	}

	/**
	 * Set up the title for pages and <title>
	 */
	public function setup_title() {
		$bp = buddypress();

		// Set up the component options navigation for Site
		if ( bp_is_blogs_component() ) {
			if ( bp_is_my_profile() ) {
				if ( bp_is_active( 'xprofile' ) ) {
					$bp->bp_options_title = __( 'My Sites', 'buddypress' );
				}

			// If we are not viewing the logged in user, set up the current
			// users avatar and name
			} else {
				$bp->bp_options_avatar = bp_core_fetch_avatar( array(
					'item_id' => bp_displayed_user_id(),
					'type'    => 'thumb',
					'alt'     => sprintf( __( 'Profile picture of %s', 'buddypress' ), bp_get_displayed_user_fullname() )
				) );
				$bp->bp_options_title = bp_get_displayed_user_fullname();
			}
		}

		parent::setup_title();
	}

	/**
	 * Setup cache groups
	 *
	 * @since BuddyPress (2.2.0)
	 */
	public function setup_cache_groups() {

		// Global groups
		wp_cache_add_global_groups( array(
			'blog_meta'
		) );

		parent::setup_cache_groups();
	}

	/**
	 * Set up the tracking arguments for the 'post' post type.
	 *
	 * @since BuddyPress (2.2.0)
	 *
	 * @see bp_activity_get_post_type_tracking_args() for information on parameters.
	 */
	public function post_tracking_args( $params = array(), $post_type = 0 ) {
		if ( 'post' != $post_type ) {
			return $params;
		}

		// Set specific params for the 'post' post type.
		$params->component_id    = $this->id;
		$params->action_id       = 'new_blog_post';
		$params->admin_filter    = __( 'New post published', 'buddypress' );
		$params->format_callback = 'bp_blogs_format_activity_action_new_blog_post';
		$params->front_filter    = __( 'Posts', 'buddypress' );
		$params->contexts        = array( 'activity', 'member' );
		$params->position        = 5;

		return $params;
	}
}

/**
 * Set up the bp-blogs component.
 */
function bp_setup_blogs() {
	buddypress()->blogs = new BP_Blogs_Component();
}
add_action( 'bp_setup_components', 'bp_setup_blogs', 6 );
