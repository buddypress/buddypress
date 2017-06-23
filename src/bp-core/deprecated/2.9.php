<?php
/**
 * Deprecated functions.
 *
 * @deprecated 2.9.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Replace default WordPress avatars with BP avatars, if available.
 *
 * See 'get_avatar' filter description in wp-includes/pluggable.php.
 *
 * @since 1.1.0
 * @since 2.4.0 Added $args parameter to coincide with WordPress 4.2.0.
 *
 * @param string            $avatar  The avatar path passed to 'get_avatar'.
 * @param int|string|object $user    A user ID, email address, or comment object.
 * @param int               $size    Size of the avatar image ('thumb' or 'full').
 * @param string            $default URL to a default image to use if no avatar is available.
 * @param string            $alt     Alternate text to use in image tag. Default: ''.
 * @param array             $args    Arguments passed to get_avatar_data(), after processing.
 * @return string BP avatar path, if found; else the original avatar path.
 */
function bp_core_fetch_avatar_filter( $avatar, $user, $size, $default, $alt = '', $args = array() ) {
	_deprecated_function( __FUNCTION__, '2.9' );
	return $avatar;
}