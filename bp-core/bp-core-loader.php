<?php

/**
 * BuddyPress Core Loader
 *
 * Core contains the commonly used functions, classes, and API's
 *
 * @package BuddyPress
 * @subpackage Core
 */

// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

class BP_Core extends BP_Component {

	/**
	 * Start the members component creation process
	 *
	 * @since BuddyPress (1.5)
	 *
	 * @uses BP_Core::bootstrap()
	 */
	function __construct() {
		parent::start(
			'core',
			__( 'BuddyPress Core', 'buddypress' )
			, BP_PLUGIN_DIR
		);

		$this->bootstrap();
	}

	/**
	 * Populate the global data needed before BuddyPress can continue
	 *
	 * This involves figuring out the currently required, active, deactive,
	 * and optional components.
	 *
	 * @since BuddyPress (1.5)
	 *
	 * @global BuddyPress $bp
	 */
	private function bootstrap() {
		global $bp;

		/**
		 * At this point in the stack, BuddyPress core has been loaded but
		 * individual components (friends/activity/groups/etc...) have not.
		 *
		 * The 'bp_core_loaded' action lets you execute code ahead of the
		 * other components.
		 */
		do_action( 'bp_core_loaded' );

		/** Components ********************************************************/

		// Set the included and optional components.
		$bp->optional_components = apply_filters( 'bp_optional_components', array( 'activity', 'blogs', 'forums', 'friends', 'groups', 'messages', 'settings', 'xprofile' ) );

		// Set the required components
		$bp->required_components = apply_filters( 'bp_required_components', array( 'members' ) );

		// Get a list of activated components
		if ( $active_components = bp_get_option( 'bp-active-components' ) ) {
			$bp->active_components      = apply_filters( 'bp_active_components', $active_components );
			$bp->deactivated_components = apply_filters( 'bp_deactivated_components', array_values( array_diff( array_values( array_merge( $bp->optional_components, $bp->required_components ) ), array_keys( $bp->active_components ) ) ) );

		// Pre 1.5 Backwards compatibility
		} elseif ( $deactivated_components = bp_get_option( 'bp-deactivated-components' ) ) {

			// Trim off namespace and filename
			foreach ( (array) $deactivated_components as $component => $value )
				$trimmed[] = str_replace( '.php', '', str_replace( 'bp-', '', $component ) );

			// Set globals
			$bp->deactivated_components = apply_filters( 'bp_deactivated_components', $trimmed );

			// Setup the active components
			$active_components     = array_fill_keys( array_diff( array_values( array_merge( $optional_components, $required_components ) ), array_values( $deactivated_components ) ), '1' );

			// Set the active component global
			$bp->active_components = apply_filters( 'bp_active_components', $bp->active_components );

		// Default to all components active
		} else {

			// Set globals
			$bp->deactivated_components = array();

			// Setup the active components
			$active_components     = array_fill_keys( array_values( array_merge( $bp->optional_components, $bp->required_components ) ), '1' );

			// Set the active component global
			$bp->active_components = apply_filters( 'bp_active_components', $bp->active_components );
		}

		// Loop through optional components
		foreach( $bp->optional_components as $component )
			if ( bp_is_active( $component ) && file_exists( BP_PLUGIN_DIR . '/bp-' . $component . '/bp-' . $component . '-loader.php' ) )
				include( BP_PLUGIN_DIR . '/bp-' . $component . '/bp-' . $component . '-loader.php' );

		// Loop through required components
		foreach( $bp->required_components as $component )
			if ( file_exists( BP_PLUGIN_DIR . '/bp-' . $component . '/bp-' . $component . '-loader.php' ) )
				include( BP_PLUGIN_DIR . '/bp-' . $component . '/bp-' . $component . '-loader.php' );

		// Add Core to required components
		$bp->required_components[] = 'core';
	}

	function includes() {

		if ( !is_admin() )
			return;

		$includes = array(
			'admin'
		);

		parent::includes( $includes );
	}

	/**
	 * Sets up a majority of the BuddyPress globals that require a minimal
	 * amount of processing, meaning they cannot be set in the BuddyPress class.
	 *
	 * @since BuddyPress (1.5)
	 *
	 * @global BuddyPress $bp
	 */
	function setup_globals() {
		global $bp;

		/** Database **********************************************************/

		// Get the base database prefix
		if ( empty( $bp->table_prefix ) )
			$bp->table_prefix = bp_core_get_table_prefix();

		// The domain for the root of the site where the main blog resides
		if ( empty( $bp->root_domain ) )
			$bp->root_domain = bp_core_get_root_domain();

		// Fetches all of the core BuddyPress settings in one fell swoop
		if ( empty( $bp->site_options ) )
			$bp->site_options = bp_core_get_root_options();

		// The names of the core WordPress pages used to display BuddyPress content
		if ( empty( $bp->pages ) )
			$bp->pages = bp_core_get_directory_pages();

		/** Basic current user data *******************************************/

		// Logged in user is the 'current_user'
		$current_user            = wp_get_current_user();

		// The user ID of the user who is currently logged in.
		$bp->loggedin_user       = new stdClass;
		$bp->loggedin_user->id   = isset( $current_user->ID ) ? $current_user->ID : 0;

		/** Avatars ***********************************************************/

		// Fetches the default Gravatar image to use if the user/group/blog has no avatar or gravatar
		$bp->grav_default        = new stdClass;
		$bp->grav_default->user  = apply_filters( 'bp_user_gravatar_default',  $bp->site_options['avatar_default'] );
		$bp->grav_default->group = apply_filters( 'bp_group_gravatar_default', $bp->grav_default->user );
		$bp->grav_default->blog  = apply_filters( 'bp_blog_gravatar_default',  $bp->grav_default->user );

		// Notifications Table
		$bp->core->table_name_notifications = $bp->table_prefix . 'bp_notifications';

		/**
		 * Used to determine if user has admin rights on current content. If the
		 * logged in user is viewing their own profile and wants to delete
		 * something, is_item_admin is used. This is a generic variable so it
		 * can be used by other components. It can also be modified, so when
		 * viewing a group 'is_item_admin' would be 'true' if they are a group
		 * admin, and 'false' if they are not.
		 */
		bp_update_is_item_admin( bp_user_has_access(), 'core' );

		// Is the logged in user is a mod for the current item?
		bp_update_is_item_mod( false,                  'core' );

		do_action( 'bp_core_setup_globals' );
	}

	/**
	 * Setup BuddyBar navigation
	 *
	 * @since BuddyPress (1.5)
	 *
	 * @global BuddyPress $bp
	 */
	function setup_nav() {
		global $bp;

		 // If xprofile component is disabled, revert to WordPress profile
		if ( !bp_is_active( 'xprofile' ) ) {

			// Define local variable
			$sub_nav = array();

			// Fallback values if xprofile is disabled
			$bp->core->profile->slug = 'profile';
			$bp->active_components[$bp->core->profile->slug] = $bp->core->profile->slug;

			// Add 'Profile' to the main navigation
			$main_nav = array(
				'name'                => __( 'Profile', 'buddypress' ),
				'slug'                => $bp->core->profile->slug,
				'position'            => 20,
				'screen_function'     => 'bp_core_catch_profile_uri',
				'default_subnav_slug' => 'public'
			);

			$profile_link = trailingslashit( bp_loggedin_user_domain() . '/' . $bp->core->profile->slug );

			// Add the subnav items to the profile
			$sub_nav[] = array(
				'name'            => __( 'Public', 'buddypress' ),
				'slug'            => 'public',
				'parent_url'      => $profile_link,
				'parent_slug'     => $bp->core->profile->slug,
				'screen_function' => 'bp_core_catch_profile_uri'
			);

			parent::setup_nav( $main_nav, $sub_nav );
		}
	}
}

/**
 * Setup the BuddyPress Core component
 *
 * @since BuddyPress (1.6)
 *
 * @global BuddyPress $bp
 */
function bp_setup_core() {
	global $bp;
	$bp->core = new BP_Core();
}
add_action( 'bp_setup_components', 'bp_setup_core', 2 );

?>