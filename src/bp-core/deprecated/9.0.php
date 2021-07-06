<?php
/**
 * Deprecated functions.
 *
 * @package BuddyPress
 * @deprecated 9.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Get user URL.
 *
 * @since 5.0.0
 * @deprecated 9.0.0
 *
 * @param  int $user_id User ID.
 * @return string
 */
function bp_rest_get_user_url( $user_id ) {
	_deprecated_function( __FUNCTION__, '9.0.0', 'bp_rest_get_object_url( $user_id, \'members\' )' );
	return bp_rest_get_object_url( $user_id, 'members' );
}
