<?php
/**
 * Messages: Compose action handler.
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
 * @return bool
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
	$is_notice   = isset( $_POST['send-notice'] );
	$path_chunks = array( bp_get_messages_slug() );

	// List required vars and error messages.
	$required_vars = array(
		'subject' => __( 'Please enter a subject line.', 'buddypress' ),
		'content' => __( 'Please enter some content.', 'buddypress' ),
	);

	// The username is only needed for private messages.
	if ( ! $is_notice ) {
		$required_vars = array_merge(
			array(
				'send_to_usernames' => __( 'Please enter a username or Friend\'s name.', 'buddypress' ),
			),
			$required_vars
		);
	}

	// Calculate whether some required vars are missing.
	$needed_keys    = array_intersect_key( $_POST, $required_vars );
	$needs_feedback = count( array_filter( $needed_keys ) ) !== count( $required_vars );

	// Prevent notice errors.
	$required_args = array_map(
		'wp_unslash',
		bp_parse_args(
			$needed_keys,
			array(
				'send_to_usernames' => '',
				'subject'           => '',
				'content'           => ''
			)
		)
	);

	// Use cookies to preserve existing values.
	messages_add_callback_values(
		implode( ' ', wp_parse_list( $required_args['send_to_usernames'] ) ),
		esc_html( $required_args['subject'] ),
		esc_textarea( $required_args['content'] )
	);

	// Handle required vars.
	if ( $needs_feedback ) {
		$success       = false;
		$path_chunks[] = 'compose';
		$feedbacks     = array( __( 'Your message was not sent.', 'buddypress' ) );

		foreach ( $required_vars as $required_key => $required_var_feedback ) {
			if ( ! $required_args[ $required_key ] ) {
				$feedbacks[] = $required_var_feedback;
			}
		}

		// Set the feedback message.
		$feedback    = implode( "\n", $feedbacks );
		$redirect_to = bp_loggedin_user_url( bp_members_get_path_chunks( $path_chunks ) );
	} else {

		// Site-wide notice.
		if ( $is_notice ) {

			// Attempt to save the notice and redirect to notices.
			if ( messages_send_notice( $required_args['subject'], $required_args['content'] ) ) {
				$success       = true;
				$feedback      = __( 'Notice successfully created.', 'buddypress' );
				$path_chunks[] = 'notices';

				// Notice could not be sent.
			} else {
				$success       = false;
				$path_chunks[] = 'compose';
				$feedback      = __( 'Notice was not created. Please try again.', 'buddypress' );
			}

			$redirect_to = bp_loggedin_user_url( bp_members_get_path_chunks( $path_chunks ) );

			// Private conversation.
		} else {

			// Filter recipients into the format we need - array( 'username/userid', 'username/userid' ).
			$autocomplete_recipients = array();
			if ( isset( $_POST['send-to-input'] ) && $_POST['send-to-input'] ) {
				$autocomplete_recipients = (array) explode( ',', $_POST['send-to-input'] );
			}

			$typed_recipients = (array) explode( ' ', $required_args['send_to_usernames'] );
			$recipients       = array_merge( $autocomplete_recipients, $typed_recipients );

			/**
			 * Filters the array of recipients to receive the composed message.
			 *
			 * @since 1.2.10
			 *
			 * @param array $recipients Array of recipients to receive message.
			 */
			$recipients = apply_filters( 'bp_messages_recipients', $recipients );

			// Attempt to send the message.
			$send = messages_new_message(
				array(
					'recipients' => $recipients,
					'subject'    => $required_args['subject'],
					'content'    => $required_args['content'],
					'error_type' => 'wp_error',
				)
			);

			// Send the message and redirect to it.
			if ( true === is_int( $send ) ) {
				$success       = true;
				$feedback      = __( 'Message successfully sent.', 'buddypress' );
				$path_chunks[] = 'view';
				$path_chunks[] = array( $send );

				// Message could not be sent.
			} else {
				$success       = false;
				$path_chunks[] = 'compose';
				$feedback      = $send->get_error_message();
			}

			$redirect_to = bp_loggedin_user_url( bp_members_get_path_chunks( $path_chunks ) );
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
