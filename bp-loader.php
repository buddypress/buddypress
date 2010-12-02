<?php
/*
Plugin Name: BuddyPress
Plugin URI: http://buddypress.org
Description: Social networking in a box. Build a social network for your company, school, sports team or niche community all based on the power and flexibility of WordPress.
Author: The BuddyPress Community
Version: 1.3-bleeding
Author URI: http://buddypress.org/community/members/
*/

define( 'BP_VERSION', '1.3-bleeding' );
define( 'BP_DB_VERSION', 1225 );

// Define on which blog ID BuddyPress should run
if ( !defined( 'BP_ROOT_BLOG' ) )
	define( 'BP_ROOT_BLOG', 1 );

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

	add_action( 'plugins_loaded', 'bp_loaded', 20 );
}

/**
* bp_loaded()
*
* Allow dependent plugins and core actions to attach themselves in a safe way.
*
* See bp-core.php for the following core actions:
*      - bp_init|bp_setup_globals|bp_setup_root_components|bp_setup_nav|bp_register_widgets
*/
function bp_loaded() {
	do_action( 'bp_loaded' );
}

/**
 * bp_core_get_site_options()
 *
 * BuddyPress uses site options to store configuration settings. Many of these settings are needed
 * at run time. Instead of fetching them all and adding many initial queries to each page load, let's fetch
 * them all in one go.
 *
 * @package BuddyPress Core
 */
function bp_core_get_site_options() {
	global $bp, $wpdb;

	// These options come from the options table in WP single, and sitemeta in MS
	$site_options = apply_filters( 'bp_core_site_options', array(
		'bp-deactivated-components',
		'bp-blogs-first-install',
		'bp-disable-blog-forum-comments',
		'bp-xprofile-base-group-name',
		'bp-xprofile-fullname-field-name',
		'bp-disable-profile-sync',
		'bp-disable-avatar-uploads',
		'bp-disable-account-deletion',
		'bp-disable-forum-directory',
		'bp-disable-blogforum-comments',
		'bb-config-location',
		'hide-loggedout-adminbar',

		// Useful WordPress settings used often
		'tags_blog_id',
		'registration',
		'fileupload_maxk'
	) );
	
	// These options always come from the options table of BP_ROOT_BLOG
	$root_blog_options = apply_filters( 'bp_core_root_blog_options', array(
		'avatar_default'
	) );

	$meta_keys = "'" . implode( "','", (array)$site_options ) ."'";

	if ( is_multisite() )
		$site_meta = $wpdb->get_results( "SELECT meta_key AS name, meta_value AS value FROM {$wpdb->sitemeta} WHERE meta_key IN ({$meta_keys}) AND site_id = {$wpdb->siteid}" );
	else
		$site_meta = $wpdb->get_results( "SELECT option_name AS name, option_value AS value FROM {$wpdb->options} WHERE option_name IN ({$meta_keys})" );
		
	$root_blog_meta_keys = "'" . implode( "','", (array)$root_blog_options ) ."'";
	
	$root_blog_meta_table = BP_ROOT_BLOG == 1 ? $wpdb->base_prefix . 'options' : $wpdb->base_prefix . BP_ROOT_BLOG . '_options';
	$root_blog_meta = $wpdb->get_results( $wpdb->prepare( "SELECT option_name AS name, option_value AS value FROM {$root_blog_meta_table} WHERE option_name IN ({$root_blog_meta_keys})" ) );

	$site_options = array();
	foreach( array( $site_meta, $root_blog_meta ) as $meta ) {
		if ( !empty( $meta ) ) {
			foreach( (array)$meta as $meta_item )
				$site_options[$meta_item->name] = $meta_item->value;
		}
	}
	return apply_filters( 'bp_core_get_site_options', $site_options );
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

	delete_site_option( 'bp-core-db-version' );
	delete_site_option( 'bp-activity-db-version' );
	delete_site_option( 'bp-blogs-db-version' );
	delete_site_option( 'bp-friends-db-version' );
	delete_site_option( 'bp-groups-db-version' );
	delete_site_option( 'bp-messages-db-version' );
	delete_site_option( 'bp-xprofile-db-version' );
	delete_site_option( 'bp-deactivated-components' );
	delete_site_option( 'bp-blogs-first-install' );

	do_action( 'bp_loader_deactivate' );
}
register_deactivation_hook( 'buddypress/bp-loader.php', 'bp_loader_deactivate' );

?>