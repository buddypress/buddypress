<?php
/**
 * Messages: Compose action handler
 *
 * @package BuddyPress
 * @subpackage MessageActions
 * @since 3.0.0
 */

/**
 * Handle creating of private messages or sitewide notices
 *
 * @since 2.4.0 This function was split from messages_screen_compose(). See #6505.
 *
 * @return boolean
 */
function bp_messages_action_create_message() {

	// Bail if not posting to the compose message screen.
	if ( ! bp_is_post_request() || ! bp_is_messages_component() || ! bp_is_current_action( 'compose' ) ) {
		return false;
	}

	// Check the nonce.
	check_admin_referer( 'messages_send_message' );

	// Define local variables.
	$redirect_to = '';
	$feedback    = '';
	$success     = false;

	// Missing subject or content.
	if ( empty( $_POST['subject'] ) || empty( $_POST['content'] ) ) {
		$success  = false;

		if ( empty( $_POST['subject'] ) ) {
			$feedback = __( 'Your message was not sent. Please enter a subject line.', 'buddypress' );
		} else {
			$feedback = __( 'Your message was not sent. Please enter some content.', 'buddypress' );
		}

	// Subject and content present.
	} else {

		// Setup the link to the logged-in user's messages.
		$member_messages = trailingslashit( bp_loggedin_user_domain() . bp_get_messages_slug() );

		// Site-wide notice.
		if ( isset( $_POST['send-notice'] ) ) {

			// Attempt to save the notice and redirect to notices.
			if ( messages_send_notice( $_POST['subject'], $_POST['content'] ) ) {
				$success     = true;
				$feedback    = __( 'Notice successfully created.', 'buddypress' );
				$redirect_to = trailingslashit( $member_messages . 'notices' );

			// Notice could not be sent.
			} else {
				$success  = false;
				$feedback = __( 'Notice was not created. Please try again.', 'buddypress' );
			}

		// Private conversation.
		} else {

			// Filter recipients into the format we need - array( 'username/userid', 'username/userid' ).
			$autocomplete_recipients = (array) explode( ',', $_POST['send-to-input']     );
			$typed_recipients        = (array) explode( ' ', $_POST['send_to_usernames'] );
			$recipients              = array_merge( $autocomplete_recipients, $typed_recipients );

			/**
			 * Filters the array of recipients to receive the composed message.
			 *
			 * @since 1.2.10
			 *
			 * @param array $recipients Array of recipients to receive message.
			 */
			$recipients = apply_filters( 'bp_messages_recipients', $recipients );

			// Attempt to send the message.
			$send = messages_new_message( array(
				'recipients' => $recipients,
				'subject'    => $_POST['subject'],
				'content'    => $_POST['content'],
				'error_type' => 'wp_error'
			) );

			// Send the message and redirect to it.
			if ( true === is_int( $send ) ) {
				$success     = true;
				$feedback    = __( 'Message successfully sent.', 'buddypress' );
				$view        = trailingslashit( $member_messages . 'view' );
				$redirect_to = trailingslashit( $view . $send );

			// Message could not be sent.
			} else {
				$success  = false;
				$feedback = $send->get_error_message();
			}
		}
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

	// Maybe redirect.
	if ( ! empty( $redirect_to ) ) {
		bp_core_redirect( $redirect_to );
	}
}
add_action( 'bp_actions', 'bp_messages_action_create_message' );