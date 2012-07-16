<?php

/**
 * BuddyPress Settings Loader
 *
 * @package BuddyPress
 * @subpackage SettingsLoader
 */

// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

class BP_Settings_Component extends BP_Component {

	/**
	 * Start the settings component creation process
	 *
	 * @since BuddyPress (1.5)
	 */
	function __construct() {
		parent::start(
			'settings',
			__( 'Settings', 'buddypress' ),
			BP_PLUGIN_DIR
		);
	}

	/**
	 * Include files
	 *
	 * @global BuddyPress $bp The one true BuddyPress instance
	 */
	function includes() {
		// Files to include
		$includes = array(
			'actions',
			'screens',
			'template',
			'functions',
		);

		parent::includes( $includes );
	}

	/**
	 * Setup globals
	 *
	 * The BP_SETTINGS_SLUG constant is deprecated, and only used here for
	 * backwards compatibility.
	 *
	 * @since BuddyPress (1.5)
	 */
	function setup_globals() {

		// Define a slug, if necessary
		if ( !defined( 'BP_SETTINGS_SLUG' ) )
			define( 'BP_SETTINGS_SLUG', $this->id );

		// All globals for settings component.
		$globals = array(
			'slug'          => BP_SETTINGS_SLUG,
			'has_directory' => false,
		);

		parent::setup_globals( $globals );
	}

	/**
	 * Setup BuddyBar navigation
	 */
	function setup_nav() {

		// Define local variable
		$sub_nav = array();

		// Add the settings navigation item
		$main_nav = array(
			'name'                    => __( 'Settings', 'buddypress' ),
			'slug'                    => $this->slug,
			'position'                => 100,
			'show_for_displayed_user' => bp_core_can_edit_settings(),
			'screen_function'         => 'bp_settings_screen_general',
			'default_subnav_slug'     => 'general'
		);

		// Determine user to use
		if ( bp_displayed_user_domain() )
			$user_domain = bp_displayed_user_domain();
		elseif ( bp_loggedin_user_domain() )
			$user_domain = bp_loggedin_user_domain();
		else
			return;

		$settings_link = trailingslashit( $user_domain . $this->slug );

		// Add General Settings nav item
		$sub_nav[] = array(
			'name'            => __( 'General', 'buddypress' ),
			'slug'            => 'general',
			'parent_url'      => $settings_link,
			'parent_slug'     => $this->slug,
			'screen_function' => 'bp_settings_screen_general',
			'position'        => 10,
			'user_has_access' => bp_core_can_edit_settings()
		);

		// Add Notifications nav item
		$sub_nav[] = array(
			'name'            => __( 'Notifications', 'buddypress' ),
			'slug'            => 'notifications',
			'parent_url'      => $settings_link,
			'parent_slug'     => $this->slug,
			'screen_function' => 'bp_settings_screen_notification',
			'position'        => 20,
			'user_has_access' => bp_core_can_edit_settings()
		);

		// Add Spam Account nav item
		if ( bp_current_user_can( 'bp_moderate' ) ) {
			$sub_nav[] = array(
				'name'            => __( 'Capabilities', 'buddypress' ),
				'slug'            => 'capabilities',
				'parent_url'      => $settings_link,
				'parent_slug'     => $this->slug,
				'screen_function' => 'bp_settings_screen_capabilities',
				'position'        => 80,
				'user_has_access' => ! bp_is_my_profile()
			);
		}

		// Add Delete Account nav item
		if ( ! bp_disable_account_deletion() || bp_current_user_can( 'delete_users' ) ) {
			$sub_nav[] = array(
				'name'            => __( 'Delete Account', 'buddypress' ),
				'slug'            => 'delete-account',
				'parent_url'      => $settings_link,
				'parent_slug'     => $this->slug,
				'screen_function' => 'bp_settings_screen_delete_account',
				'position'        => 90,
				'user_has_access' => bp_is_my_profile() || !is_super_admin( bp_displayed_user_id() )
			);
		}

		parent::setup_nav( $main_nav, $sub_nav );
	}

	/**
	 * Set up the Toolbar
	 *
	 * @global BuddyPress $bp The one true BuddyPress instance
	 */
	function setup_admin_bar() {
		global $bp;

		// Prevent debug notices
		$wp_admin_nav = array();

		// Menus for logged in user
		if ( is_user_logged_in() ) {

			// Setup the logged in user variables
			$user_domain   = bp_loggedin_user_domain();
			$settings_link = trailingslashit( $user_domain . $this->slug );

			// Add main Settings menu
			$wp_admin_nav[] = array(
				'parent' => $bp->my_account_menu_id,
				'id'     => 'my-account-' . $this->id,
				'title'  => __( 'Settings', 'buddypress' ),
				'href'   => trailingslashit( $settings_link )
			);

			// General Account
			$wp_admin_nav[] = array(
				'parent' => 'my-account-' . $this->id,
				'id'     => 'my-account-' . $this->id . '-general',
				'title'  => __( 'General', 'buddypress' ),
				'href'   => trailingslashit( $settings_link . 'general' )
			);

			// Notifications
			$wp_admin_nav[] = array(
				'parent' => 'my-account-' . $this->id,
				'id'     => 'my-account-' . $this->id . '-notifications',
				'title'  => __( 'Notifications', 'buddypress' ),
				'href'   => trailingslashit( $settings_link . 'notifications' )
			);

			// Delete Account
			if ( !bp_current_user_can( 'bp_moderate' ) && empty( $bp->site_options['bp-disable-account-deletion'] ) ) {
				$wp_admin_nav[] = array(
					'parent' => 'my-account-' . $this->id,
					'id'     => 'my-account-' . $this->id . '-delete-account',
					'title'  => __( 'Delete Account', 'buddypress' ),
					'href'   => trailingslashit( $settings_link . 'delete-account' )
				);
			}
		}

		parent::setup_admin_bar( $wp_admin_nav );
	}
}

function bp_setup_settings() {
	global $bp;
	$bp->settings = new BP_Settings_Component();
}
add_action( 'bp_setup_components', 'bp_setup_settings', 6 );

?>