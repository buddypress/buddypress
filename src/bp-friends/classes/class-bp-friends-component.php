<?php
/**
 * BuddyPress Friends Streams Loader.
 *
 * The friends component is for users to create relationships with each other.
 *
 * @package BuddyPress
 * @subpackage FriendsComponent
 * @since 1.5.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Defines the BuddyPress Friends Component.
 *
 * @since 1.5.0
 */
class BP_Friends_Component extends BP_Component {

	/**
	 * Start the friends component creation process.
	 *
	 * @since 1.5.0
	 */
	public function __construct() {
		parent::start(
			'friends',
			_x( 'Friend Connections', 'Friends screen page <title>', 'buddypress' ),
			buddypress()->plugin_dir,
			array(
				'adminbar_myaccount_order' => 60,
			)
		);
	}

	/**
	 * Include bp-friends files.
	 *
	 * @since 1.5.0
	 *
	 * @see BP_Component::includes() for description of parameters.
	 *
	 * @param array $includes See {@link BP_Component::includes()}.
	 */
	public function includes( $includes = array() ) {
		$includes = array(
			'cssjs',
			'cache',
			'filters',
			'template',
			'functions',
			'widgets',
			'blocks',
		);

		// Conditional includes.
		if ( bp_is_active( 'activity' ) ) {
			$includes[] = 'activity';
		}
		if ( bp_is_active( 'notifications' ) ) {
			$includes[] = 'notifications';
		}

		parent::includes( $includes );
	}

	/**
	 * Late includes method.
	 *
	 * Only load up certain code when on specific pages.
	 *
	 * @since 3.0.0
	 */
	public function late_includes() {
		// Bail if PHPUnit is running.
		if ( defined( 'BP_TESTS_DIR' ) ) {
			return;
		}

		// Friends.
		if ( bp_is_user_friends() ) {
			// Authenticated actions.
			if ( is_user_logged_in() &&
				in_array( bp_current_action(), array( 'add-friend', 'remove-friend' ), true )
			) {
				require $this->path . 'bp-friends/actions/' . bp_current_action() . '.php';
			}

			// User nav.
			require $this->path . 'bp-friends/screens/my-friends.php';
			if ( is_user_logged_in() && bp_is_user_friend_requests() ) {
				require $this->path . 'bp-friends/screens/requests.php';
			}
		}
	}

	/**
	 * Set up bp-friends global settings.
	 *
	 * The BP_FRIENDS_SLUG constant is deprecated, and only used here for
	 * backwards compatibility.
	 *
	 * @since 1.5.0
	 *
	 * @global BuddyPress $bp The one true BuddyPress instance.
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

		// Define a slug, if necessary.
		if ( ! defined( 'BP_FRIENDS_SLUG' ) ) {
			define( 'BP_FRIENDS_SLUG', $this->id );
		}

		// Global tables for the friends component.
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
			'global_tables'         => $global_tables,
			'block_globals'         => array(
				'bp/friends' => array(
					'widget_classnames' => array( 'widget_bp_core_friends_widget', 'buddypress' ),
				)
			),
		);

		parent::setup_globals( $args );
	}

	/**
	 * Set up component navigation.
	 *
	 * @since 1.5.0
	 *
	 * @see BP_Component::setup_nav() for a description of arguments.
	 *
	 * @param array $main_nav Optional. See BP_Component::setup_nav() for
	 *                        description.
	 * @param array $sub_nav  Optional. See BP_Component::setup_nav() for
	 *                        description.
	 */
	public function setup_nav( $main_nav = array(), $sub_nav = array() ) {

		// Determine user to use.
		if ( bp_displayed_user_domain() ) {
			$user_domain = bp_displayed_user_domain();
		} elseif ( bp_loggedin_user_domain() ) {
			$user_domain = bp_loggedin_user_domain();
		} else {
			return;
		}

		$access       = bp_core_can_edit_settings();
		$slug         = bp_get_friends_slug();
		$friends_link = trailingslashit( $user_domain . $slug );

		// Add 'Friends' to the main navigation.
		$count = friends_get_total_friend_count();
		$class = ( 0 === $count ) ? 'no-count' : 'count';

		$main_nav_name = sprintf(
			/* translators: %s: Friend count for the current user */
			__( 'Friends %s', 'buddypress' ),
			sprintf(
				'<span class="%s">%s</span>',
				esc_attr( $class ),
				esc_html( $count )
			)
		);

		$main_nav = array(
			'name'                => $main_nav_name,
			'slug'                => $slug,
			'position'            => 60,
			'screen_function'     => 'friends_screen_my_friends',
			'default_subnav_slug' => 'my-friends',
			'item_css_id'         => $this->id,
		);

		// Add the subnav items to the friends nav item.
		$sub_nav[] = array(
			'name'            => _x( 'Friendships', 'Friends screen sub nav', 'buddypress' ),
			'slug'            => 'my-friends',
			'parent_url'      => $friends_link,
			'parent_slug'     => $slug,
			'screen_function' => 'friends_screen_my_friends',
			'position'        => 10,
			'item_css_id'     => 'friends-my-friends',
		);

		$sub_nav[] = array(
			'name'            => _x( 'Requests', 'Friends screen sub nav', 'buddypress' ),
			'slug'            => 'requests',
			'parent_url'      => $friends_link,
			'parent_slug'     => $slug,
			'screen_function' => 'friends_screen_requests',
			'position'        => 20,
			'user_has_access' => $access,
		);

		parent::setup_nav( $main_nav, $sub_nav );
	}

	/**
	 * Set up bp-friends integration with the WordPress admin bar.
	 *
	 * @since 1.5.0
	 *
	 * @see BP_Component::setup_admin_bar() for a description of arguments.
	 *
	 * @param array $wp_admin_nav See BP_Component::setup_admin_bar()
	 *                            for description.
	 */
	public function setup_admin_bar( $wp_admin_nav = array() ) {

		// Menus for logged in user.
		if ( is_user_logged_in() ) {

			// Setup the logged in user variables.
			$friends_link = trailingslashit( bp_loggedin_user_domain() . bp_get_friends_slug() );

			// Pending friend requests.
			$count = count( friends_get_friendship_request_user_ids( bp_loggedin_user_id() ) );
			if ( ! empty( $count ) ) {
				$title = sprintf(
					/* translators: %s: Pending friend request count for the current user */
					_x( 'Friends %s', 'My Account Friends menu', 'buddypress' ),
					'<span class="count">' . bp_core_number_format( $count ) . '</span>'
				);
				$pending = sprintf(
					/* translators: %s: Pending friend request count for the current user */
					_x( 'Pending Requests %s', 'My Account Friends menu sub nav', 'buddypress' ),
					'<span class="count">' . bp_core_number_format( $count ) . '</span>'
				);
			} else {
				$title   = _x( 'Friends', 'My Account Friends menu', 'buddypress' );
				$pending = _x( 'No Pending Requests', 'My Account Friends menu sub nav', 'buddypress' );
			}

			// Add the "My Account" sub menus.
			$wp_admin_nav[] = array(
				'parent' => buddypress()->my_account_menu_id,
				'id'     => 'my-account-' . $this->id,
				'title'  => $title,
				'href'   => $friends_link,
			);

			// My Friends.
			$wp_admin_nav[] = array(
				'parent'   => 'my-account-' . $this->id,
				'id'       => 'my-account-' . $this->id . '-friendships',
				'title'    => _x( 'Friendships', 'My Account Friends menu sub nav', 'buddypress' ),
				'href'     => trailingslashit( $friends_link . 'my-friends' ),
				'position' => 10,
			);

			// Requests.
			$wp_admin_nav[] = array(
				'parent'   => 'my-account-' . $this->id,
				'id'       => 'my-account-' . $this->id . '-requests',
				'title'    => $pending,
				'href'     => trailingslashit( $friends_link . 'requests' ),
				'position' => 20,
			);
		}

		parent::setup_admin_bar( $wp_admin_nav );
	}

	/**
	 * Set up the title for pages and <title>.
	 *
	 * @since 1.5.0
	 *
	 * @global BuddyPress $bp The one true BuddyPress instance.
	 */
	public function setup_title() {

		// Adjust title.
		if ( bp_is_friends_component() ) {
			$bp = buddypress();

			if ( bp_is_my_profile() ) {
				$bp->bp_options_title = __( 'Friendships', 'buddypress' );
			} else {
				$bp->bp_options_avatar = bp_core_fetch_avatar( array(
					'item_id' => bp_displayed_user_id(),
					'type'    => 'thumb',
					'alt'     => sprintf(
						/* translators: %s: member name */
						__( 'Profile picture of %s', 'buddypress' ),
						bp_get_displayed_user_fullname()
					),
				) );
				$bp->bp_options_title = bp_get_displayed_user_fullname();
			}
		}

		parent::setup_title();
	}

	/**
	 * Setup cache groups.
	 *
	 * @since 2.2.0
	 */
	public function setup_cache_groups() {

		// Global groups.
		wp_cache_add_global_groups( array(
			'bp_friends_requests',
			'bp_friends_friendships', // Individual friendship objects are cached here by ID.
			'bp_friends_friendships_for_user' // All friendship IDs for a single user.
		) );

		parent::setup_cache_groups();
	}

	/**
	 * Init the BP REST API.
	 *
	 * @since 6.0.0
	 *
	 * @param array $controllers Optional. See BP_Component::rest_api_init() for
	 *                           description.
	 */
	public function rest_api_init( $controllers = array() ) {
		parent::rest_api_init( array( 'BP_REST_Friends_Endpoint' ) );
	}

	/**
	 * Register the BP Friends Blocks.
	 *
	 * @since 9.0.0
	 *
	 * @param array $blocks Optional. See BP_Component::blocks_init() for
	 *                      description.
	 */
	public function blocks_init( $blocks = array() ) {
		parent::blocks_init(
			array(
				'bp/friends' => array(
					'name'               => 'bp/friends',
					'editor_script'      => 'bp-friends-block',
					'editor_script_url'  => plugins_url( 'js/blocks/friends.js', dirname( __FILE__ ) ),
					'editor_script_deps' => array(
						'wp-blocks',
						'wp-element',
						'wp-components',
						'wp-i18n',
						'wp-block-editor',
						'wp-server-side-render',
						'bp-block-data',
					),
					'style'              => 'bp-friends-block',
					'style_url'          => plugins_url( 'css/blocks/friends.css', dirname( __FILE__ ) ),
					'attributes'         => array(
						'maxFriends'    => array(
							'type'    => 'number',
							'default' => 5,
						),
						'friendDefault' => array(
							'type'    => 'string',
							'default' => 'active',
						),
						'linkTitle'     => array(
							'type'    => 'boolean',
							'default' => false,
						),
						'postId'        => array(
							'type'    => 'number',
							'default' => 0,
						),
					),
					'render_callback'    => 'bp_friends_render_friends_block',
				),
			)
		);
	}
}
