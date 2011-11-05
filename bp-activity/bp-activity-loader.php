<?php

/**
 * BuddyPress Activity Streams Loader
 *
 * An activity stream component, for users, groups, and blog tracking.
 *
 * @package BuddyPress
 * @subpackage ActivityCore
 */

// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

/**
 * Main Activity Class
 *
 * @since 1.5.0
 */
class BP_Activity_Component extends BP_Component {

	/**
	 * Start the activity component creation process
	 *
	 * @since 1.5.0
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
	 *
	 * @since 1.5.0
	 */
	function includes() {
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

		parent::includes( $includes );
	}

	/**
	 * Setup globals
	 *
	 * The BP_ACTIVITY_SLUG constant is deprecated, and only used here for
	 * backwards compatibility.
	 *
	 * @since 1.5.0
	 *
	 * @global object $bp BuddyPress global settings
	 */
	function setup_globals() {
		global $bp;

		// Define a slug, if necessary
		if ( !defined( 'BP_ACTIVITY_SLUG' ) )
			define( 'BP_ACTIVITY_SLUG', $this->id );

		// Global tables for activity component
		$global_tables = array(
			'table_name'      => $bp->table_prefix . 'bp_activity',
			'table_name_meta' => $bp->table_prefix . 'bp_activity_meta',
		);

		// All globals for activity component.
		// Note that global_tables is included in this array.
		$globals = array(
			'path'                  => BP_PLUGIN_DIR,
			'slug'                  => BP_ACTIVITY_SLUG,
			'root_slug'             => isset( $bp->pages->activity->slug ) ? $bp->pages->activity->slug : BP_ACTIVITY_SLUG,
			'has_directory'         => true,
			'search_string'         => __( 'Search Activity...', 'buddypress' ),
			'global_tables'         => $global_tables,
			'notification_callback' => 'bp_activity_format_notifications',
		);

		parent::setup_globals( $globals );
	}

	/**
	 * Setup BuddyBar navigation
	 *
	 * @since 1.5.0
	 *
	 * @global object $bp BuddyPress global settings
	 * @uses bp_is_active()
	 * @uses is_user_logged_in()
	 * @uses bp_get_friends_slug()
	 * @uses bp_get_groups_slug()
	 */
	function setup_nav() {
		global $bp;

		// Add 'Activity' to the main navigation
		$main_nav = array(
			'name'                => __( 'Activity', 'buddypress' ),
			'slug'                => $this->slug,
			'position'            => 10,
			'screen_function'     => 'bp_activity_screen_my_activity',
			'default_subnav_slug' => 'just-me',
			'item_css_id'         => $this->id
		);

		// Stop if there is no user displayed or logged in
		if ( !is_user_logged_in() && !isset( $bp->displayed_user->id ) )
			return;

		// Determine user to use
		if ( isset( $bp->displayed_user->domain ) )
			$user_domain = $bp->displayed_user->domain;
		elseif ( isset( $bp->loggedin_user->domain ) )
			$user_domain = $bp->loggedin_user->domain;
		else
			return;

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
				'slug'            => bp_get_friends_slug(),
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
				'slug'            => bp_get_groups_slug(),
				'parent_url'      => $activity_link,
				'parent_slug'     => $this->slug,
				'screen_function' => 'bp_activity_screen_groups',
				'position'        => 50,
				'item_css_id'     => 'activity-groups'
			);
		}

		parent::setup_nav( $main_nav, $sub_nav );
	}

	/**
	 * Set up the admin bar
	 *
	 * @since 1.5.0
	 *
	 * @global object $bp BuddyPress global settings
	 * @uses is_user_logged_in()
	 * @uses trailingslashit()
	 * @uses bp_get_total_mention_count_for_user()
	 * @uses bp_loggedin_user_id()
	 * @uses bp_is_active()
	 * @uses bp_get_friends_slug()
	 * @uses bp_get_groups_slug()
	 */
	function setup_admin_bar() {
		global $bp;

		// Prevent debug notices
		$wp_admin_nav = array();

		// Menus for logged in user
		if ( is_user_logged_in() ) {

			// Setup the logged in user variables
			$user_domain   = $bp->loggedin_user->domain;
			$activity_link = trailingslashit( $user_domain . $this->slug );

			// Unread message count
			if ( $count = bp_get_total_mention_count_for_user( bp_loggedin_user_id() ) ) {
				$title = sprintf( __( 'Mentions <span class="count">%s</span>', 'buddypress' ), $count );
			} else {
				$title = __( 'Mentions', 'buddypress' );
			}

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
				'id'     => 'my-account-' . $this->id . '-mentions',
				'title'  => $title,
				'href'   => trailingslashit( $activity_link . 'mentions' )
			);

			// Personal
			$wp_admin_nav[] = array(
				'parent' => 'my-account-' . $this->id,
				'id'     => 'my-account-' . $this->id . '-personal',
				'title'  => __( 'Personal', 'buddypress' ),
				'href'   => trailingslashit( $activity_link )
			);

			// Favorites
			$wp_admin_nav[] = array(
				'parent' => 'my-account-' . $this->id,
				'id'     => 'my-account-' . $this->id . '-favorites',
				'title'  => __( 'Favorites', 'buddypress' ),
				'href'   => trailingslashit( $activity_link . 'favorites' )
			);

			// Friends?
			if ( bp_is_active( 'friends' ) ) {
				$wp_admin_nav[] = array(
					'parent' => 'my-account-' . $this->id,
					'id'     => 'my-account-' . $this->id . '-friends',
					'title'  => __( 'Friends', 'buddypress' ),
					'href'   => trailingslashit( $activity_link . bp_get_friends_slug() )
				);
			}

			// Groups?
			if ( bp_is_active( 'groups' ) ) {
				$wp_admin_nav[] = array(
					'parent' => 'my-account-' . $this->id,
					'id'     => 'my-account-' . $this->id . '-groups',
					'title'  => __( 'Groups', 'buddypress' ),
					'href'   => trailingslashit( $activity_link . bp_get_groups_slug() )
				);
			}
		}

		parent::setup_admin_bar( $wp_admin_nav );
	}

	/**
	 * Sets up the title for pages and <title>
	 *
	 * @since 1.5.0
	 *
	 * @global object $bp BuddyPress global settings
	 * @uses bp_is_activity_component()
	 * @uses bp_is_my_profile()
	 * @uses bp_core_fetch_avatar()
	 */
	function setup_title() {
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

		parent::setup_title();
	}
}

// Create the activity component
$bp->activity = new BP_Activity_Component();

?>