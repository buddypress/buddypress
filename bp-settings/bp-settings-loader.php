<?php

class BP_Settings_Component extends BP_Component {

	/**
	 * Start the settings component creation process
	 *
	 * @since BuddyPress {unknown}
	 */
	function BP_Settings_Component() {
		parent::start( 'settings', __( 'Settings', 'buddypress' ) );
	}

	/**
	 * Setup globals
	 *
	 * The BP_SETTINGS_SLUG constant is deprecated, and only used here for
	 * backwards compatibility.
	 *
	 * @since BuddyPress {unknown}
	 * @global obj $bp
	 */
	function _setup_globals() {
		global $bp;

		// Define a slug, if necessary
		if ( !defined( 'BP_SETTINGS_SLUG' ) )
			define( 'BP_SETTINGS_SLUG', $this->id );

		// Do some slug checks
		$this->slug      = BP_SETTINGS_SLUG;
		$this->root_slug = isset( $bp->pages->settings->slug ) ? $bp->pages->settings->slug : $this->slug;
	}

	/**
	 * Include files
	 *
	 * @global obj $bp
	 */
	function _includes() {
		require_once( BP_PLUGIN_DIR . '/bp-settings/bp-settings-actions.php'   );
		require_once( BP_PLUGIN_DIR . '/bp-settings/bp-settings-screens.php'   );
		require_once( BP_PLUGIN_DIR . '/bp-settings/bp-settings-template.php'  );
		require_once( BP_PLUGIN_DIR . '/bp-settings/bp-settings-functions.php' );
	}

	/**
	 * Setup BuddyBar navigation
	 *
	 * @global obj $bp
	 */
	function _setup_nav() {
		global $bp;

		// Add the settings navigation item
		bp_core_new_nav_item( array(
			'name'                    => __( 'Settings', 'buddypress' ),
			'slug'                    => $bp->settings->slug,
			'position'                => 100,
			'show_for_displayed_user' => bp_users_can_edit_settings(),
			'screen_function'         => 'bp_settings_screen_general_settings',
			'default_subnav_slug'     => 'general'
		) );

		$settings_link = trailingslashit( $bp->displayed_user->domain . $bp->settings->slug );

		// Add General Settings nav item
		bp_core_new_subnav_item( array(
			'name'            => __( 'General', 'buddypress' ),
			'slug'            => 'general',
			'parent_url'      => $settings_link,
			'parent_slug'     => $bp->settings->slug,
			'screen_function' => 'bp_settings_screen_general_settings',
			'position'        => 10,
			'user_has_access' => bp_users_can_edit_settings()
		) );

		// Add Notifications nav item
		bp_core_new_subnav_item( array(
			'name'            => __( 'Notifications', 'buddypress' ),
			'slug'            => 'notifications',
			'parent_url'      => $settings_link,
			'parent_slug'     => $bp->settings->slug,
			'screen_function' => 'bp_settings_screen_notification_settings',
			'position'        => 20,
			'user_has_access' => bp_users_can_edit_settings()
		) );

		// Add Delete Account nav item
		if ( !is_super_admin() && empty( $bp->site_options['bp-disable-account-deletion'] ) ) {
			bp_core_new_subnav_item( array(
				'name'            => __( 'Delete Account', 'buddypress' ),
				'slug'            => 'delete-account',
				'parent_url'      => $settings_link,
				'parent_slug'     => $bp->settings->slug,
				'screen_function' => 'bp_settings_screen_delete_account',
				'position'        => 90,
				'user_has_access' => bp_is_my_profile()
			) );
		}
	}
}
$bp->settings = new BP_Settings_Component();

function bp_users_add_settings_nav() {
	global $bp;

	// Register this in the active components array
	$bp->active_components[$bp->settings->slug] = $bp->settings->id;

	do_action( 'bp_core_settings_setup_nav' );
}

?>
