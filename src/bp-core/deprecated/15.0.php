<?php
/**
 * Deprecated functions.
 *
 * @package BuddyPress
 * @deprecated 15.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Is the current page the Notices screen?
 *
 * Eg http://example.com/members/joe/messages/notices/.
 *
 * @since 1.1.0
 * @deprecated 15.0.0
 *
 * @return bool True if the current page is the Notices screen.
 */
function bp_is_notices() {
	_deprecated_function( __FUNCTION__, '15.0.0' );
	return false;
}

/**
 * Handle editing of sitewide notices.
 *
 * @since 2.4.0 This function was split from messages_screen_notices(). See #6505.
 * @deprecated 15.0.0
 *
 * @return void
 */
function bp_messages_action_edit_notice() {
	_deprecated_function( __FUNCTION__, '15.0.0' );
}

/**
 * Handle user dismissal of sitewide notices.
 *
 * @since 9.0.0
 * @deprecated 15.0.0
 *
 * @return bool False on failure.
 */
function bp_messages_action_dismiss_notice() {
	_deprecated_function( __FUNCTION__, '15.0.0' );
	return false;
}

/**
 * Load the Messages > Notices screen.
 *
 * @since 1.0.0
 * @deprecated 15.0.0
 *
 * @return void
 */
function messages_screen_notices() {
	_deprecated_function( __FUNCTION__, '15.0.0' );
}

/**
 * Send a notice.
 *
 * @since 1.0.0
 * @deprecated 15.0.0
 *
 * @param string $subject Subject of the notice.
 * @param string $message Content of the notice.
 * @return bool True on success, false on failure.
 */
function messages_send_notice( $subject, $message ) {
	_deprecated_function( __FUNCTION__, '15.0.0' );
	$notice_id = bp_members_send_notice(
		array(
			'title'   => $subject,
			'content' => $message
		)
	);

	if ( ! is_wp_error( $notice_id )  ) {
		$notice = new BP_Members_Notice( $notice_id );
		$retval = true;
	} else {
		$notice = $notice_id;
		$retval = false;
	}

	/**
	 * Fires after a notice has been successfully sent.
	 *
	 * @since 1.0.0
	 * @deprecated 15.0.0
	 *
	 * @param string                     $subject Subject of the notice.
	 * @param string                     $message Content of the notice.
	 * @param BP_Members_Notice|WP_Error $notice  Notice object sent. A WP Error if something went wrong.
	 */
	do_action_deprecated( 'messages_send_notice', array( $subject, $message, $notice ), '15.0.0' );

	return $retval;
}

/**
 * Output the subject of the current notice in the loop.
 *
 * @since 5.0.0 The $notice parameter has been added.
 * @deprecated 15.0.0
 *
 * @param BP_Members_Notice $notice The notice object.
 */
function bp_message_notice_subject( $notice = null ) {
	_deprecated_function( __FUNCTION__, '15.0.0', 'bp_notice_title' );
	return bp_notice_title( $notice );
}
/**
 * Get the subject of the current notice in the loop.
 *
 * @since 5.0.0 The $notice parameter has been added.
 * @deprecated 15.0.0
 *
 * @param BP_Members_Notice|null $notice The notice object.
 * @return string
 */
function bp_get_message_notice_subject( $notice = null ) {
	_deprecated_function( __FUNCTION__, '15.0.0', 'bp_get_notice_title' );
	return bp_get_notice_title( $notice );
}

/**
 * Output the text of the current notice in the loop.
 *
 * @since 5.0.0 The $notice parameter has been added.
 * @deprecated 15.0.0
 *
 * @param BP_Members_Notice $notice The notice object.
 */
function bp_message_notice_text( $notice = null ) {
	_deprecated_function( __FUNCTION__, '15.0.0', 'bp_notice_content' );
	return bp_notice_content( $notice );
}

/**
 * Get the text of the current notice in the loop.
 *
 * @since 5.0.0 The $notice parameter has been added.
 * @deprecated 15.0.0
 *
 * @param BP_Members_Notice|null $notice The notice object.
 * @return string
 */
function bp_get_message_notice_text( $notice = null ) {
	_deprecated_function( __FUNCTION__, '15.0.0', 'bp_get_notice_content' );
	return bp_get_notice_content( $notice );
}

/**
 * Output the URL for dismissing the current notice for the current user.
 *
 * @since 9.0.0
 * @deprecated 15.0.0
 */
function bp_message_notice_dismiss_link() {
	_deprecated_function( __FUNCTION__, '15.0.0', 'bp_notice_dismiss_url' );
	return bp_notice_dismiss_url();
}

/**
 * Get the URL for dismissing the current notice for the current user.
 *
 * @since 9.0.0
 * @deprecated 15.0.0
 * @return string URL for dismissing the current notice for the current user.
 */
function bp_get_message_notice_dismiss_link() {
	_deprecated_function( __FUNCTION__, '15.0.0', 'bp_get_notice_dismiss_url' );
	return bp_get_notice_dismiss_url();
}

/**
 * Callback function to render the BP Sitewide Notices Block.
 *
 * @since 9.0.0
 * @deprecated 15.0.0
 *
 * @param array $attributes The block attributes.
 * @return string HTML output.
 */
function bp_messages_render_sitewide_notices_block( $attributes = array() ) {
	_deprecated_function( __FUNCTION__, '15.0.0', 'bp_members_render_notices_block' );
	return bp_members_render_notices_block( $attributes = array() );
}

/**
 * Dismiss a sitewide notice for a user.
 *
 * @since 9.0.0
 * @deprecated 15.0.0
 *
 * @param int $user_id   ID of the user to dismiss the notice for.
 *                       Defaults to the logged-in user.
 * @param int $notice_id ID of the notice to be dismissed.
 *                       Defaults to the currently active notice.
 * @return bool False on failure, true if notice is dismissed
 *              (or was already dismissed).
 */
function bp_messages_dismiss_sitewide_notice( $user_id = 0, $notice_id = 0 ) {
	_deprecated_function( __FUNCTION__, '15.0.0', 'bp_members_dismiss_notice' );
	return bp_members_dismiss_notice( $user_id, $notice_id );
}
