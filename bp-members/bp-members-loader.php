<?php

/**
 * BuddyPress Member Loader
 *
 * A members component to help contain all of the user specific slugs
 *
 * @package BuddyPress
 * @subpackage Members
 */

class BP_Members_Component extends BP_Component {

	/**
	 * Start the members component creation process
	 *
	 * @since BuddyPress {unknown}
	 */
	function BP_Members_Component() {
		$this->__construct();
	}

	function __construct() {
		parent::start(
			'members',
			__( 'Members', 'buddypress' ),
			BP_PLUGIN_DIR
		);
	}

	/**
	 * Include files
	 *
	 * @global obj $bp
	 */
	function _includes() {
		$includes = array(
			'signup',
			'actions',
			'filters',
			'screens',
			'template',
			'buddybar',
			'adminbar',
			'functions',
			'notifications',
		);
		parent::_includes( $includes );
	}

	/**
	 * Setup globals
	 *
	 * The BP_MEMBERS_SLUG constant is deprecated, and only used here for
	 * backwards compatibility.
	 *
	 * @since BuddyPress {unknown}
	 * @global obj $bp
	 */
	function _setup_globals() {
		global $bp, $current_user, $displayed_user_id;

		// Define a slug, if necessary
		if ( !defined( 'BP_MEMBERS_SLUG' ) )
			define( 'BP_MEMBERS_SLUG', 'members' );

		$globals = array(
			'slug'          => BP_MEMBERS_SLUG,
			'root_slug'     => isset( $bp->pages->members->slug ) ? $bp->pages->members->slug : BP_MEMBERS_SLUG,
			'search_string' => __( 'Search Members...', 'buddypress' ),
		);

		parent::_setup_globals( $globals );

		/** Logged in user ****************************************************/

		// Fetch the full name for the logged in user
		$bp->loggedin_user->fullname       = bp_core_get_user_displayname( $bp->loggedin_user->id );

		// Hits the DB on single WP installs so get this separately
		$bp->loggedin_user->is_super_admin = $bp->loggedin_user->is_site_admin = is_super_admin();

		// The domain for the user currently logged in. eg: http://domain.com/members/andy
		$bp->loggedin_user->domain         = bp_core_get_user_domain( $bp->loggedin_user->id );

		// The core userdata of the user who is currently logged in.
		$bp->loggedin_user->userdata       = bp_core_get_core_userdata( $bp->loggedin_user->id );

		/**
		 * Used to determine if user has admin rights on current content. If the
		 * logged in user is viewing their own profile and wants to delete
		 * something, is_item_admin is used. This is a generic variable so it
		 * can be used by other components. It can also be modified, so when
		 * viewing a group 'is_item_admin' would be 'true' if they are a group
		 * admin, and 'false' if they are not.
		 */
		bp_update_is_item_admin( bp_user_has_access(), 'members' );

		// Is the logged in user is a mod for the current item?
		bp_update_is_item_mod  ( false,                'members' );

		/** Displayed user ****************************************************/

		// The user id of the user currently being viewed:
		// $bp->displayed_user->id is set in /bp-core/bp-core-catchuri.php
		if ( empty( $bp->displayed_user->id ) )
			$bp->displayed_user->id = 0;

		// The domain for the user currently being displayed
		$bp->displayed_user->domain   = bp_core_get_user_domain( $bp->displayed_user->id );

		// The core userdata of the user who is currently being displayed
		$bp->displayed_user->userdata = bp_core_get_core_userdata( $bp->displayed_user->id );

		// Fetch the full name displayed user
		$bp->displayed_user->fullname = bp_core_get_user_displayname( $bp->displayed_user->id );

		/** Profiles Fallback *************************************************/
		if ( !bp_is_active( 'xprofile' ) ) {
			$bp->profile->slug = 'profile';
			$bp->profile->id   = 'profile';
		}

		/** Default Profile Component *****************************************/
		if ( !defined( 'BP_DEFAULT_COMPONENT' ) ) {
			if ( isset( $bp->pages->activity ) && isset( $bp->activity->id ) )
				$bp->default_component = $bp->activity->id;
			else
				$bp->default_component = $bp->profile->id;
		} else {
			$bp->default_component     = BP_DEFAULT_COMPONENT;
		}

		if ( !$bp->current_component && $bp->displayed_user->id )
			$bp->current_component = $bp->default_component;
	}

	/**
	 * Setup BuddyBar navigation
	 *
	 * @global obj $bp
	 */
	function _setup_nav() {
		global $bp;

		// Stop if there is no user displayed and not logged in
		if ( !is_user_logged_in() && empty( $bp->displayed_user->id ) )
			return;

		// Add 'Profile' to the main navigation
		if ( !bp_is_active( 'xprofile' ) ) {
			$main_nav = array(
				'name'                => __( 'Profile', 'buddypress' ),
				'slug'                => $bp->profile->slug,
				'position'            => 20,
				'screen_function'     => 'bp_members_screen_display_profile',
				'default_subnav_slug' => 'public',
				'item_css_id'         => $bp->profile->id
			);

			// User links
			$user_domain   = ( !empty( $bp->displayed_user->domain ) )               ? $bp->displayed_user->domain               : $bp->loggedin_user->domain;
			$user_login    = ( !empty( $bp->displayed_user->userdata->user_login ) ) ? $bp->displayed_user->userdata->user_login : $bp->loggedin_user->userdata->user_login;
			$profile_link  = trailingslashit( $user_domain . $bp->profile->slug );

			// Add the subnav items to the profile
			$sub_nav[] = array(
				'name'            => __( 'Public', 'buddypress' ),
				'slug'            => 'public',
				'parent_url'      => $profile_link,
				'parent_slug'     => $bp->profile->slug,
				'screen_function' => 'bp_members_screen_display_profile',
				'position'        => 10
			);

			parent::_setup_nav( $main_nav, $sub_nav );
		}
	}

	/**
	 * Sets up the title for pages and <title>
	 *
	 * @global obj $bp
	 */
	function _setup_title() {
		global $bp;

		if ( bp_is_my_profile() ) {
			$bp->bp_options_title = __( 'You', 'buddypress' );
		} elseif( bp_is_user() ) {
			$bp->bp_options_avatar = bp_core_fetch_avatar( array(
				'item_id' => $bp->displayed_user->id,
				'type'    => 'thumb'
			) );
			$bp->bp_options_title  = $bp->displayed_user->fullname;
		}

		parent::_setup_title();
	}

}
// Create the users component
$bp->members = new BP_Members_Component();

?>
