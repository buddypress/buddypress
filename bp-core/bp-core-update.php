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
 * @global BuddyPress $bp
 * @return bool True if activating BuddyPress, false if not
 */
function bp_is_activation( $basename = '' ) {
	global $bp;

	// Baif if action or plugin are empty
	if ( empty( $_GET['action'] ) || empty( $_GET['plugin'] ) )
		return false;

	// Bail if not activating
	if ( 'activate' !== $_GET['action'] )
		return false;

	// The plugin being activated
	$plugin = isset( $_GET['plugin'] ) ? $_GET['plugin'] : '';

	// Set basename if empty
	if ( empty( $basename ) && !empty( $bp->basename ) )
		$basename = $bp->basename;

	// Bail if no basename
	if ( empty( $basename ) )
		return false;

	// Bail if plugin is not BuddyPress
	if ( $basename !== $plugin )
		return false;

	return true;
}

/**
 * Determine if BuddyPress is being deactivated
 *
 * @since BuddyPress (1.6)
 *
 * @global BuddyPress $bp
 * @return bool True if deactivating BuddyPress, false if not
 */
function bp_is_deactivation( $basename = '' ) {
	global $bp;

	// Baif if action or plugin are empty
	if ( empty( $_GET['action'] ) || empty( $_GET['plugin'] ) )
		return false;

	// Bail if not deactivating
	if ( 'deactivate' !== $_GET['action'] )
		return false;

	// The plugin being deactivated
	$plugin = isset( $_GET['plugin'] ) ? $_GET['plugin'] : '';

	// Set basename if empty
	if ( empty( $basename ) && !empty( $bp->basename ) )
		$basename = $bp->basename;

	// Bail if no basename
	if ( empty( $basename ) )
		return false;

	// Bail if plugin is not BuddyPress
	if ( $basename !== $plugin )
		return false;

	return true;
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
	$db_version = bp_get_db_version();
	bp_update_option( '_bp_db_version', $db_version );
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
	if ( bp_is_update() ) {

		// Bump the version
		bp_version_bump();

		// Run the deactivation function to wipe roles, caps, and rewrite rules
		bp_deactivation();

		// Run the activation function to reset roles, caps, and rewrite rules
		bp_activation();
	}
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

?>
