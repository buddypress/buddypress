<?php
/**
 * BuddyPress Notices functions.
 *
 * @package buddypress\bp-members\bp-members-notices
 * @since 14.0.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Load the Commmunity Notices Admin.
add_action( bp_core_admin_hook(), array( 'BP_Members_Notices_Admin', 'register_notices_admin' ), 9 );

/**
 * Handle user dismissal of sitewide notices.
 *
 * @since 14.0.0
 *
 * @return bool False on failure.
 */
function bp_members_dismiss_notice() {

	/**
	 *
	 *
	 *
	 *
	 * @todo check to see is we still need this code.
	 *
	 */
	return false;

	// Bail if the current user isn't logged in.
	if ( ! is_user_logged_in() ) {
		return false;
	}

	// Check the nonce.
	check_admin_referer( 'messages_dismiss_notice' );

	// Dismiss the notice.
	$success = bp_messages_dismiss_sitewide_notice();

	// User feedback.
	if ( $success ) {
		$feedback = __( 'Notice has been dismissed.', 'buddypress' );
		$type     = 'success';
	} else {
		$feedback = __( 'There was a problem dismissing the notice.', 'buddypress');
		$type     = 'error';
	}

	// Add feedback message.
	bp_core_add_message( $feedback, $type );

	// Redirect.
	$redirect_to = bp_loggedin_user_url( bp_members_get_path_chunks( array( bp_get_messages_slug() ) ) );

	bp_core_redirect( $redirect_to );
}

/**
 * Handle editing of sitewide notices.
 *
 * @since 2.4.0 This function was split from messages_screen_notices(). See #6505.
 *
 * @return bool
 */
function bp_members_edit_notice() {

	/**
	 *
	 *
	 *
	 *
	 * @todo check to see is we still need this code.
	 *
	 */
	return false;

	// Get the notice ID (1|2|3).
	$notice_id = bp_action_variable( 1 );

	// Bail if notice ID is not numeric.
	if ( empty( $notice_id ) || ! is_numeric( $notice_id ) ) {
		return false;
	}

	// Bail if the current user doesn't have administrator privileges.
	if ( ! bp_current_user_can( 'bp_moderate' ) ) {
		return false;
	}

	// Get the action (deactivate|activate|delete).
	$action = sanitize_key( bp_action_variable( 0 ) );

	// Check the nonce.
	check_admin_referer( "messages_{$action}_notice" );

	// Get the notice from database.
	$notice   = new BP_Members_Notice( $notice_id );
	$success  = false;
	$feedback = '';

	// Take action.
	switch ( $action ) {

		// Deactivate.
		case 'deactivate' :
			$success  = $notice->deactivate();
			$feedback = true === $success
				? __( 'Notice deactivated successfully.',              'buddypress' )
				: __( 'There was a problem deactivating that notice.', 'buddypress' );
			break;

		// Activate.
		case 'activate' :
			$success  = $notice->activate();
			$feedback = true === $success
				? __( 'Notice activated successfully.',              'buddypress' )
				: __( 'There was a problem activating that notice.', 'buddypress' );
			break;

		// Delete.
		case 'delete' :
			$success  = $notice->delete();
			$feedback = true === $success
				? __( 'Notice deleted successfully.',              'buddypress' )
				: __( 'There was a problem deleting that notice.', 'buddypress' );
			break;
	}

	// Feedback.
	if ( ! empty( $feedback ) ) {

		// Determine message type.
		$type = ( true === $success )
			? 'success'
			: 'error';

		// Add feedback message.
		bp_core_add_message( $feedback, $type );
	}

	// Redirect.
	$redirect_to = bp_loggedin_user_url( bp_members_get_path_chunks( array( bp_get_messages_slug(), 'notices' ) ) );

	bp_core_redirect( $redirect_to );
}
