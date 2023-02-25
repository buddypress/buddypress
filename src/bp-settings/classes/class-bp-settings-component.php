<?php
/**
 * BuddyPress Settings Loader.
 *
 * @package BuddyPress
 * @subpackage SettingsLoader
 * @since 1.5.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Creates our Settings component.
 *
 * @since 1.5.0
 */
#[AllowDynamicProperties]
class BP_Settings_Component extends BP_Component {

	/**
	 * Start the settings component creation process.
	 *
	 * @since 1.5.0
	 */
	public function __construct() {
		parent::start(
			'settings',
			__( 'Settings', 'buddypress' ),
			buddypress()->plugin_dir,
			array(
				'adminbar_myaccount_order' => 100,
			)
		);
	}

	/**
	 * Include files.
	 *
	 * @since 1.5.0
	 *
	 * @param array $includes Array of values to include. Not used.
	 */
	public function includes( $includes = array() ) {
		parent::includes( array(
			'template',
			'filters',
			'functions',
		) );
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

		// Bail if not on Settings component.
		if ( ! bp_is_settings_component() ) {
			return;
		}

		$actions = array( 'notifications', 'capabilities', 'data', 'delete-account' );

		// Authenticated actions.
		if ( is_user_logged_in() ) {
			if ( ! bp_current_action() || bp_is_current_action( 'general' ) ) {
				require $this->path . 'bp-settings/actions/general.php';

			// Specific to post requests.
			} elseif ( bp_is_post_request() && in_array( bp_current_action(), $actions, true ) ) {
				require $this->path . 'bp-settings/actions/' . bp_current_action() . '.php';
			}
		}

		// Screens - User profile integration.
		if ( bp_is_user() ) {
			require $this->path . 'bp-settings/screens/general.php';

			// Sub-nav items.
			if ( in_array( bp_current_action(), $actions, true ) ) {
				require $this->path . 'bp-settings/screens/' . bp_current_action() . '.php';
			}
		}
	}

	/**
	 * Setup globals.
	 *
	 * The BP_SETTINGS_SLUG constant is deprecated.
	 *
	 * @since 1.5.0
	 *
	 * @see BP_Component::setup_globals() for a description of arguments.
	 *
	 * @param array $args See BP_Component::setup_globals() for a description.
	 */
	public function setup_globals( $args = array() ) {
		$default_slug = $this->id;

		// @deprecated.
		if ( defined( 'BP_SETTINGS_SLUG' ) ) {
			_doing_it_wrong( 'BP_SETTINGS_SLUG', esc_html__( 'Slug constants are deprecated.', 'buddypress' ), 'BuddyPress 12.0.0' );
			$default_slug = BP_SETTINGS_SLUG;
		}

		// All globals for settings component.
		parent::setup_globals( array(
			'slug'          => $default_slug,
			'has_directory' => false,
		) );
	}

	/**
	 * Set up navigation.
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

		$access        = bp_core_can_edit_settings();
		$slug          = bp_get_settings_slug();
		$settings_link = trailingslashit( $user_domain . $slug );

		// Add the settings navigation item.
		$main_nav = array(
			'name'                    => __( 'Settings', 'buddypress' ),
			'slug'                    => $slug,
			'position'                => 100,
			'show_for_displayed_user' => $access,
			'screen_function'         => 'bp_settings_screen_general',
			'default_subnav_slug'     => 'general',
		);

		// Add General Settings nav item.
		$sub_nav[] = array(
			'name'            => __( 'General', 'buddypress' ),
			'slug'            => 'general',
			'parent_url'      => $settings_link,
			'parent_slug'     => $slug,
			'screen_function' => 'bp_settings_screen_general',
			'position'        => 10,
			'user_has_access' => $access,
		);

		// Add Email nav item. Formerly called 'Notifications', we
		// retain the old slug and function names for backward compat.
		$sub_nav[] = array(
			'name'            => __( 'Email', 'buddypress' ),
			'slug'            => 'notifications',
			'parent_url'      => $settings_link,
			'parent_slug'     => $slug,
			'screen_function' => 'bp_settings_screen_notification',
			'position'        => 20,
			'user_has_access' => $access,
		);

		// Add Spam Account nav item.
		if ( bp_current_user_can( 'bp_moderate' ) ) {
			$sub_nav[] = array(
				'name'            => __( 'Capabilities', 'buddypress' ),
				'slug'            => 'capabilities',
				'parent_url'      => $settings_link,
				'parent_slug'     => $slug,
				'screen_function' => 'bp_settings_screen_capabilities',
				'position'        => 80,
				'user_has_access' => ! bp_is_my_profile(),
			);
		}

		/**
		 * Filter whether the site should show the "Settings > Data" page.
		 *
		 * @since 4.0.0
		 *
		 * @param bool $show Defaults to true.
		 */
		$show_data_page = apply_filters( 'bp_settings_show_user_data_page', true );

		// Export Data.
		if ( true === $show_data_page ) {
			$sub_nav[] = array(
				'name'            => __( 'Export Data', 'buddypress' ),
				'slug'            => 'data',
				'parent_url'      => $settings_link,
				'parent_slug'     => $slug,
				'screen_function' => 'bp_settings_screen_data',
				'position'        => 89,
				'user_has_access' => $access,
			);
		}

		// Add Delete Account nav item.
		if ( ( ! bp_disable_account_deletion() && bp_is_my_profile() ) || bp_current_user_can( 'delete_users' ) ) {
			$sub_nav[] = array(
				'name'            => __( 'Delete Account', 'buddypress' ),
				'slug'            => 'delete-account',
				'parent_url'      => $settings_link,
				'parent_slug'     => $slug,
				'screen_function' => 'bp_settings_screen_delete_account',
				'position'        => 90,
				'user_has_access' => ! user_can( bp_displayed_user_id(), 'delete_users' ),
			);
		}

		parent::setup_nav( $main_nav, $sub_nav );
	}

	/**
	 * Set up the component entries in the WordPress Admin Bar.
	 *
	 * @since 1.5.0
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

			// Setup the logged in user variables.
			$settings_link = trailingslashit( bp_loggedin_user_domain() . bp_get_settings_slug() );

			// Add main Settings menu.
			$wp_admin_nav[] = array(
				'parent' => buddypress()->my_account_menu_id,
				'id'     => 'my-account-' . $this->id,
				'title'  => __( 'Settings', 'buddypress' ),
				'href'   => $settings_link,
			);

			// General Account.
			$wp_admin_nav[] = array(
				'parent'   => 'my-account-' . $this->id,
				'id'       => 'my-account-' . $this->id . '-general',
				'title'    => __( 'General', 'buddypress' ),
				'href'     => trailingslashit( $settings_link . 'general' ),
				'position' => 10,
			);

			// Notifications - only add the tab when there is something to display there.
			if ( has_action( 'bp_notification_settings' ) ) {
				$wp_admin_nav[] = array(
					'parent'   => 'my-account-' . $this->id,
					'id'       => 'my-account-' . $this->id . '-notifications',
					'title'    => __( 'Email', 'buddypress' ),
					'href'     => trailingslashit( $settings_link . 'notifications' ),
					'position' => 20,
				);
			}

			/** This filter is documented in bp-settings/classes/class-bp-settings-component.php */
			$show_data_page = apply_filters( 'bp_settings_show_user_data_page', true );

			// Export Data.
			if ( true === $show_data_page ) {
				$wp_admin_nav[] = array(
					'parent'   => 'my-account-' . $this->id,
					'id'       => 'my-account-' . $this->id . '-data',
					'title'    => __( 'Export Data', 'buddypress' ),
					'href'     => trailingslashit( $settings_link . 'data' ),
					'position' => 89,
				);
			}

			// Delete Account
			if ( !bp_current_user_can( 'bp_moderate' ) && ! bp_core_get_root_option( 'bp-disable-account-deletion' ) ) {
				$wp_admin_nav[] = array(
					'parent'   => 'my-account-' . $this->id,
					'id'       => 'my-account-' . $this->id . '-delete-account',
					'title'    => __( 'Delete Account', 'buddypress' ),
					'href'     => trailingslashit( $settings_link . 'delete-account' ),
					'position' => 90,
				);
			}
		}

		parent::setup_admin_bar( $wp_admin_nav );
	}

	/**
	 * Register the BP Settings Blocks.
	 *
	 * @since 9.0.0
	 *
	 * @param array $blocks Optional. See BP_Component::blocks_init() for the description.
	 */
	public function blocks_init( $blocks = array() ) {
		parent::blocks_init( array() );
	}
}
