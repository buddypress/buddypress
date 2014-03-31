<?php
/**
 * BuddyPress Friends Streams Loader
 *
 * The friends component is for users to create relationships with each other.
 *
 * @package BuddyPress
 * @subpackage Friends
 */

// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

class BP_Friends_Component extends BP_Component {

	/**
	 * Start the friends component creation process.
	 *
	 * @since BuddyPress (1.5.0)
	 */
	public function __construct() {
		parent::start(
			'friends',
			__( 'Friend Connections', 'buddypress' ),
			buddypress()->plugin_dir,
			array(
				'adminbar_myaccount_order' => 60
			)
		);
	}

	/**
	 * Include bp-friends files.
	 *
	 * @see BP_Component::includes() for description of parameters.
	 *
	 * @param array $includes See {@link BP_Component::includes()}.
	 */
	public function includes( $includes = array() ) {
		$includes = array(
			'cache',
			'actions',
			'screens',
			'filters',
			'classes',
			'activity',
			'template',
			'functions',
			'notifications',
			'widgets',
		);

		parent::includes( $includes );
	}

	/**
	 * Set up bp-friends global settings.
	 *
	 * The BP_FRIENDS_SLUG constant is deprecated, and only used here for
	 * backwards compatibility.
	 *
	 * @since BuddyPress (1.5.0)
	 *
	 * @see BP_Component::setup_globals() for description of parameters.
	 *
	 * @param array $args See {@link BP_Component::setup_globals()}.
	 */
	public function setup_globals( $args = array() ) {
		$bp = buddypress();

		// Deprecated. Do not use.
		// Defined conditionally to support unit tests.
		if ( ! defined( 'BP_FRIENDS_DB_VERSION' ) ) {
			define( 'BP_FRIENDS_DB_VERSION', '1800' );
		}

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
		$args = array(
			'slug'                  => BP_FRIENDS_SLUG,
			'has_directory'         => false,
			'search_string'         => __( 'Search Friends...', 'buddypress' ),
			'notification_callback' => 'friends_format_notifications',
			'global_tables'         => $global_tables
		);

		parent::setup_globals( $args );
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

		// Add 'Friends' to the main navigation
		$count    = friends_get_total_friend_count();
		$class    = ( 0 === $count ) ? 'no-count' : 'count';
		$main_nav = array(
			'name'                => sprintf( __( 'Friends <span class="%s">%s</span>', 'buddypress' ), esc_attr( $class ), number_format_i18n( $count ) ),
			'slug'                => $this->slug,
			'position'            => 60,
			'screen_function'     => 'friends_screen_my_friends',
			'default_subnav_slug' => 'my-friends',
			'item_css_id'         => $bp->friends->id
		);

		// Determine user to use
		if ( bp_displayed_user_domain() ) {
			$user_domain = bp_displayed_user_domain();
		} elseif ( bp_loggedin_user_domain() ) {
			$user_domain = bp_loggedin_user_domain();
		} else {
			return;
		}

		$friends_link = trailingslashit( $user_domain . bp_get_friends_slug() );

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
			'user_has_access' => bp_core_can_edit_settings()
		);

		parent::setup_nav( $main_nav, $sub_nav );
	}

	/**
	 * Set up bp-friends integration with the WordPress admin bar.
	 *
	 * @since BuddyPress (1.5.0)
	 *
	 * @see BP_Component::setup_admin_bar() for a description of arguments.
	 *
	 * @param array $wp_admin_nav See BP_Component::setup_admin_bar()
	 *        for description.
	 */
	public function setup_admin_bar( $wp_admin_nav = array() ) {
		$bp = buddypress();

		// Menus for logged in user
		if ( is_user_logged_in() ) {

			// Setup the logged in user variables
			$user_domain  = bp_loggedin_user_domain();
			$friends_link = trailingslashit( $user_domain . $this->slug );

			// Pending friend requests
			$count = count( friends_get_friendship_request_user_ids( bp_loggedin_user_id() ) );
			if ( !empty( $count ) ) {
				$title   = sprintf( __( 'Friends <span class="count">%s</span>',          'buddypress' ), number_format_i18n( $count ) );
				$pending = sprintf( __( 'Pending Requests <span class="count">%s</span>', 'buddypress' ), number_format_i18n( $count ) );
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
				'id'     => 'my-account-' . $this->id . '-friendships',
				'title'  => __( 'Friendships', 'buddypress' ),
				'href'   => trailingslashit( $friends_link )
			);

			// Requests
			$wp_admin_nav[] = array(
				'parent' => 'my-account-' . $this->id,
				'id'     => 'my-account-' . $this->id . '-requests',
				'title'  => $pending,
				'href'   => trailingslashit( $friends_link . 'requests' )
			);
		}

		parent::setup_admin_bar( $wp_admin_nav );
	}

	/**
	 * Set up the title for pages and <title>.
	 */
	public function setup_title() {
		$bp = buddypress();

		// Adjust title
		if ( bp_is_friends_component() ) {
			if ( bp_is_my_profile() ) {
				$bp->bp_options_title = __( 'Friendships', 'buddypress' );
			} else {
				$bp->bp_options_avatar = bp_core_fetch_avatar( array(
					'item_id' => bp_displayed_user_id(),
					'type'    => 'thumb',
					'alt'     => sprintf( __( 'Profile picture of %s', 'buddypress' ), bp_get_displayed_user_fullname() )
				) );
				$bp->bp_options_title = bp_get_displayed_user_fullname();
			}
		}

		parent::setup_title();
	}
}

/**
 * Set up the bp-forums component.
 */
function bp_setup_friends() {
	buddypress()->friends = new BP_Friends_Component();
}
add_action( 'bp_setup_components', 'bp_setup_friends', 6 );
