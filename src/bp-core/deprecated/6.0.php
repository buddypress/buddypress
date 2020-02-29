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
