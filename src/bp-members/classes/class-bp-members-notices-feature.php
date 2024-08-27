<?php
/**
 * BuddyPress Member's notice feature Class.
 *
 * @package buddypress\bp-members\classes\class-bp-members-notices-feature
 * @since 15.0.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * This feature is required as BuddyPress is using it to inform Site Admins of important changes.
 *
 * If you really want to disable it, you can use:
 * `add_filter( 'bp_is_members_notices_active', '__return_false' );`
 *
 * @since 15.0.0
 */
class BP_Members_Notices_Feature extends BP_Component_Feature {

	/**
	 * Notices Feature initialization.
	 *
	 * @since 15.0.0
	 */
	public function __construct() {
		parent::init( 'notices', 'members' );
	}

	/**
	 * Include Notices feature files.
	 *
	 * @since 15.0.0
	 *
	 * @see `BP_Component_Feature::includes()` for description of parameters.
	 *
	 * @param array $includes See {@link BP_Component_Feature::includes()}.
	 */
	public function includes( $includes = array() ) {
		parent::includes( array( 'bp-members-notices' ) );
	}

	/**
	 * Include screen/action files later & when on specific pages.
	 *
	 * @since 15.0.0
	 */
	public function late_includes() {
		// Bail if PHPUnit is running.
		if ( defined( 'BP_TESTS_DIR' ) ) {
			return;
		}

		$is_notices_screen = bp_is_current_component( 'notices' );

		// When the notifications component is active, we move the notices front-end screen into this component.
		if ( bp_is_active( 'notifications' ) ) {
			$is_notices_screen = bp_is_current_component( 'notifications' ) && bp_is_current_action( 'notices' );
		}

		if ( bp_is_user() && $is_notices_screen ) {
			$action_variables = bp_action_variables();

			if ( is_array( $action_variables ) && 'dismiss' === $action_variables[0] ) {
				require_once buddypress()->members->path . 'bp-members/actions/dismiss-notice.php';
			}

			require_once buddypress()->members->path . 'bp-members/screens/notices.php';
		}
	}

	/**
	 * Register Notices feature navigation.
	 *
	 * @since 15.0.0
	 *
	 * @see `BP_Component::register_nav()` for a description of arguments.
	 *
	 * @param array $main_nav Optional. See `BP_Component::register_nav()` for
	 *                        description.
	 * @param array $sub_nav  Optional. See `BP_Component::register_nav()` for
	 *                        description.
	 */
	public function register_nav( $main_nav = array(), $sub_nav = array() ) {
		$notices_slug = $this->slug;

		$main_nav = array(
			'name'                     => _x( 'Notices', 'Member notices main navigation', 'buddypress' ),
			'slug'                     => $notices_slug,
			'position'                 => 25,
			'screen_function'          => 'bp_members_notices_load_screen',
			'default_subnav_slug'      => 'community',
			'item_css_id'              => $notices_slug,
			'user_has_access_callback' => 'bp_core_can_edit_settings',
			'generate'                 => ! bp_is_active( 'notifications' ),
		);

		$sub_nav[] = array(
			'name'                     => _x( 'Community', 'Member community notices sub nav', 'buddypress' ),
			'slug'                     => 'community',
			'parent_slug'              => $notices_slug,
			'screen_function'          => 'bp_members_notices_load_screen',
			'position'                 => 10,
			'user_has_access'          => false,
			'user_has_access_callback' => 'bp_core_can_edit_settings',
			'generate'                 => ! bp_is_active( 'notifications' ),
		);

		parent::register_nav( $main_nav, $sub_nav );
	}

	/**
	 * Returns Notices user's count.
	 *
	 * @since 15.0.0
	 *
	 * @param integer $user_id The user ID. Required.
	 * @return integer Notices user's count.
	 */
	public static function get_count_data( $user_id ) {
		if ( ! $user_id ) {
			return 0;
		}

		return bp_members_get_notices_count(
			array(
				'user_id'  => $user_id,
				'exclude'  => bp_members_get_dismissed_notices_for_user( $user_id ),
			)
		);
	}

	/**
	 * Set up component navigation.
	 *
	 * Wait for navigation generation before adding Notices count information.
	 *
	 * @since 15.0.0
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
			$count = self::get_count_data( bp_displayed_user_id() );

			// Set count information for Notices main nav.
			if ( $count ) {
				$class = ( 0 === $count ) ? 'no-count' : 'count';

				$this->main_nav['name'] = sprintf(
					/* translators: %s: Unread notification count for the current user */
					_x( 'Notices %s', 'Member notices main navigation with count information', 'buddypress' ),
					sprintf(
						'<span class="%s">%s</span>',
						esc_attr( $class ),
						esc_html( $count )
					)
				);
			}
		}

		parent::setup_nav( $main_nav, $sub_nav );
	}

	/**
	 * Return the WP Admin Nav to manage Community notices.
	 *
	 * @since 15.0.0
	 *
	 * @param string $selector_id The string to use to customize the nav item ID.
	 * @return array the WP Admin Nav to manage Community notices.
	 */
	public function get_manage_notice_admin_nav( $selector_id = '' ) {
		$wp_admin_nav = array();

		if ( bp_current_user_can( 'bp_moderate' ) ) {
			$wp_admin_nav[] = array(
				'parent'   => 'my-account-' . $selector_id,
				'id'       => 'my-account-' . $selector_id . '-manage-notices',
				'title'    => _x( 'Manage Notices', 'My Account Manage Notices sub nav', 'buddypress' ),
				'href'     => esc_url(
					add_query_arg(
						array(
							'page' => 'bp-notices',
						),
						bp_get_admin_url( 'users.php' )
					)
				),
				'position' => 30,
			);
		}

		return $wp_admin_nav;
	}

	/**
	 * Set up the Notices menu items in the WordPress Admin Bar.
	 *
	 * @since 15.0.0
	 *
	 * @see BP_Component::setup_nav() for a description of the `$wp_admin_nav`
	 *      parameter array.
	 *
	 * @param array $wp_admin_nav See BP_Component::setup_admin_bar() for a
	 *                            description.
	 */
	public function setup_admin_bar( $wp_admin_nav = array() ) {

		// Menus for logged in user.
		if ( is_user_logged_in() && ! bp_is_active( 'notifications' ) ) {
			$notices_slug = $this->slug;
			$title        = _x( 'Notices', 'My Account Notice nav', 'buddypress' );
			$count        = self::get_count_data( bp_loggedin_user_id() );

			if ( $count ) {
				$title = sprintf(
					/* translators: %s: Unread notices count for the current user */
					_x( 'Notices %s', 'My Account Notice nav with count information', 'buddypress' ),
					'<span class="count">' . bp_core_number_format( $count ) . '</span>'
				);
			}

			// Add the "My Account" sub menus.
			$wp_admin_nav[] = array(
				'parent' => buddypress()->my_account_menu_id,
				'id'     => 'my-account-' . $this->id,
				'title'  => $title,
				'href'   => bp_loggedin_user_url( bp_members_get_path_chunks( array( $notices_slug ) ) ),
			);

			// Unread.
			$wp_admin_nav[] = array(
				'parent'   => 'my-account-' . $this->id,
				'id'       => 'my-account-' . $this->id . '-community',
				'title'    => _x( 'Community', 'My Account Community notices sub nav', 'buddypress' ),
				'href'     => bp_loggedin_user_url( bp_members_get_path_chunks( array( $notices_slug, 'community' ) ) ),
				'position' => 10,
			);

			$wp_admin_nav = array_merge( $wp_admin_nav, $this->get_manage_notice_admin_nav( $this->id ) );
		}

		parent::setup_admin_bar( $wp_admin_nav );
	}

	/**
	 * Filters the Notifications WP Admin Nav to include one to manage Notices.
	 *
	 * @since 15.0.0
	 *
	 * @param array $wp_admin_nav Array of navigation items to add.
	 * @return array Array of navigation items to add.
	 */
	public function notifications_admin_nav( $wp_admin_nav = array() ) {
		return array_merge( $wp_admin_nav, $this->get_manage_notice_admin_nav( 'notifications' ) );
	}

	/**
	 * Set up action hooks for the Member Notices Feature.
	 *
	 * @since 15.0.0
	 */
	public function setup_actions() {
		// Perform default actions.
		parent::setup_actions();

		// Perform actions specific to this feature.
		add_filter( 'bp_notifications_admin_nav', array( $this, 'notifications_admin_nav' ) );

		/*
		 *
		 * @todo: this should be removed once BP REST API v2 has been merged into
		 * BuddyPress Core.
		 *
		 */
		add_action( 'bp_rest_api_init', array( $this, 'rest_api_init' ), 10 );
	}

	/**
	 * Register the BP REST API Controller.
	 *
	 * @since 15.0.0
	 *
	 * @param array $controllers Optional. See BP_Component::rest_api_init() for
	 *                           description.
	 */
	public function rest_api_init( $controllers = array() ) {
		$controllers = array( 'BP_Members_Notices_REST_Controller' );

		parent::rest_api_init( $controllers );
	}

	/**
	 * Register the Notices Blocks.
	 *
	 * @since 15.0.0
	 *
	 * @param array $blocks Optional. See BP_Component::blocks_init() for
	 *                      description.
	 */
	public function blocks_init( $blocks = array() ) {
		$blocks = array(
			'bp/sitewide-notices' => array(
				'metadata'        => trailingslashit( buddypress()->plugin_dir ) . 'bp-members/blocks/sitewide-notices',
				'render_callback' => 'bp_members_render_notices_block',
			)
		);

		parent::blocks_init( $blocks );
	}
}
