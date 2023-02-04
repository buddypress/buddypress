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
 * Plugin Name:       BuddyPress
 * Plugin URI:        https://buddypress.org
 * Description:       BuddyPress adds community features to WordPress. Member Profiles, Activity Streams, Direct Messaging, Notifications, and more!
 * Author:            The BuddyPress Community
 * Author URI:        https://buddypress.org
 * License:           GNU General Public License v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       buddypress
 * Domain Path:       /bp-languages/
 * Requires PHP:      5.6
 * Requires at least: 5.7
 * Version:           12.0.0-alpha
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

// Assume you want to load from build
$bp_loader = dirname( __FILE__ ) . '/build/bp-loader.php';

// Load from source if no build exists
if ( ! file_exists( $bp_loader ) || defined( 'BP_LOAD_SOURCE' ) ) {
	$bp_loader = dirname( __FILE__ ) . '/src/bp-loader.php';
	$bp_subdir = 'src';
} else {
	$bp_subdir = 'build';
}

// Set source subdirectory
define( 'BP_SOURCE_SUBDIRECTORY', $bp_subdir );

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

// Unset vars that were invoked in global scope
unset( $bp_loader, $bp_subdir );
