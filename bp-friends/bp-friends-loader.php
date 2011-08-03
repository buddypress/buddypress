<?php
/**
 * BuddyPress Friends Streams Loader
 *
 * The friends component is for users to create relationships with each other
 *
 * @package BuddyPress
 * @subpackage Friends Core
 */

// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

class BP_Friends_Component extends BP_Component {

	/**
	 * Start the friends component creation process
	 *
	 * @since 1.5
	 */
	function __construct() {
		parent::start(
			'friends',
			__( 'Friend Connections', 'buddypress' ),
			BP_PLUGIN_DIR
		);
	}

	/**
	 * Include files
	 */
	function includes() {
		// Files to include
		$includes = array(
			'actions',
			'screens',
			'filters',
			'classes',
			'activity',
			'template',
			'functions',
			'notifications',
		);

		parent::includes( $includes );
	}

	/**
	 * Setup globals
	 *
	 * The BP_FRIENDS_SLUG constant is deprecated, and only used here for
	 * backwards compatibility.
	 *
	 * @since 1.5
	 * @global obj $bp
	 */
	function setup_globals() {
		global $bp;

		define ( 'BP_FRIENDS_DB_VERSION', '1800' );

		// Define a slug, if necessary
		if ( !defined( 'BP_FRIENDS_SLUG' ) )
			define( 'BP_FRIENDS_SLUG', $this->id );

		// Global tables for the friends component
		$global_tables = array(
			'table_name'      => $bp->table_prefix . 'bp_friends',
			'table_name_meta' => $bp->table_prefix . 'bp_friends_meta',
		);

		// All globals for the friends component.
		// Note that global_tables is included in this array.
		$globals = array(
			'path'                  => BP_PLUGIN_DIR,
			'slug'                  => BP_FRIENDS_SLUG,
			'has_directory'         => false,
			'search_string'         => __( 'Search Friends...', 'buddypress' ),
			'notification_callback' => 'friends_format_notifications',
			'global_tables'         => $global_tables
		);

		parent::setup_globals( $globals );
	}

	/**
	 * Setup BuddyBar navigation
	 *
	 * @global obj $bp
	 */
	function setup_nav() {
		global $bp;

		// Add 'Friends' to the main navigation
		$main_nav = array(
			'name'                => sprintf( __( 'Friends <span>%d</span>', 'buddypress' ), friends_get_total_friend_count() ),
			'slug'                => $this->slug,
			'position'            => 60,
			'screen_function'     => 'friends_screen_my_friends',
			'default_subnav_slug' => 'my-friends',
			'item_css_id'         => $bp->friends->id
		);

		$friends_link = trailingslashit( $bp->loggedin_user->domain . bp_get_friends_slug() );

		// Add the subnav items to the friends nav item
		$sub_nav[] = array(
			'name'            => __( 'Friendships', 'buddypress' ),
			'slug'            => 'my-friends',
			'parent_url'      => $friends_link,
			'parent_slug'     => bp_get_friends_slug(),
			'screen_function' => 'friends_screen_my_friends',
			'position'        => 10,
			'item_css_id'     => 'friends-my-friends'
		);

		$sub_nav[] = array(
			'name'            => __( 'Requests',   'buddypress' ),
			'slug'            => 'requests',
			'parent_url'      => $friends_link,
			'parent_slug'     => bp_get_friends_slug(),
			'screen_function' => 'friends_screen_requests',
			'position'        => 20,
			'user_has_access' => bp_is_my_profile()
		);

		parent::setup_nav( $main_nav, $sub_nav );
	}

	/**
	 * Set up the admin bar
	 *
	 * @global obj $bp
	 */
	function setup_admin_bar() {
		global $bp;

		// Prevent debug notices
		$wp_admin_nav = array();

		// Menus for logged in user
		if ( is_user_logged_in() ) {

			// Setup the logged in user variables
			$user_domain  = $bp->loggedin_user->domain;
			$friends_link = trailingslashit( $user_domain . $this->slug );

			// Pending friend requests
			if ( $count = count( friends_get_friendship_request_user_ids( $bp->loggedin_user->id ) ) ) {
				$title   = sprintf( __( 'Friends <span class="count">%s</span>',          'buddypress' ), $count );
				$pending = sprintf( __( 'Pending Requests <span class="count">%s</span>', 'buddypress' ), $count );
			} else {
				$title   = __( 'Friends',             'buddypress' );
				$pending = __( 'No Pending Requests', 'buddypress' );
			}

			// Add the "My Account" sub menus
			$wp_admin_nav[] = array(
				'parent' => $bp->my_account_menu_id,
				'id'     => 'my-account-' . $this->id,
				'title'  => $title,
				'href'   => trailingslashit( $friends_link )
			);

			// My Groups
			$wp_admin_nav[] = array(
				'parent' => 'my-account-' . $this->id,
				'title'  => __( 'Friendships', 'buddypress' ),
				'href'   => trailingslashit( $friends_link )
			);

			// Requests
			$wp_admin_nav[] = array(
				'parent' => 'my-account-' . $this->id,
				'title'  => $pending,
				'href'   => trailingslashit( $friends_link . 'requests' )
			);
		}

		parent::setup_admin_bar( $wp_admin_nav );
	}

	/**
	 * Sets up the title for pages and <title>
	 *
	 * @global obj $bp
	 */
	function setup_title() {
		global $bp;

		// Adjust title
		if ( bp_is_friends_component() ) {
			if ( bp_is_my_profile() ) {
				$bp->bp_options_title = __( 'Friendships', 'buddypress' );
			} else {
				$bp->bp_options_avatar = bp_core_fetch_avatar( array(
					'item_id' => $bp->displayed_user->id,
					'type'    => 'thumb'
				) );
				$bp->bp_options_title  = $bp->displayed_user->fullname;
			}
		}

		parent::setup_title();
	}
}
// Create the friends component
$bp->friends = new BP_Friends_Component();

?>