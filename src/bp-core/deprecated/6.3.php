<?php
/**
 * Deprecated functions.
 *
 * @package BuddyPress
 * @deprecated 6.3.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Includes a message into the sent invitation email.
 *
 * @since 3.0.0
 * @deprecated 6.3.0
 *
 * @param string $message Optional. The message to send with the invite.
 * @return string The invitation message.
 */
function bp_nouveau_groups_invites_custom_message( $message = '' ) {
	_deprecated_function( __FUNCTION__, '6.3.0' );

	if ( empty( $message ) ) {
		return $message;
	}

	$bp = buddypress();

	if ( empty( $bp->groups->invites_message ) ) {
		return $message;
	}

	$message = str_replace(
		'---------------------',
		"
---------------------\n
" . $bp->groups->invites_message . "\n
---------------------
	",
		$message
	);

	return $message;
}
