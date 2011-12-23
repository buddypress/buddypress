<?php

/**
 * BuddyPress Options
 *
 * @package BuddyPress
 * @subpackage Options
 */

// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

/**
 * Get the default site options and their values
 *
 * @since BuddyPress (1.6)
 *
 * @return array Filtered option names and values
 */
function bp_get_default_options() {

	// Default options
	$options = array (

		/** DB Version ********************************************************/

		'_bp_db_version'                  => '155',

		/** Components ********************************************************/

		'bp-deactivated-components'       => serialize( array() ),

		/** bbPress ***********************************************************/

		// Legacy bbPress config location
		'bb-config-location'              => ABSPATH . 'bb-config.php',

		/** XProfile **********************************************************/

		// Base profile groups name
		'bp-xprofile-base-group-name'     => 'Base',

		// Base fullname field name
		'bp-xprofile-fullname-field-name' => 'Name',

		/** Blogs *************************************************************/

		// Used to decide if blogs need indexing
		'bp-blogs-first-install'          => false,

		/** Settings **********************************************************/

		// Disable the WP to BP profile sync
		'bp-disable-profile-sync'         => false,

		// Hide the admin bar for logged out users
		'hide-loggedout-adminbar'         => false,

		// Avatar uploads
		'bp-disable-avatar-uploads'       => false,

		// Allow users to delete their own accounts
		'bp-disable-account-deletion'     => true,

		// Allow comments on blog and forum activity items
		'bp-disable-blogforum-comments'   => true,

		// Use the WordPress editor when possible
		'_bp_use_wp_editor'               => false,

		/** Groups ************************************************************/

		// @todo Move this into the groups component

		// Restrict group creation to super admins
		'bp_restrict_group_creation'      => false,

		// Root forum ID for groups
		'_bbp_group_forums_root_id'       => 0,

		/** Akismet ***********************************************************/

		// Users from all sites can post
		'_bp_enable_akismet'              => true,
	);

	return apply_filters( 'bp_get_default_options', $options );
}

/**
 * Add default options
 *
 * Hooked to bp_activate, it is only called once when BuddyPress is activated.
 * This is non-destructive, so existing settings will not be overridden.
 *
 * @since BuddyPress (1.6)
 *
 * @uses bp_get_default_options() To get default options
 * @uses add_option() Adds default options
 * @uses do_action() Calls 'bp_add_options'
 */
function bp_add_options() {

	// Get the default options and values
	$options = bp_get_default_options();

	// Add default options
	foreach ( $options as $key => $value )
		add_option( $key, $value );

	// Allow previously activated plugins to append their own options.
	do_action( 'bp_add_options' );
}

/**
 * Delete default options
 *
 * Hooked to bp_uninstall, it is only called once when BuddyPress is uninstalled.
 * This is destructive, so existing settings will be destroyed.
 *
 * @since BuddyPress (1.6)
 * 
 * @uses bp_get_default_options() To get default options
 * @uses delete_option() Removes default options
 * @uses do_action() Calls 'bp_delete_options'
 */
function bp_delete_options() {

	// Get the default options and values
	$options = bp_get_default_options();

	// Add default options
	foreach ( $options as $key => $value )
		delete_option( $key );

	// Allow previously activated plugins to append their own options.
	do_action( 'bp_delete_options' );
}

/**
 * Add filters to each BuddyPress option and allow them to be overloaded from
 * inside the $bp->options array.
 *
 * @since BuddyPress (1.6)
 *
 * @uses bp_get_default_options() To get default options
 * @uses add_filter() To add filters to 'pre_option_{$key}'
 * @uses do_action() Calls 'bp_add_option_filters'
 */
function bp_setup_option_filters() {

	// Get the default options and values
	$options = bp_get_default_options();

	// Add filters to each BuddyPress option
	foreach ( $options as $key => $value )
		add_filter( 'pre_option_' . $key, 'bp_pre_get_option' );

	// Allow previously activated plugins to append their own options.
	do_action( 'bp_setup_option_filters' );
}

/**
 * Filter default options and allow them to be overloaded from inside the
 * $bp->options array.
 *
 * @since BuddyPress (1.6)
 *
 * @global BuddyPress $bp
 * @param bool $value Optional. Default value false
 * @return mixed false if not overloaded, mixed if set
 */
function bp_pre_get_option( $value = false ) {
	global $bp;

	// Get the name of the current filter so we can manipulate it
	$filter = current_filter();

	// Remove the filter prefix
	$option = str_replace( 'pre_option_', '', $filter );

	// Check the options global for preset value
	if ( !empty( $bp->options[$option] ) )
		$value = $bp->options[$option];

	// Always return a value, even if false
	return $value;
}

/**
 * When switching from single to multisite we need to copy blog options to
 * site options.
 *
 * @package BuddyPress Core
 * @todo Does this need to be here anymore after the introduction of bp_get_option etc?
 */
function bp_core_activate_site_options( $keys = array() ) {
	global $bp;

	if ( !empty( $keys ) && is_array( $keys ) ) {
		$errors = false;

		foreach ( $keys as $key => $default ) {
			if ( empty( $bp->site_options[ $key ] ) ) {
				$bp->site_options[ $key ] = bp_get_option( $key, $default );

				if ( !bp_update_option( $key, $bp->site_options[ $key ] ) )
					$errors = true;
			}
		}

		if ( empty( $errors ) )
			return true;
	}

	return false;
}

/**
 * BuddyPress uses common options to store configuration settings. Many of these
 * settings are needed at run time. Instead of fetching them all and adding many
 * initial queries to each page load, let's fetch them all in one go.
 *
 * @package BuddyPress Core
 * @todo Use settings API and audit these methods
 */
function bp_core_get_root_options() {
	global $wpdb;

	// Get all the BuddyPress settings, and a few useful WP ones too
	$root_blog_options                   = bp_get_default_options();
	$root_blog_options['registration']   = '0';
	$root_blog_options['avatar_default'] = 'mysteryman';
	$root_blog_option_keys               = array_keys( $root_blog_options );

	// Do some magic to get all the root blog options in 1 swoop
	$blog_options_keys      = "'" . join( "', '", (array) $root_blog_option_keys ) . "'";
	$blog_options_table	    = bp_is_multiblog_mode() ? $wpdb->options : $wpdb->get_blog_prefix( bp_get_root_blog_id() ) . 'options';
	$blog_options_query     = $wpdb->prepare( "SELECT option_name AS name, option_value AS value FROM {$blog_options_table} WHERE option_name IN ( {$blog_options_keys} )" );
	$root_blog_options_meta = $wpdb->get_results( $blog_options_query );

	// On Multisite installations, some options must always be fetched from sitemeta
	if ( is_multisite() ) {
		$network_options = apply_filters( 'bp_core_network_options', array(
			'tags_blog_id'       => '0',
			'sitewide_tags_blog' => '',
			'registration'       => '0',
			'fileupload_maxk'    => '1500'
		) );

		$current_site           = get_current_site();
		$network_option_keys    = array_keys( $network_options );
		$sitemeta_options_keys  = "'" . join( "', '", (array) $network_option_keys ) . "'";
		$sitemeta_options_query = $wpdb->prepare( "SELECT meta_key AS name, meta_value AS value FROM {$wpdb->sitemeta} WHERE meta_key IN ( {$sitemeta_options_keys} ) AND site_id = %d", $current_site->id );
		$network_options_meta   = $wpdb->get_results( $sitemeta_options_query );

		// Sitemeta comes second in the merge, so that network 'registration' value wins
		$root_blog_options_meta = array_merge( $root_blog_options_meta, $network_options_meta );
	}

	// Missing some options, so do some one-time fixing
	if ( empty( $root_blog_options_meta ) || ( count( $root_blog_options_meta ) < count( $root_blog_option_keys ) ) ) {

		// Unset the query - We'll be resetting it soon
		unset( $root_blog_options_meta );

		// Loop through options
		foreach ( $root_blog_options as $old_meta_key => $old_meta_default ) {
			// Clear out the value from the last time around
			unset( $old_meta_value );

			// Get old site option
			if ( is_multisite() )
				$old_meta_value = get_site_option( $old_meta_key );

			// No site option so look in root blog
			if ( empty( $old_meta_value ) )
				$old_meta_value = bp_get_option( $old_meta_key, $old_meta_default );

			// Update the root blog option
			bp_update_option( $old_meta_key, $old_meta_value );

			// Update the global array
			$root_blog_options_meta[$old_meta_key] = $old_meta_value;
		}

	// We're all matched up
	} else {
		// Loop through our results and make them usable
		foreach ( $root_blog_options_meta as $root_blog_option )
			$root_blog_options[$root_blog_option->name] = $root_blog_option->value;

		// Copy the options no the return val
		$root_blog_options_meta = $root_blog_options;

		// Clean up our temporary copy
		unset( $root_blog_options );
	}

	return apply_filters( 'bp_core_get_root_options', $root_blog_options_meta );
}

/** Active? *******************************************************************/

/**
 * Is profile sycing disabled?
 *
 * @since BuddyPress (1.6)
 *
 * @param $default bool Optional.Default value true
 *
 * @uses get_option() To get the profile sync option
 * @return bool Is profile sync enabled or not
 */
function bp_disable_profile_sync( $default = true ) {
	return (bool) apply_filters( 'bp_disable_profile_sync', (bool) get_option( 'bp-disable-profile-sync', $default ) );
}

/**
 * Is the admin bar hidden for logged out users?
 *
 * @since BuddyPress (1.6)
 *
 * @param $default bool Optional.Default value true
 *
 * @uses get_option() To get the logged out admin bar option
 * @return bool Is logged out admin bar enabled or not
 */
function bp_hide_loggedout_adminbar( $default = true ) {
	return (bool) apply_filters( 'bp_hide_loggedout_adminbar', (bool) get_option( 'hide-loggedout-adminbar', $default ) );
}

/**
 * Are members able to upload their own avatars?
 *
 * @since BuddyPress (r3412)
 *
 * @param $default bool Optional. Default value true
 *
 * @uses get_option() To get the avatar uploads option
 * @return bool Are avatar uploads allowed?
 */
function bp_disable_avatar_uploads( $default = true ) {
	return (bool) apply_filters( 'bp_disable_avatar_uploads', (bool) get_option( 'bp-disable-avatar-uploads', $default ) );
}

/**
 * Are members able to delete their own accounts
 *
 * @since BuddyPress (1.6)
 *
 * @param $default bool Optional. Default value
 *
 * @uses get_option() To get the account deletion option
 * @return bool Is account deletion allowed?
 */
function bp_disable_account_deletion( $default = false ) {
	return apply_filters( 'bp_disable_account_deletion', (bool) get_option( 'bp-disable-account-deletion', $default ) );
}

/**
 * Are blog and forum activity stream comments disabled
 *
 * @since BuddyPress (1.6)
 *
 * @param $default bool Optional. Default value false
 * @todo split and move into blog and forum components
 * @uses get_option() To get the blog/forum comments option
 * @return bool Is blog/forum comments allowed?
 */
function bp_disable_blogforum_comments( $default = false ) {
	return (bool) apply_filters( 'bp_disable_blogforum_comments', (bool) get_option( 'bp-disable-blogforum-comments', $default ) );
}

/**
 * Is group creation turned off?
 *
 * @since BuddyPress (r3386)
 *
 * @param $default bool Optional. Default value true
 *
 * @todo Move into groups component
 * @uses get_option() To get the group creation
 * @return bool Allow group creation?
 */
function bp_restrict_group_creation( $default = true ) {
	return (bool) apply_filters( 'bp_restrict_group_creation', (bool) get_option( 'bp_restrict_group_creation', $default ) );
}

/**
 * Have we migrated to using the WordPress admin bar?
 *
 * @since BuddyPress (r3386)
 *
 * @param $default bool Optional. Default value true
 *
 * @todo Move into groups component
 * @uses get_option() To get the WP editor option
 * @return bool Use WP editor?
 */
function bp_force_buddybar( $default = true ) {
	return (bool) apply_filters( 'bp_force_buddybar', (bool) get_option( 'bp-force-buddybar', $default ) );
}

/**
 * Use the WordPress editor if available
 *
 * @since BuddyPress (r3386)
 *
 * @param $default bool Optional. Default value true
 *
 * @uses get_option() To get the WP editor option
 * @return bool Use WP editor?
 */
function bp_use_wp_editor( $default = true ) {
	return (bool) apply_filters( 'bp_use_wp_editor', (bool) get_option( '_bp_use_wp_editor', $default ) );
}

/**
 * Output the group forums root parent forum id
 *
 * @since BuddyPress (1.6)
 *
 * @param $default bool Optional. Default value
 */
function bp_group_forums_root_id( $default = '0' ) {
	echo bp_get_group_forums_root_id( $default );
}
	/**
	 * Return the group forums root parent forum id
	 *
	 * @since BuddyPress (1.6)
	 *
	 * @param $default bool Optional. Default value 0
	 *
	 * @uses get_option() To get the maximum title length
	 * @return int Is anonymous posting allowed?
	 */
	function bp_get_group_forums_root_id( $default = '0' ) {
		return (int) apply_filters( 'bp_get_group_forums_root_id', (int) get_option( '_bbp_group_forums_root_id', $default ) );
	}

/**
 * Checks if BuddyPress Group Forums are enabled
 *
 * @since BuddyPress (1.6)
 *
 * @param $default bool Optional. Default value true
 *
 * @uses get_option() To get the group forums option
 * @return bool Is group forums enabled or not
 */
function bp_is_group_forums_active( $default = true ) {
	return (bool) apply_filters( 'bp_is_group_forums_active', (bool) get_option( '_bbp_enable_group_forums', $default ) );
}

/**
 * Checks if Akismet is enabled
 *
 * @since BuddyPress (1.6)
 *
 * @param $default bool Optional. Default value true
 *
 * @uses get_option() To get the Akismet option
 * @return bool Is Akismet enabled or not
 */
function bp_is_akismet_active( $default = true ) {
	return (bool) apply_filters( 'bp_is_akismet_active', (bool) get_option( '_bp_enable_akismet', $default ) );
}

?>
