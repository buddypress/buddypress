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

		'_bp_db_version'            => '155',

		/** Settings **********************************************************/

		// Lock post editing after 5 minutes
		'_bp_edit_lock'             => '5',

		// Throttle post time to 10 seconds
		'_bp_throttle_time'         => '10',

		// Favorites
		'_bp_enable_favorites'      => true,

		// Subscriptions
		'_bp_enable_subscriptions'  => true,

		// Allow anonymous posting
		'_bp_allow_anonymous'       => false,

		// Users from all sites can post
		'_bp_allow_global_access'   => false,

		// Use the WordPress editor if available
		'_bp_use_wp_editor'         => true,

		/** Per Page **********************************************************/

		// Topics per page
		'_bp_topics_per_page'       => '15',

		// Replies per page
		'_bp_replies_per_page'      => '15',

		// Forums per page
		'_bp_forums_per_page'       => '50',

		// Topics per RSS page
		'_bp_topics_per_rss_page'   => '25',

		// Replies per RSS page
		'_bp_replies_per_rss_page'  => '25',

		/** Page For **********************************************************/

		// Page for forums
		'_bp_page_for_forums'       => '0',

		// Page for forums
		'_bp_page_for_topics'       => '0',

		// Page for login
		'_bp_page_for_login'        => '0',

		// Page for register
		'_bp_page_for_register'     => '0',

		// Page for lost-pass
		'_bp_page_for_lost_pass'    => '0',

		/** Archive Slugs *****************************************************/

		// Forum archive slug
		'_bp_root_slug'             => 'forums',

		// Topic archive slug
		'_bp_topic_archive_slug'    => 'topics',

		/** Single Slugs ******************************************************/

		// Include Forum archive before single slugs
		'_bp_include_root'          => true,

		// Forum slug
		'_bp_forum_slug'            => 'forum',

		// Topic slug
		'_bp_topic_slug'            => 'topic',

		// Reply slug
		'_bp_reply_slug'            => 'reply',

		// Topic tag slug
		'_bp_topic_tag_slug'        => 'topic-tag',

		/** Other Slugs *******************************************************/

		// User profile slug
		'_bp_user_slug'             => 'users',

		// View slug
		'_bp_view_slug'             => 'view',

		/** Topics ************************************************************/

		// Title Max Length
		'_bp_title_max_length'      => '80',

		/** BuddyPress ********************************************************/

		// Enable BuddyPress Group Extension
		'_bbp_enable_group_forums'  => true,

		// Group Forums parent forum id
		'_bbp_group_forums_root_id' => '0',

		/** Akismet ***********************************************************/

		// Users from all sites can post
		'_bp_enable_akismet'        => true,
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
 * inside the $bbp->options array.
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
 * $bbp->options array.
 *
 * @since BuddyPress (1.6)
 *
 * @global BuddyPress $bbp
 * @param bool $value Optional. Default value false
 * @return mixed false if not overloaded, mixed if set
 */
function bp_pre_get_option( $value = false ) {
	global $bbp;

	// Get the name of the current filter so we can manipulate it
	$filter = current_filter();

	// Remove the filter prefix
	$option = str_replace( 'pre_option_', '', $filter );

	// Check the options global for preset value
	if ( !empty( $bbp->options[$option] ) )
		$value = $bbp->options[$option];

	// Always return a value, even if false
	return $value;
}

/** Active? *******************************************************************/

/**
 * Checks if favorites feature is enabled.
 *
 * @since BuddyPress (1.6)
 *
 * @param $default bool Optional.Default value true
 *
 * @uses get_option() To get the favorites option
 * @return bool Is favorites enabled or not
 */
function bp_is_favorites_active( $default = true ) {
	return (bool) apply_filters( 'bp_is_favorites_active', (bool) get_option( '_bp_enable_favorites', $default ) );
}

/**
 * Checks if subscription feature is enabled.
 *
 * @since BuddyPress (1.6)
 *
 * @param $default bool Optional.Default value true
 *
 * @uses get_option() To get the subscriptions option
 * @return bool Is subscription enabled or not
 */
function bp_is_subscriptions_active( $default = true ) {
	return (bool) apply_filters( 'bp_is_subscriptions_active', (bool) get_option( '_bp_enable_subscriptions', $default ) );
}

/**
 * Are topic and reply revisions allowed
 *
 * @since BuddyPress (r3412)
 *
 * @param $default bool Optional. Default value true
 *
 * @uses get_option() To get the allow revisions
 * @return bool Are revisions allowed?
 */
function bp_allow_revisions( $default = true ) {
	return (bool) apply_filters( 'bp_allow_revisions', (bool) get_option( '_bp_allow_revisions', $default ) );
}

/**
 * Is the anonymous posting allowed?
 *
 * @since BuddyPress (1.6)
 *
 * @param $default bool Optional. Default value
 *
 * @uses get_option() To get the allow anonymous option
 * @return bool Is anonymous posting allowed?
 */
function bp_allow_anonymous( $default = false ) {
	return apply_filters( 'bp_allow_anonymous', (bool) get_option( '_bp_allow_anonymous', $default ) );
}

/**
 * Is this community available to all users on all sites in this installation?
 *
 * @since BuddyPress (1.6)
 *
 * @param $default bool Optional. Default value false
 *
 * @uses get_option() To get the global access option
 * @return bool Is global access allowed?
 */
function bp_allow_global_access( $default = false ) {
	return (bool) apply_filters( 'bp_allow_global_access', (bool) get_option( '_bp_allow_global_access', $default ) );
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
 * Output the maximum length of a title
 *
 * @since BuddyPress (r3246)
 *
 * @param $default bool Optional. Default value 80
 */
function bp_title_max_length( $default = '80' ) {
	echo bp_get_title_max_length( $default );
}
	/**
	 * Return the maximum length of a title
	 *
	 * @since BuddyPress (1.6)
	 *
	 * @param $default bool Optional. Default value 80
	 *
	 * @uses get_option() To get the maximum title length
	 * @return int Is anonymous posting allowed?
	 */
	function bp_get_title_max_length( $default = '80' ) {
		return (int) apply_filters( 'bp_get_title_max_length', (int) get_option( '_bp_title_max_length', $default ) );
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
	 * Return the grop forums root parent forum id
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
