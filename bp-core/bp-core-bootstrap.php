<?php

// Test to see whether this is a new installation or an upgraded version of BuddyPress
if ( !$bp_db_version = get_site_option( 'bp-db-version' ) )
	$bp_db_version = get_site_option( 'bp-core-db-version' );  // BP 1.2 option name

// This is a new installation. Run the wizard before loading BP core files
if ( empty( $bp_db_version ) ) {
 	define( 'BP_IS_INSTALL', true );
	require_once( WP_PLUGIN_DIR . '/buddypress/bp-core/admin/bp-core-update.php' );

// Existing successful installation
} else {

	// Always require the BuddyPress Core - It cannot be turned off
	require_once( WP_PLUGIN_DIR . '/buddypress/bp-core/bp-core-loader.php' );

	/**
	 * At this point in the stack, BuddyPress core has been loaded but
	 * individual components (friends/activity/groups/etc...) have not.
	 *
	 * The 'bp_core_loaded' action lets you execute code ahead of the
	 * other components.
	 */
	do_action( 'bp_core_loaded' );

	// Get a list of deactivated components
	$bp_deactivated = apply_filters( 'bp_deactivated_components', get_site_option( 'bp-deactivated-components' ) );

	// Activity Streams
	if ( !isset( $bp_deactivated['bp-activity/bp-activity-loader.php'] ) && file_exists( BP_PLUGIN_DIR . '/bp-activity/bp-activity-loader.php') )
		include( BP_PLUGIN_DIR . '/bp-activity/bp-activity-loader.php' );

	// Blog Tracking
	if ( !isset( $bp_deactivated['bp-blogs/bp-blogs-loader.php'] ) && file_exists( BP_PLUGIN_DIR . '/bp-blogs/bp-blogs-loader.php') )
		include( BP_PLUGIN_DIR . '/bp-blogs/bp-blogs-loader.php' );

	// bbPress Forum Integration
	if ( !isset( $bp_deactivated['bp-forums/bp-forums-loader.php'] ) && file_exists( BP_PLUGIN_DIR . '/bp-forums/bp-forums-loader.php') )
		include( BP_PLUGIN_DIR . '/bp-forums/bp-forums-loader.php' );

	// Friend Connections
	if ( !isset( $bp_deactivated['bp-friends/bp-friends-loader.php'] ) && file_exists( BP_PLUGIN_DIR . '/bp-friends/bp-friends-loader.php') )
		include( BP_PLUGIN_DIR . '/bp-friends/bp-friends-loader.php' );

	// Groups Support
	if ( !isset( $bp_deactivated['bp-groups/bp-groups-loader.php'] ) && file_exists( BP_PLUGIN_DIR . '/bp-groups/bp-groups-loader.php') )
		include( BP_PLUGIN_DIR . '/bp-groups/bp-groups-loader.php' );

	// Private Messaging
	if ( !isset( $bp_deactivated['bp-messages/bp-messages-loader.php'] ) && file_exists( BP_PLUGIN_DIR . '/bp-messages/bp-messages-loader.php') )
		include( BP_PLUGIN_DIR . '/bp-messages/bp-messages-loader.php' );

	// Extended Profiles
	if ( !isset( $bp_deactivated['bp-xprofile/bp-xprofile-loader.php'] ) && file_exists( BP_PLUGIN_DIR . '/bp-xprofile/bp-xprofile-loader.php') )
		include( BP_PLUGIN_DIR . '/bp-xprofile/bp-xprofile-loader.php' );

	// Member Settings
	if ( !isset( $bp_deactivated['bp-settings/bp-settings-loader.php'] ) && file_exists( BP_PLUGIN_DIR . '/bp-settings/bp-settings-loader.php') )
		include( BP_PLUGIN_DIR . '/bp-settings/bp-settings-loader.php' );

	// Always require BuddyPress Members - It cannot be turned off (yet)
	include( BP_PLUGIN_DIR . '/bp-members/bp-members-loader.php'   );

	// If this is an upgrade, load the upgrade file
	if ( $bp_db_version < constant( 'BP_DB_VERSION' ) ) {
		define( 'BP_IS_UPGRADE', true );
		require_once( WP_PLUGIN_DIR . '/buddypress/bp-core/admin/bp-core-update.php' );
	}
}

?>
