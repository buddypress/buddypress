<?php
/**
 * Plugin Name: BuddyPress
 * Plugin URI:  http://buddypress.org
 * Description: Social networking in a box. Build a social network for your company, school, sports team or niche community all based on the power and flexibility of WordPress.
 * Author:      The BuddyPress Community
 * Version:     1.5.3
 * Author URI:  http://buddypress.org/community/members/
 * Network:     true
 */

// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

/** Constants *****************************************************************/
global $wpdb;

// Define the BuddyPress version
if ( !defined( 'BP_VERSION' ) )
	define( 'BP_VERSION', '1.5.3' );

// Define the database version
if ( !defined( 'BP_DB_VERSION' ) )
	define( 'BP_DB_VERSION', 3820 );

// Place your custom code (actions/filters) in a file called
// '/plugins/bp-custom.php' and it will be loaded before anything else.
if ( file_exists( WP_PLUGIN_DIR . '/bp-custom.php' ) )
	require( WP_PLUGIN_DIR . '/bp-custom.php' );

// Define on which blog ID BuddyPress should run
if ( !defined( 'BP_ROOT_BLOG' ) ) {

	// Root blog is the main site on this network
	if ( is_multisite() && !defined( 'BP_ENABLE_MULTIBLOG' ) ) {
		$current_site = get_current_site();
		$root_blog_id = $current_site->blog_id;

	// Root blog is every site on this network
	} elseif ( is_multisite() && defined( 'BP_ENABLE_MULTIBLOG' ) ) {
		$root_blog_id = get_current_blog_id();

	// Root blog is the only blog on this network
	} elseif( !is_multisite() ) {
		$root_blog_id = 1;
	}

	define( 'BP_ROOT_BLOG', $root_blog_id );
}

// Path and URL
if ( !defined( 'BP_PLUGIN_DIR' ) )
	define( 'BP_PLUGIN_DIR', WP_PLUGIN_DIR . '/buddypress' );

if ( !defined( 'BP_PLUGIN_URL' ) )
	define( 'BP_PLUGIN_URL', plugins_url( 'buddypress' ) );

// The search slug has to be defined nice and early because of the way search requests are loaded
if ( !defined( 'BP_SEARCH_SLUG' ) )
	define( 'BP_SEARCH_SLUG', 'search' );

// Setup the BuddyPress theme directory
register_theme_directory( BP_PLUGIN_DIR . '/bp-themes' );

/** Loader ********************************************************************/

// Load the WP abstraction file so BuddyPress can run on all WordPress setups.
require( BP_PLUGIN_DIR . '/bp-core/bp-core-wpabstraction.php' );

// Test to see whether this is a new installation or an upgraded version of BuddyPress
if ( !$bp->database_version = get_site_option( 'bp-db-version' ) ) {
	if ( $bp->database_version = get_option( 'bp-db-version' ) ) {
		$bp->is_network_activate = 1;
	} else {
		$bp->database_version = get_site_option( 'bp-core-db-version' );  // BP 1.2 option
	}
}

// This is a new installation.
if ( empty( $bp->database_version ) ) {
	$bp->maintenance_mode = 'install';
	require( BP_PLUGIN_DIR . '/bp-core/admin/bp-core-update.php' );

// There is a previous installation
} else {
	// Load core
	require( BP_PLUGIN_DIR . '/bp-core/bp-core-loader.php' );

	// Check if an update is required
	if ( (int)$bp->database_version < (int)constant( 'BP_DB_VERSION' ) || isset( $bp->is_network_activate ) ) {
		$bp->maintenance_mode = 'update';
		require( BP_PLUGIN_DIR . '/bp-core/admin/bp-core-update.php' );
	}
}

/** Activation ****************************************************************/

if ( !function_exists( 'bp_loader_activate' ) ) :
/**
 * Defines BP's activation routine.
 *
 * Most of BP's crucial setup is handled by the setup wizard. This function takes care of some
 * issues with incompatible legacy themes, and provides a hook for other functions to know that
 * BP has been activated.
 *
 * @package BuddyPress Core
*/
function bp_loader_activate() {
	// Force refresh theme roots.
	delete_site_transient( 'theme_roots' );

	if ( !function_exists( 'get_blog_option' ) )
		require ( WP_PLUGIN_DIR . '/buddypress/bp-core/bp-core-wpabstraction.php' );

	if ( !function_exists( 'bp_get_root_blog_id' ) )
		require ( WP_PLUGIN_DIR . '/buddypress/bp-core/bp-core-functions.php' );

	// Switch the user to the new bp-default if they are using the old
	// bp-default on activation.
	if ( 'bp-sn-parent' == get_blog_option( bp_get_root_blog_id(), 'template' ) && 'bp-default' == get_blog_option( bp_get_root_blog_id(), 'stylesheet' ) )
		switch_theme( 'bp-default', 'bp-default' );

	do_action( 'bp_loader_activate' );
}
register_activation_hook( 'buddypress/bp-loader.php', 'bp_loader_activate' );
endif;

if ( !function_exists( 'bp_loader_deactivate' ) ) :
// Deactivation Function
function bp_loader_deactivate() {
	do_action( 'bp_loader_deactivate' );
}
register_deactivation_hook( 'buddypress/bp-loader.php', 'bp_loader_deactivate' );
endif;

?>
