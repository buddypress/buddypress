<?php

/**
 * BuddyPress Updater
 *
 * @package BuddyPress
 * @subpackage Updater
 */

// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

/**
 * If there is no raw DB version, this is the first installation
 *
 * @since BuddyPress (1.7)
 *
 * @uses get_option()
 * @uses bp_get_db_version() To get BuddyPress's database version
 * @return bool True if update, False if not
 */
function bp_is_install() {
	return ! bp_get_db_version_raw();
}

/**
 * Compare the BuddyPress version to the DB version to determine if updating
 *
 * @since BuddyPress (1.6)
 *
 * @uses get_option()
 * @uses bp_get_db_version() To get BuddyPress's database version
 * @return bool True if update, False if not
 */
function bp_is_update() {

	// Current DB version of this site (per site in a multisite network)
	$current_db   = bp_get_option( '_bp_db_version' );
	$current_live = bp_get_db_version();

	// Compare versions (cast as int and bool to be safe)
	$is_update = (bool) ( (int) $current_db < (int) $current_live );

	// Return the product of version comparison
	return $is_update;
}

/**
 * Determine if BuddyPress is being activated
 *
 * @since BuddyPress (1.6)
 *
 * @uses buddypress()
 * @return bool True if activating BuddyPress, false if not
 */
function bp_is_activation( $basename = '' ) {
	$bp     = buddypress();
	$action = false;

	if ( ! empty( $_REQUEST['action'] ) && ( '-1' != $_REQUEST['action'] ) ) {
		$action = $_REQUEST['action'];
	} elseif ( ! empty( $_REQUEST['action2'] ) && ( '-1' != $_REQUEST['action2'] ) ) {
		$action = $_REQUEST['action2'];
	}

	// Bail if not activating
	if ( empty( $action ) || !in_array( $action, array( 'activate', 'activate-selected' ) ) ) {
		return false;
	}

	// The plugin(s) being activated
	if ( $action == 'activate' ) {
		$plugins = isset( $_GET['plugin'] ) ? array( $_GET['plugin'] ) : array();
	} else {
		$plugins = isset( $_POST['checked'] ) ? (array) $_POST['checked'] : array();
	}

	// Set basename if empty
	if ( empty( $basename ) && !empty( $bp->basename ) ) {
		$basename = $bp->basename;
	}

	// Bail if no basename
	if ( empty( $basename ) ) {
		return false;
	}

	// Is BuddyPress being activated?
	return in_array( $basename, $plugins );
}

/**
 * Determine if BuddyPress is being deactivated
 *
 * @since BuddyPress (1.6)
 *
 * @uses buddypress()
 * @return bool True if deactivating BuddyPress, false if not
 */
function bp_is_deactivation( $basename = '' ) {
	$bp     = buddypress();
	$action = false;

	if ( ! empty( $_REQUEST['action'] ) && ( '-1' != $_REQUEST['action'] ) ) {
		$action = $_REQUEST['action'];
	} elseif ( ! empty( $_REQUEST['action2'] ) && ( '-1' != $_REQUEST['action2'] ) ) {
		$action = $_REQUEST['action2'];
	}

	// Bail if not deactivating
	if ( empty( $action ) || !in_array( $action, array( 'deactivate', 'deactivate-selected' ) ) ) {
		return false;
	}

	// The plugin(s) being deactivated
	if ( 'deactivate' == $action ) {
		$plugins = isset( $_GET['plugin'] ) ? array( $_GET['plugin'] ) : array();
	} else {
		$plugins = isset( $_POST['checked'] ) ? (array) $_POST['checked'] : array();
	}

	// Set basename if empty
	if ( empty( $basename ) && !empty( $bp->basename ) ) {
		$basename = $bp->basename;
	}

	// Bail if no basename
	if ( empty( $basename ) ) {
		return false;
	}

	// Is bbPress being deactivated?
	return in_array( $basename, $plugins );
}

/**
 * Update the DB to the latest version
 *
 * @since BuddyPress (1.6)
 *
 * @uses update_option()
 * @uses bp_get_db_version() To get BuddyPress's database version
 * @uses bp_update_option() To update BuddyPress's database version
 */
function bp_version_bump() {
	bp_update_option( '_bp_db_version', bp_get_db_version() );
}

/**
 * Setup the BuddyPress updater
 *
 * @since BuddyPress (1.6)
 *
 * @uses BBP_Updater
 */
function bp_setup_updater() {

	// Are we running an outdated version of BuddyPress?
	if ( ! bp_is_update() )
		return;

	bp_version_updater();
}

/**
 * bbPress's version updater looks at what the current database version is, and
 * runs whatever other code is needed.
 *
 * This is most-often used when the data schema changes, but should also be used
 * to correct issues with bbPress meta-data silently on software update.
 *
 * @since bbPress (r4104)
 */
function bp_version_updater() {

	// Get the raw database version
	$raw_db_version = (int) bp_get_db_version_raw();

	$default_components = apply_filters( 'bp_new_install_default_components', array( 'activity' => 1, 'xprofile' => 1, ) );
	require_once( BP_PLUGIN_DIR . '/bp-core/admin/bp-core-schema.php' );

	// Install BP schema and activate only Activity and XProfile
	if ( bp_is_install() ) {

		// Apply schema and set Activity and XProfile components as active
		bp_core_install( $default_components );
		bp_update_option( 'bp-active-components', $default_components );
		bp_updater_add_page_mappings( $default_components );

	// Upgrades
	} else {

		// Run the schema install to update tables
		bp_core_install();

		// 1.5
		if ( $raw_db_version < 1801 ) {
			bp_update_to_1_5();
			bp_updater_add_page_mappings( $default_components );
		}

		// 1.6
		if ( $raw_db_version < 6067 ) {
			bp_update_to_1_6();
		}
	}

	/** All done! *************************************************************/

	// Bump the version
	bp_version_bump();
}

/**
 * Database update methods based on version numbers
 *
 * @since BuddyPress (1.7)
 */
function bp_update_to_1_5() {

	// Delete old database version options
	delete_site_option( 'bp-activity-db-version' );
	delete_site_option( 'bp-blogs-db-version'    );
	delete_site_option( 'bp-friends-db-version'  );
	delete_site_option( 'bp-groups-db-version'   );
	delete_site_option( 'bp-messages-db-version' );
	delete_site_option( 'bp-xprofile-db-version' );
}

/**
 * Database update methods based on version numbers
 *
 * @since BuddyPress (1.7)
 */
function bp_update_to_1_6() {

	// Delete possible site options
	delete_site_option( 'bp-db-version'       );
	delete_site_option( '_bp_db_version'      );
	delete_site_option( 'bp-core-db-version'  );
	delete_site_option( '_bp-core-db-version' );

	// Delete possible blog options
	delete_blog_option( bp_get_root_blog_id(), 'bp-db-version'       );
	delete_blog_option( bp_get_root_blog_id(), 'bp-core-db-version'  );
	delete_site_option( bp_get_root_blog_id(), '_bp-core-db-version' );
	delete_site_option( bp_get_root_blog_id(), '_bp_db_version'      );
}

/**
 * Add the pages for the component mapping. These are most often used by components with directories (e.g. groups, members).
 *
 * @param array $default_components Optional components to create pages for
 * @since BuddyPress (1.7)
 */
function bp_updater_add_page_mappings( $default_components ) {

	// Make sure that the pages are created on the root blog no matter which Dashboard the setup is being run on
	if ( ! bp_is_root_blog() )
		switch_to_blog( bp_get_root_blog_id() );

	// Delete any existing pages
	$pages = bp_core_get_directory_page_ids();
	foreach ( (array) $pages as $page_id )
		wp_delete_post( $page_id, true );

	$pages = array();
	foreach ( array_keys( $default_components ) as $component_name ) {
		if ( $component_name == 'activity' ) {
			$pages[$component_name] = _x( 'Activity', 'Page title for the Activity directory.', 'buddypress' );

		} elseif ( $component_name == 'groups') {
			$pages[$component_name] = _x( 'Groups', 'Page title for the Groups directory.', 'buddypress' );

		// Blogs component only needs a directory page when multisite is enabled
		} elseif ( $component_name == 'blogs' && is_multisite() ) {
			$pages[$component_name] = _x( 'Sites', 'Page title for the Sites directory.', 'buddypress' );
		}
	}

	// Mandatory components/pages
	$pages['activate'] = _x( 'Activate', 'Page title for the user account activation screen.', 'buddypress' );
	$pages['members']  = _x( 'Members',  'Page title for the Members directory.',              'buddypress' );
	$pages['register'] = _x( 'Register', 'Page title for the user registration screen.',       'buddypress' );

	// Create the pages
	foreach ( $pages as $component_name => $page_name ) {
		$pages[$component_name] = wp_insert_post( array(
			'comment_status' => 'closed',
			'ping_status'    => 'closed',
			'post_status'    => 'publish',
			'post_title'     => $page_name,
			'post_type'      => 'page',
		) );
	}

	// Save the page mapping
	bp_update_option( 'bp-pages', $pages );

	// If we had to switch_to_blog, go back to the original site.
	if ( ! bp_is_root_blog() )
		restore_current_blog();
}

/**
 * Redirect user to BuddyPress's What's New page on activation
 *
 * @since BuddyPress (1.7)
 *
 * @internal Used internally to redirect BuddyPress to the about page on activation
 *
 * @uses set_transient() To drop the activation transient for 30 seconds
 *
 * @return If bulk activation
 */
function bp_add_activation_redirect() {

	// Bail if activating from network, or bulk
	if ( isset( $_GET['activate-multi'] ) )
		return;

	// Record that this is a new installation, so we show the right
	// welcome message
	if ( bp_is_install() ) {
		set_transient( '_bp_is_new_install', true, 30 );
	}

	// Add the transient to redirect
	set_transient( '_bp_activation_redirect', true, 30 );
}

/** Activation Actions ********************************************************/

/**
 * Runs on BuddyPress activation
 *
 * @since BuddyPress (1.6)
 *
 * @uses do_action() Calls 'bp_activation' hook
 */
function bp_activation() {

	// Force refresh theme roots.
	delete_site_transient( 'theme_roots' );

	// Use as of (1.6)
	do_action( 'bp_activation' );

	// @deprecated as of (1.6)
	do_action( 'bp_loader_activate' );
}

/**
 * Runs on BuddyPress deactivation
 *
 * @since BuddyPress (1.6)
 *
 * @uses do_action() Calls 'bp_deactivation' hook
 */
function bp_deactivation() {

	// Force refresh theme roots.
	delete_site_transient( 'theme_roots' );

	// Switch to WordPress's default theme if current parent or child theme
	// depend on bp-default. This is to prevent white screens of doom.
	if ( in_array( 'bp-default', array( get_template(), get_stylesheet() ) ) ) {
		switch_theme( WP_DEFAULT_THEME, WP_DEFAULT_THEME );
		update_option( 'template_root',   get_raw_theme_root( WP_DEFAULT_THEME, true ) );
		update_option( 'stylesheet_root', get_raw_theme_root( WP_DEFAULT_THEME, true ) );
	}

	// Use as of (1.6)
	do_action( 'bp_deactivation' );

	// @deprecated as of (1.6)
	do_action( 'bp_loader_deactivate' );
}

/**
 * Runs when uninstalling BuddyPress
 *
 * @since BuddyPress (1.6)
 *
 * @uses do_action() Calls 'bp_uninstall' hook
 */
function bp_uninstall() {
	do_action( 'bp_uninstall' );
}
