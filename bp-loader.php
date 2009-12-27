<?php
/*
Plugin Name: BuddyPress
Plugin URI: http://buddypress.org/download/
Description: BuddyPress will add social networking features to a new or existing WordPress MU installation.
Author: The BuddyPress Community
Version: 1.2-bleeding
Author URI: http://buddypress.org/developers/
Site Wide Only: true
*/

define( 'BP_VERSION', '1.2-bleeding' );

/***
 * This file will load in each BuddyPress component based on which
 * of the components have been activated on the "BuddyPress" admin menu.
 */

require_once( WP_PLUGIN_DIR . '/buddypress/bp-core.php' );
$deactivated = apply_filters( 'bp_deactivated_components', get_site_option( 'bp-deactivated-components' ) );

/* Activity Streams */
if ( !isset( $deactivated['bp-activity.php'] ) && file_exists( BP_PLUGIN_DIR . '/bp-activity.php') )
	include( BP_PLUGIN_DIR . '/bp-activity.php' );

/* Blog Tracking */
if ( !isset( $deactivated['bp-blogs.php'] ) && file_exists( BP_PLUGIN_DIR . '/bp-blogs.php') )
	include( BP_PLUGIN_DIR . '/bp-blogs.php' );

/* bbPress Forum Integration */
if ( !isset( $deactivated['bp-forums.php'] ) && file_exists( BP_PLUGIN_DIR . '/bp-forums.php') )
	include( BP_PLUGIN_DIR . '/bp-forums.php' );

/* Friend Connections */
if ( !isset( $deactivated['bp-friends.php'] ) && file_exists( BP_PLUGIN_DIR . '/bp-friends.php') )
	include( BP_PLUGIN_DIR . '/bp-friends.php' );

/* Groups Support */
if ( !isset( $deactivated['bp-groups.php'] ) && file_exists( BP_PLUGIN_DIR . '/bp-groups.php') )
	include( BP_PLUGIN_DIR . '/bp-groups.php' );

/* Private Messaging */
if ( !isset( $deactivated['bp-messages.php'] ) && file_exists( BP_PLUGIN_DIR . '/bp-messages.php') )
	include( BP_PLUGIN_DIR . '/bp-messages.php' );

/* Extended Profiles */
if ( !isset( $deactivated['bp-xprofile.php'] ) && file_exists( BP_PLUGIN_DIR . '/bp-xprofile.php') )
	include( BP_PLUGIN_DIR . '/bp-xprofile.php' );

/* Activation Function */
function bp_loader_activate() {
	/* Force refresh theme roots. */
	delete_site_transient( 'theme_roots' );
}
register_activation_hook( __FILE__, 'bp_loader_activate' );

/* Deactivation Function */
function bp_loader_deactivate() {
	if ( !function_exists( 'delete_site_option') )
		return false;

	delete_site_option( 'bp-core-db-version' );
	delete_site_option( 'bp-activity-db-version' );
	delete_site_option( 'bp-blogs-db-version' );
	delete_site_option( 'bp-friends-db-version' );
	delete_site_option( 'bp-groups-db-version' );
	delete_site_option( 'bp-messages-db-version' );
	delete_site_option( 'bp-xprofile-db-version' );
}
register_deactivation_hook( __FILE__, 'bp_loader_deactivate' );

?>