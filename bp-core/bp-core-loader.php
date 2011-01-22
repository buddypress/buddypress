<?php

// Load the files containing functions that we globally will need.
require ( BP_PLUGIN_DIR . '/bp-core/bp-core-hooks.php'      );
require ( BP_PLUGIN_DIR . '/bp-core/bp-core-cssjs.php'      );
require ( BP_PLUGIN_DIR . '/bp-core/bp-core-classes.php'    );
require ( BP_PLUGIN_DIR . '/bp-core/bp-core-filters.php'    );
require ( BP_PLUGIN_DIR . '/bp-core/bp-core-avatars.php'    );
require ( BP_PLUGIN_DIR . '/bp-core/bp-core-widgets.php'    );
require ( BP_PLUGIN_DIR . '/bp-core/bp-core-template.php'   );
require ( BP_PLUGIN_DIR . '/bp-core/bp-core-buddybar.php'   );
require ( BP_PLUGIN_DIR . '/bp-core/bp-core-catchuri.php'   );
require ( BP_PLUGIN_DIR . '/bp-core/bp-core-component.php'  );
require ( BP_PLUGIN_DIR . '/bp-core/bp-core-functions.php'  );

// Do we load deprecated functions?
if ( !defined( 'BP_SKIP_DEPRECATED' ) )
	require ( BP_PLUGIN_DIR . '/bp-core/deprecated/1.3.php' );

// If BP_DISABLE_ADMIN_BAR is defined, do not load the global admin bar.
if ( !defined( 'BP_DISABLE_ADMIN_BAR' ) )
	require ( BP_PLUGIN_DIR . '/bp-core/bp-core-adminbar.php' );

/** "And now for something completely different" ******************************/

/**
 * Sets up default global BuddyPress configuration settings and stores
 * them in a $bp variable.
 *
 * @package BuddyPress Core Core
 * @global $bp The global BuddyPress settings variable created in bp_core_setup_globals()
 * @uses bp_core_get_user_domain() Returns the domain for a user
 */
function bp_core_setup_globals() {
	global $bp;
	//global $current_action, $current_blog;
	global $bp_pages;
	//global $action_variables;

	// Get the base database prefix
	$bp->table_prefix = bp_core_get_table_prefix();

	// The domain for the root of the site where the main blog resides
	$bp->root_domain  = bp_core_get_root_domain();

	// The names of the core WordPress pages used to display BuddyPress content
	$bp->pages        = $bp_pages;

	/** Component and Action **************************************************/

	// Used for overriding the 2nd level navigation menu so it can be used to
	// display custom navigation for an item (for example a group)
	$bp->is_single_item = false;

	// The default component to use if none are set and someone
	// visits: http://domain.com/members/andy
	if ( !defined( 'BP_DEFAULT_COMPONENT' ) ) {
		if ( isset( $bp->pages->activity ) )
			$bp->default_component = $bp->activity->id;
		else
			$bp->default_component = $bp->profile->id;
	} else {
		$bp->default_component     = BP_DEFAULT_COMPONENT;
	}

	// Fetches all of the core BuddyPress settings in one fell swoop
	$bp->site_options = bp_core_get_site_options();

	// Sets up the array container for the component navigation rendered
	// by bp_get_nav()
	$bp->bp_nav            = array();

	// Sets up the array container for the component options navigation
	// rendered by bp_get_options_nav()
	$bp->bp_options_nav    = array();

	// Contains an array of all the active components. The key is the slug,
	// value the internal ID of the component.
	$bp->active_components = array();

	/** Avatars ***************************************************************/

	// Fetches the default Gravatar image to use if the user/group/blog has no avatar or gravatar
	$bp->grav_default->user  = apply_filters( 'bp_user_gravatar_default',  $bp->site_options['avatar_default'] );
	$bp->grav_default->group = apply_filters( 'bp_group_gravatar_default', $bp->grav_default->user );
	$bp->grav_default->blog  = apply_filters( 'bp_blog_gravatar_default',  $bp->grav_default->user );

	// Notifications Table
	$bp->core->table_name_notifications = $bp->table_prefix . 'bp_notifications';

	do_action( 'bp_core_setup_globals' );
}
add_action( 'bp_setup_globals', 'bp_core_setup_globals', 1 );

/**
 * Sets up the profile navigation item if the Xprofile component is not installed.
 *
 * @package BuddyPress Core
 * @global $bp The global BuddyPress settings variable created in bp_core_setup_globals()
 * @uses bp_core_new_nav_item() Adds a navigation item to the top level buddypress navigation
 * @uses bp_core_new_subnav_item() Adds a sub navigation item to a nav item
 * @uses bp_is_my_profile() Returns true if the current user being viewed is equal the logged in user
 * @uses bp_core_fetch_avatar() Returns the either the thumb or full avatar URL for the user_id passed
 */
function bp_core_setup_nav() {
	global $bp;

	/***
	 * If the extended profiles component is disabled, we need to revert to using the
	 * built in WordPress profile information
	 */
	if ( !bp_is_active( 'profile' ) ) {

		// Fallback values if xprofile is disabled
		$bp->core->profile->slug = 'profile';
		$bp->active_components[$bp->core->profile->slug] = $bp->core->profile->slug;

		// Add 'Profile' to the main navigation
		bp_core_new_nav_item( array(
			'name'                => __( 'Profile', 'buddypress' ),
			'slug'                => $bp->core->profile->slug,
			'position'            => 20,
			'screen_function'     => 'bp_core_catch_profile_uri',
			'default_subnav_slug' => 'public'
		) );

		$profile_link = trailingslashit( $bp->loggedin_user->domain . '/' . $bp->core->profile->slug );

		// Add the subnav items to the profile
		bp_core_new_subnav_item( array(
			'name'            => __( 'Public', 'buddypress' ),
			'slug'            => 'public',
			'parent_url'      => $profile_link,
			'parent_slug'     => $bp->core->profile->slug,
			'screen_function' => 'bp_core_catch_profile_uri'
		) );

		// Looking at a profile
		if ( 'profile' == $bp->current_component ) {
			if ( bp_is_my_profile() ) {
				$bp->bp_options_title = __( 'My Profile', 'buddypress' );
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
add_action( 'bp_setup_nav', 'bp_core_setup_nav' );


?>
