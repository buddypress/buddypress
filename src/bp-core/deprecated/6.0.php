<?php
/**
 * Deprecated functions.
 *
 * @deprecated 6.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Check if the current WordPress version is using Plupload 2.1.1
 *
 * Plupload 2.1.1 was introduced in WordPress 3.9. Our bp-plupload.js
 * script requires it. So we need to make sure the current WordPress
 * match with our needs.
 *
 * @since 2.3.0
 * @since 3.0.0 This is always true.
 * @deprecated 6.0.0
 *
 * @return bool Always true.
 */
function bp_attachments_is_wp_version_supported() {
	_deprecated_function( __FUNCTION__, '6.0.0' );
	return true;
}

/**
 * Setup the avatar upload directory for a user.
 *
 * @since 1.0.0
 * @deprecated 6.0.0
 *
 * @package BuddyPress Core
 *
 * @param string $directory The root directory name. Optional.
 * @param int    $user_id   The user ID. Optional.
 * @return array Array containing the path, URL, and other helpful settings.
 */
function xprofile_avatar_upload_dir( $directory = 'avatars', $user_id = 0 ) {
	_deprecated_function( __FUNCTION__, '6.0.0', 'bp_members_avatar_upload_dir()' );

	$avatar_dir = bp_members_avatar_upload_dir( $directory, $user_id );

	/** This filter is documented in wp-includes/deprecated.php */
	return apply_filters_deprecated( 'xprofile_avatar_upload_dir', array( $avatar_dir ), '6.0.0', 'bp_members_avatar_upload_dir' );
}

/**
 * This function runs when an action is set for a screen:
 * example.com/members/andy/profile/change-avatar/ [delete-avatar]
 *
 * The function will delete the active avatar for a user.
 *
 * @since 1.0.0
 * @deprecated 6.0.0
 */
function xprofile_action_delete_avatar() {
	_deprecated_function( __FUNCTION__, '6.0.0', 'bp_members_action_delete_avatar()' );

	bp_members_action_delete_avatar();
}

/**
 * Displays the change cover image page.
 *
 * @since 2.4.0
 * @deprecated 6.0.0
 */
function xprofile_screen_change_cover_image() {
	_deprecated_function( __FUNCTION__, '6.0.0', 'bp_members_screen_change_cover_image()' );

	bp_members_screen_change_cover_image();
}

/**
 * Handles the uploading and cropping of a user avatar. Displays the change avatar page.
 *
 * @since 1.0.0
 * @deprecated 6.0.0
 */
function xprofile_screen_change_avatar() {
	_deprecated_function( __FUNCTION__, '6.0.0', 'bp_members_screen_change_avatar()' );

	bp_members_screen_change_avatar();
}

/**
 * Output the status of the current group in the loop.
 *
 * Either 'Public' or 'Private'.
 *
 * @since 1.0.0
 * @deprecated 6.0.0 Not used anymore.
 *
 * @param object|bool $group Optional. Group object.
 *                           Default: current group in loop.
 */
function bp_group_public_status( $group = false ) {
	_deprecated_function( __FUNCTION__, '6.0' );
}
	/**
	 * Return the status of the current group in the loop.
	 *
	 * Either 'Public' or 'Private'.
	 *
	 * @since 1.0.0
	 * @deprecated 6.0.0 Not used anymore.
	 *
	 * @param object|bool $group Optional. Group object.
	 *                           Default: current group in loop.
	 * @return string
	 */
	function bp_get_group_public_status( $group = false ) {
		_deprecated_function( __FUNCTION__, '6.0' );
	}

/**
 * Output whether the current group in the loop is public.
 *
 * No longer used in BuddyPress.
 *
 * @deprecated 6.0.0 Not used anymore.
 *
 * @param object|bool $group Optional. Group object.
 *                           Default: current group in loop.
 */
function bp_group_is_public( $group = false ) {
	_deprecated_function( __FUNCTION__, '6.0' );
}
	/**
	 * Return whether the current group in the loop is public.
	 *
	 * No longer used in BuddyPress.
	 *
	 * @deprecated 6.0.0 Not used anymore.
	 *
	 * @param object|bool $group Optional. Group object.
	 *                           Default: current group in loop.
	 * @return mixed
	 */
	function bp_get_group_is_public( $group = false ) {
		_deprecated_function( __FUNCTION__, '6.0' );
	}

/**
 * Add illegal blog names to WP so that root components will not conflict with blog names on a subdirectory installation.
 *
 * For example, it would stop someone creating a blog with the slug "groups".
 *
 * @since 1.0.0
 * @deprecated 6.0.0
 */
function bp_core_add_illegal_names() {
	_deprecated_function( __FUNCTION__, '6.0' );

	update_site_option( 'illegal_names', get_site_option( 'illegal_names' ), array() );
}
