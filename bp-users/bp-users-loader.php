<?php

/**
 * BuddyPress User Loader
 *
 * A users component to help contain all of the user specific slugs
 *
 * @package BuddyPress
 * @subpackage User Core
 */

class BP_User_Component extends BP_Component {

	/**
	 * Start the users component creation process
	 *
	 * @since BuddyPress {unknown}
	 */
	function BP_User_Component() {
		parent::start( 'members', __( 'Members', 'buddypress' ) );
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
		global $bp;

		// Define a slug, if necessary
		if ( !defined( 'BP_MEMBERS_SLUG' ) )
			define( 'BP_MEMBERS_SLUG', $this->id );

		// Do some slug checks
		$this->slug      = BP_MEMBERS_SLUG;
		$this->root_slug = isset( $bp->pages->users->slug ) ? $bp->pages->users->slug : $this->slug;

		// Tables
		$this->table_name      = $bp->table_prefix . 'bp_users';
		$this->table_name_meta = $bp->table_prefix . 'bp_users_meta';
	}

	/**
	 * Include files
	 *
	 * @global obj $bp
	 */
	function _includes() {
		require_once( BP_PLUGIN_DIR . '/bp-users/bp-users-signup.php'        );
		require_once( BP_PLUGIN_DIR . '/bp-users/bp-users-actions.php'       );
		require_once( BP_PLUGIN_DIR . '/bp-users/bp-users-filters.php'       );
		require_once( BP_PLUGIN_DIR . '/bp-users/bp-users-screens.php'       );
		require_once( BP_PLUGIN_DIR . '/bp-users/bp-users-template.php'      );
		require_once( BP_PLUGIN_DIR . '/bp-users/bp-users-functions.php'     );
		require_once( BP_PLUGIN_DIR . '/bp-users/bp-users-notifications.php' );
	}

	/**
	 * Setup BuddyBar navigation
	 *
	 * @global obj $bp
	 */
	function _setup_nav() {
		global $bp;

		return false;

		// Add 'User' to the main navigation
		bp_core_new_nav_item( array(
			'name'                => __( 'User', 'buddypress' ),
			'slug'                => $bp->users->slug,
			'position'            => 10,
			'screen_function'     => 'bp_users_screen_my_users',
			'default_subnav_slug' => 'just-me',
			'item_css_id'         => $bp->users->id )
		);

		// Stop if there is no user displayed or logged in
		if ( !is_user_logged_in() && !isset( $bp->displayed_user->id ) )
			return;

		// User links
		$user_domain   = ( isset( $bp->displayed_user->domain ) )               ? $bp->displayed_user->domain               : $bp->loggedin_user->domain;
		$user_login    = ( isset( $bp->displayed_user->userdata->user_login ) ) ? $bp->displayed_user->userdata->user_login : $bp->loggedin_user->userdata->user_login;
		$users_link = $user_domain . $bp->users->slug . '/';

		// Add the subnav items to the users nav item if we are using a theme that supports this
		bp_core_new_subnav_item( array(
			'name'            => __( 'Personal', 'buddypress' ),
			'slug'            => 'just-me',
			'parent_url'      => $users_link,
			'parent_slug'     => $bp->users->slug,
			'screen_function' => 'bp_users_screen_my_users',
			'position'        => 10
		) );

		// Additional menu if friends is active
		if ( bp_is_active( 'friends' ) ) {
			bp_core_new_subnav_item( array(
				'name'            => __( 'Friends', 'buddypress' ),
				'slug'            => BP_FRIENDS_SLUG,
				'parent_url'      => $users_link,
				'parent_slug'     => $bp->users->slug,
				'screen_function' => 'bp_users_screen_friends',
				'position'        => 20,
				'item_css_id'     => 'users-friends'
			) );
		}

		// Additional menu if groups is active
		if ( bp_is_active( 'groups' ) ) {
			bp_core_new_subnav_item( array(
				'name'            => __( 'Groups', 'buddypress' ),
				'slug'            => BP_GROUPS_SLUG,
				'parent_url'      => $users_link,
				'parent_slug'     => $bp->users->slug,
				'screen_function' => 'bp_users_screen_groups',
				'position'        => 30,
				'item_css_id'     => 'users-groups'
			) );
		}

		// Favorite users items
		bp_core_new_subnav_item( array(
			'name'            => __( 'Favorites', 'buddypress' ),
			'slug'            => 'favorites',
			'parent_url'      => $users_link,
			'parent_slug'     => $bp->users->slug,
			'screen_function' => 'bp_users_screen_favorites',
			'position'        => 40,
			'item_css_id'     => 'users-favs'
		) );

		// @ mentions
		bp_core_new_subnav_item( array(
			'name'            => sprintf( __( '@%s Mentions', 'buddypress' ), $user_login ),
			'slug'            => 'mentions',
			'parent_url'      => $users_link,
			'parent_slug'     => $bp->users->slug,
			'screen_function' => 'bp_users_screen_mentions',
			'position'        => 50,
			'item_css_id'     => 'users-mentions'
		) );

		// Adjust title based on view
		if ( bp_is_users_component() ) {
			if ( bp_is_my_profile() ) {
				$bp->bp_options_title = __( 'My User', 'buddypress' );
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
// Create the users component
$bp->users = new BP_User_Component();

?>
