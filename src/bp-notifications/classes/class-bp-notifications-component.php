<?php
/**
 * BuddyPress Member Notifications Loader.
 *
 * Initializes the Notifications component.
 *
 * @package BuddyPress
 * @subpackage NotificationsLoader
 * @since 1.9.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Extends the component class to set up the Notifications component.
 *
 * @since 1.9.0
 */
#[AllowDynamicProperties]
class BP_Notifications_Component extends BP_Component {

	/**
	 * Start the notifications component creation process.
	 *
	 * @since 1.9.0
	 */
	public function __construct() {
		parent::start(
			'notifications',
			_x( 'Notifications', 'Page <title>', 'buddypress' ),
			buddypress()->plugin_dir,
			array(
				'adminbar_myaccount_order' => 30,
			)
		);
	}

	/**
	 * Include notifications component files.
	 *
	 * @since 1.9.0
	 *
	 * @see BP_Component::includes() for a description of arguments.
	 *
	 * @param array $includes See BP_Component::includes() for a description.
	 */
	public function includes( $includes = array() ) {
		$includes = array(
			'adminbar',
			'template',
			'filters',
			'functions',
			'cache',
		);

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

		// Bail if not on a notifications page or logged in.
		if ( ! bp_is_user_notifications() || ! is_user_logged_in() ) {
			return;
		}

		// Actions.
		if ( bp_is_post_request() ) {
			require_once $this->path . 'bp-notifications/actions/bulk-manage.php';
		} elseif ( bp_is_get_request() ) {
			require_once $this->path . 'bp-notifications/actions/delete.php';
		}

		// Screens.
		require_once $this->path . 'bp-notifications/screens/unread.php';
		if ( bp_is_current_action( 'read' ) ) {
			require_once $this->path . 'bp-notifications/screens/read.php';
		}
	}

	/**
	 * Set up component global data.
	 *
	 * @since 1.9.0
	 *
	 * @see BP_Component::setup_globals() for a description of arguments.
	 *
	 * @param array $args See BP_Component::setup_globals() for a description.
	 */
	public function setup_globals( $args = array() ) {
		$bp           = buddypress();
		$default_slug = $this->id;

		// @deprecated.
		if ( defined( 'BP_NOTIFICATIONS_SLUG' ) ) {
			_doing_it_wrong( 'BP_NOTIFICATIONS_SLUG', esc_html__( 'Slug constants are deprecated.', 'buddypress' ), 'BuddyPress 12.0.0' );
			$default_slug = BP_NOTIFICATIONS_SLUG;
		}

		// Global tables for the notifications component.
		$global_tables = array(
			'table_name'      => $bp->table_prefix . 'bp_notifications',
			'table_name_meta' => $bp->table_prefix . 'bp_notifications_meta',
		);

		// Metadata tables for notifications component.
		$meta_tables = array(
			'notification' => $bp->table_prefix . 'bp_notifications_meta',
		);

		// All globals for the notifications component.
		// Note that global_tables is included in this array.
		$args = array(
			'slug'          => $default_slug,
			'has_directory' => false,
			'search_string' => __( 'Search Notifications...', 'buddypress' ),
			'global_tables' => $global_tables,
			'meta_tables'   => $meta_tables,
		);

		parent::setup_globals( $args );
	}

	/**
	 * Register component navigation.
	 *
	 * @since 12.0.0
	 *
	 * @see `BP_Component::register_nav()` for a description of arguments.
	 *
	 * @param array $main_nav Optional. See `BP_Component::register_nav()` for
	 *                        description.
	 * @param array $sub_nav  Optional. See `BP_Component::register_nav()` for
	 *                        description.
	 */
	public function register_nav( $main_nav = array(), $sub_nav = array() ) {
		$slug   = bp_get_notifications_slug();

		// Add 'Notifications' to the main navigation.
		$main_nav = array(
			'name'                     => _x( 'Notifications', 'Profile screen nav', 'buddypress' ),
			'slug'                     => $slug,
			'position'                 => 30,
			'show_for_displayed_user'  => false,
			'screen_function'          => 'bp_notifications_screen_unread',
			'default_subnav_slug'      => 'unread',
			'item_css_id'              => $this->id,
			'user_has_access_callback' => 'bp_core_can_edit_settings',
		);

		// Add the subnav items to the notifications nav item.
		$sub_nav[] = array(
			'name'                     => _x( 'Unread', 'Notification screen nav', 'buddypress' ),
			'slug'                     => 'unread',
			'parent_slug'              => $slug,
			'screen_function'          => 'bp_notifications_screen_unread',
			'position'                 => 10,
			'item_css_id'              => 'notifications-my-notifications',
			'user_has_access'          => false,
			'user_has_access_callback' => 'bp_core_can_edit_settings',
		);

		$sub_nav[] = array(
			'name'                     => _x( 'Read', 'Notification screen nav', 'buddypress' ),
			'slug'                     => 'read',
			'parent_slug'              => $slug,
			'screen_function'          => 'bp_notifications_screen_read',
			'position'                 => 20,
			'user_has_access'          => false,
			'user_has_access_callback' => 'bp_core_can_edit_settings',
		);

		parent::register_nav( $main_nav, $sub_nav );
	}

	/**
	 * Set up component navigation.
	 *
	 * @since 1.9.0
	 * @since 12.0.0 Used to customize the main navigation name.
	 *
	 * @see `BP_Component::setup_nav()` for a description of arguments.
	 *
	 * @param array $main_nav Optional. See `BP_Component::setup_nav()` for
	 *                        description.
	 * @param array $sub_nav  Optional. See `BP_Component::setup_nav()` for
	 *                        description.
	 */
	public function setup_nav( $main_nav = array(), $sub_nav = array() ) {
		// Only grab count if we're on a user page and current user has access.
		if ( isset( $this->main_nav['name'] ) && bp_is_user() && bp_user_has_access() ) {
			$count                  = bp_notifications_get_unread_notification_count( bp_displayed_user_id() );
			$class                  = ( 0 === $count ) ? 'no-count' : 'count';
			$this->main_nav['name'] = sprintf(
				/* translators: %s: Unread notification count for the current user */
				_x( 'Notifications %s', 'Profile screen nav', 'buddypress' ),
				sprintf(
					'<span class="%s">%s</span>',
					esc_attr( $class ),
					esc_html( $count )
				)
			);
		}

		parent::setup_nav( $main_nav, $sub_nav );
	}

	/**
	 * Set up the component entries in the WordPress Admin Bar.
	 *
	 * @since 1.9.0
	 *
	 * @see BP_Component::setup_nav() for a description of the $wp_admin_nav
	 *      parameter array.
	 *
	 * @param array $wp_admin_nav See BP_Component::setup_admin_bar() for a
	 *                            description.
	 */
	public function setup_admin_bar( $wp_admin_nav = array() ) {

		// Menus for logged in user.
		if ( is_user_logged_in() ) {
			$notifications_slug = bp_get_notifications_slug();

			// Pending notification requests.
			$count = bp_notifications_get_unread_notification_count( bp_loggedin_user_id() );
			if ( ! empty( $count ) ) {
				$title = sprintf(
					/* translators: %s: Unread notification count for the current user */
					_x( 'Notifications %s', 'My Account Notification pending', 'buddypress' ),
					'<span class="count">' . bp_core_number_format( $count ) . '</span>'
				);
				$unread = sprintf(
					/* translators: %s: Unread notification count for the current user */
					_x( 'Unread %s', 'My Account Notification pending', 'buddypress' ),
					'<span class="count">' . bp_core_number_format( $count ) . '</span>'
				);
			} else {
				$title  = _x( 'Notifications', 'My Account Notification',         'buddypress' );
				$unread = _x( 'Unread',        'My Account Notification sub nav', 'buddypress' );
			}

			// Add the "My Account" sub menus.
			$wp_admin_nav[] = array(
				'parent' => buddypress()->my_account_menu_id,
				'id'     => 'my-account-' . $this->id,
				'title'  => $title,
				'href'   => bp_loggedin_user_url( bp_members_get_path_chunks( array( $notifications_slug ) ) ),
			);

			// Unread.
			$wp_admin_nav[] = array(
				'parent'   => 'my-account-' . $this->id,
				'id'       => 'my-account-' . $this->id . '-unread',
				'title'    => $unread,
				'href'     => bp_loggedin_user_url( bp_members_get_path_chunks( array( $notifications_slug, 'unread' ) ) ),
				'position' => 10,
			);

			// Read.
			$wp_admin_nav[] = array(
				'parent'   => 'my-account-' . $this->id,
				'id'       => 'my-account-' . $this->id . '-read',
				'title'    => _x( 'Read', 'My Account Notification sub nav', 'buddypress' ),
				'href'     => bp_loggedin_user_url( bp_members_get_path_chunks( array( $notifications_slug, 'read' ) ) ),
				'position' => 20,
			);
		}

		parent::setup_admin_bar( $wp_admin_nav );
	}

	/**
	 * Set up the title for pages and <title>.
	 *
	 * @since 1.9.0
	 */
	public function setup_title() {

		// Adjust title.
		if ( bp_is_notifications_component() ) {
			$bp = buddypress();

			if ( bp_is_my_profile() ) {
				$bp->bp_options_title = __( 'Notifications', 'buddypress' );
			} else {
				$bp->bp_options_title  = bp_get_displayed_user_fullname();
				$bp->bp_options_avatar = bp_core_fetch_avatar( array(
					'item_id' => bp_displayed_user_id(),
					'type'    => 'thumb',
					/* translators: %s: member name */
					'alt'     => sprintf( __( 'Profile picture of %s', 'buddypress' ), bp_get_displayed_user_fullname() )
				) );
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
			'bp_notifications',
			'notification_meta',
			'bp_notifications_unread_count',
			'bp_notifications_grouped_notifications',
		) );

		parent::setup_cache_groups();
	}

	/**
	 * Init the BP REST API.
	 *
	 * @since 5.0.0
	 *
	 * @param array $controllers Optional. See BP_Component::rest_api_init() for
	 *                           description.
	 */
	public function rest_api_init( $controllers = array() ) {
		parent::rest_api_init( array( 'BP_REST_Notifications_Endpoint' ) );
	}
}
