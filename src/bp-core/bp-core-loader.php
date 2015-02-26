<?php

/**
 * BuddyPress Core Loader.
 *
 * Core contains the commonly used functions, classes, and APIs.
 *
 * @package BuddyPress
 * @subpackage Core
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

class BP_Core extends BP_Component {

	/**
	 * Start the members component creation process.
	 *
	 * @since BuddyPress (1.5.0)
	 *
	 * @uses BP_Core::bootstrap()
	 */
	public function __construct() {
		parent::start(
			'core',
			__( 'BuddyPress Core', 'buddypress' ),
			buddypress()->plugin_dir
		);

		$this->bootstrap();
	}

	/**
	 * Populate the global data needed before BuddyPress can continue.
	 *
	 * This involves figuring out the currently required, activated, deactivated,
	 * and optional components.
	 *
	 * @since BuddyPress (1.5.0)
	 */
	private function bootstrap() {
		$bp = buddypress();

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
		$bp->optional_components = apply_filters( 'bp_optional_components', array( 'activity', 'blogs', 'forums', 'friends', 'groups', 'messages', 'notifications', 'settings', 'xprofile' ) );

		// Set the required components
		$bp->required_components = apply_filters( 'bp_required_components', array( 'members' ) );

		// Get a list of activated components
		if ( $active_components = bp_get_option( 'bp-active-components' ) ) {
			$bp->active_components      = apply_filters( 'bp_active_components', $active_components );
			$bp->deactivated_components = apply_filters( 'bp_deactivated_components', array_values( array_diff( array_values( array_merge( $bp->optional_components, $bp->required_components ) ), array_keys( $bp->active_components ) ) ) );

		// Pre 1.5 Backwards compatibility
		} elseif ( $deactivated_components = bp_get_option( 'bp-deactivated-components' ) ) {

			// Trim off namespace and filename
			foreach ( array_keys( (array) $deactivated_components ) as $component ) {
				$trimmed[] = str_replace( '.php', '', str_replace( 'bp-', '', $component ) );
			}

			// Set globals
			$bp->deactivated_components = apply_filters( 'bp_deactivated_components', $trimmed );

			// Setup the active components
			$active_components     = array_fill_keys( array_diff( array_values( array_merge( $bp->optional_components, $bp->required_components ) ), array_values( $bp->deactivated_components ) ), '1' );

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
		foreach( $bp->optional_components as $component ) {
			if ( bp_is_active( $component ) && file_exists( $bp->plugin_dir . '/bp-' . $component . '/bp-' . $component . '-loader.php' ) ) {
				include( $bp->plugin_dir . '/bp-' . $component . '/bp-' . $component . '-loader.php' );
			}
		}

		// Loop through required components
		foreach( $bp->required_components as $component ) {
			if ( file_exists( $bp->plugin_dir . '/bp-' . $component . '/bp-' . $component . '-loader.php' ) ) {
				include( $bp->plugin_dir . '/bp-' . $component . '/bp-' . $component . '-loader.php' );
			}
		}

		// Add Core to required components
		$bp->required_components[] = 'core';

		do_action( 'bp_core_components_included' );
	}

	/**
	 * Include bp-core files.
	 *
	 * @see BP_Component::includes() for description of parameters.
	 *
	 * @param array $includes See {@link BP_Component::includes()}.
	 */
	public function includes( $includes = array() ) {

		if ( !is_admin() )
			return;

		$includes = array(
			'admin'
		);

		parent::includes( $includes );
	}

	/**
	 * Set up bp-core global settings.
	 *
	 * Sets up a majority of the BuddyPress globals that require a minimal
	 * amount of processing, meaning they cannot be set in the BuddyPress class.
	 *
	 * @since BuddyPress (1.5.0)
	 *
	 * @see BP_Component::setup_globals() for description of parameters.
	 *
	 * @param array $args See {@link BP_Component::setup_globals()}.
	 */
	public function setup_globals( $args = array() ) {
		$bp = buddypress();

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

		// Notifications table. Included here for legacy purposes. Use
		// bp-notifications instead.
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
	 * Set up component navigation.
	 *
	 * @since BuddyPress (1.5.0)
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

		 // If xprofile component is disabled, revert to WordPress profile
		if ( !bp_is_active( 'xprofile' ) ) {

			// Fallback values if xprofile is disabled
			if ( ! isset( $bp->core->profile ) ) {
				$bp->core->profile = new stdClass;
			}
			$bp->core->profile->slug = 'profile';
			$bp->active_components[$bp->core->profile->slug] = $bp->core->profile->slug;

			// Add 'Profile' to the main navigation
			$main_nav = array(
				'name'                => _x( 'Profile', 'Main navigation', 'buddypress' ),
				'slug'                => $bp->core->profile->slug,
				'position'            => 20,
				'screen_function'     => 'bp_core_catch_profile_uri',
				'default_subnav_slug' => 'public'
			);

			$profile_link = trailingslashit( bp_loggedin_user_domain() . '/' . $bp->core->profile->slug );

			// Add the subnav items to the profile
			$sub_nav[] = array(
				'name'            => _x( 'View', 'Profile sub nav', 'buddypress' ),
				'slug'            => 'public',
				'parent_url'      => $profile_link,
				'parent_slug'     => $bp->core->profile->slug,
				'screen_function' => 'bp_core_catch_profile_uri'
			);

			parent::setup_nav( $main_nav, $sub_nav );
		}
	}

	/**
	 * Setup cache groups
	 *
	 * @since BuddyPress (2.2.0)
	 */
	public function setup_cache_groups() {

		// Global groups
		wp_cache_add_global_groups( array(
			'bp'
		) );

		parent::setup_cache_groups();
	}
}

/**
 * Set up the BuddyPress Core component.
 *
 * @since BuddyPress (1.6.0)
 *
 * @global BuddyPress $bp BuddyPress global settings object.
 */
function bp_setup_core() {
	buddypress()->core = new BP_Core();
}
add_action( 'bp_loaded', 'bp_setup_core', 0 );
