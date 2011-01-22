<?php

/**
 * BuddyPress Forums Loader
 *
 * A discussion forums component. Comes bundled with bbPress stand-alone.
 *
 * @package BuddyPress
 * @subpackage Forums Core
 */

class BP_Forums_Component extends BP_Component {

	/**
	 * Start the forums component creation process
	 *
	 * @since BuddyPress {unknown}
	 */
	function BP_Forums_Component() {
		parent::start( 'forums', __( 'Discussion Forums', 'buddypress' ) );
	}

	/**
	 * Setup globals
	 *
	 * The BP_FORUMS_SLUG constant is deprecated, and only used here for
	 * backwards compatibility.
	 *
	 * @since BuddyPress {unknown}
	 * @global obj $bp
	 */
	function _setup_globals() {
		global $bp;

		// Define the parent forum ID
		if ( !defined( 'BP_FORUMS_PARENT_FORUM_ID' ) )
			define( 'BP_FORUMS_PARENT_FORUM_ID', 1 );

		// Define a slug, if necessary
		if ( !defined( 'BP_FORUMS_SLUG' ) )
			define( 'BP_FORUMS_SLUG', $this->id );

		// Do some slug checks
		$this->slug      = BP_FORUMS_SLUG;
		$this->root_slug = isset( $bp->pages->forums->slug ) ? $bp->pages->forums->slug : $this->slug;

		// The location of the bbPress stand-alone config file
		if ( isset( $bp->site_options['bb-config-location'] ) )
			$this->bbconfig = $bp->site_options['bb-config-location'];

		// Register this in the active components array
		$bp->active_components[$this->id] = $this->id;

		// The default text for the blogs directory search box
		$bp->default_search_strings[$this->id] = __( 'Search Forums...', 'buddypress' );
	}

	/**
	 * Include files
	 */
	function _includes() {

		// Support for bbPress stand-alone
		if ( !defined( 'BB_PATH' ) )
			require ( BP_PLUGIN_DIR . '/bp-forums/bp-forums-bbpress-sa.php' );

		require ( BP_PLUGIN_DIR . '/bp-forums/bp-forums-actions.php'   );
		require ( BP_PLUGIN_DIR . '/bp-forums/bp-forums-screens.php'   );
		require ( BP_PLUGIN_DIR . '/bp-forums/bp-forums-filters.php'   );
		require ( BP_PLUGIN_DIR . '/bp-forums/bp-forums-template.php'  );
		require ( BP_PLUGIN_DIR . '/bp-forums/bp-forums-functions.php' );
	}

	/**
	 * Setup BuddyBar navigation
	 *
	 * @global obj $bp
	 */
	function _setup_nav() {
		global $bp;

		// Add 'Forums' to the main navigation
		bp_core_new_nav_item( array(
			'name'                => __( 'Forums', 'buddypress' ),
			'slug'                => $this->slug,
			'position'            => 80,
			'screen_function'     => 'bp_forums_screen_topics',
			'default_subnav_slug' => 'topics',
			'item_css_id'         => $this->id )
		);

		// Stop if there is no user displayed or logged in
		if ( !is_user_logged_in() && !isset( $bp->displayed_user->id ) )
			return;

		// Determine user to use
		if ( isset( $bp->displayed_user->domain ) ) {
			$user_domain = $bp->displayed_user->domain;
			$user_login  = $bp->displayed_user->userdata->user_login;
		} elseif ( isset( $bp->loggedin_user->domain ) ) {
			$user_domain = $bp->loggedin_user->domain;
			$user_login  = $bp->loggedin_user->userdata->user_login;
		} else {
			return;
		}

		// User link
		$forums_link = trailingslashit( $user_domain . $this->slug );

		// Additional menu if friends is active
		bp_core_new_subnav_item( array(
			'name'            => __( 'Topics Started', 'buddypress' ),
			'slug'            => 'topics',
			'parent_url'      => $forums_link,
			'parent_slug'     => $this->slug,
			'screen_function' => 'bp_forums_screen_topics',
			'position'        => 20,
			'item_css_id'     => 'forums-friends'
		) );

		// Additional menu if friends is active
		bp_core_new_subnav_item( array(
			'name'            => __( 'Replies', 'buddypress' ),
			'slug'            => 'replies',
			'parent_url'      => $forums_link,
			'parent_slug'     => $this->slug,
			'screen_function' => 'bp_forums_screen_topics',
			'position'        => 40,
			'item_css_id'     => 'forums-friends'
		) );

		// Favorite forums items
		bp_core_new_subnav_item( array(
			'name'            => __( 'Favorite Topics', 'buddypress' ),
			'slug'            => 'favorites',
			'parent_url'      => $forums_link,
			'parent_slug'     => $this->slug,
			'screen_function' => 'bp_forums_screen_favorites',
			'position'        => 60,
			'item_css_id'     => 'forums-favs'
		) );

		// Adjust title based on view
		if ( bp_is_forums_component() ) {
			if ( bp_is_my_profile() ) {
				$bp->bp_options_title = __( 'My Forums', 'buddypress' );
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
// Create the forums component
$bp->forums = new BP_Forums_Component();

?>
