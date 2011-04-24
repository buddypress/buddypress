<?php

/**
 * BuddyPress Activity Streams Loader
 *
 * An activity stream component, for users, groups, and blog tracking.
 *
 * @package BuddyPress
 * @subpackage Activity Core
 */

class BP_Activity_Component extends BP_Component {

	/**
	 * Start the activity component creation process
	 *
	 * @since BuddyPress {unknown}
	 */
	function BP_Activity_Component() {
		$this->__construct();
	}

	function __construct() {
		parent::start(
			'activity',
			__( 'Activity Streams', 'buddypress' ),
			BP_PLUGIN_DIR
		);
	}

	/**
	 * Include files
	 */
	function _includes() {
		// Files to include
		$includes = array(
			'actions',
			'screens',
			'filters',
			'classes',
			'template',
			'functions',
			'notifications',
		);

		parent::_includes( $includes );
	}

	/**
	 * Setup globals
	 *
	 * The BP_ACTIVITY_SLUG constant is deprecated, and only used here for
	 * backwards compatibility.
	 *
	 * @since BuddyPress {unknown}
	 * @global obj $bp
	 */
	function _setup_globals() {
		global $bp;

		// Define a slug, if necessary
		if ( !defined( 'BP_ACTIVITY_SLUG' ) )
			define( 'BP_ACTIVITY_SLUG', $this->id );

		// Global tables for messaging component
		$global_tables = array(
			'table_name'      => $bp->table_prefix . 'bp_activity',
			'table_name_meta' => $bp->table_prefix . 'bp_activity_meta',
		);

		// All globals for messaging component.
		// Note that global_tables is included in this array.
		$globals = array(
			'path'                  => BP_PLUGIN_DIR,
			'slug'                  => BP_ACTIVITY_SLUG,
			'root_slug'             => isset( $bp->pages->activity->slug ) ? $bp->pages->activity->slug : BP_ACTIVITY_SLUG,
			'search_string'         => __( 'Search Activity...', 'buddypress' ),
			'global_tables'         => $global_tables,
		);

		parent::_setup_globals( $globals );
	}

	/**
	 * Setup BuddyBar navigation
	 *
	 * @global obj $bp
	 */
	function _setup_nav() {
		global $bp;

		// Stop if there is no user displayed
		if ( empty( $bp->displayed_user->id ) )
			return;

		// Determine user to use
		if ( !empty( $bp->displayed_user->domain ) )
			$user_domain = $bp->displayed_user->domain;
		else
			return;
			
		// Add 'Activity' to the main navigation
		$main_nav = array(
			'name'                => __( 'Activity', 'buddypress' ),
			'slug'                => $this->slug,
			'position'            => 10,
			'screen_function'     => 'bp_activity_screen_my_activity',
			'default_subnav_slug' => 'just-me',
			'item_css_id'         => $this->id
		);

		// User link
		$activity_link = trailingslashit( $user_domain . $this->slug );

		// Add the subnav items to the activity nav item if we are using a theme that supports this
		$sub_nav[] = array(
			'name'            => __( 'Personal', 'buddypress' ),
			'slug'            => 'just-me',
			'parent_url'      => $activity_link,
			'parent_slug'     => $this->slug,
			'screen_function' => 'bp_activity_screen_my_activity',
			'position'        => 10
		);

		// @ mentions
		$sub_nav[] = array(
			'name'            => __( 'Mentions', 'buddypress' ),
			'slug'            => 'mentions',
			'parent_url'      => $activity_link,
			'parent_slug'     => $this->slug,
			'screen_function' => 'bp_activity_screen_mentions',
			'position'        => 20,
			'item_css_id'     => 'activity-mentions'
		);

		// Favorite activity items
		$sub_nav[] = array(
			'name'            => __( 'Favorites', 'buddypress' ),
			'slug'            => 'favorites',
			'parent_url'      => $activity_link,
			'parent_slug'     => $this->slug,
			'screen_function' => 'bp_activity_screen_favorites',
			'position'        => 30,
			'item_css_id'     => 'activity-favs'
		);

		// Additional menu if friends is active
		if ( bp_is_active( 'friends' ) ) {
			$sub_nav[] = array(
				'name'            => __( 'Friends', 'buddypress' ),
				'slug'            => $bp->friends->slug,
				'parent_url'      => $activity_link,
				'parent_slug'     => $this->slug,
				'screen_function' => 'bp_activity_screen_friends',
				'position'        => 40,
				'item_css_id'     => 'activity-friends'
			) ;
		}

		// Additional menu if groups is active
		if ( bp_is_active( 'groups' ) ) {
			$sub_nav[] = array(
				'name'            => __( 'Groups', 'buddypress' ),
				'slug'            => $bp->groups->slug,
				'parent_url'      => $activity_link,
				'parent_slug'     => $this->slug,
				'screen_function' => 'bp_activity_screen_groups',
				'position'        => 50,
				'item_css_id'     => 'activity-groups'
			);
		}

		parent::_setup_nav( $main_nav, $sub_nav );
	}

	/**
	 * Set up the admin bar
	 *
	 * @global obj $bp
	 */
	function _setup_admin_bar() {
		global $bp;

		// Prevent debug notices
		$wp_admin_nav = array();

		// Menus for logged in user
		if ( is_user_logged_in() ) {

			// Setup the logged in user variables
			$user_domain   = $bp->loggedin_user->domain;
			$activity_link = trailingslashit( $user_domain . $this->slug );

			// Add the "Activity" sub menu
			$wp_admin_nav[] = array(
				'parent' => $bp->my_account_menu_id,
				'id'     => 'my-account-' . $this->id,
				'title'  => __( 'Activity', 'buddypress' ),
				'href'   => trailingslashit( $activity_link )
			);

			// Mentions
			$wp_admin_nav[] = array(
				'parent' => 'my-account-' . $this->id,
				'title'  => __( 'Mentions', 'buddypress' ),
				'href'   => trailingslashit( $activity_link . 'mentions' )
			);

			// Personal
			$wp_admin_nav[] = array(
				'parent' => 'my-account-' . $this->id,
				'title'  => __( 'Personal', 'buddypress' ),
				'href'   => trailingslashit( $activity_link )
			);

			// Favorites
			$wp_admin_nav[] = array(
				'parent' => 'my-account-' . $this->id,
				'title'  => __( 'Favorites', 'buddypress' ),
				'href'   => trailingslashit( $activity_link . 'favorites' )
			);

			// Friends?
			if ( bp_is_active( 'friends' ) ) {
				$wp_admin_nav[] = array(
					'parent' => 'my-account-' . $this->id,
					'title'  => __( 'Friends', 'buddypress' ),
					'href'   => trailingslashit( $activity_link . bp_get_friends_slug() )
				);
			}

			// Groups?
			if ( bp_is_active( 'groups' ) ) {
				$wp_admin_nav[] = array(
					'parent' => 'my-account-' . $this->id,
					'title'  => __( 'Groups', 'buddypress' ),
					'href'   => trailingslashit( $activity_link . bp_get_groups_slug() )
				);
			}
		}

		parent::_setup_admin_bar( $wp_admin_nav );
	}

	/**
	 * Sets up the title for pages and <title>
	 *
	 * @global obj $bp
	 */
	function _setup_title() {
		global $bp;

		// Adjust title based on view
		if ( bp_is_activity_component() ) {
			if ( bp_is_my_profile() ) {
				$bp->bp_options_title = __( 'My Activity', 'buddypress' );
			} else {
				$bp->bp_options_avatar = bp_core_fetch_avatar( array(
					'item_id' => $bp->displayed_user->id,
					'type'    => 'thumb'
				) );
				$bp->bp_options_title  = $bp->displayed_user->fullname;
			}
		}

		parent::_setup_title();
	}
}
// Create the activity component
$bp->activity = new BP_Activity_Component();

?>
