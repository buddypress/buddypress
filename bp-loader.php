<?php
/**
 * Plugin Name: BuddyPress
 * Plugin URI:  http://buddypress.org
 * Description: Social networking in a box. Build a social network for your company, school, sports team or niche community all based on the power and flexibility of WordPress.
 * Author:      The BuddyPress Community
 * Version:     1.3-bleeding
 * Author URI:  http://buddypress.org/community/members/
 * Network:     true
 */

/** Constants *****************************************************************/

// Define the BuddyPress version
if ( !defined( 'BP_VERSION' ) )
	define( 'BP_VERSION', '1.3-bleeding' );

// Define the database version
if ( !defined( 'BP_DB_VERSION' ) )
	define( 'BP_DB_VERSION', 3706 );

// Place your custom code (actions/filters) in a file called
// '/plugins/bp-custom.php' and it will be loaded before anything else.
if ( file_exists( WP_PLUGIN_DIR . '/bp-custom.php' ) )
	require_once( WP_PLUGIN_DIR . '/bp-custom.php' );

// Define on which blog ID BuddyPress should run
if ( !defined( 'BP_ROOT_BLOG' ) )
	define( 'BP_ROOT_BLOG', 1 );

// Path and URL
if ( !defined( 'BP_PLUGIN_DIR' ) )
	define( 'BP_PLUGIN_DIR', WP_PLUGIN_DIR . '/buddypress' );

if ( !defined( 'BP_PLUGIN_URL' ) )
	define( 'BP_PLUGIN_URL', plugins_url( $path = '/buddypress' ) );

// Define the user and usermeta table names, useful if you are using custom or shared tables.
if ( !defined( 'CUSTOM_USER_TABLE' ) )
	define( 'CUSTOM_USER_TABLE',      $wpdb->base_prefix . 'users' );

if ( !defined( 'CUSTOM_USER_META_TABLE' ) )
	define( 'CUSTOM_USER_META_TABLE', $wpdb->base_prefix . 'usermeta' );

// The search slug has to be defined nice and early because of the way search requests are loaded
if ( !defined( 'BP_SEARCH_SLUG' ) )
	define( 'BP_SEARCH_SLUG', 'search' );

// Setup the BuddyPress theme directory
register_theme_directory( WP_PLUGIN_DIR . '/buddypress/bp-themes' );

/** Loader ********************************************************************/

// Load the WP abstraction file so BuddyPress can run on all WordPress setups.
require_once( BP_PLUGIN_DIR . '/bp-core/bp-core-wpabstraction.php' );

// Test to see whether this is a new installation or an upgraded version of BuddyPress
if ( !$bp->database_version = get_site_option( 'bp-db-version' ) )
	$bp->database_version = get_site_option( 'bp-core-db-version' );  // BP 1.2 option name

// This is a new installation.
if ( empty( $bp->database_version ) ) {
	$bp->maintenence_mode = 'install';
	require_once( WP_PLUGIN_DIR . '/buddypress/bp-core/admin/bp-core-update.php' );

// There is a previous installation
} else {
	// Load core
	require_once( WP_PLUGIN_DIR . '/buddypress/bp-core/bp-core-loader.php' );

	// Check if an update is required
	if ( (int)$bp->database_version < (int)constant( 'BP_DB_VERSION' ) ) {
		$bp->maintenence_mode = 'update';
		require_once( WP_PLUGIN_DIR . '/buddypress/bp-core/admin/bp-core-update.php' );
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

	// Switch the user to the new bp-default if they are using the old
	// bp-default on activation.
	if ( 'bp-sn-parent' == get_blog_option( BP_ROOT_BLOG, 'template' ) && 'bp-default' == get_blog_option( BP_ROOT_BLOG, 'stylesheet' ) )
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
