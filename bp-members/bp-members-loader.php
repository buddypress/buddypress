<?php
/**
 * BuddyPress Member Loader
 *
 * A members component to help contain all of the user specific slugs
 *
 * @package BuddyPress
 * @subpackage Members
 */

// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

class BP_Members_Component extends BP_Component {

	/**
	 * Start the members component creation process
	 *
	 * @since 1.5
	 */
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
	function includes() {
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
		parent::includes( $includes );
	}

	/**
	 * Setup globals
	 *
	 * The BP_MEMBERS_SLUG constant is deprecated, and only used here for
	 * backwards compatibility.
	 *
	 * @since 1.5
	 * @global obj $bp
	 */
	function setup_globals() {
		global $bp, $current_user, $displayed_user_id;

		// Define a slug, if necessary
		if ( !defined( 'BP_MEMBERS_SLUG' ) )
			define( 'BP_MEMBERS_SLUG', 'members' );

		$globals = array(
			'path'          => BP_PLUGIN_DIR,
			'slug'          => BP_MEMBERS_SLUG,
			'root_slug'     => isset( $bp->pages->members->slug ) ? $bp->pages->members->slug : BP_MEMBERS_SLUG,
			'has_directory' => true,
			'search_string' => __( 'Search Members...', 'buddypress' ),
		);

		parent::setup_globals( $globals );

		/** Logged in user ****************************************************/

		// Fetch the full name for the logged in user
		$bp->loggedin_user->fullname       = bp_core_get_user_displayname( $bp->loggedin_user->id );

		// Hits the DB on single WP installs so get this separately
		$bp->loggedin_user->is_super_admin = $bp->loggedin_user->is_site_admin = is_super_admin();

		// The domain for the user currently logged in. eg: http://domain.com/members/andy
		$bp->loggedin_user->domain         = bp_core_get_user_domain( $bp->loggedin_user->id );

		// The core userdata of the user who is currently logged in.
		$bp->loggedin_user->userdata       = bp_core_get_core_userdata( $bp->loggedin_user->id );

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
			if ( bp_is_active( 'activity' ) && isset( $bp->pages->activity ) )
				$bp->default_component = bp_get_activity_slug();
			else
				$bp->default_component = ( 'xprofile' == $bp->profile->id ) ? 'profile' : $bp->profile->id;

		} else {
			$bp->default_component = BP_DEFAULT_COMPONENT;
		}

		if ( !$bp->current_component && $bp->displayed_user->id )
			$bp->current_component = $bp->default_component;
	}

	/**
	 * Setup BuddyBar navigation
	 *
	 * @global obj $bp
	 */
	function setup_nav() {
		global $bp;

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
			$user_domain   = ( isset( $bp->displayed_user->domain ) )               ? $bp->displayed_user->domain               : $bp->loggedin_user->domain;
			$user_login    = ( isset( $bp->displayed_user->userdata->user_login ) ) ? $bp->displayed_user->userdata->user_login : $bp->loggedin_user->userdata->user_login;
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

			parent::setup_nav( $main_nav, $sub_nav );
		}
	}

	/**
	 * Sets up the title for pages and <title>
	 *
	 * @global obj $bp
	 */
	function setup_title() {
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

		parent::setup_title();
	}

}
// Create the users component
$bp->members = new BP_Members_Component();

?>