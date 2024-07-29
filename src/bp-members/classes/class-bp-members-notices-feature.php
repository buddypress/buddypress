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

		if ( bp_is_user() && bp_is_current_component( 'notices' ) ) {
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
			'name'                     => _x( 'Notices', 'Member profile main navigation', 'buddypress' ),
			'slug'                     => $notices_slug,
			'position'                 => 25,
			'screen_function'          => 'bp_members_notices_load_screen',
			'default_subnav_slug'      => 'unread',
			'item_css_id'              => $notices_slug,
			'user_has_access_callback' => 'bp_core_can_edit_settings',
			'generate'                 => ! bp_is_active( 'notifications' ),
		);

		$sub_nav[] = array(
			'name'                     => _x( 'Unread', 'Member profile view', 'buddypress' ),
			'slug'                     => 'unread',
			'parent_slug'              => $notices_slug,
			'screen_function'          => 'bp_members_notices_load_screen',
			'position'                 => 10,
			'user_has_access'          => false,
			'user_has_access_callback' => 'bp_core_can_edit_settings',
			'generate'                 => ! bp_is_active( 'notifications' ),
		);

		$sub_nav[] = array(
			'name'                     => _x( 'Read', 'Member profile view', 'buddypress' ),
			'slug'                     => 'read',
			'parent_slug'              => $notices_slug,
			'screen_function'          => 'bp_members_notices_load_screen',
			'position'                 => 20,
			'user_has_access'          => false,
			'user_has_access_callback' => 'bp_core_can_edit_settings',
			'generate'                 => ! bp_is_active( 'notifications' ),
		);

		parent::register_nav( $main_nav, $sub_nav );
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
		if ( is_user_logged_in() ) {
			$notices_slug = $this->slug;

			// Add the "My Account" sub menus.
			$wp_admin_nav[] = array(
				'parent' => buddypress()->my_account_menu_id,
				'id'     => 'my-account-' . $this->id,
				'title'  => _x( 'Notices', 'My Account Notice nav', 'buddypress' ),
				'href'   => bp_loggedin_user_url( bp_members_get_path_chunks( array( $notices_slug ) ) ),
			);

			// Unread.
			$wp_admin_nav[] = array(
				'parent'   => 'my-account-' . $this->id,
				'id'       => 'my-account-' . $this->id . '-unread',
				'title'    => _x( 'Unread', 'My Account Notice sub nav', 'buddypress' ),
				'href'     => bp_loggedin_user_url( bp_members_get_path_chunks( array( $notices_slug, 'unread' ) ) ),
				'position' => 10,
			);

			// Read.
			$wp_admin_nav[] = array(
				'parent'   => 'my-account-' . $this->id,
				'id'       => 'my-account-' . $this->id . '-read',
				'title'    => _x( 'Read', 'My Account Notice sub nav', 'buddypress' ),
				'href'     => bp_loggedin_user_url( bp_members_get_path_chunks( array( $notices_slug, 'read' ) ) ),
				'position' => 20,
			);

			if ( bp_current_user_can( 'bp_moderate' ) ) {
				$wp_admin_nav[] = array(
					'parent'   => 'my-account-' . $this->id,
					'id'       => 'my-account-' . $this->id . '-manage-notices',
					'title'    => _x( 'Manage Notices', 'My Account Notice sub nav', 'buddypress' ),
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
		}

		parent::setup_admin_bar( $wp_admin_nav );
	}
}
