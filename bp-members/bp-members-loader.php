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
	 * @since BuddyPress (1.5)
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
	 * @global BuddyPress $bp The one true BuddyPress instance
	 */
	function includes() {
		$includes = array(
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
	 * @since BuddyPress (1.5)
	 * @global BuddyPress $bp The one true BuddyPress instance
	 */
	function setup_globals() {
		global $bp;

		// Define a slug, if necessary
		if ( !defined( 'BP_MEMBERS_SLUG' ) )
			define( 'BP_MEMBERS_SLUG', $this->id );

		$globals = array(
			'slug'          => BP_MEMBERS_SLUG,
			'root_slug'     => isset( $bp->pages->members->slug ) ? $bp->pages->members->slug : BP_MEMBERS_SLUG,
			'has_directory' => true,
			'search_string' => __( 'Search Members...', 'buddypress' ),
		);

		parent::setup_globals( $globals );

		/** Logged in user ****************************************************/

		// Fetch the full name for the logged in user
		$bp->loggedin_user->fullname       = bp_core_get_user_displayname( bp_loggedin_user_id() );

		// Hits the DB on single WP installs so get this separately
		$bp->loggedin_user->is_super_admin = $bp->loggedin_user->is_site_admin = is_super_admin( bp_loggedin_user_id() );

		// The domain for the user currently logged in. eg: http://domain.com/members/andy
		$bp->loggedin_user->domain         = bp_core_get_user_domain( bp_loggedin_user_id() );

		// The core userdata of the user who is currently logged in.
		$bp->loggedin_user->userdata       = bp_core_get_core_userdata( bp_loggedin_user_id() );

		/** Displayed user ****************************************************/

		// The domain for the user currently being displayed
		$bp->displayed_user->domain   = bp_core_get_user_domain( bp_displayed_user_id() );

		// The core userdata of the user who is currently being displayed
		$bp->displayed_user->userdata = bp_core_get_core_userdata( bp_displayed_user_id() );

		// Fetch the full name displayed user
		$bp->displayed_user->fullname = bp_core_get_user_displayname( bp_displayed_user_id() );

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
		
		if ( bp_displayed_user_id() ) {
			$bp->canonical_stack['base_url'] = bp_displayed_user_domain();
		
			if ( bp_current_component() ) {
				$bp->canonical_stack['component'] = bp_current_component();
			}
			
			if ( bp_current_action() ) {
				$bp->canonical_stack['action'] = bp_current_action();
			}
			
			if ( !empty( $bp->action_variables ) ) {
				$bp->canonical_stack['action_variables'] = bp_action_variables();
			}

			if ( !bp_current_component() ) {
				$bp->current_component = $bp->default_component;
			} else if ( bp_is_current_component( $bp->default_component ) && !bp_current_action() ) {			
				// The canonical URL will not contain the default component
				unset( $bp->canonical_stack['component'] );
			}
		}
	}

	/**
	 * Setup BuddyBar navigation
	 *
	 * @global BuddyPress $bp The one true BuddyPress instance
	 */
	function setup_nav() {
		global $bp;

		// Add 'Profile' to the main navigation
		if ( !bp_is_active( 'xprofile' ) ) {

			// Don't set up navigation if there's no user
			if ( !is_user_logged_in() && !bp_is_user() )
				return;

			$sub_nav  = array();
			$main_nav = array(
				'name'                => __( 'Profile', 'buddypress' ),
				'slug'                => $bp->profile->slug,
				'position'            => 20,
				'screen_function'     => 'bp_members_screen_display_profile',
				'default_subnav_slug' => 'public',
				'item_css_id'         => $bp->profile->id
			);

			// User links
			$user_domain   = bp_displayed_user_domain() ? bp_displayed_user_domain() : bp_loggedin_user_domain();
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
	 * @global BuddyPress $bp The one true BuddyPress instance
	 */
	function setup_title() {
		global $bp;

		if ( bp_is_my_profile() ) {
			$bp->bp_options_title = __( 'You', 'buddypress' );
		} elseif( bp_is_user() ) {
			$bp->bp_options_avatar = bp_core_fetch_avatar( array(
				'item_id' => bp_displayed_user_id(),
				'type'    => 'thumb',
				'alt'     => sprintf( __( 'Profile picture of %s', 'buddypress' ), bp_get_displayed_user_fullname() )
			) );
			$bp->bp_options_title = bp_get_displayed_user_fullname();
		}

		parent::setup_title();
	}
}

function bp_setup_members() {
	global $bp;
	$bp->members = new BP_Members_Component();
}
add_action( 'bp_setup_components', 'bp_setup_members', 1 );

?>
