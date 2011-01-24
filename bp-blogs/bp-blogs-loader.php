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
		$this->table_name          = $bp->table_prefix . 'bp_user_blogs';
		$this->table_name_blogmeta = $bp->table_prefix . 'bp_user_blogs_blogmeta';

		// Notifications
		$this->notification_callback = 'bp_blogs_format_notifications';

		// Register this in the active components array
		$bp->active_components[$this->id] = $this->id;

		// The default text for the blogs directory search box
		$bp->default_search_strings[$this->id] = __( 'Search Blogs...', 'buddypress' );
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

		/**
		 * Blog/post/comment menus should not appear on single WordPress setups.
		 * Although comments and posts made by users will still show on their
		 * activity stream.
		 */
		if ( !is_multisite() )
			return false;

		// Add 'Blogs' to the main navigation
		bp_core_new_nav_item( array(
			'name'                => sprintf( __( 'Blogs <span>(%d)</span>', 'buddypress' ), bp_blogs_total_blogs_for_user() ),
			'slug'                => $this->slug,
			'position'            => 30,
			'screen_function'     => 'bp_blogs_screen_my_blogs',
			'default_subnav_slug' => 'my-blogs',
			'item_css_id'         => $this->id
		) );

		// Set up the component options navigation for Blog
		if ( $bp->blogs->slug == $bp->current_component ) {
			if ( bp_is_my_profile() ) {
				if ( function_exists('xprofile_setup_nav') ) {
					$bp->bp_options_title = __( 'My Blogs', 'buddypress' );
				}

			// If we are not viewing the logged in user, set up the current
			// users avatar and name
			} else {
				$bp->bp_options_avatar = bp_core_fetch_avatar( array(
					'item_id' => $bp->displayed_user->id,
					'type'    => 'thumb'
				) );
				$bp->bp_options_title = $bp->displayed_user->fullname;
			}
		}
	}
}
// Create the blogs component
$bp->blogs = new BP_Blogs_Component();

?>
