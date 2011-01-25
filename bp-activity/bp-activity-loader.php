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
		if ( isset( $bp->displayed_user->domain ) ) {
			$user_domain = $bp->displayed_user->domain;
			$user_login  = $bp->displayed_user->userdata->user_login;
		} elseif ( isset( $bp->loggedin_user->domain ) ) {
			$user_domain = $bp->loggedin_user->domain;
			$user_login  = $bp->loggedin_user->userdata->user_login;
		} else {
			return;
		}

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

		// Additional menu if friends is active
		if ( bp_is_active( 'friends' ) ) {
			$sub_nav[] = array(
				'name'            => __( 'Friends', 'buddypress' ),
				'slug'            => $bp->friends->slug,
				'parent_url'      => $activity_link,
				'parent_slug'     => $this->slug,
				'screen_function' => 'bp_activity_screen_friends',
				'position'        => 20,
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
				'position'        => 30,
				'item_css_id'     => 'activity-groups'
			);
		}

		// Favorite activity items
		$sub_nav[] = array(
			'name'            => __( 'Favorites', 'buddypress' ),
			'slug'            => 'favorites',
			'parent_url'      => $activity_link,
			'parent_slug'     => $this->slug,
			'screen_function' => 'bp_activity_screen_favorites',
			'position'        => 40,
			'item_css_id'     => 'activity-favs'
		);

		// @ mentions
		$sub_nav[] = array(
			'name'            => sprintf( __( '@%s Mentions', 'buddypress' ), $user_login ),
			'slug'            => 'mentions',
			'parent_url'      => $activity_link,
			'parent_slug'     => $this->slug,
			'screen_function' => 'bp_activity_screen_mentions',
			'position'        => 50,
			'item_css_id'     => 'activity-mentions'
		);

		parent::_setup_nav( $main_nav, $sub_nav );
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
