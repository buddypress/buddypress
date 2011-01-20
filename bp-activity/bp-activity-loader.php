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
		parent::start( 'activity', __( 'Activity Streams', 'buddypress' ) );
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

		// Do some slug checks
		$this->slug      = BP_ACTIVITY_SLUG;
		$this->root_slug = isset( $bp->pages->activity->slug ) ? $bp->pages->activity->slug : $this->slug;

		// Tables
		$this->table_name      = $bp->table_prefix . 'bp_activity';
		$this->table_name_meta = $bp->table_prefix . 'bp_activity_meta';
	}

	/**
	 * Include files
	 */
	function _includes() {
		require_once( BP_PLUGIN_DIR . '/bp-activity/bp-activity-actions.php'   );
		require_once( BP_PLUGIN_DIR . '/bp-activity/bp-activity-filters.php'   );
		require_once( BP_PLUGIN_DIR . '/bp-activity/bp-activity-screens.php'   );
		require_once( BP_PLUGIN_DIR . '/bp-activity/bp-activity-classes.php'   );
		require_once( BP_PLUGIN_DIR . '/bp-activity/bp-activity-template.php'  );
		require_once( BP_PLUGIN_DIR . '/bp-activity/bp-activity-functions.php' );
	}

	/**
	 * Setup BuddyBar navigation
	 *
	 * @global obj $bp
	 */
	function _setup_nav() {
		global $bp;

		// Add 'Activity' to the main navigation
		bp_core_new_nav_item( array(
			'name'                => __( 'Activity', 'buddypress' ),
			'slug'                => $bp->activity->slug,
			'position'            => 10,
			'screen_function'     => 'bp_activity_screen_my_activity',
			'default_subnav_slug' => 'just-me',
			'item_css_id'         => $bp->activity->id )
		);

		// Stop if there is no user displayed or logged in
		if ( !is_user_logged_in() && !isset( $bp->displayed_user->id ) )
			return;

		// User links
		$user_domain   = ( isset( $bp->displayed_user->domain ) )               ? $bp->displayed_user->domain               : $bp->loggedin_user->domain;
		$user_login    = ( isset( $bp->displayed_user->userdata->user_login ) ) ? $bp->displayed_user->userdata->user_login : $bp->loggedin_user->userdata->user_login;
		$activity_link = $user_domain . $bp->activity->slug . '/';

		// Add the subnav items to the activity nav item if we are using a theme that supports this
		bp_core_new_subnav_item( array(
			'name'            => __( 'Personal', 'buddypress' ),
			'slug'            => 'just-me',
			'parent_url'      => $activity_link,
			'parent_slug'     => $bp->activity->slug,
			'screen_function' => 'bp_activity_screen_my_activity',
			'position'        => 10
		) );

		// Additional menu if friends is active
		if ( bp_is_active( 'friends' ) ) {
			bp_core_new_subnav_item( array(
				'name'            => __( 'Friends', 'buddypress' ),
				'slug'            => BP_FRIENDS_SLUG,
				'parent_url'      => $activity_link,
				'parent_slug'     => $bp->activity->slug,
				'screen_function' => 'bp_activity_screen_friends',
				'position'        => 20,
				'item_css_id'     => 'activity-friends'
			) );
		}

		// Additional menu if groups is active
		if ( bp_is_active( 'groups' ) ) {
			bp_core_new_subnav_item( array(
				'name'            => __( 'Groups', 'buddypress' ),
				'slug'            => BP_GROUPS_SLUG,
				'parent_url'      => $activity_link,
				'parent_slug'     => $bp->activity->slug,
				'screen_function' => 'bp_activity_screen_groups',
				'position'        => 30,
				'item_css_id'     => 'activity-groups'
			) );
		}

		// Favorite activity items
		bp_core_new_subnav_item( array(
			'name'            => __( 'Favorites', 'buddypress' ),
			'slug'            => 'favorites',
			'parent_url'      => $activity_link,
			'parent_slug'     => $bp->activity->slug,
			'screen_function' => 'bp_activity_screen_favorites',
			'position'        => 40,
			'item_css_id'     => 'activity-favs'
		) );

		// @ mentions
		bp_core_new_subnav_item( array(
			'name'            => sprintf( __( '@%s Mentions', 'buddypress' ), $user_login ),
			'slug'            => 'mentions',
			'parent_url'      => $activity_link,
			'parent_slug'     => $bp->activity->slug,
			'screen_function' => 'bp_activity_screen_mentions',
			'position'        => 50,
			'item_css_id'     => 'activity-mentions'
		) );

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
	}
}
// Create the activity component
$bp->activity = new BP_Activity_Component();

?>
