<?php

/**
 * BuddyPress Blogs Streams Loader
 *
 * An blogs stream component, for users, groups, and blog tracking.
 *
 * @package BuddyPress
 * @subpackage Blogs Core
 */

// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

class BP_Blogs_Component extends BP_Component {

	/**
	 * Start the blogs component creation process
	 *
	 * @since BuddyPress (1.5)
	 */
	function __construct() {
		parent::start(
			'blogs',
			__( 'Site Tracking', 'buddypress' ),
			BP_PLUGIN_DIR
		);
	}

	/**
	 * Setup globals
	 *
	 * The BP_BLOGS_SLUG constant is deprecated, and only used here for
	 * backwards compatibility.
	 *
	 * @since BuddyPress (1.5)
	 * @global BuddyPress $bp The one true BuddyPress instance
	 */
	function setup_globals() {
		global $bp;

		if ( !defined( 'BP_BLOGS_SLUG' ) )
			define ( 'BP_BLOGS_SLUG', $this->id );

		// Global tables for messaging component
		$global_tables = array(
			'table_name'          => $bp->table_prefix . 'bp_user_blogs',
			'table_name_blogmeta' => $bp->table_prefix . 'bp_user_blogs_blogmeta',
		);

		// All globals for messaging component.
		// Note that global_tables is included in this array.
		$globals = array(
			'slug'                  => BP_BLOGS_SLUG,
			'root_slug'             => isset( $bp->pages->blogs->slug ) ? $bp->pages->blogs->slug : BP_BLOGS_SLUG,
			'has_directory'         => is_multisite(), // Non-multisite installs don't need a top-level Sites directory, since there's only one site
			'notification_callback' => 'bp_blogs_format_notifications',
			'search_string'         => __( 'Search sites...', 'buddypress' ),
			'autocomplete_all'      => defined( 'BP_MESSAGES_AUTOCOMPLETE_ALL' ),
			'global_tables'         => $global_tables,
		);

		// Setup the globals
		parent::setup_globals( $globals );
	}

	/**
	 * Include files
	 */
	function includes() {
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
			'buddybar'
		);

		if ( is_multisite() )
			$includes[] = 'widgets';

		// Include the files
		parent::includes( $includes );
	}

	/**
	 * Setup BuddyBar navigation
	 *
	 * @global BuddyPress $bp The one true BuddyPress instance
	 */
	function setup_nav() {
		global $bp;

		/**
		 * Blog/post/comment menus should not appear on single WordPress setups.
		 * Although comments and posts made by users will still show on their
		 * activity stream.
		 */
		if ( !is_multisite() )
			return false;

		$sub_nav = array();

		// Add 'Sites' to the main navigation
		$main_nav =  array(
			'name'                => sprintf( __( 'Sites <span>%d</span>', 'buddypress' ), bp_blogs_total_blogs_for_user() ),
			'slug'                => $this->slug,
			'position'            => 30,
			'screen_function'     => 'bp_blogs_screen_my_blogs',
			'default_subnav_slug' => 'my-sites',
			'item_css_id'         => $this->id
		);
		
		$parent_url = trailingslashit( bp_displayed_user_domain() . bp_get_blogs_slug() );
		
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
	 * Set up the Toolbar
	 *
	 * @global BuddyPress $bp The one true BuddyPress instance
	 */
	function setup_admin_bar() {
		global $bp;

		/**
		 * Blog/post/comment menus should not appear on single WordPress setups.
		 * Although comments and posts made by users will still show on their
		 * activity stream.
		 */
		if ( !is_multisite() )
			return false;

		// Prevent debug notices
		$wp_admin_nav = array();

		// Menus for logged in user
		if ( is_user_logged_in() ) {

			$blogs_link = trailingslashit( bp_loggedin_user_domain() . $this->slug );

			// Add the "Blogs" sub menu
			$wp_admin_nav[] = array(
				'parent' => $bp->my_account_menu_id,
				'id'     => 'my-account-' . $this->id,
				'title'  => __( 'Sites', 'buddypress' ),
				'href'   => trailingslashit( $blogs_link )
			);

			// My Blogs
			$wp_admin_nav[] = array(
				'parent' => 'my-account-' . $this->id,
				'id'     => 'my-account-' . $this->id . '-my-sites',
				'title'  => __( 'My Sites', 'buddypress' ),
				'href'   => trailingslashit( $blogs_link )
			);

		}

		parent::setup_admin_bar( $wp_admin_nav );
	}

	/**
	 * Sets up the title for pages and <title>
	 *
	 * @global BuddyPress $bp The one true BuddyPress instance
	 */
	function setup_title() {
		global $bp;

		// Set up the component options navigation for Blog
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
}

function bp_setup_blogs() {
	global $bp;
	$bp->blogs = new BP_Blogs_Component();
}
add_action( 'bp_setup_components', 'bp_setup_blogs', 6 );

?>
