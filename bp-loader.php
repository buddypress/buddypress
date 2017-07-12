<?php

/**
 * The BuddyPress Plugin
 *
 * BuddyPress is social networking software with a twist from the creators of WordPress.
 *
 * @package BuddyPress
 * @subpackage Main
 */

/**
 * Plugin Name: BuddyPress
 * Plugin URI:  https://buddypress.org
 * Description: BuddyPress adds community features to WordPress. Member Profiles, Activity Streams, Direct Messaging, Notifications, and more!
 * Author:      The BuddyPress Community
 * Author URI:  https://buddypress.org/
 * Version:     2.9.0-beta2
 * Text Domain: buddypress
 * Domain Path: /bp-languages/
 * License:     GPLv2 or later (license.txt)
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

// Assume you want to load from build
$bp_loader = dirname( __FILE__ ) . '/build/bp-loader.php';

// Load from source if no build exists
if ( ! file_exists( $bp_loader ) || defined( 'BP_LOAD_SOURCE' ) ) {
	$bp_loader = dirname( __FILE__ ) . '/src/bp-loader.php';
	$subdir = 'src';
} else {
	$subdir = 'build';
}

// Set source subdirectory
define( 'BP_SOURCE_SUBDIRECTORY', $subdir );

// Define overrides - only applicable to those running trunk
if ( ! defined( 'BP_PLUGIN_DIR' ) ) {
	define( 'BP_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
}
if ( ! defined( 'BP_PLUGIN_URL' ) ) {
	// Be nice to symlinked directories
	define( 'BP_PLUGIN_URL', plugins_url( trailingslashit( basename( constant( 'BP_PLUGIN_DIR' ) ) ) ) );
}

// Include BuddyPress
include( $bp_loader );

// Unset the loader, since it's loaded in global scope
unset( $bp_loader );
