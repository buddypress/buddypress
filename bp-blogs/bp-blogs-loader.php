<?php

/**
 * BuddyPress Blogs Streams Loader
 *
 * An blogs stream component, for users, groups, and blog tracking.
 *
 * @package BuddyPress
 * @subpackage Blogs Core
 */

class BP_Blogs_Component extends BP_Component {

	/**
	 * Start the blogs component creation process
	 *
	 * @since BuddyPress {unknown}
	 */
	function BP_Blogs_Component() {
		parent::start( 'blogs', __( 'Blogs Streams', 'buddypress' ) );
	}

	/**
	 * Setup globals
	 *
	 * The BP_BLOGS_SLUG constant is deprecated, and only used here for
	 * backwards compatibility.
	 *
	 * @since BuddyPress {unknown}
	 * @global obj $bp
	 */
	function _setup_globals() {
		global $bp;

		if ( !defined( 'BP_BLOGS_SLUG' ) )
			define ( 'BP_BLOGS_SLUG', $this->id );

		// Do some slug checks
		$this->slug      = BP_BLOGS_SLUG;
		$this->root_slug = isset( $bp->pages->blogs->slug ) ? $bp->pages->blogs->slug : $this->slug;

		// Tables
		$this->table_name      = $bp->table_prefix . 'bp_user_blogs';
		$this->table_name_meta = $bp->table_prefix . 'bp_user_blogs_blogmeta';

		// Notifications
		$bp->blogs->notification_callback = 'bp_blogs_format_notifications';

		// Register this in the active components array
		$bp->active_components[$this->slug] = $this->id;

		// The default text for the blogs directory search box
		$bp->default_search_strings[$this->slug] = __( 'Search Blogs...', 'buddypress' );
	}

	/**
	 * Include files
	 */
	function _includes() {
		require_once( BP_PLUGIN_DIR . '/bp-blogs/bp-blogs-cache.php'        );
		require_once( BP_PLUGIN_DIR . '/bp-blogs/bp-blogs-classes.php'      );
		require_once( BP_PLUGIN_DIR . '/bp-blogs/bp-blogs-screens.php'      );
		require_once( BP_PLUGIN_DIR . '/bp-blogs/bp-blogs-actions.php'      );
		require_once( BP_PLUGIN_DIR . '/bp-blogs/bp-blogs-activity.php'     );
		require_once( BP_PLUGIN_DIR . '/bp-blogs/bp-blogs-template.php'     );
		require_once( BP_PLUGIN_DIR . '/bp-blogs/bp-blogs-functions.php'    );

		if ( is_multisite() )
			require_once( BP_PLUGIN_DIR . '/bp-blogs/bp-blogs-widgets.php' );
	}

	/**
	 * Setup BuddyBar navigation
	 *
	 * @global obj $bp
	 */
	function _setup_nav() {
		global $bp;

		// Add 'Blogs' to the main navigation
		bp_core_new_nav_item( array(
			'name'                => __( 'Blogs', 'buddypress' ),
			'slug'                => $bp->blogs->slug,
			'position'            => 10,
			'screen_function'     => 'bp_blogs_screen_my_blogs',
			'default_subnav_slug' => 'just-me',
			'item_css_id'         => $bp->blogs->id )
		);

		// Stop if there is no user displayed or logged in
		if ( !is_user_logged_in() && !isset( $bp->displayed_user->id ) )
			return;

		// User links
		$user_domain   = ( isset( $bp->displayed_user->domain ) )               ? $bp->displayed_user->domain               : $bp->loggedin_user->domain;
		$user_login    = ( isset( $bp->displayed_user->userdata->user_login ) ) ? $bp->displayed_user->userdata->user_login : $bp->loggedin_user->userdata->user_login;
		$blogs_link = $user_domain . $bp->blogs->slug . '/';

		// Add the subnav items to the blogs nav item if we are using a theme that supports this
		bp_core_new_subnav_item( array(
			'name'            => __( 'Personal', 'buddypress' ),
			'slug'            => 'just-me',
			'parent_url'      => $blogs_link,
			'parent_slug'     => $bp->blogs->slug,
			'screen_function' => 'bp_blogs_screen_my_blogs',
			'position'        => 10
		) );

		// Additional menu if friends is active
		if ( bp_is_active( 'friends' ) ) {
			bp_core_new_subnav_item( array(
				'name'            => __( 'Friends', 'buddypress' ),
				'slug'            => BP_FRIENDS_SLUG,
				'parent_url'      => $blogs_link,
				'parent_slug'     => $bp->blogs->slug,
				'screen_function' => 'bp_blogs_screen_friends',
				'position'        => 20,
				'item_css_id'     => 'blogs-friends'
			) );
		}

		// Additional menu if groups is active
		if ( bp_is_active( 'groups' ) ) {
			bp_core_new_subnav_item( array(
				'name'            => __( 'Groups', 'buddypress' ),
				'slug'            => BP_GROUPS_SLUG,
				'parent_url'      => $blogs_link,
				'parent_slug'     => $bp->blogs->slug,
				'screen_function' => 'bp_blogs_screen_groups',
				'position'        => 30,
				'item_css_id'     => 'blogs-groups'
			) );
		}

		// Favorite blogs items
		bp_core_new_subnav_item( array(
			'name'            => __( 'Favorites', 'buddypress' ),
			'slug'            => 'favorites',
			'parent_url'      => $blogs_link,
			'parent_slug'     => $bp->blogs->slug,
			'screen_function' => 'bp_blogs_screen_favorites',
			'position'        => 40,
			'item_css_id'     => 'blogs-favs'
		) );

		// @ mentions
		bp_core_new_subnav_item( array(
			'name'            => sprintf( __( '@%s Mentions', 'buddypress' ), $user_login ),
			'slug'            => 'mentions',
			'parent_url'      => $blogs_link,
			'parent_slug'     => $bp->blogs->slug,
			'screen_function' => 'bp_blogs_screen_mentions',
			'position'        => 50,
			'item_css_id'     => 'blogs-mentions'
		) );

		// Adjust title based on view
		if ( bp_is_blogs_component() ) {
			if ( bp_is_my_profile() ) {
				$bp->bp_options_title = __( 'My Blogs', 'buddypress' );
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
// Create the blogs component
$bp->blogs = new BP_Blogs_Component();

?>



<?php

function bp_blogs_setup_globals() {
	global $bp, $wpdb;

	if ( !defined( 'BP_BLOGS_SLUG' ) && isset( $bp->pages->blogs->slug ) )
		define ( 'BP_BLOGS_SLUG', bp_core_component_slug_from_root_slug( $bp->pages->blogs->slug ) );
	else if ( !defined( 'BP_BLOGS_SLUG' ) )
		define ( 'BP_BLOGS_SLUG', 'blogs' );

	// For internal identification
	$bp->blogs->id   = 'blogs';
	$bp->blogs->name = !empty( $bp->pages->blogs->name ) ? $bp->pages->blogs->name : 'blogs';

	// Slugs
	$bp->blogs->slug      = BP_BLOGS_SLUG;
	$bp->blogs->root_slug = !empty( $bp->pages->blogs->slug ) ? $bp->pages->blogs->slug : BP_BLOGS_SLUG;

	// Tables
	$bp->blogs->table_name          = $bp->table_prefix . 'bp_user_blogs';
	$bp->blogs->table_name_blogmeta = $bp->table_prefix . 'bp_user_blogs_blogmeta';

	// Notifications
	$bp->blogs->notification_callback      = 'bp_blogs_format_notifications';

	// Register this in the active components array
	$bp->active_components[$bp->blogs->slug]      = $bp->blogs->id;

	// The default text for the blogs directory search box
	$bp->default_search_strings[$bp->blogs->slug] = __( 'Search Blogs...', 'buddypress' );

	do_action( 'bp_blogs_setup_globals' );
}
add_action( 'bp_setup_globals', 'bp_blogs_setup_globals' );

/**
 * Adds "Blog" to the navigation arrays for the current and logged in user.
 *
 * @package BuddyPress Blogs
 * @global $bp The global BuddyPress settings variable created in bp_core_setup_globals()
 * @uses bp_is_my_profile() Checks to see if the current user being viewed is the logged in user
 */
function bp_blogs_setup_nav() {
	global $bp;

	/**
	 * Blog/post/comment menus should not appear on single WordPress setups.
	 * Although comments and posts made by users will still show on their
	 * activity stream.
	 */
	if ( !is_multisite() )
		return false;

	// Add 'Blogs' to the main navigation
	bp_core_new_nav_item( array( 'name' => sprintf( __( 'Blogs <span>(%d)</span>', 'buddypress' ), bp_blogs_total_blogs_for_user() ), 'slug' => $bp->blogs->slug, 'position' => 30, 'screen_function' => 'bp_blogs_screen_my_blogs', 'default_subnav_slug' => 'my-blogs', 'item_css_id' => $bp->blogs->id ) );

	$blogs_link = $bp->loggedin_user->domain . $bp->blogs->slug . '/';

	// Set up the component options navigation for Blog
	if ( $bp->blogs->slug == $bp->current_component ) {
		if ( bp_is_my_profile() ) {
			if ( function_exists('xprofile_setup_nav') ) {
				$bp->bp_options_title = __('My Blogs', 'buddypress');
			}
		} else {
			// If we are not viewing the logged in user, set up the current
			// users avatar and name
			$bp->bp_options_avatar = bp_core_fetch_avatar( array( 'item_id' => $bp->displayed_user->id, 'type' => 'thumb' ) );
			$bp->bp_options_title = $bp->displayed_user->fullname;
		}
	}

	do_action( 'bp_blogs_setup_nav' );
}
add_action( 'bp_setup_nav', 'bp_blogs_setup_nav' );

function bp_blogs_directory_blogs_setup() {
	global $bp;

	if ( is_multisite() && $bp->current_component == $bp->blogs->slug && empty( $bp->current_action ) ) {
		$bp->is_directory = true;

		do_action( 'bp_blogs_directory_blogs_setup' );
		bp_core_load_template( apply_filters( 'bp_blogs_template_directory_blogs_setup', 'blogs/index' ) );
	}
}
add_action( 'wp', 'bp_blogs_directory_blogs_setup', 2 );

?>
