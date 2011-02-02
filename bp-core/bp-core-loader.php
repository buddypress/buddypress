<?php

// Require all of the BuddyPress core libraries
require_once( BP_PLUGIN_DIR . '/bp-core/bp-core-cache.php'  );
require_once( BP_PLUGIN_DIR . '/bp-core/bp-core-hooks.php'  );
require_once( BP_PLUGIN_DIR . '/bp-core/bp-core-cssjs.php'  );
require_once( BP_PLUGIN_DIR . '/bp-core/bp-core-classes.php'  );
require_once( BP_PLUGIN_DIR . '/bp-core/bp-core-filters.php'  );
require_once( BP_PLUGIN_DIR . '/bp-core/bp-core-avatars.php'  );
require_once( BP_PLUGIN_DIR . '/bp-core/bp-core-widgets.php'  );
require_once( BP_PLUGIN_DIR . '/bp-core/bp-core-template.php'  );
require_once( BP_PLUGIN_DIR . '/bp-core/bp-core-buddybar.php'  );
require_once( BP_PLUGIN_DIR . '/bp-core/bp-core-catchuri.php'  );
require_once( BP_PLUGIN_DIR . '/bp-core/bp-core-component.php'  );
require_once( BP_PLUGIN_DIR . '/bp-core/bp-core-functions.php'  );

// Load deprecated functions
if ( !defined( 'BP_SKIP_DEPRECATED' ) )
	require_once( BP_PLUGIN_DIR . '/bp-core/deprecated/1.3.php'  );

// Load the WP admin bar.
if ( !defined( 'BP_DISABLE_ADMIN_BAR' ) )
	require_once( BP_PLUGIN_DIR . '/bp-core/bp-core-adminbar.php'  );

/** "And now for something completely different" ******************************/

class BP_Core extends BP_Component {

	function BP_Core() {
		parent::start(
			'_core',
			__( 'BuddyPress Core', 'buddypress' )
			, BP_PLUGIN_DIR
		);
	}

	function _setup_globals() {
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
			$bp->site_options = bp_core_get_site_options();

		// The names of the core WordPress pages used to display BuddyPress content
		if ( empty( $bp->pages ) )
			$bp->pages = bp_core_get_page_names();

		/** Component and Action **********************************************/

		// Used for overriding the 2nd level navigation menu so it can be used to
		// display custom navigation for an item (for example a group)
		$bp->is_single_item = false;

		// Sets up the array container for the component navigation rendered
		// by bp_get_nav()
		$bp->bp_nav            = array();

		// Sets up the array container for the component options navigation
		// rendered by bp_get_options_nav()
		$bp->bp_options_nav    = array();

		// Contains an array of all the active components. The key is the slug,
		// value the internal ID of the component.
		//$bp->active_components = array();

		/** Basic current user data *******************************************/

		// Logged in user is the 'current_user'
		$current_user            = wp_get_current_user();

		// The user ID of the user who is currently logged in.
		$bp->loggedin_user->id   = $current_user->ID;

		/** Avatars ***********************************************************/

		// Fetches the default Gravatar image to use if the user/group/blog has no avatar or gravatar
		$bp->grav_default->user  = apply_filters( 'bp_user_gravatar_default',  $bp->site_options['avatar_default'] );
		$bp->grav_default->group = apply_filters( 'bp_group_gravatar_default', $bp->grav_default->user );
		$bp->grav_default->blog  = apply_filters( 'bp_blog_gravatar_default',  $bp->grav_default->user );

		// Notifications Table
		$bp->core->table_name_notifications = $bp->table_prefix . 'bp_notifications';

		do_action( 'bp_core_setup_globals' );
	}

	function _setup_nav() {
		global $bp;

		/***
		 * If the extended profiles component is disabled, we need to revert to using the
		 * built in WordPress profile information
		 */
		if ( !bp_is_active( 'xprofile' ) ) {

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

			$profile_link = trailingslashit( $bp->loggedin_user->domain . '/' . $bp->core->profile->slug );

			// Add the subnav items to the profile
			$sub_nav[] = array(
				'name'            => __( 'Public', 'buddypress' ),
				'slug'            => 'public',
				'parent_url'      => $profile_link,
				'parent_slug'     => $bp->core->profile->slug,
				'screen_function' => 'bp_core_catch_profile_uri'
			);
		}
	}
}
$bp->core = new BP_Core();

?>
