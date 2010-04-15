<?php
/*
Plugin Name: BuddyPress
Plugin URI: http://buddypress.org/download/
Description: Social networking in a box. Build a social network for your company, school, sports team or niche community all based on the power and flexibility of WordPress.
Author: The BuddyPress Community
Version: 1.3-bleeding
Author URI: http://buddypress.org/developers/
Network: true
*/

define( 'BP_VERSION', '1.3-bleeding' );
define( 'BP_DB_VERSION', 1225 );

/***
 * Check if this is the first time BuddyPress has been loaded, or the first time
 * since an upgrade. If so, load the install/upgrade routine only.
 */
if ( get_site_option( 'bp-db-version' ) < constant( 'BP_DB_VERSION' ) ) {
	require_once( WP_PLUGIN_DIR . '/buddypress/bp-core/admin/bp-core-upgrade.php' );

/***
 * If the install or upgrade routine is completed and everything is up to date
 * continue loading BuddyPress as normal.
 */
} else {
	/***
	 * This file will load in each BuddyPress component based on which
	 * of the components have been activated on the "BuddyPress" admin menu.
	 */
	require_once( WP_PLUGIN_DIR . '/buddypress/bp-core.php' );
	$bp_deactivated = apply_filters( 'bp_deactivated_components', get_site_option( 'bp-deactivated-components' ) );

	do_action( 'bp_core_loaded' );

	/* Activity Streams */
	if ( !isset( $bp_deactivated['bp-activity.php'] ) && file_exists( BP_PLUGIN_DIR . '/bp-activity.php') )
		include( BP_PLUGIN_DIR . '/bp-activity.php' );

	/* Blog Tracking */
	if ( !isset( $bp_deactivated['bp-blogs.php'] ) && file_exists( BP_PLUGIN_DIR . '/bp-blogs.php') )
		include( BP_PLUGIN_DIR . '/bp-blogs.php' );

	/* bbPress Forum Integration */
	if ( !isset( $bp_deactivated['bp-forums.php'] ) && file_exists( BP_PLUGIN_DIR . '/bp-forums.php') )
		include( BP_PLUGIN_DIR . '/bp-forums.php' );

	/* Friend Connections */
	if ( !isset( $bp_deactivated['bp-friends.php'] ) && file_exists( BP_PLUGIN_DIR . '/bp-friends.php') )
		include( BP_PLUGIN_DIR . '/bp-friends.php' );

	/* Groups Support */
	if ( !isset( $bp_deactivated['bp-groups.php'] ) && file_exists( BP_PLUGIN_DIR . '/bp-groups.php') )
		include( BP_PLUGIN_DIR . '/bp-groups.php' );

	/* Private Messaging */
	if ( !isset( $bp_deactivated['bp-messages.php'] ) && file_exists( BP_PLUGIN_DIR . '/bp-messages.php') )
		include( BP_PLUGIN_DIR . '/bp-messages.php' );

	/* Extended Profiles */
	if ( !isset( $bp_deactivated['bp-xprofile.php'] ) && file_exists( BP_PLUGIN_DIR . '/bp-xprofile.php') )
		include( BP_PLUGIN_DIR . '/bp-xprofile.php' );

	/* Allow dependent plugins to hook into BuddyPress in a safe way */
	function bp_loaded() {
		do_action( 'bp_init' );
	}
	add_action( 'plugins_loaded', 'bp_loaded' );
}

/* Activation Function */
function bp_loader_activate() {
	/* Force refresh theme roots. */
	delete_site_transient( 'theme_roots' );

	/* Switch the user to the new bp-default if they are using the old bp-default on activation. */
	if ( 'bp-sn-parent' == get_blog_option( BP_ROOT_BLOG, 'template' ) && 'bp-default' == get_blog_option( BP_ROOT_BLOG, 'stylesheet' ) )
		switch_theme( 'bp-default', 'bp-default' );

	do_action( 'bp_loader_activate' );
}
register_activation_hook( 'buddypress/bp-loader.php', 'bp_loader_activate' );

/* Deactivation Function */
function bp_loader_deactivate() {
	if ( !function_exists( 'delete_site_option') )
		return false;

	delete_site_option( 'bp-deactivated-components' );
	delete_site_option( 'bp-blogs-first-install' );
	delete_site_option( 'bp-pages' );

	do_action( 'bp_loader_deactivate' );
}
register_deactivation_hook( 'buddypress/bp-loader.php', 'bp_loader_deactivate' );

?>